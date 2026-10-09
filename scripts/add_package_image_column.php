<?php
require_once dirname(__DIR__) . '/app/autoload.php';
$pdo = App\Config\Database::getConnection();

try {
    $pdo->exec("ALTER TABLE visa_services ADD COLUMN image_url VARCHAR(255) NULL");
    echo "SUCCESS: Added image_url column to visa_services\n";
} catch (\Throwable $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false) {
        echo "INFO: image_url column already exists.\n";
    } else {
        echo "NOTICE: " . $e->getMessage() . "\n";
    }
}

// Check current services
$stmt = $pdo->query("SELECT id, name, country_id, image_url FROM visa_services LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($rows) . " visa services in DB.\n";
foreach ($rows as $r) {
    echo " - ID {$r['id']}: {$r['name']} (Image: " . ($r['image_url'] ?? 'NULL') . ")\n";
}
