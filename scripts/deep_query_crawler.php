<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';

use App\Config\Database;
use App\Database\DatabaseBootstrapper;

echo "======================================================================\n";
echo "DEEP SQL QUERY AUDITOR & DRY-RUN TESTER\n";
echo "======================================================================\n\n";

DatabaseBootstrapper::init(true);

$pdo = Database::getConnection();

// Get list of PHP files
$dirIter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../app'));
$files = [];
foreach ($dirIter as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $files[] = $file->getPathname();
    }
}
$files[] = __DIR__ . '/../public/index.php';

$sqlErrors = [];
$totalQueriesTested = 0;

foreach ($files as $filePath) {
    $content = file_get_contents($filePath);
    $relPath = str_replace(dirname(__DIR__) . DIRECTORY_SEPARATOR, '', $filePath);

    // Extract SQL query string literals
    preg_match_all('/["\']\s*(SELECT|INSERT|UPDATE|DELETE|REPLACE)\s+[^"\']+["\']/i', $content, $matches);

    foreach ($matches[0] as $rawSql) {
        $sql = trim($rawSql, "\"' \t\n\r");
        
        // Skip queries with unparsed dynamic variable substitutions that break syntax
        if (str_contains($sql, '$') || str_contains($sql, '{')) {
            // Replace simple PHP interpolation placeholders for dry-run
            $drySql = preg_replace('/\$[a-zA-Z0-9_]+/', '1', $sql);
            $drySql = preg_replace('/\{[^\}]+\}/', '1', $drySql);
        } else {
            $drySql = $sql;
        }

        $totalQueriesTested++;

        try {
            // Test EXPLAIN or prepare dry-run
            $pdo->prepare($drySql);
        } catch (\PDOException $e) {
            $sqlErrors[] = [
                'file' => $relPath,
                'sql' => $sql,
                'error' => $e->getMessage()
            ];
        }
    }
}

echo "Tested {$totalQueriesTested} SQL query statements across codebase.\n\n";

if (!empty($sqlErrors)) {
    echo "FAILED QUERIES FOUND (" . count($sqlErrors) . "):\n";
    foreach ($sqlErrors as $err) {
        echo "----------------------------------------------------------------------\n";
        echo "File:  {$err['file']}\n";
        echo "Error: {$err['error']}\n";
        echo "SQL:   {$err['sql']}\n";
    }
} else {
    echo "[PASS] ZERO SQL ERRORS DETECTED! All queries prepare cleanly against PDO.\n";
}

echo "\n======================================================================\n";
