<?php
$pageTitle = 'Operating Branches & Global Desks — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';

$totalBranches = count($branches);
$activeBranches = count(array_filter($branches, fn($x) => (int)($x['is_active'] ?? 0) === 1));
$totalStaffCount = array_sum(array_column($branches, 'staff_count'));
$totalBranchRevenue = array_sum(array_column($branches, 'total_revenue'));
$currentView = $_GET['view'] ?? 'cards';
?>

<!-- Link Dedicated Bento CSS -->
<link rel="stylesheet" href="/assets/css/dashboard-bento.css">
<link rel="stylesheet" href="/assets/css/pages/branches-index.css">

<div class="content-body branches-dashboard">
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
  <div class="branches-hero">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
          <h1 class="branches-hero-title mb-0">
            <i class="fa-solid fa-building text-danger"></i> Operating Branches &amp; Global Desks
          </h1>
          <span class="branches-hero-badge">
            <i class="fa-solid fa-network-wired me-1"></i> Multi-Branch Network
          </span>
        </div>
        <p class="branches-hero-desc">
          Worldwide operations hubs, localized consular processing centers, staff allocations, and real-time regional performance attribution.
        </p>
      </div>

      <div class="d-flex align-items-center gap-2 flex-wrap">
        <button type="button" class="btn btn-primary btn-sm px-3.5 shadow-sm rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#newBranchModal">
          <i class="fa-solid fa-plus-circle me-1"></i> Register New Branch
        </button>
      </div>
    </div>
  </div>

  <!-- Bento KPI Metrics Strip -->
  <div class="bento-branch-kpi-grid">
    <!-- Card 1: Total Branches -->
    <div class="bento-branch-kpi-card" style="--kpi-accent: #E11D48; --kpi-icon-bg: #FFF1F2; --kpi-icon-color: #E11D48;">
      <div class="bento-branch-kpi-header">
        <span class="bento-branch-kpi-title">Total Offices</span>
        <div class="bento-branch-kpi-icon">
          <i class="fa-solid fa-building"></i>
        </div>
      </div>
      <div class="bento-branch-kpi-value"><?= $totalBranches ?></div>
      <div class="bento-branch-kpi-footer">
        <i class="fa-solid fa-map-pin text-danger"></i>
        <span>Global operational hubs</span>
      </div>
    </div>

    <!-- Card 2: Active Desks -->
    <div class="bento-branch-kpi-card" style="--kpi-accent: #10B981; --kpi-icon-bg: #ECFDF5; --kpi-icon-color: #059669;">
      <div class="bento-branch-kpi-header">
        <span class="bento-branch-kpi-title">Active Desks</span>
        <div class="bento-branch-kpi-icon">
          <i class="fa-solid fa-circle-check"></i>
        </div>
      </div>
      <div class="bento-branch-kpi-value text-success"><?= $activeBranches ?></div>
      <div class="bento-branch-kpi-footer">
        <i class="fa-solid fa-shield-halved text-success"></i>
        <span>Authorized processing desks</span>
      </div>
    </div>

    <!-- Card 3: Staff Assigned -->
    <div class="bento-branch-kpi-card" style="--kpi-accent: #0284C7; --kpi-icon-bg: #F0F9FF; --kpi-icon-color: #0284C7;">
      <div class="bento-branch-kpi-header">
        <span class="bento-branch-kpi-title">Assigned Officers</span>
        <div class="bento-branch-kpi-icon">
          <i class="fa-solid fa-users"></i>
        </div>
      </div>
      <div class="bento-branch-kpi-value text-primary"><?= $totalStaffCount ?></div>
      <div class="bento-branch-kpi-footer">
        <i class="fa-solid fa-user-tie text-primary"></i>
        <span>Active team personnel deployed</span>
      </div>
    </div>

    <!-- Card 4: Branch Turnover -->
    <div class="bento-branch-kpi-card" style="--kpi-accent: #8B5CF6; --kpi-icon-bg: #F5F3FF; --kpi-icon-color: #7C3AED;">
      <div class="bento-branch-kpi-header">
        <span class="bento-branch-kpi-title">Network Turnover</span>
        <div class="bento-branch-kpi-icon">
          <i class="fa-solid fa-coins"></i>
        </div>
      </div>
      <div class="bento-branch-kpi-value" style="color: #7C3AED;"><?= format_currency($totalBranchRevenue) ?></div>
      <div class="bento-branch-kpi-footer">
        <i class="fa-solid fa-chart-line text-purple"></i>
        <span>Consolidated regional settlements</span>
      </div>
    </div>
  </div>

  <!-- Bento Toolbar -->
  <div class="bento-branch-toolbar">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="d-flex align-items-center gap-2">
        <span class="fw-bold text-dark fs-6"><i class="fa-solid fa-layer-group text-danger me-2"></i>Global Network Directory</span>
        <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1"><?= $totalBranches ?> Desks</span>
      </div>

      <!-- 3-View Segmented Switcher -->
      <div class="view-switcher-pill-group" role="group" aria-label="Branch View Mode">
        <button type="button" class="branch-view-btn <?= $currentView === 'cards' ? 'active' : '' ?>" onclick="switchBranchView('cards')" id="btnBranchViewCards" title="Cards View">
          <i class="fa-solid fa-grip"></i> <span>Cards</span>
        </button>
        <button type="button" class="branch-view-btn <?= $currentView === 'table' ? 'active' : '' ?>" onclick="switchBranchView('table')" id="btnBranchViewTable" title="Table View">
          <i class="fa-solid fa-table-list"></i> <span>Table</span>
        </button>
        <button type="button" class="branch-view-btn <?= $currentView === 'compact' ? 'active' : '' ?>" onclick="switchBranchView('compact')" id="btnBranchViewCompact" title="List View">
          <i class="fa-solid fa-list-ul"></i> <span>List</span>
        </button>
      </div>
    </div>
  </div>

  <!-- ================================================================= -->
  <!-- VIEW OPTION 1: BRANCH CARDS GRID -->
  <!-- ================================================================= -->
  <div id="branchViewCards" class="branch-view-container <?= $currentView === 'cards' ? '' : 'd-none' ?>">
    <div class="row g-3">
      <?php foreach ($branches as $b): ?>
        <?php 
          $isActive = (int)($b['is_active'] ?? 0) === 1; 
          $bJson = htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8');
        ?>
        <div class="col-12 col-md-6 col-xl-3">
          <div class="bento-branch-card">
            <div>
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="branch-code-chip"><?= e($b['code']) ?></span>
                <span class="badge <?= $isActive ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> fw-bold px-2 py-1 rounded-pill" style="font-size: 0.72rem;">
                  <?= $isActive ? 'Active Desk' : 'Suspended' ?>
                </span>
              </div>

              <h3 class="fw-bold text-dark mb-1 fs-6 text-truncate">
                <?= e($b['name']) ?>
              </h3>
              <div class="text-muted small mb-3 text-truncate">
                <i class="fa-solid fa-location-dot me-1 text-danger"></i> <?= e($b['city'] ?: 'City') ?>, <?= e($b['country']) ?>
              </div>

              <div class="bento-branch-metrics-bar">
                <div>
                  <div class="bento-branch-metric-lbl">Staff</div>
                  <div class="bento-branch-metric-val"><?= (int)$b['staff_count'] ?></div>
                </div>
                <div>
                  <div class="bento-branch-metric-lbl">Visas</div>
                  <div class="bento-branch-metric-val text-primary"><?= (int)$b['total_applications'] ?></div>
                </div>
                <div>
                  <div class="bento-branch-metric-lbl">Revenue</div>
                  <div class="bento-branch-metric-val text-success" style="font-size: 0.75rem;"><?= format_currency((float)$b['total_revenue']) ?></div>
                </div>
              </div>

              <div class="p-2.5 bg-light rounded-3 border mb-3 small text-muted" style="font-size: 0.75rem;">
                <div class="text-truncate"><i class="fa-solid fa-phone me-1 text-primary"></i> <?= e($b['phone'] ?: 'No phone registered') ?></div>
                <div class="text-truncate mt-1"><i class="fa-regular fa-envelope me-1 text-primary"></i> <?= e($b['email'] ?: 'No official email') ?></div>
              </div>
            </div>

            <div class="pt-2 border-top d-flex align-items-center justify-content-between gap-1 flex-wrap mt-2">
              <button type="button" class="btn btn-sm btn-outline-primary py-1 px-3 rounded-pill fw-semibold" onclick="openEditBranchModal(<?= $bJson ?>)">
                <i class="fa-solid fa-pen-to-square me-1"></i> Edit
              </button>
              <form action="/branches/toggle-status" method="POST" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                <button type="submit" class="btn btn-sm <?= $isActive ? 'btn-outline-danger' : 'btn-outline-success' ?> py-1 px-2.5 rounded-pill">
                  <i class="fa-solid <?= $isActive ? 'fa-ban' : 'fa-check' ?> me-1"></i><?= $isActive ? 'Suspend' : 'Activate' ?>
                </button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ================================================================= -->
  <!-- VIEW OPTION 2: BRANCH DATA TABLE -->
  <!-- ================================================================= -->
  <div id="branchViewTable" class="branch-view-container <?= $currentView === 'table' ? '' : 'd-none' ?>">
    <div class="bento-branch-table-card">
      <div class="table-responsive mb-0">
        <table class="bento-branch-table align-middle">
          <thead>
            <tr>
              <th style="min-width: 100px;">Branch Code</th>
              <th style="min-width: 200px;">Branch &amp; Hub</th>
              <th style="min-width: 140px;">Location</th>
              <th style="min-width: 100px;">Status</th>
              <th style="min-width: 100px;">Staff Officers</th>
              <th style="min-width: 100px;">Live Visas</th>
              <th style="min-width: 130px;">Branch Revenue</th>
              <th style="min-width: 180px;">Contact Details</th>
              <th class="text-end" style="min-width: 120px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($branches as $b): ?>
              <?php 
                $isActive = (int)($b['is_active'] ?? 0) === 1;
                $bJson = htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8');
              ?>
              <tr>
                <td data-label="Branch Code">
                  <span class="branch-code-chip"><?= e($b['code']) ?></span>
                </td>
                <td data-label="Branch & Hub">
                  <strong class="text-dark"><?= e($b['name']) ?></strong>
                </td>
                <td data-label="Location" class="small text-muted">
                  <i class="fa-solid fa-location-dot me-1 text-danger"></i> <?= e($b['city'] ?: '') ?><?= (!empty($b['city']) && !empty($b['country'])) ? ', ' : '' ?><?= e($b['country']) ?>
                </td>
                <td data-label="Status">
                  <span class="badge <?= $isActive ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> fw-semibold px-2 py-0.5 rounded-pill">
                    <?= $isActive ? 'Active' : 'Suspended' ?>
                  </span>
                </td>
                <td data-label="Staff Officers">
                  <span class="badge bg-light text-dark border font-monospace"><?= (int)$b['staff_count'] ?> officers</span>
                </td>
                <td data-label="Live Visas">
                  <span class="badge bg-primary rounded-pill px-2.5 py-1 font-monospace"><?= (int)$b['total_applications'] ?></span>
                </td>
                <td data-label="Branch Revenue" class="fw-bold font-monospace text-success">
                  <?= format_currency((float)$b['total_revenue']) ?>
                </td>
                <td data-label="Contact Details" class="small text-muted">
                  <div><?= e($b['phone'] ?: '—') ?></div>
                  <div><?= e($b['email'] ?: '—') ?></div>
                </td>
                <td data-label="Actions" class="text-end">
                  <div class="d-inline-flex align-items-center gap-1">
                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2.5 rounded-pill" onclick="openEditBranchModal(<?= $bJson ?>)" title="Edit Branch">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <form action="/branches/toggle-status" method="POST" class="d-inline">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= $b['id'] ?>">
                      <button type="submit" class="btn btn-sm <?= $isActive ? 'btn-outline-danger' : 'btn-outline-success' ?> py-1 px-2 rounded-pill" title="<?= $isActive ? 'Suspend' : 'Activate' ?>">
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

  <!-- ================================================================= -->
  <!-- VIEW OPTION 3: BRANCH COMPACT LIST VIEW -->
  <!-- ================================================================= -->
  <div id="branchViewCompact" class="branch-view-container <?= $currentView === 'compact' ? '' : 'd-none' ?>">
    <div class="bento-branch-table-card">
      <ul class="list-group list-group-flush mb-0">
        <?php foreach ($branches as $b): ?>
          <?php 
            $isActive = (int)($b['is_active'] ?? 0) === 1;
            $bJson = htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8');
          ?>
          <li class="list-group-item p-3 d-flex flex-wrap align-items-center justify-content-between gap-2.5">
            <div class="d-flex align-items-center gap-3">
              <span class="branch-code-chip"><?= e($b['code']) ?></span>
              <div>
                <div class="d-flex align-items-center gap-2">
                  <h3 class="fw-bold text-dark mb-0 fs-6"><?= e($b['name']) ?></h3>
                  <span class="badge <?= $isActive ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> rounded-pill" style="font-size: 0.68rem;">
                    <?= $isActive ? 'Active' : 'Suspended' ?>
                  </span>
                </div>
                <div class="small text-muted" style="font-size: 0.75rem;">
                  <i class="fa-solid fa-location-dot text-danger me-1"></i> <?= e($b['city'] ?: '') ?>, <?= e($b['country']) ?> &bull; 
                  <span class="text-dark fw-medium"><?= (int)$b['staff_count'] ?> officers</span> &bull; 
                  <span class="text-primary fw-medium"><?= (int)$b['total_applications'] ?> apps</span>
                </div>
              </div>
            </div>

            <div class="d-flex align-items-center gap-3">
              <span class="badge bg-success-subtle text-success border fw-bold px-2.5 py-1.5 font-monospace" style="font-size: 0.8rem;">
                <?= format_currency((float)$b['total_revenue']) ?>
              </span>
              <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2.5 rounded-pill" onclick="openEditBranchModal(<?= $bJson ?>)">
                <i class="fa-solid fa-pen-to-square me-1"></i> Edit
              </button>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<!-- ================================================================= -->
<!-- MODALS -->
<!-- ================================================================= -->

<!-- MODAL: ADD BRANCH -->
<div class="modal fade" id="newBranchModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow" style="border-radius: var(--bento-radius);">
      <div class="modal-header bg-dark text-white" style="border-top-left-radius: var(--bento-radius); border-top-right-radius: var(--bento-radius);">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-building me-2 text-danger"></i> Register New Operating Branch</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/branches/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="row g-2 mb-3">
            <div class="col-8">
              <label class="form-label small fw-semibold">Branch Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Dubai Central Hub" required>
            </div>
            <div class="col-4">
              <label class="form-label small fw-semibold">Code <span class="text-danger">*</span></label>
              <input type="text" name="code" class="form-control font-monospace" placeholder="DXB-01" required>
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Country <span class="text-danger">*</span></label>
              <input type="text" name="country" class="form-control" placeholder="United Arab Emirates" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">City</label>
              <input type="text" name="city" class="form-control" placeholder="Dubai">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Office Address</label>
            <input type="text" name="address" class="form-control" placeholder="Street, Building, Floor">
          </div>
          <div class="row g-2 mb-0">
            <div class="col-6">
              <label class="form-label small fw-semibold">Phone</label>
              <input type="text" name="phone" class="form-control" placeholder="+971 4 123 4567">
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Email</label>
              <input type="email" name="email" class="form-control" placeholder="dubai@visatrack.com">
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light" style="border-bottom-left-radius: var(--bento-radius); border-bottom-right-radius: var(--bento-radius);">
          <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold rounded-pill px-4"><i class="fa-solid fa-save me-1"></i> Save Branch</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: EDIT BRANCH -->
<div class="modal fade" id="editBranchModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow" style="border-radius: var(--bento-radius);">
      <div class="modal-header bg-dark text-white" style="border-top-left-radius: var(--bento-radius); border-top-right-radius: var(--bento-radius);">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-building-pen me-2 text-danger"></i> Edit Branch Office</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/branches/update" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="edit_branch_id" value="">
        <div class="modal-body p-4">
          <div class="row g-2 mb-3">
            <div class="col-8">
              <label class="form-label small fw-semibold">Branch Name <span class="text-danger">*</span></label>
              <input type="text" name="name" id="edit_branch_name" class="form-control" required>
            </div>
            <div class="col-4">
              <label class="form-label small fw-semibold">Code <span class="text-danger">*</span></label>
              <input type="text" name="code" id="edit_branch_code" class="form-control font-monospace" required>
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Country <span class="text-danger">*</span></label>
              <input type="text" name="country" id="edit_branch_country" class="form-control" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">City</label>
              <input type="text" name="city" id="edit_branch_city" class="form-control">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Office Address</label>
            <input type="text" name="address" id="edit_branch_address" class="form-control">
          </div>
          <div class="row g-2 mb-0">
            <div class="col-6">
              <label class="form-label small fw-semibold">Phone</label>
              <input type="text" name="phone" id="edit_branch_phone" class="form-control">
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Email</label>
              <input type="email" name="email" id="edit_branch_email" class="form-control">
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light" style="border-bottom-left-radius: var(--bento-radius); border-bottom-right-radius: var(--bento-radius);">
          <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold rounded-pill px-4"><i class="fa-solid fa-save me-1"></i> Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function switchBranchView(mode) {
  document.querySelectorAll('.branch-view-container').forEach(el => el.classList.add('d-none'));
  document.querySelectorAll('.branch-view-btn').forEach(btn => btn.classList.remove('active'));

  if (mode === 'table') {
    const tableEl = document.getElementById('branchViewTable');
    if (tableEl) tableEl.classList.remove('d-none');
    const tableBtn = document.getElementById('btnBranchViewTable');
    if (tableBtn) tableBtn.classList.add('active');
    try { localStorage.setItem('vt_branch_view', 'table'); } catch(e) {}
  } else if (mode === 'compact') {
    const compactEl = document.getElementById('branchViewCompact');
    if (compactEl) compactEl.classList.remove('d-none');
    const compactBtn = document.getElementById('btnBranchViewCompact');
    if (compactBtn) compactBtn.classList.add('active');
    try { localStorage.setItem('vt_branch_view', 'compact'); } catch(e) {}
  } else {
    const gridEl = document.getElementById('branchViewCards');
    if (gridEl) gridEl.classList.remove('d-none');
    const gridBtn = document.getElementById('btnBranchViewCards');
    if (gridBtn) gridBtn.classList.add('active');
    try { localStorage.setItem('vt_branch_view', 'cards'); } catch(e) {}
  }
}

function openEditBranchModal(data) {
  if (!data) return;
  document.getElementById('edit_branch_id').value = data.id || '';
  document.getElementById('edit_branch_name').value = data.name || '';
  document.getElementById('edit_branch_code').value = data.code || '';
  document.getElementById('edit_branch_country').value = data.country || '';
  document.getElementById('edit_branch_city').value = data.city || '';
  document.getElementById('edit_branch_address').value = data.address || '';
  document.getElementById('edit_branch_phone').value = data.phone || '';
  document.getElementById('edit_branch_email').value = data.email || '';

  window.openModalById('editBranchModal');
}

document.addEventListener('DOMContentLoaded', function() {
  const savedView = (function() {
    try { return localStorage.getItem('vt_branch_view') || 'cards'; } catch(e) { return 'cards'; }
  })();
  if (savedView === 'table' || savedView === 'compact') {
    switchBranchView(savedView);
  }
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
