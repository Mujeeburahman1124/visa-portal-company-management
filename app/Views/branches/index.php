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
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <!-- 3 View Options Switcher (Responsive) -->
      <div class="btn-group btn-group-sm bg-white shadow-sm border rounded-pill p-1 view-switcher-pill-group" role="group" aria-label="Branch View Mode">
        <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 fw-semibold branch-view-btn btn-primary shadow-sm" id="btnBranchViewGrid" onclick="switchBranchView('grid')" title="Cards Grid View">
          <i class="fa-solid fa-grip me-1"></i> <span class="d-none d-sm-inline">Cards</span>
        </button>
        <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 fw-semibold branch-view-btn btn-light text-muted" id="btnBranchViewTable" onclick="switchBranchView('table')" title="Table View">
          <i class="fa-solid fa-table-list me-1"></i> <span class="d-none d-sm-inline">Table</span>
        </button>
        <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 fw-semibold branch-view-btn btn-light text-muted" id="btnBranchViewCompact" onclick="switchBranchView('compact')" title="Compact List View">
          <i class="fa-solid fa-list-ul me-1"></i> <span class="d-none d-sm-inline">Compact</span>
        </button>
      </div>

      <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#newBranchModal">
        <i class="fa-solid fa-plus me-1"></i> Register New Branch
      </button>
    </div>
  </div>

  <!-- VIEW OPTION 1: BRANCH CARDS GRID -->
  <div id="branchViewGrid" class="branch-view-container">
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

  <!-- VIEW OPTION 2: BRANCH DATA TABLE -->
  <div id="branchViewTable" class="branch-view-container d-none mb-4">
    <div class="card card-enterprise shadow-sm border bg-white">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr class="small text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
              <th class="ps-3">Branch &amp; Hub</th>
              <th>Code</th>
              <th>Status</th>
              <th>Assigned Staff</th>
              <th>Live Applications</th>
              <th>Branch Revenue</th>
              <th>Contact Details</th>
              <th class="text-end pe-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($branches as $b): ?>
              <?php $isActive = (int)$b['is_active'] === 1; ?>
              <tr>
                <td class="ps-3">
                  <div class="fw-bold text-dark"><?= e($b['name']) ?></div>
                  <div class="small text-muted"><i class="fa-solid fa-location-dot me-1 text-danger"></i> <?= e($b['city']) ?>, <?= e($b['country']) ?></div>
                </td>
                <td>
                  <span class="badge bg-primary-subtle text-primary fw-bold border"><?= e($b['code']) ?></span>
                </td>
                <td>
                  <span class="badge <?= $isActive ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> fw-semibold">
                    <?= $isActive ? 'Active' : 'Suspended' ?>
                  </span>
                </td>
                <td>
                  <div class="small fw-semibold text-dark"><i class="fa-solid fa-users me-1 text-muted"></i> <?= (int)$b['staff_count'] ?> officers</div>
                </td>
                <td>
                  <div class="small fw-bold text-primary"><i class="fa-solid fa-folder-open me-1"></i> <?= (int)$b['total_applications'] ?></div>
                </td>
                <td>
                  <div class="small fw-bold text-success"><?= format_currency((float)$b['total_revenue']) ?></div>
                </td>
                <td>
                  <div class="small text-muted" style="font-size: 0.75rem;">
                    <div><i class="fa-solid fa-phone me-1 text-primary"></i> <?= e($b['phone'] ?: '—') ?></div>
                    <div><i class="fa-regular fa-envelope me-1 text-primary"></i> <?= e($b['email'] ?: '—') ?></div>
                  </div>
                </td>
                <td class="text-end pe-3">
                  <div class="d-flex align-items-center justify-content-end gap-1">
                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" data-bs-toggle="modal" data-bs-target="#editBranchModal<?= $b['id'] ?>" title="Edit Branch">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <form action="/branches/toggle-status" method="POST" class="d-inline">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= $b['id'] ?>">
                      <button type="submit" class="btn btn-sm <?= $isActive ? 'btn-outline-danger' : 'btn-outline-success' ?> py-1 px-2" title="<?= $isActive ? 'Suspend' : 'Activate' ?>">
                        <i class="fa-solid <?= $isActive ? 'fa-ban' : 'fa-check' ?>"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- VIEW OPTION 3: BRANCH COMPACT LIST VIEW -->
  <div id="branchViewCompact" class="branch-view-container d-none mb-4">
    <div class="list-group shadow-xs rounded-3">
      <?php foreach ($branches as $b): ?>
        <?php $isActive = (int)$b['is_active'] === 1; ?>
        <div class="list-group-item list-group-item-action d-flex flex-wrap align-items-center justify-content-between p-3 gap-2 border-start-0 border-end-0">
          <div class="d-flex align-items-center gap-3 flex-grow-1" style="min-width: 240px;">
            <div class="rounded-pill p-2 bg-primary-subtle text-primary fw-bold text-center" style="width: 48px; height: 48px; line-height: 32px;">
              <?= e($b['code']) ?>
            </div>
            <div>
              <div class="d-flex align-items-center gap-2">
                <h6 class="fw-bold text-dark mb-0"><?= e($b['name']) ?></h6>
                <span class="badge <?= $isActive ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> fw-semibold" style="font-size: 0.68rem;">
                  <?= $isActive ? 'Active' : 'Suspended' ?>
                </span>
              </div>
              <div class="small text-muted" style="font-size: 0.75rem;">
                <i class="fa-solid fa-location-dot text-danger me-1"></i> <?= e($b['city']) ?>, <?= e($b['country']) ?> &bull; 
                <span class="text-dark fw-medium"><?= (int)$b['staff_count'] ?> officers</span> &bull; 
                <span class="text-primary fw-medium"><?= (int)$b['total_applications'] ?> apps</span>
              </div>
            </div>
          </div>

          <div class="d-flex align-items-center gap-3">
            <span class="badge bg-success-subtle text-success border fw-bold px-2.5 py-1.5" style="font-size: 0.8rem;">
              <?= format_currency((float)$b['total_revenue']) ?>
            </span>
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-outline-primary py-1 px-2.5" data-bs-toggle="modal" data-bs-target="#editBranchModal<?= $b['id'] ?>">
                <i class="fa-solid fa-pen-to-square me-1"></i> Edit
              </button>
              <form action="/branches/toggle-status" method="POST" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                <button type="submit" class="btn <?= $isActive ? 'btn-outline-danger' : 'btn-outline-success' ?> py-1 px-2 border-start-0">
                  <i class="fa-solid <?= $isActive ? 'fa-ban' : 'fa-check' ?>"></i>
                </button>
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
              <input type="email" name="email" class="form-control" placeholder="branch@mshorizonuae.com">
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

<script>
function switchBranchView(mode) {
  document.querySelectorAll('.branch-view-container').forEach(el => el.classList.add('d-none'));
  document.querySelectorAll('.branch-view-btn').forEach(btn => {
    btn.classList.remove('btn-primary', 'shadow-sm', 'active');
    btn.classList.add('btn-light', 'text-muted');
  });

  if (mode === 'table') {
    const tableEl = document.getElementById('branchViewTable');
    if (tableEl) tableEl.classList.remove('d-none');
    const tableBtn = document.getElementById('btnBranchViewTable');
    if (tableBtn) {
      tableBtn.classList.remove('btn-light', 'text-muted');
      tableBtn.classList.add('btn-primary', 'shadow-sm', 'active');
    }
    try { localStorage.setItem('vt_branch_view', 'table'); } catch(e) {}
  } else if (mode === 'compact') {
    const compactEl = document.getElementById('branchViewCompact');
    if (compactEl) compactEl.classList.remove('d-none');
    const compactBtn = document.getElementById('btnBranchViewCompact');
    if (compactBtn) {
      compactBtn.classList.remove('btn-light', 'text-muted');
      compactBtn.classList.add('btn-primary', 'shadow-sm', 'active');
    }
    try { localStorage.setItem('vt_branch_view', 'compact'); } catch(e) {}
  } else {
    const gridEl = document.getElementById('branchViewGrid');
    if (gridEl) gridEl.classList.remove('d-none');
    const gridBtn = document.getElementById('btnBranchViewGrid');
    if (gridBtn) {
      gridBtn.classList.remove('btn-light', 'text-muted');
      gridBtn.classList.add('btn-primary', 'shadow-sm', 'active');
    }
    try { localStorage.setItem('vt_branch_view', 'grid'); } catch(e) {}
  }
}

document.addEventListener('DOMContentLoaded', function() {
  const savedView = (function() {
    try { return localStorage.getItem('vt_branch_view') || (window.innerWidth < 768 ? 'grid' : 'grid'); } catch(e) { return 'grid'; }
  })();
  if (savedView === 'table' || savedView === 'compact') {
    switchBranchView(savedView);
  }
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
