<?php
$pageTitle = 'Supplier Payment Ledger & History — MS TRAVEL HUB';
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
        <h1 class="h3 mb-1 text-gray-800 fw-bold">Supplier Disbursements & Payment History</h1>
        <p class="text-muted small mb-0">Track all supplier disbursements linked to visa applications, applicants, and direct company expenses</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/suppliers" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Supplier Directory
        </a>
        <button class="btn btn-outline-primary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Ledger
        </button>
    </div>
</div>

<!-- Summary Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm border-start border-primary border-4 p-3">
            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Disbursements Recorded</div>
            <div class="h4 mb-0 fw-bold text-gray-800">$<?= number_format((float)($totals['total_paid'] ?? 0), 2) ?></div>
            <div class="text-muted small mt-1">Sum of filtered payments</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm border-start border-info border-4 p-3">
            <div class="text-xs fw-bold text-info text-uppercase mb-1">Transaction Count</div>
            <div class="h4 mb-0 fw-bold text-gray-800"><?= number_format((int)($totals['count'] ?? count($payments))) ?></div>
            <div class="text-muted small mt-1">Payment transactions</div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/suppliers/payments" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Supplier</label>
                <select name="supplier_id" class="form-select form-select-sm">
                    <option value="">All Suppliers</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($filters['supplier_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Date From</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['from_date'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Date To</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['to_date'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Payment Method</label>
                <select name="payment_method" class="form-select form-select-sm">
                    <option value="">All Methods</option>
                    <option value="Cash" <?= ($filters['payment_method'] ?? '') === 'Cash' ? 'selected' : '' ?>>Cash</option>
                    <option value="Bank Transfer" <?= ($filters['payment_method'] ?? '') === 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                    <option value="Cheque" <?= ($filters['payment_method'] ?? '') === 'Cheque' ? 'selected' : '' ?>>Cheque</option>
                    <option value="Online / Card" <?= ($filters['payment_method'] ?? '') === 'Online / Card' ? 'selected' : '' ?>>Online / Card</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="/suppliers/payments" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Payments Table Card -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="bi bi-receipt-cutoff me-1"></i> Supplier Payment Transactions
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Payment Date</th>
                        <th>Payment Reference</th>
                        <th>Supplier</th>
                        <th>Application & Applicant</th>
                        <th>Amount Paid</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No supplier payment records found matching the filter criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td class="ps-3 small text-muted">
                                    <?= htmlspecialchars($p['payment_date'] ?? date('Y-m-d', strtotime($p['created_at'] ?? 'now'))) ?>
                                </td>
                                <td>
                                    <code class="fw-semibold text-dark"><?= htmlspecialchars($p['reference_number'] ?? ('PAY-' . $p['id'])) ?></code>
                                    <?php if (!empty($p['supplier_invoice_ref'])): ?>
                                        <div class="text-muted small">Inv: <?= htmlspecialchars($p['supplier_invoice_ref']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($p['supplier_name'] ?? 'Supplier #' . $p['supplier_id']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($p['supplier_phone'] ?? '') ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($p['application_id'])): ?>
                                        <div>
                                            <a href="/applications/view?id=<?= $p['application_id'] ?>" class="text-decoration-none fw-semibold">
                                                <i class="bi bi-folder2 me-1"></i>App #<?= $p['application_id'] ?>
                                            </a>
                                        </div>
                                        <?php if (!empty($p['applicant_name'])): ?>
                                            <small class="text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars($p['applicant_name']) ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic small">General / Direct Purchase</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold text-success">
                                    $<?= number_format((float)($p['amount'] ?? 0), 2) ?>
                                    <span class="text-muted small fw-normal"><?= htmlspecialchars($p['currency'] ?? 'USD') ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars($p['payment_method'] ?? 'Bank Transfer') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <?= htmlspecialchars($p['payment_status'] ?? 'Completed') ?>
                                    </span>
                                </td>
                                <td class="small">
                                    <div><i class="bi bi-person me-1 text-muted"></i><?= htmlspecialchars($p['created_by_name'] ?? 'System') ?></div>
                                    <?php if (!empty($p['notes'])): ?>
                                        <div class="text-muted fst-italic" style="max-width: 180px;"><?= htmlspecialchars($p['notes']) ?></div>
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

