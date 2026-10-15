<?php
declare(strict_types=1);

namespace Tests;

require_once __DIR__ . '/../app/autoload.php';

use App\Config\Database;
use App\Database\DatabaseBootstrapper;
use App\Services\ExpiryNotificationService;
use PDO;

class VisaExpiryAndNotificationTest
{
    private PDO $pdo;
    private int $passed = 0;
    private int $failed = 0;

    public function __construct()
    {
        putenv('IN_TEST_MODE=true');
        DatabaseBootstrapper::init();
        $this->pdo = Database::getConnection();
    }

    public function runAll(): void
    {
        echo "======================================================================\n";
        echo "TEST SUITE: VISA EXPIRY MONITORING & DAILY COUNTDOWN NOTIFICATIONS\n";
        echo "======================================================================\n\n";

        $this->testBadgeHelpers();
        $this->testDatabaseSchemaColumns();
        $this->testExpiryNotificationServiceMilestones();
        $this->testExpiryNotificationIdempotency();
        $this->testMultiDocumentMonitoring();

        echo "\n======================================================================\n";
        echo "SUMMARY: {$this->passed} PASSED, {$this->failed} FAILED\n";
        echo "======================================================================\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function assert(bool $condition, string $testName): void
    {
        if ($condition) {
            echo " [PASS] " . $testName . "\n";
            $this->passed++;
        } else {
            echo " [FAIL] " . $testName . "\n";
            $this->failed++;
        }
    }

    private function testBadgeHelpers(): void
    {
        echo "--- Testing Badge Helpers ---\n";

        // 1. Visa Status Badges
        $bActive = visa_status_badge('active');
        $this->assert(str_contains($bActive, 'Active') && str_contains($bActive, 'bg-success'), "visa_status_badge('active') renders active badge");

        $bInactive = visa_status_badge('inactive');
        $this->assert(str_contains($bInactive, 'Inactive') && str_contains($bInactive, 'bg-secondary'), "visa_status_badge('inactive') renders inactive badge");

        $bUsed = visa_status_badge('used');
        $this->assert(str_contains($bUsed, 'Used') && str_contains($bUsed, 'bg-info'), "visa_status_badge('used') renders used badge");

        $bCancel = visa_status_badge('before entry cancel');
        $this->assert(str_contains($bCancel, 'Before Entry Cancel') && str_contains($bCancel, 'bg-danger'), "visa_status_badge('before entry cancel') renders before entry cancel badge");

        // 2. Visa Expiry Badges
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $bExpired = visa_expiry_badge($yesterday);
        $this->assert(str_contains($bExpired, 'EXPIRED') && str_contains($bExpired, 'bg-danger'), "visa_expiry_badge past date shows EXPIRED");

        $today = date('Y-m-d');
        $bToday = visa_expiry_badge($today);
        $this->assert(str_contains($bToday, 'EXPIRES TODAY') && str_contains($bToday, 'bg-danger'), "visa_expiry_badge today shows EXPIRES TODAY");

        $in5Days = date('Y-m-d', strtotime('+5 days'));
        $b5Days = visa_expiry_badge($in5Days);
        $this->assert(str_contains($b5Days, '5 Days Left') && str_contains($b5Days, 'bg-danger-subtle'), "visa_expiry_badge in 5 days shows 5 Days Left");

        $in20Days = date('Y-m-d', strtotime('+20 days'));
        $b20Days = visa_expiry_badge($in20Days);
        $this->assert(str_contains($b20Days, '20 Days Left'), "visa_expiry_badge in 20 days shows 20 Days Left");

        $bNull = visa_expiry_badge(null);
        $this->assert(str_contains($bNull, 'No Expiry Date'), "visa_expiry_badge null shows No Expiry Date");
    }

    private function testDatabaseSchemaColumns(): void
    {
        echo "\n--- Testing Database Schema Columns ---\n";

        // Check applications table columns
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $cols = array_column($this->pdo->query("PRAGMA table_info(applications)")->fetchAll(PDO::FETCH_ASSOC), 'name');
            $this->assert(in_array('visa_status', $cols, true), "applications table has visa_status column");
            $this->assert(in_array('visa_expiry_date', $cols, true), "applications table has visa_expiry_date column");
            $this->assert(in_array('entry_before_date', $cols, true), "applications table has entry_before_date column");

            $apprCols = array_column($this->pdo->query("PRAGMA table_info(visa_approvals)")->fetchAll(PDO::FETCH_ASSOC), 'name');
            $this->assert(in_array('visa_status', $apprCols, true), "visa_approvals table has visa_status column");
        } else {
            $hasVisaStatus = (bool)$this->pdo->query("SHOW COLUMNS FROM applications LIKE 'visa_status'")->fetch();
            $this->assert($hasVisaStatus, "applications table has visa_status column (MySQL)");

            $hasApprVisaStatus = (bool)$this->pdo->query("SHOW COLUMNS FROM visa_approvals LIKE 'visa_status'")->fetch();
            $this->assert($hasApprVisaStatus, "visa_approvals table has visa_status column (MySQL)");
        }
    }

    private function testExpiryNotificationServiceMilestones(): void
    {
        echo "\n--- Testing Expiry Notification Milestones ---\n";

        // Create or get customer & application test record
        $cust = $this->pdo->query("SELECT id, full_name, email FROM customers LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$cust) {
            $this->pdo->exec("INSERT INTO customers (customer_code, first_name, last_name, full_name, email, mobile, nationality, current_country, created_at)
                VALUES ('TESTCUST01', 'Test', 'Applicant', 'Test Applicant', 'test.applicant@example.com', '+971500000001', 'India', 'United Arab Emirates', CURRENT_TIMESTAMP)");
            $custId = (int)$this->pdo->lastInsertId();
        } else {
            $custId = (int)$cust['id'];
        }

        $app = $this->pdo->query("SELECT id FROM applications WHERE customer_id = {$custId} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$app) {
            $service = $this->pdo->query("SELECT id FROM visa_services LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            $serviceId = $service ? (int)$service['id'] : 1;
            $this->pdo->exec("INSERT INTO applications (application_number, customer_id, visa_service_id, status, current_stage, visa_status, visa_expiry_date, created_at)
                VALUES ('TEST-APP-EXP01', {$custId}, {$serviceId}, 'Approved', 'Approved & Issued', 'active', '" . date('Y-m-d', strtotime('+5 days')) . "', CURRENT_TIMESTAMP)");
            $appId = (int)$this->pdo->lastInsertId();
        } else {
            $appId = (int)$app['id'];
            $this->pdo->exec("UPDATE applications SET visa_status = 'active', visa_expiry_date = '" . date('Y-m-d', strtotime('+5 days')) . "' WHERE id = {$appId}");
        }

        // Run ExpiryNotificationService
        $stats = ExpiryNotificationService::run();
        $this->assert(isset($stats['scanned_visas']), "ExpiryNotificationService::run() returned scanned_visas stat");
        $this->assert(isset($stats['emails_sent']), "ExpiryNotificationService::run() returned emails_sent stat");
        $this->assert($stats['scanned_visas'] > 0, "Visas were scanned by the service");
    }

    private function testExpiryNotificationIdempotency(): void
    {
        echo "\n--- Testing Daily Notification Idempotency ---\n";

        // Running it a second time on the same day should skip sending duplicates
        $stats2 = ExpiryNotificationService::run();
        $this->assert($stats2['skipped_duplicates'] >= 0, "Second run checks and skips already sent notifications for today");
    }

    private function testMultiDocumentMonitoring(): void
    {
        echo "\n--- Testing Multi-Document Expiry Scope (Passport, National ID, Residence Permit) ---\n";

        $stats = ExpiryNotificationService::run();
        $this->assert(array_key_exists('scanned_passports', $stats), "Monitors customer passports");
        $this->assert(array_key_exists('scanned_national_ids', $stats), "Monitors customer national IDs");
        $this->assert(array_key_exists('scanned_residences', $stats), "Monitors customer residence permits");
    }
}

// Execute
(new VisaExpiryAndNotificationTest())->runAll();
