<?php
$pageTitle = 'Staff Attendance & Payroll Management — MS TRAVEL HUB';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
?>

<div class="content-body">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Header & Action Bar -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <h3 class="fw-bold brand-font text-dark mb-1">Staff Attendance &amp; Payroll Management</h3>
      <p class="text-muted small mb-0">Automated attendance-based salary calculations, overtime multipliers, unpaid leave deductions &amp; payslips.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="/payroll/history" class="btn btn-outline-primary btn-sm px-3 shadow-sm">
        <i class="fa-solid fa-clock-rotate-left me-1"></i> Payroll History
      </a>
      <button class="btn btn-success btn-sm px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#recordAttendanceModal">
        <i class="fa-solid fa-calendar-check me-1"></i> Log Attendance
      </button>
    </div>
  </div>

  <!-- KPI Metrics Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card card-enterprise h-100 shadow-sm border-0 border-start border-4 border-primary">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small text-uppercase fw-semibold">Active Staff</span>
            <div class="rounded-circle bg-primary-subtle p-2 text-primary"><i class="fa-solid fa-users"></i></div>
          </div>
          <h3 class="fw-bold text-dark mb-0"><?= $totalStaff ?></h3>
          <div class="text-muted small mt-1">Processed: <strong class="text-success"><?= $generatedCount ?> / <?= $totalStaff ?></strong></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card card-enterprise h-100 shadow-sm border-0 border-start border-4 border-success">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small text-uppercase fw-semibold">Total Net Payroll</span>
            <div class="rounded-circle bg-success-subtle p-2 text-success"><i class="fa-solid fa-money-check-dollar"></i></div>
          </div>
          <h3 class="fw-bold text-success mb-0">AED <?= number_format($totalNetPayroll, 2) ?></h3>
          <div class="text-muted small mt-1">Month: <strong><?= e($selectedMonth) ?></strong></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card card-enterprise h-100 shadow-sm border-0 border-start border-4 border-info">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small text-uppercase fw-semibold">Base Salaries</span>
            <div class="rounded-circle bg-info-subtle p-2 text-info"><i class="fa-solid fa-coins"></i></div>
          </div>
          <h3 class="fw-bold text-dark mb-0">AED <?= number_format($totalBasicSalaries, 2) ?></h3>
          <div class="text-muted small mt-1">Fixed Contract Base</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card card-enterprise h-100 shadow-sm border-0 border-start border-4 border-warning">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small text-uppercase fw-semibold">Overtime Disbursed</span>
            <div class="rounded-circle bg-warning-subtle p-2 text-warning"><i class="fa-solid fa-business-time"></i></div>
          </div>
          <h3 class="fw-bold text-dark mb-0">AED <?= number_format($totalOvertimeDisbursed, 2) ?></h3>
          <div class="text-muted small mt-1">Multiplier 1.5x Applied</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Month & Filter Selector Bar -->
  <div class="card card-enterprise shadow-sm mb-4">
    <div class="card-body p-3">
      <form method="GET" action="/payroll" class="row g-2 align-items-center">
        <div class="col-12 col-md-3">
          <label class="form-label small fw-bold text-muted mb-1">Payroll Month</label>
          <input type="month" name="month" class="form-control form-control-sm font-monospace" value="<?= e($selectedMonth) ?>" onchange="this.form.submit()">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label small fw-bold text-muted mb-1">Branch</label>
          <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Branches</option>
            <?php foreach ($branches as $b): ?>
              <option value="<?= $b['id'] ?>" <?= $branchId == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label small fw-bold text-muted mb-1">Search Staff</label>
          <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, email, designation..." value="<?= e($search) ?>">
        </div>
        <div class="col-12 col-md-2 d-flex align-items-end gap-1 mt-auto">
          <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="fa-solid fa-magnifying-glass me-1"></i> Filter</button>
          <a href="/payroll" class="btn btn-light border btn-sm" title="Reset Filters"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
      </form>
    </div>
  </div>

  <!-- Payroll Data Table -->
  <div class="card card-enterprise shadow-sm">
    <div class="table-responsive">
      <table class="table-modern mb-0">
        <thead>
          <tr>
            <th style="min-width: 180px;">Staff Member</th>
            <th style="min-width: 120px;">Role / Branch</th>
            <th style="min-width: 110px;">Basic Salary</th>
            <th style="min-width: 130px;">Attendance Stats</th>
            <th style="min-width: 110px;">Overtime</th>
            <th style="min-width: 110px;">Deductions</th>
            <th style="min-width: 130px;">Net Payable</th>
            <th style="min-width: 100px;">Status</th>
            <th class="text-end" style="min-width: 150px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($staffPayroll)): ?>
            <tr><td colspan="9" class="text-center py-5 text-muted">No staff members found matching criteria.</td></tr>
          <?php else: ?>
            <?php foreach ($staffPayroll as $sp): ?>
              <?php
                $isGenerated = !empty($sp['payroll_id']);
                $basic = (float)($sp['basic_salary'] ?: 5000.00);
                $curr = $sp['salary_currency'] ?: 'AED';
                $net = $isGenerated ? (float)$sp['net_salary'] : $basic;
                $present = $isGenerated ? (int)$sp['present_days'] : (int)$sp['recorded_present_days'];
                $absent = $isGenerated ? (int)$sp['absent_days'] : (int)$sp['recorded_absent_days'];
                $otHrs = $isGenerated ? (float)$sp['overtime_hours'] : (float)$sp['recorded_ot_hours'];
              ?>
              <tr>
                <td>
                  <div class="fw-bold text-dark fs-6"><?= e($sp['name']) ?></div>
                  <div class="text-muted small"><?= e($sp['designation'] ?: 'Visa Specialist') ?> &bull; <span class="font-monospace text-primary"><?= e($sp['email']) ?></span></div>
                </td>
                <td>
                  <div class="badge bg-light text-dark border fw-semibold"><?= e($sp['role_name']) ?></div>
                  <div class="text-muted small mt-1"><i class="fa-solid fa-location-dot text-danger me-1"></i><?= e($sp['branch_name'] ?: 'Main Branch') ?></div>
                </td>
                <td>
                  <span class="fw-bold font-monospace text-dark"><?= $curr ?> <?= number_format($basic, 2) ?></span>
                </td>
                <td>
                  <div class="small">
                    <span class="badge bg-success-subtle text-success border border-success-subtle me-1" title="Present Days"><i class="fa-solid fa-check me-1"></i><?= $present ?>d</span>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="Absent Days"><i class="fa-solid fa-xmark me-1"></i><?= $absent ?>d</span>
                  </div>
                  <?php if ($isGenerated && (int)$sp['approved_paid_leave_days'] > 0): ?>
                    <div class="text-muted small" style="font-size: 0.72rem;">Paid Leave: <?= (int)$sp['approved_paid_leave_days'] ?>d</div>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="small fw-semibold text-dark"><?= $otHrs ?> hrs</div>
                  <?php if ($isGenerated && (float)$sp['overtime_amount'] > 0): ?>
                    <div class="text-success small fw-bold">+<?= $curr ?> <?= number_format((float)$sp['overtime_amount'], 2) ?></div>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($isGenerated): ?>
                    <div class="small text-danger fw-bold">-<?= $curr ?> <?= number_format((float)$sp['unpaid_absence_deductions'] + (float)$sp['deductions'], 2) ?></div>
                  <?php else: ?>
                    <span class="text-muted small">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="fw-bold fs-6 font-monospace <?= $isGenerated ? 'text-success' : 'text-primary' ?>">
                    <?= $curr ?> <?= number_format($net, 2) ?>
                  </div>
                  <?php if ($isGenerated): ?>
                    <div class="text-muted font-monospace" style="font-size: 0.7rem;"><?= e($sp['payslip_number']) ?></div>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($isGenerated): ?>
                    <span class="badge bg-success px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i><?= e($sp['payment_status'] ?: 'Processed') ?></span>
                  <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1"><i class="fa-solid fa-clock me-1"></i>Pending</span>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <div class="d-inline-flex align-items-center gap-1">
                    <button type="button" class="btn btn-sm btn-primary py-1 px-2.5 fw-semibold" onclick="openPayrollCalculatorModal(<?= htmlspecialchars(json_encode($sp)) ?>, '<?= e($selectedMonth) ?>')">
                      <i class="fa-solid fa-calculator me-1"></i> <?= $isGenerated ? 'Recalculate' : 'Process' ?>
                    </button>
                    <?php if ($isGenerated): ?>
                      <a href="/payroll/payslip?id=<?= $sp['payroll_id'] ?>" class="btn btn-sm btn-outline-success py-1 px-2" title="View &amp; Print Payslip" target="_blank">
                        <i class="fa-solid fa-file-invoice"></i>
                      </a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODAL: PAYROLL CALCULATOR & GENERATOR -->
<div class="modal fade" id="payrollCalculatorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-calculator me-2"></i> Payroll Computation &amp; Payslip Generator</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/payroll/generate" method="POST" id="payrollForm">
        <?= csrf_field() ?>
        <input type="hidden" name="user_id" id="calcUserId">
        <input type="hidden" name="month" id="calcMonth">
        <input type="hidden" name="currency" id="calcCurrency" value="AED">

        <div class="modal-body p-4 text-start">
          <div class="p-3 bg-light rounded border mb-3 d-flex align-items-center justify-content-between">
            <div>
              <div class="small text-muted">Employee Profile:</div>
              <div class="fw-bold fs-6 text-dark" id="calcStaffName">—</div>
              <div class="small text-muted" id="calcStaffMeta">—</div>
            </div>
            <div class="text-end">
              <div class="small text-muted">Selected Month:</div>
              <div class="fw-bold text-primary fs-6" id="calcDisplayMonth">—</div>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label small fw-bold">Basic Salary (<span class="calcCurrDisplay">AED</span>)</label>
              <input type="number" step="0.01" name="basic_salary" id="calcBasicSalary" class="form-control font-monospace fw-bold" required oninput="recomputePayroll()">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">Standard Working Days</label>
              <input type="number" name="working_days" id="calcWorkingDays" class="form-control" value="26" min="1" max="31" required oninput="recomputePayroll()">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">Present Days</label>
              <input type="number" name="present_days" id="calcPresentDays" class="form-control" value="24" min="0" max="31" required oninput="recomputePayroll()">
            </div>

            <div class="col-md-3">
              <label class="form-label small fw-bold">Absent Days</label>
              <input type="number" name="absent_days" id="calcAbsentDays" class="form-control text-danger fw-bold" value="0" min="0" oninput="recomputePayroll()">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold">Approved Paid Leave</label>
              <input type="number" name="paid_leave_days" id="calcPaidLeave" class="form-control" value="0" min="0" oninput="recomputePayroll()">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold">Unpaid Leave Days</label>
              <input type="number" name="unpaid_leave_days" id="calcUnpaidLeave" class="form-control text-danger" value="0" min="0" oninput="recomputePayroll()">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold">Overtime Hours</label>
              <input type="number" step="0.5" name="overtime_hours" id="calcOtHours" class="form-control text-success fw-bold" value="0" min="0" oninput="recomputePayroll()">
            </div>

            <div class="col-md-4">
              <label class="form-label small fw-bold">Allowances &amp; Additions (+)</label>
              <input type="number" step="0.01" name="allowances" id="calcAllowances" class="form-control text-success font-monospace" value="0.00" oninput="recomputePayroll()">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">Special Deductions (-)</label>
              <input type="number" step="0.01" name="deductions" id="calcDeductions" class="form-control text-danger font-monospace" value="0.00" oninput="recomputePayroll()">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">Advance Salary / Loan (-)</label>
              <input type="number" step="0.01" name="advance_salary" id="calcAdvance" class="form-control text-danger font-monospace" value="0.00" oninput="recomputePayroll()">
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-bold">Payment Method</label>
              <select name="payment_method" class="form-select">
                <option value="Bank Transfer">Bank Transfer (WPS)</option>
                <option value="Cash">Cash</option>
                <option value="Cheque">Cheque</option>
                <option value="Digital Wallet">Digital Wallet</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Disbursement Status</label>
              <select name="payment_status" class="form-select">
                <option value="Paid">Disbursed &amp; Paid</option>
                <option value="Pending">Pending Approval</option>
                <option value="Processing">Processing</option>
              </select>
            </div>
          </div>

          <!-- Real-Time Dynamic Calculation Breakdown Box -->
          <div class="mt-4 p-3 bg-white border rounded shadow-sm">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-receipt text-primary me-2"></i> Real-Time Mathematical Breakdown</h6>
            <div class="row g-2 small">
              <div class="col-6 col-md-4 text-muted">Daily Rate:</div>
              <div class="col-6 col-md-2 fw-semibold font-monospace" id="lblDailyRate">AED 0.00</div>
              <div class="col-6 col-md-4 text-muted">Hourly Rate (8h):</div>
              <div class="col-6 col-md-2 fw-semibold font-monospace" id="lblHourlyRate">AED 0.00</div>

              <div class="col-6 col-md-4 text-muted">Overtime Multiplier (1.5x):</div>
              <div class="col-6 col-md-2 fw-bold text-success font-monospace" id="lblOtAmount">+AED 0.00</div>
              <div class="col-6 col-md-4 text-muted">Unpaid Absence Deduction:</div>
              <div class="col-6 col-md-2 fw-bold text-danger font-monospace" id="lblUnpaidDeduction">-AED 0.00</div>
            </div>

            <div class="d-flex align-items-center justify-content-between pt-3 mt-3 border-top">
              <span class="fs-5 fw-bold text-dark">FINAL NET PAYABLE:</span>
              <span class="fs-4 fw-bold font-monospace text-success" id="lblFinalNet">AED 0.00</span>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light p-3">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success btn-sm px-4 fw-bold">
            <i class="fa-solid fa-check-double me-1"></i> Save &amp; Issue Official Payslip
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: RECORD STAFF ATTENDANCE -->
<div class="modal fade" id="recordAttendanceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-calendar-check me-2"></i> Log Daily Staff Attendance</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/payroll/attendance/record" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4 text-start">
          <div class="mb-3">
            <label class="form-label small fw-bold">Staff Member</label>
            <select name="user_id" class="form-select" required>
              <option value="">Select Employee...</option>
              <?php foreach ($staffPayroll as $s): ?>
                <option value="<?= $s['id'] ?>"><?= e($s['name']) ?> (<?= e($s['designation']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Date</label>
            <input type="date" name="attendance_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-bold">Attendance Status</label>
              <select name="status" class="form-select">
                <option value="Present">Present</option>
                <option value="Absent">Absent (Unpaid)</option>
                <option value="On-Leave">Approved Leave</option>
                <option value="Half-Day">Half Day</option>
                <option value="Late">Late Arrival</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-bold">Overtime Hours</label>
              <input type="number" step="0.5" name="overtime_hours" class="form-control" value="0.0" min="0">
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-bold">Check-in Time</label>
              <input type="time" name="check_in_time" class="form-control" value="09:00">
            </div>
            <div class="col-6">
              <label class="form-label small fw-bold">Check-out Time</label>
              <input type="time" name="check_out_time" class="form-control" value="18:00">
            </div>
          </div>
          <div class="mb-2">
            <label class="form-label small fw-bold">Notes / Observations</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light p-3">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success btn-sm fw-bold"><i class="fa-solid fa-save me-1"></i> Save Attendance</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openPayrollCalculatorModal(staff, month) {
  document.getElementById('calcUserId').value = staff.id;
  document.getElementById('calcMonth').value = month;
  document.getElementById('calcStaffName').textContent = staff.name;
  document.getElementById('calcStaffMeta').textContent = (staff.designation || 'Specialist') + ' • ' + (staff.branch_name || 'Main Office');
  document.getElementById('calcDisplayMonth').textContent = month;

  const curr = staff.salary_currency || 'AED';
  document.getElementById('calcCurrency').value = curr;
  document.querySelectorAll('.calcCurrDisplay').forEach(el => el.textContent = curr);

  document.getElementById('calcBasicSalary').value = parseFloat(staff.basic_salary || 5000.00).toFixed(2);
  document.getElementById('calcWorkingDays').value = parseInt(staff.working_days || 26);
  document.getElementById('calcPresentDays').value = parseInt(staff.present_days !== null && staff.present_days !== undefined && staff.payroll_id ? staff.present_days : (staff.recorded_present_days || 24));
  document.getElementById('calcAbsentDays').value = parseInt(staff.absent_days !== null && staff.absent_days !== undefined && staff.payroll_id ? staff.absent_days : (staff.recorded_absent_days || 0));
  document.getElementById('calcPaidLeave').value = parseInt(staff.approved_paid_leave_days || 0);
  document.getElementById('calcUnpaidLeave').value = parseInt(staff.unpaid_leave_days || 0);
  document.getElementById('calcOtHours').value = parseFloat(staff.overtime_hours !== null && staff.overtime_hours !== undefined && staff.payroll_id ? staff.overtime_hours : (staff.recorded_ot_hours || 0.0)).toFixed(1);
  document.getElementById('calcAllowances').value = parseFloat(staff.allowances || 0.00).toFixed(2);
  document.getElementById('calcDeductions').value = parseFloat(staff.deductions || 0.00).toFixed(2);
  document.getElementById('calcAdvance').value = parseFloat(staff.advance_salary || 0.00).toFixed(2);

  recomputePayroll();
  new bootstrap.Modal(document.getElementById('payrollCalculatorModal')).show();
}

function recomputePayroll() {
  const basic = parseFloat(document.getElementById('calcBasicSalary').value) || 0;
  const workDays = parseInt(document.getElementById('calcWorkingDays').value) || 26;
  const absent = parseInt(document.getElementById('calcAbsentDays').value) || 0;
  const unpaidLeave = parseInt(document.getElementById('calcUnpaidLeave').value) || 0;
  const otHrs = parseFloat(document.getElementById('calcOtHours').value) || 0;
  const allowances = parseFloat(document.getElementById('calcAllowances').value) || 0;
  const deductions = parseFloat(document.getElementById('calcDeductions').value) || 0;
  const advance = parseFloat(document.getElementById('calcAdvance').value) || 0;
  const curr = document.getElementById('calcCurrency').value || 'AED';

  const dailyRate = workDays > 0 ? (basic / workDays) : 0;
  const hourlyRate = dailyRate / 8;
  const otRate = hourlyRate * 1.5;
  const otAmount = otHrs * otRate;
  const unpaidAbsenceDeduction = dailyRate * (absent + unpaidLeave);
  const net = Math.max(0, basic + otAmount + allowances - deductions - unpaidAbsenceDeduction - advance);

  document.getElementById('lblDailyRate').textContent = curr + ' ' + dailyRate.toFixed(2);
  document.getElementById('lblHourlyRate').textContent = curr + ' ' + hourlyRate.toFixed(2);
  document.getElementById('lblOtAmount').textContent = '+' + curr + ' ' + otAmount.toFixed(2);
  document.getElementById('lblUnpaidDeduction').textContent = '-' + curr + ' ' + unpaidAbsenceDeduction.toFixed(2);
  document.getElementById('lblFinalNet').textContent = curr + ' ' + net.toFixed(2);
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
