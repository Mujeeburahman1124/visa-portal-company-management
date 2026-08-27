<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use PDO;

class PriorityEngineService
{
    /**
     * Recomputes operational priority for an application based on business rules
     */
    public static function evaluate(int $applicationId): string
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT a.*, vs.estimated_days 
            FROM applications a 
            JOIN visa_services vs ON a.visa_service_id = vs.id 
            WHERE a.id = ?");
        $stmt->execute([$applicationId]);
        $app = $stmt->fetch();

        if (!$app || in_array($app['status'], ['Approved', 'Completed', 'Cancelled'], true)) {
            return $app['priority'] ?? 'Normal';
        }

        // If returned or action required -> Critical
        if ($app['status'] === 'Action Required' || str_contains(strtolower($app['current_stage']), 'returned')) {
            return 'Critical';
        }

        // Check Travel Date proximity
        if (!empty($app['travel_date'])) {
            $travelTimestamp = strtotime($app['travel_date']);
            $daysToTravel = ($travelTimestamp - time()) / 86400;
            if ($daysToTravel <= 5 && $daysToTravel >= 0) {
                return 'Critical';
            } elseif ($daysToTravel <= 10 && $daysToTravel >= 0) {
                return 'Urgent';
            }
        }

        // Check Expected Completion Date
        if (!empty($app['expected_completion_date'])) {
            $deadlineTimestamp = strtotime($app['expected_completion_date']);
            $daysToDeadline = ($deadlineTimestamp - time()) / 86400;
            if ($daysToDeadline < 0) {
                return 'Critical'; // Overdue
            } elseif ($daysToDeadline <= 2) {
                return 'Urgent';
            } elseif ($daysToDeadline <= 5) {
                return 'High';
            }
        }

        // Check health score
        $health = (int)($app['calculated_health'] ?? 100);
        if ($health < 50) {
            return 'Critical';
        } elseif ($health < 75) {
            return 'High';
        }

        return $app['priority'] ?: 'Normal';
    }
}
