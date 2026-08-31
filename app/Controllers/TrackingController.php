<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Services\HealthCalculatorService;
use PDO;

class TrackingController
{
    /**
     * Dedicated Visual Visa Tracking Center
     * Sir Feedback: Search & filter by Date, Date Range, Name, Passport, Phone, Email, Visa #, Country, Visa Type, Status, Staff, Supplier.
     */
    public function index(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        $search = trim($_GET['search'] ?? '');
        $name = trim($_GET['name'] ?? '');
        $passport = trim($_GET['passport'] ?? '');
        $phone = trim($_GET['phone'] ?? '');
        $email = trim($_GET['email'] ?? '');
        $visaNumber = trim($_GET['visa_number'] ?? '');
        $appNumber = trim($_GET['app_number'] ?? '');
        $stage = trim($_GET['stage'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $countryId = (int)($_GET['country_id'] ?? 0);
        $serviceId = (int)($_GET['service_id'] ?? 0);
        $staffId = (int)($_GET['staff_id'] ?? 0);
        $supplierId = (int)($_GET['supplier_id'] ?? 0);
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');

        // Pagination
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $baseFromWhere = " FROM applications a
                JOIN customers c ON a.customer_id = c.id
                JOIN visa_services vs ON a.visa_service_id = vs.id
                JOIN countries ct ON vs.country_id = ct.id
                LEFT JOIN users u ON a.assigned_staff_id = u.id
                LEFT JOIN suppliers s ON a.supplier_id = s.id
                WHERE a.is_archived = 0";

        $filterSql = "";
        $params = [];

        if ($search !== '') {
            $filterSql .= " AND (a.application_number LIKE ? OR c.full_name LIKE ? OR a.passport_number LIKE ? OR c.mobile LIKE ? OR c.email LIKE ? OR a.visa_number LIKE ? OR a.supplier_reference LIKE ?)";
            $term = "%{$search}%";
            $params = array_merge($params, array_fill(0, 7, $term));
        }

        if ($name !== '') {
            $filterSql .= " AND c.full_name LIKE ?";
            $params[] = "%{$name}%";
        }

        if ($passport !== '') {
            $filterSql .= " AND a.passport_number LIKE ?";
            $params[] = "%{$passport}%";
        }

        if ($phone !== '') {
            $filterSql .= " AND (c.mobile LIKE ? OR c.whatsapp LIKE ?)";
            $params[] = "%{$phone}%";
            $params[] = "%{$phone}%";
        }

        if ($email !== '') {
            $filterSql .= " AND c.email LIKE ?";
            $params[] = "%{$email}%";
        }

        if ($visaNumber !== '') {
            $filterSql .= " AND a.visa_number LIKE ?";
            $params[] = "%{$visaNumber}%";
        }

        if ($appNumber !== '') {
            $filterSql .= " AND a.application_number LIKE ?";
            $params[] = "%{$appNumber}%";
        }

        if ($stage !== '' && $stage !== 'All') {
            $filterSql .= " AND a.current_stage = ?";
            $params[] = $stage;
        }

        if ($status !== '' && $status !== 'All') {
            $filterSql .= " AND a.status = ?";
            $params[] = $status;
        }

        if ($countryId > 0) {
            $filterSql .= " AND vs.country_id = ?";
            $params[] = $countryId;
        }

        if ($serviceId > 0) {
            $filterSql .= " AND a.visa_service_id = ?";
            $params[] = $serviceId;
        }

        if ($staffId > 0) {
            $filterSql .= " AND a.assigned_staff_id = ?";
            $params[] = $staffId;
        }

        if ($supplierId > 0) {
            $filterSql .= " AND a.supplier_id = ?";
            $params[] = $supplierId;
        }

        if ($dateFrom !== '') {
            $filterSql .= " AND a.application_date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $filterSql .= " AND a.application_date <= ?";
            $params[] = $dateTo;
        }

        // Count query for pagination
        $countSql = "SELECT COUNT(a.id)" . $baseFromWhere . $filterSql;
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = (int)$countStmt->fetchColumn();
        $totalPages = ceil($totalRecords / $perPage);

        // Fetch data
        $sql = "SELECT a.*, 
                    c.full_name as customer_name, c.customer_code, c.mobile as customer_mobile, c.email as customer_email,
                    vs.name as service_name, vs.entry_type as service_entry_type, vs.duration as service_duration,
                    ct.name as country_name, ct.flag_emoji,
                    u.name as staff_name,
                    s.company_name as supplier_name" 
                . $baseFromWhere . $filterSql 
                . " ORDER BY a.priority = 'Critical' DESC, a.priority = 'Urgent' DESC, a.created_at DESC LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $applications = $stmt->fetchAll();

        // Recalculate live health for all active tracking cards
        foreach ($applications as &$app) {
            $health = HealthCalculatorService::calculate((int)$app['id']);
            $app['calculated_health'] = $health['score'];
            $app['health_status'] = $health['status'];
            $app['health_reason'] = $health['summary'];
        }
        unset($app);

        // Lists for filters
        $stages = [
            'All',
            'Application Registered',
            'Pending Review',
            'Documents Required',
            'Documents Submitted',
            'Documents Under Review',
            'Documents Approved',
            'Ready for Submission',
            'Security / Blacklist Check',
            'Submitted / Posted',
            'In Process',
            'Approved',
            'Returned / Modification Required',
            'Customer Documents Required',
            'Documents Resubmitted',
            'Resubmitted',
            'Rejected',
            'Cancelled',
            'On Hold',
            'Waiting for Customer',
            'Waiting for Supplier',
            'Waiting for Embassy'
        ];

        $countriesList = $pdo->query("SELECT id, name, flag_emoji FROM countries WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
        $servicesList = $pdo->query("SELECT id, name FROM visa_services WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
        $staffList = $pdo->query("SELECT id, name FROM users WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
        $suppliersList = $pdo->query("SELECT id, company_name FROM suppliers WHERE is_active = 1 ORDER BY company_name ASC")->fetchAll();

        require_once dirname(__DIR__) . '/Views/tracking/index.php';
    }
}
