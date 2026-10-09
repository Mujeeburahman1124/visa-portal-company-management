<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\App;
use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Services\AuditService;
use App\Services\DocumentChecklistService;
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
                LEFT JOIN document_types dt ON d.document_type_id = dt.id 
                LEFT JOIN applications a ON d.application_id = a.id 
                LEFT JOIN visa_services vs ON a.visa_service_id = vs.id
                LEFT JOIN countries ct ON vs.country_id = ct.id
                LEFT JOIN customers c ON d.customer_id = c.id 
                LEFT JOIN customer_passports cp ON c.id = cp.customer_id AND cp.is_primary = 1
                LEFT JOIN users u ON d.verified_by = u.id 
                LEFT JOIN users uploader ON (d.uploaded_by_type = 'Staff' AND d.uploaded_by_id = uploader.id)
                WHERE 1=1";

        $params = [];
        $user = auth_user();
        $userBranch = (int)($user['branch_id'] ?? 0);
        $roleSlug = $user['role_slug'] ?? '';
        if ($userBranch > 0 && !in_array($roleSlug, ['super-admin', 'admin'], true)) {
            $sql .= " AND (a.branch_id = ? OR a.branch_id IS NULL)";
            $params[] = $userBranch;
        }

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

        $countryId = (int)($_GET['country_id'] ?? 0);
        $uploadedByType = trim($_GET['uploaded_by_type'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');
        $category = trim($_GET['category'] ?? '');
        $customerId = (int)($_GET['customer_id'] ?? 0);
        $fileFormat = strtolower(trim($_GET['file_format'] ?? ''));

        if ($countryId > 0) {
            $sql .= " AND vs.country_id = ?";
            $params[] = $countryId;
        }

        if ($uploadedByType !== '') {
            $sql .= " AND d.uploaded_by_type = ?";
            $params[] = $uploadedByType;
        }

        if ($dateFrom !== '') {
            $sql .= " AND DATE(d.created_at) >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $sql .= " AND DATE(d.created_at) <= ?";
            $params[] = $dateTo;
        }

        if ($category !== '') {
            $sql .= " AND dt.category = ?";
            $params[] = $category;
        }

        if ($customerId > 0) {
            $sql .= " AND d.customer_id = ?";
            $params[] = $customerId;
        }

        if ($fileFormat !== '') {
            $sql .= " AND LOWER(d.file_name) LIKE ?";
            $params[] = "%.{$fileFormat}";
        }

        $sql .= " ORDER BY CASE WHEN d.status = 'REJECTED' THEN 1 WHEN d.status = 'UNDER_REVIEW' THEN 2 WHEN d.expiry_date < '{$today}' THEN 3 ELSE 4 END, d.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $allDocuments = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Check if CSV export requested
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->outputCsv($allDocuments);
            return;
        }

        // Flat Document Pagination
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(5, min(100, (int)($_GET['per_page'] ?? 15)));
        $totalRecords = count($allDocuments);
        $totalPages = max(1, (int)ceil($totalRecords / $perPage));
        if ($page > $totalPages) $page = $totalPages;

        $documents = array_slice($allDocuments, ($page - 1) * $perPage, $perPage);

        // Attach Expiry Analysis for each document
        foreach ($documents as &$doc) {
            $doc['expiry_info'] = DocumentExpiryService::checkExpiry($doc['expiry_date'] ?? null);
        }
        unset($doc);

        // ==============================================================
        // 1. APPLICANT FOLDER DIRECTORY QUERY (PRIMARY PRESENTATION)
        // ==============================================================
        $folderSql = "SELECT a.id as application_id, a.application_number, a.status as application_status, a.current_stage,
                             a.visa_service_id, a.branch_id, a.assigned_staff_id, a.created_at as app_created_at,
                             a.priority,
                             c.id as customer_id, c.customer_code, c.full_name as customer_name, c.nationality,
                             c.current_country, c.mobile, c.whatsapp, c.email, c.gender,
                             cp.passport_number,
                             vs.name as service_name, ct.name as country_name, ct.flag_emoji,
                             b.name as branch_name,
                             u.name as assigned_staff_name,
                             COUNT(d.id) as total_docs,
                             SUM(CASE WHEN d.status = 'VERIFIED' THEN 1 ELSE 0 END) as verified_docs,
                             SUM(CASE WHEN d.status IN ('UNDER_REVIEW', 'UPLOADED') THEN 1 ELSE 0 END) as pending_docs,
                             SUM(CASE WHEN d.status = 'REJECTED' THEN 1 ELSE 0 END) as rejected_docs
                      FROM applications a
                      JOIN customers c ON a.customer_id = c.id
                      LEFT JOIN customer_passports cp ON c.id = cp.customer_id AND cp.is_primary = 1
                      LEFT JOIN visa_services vs ON a.visa_service_id = vs.id
                      LEFT JOIN countries ct ON vs.country_id = ct.id
                      LEFT JOIN branches b ON a.branch_id = b.id
                      LEFT JOIN users u ON a.assigned_staff_id = u.id
                      LEFT JOIN documents d ON d.application_id = a.id
                      WHERE 1=1";

        $folderParams = [];
        if ($userBranch > 0 && !in_array($roleSlug, ['super-admin', 'admin'], true)) {
            $folderSql .= " AND a.branch_id = ?";
            $folderParams[] = $userBranch;
        }

        if ($search !== '') {
            $folderSql .= " AND (c.full_name LIKE ? OR c.customer_code LIKE ? OR a.application_number LIKE ? OR cp.passport_number LIKE ? OR c.mobile LIKE ? OR c.email LIKE ?)";
            $term = "%{$search}%";
            $folderParams = array_merge($folderParams, [$term, $term, $term, $term, $term, $term]);
        }

        if ($serviceId > 0) {
            $folderSql .= " AND a.visa_service_id = ?";
            $folderParams[] = $serviceId;
        }

        if ($staffId > 0) {
            $folderSql .= " AND a.assigned_staff_id = ?";
            $folderParams[] = $staffId;
        }

        if ($countryId > 0) {
            $folderSql .= " AND vs.country_id = ?";
            $folderParams[] = $countryId;
        }

        if ($customerId > 0) {
            $folderSql .= " AND a.customer_id = ?";
            $folderParams[] = $customerId;
        }

        $branchIdFilter = (int)($_GET['branch_id'] ?? 0);
        if ($branchIdFilter > 0) {
            $folderSql .= " AND a.branch_id = ?";
            $folderParams[] = $branchIdFilter;
        }

        if ($dateFrom !== '') {
            $folderSql .= " AND DATE(a.created_at) >= ?";
            $folderParams[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $folderSql .= " AND DATE(a.created_at) <= ?";
            $folderParams[] = $dateTo;
        }

        $folderSql .= " GROUP BY a.id";

        if ($status !== '') {
            if ($status === 'VERIFIED') {
                $folderSql .= " HAVING verified_docs > 0";
            } elseif ($status === 'UNDER_REVIEW' || $status === 'PENDING') {
                $folderSql .= " HAVING pending_docs > 0";
            } elseif ($status === 'REJECTED') {
                $folderSql .= " HAVING rejected_docs > 0";
            }
        }

        $folderSql .= " ORDER BY a.created_at DESC";

        $folderStmt = $pdo->prepare($folderSql);
        $folderStmt->execute($folderParams);
        $allFolders = $folderStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Folder Pagination
        $folderPage = max(1, (int)($_GET['f_page'] ?? $_GET['page'] ?? 1));
        $folderPerPage = max(6, min(100, (int)($_GET['f_per_page'] ?? 12)));
        $totalFolders = count($allFolders);
        $folderTotalPages = max(1, (int)ceil($totalFolders / $folderPerPage));
        if ($folderPage > $folderTotalPages) $folderPage = $folderTotalPages;

        $folders = array_slice($allFolders, ($folderPage - 1) * $folderPerPage, $folderPerPage);

        // Batch enrich folders with photos and accurate checklist metrics (Single Source of Truth)
        if (!empty($folders)) {
            foreach ($folders as &$f) {
                $aId = (int)$f['application_id'];
                $cId = (int)$f['customer_id'];

                // Photo resolution via single source of truth
                $photoDoc = DocumentChecklistService::getApplicantProfilePhoto($aId, $cId);
                $f['photo_doc_id'] = $photoDoc ? (int)$photoDoc['id'] : null;

                // Document checklist metrics via single source of truth
                $chk = DocumentChecklistService::getChecklist($aId);
                $f['total_docs'] = count($chk['items']);
                $f['verified_docs'] = $chk['total_verified'];
                $f['pending_docs'] = $chk['total_pending'];
                $f['missing_docs'] = $chk['total_missing'];
                $f['rejected_docs'] = $chk['total_rejected'];
                $f['expired_docs'] = $chk['total_expired'];
                $f['required_docs_count'] = $chk['total_required'];
                $f['completion_percent'] = $chk['percentage'];
            }
            unset($f);
        }

        // Filter Options
        $docTypes = $pdo->query("SELECT id, name FROM document_types WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $services = $pdo->query("SELECT id, name FROM visa_services WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $staffMembers = $pdo->query("SELECT id, name FROM users WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $countries = $pdo->query("SELECT id, name, flag_emoji FROM countries ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $customers = $pdo->query("SELECT id, full_name, customer_code FROM customers ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $branches = $pdo->query("SELECT id, name, code FROM branches ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $categories = ['Identity', 'Financial', 'Employment', 'Travel', 'Academic', 'Legal', 'Medical', 'Other'];

        // Real Database Statistics
        $stats = [
            'total_folders' => (int)$pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn(),
            'total' => (int)$pdo->query("SELECT COUNT(*) FROM documents")->fetchColumn(),
            'pending_review' => (int)$pdo->query("SELECT COUNT(*) FROM documents WHERE status IN ('UNDER_REVIEW', 'UPLOADED')")->fetchColumn(),
            'verified' => (int)$pdo->query("SELECT COUNT(*) FROM documents WHERE status = 'VERIFIED'")->fetchColumn(),
            'rejected' => (int)$pdo->query("SELECT COUNT(*) FROM documents WHERE status = 'REJECTED'")->fetchColumn(),
            'expired' => (int)$pdo->query("SELECT COUNT(*) FROM documents WHERE expiry_date IS NOT NULL AND expiry_date < '{$today}'")->fetchColumn(),
            'expiring_soon' => (int)$pdo->query("SELECT COUNT(*) FROM documents WHERE expiry_date IS NOT NULL AND expiry_date >= '{$today}' AND expiry_date <= '{$in30Days}'")->fetchColumn(),
        ];

        require_once dirname(__DIR__) . '/Views/documents/index.php';
    }

    /**
     * Dedicated Applicant Document Profile Workspace (Full Responsive Page)
     */
    public function profile(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $appId = (int)($_GET['application_id'] ?? $_GET['id'] ?? 0);
        if ($appId <= 0) {
            redirect('/documents', 'Please specify a valid visa application to view the document profile workspace.', 'warning');
        }

        // 1. Fetch Application + Customer + Relational Data
        $stmt = $pdo->prepare("SELECT a.*, 
                    c.id as customer_id, c.customer_code, c.first_name, c.middle_name, c.last_name, c.full_name as customer_name,
                    c.gender, c.dob, c.nationality, c.place_of_birth, c.birth_country, c.city, c.education, c.language, c.marital_status, c.religion, c.occupation,
                    c.mobile, c.whatsapp, c.email, c.current_country, c.address, c.notes as customer_notes,
                    c.created_at as customer_created_at, c.updated_at as customer_updated_at,
                    COALESCE(cp.passport_number, a.passport_number) as passport_number,
                    COALESCE(cp.issuing_country, a.nationality, c.nationality) as passport_issuing_country,
                    cp.issue_date as passport_issue_date,
                    COALESCE(cp.expiry_date, a.passport_expiry_date) as passport_expiry_date,
                    cp.place_of_issue as passport_place_of_issue,
                    fam.father_name, fam.mother_name, fam.spouse_name,
                    nid.id_number as national_id_number, nid.id_type as national_id_type, nid.expiry_date as national_id_expiry,
                    vs.name as service_name, vs.duration as service_duration, vs.entry_type as service_entry_type,
                    vs.processing_type as service_processing_type, vs.estimated_days as service_estimated_days,
                    vs.selling_price as service_selling_price,
                    vc.name as category_name,
                    ct.name as destination_country_name, ct.flag_emoji,
                    b.name as branch_name, b.code as branch_code,
                    staff.name as assigned_staff_name, staff.email as assigned_staff_email,
                    creator.name as created_by_name
                FROM applications a
                JOIN customers c ON a.customer_id = c.id
                LEFT JOIN customer_passports cp ON cp.id = (SELECT id FROM customer_passports WHERE customer_id = c.id ORDER BY is_primary DESC, id DESC LIMIT 1)
                LEFT JOIN customer_family fam ON fam.customer_id = c.id
                LEFT JOIN customer_national_ids nid ON nid.id = (SELECT id FROM customer_national_ids WHERE customer_id = c.id ORDER BY is_primary DESC, id DESC LIMIT 1)
                LEFT JOIN visa_services vs ON a.visa_service_id = vs.id
                LEFT JOIN visa_categories vc ON vs.category_id = vc.id
                LEFT JOIN countries ct ON vs.country_id = ct.id
                LEFT JOIN branches b ON a.branch_id = b.id
                LEFT JOIN users staff ON a.assigned_staff_id = staff.id
                LEFT JOIN users creator ON a.created_by = creator.id
                WHERE a.id = ?");
        $stmt->execute([$appId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            redirect('/documents', 'Applicant or application record not found.', 'danger');
        }

        // 2. Security & Authorization: Branch and RBAC check
        $mockDoc = ['application_id' => $appId, 'customer_id' => $app['customer_id']];
        if (!self::authorizeDocumentAccess($mockDoc)) {
            http_response_code(403);
            die('Access Denied. You are not authorized to access documents for this application.');
        }

        // 3. Multi-Application Support (Requirement 42)
        $stmtMulti = $pdo->prepare("SELECT a.id, a.application_number, a.status, a.current_stage, vs.name as service_name, a.created_at
                                    FROM applications a
                                    LEFT JOIN visa_services vs ON a.visa_service_id = vs.id
                                    WHERE a.customer_id = ?
                                    ORDER BY a.id DESC");
        $stmtMulti->execute([(int)$app['customer_id']]);
        $customerApplications = $stmtMulti->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 4. Fetch All Documents for this application with version counts
        $docStmt = $pdo->prepare("SELECT d.*, 
                    dt.name as doc_type_name, dt.code as doc_type_code, dt.category, dt.requires_expiry,
                    u.name as verified_by_name,
                    uploader.name as uploaded_by_name,
                    (SELECT COUNT(*) FROM document_versions dv WHERE dv.document_id = d.id) as version_count
                FROM documents d
                JOIN document_types dt ON d.document_type_id = dt.id
                LEFT JOIN users u ON d.verified_by = u.id
                LEFT JOIN users uploader ON (d.uploaded_by_type = 'Staff' AND d.uploaded_by_id = uploader.id)
                WHERE d.application_id = ?
                ORDER BY d.created_at DESC");
        $docStmt->execute([$appId]);
        $applicationDocuments = $docStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Attach expiry analysis to each document
        foreach ($applicationDocuments as &$doc) {
            $doc['expiry_info'] = DocumentExpiryService::checkExpiry($doc['expiry_date'] ?? null);
        }
        unset($doc);

        // 5. Document Checklist & Completion (Requirement 24 - Single Source of Truth)
        $checklist = DocumentChecklistService::getChecklist($appId);

        // 6. Profile Photo Resolution (Requirement 6 & PART 2, 36)
        $photoDoc = DocumentChecklistService::getApplicantProfilePhoto($appId, (int)$app['customer_id']);

        // 7. Quick Access Documents Resolution (Requirement 14 & 15)
        $quickDocs = [
            'photo' => $photoDoc,
            'passport' => self::resolveQuickDocument($applicationDocuments, 'passport'),
            'cv' => self::resolveQuickDocument($applicationDocuments, 'cv'),
            'visa' => self::resolveQuickDocument($applicationDocuments, 'visa'),
            'national_id' => self::resolveQuickDocument($applicationDocuments, 'national_id'),
        ];

        // 8. Group all documents by category (Requirement 16)
        $allCategories = ['Identity', 'Employment', 'Academic', 'Financial', 'Travel', 'Medical', 'Legal', 'Corporate', 'Visa', 'Other'];
        $categorizedDocs = [];
        foreach ($allCategories as $cat) {
            $categorizedDocs[$cat] = [];
        }
        foreach ($applicationDocuments as $d) {
            $cat = $d['category'] ?: 'Other';
            if (!isset($categorizedDocs[$cat])) {
                $categorizedDocs[$cat] = [];
            }
            $categorizedDocs[$cat][] = $d;
        }

        // 9. Passport Validity Calculation (Requirement 13)
        $passportValidity = self::calculatePassportValidity($app['passport_expiry_date'] ?? $app['passport_expiry'] ?? null);

        // 10. Expiring Documents (Requirement 26)
        $expiringDocs = [];
        $today = date('Y-m-d');
        $in30Days = date('Y-m-d', strtotime('+30 days'));
        foreach ($applicationDocuments as $d) {
            if (!empty($d['expiry_date']) && $d['expiry_date'] >= $today && $d['expiry_date'] <= $in30Days) {
                $expiringDocs[] = $d;
            }
        }

        // 11. Document Types for Upload Modal
        $docTypes = $pdo->query("SELECT id, name, code, category, requires_expiry FROM document_types WHERE is_active = 1 ORDER BY category ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 12. Recent Activity for this Application/Documents (Requirement 48)
        $activityStmt = $pdo->prepare("SELECT al.*, u.name as user_name 
                                      FROM activity_logs al 
                                      LEFT JOIN users u ON al.user_id = u.id 
                                      WHERE (al.module = 'Applications' AND al.record_id = ?) 
                                         OR (al.module = 'Documents' AND al.record_id IN (SELECT id FROM documents WHERE application_id = ?))
                                         OR (al.description LIKE ?)
                                      ORDER BY al.id DESC 
                                      LIMIT 10");
        $appNumTerm = "%{$app['application_number']}%";
        $activityStmt->execute([$appId, $appId, $appNumTerm]);
        $recentActivity = $activityStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 13. Audit Log this visit
        AuditService::log('VIEW_DOC_WORKSPACE', 'Applications', $appId, "Viewed applicant document workspace for {$app['customer_name']} ({$app['application_number']})", [
            'application_id' => $appId,
            'customer_id' => $app['customer_id']
        ], $currentUser['id'] ?? null);

        require_once dirname(__DIR__) . '/Views/documents/profile.php';
    }

    /**
     * Download All Application Documents as a Structured Category ZIP (Requirement 30)
     */
    public function downloadAll(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $appId = (int)($_GET['application_id'] ?? $_GET['id'] ?? 0);
        if ($appId <= 0) {
            redirect('/documents', 'Invalid application ID.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT a.*, c.full_name as customer_name, c.customer_code, a.branch_id as customer_branch_id 
                               FROM applications a 
                               JOIN customers c ON a.customer_id = c.id 
                               WHERE a.id = ?");
        $stmt->execute([$appId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            redirect('/documents', 'Application not found.', 'danger');
        }

        if (!self::authorizeDocumentAccess(['application_id' => $appId, 'customer_id' => $app['customer_id']])) {
            http_response_code(403);
            die('Access Denied. You are not authorized to download files for this application.');
        }

        $docsStmt = $pdo->prepare("SELECT d.*, dt.name as doc_type_name, dt.category 
                                  FROM documents d 
                                  JOIN document_types dt ON d.document_type_id = dt.id 
                                  WHERE d.application_id = ?");
        $docsStmt->execute([$appId]);
        $docs = $docsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (empty($docs)) {
            redirect("/documents/profile?application_id={$appId}", 'No uploaded documents available for this applicant to download.', 'warning');
        }

        if (!class_exists('ZipArchive')) {
            redirect("/documents/profile?application_id={$appId}", 'Server does not support ZipArchive extension.', 'danger');
        }

        $zip = new \ZipArchive();
        $tempDir = sys_get_temp_dir();
        $zipFilename = $tempDir . DIRECTORY_SEPARATOR . 'applicant_docs_' . $appId . '_' . time() . '.zip';

        if ($zip->open($zipFilename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            redirect("/documents/profile?application_id={$appId}", 'Failed to create zip archive.', 'danger');
        }

        $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string)$app['customer_name']);
        $rootFolder = $cleanName . '_' . ($app['application_number'] ?: 'APP' . $appId);

        $addedCount = 0;
        foreach ($docs as $d) {
            $filePath = App::uploadPath($d['file_path']);
            if (!file_exists($filePath)) {
                $legacyPath = App::publicPath('uploads/documents/' . basename($d['file_path']));
                if (file_exists($legacyPath)) {
                    $filePath = $legacyPath;
                }
            }

            if (file_exists($filePath) && is_readable($filePath)) {
                $category = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string)($d['category'] ?: 'Other'));
                $safeDocTitle = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string)($d['document_title'] ?: $d['doc_type_name']));
                $ext = pathinfo((string)$d['file_name'], PATHINFO_EXTENSION);
                $inZipName = "{$rootFolder}/{$category}/{$safeDocTitle}.{$ext}";
                
                $zip->addFile($filePath, $inZipName);
                $addedCount++;
            }
        }

        $zip->close();

        if ($addedCount === 0 || !file_exists($zipFilename)) {
            if (file_exists($zipFilename)) @unlink($zipFilename);
            redirect("/documents/profile?application_id={$appId}", 'None of the document files could be retrieved from storage.', 'warning');
        }

        AuditService::log('DOWNLOAD_ZIP', 'Applications', $appId, "Downloaded ZIP bundle of {$addedCount} documents for application {$app['application_number']}", [
            'application_id' => $appId,
            'file_count' => $addedCount
        ], $currentUser['id'] ?? null);

        $downloadFilename = "Documents_{$app['application_number']}.zip";
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $downloadFilename . '"');
        header('Content-Length: ' . filesize($zipFilename));
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($zipFilename);
        @unlink($zipFilename);
        exit;
    }

    /**
     * Resolve applicant profile photograph using PHOTO_WHITE_BG preference rule (Requirement 6 & PART 2, 36)
     */
    public static function resolveApplicantPhoto(array $docs, int $customerId, \PDO $pdo, int $applicationId = 0): ?array
    {
        if ($applicationId <= 0 && !empty($docs[0]['application_id'])) {
            $applicationId = (int)$docs[0]['application_id'];
        }
        return DocumentChecklistService::getApplicantProfilePhoto($applicationId, $customerId);
    }

    /**
     * Reusable helper for applicant profile photo resolution (PART 36)
     */
    public static function getApplicantProfilePhoto(int $applicationId, int $customerId): ?array
    {
        return DocumentChecklistService::getApplicantProfilePhoto($applicationId, $customerId);
    }

    /**
     * Resolve quick-access document types: photo, passport, cv, visa, national_id (Requirement 14 & 15)
     */
    public static function resolveQuickDocument(array $docs, string $key): ?array
    {
        $matches = [];
        foreach ($docs as $d) {
            // Must have a valid file on disk
            if (!DocumentChecklistService::hasValidFile($d['file_path'] ?? null)) {
                continue;
            }

            $code = strtoupper((string)($d['doc_type_code'] ?? ''));
            $name = strtolower((string)($d['doc_type_name'] ?? ''));
            $cat = strtolower((string)($d['category'] ?? ''));

            if ($key === 'passport') {
                if ($code === 'PASSPORT_BIO' || strpos($name, 'passport bio') !== false || strpos($name, 'passport') !== false) {
                    $matches[] = $d;
                }
            } elseif ($key === 'cv') {
                if ($code === 'CV' || strpos($name, 'cv') !== false || strpos($name, 'resume') !== false || $code === 'EMPLOYMENT_LETTER' || $code === 'JOB_OFFER_CONTRACT') {
                    $matches[] = $d;
                }
            } elseif ($key === 'visa') {
                if ($code === 'VISA_COPY' || $code === 'VISA' || $code === 'RESIDENCE_PERMIT' || strpos($name, 'visa') !== false || $cat === 'visa') {
                    $matches[] = $d;
                }
            } elseif ($key === 'national_id') {
                if ($code === 'NATIONAL_ID' || strpos($name, 'national id') !== false || strpos($name, 'emirates id') !== false) {
                    $matches[] = $d;
                }
            }
        }

        if (empty($matches)) {
            return null;
        }

        // Recommended priority: VERIFIED > UNDER_REVIEW > UPLOADED > REJECTED > version DESC > id DESC
        usort($matches, function ($a, $b) {
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

            $vDiff = ((int)($b['version'] ?? 1)) <=> ((int)($a['version'] ?? 1));
            if ($vDiff !== 0) return $vDiff;

            return ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0));
        });

        return $matches[0];
    }

    /**
     * Calculate passport validity status and days remaining (Requirement 13)
     */
    public static function calculatePassportValidity(?string $expiryDate): array
    {
        if (empty($expiryDate)) {
            return [
                'status' => 'UNKNOWN',
                'label' => 'No Expiry Set',
                'badge_class' => 'bg-secondary text-white',
                'days_remaining' => null,
                'description' => 'Passport expiry date not recorded.'
            ];
        }

        $expTime = strtotime($expiryDate);
        $todayTime = strtotime(date('Y-m-d'));
        $diffSeconds = $expTime - $todayTime;
        $days = (int)round($diffSeconds / 86400);

        if ($days < 0) {
            return [
                'status' => 'EXPIRED',
                'label' => 'EXPIRED',
                'badge_class' => 'bg-danger text-white',
                'days_remaining' => $days,
                'description' => 'Expired ' . abs($days) . ' days ago. Renewal required.'
            ];
        } elseif ($days <= 180) { // Less than 6 months
            return [
                'status' => 'EXPIRING_SOON',
                'label' => 'EXPIRING SOON',
                'badge_class' => 'bg-warning text-dark',
                'days_remaining' => $days,
                'description' => 'Expires in ' . $days . ' days (< 6 months).'
            ];
        } else {
            $years = round($days / 365, 1);
            return [
                'status' => 'VALID',
                'label' => 'VALID',
                'badge_class' => 'bg-success text-white',
                'days_remaining' => $days,
                'description' => 'Valid (' . $years . ' years / ' . $days . ' days remaining).'
            ];
        }
    }

    public function exportCsv(): void
    {
        $_GET['export'] = 'csv';
        $this->index();
    }

    private function outputCsv(array $documents): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=documents_export_' . date('Y-m-d_His') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Document Title', 'Type', 'Category', 'Customer Name', 'Passport Number', 'Application #', 'Service', 'Country', 'Status', 'Expiry Date', 'Uploaded By', 'Created At']);

        foreach ($documents as $d) {
            fputcsv($output, [
                $d['id'] ?? '',
                $d['document_title'] ?: ($d['doc_type_name'] ?? 'Document'),
                $d['doc_type_name'] ?? '',
                $d['category'] ?? 'General',
                $d['customer_name'] ?? '',
                $d['passport_number'] ?? '',
                $d['application_number'] ?? '',
                $d['service_name'] ?? '',
                $d['country_name'] ?? '',
                $d['status'] ?? '',
                $d['expiry_date'] ?? 'N/A',
                $d['uploaded_by_name'] ?? ($d['uploaded_by_type'] ?? ''),
                $d['created_at'] ?? ''
            ]);
        }
        fclose($output);
        exit;
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
        if ($this->isJsonRequest()) {
            header('Content-Type: application/json');
            echo json_encode($res);
            exit;
        }
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
            $msg = 'A rejection reason is strictly mandatory when rejecting a document.';
            if ($this->isJsonRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            redirect($_SERVER['HTTP_REFERER'] ?? '/documents', $msg, 'danger');
        }

        $res = DocumentVerificationService::reject($docId, (int)$currentUser['id'], $reason, $notes);
        if ($this->isJsonRequest()) {
            header('Content-Type: application/json');
            echo json_encode($res);
            exit;
        }
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
            $msg = 'Please provide a valid replacement file.';
            if ($this->isJsonRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            redirect($_SERVER['HTTP_REFERER'] ?? '/documents', $msg, 'danger');
        }

        $res = DocumentVerificationService::uploadReplacement(
            $docId,
            $_FILES['document_file'],
            (int)$currentUser['id'],
            'Staff',
            $expiryDate,
            $notes
        );

        if ($this->isJsonRequest()) {
            header('Content-Type: application/json');
            echo json_encode($res);
            exit;
        }
        redirect($_SERVER['HTTP_REFERER'] ?? '/documents', $res['message'], $res['success'] ? 'success' : 'danger');
    }

    /**
     * Update document metadata or replace file (Unified Edit)
     */
    public function update(): void
    {
        AuthMiddleware::handle();
        $currentUser = auth_user();
        $pdo = Database::getConnection();

        $docId = (int)($_POST['document_id'] ?? 0);
        $docTitle = trim($_POST['document_title'] ?? '');
        $docTypeId = (int)($_POST['document_type_id'] ?? 0);
        $expiryDate = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        $status = trim($_POST['status'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $rejectionReason = trim($_POST['rejection_reason'] ?? '');

        if ($docId <= 0) {
            $msg = 'Invalid document record.';
            if ($this->isJsonRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            redirect($_SERVER['HTTP_REFERER'] ?? '/documents', $msg, 'danger');
        }

        $stmt = $pdo->prepare("SELECT d.*, dt.name as doc_type_name, a.id as app_id FROM documents d LEFT JOIN document_types dt ON d.document_type_id = dt.id LEFT JOIN applications a ON d.application_id = a.id WHERE d.id = ?");
        $stmt->execute([$docId]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doc) {
            $msg = 'Document not found.';
            if ($this->isJsonRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            redirect($_SERVER['HTTP_REFERER'] ?? '/documents', $msg, 'danger');
        }

        // If a new replacement file was provided, upload it as a new version
        if (!empty($_FILES['document_file']['name'])) {
            $repRes = DocumentVerificationService::uploadReplacement(
                $docId,
                $_FILES['document_file'],
                (int)$currentUser['id'],
                'Staff',
                $expiryDate,
                $notes
            );
            if (!$repRes['success']) {
                if ($this->isJsonRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode($repRes);
                    exit;
                }
                redirect($_SERVER['HTTP_REFERER'] ?? '/documents', $repRes['message'], 'danger');
            }
        }

        // Update metadata
        $fields = [];
        $params = [];

        if ($docTitle !== '') {
            $fields[] = "document_title = ?";
            $params[] = $docTitle;
        }

        if ($docTypeId > 0) {
            $fields[] = "document_type_id = ?";
            $params[] = $docTypeId;
        }

        $fields[] = "expiry_date = ?";
        $params[] = $expiryDate;

        if ($notes !== '') {
            $fields[] = "notes = ?";
            $params[] = $notes;
        }

        if (in_array($status, ['VERIFIED', 'UNDER_REVIEW', 'REJECTED'], true)) {
            $fields[] = "status = ?";
            $params[] = $status;
            if ($status === 'VERIFIED') {
                $fields[] = "verified_by = ?";
                $params[] = (int)$currentUser['id'];
                $fields[] = "verified_at = CURRENT_TIMESTAMP";
                $fields[] = "rejection_reason = NULL";
                $fields[] = "replacement_requested = 0";
            } elseif ($status === 'REJECTED') {
                $fields[] = "verified_by = ?";
                $params[] = (int)$currentUser['id'];
                $fields[] = "verified_at = CURRENT_TIMESTAMP";
                $fields[] = "rejection_reason = ?";
                $params[] = $rejectionReason ?: 'Document rejected during review';
                $fields[] = "replacement_requested = 1";
            } elseif ($status === 'UNDER_REVIEW') {
                $fields[] = "verified_at = NULL";
                $fields[] = "rejection_reason = NULL";
                $fields[] = "replacement_requested = 0";
            }
        }

        $fields[] = "updated_at = CURRENT_TIMESTAMP";
        $params[] = $docId;

        $sql = "UPDATE documents SET " . implode(', ', $fields) . " WHERE id = ?";
        $upd = $pdo->prepare($sql);
        $upd->execute($params);

        if (!empty($doc['app_id'])) {
            HealthCalculatorService::calculate((int)$doc['app_id']);
        }

        AuditService::log('UPDATE_DOC', 'Documents', $docId, "Updated document metadata for '{$doc['file_name']}' (ID #{$docId})", [
            'doc_id' => $docId,
            'title' => $docTitle ?: $doc['document_title'],
            'status' => $status ?: $doc['status']
        ], (int)$currentUser['id']);

        $msg = "Document details updated successfully.";
        if ($this->isJsonRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $msg, 'data' => ['document_id' => $docId, 'status' => $status ?: $doc['status']]]);
            exit;
        }

        redirect($_SERVER['HTTP_REFERER'] ?? '/documents', $msg, 'success');
    }

    /**
     * Reset document status back to UNDER_REVIEW
     */
    public function setUnderReview(): void
    {
        AuthMiddleware::handle();
        $currentUser = auth_user();

        $docId = (int)($_POST['document_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if ($docId <= 0) {
            $msg = 'Invalid document record.';
            if ($this->isJsonRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            redirect($_SERVER['HTTP_REFERER'] ?? '/documents', $msg, 'danger');
        }

        $res = DocumentVerificationService::setUnderReview($docId, (int)$currentUser['id'], $notes);
        if ($this->isJsonRequest()) {
            header('Content-Type: application/json');
            echo json_encode($res);
            exit;
        }
        redirect($_SERVER['HTTP_REFERER'] ?? '/documents', $res['message'], $res['success'] ? 'info' : 'danger');
    }

    /**
     * Permanently delete a document
     */
    public function delete(): void
    {
        AuthMiddleware::handle();
        $currentUser = auth_user();

        $docId = (int)($_POST['document_id'] ?? 0);
        if ($docId <= 0) {
            $msg = 'Invalid document selected for deletion.';
            if ($this->isJsonRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            redirect($_SERVER['HTTP_REFERER'] ?? '/documents', $msg, 'danger');
        }

        // Permission check
        if (!user_has_role(['super-admin', 'admin']) && !user_can('documents.manage') && !user_can('documents.delete')) {
            $msg = 'You do not have permission to delete document records.';
            if ($this->isJsonRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            redirect($_SERVER['HTTP_REFERER'] ?? '/documents', $msg, 'danger');
        }

        $res = DocumentVerificationService::delete($docId, (int)$currentUser['id']);
        if ($this->isJsonRequest()) {
            header('Content-Type: application/json');
            echo json_encode($res);
            exit;
        }
        redirect($_SERVER['HTTP_REFERER'] ?? '/documents', $res['message'], $res['success'] ? 'success' : 'danger');
    }

    private function isJsonRequest(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }

    /**
     * Verify ownership / role access for a document with strict RBAC & branch isolation
     */
    public static function authorizeDocumentAccess(array $doc): bool
    {
        if (is_authenticated()) {
            $user = auth_user();
            if (!$user) {
                return false;
            }
            $roleSlug = $user['role_slug'] ?? '';
            $staffId = (int)($user['id'] ?? 0);
            $userBranch = (int)($user['branch_id'] ?? 0);

            // 1. Super Admin & Admin have full global document authority
            if (in_array($roleSlug, ['super-admin', 'admin'], true) || (int)($user['role_id'] ?? 0) === 1) {
                return true;
            }

            // 2. Staff must possess document or application view permission
            if (!user_can('documents.view') && !user_can('applications.view') && !user_can('documents.manage')) {
                return false;
            }

            // 3. Inspect application and customer context
            $pdo = Database::getConnection();
            $docCustBranch = null;
            $docAppBranch = null;
            $assignedStaffId = null;

            if (!empty($doc['customer_id'])) {
                try {
                    $cStmt = $pdo->prepare("SELECT branch_id FROM customers WHERE id = ?");
                    $cStmt->execute([(int)$doc['customer_id']]);
                    $docCustBranch = (int)$cStmt->fetchColumn();
                } catch (\Throwable $e) {}
            }

            if (!empty($doc['application_id'])) {
                try {
                    $aStmt = $pdo->prepare("SELECT branch_id, assigned_staff_id FROM applications WHERE id = ?");
                    $aStmt->execute([(int)$doc['application_id']]);
                    $appRow = $aStmt->fetch(PDO::FETCH_ASSOC);
                    if ($appRow) {
                        $docAppBranch = (int)($appRow['branch_id'] ?? 0);
                        $assignedStaffId = (int)($appRow['assigned_staff_id'] ?? 0);
                    }
                } catch (\Throwable $e) {}
            }

            // Directly assigned officer can view document
            if ($assignedStaffId > 0 && $assignedStaffId === $staffId) {
                return true;
            }

            // Branch Manager: can view any document belonging to their branch
            if ($roleSlug === 'branch-manager') {
                if ($userBranch > 0) {
                    return ($docCustBranch === $userBranch || $docAppBranch === $userBranch);
                }
                return false;
            }

            // General Staff / Consultant: must belong to the same branch
            if ($userBranch > 0) {
                if ($docAppBranch > 0 && $docAppBranch !== $userBranch) {
                    return false;
                }
                if ($docCustBranch > 0 && $docCustBranch !== $userBranch) {
                    return false;
                }
                return true;
            }

            return false;
        }

        if (is_customer_authenticated()) {
            $customer = auth_customer();
            return !empty($customer['id']) && (int)($doc['customer_id'] ?? 0) === (int)$customer['id'];
        }

        if (is_agent_authenticated()) {
            $agent = auth_agent();
            if (!empty($doc['application_id']) && !empty($agent['id'])) {
                try {
                    $pdo = Database::getConnection();
                    $stmt = $pdo->prepare("SELECT agent_id FROM applications WHERE id = ?");
                    $stmt->execute([(int)$doc['application_id']]);
                    $appAgent = (int)$stmt->fetchColumn();
                    return $appAgent === (int)$agent['id'];
                } catch (\Throwable $e) {}
            }
            return false;
        }

        if (is_supplier_authenticated()) {
            $supplier = auth_supplier();
            if (!empty($doc['application_id']) && !empty($supplier['id'])) {
                try {
                    $pdo = Database::getConnection();
                    $stmt = $pdo->prepare("SELECT supplier_id FROM applications WHERE id = ?");
                    $stmt->execute([(int)$doc['application_id']]);
                    $appSupplier = (int)$stmt->fetchColumn();
                    return $appSupplier === (int)$supplier['id'];
                } catch (\Throwable $e) {}
            }
            return false;
        }

        return false;
    }

    /**
     * Preview / Stream Document Securely Inline
     */
    public function preview(): void
    {
        if (!is_authenticated() && !is_customer_authenticated() && !is_agent_authenticated() && !is_supplier_authenticated()) {
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

        if (!self::authorizeDocumentAccess($doc)) {
            http_response_code(403);
            die('Access Denied. You are not authorized to view this document.');
        }

        $filePath = App::uploadPath($doc['file_path']);
        if (!file_exists($filePath)) {
            $legacyPath = App::publicPath('uploads/documents/' . basename($doc['file_path']));
            if (file_exists($legacyPath)) {
                $filePath = $legacyPath;
            } else {
                http_response_code(404);
                die('File not found in storage repository.');
            }
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
        if (!is_authenticated() && !is_customer_authenticated() && !is_agent_authenticated() && !is_supplier_authenticated()) {
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

        if (!self::authorizeDocumentAccess($doc)) {
            http_response_code(403);
            die('Access Denied. You are not authorized to download this document.');
        }

        $filePath = App::uploadPath($doc['file_path']);
        if (!file_exists($filePath)) {
            $legacyPath = App::publicPath('uploads/documents/' . basename($doc['file_path']));
            if (file_exists($legacyPath)) {
                $filePath = $legacyPath;
            } else {
                http_response_code(404);
                die('File not found on server.');
            }
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
                'actionUrl' => App::url('portal/documents'),
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
     * Fetch Document Version History (JSON - Protected by RBAC)
     */
    public function history(): void
    {
        if (!is_authenticated() && !is_customer_authenticated() && !is_agent_authenticated() && !is_supplier_authenticated()) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access Denied: Please log in.']);
            exit;
        }

        $docId = (int)($_GET['id'] ?? 0);
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT d.* FROM documents d WHERE d.id = ?");
        $stmt->execute([$docId]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doc || !self::authorizeDocumentAccess($doc)) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access Denied: You do not have permission to view this document history.']);
            exit;
        }
        $isStaff = is_authenticated();
        if ($isStaff) {
            $user = auth_user();
            if (!user_can('documents.view') && !user_can('documents.manage') && !in_array($user['role_slug'] ?? '', ['super-admin', 'admin', 'branch-manager'], true)) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access Denied: Insufficient staff permissions to view document history.']);
                exit;
            }
        }

        $vStmt = $pdo->prepare("SELECT dv.*, u.name as uploader_name 
            FROM document_versions dv 
            LEFT JOIN users u ON (dv.uploaded_by_type = 'Staff' AND dv.uploaded_by_id = u.id)
            WHERE dv.document_id = ? 
            ORDER BY dv.version_number DESC");
        $vStmt->execute([$docId]);
        $versions = $vStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (!$isStaff) {
            // Strip internal staff user IDs and internal file system paths for external clients
            $versions = array_map(function ($v) {
                return [
                    'version_number' => $v['version_number'] ?? 1,
                    'file_name'      => basename((string)($v['file_name'] ?? '')),
                    'created_at'     => $v['created_at'] ?? '',
                    'status'         => $v['status'] ?? 'Active',
                    'file_size'      => $v['file_size'] ?? 0,
                ];
            }, $versions);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $versions]);
        exit;
    }
}
