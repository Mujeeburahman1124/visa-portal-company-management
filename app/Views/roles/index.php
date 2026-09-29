<?php
$pageTitle = 'Security Roles & Permissions — VISA TRACK';
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
      <h3 class="fw-bold brand-font text-dark mb-1">Security Roles &amp; Granular Permissions</h3>
      <p class="text-muted small mb-0">Define role-based access control (RBAC), operational boundaries, and module authorization rules.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createModuleModal">
        <i class="fa-solid fa-folder-plus me-1"></i> Add Module
      </button>
      <button type="button" class="btn btn-outline-primary btn-sm px-3 bg-white shadow-sm" data-bs-toggle="modal" data-bs-target="#createPermissionModal">
        <i class="fa-solid fa-key me-1"></i> Add Permission
      </button>
      <button type="button" class="btn btn-outline-dark btn-sm px-3 bg-white shadow-sm" data-bs-toggle="modal" data-bs-target="#createRoleModal">
        <i class="fa-solid fa-shield-plus me-1"></i> Create Custom Role
      </button>
      <a href="/staff" class="btn btn-outline-secondary btn-sm px-3 bg-white shadow-sm">
        <i class="fa-solid fa-users me-1"></i> Staff Roster
      </a>
    </div>
  </div>

  <!-- Role Cards Grid -->
  <div class="row g-3 mb-4">
    <?php foreach ($roles as $r): ?>
      <?php
        $permPct = $totalPermissions > 0 ? (int)round(((int)$r['permission_count'] / $totalPermissions) * 100) : 0;
        $isSuper = ($r['slug'] === 'super-admin');
        $isCustom = ((int)$r['id'] > 11);
      ?>
      <div class="col-md-6 col-lg-4">
        <div class="card card-enterprise h-100 shadow-sm border">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge <?= $isSuper ? 'bg-danger' : 'bg-primary-subtle text-primary' ?> fw-bold px-2.5 py-1" style="font-size: 0.72rem;">
                  <i class="fa-solid <?= $isSuper ? 'fa-crown' : 'fa-shield-halved' ?> me-1"></i><?= e($r['slug']) ?>
                </span>
                <div class="d-flex align-items-center gap-1">
                  <span class="badge bg-light text-dark border small">
                    <i class="fa-solid fa-users me-1 text-muted"></i><?= (int)$r['user_count'] ?> Staff
                  </span>
                  <?php if (!$isSuper && $isCustom): ?>
                    <form action="/roles/delete" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this custom role?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= $r['id'] ?>">
                      <button type="submit" class="btn btn-outline-danger btn-sm p-1 py-0" title="Delete Custom Role">
                        <i class="fa-solid fa-trash-can" style="font-size: 0.7rem;"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>

              <h5 class="fw-bold text-dark mb-1"><?= e($r['name']) ?></h5>
              <p class="text-muted small mb-3" style="font-size: 0.78rem; min-height: 38px;">
                <?= e($r['description'] ?: 'Standard operational role within the visa processing pipeline.') ?>
              </p>

              <div class="p-3 bg-light rounded border mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="text-muted small fw-semibold">Permissions Coverage:</span>
                  <span class="small fw-bold text-primary"><?= (int)$r['permission_count'] ?> / <?= $totalPermissions ?> (<?= $permPct ?>%)</span>
                </div>
                <div class="progress" style="height: 6px;">
                  <div class="progress-bar <?= $isSuper ? 'bg-danger' : 'bg-primary' ?>" role="progressbar" style="width: <?= $permPct ?>%;"></div>
                </div>
              </div>
            </div>

            <div class="d-flex align-items-center justify-content-between pt-2 border-top">
              <span class="text-muted small" style="font-size: 0.72rem;">
                <i class="fa-solid fa-layer-group me-1"></i><?= count($modulesList ?? []) ?> Modules
              </span>
              <a href="/roles/edit?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary px-3 fw-semibold shadow-sm">
                <i class="fa-solid fa-sliders me-1"></i> Configure Matrix &rarr;
              </a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Modules & Permission Catalog -->
  <div class="card card-enterprise shadow-sm border mb-4">
    <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom">
      <div>
        <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> System Modules Catalog</h5>
        <span class="text-muted small">Manage available operational modules and their granular permission rules.</span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-primary px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createModuleModal">
          <i class="fa-solid fa-plus me-1"></i> Add Module
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary px-3" data-bs-toggle="modal" data-bs-target="#createPermissionModal">
          <i class="fa-solid fa-key me-1"></i> Add Permission
        </button>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">Module Name</th>
              <th>Permissions Count</th>
              <th>Sample Permissions</th>
              <th>Type</th>
              <th class="text-end pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $coreModules = ['Applications', 'Applicants', 'Documents', 'Tasks', 'Payments', 'Staff', 'Settings'];
              foreach (($modulesList ?? []) as $mod):
                $isCore = in_array($mod['module'], $coreModules, true);
                $samplePerms = array_slice(array_filter($allPermissions, fn($p) => $p['module'] === $mod['module']), 0, 4);
            ?>
              <tr>
                <td class="ps-4">
                  <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center p-2" style="width: 32px; height: 32px;">
                      <i class="fa-solid fa-cubes-stacked" style="font-size: 0.85rem;"></i>
                    </div>
                    <div>
                      <strong class="text-dark"><?= e($mod['module']) ?></strong>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="badge bg-secondary-subtle text-dark border px-2.5 py-1">
                    <?= (int)$mod['perm_count'] ?> permissions
                  </span>
                </td>
                <td>
                  <div class="d-flex flex-wrap gap-1">
                    <?php foreach ($samplePerms as $sp): ?>
                      <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.65rem;">
                        <?= e($sp['slug']) ?>
                      </span>
                    <?php endforeach; ?>
                    <?php if ((int)$mod['perm_count'] > count($samplePerms)): ?>
                      <span class="badge bg-light text-muted border" style="font-size: 0.65rem;">+<?= (int)$mod['perm_count'] - count($samplePerms) ?> more</span>
                    <?php endif; ?>
                  </div>
                </td>
                <td>
                  <?php if ($isCore): ?>
                    <span class="badge bg-info-subtle text-info border px-2 py-0.5" style="font-size: 0.72rem;">Core System</span>
                  <?php else: ?>
                    <span class="badge bg-success-subtle text-success border px-2 py-0.5" style="font-size: 0.72rem;">Custom Module</span>
                  <?php endif; ?>
                </td>
                <td class="text-end pe-4">
                  <?php if (!$isCore): ?>
                    <form action="/roles/delete-module" method="POST" class="d-inline" onsubmit="return confirm('Delete custom module \'<?= e($mod['module']) ?>\' and all associated permissions?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="module" value="<?= e($mod['module']) ?>">
                      <button type="submit" class="btn btn-outline-danger btn-sm px-2 py-1" title="Delete Module">
                        <i class="fa-solid fa-trash-can me-1"></i> Delete
                      </button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted small fst-italic">Protected</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Add New Module -->
<div class="modal fade" id="createModuleModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="/roles/add-module" method="POST">
        <?= csrf_field() ?>
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title fw-bold"><i class="fa-solid fa-folder-plus me-2"></i> Add New System Module</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Module Name <span class="text-danger">*</span></label>
              <input type="text" name="module_name" class="form-control" placeholder="e.g. Finance, Legal Compliance, Reports" required>
              <div class="form-text small">Display name for the module grouping.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Module Slug (Optional)</label>
              <input type="text" name="module_slug" class="form-control" placeholder="e.g. finance, legal_compliance">
              <div class="form-text small">Leave blank to auto-generate from module name.</div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Description</label>
            <input type="text" name="description" class="form-control" placeholder="e.g. Financial auditing, invoice processing, and account settlements">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold mb-2">Standard Action Permissions to Create</label>
            <div class="row g-2 p-3 bg-light rounded border">
              <?php 
                $standardActions = [
                  'view' => 'View / Read records',
                  'create' => 'Create new records',
                  'edit' => 'Edit / Update records',
                  'delete' => 'Delete records',
                  'approve' => 'Approve / Review workflows',
                  'assign' => 'Assign records / tasks',
                  'export' => 'Export data (CSV/PDF)'
                ];
                foreach ($standardActions as $actKey => $actLabel):
              ?>
                <div class="col-md-6">
                  <div class="form-check small">
                    <input class="form-check-input" type="checkbox" name="actions[]" value="<?= $actKey ?>" id="act_<?= $actKey ?>" checked>
                    <label class="form-check-label" for="act_<?= $actKey ?>">
                      <strong class="text-dark"><?= ucfirst($actKey) ?></strong> <span class="text-muted">(<?= $actLabel ?>)</span>
                    </label>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Custom Actions (Optional)</label>
            <input type="text" name="custom_actions" class="form-control" placeholder="e.g. reconcile, audit, generate_invoice (comma separated)">
            <div class="form-text small">Additional permission actions separated by commas.</div>
          </div>

          <div>
            <label class="form-label small fw-semibold mb-2">Assign Newly Created Permissions To Roles</label>
            <div class="row g-2 p-3 bg-light rounded border">
              <?php foreach ($roles as $r): ?>
                <div class="col-md-6">
                  <div class="form-check small">
                    <input class="form-check-input" type="checkbox" name="assign_roles[]" value="<?= $r['id'] ?>" id="assign_role_<?= $r['id'] ?>" <?= ($r['slug'] === 'super-admin' || $r['slug'] === 'admin') ? 'checked' : '' ?> <?= $r['slug'] === 'super-admin' ? 'checked disabled' : '' ?>>
                    <label class="form-check-label" for="assign_role_<?= $r['id'] ?>">
                      <strong class="text-dark"><?= e($r['name']) ?></strong> <span class="badge bg-light text-muted border"><?= e($r['slug']) ?></span>
                    </label>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="form-text small text-muted">Super Admin will always receive all permissions automatically.</div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
            <i class="fa-solid fa-check me-1"></i> Create Module &amp; Permissions
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Add Single Permission -->
<div class="modal fade" id="createPermissionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="/roles/add-permission" method="POST">
        <?= csrf_field() ?>
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title fw-bold"><i class="fa-solid fa-key me-2"></i> Add Single Permission</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Target Module <span class="text-danger">*</span></label>
            <select name="module" class="form-select" required>
              <option value="">-- Select Existing Module --</option>
              <?php foreach (($modulesList ?? []) as $mod): ?>
                <option value="<?= e($mod['module']) ?>"><?= e($mod['module']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Permission Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Export Financial Audits" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Permission Slug (Optional)</label>
            <input type="text" name="slug" class="form-control" placeholder="e.g. finance.export_audits">
            <div class="form-text small">Leave blank to auto-generate (e.g. module.action).</div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Description</label>
            <input type="text" name="description" class="form-control" placeholder="Capability description...">
          </div>

          <div>
            <label class="form-label small fw-semibold mb-2">Grant To Roles</label>
            <div class="row g-2 p-2 bg-light rounded border" style="max-height: 180px; overflow-y: auto;">
              <?php foreach ($roles as $r): ?>
                <div class="col-12">
                  <div class="form-check small">
                    <input class="form-check-input" type="checkbox" name="assign_roles[]" value="<?= $r['id'] ?>" id="perm_role_<?= $r['id'] ?>" <?= ($r['slug'] === 'super-admin' || $r['slug'] === 'admin') ? 'checked' : '' ?> <?= $r['slug'] === 'super-admin' ? 'checked disabled' : '' ?>>
                    <label class="form-check-label" for="perm_role_<?= $r['id'] ?>">
                      <?= e($r['name']) ?> <span class="text-muted">(<?= e($r['slug']) ?>)</span>
                    </label>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
            <i class="fa-solid fa-plus me-1"></i> Add Permission
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Create Custom Role -->
<div class="modal fade" id="createRoleModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="/roles/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-header bg-dark text-white">
          <h5 class="modal-title fw-bold"><i class="fa-solid fa-shield-halved me-2"></i> Create Custom Operational Role</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Role Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Senior Document Reviewer" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Description</label>
              <input type="text" name="description" class="form-control" placeholder="Responsibilities and scope...">
            </div>
          </div>

          <label class="form-label small fw-semibold mb-2">Initial Permissions</label>
          <div class="row g-2 p-3 bg-light rounded border" style="max-height: 280px; overflow-y: auto;">
            <?php foreach ($allPermissions as $perm): ?>
              <div class="col-md-6">
                <div class="form-check small">
                  <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $perm['id'] ?>" id="newperm_<?= $perm['id'] ?>">
                  <label class="form-check-label" for="newperm_<?= $perm['id'] ?>">
                    <strong class="text-dark"><?= e($perm['name']) ?></strong> <span class="text-muted">(<?= e($perm['module']) ?>)</span>
                  </label>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark btn-sm px-4 fw-semibold shadow-sm">
            <i class="fa-solid fa-plus me-1"></i> Save &amp; Activate Role
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
