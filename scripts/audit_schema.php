<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';

use App\Config\Database;
use App\Database\DatabaseBootstrapper;

echo "======================================================================\n";
echo "COMPLETE CODE-TO-DATABASE SCHEMA & QUERY AUDIT\n";
echo "======================================================================\n\n";

DatabaseBootstrapper::init(true);

$pdo = Database::getConnection();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

// 1. Fetch all actual tables & columns in current DB
$dbSchema = [];
if ($driver === 'sqlite') {
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        if ($table === 'sqlite_sequence') continue;
        $cols = $pdo->query("PRAGMA table_info(`{$table}`)")->fetchAll(PDO::FETCH_ASSOC);
        $dbSchema[strtolower($table)] = [];
        foreach ($cols as $c) {
            $dbSchema[strtolower($table)][] = strtolower($c['name']);
        }
    }
} else {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $cols = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        $dbSchema[strtolower($table)] = [];
        foreach ($cols as $c) {
            $dbSchema[strtolower($table)][] = strtolower($c['Field']);
        }
    }
}

echo "Found " . count($dbSchema) . " tables in active DB ({$driver}):\n";
foreach (array_keys($dbSchema) as $t) {
    echo "  - {$t} (" . count($dbSchema[$t]) . " columns)\n";
}
echo "\n";

// 2. Scan all PHP files for SQL queries and inspect table/column usage
$files = [];
$dirIter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../app'));
foreach ($dirIter as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $files[] = $file->getPathname();
    }
}
$files[] = __DIR__ . '/../public/index.php';

$missingTables = [];
$missingColumns = [];
$checkedQueries = 0;

$knownKeywords = ['select', 'from', 'where', 'and', 'or', 'join', 'left', 'right', 'inner', 'outer', 'on', 'group', 'by', 'order', 'limit', 'as', 'set', 'into', 'values', 'null', 'is', 'not', 'asc', 'desc', 'count', 'sum', 'avg', 'max', 'min', 'like', 'in', 'coalesce', 'date', 'now'];

foreach ($files as $filePath) {
    $content = file_get_contents($filePath);
    $relPath = str_replace(dirname(__DIR__) . DIRECTORY_SEPARATOR, '', $filePath);

    // Extract SQL queries
    preg_match_all('/["\']\s*(SELECT|INSERT\s+INTO|UPDATE|DELETE\s+FROM)\s+[^"\']+["\']/i', $content, $matches);

    foreach ($matches[0] as $rawSql) {
        $checkedQueries++;
        $sql = trim($rawSql, "\"' \t\n\r");

        // Inspect explicit table.column patterns
        if (preg_match_all('/(?:`?([a-zA-Z0-9_]+)`?\.)?`?([a-zA-Z0-9_]+)`?/i', $sql, $colMatches, PREG_SET_ORDER)) {
            // Find FROM or JOIN table
            $foundTables = [];
            if (preg_match_all('/(?:FROM|JOIN|UPDATE|INTO)\s+`?([a-zA-Z0-9_]+)`?/i', $sql, $tblMatches)) {
                foreach ($tblMatches[1] as $t) {
                    $tLow = strtolower($t);
                    if (!in_array($tLow, $knownKeywords)) {
                        $foundTables[] = $tLow;
                    }
                }
            }

            foreach ($colMatches as $m) {
                $prefix = strtolower($m[1]);
                $colName = strtolower($m[2]);

                if (in_array($colName, $knownKeywords) || is_numeric($colName)) {
                    continue;
                }

                if ($prefix !== '' && isset($dbSchema[$prefix])) {
                    if (!in_array($colName, $dbSchema[$prefix])) {
                        $missingColumns["{$prefix}.{$colName}"][] = "{$relPath}";
                    }
                }
            }
        }
    }
}

echo "=== AUDIT RESULTS ===\n\n";

echo "MISSING COLUMNS IN SCHEMAS (" . count($missingColumns) . "):\n";
foreach ($missingColumns as $col => $locs) {
    echo "  [FAIL] Column '{$col}' referenced in: " . implode(', ', array_unique($locs)) . "\n";
}
if (empty($missingColumns)) {
    echo "  [PASS] All referenced columns exist in schema!\n";
}

echo "\n======================================================================\n";
