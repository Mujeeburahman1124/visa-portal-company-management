<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Config\Database;
use App\Services\AuditService;
use App\Services\DocumentChecklistService;
use App\Services\DocumentVerificationService;
use App\Services\DocumentExpiryService;
use PDO;

class DocumentApiController extends ApiController
{
    /**
     * GET /api/documents?application_id=...
     */
    public function index(int $applicationId = 0): void
    {
        $user = $this->requireAuth();
        $scopedBranchId = $this->getScopedBranchId((int)($_GET['branch_id'] ?? 0));

        if ($applicationId <= 0) {
            $applicationId = (int)($_GET['application_id'] ?? 0);
        }

        $pdo = Database::getConnection();

        if ($applicationId > 0) {
            // Verify application access and branch scoping
            if ($scopedBranchId > 0) {
                $checkStmt = $pdo->prepare("SELECT id FROM applications WHERE id = ? AND branch_id = ?");
                $checkStmt->execute([$applicationId, $scopedBranchId]);
                if (!$checkStmt->fetch()) {
                    $this->jsonError('Application not found or access denied for your branch.', [], 403);
                    return;
                }
            }

            $checklist = DocumentChecklistService::getChecklist($applicationId);
            $this->jsonSuccess($checklist, 'Application document checklist retrieved');
            return;
        }

        $status = $_GET['status'] ?? '';
        $sql = "SELECT d.*, dt.name as document_type_name, dt.category, c.full_name as customer_name, a.application_number 
            FROM documents d 
            JOIN document_types dt ON d.document_type_id = dt.id 
            JOIN customers c ON d.customer_id = c.id
            LEFT JOIN applications a ON d.application_id = a.id
            WHERE 1=1";
        $params = [];

        if ($scopedBranchId > 0) {
            $sql .= " AND (a.branch_id = ? OR a.branch_id IS NULL)";
            $params[] = $scopedBranchId;
        }

        if ($status !== '') {
            $sql .= " AND d.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY d.created_at DESC LIMIT 100";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->jsonSuccess($docs, 'Documents retrieved successfully');
    }

    /**
     * POST /api/documents/verify
     */
    public function verify(int $id = 0): void
    {
        $user = $this->requireAuth();
        $roleSlug = $user['role_slug'] ?? '';
        if (!in_array($roleSlug, ['super-admin', 'admin', 'branch-manager', 'visa-manager'], true) && !user_can('documents.verify') && !user_can('documents.manage')) {
            $this->jsonError('Unauthorized: You do not have permission to verify documents.', [], 403);
            return;
        }

        $input = $this->getJsonInput();
        if ($id <= 0) {
            $id = (int)($input['document_id'] ?? $_POST['document_id'] ?? $_GET['id'] ?? 0);
        }

        $pdo = Database::getConnection();
        $dStmt = $pdo->prepare("SELECT d.* FROM documents d WHERE d.id = ?");
        $dStmt->execute([$id]);
        $doc = $dStmt->fetch(PDO::FETCH_ASSOC);
        if (!$doc || !\App\Controllers\DocumentController::authorizeDocumentAccess($doc)) {
            $this->jsonError('Document not found or unauthorized for your branch.', [], 403);
            return;
        }

        $userId = (int)$user['id'];
        $notes = trim($input['notes'] ?? '');

        $res = DocumentVerificationService::verify($id, $userId, $notes);
        if (!$res['success']) {
            $this->jsonError($res['message'], [], 422);
        }

        $this->jsonSuccess($res['data'], $res['message']);
    }

    /**
     * POST /api/documents/reject
     */
    public function reject(int $id = 0): void
    {
        $user = $this->requireAuth();
        $roleSlug = $user['role_slug'] ?? '';
        if (!in_array($roleSlug, ['super-admin', 'admin', 'branch-manager', 'visa-manager'], true) && !user_can('documents.verify') && !user_can('documents.manage')) {
            $this->jsonError('Unauthorized: You do not have permission to reject documents.', [], 403);
            return;
        }

        $input = $this->getJsonInput();
        if ($id <= 0) {
            $id = (int)($input['document_id'] ?? $_POST['document_id'] ?? $_GET['id'] ?? 0);
        }

        $reason = trim($input['reason'] ?? $input['rejection_reason'] ?? '');
        if ($reason === '') {
            $this->jsonError('Rejection reason is required.', ['reason' => 'Required'], 422);
        }

        $pdo = Database::getConnection();
        $dStmt = $pdo->prepare("SELECT d.* FROM documents d WHERE d.id = ?");
        $dStmt->execute([$id]);
        $doc = $dStmt->fetch(PDO::FETCH_ASSOC);
        if (!$doc || !\App\Controllers\DocumentController::authorizeDocumentAccess($doc)) {
            $this->jsonError('Document not found or unauthorized for your branch.', [], 403);
            return;
        }

        $userId = (int)$user['id'];
        $notes = trim($input['notes'] ?? '');

        $res = DocumentVerificationService::reject($id, $userId, $reason, $notes);
        if (!$res['success']) {
            $this->jsonError($res['message'], [], 422);
        }

        $this->jsonSuccess($res['data'], $res['message']);
    }

    /**
     * POST /api/documents/ocr-passport
     * Upload and extract passport data via OCR & MRZ parsing.
     */
    public function ocrPassport(): void
    {
        $user = $this->requireAuth();

        $file = $_FILES['passport_file'] ?? $_FILES['file'] ?? null;
        if (!$file || empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;
            $errMessage = match ($errorCode) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Passport file exceeds maximum allowed upload size (20MB).',
                UPLOAD_ERR_PARTIAL => 'File upload was interrupted. Please retry.',
                UPLOAD_ERR_NO_FILE => 'No passport file was provided. Please upload or take a photo of your passport.',
                default => 'Failed to upload passport file. Please check file permissions and retry.'
            };
            $this->jsonError($errMessage, ['passport_file' => $errMessage], 400);
            return;
        }

        $origName = $file['name'] ?? 'passport.jpg';
        $tmpPath = $file['tmp_name'];
        $fileSize = (int)$file['size'];

        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpPath) ?: ($file['type'] ?? 'application/octet-stream');
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        if (!in_array($mimeType, $allowedMimes, true)) {
            $this->jsonError('Unsupported file type. Please upload a clear JPG, PNG, WEBP, or PDF passport scan.', [
                'file_type' => 'Allowed: JPG, PNG, WEBP, PDF. Detected: ' . $mimeType
            ], 422);
            return;
        }

        // Prepare temporary storage directory
        $tempDir = dirname(__DIR__, 3) . '/public/uploads/temp_ocr';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }

        // Clean up temporary OCR files older than 2 hours
        $this->cleanupOldTempFiles($tempDir, 7200);

        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if ($mimeType === 'application/pdf') {
            $ext = 'pdf';
        } elseif (empty($ext) || !in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf'], true)) {
            $ext = 'jpg';
        }

        $token = bin2hex(random_bytes(16));
        $savedFilename = 'temp_passport_' . $token . '.' . $ext;
        $targetPath = $tempDir . DIRECTORY_SEPARATOR . $savedFilename;

        if (!move_uploaded_file($tmpPath, $targetPath)) {
            $this->jsonError('Failed to store uploaded passport file for processing.', [], 500);
            return;
        }

        // Save metadata in session for secure retrieval
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (!isset($_SESSION['temp_ocr_passports']) || !is_array($_SESSION['temp_ocr_passports'])) {
            $_SESSION['temp_ocr_passports'] = [];
        }
        $_SESSION['temp_ocr_passports'][$token] = [
            'file_path' => $targetPath,
            'file_name' => $savedFilename,
            'orig_name' => $origName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'created_at' => time(),
            'user_id' => $user['id'] ?? 0
        ];

        // Process OCR extraction
        $ocrService = new \App\Services\Ocr\PassportOcrService();
        $ocrResult = $ocrService->extract($targetPath, ['original_name' => $origName, 'mime_type' => $mimeType]);

        $previewUrl = '/api/documents/temp-preview?token=' . urlencode($token);

        // Check if extraction succeeded with readable fields
        if (empty($ocrResult['success'])) {
            error_log("[OCR DEBUG] Passport OCR Extraction Notice for {$origName}: " . ($ocrResult['message'] ?? 'No text detected'));

            $this->jsonError($ocrResult['message'] ?? 'Passport data could not be confidently extracted. The scan may be blurry or obscured. Please enter details manually or scan another image.', [
                'temp_token' => $token,
                'preview_url' => $previewUrl,
                'original_filename' => $origName,
                'ocr_provider' => $ocrResult['ocr_provider'] ?? 'none',
                'overall_confidence' => 0,
                'mrz_detected' => false,
                'extracted' => $ocrResult['extracted'] ?? [],
                'confidence' => []
            ], 422);
            return;
        }

        // Development-only server-side logging with masked passport number (Requirement #6)
        $maskedPass = \App\Services\Ocr\PassportOcrService::maskPassportNumber((string)($ocrResult['extracted']['passport_number'] ?? ''));
        error_log(sprintf(
            "[OCR DEBUG] Success | Provider: %s | Overall Conf: %s%% | MRZ: %s | Pass: %s | Name: %s",
            $ocrResult['ocr_provider'] ?? 'unknown',
            $ocrResult['overall_confidence'] ?? 0,
            !empty($ocrResult['mrz_detected']) ? 'YES' : 'NO',
            $maskedPass,
            ($ocrResult['extracted']['full_name'] ?? 'N/A')
        ));

        // Audit log
        AuditService::log(
            'PASSPORT_OCR_EXTRACTED',
            'documents',
            0,
            "Passport OCR processed for '{$origName}' by User #{$user['id']} (Provider: {$ocrResult['ocr_provider']}, Overall Confidence: {$ocrResult['overall_confidence']}%)"
        );

        $this->jsonSuccess([
            'temp_token' => $token,
            'preview_url' => $previewUrl,
            'original_filename' => $origName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'ocr_provider' => $ocrResult['ocr_provider'],
            'overall_confidence' => $ocrResult['overall_confidence'],
            'mrz_detected' => $ocrResult['mrz_detected'],
            'warnings' => $ocrResult['warnings'],
            'extracted' => $ocrResult['extracted'],
            'confidence' => $ocrResult['confidence']
        ], 'Passport data extracted successfully.');
    }

    /**
     * GET /api/documents/temp-preview?token=...
     * Secure preview of temporary uploaded passport before application submission.
     */
    public function tempPreview(): void
    {
        $this->requireAuth();
        $token = trim($_GET['token'] ?? '');
        if ($token === '' || !preg_match('/^[a-f0-9]{32}$/', $token)) {
            http_response_code(400);
            echo 'Invalid token.';
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $info = $_SESSION['temp_ocr_passports'][$token] ?? null;
        if (!$info || empty($info['file_path']) || !file_exists($info['file_path'])) {
            // Check direct file existence in temp_ocr as fallback
            $tempDir = dirname(__DIR__, 3) . '/public/uploads/temp_ocr';
            $pattern = $tempDir . DIRECTORY_SEPARATOR . 'temp_passport_' . $token . '.*';
            $matches = glob($pattern);
            if (!empty($matches) && file_exists($matches[0])) {
                $filePath = $matches[0];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $filePath) ?: 'image/jpeg';
                finfo_close($finfo);
            } else {
                http_response_code(404);
                echo 'Temporary file not found or expired.';
                exit;
            }
        } else {
            $filePath = $info['file_path'];
            $mimeType = $info['mime_type'];
        }

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=300');
        readfile($filePath);
        exit;
    }

    /**
     * Cleanup old temporary files
     */
    private function cleanupOldTempFiles(string $dir, int $maxAgeSeconds): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $now = time();
        $files = scandir($dir);
        if ($files === false) {
            return;
        }
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            $full = $dir . DIRECTORY_SEPARATOR . $f;
            if (is_file($full) && ($now - filemtime($full)) > $maxAgeSeconds) {
                @unlink($full);
            }
        }
    }

    /**
     * GET /api/documents/expiry-summary
     */
    public function expirySummary(): void
    {
        $this->requireAuth();
        $summary = DocumentExpiryService::getExpirySummary();
        $this->jsonSuccess($summary, 'Document expiry summary');
    }
}
