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

            if ($driver === 'mysql') {
                $host = (string)Env::get('DB_HOST', '127.0.0.1');
                $port = (string)Env::get('DB_PORT', '3306');
                $dbname = (string)Env::get('DB_NAME', (string)Env::get('DB_DATABASE', 'visatrack'));
                $user = (string)Env::get('DB_USER', (string)Env::get('DB_USERNAME', 'root'));
                $pass = (string)Env::get('DB_PASS', (string)Env::get('DB_PASSWORD', ''));
                $charset = (string)Env::get('DB_CHARSET', 'utf8mb4');

                try {
                    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
                    self::$pdo = new PDO($dsn, $user, $pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                } catch (PDOException $e) {
                    // Try connecting without dbname to create database if it doesn't exist
                    try {
                        $rawDsn = "mysql:host={$host};port={$port};charset={$charset}";
                        $tempPdo = new PDO($rawDsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                        $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        unset($tempPdo);

                        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
                        self::$pdo = new PDO($dsn, $user, $pass, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES => false,
                        ]);
                    } catch (PDOException $ex) {
                        // If MySQL is offline or refused, seamlessly fallback to SQLite so the app never crashes
                        error_log("MySQL connection failed ({$ex->getMessage()}). Falling back to SQLite database.");
                        $dbPath = App::dbPath();
                        $dsn = "sqlite:{$dbPath}";
                        self::$pdo = new PDO($dsn, null, null, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_TIMEOUT => 60,
                        ]);
                        self::$pdo->exec('PRAGMA foreign_keys = ON;');
                        self::$pdo->exec('PRAGMA journal_mode = WAL;');
                        self::$pdo->exec('PRAGMA busy_timeout = 60000;');
                    }
                }
            } else {
                $customPath = Env::get('DB_PATH');
                if (!empty($customPath)) {
                    $customPathStr = (string)$customPath;
                    $isAbsolute = str_starts_with($customPathStr, '/') || 
                                  str_starts_with($customPathStr, '\\') || 
                                  (strlen($customPathStr) > 1 && $customPathStr[1] === ':');
                    $dbPath = $isAbsolute ? $customPathStr : App::basePath($customPathStr);
                } else {
                    $dbPath = App::dbPath();
                }
                $dsn = "sqlite:{$dbPath}";
                
                self::$pdo = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 60,
                ]);
                
                self::$pdo->exec('PRAGMA foreign_keys = ON;');
                self::$pdo->exec('PRAGMA journal_mode = WAL;');
                self::$pdo->exec('PRAGMA busy_timeout = 60000;');
            }
        }

        return self::$pdo;
    }
}
