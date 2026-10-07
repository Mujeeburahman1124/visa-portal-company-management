<?php
declare(strict_types=1);

/**
 * Full project PHP linter
 * Runs php -l on every PHP file in app/, public/, and scripts/
 */

// Portable PHP Binary Discovery
$phpBinary = (function (): string {
    if (defined('PHP_BINARY') && !empty(PHP_BINARY) && (is_executable(PHP_BINARY) || file_exists(PHP_BINARY))) {
        return PHP_BINARY;
    }
    if (file_exists('C:\\xampp\\php\\php.exe')) {
        return 'C:\\xampp\\php\\php.exe';
    }
    $isWin = stripos(PHP_OS, 'WIN') === 0;
    $lookupCmd = $isWin ? 'where php 2>nul' : 'which php 2>/dev/null';
    $out = [];
    @exec($lookupCmd, $out);
    if (!empty($out[0]) && file_exists(trim($out[0]))) {
        return trim($out[0]);
    }
    return 'php';
})();
$directories = [
    __DIR__ . '/../app',
    __DIR__ . '/../public',
    __DIR__ . '/../scripts',
];

$checked = 0;
$errors = [];

foreach ($directories as $dir) {
    if (!is_dir($dir)) continue;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $checked++;
            $filePath = $file->getRealPath();
            $cmd = escapeshellarg($phpBinary) . ' -l ' . escapeshellarg($filePath);
            $output = [];
            $code = 0;
            exec($cmd . ' 2>&1', $output, $code);
            if ($code !== 0) {
                $errors[] = [
                    'file' => $filePath,
                    'output' => implode("\n", $output)
                ];
            }
        }
    }
}

echo "=== VISA TRACK PHP Syntax Check ===\n";
echo "Files checked: {$checked}\n";

if (empty($errors)) {
    echo "SUCCESS: All {$checked} PHP files passed linting with 0 errors!\n";
    exit(0);
} else {
    echo "FAILED: " . count($errors) . " files had syntax errors:\n";
    foreach ($errors as $err) {
        echo "  - {$err['file']}:\n    {$err['output']}\n";
    }
    exit(1);
}
