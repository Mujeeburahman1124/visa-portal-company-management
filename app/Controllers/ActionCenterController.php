<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Services\AuditService;
use PDO;

class ActionCenterController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();
        $userId = (int)$user['id'];
        $userRole = strtolower($user['role'] ?? 'staff');

        $scope = trim($_GET['scope'] ?? 'my'); // 'my' or 'team'
        $activeTab = trim($_GET['tab'] ?? 'missing');

        // 1. Missing Mandatory Documents Queue
        $missingSql = "SELECT d.*, dt.name as doc_name, a.application_number, a.id as app_id, a.priority, 
                c.full_name as customer_name, c.mobile, u.name as staff_name, a.assigned_staff_id
            FROM documents d 
            JOIN document_types dt ON d.document_type_id = dt.id 
            JOIN applications a ON d.application_id = a.id 
            JOIN customers c ON a.customer_id = c.id 
            LEFT JOIN users u ON a.assigned_staff_id = u.id 
            WHERE d.status IN ('MISSING', 'Missing') AND a.status NOT IN ('Approved', 'Completed', 'Cancelled')";
        if ($scope === 'my') {
            $missingSql .= " AND a.assigned_staff_id = {$userId}";
        }
        $missingDocuments = $pdo->query($missingSql)->fetchAll(PDO::FETCH_ASSOC);

        // 2. Rejected Documents Queue
        $rejectedSql = "SELECT d.*, dt.name as doc_name, a.application_number, a.id as app_id, a.priority, 
                c.full_name as customer_name, c.mobile, u.name as staff_name, a.assigned_staff_id
            FROM documents d 
            JOIN document_types dt ON d.document_type_id = dt.id 
            JOIN applications a ON d.application_id = a.id 
            JOIN customers c ON a.customer_id = c.id 
            LEFT JOIN users u ON a.assigned_staff_id = u.id 
            WHERE d.status = 'REJECTED' AND a.status NOT IN ('Approved', 'Completed', 'Cancelled')";
        if ($scope === 'my') {
            $rejectedSql .= " AND a.assigned_staff_id = {$userId}";
        }
        $rejectedDocuments = $pdo->query($rejectedSql)->fetchAll(PDO::FETCH_ASSOC);

        // 3. Documents Pending Verification Queue
        $pendingVerifSql = "SELECT d.*, dt.name as doc_name, a.application_number, a.id as app_id, a.priority, 
                c.full_name as customer_name, c.mobile, u.name as staff_name, a.assigned_staff_id
            FROM documents d 
            JOIN document_types dt ON d.document_type_id = dt.id 
            JOIN applications a ON d.application_id = a.id 
            JOIN customers c ON a.customer_id = c.id 
            LEFT JOIN users u ON a.assigned_staff_id = u.id 
            WHERE d.status IN ('UNDER_REVIEW', 'UPLOADED') AND a.status NOT IN ('Approved', 'Completed', 'Cancelled')";
        if ($scope === 'my') {
            $pendingVerifSql .= " AND a.assigned_staff_id = {$userId}";
        }
        $pendingVerifications = $pdo->query($pendingVerifSql)->fetchAll(PDO::FETCH_ASSOC);

        // 4. Overdue Tasks Queue & All Active Tasks
        $today = date('Y-m-d');
        $tasksSql = "SELECT t.*, a.application_number, a.id as app_id, c.full_name as customer_name, 
                     u.name as staff_name, creator.name as created_by_name
            FROM tasks t 
            LEFT JOIN applications a ON t.application_id = a.id 
            LEFT JOIN customers c ON a.customer_id = c.id 
            LEFT JOIN users u ON t.assigned_to = u.id 
            LEFT JOIN users creator ON t.created_by = creator.id
            WHERE 1=1";
        if ($scope === 'my') {
            $tasksSql .= " AND t.assigned_to = {$userId}";
        }
        $tasksSql .= " ORDER BY t.due_date ASC";
        $allTasks = $pdo->query($tasksSql)->fetchAll(PDO::FETCH_ASSOC);
        $overdueTasks = array_filter($allTasks, fn($t) => $t['status'] !== 'Completed' && $t['due_date'] < $today);

        // 5. Approaching Deadlines (< 48 hours or overdue)
        $in2Days = date('Y-m-d', strtotime('+2 days'));
        $in90Days = date('Y-m-d', strtotime('+90 days'));

        $deadlinesSql = "SELECT a.*, c.full_name as customer_name, c.mobile, vs.name as service_name, u.name as staff_name 
            FROM applications a 
            JOIN customers c ON a.customer_id = c.id 
            JOIN visa_services vs ON a.visa_service_id = vs.id 
            LEFT JOIN users u ON a.assigned_staff_id = u.id 
            WHERE a.is_archived = 0 AND a.status NOT IN ('Approved', 'Completed', 'Cancelled') 
            AND a.expected_completion_date <= '{$in2Days}' ORDER BY a.expected_completion_date ASC";
        if ($scope === 'my') {
            $deadlinesSql = str_replace("WHERE a.is_archived = 0", "WHERE a.is_archived = 0 AND a.assigned_staff_id = {$userId}", $deadlinesSql);
        }
        $approachingDeadlines = $pdo->query($deadlinesSql)->fetchAll(PDO::FETCH_ASSOC);

        // 6. Expiring Passports Queue (within 90 days)
        $passportsSql = "SELECT cp.*, c.full_name as customer_name, c.mobile, c.email, c.id as cust_id 
            FROM customer_passports cp 
            JOIN customers c ON cp.customer_id = c.id 
            WHERE cp.expiry_date <= '{$in90Days}' 
            ORDER BY cp.expiry_date ASC";
        $expiringPassports = $pdo->query($passportsSql)->fetchAll(PDO::FETCH_ASSOC);

        // 7. Stuck / Bottleneck Applications (> 5 days in same stage)
        $ago5Days = date('Y-m-d H:i:s', strtotime('-5 days'));
        $stuckSql = "SELECT a.*, c.full_name as customer_name, vs.name as service_name, u.name as staff_name 
            FROM applications a 
            JOIN customers c ON a.customer_id = c.id 
            JOIN visa_services vs ON a.visa_service_id = vs.id 
            LEFT JOIN users u ON a.assigned_staff_id = u.id 
            WHERE a.is_archived = 0 AND a.status NOT IN ('Approved', 'Completed', 'Cancelled')
            AND a.updated_at <= '{$ago5Days}' 
            ORDER BY a.updated_at ASC";
        if ($scope === 'my') {
            $stuckSql = str_replace("WHERE a.is_archived = 0", "WHERE a.is_archived = 0 AND a.assigned_staff_id = {$userId}", $stuckSql);
        }
        $stuckApplications = $pdo->query($stuckSql)->fetchAll(PDO::FETCH_ASSOC);

        // 8. Staff Leave Requests
        $leaveSql = "SELECT slr.*, u.name as staff_name, u.email as staff_email, appu.name as approver_name
            FROM staff_leave_requests slr
            JOIN users u ON slr.user_id = u.id
            LEFT JOIN users appu ON slr.approver_id = appu.id
            WHERE 1=1";
        if ($scope === 'my') {
            $leaveSql .= " AND slr.user_id = {$userId}";
        }
        $leaveSql .= " ORDER BY slr.created_at DESC";
        $leaveRequests = $pdo->query($leaveSql)->fetchAll(PDO::FETCH_ASSOC);

        // 9. Staff Operational Requests
        $staffReqSql = "SELECT sr.*, u.name as staff_name, resu.name as resolver_name
            FROM staff_requests sr
            JOIN users u ON sr.user_id = u.id
            LEFT JOIN users resu ON sr.resolved_by = resu.id
            WHERE 1=1";
        if ($scope === 'my') {
            $staffReqSql .= " AND sr.user_id = {$userId}";
        }
        $staffReqSql .= " ORDER BY sr.created_at DESC";
        $staffRequests = $pdo->query($staffReqSql)->fetchAll(PDO::FETCH_ASSOC);

        // 10. Action Center History (Audit logs for operational activities)
        $historySql = "SELECT al.*, u.name as user_name
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.entity_type IN ('Tasks', 'Documents', 'Applications', 'StaffLeave', 'StaffRequest', 'ActionCenter')
            ORDER BY al.created_at DESC LIMIT 100";
        $actionHistory = $pdo->query($historySql)->fetchAll(PDO::FETCH_ASSOC);

        // Supporting data for modals
        $staffList = $pdo->query("SELECT id, name, role FROM users WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $applications = $pdo->query("SELECT a.id, a.application_number, c.full_name as customer_name 
            FROM applications a 
            JOIN customers c ON a.customer_id = c.id 
            WHERE a.is_archived = 0 AND a.status NOT IN ('Approved', 'Completed') 
            ORDER BY a.application_number ASC")->fetchAll(PDO::FETCH_ASSOC);

        $totalActionCount = count($missingDocuments) + count($rejectedDocuments) + count($pendingVerifications) + count($overdueTasks) + count($approachingDeadlines) + count($stuckApplications);

        require_once dirname(__DIR__) . '/Views/action-center/index.php';
    }

    public function storeLeave(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        $leaveType = trim($_POST['leave_type'] ?? 'Annual Leave');
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate = trim($_POST['end_date'] ?? '');
        $reason = trim($_POST['reason'] ?? '');

        if (empty($startDate) || empty($endDate) || empty($reason)) {
            redirect('/action-center?tab=leave', 'Please complete all required fields for leave application.', 'danger');
        }

        $d1 = new \DateTime($startDate);
        $d2 = new \DateTime($endDate);
        $diff = $d1->diff($d2);
        $totalDays = (int)$diff->days + 1;

        $stmt = $pdo->prepare("INSERT INTO staff_leave_requests (
            user_id, leave_type, start_date, end_date, total_days, reason, status, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'Pending', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $stmt->execute([$user['id'], $leaveType, $startDate, $endDate, $totalDays, $reason]);
        $leaveId = (int)$pdo->lastInsertId();

        AuditService::log('SUBMIT_LEAVE', 'StaffLeave', $leaveId, "Staff {$user['name']} applied for {$totalDays} days {$leaveType} ({$startDate} to {$endDate})");

        redirect('/action-center?tab=leave', "Leave request for {$totalDays} days submitted successfully for approval.", 'success');
    }

    public function approveLeave(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        $userRole = strtolower($user['role'] ?? '');
        if (!in_array($userRole, ['super-admin', 'admin', 'branch-manager'], true)) {
            redirect('/action-center?tab=leave', 'Unauthorized to approve leave requests.', 'danger');
        }

        $id = (int)($_POST['id'] ?? 0);
        $notes = trim($_POST['approver_notes'] ?? 'Approved');

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE staff_leave_requests SET 
                status = 'Approved', approver_id = ?, approver_notes = ?, approved_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?");
            $stmt->execute([$user['id'], $notes, $id]);

            AuditService::log('APPROVE_LEAVE', 'StaffLeave', $id, "Leave request #{$id} approved by {$user['name']}");
            redirect('/action-center?tab=leave', "Leave request #{$id} has been approved.", 'success');
        }

        redirect('/action-center?tab=leave', 'Invalid leave request ID.', 'danger');
    }

    public function rejectLeave(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        $userRole = strtolower($user['role'] ?? '');
        if (!in_array($userRole, ['super-admin', 'admin', 'branch-manager'], true)) {
            redirect('/action-center?tab=leave', 'Unauthorized to reject leave requests.', 'danger');
        }

        $id = (int)($_POST['id'] ?? 0);
        $notes = trim($_POST['approver_notes'] ?? 'Rejected');

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE staff_leave_requests SET 
                status = 'Rejected', approver_id = ?, approver_notes = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?");
            $stmt->execute([$user['id'], $notes, $id]);

            AuditService::log('REJECT_LEAVE', 'StaffLeave', $id, "Leave request #{$id} rejected by {$user['name']}: {$notes}");
            redirect('/action-center?tab=leave', "Leave request #{$id} has been rejected.", 'warning');
        }

        redirect('/action-center?tab=leave', 'Invalid leave request ID.', 'danger');
    }

    public function storeStaffRequest(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        $reqType = trim($_POST['request_type'] ?? 'Assistance');
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $priority = trim($_POST['priority'] ?? 'Normal');

        if (empty($title)) {
            redirect('/action-center?tab=requests', 'Please provide a request title.', 'danger');
        }

        $stmt = $pdo->prepare("INSERT INTO staff_requests (
            user_id, request_type, title, description, priority, status, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, 'Pending', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $stmt->execute([$user['id'], $reqType, $title, $desc, $priority]);
        $reqId = (int)$pdo->lastInsertId();

        AuditService::log('CREATE_REQUEST', 'StaffRequest', $reqId, "Staff {$user['name']} submitted request: {$title}");

        redirect('/action-center?tab=requests', "Request '{$title}' submitted successfully.", 'success');
    }

    public function updateStaffRequest(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? 'Completed');
        $notes = trim($_POST['resolution_notes'] ?? '');

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE staff_requests SET 
                status = ?, resolution_notes = ?, resolved_by = ?, resolved_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?");
            $stmt->execute([$status, $notes, $user['id'], $id]);

            AuditService::log('UPDATE_REQUEST', 'StaffRequest', $id, "Staff request #{$id} updated to {$status}");
            redirect('/action-center?tab=requests', "Request updated to {$status}.", 'success');
        }

        redirect('/action-center?tab=requests', 'Invalid request ID.', 'danger');
    }
}
