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
        $pdo = Database::getConnection();
        $status = $_GET['status'] ?? '';
        $sql = "SELECT t.*, u.name as assigned_to_name, a.application_number, c.full_name as customer_name
            FROM application_tasks t
            JOIN users u ON t.assigned_to = u.id
            LEFT JOIN applications a ON t.application_id = a.id
            LEFT JOIN customers c ON a.customer_id = c.id
            WHERE 1=1";

        $params = [];
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
        $input = $this->getJsonInput();
        $user = auth_user();
        $userId = $user ? (int)$user['id'] : null;

        $validator = new TaskValidator();
        if (!$validator->validate($input)) {
            $this->jsonError($validator->getFirstError() ?? 'Validation failed', $validator->getErrors(), 422);
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO application_tasks 
            (application_id, task_title, description, assigned_to, created_by, priority, due_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            !empty($input['application_id']) ? (int)$input['application_id'] : null,
            trim($input['task_title']),
            trim($input['description'] ?? ''),
            (int)$input['assigned_to'],
            $userId,
            $input['priority'] ?? 'Normal',
            $input['due_date'],
            $input['status'] ?? 'Pending'
        ]);

        $taskId = (int)$pdo->lastInsertId();

        if (!empty($input['application_id'])) {
            HealthCalculatorService::updateHealthScore((int)$input['application_id']);
        }

        AuditService::log('CREATE_TASK', 'Tasks', $taskId, "Created task '{$input['task_title']}'", $input, $userId);

        $this->jsonSuccess(['id' => $taskId], 'Task created successfully', 201);
    }

    /**
     * PUT /api/tasks/{id}
     */
    public function updateStatus(int $id): void
    {
        $input = $this->getJsonInput();
        $status = trim($input['status'] ?? 'Completed');
        $user = auth_user();
        $userId = $user ? (int)$user['id'] : null;

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
