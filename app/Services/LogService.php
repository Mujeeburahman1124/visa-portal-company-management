<?php
declare(strict_types=1);

namespace App\Services;

/**
 * File-based structured logger (JSON lines).
 * Writes to storage/logs/app-YYYY-MM-DD.log, which is web-blocked by storage/.htaccess.
 * Never throws — logging must not break the request.
 */
class LogService
{
    private const MAX_FIELD = 4000;

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function exception(\Throwable $e, array $context = []): void
    {
        self::write('ERROR', get_class($e) . ': ' . $e->getMessage(), $context + [
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
            'trace' => substr($e->getTraceAsString(), 0, self::MAX_FIELD),
        ]);
    }

    public static function logDir(): string
    {
        return dirname(__DIR__, 2) . '/storage/logs';
    }

    private static function write(string $level, string $message, array $context): void
    {
        try {
            $dir = self::logDir();
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            $user = $_SESSION['user'] ?? null;
            $entry = [
                'ts'      => date('c'),
                'level'   => $level,
                'message' => substr($message, 0, self::MAX_FIELD),
                'method'  => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
                'uri'     => $_SERVER['REQUEST_URI'] ?? (PHP_SAPI === 'cli' ? implode(' ', $_SERVER['argv'] ?? []) : ''),
                'ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_id' => $user['id'] ?? null,
            ] + $context;

            $line = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            $file = $dir . '/app-' . date('Y-m-d') . '.log';

            if (@file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
                error_log('[VISA-TRACK][' . $level . '] ' . $message);
            }
        } catch (\Throwable $ignored) {
            error_log('[VISA-TRACK][' . $level . '] ' . $message);
        }
    }
}
