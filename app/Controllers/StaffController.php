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
        $password = !empty($_POST['password']) ? trim($_POST['password']) : \App\Services\PasswordGeneratorService::generate(10, 'STAFF@');
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

        // Fetch Role & Branch Name for Welcome Email
        $roleName = $pdo->query("SELECT name FROM roles WHERE id = {$roleId}")->fetchColumn() ?: 'Staff Member';
        $branchName = $pdo->query("SELECT name FROM branches WHERE id = {$branchId}")->fetchColumn() ?: 'Main Office';
        $appUrl = (string)\App\Config\Env::get('APP_URL', 'http://localhost:8000');
        $loginUrl = rtrim($appUrl, '/') . '/login';

        // Dispatch Welcome Onboarding Email with Auto-Generated Password
        try {
            $emailSubject = "Welcome to " . \App\Config\App::COMPANY_NAME . " — Staff Portal Access Credentials";
            $emailBody = "
                <p>Dear <strong>{$name}</strong>,</p>
                <p>Welcome to <strong>" . \App\Config\App::COMPANY_NAME . "</strong>. Your staff management account has been created by the administrator.</p>
                <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; margin: 20px 0;'>
                    <h4 style='margin-top: 0; color: #1e3a8a; font-size: 1.1em;'>Your Portal Login Credentials</h4>
                    <p style='margin: 6px 0;'><strong>Official Login Email:</strong> <span style='color: #2563eb;'>{$email}</span></p>
                    <p style='margin: 6px 0;'><strong>Auto-Generated Password:</strong> <code style='background: #e2e8f0; padding: 4px 8px; border-radius: 4px; font-weight: bold; color: #0f172a; font-size: 1.1em;'>{$password}</code></p>
                    <p style='margin: 6px 0;'><strong>Assigned Role:</strong> {$roleName}</p>
                    <p style='margin: 6px 0;'><strong>Designation:</strong> {$designation}</p>
                    <p style='margin: 6px 0;'><strong>Branch:</strong> {$branchName}</p>
                </div>
                <p style='color: #dc2626; font-size: 0.9em;'><strong>Security Requirement:</strong> Please log in and change your auto-generated temporary password upon your first sign-in.</p>
                <p style='text-align: center; margin-top: 25px;'>
                    <a href='{$loginUrl}' style='background: #2563eb; color: #ffffff; padding: 12px 28px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block;'>Access Staff Portal &rarr;</a>
                </p>
            ";

            \App\Services\EmailService::send([
                'to' => $email,
                'name' => $name,
                'subject' => $emailSubject,
                'bodyHtml' => $emailBody,
                'data' => [
                    'name' => $name,
                    'email' => $email,
                    'temporaryPassword' => $password,
                    'roleName' => $roleName,
                    'designation' => $designation,
                    'branchName' => $branchName,
                    'loginUrl' => $loginUrl,
                ]
            ]);

            // Log notification
            $pdo->prepare("INSERT INTO notification_logs (event_type, recipient_type, recipient_id, recipient_name, recipient_email, channel, template_name, subject, content_preview, status, sent_at) VALUES ('staff.registered', 'Staff', ?, ?, ?, 'Email', 'staff_welcome_email', ?, ?, 'Sent', CURRENT_TIMESTAMP)")
                ->execute([$newId, $name, $email, $emailSubject, "Welcome email with temporary password {$password}"]);
        } catch (\Throwable $ex) {}

        AuditService::log('CREATE_STAFF', 'Staff', $newId, "Created staff member {$name} ({$email}) with initial temporary password");

        redirect('/staff', "Staff officer '{$name}' created successfully. Auto password: {$password}", 'success');
    }

    public function delete(): void
    {
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $id = (int)($_POST['id'] ?? $_POST['user_id'] ?? 0);
        if ($id <= 0) {
            redirect('/staff', 'Invalid staff member identifier.', 'danger');
        }

        if ($id === (int)($currentUser['id'] ?? 0)) {
            redirect('/staff', 'You cannot delete your own active administrator account.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT u.*, r.slug as role_slug FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
        $stmt->execute([$id]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            redirect('/staff', 'Staff member not found.', 'danger');
        }

        if (($member['role_slug'] ?? '') === 'super-admin') {
            $superAdminCount = (int)$pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.slug = 'super-admin'")->fetchColumn();
            if ($superAdminCount <= 1) {
                redirect('/staff', 'Cannot delete the sole Super Admin account in the system.', 'danger');
            }
        }

        // Unlink or clean up staff foreign key references
        try { $pdo->prepare("DELETE FROM application_assignments WHERE assigned_to = ? OR assigned_by = ?")->execute([$id, $id]); } catch (\Throwable $e) {}
        try { $pdo->prepare("UPDATE applications SET assigned_staff_id = NULL WHERE assigned_staff_id = ?")->execute([$id]); } catch (\Throwable $e) {}
        try { $pdo->prepare("UPDATE tasks SET assigned_to = NULL WHERE assigned_to = ?")->execute([$id]); } catch (\Throwable $e) {}
        try { $pdo->prepare("UPDATE tasks SET created_by = NULL WHERE created_by = ?")->execute([$id]); } catch (\Throwable $e) {}
        try { $pdo->prepare("UPDATE tasks SET completed_by = NULL WHERE completed_by = ?")->execute([$id]); } catch (\Throwable $e) {}
        try { $pdo->prepare("UPDATE appointments SET assigned_staff_id = NULL WHERE assigned_staff_id = ?")->execute([$id]); } catch (\Throwable $e) {}
        try { $pdo->prepare("UPDATE appointments SET assigned_to = NULL WHERE assigned_to = ?")->execute([$id]); } catch (\Throwable $e) {}
        try { $pdo->prepare("UPDATE appointments SET created_by = NULL WHERE created_by = ?")->execute([$id]); } catch (\Throwable $e) {}
        try { $pdo->prepare("UPDATE visa_approvals SET approved_by = NULL WHERE approved_by = ?")->execute([$id]); } catch (\Throwable $e) {}
        try { $pdo->prepare("UPDATE visa_rejections SET rejected_by = NULL WHERE rejected_by = ?")->execute([$id]); } catch (\Throwable $e) {}
        try { $pdo->prepare("UPDATE visa_returns SET returned_by = NULL WHERE returned_by = ?")->execute([$id]); } catch (\Throwable $e) {}
        try { $pdo->prepare("UPDATE application_stages SET changed_by = NULL WHERE changed_by = ?")->execute([$id]); } catch (\Throwable $e) {}
        try { $pdo->prepare("UPDATE application_status_history SET changed_by = NULL WHERE changed_by = ?")->execute([$id]); } catch (\Throwable $e) {}
        try { $pdo->prepare("UPDATE documents SET verified_by = NULL WHERE verified_by = ?")->execute([$id]); } catch (\Throwable $e) {}
        try { $pdo->prepare("UPDATE documents SET uploaded_by = NULL WHERE uploaded_by = ?")->execute([$id]); } catch (\Throwable $e) {}
        try { $pdo->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$id]); } catch (\Throwable $e) {}
        try { $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?")->execute([$id]); } catch (\Throwable $e) {}
        try { $pdo->prepare("DELETE FROM activity_logs WHERE user_id = ?")->execute([$id]); } catch (\Throwable $e) {}
        try { $pdo->prepare("DELETE FROM audit_logs WHERE user_id = ?")->execute([$id]); } catch (\Throwable $e) {}
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);

        AuditService::log('DELETE_STAFF', 'Staff', $id, "Permanently deleted staff member {$member['name']} ({$member['email']})");

        redirect('/staff', "Staff member '{$member['name']}' has been permanently deleted from the system.", 'success');
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
        $newPassword = !empty($_POST['new_password']) ? trim($_POST['new_password']) : \App\Services\PasswordGeneratorService::generate(10, 'STAFF@');

        if ($id <= 0) {
            redirect('/staff', 'Staff ID cannot be empty.', 'danger');
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

        // Dispatch Email Notification to Staff
        if (!empty($member['email'])) {
            try {
                $appUrl = (string)\App\Config\Env::get('APP_URL', 'http://localhost:8000');
                $loginUrl = rtrim($appUrl, '/') . '/login';
                \App\Services\EmailService::send([
                    'to' => $member['email'],
                    'name' => $member['name'],
                    'subject' => 'MS Travel Hub — Your Staff Portal Password Has Been Reset',
                    'bodyHtml' => "
                        <p>Dear <strong>{$member['name']}</strong>,</p>
                        <p>Your staff portal password has been reset by the administrator.</p>
                        <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; margin: 20px 0;'>
                            <h4 style='margin-top: 0; color: #1e3a8a;'>Your New Staff Login Password</h4>
                            <p style='margin: 6px 0;'><strong>Login Email:</strong> {$member['email']}</p>
                            <p style='margin: 6px 0;'><strong>New Temporary Password:</strong> <code style='background: #e2e8f0; padding: 4px 8px; border-radius: 4px; font-weight: bold; color: #0f172a; font-size: 1.1em;'>{$newPassword}</code></p>
                        </div>
                        <p style='text-align: center; margin-top: 25px;'>
                            <a href='{$loginUrl}' style='background: #2563eb; color: #ffffff; padding: 12px 28px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block;'>Login to Staff Portal &rarr;</a>
                        </p>
                    "
                ]);
            } catch (\Throwable $e) {}
        }

        AuditService::log('RESET_PASSWORD', 'Staff', $id, "Administrative password reset for {$member['name']} ({$member['email']})");

        redirect($_SERVER['HTTP_REFERER'] ?? "/staff/show?id={$id}", "Password for {$member['name']} has been reset to: {$newPassword}", 'success');
    }
}
