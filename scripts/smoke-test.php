<?php
/**
 * Authenticated HTTP smoke test (GET only — never submits forms).
 *
 * 1. Seeds REAL sessions (staff super-admin, customer, agent, supplier) read from the DB
 *    into an isolated session folder (scratch/test-sessions) — no app code involved.
 * 2. Crawls key pages with those sessions against a local server and flags:
 *    HTTP >= 500, PHP fatals / SQL errors in the body, and unexpected redirects to a login page.
 *
 * Usage:
 *   php -S 127.0.0.1:8765 -t public -d session.save_path="<abs>/scratch/test-sessions" public/index.php
 *   php scripts/smoke-test.php http://127.0.0.1:8765
 */
require_once dirname(__DIR__) . '/app/autoload.php';
\App\Database\DatabaseBootstrapper::init(true);
$pdo = \App\Config\Database::getConnection();

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8765', '/');
$sessDir = dirname(__DIR__) . '/scratch/test-sessions';
if (!is_dir($sessDir)) { mkdir($sessDir, 0777, true); }

function one(PDO $pdo, string $sql): ?array {
    try { $r = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC); return $r ?: null; } catch (Throwable $e) { return null; }
}
function seedSession(string $dir, string $id, array $data): void {
    // PHP's default "php" serialize handler format: key|serialized_value
    $payload = '';
    foreach ($data + ['LAST_ACTIVITY' => time()] as $k => $v) { $payload .= $k . '|' . serialize($v); }
    file_put_contents($dir . '/sess_' . $id, $payload);
}

$staff = one($pdo, "SELECT u.*, r.name AS role_name, r.slug AS role_slug, b.name AS branch_name FROM users u LEFT JOIN roles r ON u.role_id=r.id LEFT JOIN branches b ON u.branch_id=b.id WHERE u.role_id=1 AND u.is_active=1 ORDER BY u.id LIMIT 1");
if ($staff) { unset($staff['password_hash']); }
$customer = one($pdo, "SELECT * FROM customers ORDER BY id LIMIT 1");
if ($customer) { unset($customer['password_hash'], $customer['password']); }
$agent = one($pdo, "SELECT id, agent_code, company_name, contact_person, email FROM agents ORDER BY id LIMIT 1");
$supplier = one($pdo, "SELECT id, supplier_code, company_name, contact_person, email FROM suppliers ORDER BY id LIMIT 1");

$sessions = [];
if ($staff)    { seedSession($sessDir, 'smokestaff0001',    ['user' => $staff]);             $sessions['staff'] = 'smokestaff0001'; }
if ($customer) { seedSession($sessDir, 'smokecust00001',    ['customer' => $customer]);      $sessions['customer'] = 'smokecust00001'; }
if ($agent)    { seedSession($sessDir, 'smokeagent0001',    ['agent_auth' => $agent]);       $sessions['agent'] = 'smokeagent0001'; }

$appId  = (int)($pdo->query("SELECT id FROM applications ORDER BY id LIMIT 1")->fetchColumn() ?: 0);
$custId = (int)($customer['id'] ?? 0);
$docId  = (int)($pdo->query("SELECT id FROM documents ORDER BY id DESC LIMIT 1")->fetchColumn() ?: 0);
$taskId = (int)($pdo->query("SELECT id FROM tasks WHERE proof_attachment IS NOT NULL AND proof_attachment <> '' ORDER BY id LIMIT 1")->fetchColumn() ?: 0);

$routes = [
    'public' => ['/', '/about', '/jobs', '/visa-enquiry', '/contact', '/faq', '/portal/track',
                 '/auth/login', '/admin/login', '/auth/forgot-password', '/portal/login', '/portal/forgot-password',
                 '/agent/login'],
    'staff' => ['/dashboard', '/applications', '/applications/create', "/applications/show?id=$appId", "/applications/edit?id=$appId",
                '/customers', '/customers/create', "/customers/show?id=$custId", '/documents', "/documents/profile?id=$appId",
                "/documents/preview?id=$docId", '/payments', '/payments/history', '/payments/links', '/payments/wallets',
                '/appointments', '/tasks', "/tasks/proof?id=$taskId", '/reports', '/agents', '/attendance',
                '/payroll', '/inventory', '/branches', '/staff', '/roles', '/settings', '/notifications', '/audit-logs',
                '/action-center'],
    'customer' => ['/portal/dashboard', '/portal/documents', '/portal/invoices', '/portal/appointments',
                   '/portal/notifications', '/portal/support', '/portal/wallet'],
    'agent' => ['/agent/dashboard', '/agent/applications', '/agent/profile'],
];

$pass = 0; $fail = 0; $warn = 0; $rows = [];
foreach ($routes as $actor => $paths) {
    if ($actor !== 'public' && empty($sessions[$actor])) {
        $rows[] = sprintf('[SKIP] %-9s (no %s account in DB to test with)', $actor, $actor);
        continue;
    }
    foreach ($paths as $p) {
        $ch = curl_init($base . $p);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 60]);
        if ($actor !== 'public') { curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . $sessions[$actor]); }
        $raw = (string)curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $hs = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $loc = (string)curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        $body = substr($raw, $hs);

        $fatal = preg_match('/Fatal error|Uncaught |SQLSTATE\[|Parse error/i', $body)
              || str_contains($body, 'SUPER ADMIN DIAGNOSTIC CONSOLE');
        $phpWarn = preg_match('/<b>(Warning|Notice|Deprecated)<\/b>:/', $body);
        $toLogin = $actor !== 'public' && $code >= 300 && $code < 400 && preg_match('#/(login|auth/login)#', $loc);

        if ($code === 0 || $code === 404 || $code >= 500 || $fatal || $toLogin) {
            $fail++;
            $why = $code === 0 ? 'no response' : ($toLogin ? "redirected to login ($loc)" : ($fatal ? 'error in page body' : "HTTP $code"));
            $snip = trim(preg_replace('/\s+/', ' ', substr(strip_tags($body), 0, 200)));
            $rows[] = sprintf('[FAIL] %-9s %-38s HTTP %d — %s%s', $actor, $p, $code, $why, $snip ? "\n         $snip" : '');
        } else {
            $pass++;
            if ($phpWarn) { $warn++; }
            $rows[] = sprintf('[PASS] %-9s %-38s HTTP %d%s%s', $actor, $p, $code, $loc ? " -> $loc" : '', $phpWarn ? '  (PHP warning in output)' : '');
        }
    }
}

echo implode("\n", $rows), "\n\nPASSED: $pass   FAILED: $fail   WITH PHP WARNINGS: $warn\n";
// Clean up seeded sessions
foreach (glob($sessDir . '/sess_smoke*') as $f) { @unlink($f); }
exit($fail > 0 ? 1 : 0);
