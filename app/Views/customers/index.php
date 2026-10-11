<?php
$pageTitle = 'Applicant & Customer Profiles — MS TRAVEL HUB';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';

$currentView = $_GET['view'] ?? 'table';
if (!in_array($currentView, ['table', 'grid', 'compact'], true)) {
    $currentView = 'table';
}

$totalProfiles = count($customers);
$totalActiveCases = (int)array_sum(array_column($customers, 'active_applications'));
$totalWithPassport = count(array_filter($customers, fn($c) => !empty($c['passport_number'])));
$totalNationalities = count($nationalities ?? []);
$searchQuery = trim($_GET['search'] ?? '');
$activeNationality = trim($_GET['nationality'] ?? '');
?>

<!-- Load Bento Design System CSS (Harmonized with Dashboard & Visa Tracking) -->
<link rel="stylesheet" href="/assets/css/dashboard-bento.css?v=<?= time() ?>">
<link rel="stylesheet" href="/assets/css/pages/customers-index.css?v=<?= time() ?>">

<div class="content-body p-0 customers-page">
  <div class="bento-dashboard-wrap">

    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm rounded-4" role="alert">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
          <span><?= e($flash['message']) ?></span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- ─── 1. BENTO HEADER & ACTIONS ────────────────────────────────────── -->
    <header class="bento-header mb-4">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
          <h1 class="bento-header-title mb-0">Applicant &amp; Customer Profiles</h1>
          <span class="badge rounded-pill text-white fw-bold px-2.5 py-1" style="background: var(--bento-primary); font-size: 0.72rem;">
            <i class="fa-solid fa-users me-1"></i> Customer CRM
          </span>
          <span class="badge bg-white text-secondary border rounded-pill px-2.5 py-1 small">
            <?= $totalProfiles ?> <?= $totalProfiles === 1 ? 'profile' : 'profiles' ?>
          </span>
        </div>
        <p class="bento-header-subtitle">
          Centralized directory of registered visa applicants, global travelers, corporate sponsors, and linked travel files.
        </p>
      </div>

      <div class="cust-header-actions">
        <!-- 3-Mode View Switcher -->
        <div class="cust-segmented" role="group" aria-label="View Mode">
          <button type="button" class="cust-view-btn <?= $currentView === 'table' ? 'active' : '' ?>" onclick="switchCustView('table')" id="btnCustViewTable" title="Table View">
            <i class="fa-solid fa-table-list"></i> <span class="d-none d-sm-inline">Table</span>
          </button>
          <button type="button" class="cust-view-btn <?= $currentView === 'grid' ? 'active' : '' ?>" onclick="switchCustView('grid')" id="btnCustViewGrid" title="Profile Cards View">
            <i class="fa-solid fa-grip"></i> <span class="d-none d-sm-inline">Cards</span>
          </button>
          <button type="button" class="cust-view-btn <?= $currentView === 'compact' ? 'active' : '' ?>" onclick="switchCustView('compact')" id="btnCustViewCompact" title="Compact List View">
            <i class="fa-solid fa-list-ul"></i> <span class="d-none d-sm-inline">Compact</span>
          </button>
        </div>

        <a href="/customers/create" class="bento-btn-primary" title="Register New Customer / Applicant">
          <i class="fa-solid fa-user-plus"></i> Register Applicant
        </a>
      </div>
    </header>

    <!-- ─── 2. EXECUTIVE KPI METRIC STRIP ────────────────────────────────── -->
    <div class="cust-kpi-grid">
      <!-- Total Profiles -->
      <div class="cust-kpi-card tone-primary">
        <div class="cust-kpi-top">
          <span class="cust-kpi-title">Registered Profiles</span>
          <div class="cust-kpi-icon"><i class="fa-solid fa-users"></i></div>
        </div>
        <div class="cust-kpi-val"><?= $totalProfiles ?></div>
        <span class="cust-kpi-sub">Total active client database</span>
      </div>

      <!-- Active Cases -->
      <div class="cust-kpi-card tone-warning">
        <div class="cust-kpi-top">
          <span class="cust-kpi-title">Active Visa Cases</span>
          <div class="cust-kpi-icon"><i class="fa-solid fa-plane-departure"></i></div>
        </div>
        <div class="cust-kpi-val"><?= $totalActiveCases ?></div>
        <span class="cust-kpi-sub">Applications in live review</span>
      </div>

      <!-- Linked Passports -->
      <div class="cust-kpi-card tone-info">
        <div class="cust-kpi-top">
          <span class="cust-kpi-title">Linked Passports</span>
          <div class="cust-kpi-icon"><i class="fa-solid fa-passport"></i></div>
        </div>
        <div class="cust-kpi-val"><?= $totalWithPassport ?></div>
        <span class="cust-kpi-sub">Primary travel documents on file</span>
      </div>

      <!-- Global Nationalities -->
      <div class="cust-kpi-card tone-success">
        <div class="cust-kpi-top">
          <span class="cust-kpi-title">Global Nationalities</span>
          <div class="cust-kpi-icon"><i class="fa-solid fa-globe"></i></div>
        </div>
        <div class="cust-kpi-val"><?= $totalNationalities ?></div>
        <span class="cust-kpi-sub">Countries of origin represented</span>
      </div>
    </div>

    <!-- ─── 3. SEARCH & FILTER TOOLBAR ───────────────────────────────────── -->
    <div class="cust-filter-card">
      <form action="/customers" method="GET" id="custFilterForm" class="row g-2 align-items-center">
        <input type="hidden" name="view" id="activeCustViewParam" value="<?= e($currentView) ?>">

        <div class="col-12 col-md-6 col-lg-5">
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Search name, code, passport, mobile, email..." value="<?= e($searchQuery) ?>">
          </div>
        </div>

        <div class="col-7 col-md-4 col-lg-4">
          <select name="nationality" class="form-select form-select-sm" onchange="document.getElementById('custFilterForm').submit()">
            <option value="">All Nationalities</option>
            <?php foreach (($nationalities ?? []) as $nat): ?>
              <option value="<?= e($nat) ?>" <?= $activeNationality === $nat ? 'selected' : '' ?>><?= e($nat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-5 col-md-2 col-lg-3 d-flex align-items-center gap-1.5 ms-auto justify-content-end">
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.15rem; font-size: 0.82rem;">
            <i class="fa-solid fa-filter me-1"></i> Filter
          </button>
          <?php if (!empty($searchQuery) || !empty($activeNationality)): ?>
            <a href="/customers" class="btn btn-sm btn-light border rounded-pill px-2.5 py-1.5" title="Clear Filters">
              <i class="fa-solid fa-rotate-left"></i>
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- ─── 4. RESULTS VIEW PRESENTATION ─────────────────────────────────── -->
    <?php if (empty($customers)): ?>
      <div class="bento-card text-center py-5">
        <i class="fa-solid fa-user-slash fs-1 text-muted opacity-50 mb-3 d-block"></i>
        <h5 class="fw-bold text-dark mb-1">No Applicant Profiles Found</h5>
        <p class="text-muted small mb-3">No customers match your active search or nationality criteria.</p>
        <div class="d-flex justify-content-center gap-2">
          <a href="/customers" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Clear Filters</a>
          <a href="/customers/create" class="bento-btn-primary" style="padding: 0.45rem 1.15rem; font-size: 0.82rem;">
            <i class="fa-solid fa-user-plus me-1"></i> Register New Applicant
          </a>
        </div>
      </div>
    <?php else: ?>

      <!-- ─── 4A. VIEW 1: TABLE VIEW (BENTO ADAPTIVE DATA TABLE) ─────────── -->
      <div id="custViewTable" class="cust-view-container <?= $currentView === 'table' ? '' : 'd-none' ?>">
        <div class="cust-table-wrap">
          <table class="cust-table">
            <thead>
              <tr>
                <th style="min-width: 140px;">Customer Code</th>
                <th style="min-width: 240px;">Applicant Name</th>
                <th style="min-width: 160px;">Primary Passport</th>
                <th style="min-width: 140px;">Nationality</th>
                <th style="min-width: 150px;">Residence</th>
                <th style="min-width: 160px;">Mobile / Phone</th>
                <th style="min-width: 140px;">Applications</th>
                <th class="text-end" style="min-width: 120px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($customers as $c): 
                $initials = strtoupper(substr(trim($c['full_name'] ?? 'CU'), 0, 2));
                $passport = $c['passport_number'] ?: '—';
              ?>
                <tr>
                  <!-- Code -->
                  <td data-label="Customer ID">
                    <a href="/customers/show?id=<?= $c['id'] ?>" class="cust-code">
                      <i class="fa-solid fa-id-badge me-1"></i><?= e($c['customer_code']) ?>
                    </a>
                  </td>

                  <!-- Applicant Hero -->
                  <td data-label="Applicant">
                    <div class="d-flex align-items-center gap-2.5">
                      <div class="cust-avatar">
                        <?= e($initials) ?>
                      </div>
                      <div class="overflow-hidden">
                        <a href="/customers/show?id=<?= $c['id'] ?>" class="fw-bold text-dark text-decoration-none text-truncate d-block" style="max-width: 180px;">
                          <?= e($c['full_name']) ?>
                        </a>
                        <div class="text-muted small text-truncate" style="font-size: 0.74rem; max-width: 180px;">
                          <?= e($c['email'] ?: 'No email registered') ?>
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- Primary Passport -->
                  <td data-label="Passport">
                    <span class="badge bg-light text-secondary border font-monospace px-2 py-1" style="font-size: 0.76rem;">
                      <i class="fa-solid fa-passport text-primary me-1"></i><?= e($passport) ?>
                    </span>
                  </td>

                  <!-- Nationality -->
                  <td data-label="Nationality">
                    <span class="cust-chip neutral"><?= e($c['nationality'] ?: 'Global') ?></span>
                  </td>

                  <!-- Residence -->
                  <td data-label="Residence">
                    <span class="small fw-semibold text-dark"><?= e($c['current_country'] ?: 'UAE') ?></span>
                  </td>

                  <!-- Contact -->
                  <td data-label="Phone">
                    <div class="small fw-semibold text-dark">
                      <i class="fa-solid fa-phone text-muted me-1 small"></i><?= e($c['mobile'] ?: '—') ?>
                    </div>
                  </td>

                  <!-- Applications Count -->
                  <td data-label="Files">
                    <div class="d-flex align-items-center gap-1 flex-wrap">
                      <span class="cust-chip primary"><?= (int)$c['total_applications'] ?> Total</span>
                      <?php if ($c['active_applications'] > 0): ?>
                        <span class="cust-chip warning"><?= (int)$c['active_applications'] ?> Active</span>
                      <?php endif; ?>
                    </div>
                  </td>

                  <!-- Actions -->
                  <td class="text-end td-actions" data-label="Actions">
                    <div class="d-flex align-items-center justify-content-end gap-1">
                      <a href="/customers/show?id=<?= $c['id'] ?>" class="cust-icon-btn primary" title="View Profile">
                        <i class="fa-solid fa-eye"></i>
                      </a>
                      <a href="/customers/edit?id=<?= $c['id'] ?>" class="cust-icon-btn" title="Edit Profile">
                        <i class="fa-solid fa-pen-to-square"></i>
                      </a>
                      <form action="/customers/delete" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete applicant <?= e($c['customer_code']) ?> (<?= e($c['full_name']) ?>) and linked records?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="customer_id" value="<?= $c['id'] ?>">
                        <button type="submit" class="cust-icon-btn danger" title="Delete Profile">
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

      <!-- ─── 4B. VIEW 2: GRID CARDS VIEW (BENTO CARDS AESTHETIC) ───────── -->
      <div id="custViewGrid" class="cust-view-container <?= $currentView === 'grid' ? '' : 'd-none' ?>">
        <div class="cust-grid">
          <?php foreach ($customers as $c): 
            $initials = strtoupper(substr(trim($c['full_name'] ?? 'CU'), 0, 2));
            $passport = $c['passport_number'] ?: 'PENDING';
          ?>
            <div class="cust-card">
              <div>
                <!-- Top Row: Customer Code & Applications Counter -->
                <div class="cust-card-top">
                  <a href="/customers/show?id=<?= $c['id'] ?>" class="cust-code">
                    <i class="fa-solid fa-id-badge me-1"></i><?= e($c['customer_code']) ?>
                  </a>
                  <div class="d-flex align-items-center gap-1">
                    <span class="cust-chip primary"><?= (int)$c['total_applications'] ?> Files</span>
                    <?php if ($c['active_applications'] > 0): ?>
                      <span class="cust-chip warning"><?= (int)$c['active_applications'] ?> Active</span>
                    <?php endif; ?>
                  </div>
                </div>

                <!-- Hero: Avatar & Name -->
                <div class="cust-card-hero">
                  <div class="cust-avatar">
                    <?= e($initials) ?>
                  </div>
                  <div class="overflow-hidden" style="min-width: 0;">
                    <h6 class="fw-bold text-dark mb-0.5 text-truncate">
                      <a href="/customers/show?id=<?= $c['id'] ?>" class="text-dark text-decoration-none"><?= e($c['full_name']) ?></a>
                    </h6>
                    <div class="text-muted small text-truncate" style="font-size: 0.74rem;">
                      <?= e($c['email'] ?: 'No email specified') ?>
                    </div>
                  </div>
                </div>

                <!-- 2x2 Metadata Grid -->
                <div class="cust-card-meta-grid">
                  <div class="cust-card-meta-cell">
                    <div class="cust-card-meta-label">Primary Passport</div>
                    <div class="cust-card-meta-val font-monospace">
                      <i class="fa-solid fa-passport text-primary me-1"></i><?= e($passport) ?>
                    </div>
                  </div>

                  <div class="cust-card-meta-cell">
                    <div class="cust-card-meta-label">Nationality</div>
                    <div class="cust-card-meta-val">
                      <?= e($c['nationality'] ?: 'Global') ?>
                    </div>
                  </div>

                  <div class="cust-card-meta-cell">
                    <div class="cust-card-meta-label">Residence Country</div>
                    <div class="cust-card-meta-val">
                      <?= e($c['current_country'] ?: 'UAE') ?>
                    </div>
                  </div>

                  <div class="cust-card-meta-cell">
                    <div class="cust-card-meta-label">Mobile Phone</div>
                    <div class="cust-card-meta-val">
                      <i class="fa-solid fa-phone text-muted me-1"></i><?= e($c['mobile'] ?: '—') ?>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action Footer -->
              <div class="cust-card-foot">
                <a href="/customers/show?id=<?= $c['id'] ?>" class="bento-btn-primary" style="padding: 0.4rem 1.15rem; font-size: 0.78rem;">
                  <i class="fa-solid fa-folder-open me-1"></i> View Profile
                </a>
                <div class="d-flex align-items-center gap-1.5">
                  <a href="/customers/edit?id=<?= $c['id'] ?>" class="cust-icon-btn" title="Edit Profile">
                    <i class="fa-solid fa-pen"></i>
                  </a>
                  <form action="/customers/delete" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete applicant <?= e($c['full_name']) ?>?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="customer_id" value="<?= $c['id'] ?>">
                    <button type="submit" class="cust-icon-btn danger" title="Delete Profile">
                      <i class="fa-solid fa-trash-can"></i>
                    </button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- ─── 4C. VIEW 3: COMPACT LIST VIEW (HIGH DENSITY ROWS) ─────────── -->
      <div id="custViewCompact" class="cust-view-container <?= $currentView === 'compact' ? '' : 'd-none' ?>">
        <div class="cust-compact-list">
          <?php foreach ($customers as $c): 
            $initials = strtoupper(substr(trim($c['full_name'] ?? 'CU'), 0, 2));
          ?>
            <div class="cust-compact-row">
              <!-- Left: Avatar + Code + Name -->
              <div class="d-flex align-items-center gap-2.5">
                <div class="cust-avatar sm">
                  <?= e($initials) ?>
                </div>
                <div class="overflow-hidden">
                  <div class="d-flex align-items-center gap-1.5 flex-wrap">
                    <a href="/customers/show?id=<?= $c['id'] ?>" class="fw-bold brand-font text-dark text-decoration-none" style="font-size: 0.88rem;">
                      <?= e($c['full_name']) ?>
                    </a>
                    <span class="cust-code" style="font-size: 0.7rem; padding: 0.15rem 0.5rem;"><?= e($c['customer_code']) ?></span>
                  </div>
                  <div class="text-muted small text-truncate" style="font-size: 0.74rem;">
                    <?= e($c['email'] ?: 'No email registered') ?>
                  </div>
                </div>
              </div>

              <!-- Passport & Country Info -->
              <div class="cc-meta text-truncate">
                <div class="small fw-semibold text-dark text-truncate">
                  <i class="fa-solid fa-passport text-primary me-1"></i><?= e($c['passport_number'] ?? 'NO PASSPORT') ?>
                  &bull; <span class="text-secondary"><?= e($c['nationality'] ?: 'Global') ?></span>
                </div>
                <div class="text-muted small" style="font-size: 0.72rem;">
                  <i class="fa-solid fa-phone me-1"></i><?= e($c['mobile'] ?: '—') ?> &bull; Resident: <?= e($c['current_country'] ?: 'UAE') ?>
                </div>
              </div>

              <!-- Active Status Badges -->
              <div class="d-flex align-items-center gap-1 flex-wrap">
                <span class="cust-chip primary"><?= (int)$c['total_applications'] ?> Total</span>
                <?php if ($c['active_applications'] > 0): ?>
                  <span class="cust-chip warning"><?= (int)$c['active_applications'] ?> Active</span>
                <?php endif; ?>
              </div>

              <!-- Actions -->
              <div class="d-flex align-items-center gap-1.5 flex-shrink-0">
                <a href="/customers/show?id=<?= $c['id'] ?>" class="cust-icon-btn primary" title="View Profile">
                  <i class="fa-solid fa-eye"></i>
                </a>
                <a href="/customers/edit?id=<?= $c['id'] ?>" class="cust-icon-btn" title="Edit Profile">
                  <i class="fa-solid fa-pen"></i>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    <?php endif; ?>

  </div>
</div>

<script>
function switchCustView(view) {
  document.querySelectorAll('.cust-view-container').forEach(el => el.classList.add('d-none'));
  const target = document.getElementById('custView' + view.charAt(0).toUpperCase() + view.slice(1));
  if (target) target.classList.remove('d-none');

  document.querySelectorAll('.cust-view-btn').forEach(btn => btn.classList.remove('active'));
  const activeBtn = document.getElementById('btnCustView' + view.charAt(0).toUpperCase() + view.slice(1));
  if (activeBtn) activeBtn.classList.add('active');

  const viewInput = document.getElementById('activeCustViewParam');
  if (viewInput) viewInput.value = view;

  try { localStorage.setItem('cust_active_view', view); } catch (e) {}
}

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
