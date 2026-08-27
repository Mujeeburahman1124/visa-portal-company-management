<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\AuthMiddleware;
use PDO;

class ReportController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $reportType = trim($_GET['type'] ?? 'status');
        $dateFrom = trim($_GET['date_from'] ?? date('Y-01-01'));
        $dateTo = trim($_GET['date_to'] ?? date('Y-m-d'));
        $export = trim($_GET['export'] ?? '');

        $data = [];
        $columns = [];
        $title = '';

        switch ($reportType) {
            case 'status':
                $title = 'Applications by Status Report';
                $columns = ['Status', 'Total Applications', 'Total Revenue ($)', 'Paid ($)', 'Outstanding ($)'];
                $stmt = $pdo->prepare("SELECT status as col_1, COUNT(*) as col_2, SUM(total_amount) as col_3, SUM(paid_amount) as col_4, SUM(balance_amount) as col_5 FROM applications WHERE application_date BETWEEN ? AND ? GROUP BY status ORDER BY col_2 DESC");
                $stmt->execute([$dateFrom, $dateTo]);
                $data = $stmt->fetchAll();
                break;

            case 'stage':
                $title = 'Applications by Current Stage Report';
                $columns = ['Current Stage', 'Active Applications', 'Critical Priority', 'Avg Health %'];
                $stmt = $pdo->prepare("SELECT current_stage as col_1, COUNT(*) as col_2, SUM(CASE WHEN priority='Critical' THEN 1 ELSE 0 END) as col_3, ROUND(AVG(calculated_health), 1) as col_4 FROM applications WHERE application_date BETWEEN ? AND ? GROUP BY current_stage ORDER BY col_2 DESC");
                $stmt->execute([$dateFrom, $dateTo]);
                $data = $stmt->fetchAll();
                break;

            case 'visa_type':
                $title = 'Applications by Visa Service & Type';
                $columns = ['Visa Service', 'Country', 'Applications', 'Total Value ($)', 'Gross Profit ($)'];
                $stmt = $pdo->prepare("SELECT vs.name as col_1, ct.name as col_2, COUNT(a.id) as col_3, SUM(a.total_amount) as col_4, SUM(a.gross_profit) as col_5 
                    FROM applications a JOIN visa_services vs ON a.visa_service_id = vs.id JOIN countries ct ON vs.country_id = ct.id 
                    WHERE a.application_date BETWEEN ? AND ? GROUP BY vs.id ORDER BY col_3 DESC");
                $stmt->execute([$dateFrom, $dateTo]);
                $data = $stmt->fetchAll();
                break;

            case 'nationality':
                $title = 'Applications by Nationality & Origin';
                $columns = ['Applicant Nationality', 'Applications', 'Approved', 'Rejected', 'Total Sales ($)'];
                $stmt = $pdo->prepare("SELECT nationality as col_1, COUNT(*) as col_2, 
                    SUM(CASE WHEN status='Approved' THEN 1 ELSE 0 END) as col_3,
                    SUM(CASE WHEN status='Rejected' THEN 1 ELSE 0 END) as col_4,
                    SUM(total_amount) as col_5 
                    FROM applications WHERE application_date BETWEEN ? AND ? GROUP BY nationality ORDER BY col_2 DESC");
                $stmt->execute([$dateFrom, $dateTo]);
                $data = $stmt->fetchAll();
                break;

            case 'staff':
                $title = 'Staff Performance & Workload Report';
                $columns = ['Staff Member', 'Role', 'Handled Cases', 'Approved', 'Pending', 'Completion Rate %'];
                $stmt = $pdo->prepare("SELECT u.name as col_1, r.name as col_2, COUNT(a.id) as col_3,
                    SUM(CASE WHEN a.status IN ('Approved', 'Completed') THEN 1 ELSE 0 END) as col_4,
                    SUM(CASE WHEN a.status NOT IN ('Approved', 'Completed', 'Rejected', 'Cancelled') THEN 1 ELSE 0 END) as col_5,
                    CASE WHEN COUNT(a.id) > 0 THEN ROUND((SUM(CASE WHEN a.status IN ('Approved', 'Completed') THEN 1.0 ELSE 0 END) / COUNT(a.id)) * 100, 1) ELSE 0 END as col_6
                    FROM users u JOIN roles r ON u.role_id = r.id LEFT JOIN applications a ON a.assigned_staff_id = u.id AND a.application_date BETWEEN ? AND ?
                    WHERE u.is_active = 1 GROUP BY u.id ORDER BY col_3 DESC");
                $stmt->execute([$dateFrom, $dateTo]);
                $data = $stmt->fetchAll();
                break;

            case 'pending':
                $title = 'Pending Applications & Bottlenecks';
                $columns = ['Application #', 'Customer', 'Visa Service', 'Current Stage', 'Days in System', 'Priority'];
                $stmt = $pdo->prepare("SELECT a.application_number as col_1, c.full_name as col_2, vs.name as col_3, a.current_stage as col_4, 
                    DATEDIFF(CURRENT_DATE, a.application_date) as col_5, a.priority as col_6
                    FROM applications a JOIN customers c ON a.customer_id = c.id JOIN visa_services vs ON a.visa_service_id = vs.id
                    WHERE a.status NOT IN ('Approved', 'Completed', 'Rejected', 'Cancelled') AND a.application_date BETWEEN ? AND ?
                    ORDER BY col_5 DESC");
                $stmt->execute([$dateFrom, $dateTo]);
                $data = $stmt->fetchAll();
                break;

            case 'overdue':
                $title = 'Overdue Applications Report';
                $columns = ['Application #', 'Customer', 'Visa Service', 'Expected Date', 'Days Overdue', 'Assigned Staff'];
                $stmt = $pdo->prepare("SELECT a.application_number as col_1, c.full_name as col_2, vs.name as col_3, a.expected_completion_date as col_4,
                    DATEDIFF(CURRENT_DATE, a.expected_completion_date) as col_5, u.name as col_6
                    FROM applications a JOIN customers c ON a.customer_id = c.id JOIN visa_services vs ON a.visa_service_id = vs.id LEFT JOIN users u ON a.assigned_staff_id = u.id
                    WHERE a.status NOT IN ('Approved', 'Completed', 'Cancelled') AND a.expected_completion_date < CURRENT_DATE
                    ORDER BY col_5 DESC");
                $stmt->execute([]);
                $data = $stmt->fetchAll();
                break;

            case 'completed':
                $title = 'Completed & Approved Visas Report';
                $columns = ['Application #', 'Customer', 'Visa Number', 'Country', 'Issue Date', 'Expiry Date', 'Total Fee ($)'];
                $stmt = $pdo->prepare("SELECT a.application_number as col_1, c.full_name as col_2, a.visa_number as col_3, ct.name as col_4, a.visa_issue_date as col_5, a.visa_expiry_date as col_6, a.total_amount as col_7
                    FROM applications a JOIN customers c ON a.customer_id = c.id JOIN visa_services vs ON a.visa_service_id = vs.id JOIN countries ct ON vs.country_id = ct.id
                    WHERE a.status = 'Approved' AND a.actual_completion_date BETWEEN ? AND ? ORDER BY a.actual_completion_date DESC");
                $stmt->execute([$dateFrom, $dateTo]);
                $data = $stmt->fetchAll();
                break;

            case 'rejected':
                $title = 'Rejected Applications Analysis Report';
                $columns = ['Application #', 'Customer', 'Country', 'Visa Service', 'Customer Reason', 'Internal Reason'];
                $stmt = $pdo->prepare("SELECT a.application_number as col_1, c.full_name as col_2, ct.name as col_3, vs.name as col_4, a.rejection_reason_customer as col_5, a.rejection_reason_internal as col_6
                    FROM applications a JOIN customers c ON a.customer_id = c.id JOIN visa_services vs ON a.visa_service_id = vs.id JOIN countries ct ON vs.country_id = ct.id
                    WHERE a.status = 'Rejected' ORDER BY a.updated_at DESC");
                $stmt->execute([]);
                $data = $stmt->fetchAll();
                break;

            case 'documents':
                $title = 'Document Status & Missing Checklist Report';
                $columns = ['Document Type', 'Category', 'Total Required', 'Uploaded', 'Verified', 'Rejected', 'Missing'];
                $stmt = $pdo->query("SELECT dt.name as col_1, dt.category as col_2, COUNT(d.id) as col_3,
                    SUM(CASE WHEN d.status IN ('UPLOADED', 'UNDER_REVIEW', 'VERIFIED') THEN 1 ELSE 0 END) as col_4,
                    SUM(CASE WHEN d.status = 'VERIFIED' THEN 1 ELSE 0 END) as col_5,
                    SUM(CASE WHEN d.status = 'REJECTED' THEN 1 ELSE 0 END) as col_6,
                    SUM(CASE WHEN d.status IN ('MISSING', 'Missing') THEN 1 ELSE 0 END) as col_7
                    FROM document_types dt LEFT JOIN documents d ON d.document_type_id = dt.id
                    WHERE dt.is_active = 1 GROUP BY dt.id ORDER BY col_3 DESC");
                $data = $stmt->fetchAll();
                break;

            case 'expiry':
                $title = 'Passport, ID & Visa Expiry Report';
                $columns = ['Customer Name', 'Document Type', 'Document Number', 'Expiry Date', 'Status / Warning'];
                $stmt = $pdo->query("SELECT c.full_name as col_1, 'Passport' as col_2, cp.passport_number as col_3, cp.expiry_date as col_4,
                    CASE 
                        WHEN cp.expiry_date < CURRENT_DATE THEN 'EXPIRED'
                        WHEN cp.expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY) THEN 'Critical (< 30 Days)'
                        WHEN cp.expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 60 DAY) THEN 'Warning (< 60 Days)'
                        ELSE 'Expiring (< 90 Days)'
                    END as col_5
                    FROM customer_passports cp JOIN customers c ON cp.customer_id = c.id
                    WHERE cp.expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 90 DAY)
                    UNION ALL
                    SELECT c.full_name as col_1, 'National / Emirates ID' as col_2, nid.id_number as col_3, nid.expiry_date as col_4,
                    CASE 
                        WHEN nid.expiry_date < CURRENT_DATE THEN 'EXPIRED'
                        ELSE 'Expiring Soon'
                    END as col_5
                    FROM customer_national_ids nid JOIN customers c ON nid.customer_id = c.id
                    WHERE nid.expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 60 DAY)
                    ORDER BY col_4 ASC");
                $data = $stmt->fetchAll();
                break;

            case 'processing_time':
                $title = 'Average Visa Processing Time Report';
                $columns = ['Destination Country', 'Visa Service', 'Estimated SLA (Days)', 'Actual Avg Processing (Days)', 'Completed Visas'];
                $stmt = $pdo->query("SELECT ct.name as col_1, vs.name as col_2, vs.estimated_days as col_3,
                    ROUND(AVG(DATEDIFF(a.actual_completion_date, a.application_date)), 1) as col_4,
                    COUNT(a.id) as col_5
                    FROM applications a JOIN visa_services vs ON a.visa_service_id = vs.id JOIN countries ct ON vs.country_id = ct.id
                    WHERE a.status = 'Approved' AND a.actual_completion_date IS NOT NULL
                    GROUP BY vs.id ORDER BY col_5 DESC");
                $data = $stmt->fetchAll();
                break;

            case 'finance':
            default:
                $title = 'Financial, Supplier Cost & Profit Report';
                $columns = ['Application #', 'Customer', 'Visa Service', 'Selling Price ($)', 'Supplier Cost ($)', 'Expenses ($)', 'Received ($)', 'Profit ($)'];
                $stmt = $pdo->prepare("SELECT a.application_number as col_1, c.full_name as col_2, vs.name as col_3, a.total_amount as col_4, a.supplier_cost as col_5, a.other_expenses as col_6, a.paid_amount as col_7, a.gross_profit as col_8
                    FROM applications a JOIN customers c ON a.customer_id = c.id JOIN visa_services vs ON a.visa_service_id = vs.id
                    WHERE a.application_date BETWEEN ? AND ? ORDER BY a.application_date DESC");
                $stmt->execute([$dateFrom, $dateTo]);
                $data = $stmt->fetchAll();
                break;
        }

        // CSV Export Trigger
        if ($export === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="report_' . $reportType . '_' . date('Ymd_His') . '.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, $columns);
            foreach ($data as $row) {
                $line = [];
                for ($i = 1; $i <= count($columns); $i++) {
                    $line[] = $row["col_{$i}"] ?? '';
                }
                fputcsv($output, $line);
            }
            fclose($output);
            exit;
        }

        require_once dirname(__DIR__) . '/Views/reports/index.php';
    }
}
