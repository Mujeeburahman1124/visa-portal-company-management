<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\AuditService;
use PDO;

class StaffController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $search = trim($_GET['search'] ?? '');
        $roleId = (int)($_GET['role_id'] ?? 0);
        $branchId = (int)($_GET['branch_id'] ?? 0);
        $status = $_GET['status'] ?? '';

        $sql = "SELECT u.*, r.name as role_name, r.slug as role_slug, b.name as branch_name,
            COUNT(DISTINCT a.id) as total_applications,
            SUM(CASE WHEN a.status NOT IN ('Approved', 'Completed', 'Rejected', 'Cancelled') THEN 1 ELSE 0 END) as active_applications,
            (SELECT COUNT(*) FROM tasks t WHERE t.assigned_to = u.id AND t.status != 'Completed') as pending_tasks
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            LEFT JOIN branches b ON u.branch_id = b.id 
            LEFT JOIN applications a ON a.assigned_staff_id = u.id 
            WHERE 1=1";

        $params = [];
        if ($search !== '') {
            $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.designation LIKE ?)";
            $term = "%{$search}%";
            $params = array_merge($params, [$term, $term, $term]);
        }
        if ($roleId > 0) {
            $sql .= " AND u.role_id = ?";
            $params[] = $roleId;
        }
        if ($branchId > 0) {
            $sql .= " AND u.branch_id = ?";
            $params[] = $branchId;
        }
        if ($status === 'active') {
            $sql .= " AND u.is_active = 1";
        } elseif ($status === 'inactive') {
            $sql .= " AND u.is_active = 0";
        }

        $sql .= " GROUP BY u.id ORDER BY u.is_active DESC, u.name ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $staff = $stmt->fetchAll();

        $roles = $pdo->query("SELECT id, name, slug FROM roles ORDER BY name ASC")->fetchAll();
        $branches = $pdo->query("SELECT id, name, code FROM branches ORDER BY name ASC")->fetchAll();

        // Overall stats
        $totalStaff = count($staff);
        $activeStaff = count(array_filter($staff, fn($s) => (int)$s['is_active'] === 1));

        require_once dirname(__DIR__) . '/Views/staff/index.php';
    }

    public function show(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            redirect('/staff', 'Invalid staff member identifier.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT u.*, r.name as role_name, r.slug as role_slug, r.description as role_description, b.name as branch_name, b.code as branch_code 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            LEFT JOIN branches b ON u.branch_id = b.id 
            WHERE u.id = ?");
        $stmt->execute([$id]);
        $member = $stmt->fetch();

        if (!$member) {
            redirect('/staff', 'Staff member not found.', 'danger');
        }

        // Assigned Applications
        $appStmt = $pdo->prepare("SELECT a.*, c.full_name as customer_name, c.nationality, vs.name as service_name, co.name as country_name, co.flag_emoji 
            FROM applications a 
            JOIN customers c ON a.customer_id = c.id 
            JOIN visa_services vs ON a.visa_service_id = vs.id 
            JOIN countries co ON vs.country_id = co.id 
            WHERE a.assigned_staff_id = ? 
            ORDER BY a.created_at DESC LIMIT 20");
        $appStmt->execute([$id]);
        $assignedApps = $appStmt->fetchAll();

        // Assigned Pending Tasks
        $taskStmt = $pdo->prepare("SELECT t.*, a.application_number 
            FROM tasks t 
            LEFT JOIN applications a ON t.application_id = a.id 
            WHERE t.assigned_to = ? 
            ORDER BY t.status ASC, t.due_date ASC LIMIT 20");
        $taskStmt->execute([$id]);
        $assignedTasks = $taskStmt->fetchAll();

        // Recent Activity Logs
        $logStmt = $pdo->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 15");
        $logStmt->execute([$id]);
        $recentLogs = $logStmt->fetchAll();

        // Effective Permissions for this member's role
        $permStmt = $pdo->prepare("SELECT p.name, p.slug, p.module, p.description 
            FROM role_permissions rp 
            JOIN permissions p ON rp.permission_id = p.id 
            WHERE rp.role_id = ? 
            ORDER BY p.module ASC, p.name ASC");
        $permStmt->execute([(int)$member['role_id']]);
        $permissions = $permStmt->fetchAll();

        $roles = $pdo->query("SELECT id, name FROM roles ORDER BY name ASC")->fetchAll();
        $branches = $pdo->query("SELECT id, name FROM branches ORDER BY name ASC")->fetchAll();

        require_once dirname(__DIR__) . '/Views/staff/show.php';
    }

    public function store(): void
    {
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        $pdo = Database::getConnection();

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? 'password123';
        $roleId = (int)($_POST['role_id'] ?? 4);
        $branchId = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : 1;
        $phone = trim($_POST['phone'] ?? '');
        $designation = trim($_POST['designation'] ?? 'Visa Specialist');
        $department = trim($_POST['department'] ?? 'Visa Department');

        if (empty($name) || empty($email)) {
            redirect('/staff', 'Please provide staff name and work email.', 'danger');
        }

        // Check duplicate email
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ((int)$stmt->fetchColumn() > 0) {
            redirect('/staff', "A user with email '{$email}' already exists.", 'danger');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (
            role_id, branch_id, name, email, password_hash, phone, designation, department
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$roleId, $branchId, $name, $email, $passwordHash, $phone, $designation, $department]);
        $newId = (int)$pdo->lastInsertId();

        AuditService::log('CREATE_STAFF', 'Staff', $newId, "Created staff member {$name} ({$email})");

        redirect('/staff', "Staff officer '{$name}' created successfully.", 'success');
    }

    public function update(): void
    {
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        $pdo = Database::getConnection();

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $roleId = (int)($_POST['role_id'] ?? 0);
        $branchId = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null;
        $phone = trim($_POST['phone'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $department = trim($_POST['department'] ?? '');

        if ($id <= 0 || empty($name) || empty($email) || $roleId <= 0) {
            redirect('/staff', 'All required profile fields must be provided.', 'danger');
        }

        // Check duplicate email
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ((int)$stmt->fetchColumn() > 0) {
            redirect("/staff/show?id={$id}", "Email '{$email}' is already in use by another officer.", 'danger');
        }

        $stmt = $pdo->prepare("UPDATE users SET 
            name = ?, email = ?, role_id = ?, branch_id = ?, phone = ?, designation = ?, department = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?");
        $stmt->execute([$name, $email, $roleId, $branchId, $phone, $designation, $department, $id]);

        AuditService::log('UPDATE_STAFF', 'Staff', $id, "Updated staff profile details for {$name}");

        redirect($_SERVER['HTTP_REFERER'] ?? "/staff/show?id={$id}", "Profile for '{$name}' updated successfully.", 'success');
    }

    public function toggleActive(): void
    {
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            redirect('/staff', 'Invalid staff identifier.', 'danger');
        }

        // Prevent self-deactivation or deactivating sole super admin
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
        if ($id === $currentUserId) {
            redirect('/staff', 'You cannot deactivate your own administrative account.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT u.*, r.slug as role_slug FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
        $stmt->execute([$id]);
        $member = $stmt->fetch();

        if (!$member) {
            redirect('/staff', 'Staff member not found.', 'danger');
        }

        $newStatus = ((int)$member['is_active'] === 1) ? 0 : 1;
        $statusText = $newStatus === 1 ? 'Activated' : 'Deactivated';

        $upStmt = $pdo->prepare("UPDATE users SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $upStmt->execute([$newStatus, $id]);

        AuditService::log($newStatus === 1 ? 'ACTIVATE_STAFF' : 'DEACTIVATE_STAFF', 'Staff', $id, "{$statusText} staff member {$member['name']}");

        redirect($_SERVER['HTTP_REFERER'] ?? '/staff', "Staff member '{$member['name']}' has been {$statusText}.", 'success');
    }

    public function resetPassword(): void
    {
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $id = (int)($_POST['id'] ?? 0);
        $newPassword = $_POST['new_password'] ?? 'password123';

        if ($id <= 0 || empty($newPassword)) {
            redirect('/staff', 'Password cannot be empty.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $member = $stmt->fetch();

        if (!$member) {
            redirect('/staff', 'Staff member not found.', 'danger');
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$passwordHash, $id]);

        AuditService::log('RESET_PASSWORD', 'Staff', $id, "Administrative password reset for {$member['name']} ({$member['email']})");

        redirect($_SERVER['HTTP_REFERER'] ?? "/staff/show?id={$id}", "Password for {$member['name']} has been reset successfully.", 'success');
    }
}
