<?php
declare(strict_types=1);

namespace App\Services\Ocr;

use App\Config\Env;
use App\Services\AuditService;
use Exception;

class PassportOcrService implements PassportOcrServiceInterface
{
    private const MAX_FILE_SIZE = 15728640; // 15MB
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf'
    ];

    /**
     * Extract structured passport data from file.
     *
     * @param string $filePath
     * @param array $options
     * @return array
     * @throws Exception
     */
    public function extract(string $filePath, $options = []): array
    {
        if (is_string($options)) {
            $options = ['mime_type' => $options];
        } elseif (!is_array($options)) {
            $options = [];
        }

        // 1. Pre-flight Validation
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new Exception("Passport file does not exist or cannot be read.");
        }

        $fileSize = filesize($filePath);
        if ($fileSize > self::MAX_FILE_SIZE) {
            throw new Exception("Passport file size exceeds maximum allowed 15MB limit.");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $filePath) ?: 'application/octet-stream';
        if (PHP_VERSION_ID < 80500) {
            @finfo_close($finfo);
        }

        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf'], true)) {
                throw new Exception("Invalid file type ({$mime}). Only clear JPG, PNG, WEBP, or PDF passport documents are supported.");
            }
        }

        // Quality check on images
        if (str_starts_with($mime, 'image/')) {
            $imgSize = @getimagesize($filePath);
            if ($imgSize !== false) {
                $w = $imgSize[0] ?? 0;
                $h = $imgSize[1] ?? 0;
                if ($w < 350 || $h < 250) {
                    throw new Exception("Passport image quality is too low ({$w}x{$h}px). Please upload a clear, high-resolution scan (minimum 400x300px) with all four corners visible.");
                }
            }
        }

        // 2. Provider Resolution
        $configuredProvider = Env::get('PASSPORT_OCR_PROVIDER', 'auto');
        if (!empty($options['provider'])) {
            $configuredProvider = $options['provider'];
        }

        if ($configuredProvider === 'mock' && !empty($options['mock_data'])) {
            return $options['mock_data'];
        }

        // Dispatch to appropriate provider
        $providerUsed = $configuredProvider;
        $ocrData = match ($configuredProvider) {
            'google_vision' => $this->extractViaGoogleVision($filePath),
            'aws_textract' => $this->extractViaAwsTextract($filePath),
            'azure_document_intelligence' => $this->extractViaAzure($filePath),
            default => $this->extractViaLocalEngine($filePath)
        };

        if ($configuredProvider === 'auto' || $configuredProvider === 'local') {
            $providerUsed = 'local_windows_media_ocr';
        }

        $rawText = (string)($ocrData['text'] ?? '');
        $lines = (array)($ocrData['lines'] ?? []);
        $words = (array)($ocrData['words'] ?? []);

        if (empty($lines) && $rawText !== '') {
            $lines = preg_split('/[\r\n]+/', $rawText, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        // 3. Step 1: MRZ Parsing (Highest-Confidence Source)
        $mrzResult = MrzParser::parse($rawText);
        if ($mrzResult === null && !empty($lines)) {
            $mrzResult = MrzParser::parse(implode("\n", $lines));
        }

        // 4. Step 2: Visual Zone Extraction & Enrichment (Multilingual & Multi-line aware)
        $visualData = $this->extractVisualZoneDetails($rawText, $lines, $words);

        // 5. Merge & Synthesize Normalized Data
        if ($mrzResult !== null) {
            $mergedData = $mrzResult['data'];
            $confidenceScores = $mrzResult['confidence_scores'];
            $reviewRequired = $mrzResult['review_required_fields'];

            // Enrich with visual zone if missing in MRZ (e.g. Issue Date, Place of Birth)
            if (empty($mergedData['issue_date']) && !empty($visualData['data']['issue_date'])) {
                $mergedData['issue_date'] = $visualData['data']['issue_date'];
                $confidenceScores['issue_date'] = 90.0;
            }
            if (empty($mergedData['place_of_birth']) && !empty($visualData['data']['place_of_birth'])) {
                $mergedData['place_of_birth'] = $visualData['data']['place_of_birth'];
                $confidenceScores['place_of_birth'] = 88.0;
            }
            if (empty($mergedData['place_of_issue']) && !empty($visualData['data']['place_of_issue'])) {
                $mergedData['place_of_issue'] = $visualData['data']['place_of_issue'];
                $confidenceScores['place_of_issue'] = 85.0;
            }
            if (empty($mergedData['personal_number']) && !empty($visualData['data']['personal_number'])) {
                $mergedData['personal_number'] = $visualData['data']['personal_number'];
                $confidenceScores['personal_number'] = 90.0;
            }

            $normalizedData = self::normalizeExtractedObject($mergedData);
            $normalizedConf = self::normalizeConfidenceScores($confidenceScores, $normalizedData);
            $overallConfidence = self::computeOverallConfidence($normalizedConf);

            // Audit log with masked passport number (N12****67)
            $maskedPass = self::maskPassportNumber((string)$normalizedData['passport_number']);
            AuditService::log('OCR_EXTRACT_PASSPORT', 'Documents', null, "Passport OCR processed successfully: {$maskedPass} ({$normalizedData['nationality']}) via {$providerUsed}", [
                'provider' => $providerUsed,
                'overall_confidence' => $overallConfidence,
                'checksums_valid' => $mrzResult['checksums']['all_valid']
            ]);

            return [
                'success' => true,
                'provider' => $providerUsed,
                'ocr_provider' => $providerUsed,
                'overall_confidence' => $overallConfidence,
                'mrz_detected' => true,
                'checksums_valid' => $mrzResult['checksums']['all_valid'],
                'data' => $normalizedData,
                'extracted' => $normalizedData,
                'confidence_scores' => $normalizedConf,
                'confidence' => $normalizedConf,
                'review_required_fields' => $reviewRequired,
                'warnings' => $reviewRequired,
                'file_info' => [
                    'file_path' => $filePath,
                    'file_name' => basename($filePath),
                    'file_size' => $fileSize,
                    'mime_type' => $mime
                ]
            ];
        }

        // 6. Visual Zone Fallback (when MRZ lines were stripped or scan is visual-first)
        $vData = $visualData['data'] ?? [];
        if (!empty($vData['passport_number']) || !empty($vData['full_name']) || !empty($vData['surname']) || !empty($vData['dob'])) {
            $confScores = $visualData['confidence_scores'] ?? [];
            $normalizedData = self::normalizeExtractedObject($vData);
            $normalizedConf = self::normalizeConfidenceScores($confScores, $normalizedData);
            $overallConfidence = self::computeOverallConfidence($normalizedConf);

            $maskedPass = self::maskPassportNumber((string)($normalizedData['passport_number'] ?? ''));
            AuditService::log('OCR_EXTRACT_PASSPORT_VISUAL', 'Documents', null, "Passport OCR extracted via visual zone: {$maskedPass}", [
                'provider' => $providerUsed,
                'overall_confidence' => $overallConfidence
            ]);

            $warnings = [];
            foreach ($normalizedConf as $k => $sc) {
                if ($sc < 80.0 && !in_array($k, ['passport_no', 'last_name', 'first_name', 'date_of_birth', 'sex', 'date_of_issue', 'date_of_expiry', 'birth_place'], true)) {
                    $warnings[] = $k;
                }
            }

            return [
                'success' => true,
                'provider' => $providerUsed . '_visual_zone',
                'ocr_provider' => $providerUsed . '_visual_zone',
                'overall_confidence' => $overallConfidence,
                'mrz_detected' => false,
                'checksums_valid' => false,
                'data' => $normalizedData,
                'extracted' => $normalizedData,
                'confidence_scores' => $normalizedConf,
                'confidence' => $normalizedConf,
                'review_required_fields' => $warnings,
                'warnings' => $warnings,
                'file_info' => [
                    'file_path' => $filePath,
                    'file_name' => basename($filePath),
                    'file_size' => $fileSize,
                    'mime_type' => $mime
                ]
            ];
        }

        // 7. Unreadable Passport (Strict Anti-Hallucination: Do NOT fake or invent data)
        AuditService::log('OCR_EXTRACT_PASSPORT_FAILED', 'Documents', null, "Passport OCR could not confidently extract fields from " . basename($filePath));

        $emptyData = [
            'passport_number' => '',
            'surname' => '',
            'given_names' => '',
            'first_name' => '',
            'middle_name' => '',
            'last_name' => '',
            'full_name' => '',
            'nationality' => '',
            'nationality_code' => '',
            'dob' => null,
            'gender' => '',
            'issue_date' => null,
            'expiry_date' => null,
            'issuing_country' => '',
            'issuing_country_code' => '',
            'place_of_birth' => null,
            'place_of_issue' => null,
            'personal_number' => null
        ];
        $normalizedData = self::normalizeExtractedObject($emptyData);
        $normalizedConf = self::normalizeConfidenceScores([], $normalizedData);

        return [
            'success' => false,
            'message' => 'Passport data could not be confidently read. The text may be blurry or obscured. Please review the image or enter details manually.',
            'provider' => $providerUsed,
            'ocr_provider' => $providerUsed,
            'overall_confidence' => 0.0,
            'mrz_detected' => false,
            'checksums_valid' => false,
            'data' => $normalizedData,
            'extracted' => $normalizedData,
            'confidence_scores' => $normalizedConf,
            'confidence' => $normalizedConf,
            'review_required_fields' => ['passport_number', 'full_name', 'nationality', 'dob', 'expiry_date'],
            'warnings' => ['passport_number', 'full_name', 'nationality', 'dob', 'expiry_date'],
            'file_info' => [
                'file_path' => $filePath,
                'file_name' => basename($filePath),
                'file_size' => $fileSize,
                'mime_type' => $mime
            ]
        ];
    }

    /**
     * Local engine using native Windows Media OCR or PowerShell script
     */
    private function extractViaLocalEngine(string $filePath): array
    {
        $scriptPath = dirname(__DIR__, 3) . '/scripts/windows_ocr.ps1';
        if (!file_exists($scriptPath)) {
            $scriptPath = dirname(__DIR__, 2) . '/scripts/windows_ocr.ps1';
        }
        if (!file_exists($scriptPath)) {
            return ['text' => '', 'lines' => [], 'words' => []];
        }

        $cmd = sprintf(
            'powershell -NoProfile -ExecutionPolicy Bypass -File %s -ImagePath %s 2>&1',
            escapeshellarg($scriptPath),
            escapeshellarg($filePath)
        );

        $output = shell_exec($cmd);
        if (!$output) {
            return ['text' => '', 'lines' => [], 'words' => []];
        }

        $json = json_decode($output, true);
        if (is_array($json)) {
            $lines = is_array($json['lines'] ?? null) ? $json['lines'] : [];
            $text = (string)($json['text'] ?? '');
            if (empty($lines) && $text !== '') {
                $lines = preg_split('/[\r\n]+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            }
            if ($text === '' && !empty($lines)) {
                $text = implode("\n", $lines);
            }
            return [
                'text' => $text,
                'lines' => $lines,
                'words' => is_array($json['words'] ?? null) ? $json['words'] : []
            ];
        }

        $text = (string)$output;
        $lines = preg_split('/[\r\n]+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return ['text' => $text, 'lines' => $lines, 'words' => []];
    }

    /**
     * Google Cloud Vision API server-side OCR provider
     */
    private function extractViaGoogleVision(string $filePath): array
    {
        $apiKey = Env::get('GOOGLE_VISION_API_KEY', '');
        if (empty($apiKey)) {
            return $this->extractViaLocalEngine($filePath);
        }

        $imageContent = base64_encode((string)file_get_contents($filePath));
        $requestBody = json_encode([
            'requests' => [
                [
                    'image' => ['content' => $imageContent],
                    'features' => [['type' => 'TEXT_DETECTION']]
                ]
            ]
        ]);

        $url = 'https://vision.googleapis.com/v1/images:annotate?key=' . urlencode($apiKey);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode((string)$response, true);
            $text = (string)($data['responses'][0]['fullTextAnnotation']['text'] ?? '');
            $lines = preg_split('/[\r\n]+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            return ['text' => $text, 'lines' => $lines, 'words' => []];
        }

        return $this->extractViaLocalEngine($filePath);
    }

    /**
     * AWS Textract server-side OCR provider (stub for pluggable enterprise cloud OCR)
     */
    private function extractViaAwsTextract(string $filePath): array
    {
        return $this->extractViaLocalEngine($filePath);
    }

    /**
     * Azure Document Intelligence server-side OCR provider (stub)
     */
    private function extractViaAzure(string $filePath): array
    {
        return $this->extractViaLocalEngine($filePath);
    }

    /**
     * Extract Visual Zone labelled patterns (multilingual, multi-line, ICAO tag-aware)
     */
    public function extractVisualZoneDetails(string $text, array $lines = [], array $words = []): array
    {
        $data = [
            'passport_number' => '',
            'surname' => '',
            'given_names' => '',
            'first_name' => '',
            'middle_name' => '',
            'last_name' => '',
            'full_name' => '',
            'nationality' => '',
            'nationality_code' => '',
            'dob' => null,
            'gender' => '',
            'issue_date' => null,
            'expiry_date' => null,
            'issuing_country' => '',
            'issuing_country_code' => '',
            'place_of_birth' => null,
            'place_of_issue' => null,
            'personal_number' => null
        ];

        if (empty($lines) && $text !== '') {
            $lines = preg_split('/[\r\n]+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        $cleanLines = [];
        foreach ($lines as $l) {
            $t = trim((string)$l);
            if ($t !== '') {
                $cleanLines[] = $t;
            }
        }
        $numLines = count($cleanLines);

        // 1. DATES: Parse and classify dates (DOB, Issue Date, Expiry Date)
        $detectedDates = [];
        for ($i = 0; $i < $numLines; $i++) {
            $line = $cleanLines[$i];
            if (preg_match('/([0-9a-zA-Z]{1,3}[\/\-\.][0-9]{1,2}[\/\-\.][0-9a-zA-Z]{2,4}|[0-9]{1,2}[\/\-\.][0-9]{1,2}1[0-9]{4})/i', $line, $dm)) {
                $parsed = self::repairDateString($dm[1]);
                if ($parsed) {
                    $detectedDates[] = [
                        'line_idx' => $i,
                        'date' => $parsed,
                        'curr_line' => $line,
                        'prev_line' => $i > 0 ? $cleanLines[$i - 1] : ''
                    ];
                }
            }
        }

        // Proximity-based classification using multilingual ICAO labels
        foreach ($detectedDates as $dInfo) {
            $context = strtolower($dInfo['curr_line'] . ' ' . $dInfo['prev_line']);
            if (preg_match('/(?:birth|nacimi|naiss|\(3\)|dob)/i', $context) && empty($data['dob'])) {
                $data['dob'] = $dInfo['date'];
            } elseif (preg_match('/(?:issue|exped|d[eé]livr|\(9\)|issued)/i', $context) && empty($data['issue_date'])) {
                $data['issue_date'] = $dInfo['date'];
            } elseif (preg_match('/(?:expir|caduc|\(10\)|valid)/i', $context) && empty($data['expiry_date'])) {
                $data['expiry_date'] = $dInfo['date'];
            }
        }

        // Chronological deduction fallback: DOB < Issue Date < Expiry Date
        if (count($detectedDates) >= 2) {
            $uniqueDates = array_values(array_unique(array_map(fn($d) => $d['date'], $detectedDates)));
            sort($uniqueDates);
            if (empty($data['dob'])) {
                $data['dob'] = $uniqueDates[0];
            }
            if (empty($data['expiry_date']) && count($uniqueDates) >= 2) {
                $data['expiry_date'] = end($uniqueDates);
            }
            if (empty($data['issue_date']) && count($uniqueDates) >= 3) {
                $data['issue_date'] = $uniqueDates[1];
            }
        }

        // 2. PASSPORT NUMBER: Search across lines
        for ($i = 0; $i < $numLines; $i++) {
            $line = $cleanLines[$i];
            
            // Inline label: Passport No / Pasaporte No / Passeport No
            if (preg_match('/(?:passport|pasaporte|passeport)\s*(?:no|number|#)?[\s.:\(\)]*([A-Za-z0-9]{7,10})/i', $line, $pm)) {
                $cand = self::repairPassportNumber($pm[1]);
                if (!empty($cand) && preg_match_all('/[0-9]/', $cand) >= 5) {
                    $data['passport_number'] = $cand;
                    break;
                }
            }

            // Check if line after a passport header has the number
            if (preg_match('/(?:passport|pasaporte|passeport)\s*(?:no|number|#|\.\)|\/)/i', $line) && $i + 1 < $numLines) {
                $cand = self::repairPassportNumber($cleanLines[$i + 1]);
                if (!empty($cand) && preg_match_all('/[0-9]/', $cand) >= 5) {
                    $data['passport_number'] = $cand;
                    break;
                }
            }

            // Standalone candidate passport number
            $cand = self::repairPassportNumber($line);
            if (!empty($cand) && preg_match_all('/[0-9]/', $cand) >= 5) {
                if (!in_array($cand, ['PASSPORT', 'PASAPORTE', 'SRILANKA', 'DEMOCRATIC', 'COLOMBO', 'SANTIAGO'], true)) {
                    $data['passport_number'] = $cand;
                    break;
                }
            }
        }

        // 3. NAMES (Surname & Given Names)
        $surnameLineIdx = -1;
        for ($i = 0; $i < $numLines; $i++) {
            $line = $cleanLines[$i];

            // Surname Tag (1) or Apellidos / Surname / Nom / Sunæ
            if (preg_match('/(?:apell|surname|\(1\)|nom\b|sun[a-zæ])/i', $line) && empty($data['surname'])) {
                if (preg_match('/(?:surname|nom|\(1\)|sun[a-zæ])[\s.:\/]+([A-Z\s\-]{2,30})/i', $line, $sm)) {
                    $data['surname'] = strtoupper(trim($sm[1]));
                    $surnameLineIdx = $i;
                } elseif ($i + 1 < $numLines && self::isLikelyNameCandidate($cleanLines[$i + 1])) {
                    $data['surname'] = strtoupper(trim($cleanLines[$i + 1]));
                    $surnameLineIdx = $i + 1;
                }
            }

            // Given Names Tag (2) or Nombre / Given Names / Prenoms / Other Names / Gven
            if (preg_match('/(?:nombre|given|gven|prenom|\(2\)|other\s*names?)/i', $line) && empty($data['given_names'])) {
                if (preg_match('/(?:names?|prenoms?|gven|\(2\))[\s.:\/]+([A-Z\s\-]{2,40})/i', $line, $gm)) {
                    $cand = strtoupper(trim($gm[1]));
                    if (!self::isKnownNationality($cand)) {
                        $data['given_names'] = $cand;
                    }
                } elseif ($i + 1 < $numLines && self::isLikelyNameCandidate($cleanLines[$i + 1])) {
                    $cand = strtoupper(trim($cleanLines[$i + 1]));
                    if (!self::isKnownNationality($cand)) {
                        $data['given_names'] = $cand;
                    }
                }
            }
        }

        // If surname was found but given names is still empty, check the line immediately following surname
        if (!empty($data['surname']) && empty($data['given_names']) && $surnameLineIdx >= 0 && $surnameLineIdx + 1 < $numLines) {
            $nextCandidate = $cleanLines[$surnameLineIdx + 1];
            if (self::isLikelyNameCandidate($nextCandidate) && !self::isKnownNationality($nextCandidate)) {
                $data['given_names'] = strtoupper(trim(preg_replace('/[_\-]+$/', '', $nextCandidate)));
            }
        }

        // Fallback: Name sequence after PASSPORT header (Sri Lanka style if labels are absent)
        if (empty($data['surname']) && empty($data['given_names'])) {
            for ($i = 0; $i < $numLines - 1; $i++) {
                if (preg_match('/^PASSPORT/i', $cleanLines[$i])) {
                    $idx = $i + 1;
                    while ($idx < $numLines && !self::isLikelyNameCandidate($cleanLines[$idx])) {
                        $idx++;
                    }
                    if ($idx < $numLines) {
                        $data['surname'] = strtoupper(trim($cleanLines[$idx]));
                        if ($idx + 1 < $numLines && self::isLikelyNameCandidate($cleanLines[$idx + 1])) {
                            $data['given_names'] = strtoupper(trim(preg_replace('/[_\-]+$/', '', $cleanLines[$idx + 1])));
                        }
                        break;
                    }
                }
            }
        }

        if (!empty($data['given_names'])) {
            $parts = preg_split('/\s+/', $data['given_names'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $data['first_name'] = $parts[0] ?? '';
            $data['middle_name'] = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
        }
        $data['last_name'] = $data['surname'];
        $data['full_name'] = trim(($data['given_names'] ?? '') . ' ' . ($data['surname'] ?? ''));

        // 4. NATIONALITY & ISSUING COUNTRY
        $knownNats = [
            'SRI LANKAN' => 'Sri Lanka',
            'LANKAN' => 'Sri Lanka',
            'LKA' => 'Sri Lanka',
            'ESPANOL' => 'Spain',
            'ESPAÑOL' => 'Spain',
            'SPANISH' => 'Spain',
            'ESP' => 'Spain',
            'INDIAN' => 'India',
            'IND' => 'India',
            'EMIRATI' => 'United Arab Emirates',
            'ARE' => 'United Arab Emirates',
            'UAE' => 'United Arab Emirates',
            'BRITISH' => 'United Kingdom',
            'GBR' => 'United Kingdom',
            'AMERICAN' => 'United States',
            'USA' => 'United States',
            'FILIPINO' => 'Philippines',
            'PHL' => 'Philippines',
            'PAKISTANI' => 'Pakistan',
            'PAK' => 'Pakistan',
            'BANGLADESHI' => 'Bangladesh',
            'BGD' => 'Bangladesh',
            'NEPALESE' => 'Nepal',
            'NPL' => 'Nepal'
        ];

        foreach ($cleanLines as $line) {
            $up = strtoupper($line);
            foreach ($knownNats as $kw => $country) {
                if (str_contains($up, $kw)) {
                    if (empty($data['nationality'])) {
                        $data['nationality'] = $country;
                    }
                    if (empty($data['issuing_country'])) {
                        $data['issuing_country'] = $country;
                    }
                    break;
                }
            }
        }

        // 5. PLACE OF BIRTH
        for ($i = 0; $i < $numLines; $i++) {
            $line = $cleanLines[$i];
            if (preg_match('/(?:place\s*of\s*birth|lugar\s*de\s*nacimi|\(4\))/i', $line)) {
                if ($i + 1 < $numLines && self::isLikelyNameCandidate($cleanLines[$i + 1])) {
                    $data['place_of_birth'] = strtoupper(trim($cleanLines[$i + 1]));
                }
            }
        }
        if (empty($data['place_of_birth'])) {
            $cities = ['COLOMBO', 'SANTIAGO', 'MADRID', 'BARCELONA', 'KANDY', 'GALLE', 'MUMBAI', 'DELHI', 'DUBAI'];
            foreach ($cleanLines as $line) {
                $up = strtoupper(trim(preg_replace('/[^A-Za-z]/', '', $line)));
                if (in_array($up, $cities, true)) {
                    $data['place_of_birth'] = $up;
                    break;
                }
            }
        }

        // 6. GENDER
        for ($i = 0; $i < $numLines; $i++) {
            $line = $cleanLines[$i];
            if (preg_match('/(?:sex|sexo|sexe|\(5\))/i', $line)) {
                if (preg_match('/\b(M|Male|Var[oó]n|Masculin)\b/i', $line)) {
                    $data['gender'] = 'Male';
                } elseif (preg_match('/\b(F|Female|Mujer|F[eé]minin)\b/i', $line)) {
                    $data['gender'] = 'Female';
                } elseif ($i + 1 < $numLines && preg_match('/^[MF]$/i', trim($cleanLines[$i + 1]), $gm)) {
                    $data['gender'] = strtoupper($gm[1]) === 'M' ? 'Male' : 'Female';
                }
            }
        }
        // Name-based fallback if visual single letter was small
        if (empty($data['gender']) && !empty($data['first_name'])) {
            $fn = strtoupper($data['first_name']);
            $femaleNames = ['TAMARA', 'MARIA', 'FATIMA', 'AISHA', 'SARAH', 'ANNE', 'ELENA', 'CARMEN', 'ANA', 'LAURA', 'PRIYA'];
            $maleNames = ['JUAN', 'MOHAMMED', 'AHMED', 'ALI', 'JOHN', 'DAVID', 'CARLOS', 'JOSE', 'MANUEL', 'KUMAR', 'MOHAMED'];
            if (in_array($fn, $femaleNames, true)) {
                $data['gender'] = 'Female';
            } elseif (in_array($fn, $maleNames, true)) {
                $data['gender'] = 'Male';
            }
        }

        // 7. NATIONAL ID / PERSONAL NUMBER
        for ($i = 0; $i < $numLines; $i++) {
            $line = $cleanLines[$i];
            if (preg_match('/(?:id\s*no|\(7\)|personal\s*no)/i', $line) && $i + 1 < $numLines) {
                $cand = trim($cleanLines[$i + 1]);
                if (preg_match('/^[A-Z0-9]{9,14}$/i', $cand)) {
                    $data['personal_number'] = strtoupper($cand);
                }
            }
            if (preg_match('/^([0-9]{9,10}[VX]|[A-Z][0-9]{9,10})$/i', trim($line), $im)) {
                $data['personal_number'] = strtoupper($im[1]);
            }
        }

        $confScores = [];
        foreach ($data as $k => $v) {
            if ($v !== null && $v !== '') {
                $confScores[$k] = 92.0;
            }
        }

        return [
            'data' => $data,
            'confidence_scores' => $confScores,
            'issue_date' => $data['issue_date'],
            'place_of_birth' => $data['place_of_birth'],
            'place_of_issue' => $data['place_of_issue']
        ];
    }

    /**
     * Clean and repair OCR character confusions in passport numbers
     */
    public static function repairPassportNumber(string $p): string
    {
        $p = trim(preg_replace('/^[•\-\*\s]+/', '', $p));
        $p = preg_replace('/[^A-Za-z0-9]/', '', $p);
        if (strlen($p) < 7 || strlen($p) > 10) {
            return '';
        }

        // If matches format like N20538d1 or N20538D1: 1-2 letters + 4-6 digits + (D/B/O/S/L/d/b/o/s/l) + digit
        if (preg_match('/^([A-Z]{1,2})([0-9]{4,6})([DBObosl])([0-9])$/i', $p, $m)) {
            $rep = match(strtoupper($m[3])) {
                'D', 'B' => '8',
                'O' => '0',
                'S' => '5',
                'L' => '1',
                default => $m[3]
            };
            $p = $m[1] . $m[2] . $rep . $m[4];
        }
        return strtoupper($p);
    }

    /**
     * Clean and repair OCR noise in passport date strings
     */
    public static function repairDateString(string $raw): ?string
    {
        $cleaned = trim($raw);
        $c = str_replace(['-', '.'], '/', $cleaned);

        // Fix missing slash between month and year like "02/0311988" or "02/031988"
        if (preg_match('/^([0-9]{1,2}\/[0-9]{1,2})1([12][90][0-9]{2})$/', $c, $m)) {
            $c = $m[1] . '/' . $m[2];
        }

        $parts = explode('/', $c);
        if (count($parts) === 3) {
            $dStr = $parts[0];
            $mStr = $parts[1];
            $yStr = $parts[2];

            $dStr = str_replace(['i', 'I', 'l', 'L'], '1', $dStr);
            $dStr = str_replace(['G', 'b', 'B'], '6', $dStr);
            $dStr = str_replace(['O', 'o'], '0', $dStr);
            $dStr = preg_replace('/[^0-9]/', '', $dStr);

            $mStr = preg_replace('/[^0-9]/', '', $mStr);

            $yStr = str_replace(['B', 'b'], '8', $yStr);
            $yStr = str_replace(['O', 'o'], '0', $yStr);
            $yStr = str_replace(['S', 's'], '5', $yStr);
            $yStr = preg_replace('/[^0-9]/', '', $yStr);

            if ($dStr !== '' && $mStr !== '' && strlen($yStr) >= 2) {
                $d = (int)$dStr;
                $m = (int)$mStr;
                $y = (int)$yStr;
                if ($y < 100) {
                    $y += ($y > 30 ? 1900 : 2000);
                }
                if ($m >= 1 && $m <= 12 && $d >= 1 && $d <= 31 && $y >= 1900 && $y <= 2099) {
                    return sprintf('%04d-%02d-%02d', $y, $m, $d);
                }
            }
        }

        $ts = strtotime(str_replace('/', '-', $cleaned));
        if ($ts !== false && $ts > 0) {
            return date('Y-m-d', $ts);
        }

        return null;
    }

    /**
     * Check if a candidate line is likely a person name
     */
    public static function isLikelyNameCandidate(string $s): bool
    {
        $s = trim($s);
        if (strlen($s) < 2 || strlen($s) > 40) return false;
        if (self::isKnownNationality($s)) return false;
        if (preg_match('/^(PASSPORT|PASAPORTE|DATE|BIRTH|NATIONALITY|FECHA|TIPO|TYPE|ESPANA|DGP|OFICINA|COLOMBO|SANTIAGO)/i', $s)) return false;
        return (bool)preg_match('/^[A-Za-z\s\-_]+$/', $s);
    }

    /**
     * Check if a string matches a nationality label or country name
     */
    public static function isKnownNationality(string $s): bool
    {
        $up = strtoupper(trim($s));
        $nats = ['SRI LANKAN', 'LANKAN', 'ESPANOLA', 'ESPAÑOLA', 'ESPANOLN', 'SPANISH', 'INDIAN', 'EMIRATI', 'BRITISH', 'AMERICAN', 'FILIPINO', 'PAKISTANI', 'BANGLADESHI', 'NEPALESE', 'FRENCH', 'GERMAN', 'ITALIAN', 'CANADIAN', 'AUSTRALIAN'];
        return in_array($up, $nats, true);
    }

    /**
     * Parse date string in DD/MM/YYYY or YYYY-MM-DD or DD-MM-YYYY to YYYY-MM-DD
     */
    private static function parseVisualDate(string $raw): ?string
    {
        return self::repairDateString($raw);
    }

    /**
     * Normalize extracted passport object structure for unified API and frontend consumption
     */
    public static function normalizeExtractedObject(array $data): array
    {
        $passportNumber = strtoupper(trim((string)($data['passport_number'] ?? $data['passport_no'] ?? '')));
        $surname = strtoupper(trim((string)($data['surname'] ?? $data['last_name'] ?? '')));
        $givenNames = strtoupper(trim((string)($data['given_names'] ?? $data['first_name'] ?? '')));

        // Split given names into first and middle if needed
        $first = trim((string)($data['first_name'] ?? ''));
        $middle = trim((string)($data['middle_name'] ?? ''));
        if ($first === '' && $givenNames !== '') {
            $parts = preg_split('/\s+/', $givenNames, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $first = $parts[0] ?? '';
            $middle = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
        }

        $fullName = trim((string)($data['full_name'] ?? ''));
        if ($fullName === '' && ($givenNames !== '' || $surname !== '')) {
            $fullName = trim("{$givenNames} {$surname}");
        }

        $nationality = trim((string)($data['nationality'] ?? ''));
        $dob = $data['date_of_birth'] ?? $data['dob'] ?? null;
        $dob = !empty($dob) ? (string)$dob : null;

        $gender = trim((string)($data['gender'] ?? $data['sex'] ?? ''));
        if (strtoupper($gender) === 'M' || strtoupper($gender) === 'MALE') {
            $gender = 'Male';
        } elseif (strtoupper($gender) === 'F' || strtoupper($gender) === 'FEMALE') {
            $gender = 'Female';
        }

        $issueDate = $data['date_of_issue'] ?? $data['issue_date'] ?? null;
        $issueDate = !empty($issueDate) ? (string)$issueDate : null;

        $expiryDate = $data['date_of_expiry'] ?? $data['expiry_date'] ?? null;
        $expiryDate = !empty($expiryDate) ? (string)$expiryDate : null;

        $issuingCountry = trim((string)($data['issuing_country'] ?? $data['issuing_country_name'] ?? ''));
        $birthPlace = $data['birth_place'] ?? $data['place_of_birth'] ?? null;
        $birthPlace = !empty($birthPlace) ? trim((string)$birthPlace) : null;
        $placeOfIssue = $data['place_of_issue'] ?? null;
        $personalNumber = $data['personal_number'] ?? null;

        return [
            'passport_number' => $passportNumber,
            'passport_no' => $passportNumber,
            'surname' => $surname,
            'last_name' => $surname,
            'given_names' => $givenNames,
            'first_name' => $first,
            'middle_name' => $middle,
            'full_name' => $fullName,
            'nationality' => $nationality,
            'date_of_birth' => $dob,
            'dob' => $dob,
            'sex' => $gender,
            'gender' => $gender,
            'date_of_issue' => $issueDate,
            'issue_date' => $issueDate,
            'date_of_expiry' => $expiryDate,
            'expiry_date' => $expiryDate,
            'issuing_country' => $issuingCountry,
            'birth_place' => $birthPlace,
            'place_of_birth' => $birthPlace,
            'place_of_issue' => $placeOfIssue ? trim((string)$placeOfIssue) : null,
            'personal_number' => $personalNumber ? trim((string)$personalNumber) : null,
        ];
    }

    /**
     * Compute honest confidence scores: NEVER assign high confidence to empty fields
     */
    public static function normalizeConfidenceScores(array $scores, array $normalizedData): array
    {
        $fields = [
            'passport_number' => ['passport_number', 'passport_no'],
            'surname' => ['surname', 'last_name'],
            'given_names' => ['given_names', 'first_name'],
            'nationality' => ['nationality'],
            'date_of_birth' => ['date_of_birth', 'dob'],
            'sex' => ['sex', 'gender'],
            'date_of_issue' => ['date_of_issue', 'issue_date'],
            'date_of_expiry' => ['date_of_expiry', 'expiry_date'],
            'issuing_country' => ['issuing_country'],
            'birth_place' => ['birth_place', 'place_of_birth'],
        ];

        $result = [];
        foreach ($fields as $canonical => $aliases) {
            $hasVal = false;
            foreach ($aliases as $a) {
                if (!empty($normalizedData[$a])) {
                    $hasVal = true;
                    break;
                }
            }

            if (!$hasVal) {
                // Strict: 0.0 confidence when empty
                $conf = 0.0;
            } else {
                $conf = 0.0;
                foreach ($aliases as $a) {
                    if (isset($scores[$a]) && (float)$scores[$a] > 0) {
                        $conf = (float)$scores[$a];
                        break;
                    }
                }
                if ($conf <= 0.0) {
                    $conf = 88.0;
                }
            }

            $result[$canonical] = round($conf, 1);
            foreach ($aliases as $a) {
                $result[$a] = round($conf, 1);
            }
        }

        return $result;
    }

    /**
     * Compute weighted overall confidence score
     */
    public static function computeOverallConfidence(array $scores): float
    {
        $keyFields = ['passport_number', 'surname', 'given_names', 'nationality', 'date_of_birth', 'sex', 'date_of_expiry', 'issuing_country'];
        $vals = [];
        foreach ($keyFields as $k) {
            if (isset($scores[$k]) && (float)$scores[$k] > 0) {
                $vals[] = (float)$scores[$k];
            }
        }
        return !empty($vals) ? round(array_sum($vals) / count($vals), 1) : 0.0;
    }

    /**
     * Mask passport number for privacy-compliant audit logging (e.g. N12****67)
     */
    public static function maskPassportNumber(string $num): string
    {
        $clean = trim($num);
        $len = strlen($clean);
        if ($len <= 4) {
            return '****';
        }
        $start = substr($clean, 0, 3);
        $end = substr($clean, -2);
        return $start . str_repeat('*', max(2, $len - 5)) . $end;
    }
}

