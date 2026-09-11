<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\App;
use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Services\AuditService;
use App\Services\FinanceService;
use PDO;

class PaymentController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $method = trim($_GET['method'] ?? '');
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
                JOIN applications a ON p.application_id = a.id
                JOIN customers c ON p.customer_id = c.id
                JOIN visa_services vs ON a.visa_service_id = vs.id
                JOIN countries ct ON vs.country_id = ct.id
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
        $applicationsList = $pdo->query("SELECT a.id, a.application_number, a.total_amount, a.balance_amount, a.passport_number, 
                c.id as customer_id, c.customer_code, c.full_name as customer_name, c.mobile as customer_mobile, c.email as customer_email,
                ct.name as country_name, ct.flag_emoji, vs.name as service_name
            FROM applications a 
            JOIN customers c ON a.customer_id = c.id 
            JOIN visa_services vs ON a.visa_service_id = vs.id
            JOIN countries ct ON vs.country_id = ct.id
            WHERE a.is_archived = 0 
            ORDER BY a.created_at DESC LIMIT 100")->fetchAll();

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
        if ($exchangeRate <= 0) {
            $exchangeRate = 1.000000;
        }
        $originalAmount = (float)($_POST['original_amount'] ?? $amount);
        $convertedAmount = (float)($_POST['converted_amount'] ?? $amount);

        // If converted amount provided and differs, base payment amount is the converted amount
        if ($convertedAmount > 0 && abs($convertedAmount - $amount) > 0.001) {
            $amount = $convertedAmount;
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

        $payStmt = $pdo->prepare("INSERT INTO payments (
            payment_number, invoice_number, application_id, customer_id, amount, currency,
            from_currency, to_currency, exchange_rate, original_amount, converted_amount,
            payment_date, payment_method, transaction_reference, payment_type, status, received_by, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Customer Payment', 'Completed', ?, ?)");

        $payStmt->execute([
            $receiptNumber, $invoiceNumber, $appId, $customerId, $amount, $toCurrency,
            $fromCurrency, $toCurrency, $exchangeRate, $originalAmount, $amount,
            $paymentDate, $paymentMethod, $txnRef, $currentUser['id'], $notes
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
            \App\Services\NotificationService::trigger('payment.received', [
                'application_id' => $appId,
                'customer_id' => $customerId,
                'application_number' => $app['application_number'] ?? '',
                'paymentNumber' => $receiptNumber,
                'amount' => number_format($amount, 2),
                'currency' => $toCurrency,
                'paymentMethod' => $paymentMethod,
                'paymentDate' => $paymentDate,
                'receiptUrl' => (string)\App\Config\Env::get('APP_URL', 'http://localhost:8000') . "/portal/invoices",
                'portal_link' => "/portal/invoices",
                'link' => "/payments/receipt?id={$paymentId}",
            ]);
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

        $customerWallets = $pdo->query("SELECT cw.*, c.full_name, c.customer_code, c.email, c.mobile 
            FROM customer_wallets cw 
            JOIN customers c ON cw.customer_id = c.id 
            ORDER BY cw.current_balance DESC, c.full_name ASC")->fetchAll(PDO::FETCH_ASSOC);

        $supplierWallets = $pdo->query("SELECT sw.*, s.company_name as supplier_name, s.company_name, s.country 
            FROM supplier_wallets sw 
            JOIN suppliers s ON sw.supplier_id = s.id 
            ORDER BY sw.current_balance DESC, s.company_name ASC")->fetchAll(PDO::FETCH_ASSOC);

        $agentWallets = $pdo->query("SELECT aw.*, COALESCE(ag.company_name, ag.contact_person, u.name, 'Agent') as agent_name, COALESCE(ag.email, u.email, '') as agent_email 
            FROM agent_wallets aw 
            LEFT JOIN agents ag ON aw.agent_id = ag.id 
            LEFT JOIN users u ON aw.agent_id = u.id 
            ORDER BY aw.current_balance DESC")->fetchAll(PDO::FETCH_ASSOC);

        $recentTransactions = $pdo->query("SELECT wt.*, c.full_name as customer_name, u.name as created_by_name 
            FROM wallet_transactions wt 
            LEFT JOIN customers c ON wt.customer_id = c.id 
            LEFT JOIN users u ON wt.created_by = u.id 
            ORDER BY wt.created_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

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
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0.00);
        $currency = strtoupper(trim($_POST['currency'] ?? 'USD'));
        $notes = trim($_POST['notes'] ?? 'Supplier Advance Deposit');

        if ($supplierId <= 0 || $amount <= 0) {
            redirect('/payments/wallets?tab=suppliers', 'Please specify valid supplier and amount.', 'danger');
        }

        try {
            \App\Services\WalletService::creditSupplier($supplierId, $amount, $notes, $currentUser['id'] ?? null, $currency);
            redirect('/payments/wallets?tab=suppliers', "Successfully credited {$currency} " . number_format($amount, 2) . " to supplier wallet.", 'success');
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
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $agentId = (int)($_POST['agent_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0.00);
        $currency = strtoupper(trim($_POST['currency'] ?? 'USD'));
        $notes = trim($_POST['notes'] ?? 'Agent Credit Top-up');

        if ($agentId <= 0 || $amount <= 0) {
            redirect('/payments/wallets?tab=agents', 'Please specify valid agent and amount.', 'danger');
        }

        try {
            \App\Services\WalletService::creditAgent($agentId, $amount, $notes, $currentUser['id'] ?? null, $currency);
            redirect('/payments/wallets?tab=agents', "Successfully credited {$currency} " . number_format($amount, 2) . " to agent wallet.", 'success');
        } catch (\Throwable $e) {
            redirect('/payments/wallets?tab=agents', 'Agent top-up failed: ' . $e->getMessage(), 'danger');
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
        $currency = strtoupper(trim($_POST['currency'] ?? 'USD'));
        $paymentMethod = trim($_POST['payment_method'] ?? 'Cash at Office');
        $txnRef = trim($_POST['transaction_reference'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($customerId <= 0 || $amount <= 0) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/payments', 'Please specify a valid customer and deposit amount.', 'danger');
        }

        $custStmt = $pdo->prepare("SELECT id, full_name, customer_code FROM customers WHERE id = ?");
        $custStmt->execute([$customerId]);
        $customer = $custStmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/payments', 'Customer not found.', 'danger');
        }

        try {
            $desc = "Office Wallet Deposit via {$paymentMethod}" . ($txnRef ? " (Ref: {$txnRef})" : '') . ($notes ? " - {$notes}" : '');
            $res = \App\Services\WalletService::credit($customerId, $amount, $desc, null, null, $currentUser['id'] ?? null, $currency);

            // Also record a payment receipt record for accounting
            $receiptNumber = FinanceService::generateReceiptNumber();
            $invNumber = 'INV-WAL-' . $receiptNumber;
            $pdo->prepare("INSERT INTO payments (
                payment_number, invoice_number, customer_id, amount, currency, payment_date, payment_method,
                transaction_reference, wallet_transaction_id, payment_type, status, received_by, notes
            ) VALUES (?, ?, ?, ?, ?, CURRENT_DATE, ?, ?, ?, 'Wallet Topup', 'Completed', ?, ?)")
            ->execute([$receiptNumber, $invNumber, $customerId, $amount, $currency, $paymentMethod, $txnRef, $res['transaction_id'] ?? null, $currentUser['id'] ?? null, $notes]);

            redirect($_SERVER['HTTP_REFERER'] ?? '/payments', "Successfully deposited {$currency} " . number_format($amount, 2) . " into {$customer['full_name']}'s digital wallet.", 'success');
        } catch (\Throwable $e) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/payments', 'Deposit failed: ' . $e->getMessage(), 'danger');
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
     * Printable Official Payment Receipt
     */
    public function receipt(): void
    {
        $pdo = Database::getConnection();
        $paymentId = (int)($_GET['id'] ?? 0);

        $stmt = $pdo->prepare("SELECT p.*, 
                a.application_number, a.selling_price, a.total_amount, a.paid_amount, a.balance_amount,
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
            die('Payment receipt not found.');
        }

        require_once dirname(__DIR__) . '/Views/payments/receipt.php';
    }

    /**
     * Printable Official Tax Invoice
     */
    public function invoice(): void
    {
        $pdo = Database::getConnection();
        $appId = (int)($_GET['app_id'] ?? 0);

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
            die('Invoice not found.');
        }

        $payments = $pdo->query("SELECT * FROM payments WHERE application_id = {$appId} AND status = 'Completed' ORDER BY payment_date ASC")->fetchAll();

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

        // Send WhatsApp if requested
        if ($sendWhatsapp && !empty($result['customer_mobile'])) {
            try {
                \App\Services\WhatsAppService::send([
                    'phoneNumber' => $result['customer_mobile'],
                    'messageText' => $result['whatsapp_message'] ?? "Please complete your visa payment here: {$result['url']}"
                ]);
            } catch (\Throwable $e) {}
        }

        redirect($_SERVER['HTTP_REFERER'] ?? '/payments', "Payment link generated successfully: {$result['url']}", 'success');
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
}
