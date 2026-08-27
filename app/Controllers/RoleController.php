<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\AuditService;
use PDO;

class RoleController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $roles = $pdo->query("SELECT r.*, 
            COUNT(DISTINCT u.id) as user_count,
            COUNT(DISTINCT rp.permission_id) as permission_count
            FROM roles r 
            LEFT JOIN users u ON u.role_id = r.id 
            LEFT JOIN role_permissions rp ON rp.role_id = r.id 
            GROUP BY r.id 
            ORDER BY r.id ASC")->fetchAll();

        $totalPermissions = (int)$pdo->query("SELECT COUNT(*) FROM permissions")->fetchColumn();
        $allPermissions = $pdo->query("SELECT * FROM permissions ORDER BY module ASC, name ASC")->fetchAll();

        require_once dirname(__DIR__) . '/Views/roles/index.php';
    }

    public function store(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $selectedPermIds = $_POST['permissions'] ?? [];

        if (empty($name)) {
            redirect('/roles', 'Role name is required.', 'danger');
        }

        $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $name));

        // Check if role name/slug already exists
        $chk = $pdo->prepare("SELECT id FROM roles WHERE slug = ? OR name = ? LIMIT 1");
        $chk->execute([$slug, $name]);
        if ($chk->fetch()) {
            redirect('/roles', "Role '{$name}' already exists.", 'danger');
        }

        $stmt = $pdo->prepare("INSERT INTO roles (name, slug, description, is_active, created_at) VALUES (?, ?, ?, 1, NOW())");
        $stmt->execute([$name, $slug, $description]);
        $newRoleId = (int)$pdo->lastInsertId();

        if (!empty($selectedPermIds)) {
            $insStmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($selectedPermIds as $pId) {
                $insStmt->execute([$newRoleId, (int)$pId]);
            }
        }

        AuditService::log('CREATE_ROLE', 'Roles', $newRoleId, "Created custom role '{$name}' with " . count($selectedPermIds) . " permissions");

        redirect('/roles', "Role '{$name}' created successfully with " . count($selectedPermIds) . " permissions.", 'success');
    }

    public function edit(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            redirect('/roles', 'Invalid role identifier.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
        $stmt->execute([$id]);
        $role = $stmt->fetch();

        if (!$role) {
            redirect('/roles', 'Role not found.', 'danger');
        }

        // Fetch all permissions grouped by module
        $perms = $pdo->query("SELECT * FROM permissions ORDER BY module ASC, slug ASC")->fetchAll();
        $groupedPermissions = [];
        foreach ($perms as $p) {
            $groupedPermissions[$p['module']][] = $p;
        }

        // Fetch active permission IDs for this role
        $rpStmt = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
        $rpStmt->execute([$id]);
        $activePermIds = $rpStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        require_once dirname(__DIR__) . '/Views/roles/edit.php';
    }

    public function update(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $selectedPermIds = $_POST['permissions'] ?? [];

        if ($id <= 0 || empty($name)) {
            redirect('/roles', 'Role name is required.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
        $stmt->execute([$id]);
        $role = $stmt->fetch();

        if (!$role) {
            redirect('/roles', 'Role not found.', 'danger');
        }

        // Update role basic info
        $upStmt = $pdo->prepare("UPDATE roles SET name = ?, description = ? WHERE id = ?");
        $upStmt->execute([$name, $description, $id]);

        // If Super Admin role, ensure all permissions remain intact
        if ($role['slug'] === 'super-admin' || $id === 1) {
            $allPermIds = $pdo->query("SELECT id FROM permissions")->fetchAll(PDO::FETCH_COLUMN);
            $selectedPermIds = $allPermIds;
        }

        // Sync role permissions
        $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$id]);
        
        if (!empty($selectedPermIds)) {
            $insStmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($selectedPermIds as $pId) {
                $insStmt->execute([$id, (int)$pId]);
            }
        }

        // Invalidate cached user permissions in active session
        unset($_SESSION['user_permissions']);

        AuditService::log('UPDATE_ROLE_PERMISSIONS', 'Roles', $id, "Updated permissions matrix for role {$name} (" . count($selectedPermIds) . " permissions)");

        redirect('/roles', "Permissions for role '{$name}' updated successfully.", 'success');
    }

    public function toggleStatus(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 1) {
            redirect('/roles', 'Super Admin role status cannot be altered.', 'danger');
        }

        $stmt = $pdo->prepare("UPDATE roles SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?");
        $stmt->execute([$id]);

        AuditService::log('TOGGLE_ROLE_STATUS', 'Roles', $id, "Toggled status for role #{$id}");

        redirect('/roles', "Role status updated.", 'success');
    }

    public function delete(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin']);
        $pdo = Database::getConnection();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 1) {
            redirect('/roles', 'Super Admin role cannot be deleted.', 'danger');
        }

        // Check if any users are assigned to this role
        $userCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role_id = {$id}")->fetchColumn();
        if ($userCount > 0) {
            redirect('/roles', "Cannot delete role: {$userCount} user(s) are currently assigned to it.", 'danger');
        }

        $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM roles WHERE id = ?")->execute([$id]);

        AuditService::log('DELETE_ROLE', 'Roles', $id, "Deleted custom role #{$id}");

        redirect('/roles', "Custom role deleted successfully.", 'success');
    }
}
