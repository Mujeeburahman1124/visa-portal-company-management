<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\App;
use App\Config\Database;
use App\Config\Env;
use PDO;
use Exception;

class NotificationService
{
    /**
     * Central Dispatch Gateway: Evaluates event settings, resolves recipients,
     * performs de-duplication, and broadcasts to Email, WhatsApp, and In-App channels.
     *
     * @param string $eventType (e.g., 'visa.stage_changed', 'interview.scheduled')
     * @param array $context (contextual data including customer_id, application_id, etc.)
     * @param ?string $idempotencyKey (optional unique token to prevent duplicate sends)
     * @return array [
     *   'event_type' => string,
     *   'email' => array,
     *   'whatsapp' => array,
     *   'in_app' => array,
     *   'success' => bool
     * ]
     */
    public static function trigger(string $eventType, array $context = [], ?string $idempotencyKey = null): array
    {
        $pdo = Database::getConnection();

        // 1. Fetch Event-Level Channel Settings
        $stmtSetting = $pdo->prepare("SELECT * FROM notification_settings WHERE event_type = ? LIMIT 1");
        $stmtSetting->execute([$eventType]);
        $setting = $stmtSetting->fetch(PDO::FETCH_ASSOC);

        // If not found in settings, use permissive defaults
        if (!$setting) {
            $setting = [
                'email_enabled' => 1,
                'whatsapp_enabled' => 1,
                'in_app_enabled' => 1,
                'applicant_enabled' => 1,
                'staff_enabled' => 1,
            ];
        }

        // 2. Resolve Applicant / Customer Info
        $customer = self::resolveCustomer($context, $pdo);
        // 3. Resolve Staff Info
        $staff = self::resolveStaff($context, $pdo);
        // 4. Resolve Application Info if available
        $app = self::resolveApplication($context, $pdo);

        // 5. Merge Variables for Template Interpolation
        $data = self::buildTemplateData($context, $customer, $staff, $app);

        // Generate automatic idempotency key if not provided
        if (empty($idempotencyKey)) {
            $idempotencyKey = 'evt_' . substr(md5($eventType . '_' . ($customer['id'] ?? 0) . '_' . ($app['id'] ?? 0) . '_' . date('YmdHi')), 0, 24);
        }

        // 6. Check Duplicate Prevention (Idempotency)
        $stmtDup = $pdo->prepare("SELECT id, status, channel FROM notification_logs WHERE (idempotency_key = ? OR idempotency_key LIKE ?) AND status IN ('Sent', 'Simulated', 'Queued')");
        $stmtDup->execute([$idempotencyKey, $idempotencyKey . '_%']);
        $existingSent = $stmtDup->fetchAll(PDO::FETCH_ASSOC);
        $alreadySentChannels = array_column($existingSent, 'channel');

        $processMode = strtolower((string)Env::get('NOTIFICATION_PROCESS_MODE', 'sync'));

        $results = [
            'event_type' => $eventType,
            'email' => ['enabled' => false, 'status' => 'Skipped'],
            'whatsapp' => ['enabled' => false, 'status' => 'Skipped'],
            'in_app' => ['enabled' => false, 'status' => 'Skipped'],
            'success' => true,
        ];

        // =========================================================================
        // CHANNEL 1: EMAIL DISPATCH
        // =========================================================================
        if (!empty($setting['email_enabled'])) {
            if (in_array('Email', $alreadySentChannels, true)) {
                $results['email'] = ['enabled' => true, 'status' => 'Skipped', 'reason' => 'Duplicate idempotency key'];
            } else {
                $recipientEmail = $customer['email'] ?? $context['recipient_email'] ?? $staff['email'] ?? null;
                $recipientName = $customer['full_name'] ?? $customer['name'] ?? $staff['name'] ?? 'Valued Customer';

                if (!empty($recipientEmail)) {
                    // Fetch Email Template
                    $tmpl = self::fetchTemplate($eventType, 'Email', $pdo);
                    $subject = $tmpl['subject'] ?? "Notification from " . App::COMPANY_NAME;
                    $bodyHtml = $tmpl['content'] ?? "<p>Hello {{applicantName}},</p><p>You have a new update regarding your visa file.</p>";

                    $emailPayload = [
                        'to' => $recipientEmail,
                        'name' => $recipientName,
                        'subject' => $subject,
                        'bodyHtml' => $bodyHtml,
                        'data' => $data,
                    ];

                    if ($processMode === 'async') {
                        NotificationQueueService::enqueue([
                            'event_type' => $eventType,
                            'recipient_type' => !empty($customer['id']) ? 'Applicant' : 'Staff',
                            'recipient_id' => $customer['id'] ?? $staff['id'] ?? null,
                            'recipient_name' => $recipientName,
                            'recipient_email' => $recipientEmail,
                            'channel' => 'Email',
                            'template_name' => $tmpl['template_name'] ?? 'default_email',
                            'idempotency_key' => $idempotencyKey . '_email',
                            'payload' => $emailPayload,
                        ]);
                        $results['email'] = ['enabled' => true, 'status' => 'Queued', 'success' => true];
                    } else {
                        $emailRes = EmailService::send($emailPayload);
                        $status = $emailRes['success'] ? 'Sent' : 'Failed';
                        self::recordLog([
                            'event_type' => $eventType,
                            'recipient_type' => !empty($customer['id']) ? 'Applicant' : 'Staff',
                            'recipient_id' => $customer['id'] ?? $staff['id'] ?? null,
                            'recipient_name' => $recipientName,
                            'recipient_email' => $recipientEmail,
                            'recipient_phone' => null,
                            'channel' => 'Email',
                            'template_name' => $tmpl['template_name'] ?? 'email_template',
                            'subject' => EmailService::interpolate($subject, $data),
                            'content_preview' => mb_strimwidth(strip_tags(EmailService::interpolate($bodyHtml, $data)), 0, 150, '...'),
                            'idempotency_key' => $idempotencyKey . '_email',
                            'status' => $status,
                            'provider_message_id' => $emailRes['message_id'] ?? null,
                            'request_payload' => json_encode($emailPayload),
                            'response_payload' => null,
                            'error_message' => $emailRes['error'] ?? null,
                            'sent_at' => $emailRes['success'] ? date('Y-m-d H:i:s') : null,
                        ], $pdo);
                        $results['email'] = array_merge(['enabled' => true, 'status' => $status], $emailRes);
                    }
                }
            }
        }

        // =========================================================================
        // CHANNEL 2: WHATSAPP DISPATCH (Meta Cloud API)
        // =========================================================================
        if (!empty($setting['whatsapp_enabled'])) {
            if (in_array('WhatsApp', $alreadySentChannels, true)) {
                $results['whatsapp'] = ['enabled' => true, 'status' => 'Skipped', 'reason' => 'Duplicate idempotency key'];
            } else {
                $rawPhone = $customer['whatsapp'] ?? $customer['mobile'] ?? $context['recipient_phone'] ?? null;
                $recipientName = $customer['full_name'] ?? $customer['name'] ?? 'Customer';

                if (!empty($rawPhone)) {
                    $tmpl = self::fetchTemplate($eventType, 'WhatsApp', $pdo);
                    $providerTmplName = $tmpl['provider_template_id'] ?? null;
                    $messageText = $tmpl['content'] ?? "Hello {{applicantName}},\n\nYou have an update regarding your visa application: {{applicationNumber}}.\n\n{{companyName}}";

                    // Build template variables if provider template is configured
                    $variables = self::buildWhatsAppVariables($tmpl, $data);

                    $waPayload = [
                        'phoneNumber' => $rawPhone,
                        'templateName' => $providerTmplName,
                        'languageCode' => $tmpl['language_code'] ?? 'en_US',
                        'variables' => $variables,
                        'messageText' => $messageText,
                        'data' => $data,
                    ];

                    if ($processMode === 'async') {
                        NotificationQueueService::enqueue([
                            'event_type' => $eventType,
                            'recipient_type' => !empty($customer['id']) ? 'Applicant' : 'Staff',
                            'recipient_id' => $customer['id'] ?? null,
                            'recipient_name' => $recipientName,
                            'recipient_phone' => $rawPhone,
                            'channel' => 'WhatsApp',
                            'template_name' => $tmpl['template_name'] ?? 'default_wa',
                            'idempotency_key' => $idempotencyKey . '_whatsapp',
                            'payload' => $waPayload,
                        ]);
                        $results['whatsapp'] = ['enabled' => true, 'status' => 'Queued', 'success' => true];
                    } else {
                        $waRes = WhatsAppService::send($waPayload);
                        $status = $waRes['success'] ? 'Sent' : 'Failed';
                        self::recordLog([
                            'event_type' => $eventType,
                            'recipient_type' => !empty($customer['id']) ? 'Applicant' : 'Staff',
                            'recipient_id' => $customer['id'] ?? null,
                            'recipient_name' => $recipientName,
                            'recipient_email' => null,
                            'recipient_phone' => $waRes['normalized_phone'] ?? $rawPhone,
                            'channel' => 'WhatsApp',
                            'template_name' => $tmpl['template_name'] ?? 'whatsapp_template',
                            'subject' => "WhatsApp: " . ($tmpl['template_name'] ?? $eventType),
                            'content_preview' => mb_strimwidth(EmailService::interpolate($messageText, $data), 0, 150, '...'),
                            'idempotency_key' => $idempotencyKey . '_whatsapp',
                            'status' => $status,
                            'provider_message_id' => $waRes['message_id'] ?? null,
                            'request_payload' => json_encode($waPayload),
                            'response_payload' => !empty($waRes['response']) ? json_encode($waRes['response']) : null,
                            'error_message' => $waRes['error'] ?? null,
                            'sent_at' => $waRes['success'] ? date('Y-m-d H:i:s') : null,
                        ], $pdo);
                        $results['whatsapp'] = array_merge(['enabled' => true, 'status' => $status], $waRes);
                    }
                }
            }
        }

        // =========================================================================
        // CHANNEL 3: REAL-TIME IN-APP NOTIFICATION
        // =========================================================================
        if (!empty($setting['in_app_enabled'])) {
            if (in_array('In-App', $alreadySentChannels, true)) {
                $results['in_app'] = ['enabled' => true, 'status' => 'Skipped', 'reason' => 'Duplicate idempotency key'];
            } else {
                $inAppTitle = $context['title'] ?? self::formatEventTitle($eventType, $data);
                $inAppMessage = $context['message'] ?? self::formatEventMessage($eventType, $data);
                $inAppLink = $context['link'] ?? $data['actionUrl'] ?? '/dashboard';
                $severity = $context['severity'] ?? (str_contains($eventType, 'rejected') ? 'danger' : (str_contains($eventType, 'approved') ? 'success' : 'info'));

                // Deliver to Applicant In-App Inbox
                if (!empty($setting['applicant_enabled']) && !empty($customer['id'])) {
                    self::createInAppNotification([
                        'customer_id' => $customer['id'],
                        'user_id' => null,
                        'recipient_type' => 'Customer',
                        'title' => $inAppTitle,
                        'message' => $inAppMessage,
                        'link' => $context['portal_link'] ?? '/portal/notifications',
                        'notification_type' => $eventType,
                        'severity' => $severity,
                    ], $pdo);
                }

                // Deliver to Staff In-App Inbox
                if (!empty($setting['staff_enabled']) && (!empty($staff['id']) || !empty($context['broadcast_staff']))) {
                    self::createInAppNotification([
                        'customer_id' => null,
                        'user_id' => $staff['id'] ?? null,
                        'recipient_type' => 'Staff',
                        'title' => $inAppTitle,
                        'message' => $inAppMessage,
                        'link' => $inAppLink,
                        'notification_type' => $eventType,
                        'severity' => $severity,
                    ], $pdo);
                }

                $results['in_app'] = ['enabled' => true, 'status' => 'Sent', 'success' => true];
            }
        }

        return $results;
    }

    /**
     * Create an in-app notification record in the `notifications` table.
     */
    public static function createInAppNotification(array $payload, ?PDO $pdo = null): array
    {
        $pdo = $pdo ?: Database::getConnection();

        try {
            $stmt = $pdo->prepare("INSERT INTO notifications 
                (user_id, customer_id, recipient_type, title, message, link, notification_type, severity, is_read, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP)");

            $stmt->execute([
                $payload['user_id'] ?? null,
                $payload['customer_id'] ?? null,
                $payload['recipient_type'] ?? 'Staff',
                $payload['title'] ?? 'System Notification',
                $payload['message'] ?? '',
                $payload['link'] ?? '/dashboard',
                $payload['notification_type'] ?? 'System',
                $payload['severity'] ?? 'info',
            ]);

            return [
                'success' => true,
                'notification_id' => (int)$pdo->lastInsertId(),
                'provider' => 'in_app',
                'error' => null,
                'simulated' => false,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'notification_id' => null,
                'provider' => 'in_app',
                'error' => $e->getMessage(),
                'simulated' => false,
            ];
        }
    }

    /**
     * Record delivery log entry.
     */
    private static function recordLog(array $log, PDO $pdo): void
    {
        $stmt = $pdo->prepare("INSERT INTO notification_logs 
            (event_type, recipient_type, recipient_id, recipient_name, recipient_email, recipient_phone, channel, template_name, subject, content_preview, idempotency_key, status, provider_message_id, request_payload, response_payload, error_message, sent_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");

        $stmt->execute([
            $log['event_type'],
            $log['recipient_type'] ?? 'Applicant',
            $log['recipient_id'] ?? null,
            $log['recipient_name'] ?? null,
            $log['recipient_email'] ?? null,
            $log['recipient_phone'] ?? null,
            $log['channel'],
            $log['template_name'] ?? null,
            $log['subject'] ?? null,
            $log['content_preview'] ?? null,
            $log['idempotency_key'] ?? null,
            $log['status'] ?? 'Pending',
            $log['provider_message_id'] ?? null,
            $log['request_payload'] ?? null,
            $log['response_payload'] ?? null,
            $log['error_message'] ?? null,
            $log['sent_at'] ?? null,
        ]);
    }

    /**
     * Retrieve matching template for event & channel.
     */
    private static function fetchTemplate(string $eventType, string $channel, PDO $pdo): array
    {
        $stmt = $pdo->prepare("SELECT * FROM notification_templates WHERE event_type = ? AND channel = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$eventType, $channel]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $row;
        }

        // Fallback to legacy email_templates if email channel
        if ($channel === 'Email') {
            $legacyKey = strtoupper(str_replace('.', '_', $eventType));
            $stmtLeg = $pdo->prepare("SELECT * FROM email_templates WHERE template_key = ? AND is_active = 1 LIMIT 1");
            $stmtLeg->execute([$legacyKey]);
            $leg = $stmtLeg->fetch(PDO::FETCH_ASSOC);
            if ($leg) {
                return [
                    'template_name' => $leg['template_key'],
                    'subject' => $leg['subject'],
                    'content' => $leg['body_html'],
                    'variables' => $leg['placeholders'],
                ];
            }
        }

        return [
            'template_name' => "default_{$channel}",
            'subject' => "Notification: " . ucwords(str_replace(['.', '_'], ' ', $eventType)),
            'content' => "<p>Dear {{applicantName}},</p><p>You have a new update regarding your visa application <strong>{{applicationNumber}}</strong>.</p>",
            'provider_template_id' => null,
            'language_code' => 'en_US',
        ];
    }

    /**
     * Resolve Customer / Applicant record.
     */
    private static function resolveCustomer(array $context, PDO $pdo): ?array
    {
        if (!empty($context['customer']) && is_array($context['customer'])) {
            return $context['customer'];
        }

        $customerId = $context['customer_id'] ?? null;

        if (!$customerId && !empty($context['application_id'])) {
            $stmt = $pdo->prepare("SELECT customer_id FROM applications WHERE id = ?");
            $stmt->execute([(int)$context['application_id']]);
            $customerId = $stmt->fetchColumn();
        }

        if ($customerId) {
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? LIMIT 1");
            $stmt->execute([(int)$customerId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        return null;
    }

    /**
     * Resolve Staff / Assigned Officer record.
     */
    private static function resolveStaff(array $context, PDO $pdo): ?array
    {
        if (!empty($context['staff']) && is_array($context['staff'])) {
            return $context['staff'];
        }

        $userId = $context['user_id'] ?? $context['assigned_staff_id'] ?? null;

        if (!$userId && !empty($context['application_id'])) {
            $stmt = $pdo->prepare("SELECT assigned_staff_id FROM applications WHERE id = ?");
            $stmt->execute([(int)$context['application_id']]);
            $userId = $stmt->fetchColumn();
        }

        if ($userId) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([(int)$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        return null;
    }

    /**
     * Resolve Application record.
     */
    private static function resolveApplication(array $context, PDO $pdo): ?array
    {
        if (!empty($context['application']) && is_array($context['application'])) {
            return $context['application'];
        }

        $appId = $context['application_id'] ?? null;

        if ($appId) {
            $stmt = $pdo->prepare("SELECT a.*, c.name as country_name, vc.name as category_name 
                FROM applications a 
                LEFT JOIN visa_services vs ON a.visa_service_id = vs.id 
                LEFT JOIN countries c ON vs.country_id = c.id 
                LEFT JOIN visa_categories vc ON vs.category_id = vc.id 
                WHERE a.id = ? LIMIT 1");
            $stmt->execute([(int)$appId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        return null;
    }

    /**
     * Build unified template variables map.
     */
    private static function buildTemplateData(array $context, ?array $customer, ?array $staff, ?array $app): array
    {
        $appUrl = (string)Env::get('APP_URL', 'http://localhost:8000');
        $companyName = (string)Env::get('COMPANY_NAME', App::COMPANY_NAME);

        $data = [
            'companyName' => $companyName,
            'companyEmail' => (string)Env::get('COMPANY_EMAIL', 'notifications@mstravelhub.com'),
            'companyPhone' => (string)Env::get('COMPANY_PHONE', '+94 11 234 5678'),
            'companyWebsite' => (string)Env::get('COMPANY_WEBSITE', 'https://visatrack.mstravelhub.com'),
            'appUrl' => $appUrl,
            'loginUrl' => $appUrl . '/portal/login',
            'supportEmail' => 'support@mstravelhub.com',
        ];

        // Customer variables
        if ($customer) {
            $data['applicantName'] = $customer['full_name'] ?? ($customer['first_name'] . ' ' . $customer['last_name']);
            $data['customerCode'] = $customer['customer_code'] ?? '';
            $data['applicantEmail'] = $customer['email'] ?? '';
            $data['applicantPhone'] = $customer['whatsapp'] ?: $customer['mobile'] ?: '';
            $data['nationality'] = $customer['nationality'] ?? '';
        } else {
            $data['applicantName'] = $context['applicant_name'] ?? $context['recipient_name'] ?? 'Valued Customer';
        }

        // Staff variables
        if ($staff) {
            $data['userName'] = $staff['name'] ?? '';
            $data['staffName'] = $staff['name'] ?? '';
            $data['staffEmail'] = $staff['email'] ?? '';
        }

        // Application variables
        if ($app) {
            $data['applicationNumber'] = $app['application_number'] ?? '';
            $data['countryName'] = $app['country_name'] ?? $app['destination_country'] ?? 'Destination Country';
            $data['visaType'] = $app['category_name'] ?? $app['visa_type'] ?? 'Visa';
            $data['currentStage'] = $app['current_stage'] ?? 'Processing';
            $data['status'] = $app['status'] ?? 'Active';
            $data['passportNumber'] = $app['passport_number'] ?? '';
            $data['actionUrl'] = $appUrl . "/portal/dashboard";
        }

        // Context overrides
        foreach ($context as $k => $v) {
            if (is_scalar($v)) {
                $data[$k] = (string)$v;
                // Also camelCase version
                $camel = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $k))));
                $data[$camel] = (string)$v;
            }
        }

        return $data;
    }

    /**
     * Build WhatsApp positional parameters.
     */
    private static function buildWhatsAppVariables(array $template, array $data): array
    {
        $rawVars = $template['variables'] ?? '';
        if (empty($rawVars)) {
            return array_values(array_filter([
                $data['applicantName'] ?? null,
                $data['applicationNumber'] ?? null,
                $data['currentStage'] ?? $data['status'] ?? null,
                $data['companyName'] ?? null,
            ]));
        }

        $varKeys = array_map('trim', explode(',', $rawVars));
        $ordered = [];
        foreach ($varKeys as $k) {
            $ordered[] = $data[$k] ?? ($data[lcfirst($k)] ?? '');
        }

        return $ordered;
    }

    private static function formatEventTitle(string $eventType, array $data): string
    {
        return match ($eventType) {
            'applicant.registered' => 'New Applicant Registered: ' . ($data['applicantName'] ?? ''),
            'interview.scheduled' => 'Appointment Scheduled: ' . ($data['applicationNumber'] ?? ''),
            'interview.rescheduled' => 'Appointment Rescheduled: ' . ($data['applicationNumber'] ?? ''),
            'visa.stage_changed' => 'Visa Stage Shift: ' . ($data['applicationNumber'] ?? ''),
            'visa.approved' => 'Visa Approved: ' . ($data['applicationNumber'] ?? ''),
            'visa.rejected' => 'Visa Refused: ' . ($data['applicationNumber'] ?? ''),
            'document.rejected' => 'Document Rejected: ' . ($data['documentName'] ?? 'File'),
            'payment.received' => 'Payment Received: ' . ($data['amount'] ?? '') . ' ' . ($data['currency'] ?? 'USD'),
            default => 'Visa Portal Update: ' . ucwords(str_replace(['.', '_'], ' ', $eventType)),
        };
    }

    private static function formatEventMessage(string $eventType, array $data): string
    {
        $applicantName = $data['applicantName'] ?? 'Applicant';
        $appNum = $data['applicationNumber'] ?? 'Visa';
        $interviewDate = $data['interviewDate'] ?? 'Scheduled Date';
        $interviewTime = $data['interviewTime'] ?? 'Scheduled Time';
        $centerName = $data['centerName'] ?? 'Appointment Center';
        $currentStage = $data['currentStage'] ?? 'In Progress';
        $status = $data['status'] ?? 'Active';
        $countryName = $data['countryName'] ?? 'Destination Country';
        $docName = $data['documentName'] ?? 'Document';
        $rejReason = $data['rejectionReason'] ?? 'Please review requirements';
        $amount = $data['amount'] ?? '0.00';
        $currency = $data['currency'] ?? 'USD';

        return match ($eventType) {
            'applicant.registered' => "Applicant profile {$applicantName} was registered successfully.",
            'interview.scheduled' => "Appointment confirmed on {$interviewDate} at {$interviewTime} ({$centerName}).",
            'visa.stage_changed' => "Application {$appNum} advanced to {$currentStage} ({$status}).",
            'visa.approved' => "Visa grant issued for {$applicantName} to {$countryName}.",
            'visa.rejected' => "Consular refusal recorded for application {$appNum}.",
            'document.rejected' => "Replacement required for {$docName}: {$rejReason}.",
            'payment.received' => "Payment of {$amount} {$currency} collected for {$appNum}.",
            default => "System update processed for " . ($data['applicationNumber'] ?? $data['applicantName'] ?? 'your account') . ".",
        };
    }
}
