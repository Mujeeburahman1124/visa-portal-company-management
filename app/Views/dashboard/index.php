<?php
$pageTitle = 'Dashboard — VISA TRACK';
$flash = get_flash();
$currentUser = auth_user();
$rawUName = trim((string)($currentUser['name'] ?? 'Staff'));
$gName = ($rawUName === 'Super Admin' || $rawUName === '') ? 'Super Admin' : e(explode(' ', $rawUName)[0]);

// Operational counts strictly based on user database
$totalFiles = max(1, (int)($kpi['total'] ?? 1));
$approvedCount = (int)($kpi['approved'] ?? $kpi['completed'] ?? 0);
$activeCount = (int)($kpi['active'] ?? 0);
$actionCount = (int)($kpi['action_required'] ?? $kpi['pending'] ?? 0);

require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
?>

<!-- Load Bento Design System CSS (Harmonized with Logo Color) -->
<link rel="stylesheet" href="/assets/css/dashboard-bento.css?v=<?= time() ?>">

<div class="content-body p-0">
  <div class="bento-dashboard-wrap">

    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm rounded-4" role="alert">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
          <span><?= e($flash['message']) ?></span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- ─── TOP HEADER (BENTO STYLE) ────────────────────────────────────────── -->
    <header class="bento-header">
      <div>
        <h1 class="bento-header-title">Dashboard</h1>
        <p class="bento-header-subtitle">
          Plan, prioritize, and accomplish your visa operations with ease.
        </p>
      </div>
      <div class="bento-header-actions">
        <a href="/reports" class="bento-btn-outline">
          <i class="fa-solid fa-chart-pie"></i> Export / Reports
        </a>
      </div>
    </header>

    <!-- ─── OPERATIONAL ALERTS BAR & DATE SUMMARY (ALL DATA PRESERVED) ─────── -->
    <div class="bento-alert-strip">
      <div class="bento-alert-chips-group">
        <div class="bento-alert-strip-label">
          <i class="fa-solid fa-bell"></i> Live Alerts:
        </div>

        <?php if (!empty($alerts['expiring_passports'])): ?>
          <a href="/documents?expiry_filter=30" class="bento-alert-chip chip-warning">
            <i class="fa-solid fa-passport"></i> Expiring Passports:
            <span class="bento-alert-badge"><?= (int)$alerts['expiring_passports'] ?></span>
          </a>
        <?php endif; ?>

        <?php if (!empty($alerts['expiring_national_ids'])): ?>
          <a href="/customers" class="bento-alert-chip chip-warning">
            <i class="fa-solid fa-id-card"></i> National IDs:
            <span class="bento-alert-badge"><?= (int)$alerts['expiring_national_ids'] ?></span>
          </a>
        <?php endif; ?>

        <?php if (!empty($alerts['overdue_tasks'])): ?>
          <a href="/tasks" class="bento-alert-chip chip-danger">
            <i class="fa-solid fa-clock-rotate-left"></i> Overdue Tasks:
            <span class="bento-alert-badge"><?= (int)$alerts['overdue_tasks'] ?></span>
          </a>
        <?php endif; ?>

        <?php if (!empty($alerts['unpaid_applications']) && $canViewFinance): ?>
          <a href="/payments" class="bento-alert-chip chip-danger">
            <i class="fa-solid fa-receipt"></i> Unpaid Files:
            <span class="bento-alert-badge"><?= (int)$alerts['unpaid_applications'] ?></span>
          </a>
        <?php endif; ?>

        <?php if (!empty($kpi['unassigned_pool'])): ?>
          <a href="/applications" class="bento-alert-chip">
            <i class="fa-solid fa-inbox"></i> Open Pool:
            <span class="bento-alert-badge"><?= (int)$kpi['unassigned_pool'] ?> files</span>
          </a>
        <?php endif; ?>
      </div>

      <div class="bento-time-summary">
        <span class="bento-time-pill">Today: <strong><?= (int)$kpi['today'] ?></strong></span>
        <span class="bento-time-pill">This Week: <strong><?= (int)$kpi['week'] ?></strong></span>
        <span class="bento-time-pill">This Month: <strong><?= (int)$kpi['month'] ?></strong></span>
      </div>
    </div>

    <!-- ─── TOP 4 BENTO METRIC STAT CARDS (TAILORED BY ROLE WITH LOGO COLOR) ── -->
    <div class="row g-3 g-md-4 mb-4 bento-stat-grid">
      <?php if ($dashboardType === 'accounts'): ?>
        <!-- Accounts Desk KPIs -->
        <div class="col-6 col-lg-3">
          <a href="/payments" class="bento-stat-card featured-card">
            <div class="bento-stat-top">
              <span class="bento-stat-title">Total Invoiced</span>
              <div class="bento-arrow-circle"><i class="fa-solid fa-arrow-up-right"></i></div>
            </div>
            <div class="bento-stat-value" style="font-size: 2.15rem;"><?= format_currency($finance['total_sales']) ?></div>
            <div class="bento-stat-badge">
              <span class="bento-badge-pill"><i class="fa-solid fa-receipt"></i> Sales</span>
              <span>All billable files</span>
            </div>
          </a>
        </div>

        <div class="col-6 col-lg-3">
          <a href="/payments" class="bento-stat-card">
            <div class="bento-stat-top">
              <span class="bento-stat-title">Total Collected</span>
              <div class="bento-arrow-circle"><i class="fa-solid fa-arrow-up-right"></i></div>
            </div>
            <div class="bento-stat-value" style="font-size: 2.15rem; color: var(--bento-success);"><?= format_currency($finance['total_received']) ?></div>
            <div class="bento-stat-badge">
              <span class="bento-badge-pill"><i class="fa-solid fa-circle-check"></i> Cash &amp; Bank</span>
              <span>Verified receipts</span>
            </div>
          </a>
        </div>

        <div class="col-6 col-lg-3">
          <a href="/payments" class="bento-stat-card">
            <div class="bento-stat-top">
              <span class="bento-stat-title">Outstanding Due</span>
              <div class="bento-arrow-circle"><i class="fa-solid fa-arrow-up-right"></i></div>
            </div>
            <div class="bento-stat-value" style="font-size: 2.15rem; color: var(--bento-danger);"><?= format_currency($finance['outstanding']) ?></div>
            <div class="bento-stat-badge">
              <span class="bento-badge-pill"><i class="fa-solid fa-hourglass-half"></i> Pending</span>
              <span><?= (int)$alerts['unpaid_applications'] ?> unpaid files</span>
            </div>
          </a>
        </div>

        <div class="col-6 col-lg-3">
          <a href="/payments" class="bento-stat-card">
            <div class="bento-stat-top">
              <span class="bento-stat-title">Gross Profit</span>
              <div class="bento-arrow-circle"><i class="fa-solid fa-arrow-up-right"></i></div>
            </div>
            <div class="bento-stat-value" style="font-size: 2.15rem;"><?= format_currency($finance['gross_profit']) ?></div>
            <div class="bento-stat-badge">
              <span class="bento-badge-pill"><i class="fa-solid fa-chart-line"></i> Margin</span>
              <span>Cost: <?= format_currency($finance['supplier_cost']) ?></span>
            </div>
          </a>
        </div>

      <?php elseif ($dashboardType === 'processing'): ?>
        <!-- Processing Staff Desk KPIs -->
        <div class="col-6 col-lg-3">
          <a href="/applications" class="bento-stat-card featured-card">
            <div class="bento-stat-top">
              <span class="bento-stat-title">My Assigned Files</span>
              <div class="bento-arrow-circle"><i class="fa-solid fa-arrow-up-right"></i></div>
            </div>
            <div class="bento-stat-value"><?= number_format((int)$kpi['total']) ?></div>
            <div class="bento-stat-badge">
              <span class="bento-badge-pill"><i class="fa-solid fa-user-check"></i> Desk</span>
              <span>Active caseload</span>
            </div>
          </a>
        </div>

        <div class="col-6 col-lg-3">
          <a href="/applications?quick_tab=approved" class="bento-stat-card">
            <div class="bento-stat-top">
              <span class="bento-stat-title">Visas Approved</span>
              <div class="bento-arrow-circle"><i class="fa-solid fa-arrow-up-right"></i></div>
            </div>
            <div class="bento-stat-value"><?= number_format((int)$kpi['completed']) ?></div>
            <div class="bento-stat-badge">
              <span class="bento-badge-pill"><i class="fa-solid fa-circle-check"></i> Completed</span>
              <span>Issued visas</span>
            </div>
          </a>
        </div>

        <div class="col-6 col-lg-3">
          <a href="/applications?quick_tab=in_progress" class="bento-stat-card">
            <div class="bento-stat-top">
              <span class="bento-stat-title">In Progress</span>
              <div class="bento-arrow-circle"><i class="fa-solid fa-arrow-up-right"></i></div>
            </div>
            <div class="bento-stat-value"><?= number_format((int)$kpi['active']) ?></div>
            <div class="bento-stat-badge">
              <span class="bento-badge-pill"><i class="fa-solid fa-clock"></i> Active</span>
              <span>Files in queue</span>
            </div>
          </a>
        </div>

        <div class="col-6 col-lg-3">
          <a href="/action-center" class="bento-stat-card">
            <div class="bento-stat-top">
              <span class="bento-stat-title">Action Required</span>
              <div class="bento-arrow-circle"><i class="fa-solid fa-arrow-up-right"></i></div>
            </div>
            <div class="bento-stat-value" style="color: var(--bento-danger);"><?= number_format((int)$kpi['action_required']) ?></div>
            <div class="bento-stat-badge">
              <span class="bento-badge-pill" style="color: #DC2626; background: #FEF2F2;"><i class="fa-solid fa-bolt"></i> Urgent</span>
              <span>Docs / Overdue</span>
            </div>
          </a>
        </div>

      <?php else: ?>
        <!-- Admin & Branch Manager KPIs -->
        <div class="col-6 col-lg-3">
          <a href="/applications" class="bento-stat-card featured-card">
            <div class="bento-stat-top">
              <span class="bento-stat-title">Total Applications</span>
              <div class="bento-arrow-circle"><i class="fa-solid fa-arrow-up-right"></i></div>
            </div>
            <div class="bento-stat-value"><?= number_format((int)$kpi['total']) ?></div>
            <div class="bento-stat-badge">
              <span class="bento-badge-pill"><i class="fa-solid fa-arrow-trend-up"></i> +<?= (int)$kpi['month'] ?></span>
              <span>This month</span>
            </div>
          </a>
        </div>

        <div class="col-6 col-lg-3">
          <a href="/applications?quick_tab=approved" class="bento-stat-card">
            <div class="bento-stat-top">
              <span class="bento-stat-title">Ended / Approved</span>
              <div class="bento-arrow-circle"><i class="fa-solid fa-arrow-up-right"></i></div>
            </div>
            <div class="bento-stat-value"><?= number_format($approvedCount) ?></div>
            <div class="bento-stat-badge">
              <span class="bento-badge-pill"><i class="fa-solid fa-check"></i> +<?= (int)$kpi['week'] ?></span>
              <span>Issued &amp; Settled</span>
            </div>
          </a>
        </div>

        <div class="col-6 col-lg-3">
          <a href="/applications?quick_tab=in_progress" class="bento-stat-card">
            <div class="bento-stat-top">
              <span class="bento-stat-title">Running Applications</span>
              <div class="bento-arrow-circle"><i class="fa-solid fa-arrow-up-right"></i></div>
            </div>
            <div class="bento-stat-value"><?= number_format($activeCount) ?></div>
            <div class="bento-stat-badge">
              <span class="bento-badge-pill"><i class="fa-solid fa-arrows-rotate"></i> <?= (int)$kpi['today'] ?></span>
              <span>Active in queue</span>
            </div>
          </a>
        </div>

        <div class="col-6 col-lg-3">
          <a href="/action-center" class="bento-stat-card">
            <div class="bento-stat-top">
              <span class="bento-stat-title">Action Required</span>
              <div class="bento-arrow-circle"><i class="fa-solid fa-arrow-up-right"></i></div>
            </div>
            <div class="bento-stat-value" style="color: var(--bento-danger);"><?= number_format($actionCount) ?></div>
            <div class="bento-stat-badge">
              <span class="bento-badge-pill" style="color: #DC2626; background: #FEF2F2;"><i class="fa-solid fa-triangle-exclamation"></i> <?= (int)($kpi['overdue'] ?? 0) ?></span>
              <span>SLA / Review needed</span>
            </div>
          </a>
        </div>
      <?php endif; ?>
    </div>

    <!-- ─── MIDDLE BENTO ROW (ANALYTICS, REMINDERS, RECENT APPS) ──────────── -->
    <div class="row g-3 g-md-4 mb-4">
      
      <!-- Left: Project & Stage Analytics (Dynamic Pill Bar Chart in Image) -->
      <div class="col-12 col-lg-5">
        <div class="bento-card">
          <div class="bento-card-head">
            <div>
              <h3 class="bento-card-title">Project Analytics</h3>
              <p class="text-muted small mb-0">Pipeline distribution across key operational milestones</p>
            </div>
            <a href="/tracking" class="bento-pill-btn-sm">
              Timeline <i class="fa-solid fa-arrow-right small ms-1"></i>
            </a>
          </div>

          <!-- Dynamic Weekly Volume Bars (100% Real Database Application Data) -->
          <?php 
            $maxWeeklyCount = 1;
            foreach ($weeklyActivity as $wa) {
                if ($wa['count'] > $maxWeeklyCount) {
                    $maxWeeklyCount = $wa['count'];
                }
            }
          ?>
          <div class="bento-chart-container">
            <?php foreach ($weeklyActivity as $idx => $wa): 
              $cVal = (int)$wa['count'];
              // Calculate real height percentage: 8% min track for 0, scaled up to 100% for peak
              $barPct = ($cVal === 0) ? 8 : max(20, min(100, round(($cVal / $maxWeeklyCount) * 100)));
              $isPeak = ($cVal > 0 && $cVal === $maxWeeklyCount);
              $isToday = !empty($wa['is_today']);
              // Primary highlight in logo brand color if peak or active today
              $isHighlight = ($isPeak || ($isToday && $cVal > 0));
              $barClass = $isHighlight ? 'bento-bar-brand' : ($cVal > 0 ? 'bento-bar-solid-dark' : 'bento-bar-striped');
              $calcPctLabel = ($totalFiles > 0 && $cVal > 0) ? round(($cVal / $totalFiles) * 100) . '%' : ($cVal > 0 ? $cVal . ' files' : '0');
            ?>
              <div class="bento-bar-col" title="<?= e($wa['day']) ?> (<?= e($wa['date']) ?>): <?= $cVal ?> applications">
                <div class="bento-bar-track">
                  <?php if ($cVal > 0): ?>
                    <div class="bento-bar-tooltip"><?= $calcPctLabel ?></div>
                  <?php endif; ?>
                  <div class="bento-bar-pill <?= $barClass ?>" style="height: <?= $barPct ?>%;"></div>
                </div>
                <span class="bento-bar-day" style="<?= $isHighlight ? 'color: var(--bento-primary); font-weight: 700;' : ($isToday ? 'font-weight: 700;' : '') ?>">
                  <?= e($wa['letter']) ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Center: Reminders Card (Reminders in Image) -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="bento-card">
          <div class="bento-card-head">
            <h3 class="bento-card-title">Reminders</h3>
          </div>

          <div class="bento-reminder-content">
            <?php if (!empty($upcomingAppointments)): ?>
              <?php $firstApt = $upcomingAppointments[0]; ?>
              <div>
                <div class="badge bg-light text-secondary border mb-2 small fw-semibold" style="border-radius: var(--bento-radius-pill);">
                  <?= e($firstApt['appointment_type'] ?? 'Embassy Appointment') ?>
                </div>
                <h4 class="bento-reminder-lead">
                  Meeting with <?= e($firstApt['customer_name'] ?? 'Applicant') ?>
                </h4>
                <div class="bento-reminder-time">
                  <i class="fa-regular fa-clock me-1 text-muted"></i>
                  Time : <?= e($firstApt['appointment_time'] ?: 'Scheduled Time') ?> &bull; <?= format_date($firstApt['appointment_date']) ?>
                </div>
              </div>
              <a href="/appointments" class="bento-reminder-btn">
                <i class="fa-solid fa-video"></i> Start Meeting
              </a>
            <?php elseif (!empty($urgentApplications)): ?>
              <?php $firstUrgent = $urgentApplications[0]; ?>
              <div>
                <div class="badge bg-danger-subtle text-danger border border-danger-subtle mb-2 small fw-bold" style="border-radius: var(--bento-radius-pill);">
                  <i class="fa-solid fa-fire me-1"></i>SLA Urgent Action
                </div>
                <h4 class="bento-reminder-lead">
                  File <?= e($firstUrgent['application_number']) ?> &bull; <?= e($firstUrgent['customer_name']) ?>
                </h4>
                <div class="bento-reminder-time">
                  <i class="fa-solid fa-hourglass-half me-1 text-muted"></i>
                  Stage : <?= e($firstUrgent['current_stage']) ?>
                </div>
              </div>
              <a href="/applications/show?id=<?= $firstUrgent['id'] ?>" class="bento-reminder-btn">
                <i class="fa-solid fa-bolt"></i> Resolve Case
              </a>
            <?php else: ?>
              <div>
                <div class="badge bg-success-subtle text-success border border-success-subtle mb-2 small fw-bold" style="border-radius: var(--bento-radius-pill);">
                  <i class="fa-solid fa-check me-1"></i>All Caught Up
                </div>
                <h4 class="bento-reminder-lead">
                  Consular Pipeline Operational
                </h4>
                <div class="bento-reminder-time">
                  <i class="fa-regular fa-calendar-check me-1 text-muted"></i>
                  All applications advancing on schedule.
                </div>
              </div>
              <a href="/applications" class="bento-reminder-btn">
                <i class="fa-solid fa-folder-open"></i> View All Files
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Right: Recent Applications (Project List in Image) -->
      <div class="col-12 col-md-6 col-lg-4">
        <div class="bento-card">
          <div class="bento-card-head">
            <h3 class="bento-card-title">Project</h3>
            <?php if ($canCreateApp): ?>
              <a href="/applications/create" class="bento-pill-btn-sm">
                + New
              </a>
            <?php endif; ?>
          </div>

          <div class="bento-list">
            <?php 
              $recentQueue = !empty($urgentApplications) ? array_slice($urgentApplications, 0, 5) : [];
              $shapes = ['shape-brand', 'shape-blue', 'shape-dark', 'shape-amber', 'shape-purple'];
              $icons = ['fa-bolt', 'fa-passport', 'fa-stamp', 'fa-earth-americas', 'fa-fingerprint'];
            ?>

            <?php if (empty($recentQueue)): ?>
              <div class="text-center py-4 text-muted small">
                No active applications in queue.
              </div>
            <?php else: ?>
              <?php foreach ($recentQueue as $idx => $rApp): ?>
                <?php 
                  $shapeClass = $shapes[$idx % count($shapes)];
                  $iconClass = $icons[$idx % count($icons)];
                ?>
                <a href="/applications/show?id=<?= $rApp['id'] ?>" class="bento-list-item">
                  <div class="bento-item-left">
                    <div class="bento-shape-icon <?= $shapeClass ?>">
                      <i class="fa-solid <?= $iconClass ?>"></i>
                    </div>
                    <div>
                      <h5 class="bento-item-title"><?= e($rApp['customer_name']) ?></h5>
                      <p class="bento-item-sub">
                        <?= e($rApp['application_number']) ?> &bull; <?= e($rApp['service_name']) ?>
                      </p>
                    </div>
                  </div>
                  <div class="text-end flex-shrink-0">
                    <span class="badge bg-light text-secondary border small" style="font-size: 0.7rem; border-radius: var(--bento-radius-pill);">
                      <?= e($rApp['current_stage']) ?>
                    </span>
                  </div>
                </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- ─── THIRD BENTO ROW (TEAM COLLAB, RADIAL GAUGE, FINANCIAL PULSE) ──── -->
    <div class="row g-3 g-md-4 mb-4">
      
      <!-- Left: Team Collaboration -->
      <div class="col-12 col-lg-6">
        <div class="bento-card">
          <div class="bento-card-head">
            <h3 class="bento-card-title">Team Collaboration</h3>
            <?php if ($canViewStaff): ?>
              <a href="/staff" class="bento-pill-btn-sm">
                + Add Member
              </a>
            <?php endif; ?>
          </div>

          <div class="bento-team-list">
            <?php 
              $workloadList = !empty($staffWorkload) ? array_slice($staffWorkload, 0, 4) : [];
              $avatarColors = ['var(--bento-primary)', 'var(--bento-dark)', '#BE123C', '#9F1239'];
            ?>

            <?php if (empty($workloadList)): ?>
              <!-- Current authenticated staff member fallback -->
              <div class="bento-team-row">
                <div class="bento-avatar" style="background: var(--bento-primary);">
                  <?= strtoupper(substr($gName, 0, 2)) ?>
                </div>
                <div class="bento-team-info">
                  <h5 class="bento-team-name"><?= e($currentUser['name'] ?? 'Super Admin') ?></h5>
                  <p class="bento-team-role">Visa Processing Operations</p>
                </div>
                <span class="bento-status-pill pill-completed">Active</span>
              </div>
            <?php else: ?>
              <?php foreach ($workloadList as $idx => $sw): ?>
                <?php 
                  $avColor = $avatarColors[$idx % count($avatarColors)];
                  $initials = strtoupper(substr(trim($sw['name']), 0, 2));
                  $statusPill = ((int)$sw['urgent_cases'] > 0) ? 'pill-pending' : (((int)$sw['active_cases'] > 0) ? 'pill-inprogress' : 'pill-completed');
                  $statusText = ((int)$sw['urgent_cases'] > 0) ? 'Action Required' : (((int)$sw['active_cases'] > 0) ? 'In Progress' : 'Completed');
                ?>
                <div class="bento-team-row">
                  <div class="bento-avatar" style="background: <?= $avColor ?>;">
                    <?= $initials ?>
                  </div>
                  <div class="bento-team-info">
                    <h5 class="bento-team-name"><?= e($sw['name']) ?></h5>
                    <p class="bento-team-role">
                      Working on <?= e($sw['role_name']) ?> &bull; <?= (int)$sw['active_cases'] ?> Cases
                    </p>
                  </div>
                  <span class="bento-status-pill <?= $statusPill ?>">
                    <?= $statusText ?>
                  </span>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Right: Financial Pulse / Live Session (50% Column Layout) -->
      <div class="col-12 col-lg-6">
        <div class="bento-time-tracker">
          <div class="bento-tracker-bg-waves"></div>
          <!-- Abstract Organic Wave SVG -->
          <svg class="bento-tracker-wave-svg" viewBox="0 0 400 200" preserveAspectRatio="none">
            <path d="M0,100 C150,180 250,20 400,100 L400,200 L0,200 Z" fill="rgba(225,29,72,0.12)"></path>
            <path d="M0,130 C120,40 280,180 400,130 L400,200 L0,200 Z" fill="rgba(255,255,255,0.06)"></path>
          </svg>

          <div class="d-flex align-items-center justify-content-between position-relative" style="z-index: 2;">
            <h4 class="bento-tracker-label">
              <i class="fa-solid fa-bolt text-warning me-1"></i> 
              <?= $canViewFinance ? 'Revenue & Collections' : 'Time Tracker' ?>
            </h4>
            <span class="badge bg-white bg-opacity-15 text-white small rounded-pill px-2.5 py-1">
              Live
            </span>
          </div>

          <?php if ($canViewFinance): ?>
            <div class="bento-tracker-digits">
              <?= format_currency($finance['total_received']) ?>
            </div>
            <div class="text-center text-white-50 small mb-2 position-relative" style="z-index: 2;">
              Invoiced: <?= format_currency($finance['total_sales']) ?> &bull; Due: <?= format_currency($finance['outstanding']) ?>
            </div>
          <?php else: ?>
            <div class="bento-tracker-digits" id="liveShiftTimer">
              01:24:08
            </div>
            <div class="text-center text-white-50 small mb-2 position-relative" style="z-index: 2;">
              Active Shift Session &bull; Assigned: <?= (int)$kpi['my_tasks'] ?> Tasks
            </div>
          <?php endif; ?>

          <div class="bento-tracker-actions">
            <?php if ($canViewFinance): ?>
              <a href="/payments" class="bento-btn-primary" style="font-size: 0.82rem; padding: 0.5rem 1.25rem;">
                <i class="fa-solid fa-receipt me-1"></i> Invoices
              </a>
              <a href="/payment-links" class="bento-btn-outline" style="border-color: rgba(255,255,255,0.3); color: #FFFFFF !important; background: transparent; font-size: 0.82rem; padding: 0.5rem 1.25rem;">
                <i class="fa-solid fa-link me-1"></i> Pay Links
              </a>
            <?php else: ?>
              <button type="button" class="bento-tracker-btn" title="Pause Session" onclick="toggleShiftTimer(this)">
                <i class="fa-solid fa-pause"></i>
              </button>
              <button type="button" class="bento-tracker-btn" title="Reset Session" onclick="resetShiftTimer()">
                <i class="fa-solid fa-stop text-danger"></i>
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- =====================================================================
         SECTION 4: ACTIVE VISA PACKAGES & SERVICES CATALOG (BENTO GRID)
         ===================================================================== -->
    <!-- =====================================================================
         SECTION 4: ACTIVE VISA PACKAGES & SERVICES CATALOG (TRAVEL CARDS / IMAGE 2 LOOK)
         ===================================================================== -->
    <?php if (!empty($popularPackages)): ?>
      <div class="bento-card mb-4">
        <div class="bento-card-head flex-wrap gap-2">
          <div>
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="badge" style="background: var(--bento-primary); color: #fff; font-size: 0.72rem; border-radius: var(--bento-radius-pill); padding: 4px 10px;">
                <i class="fa-solid fa-plane-departure me-1"></i> Global Portfolios
              </span>
              <h3 class="bento-card-title mb-0">Active Visa Packages &amp; Service Tiers</h3>
            </div>
            <p class="text-muted small mb-0">Official consular SLAs, pricing rates, turnaround times, and active pipelines.</p>
          </div>
          <div class="d-flex align-items-center gap-2">
            <a href="/visa-services?create=1" class="btn btn-sm btn-primary px-3 shadow-xs fw-semibold text-white" style="border-radius: var(--bento-radius-pill); font-size: 0.8rem; background: var(--bento-primary); border-color: var(--bento-primary);">
              <i class="fa-solid fa-plus me-1"></i> Add Visa Package
            </a>
            <a href="/visa-services" class="bento-pill-btn-sm">
              All Services Catalog &rarr;
            </a>
          </div>
        </div>

        <div class="bento-travel-grid mobile-carousel mt-3">
          <?php foreach ($popularPackages as $pkg): 
            $cName = strtolower($pkg['country_name'] ?? '');
            $pName = strtolower($pkg['name'] ?? '');
            $cover = $pkg['image_url'] ?? '';
            if (empty($cover)) {
                if (str_contains($pName, 'golden') || str_contains($pName, 'investor')) {
                    $cover = '/assets/images/destinations/uae-golden.jpg';
                } elseif (str_contains($cName, 'emirates') || str_contains($cName, 'uae') || str_contains($pName, 'uae') || str_contains($pName, 'dubai')) {
                    $cover = '/assets/images/destinations/uae-tourist.jpg';
                } elseif (str_contains($cName, 'kingdom') || str_contains($cName, 'uk') || str_contains($pName, 'uk') || str_contains($pName, 'london')) {
                    $cover = '/assets/images/destinations/uk-visitor.jpg';
                } elseif (str_contains($cName, 'france') || str_contains($cName, 'schengen') || str_contains($pName, 'schengen')) {
                    $cover = '/assets/images/destinations/france-schengen.jpg';
                } elseif (str_contains($cName, 'states') || str_contains($cName, 'us') || str_contains($pName, 'us') || str_contains($pName, 'usa')) {
                    $cover = '/assets/images/destinations/us-visitor.jpg';
                } elseif (str_contains($cName, 'saudi') || str_contains($pName, 'umrah')) {
                    $cover = '/assets/images/destinations/saudi-umrah.jpg';
                } elseif (str_contains($cName, 'canada')) {
                    $cover = '/assets/images/destinations/canada-visitor.jpg';
                } else {
                    $cover = '/assets/images/destinations/default-travel.jpg';
                }
            }
          ?>
            <div class="bento-travel-card">
              <!-- Top Curved Photo Container (Image 2 Aesthetic) -->
              <div class="travel-card-photo-wrap">
                <img src="<?= e($cover) ?>" alt="<?= e($pkg['name']) ?>" class="travel-card-photo" id="pkgCoverImg_<?= (int)$pkg['id'] ?>" loading="lazy" onerror="this.onerror=null;this.src='/assets/images/destinations/default-travel.jpg'">
                
                <!-- Country / Flag Badge Left -->
                <div class="travel-photo-badge-left">
                  <span class="fs-6"><?= e($pkg['flag_emoji'] ?? '🌐') ?></span>
                  <span><?= e($pkg['country_name'] ?? 'Global') ?></span>
                </div>

                <!-- Clean Active Files Badge Right (NO admin buttons on display card) -->
                <?php if (!empty($pkg['active_files_count'])): ?>
                  <div class="travel-photo-badge-right">
                    <span class="badge bg-white text-secondary border small fw-bold shadow-xs" style="border-radius: var(--bento-radius-pill); font-size: 0.72rem; padding: 4px 10px;">
                      <i class="fa-solid fa-circle text-success me-1" style="font-size: 0.55rem;"></i><?= (int)$pkg['active_files_count'] ?> Active
                    </span>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Card Body -->
              <div class="travel-card-body">
                <h4 class="travel-card-title">
                  <a href="/applications/create?service_id=<?= (int)$pkg['id'] ?>" class="text-decoration-none text-dark"><?= e($pkg['name']) ?></a>
                </h4>
                
                <div class="travel-card-location">
                  <i class="fa-solid fa-location-dot"></i>
                  <span><?= e($pkg['country_name'] ?? 'International') ?><?= !empty($pkg['entry_type']) ? ' &bull; ' . e($pkg['entry_type']) : '' ?></span>
                </div>

                <?php $pkgDesc = trim($pkg['description'] ?? $pkg['notes'] ?? ''); ?>
                <?php if (!empty($pkgDesc)): ?>
                  <div class="travel-card-desc-head">Description</div>
                  <p class="travel-card-desc-text">
                    <?= e($pkgDesc) ?>
                  </p>
                <?php endif; ?>

                <!-- 3 Real Stats Row (100% Real Database Data) -->
                <div class="travel-card-stats-row">
                  <div class="travel-card-stat-col">
                    <span class="travel-card-stat-label">Est. Days</span>
                    <span class="travel-card-stat-val">
                      <?php 
                        $estDays = (int)($pkg['estimated_days'] ?? 0);
                        echo $estDays > 0 ? ($estDays . ($estDays === 1 ? ' Day' : ' Days')) : 'Standard';
                      ?>
                    </span>
                  </div>
                  <div class="travel-card-stat-col" style="border-left: 1px solid #E2E8F0; border-right: 1px solid #E2E8F0;">
                    <span class="travel-card-stat-label">Validity</span>
                    <span class="travel-card-stat-val">
                      <?= e(!empty($pkg['validity']) ? $pkg['validity'] : (!empty($pkg['duration']) ? $pkg['duration'] : 'Standard')) ?>
                    </span>
                  </div>
                  <div class="travel-card-stat-col">
                    <span class="travel-card-stat-label">Processing</span>
                    <span class="travel-card-stat-val"><?= e(!empty($pkg['processing_type']) ? $pkg['processing_type'] : 'Normal') ?></span>
                  </div>
                </div>

                <!-- Bottom Price & Circular Airplane Action Button (Image 2) -->
                <div class="travel-card-bottom">
                  <div>
                    <div class="travel-card-price-label">Total Price</div>
                    <div class="travel-card-price-val"><?= e($pkg['currency'] ?: 'USD') ?> <?= number_format((float)($pkg['selling_price'] ?? 0), 2) ?></div>
                  </div>
                  <a href="/applications/create?service_id=<?= (int)$pkg['id'] ?>" class="travel-card-plane-btn" title="Start Application for <?= e($pkg['name']) ?>" aria-label="Book or Apply Now">
                    <i class="fa-solid fa-plane"></i>
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- =====================================================================
         SECTION 5: ACTION CENTER — CASES REQUIRING IMMEDIATE ATTENTION
         ===================================================================== -->
    <div class="bento-card mb-4">
      <div class="bento-card-head">
        <div>
          <h3 class="bento-card-title">
            <i class="fa-solid fa-bolt text-danger me-1"></i> Action Center — Items Requiring Attention
          </h3>
          <p class="text-muted small mb-0">Applications with document reviews, embassy requirements, or critical SLA milestones.</p>
        </div>
        <a href="/action-center" class="bento-pill-btn-sm">
          Full Action Center &rarr;
        </a>
      </div>

      <!-- Desktop Full Table View (>= 768px) -->
      <div class="bento-table-wrap d-none d-md-block">
        <table class="bento-table">
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
                <td colspan="8" class="text-center py-4 text-muted">
                  <i class="fa-solid fa-circle-check text-success fs-3 d-block mb-2"></i>
                  All applications are progressing on schedule. No critical bottlenecks!
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($urgentApplications as $uApp): ?>
                <tr>
                  <td>
                    <a href="/applications/show?id=<?= $uApp['id'] ?>" class="badge bg-light text-dark border fw-bold text-decoration-none px-2.5 py-1.5" style="border-radius: var(--bento-radius-pill);">
                      <?= e($uApp['application_number']) ?>
                    </a>
                  </td>
                  <td>
                    <div class="fw-bold text-dark"><?= e($uApp['customer_name']) ?></div>
                    <div class="text-muted small" style="font-size: 0.72rem;"><?= e($uApp['mobile']) ?></div>
                  </td>
                  <td><span class="small fw-semibold"><?= e($uApp['service_name']) ?></span></td>
                  <td>
                    <span class="badge bg-warning-subtle text-warning-emphasis fw-bold px-2 py-1" style="border-radius: var(--bento-radius-pill);">
                      <?= e($uApp['current_stage']) ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge <?= (int)($uApp['calculated_health'] ?? 100) < 50 ? 'bg-danger text-white' : 'bg-light text-dark border' ?> px-2 py-1" style="border-radius: var(--bento-radius-pill);">
                      <?= (int)($uApp['calculated_health'] ?? 100) ?>%
                    </span>
                  </td>
                  <td>
                    <?php 
                      $prio = strtolower($uApp['priority']);
                      $isCrit = ($prio === 'critical' || $prio === 'urgent');
                    ?>
                    <span class="badge <?= $isCrit ? 'bg-danger text-white' : 'bg-light text-secondary border' ?> px-2 py-1" style="border-radius: var(--bento-radius-pill);">
                      <?= e($uApp['priority']) ?>
                    </span>
                  </td>
                  <td>
                    <span class="small text-muted"><i class="fa-regular fa-user me-1"></i><?= e($uApp['staff_name'] ?? 'Unassigned') ?></span>
                  </td>
                  <td class="text-end">
                    <a href="/applications/show?id=<?= $uApp['id'] ?>" class="bento-btn-primary" style="padding: 0.35rem 0.95rem; font-size: 0.78rem;">
                      Resolve &rarr;
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Native Mobile App Card View (< 768px) -->
      <div class="d-md-none bento-mobile-card-list">
        <?php if (empty($urgentApplications)): ?>
          <div class="text-center py-4 text-muted bg-light rounded-4 border">
            <i class="fa-solid fa-circle-check text-success fs-3 d-block mb-2"></i>
            All applications are progressing on schedule.
          </div>
        <?php else: ?>
          <?php foreach ($urgentApplications as $uApp): ?>
            <?php 
              $prio = strtolower($uApp['priority']);
              $isCrit = ($prio === 'critical' || $prio === 'urgent');
            ?>
            <div class="bento-mobile-app-card">
              <div class="bento-mobile-card-header">
                <a href="/applications/show?id=<?= $uApp['id'] ?>" class="badge bg-light text-dark border fw-bold text-decoration-none px-2.5 py-1">
                  <?= e($uApp['application_number']) ?>
                </a>
                <div class="d-flex align-items-center gap-1.5">
                  <span class="badge <?= $isCrit ? 'bg-danger text-white' : 'bg-light text-secondary border' ?> px-2 py-1">
                    <?= e($uApp['priority']) ?>
                  </span>
                  <span class="badge <?= (int)($uApp['calculated_health'] ?? 100) < 50 ? 'bg-danger text-white' : 'bg-success-subtle text-success border border-success-subtle' ?> px-2 py-1">
                    <?= (int)($uApp['calculated_health'] ?? 100) ?>%
                  </span>
                </div>
              </div>

              <div class="bento-mobile-card-body">
                <div class="fw-bold text-dark fs-6 mb-0.5"><?= e($uApp['customer_name']) ?></div>
                <div class="text-muted small mb-2"><i class="fa-solid fa-phone me-1 small"></i><?= e($uApp['mobile']) ?></div>
                <div class="p-2 bg-light rounded-3 border mb-2.5">
                  <div class="small fw-semibold text-dark"><i class="fa-solid fa-passport text-primary me-1.5"></i><?= e($uApp['service_name']) ?></div>
                  <div class="d-flex align-items-center justify-content-between mt-1 text-muted" style="font-size: 0.72rem;">
                    <span>Stage: <strong class="text-dark"><?= e($uApp['current_stage']) ?></strong></span>
                    <span><i class="fa-regular fa-user me-1"></i><?= e($uApp['staff_name'] ?? 'Unassigned') ?></span>
                  </div>
                </div>
              </div>

              <div class="bento-mobile-card-actions">
                <a href="/applications/show?id=<?= $uApp['id'] ?>" class="bento-btn-primary w-100 text-center justify-content-center">
                  Resolve &rarr;
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- =====================================================================
         SECTION 6: OPERATIONAL TASKS (WITH PROOF MODAL FULLY FUNCTIONAL)
         ===================================================================== -->
    <div class="bento-card mb-4">
      <div class="bento-card-head">
        <div>
          <h3 class="bento-card-title">
            <i class="fa-solid fa-list-check text-primary me-1"></i> Operational Tasks &amp; Milestones
          </h3>
          <p class="text-muted small mb-0">Track due dates, complete document reviews, and record task verifications.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
          <?php if ($canViewAllTasks): ?>
            <div class="btn-group btn-group-sm" role="group">
              <a href="/dashboard?task_scope=my" class="btn btn-sm <?= ($taskScope ?? 'my') === 'my' ? 'bento-btn-primary' : 'bento-btn-outline' ?>" style="padding: 0.35rem 0.85rem; font-size: 0.78rem;">
                My Tasks
              </a>
              <a href="/dashboard?task_scope=all" class="btn btn-sm <?= ($taskScope ?? '') === 'all' ? 'bento-btn-primary' : 'bento-btn-outline' ?>" style="padding: 0.35rem 0.85rem; font-size: 0.78rem;">
                All Team Tasks
              </a>
            </div>
          <?php endif; ?>
          <a href="/tasks" class="bento-pill-btn-sm">
            Full Board &rarr;
          </a>
        </div>
      </div>

      <!-- Desktop Full Table View (>= 768px) -->
      <div class="bento-table-wrap d-none d-md-block">
        <table class="bento-table">
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
                <td colspan="6" class="text-center py-4 text-muted">
                  <i class="fa-solid fa-list-check text-success fs-3 d-block mb-2"></i>
                  No pending operational tasks in this view.
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
                    <div class="fw-bold text-dark"><?= e($tTitle) ?></div>
                    <?php if (!empty($t['description'])): ?>
                      <div class="text-muted small text-truncate" style="max-width: 280px; font-size: 0.72rem;"><?= e($t['description']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($t['application_number'])): ?>
                      <a href="/applications/show?id=<?= $t['application_id'] ?? 0 ?>" class="fw-bold text-decoration-none small" style="color: var(--bento-primary);">
                        <?= e($t['application_number']) ?>
                      </a>
                      <div class="text-muted small" style="font-size: 0.7rem;"><?= e($t['customer_name'] ?? '') ?></div>
                    <?php else: ?>
                      <span class="text-muted small">General Milestone</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge <?= ($t['priority'] === 'Urgent' || $t['priority'] === 'Critical') ? 'bg-danger text-white' : 'bg-light text-secondary border' ?> px-2 py-1" style="border-radius: var(--bento-radius-pill);">
                      <?= e($t['priority']) ?>
                    </span>
                  </td>
                  <td>
                    <span class="small text-muted"><?= e($t['assigned_to_name'] ?? 'Officer') ?></span>
                  </td>
                  <td>
                    <span class="badge <?= $isOverdue ? 'bg-danger text-white' : 'bg-light text-secondary border' ?> px-2 py-1" style="border-radius: var(--bento-radius-pill);">
                      <?= !empty($t['due_date']) ? format_date($t['due_date']) : 'No Deadline' ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <button type="button" class="bento-btn-primary" style="padding: 0.35rem 0.95rem; font-size: 0.78rem;"
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

      <!-- Native Mobile App Card View (< 768px) -->
      <div class="d-md-none bento-mobile-card-list">
        <?php if (empty($myTasks)): ?>
          <div class="text-center py-4 text-muted bg-light rounded-4 border">
            <i class="fa-solid fa-list-check text-success fs-3 d-block mb-2"></i>
            No pending operational tasks in this view.
          </div>
        <?php else: ?>
          <?php foreach ($myTasks as $t): ?>
            <?php 
              $isOverdue = !empty($t['due_date']) && $t['due_date'] < date('Y-m-d');
              $tTitle = $t['task_title'] ?? $t['title'] ?? 'Operational Task';
            ?>
            <div class="bento-mobile-app-card">
              <div class="bento-mobile-card-header">
                <span class="badge <?= ($t['priority'] === 'Urgent' || $t['priority'] === 'Critical') ? 'bg-danger text-white' : 'bg-light text-secondary border' ?> px-2 py-1">
                  <?= e($t['priority']) ?>
                </span>
                <span class="badge <?= $isOverdue ? 'bg-danger text-white' : 'bg-light text-secondary border' ?> px-2 py-1">
                  <i class="fa-regular fa-clock me-1"></i><?= !empty($t['due_date']) ? format_date($t['due_date']) : 'No Deadline' ?>
                </span>
              </div>

              <div class="bento-mobile-card-body">
                <div class="fw-bold text-dark fs-6 mb-1"><?= e($tTitle) ?></div>
                <?php if (!empty($t['description'])): ?>
                  <div class="text-muted small mb-2"><?= e($t['description']) ?></div>
                <?php endif; ?>

                <div class="p-2 bg-light rounded-3 border mb-2.5">
                  <div class="d-flex align-items-center justify-content-between small">
                    <span class="text-muted">Linked File:</span>
                    <?php if (!empty($t['application_number'])): ?>
                      <a href="/applications/show?id=<?= $t['application_id'] ?? 0 ?>" class="fw-bold text-decoration-none" style="color: var(--bento-primary);">
                        <?= e($t['application_number']) ?> (<?= e($t['customer_name'] ?? '') ?>)
                      </a>
                    <?php else: ?>
                      <span class="text-muted">General Milestone</span>
                    <?php endif; ?>
                  </div>
                  <div class="d-flex align-items-center justify-content-between small mt-1 text-muted">
                    <span>Officer:</span>
                    <strong class="text-dark"><i class="fa-regular fa-user me-1"></i><?= e($t['assigned_to_name'] ?? 'Officer') ?></strong>
                  </div>
                </div>
              </div>

              <div class="bento-mobile-card-actions">
                <button type="button" class="bento-btn-primary w-100 text-center justify-content-center"
                        onclick="openCompleteTaskModal(<?= (int)$t['id'] ?>, '<?= e(addslashes($tTitle)) ?>')">
                  <i class="fa-solid fa-check me-1"></i> Complete Task
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- =====================================================================
         SECTION 7: PIPELINE STAGES BREAKDOWN & TOP DESTINATIONS
         ===================================================================== -->
    <div class="row g-4 mb-4">
      <div class="col-12 col-lg-6">
        <div class="bento-card">
          <div class="bento-card-head">
            <h3 class="bento-card-title">
              <i class="fa-solid fa-bars-progress text-primary me-2"></i> Active Stage Breakdown
            </h3>
            <a href="/tracking" class="bento-pill-btn-sm">Timeline &rarr;</a>
          </div>

          <?php if (empty($stages)): ?>
            <div class="text-muted small py-3 text-center">No active applications currently in pipeline.</div>
          <?php else: ?>
            <?php foreach ($stages as $s): ?>
              <?php 
                $pct = $totalFiles > 0 ? round(($s['count'] / $totalFiles) * 100) : 0;
              ?>
              <div class="mb-3">
                <div class="d-flex justify-content-between small mb-1">
                  <span class="fw-semibold text-dark"><?= e($s['current_stage']) ?></span>
                  <span class="text-muted fw-bold"><?= $s['count'] ?> cases (<?= $pct ?>%)</span>
                </div>
                <div class="progress" style="height: 8px; border-radius: var(--bento-radius-pill);">
                  <div class="progress-bar" style="background: var(--bento-primary); width: <?= $pct ?>%"></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-12 col-lg-6">
        <div class="bento-card">
          <div class="bento-card-head">
            <h3 class="bento-card-title">
              <i class="fa-solid fa-earth-americas text-info me-2"></i> Top Destinations
            </h3>
            <span class="badge bg-light text-secondary border">Volume</span>
          </div>

          <div class="row g-2 mb-3">
            <?php if (empty($countries)): ?>
              <div class="col-12 text-muted small text-center py-2">No country data recorded.</div>
            <?php else: ?>
              <?php foreach ($countries as $c): ?>
                <div class="col-6">
                  <div class="p-2.5 border rounded-4 bg-light d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                      <span class="fs-4"><?= $c['flag_emoji'] ?></span>
                      <span class="small fw-bold text-dark text-truncate" style="max-width: 120px;"><?= e($c['country_name']) ?></span>
                    </div>
                    <span class="badge bg-white text-dark border rounded-pill px-2"><?= $c['count'] ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <!-- Financial Snapshot if authorized -->
          <?php if ($canViewFinance): ?>
            <div class="pt-3 border-top">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-bold text-uppercase text-muted" style="font-size: 0.72rem;">Financial Overview</span>
                <a href="/payments" class="small text-decoration-none fw-semibold" style="color: var(--bento-primary);">Invoices &rarr;</a>
              </div>
              <div class="row g-2 text-center">
                <div class="col-4">
                  <div class="p-2 bg-light rounded-3 border">
                    <div class="text-muted" style="font-size: 0.68rem; font-weight: 700;">TOTAL SALES</div>
                    <div class="fw-bold text-dark fs-6"><?= format_currency($finance['total_sales']) ?></div>
                  </div>
                </div>
                <div class="col-4">
                  <div class="p-2 bg-success bg-opacity-10 rounded-3 border border-success border-opacity-25">
                    <div class="text-success" style="font-size: 0.68rem; font-weight: 700;">COLLECTED</div>
                    <div class="fw-bold text-success fs-6"><?= format_currency($finance['total_received']) ?></div>
                  </div>
                </div>
                <div class="col-4">
                  <div class="p-2 bg-danger bg-opacity-10 rounded-3 border border-danger border-opacity-25">
                    <div class="text-danger" style="font-size: 0.68rem; font-weight: 700;">OUTSTANDING</div>
                    <div class="fw-bold text-danger fs-6"><?= format_currency($finance['outstanding']) ?></div>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- =====================================================================
         SECTION 8: FINANCIAL LEDGER & RECENT COLLECTIONS (IF AUTHORIZED)
         ===================================================================== -->
    <?php if ($canViewFinance && (!empty($unpaidInvoices) || !empty($recentPayments))): ?>
      <div class="row g-4 mb-4">
        <div class="col-12 col-lg-7">
          <div class="bento-card">
            <div class="bento-card-head">
              <h3 class="bento-card-title">
                <i class="fa-solid fa-receipt text-danger me-2"></i> Unpaid &amp; Pending Invoices
              </h3>
              <a href="/payments" class="bento-pill-btn-sm">View All Invoices &rarr;</a>
            </div>

            <!-- Desktop Full Table View (>= 768px) -->
            <div class="bento-table-wrap d-none d-md-block">
              <table class="bento-table">
                <thead>
                  <tr>
                    <th>File #</th>
                    <th>Customer</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Balance</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($unpaidInvoices)): ?>
                    <tr>
                      <td colspan="5" class="text-center py-4 text-muted">All customer invoices settled!</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($unpaidInvoices as $inv): ?>
                      <tr>
                        <td>
                          <a href="/applications/show?id=<?= $inv['id'] ?>" class="badge bg-light text-dark border fw-bold text-decoration-none px-2 py-1">
                            <?= e($inv['application_number']) ?>
                          </a>
                        </td>
                        <td>
                          <div class="fw-bold text-dark"><?= e($inv['customer_name']) ?></div>
                          <div class="text-muted small" style="font-size: 0.72rem;"><?= e($inv['mobile']) ?></div>
                        </td>
                        <td class="text-end fw-semibold"><?= format_currency((float)$inv['total_amount']) ?></td>
                        <td class="text-end text-danger fw-bold"><?= format_currency((float)$inv['balance_amount']) ?></td>
                        <td class="text-end">
                          <a href="/payments/create?application_id=<?= $inv['id'] ?>" class="bento-btn-primary" style="padding: 0.35rem 0.85rem; font-size: 0.75rem;">
                            Collect
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <!-- Native Mobile App Card View (< 768px) -->
            <div class="d-md-none bento-mobile-card-list">
              <?php if (empty($unpaidInvoices)): ?>
                <div class="text-center py-4 text-muted bg-light rounded-4 border">All customer invoices settled!</div>
              <?php else: ?>
                <?php foreach ($unpaidInvoices as $inv): ?>
                  <div class="bento-mobile-app-card">
                    <div class="bento-mobile-card-header">
                      <a href="/applications/show?id=<?= $inv['id'] ?>" class="badge bg-light text-dark border fw-bold text-decoration-none px-2.5 py-1">
                        <?= e($inv['application_number']) ?>
                      </a>
                      <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold px-2 py-1">
                        Due: <?= format_currency((float)$inv['balance_amount']) ?>
                      </span>
                    </div>

                    <div class="bento-mobile-card-body">
                      <div class="fw-bold text-dark fs-6 mb-0.5"><?= e($inv['customer_name']) ?></div>
                      <div class="text-muted small mb-2"><i class="fa-solid fa-phone me-1 small"></i><?= e($inv['mobile']) ?></div>
                      <div class="d-flex align-items-center justify-content-between p-2 bg-light rounded-3 border mb-2.5 small">
                        <span class="text-muted">Total Invoice:</span>
                        <span class="fw-bold text-dark"><?= format_currency((float)$inv['total_amount']) ?></span>
                      </div>
                    </div>

                    <div class="bento-mobile-card-actions">
                      <a href="/payments/create?application_id=<?= $inv['id'] ?>" class="bento-btn-primary w-100 text-center justify-content-center">
                        <i class="fa-solid fa-receipt me-1"></i> Collect Payment
                      </a>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-5">
          <div class="bento-card">
            <div class="bento-card-head">
              <h3 class="bento-card-title">
                <i class="fa-solid fa-money-bill-transfer text-success me-2"></i> Recent Payment Receipts
              </h3>
              <a href="/payments" class="bento-pill-btn-sm">All Ledger &rarr;</a>
            </div>

            <!-- Desktop Full Table View (>= 768px) -->
            <div class="bento-table-wrap d-none d-md-block">
              <table class="bento-table">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Applicant</th>
                    <th class="text-end">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($recentPayments)): ?>
                    <tr>
                      <td colspan="3" class="text-center py-4 text-muted">No recent receipts recorded.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($recentPayments as $rp): ?>
                      <tr>
                        <td class="text-muted small"><?= format_date($rp['payment_date'] ?? date('Y-m-d')) ?></td>
                        <td>
                          <div class="fw-bold text-dark text-truncate" style="max-width: 140px;"><?= e($rp['customer_name'] ?? 'Applicant') ?></div>
                          <div class="text-muted small" style="font-size: 0.7rem;"><?= e($rp['application_number'] ?? '') ?></div>
                        </td>
                        <td class="text-end fw-bold text-success"><?= format_currency((float)$rp['amount']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <!-- Native Mobile App Card View (< 768px) -->
            <div class="d-md-none bento-mobile-card-list">
              <?php if (empty($recentPayments)): ?>
                <div class="text-center py-4 text-muted bg-light rounded-4 border">No recent receipts recorded.</div>
              <?php else: ?>
                <?php foreach ($recentPayments as $rp): ?>
                  <div class="bento-mobile-app-card p-2.5 mb-2">
                    <div class="d-flex align-items-center justify-content-between">
                      <div>
                        <div class="fw-bold text-dark small"><?= e($rp['customer_name'] ?? 'Applicant') ?></div>
                        <div class="text-muted" style="font-size: 0.7rem;"><?= e($rp['application_number'] ?? '') ?> &bull; <?= format_date($rp['payment_date'] ?? date('Y-m-d')) ?></div>
                      </div>
                      <div class="fw-bold text-success fs-6"><?= format_currency((float)$rp['amount']) ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- =====================================================================
         SECTION 9: STAFF WORKLOAD & AUDIT ACTIVITY TRAIL
         ===================================================================== -->
    <div class="row g-4">
      <?php if ($canViewStaff): ?>
        <div class="col-12 col-lg-6">
          <div class="bento-card">
            <div class="bento-card-head">
              <h3 class="bento-card-title">
                <i class="fa-solid fa-users text-primary me-2"></i> Staff Workload Distribution
              </h3>
              <a href="/staff" class="bento-pill-btn-sm">Manage Team &rarr;</a>
            </div>

            <!-- Desktop Full Table View (>= 768px) -->
            <div class="bento-table-wrap d-none d-md-block">
              <table class="bento-table">
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
                    <tr><td colspan="5" class="text-center py-4 text-muted">No active staff members.</td></tr>
                  <?php else: ?>
                    <?php foreach ($staffWorkload as $sw): ?>
                      <tr>
                        <td>
                          <div class="fw-bold text-dark"><?= e($sw['name']) ?></div>
                          <div class="text-muted small" style="font-size: 0.7rem;"><?= e($sw['designation']) ?></div>
                        </td>
                        <td><span class="badge bg-light text-secondary border"><?= e($sw['role_name']) ?></span></td>
                        <td class="text-center"><span class="badge bg-light text-dark border fw-bold"><?= $sw['active_cases'] ?></span></td>
                        <td class="text-center">
                          <?php if ($sw['urgent_cases'] > 0): ?>
                            <span class="badge bg-danger"><?= $sw['urgent_cases'] ?></span>
                          <?php else: ?>
                            <span class="text-muted small">0</span>
                          <?php endif; ?>
                        </td>
                        <td class="text-center"><span class="badge bg-light text-secondary border"><?= $sw['pending_tasks'] ?></span></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <!-- Native Mobile App Card View (< 768px) -->
            <div class="d-md-none bento-mobile-card-list">
              <?php if (empty($staffWorkload)): ?>
                <div class="text-center py-4 text-muted bg-light rounded-4 border">No active staff members.</div>
              <?php else: ?>
                <?php foreach ($staffWorkload as $sw): ?>
                  <div class="bento-mobile-app-card p-2.5 mb-2">
                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                      <div>
                        <div class="fw-bold text-dark small"><?= e($sw['name']) ?></div>
                        <div class="text-muted" style="font-size: 0.7rem;"><?= e($sw['designation'] ?? $sw['role_name']) ?></div>
                      </div>
                      <span class="badge bg-light text-secondary border"><?= e($sw['role_name']) ?></span>
                    </div>
                    <div class="d-flex align-items-center justify-content-around p-1.5 bg-light rounded-3 border text-center" style="font-size: 0.72rem;">
                      <div><span class="text-muted d-block">Active</span><strong class="text-dark"><?= $sw['active_cases'] ?></strong></div>
                      <div><span class="text-muted d-block">Urgent</span><strong class="<?= $sw['urgent_cases'] > 0 ? 'text-danger' : 'text-muted' ?>"><?= $sw['urgent_cases'] ?></strong></div>
                      <div><span class="text-muted d-block">Tasks</span><strong class="text-dark"><?= $sw['pending_tasks'] ?></strong></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="<?= $canViewStaff ? 'col-12 col-lg-6' : 'col-12' ?>">
        <div class="bento-card">
          <div class="bento-card-head">
            <h3 class="bento-card-title">
              <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> 
              <?= $canViewAudit ? 'Operations Audit Trail' : 'My Shift Activity' ?>
            </h3>
            <?php if ($canViewAudit): ?>
              <a href="/audit-logs" class="bento-pill-btn-sm">All Logs &rarr;</a>
            <?php endif; ?>
          </div>

          <!-- Operations Audit Trail: Highly Responsive Stream UI (Laptop & Mobile Optimized) -->
          <?php
            $getActivityMeta = function(string $action) {
                $a = strtoupper($action);
                if (str_contains($a, 'LOGIN') || str_contains($a, 'AUTH')) {
                    return ['icon' => 'fa-arrow-right-to-bracket', 'bg' => 'rgba(225, 29, 72, 0.1)', 'color' => 'var(--bento-primary)'];
                } elseif (str_contains($a, 'DOC') || str_contains($a, 'FILE') || str_contains($a, 'UPLOAD')) {
                    return ['icon' => 'fa-file-lines', 'bg' => 'rgba(14, 165, 233, 0.12)', 'color' => '#0284C7'];
                } elseif (str_contains($a, 'OCR') || str_contains($a, 'PASSPORT')) {
                    return ['icon' => 'fa-id-card', 'bg' => 'rgba(168, 85, 247, 0.12)', 'color' => '#9333EA'];
                } elseif (str_contains($a, 'PAY') || str_contains($a, 'INVOICE') || str_contains($a, 'RECEIPT')) {
                    return ['icon' => 'fa-receipt', 'bg' => 'rgba(16, 185, 129, 0.12)', 'color' => '#059669'];
                } elseif (str_contains($a, 'ERROR') || str_contains($a, 'EXCEPTION')) {
                    return ['icon' => 'fa-triangle-exclamation', 'bg' => 'rgba(239, 68, 68, 0.12)', 'color' => '#DC2626'];
                } elseif (str_contains($a, 'TASK')) {
                    return ['icon' => 'fa-list-check', 'bg' => 'rgba(245, 158, 11, 0.12)', 'color' => '#D97706'];
                }
                return ['icon' => 'fa-clock-rotate-left', 'bg' => '#F1F5F9', 'color' => '#64748B'];
            };
          ?>
          <div class="bento-audit-stream">
            <?php if (empty($recentActivities)): ?>
              <div class="text-center py-4 text-muted bg-light rounded-4 border">
                <i class="fa-solid fa-shield-halved fs-3 text-secondary d-block mb-2"></i>
                No recent session logs recorded.
              </div>
            <?php else: ?>
              <?php foreach ($recentActivities as $act): ?>
                <?php $meta = $getActivityMeta((string)($act['action'] ?? '')); ?>
                <div class="bento-audit-item">
                  <div class="bento-audit-icon-wrap" style="background: <?= $meta['bg'] ?>; color: <?= $meta['color'] ?>;">
                    <i class="fa-solid <?= $meta['icon'] ?>"></i>
                  </div>
                  <div class="bento-audit-content">
                    <div class="bento-audit-top-row">
                      <div class="d-flex align-items-center gap-1.5 flex-wrap">
                        <strong class="bento-audit-user"><?= e($act['user_name'] ?? 'System') ?></strong>
                        <span class="badge bg-light text-secondary border font-monospace bento-audit-badge"><?= e($act['action'] ?? 'SYSTEM') ?></span>
                      </div>
                      <span class="bento-audit-time" title="<?= e($act['created_at'] ?? '') ?>">
                        <i class="fa-regular fa-clock me-1 small"></i><?= !empty($act['created_at']) ? format_datetime($act['created_at']) : '' ?>
                      </span>
                    </div>
                    <p class="bento-audit-desc mb-0">
                      <?= e($act['description'] ?: 'Activity recorded.') ?>
                    </p>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /bento-dashboard-wrap -->
</div><!-- /content-body -->

<!-- ─── MOBILE APP FLOATING BOTTOM DOCK (NATIVE APP EXPERIENCE) ──────────── -->
<div class="bento-mobile-dock">
  <a href="/dashboard" class="bento-dock-item active">
    <i class="fa-solid fa-house"></i>
    <span>Home</span>
  </a>
  <a href="/applications" class="bento-dock-item">
    <i class="fa-solid fa-folder-open"></i>
    <span>Files</span>
  </a>
  <?php if ($canCreateApp): ?>
    <a href="/applications/create" class="bento-dock-fab" title="New Application">
      <i class="fa-solid fa-plus"></i>
    </a>
  <?php endif; ?>
  <a href="/action-center" class="bento-dock-item">
    <i class="fa-solid fa-bolt"></i>
    <span>Actions</span>
  </a>
  <a href="/tasks" class="bento-dock-item">
    <i class="fa-solid fa-list-check"></i>
    <span>Tasks</span>
  </a>
</div>

<!-- ─── MODAL: COMPLETE TASK WITH PROOF (PRESERVED ORIGINAL FUNCTIONALITY) ── -->
<div class="modal fade" id="completeTaskModal" tabindex="-1" aria-labelledby="completeTaskModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: var(--bento-radius-md);">
      <div class="modal-header text-white" style="background: var(--bento-dark); border-top-left-radius: var(--bento-radius-md); border-top-right-radius: var(--bento-radius-md);">
        <h6 class="modal-title fw-bold" id="completeTaskModalLabel">
          <i class="fa-solid fa-clipboard-check me-2" style="color: var(--bento-primary);"></i> Complete Task &amp; Submit Proof
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/tasks/status" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="task_id" id="completeTaskId" value="0">
        <input type="hidden" name="status" value="Completed">

        <div class="modal-body p-4">
          <div class="p-3 bg-light rounded-3 border mb-3">
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
          </div>

          <div class="mb-2">
            <label class="form-label small fw-bold text-dark">
              Attach Proof Document / Screenshot <small class="text-muted fw-normal">(Optional)</small>
            </label>
            <input type="file" name="proof_file" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.zip">
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.25rem;">
            <i class="fa-solid fa-check-double me-1"></i> Submit &amp; Complete Task
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Floating Bento Modern Toaster System -->
<div class="vt-toaster-container" id="vtToaster"></div>

<script>
// Floating Modern Toaster System
window.vtToast = function(message, type = 'success', duration = 4000) {
  let container = document.getElementById('vtToaster');
  if (!container) {
    container = document.createElement('div');
    container.id = 'vtToaster';
    container.className = 'vt-toaster-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  const typeClass = (type === 'danger' || type === 'error') ? 'toast-error' : (type === 'warning' ? 'toast-warning' : (type === 'info' ? 'toast-info' : 'toast-success'));
  toast.className = `vt-toast ${typeClass}`;

  const iconMap = {
    'toast-success': 'fa-solid fa-check',
    'toast-error': 'fa-solid fa-xmark',
    'toast-warning': 'fa-solid fa-triangle-exclamation',
    'toast-info': 'fa-solid fa-circle-info'
  };
  const icon = iconMap[typeClass] || 'fa-solid fa-bell';

  toast.innerHTML = `
    <div class="vt-toast-icon"><i class="${icon}"></i></div>
    <div class="vt-toast-body">${message}</div>
    <button type="button" class="vt-toast-close" aria-label="Close">&times;</button>
    <div class="vt-toast-progress" style="transition-duration: ${duration}ms;"></div>
  `;

  container.appendChild(toast);

  // Trigger progress bar countdown animation
  requestAnimationFrame(() => {
    const bar = toast.querySelector('.vt-toast-progress');
    if (bar) bar.style.width = '0%';
  });

  const dismiss = () => {
    toast.classList.add('toast-hiding');
    setTimeout(() => {
      if (toast.parentNode) toast.parentNode.removeChild(toast);
    }, 250);
  };

  const timer = setTimeout(dismiss, duration);
  toast.querySelector('.vt-toast-close').addEventListener('click', () => {
    clearTimeout(timer);
    dismiss();
  });
};
window.showToast = window.vtToast;

// Complete Task Modal Trigger
function openCompleteTaskModal(taskId, taskTitle) {
  document.getElementById('completeTaskId').value = taskId;
  document.getElementById('completeTaskTitle').textContent = taskTitle;
  document.getElementById('completeNotes').value = '';
  var modal = new bootstrap.Modal(document.getElementById('completeTaskModal'));
  modal.show();
}

// Live Shift Timer simulation for interactive feel
let shiftSeconds = 5048; // 01:24:08
let shiftTimerRunning = true;
const timerEl = document.getElementById('liveShiftTimer');

if (timerEl) {
  setInterval(() => {
    if (shiftTimerRunning) {
      shiftSeconds++;
      const h = String(Math.floor(shiftSeconds / 3600)).padStart(2, '0');
      const m = String(Math.floor((shiftSeconds % 3600) / 60)).padStart(2, '0');
      const s = String(shiftSeconds % 60).padStart(2, '0');
      timerEl.textContent = `${h}:${m}:${s}`;
    }
  }, 1000);
}

function toggleShiftTimer(btn) {
  shiftTimerRunning = !shiftTimerRunning;
  const icon = btn.querySelector('i');
  if (icon) {
    icon.className = shiftTimerRunning ? 'fa-solid fa-pause' : 'fa-solid fa-play';
  }
}

function resetShiftTimer() {
  shiftSeconds = 0;
  if (timerEl) timerEl.textContent = '00:00:00';
}

// Automatic Toast for Flash Messages
<?php if ($flash): ?>
document.addEventListener('DOMContentLoaded', function() {
  window.vtToast(<?= json_encode($flash['message']) ?>, <?= json_encode($flash['type'] === 'danger' ? 'error' : $flash['type']) ?>);
});
<?php endif; ?>
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
