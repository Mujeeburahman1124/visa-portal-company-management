<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Services\AuditService;
use App\Services\DuplicateDetectionService;
use PDO;

class CustomerController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $search = trim($_GET['search'] ?? '');
        $nationality = trim($_GET['nationality'] ?? '');

        $sql = "SELECT c.*, 
                    cp.passport_number, cp.expiry_date as passport_expiry,
                    COUNT(a.id) as total_applications,
                    SUM(CASE WHEN a.status NOT IN ('Approved', 'Completed', 'Cancelled') THEN 1 ELSE 0 END) as active_applications
                FROM customers c 
                LEFT JOIN customer_passports cp ON cp.customer_id = c.id AND cp.is_primary = 1
                LEFT JOIN applications a ON a.customer_id = c.id
                WHERE c.is_active = 1";

        $params = [];
        if ($search !== '') {
            $sql .= " AND (c.full_name LIKE ? OR c.customer_code LIKE ? OR c.mobile LIKE ? OR c.email LIKE ? OR cp.passport_number LIKE ?)";
            $term = "%{$search}%";
            $params = array_fill(0, 5, $term);
        }

        if ($nationality !== '') {
            $sql .= " AND c.nationality = ?";
            $params[] = $nationality;
        }

        $sql .= " GROUP BY c.id ORDER BY c.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $customers = $stmt->fetchAll();

        $nationalities = $pdo->query("SELECT DISTINCT nationality FROM customers WHERE is_active = 1 ORDER BY nationality ASC")->fetchAll(PDO::FETCH_COLUMN);

        require_once dirname(__DIR__) . '/Views/customers/index.php';
    }

    public function create(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $countries = $pdo->query("SELECT id, name, iso_code, flag_emoji FROM countries ORDER BY name ASC")->fetchAll();
        $docTypes = $pdo->query("SELECT id, name, code, category FROM document_types WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
        require_once dirname(__DIR__) . '/Views/customers/create.php';
    }

    public function store(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $firstName = trim($_POST['first_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $fullName = trim($firstName . ($middleName ? ' ' . $middleName : '') . ' ' . $lastName);
        $gender = trim($_POST['gender'] ?? 'Male');
        $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
        $nationality = trim($_POST['nationality'] ?? '');
        $placeOfBirth = trim($_POST['place_of_birth'] ?? '');
        $maritalStatus = trim($_POST['marital_status'] ?? 'Single');
        $occupation = trim($_POST['occupation'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? $mobile);
        $email = trim($_POST['email'] ?? '');
        $currentCountry = trim($_POST['current_country'] ?? 'United Arab Emirates');
        $address = trim($_POST['address'] ?? '');
        $religion = trim($_POST['religion'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        // Family details
        $fatherName = trim($_POST['father_name'] ?? '');
        $fatherDob = !empty($_POST['father_dob']) ? $_POST['father_dob'] : null;
        $fatherCountryOfBirth = trim($_POST['father_country_of_birth'] ?? '');
        $fatherReligion = trim($_POST['father_religion'] ?? '');

        $motherName = trim($_POST['mother_name'] ?? '');
        $motherDob = !empty($_POST['mother_dob']) ? $_POST['mother_dob'] : null;
        $motherMobile = trim($_POST['mother_mobile'] ?? '');
        $motherReligion = trim($_POST['mother_religion'] ?? '');

        // Passport details
        $passportNumber = trim($_POST['passport_number'] ?? '');
        $passportIssuingCountry = trim($_POST['passport_issuing_country'] ?? $nationality);
        $passportIssueDate = !empty($_POST['passport_issue_date']) ? $_POST['passport_issue_date'] : null;
        $passportExpiryDate = !empty($_POST['passport_expiry_date']) ? $_POST['passport_expiry_date'] : null;
        $passportPlaceOfIssue = trim($_POST['passport_place_of_issue'] ?? '');

        // National ID details
        $nationalIdNumber = trim($_POST['national_id_number'] ?? '');
        $nationalIdType = trim($_POST['national_id_type'] ?? 'Emirates ID');
        $nationalIdIssueDate = !empty($_POST['national_id_issue_date']) ? $_POST['national_id_issue_date'] : null;
        $nationalIdExpiryDate = !empty($_POST['national_id_expiry_date']) ? $_POST['national_id_expiry_date'] : null;

        // Residence details
        $residenceCountry = trim($_POST['residence_country'] ?? '');
        $residencePermitNumber = trim($_POST['residence_permit_number'] ?? '');
        $residenceExpiryDate = !empty($_POST['residence_expiry_date']) ? $_POST['residence_expiry_date'] : null;
        $residenceEmployer = trim($_POST['residence_employer'] ?? '');
        $residenceJobTitle = trim($_POST['residence_job_title'] ?? '');

        if (empty($firstName) || empty($lastName) || empty($mobile) || empty($nationality)) {
            redirect('/customers/create', 'Please complete all required customer fields.', 'danger');
        }

        // Check for duplicates
        $dupCustomer = DuplicateDetectionService::checkCustomer($email, $mobile, $fullName, $dob);
        if (!empty($dupCustomer) && empty($_POST['force_duplicate'])) {
            $msg = $dupCustomer[0]['message'] . ' To proceed anyway, please confirm.';
            redirect('/customers/create', $msg, 'warning');
        }

        if (!empty($passportNumber)) {
            $dupPassport = DuplicateDetectionService::checkPassport($passportNumber);
            if (!empty($dupPassport) && empty($_POST['force_duplicate'])) {
                redirect('/customers/create', $dupPassport[0]['message'], 'warning');
            }
        }

        // Generate Customer Code: MSC-XXXXXX
        $count = (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn() + 1;
        $customerCode = sprintf("MSC-%06d", $count);
        $passwordHash = password_hash('customer123', PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO customers (
            customer_code, first_name, middle_name, last_name, full_name, gender, dob,
            nationality, place_of_birth, marital_status, occupation, religion, mobile, whatsapp,
            email, password_hash, current_country, address, created_by, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $customerCode, $firstName, $middleName, $lastName, $fullName, $gender, $dob,
            $nationality, $placeOfBirth, $maritalStatus, $occupation, $religion, $mobile, $whatsapp,
            $email, $passwordHash, $currentCountry, $address, $currentUser['id'], $notes
        ]);

        $customerId = (int)$pdo->lastInsertId();

        // Insert Family Details into customer_family
        if (!empty($fatherName) || !empty($motherName) || !empty($fatherReligion) || !empty($motherReligion)) {
            $famStmt = $pdo->prepare("INSERT INTO customer_family (
                customer_id, father_name, father_dob, father_country_of_birth, father_nationality, father_religion,
                mother_name, mother_dob, mother_country_of_birth, mother_nationality, mother_religion, mother_mobile
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $famStmt->execute([
                $customerId,
                $fatherName, $fatherDob, $fatherCountryOfBirth, $fatherCountryOfBirth ?: $nationality, $fatherReligion,
                $motherName, $motherDob, null, $nationality, $motherReligion, $motherMobile
            ]);
        }

        // Handle File Upload Directory
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/customers/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $frontFile = null;
        if (!empty($_FILES['national_id_front']['name']) && $_FILES['national_id_front']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['national_id_front']['name'], PATHINFO_EXTENSION);
            $fileName = "nid_front_{$customerId}_" . time() . ".{$ext}";
            if (move_uploaded_file($_FILES['national_id_front']['tmp_name'], $uploadDir . $fileName)) {
                $frontFile = "/uploads/customers/{$fileName}";
            }
        }

        $backFile = null;
        if (!empty($_FILES['national_id_back']['name']) && $_FILES['national_id_back']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['national_id_back']['name'], PATHINFO_EXTENSION);
            $fileName = "nid_back_{$customerId}_" . time() . ".{$ext}";
            if (move_uploaded_file($_FILES['national_id_back']['tmp_name'], $uploadDir . $fileName)) {
                $backFile = "/uploads/customers/{$fileName}";
            }
        }

        // Insert Passport
        if (!empty($passportNumber)) {
            $passStmt = $pdo->prepare("INSERT INTO customer_passports (
                customer_id, passport_number, issuing_country, issue_date, expiry_date, place_of_issue, is_primary
            ) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $passStmt->execute([$customerId, $passportNumber, $passportIssuingCountry, $passportIssueDate, $passportExpiryDate ?: date('Y-m-d', strtotime('+5 years')), $passportPlaceOfIssue]);
        }

        // Insert National ID (Global / UAE Emirates ID)
        if (!empty($nationalIdNumber)) {
            $nidStmt = $pdo->prepare("INSERT INTO customer_national_ids (customer_id, id_number, id_type, issuing_country, issue_date, expiry_date, front_file, back_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $nidStmt->execute([$customerId, $nationalIdNumber, $nationalIdType, $currentCountry, $nationalIdIssueDate, $nationalIdExpiryDate, $frontFile, $backFile]);
        }

        // Insert Residence Details
        if (!empty($residencePermitNumber) || !empty($residenceCountry)) {
            $resStmt = $pdo->prepare("INSERT INTO customer_residences (customer_id, residence_country, permit_number, expiry_date, employer, job_title) VALUES (?, ?, ?, ?, ?, ?)");
            $resStmt->execute([$customerId, $residenceCountry ?: $currentCountry, $residencePermitNumber, $residenceExpiryDate, $residenceEmployer, $residenceJobTitle ?: $occupation]);
        }

        // Process Unlimited Multi-Document Uploads (Part 2, 3, 4)
        if (!empty($_FILES['applicant_documents']['name']) && is_array($_FILES['applicant_documents']['name'])) {
            $docTypes = $_POST['document_types'] ?? [];
            $docTitles = $_POST['document_titles'] ?? [];
            $docsUploadDir = dirname(__DIR__, 2) . '/public/uploads/documents/';
            if (!is_dir($docsUploadDir)) {
                @mkdir($docsUploadDir, 0777, true);
            }

            $docStmt = $pdo->prepare("INSERT INTO documents (
                customer_id, document_type_id, document_title, file_path, file_name, file_size, mime_type, version, status, uploaded_by_type, uploaded_by_id, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'Verified', 'Staff', ?, NOW())");

            foreach ($_FILES['applicant_documents']['name'] as $idx => $origName) {
                if (!empty($origName) && $_FILES['applicant_documents']['error'][$idx] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['applicant_documents']['tmp_name'][$idx];
                    $fileSize = (int)$_FILES['applicant_documents']['size'][$idx];
                    $mimeType = $_FILES['applicant_documents']['type'][$idx] ?? 'application/octet-stream';
                    $ext = pathinfo($origName, PATHINFO_EXTENSION);
                    $savedName = "doc_{$customerId}_" . time() . "_{$idx}." . $ext;
                    $targetPath = $docsUploadDir . $savedName;

                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $typeId = !empty($docTypes[$idx]) ? (int)$docTypes[$idx] : null;
                        $title = !empty($docTitles[$idx]) ? trim($docTitles[$idx]) : pathinfo($origName, PATHINFO_FILENAME);
                        $filePath = "/uploads/documents/{$savedName}";

                        $docStmt->execute([$customerId, $typeId, $title, $filePath, $origName, $fileSize, $mimeType, $currentUser['id'] ?? null]);
                    }
                }
            }
        }

        AuditService::log('CREATE', 'Customers', $customerId, "Registered new customer {$customerCode} - {$fullName}");

        // Dispatch Central Real-Time Notification for Applicant Registration
        try {
            \App\Services\NotificationService::trigger('applicant.registered', [
                'customer_id' => $customerId,
                'customerCode' => $customerCode,
                'applicantName' => $fullName,
                'applicantEmail' => $email,
                'applicantPhone' => $whatsapp ?: $mobile,
                'mobile' => $mobile,
                'whatsapp' => $whatsapp,
                'nationality' => $nationality,
                'loginUrl' => (string)\App\Config\Env::get('APP_URL', 'http://localhost:8000') . "/portal/login",
            ]);
        } catch (\Throwable $e) {}

        redirect("/customers/show?id={$customerId}", "Customer {$customerCode} registered successfully.", 'success');
    }

    public function show(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            redirect('/customers', 'Customer not found.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $customer = $stmt->fetch();

        if (!$customer) {
            redirect('/customers', 'Customer not found.', 'danger');
        }

        $passports = $pdo->query("SELECT * FROM customer_passports WHERE customer_id = {$id} ORDER BY is_primary DESC")->fetchAll();
        $nationalIds = $pdo->query("SELECT * FROM customer_national_ids WHERE customer_id = {$id}")->fetchAll();
        $residences = $pdo->query("SELECT * FROM customer_residences WHERE customer_id = {$id}")->fetchAll();
        $family = $pdo->query("SELECT * FROM customer_family WHERE customer_id = {$id}")->fetch(PDO::FETCH_ASSOC);
        $wallet = \App\Services\WalletService::getOrCreateWallet($id);

        // Linked Applications
        $appStmt = $pdo->prepare("SELECT a.*, vs.name as service_name, ct.name as country_name, ct.flag_emoji, u.name as staff_name 
            FROM applications a 
            JOIN visa_services vs ON a.visa_service_id = vs.id 
            JOIN countries ct ON vs.country_id = ct.id 
            LEFT JOIN users u ON a.assigned_staff_id = u.id 
            WHERE a.customer_id = ? ORDER BY a.created_at DESC");
        $appStmt->execute([$id]);
        $applications = $appStmt->fetchAll();

        // Customer Documents
        $docStmt = $pdo->prepare("SELECT d.*, dt.name as doc_type_name, a.application_number 
            FROM documents d 
            JOIN document_types dt ON d.document_type_id = dt.id 
            LEFT JOIN applications a ON d.application_id = a.id 
            WHERE d.customer_id = ? ORDER BY d.created_at DESC");
        $docStmt->execute([$id]);
        $documents = $docStmt->fetchAll();

        // Tasks & Communications
        $tasks = $pdo->query("SELECT t.*, u.name as assigned_to_name FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.customer_id = {$id} ORDER BY t.due_date ASC")->fetchAll();
        $communications = $pdo->query("SELECT c.*, u.name as staff_name FROM communications c LEFT JOIN users u ON c.staff_id = u.id WHERE c.customer_id = {$id} ORDER BY c.recorded_at DESC")->fetchAll();
        $payments = $pdo->query("SELECT p.*, a.application_number FROM payments p LEFT JOIN applications a ON p.application_id = a.id WHERE p.customer_id = {$id} ORDER BY p.payment_date DESC")->fetchAll();

        require_once dirname(__DIR__) . '/Views/customers/show.php';
    }

    /**
     * Real-time duplicate check endpoint for AJAX
     */
    public function checkDuplicate(): void
    {
        AuthMiddleware::handle();
        $email = trim($_GET['email'] ?? '');
        $mobile = trim($_GET['mobile'] ?? '');
        $name = trim($_GET['name'] ?? '');
        $dob = trim($_GET['dob'] ?? '');
        $passport = trim($_GET['passport'] ?? '');
        $excludeId = !empty($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : null;

        $results = [];
        if ($email || $mobile || $name) {
            $custDups = DuplicateDetectionService::checkCustomer($email, $mobile, $name, $dob, $excludeId);
            $results = array_merge($results, $custDups);
        }
        if ($passport) {
            $passDups = DuplicateDetectionService::checkPassport($passport, null, $excludeId);
            $results = array_merge($results, $passDups);
        }

        json_response([
            'has_duplicates' => !empty($results),
            'duplicates' => $results,
        ]);
    }
}
