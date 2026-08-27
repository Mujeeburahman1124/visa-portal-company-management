<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\AuthMiddleware;
use PDO;

class ActionCenterController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();
        $userId = (int)$user['id'];

        $scope = trim($_GET['scope'] ?? 'my'); // 'my' or 'team'
        $activeTab = trim($_GET['tab'] ?? 'all');

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
        $missingDocuments = $pdo->query($missingSql)->fetchAll();

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
        $rejectedDocuments = $pdo->query($rejectedSql)->fetchAll();

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
        $pendingVerifications = $pdo->query($pendingVerifSql)->fetchAll();

        // 4. Overdue Tasks Queue
        $today = date('Y-m-d');
        $in2Days = date('Y-m-d', strtotime('+2 days'));
        $in90Days = date('Y-m-d', strtotime('+90 days'));

        $tasksSql = "SELECT t.*, a.application_number, a.id as app_id, c.full_name as customer_name, u.name as staff_name 
            FROM tasks t 
            LEFT JOIN applications a ON t.application_id = a.id 
            LEFT JOIN customers c ON a.customer_id = c.id 
            LEFT JOIN users u ON t.assigned_to = u.id 
            WHERE t.status != 'Completed' AND t.due_date < '{$today}'";
        if ($scope === 'my') {
            $tasksSql .= " AND t.assigned_to = {$userId}";
        }
        $overdueTasks = $pdo->query($tasksSql)->fetchAll();

        // 5. Approaching Deadlines (< 48 hours or overdue)
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
        $approachingDeadlines = $pdo->query($deadlinesSql)->fetchAll();

        // 6. Expiring Passports Queue (within 90 days)
        $passportsSql = "SELECT cp.*, c.full_name as customer_name, c.mobile, c.email, c.id as cust_id 
            FROM customer_passports cp 
            JOIN customers c ON cp.customer_id = c.id 
            WHERE cp.expiry_date <= '{$in90Days}' 
            ORDER BY cp.expiry_date ASC";
        $expiringPassports = $pdo->query($passportsSql)->fetchAll();

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
        $stuckApplications = $pdo->query($stuckSql)->fetchAll();

        $totalActionCount = count($missingDocuments) + count($rejectedDocuments) + count($pendingVerifications) + count($overdueTasks) + count($approachingDeadlines) + count($stuckApplications);

        require_once dirname(__DIR__) . '/Views/action-center/index.php';
    }
}
