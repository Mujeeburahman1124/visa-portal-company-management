<?php
$pageTitle = 'Official Staff Payslip — ' . e($payslip['payslip_number']);
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container py-4">
  <!-- Print Controls (hidden in print) -->
  <div class="d-flex align-items-center justify-content-between mb-4 d-print-none">
    <a href="/payroll" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> Back to Payroll</a>
    <div class="d-flex align-items-center gap-2">
      <button onclick="window.print()" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
        <i class="fa-solid fa-print me-1"></i> Print Payslip / Export PDF
      </button>
    </div>
  </div>

  <!-- Official Printable Payslip Document -->
  <div class="card shadow-sm border p-4 p-md-5 bg-white font-serif" style="font-family: 'Times New Roman', Times, serif; color: #111827;">
    <!-- Company Header -->
    <div class="row align-items-center border-bottom pb-4 mb-4">
      <div class="col-8">
        <h2 class="fw-bold text-dark mb-1" style="font-family: 'Times New Roman', Times, serif;"><?= e(\App\Config\App::COMPANY_NAME) ?></h2>
        <div class="text-muted small">Global Visa Processing &amp; Consular Management Services</div>
        <div class="small mt-1"><?= e($payslip['branch_name'] ?: 'Corporate Headquarters') ?> &bull; <?= e($payslip['branch_address'] ?: 'Business Bay, Dubai, UAE') ?></div>
        <div class="small text-muted">Email: <?= e(\App\Config\App::COMPANY_EMAIL) ?> | Phone: <?= e(\App\Config\App::COMPANY_PHONE) ?></div>
      </div>
      <div class="col-4 text-end">
        <div class="badge bg-light text-dark border fs-6 px-3 py-2 fw-bold font-monospace"><?= e($payslip['payslip_number']) ?></div>
        <div class="small text-muted mt-2">Payroll Month: <strong class="text-dark fs-6"><?= e($payslip['payroll_month']) ?></strong></div>
        <div class="small text-muted">Disbursement: <?= e($payslip['payment_date'] ?: date('Y-m-d')) ?></div>
      </div>
    </div>

    <!-- Employee & Contract Details Table -->
    <div class="row g-3 mb-4">
      <div class="col-12 col-md-6">
        <table class="table table-sm table-borderless mb-0">
          <tr>
            <td class="text-muted" style="width: 140px;">Employee Name:</td>
            <td class="fw-bold fs-6"><?= e($payslip['staff_name']) ?></td>
          </tr>
          <tr>
            <td class="text-muted">Designation:</td>
            <td class="fw-semibold"><?= e($payslip['designation'] ?: 'Visa Specialist') ?></td>
          </tr>
          <tr>
            <td class="text-muted">Department / Role:</td>
            <td><?= e($payslip['department'] ?: 'Visa Department') ?> (<?= e($payslip['role_name']) ?>)</td>
          </tr>
          <tr>
            <td class="text-muted">Staff Email:</td>
            <td class="font-monospace"><?= e($payslip['staff_email']) ?></td>
          </tr>
        </table>
      </div>
      <div class="col-12 col-md-6">
        <table class="table table-sm table-borderless mb-0">
          <tr>
            <td class="text-muted" style="width: 150px;">Working Days:</td>
            <td class="fw-bold"><?= (int)$payslip['working_days'] ?> Days</td>
          </tr>
          <tr>
            <td class="text-muted">Present / Leave:</td>
            <td><strong class="text-success"><?= (int)$payslip['present_days'] ?> Present</strong> / <?= (int)$payslip['approved_paid_leave_days'] ?> Paid Leave</td>
          </tr>
          <tr>
            <td class="text-muted">Unpaid Absences:</td>
            <td class="text-danger fw-bold"><?= (int)$payslip['absent_days'] + (int)$payslip['unpaid_leave_days'] ?> Days</td>
          </tr>
          <tr>
            <td class="text-muted">Payment Mode / Ref:</td>
            <td><?= e($payslip['payment_method']) ?> &bull; <span class="font-monospace text-muted"><?= e($payslip['transaction_reference'] ?: '—') ?></span></td>
          </tr>
        </table>
      </div>
    </div>

    <!-- Earnings & Deductions Breakdown -->
    <div class="row g-4 mb-4">
      <!-- Earnings Column -->
      <div class="col-12 col-md-6">
        <div class="border rounded p-3 bg-light-subtle h-100">
          <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-plus-circle text-success me-2"></i> Gross Earnings &amp; Additions</h6>
          <table class="table table-sm table-borderless mb-0">
            <tr>
              <td>Basic Contract Salary:</td>
              <td class="text-end font-monospace fw-semibold"><?= e($payslip['currency']) ?> <?= number_format((float)$payslip['basic_salary'], 2) ?></td>
            </tr>
            <tr>
              <td>Approved Overtime (<?= (float)$payslip['overtime_hours'] ?> hrs @ <?= e($payslip['currency']) ?> <?= number_format((float)$payslip['overtime_rate'], 2) ?>/hr):</td>
              <td class="text-end font-monospace text-success fw-semibold">+<?= e($payslip['currency']) ?> <?= number_format((float)$payslip['overtime_amount'], 2) ?></td>
            </tr>
            <tr>
              <td>Allowances &amp; Travel Subsidy:</td>
              <td class="text-end font-monospace text-success fw-semibold">+<?= e($payslip['currency']) ?> <?= number_format((float)$payslip['allowances'], 2) ?></td>
            </tr>
            <tr class="border-top">
              <td class="fw-bold">Total Gross Earnings:</td>
              <td class="text-end font-monospace fw-bold fs-6"><?= e($payslip['currency']) ?> <?= number_format((float)$payslip['basic_salary'] + (float)$payslip['overtime_amount'] + (float)$payslip['allowances'], 2) ?></td>
            </tr>
          </table>
        </div>
      </div>

      <!-- Deductions Column -->
      <div class="col-12 col-md-6">
        <div class="border rounded p-3 bg-light-subtle h-100">
          <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-minus-circle text-danger me-2"></i> Payroll Deductions</h6>
          <table class="table table-sm table-borderless mb-0">
            <tr>
              <td>Unpaid Absence Deductions:</td>
              <td class="text-end font-monospace text-danger fw-semibold">-<?= e($payslip['currency']) ?> <?= number_format((float)$payslip['unpaid_absence_deductions'], 2) ?></td>
            </tr>
            <tr>
              <td>Standard Deductions / Tax / Penalties:</td>
              <td class="text-end font-monospace text-danger fw-semibold">-<?= e($payslip['currency']) ?> <?= number_format((float)$payslip['deductions'], 2) ?></td>
            </tr>
            <tr>
              <td>Salary Advance / Loans Settled:</td>
              <td class="text-end font-monospace text-danger fw-semibold">-<?= e($payslip['currency']) ?> <?= number_format((float)$payslip['advance_salary'], 2) ?></td>
            </tr>
            <tr class="border-top">
              <td class="fw-bold">Total Deductions:</td>
              <td class="text-end font-monospace fw-bold fs-6 text-danger">-<?= e($payslip['currency']) ?> <?= number_format((float)$payslip['unpaid_absence_deductions'] + (float)$payslip['deductions'] + (float)$payslip['advance_salary'], 2) ?></td>
            </tr>
          </table>
        </div>
      </div>
    </div>

    <!-- Final Net Disbursed Box -->
    <div class="p-4 bg-light border border-2 border-dark rounded mb-4 text-center">
      <div class="small text-muted text-uppercase fw-bold letter-spacing-1">Official Net Disbursed Salary</div>
      <div class="display-6 fw-bold font-monospace text-dark mt-1">
        <?= e($payslip['currency']) ?> <?= number_format((float)$payslip['net_salary'], 2) ?>
      </div>
      <div class="text-muted small mt-2">
        Disbursement Method: <strong><?= e($payslip['payment_method']) ?></strong> &bull; Status: <strong class="text-success"><?= e($payslip['payment_status']) ?></strong>
      </div>
    </div>

    <!-- Notes & Authorization Sign-off -->
    <div class="mt-4 pt-4 border-top">
      <div class="row text-center mt-5">
        <div class="col-4">
          <div class="border-bottom pb-4 mb-2 mx-auto" style="width: 80%;"></div>
          <div class="small fw-bold">Prepared By</div>
          <div class="text-muted" style="font-size: 0.75rem;"><?= e($payslip['generated_by_name'] ?: 'Accounts Officer') ?></div>
        </div>
        <div class="col-4">
          <div class="border-bottom pb-4 mb-2 mx-auto" style="width: 80%;"></div>
          <div class="small fw-bold">Authorized &amp; Approved</div>
          <div class="text-muted" style="font-size: 0.75rem;">Finance Director</div>
        </div>
        <div class="col-4">
          <div class="border-bottom pb-4 mb-2 mx-auto" style="width: 80%;"></div>
          <div class="small fw-bold">Employee Acknowledgment</div>
          <div class="text-muted" style="font-size: 0.75rem;"><?= e($payslip['staff_name']) ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
