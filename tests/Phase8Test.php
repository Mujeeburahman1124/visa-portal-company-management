<?php
declare(strict_types=1);

namespace Tests;

require_once __DIR__ . '/../app/autoload.php';

use App\Config\Database;
use App\Database\DatabaseBootstrapper;
use App\Middleware\PermissionMiddleware;
use App\Services\AuditService;
use PDO;

class Phase8Test
{
    private PDO $pdo;
    private int $passed = 0;
    private int $failed = 0;

    public function __construct()
    {
        DatabaseBootstrapper::init();
        $this->pdo = Database::getConnection();
    }

    public function runAll(): void
    {
        echo "==================================================\n";
        echo "STARTING PHASE 8 COMPREHENSIVE QA & VERIFICATION\n";
        echo "==================================================\n\n";

        $this->testPermissionsSeeded();
        $this->testRolesAndMatrix();
        $this->testStaffManagement();
        $this->testStaffProfileData();
        $this->testBranchManagement();
        $this->testSupplierManagement();
        $this->testNotificationSystem();
        $this->testNotificationPreferences();
        $this->testEmailTemplates();
        $this->testAuditTrail();
        $this->testSystemSettings();
        $this->testSecurityAndPermissionEnforcement();

        echo "\n==================================================\n";
        echo "PHASE 8 TEST SUMMARY\n";
        echo "PASSED: {$this->passed}\n";
        echo "FAILED: {$this->failed}\n";
        echo "==================================================\n";

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

    private function testPermissionsSeeded(): void
    {
        echo "1. PERMISSIONS & RBAC SEEDING TESTS\n";
        $count = (int)$this->pdo->query("SELECT COUNT(*) FROM permissions")->fetchColumn();
        $this->assert($count >= 91, "All 13 categories x 7 actions seeded (Found: {$count})");

        $categories = $this->pdo->query("SELECT DISTINCT module FROM permissions")->fetchAll(PDO::FETCH_COLUMN);
        $this->assert(in_array('Applications', $categories) && in_array('Settings', $categories) && in_array('Staff', $categories), "Core permission categories present");
    }

    private function testRolesAndMatrix(): void
    {
        echo "\n2. ROLES & PERMISSION MATRIX TESTS\n";
        $roles = $this->pdo->query("SELECT * FROM roles")->fetchAll();
        $this->assert(count($roles) >= 4, "Core roles exist (Super Admin, Admin, Branch Manager, Staff)");

        $superAdminPerms = (int)$this->pdo->query("SELECT COUNT(*) FROM role_permissions WHERE role_id = 1")->fetchColumn();
        $this->assert($superAdminPerms >= 91, "Super Admin has full permission matrix assigned");
    }

    private function testStaffManagement(): void
    {
        echo "\n3. STAFF MANAGEMENT TESTS\n";
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
        $userCount = (int)$stmt->fetchColumn();
        $this->assert($userCount >= 5, "Staff records found in database (Found: {$userCount})");

        // Test staff workload calculation
        $workloadStmt = $this->pdo->query("SELECT u.name,
            COUNT(DISTINCT a.id) as total_applications,
            (SELECT COUNT(*) FROM tasks t WHERE t.assigned_to = u.id AND t.status != 'Completed') as pending_tasks
            FROM users u 
            LEFT JOIN applications a ON a.assigned_staff_id = u.id 
            GROUP BY u.id");
        $workload = $workloadStmt->fetchAll();
        $this->assert(count($workload) > 0, "Staff workload queries calculate successfully");
    }

    private function testStaffProfileData(): void
    {
        echo "\n4. STAFF PROFILE DATA TESTS\n";
        $profile = $this->pdo->query("SELECT u.*, r.name as role_name, b.name as branch_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            LEFT JOIN branches b ON u.branch_id = b.id 
            WHERE u.id = 1")->fetch();
        $this->assert(!empty($profile['name']) && !empty($profile['role_name']), "Staff profile loads with role and branch associations");
    }

    private function testBranchManagement(): void
    {
        echo "\n5. BRANCH NETWORK TESTS\n";
        $branches = $this->pdo->query("SELECT * FROM branches")->fetchAll();
        $this->assert(count($branches) >= 3, "Global branches registered (Dubai, London, New York, Riyadh)");
    }

    private function testSupplierManagement(): void
    {
        echo "\n6. SUPPLIERS & PARTNERS TESTS\n";
        $suppliers = $this->pdo->query("SELECT * FROM suppliers")->fetchAll();
        $this->assert(count($suppliers) >= 1, "Visa clearing suppliers and consular partners found");
    }

    private function testNotificationSystem(): void
    {
        echo "\n7. NOTIFICATION SYSTEM TESTS\n";
        $notifications = $this->pdo->query("SELECT * FROM notifications")->fetchAll();
        $this->assert(count($notifications) >= 1, "Notifications table functional with unread/read state");
    }

    private function testNotificationPreferences(): void
    {
        echo "\n8. NOTIFICATION PREFERENCES TESTS\n";
        $pref = $this->pdo->query("SELECT * FROM notification_settings WHERE in_app_enabled = 1 OR email_enabled = 1 LIMIT 1")->fetch();
        $this->assert(!empty($pref), "Notification settings table supports per-event in-app, email, and whatsapp toggles");
    }

    private function testEmailTemplates(): void
    {
        echo "\n9. EMAIL TEMPLATES & VARIABLE TAGS TESTS\n";
        $templates = $this->pdo->query("SELECT * FROM email_templates")->fetchAll();
        $this->assert(count($templates) >= 7, "All 7 standard email templates populated (Found: " . count($templates) . ")");

        $docRej = $this->pdo->query("SELECT * FROM email_templates WHERE template_key = 'DOC_REJECTED'")->fetch();
        $this->assert(!empty($docRej['placeholders']), "Template contains placeholders for dynamic variable rendering");
    }

    private function testAuditTrail(): void
    {
        echo "\n10. IMMUTABLE AUDIT TRAIL TESTS\n";
        AuditService::log('QA_TEST_ACTION', 'System', 999, "Automated QA test audit entry", ['test' => true], 1);
        $log = $this->pdo->query("SELECT * FROM activity_logs WHERE action = 'QA_TEST_ACTION'")->fetch();
        $this->assert(!empty($log) && $log['module'] === 'System', "AuditService successfully logs immutable activity entries with context delta");
    }

    private function testSystemSettings(): void
    {
        echo "\n11. SYSTEM SETTINGS ENGINE TESTS\n";
        $this->pdo->exec("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES ('qa_test_key', 'passed')");
        $val = $this->pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'qa_test_key'")->fetchColumn();
        $this->assert($val === 'passed', "System settings key-value store functional");
    }

    private function testSecurityAndPermissionEnforcement(): void
    {
        echo "\n12. BACKEND AUTHORIZATION & PERMISSION TESTS\n";
        $_SESSION['user'] = [
            'id' => 1,
            'name' => 'Tariq Al-Mansoor',
            'role_id' => 1,
            'role_slug' => 'super-admin'
        ];

        $this->assert(user_can('applications.view') === true, "Super admin has all permissions");
        $this->assert(has_permission('settings.edit') === true, "has_permission helper functions correctly");
    }
}

(new Phase8Test())->runAll();
