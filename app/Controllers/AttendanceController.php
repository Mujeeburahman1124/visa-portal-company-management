<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\AuditService;
use PDO;

class AttendanceController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $selectedDate = trim($_GET['date'] ?? date('Y-m-d'));
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');
        $staffId = (int)($_GET['staff_id'] ?? 0);
        $department = trim($_GET['department'] ?? '');
        $branchId = (int)($_GET['branch_id'] ?? 0);
        $status = trim($_GET['status'] ?? '');
        $viewMode = trim($_GET['view'] ?? 'daily'); // 'daily', 'list', 'monthly'

        // Date Presets resolution
        $preset = trim($_GET['preset'] ?? '');
        if ($preset === 'today') {
            $selectedDate = date('Y-m-d');
            $dateFrom = '';
            $dateTo = '';
        } elseif ($preset === 'yesterday') {
            $selectedDate = date('Y-m-d', strtotime('-1 day'));
            $dateFrom = '';
            $dateTo = '';
        } elseif ($preset === 'this_week') {
            $dateFrom = date('Y-m-d', strtotime('monday this week'));
            $dateTo = date('Y-m-d', strtotime('sunday this week'));
        } elseif ($preset === 'this_month') {
            $dateFrom = date('Y-m-01');
            $dateTo = date('Y-m-t');
        }

        // 1. Core Summary Metrics
        $todayStr = date('Y-m-d');
        $currentMonthStr = date('Y-m');

        $totalStaff = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
        
        $presentToday = (int)$pdo->query("SELECT COUNT(*) FROM staff_attendance sa 
            JOIN users u ON sa.user_id = u.id 
            WHERE sa.attendance_date = '{$todayStr}' AND sa.status = 'Present' AND u.is_active = 1")->fetchColumn();
            
        $absentToday = (int)$pdo->query("SELECT COUNT(*) FROM staff_attendance sa 
            JOIN users u ON sa.user_id = u.id 
            WHERE sa.attendance_date = '{$todayStr}' AND sa.status = 'Absent' AND u.is_active = 1")->fetchColumn();
            
        $lateHalfDayToday = (int)$pdo->query("SELECT COUNT(*) FROM staff_attendance sa 
            JOIN users u ON sa.user_id = u.id 
            WHERE sa.attendance_date = '{$todayStr}' AND sa.status IN ('Late', 'Half Day') AND u.is_active = 1")->fetchColumn();

        $onLeaveToday = (int)$pdo->query("SELECT COUNT(*) FROM staff_attendance sa 
            JOIN users u ON sa.user_id = u.id 
            WHERE sa.attendance_date = '{$todayStr}' AND sa.status = 'Leave' AND u.is_active = 1")->fetchColumn();

        $monthOtHours = (float)$pdo->query("SELECT COALESCE(SUM(overtime_hours), 0) FROM staff_attendance 
            WHERE strftime('%Y-%m', attendance_date) = '{$currentMonthStr}'")->fetchColumn();

        // 2. Query Attendance Records
        $sql = "SELECT sa.*, u.name as staff_name, u.email as staff_email, u.phone as staff_phone,
                       u.designation, u.department, u.avatar,
                       b.name as branch_name,
                       rec.name as recorded_by_name,
                       upd.name as updated_by_name
                FROM staff_attendance sa
                JOIN users u ON sa.user_id = u.id
                LEFT JOIN branches b ON sa.branch_id = b.id OR u.branch_id = b.id
                LEFT JOIN users rec ON sa.recorded_by = rec.id
                LEFT JOIN users upd ON sa.updated_by = upd.id
                WHERE 1=1";
        $params = [];

        if (!empty($dateFrom) && !empty($dateTo)) {
            $sql .= " AND sa.attendance_date BETWEEN ? AND ?";
            $params[] = $dateFrom;
            $params[] = $dateTo;
        } elseif (!empty($dateFrom)) {
            $sql .= " AND sa.attendance_date >= ?";
            $params[] = $dateFrom;
        } elseif (!empty($dateTo)) {
            $sql .= " AND sa.attendance_date <= ?";
            $params[] = $dateTo;
        } elseif (!empty($selectedDate) && $viewMode === 'daily') {
            $sql .= " AND sa.attendance_date = ?";
            $params[] = $selectedDate;
        }

        if ($staffId > 0) {
            $sql .= " AND sa.user_id = ?";
            $params[] = $staffId;
        }

        if (!empty($department)) {
            $sql .= " AND u.department = ?";
            $params[] = $department;
        }

        if ($branchId > 0) {
            $sql .= " AND (sa.branch_id = ? OR u.branch_id = ?)";
            $params[] = $branchId;
            $params[] = $branchId;
        }

        if (!empty($status)) {
            $sql .= " AND sa.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY sa.attendance_date DESC, u.name ASC LIMIT 500";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Dropdown Metadata
        $staffList = $pdo->query("SELECT id, name, email, department, designation, branch_id FROM users WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $branches = $pdo->query("SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $departments = $pdo->query("SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department ASC")->fetchAll(PDO::FETCH_COLUMN);

        $pageTitle = 'Staff Attendance & Working Hours — MS TRAVEL HUB';
        $flash = get_flash();
        require_once dirname(__DIR__) . '/Views/attendance/index.php';
    }

    public function record(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'hr']);

        $pdo = Database::getConnection();
        $user = auth_user();

        $userId = (int)($_POST['user_id'] ?? 0);
        $date = trim($_POST['attendance_date'] ?? date('Y-m-d'));
        $status = trim($_POST['status'] ?? 'Present');
        $checkIn = trim($_POST['check_in_time'] ?? '');
        $checkOut = trim($_POST['check_out_time'] ?? '');
        $otHours = (float)($_POST['overtime_hours'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if ($userId <= 0) {
            redirect('/attendance', 'Please select a valid staff member.', 'danger');
        }

        // Get staff branch
        $branchStmt = $pdo->prepare("SELECT branch_id FROM users WHERE id = ?");
        $branchStmt->execute([$userId]);
        $branchId = (int)$branchStmt->fetchColumn() ?: null;

        // Calculate working hours automatically if check-in and check-out provided
        $workingHours = 0.0;
        if (!empty($checkIn) && !empty($checkOut)) {
            $inTs = strtotime("{$date} {$checkIn}");
            $outTs = strtotime("{$date} {$checkOut}");
            if ($outTs > $inTs) {
                $diffHours = ($outTs - $inTs) / 3600.0;
                $workingHours = round($diffHours, 2);
            }
        } elseif ($status === 'Present') {
            $workingHours = 8.0; // standard workday default
        } elseif ($status === 'Half Day') {
            $workingHours = 4.0;
        }

        // Check if attendance already logged for this staff member on this date
        $checkStmt = $pdo->prepare("SELECT id FROM staff_attendance WHERE user_id = ? AND attendance_date = ?");
        $checkStmt->execute([$userId, $date]);
        $existingId = $checkStmt->fetchColumn();

        if ($existingId) {
            $upStmt = $pdo->prepare("UPDATE staff_attendance 
                SET status = ?, check_in_time = ?, check_out_time = ?, working_hours = ?, overtime_hours = ?, notes = ?, recorded_by = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?");
            $upStmt->execute([$status, $checkIn, $checkOut, $workingHours, $otHours, $notes, $user['id'] ?? null, $existingId]);
            $recordId = (int)$existingId;
        } else {
            $insStmt = $pdo->prepare("INSERT INTO staff_attendance 
                (user_id, branch_id, attendance_date, status, check_in_time, check_out_time, working_hours, overtime_hours, notes, recorded_by, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            $insStmt->execute([$userId, $branchId, $date, $status, $checkIn, $checkOut, $workingHours, $otHours, $notes, $user['id'] ?? null]);
            $recordId = (int)$pdo->lastInsertId();
        }

        AuditService::log('ATTENDANCE_LOGGED', 'Staff', $userId, "Recorded {$status} on {$date} (Hrs: {$workingHours}, OT: {$otHours}h)", null, $user['id'] ?? null);

        redirect($_SERVER['HTTP_REFERER'] ?? '/attendance', "Attendance for {$date} saved successfully.", 'success');
    }

    public function update(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'hr']);

        $pdo = Database::getConnection();
        $user = auth_user();

        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? 'Present');
        $checkIn = trim($_POST['check_in_time'] ?? '');
        $checkOut = trim($_POST['check_out_time'] ?? '');
        $otHours = (float)($_POST['overtime_hours'] ?? 0);
        $workingHours = !empty($_POST['working_hours']) ? (float)$_POST['working_hours'] : null;
        $notes = trim($_POST['notes'] ?? '');
        $reason = trim($_POST['correction_reason'] ?? 'Administrative correction');

        if ($id <= 0) {
            redirect('/attendance', 'Invalid attendance record identifier.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT sa.*, u.name as staff_name FROM staff_attendance sa JOIN users u ON sa.user_id = u.id WHERE sa.id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            redirect('/attendance', 'Attendance record not found.', 'danger');
        }

        // Auto-calculate working hours if not manually overridden
        if ($workingHours === null) {
            if (!empty($checkIn) && !empty($checkOut)) {
                $inTs = strtotime("{$current['attendance_date']} {$checkIn}");
                $outTs = strtotime("{$current['attendance_date']} {$checkOut}");
                if ($outTs > $inTs) {
                    $workingHours = round(($outTs - $inTs) / 3600.0, 2);
                }
            } else {
                $workingHours = ($status === 'Present') ? 8.0 : (($status === 'Half Day') ? 4.0 : 0.0);
            }
        }

        $upStmt = $pdo->prepare("UPDATE staff_attendance 
            SET status = ?, check_in_time = ?, check_out_time = ?, working_hours = ?, overtime_hours = ?, notes = ?, correction_notes = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?");
        $upStmt->execute([$status, $checkIn, $checkOut, $workingHours, $otHours, $notes, $reason, $user['id'] ?? null, $id]);

        AuditService::log('ATTENDANCE_CORRECTED', 'Staff', (int)$current['user_id'], 
            "Corrected attendance #{$id} for {$current['staff_name']} on {$current['attendance_date']}: Status changed from '{$current['status']}' to '{$status}'. Reason: {$reason}",
            null, $user['id'] ?? null);

        redirect($_SERVER['HTTP_REFERER'] ?? '/attendance', "Attendance record #{$id} updated successfully.", 'success');
    }

    public function export(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'hr', 'accounts']);

        $pdo = Database::getConnection();

        $selectedDate = trim($_GET['date'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');
        $staffId = (int)($_GET['staff_id'] ?? 0);
        $department = trim($_GET['department'] ?? '');
        $branchId = (int)($_GET['branch_id'] ?? 0);
        $status = trim($_GET['status'] ?? '');

        $sql = "SELECT sa.*, u.name as staff_name, u.email as staff_email, u.department, u.designation,
                       b.name as branch_name, rec.name as recorded_by_name
                FROM staff_attendance sa
                JOIN users u ON sa.user_id = u.id
                LEFT JOIN branches b ON sa.branch_id = b.id OR u.branch_id = b.id
                LEFT JOIN users rec ON sa.recorded_by = rec.id
                WHERE 1=1";
        $params = [];

        if (!empty($dateFrom) && !empty($dateTo)) {
            $sql .= " AND sa.attendance_date BETWEEN ? AND ?";
            $params[] = $dateFrom;
            $params[] = $dateTo;
        } elseif (!empty($dateFrom)) {
            $sql .= " AND sa.attendance_date >= ?";
            $params[] = $dateFrom;
        } elseif (!empty($selectedDate)) {
            $sql .= " AND sa.attendance_date = ?";
            $params[] = $selectedDate;
        }

        if ($staffId > 0) {
            $sql .= " AND sa.user_id = ?";
            $params[] = $staffId;
        }
        if (!empty($department)) {
            $sql .= " AND u.department = ?";
            $params[] = $department;
        }
        if ($branchId > 0) {
            $sql .= " AND (sa.branch_id = ? OR u.branch_id = ?)";
            $params[] = $branchId;
            $params[] = $branchId;
        }
        if (!empty($status)) {
            $sql .= " AND sa.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY sa.attendance_date DESC, u.name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $filename = "staff_attendance_export_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Staff Name', 'Email', 'Department', 'Designation', 'Branch', 'Date', 'Status', 'Check-In', 'Check-Out', 'Working Hours', 'Overtime Hours', 'Notes', 'Correction Notes', 'Recorded By', 'Logged At']);

        foreach ($rows as $r) {
            fputcsv($output, [
                $r['id'],
                $r['staff_name'],
                $r['staff_email'],
                $r['department'] ?? '—',
                $r['designation'] ?? '—',
                $r['branch_name'] ?? 'Main Office',
                $r['attendance_date'],
                $r['status'],
                $r['check_in_time'] ?: '—',
                $r['check_out_time'] ?: '—',
                number_format((float)($r['working_hours'] ?? 0), 2),
                number_format((float)($r['overtime_hours'] ?? 0), 2),
                $r['notes'] ?? '',
                $r['correction_notes'] ?? '',
                $r['recorded_by_name'] ?? 'System',
                $r['created_at']
            ]);
        }
        fclose($output);
        exit;
    }
}
