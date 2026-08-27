<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use PDO;

class DocumentExpiryService
{
    /**
     * Determine expiry status and days remaining for a given expiry date
     */
    public static function checkExpiry(?string $expiryDate): array
    {
        if (empty($expiryDate)) {
            return [
                'status' => 'NOT_APPLICABLE',
                'days_remaining' => null,
                'label' => 'No Expiry',
                'badge_class' => 'bg-light text-muted border',
                'urgency' => 'none'
            ];
        }

        $today = strtotime(date('Y-m-d'));
        $target = strtotime($expiryDate);
        $diff = (int)round(($target - $today) / 86400);

        if ($diff < 0) {
            return [
                'status' => 'EXPIRED',
                'days_remaining' => $diff,
                'label' => 'Expired ' . abs($diff) . 'd ago',
                'badge_class' => 'bg-danger text-white fw-bold',
                'urgency' => 'critical'
            ];
        } elseif ($diff === 0) {
            return [
                'status' => 'EXPIRES_TODAY',
                'days_remaining' => 0,
                'label' => 'Expires Today',
                'badge_class' => 'bg-danger text-white fw-bold',
                'urgency' => 'critical'
            ];
        } elseif ($diff <= 7) {
            return [
                'status' => 'CRITICAL_SOON',
                'days_remaining' => $diff,
                'label' => "Expires in {$diff}d",
                'badge_class' => 'bg-danger text-white fw-semibold',
                'urgency' => 'critical'
            ];
        } elseif ($diff <= 30) {
            return [
                'status' => 'EXPIRING_SOON',
                'days_remaining' => $diff,
                'label' => "Expires in {$diff}d",
                'badge_class' => 'bg-warning text-dark fw-semibold',
                'urgency' => 'warning'
            ];
        } elseif ($diff <= 90) {
            return [
                'status' => 'UPCOMING_EXPIRY',
                'days_remaining' => $diff,
                'label' => "Valid ({$diff}d left)",
                'badge_class' => 'bg-info-subtle text-info-emphasis border',
                'urgency' => 'info'
            ];
        }

        return [
            'status' => 'VALID',
            'days_remaining' => $diff,
            'label' => format_date($expiryDate),
            'badge_class' => 'bg-success-subtle text-success border',
            'urgency' => 'normal'
        ];
    }

    /**
     * Get aggregate statistics for document expiry dashboard
     */
    public static function getExpirySummary(): array
    {
        $pdo = Database::getConnection();
        
        $sql = "SELECT d.id, d.expiry_date FROM documents d WHERE d.expiry_date IS NOT NULL";
        $stmt = $pdo->query($sql);
        $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = [
            'expired' => 0,
            'within_7_days' => 0,
            'within_15_days' => 0,
            'within_30_days' => 0,
            'within_60_days' => 0,
            'within_90_days' => 0,
            'valid' => 0
        ];

        $today = strtotime(date('Y-m-d'));

        foreach ($docs as $d) {
            $target = strtotime($d['expiry_date']);
            $diff = (int)round(($target - $today) / 86400);

            if ($diff < 0) {
                $stats['expired']++;
            } elseif ($diff <= 7) {
                $stats['within_7_days']++;
            } elseif ($diff <= 15) {
                $stats['within_15_days']++;
            } elseif ($diff <= 30) {
                $stats['within_30_days']++;
            } elseif ($diff <= 60) {
                $stats['within_60_days']++;
            } elseif ($diff <= 90) {
                $stats['within_90_days']++;
            } else {
                $stats['valid']++;
            }
        }

        return $stats;
    }
}
