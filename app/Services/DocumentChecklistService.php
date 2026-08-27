<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use PDO;

class DocumentChecklistService
{
    /**
     * Get complete smart checklist for an application
     */
    public static function getChecklist(int $applicationId): array
    {
        $pdo = Database::getConnection();

        // 1. Get Application Service ID and Customer ID
        $stmt = $pdo->prepare("SELECT visa_service_id, customer_id FROM applications WHERE id = ?");
        $stmt->execute([$applicationId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            return [
                'total_required' => 0,
                'total_uploaded' => 0,
                'total_verified' => 0,
                'total_missing' => 0,
                'total_rejected' => 0,
                'total_expired' => 0,
                'percentage' => 0,
                'items' => [],
            ];
        }

        // 2. Fetch all service requirements with document types
        $stmt = $pdo->prepare("SELECT vr.id as requirement_id, vr.is_mandatory, vr.is_critical, vr.condition_notes, vr.instructions,
                    dt.id as document_type_id, dt.name as document_name, dt.code as document_code, 
                    dt.category, dt.requires_expiry
             FROM visa_requirements vr
             JOIN document_types dt ON vr.document_type_id = dt.id
             WHERE vr.service_id = ?
             ORDER BY vr.is_critical DESC, vr.is_mandatory DESC, dt.name ASC");
        $stmt->execute([$app['visa_service_id']]);
        $requirements = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 3. Fetch all current uploaded documents for this application
        $stmt = $pdo->prepare("SELECT d.*, u.name as verified_by_name 
             FROM documents d 
             LEFT JOIN users u ON d.verified_by = u.id 
             WHERE d.application_id = ?");
        $stmt->execute([$applicationId]);
        $docs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $docsByType = [];
        foreach ($docs as $doc) {
            $docsByType[(int)$doc['document_type_id']] = $doc;
        }

        $items = [];
        $totalRequired = 0;
        $totalVerified = 0;
        $totalUploaded = 0;
        $totalMissing = 0;
        $totalRejected = 0;
        $totalExpired = 0;

        foreach ($requirements as $req) {
            $typeId = (int)$req['document_type_id'];
            $isMandatory = (bool)$req['is_mandatory'];
            $isCritical = !empty($req['is_critical']);

            if ($isMandatory) {
                $totalRequired++;
            }

            $doc = $docsByType[$typeId] ?? null;
            $rawStatus = $doc ? ($doc['status'] ?? 'UPLOADED') : 'MISSING';
            $expiryInfo = DocumentExpiryService::checkExpiry($doc['expiry_date'] ?? null);

            // Compute effective status
            $effectiveStatus = $rawStatus;
            if ($doc && !empty($doc['file_path'])) {
                $totalUploaded++;

                if ($expiryInfo['status'] === 'EXPIRED') {
                    $effectiveStatus = 'EXPIRED';
                    $totalExpired++;
                } elseif ($rawStatus === 'VERIFIED') {
                    $totalVerified++;
                } elseif ($rawStatus === 'REJECTED') {
                    $totalRejected++;
                }
            } else {
                $totalMissing++;
            }

            $items[] = [
                'requirement_id' => $req['requirement_id'],
                'document_type_id' => $typeId,
                'document_name' => $req['document_name'],
                'document_code' => $req['document_code'],
                'category' => $req['category'] ?? 'General',
                'is_mandatory' => $isMandatory,
                'is_critical' => $isCritical,
                'requires_expiry' => (bool)$req['requires_expiry'],
                'condition_notes' => $req['condition_notes'],
                'instructions' => $req['instructions'],
                'document_id' => $doc['id'] ?? null,
                'file_path' => $doc['file_path'] ?? null,
                'file_name' => $doc['file_name'] ?? null,
                'file_size' => $doc['file_size'] ?? 0,
                'version' => $doc['version'] ?? 1,
                'expiry_date' => $doc['expiry_date'] ?? null,
                'expiry_info' => $expiryInfo,
                'status' => $effectiveStatus,
                'raw_status' => $rawStatus,
                'uploaded_by_type' => $doc['uploaded_by_type'] ?? null,
                'verified_by_name' => $doc['verified_by_name'] ?? null,
                'verified_at' => $doc['verified_at'] ?? null,
                'rejection_reason' => $doc['rejection_reason'] ?? null,
                'replacement_requested' => (bool)($doc['replacement_requested'] ?? 0),
                'notes' => $doc['notes'] ?? null,
            ];
        }

        // Also append any extra custom documents uploaded that were not in initial service requirements
        foreach ($docs as $doc) {
            $typeId = (int)$doc['document_type_id'];
            $found = false;
            foreach ($items as $item) {
                if ($item['document_type_id'] === $typeId) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $expiryInfo = DocumentExpiryService::checkExpiry($doc['expiry_date'] ?? null);
                $effectiveStatus = $doc['status'];
                if ($expiryInfo['status'] === 'EXPIRED') {
                    $effectiveStatus = 'EXPIRED';
                }

                $items[] = [
                    'requirement_id' => null,
                    'document_type_id' => $typeId,
                    'document_name' => $doc['document_title'] ?? 'Additional Document',
                    'document_code' => 'CUSTOM_DOC',
                    'category' => 'Additional',
                    'is_mandatory' => false,
                    'is_critical' => false,
                    'requires_expiry' => !empty($doc['expiry_date']),
                    'condition_notes' => 'Additional requested document',
                    'instructions' => null,
                    'document_id' => $doc['id'],
                    'file_path' => $doc['file_path'],
                    'file_name' => $doc['file_name'],
                    'file_size' => $doc['file_size'],
                    'version' => $doc['version'],
                    'expiry_date' => $doc['expiry_date'],
                    'expiry_info' => $expiryInfo,
                    'status' => $effectiveStatus,
                    'raw_status' => $doc['status'],
                    'uploaded_by_type' => $doc['uploaded_by_type'],
                    'verified_by_name' => $doc['verified_by_name'],
                    'verified_at' => $doc['verified_at'],
                    'rejection_reason' => $doc['rejection_reason'],
                    'replacement_requested' => (bool)$doc['replacement_requested'],
                    'notes' => $doc['notes'],
                ];
            }
        }

        $percentage = $totalRequired > 0 ? (int)round(($totalVerified / $totalRequired) * 100) : 100;
        $percentage = min(100, max(0, $percentage));

        return [
            'total_required' => $totalRequired,
            'total_uploaded' => $totalUploaded,
            'total_verified' => $totalVerified,
            'total_missing' => $totalMissing,
            'total_rejected' => $totalRejected,
            'total_expired' => $totalExpired,
            'percentage' => $percentage,
            'items' => $items,
        ];
    }

    /**
     * Initializes and generates initial document checklist matrix for a new application
     */
    public static function generateForApplication(int $applicationId, int $visaServiceId): array
    {
        return self::getChecklist($applicationId);
    }
}
