<?php
declare(strict_types=1);

namespace App\Middleware;

class CsrfMiddleware
{
    public static function field(): string
    {
        return csrf_field();
    }

    public static function validate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!verify_csrf($token)) {
                $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                    || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
                    || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');

                if ($isAjax) {
                    http_response_code(419);
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'CSRF security token expired or invalid. Please refresh the page and try again.',
                        'csrf_token' => csrf_token()
                    ]);
                    exit;
                }

                // If regular browser form submission, set flash message and redirect gracefully
                set_flash('Your security session was refreshed or expired. Please submit the form again.', 'warning');

                $referer = $_SERVER['HTTP_REFERER'] ?? null;
                $currentUri = $_SERVER['REQUEST_URI'] ?? '/';
                $refererPath = $referer ? parse_url($referer, PHP_URL_PATH) : null;
                $currentPath = parse_url($currentUri, PHP_URL_PATH);

                if ($referer && $refererPath !== $currentPath) {
                    redirect($referer);
                }

                if (is_authenticated()) {
                    redirect('/dashboard');
                } elseif (is_customer_authenticated()) {
                    redirect('/portal/dashboard');
                } elseif (is_agent_authenticated()) {
                    redirect('/agent/dashboard');
                } elseif (is_supplier_authenticated()) {
                    redirect('/supplier/dashboard');
                } else {
                    redirect('/auth/login');
                }
            }
        }
    }
}
