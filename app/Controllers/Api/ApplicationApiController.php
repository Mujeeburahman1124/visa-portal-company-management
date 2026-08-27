<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Config\Database;
use App\Services\AuditService;
use App\Services\StageTransitionService;
use App\Services\StaffAssignmentService;
use App\Services\HealthCalculatorService;
use App\Services\DocumentChecklistService;
use App\Services\DuplicateDetectionService;
use App\Validators\ApplicationValidator;
use PDO;
use Exception;

class ApplicationApiController extends ApiController
{
    /**
     * GET /api/applications
     */
    public function index(): void
    {
        $pdo = Database::getConnection();
        $status = $_GET['status'] ?? '';
        $priority = $_GET['priority'] ?? '';
        $stage = $_GET['stage'] ?? '';
        $search = trim($_GET['search'] ?? '');

        $sql = "SELECT a.*, 
                    c.full_name as customer_name, c.customer_code, c.email as customer_email, c.mobile as customer_mobile,
                    vs.name as service_name, vs.entry_type, vs.duration,
                    co.name as country_name, co.flag_emoji,
                    u.name as staff_name, u.email as staff_email
                FROM applications a
                JOIN customers c ON a.customer_id = c.id
                JOIN visa_services vs ON a.visa_service_id = vs.id
                JOIN countries co ON vs.country_id = co.id
                LEFT JOIN users u ON a.assigned_staff_id = u.id
                WHERE a.is_archived = 0";

        $params = [];
        if ($search !== '') {
            $sql .= " AND (a.application_number LIKE ? OR c.full_name LIKE ? OR c.customer_code LIKE ? OR a.passport_number LIKE ?)";
            $term = "%{$search}%";
            $params = array_merge($params, [$term, $term, $term, $term]);
        }
        if ($status !== '') {
            $sql .= " AND a.status = ?";
            $params[] = $status;
        }
        if ($priority !== '') {
            $sql .= " AND a.priority = ?";
            $params[] = $priority;
        }
        if ($stage !== '') {
            $sql .= " AND a.current_stage = ?";
            $params[] = $stage;
        }

        $sql .= " ORDER BY a.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->jsonSuccess($applications, 'Applications retrieved successfully');
    }

    /**
     * GET /api/applications/{id}
     */
    public function show(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT a.*, 
                c.full_name as customer_name, c.customer_code, c.email as customer_email, c.mobile as customer_mobile, c.nationality as customer_nationality,
                vs.name as service_name, vs.entry_type, vs.duration, vs.estimated_days,
                co.name as country_name, co.flag_emoji,
                u.name as staff_name, u.email as staff_email, u.designation as staff_designation
            FROM applications a
            JOIN customers c ON a.customer_id = c.id
            JOIN visa_services vs ON a.visa_service_id = vs.id
            JOIN countries co ON vs.country_id = co.id
            LEFT JOIN users u ON a.assigned_staff_id = u.id
            WHERE a.id = ?");
        $stmt->execute([$id]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            $this->jsonError('Application not found', [], 404);
        }

        // Attach Checklist & Stage History
        $app['checklist'] = DocumentChecklistService::getChecklist($id);
        
        $stmtHist = $pdo->prepare("SELECT h.*, u.name as changed_by_name 
            FROM application_status_history h 
            LEFT JOIN users u ON h.changed_by = u.id 
            WHERE h.application_id = ? 
            ORDER BY h.created_at ASC");
        $stmtHist->execute([$id]);
        $app['stage_history'] = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

        $this->jsonSuccess($app, 'Application details retrieved');
    }

    /**
     * POST /api/applications
     */
    public function store(): void
    {
        $input = $this->getJsonInput();
        $user = auth_user();
        $userId = $user ? (int)$user['id'] : null;

        $customerId = (int)($input['customer_id'] ?? 0);
        $serviceId = (int)($input['visa_service_id'] ?? 0);
        $priority = $input['priority'] ?? 'Normal';

        if ($customerId <= 0 || $serviceId <= 0) {
            $this->jsonError('Missing required customer_id or visa_service_id', [], 422);
        }

        $pdo = Database::getConnection();
        $custStmt = $pdo->prepare("SELECT c.*, cp.passport_number, cp.expiry_date as passport_expiry FROM customers c LEFT JOIN customer_passports cp ON c.id = cp.customer_id AND cp.is_primary = 1 WHERE c.id = ?");
        $custStmt->execute([$customerId]);
        $customer = $custStmt->fetch(PDO::FETCH_ASSOC);

        $srvStmt = $pdo->prepare("SELECT vs.*, ct.name as country_name FROM visa_services vs JOIN countries ct ON vs.country_id = ct.id WHERE vs.id = ?");
        $srvStmt->execute([$serviceId]);
        $service = $srvStmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer || !$service) {
            $this->jsonError('Invalid customer or visa service', [], 422);
        }

        $year = date('Y');
        $count = (int)$pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
        $appNumber = sprintf("VISA-%s-%05d", $year, $count + 1);

        $sellingPrice = (float)($service['selling_price'] ?? 0.0);
        $supplierCost = (float)($service['supplier_cost'] ?? 0.0);
        $taxAmount = $sellingPrice * (((float)($service['tax_rate'] ?? 0.0)) / 100.0);
        $totalAmount = $sellingPrice + $taxAmount;
        $procDays = (int)($service['estimated_days'] ?? 15);
        $expectedCompletion = date('Y-m-d', strtotime("+{$procDays} days"));

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO applications (
                application_number, customer_id, visa_service_id, branch_id, assigned_staff_id,
                current_stage, status, priority, calculated_health, health_reason,
                nationality, residence_country, passport_number, passport_expiry_date,
                application_date, expected_completion_date,
                selling_price, supplier_cost, tax_amount, total_amount, paid_amount, balance_amount,
                next_action, next_action_due_date, created_by
            ) VALUES (
                ?, ?, ?, 1, ?,
                'Application Registered', 'Registered', ?, 100, 'Application initialized.',
                ?, ?, ?, ?,
                CURRENT_DATE, ?,
                ?, ?, ?, ?, 0.00, ?,
                'Collect and verify initial required documents', ?, ?
            )");

            $nextDue = date('Y-m-d', strtotime('+3 days'));
            $stmt->execute([
                $appNumber, $customerId, $serviceId, $userId,
                $priority,
                $customer['nationality'] ?? 'Unknown', $customer['current_country'] ?? 'United Arab Emirates', $customer['passport_number'] ?? '', $customer['passport_expiry'] ?? null,
                $expectedCompletion,
                $sellingPrice, $supplierCost, $taxAmount, $totalAmount, $totalAmount,
                $nextDue, $userId
            ]);

            $appId = (int)$pdo->lastInsertId();

            $histStmt = $pdo->prepare("INSERT INTO application_status_history (application_id, from_stage, to_stage, from_status, to_status, comments, changed_by) VALUES (?, 'Initiation', 'Application Registered', 'Draft', 'Registered', 'Created via API', ?)");
            $histStmt->execute([$appId, $userId]);

            DocumentChecklistService::generateForApplication($appId, $serviceId);
            HealthCalculatorService::calculate($appId);

            $pdo->commit();

            $this->jsonSuccess([
                'id' => $appId,
                'application_number' => $appNumber,
                'status' => 'Registered',
                'current_stage' => 'Application Registered'
            ], 'Visa application created successfully', 201);
        } catch (Exception $e) {
            $pdo->rollBack();
            $this->jsonError('Failed to create application: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * POST /api/applications/{id}/stages
     */
    public function updateStage(int $id): void
    {
        $input = $this->getJsonInput();
        $user = auth_user();
        $userId = $user ? (int)$user['id'] : null;

        $newStage = trim($input['new_stage'] ?? '');
        $newStatus = trim($input['new_status'] ?? 'In Process');
        $comments = trim($input['comments'] ?? '');

        $res = StageTransitionService::transition($id, $newStage, $newStatus, $comments, $userId);

        if (!$res['success']) {
            $this->jsonError($res['message'], $res['errors'] ?? [], 422);
        }

        $this->jsonSuccess($res['data'], $res['message']);
    }

    /**
     * POST /api/applications/{id}/assign
     */
    public function assignStaff(int $id): void
    {
        $input = $this->getJsonInput();
        $user = auth_user();
        $assignedBy = $user ? (int)$user['id'] : null;

        $newStaffId = (int)($input['staff_id'] ?? 0);
        $notes = trim($input['notes'] ?? '');

        if ($newStaffId <= 0) {
            $this->jsonError('Valid staff_id is required.', ['staff_id' => 'Required']);
        }

        $res = StaffAssignmentService::assign($id, $newStaffId, $assignedBy, $notes);

        if (!$res['success']) {
            $this->jsonError($res['message'], [], 422);
        }

        $this->jsonSuccess($res['data'], $res['message']);
    }

    /**
     * GET /api/applications/duplicate-check
     */
    public function checkDuplicate(): void
    {
        $customerId = (int)($_GET['customer_id'] ?? 0);
        $serviceId = (int)($_GET['visa_service_id'] ?? 0);
        $res = DuplicateDetectionService::checkApplicationDuplicate($customerId, $serviceId);
        $this->jsonSuccess($res);
    }
}
