<?php
$pageTitle = 'Visa Tracking & Journey Center — MS TRAVEL HUB';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';

$viewMode = $_GET['view'] ?? 'cards';
if ($viewMode === 'grid') {
    $viewMode = 'cards';
}

$buildTrackingUrl = function(array $paramsToMerge = []) {
    $current = $_GET;
    foreach ($paramsToMerge as $k => $v) {
        if ($v === null) {
            unset($current[$k]);
        } else {
            $current[$k] = $v;
        }
    }
    return '/tracking?' . http_build_query($current);
};

// Calculate journey stage step (1 to 4) for visual stepper
$getStageStep = function(string $stage, string $status): int {
    $s = strtolower($stage);
    $st = strtolower($status);
    if ($st === 'approved' || $st === 'completed' || str_contains($s, 'approved') || str_contains($s, 'issued') || str_contains($s, 'collected')) {
        return 4;
    }
    if (str_contains($s, 'submitted') || str_contains($s, 'posted') || str_contains($s, 'process') || str_contains($s, 'embassy') || str_contains($s, 'security')) {
        return 3;
    }
    if (str_contains($s, 'doc') || str_contains($s, 'review') || str_contains($s, 'ready')) {
        return 2;
    }
    return 1;
};
?>

<!-- Load Bento Design System CSS for Unified MS Travel Hub Aesthetic -->
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

    <!-- ─── HEADER & VIEW SWITCHER ────────────────────────────────────────── -->
    <header class="bento-header mb-4">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <h1 class="bento-header-title mb-0">Visa Tracking Center</h1>
          <span class="badge rounded-pill text-white fw-bold px-2.5 py-1" style="background: var(--bento-primary); font-size: 0.72rem;">
            <i class="fa-solid fa-route me-1"></i> Live Hub
          </span>
        </div>
        <p class="bento-header-subtitle">
          Real-time lifecycle monitoring, consular submission stages, and applicant journey milestones.
        </p>
      </div>

      <div class="d-flex align-items-center gap-2 flex-wrap">
        <!-- View Switcher (Cards vs Table) -->
        <div class="btn-group btn-group-sm bg-white shadow-xs border rounded-pill p-1" role="group" aria-label="View Switcher">
          <a href="<?= $buildTrackingUrl(['view' => 'cards']) ?>" 
             class="btn btn-sm rounded-pill px-3 fw-bold <?= $viewMode === 'cards' ? 'text-white' : 'text-dark border-0 bg-transparent' ?>" 
             style="<?= $viewMode === 'cards' ? 'background: var(--bento-primary);' : '' ?>">
            <i class="fa-solid fa-grip me-1.5"></i> Cards
          </a>
          <a href="<?= $buildTrackingUrl(['view' => 'table']) ?>" 
             class="btn btn-sm rounded-pill px-3 fw-bold <?= $viewMode === 'table' ? 'text-white' : 'text-dark border-0 bg-transparent' ?>" 
             style="<?= $viewMode === 'table' ? 'background: var(--bento-primary);' : '' ?>">
            <i class="fa-solid fa-table-list me-1.5"></i> Table
          </a>
        </div>

        <a href="/applications/create" class="bento-btn-primary" style="padding: 0.5rem 1.15rem; font-size: 0.82rem;">
          <i class="fa-solid fa-plus"></i> New Application
        </a>
      </div>
    </header>

    <!-- ─── EXECUTIVE TRACKING KPI METRIC STRIP ───────────────────────────── -->
    <div class="row g-2 g-md-3 mb-4 bento-stat-grid">
      <div class="col-6 col-lg-3">
        <div class="bento-stat-card featured-card" style="min-height: 120px; padding: 1.1rem 1rem;">
          <div class="bento-stat-top">
            <span class="bento-stat-title text-white opacity-90">Total In Network</span>
            <div class="bento-arrow-circle" style="width: 26px; height: 26px;"><i class="fa-solid fa-folder-open"></i></div>
          </div>
          <div class="bento-stat-value text-white my-1" style="font-size: 1.85rem;"><?= (int)($trackingKpis['total'] ?? 0) ?></div>
          <div class="bento-stat-badge text-white opacity-90" style="font-size: 0.7rem;">
            <span>All active applications</span>
          </div>
        </div>
      </div>

      <div class="col-6 col-lg-3">
        <div class="bento-stat-card" style="min-height: 120px; padding: 1.1rem 1rem;">
          <div class="bento-stat-top">
            <span class="bento-stat-title">In Process</span>
            <div class="bento-arrow-circle" style="width: 26px; height: 26px;"><i class="fa-solid fa-arrows-rotate text-primary"></i></div>
          </div>
          <div class="bento-stat-value my-1 text-dark" style="font-size: 1.85rem;"><?= (int)($trackingKpis['active'] ?? 0) ?></div>
          <div class="bento-stat-badge text-muted" style="font-size: 0.7rem;">
            <span>Consular pipeline active</span>
          </div>
        </div>
      </div>

      <div class="col-6 col-lg-3">
        <div class="bento-stat-card" style="min-height: 120px; padding: 1.1rem 1rem;">
          <div class="bento-stat-top">
            <span class="bento-stat-title">Visas Approved</span>
            <div class="bento-arrow-circle" style="width: 26px; height: 26px;"><i class="fa-solid fa-circle-check text-success"></i></div>
          </div>
          <div class="bento-stat-value my-1 text-success" style="font-size: 1.85rem;"><?= (int)($trackingKpis['approved'] ?? 0) ?></div>
          <div class="bento-stat-badge text-muted" style="font-size: 0.7rem;">
            <span>Issued &amp; completed</span>
          </div>
        </div>
      </div>

      <div class="col-6 col-lg-3">
        <div class="bento-stat-card" style="min-height: 120px; padding: 1.1rem 1rem;">
          <div class="bento-stat-top">
            <span class="bento-stat-title">Bottlenecks / Urgent</span>
            <div class="bento-arrow-circle" style="width: 26px; height: 26px;"><i class="fa-solid fa-bolt text-danger"></i></div>
          </div>
          <div class="bento-stat-value my-1 text-danger" style="font-size: 1.85rem;"><?= (int)($trackingKpis['urgent'] ?? 0) ?></div>
          <div class="bento-stat-badge text-muted" style="font-size: 0.7rem;">
            <span>Priority attention required</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ─── QUICK TRACK SEARCH BAR (HIGH IMPACT BRAND ACCENT) ─────────────── -->
    <div class="bento-card mb-4 p-3 p-md-3.5">
      <form action="/tracking" method="GET" class="row g-2 align-items-center">
        <div class="col-12 col-md-auto">
          <span class="fw-bold text-dark d-flex align-items-center gap-2 small">
            <i class="fa-solid fa-magnifying-glass-location text-danger fs-5"></i>
            <span>Quick Passport / Visa Finder:</span>
          </span>
        </div>
        <div class="col-12 col-md-6 col-lg-5">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-search"></i></span>
            <input type="text" name="quick_track" class="form-control border-start-0 font-monospace bg-light" 
                   placeholder="Enter App # (e.g. MSV-2026-000001), Passport, Visa #, or Mobile..." 
                   value="<?= e($_GET['quick_track'] ?? '') ?>" required>
          </div>
        </div>
        <div class="col-12 col-md-auto d-flex gap-2">
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.15rem; font-size: 0.82rem;">
            <i class="fa-solid fa-route me-1"></i> Track Now
          </button>
          <?php if (!empty($_GET['quick_track'])): ?>
            <a href="/tracking" class="btn btn-sm btn-light border rounded-pill px-3">Clear</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- ─── MULTI-CRITERIA ADVANCED FILTER PANEL ───────────────────────────── -->
    <div class="bento-card mb-4 p-3 p-md-3.5">
      <form action="/tracking" method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="view" value="<?= e($viewMode) ?>">

        <div class="col-12 col-sm-6 col-md-4 col-xl-3">
          <label class="form-label small fw-semibold text-secondary mb-1">Applicant Name</label>
          <input type="text" name="name" class="form-control form-control-sm bg-light" placeholder="Search applicant..." value="<?= e($_GET['name'] ?? '') ?>">
        </div>

        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <label class="form-label small fw-semibold text-secondary mb-1">Passport Number</label>
          <input type="text" name="passport" class="form-control form-control-sm bg-light font-monospace" placeholder="Passport #..." value="<?= e($_GET['passport'] ?? '') ?>">
        </div>

        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <label class="form-label small fw-semibold text-secondary mb-1">Mobile / WhatsApp</label>
          <input type="text" name="phone" class="form-control form-control-sm bg-light" placeholder="Phone #..." value="<?= e($_GET['phone'] ?? '') ?>">
        </div>

        <div class="col-6 col-sm-6 col-md-3 col-xl-2">
          <label class="form-label small fw-semibold text-secondary mb-1">Country</label>
          <select name="country_id" class="form-select form-select-sm bg-light">
            <option value="">All Countries</option>
            <?php foreach ($countriesList as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ((int)($_GET['country_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                <?= $c['flag_emoji'] ?> <?= e($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-sm-6 col-md-3 col-xl-3">
          <label class="form-label small fw-semibold text-secondary mb-1">Lifecycle Stage</label>
          <select name="stage" class="form-select form-select-sm bg-light">
            <option value="">All Stages</option>
            <?php foreach ($stages as $stg): ?>
              <?php if ($stg !== 'All'): ?>
                <option value="<?= e($stg) ?>" <?= (($_GET['stage'] ?? '') === $stg) ? 'selected' : '' ?>>
                  <?= e($stg) ?>
                </option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-sm-6 col-md-3 col-xl-2">
          <label class="form-label small fw-semibold text-secondary mb-1">Assigned Officer</label>
          <select name="staff_id" class="form-select form-select-sm bg-light">
            <option value="">All Staff</option>
            <?php foreach ($staffList as $u): ?>
              <option value="<?= $u['id'] ?>" <?= ((int)($_GET['staff_id'] ?? 0) === (int)$u['id']) ? 'selected' : '' ?>>
                <?= e($u['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-sm-6 col-md-3 col-xl-2">
          <label class="form-label small fw-semibold text-secondary mb-1">Date From</label>
          <input type="date" name="date_from" class="form-control form-control-sm bg-light" value="<?= e($_GET['date_from'] ?? '') ?>">
        </div>

        <div class="col-6 col-sm-6 col-md-3 col-xl-2">
          <label class="form-label small fw-semibold text-secondary mb-1">Date To</label>
          <input type="date" name="date_to" class="form-control form-control-sm bg-light" value="<?= e($_GET['date_to'] ?? '') ?>">
        </div>

        <div class="col-6 col-sm-6 col-md-3 col-xl-auto ms-auto d-flex gap-2">
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.15rem; font-size: 0.82rem;">
            <i class="fa-solid fa-filter me-1.5"></i> Apply Filter
          </button>
          <a href="/tracking" class="btn btn-sm btn-light border rounded-pill px-2.5" title="Reset Filters">
            <i class="fa-solid fa-rotate-left"></i>
          </a>
        </div>
      </form>
    </div>

    <!-- ─── RESULTS COUNTER & SUMMARY ────────────────────────────────────── -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 px-1">
      <div class="text-muted small">
        Showing <span class="fw-bold text-dark"><?= count($applications) ?></span> of <span class="fw-bold text-dark"><?= $totalRecords ?></span> tracking cases
        <?php if ($totalPages > 1): ?> &bull; <span class="badge bg-light text-dark border">Page <?= $page ?> of <?= $totalPages ?></span><?php endif; ?>
      </div>
    </div>

    <?php if (empty($applications)): ?>
      <div class="bento-card text-center py-5">
        <i class="fa-solid fa-route fs-1 text-muted opacity-50 mb-3 d-block"></i>
        <h5 class="fw-bold text-dark mb-1">No tracking cases found</h5>
        <p class="text-muted small mb-3">No applications match your active search and filter criteria.</p>
        <a href="/tracking" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Clear All Filters</a>
      </div>
    <?php else: ?>

      <?php if ($viewMode === 'cards'): ?>
        <!-- ─── CARDS GRID VIEW (RESPONSIVE BENTO CARDS) ───────────────────── -->
        <div class="bento-tracking-grid mb-4">
          <?php foreach ($applications as $app): 
            $healthVal = (int)($app['calculated_health'] ?? 100);
            $healthClass = ($healthVal < 50) ? 'bg-danger text-white' : (($healthVal < 80) ? 'bg-warning text-dark' : 'bg-success text-white');
            $prio = strtolower($app['priority'] ?? 'normal');
            $isCrit = ($prio === 'critical' || $prio === 'urgent');
            $curStep = $getStageStep($app['current_stage'] ?? '', $app['status'] ?? '');
            $appDate = !empty($app['application_date']) ? format_date($app['application_date']) : (!empty($app['created_at']) ? format_date($app['created_at']) : '—');
          ?>
            <div class="bento-tracking-card">
              <div>
                <!-- Top Row: App Ref, Priority & Live Health Badge -->
                <div class="bento-tracking-top">
                  <a href="/tracking/show?id=<?= $app['id'] ?>" class="badge bg-light text-dark border fw-bold text-decoration-none px-2.5 py-1.5" style="border-radius: var(--bento-radius-pill);">
                    <?= e($app['application_number']) ?>
                  </a>
                  <div class="d-flex align-items-center gap-1.5">
                    <?php if ($isCrit): ?>
                      <span class="badge bg-danger text-white px-2 py-1" style="border-radius: var(--bento-radius-pill);">
                        <i class="fa-solid fa-bolt me-1"></i><?= e($app['priority']) ?>
                      </span>
                    <?php endif; ?>
                    <span class="badge <?= $healthClass ?> px-2 py-1" style="border-radius: var(--bento-radius-pill);" title="<?= e($app['health_reason'] ?? 'Health Status') ?>">
                      <?= $healthVal ?>% Health
                    </span>
                  </div>
                </div>

                <!-- Applicant Hero Block -->
                <div class="bento-tracking-applicant">
                  <div class="bento-tracking-avatar">
                    <?= strtoupper(substr(trim($app['customer_name'] ?? 'A'), 0, 1)) ?>
                  </div>
                  <div style="min-width: 0;">
                    <h5 class="fw-bold text-dark mb-0.5 text-truncate" style="font-size: 0.98rem;">
                      <?= e($app['customer_name']) ?>
                    </h5>
                    <div class="d-flex align-items-center gap-2 text-muted" style="font-size: 0.74rem;">
                      <span class="font-monospace fw-semibold"><i class="fa-regular fa-id-card me-1"></i><?= e($app['passport_number'] ?: '—') ?></span>
                      <?php if (!empty($app['customer_code'])): ?>
                        <span>&bull; <?= e($app['customer_code']) ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>

                <!-- Destination & Service Banner -->
                <div class="bento-tracking-dest-banner">
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                      <span class="fs-4"><?= $app['flag_emoji'] ?></span>
                      <div>
                        <div class="fw-bold text-dark small"><?= e($app['country_name']) ?></div>
                        <div class="text-muted" style="font-size: 0.72rem;"><?= e($app['service_name']) ?></div>
                      </div>
                    </div>
                    <?php if (!empty($app['service_duration'])): ?>
                      <span class="badge bg-white text-secondary border rounded-pill px-2 py-1" style="font-size: 0.68rem;">
                        <?= e($app['service_duration']) ?>
                      </span>
                    <?php endif; ?>
                  </div>
                </div>

                <!-- 4-Stage Visual Journey Stepper -->
                <div class="bento-journey-stepper">
                  <div class="bento-step-item <?= $curStep >= 1 ? ($curStep > 1 ? 'completed' : 'active') : '' ?>">
                    <div class="bento-step-dot">
                      <?php if ($curStep > 1): ?><i class="fa-solid fa-check"></i><?php else: ?>1<?php endif; ?>
                    </div>
                    <span class="bento-step-label">Registered</span>
                  </div>
                  <div class="bento-step-item <?= $curStep >= 2 ? ($curStep > 2 ? 'completed' : 'active') : '' ?>">
                    <div class="bento-step-dot">
                      <?php if ($curStep > 2): ?><i class="fa-solid fa-check"></i><?php else: ?>2<?php endif; ?>
                    </div>
                    <span class="bento-step-label">Documents</span>
                  </div>
                  <div class="bento-step-item <?= $curStep >= 3 ? ($curStep > 3 ? 'completed' : 'active') : '' ?>">
                    <div class="bento-step-dot">
                      <?php if ($curStep > 3): ?><i class="fa-solid fa-check"></i><?php else: ?>3<?php endif; ?>
                    </div>
                    <span class="bento-step-label">Embassy</span>
                  </div>
                  <div class="bento-step-item <?= $curStep >= 4 ? 'completed active' : '' ?>">
                    <div class="bento-step-dot">
                      <?php if ($curStep >= 4): ?><i class="fa-solid fa-check"></i><?php else: ?>4<?php endif; ?>
                    </div>
                    <span class="bento-step-label">Approved</span>
                  </div>
                </div>

                <!-- Metadata 2x2 Grid -->
                <div class="bento-tracking-meta-grid">
                  <div class="bento-tracking-meta-cell">
                    <div class="bento-tracking-meta-label">Current Stage</div>
                    <div class="bento-tracking-meta-val" title="<?= e($app['current_stage']) ?>">
                      <?= e($app['current_stage']) ?>
                    </div>
                  </div>

                  <div class="bento-tracking-meta-cell">
                    <div class="bento-tracking-meta-label">Assigned Officer</div>
                    <div class="bento-tracking-meta-val" title="<?= e($app['staff_name'] ?? 'Operations') ?>">
                      <i class="fa-regular fa-user me-1 text-muted"></i><?= e($app['staff_name'] ?? 'Operations') ?>
                    </div>
                  </div>

                  <div class="bento-tracking-meta-cell">
                    <div class="bento-tracking-meta-label">Submission Date</div>
                    <div class="bento-tracking-meta-val text-muted">
                      <?= $appDate ?>
                    </div>
                  </div>

                  <div class="bento-tracking-meta-cell">
                    <div class="bento-tracking-meta-label">Visa Number</div>
                    <div class="bento-tracking-meta-val">
                      <?php if (!empty($app['visa_number'])): ?>
                        <span class="text-success fw-bold font-monospace"><i class="fa-solid fa-stamp me-1"></i><?= e($app['visa_number']) ?></span>
                      <?php else: ?>
                        <span class="text-muted fw-normal">In Progress</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Card Action Footer -->
              <div class="bento-tracking-actions">
                <a href="/tracking/show?id=<?= $app['id'] ?>" class="bento-btn-primary flex-grow-1 text-center justify-content-center" style="padding: 0.5rem 1rem; font-size: 0.82rem;">
                  <i class="fa-solid fa-magnifying-glass-location me-1.5"></i> Track Journey &rarr;
                </a>
                <a href="/applications/show?id=<?= $app['id'] ?>" class="btn btn-sm btn-light border rounded-pill px-2.5 py-1.5" title="Open Workspace">
                  <i class="fa-solid fa-folder-open text-muted"></i>
                </a>
                <?php 
                  $mob = preg_replace('/[^0-9]/', '', (string)($app['customer_whatsapp'] ?: $app['customer_mobile']));
                  if (!empty($mob)):
                ?>
                  <a href="https://wa.me/<?= $mob ?>?text=<?= urlencode("Hello " . $app['customer_name'] . ", here is the live tracking update for your visa application (" . $app['application_number'] . "): Status is currently " . $app['current_stage'] . ".") ?>" 
                     target="_blank" class="btn btn-sm btn-success rounded-pill px-2.5 py-1.5" title="Send WhatsApp Update">
                    <i class="fa-brands fa-whatsapp"></i>
                  </a>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

      <?php else: ?>
        <!-- ─── TABULAR TRACKING VIEW ──────────────────────────────────────── -->
        <div class="bento-card mb-4 p-0 overflow-hidden">
          <div class="table-responsive" style="-webkit-overflow-scrolling: touch;">
            <table class="table table-hover align-middle mb-0" style="min-width: 1050px;">
              <thead class="table-light">
                <tr class="small text-muted text-uppercase">
                  <th class="ps-3" style="width: 160px;">App Ref &amp; Date</th>
                  <th style="width: 200px;">Applicant / Passport</th>
                  <th style="width: 200px;">Destination &amp; Service</th>
                  <th style="width: 180px;">Current Stage &amp; Status</th>
                  <th style="width: 140px;">Assigned Officer</th>
                  <th style="width: 90px;">Health</th>
                  <th class="pe-3 text-end" style="width: 170px;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($applications as $app): 
                  $healthVal = (int)($app['calculated_health'] ?? 100);
                  $healthClass = ($healthVal < 50) ? 'bg-danger text-white' : (($healthVal < 80) ? 'bg-warning text-dark' : 'bg-success text-white');
                  $appDate = !empty($app['application_date']) ? format_date($app['application_date']) : (!empty($app['created_at']) ? format_date($app['created_at']) : '—');
                ?>
                  <tr>
                    <td class="ps-3">
                      <a href="/tracking/show?id=<?= $app['id'] ?>" class="fw-bold text-decoration-none" style="color: var(--bento-primary);">
                        <?= e($app['application_number']) ?>
                      </a>
                      <div class="small text-muted"><?= $appDate ?></div>
                    </td>
                    <td>
                      <div class="fw-bold text-dark small"><?= e($app['customer_name']) ?></div>
                      <span class="badge bg-light text-dark font-monospace border" style="font-size: 0.68rem;"><?= e($app['passport_number'] ?: '—') ?></span>
                    </td>
                    <td>
                      <div class="small fw-bold text-dark"><?= $app['flag_emoji'] ?> <?= e($app['country_name']) ?></div>
                      <div class="text-muted" style="font-size: 0.72rem;"><?= e($app['service_name']) ?></div>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark border px-2 py-1 small"><?= e($app['current_stage']) ?></span>
                      <div class="small text-muted mt-0.5" style="font-size: 0.7rem;">Status: <?= e($app['status']) ?></div>
                    </td>
                    <td>
                      <span class="small text-dark"><i class="fa-regular fa-user text-muted me-1"></i><?= e($app['staff_name'] ?? 'Operations') ?></span>
                    </td>
                    <td>
                      <span class="badge <?= $healthClass ?> px-2 py-1" style="border-radius: var(--bento-radius-pill);">
                        <?= $healthVal ?>%
                      </span>
                    </td>
                    <td class="pe-3 text-end">
                      <div class="d-flex align-items-center justify-content-end gap-1">
                        <a href="/tracking/show?id=<?= $app['id'] ?>" class="bento-btn-primary" style="padding: 0.35rem 0.85rem; font-size: 0.75rem;">
                          Track &rarr;
                        </a>
                        <a href="/applications/show?id=<?= $app['id'] ?>" class="btn btn-sm btn-light border rounded-pill px-2 py-1" title="Workspace">
                          <i class="fa-solid fa-folder-open text-muted small"></i>
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>

      <!-- ─── PAGINATION CONTROLS (1, 2, 3... BUTTONS) ─────────────────────── -->
      <?php if ($totalRecords > 0): ?>
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mt-3 mb-4 p-3 bg-white rounded-4 border shadow-xs">
          <div class="text-muted small">
            Showing <span class="fw-bold text-dark"><?= ($offset + 1) ?></span> &ndash; <span class="fw-bold text-dark"><?= min($offset + $perPage, $totalRecords) ?></span> of <span class="fw-bold text-dark"><?= $totalRecords ?></span> cases
          </div>

          <nav aria-label="Tracking Pagination">
            <ul class="pagination pagination-sm mb-0 gap-1 align-items-center">
              <!-- Previous Button -->
              <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link rounded-pill px-3 py-1.5 fw-semibold <?= ($page <= 1) ? 'text-muted bg-light border-0' : 'text-dark border shadow-xs' ?>" 
                   href="<?= ($page > 1) ? $buildTrackingUrl(['page' => $page - 1]) : 'javascript:void(0)' ?>">
                  <i class="fa-solid fa-chevron-left me-1 small"></i> Prev
                </a>
              </li>

              <!-- Numbered Page Buttons (1, 2, 3...) -->
              <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                  <a class="page-link rounded-pill text-center fw-bold <?= ($p === $page) ? 'text-white shadow-xs' : 'text-dark border' ?>" 
                     style="<?= ($p === $page) ? 'background: var(--bento-primary); border-color: var(--bento-primary);' : '' ?> min-width: 34px; padding: 0.35rem 0.65rem;" 
                     href="<?= $buildTrackingUrl(['page' => $p]) ?>">
                    <?= $p ?>
                  </a>
                </li>
              <?php endfor; ?>

              <!-- Next Button -->
              <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link rounded-pill px-3 py-1.5 fw-semibold <?= ($page >= $totalPages) ? 'text-muted bg-light border-0' : 'text-dark border shadow-xs' ?>" 
                   href="<?= ($page < $totalPages) ? $buildTrackingUrl(['page' => $page + 1]) : 'javascript:void(0)' ?>">
                  Next <i class="fa-solid fa-chevron-right ms-1 small"></i>
                </a>
              </li>
            </ul>
          </nav>
        </div>
      <?php endif; ?>

    <?php endif; ?>

  </div><!-- /bento-dashboard-wrap -->
</div><!-- /content-body -->

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
