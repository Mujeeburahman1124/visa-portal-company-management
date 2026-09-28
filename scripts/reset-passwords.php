<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';

use App\Config\Database;

$pdo = Database::getConnection();

// Update all staff users and agents so BOTH 'password' and 'admin123' are verified
// Actually, let's update all users so password_hash is password_hash('password', PASSWORD_DEFAULT)
$newHash = password_hash('password', PASSWORD_DEFAULT);
$pdo->prepare("UPDATE users SET password_hash = ?")->execute([$newHash]);
$pdo->prepare("UPDATE agents SET password_hash = ?")->execute([$newHash]);

echo "Updated all users and agents with password 'password'!\n";

$users = $pdo->query("SELECT email, password_hash FROM users")->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    echo "User {$u['email']}: verify 'password' = " . (password_verify('password', $u['password_hash']) ? 'YES' : 'NO') . "\n";
}
