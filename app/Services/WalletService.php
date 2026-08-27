<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use PDO;
use Exception;

class WalletService
{
    /**
     * Get or automatically initialize customer wallet
     */
    public static function getOrCreateWallet(int $customerId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM customer_wallets WHERE customer_id = ? LIMIT 1");
        $stmt->execute([$customerId]);
        $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$wallet) {
            $pdo->prepare("INSERT INTO customer_wallets (customer_id, currency, current_balance, total_credited, total_debited) VALUES (?, 'USD', 0.00, 0.00, 0.00)")
                ->execute([$customerId]);
            $stmt->execute([$customerId]);
            $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $wallet ?: [
            'id' => 0,
            'customer_id' => $customerId,
            'currency' => 'USD',
            'current_balance' => 0.00,
            'total_credited' => 0.00,
            'total_debited' => 0.00
        ];
    }

    /**
     * Credit funds to customer wallet (e.g. advance deposit, Stripe online payment, refund credit)
     */
    public static function credit(int $customerId, float $amount, string $description, ?int $paymentId = null, ?int $applicationId = null, ?int $createdBy = null): array
    {
        if ($amount <= 0) {
            throw new Exception("Credit amount must be greater than zero.");
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $wallet = self::getOrCreateWallet($customerId);
            $walletId = (int)$wallet['id'];

            $newBalance = (float)$wallet['current_balance'] + $amount;
            $newTotalCredited = (float)$wallet['total_credited'] + $amount;

            // Update wallet balance
            $update = $pdo->prepare("UPDATE customer_wallets SET current_balance = ?, total_credited = ? WHERE id = ?");
            $update->execute([$newBalance, $newTotalCredited, $walletId]);

            // Generate unique transaction ID
            $txnId = 'WTX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            // Record transaction ledger entry
            $insert = $pdo->prepare("INSERT INTO wallet_transactions 
                (transaction_id, wallet_id, customer_id, transaction_type, amount, balance_after, description, payment_id, application_id, created_by)
                VALUES (?, ?, ?, 'Credit', ?, ?, ?, ?, ?, ?)");
            $insert->execute([$txnId, $walletId, $customerId, $amount, $newBalance, $description, $paymentId, $applicationId, $createdBy]);

            $pdo->commit();

            AuditService::log('WALLET_CREDIT', 'Wallet', $customerId, "Credited $" . number_format($amount, 2) . " to customer wallet. Ref: {$txnId}");

            return [
                'success' => true,
                'transaction_id' => $txnId,
                'new_balance' => $newBalance
            ];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Debit funds from customer wallet (e.g. visa processing fee settlement)
     */
    public static function debit(int $customerId, float $amount, string $description, ?int $applicationId = null, ?int $createdBy = null): array
    {
        if ($amount <= 0) {
            throw new Exception("Debit amount must be greater than zero.");
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $wallet = self::getOrCreateWallet($customerId);
            $walletId = (int)$wallet['id'];
            $currentBalance = (float)$wallet['current_balance'];

            if ($currentBalance < $amount) {
                throw new Exception("Insufficient wallet balance. Available: $" . number_format($currentBalance, 2) . ", Required: $" . number_format($amount, 2));
            }

            $newBalance = $currentBalance - $amount;
            $newTotalDebited = (float)$wallet['total_debited'] + $amount;

            // Update wallet balance
            $update = $pdo->prepare("UPDATE customer_wallets SET current_balance = ?, total_debited = ? WHERE id = ?");
            $update->execute([$newBalance, $newTotalDebited, $walletId]);

            // Generate unique transaction ID
            $txnId = 'WTX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            // Record transaction ledger entry
            $insert = $pdo->prepare("INSERT INTO wallet_transactions 
                (transaction_id, wallet_id, customer_id, transaction_type, amount, balance_after, description, payment_id, application_id, created_by)
                VALUES (?, ?, ?, 'Debit', ?, ?, ?, NULL, ?, ?)");
            $insert->execute([$txnId, $walletId, $customerId, $amount, $newBalance, $description, $applicationId, $createdBy]);

            $pdo->commit();

            AuditService::log('WALLET_DEBIT', 'Wallet', $customerId, "Debited $" . number_format($amount, 2) . " from customer wallet. Ref: {$txnId}");

            return [
                'success' => true,
                'transaction_id' => $txnId,
                'new_balance' => $newBalance
            ];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Get wallet transaction ledger
     */
    public static function getTransactions(int $customerId, int $limit = 50): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT wt.*, a.application_number, u.name as created_by_name
            FROM wallet_transactions wt
            LEFT JOIN applications a ON wt.application_id = a.id
            LEFT JOIN users u ON wt.created_by = u.id
            WHERE wt.customer_id = ?
            ORDER BY wt.created_at DESC
            LIMIT ?");
        $stmt->bindValue(1, $customerId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
