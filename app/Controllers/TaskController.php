<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\App;
use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Services\AuditService;
use App\Services\EmailService;
use PDO;

class TaskController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();
        $userId = (int)($user['id'] ?? 0);

        $viewMode = trim($_GET['view'] ?? 'list'); // 'list' or 'kanban'
        $status = trim($_GET['status'] ?? '');
        $priority = trim($_GET['priority'] ?? '');
        $assignedTo = (int)($_GET['assigned_to'] ?? 0);

        // Check if user has permission to view all staff tasks
        $isSuperAdmin = ($user['role_slug'] ?? '') === 'super-admin' || (int)($user['role_id'] ?? 0) === 1;
        $isAdmin = $isSuperAdmin || (($user['role_slug'] ?? '') === 'admin');
        $canViewAllTasks = user_can('tasks.view_all') || user_can('tasks.manage') || user_can('tasks.*') || $isAdmin;

        $taskScope = trim($_GET['scope'] ?? ($canViewAllTasks ? 'all' : 'my'));

        $sql = "SELECT t.*, 
                    a.application_number, a.id as app_id,
                    c.full_name as customer_name,
                    u.name as assigned_to_name, u.email as assigned_to_email,
                    completer.name as completed_by_name,
                    creator.name as created_by_name
                FROM tasks t
                LEFT JOIN applications a ON t.application_id = a.id
                LEFT JOIN customers c ON t.customer_id = c.id
                LEFT JOIN users u ON t.assigned_to = u.id
                LEFT JOIN users completer ON t.completed_by = completer.id
                LEFT JOIN users creator ON t.created_by = creator.id
                WHERE 1=1";

        $params = [];

        // Role-based scoping: regular staff ONLY see their own assigned tasks
        if (!$canViewAllTasks || $taskScope === 'my') {
            $sql .= " AND t.assigned_to = ?";
            $params[] = $userId;
        } elseif ($assignedTo > 0) {
            $sql .= " AND t.assigned_to = ?";
            $params[] = $assignedTo;
        }

        if ($status !== '') {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        }
        if ($priority !== '') {
            $sql .= " AND t.priority = ?";
            $params[] = $priority;
        }

        $sql .= " ORDER BY t.status = 'Completed' ASC, t.due_date ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $staffList = $pdo->query("SELECT id, name, email FROM users WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $applications = $pdo->query("SELECT a.id, a.application_number, c.full_name as customer_name, a.customer_id FROM applications a JOIN customers c ON a.customer_id = c.id WHERE a.is_archived = 0 AND a.status NOT IN ('Approved', 'Completed') ORDER BY a.application_number ASC")->fetchAll(PDO::FETCH_ASSOC);

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
        $appInfo = null;
        if ($appId) {
            $aStmt = $pdo->prepare("SELECT a.customer_id, a.application_number, c.full_name as applicant_name FROM applications a JOIN customers c ON a.customer_id = c.id WHERE a.id = ?");
            $aStmt->execute([$appId]);
            $appInfo = $aStmt->fetch(PDO::FETCH_ASSOC);
            if ($appInfo) {
                $customerId = (int)$appInfo['customer_id'];
            }
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

        // Send Email Notification to the Assigned Staff
        $this->sendTaskAssignmentEmail($taskId, $assignedTo, $title, $desc, $priority, $dueDate, $taskType, $appInfo, $currentUser);

        redirect($this->getRedirectUrl(), "Task '{$title}' created successfully.", 'success');
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

            // When completing a task, require proof of work done
            $proofAttachment = null;
            if ($status === 'Completed') {
                if (empty($completionNotes)) {
                    redirect($this->getRedirectUrl(), 'Proof of work required: Please provide a description of the completed work.', 'danger');
                }

                // Handle file upload for proof document/screenshot/receipt if provided
                if (!empty($_FILES['proof_file']['name']) && $_FILES['proof_file']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = dirname(__DIR__, 2) . '/public/uploads/tasks/';
                    if (!is_dir($uploadDir)) {
                        @mkdir($uploadDir, 0755, true);
                    }
                    $origName = $_FILES['proof_file']['name'];
                    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip'];
                    if (in_array($ext, $allowed, true)) {
                        $newFileName = 'proof_' . $taskId . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                        if (move_uploaded_file($_FILES['proof_file']['tmp_name'], $uploadDir . $newFileName)) {
                            $proofAttachment = 'uploads/tasks/' . $newFileName;
                        }
                    }
                }
            }

            $completedAt = ($status === 'Completed') ? date('Y-m-d H:i:s') : null;
            $completedBy = ($status === 'Completed') ? (int)$currentUser['id'] : null;

            $updateSql = "UPDATE tasks SET status = ?, completion_notes = ?, proof_of_work = ?, completed_at = ?, completed_by = ?";
            $updateParams = [$status, $completionNotes ?: null, $completionNotes ?: null, $completedAt, $completedBy];

            if ($proofAttachment) {
                $updateSql .= ", proof_attachment = ?";
                $updateParams[] = $proofAttachment;
            }

            $updateSql .= ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $updateParams[] = $taskId;

            $pdo->prepare($updateSql)->execute($updateParams);

            // Log into task_history
            $historyNote = $completionNotes ?: "Status changed to {$status}";
            if ($proofAttachment) {
                $historyNote .= " [Proof File Attached: {$proofAttachment}]";
            }
            $stmtHist = $pdo->prepare("INSERT INTO task_history (task_id, action, from_status, to_status, notes, performed_by, created_at) VALUES (?, 'STATUS_CHANGE', ?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $stmtHist->execute([$taskId, $prevStatus, $status, $historyNote, $currentUser['id']]);

            AuditService::log('UPDATE_TASK', 'Tasks', $taskId, "Updated task status from {$prevStatus} to {$status} with proof of work");
            redirect($this->getRedirectUrl(), "Task marked as {$status} with verified proof of work.", 'success');
        }

        redirect($this->getRedirectUrl(), 'Invalid task status.', 'danger');
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
            $stmtPrev = $pdo->prepare("SELECT t.*, a.application_number, c.full_name as applicant_name 
                FROM tasks t 
                LEFT JOIN applications a ON t.application_id = a.id 
                LEFT JOIN customers c ON t.customer_id = c.id 
                WHERE t.id = ?");
            $stmtPrev->execute([$taskId]);
            $task = $stmtPrev->fetch(PDO::FETCH_ASSOC);
            if ($task) {
                $prevAssignee = (int)$task['assigned_to'];
                $pdo->prepare("UPDATE tasks SET assigned_to = ?, reassigned_to = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                    ->execute([$assignedTo, $assignedTo, $taskId]);

                $stmtHist = $pdo->prepare("INSERT INTO task_history (task_id, action, assigned_from, assigned_to, notes, performed_by, created_at) VALUES (?, 'REASSIGN', ?, ?, ?, ?, CURRENT_TIMESTAMP)");
                $stmtHist->execute([$taskId, $prevAssignee, $assignedTo, $reason, $currentUser['id']]);

                AuditService::log('REASSIGN_TASK', 'Tasks', $taskId, "Reassigned task #{$taskId} to user #{$assignedTo}: {$reason}");

                // Send email notification to new assigned officer
                $appInfo = !empty($task['application_number']) ? [
                    'application_number' => $task['application_number'],
                    'applicant_name' => $task['applicant_name'] ?? '—'
                ] : null;

                $this->sendTaskAssignmentEmail(
                    $taskId,
                    $assignedTo,
                    $task['task_title'],
                    $reason . ($task['description'] ? "\nOriginal description: " . $task['description'] : ''),
                    $task['priority'] ?? 'Normal',
                    $task['due_date'] ?? date('Y-m-d', strtotime('+2 days')),
                    $task['task_type'] ?? 'General',
                    $appInfo,
                    $currentUser,
                    true
                );

                redirect($this->getRedirectUrl(), 'Task reassigned successfully and notification email dispatched.', 'success');
            }
        }
        redirect($this->getRedirectUrl(), 'Failed to reassign task.', 'danger');
    }

    /**
     * Dispatch live email notification when a task is assigned or reassigned.
     */
    private function sendTaskAssignmentEmail(
        int $taskId,
        int $assignedToUserId,
        string $title,
        string $desc,
        string $priority,
        string $dueDate,
        string $taskType,
        ?array $appInfo,
        array $assigner,
        bool $isReassignment = false
    ): void {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ?");
            $stmt->execute([$assignedToUserId]);
            $assignedUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$assignedUser || empty($assignedUser['email'])) {
                return;
            }

            $taskUrl = App::url("tasks");
            $relatedStr = !empty($appInfo['application_number'])
                ? ($appInfo['applicant_name'] . ' (App #' . $appInfo['application_number'] . ')')
                : 'General Operational Task';

            $actionTitle = $isReassignment ? "Task Reassigned to You" : "New Task Assigned to You";
            $subject = "[{$actionTitle}] {$title} — Priority: {$priority}";

            $bodyHtml = "
                <p>Dear <strong>" . htmlspecialchars($assignedUser['name']) . "</strong>,</p>
                <p>" . ($isReassignment 
                    ? "A task has been reassigned to you by <strong>" . htmlspecialchars($assigner['name']) . "</strong>:" 
                    : "You have been assigned a new operational task by <strong>" . htmlspecialchars($assigner['name']) . "</strong>:") . "</p>
                <div style='background: #f8fafc; border-left: 4px solid #2563eb; padding: 14px 18px; margin: 18px 0; border-radius: 6px;'>
                    <p style='margin: 0 0 8px 0; font-size: 16px; font-weight: bold; color: #1e293b;'>Task: " . htmlspecialchars($title) . "</p>
                    <p style='margin: 0 0 6px 0; font-size: 14px;'><strong>Priority:</strong> <span style='color: " . ($priority === 'Urgent' || $priority === 'Critical' ? '#dc2626' : '#2563eb') . "; font-weight: bold;'>" . htmlspecialchars($priority) . "</span></p>
                    <p style='margin: 0 0 6px 0; font-size: 14px;'><strong>Category:</strong> " . htmlspecialchars($taskType) . "</p>
                    <p style='margin: 0 0 6px 0; font-size: 14px;'><strong>Due Date:</strong> " . date('M j, Y', strtotime($dueDate)) . "</p>
                    <p style='margin: 0 0 6px 0; font-size: 14px;'><strong>Linked File:</strong> " . htmlspecialchars($relatedStr) . "</p>
                    " . (!empty($desc) ? "<p style='margin: 10px 0 0 0; font-size: 13px; color: #475569;'><strong>Instructions / Notes:</strong><br>" . nl2br(htmlspecialchars($desc)) . "</p>" : "") . "
                </div>
                <p style='margin: 20px 0;'>
                    <a href='{$taskUrl}' style='display: inline-block; padding: 11px 22px; background: #2563eb; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold;'>
                        Open Task Board &rarr;
                    </a>
                </p>
                <p style='font-size: 12px; color: #94a3b8; margin-top: 24px;'>This is an automated notification from " . App::COMPANY_NAME . " Task Operations Desk.</p>
            ";

            EmailService::send([
                'to' => $assignedUser['email'],
                'name' => $assignedUser['name'],
                'subject' => $subject,
                'bodyHtml' => $bodyHtml,
                'data' => [
                    'user_name' => $assignedUser['name'],
                    'task_title' => $title,
                    'priority' => $priority,
                    'due_date' => $dueDate,
                    'application_number' => $appInfo['application_number'] ?? '—',
                    'applicant_name' => $appInfo['applicant_name'] ?? '—',
                    'action_url' => $taskUrl,
                ]
            ]);
        } catch (\Throwable $e) {
            error_log('[VISA-TRACK] Task assign email exception: ' . $e->getMessage());
        }
    }

    private function getRedirectUrl(): string
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer !== '') {
            if (str_contains($referer, '/action-center')) {
                if (!str_contains($referer, 'tab=')) {
                    return $referer . (str_contains($referer, '?') ? '&' : '?') . 'tab=tasks';
                }
                return preg_replace('/tab=[^&]+/', 'tab=tasks', $referer);
            }
            return $referer;
        }
        return '/action-center?tab=tasks';
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
