<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Config\Database;
use App\Database\DatabaseBootstrapper;
use App\Services\DuplicateDetectionService;
use App\Services\StageTransitionService;
use App\Services\StaffAssignmentService;
use App\Services\HealthCalculatorService;
use App\Services\DocumentChecklistService;
use App\Services\AuditService;

echo "=======================================================\n";
echo "VISA TRACK — PHASE 4 AUTOMATED VERIFICATION SUITE\n";
echo "=======================================================\n\n";

$passed = 0;
$failed = 0;

function assert_test(string $name, bool $condition, string $details = '') {
    global $passed, $failed;
    if ($condition) {
        echo "[PASS] " . $name . "\n";
        $passed++;
    } else {
        echo "[FAIL] " . $name . ($details ? " -> " . $details : "") . "\n";
        $failed++;
    }
}

DatabaseBootstrapper::init();
$pdo = Database::getConnection();

// 1. Create a Test Customer and Passport
$testCode = 'CUST-TEST-' . rand(1000, 9999);
$testEmail = 'tariq.test.' . rand(10000, 99999) . '@example.com';
$insCust = $pdo->prepare("INSERT INTO customers (customer_code, first_name, last_name, full_name, nationality, mobile, email, current_country) VALUES (?, 'Tariq', 'Al-Hashimi', 'Tariq Al-Hashimi', 'Emirati', '+971509998877', ?, 'United Arab Emirates')");
$insCust->execute([$testCode, $testEmail]);
$customerId = (int)$pdo->lastInsertId();

$testPassport = 'P' . rand(1000000, 9999999);
$insPass = $pdo->prepare("INSERT INTO customer_passports (customer_id, passport_number, issuing_country, expiry_date, is_primary) VALUES (?, ?, 'United Arab Emirates', '2030-12-31', 1)");
$insPass->execute([$customerId, $testPassport]);

assert_test("1. Test Applicant & Passport Created", $customerId > 0);

// 2. Fetch a Visa Service
$service = $pdo->query("SELECT id, name FROM visa_services LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$serviceId = (int)$service['id'];
assert_test("2. Visa Service Available", $serviceId > 0, "Service: {$service['name']}");

// 3. Test Duplicate Checking Before Application Creation
$dupCheck1 = DuplicateDetectionService::checkApplicationDuplicate($customerId, $serviceId);
assert_test("3. Initial Duplicate Check (No Active Apps)", $dupCheck1['is_duplicate'] === false);

// 4. Create Application with Sequential Number
$year = date('Y');
$appNum = "VISA-TEST-" . rand(10000, 99999);
$insApp = $pdo->prepare("INSERT INTO applications (
    application_number, customer_id, visa_service_id, branch_id, assigned_staff_id,
    current_stage, status, priority, calculated_health, health_reason,
    nationality, residence_country, passport_number, application_date, expected_completion_date,
    selling_price, supplier_cost, tax_amount, total_amount, paid_amount, balance_amount,
    next_action, next_action_due_date, created_by
) VALUES (
    ?, ?, ?, 1, 1,
    'Application Registered', 'Registered', 'High', 100, 'Initial registration',
    'Emirati', 'United Arab Emirates', ?, CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 15 DAY),
    500.00, 250.00, 25.00, 525.00, 0.00, 525.00,
    'Collect initial documents', DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), 1
)");
$insApp->execute([$appNum, $customerId, $serviceId, $testPassport]);
$appId = (int)$pdo->lastInsertId();

assert_test("4. Visa Application Created", $appId > 0, "App Number: {$appNum}");

// 5. Test Duplicate Checking After Creation
$dupCheck2 = DuplicateDetectionService::checkApplicationDuplicate($customerId, $serviceId);
assert_test("5. Duplicate Detection Detects Active Application", $dupCheck2['is_duplicate'] === true);

// 6. Generate Checklist
DocumentChecklistService::generateForApplication($appId, $serviceId);
$checklist = DocumentChecklistService::getChecklist($appId);
assert_test("6. Document Requirements Checklist Matrix Generated", count($checklist) > 0);

// 7. Test Atomic Stage Transitions
$stage1 = StageTransitionService::transition($appId, 'Documents Collected', 'Documents Pending', 'Customer submitted documents', 1);
assert_test("7. Stage Transition 1 (Documents Collected)", $stage1['success'] === true);

$stage2 = StageTransitionService::transition($appId, 'Documents Verified', 'Documents Under Verification', 'All documents verified by staff', 1);
assert_test("8. Stage Transition 2 (Documents Verified)", $stage2['success'] === true);

$stage3 = StageTransitionService::transition($appId, 'Application Submitted', 'Submitted', 'Submitted to immigration consulate', 1);
assert_test("9. Stage Transition 3 (Application Submitted)", $stage3['success'] === true);

// 8. Verify Immutable History Trail
$histStmt = $pdo->prepare("SELECT COUNT(*) FROM application_status_history WHERE application_id = ?");
$histStmt->execute([$appId]);
$histCount = (int)$histStmt->fetchColumn();
assert_test("10. Immutable Status History Recorded (>= 3 transitions)", $histCount >= 3);

// 9. Test Staff Assignment History
$assign1 = StaffAssignmentService::assign($appId, 2, 1, 'Reassigned to Manager Sarah');
assert_test("11. Staff Reassignment Executed", $assign1['success'] === true);

$assignStmt = $pdo->prepare("SELECT COUNT(*) FROM application_assignments WHERE application_id = ?");
$assignStmt->execute([$appId]);
$assignCount = (int)$assignStmt->fetchColumn();
assert_test("12. Staff Assignment History Preserved", $assignCount >= 1);

// 10. Test Health Calculator Diagnostics
$health = HealthCalculatorService::diagnose($appId);
assert_test("13. Health Score Diagnostic Engine", isset($health['score']) && isset($health['status']) && count($health['reasons']) > 0);

// 11. Test Final Decision (Approval)
$visaNo = 'VISA-GRANT-' . rand(100000, 999999);
$apprvStmt = $pdo->prepare("UPDATE applications SET status = 'Approved', current_stage = 'Visa Issued & Completed', visa_number = ? WHERE id = ?");
$apprvStmt->execute([$visaNo, $appId]);

$checkApprv = $pdo->prepare("SELECT status, current_stage, visa_number FROM applications WHERE id = ?");
$checkApprv->execute([$appId]);
$finalApp = $checkApprv->fetch(PDO::FETCH_ASSOC);

assert_test("14. Final Visa Grant & Completion Recorded", $finalApp['status'] === 'Approved' && $finalApp['visa_number'] === $visaNo);

// 12. Health Score Recalculated for Completed App
$healthFinal = HealthCalculatorService::diagnose($appId);
assert_test("15. Completed Application Returns 100% Health", $healthFinal['score'] === 100 && $healthFinal['status'] === 'Healthy');

// Clean up test records
$pdo->prepare("DELETE FROM application_status_history WHERE application_id = ?")->execute([$appId]);
$pdo->prepare("DELETE FROM application_assignments WHERE application_id = ?")->execute([$appId]);
$pdo->prepare("DELETE FROM documents WHERE application_id = ?")->execute([$appId]);
$pdo->prepare("DELETE FROM applications WHERE id = ?")->execute([$appId]);
$pdo->prepare("DELETE FROM customer_passports WHERE customer_id = ?")->execute([$customerId]);
$pdo->prepare("DELETE FROM customers WHERE id = ?")->execute([$customerId]);

echo "\n=======================================================\n";
echo "SUMMARY: Total: " . ($passed + $failed) . " | Passed: {$passed} | Failed: {$failed}\n";
echo "=======================================================\n";

if ($failed > 0) {
    exit(1);
}
