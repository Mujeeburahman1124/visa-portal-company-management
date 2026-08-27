<?php
$pageTitle = 'Operational & Financial Reports — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';

$reportsCatalog = [
    'status' => '1. Applications by Status',
    'stage' => '2. Applications by Stage',
    'visa_type' => '3. Applications by Visa Type',
    'nationality' => '4. Applications by Nationality',
    'staff' => '5. Staff Performance & Workload',
    'pending' => '6. Pending & Bottlenecks',
    'overdue' => '7. Overdue Applications',
    'completed' => '8. Completed & Approved Visas',
    'rejected' => '9. Rejected Applications Analytics',
    'documents' => '10. Document Status & Checklist',
    'expiry' => '11. Passport & ID Expiry Report',
    'processing_time' => '12. Avg Processing Time Report',
    'finance' => '13. Financial & Gross Profit Report',
];
?>

<div class="content-body">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show mb-4" role="alert">
      <?= e($flash['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
      <h3 class="fw-bold brand-font mb-0"><i class="fa-solid fa-chart-pie text-primary me-2"></i> Operational Intelligence & Reports</h3>
      <p class="text-muted small mb-0">Audited operational metrics, staff performance benchmarks, and financial summaries.</p>
    </div>
    
    <!-- CSV Export Button -->
    <a href="/reports?type=<?= urlencode($reportType) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&export=csv" class="btn btn-outline-success btn-sm px-3 shadow-sm">
      <i class="fa-solid fa-file-csv me-1"></i> Export to CSV
    </a>
  </div>

  <!-- Filter & Report Selector -->
  <div class="card-custom mb-4 bg-white">
    <div class="card-custom-body p-3">
      <form action="/reports" method="GET" class="row g-2 align-items-center">
        <div class="col-md-4">
          <label class="form-label small fw-semibold text-muted mb-1">Select Report</label>
          <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php foreach ($reportsCatalog as $key => $name): ?>
              <option value="<?= $key ?>" <?= $reportType === $key ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label small fw-semibold text-muted mb-1">From Date</label>
          <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>">
        </div>

        <div class="col-md-3">
          <label class="form-label small fw-semibold text-muted mb-1">To Date</label>
          <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo) ?>">
        </div>

        <div class="col-md-2 mt-auto">
          <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fa-solid fa-magnifying-glass me-1"></i> Generate</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Report Data Card -->
  <div class="card-custom">
    <div class="card-custom-header bg-white">
      <span class="fw-bold fs-6 text-primary"><?= e($title) ?></span>
      <span class="badge bg-light text-dark border"><?= count($data) ?> Records Generated</span>
    </div>
    <div class="table-responsive">
      <table class="table table-custom align-middle mb-0">
        <thead>
          <tr>
            <?php foreach ($columns as $col): ?>
              <th><?= e($col) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($data)): ?>
            <tr><td colspan="<?= count($columns) ?>" class="text-center py-5 text-muted">No records found for the selected period.</td></tr>
          <?php else: ?>
            <?php foreach ($data as $row): ?>
              <tr>
                <?php for ($i = 1; $i <= count($columns); $i++): ?>
                  <td>
                    <?php 
                      $val = $row["col_{$i}"] ?? '';
                      // Format currency if number looks like currency
                      if (str_contains($columns[$i-1], '($)') || str_contains($columns[$i-1], 'Price') || str_contains($columns[$i-1], 'Revenue') || str_contains($columns[$i-1], 'Profit') || str_contains($columns[$i-1], 'Paid') || str_contains($columns[$i-1], 'Outstanding')) {
                        echo is_numeric($val) ? format_currency((float)$val) : e((string)$val);
                      } else {
                        echo e((string)$val);
                      }
                    ?>
                  </td>
                <?php endfor; ?>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
