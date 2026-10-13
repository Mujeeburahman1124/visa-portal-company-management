<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Lightweight Zero-Dependency Sentry Client for PHP.
 * Works natively on any host (Hostinger, Apache, Nginx) without Composer.
 * Captures unhandled exceptions, database errors, and fatal shutdown errors.
 */
class SentryService
{
    private static ?string $dsn = null;
    private static ?array $parsedDsn = null;

    /**
     * Get or initialize Sentry DSN configuration.
     */
    public static function getDsn(): ?string
    {
        if (self::$dsn !== null) {
            return self::$dsn;
        }

        $dsn = $_ENV['SENTRY_DSN'] ?? getenv('SENTRY_DSN') ?: '';
        if (empty($dsn)) {
            // Also check .env file directly if Env class loaded it
            $dsn = \App\Config\Env::get('SENTRY_DSN', '');
        }

        self::$dsn = trim((string)$dsn);
        return self::$dsn ?: null;
    }

    /**
     * Parse Sentry DSN: https://{KEY}@{HOST}/{PROJECT_ID}
     */
    private static function parseDsn(): ?array
    {
        if (self::$parsedDsn !== null) {
            return self::$parsedDsn;
        }

        $dsn = self::getDsn();
        if (!$dsn) {
            return null;
        }

        $parsed = parse_url($dsn);
        if (!$parsed || empty($parsed['host']) || empty($parsed['user']) || empty($parsed['path'])) {
            return null;
        }

        $projectId = trim($parsed['path'], '/');
        if (empty($projectId)) {
            return null;
        }

        $scheme = $parsed['scheme'] ?? 'https';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $endpoint = "{$scheme}://{$parsed['host']}{$port}/api/{$projectId}/store/";

        self::$parsedDsn = [
            'public_key' => $parsed['user'],
            'endpoint'   => $endpoint,
            'host'       => $parsed['host'],
            'project_id' => $projectId,
        ];

        return self::$parsedDsn;
    }

    /**
     * Capture a Throwable exception and send to Sentry.
     */
    public static function captureException(\Throwable $e, array $extra = []): ?string
    {
        $parsed = self::parseDsn();
        if (!$parsed) {
            return null; // Sentry not configured, silently skip
        }

        $eventId = bin2hex(random_bytes(16));
        $timestamp = gmdate("Y-m-d\TH:i:s\Z");

        // Format stacktrace frames for Sentry UI
        $frames = [];
        $trace = $e->getTrace();
        // Add origin frame
        array_unshift($trace, [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'function' => '{main}',
        ]);

        foreach (array_reverse($trace) as $t) {
            $frames[] = [
                'filename' => $t['file'] ?? '[internal]',
                'lineno'   => $t['line'] ?? 0,
                'function' => ($t['class'] ?? '') . ($t['type'] ?? '') . ($t['function'] ?? 'unknown'),
                'in_app'   => isset($t['file']) && !str_contains($t['file'], 'vendor'),
            ];
        }

        $user = function_exists('auth_user') ? auth_user() : ($_SESSION['user'] ?? null);

        $payload = [
            'event_id'    => $eventId,
            'timestamp'   => $timestamp,
            'platform'    => 'php',
            'level'       => 'error',
            'logger'      => 'php',
            'environment' => $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production',
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'mshorizonuae.com',
            'release'     => '1.0.0',
            'exception'   => [
                'values' => [
                    [
                        'type'       => get_class($e),
                        'value'      => $e->getMessage(),
                        'module'     => $e->getFile() . ':' . $e->getLine(),
                        'stacktrace' => ['frames' => $frames],
                    ]
                ]
            ],
            'request' => [
                'url'         => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/'),
                'method'      => $_SERVER['REQUEST_METHOD'] ?? 'GET',
                'headers'     => [
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                ],
            ],
            'user' => [
                'id'       => $user['id'] ?? null,
                'username' => $user['name'] ?? $user['email'] ?? null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ],
            'tags' => [
                'php_version' => PHP_VERSION,
                'app_name'    => 'MS Travel Hub Visa Track',
            ],
            'extra' => $extra,
        ];

        self::sendPayload($parsed, $payload);
        return $eventId;
    }

    /**
     * Capture a fatal shutdown error (e.g. out of memory, timeout, syntax).
     */
    public static function captureFatal(string $message, string $file, int $line): ?string
    {
        $parsed = self::parseDsn();
        if (!$parsed) {
            return null;
        }

        $eventId = bin2hex(random_bytes(16));
        $timestamp = gmdate("Y-m-d\TH:i:s\Z");

        $payload = [
            'event_id'    => $eventId,
            'timestamp'   => $timestamp,
            'platform'    => 'php',
            'level'       => 'fatal',
            'logger'      => 'php.fatal',
            'environment' => $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production',
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'mshorizonuae.com',
            'exception'   => [
                'values' => [
                    [
                        'type'       => 'FatalError',
                        'value'      => $message,
                        'stacktrace' => [
                            'frames' => [
                                [
                                    'filename' => $file,
                                    'lineno'   => $line,
                                    'function' => '{shutdown}',
                                    'in_app'   => true,
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'request' => [
                'url'    => $_SERVER['REQUEST_URI'] ?? '/',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            ],
        ];

        self::sendPayload($parsed, $payload);
        return $eventId;
    }

    /**
     * Send arbitrary message or alert to Sentry.
     */
    public static function captureMessage(string $message, string $level = 'info', array $extra = []): ?string
    {
        $parsed = self::parseDsn();
        if (!$parsed) {
            return null;
        }

        $eventId = bin2hex(random_bytes(16));
        $payload = [
            'event_id'    => $eventId,
            'timestamp'   => gmdate("Y-m-d\TH:i:s\Z"),
            'platform'    => 'php',
            'level'       => $level,
            'message'     => ['formatted' => $message],
            'environment' => $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production',
            'extra'       => $extra,
        ];

        self::sendPayload($parsed, $payload);
        return $eventId;
    }

    /**
     * Transmit JSON event payload to Sentry Store API endpoint.
     */
    private static function sendPayload(array $parsed, array $payload): void
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!$body) {
            return;
        }

        $authHeader = sprintf(
            'Sentry sentry_version=7, sentry_client=ms-travel-hub/1.0, sentry_key=%s, sentry_timestamp=%d',
            $parsed['public_key'],
            time()
        );

        // Send via cURL with a strict 2-second timeout so page execution is never delayed
        $ch = curl_init($parsed['endpoint']);
        if ($ch) {
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'X-Sentry-Auth: ' . $authHeader,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 2,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            @curl_exec($ch);
            unset($ch);
        }
    }
}
