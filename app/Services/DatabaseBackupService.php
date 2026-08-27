<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use PDO;
use ZipArchive;

class DatabaseBackupService
{
    /**
     * Creates a full SQL dump of the MySQL / SQLite database into the backups directory.
     */
    public static function createDatabaseBackup(): array
    {
        $backupDir = dirname(__DIR__, 2) . '/storage/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'db_backup_' . date('Ymd_His') . '.sql';
        $filepath = $backupDir . '/' . $filename;

        $pdo = Database::getConnection();
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        $handle = fopen($filepath, 'w');
        fwrite($handle, "-- MS Travel Hub — Automated Database Backup\n");
        fwrite($handle, "-- Generated at: " . date('Y-m-d H:i:s') . "\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($tables as $table) {
            $createTable = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
            $createSql = $createTable['Create Table'] ?? '';
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($handle, "{$createSql};\n\n");

            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $keys = array_map(fn($k) => "`{$k}`", array_keys($row));
                    $values = array_map(function($v) use ($pdo) {
                        return $v === null ? 'NULL' : $pdo->quote((string)$v);
                    }, array_values($row));

                    fwrite($handle, "INSERT INTO `{$table}` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n");
                }
                fwrite($handle, "\n");
            }
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        $size = filesize($filepath);

        AuditService::log('BACKUP_DATABASE', 'Settings', null, "Created database backup: {$filename} ({$size} bytes)");

        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'size_bytes' => $size
        ];
    }
}
