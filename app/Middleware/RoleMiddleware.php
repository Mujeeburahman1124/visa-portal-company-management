<?php
declare(strict_types=1);

namespace App\Middleware;

class RoleMiddleware
{
    /**
     * Authorize against one or more permitted role slugs/names, OR granular permissions
     */
    public static function authorize(array|string $roles, array|string $permissions = []): void
    {
        AuthMiddleware::handle();

        if (user_has_role($roles)) {
            return;
        }

        if (!empty($permissions)) {
            if (is_string($permissions)) {
                $permissions = [$permissions];
            }
            foreach ($permissions as $perm) {
                if (user_can($perm)) {
                    return;
                }
            }
        }

        http_response_code(403);
        require_once dirname(__DIR__) . '/Views/layouts/403.php';
        exit;
    }

    /**
     * Authorize against granular permission slug
     */
    public static function authorizePermission(string $permissionSlug): void
    {
        AuthMiddleware::handle();

        if (!user_can($permissionSlug)) {
            http_response_code(403);
            require_once dirname(__DIR__) . '/Views/layouts/403.php';
            exit;
        }
    }
}

