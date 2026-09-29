<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\AuthMiddleware;
use PDO;

class DashboardController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();
        $userId = (int)($user['id'] ?? 0);
        $roleSlug = $user['role_slug'] ?? '';
        $roleName = $user['role_name'] ?? 'Staff';
        $branchId = (int)($user['branch_id'] ?? 0);

        // Core role and privilege classification
        $isSuperAdmin = ($roleSlug === 'super-admin' || (int)($user['role_id'] ?? 0) === 1 || $roleName === 'Super Admin');
        $isAdmin = $isSuperAdmin || ($roleSlug === 'admin');
        $isBranchManager = ($roleSlug === 'branch-manager');
        $isAccounts = ($roleSlug === 'accounts');
        $isProcessingStaff = ($roleSlug === 'processing-staff' || $roleSlug === 'visa-consultant' || $roleSlug === 'visa-officer');

        // Fine-grained permission capabilities
        $canCreateApp = user_can('applications.create');
        $canViewFinance = user_can('payments.view') || user_can('finance.view') || $isAdmin || $isAccounts;
        $canCreatePayment = user_can('payments.create');
        $canViewStaff = (user_can('staff.view') || $isAdmin || $isBranchManager) && !$isProcessingStaff && !$isAccounts;
        $canViewAudit = user_can('audit.view') || $isAdmin;

        // Determine dashboard operational mode
        if ($isAdmin) {
            $dashboardType = 'admin';
        } elseif ($isAccounts) {
            $dashboardType = 'accounts';
        } elseif ($isBranchManager && $branchId > 0) {
            $dashboardType = 'branch';
        } elseif ($isProcessingStaff) {
            $dashboardType = 'processing';
        } else {
            // Check if user has finance-only or desk-only permissions
            if ($canViewFinance && !$canCreateApp) {
                $dashboardType = 'accounts';
            } elseif ($isProcessingStaff || !user_can('applications.export')) {
                $dashboardType = 'processing';
            } else {
                $dashboardType = 'admin';
            }
        }

        // Scope non-super-admins strictly to their branch
        $scopedBranch = (!$isSuperAdmin && $branchId > 0) ? $branchId : (($dashboardType === 'branch' && $branchId > 0) ? $branchId : 0);

        // Branch name lookup if applicable
        $branchName = 'Head Office';
        if ($scopedBranch > 0) {
            $bStmt = $pdo->prepare("SELECT name FROM branches WHERE id = ?");
            $bStmt->execute([$scopedBranch]);
            $branchName = $bStmt->fetchColumn() ?: 'Branch Office';
        }

        // Time reference markers
        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $monthStart = date('Y-m-01');
        $in30Days = date('Y-m-d', strtotime('+30 days'));
        $in90Days = date('Y-m-d', strtotime('+90 days'));

        // Query scoping setup
        $baseWhere = "WHERE is_archived = 0";
        $baseParams = [];

        if ($scopedBranch > 0) {
            $baseWhere .= " AND branch_id = ?";
            $baseParams[] = $scopedBranch;
        } elseif ($dashboardType === 'processing') {
            $baseWhere .= " AND assigned_staff_id = ?";
            $baseParams[] = $userId;
        }

        // Helper closure to build scoped counts safely
        $buildCountQuery = function(string $extraWhere = '', array $params = []) use ($pdo, $baseWhere, $baseParams): int {
            $where = $baseWhere . ($extraWhere ? " AND ({$extraWhere})" : '');
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications {$where}");
            $stmt->execute(array_merge($baseParams, $params));
            return (int)$stmt->fetchColumn();
        };

        // 1. Operational & Lifecycle Status KPIs (Scoped)
        $kpi = [];
        $kpi['total'] = $buildCountQuery();
        $kpi['today'] = $buildCountQuery("DATE(application_date) = ?", [$today]);
        $kpi['week'] = $buildCountQuery("DATE(application_date) >= ?", [$weekStart]);
        $kpi['month'] = $buildCountQuery("DATE(application_date) >= ?", [$monthStart]);

        $kpi['pending'] = $buildCountQuery("status IN ('New Application', 'Pending Review', 'Draft')");
        $kpi['docs_required'] = $buildCountQuery("status IN ('Documents Required', 'Customer Documents Required', 'Action Required')");
        $kpi['docs_submitted'] = $buildCountQuery("status IN ('Documents Submitted', 'Documents Resubmitted')");
        $kpi['ready_submission'] = $buildCountQuery("status IN ('Ready for Submission', 'Documents Approved')");
        $kpi['submitted'] = $buildCountQuery("status IN ('Submitted / Posted', 'Submitted', 'In Process')");
        $kpi['in_process'] = $buildCountQuery("status IN ('In Process', 'Processing', 'Security / Blacklist Check')");
        $kpi['returned'] = $buildCountQuery("status IN ('Returned', 'Modification Required')");
        $kpi['approved'] = $buildCountQuery("status IN ('Approved', 'Completed', 'Visa Issued & Completed')");
        $kpi['rejected'] = $buildCountQuery("status = 'Rejected'");
        $kpi['cancelled'] = $buildCountQuery("status = 'Cancelled'");
        $kpi['on_hold'] = $buildCountQuery("status IN ('On Hold', 'Waiting for Customer', 'Waiting for Supplier', 'Waiting for Embassy')");

        $kpi['active'] = $buildCountQuery("status NOT IN ('Approved', 'Completed', 'Rejected', 'Cancelled')");
        $kpi['action_required'] = $buildCountQuery("status IN ('Action Required', 'Draft', 'Documents Pending', 'Customer Documents Required') OR priority IN ('Critical', 'Urgent')");
        $kpi['completed'] = $kpi['approved'];
        $kpi['overdue'] = $buildCountQuery("status NOT IN ('Approved', 'Completed', 'Cancelled') AND expected_completion_date < ?", [$today]);

        // Unassigned cases pool in system (useful for processing staff & managers)
        $kpi['unassigned_pool'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE is_archived = 0 AND (assigned_staff_id IS NULL OR assigned_staff_id = 0)")->fetchColumn();

        // Tasks assigned to this specific user
        $stmtMyTasksCount = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status != 'Completed'");
        $stmtMyTasksCount->execute([$userId]);
        $kpi['my_tasks'] = (int)$stmtMyTasksCount->fetchColumn();

        // 2. Operational Expiry & Compliance Alerts (Scoped)
        $alerts = [];
        if ($dashboardType === 'processing') {
            // Filter expiring documents to applicants of assigned applications
            $stmtExpPass = $pdo->prepare("SELECT COUNT(DISTINCT cp.id) FROM customer_passports cp 
                JOIN applications a ON cp.customer_id = a.customer_id 
                WHERE a.is_archived = 0 AND a.assigned_staff_id = ? AND cp.expiry_date <= ?");
            $stmtExpPass->execute([$userId, $in90Days]);
            $alerts['expiring_passports'] = (int)$stmtExpPass->fetchColumn();

            $alerts['expiring_national_ids'] = 0;
            $alerts['expiring_residences'] = 0;
            $alerts['expiring_visas'] = 0;

            $stmtMyOverdueTasks = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status != 'Completed' AND due_date < ?");
            $stmtMyOverdueTasks->execute([$userId, $today]);
            $alerts['overdue_tasks'] = (int)$stmtMyOverdueTasks->fetchColumn();
            $alerts['unpaid_applications'] = 0; // Concealed from processing desk
        } elseif ($scopedBranch > 0) {
            $stmtExpPass = $pdo->prepare("SELECT COUNT(DISTINCT cp.id) FROM customer_passports cp 
                JOIN applications a ON cp.customer_id = a.customer_id 
                WHERE a.is_archived = 0 AND a.branch_id = ? AND cp.expiry_date <= ?");
            $stmtExpPass->execute([$scopedBranch, $in90Days]);
            $alerts['expiring_passports'] = (int)$stmtExpPass->fetchColumn();

            $alerts['expiring_national_ids'] = (int)$pdo->query("SELECT COUNT(*) FROM customer_national_ids WHERE expiry_date <= '{$in90Days}'")->fetchColumn();
            $alerts['expiring_residences'] = (int)$pdo->query("SELECT COUNT(*) FROM customer_residences WHERE expiry_date <= '{$in90Days}'")->fetchColumn();
            $alerts['expiring_visas'] = (int)$pdo->query("SELECT COUNT(*) FROM visa_approvals WHERE expiry_date <= '{$in30Days}'")->fetchColumn();

            $stmtBranchTasks = $pdo->prepare("SELECT COUNT(*) FROM tasks t 
                JOIN users u ON t.assigned_to = u.id 
                WHERE u.branch_id = ? AND t.status != 'Completed' AND t.due_date < ?");
            $stmtBranchTasks->execute([$scopedBranch, $today]);
            $alerts['overdue_tasks'] = (int)$stmtBranchTasks->fetchColumn();

            $stmtBranchUnpaid = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE is_archived = 0 AND branch_id = ? AND balance_amount > 0");
            $stmtBranchUnpaid->execute([$scopedBranch]);
            $alerts['unpaid_applications'] = (int)$stmtBranchUnpaid->fetchColumn();
        } else {
            // Global alerts for admin and accounts
            $alerts['expiring_passports'] = (int)$pdo->query("SELECT COUNT(*) FROM customer_passports WHERE expiry_date <= '{$in90Days}'")->fetchColumn();
            $alerts['expiring_national_ids'] = (int)$pdo->query("SELECT COUNT(*) FROM customer_national_ids WHERE expiry_date <= '{$in90Days}'")->fetchColumn();
            $alerts['expiring_residences'] = (int)$pdo->query("SELECT COUNT(*) FROM customer_residences WHERE expiry_date <= '{$in90Days}'")->fetchColumn();
            $alerts['expiring_visas'] = (int)$pdo->query("SELECT COUNT(*) FROM visa_approvals WHERE expiry_date <= '{$in30Days}'")->fetchColumn();
            $alerts['overdue_tasks'] = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status != 'Completed' AND due_date < '{$today}'")->fetchColumn();
            $alerts['unpaid_applications'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE is_archived = 0 AND balance_amount > 0")->fetchColumn();
        }
        $kpi['expiring_passports'] = $alerts['expiring_passports'];

        // 3. Financial Metrics (Strictly checked against permissions)
        $finance = [
            'total_sales' => 0.0,
            'total_received' => 0.0,
            'outstanding' => 0.0,
            'supplier_cost' => 0.0,
            'gross_profit' => 0.0,
        ];
        if ($canViewFinance) {
            $finWhere = "WHERE is_archived = 0";
            $finParams = [];
            if ($scopedBranch > 0) {
                $finWhere .= " AND branch_id = ?";
                $finParams[] = $scopedBranch;
            }
            $fStmt = $pdo->prepare("SELECT 
                COALESCE(SUM(total_amount), 0) as total_sales,
                COALESCE(SUM(paid_amount), 0) as total_received,
                COALESCE(SUM(balance_amount), 0) as outstanding,
                COALESCE(SUM(supplier_cost), 0) as supplier_cost,
                COALESCE(SUM(gross_profit), 0) as gross_profit
                FROM applications {$finWhere}");
            $fStmt->execute($finParams);
            $financeData = $fStmt->fetch(PDO::FETCH_ASSOC);
            if ($financeData) {
                $finance['total_sales'] = (float)$financeData['total_sales'];
                $finance['total_received'] = (float)$financeData['total_received'];
                $finance['outstanding'] = (float)$financeData['outstanding'];
                $finance['supplier_cost'] = (float)$financeData['supplier_cost'];
                $finance['gross_profit'] = (float)$financeData['gross_profit'];
            }
        }

        // 4. Applications by Stage (Scoped)
        $stageStmt = $pdo->prepare("SELECT current_stage, COUNT(*) as count 
            FROM applications {$baseWhere} 
            GROUP BY current_stage ORDER BY count DESC");
        $stageStmt->execute($baseParams);
        $stages = $stageStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 5. Applications by Status (Scoped)
        $statusStmt = $pdo->prepare("SELECT status, COUNT(*) as count 
            FROM applications {$baseWhere} 
            GROUP BY status ORDER BY count DESC");
        $statusStmt->execute($baseParams);
        $statuses = $statusStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 6. Applications by Country (Scoped)
        $countryStmt = $pdo->prepare("SELECT c.name as country_name, c.flag_emoji, COUNT(a.id) as count 
            FROM applications a 
            JOIN visa_services vs ON a.visa_service_id = vs.id 
            JOIN countries c ON vs.country_id = c.id 
            {$baseWhere} 
            GROUP BY c.id ORDER BY count DESC LIMIT 6");
        $countryStmt->execute($baseParams);
        $countries = $countryStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 7. Urgent & Priority Cases Requiring Operational Attention
        if ($dashboardType === 'processing') {
            // Personal work queue: files assigned to current officer needing action
            $urgentStmt = $pdo->prepare("SELECT a.*, c.full_name as customer_name, c.mobile, vs.name as service_name, u.name as staff_name 
                FROM applications a 
                JOIN customers c ON a.customer_id = c.id 
                JOIN visa_services vs ON a.visa_service_id = vs.id 
                LEFT JOIN users u ON a.assigned_staff_id = u.id 
                WHERE a.is_archived = 0 AND a.assigned_staff_id = ? AND a.status NOT IN ('Approved', 'Completed', 'Rejected', 'Cancelled')
                ORDER BY CASE WHEN a.priority IN ('Critical', 'Urgent') THEN 0 ELSE 1 END, a.expected_completion_date ASC LIMIT 8");
            $urgentStmt->execute([$userId]);
            $urgentApplications = $urgentStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } elseif ($scopedBranch > 0) {
            $urgentStmt = $pdo->prepare("SELECT a.*, c.full_name as customer_name, c.mobile, vs.name as service_name, u.name as staff_name 
                FROM applications a 
                JOIN customers c ON a.customer_id = c.id 
                JOIN visa_services vs ON a.visa_service_id = vs.id 
                LEFT JOIN users u ON a.assigned_staff_id = u.id 
                WHERE a.is_archived = 0 AND a.branch_id = ? AND (a.priority IN ('Critical', 'Urgent') OR a.status = 'Action Required')
                ORDER BY a.expected_completion_date ASC LIMIT 6");
            $urgentStmt->execute([$scopedBranch]);
            $urgentApplications = $urgentStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } else {
            $urgentStmt = $pdo->query("SELECT a.*, c.full_name as customer_name, c.mobile, vs.name as service_name, u.name as staff_name 
                FROM applications a 
                JOIN customers c ON a.customer_id = c.id 
                JOIN visa_services vs ON a.visa_service_id = vs.id 
                LEFT JOIN users u ON a.assigned_staff_id = u.id 
                WHERE a.is_archived = 0 AND (a.priority IN ('Critical', 'Urgent') OR a.status = 'Action Required')
                ORDER BY a.expected_completion_date ASC LIMIT 6");
            $urgentApplications = $urgentStmt ? $urgentStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        }

        // 8. Upcoming Appointments (Scoped)
        if ($dashboardType === 'processing') {
            $aptStmt = $pdo->prepare("SELECT ap.*, a.application_number, c.full_name as customer_name, u.name as staff_name 
                FROM appointments ap 
                JOIN applications a ON ap.application_id = a.id 
                JOIN customers c ON a.customer_id = c.id 
                LEFT JOIN users u ON a.assigned_staff_id = u.id 
                WHERE ap.appointment_date >= ? AND ap.status != 'Cancelled' AND a.assigned_staff_id = ?
                ORDER BY ap.appointment_date ASC, ap.appointment_time ASC LIMIT 5");
            $aptStmt->execute([$today, $userId]);
            $upcomingAppointments = $aptStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } elseif ($scopedBranch > 0) {
            $aptStmt = $pdo->prepare("SELECT ap.*, a.application_number, c.full_name as customer_name, u.name as staff_name 
                FROM appointments ap 
                JOIN applications a ON ap.application_id = a.id 
                JOIN customers c ON a.customer_id = c.id 
                LEFT JOIN users u ON a.assigned_staff_id = u.id 
                WHERE ap.appointment_date >= ? AND ap.status != 'Cancelled' AND a.branch_id = ?
                ORDER BY ap.appointment_date ASC, ap.appointment_time ASC LIMIT 5");
            $aptStmt->execute([$today, $scopedBranch]);
            $upcomingAppointments = $aptStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } else {
            $aptStmt = $pdo->query("SELECT ap.*, a.application_number, c.full_name as customer_name, u.name as staff_name 
                FROM appointments ap 
                JOIN applications a ON ap.application_id = a.id 
                JOIN customers c ON a.customer_id = c.id 
                LEFT JOIN users u ON a.assigned_staff_id = u.id 
                WHERE ap.appointment_date >= '{$today}' AND ap.status != 'Cancelled' 
                ORDER BY ap.appointment_date ASC, ap.appointment_time ASC LIMIT 5");
            $upcomingAppointments = $aptStmt ? $aptStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        }

        // 9. Operational Tasks & SLA Milestones (Role Scoped)
        $canViewAllTasks = user_can('tasks.view_all') || user_can('tasks.manage') || user_can('tasks.*') || $isAdmin || $isSuperAdmin;
        $taskScope = trim($_GET['task_scope'] ?? ($canViewAllTasks && $dashboardType === 'admin' ? 'all' : 'my'));

        if ($canViewAllTasks && $taskScope === 'all') {
            $taskWhere = ($scopedBranch > 0)
                ? "WHERE (t.application_id IS NULL OR a.branch_id = ?) AND t.status != 'Completed'"
                : "WHERE t.status != 'Completed'";
            $tParams = ($scopedBranch > 0) ? [$scopedBranch, $today] : [$today];

            $myTasksStmt = $pdo->prepare("SELECT t.*, a.application_number, c.full_name as customer_name, u.name as assigned_to_name 
                FROM tasks t 
                LEFT JOIN applications a ON t.application_id = a.id 
                LEFT JOIN customers c ON a.customer_id = c.id 
                LEFT JOIN users u ON t.assigned_to = u.id
                {$taskWhere}
                ORDER BY CASE WHEN t.due_date < ? THEN 0 ELSE 1 END, t.due_date ASC LIMIT 8");
            $myTasksStmt->execute($tParams);
            $myTasks = $myTasksStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } else {
            // Strictly scoped to assigned staff member
            $myTasksStmt = $pdo->prepare("SELECT t.*, a.application_number, c.full_name as customer_name, u.name as assigned_to_name 
                FROM tasks t 
                LEFT JOIN applications a ON t.application_id = a.id 
                LEFT JOIN customers c ON a.customer_id = c.id 
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.assigned_to = ? AND t.status != 'Completed' 
                ORDER BY CASE WHEN t.due_date < ? THEN 0 ELSE 1 END, t.due_date ASC LIMIT 8");
            $myTasksStmt->execute([$userId, $today]);
            $myTasks = $myTasksStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        // 10. Staff Workload Distribution (Only for Admin and Branch Manager)
        $staffWorkload = [];
        if ($canViewStaff) {
            $swWhere = "WHERE u.is_active = 1 AND r.slug != 'read-only'";
            $swParams = [];
            if ($scopedBranch > 0) {
                $swWhere .= " AND u.branch_id = ?";
                $swParams[] = $scopedBranch;
            }
            $workloadStmt = $pdo->prepare("SELECT u.id, u.name, u.designation, r.name as role_name,
                COUNT(a.id) as total_assigned,
                SUM(CASE WHEN a.status NOT IN ('Approved', 'Completed', 'Rejected', 'Cancelled') THEN 1 ELSE 0 END) as active_cases,
                SUM(CASE WHEN a.priority IN ('Critical', 'Urgent') THEN 1 ELSE 0 END) as urgent_cases,
                (SELECT COUNT(*) FROM tasks t WHERE t.assigned_to = u.id AND t.status != 'Completed') as pending_tasks
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                LEFT JOIN applications a ON a.assigned_staff_id = u.id AND a.is_archived = 0
                {$swWhere}
                GROUP BY u.id ORDER BY active_cases DESC");
            $workloadStmt->execute($swParams);
            $staffWorkload = $workloadStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        // 11. Activity Log & Audit Trail (Scoped by permission)
        $recentActivities = [];
        if ($canViewAudit) {
            $activityStmt = $pdo->query("SELECT l.*, u.name as user_name 
                FROM activity_logs l 
                LEFT JOIN users u ON l.user_id = u.id 
                ORDER BY l.created_at DESC LIMIT 8");
            $recentActivities = $activityStmt ? $activityStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } else {
            // Show only personal audit actions to maintain security
            $activityStmt = $pdo->prepare("SELECT l.*, u.name as user_name 
                FROM activity_logs l 
                LEFT JOIN users u ON l.user_id = u.id 
                WHERE l.user_id = ? 
                ORDER BY l.created_at DESC LIMIT 6");
            $activityStmt->execute([$userId]);
            $recentActivities = $activityStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        // 12. Accounts-specific Data Collections (Only if authorized)
        $unpaidInvoices = [];
        $recentPayments = [];
        if ($canViewFinance) {
            $unpaidStmt = $pdo->query("SELECT a.id, a.application_number, a.total_amount, a.paid_amount, a.balance_amount, a.status, a.payment_status, c.full_name as customer_name, c.mobile 
                FROM applications a 
                JOIN customers c ON a.customer_id = c.id 
                WHERE a.is_archived = 0 AND a.balance_amount > 0 
                ORDER BY a.balance_amount DESC LIMIT 8");
            $unpaidInvoices = $unpaidStmt ? $unpaidStmt->fetchAll(PDO::FETCH_ASSOC) : [];

            $recentPaymentsStmt = $pdo->query("SELECT p.*, a.application_number, c.full_name as customer_name 
                FROM payments p 
                JOIN applications a ON p.application_id = a.id 
                JOIN customers c ON a.customer_id = c.id 
                ORDER BY p.payment_date DESC, p.id DESC LIMIT 8");
            $recentPayments = $recentPaymentsStmt ? $recentPaymentsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        }

        require_once dirname(__DIR__) . '/Views/dashboard/index.php';
    }
}
