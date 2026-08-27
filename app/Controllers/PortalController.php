<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\App;
use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Services\AuditService;
use App\Services\DocumentChecklistService;
use PDO;

class PortalController
{
    public function showLogin(): void
    {
        if (is_customer_authenticated()) {
            redirect('/portal/dashboard');
        }
        require_once dirname(__DIR__) . '/Views/portal/login.php';
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            redirect('/portal/login', 'Please enter your registered email and password.', 'danger');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE (LOWER(email) = LOWER(?) OR UPPER(customer_code) = UPPER(?)) AND is_active = 1");
        $stmt->execute([$email, $email]);
        $customer = $stmt->fetch();

        if ($customer && password_verify($password, $customer['password_hash'])) {
            unset($customer['password_hash']);
            $_SESSION['customer'] = $customer;
            AuditService::log('CUSTOMER_LOGIN', 'Portal', (int)$customer['id'], "Customer {$customer['full_name']} logged into applicant portal", null, null, (int)$customer['id'], 'Customer');
            redirect('/portal/dashboard', "Welcome back, {$customer['full_name']}!", 'success');
        }

        redirect('/portal/login', 'Invalid credentials. (Demo hint: password is customer123)', 'danger');
    }

    public function logout(): void
    {
        unset($_SESSION['customer']);
        redirect('/portal/login', 'You have been safely signed out from the customer portal.', 'info');
    }

    public function dashboard(): void
    {
        AuthMiddleware::handleCustomer();
        $pdo = Database::getConnection();
        $customer = auth_customer();
        $customerId = (int)$customer['id'];

        $stmt = $pdo->prepare("SELECT a.*, 
                vs.name as service_name, vs.entry_type, vs.duration, vs.validity,
                ct.name as country_name, ct.flag_emoji
            FROM applications a
            JOIN visa_services vs ON a.visa_service_id = vs.id
            JOIN countries ct ON vs.country_id = ct.id
            WHERE a.customer_id = ?
            ORDER BY a.created_at DESC");
        $stmt->execute([$customerId]);
        $applications = $stmt->fetchAll();

        // Pending document requests
        $docReqStmt = $pdo->prepare("SELECT dr.*, dt.name as doc_name, a.application_number 
            FROM document_requests dr 
            JOIN document_types dt ON dr.document_type_id = dt.id 
            JOIN applications a ON dr.application_id = a.id 
            WHERE dr.customer_id = ? AND dr.status = 'PENDING' ORDER BY dr.due_date ASC");
        $docReqStmt->execute([$customerId]);
        $pendingDocRequests = $docReqStmt->fetchAll();

        // Upcoming appointments
        $today = date('Y-m-d');
        $aptStmt = $pdo->prepare("SELECT ap.*, a.application_number 
            FROM appointments ap 
            JOIN applications a ON ap.application_id = a.id 
            WHERE a.customer_id = ? AND ap.appointment_date >= ? 
            ORDER BY ap.appointment_date ASC LIMIT 5");
        $aptStmt->execute([$customerId, $today]);
        $appointments = $aptStmt->fetchAll();

        // Unread customer notifications count
        $unreadNotifs = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE (customer_id = {$customerId} OR (recipient_type = 'Customer' AND customer_id IS NULL)) AND is_read = 0")->fetchColumn();

        require_once dirname(__DIR__) . '/Views/portal/dashboard.php';
    }

    public function documents(): void
    {
        AuthMiddleware::handleCustomer();
        $pdo = Database::getConnection();
        $customer = auth_customer();
        $customerId = (int)$customer['id'];

        $appId = (int)($_GET['app_id'] ?? 0);

        // Fetch applications belonging to this customer
        $apps = $pdo->prepare("SELECT id, application_number FROM applications WHERE customer_id = ? ORDER BY id DESC");
        $apps->execute([$customerId]);
        $customerApps = $apps->fetchAll();

        if ($appId <= 0 && !empty($customerApps)) {
            $appId = (int)$customerApps[0]['id'];
        }

        $checklist = [];
        if ($appId > 0) {
            // Verify application belongs to this customer
            $appCheck = $pdo->prepare("SELECT id, application_number FROM applications WHERE id = ? AND customer_id = ?");
            $appCheck->execute([$appId, $customerId]);
            if ($appCheck->fetch()) {
                $checklist = DocumentChecklistService::getChecklist($appId);
            }
        }

        $docTypes = $pdo->query("SELECT id, name FROM document_types ORDER BY name ASC")->fetchAll();

        require_once dirname(__DIR__) . '/Views/portal/documents.php';
    }

    public function uploadDocument(): void
    {
        AuthMiddleware::handleCustomer();
        $pdo = Database::getConnection();
        $customer = auth_customer();
        $customerId = (int)$customer['id'];

        $appId = (int)($_POST['application_id'] ?? 0);
        $docTypeId = (int)($_POST['document_type_id'] ?? 0);

        if ($appId <= 0 || $docTypeId <= 0 || empty($_FILES['document_file']['name'])) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/portal/documents', 'Please select a file to upload.', 'danger');
        }

        // Verify application belongs to this customer
        $app = $pdo->query("SELECT * FROM applications WHERE id = {$appId} AND customer_id = {$customerId}")->fetch();
        if (!$app) {
            redirect('/portal/dashboard', 'Unauthorized application access.', 'danger');
        }

        $file = $_FILES['document_file'];
        $uploadDir = App::uploadPath();
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'docx'];

        if (!in_array($ext, $allowedExtensions, true)) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/portal/documents', 'Invalid file type. Please upload PDF, JPG, PNG or DOCX.', 'danger');
        }

        $safeFileName = 'cust_doc_' . $appId . '_' . $docTypeId . '_' . time() . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $uploadDir . DIRECTORY_SEPARATOR . $safeFileName);

        $docTitle = $pdo->query("SELECT name FROM document_types WHERE id = {$docTypeId}")->fetchColumn() ?: 'Uploaded Document';

        $existing = $pdo->query("SELECT * FROM documents WHERE application_id = {$appId} AND document_type_id = {$docTypeId}")->fetch();

        if ($existing) {
            $newVersion = ((int)$existing['version']) + 1;
            if (!empty($existing['file_path'])) {
                $pdo->prepare("INSERT INTO document_versions (document_id, file_path, file_name, file_size, version_number, uploaded_by_type) VALUES (?, ?, ?, ?, ?, 'Customer')")
                    ->execute([$existing['id'], $existing['file_path'], $existing['file_name'], $existing['file_size'], $existing['version']]);
            }

            $updateStmt = $pdo->prepare("UPDATE documents SET 
                file_path = ?, file_name = ?, file_size = ?, mime_type = ?, version = ?, 
                status = 'UNDER_REVIEW', uploaded_by_type = 'Customer', rejection_reason = NULL, replacement_requested = 0, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?");
            $updateStmt->execute([$safeFileName, $file['name'], $file['size'], $file['type'], $newVersion, $existing['id']]);
        } else {
            $insertStmt = $pdo->prepare("INSERT INTO documents (
                application_id, customer_id, document_type_id, document_title, file_path, file_name,
                file_size, mime_type, version, status, uploaded_by_type
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'UNDER_REVIEW', 'Customer')");
            $insertStmt->execute([$appId, $customerId, $docTypeId, $docTitle, $safeFileName, $file['name'], $file['size'], $file['type']]);
        }

        // Fulfill document request if any
        $pdo->prepare("UPDATE document_requests SET status = 'FULFILLED', fulfilled_at = CURRENT_TIMESTAMP WHERE application_id = ? AND document_type_id = ?")->execute([$appId, $docTypeId]);

        // Staff notification
        $pdo->prepare("INSERT INTO notifications (user_id, recipient_type, title, message, link, notification_type, severity) VALUES (?, 'Staff', 'Customer Uploaded Document', ?, ?, 'Document Upload', 'info')")
            ->execute([$app['assigned_staff_id'] ?: 1, "Customer {$customer['full_name']} uploaded {$docTitle} for {$app['application_number']}", "/applications/show?id={$appId}#documents"]);

        AuditService::log('CUSTOMER_UPLOAD', 'Documents', $appId, "Customer uploaded '{$docTitle}' for {$app['application_number']}", null, null, $customerId, 'Customer');

        redirect($_SERVER['HTTP_REFERER'] ?? '/portal/documents', "Document '{$docTitle}' submitted for verification.", 'success');
    }

    public function appointments(): void
    {
        AuthMiddleware::handleCustomer();
        $pdo = Database::getConnection();
        $customer = auth_customer();
        $customerId = (int)$customer['id'];

        $stmt = $pdo->prepare("SELECT ap.*, a.application_number, vs.name as service_name, ct.name as country_name, ct.flag_emoji 
            FROM appointments ap 
            JOIN applications a ON ap.application_id = a.id 
            JOIN visa_services vs ON a.visa_service_id = vs.id 
            JOIN countries ct ON vs.country_id = ct.id 
            WHERE a.customer_id = ? 
            ORDER BY ap.appointment_date DESC");
        $stmt->execute([$customerId]);
        $appointments = $stmt->fetchAll();

        require_once dirname(__DIR__) . '/Views/portal/appointments.php';
    }

    public function notifications(): void
    {
        AuthMiddleware::handleCustomer();
        $pdo = Database::getConnection();
        $customer = auth_customer();
        $customerId = (int)$customer['id'];

        // Mark read if requested
        if (isset($_GET['mark_all_read'])) {
            $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = CURRENT_TIMESTAMP WHERE customer_id = ? OR recipient_type = 'Customer'")->execute([$customerId]);
            redirect('/portal/notifications', 'All notifications marked as read.', 'success');
        }

        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE customer_id = ? OR recipient_type = 'Customer' ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$customerId]);
        $notifications = $stmt->fetchAll();

        require_once dirname(__DIR__) . '/Views/portal/notifications.php';
    }

    public function invoices(): void
    {
        AuthMiddleware::handleCustomer();
        $pdo = Database::getConnection();
        $customer = auth_customer();
        $customerId = (int)$customer['id'];

        $stmt = $pdo->prepare("SELECT p.*, a.application_number, vs.name as service_name, ct.name as country_name 
            FROM payments p 
            JOIN applications a ON p.application_id = a.id 
            JOIN visa_services vs ON a.visa_service_id = vs.id 
            JOIN countries ct ON vs.country_id = ct.id 
            WHERE p.customer_id = ? 
            ORDER BY p.payment_date DESC");
        $stmt->execute([$customerId]);
        $invoices = $stmt->fetchAll();

        $settingsRaw = $pdo->query("SELECT * FROM system_settings")->fetchAll();
        $settings = [];
        foreach ($settingsRaw as $s) {
            $settings[$s['setting_key']] = $s['setting_value'];
        }

        require_once dirname(__DIR__) . '/Views/portal/invoices.php';
    }

    public function viewInvoice(): void
    {
        AuthMiddleware::handleCustomer();
        $pdo = Database::getConnection();
        $customer = auth_customer();
        $customerId = (int)$customer['id'];

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            redirect('/portal/invoices', 'Invalid invoice reference.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT p.*, a.application_number, a.created_at as application_date, vs.name as service_name, vs.selling_price, ct.name as country_name, ct.flag_emoji 
            FROM payments p 
            JOIN applications a ON p.application_id = a.id 
            JOIN visa_services vs ON a.visa_service_id = vs.id 
            JOIN countries ct ON vs.country_id = ct.id 
            WHERE p.id = ? AND p.customer_id = ?");
        $stmt->execute([$id, $customerId]);
        $invoice = $stmt->fetch();

        if (!$invoice) {
            redirect('/portal/invoices', 'Invoice not found or access unauthorized.', 'danger');
        }

        require_once dirname(__DIR__) . '/Views/portal/invoice_view.php';
    }

    public function support(): void
    {
        AuthMiddleware::handleCustomer();
        $pdo = Database::getConnection();
        $customer = auth_customer();
        $customerId = (int)$customer['id'];

        $settingsRaw = $pdo->query("SELECT * FROM system_settings")->fetchAll();
        $settings = [];
        foreach ($settingsRaw as $s) {
            $settings[$s['setting_key']] = $s['setting_value'];
        }

        // Recent communications
        $commStmt = $pdo->prepare("SELECT * FROM communications WHERE customer_id = ? ORDER BY recorded_at DESC LIMIT 10");
        $commStmt->execute([$customerId]);
        $messages = $commStmt->fetchAll();

        require_once dirname(__DIR__) . '/Views/portal/support.php';
    }

    public function submitInquiry(): void
    {
        AuthMiddleware::handleCustomer();
        $pdo = Database::getConnection();
        $customer = auth_customer();
        $customerId = (int)$customer['id'];

        $subject = trim($_POST['subject'] ?? 'Customer Inquiry');
        $message = trim($_POST['message'] ?? '');
        $appId = !empty($_POST['application_id']) ? (int)$_POST['application_id'] : null;

        if (empty($message)) {
            redirect('/portal/support', 'Please enter your message before sending.', 'danger');
        }

        $stmt = $pdo->prepare("INSERT INTO communications (application_id, customer_id, channel, direction, subject, message, contact_person) 
            VALUES (?, ?, 'Portal Message', 'Inbound', ?, ?, ?)");
        $stmt->execute([$appId, $customerId, $subject, $message, $customer['full_name']]);

        // Staff notification
        $pdo->prepare("INSERT INTO notifications (recipient_type, title, message, link, notification_type, severity) VALUES ('Staff', 'New Customer Inquiry', ?, '/customers', 'Support', 'info')")
            ->execute(["Applicant {$customer['full_name']} sent a message: {$subject}"]);

        AuditService::log('CUSTOMER_INQUIRY', 'Communications', $customerId, "Customer {$customer['full_name']} submitted support inquiry: {$subject}", null, null, $customerId, 'Customer');

        redirect('/portal/support', 'Your inquiry has been submitted. Our visa operations officer will respond promptly.', 'success');
    }

    public function trackPublic(): void
    {
        $pdo = Database::getConnection();
        $appNumber = trim($_GET['ref'] ?? '');
        $passport = trim($_GET['passport'] ?? '');

        $application = null;
        $checklist = null;
        $error = null;

        if (!empty($appNumber) && !empty($passport)) {
            $stmt = $pdo->prepare("SELECT a.id, a.application_number, a.application_date, a.status, a.current_stage, a.expected_completion_date, a.next_action,
                    c.full_name as customer_name, c.customer_code,
                    vs.name as service_name, vs.entry_type, vs.duration,
                    ct.name as country_name, ct.flag_emoji,
                    a.passport_number
                FROM applications a
                JOIN customers c ON a.customer_id = c.id
                JOIN visa_services vs ON a.visa_service_id = vs.id
                JOIN countries ct ON vs.country_id = ct.id
                WHERE UPPER(TRIM(a.application_number)) = UPPER(TRIM(?))
                AND (UPPER(TRIM(a.passport_number)) = UPPER(TRIM(?)) OR UPPER(TRIM(c.customer_code)) = UPPER(TRIM(?)))");
            $stmt->execute([$appNumber, $passport, $passport]);
            $application = $stmt->fetch();

            if ($application) {
                // Mask passport number for security in public tracker (e.g. Z*****19)
                $rawPassport = $application['passport_number'] ?: '';
                if (strlen($rawPassport) > 3) {
                    $application['masked_passport'] = substr($rawPassport, 0, 1) . str_repeat('*', max(3, strlen($rawPassport) - 3)) . substr($rawPassport, -2);
                } else {
                    $application['masked_passport'] = '***';
                }

                // Mask applicant name (e.g. Rahul S***)
                $parts = explode(' ', $application['customer_name']);
                if (count($parts) > 1) {
                    $application['masked_name'] = $parts[0] . ' ' . substr($parts[1], 0, 1) . '***';
                } else {
                    $application['masked_name'] = substr($application['customer_name'], 0, 2) . '***';
                }

                $checklist = DocumentChecklistService::getChecklist((int)$application['id']);
            } else {
                $error = 'No active application matched the provided Reference Number and Passport Number.';
            }
        }

        require_once dirname(__DIR__) . '/Views/portal/track.php';
    }

    public function wallet(): void
    {
        AuthMiddleware::handleCustomer();
        $customer = auth_customer();
        $customerId = (int)$customer['id'];

        $wallet = \App\Services\WalletService::getOrCreateWallet($customerId);
        $transactions = \App\Services\WalletService::getTransactions($customerId, 50);

        $pageTitle = 'My Wallet & Pre-funded Balance — VISA TRACK';
        $flash = get_flash();
        require_once dirname(__DIR__) . '/Views/portal/wallet.php';
    }

    public function walletDeposit(): void
    {
        AuthMiddleware::handleCustomer();
        $customer = auth_customer();
        $customerId = (int)$customer['id'];
        $amount = (float)($_POST['amount'] ?? 0);
        $method = trim($_POST['payment_method'] ?? 'Bank Transfer');
        $txnRef = trim($_POST['transaction_reference'] ?? '');

        if ($amount <= 0) {
            redirect('/portal/wallet', 'Deposit amount must be greater than zero.', 'danger');
        }

        try {
            $desc = "Customer self-funded advance deposit via {$method}" . (!empty($txnRef) ? " (Ref: {$txnRef})" : '');
            \App\Services\WalletService::credit($customerId, $amount, $desc, null, null, null);
            redirect('/portal/wallet', "Successfully credited " . format_currency($amount) . " to your wallet!", 'success');
        } catch (\Throwable $e) {
            redirect('/portal/wallet', 'Deposit failed: ' . $e->getMessage(), 'danger');
        }
    }
}
