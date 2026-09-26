<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';

use App\Config\Database;
use App\Database\DatabaseBootstrapper;

DatabaseBootstrapper::init(true);

echo "======================================================================\n";
echo "SYSTEMATIC ALL-ROUTE CRAWLER & ERROR AUDIT\n";
echo "======================================================================\n\n";

$pdo = Database::getConnection();

// Fetch sample IDs for parameter testing
$serviceId = (int)($pdo->query("SELECT id FROM visa_services LIMIT 1")->fetchColumn() ?: 1);
$jobId = (int)($pdo->query("SELECT id FROM jobs LIMIT 1")->fetchColumn() ?: 1);
$appId = (int)($pdo->query("SELECT id FROM applications LIMIT 1")->fetchColumn() ?: 1);
$custId = (int)($pdo->query("SELECT id FROM customers LIMIT 1")->fetchColumn() ?: 1);
$staffId = (int)($pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn() ?: 1);
$roleId = (int)($pdo->query("SELECT id FROM roles LIMIT 1")->fetchColumn() ?: 1);

// List of all routes across all application modules
$routesToTest = [
    // Public Website
    'GET' => [
        '/',
        '/about',
        '/visa-services',
        '/visa-service?id=' . $serviceId,
        '/jobs',
        '/job?id=' . $jobId,
        '/jobs/apply?job_id=' . $jobId,
        '/visa-enquiry',
        '/track',
        '/contact',
        '/faq',
        '/sitemap.xml',
        '/robots.txt',

        // Auth Pages
        '/auth/login',
        '/auth/forgot-password',
        '/portal/login',
        '/agent-portal/login',
        '/supplier-portal/login',

        // Admin / Staff Protected Routes
        '/dashboard',
        '/applications',
        '/applications/create',
        '/applications/show?id=' . $appId,
        '/applications/edit?id=' . $appId,
        '/customers',
        '/customers/create',
        '/customers/show?id=' . $custId,
        '/customers/edit?id=' . $custId,
        '/documents',
        '/payments',
        '/payments/history',
        '/payments/links',
        '/payments/wallets',
        '/appointments',
        '/tasks',
        '/reports',
        '/suppliers',
        '/suppliers/payments',
        '/suppliers/wallet',
        '/agents',
        '/attendance',
        '/payroll',
        '/payroll/history',
        '/inventory',
        '/inventory/history',
        '/branches',
        '/staff',
        '/staff/show?id=' . $staffId,
        '/roles',
        '/roles/edit?id=' . $roleId,
        '/settings',
        '/notifications',
        '/notifications/admin',
        '/notifications/preferences',
        '/audit-logs',
        '/action-center',
        '/downloads',

        // Customer Portal
        '/portal/dashboard',
        '/portal/documents',
        '/portal/invoices',
        '/portal/appointments',
        '/portal/notifications',
        '/portal/support',
        '/portal/wallet',

        // Agent Portal
        '/agent-portal/dashboard',
        '/agent-portal/applications',
        '/agent-portal/create-application',
        '/agent-portal/profile',

        // Supplier Portal
        '/supplier-portal/dashboard',
        '/supplier-portal/applications',
        '/supplier-portal/payments',
        '/supplier-portal/profile',
    ]
];

// Setup test session credentials for authenticated routes
$staffUser = $pdo->query("SELECT * FROM users WHERE role_id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$customerUser = $pdo->query("SELECT * FROM customers LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$agentUser = $pdo->query("SELECT * FROM agents LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$supplierUser = $pdo->query("SELECT * FROM suppliers LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$results = [];
$totalPassed = 0;
$totalFailed = 0;

$baseUrl = 'http://127.0.0.1:8000';

foreach ($routesToTest['GET'] as $urlPath) {
    $fullUrl = $baseUrl . $urlPath;

    // Use cURL to fetch the response
    $ch = curl_init($fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true);

    // Set cookie headers for session simulation if needed
    if (str_starts_with($urlPath, '/portal/')) {
        curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=test_customer_session');
    } elseif (str_starts_with($urlPath, '/agent-portal/')) {
        curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=test_agent_session');
    } elseif (str_starts_with($urlPath, '/supplier-portal/')) {
        curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=test_supplier_session');
    } else {
        curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=test_staff_session');
    }

    $rawResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $body = substr($rawResponse ?: '', $headerSize);

    // Detect fatal errors, SQL errors, warnings or exceptions
    $hasFatal = str_contains($body, 'Fatal error') || str_contains($body, 'Uncaught PDOException') || str_contains($body, 'SQLSTATE[');
    $hasWarning = str_contains($body, 'Warning:') || str_contains($body, 'Notice:');

    if ($hasFatal || ($httpCode >= 500)) {
        $status = 'FAIL';
        $totalFailed++;
    } else {
        $status = 'PASS';
        $totalPassed++;
    }

    $results[] = [
        'path' => $urlPath,
        'http_code' => $httpCode,
        'status' => $status,
        'has_fatal' => $hasFatal,
        'has_warning' => $hasWarning,
        'snippet' => substr(strip_tags($body), 0, 120)
    ];
}

echo "=== ROUTE CRAWL RESULTS ===\n\n";

foreach ($results as $res) {
    $badge = $res['status'] === 'PASS' ? '[PASS]' : '[FAIL]';
    echo sprintf("%-7s %-40s (HTTP %d)", $badge, $res['path'], $res['http_code']) . "\n";
    if ($res['status'] === 'FAIL' || $res['has_fatal'] || $res['has_warning']) {
        echo "   Details: " . trim(preg_replace('/\s+/', ' ', $res['snippet'])) . "\n";
    }
}

echo "\n======================================================================\n";
echo "CRAWL SUMMARY:\n";
echo "TOTAL ROUTE TESTS : " . count($results) . "\n";
echo "PASSED            : {$totalPassed}\n";
echo "FAILED            : {$totalFailed}\n";
echo "======================================================================\n";
