<?php
declare(strict_types=1);

/**
 * CLI Database Migration Runner
 * MS TRAVEL HUB — Global Visa Management Portal
 */

require_once __DIR__ . '/../app/autoload.php';

use App\Database\DatabaseBootstrapper;
use App\Config\Database;

echo "=== MS TRAVEL HUB DATABASE MIGRATION RUNNER ===\n";

try {
    $pdo = Database::getConnection();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "Connecting to database ({$driver})...\n";

    DatabaseBootstrapper::init(true);

    $currentVersion = $pdo->query("SELECT COALESCE(MAX(version), 0) FROM schema_migrations")->fetchColumn();
    echo "SUCCESS: Database migrated up to version {$currentVersion}.\n";
} catch (\Throwable $e) {
    echo "ERROR: Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
