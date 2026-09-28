<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';

use App\Config\Database;

$pdo = Database::getConnection();

echo "=== STAFF / OPERATIONS USERS (For /login or /auth/login) ===\n";
$stmt = $pdo->query("SELECT u.id, u.name, u.email, r.name as role_name, r.slug as role_slug, u.is_active FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.id ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $u) {
    echo "• Email: {$u['email']}\n";
    echo "  Name:  {$u['name']}\n";
    echo "  Role:  {$u['role_name']} ({$u['role_slug']})\n";
    echo "  Pass:  password\n";
    echo "  Status: " . ($u['is_active'] ? 'Active' : 'Inactive') . "\n\n";
}

echo "=== AGENTS ===\n";
$agents = $pdo->query("SELECT id, company_name, contact_person, email, is_active FROM agents ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
if (empty($agents)) {
    echo "No agents found.\n";
} else {
    foreach ($agents as $a) {
        echo "• Email:   {$a['email']}\n";
        echo "  Company: {$a['company_name']}\n";
        echo "  Contact: {$a['contact_person']}\n";
        echo "  Pass:    password\n\n";
    }
}
