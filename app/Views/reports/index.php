<?php
$pageTitle = 'Operational Intelligence & Analytics Reports — VISA TRACK';
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

$activePreset = $preset ?? 'custom';
?>

<!-- Include Chart.js UMD -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

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

  <!-- Page Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <h3 class="fw-bold brand-font mb-0 text-dark">
          <i class="fa-solid fa-chart-pie text-primary me-2"></i> Operational Intelligence &amp; Analytics
        </h3>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold px-2 py-1">
          <i class="fa-solid fa-chart-simple me-1"></i> Interactive Charts &amp; Drilldowns
        </span>
      </div>
      <p class="text-muted small mb-0">Multi-dimensional operational and financial reports with bar, pie, doughnut, and trend analytics by month, year, and custom dates.</p>
    </div>
    
    <div class="d-flex align-items-center gap-2">
      <a href="/reports?type=<?= urlencode($reportType) ?>&preset=<?= urlencode($activePreset) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&export=csv" class="btn btn-outline-success btn-sm px-3 shadow-sm fw-semibold">
        <i class="fa-solid fa-file-csv me-1.5"></i> Export Table (CSV)
      </a>
    </div>
  </div>

  <!-- Quick Date Presets Bar -->
  <div class="card card-enterprise mb-4 border shadow-sm">
    <div class="card-body p-2.5">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex flex-wrap align-items-center gap-1.5">
          <span class="small fw-bold text-muted me-1"><i class="fa-solid fa-clock-rotate-left me-1"></i> Quick Presets:</span>
          <a href="/reports?type=<?= urlencode($reportType) ?>&preset=today" class="btn btn-sm py-1 px-2.5 rounded-pill <?= $activePreset === 'today' ? 'btn-primary fw-bold' : 'btn-light border text-dark' ?>">
            Today
          </a>
          <a href="/reports?type=<?= urlencode($reportType) ?>&preset=this_month" class="btn btn-sm py-1 px-2.5 rounded-pill <?= $activePreset === 'this_month' ? 'btn-primary fw-bold' : 'btn-light border text-dark' ?>">
            This Month
          </a>
          <a href="/reports?type=<?= urlencode($reportType) ?>&preset=last_month" class="btn btn-sm py-1 px-2.5 rounded-pill <?= $activePreset === 'last_month' ? 'btn-primary fw-bold' : 'btn-light border text-dark' ?>">
            Last Month
          </a>
          <a href="/reports?type=<?= urlencode($reportType) ?>&preset=this_year" class="btn btn-sm py-1 px-2.5 rounded-pill <?= $activePreset === 'this_year' ? 'btn-primary fw-bold' : 'btn-light border text-dark' ?>">
            This Year (By Month)
          </a>
          <a href="/reports?type=<?= urlencode($reportType) ?>&preset=last_year" class="btn btn-sm py-1 px-2.5 rounded-pill <?= $activePreset === 'last_year' ? 'btn-primary fw-bold' : 'btn-light border text-dark' ?>">
            Last Year
          </a>
          <a href="/reports?type=<?= urlencode($reportType) ?>&preset=all_time" class="btn btn-sm py-1 px-2.5 rounded-pill <?= $activePreset === 'all_time' ? 'btn-primary fw-bold' : 'btn-light border text-dark' ?>">
            All Time (Multi-Year)
          </a>
        </div>

        <div class="text-muted small">
          <i class="fa-regular fa-calendar me-1 text-primary"></i> <strong>Period:</strong> <?= date('M d, Y', strtotime($dateFrom)) ?> &mdash; <?= date('M d, Y', strtotime($dateTo)) ?>
        </div>
      </div>
    </div>
  </div>

  <!-- KPI Metrics Strip -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="card card-enterprise border shadow-sm p-3 h-100">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-muted small fw-semibold">Total Applications</span>
          <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
            <i class="fa-solid fa-folder-open"></i>
          </div>
        </div>
        <div class="fs-4 fw-bold text-dark"><?= number_format($kpiSummary['total_apps'] ?? 0) ?></div>
        <div class="text-muted small mt-1" style="font-size: 0.76rem;">
          <i class="fa-solid fa-calendar-check text-primary me-1"></i> Recorded in period
        </div>
      </div>
    </div>

    <div class="col-6 col-lg-3">
      <div class="card card-enterprise border shadow-sm p-3 h-100">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-muted small fw-semibold">Total Revenue</span>
          <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
            <i class="fa-solid fa-dollar-sign"></i>
          </div>
        </div>
        <div class="fs-4 fw-bold text-dark"><?= format_currency((float)($kpiSummary['total_revenue'] ?? 0.0)) ?></div>
        <div class="text-muted small mt-1" style="font-size: 0.76rem;">
          <span class="text-success fw-bold"><i class="fa-solid fa-money-bill-transfer me-1"></i> Paid:</span> <?= format_currency((float)($kpiSummary['total_paid'] ?? 0.0)) ?>
        </div>
      </div>
    </div>

    <div class="col-6 col-lg-3">
      <div class="card card-enterprise border shadow-sm p-3 h-100">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-muted small fw-semibold">Gross Profit</span>
          <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
            <i class="fa-solid fa-chart-line"></i>
          </div>
        </div>
        <div class="fs-4 fw-bold text-info"><?= format_currency((float)($kpiSummary['total_profit'] ?? 0.0)) ?></div>
        <div class="text-muted small mt-1" style="font-size: 0.76rem;">
          <span class="text-muted">Cost: <?= format_currency((float)($kpiSummary['total_cost'] ?? 0.0)) ?></span>
        </div>
      </div>
    </div>

    <div class="col-6 col-lg-3">
      <div class="card card-enterprise border shadow-sm p-3 h-100">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-muted small fw-semibold">Approved / Success</span>
          <div class="rounded-circle bg-warning-subtle text-warning-text d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
            <i class="fa-solid fa-circle-check"></i>
          </div>
        </div>
        <div class="fs-4 fw-bold text-success"><?= number_format($kpiSummary['approved_count'] ?? 0) ?></div>
        <div class="text-muted small mt-1" style="font-size: 0.76rem;">
          <span class="badge bg-success-subtle text-success px-1.5 py-0.5 fw-semibold me-1"><?= $kpiSummary['approval_rate'] ?? 0 ?>%</span> Success Rate
        </div>
      </div>
    </div>
  </div>

  <!-- VISUAL CHARTS GRID: BAR, PIE, DOUGHNUT, LINE -->
  <div class="row g-4 mb-4">
    <!-- Chart 1: Monthly Performance (Bar & Line Chart) -->
    <div class="col-12 col-xl-7">
      <div class="card card-enterprise border shadow-sm h-100">
        <div class="card-header bg-white border-bottom py-3 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
          <div>
            <h6 class="fw-bold mb-0 text-dark">
              <i class="fa-solid fa-chart-column text-primary me-2"></i> Monthly Volume &amp; Revenue Trend
            </h6>
            <small class="text-muted">Month-by-month visa applications volume and revenue performance</small>
          </div>
          <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-primary active" id="btnMonthlyBar" onclick="switchMonthlyChart('bar')">
              <i class="fa-solid fa-chart-column me-1"></i> Bar
            </button>
            <button type="button" class="btn btn-outline-primary" id="btnMonthlyLine" onclick="switchMonthlyChart('line')">
              <i class="fa-solid fa-chart-line me-1"></i> Line
            </button>
          </div>
        </div>
        <div class="card-body p-3">
          <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="monthlyTrendChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Chart 2: Status & Stage Slices (Pie & Doughnut Charts) -->
    <div class="col-12 col-xl-5">
      <div class="card card-enterprise border shadow-sm h-100">
        <div class="card-header bg-white border-bottom py-3 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
          <div>
            <h6 class="fw-bold mb-0 text-dark">
              <i class="fa-solid fa-chart-pie text-success me-2"></i> Status Distribution
            </h6>
            <small class="text-muted">Proportion of approved, pending, and in-progress files</small>
          </div>
          <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-success active" id="btnStatusDoughnut" onclick="switchStatusChart('doughnut')">
              <i class="fa-solid fa-circle-notch me-1"></i> Doughnut
            </button>
            <button type="button" class="btn btn-outline-success" id="btnStatusPie" onclick="switchStatusChart('pie')">
              <i class="fa-solid fa-chart-pie me-1"></i> Pie
            </button>
          </div>
        </div>
        <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center">
          <div style="position: relative; height: 280px; width: 100%;">
            <canvas id="statusDistChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Chart 3: Multi-Year Comparison (Bar Chart by Years) -->
    <div class="col-12 col-xl-6">
      <div class="card card-enterprise border shadow-sm h-100">
        <div class="card-header bg-white border-bottom py-3 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
          <div>
            <h6 class="fw-bold mb-0 text-dark">
              <i class="fa-solid fa-calendar-days text-info me-2"></i> Multi-Year Performance Comparison (By Year)
            </h6>
            <small class="text-muted">Historical application throughput and gross turnover year-by-year</small>
          </div>
          <span class="badge bg-light text-secondary border">Annual Summary</span>
        </div>
        <div class="card-body p-3">
          <div style="position: relative; height: 270px; width: 100%;">
            <canvas id="yearlyTrendChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Chart 4: Top Destination Countries & Categories (Horizontal Bar & Polar) -->
    <div class="col-12 col-xl-6">
      <div class="card card-enterprise border shadow-sm h-100">
        <div class="card-header bg-white border-bottom py-3 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
          <div>
            <h6 class="fw-bold mb-0 text-dark">
              <i class="fa-solid fa-earth-americas text-warning-text me-2"></i> Destination Countries Breakdown
            </h6>
            <small class="text-muted">Application volume by destination destination country</small>
          </div>
          <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-secondary active" id="btnCountryBar" onclick="switchCountryChart('bar')">
              <i class="fa-solid fa-bars me-1"></i> Bar
            </button>
            <button type="button" class="btn btn-outline-secondary" id="btnCountryPolar" onclick="switchCountryChart('polarArea')">
              <i class="fa-solid fa-asterisk me-1"></i> Polar
            </button>
          </div>
        </div>
        <div class="card-body p-3">
          <div style="position: relative; height: 270px; width: 100%;">
            <canvas id="countryDistChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Detailed Filter & Report Selector (Date by Date) -->
  <div class="card card-enterprise mb-4 border shadow-sm">
    <div class="card-header bg-white border-bottom py-3 px-3">
      <h6 class="fw-bold mb-0 text-dark">
        <i class="fa-solid fa-filter text-primary me-2"></i> Custom Date-by-Date &amp; Report Type Filter
      </h6>
    </div>
    <div class="card-body p-3">
      <form action="/reports" method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="preset" value="custom">

        <div class="col-12 col-md-4">
          <label class="form-label small fw-bold text-dark mb-1">Detailed Report Catalog</label>
          <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php foreach ($reportsCatalog as $key => $name): ?>
              <option value="<?= $key ?>" <?= $reportType === $key ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-md-3">
          <label class="form-label small fw-bold text-dark mb-1">Date From (Date-by-Date)</label>
          <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>">
        </div>

        <div class="col-6 col-md-3">
          <label class="form-label small fw-bold text-dark mb-1">Date To</label>
          <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo) ?>">
        </div>

        <div class="col-12 col-md-2 d-flex gap-1">
          <button type="submit" class="btn btn-primary btn-sm flex-grow-1 fw-bold shadow-sm" title="Generate Report">
            <i class="fa-solid fa-magnifying-glass me-1"></i> Generate
          </button>
          <a href="/reports" class="btn btn-light btn-sm border" title="Reset Filters">
            <i class="fa-solid fa-rotate-left"></i>
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- Report Data Table Card -->
  <div class="card card-enterprise border shadow-sm">
    <div class="card-header bg-white border-bottom py-3 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div>
        <span class="fw-bold fs-6 text-primary"><i class="fa-solid fa-table-list me-2"></i><?= e($title) ?></span>
      </div>
      <div>
        <span class="badge bg-light text-dark border fw-semibold px-2 py-1"><?= count($data) ?> Records Loaded</span>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-custom align-middle mb-0">
        <thead class="table-light">
          <tr>
            <?php foreach ($columns as $col): ?>
              <th class="small fw-bold text-secondary text-nowrap"><?= e($col) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($data)): ?>
            <tr>
              <td colspan="<?= count($columns) ?>" class="text-center py-5 text-muted">
                <i class="fa-solid fa-inbox fa-2x mb-2 text-secondary opacity-50 d-block"></i>
                No records found matching the selected dates and filters.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($data as $row): ?>
              <tr>
                <?php for ($i = 1; $i <= count($columns); $i++): ?>
                  <td class="small">
                    <?php 
                      $val = $row["col_{$i}"] ?? '';
                      // Format currency if column indicates monetary amount
                      if (str_contains($columns[$i-1], '($)') || str_contains($columns[$i-1], 'Price') || str_contains($columns[$i-1], 'Revenue') || str_contains($columns[$i-1], 'Profit') || str_contains($columns[$i-1], 'Paid') || str_contains($columns[$i-1], 'Outstanding') || str_contains($columns[$i-1], 'Cost') || str_contains($columns[$i-1], 'Fee')) {
                        echo is_numeric($val) ? '<span class="fw-semibold text-dark">' . format_currency((float)$val) . '</span>' : e((string)$val);
                      } elseif (str_contains($columns[$i-1], 'Status') || str_contains($columns[$i-1], 'Stage')) {
                        $badgeCls = match($val) {
                          'Approved', 'Completed' => 'bg-success-subtle text-success border border-success-subtle',
                          'In Progress', 'Submitted', 'Under Review' => 'bg-primary-subtle text-primary border border-primary-subtle',
                          'Rejected' => 'bg-danger-subtle text-danger border border-danger-subtle',
                          default => 'bg-secondary-subtle text-secondary border'
                        };
                        echo '<span class="badge ' . $badgeCls . ' fw-semibold px-2 py-1">' . e((string)$val) . '</span>';
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

<!-- Chart.js Initialization & Interactive Switching -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  const brandColors = {
    primary: '#2563eb',
    primaryLight: 'rgba(37, 99, 235, 0.2)',
    success: '#10b981',
    successLight: 'rgba(16, 185, 129, 0.2)',
    danger: '#ef4444',
    warning: '#f59e0b',
    info: '#06b6d4',
    purple: '#8b5cf6',
    indigo: '#6366f1',
    slate: '#64748b'
  };

  const chartPalette = [
    '#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
    '#06b6d4', '#ec4899', '#14b8a6', '#f97316', '#64748b'
  ];

  // 1. Monthly Trend Chart (Bar & Line)
  const monthlyRaw = <?= json_encode($monthlyTrend ?? []) ?>;
  const monthlyLabels = monthlyRaw.length ? monthlyRaw.map(r => r.period) : ['Current Period'];
  const monthlyCounts = monthlyRaw.length ? monthlyRaw.map(r => parseInt(r.app_count || 0)) : [<?= (int)($kpiSummary['total_apps'] ?? 0) ?>];
  const monthlyRevenues = monthlyRaw.length ? monthlyRaw.map(r => parseFloat(r.revenue || 0)) : [<?= (float)($kpiSummary['total_revenue'] ?? 0) ?>];

  const ctxMonthly = document.getElementById('monthlyTrendChart').getContext('2d');
  window.monthlyChartInstance = new Chart(ctxMonthly, {
    type: 'bar',
    data: {
      labels: monthlyLabels,
      datasets: [
        {
          label: 'Applications (Count)',
          data: monthlyCounts,
          backgroundColor: 'rgba(37, 99, 235, 0.7)',
          borderColor: '#2563eb',
          borderWidth: 1.5,
          borderRadius: 4,
          yAxisID: 'y'
        },
        {
          label: 'Revenue ($)',
          data: monthlyRevenues,
          type: 'line',
          borderColor: '#10b981',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          borderWidth: 2.5,
          pointBackgroundColor: '#10b981',
          pointRadius: 4,
          tension: 0.3,
          yAxisID: 'y1'
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      scales: {
        y: {
          type: 'linear',
          display: true,
          position: 'left',
          title: { display: true, text: 'Applications Count' },
          grid: { color: 'rgba(0,0,0,0.04)' }
        },
        y1: {
          type: 'linear',
          display: true,
          position: 'right',
          title: { display: true, text: 'Revenue ($)' },
          grid: { drawOnChartArea: false }
        }
      },
      plugins: {
        legend: { position: 'top' },
        tooltip: {
          callbacks: {
            label: function(context) {
              if (context.dataset.label.includes('Revenue')) {
                return ' ' + context.dataset.label + ': $' + context.raw.toLocaleString(undefined, {minimumFractionDigits: 2});
              }
              return ' ' + context.dataset.label + ': ' + context.raw;
            }
          }
        }
      }
    }
  });

  // 2. Status Distribution Chart (Pie & Doughnut)
  const statusRaw = <?= json_encode($statusDist ?? []) ?>;
  const statusLabels = statusRaw.length ? statusRaw.map(r => r.status) : ['No Data'];
  const statusCounts = statusRaw.length ? statusRaw.map(r => parseInt(r.count || 0)) : [1];

  const ctxStatus = document.getElementById('statusDistChart').getContext('2d');
  window.statusChartInstance = new Chart(ctxStatus, {
    type: 'doughnut',
    data: {
      labels: statusLabels,
      datasets: [{
        data: statusCounts,
        backgroundColor: chartPalette.slice(0, statusLabels.length),
        borderWidth: 2,
        borderColor: '#ffffff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom' },
        tooltip: {
          callbacks: {
            label: function(context) {
              return ' ' + context.label + ': ' + context.raw + ' cases';
            }
          }
        }
      },
      cutout: '60%'
    }
  });

  // 3. Yearly Trend Chart (Bar Multi-Year)
  const yearlyRaw = <?= json_encode($yearlyTrend ?? []) ?>;
  const yearlyLabels = yearlyRaw.length ? yearlyRaw.map(r => String(r.period)) : [new Date().getFullYear().toString()];
  const yearlyCounts = yearlyRaw.length ? yearlyRaw.map(r => parseInt(r.app_count || 0)) : [<?= (int)($kpiSummary['total_apps'] ?? 0) ?>];
  const yearlyRevenues = yearlyRaw.length ? yearlyRaw.map(r => parseFloat(r.revenue || 0)) : [<?= (float)($kpiSummary['total_revenue'] ?? 0) ?>];

  const ctxYearly = document.getElementById('yearlyTrendChart').getContext('2d');
  window.yearlyChartInstance = new Chart(ctxYearly, {
    type: 'bar',
    data: {
      labels: yearlyLabels,
      datasets: [
        {
          label: 'Total Cases',
          data: yearlyCounts,
          backgroundColor: 'rgba(6, 182, 212, 0.75)',
          borderColor: '#06b6d4',
          borderWidth: 1.5,
          borderRadius: 4
        },
        {
          label: 'Total Revenue ($)',
          data: yearlyRevenues,
          backgroundColor: 'rgba(99, 102, 241, 0.75)',
          borderColor: '#6366f1',
          borderWidth: 1.5,
          borderRadius: 4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(0,0,0,0.04)' }
        }
      },
      plugins: {
        legend: { position: 'top' }
      }
    }
  });

  // 4. Destination Countries Breakdown (Horizontal Bar & Polar)
  const countryRaw = <?= json_encode($countryDist ?? []) ?>;
  const countryLabels = countryRaw.length ? countryRaw.map(r => r.country_name) : ['No Data'];
  const countryCounts = countryRaw.length ? countryRaw.map(r => parseInt(r.count || 0)) : [0];

  const ctxCountry = document.getElementById('countryDistChart').getContext('2d');
  window.countryChartInstance = new Chart(ctxCountry, {
    type: 'bar',
    data: {
      labels: countryLabels,
      datasets: [{
        label: 'Applications Volume',
        data: countryCounts,
        backgroundColor: 'rgba(245, 158, 11, 0.8)',
        borderColor: '#f59e0b',
        borderWidth: 1,
        borderRadius: 4
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } }
      },
      plugins: {
        legend: { display: false }
      }
    }
  });
});

// Interactive Chart Type Switchers
function switchMonthlyChart(type) {
  document.getElementById('btnMonthlyBar').classList.toggle('active', type === 'bar');
  document.getElementById('btnMonthlyLine').classList.toggle('active', type === 'line');
  
  if (window.monthlyChartInstance) {
    if (type === 'line') {
      window.monthlyChartInstance.config.data.datasets[0].type = 'line';
      window.monthlyChartInstance.config.data.datasets[0].tension = 0.3;
      window.monthlyChartInstance.config.data.datasets[0].fill = true;
      window.monthlyChartInstance.config.data.datasets[0].backgroundColor = 'rgba(37, 99, 235, 0.1)';
    } else {
      window.monthlyChartInstance.config.data.datasets[0].type = 'bar';
      window.monthlyChartInstance.config.data.datasets[0].backgroundColor = 'rgba(37, 99, 235, 0.7)';
    }
    window.monthlyChartInstance.update();
  }
}

function switchStatusChart(type) {
  document.getElementById('btnStatusDoughnut').classList.toggle('active', type === 'doughnut');
  document.getElementById('btnStatusPie').classList.toggle('active', type === 'pie');
  
  if (window.statusChartInstance) {
    window.statusChartInstance.config.type = type;
    window.statusChartInstance.config.options.cutout = (type === 'doughnut') ? '60%' : '0%';
    window.statusChartInstance.update();
  }
}

function switchCountryChart(type) {
  document.getElementById('btnCountryBar').classList.toggle('active', type === 'bar');
  document.getElementById('btnCountryPolar').classList.toggle('active', type === 'polarArea');
  
  if (window.countryChartInstance) {
    const data = window.countryChartInstance.config.data;
    const oldCtx = document.getElementById('countryDistChart').getContext('2d');
    window.countryChartInstance.destroy();
    
    if (type === 'polarArea') {
      window.countryChartInstance = new Chart(oldCtx, {
        type: 'polarArea',
        data: {
          labels: data.labels,
          datasets: [{
            data: data.datasets[0].data,
            backgroundColor: [
              'rgba(37, 99, 235, 0.7)',
              'rgba(16, 185, 129, 0.7)',
              'rgba(245, 158, 11, 0.7)',
              'rgba(239, 68, 68, 0.7)',
              'rgba(139, 92, 246, 0.7)',
              'rgba(6, 182, 212, 0.7)'
            ]
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: 'bottom' } }
        }
      });
    } else {
      window.countryChartInstance = new Chart(oldCtx, {
        type: 'bar',
        data: {
          labels: data.labels,
          datasets: [{
            label: 'Applications Volume',
            data: data.datasets[0].data,
            backgroundColor: 'rgba(245, 158, 11, 0.8)',
            borderColor: '#f59e0b',
            borderWidth: 1,
            borderRadius: 4
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          scales: { x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } } },
          plugins: { legend: { display: false } }
        }
      });
    }
  }
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
