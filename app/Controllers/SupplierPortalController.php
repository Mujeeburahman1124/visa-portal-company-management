<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use PDO;

class SupplierPortalController
{
    private function guard(): array
    {
        session_start_safe();
        if (empty($_SESSION['supplier_auth'])) {
            redirect('/supplier/login');
        }
        return $_SESSION['supplier_auth'];
    }

    public function showLogin(): void
    {
        session_start_safe();
        if (!empty($_SESSION['supplier_auth'])) {
            redirect('/supplier/dashboard');
        }
        $pageTitle = 'Supplier Portal Login — MS Travel Hub';
        $flash = get_flash();
        require_once dirname(__DIR__) . '/Views/supplier-portal/login.php';
    }

    public function login(): void
    {
        session_start_safe();
        $pdo   = Database::getConnection();
        $email = trim($_POST['email'] ?? '');
        $pass  = trim($_POST['password'] ?? '');

        if (empty($email) || empty($pass)) {
            set_flash('Email and password are required.', 'danger');
            redirect('/supplier/login');
        }

        $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE email = ? AND is_active = 1 AND portal_enabled = 1 LIMIT 1");
        $stmt->execute([$email]);
        $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$supplier || empty($supplier['password_hash']) || !password_verify($pass, $supplier['password_hash'])) {
            set_flash('Invalid credentials or portal access not enabled for this account.', 'danger');
            redirect('/supplier/login');
        }

        $_SESSION['supplier_auth'] = [
            'id'           => $supplier['id'],
            'supplier_code'=> $supplier['supplier_code'],
            'company_name' => $supplier['company_name'],
            'contact_person'=> $supplier['contact_person'],
            'email'        => $supplier['email'],
        ];

        $pdo->prepare("UPDATE suppliers SET last_login_at = NOW() WHERE id = ?")->execute([$supplier['id']]);
        redirect('/supplier/dashboard');
    }

    public function logout(): void
    {
        session_start_safe();
        unset($_SESSION['supplier_auth']);
        redirect('/supplier/login');
    }

    public function dashboard(): void
    {
        $supplier   = $this->guard();
        $pdo        = Database::getConnection();
        $supplierId = (int)$supplier['id'];

        // KPIs
        $totalApps   = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE supplier_id = {$supplierId}")->fetchColumn();
        $inProcess   = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE supplier_id = {$supplierId} AND status NOT IN ('Approved','Rejected','Cancelled')")->fetchColumn();
        $approved    = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE supplier_id = {$supplierId} AND status = 'Approved'")->fetchColumn();
        $pending_pay = (float)$pdo->query("SELECT COALESCE(SUM(payable_amount - paid_amount),0) FROM supplier_payments WHERE supplier_id = {$supplierId}")->fetchColumn();

        // Recent applications assigned to this supplier
        $recentStmt = $pdo->prepare("SELECT a.*, vs.name as service_name, ct.name as country_name, ct.flag_emoji,
            c.full_name as customer_name, c.customer_code
            FROM applications a
            JOIN visa_services vs ON vs.id = a.visa_service_id
            JOIN countries ct ON ct.id = vs.country_id
            JOIN customers c ON c.id = a.customer_id
            WHERE a.supplier_id = ?
            ORDER BY a.created_at DESC LIMIT 10");
        $recentStmt->execute([$supplierId]);
        $recentApps = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

        // Recent payments
        $payStmt = $pdo->prepare("SELECT sp.*, a.application_number FROM supplier_payments sp LEFT JOIN applications a ON a.id=sp.application_id WHERE sp.supplier_id=? ORDER BY sp.payment_date DESC LIMIT 5");
        $payStmt->execute([$supplierId]);
        $recentPayments = $payStmt->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'Supplier Dashboard — MS Travel Hub';
        $flash = get_flash();
        require_once dirname(__DIR__) . '/Views/supplier-portal/dashboard.php';
    }

    public function applications(): void
    {
        $supplier   = $this->guard();
        $pdo        = Database::getConnection();
        $supplierId = (int)$supplier['id'];

        $status = trim($_GET['status'] ?? '');
        $search = trim($_GET['search'] ?? '');

        $sql = "SELECT a.*, vs.name as service_name, ct.name as country_name, ct.flag_emoji,
            c.full_name as customer_name, c.customer_code, c.mobile as customer_mobile
            FROM applications a
            JOIN visa_services vs ON vs.id = a.visa_service_id
            JOIN countries ct ON ct.id = vs.country_id
            JOIN customers c ON c.id = a.customer_id
            WHERE a.supplier_id = ?";
        $params = [$supplierId];

        if ($status !== '') {
            $sql .= " AND a.status = ?";
            $params[] = $status;
        }
        if ($search !== '') {
            $sql .= " AND (a.application_number LIKE ? OR c.full_name LIKE ? OR a.supplier_reference LIKE ?)";
            $t = "%{$search}%";
            $params[] = $t; $params[] = $t; $params[] = $t;
        }
        $sql .= " ORDER BY a.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'Assigned Applications — Supplier Portal';
        $flash = get_flash();
        require_once dirname(__DIR__) . '/Views/supplier-portal/applications.php';
    }

    public function updateStatus(): void
    {
        $supplier   = $this->guard();
        $pdo        = Database::getConnection();
        $supplierId = (int)$supplier['id'];

        $appId      = (int)($_POST['application_id'] ?? 0);
        $supplierRef = trim($_POST['supplier_reference'] ?? '');
        $statusNote  = trim($_POST['status_note'] ?? '');

        // Verify app belongs to this supplier
        $check = $pdo->prepare("SELECT id FROM applications WHERE id=? AND supplier_id=?");
        $check->execute([$appId, $supplierId]);
        if (!$check->fetch()) {
            set_flash('Application not found or access denied.', 'danger');
            redirect('/supplier/applications');
        }

        if (!empty($supplierRef)) {
            $pdo->prepare("UPDATE applications SET supplier_reference=? WHERE id=?")->execute([$supplierRef, $appId]);
        }

        if (!empty($statusNote)) {
            // Log the note in application_status_history
            $pdo->prepare("INSERT INTO application_status_history (application_id, from_stage, to_stage, changed_by, comments, created_at) SELECT id, current_stage, current_stage, 0, ?, NOW() FROM applications WHERE id=?")->execute(["[Supplier Update] " . $statusNote, $appId]);
        }

        set_flash('Application updated successfully.', 'success');
        redirect('/supplier/applications');
    }

    public function payments(): void
    {
        $supplier   = $this->guard();
        $pdo        = Database::getConnection();
        $supplierId = (int)$supplier['id'];

        $payStmt = $pdo->prepare("SELECT sp.*, a.application_number, c.full_name as customer_name
            FROM supplier_payments sp
            LEFT JOIN applications a ON a.id = sp.application_id
            LEFT JOIN customers c ON c.id = a.customer_id
            WHERE sp.supplier_id = ?
            ORDER BY sp.payment_date DESC");
        $payStmt->execute([$supplierId]);
        $payments = $payStmt->fetchAll(PDO::FETCH_ASSOC);

        $totalPayable = (float)$pdo->query("SELECT COALESCE(SUM(payable_amount),0) FROM supplier_payments WHERE supplier_id={$supplierId}")->fetchColumn();
        $totalPaid    = (float)$pdo->query("SELECT COALESCE(SUM(paid_amount),0) FROM supplier_payments WHERE supplier_id={$supplierId}")->fetchColumn();

        $pageTitle = 'Payment History — Supplier Portal';
        $flash = get_flash();
        require_once dirname(__DIR__) . '/Views/supplier-portal/payments.php';
    }

    public function profile(): void
    {
        $supplier   = $this->guard();
        $pdo        = Database::getConnection();
        $supplierId = (int)$supplier['id'];

        $supplierData = $pdo->query("SELECT * FROM suppliers WHERE id={$supplierId}")->fetch(PDO::FETCH_ASSOC);

        $pageTitle = 'My Profile — Supplier Portal';
        $flash = get_flash();
        require_once dirname(__DIR__) . '/Views/supplier-portal/profile.php';
    }

    public function updateProfile(): void
    {
        $supplier   = $this->guard();
        $pdo        = Database::getConnection();
        $supplierId = (int)$supplier['id'];

        $contact  = trim($_POST['contact_person'] ?? '');
        $mobile   = trim($_POST['mobile'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $password = trim($_POST['password'] ?? '');

        $sql = "UPDATE suppliers SET contact_person=?, mobile=?, whatsapp=?, address=?";
        $params = [$contact, $mobile, $whatsapp, $address];
        if (!empty($password)) {
            $sql .= ", password_hash=?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        $sql .= " WHERE id=?";
        $params[] = $supplierId;
        $pdo->prepare($sql)->execute($params);

        set_flash('Profile updated.', 'success');
        redirect('/supplier/profile');
    }
}
