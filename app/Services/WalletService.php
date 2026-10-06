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
    public static function getOrCreateWallet(int $customerId, string $currency = 'USD'): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM customer_wallets WHERE customer_id = ? LIMIT 1");
        $stmt->execute([$customerId]);
        $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$wallet) {
            $pdo->prepare("INSERT INTO customer_wallets (customer_id, currency, current_balance, total_credited, total_debited) VALUES (?, ?, 0.00, 0.00, 0.00)")
                ->execute([$customerId, $currency]);
            $stmt->execute([$customerId]);
            $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $wallet ?: [
            'id' => 0,
            'customer_id' => $customerId,
            'currency' => $currency,
            'current_balance' => 0.00,
            'total_credited' => 0.00,
            'total_debited' => 0.00
        ];
    }

    /**
     * Credit funds to customer wallet
     */
    public static function credit(
        int $customerId,
        float $amount,
        string $description,
        ?int $paymentId = null,
        ?int $applicationId = null,
        ?int $createdBy = null,
        string $currency = 'USD',
        ?float $originalAmount = null,
        float $exchangeRate = 1.000000,
        ?string $paymentMethod = null,
        ?string $reference = null,
        ?int $invoiceId = null,
        ?float $convertedAmount = null,
        ?string $convertedCurrency = null
    ): array {
        if ($amount <= 0) {
            throw new Exception("Credit amount must be greater than zero.");
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $wallet = self::getOrCreateWallet($customerId, $currency);
            $walletId = (int)$wallet['id'];

            // Row-level lock on wallet record
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $lockSql = ($driver === 'mysql')
                ? "SELECT id, current_balance, total_credited, total_debited FROM customer_wallets WHERE id = ? FOR UPDATE"
                : "SELECT id, current_balance, total_credited, total_debited FROM customer_wallets WHERE id = ?";
            $lockStmt = $pdo->prepare($lockSql);
            $lockStmt->execute([$walletId]);
            $lockedWallet = $lockStmt->fetch(PDO::FETCH_ASSOC) ?: $wallet;

            $newBalance = (float)$lockedWallet['current_balance'] + $amount;
            $newTotalCredited = (float)$lockedWallet['total_credited'] + $amount;

            // Update wallet balance atomically
            $update = $pdo->prepare("UPDATE customer_wallets SET current_balance = ?, total_credited = ? WHERE id = ?");
            $update->execute([$newBalance, $newTotalCredited, $walletId]);

            // Generate unique transaction ID
            $txnId = 'WTX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            // Record transaction ledger entry
            $insert = $pdo->prepare("INSERT INTO wallet_transactions 
                (transaction_id, wallet_id, customer_id, transaction_type, amount, balance_after, currency, original_amount, exchange_rate, description, payment_id, application_id, created_by, invoice_id, payment_method, reference, converted_amount, converted_currency)
                VALUES (?, ?, ?, 'Credit', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert->execute([
                $txnId, $walletId, $customerId, $amount, $newBalance, $currency,
                $originalAmount ?? $amount, $exchangeRate, $description, $paymentId, $applicationId, $createdBy,
                $invoiceId, $paymentMethod, $reference, $convertedAmount ?? $amount, $convertedCurrency ?? $currency
            ]);

            $pdo->commit();

            AuditService::log('WALLET_CREDIT', 'Wallet', $customerId, "Credited {$currency} " . number_format($amount, 2) . " to customer wallet. Ref: {$txnId}");

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
     * Debit funds from customer wallet
     */
    public static function debit(
        int $customerId,
        float $amount,
        string $description,
        ?int $applicationId = null,
        ?int $createdBy = null,
        string $currency = 'USD',
        ?float $originalAmount = null,
        float $exchangeRate = 1.000000,
        ?string $paymentMethod = null,
        ?string $reference = null,
        ?int $invoiceId = null,
        ?float $convertedAmount = null,
        ?string $convertedCurrency = null
    ): array {
        if ($amount <= 0) {
            throw new Exception("Debit amount must be greater than zero.");
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $wallet = self::getOrCreateWallet($customerId, $currency);
            $walletId = (int)$wallet['id'];

            // Row-level lock on wallet record
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $lockSql = ($driver === 'mysql')
                ? "SELECT id, current_balance, total_debited FROM customer_wallets WHERE id = ? FOR UPDATE"
                : "SELECT id, current_balance, total_debited FROM customer_wallets WHERE id = ?";
            $lockStmt = $pdo->prepare($lockSql);
            $lockStmt->execute([$walletId]);
            $lockedWallet = $lockStmt->fetch(PDO::FETCH_ASSOC) ?: $wallet;

            $currentBalance = (float)$lockedWallet['current_balance'];
            if ($currentBalance < $amount) {
                throw new Exception("Insufficient wallet balance. Available: {$currency} " . number_format($currentBalance, 2) . ", Required: {$currency} " . number_format($amount, 2));
            }

            // Atomic conditional deduction to eliminate race conditions
            $update = $pdo->prepare("UPDATE customer_wallets SET current_balance = current_balance - ?, total_debited = total_debited + ? WHERE id = ? AND current_balance >= ?");
            $update->execute([$amount, $amount, $walletId, $amount]);

            if ($update->rowCount() === 0) {
                throw new Exception("Wallet concurrency conflict: Balance was modified by another operation. Please retry.");
            }

            $newBalance = $currentBalance - $amount;

            // Generate unique transaction ID
            $txnId = 'WTX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            // Record transaction ledger entry
            $insert = $pdo->prepare("INSERT INTO wallet_transactions 
                (transaction_id, wallet_id, customer_id, transaction_type, amount, balance_after, currency, original_amount, exchange_rate, description, payment_id, application_id, created_by, invoice_id, payment_method, reference, converted_amount, converted_currency)
                VALUES (?, ?, ?, 'Debit', ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?)");
            $insert->execute([
                $txnId, $walletId, $customerId, $amount, $newBalance, $currency,
                $originalAmount ?? $amount, $exchangeRate, $description, $applicationId, $createdBy,
                $invoiceId, $paymentMethod, $reference, $convertedAmount ?? $amount, $convertedCurrency ?? $currency
            ]);

            $pdo->commit();

            AuditService::log('WALLET_DEBIT', 'Wallet', $customerId, "Debited {$currency} " . number_format($amount, 2) . " from customer wallet. Ref: {$txnId}");

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
     * Get wallet transaction ledger for customer
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // SUPPLIER WALLET MANAGEMENT
    // ==========================================

    public static function getOrCreateSupplierWallet(int $supplierId, string $currency = 'USD'): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM supplier_wallets WHERE supplier_id = ? LIMIT 1");
        $stmt->execute([$supplierId]);
        $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$wallet) {
            $pdo->prepare("INSERT INTO supplier_wallets (supplier_id, currency, current_balance, total_credited, total_debited) VALUES (?, ?, 0.00, 0.00, 0.00)")
                ->execute([$supplierId, $currency]);
            $stmt->execute([$supplierId]);
            $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $wallet ?: [
            'id' => 0,
            'supplier_id' => $supplierId,
            'currency' => $currency,
            'current_balance' => 0.00,
            'total_credited' => 0.00,
            'total_debited' => 0.00
        ];
    }

    public static function creditSupplier(
        int $supplierId,
        float $amount,
        string $description,
        ?int $createdBy = null,
        string $currency = 'USD',
        ?float $originalAmount = null,
        float $exchangeRate = 1.0,
        ?string $paymentMethod = 'Bank Transfer',
        ?string $reference = null,
        ?float $convertedAmount = null,
        ?int $applicationId = null,
        ?int $paymentId = null
    ): array {
        if ($amount <= 0) {
            throw new Exception("Supplier credit amount must be greater than zero.");
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $wallet = self::getOrCreateSupplierWallet($supplierId, $currency);
            $walletId = (int)$wallet['id'];

            // Row-level lock on supplier wallet record
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $lockSql = ($driver === 'mysql')
                ? "SELECT id, current_balance, total_credited, total_debited FROM supplier_wallets WHERE id = ? FOR UPDATE"
                : "SELECT id, current_balance, total_credited, total_debited FROM supplier_wallets WHERE id = ?";
            $lockStmt = $pdo->prepare($lockSql);
            $lockStmt->execute([$walletId]);
            $lockedWallet = $lockStmt->fetch(PDO::FETCH_ASSOC) ?: $wallet;

            $newBalance = (float)$lockedWallet['current_balance'] + $amount;
            $newTotalCredited = (float)$lockedWallet['total_credited'] + $amount;

            $pdo->prepare("UPDATE supplier_wallets SET current_balance = ?, total_credited = ? WHERE id = ?")
                ->execute([$newBalance, $newTotalCredited, $walletId]);

            $txnId = 'SWTX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            $insert = $pdo->prepare("INSERT INTO supplier_wallet_transactions 
                (transaction_id, wallet_id, supplier_id, transaction_type, amount, balance_after, currency, original_amount, exchange_rate, description, created_by, payment_method, reference, converted_amount, application_id, payment_id)
                VALUES (?, ?, ?, 'Credit', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert->execute([
                $txnId, $walletId, $supplierId, $amount, $newBalance, $currency,
                $originalAmount ?? $amount, $exchangeRate, $description, $createdBy,
                $paymentMethod, $reference, $convertedAmount ?? $amount, $applicationId, $paymentId
            ]);

            $pdo->commit();
            AuditService::log('SUPPLIER_WALLET_CREDIT', 'SupplierWallet', $supplierId, "Credited {$currency} " . number_format($amount, 2) . " to supplier wallet #{$supplierId}. Ref: {$txnId}");

            return ['success' => true, 'transaction_id' => $txnId, 'new_balance' => $newBalance];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function debitSupplier(
        int $supplierId,
        float $amount,
        string $description,
        ?int $createdBy = null,
        string $currency = 'USD',
        ?float $originalAmount = null,
        float $exchangeRate = 1.0,
        ?string $paymentMethod = 'Bank Transfer',
        ?string $reference = null,
        ?float $convertedAmount = null,
        ?int $applicationId = null,
        ?int $paymentId = null
    ): array {
        if ($amount <= 0) {
            throw new Exception("Supplier debit amount must be greater than zero.");
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $wallet = self::getOrCreateSupplierWallet($supplierId, $currency);
            $walletId = (int)$wallet['id'];

            // Row-level lock on supplier wallet record
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $lockSql = ($driver === 'mysql')
                ? "SELECT id, current_balance, total_debited FROM supplier_wallets WHERE id = ? FOR UPDATE"
                : "SELECT id, current_balance, total_debited FROM supplier_wallets WHERE id = ?";
            $lockStmt = $pdo->prepare($lockSql);
            $lockStmt->execute([$walletId]);
            $lockedWallet = $lockStmt->fetch(PDO::FETCH_ASSOC) ?: $wallet;

            $currentBalance = (float)$lockedWallet['current_balance'];
            if ($currentBalance < $amount) {
                throw new Exception("Insufficient supplier wallet balance. Available: {$currency} " . number_format($currentBalance, 2) . ", Required: {$currency} " . number_format($amount, 2));
            }

            // Atomic conditional update
            $update = $pdo->prepare("UPDATE supplier_wallets SET current_balance = current_balance - ?, total_debited = total_debited + ? WHERE id = ? AND current_balance >= ?");
            $update->execute([$amount, $amount, $walletId, $amount]);

            if ($update->rowCount() === 0) {
                throw new Exception("Supplier wallet concurrency conflict: Balance was modified concurrently. Please retry.");
            }

            $newBalance = $currentBalance - $amount;

            $txnId = 'SWTX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            $insert = $pdo->prepare("INSERT INTO supplier_wallet_transactions 
                (transaction_id, wallet_id, supplier_id, transaction_type, amount, balance_after, currency, original_amount, exchange_rate, description, created_by, payment_method, reference, converted_amount, application_id, payment_id)
                VALUES (?, ?, ?, 'Debit', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert->execute([
                $txnId, $walletId, $supplierId, $amount, $newBalance, $currency,
                $originalAmount ?? $amount, $exchangeRate, $description, $createdBy,
                $paymentMethod, $reference, $convertedAmount ?? $amount, $applicationId, $paymentId
            ]);

            $pdo->commit();
            AuditService::log('SUPPLIER_WALLET_DEBIT', 'SupplierWallet', $supplierId, "Debited {$currency} " . number_format($amount, 2) . " from supplier wallet #{$supplierId}. Ref: {$txnId}");

            return ['success' => true, 'transaction_id' => $txnId, 'new_balance' => $newBalance];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function getSupplierTransactions(int $supplierId, int $limit = 50): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT swt.*, u.name as created_by_name
            FROM supplier_wallet_transactions swt
            LEFT JOIN users u ON swt.created_by = u.id
            WHERE swt.supplier_id = ?
            ORDER BY swt.created_at DESC
            LIMIT ?");
        $stmt->bindValue(1, $supplierId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // AGENT WALLET MANAGEMENT
    // ==========================================

    public static function getOrCreateAgentWallet(int $agentId, string $currency = 'USD'): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM agent_wallets WHERE agent_id = ? LIMIT 1");
        $stmt->execute([$agentId]);
        $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$wallet) {
            $pdo->prepare("INSERT INTO agent_wallets (agent_id, currency, current_balance, total_credited, total_debited) VALUES (?, ?, 0.00, 0.00, 0.00)")
                ->execute([$agentId, $currency]);
            $stmt->execute([$agentId]);
            $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $wallet ?: [
            'id' => 0,
            'agent_id' => $agentId,
            'currency' => $currency,
            'current_balance' => 0.00,
            'total_credited' => 0.00,
            'total_debited' => 0.00
        ];
    }

    public static function creditAgent(
        int $agentId,
        float $amount,
        string $description,
        ?int $createdBy = null,
        string $currency = 'USD',
        ?float $originalAmount = null,
        float $exchangeRate = 1.0,
        ?string $paymentMethod = 'Bank Transfer',
        ?string $reference = null,
        ?float $convertedAmount = null,
        ?int $applicationId = null,
        ?int $paymentId = null
    ): array {
        if ($amount <= 0) {
            throw new Exception("Agent credit amount must be greater than zero.");
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $wallet = self::getOrCreateAgentWallet($agentId, $currency);
            $walletId = (int)$wallet['id'];

            // Row-level lock on agent wallet record
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $lockSql = ($driver === 'mysql')
                ? "SELECT id, current_balance, total_credited, total_debited FROM agent_wallets WHERE id = ? FOR UPDATE"
                : "SELECT id, current_balance, total_credited, total_debited FROM agent_wallets WHERE id = ?";
            $lockStmt = $pdo->prepare($lockSql);
            $lockStmt->execute([$walletId]);
            $lockedWallet = $lockStmt->fetch(PDO::FETCH_ASSOC) ?: $wallet;

            $newBalance = (float)$lockedWallet['current_balance'] + $amount;
            $newTotalCredited = (float)$lockedWallet['total_credited'] + $amount;

            $pdo->prepare("UPDATE agent_wallets SET current_balance = ?, total_credited = ? WHERE id = ?")
                ->execute([$newBalance, $newTotalCredited, $walletId]);

            $txnId = 'AWTX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            $insert = $pdo->prepare("INSERT INTO agent_wallet_transactions 
                (transaction_id, wallet_id, agent_id, transaction_type, amount, balance_after, currency, original_amount, exchange_rate, description, created_by, payment_method, reference, converted_amount, application_id, payment_id)
                VALUES (?, ?, ?, 'Credit', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert->execute([
                $txnId, $walletId, $agentId, $amount, $newBalance, $currency,
                $originalAmount ?? $amount, $exchangeRate, $description, $createdBy,
                $paymentMethod, $reference, $convertedAmount ?? $amount, $applicationId, $paymentId
            ]);

            $pdo->commit();
            AuditService::log('AGENT_WALLET_CREDIT', 'AgentWallet', $agentId, "Credited {$currency} " . number_format($amount, 2) . " to agent wallet #{$agentId}. Ref: {$txnId}");

            return ['success' => true, 'transaction_id' => $txnId, 'new_balance' => $newBalance];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function debitAgent(
        int $agentId,
        float $amount,
        string $description,
        ?int $createdBy = null,
        string $currency = 'USD',
        ?float $originalAmount = null,
        float $exchangeRate = 1.0,
        ?string $paymentMethod = 'Bank Transfer',
        ?string $reference = null,
        ?float $convertedAmount = null,
        ?int $applicationId = null,
        ?int $paymentId = null
    ): array {
        if ($amount <= 0) {
            throw new Exception("Agent debit amount must be greater than zero.");
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $wallet = self::getOrCreateAgentWallet($agentId, $currency);
            $walletId = (int)$wallet['id'];

            // Row-level lock on agent wallet record
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $lockSql = ($driver === 'mysql')
                ? "SELECT id, current_balance, total_debited FROM agent_wallets WHERE id = ? FOR UPDATE"
                : "SELECT id, current_balance, total_debited FROM agent_wallets WHERE id = ?";
            $lockStmt = $pdo->prepare($lockSql);
            $lockStmt->execute([$walletId]);
            $lockedWallet = $lockStmt->fetch(PDO::FETCH_ASSOC) ?: $wallet;

            $currentBalance = (float)$lockedWallet['current_balance'];
            if ($currentBalance < $amount) {
                throw new Exception("Insufficient agent wallet balance. Available: {$currency} " . number_format($currentBalance, 2) . ", Required: {$currency} " . number_format($amount, 2));
            }

            // Atomic conditional update
            $update = $pdo->prepare("UPDATE agent_wallets SET current_balance = current_balance - ?, total_debited = total_debited + ? WHERE id = ? AND current_balance >= ?");
            $update->execute([$amount, $amount, $walletId, $amount]);

            if ($update->rowCount() === 0) {
                throw new Exception("Agent wallet concurrency conflict: Balance was modified concurrently. Please retry.");
            }

            $newBalance = $currentBalance - $amount;

            $txnId = 'AWTX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            $insert = $pdo->prepare("INSERT INTO agent_wallet_transactions 
                (transaction_id, wallet_id, agent_id, transaction_type, amount, balance_after, currency, original_amount, exchange_rate, description, created_by, payment_method, reference, converted_amount, application_id, payment_id)
                VALUES (?, ?, ?, 'Debit', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert->execute([
                $txnId, $walletId, $agentId, $amount, $newBalance, $currency,
                $originalAmount ?? $amount, $exchangeRate, $description, $createdBy,
                $paymentMethod, $reference, $convertedAmount ?? $amount, $applicationId, $paymentId
            ]);

            $pdo->commit();
            AuditService::log('AGENT_WALLET_DEBIT', 'AgentWallet', $agentId, "Debited {$currency} " . number_format($amount, 2) . " from agent wallet #{$agentId}. Ref: {$txnId}");

            return ['success' => true, 'transaction_id' => $txnId, 'new_balance' => $newBalance];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function getAgentTransactions(int $agentId, int $limit = 50): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT awt.*, u.name as created_by_name
            FROM agent_wallet_transactions awt
            LEFT JOIN users u ON awt.created_by = u.id
            WHERE awt.agent_id = ?
            ORDER BY awt.created_at DESC
            LIMIT ?");
        $stmt->bindValue(1, $agentId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function ensureWalletTransactionColumns(?PDO $pdo = null): void
    {
        if (php_sapi_name() !== 'cli') return;
        static $done = false;
        if ($done) return;
        $done = true;

        $pdo = $pdo ?: Database::getConnection();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $cols = [
            'invoice_id'         => ($driver === 'mysql') ? 'INT NULL' : 'INTEGER NULL',
            'payment_method'     => ($driver === 'mysql') ? "VARCHAR(50) DEFAULT 'Bank Transfer'" : "TEXT DEFAULT 'Bank Transfer'",
            'reference'          => ($driver === 'mysql') ? 'VARCHAR(255) NULL' : 'TEXT NULL',
            'currency'           => ($driver === 'mysql') ? "VARCHAR(10) DEFAULT 'USD'" : "TEXT DEFAULT 'USD'",
            'original_amount'    => ($driver === 'mysql') ? 'DECIMAL(12,2) NULL' : 'REAL NULL',
            'original_currency'  => ($driver === 'mysql') ? 'VARCHAR(10) NULL' : 'TEXT NULL',
            'exchange_rate'      => ($driver === 'mysql') ? 'DECIMAL(12,4) DEFAULT 1.0000' : 'REAL DEFAULT 1.0000',
            'converted_amount'   => ($driver === 'mysql') ? 'DECIMAL(12,2) NULL' : 'REAL NULL',
            'converted_currency' => ($driver === 'mysql') ? 'VARCHAR(10) NULL' : 'TEXT NULL',
            'payment_id'         => ($driver === 'mysql') ? 'INT NULL' : 'INTEGER NULL',
            'application_id'     => ($driver === 'mysql') ? 'INT NULL' : 'INTEGER NULL',
            'created_by'         => ($driver === 'mysql') ? 'INT NULL' : 'INTEGER NULL',
        ];
        foreach ($cols as $col => $def) {
            try { $pdo->exec("ALTER TABLE wallet_transactions ADD COLUMN {$col} {$def}"); } catch (\Throwable $e) {}
        }
    }

    public static function ensureSupplierWalletColumns(?PDO $pdo = null): void
    {
        if (php_sapi_name() !== 'cli') return;
        static $done = false;
        if ($done) return;
        $done = true;

        $pdo = $pdo ?: Database::getConnection();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $cols = [
            'original_amount'    => ($driver === 'mysql') ? 'DECIMAL(12,2) NULL' : 'REAL NULL',
            'original_currency'  => ($driver === 'mysql') ? 'VARCHAR(10) NULL' : 'TEXT NULL',
            'exchange_rate'      => ($driver === 'mysql') ? 'DECIMAL(12,4) DEFAULT 1.0000' : 'REAL DEFAULT 1.0000',
            'converted_amount'   => ($driver === 'mysql') ? 'DECIMAL(12,2) NULL' : 'REAL NULL',
            'converted_currency' => ($driver === 'mysql') ? 'VARCHAR(10) NULL' : 'TEXT NULL',
            'payment_method'     => ($driver === 'mysql') ? "VARCHAR(50) DEFAULT 'Bank Transfer'" : "TEXT DEFAULT 'Bank Transfer'",
            'reference'          => ($driver === 'mysql') ? 'VARCHAR(255) NULL' : 'TEXT NULL',
            'application_id'     => ($driver === 'mysql') ? 'INT NULL' : 'INTEGER NULL',
            'payment_id'         => ($driver === 'mysql') ? 'INT NULL' : 'INTEGER NULL',
            'created_by'         => ($driver === 'mysql') ? 'INT NULL' : 'INTEGER NULL',
        ];
        foreach ($cols as $col => $def) {
            try { $pdo->exec("ALTER TABLE supplier_wallet_transactions ADD COLUMN {$col} {$def}"); } catch (\Throwable $e) {}
        }
    }

    public static function ensureAgentWalletColumns(?PDO $pdo = null): void
    {
        if (php_sapi_name() !== 'cli') return;
        static $done = false;
        if ($done) return;
        $done = true;

        $pdo = $pdo ?: Database::getConnection();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $cols = [
            'original_amount'    => ($driver === 'mysql') ? 'DECIMAL(12,2) NULL' : 'REAL NULL',
            'original_currency'  => ($driver === 'mysql') ? 'VARCHAR(10) NULL' : 'TEXT NULL',
            'exchange_rate'      => ($driver === 'mysql') ? 'DECIMAL(12,4) DEFAULT 1.0000' : 'REAL DEFAULT 1.0000',
            'converted_amount'   => ($driver === 'mysql') ? 'DECIMAL(12,2) NULL' : 'REAL NULL',
            'converted_currency' => ($driver === 'mysql') ? 'VARCHAR(10) NULL' : 'TEXT NULL',
            'payment_method'     => ($driver === 'mysql') ? "VARCHAR(50) DEFAULT 'Bank Transfer'" : "TEXT DEFAULT 'Bank Transfer'",
            'reference'          => ($driver === 'mysql') ? 'VARCHAR(255) NULL' : 'TEXT NULL',
            'application_id'     => ($driver === 'mysql') ? 'INT NULL' : 'INTEGER NULL',
            'payment_id'         => ($driver === 'mysql') ? 'INT NULL' : 'INTEGER NULL',
            'created_by'         => ($driver === 'mysql') ? 'INT NULL' : 'INTEGER NULL',
        ];
        foreach ($cols as $col => $def) {
            try { $pdo->exec("ALTER TABLE agent_wallet_transactions ADD COLUMN {$col} {$def}"); } catch (\Throwable $e) {}
        }
    }
}
