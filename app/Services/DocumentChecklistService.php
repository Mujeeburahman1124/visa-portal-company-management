<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\App;
use App\Config\Database;
use App\Services\DocumentExpiryService;
use PDO;

class DocumentChecklistService
{
    /**
     * Check if a document file path actually exists and is readable on disk
     */
    public static function hasValidFile(?string $filePath): bool
    {
        if (empty($filePath)) {
            return false;
        }

        $p1 = App::uploadPath($filePath);
        if (file_exists($p1) && is_file($p1) && filesize($p1) > 0) {
            return true;
        }

        $p2 = App::publicPath('uploads/documents/' . basename($filePath));
        if (file_exists($p2) && is_file($p2) && filesize($p2) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get complete smart checklist for an application with strict deduplication
     * and accurate mathematical completion calculation (Single Source of Truth)
     */
    public static function getChecklist(int $applicationId): array
    {
        $pdo = Database::getConnection();

        // 1. Get Application details (Service ID and Customer ID)
        $stmt = $pdo->prepare("SELECT id, visa_service_id, customer_id FROM applications WHERE id = ?");
        $stmt->execute([$applicationId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            return [
                'total_required' => 0,
                'total_uploaded' => 0,
                'total_verified' => 0,
                'total_pending'  => 0,
                'total_missing'  => 0,
                'total_rejected' => 0,
                'total_expired'  => 0,
                'total_optional' => 0,
                'percentage'     => 0,
                'items'          => [],
                'mandatory_items'=> [],
                'optional_items' => [],
            ];
        }

        $serviceId = (int)$app['visa_service_id'];
        $customerId = (int)$app['customer_id'];

        // 2. Fetch service requirements
        $stmt = $pdo->prepare("SELECT vr.id as requirement_id, vr.service_id, vr.is_mandatory,
                    COALESCE(vr.is_critical, 0) as is_critical,
                    vr.condition_notes, vr.instructions,
                    dt.id as document_type_id, dt.name as document_name, dt.code as document_code,
                    dt.category, dt.requires_expiry
             FROM visa_requirements vr
             JOIN document_types dt ON vr.document_type_id = dt.id
             WHERE vr.service_id = ?
             ORDER BY vr.is_mandatory DESC, dt.id ASC, vr.id ASC");
        $stmt->execute([$serviceId]);
        $rawRequirements = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 3. Deduplicate requirements by unique document_type_id (PART 8, 9, 10)
        $uniqueRequirements = [];
        foreach ($rawRequirements as $req) {
            $typeId = (int)$req['document_type_id'];
            if (!isset($uniqueRequirements[$typeId])) {
                $uniqueRequirements[$typeId] = $req;
                $uniqueRequirements[$typeId]['condition_notes_list'] = [];
                $uniqueRequirements[$typeId]['instructions_list'] = [];
                if (!empty($req['condition_notes'])) {
                    $uniqueRequirements[$typeId]['condition_notes_list'][] = trim((string)$req['condition_notes']);
                }
                if (!empty($req['instructions'])) {
                    $uniqueRequirements[$typeId]['instructions_list'][] = trim((string)$req['instructions']);
                }
            } else {
                // If any duplicate row marks it mandatory, preserve mandatory = 1
                if (!empty($req['is_mandatory'])) {
                    $uniqueRequirements[$typeId]['is_mandatory'] = 1;
                }
                if (!empty($req['is_critical'])) {
                    $uniqueRequirements[$typeId]['is_critical'] = 1;
                }
                // Merge condition notes
                if (!empty($req['condition_notes'])) {
                    $cn = trim((string)$req['condition_notes']);
                    if (!in_array($cn, $uniqueRequirements[$typeId]['condition_notes_list'], true)) {
                        $uniqueRequirements[$typeId]['condition_notes_list'][] = $cn;
                    }
                }
                // Merge instructions
                if (!empty($req['instructions'])) {
                    $inst = trim((string)$req['instructions']);
                    if (!in_array($inst, $uniqueRequirements[$typeId]['instructions_list'], true)) {
                        $uniqueRequirements[$typeId]['instructions_list'][] = $inst;
                    }
                }
            }
        }

        // Finalize merged strings for each unique requirement
        foreach ($uniqueRequirements as &$uReq) {
            $uReq['condition_notes'] = !empty($uReq['condition_notes_list']) 
                ? implode(' • ', $uReq['condition_notes_list']) 
                : ($uReq['condition_notes'] ?? null);
            $uReq['instructions'] = !empty($uReq['instructions_list']) 
                ? implode(' • ', $uReq['instructions_list']) 
                : ($uReq['instructions'] ?? null);
            unset($uReq['condition_notes_list'], $uReq['instructions_list']);
        }
        unset($uReq);

        // 4. Fetch all uploaded documents for this application
        $stmt = $pdo->prepare("SELECT d.*, u.name as verified_by_name,
                    dt.name as doc_type_name, dt.code as doc_type_code, dt.category as doc_type_category, dt.requires_expiry
             FROM documents d 
             JOIN document_types dt ON d.document_type_id = dt.id
             LEFT JOIN users u ON d.verified_by = u.id 
             WHERE d.application_id = ?
             ORDER BY d.id DESC");
        $stmt->execute([$applicationId]);
        $uploadedDocs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 5. Group and select the single BEST document record per document_type_id (PART 11)
        // Priority: VERIFIED (4) > UNDER_REVIEW (3) > UPLOADED (2) > REJECTED (1) > latest version DESC > latest id DESC
        $docsByType = [];
        foreach ($uploadedDocs as $d) {
            $tId = (int)$d['document_type_id'];
            if (!isset($docsByType[$tId])) {
                $docsByType[$tId] = [];
            }
            $docsByType[$tId][] = $d;
        }

        $bestDocByType = [];
        foreach ($docsByType as $tId => $typeDocs) {
            usort($typeDocs, function ($a, $b) {
                $statusRank = function ($status) {
                    if ($status === 'VERIFIED') return 4;
                    if ($status === 'UNDER_REVIEW') return 3;
                    if ($status === 'UPLOADED') return 2;
                    if ($status === 'REJECTED') return 1;
                    return 0;
                };
                $rA = $statusRank($a['status'] ?? '');
                $rB = $statusRank($b['status'] ?? '');
                if ($rA !== $rB) return $rB <=> $rA;
                $vA = (int)($a['version'] ?? 1);
                $vB = (int)($b['version'] ?? 1);
                if ($vA !== $vB) return $vB <=> $vA;
                return ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0));
            });
            $bestDocByType[$tId] = $typeDocs[0];
        }

        // 6. Build Checklist Items & Evaluate Metrics (PART 5, 6, 12, 13)
        $items = [];
        $mandatoryItems = [];
        $optionalItems = [];

        $totalRequired = 0; // Total UNIQUE mandatory document types
        $totalUploaded = 0; // Mandatory items where valid file is uploaded
        $totalVerified = 0; // Mandatory items verified & not expired
        $totalPending  = 0; // Mandatory items uploaded but awaiting verification
        $totalMissing  = 0; // Mandatory items missing upload
        $totalRejected = 0; // Mandatory items rejected
        $totalExpired  = 0; // Mandatory items where document has expired

        $today = date('Y-m-d');

        foreach ($uniqueRequirements as $req) {
            $typeId = (int)$req['document_type_id'];
            $isMandatory = (bool)$req['is_mandatory'];
            $isCritical = !empty($req['is_critical']);

            $doc = $bestDocByType[$typeId] ?? null;
            $hasFile = $doc ? self::hasValidFile($doc['file_path']) : false;

            // Determine effective status
            $effectiveStatus = 'MISSING';
            $expiryInfo = ['status' => 'NONE', 'days' => null];

            if ($doc && $hasFile) {
                $expiryInfo = DocumentExpiryService::checkExpiry($doc['expiry_date'] ?? null);
                if (!empty($doc['expiry_date']) && $doc['expiry_date'] < $today) {
                    $effectiveStatus = 'EXPIRED';
                } elseif ($doc['status'] === 'VERIFIED') {
                    $effectiveStatus = 'VERIFIED';
                } elseif ($doc['status'] === 'REJECTED') {
                    $effectiveStatus = 'REJECTED';
                } else {
                    $effectiveStatus = 'UNDER_REVIEW'; // PENDING verification
                }
            } else {
                $effectiveStatus = 'MISSING';
            }

            // Statistics accumulation strictly based on MANDATORY vs OPTIONAL
            if ($isMandatory) {
                $totalRequired++;

                if ($effectiveStatus === 'VERIFIED') {
                    $totalVerified++;
                    $totalUploaded++;
                } elseif ($effectiveStatus === 'UNDER_REVIEW') {
                    $totalPending++;
                    $totalUploaded++;
                } elseif ($effectiveStatus === 'EXPIRED') {
                    $totalExpired++;
                    $totalUploaded++;
                } elseif ($effectiveStatus === 'REJECTED') {
                    $totalRejected++;
                    $totalUploaded++;
                } else {
                    $totalMissing++;
                }
            }

            $item = [
                'requirement_id'        => $req['requirement_id'],
                'document_type_id'      => $typeId,
                'document_name'         => $req['document_name'],
                'document_code'         => $req['document_code'],
                'category'              => $req['category'] ?? 'General',
                'is_mandatory'          => $isMandatory,
                'is_critical'           => $isCritical,
                'requires_expiry'       => (bool)$req['requires_expiry'],
                'condition_notes'       => $req['condition_notes'],
                'instructions'          => $req['instructions'],
                'document_id'           => $doc['id'] ?? null,
                'file_path'             => $doc['file_path'] ?? null,
                'file_name'             => $doc['file_name'] ?? null,
                'file_size'             => $doc['file_size'] ?? 0,
                'version'               => $doc['version'] ?? 1,
                'expiry_date'           => $doc['expiry_date'] ?? null,
                'expiry_info'           => $expiryInfo,
                'status'                => $effectiveStatus,
                'raw_status'            => $doc['status'] ?? 'MISSING',
                'uploaded_by_type'      => $doc['uploaded_by_type'] ?? null,
                'verified_by_name'      => $doc['verified_by_name'] ?? null,
                'verified_at'           => $doc['verified_at'] ?? null,
                'rejection_reason'      => $doc['rejection_reason'] ?? null,
                'replacement_requested' => (bool)($doc['replacement_requested'] ?? 0),
                'notes'                 => $doc['notes'] ?? null,
            ];

            $items[] = $item;
            if ($isMandatory) {
                $mandatoryItems[] = $item;
            } else {
                $optionalItems[] = $item;
            }
        }

        // 7. Append any extra documents uploaded by user that are NOT part of service requirements
        foreach ($bestDocByType as $tId => $doc) {
            if (!isset($uniqueRequirements[$tId])) {
                $hasFile = self::hasValidFile($doc['file_path']);
                $expiryInfo = DocumentExpiryService::checkExpiry($doc['expiry_date'] ?? null);
                
                $effectiveStatus = 'UNDER_REVIEW';
                if ($hasFile) {
                    if (!empty($doc['expiry_date']) && $doc['expiry_date'] < $today) {
                        $effectiveStatus = 'EXPIRED';
                    } elseif ($doc['status'] === 'VERIFIED') {
                        $effectiveStatus = 'VERIFIED';
                    } elseif ($doc['status'] === 'REJECTED') {
                        $effectiveStatus = 'REJECTED';
                    }
                } else {
                    $effectiveStatus = 'MISSING';
                }

                $extraItem = [
                    'requirement_id'        => null,
                    'document_type_id'      => $tId,
                    'document_name'         => $doc['document_title'] ?: $doc['doc_type_name'],
                    'document_code'         => $doc['doc_type_code'] ?? 'CUSTOM_DOC',
                    'category'              => $doc['doc_type_category'] ?? 'Additional',
                    'is_mandatory'          => false,
                    'is_critical'           => false,
                    'requires_expiry'       => !empty($doc['expiry_date']),
                    'condition_notes'       => 'Additional applicant document',
                    'instructions'          => null,
                    'document_id'           => $doc['id'],
                    'file_path'             => $doc['file_path'],
                    'file_name'             => $doc['file_name'],
                    'file_size'             => $doc['file_size'] ?? 0,
                    'version'               => $doc['version'] ?? 1,
                    'expiry_date'           => $doc['expiry_date'] ?? null,
                    'expiry_info'           => $expiryInfo,
                    'status'                => $effectiveStatus,
                    'raw_status'            => $doc['status'],
                    'uploaded_by_type'      => $doc['uploaded_by_type'] ?? null,
                    'verified_by_name'      => $doc['verified_by_name'] ?? null,
                    'verified_at'           => $doc['verified_at'] ?? null,
                    'rejection_reason'      => $doc['rejection_reason'] ?? null,
                    'replacement_requested' => (bool)($doc['replacement_requested'] ?? 0),
                    'notes'                 => $doc['notes'] ?? null,
                ];

                $items[] = $extraItem;
                $optionalItems[] = $extraItem;
            }
        }

        // 8. Mathematically Sound Completion Percentage (PART 12, 46)
        // Formula: verified unique mandatory document types / total unique mandatory document types
        $percentage = 0;
        if ($totalRequired > 0) {
            $percentage = (int)round(($totalVerified / $totalRequired) * 100);
            // RULE: 100% is impossible while any mandatory document is missing, expired, rejected, or pending verification!
            if (($totalMissing > 0 || $totalPending > 0 || $totalRejected > 0 || $totalExpired > 0) && $percentage >= 100) {
                $percentage = 99;
            }
        } else {
            // If service has no mandatory requirements, base on verified uploaded documents
            $percentage = $totalVerified > 0 ? 100 : ($totalUploaded > 0 ? 50 : 0);
        }
        $percentage = min(100, max(0, $percentage));

        return [
            'total_required' => $totalRequired, // Unique mandatory document types
            'total_uploaded' => $totalUploaded, // Mandatory items uploaded
            'total_verified' => $totalVerified, // Mandatory items verified
            'total_pending'  => $totalPending,  // Mandatory items pending review
            'total_missing'  => $totalMissing,  // Mandatory items missing upload
            'total_rejected' => $totalRejected, // Mandatory items rejected
            'total_expired'  => $totalExpired,  // Mandatory items expired
            'total_optional' => count($optionalItems),
            'percentage'     => $percentage,
            'items'          => $items,
            'mandatory_items'=> $mandatoryItems,
            'optional_items' => $optionalItems,
        ];
    }

    /**
     * Resolve applicant profile photograph with strict hierarchy (PART 2, 36)
     * 1. Current application + PHOTO_WHITE_BG + valid physical file
     * 2. Current application + applicant photo document type + valid physical file
     * 3. Customer-level photo fallback from same customer + valid physical file
     * 4. Return null -> UI renders initials avatar (MN), NEVER company logo!
     */
    public static function getApplicantProfilePhoto(int $applicationId, int $customerId): ?array
    {
        $pdo = Database::getConnection();

        // Priority 1: Current application documents matching PHOTO_WHITE_BG with valid file
        if ($applicationId > 0) {
            $stmt = $pdo->prepare("SELECT d.*, dt.name as doc_type_name, dt.code as doc_type_code, dt.category 
                                   FROM documents d 
                                   JOIN document_types dt ON d.document_type_id = dt.id 
                                   WHERE d.application_id = ? 
                                     AND (dt.code = 'PHOTO_WHITE_BG' OR dt.name LIKE '%Passport Size Photograph%')
                                     AND d.file_path IS NOT NULL AND d.file_path != ''
                                   ORDER BY CASE WHEN d.status = 'VERIFIED' THEN 1 ELSE 2 END, d.version DESC, d.id DESC");
            $stmt->execute([$applicationId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $row) {
                if (self::hasValidFile($row['file_path'])) {
                    return $row;
                }
            }
        }

        // Priority 2: Current application documents with any photo doc type
        if ($applicationId > 0) {
            $stmt = $pdo->prepare("SELECT d.*, dt.name as doc_type_name, dt.code as doc_type_code, dt.category 
                                   FROM documents d 
                                   JOIN document_types dt ON d.document_type_id = dt.id 
                                   WHERE d.application_id = ? 
                                     AND (dt.name LIKE '%Photo%' OR dt.name LIKE '%Picture%')
                                     AND d.file_path IS NOT NULL AND d.file_path != ''
                                   ORDER BY CASE WHEN d.status = 'VERIFIED' THEN 1 ELSE 2 END, d.version DESC, d.id DESC");
            $stmt->execute([$applicationId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $row) {
                if (self::hasValidFile($row['file_path'])) {
                    return $row;
                }
            }
        }

        // Priority 3: Customer-level fallback from other applications of the SAME customer only
        if ($customerId > 0) {
            $stmt = $pdo->prepare("SELECT d.*, dt.name as doc_type_name, dt.code as doc_type_code, dt.category 
                                   FROM documents d 
                                   JOIN document_types dt ON d.document_type_id = dt.id 
                                   WHERE d.customer_id = ? 
                                     AND (dt.code = 'PHOTO_WHITE_BG' OR dt.name LIKE '%Photo%')
                                     AND d.file_path IS NOT NULL AND d.file_path != ''
                                   ORDER BY CASE WHEN d.status = 'VERIFIED' THEN 1 ELSE 2 END, d.version DESC, d.id DESC");
            $stmt->execute([$customerId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $row) {
                if (self::hasValidFile($row['file_path'])) {
                    return $row;
                }
            }
        }

        // Priority 4: Return null (Caller renders Initials avatar, NEVER company logo)
        return null;
    }

    /**
     * Initializes and generates initial document checklist matrix for a new application
     */
    public static function generateForApplication(int $applicationId, int $visaServiceId): array
    {
        return self::getChecklist($applicationId);
    }

    /**
     * Checks if all mandatory requirements are uploaded, non-expired, and verified.
     */
    public static function getUnverifiedMandatoryRequirements(int $applicationId): array
    {
        $checklist = self::getChecklist($applicationId);
        $unverified = [];

        foreach ($checklist['mandatory_items'] as $item) {
            if (empty($item['file_path']) || !self::hasValidFile($item['file_path'])) {
                $unverified[] = "{$item['document_name']} (Missing upload)";
            } elseif ($item['status'] === 'EXPIRED') {
                $unverified[] = "{$item['document_name']} (Document Expired)";
            } elseif ($item['status'] === 'REJECTED') {
                $unverified[] = "{$item['document_name']} (Document Rejected)";
            } elseif ($item['status'] !== 'VERIFIED') {
                $unverified[] = "{$item['document_name']} (Awaiting Staff Verification)";
            }
        }

        return $unverified;
    }
}
