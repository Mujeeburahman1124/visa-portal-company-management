<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Config\App;
use App\Config\Database;
use App\Config\Env;
use App\Database\DatabaseBootstrapper;
use App\Services\EmailService;
use App\Services\WhatsAppService;
use App\Services\NotificationService;
use App\Services\NotificationQueueService;
use PDO;

class NotificationSystemTest
{
    private PDO $pdo;
    private int $passed = 0;
    private int $failed = 0;

    public function __construct()
    {
        DatabaseBootstrapper::init();
        $this->pdo = Database::getConnection();
    }

    public function run(): void
    {
        echo "======================================================================\n";
        echo "VISA PORTAL: REAL-TIME NOTIFICATION SYSTEM COMPREHENSIVE TEST SUITE\n";
        echo "======================================================================\n";

        $this->testEnvironmentAndConfig();
        $this->testDatabaseSchemaAndSeeds();
        $this->testPhoneNumberValidationAndNormalization();
        $this->testEmailService();
        $this->testWhatsAppService();
        $this->testNotificationQueueAndRetry();
        $this->testCentralNotificationServiceEvents();
        $this->testIdempotencyAndDuplicateSuppression();
        $this->testChannelFaultIsolation();
        $this->testSettingsMatrixAndPreferences();

        echo "\n======================================================================\n";
        echo "NOTIFICATION SYSTEM TEST SUMMARY\n";
        echo "TOTAL PASSED: {$this->passed}\n";
        echo "TOTAL FAILED: {$this->failed}\n";
        echo "STATUS: " . ($this->failed === 0 ? "ALL NOTIFICATION CRITERIA VERIFIED SUCCESSFUL" : "FAILURES DETECTED") . "\n";
        echo "======================================================================\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function assert(bool $condition, string $description): void
    {
        if ($condition) {
            $this->passed++;
            echo " [PASS] {$description}\n";
        } else {
            $this->failed++;
            echo " [FAIL] {$description}\n";
        }
    }

    private function testEnvironmentAndConfig(): void
    {
        echo "\n1. ENVIRONMENT & CONFIGURATION AUDIT\n";
        $envMode = Env::get('NOTIFICATION_ENV', 'development');
        $this->assert(in_array($envMode, ['development', 'production', 'testing'], true), "NOTIFICATION_ENV loaded correctly: {$envMode}");

        $emailProvider = Env::get('EMAIL_PROVIDER', 'smtp');
        $this->assert(!empty($emailProvider), "EMAIL_PROVIDER configured: {$emailProvider}");

        $waProvider = Env::get('WHATSAPP_PROVIDER', 'meta');
        $this->assert(!empty($waProvider), "WHATSAPP_PROVIDER configured: {$waProvider}");
    }

    private function testDatabaseSchemaAndSeeds(): void
    {
        echo "\n2. DATABASE SCHEMA & SEEDS AUDIT\n";
        
        $settingsCount = (int)$this->pdo->query("SELECT COUNT(*) FROM notification_settings")->fetchColumn();
        $this->assert($settingsCount >= 25, "notification_settings table populated with standard event matrix (Count: {$settingsCount})");

        $templatesCount = (int)$this->pdo->query("SELECT COUNT(*) FROM notification_templates")->fetchColumn();
        $this->assert($templatesCount >= 10, "notification_templates table seeded with Email & WhatsApp templates (Count: {$templatesCount})");

        $logsExists = $this->pdo->query("SELECT COUNT(*) FROM notification_logs")->fetchColumn() !== false;
        $this->assert($logsExists, "notification_logs table active and queryable");

        $queueExists = $this->pdo->query("SELECT COUNT(*) FROM notification_queue")->fetchColumn() !== false;
        $this->assert($queueExists, "notification_queue table active for async processing");
    }

    private function testPhoneNumberValidationAndNormalization(): void
    {
        echo "\n3. PHONE NUMBER NORMALIZATION AUDIT (SRI LANKA & INTERNATIONAL)\n";

        // Sri Lankan 10-digit national (077XXXXXXX)
        $res1 = WhatsAppService::normalizePhoneNumber('0771234567');
        $this->assert($res1['valid'] && $res1['normalized'] === '94771234567', "Normalizes Sri Lankan 0771234567 -> 94771234567");

        // Sri Lankan with leading zero and spaces
        $res2 = WhatsAppService::normalizePhoneNumber('071 987 6543');
        $this->assert($res2['valid'] && $res2['normalized'] === '94719876543', "Normalizes spaced Sri Lankan 071 987 6543 -> 94719876543");

        // Sri Lankan 9-digit without leading 0 (771234567)
        $res3 = WhatsAppService::normalizePhoneNumber('771234567');
        $this->assert($res3['valid'] && $res3['normalized'] === '94771234567', "Normalizes 9-digit 771234567 -> 94771234567");

        // International with + (UAE: +971501234567)
        $res4 = WhatsAppService::normalizePhoneNumber('+971 50 123 4567');
        $this->assert($res4['valid'] && $res4['normalized'] === '971501234567', "Normalizes UAE international +971 50 123 4567 -> 971501234567");

        // Invalid number
        $res5 = WhatsAppService::normalizePhoneNumber('1234');
        $this->assert(!$res5['valid'], "Correctly rejects short invalid number '1234'");
    }

    private function testEmailService(): void
    {
        echo "\n4. EMAIL SERVICE & TEMPLATE ENGINE AUDIT\n";

        $result = EmailService::send([
            'to' => 'test-applicant@example.com',
            'name' => 'John Doe',
            'subject' => 'Visa Grant for {{applicantName}} ({{applicationNumber}})',
            'bodyHtml' => '<p>Dear {{applicantName}},</p><p>Your visa for <strong>{{countryName}}</strong> is approved!</p>',
            'data' => [
                'applicantName' => 'John Doe',
                'applicationNumber' => 'VISA-2026-00042',
                'countryName' => 'United Kingdom',
            ]
        ]);

        $this->assert($result['success'] === true, "EmailService dispatches email successfully in simulation/SMTP mode");
        $this->assert(!empty($result['message_id']), "EmailService returns valid Message-ID: " . ($result['message_id'] ?? 'none'));

        // Invalid email rejection
        $invalidResult = EmailService::send([
            'to' => 'invalid-email-address',
            'subject' => 'Test',
            'bodyHtml' => 'Test'
        ]);
        $this->assert($invalidResult['success'] === false, "EmailService strictly validates recipient email format");
    }

    private function testWhatsAppService(): void
    {
        echo "\n5. META WHATSAPP BUSINESS API AUDIT\n";

        $result = WhatsAppService::send([
            'phoneNumber' => '0771234567',
            'templateName' => 'visa_status_update',
            'variables' => ['John Doe', 'VISA-2026-00042', 'In Embassy Review', 'MS Travel Hub'],
            'data' => [
                'applicantName' => 'John Doe',
                'applicationNumber' => 'VISA-2026-00042',
            ]
        ]);

        $this->assert($result['success'] === true, "WhatsAppService formats template payload and dispatches successfully");
        $this->assert(str_starts_with((string)$result['message_id'], 'wamid.'), "WhatsAppService tracks provider wamid message ID: " . ($result['message_id'] ?? 'none'));
        $this->assert($result['normalized_phone'] === '+94771234567', "WhatsAppService passes normalized international E.164 phone");
    }

    private function testNotificationQueueAndRetry(): void
    {
        echo "\n6. NOTIFICATION QUEUE & EXPONENTIAL BACKOFF RETRY AUDIT\n";

        $jobId = NotificationQueueService::enqueue([
            'event_type' => 'visa.test_queue',
            'recipient_type' => 'Applicant',
            'recipient_name' => 'Queue Test User',
            'recipient_email' => 'queue-test@example.com',
            'channel' => 'Email',
            'template_name' => 'test_tmpl',
            'idempotency_key' => 'queue_test_' . uniqid(),
            'payload' => [
                'to' => 'queue-test@example.com',
                'name' => 'Queue Test User',
                'subject' => 'Queue Test Subject',
                'bodyHtml' => '<p>Queue payload test</p>',
            ]
        ]);

        $this->assert($jobId > 0, "NotificationQueueService enqueues job with ID #{$jobId}");

        $processResult = NotificationQueueService::processQueue(10);
        $this->assert($processResult['total_processed'] >= 1, "Queue worker processes pending job (Processed: {$processResult['total_processed']})");
        $this->assert($processResult['succeeded'] >= 1, "Queue worker records successful delivery");
    }

    private function testCentralNotificationServiceEvents(): void
    {
        echo "\n7. CENTRAL NOTIFICATION SERVICE EVENT TRIGGERS AUDIT\n";

        // Fetch or ensure a test customer and application exist
        $cust = $this->pdo->query("SELECT * FROM customers LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
        $app = $this->pdo->query("SELECT * FROM applications LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];

        $customerId = (int)($cust['id'] ?? 1);
        $applicationId = (int)($app['id'] ?? 1);
        $baseCtx = [
            'application_id' => $applicationId,
            'customer_id' => $customerId,
            'recipient_email' => $cust['email'] ?? 'test-applicant@example.com',
            'recipient_phone' => $cust['mobile'] ?? '0771234567',
            'applicantName' => $cust['full_name'] ?? 'Test Applicant',
            'applicationNumber' => $app['application_number'] ?? 'VISA-2026-0001',
        ];

        // 1. Visa Stage Changed Event
        $stageRes = NotificationService::trigger('visa.stage_changed', array_merge($baseCtx, [
            'current_stage' => 'Under Embassy Review',
            'status' => 'In Process',
            'next_action' => 'Consular verification in progress',
        ]));
        $this->assert($stageRes['email']['enabled'] && $stageRes['whatsapp']['enabled'] && $stageRes['in_app']['enabled'], "Central trigger for 'visa.stage_changed' broadcasts to Email, WhatsApp, and In-App");

        // 2. Visa Approved Event
        $approvedRes = NotificationService::trigger('visa.approved', array_merge($baseCtx, [
            'countryName' => 'Canada',
            'visaType' => 'Tourist Visa',
            'passportNumber' => 'N1234567',
        ]));
        $this->assert($approvedRes['email']['status'] === 'Sent' || $approvedRes['email']['status'] === 'Simulated', "Central trigger for 'visa.approved' delivers approval notification");

        // 3. Interview Scheduled Event
        $interviewRes = NotificationService::trigger('interview.scheduled', array_merge($baseCtx, [
            'appointmentType' => 'Biometrics & Consular Interview',
            'interviewDate' => '2026-09-15',
            'interviewTime' => '10:30 AM',
            'centerName' => 'VFS Global Colombo Center',
            'locationAddress' => 'World Trade Center, Colombo',
        ]));
        $this->assert($interviewRes['whatsapp']['status'] === 'Sent' || $interviewRes['whatsapp']['status'] === 'Simulated', "Central trigger for 'interview.scheduled' formats and sends WhatsApp appointment slip");

        // 4. Document Rejected Event
        $docRejRes = NotificationService::trigger('document.rejected', array_merge($baseCtx, [
            'documentName' => 'Bank Statement (Last 6 Months)',
            'rejectionReason' => 'Document scan is blurry and missing official bank stamp',
        ]));
        $this->assert($docRejRes['in_app']['status'] === 'Sent', "Central trigger for 'document.rejected' creates in-app action alert for customer");

        // 5. Payment Received Event
        $payRes = NotificationService::trigger('payment.received', array_merge($baseCtx, [
            'paymentNumber' => 'REC-2026-00088',
            'amount' => '1,250.00',
            'currency' => 'USD',
            'paymentMethod' => 'Bank Transfer',
            'paymentDate' => date('Y-m-d'),
        ]));
        $this->assert($payRes['email']['enabled'] && $payRes['whatsapp']['enabled'], "Central trigger for 'payment.received' dispatches payment receipt notification");
    }

    private function testIdempotencyAndDuplicateSuppression(): void
    {
        echo "\n8. IDEMPOTENCY & DUPLICATE SEND SUPPRESSION AUDIT\n";

        $testIdempKey = 'idemp_test_' . time();
        $payload = [
            'to' => 'idemp-user@example.com',
            'name' => 'Idempotent User',
            'subject' => 'Idempotency Test',
            'bodyHtml' => '<p>Test</p>',
            'recipient_email' => 'idemp-user@example.com',
            'recipient_phone' => '0771234567',
        ];

        // First trigger
        $res1 = NotificationService::trigger('system.announcement', $payload, $testIdempKey);
        $this->assert($res1['email']['enabled'] === true, "First trigger sends notification successfully with idempotency key");

        // Immediate second duplicate trigger with same idempotency key
        $res2 = NotificationService::trigger('system.announcement', $payload, $testIdempKey);
        $this->assert($res2['email']['status'] === 'Skipped' || $res2['email']['enabled'] === false, "Duplicate trigger with same key is automatically suppressed without double-sending");
    }

    private function testChannelFaultIsolation(): void
    {
        echo "\n9. INDEPENDENT CHANNEL FAULT ISOLATION AUDIT\n";

        // Provide an invalid email but valid phone
        $res = NotificationService::trigger('visa.stage_changed', [
            'recipient_email' => 'invalid-email',
            'recipient_phone' => '0771234567',
            'applicant_name' => 'Fault Isolation User',
            'application_number' => 'VISA-FAIL-01',
        ]);

        $this->assert($res['email']['success'] === false, "Email channel gracefully records failure for invalid email");
        $this->assert($res['whatsapp']['success'] === true, "WhatsApp channel succeeds independently without being blocked by email failure");
        $this->assert($res['in_app']['status'] === 'Sent', "In-App channel succeeds independently");
    }

    private function testSettingsMatrixAndPreferences(): void
    {
        echo "\n10. NOTIFICATION SETTINGS MATRIX & AUDIT LOG EXPLORER AUDIT\n";

        // Check if logs are recorded in DB
        $logCount = (int)$this->pdo->query("SELECT COUNT(*) FROM notification_logs")->fetchColumn();
        $this->assert($logCount >= 5, "notification_logs ledger populated with immutable audit records (Found: {$logCount})");

        // Verify log channels recorded
        $channels = $this->pdo->query("SELECT DISTINCT channel FROM notification_logs")->fetchAll(PDO::FETCH_COLUMN);
        $this->assert(in_array('Email', $channels, true) && in_array('WhatsApp', $channels, true), "Logs contain both Email and WhatsApp delivery entries");

        // Verify status fields
        $statuses = $this->pdo->query("SELECT DISTINCT status FROM notification_logs")->fetchAll(PDO::FETCH_COLUMN);
        $this->assert(count($statuses) >= 1, "Logs contain structured statuses: " . implode(', ', $statuses));
    }
}

$tester = new NotificationSystemTest();
$tester->run();
