<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\App;
use App\Config\Database;
use App\Config\Env;
use PDO;

class StripePaymentService
{
    private static function getSecretKey(): ?string
    {
        $key = Env::get('STRIPE_SECRET_KEY') ?: getenv('STRIPE_SECRET_KEY');
        return !empty($key) ? trim((string)$key) : null;
    }

    public static function getPublishableKey(): ?string
    {
        $key = Env::get('STRIPE_PUBLISHABLE_KEY') ?: getenv('STRIPE_PUBLISHABLE_KEY');
        return !empty($key) ? trim((string)$key) : null;
    }

    public static function isConfigured(): bool
    {
        return !empty(self::getSecretKey());
    }

    /**
     * Create real Stripe PaymentIntent on Stripe Gateway
     */
    public static function createPaymentIntent(float $amount, string $currency, array $metadata = []): array
    {
        $secretKey = self::getSecretKey();
        if (!$secretKey) {
            return [
                'success' => false,
                'message' => 'Stripe Gateway is not configured. Please set STRIPE_SECRET_KEY in environment settings.'
            ];
        }

        // Stripe expects amount in smallest currency unit (e.g. cents for USD)
        $amountInCents = (int)round($amount * 100);
        $currencyLower = strtolower(trim($currency));

        $postData = [
            'amount' => $amountInCents,
            'currency' => $currencyLower,
            'payment_method_types' => ['card'],
        ];

        foreach ($metadata as $k => $v) {
            $postData["metadata[{$k}]"] = (string)$v;
        }

        $res = self::stripeRequest('POST', 'https://api.stripe.com/v1/payment_intents', $postData, $secretKey);
        if (!$res['success']) {
            return $res;
        }

        $data = $res['data'];
        return [
            'success' => true,
            'payment_intent_id' => $data['id'] ?? null,
            'client_secret' => $data['client_secret'] ?? null,
            'amount' => ($data['amount'] ?? 0) / 100,
            'currency' => strtoupper($data['currency'] ?? $currency),
            'status' => $data['status'] ?? 'unknown',
        ];
    }

    /**
     * Verify Stripe PaymentIntent directly against Stripe API
     */
    public static function verifyPaymentIntent(string $paymentIntentId, float $expectedAmount, string $expectedCurrency = 'USD'): array
    {
        $paymentIntentId = trim($paymentIntentId);
        if (empty($paymentIntentId)) {
            return ['success' => false, 'message' => 'Missing Stripe payment intent identifier.'];
        }

        $secretKey = self::getSecretKey();
        if (!$secretKey) {
            // In development/test mode without credentials, prevent fake random spoofing:
            // Only accept simulated transactions if test environment is explicitly flagged
            if (Env::get('APP_ENV') === 'testing' || Env::get('NOTIFICATION_ENV') === 'testing') {
                return [
                    'success' => true,
                    'transaction_id' => $paymentIntentId,
                    'amount' => $expectedAmount,
                    'currency' => $expectedCurrency,
                    'status' => 'succeeded'
                ];
            }
            return [
                'success' => false,
                'message' => 'Stripe secret key is not configured. Live transaction verification cannot proceed.'
            ];
        }

        // Check for replay attacks: Has this payment intent already been consumed by another payment?
        $pdo = Database::getConnection();
        $checkStmt = $pdo->prepare("SELECT id FROM payments WHERE transaction_reference = ? LIMIT 1");
        $checkStmt->execute([$paymentIntentId]);
        if ($checkStmt->fetch()) {
            return ['success' => false, 'message' => 'Transaction has already been processed (duplicate replay rejected).'];
        }

        $url = 'https://api.stripe.com/v1/payment_intents/' . urlencode($paymentIntentId);
        $res = self::stripeRequest('GET', $url, [], $secretKey);

        if (!$res['success']) {
            return $res;
        }

        $data = $res['data'];
        $status = $data['status'] ?? '';
        $chargedCents = (int)($data['amount_received'] ?? $data['amount'] ?? 0);
        $chargedCurrency = strtoupper($data['currency'] ?? '');
        $expectedCents = (int)round($expectedAmount * 100);

        if ($status !== 'succeeded') {
            return [
                'success' => false,
                'message' => "Stripe payment status is '{$status}', expected 'succeeded'."
            ];
        }

        // Verify amount
        if ($chargedCents < $expectedCents) {
            return [
                'success' => false,
                'message' => "Payment amount mismatch: received $" . number_format($chargedCents / 100, 2) . " but expected $" . number_format($expectedAmount, 2) . "."
            ];
        }

        // Verify currency
        if ($chargedCurrency !== strtoupper($expectedCurrency)) {
            return [
                'success' => false,
                'message' => "Payment currency mismatch: received {$chargedCurrency} but expected {$expectedCurrency}."
            ];
        }

        return [
            'success' => true,
            'transaction_id' => $data['id'],
            'amount' => $chargedCents / 100,
            'currency' => $chargedCurrency,
            'status' => 'succeeded',
            'charge_id' => $data['latest_charge'] ?? null
        ];
    }

    private static function stripeRequest(string $method, string $url, array $postData, string $secretKey): array
    {
        $ch = curl_init();
        $headers = [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: MS-Travel-Hub-VisaTrack/2.0',
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        }

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            return ['success' => false, 'message' => 'Stripe API communication error: ' . $err];
        }

        $decoded = json_decode((string)$response, true);
        if ($httpCode >= 400 || isset($decoded['error'])) {
            $msg = $decoded['error']['message'] ?? "Stripe API returned HTTP {$httpCode}";
            return ['success' => false, 'message' => $msg];
        }

        return ['success' => true, 'data' => $decoded];
    }
}
