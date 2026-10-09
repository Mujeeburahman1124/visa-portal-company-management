<?php
declare(strict_types=1);

define('IN_TEST_MODE', true);
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__) . '/public';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Controllers\AuthController;

$_POST['email'] = 'admin@system.com';
$_POST['password'] = 'password';

$ctrl = new AuthController();
ob_start();
$ctrl->login();
$out = ob_get_clean();

echo "Session User ID: " . ($_SESSION['user']['id'] ?? 'NONE') . "\n";
echo "Session User Name: " . ($_SESSION['user']['name'] ?? 'NONE') . "\n";
echo "Session Role: " . ($_SESSION['user']['role_slug'] ?? 'NONE') . "\n";

if (!empty($_SESSION['user'])) {
    echo "SUCCESS: Admin login verified! User: " . $_SESSION['user']['name'] . "\n";
} else {
    echo "FAIL: Admin login failed.\n";
    exit(1);
}

// Test 2: Agent Login
$_SESSION = [];
$_POST['email'] = 'agent@skylinetravel.com';
$_POST['password'] = 'password';

$ctrl = new AuthController();
ob_start();
$ctrl->login();
$out = ob_get_clean();

if (!empty($_SESSION['agent_auth'])) {
    echo "SUCCESS: Agent login verified! Agent: " . $_SESSION['agent_auth']['contact_person'] . " (" . $_SESSION['agent_auth']['company_name'] . ")\n";
    exit(0);
} else {
    echo "FAIL: Agent login failed.\n";
    exit(1);
}
