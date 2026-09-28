<?php
declare(strict_types=1);

/**
 * Functional Verification Test
 * Runs controller methods with simulated request environments, capturing output and checking for exceptions.
 */

// Setup simulated web server environment
define('IN_TEST_MODE', true);
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__) . '/public';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Database\DatabaseBootstrapper;
use App\Controllers\PublicWebsiteController;
use App\Controllers\AuthController;
use App\Controllers\Api\SearchApiController;

// Ensure database is bootstrapped
DatabaseBootstrapper::init(true);

$tests = [
    'Home Page (/)' => function() {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        ob_start();
        (new PublicWebsiteController())->home();
        $out = ob_get_clean();
        if (strlen($out) < 500) throw new \RuntimeException("Output too short: " . strlen($out));
        if (stripos($out, 'Visa') === false) throw new \RuntimeException("Missing 'Visa' in content");
        return strlen($out) . " bytes";
    },
    'About Page (/about)' => function() {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/about';
        ob_start();
        (new PublicWebsiteController())->about();
        $out = ob_get_clean();
        if (strlen($out) < 500) throw new \RuntimeException("Output too short: " . strlen($out));
        if (stripos($out, 'About') === false) throw new \RuntimeException("Missing 'About' in content");
        return strlen($out) . " bytes";
    },
    'Visa Services (/visa)' => function() {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/visa';
        ob_start();
        (new PublicWebsiteController())->visaServices();
        $out = ob_get_clean();
        if (strlen($out) < 500) throw new \RuntimeException("Output too short: " . strlen($out));
        if (stripos($out, 'Visa') === false) throw new \RuntimeException("Missing 'Visa' in content");
        return strlen($out) . " bytes";
    },
    'Recruitment Jobs (/jobs)' => function() {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/jobs';
        ob_start();
        (new PublicWebsiteController())->jobs();
        $out = ob_get_clean();
        if (strlen($out) < 500) throw new \RuntimeException("Output too short: " . strlen($out));
        return strlen($out) . " bytes";
    },
    'FAQ (/faq)' => function() {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/faq';
        ob_start();
        (new PublicWebsiteController())->faq();
        $out = ob_get_clean();
        if (strlen($out) < 500) throw new \RuntimeException("Output too short: " . strlen($out));
        return strlen($out) . " bytes";
    },
    'Contact (/contact)' => function() {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/contact';
        ob_start();
        (new PublicWebsiteController())->contact();
        $out = ob_get_clean();
        if (strlen($out) < 500) throw new \RuntimeException("Output too short: " . strlen($out));
        return strlen($out) . " bytes";
    },
    'Track Application (/track)' => function() {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/track';
        ob_start();
        (new PublicWebsiteController())->tracking();
        $out = ob_get_clean();
        if (strlen($out) < 500) throw new \RuntimeException("Output too short: " . strlen($out));
        return strlen($out) . " bytes";
    },
    'Login Page (/login)' => function() {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/login';
        ob_start();
        (new AuthController())->showLogin();
        $out = ob_get_clean();
        if (strlen($out) < 200) throw new \RuntimeException("Output too short: " . strlen($out));
        return strlen($out) . " bytes";
    },
    'Search API (/api/search)' => function() {
        $_SESSION['user'] = [
            'id' => 1,
            'role_id' => 1,
            'role_slug' => 'super-admin',
            'role_name' => 'Super Admin',
            'name' => 'Test Admin',
            'email' => 'admin@visatrack.test',
        ];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/search?q=dubai';
        $_GET['q'] = 'dubai';
        ob_start();
        (new SearchApiController())->search();
        $out = ob_get_clean();
        unset($_SESSION['user']);
        $json = json_decode($out, true);
        if (!is_array($json)) throw new \RuntimeException("Invalid JSON response: " . $out);
        return "JSON valid (" . strlen($out) . " bytes)";
    },
];

echo "=== VISA TRACK Functional Controller Tests ===\n";
$passed = 0;
$failed = 0;

foreach ($tests as $name => $fn) {
    try {
        $result = $fn();
        echo "PASS  [{$name}] -> {$result}\n";
        $passed++;
    } catch (\Throwable $e) {
        echo "FAIL  [{$name}] -> {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}\n";
        $failed++;
    }
}

echo "------------------------------------------------------------\n";
echo "PASSED: {$passed}  FAILED: {$failed}  TOTAL: " . ($passed + $failed) . "\n";

if ($failed === 0) {
    echo "SUCCESS: All functional controller tests passed!\n";
    exit(0);
} else {
    echo "FAILURE: {$failed} test(s) failed.\n";
    exit(1);
}
