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

  <!-- Dynamic Role-Based Hero Banner -->
  <div class="dashboard-hero-banner d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <div class="position-relative" style="z-index: 2;">
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="pulse-dot"></span>
        <span class="badge bg-white bg-opacity-20 text-white fw-bold px-2 py-1" style="font-size: 0.7rem; letter-spacing: 0.06em; backdrop-filter: blur(4px);">
          <?php if ($dashboardType === 'admin'): ?>
            EXECUTIVE CONTROL &bull; REAL-TIME SYNC
          <?php elseif ($dashboardType === 'branch'): ?>
            BRANCH COMMAND &bull; <?= e(strtoupper($branchName)) ?>
          <?php elseif ($dashboardType === 'accounts'): ?>
            FINANCIAL COMMAND &bull; LEDGER SYNC
          <?php else: ?>
            VISA PROCESSING DESK &bull; ACTIVE SHIFT
          <?php endif; ?>
        </span>
        <span class="text-white-50 small d-none d-sm-inline">&bull; <?= date('l, F j, Y') ?></span>
      </div>
      <h2 class="fw-bold brand-font text-white mb-1" style="font-size: 1.75rem; letter-spacing: -0.02em;">
        Good <?= (date('H') < 12) ? 'morning' : ((date('H') < 17) ? 'afternoon' : 'evening') ?>, <?= e(explode(' ', $currentUser['name'] ?? 'Staff')[0]) ?> 👋
      </h2>
      <p class="text-white-50 small mb-0">
        <?php if ($dashboardType === 'admin'): ?>
          Unified command center for global visa operations, team bottlenecks &amp; compliance SLAs.
        <?php elseif ($dashboardType === 'branch'): ?>
          Dedicated operations hub for <strong><?= e($branchName) ?></strong> staff, customer files &amp; branch throughput.
        <?php elseif ($dashboardType === 'accounts'): ?>
          Real-time oversight of customer invoicing, collections, supplier payouts &amp; ledger balances.
        <?php else: ?>
          Personal processing workstation for your assigned files, task deadlines &amp; document reviews.
        <?php endif; ?>
      </p>
    </div>

    <div class="d-flex align-items-center gap-2 position-relative" style="z-index: 2;">
      <?php if ($dashboardType === 'accounts'): ?>
        <?php if ($canCreatePayment): ?>
          <a href="/payments" class="btn btn-light btn-sm px-3 shadow fw-semibold text-primary">
            <i class="fa-solid fa-receipt text-primary me-1"></i> Payments &amp; Invoices
          </a>
          <a href="/payment-links" class="btn btn-primary btn-sm px-3 shadow fw-semibold" style="background: var(--ms-gradient-ruby); border: 1px solid rgba(255,255,255,0.25);">
            <i class="fa-solid fa-link me-1"></i> Payment Links
          </a>
        <?php endif; ?>
      <?php elseif ($dashboardType === 'processing'): ?>
        <a href="/tasks" class="btn btn-light btn-sm px-3 shadow fw-semibold text-primary">
          <i class="fa-solid fa-list-check text-primary me-1"></i> My Tasks (<?= $kpi['my_tasks'] ?>)
        </a>
        <?php if ($canCreateApp): ?>
          <a href="/applications/create" class="btn btn-primary btn-sm px-3 shadow fw-semibold" style="background: var(--ms-gradient-ruby); border: 1px solid rgba(255,255,255,0.25);">
            <i class="fa-solid fa-plus me-1"></i> New Application
          </a>
        <?php else: ?>
          <a href="/applications" class="btn btn-primary btn-sm px-3 shadow fw-semibold" style="background: var(--ms-gradient-ruby); border: 1px solid rgba(255,255,255,0.25);">
            <i class="fa-solid fa-folder-open me-1"></i> My Queue
          </a>
        <?php endif; ?>
      <?php else: ?>
        <a href="/tracking" class="btn btn-light btn-sm px-3 shadow fw-semibold text-primary">
          <i class="fa-solid fa-route text-primary me-1"></i> Tracking Hub
        </a>
        <?php if ($canCreateApp): ?>
          <a href="/applications/create" class="btn btn-primary btn-sm px-3 shadow fw-semibold" style="background: var(--ms-gradient-ruby); border: 1px solid rgba(255,255,255,0.25);">
            <i class="fa-solid fa-plus me-1"></i> New Application
          </a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Operational Alerts Bar (Scoped by Role) -->
  <div class="row g-2 mb-3">
    <div class="col-12">
      <div class="live-alerts-bar">
        <div class="d-flex flex-wrap align-items-center gap-2">
          <span class="live-alerts-header">
            <i class="fa-solid fa-bell"></i> Live Alerts:
          </span>

          <?php if ($dashboardType === 'accounts'): ?>
            <a href="/payments" class="alert-pill alert-pill-ruby">
              <i class="fa-solid fa-receipt"></i> Unpaid Applications:
              <span class="count-badge"><?= $alerts['unpaid_applications'] ?></span>
            </a>
            <span class="alert-pill alert-pill-success">
              <i class="fa-solid fa-money-bill-wave"></i> Outstanding Due:
              <span class="count-badge"><?= format_currency($finance['outstanding']) ?></span>
            </span>
          <?php elseif ($dashboardType === 'processing'): ?>
            <a href="/documents?expiry_filter=30" class="alert-pill alert-pill-amber">
              <i class="fa-solid fa-passport"></i> Passports Expiring (Assigned):
              <span class="count-badge"><?= $alerts['expiring_passports'] ?></span>
            </a>
            <a href="/tasks" class="alert-pill alert-pill-rose">
              <i class="fa-solid fa-clock-rotate-left"></i> My Overdue Tasks:
              <span class="count-badge"><?= $alerts['overdue_tasks'] ?></span>
            </a>
            <span class="alert-pill alert-pill-indigo">
              <i class="fa-solid fa-inbox"></i> Open System Pool:
              <span class="count-badge"><?= $kpi['unassigned_pool'] ?> files</span>
            </span>
          <?php else: ?>
            <a href="/documents?expiry_filter=30" class="alert-pill alert-pill-amber">
              <i class="fa-solid fa-passport"></i> Passports Expiring:
              <span class="count-badge"><?= $alerts['expiring_passports'] ?></span>
            </a>
            <a href="/customers" class="alert-pill alert-pill-cyan">
              <i class="fa-solid fa-id-card"></i> National IDs Expiring:
              <span class="count-badge"><?= $alerts['expiring_national_ids'] ?></span>
            </a>
            <a href="/tasks" class="alert-pill alert-pill-rose">
              <i class="fa-solid fa-clock-rotate-left"></i> Overdue Tasks:
              <span class="count-badge"><?= $alerts['overdue_tasks'] ?></span>
            </a>
            <?php if ($canViewFinance): ?>
              <a href="/payments" class="alert-pill alert-pill-ruby">
                <i class="fa-solid fa-receipt"></i> Outstanding Balances:
                <span class="count-badge"><?= $alerts['unpaid_applications'] ?></span>
              </a>
            <?php endif; ?>
          <?php endif; ?>
        </div>

        <div class="date-summary-group">
          <span class="date-summary-pill">Today: <strong><?= $kpi['today'] ?></strong></span>
          <span class="date-summary-pill">This Week: <strong><?= $kpi['week'] ?></strong></span>
          <span class="date-summary-pill">This Month: <strong><?= $kpi['month'] ?></strong></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Primary 6-Grid KPI Stat Cards (Tailored by Role) -->
  <div class="row g-2 g-md-3 mb-4">
    <?php if ($dashboardType === 'accounts'): ?>
      <!-- Accounts Desk: Financial Core KPIs -->
      <div class="col-6 col-md-4 col-xl-2">
        <a href="/payments" class="stat-card stat-card-blue">
          <div class="stat-icon-wrapper"><i class="fa-solid fa-file-invoice-dollar"></i></div>
          <div class="stat-card-content">
            <div class="stat-title">Total Invoiced</div>
            <div class="stat-value" style="font-size: 1.15rem;"><?= format_currency($finance['total_sales']) ?></div>
            <div class="stat-trend"><i class="fa-solid fa-receipt me-1"></i>All sales</div>
          </div>
        </a>
      </div>

      <div class="col-6 col-md-4 col-xl-2">
        <a href="/payments" class="stat-card stat-card-success">
          <div class="stat-icon-wrapper"><i class="fa-solid fa-circle-check"></i></div>
          <div class="stat-card-content">
            <div class="stat-title">Total Collected</div>
            <div class="stat-value text-success" style="font-size: 1.15rem;"><?= format_currency($finance['total_received']) ?></div>
            <div class="stat-trend"><i class="fa-solid fa-arrow-trend-up me-1"></i>Cash &amp; bank</div>
          </div>
        </a>
      </div>

      <div class="col-6 col-md-4 col-xl-2">
        <a href="/payments" class="stat-card stat-card-danger">
          <div class="stat-icon-wrapper"><i class="fa-solid fa-hourglass-half"></i></div>
          <div class="stat-card-content">
            <div class="stat-title">Outstanding</div>
            <div class="stat-value text-danger" style="font-size: 1.15rem;"><?= format_currency($finance['outstanding']) ?></div>
            <div class="stat-trend"><i class="fa-solid fa-clock me-1"></i>Receivables</div>
          </div>
        </a>
      </div>

      <div class="col-6 col-md-4 col-xl-2">
        <a href="/payments" class="stat-card stat-card-purple">
          <div class="stat-icon-wrapper"><i class="fa-solid fa-hand-holding-dollar"></i></div>
          <div class="stat-card-content">
            <div class="stat-title">Supplier Cost</div>
            <div class="stat-value" style="font-size: 1.15rem;"><?= format_currency($finance['supplier_cost']) ?></div>
            <div class="stat-trend"><i class="fa-solid fa-building-columns me-1"></i>Payables</div>
          </div>
        </a>
      </div>

      <div class="col-6 col-md-4 col-xl-2">
        <a href="/payments" class="stat-card stat-card-cyan">
          <div class="stat-icon-wrapper"><i class="fa-solid fa-chart-line"></i></div>
          <div class="stat-card-content">
            <div class="stat-title">Gross Profit</div>
            <div class="stat-value" style="font-size: 1.15rem;"><?= format_currency($finance['gross_profit']) ?></div>
            <div class="stat-trend"><i class="fa-solid fa-shield-halved me-1"></i>Net margin</div>
          </div>
        </a>
      </div>

      <div class="col-6 col-md-4 col-xl-2">
        <a href="/payments" class="stat-card stat-card-warning">
          <div class="stat-icon-wrapper"><i class="fa-solid fa-receipt"></i></div>
          <div class="stat-card-content">
            <div class="stat-title">Unpaid Files</div>
            <div class="stat-value"><?= number_format($alerts['unpaid_applications']) ?></div>
            <div class="stat-trend"><i class="fa-solid fa-triangle-exclamation me-1"></i>Action needed</div>
          </div>
        </a>
      </div>

    <?php elseif ($dashboardType === 'processing'): ?>
      <!-- Processing Staff Desk: Personal Assigned Workload KPIs -->
      <div class="col-6 col-md-4 col-xl-2">
        <a href="/applications" class="stat-card stat-card-blue">
          <div class="stat-icon-wrapper"><i class="fa-solid fa-folder-open"></i></div>
          <div class="stat-card-content">
            <div class="stat-title">My Assigned Files</div>
            <div class="stat-value"><?= number_format($kpi['total']) ?></div>
            <div class="stat-trend"><i class="fa-solid fa-user-check me-1"></i>My active desk</div>
          </div>
        </a>
      </div>

      <div class="col-6 col-md-4 col-xl-2">
        <a href="/applications?quick_tab=in_progress" class="stat-card stat-card-cyan">
          <div class="stat-icon-wrapper"><i class="fa-solid fa-arrows-rotate fa-spin-pulse"></i></div>
          <div class="stat-card-content">
            <div class="stat-title">In Progress</div>
            <div class="stat-value"><?= number_format($kpi['active']) ?></div>
            <div class="stat-trend"><i class="fa-solid fa-clock me-1"></i>Active processing</div>
          </div>
        </a>
      </div>

      <div class="col-6 col-md-4 col-xl-2">
        <a href="/action-center" class="stat-card stat-card-danger">
          <div class="stat-icon-wrapper"><i class="fa-solid fa-triangle-exclamation"></i></div>
          <div class="stat-card-content">
            <div class="stat-title">Action Required</div>
            <div class="stat-value"><?= number_format($kpi['action_required']) ?></div>
            <div class="stat-trend"><i class="fa-solid fa-bolt me-1"></i>Urgent / Docs</div>
          </div>
        </a>
      </div>

      <div class="col-6 col-md-4 col-xl-2">
        <a href="/applications?quick_tab=approved" class="stat-card stat-card-success">
          <div class="stat-icon-wrapper"><i class="fa-solid fa-circle-check"></i></div>
          <div class="stat-card-content">
            <div class="stat-title">Visas Approved</div>
            <div class="stat-value"><?= number_format($kpi['completed']) ?></div>
            <div class="stat-trend"><i class="fa-solid fa-award me-1"></i>Completed</div>
          </div>
        </a>
      </div>

      <div class="col-6 col-md-4 col-xl-2">
        <a href="/applications?quick_tab=urgent" class="stat-card stat-card-purple">
          <div class="stat-icon-wrapper"><i class="fa-solid fa-hourglass-end"></i></div>
          <div class="stat-card-content">
            <div class="stat-title">Overdue SLA</div>
            <div class="stat-value"><?= number_format($kpi['overdue']) ?></div>
            <div class="stat-trend"><i class="fa-solid fa-fire me-1"></i>Deadline alert</div>
          </div>
        </a>
      </div>

      <div class="col-6 col-md-4 col-xl-2">
        <a href="/tasks" class="stat-card stat-card-warning">
          <div class="stat-icon-wrapper"><i class="fa-solid fa-list-check"></i></div>
          <div class="stat-card-content">
            <div class="stat-title">My Tasks</div>
            <div class="stat-value"><?= number_format($kpi['my_tasks']) ?></div>
            <div class="stat-trend"><i class="fa-solid fa-calendar-check me-1"></i>Pending action</div>
          </div>
        </a>
      </div>

    <?php else: ?>
      <!-- Admin & Branch Manager: Operational Throughput KPIs -->
      <div class="col-6 col-md-4 col-xl-2">
        <a href="/applications" class="stat-card stat-card-blue">
          <div class="stat-icon-wrapper"><i class="fa-solid fa-folder-open"></i></div>
          <div class="stat-card-content">
            <div class="stat-title"><?= $dashboardType === 'branch' ? 'Branch Files' : 'Total Files' ?></div>
            <div class="stat-value"><?= number_format($kpi['total']) ?></div>
            <div class="stat-trend"><i class="fa-solid fa-layer-group me-1"></i>Registry</div>
          </div>
        </a>
      </div>

      <div class="col-6 col-md-4 col-xl-2">
        <a href="/applications?quick_tab=in_progress" class="stat-card stat-card-cyan">
          <div class="stat-icon-wrapper"><i class="fa-solid fa-arrows-rotate fa-spin-pulse"></i></div>
          <div class="stat-card-content">
            <div class="stat-title">In Progress</div>
            <div class="stat-value"><?= number_format($kpi['active']) ?></div>
            <div class="stat-trend"><i class="fa-solid fa-clock me-1"></i>Active queue</div>
          </div>
        </a>
      </div>

      <div class="col-6 col-md-4 col-xl-2">
        <a href="/action-center" class="stat-card stat-card-danger">
          <div class="stat-icon-wrapper"><i class="fa-solid fa-triangle-exclamation"></i></div>
          <div class="stat-card-content">
            <div class="stat-title">Action Needed</div>
            <div class="stat-value"><?= number_format($kpi['action_required']) ?></div>
            <div class="stat-trend"><i class="fa-solid fa-bolt me-1"></i>Attention</div>
          </div>
        </a>
      </div>

      <div class="col-6 col-md-4 col-xl-2">
        <a href="/applications?quick_tab=approved" class="stat-card stat-card-success">
          <div class="stat-icon-wrapper"><i class="fa-solid fa-circle-check"></i></div>
          <div class="stat-card-content">
            <div class="stat-title">Approved</div>
            <div class="stat-value"><?= number_format($kpi['completed']) ?></div>
            <div class="stat-trend"><i class="fa-solid fa-check me-1"></i>Issued</div>
          </div>
        </a>
      </div>

      <div class="col-6 col-md-4 col-xl-2">
        <a href="/applications?quick_tab=urgent" class="stat-card stat-card-purple">
          <div class="stat-icon-wrapper"><i class="fa-solid fa-hourglass-end"></i></div>
          <div class="stat-card-content">
            <div class="stat-title">Overdue SLA</div>
            <div class="stat-value"><?= number_format($kpi['overdue']) ?></div>
            <div class="stat-trend"><i class="fa-solid fa-fire me-1"></i>Breached</div>
          </div>
        </a>
      </div>

      <div class="col-6 col-md-4 col-xl-2">
        <a href="/documents?expiry_filter=30" class="stat-card stat-card-warning">
          <div class="stat-icon-wrapper"><i class="fa-solid fa-id-card-clip"></i></div>
          <div class="stat-card-content">
            <div class="stat-title">Expiring Docs</div>
            <div class="stat-value"><?= number_format($kpi['expiring_passports']) ?></div>
            <div class="stat-trend"><i class="fa-solid fa-clock-rotate-left me-1"></i>&le; 30 Days</div>
          </div>
        </a>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($dashboardType === 'accounts'): ?>
    <!-- ACCOUNTS DESK SECTION 1: INVOICING & PAYMENT STREAMS -->
    <div class="row g-4 mb-4">
      <div class="col-lg-7">
        <div class="card card-enterprise h-100">
          <div class="card-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-danger fw-bold px-2 py-1"><i class="fa-solid fa-receipt me-1"></i>COLLECTIONS</span>
              <span class="fw-bold text-dark small text-uppercase">Unpaid &amp; Pending Invoices</span>
            </div>
            <a href="/payments" class="btn btn-outline-danger btn-sm py-0 px-2 fw-semibold" style="font-size: 0.78rem;">
              View All Invoices &rarr;
            </a>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table-modern mb-0">
                <thead>
                  <tr>
                    <th>File #</th>
                    <th>Customer</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Balance</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($unpaidInvoices)): ?>
                    <tr>
                      <td colspan="6">
                        <div class="empty-state py-4">
                          <div class="empty-state-icon" style="width: 48px; height: 48px; font-size: 1.25rem;">
                            <i class="fa-solid fa-circle-check text-success"></i>
                          </div>
                          <div class="empty-state-title fs-6">All Invoices Settled</div>
                          <div class="empty-state-text small mb-0">There are no outstanding customer balances pending collection.</div>
                        </div>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($unpaidInvoices as $inv): ?>
                      <tr>
                        <td>
                          <a href="/applications/show?id=<?= $inv['id'] ?>" class="badge bg-primary-subtle text-primary fw-bold text-decoration-none px-2 py-1">
                            <?= e($inv['application_number']) ?>
                          </a>
                        </td>
                        <td>
                          <div class="fw-bold text-dark"><?= e($inv['customer_name']) ?></div>
                          <div class="text-muted small" style="font-size: 0.72rem;"><?= e($inv['mobile']) ?></div>
                        </td>
                        <td class="text-end fw-semibold"><?= format_currency((float)$inv['total_amount']) ?></td>
                        <td class="text-end text-success fw-semibold"><?= format_currency((float)$inv['paid_amount']) ?></td>
                        <td class="text-end text-danger fw-bold"><?= format_currency((float)$inv['balance_amount']) ?></td>
                        <td class="text-end">
                          <a href="/payments/create?application_id=<?= $inv['id'] ?>" class="btn btn-sm btn-success py-1 px-2" style="font-size: 0.75rem;">
                            <i class="fa-solid fa-plus me-1"></i> Collect
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
      </div>

      <div class="col-lg-5">
        <div class="card card-enterprise h-100">
          <div class="card-header d-flex align-items-center justify-content-between">
            <span class="fw-bold small text-uppercase text-secondary">
              <i class="fa-solid fa-money-bill-transfer text-success me-2"></i> Recent Payment Receipts
            </span>
            <a href="/payments" class="btn btn-link btn-sm text-decoration-none p-0" style="font-size: 0.78rem;">All Records &rarr;</a>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table-modern mb-0">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Applicant</th>
                    <th>Method</th>
                    <th class="text-end">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($recentPayments)): ?>
                    <tr>
                      <td colspan="4" class="text-center py-4 text-muted small">No verified payment transactions logged yet.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($recentPayments as $rp): ?>
                      <tr>
                        <td class="text-nowrap small text-muted"><?= format_date($rp['payment_date'] ?? date('Y-m-d')) ?></td>
                        <td>
                          <div class="fw-semibold text-dark text-truncate" style="max-width: 140px;"><?= e($rp['customer_name'] ?? 'Applicant') ?></div>
                          <div class="text-muted small" style="font-size: 0.7rem;"><?= e($rp['application_number'] ?? '') ?></div>
                        </td>
                        <td><span class="badge bg-light text-secondary border"><?= e($rp['payment_method'] ?? 'Cash') ?></span></td>
                        <td class="text-end fw-bold text-success"><?= format_currency((float)$rp['amount']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

  <?php elseif ($dashboardType === 'processing'): ?>
    <!-- PROCESSING DESK SECTION 1: WORK QUEUE & ASSIGNED TASKS -->
    <div class="row g-4 mb-4">
      <!-- Assigned Cases Requiring Attention -->
      <div class="col-lg-7">
        <div class="card card-enterprise h-100">
          <div class="card-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-primary fw-bold px-2 py-1"><i class="fa-solid fa-briefcase me-1"></i>MY DESK</span>
              <span class="fw-bold text-dark small text-uppercase">Assigned Files In Queue</span>
            </div>
            <a href="/applications" class="btn btn-outline-primary btn-sm py-0 px-2 fw-semibold" style="font-size: 0.78rem;">
              Full Workload &rarr;
            </a>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table-modern mb-0">
                <thead>
                  <tr>
                    <th>File #</th>
                    <th>Applicant</th>
                    <th>Visa Service</th>
                    <th>Current Stage</th>
                    <th>Priority</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($urgentApplications)): ?>
                    <tr>
                      <td colspan="6">
                        <div class="empty-state py-4">
                          <div class="empty-state-icon" style="width: 48px; height: 48px; font-size: 1.25rem;">
                            <i class="fa-solid fa-circle-check text-success"></i>
                          </div>
                          <div class="empty-state-title fs-6">Your Processing Queue is Clear!</div>
                          <div class="empty-state-text small mb-0">No active applications currently assigned to your desk. Pick files from the registry or check with management.</div>
                        </div>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($urgentApplications as $uApp): ?>
                      <tr>
                        <td>
                          <a href="/applications/show?id=<?= $uApp['id'] ?>" class="badge bg-primary-subtle text-primary fw-bold text-decoration-none px-2 py-1">
                            <?= e($uApp['application_number']) ?>
                          </a>
                        </td>
                        <td>
                          <div class="fw-bold text-dark"><?= e($uApp['customer_name']) ?></div>
                          <div class="text-muted small" style="font-size: 0.72rem;"><?= e($uApp['mobile']) ?></div>
                        </td>
                        <td><span class="small fw-semibold text-dark"><?= e($uApp['service_name']) ?></span></td>
                        <td>
                          <span class="badge bg-warning-subtle text-warning-text fw-bold px-2 py-1" style="font-size: 0.73rem;">
                            <?= e($uApp['current_stage']) ?>
                          </span>
                        </td>
                        <td>
                          <?php 
                            $prio = strtolower($uApp['priority']);
                            $prioClass = ($prio === 'urgent' || $prio === 'critical') ? 'badge-priority-critical' : (($prio === 'high') ? 'badge-priority-urgent' : 'badge-priority-normal');
                          ?>
                          <span class="badge <?= $prioClass ?>">
                            <?= e($uApp['priority']) ?>
                          </span>
                        </td>
                        <td class="text-end">
                          <a href="/applications/show?id=<?= $uApp['id'] ?>" class="btn btn-sm btn-primary py-1 px-3" style="font-size: 0.78rem;">
                            Process &rarr;
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
      </div>

      <!-- My Assigned Tasks & Deadlines (Role Scoped) -->
      <div class="col-lg-5">
        <div class="card card-enterprise h-100 shadow-sm border">
          <div class="card-header d-flex align-items-center justify-content-between py-3 px-3">
            <div class="d-flex align-items-center gap-2">
              <span class="fw-bold small text-uppercase text-secondary">
                <i class="fa-solid fa-list-check text-primary me-1"></i> My Tasks (<?= count($myTasks) ?>)
              </span>
              <?php if (!$canViewAllTasks): ?>
                <span class="badge bg-light text-primary border" style="font-size: 0.68rem;">Personal Workstation</span>
              <?php endif; ?>
            </div>
            <a href="/tasks" class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold" style="font-size: 0.78rem;">Full Board &rarr;</a>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table-modern mb-0">
                <thead>
                  <tr>
                    <th>Task &amp; Linked File</th>
                    <th>Due Date</th>
                    <th>Priority</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($myTasks)): ?>
                    <tr>
                      <td colspan="4">
                        <div class="empty-state py-4">
                          <div class="empty-state-icon" style="width: 42px; height: 42px; font-size: 1.15rem;">
                            <i class="fa-solid fa-check-double text-success"></i>
                          </div>
                          <div class="empty-state-title fs-6">No Pending Tasks</div>
                          <div class="empty-state-text small mb-0">You're all caught up with your operational milestones!</div>
                        </div>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($myTasks as $t): ?>
                      <?php 
                        $isOverdue = !empty($t['due_date']) && $t['due_date'] < date('Y-m-d');
                        $tTitle = $t['task_title'] ?? $t['title'] ?? 'Operational Task';
                      ?>
                      <tr>
                        <td>
                          <div class="fw-semibold text-dark text-truncate" style="max-width: 170px;" title="<?= e($tTitle) ?>">
                            <?= e($tTitle) ?>
                          </div>
                          <div class="text-muted small" style="font-size: 0.7rem;">
                            <?= e($t['application_number'] ?? 'General') ?> &bull; <?= e($t['customer_name'] ?? '') ?>
                          </div>
                        </td>
                        <td>
                          <span class="badge <?= $isOverdue ? 'bg-danger text-white' : 'bg-light text-secondary border' ?>" style="font-size: 0.72rem;">
                            <?= !empty($t['due_date']) ? format_date($t['due_date']) : 'No Deadline' ?>
                          </span>
                        </td>
                        <td>
                          <span class="badge <?= ($t['priority'] === 'Urgent' || $t['priority'] === 'Critical') ? 'badge-priority-critical' : 'badge-priority-normal' ?>">
                            <?= e($t['priority']) ?>
                          </span>
                        </td>
                        <td class="text-end text-nowrap">
                          <button type="button" class="btn btn-sm btn-success py-1 px-2 fw-semibold" style="font-size: 0.75rem;"
                                  onclick="openCompleteTaskModal(<?= (int)$t['id'] ?>, '<?= e(addslashes($tTitle)) ?>')">
                            <i class="fa-solid fa-check me-1"></i> Complete
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
      </div>
    </div>

  <?php else: ?>
    <!-- ADMIN & BRANCH MANAGER: ACTION CENTER HIGHLIGHTS -->
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
  <?php endif; ?>

  <?php if ($dashboardType !== 'processing'): ?>
    <!-- OPERATIONAL TASKS & ASSIGNED WORK (ROLE SCOPED) -->
    <div class="card card-enterprise mb-4 shadow-sm border">
      <div class="card-header bg-white border-bottom py-3 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-primary fw-bold px-2 py-1"><i class="fa-solid fa-list-check me-1"></i>OPERATIONAL TASKS</span>
          <span class="fw-bold text-dark small text-uppercase">
            <?= ($taskScope ?? 'my') === 'all' && $canViewAllTasks ? 'Team Operational Tasks' : 'My Assigned Operational Tasks' ?> (<?= count($myTasks) ?>)
          </span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <?php if ($canViewAllTasks): ?>
            <div class="btn-group btn-group-sm" role="group">
              <a href="/dashboard?task_scope=my" class="btn <?= ($taskScope ?? 'my') === 'my' ? 'btn-primary fw-bold' : 'btn-light border' ?>">
                <i class="fa-solid fa-user-check me-1"></i> My Tasks
              </a>
              <a href="/dashboard?task_scope=all" class="btn <?= ($taskScope ?? '') === 'all' ? 'btn-primary fw-bold' : 'btn-light border' ?>">
                <i class="fa-solid fa-users me-1"></i> All Staff Tasks
              </a>
            </div>
          <?php else: ?>
            <span class="badge bg-light text-primary border" style="font-size: 0.72rem;">
              <i class="fa-solid fa-lock me-1"></i> My Tasks Only
            </span>
          <?php endif; ?>
          <a href="/tasks" class="btn btn-outline-primary btn-sm py-0 px-2 fw-semibold" style="font-size: 0.78rem;">
            Full Board &rarr;
          </a>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table-modern mb-0">
            <thead>
              <tr>
                <th>Task Title &amp; Details</th>
                <th>Linked Application</th>
                <th>Priority</th>
                <th>Assigned Officer</th>
                <th>Due Date</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($myTasks)): ?>
                <tr>
                  <td colspan="6" class="text-center py-4">
                    <div class="empty-state py-2">
                      <div class="empty-state-icon" style="width: 40px; height: 40px; font-size: 1.1rem;">
                        <i class="fa-solid fa-circle-check text-success"></i>
                      </div>
                      <div class="empty-state-title fs-6">No Pending Tasks</div>
                      <div class="empty-state-text small mb-0">No active operational tasks in this view.</div>
                    </div>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($myTasks as $t): ?>
                  <?php 
                    $isOverdue = !empty($t['due_date']) && $t['due_date'] < date('Y-m-d');
                    $tTitle = $t['task_title'] ?? $t['title'] ?? 'Operational Task';
                  ?>
                  <tr>
                    <td>
                      <div class="fw-bold text-dark text-truncate" style="max-width: 260px;" title="<?= e($tTitle) ?>">
                        <?= e($tTitle) ?>
                      </div>
                      <?php if (!empty($t['description'])): ?>
                        <div class="text-muted small text-truncate" style="max-width: 260px; font-size: 0.72rem;" title="<?= e($t['description']) ?>">
                          <?= e($t['description']) ?>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (!empty($t['application_number'])): ?>
                        <a href="/applications/show?id=<?= $t['application_id'] ?? 0 ?>" class="fw-bold text-primary text-decoration-none">
                          <i class="fa-solid fa-folder me-1"></i><?= e($t['application_number']) ?>
                        </a>
                        <div class="text-muted small" style="font-size: 0.7rem;"><?= e($t['customer_name'] ?? '—') ?></div>
                      <?php else: ?>
                        <span class="text-muted small">General Milestone</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="badge <?= ($t['priority'] === 'Urgent' || $t['priority'] === 'Critical') ? 'badge-priority-critical' : 'badge-priority-normal' ?>">
                        <?= e($t['priority']) ?>
                      </span>
                    </td>
                    <td>
                      <span class="small fw-semibold text-dark"><i class="fa-solid fa-user-circle text-muted me-1"></i><?= e($t['assigned_to_name'] ?? 'Assigned Officer') ?></span>
                    </td>
                    <td>
                      <span class="badge <?= $isOverdue ? 'bg-danger text-white' : 'bg-light text-secondary border' ?>" style="font-size: 0.72rem;">
                        <?= !empty($t['due_date']) ? format_date($t['due_date']) : 'No Deadline' ?>
                      </span>
                      <?php if ($isOverdue): ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.65rem;">Overdue</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                      <button type="button" class="btn btn-sm btn-success py-1 px-3 fw-bold" style="font-size: 0.75rem;"
                              onclick="openCompleteTaskModal(<?= (int)$t['id'] ?>, '<?= e(addslashes($tTitle)) ?>')">
                        <i class="fa-solid fa-check me-1"></i> Complete
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
  <?php endif; ?>

  <!-- Operational Insights Grid (Stages Breakdown & Contextual Right Column) -->
  <div class="row g-4 mb-4">
    <!-- Active Stage Breakdown -->
    <div class="col-lg-6">
      <div class="card card-enterprise h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
          <span class="fw-bold small text-uppercase text-secondary">
            <i class="fa-solid fa-bars-progress text-primary me-2"></i> 
            <?= $dashboardType === 'processing' ? 'My Assigned Stage Breakdown' : 'Active Stage Breakdown' ?>
          </span>
          <a href="/tracking" class="btn btn-link btn-sm text-decoration-none p-0" style="font-size: 0.78rem;">Open Timeline &rarr;</a>
        </div>
        <div class="card-body">
          <?php if (empty($stages)): ?>
            <div class="text-muted small py-3 text-center">No active applications currently progressing through pipeline stages.</div>
          <?php else: ?>
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
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Right Side Column: Tailored by Permissions -->
    <div class="col-lg-6">
      <?php if ($canViewFinance && $dashboardType !== 'processing'): ?>
        <!-- Top Destinations & Financial Snapshot (Admin & Branch Manager) -->
        <div class="card card-enterprise h-100">
          <div class="card-header d-flex align-items-center justify-content-between">
            <span class="fw-bold small text-uppercase text-secondary">
              <i class="fa-solid fa-earth-americas text-info me-2"></i> Destinations &amp; Financials
            </span>
            <span class="badge bg-light text-secondary border">Volume</span>
          </div>
          <div class="card-body">
            <div class="row g-2 mb-3">
              <?php if (empty($countries)): ?>
                <div class="col-12 text-muted small text-center py-2">No country data recorded.</div>
              <?php else: ?>
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
              <?php endif; ?>
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

      <?php else: ?>
        <!-- Processing Staff & Non-Finance: Upcoming Customer Appointments -->
        <div class="card card-enterprise h-100">
          <div class="card-header d-flex align-items-center justify-content-between">
            <span class="fw-bold small text-uppercase text-secondary">
              <i class="fa-solid fa-calendar-check text-primary me-2"></i> Upcoming Appointments
            </span>
            <a href="/appointments" class="btn btn-link btn-sm text-decoration-none p-0" style="font-size: 0.78rem;">Appointments Calendar &rarr;</a>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table-modern mb-0">
                <thead>
                  <tr>
                    <th>Date &amp; Time</th>
                    <th>Applicant</th>
                    <th>Appointment Type</th>
                    <th class="text-end">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($upcomingAppointments)): ?>
                    <tr>
                      <td colspan="4" class="text-center py-4 text-muted small">
                        <i class="fa-regular fa-calendar-xmark fs-4 d-block mb-1 text-muted"></i>
                        No appointments scheduled for upcoming days.
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($upcomingAppointments as $apt): ?>
                      <tr>
                        <td class="text-nowrap small fw-semibold">
                          <i class="fa-regular fa-calendar me-1 text-primary"></i> <?= format_date($apt['appointment_date']) ?><br>
                          <span class="text-muted" style="font-size: 0.72rem;"><i class="fa-regular fa-clock me-1"></i><?= e($apt['appointment_time'] ?? '') ?></span>
                        </td>
                        <td>
                          <div class="fw-semibold text-dark"><?= e($apt['customer_name'] ?? 'Applicant') ?></div>
                          <div class="text-muted small" style="font-size: 0.7rem;"><?= e($apt['application_number'] ?? '') ?></div>
                        </td>
                        <td><span class="badge bg-light text-secondary border"><?= e($apt['appointment_type'] ?? 'Embassy') ?></span></td>
                        <td class="text-end">
                          <span class="badge bg-info-subtle text-info fw-semibold"><?= e($apt['status'] ?? 'Scheduled') ?></span>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Bottom Section: Staff Workload & Audit Activity Trail -->
  <div class="row g-4">
    <?php if ($canViewStaff): ?>
      <!-- Staff Workload (Only for Admins & Branch Managers) -->
      <div class="col-lg-6">
        <div class="card card-enterprise h-100">
          <div class="card-header d-flex align-items-center justify-content-between">
            <span class="fw-bold small text-uppercase text-secondary">
              <i class="fa-solid fa-users text-primary me-2"></i> 
              <?= $dashboardType === 'branch' ? 'Branch Team Workload' : 'Staff Workload Distribution' ?>
            </span>
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
                  <?php if (empty($staffWorkload)): ?>
                    <tr>
                      <td colspan="5" class="text-center py-4 text-muted small">No active officers found.</td>
                    </tr>
                  <?php else: ?>
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
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Audit / Activity Trail (Scoped to user permissions) -->
    <div class="<?= $canViewStaff ? 'col-lg-6' : 'col-12' ?>">
      <div class="card card-enterprise shadow-sm border h-100">
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4 d-flex align-items-center justify-content-between">
          <span class="fw-bold small text-uppercase text-secondary d-flex align-items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-primary"></i> 
            <?= $canViewAudit ? 'Operations Audit Trail' : 'My Recent Shift Activity' ?>
          </span>
          <?php if ($canViewAudit): ?>
            <a href="/audit-logs" class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold" style="font-size: 0.78rem;">All Logs &rarr;</a>
          <?php endif; ?>
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
                <?php if (empty($recentActivities)): ?>
                  <tr>
                    <td colspan="4" class="text-center py-4 text-muted small">No recent activity logs recorded for this session.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($recentActivities as $act): ?>
                    <tr>
                      <td class="text-muted small text-nowrap ps-3" style="font-size: 0.76rem;"><?= format_datetime($act['created_at']) ?></td>
                      <td><span class="fw-bold small text-dark"><?= e($act['user_name'] ?? 'System') ?></span></td>
                      <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold px-2 py-1" style="font-size: 0.68rem;"><?= e($act['action']) ?></span></td>
                      <td class="text-end pe-3"><span class="small text-truncate d-inline-block text-secondary" style="max-width: 260px;" title="<?= e($act['description']) ?>"><?= e($act['description']) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
<!-- MODAL: COMPLETE TASK WITH PROOF OF WORK (DASHBOARD) -->
<div class="modal fade" id="completeTaskModal" tabindex="-1" aria-labelledby="completeTaskModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-success text-white">
        <h6 class="modal-title fw-bold" id="completeTaskModalLabel">
          <i class="fa-solid fa-clipboard-check me-2"></i> Complete Task &amp; Submit Proof of Work
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/tasks/status" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="task_id" id="completeTaskId" value="0">
        <input type="hidden" name="status" value="Completed">

        <div class="modal-body p-4">
          <div class="p-3 bg-light rounded border mb-3">
            <div class="fw-bold text-dark small" id="completeTaskTitle">—</div>
            <div class="text-muted small mt-1" style="font-size: 0.75rem;">
              <i class="fa-solid fa-circle-info text-primary me-1"></i> Policy requirement: Record verification notes or proof of work performed to complete this task.
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-dark">
              Proof of Work / Completion Notes <span class="text-danger">*</span>
            </label>
            <textarea name="completion_notes" id="completeNotes" class="form-control" rows="3" required 
                      placeholder="e.g. Completed document upload, submitted visa to embassy, reference code #98124..."></textarea>
            <div class="form-text small">Detailed explanation of the work done to fulfill this operational task.</div>
          </div>

          <div class="mb-2">
            <label class="form-label small fw-bold text-dark">
              Attach Proof Document / Screenshot / Receipt <small class="text-muted fw-normal">(Optional)</small>
            </label>
            <input type="file" name="proof_file" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.zip">
            <div class="form-text small">Accepts PDF, PNG, JPG, DOCX, ZIP files up to 10MB.</div>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success btn-sm px-3 fw-bold">
            <i class="fa-solid fa-check-double me-1"></i> Submit Proof &amp; Complete Task
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openCompleteTaskModal(taskId, taskTitle) {
  document.getElementById('completeTaskId').value = taskId;
  document.getElementById('completeTaskTitle').textContent = taskTitle;
  document.getElementById('completeNotes').value = '';
  var modal = new bootstrap.Modal(document.getElementById('completeTaskModal'));
  modal.show();
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
