<?php
$pageTitle = 'Payment History Ledger — VISA TRACK';
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
      <h3 class="fw-bold brand-font text-dark mb-1">Financial Payment History Ledger</h3>
      <p class="text-muted small mb-0">Audited chronological register of all customer payments, digital wallet debits, and online settlements.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="/payments" class="btn btn-outline-secondary btn-sm bg-white shadow-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Payment Hub
      </a>
      <a href="/payments/links" class="btn btn-outline-primary btn-sm bg-white shadow-sm">
        <i class="fa-solid fa-link me-1"></i> Payment Links
      </a>
      <a href="/payments/export-csv" class="btn btn-outline-success btn-sm bg-white shadow-sm">
        <i class="fa-solid fa-file-excel me-1"></i> Export Ledger
      </a>
    </div>
  </div>

  <!-- KPI Metrics Row -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card card-enterprise shadow-sm border p-3">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted small fw-semibold text-uppercase">Total Settled Collections</div>
            <h3 class="fw-bold text-success mb-0">$<?= number_format((float)$totalCollected, 2) ?></h3>
          </div>
          <div class="rounded-circle bg-success bg-opacity-10 p-3 text-success">
            <i class="fa-solid fa-vault fs-4"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card card-enterprise shadow-sm border p-3">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted small fw-semibold text-uppercase">Processed Transactions</div>
            <h3 class="fw-bold text-primary mb-0"><?= number_format($totalTransactions) ?></h3>
          </div>
          <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary">
            <i class="fa-solid fa-receipt fs-4"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card card-enterprise shadow-sm border p-3">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted small fw-semibold text-uppercase">Average Payment Size</div>
            <h3 class="fw-bold text-dark mb-0">
              $<?= $totalTransactions > 0 ? number_format((float)($totalCollected / $totalTransactions), 2) : '0.00' ?>
            </h3>
          </div>
          <div class="rounded-circle bg-info bg-opacity-10 p-3 text-info">
            <i class="fa-solid fa-chart-line fs-4"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Multi-Criteria Search & Filter Form -->
  <div class="card card-enterprise mb-4 shadow-sm border">
    <div class="card-body p-3">
      <form method="GET" action="/payments/history" class="row g-2">
        <div class="col-lg-3 col-md-6">
          <label class="form-label small text-muted mb-1">Search Keywords</label>
          <input type="text" name="search" class="form-control form-control-sm" placeholder="Receipt #, Payer, App #, Passport, Ref..." value="<?= e($search ?? '') ?>">
        </div>
        <div class="col-lg-2 col-md-3">
          <label class="form-label small text-muted mb-1">Payment Method</label>
          <select name="method" class="form-select form-select-sm">
            <option value="">-- All Methods --</option>
            <option value="Customer Wallet" <?= ($method ?? '') === 'Customer Wallet' ? 'selected' : '' ?>>Customer Wallet</option>
            <option value="Online (Stripe)" <?= ($method ?? '') === 'Online (Stripe)' ? 'selected' : '' ?>>Online (Stripe)</option>
            <option value="Cash" <?= ($method ?? '') === 'Cash' ? 'selected' : '' ?>>Cash</option>
            <option value="Bank Transfer" <?= ($method ?? '') === 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
            <option value="Credit Card" <?= ($method ?? '') === 'Credit Card' ? 'selected' : '' ?>>POS Card</option>
          </select>
        </div>
        <div class="col-lg-2 col-md-3">
          <label class="form-label small text-muted mb-1">Settlement Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">-- All Statuses --</option>
            <option value="Completed" <?= ($status ?? '') === 'Completed' ? 'selected' : '' ?>>Completed</option>
            <option value="Pending" <?= ($status ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Refunded" <?= ($status ?? '') === 'Refunded' ? 'selected' : '' ?>>Refunded</option>
          </select>
        </div>
        <div class="col-lg-2 col-md-3">
          <label class="form-label small text-muted mb-1">Date From</label>
          <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom ?? '') ?>">
        </div>
        <div class="col-lg-2 col-md-3">
          <label class="form-label small text-muted mb-1">Date To</label>
          <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo ?? '') ?>">
        </div>
        <div class="col-lg-1 col-md-12 d-flex align-items-end gap-1">
          <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold" title="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
          <a href="/payments/history" class="btn btn-outline-secondary btn-sm" title="Clear Filters"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
      </form>
    </div>
  </div>

  <!-- Historical Transactions Table -->
  <div class="card card-enterprise shadow-sm border">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
      <span class="fw-bold text-dark">
        <i class="fa-solid fa-list-check text-primary me-2"></i> All Recorded Transactions (<?= count($payments) ?> Records)
      </span>
      <span class="badge bg-light text-muted border">Real-time DB Sync</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="bg-light text-muted small text-uppercase">
          <tr>
            <th class="ps-3">Date &amp; Receipt #</th>
            <th>Payer &amp; Applicant</th>
            <th>Application Ref &amp; Visa</th>
            <th>Method</th>
            <th>Transaction Ref</th>
            <th>Received By</th>
            <th class="text-end">Amount</th>
            <th class="text-center pe-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($payments)): ?>
            <tr>
              <td colspan="8" class="text-center py-5 text-muted">
                <i class="fa-solid fa-folder-open fs-1 text-secondary mb-2 d-block opacity-50"></i>
                No historical payment transactions match your query criteria.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($payments as $p): ?>
              <tr>
                <td class="ps-3">
                  <div class="fw-bold font-monospace text-primary" style="font-size: 0.85rem;"><?= e($p['payment_number']) ?></div>
                  <div class="text-muted small" style="font-size: 0.72rem;">
                    <i class="fa-regular fa-clock me-1"></i><?= date('d M Y', strtotime($p['payment_date'] ?? $p['created_at'])) ?>
                  </div>
                </td>
                <td>
                  <div class="fw-semibold text-dark"><?= e($p['customer_name']) ?></div>
                  <div class="text-muted small" style="font-size: 0.72rem;">
                    <span class="badge bg-light text-dark border px-1.5 py-0"><?= e($p['customer_code']) ?></span>
                    &bull; <?= e($p['customer_mobile'] ?: 'No Phone') ?>
                  </div>
                </td>
                <td>
                  <a href="/applications/show?id=<?= $p['app_id'] ?>" class="fw-bold font-monospace text-decoration-none text-dark">
                    <?= e($p['application_number']) ?>
                  </a>
                  <div class="text-muted small" style="font-size: 0.72rem;">
                    <?= $p['flag_emoji'] ?> <?= e($p['country_name']) ?> &mdash; <?= e($p['service_name']) ?>
                  </div>
                </td>
                <td>
                  <?php if ($p['payment_method'] === 'Customer Wallet'): ?>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold">
                      <i class="fa-solid fa-wallet me-1"></i> Wallet
                    </span>
                  <?php elseif (str_contains(strtolower($p['payment_method']), 'stripe') || str_contains(strtolower($p['payment_method']), 'online')): ?>
                    <span class="badge bg-info-subtle text-info border border-info-subtle fw-semibold">
                      <i class="fa-brands fa-stripe me-1"></i> Stripe Online
                    </span>
                  <?php elseif ($p['payment_method'] === 'Cash'): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">
                      <i class="fa-solid fa-money-bill me-1"></i> Cash
                    </span>
                  <?php else: ?>
                    <span class="badge bg-light text-dark border fw-semibold">
                      <?= e($p['payment_method']) ?>
                    </span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="font-monospace small text-muted"><?= e($p['transaction_reference'] ?: '&mdash;') ?></span>
                </td>
                <td>
                  <span class="small text-muted"><?= e($p['received_by_name'] ?: 'Online System') ?></span>
                </td>
                <td class="text-end">
                  <div class="fw-bold text-success fs-6">$<?= number_format((float)$p['amount'], 2) ?></div>
                  <span class="badge <?= $p['status'] === 'Completed' ? 'bg-success' : 'bg-warning text-dark' ?>" style="font-size: 0.65rem;">
                    <?= e($p['status']) ?>
                  </span>
                </td>
                <td class="text-center pe-3">
                  <a href="/payments/receipt?id=<?= $p['id'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm px-2 py-1" title="View &amp; Print Official Receipt">
                    <i class="fa-solid fa-receipt"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
