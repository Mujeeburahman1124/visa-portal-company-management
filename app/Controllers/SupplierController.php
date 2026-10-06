<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Core\App;
use App\Services\AuditService;
use App\Services\WalletService;
use PDO;

class SupplierController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'accounts', 'branch-manager']);
        $pdo = Database::getConnection();

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');

        $sql = "SELECT s.*,
            COUNT(DISTINCT a.id) as total_applications,
            COALESCE((SELECT SUM(sp.payable_amount) FROM supplier_payments sp WHERE sp.supplier_id = s.id), 0) as total_payables,
            COALESCE((SELECT SUM(sp.paid_amount) FROM supplier_payments sp WHERE sp.supplier_id = s.id), 0) as total_paid
            FROM suppliers s 
            LEFT JOIN applications a ON a.supplier_id = s.id 
            WHERE 1=1";

        $params = [];
        if ($search !== '') {
            $sql .= " AND (s.company_name LIKE ? OR s.supplier_code LIKE ? OR s.contact_person LIKE ? OR s.email LIKE ? OR s.country LIKE ?)";
            $term = "%{$search}%";
            $params = array_fill(0, 5, $term);
        }
        if ($status === 'active') {
            $sql .= " AND s.is_active = 1";
        } elseif ($status === 'inactive') {
            $sql .= " AND s.is_active = 0";
        }

        $sql .= " GROUP BY s.id ORDER BY s.is_active DESC, s.company_name ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate Global Aggregates
        $totalPayableAll = array_reduce($suppliers, fn($sum, $s) => $sum + (float)$s['total_payables'], 0.0);
        $totalPaidAll = array_reduce($suppliers, fn($sum, $s) => $sum + (float)$s['total_paid'], 0.0);
        $totalOutstandingAll = max(0.00, $totalPayableAll - $totalPaidAll);

        // Fetch applications for dropdown linking
        $applications = $pdo->query("SELECT a.id, a.application_number, a.passport_number, c.full_name as customer_name, c.customer_code, vs.name as service_name
            FROM applications a
            JOIN customers c ON a.customer_id = c.id
            JOIN visa_services vs ON a.visa_service_id = vs.id
            ORDER BY a.id DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch recent supplier payments with application & applicant traceability
        $recentPaymentsStmt = $pdo->query("SELECT sp.*, s.company_name, s.supplier_code,
                   a.application_number, a.passport_number,
                   c.full_name as applicant_name, c.customer_code,
                   u.name as created_by_name
            FROM supplier_payments sp
            JOIN suppliers s ON sp.supplier_id = s.id
            LEFT JOIN applications a ON sp.application_id = a.id
            LEFT JOIN customers c ON a.customer_id = c.id
            LEFT JOIN users u ON sp.created_by = u.id
            ORDER BY sp.payment_date DESC, sp.id DESC LIMIT 20");
        $recentPayments = $recentPaymentsStmt ? $recentPaymentsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        require_once dirname(__DIR__) . '/Views/suppliers/index.php';
    }

    public function payments(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'accounts', 'branch-manager', 'visa-manager']);
        $pdo = Database::getConnection();

        $search = trim($_GET['search'] ?? '');
        $supplierId = (int)($_GET['supplier_id'] ?? 0);
        $paymentMethod = trim($_GET['payment_method'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');

        $sql = "SELECT sp.*, 
                       s.company_name, s.supplier_code,
                       a.application_number, a.passport_number,
                       c.full_name as applicant_name, c.customer_code,
                       u.name as created_by_name
                FROM supplier_payments sp
                JOIN suppliers s ON sp.supplier_id = s.id
                LEFT JOIN applications a ON sp.application_id = a.id
                LEFT JOIN customers c ON a.customer_id = c.id
                LEFT JOIN users u ON sp.created_by = u.id
                WHERE 1=1";

        $params = [];
        if ($search !== '') {
            $sql .= " AND (sp.payment_reference LIKE ? OR sp.supplier_invoice_ref LIKE ? OR sp.transaction_reference LIKE ? OR s.company_name LIKE ? OR a.application_number LIKE ? OR c.full_name LIKE ?)";
            $term = "%{$search}%";
            $params = array_fill(0, 6, $term);
        }
        if ($supplierId > 0) {
            $sql .= " AND sp.supplier_id = ?";
            $params[] = $supplierId;
        }
        if ($paymentMethod !== '') {
            $sql .= " AND sp.payment_method = ?";
            $params[] = $paymentMethod;
        }
        if ($status !== '') {
            $sql .= " AND sp.payment_status = ?";
            $params[] = $status;
        }
        if (!empty($dateFrom)) {
            $sql .= " AND sp.payment_date >= ?";
            $params[] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $sql .= " AND sp.payment_date <= ?";
            $params[] = $dateTo;
        }

        $sql .= " ORDER BY sp.payment_date DESC, sp.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $suppliers = $pdo->query("SELECT id, company_name, supplier_code FROM suppliers WHERE is_active = 1 ORDER BY company_name ASC")->fetchAll(PDO::FETCH_ASSOC);

        require_once dirname(__DIR__) . '/Views/suppliers/payments.php';
    }

    public function store(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'accounts']);
        $pdo = Database::getConnection();

        $code = strtoupper(trim($_POST['supplier_code'] ?? ''));
        $name = trim($_POST['company_name'] ?? '');
        $contact = trim($_POST['contact_person'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $services = trim($_POST['services_provided'] ?? '');
        $bankDetails = trim($_POST['bank_details'] ?? '');

        if (empty($code) || empty($name)) {
            redirect('/suppliers', 'Supplier code and company name are required.', 'danger');
        }

        // Check duplicate code
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM suppliers WHERE supplier_code = ?");
        $stmt->execute([$code]);
        if ((int)$stmt->fetchColumn() > 0) {
            redirect('/suppliers', "Supplier code '{$code}' already exists.", 'danger');
        }

        $placeholderPassword = bin2hex(random_bytes(32));
        $passwordHash = password_hash($placeholderPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO suppliers (
            supplier_code, company_name, contact_person, email, mobile, whatsapp, country, address, services_provided, bank_details, password_hash, portal_enabled
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$code, $name, $contact, $email, $mobile, $whatsapp, $country, $address, $services, $bankDetails, $passwordHash]);
        $supplierId = (int)$pdo->lastInsertId();

        // Generate Activation Token for Supplier to Set Password Securely via Link
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+72 hours'));

        try {
            $pdo->prepare("INSERT INTO portal_activation_tokens (portal_type, entity_id, entity_email, customer_id, token, token_hash, expires_at, is_used, created_at) VALUES ('supplier', ?, ?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP)")
                ->execute([$supplierId, $email, $supplierId, $rawToken, $tokenHash, $expiresAt]);
        } catch (\Throwable $eTok) {
            try {
                $pdo->prepare("INSERT INTO portal_activation_tokens (portal_type, entity_id, entity_email, token, expires_at, created_at) VALUES ('supplier', ?, ?, ?, ?, CURRENT_TIMESTAMP)")
                    ->execute([$supplierId, $email, $rawToken, $expiresAt]);
            } catch (\Throwable $eTok2) {}
        }

        $activationLink = \App\Config\App::url("supplier/activate?token={$rawToken}");

        // Dispatch Welcome Onboarding Email to Supplier with Password Setup Link
        if (!empty($email)) {
            try {
                $subject = "Welcome to " . \App\Config\App::COMPANY_NAME . " — Set Your Supplier Portal Password";
                $bodyHtml = "
                    <p>Dear <strong>" . htmlspecialchars($contact ?: $name) . "</strong>,</p>
                    <p>Welcome to <strong>" . \App\Config\App::COMPANY_NAME . "</strong>. Your supplier vendor account has been registered in our portal.</p>
                    <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; margin: 20px 0;'>
                        <h4 style='margin-top: 0; color: #1e3a8a;'>Set Your Password &amp; Activate Supplier Portal</h4>
                        <p style='margin: 6px 0;'><strong>Supplier Code:</strong> <span style='font-family: monospace; font-weight: bold;'>{$code}</span></p>
                        <p style='margin: 6px 0;'><strong>Company / Vendor:</strong> " . htmlspecialchars($name) . "</p>
                        <p style='margin: 6px 0;'><strong>Login Email:</strong> {$email}</p>
                        <p style='margin-top: 15px;'>Please click the button below to set your password and activate your vendor portal access:</p>
                        <p style='text-align: center; margin: 20px 0;'>
                            <a href='{$activationLink}' style='background: #0284c7; color: #ffffff; padding: 12px 28px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block;'>Set Password &amp; Activate &rarr;</a>
                        </p>
                        <p style='color: #64748b; font-size: 13px;'>Or copy and paste this link:<br><a href='{$activationLink}'>{$activationLink}</a></p>
                    </div>
                    <p style='color: #64748b; font-size: 0.85em;'>This activation link is single-use and will expire in 72 hours.</p>
                ";

                \App\Services\EmailService::send([
                    'to' => $email,
                    'name' => $contact ?: $name,
                    'subject' => $subject,
                    'bodyHtml' => $bodyHtml,
                    'data' => [
                        'supplier_name' => $name,
                        'supplier_code' => $code,
                        'email' => $email,
                        'activation_link' => $activationLink,
                    ]
                ]);

                // Record Notification Log
                try {
                    $pdo->prepare("INSERT INTO notification_logs (event_type, recipient_type, recipient_id, recipient_name, recipient_email, channel, template_name, subject, content_preview, status, sent_at) VALUES ('supplier.registered', 'Supplier', ?, ?, ?, 'Email', 'supplier_activation_email', ?, ?, 'Sent', CURRENT_TIMESTAMP)")
                        ->execute([$supplierId, $name, $email, $subject, "Supplier welcome activation link {$activationLink}"]);
                } catch (\Throwable $eLog) {}
            } catch (\Throwable $e) {}
        }

        AuditService::log('CREATE_SUPPLIER', 'Suppliers', $supplierId, "Created supplier {$name} ({$code}) with activation link");

        redirect('/suppliers', "Supplier '{$name}' created! An activation link has been sent to {$email}. <a href='{$activationLink}' target='_blank' class='fw-bold text-decoration-underline'>Click here to activate now</a>", 'success');
    }

    public function update(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'accounts']);
        $pdo = Database::getConnection();

        $id = (int)($_POST['id'] ?? 0);
        $code = strtoupper(trim($_POST['supplier_code'] ?? ''));
        $name = trim($_POST['company_name'] ?? '');
        $contact = trim($_POST['contact_person'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $services = trim($_POST['services_provided'] ?? '');

        if ($id <= 0 || empty($name) || empty($code)) {
            redirect('/suppliers', 'Required supplier fields are missing.', 'danger');
        }

        // Auto-ensure services_provided column exists in suppliers table
        try {
            $stmt = $pdo->prepare("UPDATE suppliers SET 
                supplier_code = ?, company_name = ?, contact_person = ?, email = ?, mobile = ?, country = ?, address = ?, services_provided = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?");
            $stmt->execute([$code, $name, $contact, $email, $mobile, $country, $address, $services, $id]);
        } catch (\PDOException $ex) {
            // If unknown column error, dynamically add missing columns and retry
            if (str_contains($ex->getMessage(), 'Unknown column') || str_contains($ex->getMessage(), 'no such column')) {
                $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
                $textType = ($driver === 'mysql') ? 'VARCHAR(255) NULL' : 'TEXT NULL';
                try { $pdo->exec("ALTER TABLE suppliers ADD COLUMN services_provided {$textType}"); } catch (\Throwable $e) {}
                try { $pdo->exec("ALTER TABLE suppliers ADD COLUMN country {$textType}"); } catch (\Throwable $e) {}
                try { $pdo->exec("ALTER TABLE suppliers ADD COLUMN address {$textType}"); } catch (\Throwable $e) {}
                try { $pdo->exec("ALTER TABLE suppliers ADD COLUMN contact_person {$textType}"); } catch (\Throwable $e) {}
                try { $pdo->exec("ALTER TABLE suppliers ADD COLUMN mobile {$textType}"); } catch (\Throwable $e) {}
                
                $stmt = $pdo->prepare("UPDATE suppliers SET 
                    supplier_code = ?, company_name = ?, contact_person = ?, email = ?, mobile = ?, country = ?, address = ?, services_provided = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?");
                $stmt->execute([$code, $name, $contact, $email, $mobile, $country, $address, $services, $id]);
            } else {
                throw $ex;
            }
        }

        AuditService::log('UPDATE_SUPPLIER', 'Suppliers', $id, "Updated supplier details for {$name}");

        redirect('/suppliers', "Supplier '{$name}' updated successfully.", 'success');
    }


    public function pay(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'accounts']);
        $pdo = Database::getConnection();

        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        $applicationId = (int)($_POST['application_id'] ?? 0);
        $payableAmount = (float)($_POST['payable_amount'] ?? 0.00);
        $paidAmount = (float)($_POST['paid_amount'] ?? $_POST['amount'] ?? 0.00);
        $currency = strtoupper(trim($_POST['currency'] ?? 'AED'));
        $date = trim($_POST['payment_date'] ?? date('Y-m-d'));
        $method = trim($_POST['payment_method'] ?? 'Bank Transfer');
        $invoiceRef = trim($_POST['supplier_invoice_ref'] ?? ('SUP-INV-' . date('Ymd') . '-' . rand(100, 999)));
        $ref = trim($_POST['transaction_reference'] ?? ('TXN-SPAY-' . rand(100000, 999999)));
        $notes = trim($_POST['notes'] ?? '');
        $status = trim($_POST['payment_status'] ?? 'Completed');
        $userId = (int)($_SESSION['user']['id'] ?? 1);

        if ($supplierId <= 0 || ($payableAmount <= 0 && $paidAmount <= 0)) {
            redirect('/suppliers', 'Supplier and valid payment amount are required.', 'danger');
        }

        if ($payableAmount <= 0) {
            $payableAmount = $paidAmount;
        }

        // Ensure receipt_file column exists in supplier_payments
        try {
            $pdo->exec("ALTER TABLE supplier_payments ADD COLUMN receipt_file VARCHAR(255) NULL");
        } catch (\Throwable $e) {}

        // Mandatory Slip / Disbursement Receipt Attachment (Policy requirement)
        $receiptFile = null;
        $hasReceiptUpload = isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK;

        if (!$hasReceiptUpload) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/suppliers', 'Payment rejected: An official disbursement receipt slip, bank transfer voucher, or cheque voucher attachment is mandatory for all supplier payments.', 'danger');
        }

        $file = $_FILES['receipt_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'docx'];
        if (!in_array($ext, $allowed, true)) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/suppliers', 'Invalid receipt file format. Allowed formats: PDF, JPG, PNG, DOCX.', 'danger');
        }
        if ($file['size'] > 15 * 1024 * 1024) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/suppliers', 'Receipt attachment file exceeds 15MB size limit.', 'danger');
        }

        $uploadDir = App::basePath('storage' . DIRECTORY_SEPARATOR . 'receipts');
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        $safeFileName = 'sup_slip_' . $supplierId . '_' . time() . '_' . substr(bin2hex(random_bytes(4)), 0, 6) . '.' . $ext;
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $safeFileName;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/suppliers', 'Failed to store receipt attachment file. Please try again.', 'danger');
        }
        $receiptFile = 'storage/receipts/' . $safeFileName;

        $payRef = 'SPAY-' . date('Ymd') . '-' . rand(1000, 9999);

        // If paid via Supplier Wallet, deduct wallet balance
        if ($method === 'Supplier Wallet') {
            try {
                WalletService::debitSupplier(
                    $supplierId,
                    $paidAmount,
                    "Disbursement payment (Ref: {$payRef}, Invoice: {$invoiceRef})" . ($notes ? " - {$notes}" : ''),
                    $userId,
                    $currency,
                    $paidAmount,
                    1.0,
                    'Supplier Wallet',
                    $ref,
                    $paidAmount,
                    $applicationId
                );
            } catch (\Throwable $e) {
                redirect($_SERVER['HTTP_REFERER'] ?? '/suppliers', 'Supplier Wallet deduction failed: ' . $e->getMessage(), 'danger');
            }
        }

        if ($applicationId <= 0) {
            $applicationId = null;
        } else {
            // Verify application actually exists to prevent FK constraint failure
            $appCheck = $pdo->prepare("SELECT id FROM applications WHERE id = ?");
            $appCheck->execute([$applicationId]);
            if (!$appCheck->fetch()) {
                $applicationId = null;
            }
        }

        // Ensure supplier_payments allows NULL application_id for general disbursements
        try {
            $pdo->exec("ALTER TABLE supplier_payments MODIFY COLUMN application_id INT NULL DEFAULT NULL");
        } catch (\Throwable $e) {}

        // Check if there is an existing pending payable for this application
        $existingPayable = null;
        if ($applicationId) {
            $epStmt = $pdo->prepare("SELECT * FROM supplier_payments WHERE application_id = ? AND supplier_id = ? AND payable_amount > paid_amount ORDER BY id ASC LIMIT 1");
            $epStmt->execute([$applicationId, $supplierId]);
            $existingPayable = $epStmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($existingPayable) {
            $newPaid = (float)$existingPayable['paid_amount'] + $paidAmount;
            $newStatus = ($newPaid >= (float)$existingPayable['payable_amount']) ? 'Completed' : 'Partial';
            $combinedNotes = trim(($existingPayable['notes'] ?? '') . ($notes ? " | {$notes}" : ''));
            
            $upStmt = $pdo->prepare("UPDATE supplier_payments SET 
                paid_amount = ?, 
                payment_status = ?, 
                payment_method = ?, 
                transaction_reference = ?, 
                supplier_invoice_ref = ?,
                payment_date = ?, 
                notes = ?,
                receipt_file = COALESCE(?, receipt_file)
                WHERE id = ?");
            $upStmt->execute([
                $newPaid, 
                $newStatus, 
                $method, 
                $ref, 
                $invoiceRef, 
                $date, 
                $combinedNotes, 
                $receiptFile,
                $existingPayable['id']
            ]);
            $payRef = $existingPayable['payment_reference'];
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO supplier_payments (
                    payment_reference, supplier_id, application_id, payable_amount, paid_amount, currency, supplier_invoice_ref, payment_date, payment_method, transaction_reference, payment_status, notes, created_by, receipt_file
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$payRef, $supplierId, $applicationId, $payableAmount, $paidAmount, $currency, $invoiceRef, $date, $method, $ref, $status, $notes, $userId, $receiptFile]);
            } catch (\PDOException $exPay) {
                if (str_contains($exPay->getMessage(), 'application_id') || str_contains($exPay->getMessage(), 'cannot be null')) {
                    try { $pdo->exec("ALTER TABLE supplier_payments MODIFY COLUMN application_id INT NULL DEFAULT NULL"); } catch (\Throwable $e) {}
                    $stmt = $pdo->prepare("INSERT INTO supplier_payments (
                        payment_reference, supplier_id, application_id, payable_amount, paid_amount, currency, supplier_invoice_ref, payment_date, payment_method, transaction_reference, payment_status, notes, created_by, receipt_file
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$payRef, $supplierId, $applicationId, $payableAmount, $paidAmount, $currency, $invoiceRef, $date, $method, $ref, $status, $notes, $userId, $receiptFile]);
                } else {
                    throw $exPay;
                }
            }
        }

        AuditService::log('SUPPLIER_PAYMENT', 'Suppliers', $supplierId, "Recorded payment of {$currency} " . number_format($paidAmount, 2) . " (Ref: {$payRef}, Invoice: {$invoiceRef})");

        redirect($_SERVER['HTTP_REFERER'] ?? '/suppliers', "Payment of {$currency} " . number_format($paidAmount, 2) . " recorded successfully.", 'success');
    }

    public function delete(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $id = (int)($_POST['id'] ?? $_POST['supplier_id'] ?? 0);
        if ($id <= 0) {
            redirect('/suppliers', 'Invalid supplier identifier.', 'danger');
        }

        $appCount = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE supplier_id = {$id}")->fetchColumn();
        if ($appCount > 0) {
            redirect('/suppliers', "Cannot delete supplier with {$appCount} active visa applications. Deactivate portal access instead.", 'warning');
        }

        $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
        $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$supplier) {
            redirect('/suppliers', 'Supplier not found.', 'danger');
        }

        $pdo->prepare("DELETE FROM supplier_services WHERE supplier_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM supplier_payments WHERE supplier_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM suppliers WHERE id = ?")->execute([$id]);

        AuditService::log('DELETE_SUPPLIER', 'Suppliers', $id, "Deleted supplier {$supplier['company_name']} ({$supplier['supplier_code']})");

        redirect('/suppliers', "Supplier '{$supplier['company_name']}' deleted successfully.", 'success');
    }

    public function resetPassword(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'accounts']);
        $pdo = Database::getConnection();

        $id = (int)($_POST['supplier_id'] ?? $_POST['id'] ?? 0);
        $newPassword = !empty($_POST['new_password']) ? trim($_POST['new_password']) : \App\Services\PasswordGeneratorService::generate(10, 'SUP@');

        if ($id <= 0) {
            redirect('/suppliers', 'Supplier identifier is missing.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT id, company_name, contact_person, email, supplier_code FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
        $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$supplier) {
            redirect('/suppliers', 'Supplier not found.', 'danger');
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE suppliers SET password_hash = ?, portal_enabled = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$passwordHash, $id]);

        AuditService::log('RESET_SUPPLIER_PASSWORD', 'Suppliers', $id, "Admin password reset for supplier {$supplier['company_name']}");

        redirect($_SERVER['HTTP_REFERER'] ?? '/suppliers', "Password for {$supplier['company_name']} reset to: {$newPassword}", 'success');
    }

    public function sendActivation(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'accounts']);
        $pdo = Database::getConnection();

        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        if ($supplierId <= 0) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/suppliers', 'Invalid supplier identifier.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->execute([$supplierId]);
        $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$supplier || empty($supplier['email'])) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/suppliers', 'Supplier has no registered email address.', 'danger');
        }

        // Defensive self-healing for portal_activation_tokens schema
        try { $pdo->exec("ALTER TABLE portal_activation_tokens ADD COLUMN is_used INTEGER DEFAULT 0"); } catch (\Throwable $e) {}
        try { $pdo->exec("ALTER TABLE portal_activation_tokens ADD COLUMN customer_id INTEGER NULL"); } catch (\Throwable $e) {}
        try { $pdo->exec("ALTER TABLE portal_activation_tokens ADD COLUMN token_hash TEXT NULL"); } catch (\Throwable $e) {}

        // Invalidate prior active tokens
        try {
            $pdo->prepare("UPDATE portal_activation_tokens SET is_used = 1, used_at = CURRENT_TIMESTAMP WHERE portal_type = 'supplier' AND entity_id = ?")->execute([$supplierId]);
        } catch (\Throwable $e) {
            try {
                $pdo->prepare("UPDATE portal_activation_tokens SET used_at = CURRENT_TIMESTAMP WHERE portal_type = 'supplier' AND entity_id = ?")->execute([$supplierId]);
            } catch (\Throwable $e2) {}
        }

        // Generate token
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+48 hours'));

        try {
            $stmt = $pdo->prepare("INSERT INTO portal_activation_tokens (portal_type, entity_id, entity_email, customer_id, token, token_hash, expires_at, is_used, created_at) VALUES ('supplier', ?, ?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP)");
            $stmt->execute([$supplierId, $supplier['email'], $supplierId, $rawToken, $tokenHash, $expiresAt]);
        } catch (\Throwable $e) {
            $stmt = $pdo->prepare("INSERT INTO portal_activation_tokens (portal_type, entity_id, entity_email, token, expires_at, created_at) VALUES ('supplier', ?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$supplierId, $supplier['email'], $rawToken, $expiresAt]);
        }

        $appUrl = \App\Config\App::url();
        $activationLink = \App\Config\App::url("supplier/activate?token={$rawToken}");

        try {
            \App\Services\EmailService::send([
                'to' => $supplier['email'],
                'name' => $supplier['company_name'],
                'subject' => "Activate Your MS TRAVEL HUB Supplier Portal Account",
                'bodyHtml' => "
                    <h2 style='color:#0f172a;'>Welcome to MS TRAVEL HUB Supplier Portal, {$supplier['company_name']}!</h2>
                    <p>Your supplier vendor portal account has been prepared. You can now securely set your password and access your payment ledger, assigned visa services, and statements online.</p>
                    <p style='text-align:center; margin:30px 0;'>
                        <a href='{$activationLink}' style='background-color:#0284c7; color:#ffffff; padding:12px 28px; text-decoration:none; border-radius:6px; font-weight:bold; display:inline-block;'>Activate Supplier Portal &rarr;</a>
                    </p>
                    <p style='color:#64748b; font-size:13px;'>Or copy and paste this link:<br><a href='{$activationLink}'>{$activationLink}</a></p>
                    <p style='color:#64748b; font-size:12px;'>This activation link is single-use and will expire in 48 hours.</p>
                "
            ]);
        } catch (\Throwable $e) {}

        AuditService::log('SEND_SUPPLIER_ACTIVATION', 'Suppliers', $supplierId, "Dispatched supplier portal activation link to {$supplier['email']}");
        redirect($_SERVER['HTTP_REFERER'] ?? '/suppliers', "Supplier portal activation link dispatched to {$supplier['email']}.", 'success');
    }

    public function wallet(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'accounts', 'branch-manager', 'visa-manager']);
        $pdo = Database::getConnection();

        $supplierId = (int)($_GET['id'] ?? $_GET['supplier_id'] ?? 0);
        $suppliers = $pdo->query("SELECT id, company_name, supplier_code, country, email, mobile FROM suppliers WHERE is_active = 1 ORDER BY company_name ASC")->fetchAll(PDO::FETCH_ASSOC);

        if ($supplierId <= 0 && !empty($suppliers)) {
            $supplierId = (int)$suppliers[0]['id'];
        }

        $currentSupplier = null;
        if ($supplierId > 0) {
            $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
            $stmt->execute([$supplierId]);
            $currentSupplier = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$currentSupplier && !empty($suppliers)) {
            $currentSupplier = $suppliers[0];
            $supplierId = (int)$currentSupplier['id'];
        }

        $wallet = $supplierId > 0 ? WalletService::getOrCreateSupplierWallet($supplierId, 'AED') : [
            'id' => 0, 'supplier_id' => 0, 'currency' => 'AED', 'current_balance' => 0.00, 'total_credited' => 0.00, 'total_debited' => 0.00
        ];
        $transactions = $supplierId > 0 ? WalletService::getSupplierTransactions($supplierId, 100) : [];

        // Calculate Supplier Financial Summary from supplier_payments
        $totalPayables = 0.0;
        $totalPaid = 0.0;
        $outstanding = 0.0;
        if ($supplierId > 0) {
            $payStmt = $pdo->prepare("SELECT 
                COALESCE(SUM(payable_amount), 0) as total_payables,
                COALESCE(SUM(paid_amount), 0) as total_paid
                FROM supplier_payments WHERE supplier_id = ?");
            $payStmt->execute([$supplierId]);
            $fin = $payStmt->fetch(PDO::FETCH_ASSOC);
            $totalPayables = (float)($fin['total_payables'] ?? 0);
            $totalPaid = (float)($fin['total_paid'] ?? 0);
            $outstanding = max(0.00, $totalPayables - $totalPaid);
        }

        require_once dirname(__DIR__) . '/Views/suppliers/wallet.php';
    }

    public function walletTopUp(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'accounts']);

        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0.00);
        $currency = strtoupper(trim($_POST['currency'] ?? 'AED'));
        $exchangeRate = (float)($_POST['exchange_rate'] ?? 1.000000);
        $originalAmount = (float)($_POST['original_amount'] ?? $amount);
        $convertedAmount = (float)($_POST['converted_amount'] ?? $amount);
        $method = trim($_POST['payment_method'] ?? 'Bank Transfer');
        $reference = trim($_POST['reference'] ?? ('SUP-WTOP-' . date('Ymd') . '-' . rand(1000, 9999)));
        $notes = trim($_POST['notes'] ?? 'Supplier wallet top-up');
        $userId = (int)($_SESSION['user']['id'] ?? 1);

        if ($supplierId <= 0 || $amount <= 0) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/suppliers', 'Invalid supplier or top-up amount.', 'danger');
        }

        try {
            WalletService::creditSupplier(
                $supplierId,
                $amount,
                $notes,
                $userId,
                $currency,
                $originalAmount,
                $exchangeRate,
                $method,
                $reference,
                $convertedAmount
            );
            redirect('/suppliers/wallet?id=' . $supplierId, 'Supplier wallet credited successfully.', 'success');
        } catch (\Throwable $e) {
            redirect('/suppliers/wallet?id=' . $supplierId, 'Failed to credit wallet: ' . $e->getMessage(), 'danger');
        }
    }

    public function walletDeduct(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'accounts']);

        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0.00);
        $currency = strtoupper(trim($_POST['currency'] ?? 'AED'));
        $exchangeRate = (float)($_POST['exchange_rate'] ?? 1.000000);
        $originalAmount = (float)($_POST['original_amount'] ?? $amount);
        $convertedAmount = (float)($_POST['converted_amount'] ?? $amount);
        $method = trim($_POST['payment_method'] ?? 'Bank Transfer');
        $reference = trim($_POST['reference'] ?? ('SUP-WDED-' . date('Ymd') . '-' . rand(1000, 9999)));
        $notes = trim($_POST['notes'] ?? 'Supplier wallet debit/payout');
        $userId = (int)($_SESSION['user']['id'] ?? 1);

        if ($supplierId <= 0 || $amount <= 0) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/suppliers', 'Invalid supplier or debit amount.', 'danger');
        }

        try {
            WalletService::debitSupplier(
                $supplierId,
                $amount,
                $notes,
                $userId,
                $currency,
                $originalAmount,
                $exchangeRate,
                $method,
                $reference,
                $convertedAmount
            );
            redirect('/suppliers/wallet?id=' . $supplierId, 'Supplier wallet debited successfully.', 'success');
        } catch (\Throwable $e) {
            redirect('/suppliers/wallet?id=' . $supplierId, 'Failed to debit wallet: ' . $e->getMessage(), 'danger');
        }
    }
}
