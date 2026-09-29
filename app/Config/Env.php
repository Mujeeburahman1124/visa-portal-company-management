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

        // Always prefer the actual incoming live host for APP_URL when running via web server
        if ($key === 'APP_URL' && !empty($_SERVER['HTTP_HOST']) && !str_contains($_SERVER['HTTP_HOST'], 'localhost') && !str_starts_with($_SERVER['HTTP_HOST'], '127.0.0.1')) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
            $scheme = $isHttps ? 'https' : 'http';
            return $scheme . '://' . $_SERVER['HTTP_HOST'];
        }

        // 1. Check direct env variable
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            $casted = self::castValue($val);
            if ($key === 'APP_URL' && is_string($casted) && str_contains($casted, 'localhost')) {
                return 'https://mshorizonuae.com';
            }
            return $casted;
        }

        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            $casted = self::castValue($_ENV[$key]);
            if ($key === 'APP_URL' && is_string($casted) && str_contains($casted, 'localhost')) {
                return 'https://mshorizonuae.com';
            }
            return $casted;
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

        // Special handling for APP_URL to ensure emails and links always resolve to the live domain
        if ($key === 'APP_URL') {
            if (!empty($_SERVER['HTTP_HOST']) && !str_contains($_SERVER['HTTP_HOST'], 'localhost') && !str_starts_with($_SERVER['HTTP_HOST'], '127.0.0.1')) {
                $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
                    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
                $scheme = $isHttps ? 'https' : 'http';
                return $scheme . '://' . $_SERVER['HTTP_HOST'];
            }
            if ($default === 'http://localhost:8000' || empty($default)) {
                return 'https://mshorizonuae.com';
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
