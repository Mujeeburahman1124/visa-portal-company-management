<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Services\AuditService;
use PDO;

class TaskController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        $viewMode = trim($_GET['view'] ?? 'list'); // 'list' or 'kanban'
        $status = trim($_GET['status'] ?? '');
        $priority = trim($_GET['priority'] ?? '');
        $assignedTo = (int)($_GET['assigned_to'] ?? 0);

        $sql = "SELECT t.*, 
                    a.application_number, a.id as app_id,
                    c.full_name as customer_name,
                    u.name as assigned_to_name, creator.name as created_by_name
                FROM tasks t
                LEFT JOIN applications a ON t.application_id = a.id
                LEFT JOIN customers c ON t.customer_id = c.id
                LEFT JOIN users u ON t.assigned_to = u.id
                LEFT JOIN users creator ON t.created_by = creator.id
                WHERE 1=1";

        $params = [];
        if ($status !== '') {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        }
        if ($priority !== '') {
            $sql .= " AND t.priority = ?";
            $params[] = $priority;
        }
        if ($assignedTo > 0) {
            $sql .= " AND t.assigned_to = ?";
            $params[] = $assignedTo;
        }

        $sql .= " ORDER BY t.status = 'Completed' ASC, t.due_date ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll();

        $staffList = $pdo->query("SELECT id, name FROM users WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
        $applications = $pdo->query("SELECT a.id, a.application_number, c.full_name as customer_name, a.customer_id FROM applications a JOIN customers c ON a.customer_id = c.id WHERE a.is_archived = 0 AND a.status NOT IN ('Approved', 'Completed') ORDER BY a.application_number ASC")->fetchAll();

        require_once dirname(__DIR__) . '/Views/tasks/index.php';
    }

    public function store(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $title = trim($_POST['task_title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $appId = !empty($_POST['application_id']) ? (int)$_POST['application_id'] : null;
        $taskType = trim($_POST['task_type'] ?? 'General');
        $priority = trim($_POST['priority'] ?? 'Normal');
        $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : (int)$currentUser['id'];
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : date('Y-m-d', strtotime('+2 days'));

        if (empty($title)) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/tasks', 'Please enter a task title.', 'danger');
        }

        $customerId = null;
        if ($appId) {
            $customerId = $pdo->query("SELECT customer_id FROM applications WHERE id = {$appId}")->fetchColumn() ?: null;
        }

        $stmt = $pdo->prepare("INSERT INTO tasks (
            application_id, customer_id, task_title, description, task_type, priority,
            assigned_to, created_by, start_date, due_date, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_DATE, ?, 'Pending')");

        $stmt->execute([$appId, $customerId, $title, $desc, $taskType, $priority, $assignedTo, $currentUser['id'], $dueDate]);
        $taskId = (int)$pdo->lastInsertId();

        AuditService::log('CREATE_TASK', 'Tasks', $taskId, "Created task: {$title}");

        redirect($_SERVER['HTTP_REFERER'] ?? '/tasks', "Task '{$title}' created successfully.", 'success');
    }

    public function updateStatus(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $taskId = (int)($_POST['task_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'Completed');

        if ($taskId > 0 && in_array($status, ['Pending', 'In Progress', 'Completed', 'Overdue', 'Cancelled'], true)) {
            $completedAt = ($status === 'Completed') ? date('Y-m-d H:i:s') : null;
            $pdo->prepare("UPDATE tasks SET status = ?, completed_at = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                ->execute([$status, $completedAt, $taskId]);

            AuditService::log('UPDATE_TASK', 'Tasks', $taskId, "Updated task status to {$status}");
            redirect($_SERVER['HTTP_REFERER'] ?? '/tasks', "Task marked as {$status}.", 'success');
        }

        redirect($_SERVER['HTTP_REFERER'] ?? '/tasks', 'Invalid task status.', 'danger');
    }
}
