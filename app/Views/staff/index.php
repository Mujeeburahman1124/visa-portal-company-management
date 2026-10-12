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

  <!-- Bento KPI Metrics Strip -->
  <div class="bento-staff-kpi-grid">
    <!-- Card 1: Total Staff -->
    <div class="bento-staff-kpi-card" style="--kpi-accent: #E11D48; --kpi-icon-bg: #FFF1F2; --kpi-icon-color: #E11D48;">
      <div class="bento-staff-kpi-header">
        <span class="bento-staff-kpi-title">Total Staff</span>
        <div class="bento-staff-kpi-icon">
          <i class="fa-solid fa-users"></i>
        </div>
      </div>
      <div class="bento-staff-kpi-value"><?= $totalStaff ?></div>
      <div class="bento-staff-kpi-footer">
        <i class="fa-solid fa-id-card-clip text-danger"></i>
        <span>Registered enterprise officers</span>
      </div>
    </div>

    <!-- Card 2: Active Accounts -->
    <div class="bento-staff-kpi-card" style="--kpi-accent: #10B981; --kpi-icon-bg: #ECFDF5; --kpi-icon-color: #059669;">
      <div class="bento-staff-kpi-header">
        <span class="bento-staff-kpi-title">Active Accounts</span>
        <div class="bento-staff-kpi-icon">
          <i class="fa-solid fa-user-check"></i>
        </div>
      </div>
      <div class="bento-staff-kpi-value text-success"><?= $activeStaff ?></div>
      <div class="bento-staff-kpi-footer">
        <i class="fa-solid fa-circle-check text-success"></i>
        <span>Authorized operational personnel</span>
      </div>
    </div>

    <!-- Card 3: Branch Network -->
    <div class="bento-staff-kpi-card" style="--kpi-accent: #0284C7; --kpi-icon-bg: #F0F9FF; --kpi-icon-color: #0284C7;">
      <div class="bento-staff-kpi-header">
        <span class="bento-staff-kpi-title">Operating Desks</span>
        <div class="bento-staff-kpi-icon">
          <i class="fa-solid fa-building"></i>
        </div>
      </div>
      <div class="bento-staff-kpi-value text-primary"><?= count($branches) ?></div>
      <div class="bento-staff-kpi-footer">
        <i class="fa-solid fa-globe text-primary"></i>
        <span>Regional operational branches</span>
      </div>
    </div>

    <!-- Card 4: Security Roles -->
    <div class="bento-staff-kpi-card" style="--kpi-accent: #8B5CF6; --kpi-icon-bg: #F5F3FF; --kpi-icon-color: #7C3AED;">
      <div class="bento-staff-kpi-header">
        <span class="bento-staff-kpi-title">Configured Roles</span>
        <div class="bento-staff-kpi-icon">
          <i class="fa-solid fa-shield-halved"></i>
        </div>
      </div>
      <div class="bento-staff-kpi-value" style="color: #7C3AED;"><?= count($roles) ?></div>
      <div class="bento-staff-kpi-footer">
        <i class="fa-solid fa-lock text-purple"></i>
        <span>RBAC privilege structures</span>
      </div>
    </div>
  </div>

  <!-- Bento Toolbar: Search, Role Filter, Branch Filter & View Switcher -->
  <div class="bento-staff-toolbar">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <form action="/staff" method="GET" class="d-flex align-items-center gap-2 flex-grow-1 flex-wrap" style="max-width: 780px;">
        <div class="input-group" style="max-width: 320px;">
          <span class="input-group-text bg-white border-end-0 text-muted rounded-start-pill ps-3">
            <i class="fa-solid fa-magnifying-glass"></i>
          </span>
          <input type="text" name="search" class="form-control form-control-sm border-start-0 py-2" placeholder="Search officer, email, title..." value="<?= e($_GET['search'] ?? '') ?>">
          <button type="submit" class="btn btn-primary btn-sm px-3 rounded-end-pill fw-semibold">Filter</button>
        </div>

        <select name="role_id" class="form-select form-select-sm rounded-pill" style="width: auto; min-width: 160px;" onchange="this.form.submit()">
          <option value="">All Security Roles</option>
          <?php foreach ($roles as $r): ?>
            <option value="<?= $r['id'] ?>" <?= ((int)($_GET['role_id'] ?? 0)) === (int)$r['id'] ? 'selected' : '' ?>>
              <?= e($r['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <select name="branch_id" class="form-select form-select-sm rounded-pill" style="width: auto; min-width: 160px;" onchange="this.form.submit()">
          <option value="">All Operating Branches</option>
          <?php foreach ($branches as $b): ?>
            <option value="<?= $b['id'] ?>" <?= ((int)($_GET['branch_id'] ?? 0)) === (int)$b['id'] ? 'selected' : '' ?>>
              <?= e($b['name']) ?> (<?= e($b['code']) ?>)
            </option>
          <?php endforeach; ?>
        </select>

        <?php if (!empty($_GET['search']) || !empty($_GET['role_id']) || !empty($_GET['branch_id'])): ?>
          <a href="/staff" class="btn btn-light btn-sm border rounded-pill px-2.5" title="Clear Filters">
            <i class="fa-solid fa-rotate-left"></i>
          </a>
        <?php endif; ?>
      </form>

      <!-- 3-View Segmented Switcher -->
      <div class="view-switcher-pill-group" role="group" aria-label="Staff View Mode">
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
                    <div class="d-flex align-items-center gap-2.5">
                      <div class="staff-avatar-initials">
                        <?= $initials ?>
                      </div>
                      <div>
                        <a href="/staff/show?id=<?= $u['id'] ?>" class="fw-bold text-dark text-decoration-none">
                          <?= e($u['name']) ?>
                        </a>
                        <div class="text-muted small" style="font-size: 0.73rem;">
                          <?= e($u['email']) ?>
                        </div>
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
                    <div class="d-inline-flex align-items-center gap-1">
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
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold px-2 py-0.5 rounded-pill" style="font-size: 0.72rem;">
                    <?= e($u['role_name']) ?>
                  </span>
                  <span class="badge <?= $isActive ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> fw-bold px-2 py-0.5 rounded-pill" style="font-size: 0.72rem;">
                    <?= $isActive ? 'Active' : 'Disabled' ?>
                  </span>
                </div>

                <div class="d-flex align-items-center gap-2.5 mb-2">
                  <div class="staff-avatar-initials">
                    <?= $initials ?>
                  </div>
                  <div class="min-w-0">
                    <a href="/staff/show?id=<?= $u['id'] ?>" class="fw-bold text-dark text-decoration-none fs-6 text-truncate d-block">
                      <?= e($u['name']) ?>
                    </a>
                    <div class="text-muted small text-truncate" style="font-size: 0.75rem;">
                      <?= e($u['designation'] ?: 'Operations Officer') ?>
                    </div>
                  </div>
                </div>

                <div class="p-2.5 bg-light rounded-3 border mb-3 small text-muted" style="font-size: 0.75rem;">
                  <div class="text-truncate mb-1"><i class="fa-solid fa-envelope me-1 text-primary"></i> <?= e($u['email']) ?></div>
                  <div class="text-truncate mb-1"><i class="fa-solid fa-building me-1 text-primary"></i> <?= e($u['branch_name'] ?? 'Global Desk') ?></div>
                  <?php if (!empty($u['phone'])): ?>
                    <div class="text-truncate"><i class="fa-solid fa-phone me-1 text-primary"></i> <?= e($u['phone']) ?></div>
                  <?php endif; ?>
                </div>

                <div class="bento-staff-metrics-bar">
                  <div>
                    <div class="bento-staff-metric-lbl">Active Cases</div>
                    <div class="bento-staff-metric-val text-primary"><?= (int)$u['active_applications'] ?></div>
                  </div>
                  <div>
                    <div class="bento-staff-metric-lbl">Pending Tasks</div>
                    <div class="bento-staff-metric-val text-success"><?= (int)$u['pending_tasks'] ?></div>
                  </div>
                </div>
              </div>

              <div class="pt-2 border-top d-flex align-items-center justify-content-between gap-1 flex-wrap mt-2">
                <div class="d-flex align-items-center gap-1">
                  <a href="/staff/show?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary py-1 px-2.5 rounded-pill fw-semibold">
                    <i class="fa-solid fa-eye me-1"></i> Profile
                  </a>
                  <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2.5 rounded-pill" data-bs-toggle="modal" data-bs-target="#editStaffModal<?= $u['id'] ?>">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </button>
                </div>

                <div class="d-flex align-items-center gap-1">
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
            $initials = strtoupper(substr($u['name'] ?? 'U', 0, 1));
            $isSelf = (int)$u['id'] === (int)($_SESSION['user']['id'] ?? 0);
          ?>
          <li class="list-group-item p-3 d-flex flex-wrap align-items-center justify-content-between gap-2.5">
            <div class="d-flex align-items-center gap-2.5 min-w-0">
              <div class="staff-avatar-initials" style="width: 36px; height: 36px; font-size: 0.85rem;">
                <?= $initials ?>
              </div>
              <div class="min-w-0">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <a href="/staff/show?id=<?= $u['id'] ?>" class="fw-bold text-dark text-decoration-none">
                    <?= e($u['name']) ?>
                  </a>
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill" style="font-size: 0.68rem;">
                    <?= e($u['role_name']) ?>
                  </span>
                  <span class="badge <?= $isActive ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> rounded-pill" style="font-size: 0.68rem;">
                    <?= $isActive ? 'Active' : 'Disabled' ?>
                  </span>
                </div>
                <div class="text-muted small mt-0.5 text-truncate" style="font-size: 0.74rem;">
                  <?= e($u['email']) ?> &bull; <?= e($u['designation'] ?: 'Operations') ?> (<?= e($u['branch_name'] ?? 'Global Desk') ?>)
                </div>
              </div>
            </div>

            <div class="d-flex align-items-center gap-1.5">
              <span class="badge bg-primary rounded-pill px-2.5 py-1 font-monospace small"><?= (int)$u['active_applications'] ?> Visas</span>
              <a href="/staff/show?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary py-1 px-2 rounded-pill" title="View Profile">
                <i class="fa-solid fa-eye"></i>
              </a>
              <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 rounded-pill" data-bs-toggle="modal" data-bs-target="#editStaffModal<?= $u['id'] ?>" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
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
  <div class="modal-dialog modal-dialog-centered">
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
              <select name="role_id" class="form-select" required>
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

          <div class="mb-0">
            <label class="form-label small fw-semibold">Contact Phone / WhatsApp</label>
            <input type="text" name="phone" class="form-control" placeholder="+971 50 123 4567">
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

document.addEventListener('DOMContentLoaded', function() {
  const saved = (function() {
    try { return localStorage.getItem('vt_staff_view') || 'table'; } catch(e) { return 'table'; }
  })();
  if (saved === 'grid' || saved === 'compact') {
    switchStaffView(saved);
  }
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
