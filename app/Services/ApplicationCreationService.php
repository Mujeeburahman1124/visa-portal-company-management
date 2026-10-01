<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use App\Services\VisaRuleEngineService;
use App\Services\DocumentChecklistService;
use App\Services\HealthCalculatorService;
use App\Services\AuditService;
use PDO;
use Exception;

class ApplicationCreationService
{
    /**
     * Centralized, authoritative application creation service.
     * Enforces visa rule engine resolution, concurrency-safe numbering,
     * backend tax and net selling price calculations, and document checklist generation.
     */
    public static function create(array $data, ?int $userId = null): array
    {
        $pdo = Database::getConnection();

        $customerId = (int)($data['customer_id'] ?? 0);
        $serviceId = (int)($data['visa_service_id'] ?? 0);
        $branchId = (int)($data['branch_id'] ?? 1);
        if ($branchId <= 0) $branchId = 1;

        if ($customerId <= 0) {
            throw new Exception("Customer ID is required.");
        }

        // 1. Fetch customer details
        $custStmt = $pdo->prepare("SELECT c.*, cp.passport_number as primary_passport, cp.expiry_date as primary_passport_expiry 
            FROM customers c 
            LEFT JOIN customer_passports cp ON c.id = cp.customer_id AND cp.is_primary = 1 
            WHERE c.id = ?");
        $custStmt->execute([$customerId]);
        $customer = $custStmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            throw new Exception("Applicant record not found.");
        }

        // 2. Fetch service details
        $service = null;
        if ($serviceId > 0) {
            $srvStmt = $pdo->prepare("SELECT vs.*, ct.name as country_name, ct.iso_code as country_code 
                FROM visa_services vs 
                JOIN countries ct ON vs.country_id = ct.id 
                WHERE vs.id = ?");
            $srvStmt->execute([$serviceId]);
            $service = $srvStmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$service) {
            throw new Exception("Valid visa service must be selected.");
        }

        // 3. Rule Engine resolution & eligibility check
        $nationality = $customer['nationality'] ?? 'Unknown';
        $residence = $customer['current_country'] ?? 'United Arab Emirates';
        $ruleResult = VisaRuleEngineService::resolve((int)$service['country_id'], $serviceId, $nationality, $residence);

        if (!$ruleResult['is_eligible'] && empty($data['confirm_ineligible'])) {
            throw new Exception("Applicant is ineligible under visa rules: " . ($ruleResult['reason'] ?? 'Category restriction.'));
        }

        // 4. Authoritative pricing calculation (no frontend tampering)
        $sellingPrice = (float)($ruleResult['selling_price'] ?? $service['selling_price'] ?? 0.0);
        $supplierCost = (float)($ruleResult['supplier_cost'] ?? $service['supplier_cost'] ?? 0.0);

        if (!empty($data['custom_selling_price']) && (float)$data['custom_selling_price'] > 0) {
            $sellingPrice = (float)$data['custom_selling_price'];
        }
        if (!empty($data['custom_supplier_cost']) && (float)$data['custom_supplier_cost'] > 0) {
            $supplierCost = (float)$data['custom_supplier_cost'];
        }

        $discount = max(0.0, (float)($data['discount'] ?? 0.0));
        if ($discount > $sellingPrice) {
            $discount = $sellingPrice;
        }

        $otherExpenses = max(0.0, (float)($data['other_expenses'] ?? 0.0));
        $taxRate = (float)($service['tax_rate'] ?? 0.0);
        $netSellingPrice = max(0.0, $sellingPrice - $discount);
        $taxAmount = round($netSellingPrice * ($taxRate / 100.0), 2);
        $totalAmount = round($netSellingPrice + $taxAmount, 2);
        $grossProfit = round($totalAmount - $supplierCost - $otherExpenses, 2);

        // 5. Processing dates
        $procDays = (int)($ruleResult['processing_days'] ?? ($service['estimated_days'] ?? 15));
        $appDate = date('Y-m-d');
        $expectedCompletionDate = date('Y-m-d', strtotime("+{$procDays} days"));

        // 6. Concurrency-safe Application Number
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

        $priority = $data['priority'] ?? 'Normal';
        $assignedStaffId = !empty($data['assigned_staff_id']) ? (int)$data['assigned_staff_id'] : null;
        $supplierId = !empty($data['supplier_id']) ? (int)$data['supplier_id'] : ($ruleResult['preferred_supplier_id'] ?? null);

        $countryName = $data['destination_country'] ?? ($service['country_name'] ?? '');
        $categoryName = $data['visa_category'] ?? ($service['category_name'] ?? 'General');
        $visaTypeName = $data['visa_type'] ?? ($service['name'] ?? 'Standard Visa');
        $duration = $data['visa_duration'] ?? ($service['duration'] ?? '30 Days');
        $entryType = $data['entry_type'] ?? ($service['entry_type'] ?? 'Single Entry');
        $processingType = $data['processing_type'] ?? ($service['processing_type'] ?? 'Normal');

        $isPayNow = !empty($data['pay_now']) && ((string)$data['pay_now'] === '1' || (string)$data['pay_now'] === 'on');
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
                internal_notes, customer_notes, next_action, next_action_due_date, payment_type, payment_status, created_by
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                'New Application', 'Draft', ?, 100, 'Application registered.',
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, 0.00, ?,
                ?, ?, ?, ?, ?,
                ?, ?, 'Collect and verify initial required documents', ?, ?, ?, ?
            )";

            $nextActionDue = date('Y-m-d', strtotime('+3 days'));
            $travelDate = !empty($data['travel_date']) ? $data['travel_date'] : null;
            $returnDate = !empty($data['return_date']) ? $data['return_date'] : null;
            $passportNum = $data['passport_number'] ?? ($customer['primary_passport'] ?? $customer['passport_number'] ?? '');
            $passportExp = $data['passport_expiry_date'] ?? ($customer['primary_passport_expiry'] ?? $customer['passport_expiry'] ?? null);

            $insStmt = $pdo->prepare($insSql);
            $insStmt->execute([
                $appNumber, $customerId, $serviceId, $branchId, $assignedStaffId, $supplierId,
                $priority,
                $nationality, $residence, $passportNum, $passportExp,
                $countryName, $categoryName, $visaTypeName, $duration, $entryType, $processingType,
                $appDate, $expectedCompletionDate, $travelDate, $returnDate,
                $sellingPrice, $discount, $taxAmount, $totalAmount, $totalAmount,
                $supplierCost, $otherExpenses, $grossProfit, $data['supplier_reference'] ?? null, $data['embassy_reference'] ?? null,
                $data['internal_notes'] ?? null, $data['customer_notes'] ?? null, $nextActionDue, $paymentType, $paymentStatus, $userId
            ]);

            $appId = (int)$pdo->lastInsertId();

            // Initial status history
            $histStmt = $pdo->prepare("INSERT INTO application_status_history (
                application_id, from_stage, to_stage, from_status, to_status, comments, changed_by
            ) VALUES (?, 'Initiation', 'Application Registered', 'Draft', 'Registered', 'Application file created and initialized in operations system.', ?)");
            $histStmt->execute([$appId, $userId]);

            // Document checklist generation
            DocumentChecklistService::generateForApplication($appId, $serviceId);

            // Invoice generation
            $invCount = (int)$pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn() + 1;
            $invNumber = sprintf("INV-%s-%06d", date('Y'), $invCount);
            $dueDate = date('Y-m-d', strtotime('+7 days'));

            $insInv = $pdo->prepare("INSERT INTO invoices (
                invoice_number, application_id, customer_id, issue_date, due_date,
                subtotal, discount, tax_rate, tax_amount, total_amount, paid_amount, balance_amount,
                status, notes, created_by, created_at
            ) VALUES (?, ?, ?, CURRENT_DATE, ?, ?, ?, ?, ?, ?, 0.00, ?, 'Unpaid', 'Initial Application Invoice', ?, CURRENT_TIMESTAMP)");
            $insInv->execute([
                $invNumber, $appId, $customerId, $dueDate,
                $sellingPrice, $discount, $taxRate, $taxAmount, $totalAmount, $totalAmount,
                $userId
            ]);

            // Calculate health score
            HealthCalculatorService::calculate($appId);

            AuditService::log('CREATE_APPLICATION', 'Applications', $appId, "Created application {$appNumber} for {$customer['full_name']}", [
                'application_number' => $appNumber,
                'total_amount' => $totalAmount,
                'service' => $visaTypeName
            ], $userId);

            $pdo->commit();

            return [
                'success' => true,
                'application_id' => $appId,
                'application_number' => $appNumber,
                'total_amount' => $totalAmount,
                'invoice_number' => $invNumber,
                'message' => "Application {$appNumber} created successfully."
            ];

        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
