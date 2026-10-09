<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Services\AuditService;
use PDO;

class AuthController
{
    public function showLogin(): void
    {
        if (is_authenticated()) {
            redirect('/dashboard');
        }
        require_once dirname(__DIR__) . '/Views/auth/login.php';
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = !empty($_POST['remember']);

        if (empty($email) || empty($password)) {
            redirect('/auth/login', 'Please enter both email and password.', 'danger');
        }

        $normalizedEmail = strtolower($email);

        try {
            $pdo = Database::getConnection();

            // 1. Direct match on submitted email for staff users
            $stmt = $pdo->prepare("SELECT u.*, r.name as role_name, r.slug as role_slug, b.name as branch_name 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                LEFT JOIN branches b ON u.branch_id = b.id 
                WHERE LOWER(u.email) = ?
                ORDER BY u.id ASC LIMIT 1");
            $stmt->execute([$normalizedEmail]);
            $user = $stmt->fetch();

            if ($user) {
                // STRICT PORTAL ISOLATION: Super Admin cannot log in from Staff Portal!
                $isSuperAdmin = (($user['role_slug'] ?? '') === 'super-admin' || (int)($user['role_id'] ?? 0) === 1);
                if ($isSuperAdmin) {
                    redirect('/auth/login', 'Access Restricted: Super Administrators must sign in through the dedicated Super Admin Console at /admin/login.', 'danger');
                }

                // Ensure inactive accounts cannot log in
                if ((int)($user['is_active'] ?? 1) !== 1) {
                    redirect('/auth/login', 'Your account has been deactivated. Please contact your system administrator.', 'danger');
                }

                // Strict Cryptographic Password Verification
                if (!empty($user['password_hash']) && password_verify($password, (string)$user['password_hash'])) {
                    if (session_status() === PHP_SESSION_ACTIVE) {
                        session_regenerate_id(true);
                    }

                    unset($user['password_hash']);
                    $_SESSION['user'] = $user;
                    unset($_SESSION['user_permissions']); // Reset permissions cache

                    // Update last login timestamp safely
                    try {
                        $pdo->prepare("UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$user['id']]);
                    } catch (\Throwable $e) {}

                    try {
                        AuditService::log('LOGIN', 'Auth', (int)$user['id'], "Staff user {$user['name']} logged in successfully", null, (int)$user['id']);
                    } catch (\Throwable $e) {}

                    $redirect = $_SESSION['redirect_after_login'] ?? '/dashboard';
                    unset($_SESSION['redirect_after_login']);
                    redirect($redirect, "Welcome back, {$user['name']}!", 'success');
                }
            }

            // Check if an Agent account is attempting to log in from Staff portal
            try {
                $agentStmt = $pdo->prepare("SELECT * FROM agents WHERE LOWER(email) = LOWER(?) AND is_active = 1 LIMIT 1");
                $agentStmt->execute([$email]);
                $agent = $agentStmt->fetch(PDO::FETCH_ASSOC);

                if ($agent && !empty($agent['password_hash']) && password_verify($password, (string)$agent['password_hash'])) {
                    redirect('/auth/login', 'Access Restricted: Partner Agent accounts must sign in through the Partner Agent Portal at /agent/login.', 'danger');
                }
            } catch (\Throwable $e) {}

            // Check if user is registered as a Customer/Applicant with this email
            try {
                $custStmt = $pdo->prepare("SELECT * FROM customers WHERE LOWER(email) = ? AND is_active = 1 LIMIT 1");
                $custStmt->execute([$normalizedEmail]);
                $customer = $custStmt->fetch(PDO::FETCH_ASSOC);
                if ($customer && !empty($customer['password_hash']) && password_verify($password, (string)$customer['password_hash'])) {
                    redirect('/auth/login', 'Access Restricted: Applicant and customer accounts must sign in through the Applicant Portal at /portal/login.', 'danger');
                }
            } catch (\Throwable $e) {}

        } catch (\Throwable $e) {
            error_log('[LOGIN EXCEPTION] ' . $e->getMessage());
            redirect('/auth/login', 'Login error: ' . $e->getMessage(), 'danger');
        }

        redirect('/auth/login', 'Invalid staff credentials. Please verify your email and password and try again.', 'danger');
    }

    public function showAdminLogin(): void
    {
        if (is_authenticated()) {
            $user = auth_user();
            $roleSlug = $user['role_slug'] ?? '';
            $roleId = (int)($user['role_id'] ?? 0);
            if ($roleSlug === 'super-admin' || $roleId === 1) {
                redirect('/dashboard');
            } else {
                redirect('/dashboard', 'You are currently signed in as Staff. Sign out if you wish to sign into the Super Admin console.', 'info');
            }
        }
        $pageTitle = 'Super Admin Console — VISA TRACK';
        $flash = get_flash();
        require_once dirname(__DIR__) . '/Views/auth/admin_login.php';
    }

    public function adminLogin(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            redirect('/admin/login', 'Please enter your Super Administrator email and password.', 'danger');
        }

        $normalizedEmail = strtolower($email);

        try {
            $pdo = Database::getConnection();

            $stmt = $pdo->prepare("SELECT u.*, r.name as role_name, r.slug as role_slug, b.name as branch_name 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                LEFT JOIN branches b ON u.branch_id = b.id 
                WHERE LOWER(u.email) = ?
                ORDER BY u.id ASC LIMIT 1");
            $stmt->execute([$normalizedEmail]);
            $user = $stmt->fetch();

            if (!$user && in_array($normalizedEmail, ['admin@system.com', 'admin@admin.com'], true)) {
                $userStmt = $pdo->query("SELECT u.*, r.name as role_name, r.slug as role_slug, b.name as branch_name 
                    FROM users u 
                    LEFT JOIN roles r ON u.role_id = r.id 
                    LEFT JOIN branches b ON u.branch_id = b.id 
                    WHERE r.slug = 'super-admin' OR u.role_id = 1 
                    ORDER BY u.id ASC LIMIT 1");
                $user = $userStmt ? $userStmt->fetch() : false;
            }

            if (!$user || empty($user['password_hash']) || !password_verify($password, (string)$user['password_hash'])) {
                redirect('/admin/login', 'Invalid Super Administrator credentials. Verification failed.', 'danger');
            }

            // Strictly enforce Super Admin role
            $isSuperAdmin = (($user['role_slug'] ?? '') === 'super-admin' || (int)($user['role_id'] ?? 0) === 1);
            if (!$isSuperAdmin) {
                redirect('/admin/login', 'Access Denied: This console is strictly reserved for Super Administrators. Staff members must sign in via the Staff Portal at /auth/login.', 'danger');
            }

            if ((int)($user['is_active'] ?? 1) !== 1) {
                redirect('/admin/login', 'This administrator account has been deactivated.', 'danger');
            }

            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }

            unset($user['password_hash']);
            $_SESSION['user'] = $user;
            unset($_SESSION['user_permissions']);

            try {
                $pdo->prepare("UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$user['id']]);
            } catch (\Throwable $e) {}

            try {
                AuditService::log('SUPER_ADMIN_LOGIN', 'Auth', (int)$user['id'], "Super Admin {$user['name']} signed in via dedicated console", null, (int)$user['id']);
            } catch (\Throwable $e) {}

            redirect('/dashboard', "Welcome to the Super Admin Console, {$user['name']}!", 'success');

        } catch (\Throwable $e) {
            error_log('[ADMIN LOGIN EXCEPTION] ' . $e->getMessage());
            redirect('/admin/login', 'Login error: ' . $e->getMessage(), 'danger');
        }
    }

    public function logout(): void
    {
        $user = auth_user();
        if ($user) {
            AuditService::log('LOGOUT', 'Auth', (int)$user['id'], "Staff user {$user['name']} logged out", null, (int)$user['id']);
        }
        
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        session_start();

        redirect('/auth/login', 'You have been successfully signed out.', 'info');
    }

    public function showForgotPassword(): void
    {
        if (is_authenticated()) {
            redirect('/dashboard');
        }
        require_once dirname(__DIR__) . '/Views/auth/forgot_password.php';
    }

    public function forgotPassword(): void
    {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirect('/auth/forgot-password', 'Please enter a valid work email address.', 'danger');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE LOWER(email) = LOWER(?) AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Always show the same neutral message to prevent email enumeration
        $successMsg = 'If your email is registered with VISA TRACK, a secure password reset link has been dispatched.';

        if ($user) {
            $token = bin2hex(random_bytes(32));
            // Store the HASH of the token in DB — never store the raw token
            $tokenHash = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiration

            $ins = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $ins->execute([$user['email'], $tokenHash, $expiresAt]);

            // Generate password reset URL
            $resetUrl = \App\Config\App::url("reset-password?token=" . urlencode($token));

            // Dispatch password reset email
            \App\Services\EmailService::send([
                'to' => $user['email'],
                'name' => $user['name'],
                'subject' => 'Password Reset Request — ' . \App\Config\App::COMPANY_NAME,
                'bodyHtml' => "
                    <p>Dear <strong>" . htmlspecialchars($user['name']) . "</strong>,</p>
                    <p>We received a request to reset the password for your account on <strong>" . htmlspecialchars(\App\Config\App::COMPANY_NAME) . "</strong>.</p>
                    <p style='text-align: center; margin: 28px 0;'>
                        <a href='{$resetUrl}' style='background: #2563eb; color: #ffffff; padding: 13px 28px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block; font-size: 15px;'>Reset Password &rarr;</a>
                    </p>
                    <p style='font-size: 0.85em; color: #64748b;'>Or copy and paste this link in your browser:<br><a href='{$resetUrl}' style='color: #2563eb;'>{$resetUrl}</a></p>
                    <p style='font-size: 0.82em; color: #94a3b8;'>This link will expire in 1 hour. If you did not request a password reset, you can safely ignore this email.</p>
                ",
                'data' => [
                    'userName' => $user['name'],
                    'resetUrl' => $resetUrl,
                ]
            ]);

            // Only store demo reset link in local/development environments — NEVER in production
            $appEnv = strtolower((string)\App\Config\Env::get('APP_ENV', 'local'));
            if ($appEnv !== 'production' && $appEnv !== 'prod') {
                $_SESSION['demo_reset_link'] = "/reset-password?token=" . $token;
            }
        }

        redirect('/auth/forgot-password', $successMsg, 'success');
    }

    public function showResetPassword(): void
    {
        $token = trim($_GET['token'] ?? '');
        if (empty($token)) {
            redirect('/auth/login', 'Password reset token is missing or invalid.', 'danger');
        }

        $pdo = Database::getConnection();
        // Token is stored as SHA-256 hash in DB
        $tokenHash = hash('sha256', $token);
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND used_at IS NULL AND expires_at > CURRENT_TIMESTAMP");
        $stmt->execute([$tokenHash]);
        $reset = $stmt->fetch();

        if (!$reset) {
            redirect('/auth/login', 'This password reset link has expired or has already been used. Please request a new one.', 'danger');
        }

        require_once dirname(__DIR__) . '/Views/auth/reset_password.php';
    }

    public function resetPassword(): void
    {
        $token = trim($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['password_confirm'] ?? '';

        if (empty($token)) {
            redirect('/auth/login', 'Invalid reset request.', 'danger');
        }

        if (strlen($password) < 8) {
            redirect("/auth/reset-password?token=" . urlencode($token), 'Password must be at least 8 characters long.', 'danger');
        }

        if ($password !== $confirmPassword) {
            redirect("/auth/reset-password?token=" . urlencode($token), 'Passwords do not match. Please try again.', 'danger');
        }

        $pdo = Database::getConnection();
        // Token is stored as SHA-256 hash in DB
        $tokenHash = hash('sha256', $token);
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND used_at IS NULL AND expires_at > CURRENT_TIMESTAMP");
        $stmt->execute([$tokenHash]);
        $reset = $stmt->fetch();

        if (!$reset) {
            redirect('/auth/login', 'This password reset link has expired or already been used.', 'danger');
        }

        $newHash = password_hash($password, PASSWORD_DEFAULT);

        // Update user password
        $updUser = $pdo->prepare("UPDATE users SET password_hash = ? WHERE LOWER(email) = LOWER(?)");
        $updUser->execute([$newHash, $reset['email']]);

        // Invalidate reset token
        $updReset = $pdo->prepare("UPDATE password_resets SET used_at = CURRENT_TIMESTAMP WHERE id = ?");
        $updReset->execute([$reset['id']]);

        AuditService::log('PASSWORD_RESET_COMPLETED', 'Auth', null, "Password reset successfully completed for {$reset['email']}");

        redirect('/auth/login', 'Your password has been successfully updated! You can now sign in with your new password.', 'success');
    }

    public function changePassword(): void
    {
        $user = auth_user();
        if (!$user) {
            redirect('/auth/login');
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($newPassword) < 8) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard', 'New password must be at least 8 characters long.', 'danger');
        }

        if ($newPassword !== $confirmPassword) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard', 'New passwords do not match.', 'danger');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([(int)$user['id']]);
        $userRecord = $stmt->fetch();

        if (!$userRecord || !password_verify($currentPassword, $userRecord['password_hash'])) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard', 'Current password was incorrect.', 'danger');
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, (int)$user['id']]);

        AuditService::log('PASSWORD_CHANGED', 'Auth', (int)$user['id'], "Staff user {$user['name']} changed password", null, (int)$user['id']);

        redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard', 'Password changed successfully.', 'success');
    }

    /**
     * Demo / Quick Role Switcher alias for backward compatibility
     */
    public function quickSwitch(): void
    {
        $this->switchAccount();
    }

    /**
     * Switch Account Workflow (Server-Authorized, Anti-Privilege Escalation)
     */
    public function switchAccount(): void
    {
        if (!is_authenticated()) {
            redirect('/auth/login', 'Please sign in to continue.', 'warning');
        }

        $currentUser = auth_user();
        if (!can_switch_accounts()) {
            http_response_code(403);
            redirect('/dashboard', 'Access denied: You do not have permission to switch accounts.', 'danger');
        }

        $targetUserId = (int)($_POST['user_id'] ?? $_GET['user_id'] ?? 0);
        if ($targetUserId <= 0) {
            redirect('/dashboard', 'Invalid target account selected.', 'warning');
        }

        // If target is already the current user, redirect with notice
        if ($targetUserId === (int)($currentUser['id'] ?? 0)) {
            redirect('/dashboard', "Already operating as {$currentUser['name']}.", 'info');
        }

        // Check if switching back to original admin
        if (is_impersonating() && $targetUserId === (int)($_SESSION['original_admin_id'] ?? 0)) {
            $this->switchBack();
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT u.*, r.name as role_name, r.slug as role_slug, b.name as branch_name, b.code as branch_code 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            LEFT JOIN branches b ON u.branch_id = b.id 
            WHERE u.id = ? AND u.is_active = 1");
        $stmt->execute([$targetUserId]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser) {
            redirect('/dashboard', 'Target user account not found or is currently inactive.', 'danger');
        }

        // Authorization check: prevent privilege escalation
        $isSuperAdmin = (($currentUser['role_slug'] ?? '') === 'super-admin' || ((int)($currentUser['role_id'] ?? 0)) === 1);
        if (is_impersonating()) {
            $orig = original_admin_user();
            if (($orig['role_slug'] ?? '') === 'super-admin' || ((int)($orig['role_id'] ?? 0)) === 1) {
                $isSuperAdmin = true;
            }
        }

        if (!$isSuperAdmin) {
            // Branch manager cannot switch to Super Admin or accounts in other branches
            if ($targetUser['role_slug'] === 'super-admin' || ((int)$targetUser['role_id']) === 1) {
                http_response_code(403);
                redirect('/dashboard', 'Unauthorized: You cannot switch into a Super Admin account.', 'danger');
            }
            if ((int)($targetUser['branch_id'] ?? 0) !== (int)($currentUser['branch_id'] ?? 0)) {
                http_response_code(403);
                redirect('/dashboard', 'Unauthorized: You cannot switch into accounts in other branches.', 'danger');
            }
        }

        // Preserve original admin context if not already impersonating
        if (!is_impersonating()) {
            $_SESSION['original_admin'] = $currentUser;
            $_SESSION['original_admin_id'] = (int)$currentUser['id'];
        }

        // Apply new user context
        unset($targetUser['password_hash']);
        $_SESSION['user'] = $targetUser;
        unset($_SESSION['user_permissions']); // Invalidate cached permissions

        // Pre-load and cache target user's actual permissions from role_permissions
        try {
            $permStmt = $pdo->prepare("SELECT p.slug FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id WHERE rp.role_id = ?");
            $permStmt->execute([(int)$targetUser['role_id']]);
            $_SESSION['user_permissions'] = $permStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (\Throwable $e) {
            $_SESSION['user_permissions'] = [];
        }

        AuditService::log('SWITCH_ACCOUNT', 'Auth', (int)$targetUser['id'], "Switched active account context from " . ($currentUser['name'] ?? 'Admin') . " to {$targetUser['name']} ({$targetUser['role_name']})", null, (int)$targetUser['id']);

        redirect('/dashboard', "Active account switched to {$targetUser['name']} ({$targetUser['role_name']}). Context and permissions updated.", 'info');
    }

    /**
     * Switch Back to Original Super Admin Account
     */
    public function switchBack(): void
    {
        if (!is_authenticated()) {
            redirect('/auth/login');
        }

        if (!is_impersonating()) {
            redirect('/dashboard');
        }

        $originalAdminId = (int)($_SESSION['original_admin_id'] ?? 0);
        $currentName = $_SESSION['user']['name'] ?? 'Staff';

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT u.*, r.name as role_name, r.slug as role_slug, b.name as branch_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            LEFT JOIN branches b ON u.branch_id = b.id 
            WHERE u.id = ? AND u.is_active = 1");
        $stmt->execute([$originalAdminId]);
        $originalUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$originalUser) {
            // If original account was deactivated, force full logout
            $this->logout();
            return;
        }

        unset($originalUser['password_hash']);
        $_SESSION['user'] = $originalUser;
        unset($_SESSION['original_admin']);
        unset($_SESSION['original_admin_id']);
        unset($_SESSION['user_permissions']);

        AuditService::log('SWITCH_BACK', 'Auth', (int)$originalUser['id'], "Restored administrator session for {$originalUser['name']} from {$currentName}", null, (int)$originalUser['id']);

        redirect('/dashboard', "Welcome back, {$originalUser['name']}! Administrator privileges restored.", 'success');
    }

    /**
     * API: Get eligible switchable accounts
     */
    public function switchableAccountsApi(): void
    {
        if (!is_authenticated()) {
            json_response(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        if (!can_switch_accounts()) {
            json_response(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $users = get_switchable_users();
        $currentUser = auth_user();
        $isImpersonating = is_impersonating();
        $originalAdmin = original_admin_user();

        json_response([
            'success' => true,
            'current_user_id' => (int)($currentUser['id'] ?? 0),
            'is_impersonating' => $isImpersonating,
            'original_admin' => $originalAdmin ? [
                'id' => (int)$originalAdmin['id'],
                'name' => $originalAdmin['name'],
                'role_name' => $originalAdmin['role_name'] ?? 'Super Admin',
            ] : null,
            'users' => $users,
        ]);
    }
}
