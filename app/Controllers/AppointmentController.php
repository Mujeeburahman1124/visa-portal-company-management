<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\App;
use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Services\AuditService;
use PDO;

class AppointmentController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $status = trim($_GET['status'] ?? '');
        $type = trim($_GET['type'] ?? '');
        $search = trim($_GET['search'] ?? '');

        $sql = "SELECT ap.*, 
                    a.application_number, a.id as app_id,
                    c.full_name as customer_name, c.mobile as customer_mobile,
                    u.name as staff_name
                FROM appointments ap
                JOIN applications a ON ap.application_id = a.id
                JOIN customers c ON a.customer_id = c.id
                LEFT JOIN users u ON a.assigned_staff_id = u.id
                WHERE 1=1";

        $params = [];
        if ($status !== '') {
            $sql .= " AND ap.status = ?";
            $params[] = $status;
        }
        if ($type !== '') {
            $sql .= " AND ap.appointment_type = ?";
            $params[] = $type;
        }
        if ($search !== '') {
            $sql .= " AND (c.full_name LIKE ? OR a.application_number LIKE ? OR ap.center_name LIKE ? OR ap.reference_number LIKE ?)";
            $term = "%{$search}%";
            $params = array_fill(0, 4, $term);
        }

        $sql .= " ORDER BY ap.appointment_date ASC, ap.appointment_time ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $appointments = $stmt->fetchAll();

        $staffMembers = $pdo->query("SELECT id, name FROM users WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
        $activeApplications = $pdo->query("SELECT a.id, a.application_number, c.full_name as customer_name, a.customer_id FROM applications a JOIN customers c ON a.customer_id = c.id WHERE a.is_archived = 0 AND a.status NOT IN ('Approved', 'Completed') ORDER BY a.application_number ASC")->fetchAll();

        require_once dirname(__DIR__) . '/Views/appointments/index.php';
    }

    public function store(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $appId = (int)($_POST['application_id'] ?? 0);
        $type = trim($_POST['appointment_type'] ?? 'Biometrics');
        $centerName = trim($_POST['center_name'] ?? '');
        $location = trim($_POST['location_address'] ?? '');
        $date = !empty($_POST['appointment_date']) ? $_POST['appointment_date'] : date('Y-m-d');
        $time = !empty($_POST['appointment_time']) ? $_POST['appointment_time'] : '09:00';
        $refNumber = trim($_POST['reference_number'] ?? '');
        $staffId = !empty($_POST['assigned_staff_id']) ? (int)$_POST['assigned_staff_id'] : (int)$currentUser['id'];
        $notes = trim($_POST['notes'] ?? '');

        if ($appId <= 0 || empty($centerName)) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/appointments', 'Please specify a valid application and center location.', 'danger');
        }

        $app = $pdo->query("SELECT customer_id, application_number FROM applications WHERE id = {$appId}")->fetch();
        $customerId = (int)$app['customer_id'];

        // Handle appointment document/letter upload if attached
        $docFileName = null;
        if (!empty($_FILES['document_file']['name'])) {
            $uploadDir = App::uploadPath();
            $ext = pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION);
            $docFileName = 'appointment_' . $appId . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['document_file']['tmp_name'], $uploadDir . DIRECTORY_SEPARATOR . $docFileName);
        }

        $stmt = $pdo->prepare("INSERT INTO appointments (
            application_id, customer_id, appointment_type, center_name, location_address,
            appointment_date, appointment_time, reference_number, assigned_staff_id, status, document_file, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Scheduled', ?, ?)");

        $stmt->execute([
            $appId, $customerId, $type, $centerName, $location,
            $date, $time, $refNumber, $staffId, $docFileName, $notes
        ]);
        $aptId = (int)$pdo->lastInsertId();

        // Dispatch Central Real-Time Notification (Email + WhatsApp + In-App)
        try {
            \App\Services\NotificationService::trigger('interview.scheduled', [
                'application_id' => $appId,
                'customer_id' => $customerId,
                'assigned_staff_id' => $staffId,
                'application_number' => $app['application_number'] ?? '',
                'appointmentType' => $type,
                'interviewDate' => $date,
                'interviewTime' => $time,
                'centerName' => $centerName,
                'locationAddress' => $location ?: 'Consular Visa Application Center',
                'referenceNumber' => $refNumber,
                'actionUrl' => (string)\App\Config\Env::get('APP_URL', 'http://localhost:8000') . "/portal/appointments",
                'portal_link' => "/portal/appointments",
                'link' => "/applications/show?id={$appId}",
            ]);
        } catch (\Throwable $e) {}

        AuditService::log('SCHEDULE_APPOINTMENT', 'Appointments', $aptId, "Scheduled {$type} for {$app['application_number']} on {$date} at {$centerName}");

        redirect($_SERVER['HTTP_REFERER'] ?? '/appointments', "Appointment scheduled successfully for {$date}.", 'success');
    }

    public function updateStatus(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $aptId = (int)($_POST['appointment_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'Completed');

        if ($aptId > 0 && in_array($status, ['Scheduled', 'Confirmed', 'Completed', 'Cancelled', 'Missed', 'Rescheduled'], true)) {
            $stmtApt = $pdo->prepare("SELECT a.*, ap.application_number FROM appointments a JOIN applications ap ON a.application_id = ap.id WHERE a.id = ?");
            $stmtApt->execute([$aptId]);
            $apt = $stmtApt->fetch(PDO::FETCH_ASSOC);

            $pdo->prepare("UPDATE appointments SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$status, $aptId]);
            
            if ($apt) {
                try {
                    $evt = match ($status) {
                        'Cancelled' => 'interview.cancelled',
                        'Rescheduled' => 'interview.rescheduled',
                        default => 'interview.result_updated',
                    };
                    \App\Services\NotificationService::trigger($evt, [
                        'application_id' => $apt['application_id'],
                        'customer_id' => $apt['customer_id'],
                        'assigned_staff_id' => $apt['assigned_staff_id'],
                        'application_number' => $apt['application_number'],
                        'appointmentType' => $apt['appointment_type'],
                        'interviewDate' => $apt['appointment_date'],
                        'interviewTime' => $apt['appointment_time'],
                        'centerName' => $apt['center_name'],
                        'status' => $status,
                        'portal_link' => "/portal/appointments",
                    ]);
                } catch (\Throwable $e) {}
            }

            AuditService::log('UPDATE_APPOINTMENT', 'Appointments', $aptId, "Updated appointment status to {$status}");
            redirect($_SERVER['HTTP_REFERER'] ?? '/appointments', "Appointment marked as {$status}.", 'success');
        }

        redirect($_SERVER['HTTP_REFERER'] ?? '/appointments', 'Invalid appointment status.', 'danger');
    }
}
