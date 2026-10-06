<?php
$pageTitle = 'Staff Roster & Access Control — VISA TRACK';
$flash = get_flash();
$currentView = $_GET['view'] ?? 'table';
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
?>

<div class="content-body">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
        <span><?= $flash['message'] ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Page Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <h3 class="fw-bold brand-font text-dark mb-1">Staff Roster &amp; Access Control</h3>
      <p class="text-muted small mb-0">Manage operations officers, branch staff assignments, roles, and administrative privileges.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <!-- 3 View Options Switcher -->
      <div class="btn-group btn-group-sm bg-white shadow-sm border rounded-pill p-1" role="group" aria-label="View Mode">
        <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 fw-semibold staff-view-btn <?= $currentView === 'table' ? 'btn-primary shadow-sm' : 'btn-light text-muted' ?>" onclick="switchStaffView('table')" id="btnStaffViewTable" title="Table View">
          <i class="fa-solid fa-table-list me-1"></i> <span class="d-none d-sm-inline">Table</span>
        </button>
        <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 fw-semibold staff-view-btn <?= $currentView === 'grid' ? 'btn-primary shadow-sm' : 'btn-light text-muted' ?>" onclick="switchStaffView('grid')" id="btnStaffViewGrid" title="Grid Cards View">
          <i class="fa-solid fa-grip me-1"></i> <span class="d-none d-sm-inline">Grid Cards</span>
        </button>
        <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 fw-semibold staff-view-btn <?= $currentView === 'compact' ? 'btn-primary shadow-sm' : 'btn-light text-muted' ?>" onclick="switchStaffView('compact')" id="btnStaffViewCompact" title="Compact List View">
          <i class="fa-solid fa-list-ul me-1"></i> <span class="d-none d-sm-inline">Compact List</span>
        </button>
      </div>

      <a href="/roles" class="btn btn-outline-primary btn-sm px-3 shadow-sm bg-white">
        <i class="fa-solid fa-shield-halved me-1"></i> Roles &amp; Permissions
      </a>
      <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#newStaffModal">
        <i class="fa-solid fa-user-plus me-1"></i> Add Staff Officer
      </button>
    </div>
  </div>

  <!-- Real Statistics Metric Cards (Vibrant Gradients) -->
  <div class="row g-2 g-md-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="stat-card stat-card-blue h-100">
        <div class="stat-icon-wrapper">
          <i class="fa-solid fa-users"></i>
        </div>
        <div class="stat-card-content">
          <div class="stat-title">Total Staff</div>
          <div class="stat-value"><?= $totalStaff ?></div>
          <div class="stat-trend"><i class="fa-solid fa-building me-1"></i>All departments</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3">
      <div class="stat-card stat-card-success h-100">
        <div class="stat-icon-wrapper">
          <i class="fa-solid fa-user-check"></i>
        </div>
        <div class="stat-card-content">
          <div class="stat-title">Active Accounts</div>
          <div class="stat-value"><?= $activeStaff ?></div>
          <div class="stat-trend"><i class="fa-solid fa-circle-check me-1"></i>Authorized</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3">
      <div class="stat-card stat-card-purple h-100">
        <div class="stat-icon-wrapper">
          <i class="fa-solid fa-network-wired"></i>
        </div>
        <div class="stat-card-content">
          <div class="stat-title">Branch Network</div>
          <div class="stat-value"><?= count($branches) ?></div>
          <div class="stat-trend"><i class="fa-solid fa-globe me-1"></i>Global desks</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3">
      <div class="stat-card stat-card-cyan h-100">
        <div class="stat-icon-wrapper">
          <i class="fa-solid fa-shield-halved"></i>
        </div>
        <div class="stat-card-content">
          <div class="stat-title">Security Roles</div>
          <div class="stat-value"><?= count($roles) ?></div>
          <div class="stat-trend"><i class="fa-solid fa-lock me-1"></i>RBAC configured</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Search & Filter Form -->
  <div class="card card-enterprise mb-4 shadow-sm">
    <div class="card-body p-3">
      <form action="/staff" method="GET" class="row g-2 align-items-center">
        <div class="col-12 col-md-4">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search officer name, email, designation..." value="<?= e($_GET['search'] ?? '') ?>">
          </div>
        </div>

        <div class="col-6 col-md-3">
          <select name="role_id" class="form-select form-select-sm">
            <option value="">All Security Roles</option>
            <?php foreach ($roles as $r): ?>
              <option value="<?= $r['id'] ?>" <?= ((int)($_GET['role_id'] ?? 0)) === (int)$r['id'] ? 'selected' : '' ?>>
                <?= e($r['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-md-3">
          <select name="branch_id" class="form-select form-select-sm">
            <option value="">All Operating Branches</option>
            <?php foreach ($branches as $b): ?>
              <option value="<?= $b['id'] ?>" <?= ((int)($_GET['branch_id'] ?? 0)) === (int)$b['id'] ? 'selected' : '' ?>>
                <?= e($b['name']) ?> (<?= e($b['code']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 col-md-2 d-flex gap-1">
          <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold"><i class="fa-solid fa-filter me-1"></i> Filter</button>
          <a href="/staff" class="btn btn-light btn-sm border" title="Clear Filters"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
      </form>
    </div>
  </div>

  <!-- ================================================================= -->
  <!-- VIEW OPTION 1: STAFF DATA TABLE VIEW -->
  <!-- ================================================================= -->
  <div id="staffViewTable" class="staff-view-container <?= $currentView === 'table' ? '' : 'd-none' ?>">
    <div class="card card-enterprise shadow-sm">
    <div class="table-responsive">
      <table class="table table-modern align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th scope="col" style="min-width: 200px;">Staff Officer</th>
            <th scope="col" style="min-width: 140px;">Security Role</th>
            <th scope="col" style="min-width: 170px;">Designation &amp; Branch</th>
            <th scope="col" style="min-width: 130px;">Contact</th>
            <th scope="col" class="text-center" style="min-width: 100px;">Active Cases</th>
            <th scope="col" class="text-center" style="min-width: 100px;">Pending Tasks</th>
            <th scope="col" class="text-center" style="min-width: 90px;">Status</th>
            <th scope="col" class="text-end" style="min-width: 100px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($staff)): ?>
            <tr><td colspan="8" class="text-center py-5 text-muted">No staff members match the active criteria.</td></tr>
          <?php else: ?>
            <?php foreach ($staff as $u): ?>
              <?php
                $isActive = (int)$u['is_active'] === 1;
                $initials = strtoupper(substr($u['name'], 0, 1));
              ?>
              <tr>
                <!-- Staff Name & Email -->
                <td>
                  <div class="d-flex align-items-center gap-2.5">
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold shadow-sm flex-shrink-0" style="width: 38px; height: 38px; font-size: 0.95rem; background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);">
                      <?= $initials ?>
                    </div>
                    <div class="min-w-0">
                      <a href="/staff/show?id=<?= $u['id'] ?>" class="fw-bold text-dark text-decoration-none hover-primary d-block text-truncate" style="max-width: 220px;">
                        <?= e($u['name']) ?>
                      </a>
                      <div class="text-muted text-truncate mt-0.5" style="font-size: 0.76rem; max-width: 220px;">
                        <?= e($u['email']) ?>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Role Badge -->
                <td>
                  <span class="badge bg-primary-subtle text-primary fw-bold px-2.5 py-1" style="font-size: 0.75rem; border: 1px solid var(--vt-primary-border);">
                    <i class="fa-solid fa-shield-halved me-1"></i><?= e($u['role_name']) ?>
                  </span>
                </td>

                <!-- Designation & Branch -->
                <td>
                  <div>
                    <div class="fw-semibold small text-dark text-truncate" style="max-width: 200px;"><?= e($u['designation'] ?: 'Operations Specialist') ?></div>
                    <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 0.72rem;">
                      <i class="fa-solid fa-building text-primary opacity-75 flex-shrink-0"></i>
                      <span class="text-truncate" style="max-width: 180px;"><?= e($u['branch_name'] ?? 'Main Office') ?></span>
                    </div>
                  </div>
                </td>

                <!-- Contact -->
                <td>
                  <div class="d-flex align-items-center gap-1.5 text-secondary small">
                    <i class="fa-solid fa-phone text-muted flex-shrink-0" style="font-size: 0.7rem;"></i>
                    <span class="text-nowrap"><?= e($u['phone'] ?: '—') ?></span>
                  </div>
                </td>

                <!-- Active Workload -->
                <td class="text-center">
                  <span class="badge <?= (int)$u['active_applications'] > 0 ? 'bg-primary' : 'bg-light text-muted border' ?> rounded-pill px-2.5 py-1">
                    <?= (int)$u['active_applications'] ?> cases
                  </span>
                </td>

                <!-- Pending Tasks -->
                <td class="text-center">
                  <span class="badge <?= (int)$u['pending_tasks'] > 0 ? 'bg-warning text-dark' : 'bg-light text-muted border' ?> rounded-pill px-2.5 py-1">
                    <?= (int)$u['pending_tasks'] ?> tasks
                  </span>
                </td>

                <!-- Status -->
                <td class="text-center">
                  <span class="badge <?= $isActive ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> fw-bold px-2.5 py-1" style="font-size: 0.73rem;">
                    <i class="fa-solid <?= $isActive ? 'fa-circle-check' : 'fa-ban' ?> me-1"></i><?= $isActive ? 'Active' : 'Disabled' ?>
                  </span>
                </td>

                <!-- Action Dropdown / Buttons -->
                <td class="text-end">
                  <div class="d-inline-flex align-items-center gap-1">
                    <a href="/staff/show?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary py-1 px-2 fw-semibold" title="View Full Profile">
                      <i class="fa-solid fa-eye"></i>
                    </a>

                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" data-bs-toggle="modal" data-bs-target="#editStaffModal<?= $u['id'] ?>" title="Edit Staff Member">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </button>

                    <form action="/staff/send-activation" method="POST" class="d-inline" onsubmit="return confirm('Send password activation link to <?= e($u['name']) ?> (<?= e($u['email']) ?>)?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="staff_id" value="<?= $u['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-info py-1 px-2" title="Send Password Setup Link">
                        <i class="fa-solid fa-paper-plane"></i>
                      </button>
                    </form>
                    
                    <?php if ($u['id'] !== (int)($_SESSION['user']['id'] ?? 0)): ?>
                      <button type="button" class="btn btn-sm <?= $isActive ? 'btn-outline-warning text-dark' : 'btn-outline-success' ?> py-1 px-2" 
                              data-bs-toggle="modal" data-bs-target="#toggleStaffModal<?= $u['id'] ?>" title="<?= $isActive ? 'Deactivate Staff' : 'Activate Staff' ?>">
                        <i class="fa-solid <?= $isActive ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                      </button>

                      <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2" data-bs-toggle="modal" data-bs-target="#deleteStaffModal<?= $u['id'] ?>" title="Delete Staff Member">
                        <i class="fa-solid fa-trash-can"></i>
                      </button>

                      <!-- DEACTIVATE/ACTIVATE CONFIRMATION MODAL -->
                      <div class="modal fade" id="toggleStaffModal<?= $u['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content border-0 shadow">
                            <div class="modal-header <?= $isActive ? 'bg-warning text-dark' : 'bg-success text-white' ?>">
                              <h6 class="modal-title fw-bold">
                                <i class="fa-solid <?= $isActive ? 'fa-triangle-exclamation' : 'fa-circle-check' ?> me-2"></i>
                                <?= $isActive ? 'Deactivate Staff Member?' : 'Activate Staff Member?' ?>
                              </h6>
                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="/staff/toggle-active" method="POST">
                              <?= csrf_field() ?>
                              <input type="hidden" name="id" value="<?= $u['id'] ?>">
                              <div class="modal-body p-4 text-start">
                                <p class="mb-2">Are you sure you want to <strong><?= $isActive ? 'deactivate' : 'activate' ?></strong> the account for <strong><?= e($u['name']) ?></strong> (<?= e($u['email']) ?>)?</p>
                                <?php if ($isActive): ?>
                                  <div class="alert alert-warning mb-0 small text-dark">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> This will immediately prevent the user from logging in to the system.
                                  </div>
                                <?php else: ?>
                                  <div class="alert alert-success mb-0 small">
                                    <i class="fa-solid fa-circle-check me-1"></i> This will restore login privileges and system access.
                                  </div>
                                <?php endif; ?>
                              </div>
                              <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn <?= $isActive ? 'btn-warning text-dark' : 'btn-success' ?> btn-sm px-3 fw-semibold">
                                  <?= $isActive ? 'Confirm Deactivation' : 'Confirm Activation' ?>
                                </button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>

                      <!-- DELETE CONFIRMATION MODAL -->
                      <div class="modal fade" id="deleteStaffModal<?= $u['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-danger text-white">
                              <h6 class="modal-title fw-bold">
                                <i class="fa-solid fa-trash-can me-2"></i> Delete Staff Member
                              </h6>
                              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="/staff/delete" method="POST">
                              <?= csrf_field() ?>
                              <input type="hidden" name="id" value="<?= $u['id'] ?>">
                              <div class="modal-body p-4 text-start">
                                <p class="mb-2">Are you sure you want to permanently delete <strong><?= e($u['name']) ?></strong> (<?= e($u['email']) ?>)?</p>
                                <div class="alert alert-danger mb-0 small">
                                  <i class="fa-solid fa-triangle-exclamation me-1"></i> This action is permanent. Assigned applications and tasks will be safely unlinked.
                                </div>
                              </div>
                              <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold">
                                  Permanently Delete
                                </button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>
                    <?php endif; ?>

                    <!-- EDIT STAFF MODAL -->
                    <div class="modal fade" id="editStaffModal<?= $u['id'] ?>" tabindex="-1">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow text-start">
                          <div class="modal-header bg-primary text-white">
                            <h6 class="modal-title fw-bold"><i class="fa-solid fa-user-pen me-2"></i> Edit Staff Officer Profile</h6>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                          </div>
                          <form action="/staff/update" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <div class="modal-body p-4">
                              <div class="mb-3">
                                <label class="form-label small fw-semibold">Full Official Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="<?= e($u['name']) ?>" required>
                              </div>
                              <div class="mb-3">
                                <label class="form-label small fw-semibold">Work Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="<?= e($u['email']) ?>" required>
                              </div>
                              <div class="row g-2 mb-3">
                                <div class="col-6">
                                  <label class="form-label small fw-semibold">Security Role <span class="text-danger">*</span></label>
                                  <select name="role_id" class="form-select" required>
                                    <?php foreach ($roles as $r): ?>
                                      <option value="<?= $r['id'] ?>" <?= (int)$u['role_id'] === (int)$r['id'] ? 'selected' : '' ?>>
                                        <?= e($r['name']) ?>
                                      </option>
                                    <?php endforeach; ?>
                                  </select>
                                </div>
                                <div class="col-6">
                                  <label class="form-label small fw-semibold">Operating Branch <span class="text-danger">*</span></label>
                                  <select name="branch_id" class="form-select" required>
                                    <?php foreach ($branches as $b): ?>
                                      <option value="<?= $b['id'] ?>" <?= (int)($u['branch_id'] ?? 1) === (int)$b['id'] ? 'selected' : '' ?>>
                                        <?= e($b['name']) ?>
                                      </option>
                                    <?php endforeach; ?>
                                  </select>
                                </div>
                              </div>
                              <div class="mb-3">
                                <label class="form-label small fw-semibold">Direct Phone / Mobile</label>
                                <input type="text" name="phone" class="form-control" value="<?= e($u['phone'] ?? '') ?>">
                              </div>
                              <div class="row g-2 mb-0">
                                <div class="col-6">
                                  <label class="form-label small fw-semibold">Designation</label>
                                  <input type="text" name="designation" class="form-control" value="<?= e($u['designation'] ?? '') ?>">
                                </div>
                                <div class="col-6">
                                  <label class="form-label small fw-semibold">Department</label>
                                  <input type="text" name="department" class="form-control" value="<?= e($u['department'] ?? '') ?>">
                                </div>
                              </div>
                            </div>
                            <div class="modal-footer bg-light">
                              <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">Save Profile Changes</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>

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
  <!-- VIEW OPTION 2: GRID CARDS VIEW -->
  <!-- ================================================================= -->
  <div id="staffViewGrid" class="staff-view-container <?= $currentView === 'grid' ? '' : 'd-none' ?>">
    <div class="row g-3">
      <?php if (empty($staff)): ?>
        <div class="col-12 text-center py-5 text-muted">No staff members match the active criteria.</div>
      <?php else: ?>
        <?php foreach ($staff as $u): ?>
          <?php
            $isActive = (int)$u['is_active'] === 1;
            $initials = strtoupper(substr($u['name'], 0, 1));
          ?>
          <div class="col-12 col-md-6 col-lg-4">
            <div class="card card-enterprise h-100 shadow-sm border">
              <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                <div>
                  <div class="d-flex align-items-center justify-content-between mb-2.5">
                    <span class="badge bg-primary-subtle text-primary fw-bold px-2.5 py-1" style="font-size: 0.75rem; border: 1px solid var(--vt-primary-border);">
                      <i class="fa-solid fa-shield-halved me-1"></i><?= e($u['role_name']) ?>
                    </span>
                    <span class="badge <?= $isActive ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> fw-bold px-2 py-0.5" style="font-size: 0.72rem;">
                      <i class="fa-solid <?= $isActive ? 'fa-circle-check' : 'fa-ban' ?> me-1"></i><?= $isActive ? 'Active' : 'Disabled' ?>
                    </span>
                  </div>

                  <div class="d-flex align-items-center gap-2.5 mb-3">
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold shadow-sm flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.05rem; background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);">
                      <?= $initials ?>
                    </div>
                    <div class="min-w-0">
                      <h6 class="fw-bold text-dark mb-0 text-truncate">
                        <a href="/staff/show?id=<?= $u['id'] ?>" class="text-dark text-decoration-none hover-primary"><?= e($u['name']) ?></a>
                      </h6>
                      <div class="text-muted text-truncate small" style="font-size: 0.76rem;"><?= e($u['email']) ?></div>
                    </div>
                  </div>

                  <div class="p-2.5 bg-light rounded-3 border mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="text-muted small">Designation:</span>
                      <strong class="text-dark small text-truncate" style="max-width: 160px;"><?= e($u['designation'] ?: 'Operations Specialist') ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="text-muted small">Branch:</span>
                      <span class="small fw-semibold text-secondary text-truncate" style="max-width: 160px;"><?= e($u['branch_name'] ?? 'Main Office') ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="text-muted small">Contact:</span>
                      <span class="small text-dark font-monospace"><?= e($u['phone'] ?: '—') ?></span>
                    </div>
                  </div>

                  <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge <?= (int)$u['active_applications'] > 0 ? 'bg-primary' : 'bg-light text-muted border' ?> rounded-pill px-2.5 py-1 small">
                      <i class="fa-solid fa-briefcase me-1"></i><?= (int)$u['active_applications'] ?> cases
                    </span>
                    <span class="badge <?= (int)$u['pending_tasks'] > 0 ? 'bg-warning text-dark' : 'bg-light text-muted border' ?> rounded-pill px-2.5 py-1 small">
                      <i class="fa-solid fa-list-check me-1"></i><?= (int)$u['pending_tasks'] ?> tasks
                    </span>
                  </div>
                </div>

                <div class="pt-2 border-top d-flex align-items-center justify-content-between gap-1 flex-wrap">
                  <a href="/staff/show?id=<?= $u['id'] ?>" class="btn btn-sm btn-primary px-3 fw-semibold shadow-sm">
                    <i class="fa-solid fa-eye me-1"></i> Profile
                  </a>
                  <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary py-1 px-2" data-bs-toggle="modal" data-bs-target="#editStaffModal<?= $u['id'] ?>" title="Edit">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <form action="/staff/send-activation" method="POST" class="d-inline" onsubmit="return confirm('Send password activation link to <?= e($u['name']) ?>?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="staff_id" value="<?= $u['id'] ?>">
                      <button type="submit" class="btn btn-outline-info py-1 px-2" title="Send Password Activation Link">
                        <i class="fa-solid fa-paper-plane"></i>
                      </button>
                    </form>
                    <?php if ($u['id'] !== (int)($_SESSION['user']['id'] ?? 0)): ?>
                      <button type="button" class="btn <?= $isActive ? 'btn-outline-warning text-dark' : 'btn-outline-success' ?> py-1 px-2" data-bs-toggle="modal" data-bs-target="#toggleStaffModal<?= $u['id'] ?>" title="<?= $isActive ? 'Deactivate' : 'Activate' ?>">
                        <i class="fa-solid <?= $isActive ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                      </button>
                      <button type="button" class="btn btn-outline-danger py-1 px-2" data-bs-toggle="modal" data-bs-target="#deleteStaffModal<?= $u['id'] ?>" title="Delete">
                        <i class="fa-solid fa-trash-can"></i>
                      </button>
                    <?php endif; ?>
                  </div>
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
    <div class="card card-enterprise shadow-sm border">
      <ul class="list-group list-group-flush mb-0">
        <?php if (empty($staff)): ?>
          <li class="list-group-item text-center py-5 text-muted">No staff members match the active criteria.</li>
        <?php else: ?>
          <?php foreach ($staff as $u): ?>
            <?php
              $isActive = (int)$u['is_active'] === 1;
              $initials = strtoupper(substr($u['name'], 0, 1));
            ?>
            <li class="list-group-item p-3 d-flex flex-wrap align-items-center justify-content-between gap-2.5 hover-bg-light">
              <div class="d-flex align-items-center gap-2.5 min-w-0">
                <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0 shadow-sm" style="width: 38px; height: 38px; font-size: 0.9rem; background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);">
                  <?= $initials ?>
                </div>
                <div class="min-w-0">
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="/staff/show?id=<?= $u['id'] ?>" class="fw-bold text-dark text-decoration-none text-truncate" style="max-width: 180px;">
                      <?= e($u['name']) ?>
                    </a>
                    <span class="badge bg-primary-subtle text-primary border" style="font-size: 0.7rem;">
                      <?= e($u['role_name']) ?>
                    </span>
                    <span class="badge <?= $isActive ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>" style="font-size: 0.68rem;">
                      <?= $isActive ? 'Active' : 'Disabled' ?>
                    </span>
                  </div>
                  <div class="text-muted small mt-0.5 text-truncate" style="font-size: 0.74rem;">
                    <?= e($u['email']) ?> &bull; <?= e($u['designation'] ?: 'Operations') ?> (<?= e($u['branch_name'] ?? 'Main') ?>)
                    <?php if (!empty($u['phone'])): ?> &bull; <i class="fa-solid fa-phone ms-1"></i> <?= e($u['phone']) ?><?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="d-flex align-items-center gap-1.5 ms-auto ms-sm-0">
                <div class="d-none d-md-flex align-items-center gap-1 me-2 text-muted small">
                  <span class="badge <?= (int)$u['active_applications'] > 0 ? 'bg-primary' : 'bg-light text-muted border' ?> rounded-pill px-2 py-0.5" style="font-size: 0.7rem;">
                    <?= (int)$u['active_applications'] ?> cases
                  </span>
                </div>
                <a href="/staff/show?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary py-1 px-2 fw-semibold" title="View Profile">
                  <i class="fa-solid fa-eye"></i>
                </a>
                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" data-bs-toggle="modal" data-bs-target="#editStaffModal<?= $u['id'] ?>" title="Edit">
                  <i class="fa-solid fa-pen-to-square"></i>
                </button>
                <form action="/staff/send-activation" method="POST" class="d-inline" onsubmit="return confirm('Send password activation link to <?= e($u['name']) ?>?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="staff_id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-info py-1 px-2" title="Send Password Link">
                    <i class="fa-solid fa-paper-plane"></i>
                  </button>
                </form>
                <?php if ($u['id'] !== (int)($_SESSION['user']['id'] ?? 0)): ?>
                  <button type="button" class="btn btn-sm <?= $isActive ? 'btn-outline-warning text-dark' : 'btn-outline-success' ?> py-1 px-2" data-bs-toggle="modal" data-bs-target="#toggleStaffModal<?= $u['id'] ?>" title="<?= $isActive ? 'Deactivate' : 'Activate' ?>">
                    <i class="fa-solid <?= $isActive ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2" data-bs-toggle="modal" data-bs-target="#deleteStaffModal<?= $u['id'] ?>" title="Delete">
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>
    </div>
  </div>

</div> <!-- /content-body -->

<!-- MODAL: ADD STAFF -->
<div class="modal fade" id="newStaffModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-user-plus me-2"></i> Register New Staff Member</h6>
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
            <input type="email" name="email" class="form-control" placeholder="e.g. staff@company.com" required>
            <div class="form-text small text-muted mt-1">
              <i class="fa-solid fa-link text-primary me-1"></i> A secure activation link will be automatically generated and sent to this email for the staff officer to set their own password before logging in.
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
              <input type="text" name="designation" class="form-control" placeholder="e.g. Senior Visa Officer">
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Department</label>
              <input type="text" name="department" class="form-control" placeholder="e.g. Visa Operations">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Contact Phone / WhatsApp</label>
            <input type="text" name="phone" class="form-control" placeholder="+971 50 123 4567">
          </div>

          <!-- Permission Giving Option -->
          <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between mb-1.5">
              <label class="form-label small fw-semibold mb-0">
                <i class="fa-solid fa-key text-primary me-1"></i> Permissions &amp; Privileges Granting Option
              </label>
              <button class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#staffPermsCollapse" aria-expanded="false" style="font-size: 0.75rem;">
                <i class="fa-solid fa-sliders me-1"></i> Customize / View Permissions
              </button>
            </div>
            <div class="collapse show" id="staffPermsCollapse">
              <div class="p-3 bg-light rounded border" style="max-height: 200px; overflow-y: auto;">
                <p class="text-muted small mb-2" style="font-size: 0.72rem;">
                  <i class="fa-solid fa-circle-info text-info me-1"></i> Permissions automatically synchronize with the selected Security Role. You can also grant or revoke specific custom permissions below:
                </p>
                <?php
                  $groupedModalPerms = [];
                  foreach ($allPermissions ?? [] as $mp) {
                      $groupedModalPerms[$mp['module']][] = $mp;
                  }
                ?>
                <div class="row g-2">
                  <?php foreach ($groupedModalPerms as $modName => $mPerms): ?>
                    <div class="col-12">
                      <div class="fw-bold text-dark border-bottom pb-1 mb-1.5" style="font-size: 0.74rem;">
                        <i class="fa-solid fa-folder-open text-secondary me-1"></i> <?= e($modName) ?>
                      </div>
                      <div class="row g-1">
                        <?php foreach ($mPerms as $p): ?>
                          <div class="col-6">
                            <div class="form-check form-check-inline m-0">
                              <input class="form-check-input staff-perm-checkbox" type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" id="stf_perm_<?= $p['id'] ?>">
                              <label class="form-check-label text-truncate small" for="stf_perm_<?= $p['id'] ?>" style="font-size: 0.72rem;" title="<?= e($p['name']) ?>">
                                <?= e($p['name']) ?>
                              </label>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>

          <div class="alert alert-info small mb-0 mt-3 d-flex align-items-start gap-2">
            <i class="fa-solid fa-paper-plane text-primary mt-0.5"></i>
            <div>
              <strong>Automated Onboarding Email:</strong> An official welcome email containing these credentials and the direct portal login link will be automatically sent to the work email.
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="btnSubmitNewStaff" class="btn btn-primary btn-sm px-3 fw-semibold">
            <i class="fa-solid fa-user-plus me-1"></i> Create Staff &amp; Send Credentials
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function switchStaffView(view) {
  document.querySelectorAll('.staff-view-container').forEach(el => el.classList.add('d-none'));
  const target = document.getElementById('staffView' + view.charAt(0).toUpperCase() + view.slice(1));
  if (target) target.classList.remove('d-none');

  document.querySelectorAll('.staff-view-btn').forEach(btn => {
    btn.classList.remove('btn-primary', 'shadow-sm');
    btn.classList.add('btn-light', 'text-muted');
  });

  const activeBtn = document.getElementById('btnStaffView' + view.charAt(0).toUpperCase() + view.slice(1));
  if (activeBtn) {
    activeBtn.classList.remove('btn-light', 'text-muted');
    activeBtn.classList.add('btn-primary', 'shadow-sm');
  }

  try { localStorage.setItem('staff_active_view', view); } catch (e) {}
}

const rolePermMap = <?= json_encode($rolePermissionsMap ?? []) ?>;

function updateStaffPermissionsFromRole() {
  const roleSelect = document.querySelector('#newStaffModal select[name="role_id"]');
  if (!roleSelect) return;
  const roleId = parseInt(roleSelect.value, 10);
  const permittedIds = rolePermMap[roleId] || [];

  document.querySelectorAll('.staff-perm-checkbox').forEach(cb => {
    const pId = parseInt(cb.value, 10);
    cb.checked = permittedIds.includes(pId);
  });
}

document.addEventListener('DOMContentLoaded', function() {
  // Restore saved view preference
  const urlParams = new URLSearchParams(window.location.search);
  if (!urlParams.has('view')) {
    const saved = localStorage.getItem('staff_active_view');
    if (saved && ['table', 'grid', 'compact'].includes(saved)) {
      switchStaffView(saved);
    } else if (window.innerWidth < 768) {
      // Mobile-friendly default view
      switchStaffView('grid');
    }
  }

  const roleSelect = document.querySelector('#newStaffModal select[name="role_id"]');
  if (roleSelect) {
    roleSelect.addEventListener('change', updateStaffPermissionsFromRole);
    updateStaffPermissionsFromRole();
  }

  // Prevent double-submission and repeated email triggers
  const staffForm = document.querySelector('#newStaffModal form');
  if (staffForm) {
    staffForm.addEventListener('submit', function(e) {
      const btn = document.getElementById('btnSubmitNewStaff');
      if (btn && btn.disabled) {
        e.preventDefault();
        return false;
      }
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Registering...';
      }
    });
  }
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
