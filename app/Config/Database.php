<?php
declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            $driver = (string)Env::get('DB_DRIVER', 'sqlite');

            try {
                if ($driver === 'mysql') {
                    $host = (string)Env::get('DB_HOST', '127.0.0.1');
                    $port = (string)Env::get('DB_PORT', '3306');
                    $dbname = (string)Env::get('DB_NAME', (string)Env::get('DB_DATABASE', 'visatrack'));
                    $user = (string)Env::get('DB_USER', (string)Env::get('DB_USERNAME', 'root'));
                    $pass = (string)Env::get('DB_PASS', (string)Env::get('DB_PASSWORD', ''));
                    $charset = (string)Env::get('DB_CHARSET', 'utf8mb4');
                    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
                    
                    self::$pdo = new PDO($dsn, $user, $pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                } else {
                    $customPath = Env::get('DB_PATH');
                    $dbPath = !empty($customPath) ? (string)$customPath : App::dbPath();
                    $dsn = "sqlite:{$dbPath}";
                    
                    self::$pdo = new PDO($dsn, null, null, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);
                    
                    self::$pdo->exec('PRAGMA foreign_keys = ON;');
                    self::$pdo->exec('PRAGMA journal_mode = WAL;');
                }
            } catch (PDOException $e) {
                die('Database Connection Error: ' . $e->getMessage());
            }
        }

        return self::$pdo;
    }
}
