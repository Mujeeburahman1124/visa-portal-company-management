<?php
$pageTitle = 'Staff Roster & Access Control — VISA TRACK';
$flash = get_flash();
$currentView = $_GET['view'] ?? 'table';
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
?>

<!-- Link Dedicated Bento CSS -->
<link rel="stylesheet" href="/assets/css/dashboard-bento.css">
<link rel="stylesheet" href="/assets/css/pages/staff-index.css">

<div class="content-body staff-dashboard">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
        <span><?= $flash['message'] ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Bento Hero Header -->
  <div class="staff-hero">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
          <h1 class="staff-hero-title mb-0">
            <i class="fa-solid fa-users text-danger"></i> Staff Roster &amp; Access Control
          </h1>
          <span class="staff-hero-badge">
            <i class="fa-solid fa-shield-halved me-1"></i> Identity &amp; RBAC Directory
          </span>
        </div>
        <p class="staff-hero-desc">
          Manage operations officers, localized processing desks, role authorizations, workload attributions, and portal security credentials.
        </p>
      </div>

      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="/roles" class="btn btn-outline-light btn-sm px-3 shadow-sm rounded-pill fw-semibold">
          <i class="fa-solid fa-sliders me-1 text-danger"></i> Security Roles
        </a>
        <button class="btn btn-primary btn-sm px-3.5 shadow-sm rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#newStaffModal">
          <i class="fa-solid fa-user-plus me-1"></i> Add Staff Officer
        </button>
      </div>
    </div>
  </div>

  <!-- Bento KPI Metrics Strip (Harmonized with Dashboard / Visa Tracking reference) -->
  <div class="row g-2 g-md-3 mb-4 bento-stat-grid">
    <!-- Card 1: Total Staff (Featured Brand Card) -->
    <div class="col-6 col-lg-3">
      <div class="bento-stat-card featured-card" style="min-height: 120px; padding: 1.1rem 1rem;">
        <div class="bento-stat-top">
          <span class="bento-stat-title text-white opacity-90">Total Staff</span>
          <div class="bento-arrow-circle" style="width: 28px; height: 28px;">
            <i class="fa-solid fa-users text-primary"></i>
          </div>
        </div>
        <div class="bento-stat-value text-white my-1" style="font-size: 1.85rem;"><?= $totalStaff ?></div>
        <div class="bento-stat-badge text-white opacity-90" style="font-size: 0.72rem;">
          <span><i class="fa-solid fa-id-card-clip me-1"></i>Registered officers</span>
        </div>
      </div>
    </div>

    <!-- Card 2: Active Accounts -->
    <div class="col-6 col-lg-3">
      <div class="bento-stat-card" style="min-height: 120px; padding: 1.1rem 1rem;">
        <div class="bento-stat-top">
          <span class="bento-stat-title">Active Accounts</span>
          <div class="bento-arrow-circle" style="width: 28px; height: 28px;">
            <i class="fa-solid fa-user-check text-success"></i>
          </div>
        </div>
        <div class="bento-stat-value my-1 text-success" style="font-size: 1.85rem;"><?= $activeStaff ?></div>
        <div class="bento-stat-badge text-muted" style="font-size: 0.72rem;">
          <span class="text-success fw-semibold"><?= $totalStaff > 0 ? round(($activeStaff / $totalStaff) * 100) : 100 ?>%</span> authorized personnel
        </div>
      </div>
    </div>

    <!-- Card 3: Branch Network -->
    <div class="col-6 col-lg-3">
      <div class="bento-stat-card" style="min-height: 120px; padding: 1.1rem 1rem;">
        <div class="bento-stat-top">
          <span class="bento-stat-title">Operating Desks</span>
          <div class="bento-arrow-circle" style="width: 28px; height: 28px;">
            <i class="fa-solid fa-building text-info"></i>
          </div>
        </div>
        <div class="bento-stat-value my-1 text-primary" style="font-size: 1.85rem;"><?= count($branches) ?></div>
        <div class="bento-stat-badge text-muted" style="font-size: 0.72rem;">
          <span><i class="fa-solid fa-globe text-primary me-1"></i>Regional branches</span>
        </div>
      </div>
    </div>

    <!-- Card 4: Security Roles -->
    <div class="col-6 col-lg-3">
      <div class="bento-stat-card" style="min-height: 120px; padding: 1.1rem 1rem;">
        <div class="bento-stat-top">
          <span class="bento-stat-title">Configured Roles</span>
          <div class="bento-arrow-circle" style="width: 28px; height: 28px;">
            <i class="fa-solid fa-shield-halved text-purple" style="color: #7C3AED;"></i>
          </div>
        </div>
        <div class="bento-stat-value my-1" style="font-size: 1.85rem; color: #7C3AED;"><?= count($roles) ?></div>
        <div class="bento-stat-badge text-muted" style="font-size: 0.72rem;">
          <span><i class="fa-solid fa-lock me-1"></i>RBAC privileges</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Bento Toolbar: Search, Role Filter, Branch Filter & View Switcher -->
  <div class="bento-staff-toolbar mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <form action="/staff" method="GET" class="row g-2 align-items-center flex-grow-1" style="max-width: 820px;">
        <div class="col-12 col-md-5">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-white border-end-0 text-muted rounded-start-pill ps-3">
              <i class="fa-solid fa-magnifying-glass"></i>
            </span>
            <input type="text" name="search" class="form-control border-start-0 py-2" placeholder="Search officer, email, title..." value="<?= e($_GET['search'] ?? '') ?>">
            <button type="submit" class="btn btn-primary btn-sm px-3 rounded-end-pill fw-semibold">Filter</button>
          </div>
        </div>

        <div class="col-6 col-md-3">
          <select name="role_id" class="form-select form-select-sm rounded-pill w-100" onchange="this.form.submit()">
            <option value="">All Security Roles</option>
            <?php foreach ($roles as $r): ?>
              <option value="<?= $r['id'] ?>" <?= ((int)($_GET['role_id'] ?? 0)) === (int)$r['id'] ? 'selected' : '' ?>>
                <?= e($r['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-md-3">
          <select name="branch_id" class="form-select form-select-sm rounded-pill w-100" onchange="this.form.submit()">
            <option value="">All Operating Branches</option>
            <?php foreach ($branches as $b): ?>
              <option value="<?= $b['id'] ?>" <?= ((int)($_GET['branch_id'] ?? 0)) === (int)$b['id'] ? 'selected' : '' ?>>
                <?= e($b['name']) ?> (<?= e($b['code']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if (!empty($_GET['search']) || !empty($_GET['role_id']) || !empty($_GET['branch_id'])): ?>
          <div class="col-auto">
            <a href="/staff" class="btn btn-light btn-sm border rounded-pill px-2.5" title="Clear Filters">
              <i class="fa-solid fa-rotate-left"></i>
            </a>
          </div>
        <?php endif; ?>
      </form>

      <!-- 3-View Segmented Switcher -->
      <div class="view-switcher-pill-group ms-auto ms-md-0" role="group" aria-label="Staff View Mode">
        <button type="button" class="staff-view-btn <?= $currentView === 'table' ? 'active' : '' ?>" onclick="switchStaffView('table')" id="btnStaffViewTable" title="Table View">
          <i class="fa-solid fa-table-list"></i> <span>Table</span>
        </button>
        <button type="button" class="staff-view-btn <?= $currentView === 'grid' ? 'active' : '' ?>" onclick="switchStaffView('grid')" id="btnStaffViewGrid" title="Cards View">
          <i class="fa-solid fa-grip"></i> <span>Cards</span>
        </button>
        <button type="button" class="staff-view-btn <?= $currentView === 'compact' ? 'active' : '' ?>" onclick="switchStaffView('compact')" id="btnStaffViewCompact" title="List View">
          <i class="fa-solid fa-list-ul"></i> <span>List</span>
        </button>
      </div>
    </div>
  </div>

  <!-- ================================================================= -->
  <!-- VIEW OPTION 1: STAFF DATA TABLE VIEW -->
  <!-- ================================================================= -->
  <div id="staffViewTable" class="staff-view-container <?= $currentView === 'table' ? '' : 'd-none' ?>">
    <div class="bento-staff-table-card">
      <div class="table-responsive mb-0">
        <table class="bento-staff-table align-middle">
          <thead>
            <tr>
              <th style="min-width: 220px;">Staff Officer</th>
              <th style="min-width: 140px;">Role &amp; Privilege</th>
              <th style="min-width: 140px;">Assigned Branch</th>
              <th style="min-width: 140px;">Designation</th>
              <th style="min-width: 130px;">Contact Phone</th>
              <th style="min-width: 90px;">Active Visas</th>
              <th style="min-width: 90px;">Tasks</th>
              <th style="min-width: 90px;">Status</th>
              <th class="text-end" style="min-width: 140px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($staff)): ?>
              <tr>
                <td colspan="9" class="text-center py-5 text-muted">
                  <i class="fa-solid fa-users-slash fa-2x mb-2 d-block opacity-25"></i>
                  No staff members match the active criteria.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($staff as $u): ?>
                <?php
                  $isActive = (int)($u['is_active'] ?? 0) === 1;
                  $initials = strtoupper(substr($u['name'] ?? 'U', 0, 1));
                  $isSelf = (int)$u['id'] === (int)($_SESSION['user']['id'] ?? 0);
                ?>
                <tr>
                  <td data-label="Staff Officer">
                    <div>
                      <a href="/staff/show?id=<?= $u['id'] ?>" class="fw-bold text-dark text-decoration-none d-block">
                        <?= e($u['name']) ?>
                      </a>
                      <div class="text-muted small" style="font-size: 0.73rem;">
                        <?= e($u['email']) ?>
                      </div>
                    </div>
                  </td>
                  <td data-label="Role & Privilege">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold px-2 py-0.5 rounded-pill">
                      <?= e($u['role_name']) ?>
                    </span>
                  </td>
                  <td data-label="Assigned Branch" class="small text-muted">
                    <i class="fa-solid fa-building me-1 text-secondary"></i><?= e($u['branch_name'] ?? 'Global Desk') ?>
                  </td>
                  <td data-label="Designation" class="small fw-semibold text-dark">
                    <?= e($u['designation'] ?: 'Operations Officer') ?>
                  </td>
                  <td data-label="Contact Phone" class="small text-muted font-monospace">
                    <?= e($u['phone'] ?: '—') ?>
                  </td>
                  <td data-label="Active Visas">
                    <span class="badge bg-primary rounded-pill px-2.5 py-1 font-monospace"><?= (int)$u['active_applications'] ?></span>
                  </td>
                  <td data-label="Tasks">
                    <span class="badge bg-light text-dark border font-monospace"><?= (int)$u['pending_tasks'] ?></span>
                  </td>
                  <td data-label="Status">
                    <span class="badge <?= $isActive ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> fw-bold px-2 py-0.5 rounded-pill">
                      <?= $isActive ? 'Active' : 'Disabled' ?>
                    </span>
                  </td>
                  <td data-label="Actions" class="text-end">
                    <div class="d-inline-flex align-items-center gap-1 flex-wrap justify-content-end">
                      <a href="/staff/show?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary py-1 px-2 rounded-pill" title="View Profile">
                        <i class="fa-solid fa-eye"></i>
                      </a>
                      <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 rounded-pill" data-bs-toggle="modal" data-bs-target="#editStaffModal<?= $u['id'] ?>" title="Edit Officer">
                        <i class="fa-solid fa-pen-to-square"></i>
                      </button>
                      <form action="/staff/send-activation" method="POST" class="d-inline" onsubmit="return confirm('Send password reset/activation link to <?= e($u['name']) ?>?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="staff_id" value="<?= $u['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-info py-1 px-2 rounded-pill" title="Send Password Link">
                          <i class="fa-solid fa-paper-plane"></i>
                        </button>
                      </form>
                      <?php if (!$isSelf): ?>
                        <button type="button" class="btn btn-sm <?= $isActive ? 'btn-outline-warning' : 'btn-outline-success' ?> py-1 px-2 rounded-pill" data-bs-toggle="modal" data-bs-target="#toggleStaffModal<?= $u['id'] ?>" title="<?= $isActive ? 'Deactivate' : 'Activate' ?>">
                          <i class="fa-solid <?= $isActive ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 rounded-pill" data-bs-toggle="modal" data-bs-target="#deleteStaffModal<?= $u['id'] ?>" title="Delete">
                          <i class="fa-solid fa-trash-can"></i>
                        </button>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ================================================================= -->
  <!-- VIEW OPTION 2: STAFF CARDS GRID -->
  <!-- ================================================================= -->
  <div id="staffViewGrid" class="staff-view-container <?= $currentView === 'grid' ? '' : 'd-none' ?>">
    <div class="row g-3">
      <?php if (empty($staff)): ?>
        <div class="col-12 text-center py-5 text-muted">
          <i class="fa-solid fa-users-slash fa-2x mb-2 d-block opacity-25"></i>
          No staff members match the active criteria.
        </div>
      <?php else: ?>
        <?php foreach ($staff as $u): ?>
          <?php
            $isActive = (int)($u['is_active'] ?? 0) === 1;
            $initials = strtoupper(substr($u['name'] ?? 'U', 0, 1));
            $isSelf = (int)$u['id'] === (int)($_SESSION['user']['id'] ?? 0);
          ?>
          <div class="col-12 col-md-6 col-xl-4">
            <div class="bento-staff-card">
              <div>
                <!-- Top Status Badges -->
                <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-1">
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold px-2.5 py-1 rounded-pill" style="font-size: 0.72rem;">
                    <i class="fa-solid fa-user-shield me-1"></i><?= e($u['role_name']) ?>
                  </span>
                  <span class="badge <?= $isActive ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> fw-bold px-2.5 py-1 rounded-pill" style="font-size: 0.72rem;">
                    <i class="fa-solid <?= $isActive ? 'fa-circle-check' : 'fa-circle-xmark' ?> me-1"></i><?= $isActive ? 'Active' : 'Disabled' ?>
                  </span>
                </div>

                <!-- Officer Name & Designation -->
                <div class="mb-2">
                  <a href="/staff/show?id=<?= $u['id'] ?>" class="fw-bold text-dark text-decoration-none fs-5 text-truncate d-block">
                    <?= e($u['name']) ?>
                  </a>
                  <div class="text-muted small fw-medium" style="font-size: 0.78rem;">
                    <?= e($u['designation'] ?: 'Operations Officer') ?>
                  </div>
                </div>

                <!-- Contact & Location Box -->
                <div class="p-2.5 bg-light rounded-3 border mb-3 small text-muted" style="font-size: 0.76rem;">
                  <div class="text-truncate mb-1"><i class="fa-solid fa-envelope me-1.5 text-danger"></i> <?= e($u['email']) ?></div>
                  <div class="text-truncate mb-1"><i class="fa-solid fa-building me-1.5 text-danger"></i> <?= e($u['branch_name'] ?? 'Global Desk') ?></div>
                  <?php if (!empty($u['phone'])): ?>
                    <div class="text-truncate"><i class="fa-solid fa-phone me-1.5 text-danger"></i> <?= e($u['phone']) ?></div>
                  <?php endif; ?>
                </div>

                <!-- Dual Metrics Bar -->
                <div class="bento-staff-metrics-bar mb-3">
                  <div>
                    <div class="bento-staff-metric-lbl">Active Cases</div>
                    <div class="bento-staff-metric-val text-danger"><?= (int)$u['active_applications'] ?></div>
                  </div>
                  <div>
                    <div class="bento-staff-metric-lbl">Pending Tasks</div>
                    <div class="bento-staff-metric-val text-success"><?= (int)$u['pending_tasks'] ?></div>
                  </div>
                </div>
              </div>

              <!-- Action Buttons Footer -->
              <div class="pt-2.5 border-top d-flex align-items-center justify-content-between gap-1 flex-wrap mt-auto">
                <div class="d-flex align-items-center gap-1">
                  <a href="/staff/show?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger py-1 px-3 rounded-pill fw-semibold" style="font-size: 0.8rem;">
                    <i class="fa-solid fa-eye me-1"></i> Profile
                  </a>
                  <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 rounded-pill" data-bs-toggle="modal" data-bs-target="#editStaffModal<?= $u['id'] ?>" title="Edit Officer">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </button>
                </div>

                <div class="d-flex align-items-center gap-1 ms-auto">
                  <form action="/staff/send-activation" method="POST" class="d-inline" onsubmit="return confirm('Send password reset link to <?= e($u['name']) ?>?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="staff_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-info py-1 px-2 rounded-pill" title="Send Password Link">
                      <i class="fa-solid fa-paper-plane"></i>
                    </button>
                  </form>
                  <?php if (!$isSelf): ?>
                    <button type="button" class="btn btn-sm <?= $isActive ? 'btn-outline-warning' : 'btn-outline-success' ?> py-1 px-2 rounded-pill" data-bs-toggle="modal" data-bs-target="#toggleStaffModal<?= $u['id'] ?>" title="<?= $isActive ? 'Deactivate' : 'Activate' ?>">
                      <i class="fa-solid <?= $isActive ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 rounded-pill" data-bs-toggle="modal" data-bs-target="#deleteStaffModal<?= $u['id'] ?>" title="Delete">
                      <i class="fa-solid fa-trash-can"></i>
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- ================================================================= -->
  <!-- VIEW OPTION 3: COMPACT LIST VIEW -->
  <!-- ================================================================= -->
  <div id="staffViewCompact" class="staff-view-container <?= $currentView === 'compact' ? '' : 'd-none' ?>">
    <div class="bento-staff-table-card">
      <ul class="list-group list-group-flush mb-0">
        <?php foreach ($staff as $u): ?>
          <?php
            $isActive = (int)($u['is_active'] ?? 0) === 1;
            $isSelf = (int)$u['id'] === (int)($_SESSION['user']['id'] ?? 0);
          ?>
          <li class="list-group-item p-3 bento-staff-list-item">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 w-100">
              <div class="min-w-0 flex-grow-1">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                  <a href="/staff/show?id=<?= $u['id'] ?>" class="fw-bold text-dark text-decoration-none fs-6">
                    <?= e($u['name']) ?>
                  </a>
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill" style="font-size: 0.68rem;">
                    <?= e($u['role_name']) ?>
                  </span>
                  <span class="badge <?= $isActive ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> rounded-pill" style="font-size: 0.68rem;">
                    <?= $isActive ? 'Active' : 'Disabled' ?>
                  </span>
                </div>
                <div class="text-muted small text-truncate" style="font-size: 0.76rem;">
                  <span class="me-2"><i class="fa-solid fa-envelope me-1 text-danger"></i><?= e($u['email']) ?></span>
                  <span class="me-2 d-none d-sm-inline">• <i class="fa-solid fa-user-tag me-1 text-secondary"></i><?= e($u['designation'] ?: 'Operations') ?></span>
                  <span class="d-none d-md-inline">• <i class="fa-solid fa-building me-1 text-secondary"></i><?= e($u['branch_name'] ?? 'Global Desk') ?></span>
                </div>
              </div>

              <div class="d-flex align-items-center gap-1.5 ms-auto flex-wrap">
                <span class="badge bg-primary rounded-pill px-2.5 py-1 font-monospace small"><?= (int)$u['active_applications'] ?> Visas</span>
                <a href="/staff/show?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger py-1 px-2.5 rounded-pill" title="View Profile">
                  <i class="fa-solid fa-eye"></i>
                </a>
                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2.5 rounded-pill" data-bs-toggle="modal" data-bs-target="#editStaffModal<?= $u['id'] ?>" title="Edit">
                  <i class="fa-solid fa-pen-to-square"></i>
                </button>
                <form action="/staff/send-activation" method="POST" class="d-inline" onsubmit="return confirm('Send password reset link to <?= e($u['name']) ?>?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="staff_id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-info py-1 px-2 rounded-pill" title="Send Password Link">
                    <i class="fa-solid fa-paper-plane"></i>
                  </button>
                </form>
                <?php if (!$isSelf): ?>
                  <button type="button" class="btn btn-sm <?= $isActive ? 'btn-outline-warning' : 'btn-outline-success' ?> py-1 px-2 rounded-pill" data-bs-toggle="modal" data-bs-target="#toggleStaffModal<?= $u['id'] ?>" title="<?= $isActive ? 'Deactivate' : 'Activate' ?>">
                    <i class="fa-solid <?= $isActive ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 rounded-pill" data-bs-toggle="modal" data-bs-target="#deleteStaffModal<?= $u['id'] ?>" title="Delete">
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<!-- ================================================================= -->
<!-- MODALS -->
<!-- ================================================================= -->

<!-- MODAL: ADD STAFF -->
<div class="modal fade" id="newStaffModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow" style="border-radius: var(--bento-radius);">
      <div class="modal-header bg-dark text-white" style="border-top-left-radius: var(--bento-radius); border-top-right-radius: var(--bento-radius);">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-user-plus me-2 text-danger"></i> Register New Staff Member</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/staff/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Omar Farooq" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Work Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" placeholder="e.g. staff@visatrack.com" required>
            <div class="form-text small text-muted mt-1">
              <i class="fa-solid fa-envelope-circle-check text-primary me-1"></i> A secure activation link will be automatically generated and sent to this email for the staff officer to set their password.
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Security Role <span class="text-danger">*</span></label>
              <select name="role_id" id="newStaffRoleId" class="form-select" required onchange="syncRolePermissionsToStaff(parseInt(this.value, 10))">
                <?php foreach ($roles as $r): ?>
                  <option value="<?= $r['id'] ?>" <?= $r['slug'] === 'processing-staff' ? 'selected' : '' ?>><?= e($r['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Assigned Branch <span class="text-danger">*</span></label>
              <select name="branch_id" class="form-select" required>
                <?php foreach ($branches as $b): ?>
                  <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Job Designation</label>
              <input type="text" name="designation" class="form-control" placeholder="Senior Visa Officer">
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Department</label>
              <input type="text" name="department" class="form-control" placeholder="Operations">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Contact Phone / WhatsApp</label>
            <input type="text" name="phone" class="form-control" placeholder="+971 50 123 4567">
          </div>

          <!-- ─── GRANULAR PERMISSIONS SELECTOR ───────────────────────────── -->
          <?php
            $groupedPermissions = [];
            foreach (($allPermissions ?? []) as $p) {
                if (($p['module'] ?? '') === 'Suppliers') continue;
                $mod = $p['module'] ?: 'General';
                $groupedPermissions[$mod][] = $p;
            }
          ?>
          <div class="mt-4 pt-3 border-top">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div>
                <label class="form-label small fw-bold text-dark mb-0">
                  <i class="fa-solid fa-shield-halved text-primary me-1"></i> Staff Permissions Matrix
                </label>
                <div class="text-muted" style="font-size: 0.72rem;">
                  Pre-selected from the security role. Customize what this officer can view, create, edit or approve.
                </div>
              </div>
              <div class="d-flex gap-1">
                <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2.5 py-0.5" style="font-size: 0.72rem;" onclick="toggleAllStaffPermissions(true)">
                  <i class="fa-solid fa-check-double me-1"></i>Select All
                </button>
                <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2.5 py-0.5" style="font-size: 0.72rem;" onclick="toggleAllStaffPermissions(false)">
                  <i class="fa-solid fa-xmark me-1"></i>Clear All
                </button>
              </div>
            </div>

            <div class="border rounded-3 p-3 bg-light bg-opacity-50" style="max-height: 320px; overflow-y: auto;">
              <div class="row g-2.5">
                <?php foreach ($groupedPermissions as $moduleName => $perms): ?>
                  <div class="col-12 col-md-6">
                    <div class="card h-100 border shadow-none rounded-3 p-2 bg-white">
                      <div class="d-flex align-items-center justify-content-between mb-1.5 pb-1 border-bottom">
                        <span class="fw-bold small text-dark" style="font-size: 0.8rem;">
                          <i class="fa-solid fa-folder-closed text-warning me-1"></i> <?= e($moduleName) ?>
                        </span>
                        <a href="javascript:void(0)" class="text-muted text-decoration-none small" style="font-size: 0.70rem;" onclick="toggleModulePermissions(this, '<?= e(addslashes($moduleName)) ?>')">
                          Toggle All
                        </a>
                      </div>
                      <div class="d-flex flex-column gap-1 module-perm-group" data-module="<?= e($moduleName) ?>">
                        <?php foreach ($perms as $perm): ?>
                          <div class="form-check form-check-sm mb-1">
                            <input class="form-check-input staff-perm-checkbox" type="checkbox" name="permissions[]" value="<?= $perm['id'] ?>" id="perm_new_<?= $perm['id'] ?>">
                            <label class="form-check-label text-dark" for="perm_new_<?= $perm['id'] ?>" style="font-size: 0.78rem; cursor: pointer;">
                              <?= e($perm['name']) ?>
                              <span class="text-muted font-monospace" style="font-size: 0.68rem;">(<?= e($perm['slug']) ?>)</span>
                            </label>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light" style="border-bottom-left-radius: var(--bento-radius); border-bottom-right-radius: var(--bento-radius);">
          <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold rounded-pill px-4"><i class="fa-solid fa-save me-1"></i> Register Officer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- EDIT & TOGGLE MODALS PER OFFICER -->
<?php foreach ($staff as $u): ?>
  <?php $isSelf = (int)$u['id'] === (int)($_SESSION['user']['id'] ?? 0); ?>
  <!-- MODAL: EDIT STAFF -->
  <div class="modal fade" id="editStaffModal<?= $u['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow" style="border-radius: var(--bento-radius);">
        <div class="modal-header bg-dark text-white" style="border-top-left-radius: var(--bento-radius); border-top-right-radius: var(--bento-radius);">
          <h6 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2 text-danger"></i> Edit Staff Officer: <?= e($u['name']) ?></h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form action="/staff/update" method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= $u['id'] ?>">
          <div class="modal-body p-4">
            <div class="mb-3">
              <label class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" value="<?= e($u['name']) ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Email <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" value="<?= e($u['email']) ?>" required>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label small fw-semibold">Role <span class="text-danger">*</span></label>
                <select name="role_id" class="form-select" required>
                  <?php foreach ($roles as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= (int)$u['role_id'] === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold">Branch <span class="text-danger">*</span></label>
                <select name="branch_id" class="form-select" required>
                  <?php foreach ($branches as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= (int)$u['branch_id'] === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label small fw-semibold">Designation</label>
                <input type="text" name="designation" class="form-control" value="<?= e($u['designation'] ?? '') ?>">
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold">Phone</label>
                <input type="text" name="phone" class="form-control" value="<?= e($u['phone'] ?? '') ?>">
              </div>
            </div>
            <div class="mb-0">
              <label class="form-label small fw-semibold">Reset Password (Optional)</label>
              <input type="password" name="password" class="form-control" placeholder="Leave empty to keep current password">
            </div>
          </div>
          <div class="modal-footer bg-light" style="border-bottom-left-radius: var(--bento-radius); border-bottom-right-radius: var(--bento-radius);">
            <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary fw-semibold rounded-pill px-4"><i class="fa-solid fa-save me-1"></i> Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php if (!$isSelf): ?>
    <!-- MODAL: TOGGLE STATUS -->
    <div class="modal fade" id="toggleStaffModal<?= $u['id'] ?>" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: var(--bento-radius);">
          <div class="modal-header bg-dark text-white" style="border-top-left-radius: var(--bento-radius); border-top-right-radius: var(--bento-radius);">
            <h6 class="modal-title fw-bold"><i class="fa-solid fa-user-gear me-2 text-danger"></i> <?= (int)$u['is_active'] === 1 ? 'Disable Staff Account' : 'Enable Staff Account' ?></h6>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form action="/staff/toggle-active" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $u['id'] ?>">
            <div class="modal-body p-4">
              <p class="mb-0 text-dark">
                Are you sure you want to <strong><?= (int)$u['is_active'] === 1 ? 'deactivate' : 'reactivate' ?></strong> access for <strong><?= e($u['name']) ?></strong>?
              </p>
            </div>
            <div class="modal-footer bg-light" style="border-bottom-left-radius: var(--bento-radius); border-bottom-right-radius: var(--bento-radius);">
              <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn <?= (int)$u['is_active'] === 1 ? 'btn-danger' : 'btn-success' ?> fw-semibold rounded-pill px-4">
                <?= (int)$u['is_active'] === 1 ? 'Disable Account' : 'Enable Account' ?>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- MODAL: DELETE STAFF -->
    <div class="modal fade" id="deleteStaffModal<?= $u['id'] ?>" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: var(--bento-radius);">
          <div class="modal-header bg-danger text-white" style="border-top-left-radius: var(--bento-radius); border-top-right-radius: var(--bento-radius);">
            <h6 class="modal-title fw-bold"><i class="fa-solid fa-trash-can me-2"></i> Delete Staff Officer</h6>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form action="/staff/delete" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $u['id'] ?>">
            <div class="modal-body p-4">
              <p class="text-danger fw-bold mb-1">Permanent Deletion Warning</p>
              <p class="text-muted small mb-0">
                Are you sure you want to permanently remove <strong><?= e($u['name']) ?></strong>? All assigned records will require reassignment.
              </p>
            </div>
            <div class="modal-footer bg-light" style="border-bottom-left-radius: var(--bento-radius); border-bottom-right-radius: var(--bento-radius);">
              <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-danger fw-semibold rounded-pill px-4">Delete Staff Member</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>
<?php endforeach; ?>

<script>
function switchStaffView(mode) {
  document.querySelectorAll('.staff-view-container').forEach(el => el.classList.add('d-none'));
  document.querySelectorAll('.staff-view-btn').forEach(btn => btn.classList.remove('active'));

  if (mode === 'grid') {
    const g = document.getElementById('staffViewGrid');
    if (g) g.classList.remove('d-none');
    const b = document.getElementById('btnStaffViewGrid');
    if (b) b.classList.add('active');
    try { localStorage.setItem('vt_staff_view', 'grid'); } catch(e) {}
  } else if (mode === 'compact') {
    const c = document.getElementById('staffViewCompact');
    if (c) c.classList.remove('d-none');
    const b = document.getElementById('btnStaffViewCompact');
    if (b) b.classList.add('active');
    try { localStorage.setItem('vt_staff_view', 'compact'); } catch(e) {}
  } else {
    const t = document.getElementById('staffViewTable');
    if (t) t.classList.remove('d-none');
    const b = document.getElementById('btnStaffViewTable');
    if (b) b.classList.add('active');
    try { localStorage.setItem('vt_staff_view', 'table'); } catch(e) {}
  }
}

const rolePermissionsMap = <?= json_encode($rolePermissionsMap ?? []) ?>;

function syncRolePermissionsToStaff(roleId) {
  const allowedIds = rolePermissionsMap[roleId] || [];
  document.querySelectorAll('.staff-perm-checkbox').forEach(cb => {
    const permId = parseInt(cb.value, 10);
    cb.checked = allowedIds.includes(permId);
  });
}

function toggleAllStaffPermissions(check) {
  document.querySelectorAll('.staff-perm-checkbox').forEach(cb => {
    cb.checked = check;
  });
}

function toggleModulePermissions(btn, moduleName) {
  const group = document.querySelector(`.module-perm-group[data-module="${moduleName}"]`);
  if (!group) return;
  const checkboxes = group.querySelectorAll('.staff-perm-checkbox');
  const allChecked = Array.from(checkboxes).every(cb => cb.checked);
  checkboxes.forEach(cb => cb.checked = !allChecked);
}

document.addEventListener('DOMContentLoaded', function() {
  const saved = (function() {
    try { return localStorage.getItem('vt_staff_view') || 'table'; } catch(e) { return 'table'; }
  })();
  if (saved === 'grid' || saved === 'compact') {
    switchStaffView(saved);
  }

  const roleSelect = document.getElementById('newStaffRoleId');
  if (roleSelect) {
    syncRolePermissionsToStaff(parseInt(roleSelect.value, 10));
  }
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
