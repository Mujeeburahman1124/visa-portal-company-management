<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';

use App\Config\Database;

$pdo = Database::getConnection();
$rows = $pdo->query('SELECT id, template_key, title, subject, body_html FROM email_templates')->fetchAll(PDO::FETCH_ASSOC);

echo "Total templates: " . count($rows) . "\n";
foreach ($rows as $r) {
    echo "ID: {$r['id']} | KEY: {$r['template_key']} | TITLE: {$r['title']}\n";
    echo "SUBJECT: {$r['subject']}\n";
    echo "BODY:\n" . substr($r['body_html'], 0, 150) . "...\n\n";
}
