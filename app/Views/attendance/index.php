<?php
$pageTitle = 'Staff Attendance & Working Hours — MS TRAVEL HUB';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
?>

<div class="content-body">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show mb-4 shadow-sm" role="alert">
      <?= e($flash['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Top Action & Title Bar -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
      <h3 class="fw-bold brand-font text-dark mb-1">
        <i class="fa-solid fa-user-clock text-primary me-2"></i>Staff Attendance &amp; Working Hours
      </h3>
      <p class="text-muted small mb-0">Monitor real-time staff check-ins, daily work hours, overtime, and administrative corrections.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="/attendance/export?<?= http_build_query($_GET) ?>" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
        <i class="fa-solid fa-file-excel text-success me-1"></i> Export CSV
      </a>
      <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#recordAttendanceModal">
        <i class="fa-solid fa-plus me-1"></i> Log Attendance
      </button>
    </div>
  </div>

  <!-- Summary KPI Cards -->
  <div class="row g-3 mb-4">
    <div class="col-xl-2 col-md-4 col-6">
      <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-primary border-4 h-100">
        <div class="text-muted small fw-semibold text-uppercase">Total Staff</div>
        <div class="fs-4 fw-bold text-dark mt-1"><?= number_format($totalStaff) ?></div>
        <div class="text-muted small mt-1"><i class="fa-solid fa-users text-primary me-1"></i>Active Team</div>
      </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
      <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-success border-4 h-100">
        <div class="text-muted small fw-semibold text-uppercase">Present Today</div>
        <div class="fs-4 fw-bold text-success mt-1"><?= number_format($presentToday) ?></div>
        <div class="text-muted small mt-1"><i class="fa-solid fa-circle-check text-success me-1"></i>On Duty</div>
      </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
      <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-danger border-4 h-100">
        <div class="text-muted small fw-semibold text-uppercase">Absent Today</div>
        <div class="fs-4 fw-bold text-danger mt-1"><?= number_format($absentToday) ?></div>
        <div class="text-muted small mt-1"><i class="fa-solid fa-circle-xmark text-danger me-1"></i>Not Checked In</div>
      </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
      <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-warning border-4 h-100">
        <div class="text-muted small fw-semibold text-uppercase">Late / Half Day</div>
        <div class="fs-4 fw-bold text-warning mt-1"><?= number_format($lateHalfDayToday) ?></div>
        <div class="text-muted small mt-1"><i class="fa-solid fa-clock text-warning me-1"></i>Partial Shifts</div>
      </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
      <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-info border-4 h-100">
        <div class="text-muted small fw-semibold text-uppercase">On Leave Today</div>
        <div class="fs-4 fw-bold text-info mt-1"><?= number_format($onLeaveToday) ?></div>
        <div class="text-muted small mt-1"><i class="fa-solid fa-plane-departure text-info me-1"></i>Approved Leave</div>
      </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
      <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-secondary border-4 h-100">
        <div class="text-muted small fw-semibold text-uppercase">Monthly Overtime</div>
        <div class="fs-4 fw-bold text-dark mt-1"><?= number_format($monthOtHours, 1) ?> <span class="fs-6 fw-normal text-muted">hrs</span></div>
        <div class="text-muted small mt-1"><i class="fa-solid fa-bolt text-warning me-1"></i>Current Month</div>
      </div>
    </div>
  </div>

  <!-- Multi-Criteria Filter Bar -->
  <div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
      <form action="/attendance" method="GET" class="row g-2 align-items-end">
        <div class="col-lg-2 col-md-4 col-6">
          <label class="form-label small fw-semibold mb-1">Preset Period</label>
          <select name="preset" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Custom / Specific Date</option>
            <option value="today" <?= ($_GET['preset'] ?? '') === 'today' ? 'selected' : '' ?>>Today</option>
            <option value="yesterday" <?= ($_GET['preset'] ?? '') === 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
            <option value="this_week" <?= ($_GET['preset'] ?? '') === 'this_week' ? 'selected' : '' ?>>This Week</option>
            <option value="this_month" <?= ($_GET['preset'] ?? '') === 'this_month' ? 'selected' : '' ?>>This Month</option>
          </select>
        </div>

        <div class="col-lg-2 col-md-4 col-6">
          <label class="form-label small fw-semibold mb-1">Date</label>
          <input type="date" name="date" class="form-control form-control-sm" value="<?= e($selectedDate) ?>">
        </div>

        <div class="col-lg-2 col-md-4 col-6">
          <label class="form-label small fw-semibold mb-1">Staff Member</label>
          <select name="staff_id" class="form-select form-select-sm">
            <option value="">All Staff</option>
            <?php foreach ($staffList as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $staffId === (int)$s['id'] ? 'selected' : '' ?>>
                <?= e($s['name']) ?> (<?= e($s['designation'] ?: 'Staff') ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-lg-2 col-md-4 col-6">
          <label class="form-label small fw-semibold mb-1">Department</label>
          <select name="department" class="form-select form-select-sm">
            <option value="">All Departments</option>
            <?php foreach ($departments as $dept): ?>
              <option value="<?= e($dept) ?>" <?= $department === $dept ? 'selected' : '' ?>><?= e($dept) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-lg-2 col-md-4 col-6">
          <label class="form-label small fw-semibold mb-1">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">All Statuses</option>
            <option value="Present" <?= $status === 'Present' ? 'selected' : '' ?>>Present</option>
            <option value="Absent" <?= $status === 'Absent' ? 'selected' : '' ?>>Absent</option>
            <option value="Late" <?= $status === 'Late' ? 'selected' : '' ?>>Late</option>
            <option value="Half Day" <?= $status === 'Half Day' ? 'selected' : '' ?>>Half Day</option>
            <option value="Leave" <?= $status === 'Leave' ? 'selected' : '' ?>>Leave</option>
            <option value="Holiday" <?= $status === 'Holiday' ? 'selected' : '' ?>>Holiday</option>
            <option value="Overtime" <?= $status === 'Overtime' ? 'selected' : '' ?>>Overtime</option>
          </select>
        </div>

        <div class="col-lg-2 col-md-4 col-6 d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm flex-fill">
            <i class="fa-solid fa-filter me-1"></i> Filter
          </button>
          <a href="/attendance" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
            <i class="fa-solid fa-rotate-left"></i>
          </a>
        </div>
      </form>

      <!-- Date Range (Optional Drawer) -->
      <div class="mt-2 pt-2 border-top d-flex flex-wrap align-items-center justify-content-between gap-2 small text-muted">
        <div class="d-flex align-items-center gap-2">
          <span>Date Range Search:</span>
          <form action="/attendance" method="GET" class="d-inline-flex align-items-center gap-2">
            <input type="date" name="date_from" class="form-control form-control-sm" style="width: 140px;" value="<?= e($dateFrom) ?>">
            <span>to</span>
            <input type="date" name="date_to" class="form-control form-control-sm" style="width: 140px;" value="<?= e($dateTo) ?>">
            <button type="submit" class="btn btn-outline-primary btn-sm px-2 py-1">Apply Range</button>
          </form>
        </div>
        <div>
          Showing <strong><?= count($records) ?></strong> attendance entries
        </div>
      </div>
    </div>
  </div>

  <!-- Attendance Table Card -->
  <div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
      <h6 class="card-title fw-bold text-dark mb-0">
        <i class="fa-solid fa-list-check text-primary me-2"></i>Attendance Ledger
      </h6>
      <span class="badge bg-light text-muted border"><?= count($records) ?> Records Loaded</span>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light text-muted small text-uppercase">
          <tr>
            <th>Staff Member</th>
            <th>Department / Branch</th>
            <th>Date</th>
            <th>Status</th>
            <th>Check-In</th>
            <th>Check-Out</th>
            <th>Working Hours</th>
            <th>Overtime</th>
            <th>Notes &amp; Audit</th>
            <th class="text-end pe-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($records)): ?>
            <tr>
              <td colspan="10" class="text-center py-5 text-muted">
                <i class="fa-solid fa-calendar-xmark fs-1 text-muted opacity-50 mb-3 d-block"></i>
                <h6 class="fw-bold mb-1">No attendance records found</h6>
                <p class="small mb-3">No staff check-ins match the selected date or filter criteria.</p>
                <button type="button" class="btn btn-outline-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#recordAttendanceModal">
                  <i class="fa-solid fa-plus me-1"></i> Log First Entry
                </button>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($records as $r): ?>
              <?php
                $statusClass = match($r['status']) {
                  'Present' => 'bg-success text-white',
                  'Absent' => 'bg-danger text-white',
                  'Late' => 'bg-warning text-dark',
                  'Half Day' => 'bg-warning text-dark',
                  'Leave' => 'bg-info text-dark',
                  'Holiday' => 'bg-secondary text-white',
                  default => 'bg-primary text-white'
                };
              ?>
              <tr>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px;">
                      <?= strtoupper(substr($r['staff_name'], 0, 1)) ?>
                    </div>
                    <div>
                      <div class="fw-bold text-dark small"><?= e($r['staff_name']) ?></div>
                      <div class="text-muted" style="font-size: 11px;"><?= e($r['designation'] ?: 'Staff') ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="small fw-semibold text-dark"><?= e($r['department'] ?: 'Operations') ?></div>
                  <div class="text-muted small" style="font-size: 11px;"><?= e($r['branch_name'] ?: 'Main Office') ?></div>
                </td>
                <td>
                  <span class="small fw-semibold text-dark"><?= format_date($r['attendance_date']) ?></span>
                </td>
                <td>
                  <span class="badge <?= $statusClass ?> px-2 py-1 rounded-pill small">
                    <?= e($r['status']) ?>
                  </span>
                </td>
                <td>
                  <span class="small font-monospace"><?= $r['check_in_time'] ? e($r['check_in_time']) : '—' ?></span>
                </td>
                <td>
                  <span class="small font-monospace"><?= $r['check_out_time'] ? e($r['check_out_time']) : '—' ?></span>
                </td>
                <td>
                  <span class="badge bg-light text-dark border small fw-semibold">
                    <?= number_format((float)($r['working_hours'] ?? 0), 2) ?> hrs
                  </span>
                </td>
                <td>
                  <?php if ((float)($r['overtime_hours'] ?? 0) > 0): ?>
                    <span class="badge bg-warning bg-opacity-25 text-dark border border-warning small fw-bold">
                      +<?= number_format((float)$r['overtime_hours'], 1) ?> hrs
                    </span>
                  <?php else: ?>
                    <span class="text-muted small">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="small text-truncate" style="max-width: 180px;" title="<?= e($r['notes'] ?: '') ?>">
                    <?= e($r['notes'] ?: '—') ?>
                  </div>
                  <?php if (!empty($r['correction_notes'])): ?>
                    <div class="text-danger small" style="font-size: 10px;" title="<?= e($r['correction_notes']) ?>">
                      <i class="fa-solid fa-triangle-exclamation me-1"></i><?= e($r['correction_notes']) ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td class="text-end pe-3">
                  <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2" onclick='openEditModal(<?= json_encode($r) ?>)'>
                    <i class="fa-solid fa-pen-to-square me-1"></i> Correct
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODAL: RECORD STAFF ATTENDANCE -->
<div class="modal fade" id="recordAttendanceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="/attendance/record" method="POST">
        <?= csrf_field() ?>
        <div class="modal-header bg-primary text-white py-3">
          <h6 class="modal-title fw-bold"><i class="fa-solid fa-calendar-check me-2"></i>Log Staff Attendance</h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Staff Member <span class="text-danger">*</span></label>
            <select name="user_id" class="form-select" required>
              <option value="">— Select Staff Member —</option>
              <?php foreach ($staffList as $s): ?>
                <option value="<?= $s['id'] ?>"><?= e($s['name']) ?> (<?= e($s['department'] ?: 'Staff') ?> - <?= e($s['designation'] ?: '') ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Attendance Date <span class="text-danger">*</span></label>
              <input type="date" name="attendance_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
              <select name="status" class="form-select" required>
                <option value="Present">Present</option>
                <option value="Absent">Absent</option>
                <option value="Late">Late</option>
                <option value="Half Day">Half Day</option>
                <option value="Leave">Leave</option>
                <option value="Holiday">Holiday</option>
                <option value="Overtime">Overtime</option>
              </select>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Check-In Time</label>
              <input type="time" name="check_in_time" class="form-control" value="09:00">
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Check-Out Time</label>
              <input type="time" name="check_out_time" class="form-control" value="18:00">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Overtime Hours (if applicable)</label>
            <input type="number" step="0.5" min="0" max="16" name="overtime_hours" class="form-control" placeholder="0.0">
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold">Notes / Shift Remarks</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light py-2">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">Save Record</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: EDIT / CORRECT ATTENDANCE -->
<div class="modal fade" id="editAttendanceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="/attendance/update" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="editAttId">

        <div class="modal-header bg-dark text-white py-3">
          <h6 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>Correct Staff Attendance</h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="p-3 bg-light rounded-3 mb-3 border">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <span class="text-muted small">Staff Member:</span>
                <div class="fw-bold text-dark" id="editStaffName">—</div>
              </div>
              <div class="text-end">
                <span class="text-muted small">Date:</span>
                <div class="fw-bold text-primary" id="editAttDate">—</div>
              </div>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
              <select name="status" id="editStatus" class="form-select" required>
                <option value="Present">Present</option>
                <option value="Absent">Absent</option>
                <option value="Late">Late</option>
                <option value="Half Day">Half Day</option>
                <option value="Leave">Leave</option>
                <option value="Holiday">Holiday</option>
                <option value="Overtime">Overtime</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Working Hours</label>
              <input type="number" step="0.1" min="0" max="24" name="working_hours" id="editWorkingHours" class="form-control">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Check-In Time</label>
              <input type="time" name="check_in_time" id="editCheckIn" class="form-control">
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Check-Out Time</label>
              <input type="time" name="check_out_time" id="editCheckOut" class="form-control">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Overtime Hours</label>
            <input type="number" step="0.5" min="0" max="16" name="overtime_hours" id="editOtHours" class="form-control">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Correction Reason <span class="text-danger">*</span></label>
            <input type="text" name="correction_reason" class="form-control" placeholder="E.g., Biometrics failed, Approved manager adjustment..." required>
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold">Internal Notes</label>
            <textarea name="notes" id="editNotes" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light py-2">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">Save Correction</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openEditModal(record) {
  document.getElementById('editAttId').value = record.id;
  document.getElementById('editStaffName').textContent = record.staff_name;
  document.getElementById('editAttDate').textContent = record.attendance_date;
  document.getElementById('editStatus').value = record.status;
  document.getElementById('editCheckIn').value = record.check_in_time || '';
  document.getElementById('editCheckOut').value = record.check_out_time || '';
  document.getElementById('editWorkingHours').value = record.working_hours || '';
  document.getElementById('editOtHours').value = record.overtime_hours || '0';
  document.getElementById('editNotes').value = record.notes || '';

  const modal = new bootstrap.Modal(document.getElementById('editAttendanceModal'));
  modal.show();
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
