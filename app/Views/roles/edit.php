<?php
$pageTitle = 'Configure Role Matrix: ' . $role['name'] . ' — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';

$isSuper = ($role['slug'] === 'super-admin' || (int)$role['id'] === 1);
?>

<!-- Link Dedicated Bento CSS -->
<link rel="stylesheet" href="/assets/css/dashboard-bento.css">
<link rel="stylesheet" href="/assets/css/pages/roles-index.css">

<div class="content-body roles-dashboard">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Bento Hero Header -->
  <div class="roles-hero">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
          <h1 class="roles-hero-title mb-0">
            <i class="fa-solid fa-sliders text-danger"></i> Permissions Matrix: <?= e($role['name']) ?>
          </h1>
          <span class="role-slug-chip <?= $isSuper ? 'super-admin' : 'standard' ?>">
            <i class="fa-solid <?= $isSuper ? 'fa-crown' : 'fa-shield-halved' ?> me-1"></i><?= e($role['slug']) ?>
          </span>
        </div>
        <p class="roles-hero-desc">
          Configure precise capability gates across View, Create, Edit, Delete, Approve, Assign, and Export functions for this security role.
        </p>
      </div>

      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="/roles" class="btn btn-outline-light btn-sm px-3 shadow-sm rounded-pill fw-semibold">
          <i class="fa-solid fa-arrow-left me-1"></i> Back to Roles
        </a>
        <?php if (!$isSuper): ?>
          <button type="button" class="btn btn-light btn-sm px-3 shadow-sm rounded-pill fw-semibold" onclick="toggleAllPermissions(true)">
            <i class="fa-solid fa-check-double text-success me-1"></i> Grant All
          </button>
          <button type="button" class="btn btn-outline-light btn-sm px-3 shadow-sm rounded-pill fw-semibold" onclick="toggleAllPermissions(false)">
            <i class="fa-solid fa-xmark text-danger me-1"></i> Revoke All
          </button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <form action="/roles/update" method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $role['id'] ?>">

    <!-- Role Metadata Card -->
    <div class="card card-enterprise mb-4 shadow-sm border" style="border-radius: var(--bento-radius);">
      <div class="card-body p-4">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label small fw-semibold text-secondary">Role Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="<?= e($role['name']) ?>" required <?= $isSuper ? 'readonly' : '' ?>>
          </div>
          <div class="col-md-8">
            <label class="form-label small fw-semibold text-secondary">Operational Scope / Description</label>
            <input type="text" name="description" class="form-control" value="<?= e($role['description'] ?? '') ?>" placeholder="Describe the operational boundaries and duties of this security role...">
          </div>
        </div>
      </div>
    </div>

    <!-- Permission Categories Bento Grid -->
    <div class="row g-3 mb-5">
      <?php foreach ($groupedPermissions as $moduleName => $perms): ?>
        <?php $groupClass = 'mod-grp-' . strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', (string)$moduleName)); ?>
        <div class="col-12 col-xl-6">
          <div class="bento-matrix-card">
            <div class="bento-matrix-header">
              <div class="fw-bold text-dark d-flex align-items-center gap-2">
                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center p-1.5" style="width: 28px; height: 28px;">
                  <i class="fa-solid fa-folder-tree" style="font-size: 0.75rem;"></i>
                </div>
                <span><?= e($moduleName) ?> Module</span>
              </div>
              <?php if (!$isSuper): ?>
                <button type="button" class="btn btn-outline-secondary btn-sm py-0.5 px-2 rounded-pill fw-semibold" style="font-size: 0.72rem;" onclick="toggleModuleGroup('<?= $groupClass ?>')">
                  Toggle Category
                </button>
              <?php endif; ?>
            </div>

            <div class="p-3 <?= $groupClass ?>">
              <div class="row g-2">
                <?php foreach ($perms as $p): ?>
                  <?php $isChecked = in_array((int)$p['id'], array_map('intval', $activePermIds), true) || $isSuper; ?>
                  <div class="col-12 col-sm-6">
                    <div class="perm-checkbox-chip">
                      <input class="form-check-input perm-checkbox flex-shrink-0 mt-1" type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" id="perm_<?= $p['id'] ?>" <?= $isChecked ? 'checked' : '' ?> <?= $isSuper ? 'disabled' : '' ?>>
                      <label class="form-check-label small mb-0 flex-grow-1" for="perm_<?= $p['id'] ?>" style="cursor: pointer;">
                        <div class="perm-title fw-semibold text-dark"><?= e($p['name']) ?></div>
                        <div class="text-muted font-monospace" style="font-size: 0.68rem;"><?= e($p['slug']) ?></div>
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

    <!-- Sticky Save Bar -->
    <div class="sticky-save-bar d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="text-muted small">
        <i class="fa-solid fa-shield-halved text-primary me-1.5"></i> Permissions update takes effect on next session authorization for <strong><?= e($role['name']) ?></strong>.
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="/roles" class="btn btn-light border btn-sm px-3 rounded-pill">Cancel</a>
        <?php if (!$isSuper): ?>
          <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold rounded-pill shadow-sm">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Permissions Matrix
          </button>
        <?php endif; ?>
      </div>
    </div>
  </form>
</div>

<script>
function toggleAllPermissions(checked) {
  document.querySelectorAll('.perm-checkbox').forEach(cb => {
    if (!cb.disabled) {
      cb.checked = checked;
    }
  });
}

function toggleModuleGroup(groupClass) {
  const container = document.querySelector('.' + groupClass);
  if (!container) return;
  const boxes = container.querySelectorAll('.perm-checkbox');
  if (!boxes.length) return;
  const anyUnchecked = Array.from(boxes).some(cb => !cb.checked);
  boxes.forEach(cb => {
    if (!cb.disabled) {
      cb.checked = anyUnchecked;
    }
  });
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
