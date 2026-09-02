<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\AuditService;
use PDO;

class BranchController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $branches = $pdo->query("SELECT b.*,
            COUNT(DISTINCT u.id) as staff_count,
            COUNT(DISTINCT a.id) as total_applications,
            COALESCE(SUM(p.amount), 0) as total_revenue
            FROM branches b 
            LEFT JOIN users u ON u.branch_id = b.id 
            LEFT JOIN applications a ON a.branch_id = b.id 
            LEFT JOIN payments p ON p.application_id = a.id AND p.status = 'Completed'
            GROUP BY b.id 
            ORDER BY b.is_active DESC, b.name ASC")->fetchAll();

        require_once dirname(__DIR__) . '/Views/branches/index.php';
    }

    public function store(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $country = trim($_POST['country'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($name) || empty($code) || empty($country)) {
            redirect('/branches', 'Branch name, code, and country are required.', 'danger');
        }

        // Duplicate code check
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM branches WHERE code = ?");
        $stmt->execute([$code]);
        if ((int)$stmt->fetchColumn() > 0) {
            redirect('/branches', "Branch code '{$code}' already exists.", 'danger');
        }

        $stmt = $pdo->prepare("INSERT INTO branches (name, code, country, city, address, phone, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $code, $country, $city, $address, $phone, $email]);
        $branchId = (int)$pdo->lastInsertId();

        AuditService::log('CREATE_BRANCH', 'Branches', $branchId, "Created operating branch {$name} ({$code})");

        redirect('/branches', "Branch '{$name}' created successfully.", 'success');
    }

    public function update(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $country = trim($_POST['country'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($id <= 0 || empty($name) || empty($code)) {
            redirect('/branches', 'All required branch details must be provided.', 'danger');
        }

        $stmt = $pdo->prepare("UPDATE branches SET name = ?, code = ?, country = ?, city = ?, address = ?, phone = ?, email = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$name, $code, $country, $city, $address, $phone, $email, $id]);

        AuditService::log('UPDATE_BRANCH', 'Branches', $id, "Updated branch details for {$name}");

        redirect('/branches', "Branch '{$name}' updated successfully.", 'success');
    }

    public function toggleStatus(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            redirect('/branches', 'Invalid branch identifier.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT * FROM branches WHERE id = ?");
        $stmt->execute([$id]);
        $branch = $stmt->fetch();

        if (!$branch) {
            redirect('/branches', 'Branch not found.', 'danger');
        }

        $newStatus = ((int)$branch['is_active'] === 1) ? 0 : 1;
        $pdo->prepare("UPDATE branches SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$newStatus, $id]);

        $statusText = $newStatus === 1 ? 'Activated' : 'Deactivated';
        AuditService::log('TOGGLE_BRANCH_STATUS', 'Branches', $id, "{$statusText} branch {$branch['name']}");

        redirect('/branches', "Branch '{$branch['name']}' {$statusText}.", 'success');
    }

    public function delete(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $id = (int)($_POST['id'] ?? $_POST['branch_id'] ?? 0);
        if ($id <= 0) {
            redirect('/branches', 'Invalid branch identifier.', 'danger');
        }

        $appCount = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE branch_id = {$id}")->fetchColumn();
        if ($appCount > 0) {
            redirect('/branches', "Cannot delete branch with {$appCount} linked visa applications. Deactivate it instead.", 'warning');
        }

        $stmt = $pdo->prepare("SELECT * FROM branches WHERE id = ?");
        $stmt->execute([$id]);
        $branch = $stmt->fetch();

        if (!$branch) {
            redirect('/branches', 'Branch not found.', 'danger');
        }

        $pdo->prepare("UPDATE users SET branch_id = NULL WHERE branch_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM branches WHERE id = ?")->execute([$id]);

        AuditService::log('DELETE_BRANCH', 'Branches', $id, "Deleted branch {$branch['name']} ({$branch['code']})");

        redirect('/branches', "Branch '{$branch['name']}' deleted successfully.", 'success');
    }
}
