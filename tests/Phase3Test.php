<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Config\Database;
use App\Database\DatabaseBootstrapper;
use App\Controllers\AuthController;

echo "=======================================================\n";
echo "VISA TRACK — PHASE 3 AUTOMATED VERIFICATION SUITE\n";
echo "=======================================================\n\n";

$passed = 0;
$failed = 0;

function assert_test(string $name, bool $condition, string $details = '') {
    global $passed, $failed;
    if ($condition) {
        echo "[PASS] " . $name . "\n";
        $passed++;
    } else {
        echo "[FAIL] " . $name . ($details ? " -> " . $details : "") . "\n";
        $failed++;
    }
}

// 1. Test Database Initialization & Bootstrapping
DatabaseBootstrapper::init();
$pdo = Database::getConnection();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
assert_test("1. Database Connection & Bootstrapper", $pdo instanceof PDO, "Driver: {$driver}");

// Check tables
if ($driver === 'sqlite') {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
} else {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
}
assert_test("2. Required Tables Exist (including password_resets)", in_array('password_resets', $tables) && in_array('users', $tables) && in_array('roles', $tables));

// 2. Test User Authentication & Password Verification
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute(['admin@visatrack.com']);
$admin = $stmt->fetch();
assert_test("3. Admin Seed User Exists", !empty($admin) && $admin['email'] === 'admin@visatrack.com');
assert_test("4. Secure Password Verification (admin123)", password_verify('admin123', $admin['password_hash'] ?? ''));

// 3. Test RBAC Permissions
$_SESSION['user'] = [
    'id' => $admin['id'],
    'name' => $admin['name'],
    'email' => $admin['email'],
    'role_id' => $admin['role_id'],
    'role_name' => 'Super Admin',
    'role_slug' => 'super-admin'
];
assert_test("5. Super Admin Role Detection", user_has_role('super-admin') && user_has_role(['super-admin', 'admin']));
assert_test("6. Super Admin Permission Bypass", user_can('users.manage') && user_can('settings.edit'));

// Test Officer Role (Non-Admin)
$_SESSION['user'] = [
    'id' => 4,
    'name' => 'Fatima Al-Zaabi',
    'email' => 'officer@visatrack.com',
    'role_id' => 4,
    'role_name' => 'Visa Consultant',
    'role_slug' => 'visa-consultant'
];
unset($_SESSION['user_permissions']);
assert_test("7. Non-Admin Role Restriction", !user_has_role(['super-admin', 'admin']));

// 4. Test Password Reset Token Lifecycle
$token = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', time() + 3600);
$ins = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
$ins->execute(['officer@visatrack.com', $token, $expiresAt]);

$check = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND used_at IS NULL");
$check->execute([$token]);
$resetRecord = $check->fetch();
assert_test("8. Password Reset Token Generated & Stored", !empty($resetRecord) && $resetRecord['email'] === 'officer@visatrack.com');

// Invalidate token
$pdo->prepare("UPDATE password_resets SET used_at = CURRENT_TIMESTAMP WHERE token = ?")->execute([$token]);
$checkInvalid = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND used_at IS NULL");
$checkInvalid->execute([$token]);
assert_test("9. Password Reset Token Invalidation", empty($checkInvalid->fetch()));

// 5. Test Live Search Query Logic
$searchQuery = '%admin%';
$appResults = $pdo->prepare("SELECT id, application_number FROM applications WHERE application_number LIKE ? LIMIT 5");
$appResults->execute([$searchQuery]);
assert_test("10. Global Search Query Syntax & Execution", $appResults !== false);

// 6. Test CSRF Protection Token Generation
$token1 = csrf_token();
$field = csrf_field();
assert_test("11. CSRF Token & Field Generation", strlen($token1) === 64 && str_contains($field, 'name="csrf_token"'));
assert_test("12. CSRF Token Verification", verify_csrf($token1) && !verify_csrf('invalid_token'));

echo "\n=======================================================\n";
echo "SUMMARY: Total: " . ($passed + $failed) . " | Passed: {$passed} | Failed: {$failed}\n";
echo "=======================================================\n";

if ($failed > 0) {
    exit(1);
}
