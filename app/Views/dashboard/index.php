<?php
$pageTitle = 'Operations Dashboard — VISA TRACK';
$flash = get_flash();
$currentUser = auth_user();
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

  <!-- Dashboard Colorful Hero Banner -->
  <div class="dashboard-hero-banner d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div class="position-relative" style="z-index: 2;">
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="pulse-dot"></span>
        <span class="badge bg-white bg-opacity-20 text-white fw-bold px-2 py-1" style="font-size: 0.7rem; letter-spacing: 0.06em; backdrop-filter: blur(4px);">REAL-TIME SYNC ACTIVE</span>
        <span class="text-white-50 small d-none d-sm-inline">&bull; <?= date('l, F j, Y') ?></span>
      </div>
      <h2 class="fw-bold brand-font text-white mb-1" style="font-size: 1.75rem; letter-spacing: -0.02em;">
        Good <?= (date('H') < 12) ? 'morning' : ((date('H') < 17) ? 'afternoon' : 'evening') ?>, <?= e(explode(' ', $currentUser['name'] ?? 'Staff')[0]) ?> 👋
      </h2>
      <p class="text-white-50 small mb-0">Unified command center for global visa operations, bottlenecks &amp; compliance SLAs.</p>
    </div>
    <div class="d-flex align-items-center gap-2 position-relative" style="z-index: 2;">
      <a href="/tracking" class="btn btn-light btn-sm px-3 shadow fw-semibold text-primary">
        <i class="fa-solid fa-route text-primary me-1"></i> Visual Tracking Hub
      </a>
      <a href="/applications/create" class="btn btn-primary btn-sm px-3 shadow fw-semibold" style="background: var(--ms-gradient-ruby); border: 1px solid rgba(255,255,255,0.25);">
        <i class="fa-solid fa-plus me-1"></i> New Application
      </a>
    </div>
  </div>

  <!-- Operational Expiry & Compliance Alerts Bar (Section 2) -->
  <div class="row g-2 mb-3">
    <div class="col-12">
      <div class="live-alerts-bar">
        <div class="d-flex flex-wrap align-items-center gap-2">
          <span class="live-alerts-header">
            <i class="fa-solid fa-bell"></i> Live Alerts:
          </span>
          <a href="/documents?expiry_filter=30" class="alert-pill alert-pill-amber">
            <i class="fa-solid fa-passport"></i> Passports Expiring (90d):
            <span class="count-badge"><?= $alerts['expiring_passports'] ?></span>
          </a>
          <a href="/customers" class="alert-pill alert-pill-cyan">
            <i class="fa-solid fa-id-card"></i> National ID Expiring:
            <span class="count-badge"><?= $alerts['expiring_national_ids'] ?></span>
          </a>
          <a href="/applications" class="alert-pill alert-pill-indigo">
            <i class="fa-solid fa-house-user"></i> Residency Expiring:
            <span class="count-badge"><?= $alerts['expiring_residences'] ?></span>
          </a>
          <a href="/tasks" class="alert-pill alert-pill-rose">
            <i class="fa-solid fa-clock-rotate-left"></i> Overdue Tasks:
            <span class="count-badge"><?= $alerts['overdue_tasks'] ?></span>
          </a>
          <a href="/payments" class="alert-pill alert-pill-ruby">
            <i class="fa-solid fa-receipt"></i> Outstanding Balances:
            <span class="count-badge"><?= $alerts['unpaid_applications'] ?></span>
          </a>
        </div>
        <div class="date-summary-group">
          <span class="date-summary-pill">Today: <strong><?= $kpi['today'] ?></strong></span>
          <span class="date-summary-pill">This Week: <strong><?= $kpi['week'] ?></strong></span>
          <span class="date-summary-pill">This Month: <strong><?= $kpi['month'] ?></strong></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Primary Operational KPI Cards (6 Vibrant Grid) -->
  <div class="row g-2 g-md-3 mb-4">
    <!-- Total Applications -->
    <div class="col-6 col-md-4 col-xl-2">
      <a href="/applications" class="stat-card stat-card-blue">
        <div class="stat-icon-wrapper">
          <i class="fa-solid fa-folder-open"></i>
        </div>
        <div class="stat-card-content">
          <div class="stat-title">Total Files</div>
          <div class="stat-value"><?= number_format($kpi['total']) ?></div>
          <div class="stat-trend"><i class="fa-solid fa-layer-group me-1"></i>All files</div>
        </div>
      </a>
    </div>

    <!-- Active In Progress -->
    <div class="col-6 col-md-4 col-xl-2">
      <a href="/applications?quick_tab=in_progress" class="stat-card stat-card-cyan">
        <div class="stat-icon-wrapper">
          <i class="fa-solid fa-arrows-rotate fa-spin-pulse"></i>
        </div>
        <div class="stat-card-content">
          <div class="stat-title">In Progress</div>
          <div class="stat-value"><?= number_format($kpi['active']) ?></div>
          <div class="stat-trend"><i class="fa-solid fa-clock me-1"></i>Active queue</div>
        </div>
      </a>
    </div>

    <!-- Action Required -->
    <div class="col-6 col-md-4 col-xl-2">
      <a href="/action-center" class="stat-card stat-card-danger">
        <div class="stat-icon-wrapper">
          <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div class="stat-card-content">
          <div class="stat-title">Action Needed</div>
          <div class="stat-value"><?= number_format($kpi['action_required']) ?></div>
          <div class="stat-trend"><i class="fa-solid fa-bolt me-1"></i>Attention</div>
        </div>
      </a>
    </div>

    <!-- Approved / Completed -->
    <div class="col-6 col-md-4 col-xl-2">
      <a href="/applications?quick_tab=approved" class="stat-card stat-card-success">
        <div class="stat-icon-wrapper">
          <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="stat-card-content">
          <div class="stat-title">Approved</div>
          <div class="stat-value"><?= number_format($kpi['completed']) ?></div>
          <div class="stat-trend"><i class="fa-solid fa-check me-1"></i>Issued</div>
        </div>
      </a>
    </div>

    <!-- Overdue Cases -->
    <div class="col-6 col-md-4 col-xl-2">
      <a href="/applications?quick_tab=urgent" class="stat-card stat-card-purple">
        <div class="stat-icon-wrapper">
          <i class="fa-solid fa-hourglass-end"></i>
        </div>
        <div class="stat-card-content">
          <div class="stat-title">Overdue SLA</div>
          <div class="stat-value"><?= number_format($kpi['overdue']) ?></div>
          <div class="stat-trend"><i class="fa-solid fa-fire me-1"></i>Breached</div>
        </div>
      </a>
    </div>

    <!-- Expiring Passports & Documents -->
    <div class="col-6 col-md-4 col-xl-2">
      <a href="/documents?expiry_filter=30" class="stat-card stat-card-warning">
        <div class="stat-icon-wrapper">
          <i class="fa-solid fa-id-card-clip"></i>
        </div>
        <div class="stat-card-content">
          <div class="stat-title">Expiring Docs</div>
          <div class="stat-value"><?= number_format($kpi['expiring_passports']) ?></div>
          <div class="stat-trend"><i class="fa-solid fa-clock-rotate-left me-1"></i>&le; 30 Days</div>
        </div>
      </a>
    </div>
  </div>

  <!-- Action Center Highlight & Bottleneck Queue -->
  <div class="card card-enterprise mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-danger fw-bold px-2 py-1"><i class="fa-solid fa-bolt me-1"></i>ACTION CENTER</span>
        <span class="fw-bold text-dark small text-uppercase">Items Requiring Operational Attention</span>
      </div>
      <a href="/action-center" class="btn btn-outline-danger btn-sm py-0 px-2 fw-semibold" style="font-size: 0.78rem;">
        View All Actions &rarr;
      </a>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table-modern mb-0">
          <thead>
            <tr>
              <th>Application #</th>
              <th>Applicant</th>
              <th>Visa Service</th>
              <th>Current Stage</th>
              <th>Health</th>
              <th>Priority</th>
              <th>Assigned Staff</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($urgentApplications)): ?>
              <tr>
                <td colspan="8">
                  <div class="empty-state py-4">
                    <div class="empty-state-icon" style="width: 48px; height: 48px; font-size: 1.25rem;">
                      <i class="fa-solid fa-circle-check text-success"></i>
                    </div>
                    <div class="empty-state-title fs-6">No pending action bottlenecks</div>
                    <div class="empty-state-text small mb-0">All applications are advancing on schedule according to SLA deadlines.</div>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($urgentApplications as $uApp): ?>
                <tr>
                  <td>
                    <a href="/applications/show?id=<?= $uApp['id'] ?>" class="badge bg-primary-subtle text-primary fw-bold text-decoration-none px-2 py-1" style="font-size: 0.82rem; border: 1px solid var(--vt-primary-border);">
                      <i class="fa-solid fa-folder me-1"></i><?= e($uApp['application_number']) ?>
                    </a>
                  </td>
                  <td>
                    <div class="fw-bold text-dark"><?= e($uApp['customer_name']) ?></div>
                    <div class="text-muted small" style="font-size: 0.72rem;"><i class="fa-solid fa-phone me-1 text-primary small"></i><?= e($uApp['mobile']) ?></div>
                  </td>
                  <td>
                    <span class="small fw-semibold text-dark"><?= e($uApp['service_name']) ?></span>
                  </td>
                  <td>
                    <span class="badge bg-warning-subtle text-warning-text fw-bold px-2 py-1" style="font-size: 0.73rem; border: 1px solid var(--vt-warning-border);">
                      <i class="fa-solid fa-clock-rotate-left me-1"></i><?= e($uApp['current_stage']) ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge <?= (int)$uApp['calculated_health'] < 50 ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning' ?> fw-bold px-2 py-1" style="font-size: 0.75rem;">
                      <i class="fa-solid fa-heart-pulse me-1"></i><?= (int)$uApp['calculated_health'] ?>%
                    </span>
                  </td>
                  <td>
                    <?php 
                      $prio = strtolower($uApp['priority']);
                      $prioClass = ($prio === 'urgent' || $prio === 'critical') ? 'badge-priority-critical' : (($prio === 'high') ? 'badge-priority-urgent' : 'badge-priority-normal');
                    ?>
                    <span class="badge <?= $prioClass ?>">
                      <i class="fa-solid <?= $prio === 'critical' ? 'fa-fire' : ($prio === 'urgent' ? 'fa-bolt' : 'fa-circle-info') ?> me-1"></i><?= e($uApp['priority']) ?>
                    </span>
                  </td>
                  <td>
                    <span class="small text-secondary fw-medium"><i class="fa-solid fa-user-circle me-1 text-muted"></i><?= e($uApp['staff_name'] ?? 'Unassigned') ?></span>
                  </td>
                  <td class="text-end">
                    <a href="/applications/show?id=<?= $uApp['id'] ?>" class="btn btn-sm btn-primary py-1 px-3" style="font-size: 0.78rem;">
                      Resolve <i class="fa-solid fa-arrow-right ms-1"></i>
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

  <!-- Operational Insights Grid (Stages Breakdown & Financial Strip) -->
  <div class="row g-4 mb-4">
    <!-- Applications by Stage -->
    <div class="col-lg-6">
      <div class="card card-enterprise h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
          <span class="fw-bold small text-uppercase text-secondary"><i class="fa-solid fa-bars-progress text-primary me-2"></i> Active Stage Breakdown</span>
          <a href="/tracking" class="btn btn-link btn-sm text-decoration-none p-0" style="font-size: 0.78rem;">Open Timeline &rarr;</a>
        </div>
        <div class="card-body">
          <?php foreach ($stages as $s): ?>
            <?php 
              $pct = $kpi['total'] > 0 ? round(($s['count'] / $kpi['total']) * 100) : 0;
            ?>
            <div class="mb-3">
              <div class="d-flex justify-content-between small mb-1">
                <span class="fw-semibold text-dark"><?= e($s['current_stage']) ?></span>
                <span class="text-muted fw-bold"><?= $s['count'] ?> cases (<?= $pct ?>%)</span>
              </div>
              <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-primary" style="width: <?= $pct ?>%"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Top Destination Countries & Finance -->
    <div class="col-lg-6">
      <div class="card card-enterprise h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
          <span class="fw-bold small text-uppercase text-secondary"><i class="fa-solid fa-earth-americas text-info me-2"></i> Destinations &amp; Financials</span>
          <span class="badge bg-light text-secondary border">Volume</span>
        </div>
        <div class="card-body">
          <div class="row g-2 mb-3">
            <?php foreach ($countries as $c): ?>
              <div class="col-6">
                <div class="p-2 border rounded bg-light d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center gap-2">
                    <span class="fs-5"><?= $c['flag_emoji'] ?></span>
                    <span class="small fw-semibold text-truncate" style="max-width: 110px;"><?= e($c['country_name']) ?></span>
                  </div>
                  <span class="badge bg-primary rounded-pill"><?= $c['count'] ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Financial Snapshot -->
          <div class="pt-3 border-top">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="small fw-bold text-uppercase text-muted" style="font-size: 0.7rem;">Financial Snapshot</span>
              <a href="/payments" class="small text-decoration-none fw-semibold" style="font-size: 0.75rem;">Invoices &rarr;</a>
            </div>
            <div class="row g-2 text-center">
              <div class="col-4">
                <div class="p-2 bg-light rounded border">
                  <div class="text-muted" style="font-size: 0.68rem; font-weight: 700;">TOTAL REVENUE</div>
                  <div class="fw-bold text-dark fs-6"><?= format_currency($finance['total_sales']) ?></div>
                </div>
              </div>
              <div class="col-4">
                <div class="p-2 bg-success bg-opacity-10 rounded border border-success border-opacity-25">
                  <div class="text-success" style="font-size: 0.68rem; font-weight: 700;">COLLECTED</div>
                  <div class="fw-bold text-success fs-6"><?= format_currency($finance['total_received']) ?></div>
                </div>
              </div>
              <div class="col-4">
                <div class="p-2 bg-danger bg-opacity-10 rounded border border-danger border-opacity-25">
                  <div class="text-danger" style="font-size: 0.68rem; font-weight: 700;">OUTSTANDING</div>
                  <div class="fw-bold text-danger fs-6"><?= format_currency($finance['outstanding']) ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Staff Workload & Activity Trail -->
  <div class="row g-4">
    <!-- Staff Workload -->
    <div class="col-lg-6">
      <div class="card card-enterprise h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
          <span class="fw-bold small text-uppercase text-secondary"><i class="fa-solid fa-users text-primary me-2"></i> Staff Workload</span>
          <a href="/staff" class="btn btn-link btn-sm text-decoration-none p-0" style="font-size: 0.78rem;">Manage Team &rarr;</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table-modern mb-0">
              <thead>
                <tr>
                  <th>Officer</th>
                  <th>Role</th>
                  <th class="text-center">Active</th>
                  <th class="text-center">Urgent</th>
                  <th class="text-center">Tasks</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($staffWorkload as $sw): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold text-dark"><?= e($sw['name']) ?></div>
                      <div class="text-muted" style="font-size: 0.7rem;"><?= e($sw['designation']) ?></div>
                    </td>
                    <td><span class="badge bg-light text-secondary border"><?= e($sw['role_name']) ?></span></td>
                    <td class="text-center"><span class="badge bg-primary-subtle text-primary fw-bold"><?= $sw['active_cases'] ?></span></td>
                    <td class="text-center">
                      <?php if ($sw['urgent_cases'] > 0): ?>
                        <span class="badge bg-danger"><?= $sw['urgent_cases'] ?></span>
                      <?php else: ?>
                        <span class="text-muted small">0</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center"><span class="badge bg-secondary-subtle text-secondary"><?= $sw['pending_tasks'] ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Audit Activity -->
    <div class="col-lg-6">
      <div class="card card-enterprise shadow-sm border h-100">
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4 d-flex align-items-center justify-content-between">
          <span class="fw-bold small text-uppercase text-secondary d-flex align-items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-primary"></i> Operations Audit Trail
          </span>
          <a href="/audit-logs" class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold" style="font-size: 0.78rem;">All Logs &rarr;</a>
        </div>
        <div class="card-body p-0 d-flex flex-column justify-content-between">
          <div class="table-responsive" style="-webkit-overflow-scrolling: touch;">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem; min-width: 480px;">
              <thead class="table-light">
                <tr>
                  <th style="font-size: 0.72rem; text-transform: uppercase;" class="text-nowrap ps-3">Timestamp</th>
                  <th style="font-size: 0.72rem; text-transform: uppercase;">User</th>
                  <th style="font-size: 0.72rem; text-transform: uppercase;">Action</th>
                  <th style="font-size: 0.72rem; text-transform: uppercase;" class="text-end pe-3">Details</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentActivities as $act): ?>
                  <tr>
                    <td class="text-muted small text-nowrap ps-3" style="font-size: 0.76rem;"><?= format_datetime($act['created_at']) ?></td>
                    <td><span class="fw-bold small text-dark"><?= e($act['user_name'] ?? 'System') ?></span></td>
                    <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold px-2 py-1" style="font-size: 0.68rem;"><?= e($act['action']) ?></span></td>
                    <td class="text-end pe-3"><span class="small text-truncate d-inline-block text-secondary" style="max-width: 180px;" title="<?= e($act['description']) ?>"><?= e($act['description']) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
