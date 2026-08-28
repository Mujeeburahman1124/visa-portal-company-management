<?php
declare(strict_types=1);

if (!defined('IN_TEST_MODE')) {
    define('IN_TEST_MODE', true);
}

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Config\Database;
use App\Database\DatabaseBootstrapper;
use App\Controllers\CustomerController;

class NewApplicantFormTest
{
    private \PDO $pdo;
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
        echo "MS TRAVEL HUB: NEW APPLICANT FORM & VISA INTEGRATION TEST SUITE\n";
        echo "======================================================================\n";

        $this->testFormViewRendering();
        $this->testPayLaterRegistration();
        $this->testPayNowRegistrationWithWallet();
        $this->testManualCategoryAndTypePersistence();
        $this->testManualDurationEntryAndProcessingPersistence();

        echo "\n======================================================================\n";
        echo "NEW APPLICANT FORM TEST SUMMARY\n";
        echo "TOTAL PASSED: {$this->passed}\n";
        echo "TOTAL FAILED: {$this->failed}\n";
        echo "STATUS: " . ($this->failed === 0 ? "ALL APPLICANT FORM CRITERIA VERIFIED SUCCESSFUL" : "FAILURES DETECTED") . "\n";
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

    private function testFormViewRendering(): void
    {
        echo "\n1. VIEW RENDERING & FIELD VERIFICATION AUDIT\n";

        $_SESSION['user'] = [
            'id' => 1,
            'role_id' => 1,
            'role_slug' => 'super-admin',
            'role_name' => 'Super Admin',
            'name' => 'System Admin',
            'branch_id' => 1,
        ];

        ob_start();
        $ctrl = new CustomerController();
        $ctrl->create();
        $html = ob_get_clean();

        $this->assert(str_contains($html, 'Visa Application Details'), "Form contains 'Visa Application Details' section");
        $this->assert(str_contains($html, 'custom_destination_country'), "Form contains manual Destination Country input");
        $this->assert(str_contains($html, 'custom_visa_category'), "Form contains manual Visa Category input");
        $this->assert(str_contains($html, 'custom_visa_type'), "Form contains manual Visa Type input");
        $this->assert(str_contains($html, 'custom_visa_duration'), "Form contains manual Visa Duration input");
        $this->assert(str_contains($html, 'custom_entry_type'), "Form contains manual Entry Type input");
        $this->assert(str_contains($html, 'custom_processing_type'), "Form contains manual Processing Type input");
        $this->assert(str_contains($html, 'Registration Payment (Optional)'), "Form contains optional Registration Payment section");
        $this->assert(str_contains($html, 'custPayLaterOpt'), "Form defaults to Pay Later without blocking applicant creation");
    }

    private function testPayLaterRegistration(): void
    {
        echo "\n2. SCENARIO 1: PAY LATER APPLICANT REGISTRATION FLOW\n";

        $_SESSION['user'] = [
            'id' => 1,
            'role_id' => 1,
            'role_slug' => 'super-admin',
            'role_name' => 'Super Admin',
            'name' => 'System Admin',
            'branch_id' => 1,
        ];

        $uniqueCode = 'TST_' . time() . '_' . rand(100, 999);
        $_POST = [
            'first_name' => 'PayLater',
            'last_name' => 'Applicant',
            'gender' => 'Male',
            'dob' => '1992-05-15',
            'nationality' => 'Sri Lankan',
            'mobile' => '+94771234567',
            'whatsapp' => '+94771234567',
            'email' => "paylater_{$uniqueCode}@example.com",
            'current_country' => 'United Arab Emirates',
            'passport_number' => "N{$uniqueCode}",
            'destination_country' => 'United Arab Emirates',
            'visa_category' => 'Tourist',
            'visa_type' => '30 Days Tourist Visa',
            'visa_duration' => '30 Days',
            'entry_type' => 'Single Entry',
            'processing_type' => 'Normal',
            'selling_price' => '250.00',
            'supplier_cost' => '150.00',
            'discount' => '0.00',
            'tax_amount' => '12.50',
            'pay_now' => '0', // Pay Later
        ];

        $_FILES = [
            'applicant_documents' => [
                'name' => [''],
                'type' => [''],
                'tmp_name' => [''],
                'error' => [UPLOAD_ERR_NO_FILE],
                'size' => [0]
            ]
        ];

        $ctrl = new CustomerController();
        $ctrl->store();

        // Verify Customer Created
        $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->execute(["paylater_{$uniqueCode}@example.com"]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assert(!empty($customer), "Pay Later customer created successfully in database (ID: " . ($customer['id'] ?? 0) . ")");

        // Verify Application Created with exact visa fields
        $appStmt = $this->pdo->prepare("SELECT * FROM applications WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
        $appStmt->execute([$customer['id'] ?? 0]);
        $application = $appStmt->fetch(PDO::FETCH_ASSOC);

        $this->assert(!empty($application), "Visa application created and linked to customer (App #: " . ($application['application_number'] ?? 'N/A') . ")");
        $this->assert(($application['payment_type'] ?? '') === 'Pay Later', "Application payment_type stored as 'Pay Later'");
        $this->assert(($application['payment_status'] ?? '') === 'Unpaid', "Application payment_status stored as 'Unpaid'");
        $this->assert((float)($application['balance_amount'] ?? 0) > 0, "Application balance remains outstanding (" . ($application['balance_amount'] ?? 0) . ")");

        // Verify Invoice Created
        $invStmt = $this->pdo->prepare("SELECT * FROM invoices WHERE application_id = ?");
        $invStmt->execute([$application['id'] ?? 0]);
        $invoice = $invStmt->fetch(PDO::FETCH_ASSOC);

        $this->assert(!empty($invoice) && ($invoice['status'] ?? '') === 'Unpaid', "Invoice created with Unpaid status");
    }

    private function testPayNowRegistrationWithWallet(): void
    {
        echo "\n3. SCENARIO 2: PAY NOW APPLICANT REGISTRATION FLOW WITH DIRECT SETTLEMENT\n";

        $_SESSION['user'] = [
            'id' => 1,
            'role_id' => 1,
            'role_slug' => 'super-admin',
            'role_name' => 'Super Admin',
            'name' => 'System Admin',
            'branch_id' => 1,
        ];

        $uniqueCode = 'WLT_' . time() . '_' . rand(100, 999);
        $_POST = [
            'first_name' => 'WalletPay',
            'last_name' => 'Applicant',
            'gender' => 'Female',
            'dob' => '1995-08-20',
            'nationality' => 'Indian',
            'mobile' => '+971509988776',
            'whatsapp' => '+971509988776',
            'email' => "walletpay_{$uniqueCode}@example.com",
            'current_country' => 'United Arab Emirates',
            'passport_number' => "P{$uniqueCode}",
            'destination_country' => 'Saudi Arabia',
            'visa_category' => 'Umrah / Visit',
            'visa_type' => '90 Days Multiple Entry Umrah Visa',
            'visa_duration' => '90 Days',
            'entry_type' => 'Multiple Entry',
            'processing_type' => 'Express',
            'selling_price' => '300.00',
            'supplier_cost' => '180.00',
            'discount' => '0.00',
            'tax_amount' => '15.00',
            'pay_now' => '1', // Pay Now
            'pay_method' => 'Cash',
            'pay_reference' => 'CASH-REC-001',
        ];

        $_FILES = [
            'applicant_documents' => [
                'name' => [''],
                'type' => [''],
                'tmp_name' => [''],
                'error' => [UPLOAD_ERR_NO_FILE],
                'size' => [0]
            ]
        ];

        $ctrl = new CustomerController();
        $ctrl->store();

        $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->execute(["walletpay_{$uniqueCode}@example.com"]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assert(!empty($customer), "Pay Now applicant profile registered");

        $appStmt = $this->pdo->prepare("SELECT * FROM applications WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
        $appStmt->execute([$customer['id'] ?? 0]);
        $application = $appStmt->fetch(PDO::FETCH_ASSOC);

        $this->assert(!empty($application), "Pay Now visa application generated");
        $this->assert(($application['payment_type'] ?? '') === 'Pay Now', "Application payment_type recorded as 'Pay Now'");
        $this->assert(($application['payment_status'] ?? '') === 'Paid', "Application payment_status recorded as 'Paid'");
        $this->assert((float)($application['balance_amount'] ?? 1) == 0.00, "Application balance_amount settled to 0.00");

        // Verify Payment record
        $payStmt = $this->pdo->prepare("SELECT * FROM payments WHERE application_id = ?");
        $payStmt->execute([$application['id'] ?? 0]);
        $payment = $payStmt->fetch(PDO::FETCH_ASSOC);

        $this->assert(!empty($payment) && ($payment['status'] ?? '') === 'Completed', "Payment transaction recorded with Completed status (Receipt: " . ($payment['payment_number'] ?? 'N/A') . ")");
    }

    private function testManualCategoryAndTypePersistence(): void
    {
        echo "\n4. SCENARIO 3 & 4: MANUAL VISA CATEGORY & MANUAL VISA TYPE PERSISTENCE\n";

        $_SESSION['user'] = [
            'id' => 1,
            'role_id' => 1,
            'role_slug' => 'super-admin',
            'role_name' => 'Super Admin',
            'name' => 'System Admin',
            'branch_id' => 1,
        ];

        $uniqueCode = 'MAN_' . time() . '_' . rand(100, 999);
        $customCat = "Special Golden Visa Category {$uniqueCode}";
        $customType = "10 Years Investor & Entrepreneur Multi-Pass {$uniqueCode}";
        $customCountry = "United Kingdom";

        $_POST = [
            'first_name' => 'CustomVisa',
            'last_name' => 'Tester',
            'gender' => 'Male',
            'dob' => '1988-11-10',
            'nationality' => 'British',
            'mobile' => '+447911123456',
            'whatsapp' => '+447911123456',
            'email' => "customvisa_{$uniqueCode}@example.com",
            'current_country' => 'United Kingdom',
            'passport_number' => "GB{$uniqueCode}",
            'custom_destination_country' => $customCountry,
            'custom_visa_category' => $customCat,
            'custom_visa_type' => $customType,
            'custom_visa_duration' => '10 Years',
            'custom_entry_type' => 'Multiple Entry',
            'custom_processing_type' => 'VIP Concierge Fast-Track',
            'selling_price' => '1500.00',
            'supplier_cost' => '900.00',
            'discount' => '0.00',
            'tax_amount' => '75.00',
            'pay_now' => '0',
        ];

        $_FILES = [
            'applicant_documents' => [
                'name' => [''],
                'type' => [''],
                'tmp_name' => [''],
                'error' => [UPLOAD_ERR_NO_FILE],
                'size' => [0]
            ]
        ];

        $ctrl = new CustomerController();
        $ctrl->store();

        $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->execute(["customvisa_{$uniqueCode}@example.com"]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        $appStmt = $this->pdo->prepare("SELECT * FROM applications WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
        $appStmt->execute([$customer['id'] ?? 0]);
        $application = $appStmt->fetch(PDO::FETCH_ASSOC);

        $this->assert(($application['destination_country'] ?? '') === $customCountry, "Manual Destination Country accurately stored: " . ($application['destination_country'] ?? 'N/A'));
        $this->assert(($application['visa_category'] ?? '') === $customCat, "Manual Visa Category accurately stored: " . ($application['visa_category'] ?? 'N/A'));
        $this->assert(($application['visa_type'] ?? '') === $customType, "Manual Visa Type accurately stored: " . ($application['visa_type'] ?? 'N/A'));
    }

    private function testManualDurationEntryAndProcessingPersistence(): void
    {
        echo "\n5. SCENARIO 5: MANUAL DURATION, ENTRY TYPE & PROCESSING TYPE PERSISTENCE\n";

        $_SESSION['user'] = [
            'id' => 1,
            'role_id' => 1,
            'role_slug' => 'super-admin',
            'role_name' => 'Super Admin',
            'name' => 'System Admin',
            'branch_id' => 1,
        ];

        $uniqueCode = 'DUR_' . time() . '_' . rand(100, 999);
        $customDuration = "45 Days Flexible";
        $customEntry = "Triple Entry Port-Pass";
        $customProcessing = "Same-Day Embassy Express";

        $_POST = [
            'first_name' => 'ManualDuration',
            'last_name' => 'Applicant',
            'gender' => 'Male',
            'dob' => '1990-03-25',
            'nationality' => 'Sri Lankan',
            'mobile' => '+94718889900',
            'whatsapp' => '+94718889900',
            'email' => "duration_{$uniqueCode}@example.com",
            'current_country' => 'Sri Lanka',
            'passport_number' => "LK{$uniqueCode}",
            'destination_country' => 'Qatar',
            'visa_category' => 'Business',
            'visa_type' => 'Business Visit Visa',
            'custom_visa_duration' => $customDuration,
            'custom_entry_type' => $customEntry,
            'custom_processing_type' => $customProcessing,
            'selling_price' => '400.00',
            'pay_now' => '0',
        ];

        $_FILES = [
            'applicant_documents' => [
                'name' => [''],
                'type' => [''],
                'tmp_name' => [''],
                'error' => [UPLOAD_ERR_NO_FILE],
                'size' => [0]
            ]
        ];

        $ctrl = new CustomerController();
        $ctrl->store();

        $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->execute(["duration_{$uniqueCode}@example.com"]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        $appStmt = $this->pdo->prepare("SELECT * FROM applications WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
        $appStmt->execute([$customer['id'] ?? 0]);
        $application = $appStmt->fetch(PDO::FETCH_ASSOC);

        $this->assert(($application['visa_duration'] ?? '') === $customDuration, "Manual Visa Duration accurately saved: " . ($application['visa_duration'] ?? 'N/A'));
        $this->assert(($application['entry_type'] ?? '') === $customEntry, "Manual Entry Type accurately saved: " . ($application['entry_type'] ?? 'N/A'));
        $this->assert(($application['processing_type'] ?? '') === $customProcessing, "Manual Processing Type accurately saved: " . ($application['processing_type'] ?? 'N/A'));
    }
}

$test = new NewApplicantFormTest();
$test->run();
