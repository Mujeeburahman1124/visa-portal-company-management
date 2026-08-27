<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use PDO;

class AuditService
{
    public static function log(
        string $action,
        string $module,
        ?int $recordId,
        string $description,
        ?array $details = null,
        ?int $userId = null,
        ?int $customerId = null,
        string $actorType = 'Staff'
    ): void {
        try {
            $pdo = Database::getConnection();
            
            if ($userId === null && isset($_SESSION['user']['id'])) {
                $userId = (int)$_SESSION['user']['id'];
            }
            if ($customerId === null && isset($_SESSION['customer']['id'])) {
                $customerId = (int)$_SESSION['customer']['id'];
                $actorType = 'Customer';
            }

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI', 0, 250);
            $json = $details ? json_encode($details) : null;

            $stmt = $pdo->prepare("INSERT INTO activity_logs (
                user_id, customer_id, actor_type, action, module, record_id, 
                description, details_json, ip_address, user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $userId,
                $customerId,
                $actorType,
                $action,
                $module,
                $recordId,
                $description,
                $json,
                $ip,
                $ua
            ]);
        } catch (\Exception $e) {
            // Failsafe so audit errors don't interrupt critical business flow
            error_log('Audit log failure: ' . $e->getMessage());
        }
    }
}
