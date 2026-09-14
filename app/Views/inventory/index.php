<?php
$pageTitle = 'Consular & Operations Inventory Management — MS TRAVEL HUB';
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

  <!-- Header & Operations Button Toolbar -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <h3 class="fw-bold brand-font text-dark mb-1">Consular &amp; Operations Inventory Management</h3>
      <p class="text-muted small mb-0">Track visa application stationery, holographic seals, branded passport covers, courier bags &amp; supplier purchase orders.</p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
      <a href="/inventory/history" class="btn btn-outline-primary btn-sm px-3 shadow-sm">
        <i class="fa-solid fa-clock-rotate-left me-1"></i> Stock Ledger
      </a>
      <button class="btn btn-info text-white btn-sm px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#purchaseFromSupplierModal">
        <i class="fa-solid fa-cart-shopping me-1"></i> Supplier Purchase
      </button>
      <button class="btn btn-warning text-dark btn-sm px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#transferStockModal">
        <i class="fa-solid fa-truck-ramp-box me-1"></i> Transfer
      </button>
      <button class="btn btn-primary btn-sm px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#newItemModal">
        <i class="fa-solid fa-plus me-1"></i> Register Item
      </button>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card card-enterprise h-100 shadow-sm border-0 border-start border-4 border-primary">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small text-uppercase fw-semibold">Registered Items</span>
            <div class="rounded-circle bg-primary-subtle p-2 text-primary"><i class="fa-solid fa-boxes-stacked"></i></div>
          </div>
          <h3 class="fw-bold text-dark mb-0"><?= $totalItems ?></h3>
          <div class="text-muted small mt-1">Total Stock: <strong class="text-primary"><?= number_format($totalUnits) ?> Units</strong></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card card-enterprise h-100 shadow-sm border-0 border-start border-4 border-success">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small text-uppercase fw-semibold">Inventory Valuation</span>
            <div class="rounded-circle bg-success-subtle p-2 text-success"><i class="fa-solid fa-vault"></i></div>
          </div>
          <h3 class="fw-bold text-success mb-0">AED <?= number_format($totalValuation, 2) ?></h3>
          <div class="text-muted small mt-1">At Purchase Cost</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card card-enterprise h-100 shadow-sm border-0 border-start border-4 <?= $lowStockCount > 0 ? 'border-danger' : 'border-info' ?>">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small text-uppercase fw-semibold">Low Stock Alerts</span>
            <div class="rounded-circle <?= $lowStockCount > 0 ? 'bg-danger-subtle text-danger' : 'bg-info-subtle text-info' ?> p-2"><i class="fa-solid fa-triangle-exclamation"></i></div>
          </div>
          <h3 class="fw-bold <?= $lowStockCount > 0 ? 'text-danger' : 'text-dark' ?> mb-0"><?= $lowStockCount ?></h3>
          <div class="text-muted small mt-1"><?= $lowStockCount > 0 ? 'Requires immediate reorder' : 'All items well stocked' ?></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card card-enterprise h-100 shadow-sm border-0 border-start border-4 border-secondary">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small text-uppercase fw-semibold">Audit Ledger Records</span>
            <div class="rounded-circle bg-secondary-subtle p-2 text-secondary"><i class="fa-solid fa-receipt"></i></div>
          </div>
          <h3 class="fw-bold text-dark mb-0"><?= $totalTransactionsCount ?></h3>
          <div class="text-muted small mt-1">Immutable Stock Movements</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter Bar -->
  <div class="card card-enterprise shadow-sm mb-4">
    <div class="card-body p-3">
      <form method="GET" action="/inventory" class="row g-2 align-items-center">
        <div class="col-12 col-md-3">
          <label class="form-label small fw-bold text-muted mb-1">Search Items</label>
          <input type="text" name="search" class="form-control form-control-sm" placeholder="SKU, item name, shelf..." value="<?= e($search) ?>">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label small fw-bold text-muted mb-1">Category</label>
          <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>" <?= $categoryId == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-md-2">
          <label class="form-label small fw-bold text-muted mb-1">Supplier</label>
          <select name="supplier_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Suppliers</option>
            <?php foreach ($suppliers as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $supplierId == $s['id'] ? 'selected' : '' ?>><?= e($s['company_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-md-2">
          <label class="form-label small fw-bold text-muted mb-1">Stock Status</label>
          <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Levels</option>
            <option value="In Stock" <?= $status === 'In Stock' ? 'selected' : '' ?>>In Stock</option>
            <option value="Low Stock" <?= $status === 'Low Stock' ? 'selected' : '' ?>>Low Stock</option>
            <option value="Out of Stock" <?= $status === 'Out of Stock' ? 'selected' : '' ?>>Out of Stock</option>
          </select>
        </div>
        <div class="col-12 col-md-2 d-flex align-items-end gap-1 mt-auto">
          <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="fa-solid fa-magnifying-glass me-1"></i> Filter</button>
          <a href="/inventory" class="btn btn-light border btn-sm" title="Reset Filters"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
      </form>
    </div>
  </div>

  <!-- Inventory Data Table -->
  <div class="card card-enterprise shadow-sm">
    <div class="table-responsive">
      <table class="table-modern mb-0">
        <thead>
          <tr>
            <th style="min-width: 130px;">Item SKU</th>
            <th style="min-width: 220px;">Item Name &amp; Category</th>
            <th style="min-width: 150px;">Supplier / Partner</th>
            <th style="min-width: 120px;">Location / Unit</th>
            <th style="min-width: 120px;">Current Stock</th>
            <th style="min-width: 110px;">Purchase Cost</th>
            <th style="min-width: 120px;">Valuation</th>
            <th style="min-width: 110px;">Status</th>
            <th class="text-end" style="min-width: 180px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($items)): ?>
            <tr><td colspan="9" class="text-center py-5 text-muted">No inventory items found matching filters.</td></tr>
          <?php else: ?>
            <?php foreach ($items as $item): ?>
              <?php
                $currStock = (int)$item['current_stock'];
                $minStock = (int)$item['minimum_stock'];
                $isLow = $currStock <= $minStock;
                $isOut = $currStock <= 0;
                $val = $currStock * (float)$item['purchase_price'];
              ?>
              <tr>
                <td>
                  <span class="badge bg-light text-dark border font-monospace px-2 py-1"><?= e($item['item_code']) ?></span>
                </td>
                <td>
                  <div class="fw-bold text-dark fs-6"><?= e($item['name']) ?></div>
                  <div class="text-muted small"><i class="fa-solid fa-tag me-1 text-primary"></i><?= e($item['category_name']) ?></div>
                </td>
                <td>
                  <div class="fw-semibold small text-dark"><?= e($item['supplier_name'] ?: 'Internal / Consular Desk') ?></div>
                  <?php if (!empty($item['supplier_code'])): ?>
                    <span class="badge bg-secondary-subtle text-secondary font-monospace" style="font-size: 0.68rem;"><?= e($item['supplier_code']) ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="small fw-semibold text-dark"><?= e($item['location'] ?: 'Main Store') ?></div>
                  <div class="text-muted small">Unit: <?= e($item['unit']) ?></div>
                </td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold fs-6 font-monospace <?= $isOut ? 'text-danger' : ($isLow ? 'text-warning' : 'text-dark') ?>"><?= number_format($currStock) ?></span>
                    <span class="text-muted small" style="font-size: 0.72rem;">(Min: <?= $minStock ?>)</span>
                  </div>
                </td>
                <td>
                  <span class="font-monospace text-muted small"><?= e($item['currency']) ?> <?= number_format((float)$item['purchase_price'], 2) ?></span>
                </td>
                <td>
                  <span class="font-monospace fw-bold text-dark"><?= e($item['currency']) ?> <?= number_format($val, 2) ?></span>
                </td>
                <td>
                  <?php if ($isOut): ?>
                    <span class="badge bg-danger px-2 py-1"><i class="fa-solid fa-circle-xmark me-1"></i>Out of Stock</span>
                  <?php elseif ($isLow): ?>
                    <span class="badge bg-warning text-dark px-2 py-1"><i class="fa-solid fa-triangle-exclamation me-1"></i>Low Stock</span>
                  <?php else: ?>
                    <span class="badge bg-success px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i>In Stock</span>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <div class="d-inline-flex align-items-center gap-1">
                    <button type="button" class="btn btn-sm btn-outline-success py-1 px-2" title="Stock-In (+)" onclick="openStockInModal(<?= htmlspecialchars(json_encode($item)) ?>)">
                      <i class="fa-solid fa-plus"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2" title="Stock-Out (-)" onclick="openStockOutModal(<?= htmlspecialchars(json_encode($item)) ?>)">
                      <i class="fa-solid fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning py-1 px-2" title="Adjust Stock" onclick="openAdjustModal(<?= htmlspecialchars(json_encode($item)) ?>)">
                      <i class="fa-solid fa-sliders"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" title="Edit Item" onclick="openEditItemModal(<?= htmlspecialchars(json_encode($item)) ?>)">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODAL: REGISTER NEW ITEM -->
<div class="modal fade" id="newItemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle me-2"></i> Register New Inventory Item</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/inventory/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4 text-start">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label small fw-bold">Item SKU Code <span class="text-danger">*</span></label>
              <input type="text" name="item_code" class="form-control font-monospace" placeholder="e.g. SKU-VAF-009" required>
            </div>
            <div class="col-md-8">
              <label class="form-label small fw-bold">Item Description / Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Schengen Visa Application Dossier" required>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-bold">Category</label>
              <select name="category_id" class="form-select" required>
                <?php foreach ($categories as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Primary Supplier</label>
              <select name="supplier_id" class="form-select">
                <option value="">None / Internal</option>
                <?php foreach ($suppliers as $s): ?>
                  <option value="<?= $s['id'] ?>"><?= e($s['company_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label small fw-bold">Unit of Measure</label>
              <input type="text" name="unit" class="form-control" value="Pcs" placeholder="Pcs, Box, Roll, Pack">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold">Opening Stock</label>
              <input type="number" name="opening_stock" class="form-control" value="100" min="0">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold">Minimum Stock Alert</label>
              <input type="number" name="minimum_stock" class="form-control" value="20" min="1">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold">Unit Purchase Cost (AED)</label>
              <input type="number" step="0.01" name="purchase_price" class="form-control font-monospace" value="5.00" min="0">
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-bold">Storage Location / Bin</label>
              <input type="text" name="location" class="form-control" placeholder="e.g. Shelf A-3, Main Vault">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Branch</label>
              <select name="branch_id" class="form-select">
                <?php foreach ($branches as $b): ?>
                  <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label small fw-bold">Notes / Specifications</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light p-3">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold"><i class="fa-solid fa-save me-1"></i> Register Item</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: STOCK-IN (+) -->
<div class="modal fade" id="stockInModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle me-2"></i> Record Stock-In Intake</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/inventory/stock-in" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="item_id" id="stockInItemId">
        <div class="modal-body p-4 text-start">
          <div class="p-3 bg-light rounded border mb-3">
            <div class="small text-muted">Selected Item:</div>
            <div class="fw-bold fs-6 text-dark" id="stockInItemName">—</div>
            <div class="small text-muted">Current Stock: <strong class="text-primary font-monospace" id="stockInCurrentStock">0</strong></div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-bold">Intake Quantity (+)</label>
              <input type="number" name="quantity" class="form-control font-monospace fw-bold text-success" value="50" min="1" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-bold">Unit Purchase Price</label>
              <input type="number" step="0.01" name="unit_price" id="stockInUnitPrice" class="form-control font-monospace" value="0.00">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Supplier Invoice / Batch Ref</label>
            <input type="text" name="supplier_invoice_ref" class="form-control font-monospace" placeholder="e.g. SUP-INV-9901">
          </div>
          <div class="mb-2">
            <label class="form-label small fw-bold">Reason / Purpose</label>
            <input type="text" name="reason_notes" class="form-control" value="Regular stock replenishment" required>
          </div>
        </div>
        <div class="modal-footer bg-light p-3">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success btn-sm fw-bold"><i class="fa-solid fa-check me-1"></i> Commit Stock-In</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: STOCK-OUT (-) -->
<div class="modal fade" id="stockOutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-minus-circle me-2"></i> Record Stock-Out Issue</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/inventory/stock-out" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="item_id" id="stockOutItemId">
        <div class="modal-body p-4 text-start">
          <div class="p-3 bg-light rounded border mb-3">
            <div class="small text-muted">Selected Item:</div>
            <div class="fw-bold fs-6 text-dark" id="stockOutItemName">—</div>
            <div class="small text-muted">Available Stock: <strong class="text-danger font-monospace" id="stockOutCurrentStock">0</strong></div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Dispatch Quantity (-)</label>
            <input type="number" name="quantity" class="form-control font-monospace fw-bold text-danger" value="1" min="1" required>
          </div>
          <div class="mb-2">
            <label class="form-label small fw-bold">Reason / Application Reference</label>
            <input type="text" name="reason_notes" class="form-control" placeholder="e.g. Issued for application MSV-2026-0001" required>
          </div>
        </div>
        <div class="modal-footer bg-light p-3">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger btn-sm fw-bold"><i class="fa-solid fa-check me-1"></i> Commit Stock-Out</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: ADJUST STOCK -->
<div class="modal fade" id="adjustModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-warning text-dark">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-sliders me-2"></i> Controlled Stock Adjustment</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/inventory/adjust" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="item_id" id="adjustItemId">
        <div class="modal-body p-4 text-start">
          <div class="p-3 bg-light rounded border mb-3">
            <div class="small text-muted">Selected Item:</div>
            <div class="fw-bold fs-6 text-dark" id="adjustItemName">—</div>
            <div class="small text-muted">Current System Stock: <strong class="text-dark font-monospace" id="adjustCurrentStock">0</strong></div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">New Physical Count / Verified Quantity</label>
            <input type="number" name="new_stock" id="adjustNewStock" class="form-control font-monospace fw-bold" min="0" required>
          </div>
          <div class="mb-2">
            <label class="form-label small fw-bold">Mandatory Audit Justification / Reason</label>
            <textarea name="reason_notes" class="form-control" rows="3" placeholder="e.g. Annual physical count variance, damaged in storage, consular disposal..." required></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light p-3">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning btn-sm fw-bold"><i class="fa-solid fa-save me-1"></i> Save Adjustment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: TRANSFER STOCK -->
<div class="modal fade" id="transferStockModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-warning text-dark">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-truck-ramp-box me-2"></i> Inter-Branch Stock Transfer</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/inventory/transfer" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4 text-start">
          <div class="mb-3">
            <label class="form-label small fw-bold">Inventory Item</label>
            <select name="item_id" class="form-select" required>
              <option value="">Select Item...</option>
              <?php foreach ($items as $it): ?>
                <option value="<?= $it['id'] ?>"><?= e($it['name']) ?> (Stock: <?= $it['current_stock'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-bold">Source Branch</label>
              <select name="source_branch_id" class="form-select">
                <?php foreach ($branches as $b): ?>
                  <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-bold">Destination Branch</label>
              <select name="destination_branch_id" class="form-select">
                <?php foreach ($branches as $b): ?>
                  <option value="<?= $b['id'] ?>" <?= $b['id'] == 2 ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Quantity to Transfer</label>
            <input type="number" name="quantity" class="form-control" value="20" min="1" required>
          </div>
          <div class="mb-2">
            <label class="form-label small fw-bold">Transfer Reason / Dispatch Note</label>
            <input type="text" name="reason_notes" class="form-control" value="Branch replenishment" required>
          </div>
        </div>
        <div class="modal-footer bg-light p-3">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning btn-sm fw-bold"><i class="fa-solid fa-arrow-right-arrow-left me-1"></i> Transfer Stock</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: SUPPLIER PURCHASE (FINANCIAL INTEGRATION) -->
<div class="modal fade" id="purchaseFromSupplierModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-cart-shopping me-2"></i> Purchase Inventory from Supplier</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/inventory/purchase" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4 text-start">
          <div class="alert alert-info border-0 shadow-sm small mb-3">
            <i class="fa-solid fa-info-circle me-1"></i> This order will automatically increment stock, write an immutable transaction record, and update the <strong>Supplier Financial Ledger (Payables &amp; Balance)</strong>.
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Supplier / Vendor <span class="text-danger">*</span></label>
              <select name="supplier_id" class="form-select" required>
                <option value="">Select Supplier...</option>
                <?php foreach ($suppliers as $s): ?>
                  <option value="<?= $s['id'] ?>"><?= e($s['company_name']) ?> (<?= e($s['supplier_code']) ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Inventory Item <span class="text-danger">*</span></label>
              <select name="item_id" class="form-select" required>
                <option value="">Select Item...</option>
                <?php foreach ($items as $it): ?>
                  <option value="<?= $it['id'] ?>"><?= e($it['name']) ?> (SKU: <?= e($it['item_code']) ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label small fw-bold">Quantity Ordered</label>
              <input type="number" name="quantity" class="form-control font-monospace fw-bold" value="100" min="1" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">Unit Purchase Price (AED)</label>
              <input type="number" step="0.01" name="purchase_price" class="form-control font-monospace fw-bold" value="12.00" min="0.01" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">Supplier Invoice #</label>
              <input type="text" name="supplier_invoice_ref" class="form-control font-monospace" placeholder="e.g. SUP-INV-<?= date('Ymd') ?>-101" required>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-bold">Purchase Date</label>
              <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Payment Terms</label>
              <select name="payment_status" class="form-select">
                <option value="Pending">Payable on Credit (Pending)</option>
                <option value="Paid">Immediate Settlement (Paid)</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label small fw-bold">Purchase Order Notes</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Consular supplies batch order..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light p-3">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-info text-white btn-sm px-4 fw-bold"><i class="fa-solid fa-check me-1"></i> Confirm Supplier Purchase Order</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openStockInModal(item) {
  document.getElementById('stockInItemId').value = item.id;
  document.getElementById('stockInItemName').textContent = item.name + ' (' + item.item_code + ')';
  document.getElementById('stockInCurrentStock').textContent = item.current_stock + ' ' + item.unit;
  document.getElementById('stockInUnitPrice').value = parseFloat(item.purchase_price || 0).toFixed(2);
  new bootstrap.Modal(document.getElementById('stockInModal')).show();
}

function openStockOutModal(item) {
  document.getElementById('stockOutItemId').value = item.id;
  document.getElementById('stockOutItemName').textContent = item.name + ' (' + item.item_code + ')';
  document.getElementById('stockOutCurrentStock').textContent = item.current_stock + ' ' + item.unit;
  new bootstrap.Modal(document.getElementById('stockOutModal')).show();
}

function openAdjustModal(item) {
  document.getElementById('adjustItemId').value = item.id;
  document.getElementById('adjustItemName').textContent = item.name + ' (' + item.item_code + ')';
  document.getElementById('adjustCurrentStock').textContent = item.current_stock + ' ' + item.unit;
  document.getElementById('adjustNewStock').value = item.current_stock;
  new bootstrap.Modal(document.getElementById('adjustModal')).show();
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
