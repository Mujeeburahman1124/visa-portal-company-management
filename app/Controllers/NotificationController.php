<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\App;
use App\Config\Database;
use App\Config\Env;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\AuditService;
use App\Services\EmailService;
use App\Services\WhatsAppService;
use App\Services\NotificationService;
use App\Services\NotificationQueueService;
use PDO;

class NotificationController
{
    /**
     * User Notification Inbox (Staff & General).
     */
    public function index(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();
        $userId = (int)($user['id'] ?? 0);

        $filter = $_GET['filter'] ?? 'all'; // all, unread, read
        $type = trim($_GET['type'] ?? '');

        $sql = "SELECT * FROM notifications WHERE (user_id = ? OR recipient_type = 'Staff')";
        $params = [$userId];

        if ($filter === 'unread') {
            $sql .= " AND is_read = 0";
        } elseif ($filter === 'read') {
            $sql .= " AND is_read = 1";
        }

        if ($type !== '') {
            $sql .= " AND notification_type = ?";
            $params[] = $type;
        }

        $sql .= " ORDER BY created_at DESC LIMIT 100";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $notifications = $stmt->fetchAll();

        // Counts
        $countUnread = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE (user_id = {$userId} OR recipient_type = 'Staff') AND is_read = 0")->fetchColumn();
        $countAll = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE (user_id = {$userId} OR recipient_type = 'Staff')")->fetchColumn();

        $types = ['Task Alert', 'Document Alert', 'Application Update', 'Appointment', 'Deadline Warning', 'System'];

        require_once dirname(__DIR__) . '/Views/notifications/index.php';
    }

    /**
     * Mark single notification as read.
     */
    public function markRead(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $redirectUrl = $_GET['redirect'] ?? null;

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$id]);
        }

        if ($redirectUrl) {
            redirect($redirectUrl);
        }

        redirect($_SERVER['HTTP_REFERER'] ?? '/notifications', 'Notification marked as read.', 'success');
    }

    /**
     * Mark all notifications read.
     */
    public function markAllRead(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();
        $userId = (int)($user['id'] ?? 0);

        $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = CURRENT_TIMESTAMP WHERE (user_id = ? OR recipient_type = 'Staff')")->execute([$userId]);
        redirect($_SERVER['HTTP_REFERER'] ?? '/notifications', 'All notifications marked as read.', 'success');
    }

    /**
     * Delete/dismiss single notification.
     */
    public function delete(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM notifications WHERE id = ?")->execute([$id]);
        }

        redirect($_SERVER['HTTP_REFERER'] ?? '/notifications', 'Notification dismissed.', 'success');
    }

    /**
     * User Notification Delivery Preferences.
     */
    public function preferences(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();
        $userId = (int)($user['id'] ?? 0);

        $eventTypes = [
            'TASK_ASSIGNED' => 'Task Assigned to Me',
            'TASK_DUE_SOON' => 'Task Due Soon / SLA Warning',
            'DOC_REJECTED' => 'Document Rejected by Officer',
            'STAGE_UPDATED' => 'Visa Application Stage Shift',
            'VISA_APPROVED' => 'Visa Issued & Approved',
            'SYSTEM_ALERT' => 'System & Security Announcements',
        ];

        // Fetch user preferences
        $prefStmt = $pdo->prepare("SELECT * FROM notification_preferences WHERE user_id = ?");
        $prefStmt->execute([$userId]);
        $rows = $prefStmt->fetchAll();
        $preferences = [];
        foreach ($rows as $r) {
            $preferences[$r['event_type']] = $r;
        }

        require_once dirname(__DIR__) . '/Views/notifications/preferences.php';
    }

    /**
     * Update user notification delivery preferences.
     */
    public function updatePreferences(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();
        $userId = (int)($user['id'] ?? 0);

        $inApp = $_POST['in_app'] ?? [];
        $email = $_POST['email'] ?? [];

        $eventTypes = ['TASK_ASSIGNED', 'TASK_DUE_SOON', 'DOC_REJECTED', 'STAGE_UPDATED', 'VISA_APPROVED', 'SYSTEM_ALERT'];

        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $pdo->prepare("INSERT INTO notification_preferences (user_id, event_type, in_app, email, updated_at) 
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
                ON CONFLICT(user_id, event_type) DO UPDATE SET 
                in_app = excluded.in_app, 
                email = excluded.email, 
                updated_at = CURRENT_TIMESTAMP");
        } else {
            $stmt = $pdo->prepare("INSERT INTO notification_preferences (user_id, event_type, in_app, email, updated_at) 
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE in_app = VALUES(in_app), email = VALUES(email), updated_at = CURRENT_TIMESTAMP");
        }

        foreach ($eventTypes as $evt) {
            $hasInApp = isset($inApp[$evt]) ? 1 : 0;
            $hasEmail = isset($email[$evt]) ? 1 : 0;
            $stmt->execute([$userId, $evt, $hasInApp, $hasEmail]);
        }

        AuditService::log('UPDATE_NOTIF_PREFS', 'Notifications', $userId, "Updated notification delivery preferences");

        redirect('/notifications/preferences', 'Notification preferences saved successfully.', 'success');
    }

    // =========================================================================
    // ADMIN NOTIFICATION CENTER, SETTINGS & LOGS (Enterprise Operations)
    // =========================================================================

    /**
     * Admin Notification Operations Dashboard & Log Center.
     */
    public function admin(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'visa-manager']);
        $pdo = Database::getConnection();

        $tab = trim($_GET['tab'] ?? 'logs');
        $channel = trim($_GET['channel'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $event = trim($_GET['event'] ?? '');
        $search = trim($_GET['search'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        // Base query for logs
        $sql = "SELECT * FROM notification_logs WHERE 1=1";
        $countSql = "SELECT COUNT(*) FROM notification_logs WHERE 1=1";
        $params = [];

        if ($channel !== '') {
            $sql .= " AND channel = ?";
            $countSql .= " AND channel = ?";
            $params[] = $channel;
        }

        if ($status !== '') {
            $sql .= " AND status = ?";
            $countSql .= " AND status = ?";
            $params[] = $status;
        }

        if ($event !== '') {
            $sql .= " AND event_type = ?";
            $countSql .= " AND event_type = ?";
            $params[] = $event;
        }

        if ($search !== '') {
            $clause = " AND (recipient_name LIKE ? OR recipient_email LIKE ? OR recipient_phone LIKE ? OR subject LIKE ? OR provider_message_id LIKE ? OR template_name LIKE ?)";
            $sql .= $clause;
            $countSql .= $clause;
            $term = "%{$search}%";
            for ($i = 0; $i < 6; $i++) {
                $params[] = $term;
            }
        }

        if ($dateFrom !== '') {
            $sql .= " AND created_at >= ?";
            $countSql .= " AND created_at >= ?";
            $params[] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== '') {
            $sql .= " AND created_at <= ?";
            $countSql .= " AND created_at <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }

        // Count total matching logs
        $stmtCount = $pdo->prepare($countSql);
        $stmtCount->execute($params);
        $totalLogs = (int)$stmtCount->fetchColumn();
        $totalPages = max(1, (int)ceil($totalLogs / $perPage));

        // Fetch paginated logs
        $sql .= " ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmtLogs = $pdo->prepare($sql);
        $stmtLogs->execute($params);
        $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // KPI Summary Metrics
        $totalSent = (int)$pdo->query("SELECT COUNT(*) FROM notification_logs WHERE status IN ('Sent', 'Simulated')")->fetchColumn();
        $totalFailed = (int)$pdo->query("SELECT COUNT(*) FROM notification_logs WHERE status = 'Failed'")->fetchColumn();
        $totalEmail = (int)$pdo->query("SELECT COUNT(*) FROM notification_logs WHERE channel = 'Email'")->fetchColumn();
        $emailSuccess = (int)$pdo->query("SELECT COUNT(*) FROM notification_logs WHERE channel = 'Email' AND status IN ('Sent', 'Simulated')")->fetchColumn();
        $totalWhatsApp = (int)$pdo->query("SELECT COUNT(*) FROM notification_logs WHERE channel = 'WhatsApp'")->fetchColumn();
        $whatsappSuccess = (int)$pdo->query("SELECT COUNT(*) FROM notification_logs WHERE channel = 'WhatsApp' AND status IN ('Sent', 'Simulated')")->fetchColumn();
        $activeQueue = (int)$pdo->query("SELECT COUNT(*) FROM notification_queue WHERE status IN ('Pending', 'Retrying')")->fetchColumn();

        $emailRate = $totalEmail > 0 ? (int)round(($emailSuccess / $totalEmail) * 100) : 100;
        $whatsappRate = $totalWhatsApp > 0 ? (int)round(($whatsappSuccess / $totalWhatsApp) * 100) : 100;

        // Fetch Settings & Templates for respective tabs
        $settings = $pdo->query("SELECT * FROM notification_settings ORDER BY event_category ASC, title ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $templates = $pdo->query("SELECT * FROM notification_templates ORDER BY event_type ASC, channel ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Group settings by category
        $groupedSettings = [];
        foreach ($settings as $s) {
            $cat = $s['event_category'] ?: 'General';
            $groupedSettings[$cat][] = $s;
        }

        require_once dirname(__DIR__) . '/Views/notifications/admin.php';
    }

    /**
     * Update Event-Level Channel Activation Settings.
     */
    public function updateSettings(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $settings = $_POST['settings'] ?? [];

        $stmt = $pdo->prepare("UPDATE notification_settings 
            SET email_enabled = ?, whatsapp_enabled = ?, in_app_enabled = ?, applicant_enabled = ?, staff_enabled = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE event_type = ?");

        foreach ($settings as $eventType => $channels) {
            $email = isset($channels['email']) ? 1 : 0;
            $whatsapp = isset($channels['whatsapp']) ? 1 : 0;
            $inApp = isset($channels['in_app']) ? 1 : 0;
            $applicant = isset($channels['applicant']) ? 1 : 0;
            $staff = isset($channels['staff']) ? 1 : 0;

            $stmt->execute([$email, $whatsapp, $inApp, $applicant, $staff, $eventType]);
        }

        AuditService::log('UPDATE_NOTIFICATION_SETTINGS', 'Settings', null, "Updated global notification event channel matrix");

        redirect('/notifications/admin?tab=settings', 'Notification event matrix saved successfully.', 'success');
    }

    /**
     * Admin Test Message Sender (Email / WhatsApp).
     */
    public function sendTest(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);

        $channel = trim($_POST['channel'] ?? 'Email');
        $recipient = trim($_POST['recipient'] ?? '');
        $subject = trim($_POST['subject'] ?? 'Test Message from VISA TRACK');
        $message = trim($_POST['message'] ?? 'This is a test notification from the VISA TRACK Operations System.');
        $templateName = trim($_POST['template_name'] ?? '');

        if (empty($recipient)) {
            redirect('/notifications/admin?tab=test', 'Please provide a valid test recipient email or phone number.', 'danger');
        }

        if ($channel === 'Email') {
            $result = EmailService::send([
                'to' => $recipient,
                'name' => 'Admin Test Recipient',
                'subject' => '[TEST] ' . $subject,
                'bodyHtml' => "<p><strong>TEST MESSAGE</strong></p><p>" . nl2br(htmlspecialchars($message)) . "</p><p style='font-size:12px; color:#64748b;'>Sent by Administrator: " . htmlspecialchars(auth_user()['name'] ?? 'Admin') . " at " . date('Y-m-d H:i:s') . "</p>",
                'data' => [
                    'applicantName' => 'Test Applicant',
                    'applicationNumber' => 'VISA-TEST-00001',
                    'status' => 'Testing',
                    'companyName' => (string)Env::get('COMPANY_NAME', App::COMPANY_NAME),
                ]
            ]);

            if ($result['success']) {
                $providerInfo = $result['provider'] . ($result['simulated'] ? ' (Simulation Mode)' : '');
                redirect('/notifications/admin?tab=test', "Test Email dispatched successfully via {$providerInfo}! Message ID: {$result['message_id']}", 'success');
            } else {
                redirect('/notifications/admin?tab=test', "Email delivery failed: {$result['error']}", 'danger');
            }
        } elseif ($channel === 'WhatsApp') {
            $result = WhatsAppService::send([
                'phoneNumber' => $recipient,
                'templateName' => $templateName ?: null,
                'languageCode' => 'en_US',
                'variables' => ['Test Applicant', 'VISA-TEST-00001', 'Approved', App::COMPANY_NAME],
                'messageText' => "[TEST MESSAGE]\n\n{$message}\n\nSent at: " . date('Y-m-d H:i:s'),
                'data' => [
                    'applicantName' => 'Test Applicant',
                    'applicationNumber' => 'VISA-TEST-00001',
                    'companyName' => App::COMPANY_NAME,
                ]
            ]);

            if ($result['success']) {
                $normPhone = $result['normalized_phone'] ?? $recipient;
                $simTag = $result['simulated'] ? ' (Simulated)' : '';
                redirect('/notifications/admin?tab=test', "Test WhatsApp message sent to {$normPhone}! ID: {$result['message_id']}{$simTag}", 'success');
            } else {
                redirect('/notifications/admin?tab=test', "WhatsApp delivery failed: {$result['error']}", 'danger');
            }
        }

        redirect('/notifications/admin?tab=test', 'Unsupported test channel.', 'danger');
    }

    /**
     * Retry a failed notification log entry.
     */
    public function retryLog(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);

        $logId = (int)($_POST['log_id'] ?? $_GET['id'] ?? 0);
        if ($logId <= 0) {
            redirect('/notifications/admin', 'Invalid notification log record.', 'danger');
        }

        $result = NotificationQueueService::retryFailedLog($logId);
        if ($result['success']) {
            redirect('/notifications/admin', $result['message'], 'success');
        } else {
            redirect('/notifications/admin', $result['message'], 'danger');
        }
    }

    /**
     * Update Notification Template.
     */
    public function updateTemplate(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $id = (int)($_POST['id'] ?? 0);
        $subject = trim($_POST['subject'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $providerTemplateId = trim($_POST['provider_template_id'] ?? '');
        $variables = trim($_POST['variables'] ?? '');

        if ($id <= 0 || empty($content)) {
            redirect('/notifications/admin?tab=templates', 'Template content is required.', 'danger');
        }

        $stmt = $pdo->prepare("UPDATE notification_templates 
            SET subject = ?, content = ?, provider_template_id = ?, variables = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?");
        $stmt->execute([$subject ?: null, $content, $providerTemplateId ?: null, $variables ?: null, $id]);

        AuditService::log('UPDATE_NOTIFICATION_TEMPLATE', 'Settings', $id, "Updated notification template #{$id}");

        redirect('/notifications/admin?tab=templates', 'Notification template updated successfully.', 'success');
    }

    /**
     * Real-time Server-Sent Events (SSE) Stream for Live Notification Bell Sync.
     */
    public function stream(): void
    {
        // Set SSE Headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable FastCGI buffering

        // Verify session / authentication
        $isStaff = is_authenticated();
        $isCustomer = is_customer_authenticated();

        if (!$isStaff && !$isCustomer) {
            echo "event: error\ndata: " . json_encode(['error' => 'Unauthenticated']) . "\n\n";
            exit;
        }

        $pdo = Database::getConnection();
        $user = auth_user();
        $customer = auth_customer();

        if ($isStaff) {
            $userId = (int)($user['id'] ?? 0);
            $stmtCount = $pdo->query("SELECT COUNT(*) FROM notifications WHERE (recipient_type = 'Staff' OR user_id = {$userId}) AND is_read = 0");
            $unreadCount = (int)$stmtCount->fetchColumn();

            $stmtLatest = $pdo->query("SELECT * FROM notifications WHERE (recipient_type = 'Staff' OR user_id = {$userId}) ORDER BY created_at DESC LIMIT 5");
            $recent = $stmtLatest->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } else {
            $custId = (int)($customer['id'] ?? 0);
            $stmtCount = $pdo->query("SELECT COUNT(*) FROM notifications WHERE (customer_id = {$custId} OR recipient_type = 'Customer') AND is_read = 0");
            $unreadCount = (int)$stmtCount->fetchColumn();

            $stmtLatest = $pdo->query("SELECT * FROM notifications WHERE (customer_id = {$custId} OR recipient_type = 'Customer') ORDER BY created_at DESC LIMIT 5");
            $recent = $stmtLatest->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $payload = [
            'unread_count' => $unreadCount,
            'recent' => $recent,
            'timestamp' => time(),
        ];

        echo "event: ping\ndata: " . json_encode($payload) . "\n\n";
        flush();
        exit;
    }

    /**
     * Background Queue Processor API (can be invoked via cron or worker heartbeat).
     */
    public function processQueueApi(): void
    {
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 25)));
        $result = NotificationQueueService::processQueue($limit);
        json_response([
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'report' => $result
        ]);
    }
}
