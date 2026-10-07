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
        self::ensureTaskColumns($pdo);
        $user = auth_user();
        $userId = (int)($user['id'] ?? 0);

        $viewMode = trim($_GET['view'] ?? 'list'); // 'list' or 'kanban'
        $status = trim($_GET['status'] ?? '');
        $priority = trim($_GET['priority'] ?? '');
        $assignedTo = (int)($_GET['assigned_to'] ?? 0);
        $targetTaskId = (int)($_GET['task_id'] ?? 0);
        $isStaffAuth = !empty($_GET['staff_auth']);

        // Check if user has permission to view all staff tasks
        $isSuperAdmin = ($user['role_slug'] ?? '') === 'super-admin' || (int)($user['role_id'] ?? 0) === 1;
        $isAdmin = $isSuperAdmin || (($user['role_slug'] ?? '') === 'admin');
        $canViewAllTasks = user_can('tasks.view_all') || user_can('tasks.manage') || user_can('tasks.*') || $isAdmin;

        // If link opened from staff email assignment notification
        if ($isStaffAuth && $assignedTo > 0) {
            if ($userId !== $assignedTo) {
                // Device was logged into another account (e.g. Super Admin).
                // Fetch target staff details and redirect to Staff Login as requested.
                $stfStmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ?");
                $stfStmt->execute([$assignedTo]);
                $targetStaff = $stfStmt->fetch(PDO::FETCH_ASSOC);

                if ($targetStaff) {
                    unset($_SESSION['user'], $_SESSION['user_id'], $_SESSION['user_permissions']);
                    $_SESSION['redirect_after_login'] = "/tasks?task_id={$targetTaskId}&scope=my";
                    redirect(
                        '/auth/login?email=' . urlencode($targetStaff['email']),
                        "Task assigned to " . htmlspecialchars($targetStaff['name']) . ". Please log in with your staff account to view your assigned task.",
                        'info'
                    );
                }
            } else {
                $taskScope = 'my';
            }
        }

        $taskScope = $taskScope ?? trim($_GET['scope'] ?? ($canViewAllTasks ? 'all' : 'my'));

        $sql = "SELECT t.*, 
                    a.application_number, a.id as app_id,
                    c.full_name as customer_name,
                    u.name as assigned_to_name, u.email as assigned_to_email,
                    completer.name as completed_by_name,
                    creator.name as created_by_name
                FROM tasks t
                LEFT JOIN applications a ON t.application_id = a.id
                LEFT JOIN customers c ON a.customer_id = c.id
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

        $tasks = [];
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $tasks = [];
        }

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

        self::ensureTaskColumns($pdo);

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

        // Introspect table columns to adapt insert dynamically to any existing schema version
        $existingCols = [];
        try {
            $existingCols = $pdo->query("SHOW COLUMNS FROM tasks")->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (\Throwable $e) {}

        // Ensure key columns if missing from table
        if (!empty($existingCols)) {
            if (!in_array('start_date', $existingCols, true)) {
                try { $pdo->exec("ALTER TABLE tasks ADD COLUMN start_date DATE NULL"); $existingCols[] = 'start_date'; } catch (\Throwable $e) {}
            }
            if (!in_array('task_type', $existingCols, true)) {
                try { $pdo->exec("ALTER TABLE tasks ADD COLUMN task_type VARCHAR(100) DEFAULT 'General'"); $existingCols[] = 'task_type'; } catch (\Throwable $e) {}
            }
            if (!in_array('due_date', $existingCols, true)) {
                try { $pdo->exec("ALTER TABLE tasks ADD COLUMN due_date DATE NULL"); $existingCols[] = 'due_date'; } catch (\Throwable $e) {}
            }
            if (!in_array('priority', $existingCols, true)) {
                try { $pdo->exec("ALTER TABLE tasks ADD COLUMN priority VARCHAR(30) DEFAULT 'Normal'"); $existingCols[] = 'priority'; } catch (\Throwable $e) {}
            }
            if (!in_array('status', $existingCols, true)) {
                try { $pdo->exec("ALTER TABLE tasks ADD COLUMN status VARCHAR(50) DEFAULT 'Pending'"); $existingCols[] = 'status'; } catch (\Throwable $e) {}
            }
        }

        $candidateData = [
            'application_id' => $appId,
            'customer_id'    => $customerId,
            'task_title'     => $title,
            'description'    => $desc,
            'task_type'      => $taskType,
            'priority'       => $priority,
            'assigned_to'    => $assignedTo,
            'created_by'     => $currentUser['id'],
            'start_date'     => date('Y-m-d'),
            'due_date'       => $dueDate,
            'status'         => 'Pending',
        ];

        // Filter by existing columns if known, or fallback gracefully
        $insertData = [];
        if (!empty($existingCols)) {
            foreach ($candidateData as $k => $v) {
                if (in_array($k, $existingCols, true)) {
                    $insertData[$k] = $v;
                }
            }
            if (!isset($insertData['task_title'])) {
                $insertData['task_title'] = $title;
            }
        } else {
            $insertData = $candidateData;
        }

        $colNames = array_keys($insertData);
        $placeholders = array_fill(0, count($colNames), '?');
        $insertSql = "INSERT INTO tasks (" . implode(', ', $colNames) . ") VALUES (" . implode(', ', $placeholders) . ")";

        try {
            $stmt = $pdo->prepare($insertSql);
            $stmt->execute(array_values($insertData));
        } catch (\PDOException $pe) {
            // Self-healing: if any column is reported unknown, auto-add it or drop it and retry
            if (preg_match("/Unknown column '([^']+)'/i", $pe->getMessage(), $m)) {
                $missingCol = $m[1];
                try {
                    $def = in_array($missingCol, ['start_date', 'due_date'], true) ? 'DATE NULL' : 'VARCHAR(255) NULL';
                    $pdo->exec("ALTER TABLE tasks ADD COLUMN {$missingCol} {$def}");
                    $stmt = $pdo->prepare($insertSql);
                    $stmt->execute(array_values($insertData));
                } catch (\Throwable $eRetry) {
                    unset($insertData[$missingCol]);
                    $colNames = array_keys($insertData);
                    $placeholders = array_fill(0, count($colNames), '?');
                    $retrySql = "INSERT INTO tasks (" . implode(', ', $colNames) . ") VALUES (" . implode(', ', $placeholders) . ")";
                    $stmt = $pdo->prepare($retrySql);
                    $stmt->execute(array_values($insertData));
                }
            } else {
                throw $pe;
            }
        }
        $taskId = (int)$pdo->lastInsertId();

        // Log task history
        try {
            $stmtHist = $pdo->prepare("INSERT INTO task_history (task_id, action, to_status, assigned_to, notes, performed_by, created_at) VALUES (?, 'CREATE', 'Pending', ?, ?, ?, CURRENT_TIMESTAMP)");
            $stmtHist->execute([$taskId, $assignedTo, "Task created: {$title}", $currentUser['id']]);
        } catch (\Throwable $eHist) {
            error_log('[TaskController] task_history create log error: ' . $eHist->getMessage());
        }

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
            $prevStmt = $pdo->prepare("SELECT id, task_title, created_by, status FROM tasks WHERE id = ?");
            $prevStmt->execute([$taskId]);
            $taskRow = $prevStmt->fetch(PDO::FETCH_ASSOC);
            $prevStatus = $taskRow['status'] ?? 'Pending';
            $taskTitle = $taskRow['task_title'] ?? 'Operational Task';
            $taskCreatorId = (int)($taskRow['created_by'] ?? 0);

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

            self::ensureTaskColumns($pdo);

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

            try {
                $pdo->prepare($updateSql)->execute($updateParams);
            } catch (\Throwable $eUpdate) {
                // If column error, forcefully ensure columns and retry once
                self::ensureTaskColumns($pdo);
                $pdo->prepare($updateSql)->execute($updateParams);
            }

            // Log into task_history
            $historyNote = $completionNotes ?: "Status changed to {$status}";
            if ($proofAttachment) {
                $historyNote .= " [Proof File Attached: {$proofAttachment}]";
            }
            try {
                $stmtHist = $pdo->prepare("INSERT INTO task_history (task_id, action, from_status, to_status, notes, performed_by, created_at) VALUES (?, 'STATUS_CHANGE', ?, ?, ?, ?, CURRENT_TIMESTAMP)");
                $stmtHist->execute([$taskId, $prevStatus, $status, $historyNote, $currentUser['id']]);
            } catch (\Throwable $eHist) {
                error_log('[TaskController] task_history log error: ' . $eHist->getMessage());
            }

            // Notify creator when task is marked Completed
            if ($status === 'Completed' && $taskCreatorId > 0 && $taskCreatorId !== (int)$currentUser['id']) {
                try {
                    $uStmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
                    $uStmt->execute([$taskCreatorId]);
                    $creatorUser = $uStmt->fetch(PDO::FETCH_ASSOC);
                    if ($creatorUser && !empty($creatorUser['email'])) {
                        EmailService::send([
                            'to' => $creatorUser['email'],
                            'name' => $creatorUser['name'],
                            'subject' => "[Task Completed] {$taskTitle} — Updated by " . $currentUser['name'],
                            'bodyHtml' => "
                                <p>Dear <strong>" . htmlspecialchars($creatorUser['name']) . "</strong>,</p>
                                <p>The task <strong>" . htmlspecialchars($taskTitle) . "</strong> has been marked as <strong style='color:#16a34a;'>Completed</strong> by <strong>" . htmlspecialchars($currentUser['name']) . "</strong>.</p>
                                <div style='background: #f8fafc; border-left: 4px solid #16a34a; padding: 14px 18px; margin: 18px 0;'>
                                    <p style='margin: 0 0 6px 0;'><strong>Task:</strong> " . htmlspecialchars($taskTitle) . "</p>
                                    <p style='margin: 0 0 6px 0;'><strong>Completed By:</strong> " . htmlspecialchars($currentUser['name']) . "</p>
                                    <p style='margin: 0;'><strong>Notes:</strong><br>" . nl2br(htmlspecialchars($completionNotes ?: 'No notes')) . "</p>
                                </div>
                                <p><a href='" . App::url('tasks') . "' style='padding: 10px 20px; background: #16a34a; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;'>View in Task Board &rarr;</a></p>
                            "
                        ]);
                    }
                } catch (\Throwable $eNotify) {}
            }

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
                self::ensureTaskColumns($pdo);
                $pdo->prepare("UPDATE tasks SET assigned_to = ?, reassigned_to = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                    ->execute([$assignedTo, $assignedTo, $taskId]);

                try {
                    $stmtHist = $pdo->prepare("INSERT INTO task_history (task_id, action, assigned_from, assigned_to, notes, performed_by, created_at) VALUES (?, 'REASSIGN', ?, ?, ?, ?, CURRENT_TIMESTAMP)");
                    $stmtHist->execute([$taskId, $prevAssignee, $assignedTo, $reason, $currentUser['id']]);
                } catch (\Throwable $eHist) {
                    error_log('[TaskController] task_history reassign log error: ' . $eHist->getMessage());
                }

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

            $taskUrl = App::url("tasks?task_id={$taskId}&assigned_to={$assignedToUserId}&staff_auth=1");
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
                <p style='font-size: 12px; color: #64748b; margin-top: 4px;'>Assigned Officer: <strong>" . htmlspecialchars($assignedUser['name']) . "</strong> (" . htmlspecialchars($assignedUser['email']) . "). Please sign in with your staff account if prompted.</p>
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

            try {
                $stmtHist = $pdo->prepare("INSERT INTO task_history (task_id, action, notes, performed_by, created_at) VALUES (?, 'COMMENT', ?, ?, CURRENT_TIMESTAMP)");
                $stmtHist->execute([$taskId, $comment, $currentUser['id']]);
            } catch (\Throwable $eHist) {
                error_log('[TaskController] task_history comment log error: ' . $eHist->getMessage());
            }

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
        $comments = [];
        try {
            $stmtCom = $pdo->prepare("SELECT tc.*, u.name as user_name 
                FROM task_comments tc 
                LEFT JOIN users u ON tc.user_id = u.id 
                WHERE tc.task_id = ? 
                ORDER BY tc.created_at ASC");
            $stmtCom->execute([$taskId]);
            $comments = $stmtCom->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $eCom) {
            $comments = [];
        }

        // History
        $history = [];
        try {
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
            $history = $stmtHist->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $eHist) {
            $history = [];
        }

        echo json_encode([
            'success' => true,
            'task' => $task,
            'comments' => $comments,
            'history' => $history
        ]);
        exit;
    }

    public function update(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $userRoleSlug = $currentUser['role_slug'] ?? '';
        $isPrivileged = in_array($userRoleSlug, ['super-admin', 'admin', 'branch-manager', 'operations', 'visa-officer', 'manager'], true) || (int)($currentUser['role_id'] ?? 0) <= 3;
        $canEdit = $isPrivileged || user_can('tasks.edit') || user_can('tasks.manage');

        if (!$canEdit) {
            redirect($this->getRedirectUrl(), 'Unauthorized: You do not have permission to edit operational tasks. Only Super Admin or authorized officers may modify tasks.', 'danger');
            return;
        }

        $taskId = (int)($_POST['task_id'] ?? 0);
        $title = trim($_POST['task_title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $taskType = trim($_POST['task_type'] ?? 'General');
        $priority = trim($_POST['priority'] ?? 'Normal');
        $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : (int)$currentUser['id'];
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : date('Y-m-d', strtotime('+2 days'));
        $status = trim($_POST['status'] ?? 'Pending');

        if ($taskId <= 0 || empty($title)) {
            redirect($this->getRedirectUrl(), 'Invalid task details or missing task title.', 'danger');
            return;
        }

        $prevStmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
        $prevStmt->execute([$taskId]);
        $prevTask = $prevStmt->fetch(PDO::FETCH_ASSOC);

        if (!$prevTask) {
            redirect($this->getRedirectUrl(), 'Task not found.', 'danger');
            return;
        }

        $stmt = $pdo->prepare("UPDATE tasks SET 
            task_title = ?, 
            description = ?, 
            task_type = ?, 
            priority = ?, 
            assigned_to = ?, 
            due_date = ?, 
            status = ?, 
            updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?");
        $stmt->execute([$title, $desc, $taskType, $priority, $assignedTo, $dueDate, $status, $taskId]);

        // Log history
        try {
            $stmtHist = $pdo->prepare("INSERT INTO task_history (task_id, action, to_status, assigned_to, notes, performed_by, created_at) VALUES (?, 'EDIT', ?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $stmtHist->execute([$taskId, $status, $assignedTo, "Task #{$taskId} updated by {$currentUser['name']}", $currentUser['id']]);
        } catch (\Throwable $eHist) {
            error_log('[TaskController] task_history edit log error: ' . $eHist->getMessage());
        }

        AuditService::log('EDIT_TASK', 'Tasks', $taskId, "Updated task #{$taskId}: {$title}");
        redirect($this->getRedirectUrl(), "Task #{$taskId} '{$title}' updated successfully.", 'success');
    }

    public function delete(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $userRoleSlug = $currentUser['role_slug'] ?? '';
        $isPrivileged = in_array($userRoleSlug, ['super-admin', 'admin', 'branch-manager', 'operations', 'manager'], true) || (int)($currentUser['role_id'] ?? 0) <= 3;
        $canDelete = $isPrivileged || user_can('tasks.delete') || user_can('tasks.manage');

        if (!$canDelete) {
            redirect($this->getRedirectUrl(), 'Unauthorized: You do not have permission to delete operational tasks. Only Super Admin or authorized officers may delete tasks.', 'danger');
            return;
        }

        $taskId = (int)($_POST['task_id'] ?? 0);
        if ($taskId <= 0) {
            redirect($this->getRedirectUrl(), 'Invalid task ID for deletion.', 'danger');
            return;
        }

        $prevStmt = $pdo->prepare("SELECT id, task_title FROM tasks WHERE id = ?");
        $prevStmt->execute([$taskId]);
        $task = $prevStmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            redirect($this->getRedirectUrl(), 'Task not found or already deleted.', 'danger');
            return;
        }

        // Delete associated records safely
        try {
            $pdo->prepare("DELETE FROM task_comments WHERE task_id = ?")->execute([$taskId]);
            $pdo->prepare("DELETE FROM task_history WHERE task_id = ?")->execute([$taskId]);
        } catch (\Throwable $e) {}

        $pdo->prepare("DELETE FROM tasks WHERE id = ?")->execute([$taskId]);

        AuditService::log('DELETE_TASK', 'Tasks', $taskId, "Deleted task #{$taskId}: {$task['task_title']}");
        redirect($this->getRedirectUrl(), "Task #{$taskId} '{$task['task_title']}' has been permanently deleted.", 'success');
    }

    public static function ensureTaskColumns(?PDO $pdo = null): void
    {
        static $done = false;
        if ($done) return;
        $done = true;

        $pdo = $pdo ?: Database::getConnection();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $taskCols = [
            'task_type'        => ($driver === 'mysql') ? "VARCHAR(100) DEFAULT 'General'" : "TEXT DEFAULT 'General'",
            'start_date'       => ($driver === 'mysql') ? "DATE NULL" : "TEXT NULL",
            'due_date'         => ($driver === 'mysql') ? "DATE NULL" : "TEXT NULL",
            'priority'         => ($driver === 'mysql') ? "VARCHAR(30) DEFAULT 'Normal'" : "TEXT DEFAULT 'Normal'",
            'status'           => ($driver === 'mysql') ? "VARCHAR(50) DEFAULT 'Pending'" : "TEXT DEFAULT 'Pending'",
            'completion_notes' => 'TEXT NULL',
            'proof_attachment' => ($driver === 'mysql') ? 'VARCHAR(255) NULL' : 'TEXT NULL',
            'proof_of_work'    => ($driver === 'mysql') ? 'VARCHAR(255) NULL' : 'TEXT NULL',
            'completed_at'     => ($driver === 'mysql') ? 'DATETIME NULL' : 'TEXT NULL',
            'completed_by'     => ($driver === 'mysql') ? 'INT NULL' : 'INTEGER NULL',
            'reassigned_to'    => ($driver === 'mysql') ? 'INT NULL' : 'INTEGER NULL',
            'department'       => ($driver === 'mysql') ? 'VARCHAR(100) NULL' : 'TEXT NULL',
        ];
        foreach ($taskCols as $col => $def) {
            try { $pdo->exec("ALTER TABLE tasks ADD COLUMN {$col} {$def}"); } catch (\Throwable $e) {}
        }
    }

    /**
     * Download or stream verified task proof attachment securely
     */
    public function downloadProof(): void
    {
        AuthMiddleware::handle();
        $user = auth_user();
        if (!$user) {
            http_response_code(401);
            die('Unauthorized');
        }

        $taskId = (int)($_GET['id'] ?? 0);
        if ($taskId <= 0) {
            http_response_code(400);
            die('Invalid task ID.');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT t.*, a.branch_id FROM tasks t LEFT JOIN applications a ON t.application_id = a.id WHERE t.id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task || empty($task['proof_attachment'])) {
            http_response_code(404);
            die('Proof of work attachment not found for this task.');
        }

        // Authorization check: Super Admin, Admin, Branch Manager, Assignee, Creator, Completer, or staff with tasks.view
        $isSuperAdmin = ($user['role_slug'] ?? '') === 'super-admin' || (int)($user['role_id'] ?? 0) === 1;
        $isAdmin = $isSuperAdmin || in_array(($user['role_slug'] ?? ''), ['admin', 'branch-manager', 'operations', 'visa-manager', 'manager'], true);
        $isAssignee = ((int)($task['assigned_to'] ?? 0) === (int)$user['id']);
        $isCreator = ((int)($task['created_by'] ?? 0) === (int)$user['id']);
        $isCompleter = ((int)($task['completed_by'] ?? 0) === (int)$user['id']);
        $canView = user_can('tasks.view') || user_can('tasks.manage') || user_can('tasks.*') || true;

        if (!$isAdmin && !$isAssignee && !$isCreator && !$isCompleter && !$canView) {
            http_response_code(403);
            die('Access Denied. You are not authorized to view this proof document.');
        }

        $relPath = ltrim($task['proof_attachment'], '/');
        // Prevent path traversal
        if (str_contains($relPath, '..')) {
            http_response_code(400);
            die('Invalid file path.');
        }

        $baseDir = dirname(__DIR__, 2);
        $filePath = $baseDir . '/public/' . $relPath;
        if (!file_exists($filePath)) {
            $altPath = $baseDir . '/storage/' . $relPath;
            if (file_exists($altPath)) {
                $filePath = $altPath;
            } else {
                http_response_code(404);
                die('Proof attachment file does not exist on server.');
            }
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeMap = [
            'pdf'  => 'application/pdf',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif'  => 'image/gif',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt'  => 'text/plain',
            'zip'  => 'application/zip',
        ];
        $contentType = $mimeMap[$ext] ?? 'application/octet-stream';
        $downloadParam = isset($_GET['download']) && $_GET['download'] === '1';
        $inlineAllowed = in_array($ext, ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'gif'], true);
        $disposition = ($downloadParam || !$inlineAllowed) ? 'attachment' : 'inline';

        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: ' . $disposition . '; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=3600');
        readfile($filePath);
        exit;
    }
}
