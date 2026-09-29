<?php
$pageTitle = 'Customer & Applicant Profiles — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';

$currentView = $_GET['view'] ?? 'table';
if (!in_array($currentView, ['table', 'grid', 'compact'], true)) {
    $currentView = 'table';
}
?>

<div class="content-body">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Header with 3 View Switchers -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <div class="d-flex align-items-center gap-2">
        <h3 class="fw-bold brand-font mb-0 text-dark"><i class="fa-solid fa-users text-primary me-2"></i> Applicant &amp; Customer Profiles</h3>
        <span class="badge bg-primary-subtle text-primary border rounded-pill"><?= count($customers) ?> Applicants</span>
      </div>
      <p class="text-muted small mb-0 mt-1">Directory of registered visa applicants, global corporate sponsors, and linked file histories.</p>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <!-- 3 View Options Switcher -->
      <div class="btn-group btn-group-sm bg-white shadow-sm border rounded-pill p-1" role="group" aria-label="View Mode">
        <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold cust-view-btn <?= $currentView === 'table' ? 'btn-primary shadow-sm' : 'btn-light text-muted' ?>" onclick="switchCustView('table')" id="btnCustViewTable">
          <i class="fa-solid fa-table-list me-1"></i> <span class="d-none d-sm-inline">Table</span>
        </button>
        <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold cust-view-btn <?= $currentView === 'grid' ? 'btn-primary shadow-sm' : 'btn-light text-muted' ?>" onclick="switchCustView('grid')" id="btnCustViewGrid">
          <i class="fa-solid fa-grip me-1"></i> <span class="d-none d-sm-inline">Grid Cards</span>
        </button>
        <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold cust-view-btn <?= $currentView === 'compact' ? 'btn-primary shadow-sm' : 'btn-light text-muted' ?>" onclick="switchCustView('compact')" id="btnCustViewCompact">
          <i class="fa-solid fa-list-ul me-1"></i> <span class="d-none d-sm-inline">Compact List</span>
        </button>
      </div>

      <a href="/customers/create" class="btn btn-primary btn-sm px-3 shadow-sm">
        <i class="fa-solid fa-user-plus me-1"></i> Register Applicant
      </a>
    </div>
  </div>

  <!-- Search & Filter Toolbar -->
  <div class="card card-enterprise mb-4 shadow-sm border bg-white">
    <div class="card-body p-3">
      <form action="/customers" method="GET" class="row g-2 align-items-center" id="custFilterForm">
        <input type="hidden" name="view" id="activeCustViewParam" value="<?= e($currentView) ?>">
        <div class="col-12 col-md-5">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search name, code, passport, mobile, email..." value="<?= e($_GET['search'] ?? '') ?>">
          </div>
        </div>
        <div class="col-6 col-md-3">
          <select name="nationality" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Nationalities</option>
            <?php foreach (($nationalities ?? []) as $nat): ?>
              <option value="<?= e($nat) ?>" <?= ($_GET['nationality'] ?? '') === $nat ? 'selected' : '' ?>><?= e($nat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold"><i class="fa-solid fa-filter me-1"></i> Filter</button>
        </div>
        <div class="col-12 col-md-2 d-flex">
          <a href="/customers" class="btn btn-light btn-sm border w-100 text-center" title="Clear Filters"><i class="fa-solid fa-rotate-left me-1"></i> Reset</a>
        </div>
      </form>
    </div>
  </div>

  <?php if (empty($customers)): ?>
    <div class="card card-enterprise shadow-sm border text-center py-5 p-4 bg-white mb-4">
      <div class="mb-3">
        <i class="fa-regular fa-user-slash text-muted fs-1 opacity-50"></i>
      </div>
      <h5 class="fw-bold text-dark mb-1">No Applicant Profiles Found</h5>
      <p class="text-muted small mb-3">No customers or applicants matched your search parameters.</p>
      <div>
        <a href="/customers/create" class="btn btn-primary btn-sm px-3 shadow-sm">
          <i class="fa-solid fa-user-plus me-1"></i> Register New Applicant
        </a>
      </div>
    </div>
  <?php else: ?>

    <!-- ================================================================= -->
    <!-- VIEW OPTION 1: TABLE VIEW -->
    <!-- ================================================================= -->
    <div id="custViewTable" class="cust-view-container <?= $currentView === 'table' ? '' : 'd-none' ?>">
      <div class="card card-enterprise shadow-sm border">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-3">Customer ID</th>
                <th>Applicant Name</th>
                <th>Primary Passport</th>
                <th>Nationality</th>
                <th>Country of Residence</th>
                <th>Mobile / Phone</th>
                <th>Applications</th>
                <th class="text-end pe-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($customers as $c): ?>
                <tr>
                  <td class="ps-3"><span class="fw-bold text-primary font-monospace"><?= e($c['customer_code']) ?></span></td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="avatar-sm rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.75rem;">
                        <?= strtoupper(substr($c['full_name'], 0, 2)) ?>
                      </div>
                      <div>
                        <a href="/customers/show?id=<?= $c['id'] ?>" class="fw-bold text-dark text-decoration-none">
                          <?= e($c['full_name']) ?>
                        </a>
                        <div class="text-muted small" style="font-size: 0.72rem;"><?= e($c['email'] ?: 'No email registered') ?></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border font-monospace"><?= e($c['passport_number'] ?? 'PENDING') ?></span>
                  </td>
                  <td><?= e($c['nationality']) ?></td>
                  <td><?= e($c['current_country']) ?></td>
                  <td>
                    <div class="small fw-semibold text-dark"><i class="fa-solid fa-phone small me-1 text-muted"></i><?= e($c['mobile']) ?></div>
                  </td>
                  <td>
                    <span class="badge bg-primary-subtle text-primary border rounded-pill px-2.5 py-1"><?= $c['total_applications'] ?> Total</span>
                    <?php if ($c['active_applications'] > 0): ?>
                      <span class="badge bg-warning text-dark rounded-pill ms-1 px-2.5 py-1"><?= $c['active_applications'] ?> Active</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end pe-3">
                    <div class="btn-group btn-group-sm">
                      <a href="/customers/show?id=<?= $c['id'] ?>" class="btn btn-outline-primary py-1 px-2.5" title="View Profile">
                        <i class="fa-solid fa-eye"></i>
                      </a>
                      <a href="/customers/edit?id=<?= $c['id'] ?>" class="btn btn-outline-secondary py-1 px-2.5" title="Edit Profile">
                        <i class="fa-solid fa-pen-to-square"></i>
                      </a>
                      <form action="/customers/delete" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete applicant <?= e($c['customer_code']) ?> (<?= e($c['full_name']) ?>) and linked records?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="customer_id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn btn-outline-danger py-1 px-2.5" title="Delete Profile">
                          <i class="fa-solid fa-trash-can"></i>
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
    <!-- VIEW OPTION 2: GRID CARDS VIEW -->
    <!-- ================================================================= -->
    <div id="custViewGrid" class="cust-view-container <?= $currentView === 'grid' ? '' : 'd-none' ?>">
      <div class="row g-3">
        <?php foreach ($customers as $c): ?>
          <div class="col-12 col-md-6 col-lg-4">
            <div class="card card-enterprise h-100 shadow-sm border">
              <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                <div>
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-primary-subtle text-primary border font-monospace small px-2 py-0.5">
                      <?= e($c['customer_code']) ?>
                    </span>
                    <div>
                      <span class="badge bg-primary rounded-pill"><?= $c['total_applications'] ?> Files</span>
                      <?php if ($c['active_applications'] > 0): ?>
                        <span class="badge bg-warning text-dark rounded-pill ms-1"><?= $c['active_applications'] ?> In Progress</span>
                      <?php endif; ?>
                    </div>
                  </div>

                  <div class="d-flex align-items-center gap-2.5 mb-3">
                    <div class="avatar rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 44px; height: 44px; font-size: 0.95rem;">
                      <?= strtoupper(substr($c['full_name'], 0, 2)) ?>
                    </div>
                    <div class="overflow-hidden">
                      <h6 class="fw-bold text-dark mb-0 text-truncate">
                        <a href="/customers/show?id=<?= $c['id'] ?>" class="text-dark text-decoration-none"><?= e($c['full_name']) ?></a>
                      </h6>
                      <div class="text-muted small text-truncate" style="font-size: 0.75rem;"><?= e($c['email'] ?: 'No email specified') ?></div>
                    </div>
                  </div>

                  <div class="p-2.5 bg-light rounded-3 border mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="text-muted small">Passport:</span>
                      <span class="badge bg-white text-dark border font-monospace"><?= e($c['passport_number'] ?? 'PENDING') ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="text-muted small">Nationality:</span>
                      <strong class="text-dark small"><?= e($c['nationality']) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="text-muted small">Residence:</span>
                      <span class="small fw-semibold text-secondary"><?= e($c['current_country']) ?></span>
                    </div>
                  </div>

                  <div class="small text-muted mb-2">
                    <i class="fa-solid fa-phone me-1 text-primary"></i><?= e($c['mobile']) ?>
                  </div>
                </div>

                <div class="pt-2 border-top d-flex align-items-center justify-content-between">
                  <a href="/customers/show?id=<?= $c['id'] ?>" class="btn btn-sm btn-primary px-3 fw-semibold shadow-sm">
                    <i class="fa-solid fa-folder-open me-1"></i> View Profile
                  </a>
                  <div class="btn-group btn-group-sm">
                    <a href="/customers/edit?id=<?= $c['id'] ?>" class="btn btn-outline-secondary py-1 px-2.5" title="Edit">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                    <form action="/customers/delete" method="POST" class="d-inline" onsubmit="return confirm('Delete applicant <?= e($c['full_name']) ?>?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="customer_id" value="<?= $c['id'] ?>">
                      <button type="submit" class="btn btn-outline-danger py-1 px-2.5" title="Delete">
                        <i class="fa-solid fa-trash-can"></i>
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ================================================================= -->
    <!-- VIEW OPTION 3: COMPACT LIST VIEW -->
    <!-- ================================================================= -->
    <div id="custViewCompact" class="cust-view-container <?= $currentView === 'compact' ? '' : 'd-none' ?>">
      <div class="card card-enterprise shadow-sm border">
        <ul class="list-group list-group-flush mb-0">
          <?php foreach ($customers as $c): ?>
            <li class="list-group-item p-3 d-flex flex-wrap align-items-center justify-content-between gap-3 hover-bg-light">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar-sm rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px; font-size: 0.85rem;">
                  <?= strtoupper(substr($c['full_name'], 0, 2)) ?>
                </div>
                <div>
                  <div class="d-flex align-items-center gap-2">
                    <a href="/customers/show?id=<?= $c['id'] ?>" class="fw-bold text-dark text-decoration-none fs-6">
                      <?= e($c['full_name']) ?>
                    </a>
                    <span class="badge bg-light text-primary border font-monospace small"><?= e($c['customer_code']) ?></span>
                    <span class="badge bg-light text-dark border small"><?= e($c['nationality']) ?></span>
                  </div>
                  <div class="text-muted small mt-0.5">
                    <i class="fa-solid fa-passport me-1 text-muted"></i><?= e($c['passport_number'] ?? 'NO PASSPORT') ?> &bull; 
                    <i class="fa-solid fa-phone me-1 text-muted ms-2"></i><?= e($c['mobile']) ?> &bull;
                    <i class="fa-solid fa-location-dot me-1 text-muted ms-2"></i><?= e($c['current_country']) ?>
                  </div>
                </div>
              </div>

              <div class="d-flex align-items-center gap-2 ms-auto ms-md-0">
                <div class="text-end me-2 d-none d-md-block">
                  <div class="small fw-bold text-dark"><?= $c['total_applications'] ?> Applications</div>
                  <div class="text-muted" style="font-size: 0.72rem;"><?= $c['active_applications'] > 0 ? ($c['active_applications'] . ' in active review') : 'No active file' ?></div>
                </div>
                <div class="btn-group btn-group-sm">
                  <a href="/customers/show?id=<?= $c['id'] ?>" class="btn btn-outline-primary px-3 py-1 fw-semibold">Profile &rarr;</a>
                  <a href="/customers/edit?id=<?= $c['id'] ?>" class="btn btn-outline-secondary px-2.5 py-1" title="Edit"><i class="fa-solid fa-pen"></i></a>
                </div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

  <?php endif; ?>
</div>

<script>
function switchCustView(view) {
  // Hide all view containers
  document.querySelectorAll('.cust-view-container').forEach(el => el.classList.add('d-none'));

  // Show active view container
  const target = document.getElementById('custView' + view.charAt(0).toUpperCase() + view.slice(1));
  if (target) target.classList.remove('d-none');

  // Update button active states
  document.querySelectorAll('.cust-view-btn').forEach(btn => {
    btn.classList.remove('btn-primary', 'shadow-sm');
    btn.classList.add('btn-light', 'text-muted');
  });

  const activeBtn = document.getElementById('btnCustView' + view.charAt(0).toUpperCase() + view.slice(1));
  if (activeBtn) {
    activeBtn.classList.remove('btn-light', 'text-muted');
    activeBtn.classList.add('btn-primary', 'shadow-sm');
  }

  // Update hidden input in filter form
  const viewInput = document.getElementById('activeCustViewParam');
  if (viewInput) viewInput.value = view;

  // Persist preference in localStorage
  try { localStorage.setItem('cust_active_view', view); } catch (e) {}
}

// Restore saved view preference if URL does not specify one
document.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  if (!urlParams.has('view')) {
    const saved = localStorage.getItem('cust_active_view');
    if (saved && ['table', 'grid', 'compact'].includes(saved)) {
      switchCustView(saved);
    }
  }
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
