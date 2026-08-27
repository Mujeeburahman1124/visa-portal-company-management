<?php
declare(strict_types=1);

namespace App\Config;

use App\Config\Database;
use PDO;

class Env
{
    private static bool $loaded = false;
    private static array $cache = [];

    /**
     * Load environment variables from .env file into putenv and $_ENV.
     */
    public static function init(?string $filePath = null): void
    {
        if (self::$loaded) {
            return;
        }

        $path = $filePath ?: App::basePath('.env');
        if (file_exists($path) && is_readable($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }

                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $value = trim($parts[1]);

                    // Strip surrounding quotes
                    if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                        (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                        $value = substr($value, 1, -1);
                    }

                    // Handle escaped newlines in quoted values
                    $value = str_replace('\n', "\n", $value);

                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                    putenv("{$key}={$value}");
                    self::$cache[$key] = $value;
                }
            }
        }

        self::$loaded = true;
    }

    /**
     * Retrieve an environment variable with optional fallback to system_settings or default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            self::init();
        }

        // 1. Check direct env variable
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return self::castValue($val);
        }

        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return self::castValue($_ENV[$key]);
        }

        // 2. Check system_settings table if available (skip DB_* keys to prevent recursion)
        if (!str_starts_with($key, 'DB_')) {
            try {
                $pdo = Database::getConnection();
                $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1");
                $stmt->execute([$key]);
                $dbVal = $stmt->fetchColumn();
                if ($dbVal !== false && $dbVal !== null && $dbVal !== '') {
                    return self::castValue($dbVal);
                }
            } catch (\Throwable $e) {
                // DB might not be initialized yet
            }
        }

        return $default;
    }

    /**
     * Set a runtime config value.
     */
    public static function set(string $key, mixed $value): void
    {
        $_ENV[$key] = (string)$value;
        $_SERVER[$key] = (string)$value;
        putenv("{$key}={$value}");
        self::$cache[$key] = (string)$value;
    }

    /**
     * Type caster for booleans and numbers.
     */
    private static function castValue(string $value): mixed
    {
        $lower = strtolower(trim($value));
        if ($lower === 'true' || $lower === '(true)') return true;
        if ($lower === 'false' || $lower === '(false)') return false;
        if ($lower === 'null' || $lower === '(null)') return null;
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float)$value : (int)$value;
        }
        return $value;
    }
}
