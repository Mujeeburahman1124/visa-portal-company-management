<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Services\AuditService;
use App\Services\DuplicateDetectionService;
use PDO;

class CustomerController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $search = trim($_GET['search'] ?? '');
        $nationality = trim($_GET['nationality'] ?? '');

        $sql = "SELECT c.*, 
                    cp.passport_number, cp.expiry_date as passport_expiry,
                    COUNT(a.id) as total_applications,
                    SUM(CASE WHEN a.status NOT IN ('Approved', 'Completed', 'Cancelled') THEN 1 ELSE 0 END) as active_applications
                FROM customers c 
                LEFT JOIN customer_passports cp ON cp.customer_id = c.id AND cp.is_primary = 1
                LEFT JOIN applications a ON a.customer_id = c.id
                WHERE c.is_active = 1";

        $params = [];
        if ($search !== '') {
            $sql .= " AND (c.full_name LIKE ? OR c.customer_code LIKE ? OR c.mobile LIKE ? OR c.email LIKE ? OR cp.passport_number LIKE ?)";
            $term = "%{$search}%";
            $params = array_fill(0, 5, $term);
        }

        if ($nationality !== '') {
            $sql .= " AND c.nationality = ?";
            $params[] = $nationality;
        }

        $sql .= " GROUP BY c.id ORDER BY c.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $customers = $stmt->fetchAll();

        $nationalities = $pdo->query("SELECT DISTINCT nationality FROM customers WHERE is_active = 1 ORDER BY nationality ASC")->fetchAll(PDO::FETCH_COLUMN);

        require_once dirname(__DIR__) . '/Views/customers/index.php';
    }

    public function create(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $countries = $pdo->query("SELECT id, name, iso_code, flag_emoji FROM countries WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $categories = $pdo->query("SELECT id, name, slug FROM visa_categories WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $services = $pdo->query("SELECT vs.*, ct.name as country_name, ct.flag_emoji, vc.name as category_name 
            FROM visa_services vs 
            JOIN countries ct ON vs.country_id = ct.id 
            LEFT JOIN visa_categories vc ON vs.category_id = vc.id 
            WHERE vs.is_active = 1 
            ORDER BY ct.name ASC, vs.name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $docTypes = $pdo->query("SELECT id, name, code, category FROM document_types WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $suppliers = $pdo->query("SELECT id, company_name, contact_person FROM suppliers WHERE is_active = 1 ORDER BY company_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $branches = $pdo->query("SELECT id, name, city FROM branches WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $staffMembers = $pdo->query("SELECT id, name, designation FROM users WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        require_once dirname(__DIR__) . '/Views/customers/create.php';
    }

    public function store(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $firstName = trim($_POST['first_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $fullName = trim($firstName . ($middleName ? ' ' . $middleName : '') . ' ' . $lastName);
        $gender = trim($_POST['gender'] ?? 'Male');
        $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
        $nationality = trim($_POST['nationality'] ?? '');
        $placeOfBirth = trim($_POST['place_of_birth'] ?? '');
        $maritalStatus = trim($_POST['marital_status'] ?? 'Single');
        $occupation = trim($_POST['occupation'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? $mobile);
        $email = trim($_POST['email'] ?? '');
        $currentCountry = trim($_POST['current_country'] ?? 'United Arab Emirates');
        $address = trim($_POST['address'] ?? '');
        $religion = trim($_POST['religion'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        // Family details
        $fatherName = trim($_POST['father_name'] ?? '');
        $fatherDob = !empty($_POST['father_dob']) ? $_POST['father_dob'] : null;
        $fatherCountryOfBirth = trim($_POST['father_country_of_birth'] ?? '');
        $fatherReligion = trim($_POST['father_religion'] ?? '');

        $motherName = trim($_POST['mother_name'] ?? '');
        $motherDob = !empty($_POST['mother_dob']) ? $_POST['mother_dob'] : null;
        $motherMobile = trim($_POST['mother_mobile'] ?? '');
        $motherReligion = trim($_POST['mother_religion'] ?? '');

        // Passport details
        $passportNumber = trim($_POST['passport_number'] ?? '');
        $passportIssuingCountry = trim($_POST['passport_issuing_country'] ?? $nationality);
        $passportIssueDate = !empty($_POST['passport_issue_date']) ? $_POST['passport_issue_date'] : null;
        $passportExpiryDate = !empty($_POST['passport_expiry_date']) ? $_POST['passport_expiry_date'] : null;
        $passportPlaceOfIssue = trim($_POST['passport_place_of_issue'] ?? '');

        // National ID details
        $nationalIdNumber = trim($_POST['national_id_number'] ?? '');
        $nationalIdType = trim($_POST['national_id_type'] ?? 'Emirates ID');
        $nationalIdIssueDate = !empty($_POST['national_id_issue_date']) ? $_POST['national_id_issue_date'] : null;
        $nationalIdExpiryDate = !empty($_POST['national_id_expiry_date']) ? $_POST['national_id_expiry_date'] : null;

        // Residence details
        $residenceCountry = trim($_POST['residence_country'] ?? '');
        $residencePermitNumber = trim($_POST['residence_permit_number'] ?? '');
        $residenceExpiryDate = !empty($_POST['residence_expiry_date']) ? $_POST['residence_expiry_date'] : null;
        $residenceEmployer = trim($_POST['residence_employer'] ?? '');
        $residenceJobTitle = trim($_POST['residence_job_title'] ?? '');

        if (empty($firstName) || empty($lastName) || empty($mobile) || empty($nationality)) {
            redirect('/customers/create', 'Please complete all required customer fields.', 'danger');
        }

        // Check for duplicates
        $dupCustomer = DuplicateDetectionService::checkCustomer($email, $mobile, $fullName, $dob);
        if (!empty($dupCustomer) && empty($_POST['force_duplicate'])) {
            $msg = $dupCustomer[0]['message'] . ' To proceed anyway, please confirm.';
            redirect('/customers/create', $msg, 'warning');
        }

        if (!empty($passportNumber)) {
            $dupPassport = DuplicateDetectionService::checkPassport($passportNumber);
            if (!empty($dupPassport) && empty($_POST['force_duplicate'])) {
                redirect('/customers/create', $dupPassport[0]['message'], 'warning');
            }
        }

        // Generate Customer Code: MSC-XXXXXX
        $count = (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn() + 1;
        $customerCode = sprintf("MSC-%06d", $count);
        $passwordHash = password_hash('customer123', PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO customers (
            customer_code, first_name, middle_name, last_name, full_name, gender, dob,
            nationality, place_of_birth, marital_status, occupation, religion, mobile, whatsapp,
            email, password_hash, current_country, address, created_by, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $customerCode, $firstName, $middleName, $lastName, $fullName, $gender, $dob,
            $nationality, $placeOfBirth, $maritalStatus, $occupation, $religion, $mobile, $whatsapp,
            $email, $passwordHash, $currentCountry, $address, $currentUser['id'], $notes
        ]);

        $customerId = (int)$pdo->lastInsertId();

        // Clean any existing orphan child records for this customer ID
        $pdo->prepare("DELETE FROM customer_family WHERE customer_id = ?")->execute([$customerId]);
        $pdo->prepare("DELETE FROM customer_passports WHERE customer_id = ?")->execute([$customerId]);
        $pdo->prepare("DELETE FROM customer_national_ids WHERE customer_id = ?")->execute([$customerId]);
        $pdo->prepare("DELETE FROM customer_residences WHERE customer_id = ?")->execute([$customerId]);

        // Insert Family Details into customer_family
        if (!empty($fatherName) || !empty($motherName) || !empty($fatherReligion) || !empty($motherReligion)) {
            $famStmt = $pdo->prepare("INSERT INTO customer_family (
                customer_id, father_name, father_dob, father_country_of_birth, father_nationality, father_religion,
                mother_name, mother_dob, mother_country_of_birth, mother_nationality, mother_religion, mother_mobile
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $famStmt->execute([
                $customerId,
                $fatherName, $fatherDob, $fatherCountryOfBirth, $fatherCountryOfBirth ?: $nationality, $fatherReligion,
                $motherName, $motherDob, null, $nationality, $motherReligion, $motherMobile
            ]);
        }

        // Handle File Upload Directory
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/customers/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $frontFile = null;
        if (!empty($_FILES['national_id_front']['name']) && $_FILES['national_id_front']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['national_id_front']['name'], PATHINFO_EXTENSION);
            $fileName = "nid_front_{$customerId}_" . time() . ".{$ext}";
            if (move_uploaded_file($_FILES['national_id_front']['tmp_name'], $uploadDir . $fileName)) {
                $frontFile = "/uploads/customers/{$fileName}";
            }
        }

        $backFile = null;
        if (!empty($_FILES['national_id_back']['name']) && $_FILES['national_id_back']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['national_id_back']['name'], PATHINFO_EXTENSION);
            $fileName = "nid_back_{$customerId}_" . time() . ".{$ext}";
            if (move_uploaded_file($_FILES['national_id_back']['tmp_name'], $uploadDir . $fileName)) {
                $backFile = "/uploads/customers/{$fileName}";
            }
        }

        // Insert Passport
        if (!empty($passportNumber)) {
            $passStmt = $pdo->prepare("INSERT INTO customer_passports (
                customer_id, passport_number, issuing_country, issue_date, expiry_date, place_of_issue, is_primary
            ) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $passStmt->execute([$customerId, $passportNumber, $passportIssuingCountry, $passportIssueDate, $passportExpiryDate ?: date('Y-m-d', strtotime('+5 years')), $passportPlaceOfIssue]);
        }

        // Insert National ID (Global / UAE Emirates ID)
        if (!empty($nationalIdNumber)) {
            $nidStmt = $pdo->prepare("INSERT INTO customer_national_ids (customer_id, id_number, id_type, issuing_country, issue_date, expiry_date, front_file, back_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $nidStmt->execute([$customerId, $nationalIdNumber, $nationalIdType, $currentCountry, $nationalIdIssueDate, $nationalIdExpiryDate, $frontFile, $backFile]);
        }

        // Insert Residence Details
        if (!empty($residencePermitNumber) || !empty($residenceCountry)) {
            $resStmt = $pdo->prepare("INSERT INTO customer_residences (customer_id, residence_country, permit_number, expiry_date, employer, job_title) VALUES (?, ?, ?, ?, ?, ?)");
            $resStmt->execute([$customerId, $residenceCountry ?: $currentCountry, $residencePermitNumber, $residenceExpiryDate, $residenceEmployer, $residenceJobTitle ?: $occupation]);
        }

        // Process Unlimited Multi-Document Uploads (Part 2, 3, 4)
        if (!empty($_FILES['applicant_documents']['name']) && is_array($_FILES['applicant_documents']['name'])) {
            $docTypes = $_POST['document_types'] ?? [];
            $docTitles = $_POST['document_titles'] ?? [];
            $docsUploadDir = dirname(__DIR__, 2) . '/public/uploads/documents/';
            if (!is_dir($docsUploadDir)) {
                @mkdir($docsUploadDir, 0777, true);
            }

            $docStmt = $pdo->prepare("INSERT INTO documents (
                customer_id, document_type_id, document_title, file_path, file_name, file_size, mime_type, version, status, uploaded_by_type, uploaded_by_id, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'Verified', 'Staff', ?, NOW())");

            foreach ($_FILES['applicant_documents']['name'] as $idx => $origName) {
                if (!empty($origName) && $_FILES['applicant_documents']['error'][$idx] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['applicant_documents']['tmp_name'][$idx];
                    $fileSize = (int)$_FILES['applicant_documents']['size'][$idx];
                    $mimeType = $_FILES['applicant_documents']['type'][$idx] ?? 'application/octet-stream';
                    $ext = pathinfo($origName, PATHINFO_EXTENSION);
                    $savedName = "doc_{$customerId}_" . time() . "_{$idx}." . $ext;
                    $targetPath = $docsUploadDir . $savedName;

                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $typeId = !empty($docTypes[$idx]) ? (int)$docTypes[$idx] : null;
                        $title = !empty($docTitles[$idx]) ? trim($docTitles[$idx]) : pathinfo($origName, PATHINFO_FILENAME);
                        $filePath = "/uploads/documents/{$savedName}";

                        $docStmt->execute([$customerId, $typeId, $title, $filePath, $origName, $fileSize, $mimeType, $currentUser['id'] ?? null]);
                    }
                }
            }
        }

        AuditService::log('CREATE', 'Customers', $customerId, "Registered new customer {$customerCode} - {$fullName}");

        // Handle Optional Visa Application Creation in New Applicant Form
        $hasVisaDetails = !empty($_POST['visa_service_id']) || !empty($_POST['custom_visa_type']) || !empty($_POST['country_id']) || !empty($_POST['custom_destination_country']) || !empty($_POST['destination_country']);
        $appId = null;
        $appNumber = null;

        if ($hasVisaDetails) {
            $serviceId = !empty($_POST['visa_service_id']) ? (int)$_POST['visa_service_id'] : null;
            $countryId = !empty($_POST['country_id']) ? (int)$_POST['country_id'] : null;
            $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

            // Fetch Service / Country / Category meta if selected
            $serviceData = null;
            if ($serviceId) {
                $sStmt = $pdo->prepare("SELECT vs.*, ct.name as country_name, vc.name as category_name FROM visa_services vs JOIN countries ct ON vs.country_id = ct.id LEFT JOIN visa_categories vc ON vs.category_id = vc.id WHERE vs.id = ?");
                $sStmt->execute([$serviceId]);
                $serviceData = $sStmt->fetch(PDO::FETCH_ASSOC);
            }

            $countryName = '';
            if (!empty($_POST['custom_destination_country'])) {
                $countryName = trim($_POST['custom_destination_country']);
            } elseif ($countryId) {
                $cStmt = $pdo->prepare("SELECT name FROM countries WHERE id = ?");
                $cStmt->execute([$countryId]);
                $countryName = (string)($cStmt->fetchColumn() ?: '');
            } elseif (!empty($_POST['destination_country'])) {
                $countryName = trim($_POST['destination_country']);
            } elseif ($serviceData) {
                $countryName = (string)($serviceData['country_name'] ?? '');
            }

            $categoryName = '';
            if (!empty($_POST['custom_visa_category'])) {
                $categoryName = trim($_POST['custom_visa_category']);
            } elseif ($categoryId) {
                $catStmt = $pdo->prepare("SELECT name FROM visa_categories WHERE id = ?");
                $catStmt->execute([$categoryId]);
                $categoryName = (string)($catStmt->fetchColumn() ?: '');
            } elseif (!empty($_POST['visa_category'])) {
                $categoryName = trim($_POST['visa_category']);
            } elseif ($serviceData) {
                $categoryName = (string)($serviceData['category_name'] ?? 'General');
            }

            $visaTypeName = '';
            if (!empty($_POST['custom_visa_type'])) {
                $visaTypeName = trim($_POST['custom_visa_type']);
            } elseif (!empty($_POST['visa_type'])) {
                $visaTypeName = trim($_POST['visa_type']);
            } elseif ($serviceData) {
                $visaTypeName = (string)($serviceData['name'] ?? 'Standard Visa');
            } else {
                $visaTypeName = 'Standard Visa';
            }

            $duration = trim($_POST['custom_visa_duration'] ?? '') ?: trim($_POST['visa_duration'] ?? ($serviceData['duration'] ?? '30 Days'));
            $entryType = trim($_POST['custom_entry_type'] ?? '') ?: trim($_POST['entry_type'] ?? ($serviceData['entry_type'] ?? 'Single Entry'));
            $processingType = trim($_POST['custom_processing_type'] ?? '') ?: trim($_POST['processing_type'] ?? ($serviceData['processing_type'] ?? 'Normal'));
            $travelDate = !empty($_POST['travel_date']) ? $_POST['travel_date'] : null;
            $returnDate = !empty($_POST['return_date']) ? $_POST['return_date'] : null;
            $priority = in_array($_POST['priority'] ?? '', ['Critical', 'Urgent', 'High', 'Normal'], true) ? $_POST['priority'] : 'Normal';
            $branchId = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : ($currentUser['branch_id'] ?? 1);
            $assignedStaffId = !empty($_POST['assigned_staff_id']) ? (int)$_POST['assigned_staff_id'] : ($currentUser['id'] ?? null);
            $supplierId = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
            $internalNotes = trim($_POST['internal_notes'] ?? '');
            $customerNotes = trim($_POST['customer_notes'] ?? '');
            $supplierRef = trim($_POST['supplier_reference'] ?? '');
            $embassyRef = trim($_POST['embassy_reference'] ?? '');

            // Financial Calculations
            $sellingPrice = (float)($_POST['selling_price'] ?? ($serviceData['selling_price'] ?? 0.0));
            $supplierCost = (float)($_POST['supplier_cost'] ?? ($serviceData['supplier_cost'] ?? 0.0));
            $discount = (float)($_POST['discount'] ?? 0.0);
            $otherExpenses = (float)($_POST['other_expenses'] ?? 0.0);
            $taxRate = (float)($serviceData['tax_rate'] ?? 5.0);
            $netSellingPrice = max(0.0, $sellingPrice - $discount);
            $taxAmount = (float)($_POST['tax_amount'] ?? ($netSellingPrice * ($taxRate / 100.0)));
            $totalAmount = max(0.0, $netSellingPrice + $taxAmount);
            $grossProfit = max(0.0, $totalAmount - $supplierCost - $otherExpenses);

            // Payment Option (Default Pay Later)
            $isPayNow = !empty($_POST['pay_now']) && (string)$_POST['pay_now'] === '1';
            $paymentType = $isPayNow ? 'Pay Now' : 'Pay Later';
            $paymentStatus = 'Unpaid';
            $paidAmount = 0.00;
            $balanceAmount = $totalAmount;

            // Generate Application Number: MSV-YYYY-XXXXXX
            $year = date('Y');
            $countStmt = $pdo->query("SELECT COUNT(*) FROM applications");
            $nextAppNum = ((int)$countStmt->fetchColumn()) + 1;
            $appNumber = sprintf("MSV-%s-%06d", $year, $nextAppNum);

            // Verify unique application number
            $chkApp = $pdo->prepare("SELECT id FROM applications WHERE application_number = ?");
            $chkApp->execute([$appNumber]);
            if ($chkApp->fetch()) {
                $appNumber = sprintf("MSV-%s-%06d", $year, $nextAppNum + rand(10, 99));
            }

            $procDays = (int)($serviceData['estimated_days'] ?? 15);
            $appDate = date('Y-m-d');
            $expectedCompletionDate = date('Y-m-d', strtotime("+{$procDays} days"));
            $nextActionDue = date('Y-m-d', strtotime('+3 days'));

            $insAppStmt = $pdo->prepare("INSERT INTO applications (
                application_number, customer_id, visa_service_id, branch_id, assigned_staff_id, supplier_id,
                current_stage, status, priority, calculated_health, health_reason,
                nationality, residence_country, passport_number,
                destination_country, visa_category, visa_type, visa_duration, entry_type, processing_type,
                application_date, expected_completion_date, travel_date, return_date,
                selling_price, discount, tax_amount, total_amount, paid_amount, balance_amount,
                supplier_cost, other_expenses, gross_profit, supplier_reference, embassy_reference,
                internal_notes, customer_notes, next_action, next_action_due_date, payment_type, payment_status, created_by
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                'New Application', 'Draft', ?, 100, 'Applicant registered with visa requirements.',
                ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, 'Collect and verify initial required documents', ?, ?, ?, ?
            )");

            $insAppStmt->execute([
                $appNumber, $customerId, $serviceId, $branchId, $assignedStaffId, $supplierId,
                $priority,
                $nationality, $currentCountry, $passportNumber,
                $countryName, $categoryName, $visaTypeName, $duration, $entryType, $processingType,
                $appDate, $expectedCompletionDate, $travelDate, $returnDate,
                $sellingPrice, $discount, $taxAmount, $totalAmount, $paidAmount, $balanceAmount,
                $supplierCost, $otherExpenses, $grossProfit, $supplierRef, $embassyRef,
                $internalNotes, $customerNotes, $nextActionDue, $paymentType, $paymentStatus, $currentUser['id'] ?? null
            ]);

            $appId = (int)$pdo->lastInsertId();

            // Link uploaded documents to application_id as well
            if (!empty($appId)) {
                $pdo->prepare("UPDATE documents SET application_id = ? WHERE customer_id = ? AND application_id IS NULL")->execute([$appId, $customerId]);
            }

            // Insert initial status history record
            $histStmt = $pdo->prepare("INSERT INTO application_status_history (
                application_id, from_stage, to_stage, from_status, to_status, comments, changed_by
            ) VALUES (?, 'Initiation', 'Application Registered', 'Draft', 'Registered', 'Applicant profile and visa application case registered.', ?)");
            $histStmt->execute([$appId, $currentUser['id'] ?? null]);

            // Auto-generate Invoice: INV-YYYY-XXXXXX
            $invCount = (int)$pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn() + 1;
            $invNumber = sprintf("INV-%s-%06d", $year, $invCount);
            $invDueDate = date('Y-m-d', strtotime('+7 days'));

            $insInv = $pdo->prepare("INSERT INTO invoices (
                invoice_number, application_id, customer_id, issue_date, due_date,
                subtotal, discount, tax_rate, tax_amount, total_amount, paid_amount, balance_amount,
                status, notes, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0.00, ?, 'Unpaid', ?, ?)");

            $insInv->execute([
                $invNumber, $appId, $customerId, $appDate, $invDueDate,
                $sellingPrice, $discount, $taxRate, $taxAmount, $totalAmount, $totalAmount,
                "Visa Application Invoice for {$visaTypeName} ({$countryName})", $currentUser['id'] ?? null
            ]);
            $invoiceId = (int)$pdo->lastInsertId();

            // Process Immediate Payment if Pay Now was selected
            if ($isPayNow && $totalAmount > 0) {
                $payMethod = trim($_POST['pay_method'] ?? 'Cash');
                $payRef = trim($_POST['pay_reference'] ?? '');
                $payAmount = $totalAmount;

                if ($payMethod === 'Customer Wallet') {
                    $walletResult = \App\Services\WalletService::debit(
                        $customerId,
                        $payAmount,
                        "Visa Registration Payment for {$appNumber} ({$visaTypeName})",
                        $appId
                    );

                    if ($walletResult['success']) {
                        $wtxId = $walletResult['transaction_id'] ?? 'WALLET_TXN';
                        $rcpCount = (int)$pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn() + 1;
                        $rcpNum = sprintf("RCP-%s-%06d", $year, $rcpCount);

                        $pdo->prepare("INSERT INTO payments (
                            payment_number, invoice_number, application_id, customer_id, supplier_id,
                            amount, currency, payment_date, payment_method, transaction_reference,
                            wallet_transaction_id, payment_type, status, received_by, notes
                        ) VALUES (?, ?, ?, ?, ?, ?, 'USD', ?, 'Customer Wallet', ?, ?, 'Customer Payment', 'Completed', ?, ?)")->execute([
                            $rcpNum, $invNumber, $appId, $customerId, $supplierId,
                            $payAmount, $appDate, $wtxId, $wtxId, $currentUser['id'] ?? null, "Settled via Customer Digital Wallet at Registration"
                        ]);
                        $paymentId = (int)$pdo->lastInsertId();

                        // Update invoice & application
                        $pdo->prepare("UPDATE invoices SET paid_amount = ?, balance_amount = 0.00, status = 'Paid' WHERE id = ?")->execute([$payAmount, $invoiceId]);
                        $pdo->prepare("UPDATE applications SET paid_amount = ?, balance_amount = 0.00, payment_status = 'Paid' WHERE id = ?")->execute([$payAmount, $appId]);

                        AuditService::log('PAYMENT', 'Payments', $paymentId, "Recorded wallet payment {$rcpNum} for {$appNumber}");
                    }
                } elseif ($payMethod === 'Cash' || $payMethod === 'Bank Transfer' || $payMethod === 'POS Card' || $payMethod === 'Credit Card') {
                    $rcpCount = (int)$pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn() + 1;
                    $rcpNum = sprintf("RCP-%s-%06d", $year, $rcpCount);

                    $pdo->prepare("INSERT INTO payments (
                        payment_number, invoice_number, application_id, customer_id, supplier_id,
                        amount, currency, payment_date, payment_method, transaction_reference,
                        payment_type, status, received_by, notes
                    ) VALUES (?, ?, ?, ?, ?, ?, 'USD', ?, ?, ?, 'Customer Payment', 'Completed', ?, ?)")->execute([
                        $rcpNum, $invNumber, $appId, $customerId, $supplierId,
                        $payAmount, $appDate, $payMethod, $payRef ?: 'BRANCH_DIRECT', $currentUser['id'] ?? null, "Settled at Registration via {$payMethod}"
                    ]);
                    $paymentId = (int)$pdo->lastInsertId();

                    // Update invoice & application
                    $pdo->prepare("UPDATE invoices SET paid_amount = ?, balance_amount = 0.00, status = 'Paid' WHERE id = ?")->execute([$payAmount, $invoiceId]);
                    $pdo->prepare("UPDATE applications SET paid_amount = ?, balance_amount = 0.00, payment_status = 'Paid' WHERE id = ?")->execute([$payAmount, $appId]);

                    AuditService::log('PAYMENT', 'Payments', $paymentId, "Recorded direct payment {$rcpNum} for {$appNumber}");
                }
            }
        }

        // Dispatch Central Real-Time Notification for Applicant Registration
        try {
            \App\Services\NotificationService::trigger('applicant.registered', [
                'customer_id' => $customerId,
                'customerCode' => $customerCode,
                'applicantName' => $fullName,
                'applicantEmail' => $email,
                'applicantPhone' => $whatsapp ?: $mobile,
                'mobile' => $mobile,
                'whatsapp' => $whatsapp,
                'nationality' => $nationality,
                'applicationNumber' => $appNumber ?? '',
                'loginUrl' => (string)\App\Config\Env::get('APP_URL', 'http://localhost:8000') . "/portal/login",
            ]);
        } catch (\Throwable $e) {}

        if (!empty($appId)) {
            redirect("/applications/show?id={$appId}", "Applicant {$customerCode} and Visa Application {$appNumber} registered successfully.", 'success');
        }

        redirect("/customers/show?id={$customerId}", "Applicant {$customerCode} registered successfully.", 'success');
    }

    public function show(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            redirect('/customers', 'Customer not found.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $customer = $stmt->fetch();

        if (!$customer) {
            redirect('/customers', 'Customer not found.', 'danger');
        }

        $passports = $pdo->query("SELECT * FROM customer_passports WHERE customer_id = {$id} ORDER BY is_primary DESC")->fetchAll();
        $nationalIds = $pdo->query("SELECT * FROM customer_national_ids WHERE customer_id = {$id}")->fetchAll();
        $residences = $pdo->query("SELECT * FROM customer_residences WHERE customer_id = {$id}")->fetchAll();
        $family = $pdo->query("SELECT * FROM customer_family WHERE customer_id = {$id}")->fetch(PDO::FETCH_ASSOC);
        $wallet = \App\Services\WalletService::getOrCreateWallet($id);

        // Linked Applications
        $appStmt = $pdo->prepare("SELECT a.*, vs.name as service_name, ct.name as country_name, ct.flag_emoji, u.name as staff_name 
            FROM applications a 
            JOIN visa_services vs ON a.visa_service_id = vs.id 
            JOIN countries ct ON vs.country_id = ct.id 
            LEFT JOIN users u ON a.assigned_staff_id = u.id 
            WHERE a.customer_id = ? ORDER BY a.created_at DESC");
        $appStmt->execute([$id]);
        $applications = $appStmt->fetchAll();

        // Customer Documents
        $docStmt = $pdo->prepare("SELECT d.*, dt.name as doc_type_name, a.application_number 
            FROM documents d 
            JOIN document_types dt ON d.document_type_id = dt.id 
            LEFT JOIN applications a ON d.application_id = a.id 
            WHERE d.customer_id = ? ORDER BY d.created_at DESC");
        $docStmt->execute([$id]);
        $documents = $docStmt->fetchAll();

        // Tasks & Communications
        $tasks = $pdo->query("SELECT t.*, u.name as assigned_to_name FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.customer_id = {$id} ORDER BY t.due_date ASC")->fetchAll();
        $communications = $pdo->query("SELECT c.*, u.name as staff_name FROM communications c LEFT JOIN users u ON c.staff_id = u.id WHERE c.customer_id = {$id} ORDER BY c.recorded_at DESC")->fetchAll();
        $payments = $pdo->query("SELECT p.*, a.application_number FROM payments p LEFT JOIN applications a ON p.application_id = a.id WHERE p.customer_id = {$id} ORDER BY p.payment_date DESC")->fetchAll();

        require_once dirname(__DIR__) . '/Views/customers/show.php';
    }

    /**
     * Real-time duplicate check endpoint for AJAX
     */
    public function checkDuplicate(): void
    {
        AuthMiddleware::handle();
        $email = trim($_GET['email'] ?? '');
        $mobile = trim($_GET['mobile'] ?? '');
        $name = trim($_GET['name'] ?? '');
        $dob = trim($_GET['dob'] ?? '');
        $passport = trim($_GET['passport'] ?? '');
        $excludeId = !empty($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : null;

        $results = [];
        if ($email || $mobile || $name) {
            $custDups = DuplicateDetectionService::checkCustomer($email, $mobile, $name, $dob, $excludeId);
            $results = array_merge($results, $custDups);
        }
        if ($passport) {
            $passDups = DuplicateDetectionService::checkPassport($passport, null, $excludeId);
            $results = array_merge($results, $passDups);
        }

        json_response([
            'has_duplicates' => !empty($results),
            'duplicates' => $results,
        ]);
    }

    public function edit(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            redirect('/customers', 'Customer not found.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $customer = $stmt->fetch();

        if (!$customer) {
            redirect('/customers', 'Customer not found.', 'danger');
        }

        $primaryPassport = $pdo->query("SELECT * FROM customer_passports WHERE customer_id = {$id} AND is_primary = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
        $family = $pdo->query("SELECT * FROM customer_family WHERE customer_id = {$id} LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
        $countries = $pdo->query("SELECT id, name, iso_code, flag_emoji FROM countries WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $branches = $pdo->query("SELECT id, name, city FROM branches WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        require_once dirname(__DIR__) . '/Views/customers/edit.php';
    }

    public function update(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            redirect('/customers', 'Customer not found.', 'danger');
        }

        $firstName = trim($_POST['first_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $fullName = trim($firstName . ($middleName ? ' ' . $middleName : '') . ' ' . $lastName);
        $gender = trim($_POST['gender'] ?? 'Male');
        $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
        $nationality = trim($_POST['nationality'] ?? '');
        $placeOfBirth = trim($_POST['place_of_birth'] ?? '');
        $maritalStatus = trim($_POST['marital_status'] ?? 'Single');
        $occupation = trim($_POST['occupation'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? $mobile);
        $email = trim($_POST['email'] ?? '');
        $currentCountry = trim($_POST['current_country'] ?? 'United Arab Emirates');
        $address = trim($_POST['address'] ?? '');
        $religion = trim($_POST['religion'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        $stmt = $pdo->prepare("UPDATE customers SET 
            first_name = ?, middle_name = ?, last_name = ?, full_name = ?, 
            gender = ?, dob = ?, nationality = ?, place_of_birth = ?, 
            marital_status = ?, occupation = ?, mobile = ?, whatsapp = ?, 
            email = ?, current_country = ?, address = ?, religion = ?, 
            notes = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?");
        $stmt->execute([
            $firstName, $middleName, $lastName, $fullName,
            $gender, $dob, $nationality, $placeOfBirth,
            $maritalStatus, $occupation, $mobile, $whatsapp,
            $email, $currentCountry, $address, $religion,
            $notes, $id
        ]);

        // Update primary passport
        $passportNumber = trim($_POST['passport_number'] ?? '');
        if (!empty($passportNumber)) {
            $passportExpiry = !empty($_POST['passport_expiry']) ? $_POST['passport_expiry'] : null;
            $passportIssue = !empty($_POST['passport_issue_date']) ? $_POST['passport_issue_date'] : null;
            $passportCountry = trim($_POST['passport_country'] ?? $nationality);

            $existingPass = $pdo->query("SELECT id FROM customer_passports WHERE customer_id = {$id} AND is_primary = 1 LIMIT 1")->fetch();
            if ($existingPass) {
                $pdo->prepare("UPDATE customer_passports SET passport_number = ?, expiry_date = ?, issue_date = ?, issuing_country = ? WHERE id = ?")
                    ->execute([$passportNumber, $passportExpiry, $passportIssue, $passportCountry, $existingPass['id']]);
            } else {
                $pdo->prepare("INSERT INTO customer_passports (customer_id, passport_number, expiry_date, issue_date, issuing_country, is_primary) VALUES (?, ?, ?, ?, ?, 1)")
                    ->execute([$id, $passportNumber, $passportExpiry, $passportIssue, $passportCountry]);
            }
        }

        // Update family details
        $fatherName = trim($_POST['father_name'] ?? '');
        $fatherDob = !empty($_POST['father_dob']) ? $_POST['father_dob'] : null;
        $fatherNationality = trim($_POST['father_nationality'] ?? '');
        $motherName = trim($_POST['mother_name'] ?? '');
        $motherDob = !empty($_POST['mother_dob']) ? $_POST['mother_dob'] : null;
        $motherNationality = trim($_POST['mother_nationality'] ?? '');

        $existingFamily = $pdo->query("SELECT id FROM customer_family WHERE customer_id = {$id} LIMIT 1")->fetch();
        if ($existingFamily) {
            $pdo->prepare("UPDATE customer_family SET father_name = ?, father_dob = ?, father_nationality = ?, mother_name = ?, mother_dob = ?, mother_nationality = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                ->execute([$fatherName, $fatherDob, $fatherNationality, $motherName, $motherDob, $motherNationality, $existingFamily['id']]);
        } elseif ($fatherName || $motherName) {
            $pdo->prepare("INSERT INTO customer_family (customer_id, father_name, father_dob, father_nationality, mother_name, mother_dob, mother_nationality) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute([$id, $fatherName, $fatherDob, $fatherNationality, $motherName, $motherDob, $motherNationality]);
        }

        AuditService::log('CUSTOMER_UPDATED', 'Customers', $id, "Updated customer profile: {$fullName}", [], $user['id'] ?? null);

        redirect("/customers/show?id={$id}", "Customer profile updated successfully.", 'success');
    }

    public function delete(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $user = auth_user();

        $id = (int)($_POST['customer_id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            redirect('/customers', 'Customer not found.', 'danger');
        }

        $cust = $pdo->query("SELECT full_name, customer_code FROM customers WHERE id = {$id}")->fetch();
        if (!$cust) {
            redirect('/customers', 'Customer not found.', 'danger');
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM applications WHERE customer_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM customer_passports WHERE customer_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM customer_national_ids WHERE customer_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM customer_residences WHERE customer_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM customer_family WHERE customer_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM customer_wallets WHERE customer_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM wallet_transactions WHERE customer_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM payment_links WHERE customer_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM payments WHERE customer_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM refunds WHERE customer_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM documents WHERE customer_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM document_requests WHERE customer_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tasks WHERE customer_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM appointments WHERE customer_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM communications WHERE customer_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM customers WHERE id = ?")->execute([$id]);
            $pdo->commit();

            AuditService::log('CUSTOMER_DELETED', 'Customers', $id, "Deleted customer {$cust['customer_code']} ({$cust['full_name']})", [], $user['id'] ?? null);

            redirect('/customers', "Customer {$cust['customer_code']} deleted successfully.", 'success');
        } catch (\Throwable $e) {
            $pdo->rollBack();
            redirect('/customers', "Error deleting customer: " . $e->getMessage(), 'danger');
        }
    }
}
