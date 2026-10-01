<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Config\Database;
use App\Services\AuditService;
use App\Services\HealthCalculatorService;
use App\Validators\TaskValidator;
use PDO;

class TaskApiController extends ApiController
{
    /**
     * GET /api/tasks
     */
    public function index(): void
    {
        $user = $this->requireAuth();
        $scopedBranchId = $this->getScopedBranchId($user);

        $pdo = Database::getConnection();
        $status = $_GET['status'] ?? '';
        $sql = "SELECT t.*, u.name as assigned_to_name, a.application_number, c.full_name as customer_name
            FROM application_tasks t
            JOIN users u ON t.assigned_to = u.id
            LEFT JOIN applications a ON t.application_id = a.id
            LEFT JOIN customers c ON a.customer_id = c.id
            WHERE 1=1";

        $params = [];
        if ($scopedBranchId !== null) {
            $sql .= " AND (a.branch_id = ? OR (a.branch_id IS NULL AND u.branch_id = ?))";
            $params[] = $scopedBranchId;
            $params[] = $scopedBranchId;
        }

        if ($status !== '') {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY t.due_date ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->jsonSuccess($tasks, 'Tasks retrieved');
    }

    /**
     * POST /api/tasks
     */
    public function store(): void
    {
        $user = $this->requireAuth();
        $scopedBranchId = $this->getScopedBranchId($user);
        $userId = (int)$user['id'];

        $input = $this->getJsonInput();

        $validator = new TaskValidator();
        if (!$validator->validate($input)) {
            $this->jsonError($validator->getFirstError() ?? 'Validation failed', $validator->getErrors(), 422);
            return;
        }

        $appId = !empty($input['application_id']) ? (int)$input['application_id'] : null;
        $pdo = Database::getConnection();

        // Verify application access if associated
        if ($appId !== null && $scopedBranchId !== null) {
            $checkStmt = $pdo->prepare("SELECT id FROM applications WHERE id = ? AND branch_id = ?");
            $checkStmt->execute([$appId, $scopedBranchId]);
            if (!$checkStmt->fetch()) {
                $this->jsonError('Application not found or unauthorized for your branch.', [], 403);
                return;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO application_tasks 
            (application_id, task_title, description, assigned_to, created_by, priority, due_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $appId,
            trim($input['task_title']),
            trim($input['description'] ?? ''),
            (int)$input['assigned_to'],
            $userId,
            $input['priority'] ?? 'Normal',
            $input['due_date'],
            $input['status'] ?? 'Pending'
        ]);

        $taskId = (int)$pdo->lastInsertId();

        if ($appId !== null) {
            HealthCalculatorService::updateHealthScore($appId);
        }

        AuditService::log('CREATE_TASK', 'Tasks', $taskId, "Created task '{$input['task_title']}'", $input, $userId);

        $this->jsonSuccess(['id' => $taskId], 'Task created successfully', 201);
    }

    /**
     * PUT /api/tasks/{id}
     */
    public function updateStatus(int $id): void
    {
        $user = $this->requireAuth();
        $userId = (int)$user['id'];

        $input = $this->getJsonInput();
        $status = trim($input['status'] ?? 'Completed');

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE application_tasks 
            SET status = ?, 
                completed_at = CASE WHEN ? = 'Completed' THEN CURRENT_TIMESTAMP ELSE NULL END, 
                completed_by = CASE WHEN ? = 'Completed' THEN ? ELSE NULL END,
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?");
        $stmt->execute([$status, $status, $status, $userId, $id]);

        $this->jsonSuccess(['id' => $id, 'status' => $status], 'Task status updated');
    }
}
