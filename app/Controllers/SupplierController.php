<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\AuditService;
use PDO;

class SupplierController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $suppliers = $pdo->query("SELECT s.*,
            COUNT(DISTINCT a.id) as total_applications,
            COALESCE(SUM(sp.payable_amount), 0) as total_payables,
            COALESCE(SUM(sp.paid_amount), 0) as total_paid
            FROM suppliers s 
            LEFT JOIN supplier_payments sp ON sp.supplier_id = s.id 
            LEFT JOIN applications a ON sp.application_id = a.id 
            GROUP BY s.id 
            ORDER BY s.is_active DESC, s.company_name ASC")->fetchAll();

        $applications = $pdo->query("SELECT id, application_number FROM applications ORDER BY id DESC LIMIT 50")->fetchAll();

        require_once dirname(__DIR__) . '/Views/suppliers/index.php';
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

        $rawPassword = !empty($_POST['password']) ? trim($_POST['password']) : \App\Services\PasswordGeneratorService::generate(10, 'SUP@');
        $passwordHash = password_hash($rawPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO suppliers (
            supplier_code, company_name, contact_person, email, mobile, whatsapp, country, address, services_provided, bank_details, password_hash, portal_enabled
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$code, $name, $contact, $email, $mobile, $whatsapp, $country, $address, $services, $bankDetails, $passwordHash]);
        $supplierId = (int)$pdo->lastInsertId();

        // Dispatch Welcome Onboarding Email to Supplier with Auto-Generated Password
        if (!empty($email)) {
            try {
                $appUrl = (string)\App\Config\Env::get('APP_URL', 'http://localhost:8000');
                $portalUrl = rtrim($appUrl, '/') . '/supplier/login';
                \App\Services\EmailService::send([
                    'to' => $email,
                    'name' => $contact ?: $name,
                    'subject' => 'Welcome to ' . \App\Config\App::COMPANY_NAME . ' — Supplier Portal Access Credentials',
                    'bodyHtml' => "
                        <p>Dear <strong>" . htmlspecialchars($contact ?: $name) . "</strong>,</p>
                        <p>Welcome to <strong>" . \App\Config\App::COMPANY_NAME . "</strong>. Your supplier partner account has been configured with portal access.</p>
                        <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; margin: 20px 0;'>
                            <h4 style='margin-top: 0; color: #1e3a8a;'>Your Portal Login Credentials</h4>
                            <p style='margin: 6px 0;'><strong>Supplier Code:</strong> <span style='font-family: monospace; font-weight: bold;'>{$code}</span></p>
                            <p style='margin: 6px 0;'><strong>Login Email:</strong> {$email}</p>
                            <p style='margin: 6px 0;'><strong>Auto-Generated Password:</strong> <code style='background: #e2e8f0; padding: 4px 8px; border-radius: 4px; font-weight: bold; color: #0f172a;'>{$rawPassword}</code></p>
                        </div>
                        <p style='text-align: center; margin-top: 25px;'>
                            <a href='{$portalUrl}' style='background: #2563eb; color: #ffffff; padding: 12px 28px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block;'>Access Supplier Portal &rarr;</a>
                        </p>
                    "
                ]);
            } catch (\Throwable $e) {}
        }

        AuditService::log('CREATE_SUPPLIER', 'Suppliers', $supplierId, "Created supplier {$name} ({$code}) with auto password");

        redirect('/suppliers', "Supplier '{$name}' created with auto password: {$rawPassword}", 'success');
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

        $stmt = $pdo->prepare("UPDATE suppliers SET 
            supplier_code = ?, company_name = ?, contact_person = ?, email = ?, mobile = ?, country = ?, address = ?, services_provided = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?");
        $stmt->execute([$code, $name, $contact, $email, $mobile, $country, $address, $services, $id]);

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
        $amount = (float)($_POST['amount'] ?? 0.00);
        $date = $_POST['payment_date'] ?? date('Y-m-d');
        $method = trim($_POST['payment_method'] ?? 'Bank Transfer');
        $ref = trim($_POST['transaction_reference'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $userId = (int)($_SESSION['user']['id'] ?? 1);

        if ($supplierId <= 0 || $amount <= 0) {
            redirect('/suppliers', 'Supplier and valid payment amount are required.', 'danger');
        }

        $payRef = 'SPAY-' . date('Ymd') . '-' . rand(1000, 9999);

        // If no specific application is linked, link to first or 1
        if ($applicationId <= 0) {
            $applicationId = 1;
        }

        $stmt = $pdo->prepare("INSERT INTO supplier_payments (
            payment_reference, supplier_id, application_id, payable_amount, paid_amount, payment_date, payment_method, transaction_reference, notes, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$payRef, $supplierId, $applicationId, $amount, $amount, $date, $method, $ref, $notes, $userId]);

        AuditService::log('SUPPLIER_PAYMENT', 'Suppliers', $supplierId, "Recorded disbursement of $" . number_format($amount, 2) . " (Ref: {$payRef})");

        redirect('/suppliers', "Disbursement of $" . number_format($amount, 2) . " recorded successfully.", 'success');
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
        $supplier = $stmt->fetch();

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
        $supplier = $stmt->fetch();

        if (!$supplier) {
            redirect('/suppliers', 'Supplier not found.', 'danger');
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE suppliers SET password_hash = ?, portal_enabled = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$passwordHash, $id]);

        // Dispatch email notification if email exists
        if (!empty($supplier['email'])) {
            try {
                \App\Services\EmailService::send([
                    'to' => $supplier['email'],
                    'name' => $supplier['contact_person'] ?: $supplier['company_name'],
                    'subject' => 'MS Travel Hub — Your Supplier Portal Password Has Been Reset',
                    'bodyHtml' => "
                        <p>Dear <strong>" . htmlspecialchars($supplier['contact_person'] ?: $supplier['company_name']) . "</strong>,</p>
                        <p>Your Supplier Portal password has been reset by the administrator.</p>
                        <p><strong>Your New Password:</strong> <code style='background: #e2e8f0; padding: 4px 8px; border-radius: 4px; font-weight: bold;'>{$newPassword}</code></p>
                    "
                ]);
            } catch (\Throwable $e) {}
        }

        AuditService::log('RESET_SUPPLIER_PASSWORD', 'Suppliers', $id, "Admin password reset for supplier {$supplier['company_name']}");

        redirect($_SERVER['HTTP_REFERER'] ?? '/suppliers', "Password for {$supplier['company_name']} reset to: {$newPassword}", 'success');
    }
}
