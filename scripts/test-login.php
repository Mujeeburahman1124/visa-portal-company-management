<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';

use App\Config\Database;

$pdo = Database::getConnection();

$stmt = $pdo->prepare("SELECT id, name, email, password_hash, is_active FROM users WHERE email = ?");
$stmt->execute(['admin@visatrack.com']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "User found: " . ($user ? 'YES' : 'NO') . "\n";
if ($user) {
    echo "Email: {$user['email']}\n";
    echo "Active: {$user['is_active']}\n";
    echo "Hash: {$user['password_hash']}\n";
    echo "Verify 'password': " . (password_verify('password', $user['password_hash']) ? 'TRUE' : 'FALSE') . "\n";
    echo "Verify 'admin123': " . (password_verify('admin123', $user['password_hash']) ? 'TRUE' : 'FALSE') . "\n";
    echo "Verify 'admin': " . (password_verify('admin', $user['password_hash']) ? 'TRUE' : 'FALSE') . "\n";
    echo "Verify '123456': " . (password_verify('123456', $user['password_hash']) ? 'TRUE' : 'FALSE') . "\n";
    echo "Verify 'password123': " . (password_verify('password123', $user['password_hash']) ? 'TRUE' : 'FALSE') . "\n";
}
