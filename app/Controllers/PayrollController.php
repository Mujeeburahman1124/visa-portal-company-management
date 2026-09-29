<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\App;
use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\AuditService;
use PDO;

class PayrollController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'accounts']);
        $pdo = Database::getConnection();

        $selectedMonth = trim($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = date('Y-m');
        }

        $branchId = get_scoped_branch_id((int)($_GET['branch_id'] ?? 0));
        $search = trim($_GET['search'] ?? '');

        // Fetch active staff with baseline salary details
        $sql = "SELECT u.id, u.name, u.email, u.phone, u.designation, u.department, u.basic_salary, u.salary_currency, u.branch_id,
                       r.name as role_name, b.name as branch_name,
                       pr.id as payroll_id, pr.payroll_code, pr.payslip_number, pr.working_days, pr.present_days, pr.absent_days,
                       pr.approved_paid_leave_days, pr.unpaid_leave_days, pr.overtime_hours, pr.overtime_amount, pr.allowances,
                       pr.deductions, pr.unpaid_absence_deductions, pr.advance_salary, pr.net_salary, pr.payment_status, pr.payment_date,
                       pr.payment_method, pr.transaction_reference,
                       (SELECT COUNT(*) FROM staff_attendance sa WHERE sa.user_id = u.id AND strftime('%Y-%m', sa.attendance_date) = ? AND sa.status = 'Present') as recorded_present_days,
                       (SELECT COUNT(*) FROM staff_attendance sa WHERE sa.user_id = u.id AND strftime('%Y-%m', sa.attendance_date) = ? AND sa.status = 'Absent') as recorded_absent_days,
                       (SELECT COALESCE(SUM(sa.overtime_hours), 0) FROM staff_attendance sa WHERE sa.user_id = u.id AND strftime('%Y-%m', sa.attendance_date) = ?) as recorded_ot_hours
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN branches b ON u.branch_id = b.id
                LEFT JOIN payroll_records pr ON pr.user_id = u.id AND pr.payroll_month = ?
                WHERE u.is_active = 1";

        $params = [$selectedMonth, $selectedMonth, $selectedMonth, $selectedMonth];

        if ($branchId > 0) {
            $sql .= " AND u.branch_id = ?";
            $params[] = $branchId;
        }

        if ($search !== '') {
            $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.designation LIKE ?)";
            $term = "%{$search}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " ORDER BY u.name ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $staffPayroll = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Overall Monthly Summary Metrics
        $totalStaff = count($staffPayroll);
        $generatedCount = count(array_filter($staffPayroll, fn($s) => !empty($s['payroll_id'])));
        $totalNetPayroll = array_reduce($staffPayroll, fn($sum, $s) => $sum + (float)($s['net_salary'] ?? 0), 0.0);
        $totalBasicSalaries = array_reduce($staffPayroll, fn($sum, $s) => $sum + (float)($s['basic_salary'] ?? 0), 0.0);
        $totalOvertimeDisbursed = array_reduce($staffPayroll, fn($sum, $s) => $sum + (float)($s['overtime_amount'] ?? 0), 0.0);

        $branches = $pdo->query("SELECT id, name FROM branches ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        require_once dirname(__DIR__) . '/Views/payroll/index.php';
    }

    public function calculate(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $userId = (int)($_GET['user_id'] ?? $_POST['user_id'] ?? 0);
        $month = trim($_GET['month'] ?? $_POST['month'] ?? date('Y-m'));
        $workingDays = (int)($_POST['working_days'] ?? 26);
        if ($workingDays <= 0) $workingDays = 26;

        $userStmt = $pdo->prepare("SELECT u.*, r.name as role_name, b.name as branch_name FROM users u JOIN roles r ON u.role_id = r.id LEFT JOIN branches b ON u.branch_id = b.id WHERE u.id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Staff member not found']);
            exit;
        }

        $basicSalary = (float)($user['basic_salary'] ?: 5000.00);
        $currency = $user['salary_currency'] ?: 'AED';

        // Check if attendance is explicitly posted or pull from database
        if (isset($_POST['present_days'])) {
            $presentDays = (int)$_POST['present_days'];
            $absentDays = (int)($_POST['absent_days'] ?? 0);
            $paidLeaveDays = (int)($_POST['paid_leave_days'] ?? 0);
            $unpaidLeaveDays = (int)($_POST['unpaid_leave_days'] ?? 0);
            $otHours = (float)($_POST['overtime_hours'] ?? 0.0);
            $allowances = (float)($_POST['allowances'] ?? 0.0);
            $deductions = (float)($_POST['deductions'] ?? 0.0);
            $advance = (float)($_POST['advance_salary'] ?? 0.0);
            $customOtRate = isset($_POST['overtime_rate']) && (float)$_POST['overtime_rate'] > 0 ? (float)$_POST['overtime_rate'] : null;
        } else {
            // Auto count from attendance records
            $attStmt = $pdo->prepare("SELECT 
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_cnt,
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_cnt,
                SUM(CASE WHEN status = 'On-Leave' THEN 1 ELSE 0 END) as leave_cnt,
                SUM(CASE WHEN status = 'Half-Day' THEN 0.5 ELSE 0 END) as half_cnt,
                COALESCE(SUM(overtime_hours), 0) as ot_hrs
                FROM staff_attendance 
                WHERE user_id = ? AND strftime('%Y-%m', attendance_date) = ?");
            $attStmt->execute([$userId, $month]);
            $att = $attStmt->fetch(PDO::FETCH_ASSOC);

            $recordedPresent = (float)($att['present_cnt'] ?? 0) + (float)($att['half_cnt'] ?? 0);
            $presentDays = $recordedPresent > 0 ? (int)$recordedPresent : $workingDays;
            $absentDays = (int)($att['absent_cnt'] ?? 0);
            $paidLeaveDays = (int)($att['leave_cnt'] ?? 0);
            $unpaidLeaveDays = max(0, $workingDays - ($presentDays + $paidLeaveDays));
            $otHours = (float)($att['ot_hrs'] ?? 0.0);
            $allowances = 0.00;
            $deductions = 0.00;
            $advance = 0.00;
            $customOtRate = null;
        }

        // Standard Calculations Formula:
        // Daily Rate = Basic Salary / Working Days
        // Hourly Rate = Daily Rate / 8
        // Standard Overtime Rate = Hourly Rate * 1.5
        $dailyRate = round($basicSalary / $workingDays, 4);
        $hourlyRate = round($dailyRate / 8, 4);
        $overtimeRate = $customOtRate !== null ? $customOtRate : round($hourlyRate * 1.5, 2);
        $overtimeAmount = round($otHours * $overtimeRate, 2);

        // Unpaid Absence Deduction = (Basic Salary / Working Days) * (Absent Days + Unpaid Leave Days)
        $unpaidAbsenceDeductions = round($dailyRate * ($absentDays + $unpaidLeaveDays), 2);

        // Net Salary Formula:
        // Net = Basic + Overtime Amount + Allowances - Deductions - Unpaid Absence Deductions - Advance Salary
        $netSalary = max(0.00, round($basicSalary + $overtimeAmount + $allowances - $deductions - $unpaidAbsenceDeductions - $advance, 2));

        $result = [
            'success' => true,
            'user_id' => $userId,
            'name' => $user['name'],
            'designation' => $user['designation'],
            'month' => $month,
            'basic_salary' => $basicSalary,
            'working_days' => $workingDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'paid_leave_days' => $paidLeaveDays,
            'unpaid_leave_days' => $unpaidLeaveDays,
            'daily_rate' => $dailyRate,
            'hourly_rate' => $hourlyRate,
            'overtime_hours' => $otHours,
            'overtime_rate' => $overtimeRate,
            'overtime_amount' => $overtimeAmount,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'unpaid_absence_deductions' => $unpaidAbsenceDeductions,
            'advance_salary' => $advance,
            'net_salary' => $netSalary,
            'currency' => $currency
        ];

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    public function generate(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'accounts']);
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $userId = (int)($_POST['user_id'] ?? 0);
        $month = trim($_POST['month'] ?? date('Y-m'));
        $workingDays = (int)($_POST['working_days'] ?? 26);
        $presentDays = (int)($_POST['present_days'] ?? 26);
        $absentDays = (int)($_POST['absent_days'] ?? 0);
        $paidLeaveDays = (int)($_POST['paid_leave_days'] ?? 0);
        $unpaidLeaveDays = (int)($_POST['unpaid_leave_days'] ?? 0);
        $basicSalary = (float)($_POST['basic_salary'] ?? 0.00);
        $otHours = (float)($_POST['overtime_hours'] ?? 0.0);
        $otRate = (float)($_POST['overtime_rate'] ?? 0.0);
        $allowances = (float)($_POST['allowances'] ?? 0.0);
        $deductions = (float)($_POST['deductions'] ?? 0.0);
        $advance = (float)($_POST['advance_salary'] ?? 0.0);
        $currency = strtoupper(trim($_POST['currency'] ?? 'AED'));
        $paymentStatus = trim($_POST['payment_status'] ?? 'Paid');
        $paymentMethod = trim($_POST['payment_method'] ?? 'Bank Transfer');
        $txnRef = trim($_POST['transaction_reference'] ?? ('TXN-SAL-' . rand(100000, 999999)));
        $notes = trim($_POST['notes'] ?? 'Monthly salary payroll settlement');

        if ($userId <= 0 || $basicSalary <= 0) {
            redirect('/payroll?month=' . urlencode($month), 'Valid staff member and basic salary are required.', 'danger');
        }

        if ($workingDays <= 0) $workingDays = 26;

        // Perform calculation server-side to guarantee integrity
        $dailyRate = round($basicSalary / $workingDays, 4);
        $hourlyRate = round($dailyRate / 8, 4);
        if ($otRate <= 0) {
            $otRate = round($hourlyRate * 1.5, 2);
        }
        $otAmount = round($otHours * $otRate, 2);
        $unpaidAbsenceDeduction = round($dailyRate * ($absentDays + $unpaidLeaveDays), 2);
        $netSalary = max(0.00, round($basicSalary + $otAmount + $allowances - $deductions - $unpaidAbsenceDeduction - $advance, 2));

        $userStmt = $pdo->prepare("SELECT branch_id, name FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $staffMember = $userStmt->fetch(PDO::FETCH_ASSOC);
        $branchId = (int)($staffMember['branch_id'] ?? 1);
        $staffName = $staffMember['name'] ?? 'Staff Officer';

        $payCode = 'PAY-' . str_replace('-', '', $month) . '-' . str_pad((string)$userId, 4, '0', STR_PAD_LEFT);
        $payslipNo = 'SLIP-' . str_replace('-', '', $month) . '-' . str_pad((string)$userId, 4, '0', STR_PAD_LEFT);

        // Check if payroll record already exists for this user and month
        $checkStmt = $pdo->prepare("SELECT id FROM payroll_records WHERE user_id = ? AND payroll_month = ?");
        $checkStmt->execute([$userId, $month]);
        $existingId = $checkStmt->fetchColumn();

        if ($existingId) {
            // Update existing
            $upStmt = $pdo->prepare("UPDATE payroll_records SET 
                basic_salary = ?, working_days = ?, present_days = ?, absent_days = ?, approved_paid_leave_days = ?, unpaid_leave_days = ?,
                overtime_hours = ?, overtime_rate = ?, overtime_amount = ?, allowances = ?, deductions = ?, unpaid_absence_deductions = ?,
                advance_salary = ?, net_salary = ?, currency = ?, payment_status = ?, payment_date = ?, payment_method = ?,
                transaction_reference = ?, notes = ?, generated_by = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?");
            $upStmt->execute([
                $basicSalary, $workingDays, $presentDays, $absentDays, $paidLeaveDays, $unpaidLeaveDays,
                $otHours, $otRate, $otAmount, $allowances, $deductions, $unpaidAbsenceDeduction,
                $advance, $netSalary, $currency, $paymentStatus, ($paymentStatus === 'Paid' ? date('Y-m-d') : null),
                $paymentMethod, $txnRef, $notes, (int)($currentUser['id'] ?? 1), $existingId
            ]);
            $recordId = (int)$existingId;
            $msg = "Payroll record updated for {$staffName} for {$month}. Net Pay: {$currency} " . number_format($netSalary, 2);
        } else {
            // Insert new
            $insStmt = $pdo->prepare("INSERT INTO payroll_records (
                payroll_code, user_id, branch_id, payroll_month, basic_salary, working_days, present_days, absent_days,
                approved_paid_leave_days, unpaid_leave_days, overtime_hours, overtime_rate, overtime_amount, allowances,
                deductions, unpaid_absence_deductions, advance_salary, net_salary, currency, payment_status, payment_date,
                payment_method, transaction_reference, payslip_number, notes, generated_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insStmt->execute([
                $payCode, $userId, $branchId, $month, $basicSalary, $workingDays, $presentDays, $absentDays,
                $paidLeaveDays, $unpaidLeaveDays, $otHours, $otRate, $otAmount, $allowances,
                $deductions, $unpaidAbsenceDeduction, $advance, $netSalary, $currency, $paymentStatus,
                ($paymentStatus === 'Paid' ? date('Y-m-d') : null), $paymentMethod, $txnRef, $payslipNo, $notes, (int)($currentUser['id'] ?? 1)
            ]);
            $recordId = (int)$pdo->lastInsertId();
            $msg = "Official payslip {$payslipNo} generated for {$staffName} for {$month}. Net Pay: {$currency} " . number_format($netSalary, 2);
        }

        AuditService::log('GENERATE_PAYROLL', 'Staff', $userId, "Processed payroll for {$staffName} ({$month}): Net {$currency} " . number_format($netSalary, 2));

        redirect('/payroll?month=' . urlencode($month), $msg, 'success');
    }

    public function payslip(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $id = (int)($_GET['id'] ?? 0);
        $slipNo = trim($_GET['payslip_number'] ?? '');

        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT pr.*, u.name as staff_name, u.email as staff_email, u.phone as staff_phone, u.designation, u.department,
                                          r.name as role_name, b.name as branch_name, b.address as branch_address, b.phone as branch_phone,
                                          gen.name as generated_by_name
                                   FROM payroll_records pr
                                   JOIN users u ON pr.user_id = u.id
                                   JOIN roles r ON u.role_id = r.id
                                   LEFT JOIN branches b ON pr.branch_id = b.id
                                   LEFT JOIN users gen ON pr.generated_by = gen.id
                                   WHERE pr.id = ?");
            $stmt->execute([$id]);
        } else {
            $stmt = $pdo->prepare("SELECT pr.*, u.name as staff_name, u.email as staff_email, u.phone as staff_phone, u.designation, u.department,
                                          r.name as role_name, b.name as branch_name, b.address as branch_address, b.phone as branch_phone,
                                          gen.name as generated_by_name
                                   FROM payroll_records pr
                                   JOIN users u ON pr.user_id = u.id
                                   JOIN roles r ON u.role_id = r.id
                                   LEFT JOIN branches b ON pr.branch_id = b.id
                                   LEFT JOIN users gen ON pr.generated_by = gen.id
                                   WHERE pr.payslip_number = ?");
            $stmt->execute([$slipNo]);
        }

        $payslip = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payslip) {
            redirect('/payroll', 'Payslip record not found.', 'danger');
        }

        require_once dirname(__DIR__) . '/Views/payroll/payslip.php';
    }

    public function history(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'accounts']);
        $pdo = Database::getConnection();

        $search = trim($_GET['search'] ?? '');
        $month = trim($_GET['month'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $branchId = get_scoped_branch_id((int)($_GET['branch_id'] ?? 0));

        $sql = "SELECT pr.*, u.name as staff_name, u.designation, u.department, b.name as branch_name, r.name as role_name
                FROM payroll_records pr
                JOIN users u ON pr.user_id = u.id
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN branches b ON pr.branch_id = b.id
                WHERE 1=1";

        $params = [];
        if ($search !== '') {
            $sql .= " AND (u.name LIKE ? OR pr.payroll_code LIKE ? OR pr.payslip_number LIKE ? OR pr.transaction_reference LIKE ?)";
            $term = "%{$search}%";
            $params = array_fill(0, 4, $term);
        }
        if ($month !== '') {
            $sql .= " AND pr.payroll_month = ?";
            $params[] = $month;
        }
        if ($status !== '') {
            $sql .= " AND pr.payment_status = ?";
            $params[] = $status;
        }
        if ($branchId > 0) {
            $sql .= " AND pr.branch_id = ?";
            $params[] = $branchId;
        }

        $sql .= " ORDER BY pr.payroll_month DESC, pr.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $branches = $pdo->query("SELECT id, name FROM branches ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        require_once dirname(__DIR__) . '/Views/payroll/history.php';
    }

    public function recordAttendance(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $userId = (int)($_POST['user_id'] ?? 0);
        $date = trim($_POST['attendance_date'] ?? date('Y-m-d'));
        $status = trim($_POST['status'] ?? 'Present');
        $checkIn = !empty($_POST['check_in_time']) ? trim($_POST['check_in_time']) : null;
        $checkOut = !empty($_POST['check_out_time']) ? trim($_POST['check_out_time']) : null;
        $otHours = (float)($_POST['overtime_hours'] ?? 0.0);
        $notes = trim($_POST['notes'] ?? '');

        if ($userId <= 0 || empty($date)) {
            redirect('/payroll', 'Staff member and date are required to log attendance.', 'danger');
        }

        $branchId = (int)$pdo->query("SELECT branch_id FROM users WHERE id = {$userId}")->fetchColumn() ?: 1;

        $checkStmt = $pdo->prepare("SELECT id FROM staff_attendance WHERE user_id = ? AND attendance_date = ?");
        $checkStmt->execute([$userId, $date]);
        $existingId = $checkStmt->fetchColumn();

        if ($existingId) {
            $upStmt = $pdo->prepare("UPDATE staff_attendance SET status = ?, check_in_time = ?, check_out_time = ?, overtime_hours = ?, notes = ?, recorded_by = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $upStmt->execute([$status, $checkIn, $checkOut, $otHours, $notes, (int)($currentUser['id'] ?? 1), $existingId]);
        } else {
            $insStmt = $pdo->prepare("INSERT INTO staff_attendance (user_id, branch_id, attendance_date, status, check_in_time, check_out_time, overtime_hours, notes, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insStmt->execute([$userId, $branchId, $date, $status, $checkIn, $checkOut, $otHours, $notes, (int)($currentUser['id'] ?? 1)]);
        }

        AuditService::log('ATTENDANCE_RECORDED', 'Staff', $userId, "Recorded {$status} on {$date} (OT: {$otHours} hrs)");

        redirect($_SERVER['HTTP_REFERER'] ?? '/payroll', "Attendance for {$date} saved successfully.", 'success');
    }
}
