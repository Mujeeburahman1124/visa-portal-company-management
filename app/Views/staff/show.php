<?php
$pageTitle = 'Staff Profile: ' . $member['name'] . ' — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';

$isActive = (int)$member['is_active'] === 1;
$initials = strtoupper(substr($member['name'], 0, 1));
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

  <!-- Staff Member Hero Header -->
  <div class="card card-enterprise mb-4 shadow-sm">
    <div class="card-body p-4">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold shadow" style="width: 64px; height: 64px; font-size: 1.5rem; background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);">
            <?= $initials ?>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1">
              <h4 class="fw-bold brand-font text-dark mb-0"><?= e($member['name']) ?></h4>
              <span class="badge <?= $isActive ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> fw-bold px-2 py-0.5" style="font-size: 0.72rem;">
                <i class="fa-solid <?= $isActive ? 'fa-circle-check' : 'fa-ban' ?> me-1"></i><?= $isActive ? 'Active Staff' : 'Deactivated' ?>
              </span>
            </div>
            <div class="text-muted small d-flex flex-wrap align-items-center gap-3">
              <span><i class="fa-solid fa-briefcase text-primary me-1"></i><?= e($member['designation'] ?: 'Operations Officer') ?></span>
              <span><i class="fa-regular fa-envelope text-primary me-1"></i><?= e($member['email']) ?></span>
              <span><i class="fa-solid fa-phone text-primary me-1"></i><?= e($member['phone'] ?: '—') ?></span>
              <span><i class="fa-solid fa-building text-primary me-1"></i><?= e($member['branch_name'] ?? 'Main Office') ?></span>
            </div>
          </div>
        </div>

        <div class="d-flex align-items-center gap-2">
          <a href="/staff" class="btn btn-outline-secondary btn-sm px-3">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Roster
          </a>
          <button class="btn btn-outline-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#editStaffModal">
            <i class="fa-solid fa-user-pen me-1"></i> Edit Profile
          </button>
          <button class="btn btn-outline-warning btn-sm px-3 text-dark" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
            <i class="fa-solid fa-key me-1"></i> Reset Password
          </button>
          <?php if ((int)$member['id'] !== (int)($_SESSION['user']['id'] ?? 0)): ?>
            <button class="btn btn-outline-danger btn-sm px-3" data-bs-toggle="modal" data-bs-target="#deleteStaffModal">
              <i class="fa-solid fa-trash-can me-1"></i> Delete Officer
            </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Profile Details Tabs -->
  <div class="card card-enterprise shadow-sm">
    <div class="card-header bg-white border-bottom p-0">
      <ul class="nav nav-tabs card-header-tabs m-0 px-3" role="tablist">
        <li class="nav-item">
          <button class="nav-link active py-3 fw-semibold small" data-bs-toggle="tab" data-bs-target="#tab-applications">
            <i class="fa-solid fa-folder-open text-primary me-1"></i> Assigned Applications 
            <span class="badge bg-primary rounded-pill ms-1"><?= count($assignedApps) ?></span>
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link py-3 fw-semibold small" data-bs-toggle="tab" data-bs-target="#tab-tasks">
            <i class="fa-solid fa-list-check text-warning me-1"></i> Operational Tasks 
            <span class="badge bg-warning text-dark rounded-pill ms-1"><?= count($assignedTasks) ?></span>
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link py-3 fw-semibold small" data-bs-toggle="tab" data-bs-target="#tab-permissions">
            <i class="fa-solid fa-shield-halved text-success me-1"></i> Permissions Matrix 
            <span class="badge bg-success rounded-pill ms-1"><?= count($permissions) ?></span>
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link py-3 fw-semibold small" data-bs-toggle="tab" data-bs-target="#tab-activity">
            <i class="fa-solid fa-clock-rotate-left text-info me-1"></i> Recent Activity Log
          </button>
        </li>
      </ul>
    </div>

    <div class="card-body p-4">
      <div class="tab-content">

        <!-- TAB 1: ASSIGNED APPLICATIONS -->
        <div class="tab-pane fade show active" id="tab-applications" role="tabpanel">
          <?php if (empty($assignedApps)): ?>
            <div class="text-center py-5 text-muted">
              <i class="fa-solid fa-folder-open fs-2 text-muted mb-2"></i>
              <div class="fw-semibold">No active visa applications assigned to this staff member.</div>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table-modern mb-0">
                <thead>
                  <tr>
                    <th>Application #</th>
                    <th>Applicant</th>
                    <th>Visa Service</th>
                    <th>Current Stage</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($assignedApps as $app): ?>
                    <tr>
                      <td>
                        <a href="/applications/show?id=<?= $app['id'] ?>" class="badge bg-primary-subtle text-primary fw-bold text-decoration-none px-2 py-1">
                          <?= e($app['application_number']) ?>
                        </a>
                      </td>
                      <td class="fw-semibold text-dark"><?= e($app['customer_name']) ?></td>
                      <td><?= $app['flag_emoji'] ?> <?= e($app['service_name']) ?></td>
                      <td><span class="badge bg-light text-dark border px-2 py-1"><i class="fa-solid fa-circle-dot text-primary me-1"></i><?= e($app['current_stage']) ?></span></td>
                      <td><span class="badge bg-secondary-subtle text-dark fw-bold"><?= e($app['status']) ?></span></td>
                      <td><span class="badge bg-danger-subtle text-danger fw-bold"><?= e($app['priority']) ?></span></td>
                      <td class="text-end">
                        <a href="/applications/show?id=<?= $app['id'] ?>" class="btn btn-sm btn-primary py-1 px-3">View File &rarr;</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <!-- TAB 2: OPERATIONAL TASKS -->
        <div class="tab-pane fade" id="tab-tasks" role="tabpanel">
          <?php if (empty($assignedTasks)): ?>
            <div class="text-center py-5 text-muted">
              <i class="fa-solid fa-clipboard-check fs-2 text-muted mb-2"></i>
              <div class="fw-semibold">No pending operational tasks assigned.</div>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table-modern mb-0">
                <thead>
                  <tr>
                    <th>Task Title</th>
                    <th>Related File</th>
                    <th>Priority</th>
                    <th>Due Date</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($assignedTasks as $tsk): ?>
                    <tr>
                      <td class="fw-semibold text-dark"><?= e($tsk['task_title']) ?></td>
                      <td><span class="badge bg-light text-primary border"><?= e($tsk['application_number'] ?: 'General') ?></span></td>
                      <td><span class="badge bg-warning-subtle text-dark fw-bold"><?= e($tsk['priority']) ?></span></td>
                      <td><i class="fa-regular fa-clock me-1 text-muted"></i><?= format_date($tsk['due_date']) ?></td>
                      <td><span class="badge <?= $tsk['status'] === 'Completed' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= e($tsk['status']) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <!-- TAB 3: PERMISSIONS MATRIX -->
        <div class="tab-pane fade" id="tab-permissions" role="tabpanel">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
              <h6 class="fw-bold text-dark mb-1">Effective Role Permissions</h6>
              <p class="text-muted small mb-0">Permissions granted through the <strong><?= e($member['role_name']) ?></strong> security role.</p>
            </div>
            <a href="/roles/edit?id=<?= $member['role_id'] ?>" class="btn btn-outline-primary btn-sm px-3">
              <i class="fa-solid fa-pen-to-square me-1"></i> Edit Role Permissions
            </a>
          </div>

          <div class="row g-2">
            <?php foreach ($permissions as $p): ?>
              <div class="col-md-6 col-lg-4">
                <div class="p-2 bg-light rounded border d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-check text-success"></i>
                    <div>
                      <div class="fw-semibold small text-dark"><?= e($p['name']) ?></div>
                      <div class="text-muted font-monospace" style="font-size: 0.68rem;"><?= e($p['slug']) ?></div>
                    </div>
                  </div>
                  <span class="badge bg-white text-secondary border small"><?= e($p['module']) ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- TAB 4: RECENT ACTIVITY LOG -->
        <div class="tab-pane fade" id="tab-activity" role="tabpanel">
          <?php if (empty($recentLogs)): ?>
            <div class="text-center py-5 text-muted">
              <i class="fa-solid fa-clock-rotate-left fs-2 text-muted mb-2"></i>
              <div class="fw-semibold">No recent activity logs recorded for this user.</div>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table-modern mb-0">
                <thead>
                  <tr>
                    <th>Timestamp</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Description</th>
                    <th>IP Address</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentLogs as $log): ?>
                    <tr>
                      <td class="text-muted small"><?= format_datetime($log['created_at']) ?></td>
                      <td><span class="badge bg-secondary"><?= e($log['action']) ?></span></td>
                      <td><span class="badge bg-light text-dark border"><?= e($log['module']) ?></span></td>
                      <td class="fw-semibold small text-dark"><?= e($log['description']) ?></td>
                      <td class="text-muted small font-monospace"><?= e($log['ip_address']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- MODAL: EDIT STAFF PROFILE -->
<div class="modal fade" id="editStaffModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-user-pen me-2"></i> Edit Staff Profile</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/staff/update" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $member['id'] ?>">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="<?= e($member['name']) ?>" required>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Work Email <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" value="<?= e($member['email']) ?>" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Contact Phone</label>
              <input type="text" name="phone" class="form-control" value="<?= e($member['phone']) ?>">
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Security Role <span class="text-danger">*</span></label>
              <select name="role_id" class="form-select" required>
                <?php foreach ($roles as $r): ?>
                  <option value="<?= $r['id'] ?>" <?= ((int)$member['role_id']) === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Branch Office</label>
              <select name="branch_id" class="form-select">
                <?php foreach ($branches as $b): ?>
                  <option value="<?= $b['id'] ?>" <?= ((int)($member['branch_id'] ?? 0)) === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row g-2 mb-0">
            <div class="col-6">
              <label class="form-label small fw-semibold">Job Designation</label>
              <input type="text" name="designation" class="form-control" value="<?= e($member['designation']) ?>">
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Department</label>
              <input type="text" name="department" class="form-control" value="<?= e($member['department']) ?>">
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

<!-- MODAL: RESET PASSWORD -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-warning text-dark">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-key me-2"></i> Reset Staff Password</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/staff/reset-password" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $member['id'] ?>">
        <div class="modal-body p-4">
          <p class="small text-muted mb-3">Set a new temporary or permanent password for <strong><?= e($member['name']) ?></strong> (<?= e($member['email']) ?>).</p>
          <div class="mb-3">
            <label class="form-label small fw-semibold">New Password <span class="text-danger">*</span></label>
            <input type="password" name="new_password" class="form-control" placeholder="Enter new password" required>
          </div>
          <div class="alert alert-info small mb-0">
            <i class="fa-solid fa-circle-info me-1"></i> The user will be able to log in with this new password immediately.
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning btn-sm px-3 fw-semibold text-dark">Confirm Reset Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: DELETE STAFF -->
<?php if ((int)$member['id'] !== (int)($_SESSION['user']['id'] ?? 0)): ?>
<div class="modal fade" id="deleteStaffModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-trash-can me-2"></i> Delete Staff Member</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/staff/delete" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $member['id'] ?>">
        <div class="modal-body p-4">
          <p class="mb-2">Are you sure you want to permanently delete <strong><?= e($member['name']) ?></strong> (<?= e($member['email']) ?>)?</p>
          <div class="alert alert-danger small mb-0">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> This action cannot be undone. Assigned visa cases and tasks will be safely unlinked.
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold">Permanently Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
