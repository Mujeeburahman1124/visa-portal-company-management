<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Config\Database;
use App\Services\HealthCalculatorService;
use PDO;

class TrackingApiController extends ApiController
{
    /**
     * GET /api/applications/{id}/tracking
     */
    public function showTracking(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT a.id, a.application_number, a.current_stage, a.status, a.priority, 
                    a.calculated_health, a.expected_completion_date, a.application_date,
                    c.full_name as customer_name, c.customer_code,
                    vs.name as service_name, co.name as country_name, co.flag_emoji,
                    u.name as staff_name
                FROM applications a
                JOIN customers c ON a.customer_id = c.id
                JOIN visa_services vs ON a.visa_service_id = vs.id
                JOIN countries co ON vs.country_id = co.id
                LEFT JOIN users u ON a.assigned_staff_id = u.id
                WHERE a.id = ?");
        $stmt->execute([$id]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            $this->jsonError('Application not found', [], 404);
        }

        // Fetch chronological timeline
        $stmtHist = $pdo->prepare("SELECT h.*, u.name as changed_by_name 
            FROM application_status_history h 
            LEFT JOIN users u ON h.changed_by = u.id 
            WHERE h.application_id = ? 
            ORDER BY h.created_at ASC");
        $stmtHist->execute([$id]);
        $history = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

        $healthDiag = HealthCalculatorService::diagnose($id);

        $this->jsonSuccess([
            'application' => $app,
            'lifecycle_stages' => [
                'Application Registered',
                'Documents Collected',
                'Documents Verified',
                'Application Submitted',
                'In Process',
                'Medical / Biometrics Processing',
                'Visa Issued & Completed'
            ],
            'current_stage' => $app['current_stage'],
            'history' => $history,
            'health_diagnosis' => $healthDiag
        ], 'Tracking timeline retrieved');
    }
}
