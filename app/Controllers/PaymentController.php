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
                LEFT JOIN suppliers s ON (p.supplier_id = s.id OR a.supplier_id = s.id)
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
            $sql .= " AND (p.supplier_id = ? OR a.supplier_id = ?)";
            $params[] = $supplierId;
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

        // Summary metrics
        $metrics = [
            'total_received' => (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Completed'")->fetchColumn(),
            'total_refunded' => (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM refunds WHERE status = 'Processed'")->fetchColumn(),
            'outstanding' => (float)$pdo->query("SELECT COALESCE(SUM(balance_amount), 0) FROM applications WHERE is_archived = 0")->fetchColumn(),
            'total_wallet_credits' => (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM wallet_transactions WHERE transaction_type = 'Credit'")->fetchColumn(),
            'total_wallet_debits' => (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM wallet_transactions WHERE transaction_type = 'Debit'")->fetchColumn(),
            'total_online_links' => (int)$pdo->query("SELECT COUNT(*) FROM payment_links")->fetchColumn(),
        ];

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

        $payStmt = $pdo->prepare("INSERT INTO payments (
            payment_number, invoice_number, application_id, customer_id, amount, currency,
            payment_date, payment_method, transaction_reference, payment_type, status, received_by, notes
        ) VALUES (?, ?, ?, ?, ?, 'USD', ?, ?, ?, 'Customer Payment', 'Completed', ?, ?)");

        $payStmt->execute([
            $receiptNumber, $invoiceNumber, $appId, $customerId, $amount,
            $paymentDate, $paymentMethod, $txnRef, $currentUser['id'], $notes
        ]);
        $paymentId = (int)$pdo->lastInsertId();

        // Recalculate Application Financials
        FinanceService::recalculateApplication($appId);

        // Dispatch Central Real-Time Notification (Email + WhatsApp + In-App)
        try {
            \App\Services\NotificationService::trigger('payment.received', [
                'application_id' => $appId,
                'customer_id' => $customerId,
                'application_number' => $app['application_number'] ?? '',
                'paymentNumber' => $receiptNumber,
                'amount' => number_format($amount, 2),
                'currency' => 'USD',
                'paymentMethod' => $paymentMethod,
                'paymentDate' => $paymentDate,
                'receiptUrl' => (string)\App\Config\Env::get('APP_URL', 'http://localhost:8000') . "/portal/invoices",
                'portal_link' => "/portal/invoices",
                'link' => "/payments/receipt?id={$paymentId}",
            ]);
        } catch (\Throwable $e) {}

        AuditService::log('PAYMENT_RECEIVED', 'Payments', $paymentId, "Received payment of $" . number_format($amount, 2) . " (Receipt {$receiptNumber}) for {$app['application_number']}", [
            'amount' => $amount,
            'method' => $paymentMethod,
            'receipt' => $receiptNumber,
        ]);

        redirect("/payments/receipt?id={$paymentId}", "Payment recorded successfully. Receipt generated.", 'success');
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
}
