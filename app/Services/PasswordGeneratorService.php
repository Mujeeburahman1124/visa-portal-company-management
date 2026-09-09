<?php
declare(strict_types=1);

namespace App\Services;

class PasswordGeneratorService
{
    /**
     * Generate a secure, readable auto-generated temporary password
     * E.g. "STAFF@8kX2m9", "CUST@7mQ3v8", "SUP@4nK8w2", "AGENT@9bT5x1"
     */
    public static function generate(int $length = 10, string $prefix = 'MST@'): string
    {
        $chars = '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
        $randStr = '';
        $max = strlen($chars) - 1;
        $needed = max(4, $length - strlen($prefix));
        
        for ($i = 0; $i < $needed; $i++) {
            $randStr .= $chars[random_int(0, $max)];
        }
        
        return $prefix . $randStr;
    }
}
