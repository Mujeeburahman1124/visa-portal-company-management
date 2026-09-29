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
        $modulesList = $pdo->query("SELECT module, COUNT(*) as perm_count FROM permissions GROUP BY module ORDER BY module ASC")->fetchAll();

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

        // Dynamic detection/addition of is_active column to prevent schema mismatch
        $hasIsActive = false;
        try {
            $pdo->query("SELECT is_active FROM roles LIMIT 1");
            $hasIsActive = true;
        } catch (\Throwable $e) {
            try {
                $pdo->exec("ALTER TABLE roles ADD COLUMN is_active INTEGER DEFAULT 1");
                $hasIsActive = true;
            } catch (\Throwable $ex) {
                $hasIsActive = false;
            }
        }

        if ($hasIsActive) {
            $stmt = $pdo->prepare("INSERT INTO roles (name, slug, description, is_active) VALUES (?, ?, ?, 1)");
        } else {
            $stmt = $pdo->prepare("INSERT INTO roles (name, slug, description) VALUES (?, ?, ?)");
        }
        $stmt->execute([$name, $slug, $description]);
        $newRoleId = (int)$pdo->lastInsertId();

        if (!empty($selectedPermIds)) {
            $selectedPermIds = array_unique(array_filter(array_map('intval', $selectedPermIds)));
            $insStmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($selectedPermIds as $pId) {
                if ($pId > 0) {
                    $insStmt->execute([$newRoleId, $pId]);
                }
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
        $roles = $pdo->query("SELECT id, name, slug FROM roles ORDER BY id ASC")->fetchAll();

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
            $selectedPermIds = array_unique(array_filter(array_map('intval', $selectedPermIds)));
            $insStmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($selectedPermIds as $pId) {
                if ($pId > 0) {
                    $insStmt->execute([$id, $pId]);
                }
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

        try {
            $pdo->exec("ALTER TABLE roles ADD COLUMN is_active INTEGER DEFAULT 1");
        } catch (\Throwable $e) {}

        try {
            $stmt = $pdo->prepare("UPDATE roles SET is_active = 1 - COALESCE(is_active, 1) WHERE id = ?");
            $stmt->execute([$id]);
        } catch (\Throwable $e) {}

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

    /**
     * Add a dynamic permission module with standard or custom actions.
     */
    public function addModule(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $moduleName = trim($_POST['module_name'] ?? '');
        $moduleSlug = trim($_POST['module_slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $actions = $_POST['actions'] ?? [];
        $customActions = trim($_POST['custom_actions'] ?? '');
        $assignRoleIds = $_POST['assign_roles'] ?? [1, 2];

        if (empty($moduleName)) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/roles', 'Module name is required.', 'danger');
        }

        if (empty($moduleSlug)) {
            $moduleSlug = strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '_', $moduleName));
        } else {
            $moduleSlug = strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '_', $moduleSlug));
        }

        // Gather actions to create
        $allActions = [];
        if (is_array($actions)) {
            foreach ($actions as $act) {
                $act = strtolower(trim((string)$act));
                if ($act !== '') $allActions[] = $act;
            }
        }
        if (!empty($customActions)) {
            $customList = preg_split('/[\r\n,]+/', $customActions);
            foreach ($customList as $cAct) {
                $cAct = strtolower(trim((string)$cAct));
                if ($cAct !== '') $allActions[] = $cAct;
            }
        }

        $allActions = array_unique($allActions);
        if (empty($allActions)) {
            $allActions = ['view', 'create', 'edit', 'delete', 'approve', 'assign', 'export'];
        }

        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $insertIgnore = ($driver === 'mysql') ? 'INSERT IGNORE INTO' : 'INSERT OR IGNORE INTO';

        $permStmt = $pdo->prepare("{$insertIgnore} permissions (name, slug, module, description) VALUES (?, ?, ?, ?)");
        $createdCount = 0;
        $createdPermIds = [];

        foreach ($allActions as $act) {
            $permSlug = $moduleSlug . '.' . $act;
            $permName = ucfirst($act) . ' ' . $moduleName;
            $permDesc = !empty($description) ? "{$description} ({$act})" : "Permission to {$act} {$moduleName} records";

            $permStmt->execute([$permName, $permSlug, $moduleName, $permDesc]);

            $idStmt = $pdo->prepare("SELECT id FROM permissions WHERE slug = ? LIMIT 1");
            $idStmt->execute([$permSlug]);
            $pId = (int)$idStmt->fetchColumn();
            if ($pId > 0) {
                $createdPermIds[] = $pId;
                $createdCount++;
            }
        }

        // Automatically assign newly created permissions to specified roles (Super Admin always gets all)
        if (!empty($createdPermIds)) {
            $assignRoleIds = array_unique(array_filter(array_map('intval', (array)$assignRoleIds)));
            if (!in_array(1, $assignRoleIds, true)) {
                $assignRoleIds[] = 1;
            }

            $rpStmt = $pdo->prepare("{$insertIgnore} role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($assignRoleIds as $rId) {
                foreach ($createdPermIds as $pId) {
                    $rpStmt->execute([$rId, $pId]);
                }
            }
        }

        unset($_SESSION['user_permissions']);

        AuditService::log('CREATE_MODULE', 'Roles', null, "Created module '{$moduleName}' with {$createdCount} permissions and assigned to " . count($assignRoleIds) . " roles");

        redirect($_SERVER['HTTP_REFERER'] ?? '/roles', "Module '{$moduleName}' and {$createdCount} permissions created successfully.", 'success');
    }

    /**
     * Delete a custom permission module and its associated permissions.
     */
    public function deleteModule(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin']);
        $pdo = Database::getConnection();

        $module = trim($_POST['module'] ?? '');
        if (empty($module)) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/roles', 'Module name is required.', 'danger');
        }

        $coreModules = ['Applications', 'Applicants', 'Documents', 'Tasks', 'Payments', 'Staff', 'Settings'];
        if (in_array($module, $coreModules, true)) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/roles', "Core module '{$module}' cannot be deleted.", 'danger');
        }

        $pdo->prepare("DELETE FROM role_permissions WHERE permission_id IN (SELECT id FROM permissions WHERE module = ?)")->execute([$module]);
        $delStmt = $pdo->prepare("DELETE FROM permissions WHERE module = ?");
        $delStmt->execute([$module]);
        $count = $delStmt->rowCount();

        unset($_SESSION['user_permissions']);

        AuditService::log('DELETE_MODULE', 'Roles', null, "Deleted module '{$module}' and {$count} permissions");

        redirect($_SERVER['HTTP_REFERER'] ?? '/roles', "Module '{$module}' and its {$count} permissions were removed.", 'success');
    }

    /**
     * Add a single custom permission under an existing module.
     */
    public function addPermission(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $module = trim($_POST['module'] ?? '');
        $permName = trim($_POST['name'] ?? '');
        $permSlug = trim($_POST['slug'] ?? '');
        $permDesc = trim($_POST['description'] ?? '');
        $assignRoles = $_POST['assign_roles'] ?? [1];

        if (empty($module) || empty($permName)) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/roles', 'Module name and permission name are required.', 'danger');
        }

        if (empty($permSlug)) {
            $permSlug = strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '_', $module)) . '.' . strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '_', $permName));
        } else {
            $permSlug = strtolower(preg_replace('/[^a-zA-Z0-9_\.]+/', '.', $permSlug));
        }

        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $insertIgnore = ($driver === 'mysql') ? 'INSERT IGNORE INTO' : 'INSERT OR IGNORE INTO';

        $stmt = $pdo->prepare("{$insertIgnore} permissions (name, slug, module, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$permName, $permSlug, $module, $permDesc ?: "Permission to {$permName} in {$module}"]);

        $idStmt = $pdo->prepare("SELECT id FROM permissions WHERE slug = ? LIMIT 1");
        $idStmt->execute([$permSlug]);
        $pId = (int)$idStmt->fetchColumn();

        if ($pId > 0) {
            $assignRoles = array_unique(array_filter(array_map('intval', (array)$assignRoles)));
            if (!in_array(1, $assignRoles, true)) $assignRoles[] = 1;
            $rpStmt = $pdo->prepare("{$insertIgnore} role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($assignRoles as $rId) {
                $rpStmt->execute([$rId, $pId]);
            }
        }

        unset($_SESSION['user_permissions']);

        AuditService::log('CREATE_PERMISSION', 'Roles', $pId, "Created permission '{$permSlug}' under module '{$module}'");

        redirect($_SERVER['HTTP_REFERER'] ?? '/roles', "Permission '{$permName}' added to {$module} module.", 'success');
    }
}
