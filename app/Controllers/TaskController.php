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

        // Log task history
        $stmtHist = $pdo->prepare("INSERT INTO task_history (task_id, action, to_status, assigned_to, notes, performed_by, created_at) VALUES (?, 'CREATE', 'Pending', ?, ?, ?, CURRENT_TIMESTAMP)");
        $stmtHist->execute([$taskId, $assignedTo, "Task created: {$title}", $currentUser['id']]);

        AuditService::log('CREATE_TASK', 'Tasks', $taskId, "Created task: {$title}");

        redirect($_SERVER['HTTP_REFERER'] ?? '/tasks', "Task '{$title}' created successfully.", 'success');
    }

    public function updateStatus(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $taskId = (int)($_POST['task_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'Completed');
        $completionNotes = trim($_POST['completion_notes'] ?? '');

        if ($taskId > 0 && in_array($status, ['Pending', 'In Progress', 'Completed', 'Overdue', 'Cancelled'], true)) {
            $prevStmt = $pdo->prepare("SELECT status FROM tasks WHERE id = ?");
            $prevStmt->execute([$taskId]);
            $prevStatus = $prevStmt->fetchColumn() ?: 'Pending';

            $completedAt = ($status === 'Completed') ? date('Y-m-d H:i:s') : null;
            $pdo->prepare("UPDATE tasks SET status = ?, completion_notes = ?, completed_at = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                ->execute([$status, $completionNotes, $completedAt, $taskId]);

            // Log into task_history
            $stmtHist = $pdo->prepare("INSERT INTO task_history (task_id, action, from_status, to_status, notes, performed_by, created_at) VALUES (?, 'STATUS_CHANGE', ?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $stmtHist->execute([$taskId, $prevStatus, $status, $completionNotes ?: "Status changed to {$status}", $currentUser['id']]);

            AuditService::log('UPDATE_TASK', 'Tasks', $taskId, "Updated task status from {$prevStatus} to {$status}");
            redirect($_SERVER['HTTP_REFERER'] ?? '/tasks', "Task marked as {$status}.", 'success');
        }

        redirect($_SERVER['HTTP_REFERER'] ?? '/tasks', 'Invalid task status.', 'danger');
    }

    public function reassign(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $taskId = (int)($_POST['task_id'] ?? 0);
        $assignedTo = (int)($_POST['assigned_to'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Task reassigned');

        if ($taskId > 0 && $assignedTo > 0) {
            $stmtPrev = $pdo->prepare("SELECT assigned_to, task_title FROM tasks WHERE id = ?");
            $stmtPrev->execute([$taskId]);
            $task = $stmtPrev->fetch(PDO::FETCH_ASSOC);
            if ($task) {
                $prevAssignee = (int)$task['assigned_to'];
                $pdo->prepare("UPDATE tasks SET assigned_to = ?, reassigned_to = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                    ->execute([$assignedTo, $assignedTo, $taskId]);

                $stmtHist = $pdo->prepare("INSERT INTO task_history (task_id, action, assigned_from, assigned_to, notes, performed_by, created_at) VALUES (?, 'REASSIGN', ?, ?, ?, ?, CURRENT_TIMESTAMP)");
                $stmtHist->execute([$taskId, $prevAssignee, $assignedTo, $reason, $currentUser['id']]);

                AuditService::log('REASSIGN_TASK', 'Tasks', $taskId, "Reassigned task #{$taskId} to user #{$assignedTo}: {$reason}");
                redirect($_SERVER['HTTP_REFERER'] ?? '/action-center?tab=tasks', 'Task reassigned successfully.', 'success');
            }
        }
        redirect($_SERVER['HTTP_REFERER'] ?? '/action-center?tab=tasks', 'Failed to reassign task.', 'danger');
    }

    public function addComment(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $taskId = (int)($_POST['task_id'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($taskId > 0 && !empty($comment)) {
            $stmt = $pdo->prepare("INSERT INTO task_comments (task_id, user_id, comment, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$taskId, $currentUser['id'], $comment]);

            $stmtHist = $pdo->prepare("INSERT INTO task_history (task_id, action, notes, performed_by, created_at) VALUES (?, 'COMMENT', ?, ?, CURRENT_TIMESTAMP)");
            $stmtHist->execute([$taskId, $comment, $currentUser['id']]);

            AuditService::log('COMMENT_TASK', 'Tasks', $taskId, "Added comment on task #{$taskId}");
            redirect($_SERVER['HTTP_REFERER'] ?? '/action-center?tab=tasks', 'Comment added successfully.', 'success');
        }
        redirect($_SERVER['HTTP_REFERER'] ?? '/action-center?tab=tasks', 'Please provide a valid comment.', 'danger');
    }

    public function details(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        header('Content-Type: application/json');

        $taskId = (int)($_GET['id'] ?? 0);
        if ($taskId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT t.*, 
                    a.application_number, a.id as app_id,
                    c.full_name as customer_name, c.mobile as customer_phone,
                    u.name as assigned_to_name, creator.name as created_by_name,
                    reassign.name as reassigned_to_name
                FROM tasks t
                LEFT JOIN applications a ON t.application_id = a.id
                LEFT JOIN customers c ON t.customer_id = c.id
                LEFT JOIN users u ON t.assigned_to = u.id
                LEFT JOIN users creator ON t.created_by = creator.id
                LEFT JOIN users reassign ON t.reassigned_to = reassign.id
                WHERE t.id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            echo json_encode(['success' => false, 'message' => 'Task not found']);
            exit;
        }

        // Comments
        $stmtCom = $pdo->prepare("SELECT tc.*, u.name as user_name 
            FROM task_comments tc 
            LEFT JOIN users u ON tc.user_id = u.id 
            WHERE tc.task_id = ? 
            ORDER BY tc.created_at ASC");
        $stmtCom->execute([$taskId]);
        $comments = $stmtCom->fetchAll(PDO::FETCH_ASSOC);

        // History
        $stmtHist = $pdo->prepare("SELECT th.*, 
                u.name as performed_by_name,
                u_from.name as assigned_from_name,
                u_to.name as assigned_to_name
            FROM task_history th
            LEFT JOIN users u ON th.performed_by = u.id
            LEFT JOIN users u_from ON th.assigned_from = u_from.id
            LEFT JOIN users u_to ON th.assigned_to = u_to.id
            WHERE th.task_id = ?
            ORDER BY th.created_at DESC");
        $stmtHist->execute([$taskId]);
        $history = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'task' => $task,
            'comments' => $comments,
            'history' => $history
        ]);
        exit;
    }
}
