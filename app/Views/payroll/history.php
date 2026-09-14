<?php
$pageTitle = 'Historical Payroll Archive & Ledger — MS TRAVEL HUB';
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

  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <h3 class="fw-bold brand-font text-dark mb-1">Payroll History &amp; Disbursement Archive</h3>
      <p class="text-muted small mb-0">Immutable records of processed staff payslips, gross earnings, absence deductions, and bank transaction references.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="/payroll" class="btn btn-outline-primary btn-sm px-3 shadow-sm">
        <i class="fa-solid fa-calculator me-1"></i> Current Month Payroll
      </a>
    </div>
  </div>

  <!-- Filter Bar -->
  <div class="card card-enterprise shadow-sm mb-4">
    <div class="card-body p-3">
      <form method="GET" action="/payroll/history" class="row g-2 align-items-center">
        <div class="col-12 col-md-3">
          <label class="form-label small fw-bold text-muted mb-1">Search Records</label>
          <input type="text" name="search" class="form-control form-control-sm" placeholder="Staff name, code, slip #..." value="<?= e($search) ?>">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label small fw-bold text-muted mb-1">Month</label>
          <input type="month" name="month" class="form-control form-control-sm font-monospace" value="<?= e($month) ?>" onchange="this.form.submit()">
        </div>
        <div class="col-12 col-md-2">
          <label class="form-label small fw-bold text-muted mb-1">Status</label>
          <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="Paid" <?= $status === 'Paid' ? 'selected' : '' ?>>Disbursed &amp; Paid</option>
            <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Processing" <?= $status === 'Processing' ? 'selected' : '' ?>>Processing</option>
          </select>
        </div>
        <div class="col-12 col-md-2">
          <label class="form-label small fw-bold text-muted mb-1">Branch</label>
          <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Branches</option>
            <?php foreach ($branches as $b): ?>
              <option value="<?= $b['id'] ?>" <?= $branchId == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-md-2 d-flex align-items-end gap-1 mt-auto">
          <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="fa-solid fa-magnifying-glass me-1"></i> Filter</button>
          <a href="/payroll/history" class="btn btn-light border btn-sm" title="Reset Filters"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
      </form>
    </div>
  </div>

  <!-- Historical Payroll Records Table -->
  <div class="card card-enterprise shadow-sm">
    <div class="table-responsive">
      <table class="table-modern mb-0">
        <thead>
          <tr>
            <th style="min-width: 140px;">Payslip No / Month</th>
            <th style="min-width: 180px;">Employee</th>
            <th style="min-width: 110px;">Basic Salary</th>
            <th style="min-width: 130px;">Attendance Stats</th>
            <th style="min-width: 110px;">Overtime</th>
            <th style="min-width: 110px;">Deductions</th>
            <th style="min-width: 130px;">Net Disbursed</th>
            <th style="min-width: 120px;">Payment Mode</th>
            <th style="min-width: 90px;">Status</th>
            <th class="text-end" style="min-width: 90px;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($records)): ?>
            <tr><td colspan="10" class="text-center py-5 text-muted">No historical payroll records found.</td></tr>
          <?php else: ?>
            <?php foreach ($records as $r): ?>
              <tr>
                <td>
                  <div class="fw-bold text-dark font-monospace"><?= e($r['payslip_number']) ?></div>
                  <div class="text-muted small">Period: <strong class="text-primary"><?= e($r['payroll_month']) ?></strong></div>
                </td>
                <td>
                  <div class="fw-bold text-dark"><?= e($r['staff_name']) ?></div>
                  <div class="text-muted small"><?= e($r['designation'] ?: 'Specialist') ?> &bull; <?= e($r['branch_name'] ?: 'Main') ?></div>
                </td>
                <td>
                  <span class="font-monospace fw-semibold"><?= e($r['currency']) ?> <?= number_format((float)$r['basic_salary'], 2) ?></span>
                </td>
                <td>
                  <span class="badge bg-success-subtle text-success border border-success-subtle"><?= (int)$r['present_days'] ?>d Pres</span>
                  <?php if ((int)$r['absent_days'] > 0): ?>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><?= (int)$r['absent_days'] ?>d Abs</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="small font-monospace text-success fw-bold">+<?= e($r['currency']) ?> <?= number_format((float)$r['overtime_amount'], 2) ?></span>
                  <div class="text-muted small" style="font-size: 0.7rem;"><?= (float)$r['overtime_hours'] ?> hrs</div>
                </td>
                <td>
                  <span class="small font-monospace text-danger fw-bold">-<?= e($r['currency']) ?> <?= number_format((float)$r['unpaid_absence_deductions'] + (float)$r['deductions'] + (float)$r['advance_salary'], 2) ?></span>
                </td>
                <td>
                  <div class="fw-bold text-success font-monospace fs-6">
                    <?= e($r['currency']) ?> <?= number_format((float)$r['net_salary'], 2) ?>
                  </div>
                </td>
                <td>
                  <div class="small fw-semibold"><?= e($r['payment_method']) ?></div>
                  <div class="text-muted font-monospace" style="font-size: 0.7rem;"><?= e($r['transaction_reference'] ?: '—') ?></div>
                </td>
                <td>
                  <span class="badge bg-success px-2 py-1"><?= e($r['payment_status']) ?></span>
                </td>
                <td class="text-end">
                  <a href="/payroll/payslip?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary py-1 px-2" title="View Payslip" target="_blank">
                    <i class="fa-solid fa-file-lines me-1"></i> Slip
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
