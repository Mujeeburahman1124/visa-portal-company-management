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
    <div class="d-flex align-items-center gap-2">
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

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
