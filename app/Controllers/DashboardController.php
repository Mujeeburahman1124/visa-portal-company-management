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

        // 1. Core Periodic & Lifecycle Status KPIs
        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $monthStart = date('Y-m-01');
        $in30Days = date('Y-m-d', strtotime('+30 days'));
        $in90Days = date('Y-m-d', strtotime('+90 days'));

        $kpi = [];
        $kpi['total'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE is_archived = 0")->fetchColumn();
        $kpi['today'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE is_archived = 0 AND DATE(application_date) = '{$today}'")->fetchColumn();
        $kpi['week'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE is_archived = 0 AND DATE(application_date) >= '{$weekStart}'")->fetchColumn();
        $kpi['month'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE is_archived = 0 AND DATE(application_date) >= '{$monthStart}'")->fetchColumn();

        // Granular Status Counts
        $kpi['pending'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE is_archived = 0 AND status IN ('New Application', 'Pending Review', 'Draft')")->fetchColumn();
        $kpi['docs_required'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE is_archived = 0 AND status IN ('Documents Required', 'Customer Documents Required', 'Action Required')")->fetchColumn();
        $kpi['docs_submitted'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE is_archived = 0 AND status IN ('Documents Submitted', 'Documents Resubmitted')")->fetchColumn();
        $kpi['ready_submission'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE is_archived = 0 AND status IN ('Ready for Submission', 'Documents Approved')")->fetchColumn();
        $kpi['submitted'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE is_archived = 0 AND status IN ('Submitted / Posted', 'Submitted', 'In Process')")->fetchColumn();
        $kpi['in_process'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE is_archived = 0 AND status IN ('In Process', 'Processing', 'Security / Blacklist Check')")->fetchColumn();
        $kpi['returned'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE is_archived = 0 AND status IN ('Returned', 'Modification Required')")->fetchColumn();
        $kpi['approved'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status IN ('Approved', 'Completed', 'Visa Issued & Completed')")->fetchColumn();
        $kpi['rejected'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'Rejected'")->fetchColumn();
        $kpi['cancelled'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'Cancelled'")->fetchColumn();
        $kpi['on_hold'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status IN ('On Hold', 'Waiting for Customer', 'Waiting for Supplier', 'Waiting for Embassy')")->fetchColumn();

        // Convenience KPI Summary Aliases
        $kpi['active'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE is_archived = 0 AND status NOT IN ('Approved', 'Completed', 'Rejected', 'Cancelled')")->fetchColumn();
        $kpi['action_required'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status IN ('Action Required', 'Draft', 'Documents Pending', 'Customer Documents Required') OR priority IN ('Critical', 'Urgent')")->fetchColumn();
        $kpi['completed'] = $kpi['approved'];
        $kpi['overdue'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE is_archived = 0 AND status NOT IN ('Approved', 'Completed', 'Cancelled') AND expected_completion_date < '{$today}'")->fetchColumn();
        $kpi['expiring_passports'] = (int)$pdo->query("SELECT COUNT(*) FROM customer_passports WHERE expiry_date <= '{$in90Days}'")->fetchColumn();

        // 2. Operational & Regulatory Expiry Alerts (Section 2)
        $alerts = [];
        $alerts['expiring_passports'] = (int)$pdo->query("SELECT COUNT(*) FROM customer_passports WHERE expiry_date <= '{$in90Days}'")->fetchColumn();
        $alerts['expiring_national_ids'] = (int)$pdo->query("SELECT COUNT(*) FROM customer_national_ids WHERE expiry_date <= '{$in90Days}'")->fetchColumn();
        $alerts['expiring_residences'] = (int)$pdo->query("SELECT COUNT(*) FROM customer_residences WHERE expiry_date <= '{$in90Days}'")->fetchColumn();
        $alerts['expiring_visas'] = (int)$pdo->query("SELECT COUNT(*) FROM visa_approvals WHERE expiry_date <= '{$in30Days}'")->fetchColumn();
        $alerts['overdue_tasks'] = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status != 'Completed' AND due_date < '{$today}'")->fetchColumn();
        $alerts['unpaid_applications'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE is_archived = 0 AND balance_amount > 0")->fetchColumn();

        // 3. Financial Overview (Section 2)
        $finance = [];
        $finance['total_sales'] = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM applications WHERE is_archived = 0")->fetchColumn();
        $finance['total_received'] = (float)$pdo->query("SELECT COALESCE(SUM(paid_amount), 0) FROM applications WHERE is_archived = 0")->fetchColumn();
        $finance['outstanding'] = (float)$pdo->query("SELECT COALESCE(SUM(balance_amount), 0) FROM applications WHERE is_archived = 0")->fetchColumn();
        $finance['supplier_cost'] = (float)$pdo->query("SELECT COALESCE(SUM(supplier_cost), 0) FROM applications WHERE is_archived = 0")->fetchColumn();
        $finance['gross_profit'] = (float)$pdo->query("SELECT COALESCE(SUM(gross_profit), 0) FROM applications WHERE is_archived = 0")->fetchColumn();

        // 3. Applications by Stage
        $stageStmt = $pdo->query("SELECT current_stage, COUNT(*) as count FROM applications WHERE is_archived = 0 GROUP BY current_stage ORDER BY count DESC");
        $stages = $stageStmt->fetchAll();

        // 4. Applications by Status
        $statusStmt = $pdo->query("SELECT status, COUNT(*) as count FROM applications WHERE is_archived = 0 GROUP BY status ORDER BY count DESC");
        $statuses = $statusStmt->fetchAll();

        // 5. Applications by Country & Visa Type
        $countryStmt = $pdo->query("SELECT c.name as country_name, c.flag_emoji, COUNT(a.id) as count 
            FROM applications a 
            JOIN visa_services vs ON a.visa_service_id = vs.id 
            JOIN countries c ON vs.country_id = c.id 
            WHERE a.is_archived = 0 
            GROUP BY c.id ORDER BY count DESC LIMIT 6");
        $countries = $countryStmt->fetchAll();

        // 6. Urgent & Critical Cases requiring attention
        $urgentStmt = $pdo->query("SELECT a.*, c.full_name as customer_name, c.mobile, vs.name as service_name, u.name as staff_name 
            FROM applications a 
            JOIN customers c ON a.customer_id = c.id 
            JOIN visa_services vs ON a.visa_service_id = vs.id 
            LEFT JOIN users u ON a.assigned_staff_id = u.id 
            WHERE a.is_archived = 0 AND (a.priority IN ('Critical', 'Urgent') OR a.status = 'Action Required')
            ORDER BY a.expected_completion_date ASC LIMIT 5");
        $urgentApplications = $urgentStmt->fetchAll();

        // 7. Upcoming Appointments
        $aptStmt = $pdo->query("SELECT ap.*, a.application_number, c.full_name as customer_name, u.name as staff_name 
            FROM appointments ap 
            JOIN applications a ON ap.application_id = a.id 
            JOIN customers c ON a.customer_id = c.id 
            LEFT JOIN users u ON a.assigned_staff_id = u.id 
            WHERE ap.appointment_date >= '{$today}' AND ap.status != 'Cancelled' 
            ORDER BY ap.appointment_date ASC, ap.appointment_time ASC LIMIT 5");
        $upcomingAppointments = $aptStmt->fetchAll();

        // 8. Staff Workload Distribution
        $workloadStmt = $pdo->query("SELECT u.id, u.name, u.designation, r.name as role_name,
            COUNT(a.id) as total_assigned,
            SUM(CASE WHEN a.status NOT IN ('Approved', 'Completed', 'Rejected', 'Cancelled') THEN 1 ELSE 0 END) as active_cases,
            SUM(CASE WHEN a.priority IN ('Critical', 'Urgent') THEN 1 ELSE 0 END) as urgent_cases,
            (SELECT COUNT(*) FROM tasks t WHERE t.assigned_to = u.id AND t.status != 'Completed') as pending_tasks
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            LEFT JOIN applications a ON a.assigned_staff_id = u.id AND a.is_archived = 0
            WHERE u.is_active = 1 AND r.slug != 'read-only'
            GROUP BY u.id ORDER BY active_cases DESC");
        $staffWorkload = $workloadStmt->fetchAll();

        // 9. Recent Activity Log
        $activityStmt = $pdo->query("SELECT l.*, u.name as user_name 
            FROM activity_logs l 
            LEFT JOIN users u ON l.user_id = u.id 
            ORDER BY l.created_at DESC LIMIT 8");
        $recentActivities = $activityStmt->fetchAll();

        require_once dirname(__DIR__) . '/Views/dashboard/index.php';
    }
}
