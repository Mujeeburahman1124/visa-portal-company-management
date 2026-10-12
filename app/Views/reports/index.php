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

<!-- Link Dedicated Bento CSS -->
<link rel="stylesheet" href="/assets/css/dashboard-bento.css">
<link rel="stylesheet" href="/assets/css/pages/reports-index.css">

<!-- Include Chart.js UMD -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

<div class="content-body reports-dashboard">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Bento Hero Header -->
  <div class="reports-hero">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
          <h1 class="reports-hero-title mb-0">
            <i class="fa-solid fa-chart-pie text-danger"></i> Operational Intelligence &amp; Analytics
          </h1>
          <span class="reports-hero-badge">
            <i class="fa-solid fa-bolt me-1"></i> Real-Time Live Feed
          </span>
        </div>
        <p class="reports-hero-desc">
          Executive telemetry, multi-dimensional visa funnel analytics, gross margins, workload attribution, and real-time statutory metrics.
        </p>
      </div>

      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="/reports?type=<?= urlencode($reportType) ?>&preset=<?= urlencode($activePreset) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&export=csv" class="btn btn-outline-light btn-sm px-3 shadow-sm fw-semibold rounded-pill">
          <i class="fa-solid fa-file-csv me-1.5 text-success"></i> Export Report (CSV)
        </a>
      </div>
    </div>
  </div>

  <!-- Quick Date Presets Bar -->
  <div class="preset-strip-card">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div class="d-flex flex-wrap align-items-center gap-1.5">
        <span class="small fw-bold text-muted me-1"><i class="fa-solid fa-clock-rotate-left me-1 text-danger"></i> Quick Presets:</span>
        <a href="/reports?type=<?= urlencode($reportType) ?>&preset=today" class="preset-pill-btn <?= $activePreset === 'today' ? 'active' : '' ?>">
          Today
        </a>
        <a href="/reports?type=<?= urlencode($reportType) ?>&preset=this_month" class="preset-pill-btn <?= $activePreset === 'this_month' ? 'active' : '' ?>">
          This Month
        </a>
        <a href="/reports?type=<?= urlencode($reportType) ?>&preset=last_month" class="preset-pill-btn <?= $activePreset === 'last_month' ? 'active' : '' ?>">
          Last Month
        </a>
        <a href="/reports?type=<?= urlencode($reportType) ?>&preset=this_year" class="preset-pill-btn <?= $activePreset === 'this_year' ? 'active' : '' ?>">
          This Year
        </a>
        <a href="/reports?type=<?= urlencode($reportType) ?>&preset=last_year" class="preset-pill-btn <?= $activePreset === 'last_year' ? 'active' : '' ?>">
          Last Year
        </a>
        <a href="/reports?type=<?= urlencode($reportType) ?>&preset=all_time" class="preset-pill-btn <?= $activePreset === 'all_time' ? 'active' : '' ?>">
          All Time
        </a>
      </div>

      <div class="text-muted small fw-semibold">
        <i class="fa-regular fa-calendar-check me-1 text-danger"></i> <strong>Period:</strong> <?= date('M d, Y', strtotime($dateFrom)) ?> &mdash; <?= date('M d, Y', strtotime($dateTo)) ?>
      </div>
    </div>
  </div>

  <!-- Bento KPI Metrics Strip -->
  <div class="bento-kpi-grid">
    <!-- Card 1: Total Volume -->
    <div class="bento-kpi-card" style="--kpi-accent: #E11D48; --kpi-icon-bg: #FFF1F2; --kpi-icon-color: #E11D48;">
      <div class="bento-kpi-header">
        <span class="bento-kpi-title">Total Applications</span>
        <div class="bento-kpi-icon">
          <i class="fa-solid fa-folder-open"></i>
        </div>
      </div>
      <div class="bento-kpi-value"><?= number_format($kpiSummary['total_apps'] ?? 0) ?></div>
      <div class="bento-kpi-footer">
        <i class="fa-solid fa-layer-group text-danger"></i>
        <span>Recorded within selected window</span>
      </div>
    </div>

    <!-- Card 2: Total Revenue -->
    <div class="bento-kpi-card" style="--kpi-accent: #10B981; --kpi-icon-bg: #ECFDF5; --kpi-icon-color: #059669;">
      <div class="bento-kpi-header">
        <span class="bento-kpi-title">Gross Revenue</span>
        <div class="bento-kpi-icon">
          <i class="fa-solid fa-coins"></i>
        </div>
      </div>
      <div class="bento-kpi-value text-success"><?= format_currency((float)($kpiSummary['total_revenue'] ?? 0.0)) ?></div>
      <div class="bento-kpi-footer">
        <span class="text-success fw-bold"><i class="fa-solid fa-money-bill-transfer me-1"></i> Paid: <?= format_currency((float)($kpiSummary['total_paid'] ?? 0.0)) ?></span>
        <span class="ms-auto text-muted">Bal: <?= format_currency((float)($kpiSummary['total_outstanding'] ?? 0.0)) ?></span>
      </div>
    </div>

    <!-- Card 3: Gross Profit -->
    <div class="bento-kpi-card" style="--kpi-accent: #0284C7; --kpi-icon-bg: #F0F9FF; --kpi-icon-color: #0284C7;">
      <div class="bento-kpi-header">
        <span class="bento-kpi-title">Gross Profit</span>
        <div class="bento-kpi-icon">
          <i class="fa-solid fa-chart-line"></i>
        </div>
      </div>
      <div class="bento-kpi-value text-primary"><?= format_currency((float)($kpiSummary['total_profit'] ?? 0.0)) ?></div>
      <div class="bento-kpi-footer">
        <span class="text-muted"><i class="fa-solid fa-hand-holding-dollar me-1 text-secondary"></i> Direct Cost: <?= format_currency((float)($kpiSummary['total_cost'] ?? 0.0)) ?></span>
      </div>
    </div>

    <!-- Card 4: Approval Rate -->
    <div class="bento-kpi-card" style="--kpi-accent: #8B5CF6; --kpi-icon-bg: #F5F3FF; --kpi-icon-color: #7C3AED;">
      <div class="bento-kpi-header">
        <span class="bento-kpi-title">Success Rate</span>
        <div class="bento-kpi-icon">
          <i class="fa-solid fa-circle-check"></i>
        </div>
      </div>
      <div class="bento-kpi-value" style="color: #7C3AED;"><?= $kpiSummary['approval_rate'] ?? 0 ?>%</div>
      <div class="bento-kpi-footer">
        <span class="text-success fw-bold"><i class="fa-solid fa-check me-1"></i><?= number_format($kpiSummary['approved_count'] ?? 0) ?> Approved</span>
        <span class="text-danger fw-bold ms-auto"><i class="fa-solid fa-xmark me-1"></i><?= number_format($kpiSummary['rejected_count'] ?? 0) ?> Rejected</span>
      </div>
    </div>
  </div>

  <!-- VISUAL CHARTS BENTO GRID -->
  <div class="row g-4 mb-4">
    <!-- Chart 1: Monthly Performance (Bar & Line Chart) -->
    <div class="col-12 col-xl-7">
      <div class="bento-chart-card">
        <div class="bento-chart-header">
          <div>
            <h2 class="bento-chart-title">
              <i class="fa-solid fa-chart-column text-danger"></i> Monthly Volume &amp; Revenue Trend
            </h2>
            <span class="bento-chart-subtitle">Real-time month-over-month visa applications volume and revenue performance</span>
          </div>
          <div class="chart-toggle-group" role="group">
            <button type="button" class="chart-toggle-btn active" id="btnMonthlyBar" onclick="switchMonthlyChart('bar')">
              <i class="fa-solid fa-chart-column"></i> Bar
            </button>
            <button type="button" class="chart-toggle-btn" id="btnMonthlyLine" onclick="switchMonthlyChart('line')">
              <i class="fa-solid fa-chart-line"></i> Line
            </button>
          </div>
        </div>
        <div class="bento-chart-body">
          <canvas id="monthlyTrendChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Chart 2: Status & Stage Slices (Pie & Doughnut Charts) -->
    <div class="col-12 col-xl-5">
      <div class="bento-chart-card">
        <div class="bento-chart-header">
          <div>
            <h2 class="bento-chart-title">
              <i class="fa-solid fa-chart-pie text-success"></i> Status Distribution
            </h2>
            <span class="bento-chart-subtitle">Proportion of approved, pending, and in-progress files</span>
          </div>
          <div class="chart-toggle-group" role="group">
            <button type="button" class="chart-toggle-btn active" id="btnStatusDoughnut" onclick="switchStatusChart('doughnut')">
              <i class="fa-solid fa-circle-notch"></i> Doughnut
            </button>
            <button type="button" class="chart-toggle-btn" id="btnStatusPie" onclick="switchStatusChart('pie')">
              <i class="fa-solid fa-chart-pie"></i> Pie
            </button>
          </div>
        </div>
        <div class="bento-chart-body d-flex flex-column align-items-center justify-content-center">
          <canvas id="statusDistChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Chart 3: Multi-Year Comparison (Bar Chart by Years) -->
    <div class="col-12 col-xl-6">
      <div class="bento-chart-card">
        <div class="bento-chart-header">
          <div>
            <h2 class="bento-chart-title">
              <i class="fa-solid fa-calendar-days text-primary"></i> Multi-Year Performance Comparison
            </h2>
            <span class="bento-chart-subtitle">Historical application throughput and gross turnover year-by-year</span>
          </div>
          <span class="badge bg-light text-secondary border">Annual Summary</span>
        </div>
        <div class="bento-chart-body">
          <canvas id="yearlyTrendChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Chart 4: Top Destination Countries Breakdown (Horizontal Bar & Polar) -->
    <div class="col-12 col-xl-6">
      <div class="bento-chart-card">
        <div class="bento-chart-header">
          <div>
            <h2 class="bento-chart-title">
              <i class="fa-solid fa-earth-americas text-warning"></i> Destination Countries Breakdown
            </h2>
            <span class="bento-chart-subtitle">Application volume distributed by destination country</span>
          </div>
          <div class="chart-toggle-group" role="group">
            <button type="button" class="chart-toggle-btn active" id="btnCountryBar" onclick="switchCountryChart('bar')">
              <i class="fa-solid fa-bars"></i> Bar
            </button>
            <button type="button" class="chart-toggle-btn" id="btnCountryPolar" onclick="switchCountryChart('polarArea')">
              <i class="fa-solid fa-asterisk"></i> Polar
            </button>
          </div>
        </div>
        <div class="bento-chart-body">
          <canvas id="countryDistChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Detailed Filter & Report Selector Panel -->
  <div class="bento-filter-card">
    <div class="bento-filter-title">
      <i class="fa-solid fa-filter text-danger"></i> Custom Date-by-Date &amp; Report Type Filter
    </div>
    <form action="/reports" method="GET" class="row g-3 align-items-end">
      <input type="hidden" name="preset" value="custom">

      <div class="col-12 col-md-4">
        <label class="form-label small fw-bold text-dark mb-1">Detailed Report Catalog</label>
        <select name="type" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()">
          <?php foreach ($reportsCatalog as $key => $name): ?>
            <option value="<?= $key ?>" <?= $reportType === $key ? 'selected' : '' ?>><?= $name ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-6 col-md-3">
        <label class="form-label small fw-bold text-dark mb-1">Date From</label>
        <input type="date" name="date_from" class="form-control form-control-sm rounded-pill" value="<?= e($dateFrom) ?>">
      </div>

      <div class="col-6 col-md-3">
        <label class="form-label small fw-bold text-dark mb-1">Date To</label>
        <input type="date" name="date_to" class="form-control form-control-sm rounded-pill" value="<?= e($dateTo) ?>">
      </div>

      <div class="col-12 col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm flex-grow-1 fw-bold rounded-pill shadow-sm" title="Generate Report">
          <i class="fa-solid fa-magnifying-glass me-1"></i> Generate
        </button>
        <a href="/reports" class="btn btn-light btn-sm border rounded-pill px-3" title="Reset Filters">
          <i class="fa-solid fa-rotate-left"></i>
        </a>
      </div>
    </form>
  </div>

  <!-- Report Data Table Bento Card -->
  <div class="bento-table-card">
    <div class="bento-table-header">
      <div>
        <span class="fw-bold fs-6 text-dark"><i class="fa-solid fa-table-list text-danger me-2"></i><?= e($title) ?></span>
      </div>
      <div>
        <span class="badge bg-light text-dark border fw-semibold px-2.5 py-1.5 rounded-pill"><?= count($data) ?> Records Loaded</span>
      </div>
    </div>
    <div class="table-responsive mb-0">
      <table class="bento-report-table align-middle">
        <thead>
          <tr>
            <?php foreach ($columns as $col): ?>
              <th><?= e($col) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($data)): ?>
            <tr>
              <td colspan="<?= count($columns) ?>" class="text-center py-5 text-muted">
                <i class="fa-solid fa-inbox fa-2x mb-2 text-secondary opacity-50 d-block"></i>
                No records found matching the selected dates and parameters.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($data as $row): ?>
              <tr>
                <?php for ($i = 1; $i <= count($columns); $i++): 
                  $colName = $columns[$i - 1];
                  $val = $row["col_{$i}"] ?? '';
                ?>
                  <td data-label="<?= e($colName) ?>">
                    <?php 
                      // Format currency if column indicates monetary amount
                      if (str_contains($colName, '($)') || str_contains($colName, 'Price') || str_contains($colName, 'Revenue') || str_contains($colName, 'Profit') || str_contains($colName, 'Paid') || str_contains($colName, 'Outstanding') || str_contains($colName, 'Cost') || str_contains($colName, 'Fee')) {
                        echo is_numeric($val) ? '<span class="fw-bold text-dark font-monospace">' . format_currency((float)$val) . '</span>' : e((string)$val);
                      } elseif (str_contains($colName, 'Status') || str_contains($colName, 'Stage')) {
                        $badgeCls = match($val) {
                          'Approved', 'Completed' => 'badge-bento-success',
                          'In Progress', 'Submitted', 'Under Review' => 'badge-bento-primary',
                          'Rejected', 'EXPIRED', 'Critical (< 30 Days)' => 'badge-bento-danger',
                          'Warning (< 60 Days)', 'Expiring Soon' => 'badge-bento-warning',
                          default => 'badge-bento-neutral'
                        };
                        echo '<span class="badge-bento ' . $badgeCls . '">' . e((string)$val) . '</span>';
                      } elseif (str_contains($colName, 'Application #')) {
                        echo '<span class="font-monospace fw-bold text-dark">' . e((string)$val) . '</span>';
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
  const chartPalette = [
    '#E11D48', '#10B981', '#F59E0B', '#0284C7', '#8B5CF6',
    '#06B6D4', '#EC4899', '#14B8A6', '#F97316', '#64748B'
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
          backgroundColor: 'rgba(225, 29, 72, 0.75)',
          borderColor: '#E11D48',
          borderWidth: 1.5,
          borderRadius: 6,
          yAxisID: 'y'
        },
        {
          label: 'Revenue ($)',
          data: monthlyRevenues,
          type: 'line',
          borderColor: '#10B981',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          borderWidth: 2.5,
          pointBackgroundColor: '#10B981',
          pointRadius: 4,
          tension: 0.35,
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
      cutout: '62%'
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
          backgroundColor: 'rgba(2, 132, 199, 0.75)',
          borderColor: '#0284C7',
          borderWidth: 1.5,
          borderRadius: 6
        },
        {
          label: 'Total Revenue ($)',
          data: yearlyRevenues,
          backgroundColor: 'rgba(139, 92, 246, 0.75)',
          borderColor: '#8B5CF6',
          borderWidth: 1.5,
          borderRadius: 6
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
        borderColor: '#F59E0B',
        borderWidth: 1,
        borderRadius: 6
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
      window.monthlyChartInstance.config.data.datasets[0].tension = 0.35;
      window.monthlyChartInstance.config.data.datasets[0].fill = true;
      window.monthlyChartInstance.config.data.datasets[0].backgroundColor = 'rgba(225, 29, 72, 0.1)';
    } else {
      window.monthlyChartInstance.config.data.datasets[0].type = 'bar';
      window.monthlyChartInstance.config.data.datasets[0].backgroundColor = 'rgba(225, 29, 72, 0.75)';
    }
    window.monthlyChartInstance.update();
  }
}

function switchStatusChart(type) {
  document.getElementById('btnStatusDoughnut').classList.toggle('active', type === 'doughnut');
  document.getElementById('btnStatusPie').classList.toggle('active', type === 'pie');
  
  if (window.statusChartInstance) {
    window.statusChartInstance.config.type = type;
    window.statusChartInstance.config.options.cutout = (type === 'doughnut') ? '62%' : '0%';
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
              'rgba(225, 29, 72, 0.75)',
              'rgba(16, 185, 129, 0.75)',
              'rgba(245, 158, 11, 0.75)',
              'rgba(2, 132, 199, 0.75)',
              'rgba(139, 92, 246, 0.75)',
              'rgba(6, 182, 212, 0.75)'
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
            borderColor: '#F59E0B',
            borderWidth: 1,
            borderRadius: 6
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
