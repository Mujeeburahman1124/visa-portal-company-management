<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\App;
use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Services\AuditService;
use App\Services\DocumentExpiryService;
use App\Services\DocumentVerificationService;
use App\Services\HealthCalculatorService;
use PDO;
use Exception;

class DocumentController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $typeId = (int)($_GET['type_id'] ?? 0);
        $serviceId = (int)($_GET['service_id'] ?? 0);
        $staffId = (int)($_GET['staff_id'] ?? 0);
        $expiryFilter = trim($_GET['expiry'] ?? '');

        // Base Query
        $sql = "SELECT d.*, 
                    dt.name as doc_type_name, dt.code as doc_type_code, dt.category, dt.requires_expiry,
                    a.application_number, a.id as app_id, a.current_stage, a.assigned_staff_id,
                    vs.name as service_name, ct.name as country_name, ct.flag_emoji,
                    c.full_name as customer_name, c.customer_code,
                    cp.passport_number,
                    u.name as verified_by_name,
                    uploader.name as uploaded_by_name
                FROM documents d 
                JOIN document_types dt ON d.document_type_id = dt.id 
                LEFT JOIN applications a ON d.application_id = a.id 
                LEFT JOIN visa_services vs ON a.visa_service_id = vs.id
                LEFT JOIN countries ct ON vs.country_id = ct.id
                JOIN customers c ON d.customer_id = c.id 
                LEFT JOIN customer_passports cp ON c.id = cp.customer_id AND cp.is_primary = 1
                LEFT JOIN users u ON d.verified_by = u.id 
                LEFT JOIN users uploader ON (d.uploaded_by_type = 'Staff' AND d.uploaded_by_id = uploader.id)
                WHERE 1=1";

        $params = [];

        if ($search !== '') {
            $sql .= " AND (d.document_title LIKE ? OR c.full_name LIKE ? OR c.customer_code LIKE ? OR a.application_number LIKE ? OR d.file_name LIKE ? OR cp.passport_number LIKE ? OR dt.name LIKE ?)";
            $term = "%{$search}%";
            $params = array_merge($params, [$term, $term, $term, $term, $term, $term, $term]);
        }

        $today = date('Y-m-d');
        $in7Days = date('Y-m-d', strtotime('+7 days'));
        $in30Days = date('Y-m-d', strtotime('+30 days'));

        if ($status !== '') {
            if ($status === 'EXPIRED') {
                $sql .= " AND d.expiry_date IS NOT NULL AND d.expiry_date < '{$today}'";
            } elseif ($status === 'EXPIRING_SOON') {
                $sql .= " AND d.expiry_date IS NOT NULL AND d.expiry_date >= '{$today}' AND d.expiry_date <= '{$in30Days}'";
            } else {
                $sql .= " AND d.status = ?";
                $params[] = $status;
            }
        }

        if ($typeId > 0) {
            $sql .= " AND dt.id = ?";
            $params[] = $typeId;
        }

        if ($serviceId > 0) {
            $sql .= " AND a.visa_service_id = ?";
            $params[] = $serviceId;
        }

        if ($staffId > 0) {
            $sql .= " AND a.assigned_staff_id = ?";
            $params[] = $staffId;
        }

        if ($expiryFilter === 'expired') {
            $sql .= " AND d.expiry_date IS NOT NULL AND d.expiry_date < '{$today}'";
        } elseif ($expiryFilter === '7days') {
            $sql .= " AND d.expiry_date IS NOT NULL AND d.expiry_date >= '{$today}' AND d.expiry_date <= '{$in7Days}'";
        } elseif ($expiryFilter === '30days') {
            $sql .= " AND d.expiry_date IS NOT NULL AND d.expiry_date >= '{$today}' AND d.expiry_date <= '{$in30Days}'";
        }

        $sql .= " ORDER BY CASE WHEN d.status = 'REJECTED' THEN 1 WHEN d.status = 'UNDER_REVIEW' THEN 2 WHEN d.expiry_date < '{$today}' THEN 3 ELSE 4 END, d.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Attach Expiry Analysis for each document
        foreach ($documents as &$doc) {
            $doc['expiry_info'] = DocumentExpiryService::checkExpiry($doc['expiry_date'] ?? null);
        }
        unset($doc);

        // Filter Options
        $docTypes = $pdo->query("SELECT id, name FROM document_types WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $services = $pdo->query("SELECT id, name FROM visa_services WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $staffMembers = $pdo->query("SELECT id, name FROM users WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Real Database Statistics
        $stats = [
            'total' => (int)$pdo->query("SELECT COUNT(*) FROM documents")->fetchColumn(),
            'pending_review' => (int)$pdo->query("SELECT COUNT(*) FROM documents WHERE status IN ('UNDER_REVIEW', 'UPLOADED')")->fetchColumn(),
            'verified' => (int)$pdo->query("SELECT COUNT(*) FROM documents WHERE status = 'VERIFIED'")->fetchColumn(),
            'rejected' => (int)$pdo->query("SELECT COUNT(*) FROM documents WHERE status = 'REJECTED'")->fetchColumn(),
            'expired' => (int)$pdo->query("SELECT COUNT(*) FROM documents WHERE expiry_date IS NOT NULL AND expiry_date < '{$today}'")->fetchColumn(),
            'expiring_soon' => (int)$pdo->query("SELECT COUNT(*) FROM documents WHERE expiry_date IS NOT NULL AND expiry_date >= '{$today}' AND expiry_date <= '{$in30Days}'")->fetchColumn(),
        ];

        require_once dirname(__DIR__) . '/Views/documents/index.php';
    }

    public function upload(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $appId = (int)($_POST['application_id'] ?? 0);
        $docTypeId = (int)($_POST['document_type_id'] ?? 0);
        $expiryDate = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        $docTitle = trim($_POST['document_title'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($appId <= 0 || $docTypeId <= 0 || empty($_FILES['document_file']['name'])) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/documents', 'Please provide a valid file and document type.', 'danger');
        }

        $app = $pdo->query("SELECT * FROM applications WHERE id = {$appId}")->fetch(PDO::FETCH_ASSOC);
        if (!$app) {
            redirect('/documents', 'Application not found.', 'danger');
        }
        $customerId = (int)$app['customer_id'];

        if (empty($docTitle)) {
            $docTitle = $pdo->query("SELECT name FROM document_types WHERE id = {$docTypeId}")->fetchColumn() ?: 'Uploaded Document';
        }

        $file = $_FILES['document_file'];
        $uploadDir = App::uploadPath();
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'docx', 'doc'];

        if (!in_array($ext, $allowedExtensions, true)) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/documents', 'Invalid file format. Allowed formats: PDF, JPG, PNG, DOCX.', 'danger');
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/documents', 'File exceeds maximum upload size (10MB).', 'danger');
        }

        $safeFileName = 'doc_' . $appId . '_' . $docTypeId . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $safeFileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/documents', 'Failed to save uploaded file to storage.', 'danger');
        }

        // Check if existing document record exists
        $existing = $pdo->query("SELECT * FROM documents WHERE application_id = {$appId} AND document_type_id = {$docTypeId}")->fetch(PDO::FETCH_ASSOC);

        $pdo->beginTransaction();
        try {
            if ($existing) {
                $newVersion = ((int)$existing['version']) + 1;
                
                // Archive to document_versions
                if (!empty($existing['file_path'])) {
                    $vStmt = $pdo->prepare("INSERT INTO document_versions (document_id, file_path, file_name, file_size, mime_type, version_number, rejection_reason, uploaded_by_type, uploaded_by_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $vStmt->execute([$existing['id'], $existing['file_path'], $existing['file_name'], $existing['file_size'], $existing['mime_type'], $existing['version'], $existing['rejection_reason'], $existing['uploaded_by_type'], $existing['uploaded_by_id']]);
                }

                $updateStmt = $pdo->prepare("UPDATE documents SET 
                    file_path = ?, file_name = ?, file_size = ?, mime_type = ?, version = ?, 
                    expiry_date = COALESCE(?, expiry_date), status = 'UNDER_REVIEW', uploaded_by_type = 'Staff', uploaded_by_id = ?,
                    rejection_reason = NULL, replacement_requested = 0, notes = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?");
                $updateStmt->execute([$safeFileName, $file['name'], $file['size'], $file['type'], $newVersion, $expiryDate, $currentUser['id'], $notes, $existing['id']]);
                $docId = (int)$existing['id'];
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO documents (
                    application_id, customer_id, document_type_id, document_title, file_path, file_name, 
                    file_size, mime_type, version, expiry_date, status, uploaded_by_type, uploaded_by_id, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, 'UNDER_REVIEW', 'Staff', ?, ?)");
                $insertStmt->execute([$appId, $customerId, $docTypeId, $docTitle, $safeFileName, $file['name'], $file['size'], $file['type'], $expiryDate, $currentUser['id'], $notes]);
                $docId = (int)$pdo->lastInsertId();
            }

            AuditService::log('UPLOAD_DOC', 'Documents', $docId, "Uploaded document '{$docTitle}' for application {$app['application_number']}", [
                'file_name' => $file['name'],
                'size' => $file['size'],
                'app_id' => $appId,
            ], $currentUser['id']);

            HealthCalculatorService::calculate($appId);

            $pdo->commit();

            redirect($_SERVER['HTTP_REFERER'] ?? "/applications/show?id={$appId}", "Document '{$docTitle}' uploaded successfully.", 'success');
        } catch (Exception $e) {
            $pdo->rollBack();
            if (file_exists($targetPath)) {
                @unlink($targetPath);
            }
            redirect($_SERVER['HTTP_REFERER'] ?? '/documents', 'Upload transaction failed: ' . $e->getMessage(), 'danger');
        }
    }

    public function verify(): void
    {
        AuthMiddleware::handle();
        $currentUser = auth_user();

        $docId = (int)($_POST['document_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if ($docId <= 0) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/documents', 'Invalid document record.', 'danger');
        }

        $res = DocumentVerificationService::verify($docId, (int)$currentUser['id'], $notes);
        redirect($_SERVER['HTTP_REFERER'] ?? '/documents', $res['message'], $res['success'] ? 'success' : 'danger');
    }

    public function reject(): void
    {
        AuthMiddleware::handle();
        $currentUser = auth_user();

        $docId = (int)($_POST['document_id'] ?? 0);
        $reason = trim($_POST['rejection_reason'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($docId <= 0 || empty($reason)) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/documents', 'A rejection reason is strictly mandatory when rejecting a document.', 'danger');
        }

        $res = DocumentVerificationService::reject($docId, (int)$currentUser['id'], $reason, $notes);
        redirect($_SERVER['HTTP_REFERER'] ?? '/documents', $res['message'], $res['success'] ? 'warning' : 'danger');
    }

    public function replace(): void
    {
        AuthMiddleware::handle();
        $currentUser = auth_user();

        $docId = (int)($_POST['document_id'] ?? 0);
        $expiryDate = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        $notes = trim($_POST['notes'] ?? '');

        if ($docId <= 0 || empty($_FILES['document_file']['name'])) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/documents', 'Please provide a valid replacement file.', 'danger');
        }

        $res = DocumentVerificationService::uploadReplacement(
            $docId,
            $_FILES['document_file'],
            (int)$currentUser['id'],
            'Staff',
            $expiryDate,
            $notes
        );

        redirect($_SERVER['HTTP_REFERER'] ?? '/documents', $res['message'], $res['success'] ? 'success' : 'danger');
    }

    /**
     * Preview / Stream Document Securely Inline
     */
    public function preview(): void
    {
        if (!is_authenticated() && !is_customer_authenticated()) {
            http_response_code(403);
            die('Access Denied. Please log in.');
        }

        $docId = (int)($_GET['id'] ?? 0);
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT d.*, dt.name as doc_name FROM documents d JOIN document_types dt ON d.document_type_id = dt.id WHERE d.id = ?");
        $stmt->execute([$docId]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doc || empty($doc['file_path'])) {
            http_response_code(404);
            die('Document file not found.');
        }

        $filePath = App::uploadPath($doc['file_path']);
        if (!file_exists($filePath)) {
            http_response_code(404);
            die('File not found in storage repository.');
        }

        $mime = 'application/octet-stream';
        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($filePath) ?: 'application/octet-stream';
        } elseif (class_exists('finfo')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($filePath) ?: 'application/octet-stream';
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . basename($doc['file_name']) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=3600');
        readfile($filePath);
        exit;
    }

    /**
     * Protected Safe File Download
     */
    public function download(): void
    {
        if (!is_authenticated() && !is_customer_authenticated()) {
            http_response_code(403);
            die('Access Denied. Please log in.');
        }

        $docId = (int)($_GET['id'] ?? 0);
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT d.* FROM documents d WHERE d.id = ?");
        $stmt->execute([$docId]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doc || empty($doc['file_path'])) {
            http_response_code(404);
            die('Document not found.');
        }

        $filePath = App::uploadPath($doc['file_path']);
        if (!file_exists($filePath)) {
            http_response_code(404);
            die('File not found on server.');
        }

        $currentUser = auth_user();
        AuditService::log('DOWNLOAD_DOC', 'Documents', $docId, "Downloaded document '{$doc['file_name']}'", [
            'doc_id' => $docId,
            'file_name' => $doc['file_name']
        ], $currentUser['id'] ?? null);

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($doc['file_name']) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    /**
     * Request a document from customer / applicant.
     */
    public function requestDocument(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        $appId = (int)($_POST['application_id'] ?? $_GET['application_id'] ?? 0);
        $docTypeId = (int)($_POST['document_type_id'] ?? $_GET['document_type_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if ($appId <= 0 || $docTypeId <= 0) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/documents', 'Invalid application or document type.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT a.*, c.full_name as customer_name, c.id as customer_id, dt.name as doc_type_name 
            FROM applications a 
            JOIN customers c ON a.customer_id = c.id 
            JOIN document_types dt ON dt.id = ? 
            WHERE a.id = ?");
        $stmt->execute([$docTypeId, $appId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            redirect('/documents', 'Application or document type not found.', 'danger');
        }

        // Trigger Real-Time Notification
        try {
            \App\Services\NotificationService::trigger('visa.document_required', [
                'application_id' => $appId,
                'customer_id' => $app['customer_id'],
                'application_number' => $app['application_number'] ?? '',
                'documentName' => $app['doc_type_name'],
                'notes' => $notes,
                'actionUrl' => (string)\App\Config\Env::get('APP_URL', 'http://localhost:8000') . "/portal/documents",
                'portal_link' => "/portal/documents",
                'severity' => 'warning',
            ]);
        } catch (\Throwable $e) {}

        AuditService::log('REQUEST_DOC', 'Documents', $docTypeId, "Requested document '{$app['doc_type_name']}' for application {$app['application_number']}", [
            'application_id' => $appId,
            'doc_type_id' => $docTypeId,
            'notes' => $notes,
        ], $user['id'] ?? null);

        redirect($_SERVER['HTTP_REFERER'] ?? "/applications/show?id={$appId}", "Document '{$app['doc_type_name']}' requested from customer successfully.", 'success');
    }

    /**
     * Fetch Document Version History (JSON)
     */
    public function history(): void
    {
        AuthMiddleware::handle();
        $docId = (int)($_GET['id'] ?? 0);
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT dv.*, u.name as uploader_name 
            FROM document_versions dv 
            LEFT JOIN users u ON (dv.uploaded_by_type = 'Staff' AND dv.uploaded_by_id = u.id)
            WHERE dv.document_id = ? 
            ORDER BY dv.version_number DESC");
        $stmt->execute([$docId]);
        $versions = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $versions]);
        exit;
    }
}
