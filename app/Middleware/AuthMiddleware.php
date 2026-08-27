<?php
declare(strict_types=1);

namespace App\Middleware;

class AuthMiddleware
{
    public static function handle(): void
    {
        if (!is_authenticated()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
            redirect('/auth/login', 'Please log in to access the visa management system.', 'warning');
        }
    }

    public static function handleCustomer(): void
    {
        if (!is_customer_authenticated()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/portal/dashboard';
            redirect('/portal/login', 'Please log in to access your customer application portal.', 'warning');
        }
    }
}
