<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\App;
use App\Config\Database;
use App\Services\PaymentLinkService;
use App\Services\WalletService;
use PDO;

class PaymentLinkController
{
    /**
     * Public Payment Link Checkout View
     */
    public function checkout(): void
    {
        $token = trim($_GET['token'] ?? '');
        if (empty($token)) {
            die('Invalid or missing payment link token.');
        }

        $link = PaymentLinkService::getLinkByToken($token);
        if (!$link) {
            die('Payment link not found or expired.');
        }

        $customer = auth_customer();
        $walletBalance = 0.0;
        if ($customer && (int)$customer['id'] === (int)$link['customer_id']) {
            $wallet = WalletService::getOrCreateWallet((int)$customer['id']);
            $walletBalance = (float)($wallet['balance'] ?? 0.0);
        }

        $pageTitle = "Pay Invoice #{$link['invoice_number']} — VISA TRACK";
        $flash = get_flash();
        require_once dirname(__DIR__) . '/Views/portal/pay_link.php';
    }

    /**
     * Process checkout submission
     */
    public function process(): void
    {
        $token = trim($_POST['token'] ?? '');
        $paymentMethod = trim($_POST['payment_method'] ?? 'Stripe');
        $cardToken = trim($_POST['card_token'] ?? '');

        $link = PaymentLinkService::getLinkByToken($token);
        if (!$link) {
            redirect("/pay?token={$token}", 'Invalid or expired payment link.', 'danger');
        }

        if ($link['status'] === 'Paid') {
            redirect("/pay?token={$token}", 'This invoice has already been settled.', 'info');
        }

        $amount = (float)$link['amount'];
        $customerId = (int)$link['customer_id'];
        $appId = (int)$link['application_id'];

        if ($paymentMethod === 'Customer Wallet') {
            $wallet = WalletService::getOrCreateWallet($customerId);
            $currBalance = (float)($wallet['balance'] ?? 0.0);

            if ($currBalance < $amount) {
                redirect("/pay?token={$token}", "Insufficient wallet balance (Available: $" . number_format($currBalance, 2) . ", Required: $" . number_format($amount, 2) . "). Please choose Card or top up your wallet.", 'danger');
            }

            try {
                $debitRes = WalletService::debit(
                    $customerId,
                    $amount,
                    "Settled visa invoice {$link['invoice_number']} via Wallet",
                    $appId,
                    null,
                    null
                );

                $walletTxnId = (int)($debitRes['id'] ?? null);
                $res = PaymentLinkService::completePayment(
                    $token,
                    'Customer Wallet',
                    $debitRes['transaction_id'] ?? ('WLT-' . strtoupper(substr(md5(uniqid()), 0, 10))),
                    (float)$amount,
                    "Paid using Customer Pre-funded Wallet",
                    $walletTxnId
                );

                if ($res['success']) {
                    redirect("/payments/receipt?id={$res['payment_id']}", "Payment completed successfully from wallet! Receipt generated.", 'success');
                } else {
                    redirect("/pay?token={$token}", $res['message'], 'danger');
                }
            } catch (\Throwable $e) {
                redirect("/pay?token={$token}", 'Wallet transaction error: ' . $e->getMessage(), 'danger');
            }
        } elseif ($paymentMethod === 'Stripe') {
            $paymentIntentId = trim($_POST['payment_intent_id'] ?? $_POST['stripe_payment_intent_id'] ?? $_POST['card_token'] ?? '');
            $verifyRes = \App\Services\StripePaymentService::verifyPaymentIntent(
                $paymentIntentId,
                $amount,
                $link['currency'] ?? 'USD'
            );

            if (!$verifyRes['success']) {
                redirect("/pay?token={$token}", "Stripe transaction verification failed: " . $verifyRes['message'], 'danger');
            }

            $txnRef = $verifyRes['transaction_id'];
            $res = PaymentLinkService::completePayment(
                $token,
                'Stripe',
                $txnRef,
                (float)$amount,
                "Online Credit Card Payment via Stripe Gateway ({$txnRef})",
                null
            );

            if ($res['success']) {
                redirect("/payments/receipt?id={$res['payment_id']}", "Stripe online payment authorized! Receipt generated.", 'success');
            } else {
                redirect("/pay?token={$token}", $res['message'], 'danger');
            }
        } else {
            // Direct Bank Transfer: Do NOT automatically mark as Paid. Requires accounts team audit.
            redirect("/pay?token={$token}", "Bank transfer instructions received. Please forward the bank deposit slip to our finance team. Your payment will be confirmed upon bank clearance.", 'info');
        }
    }

    /**
     * Admin/Staff API to create payment link
     */
    public function create(): void
    {
        \App\Middleware\AuthMiddleware::handle();
        $user = auth_user();
        $roleSlug = $user['role_slug'] ?? '';
        if (!in_array($roleSlug, ['super-admin', 'admin', 'accounts', 'branch-manager'], true)
            && !user_can('payments.create') && !user_can('finance.manage')) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/payments', 'Unauthorized: You do not have permission to generate payment links.', 'danger');
        }

        $appId = (int)($_POST['application_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0.0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($appId <= 0 || $amount < 1.00) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/payments', 'Please select a valid application and an amount of at least $1.00.', 'danger');
        }

        $res = PaymentLinkService::createLink($appId, $amount, $title ?: null, $description ?: null, $user['id'] ?? null);

        if ($res['success']) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/payments', "Payment link generated: {$res['url']}", 'success');
        } else {
            redirect($_SERVER['HTTP_REFERER'] ?? '/payments', $res['message'], 'danger');
        }
    }
}
