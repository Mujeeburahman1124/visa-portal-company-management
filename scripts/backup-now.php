<?php
// One-off: create a full DB backup before maintenance. Run: php scripts/backup-now.php
require_once dirname(__DIR__) . '/app/autoload.php';
\App\Database\DatabaseBootstrapper::init(true);
$r = \App\Services\DatabaseBackupService::createDatabaseBackup();
echo json_encode($r, JSON_PRETTY_PRINT) . PHP_EOL;
