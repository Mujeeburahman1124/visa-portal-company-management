<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Config\Database;
use PDO;

class PermissionMiddleware
{
    /**
     * Authorize user against a specific permission slug (e.g., 'staff.edit', 'roles.view')
     */
    public static function authorize(string $permissionSlug): void
    {
        AuthMiddleware::handle();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            redirect('/login', 'Session expired. Please sign in.', 'warning');
        }

        // Super admin bypass
        if (($user['role_slug'] ?? '') === 'super-admin' || ($user['role_id'] ?? 0) === 1) {
            return;
        }

        $roleId = (int)($user['role_id'] ?? 0);
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM role_permissions rp 
            JOIN permissions p ON rp.permission_id = p.id 
            WHERE rp.role_id = ? AND p.slug = ?");
        $stmt->execute([$roleId, $permissionSlug]);
        $hasPerm = ((int)$stmt->fetchColumn()) > 0;

        if (!$hasPerm) {
            // Check if AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => "Access denied. Required permission: {$permissionSlug}"]);
                exit;
            }

            http_response_code(403);
            redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard', "Access denied. You lack the required permission: {$permissionSlug}", 'danger');
        }
    }

    /**
     * Helper to verify permission in views and templates
     */
    public static function can(string $permissionSlug, ?int $roleId = null): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            return false;
        }

        if (($user['role_slug'] ?? '') === 'super-admin' || ($user['role_id'] ?? 0) === 1) {
            return true;
        }

        $targetRoleId = $roleId ?? (int)($user['role_id'] ?? 0);
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM role_permissions rp 
            JOIN permissions p ON rp.permission_id = p.id 
            WHERE rp.role_id = ? AND p.slug = ?");
        $stmt->execute([$targetRoleId, $permissionSlug]);
        return ((int)$stmt->fetchColumn()) > 0;
    }
}
