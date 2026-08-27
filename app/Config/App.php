<?php
declare(strict_types=1);

namespace App\Config;

class App
{
    public const APP_NAME = 'VISA TRACK — Global Visa Management Portal';
    public const SHORT_NAME = 'VISA TRACK';
    public const VERSION = '1.0.0';
    public const COMPANY_NAME = 'MS Travel Hub Global Visa Services';
    public const BASE_CURRENCY = 'USD';
    public const CURRENCY_SYMBOL = '$';
    
    public const ROLE_SUPER_ADMIN = 'Super Admin';
    public const ROLE_ADMIN = 'Admin';
    public const ROLE_BRANCH_MANAGER = 'Branch Manager';
    public const ROLE_VISA_MANAGER = 'Visa Manager';
    public const ROLE_VISA_CONSULTANT = 'Visa Consultant';
    public const ROLE_PROCESSING_STAFF = 'Processing Staff';
    public const ROLE_ACCOUNTS = 'Accounts';
    public const ROLE_CUSTOMER_SERVICE = 'Customer Service';
    public const ROLE_DATA_ENTRY = 'Data Entry';
    public const ROLE_READ_ONLY = 'Read Only';
    public const ROLE_CUSTOMER = 'Customer';

    public static function getRoles(): array
    {
        return [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_BRANCH_MANAGER,
            self::ROLE_VISA_MANAGER,
            self::ROLE_VISA_CONSULTANT,
            self::ROLE_PROCESSING_STAFF,
            self::ROLE_ACCOUNTS,
            self::ROLE_CUSTOMER_SERVICE,
            self::ROLE_DATA_ENTRY,
            self::ROLE_READ_ONLY,
            self::ROLE_CUSTOMER,
        ];
    }

    public static function basePath(string $path = ''): string
    {
        $base = dirname(__DIR__, 2);
        return $path ? $base . DIRECTORY_SEPARATOR . ltrim($path, '/\\') : $base;
    }

    public static function appPath(string $path = ''): string
    {
        $app = dirname(__DIR__);
        return $path ? $app . DIRECTORY_SEPARATOR . ltrim($path, '/\\') : $app;
    }

    public static function publicPath(string $path = ''): string
    {
        $pub = self::basePath('public');
        return $path ? $pub . DIRECTORY_SEPARATOR . ltrim($path, '/\\') : $pub;
    }

    public static function uploadPath(string $path = ''): string
    {
        $upload = self::publicPath('uploads/documents');
        if (!is_dir($upload)) {
            mkdir($upload, 0777, true);
        }
        return $path ? $upload . DIRECTORY_SEPARATOR . ltrim($path, '/\\') : $upload;
    }

    public static function dbPath(): string
    {
        $dir = self::basePath('data');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return $dir . DIRECTORY_SEPARATOR . 'visatrack.sqlite';
    }
}
