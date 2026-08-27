<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use App\Config\Env;
use PDO;
use Exception;

class PaymentLinkService
{
    /**
     * Generate a secure unique payment link connected to Customer, Applicant,
     * Passport, Supplier, Visa Type, Destination Country, and Invoice.
     */
    public static function createLink(
        int $applicationId,
        float $amount,
        ?string $title = null,
        ?string $description = null,
        ?int $userId = null,
        int $expiresInDays = 7,
        ?string $notes = null,
        ?string $dueDate = null
    ): array {
        $pdo = Database::getConnection();

        // 1. Fetch application details
        $stmt = $pdo->prepare("SELECT a.*, 
                c.id as customer_id, c.full_name as customer_name, c.email as customer_email, c.mobile as customer_mobile,
                vs.name as service_name, ct.name as country_name,
                s.company_name as supplier_name
            FROM applications a
            JOIN customers c ON a.customer_id = c.id
            JOIN visa_services vs ON a.visa_service_id = vs.id
            JOIN countries ct ON vs.country_id = ct.id
            LEFT JOIN suppliers s ON a.supplier_id = s.id
            WHERE a.id = ?");
        $stmt->execute([$applicationId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            return ['success' => false, 'message' => 'Application not found.'];
        }

        if ($amount <= 0) {
            $amount = (float)($app['balance_amount'] > 0 ? $app['balance_amount'] : $app['total_amount']);
        }

        // Generate cryptographically secure unguessable 32-hex random token
        $token = bin2hex(random_bytes(16));
        $invoiceNumber = FinanceService::generateInvoiceNumber($applicationId);
        
        $expiresAt = $dueDate 
            ? date('Y-m-d 23:59:59', strtotime($dueDate)) 
            : date('Y-m-d H:i:s', strtotime("+{$expiresInDays} days"));
            
        $defaultTitle = $title ?: "Visa Fee for {$app['customer_name']} — {$app['service_name']} ({$app['country_name']})";
        $combinedDesc = $description ?: "Online payment request for application {$app['application_number']}";
        if ($notes) {
            $combinedDesc .= " | Notes: " . $notes;
        }

        $insStmt = $pdo->prepare("INSERT INTO payment_links (
            link_token, application_id, customer_id, supplier_id, invoice_number,
            amount, currency, title, description, status, expires_at, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, 'USD', ?, ?, 'Pending', ?, ?)");

        $insStmt->execute([
            $token,
            $applicationId,
            (int)$app['customer_id'],
            !empty($app['supplier_id']) ? (int)$app['supplier_id'] : null,
            $invoiceNumber,
            $amount,
            $defaultTitle,
            $combinedDesc,
            $expiresAt,
            $userId
        ]);

        $linkId = (int)$pdo->lastInsertId();
        $baseUrl = (string)Env::get('APP_URL', 'http://localhost:8000');
        $paymentUrl = "{$baseUrl}/pay?token={$token}";

        // Audit Log
        AuditService::log('PAYMENT_LINK_CREATED', 'Payments', $linkId, "Generated payment link for {$app['application_number']} ($" . number_format($amount, 2) . ")", [
            'token' => $token,
            'amount' => $amount,
            'expires_at' => $expiresAt
        ]);

        $customerMobile = preg_replace('/[^0-9]/', '', $app['customer_mobile'] ?? '');
        $whatsappMsg = "Hello {$app['customer_name']},\n\nYour payment request from MS Travel Hub is ready.\n\nApplication: {$app['application_number']}\nVisa Type: {$app['service_name']}\nAmount: $" . number_format($amount, 2) . " USD\n\nPlease complete your payment using the secure link below:\n{$paymentUrl}\n\nThank you,\nMS Travel Hub";

        return [
            'success' => true,
            'link_id' => $linkId,
            'token' => $token,
            'url' => $paymentUrl,
            'amount' => $amount,
            'invoice_number' => $invoiceNumber,
            'customer_name' => $app['customer_name'],
            'customer_mobile' => $app['customer_mobile'],
            'customer_email' => $app['customer_email'],
            'passport_number' => $app['passport_number'],
            'whatsapp_message' => $whatsappMsg,
            'whatsapp_share_url' => "https://api.whatsapp.com/send?phone={$customerMobile}&text=" . urlencode($whatsappMsg)
        ];
    }

    /**
     * Resolve link details by token with real-time status and balance.
     */
    public static function getLinkByToken(string $token): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT pl.*, 
                a.application_number, a.passport_number, a.travel_date, a.return_date, a.total_amount, a.paid_amount, a.balance_amount,
                c.customer_code, c.full_name as customer_name, c.email as customer_email, c.mobile as customer_mobile,
                vs.name as service_name, vs.entry_type, vs.duration,
                ct.name as country_name, ct.flag_emoji,
                s.company_name as supplier_name, s.supplier_code
            FROM payment_links pl
            JOIN applications a ON pl.application_id = a.id
            JOIN customers c ON pl.customer_id = c.id
            JOIN visa_services vs ON a.visa_service_id = vs.id
            JOIN countries ct ON vs.country_id = ct.id
            LEFT JOIN suppliers s ON pl.supplier_id = s.id
            WHERE pl.link_token = ? LIMIT 1");
        $stmt->execute([$token]);
        $link = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$link) {
            return null;
        }

        // Check if expired
        if ($link['status'] === 'Pending' && !empty($link['expires_at']) && strtotime($link['expires_at']) < time()) {
            $pdo->prepare("UPDATE payment_links SET status = 'Expired' WHERE id = ?")->execute([(int)$link['id']]);
            $link['status'] = 'Expired';
        }

        return $link;
    }

    /**
     * Process completion of payment via Online Payment Link / Stripe / Webhook.
     * Includes duplicate protection (Idempotency), auto wallet integration, and notifications.
     */
    public static function completePayment(
        string $token,
        string $paymentMethod,
        string $transactionRef,
        ?int $walletTransactionId = null,
        ?string $notes = null,
        ?float $paidAmount = null
    ): array {
        $pdo = Database::getConnection();
        $link = self::getLinkByToken($token);

        if (!$link) {
            return ['success' => false, 'message' => 'Payment link not found or invalid.'];
        }

        // 1. IDEMPOTENCY / DUPLICATE CHECK: Verify if transactionRef has already been recorded
        if (!empty($transactionRef)) {
            $dupStmt = $pdo->prepare("SELECT id, payment_id FROM payment_transactions WHERE gateway_reference = ? AND status = 'COMPLETED' LIMIT 1");
            $dupStmt->execute([$transactionRef]);
            $existingTxn = $dupStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingTxn) {
                return [
                    'success' => true,
                    'message' => 'Transaction already processed (Idempotent replay).',
                    'payment_id' => (int)$existingTxn['payment_id'],
                    'duplicate' => true
                ];
            }
        }

        if ($link['status'] === 'Paid') {
            return ['success' => false, 'message' => 'This payment link has already been fully settled.'];
        }

        if ($link['status'] === 'Cancelled' || $link['status'] === 'Expired') {
            return ['success' => false, 'message' => "This payment link is {$link['status']} and cannot be paid."];
        }

        $appId = (int)$link['application_id'];
        $customerId = (int)$link['customer_id'];
        $expectedAmount = (float)$link['amount'];
        $amount = $paidAmount !== null ? max(0.01, $paidAmount) : $expectedAmount;
        $receiptNumber = FinanceService::generateReceiptNumber();

        $pdo->beginTransaction();
        try {
            // 2. Record payment in `payments` table
            $payStmt = $pdo->prepare("INSERT INTO payments (
                payment_number, invoice_number, application_id, customer_id, supplier_id,
                amount, currency, payment_date, payment_method, transaction_reference,
                wallet_transaction_id, payment_link_id, payment_type, status, notes
            ) VALUES (?, ?, ?, ?, ?, ?, 'USD', CURRENT_DATE, ?, ?, ?, ?, 'Online Payment', 'Completed', ?)");

            $payStmt->execute([
                $receiptNumber,
                $link['invoice_number'],
                $appId,
                $customerId,
                $link['supplier_id'],
                $amount,
                $paymentMethod,
                $transactionRef,
                $walletTransactionId,
                (int)$link['id'],
                $notes ?: "Settled via Online Payment Link ({$paymentMethod})"
            ]);
            $paymentId = (int)$pdo->lastInsertId();

            // 3. Record in `payment_transactions` table for gateway audit & idempotency
            $ptStmt = $pdo->prepare("INSERT INTO payment_transactions (
                payment_id, application_id, customer_id, transaction_type, amount, currency,
                payment_gateway, gateway_reference, status, gateway_response
            ) VALUES (?, ?, ?, 'DEBIT', ?, 'USD', ?, ?, 'COMPLETED', ?)");
            $ptStmt->execute([
                $paymentId,
                $appId,
                $customerId,
                $amount,
                strtoupper($paymentMethod),
                $transactionRef,
                json_encode(['token' => $token, 'receipt' => $receiptNumber, 'timestamp' => date('c')])
            ]);

            // 4. Check whether to update link status to 'Paid' or 'Partially Paid'
            $isFullyPaid = ($amount >= $expectedAmount);
            $newLinkStatus = $isFullyPaid ? 'Paid' : 'Partially Paid';

            $pdo->prepare("UPDATE payment_links 
                SET status = ?, paid_at = CURRENT_TIMESTAMP, payment_method = ?, transaction_reference = ? 
                WHERE id = ?")
                ->execute([$newLinkStatus, $paymentMethod, $transactionRef, (int)$link['id']]);

            // 5. Configurable Wallet Workflow: Check system_settings for wallet_auto_credit_on_online_payment
            $walletSetting = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'wallet_auto_credit_on_online_payment' LIMIT 1")->fetchColumn();
            if ($walletSetting === '1' || $walletSetting === 'true') {
                try {
                    // Credit to customer wallet then debit for visa
                    WalletService::credit($customerId, $amount, "Online Gateway Credit for {$link['application_number']}", $paymentId, $appId);
                    WalletService::debit($customerId, $amount, "Payment Settlement for {$link['application_number']}", $appId);
                } catch (\Throwable $we) {
                    // Log wallet sync warning without failing primary payment
                    error_log("Wallet auto-sync notice: " . $we->getMessage());
                }
            }

            // 6. Recalculate Application Balances
            FinanceService::recalculateApplication($appId);

            // 7. Record Immutable Audit Log
            AuditService::log('PAYMENT_LINK_SETTLED', 'Payments', $paymentId, "Payment link settled: {$receiptNumber} for {$link['application_number']} via {$paymentMethod} ($" . number_format($amount, 2) . ")", [
                'amount' => $amount,
                'method' => $paymentMethod,
                'ref' => $transactionRef,
                'link_id' => $link['id']
            ]);

            $pdo->commit();

            // 8. Trigger Central Real-Time Multi-Channel Notification (Email + WhatsApp + In-App)
            try {
                $receiptUrl = (string)Env::get('APP_URL', 'http://localhost:8000') . "/payments/receipt?id={$paymentId}";
                NotificationService::trigger('payment.received', [
                    'application_id' => $appId,
                    'customer_id' => $customerId,
                    'application_number' => $link['application_number'],
                    'paymentNumber' => $receiptNumber,
                    'amount' => number_format($amount, 2),
                    'currency' => 'USD',
                    'paymentMethod' => $paymentMethod,
                    'paymentDate' => date('Y-m-d'),
                    'receiptUrl' => $receiptUrl,
                    'portal_link' => "/portal/invoices",
                    'link' => "/payments/receipt?id={$paymentId}",
                ]);
            } catch (\Throwable $e) {}

            return [
                'success' => true,
                'payment_id' => $paymentId,
                'receipt_number' => $receiptNumber,
                'amount' => $amount,
                'is_fully_paid' => $isFullyPaid
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Payment settlement failed: ' . $e->getMessage()];
        }
    }

    /**
     * Cancel an active payment link.
     */
    public static function cancelLink(int $linkId, ?int $userId = null): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE payment_links SET status = 'Cancelled' WHERE id = ? AND status = 'Pending'");
        $stmt->execute([$linkId]);
        
        if ($stmt->rowCount() > 0) {
            AuditService::log('PAYMENT_LINK_CANCELLED', 'Payments', $linkId, "Payment link #{$linkId} cancelled by staff.");
            return true;
        }
        return false;
    }
}
