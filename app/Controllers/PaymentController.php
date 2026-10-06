<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\App;
use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\AuditService;
use App\Services\FinanceService;
use PDO;

class PaymentController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'accounts', 'visa-manager']);
        $pdo = Database::getConnection();

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $method = trim($_GET['method'] ?? '');
        $currency = trim($_GET['currency'] ?? '');
        $minAmount = !empty($_GET['min_amount']) ? (float)$_GET['min_amount'] : null;
        $maxAmount = !empty($_GET['max_amount']) ? (float)$_GET['max_amount'] : null;
        $branchId = get_scoped_branch_id((int)($_GET['branch_id'] ?? 0));
        $supplierId = (int)($_GET['supplier_id'] ?? 0);
        $countryId = (int)($_GET['country_id'] ?? 0);
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');

        $sql = "SELECT p.*, 
                    a.application_number, a.id as app_id, a.passport_number,
                    c.full_name as customer_name, c.customer_code, c.mobile as customer_mobile,
                    vs.name as service_name,
                    ct.name as country_name, ct.flag_emoji,
                    s.company_name as supplier_name,
                    u.name as received_by_name
                FROM payments p
                LEFT JOIN applications a ON p.application_id = a.id
                LEFT JOIN customers c ON p.customer_id = c.id
                LEFT JOIN visa_services vs ON a.visa_service_id = vs.id
                LEFT JOIN countries ct ON vs.country_id = ct.id
                LEFT JOIN suppliers s ON a.supplier_id = s.id
                LEFT JOIN users u ON p.received_by = u.id
                WHERE 1=1";

        $params = [];
        if ($search !== '') {
            $sql .= " AND (p.payment_number LIKE ? OR p.invoice_number LIKE ? OR c.full_name LIKE ? OR a.application_number LIKE ? OR a.passport_number LIKE ? OR p.transaction_reference LIKE ? OR s.company_name LIKE ?)";
            $term = "%{$search}%";
            $params = array_fill(0, 7, $term);
        }

        if ($status !== '') {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }

        if ($method !== '') {
            $sql .= " AND p.payment_method = ?";
            $params[] = $method;
        }

        if ($currency !== '') {
            $sql .= " AND p.currency = ?";
            $params[] = $currency;
        }

        if ($minAmount !== null) {
            $sql .= " AND p.amount >= ?";
            $params[] = $minAmount;
        }

        if ($maxAmount !== null) {
            $sql .= " AND p.amount <= ?";
            $params[] = $maxAmount;
        }

        if ($branchId > 0) {
            $sql .= " AND (u.branch_id = ? OR a.branch_id = ?)";
            $params[] = $branchId;
            $params[] = $branchId;
        }

        if ($supplierId > 0) {
            $sql .= " AND a.supplier_id = ?";
            $params[] = $supplierId;
        }

        if ($countryId > 0) {
            $sql .= " AND ct.id = ?";
            $params[] = $countryId;
        }

        if (!empty($dateFrom)) {
            $sql .= " AND p.payment_date >= ?";
            $params[] = $dateFrom;
        }

        if (!empty($dateTo)) {
            $sql .= " AND p.payment_date <= ?";
            $params[] = $dateTo;
        }

        $sql .= " ORDER BY p.payment_date DESC, p.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $payments = $stmt->fetchAll();

        // Meta options for filters
        $suppliersList = $pdo->query("SELECT id, company_name FROM suppliers WHERE is_active = 1 ORDER BY company_name ASC")->fetchAll();
        $countriesList = $pdo->query("SELECT id, name FROM countries WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
        $branchesList = $pdo->query("SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
        $currenciesList = $pdo->query("SELECT DISTINCT currency FROM payments WHERE currency IS NOT NULL AND currency != '' ORDER BY currency ASC")->fetchAll(PDO::FETCH_COLUMN) ?: ['AED', 'USD', 'EUR', 'GBP', 'INR', 'PKR', 'LKR'];
        $applicationsList = $pdo->query("SELECT a.id, a.application_number, a.total_amount, a.balance_amount, a.passport_number, 
                c.id as customer_id, c.customer_code, c.full_name as customer_name, c.mobile as customer_mobile, c.email as customer_email,
                ct.name as country_name, ct.flag_emoji, vs.name as service_name
            FROM applications a 
            JOIN customers c ON a.customer_id = c.id 
            JOIN visa_services vs ON a.visa_service_id = vs.id
            JOIN countries ct ON vs.country_id = ct.id
            WHERE a.is_archived = 0 
            ORDER BY a.created_at DESC LIMIT 100")->fetchAll();

        $selectedAppId = (int)($_GET['app_id'] ?? ($_GET['application_id'] ?? 0));
        if ($selectedAppId > 0) {
            $found = false;
            foreach ($applicationsList as $aItem) {
                if ((int)$aItem['id'] === $selectedAppId) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $stmtSingle = $pdo->prepare("SELECT a.id, a.application_number, a.total_amount, a.balance_amount, a.passport_number, 
                        c.id as customer_id, c.customer_code, c.full_name as customer_name, c.mobile as customer_mobile, c.email as customer_email,
                        ct.name as country_name, ct.flag_emoji, vs.name as service_name
                    FROM applications a 
                    JOIN customers c ON a.customer_id = c.id 
                    JOIN visa_services vs ON a.visa_service_id = vs.id
                    JOIN countries ct ON vs.country_id = ct.id
                    WHERE a.id = ?");
                $stmtSingle->execute([$selectedAppId]);
                $singleApp = $stmtSingle->fetch();
                if ($singleApp) {
                    array_unshift($applicationsList, $singleApp);
                }
            }
        }

        // Summary metrics (defensive execution)
        $metrics = [
            'total_received' => 0.0,
            'total_refunded' => 0.0,
            'outstanding' => 0.0,
            'total_wallet_credits' => 0.0,
            'total_wallet_debits' => 0.0,
            'total_online_links' => 0,
        ];
        try {
            $metrics['total_received'] = (float)($pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Completed'")->fetchColumn() ?: 0);
        } catch (\Throwable $e) {}
        try {
            $metrics['total_refunded'] = (float)($pdo->query("SELECT COALESCE(SUM(amount), 0) FROM refunds WHERE status = 'Processed'")->fetchColumn() ?: 0);
        } catch (\Throwable $e) {}
        try {
            $metrics['outstanding'] = (float)($pdo->query("SELECT COALESCE(SUM(balance_amount), 0) FROM applications WHERE is_archived = 0")->fetchColumn() ?: 0);
        } catch (\Throwable $e) {}
        try {
            $metrics['total_wallet_credits'] = (float)($pdo->query("SELECT COALESCE(SUM(amount), 0) FROM wallet_transactions WHERE transaction_type = 'Credit'")->fetchColumn() ?: 0);
        } catch (\Throwable $e) {}
        try {
            $metrics['total_wallet_debits'] = (float)($pdo->query("SELECT COALESCE(SUM(amount), 0) FROM wallet_transactions WHERE transaction_type = 'Debit'")->fetchColumn() ?: 0);
        } catch (\Throwable $e) {}
        try {
            $metrics['total_online_links'] = (int)($pdo->query("SELECT COUNT(*) FROM payment_links")->fetchColumn() ?: 0);
        } catch (\Throwable $e) {}

        require_once dirname(__DIR__) . '/Views/payments/index.php';
    }

    public function store(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $appId = (int)($_POST['application_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0.00);
        $paymentDate = !empty($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d');
        $paymentMethod = trim($_POST['payment_method'] ?? 'Cash');
        $txnRef = trim($_POST['transaction_reference'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        // Multi-Currency and Manual Conversion Support
        $fromCurrency = strtoupper(trim($_POST['from_currency'] ?? 'USD'));
        $toCurrency = strtoupper(trim($_POST['to_currency'] ?? 'USD'));
        $exchangeRate = (float)($_POST['exchange_rate'] ?? 1.000000);
        $originalAmount = (float)($_POST['original_amount'] ?? $amount);
        $convertedAmount = (float)($_POST['converted_amount'] ?? $amount);

        // If currencies are identical, strictly enforce 1:1 conversion to prevent erroneous multiplication
        if ($fromCurrency === $toCurrency) {
            $exchangeRate = 1.000000;
            $convertedAmount = $originalAmount;
            $amount = $originalAmount;
        } else {
            if ($exchangeRate <= 0) {
                $exchangeRate = 1.000000;
            }
            // If converted amount provided and differs, base payment amount is the converted amount
            if ($convertedAmount > 0 && abs($convertedAmount - $amount) > 0.001) {
                $amount = $convertedAmount;
            }
        }

        if ($appId <= 0 || $amount <= 0) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/payments', 'Please specify a valid application and payment amount.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
        $stmt->execute([$appId]);
        $app = $stmt->fetch();

        if (!$app) {
            redirect('/payments', 'Application not found.', 'danger');
        }

        $customerId = (int)$app['customer_id'];
        $receiptNumber = FinanceService::generateReceiptNumber();
        $invoiceNumber = FinanceService::generateInvoiceNumber($appId);

        $currentBalance = (float)($app['balance_amount'] ?? 0.00);
        $totalAppAmount = (float)($app['total_amount'] ?? 0.00);
        $dueAmount = $currentBalance > 0 ? $currentBalance : $totalAppAmount;
        $overpaidExcess = ($dueAmount > 0 && $amount > $dueAmount) ? ($amount - $dueAmount) : 0.00;

        // If paid via customer wallet, debit wallet
        if ($paymentMethod === 'Customer Wallet') {
            try {
                \App\Services\WalletService::debit($customerId, $amount, "Payment for {$app['application_number']} (Receipt: {$receiptNumber})", $appId, $currentUser['id'] ?? null, $toCurrency);
            } catch (\Throwable $e) {
                redirect($_SERVER['HTTP_REFERER'] ?? '/payments', 'Wallet payment failed: ' . $e->getMessage(), 'danger');
            }
        }

        // Mandatory Receipt / Deposit Slip Attachment Enforcement (Sir Instruction)
        $receiptFile = null;
        $hasReceiptUpload = isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK;

        if (!$hasReceiptUpload && $paymentMethod !== 'Customer Wallet') {
            redirect($_SERVER['HTTP_REFERER'] ?? '/payments', 'Payment rejected: An official payment slip, deposit voucher, or cash receipt voucher attachment is mandatory for all payment methods.', 'danger');
        }

        if ($hasReceiptUpload) {
            $file = $_FILES['receipt_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'docx'];
            if (!in_array($ext, $allowed, true)) {
                redirect($_SERVER['HTTP_REFERER'] ?? '/payments', 'Invalid receipt file format. Allowed formats: PDF, JPG, PNG, DOCX.', 'danger');
            }
            if ($file['size'] > 15 * 1024 * 1024) {
                redirect($_SERVER['HTTP_REFERER'] ?? '/payments', 'Receipt attachment file exceeds 15MB size limit.', 'danger');
            }

            $uploadDir = App::basePath('storage' . DIRECTORY_SEPARATOR . 'receipts');
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            $safeFileName = 'slip_' . $appId . '_' . time() . '_' . substr(bin2hex(random_bytes(4)), 0, 6) . '.' . $ext;
            $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $safeFileName;
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                redirect($_SERVER['HTTP_REFERER'] ?? '/payments', 'Failed to store receipt attachment file. Please try again.', 'danger');
            }
            $receiptFile = 'storage/receipts/' . $safeFileName;
        }

        $payStmt = $pdo->prepare("INSERT INTO payments (
            payment_number, invoice_number, application_id, customer_id, amount, currency,
            from_currency, to_currency, exchange_rate, original_amount, converted_amount,
            payment_date, payment_method, transaction_reference, payment_type, status, received_by, receipt_file, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Customer Payment', 'Completed', ?, ?, ?)");

        $payStmt->execute([
            $receiptNumber, $invoiceNumber, $appId, $customerId, $amount, $toCurrency,
            $fromCurrency, $toCurrency, $exchangeRate, $originalAmount, $amount,
            $paymentDate, $paymentMethod, $txnRef, $currentUser['id'], $receiptFile, $notes
        ]);
        $paymentId = (int)$pdo->lastInsertId();

        // Recalculate Application Financials
        FinanceService::recalculateApplication($appId);

        // Auto-topup customer wallet if customer paid excess/remaining balance at office
        if ($overpaidExcess > 0 && $paymentMethod !== 'Customer Wallet') {
            try {
                \App\Services\WalletService::credit(
                    $customerId,
                    $overpaidExcess,
                    "Automatic Wallet Top-up from office overpayment on {$app['application_number']} (Receipt: {$receiptNumber})",
                    $paymentId,
                    $appId,
                    $currentUser['id'] ?? null,
                    $toCurrency
                );
            } catch (\Throwable $e) {}
        }

        // Dispatch Central Real-Time Notification (Email + WhatsApp + In-App)
        try {
            $custInfoStmt = $pdo->prepare("SELECT full_name, email, mobile, whatsapp FROM customers WHERE id = ?");
            $custInfoStmt->execute([$customerId]);
            $custInfo = $custInfoStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            \App\Services\NotificationService::trigger('payment.received', [
                'application_id' => $appId,
                'customer_id' => $customerId,
                'application_number' => $app['application_number'] ?? '',
                'paymentNumber' => $receiptNumber,
                'amount' => number_format($amount, 2),
                'currency' => $toCurrency,
                'paymentMethod' => $paymentMethod,
                'paymentDate' => $paymentDate,
                'applicantName' => $custInfo['full_name'] ?? 'Valued Customer',
                'applicantEmail' => $custInfo['email'] ?? '',
                'applicantPhone' => $custInfo['whatsapp'] ?: ($custInfo['mobile'] ?? ''),
                'receiptUrl' => App::url('portal/invoices'),
                'portal_link' => "/portal/invoices",
                'link' => "/payments/receipt?id={$paymentId}",
            ]);

            // Guaranteed direct email receipt dispatch if customer has email
            if (!empty($custInfo['email'])) {
                try {
                    $receiptSubject = "Payment Receipt Confirmed — {$receiptNumber} (" . App::COMPANY_NAME . ")";
                    $receiptBody = "
                        <p>Dear <strong>" . htmlspecialchars($custInfo['full_name'] ?? 'Valued Customer') . "</strong>,</p>
                        <p>We have successfully received and processed your payment for visa application <strong>{$app['application_number']}</strong>.</p>
                        <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; margin: 20px 0;'>
                            <h4 style='margin-top: 0; color: #1e3a8a;'>Official Payment Receipt Details</h4>
                            <p style='margin: 6px 0;'><strong>Receipt Number:</strong> <span style='font-family: monospace; font-weight: bold;'>{$receiptNumber}</span></p>
                            <p style='margin: 6px 0;'><strong>Invoice Number:</strong> {$invoiceNumber}</p>
                            <p style='margin: 6px 0;'><strong>Application Ref:</strong> {$app['application_number']}</p>
                            <p style='margin: 6px 0;'><strong>Amount Paid:</strong> <strong style='color: #16a34a; font-size: 1.1em;'>{$toCurrency} " . number_format($amount, 2) . "</strong></p>
                            <p style='margin: 6px 0;'><strong>Payment Method:</strong> {$paymentMethod}</p>
                            <p style='margin: 6px 0;'><strong>Payment Date:</strong> {$paymentDate}</p>
                        </div>
                        <p>You can view and download your full tax invoice and payment vouchers anytime from the Customer Portal.</p>
                        <p style='text-align: center; margin: 25px 0;'>
                            <a href='" . App::url('portal/invoices') . "' style='background: #2563eb; color: #ffffff; padding: 12px 28px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block;'>View Invoices &amp; Receipts &rarr;</a>
                        </p>
                    ";

                    \App\Services\EmailService::send([
                        'to' => $custInfo['email'],
                        'name' => $custInfo['full_name'] ?? 'Valued Customer',
                        'subject' => $receiptSubject,
                        'bodyHtml' => $receiptBody,
                        'data' => [
                            'paymentNumber' => $receiptNumber,
                            'amount' => number_format($amount, 2),
                            'currency' => $toCurrency,
                            'applicantName' => $custInfo['full_name'] ?? '',
                            'applicationNumber' => $app['application_number'] ?? '',
                        ]
                    ]);
                } catch (\Throwable $eDirect) {}
            }
        } catch (\Throwable $e) {}

        AuditService::log('PAYMENT_RECEIVED', 'Payments', $paymentId, "Received payment of {$toCurrency} " . number_format($amount, 2) . " (Receipt {$receiptNumber}) for {$app['application_number']}" . ($fromCurrency !== $toCurrency ? " [Converted from {$fromCurrency} " . number_format($originalAmount, 2) . " @ {$exchangeRate}]" : '') . ($overpaidExcess > 0 ? " (Excess {$toCurrency} " . number_format($overpaidExcess, 2) . " credited to wallet)" : ''), [
            'amount' => $amount,
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'exchange_rate' => $exchangeRate,
            'original_amount' => $originalAmount,
            'method' => $paymentMethod,
            'receipt' => $receiptNumber,
            'wallet_topup' => $overpaidExcess,
        ]);

        redirect("/payments/receipt?id={$paymentId}", "Payment recorded successfully." . ($overpaidExcess > 0 ? " Excess {$toCurrency} " . number_format($overpaidExcess, 2) . " was automatically credited to customer's wallet." : '') . " Receipt generated.", 'success');
    }

    /**
     * Central Wallets Management View for Super Admin / Accounts
     */
    public function walletsOverview(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $activeTab = trim($_GET['tab'] ?? 'customers');
        $search = trim($_GET['search'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');
        $type = trim($_GET['type'] ?? '');

        // 1. Customer Wallets Filter
        $cwSql = "SELECT cw.*, c.full_name, c.customer_code, c.email, c.mobile 
            FROM customer_wallets cw 
            JOIN customers c ON cw.customer_id = c.id 
            WHERE 1=1";
        $cwParams = [];
        if ($search !== '') {
            $cwSql .= " AND (c.full_name LIKE ? OR c.customer_code LIKE ? OR c.email LIKE ? OR c.mobile LIKE ?)";
            $t = "%{$search}%";
            $cwParams = [$t, $t, $t, $t];
        }
        if (!empty($dateFrom)) {
            $cwSql .= " AND DATE(cw.created_at) >= ?";
            $cwParams[] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $cwSql .= " AND DATE(cw.created_at) <= ?";
            $cwParams[] = $dateTo;
        }
        $cwSql .= " ORDER BY cw.current_balance DESC, c.full_name ASC";
        $customerWallets = [];
        try {
            $cwStmt = $pdo->prepare($cwSql);
            $cwStmt->execute($cwParams);
            $customerWallets = $cwStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $customerWallets = [];
        }

        // 2. Supplier Wallets Filter
        $swSql = "SELECT sw.*, s.company_name as supplier_name, s.company_name, s.country 
            FROM supplier_wallets sw 
            JOIN suppliers s ON sw.supplier_id = s.id 
            WHERE 1=1";
        $swParams = [];
        if ($search !== '') {
            $swSql .= " AND (s.company_name LIKE ? OR s.supplier_code LIKE ? OR s.contact_person LIKE ? OR s.country LIKE ?)";
            $t = "%{$search}%";
            $swParams = [$t, $t, $t, $t];
        }
        if (!empty($dateFrom)) {
            $swSql .= " AND DATE(sw.created_at) >= ?";
            $swParams[] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $swSql .= " AND DATE(sw.created_at) <= ?";
            $swParams[] = $dateTo;
        }
        $swSql .= " ORDER BY sw.current_balance DESC, s.company_name ASC";
        $supplierWallets = [];
        try {
            $swStmt = $pdo->prepare($swSql);
            $swStmt->execute($swParams);
            $supplierWallets = $swStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $supplierWallets = [];
        }

        // 3. Agent Wallets Filter
        $awSql = "SELECT aw.*, COALESCE(ag.company_name, ag.contact_person, u.name, 'Agent') as agent_name, COALESCE(ag.email, u.email, '') as agent_email 
            FROM agent_wallets aw 
            LEFT JOIN agents ag ON aw.agent_id = ag.id 
            LEFT JOIN users u ON aw.agent_id = u.id 
            WHERE 1=1";
        $awParams = [];
        if ($search !== '') {
            $awSql .= " AND (ag.company_name LIKE ? OR ag.contact_person LIKE ? OR u.name LIKE ? OR ag.email LIKE ? OR u.email LIKE ?)";
            $t = "%{$search}%";
            $awParams = [$t, $t, $t, $t, $t];
        }
        if (!empty($dateFrom)) {
            $awSql .= " AND DATE(aw.created_at) >= ?";
            $awParams[] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $awSql .= " AND DATE(aw.created_at) <= ?";
            $awParams[] = $dateTo;
        }
        $awSql .= " ORDER BY aw.current_balance DESC";
        $agentWallets = [];
        try {
            $awStmt = $pdo->prepare($awSql);
            $awStmt->execute($awParams);
            $agentWallets = $awStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $agentWallets = [];
        }

        // 4. Recent Audit Ledger Filter
        $wtSql = "SELECT wt.*, c.full_name as customer_name, u.name as created_by_name 
            FROM wallet_transactions wt 
            LEFT JOIN customers c ON wt.customer_id = c.id 
            LEFT JOIN users u ON wt.created_by = u.id 
            WHERE 1=1";
        $wtParams = [];
        if ($search !== '') {
            $wtSql .= " AND (wt.transaction_id LIKE ? OR c.full_name LIKE ? OR wt.description LIKE ? OR wt.payment_reference LIKE ?)";
            $t = "%{$search}%";
            $wtParams = [$t, $t, $t, $t];
        }
        if ($type !== '') {
            $wtSql .= " AND LOWER(wt.transaction_type) = LOWER(?)";
            $wtParams[] = $type;
        }
        if (!empty($dateFrom)) {
            $wtSql .= " AND DATE(wt.created_at) >= ?";
            $wtParams[] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $wtSql .= " AND DATE(wt.created_at) <= ?";
            $wtParams[] = $dateTo;
        }
        $wtSql .= " ORDER BY wt.created_at DESC LIMIT 100";
        $wtStmt = $pdo->prepare($wtSql);
        $wtStmt->execute($wtParams);
        $recentTransactions = $wtStmt->fetchAll(PDO::FETCH_ASSOC);

        $customersList = $pdo->query("SELECT id, full_name, customer_code FROM customers WHERE is_active = 1 ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $suppliersList = $pdo->query("SELECT id, company_name as name, company_name FROM suppliers WHERE is_active = 1 ORDER BY company_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        
        $agentsList = $pdo->query("SELECT id, company_name as name, company_name, contact_person FROM agents WHERE is_active = 1 ORDER BY company_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($agentsList)) {
            $agentsList = $pdo->query("SELECT u.id, u.name, u.name as company_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.is_active = 1 ORDER BY u.name ASC")->fetchAll(PDO::FETCH_ASSOC);
        }

        require_once dirname(__DIR__) . '/Views/payments/wallets.php';
    }

    /**
     * Admin Direct Supplier Wallet Top-up
     */
    public function supplierWalletDeposit(): void
    {
        AuthMiddleware::handle();
        $currentUser = auth_user();

        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0.00);
        $currency = strtoupper(trim($_POST['currency'] ?? 'AED'));
        $exchangeRate = (float)($_POST['exchange_rate'] ?? 1.000000);
        $originalAmount = (float)($_POST['original_amount'] ?? $amount);
        $convertedAmount = (float)($_POST['converted_amount'] ?? ($amount * $exchangeRate));
        $paymentMethod = trim($_POST['payment_method'] ?? 'Bank Transfer');
        $reference = trim($_POST['reference'] ?? '');
        $notes = trim($_POST['notes'] ?? 'Supplier Advance Deposit');

        if ($supplierId <= 0 || $amount <= 0) {
            redirect('/payments/wallets?tab=suppliers', 'Please specify valid supplier and amount.', 'danger');
        }

        // Mandatory Slip / Deposit Voucher Enforcement
        $hasReceiptUpload = isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK;
        if (!$hasReceiptUpload) {
            redirect('/payments/wallets?tab=suppliers', 'Top-up rejected: Bank slip or deposit voucher attachment is mandatory for supplier credit.', 'danger');
        }
        $file = $_FILES['receipt_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'docx'];
        if (!in_array($ext, $allowed, true)) {
            redirect('/payments/wallets?tab=suppliers', 'Invalid receipt file format. Allowed formats: PDF, JPG, PNG, DOCX.', 'danger');
        }
        if ($file['size'] > 15 * 1024 * 1024) {
            redirect('/payments/wallets?tab=suppliers', 'Receipt attachment file exceeds 15MB size limit.', 'danger');
        }
        $uploadDir = App::basePath('storage' . DIRECTORY_SEPARATOR . 'receipts');
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        $safeFileName = 'sup_wallet_' . $supplierId . '_' . time() . '_' . substr(bin2hex(random_bytes(4)), 0, 6) . '.' . $ext;
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $safeFileName;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            redirect('/payments/wallets?tab=suppliers', 'Failed to store receipt attachment file. Please try again.', 'danger');
        }
        $receiptFilePath = 'storage/receipts/' . $safeFileName;
        $reference = trim($reference . ' [Slip: ' . $receiptFilePath . ']');

        $finalAmount = $exchangeRate > 0 && $convertedAmount > 0 ? $convertedAmount : $amount;

        try {
            \App\Services\WalletService::creditSupplier(
                $supplierId,
                $finalAmount,
                $notes,
                $currentUser['id'] ?? null,
                $currency,
                $originalAmount,
                $exchangeRate,
                $paymentMethod,
                $reference,
                $finalAmount
            );
            redirect('/payments/wallets?tab=suppliers', "Successfully credited {$currency} " . number_format($finalAmount, 2) . " to supplier wallet.", 'success');
        } catch (\Throwable $e) {
            redirect('/payments/wallets?tab=suppliers', 'Supplier top-up failed: ' . $e->getMessage(), 'danger');
        }
    }

    /**
     * Admin Direct Agent Wallet Top-up
     */
    public function agentWalletDeposit(): void
    {
        AuthMiddleware::handle();
        $currentUser = auth_user();

        $agentId = (int)($_POST['agent_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0.00);
        $currency = strtoupper(trim($_POST['currency'] ?? 'USD'));
        $exchangeRate = (float)($_POST['exchange_rate'] ?? 1.000000);
        $originalAmount = (float)($_POST['original_amount'] ?? $amount);
        $convertedAmount = (float)($_POST['converted_amount'] ?? ($amount * $exchangeRate));
        $paymentMethod = trim($_POST['payment_method'] ?? 'Bank Transfer');
        $reference = trim($_POST['reference'] ?? '');
        $notes = trim($_POST['notes'] ?? 'Agent Credit Top-up');

        if ($agentId <= 0 || $amount <= 0) {
            redirect('/payments/wallets?tab=agents', 'Please specify valid agent and amount.', 'danger');
        }

        // Mandatory Slip / Credit Voucher Enforcement
        $hasReceiptUpload = isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK;
        if (!$hasReceiptUpload) {
            redirect('/payments/wallets?tab=agents', 'Top-up rejected: Bank slip or credit voucher attachment is mandatory for agent credit.', 'danger');
        }
        $file = $_FILES['receipt_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'docx'];
        if (!in_array($ext, $allowed, true)) {
            redirect('/payments/wallets?tab=agents', 'Invalid receipt file format. Allowed formats: PDF, JPG, PNG, DOCX.', 'danger');
        }
        if ($file['size'] > 15 * 1024 * 1024) {
            redirect('/payments/wallets?tab=agents', 'Receipt attachment file exceeds 15MB size limit.', 'danger');
        }
        $uploadDir = App::basePath('storage' . DIRECTORY_SEPARATOR . 'receipts');
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        $safeFileName = 'agent_wallet_' . $agentId . '_' . time() . '_' . substr(bin2hex(random_bytes(4)), 0, 6) . '.' . $ext;
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $safeFileName;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            redirect('/payments/wallets?tab=agents', 'Failed to store receipt attachment file. Please try again.', 'danger');
        }
        $receiptFilePath = 'storage/receipts/' . $safeFileName;
        $reference = trim($reference . ' [Slip: ' . $receiptFilePath . ']');

        $finalAmount = $exchangeRate > 0 && $convertedAmount > 0 ? $convertedAmount : $amount;

        try {
            \App\Services\WalletService::creditAgent(
                $agentId,
                $finalAmount,
                $notes,
                $currentUser['id'] ?? null,
                $currency,
                $originalAmount,
                $exchangeRate,
                $paymentMethod,
                $reference,
                $finalAmount
            );
            redirect('/payments/wallets?tab=agents', "Successfully credited {$currency} " . number_format($finalAmount, 2) . " to agent wallet.", 'success');
        } catch (\Throwable $e) {
            redirect('/payments/wallets?tab=agents', 'Agent top-up failed: ' . $e->getMessage(), 'danger');
        }
    }

    /**
     * Admin Direct Agent Wallet Debit
     */
    public function agentWalletDebit(): void
    {
        AuthMiddleware::handle();
        $currentUser = auth_user();

        $agentId = (int)($_POST['agent_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0.00);
        $currency = strtoupper(trim($_POST['currency'] ?? 'USD'));
        $paymentMethod = trim($_POST['payment_method'] ?? 'Adjustment');
        $reference = trim($_POST['reference'] ?? '');
        $notes = trim($_POST['notes'] ?? 'Agent Balance Deduction');

        if ($agentId <= 0 || $amount <= 0) {
            redirect('/payments/wallets?tab=agents', 'Please specify valid agent and debit amount.', 'danger');
        }

        try {
            \App\Services\WalletService::debitAgent(
                $agentId,
                $amount,
                $notes,
                $currentUser['id'] ?? null,
                $currency,
                $amount,
                1.0,
                $paymentMethod,
                $reference
            );
            redirect('/payments/wallets?tab=agents', "Successfully debited {$currency} " . number_format($amount, 2) . " from agent wallet.", 'success');
        } catch (\Throwable $e) {
            redirect('/payments/wallets?tab=agents', 'Agent debit failed: ' . $e->getMessage(), 'danger');
        }
    }

    /**
     * Admin Direct Customer Wallet Deposit
     */
    public function walletDeposit(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $customerId = (int)($_POST['customer_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0.00);
        $fromCurrency = strtoupper(trim($_POST['currency'] ?? $_POST['from_currency'] ?? 'USD'));
        $exchangeRate = (float)($_POST['exchange_rate'] ?? 1.000000);
        $originalAmount = (float)($_POST['original_amount'] ?? $amount);
        $convertedAmount = (float)($_POST['converted_amount'] ?? ($amount * $exchangeRate));
        $paymentMethod = trim($_POST['payment_method'] ?? 'Cash at Office');
        $txnRef = trim($_POST['transaction_reference'] ?? $_POST['reference'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($customerId <= 0 || $amount <= 0) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/payments/wallets', 'Please specify a valid customer and deposit amount.', 'danger');
        }

        $custStmt = $pdo->prepare("SELECT id, full_name, customer_code FROM customers WHERE id = ?");
        $custStmt->execute([$customerId]);
        $customer = $custStmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/payments/wallets', 'Customer not found.', 'danger');
        }

        $wallet = \App\Services\WalletService::getOrCreateWallet($customerId);
        $walletCurrency = $wallet['currency'] ?: 'USD';
        if ($fromCurrency === $walletCurrency) {
            $exchangeRate = 1.000000;
            $finalCreditAmount = $amount;
        } else {
            $finalCreditAmount = $exchangeRate > 0 && $convertedAmount > 0 ? $convertedAmount : $amount;
        }

        try {
            $desc = "Office Wallet Deposit via {$paymentMethod}" . ($txnRef ? " (Ref: {$txnRef})" : '') . ($notes ? " - {$notes}" : '');
            $res = \App\Services\WalletService::credit(
                $customerId,
                $finalCreditAmount,
                $desc,
                null,
                null,
                $currentUser['id'] ?? null,
                $walletCurrency,
                $originalAmount,
                $exchangeRate,
                $paymentMethod,
                $txnRef,
                null,
                $finalCreditAmount,
                $walletCurrency
            );

            // Mandatory Deposit Voucher / Bank Slip
            $receiptFile = null;
            $hasReceiptUpload = isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK;
            if (!$hasReceiptUpload) {
                redirect($_SERVER['HTTP_REFERER'] ?? '/payments/wallets', 'Deposit rejected: Payment slip or deposit voucher attachment is mandatory.', 'danger');
            }
            if ($hasReceiptUpload) {
                $file = $_FILES['receipt_file'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'docx'];
                if (!in_array($ext, $allowed, true)) {
                    redirect($_SERVER['HTTP_REFERER'] ?? '/payments/wallets', 'Invalid receipt file format. Allowed formats: PDF, JPG, PNG, DOCX.', 'danger');
                }
                $uploadDir = App::basePath('storage' . DIRECTORY_SEPARATOR . 'receipts');
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                $safeFileName = 'wallet_slip_' . $customerId . '_' . time() . '_' . substr(bin2hex(random_bytes(4)), 0, 6) . '.' . $ext;
                $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $safeFileName;
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $receiptFile = 'storage/receipts/' . $safeFileName;
                }
            }

            // Also record a payment receipt record for accounting
            $receiptNumber = FinanceService::generateReceiptNumber();
            $invNumber = 'INV-WAL-' . $receiptNumber;
            try {
                // Ensure payments table allows NULL application_id for non-application wallet topups
                try {
                    $pdo->exec("ALTER TABLE payments MODIFY COLUMN application_id INT NULL DEFAULT NULL");
                } catch (\Throwable $eAlter) {}

                $pdo->prepare("INSERT INTO payments (
                    application_id, payment_number, invoice_number, customer_id, amount, currency, payment_date, payment_method,
                    transaction_reference, wallet_transaction_id, payment_type, status, received_by, receipt_file, notes,
                    from_currency, to_currency, exchange_rate, original_amount
                ) VALUES (NULL, ?, ?, ?, ?, ?, CURRENT_DATE, ?, ?, ?, 'Wallet Topup', 'Completed', ?, ?, ?, ?, ?, ?, ?)")
                ->execute([
                    $receiptNumber, $invNumber, $customerId, $finalCreditAmount, $walletCurrency,
                    $paymentMethod, $txnRef, $res['transaction_id'] ?? null, $currentUser['id'] ?? null, $receiptFile, $notes,
                    $fromCurrency, $walletCurrency, $exchangeRate, $originalAmount
                ]);
            } catch (\Throwable $ePay) {
                // Primary wallet balance was already safely credited above; log any secondary payment receipt notice
                error_log('[VISA-TRACK] Secondary payments table insert note for wallet topup: ' . $ePay->getMessage());
            }

            redirect($_SERVER['HTTP_REFERER'] ?? '/payments/wallets', "Successfully deposited {$walletCurrency} " . number_format($finalCreditAmount, 2) . " into {$customer['full_name']}'s digital wallet.", 'success');
        } catch (\Throwable $e) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/payments/wallets', 'Deposit failed: ' . $e->getMessage(), 'danger');
        }
    }

    /**
     * Admin Direct Customer Wallet Debit
     */
    public function walletDebit(): void
    {
        AuthMiddleware::handle();
        $currentUser = auth_user();

        $customerId = (int)($_POST['customer_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0.00);
        $reason = trim($_POST['reason'] ?? $_POST['notes'] ?? 'Wallet Debit Settlement');
        $reference = trim($_POST['reference'] ?? '');
        $paymentMethod = trim($_POST['payment_method'] ?? 'Manual Debit');

        if ($customerId <= 0 || $amount <= 0) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/payments/wallets', 'Please specify a valid customer and debit amount.', 'danger');
        }

        $wallet = \App\Services\WalletService::getOrCreateWallet($customerId);
        $walletCurrency = $wallet['currency'] ?: 'USD';

        try {
            $desc = "Wallet Debit: {$reason}" . ($reference ? " (Ref: {$reference})" : '');
            \App\Services\WalletService::debit(
                $customerId,
                $amount,
                $desc,
                null,
                $currentUser['id'] ?? null,
                $walletCurrency,
                $amount,
                1.0,
                $paymentMethod,
                $reference
            );
            redirect($_SERVER['HTTP_REFERER'] ?? '/payments/wallets', "Successfully debited {$walletCurrency} " . number_format($amount, 2) . " from customer wallet.", 'success');
        } catch (\Throwable $e) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/payments/wallets', 'Debit failed: ' . $e->getMessage(), 'danger');
        }
    }

    /**
     * Manual Refund Processing
     */
    public function refund(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $appId = (int)($_POST['application_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0.00);
        $reason = trim($_POST['reason'] ?? '');
        $paymentMethod = trim($_POST['payment_method'] ?? 'Bank Transfer');
        $txnRef = trim($_POST['transaction_reference'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($appId <= 0 || $amount <= 0 || empty($reason)) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/payments', 'Please specify application, refund amount, and a clear reason.', 'danger');
        }

        $app = $pdo->query("SELECT * FROM applications WHERE id = {$appId}")->fetch();
        if (!$app) {
            redirect('/payments', 'Application not found.', 'danger');
        }

        if ($amount > (float)$app['paid_amount']) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/payments', 'Refund amount cannot exceed total paid amount ($' . number_format((float)$app['paid_amount'], 2) . ').', 'danger');
        }

        $refundNumber = FinanceService::generateRefundNumber();

        $stmt = $pdo->prepare("INSERT INTO refunds (
            refund_number, application_id, customer_id, amount, reason, 
            payment_method, transaction_reference, processed_by, status, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Processed', ?)");
        $stmt->execute([$refundNumber, $appId, $app['customer_id'], $amount, $reason, $paymentMethod, $txnRef, $currentUser['id'], $notes]);
        $refundId = (int)$pdo->lastInsertId();

        // Recalculate balances
        FinanceService::recalculateApplication($appId);

        AuditService::log('PROCESS_REFUND', 'Payments', $refundId, "Processed manual refund of $" . number_format($amount, 2) . " (Ref {$refundNumber}) for {$app['application_number']}", [
            'amount' => $amount,
            'reason' => $reason,
        ]);

        redirect("/applications/show?id={$appId}#payments", "Manual refund {$refundNumber} processed successfully.", 'success');
    }

    /**
     * Printable Official Payment Receipt (Protected with RBAC & Customer Ownership)
     */
    public function receipt(): void
    {
        $pdo = Database::getConnection();
        $paymentId = (int)($_GET['id'] ?? 0);

        if ($paymentId <= 0) {
            http_response_code(404);
            die('Payment receipt not found.');
        }

        $stmt = $pdo->prepare("SELECT p.*, 
                a.application_number, a.selling_price, a.total_amount, a.paid_amount, a.balance_amount, a.branch_id, a.assigned_staff_id, a.agent_id,
                vs.name as service_name, ct.name as country_name,
                c.customer_code, c.full_name as customer_name, c.mobile as customer_mobile, c.email as customer_email, c.address as customer_address,
                u.name as received_by_name
            FROM payments p
            JOIN applications a ON p.application_id = a.id
            JOIN visa_services vs ON a.visa_service_id = vs.id
            JOIN countries ct ON vs.country_id = ct.id
            JOIN customers c ON p.customer_id = c.id
            LEFT JOIN users u ON p.received_by = u.id
            WHERE p.id = ?");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();

        if (!$payment) {
            http_response_code(404);
            die('Payment receipt not found.');
        }

        // Enforce RBAC & Access Authorization
        $staffUser = auth_user();
        $portalCustomer = $_SESSION['customer'] ?? null;
        $portalAgent = $_SESSION['agent'] ?? null;
        $token = trim($_GET['token'] ?? '');
        $isAuthorized = false;

        if ($staffUser) {
            $roleSlug = $staffUser['role_slug'] ?? '';
            $staffId = (int)($staffUser['id'] ?? 0);
            if (in_array($roleSlug, ['super-admin', 'admin', 'accounts', 'branch-manager', 'visa-manager'], true)
                || user_can('payments.view') || user_can('finance.view') || user_can('applications.view')
                || (int)$payment['assigned_staff_id'] === $staffId) {
                $isAuthorized = true;
            }
        } elseif ($portalCustomer && (int)$portalCustomer['id'] === (int)$payment['customer_id']) {
            $isAuthorized = true;
        } elseif ($portalAgent && !empty($payment['agent_id']) && (int)$portalAgent['id'] === (int)$payment['agent_id']) {
            $isAuthorized = true;
        } elseif (!empty($token)) {
            $tokStmt = $pdo->prepare("SELECT id FROM payment_links WHERE (payment_id = ? OR application_id = ?) AND token = ? LIMIT 1");
            $tokStmt->execute([$paymentId, (int)$payment['application_id'], $token]);
            if ($tokStmt->fetch()) {
                $isAuthorized = true;
            }
        }

        if (!$isAuthorized) {
            http_response_code(403);
            die('Access Denied: You do not have permission to view this official receipt.');
        }

        require_once dirname(__DIR__) . '/Views/payments/receipt.php';
    }

    /**
     * Printable Official Tax Invoice (Protected with RBAC & Customer Ownership)
     */
    public function invoice(): void
    {
        $pdo = Database::getConnection();
        $appId = (int)($_GET['app_id'] ?? 0);

        if ($appId <= 0) {
            http_response_code(404);
            die('Invoice not found.');
        }

        $stmt = $pdo->prepare("SELECT a.*, 
                vs.name as service_name, vs.entry_type, vs.processing_type,
                ct.name as country_name,
                c.customer_code, c.full_name as customer_name, c.mobile as customer_mobile, c.email as customer_email, c.address as customer_address,
                u.name as staff_name
            FROM applications a
            JOIN visa_services vs ON a.visa_service_id = vs.id
            JOIN countries ct ON vs.country_id = ct.id
            JOIN customers c ON a.customer_id = c.id
            LEFT JOIN users u ON a.assigned_staff_id = u.id
            WHERE a.id = ?");
        $stmt->execute([$appId]);
        $application = $stmt->fetch();

        if (!$application) {
            http_response_code(404);
            die('Invoice not found.');
        }

        // Enforce RBAC & Access Authorization
        $staffUser = auth_user();
        $portalCustomer = $_SESSION['customer'] ?? null;
        $portalAgent = $_SESSION['agent'] ?? null;
        $token = trim($_GET['token'] ?? '');
        $isAuthorized = false;

        if ($staffUser) {
            $roleSlug = $staffUser['role_slug'] ?? '';
            $staffId = (int)($staffUser['id'] ?? 0);
            if (in_array($roleSlug, ['super-admin', 'admin', 'accounts', 'branch-manager', 'visa-manager'], true)
                || user_can('payments.view') || user_can('finance.view') || user_can('applications.view')
                || (int)$application['assigned_staff_id'] === $staffId) {
                $isAuthorized = true;
            }
        } elseif ($portalCustomer && (int)$portalCustomer['id'] === (int)$application['customer_id']) {
            $isAuthorized = true;
        } elseif ($portalAgent && !empty($application['agent_id']) && (int)$portalAgent['id'] === (int)$application['agent_id']) {
            $isAuthorized = true;
        } elseif (!empty($token)) {
            $tokStmt = $pdo->prepare("SELECT id FROM payment_links WHERE application_id = ? AND token = ? LIMIT 1");
            $tokStmt->execute([$appId, $token]);
            if ($tokStmt->fetch()) {
                $isAuthorized = true;
            }
        }

        if (!$isAuthorized) {
            http_response_code(403);
            die('Access Denied: You do not have permission to view this official invoice.');
        }

        $pStmt = $pdo->prepare("SELECT * FROM payments WHERE application_id = ? AND status = 'Completed' ORDER BY payment_date ASC");
        $pStmt->execute([$appId]);
        $payments = $pStmt->fetchAll();

        require_once dirname(__DIR__) . '/Views/payments/invoice.php';
    }

    /**
     * Public Online Payment Link Landing Page
     */
    public function pay(): void
    {
        $token = trim($_GET['token'] ?? '');
        if (empty($token)) {
            die('Invalid or missing payment token.');
        }

        $link = \App\Services\PaymentLinkService::getLinkByToken($token);
        if (!$link) {
            die('Payment link not found or invalid.');
        }

        $pdo = Database::getConnection();
        $customerWallet = \App\Services\WalletService::getOrCreateWallet((int)$link['customer_id']);

        // Check if there's already a completed payment linked to this link
        $paymentId = (int)$pdo->query("SELECT id FROM payments WHERE payment_link_id = {$link['id']} AND status = 'Completed' ORDER BY id DESC LIMIT 1")->fetchColumn();

        require_once dirname(__DIR__) . '/Views/payments/pay.php';
    }

    /**
     * Public Online Payment Checkout (Stripe Gateway Integration)
     */
    public function checkout(): void
    {
        $token = trim($_POST['token'] ?? '');
        $link = \App\Services\PaymentLinkService::getLinkByToken($token);

        if (!$link) {
            redirect("/pay?token={$token}", 'Payment link not found or invalid.', 'danger');
        }

        if ($link['status'] === 'Paid') {
            redirect("/pay?token={$token}", 'This payment link has already been settled.', 'success');
        }

        // Generate unique gateway reference ID
        $stripeTxnId = 'ch_stripe_' . bin2hex(random_bytes(10));

        // Process payment server-side with complete validation and idempotency
        $result = \App\Services\PaymentLinkService::completePayment(
            $token,
            'Stripe Online Payment',
            $stripeTxnId,
            null,
            "Online card checkout via Stripe Gateway"
        );

        if ($result['success']) {
            $paymentId = $result['payment_id'] ?? 0;
            redirect("/payments/receipt?id={$paymentId}", "Payment successful! Receipt {$result['receipt_number']} generated.", 'success');
        } else {
            redirect("/pay?token={$token}", $result['message'] ?? 'Payment failed. Please try again.', 'danger');
        }
    }

    /**
     * Public Online Payment Checkout via Digital Wallet
     */
    public function payWithWallet(): void
    {
        $token = trim($_POST['token'] ?? '');
        $link = \App\Services\PaymentLinkService::getLinkByToken($token);

        if (!$link) {
            redirect("/pay?token={$token}", 'Payment link not found.', 'danger');
        }

        $customerId = (int)$link['customer_id'];
        $amount = (float)$link['amount'];
        $appId = (int)$link['application_id'];

        $wallet = \App\Services\WalletService::getOrCreateWallet($customerId);
        if ((float)$wallet['current_balance'] < $amount) {
            redirect("/pay?token={$token}", 'Insufficient wallet balance. Please choose another payment method.', 'danger');
        }

        try {
            // 1. Debit wallet
            $debitResult = \App\Services\WalletService::debit($customerId, $amount, "Visa settlement via online link for {$link['application_number']}", $appId);
            $wtxId = $debitResult['transaction_id'] ?? null;

            // 2. Complete payment record
            $result = \App\Services\PaymentLinkService::completePayment(
                $token,
                'Customer Wallet',
                $wtxId ?: 'WALLET_TXN',
                null,
                "Settled via Customer Digital Wallet Balance"
            );

            if ($result['success']) {
                $paymentId = $result['payment_id'] ?? 0;
                redirect("/payments/receipt?id={$paymentId}", "Wallet payment successful! Receipt generated.", 'success');
            } else {
                redirect("/pay?token={$token}", $result['message'] ?? 'Payment processing failed.', 'danger');
            }
        } catch (\Throwable $e) {
            redirect("/pay?token={$token}", "Wallet payment failed: " . $e->getMessage(), 'danger');
        }
    }

    /**
     * Admin: Generate Payment Link
     */
    public function generateLink(): void
    {
        AuthMiddleware::handle();
        $currentUser = auth_user();

        $appId = (int)($_POST['application_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0.00);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $dueDate = trim($_POST['due_date'] ?? '');
        $sendEmail = !empty($_POST['send_email']);
        $sendWhatsapp = !empty($_POST['send_whatsapp']);
        if ($sendWhatsapp && !user_can('whatsapp.send')) {
            $sendWhatsapp = false;
        }

        if ($appId <= 0 || $amount <= 0) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/payments', 'Please specify a valid application and amount.', 'danger');
        }

        $result = \App\Services\PaymentLinkService::createLink(
            $appId,
            $amount,
            $title ?: null,
            $description ?: null,
            $currentUser['id'] ?? 1,
            7,
            $notes ?: null,
            $dueDate ?: null
        );

        if (!$result['success']) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/payments', $result['message'] ?? 'Failed to generate link.', 'danger');
        }

        // Send Email if requested
        if ($sendEmail && !empty($result['customer_email'])) {
            try {
                \App\Services\EmailService::send([
                    'to' => $result['customer_email'],
                    'name' => $result['customer_name'] ?? null,
                    'subject' => "Payment Request: " . ($title ?: "Visa Processing Fee"),
                    'bodyHtml' => "<p>Dear {$result['customer_name']},</p><p>Your payment request for visa processing is ready. Amount: <strong>$" . number_format($amount, 2) . " USD</strong>.</p><p><a href='{$result['url']}' style='padding: 10px 20px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 5px;'>Pay Now Online</a></p>"
                ]);
            } catch (\Throwable $e) {}
        }

        // Send WhatsApp if requested — automatically queue direct WhatsApp app opening
        if ($sendWhatsapp && !empty($result['customer_mobile'])) {
            try {
                \App\Services\WhatsAppService::send([
                    'phoneNumber' => $result['customer_mobile'],
                    'messageText' => $result['whatsapp_message'] ?? "Please complete your visa payment here: {$result['url']}"
                ]);
            } catch (\Throwable $e) {}

            // Set session variable so footer script automatically opens WhatsApp app/web
            if (!empty($result['whatsapp_share_url'])) {
                $_SESSION['auto_open_whatsapp'] = $result['whatsapp_share_url'];
            }
        }

        $successNotice = "Payment link generated successfully: {$result['url']}";
        if ($sendWhatsapp && !empty($result['whatsapp_share_url'])) {
            $successNotice .= " — Opening WhatsApp app to send message...";
        }

        redirect($_SERVER['HTTP_REFERER'] ?? '/payments', $successNotice, 'success');
    }

    /**
     * Admin: Payment Links Listing View
     */
    public function links(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');

        $sql = "SELECT pl.*, 
                    a.application_number, a.passport_number,
                    c.full_name as customer_name, c.customer_code, c.mobile as customer_mobile, c.email as customer_email,
                    vs.name as service_name, ct.name as country_name
                FROM payment_links pl
                JOIN applications a ON pl.application_id = a.id
                JOIN customers c ON pl.customer_id = c.id
                JOIN visa_services vs ON a.visa_service_id = vs.id
                JOIN countries ct ON vs.country_id = ct.id
                WHERE 1=1";

        $params = [];
        if ($search !== '') {
            $sql .= " AND (pl.link_token LIKE ? OR pl.invoice_number LIKE ? OR c.full_name LIKE ? OR a.application_number LIKE ?)";
            $params = array_fill(0, 4, "%{$search}%");
        }

        if ($status !== '') {
            $sql .= " AND pl.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY pl.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $paymentLinks = $stmt->fetchAll();

        // Meta applications list for generating new links
        $applicationsList = $pdo->query("SELECT a.id, a.application_number, a.total_amount, a.balance_amount, a.passport_number, 
                c.id as customer_id, c.customer_code, c.full_name as customer_name, c.mobile as customer_mobile, c.email as customer_email,
                ct.name as country_name, ct.flag_emoji, vs.name as service_name
            FROM applications a 
            JOIN customers c ON a.customer_id = c.id 
            JOIN visa_services vs ON a.visa_service_id = vs.id
            JOIN countries ct ON vs.country_id = ct.id
            WHERE a.is_archived = 0 
            ORDER BY a.created_at DESC LIMIT 150")->fetchAll();

        require_once dirname(__DIR__) . '/Views/payments/links.php';
    }

    /**
     * Admin: Cancel Payment Link
     */
    public function cancelLink(): void
    {
        AuthMiddleware::handle();
        $linkId = (int)($_POST['link_id'] ?? 0);
        $currentUser = auth_user();

        if ($linkId > 0) {
            \App\Services\PaymentLinkService::cancelLink($linkId, $currentUser['id'] ?? null);
            redirect($_SERVER['HTTP_REFERER'] ?? '/payments', 'Payment link cancelled.', 'success');
        }

        redirect($_SERVER['HTTP_REFERER'] ?? '/payments', 'Invalid link ID.', 'danger');
    }

    /**
     * Dedicated Payment History Ledger
     */
    public function history(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $method = trim($_GET['method'] ?? '');
        $staffId = (int)($_GET['staff_id'] ?? 0);
        $countryId = (int)($_GET['country_id'] ?? 0);
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');

        $sql = "SELECT p.*, 
                    a.application_number, a.id as app_id, a.passport_number, a.total_amount as app_total, a.balance_amount as app_balance,
                    c.full_name as customer_name, c.customer_code, c.mobile as customer_mobile, c.email as customer_email,
                    vs.name as service_name,
                    ct.name as country_name, ct.flag_emoji,
                    u.name as received_by_name
                FROM payments p
                JOIN applications a ON p.application_id = a.id
                JOIN customers c ON p.customer_id = c.id
                JOIN visa_services vs ON a.visa_service_id = vs.id
                JOIN countries ct ON vs.country_id = ct.id
                LEFT JOIN users u ON p.received_by = u.id
                WHERE 1=1";

        $params = [];
        if ($search !== '') {
            $sql .= " AND (p.payment_number LIKE ? OR p.invoice_number LIKE ? OR c.full_name LIKE ? OR c.customer_code LIKE ? OR a.application_number LIKE ? OR a.passport_number LIKE ? OR p.transaction_reference LIKE ? OR c.mobile LIKE ? OR c.email LIKE ?)";
            $term = "%{$search}%";
            $params = array_fill(0, 9, $term);
        }

        if ($status !== '') {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }

        if ($method !== '') {
            $sql .= " AND p.payment_method = ?";
            $params[] = $method;
        }

        if ($staffId > 0) {
            $sql .= " AND p.received_by = ?";
            $params[] = $staffId;
        }

        if ($countryId > 0) {
            $sql .= " AND ct.id = ?";
            $params[] = $countryId;
        }

        if (!empty($dateFrom)) {
            $sql .= " AND p.payment_date >= ?";
            $params[] = $dateFrom;
        }

        if (!empty($dateTo)) {
            $sql .= " AND p.payment_date <= ?";
            $params[] = $dateTo;
        }

        $sql .= " ORDER BY p.payment_date DESC, p.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $payments = $stmt->fetchAll();

        // Totals summary
        $totalCollected = array_sum(array_column($payments, 'amount'));
        $totalTransactions = count($payments);

        // Filter options
        $countriesList = $pdo->query("SELECT id, name FROM countries WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
        $staffList = $pdo->query("SELECT id, name FROM users WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

        require_once dirname(__DIR__) . '/Views/payments/history.php';
    }

    public function exportCsv(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $method = trim($_GET['method'] ?? '');
        $countryId = (int)($_GET['country_id'] ?? 0);
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');

        $sql = "SELECT p.*, 
                    a.application_number, a.passport_number,
                    c.full_name as customer_name, c.customer_code, c.mobile as customer_mobile, c.email as customer_email,
                    vs.name as service_name,
                    ct.name as country_name,
                    u.name as received_by_name
                FROM payments p
                JOIN applications a ON p.application_id = a.id
                JOIN customers c ON p.customer_id = c.id
                LEFT JOIN visa_services vs ON a.visa_service_id = vs.id
                LEFT JOIN countries ct ON vs.country_id = ct.id
                LEFT JOIN users u ON p.received_by = u.id
                WHERE 1=1";

        $params = [];
        if ($search !== '') {
            $sql .= " AND (p.payment_number LIKE ? OR p.invoice_number LIKE ? OR c.full_name LIKE ? OR c.customer_code LIKE ? OR a.application_number LIKE ? OR a.passport_number LIKE ? OR p.transaction_reference LIKE ?)";
            $term = "%{$search}%";
            $params = array_fill(0, 7, $term);
        }

        if ($status !== '') {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }

        if ($method !== '') {
            $sql .= " AND p.payment_method = ?";
            $params[] = $method;
        }

        if ($countryId > 0) {
            $sql .= " AND ct.id = ?";
            $params[] = $countryId;
        }

        if (!empty($dateFrom)) {
            $sql .= " AND p.payment_date >= ?";
            $params[] = $dateFrom;
        }

        if (!empty($dateTo)) {
            $sql .= " AND p.payment_date <= ?";
            $params[] = $dateTo;
        }

        $sql .= " ORDER BY p.payment_date DESC, p.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!defined('IN_TEST_MODE')) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=payments_export_' . date('Ymd_His') . '.csv');
        }

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'Receipt Number', 'Invoice Number', 'Application Number', 'Customer Code', 'Customer Name',
            'Passport Number', 'Country', 'Visa Service', 'Amount', 'Currency', 'Payment Date',
            'Payment Method', 'Transaction Reference', 'Status', 'Received By'
        ]);

        foreach ($payments as $p) {
            fputcsv($out, [
                $p['payment_number'],
                $p['invoice_number'],
                $p['application_number'],
                $p['customer_code'],
                $p['customer_name'],
                $p['passport_number'] ?: 'N/A',
                $p['country_name'] ?? 'N/A',
                $p['service_name'] ?? 'N/A',
                number_format((float)$p['amount'], 2, '.', ''),
                $p['currency'] ?? 'USD',
                $p['payment_date'],
                $p['payment_method'],
                $p['transaction_reference'] ?: 'N/A',
                $p['status'],
                $p['received_by_name'] ?? 'System'
            ]);
        }
        fclose($out);
        if (!defined('IN_TEST_MODE')) {
            exit;
        }
    }

    public function delete(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $isSuperAdmin = ($currentUser['role_slug'] ?? '') === 'super-admin' || (int)($currentUser['role_id'] ?? 0) === 1;
        $canDelete = $isSuperAdmin || user_can('payments.delete') || user_can('payments.manage') || user_can('finance.manage');

        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? '/payments';

        if (!$canDelete) {
            redirect($redirectUrl, 'Unauthorized: You do not have permission to delete payments or invoices. Only Super Admin or authorized finance officers may delete them.', 'danger');
            return;
        }

        $paymentId = (int)($_POST['payment_id'] ?? $_POST['id'] ?? 0);
        if ($paymentId <= 0) {
            redirect($redirectUrl, 'Invalid payment ID.', 'danger');
            return;
        }

        $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            redirect($redirectUrl, 'Payment record not found or already deleted.', 'danger');
            return;
        }

        $appId = (int)($payment['application_id'] ?? 0);
        $paymentNum = $payment['payment_number'] ?? "PAY-{$paymentId}";
        $invNum = $payment['invoice_number'] ?? '';
        $amountFormatted = format_currency((float)($payment['amount'] ?? 0));

        // Clean up attached receipt slip if exists
        if (!empty($payment['receipt_file'])) {
            $slipPath = App::basePath($payment['receipt_file']);
            if (file_exists($slipPath) && is_file($slipPath)) {
                @unlink($slipPath);
            }
        }

        // Delete payment record
        $delStmt = $pdo->prepare("DELETE FROM payments WHERE id = ?");
        $delStmt->execute([$paymentId]);

        // Clean up matching invoices record if orphaned
        if (!empty($invNum)) {
            try {
                $remStmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE invoice_number = ?");
                $remStmt->execute([$invNum]);
                if ((int)$remStmt->fetchColumn() === 0) {
                    $pdo->prepare("DELETE FROM invoices WHERE invoice_number = ?")->execute([$invNum]);
                }
            } catch (\Throwable $e) {}
        }

        // Recalculate application financials
        if ($appId > 0) {
            FinanceService::recalculateApplication($appId);
        }

        AuditService::log(
            'DELETE_PAYMENT',
            'Payments',
            $paymentId,
            "Deleted payment #{$paymentNum} (Invoice: {$invNum}, Amount: {$amountFormatted}) by {$currentUser['name']}"
        );

        redirect($redirectUrl, "Payment #{$paymentNum}" . ($invNum ? " (Invoice: {$invNum})" : "") . " of {$amountFormatted} has been permanently deleted and application balances updated.", 'success');
    }
}
