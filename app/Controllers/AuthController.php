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

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT u.*, r.name as role_name, r.slug as role_slug, b.name as branch_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            LEFT JOIN branches b ON u.branch_id = b.id 
            WHERE LOWER(u.email) = LOWER(?)");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Check if account is active
            if ((int)($user['is_active'] ?? 1) !== 1) {
                redirect('/auth/login', 'Your account has been deactivated. Please contact your system administrator.', 'danger');
            }

            if (password_verify($password, $user['password_hash'])) {
                // Regenerate session ID to prevent session fixation attacks
                session_regenerate_id(true);

                unset($user['password_hash']);
                $_SESSION['user'] = $user;
                unset($_SESSION['user_permissions']); // Reset permissions cache

                // Update last login timestamp
                $pdo->prepare("UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$user['id']]);

                AuditService::log('LOGIN', 'Auth', (int)$user['id'], "Staff user {$user['name']} logged in successfully", null, (int)$user['id']);

                $redirect = $_SESSION['redirect_after_login'] ?? '/dashboard';
                unset($_SESSION['redirect_after_login']);
                redirect($redirect, "Welcome back, {$user['name']}!", 'success');
            }
        }

        redirect('/auth/login', 'Invalid email or password. Please verify your credentials and try again.', 'danger');
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
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiration

            $ins = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $ins->execute([$user['email'], $token, $expiresAt]);

            AuditService::log('PASSWORD_RESET_REQUEST', 'Auth', (int)$user['id'], "Password reset requested for {$user['email']}", null, (int)$user['id']);

            // Store demo token in session for easy local testing / simulation
            $_SESSION['demo_reset_link'] = "/auth/reset-password?token=" . $token;
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
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND used_at IS NULL AND expires_at > CURRENT_TIMESTAMP");
        $stmt->execute([$token]);
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
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND used_at IS NULL AND expires_at > CURRENT_TIMESTAMP");
        $stmt->execute([$token]);
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
     * Demo Quick Role Switcher for instant pairwise role testing
     */
    public function quickSwitch(): void
    {
        $userId = (int)($_GET['user_id'] ?? 0);
        if ($userId > 0) {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT u.*, r.name as role_name, r.slug as role_slug, b.name as branch_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                LEFT JOIN branches b ON u.branch_id = b.id 
                WHERE u.id = ? AND u.is_active = 1");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if ($user) {
                unset($user['password_hash']);
                $_SESSION['user'] = $user;
                unset($_SESSION['user_permissions']); // Reset permissions cache
                AuditService::log('ROLE_SWITCH', 'Auth', (int)$user['id'], "Switched active user session to {$user['name']} ({$user['role_name']})", null, (int)$user['id']);
                redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard', "Active role switched to {$user['name']} ({$user['role_name']})", 'info');
            }
        }
        redirect('/dashboard');
    }
}
