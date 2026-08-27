<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use PDO;
use Exception;

class StaffAssignmentService
{
    /**
     * Reassigns an application to a new staff officer while preserving complete assignment history.
     */
    public static function assign(
        int $applicationId,
        int $newStaffId,
        ?int $assignedByUserId = null,
        ?string $notes = null
    ): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Verify Application exists
            $stmtApp = $pdo->prepare("SELECT id, application_number, assigned_staff_id FROM applications WHERE id = ?");
            $stmtApp->execute([$applicationId]);
            $app = $stmtApp->fetch(PDO::FETCH_ASSOC);

            if (!$app) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Application not found.'];
            }

            // Verify Staff exists
            $stmtStaff = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ? AND is_active = 1");
            $stmtStaff->execute([$newStaffId]);
            $staff = $stmtStaff->fetch(PDO::FETCH_ASSOC);

            if (!$staff) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Target staff member does not exist or is inactive.'];
            }

            $oldStaffId = $app['assigned_staff_id'] ? (int)$app['assigned_staff_id'] : null;

            // 1. Mark previous active assignment as unassigned in assignment history
            $stmtClose = $pdo->prepare("UPDATE application_assignments 
                SET is_current = 0, unassigned_at = CURRENT_TIMESTAMP 
                WHERE application_id = ? AND is_current = 1");
            $stmtClose->execute([$applicationId]);

            // 2. Insert new assignment history record
            $assignedBy = $assignedByUserId ?: 1;
            $stmtNew = $pdo->prepare("INSERT INTO application_assignments 
                (application_id, staff_id, assigned_to, assigned_by, assigned_at, is_current, notes) 
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, 1, ?)");
            $stmtNew->execute([
                $applicationId,
                $newStaffId,
                $newStaffId,
                $assignedBy,
                $notes ?: "Assigned visa processing file to {$staff['name']}"
            ]);

            // 3. Update application main record
            $stmtUpdateApp = $pdo->prepare("UPDATE applications 
                SET assigned_staff_id = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?");
            $stmtUpdateApp->execute([$newStaffId, $applicationId]);

            // 4. Create Notification for the new assigned officer
            $stmtNotif = $pdo->prepare("INSERT INTO notifications 
                (user_id, recipient_type, title, message, severity, link, notification_type) 
                VALUES (?, 'Staff', ?, ?, 'info', ?, 'application_assigned')");
            $stmtNotif->execute([
                $newStaffId,
                "New Case Assigned: {$app['application_number']}",
                "You have been assigned to handle visa application {$app['application_number']}.",
                "/applications/show?id={$applicationId}"
            ]);

            // 5. Log immutable audit trail
            AuditService::log(
                'ASSIGN_STAFF',
                'Applications',
                $applicationId,
                "Reassigned application {$app['application_number']} to {$staff['name']}",
                ['old_staff_id' => $oldStaffId, 'new_staff_id' => $newStaffId, 'notes' => $notes],
                $assignedByUserId
            );

            $pdo->commit();

            return [
                'success' => true,
                'message' => "Application {$app['application_number']} successfully assigned to {$staff['name']}.",
                'data' => [
                    'application_id' => $applicationId,
                    'assigned_staff_id' => $newStaffId,
                    'staff_name' => $staff['name']
                ]
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Staff assignment failed: ' . $e->getMessage()
            ];
        }
    }
}
