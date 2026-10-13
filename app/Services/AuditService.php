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

            try {
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
            } catch (\PDOException $pdoe) {
                // If foreign key constraint fails (e.g. synthetic test user, deleted user, or guest ID)
                if ($pdoe->getCode() === '23000' && ($userId !== null || $customerId !== null)) {
                    $fallbackDetails = is_array($details) ? $details : [];
                    if ($userId !== null) $fallbackDetails['unlinked_user_id'] = $userId;
                    if ($customerId !== null) $fallbackDetails['unlinked_customer_id'] = $customerId;
                    $fallbackJson = json_encode($fallbackDetails);

                    $stmt->execute([
                        null,
                        null,
                        $actorType,
                        $action,
                        $module,
                        $recordId,
                        $description,
                        $fallbackJson,
                        $ip,
                        $ua
                    ]);
                } else {
                    throw $pdoe;
                }
            }
        } catch (\Throwable $e) {
            // Failsafe so audit errors don't interrupt critical business flow
            error_log('Audit log failure: ' . $e->getMessage());
            if (class_exists(LogService::class)) {
                LogService::error('Audit log failure: ' . $e->getMessage(), ['type' => 'audit_failure', 'action' => $action, 'module' => $module, 'record_id' => $recordId]);
            }
        }
    }
}
