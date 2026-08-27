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
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function e(?string $string): string {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

function auth_user(): ?array {
    return $_SESSION['user'] ?? null;
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

function set_flash(string $message, string $type = 'success'): void {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function user_permissions(): array {
    $user = auth_user();
    if (!$user) return [];
    if (isset($_SESSION['user_permissions'])) {
        return $_SESSION['user_permissions'];
    }
    try {
        $pdo = App\Config\Database::getConnection();
        $stmt = $pdo->prepare("SELECT p.slug FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id WHERE rp.role_id = ?");
        $stmt->execute([(int)$user['role_id']]);
        $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $_SESSION['user_permissions'] = $permissions;
        return $permissions;
    } catch (\Throwable $e) {
        return [];
    }
}

function user_can(string $permissionSlug): bool {
    $user = auth_user();
    if (!$user) return false;
    if (($user['role_slug'] ?? '') === 'super-admin' || ($user['role_name'] ?? '') === 'Super Admin' || ($user['role_id'] ?? 0) === 1) {
        return true;
    }
    $perms = user_permissions();
    return in_array($permissionSlug, $perms, true);
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
    header("Location: {$url}");
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

function format_currency(float $amount, string $currency = 'USD'): string {
    return '$' . number_format($amount, 2);
}

function format_date(?string $date, string $format = 'd M Y'): string {
    if (empty($date)) return '—';
    return date($format, strtotime($date));
}

function format_datetime(?string $date): string {
    if (empty($date)) return '—';
    return date('d M Y, h:i A', strtotime($date));
}

function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
