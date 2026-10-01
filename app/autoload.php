<?php
declare(strict_types=1);

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize environment variables (.env file)
\App\Config\Env::init();

// Set system default timezone (default: Asia/Dubai / UAE)
date_default_timezone_set('Asia/Dubai');
$appTimezone = $_ENV['APP_TIMEZONE'] ?? getenv('APP_TIMEZONE') ?: 'Asia/Dubai';
if ($appTimezone && $appTimezone !== 'Asia/Dubai') {
    @date_default_timezone_set($appTimezone);
}

// Configure session security parameters before session start
if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli' && !headers_sent()) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

// Session inactivity timeout check (default: 2 hours)
$sessionTimeout = 7200; // 2 hours
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $sessionTimeout)) {
    // Session expired
    $wasAuth = !empty($_SESSION['user']);
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
    if ($wasAuth) {
        $_SESSION['flash'] = [
            'type' => 'warning',
            'message' => 'Your session has timed out due to inactivity. Please log in again.'
        ];
    }
}
$_SESSION['LAST_ACTIVITY'] = time();

// Global Helper Functions
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }
    if (!in_array($_SESSION['csrf_token'], $_SESSION['csrf_tokens'], true)) {
        $_SESSION['csrf_tokens'][] = $_SESSION['csrf_token'];
        if (count($_SESSION['csrf_tokens']) > 20) {
            $_SESSION['csrf_tokens'] = array_slice($_SESSION['csrf_tokens'], -20);
        }
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool {
    if (empty($token)) {
        return false;
    }
    if (!empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    if (!empty($_SESSION['csrf_tokens']) && is_array($_SESSION['csrf_tokens'])) {
        foreach ($_SESSION['csrf_tokens'] as $validToken) {
            if (hash_equals($validToken, $token)) {
                return true;
            }
        }
    }
    return false;
}

function e(?string $string): string {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

function auth_user(): ?array {
    if (!isset($_SESSION['user'])) {
        return null;
    }
    if (empty($_SESSION['user']['profile_photo']) && !empty($_SESSION['user']['avatar'])) {
        $_SESSION['user']['profile_photo'] = $_SESSION['user']['avatar'];
    }
    if (empty($_SESSION['user']['profile_photo']) && !empty($_SESSION['user']['id'])) {
        try {
            $pdo = \App\Config\Database::getConnection();
            $stmt = $pdo->prepare("SELECT COALESCE(profile_photo, avatar) FROM users WHERE id = ?");
            $stmt->execute([(int)$_SESSION['user']['id']]);
            $photo = $stmt->fetchColumn() ?: null;
            if ($photo) {
                $_SESSION['user']['profile_photo'] = $photo;
            }
        } catch (\Throwable $e) {}
    }
    return $_SESSION['user'];
}

function user_avatar_url(?array $user = null): ?string {
    $u = $user ?: auth_user();
    if (!$u) return null;
    $photo = trim((string)($u['profile_photo'] ?? $u['avatar'] ?? ''));
    if (empty($photo)) return null;
    if (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://')) {
        return $photo;
    }

    $publicDir = dirname(__DIR__) . '/public';
    $relPath = '';

    if (str_starts_with($photo, '/')) {
        $relPath = $photo;
    } elseif (str_starts_with($photo, 'uploads/')) {
        $relPath = '/' . $photo;
    } else {
        $relPath = '/uploads/avatars/' . ltrim($photo, '/');
    }

    if (file_exists($publicDir . $relPath)) {
        return $relPath;
    }

    return null;
}

function auth_customer(): ?array {
    return $_SESSION['customer'] ?? null;
}

function is_authenticated(): bool {
    return !empty($_SESSION['user']);
}

function is_customer_authenticated(): bool {
    return !empty($_SESSION['customer']);
}

function is_agent_authenticated(): bool {
    return !empty($_SESSION['agent_auth']);
}

function is_supplier_authenticated(): bool {
    return !empty($_SESSION['supplier_auth']);
}

function auth_agent(): ?array {
    return $_SESSION['agent_auth'] ?? null;
}

function auth_supplier(): ?array {
    return $_SESSION['supplier_auth'] ?? null;
}

function session_start_safe(): void {
    if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli' && !headers_sent()) {
        session_start();
    }
}

function is_impersonating(): bool {
    return !empty($_SESSION['original_admin_id']);
}

function original_admin_user(): ?array {
    return $_SESSION['original_admin'] ?? null;
}

function is_super_admin(?array $user = null): bool {
    $u = $user ?: auth_user();
    if (!$u) return false;
    return ($u['role_slug'] ?? '') === 'super-admin'
        || (int)($u['role_id'] ?? 0) === 1
        || ($u['role_name'] ?? '') === 'Super Admin';
}

function get_scoped_branch_id(int $requestedBranchId = 0, ?array $user = null): int {
    $u = $user ?: auth_user();
    if (!$u) return 0;
    if (is_super_admin($u)) {
        return $requestedBranchId > 0 ? $requestedBranchId : 0;
    }
    return (int)($u['branch_id'] ?? 1);
}

function can_switch_accounts(): bool {
    if (is_impersonating()) {
        return true;
    }
    $user = auth_user();
    if (!$user) {
        return false;
    }
    $roleSlug = $user['role_slug'] ?? '';
    $roleId = (int)($user['role_id'] ?? 0);
    return $roleSlug === 'super-admin' || $roleId === 1 || $roleSlug === 'admin' || $roleSlug === 'branch-manager';
}

function get_switchable_users(): array {
    $user = auth_user();
    if (!$user || !can_switch_accounts()) {
        return [];
    }
    try {
        $pdo = App\Config\Database::getConnection();
        $isSuperAdmin = (($user['role_slug'] ?? '') === 'super-admin' || ((int)($user['role_id'] ?? 0)) === 1);
        if (is_impersonating()) {
            $orig = original_admin_user();
            if (($orig['role_slug'] ?? '') === 'super-admin' || ((int)($orig['role_id'] ?? 0)) === 1) {
                $isSuperAdmin = true;
            }
        }

        if ($isSuperAdmin) {
            $stmt = $pdo->query("SELECT u.id, u.name, u.email, u.phone, u.avatar, u.designation, u.department, 
                                        u.role_id, r.name as role_name, r.slug as role_slug, 
                                        u.branch_id, b.name as branch_name, b.code as branch_code, u.is_active 
                                 FROM users u 
                                 JOIN roles r ON u.role_id = r.id 
                                 LEFT JOIN branches b ON u.branch_id = b.id 
                                 WHERE u.is_active = 1 
                                 ORDER BY r.id ASC, u.name ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        // Branch Manager / Branch Admin: isolate to their branch, exclude Super Admin to prevent privilege escalation
        $branchId = (int)($user['branch_id'] ?? 1);
        $stmt = $pdo->prepare("SELECT u.id, u.name, u.email, u.phone, u.avatar, u.designation, u.department, 
                                      u.role_id, r.name as role_name, r.slug as role_slug, 
                                      u.branch_id, b.name as branch_name, b.code as branch_code, u.is_active 
                               FROM users u 
                               JOIN roles r ON u.role_id = r.id 
                               LEFT JOIN branches b ON u.branch_id = b.id 
                               WHERE u.is_active = 1 AND u.branch_id = ? AND r.slug != 'super-admin' 
                               ORDER BY r.id ASC, u.name ASC");
        $stmt->execute([$branchId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (\Throwable $e) {
        return [];
    }
}

function set_flash(string $message, string $type = 'success'): void {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function user_permissions(bool $forceFresh = false): array {
    static $staticCache = [];
    $user = auth_user();
    if (!$user) return [];
    $roleId = (int)($user['role_id'] ?? 0);
    if (!$forceFresh && isset($staticCache[$roleId])) {
        return $staticCache[$roleId];
    }
    try {
        $pdo = App\Config\Database::getConnection();
        $stmt = $pdo->prepare("SELECT p.slug FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id WHERE rp.role_id = ?");
        $stmt->execute([$roleId]);
        $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $staticCache[$roleId] = $permissions;
        $_SESSION['user_permissions'] = $permissions;
        return $permissions;
    } catch (\Throwable $e) {
        return [];
    }
}

function user_can(string $permissionSlug): bool {
    $user = auth_user();
    if (!$user) return false;
    if (($user['role_slug'] ?? '') === 'super-admin' || ($user['role_name'] ?? '') === 'Super Admin' || ((int)($user['role_id'] ?? 0) === 1)) {
        return true;
    }
    $perms = user_permissions();
    if (in_array($permissionSlug, $perms, true)) {
        return true;
    }
    if (in_array('*', $perms, true)) {
        return true;
    }
    $parts = explode('.', $permissionSlug);
    if (count($parts) === 2 && in_array($parts[0] . '.*', $perms, true)) {
        return true;
    }
    $aliases = [
        // customers ↔ applicants (bidirectional)
        'customers.view'    => ['applicants.view', 'applicants.*'],
        'customers.manage'  => ['applicants.edit', 'applicants.create', 'applicants.*', 'customers.edit', 'customers.create'],
        'customers.create'  => ['applicants.create', 'applicants.*'],
        'customers.edit'    => ['applicants.edit', 'applicants.*'],
        'customers.delete'  => ['applicants.delete', 'applicants.*'],
        'applicants.view'   => ['customers.view', 'customers.*'],
        'applicants.manage' => ['customers.manage', 'customers.*'],
        // applications
        'applications.manage' => ['applications.edit', 'applications.create', 'applications.*'],
        // documents
        'documents.view'    => ['documents.*'],
        'documents.manage'  => ['documents.create', 'documents.edit', 'documents.*'],
        // tasks
        'tasks.view'        => ['tasks.*'],
        'tasks.manage'      => ['tasks.create', 'tasks.edit', 'tasks.*'],
        // appointments
        'appointments.view'   => ['appointments.*'],
        'appointments.manage' => ['appointments.create', 'appointments.edit', 'appointments.*'],
        // finance
        'visa_services.view' => ['visa.view', 'visa-services.view', 'visa_packages.view', 'visa_services.*'],
        'visa.view'          => ['visa_services.view', 'visa-services.view'],
        'finance.view'       => ['payments.view', 'payments.*'],
        'finance.manage'     => ['payments.manage', 'payments.*'],
        'payments.view'      => ['finance.view', 'payments.*'],
        // staff ↔ users (bidirectional)
        'staff.view'         => ['users.view', 'users.*', 'staff.*'],
        'staff.manage'       => ['users.manage', 'users.*', 'staff.*', 'staff.edit', 'staff.create'],
        'staff.create'       => ['users.create', 'staff.manage', 'users.manage', 'staff.*'],
        'staff.edit'         => ['users.edit', 'staff.manage', 'users.manage', 'staff.*'],
        'staff.delete'       => ['users.delete', 'staff.manage', 'users.manage', 'staff.*'],
        'users.view'         => ['staff.view', 'staff.*', 'users.*'],
        'users.manage'       => ['staff.manage', 'staff.*', 'users.*', 'users.edit', 'users.create'],
        'users.create'       => ['staff.create', 'users.manage', 'staff.manage', 'users.*'],
        'users.edit'         => ['staff.edit', 'users.manage', 'staff.manage', 'users.*'],
        'users.delete'       => ['staff.delete', 'users.manage', 'staff.manage', 'users.*'],
        // agents & suppliers
        'agents.view'        => ['suppliers.view', 'agents.*'],
        'agents.manage'      => ['agents.*', 'suppliers.manage'],
        'suppliers.manage'   => ['suppliers.*'],
    ];
    if (isset($aliases[$permissionSlug])) {
        foreach ($aliases[$permissionSlug] as $alias) {
            if (in_array($alias, $perms, true)) {
                return true;
            }
            // support wildcard aliases like 'applicants.*'
            if (str_ends_with($alias, '.*')) {
                $base = substr($alias, 0, -2);
                foreach ($perms as $perm) {
                    if ($perm === $alias || str_starts_with($perm, $base . '.')) {
                        return true;
                    }
                }
            }
        }
    }
    return false;
}

function has_permission(string $permissionSlug): bool {
    return user_can($permissionSlug);
}

function require_permission(string $permissionSlug): void {
    \App\Middleware\PermissionMiddleware::authorize($permissionSlug);
}

function user_has_role(array|string $roles): bool {
    $user = auth_user();
    if (!$user) return false;
    if (($user['role_slug'] ?? '') === 'super-admin' || ($user['role_name'] ?? '') === 'Super Admin') {
        return true;
    }
    
    if (is_string($roles)) {
        $roles = [$roles];
    }
    return in_array($user['role_slug'] ?? '', $roles, true) || in_array($user['role_name'] ?? '', $roles, true);
}

function redirect(string $url, ?string $message = null, string $type = 'success'): void {
    if ($message) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }
    if (!headers_sent()) {
        header("Location: {$url}");
    }
    if (defined('IN_TEST_MODE') && IN_TEST_MODE) {
        return;
    }
    exit;
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function app_url(string $path = ''): string {
    return \App\Config\App::url($path);
}

function format_currency(int|float|string|null $amount, string $currency = 'USD'): string {
    $val = is_numeric($amount) ? (float)$amount : 0.0;
    return '$' . number_format($val, 2);
}

function format_date(?string $date, string $format = 'd M Y'): string {
    if (empty($date) || $date === '—') return '—';
    $time = strtotime($date);
    return ($time !== false && $time > 0) ? date($format, $time) : '—';
}

function format_datetime(?string $date): string {
    if (empty($date) || $date === '—') return '—';
    $time = strtotime($date);
    return ($time !== false && $time > 0) ? date('d M Y, h:i A', $time) : '—';
}

function json_response(array $data, int $statusCode = 200): void {
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
    }
    echo json_encode($data);
    if (defined('IN_TEST_MODE') && IN_TEST_MODE) {
        return;
    }
    exit;
}
