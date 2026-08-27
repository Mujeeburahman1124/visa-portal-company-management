<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use PDO;

class HealthCalculatorService
{
    /**
     * Calculates the operational health percentage (0-100) and diagnostic reasons
     */
    public static function calculate(int $applicationId): array
    {
        $pdo = Database::getConnection();

        // 1. Fetch application details
        $stmt = $pdo->prepare("SELECT a.*, vs.name as service_name FROM applications a JOIN visa_services vs ON a.visa_service_id = vs.id WHERE a.id = ?");
        $stmt->execute([$applicationId]);
        $app = $stmt->fetch();

        if (!$app) {
            return ['score' => 100, 'status' => 'Healthy', 'reasons' => ['Application data not found'], 'summary' => 'Application data not found'];
        }

        // If completed or approved, 100% healthy
        if (in_array($app['status'], ['Approved', 'Completed'], true)) {
            return [
                'score' => 100,
                'status' => 'Healthy',
                'reasons' => ['Application successfully processed and finalized.'],
                'summary' => 'Application successfully processed and finalized.',
            ];
        }

        $score = 100;
        $reasons = [];

        // Check if returned for modification
        if ($app['status'] === 'Action Required' || str_contains(strtolower($app['current_stage']), 'returned')) {
            $score -= 40;
            $reasons[] = 'Application is in Returned / Action Required state awaiting customer modification.';
        }

        // 2. Check Document Requirements
        $stmt = $pdo->prepare("SELECT vr.is_mandatory, dt.name as doc_name, d.status as doc_status, d.rejection_reason 
            FROM visa_requirements vr 
            JOIN document_types dt ON vr.document_type_id = dt.id 
            LEFT JOIN documents d ON d.application_id = ? AND d.document_type_id = dt.id 
            WHERE vr.service_id = ?");
        $stmt->execute([$applicationId, $app['visa_service_id']]);
        $requirements = $stmt->fetchAll();

        $missingMandatory = 0;
        $rejectedDocs = 0;
        $pendingVerification = 0;

        foreach ($requirements as $req) {
            $status = $req['doc_status'] ?? 'Missing';
            if ($status === 'REJECTED') {
                $rejectedDocs++;
                $score -= 20;
                $reasons[] = "Rejected document: {$req['doc_name']} requires re-upload.";
            } elseif ($req['is_mandatory'] && ($status === 'Missing' || $status === 'MISSING' || empty($status))) {
                $missingMandatory++;
                $score -= 15;
                $reasons[] = "Mandatory document missing: {$req['doc_name']}.";
            } elseif ($status === 'UNDER_REVIEW' || $status === 'UPLOADED') {
                $pendingVerification++;
            }
        }

        // 3. Check Overdue Tasks
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE application_id = ? AND status != 'Completed' AND due_date < ?");
        $stmt->execute([$applicationId, $today]);
        $overdueTasks = (int)$stmt->fetchColumn();

        if ($overdueTasks > 0) {
            $penalty = min(25, $overdueTasks * 10);
            $score -= $penalty;
            $reasons[] = "{$overdueTasks} linked operational task(s) are overdue.";
        }

        // 4. Check Expected Completion Deadline
        if (!empty($app['expected_completion_date'])) {
            $now = time();
            $deadline = strtotime($app['expected_completion_date']);
            $diffDays = ($deadline - $now) / 86400;

            if ($diffDays < 0) {
                $score -= 30;
                $reasons[] = 'Expected completion deadline has passed (Overdue).';
            } elseif ($diffDays <= 2 && $app['status'] !== 'Approved') {
                $score -= 10;
                $reasons[] = 'Deadline is approaching within 48 hours.';
            }
        }

        // 5. Check Outstanding Balance for high-progress apps
        if ($app['balance_amount'] > 0 && in_array($app['status'], ['Submitted', 'Processing', 'In Process'])) {
            $score -= 5;
            $reasons[] = 'Outstanding customer balance of ' . format_currency((float)$app['balance_amount']) . ' pending settlement.';
        }

        $score = max(5, min(100, $score));

        $status = 'Healthy';
        if ($score < 50) {
            $status = 'Critical';
        } elseif ($score < 80) {
            $status = 'At Risk';
        }

        if (empty($reasons)) {
            $reasons[] = 'All mandatory documents verified and tasks on schedule.';
        }

        $reasonText = implode(' ', $reasons);

        // Update application health in DB if changed
        $updateStmt = $pdo->prepare("UPDATE applications SET calculated_health = ?, health_reason = ? WHERE id = ?");
        $updateStmt->execute([$score, $reasonText, $applicationId]);

        return [
            'score' => $score,
            'status' => $status,
            'reasons' => $reasons,
            'summary' => $reasonText,
        ];
    }

    public static function updateHealthScore(int $applicationId): array
    {
        return self::calculate($applicationId);
    }

    public static function diagnose(int $applicationId): array
    {
        return self::calculate($applicationId);
    }
}
