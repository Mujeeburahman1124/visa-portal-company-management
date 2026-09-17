<?php
$pageTitle = 'Inventory Movement History — MS TRAVEL HUB';
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

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800 fw-bold">Stock Movement Audit Trail</h1>
        <p class="text-muted small mb-0">Immutable, chronological transaction log of all inventory movements and adjustments</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/inventory" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Inventory
        </a>
        <button class="btn btn-outline-success" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Ledger
        </button>
    </div>
</div>

<!-- Filter Form -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/inventory/history" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Item Filter</label>
                <select name="item_id" class="form-select form-select-sm">
                    <option value="">All Inventory Items</option>
                    <?php foreach ($items as $item): ?>
                        <option value="<?= $item['id'] ?>" <?= ($filters['item_id'] ?? '') == $item['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($item['name']) ?> (<?= htmlspecialchars($item['sku'] ?? 'No SKU') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Movement Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Movement Types</option>
                    <option value="STOCK_IN" <?= ($filters['type'] ?? '') === 'STOCK_IN' ? 'selected' : '' ?>>Stock In (+)</option>
                    <option value="STOCK_OUT" <?= ($filters['type'] ?? '') === 'STOCK_OUT' ? 'selected' : '' ?>>Stock Out (-)</option>
                    <option value="ADJUSTMENT" <?= ($filters['type'] ?? '') === 'ADJUSTMENT' ? 'selected' : '' ?>>Adjustment (=)</option>
                    <option value="TRANSFER" <?= ($filters['type'] ?? '') === 'TRANSFER' ? 'selected' : '' ?>>Branch Transfer</option>
                    <option value="PURCHASE" <?= ($filters['type'] ?? '') === 'PURCHASE' ? 'selected' : '' ?>>Supplier Purchase (+)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Date From</label>
                <input type="date" name="from_date" class="form-control form-select-sm" value="<?= htmlspecialchars($filters['from_date'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Date To</label>
                <input type="date" name="to_date" class="form-control form-select-sm" value="<?= htmlspecialchars($filters['to_date'] ?? '') ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                    <i class="bi bi-funnel me-1"></i> Apply Filter
                </button>
                <a href="/inventory/history" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- History Table Card -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="bi bi-clock-history me-1"></i> Historical Stock Transactions (<?= count($transactions) ?> Records)
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Date & Time</th>
                        <th>Reference ID</th>
                        <th>Item & SKU</th>
                        <th>Type</th>
                        <th class="text-center">Previous Qty</th>
                        <th class="text-center">Movement Qty</th>
                        <th class="text-center">New Balance</th>
                        <th>Unit Cost</th>
                        <th>Total Cost</th>
                        <th>Performed By / Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No inventory transactions found matching your criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $tx): ?>
                            <?php
                            $badgeClass = 'bg-secondary';
                            $prefix = '';
                            switch ($tx['transaction_type']) {
                                case 'STOCK_IN':
                                case 'PURCHASE':
                                    $badgeClass = 'bg-success';
                                    $prefix = '+';
                                    break;
                                case 'STOCK_OUT':
                                    $badgeClass = 'bg-danger';
                                    $prefix = '-';
                                    break;
                                case 'ADJUSTMENT':
                                    $badgeClass = 'bg-warning text-dark';
                                    $prefix = '±';
                                    break;
                                case 'TRANSFER':
                                    $badgeClass = 'bg-info text-dark';
                                    $prefix = '↔';
                                    break;
                            }
                            ?>
                            <tr>
                                <td class="ps-3 small text-muted">
                                    <?= date('Y-m-d H:i', strtotime($tx['created_at'])) ?>
                                </td>
                                <td>
                                    <code class="fw-semibold text-dark"><?= htmlspecialchars($tx['reference_no'] ?? ('TXN-' . $tx['id'])) ?></code>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($tx['item_name'] ?? 'Unknown Item') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($tx['sku'] ?? 'N/A') ?> &bull; <?= htmlspecialchars($tx['category_name'] ?? 'General') ?></small>
                                </td>
                                <td>
                                    <span class="badge <?= $badgeClass ?> px-2 py-1">
                                        <?= htmlspecialchars($tx['transaction_type']) ?>
                                    </span>
                                </td>
                                <td class="text-center text-muted fw-semibold">
                                    <?= number_format((float)($tx['quantity_before'] ?? 0), 2) ?>
                                </td>
                                <td class="text-center fw-bold <?= in_array($tx['transaction_type'], ['STOCK_IN', 'PURCHASE']) ? 'text-success' : ($tx['transaction_type'] === 'STOCK_OUT' ? 'text-danger' : 'text-primary') ?>">
                                    <?= $prefix ?><?= number_format(abs((float)($tx['quantity'] ?? 0)), 2) ?> <?= htmlspecialchars($tx['unit_of_measure'] ?? 'pcs') ?>
                                </td>
                                <td class="text-center fw-bold text-dark">
                                    <?= number_format((float)($tx['quantity_after'] ?? 0), 2) ?>
                                </td>
                                <td class="small">
                                    $<?= number_format((float)($tx['unit_cost'] ?? 0), 2) ?>
                                </td>
                                <td class="small fw-semibold">
                                    $<?= number_format((float)($tx['total_cost'] ?? 0), 2) ?>
                                </td>
                                <td class="small">
                                    <div><i class="bi bi-person me-1 text-muted"></i><?= htmlspecialchars($tx['created_by_name'] ?? 'System') ?></div>
                                    <?php if (!empty($tx['notes'])): ?>
                                        <div class="text-muted fst-italic mt-1" style="max-width: 200px;"><?= htmlspecialchars($tx['notes']) ?></div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>

