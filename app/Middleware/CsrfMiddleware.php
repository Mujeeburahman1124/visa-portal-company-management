<?php
declare(strict_types=1);

namespace App\Middleware;

class CsrfMiddleware
{
    public static function validate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!verify_csrf($token)) {
                http_response_code(419);
                die('CSRF token validation failed. Please refresh the page and try again.');
            }
        }
    }
}
