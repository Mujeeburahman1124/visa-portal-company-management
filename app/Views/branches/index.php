<?php
$pageTitle = 'Branches & Global Offices — VISA TRACK';
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
      <h3 class="fw-bold brand-font text-dark mb-1">Global Branch Network</h3>
      <p class="text-muted small mb-0">Manage worldwide branch desks, operations hubs, staff assignments, and localized revenues.</p>
    </div>
    <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#newBranchModal">
      <i class="fa-solid fa-plus me-1"></i> Register New Branch
    </button>
  </div>

  <!-- Branch Cards Grid -->
  <div class="row g-3">
    <?php foreach ($branches as $b): ?>
      <?php $isActive = (int)$b['is_active'] === 1; ?>
      <div class="col-md-6 col-lg-3">
        <div class="card card-enterprise h-100 shadow-sm border">
          <div class="card-body p-3.5 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge bg-primary-subtle text-primary fw-bold border"><?= e($b['code']) ?></span>
                <span class="badge <?= $isActive ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> fw-bold">
                  <?= $isActive ? 'Active Desk' : 'Suspended' ?>
                </span>
              </div>
              <h5 class="fw-bold text-dark mb-1"><?= e($b['name']) ?></h5>
              <div class="text-muted small mb-3"><i class="fa-solid fa-location-dot me-1 text-danger"></i> <?= e($b['city']) ?>, <?= e($b['country']) ?></div>
              
              <div class="p-2.5 bg-light rounded border mb-3 small">
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted">Assigned Staff:</span>
                  <span class="fw-bold text-dark"><?= (int)$b['staff_count'] ?> officers</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted">Live Applications:</span>
                  <span class="fw-bold text-primary"><?= (int)$b['total_applications'] ?></span>
                </div>
                <div class="d-flex justify-content-between">
                  <span class="text-muted">Branch Revenue:</span>
                  <span class="fw-bold text-success"><?= format_currency((float)$b['total_revenue']) ?></span>
                </div>
              </div>

              <div class="text-muted small" style="font-size: 0.72rem;">
                <div><i class="fa-solid fa-phone me-1 text-primary"></i> <?= e($b['phone'] ?: '—') ?></div>
                <div><i class="fa-regular fa-envelope me-1 text-primary"></i> <?= e($b['email'] ?: '—') ?></div>
              </div>
            </div>

            <div class="d-flex align-items-center justify-content-between pt-3 mt-3 border-top">
              <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2.5 fw-semibold" data-bs-toggle="modal" data-bs-target="#editBranchModal<?= $b['id'] ?>">
                <i class="fa-solid fa-pen-to-square me-1"></i> Edit
              </button>
              <form action="/branches/toggle-status" method="POST" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                <button type="submit" class="btn btn-sm <?= $isActive ? 'btn-outline-danger' : 'btn-outline-success' ?> py-1 px-2">
                  <i class="fa-solid <?= $isActive ? 'fa-ban' : 'fa-check' ?> me-1"></i><?= $isActive ? 'Suspend' : 'Activate' ?>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- MODAL: EDIT BRANCH -->
      <div class="modal fade" id="editBranchModal<?= $b['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
              <h6 class="modal-title fw-bold"><i class="fa-solid fa-building-pen me-2"></i> Edit Branch Office</h6>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/branches/update" method="POST">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= $b['id'] ?>">
              <div class="modal-body p-4">
                <div class="row g-2 mb-3">
                  <div class="col-8">
                    <label class="form-label small fw-semibold">Branch Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= e($b['name']) ?>" required>
                  </div>
                  <div class="col-4">
                    <label class="form-label small fw-semibold">Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" value="<?= e($b['code']) ?>" required>
                  </div>
                </div>
                <div class="row g-2 mb-3">
                  <div class="col-6">
                    <label class="form-label small fw-semibold">Country <span class="text-danger">*</span></label>
                    <input type="text" name="country" class="form-control" value="<?= e($b['country']) ?>" required>
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-semibold">City</label>
                    <input type="text" name="city" class="form-control" value="<?= e($b['city']) ?>">
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label small fw-semibold">Office Address</label>
                  <input type="text" name="address" class="form-control" value="<?= e($b['address']) ?>">
                </div>
                <div class="row g-2 mb-0">
                  <div class="col-6">
                    <label class="form-label small fw-semibold">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= e($b['phone']) ?>">
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= e($b['email']) ?>">
                  </div>
                </div>
              </div>
              <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">Save Changes</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- MODAL: ADD BRANCH -->
<div class="modal fade" id="newBranchModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-building me-2"></i> Register New Branch</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/branches/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="row g-2 mb-3">
            <div class="col-8">
              <label class="form-label small fw-semibold">Branch Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Singapore Operations Desk" required>
            </div>
            <div class="col-4">
              <label class="form-label small fw-semibold">Code <span class="text-danger">*</span></label>
              <input type="text" name="code" class="form-control" placeholder="SGP-01" required>
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Country <span class="text-danger">*</span></label>
              <input type="text" name="country" class="form-control" placeholder="Singapore" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">City</label>
              <input type="text" name="city" class="form-control" placeholder="Singapore">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Office Address</label>
            <input type="text" name="address" class="form-control" placeholder="Street, Building, Suite">
          </div>
          <div class="row g-2 mb-0">
            <div class="col-6">
              <label class="form-label small fw-semibold">Phone</label>
              <input type="text" name="phone" class="form-control" placeholder="+65 6123 4567">
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Email</label>
              <input type="email" name="email" class="form-control" placeholder="desk@mstravelhub.com">
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">Save Branch</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
