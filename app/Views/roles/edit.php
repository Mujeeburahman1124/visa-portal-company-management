<?php
$pageTitle = 'Configure Role Matrix: ' . $role['name'] . ' — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';

$isSuper = ($role['slug'] === 'super-admin' || (int)$role['id'] === 1);
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

  <!-- Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <h3 class="fw-bold brand-font text-dark mb-0">Role Permissions Matrix: <?= e($role['name']) ?></h3>
        <span class="badge <?= $isSuper ? 'bg-danger' : 'bg-primary' ?>"><?= e($role['slug']) ?></span>
      </div>
      <p class="text-muted small mb-0">Toggle granular module privileges across View, Create, Edit, Delete, Approve, Assign, and Export capabilities.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <button type="button" class="btn btn-outline-primary btn-sm px-3 shadow-sm bg-white" data-bs-toggle="modal" data-bs-target="#createModuleModal">
        <i class="fa-solid fa-folder-plus me-1"></i> Add Module
      </button>
      <a href="/roles" class="btn btn-outline-secondary btn-sm px-3 bg-white">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Roles
      </a>
      <?php if (!$isSuper): ?>
        <button type="button" class="btn btn-outline-primary btn-sm px-3" onclick="toggleAllPermissions(true)">
          <i class="fa-solid fa-check-double me-1"></i> Grant All
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="toggleAllPermissions(false)">
          <i class="fa-solid fa-xmark me-1"></i> Revoke All
        </button>
      <?php endif; ?>
    </div>
  </div>

  <form action="/roles/update" method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $role['id'] ?>">

    <!-- Role Metadata Card -->
    <div class="card card-enterprise mb-4 shadow-sm">
      <div class="card-body p-4">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Role Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="<?= e($role['name']) ?>" required <?= $isSuper ? 'readonly' : '' ?>>
          </div>
          <div class="col-md-8">
            <label class="form-label small fw-semibold">Role Description</label>
            <input type="text" name="description" class="form-control" value="<?= e($role['description']) ?>" placeholder="Describe the operational scope of this role...">
          </div>
        </div>
      </div>
    </div>

    <!-- 13 Permission Categories Matrix -->
    <div class="row g-3">
      <?php foreach ($groupedPermissions as $moduleName => $perms): ?>
        <div class="col-lg-6">
          <div class="card card-enterprise h-100 shadow-sm border">
            <div class="card-header bg-white border-bottom py-2.5 px-3 d-flex align-items-center justify-content-between">
              <div class="fw-bold text-dark d-flex align-items-center gap-2">
                <i class="fa-solid fa-folder-tree text-primary"></i>
                <span><?= e($moduleName) ?> Module</span>
              </div>
              <?php if (!$isSuper): ?>
                <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold" style="font-size: 0.72rem;" onclick="toggleModuleGroup('module-<?= strtolower(str_replace(' ', '-', $moduleName)) ?>')">
                  Toggle Category
                </button>
              <?php endif; ?>
            </div>

            <div class="card-body p-3 module-<?= strtolower(str_replace(' ', '-', $moduleName)) ?>">
              <div class="row g-2">
                <?php foreach ($perms as $p): ?>
                  <?php $isChecked = in_array((int)$p['id'], array_map('intval', $activePermIds), true) || $isSuper; ?>
                  <div class="col-sm-6">
                    <div class="form-check p-2 bg-light rounded border h-100">
                      <input class="form-check-input perm-checkbox ms-1" type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" id="perm_<?= $p['id'] ?>" <?= $isChecked ? 'checked' : '' ?> <?= $isSuper ? 'disabled' : '' ?>>
                      <label class="form-check-label ms-2 small" for="perm_<?= $p['id'] ?>">
                        <div class="fw-semibold text-dark"><?= e($p['name']) ?></div>
                        <div class="text-muted font-monospace" style="font-size: 0.67rem;"><?= e($p['slug']) ?></div>
                      </label>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Sticky Save Footer -->
    <div class="mt-4 p-3 bg-white rounded border shadow-sm d-flex align-items-center justify-content-between">
      <span class="text-muted small">
        <i class="fa-solid fa-circle-info text-primary me-1"></i> Changes will immediately take effect for all staff assigned to <strong><?= e($role['name']) ?></strong>.
      </span>
      <div class="d-flex gap-2">
        <a href="/roles" class="btn btn-secondary btn-sm px-3">Cancel</a>
        <?php if (!$isSuper): ?>
          <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Permissions Matrix
          </button>
        <?php endif; ?>
      </div>
    </div>
  </form>
</div>

<script>
function toggleAllPermissions(checked) {
  document.querySelectorAll('.perm-checkbox:not(:disabled)').forEach(cb => {
    cb.checked = checked;
  });
}

function toggleModuleGroup(className) {
  const container = document.querySelector('.' + className);
  if (!container) return;
  const checkboxes = container.querySelectorAll('.perm-checkbox:not(:disabled)');
  const anyUnchecked = Array.from(checkboxes).some(cb => !cb.checked);
  checkboxes.forEach(cb => {
    cb.checked = anyUnchecked;
  });
}
</script>

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
                    <input class="form-check-input" type="checkbox" name="actions[]" value="<?= $actKey ?>" id="edit_act_<?= $actKey ?>" checked>
                    <label class="form-check-label" for="edit_act_<?= $actKey ?>">
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
              <?php foreach (($roles ?? []) as $r): ?>
                <div class="col-md-6">
                  <div class="form-check small">
                    <input class="form-check-input" type="checkbox" name="assign_roles[]" value="<?= $r['id'] ?>" id="edit_assign_role_<?= $r['id'] ?>" <?= ((int)$r['id'] === (int)$role['id'] || $r['slug'] === 'super-admin' || $r['slug'] === 'admin') ? 'checked' : '' ?> <?= $r['slug'] === 'super-admin' ? 'checked disabled' : '' ?>>
                    <label class="form-check-label" for="edit_assign_role_<?= $r['id'] ?>">
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

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
