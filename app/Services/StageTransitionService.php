<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use App\Validators\StageValidator;
use PDO;
use Exception;

class StageTransitionService
{
    /**
     * Executes an atomic stage transition with history preservation,
     * notification dispatch, and audit trail.
     */
    public static function transition(
        int $applicationId,
        string $newStage,
        string $newStatus,
        ?string $comments = null,
        ?int $userId = null,
        ?string $nextAction = null,
        ?string $nextActionDueDate = null
    ): array {
        $validator = new StageValidator();
        if (!$validator->validate([
            'application_id' => $applicationId,
            'new_stage' => $newStage,
            'new_status' => $newStatus
        ])) {
            return [
                'success' => false,
                'message' => $validator->getFirstError() ?? 'Validation failed',
                'errors' => $validator->getErrors()
            ];
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // 1. Fetch current application record
            $stmt = $pdo->prepare("SELECT id, application_number, customer_id, current_stage, status, assigned_staff_id FROM applications WHERE id = ?");
            $stmt->execute([$applicationId]);
            $app = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$app) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Visa application not found.'];
            }

            $oldStage = $app['current_stage'];
            $oldStatus = $app['status'];

            // 2. Close any open stage history record for this application
            $pdo->prepare("UPDATE application_status_history 
                SET created_at = COALESCE(created_at, CURRENT_TIMESTAMP) 
                WHERE application_id = ? AND to_stage = ?")
                ->execute([$applicationId, $oldStage]);

            // 3. Insert immutable stage history record
            $stmtHist = $pdo->prepare("INSERT INTO application_status_history 
                (application_id, from_stage, to_stage, from_status, to_status, comments, changed_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $stmtHist->execute([
                $applicationId,
                $oldStage,
                $newStage,
                $oldStatus,
                $newStatus,
                $comments ?: "Advanced lifecycle stage from {$oldStage} to {$newStage}",
                $userId
            ]);

            // 4. Update application state
            $stmtUpdate = $pdo->prepare("UPDATE applications 
                SET current_stage = ?, 
                    status = ?, 
                    updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?");
            $stmtUpdate->execute([$newStage, $newStatus, $applicationId]);

            // 5. Update health score
            HealthCalculatorService::updateHealthScore($applicationId);

            // 6. Dispatch Central Real-Time Notification (Email + WhatsApp + In-App)
            try {
                $eventType = 'visa.stage_changed';
                if ($newStatus === 'Approved' || str_contains(strtolower($newStage), 'stamping') && $newStatus === 'Completed') {
                    $eventType = 'visa.approved';
                } elseif ($newStatus === 'Rejected' || $newStatus === 'Refused') {
                    $eventType = 'visa.rejected';
                }

                \App\Services\NotificationService::trigger($eventType, [
                    'application_id' => $applicationId,
                    'customer_id' => $app['customer_id'],
                    'assigned_staff_id' => $app['assigned_staff_id'],
                    'application_number' => $app['application_number'],
                    'current_stage' => $newStage,
                    'status' => $newStatus,
                    'next_action' => $nextAction ?: 'Awaiting next milestone review',
                    'comments' => $comments,
                    'link' => "/applications/show?id={$applicationId}",
                    'portal_link' => "/portal/dashboard",
                    'severity' => $newStatus === 'Action Required' ? 'warning' : ($newStatus === 'Approved' ? 'success' : ($newStatus === 'Rejected' ? 'danger' : 'info')),
                ]);
            } catch (\Throwable $e) {
                // Ensure notification error never rolls back core stage update
            }

            // 7. Record Audit Log
            AuditService::log(
                'UPDATE_STAGE',
                'Applications',
                $applicationId,
                "Stage transitioned from '{$oldStage}' ({$oldStatus}) to '{$newStage}' ({$newStatus})",
                ['old_stage' => $oldStage, 'old_status' => $oldStatus, 'new_stage' => $newStage, 'new_status' => $newStatus, 'comments' => $comments],
                $userId
            );

            $pdo->commit();

            return [
                'success' => true,
                'message' => "Application stage successfully advanced to '{$newStage}'.",
                'data' => [
                    'application_id' => $applicationId,
                    'application_number' => $app['application_number'],
                    'current_stage' => $newStage,
                    'status' => $newStatus
                ]
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Stage transition failed: ' . $e->getMessage()
            ];
        }
    }
}
