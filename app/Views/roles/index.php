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
    <div class="d-flex align-items-center gap-2">
      <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createRoleModal">
        <i class="fa-solid fa-plus me-1"></i> Create Custom Role
      </button>
      <a href="/staff" class="btn btn-outline-secondary btn-sm px-3 bg-white shadow-sm">
        <i class="fa-solid fa-users me-1"></i> Staff Roster
      </a>
    </div>
  </div>

  <!-- Role Cards Grid -->
  <div class="row g-3">
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
                <i class="fa-solid fa-layer-group me-1"></i>13 Modules
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
</div>

<!-- Modal: Create Custom Role -->
<div class="modal fade" id="createRoleModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="/roles/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-header bg-primary text-white">
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
          <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
            <i class="fa-solid fa-plus me-1"></i> Save &amp; Activate Role
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
