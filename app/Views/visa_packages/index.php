<?php
$pageTitle = 'Visa Services & Packages — MS TRAVEL HUB';
$flash = get_flash();
$activeTab = $activeTab ?? 'packages';
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
?>

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
      <h3 class="fw-bold brand-font text-dark mb-0">Visa Services &amp; Package Inventory</h3>
      <p class="text-muted small mb-0">Manage global visa packages, destination country rules, categories, supplier costs, service fees, currencies &amp; immutable price audit history.</p>
    </div>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-outline-warning px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#adjustInventoryModal">
        <i class="fa-solid fa-boxes-packing me-1"></i> Adjust Inventory
      </button>
      <button type="button" class="btn btn-outline-primary px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
        <i class="fa-solid fa-layer-group me-1"></i> Add Category
      </button>
      <button type="button" class="btn btn-primary px-3 shadow fw-semibold" data-bs-toggle="modal" data-bs-target="#createPackageModal">
        <i class="fa-solid fa-plus me-1"></i> Add Visa Package
      </button>
    </div>
  </div>

  <!-- KPI Metrics Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
      <div class="stat-card stat-card-blue p-3">
        <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary fs-5 p-2">
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
      <a class="nav-link <?= $activeTab === 'inventory' ? 'active fw-bold' : '' ?>" href="/visa-packages?tab=inventory">
        <i class="fa-solid fa-clock-rotate-left me-1.5 text-warning"></i> Inventory &amp; Price History
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

    <!-- Packages Table -->
    <div class="card card-enterprise shadow-sm border">
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
                      <button type="button" class="btn btn-outline-warning py-1 px-2" title="Adjust Inventory / Log Transaction" onclick="openAdjustInventoryModal(<?= $pkg['id'] ?>, '<?= e(addslashes($pkg['name'])) ?>')">
                        <i class="fa-solid fa-boxes-packing"></i>
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

  <?php elseif ($activeTab === 'inventory'): ?>
    <!-- Inventory & Price History Tab with Combinable Filter Bar -->
    <div class="card card-enterprise mb-4 shadow-sm border">
      <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-sliders text-warning me-2"></i> Inventory &amp; Transaction Filters</h6>
        <span class="text-muted small">Filter changes by date presets, supplier, country, currency, or application reference</span>
      </div>
      <div class="card-body p-3">
        <form action="/visa-packages" method="GET" class="row g-2 align-items-end">
          <input type="hidden" name="tab" value="inventory">
          
          <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Date Preset</label>
            <select name="inv_date_preset" class="form-select form-select-sm" onchange="if(this.value){document.getElementById('invDateFrom').value='';document.getElementById('invDateTo').value='';}">
              <option value="">-- Custom Range --</option>
              <option value="today" <?= ($_GET['inv_date_preset'] ?? '') === 'today' ? 'selected' : '' ?>>Today</option>
              <option value="yesterday" <?= ($_GET['inv_date_preset'] ?? '') === 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
              <option value="this_week" <?= ($_GET['inv_date_preset'] ?? '') === 'this_week' ? 'selected' : '' ?>>This Week</option>
              <option value="this_month" <?= ($_GET['inv_date_preset'] ?? '') === 'this_month' ? 'selected' : '' ?>>This Month</option>
            </select>
          </div>
          
          <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Date From</label>
            <input type="date" name="inv_date_from" id="invDateFrom" class="form-control form-control-sm" value="<?= e($_GET['inv_date_from'] ?? '') ?>">
          </div>
          
          <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Date To</label>
            <input type="date" name="inv_date_to" id="invDateTo" class="form-control form-control-sm" value="<?= e($_GET['inv_date_to'] ?? '') ?>">
          </div>
          
          <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Supplier</label>
            <select name="inv_supplier" class="form-select form-select-sm">
              <option value="">-- All Suppliers --</option>
              <?php foreach ($suppliers as $s): ?>
                <option value="<?= $s['id'] ?>" <?= ((int)($_GET['inv_supplier'] ?? 0) === (int)$s['id']) ? 'selected' : '' ?>>
                  <?= e($s['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Destination Country</label>
            <select name="inv_country" class="form-select form-select-sm">
              <option value="">-- All Countries --</option>
              <?php foreach ($countries as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ((int)($_GET['inv_country'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                  <?= $c['flag_emoji'] ?> <?= e($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Visa Package</label>
            <select name="inv_package" class="form-select form-select-sm">
              <option value="">-- All Packages --</option>
              <?php foreach ($packages as $p): ?>
                <option value="<?= $p['id'] ?>" <?= ((int)($_GET['inv_package'] ?? 0) === (int)$p['id']) ? 'selected' : '' ?>>
                  <?= e($p['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Currency</label>
            <select name="inv_currency" class="form-select form-select-sm">
              <option value="">-- All Currencies --</option>
              <?php foreach (['USD', 'AED', 'LKR', 'EUR', 'GBP', 'SAR', 'QAR', 'INR', 'CAD', 'AUD'] as $cur): ?>
                <option value="<?= $cur ?>" <?= (($_GET['inv_currency'] ?? '') === $cur) ? 'selected' : '' ?>><?= $cur ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Staff / User</label>
            <select name="inv_user" class="form-select form-select-sm">
              <option value="">-- All Users --</option>
              <?php foreach ($staffUsers as $u): ?>
                <option value="<?= $u['id'] ?>" <?= ((int)($_GET['inv_user'] ?? 0) === (int)$u['id']) ? 'selected' : '' ?>><?= e($u['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Application Ref</label>
            <input type="text" name="inv_app_ref" class="form-control form-control-sm" placeholder="e.g. APP-2026-0001" value="<?= e($_GET['inv_app_ref'] ?? '') ?>">
          </div>

          <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="fa-solid fa-filter me-1"></i> Apply Filters</button>
            <a href="/visa-packages?tab=inventory" class="btn btn-light border btn-sm" title="Reset Filters"><i class="fa-solid fa-rotate-left"></i> Reset</a>
          </div>
        </form>
      </div>
    </div>

    <!-- Inventory Transactions Table -->
    <div class="card card-enterprise shadow-sm border">
      <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Audit Ledger &amp; Inventory History (<?= count($inventoryTransactions) ?> Records)</h6>
        <span class="badge bg-light text-muted border">Immutable Historical Records</span>
      </div>
      <div class="table-responsive">
        <table class="table table-enterprise table-hover mb-0 align-middle font-sm" style="font-size: 0.85rem;">
          <thead>
            <tr>
              <th>Date &amp; Time</th>
              <th>Action Type</th>
              <th>Visa Package</th>
              <th>Supplier</th>
              <th>Previous Pricing</th>
              <th>New Pricing</th>
              <th>Effective Date</th>
              <th>Modified By</th>
              <th>Notes / Context</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($inventoryTransactions)): ?>
              <tr>
                <td colspan="9" class="text-center py-5 text-muted">
                  <i class="fa-solid fa-folder-open fs-3 d-block mb-2 text-secondary opacity-50"></i>
                  No inventory or price change transactions found matching filters.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($inventoryTransactions as $it): ?>
                <?php $tCurr = $it['currency'] ?? 'USD'; ?>
                <tr>
                  <td>
                    <div class="fw-bold text-dark"><?= date('d M Y', strtotime($it['created_at'])) ?></div>
                    <div class="text-muted small"><?= date('H:i A', strtotime($it['created_at'])) ?></div>
                  </td>
                  <td>
                    <?php if ($it['action_type'] === 'Created'): ?>
                      <span class="badge bg-success-subtle text-success border">Created</span>
                    <?php elseif ($it['action_type'] === 'Price Updated'): ?>
                      <span class="badge bg-warning-subtle text-warning border">Price Updated</span>
                    <?php elseif ($it['action_type'] === 'Archived'): ?>
                      <span class="badge bg-danger-subtle text-danger border">Archived</span>
                    <?php else: ?>
                      <span class="badge bg-info-subtle text-info border"><?= e($it['action_type']) ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="fw-bold text-dark"><?= e($it['package_name']) ?></div>
                    <div class="text-muted small"><?= e($it['country_name'] ?? 'Global') ?></div>
                  </td>
                  <td>
                    <?= !empty($it['supplier_name_ref']) ? '<span class="badge bg-light text-dark border">' . e($it['supplier_name_ref']) . '</span>' : '<span class="text-muted">—</span>' ?>
                  </td>
                  <td>
                    <?php if ($it['prev_price'] !== null): ?>
                      <div class="text-muted small">Cost: <?= e($tCurr) ?> <?= number_format((float)$it['prev_cost'], 2) ?></div>
                      <div class="text-muted small">Price: <?= e($tCurr) ?> <?= number_format((float)$it['prev_price'], 2) ?></div>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($it['new_price'] !== null): ?>
                      <div class="fw-semibold text-dark small">Cost: <?= e($tCurr) ?> <?= number_format((float)$it['new_cost'], 2) ?></div>
                      <div class="fw-bold text-primary small">Price: <?= e($tCurr) ?> <?= number_format((float)$it['new_price'], 2) ?></div>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border"><?= e($it['effective_date'] ?: date('Y-m-d', strtotime($it['created_at']))) ?></span>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark small"><?= e($it['user_name'] ?? 'System') ?></div>
                  </td>
                  <td style="max-width: 250px;">
                    <div class="text-muted small text-truncate" title="<?= e($it['notes']) ?>">
                      <?= e($it['notes'] ?: '—') ?>
                      <?php if (!empty($it['application_number'])): ?>
                        <span class="badge bg-light text-primary border ms-1">App: <?= e($it['application_number']) ?></span>
                      <?php endif; ?>
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
    <!-- Visa Categories Table -->
    <div class="card card-enterprise shadow-sm border">
      <div class="card-header bg-white d-flex align-items-center justify-content-between py-3 border-bottom">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-layer-group text-primary me-2"></i> Visa Categories</h6>
        <button type="button" class="btn btn-primary btn-sm px-3 shadow fw-semibold" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
          <i class="fa-solid fa-plus me-1"></i> Add Category
        </button>
      </div>
      <div class="table-responsive">
        <table class="table table-enterprise table-hover mb-0 align-middle">
          <thead>
            <tr>
              <th>Category Name</th>
              <th>Slug / Code</th>
              <th>Description</th>
              <th>Packages Linked</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $cat): ?>
              <tr>
                <td>
                  <div class="fw-bold text-dark"><i class="<?= e($cat['icon'] ?: 'fa-solid fa-passport') ?> text-primary me-2"></i><?= e($cat['name']) ?></div>
                </td>
                <td><span class="badge bg-light text-dark border font-monospace"><?= e($cat['slug']) ?></span></td>
                <td><span class="text-muted small"><?= e($cat['description'] ?: '—') ?></span></td>
                <td><span class="badge bg-primary-subtle text-primary fw-bold"><?= (int)($cat['packages_count'] ?? 0) ?> Packages</span></td>
                <td>
                  <?php if (!empty($cat['is_active'])): ?>
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

  <?php elseif ($activeTab === 'types'): ?>
    <!-- Visa Types Table -->
    <div class="card card-enterprise shadow-sm border">
      <div class="card-header bg-white d-flex align-items-center justify-content-between py-3 border-bottom">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-file-lines text-info me-2"></i> Visa Types &amp; Classifications</h6>
        <button type="button" class="btn btn-info text-white btn-sm px-3 shadow fw-semibold" data-bs-toggle="modal" data-bs-target="#createTypeModal">
          <i class="fa-solid fa-plus me-1"></i> Add Visa Type
        </button>
      </div>
      <div class="table-responsive">
        <table class="table table-enterprise table-hover mb-0 align-middle">
          <thead>
            <tr>
              <th>Type Name</th>
              <th>Slug</th>
              <th>Description</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($visaTypes as $vt): ?>
              <tr>
                <td>
                  <div class="fw-bold text-dark"><i class="<?= e($vt['icon'] ?: 'fa-solid fa-file') ?> text-info me-2"></i><?= e($vt['name']) ?></div>
                </td>
                <td><span class="badge bg-light text-dark border font-monospace"><?= e($vt['slug']) ?></span></td>
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

<!-- Modal: + Add Visa Package -->
<div class="modal fade" id="createPackageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold">
          <i class="fa-solid fa-plus-circle text-primary me-2"></i> Add Manual Visa Package
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/visa-packages/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Destination Country <span class="text-danger">*</span></label>
              <select name="country_id" class="form-select" required>
                <option value="">-- Choose Country --</option>
                <?php foreach ($countries as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= $c['flag_emoji'] ?> <?= e($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Visa Category <span class="text-danger">*</span></label>
              <select name="category_id" class="form-select" required>
                <option value="">-- Choose Category --</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Supplier / Vendor Partner</label>
              <select name="supplier_id" class="form-select">
                <option value="">-- In-House / Direct Processing --</option>
                <?php foreach ($suppliers as $s): ?>
                  <option value="<?= $s['id'] ?>"><?= e($s['name']) ?> (<?= e($s['country']) ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Currency <span class="text-danger">*</span></label>
              <select name="currency" id="pkgCurrency" class="form-select fw-bold text-dark">
                <?php foreach (['USD', 'AED', 'LKR', 'EUR', 'GBP', 'SAR', 'QAR', 'INR', 'CAD', 'AUD'] as $cur): ?>
                  <option value="<?= $cur ?>" <?= $cur === 'USD' ? 'selected' : '' ?>><?= $cur ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Effective Date</label>
              <input type="date" name="effective_date" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Package / Service Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" placeholder="e.g. 30 Days Tourist Visa Express" required>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Duration <span class="text-danger">*</span></label>
              <input type="text" name="duration" class="form-control" placeholder="e.g. 30 Days" value="30 Days" required>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Max Stay</label>
              <input type="text" name="max_stay" class="form-control" placeholder="e.g. 30 Days" value="30 Days">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Entry Type</label>
              <select name="entry_type" class="form-select">
                <option value="Single Entry">Single Entry</option>
                <option value="Multiple Entry">Multiple Entry</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Processing Type</label>
              <select name="processing_type" class="form-select">
                <option value="Normal">Normal</option>
                <option value="Express">Express</option>
                <option value="Urgent">Urgent</option>
                <option value="Super Express">Super Express</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Validity</label>
              <input type="text" name="validity" class="form-control" placeholder="e.g. 60 Days" value="60 Days">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Estimated Days</label>
              <input type="number" name="estimated_days" class="form-control" min="1" value="3">
            </div>
          </div>

          <hr class="my-3">
          <h6 class="fw-bold text-dark mb-3" style="font-size: 0.88rem;">Financial Cost Breakdown &amp; Selling Price</h6>

          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Supplier Cost</label>
              <input type="number" step="0.01" name="supplier_cost" id="pkgSupplierCost" class="form-control" value="100.00" oninput="calcSellingPrice('create')">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Service Fee</label>
              <input type="number" step="0.01" name="service_fee" id="pkgServiceFee" class="form-control" value="50.00" oninput="calcSellingPrice('create')">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Tax Rate (%)</label>
              <input type="number" step="0.01" name="tax_rate" id="pkgTaxRate" class="form-control" value="5.00" oninput="calcSellingPrice('create')">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Final Selling Price <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="selling_price" id="pkgSellingPrice" class="form-control fw-bold text-primary" value="157.50" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Pricing Notes / Audit Reason</label>
            <input type="text" name="notes" class="form-control" placeholder="e.g. Q4 2026 standard supplier package rate">
          </div>

          <div>
            <label class="form-label small fw-semibold">Cancellation &amp; Refund Policy</label>
            <textarea name="cancellation_policy" class="form-control" rows="2">Non-refundable once submitted to immigration authorities.</textarea>
          </div>
        </div>
        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="fa-solid fa-save me-1"></i> Save Visa Package</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Edit Visa Package -->
<div class="modal fade" id="editPackageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold">
          <i class="fa-solid fa-pen-to-square text-primary me-2"></i> Edit Visa Package
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/visa-packages/update" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="editPkgId">
        <div class="modal-body p-4">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Destination Country <span class="text-danger">*</span></label>
              <select name="country_id" id="editPkgCountry" class="form-select" required>
                <?php foreach ($countries as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= $c['flag_emoji'] ?> <?= e($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Visa Category <span class="text-danger">*</span></label>
              <select name="category_id" id="editPkgCategory" class="form-select" required>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Supplier / Vendor Partner</label>
              <select name="supplier_id" id="editPkgSupplier" class="form-select">
                <option value="">-- In-House / Direct Processing --</option>
                <?php foreach ($suppliers as $s): ?>
                  <option value="<?= $s['id'] ?>"><?= e($s['name']) ?> (<?= e($s['country']) ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Currency <span class="text-danger">*</span></label>
              <select name="currency" id="editPkgCurrency" class="form-select fw-bold text-dark">
                <?php foreach (['USD', 'AED', 'LKR', 'EUR', 'GBP', 'SAR', 'QAR', 'INR', 'CAD', 'AUD'] as $cur): ?>
                  <option value="<?= $cur ?>"><?= $cur ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Effective Date</label>
              <input type="date" name="effective_date" id="editPkgEffectiveDate" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Package / Service Name <span class="text-danger">*</span></label>
              <input type="text" name="name" id="editPkgName" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Duration <span class="text-danger">*</span></label>
              <input type="text" name="duration" id="editPkgDuration" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Max Stay</label>
              <input type="text" name="max_stay" id="editPkgMaxStay" class="form-control">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Entry Type</label>
              <select name="entry_type" id="editPkgEntryType" class="form-select">
                <option value="Single Entry">Single Entry</option>
                <option value="Multiple Entry">Multiple Entry</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Processing Type</label>
              <select name="processing_type" id="editPkgProcessingType" class="form-select">
                <option value="Normal">Normal</option>
                <option value="Express">Express</option>
                <option value="Urgent">Urgent</option>
                <option value="Super Express">Super Express</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Validity</label>
              <input type="text" name="validity" id="editPkgValidity" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Estimated Days</label>
              <input type="number" name="estimated_days" id="editPkgEstDays" class="form-control" min="1">
            </div>
          </div>

          <hr class="my-3">
          <h6 class="fw-bold text-dark mb-3" style="font-size: 0.88rem;">Financial Cost Breakdown &amp; Selling Price</h6>

          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Supplier Cost</label>
              <input type="number" step="0.01" name="supplier_cost" id="editPkgSupplierCost" class="form-control" oninput="calcSellingPrice('edit')">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Service Fee</label>
              <input type="number" step="0.01" name="service_fee" id="editPkgServiceFee" class="form-control" oninput="calcSellingPrice('edit')">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Tax Rate (%)</label>
              <input type="number" step="0.01" name="tax_rate" id="editPkgTaxRate" class="form-control" oninput="calcSellingPrice('edit')">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Final Selling Price <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="selling_price" id="editPkgSellingPrice" class="form-control fw-bold text-primary" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Reason for Price / Detail Update (Recorded in Audit Ledger)</label>
            <input type="text" name="notes" id="editPkgNotes" class="form-control" placeholder="e.g. Supplier rate increase for Q4">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Cancellation &amp; Refund Policy</label>
            <textarea name="cancellation_policy" id="editPkgCancellationPolicy" class="form-control" rows="2"></textarea>
          </div>

          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" id="editPkgIsActive" value="1">
            <label class="form-check-label small fw-semibold" for="editPkgIsActive">Package is Active &amp; Available for Registration</label>
          </div>
        </div>
        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="fa-solid fa-save me-1"></i> Update Visa Package</button>
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

<!-- Modal: Adjust Inventory / Log Transaction -->
<div class="modal fade" id="adjustInventoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-boxes-packing text-warning me-2"></i> Adjust Visa Package Inventory</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/visa-packages/inventory/adjust" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Visa Package <span class="text-danger">*</span></label>
            <select name="visa_service_id" id="adjPkgId" class="form-select" required>
              <option value="">-- Select Visa Package --</option>
              <?php foreach ($packages as $p): ?>
                <option value="<?= $p['id'] ?>">
                  <?= e($p['country_name'] ?? '') ?> — <?= e($p['name']) ?> (<?= e($p['currency'] ?? 'USD') ?> <?= number_format((float)$p['selling_price'], 2) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Action / Transaction Type <span class="text-danger">*</span></label>
            <select name="action_type" class="form-select" required>
              <option value="Stock Allocation">Stock Allocation (Supplier Slots Added)</option>
              <option value="Stock Reduction">Stock Reduction (Quota Reduced)</option>
              <option value="Manual Adjustment">Manual Audit Adjustment</option>
              <option value="Cancellation Return">Application Cancellation Return</option>
              <option value="Supplier Rate Revision">Supplier Rate Revision</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Effective Date</label>
            <input type="date" name="effective_date" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Audit Notes / Reason <span class="text-danger">*</span></label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Reason for inventory transaction or allocation..." required></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning px-4 fw-semibold"><i class="fa-solid fa-check me-1"></i> Save Transaction</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openAdjustInventoryModal(pkgId, pkgName) {
  if (pkgId && document.getElementById('adjPkgId')) {
    document.getElementById('adjPkgId').value = pkgId;
  }
  const modal = new bootstrap.Modal(document.getElementById('adjustInventoryModal'));
  modal.show();
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

function openEditPackageModal(pkg) {
  document.getElementById('editPkgId').value = pkg.id;
  document.getElementById('editPkgCountry').value = pkg.country_id;
  document.getElementById('editPkgCategory').value = pkg.category_id;
  document.getElementById('editPkgSupplier').value = pkg.supplier_id || '';
  document.getElementById('editPkgCurrency').value = pkg.currency || 'USD';
  document.getElementById('editPkgEffectiveDate').value = pkg.effective_date || '<?= date('Y-m-d') ?>';
  document.getElementById('editPkgName').value = pkg.name;
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
  document.getElementById('editPkgIsActive').checked = pkg.is_active == 1;

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
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
