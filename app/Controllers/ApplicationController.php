<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\App;
use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Services\AuditService;
use App\Services\DocumentChecklistService;
use App\Services\DuplicateDetectionService;
use App\Services\FinanceService;
use App\Services\HealthCalculatorService;
use App\Services\PriorityEngineService;
use App\Services\StaffAssignmentService;
use App\Services\StageTransitionService;
use PDO;
use Exception;

class ApplicationController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        // Preset / Saved Filter
        $preset = trim($_GET['preset'] ?? '');

        // Query Filters
        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $stage = trim($_GET['stage'] ?? '');
        $priority = trim($_GET['priority'] ?? '');
        $countryId = (int)($_GET['country_id'] ?? 0);
        $serviceId = (int)($_GET['service_id'] ?? 0);
        $staffId = (int)($_GET['staff_id'] ?? 0);
        $branchId = get_scoped_branch_id((int)($_GET['branch_id'] ?? 0), $user);

        // Apply Saved Presets
        if ($preset === 'my_assigned' && $user) {
            $staffId = (int)$user['id'];
        } elseif ($preset === 'critical') {
            $priority = 'Critical';
        } elseif ($preset === 'pending_docs') {
            $status = 'Documents Pending';
        } elseif ($preset === 'action_required') {
            $status = 'Action Required';
        }

        $sql = "SELECT a.*, c.full_name as customer_name, c.customer_code, c.mobile as customer_mobile, c.email as customer_email,
                    c.nationality as customer_nationality,
                    vs.name as service_name, vs.entry_type, vs.processing_type,
                    ct.name as country_name, ct.flag_emoji,
                    u.name as staff_name, b.name as branch_name,
                    cp.passport_number as current_passport
                FROM applications a
                LEFT JOIN customers c ON a.customer_id = c.id
                LEFT JOIN visa_services vs ON a.visa_service_id = vs.id
                LEFT JOIN countries ct ON vs.country_id = ct.id
                LEFT JOIN customer_passports cp ON c.id = cp.customer_id AND cp.is_primary = 1
                LEFT JOIN users u ON a.assigned_staff_id = u.id
                LEFT JOIN branches b ON a.branch_id = b.id
                WHERE a.is_archived = 0";

        $params = [];

        if ($search !== '') {
            $sql .= " AND (a.application_number LIKE ? OR c.full_name LIKE ? OR c.customer_code LIKE ? OR a.passport_number LIKE ? OR cp.passport_number LIKE ? OR a.visa_number LIKE ? OR c.email LIKE ? OR c.mobile LIKE ?)";
            $term = "%{$search}%";
            $params = array_merge($params, [$term, $term, $term, $term, $term, $term, $term, $term]);
        }

        if ($status !== '') {
            $sql .= " AND a.status = ?";
            $params[] = $status;
        }

        if ($stage !== '') {
            $sql .= " AND a.current_stage = ?";
            $params[] = $stage;
        }

        if ($priority !== '') {
            if ($preset === 'critical') {
                $sql .= " AND a.priority IN ('Critical', 'Urgent', 'High')";
            } else {
                $sql .= " AND a.priority = ?";
                $params[] = $priority;
            }
        }

        $today = date('Y-m-d');
        if ($preset === 'overdue') {
            $sql .= " AND a.expected_completion_date IS NOT NULL AND a.expected_completion_date < '{$today}' AND a.status NOT IN ('Approved', 'Completed', 'Rejected', 'Cancelled')";
        }

        if ($countryId > 0) {
            $sql .= " AND ct.id = ?";
            $params[] = $countryId;
        }

        if ($serviceId > 0) {
            $sql .= " AND vs.id = ?";
            $params[] = $serviceId;
        }

        if ($staffId > 0) {
            $sql .= " AND a.assigned_staff_id = ?";
            $params[] = $staffId;
        }

        if ($branchId > 0) {
            $sql .= " AND a.branch_id = ?";
            $params[] = $branchId;
        }

        $sql .= " ORDER BY CASE WHEN a.priority = 'Critical' THEN 1 WHEN a.priority = 'Urgent' THEN 2 WHEN a.priority = 'High' THEN 3 ELSE 4 END, a.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Filter Meta Options
        $countries = $pdo->query("SELECT id, name, flag_emoji FROM countries WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $services = $pdo->query("SELECT id, name, country_id FROM visa_services WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $staffMembers = $pdo->query("SELECT id, name FROM users WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $branches = $pdo->query("SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Filter Counts for Badges
        $counts = [
            'all' => (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE is_archived = 0")->fetchColumn(),
            'my_assigned' => $user ? (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE assigned_staff_id = " . (int)$user['id'] . " AND is_archived = 0")->fetchColumn() : 0,
            'critical' => (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE priority IN ('Critical', 'Urgent', 'High') AND status NOT IN ('Approved', 'Completed') AND is_archived = 0")->fetchColumn(),
            'pending_docs' => (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'Documents Pending' AND is_archived = 0")->fetchColumn(),
            'action_required' => (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'Action Required' AND is_archived = 0")->fetchColumn(),
            'overdue' => (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE expected_completion_date IS NOT NULL AND expected_completion_date < '{$today}' AND status NOT IN ('Approved', 'Completed') AND is_archived = 0")->fetchColumn(),
        ];

        require_once dirname(__DIR__) . '/Views/applications/index.php';
    }

    public function create(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $customers = $pdo->query("SELECT c.*, cp.passport_number FROM customers c LEFT JOIN customer_passports cp ON c.id = cp.customer_id AND cp.is_primary = 1 WHERE c.is_active = 1 ORDER BY c.full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $countries = $pdo->query("SELECT id, name, iso_code, flag_emoji FROM countries WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $categories = $pdo->query("SELECT id, name FROM visa_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $services = $pdo->query("SELECT vs.*, ct.name as country_name, ct.flag_emoji, vc.name as category_name 
            FROM visa_services vs 
            JOIN countries ct ON vs.country_id = ct.id 
            LEFT JOIN visa_categories vc ON vs.category_id = vc.id 
            WHERE vs.is_active = 1 
            ORDER BY ct.name ASC, vs.name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $staffMembers = $pdo->query("SELECT id, name, designation FROM users WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $branches = $pdo->query("SELECT id, name, city FROM branches WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $suppliers = $pdo->query("SELECT id, company_name, supplier_code FROM suppliers WHERE is_active = 1 ORDER BY company_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $docTypes = $pdo->query("SELECT id, name, code, category FROM document_types WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        require_once dirname(__DIR__) . '/Views/applications/create.php';
    }

    public function store(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        $customerId = (int)($_POST['customer_id'] ?? 0);
        $serviceId = (int)($_POST['visa_service_id'] ?? 0);
        $branchId = get_scoped_branch_id(!empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : 0, $user) ?: 1;
        $assignedStaffId = !empty($_POST['assigned_staff_id']) ? (int)$_POST['assigned_staff_id'] : ($user['id'] ?? null);
        $supplierId = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
        $priority = in_array($_POST['priority'] ?? '', ['Critical', 'Urgent', 'High', 'Normal'], true) ? $_POST['priority'] : 'Normal';
        $travelDate = !empty($_POST['travel_date']) ? $_POST['travel_date'] : null;
        $returnDate = !empty($_POST['return_date']) ? $_POST['return_date'] : null;
        $internalNotes = trim($_POST['internal_notes'] ?? '');
        $customerNotes = trim($_POST['customer_notes'] ?? '');

        $customDestCountry = trim($_POST['custom_destination_country'] ?? '');
        $customVisaType = trim($_POST['custom_visa_type'] ?? '');
        $customVisaCat = trim($_POST['custom_visa_category'] ?? '');

        $firstName = trim($_POST['first_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $rawFullName = trim($_POST['full_name'] ?? '');
        $passportNumber = strtoupper(trim($_POST['passport_number'] ?? ''));
        $nationality = trim($_POST['nationality'] ?? $_POST['present_nationality'] ?? '');
        $dob = !empty($_POST['dob']) ? $_POST['dob'] : (!empty($_POST['birth_date']) ? $_POST['birth_date'] : null);
        $gender = trim($_POST['gender'] ?? 'Male');
        $birthCountry = trim($_POST['birth_country'] ?? '');
        $placeOfBirth = trim($_POST['place_of_birth'] ?? $_POST['birth_place'] ?? '');
        $maritalStatus = trim($_POST['marital_status'] ?? 'Single');
        $mobile = trim($_POST['mobile'] ?? $_POST['applicant_mobile'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? $mobile);
        $email = trim($_POST['email'] ?? '');
        $residingCountry = trim($_POST['residing_country'] ?? $_POST['current_country'] ?? 'United Arab Emirates');
        $city = trim($_POST['city'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $profession = trim($_POST['profession'] ?? $_POST['occupation'] ?? '');
        $education = trim($_POST['education'] ?? '');
        $language = trim($_POST['language'] ?? 'English');

        $passportIssueDate = !empty($_POST['passport_issue_date']) ? $_POST['passport_issue_date'] : (!empty($_POST['date_of_issue']) ? $_POST['date_of_issue'] : null);
        $passportExpiryDate = !empty($_POST['passport_expiry_date']) ? $_POST['passport_expiry_date'] : (!empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null);
        $passportIssuingCountry = trim($_POST['passport_issuing_country'] ?? $nationality);
        $passportPlaceOfIssue = trim($_POST['passport_place_of_issue'] ?? $placeOfBirth);

        $fatherName = trim($_POST['father_name'] ?? '');
        $motherName = trim($_POST['mother_name'] ?? '');
        $spouseName = trim($_POST['spouse_name'] ?? $_POST['husband_name'] ?? '');

        $sourceType = trim($_POST['source_type'] ?? 'Normal');
        $visitReason = trim($_POST['visit_reason'] ?? 'Tourism');

        if ($customerId <= 0) {
            // Check if customer exists by passport number
            if (!empty($passportNumber)) {
                $chkP = $pdo->prepare("SELECT customer_id FROM customer_passports WHERE UPPER(passport_number) = ? LIMIT 1");
                $chkP->execute([$passportNumber]);
                $existingCustId = (int)$chkP->fetchColumn();
                if ($existingCustId > 0) {
                    $customerId = $existingCustId;
                }
            }
        }

        if ($customerId <= 0) {
            $computedName = trim($firstName . ($middleName ? ' ' . $middleName : '') . ' ' . $lastName);
            $fullName = !empty($computedName) ? $computedName : $rawFullName;

            if (!empty($fullName) || !empty($passportNumber)) {
                $cCount = (int)$pdo->query("SELECT COALESCE(MAX(id), 0) FROM customers")->fetchColumn() + 1;
                $customerCode = sprintf("CUST-%s-%05d", date('Y'), $cCount);
                if (empty($fullName)) {
                    $fullName = "Applicant {$customerCode}";
                }
                if (empty($lastName)) {
                    $lastName = $fullName;
                }

                $insCust = $pdo->prepare("INSERT INTO customers (
                    customer_code, full_name, first_name, middle_name, last_name,
                    gender, dob, nationality, birth_country, place_of_birth, city,
                    current_country, address, mobile, whatsapp, email,
                    occupation, education, language, marital_status,
                    created_by, is_active, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, 1, CURRENT_TIMESTAMP
                )");
                $insCust->execute([
                    $customerCode, $fullName, $firstName, $middleName, $lastName,
                    $gender, $dob, $nationality, $birthCountry, $placeOfBirth, $city,
                    $residingCountry, $address, $mobile, $whatsapp, $email,
                    $profession, $education, $language, $maritalStatus,
                    $user['id'] ?? null
                ]);
                $customerId = (int)$pdo->lastInsertId();
            }
        }

        if ($customerId <= 0) {
            redirect('/applications/create', 'Please select a registered applicant or fill in the applicant passport details.', 'danger');
        }

        // Synchronize / update passport record
        if (!empty($passportNumber)) {
            $chkPass = $pdo->prepare("SELECT id FROM customer_passports WHERE customer_id = ? AND UPPER(passport_number) = ?");
            $chkPass->execute([$customerId, $passportNumber]);
            $passRecId = (int)$chkPass->fetchColumn();
            if ($passRecId <= 0) {
                $insPass = $pdo->prepare("INSERT INTO customer_passports (
                    customer_id, passport_number, issuing_country, issue_date, expiry_date,
                    place_of_issue, is_primary, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP)");
                $insPass->execute([
                    $customerId, $passportNumber, $passportIssuingCountry, $passportIssueDate, $passportExpiryDate,
                    $passportPlaceOfIssue
                ]);
            } else {
                $upPass = $pdo->prepare("UPDATE customer_passports SET 
                    issuing_country = COALESCE(NULLIF(?, ''), issuing_country),
                    issue_date = COALESCE(?, issue_date),
                    expiry_date = COALESCE(?, expiry_date),
                    place_of_issue = COALESCE(NULLIF(?, ''), place_of_issue),
                    is_primary = 1
                    WHERE id = ?");
                $upPass->execute([$passportIssuingCountry, $passportIssueDate, $passportExpiryDate, $passportPlaceOfIssue, $passRecId]);
            }
        }

        // Synchronize / update family record
        if (!empty($fatherName) || !empty($motherName) || !empty($spouseName)) {
            $chkFam = $pdo->prepare("SELECT id FROM customer_family WHERE customer_id = ?");
            $chkFam->execute([$customerId]);
            $famId = (int)$chkFam->fetchColumn();
            if ($famId <= 0) {
                $insFam = $pdo->prepare("INSERT INTO customer_family (customer_id, father_name, mother_name, spouse_name, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
                $insFam->execute([$customerId, $fatherName, $motherName, $spouseName]);
            } else {
                $upFam = $pdo->prepare("UPDATE customer_family SET 
                    father_name = COALESCE(NULLIF(?, ''), father_name),
                    mother_name = COALESCE(NULLIF(?, ''), mother_name),
                    spouse_name = COALESCE(NULLIF(?, ''), spouse_name)
                    WHERE id = ?");
                $upFam->execute([$fatherName, $motherName, $spouseName, $famId]);
            }
        }

        // Update customer profile fields if previously missing
        $upCust = $pdo->prepare("UPDATE customers SET
            birth_country = COALESCE(NULLIF(?, ''), birth_country),
            city = COALESCE(NULLIF(?, ''), city),
            education = COALESCE(NULLIF(?, ''), education),
            language = COALESCE(NULLIF(?, ''), language),
            occupation = COALESCE(NULLIF(?, ''), occupation)
            WHERE id = ?");
        $upCust->execute([$birthCountry, $city, $education, $language, $profession, $customerId]);

        if ($serviceId <= 0 && empty($customDestCountry) && empty($customVisaType)) {
            redirect('/applications/create', 'Please select both an applicant and a visa service package (or enter custom package details).', 'danger');
        }

        // Fetch customer and service details
        $custStmt = $pdo->prepare("SELECT c.*, cp.passport_number, cp.expiry_date as passport_expiry 
            FROM customers c 
            LEFT JOIN customer_passports cp ON c.id = cp.customer_id AND cp.is_primary = 1 
            WHERE c.id = ?");
        $custStmt->execute([$customerId]);
        $customer = $custStmt->fetch(PDO::FETCH_ASSOC);

        $service = null;
        if ($serviceId > 0) {
            $srvStmt = $pdo->prepare("SELECT vs.*, ct.name as country_name, ct.iso_code as country_code FROM visa_services vs JOIN countries ct ON vs.country_id = ct.id WHERE vs.id = ?");
            $srvStmt->execute([$serviceId]);
            $service = $srvStmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$service) {
            // Find a valid fallback service from database to satisfy foreign keys
            $fb = $pdo->query("SELECT vs.*, ct.name as country_name, ct.iso_code as country_code FROM visa_services vs JOIN countries ct ON vs.country_id = ct.id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            $customSellingPrice = (float)($_POST['custom_selling_price'] ?? $_POST['selling_price'] ?? 0);
            $customSupplierCost = (float)($_POST['custom_supplier_cost'] ?? $_POST['supplier_cost'] ?? 0);
            $customDays = (int)($_POST['custom_estimated_days'] ?? 15);
            $service = $fb ?: [
                'id' => 1,
                'country_id' => 1,
                'category_id' => 1,
                'name' => $customVisaType ?: 'Custom Visa Service',
                'country_name' => $customDestCountry ?: 'United Arab Emirates',
                'country_code' => 'AE',
                'selling_price' => $customSellingPrice,
                'supplier_cost' => $customSupplierCost,
                'tax_rate' => 0,
                'estimated_days' => $customDays > 0 ? $customDays : 15,
                'duration' => '30 Days',
                'entry_type' => 'Single Entry',
                'processing_type' => 'Normal'
            ];
            $serviceId = (int)$service['id'];
        }

        if (!$customer) {
            redirect('/applications/create', 'Invalid applicant selected.', 'danger');
        }

        // Calculate expected completion date from service processing days
        $procDays = (int)($service['estimated_days'] ?? 15);
        $appDate = date('Y-m-d');
        $expectedCompletionDate = date('Y-m-d', strtotime("+{$procDays} days"));

        // Generate Sequential Application Number (MSV-YYYY-XXXXXX) with concurrency lock
        $year = date('Y');
        $maxNum = (int)$pdo->query("SELECT COALESCE(MAX(id), 0) FROM applications")->fetchColumn() + 1;
        $appNumber = sprintf("MSV-%s-%06d", $year, $maxNum);

        $checkStmt = $pdo->prepare("SELECT id FROM applications WHERE application_number = ?");
        $checkStmt->execute([$appNumber]);
        while ($checkStmt->fetch()) {
            $maxNum++;
            $appNumber = sprintf("MSV-%s-%06d", $year, $maxNum);
            $checkStmt->execute([$appNumber]);
        }

        // Apply Rule Engine for dynamic pricing overrides by nationality/residence
        $ruleResult = \App\Services\VisaRuleEngineService::resolve(
            (int)$service['country_id'],
            (int)$service['id'],
            $customer['nationality'] ?? 'Unknown',
            $customer['current_country'] ?? 'United Arab Emirates'
        );

        if (!$ruleResult['is_eligible'] && empty($_POST['confirm_ineligible'])) {
            redirect('/applications/create', 'Eligibility Warning: ' . ($ruleResult['reason'] ?? 'Nationality/residence restriction for this visa service.') . ' To proceed with supervisor authorization, please confirm the waiver.', 'warning');
        }

        $sellingPrice = (float)($ruleResult['selling_price'] ?? $service['selling_price'] ?? 0.0);
        $supplierCost = (float)($ruleResult['supplier_cost'] ?? $service['supplier_cost'] ?? 0.0);
        if (!empty($customVisaType) || (!empty($_POST['custom_selling_price']) && (float)$_POST['custom_selling_price'] > 0)) {
            $sellingPrice = (float)($_POST['custom_selling_price'] ?? $sellingPrice);
            $supplierCost = (float)($_POST['custom_supplier_cost'] ?? $supplierCost);
        }
        $discount = max(0.0, (float)($_POST['discount'] ?? 0.0));
        if ($discount > $sellingPrice) {
            $discount = $sellingPrice;
        }
        $otherExpenses = (float)($_POST['other_expenses'] ?? 0.0);
        $supplierRef = trim($_POST['supplier_reference'] ?? '');
        $embassyRef = trim($_POST['embassy_reference'] ?? '');

        // Authoritative backend pricing calculation — client cannot tamper with total_amount
        $taxRate = (float)($service['tax_rate'] ?? 0.0);
        $netSellingPrice = max(0.0, $sellingPrice - $discount);
        $taxAmount = round($netSellingPrice * ($taxRate / 100.0), 2);
        $totalAmount = round($netSellingPrice + $taxAmount, 2);
        // Do not clamp profit to 0.0: Accurately record real negative profit/loss
        $grossProfit = round($totalAmount - $supplierCost - $otherExpenses, 2);

        $countryName = trim($_POST['custom_destination_country'] ?? '') ?: ($service['country_name'] ?? '');
        $categoryName = trim($_POST['custom_visa_category'] ?? '') ?: ($service['category_name'] ?? 'General');
        $visaTypeName = trim($_POST['custom_visa_type'] ?? '') ?: ($service['name'] ?? 'Standard Visa');
        $duration = trim($_POST['custom_visa_duration'] ?? '') ?: trim($_POST['visa_duration'] ?? ($service['duration'] ?? '30 Days'));
        $entryType = trim($_POST['custom_entry_type'] ?? '') ?: trim($_POST['entry_type'] ?? ($service['entry_type'] ?? 'Single Entry'));
        $processingType = trim($_POST['custom_processing_type'] ?? '') ?: trim($_POST['processing_type'] ?? ($service['processing_type'] ?? 'Normal'));
        $isPayNow = !empty($_POST['pay_now']) && ((string)$_POST['pay_now'] === '1' || (string)$_POST['pay_now'] === 'on');
        $paymentType = $isPayNow ? 'Pay Now' : 'Pay Later';
        $paymentStatus = 'Unpaid';

        $pdo->beginTransaction();
        try {
            $insSql = "INSERT INTO applications (
                application_number, customer_id, visa_service_id, branch_id, assigned_staff_id, supplier_id,
                current_stage, status, priority, calculated_health, health_reason,
                nationality, residence_country, passport_number, passport_expiry_date,
                destination_country, visa_category, visa_type, visa_duration, entry_type, processing_type,
                application_date, expected_completion_date, travel_date, return_date,
                selling_price, discount, tax_amount, total_amount, paid_amount, balance_amount,
                supplier_cost, other_expenses, gross_profit, supplier_reference, embassy_reference,
                internal_notes, customer_notes, next_action, next_action_due_date, payment_type, payment_status, created_by,
                source_type, visit_reason
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                'New Application', 'Draft', ?, 100, 'Application freshly initiated. Document checklist initialized.',
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, 0.00, ?,
                ?, ?, ?, ?, ?,
                ?, ?, 'Collect and verify initial required documents', ?, ?, ?, ?,
                ?, ?
            )";

            $nextActionDue = date('Y-m-d', strtotime('+3 days'));

            $insStmt = $pdo->prepare($insSql);
            $insStmt->execute([
                $appNumber, $customerId, $serviceId, $branchId, $assignedStaffId, $supplierId,
                $priority,
                $customer['nationality'] ?? 'Unknown', $customer['current_country'] ?? 'United Arab Emirates', $customer['passport_number'] ?? '', $customer['passport_expiry'] ?? null,
                $countryName, $categoryName, $visaTypeName, $duration, $entryType, $processingType,
                $appDate, $expectedCompletionDate, $travelDate, $returnDate,
                $sellingPrice, $discount, $taxAmount, $totalAmount, $totalAmount,
                $supplierCost, $otherExpenses, $grossProfit, $supplierRef, $embassyRef,
                $internalNotes, $customerNotes, $nextActionDue, $paymentType, $paymentStatus, $user['id'] ?? null,
                $sourceType, $visitReason
            ]);

            $appId = (int)$pdo->lastInsertId();

            // Insert initial immutable stage history record
            $histStmt = $pdo->prepare("INSERT INTO application_status_history (
                application_id, from_stage, to_stage, from_status, to_status, comments, changed_by
            ) VALUES (?, 'Initiation', 'Application Registered', 'Draft', 'Registered', 'Visa application file created and registered in operations tracking system.', ?)");
            $histStmt->execute([$appId, $user['id'] ?? null]);

            // If staff assigned, record initial assignment record
            if ($assignedStaffId) {
                try {
                    $assignedBy = !empty($user['id']) ? (int)$user['id'] : 1;
                    // Self-heal table / column if missing in SQLite/MySQL
                    try {
                        $pdo->query("SELECT is_current FROM application_assignments LIMIT 1");
                    } catch (\Throwable $colEx) {
                        try {
                            $pdo->exec("CREATE TABLE IF NOT EXISTS application_assignments (
                                id INTEGER PRIMARY KEY AUTOINCREMENT,
                                application_id INTEGER NOT NULL,
                                staff_id INTEGER NULL,
                                assigned_to INTEGER NULL,
                                assigned_by INTEGER NULL,
                                assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                unassigned_at DATETIME NULL,
                                is_current INTEGER DEFAULT 1,
                                notes TEXT NULL,
                                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                            )");
                            $pdo->exec("ALTER TABLE application_assignments ADD COLUMN is_current INTEGER DEFAULT 1");
                        } catch (\Throwable $ign) {}
                    }
                    $assignStmt = $pdo->prepare("INSERT INTO application_assignments (
                        application_id, staff_id, assigned_to, assigned_by, assigned_at, notes, is_current
                    ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, 'Initial case worker assignment upon registration', 1)");
                    $assignStmt->execute([$appId, $assignedStaffId, $assignedStaffId, $assignedBy]);
                } catch (\Throwable $assignErr) {
                    error_log("[VISA-TRACK] Non-fatal assignment logging notice: " . $assignErr->getMessage());
                }
            }

            // Auto-generate document checklist matrix from visa requirements
            DocumentChecklistService::generateForApplication($appId, $serviceId);

            // Document storage directory
            $docsUploadDir = dirname(__DIR__, 2) . '/public/uploads/documents/';
            if (!is_dir($docsUploadDir)) {
                @mkdir($docsUploadDir, 0777, true);
            }

            // Attach Passport Document from fast-path OCR scan if token provided
            $tempPassToken = trim($_POST['temp_passport_token'] ?? '');
            if (!empty($tempPassToken)) {
                $tempDir = dirname(__DIR__, 2) . '/public/uploads/temp_ocr/';
                $srcTemp = null;

                if (!empty($_SESSION['temp_ocr_passports'][$tempPassToken]['file_path']) && file_exists($_SESSION['temp_ocr_passports'][$tempPassToken]['file_path'])) {
                    $srcTemp = $_SESSION['temp_ocr_passports'][$tempPassToken]['file_path'];
                } elseif (file_exists($tempDir . $tempPassToken)) {
                    $srcTemp = $tempDir . $tempPassToken;
                } else {
                    $tempMatches = glob($tempDir . 'temp_passport_' . $tempPassToken . '.*');
                    if (!empty($tempMatches) && file_exists($tempMatches[0])) {
                        $srcTemp = $tempMatches[0];
                    } else {
                        $anyMatches = glob($tempDir . '*' . $tempPassToken . '*');
                        if (!empty($anyMatches) && file_exists($anyMatches[0])) {
                            $srcTemp = $anyMatches[0];
                        }
                    }
                }

                if ($srcTemp && file_exists($srcTemp)) {
                    $ext = strtolower(pathinfo($srcTemp, PATHINFO_EXTENSION)) ?: 'jpg';
                    $savedName = "appdoc_{$appId}_passport_" . time() . '.' . $ext;
                    $targetPath = $docsUploadDir . $savedName;

                    if (copy($srcTemp, $targetPath)) {
                        $fSize = filesize($targetPath);
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mType = finfo_file($finfo, $targetPath) ?: 'image/jpeg';
                        if (PHP_VERSION_ID < 80500) { @finfo_close($finfo); }

                        $ocrConf = (float)($_POST['ocr_confidence'] ?? 95.0);
                        $ocrProv = trim($_POST['ocr_provider'] ?? 'PassportOcrService');
                        $origName = trim($_POST['passport_original_filename'] ?? ('Passport_' . ($passportNumber ?: 'Scan') . '.' . $ext));

                        $docStmt = $pdo->prepare("INSERT INTO documents (
                            application_id, customer_id, document_type_id, document_title,
                            file_path, file_name, file_size, mime_type, version, status,
                            ocr_status, ocr_confidence, ocr_provider,
                            uploaded_by_type, uploaded_by_id, created_at
                        ) VALUES (
                            ?, ?, 1, 'Passport Bio Page',
                            ?, ?, ?, ?, 1, 'VERIFIED',
                            'COMPLETED', ?, ?,
                            'Staff', ?, CURRENT_TIMESTAMP
                        )");
                        $docStmt->execute([
                            $appId, $customerId,
                            "/uploads/documents/{$savedName}", $origName, $fSize, $mType,
                            $ocrConf, $ocrProv, $user['id'] ?? null
                        ]);

                        // Synchronize checklist status
                        try {
                            $upChecklist = $pdo->prepare("UPDATE document_checklists SET status = 'VERIFIED', verified_at = CURRENT_TIMESTAMP WHERE application_id = ? AND document_type_id = 1");
                            $upChecklist->execute([$appId]);
                        } catch (\Throwable $ign) {}

                        AuditService::log('DOCUMENT_UPLOADED', 'documents', (int)$pdo->lastInsertId(), "Passport bio page auto-attached from OCR scan for Application {$appNumber}");
                    }
                }
            } elseif (!empty($_FILES['passport_scan']['name']) && $_FILES['passport_scan']['error'] === UPLOAD_ERR_OK) {
                // Direct passport file upload fallback
                $tmpName = $_FILES['passport_scan']['tmp_name'];
                $origName = $_FILES['passport_scan']['name'];
                $fileSize = (int)$_FILES['passport_scan']['size'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $tmpName) ?: 'image/jpeg';
                if (PHP_VERSION_ID < 80500) { @finfo_close($finfo); }
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION)) ?: 'jpg';
                $savedName = "appdoc_{$appId}_passport_" . time() . '.' . $ext;
                $targetPath = $docsUploadDir . $savedName;

                $copied = is_uploaded_file($tmpName) ? move_uploaded_file($tmpName, $targetPath) : copy($tmpName, $targetPath);
                if ($copied) {
                    $docStmt = $pdo->prepare("INSERT INTO documents (
                        application_id, customer_id, document_type_id, document_title,
                        file_path, file_name, file_size, mime_type, version, status,
                        uploaded_by_type, uploaded_by_id, created_at
                    ) VALUES (
                        ?, ?, 1, 'Passport Bio Page',
                        ?, ?, ?, ?, 1, 'VERIFIED',
                        'Staff', ?, CURRENT_TIMESTAMP
                    )");
                    $docStmt->execute([
                        $appId, $customerId,
                        "/uploads/documents/{$savedName}", $origName, $fileSize, $mimeType,
                        $user['id'] ?? null
                    ]);
                    try {
                        $upChecklist = $pdo->prepare("UPDATE document_checklists SET status = 'VERIFIED', verified_at = CURRENT_TIMESTAMP WHERE application_id = ? AND document_type_id = 1");
                        $upChecklist->execute([$appId]);
                    } catch (\Throwable $ign) {}
                    AuditService::log('DOCUMENT_UPLOADED', 'documents', (int)$pdo->lastInsertId(), "Passport bio page attached for Application {$appNumber}");
                }
            }

            // Attach Applicant Photo if uploaded (PHOTO_WHITE_BG, document_type_id = 3)
            if (!empty($_FILES['applicant_photo']['name']) && $_FILES['applicant_photo']['error'] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['applicant_photo']['tmp_name'];
                $origName = $_FILES['applicant_photo']['name'];
                $fileSize = (int)$_FILES['applicant_photo']['size'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $tmpName) ?: 'image/jpeg';
                if (PHP_VERSION_ID < 80500) { @finfo_close($finfo); }
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION)) ?: 'jpg';
                $savedName = "appdoc_{$appId}_photo_" . time() . '.' . $ext;
                $targetPath = $docsUploadDir . $savedName;

                $copied = is_uploaded_file($tmpName) ? move_uploaded_file($tmpName, $targetPath) : copy($tmpName, $targetPath);
                if ($copied) {
                    $docStmt = $pdo->prepare("INSERT INTO documents (
                        application_id, customer_id, document_type_id, document_title,
                        file_path, file_name, file_size, mime_type, version, status,
                        uploaded_by_type, uploaded_by_id, created_at
                    ) VALUES (
                        ?, ?, 3, 'Applicant Photo (White Background)',
                        ?, ?, ?, ?, 1, 'VERIFIED',
                        'Staff', ?, CURRENT_TIMESTAMP
                    )");
                    $docStmt->execute([
                        $appId, $customerId,
                        "/uploads/documents/{$savedName}", $origName, $fileSize, $mimeType,
                        $user['id'] ?? null
                    ]);
                    try {
                        $upChecklist = $pdo->prepare("UPDATE document_checklists SET status = 'VERIFIED', verified_at = CURRENT_TIMESTAMP WHERE application_id = ? AND document_type_id = 3");
                        $upChecklist->execute([$appId]);
                    } catch (\Throwable $ign) {}
                    AuditService::log('DOCUMENT_UPLOADED', 'documents', (int)$pdo->lastInsertId(), "Applicant photo attached for Application {$appNumber}");
                }
            }

            // Process Multiple Uploaded Documents (Part 2, 3, 4)
            if (!empty($_FILES['application_documents']['name']) && is_array($_FILES['application_documents']['name'])) {
                $docTypes = $_POST['document_types'] ?? [];
                $docTitles = $_POST['document_titles'] ?? [];

                $docStmt = $pdo->prepare("INSERT INTO documents (
                    application_id, customer_id, document_type_id, document_title, file_path, file_name, file_size, mime_type, version, status, uploaded_by_type, uploaded_by_id, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'VERIFIED', 'Staff', ?, CURRENT_TIMESTAMP)");

                foreach ($_FILES['application_documents']['name'] as $idx => $origName) {
                    if (!empty($origName) && $_FILES['application_documents']['error'][$idx] === UPLOAD_ERR_OK) {
                        $tmpName = $_FILES['application_documents']['tmp_name'][$idx];
                        $fileSize = (int)$_FILES['application_documents']['size'][$idx];
                        $mimeType = $_FILES['application_documents']['type'][$idx] ?? 'application/octet-stream';
                        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION)) ?: 'pdf';
                        $savedName = "appdoc_{$appId}_" . time() . "_{$idx}." . $ext;
                        $targetPath = $docsUploadDir . $savedName;

                        $copied = is_uploaded_file($tmpName) ? move_uploaded_file($tmpName, $targetPath) : copy($tmpName, $targetPath);
                        if ($copied) {
                            $typeId = !empty($docTypes[$idx]) ? (int)$docTypes[$idx] : 2;
                            if ($typeId <= 0) {
                                $typeId = 2;
                            }
                            $title = !empty($docTitles[$idx]) ? trim($docTitles[$idx]) : pathinfo($origName, PATHINFO_FILENAME);
                            $filePath = "/uploads/documents/{$savedName}";

                            $docStmt->execute([$appId, $customerId, $typeId, $title, $filePath, $origName, $fileSize, $mimeType, $user['id'] ?? null]);
                            if ($typeId) {
                                try {
                                    $upChecklist = $pdo->prepare("UPDATE document_checklists SET status = 'VERIFIED', verified_at = CURRENT_TIMESTAMP WHERE application_id = ? AND document_type_id = ?");
                                    $upChecklist->execute([$appId, $typeId]);
                                } catch (\Throwable $ign) {}
                            }
                        }
                    }
                }
            }

            // Generate Application Invoice (Always create proper invoice sequence)
            $invCount = (int)$pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn() + 1;
            $invNumber = sprintf("INV-%s-%06d", date('Y'), $invCount);
            $dueDate = date('Y-m-d', strtotime('+7 days'));

            if ($isPayNow && $totalAmount > 0) {
                $payMethod = trim($_POST['pay_method'] ?? 'Cash');
                $payRef = trim($_POST['pay_reference'] ?? '');

                // Insert Paid Invoice
                $insInv = $pdo->prepare("INSERT INTO invoices (
                    invoice_number, application_id, customer_id, issue_date, due_date,
                    subtotal, discount, tax_rate, tax_amount, total_amount, paid_amount, balance_amount,
                    status, notes, created_by, created_at
                ) VALUES (?, ?, ?, CURRENT_DATE, CURRENT_DATE, ?, ?, 0.00, ?, ?, ?, 0.00, 'Paid', 'Registration Invoice', ?, CURRENT_TIMESTAMP)");
                $insInv->execute([
                    $invNumber, $appId, $customerId,
                    $sellingPrice, $discount, $taxAmount, $totalAmount, $totalAmount,
                    $user['id'] ?? null
                ]);

                if ($payMethod === 'Customer Wallet') {
                    $wallet = \App\Services\WalletService::getOrCreateWallet($customerId);
                    $wBal = (float)$wallet['current_balance'];
                    if ($wBal >= $totalAmount) {
                        $wDebit = \App\Services\WalletService::debit($customerId, $totalAmount, "Visa fee for {$appNumber} via Wallet", $appId, $user['id'] ?? null);
                        
                        // Record payment with valid invoice_number
                        $rcpCount = (int)$pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn() + 1;
                        $rcpNum = sprintf("RCP-%s-%06d", date('Y'), $rcpCount);

                        $pdo->prepare("INSERT INTO payments (
                            payment_number, invoice_number, application_id, customer_id, amount, payment_method, payment_date, transaction_reference, payment_type, status, received_by, wallet_transaction_id, notes, created_at
                        ) VALUES (?, ?, ?, ?, ?, 'Customer Wallet', CURRENT_DATE, ?, 'Customer Payment', 'Completed', ?, ?, 'Immediate registration payment via wallet', CURRENT_TIMESTAMP)")
                        ->execute([$rcpNum, $invNumber, $appId, $customerId, $totalAmount, $wDebit['transaction_id'] ?? 'WALLET_TXN', $user['id'] ?? null, $wDebit['id'] ?? null]);

                        $pdo->prepare("UPDATE applications SET paid_amount = ?, balance_amount = 0.00, payment_status = 'Paid' WHERE id = ?")->execute([$totalAmount, $appId]);
                    }
                } elseif ($payMethod === 'Stripe') {
                    $linkRes = \App\Services\PaymentLinkService::createLink($appId, $totalAmount, "Visa Application Fee for {$customer['full_name']}", "Immediate Registration Checkout", $user['id'] ?? null);
                    if ($linkRes['success'] && !empty($linkRes['url'])) {
                        $paymentRedirectUrl = $linkRes['url'];
                    }
                } else {
                    // Manual Cash / Bank Transfer / Card
                    $rcpCount = (int)$pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn() + 1;
                    $rcpNum = sprintf("RCP-%s-%06d", date('Y'), $rcpCount);

                    $pdo->prepare("INSERT INTO payments (
                        payment_number, invoice_number, application_id, customer_id, amount, payment_method, payment_date, transaction_reference, payment_type, status, received_by, notes, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, CURRENT_DATE, ?, 'Customer Payment', 'Completed', ?, 'Immediate registration payment', CURRENT_TIMESTAMP)")
                    ->execute([$rcpNum, $invNumber, $appId, $customerId, $totalAmount, $payMethod, $payRef ?: 'CASH_REC', $user['id'] ?? null]);

                    $pdo->prepare("UPDATE applications SET paid_amount = ?, balance_amount = 0.00, payment_status = 'Paid' WHERE id = ?")->execute([$totalAmount, $appId]);
                }
            } else {
                // Insert Initial Unpaid Invoice
                $insInv = $pdo->prepare("INSERT INTO invoices (
                    invoice_number, application_id, customer_id, issue_date, due_date,
                    subtotal, discount, tax_rate, tax_amount, total_amount, paid_amount, balance_amount,
                    status, notes, created_by, created_at
                ) VALUES (?, ?, ?, CURRENT_DATE, ?, ?, ?, 0.00, ?, ?, 0.00, ?, 'Unpaid', 'Initial Application Invoice', ?, CURRENT_TIMESTAMP)");
                $insInv->execute([
                    $invNumber, $appId, $customerId, $dueDate,
                    $sellingPrice, $discount, $taxAmount, $totalAmount, $totalAmount,
                    $user['id'] ?? null
                ]);
            }

            // Record Supplier Account Payable immediately so Supplier Management & Portal reflect it accurately
            if (!empty($supplierId) && $supplierCost > 0) {
                $spayRef = 'SPAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
                $spStmt = $pdo->prepare("INSERT INTO supplier_payments (
                    payment_reference, supplier_id, application_id, payable_amount, paid_amount,
                    payment_date, payment_method, payment_status, notes, created_by, created_at
                ) VALUES (?, ?, ?, ?, 0.00, ?, 'Account Payable', 'Pending', ?, ?, CURRENT_TIMESTAMP)");
                $spStmt->execute([
                    $spayRef,
                    $supplierId,
                    $appId,
                    $supplierCost,
                    $appDate,
                    "Payable recorded automatically for visa application {$appNumber} ({$visaTypeName})",
                    $user['id'] ?? null
                ]);
            }

            // Calculate initial health score
            HealthCalculatorService::calculate($appId);

            // Audit log
            AuditService::log('CREATE_APPLICATION', 'Applications', $appId, "Registered new visa application {$appNumber} for {$customer['full_name']}", [
                'application_number' => $appNumber,
                'service' => $service['name'],
                'priority' => $priority
            ], $user['id'] ?? null);

            $pdo->commit();

            // Dispatch Central Real-Time Notification for Visa Creation
            try {
                \App\Services\NotificationService::trigger('visa.created', [
                    'application_id' => $appId,
                    'customer_id' => $customerId,
                    'assigned_staff_id' => $assignedStaffId,
                    'application_number' => $appNumber,
                    'visa_type' => $service['name'] ?? 'Visa',
                    'current_stage' => 'Application Registered',
                    'status' => 'Registered',
                    'actionUrl' => App::url('portal/dashboard'),
                ]);
            } catch (\Throwable $e) {}

            if ($paymentRedirectUrl) {
                redirect($paymentRedirectUrl);
            }

            redirect("/applications/show?id={$appId}", "Visa application {$appNumber} created successfully!", 'success');
        } catch (Exception $e) {
            $pdo->rollBack();
            redirect('/applications/create', 'Failed to create application: ' . $e->getMessage(), 'danger');
        }
    }

    public function show(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        $appId = (int)($_GET['id'] ?? 0);
        if ($appId <= 0) {
            redirect('/applications', 'Application not specified.', 'danger');
        }

        // Fetch application master record
        $stmt = $pdo->prepare("SELECT a.*, 
            c.id as customer_id, c.customer_code, c.full_name as customer_name, c.mobile as customer_mobile, 
            c.email as customer_email, c.nationality as customer_nationality, c.dob as customer_dob,
            c.gender as customer_gender, c.current_country as customer_current_country,
            vs.name as service_name, vs.entry_type, vs.processing_type, vs.estimated_days,
            ct.name as country_name, ct.flag_emoji, ct.iso_code as country_code,
            vc.name as category_name,
            u.name as staff_name, u.email as staff_email, u.phone as staff_phone, u.designation as staff_designation,
            creator.name as created_by_name,
            b.name as branch_name,
            cp.passport_number as current_passport, cp.expiry_date as passport_expiry, cp.issuing_country as passport_issuing_country
            FROM applications a
            JOIN customers c ON a.customer_id = c.id
            JOIN visa_services vs ON a.visa_service_id = vs.id
            JOIN countries ct ON vs.country_id = ct.id
            LEFT JOIN visa_categories vc ON vs.category_id = vc.id
            LEFT JOIN customer_passports cp ON c.id = cp.customer_id AND cp.is_primary = 1
            LEFT JOIN users u ON a.assigned_staff_id = u.id
            LEFT JOIN users creator ON a.created_by = creator.id
            LEFT JOIN branches b ON a.branch_id = b.id
            WHERE a.id = ?");
        $stmt->execute([$appId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            redirect('/applications', 'Visa application not found.', 'danger');
        }

        $scopedBranchId = get_scoped_branch_id(0, $user);
        if ($scopedBranchId > 0 && !empty($app['branch_id']) && (int)$app['branch_id'] !== $scopedBranchId) {
            redirect('/applications', 'Unauthorized: this application belongs to a different branch.', 'danger');
        }

        // Standardized Visa Journey Lifecycle Stages
        $lifecycleStages = [
            'Application Registered',
            'Documents Collected',
            'Documents Verified',
            'Application Submitted',
            'In Process',
            'Medical / Biometrics Processing',
            'Visa Issued & Completed'
        ];

        // Fetch Stage History
        $histStmt = $pdo->prepare("SELECT ash.*, u.name as changed_by_name, u.designation as changed_by_role 
            FROM application_status_history ash 
            LEFT JOIN users u ON ash.changed_by = u.id 
            WHERE ash.application_id = ? 
            ORDER BY ash.created_at ASC");
        $histStmt->execute([$appId]);
        $stageHistory = $histStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Determine which stages have been completed in history
        $completedStageNames = array_column($stageHistory, 'to_stage');
        $completedStageNames[] = 'Application Registered'; // First step always initiated

        $currentStageIndex = array_search($app['current_stage'], $lifecycleStages, true);
        if ($currentStageIndex === false) {
            $currentStageIndex = 0;
        }

        // Build journey progression objects for timeline rendering
        $journeyStages = [];
        $totalStages = count($lifecycleStages);
        $completedCount = 0;

        foreach ($lifecycleStages as $idx => $stgName) {
            $state = 'PENDING';
            $matchedHist = null;

            foreach ($stageHistory as $h) {
                if ($h['to_stage'] === $stgName) {
                    $matchedHist = $h;
                    break;
                }
            }

            if ($stgName === $app['current_stage']) {
                if ($app['status'] === 'Action Required' || str_contains(strtolower($stgName), 'returned')) {
                    $state = 'BLOCKED';
                } elseif ($app['status'] === 'Approved' || $app['status'] === 'Completed') {
                    $state = 'COMPLETED';
                    $completedCount++;
                } else {
                    $state = 'CURRENT';
                }
            } elseif ($idx < $currentStageIndex || in_array($stgName, $completedStageNames, true)) {
                $state = 'COMPLETED';
                $completedCount++;
            } else {
                $state = 'PENDING';
            }

            $journeyStages[] = [
                'index' => $idx + 1,
                'name' => $stgName,
                'state' => $state,
                'history' => $matchedHist,
                'is_current' => ($stgName === $app['current_stage']),
            ];
        }

        $progressPercentage = min(100, (int)round(($completedCount / $totalStages) * 100));

        // Fetch Document Requirements Checklist & Statistics via DocumentChecklistService
        $checklistData = DocumentChecklistService::getChecklist($appId);
        $documentChecklist = $checklistData['items'];

        // Fetch Tasks
        $tasksStmt = $pdo->prepare("SELECT t.*, u.name as assigned_to_name 
            FROM tasks t 
            LEFT JOIN users u ON t.assigned_to = u.id 
            WHERE t.application_id = ? 
            ORDER BY t.status = 'Pending' DESC, t.due_date ASC");
        $tasksStmt->execute([$appId]);
        $tasks = $tasksStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Fetch Appointments
        $aptStmt = $pdo->prepare("SELECT * FROM appointments WHERE application_id = ? ORDER BY appointment_date ASC");
        $aptStmt->execute([$appId]);
        $appointments = $aptStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Fetch Staff Assignment History
        $assignHistStmt = $pdo->prepare("SELECT aa.*, u.name as staff_name, u.designation as staff_role, assigner.name as assigned_by_name 
            FROM application_assignments aa
            JOIN users u ON aa.staff_id = u.id
            LEFT JOIN users assigner ON aa.assigned_by = assigner.id
            WHERE aa.application_id = ? 
            ORDER BY aa.assigned_at DESC");
        $assignHistStmt->execute([$appId]);
        $assignmentHistory = $assignHistStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Fetch Activity & Audit Trail
        $auditStmt = $pdo->prepare("SELECT al.*, u.name as user_name 
            FROM activity_logs al 
            LEFT JOIN users u ON al.user_id = u.id 
            WHERE (al.module = 'Applications' AND al.record_id = ?) 
               OR (al.module = 'Documents' AND al.description LIKE ?)
            ORDER BY al.created_at DESC LIMIT 30");
        $auditStmt->execute([$appId, "%#{$app['application_number']}%"]);
        $activityLogs = $auditStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Staff List for Assignment dropdown
        $allStaff = $pdo->query("SELECT id, name, designation, department FROM users WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Payments for this application
        $payStmt = $pdo->prepare("SELECT p.*, u.name as received_by_name 
            FROM payments p 
            LEFT JOIN users u ON p.received_by = u.id 
            WHERE p.application_id = ? 
            ORDER BY p.payment_date DESC, p.created_at DESC");
        $payStmt->execute([$appId]);
        $appPayments = $payStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Fetch Refunds for this application
        $refStmt = $pdo->prepare("SELECT r.*, u.name as processed_by_name 
            FROM refunds r 
            LEFT JOIN users u ON r.processed_by = u.id 
            WHERE r.application_id = ? 
            ORDER BY r.created_at DESC");
        $refStmt->execute([$appId]);
        $appRefunds = $refStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Run Real-time Health Diagnosis
        $healthDiagnosis = HealthCalculatorService::diagnose($appId);

        // Calculate Deadline Status
        $deadlineStatus = 'On Track';
        $deadlineClass = 'text-success';
        $deadlineDays = 0;

        if (!empty($app['expected_completion_date'])) {
            $now = strtotime(date('Y-m-d'));
            $target = strtotime($app['expected_completion_date']);
            $diff = (int)round(($target - $now) / 86400);
            $deadlineDays = $diff;

            if ($diff < 0) {
                $deadlineStatus = 'Overdue by ' . abs($diff) . ' day' . (abs($diff) === 1 ? '' : 's');
                $deadlineClass = 'text-danger';
            } elseif ($diff === 0) {
                $deadlineStatus = 'Due Today';
                $deadlineClass = 'text-warning';
            } else {
                $deadlineStatus = "Due in {$diff} days";
                $deadlineClass = 'text-success';
            }
        }

        // Fetch Customer Family & Residence Details
        $famStmt = $pdo->prepare("SELECT * FROM customer_family WHERE customer_id = ?");
        $famStmt->execute([(int)$app['customer_id']]);
        $customerFamily = $famStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $resStmt = $pdo->prepare("SELECT * FROM customer_residences WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
        $resStmt->execute([(int)$app['customer_id']]);
        $customerResidence = $resStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        // Fetch Decision Records (Approval, Rejection, Returns)
        $apprStmt = $pdo->prepare("SELECT va.*, u.name as approved_by_name FROM visa_approvals va LEFT JOIN users u ON va.approved_by = u.id WHERE va.application_id = ?");
        $apprStmt->execute([$appId]);
        $visaApproval = $apprStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $rejStmt = $pdo->prepare("SELECT vr.*, u.name as rejected_by_name FROM visa_rejections vr LEFT JOIN users u ON vr.rejected_by = u.id WHERE vr.application_id = ?");
        $rejStmt->execute([$appId]);
        $visaRejection = $rejStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $retStmt = $pdo->prepare("SELECT ar.*, u.name as returned_by_name FROM application_returns ar LEFT JOIN users u ON ar.returned_by = u.id WHERE ar.application_id = ? ORDER BY ar.created_at DESC");
        $retStmt->execute([$appId]);
        $applicationReturns = $retStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Fetch Document Requests
        $docReqStmt = $pdo->prepare("SELECT dr.*, u.name as requested_by_name, dt.name as document_name 
            FROM document_requests dr 
            LEFT JOIN users u ON dr.requested_by = u.id 
            LEFT JOIN document_types dt ON dr.document_type_id = dt.id 
            WHERE dr.application_id = ? 
            ORDER BY dr.created_at DESC");
        $docReqStmt->execute([$appId]);
        $documentRequests = $docReqStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Fetch Communications Log
        $commStmt = $pdo->prepare("SELECT c.*, u.name as staff_name FROM communications c LEFT JOIN users u ON c.staff_id = u.id WHERE c.application_id = ? ORDER BY c.created_at DESC");
        $commStmt->execute([$appId]);
        $communications = $commStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Fetch Supplier Details & Supplier Payments
        $supplierDetails = null;
        $supplierPayments = [];
        if (!empty($app['supplier_id'])) {
            $supStmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
            $supStmt->execute([(int)$app['supplier_id']]);
            $supplierDetails = $supStmt->fetch(PDO::FETCH_ASSOC);

            $spStmt = $pdo->prepare("SELECT * FROM supplier_payments WHERE application_id = ? ORDER BY payment_date DESC");
            $spStmt->execute([$appId]);
            $supplierPayments = $spStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        // Fetch Customer Wallet & Ledger Transactions
        $customerWallet = \App\Services\WalletService::getOrCreateWallet((int)$app['customer_id']);
        $walletTransactions = \App\Services\WalletService::getTransactions((int)$app['customer_id'], 20);

        require_once dirname(__DIR__) . '/Views/applications/show.php';
    }

    public function assignStaff(): void
    {
        AuthMiddleware::handle();
        $user = auth_user();

        $appId = (int)($_POST['application_id'] ?? 0);
        $staffId = (int)($_POST['staff_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if ($appId <= 0 || $staffId <= 0) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/applications', 'Please select a valid staff member.', 'danger');
        }

        $result = StaffAssignmentService::assign($appId, $staffId, (int)($user['id'] ?? 0), $notes);

        if ($result['success']) {
            redirect("/applications/show?id={$appId}", $result['message'], 'success');
        } else {
            redirect("/applications/show?id={$appId}", $result['message'], 'danger');
        }
    }

    public function recordDecision(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        $appId = (int)($_POST['application_id'] ?? 0);
        $decision = trim($_POST['decision'] ?? '');
        $visaNumber = trim($_POST['visa_number'] ?? '');
        $rejectionReason = trim($_POST['rejection_reason'] ?? '');
        $decisionNotes = trim($_POST['decision_notes'] ?? '');

        if ($appId <= 0 || !in_array($decision, ['Approved', 'Rejected'], true)) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/applications', 'Invalid decision submitted.', 'danger');
        }

        if ($decision === 'Rejected' && empty($rejectionReason)) {
            redirect("/applications/show?id={$appId}", 'A rejection reason is strictly mandatory when rejecting an application.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT id, application_number, customer_id, branch_id, current_stage, status, assigned_staff_id FROM applications WHERE id = ?");
        $stmt->execute([$appId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            redirect('/applications', 'Application not found.', 'danger');
        }

        // 1. RBAC Check for Final Decision
        $isAdmin = is_super_admin($user) || is_admin($user);
        $roleSlug = $user['role_slug'] ?? '';

        if ($decision === 'Approved') {
            if (!$isAdmin && !user_can('applications.approve') && !user_can('applications.final_decision') && !in_array($roleSlug, ['visa-manager', 'branch-manager'], true)) {
                redirect("/applications/show?id={$appId}", 'Unauthorized: You do not have permission to approve visa applications.', 'danger');
            }
        } else {
            if (!$isAdmin && !user_can('applications.reject') && !user_can('applications.final_decision') && !in_array($roleSlug, ['visa-manager', 'branch-manager'], true)) {
                redirect("/applications/show?id={$appId}", 'Unauthorized: You do not have permission to reject visa applications.', 'danger');
            }
        }

        // 2. Branch isolation check
        $scopedBranchId = get_scoped_branch_id(0, $user);
        if ($scopedBranchId > 0 && (int)$app['branch_id'] !== $scopedBranchId) {
            redirect("/applications/show?id={$appId}", 'Unauthorized: This application belongs to a different branch.', 'danger');
        }

        // 3. Prevent duplicate final decisions
        if (in_array($app['status'], ['Approved', 'Rejected', 'Completed'], true)) {
            redirect("/applications/show?id={$appId}", "This application has already reached a final decision ({$app['status']}).", 'warning');
        }

        $pdo->beginTransaction();
        try {
            $newStage = ($decision === 'Approved') ? 'Visa Issued & Completed' : 'Application Rejected / Closed';
            $newStatus = $decision;

            $updStmt = $pdo->prepare("UPDATE applications 
                SET status = ?, 
                    current_stage = ?, 
                    visa_number = ?, 
                    rejection_reason = ?, 
                    decision_date = CURRENT_TIMESTAMP,
                    next_action = ?,
                    updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?");
            
            $nextAct = ($decision === 'Approved') ? 'Hand over visa grant notice and passport to customer' : 'Notify customer of rejection and arrange return of original documents';

            $updStmt->execute([
                $newStatus,
                $newStage,
                $visaNumber ?: null,
                $rejectionReason ?: null,
                $nextAct,
                $appId
            ]);

            // Insert status history
            $histStmt = $pdo->prepare("INSERT INTO application_status_history (
                application_id, from_stage, to_stage, from_status, to_status, comments, changed_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $histStmt->execute([
                $appId,
                $app['current_stage'],
                $newStage,
                $app['status'],
                $newStatus,
                $decisionNotes ?: "Final decision recorded as {$decision}" . ($rejectionReason ? " (Reason: {$rejectionReason})" : ""),
                $user['id'] ?? null
            ]);

            // Recalculate health
            HealthCalculatorService::calculate($appId);

            // Audit
            AuditService::log(
                'FINAL_DECISION',
                'Applications',
                $appId,
                "Final decision recorded for {$app['application_number']}: {$decision}",
                ['decision' => $decision, 'visa_number' => $visaNumber, 'rejection_reason' => $rejectionReason],
                $user['id'] ?? null
            );

            $pdo->commit();

            // Dispatch Central Notification for Decision Outcome
            try {
                $evtType = ($decision === 'Approved') ? 'visa.approved' : 'visa.rejected';
                \App\Services\NotificationService::trigger($evtType, [
                    'application_id' => $appId,
                    'customer_id' => $app['customer_id'],
                    'assigned_staff_id' => $app['assigned_staff_id'],
                    'application_number' => $app['application_number'],
                    'decision' => $decision,
                    'visa_number' => $visaNumber,
                    'decisionNotes' => $decisionNotes ?: ($rejectionReason ?: "Decision marked as {$decision}"),
                    'rejectionReason' => $rejectionReason,
                    'actionUrl' => App::url('portal/dashboard'),
                ]);
            } catch (\Throwable $e) {}

            redirect("/applications/show?id={$appId}", "Application {$app['application_number']} finalized as {$decision}.", 'success');
        } catch (Exception $e) {
            $pdo->rollBack();
            redirect("/applications/show?id={$appId}", 'Failed to record decision: ' . $e->getMessage(), 'danger');
        }
    }

    public function updatePriority(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        $appId = (int)($_POST['application_id'] ?? 0);
        $newPriority = trim($_POST['priority'] ?? '');

        if ($appId <= 0 || !in_array($newPriority, ['Critical', 'Urgent', 'High', 'Normal'], true)) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/applications', 'Invalid priority level.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT id, application_number, priority FROM applications WHERE id = ?");
        $stmt->execute([$appId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            redirect('/applications', 'Application not found.', 'danger');
        }

        $oldPriority = $app['priority'];
        $pdo->prepare("UPDATE applications SET priority = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$newPriority, $appId]);

        AuditService::log(
            'PRIORITY_CHANGE',
            'Applications',
            $appId,
            "Priority updated from {$oldPriority} to {$newPriority} for {$app['application_number']}",
            ['old_priority' => $oldPriority, 'new_priority' => $newPriority],
            $user['id'] ?? null
        );

        redirect("/applications/show?id={$appId}", "Priority updated to {$newPriority}.", 'success');
    }

    public function addNote(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        $appId = (int)($_POST['application_id'] ?? 0);
        $note = trim($_POST['note'] ?? '');

        if ($appId <= 0 || empty($note)) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/applications', 'Note content cannot be blank.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT id, application_number, internal_notes FROM applications WHERE id = ?");
        $stmt->execute([$appId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            redirect('/applications', 'Application not found.', 'danger');
        }

        $timestamp = date('d M Y, h:i A');
        $author = $user['name'] ?? 'Staff';
        $formattedEntry = "[{$timestamp} by {$author}]\n{$note}\n\n" . ($app['internal_notes'] ?? '');

        $pdo->prepare("UPDATE applications SET internal_notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$formattedEntry, $appId]);

        AuditService::log(
            'ADD_NOTE',
            'Applications',
            $appId,
            "Internal staff note appended to {$app['application_number']}",
            ['note' => $note],
            $user['id'] ?? null
        );

        redirect("/applications/show?id={$appId}", 'Internal note saved successfully.', 'success');
    }

    public function archive(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        $appId = (int)($_POST['application_id'] ?? 0);
        if ($appId <= 0) {
            redirect('/applications', 'Invalid application ID.', 'danger');
        }

        $pdo->prepare("UPDATE applications SET is_archived = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$appId]);

        AuditService::log('ARCHIVE_APPLICATION', 'Applications', $appId, "Archived application record #{$appId}", null, $user['id'] ?? null);

        redirect('/applications', 'Application archived successfully.', 'info');
    }

    public function updateStage(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        $appId = (int)($_POST['application_id'] ?? 0);
        $newStage = trim($_POST['new_stage'] ?? '');
        $newStatus = trim($_POST['new_status'] ?? '');
        $nextAction = trim($_POST['next_action'] ?? '');
        $nextActionDueDate = !empty($_POST['next_action_due_date']) ? $_POST['next_action_due_date'] : null;
        $comments = trim($_POST['comments'] ?? '');

        if ($appId <= 0 || empty($newStage)) {
            redirect("/applications/show?id={$appId}", 'Target stage is required.', 'danger');
        }

        if (empty($newStatus)) {
            $newStatus = 'In Process';
            if ($newStage === 'Visa Issued & Completed' || $newStage === 'Approved') {
                $newStatus = 'Approved';
            } elseif ($newStage === 'Application Rejected' || $newStage === 'Rejected') {
                $newStatus = 'Rejected';
            } elseif (str_contains(strtolower($newStage), 'returned') || str_contains(strtolower($newStage), 'modification')) {
                $newStatus = 'Action Required';
            }
        }

        $res = StageTransitionService::transition(
            $appId, 
            $newStage, 
            $newStatus, 
            $comments ?: "Stage advanced to {$newStage}", 
            (int)($user['id'] ?? 0), 
            $nextAction ?: null, 
            $nextActionDueDate ?: null
        );

        if (isset($res['success']) && !$res['success']) {
            redirect("/applications/show?id={$appId}", $res['message'] ?? 'Stage transition failed.', 'danger');
        }

        AuditService::log('STAGE_TRANSITION', 'Applications', $appId, "Advanced stage to {$newStage} ({$newStatus})", ['comments' => $comments], $user['id'] ?? null);

        redirect("/applications/show?id={$appId}", "Application stage advanced to '{$newStage}' successfully.", 'success');
    }

    public function decisionApprove(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        $appId = (int)($_POST['application_id'] ?? 0);
        $visaNumber = trim($_POST['visa_number'] ?? '');
        $issueDate = trim($_POST['issue_date'] ?? date('Y-m-d'));
        $expiryDate = trim($_POST['expiry_date'] ?? '');
        $entryBefore = !empty($_POST['entry_before_date']) ? $_POST['entry_before_date'] : null;
        $maxStay = trim($_POST['maximum_stay'] ?? '30 Days');
        $validity = trim($_POST['validity'] ?? '60 Days');
        $notes = trim($_POST['approval_notes'] ?? '');

        if ($appId <= 0 || empty($visaNumber) || empty($expiryDate)) {
            redirect("/applications/show?id={$appId}", 'Visa number and expiry date are required for approval.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT id, application_number, branch_id, status FROM applications WHERE id = ?");
        $stmt->execute([$appId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            redirect('/applications', 'Application not found.', 'danger');
        }

        // 1. RBAC Check for Visa Approval
        $isAdmin = is_super_admin($user) || is_admin($user);
        $roleSlug = $user['role_slug'] ?? '';
        if (!$isAdmin && !user_can('applications.approve') && !user_can('applications.final_decision') && !in_array($roleSlug, ['visa-manager', 'branch-manager'], true)) {
            redirect("/applications/show?id={$appId}", 'Unauthorized: You do not have permission to grant visa approvals.', 'danger');
        }

        // 2. Branch isolation check
        $scopedBranchId = get_scoped_branch_id(0, $user);
        if ($scopedBranchId > 0 && (int)$app['branch_id'] !== $scopedBranchId) {
            redirect("/applications/show?id={$appId}", 'Unauthorized: This application belongs to a different branch.', 'danger');
        }

        // Handle Visa PDF upload if provided
        $filePath = null;
        if (!empty($_FILES['visa_file']['name']) && $_FILES['visa_file']['error'] === UPLOAD_ERR_OK) {
            $dir = dirname(__DIR__, 2) . '/storage/uploads/visas';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $ext = pathinfo($_FILES['visa_file']['name'], PATHINFO_EXTENSION);
            $fileName = 'VISA_' . $appId . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['visa_file']['tmp_name'], $dir . '/' . $fileName);
            $filePath = 'visas/' . $fileName;
        }

        // Upsert into visa_approvals safely
        $stmtCheck = $pdo->prepare("SELECT id FROM visa_approvals WHERE application_id = ?");
        $stmtCheck->execute([$appId]);
        $existingApprovalId = $stmtCheck->fetchColumn();

        if ($existingApprovalId) {
            $pdo->prepare("UPDATE visa_approvals SET 
                visa_number = ?, issue_date = ?, expiry_date = ?, entry_before_date = ?, maximum_stay = ?, validity = ?, 
                approved_visa_file = COALESCE(?, approved_visa_file), approval_notes = ?, approved_by = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?")
                ->execute([$visaNumber, $issueDate, $expiryDate, $entryBefore, $maxStay, $validity, $filePath, $notes, $user['id'] ?? null, $existingApprovalId]);
        } else {
            $pdo->prepare("INSERT INTO visa_approvals (
                application_id, visa_number, issue_date, expiry_date, entry_before_date, maximum_stay, validity, approved_visa_file, approval_notes, approved_by, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)")
            ->execute([$appId, $visaNumber, $issueDate, $expiryDate, $entryBefore, $maxStay, $validity, $filePath, $notes, $user['id'] ?? null]);
        }

        // Transition status
        StageTransitionService::transition($appId, 'Visa Issued & Completed', 'Approved', "Visa officially granted. Visa Number: {$visaNumber}", (int)($user['id'] ?? 0));

        // Update application record
        $pdo->prepare("UPDATE applications SET visa_number = ?, approval_date = ?, approved_visa_file = COALESCE(?, approved_visa_file) WHERE id = ?")
            ->execute([$visaNumber, $issueDate, $filePath, $appId]);

        AuditService::log('VISA_APPROVED', 'Applications', $appId, "Visa approved & granted: {$visaNumber}", ['visa_number' => $visaNumber, 'expiry_date' => $expiryDate], $user['id'] ?? null);

        redirect("/applications/show?id={$appId}", "Visa grant {$visaNumber} recorded successfully!", 'success');
    }

    public function decisionReject(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        $appId = (int)($_POST['application_id'] ?? 0);
        $rejectionDate = trim($_POST['rejection_date'] ?? date('Y-m-d'));
        $customerReason = trim($_POST['customer_reason'] ?? '');
        $internalReason = trim($_POST['internal_reason'] ?? '');
        $eligibility = trim($_POST['reapplication_eligibility'] ?? 'Eligible to Reapply');

        if ($appId <= 0 || empty($customerReason)) {
            redirect("/applications/show?id={$appId}", 'Customer-facing rejection reason is mandatory.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT id, application_number, branch_id, status FROM applications WHERE id = ?");
        $stmt->execute([$appId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            redirect('/applications', 'Application not found.', 'danger');
        }

        // 1. RBAC Check for Visa Rejection
        $isAdmin = is_super_admin($user) || is_admin($user);
        $roleSlug = $user['role_slug'] ?? '';
        if (!$isAdmin && !user_can('applications.reject') && !user_can('applications.final_decision') && !in_array($roleSlug, ['visa-manager', 'branch-manager'], true)) {
            redirect("/applications/show?id={$appId}", 'Unauthorized: You do not have permission to issue visa rejections.', 'danger');
        }

        // 2. Branch isolation check
        $scopedBranchId = get_scoped_branch_id(0, $user);
        if ($scopedBranchId > 0 && (int)$app['branch_id'] !== $scopedBranchId) {
            redirect("/applications/show?id={$appId}", 'Unauthorized: This application belongs to a different branch.', 'danger');
        }

        $filePath = null;
        if (!empty($_FILES['rejection_file']['name']) && $_FILES['rejection_file']['error'] === UPLOAD_ERR_OK) {
            $dir = dirname(__DIR__, 2) . '/storage/uploads/rejections';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $ext = pathinfo($_FILES['rejection_file']['name'], PATHINFO_EXTENSION);
            $fileName = 'REJECT_' . $appId . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['rejection_file']['tmp_name'], $dir . '/' . $fileName);
            $filePath = 'rejections/' . $fileName;
        }

        $stmtCheck = $pdo->prepare("SELECT id FROM visa_rejections WHERE application_id = ?");
        $stmtCheck->execute([$appId]);
        $existingRejId = $stmtCheck->fetchColumn();

        if ($existingRejId) {
            $pdo->prepare("UPDATE visa_rejections SET 
                rejection_date = ?, customer_reason = ?, internal_reason = ?, reapplication_eligibility = ?, 
                rejection_document = COALESCE(?, rejection_document), rejected_by = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?")
                ->execute([$rejectionDate, $customerReason, $internalReason, $eligibility, $filePath, $user['id'] ?? null, $existingRejId]);
        } else {
            $pdo->prepare("INSERT INTO visa_rejections (
                application_id, rejection_date, customer_reason, internal_reason, reapplication_eligibility, rejection_document, rejected_by, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)")
            ->execute([$appId, $rejectionDate, $customerReason, $internalReason, $eligibility, $filePath, $user['id'] ?? null]);
        }

        StageTransitionService::transition($appId, 'Application Rejected', 'Rejected', "Application rejected: {$customerReason}", (int)($user['id'] ?? 0));

        $pdo->prepare("UPDATE applications SET rejection_date = ?, rejection_reason = ? WHERE id = ?")
            ->execute([$rejectionDate, $customerReason, $appId]);

        AuditService::log('VISA_REJECTED', 'Applications', $appId, "Application rejected: {$customerReason}", null, $user['id'] ?? null);

        redirect("/applications/show?id={$appId}", 'Visa rejection recorded.', 'warning');
    }

    public function decisionReturn(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        $appId = (int)($_POST['application_id'] ?? 0);
        $returnReason = trim($_POST['return_reason'] ?? '');
        $requiredChanges = trim($_POST['required_changes'] ?? '');
        $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : date('Y-m-d', strtotime('+7 days'));
        $comment = trim($_POST['staff_comment'] ?? '');

        if ($appId <= 0 || empty($returnReason)) {
            redirect("/applications/show?id={$appId}", 'Return reason is required.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT id, application_number, branch_id, status FROM applications WHERE id = ?");
        $stmt->execute([$appId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            redirect('/applications', 'Application not found.', 'danger');
        }

        // 1. RBAC Check for Return
        $isAdmin = is_super_admin($user) || is_admin($user);
        $roleSlug = $user['role_slug'] ?? '';
        if (!$isAdmin && !user_can('applications.return') && !user_can('applications.edit') && !user_can('applications.manage') && !in_array($roleSlug, ['visa-manager', 'branch-manager'], true)) {
            redirect("/applications/show?id={$appId}", 'Unauthorized: You do not have permission to return applications for modification.', 'danger');
        }

        // 2. Branch isolation check
        $scopedBranchId = get_scoped_branch_id(0, $user);
        if ($scopedBranchId > 0 && (int)$app['branch_id'] !== $scopedBranchId) {
            redirect("/applications/show?id={$appId}", 'Unauthorized: This application belongs to a different branch.', 'danger');
        }

        $pdo->prepare("INSERT INTO application_returns (application_id, return_reason, required_changes, deadline, staff_comment, returned_by)
            VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$appId, $returnReason, $requiredChanges, $deadline, $comment, $user['id'] ?? null]);

        StageTransitionService::transition($appId, 'Returned', 'Modification Required', "Application returned for corrections: {$returnReason}", (int)($user['id'] ?? 0));

        $pdo->prepare("UPDATE applications SET return_reason = ?, return_deadline = ? WHERE id = ?")
            ->execute([$returnReason, $deadline, $appId]);

        AuditService::log('VISA_RETURNED', 'Applications', $appId, "Returned for customer modifications: {$returnReason}", ['deadline' => $deadline], $user['id'] ?? null);

        redirect("/applications/show?id={$appId}", 'Modification return notice issued to applicant.', 'info');
    }

    public function requestDocument(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        $appId = (int)($_POST['application_id'] ?? 0);
        $docTypeId = (int)($_POST['document_type_id'] ?? 1);
        $notes = trim($_POST['notes'] ?? '');
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : date('Y-m-d', strtotime('+5 days'));

        $stmt = $pdo->prepare("SELECT customer_id FROM applications WHERE id = ?");
        $stmt->execute([$appId]);
        $customerId = (int)$stmt->fetchColumn();

        if ($appId <= 0 || $customerId <= 0) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/applications', 'Invalid application ID.', 'danger');
        }

        $pdo->prepare("INSERT INTO document_requests (customer_id, application_id, document_type_id, requested_by, status, notes, due_date) VALUES (?, ?, ?, ?, 'PENDING', ?, ?)")
            ->execute([$customerId, $appId, $docTypeId, $user['id'] ?? null, $notes, $dueDate]);

        AuditService::log('REQUEST_DOCUMENT', 'Documents', $appId, "Requested additional document for application #{$appId}", ['notes' => $notes, 'due_date' => $dueDate], $user['id'] ?? null);

        try {
            \App\Services\NotificationService::trigger('document.requested', [
                'application_id' => $appId,
                'customer_id' => $customerId,
                'actionUrl' => "/portal/documents"
            ]);
        } catch (\Throwable $e) {}

        redirect("/applications/show?id={$appId}#doc-requests", 'Document request dispatched to customer portal.', 'success');
    }

    public function addCommunication(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        $appId = (int)($_POST['application_id'] ?? 0);
        $channel = trim($_POST['channel'] ?? 'Phone');
        $direction = trim($_POST['direction'] ?? 'Outbound');
        $subject = trim($_POST['subject'] ?? 'Client Follow-up');
        $message = trim($_POST['message'] ?? '');
        $contactPerson = trim($_POST['contact_person'] ?? '');

        $stmt = $pdo->prepare("SELECT customer_id FROM applications WHERE id = ?");
        $stmt->execute([$appId]);
        $customerId = (int)$stmt->fetchColumn();

        if ($appId <= 0 || empty($message)) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/applications', 'Message content is required.', 'danger');
        }

        $pdo->prepare("INSERT INTO communications (application_id, customer_id, channel, direction, subject, message, contact_person, staff_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$appId, $customerId, $channel, $direction, $subject, $message, $contactPerson, $user['id'] ?? null]);

        AuditService::log('LOG_COMMUNICATION', 'Applications', $appId, "Logged {$channel} communication ({$direction})", ['subject' => $subject], $user['id'] ?? null);

        redirect("/applications/show?id={$appId}#communication", 'Communication record saved.', 'success');
    }

    public function edit(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        if (!$user) {
            redirect('/login', 'Please sign in to continue.', 'danger');
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            redirect('/applications', 'Invalid application ID.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT a.*, c.full_name as customer_name, c.customer_code, c.email as customer_email, c.mobile as customer_mobile 
            FROM applications a 
            JOIN customers c ON a.customer_id = c.id 
            WHERE a.id = ?");
        $stmt->execute([$id]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            redirect('/applications', 'Application not found.', 'danger');
        }

        // 1. RBAC Check: require applications.edit or applications.manage
        $isAdmin = is_super_admin($user) || is_admin($user);
        $roleSlug = $user['role_slug'] ?? '';
        $isAssigned = ((int)($app['assigned_staff_id'] ?? 0) === (int)$user['id']);

        if (!$isAdmin && !user_can('applications.edit') && !user_can('applications.manage') && $roleSlug !== 'branch-manager') {
            if (!$isAssigned) {
                redirect('/applications', 'Unauthorized: You do not have permission to edit this application.', 'danger');
            }
        }

        // 2. Branch isolation check
        $scopedBranchId = get_scoped_branch_id(0, $user);
        if ($scopedBranchId > 0 && !empty($app['branch_id']) && (int)$app['branch_id'] !== $scopedBranchId) {
            redirect('/applications', 'Unauthorized: This application belongs to a different branch.', 'danger');
        }

        $countries = $pdo->query("SELECT id, name, iso_code, flag_emoji FROM countries WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $services = $pdo->query("SELECT vs.*, ct.name as country_name, ct.flag_emoji FROM visa_services vs JOIN countries ct ON vs.country_id = ct.id WHERE vs.is_active = 1 ORDER BY ct.name ASC, vs.name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $suppliers = $pdo->query("SELECT id, company_name, contact_person FROM suppliers WHERE is_active = 1 ORDER BY company_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $branches = $pdo->query("SELECT id, name, city FROM branches WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $staffMembers = $pdo->query("SELECT id, name, designation FROM users WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        require_once dirname(__DIR__) . '/Views/applications/edit.php';
    }

    public function update(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        if (!$user) {
            redirect('/login', 'Please sign in to continue.', 'danger');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            redirect('/applications', 'Invalid application ID.', 'danger');
        }

        // Fetch current application with all sensitive fields
        $currStmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
        $currStmt->execute([$id]);
        $currentApp = $currStmt->fetch(PDO::FETCH_ASSOC);

        if (!$currentApp) {
            redirect('/applications', 'Application not found.', 'danger');
        }

        // 1. RBAC & IDOR check: Verify user can edit applications
        $isAdmin = is_super_admin($user) || is_admin($user);
        $roleSlug = $user['role_slug'] ?? '';
        $isAssigned = ((int)($currentApp['assigned_staff_id'] ?? 0) === (int)$user['id']);

        if (!$isAdmin && !user_can('applications.edit') && !user_can('applications.manage') && $roleSlug !== 'branch-manager') {
            if (!$isAssigned) {
                redirect('/applications', 'Unauthorized: You do not have permission to edit this application.', 'danger');
            }
        }

        // 2. Branch Isolation check
        $scopedBranchId = get_scoped_branch_id(0, $user);
        if ($scopedBranchId > 0 && (int)$currentApp['branch_id'] !== $scopedBranchId) {
            redirect('/applications', 'Unauthorized: This application belongs to a different branch.', 'danger');
        }

        // 3. Sensitive Field Protection: Branch reassignment
        $targetBranchId = (int)$currentApp['branch_id'];
        if ($isAdmin || $roleSlug === 'branch-manager' || user_can('branches.manage')) {
            $requestedBranch = (int)($_POST['branch_id'] ?? $currentApp['branch_id']);
            if ($requestedBranch > 0 && ($isAdmin || $requestedBranch === $scopedBranchId)) {
                $targetBranchId = $requestedBranch;
            }
        }

        // 4. Sensitive Field Protection: Staff Assignment
        $assignedStaffId = (int)($currentApp['assigned_staff_id'] ?? 0);
        if ($isAdmin || $roleSlug === 'branch-manager' || user_can('applications.assign') || user_can('applications.assign_staff')) {
            if (isset($_POST['assigned_staff_id'])) {
                $newStaff = !empty($_POST['assigned_staff_id']) ? (int)$_POST['assigned_staff_id'] : null;
                if ($newStaff !== null) {
                    $staffCheck = $pdo->prepare("SELECT branch_id FROM users WHERE id = ? AND is_active = 1");
                    $staffCheck->execute([$newStaff]);
                    $staffBranch = $staffCheck->fetchColumn();
                    if ($staffBranch !== false && ($isAdmin || (int)$staffBranch === $targetBranchId)) {
                        $assignedStaffId = $newStaff;
                    }
                } else {
                    $assignedStaffId = null;
                }
            }
        }

        // 5. Sensitive Field Protection: Financial fields (selling_price, supplier_cost, etc.)
        $canEditFinancials = $isAdmin || user_can('finance.manage') || user_can('applications.financial_edit') || in_array($roleSlug, ['accounts', 'finance', 'branch-manager'], true);

        if ($canEditFinancials) {
            $sellingPrice = (float)($_POST['selling_price'] ?? $currentApp['selling_price']);
            $supplierCost = (float)($_POST['supplier_cost'] ?? $currentApp['supplier_cost']);
            $embassyFee = (float)($_POST['embassy_fee'] ?? 0.00);
            $serviceFee = (float)($_POST['service_fee'] ?? 0.00);
            $discountAmount = (float)($_POST['discount_amount'] ?? ($_POST['discount'] ?? $currentApp['discount']));
            $taxAmount = (float)($_POST['tax_amount'] ?? $currentApp['tax_amount']);
            $otherExpenses = $embassyFee + $serviceFee;
            $supplierId = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : $currentApp['supplier_id'];
        } else {
            // Keep existing server-side financial values untouched
            $sellingPrice = (float)$currentApp['selling_price'];
            $supplierCost = (float)$currentApp['supplier_cost'];
            $otherExpenses = (float)$currentApp['other_expenses'];
            $discountAmount = (float)($currentApp['discount_amount'] ?? $currentApp['discount']);
            $taxAmount = (float)$currentApp['tax_amount'];
            $supplierId = $currentApp['supplier_id'];
        }

        $paidAmount = (float)($currentApp['paid_amount'] ?? 0.00);
        $totalAmount = $sellingPrice > 0 ? max(0.0, $sellingPrice - $discountAmount + $taxAmount) : max(0.0, $supplierCost + $otherExpenses + $taxAmount - $discountAmount);
        $balanceAmount = max(0.0, $totalAmount - $paidAmount);
        $grossProfit = round($totalAmount - $supplierCost - $otherExpenses, 2);

        // General fields
        $visaServiceId = (int)($_POST['visa_service_id'] ?? $currentApp['visa_service_id']);
        $passportNumber = trim($_POST['passport_number'] ?? $currentApp['passport_number']);
        $travelDate = !empty($_POST['travel_date']) ? $_POST['travel_date'] : $currentApp['travel_date'];
        $returnDate = !empty($_POST['return_date']) ? $_POST['return_date'] : $currentApp['return_date'];
        $applicationDate = !empty($_POST['submission_date']) ? $_POST['submission_date'] : (!empty($_POST['application_date']) ? $_POST['application_date'] : $currentApp['application_date']);
        $expectedDate = !empty($_POST['expected_completion_date']) ? $_POST['expected_completion_date'] : $currentApp['expected_completion_date'];
        $priority = trim($_POST['priority'] ?? $currentApp['priority']);
        $notes = trim($_POST['notes'] ?? ($_POST['internal_notes'] ?? ($currentApp['internal_notes'] ?? '')));

        // Fetch selected visa service details to sync metadata if changed
        $destinationCountry = $currentApp['destination_country'] ?? '';
        $visaCategory = $currentApp['visa_category'] ?? 'General';
        $visaType = $currentApp['visa_type'] ?? 'Standard Visa';
        $visaDuration = $currentApp['visa_duration'] ?? '30 Days';
        $entryType = $currentApp['entry_type'] ?? 'Single Entry';
        $processingType = $currentApp['processing_type'] ?? 'Normal';

        if ($visaServiceId > 0 && $visaServiceId !== (int)$currentApp['visa_service_id']) {
            $srvStmt = $pdo->prepare("SELECT vs.*, ct.name as country_name, ct.iso_code as country_code, vc.name as category_name 
                FROM visa_services vs 
                JOIN countries ct ON vs.country_id = ct.id 
                LEFT JOIN visa_categories vc ON vs.category_id = vc.id 
                WHERE vs.id = ?");
            $srvStmt->execute([$visaServiceId]);
            $service = $srvStmt->fetch(PDO::FETCH_ASSOC);
            if ($service) {
                $destinationCountry = $service['country_name'] ?? $destinationCountry;
                $visaCategory = $service['category_name'] ?? $visaCategory;
                $visaType = $service['name'] ?? $visaType;
                $visaDuration = $service['duration'] ?? $visaDuration;
                $entryType = $service['entry_type'] ?? $entryType;
                $processingType = $service['processing_type'] ?? $processingType;
            }
        }

        $stmt = $pdo->prepare("UPDATE applications SET 
            visa_service_id = ?, passport_number = ?, travel_date = ?, return_date = ?, 
            application_date = ?, expected_completion_date = ?, priority = ?, 
            branch_id = ?, supplier_id = ?, assigned_staff_id = ?, 
            destination_country = ?, visa_category = ?, visa_type = ?, visa_duration = ?, entry_type = ?, processing_type = ?,
            selling_price = ?, supplier_cost = ?, other_expenses = ?, discount = ?, 
            discount_amount = ?, tax_amount = ?, total_amount = ?, balance_amount = ?, gross_profit = ?,
            internal_notes = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?");
        $stmt->execute([
            $visaServiceId, $passportNumber, $travelDate, $returnDate,
            $applicationDate, $expectedDate, $priority,
            $targetBranchId, $supplierId, $assignedStaffId,
            $destinationCountry, $visaCategory, $visaType, $visaDuration, $entryType, $processingType,
            $sellingPrice, $supplierCost, $otherExpenses, $discountAmount,
            $discountAmount, $taxAmount, $totalAmount, $balanceAmount, $grossProfit,
            $notes,
            $id
        ]);

        FinanceService::recalculateApplication($id);
        AuditService::log('APPLICATION_UPDATED', 'Applications', $id, "Updated application #{$id}", [
            'branch_id' => $targetBranchId,
            'assigned_staff_id' => $assignedStaffId,
            'priority' => $priority
        ], $user['id'] ?? null);

        redirect("/applications/show?id={$id}", "Application updated successfully.", 'success');
    }

    public function delete(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        if (!$user) {
            redirect('/login', 'Please sign in to continue.', 'danger');
        }

        $id = (int)($_POST['application_id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            redirect('/applications', 'Application not found.', 'danger');
        }

        // 1. Explicit application.delete permission check
        $isAdmin = is_super_admin($user) || is_admin($user);
        if (!$isAdmin && !user_can('applications.delete')) {
            redirect('/applications', 'Unauthorized: You do not have permission to delete applications.', 'danger');
        }

        // 2. Fetch application with branch info
        $stmt = $pdo->prepare("SELECT id, application_number, branch_id, customer_id, status FROM applications WHERE id = ?");
        $stmt->execute([$id]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            redirect('/applications', 'Application not found.', 'danger');
        }

        // 3. Branch isolation check
        $scopedBranchId = get_scoped_branch_id(0, $user);
        if ($scopedBranchId > 0 && (int)$app['branch_id'] !== $scopedBranchId) {
            redirect('/applications', 'Unauthorized: You cannot delete applications from another branch.', 'danger');
        }

        $reason = trim($_POST['delete_reason'] ?? $_POST['reason'] ?? 'Staff application deletion request');

        // 4. Verify whether dependent financial, document, or legal audit records exist
        $hasPayments = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE application_id = {$id}")->fetchColumn();
        $hasInvoices = (int)$pdo->query("SELECT COUNT(*) FROM invoices WHERE application_id = {$id}")->fetchColumn();
        $hasSupplierPayments = (int)$pdo->query("SELECT COUNT(*) FROM supplier_payments WHERE application_id = {$id}")->fetchColumn();
        $hasWalletTx = (int)$pdo->query("SELECT COUNT(*) FROM wallet_transactions WHERE application_id = {$id}")->fetchColumn();
        $hasDocuments = (int)$pdo->query("SELECT COUNT(*) FROM documents WHERE application_id = {$id}")->fetchColumn();
        $hasApprovals = (int)$pdo->query("SELECT COUNT(*) FROM visa_approvals WHERE application_id = {$id}")->fetchColumn();

        $hasFinancialOrLegalRecords = ($hasPayments > 0 || $hasInvoices > 0 || $hasSupplierPayments > 0 || $hasWalletTx > 0 || $hasDocuments > 0 || $hasApprovals > 0);

        if ($hasFinancialOrLegalRecords) {
            // SAFE ARCHIVE WORKFLOW: Preserve financial, ledger, and document history!
            $archiveNote = sprintf("\n[Archived by %s (ID: %d) on %s. Reason: %s]", $user['name'] ?? 'Staff', $user['id'] ?? 0, date('Y-m-d H:i:s'), $reason);
            $pdo->prepare("UPDATE applications SET is_archived = 1, status = 'Archived', internal_notes = CONCAT(COALESCE(internal_notes, ''), ?) WHERE id = ?")
                ->execute([$archiveNote, $id]);

            AuditService::log('APPLICATION_ARCHIVED', 'Applications', $id, "Archived application {$app['application_number']} (financial/legal history preserved). Reason: {$reason}", [
                'payments' => $hasPayments,
                'invoices' => $hasInvoices,
                'documents' => $hasDocuments,
                'reason' => $reason
            ], $user['id'] ?? null);

            redirect('/applications', "Application {$app['application_number']} has associated financial or document history and has been securely archived rather than permanently purged.", 'info');
            return;
        }

        // If NO financial or document records exist (e.g. an empty draft created by mistake):
        $pdo->beginTransaction();
        try {
            // Safely remove transient draft child records
            $pdo->prepare("DELETE FROM application_stages WHERE application_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM application_status_history WHERE application_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM application_assignments WHERE application_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM application_notes WHERE application_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM application_tasks WHERE application_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM application_returns WHERE application_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM document_requests WHERE application_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tasks WHERE application_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM appointments WHERE application_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM communications WHERE application_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM notifications WHERE application_id = ?")->execute([$id]);

            $pdo->prepare("DELETE FROM applications WHERE id = ?")->execute([$id]);
            $pdo->commit();

            AuditService::log('APPLICATION_DELETED', 'Applications', $id, "Deleted uncommitted application {$app['application_number']}. Reason: {$reason}", ['reason' => $reason], $user['id'] ?? null);

            redirect('/applications', "Application {$app['application_number']} deleted successfully.", 'success');
        } catch (\Throwable $e) {
            $pdo->rollBack();
            redirect('/applications', "Error deleting application: " . $e->getMessage(), 'danger');
        }
    }
}
