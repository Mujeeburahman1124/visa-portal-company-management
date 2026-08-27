<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Config\App;
use App\Config\Database;
use App\Database\DatabaseBootstrapper;

DatabaseBootstrapper::init();

echo "Verifying view rendering & templates integrity...\n";

$views = [
    'auth/login' => 'app/Views/auth/login.php',
    'dashboard/index' => 'app/Views/dashboard/index.php',
    'applications/index' => 'app/Views/applications/index.php',
    'applications/create' => 'app/Views/applications/create.php',
    'tracking/index' => 'app/Views/tracking/index.php',
    'action-center/index' => 'app/Views/action-center/index.php',
    'documents/index' => 'app/Views/documents/index.php',
];

$allPassed = true;
foreach ($views as $name => $path) {
    $fullPath = dirname(__DIR__) . '/' . $path;
    if (file_exists($fullPath)) {
        // Test php lint syntax
        $phpBin = defined('PHP_BINARY') && file_exists(PHP_BINARY) ? PHP_BINARY : 'C:\\xampp\\php\\php.exe';
        $lintCmd = '"' . $phpBin . '" -l "' . $fullPath . '"';
        exec($lintCmd, $output, $returnVar);
        if ($returnVar === 0) {
            echo "[PASS] View syntax valid: {$name}\n";
        } else {
            echo "[FAIL] View syntax error: {$name}\n";
            $allPassed = false;
        }
    } else {
        echo "[FAIL] File missing: {$path}\n";
        $allPassed = false;
    }
}

if ($allPassed) {
    echo "\n>>> ALL REDESIGNED VIEWS & TEMPLATES VERIFIED SUCCESSFULLY! <<<\n";
    exit(0);
} else {
    exit(1);
}
