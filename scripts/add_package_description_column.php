<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';

$pdo = \App\Config\Database::getConnection();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

try {
    if ($driver === 'sqlite') {
        $cols = $pdo->query("PRAGMA table_info(visa_services)")->fetchAll(PDO::FETCH_ASSOC);
        $colNames = array_column($cols, 'name');
        if (!in_array('description', $colNames, true)) {
            $pdo->exec("ALTER TABLE visa_services ADD COLUMN description TEXT NULL");
            echo "Added 'description' column to visa_services (SQLite)\n";
        } else {
            echo "'description' column already exists (SQLite)\n";
        }
    } else {
        $stmt = $pdo->query("SHOW COLUMNS FROM visa_services LIKE 'description'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE visa_services ADD COLUMN description TEXT NULL AFTER name");
            echo "Added 'description' column to visa_services (MySQL)\n";
        } else {
            echo "'description' column already exists (MySQL)\n";
        }
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
