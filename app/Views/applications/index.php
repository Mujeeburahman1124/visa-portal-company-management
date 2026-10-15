<?php
$pageTitle = 'Visa Applications Registry — MS TRAVEL HUB';
$flash = get_flash();
$activePreset = $_GET['preset'] ?? 'all';
$activeStatus = $_GET['status'] ?? '';
$activePriority = $_GET['priority'] ?? '';
$activeCountry = (int)($_GET['country_id'] ?? 0);
$activeService = (int)($_GET['service_id'] ?? 0);
$activeSupplier = (int)($_GET['supplier_id'] ?? 0);
$activeStaff = (int)($_GET['staff_id'] ?? 0);
$activeAgent = (int)($_GET['agent_id'] ?? 0);
$searchQuery = trim($_GET['search'] ?? '');

$currentView = $_GET['view'] ?? 'table';
if (!in_array($currentView, ['table', 'grid', 'compact'], true)) {
    $currentView = 'table';
}

require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';

// Stage step helper (1 to 4) for visual journey steppers
$getStageStep = function(string $stage, string $status): int {
    $s = strtolower($stage);
    $st = strtolower($status);
    if ($st === 'approved' || $st === 'completed' || str_contains($s, 'approved') || str_contains($s, 'issued') || str_contains($s, 'collected')) {
        return 4;
    }
    if (str_contains($s, 'submitted') || str_contains($s, 'posted') || str_contains($s, 'process') || str_contains($s, 'embassy') || str_contains($s, 'security')) {
        return 3;
    }
    if (str_contains($s, 'doc') || str_contains($s, 'review') || str_contains($s, 'ready') || str_contains($s, 'verification')) {
        return 2;
    }
    return 1;
};
?>

<!-- Load Bento Design System CSS (Referencing Dashboard & Visa Tracking UI) -->
<link rel="stylesheet" href="/assets/css/dashboard-bento.css?v=<?= time() ?>">
<link rel="stylesheet" href="/assets/css/pages/applications-index.css?v=<?= time() ?>">

<div class="content-body p-0 apps-page">
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

    <!-- ─── 1. BENTO HEADER & ACTIONS ────────────────────────────────────── -->
    <header class="bento-header mb-4">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
          <h1 class="bento-header-title mb-0">Applications Registry</h1>
          <span class="badge rounded-pill text-white fw-bold px-2.5 py-1" style="background: var(--bento-primary); font-size: 0.72rem;">
            <i class="fa-solid fa-folder-open me-1"></i> Live Registry
          </span>
          <span class="badge bg-white text-secondary border rounded-pill px-2.5 py-1 small">
            <?= count($applications) ?> <?= count($applications) === 1 ? 'record' : 'records' ?>
          </span>
        </div>
        <p class="bento-header-subtitle">
          Unified operations center for managing, filtering, and executing global visa application files.
        </p>
      </div>

      <div class="apps-header-actions">
        <!-- 3-Mode View Switcher -->
        <div class="apps-segmented" role="group" aria-label="View Mode">
          <button type="button" class="app-view-btn <?= $currentView === 'table' ? 'active' : '' ?>" onclick="switchAppView('table')" id="btnAppViewTable" title="Table View">
            <i class="fa-solid fa-table-list"></i> <span class="d-none d-sm-inline">Table</span>
          </button>
          <button type="button" class="app-view-btn <?= $currentView === 'grid' ? 'active' : '' ?>" onclick="switchAppView('grid')" id="btnAppViewGrid" title="Journey Cards View">
            <i class="fa-solid fa-grip"></i> <span class="d-none d-sm-inline">Cards</span>
          </button>
          <button type="button" class="app-view-btn <?= $currentView === 'compact' ? 'active' : '' ?>" onclick="switchAppView('compact')" id="btnAppViewCompact" title="Compact List View">
            <i class="fa-solid fa-list-ul"></i> <span class="d-none d-sm-inline">Compact</span>
          </button>
        </div>

        <a href="/tracking" class="bento-btn-outline" title="Open Visual Tracking Center">
          <i class="fa-solid fa-route"></i> Tracking Hub
        </a>
        <a href="/applications/create" class="bento-btn-primary" title="Create New Visa Application">
          <i class="fa-solid fa-plus"></i> New Application
        </a>
      </div>
    </header>

    <!-- ─── 2. EXECUTIVE KPI PRESET METRIC STRIP ──────────────────────────── -->
    <div class="apps-kpi-grid">
      <!-- All Files -->
      <a href="/applications" class="apps-kpi <?= ($activePreset === 'all' && empty($activeStatus) && empty($activePriority)) ? 'is-active' : '' ?>">
        <div class="apps-kpi-top">
          <span class="apps-kpi-label">All Files</span>
          <div class="apps-kpi-icon"><i class="fa-solid fa-folder-open"></i></div>
        </div>
        <div class="apps-kpi-value"><?= (int)($counts['all'] ?? 0) ?></div>
        <span class="apps-muted">Full active roster</span>
      </a>

      <!-- My Assigned -->
      <a href="/applications?preset=my_assigned" class="apps-kpi tone-info <?= $activePreset === 'my_assigned' ? 'is-active' : '' ?>">
        <div class="apps-kpi-top">
          <span class="apps-kpi-label">My Desk</span>
          <div class="apps-kpi-icon"><i class="fa-solid fa-user-check"></i></div>
        </div>
        <div class="apps-kpi-value text-info"><?= (int)($counts['my_assigned'] ?? 0) ?></div>
        <span class="apps-muted">Assigned to you</span>
      </a>

      <!-- Critical / Urgent -->
      <a href="/applications?preset=critical" class="apps-kpi tone-danger <?= $activePreset === 'critical' ? 'is-active' : '' ?>">
        <div class="apps-kpi-top">
          <span class="apps-kpi-label">Critical</span>
          <div class="apps-kpi-icon"><i class="fa-solid fa-fire"></i></div>
        </div>
        <div class="apps-kpi-value text-danger"><?= (int)($counts['critical'] ?? 0) ?></div>
        <span class="apps-muted">Urgent turnaround</span>
      </a>

      <!-- Pending Docs -->
      <a href="/applications?preset=pending_docs" class="apps-kpi tone-warning <?= $activePreset === 'pending_docs' ? 'is-active' : '' ?>">
        <div class="apps-kpi-top">
          <span class="apps-kpi-label">Pending Docs</span>
          <div class="apps-kpi-icon"><i class="fa-solid fa-file-circle-exclamation"></i></div>
        </div>
        <div class="apps-kpi-value text-warning"><?= (int)($counts['pending_docs'] ?? 0) ?></div>
        <span class="apps-muted">Awaiting customer</span>
      </a>

      <!-- Action Required -->
      <a href="/applications?preset=action_required" class="apps-kpi tone-danger <?= $activePreset === 'action_required' ? 'is-active' : '' ?>">
        <div class="apps-kpi-top">
          <span class="apps-kpi-label">Action Req.</span>
          <div class="apps-kpi-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        </div>
        <div class="apps-kpi-value text-danger"><?= (int)($counts['action_required'] ?? 0) ?></div>
        <span class="apps-muted">Consular notices</span>
      </a>

      <!-- Overdue SLAs -->
      <a href="/applications?preset=overdue" class="apps-kpi tone-danger <?= $activePreset === 'overdue' ? 'is-active' : '' ?>">
        <div class="apps-kpi-top">
          <span class="apps-kpi-label">Overdue</span>
          <div class="apps-kpi-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
        </div>
        <div class="apps-kpi-value text-danger"><?= (int)($counts['overdue'] ?? 0) ?></div>
        <span class="apps-muted">Past expected date</span>
      </a>
    </div>

    <!-- ─── 3. SEARCH & ADVANCED FILTER PANEL ────────────────────────────── -->
    <div class="bento-card mb-4 p-3 p-md-3.5 apps-filter">
      <form action="/applications" method="GET" id="appFilterForm">
        <input type="hidden" name="view" id="activeAppViewParam" value="<?= e($currentView) ?>">

        <div class="row g-2.5 align-items-center">
          <!-- Quick Search -->
          <div class="col-12 col-xl-3">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
              <input type="text" name="search" class="form-control border-start-0" placeholder="Search applicant, passport, app #, phone..." value="<?= e($searchQuery) ?>">
            </div>
          </div>

          <!-- Mobile Toggle Button -->
          <div class="col-12 d-xl-none">
            <button type="button" class="btn btn-sm btn-light border w-100 rounded-pill py-2 fw-semibold d-flex align-items-center justify-content-between apps-filter-toggle" onclick="toggleAppsFilters()">
              <span><i class="fa-solid fa-sliders me-1.5 text-primary"></i> Filter by Status, Provider, Country, Offer</span>
              <i class="fa-solid fa-chevron-down text-muted" id="filterChevron"></i>
            </button>
          </div>

          <!-- Filter Dropdowns Container (Responsive) -->
          <div class="col-12 col-xl-9 apps-filter-body" id="appsFilterBody">
            <div class="row g-2 align-items-center m-0">
              
              <!-- 1. All Statuses -->
              <div class="col-6 col-sm-4 col-md-2 p-1">
                <select name="status" class="form-select form-select-sm" title="Filter by Status">
                  <option value="">All Statuses</option>
                  <option value="Draft" <?= $activeStatus === 'Draft' ? 'selected' : '' ?>>Draft</option>
                  <option value="Pending" <?= $activeStatus === 'Pending' ? 'selected' : '' ?>>Pending</option>
                  <option value="Registered" <?= $activeStatus === 'Registered' ? 'selected' : '' ?>>Registered</option>
                  <option value="Documents Pending" <?= $activeStatus === 'Documents Pending' ? 'selected' : '' ?>>Docs Pending</option>
                  <option value="Documents Under Verification" <?= $activeStatus === 'Documents Under Verification' ? 'selected' : '' ?>>Verification</option>
                  <option value="Submitted" <?= $activeStatus === 'Submitted' ? 'selected' : '' ?>>Submitted</option>
                  <option value="In Process" <?= $activeStatus === 'In Process' ? 'selected' : '' ?>>In Process</option>
                  <option value="Action Required" <?= $activeStatus === 'Action Required' ? 'selected' : '' ?>>Action Req.</option>
                  <option value="Approved" <?= $activeStatus === 'Approved' ? 'selected' : '' ?>>Approved</option>
                  <option value="Rejected" <?= $activeStatus === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
              </div>

              <!-- 2. All Providers (Suppliers) -->
              <div class="col-6 col-sm-4 col-md-2 p-1">
                <select name="supplier_id" class="form-select form-select-sm" title="Filter by Provider / Supplier">
                  <option value="">All Providers</option>
                  <?php foreach ($suppliers as $sup): ?>
                    <option value="<?= $sup['id'] ?>" <?= $activeSupplier === (int)$sup['id'] ? 'selected' : '' ?>>
                      <?= e($sup['company_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- 3. All Destinations -->
              <div class="col-6 col-sm-4 col-md-2 p-1">
                <select name="country_id" class="form-select form-select-sm" title="Filter by Destination Country">
                  <option value="">All Destinations</option>
                  <?php foreach ($countries as $ct): ?>
                    <option value="<?= $ct['id'] ?>" <?= $activeCountry === (int)$ct['id'] ? 'selected' : '' ?>>
                      <?= $ct['flag_emoji'] ?> <?= e($ct['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- 4. All Offers (Visa Packages / Services) -->
              <div class="col-6 col-sm-4 col-md-2 p-1">
                <select name="service_id" class="form-select form-select-sm" title="Filter by Visa Offer / Service">
                  <option value="">All Offers</option>
                  <?php foreach ($services as $srv): ?>
                    <option value="<?= $srv['id'] ?>" <?= $activeService === (int)$srv['id'] ? 'selected' : '' ?>>
                      <?= e($srv['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- 5. All Officers / Staff -->
              <div class="col-6 col-sm-4 col-md-2 p-1">
                <select name="staff_id" class="form-select form-select-sm" title="Filter by Assigned Officer">
                  <option value="">All Officers</option>
                  <?php foreach ($staffMembers as $stf): ?>
                    <option value="<?= $stf['id'] ?>" <?= $activeStaff === (int)$stf['id'] ? 'selected' : '' ?>>
                      <?= e($stf['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- 6. Partner Agent Filter -->
              <div class="col-6 col-sm-4 col-md-2 p-1">
                <select name="agent_id" class="form-select form-select-sm" title="Filter by Partner Agent">
                  <option value="">All Agents</option>
                  <?php foreach ($agents ?? [] as $ag): ?>
                    <option value="<?= $ag['id'] ?>" <?= $activeAgent === (int)$ag['id'] ? 'selected' : '' ?>>
                      <?= e($ag['company_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- 6. Filter Action Buttons -->
              <div class="col-12 col-sm-4 col-md-2 ms-auto d-flex align-items-center justify-content-end gap-1.5 p-1 apps-filter-actions">
                <button type="submit" class="bento-btn-primary w-100 justify-content-center" style="padding: 0.42rem 0.9rem; font-size: 0.8rem;">
                  <i class="fa-solid fa-filter me-1"></i> Apply
                </button>
                <a href="/applications" class="btn btn-sm btn-light border rounded-pill px-2.5 py-1.5 text-muted hover-dark" title="Reset All Filters">
                  <i class="fa-solid fa-rotate-left"></i>
                </a>
              </div>

            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- ─── 4. RESULTS VIEW PRESENTATION ─────────────────────────────────── -->
    <?php if (empty($applications)): ?>
      <div class="bento-card text-center py-5">
        <i class="fa-solid fa-folder-open fs-1 text-muted opacity-50 mb-3 d-block"></i>
        <h5 class="fw-bold text-dark mb-1">No applications found</h5>
        <p class="text-muted small mb-3">No files match your active search or filter criteria.</p>
        <div class="d-flex justify-content-center gap-2">
          <a href="/applications" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Clear All Filters</a>
          <a href="/applications/create" class="bento-btn-primary" style="padding: 0.45rem 1.15rem; font-size: 0.82rem;">
            <i class="fa-solid fa-plus me-1"></i> Create Application
          </a>
        </div>
      </div>
    <?php else: ?>

      <!-- ─── 4A. TABLE VIEW (BENTO ADAPTIVE DATA TABLE) ─────────────────── -->
      <div id="appViewTable" class="app-view-container <?= $currentView === 'table' ? '' : 'd-none' ?>">
        <div class="bento-table-wrap apps-table-wrap">
          <table class="bento-table apps-table">
            <thead>
              <tr>
                <th style="min-width: 150px;">Application #</th>
                <th style="min-width: 220px;">Applicant &amp; Passport</th>
                <th style="min-width: 200px;">Visa Service</th>
                <th style="min-width: 170px;">Stage &amp; Journey</th>
                <th style="min-width: 130px;">Status</th>
                <th style="min-width: 110px;">Priority</th>
                <th style="min-width: 160px;">Officer</th>
                <th style="min-width: 130px;">Deadline</th>
                <th class="text-end" style="min-width: 110px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($applications as $app): 
                $status = $app['status'] ?? 'Draft';
                $priority = strtolower($app['priority'] ?? 'normal');
                $curStep = $getStageStep($app['current_stage'] ?? '', $status);

                // Priority chip class
                $prioChipClass = ($priority === 'critical') ? 'apps-chip danger' : (($priority === 'urgent' || $priority === 'high') ? 'apps-chip warning' : 'apps-chip neutral');

                // Status chip class
                $statusClass = 'neutral';
                if ($status === 'Approved' || $status === 'Completed') {
                    $statusClass = 'success';
                } elseif ($status === 'In Process' || $status === 'Submitted') {
                    $statusClass = 'info';
                } elseif ($status === 'Action Required' || str_contains(strtolower($status), 'reject')) {
                    $statusClass = 'danger';
                } elseif (str_contains(strtolower($status), 'pending')) {
                    $statusClass = 'warning';
                }

                // Deadline calculation
                $dlLabel = '—';
                $dlChip = 'apps-chip neutral';
                if (!empty($app['expected_completion_date'])) {
                    $diff = (int)round((strtotime($app['expected_completion_date']) - strtotime(date('Y-m-d'))) / 86400);
                    if ($diff < 0) {
                        $dlLabel = abs($diff) . 'd overdue';
                        $dlChip = 'apps-chip solid-danger';
                    } elseif ($diff === 0) {
                        $dlLabel = 'Due today';
                        $dlChip = 'apps-chip warning';
                    } elseif ($diff <= 3) {
                        $dlLabel = $diff . 'd left';
                        $dlChip = 'apps-chip warning';
                    } else {
                        $dlLabel = format_date($app['expected_completion_date']);
                        $dlChip = 'apps-chip neutral';
                    }
                }

                $passportNum = $app['passport_number'] ?: $app['current_passport'] ?: '—';
                $appDate = !empty($app['application_date']) ? format_date($app['application_date']) : (!empty($app['created_at']) ? format_date($app['created_at']) : '—');
              ?>
                <tr>
                  <!-- Application Number -->
                  <td data-label="Application #">
                    <div>
                      <a href="/applications/show?id=<?= $app['id'] ?>" class="apps-appno">
                        <i class="fa-solid fa-folder me-1"></i><?= e($app['application_number']) ?>
                      </a>
                      <div class="apps-muted mt-1">
                        <i class="fa-regular fa-calendar me-1"></i><?= $appDate ?>
                      </div>
                    </div>
                  </td>

                  <!-- Applicant & Passport -->
                  <td data-label="Applicant">
                    <div class="d-flex align-items-center gap-2">
                      <div class="apps-avatar">
                        <?= strtoupper(substr(trim($app['customer_name'] ?? 'A'), 0, 1)) ?>
                      </div>
                      <div class="overflow-hidden">
                        <div class="fw-bold text-dark fs-6 text-truncate" style="max-width: 170px;"><?= e($app['customer_name']) ?></div>
                        <div class="mt-0.5 d-flex align-items-center gap-1 flex-wrap">
                          <span class="badge bg-light text-secondary border font-monospace px-2 py-0.5" style="font-size: 0.72rem;">
                            <i class="fa-solid fa-passport text-primary me-1"></i><?= e($passportNum) ?>
                          </span>
                          <?php if (!empty($app['agent_code'])): ?>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-1.5 py-0.5" style="font-size: 0.68rem;" title="Partner Agent: <?= e($app['agent_name'] ?? '') ?>">
                              <i class="fa-solid fa-handshake me-1"></i><?= e($app['agent_code']) ?>
                            </span>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- Visa Service & Country -->
                  <td data-label="Service">
                    <div class="d-flex align-items-center gap-2">
                      <span class="fs-4"><?= $app['flag_emoji'] ?></span>
                      <div>
                        <div class="fw-bold text-dark small"><?= e($app['country_name']) ?></div>
                        <div class="text-muted small text-truncate" style="max-width: 180px; font-size: 0.74rem;">
                          <?= e($app['service_name']) ?>
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- Current Stage with Mini Stepper -->
                  <td data-label="Stage">
                    <div>
                      <span class="apps-chip neutral mb-1">
                        <i class="fa-solid fa-circle-dot text-primary me-1" style="font-size: 0.55rem;"></i>
                        <?= e($app['current_stage']) ?>
                      </span>
                      <div class="d-flex align-items-center gap-1 mt-1">
                        <span class="badge rounded-pill <?= $curStep >= 1 ? 'bg-primary' : 'bg-light border text-muted' ?>" style="font-size: 0.62rem; padding: 2px 6px;">1</span>
                        <span class="badge rounded-pill <?= $curStep >= 2 ? 'bg-primary' : 'bg-light border text-muted' ?>" style="font-size: 0.62rem; padding: 2px 6px;">2</span>
                        <span class="badge rounded-pill <?= $curStep >= 3 ? 'bg-primary' : 'bg-light border text-muted' ?>" style="font-size: 0.62rem; padding: 2px 6px;">3</span>
                        <span class="badge rounded-pill <?= $curStep >= 4 ? 'bg-success' : 'bg-light border text-muted' ?>" style="font-size: 0.62rem; padding: 2px 6px;">4</span>
                      </div>
                    </div>
                  </td>

                  <!-- Status & Visa Expiry -->
                  <td data-label="Status">
                    <span class="apps-chip <?= $statusClass ?> mb-1">
                      <?php if ($status === 'Approved' || $status === 'Completed'): ?>
                        <i class="fa-solid fa-circle-check me-1"></i>
                      <?php elseif ($status === 'In Process'): ?>
                        <i class="fa-solid fa-spinner fa-spin-pulse me-1"></i>
                      <?php elseif ($status === 'Submitted'): ?>
                        <i class="fa-solid fa-paper-plane me-1"></i>
                      <?php elseif ($status === 'Action Required'): ?>
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                      <?php endif; ?>
                      <?= e($status) ?>
                    </span>
                    <?php if (!empty($app['visa_status']) || !empty($app['visa_expiry_date']) || $status === 'Approved'): ?>
                      <div class="mt-1 d-flex flex-column gap-1">
                        <div><?= visa_status_badge($app['visa_status'] ?? 'active') ?></div>
                        <?php if (!empty($app['visa_expiry_date'])): ?>
                          <div><?= visa_expiry_badge($app['visa_expiry_date']) ?></div>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </td>

                  <!-- Priority -->
                  <td data-label="Priority">
                    <span class="<?= $prioChipClass ?>">
                      <i class="fa-solid <?= $priority === 'critical' ? 'fa-fire' : ($priority === 'urgent' ? 'fa-bolt' : 'fa-circle-check') ?> me-1"></i>
                      <?= e($app['priority']) ?>
                    </span>
                  </td>

                  <!-- Assigned Officer -->
                  <td data-label="Officer">
                    <div class="d-flex align-items-center gap-1.5">
                      <div class="apps-avatar sm">
                        <?= strtoupper(substr($app['staff_name'] ?? 'U', 0, 1)) ?>
                      </div>
                      <span class="small fw-semibold text-dark text-truncate" style="max-width: 100px;"><?= e($app['staff_name'] ?? 'Unassigned') ?></span>
                      <button type="button" class="btn btn-link btn-sm p-0 text-muted ms-1" onclick="openAppReassignModal(<?= $app['id'] ?>, '<?= e(addslashes($app['application_number'])) ?>', <?= (int)($app['assigned_staff_id'] ?? 0) ?>, event)" title="Reassign staff officer">
                        <i class="fa-solid fa-user-pen" style="font-size: 0.75rem;"></i>
                      </button>
                    </div>
                  </td>

                  <!-- Deadline -->
                  <td data-label="Deadline">
                    <span class="<?= $dlChip ?>">
                      <i class="fa-regular fa-clock me-1"></i><?= $dlLabel ?>
                    </span>
                  </td>

                  <!-- Actions -->
                  <td class="text-end td-actions">
                    <div class="apps-actions">
                      <a href="/applications/show?id=<?= $app['id'] ?>" class="apps-icon-btn primary" title="Open File">
                        <i class="fa-solid fa-eye"></i>
                      </a>
                      <a href="/tracking/show?id=<?= $app['id'] ?>" class="apps-icon-btn" title="View in Tracking Center">
                        <i class="fa-solid fa-route"></i>
                      </a>
                      <a href="/applications/edit?id=<?= $app['id'] ?>" class="apps-icon-btn" title="Edit Application">
                        <i class="fa-solid fa-pen-to-square"></i>
                      </a>
                      <?php if (user_has_role(['super-admin', 'admin'])): ?>
                        <form action="/applications/delete" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete application <?= e($app['application_number']) ?>?');">
                          <?= csrf_field() ?>
                          <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                          <button type="submit" class="apps-icon-btn danger" title="Delete Application">
                            <i class="fa-solid fa-trash-can"></i>
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ─── 4B. JOURNEY CARDS VIEW (BENTO TRACKING CARDS AESTHETIC) ────── -->
      <div id="appViewGrid" class="app-view-container <?= $currentView === 'grid' ? '' : 'd-none' ?>">
        <div class="apps-grid">
          <?php foreach ($applications as $app): 
            $status = $app['status'] ?? 'Draft';
            $priority = strtolower($app['priority'] ?? 'normal');
            $curStep = $getStageStep($app['current_stage'] ?? '', $status);
            $isCrit = ($priority === 'critical' || $priority === 'urgent');

            $statusClass = 'neutral';
            if ($status === 'Approved' || $status === 'Completed') $statusClass = 'success';
            elseif ($status === 'In Process' || $status === 'Submitted') $statusClass = 'info';
            elseif ($status === 'Action Required' || str_contains(strtolower($status), 'reject')) $statusClass = 'danger';
            elseif (str_contains(strtolower($status), 'pending')) $statusClass = 'warning';

            $dlLabel = '—';
            $dlChip = 'apps-chip neutral';
            if (!empty($app['expected_completion_date'])) {
                $diff = (int)round((strtotime($app['expected_completion_date']) - strtotime(date('Y-m-d'))) / 86400);
                if ($diff < 0) {
                    $dlLabel = abs($diff) . 'd overdue';
                    $dlChip = 'apps-chip solid-danger';
                } elseif ($diff === 0) {
                    $dlLabel = 'Due today';
                    $dlChip = 'apps-chip warning';
                } elseif ($diff <= 3) {
                    $dlLabel = $diff . 'd left';
                    $dlChip = 'apps-chip warning';
                } else {
                    $dlLabel = format_date($app['expected_completion_date']);
                    $dlChip = 'apps-chip neutral';
                }
            }

            $passportNum = $app['passport_number'] ?: $app['current_passport'] ?: '—';
            $appDate = !empty($app['application_date']) ? format_date($app['application_date']) : (!empty($app['created_at']) ? format_date($app['created_at']) : '—');
          ?>
            <div class="bento-tracking-card apps-card">
              <div>
                <!-- Top Header Row -->
                <div class="bento-tracking-top">
                  <a href="/applications/show?id=<?= $app['id'] ?>" class="apps-appno">
                    <i class="fa-solid fa-folder me-1"></i><?= e($app['application_number']) ?>
                  </a>
                  <div class="d-flex align-items-center gap-1.5">
                    <?php if ($isCrit): ?>
                      <span class="apps-chip danger">
                        <i class="fa-solid fa-fire me-1"></i><?= e($app['priority']) ?>
                      </span>
                    <?php endif; ?>
                    <span class="apps-chip <?= $statusClass ?>">
                      <?= e($status) ?>
                    </span>
                  </div>
                </div>

                <!-- Applicant Hero Block -->
                <div class="bento-tracking-applicant">
                  <div class="bento-tracking-avatar">
                    <?= strtoupper(substr(trim($app['customer_name'] ?? 'A'), 0, 1)) ?>
                  </div>
                  <div class="overflow-hidden" style="min-width: 0;">
                    <h5 class="fw-bold text-dark mb-0.5 text-truncate" style="font-size: 0.98rem;">
                      <?= e($app['customer_name']) ?>
                    </h5>
                    <div class="d-flex align-items-center gap-2 text-muted" style="font-size: 0.74rem;">
                      <span class="font-monospace fw-semibold"><i class="fa-regular fa-id-card me-1 text-primary"></i><?= e($passportNum) ?></span>
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
                        <div class="text-muted text-truncate" style="font-size: 0.72rem; max-width: 170px;"><?= e($app['service_name']) ?></div>
                      </div>
                    </div>
                    <?php if (!empty($app['entry_type'])): ?>
                      <span class="badge bg-white text-secondary border rounded-pill px-2 py-1" style="font-size: 0.68rem;">
                        <?= e($app['entry_type']) ?>
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
                      <i class="fa-regular fa-user me-1 text-muted"></i><?= e($app['staff_name'] ?? 'Unassigned') ?>
                    </div>
                  </div>

                  <div class="bento-tracking-meta-cell">
                    <div class="bento-tracking-meta-label">Submission Date</div>
                    <div class="bento-tracking-meta-val text-muted">
                      <?= $appDate ?>
                    </div>
                  </div>

                  <div class="bento-tracking-meta-cell">
                    <div class="bento-tracking-meta-label">Deadline / SLA</div>
                    <div class="bento-tracking-meta-val">
                      <span class="<?= $dlChip ?>" style="font-size: 0.72rem;"><?= $dlLabel ?></span>
                    </div>
                  </div>
                </div>

                <?php if (!empty($app['visa_status']) || !empty($app['visa_expiry_date']) || $status === 'Approved'): ?>
                  <div class="mt-2.5 p-2 bg-light rounded-3 border d-flex align-items-center justify-content-between flex-wrap gap-1.5 small">
                    <span class="text-muted fw-semibold" style="font-size: 0.72rem;"><i class="fa-solid fa-stamp text-primary me-1"></i> Visa:</span>
                    <div class="d-flex align-items-center gap-1.5 flex-wrap">
                      <?= visa_status_badge($app['visa_status'] ?? 'active') ?>
                      <?php if (!empty($app['visa_expiry_date'])): ?>
                        <?= visa_expiry_badge($app['visa_expiry_date']) ?>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Card Action Footer -->
              <div class="apps-card-foot">
                <?php if (user_has_role(['super-admin', 'admin', 'branch-manager', 'visa-manager'])): ?>
                  <button type="button" class="btn btn-sm btn-light border rounded-pill px-2.5 py-1 text-muted" style="font-size: 0.74rem;" onclick="openAppReassignModal(<?= $app['id'] ?>, '<?= e(addslashes($app['application_number'])) ?>', <?= (int)($app['assigned_staff_id'] ?? 0) ?>, event)">
                    <i class="fa-solid fa-user-pen me-1"></i> Reassign
                  </button>
                <?php else: ?>
                  <span></span>
                <?php endif; ?>

                <div class="d-flex align-items-center gap-1.5">
                  <a href="/tracking/show?id=<?= $app['id'] ?>" class="apps-icon-btn" title="Track Journey">
                    <i class="fa-solid fa-route"></i>
                  </a>
                  <a href="/applications/edit?id=<?= $app['id'] ?>" class="apps-icon-btn" title="Edit Application">
                    <i class="fa-solid fa-pen"></i>
                  </a>
                  <a href="/applications/show?id=<?= $app['id'] ?>" class="bento-btn-primary" style="padding: 0.4rem 1rem; font-size: 0.78rem;">
                    <i class="fa-solid fa-arrow-right me-1"></i> Open File
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- ─── 4C. COMPACT LIST VIEW (HIGH DENSITY BENTO ROWS) ────────────── -->
      <div id="appViewCompact" class="app-view-container <?= $currentView === 'compact' ? '' : 'd-none' ?>">
        <div class="apps-compact">
          <?php foreach ($applications as $app): 
            $status = $app['status'] ?? 'Draft';
            $priority = strtolower($app['priority'] ?? 'normal');
            $prioChipClass = ($priority === 'critical') ? 'apps-chip danger' : (($priority === 'urgent' || $priority === 'high') ? 'apps-chip warning' : 'apps-chip neutral');

            $statusClass = 'neutral';
            if ($status === 'Approved' || $status === 'Completed') $statusClass = 'success';
            elseif ($status === 'In Process' || $status === 'Submitted') $statusClass = 'info';
            elseif ($status === 'Action Required' || str_contains(strtolower($status), 'reject')) $statusClass = 'danger';
            elseif (str_contains(strtolower($status), 'pending')) $statusClass = 'warning';

            $passportNum = $app['passport_number'] ?: $app['current_passport'] ?: '—';
            $appDate = !empty($app['application_date']) ? format_date($app['application_date']) : (!empty($app['created_at']) ? format_date($app['created_at']) : '—');
          ?>
            <div class="apps-compact-row">
              <!-- Left: Flag + App # + Applicant -->
              <div class="d-flex align-items-center gap-2.5">
                <span class="fs-4 flex-shrink-0"><?= $app['flag_emoji'] ?></span>
                <div class="overflow-hidden">
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="/applications/show?id=<?= $app['id'] ?>" class="fw-bold brand-font text-primary text-decoration-none" style="font-size: 0.88rem;">
                      <?= e($app['application_number']) ?>
                    </a>
                    <span class="<?= $prioChipClass ?>" style="font-size: 0.65rem; padding: 0.15rem 0.5rem;"><?= e($app['priority']) ?></span>
                  </div>
                  <div class="fw-semibold text-dark text-truncate" style="font-size: 0.85rem; max-width: 220px;">
                    <?= e($app['customer_name']) ?>
                  </div>
                </div>
              </div>

              <!-- Country + Service -->
              <div class="cr-meta text-truncate">
                <div class="small fw-semibold text-dark text-truncate"><?= e($app['country_name']) ?> &bull; <?= e($app['service_name']) ?></div>
                <div class="text-muted" style="font-size: 0.72rem;">
                  <i class="fa-solid fa-passport me-1 text-primary"></i><?= e($passportNum) ?>
                </div>
              </div>

              <!-- Stage & Status Badges -->
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="apps-chip neutral" style="font-size: 0.7rem;">
                  <i class="fa-solid fa-circle-dot text-primary me-1" style="font-size: 0.5rem;"></i>
                  <?= e($app['current_stage']) ?>
                </span>
                <span class="apps-chip <?= $statusClass ?>" style="font-size: 0.7rem;">
                  <?= e($status) ?>
                </span>
                <?php if (!empty($app['visa_status']) || !empty($app['visa_expiry_date']) || $status === 'Approved'): ?>
                  <?= visa_status_badge($app['visa_status'] ?? 'active') ?>
                  <?php if (!empty($app['visa_expiry_date'])): ?>
                    <?= visa_expiry_badge($app['visa_expiry_date']) ?>
                  <?php endif; ?>
                <?php endif; ?>
              </div>

              <!-- Assigned Officer -->
              <div class="cr-staff text-end">
                <div class="small fw-semibold text-dark text-truncate" style="max-width: 140px;"><?= e($app['staff_name'] ?? 'Unassigned') ?></div>
                <div class="text-muted" style="font-size: 0.7rem;"><?= $appDate ?></div>
              </div>

              <!-- Action Controls -->
              <div class="d-flex align-items-center gap-1.5 flex-shrink-0">
                <a href="/applications/show?id=<?= $app['id'] ?>" class="apps-icon-btn primary" title="View Application Details">
                  <i class="fa-solid fa-eye"></i>
                </a>
                <a href="/tracking/show?id=<?= $app['id'] ?>" class="apps-icon-btn" title="View in Tracking">
                  <i class="fa-solid fa-route"></i>
                </a>
                <a href="/applications/edit?id=<?= $app['id'] ?>" class="apps-icon-btn" title="Edit">
                  <i class="fa-solid fa-pen"></i>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    <?php endif; ?>

  </div>
</div>

<!-- ─── MODAL: QUICK REASSIGN APPLICATION OFFICER ───────────────────────── -->
<div class="modal fade" id="quickReassignAppModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom py-3 px-4">
        <h5 class="modal-title fw-bold fs-6">
          <i class="fa-solid fa-user-gear text-primary me-2"></i> Reassign Application Officer
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/applications/assign" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="application_id" id="quickReassignAppId">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Application Reference</label>
            <input type="text" id="quickReassignAppNumber" class="form-control bg-light font-monospace fw-bold text-primary" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Assign To Officer <span class="text-danger">*</span></label>
            <select name="staff_id" id="quickReassignStaffSelect" class="form-select" required>
              <option value="">-- Select Staff Officer --</option>
              <?php foreach ($staffMembers as $stf): ?>
                <option value="<?= $stf['id'] ?>"><?= e($stf['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Reassignment Reason / Note</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Provide context for reassigning application..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light border-top py-2.5 px-4">
          <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.25rem; font-size: 0.82rem;">
            <i class="fa-solid fa-save me-1"></i> Confirm Assignment
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openAppReassignModal(appId, appNumber, staffId, evt) {
  if (evt) {
    evt.stopPropagation();
    evt.stopImmediatePropagation();
  }
  document.getElementById('quickReassignAppId').value = appId;
  document.getElementById('quickReassignAppNumber').value = appNumber;
  const sel = document.getElementById('quickReassignStaffSelect');
  if (sel) sel.value = staffId || '';
  const modalEl = document.getElementById('quickReassignAppModal');
  if (modalEl) {
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  }
}

function switchAppView(view) {
  document.querySelectorAll('.app-view-container').forEach(el => el.classList.add('d-none'));
  const target = document.getElementById('appView' + view.charAt(0).toUpperCase() + view.slice(1));
  if (target) target.classList.remove('d-none');

  document.querySelectorAll('.app-view-btn').forEach(btn => btn.classList.remove('active'));
  const activeBtn = document.getElementById('btnAppView' + view.charAt(0).toUpperCase() + view.slice(1));
  if (activeBtn) activeBtn.classList.add('active');

  const viewInput = document.getElementById('activeAppViewParam');
  if (viewInput) viewInput.value = view;

  try { localStorage.setItem('app_active_view', view); } catch (e) {}
}

function toggleAppsFilters() {
  const body = document.getElementById('appsFilterBody');
  const chevron = document.getElementById('filterChevron');
  if (body) {
    body.classList.toggle('open');
    if (chevron) {
      chevron.classList.toggle('fa-chevron-up');
      chevron.classList.toggle('fa-chevron-down');
    }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  if (!urlParams.has('view')) {
    const saved = localStorage.getItem('app_active_view');
    if (saved && ['table', 'grid', 'compact'].includes(saved)) {
      switchAppView(saved);
    }
  }
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
