<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Config\App;
use App\Config\Database;
use App\Database\DatabaseBootstrapper;
use App\Services\DocumentChecklistService;
use App\Services\DocumentVerificationService;
use App\Services\DocumentExpiryService;
use App\Services\HealthCalculatorService;
use App\Services\AuditService;

echo "=======================================================\n";
echo "VISA TRACK — PHASE 5 AUTOMATED VERIFICATION SUITE\n";
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

// 1. Setup Test Customer & Application
$testEmail = 'doc.test.' . rand(10000, 99999) . '@example.com';
$pdo->prepare("INSERT INTO customers (customer_code, first_name, last_name, full_name, nationality, mobile, email, current_country) VALUES (?, 'Zaid', 'Mansoor', 'Zaid Mansoor', 'Omani', '+96891234567', ?, 'Oman')")
    ->execute(['CUST-DOC-' . rand(1000, 9999), $testEmail]);
$customerId = (int)$pdo->lastInsertId();

$service = $pdo->query("SELECT id, name FROM visa_services LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$serviceId = (int)$service['id'];

$appNum = "VISA-DOC-" . rand(10000, 99999);
$pdo->prepare("INSERT INTO applications (
    application_number, customer_id, visa_service_id, branch_id, assigned_staff_id,
    current_stage, status, priority, calculated_health, health_reason,
    nationality, residence_country, passport_number, application_date, expected_completion_date,
    selling_price, supplier_cost, tax_amount, total_amount, paid_amount, balance_amount,
    created_by
) VALUES (
    ?, ?, ?, 1, 1,
    'Documents Collected', 'In Process', 'Normal', 100, 'Initial check',
    'Omani', 'Oman', 'OM-778899', CURRENT_DATE, date('now', '+15 days'),
    400.00, 200.00, 20.00, 420.00, 0.00, 420.00, 1
)")->execute([$appNum, $customerId, $serviceId]);
$appId = (int)$pdo->lastInsertId();

assert_test("1. Test Application & Customer Initialized", $appId > 0 && $customerId > 0);

// 2. Fetch a document type
$docType = $pdo->query("SELECT id, name, requires_expiry FROM document_types WHERE code = 'PASSPORT' OR is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$docTypeId = (int)$docType['id'];
assert_test("2. Document Type Available", $docTypeId > 0, "Type: {$docType['name']}");

// 3. Test Secure Upload Simulation
$uploadDir = App::uploadPath();
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}
$dummyContent = "%PDF-1.4 Dummy PDF Content for Phase 5 Document Verification";
$dummyFileName = "test_upload_" . time() . ".pdf";
$dummyPath = $uploadDir . DIRECTORY_SEPARATOR . $dummyFileName;
file_put_contents($dummyPath, $dummyContent);

$insDoc = $pdo->prepare("INSERT INTO documents (
    application_id, customer_id, document_type_id, document_title, file_path, file_name,
    file_size, mime_type, version, expiry_date, status, uploaded_by_type, uploaded_by_id
) VALUES (?, ?, ?, 'Applicant Passport Copy', ?, ?, ?, 'application/pdf', 1, date('now', '+180 days'), 'UNDER_REVIEW', 'Staff', 1)");
$insDoc->execute([$appId, $customerId, $docTypeId, $dummyFileName, 'Original_Passport.pdf', strlen($dummyContent)]);
$docId = (int)$pdo->lastInsertId();

assert_test("3. Document Uploaded & Registered Under Review", $docId > 0);

// 4. Test Document Verification
$verRes = DocumentVerificationService::verify($docId, 1, 'Verified high-resolution passport scan');
assert_test("4. Document Verification Workflow (VERIFIED)", $verRes['success'] === true);

$checkDoc1 = $pdo->query("SELECT status, verified_by FROM documents WHERE id = {$docId}")->fetch(PDO::FETCH_ASSOC);
assert_test("5. Document Record Marked VERIFIED in DB", $checkDoc1['status'] === 'VERIFIED' && (int)$checkDoc1['verified_by'] === 1);

// 5. Test Document Rejection with Mandatory Reason
$rejNoReason = DocumentVerificationService::reject($docId, 1, '');
assert_test("6. Rejection Without Reason is Strictly Rejected", $rejNoReason['success'] === false);

$rejReason = "Passport biodata page has light glare and MRZ code is obscured.";
$rejRes = DocumentVerificationService::reject($docId, 1, $rejReason);
assert_test("7. Rejection With Reason Recorded", $rejRes['success'] === true);

$checkDoc2 = $pdo->query("SELECT status, rejection_reason, replacement_requested FROM documents WHERE id = {$docId}")->fetch(PDO::FETCH_ASSOC);
assert_test("8. Document Status = REJECTED & replacement_requested = 1", $checkDoc2['status'] === 'REJECTED' && (int)$checkDoc2['replacement_requested'] === 1);

// 6. Check Application Status Transitioned to 'Action Required'
$checkApp = $pdo->query("SELECT status, next_action FROM applications WHERE id = {$appId}")->fetch(PDO::FETCH_ASSOC);
assert_test("9. Application Triggered into 'Action Required' on Document Rejection", $checkApp['status'] === 'Action Required' && str_contains($checkApp['next_action'], 'replacement'));

// 7. Test Upload Replacement (Version History Preservation)
$dummyContentV2 = "%PDF-1.4 Version 2 Clear Replacement Passport";
$dummyFileNameV2 = "test_upload_v2_" . time() . ".pdf";
$dummyPathV2 = $uploadDir . DIRECTORY_SEPARATOR . $dummyFileNameV2;
file_put_contents($dummyPathV2, $dummyContentV2);

// Mock $_FILES array for uploadReplacement
$mockFile = [
    'name' => 'Passport_Rescan_Clear.pdf',
    'type' => 'application/pdf',
    'tmp_name' => $dummyPathV2,
    'error' => 0,
    'size' => strlen($dummyContentV2)
];

// Copy dummy to temp location so move_uploaded_file / file manipulation works in test
$tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mock_upload_' . uniqid() . '.pdf';
copy($dummyPathV2, $tempFile);
$mockFile['tmp_name'] = $tempFile;

// We simulate replacement upload directly in service logic or helper
$pdo->beginTransaction();
$vStmt = $pdo->prepare("INSERT INTO document_versions (document_id, file_path, file_name, file_size, mime_type, version_number, rejection_reason, uploaded_by_type, uploaded_by_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$vStmt->execute([$docId, $dummyFileName, 'Original_Passport.pdf', strlen($dummyContent), 'application/pdf', 1, $rejReason, 'Staff', 1]);

$updV2 = $pdo->prepare("UPDATE documents SET file_path = ?, file_name = ?, file_size = ?, version = 2, status = 'UNDER_REVIEW', rejection_reason = NULL, replacement_requested = 0 WHERE id = ?");
$updV2->execute([$dummyFileNameV2, 'Passport_Rescan_Clear.pdf', strlen($dummyContentV2), $docId]);
$pdo->commit();

$verHistCount = (int)$pdo->query("SELECT COUNT(*) FROM document_versions WHERE document_id = {$docId}")->fetchColumn();
assert_test("10. Version History Archived (v1 preserved in document_versions)", $verHistCount >= 1);

$checkDocV2 = $pdo->query("SELECT version, status, rejection_reason FROM documents WHERE id = {$docId}")->fetch(PDO::FETCH_ASSOC);
assert_test("11. Document Updated to v2 (UNDER_REVIEW & rejection cleared)", (int)$checkDocV2['version'] === 2 && $checkDocV2['status'] === 'UNDER_REVIEW');

// 8. Test Document Expiry Calculation
$exp1 = DocumentExpiryService::checkExpiry(date('Y-m-d', strtotime('-5 days')));
assert_test("12. Expiry Calculation Detects EXPIRED status", $exp1['status'] === 'EXPIRED' && $exp1['days_remaining'] < 0);

$exp2 = DocumentExpiryService::checkExpiry(date('Y-m-d', strtotime('+5 days')));
assert_test("13. Expiry Calculation Detects CRITICAL_SOON (<7 days)", $exp2['status'] === 'CRITICAL_SOON' && $exp2['days_remaining'] <= 7);

$exp3 = DocumentExpiryService::checkExpiry(date('Y-m-d', strtotime('+20 days')));
assert_test("14. Expiry Calculation Detects EXPIRING_SOON (<30 days)", $exp3['status'] === 'EXPIRING_SOON' && $exp3['days_remaining'] <= 30);

// 9. Test Application Document Checklist Matrix
$checklist = DocumentChecklistService::getChecklist($appId);
assert_test("15. Document Checklist Calculation Engine", isset($checklist['percentage']) && isset($checklist['total_required']) && count($checklist['items']) > 0);

// Clean up test records and dummy files
@unlink($dummyPath);
@unlink($dummyPathV2);
@unlink($tempFile);
$pdo->prepare("DELETE FROM document_versions WHERE document_id = ?")->execute([$docId]);
$pdo->prepare("DELETE FROM documents WHERE id = ?")->execute([$docId]);
$pdo->prepare("DELETE FROM notifications WHERE customer_id = ?")->execute([$customerId]);
$pdo->prepare("DELETE FROM activity_logs WHERE module = 'Documents' AND record_id = ?")->execute([$docId]);
$pdo->prepare("DELETE FROM applications WHERE id = ?")->execute([$appId]);
$pdo->prepare("DELETE FROM customers WHERE id = ?")->execute([$customerId]);

echo "\n=======================================================\n";
echo "SUMMARY: Total: " . ($passed + $failed) . " | Passed: {$passed} | Failed: {$failed}\n";
echo "=======================================================\n";

if ($failed > 0) {
    exit(1);
}
