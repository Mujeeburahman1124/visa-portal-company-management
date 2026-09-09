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

        // Direct Quick Track lookup
        $quickTrack = trim($_GET['quick_track'] ?? '');
        if ($quickTrack !== '') {
            $findStmt = $pdo->prepare("SELECT a.id FROM applications a
                JOIN customers c ON a.customer_id = c.id
                WHERE a.is_archived = 0 AND (
                    UPPER(TRIM(a.application_number)) = UPPER(TRIM(?)) OR
                    UPPER(TRIM(a.passport_number)) = UPPER(TRIM(?)) OR
                    UPPER(TRIM(a.visa_number)) = UPPER(TRIM(?)) OR
                    UPPER(TRIM(c.customer_code)) = UPPER(TRIM(?)) OR
                    TRIM(c.mobile) = TRIM(?) OR
                    TRIM(c.whatsapp) = TRIM(?)
                ) LIMIT 1");
            $findStmt->execute([$quickTrack, $quickTrack, $quickTrack, $quickTrack, $quickTrack, $quickTrack]);
            $matchedId = $findStmt->fetchColumn();
            if ($matchedId) {
                redirect("/tracking/show?id={$matchedId}");
            } else {
                set_flash("No visa/application found matching: " . htmlspecialchars($quickTrack), 'danger');
                redirect('/tracking');
            }
        }

        // Direct id routing fallback
        if (!empty($_GET['id']) && empty($_GET['view']) && empty($_GET['name']) && empty($_GET['passport'])) {
            redirect("/tracking/show?id=" . (int)$_GET['id']);
        }

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
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

        $countriesList = $pdo->query("SELECT id, name, flag_emoji FROM countries WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $servicesList = $pdo->query("SELECT id, name FROM visa_services WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $staffList = $pdo->query("SELECT id, name FROM users WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $suppliersList = $pdo->query("SELECT id, company_name FROM suppliers WHERE is_active = 1 ORDER BY company_name ASC")->fetchAll(PDO::FETCH_ASSOC);

        require_once dirname(__DIR__) . '/Views/tracking/index.php';
    }

    /**
     * Dedicated Complete Visa Tracking Details & Journey View
     */
    public function show(): void
    {
        $pdo = Database::getConnection();
        $isStaff = is_authenticated();
        $isCustomer = is_customer_authenticated();

        if (!$isStaff && !$isCustomer) {
            redirect('/login', 'Please sign in to access visa tracking details.', 'warning');
        }

        $appId = (int)($_GET['id'] ?? 0);
        $ref = trim($_GET['ref'] ?? $_GET['app_number'] ?? '');

        if ($appId <= 0 && $ref === '') {
            redirect('/tracking', 'Please specify an application ID or reference number to track.', 'danger');
        }

        // Fetch application master record with complete relations
        $query = "SELECT a.*, 
            c.id as customer_id, c.customer_code, c.full_name as customer_name, c.mobile as customer_mobile, 
            c.whatsapp as customer_whatsapp, c.email as customer_email, c.nationality as customer_nationality, 
            c.dob as customer_dob, c.gender as customer_gender, c.current_country as customer_current_country,
            vs.name as service_name, vs.entry_type as service_entry_type, vs.processing_type as service_processing_type, 
            vs.estimated_days as service_estimated_days, vs.duration as service_duration, vs.max_stay as service_max_stay, 
            vs.validity as service_validity,
            ct.name as country_name, ct.flag_emoji, ct.iso_code as country_code, ct.currency as country_currency,
            vc.name as category_name,
            u.name as staff_name, u.email as staff_email, u.phone as staff_phone, u.designation as staff_designation,
            creator.name as created_by_name,
            b.name as branch_name, b.code as branch_code,
            s.company_name as supplier_name, s.contact_person as supplier_contact, s.mobile as supplier_mobile,
            cp.passport_number as current_passport, cp.expiry_date as passport_expiry, cp.issuing_country as passport_issuing_country
            FROM applications a
            JOIN customers c ON a.customer_id = c.id
            JOIN visa_services vs ON a.visa_service_id = vs.id
            JOIN countries ct ON vs.country_id = ct.id
            LEFT JOIN visa_categories vc ON vs.category_id = vc.id
            LEFT JOIN customer_passports cp ON c.id = cp.customer_id AND cp.is_primary = 1
            LEFT JOIN users u ON a.assigned_staff_id = u.id
            LEFT JOIN users creator ON a.created_by = creator.id
            LEFT JOIN branches b ON a.branch_id = b.id
            LEFT JOIN suppliers s ON a.supplier_id = s.id
            WHERE " . ($appId > 0 ? "a.id = ?" : "UPPER(TRIM(a.application_number)) = UPPER(TRIM(?))");

        $stmt = $pdo->prepare($query);
        $stmt->execute([$appId > 0 ? $appId : $ref]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            if ($isStaff) {
                redirect('/tracking', 'No visa/application found matching the requested identifier.', 'danger');
            } else {
                redirect('/portal/dashboard', 'No visa application found.', 'danger');
            }
        }

        $appId = (int)$app['id'];

        // Strict RBAC & Customer Access Isolation
        if ($isCustomer && !$isStaff) {
            $customer = auth_customer();
            if ((int)$app['customer_id'] !== (int)$customer['id']) {
                http_response_code(403);
                die('Access Denied. You are not authorized to view another customer\'s visa tracking details.');
            }
        }

        // Staff Role Permissions
        $user = auth_user();
        $userRole = strtolower(trim((string)($user['role_slug'] ?? $user['role_name'] ?? '')));
        $canViewFinancials = $isStaff && in_array($userRole, ['super-admin', 'admin', 'accounts', 'branch-manager'], true);
        $canChangeStatus = $isStaff && !in_array($userRole, ['read-only', 'customer'], true);

        // Fetch Complete Status & Lifecycle History (Immutable audit)
        $histStmt = $pdo->prepare("SELECT ash.*, u.name as changed_by_name, u.designation as changed_by_role 
            FROM application_status_history ash 
            LEFT JOIN users u ON ash.changed_by = u.id 
            WHERE ash.application_id = ? 
            ORDER BY ash.created_at ASC");
        $histStmt->execute([$appId]);
        $stageHistory = $histStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Determine Last Updated Information
        $lastUpdateEntry = !empty($stageHistory) ? end($stageHistory) : null;
        $lastUpdatedTime = $lastUpdateEntry['created_at'] ?? $app['updated_at'] ?? $app['created_at'];
        $lastUpdatedBy = $lastUpdateEntry['changed_by_name'] ?? ($app['staff_name'] ?? 'System / Operations');

        // Configurable Visa Lifecycle Journey Stages
        $standardLifecycle = [
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
            'Approved'
        ];

        // Map chronological history to stages
        $completedStageNames = array_column($stageHistory, 'to_stage');
        $completedStageNames[] = 'Application Registered';
        $completedStageNames[] = 'New Application';

        $currentStage = $app['current_stage'];
        $currentStatus = $app['status'];

        $isApproved = ($currentStatus === 'Approved' || $currentStatus === 'Completed' || str_contains(strtolower($currentStage), 'approved') || str_contains(strtolower($currentStage), 'completed'));
        $isRejected = ($currentStatus === 'Rejected' || $currentStatus === 'Refused' || str_contains(strtolower($currentStage), 'reject'));
        $isReturned = ($currentStatus === 'Returned' || $currentStatus === 'Action Required' || str_contains(strtolower($currentStage), 'returned') || str_contains(strtolower($currentStage), 'modification'));
        $isCancelled = ($currentStatus === 'Cancelled' || str_contains(strtolower($currentStage), 'cancel'));
        $isOnHold = ($currentStatus === 'On Hold' || str_contains(strtolower($currentStage), 'hold') || str_contains(strtolower($currentStage), 'waiting'));

        // Build journey progression objects for visual timeline
        $journeyStages = [];
        $currentStageIndex = array_search($currentStage, $standardLifecycle, true);
        if ($currentStageIndex === false) {
            // Find loose match
            foreach ($standardLifecycle as $idx => $stg) {
                if (stripos($currentStage, $stg) !== false || stripos($stg, $currentStage) !== false) {
                    $currentStageIndex = $idx;
                    break;
                }
            }
            if ($currentStageIndex === false) {
                $currentStageIndex = 0;
            }
        }

        $totalStages = count($standardLifecycle);
        $completedCount = 0;

        foreach ($standardLifecycle as $idx => $stgName) {
            $state = 'PENDING';
            $matchedHist = null;

            foreach ($stageHistory as $h) {
                if ($h['to_stage'] === $stgName || ($stgName === 'Application Registered' && ($h['to_stage'] === 'New Application' || $h['from_stage'] === 'Initiation'))) {
                    $matchedHist = $h;
                    break;
                }
            }

            if ($stgName === $currentStage || ($idx === $currentStageIndex && !$isReturned && !$isRejected && !$isCancelled)) {
                if ($isApproved) {
                    $state = 'COMPLETED';
                    $completedCount++;
                } else {
                    $state = 'CURRENT';
                }
            } elseif ($idx < $currentStageIndex || in_array($stgName, $completedStageNames, true)) {
                $state = 'COMPLETED';
                $completedCount++;
            } else {
                $state = 'PENDING';
            }

            $journeyStages[] = [
                'index' => $idx + 1,
                'name' => $stgName,
                'state' => $state,
                'history' => $matchedHist,
                'is_current' => ($stgName === $currentStage || ($idx === $currentStageIndex && !$isApproved && !$isRejected && !$isReturned)),
            ];
        }

        if ($isApproved) {
            $completedCount = $totalStages;
        }
        $progressPercentage = min(100, (int)round(($completedCount / $totalStages) * 100));

        // Fetch Document Checklist & Verification Statuses
        $checklistData = \App\Services\DocumentChecklistService::getChecklist($appId);
        $documentChecklist = $checklistData['items'] ?? [];

        // Also fetch any uploaded raw documents directly
        $rawDocsStmt = $pdo->prepare("SELECT d.*, dt.name as doc_type_name, dt.code as doc_type_code, u.name as verified_by_name 
            FROM documents d 
            LEFT JOIN document_types dt ON d.document_type_id = dt.id 
            LEFT JOIN users u ON d.verified_by = u.id 
            WHERE d.application_id = ? 
            ORDER BY d.created_at DESC");
        $rawDocsStmt->execute([$appId]);
        $uploadedDocuments = $rawDocsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Fetch Official Visa Approval Records
        $apprStmt = $pdo->prepare("SELECT va.*, u.name as approved_by_name FROM visa_approvals va LEFT JOIN users u ON va.approved_by = u.id WHERE va.application_id = ?");
        $apprStmt->execute([$appId]);
        $visaApproval = $apprStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        // Fetch Official Visa Rejection Records
        $rejStmt = $pdo->prepare("SELECT vr.*, u.name as rejected_by_name FROM visa_rejections vr LEFT JOIN users u ON vr.rejected_by = u.id WHERE vr.application_id = ?");
        $rejStmt->execute([$appId]);
        $visaRejection = $rejStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        // Fetch Official Application Return Records
        $retStmt = $pdo->prepare("SELECT ar.*, u.name as returned_by_name FROM application_returns ar LEFT JOIN users u ON ar.returned_by = u.id WHERE ar.application_id = ? ORDER BY ar.created_at DESC");
        $retStmt->execute([$appId]);
        $applicationReturns = $retStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Fetch Payments for this application
        $payStmt = $pdo->prepare("SELECT p.*, u.name as received_by_name 
            FROM payments p 
            LEFT JOIN users u ON p.received_by = u.id 
            WHERE p.application_id = ? 
            ORDER BY p.payment_date DESC, p.created_at DESC");
        $payStmt->execute([$appId]);
        $appPayments = $payStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Run Real-time Health Diagnosis
        $healthDiagnosis = HealthCalculatorService::diagnose($appId);

        // Calculate Target SLA Deadline
        $deadlineStatus = 'On Track';
        $deadlineClass = 'text-success';
        if (!empty($app['expected_completion_date'])) {
            $now = strtotime(date('Y-m-d'));
            $target = strtotime($app['expected_completion_date']);
            $diff = (int)round(($target - $now) / 86400);

            if ($diff < 0) {
                $deadlineStatus = 'Overdue by ' . abs($diff) . ' day' . (abs($diff) === 1 ? '' : 's');
                $deadlineClass = 'text-danger';
            } elseif ($diff === 0) {
                $deadlineStatus = 'Due Today';
                $deadlineClass = 'text-warning';
            } else {
                $deadlineStatus = "Due in {$diff} days";
                $deadlineClass = 'text-success';
            }
        }

        require_once dirname(__DIR__) . '/Views/tracking/show.php';
    }
}

