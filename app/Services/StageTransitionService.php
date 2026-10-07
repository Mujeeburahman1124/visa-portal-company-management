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
        $pdo = Database::getConnection();

        // 1. Fetch current application record
        $stmt = $pdo->prepare("SELECT id, application_number, customer_id, current_stage, status, assigned_staff_id, branch_id FROM applications WHERE id = ?");
        $stmt->execute([$applicationId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            return ['success' => false, 'message' => 'Visa application not found.'];
        }

        $oldStage = (string)($app['current_stage'] ?? 'New Application');
        $oldStatus = (string)($app['status'] ?? 'Draft');

        $currentUser = auth_user();
        $isAdmin = $currentUser && in_array($currentUser['role_name'] ?? '', ['Super Admin', 'Admin'], true);

        // Branch ownership and RBAC enforcement
        if ($currentUser && !$isAdmin) {
            $roleSlug = $currentUser['role_slug'] ?? '';
            $userBranch = (int)($currentUser['branch_id'] ?? 0);
            $appBranch = (int)($app['branch_id'] ?? 0);
            $assignedStaffId = (int)($app['assigned_staff_id'] ?? 0);
            $staffId = (int)($currentUser['id'] ?? 0);

            // Enforce branch isolation
            if ($userBranch > 0 && $appBranch > 0 && $userBranch !== $appBranch) {
                return ['success' => false, 'message' => 'Unauthorized: You cannot transition applications belonging to another branch.'];
            }

            // Final decision checks (Approval / Rejection)
            $isFinalDecision = in_array($newStatus, ['Approved', 'Rejected', 'Refused'], true) 
                || in_array($newStage, ['Visa Issued & Completed', 'Application Rejected / Closed', 'Application Rejected'], true);

            if ($isFinalDecision) {
                $canApprove = user_can('applications.approve') || user_can('applications.final_decision') || in_array($roleSlug, ['visa-manager', 'branch-manager'], true);
                $canReject = user_can('applications.reject') || user_can('applications.final_decision') || in_array($roleSlug, ['visa-manager', 'branch-manager'], true);

                if (($newStatus === 'Approved' || $newStage === 'Visa Issued & Completed') && !$canApprove) {
                    return ['success' => false, 'message' => 'Unauthorized: You do not have permission to grant final approval for visa applications.'];
                }
                if (($newStatus === 'Rejected' || $newStatus === 'Refused' || str_contains($newStage, 'Rejected')) && !$canReject) {
                    return ['success' => false, 'message' => 'Unauthorized: You do not have permission to issue final rejections for visa applications.'];
                }
            } else {
                // Staff must have stage transition permission or be assigned officer
                if (!user_can('applications.stage') && !user_can('applications.edit') && !user_can('applications.manage') && $roleSlug !== 'branch-manager') {
                    if ($assignedStaffId !== $staffId) {
                        return ['success' => false, 'message' => 'Unauthorized: You do not have permission to transition stages for this application.'];
                    }
                }
            }
        }

        // Normalize status to canonical if empty or omitted
        if (empty($newStatus)) {
            $newStatus = StageValidator::getCanonicalStatus($newStage);
        }

        $validator = new StageValidator();
        if (!$validator->validate([
            'application_id' => $applicationId,
            'current_stage' => $oldStage,
            'new_stage' => $newStage,
            'new_status' => $newStatus,
            'is_admin' => $isAdmin
        ])) {
            return [
                'success' => false,
                'message' => $validator->getFirstError() ?? 'Validation failed',
                'errors' => $validator->getErrors()
            ];
        }

        // Enforce mandatory document verification before advancing to verified/submitted/approved/issued stages
        if (in_array($newStage, StageValidator::STAGES_REQUIRING_DOCUMENTS_VERIFIED, true)) {
            $unverified = DocumentChecklistService::getUnverifiedMandatoryRequirements($applicationId);
            if (!empty($unverified)) {
                return [
                    'success' => false,
                    'message' => "Stage progression to '{$newStage}' blocked: The following mandatory documents are missing or unverified: " . implode(', ', $unverified) . ". Please verify all mandatory documents first.",
                    'errors' => ['mandatory_documents' => $unverified]
                ];
            }
        }

        $pdo->beginTransaction();

        try {
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
