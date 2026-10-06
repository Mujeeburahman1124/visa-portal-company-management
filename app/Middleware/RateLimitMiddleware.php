<?php
declare(strict_types=1);

namespace App\Middleware;

class RateLimitMiddleware
{
    /**
     * Enforce rate limiting by key and IP
     *
     * @param string $key Scope name (e.g. 'api', 'checkout', 'login')
     * @param int $maxAttempts Maximum allowed hits within time window
     * @param int $decaySeconds Time window in seconds
     */
    public static function handle(string $key = 'api', int $maxAttempts = 60, int $decaySeconds = 60): void
    {
        $ip = self::getClientIp();
        $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
        $safeIp = hash('sha256', $ip . '_' . $safeKey);

        $cacheDir = dirname(__DIR__, 2) . '/storage/cache/ratelimits';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        $file = $cacheDir . '/' . $safeIp . '.json';
        $now = time();
        $data = ['hits' => 0, 'reset_at' => $now + $decaySeconds];

        if (file_exists($file)) {
            $content = @file_get_contents($file);
            $parsed = $content ? json_decode($content, true) : null;
            if (is_array($parsed) && ($parsed['reset_at'] ?? 0) > $now) {
                $data = $parsed;
            }
        }

        $data['hits'] = ($data['hits'] ?? 0) + 1;
        @file_put_contents($file, json_encode($data), LOCK_EX);

        $remaining = max(0, $maxAttempts - $data['hits']);
        $resetIn = max(1, $data['reset_at'] - $now);

        header("X-RateLimit-Limit: {$maxAttempts}");
        header("X-RateLimit-Remaining: {$remaining}");
        header("X-RateLimit-Reset: {$data['reset_at']}");

        if ($data['hits'] > $maxAttempts) {
            http_response_code(429);
            header("Retry-After: {$resetIn}");

            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')
                || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => "Too many requests. Please try again in {$resetIn} seconds.",
                    'retry_after' => $resetIn
                ]);
            } else {
                echo "<!DOCTYPE html><html><head><title>Too Many Requests</title></head><body style='font-family:sans-serif;text-align:center;padding:3rem;'><h1>429 — Too Many Requests</h1><p>Please wait {$resetIn} seconds before attempting this action again.</p></body></html>";
            }
            exit;
        }
    }

    private static function getClientIp(): string
    {
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if (!empty($forwarded)) {
            $parts = explode(',', $forwarded);
            return trim($parts[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
