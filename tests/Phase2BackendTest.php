<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Config\Database;
use App\Services\StageTransitionService;
use App\Services\StaffAssignmentService;
use App\Services\HealthCalculatorService;
use App\Services\DuplicateDetectionService;
use App\Validators\ApplicantValidator;
use App\Validators\ApplicationValidator;
use App\Validators\StageValidator;
use App\Validators\DocumentValidator;
use App\Validators\TaskValidator;
use App\Validators\AppointmentValidator;

echo "=========================================================\n";
echo "   PHASE 2 — DATABASE ARCHITECTURE & BACKEND TEST SUITE  \n";
echo "=========================================================\n\n";

$pdo = Database::getConnection();
$passed = 0;
$failed = 0;

function assertTest(string $name, bool $condition, string $details = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$name}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$name} - {$details}\n";
    }
}

// ---------------------------------------------------------
// 1. DATABASE CONNECTION & SCHEMA INTEGRITY
// ---------------------------------------------------------
echo "[1] Testing Database Connection & Schema Tables / Views...\n";
$entities = [
    'users', 'roles', 'permissions', 'role_permissions',
    'customers', 'customer_passports', 'countries', 'visa_categories',
    'visa_services', 'document_types', 'visa_requirements',
    'applications', 'application_status_history', 'application_assignments',
    'documents', 'document_requests', 'tasks',
    'appointments', 'notifications', 'email_templates', 'activity_logs',
    'applicants', 'visa_applications', 'application_documents', 'application_tasks',
    'application_appointments', 'audit_logs', 'companies'
];

foreach ($entities as $ent) {
    try {
        $stmt = $pdo->query("SELECT 1 FROM `{$ent}` LIMIT 1");
        assertTest("Database entity '{$ent}' exists", true);
    } catch (\PDOException $e) {
        assertTest("Database entity '{$ent}' exists", false, $e->getMessage());
    }
}

// ---------------------------------------------------------
// 2. APPLICANT VALIDATION & DUPLICATE DETECTION
// ---------------------------------------------------------
echo "\n[2] Testing Applicant Validation & Duplicate Detection...\n";
$appValidator = new ApplicantValidator();

// Test Valid Applicant
$validData = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'nationality' => 'United States',
    'mobile' => '+1 555 123 4567',
    'email' => 'john.doe.test' . time() . '@example.com',
    'dob' => '1990-05-15',
    'passport_number' => 'US' . rand(100000, 999999),
    'passport_expiry_date' => '2030-05-15'
];
assertTest("Valid applicant passes validation", $appValidator->validate($validData));

// Test Missing First Name
$invalidData = $validData;
unset($invalidData['first_name']);
$invValidator = new ApplicantValidator();
assertTest("Missing required fields fail validation", !$invValidator->validate($invalidData));

// Test Duplicate Detection Service
$dupCheck = DuplicateDetectionService::checkDuplicate(
    '+971 50 123 4567', // Rahul Sharma mobile in seed
    'rahul.sharma@cloudtech.ae',
    'Z6543219'
);
assertTest("Duplicate detection identifies existing applicant", $dupCheck['is_duplicate'] === true);

// ---------------------------------------------------------
// 3. APPLICATION VALIDATION & CREATION
// ---------------------------------------------------------
echo "\n[3] Testing Visa Application Creation & Foreign Keys...\n";
$appVal = new ApplicationValidator();

// Test Invalid Foreign Keys
$badApp = [
    'customer_id' => 999999, // Non-existent
    'visa_service_id' => 999999,
    'assigned_staff_id' => 999999
];
assertTest("Invalid foreign keys fail application validation", !$appVal->validate($badApp));

// Test Valid Application Data
$goodApp = [
    'customer_id' => 1,
    'visa_service_id' => 1,
    'assigned_staff_id' => 1,
    'selling_price' => 500,
    'discount' => 0,
    'tax_amount' => 25
];
$goodVal = new ApplicationValidator();
assertTest("Valid application data passes validation", $goodVal->validate($goodApp));

// ---------------------------------------------------------
// 4. ATOMIC STAGE TRANSITIONS & HISTORY PRESERVATION
// ---------------------------------------------------------
echo "\n[4] Testing Atomic Stage Transition & History Trail...\n";
// Create temporary test application
$pdo->beginTransaction();
$testAppNum = 'TEST-APP-' . time();
$pdo->prepare("INSERT INTO applications 
    (application_number, customer_id, visa_service_id, branch_id, assigned_staff_id, current_stage, status, priority, nationality, residence_country, passport_number, application_date, total_amount) 
    VALUES (?, 1, 1, 1, 1, 'Application Registered', 'Draft', 'Normal', 'India', 'United Arab Emirates', 'Z6543219', CURRENT_DATE, 500)")
    ->execute([$testAppNum]);
$testAppId = (int)$pdo->lastInsertId();
$pdo->commit();

// Advance Stage 1 -> Documents Collected
$res1 = StageTransitionService::transition($testAppId, 'Documents Collected', 'Documents Pending', 'Customer uploaded passport and photo', 1);
assertTest("Stage transition 1 executes successfully", $res1['success'] === true);

// Advance Stage 2 -> Documents Verified
$res2 = StageTransitionService::transition($testAppId, 'Documents Verified', 'In Process', 'All mandatory docs verified by officer', 1);
assertTest("Stage transition 2 executes successfully", $res2['success'] === true);

// Verify History Preservation
$stmtHistory = $pdo->prepare("SELECT * FROM application_status_history WHERE application_id = ? ORDER BY created_at ASC");
$stmtHistory->execute([$testAppId]);
$historyRows = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);
assertTest("Complete stage history preserved (2 records created)", count($historyRows) === 2);
assertTest("First history step correctly records from 'Application Registered' to 'Documents Collected'", $historyRows[0]['to_stage'] === 'Documents Collected');
assertTest("Second history step correctly records from 'Documents Collected' to 'Documents Verified'", $historyRows[1]['to_stage'] === 'Documents Verified');

// Verify Current Stage updated on application
$stmtCheckApp = $pdo->prepare("SELECT current_stage, status FROM applications WHERE id = ?");
$stmtCheckApp->execute([$testAppId]);
$updatedApp = $stmtCheckApp->fetch(PDO::FETCH_ASSOC);
assertTest("Application current_stage updated to 'Documents Verified'", $updatedApp['current_stage'] === 'Documents Verified');

// ---------------------------------------------------------
// 5. STAFF ASSIGNMENT HISTORY TRACKING
// ---------------------------------------------------------
echo "\n[5] Testing Staff Assignment History Tracking...\n";
// Assign to Staff ID 2 (Sarah Jenkins)
$assignRes1 = StaffAssignmentService::assign($testAppId, 2, 1, 'Transferred case to Manager');
assertTest("Staff assignment to Officer 2 succeeds", $assignRes1['success'] === true);

// Reassign to Staff ID 4 (Fatima Al-Zaabi)
$assignRes2 = StaffAssignmentService::assign($testAppId, 4, 2, 'Assigned to Senior Officer');
assertTest("Staff reassignment to Officer 4 succeeds", $assignRes2['success'] === true);

// Verify assignment records in application_assignments
$stmtAssign = $pdo->prepare("SELECT * FROM application_assignments WHERE application_id = ? ORDER BY id ASC");
$stmtAssign->execute([$testAppId]);
$assignRecords = $stmtAssign->fetchAll(PDO::FETCH_ASSOC);
assertTest("2 assignment history records created", count($assignRecords) === 2);
assertTest("First officer assignment marked inactive (is_current = 0)", (int)$assignRecords[0]['is_current'] === 0);
assertTest("Second officer assignment marked active (is_current = 1)", (int)$assignRecords[1]['is_current'] === 1);

// ---------------------------------------------------------
// 6. DOCUMENT VALIDATION & VERIFICATION WORKFLOW
// ---------------------------------------------------------
echo "\n[6] Testing Document Verification Lifecycle...\n";
// Insert test document metadata
$pdo->prepare("INSERT INTO documents 
    (application_id, customer_id, document_type_id, document_title, file_name, file_path, file_size, mime_type, status, version) 
    VALUES (?, 1, 1, 'Passport Bio Page', 'test_passport.pdf', 'test_passport.pdf', 102400, 'application/pdf', 'UNDER_REVIEW', 1)")
    ->execute([$testAppId]);
$docId = (int)$pdo->lastInsertId();

// Verify Document
$pdo->prepare("UPDATE documents SET status = 'VERIFIED', verified_by = 1, verified_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$docId]);
$stmtDocCheck = $pdo->prepare("SELECT status, verified_by FROM documents WHERE id = ?");
$stmtDocCheck->execute([$docId]);
$verifiedDoc = $stmtDocCheck->fetch(PDO::FETCH_ASSOC);
assertTest("Document status successfully updated to 'VERIFIED'", $verifiedDoc['status'] === 'VERIFIED');

// ---------------------------------------------------------
// 7. TASK & APPOINTMENT MANAGEMENT
// ---------------------------------------------------------
echo "\n[7] Testing Task & Appointment Management...\n";
$taskValidator = new TaskValidator();
$taskData = [
    'application_id' => $testAppId,
    'task_title' => 'Follow up on medical fitness test certificate',
    'assigned_to' => 4,
    'priority' => 'High',
    'due_date' => date('Y-m-d', strtotime('+3 days')),
    'status' => 'Pending'
];
assertTest("Task validator passes valid task data", $taskValidator->validate($taskData));

$aptValidator = new AppointmentValidator();
$aptData = [
    'application_id' => $testAppId,
    'appointment_type' => 'Medical Fitness Test',
    'center_name' => 'Smart Salem Al Quoz',
    'appointment_date' => date('Y-m-d', strtotime('+2 days')),
    'appointment_time' => '10:00',
    'status' => 'Scheduled'
];
assertTest("Appointment validator passes valid appointment data", $aptValidator->validate($aptData));

// ---------------------------------------------------------
// 8. AUDIT LOGGING INTEGRITY
// ---------------------------------------------------------
echo "\n[8] Testing Audit Trail...\n";
$stmtAudit = $pdo->prepare("SELECT * FROM activity_logs WHERE record_id = ? AND module = 'Applications' ORDER BY id DESC");
$stmtAudit->execute([$testAppId]);
$auditEntries = $stmtAudit->fetchAll(PDO::FETCH_ASSOC);
assertTest("Audit log entries recorded for application events", count($auditEntries) >= 2);

// Clean up temporary test record
$pdo->prepare("DELETE FROM application_status_history WHERE application_id = ?")->execute([$testAppId]);
$pdo->prepare("DELETE FROM application_assignments WHERE application_id = ?")->execute([$testAppId]);
$pdo->prepare("DELETE FROM documents WHERE id = ?")->execute([$docId]);
$pdo->prepare("DELETE FROM activity_logs WHERE record_id = ?")->execute([$testAppId]);
$pdo->prepare("DELETE FROM applications WHERE id = ?")->execute([$testAppId]);

// ---------------------------------------------------------
// FINAL TEST RESULTS
// ---------------------------------------------------------
echo "\n=========================================================\n";
echo " TEST EXECUTION SUMMARY: Total: " . ($passed + $failed) . " | Passed: {$passed} | Failed: {$failed}\n";
echo "=========================================================\n";

if ($failed === 0) {
    echo ">>> ALL PHASE 2 DATABASE & BACKEND TESTS PASSED 100%! <<<\n";
    exit(0);
} else {
    echo ">>> SOME TESTS FAILED! <<<\n";
    exit(1);
}
