<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\App;
use App\Config\Database;
use PDO;
use Exception;

class DocumentVerificationService
{
    /**
     * Atomically verify a document
     */
    public static function verify(int $docId, int $verifierId, ?string $notes = null): array
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT d.*, dt.name as doc_type_name, a.application_number, a.id as app_id, c.full_name as customer_name, c.id as customer_id
            FROM documents d
            JOIN document_types dt ON d.document_type_id = dt.id
            LEFT JOIN applications a ON d.application_id = a.id
            JOIN customers c ON d.customer_id = c.id
            WHERE d.id = ?");
        $stmt->execute([$docId]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doc) {
            return ['success' => false, 'message' => 'Document not found.'];
        }

        $pdo->beginTransaction();
        try {
            $updStmt = $pdo->prepare("UPDATE documents 
                SET status = 'VERIFIED',
                    verified_by = ?,
                    verified_at = CURRENT_TIMESTAMP,
                    rejection_reason = NULL,
                    replacement_requested = 0,
                    notes = COALESCE(?, notes),
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?");
            $updStmt->execute([$verifierId, $notes, $docId]);

            // If application is linked, update application status if it was 'Action Required' due to document
            if (!empty($doc['app_id'])) {
                $appId = (int)$doc['app_id'];
                HealthCalculatorService::calculate($appId);

                // Check if any rejected documents remain
                $checkRej = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE application_id = ? AND status = 'REJECTED'");
                $checkRej->execute([$appId]);
                $rejCount = (int)$checkRej->fetchColumn();

                if ($rejCount === 0) {
                    // Check if current status is Action Required, recover to In Process or Registered
                    $appStmt = $pdo->prepare("SELECT status, current_stage FROM applications WHERE id = ?");
                    $appStmt->execute([$appId]);
                    $app = $appStmt->fetch(PDO::FETCH_ASSOC);

                    if ($app && $app['status'] === 'Action Required') {
                        $pdo->prepare("UPDATE applications SET status = 'In Process', next_action = 'All required documents verified. Proceed with consulate processing.', updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                            ->execute([$appId]);
                    }
                }
            }

            // Notification & Central Real-Time Dispatch
            try {
                \App\Services\NotificationService::trigger('document.approved', [
                    'application_id' => $appId,
                    'customer_id' => $doc['customer_id'],
                    'application_number' => $doc['application_number'] ?? '',
                    'documentName' => $doc['doc_type_name'],
                    'actionUrl' => (string)\App\Config\Env::get('APP_URL', 'http://localhost:8000') . "/portal/documents",
                    'portal_link' => "/portal/documents",
                    'severity' => 'success',
                ]);
            } catch (\Throwable $e) {}

            // Audit
            AuditService::log('VERIFY_DOC', 'Documents', $docId, "Verified document '{$doc['doc_type_name']}' for {$doc['customer_name']} (App: {$doc['application_number']})", [
                'doc_id' => $docId,
                'application_number' => $doc['application_number'],
                'verified_by' => $verifierId
            ], $verifierId);

            $pdo->commit();

            return [
                'success' => true,
                'message' => "Document '{$doc['doc_type_name']}' verified successfully.",
                'data' => ['document_id' => $docId, 'status' => 'VERIFIED']
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Verification failed: ' . $e->getMessage()];
        }
    }

    /**
     * Atomically reject a document (Mandatory Rejection Reason)
     */
    public static function reject(int $docId, int $verifierId, string $rejectionReason, ?string $notes = null): array
    {
        $rejectionReason = trim($rejectionReason);
        if (empty($rejectionReason)) {
            return ['success' => false, 'message' => 'A rejection reason is strictly mandatory.'];
        }

        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT d.*, dt.name as doc_type_name, a.application_number, a.id as app_id, c.full_name as customer_name, c.id as customer_id
            FROM documents d
            JOIN document_types dt ON d.document_type_id = dt.id
            LEFT JOIN applications a ON d.application_id = a.id
            JOIN customers c ON d.customer_id = c.id
            WHERE d.id = ?");
        $stmt->execute([$docId]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doc) {
            return ['success' => false, 'message' => 'Document not found.'];
        }

        $pdo->beginTransaction();
        try {
            $updStmt = $pdo->prepare("UPDATE documents 
                SET status = 'REJECTED',
                    verified_by = ?,
                    verified_at = CURRENT_TIMESTAMP,
                    rejection_reason = ?,
                    replacement_requested = 1,
                    notes = COALESCE(?, notes),
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?");
            $updStmt->execute([$verifierId, $rejectionReason, $notes, $docId]);

            // Set Application to Action Required and update next action
            if (!empty($doc['app_id'])) {
                $appId = (int)$doc['app_id'];
                $due3Days = date('Y-m-d', strtotime('+3 days'));
                $pdo->prepare("UPDATE applications 
                    SET status = 'Action Required',
                        next_action = ?,
                        next_action_due_date = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?")
                    ->execute(["Upload replacement for rejected document: {$doc['doc_type_name']}", $due3Days, $appId]);

                HealthCalculatorService::calculate($appId);
            }

            // Create notification for customer & staff via Central NotificationService
            try {
                \App\Services\NotificationService::trigger('document.rejected', [
                    'application_id' => $appId,
                    'customer_id' => $doc['customer_id'],
                    'application_number' => $doc['application_number'] ?? '',
                    'documentName' => $doc['doc_type_name'],
                    'rejectionReason' => $rejectionReason,
                    'actionUrl' => (string)\App\Config\Env::get('APP_URL', 'http://localhost:8000') . "/portal/documents",
                    'portal_link' => "/portal/documents",
                    'severity' => 'danger',
                ]);
            } catch (\Throwable $e) {}

            // Audit
            AuditService::log('REJECT_DOC', 'Documents', $docId, "Rejected document '{$doc['doc_type_name']}' for {$doc['customer_name']} (Reason: {$rejectionReason})", [
                'doc_id' => $docId,
                'application_number' => $doc['application_number'],
                'reason' => $rejectionReason,
                'rejected_by' => $verifierId
            ], $verifierId);

            $pdo->commit();

            return [
                'success' => true,
                'message' => "Document '{$doc['doc_type_name']}' rejected. Replacement requested.",
                'data' => ['document_id' => $docId, 'status' => 'REJECTED', 'reason' => $rejectionReason]
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Rejection failed: ' . $e->getMessage()];
        }
    }

    /**
     * Upload a replacement document, preserving version history
     */
    public static function uploadReplacement(int $docId, array $fileData, int $uploaderId, string $uploaderType = 'Staff', ?string $expiryDate = null, ?string $notes = null): array
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT d.*, dt.name as doc_type_name, a.application_number, a.id as app_id, c.full_name as customer_name
            FROM documents d
            JOIN document_types dt ON d.document_type_id = dt.id
            LEFT JOIN applications a ON d.application_id = a.id
            JOIN customers c ON d.customer_id = c.id
            WHERE d.id = ?");
        $stmt->execute([$docId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            return ['success' => false, 'message' => 'Document record not found.'];
        }

        // Validate File
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'docx', 'doc'];
        $ext = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts, true)) {
            return ['success' => false, 'message' => 'Invalid file format. Allowed formats: PDF, JPG, JPEG, PNG, DOCX.'];
        }

        // 10MB limit
        if ($fileData['size'] > 10 * 1024 * 1024) {
            return ['success' => false, 'message' => 'File size exceeds maximum allowed limit (10MB).'];
        }

        $uploadDir = App::uploadPath();
        $safeFileName = 'doc_' . ($existing['application_id'] ?? 'cust') . '_' . $existing['document_type_id'] . '_v' . (((int)$existing['version']) + 1) . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $safeFileName;

        if (!move_uploaded_file($fileData['tmp_name'], $targetPath)) {
            return ['success' => false, 'message' => 'Failed to save uploaded file to storage.'];
        }

        $pdo->beginTransaction();
        try {
            // Archive current version to document_versions
            if (!empty($existing['file_path'])) {
                $vStmt = $pdo->prepare("INSERT INTO document_versions (
                    document_id, file_path, file_name, file_size, mime_type, version_number, rejection_reason, uploaded_by_type, uploaded_by_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $vStmt->execute([
                    $docId,
                    $existing['file_path'],
                    $existing['file_name'],
                    $existing['file_size'],
                    $existing['mime_type'],
                    $existing['version'],
                    $existing['rejection_reason'],
                    $existing['uploaded_by_type'],
                    $existing['uploaded_by_id']
                ]);
            }

            $newVersion = ((int)$existing['version']) + 1;

            // Update main document record to UNDER_REVIEW
            $updStmt = $pdo->prepare("UPDATE documents SET 
                file_path = ?,
                file_name = ?,
                file_size = ?,
                mime_type = ?,
                version = ?,
                expiry_date = COALESCE(?, expiry_date),
                status = 'UNDER_REVIEW',
                uploaded_by_type = ?,
                uploaded_by_id = ?,
                rejection_reason = NULL,
                replacement_requested = 0,
                notes = COALESCE(?, notes),
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?");
            $updStmt->execute([
                $safeFileName,
                $fileData['name'],
                $fileData['size'],
                $fileData['type'],
                $newVersion,
                $expiryDate,
                $uploaderType,
                $uploaderId,
                $notes,
                $docId
            ]);

            if (!empty($existing['app_id'])) {
                HealthCalculatorService::calculate((int)$existing['app_id']);
            }

            AuditService::log('REPLACE_DOC', 'Documents', $docId, "Uploaded replacement (Version {$newVersion}) for '{$existing['doc_type_name']}'", [
                'doc_id' => $docId,
                'version' => $newVersion,
                'file_name' => $fileData['name']
            ], $uploaderId);

            $pdo->commit();

            return [
                'success' => true,
                'message' => "Replacement uploaded successfully (Version {$newVersion}). Document is now Under Review.",
                'data' => ['document_id' => $docId, 'version' => $newVersion, 'status' => 'UNDER_REVIEW']
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Replacement upload failed: ' . $e->getMessage()];
        }
    }
}
