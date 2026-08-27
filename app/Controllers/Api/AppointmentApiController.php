<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Config\Database;
use App\Services\AuditService;
use App\Validators\AppointmentValidator;
use PDO;

class AppointmentApiController extends ApiController
{
    /**
     * GET /api/appointments
     */
    public function index(): void
    {
        $pdo = Database::getConnection();
        $status = $_GET['status'] ?? '';
        $sql = "SELECT apt.*, a.application_number, c.full_name as customer_name, u.name as staff_name
            FROM application_appointments apt
            JOIN applications a ON apt.application_id = a.id
            JOIN customers c ON a.customer_id = c.id
            LEFT JOIN users u ON a.assigned_staff_id = u.id
            WHERE 1=1";

        $params = [];
        if ($status !== '') {
            $sql .= " AND apt.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY apt.appointment_date ASC, apt.appointment_time ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->jsonSuccess($appointments, 'Appointments retrieved');
    }

    /**
     * POST /api/appointments
     */
    public function store(): void
    {
        $input = $this->getJsonInput();
        $user = auth_user();
        $userId = $user ? (int)$user['id'] : null;

        $validator = new AppointmentValidator();
        if (!$validator->validate($input)) {
            $this->jsonError($validator->getFirstError() ?? 'Validation failed', $validator->getErrors(), 422);
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO application_appointments 
            (application_id, appointment_type, center_name, location_address, appointment_date, appointment_time, reference_number, status, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            (int)$input['application_id'],
            $input['appointment_type'],
            trim($input['center_name']),
            trim($input['location_address'] ?? ''),
            $input['appointment_date'],
            $input['appointment_time'],
            trim($input['reference_number'] ?? ''),
            $input['status'] ?? 'Scheduled',
            $userId
        ]);

        $aptId = (int)$pdo->lastInsertId();
        AuditService::log('SCHEDULE_APPOINTMENT', 'Appointments', $aptId, "Scheduled {$input['appointment_type']} at {$input['center_name']}", $input, $userId);

        $this->jsonSuccess(['id' => $aptId], 'Appointment scheduled successfully', 201);
    }
}
