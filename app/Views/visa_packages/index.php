<?php
$pageTitle = 'Visa Services & Packages — MS TRAVEL HUB';
$flash = get_flash();
$activeTab = $activeTab ?? 'packages';
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
?>
<link rel="stylesheet" href="/assets/css/dashboard-bento.css?v=2.5">

<div class="content-body">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show mb-4" role="alert">
      <?= e($flash['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <h3 class="fw-bold brand-font text-dark mb-0">Visa Services &amp; Packages</h3>
      <p class="text-muted small mb-0">Manage global visa packages, destination country rules, categories, supplier costs, service fees, currencies &amp; pricing.</p>
    </div>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-outline-primary px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
        <i class="fa-solid fa-layer-group me-1"></i> Add Category
      </button>
      <button type="button" class="btn btn-primary px-3 shadow fw-semibold" id="btnHeaderAddPackage" data-bs-toggle="modal" data-bs-target="#createPackageModal" onclick="openCreatePackageModal()">
        <i class="fa-solid fa-plus me-1"></i> Add Visa Package
      </button>
    </div>
  </div>

  <!-- KPI Metrics Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
      <div class="stat-card stat-card-blue p-3">
        <div class="stat-icon-wrapper fs-5 p-2" style="background: rgba(225, 29, 72, 0.12) !important; color: #E11D48 !important;">
          <i class="fa-solid fa-boxes-stacked"></i>
        </div>
        <div>
          <div class="stat-title" style="font-size: 0.72rem;">Total Packages</div>
          <div class="stat-value text-primary fs-5"><?= $totalPackages ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="stat-card stat-card-success p-3">
        <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success fs-5 p-2">
          <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
          <div class="stat-title" style="font-size: 0.72rem;">Active Packages</div>
          <div class="stat-value text-success fs-5"><?= $activePackages ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="stat-card stat-card-cyan p-3">
        <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info fs-5 p-2">
          <i class="fa-solid fa-earth-americas"></i>
        </div>
        <div>
          <div class="stat-title" style="font-size: 0.72rem;">Countries Served</div>
          <div class="stat-value text-info fs-5"><?= $totalCountries ?> Countries</div>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="stat-card stat-card-purple p-3">
        <div class="stat-icon-wrapper bg-purple bg-opacity-10 text-purple fs-5 p-2">
          <i class="fa-solid fa-list-check"></i>
        </div>
        <div>
          <div class="stat-title" style="font-size: 0.72rem;">Visa Categories</div>
          <div class="stat-value text-purple fs-5"><?= $totalCategories ?> Categories</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Navigation Tabs -->
  <ul class="nav nav-tabs mb-4" id="visaPackageTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <a class="nav-link <?= $activeTab === 'packages' ? 'active fw-bold' : '' ?>" href="/visa-packages?tab=packages">
        <i class="fa-solid fa-passport me-1.5 text-primary"></i> Visa Packages (<?= $totalPackages ?>)
      </a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link <?= $activeTab === 'categories' ? 'active fw-bold' : '' ?>" href="/visa-packages?tab=categories">
        <i class="fa-solid fa-layer-group me-1.5 text-success"></i> Categories (<?= $totalCategories ?>)
      </a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link <?= $activeTab === 'types' ? 'active fw-bold' : '' ?>" href="/visa-packages?tab=types">
        <i class="fa-solid fa-file-lines me-1.5 text-info"></i> Visa Types (<?= count($visaTypes) ?>)
      </a>
    </li>
  </ul>

  <?php if ($activeTab === 'packages'): ?>
    <!-- Filter Card -->
    <div class="card card-enterprise mb-4 shadow-sm border">
      <div class="card-body p-3">
        <form action="/visa-packages" method="GET" class="row g-2 align-items-center">
          <input type="hidden" name="tab" value="packages">
          <div class="col-md-3">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
              <input type="text" name="search" class="form-control" placeholder="Search package name, supplier..." value="<?= e($_GET['search'] ?? '') ?>">
            </div>
          </div>
          <div class="col-md-3">
            <select name="country_id" class="form-select form-select-sm">
              <option value="">-- All Destination Countries --</option>
              <?php foreach ($countries as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ((int)($_GET['country_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                  <?= $c['flag_emoji'] ?> <?= e($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <select name="category_id" class="form-select form-select-sm">
              <option value="">-- All Categories --</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= ((int)($_GET['category_id'] ?? 0) === (int)$cat['id']) ? 'selected' : '' ?>>
                  <?= e($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <select name="supplier_id" class="form-select form-select-sm">
              <option value="">-- All Suppliers --</option>
              <?php foreach ($suppliers as $sup): ?>
                <option value="<?= $sup['id'] ?>" <?= ((int)($_GET['supplier_id'] ?? 0) === (int)$sup['id']) ? 'selected' : '' ?>>
                  <?= e($sup['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
            <a href="/visa-packages" class="btn btn-light border btn-sm" title="Clear Filters"><i class="fa-solid fa-rotate-left"></i></a>
          </div>
        </form>
      </div>
    </div>

    <!-- View Switcher & Counter -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      <div class="text-muted small">
        Showing <span class="fw-bold text-dark"><?= count($packages) ?></span> visa packages
      </div>
      <div class="d-flex align-items-center gap-2">
        <div class="btn-group btn-group-sm p-1 bg-white rounded-pill border shadow-xs" role="group" aria-label="View Mode">
          <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold active" id="btnViewCards" onclick="setVisaViewMode('cards')">
            <i class="fa-solid fa-grip me-1.5"></i> Cards View
          </button>
          <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold text-muted" id="btnViewTable" onclick="setVisaViewMode('table')">
            <i class="fa-solid fa-table-list me-1.5"></i> Table View
          </button>
        </div>
      </div>
    </div>

    <!-- View Container 1: Cards View (Default) -->
    <div id="packagesCardsView" class="mb-4">
      <?php if (empty($packages)): ?>
        <div class="card card-enterprise text-center py-5 text-muted border shadow-sm">
          <i class="fa-solid fa-folder-open fs-3 d-block mb-2 text-secondary opacity-50"></i>
          No visa packages found matching your criteria.
        </div>
      <?php else: ?>
        <div class="bento-travel-grid">
          <?php foreach ($packages as $pkg): 
            $cName = strtolower($pkg['country_name'] ?? '');
            $pName = strtolower($pkg['name'] ?? '');
            $cover = $pkg['image_url'] ?? '';
            if (empty($cover)) {
                if (str_contains($pName, 'golden') || str_contains($pName, 'investor')) {
                    $cover = '/assets/images/destinations/uae-golden.jpg';
                } elseif (str_contains($cName, 'emirates') || str_contains($cName, 'uae') || str_contains($pName, 'uae') || str_contains($pName, 'dubai')) {
                    $cover = '/assets/images/destinations/uae-tourist.jpg';
                } elseif (str_contains($cName, 'kingdom') || str_contains($cName, 'uk') || str_contains($pName, 'uk') || str_contains($pName, 'london')) {
                    $cover = '/assets/images/destinations/uk-visitor.jpg';
                } elseif (str_contains($cName, 'france') || str_contains($cName, 'schengen') || str_contains($pName, 'schengen')) {
                    $cover = '/assets/images/destinations/france-schengen.jpg';
                } elseif (str_contains($cName, 'states') || str_contains($cName, 'us') || str_contains($pName, 'us') || str_contains($pName, 'usa')) {
                    $cover = '/assets/images/destinations/us-visitor.jpg';
                } elseif (str_contains($cName, 'saudi') || str_contains($pName, 'umrah')) {
                    $cover = '/assets/images/destinations/saudi-umrah.jpg';
                } elseif (str_contains($cName, 'canada')) {
                    $cover = '/assets/images/destinations/canada-visitor.jpg';
                } else {
                    $cover = '/assets/images/destinations/default-travel.jpg';
                }
            }
            $curr = $pkg['currency'] ?? 'USD';
          ?>
            <div class="bento-travel-card">
              <!-- Top Photo Container -->
              <div class="travel-card-photo-wrap">
                <img src="<?= e($cover) ?>" alt="<?= e($pkg['name']) ?>" class="travel-card-photo" loading="lazy" onerror="this.onerror=null;this.src='/assets/images/destinations/default-travel.jpg'">
                
                <div class="travel-photo-badge-left">
                  <span class="fs-6"><?= e($pkg['flag_emoji'] ?? '🌐') ?></span>
                  <span><?= e($pkg['country_name'] ?? 'Global') ?></span>
                </div>

                <div class="travel-photo-badge-right">
                  <?php if (!empty($pkg['is_active'])): ?>
                    <span class="badge bg-white text-success border small fw-bold" style="border-radius: var(--bento-radius-pill); font-size: 0.70rem; padding: 4px 8px;">
                      <i class="fa-solid fa-circle text-success me-1" style="font-size: 0.5rem;"></i>Active
                    </span>
                  <?php else: ?>
                    <span class="badge bg-white text-secondary border small fw-bold" style="border-radius: var(--bento-radius-pill); font-size: 0.70rem; padding: 4px 8px;">
                      <i class="fa-solid fa-circle text-muted me-1" style="font-size: 0.5rem;"></i>Inactive
                    </span>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Card Body -->
              <div class="travel-card-body">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <span class="badge bg-light text-dark border small" style="font-size: 0.72rem;"><?= e($pkg['category_name']) ?></span>
                  <?php if (!empty($pkg['supplier_company_name']) || !empty($pkg['supplier_name'])): ?>
                    <span class="text-muted small text-truncate" style="font-size: 0.70rem; max-width: 140px;" title="<?= e($pkg['supplier_company_name'] ?: $pkg['supplier_name']) ?>">
                      <i class="fa-solid fa-building me-1"></i><?= e($pkg['supplier_company_name'] ?: $pkg['supplier_name']) ?>
                    </span>
                  <?php endif; ?>
                </div>

                <h4 class="travel-card-title"><?= e($pkg['name']) ?></h4>
                
                <div class="travel-card-location">
                  <i class="fa-solid fa-location-dot"></i>
                  <span><?= e($pkg['country_name'] ?? 'International') ?><?= !empty($pkg['entry_type']) ? ' &bull; ' . e($pkg['entry_type']) : '' ?></span>
                </div>

                <?php $pkgDesc = trim($pkg['description'] ?? $pkg['notes'] ?? ''); ?>
                <?php if (!empty($pkgDesc)): ?>
                  <div class="travel-card-desc-head">Description</div>
                  <p class="travel-card-desc-text">
                    <?= e($pkgDesc) ?>
                  </p>
                <?php endif; ?>

                <!-- 3 Real Stats Row (100% Real Data, No Fake Ratings) -->
                <div class="travel-card-stats-row">
                  <div class="travel-card-stat-col">
                    <span class="travel-card-stat-label">Est. Days</span>
                    <span class="travel-card-stat-val">
                      <?php 
                        $estDays = (int)($pkg['estimated_days'] ?? 0);
                        echo $estDays > 0 ? ($estDays . ($estDays === 1 ? ' Day' : ' Days')) : 'Standard';
                      ?>
                    </span>
                  </div>
                  <div class="travel-card-stat-col" style="border-left: 1px solid #E2E8F0; border-right: 1px solid #E2E8F0;">
                    <span class="travel-card-stat-label">Validity</span>
                    <span class="travel-card-stat-val">
                      <?= e(!empty($pkg['validity']) ? $pkg['validity'] : (!empty($pkg['duration']) ? $pkg['duration'] : 'Standard')) ?>
                    </span>
                  </div>
                  <div class="travel-card-stat-col">
                    <span class="travel-card-stat-label">Processing</span>
                    <span class="travel-card-stat-val"><?= e(!empty($pkg['processing_type']) ? $pkg['processing_type'] : 'Normal') ?></span>
                  </div>
                </div>

                <!-- Commercial Pricing -->
                <div class="travel-card-bottom mb-3">
                  <div>
                    <div class="travel-card-price-label">Selling Price</div>
                    <div class="travel-card-price-val"><?= e($curr) ?> <?= number_format((float)($pkg['selling_price'] ?? 0), 2) ?></div>
                  </div>
                  <a href="/applications/create?service_id=<?= (int)$pkg['id'] ?>" class="travel-card-plane-btn" title="Start Application for <?= e($pkg['name']) ?>" aria-label="Book or Apply Now">
                    <i class="fa-solid fa-plane"></i>
                  </a>
                </div>

                <!-- Admin Action Toolbar on Card -->
                <div class="d-flex align-items-center justify-content-between pt-2 border-top gap-1">
                  <div class="text-muted small" style="font-size: 0.72rem;">
                    Cost: <?= e($curr) ?><?= number_format((float)$pkg['supplier_cost'], 0) ?>
                  </div>
                  <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-primary py-1 px-2.5 rounded-pill fw-semibold" style="font-size: 0.75rem;" onclick="openEditPackageModal(<?= htmlspecialchars(json_encode($pkg), ENT_QUOTES, 'UTF-8') ?>)">
                      <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                    </button>
                    <button type="button" class="btn btn-outline-info py-1 px-2 rounded-pill ms-1" title="Price History" onclick="viewPriceHistory(<?= $pkg['id'] ?>, '<?= e(addslashes($pkg['name'])) ?>')">
                      <i class="fa-solid fa-clock-rotate-left"></i>
                    </button>
                    <form action="/visa-packages/toggle" method="POST" class="d-inline ms-1">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= $pkg['id'] ?>">
                      <button type="submit" class="btn btn-outline-secondary py-1 px-2 rounded-pill" title="Toggle Active/Inactive">
                        <i class="fa-solid fa-power-off"></i>
                      </button>
                    </form>
                    <form action="/visa-packages/delete" method="POST" class="d-inline ms-1" onsubmit="return confirm('Are you sure you want to delete/deactivate this Visa Package?')">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= $pkg['id'] ?>">
                      <button type="submit" class="btn btn-outline-danger py-1 px-2 rounded-pill" title="Delete">
                        <i class="fa-solid fa-trash-can"></i>
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- View Container 2: Table View (Optional Toggle) -->
    <div id="packagesTableView" class="card card-enterprise shadow-sm border mb-4" style="display: none;">
      <div class="table-responsive">
        <table class="table table-enterprise table-hover mb-0 align-middle">
          <thead>
            <tr>
              <th>Package Name</th>
              <th>Destination</th>
              <th>Category</th>
              <th>Supplier</th>
              <th>Duration &amp; Entry</th>
              <th>Cost Breakdown</th>
              <th>Selling Price</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($packages)): ?>
              <tr>
                <td colspan="9" class="text-center py-5 text-muted">
                  <i class="fa-solid fa-folder-open fs-3 d-block mb-2 text-secondary opacity-50"></i>
                  No visa packages found matching your criteria.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($packages as $pkg): ?>
                <?php $curr = $pkg['currency'] ?? 'USD'; ?>
                <tr>
                  <td>
                    <div class="fw-bold text-dark"><?= e($pkg['name']) ?></div>
                    <div class="text-muted small">
                      Est. <?= (int)$pkg['estimated_days'] ?> Days &bull; Validity: <?= e($pkg['validity'] ?: 'N/A') ?>
                    </div>
                  </td>
                  <td>
                    <span class="fw-semibold"><?= $pkg['flag_emoji'] ?? '🌐' ?> <?= e($pkg['country_name']) ?></span>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border"><?= e($pkg['category_name']) ?></span>
                  </td>
                  <td>
                    <?php if (!empty($pkg['supplier_company_name']) || !empty($pkg['supplier_name'])): ?>
                      <span class="badge bg-primary-subtle text-primary border">
                        <i class="fa-solid fa-building me-1"></i><?= e($pkg['supplier_company_name'] ?: $pkg['supplier_name']) ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted small">Direct / In-house</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="small fw-semibold"><?= e($pkg['duration']) ?> (<?= e($pkg['entry_type'] ?: 'Single Entry') ?>)</div>
                    <div class="text-muted small"><?= e($pkg['processing_type'] ?: 'Normal') ?></div>
                  </td>
                  <td>
                    <div class="text-muted small">Cost: <?= e($curr) ?> <?= number_format((float)$pkg['supplier_cost'], 2) ?></div>
                    <div class="text-muted small">Fee: <?= e($curr) ?> <?= number_format((float)$pkg['service_fee'], 2) ?> (VAT <?= (float)$pkg['tax_rate'] ?>%)</div>
                  </td>
                  <td>
                    <span class="fw-bold text-primary fs-6"><?= e($curr) ?> <?= number_format((float)$pkg['selling_price'], 2) ?></span>
                  </td>
                  <td>
                    <?php if (!empty($pkg['is_active'])): ?>
                      <span class="badge bg-success-subtle text-success border px-2 py-1">Active</span>
                    <?php else: ?>
                      <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Inactive</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-outline-info py-1 px-2" title="View Price History" onclick="viewPriceHistory(<?= $pkg['id'] ?>, '<?= e(addslashes($pkg['name'])) ?>')">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                      </button>
                      <button type="button" class="btn btn-outline-primary py-1 px-2" title="Edit Package" onclick="openEditPackageModal(<?= htmlspecialchars(json_encode($pkg), ENT_QUOTES, 'UTF-8') ?>)">
                        <i class="fa-solid fa-pen-to-square"></i>
                      </button>
                      <form action="/visa-packages/toggle" method="POST" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $pkg['id'] ?>">
                        <button type="submit" class="btn btn-outline-secondary py-1 px-2" title="Toggle Active/Inactive">
                          <i class="fa-solid fa-power-off"></i>
                        </button>
                      </form>
                      <form action="/visa-packages/delete" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete/deactivate this Visa Package?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $pkg['id'] ?>">
                        <button type="submit" class="btn btn-outline-danger py-1 px-2" title="Delete / Deactivate">
                          <i class="fa-solid fa-trash-can"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php elseif ($activeTab === 'categories'): ?>
    <!-- Visa Categories Header & Controls -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
          <i class="fa-solid fa-layer-group text-primary"></i> Visa Categories Portfolio
        </h5>
        <p class="text-muted small mb-0">Operational groupings and service tiers for consular classification.</p>
      </div>
      <div class="d-flex align-items-center gap-2">
        <div class="btn-group btn-group-sm p-1 bg-white rounded-pill border shadow-xs" role="group">
          <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold active" id="btnCatCards" onclick="setSubTabMode('cat', 'cards')">
            <i class="fa-solid fa-grip me-1.5"></i> Cards
          </button>
          <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold text-muted" id="btnCatTable" onclick="setSubTabMode('cat', 'table')">
            <i class="fa-solid fa-table-list me-1.5"></i> Table
          </button>
        </div>
        <button type="button" class="bento-pill-btn-sm" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
          <i class="fa-solid fa-plus me-1"></i> Add Category
        </button>
      </div>
    </div>

    <!-- Categories Cards Grid (Default) -->
    <div id="catCardsView" class="mb-4">
      <div class="row g-3">
        <?php foreach ($categories as $cat): ?>
          <div class="col-12 col-sm-6 col-lg-3">
            <div class="bento-category-card">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="bento-cat-icon-wrap">
                  <i class="fa-solid <?= e($cat['icon'] ?: 'fa-passport') ?>"></i>
                </div>
                <span class="badge <?= !empty($cat['is_active']) ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border' ?> rounded-pill small px-2.5 py-1">
                  <i class="fa-solid fa-circle me-1" style="font-size: 0.5rem;"></i><?= !empty($cat['is_active']) ? 'Active' : 'Inactive' ?>
                </span>
              </div>
              <h5 class="bento-cat-title"><?= e($cat['name']) ?></h5>
              <div>
                <span class="bento-cat-slug">#<?= e($cat['slug']) ?></span>
              </div>
              <p class="bento-cat-desc"><?= e($cat['description'] ?: 'Official consular category classification.') ?></p>
              <div class="bento-cat-footer">
                <span class="badge bg-light text-dark border small fw-semibold">
                  <i class="fa-solid fa-cube text-primary me-1"></i><?= (int)($cat['packages_count'] ?? 0) ?> Packages
                </span>
                <a href="/visa-packages?category_id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5" title="Filter Packages">
                  View <i class="fa-solid fa-arrow-right ms-1 small"></i>
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Categories Table View -->
    <div id="catTableView" class="card card-enterprise shadow-sm border mb-4" style="display: none;">
      <div class="table-responsive">
        <table class="table table-enterprise table-hover mb-0 align-middle">
          <thead>
            <tr>
              <th>Category Name</th>
              <th>Slug / Code</th>
              <th>Description</th>
              <th>Packages Linked</th>
              <th>Status</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $cat): ?>
              <tr>
                <td>
                  <div class="fw-bold text-dark"><i class="fa-solid <?= e($cat['icon'] ?: 'fa-passport') ?> text-primary me-2"></i><?= e($cat['name']) ?></div>
                </td>
                <td><span class="badge bg-light text-dark border font-monospace">#<?= e($cat['slug']) ?></span></td>
                <td><span class="text-muted small"><?= e($cat['description'] ?: '—') ?></span></td>
                <td><span class="badge bg-primary-subtle text-primary fw-bold"><?= (int)($cat['packages_count'] ?? 0) ?> Packages</span></td>
                <td>
                  <?php if (!empty($cat['is_active'])): ?>
                    <span class="badge bg-success-subtle text-success border">Active</span>
                  <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary border">Inactive</span>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <a href="/visa-packages?category_id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5">
                    Filter Packages
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php elseif ($activeTab === 'types'): ?>
    <!-- Visa Types Header & Controls -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
          <i class="fa-solid fa-file-lines text-primary"></i> Visa Types &amp; Classifications
        </h5>
        <p class="text-muted small mb-0">Government visa classification standards and entry permit tiers.</p>
      </div>
      <div class="d-flex align-items-center gap-2">
        <div class="btn-group btn-group-sm p-1 bg-white rounded-pill border shadow-xs" role="group">
          <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold active" id="btnTypeCards" onclick="setSubTabMode('type', 'cards')">
            <i class="fa-solid fa-grip me-1.5"></i> Cards
          </button>
          <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold text-muted" id="btnTypeTable" onclick="setSubTabMode('type', 'table')">
            <i class="fa-solid fa-table-list me-1.5"></i> Table
          </button>
        </div>
        <button type="button" class="bento-pill-btn-sm" data-bs-toggle="modal" data-bs-target="#createTypeModal">
          <i class="fa-solid fa-plus me-1"></i> Add Visa Type
        </button>
      </div>
    </div>

    <!-- Visa Types Cards Grid (Default) -->
    <div id="typeCardsView" class="mb-4">
      <div class="row g-3">
        <?php foreach ($visaTypes as $vt): ?>
          <div class="col-12 col-sm-6 col-lg-3">
            <div class="bento-type-card">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="bento-cat-icon-wrap">
                  <i class="fa-solid <?= e($vt['icon'] ?: 'fa-file') ?>"></i>
                </div>
                <span class="badge <?= !empty($vt['is_active']) ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border' ?> rounded-pill small px-2.5 py-1">
                  <i class="fa-solid fa-circle me-1" style="font-size: 0.5rem;"></i><?= !empty($vt['is_active']) ? 'Active' : 'Inactive' ?>
                </span>
              </div>
              <h5 class="bento-cat-title"><?= e($vt['name']) ?></h5>
              <div>
                <span class="bento-cat-slug">#<?= e($vt['slug']) ?></span>
              </div>
              <p class="bento-cat-desc"><?= e($vt['description'] ?: 'Government classification standard.') ?></p>
              <div class="bento-cat-footer">
                <span class="text-muted small"><i class="fa-solid fa-shield-halved text-primary me-1"></i>Official Tier</span>
                <span class="badge bg-light text-muted border">System</span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Visa Types Table View -->
    <div id="typeTableView" class="card card-enterprise shadow-sm border mb-4" style="display: none;">
      <div class="table-responsive">
        <table class="table table-enterprise table-hover mb-0 align-middle">
          <thead>
            <tr>
              <th>Type Name</th>
              <th>Slug / Code</th>
              <th>Description</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($visaTypes as $vt): ?>
              <tr>
                <td>
                  <div class="fw-bold text-dark"><i class="fa-solid <?= e($vt['icon'] ?: 'fa-file') ?> text-primary me-2"></i><?= e($vt['name']) ?></div>
                </td>
                <td><span class="badge bg-light text-dark border font-monospace">#<?= e($vt['slug']) ?></span></td>
                <td><span class="text-muted small"><?= e($vt['description'] ?: '—') ?></span></td>
                <td>
                  <?php if (!empty($vt['is_active'])): ?>
                    <span class="badge bg-success-subtle text-success border">Active</span>
                  <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary border">Inactive</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Modal: + Add Visa Package (Image 1 2-Column Split Layout) -->
<div class="modal fade" id="createPackageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-split-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden;">
      <div class="modal-header bg-white border-bottom py-3 px-4">
        <div class="d-flex align-items-center gap-2">
          <div style="width: 38px; height: 38px; border-radius: 10px; background: var(--bento-primary-soft); color: var(--bento-primary); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
            <i class="fa-solid fa-circle-plus"></i>
          </div>
          <div>
            <h5 class="modal-title fw-bold text-dark fs-6 mb-0">Add Manual Visa Package</h5>
            <small class="text-muted">Define consular specifications, pricing margins, and review live card preview.</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form action="/visa-packages/store" method="POST" enctype="multipart/form-data" id="formCreatePackage">
        <?= csrf_field() ?>
        <input type="hidden" name="return_to" id="createPkgReturnTo" value="">
        <div class="modal-body p-4" style="background: #FAFAFA;">
          <div class="row g-4">
            
            <!-- LEFT COLUMN: FORM DETAILS (IMAGE 1 SPEC) -->
            <div class="col-12 col-lg-7">
              
              <!-- 1. Destination & Core Identity -->
              <div class="bento-form-panel">
                <h6 class="bento-form-panel-title">
                  <i class="fa-solid fa-globe"></i> 1. Destination &amp; Core Identity
                </h6>
                <div class="row g-3 mb-3">
                  <div class="col-md-6 bento-input-group-clean">
                    <label>Destination Country <span class="text-danger">*</span></label>
                    <select name="country_id" id="createPkgCountry" class="form-select bento-input-control" required onchange="updateCreateLivePreview()">
                      <option value="" data-flag="🌐" data-name="Select Destination">-- Choose Country --</option>
                      <?php foreach ($countries as $c): ?>
                        <option value="<?= $c['id'] ?>" data-flag="<?= $c['flag_emoji'] ?>" data-name="<?= e($c['name']) ?>">
                          <?= $c['flag_emoji'] ?> <?= e($c['name']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6 bento-input-group-clean">
                    <label>Visa Category <span class="text-danger">*</span></label>
                    <select name="category_id" id="createPkgCategory" class="form-select bento-input-control" required onchange="updateCreateLivePreview()">
                      <option value="" data-name="General">-- Choose Category --</option>
                      <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" data-name="<?= e($cat['name']) ?>"><?= e($cat['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>

                <div class="mb-3 bento-input-group-clean">
                  <label>Package / Service Name <span class="text-danger">*</span></label>
                  <input type="text" name="name" id="createPkgName" class="form-control bento-input-control" placeholder="e.g. UAE 60-Day Tourist Visa (Single Entry)" required oninput="updateCreateLivePreview()">
                </div>

                <div class="mb-0 bento-input-group-clean">
                  <label>Traveler Description (Displayed on Cards) <span class="text-danger">*</span></label>
                  <textarea name="description" id="createPkgDescription" class="form-control bento-input-control" rows="2" placeholder="Official consular visa pathway with fast-track document pre-check..." required oninput="updateCreateLivePreview()">Official consular visa pathway with fast-track document pre-check, verified embassy appointment booking, and SLA turnaround guarantee.</textarea>
                  <div class="form-text small text-muted">Summary displayed on the Travel Card and client proposals.</div>
                </div>
              </div>

              <!-- 2. Travel Card Cover Photo -->
              <div class="bento-form-panel">
                <h6 class="bento-form-panel-title">
                  <i class="fa-solid fa-image"></i> 2. Travel Card Cover Photo
                </h6>
                <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
                  <div style="width: 130px; height: 85px; border-radius: 14px; overflow: hidden; background: #0F172A; flex-shrink: 0; border: 1.5px solid #E2E8F0;">
                    <img id="createPkgPreviewImg" src="/assets/images/destinations/default-travel.jpg" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                  </div>
                  <div class="flex-grow-1">
                    <div class="mb-2 bento-input-group-clean">
                      <label class="mb-1">Upload Photo File</label>
                      <input type="file" name="cover_image" id="createPkgFileInput" class="form-control form-control-sm bento-input-control" accept="image/*" onchange="previewPkgImageLive(this, 'create')">
                    </div>
                    <div class="bento-input-group-clean">
                      <label class="mb-1">Or Photo URL <small class="text-muted fw-normal">(Optional)</small></label>
                      <input type="text" name="image_url" id="createPkgImageUrlInput" class="form-control form-control-sm bento-input-control" placeholder="e.g. /assets/images/destinations/uae-tourist.jpg" oninput="previewPkgUrlLive(this.value, 'create')">
                    </div>
                  </div>
                </div>
              </div>

              <!-- 3. Turnaround SLA & Consular Rules -->
              <div class="bento-form-panel">
                <h6 class="bento-form-panel-title">
                  <i class="fa-solid fa-clock"></i> 3. Turnaround SLA &amp; Travel Rules
                </h6>
                <div class="row g-3 mb-3">
                  <div class="col-md-3 bento-input-group-clean">
                    <label>Duration <span class="text-danger">*</span></label>
                    <input type="text" name="duration" id="createPkgDuration" class="form-control bento-input-control" placeholder="e.g. 60 Days" value="60 Days" required oninput="updateCreateLivePreview()">
                  </div>
                  <div class="col-md-3 bento-input-group-clean">
                    <label>Max Stay</label>
                    <input type="text" name="max_stay" id="createPkgMaxStay" class="form-control bento-input-control" placeholder="e.g. 60 Days" value="60 Days" oninput="updateCreateLivePreview()">
                  </div>
                  <div class="col-md-3 bento-input-group-clean">
                    <label>Validity</label>
                    <input type="text" name="validity" id="createPkgValidity" class="form-control bento-input-control" placeholder="e.g. 60 Days" value="60 Days from issue" oninput="updateCreateLivePreview()">
                  </div>
                  <div class="col-md-3 bento-input-group-clean">
                    <label>Est. SLA (Days) <span class="text-danger">*</span></label>
                    <input type="number" name="estimated_days" id="createPkgEstDays" class="form-control bento-input-control" min="1" value="3" required oninput="updateCreateLivePreview()">
                  </div>
                </div>

                <div class="row g-3">
                  <div class="col-md-6 bento-input-group-clean">
                    <label>Entry Type</label>
                    <select name="entry_type" id="createPkgEntryType" class="form-select bento-input-control" onchange="updateCreateLivePreview()">
                      <option value="Single Entry">Single Entry</option>
                      <option value="Multiple Entry">Multiple Entry</option>
                    </select>
                  </div>
                  <div class="col-md-6 bento-input-group-clean">
                    <label>Processing Tier</label>
                    <select name="processing_type" id="createPkgProcessingType" class="form-select bento-input-control" onchange="updateCreateLivePreview()">
                      <option value="Normal">Normal</option>
                      <option value="Express">Express</option>
                      <option value="Urgent">Urgent</option>
                      <option value="Super Express">Super Express</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- 4. Commercial Pricing & Margin Breakdown -->
              <div class="bento-form-panel">
                <h6 class="bento-form-panel-title">
                  <i class="fa-solid fa-calculator"></i> 4. Commercial Pricing &amp; Margin Breakdown
                </h6>
                <div class="row g-3 mb-3">
                  <div class="col-md-6 bento-input-group-clean">
                    <label>Supplier / Vendor Partner</label>
                    <select name="supplier_id" id="createPkgSupplier" class="form-select bento-input-control" onchange="updateCreateLivePreview()">
                      <option value="" data-name="In-House Direct">-- In-House / Direct Processing --</option>
                      <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s['id'] ?>" data-name="<?= e($s['name']) ?>"><?= e($s['name']) ?> (<?= e($s['country']) ?>)</option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3 bento-input-group-clean">
                    <label>Currency <span class="text-danger">*</span></label>
                    <select name="currency" id="pkgCurrency" class="form-select bento-input-control fw-bold text-dark" onchange="updateCreateLivePreview()">
                      <?php foreach (['USD', 'AED', 'LKR', 'EUR', 'GBP', 'SAR', 'QAR', 'INR', 'CAD', 'AUD'] as $cur): ?>
                        <option value="<?= $cur ?>" <?= $cur === 'USD' ? 'selected' : '' ?>><?= $cur ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3 bento-input-group-clean">
                    <label>Effective Date</label>
                    <input type="date" name="effective_date" class="form-control bento-input-control" value="<?= date('Y-m-d') ?>">
                  </div>
                </div>

                <div class="row g-3 p-3 rounded-3" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
                  <div class="col-md-3 bento-input-group-clean">
                    <label>Supplier Cost</label>
                    <input type="number" step="0.01" name="supplier_cost" id="pkgSupplierCost" class="form-control bento-input-control" value="100.00" oninput="calcSellingPrice('create'); updateCreateLivePreview();">
                  </div>
                  <div class="col-md-3 bento-input-group-clean">
                    <label>Service Fee</label>
                    <input type="number" step="0.01" name="service_fee" id="pkgServiceFee" class="form-control bento-input-control" value="50.00" oninput="calcSellingPrice('create'); updateCreateLivePreview();">
                  </div>
                  <div class="col-md-3 bento-input-group-clean">
                    <label>Tax Rate (%)</label>
                    <input type="number" step="0.01" name="tax_rate" id="pkgTaxRate" class="form-control bento-input-control" value="5.00" oninput="calcSellingPrice('create'); updateCreateLivePreview();">
                  </div>
                  <div class="col-md-3 bento-input-group-clean">
                    <label>Final Selling Price <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="selling_price" id="pkgSellingPrice" class="form-control bento-input-control fw-bold text-primary fs-6" value="157.50" required oninput="updateCreateLivePreview()">
                  </div>
                </div>
              </div>

              <!-- 5. Policies & Audit Notes -->
              <div class="bento-form-panel mb-0">
                <h6 class="bento-form-panel-title">
                  <i class="fa-solid fa-file-contract"></i> 5. Policies &amp; Audit Trail
                </h6>
                <div class="mb-3 bento-input-group-clean">
                  <label>Pricing Notes / Audit Reason</label>
                  <input type="text" name="notes" class="form-control bento-input-control" placeholder="e.g. Q4 standard supplier package rate">
                </div>
                <div class="mb-0 bento-input-group-clean">
                  <label>Cancellation &amp; Refund Policy</label>
                  <textarea name="cancellation_policy" class="form-control bento-input-control" rows="2">Non-refundable once submitted to immigration authorities.</textarea>
                </div>
              </div>

            </div>

            <!-- RIGHT COLUMN: LIVE INTERACTIVE PREVIEW PANE (IMAGE 1 SPEC) -->
            <div class="col-12 col-lg-5">
              <div class="bento-preview-pane">
                <div class="bento-preview-header">
                  <h6 class="bento-preview-title">
                    <i class="fa-solid fa-eye text-primary"></i> Live Package Preview
                  </h6>
                  <span class="bento-preview-badge-live">Live Sync</span>
                </div>

                <!-- Real Interactive Travel Card -->
                <div class="bento-travel-card shadow-sm mb-3">
                  <div class="travel-card-photo-wrap" style="height: 180px;">
                    <img id="createLiveCardImg" src="/assets/images/destinations/default-travel.jpg" alt="Preview Cover" class="travel-card-photo">
                    <div class="travel-photo-badge-left" id="createLiveCountryBadge">
                      <span class="fs-6">🌐</span> <span>Select Destination</span>
                    </div>
                    <div class="travel-photo-badge-right">
                      <span class="badge bg-white text-success border small fw-bold" style="border-radius: var(--bento-radius-pill); font-size: 0.70rem; padding: 4px 8px;">
                        <i class="fa-solid fa-circle text-success me-1" style="font-size: 0.5rem;"></i>Active
                      </span>
                    </div>
                  </div>

                  <div class="travel-card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                      <span class="badge bg-light text-dark border small" id="createLiveCategoryBadge" style="font-size: 0.70rem;">General</span>
                      <span class="text-muted small text-truncate" id="createLiveSupplierBadge" style="font-size: 0.70rem; max-width: 140px;">
                        <i class="fa-solid fa-building me-1"></i>In-House Direct
                      </span>
                    </div>

                    <h4 class="travel-card-title mb-1" id="createLiveTitle" style="font-size: 1.05rem;">UAE 60-Day Tourist Visa</h4>
                    <div class="travel-card-location mb-2" id="createLiveLocation" style="font-size: 0.78rem;">
                      <i class="fa-solid fa-location-dot"></i> <span>Global &bull; Single Entry</span>
                    </div>

                    <div class="travel-card-desc-head mb-1" style="font-size: 0.72rem;">Description</div>
                    <p class="travel-card-desc-text mb-3" id="createLiveDesc" style="font-size: 0.78rem; line-height: 1.4;">
                      Official consular visa pathway with fast-track document pre-check...
                    </p>

                    <!-- 3 Real Stats Row -->
                    <div class="travel-card-stats-row mb-3">
                      <div class="travel-card-stat-col">
                        <span class="travel-card-stat-label">Est. Days</span>
                        <span class="travel-card-stat-val" id="createLiveEstDays">3 Days</span>
                      </div>
                      <div class="travel-card-stat-col" style="border-left: 1px solid #E2E8F0; border-right: 1px solid #E2E8F0;">
                        <span class="travel-card-stat-label">Validity</span>
                        <span class="travel-card-stat-val" id="createLiveValidity">60 Days from issue</span>
                      </div>
                      <div class="travel-card-stat-col">
                        <span class="travel-card-stat-label">Processing</span>
                        <span class="travel-card-stat-val" id="createLiveProcessing">Normal</span>
                      </div>
                    </div>

                    <!-- Price & Airplane Button -->
                    <div class="travel-card-bottom">
                      <div>
                        <div class="travel-card-price-label">Selling Price</div>
                        <div class="travel-card-price-val" id="createLivePrice" style="color: var(--bento-primary); font-size: 1.25rem;">USD 157.50</div>
                      </div>
                      <div class="travel-card-plane-btn" style="pointer-events: none; opacity: 0.9;">
                        <i class="fa-solid fa-plane"></i>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Commercial Breakdown Summary (Image 1 Style) -->
                <div class="p-3 bg-white rounded-3 border shadow-xs">
                  <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                    <span class="fw-bold text-dark small"><i class="fa-solid fa-receipt text-primary me-1"></i> Cost Breakdown</span>
                    <span class="badge bg-light text-dark border small" id="createLiveCurBadge">USD</span>
                  </div>
                  <div class="d-flex justify-content-between small text-muted mb-1">
                    <span>Supplier Cost:</span>
                    <span id="createLiveCostSum">$100.00</span>
                  </div>
                  <div class="d-flex justify-content-between small text-muted mb-1">
                    <span>Service Fee:</span>
                    <span id="createLiveFeeSum">$50.00</span>
                  </div>
                  <div class="d-flex justify-content-between small text-muted mb-2 pb-2 border-bottom">
                    <span>Tax (<span id="createLiveTaxPct">5</span>%):</span>
                    <span id="createLiveTaxSum">$7.50</span>
                  </div>
                  <div class="d-flex justify-content-between fw-bold text-dark fs-6">
                    <span>Total Selling Rate:</span>
                    <span class="text-primary" id="createLiveTotalSum">USD 157.50</span>
                  </div>
                </div>

              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer bg-light border-top py-3 px-4">
          <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="bento-btn-primary" style="padding: 0.5rem 1.75rem;">
            <i class="fa-solid fa-save me-1"></i> Save &amp; Publish Package
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Edit Visa Package (Image 1 2-Column Split Layout) -->
<div class="modal fade" id="editPackageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-split-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden;">
      <div class="modal-header bg-white border-bottom py-3 px-4">
        <div class="d-flex align-items-center gap-2">
          <div style="width: 38px; height: 38px; border-radius: 10px; background: var(--bento-primary-soft); color: var(--bento-primary); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
            <i class="fa-solid fa-pen-to-square"></i>
          </div>
          <div>
            <h5 class="modal-title fw-bold text-dark fs-6 mb-0">Edit Visa Package</h5>
            <small class="text-muted">Modify service specifications, margin rules, and view real-time card reflection.</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form action="/visa-packages/update" method="POST" enctype="multipart/form-data" id="formEditPackage">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="editPkgId">
        <input type="hidden" name="return_to" id="editPkgReturnTo" value="">
        <div class="modal-body p-4" style="background: #FAFAFA;">
          <div class="row g-4">

            <!-- LEFT COLUMN: EDIT FORM (IMAGE 1 SPEC) -->
            <div class="col-12 col-lg-7">

              <!-- 1. Destination & Core Identity -->
              <div class="bento-form-panel">
                <h6 class="bento-form-panel-title">
                  <i class="fa-solid fa-globe"></i> 1. Destination &amp; Core Identity
                </h6>
                <div class="row g-3 mb-3">
                  <div class="col-md-6 bento-input-group-clean">
                    <label>Destination Country <span class="text-danger">*</span></label>
                    <select name="country_id" id="editPkgCountry" class="form-select bento-input-control" required onchange="updateEditLivePreview()">
                      <?php foreach ($countries as $c): ?>
                        <option value="<?= $c['id'] ?>" data-flag="<?= $c['flag_emoji'] ?>" data-name="<?= e($c['name']) ?>">
                          <?= $c['flag_emoji'] ?> <?= e($c['name']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6 bento-input-group-clean">
                    <label>Visa Category <span class="text-danger">*</span></label>
                    <select name="category_id" id="editPkgCategory" class="form-select bento-input-control" required onchange="updateEditLivePreview()">
                      <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" data-name="<?= e($cat['name']) ?>"><?= e($cat['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>

                <div class="mb-3 bento-input-group-clean">
                  <label>Package / Service Name <span class="text-danger">*</span></label>
                  <input type="text" name="name" id="editPkgName" class="form-control bento-input-control" required oninput="updateEditLivePreview()">
                </div>

                <div class="mb-0 bento-input-group-clean">
                  <label>Traveler Description (Displayed on Cards) <span class="text-danger">*</span></label>
                  <textarea name="description" id="editPkgDescription" class="form-control bento-input-control" rows="2" placeholder="Official consular visa pathway..." required oninput="updateEditLivePreview()"></textarea>
                  <div class="form-text small text-muted">Summary displayed on the Travel Card and client proposals.</div>
                </div>
              </div>

              <!-- 2. Travel Card Cover Photo -->
              <div class="bento-form-panel">
                <h6 class="bento-form-panel-title">
                  <i class="fa-solid fa-image"></i> 2. Travel Card Cover Photo
                </h6>
                <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
                  <div style="width: 130px; height: 85px; border-radius: 14px; overflow: hidden; background: #0F172A; flex-shrink: 0; border: 1.5px solid #E2E8F0;">
                    <img id="editPkgPreviewImg" src="/assets/images/destinations/default-travel.jpg" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                  </div>
                  <div class="flex-grow-1">
                    <div class="mb-2 bento-input-group-clean">
                      <label class="mb-1">Upload Photo File</label>
                      <input type="file" name="cover_image" id="editPkgFileInput" class="form-control form-control-sm bento-input-control" accept="image/*" onchange="previewPkgImageLive(this, 'edit')">
                    </div>
                    <div class="bento-input-group-clean">
                      <label class="mb-1">Or Photo URL <small class="text-muted fw-normal">(Optional)</small></label>
                      <input type="text" name="image_url" id="editPkgImageUrlInput" class="form-control form-control-sm bento-input-control" placeholder="e.g. /assets/images/destinations/uae-tourist.jpg" oninput="previewPkgUrlLive(this.value, 'edit')">
                    </div>
                  </div>
                </div>
              </div>

              <!-- 3. Turnaround SLA & Consular Rules -->
              <div class="bento-form-panel">
                <h6 class="bento-form-panel-title">
                  <i class="fa-solid fa-clock"></i> 3. Turnaround SLA &amp; Travel Rules
                </h6>
                <div class="row g-3 mb-3">
                  <div class="col-md-3 bento-input-group-clean">
                    <label>Duration <span class="text-danger">*</span></label>
                    <input type="text" name="duration" id="editPkgDuration" class="form-control bento-input-control" required oninput="updateEditLivePreview()">
                  </div>
                  <div class="col-md-3 bento-input-group-clean">
                    <label>Max Stay</label>
                    <input type="text" name="max_stay" id="editPkgMaxStay" class="form-control bento-input-control" oninput="updateEditLivePreview()">
                  </div>
                  <div class="col-md-3 bento-input-group-clean">
                    <label>Validity</label>
                    <input type="text" name="validity" id="editPkgValidity" class="form-control bento-input-control" oninput="updateEditLivePreview()">
                  </div>
                  <div class="col-md-3 bento-input-group-clean">
                    <label>Est. SLA (Days) <span class="text-danger">*</span></label>
                    <input type="number" name="estimated_days" id="editPkgEstDays" class="form-control bento-input-control" min="1" required oninput="updateEditLivePreview()">
                  </div>
                </div>

                <div class="row g-3">
                  <div class="col-md-6 bento-input-group-clean">
                    <label>Entry Type</label>
                    <select name="entry_type" id="editPkgEntryType" class="form-select bento-input-control" onchange="updateEditLivePreview()">
                      <option value="Single Entry">Single Entry</option>
                      <option value="Multiple Entry">Multiple Entry</option>
                    </select>
                  </div>
                  <div class="col-md-6 bento-input-group-clean">
                    <label>Processing Tier</label>
                    <select name="processing_type" id="editPkgProcessingType" class="form-select bento-input-control" onchange="updateEditLivePreview()">
                      <option value="Normal">Normal</option>
                      <option value="Express">Express</option>
                      <option value="Urgent">Urgent</option>
                      <option value="Super Express">Super Express</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- 4. Commercial Pricing & Margin Breakdown -->
              <div class="bento-form-panel">
                <h6 class="bento-form-panel-title">
                  <i class="fa-solid fa-calculator"></i> 4. Commercial Pricing &amp; Margin Breakdown
                </h6>
                <div class="row g-3 mb-3">
                  <div class="col-md-6 bento-input-group-clean">
                    <label>Supplier / Vendor Partner</label>
                    <select name="supplier_id" id="editPkgSupplier" class="form-select bento-input-control" onchange="updateEditLivePreview()">
                      <option value="" data-name="In-House Direct">-- In-House / Direct Processing --</option>
                      <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s['id'] ?>" data-name="<?= e($s['name']) ?>"><?= e($s['name']) ?> (<?= e($s['country']) ?>)</option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3 bento-input-group-clean">
                    <label>Currency <span class="text-danger">*</span></label>
                    <select name="currency" id="editPkgCurrency" class="form-select bento-input-control fw-bold text-dark" onchange="updateEditLivePreview()">
                      <?php foreach (['USD', 'AED', 'LKR', 'EUR', 'GBP', 'SAR', 'QAR', 'INR', 'CAD', 'AUD'] as $cur): ?>
                        <option value="<?= $cur ?>"><?= $cur ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3 bento-input-group-clean">
                    <label>Effective Date</label>
                    <input type="date" name="effective_date" id="editPkgEffectiveDate" class="form-control bento-input-control">
                  </div>
                </div>

                <div class="row g-3 p-3 rounded-3" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
                  <div class="col-md-3 bento-input-group-clean">
                    <label>Supplier Cost</label>
                    <input type="number" step="0.01" name="supplier_cost" id="editPkgSupplierCost" class="form-control bento-input-control" oninput="calcSellingPrice('edit'); updateEditLivePreview();">
                  </div>
                  <div class="col-md-3 bento-input-group-clean">
                    <label>Service Fee</label>
                    <input type="number" step="0.01" name="service_fee" id="editPkgServiceFee" class="form-control bento-input-control" oninput="calcSellingPrice('edit'); updateEditLivePreview();">
                  </div>
                  <div class="col-md-3 bento-input-group-clean">
                    <label>Tax Rate (%)</label>
                    <input type="number" step="0.01" name="tax_rate" id="editPkgTaxRate" class="form-control bento-input-control" oninput="calcSellingPrice('edit'); updateEditLivePreview();">
                  </div>
                  <div class="col-md-3 bento-input-group-clean">
                    <label>Final Selling Price <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="selling_price" id="editPkgSellingPrice" class="form-control bento-input-control fw-bold text-primary fs-6" required oninput="updateEditLivePreview()">
                  </div>
                </div>
              </div>

              <!-- 5. Policies & Audit Notes -->
              <div class="bento-form-panel mb-0">
                <h6 class="bento-form-panel-title">
                  <i class="fa-solid fa-file-contract"></i> 5. Policies &amp; Audit Trail
                </h6>
                <div class="mb-3 bento-input-group-clean">
                  <label>Pricing Notes / Audit Reason</label>
                  <input type="text" name="notes" id="editPkgNotes" class="form-control bento-input-control" placeholder="e.g. Supplier rate increase for Q4">
                </div>
                <div class="mb-3 bento-input-group-clean">
                  <label>Cancellation &amp; Refund Policy</label>
                  <textarea name="cancellation_policy" id="editPkgCancellationPolicy" class="form-control bento-input-control" rows="2"></textarea>
                </div>
                <div class="form-check form-switch mb-0">
                  <input class="form-check-input" type="checkbox" name="is_active" id="editPkgIsActive" value="1" onchange="updateEditLivePreview()">
                  <label class="form-check-label small fw-semibold text-dark" for="editPkgIsActive">Package is Active &amp; Available for Registration</label>
                </div>
              </div>

            </div>

            <!-- RIGHT COLUMN: EDIT LIVE PREVIEW PANE (IMAGE 1 SPEC) -->
            <div class="col-12 col-lg-5">
              <div class="bento-preview-pane">
                <div class="bento-preview-header">
                  <h6 class="bento-preview-title">
                    <i class="fa-solid fa-eye text-primary"></i> Live Package Preview
                  </h6>
                  <span class="bento-preview-badge-live">Live Sync</span>
                </div>

                <!-- Real Interactive Travel Card -->
                <div class="bento-travel-card shadow-sm mb-3">
                  <div class="travel-card-photo-wrap" style="height: 180px;">
                    <img id="editLiveCardImg" src="/assets/images/destinations/default-travel.jpg" alt="Preview Cover" class="travel-card-photo">
                    <div class="travel-photo-badge-left" id="editLiveCountryBadge">
                      <span class="fs-6">🌐</span> <span>Select Destination</span>
                    </div>
                    <div class="travel-photo-badge-right" id="editLiveActiveBadge">
                      <span class="badge bg-white text-success border small fw-bold" style="border-radius: var(--bento-radius-pill); font-size: 0.70rem; padding: 4px 8px;">
                        <i class="fa-solid fa-circle text-success me-1" style="font-size: 0.5rem;"></i>Active
                      </span>
                    </div>
                  </div>

                  <div class="travel-card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                      <span class="badge bg-light text-dark border small" id="editLiveCategoryBadge" style="font-size: 0.70rem;">General</span>
                      <span class="text-muted small text-truncate" id="editLiveSupplierBadge" style="font-size: 0.70rem; max-width: 140px;">
                        <i class="fa-solid fa-building me-1"></i>In-House Direct
                      </span>
                    </div>

                    <h4 class="travel-card-title mb-1" id="editLiveTitle" style="font-size: 1.05rem;">Package Name</h4>
                    <div class="travel-card-location mb-2" id="editLiveLocation" style="font-size: 0.78rem;">
                      <i class="fa-solid fa-location-dot"></i> <span>Global &bull; Single Entry</span>
                    </div>

                    <div class="travel-card-desc-head mb-1" style="font-size: 0.72rem;">Description</div>
                    <p class="travel-card-desc-text mb-3" id="editLiveDesc" style="font-size: 0.78rem; line-height: 1.4;">
                      Official consular visa pathway with fast-track document pre-check...
                    </p>

                    <!-- 3 Real Stats Row -->
                    <div class="travel-card-stats-row mb-3">
                      <div class="travel-card-stat-col">
                        <span class="travel-card-stat-label">Est. Days</span>
                        <span class="travel-card-stat-val" id="editLiveEstDays">3 Days</span>
                      </div>
                      <div class="travel-card-stat-col" style="border-left: 1px solid #E2E8F0; border-right: 1px solid #E2E8F0;">
                        <span class="travel-card-stat-label">Validity</span>
                        <span class="travel-card-stat-val" id="editLiveValidity">60 Days</span>
                      </div>
                      <div class="travel-card-stat-col">
                        <span class="travel-card-stat-label">Processing</span>
                        <span class="travel-card-stat-val" id="editLiveProcessing">Normal</span>
                      </div>
                    </div>

                    <!-- Price & Airplane Button -->
                    <div class="travel-card-bottom">
                      <div>
                        <div class="travel-card-price-label">Selling Price</div>
                        <div class="travel-card-price-val" id="editLivePrice" style="color: var(--bento-primary); font-size: 1.25rem;">USD 157.50</div>
                      </div>
                      <div class="travel-card-plane-btn" style="pointer-events: none; opacity: 0.9;">
                        <i class="fa-solid fa-plane"></i>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Commercial Breakdown Summary (Image 1 Style) -->
                <div class="p-3 bg-white rounded-3 border shadow-xs">
                  <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                    <span class="fw-bold text-dark small"><i class="fa-solid fa-receipt text-primary me-1"></i> Cost Breakdown</span>
                    <span class="badge bg-light text-dark border small" id="editLiveCurBadge">USD</span>
                  </div>
                  <div class="d-flex justify-content-between small text-muted mb-1">
                    <span>Supplier Cost:</span>
                    <span id="editLiveCostSum">$100.00</span>
                  </div>
                  <div class="d-flex justify-content-between small text-muted mb-1">
                    <span>Service Fee:</span>
                    <span id="editLiveFeeSum">$50.00</span>
                  </div>
                  <div class="d-flex justify-content-between small text-muted mb-2 pb-2 border-bottom">
                    <span>Tax (<span id="editLiveTaxPct">5</span>%):</span>
                    <span id="editLiveTaxSum">$7.50</span>
                  </div>
                  <div class="d-flex justify-content-between fw-bold text-dark fs-6">
                    <span>Total Selling Rate:</span>
                    <span class="text-primary" id="editLiveTotalSum">USD 157.50</span>
                  </div>
                </div>

              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer bg-light border-top py-3 px-4">
          <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="bento-btn-primary" style="padding: 0.5rem 1.75rem;">
            <i class="fa-solid fa-save me-1"></i> Update &amp; Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Price History Timeline -->
<div class="modal fade" id="priceHistoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold">
          <i class="fa-solid fa-clock-rotate-left text-info me-2"></i> Price History &amp; Audit Trail: <span id="priceHistoryPkgName" class="text-primary"></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="alert alert-info border py-2 px-3 small mb-3">
          <i class="fa-solid fa-shield-halved me-1"></i> Historical prices are immutable. Existing applications remain attached to the exact price agreed upon at application creation.
        </div>
        <div id="priceHistoryLoading" class="text-center py-4 text-muted">
          <i class="fa-solid fa-spinner fa-spin fs-4 mb-2"></i>
          <div>Loading price history...</div>
        </div>
        <div id="priceHistoryTableContainer" style="display: none;">
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0 font-sm" style="font-size: 0.85rem;">
              <thead class="table-light">
                <tr>
                  <th>Effective From</th>
                  <th>Effective To</th>
                  <th>Supplier</th>
                  <th>Supplier Cost</th>
                  <th>Service Fee</th>
                  <th>VAT %</th>
                  <th>Selling Price</th>
                  <th>Updated By</th>
                  <th>Notes</th>
                </tr>
              </thead>
              <tbody id="priceHistoryTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light border-top">
        <button type="button" class="btn btn-secondary px-4 fw-semibold" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: + Add Category -->
<div class="modal fade" id="createCategoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-layer-group text-primary me-2"></i> Add Visa Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/visa-packages/categories/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Category Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Tourist Visa, Golden Visa, Student Visa" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Icon Class (FontAwesome)</label>
            <input type="text" name="icon" class="form-control" value="fa-solid fa-passport" placeholder="e.g. fa-solid fa-plane">
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Description</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Brief description of this visa category..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="fa-solid fa-save me-1"></i> Save Category</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: + Add Visa Type -->
<div class="modal fade" id="createTypeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-file-lines text-info me-2"></i> Add Visa Type</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/visa-packages/types/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Type Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. 30 Days Single Entry, 90 Days Multiple" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Icon Class (FontAwesome)</label>
            <input type="text" name="icon" class="form-control" value="fa-solid fa-file-lines" placeholder="e.g. fa-solid fa-file-lines">
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Description</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Brief description..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-info text-white px-4 fw-semibold"><i class="fa-solid fa-save me-1"></i> Save Visa Type</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Image preview file helper with live card reflection
function previewPkgImageLive(input, mode) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const pfx = mode === 'create' ? 'create' : 'edit';
      const img1 = document.getElementById(pfx + 'PkgPreviewImg');
      const img2 = document.getElementById(pfx + 'LiveCardImg');
      if (img1) img1.src = e.target.result;
      if (img2) img2.src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
  }
}

// Image preview URL helper with live card reflection
function previewPkgUrlLive(url, mode) {
  const pfx = mode === 'create' ? 'create' : 'edit';
  const cleanUrl = url && url.trim().length > 3 ? url.trim() : '/assets/images/destinations/default-travel.jpg';
  const img1 = document.getElementById(pfx + 'PkgPreviewImg');
  const img2 = document.getElementById(pfx + 'LiveCardImg');
  if (img1) img1.src = cleanUrl;
  if (img2) img2.src = cleanUrl;
}

// Sub-tab View Switcher (Cards vs Table for Categories and Types)
function setSubTabMode(subTab, mode) {
  const cards = document.getElementById(subTab + 'CardsView');
  const table = document.getElementById(subTab + 'TableView');
  const btnC = document.getElementById('btn' + (subTab === 'cat' ? 'Cat' : 'Type') + 'Cards');
  const btnT = document.getElementById('btn' + (subTab === 'cat' ? 'Cat' : 'Type') + 'Table');

  if (mode === 'table') {
    if (cards) cards.style.display = 'none';
    if (table) table.style.display = 'block';
    if (btnC) { btnC.classList.remove('active', 'btn-primary'); btnC.classList.add('text-muted'); }
    if (btnT) { btnT.classList.add('active', 'btn-primary'); btnT.classList.remove('text-muted'); }
  } else {
    if (cards) cards.style.display = 'block';
    if (table) table.style.display = 'none';
    if (btnC) { btnC.classList.add('active', 'btn-primary'); btnC.classList.remove('text-muted'); }
    if (btnT) { btnT.classList.remove('active', 'btn-primary'); btnT.classList.add('text-muted'); }
  }
}

// Dual View Switcher: Packages Cards View vs Table View
function setVisaViewMode(mode) {
  const cardsView = document.getElementById('packagesCardsView');
  const tableView = document.getElementById('packagesTableView');
  const btnCards = document.getElementById('btnViewCards');
  const btnTable = document.getElementById('btnViewTable');

  if (mode === 'table') {
    if (cardsView) cardsView.style.display = 'none';
    if (tableView) tableView.style.display = 'block';
    if (btnCards) {
      btnCards.classList.remove('active', 'btn-primary');
      btnCards.classList.add('text-muted');
    }
    if (btnTable) {
      btnTable.classList.add('active', 'btn-primary');
      btnTable.classList.remove('text-muted');
    }
    try { localStorage.setItem('visa_view_mode', 'table'); } catch(e){}
  } else {
    if (cardsView) cardsView.style.display = 'block';
    if (tableView) tableView.style.display = 'none';
    if (btnCards) {
      btnCards.classList.add('active', 'btn-primary');
      btnCards.classList.remove('text-muted');
    }
    if (btnTable) {
      btnTable.classList.remove('active', 'btn-primary');
      btnTable.classList.add('text-muted');
    }
    try { localStorage.setItem('visa_view_mode', 'cards'); } catch(e){}
  }
}

function calcSellingPrice(mode) {
  const pfx = mode === 'create' ? 'pkg' : 'editPkg';
  const cost = parseFloat(document.getElementById(pfx + 'SupplierCost').value) || 0;
  const fee = parseFloat(document.getElementById(pfx + 'ServiceFee').value) || 0;
  const tax = parseFloat(document.getElementById(pfx + 'TaxRate').value) || 0;
  const subtotal = cost + fee;
  const total = subtotal + (subtotal * (tax / 100));
  document.getElementById(pfx + 'SellingPrice').value = total.toFixed(2);
}

// Image 1 Live Card Preview Synchronization Functions
function updateCreateLivePreview() {
  const countryEl = document.getElementById('createPkgCountry');
  const catEl = document.getElementById('createPkgCategory');
  const nameEl = document.getElementById('createPkgName');
  const descEl = document.getElementById('createPkgDescription');
  const valEl = document.getElementById('createPkgValidity');
  const daysEl = document.getElementById('createPkgEstDays');
  const entryEl = document.getElementById('createPkgEntryType');
  const procEl = document.getElementById('createPkgProcessingType');
  const supEl = document.getElementById('createPkgSupplier');
  const curEl = document.getElementById('pkgCurrency');
  const costEl = document.getElementById('pkgSupplierCost');
  const feeEl = document.getElementById('pkgServiceFee');
  const taxEl = document.getElementById('pkgTaxRate');
  const priceEl = document.getElementById('pkgSellingPrice');

  const cur = curEl ? curEl.value : 'USD';
  const name = (nameEl && nameEl.value.trim()) ? nameEl.value.trim() : 'UAE 60-Day Tourist Visa';
  const desc = (descEl && descEl.value.trim()) ? descEl.value.trim() : 'Official consular visa pathway with verified embassy documentation.';
  const estDays = (daysEl && daysEl.value) ? daysEl.value : '3';
  const val = (valEl && valEl.value.trim()) ? valEl.value.trim() : '60 Days';
  const proc = procEl ? procEl.value : 'Normal';
  const entry = entryEl ? entryEl.value : 'Single Entry';
  const cost = parseFloat(costEl ? costEl.value : 0) || 0;
  const fee = parseFloat(feeEl ? feeEl.value : 0) || 0;
  const tax = parseFloat(taxEl ? taxEl.value : 0) || 0;
  const taxAmt = (cost + fee) * (tax / 100);
  const total = parseFloat(priceEl ? priceEl.value : (cost + fee + taxAmt)) || (cost + fee + taxAmt);

  const selectedCountryOpt = countryEl ? countryEl.options[countryEl.selectedIndex] : null;
  const flag = selectedCountryOpt ? (selectedCountryOpt.getAttribute('data-flag') || '🌐') : '🌐';
  const cName = selectedCountryOpt ? (selectedCountryOpt.getAttribute('data-name') || 'Select Destination') : 'Select Destination';

  const catOpt = catEl ? catEl.options[catEl.selectedIndex] : null;
  const catName = catOpt ? (catOpt.getAttribute('data-name') || 'General') : 'General';
  const supOpt = supEl ? supEl.options[supEl.selectedIndex] : null;
  const supName = supOpt ? (supOpt.getAttribute('data-name') || 'In-House Direct') : 'In-House Direct';

  const elCountryBadge = document.getElementById('createLiveCountryBadge');
  if (elCountryBadge) elCountryBadge.innerHTML = `<span class="fs-6">${flag}</span> <span>${cName}</span>`;

  const elTitle = document.getElementById('createLiveTitle');
  if (elTitle) elTitle.textContent = name;

  const elLoc = document.getElementById('createLiveLocation');
  if (elLoc) elLoc.innerHTML = `<i class="fa-solid fa-location-dot"></i> <span>${cName} &bull; ${entry}</span>`;

  const elDesc = document.getElementById('createLiveDesc');
  if (elDesc) elDesc.textContent = desc;

  const elDays = document.getElementById('createLiveEstDays');
  if (elDays) elDays.textContent = estDays + (parseInt(estDays) === 1 ? ' Day' : ' Days');

  const elVal = document.getElementById('createLiveValidity');
  if (elVal) elVal.textContent = val;

  const elProc = document.getElementById('createLiveProcessing');
  if (elProc) elProc.textContent = proc;

  const elPrice = document.getElementById('createLivePrice');
  if (elPrice) elPrice.textContent = `${cur} ${total.toFixed(2)}`;

  const elCat = document.getElementById('createLiveCategoryBadge');
  if (elCat) elCat.textContent = catName;

  const elSup = document.getElementById('createLiveSupplierBadge');
  if (elSup) elSup.innerHTML = `<i class="fa-solid fa-building me-1"></i>${supName}`;

  const elCurBadge = document.getElementById('createLiveCurBadge');
  if (elCurBadge) elCurBadge.textContent = cur;

  const elCostSum = document.getElementById('createLiveCostSum');
  if (elCostSum) elCostSum.textContent = `${cur} ${cost.toFixed(2)}`;

  const elFeeSum = document.getElementById('createLiveFeeSum');
  if (elFeeSum) elFeeSum.textContent = `${cur} ${fee.toFixed(2)}`;

  const elTaxPct = document.getElementById('createLiveTaxPct');
  if (elTaxPct) elTaxPct.textContent = tax.toFixed(1);

  const elTaxSum = document.getElementById('createLiveTaxSum');
  if (elTaxSum) elTaxSum.textContent = `${cur} ${taxAmt.toFixed(2)}`;

  const elTotalSum = document.getElementById('createLiveTotalSum');
  if (elTotalSum) elTotalSum.textContent = `${cur} ${total.toFixed(2)}`;
}

function updateEditLivePreview() {
  const countryEl = document.getElementById('editPkgCountry');
  const catEl = document.getElementById('editPkgCategory');
  const nameEl = document.getElementById('editPkgName');
  const descEl = document.getElementById('editPkgDescription');
  const valEl = document.getElementById('editPkgValidity');
  const daysEl = document.getElementById('editPkgEstDays');
  const entryEl = document.getElementById('editPkgEntryType');
  const procEl = document.getElementById('editPkgProcessingType');
  const supEl = document.getElementById('editPkgSupplier');
  const curEl = document.getElementById('editPkgCurrency');
  const costEl = document.getElementById('editPkgSupplierCost');
  const feeEl = document.getElementById('editPkgServiceFee');
  const taxEl = document.getElementById('editPkgTaxRate');
  const priceEl = document.getElementById('editPkgSellingPrice');
  const activeEl = document.getElementById('editPkgIsActive');

  const cur = curEl ? curEl.value : 'USD';
  const name = (nameEl && nameEl.value.trim()) ? nameEl.value.trim() : 'Package Name';
  const desc = (descEl && descEl.value.trim()) ? descEl.value.trim() : 'Official consular visa pathway...';
  const estDays = (daysEl && daysEl.value) ? daysEl.value : '3';
  const val = (valEl && valEl.value.trim()) ? valEl.value.trim() : '60 Days';
  const proc = procEl ? procEl.value : 'Normal';
  const entry = entryEl ? entryEl.value : 'Single Entry';
  const cost = parseFloat(costEl ? costEl.value : 0) || 0;
  const fee = parseFloat(feeEl ? feeEl.value : 0) || 0;
  const tax = parseFloat(taxEl ? taxEl.value : 0) || 0;
  const taxAmt = (cost + fee) * (tax / 100);
  const total = parseFloat(priceEl ? priceEl.value : (cost + fee + taxAmt)) || (cost + fee + taxAmt);

  const selectedCountryOpt = countryEl ? countryEl.options[countryEl.selectedIndex] : null;
  const flag = selectedCountryOpt ? (selectedCountryOpt.getAttribute('data-flag') || '🌐') : '🌐';
  const cName = selectedCountryOpt ? (selectedCountryOpt.getAttribute('data-name') || 'Destination') : 'Destination';

  const catOpt = catEl ? catEl.options[catEl.selectedIndex] : null;
  const catName = catOpt ? (catOpt.getAttribute('data-name') || 'General') : 'General';
  const supOpt = supEl ? supEl.options[supEl.selectedIndex] : null;
  const supName = supOpt ? (supOpt.getAttribute('data-name') || 'In-House Direct') : 'In-House Direct';

  const elCountryBadge = document.getElementById('editLiveCountryBadge');
  if (elCountryBadge) elCountryBadge.innerHTML = `<span class="fs-6">${flag}</span> <span>${cName}</span>`;

  const elTitle = document.getElementById('editLiveTitle');
  if (elTitle) elTitle.textContent = name;

  const elLoc = document.getElementById('editLiveLocation');
  if (elLoc) elLoc.innerHTML = `<i class="fa-solid fa-location-dot"></i> <span>${cName} &bull; ${entry}</span>`;

  const elDesc = document.getElementById('editLiveDesc');
  if (elDesc) elDesc.textContent = desc;

  const elDays = document.getElementById('editLiveEstDays');
  if (elDays) elDays.textContent = estDays + (parseInt(estDays) === 1 ? ' Day' : ' Days');

  const elVal = document.getElementById('editLiveValidity');
  if (elVal) elVal.textContent = val;

  const elProc = document.getElementById('editLiveProcessing');
  if (elProc) elProc.textContent = proc;

  const elPrice = document.getElementById('editLivePrice');
  if (elPrice) elPrice.textContent = `${cur} ${total.toFixed(2)}`;

  const elCat = document.getElementById('editLiveCategoryBadge');
  if (elCat) elCat.textContent = catName;

  const elSup = document.getElementById('editLiveSupplierBadge');
  if (elSup) elSup.innerHTML = `<i class="fa-solid fa-building me-1"></i>${supName}`;

  const elActiveBadge = document.getElementById('editLiveActiveBadge');
  if (elActiveBadge) {
    const isActive = activeEl && activeEl.checked;
    elActiveBadge.innerHTML = isActive 
      ? `<span class="badge bg-white text-success border small fw-bold" style="border-radius: var(--bento-radius-pill); font-size: 0.70rem; padding: 4px 8px;"><i class="fa-solid fa-circle text-success me-1" style="font-size: 0.5rem;"></i>Active</span>`
      : `<span class="badge bg-white text-secondary border small fw-bold" style="border-radius: var(--bento-radius-pill); font-size: 0.70rem; padding: 4px 8px;"><i class="fa-solid fa-circle text-muted me-1" style="font-size: 0.5rem;"></i>Inactive</span>`;
  }

  const elCurBadge = document.getElementById('editLiveCurBadge');
  if (elCurBadge) elCurBadge.textContent = cur;

  const elCostSum = document.getElementById('editLiveCostSum');
  if (elCostSum) elCostSum.textContent = `${cur} ${cost.toFixed(2)}`;

  const elFeeSum = document.getElementById('editLiveFeeSum');
  if (elFeeSum) elFeeSum.textContent = `${cur} ${fee.toFixed(2)}`;

  const elTaxPct = document.getElementById('editLiveTaxPct');
  if (elTaxPct) elTaxPct.textContent = tax.toFixed(1);

  const elTaxSum = document.getElementById('editLiveTaxSum');
  if (elTaxSum) elTaxSum.textContent = `${cur} ${taxAmt.toFixed(2)}`;

  const elTotalSum = document.getElementById('editLiveTotalSum');
  if (elTotalSum) elTotalSum.textContent = `${cur} ${total.toFixed(2)}`;
}

function openCreatePackageModal() {
  updateCreateLivePreview();
  const el = document.getElementById('createPackageModal');
  if (el) {
    if (window.bootstrap && bootstrap.Modal) {
      const modal = bootstrap.Modal.getOrCreateInstance(el);
      modal.show();
    } else {
      el.classList.add('show');
      el.style.display = 'block';
    }
  }
}

function openEditPackageModal(pkg) {
  document.getElementById('editPkgId').value = pkg.id;
  document.getElementById('editPkgCountry').value = pkg.country_id;
  document.getElementById('editPkgCategory').value = pkg.category_id;
  document.getElementById('editPkgSupplier').value = pkg.supplier_id || '';
  document.getElementById('editPkgCurrency').value = pkg.currency || 'USD';
  document.getElementById('editPkgEffectiveDate').value = pkg.effective_date || '<?= date('Y-m-d') ?>';
  document.getElementById('editPkgName').value = pkg.name;
  if (document.getElementById('editPkgDescription')) {
    document.getElementById('editPkgDescription').value = pkg.description || '';
  }
  document.getElementById('editPkgDuration').value = pkg.duration;
  document.getElementById('editPkgMaxStay').value = pkg.max_stay || pkg.duration;
  document.getElementById('editPkgEntryType').value = pkg.entry_type || 'Single Entry';
  document.getElementById('editPkgProcessingType').value = pkg.processing_type || 'Normal';
  document.getElementById('editPkgValidity').value = pkg.validity || '60 Days';
  document.getElementById('editPkgEstDays').value = pkg.estimated_days || 3;
  document.getElementById('editPkgSupplierCost').value = parseFloat(pkg.supplier_cost || 0).toFixed(2);
  document.getElementById('editPkgServiceFee').value = parseFloat(pkg.service_fee || 0).toFixed(2);
  document.getElementById('editPkgTaxRate').value = parseFloat(pkg.tax_rate || 5).toFixed(2);
  document.getElementById('editPkgSellingPrice').value = parseFloat(pkg.selling_price || 0).toFixed(2);
  document.getElementById('editPkgNotes').value = pkg.notes || '';
  document.getElementById('editPkgCancellationPolicy').value = pkg.cancellation_policy || '';
  document.getElementById('editPkgImageUrlInput').value = pkg.image_url || '';
  const coverUrl = pkg.image_url || '/assets/images/destinations/default-travel.jpg';
  if (document.getElementById('editPkgPreviewImg')) {
    document.getElementById('editPkgPreviewImg').src = coverUrl;
  }
  if (document.getElementById('editLiveCardImg')) {
    document.getElementById('editLiveCardImg').src = coverUrl;
  }
  document.getElementById('editPkgIsActive').checked = pkg.is_active == 1;

  updateEditLivePreview();

  const modal = new bootstrap.Modal(document.getElementById('editPackageModal'));
  modal.show();
}


function viewPriceHistory(pkgId, pkgName) {
  document.getElementById('priceHistoryPkgName').innerText = pkgName;
  document.getElementById('priceHistoryLoading').style.display = 'block';
  document.getElementById('priceHistoryTableContainer').style.display = 'none';

  const modal = new bootstrap.Modal(document.getElementById('priceHistoryModal'));
  modal.show();

  fetch('/visa-packages/price-history?id=' + encodeURIComponent(pkgId))
    .then(r => r.json())
    .then(data => {
      document.getElementById('priceHistoryLoading').style.display = 'none';
      document.getElementById('priceHistoryTableContainer').style.display = 'block';
      const tbody = document.getElementById('priceHistoryTableBody');
      tbody.innerHTML = '';

      if (!data.success || !data.history || data.history.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">No historical price changes recorded for this package.</td></tr>';
        return;
      }

      data.history.forEach(h => {
        const cur = h.currency || 'USD';
        const toDate = h.effective_to ? `<span class="badge bg-light text-dark border">${h.effective_to.substring(0, 10)}</span>` : '<span class="badge bg-success-subtle text-success border">Current Active</span>';
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td><span class="fw-semibold text-dark">${h.effective_from ? h.effective_from.substring(0, 10) : '—'}</span></td>
          <td>${toDate}</td>
          <td>${h.supplier_name ? '<span class="badge bg-light text-dark border">' + h.supplier_name + '</span>' : '<span class="text-muted">Direct</span>'}</td>
          <td>${cur} ${parseFloat(h.supplier_cost || 0).toFixed(2)}</td>
          <td>${cur} ${parseFloat(h.service_fee || 0).toFixed(2)}</td>
          <td>${parseFloat(h.tax_rate || 0).toFixed(2)}%</td>
          <td class="fw-bold text-primary">${cur} ${parseFloat(h.selling_price || 0).toFixed(2)}</td>
          <td>${h.created_by_name || 'System'}</td>
          <td class="small text-muted">${h.notes || '—'}</td>
        `;
        tbody.appendChild(tr);
      });
    })
    .catch(err => {
      document.getElementById('priceHistoryLoading').innerHTML = '<div class="text-danger py-3"><i class="fa-solid fa-triangle-exclamation me-1"></i> Failed to load price history.</div>';
    });
}

// Auto-open handler for URL parameters, View Mode Restore & Live Preview Init
const allPackagesData = <?= json_encode($packages ?? []) ?>;
document.addEventListener('DOMContentLoaded', function() {
  let savedMode = 'cards';
  try {
    savedMode = localStorage.getItem('visa_view_mode') || 'cards';
  } catch(e) {}
  setVisaViewMode(savedMode);

  // Initialize live preview for create modal
  updateCreateLivePreview();

  const urlParams = new URLSearchParams(window.location.search);
  const editId = urlParams.get('edit');
  if (editId && Array.isArray(allPackagesData)) {
    const targetPkg = allPackagesData.find(p => p.id == editId);
    if (targetPkg) {
      openEditPackageModal(targetPkg);
    }
  }
  if (urlParams.get('create') === '1' || window.location.hash === '#create') {
    setTimeout(openCreatePackageModal, 150);
  }
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
