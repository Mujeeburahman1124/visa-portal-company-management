<?php
declare(strict_types=1);

namespace App\Services\Ocr;

use App\Config\Database;
use PDO;

class MrzParser
{
    /**
     * Parse raw text containing MRZ lines and extract normalized passport fields.
     *
     * @param string $rawText
     * @return array|null
     */
    public static function parse(string $rawText): ?array
    {
        $lines = self::extractCandidateMrzLines($rawText);
        if (empty($lines)) {
            return null;
        }

        // TD3 Passport: 2 lines of 44 characters
        if (count($lines) >= 2) {
            for ($i = 0; $i < count($lines) - 1; $i++) {
                $l1 = $lines[$i];
                $l2 = $lines[$i + 1];
                if (strlen($l1) === 44 && strlen($l2) === 44 && str_starts_with($l1, 'P')) {
                    return self::parseTd3($l1, $l2);
                }
            }
        }

        // Try loose matching if lines have slight OCR character padding or noise
        foreach ($lines as $i => $l1) {
            if (isset($lines[$i + 1]) && str_starts_with($l1, 'P') && strlen($l1) >= 40 && strlen($lines[$i + 1]) >= 40) {
                $pad1 = str_pad(substr($l1, 0, 44), 44, '<');
                $pad2 = str_pad(substr($lines[$i + 1], 0, 44), 44, '<');
                return self::parseTd3($pad1, $pad2);
            }
        }

        return null;
    }

    /**
     * Parse ICAO 9303 TD3 (Passport: 2 lines x 44 chars)
     */
    public static function parseTd3(string $line1, string $line2): array
    {
        $docType = substr($line1, 0, 2);
        $issuingState = str_replace('<', '', substr($line1, 2, 3));
        $nameSection = substr($line1, 5, 39);

        // Parse Name: SURNAME<<GIVEN<NAMES
        $nameParts = explode('<<', $nameSection, 2);
        $surnameRaw = trim($nameParts[0] ?? '');
        $givenNamesRaw = trim($nameParts[1] ?? '');

        $surname = trim(str_replace('<', ' ', $surnameRaw));
        $givenNames = trim(str_replace('<', ' ', $givenNamesRaw));
        $givenTokens = preg_split('/\s+/', $givenNames, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $firstName = $givenTokens[0] ?? '';
        $middleName = count($givenTokens) > 1 ? implode(' ', array_slice($givenTokens, 1)) : '';
        $lastName = $surname;
        $fullName = trim("{$givenNames} {$surname}");

        // Line 2 parsing
        $passportNumRaw = substr($line2, 0, 9);
        $passportNum = trim(str_replace('<', '', $passportNumRaw));
        $passportCheck = substr($line2, 9, 1);
        $passValid = self::verifyChecksum($passportNumRaw, $passportCheck);

        $nationality = str_replace('<', '', substr($line2, 10, 3));
        
        $dobRaw = substr($line2, 13, 6);
        $dobCheck = substr($line2, 19, 1);
        $dobValid = self::verifyChecksum($dobRaw, $dobCheck);
        $dobNormalized = self::normalizeDate($dobRaw, true);

        $sexChar = substr($line2, 20, 1);
        $gender = match ($sexChar) {
            'M' => 'Male',
            'F' => 'Female',
            default => 'Other'
        };

        $expiryRaw = substr($line2, 21, 6);
        $expiryCheck = substr($line2, 27, 1);
        $expiryValid = self::verifyChecksum($expiryRaw, $expiryCheck);
        $expiryNormalized = self::normalizeDate($expiryRaw, false);

        $personalNumRaw = substr($line2, 28, 14);
        $personalNum = trim(str_replace('<', '', $personalNumRaw));
        $personalNumCheck = substr($line2, 42, 1);
        $personalNumValid = ($personalNum !== '') ? self::verifyChecksum($personalNumRaw, $personalNumCheck) : true;

        $compositeCheck = substr($line2, 43, 1);
        $compositeData = substr($line2, 0, 10) . substr($line2, 13, 7) . substr($line2, 21, 22);
        $compositeValid = self::verifyChecksum($compositeData, $compositeCheck);

        // Map Country Codes
        $issuingCountryName = self::mapCountryCode($issuingState);
        $nationalityName = self::mapCountryCode($nationality);

        // Compute Confidence Scores
        $confidenceScores = [
            'passport_number' => $passValid ? 99.0 : 68.0,
            'full_name' => (!empty($surname) && !empty($givenNames)) ? 98.0 : 75.0,
            'surname' => !empty($surname) ? 98.0 : 60.0,
            'given_names' => !empty($givenNames) ? 98.0 : 60.0,
            'nationality' => !empty($nationalityName) ? 100.0 : 70.0,
            'dob' => ($dobValid && $dobNormalized !== null) ? 98.0 : 65.0,
            'gender' => in_array($gender, ['Male', 'Female'], true) ? 99.0 : 70.0,
            'expiry_date' => ($expiryValid && $expiryNormalized !== null) ? 99.0 : 65.0,
            'issuing_country' => !empty($issuingCountryName) ? 100.0 : 70.0,
        ];

        $overallConfidence = round(array_sum($confidenceScores) / count($confidenceScores), 1);
        $reviewRequired = [];
        foreach ($confidenceScores as $field => $score) {
            if ($score < 80.0) {
                $reviewRequired[] = $field;
            }
        }

        return [
            'format' => 'ICAO_TD3',
            'mrz_lines' => [$line1, $line2],
            'checksums' => [
                'passport_number' => $passValid,
                'dob' => $dobValid,
                'expiry' => $expiryValid,
                'personal_number' => $personalNumValid,
                'composite' => $compositeValid,
                'all_valid' => ($passValid && $dobValid && $expiryValid)
            ],
            'confidence_scores' => $confidenceScores,
            'overall_confidence' => $overallConfidence,
            'review_required_fields' => $reviewRequired,
            'data' => [
                'passport_number' => $passportNum,
                'surname' => $surname,
                'given_names' => $givenNames,
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'full_name' => $fullName,
                'nationality' => $nationalityName,
                'nationality_code' => $nationality,
                'dob' => $dobNormalized,
                'gender' => $gender,
                'issue_date' => null, // Visual zone only, not present in standard MRZ
                'expiry_date' => $expiryNormalized,
                'issuing_country' => $issuingCountryName,
                'issuing_country_code' => $issuingState,
                'personal_number' => $personalNum ?: null
            ]
        ];
    }

    /**
     * ICAO Document 9303 Check digit validation algorithm
     * Weights: 7, 3, 1 repeating. Modulo 10.
     */
    public static function verifyChecksum(string $data, string $expectedCheckDigit): bool
    {
        if ($expectedCheckDigit === '' || $expectedCheckDigit === '<') {
            return true;
        }

        $calc = self::calculateChecksum($data);
        return ((string)$calc === $expectedCheckDigit);
    }

    public static function calculateChecksum(string $data): int
    {
        $weights = [7, 3, 1];
        $sum = 0;
        $len = strlen($data);

        for ($i = 0; $i < $len; $i++) {
            $char = strtoupper($data[$i]);
            $val = 0;
            if ($char >= '0' && $char <= '9') {
                $val = ord($char) - ord('0');
            } elseif ($char >= 'A' && $char <= 'Z') {
                $val = ord($char) - ord('A') + 10;
            } elseif ($char === '<') {
                $val = 0;
            }
            $sum += $val * $weights[$i % 3];
        }

        return $sum % 10;
    }

    /**
     * Normalize MRZ YYMMDD date to YYYY-MM-DD
     */
    public static function normalizeDate(string $yymmdd, bool $isDob = false): ?string
    {
        if (strlen($yymmdd) !== 6 || !ctype_digit($yymmdd)) {
            return null;
        }

        $yy = (int)substr($yymmdd, 0, 2);
        $mm = (int)substr($yymmdd, 2, 2);
        $dd = (int)substr($yymmdd, 4, 2);

        if ($mm < 1 || $mm > 12 || $dd < 1 || $dd > 31) {
            return null;
        }

        $currentYear = (int)date('Y');
        $currentYY = $currentYear % 100;

        if ($isDob) {
            // Birth Date: If YY <= currentYY (e.g. <= 26), 2000-2026. If > 26, 1927-1999.
            $century = ($yy <= $currentYY) ? 2000 : 1900;
        } else {
            // Expiry Date: Passports typically have 5 to 10 years validity.
            // If YY >= 20, 20YY. If YY < 20, 20YY (or 19YY if very old).
            $century = ($yy >= 20 || $yy <= ($currentYY + 20)) ? 2000 : 1900;
        }

        $fullYear = $century + $yy;
        return sprintf('%04d-%02d-%02d', $fullYear, $mm, $dd);
    }

    /**
     * Map 3-letter ICAO country code to country name
     */
    public static function mapCountryCode(string $code): string
    {
        $code = strtoupper(trim($code));
        if (empty($code)) {
            return '';
        }

        // Query database countries table if available
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT name FROM countries WHERE UPPER(iso3_code) = ? OR UPPER(iso_code) = ? LIMIT 1");
            $stmt->execute([$code, $code]);
            $dbName = $stmt->fetchColumn();
            if ($dbName) {
                return (string)$dbName;
            }
        } catch (\Throwable $ignored) {}

        // Fallback standard ICAO dictionary
        $dictionary = [
            'LKA' => 'Sri Lanka',
            'ARE' => 'United Arab Emirates',
            'IND' => 'India',
            'PAK' => 'Pakistan',
            'BGD' => 'Bangladesh',
            'NPL' => 'Nepal',
            'PHL' => 'Philippines',
            'EGY' => 'Egypt',
            'JOR' => 'Jordan',
            'SAU' => 'Saudi Arabia',
            'OMN' => 'Oman',
            'QAT' => 'Qatar',
            'KWT' => 'Kuwait',
            'BHR' => 'Bahrain',
            'USA' => 'United States',
            'GBR' => 'United Kingdom',
            'CAN' => 'Canada',
            'AUS' => 'Australia',
            'DEU' => 'Germany',
            'FRA' => 'France',
            'ITA' => 'Italy',
            'ESP' => 'Spain',
            'NLD' => 'Netherlands',
            'TUR' => 'Turkey',
            'RUS' => 'Russian Federation',
            'CHN' => 'China',
            'IDN' => 'Indonesia',
            'MYS' => 'Malaysia',
            'SGP' => 'Singapore',
            'THA' => 'Thailand',
            'VNM' => 'Vietnam',
            'ZAF' => 'South Africa',
            'KEN' => 'Kenya',
            'NGA' => 'Nigeria',
            'GHA' => 'Ghana',
            'ETH' => 'Ethiopia',
            'UGA' => 'Uganda',
            'LBN' => 'Lebanon',
            'SYR' => 'Syria',
            'IRQ' => 'Iraq',
            'IRN' => 'Iran',
            'AFG' => 'Afghanistan',
            'YEM' => 'Yemen',
            'SDN' => 'Sudan',
            'MAR' => 'Morocco',
            'DZA' => 'Algeria',
            'TUN' => 'Tunisia',
            'UZB' => 'Uzbekistan',
            'KAZ' => 'Kazakhstan',
            'TJK' => 'Tajikistan',
            'KGZ' => 'Kyrgyzstan',
            'AZE' => 'Azerbaijan',
            'GEO' => 'Georgia',
            'ARM' => 'Armenia'
        ];

        return $dictionary[$code] ?? $code;
    }

    /**
     * Clean and isolate candidate MRZ lines from raw OCR output
     */
    private static function extractCandidateMrzLines(string $text): array
    {
        $rawLines = preg_split('/[\r\n]+/', $text);
        $candidates = [];

        foreach ($rawLines as $line) {
            // Normalize spaces and common OCR replacement artifacts in MRZ
            $clean = trim($line);
            $clean = preg_replace('/\s+/', '', $clean);
            $clean = str_replace(['«', '‹', '(', ')', '[', ']', '{', '}'], '<', $clean);
            $clean = strtoupper($clean);

            // MRZ lines contain primarily A-Z, 0-9, and '<'
            if (strlen($clean) >= 28 && preg_match('/^[A-Z0-9<]+$/', $clean)) {
                $candidates[] = $clean;
            }
        }

        return $candidates;
    }
}
