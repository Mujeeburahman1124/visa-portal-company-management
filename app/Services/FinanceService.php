<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use PDO;

class FinanceService
{
    /**
     * Recomputes and updates the financial balances for an application
     */
    public static function recalculateApplication(int $applicationId): array
    {
        $pdo = Database::getConnection();

        // 1. Fetch current application pricing
        $stmt = $pdo->prepare("SELECT selling_price, discount, tax_amount, supplier_cost, other_expenses FROM applications WHERE id = ?");
        $stmt->execute([$applicationId]);
        $app = $stmt->fetch();

        if (!$app) {
            return [];
        }

        $sellingPrice = (float)$app['selling_price'];
        $discount = (float)$app['discount'];
        $tax = (float)$app['tax_amount'];
        $supplierCost = (float)$app['supplier_cost'];
        $otherExpenses = (float)$app['other_expenses'];

        $totalAmount = max(0.00, ($sellingPrice - $discount) + $tax);

        // 2. Sum completed payments
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE application_id = ? AND status = 'Completed'");
        $stmt->execute([$applicationId]);
        $totalPaid = (float)$stmt->fetchColumn();

        // 3. Sum refunds
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM refunds WHERE application_id = ? AND status = 'Processed'");
        $stmt->execute([$applicationId]);
        $totalRefunded = (float)$stmt->fetchColumn();

        $netPaid = max(0.00, $totalPaid - $totalRefunded);
        $balance = max(0.00, $totalAmount - $netPaid);
        $grossProfit = max(0.00, ($totalAmount - $supplierCost - $otherExpenses));

        // 4. Update application table
        $updateStmt = $pdo->prepare("UPDATE applications SET 
            total_amount = ?, paid_amount = ?, balance_amount = ?, gross_profit = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?");
        $updateStmt->execute([$totalAmount, $netPaid, $balance, $grossProfit, $applicationId]);

        return [
            'selling_price' => $sellingPrice,
            'discount' => $discount,
            'tax_amount' => $tax,
            'total_amount' => $totalAmount,
            'paid_amount' => $netPaid,
            'balance_amount' => $balance,
            'supplier_cost' => $supplierCost,
            'other_expenses' => $otherExpenses,
            'gross_profit' => $grossProfit,
        ];
    }

    public static function generateReceiptNumber(): string
    {
        $pdo = Database::getConnection();
        $year = date('Y');
        $stmt = $pdo->query("SELECT COUNT(*) FROM payments");
        $count = ((int)$stmt->fetchColumn()) + 1;
        $receipt = sprintf("RCP-%s-%06d", $year, $count);

        $check = $pdo->prepare("SELECT id FROM payments WHERE payment_number = ?");
        $check->execute([$receipt]);
        while ($check->fetch()) {
            $count++;
            $receipt = sprintf("RCP-%s-%06d", $year, $count);
            $check->execute([$receipt]);
        }

        return $receipt;
    }

    public static function generateInvoiceNumber(int $appId): string
    {
        $year = date('Y');
        return sprintf("INV-%s-%06d", $year, $appId);
    }

    public static function generateRefundNumber(): string
    {
        $pdo = Database::getConnection();
        $year = date('Y');
        $stmt = $pdo->query("SELECT COUNT(*) FROM refunds");
        $count = ((int)$stmt->fetchColumn()) + 1;
        return sprintf("RFD-%s-%06d", $year, $count);
    }
}
