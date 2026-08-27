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

        $stmt = $pdo->prepare("INSERT INTO suppliers (
            supplier_code, company_name, contact_person, email, mobile, whatsapp, country, address, services_provided, bank_details
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $name, $contact, $email, $mobile, $whatsapp, $country, $address, $services, $bankDetails]);
        $supplierId = (int)$pdo->lastInsertId();

        AuditService::log('CREATE_SUPPLIER', 'Suppliers', $supplierId, "Created supplier {$name} ({$code})");

        redirect('/suppliers', "Supplier '{$name}' created successfully.", 'success');
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
}
