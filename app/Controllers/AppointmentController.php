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
        $scopedBranchId = get_scoped_branch_id((int)($_GET['branch_id'] ?? 0));
        if ($scopedBranchId > 0) {
            $sql .= " AND a.branch_id = ?";
            $params[] = $scopedBranchId;
        }

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

        // Ensure appointment_types table exists
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS appointment_types (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                code TEXT,
                description TEXT,
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            $count = (int)$pdo->query("SELECT COUNT(*) FROM appointment_types")->fetchColumn();
            if ($count === 0) {
                $defaults = [
                    'VFS / TLS Biometrics & Interview',
                    'US Consular Visa Interview',
                    'Medical Fitness Test (DHA / MOHAP / Diagnostic)',
                    'Biometrics Capture (ICP / EIDA)',
                    'Embassy Consular Submission',
                    'Document Verification & Attestation',
                    'Passport Stamping & Collection'
                ];
                $ins = $pdo->prepare("INSERT INTO appointment_types (name, code, is_active) VALUES (?, ?, 1)");
                foreach ($defaults as $d) {
                    $code = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', substr($d, 0, 20)));
                    $ins->execute([$d, $code]);
                }
            }
        } catch (\Throwable $e) {}

        try {
            $appointmentTypes = $pdo->query("SELECT * FROM appointment_types WHERE is_active = 1 ORDER BY name ASC")->fetchAll() ?: [];
        } catch (\Throwable $e) {
            $appointmentTypes = [];
        }

        if ($scopedBranchId > 0) {
            $staffStmt = $pdo->prepare("SELECT id, name FROM users WHERE is_active = 1 AND branch_id = ? ORDER BY name ASC");
            $staffStmt->execute([$scopedBranchId]);
            $staffMembers = $staffStmt->fetchAll();

            $appStmt = $pdo->prepare("SELECT a.id, a.application_number, c.full_name as customer_name, a.customer_id FROM applications a JOIN customers c ON a.customer_id = c.id WHERE a.is_archived = 0 AND a.branch_id = ? AND a.status NOT IN ('Approved', 'Completed') ORDER BY a.application_number ASC");
            $appStmt->execute([$scopedBranchId]);
            $activeApplications = $appStmt->fetchAll();
        } else {
            $staffMembers = $pdo->query("SELECT id, name FROM users WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
            $activeApplications = $pdo->query("SELECT a.id, a.application_number, c.full_name as customer_name, a.customer_id FROM applications a JOIN customers c ON a.customer_id = c.id WHERE a.is_archived = 0 AND a.status NOT IN ('Approved', 'Completed') ORDER BY a.application_number ASC")->fetchAll();
        }

        require_once dirname(__DIR__) . '/Views/appointments/index.php';
    }

    public function store(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $appId = (int)($_POST['application_id'] ?? 0);
        $type = trim($_POST['appointment_type'] ?? 'Biometrics Capture');
        $customType = trim($_POST['custom_appointment_type'] ?? '');
        if ($type === 'custom' || (!empty($customType) && $type === '')) {
            $type = $customType;
        }

        // If custom type is new, auto-persist to appointment_types
        if (!empty($type)) {
            try {
                $chk = $pdo->prepare("SELECT id FROM appointment_types WHERE name = ? LIMIT 1");
                $chk->execute([$type]);
                if (!$chk->fetch()) {
                    $code = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', substr($type, 0, 20)));
                    $pdo->prepare("INSERT INTO appointment_types (name, code, is_active) VALUES (?, ?, 1)")->execute([$type, $code]);
                }
            } catch (\Throwable $e) {}
        }
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

        // Self-healing schema repair: guarantee optional columns exist in appointments table
        try { $pdo->exec("ALTER TABLE appointments ADD COLUMN customer_id INT NULL"); } catch (\Throwable $e) {}
        try { $pdo->exec("ALTER TABLE appointments ADD COLUMN assigned_staff_id INT NULL"); } catch (\Throwable $e) {}
        try { $pdo->exec("ALTER TABLE appointments ADD COLUMN document_file VARCHAR(255) NULL"); } catch (\Throwable $e) {}
        try { $pdo->exec("ALTER TABLE appointments ADD COLUMN created_by INT NULL"); } catch (\Throwable $e) {}

        // Dynamic column inspection to prevent SQL 1054 unknown column errors on older tables
        $existingCols = [];
        try {
            $colStmt = $pdo->query("SHOW COLUMNS FROM appointments");
            if ($colStmt) {
                $existingCols = $colStmt->fetchAll(\PDO::FETCH_COLUMN);
            }
        } catch (\Throwable $e) {
            try {
                $colStmt = $pdo->query("PRAGMA table_info(appointments)");
                if ($colStmt) {
                    $existingCols = array_column($colStmt->fetchAll(\PDO::FETCH_ASSOC), 'name');
                }
            } catch (\Throwable $e2) {}
        }

        $fields = [
            'application_id' => $appId,
            'appointment_type' => $type,
            'center_name' => $centerName,
            'location_address' => $location,
            'appointment_date' => $date,
            'appointment_time' => $time,
            'reference_number' => $refNumber,
            'status' => 'Scheduled',
            'notes' => $notes,
        ];

        if (empty($existingCols) || in_array('customer_id', $existingCols, true)) {
            $fields['customer_id'] = $customerId;
        }
        if (empty($existingCols) || in_array('assigned_staff_id', $existingCols, true)) {
            $fields['assigned_staff_id'] = $staffId;
        }
        if (empty($existingCols) || in_array('document_file', $existingCols, true)) {
            $fields['document_file'] = $docFileName;
        }
        if (empty($existingCols) || in_array('created_by', $existingCols, true)) {
            $fields['created_by'] = (int)($currentUser['id'] ?? 1);
        }

        $colsList = implode(', ', array_keys($fields));
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));

        try {
            $stmt = $pdo->prepare("INSERT INTO appointments ({$colsList}) VALUES ({$placeholders})");
            $stmt->execute(array_values($fields));
        } catch (\PDOException $exApt) {
            if (str_contains($exApt->getMessage(), 'Unknown column') || str_contains($exApt->getMessage(), 'no such column')) {
                try { $pdo->exec("ALTER TABLE appointments ADD COLUMN customer_id INT NULL"); } catch (\Throwable $e) {}
                try { $pdo->exec("ALTER TABLE appointments ADD COLUMN assigned_staff_id INT NULL"); } catch (\Throwable $e) {}
                try { $pdo->exec("ALTER TABLE appointments ADD COLUMN document_file VARCHAR(255) NULL"); } catch (\Throwable $e) {}
                try { $pdo->exec("ALTER TABLE appointments ADD COLUMN created_by INT NULL"); } catch (\Throwable $e) {}

                $fallbackStmt = $pdo->prepare("INSERT INTO appointments (
                    application_id, appointment_type, center_name, location_address, appointment_date, appointment_time, reference_number, status, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Scheduled', ?)");
                $fallbackStmt->execute([$appId, $type, $centerName, $location, $date, $time, $refNumber, $notes]);
            } else {
                throw $exApt;
            }
        }
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
                'actionUrl' => App::url('portal/appointments'),
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

    /**
     * Store a new custom appointment type.
     */
    public function storeType(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/appointments', 'Appointment type name is required.', 'danger');
        }

        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS appointment_types (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                code TEXT,
                description TEXT,
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            $code = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', substr($name, 0, 20)));
            $stmt = $pdo->prepare("INSERT INTO appointment_types (name, code, description, is_active) VALUES (?, ?, ?, 1)");
            $stmt->execute([$name, $code, $description]);
            AuditService::log('ADD_APPOINTMENT_TYPE', 'Appointments', (int)$pdo->lastInsertId(), "Created appointment type '{$name}'");
            redirect($_SERVER['HTTP_REFERER'] ?? '/appointments', "Appointment type '{$name}' added successfully.", 'success');
        } catch (\Throwable $e) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/appointments', "Failed to add appointment type: " . $e->getMessage(), 'danger');
        }
    }

    /**
     * Delete an appointment type.
     */
    public function deleteType(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM appointment_types WHERE id = ?")->execute([$id]);
            redirect($_SERVER['HTTP_REFERER'] ?? '/appointments', "Appointment type deleted.", 'success');
        }
        redirect($_SERVER['HTTP_REFERER'] ?? '/appointments', "Invalid appointment type ID.", 'danger');
    }
}
