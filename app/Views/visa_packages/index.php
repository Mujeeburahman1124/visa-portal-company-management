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
      <h3 class="fw-bold brand-font text-dark mb-0">Visa Services &amp; Packages Hub</h3>
      <p class="text-muted small mb-0">Manage global visa packages, destination country rules, categories, supplier costs, service fees &amp; manual package creation.</p>
    </div>
    <div class="d-flex gap-2">
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
          <div class="col-md-4">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
              <input type="text" name="search" class="form-control" placeholder="Search package name, duration..." value="<?= e($_GET['search'] ?? '') ?>">
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
          <div class="col-md-3">
            <select name="category_id" class="form-select form-select-sm">
              <option value="">-- All Categories --</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= ((int)($_GET['category_id'] ?? 0) === (int)$cat['id']) ? 'selected' : '' ?>>
                  <?= e($cat['name']) ?>
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
              <th>Duration</th>
              <th>Entry &amp; Processing</th>
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
                <tr>
                  <td>
                    <div class="fw-bold text-dark"><?= e($pkg['name']) ?></div>
                    <div class="text-muted small">Est. <?= (int)$pkg['estimated_days'] ?> Days &bull; Validity: <?= e($pkg['validity'] ?: 'N/A') ?></div>
                  </td>
                  <td>
                    <span class="fw-semibold"><?= $pkg['flag_emoji'] ?? '🌐' ?> <?= e($pkg['country_name']) ?></span>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border"><?= e($pkg['category_name']) ?></span>
                  </td>
                  <td>
                    <span class="fw-semibold text-dark"><?= e($pkg['duration']) ?></span>
                  </td>
                  <td>
                    <div class="small fw-semibold"><?= e($pkg['entry_type'] ?: 'Single Entry') ?></div>
                    <div class="text-muted small"><?= e($pkg['processing_type'] ?: 'Normal') ?></div>
                  </td>
                  <td>
                    <div class="text-muted small">Cost: $<?= number_format((float)$pkg['supplier_cost'], 2) ?></div>
                    <div class="text-muted small">Fee: $<?= number_format((float)$pkg['service_fee'], 2) ?></div>
                  </td>
                  <td>
                    <span class="fw-bold text-primary fs-6">$<?= number_format((float)$pkg['selling_price'], 2) ?></span>
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
          <h6 class="fw-bold text-dark mb-3" style="font-size: 0.88rem;">Financial Pricing &amp; Fees</h6>

          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Supplier Cost ($)</label>
              <input type="number" step="0.01" name="supplier_cost" id="pkgSupplierCost" class="form-control" value="100.00" oninput="calcSellingPrice('create')">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Service Fee ($)</label>
              <input type="number" step="0.01" name="service_fee" id="pkgServiceFee" class="form-control" value="50.00" oninput="calcSellingPrice('create')">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Tax / VAT Rate (%)</label>
              <input type="number" step="0.01" name="tax_rate" id="pkgTaxRate" class="form-control" value="5.00" oninput="calcSellingPrice('create')">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Final Selling Price ($) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="selling_price" id="pkgSellingPrice" class="form-control fw-bold text-primary" value="157.50" required>
            </div>
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
          <h6 class="fw-bold text-dark mb-3" style="font-size: 0.88rem;">Financial Pricing &amp; Fees</h6>

          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Supplier Cost ($)</label>
              <input type="number" step="0.01" name="supplier_cost" id="editPkgSupplierCost" class="form-control" oninput="calcSellingPrice('edit')">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Service Fee ($)</label>
              <input type="number" step="0.01" name="service_fee" id="editPkgServiceFee" class="form-control" oninput="calcSellingPrice('edit')">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Tax / VAT Rate (%)</label>
              <input type="number" step="0.01" name="tax_rate" id="editPkgTaxRate" class="form-control" oninput="calcSellingPrice('edit')">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Final Selling Price ($) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="selling_price" id="editPkgSellingPrice" class="form-control fw-bold text-primary" required>
            </div>
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
  document.getElementById('editPkgCancellationPolicy').value = pkg.cancellation_policy || '';
  document.getElementById('editPkgIsActive').checked = pkg.is_active == 1;

  const modal = new bootstrap.Modal(document.getElementById('editPackageModal'));
  modal.show();
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
