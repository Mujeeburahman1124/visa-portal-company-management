<?php
$pageTitle = 'Staff Roster & Access Control — VISA TRACK';
$flash = get_flash();
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

  <!-- Page Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <h3 class="fw-bold brand-font text-dark mb-1">Staff Roster &amp; Access Control</h3>
      <p class="text-muted small mb-0">Manage operations officers, branch staff assignments, roles, and administrative privileges.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
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

  <!-- Staff Roster Data Table -->
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
                      <i class="fa-solid fa-user-gear"></i>
                    </a>
                    
                    <?php if ($u['id'] !== (int)($_SESSION['user']['id'] ?? 0)): ?>
                      <button type="button" class="btn btn-sm <?= $isActive ? 'btn-outline-danger' : 'btn-outline-success' ?> py-1 px-2" 
                              data-bs-toggle="modal" data-bs-target="#toggleStaffModal<?= $u['id'] ?>" title="<?= $isActive ? 'Deactivate Staff' : 'Activate Staff' ?>">
                        <i class="fa-solid <?= $isActive ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                      </button>

                      <!-- DEACTIVATE/ACTIVATE CONFIRMATION MODAL -->
                      <div class="modal fade" id="toggleStaffModal<?= $u['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content border-0 shadow">
                            <div class="modal-header <?= $isActive ? 'bg-danger text-white' : 'bg-success text-white' ?>">
                              <h6 class="modal-title fw-bold">
                                <i class="fa-solid <?= $isActive ? 'fa-triangle-exclamation' : 'fa-circle-check' ?> me-2"></i>
                                <?= $isActive ? 'Deactivate Staff Member?' : 'Activate Staff Member?' ?>
                              </h6>
                              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="/staff/toggle-active" method="POST">
                              <?= csrf_field() ?>
                              <input type="hidden" name="id" value="<?= $u['id'] ?>">
                              <div class="modal-body p-4 text-start">
                                <p class="mb-2">Are you sure you want to <strong><?= $isActive ? 'deactivate' : 'activate' ?></strong> the account for <strong><?= e($u['name']) ?></strong> (<?= e($u['email']) ?>)?</p>
                                <?php if ($isActive): ?>
                                  <div class="alert alert-danger mb-0 small">
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
                                <button type="submit" class="btn <?= $isActive ? 'btn-danger' : 'btn-success' ?> btn-sm px-3 fw-semibold">
                                  <?= $isActive ? 'Confirm Deactivation' : 'Confirm Activation' ?>
                                </button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>
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

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Work Email <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" placeholder="omar@visatrack.com" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Temporary Password <span class="text-danger">*</span></label>
              <input type="password" name="password" class="form-control" value="password123" required>
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

          <div class="mb-0">
            <label class="form-label small fw-semibold">Contact Phone / WhatsApp</label>
            <input type="text" name="phone" class="form-control" placeholder="+971 50 123 4567">
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">Create Staff Account</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
