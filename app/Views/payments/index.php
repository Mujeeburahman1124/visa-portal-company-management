<?php
$pageTitle = 'Payments, Invoices & Online Links — VISA TRACK';
$flash = get_flash();
$currentUser = auth_user();
$isSuperAdmin = ($currentUser['role_slug'] ?? '') === 'super-admin' || (int)($currentUser['role_id'] ?? 0) === 1;
$canDeletePayment = $isSuperAdmin || user_can('payments.delete') || user_can('payments.manage') || user_can('finance.manage');
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
?>

<div class="content-body">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
      <?= e($flash['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
      <h3 class="fw-bold brand-font mb-1">Customer Payments, Invoices &amp; Links</h3>
      <p class="text-muted small mb-0">End-to-end payment tracking: Customer &rarr; Supplier &rarr; Applicant &rarr; Passport &rarr; Visa Type &rarr; Invoice &rarr; Wallet.</p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <!-- 3 View Mode Switcher -->
      <div class="btn-group btn-group-sm bg-white shadow-sm border rounded-pill p-1 payment-view-switcher" role="group" aria-label="Payment View Mode">
        <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 fw-semibold payment-view-btn btn-primary shadow-sm" id="btnPaymentViewTable" onclick="switchPaymentView('table')" title="Tabular View">
          <i class="fa-solid fa-table-list me-1"></i> <span>Table</span>
        </button>
        <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 fw-semibold payment-view-btn btn-light text-muted" id="btnPaymentViewGrid" onclick="switchPaymentView('grid')" title="Card Grid View">
          <i class="fa-solid fa-grip me-1"></i> <span>Cards</span>
        </button>
        <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 fw-semibold payment-view-btn btn-light text-muted" id="btnPaymentViewCompact" onclick="switchPaymentView('compact')" title="Compact List View">
          <i class="fa-solid fa-bars me-1"></i> <span>Compact</span>
        </button>
      </div>

      <a href="/payments/history" class="btn btn-outline-dark btn-sm px-3 fw-semibold shadow-sm">
        <i class="fa-solid fa-clock-rotate-left me-1"></i> History
      </a>
      <a href="/payments/links" class="btn btn-outline-info btn-sm px-3 fw-semibold shadow-sm">
        <i class="fa-solid fa-list-check me-1"></i> Links (<?= $metrics['total_online_links'] ?>)
      </a>
      <button type="button" class="btn btn-outline-primary btn-sm px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#generateLinkModal">
        <i class="fa-solid fa-link me-1"></i> Generate Link
      </button>
      <button type="button" class="btn btn-success btn-sm px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
        <i class="fa-solid fa-credit-card me-1"></i> Record Payment
      </button>
    </div>
  </div>

  <!-- Financial KPI Metrics (100% Responsive Grid) -->
  <div class="row g-2 g-md-3 mb-4">
    <div class="col-6 col-sm-4 col-md-4 col-xl-2">
      <div class="stat-card p-2.5 p-md-3 h-100 shadow-sm">
        <div class="d-flex align-items-center gap-1.5 gap-md-2 mb-2" style="min-width: 0;">
          <div class="stat-icon-wrapper flex-shrink-0 bg-success bg-opacity-10 text-success p-2 rounded-3" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
            <i class="fa-solid fa-money-bill-wave"></i>
          </div>
          <div class="stat-title text-muted text-uppercase fw-bold flex-grow-1 mb-0" style="font-size: 0.62rem; letter-spacing: 0.01em; line-height: 1.15; white-space: normal; overflow-wrap: anywhere; word-break: break-word; min-width: 0;">Total Collected</div>
        </div>
        <div class="stat-value text-success fw-bold fs-5 mb-0"><?= format_currency($metrics['total_received']) ?></div>
      </div>
    </div>
    <div class="col-6 col-sm-4 col-md-4 col-xl-2">
      <div class="stat-card p-2.5 p-md-3 h-100 shadow-sm">
        <div class="d-flex align-items-center gap-1.5 gap-md-2 mb-2" style="min-width: 0;">
          <div class="stat-icon-wrapper flex-shrink-0 bg-danger bg-opacity-10 text-danger p-2 rounded-3" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
            <i class="fa-solid fa-file-invoice-dollar"></i>
          </div>
          <div class="stat-title text-muted text-uppercase fw-bold flex-grow-1 mb-0" style="font-size: 0.62rem; letter-spacing: 0.01em; line-height: 1.15; white-space: normal; overflow-wrap: anywhere; word-break: break-word; min-width: 0;">Outstanding</div>
        </div>
        <div class="stat-value text-danger fw-bold fs-5 mb-0"><?= format_currency($metrics['outstanding']) ?></div>
      </div>
    </div>
    <div class="col-6 col-sm-4 col-md-4 col-xl-2">
      <div class="stat-card p-2.5 p-md-3 h-100 shadow-sm">
        <div class="d-flex align-items-center gap-1.5 gap-md-2 mb-2" style="min-width: 0;">
          <div class="stat-icon-wrapper flex-shrink-0 bg-warning bg-opacity-10 text-warning p-2 rounded-3" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
            <i class="fa-solid fa-rotate-left"></i>
          </div>
          <div class="stat-title text-muted text-uppercase fw-bold flex-grow-1 mb-0" style="font-size: 0.62rem; letter-spacing: 0.01em; line-height: 1.15; white-space: normal; overflow-wrap: anywhere; word-break: break-word; min-width: 0;">Refunded</div>
        </div>
        <div class="stat-value text-warning fw-bold fs-5 mb-0"><?= format_currency($metrics['total_refunded']) ?></div>
      </div>
    </div>
    <div class="col-6 col-sm-4 col-md-4 col-xl-2">
      <div class="stat-card p-2.5 p-md-3 h-100 shadow-sm">
        <div class="d-flex align-items-center gap-1.5 gap-md-2 mb-2" style="min-width: 0;">
          <div class="stat-icon-wrapper flex-shrink-0 bg-info bg-opacity-10 text-info p-2 rounded-3" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
            <i class="fa-solid fa-wallet"></i>
          </div>
          <div class="stat-title text-muted text-uppercase fw-bold flex-grow-1 mb-0" style="font-size: 0.62rem; letter-spacing: 0.01em; line-height: 1.15; white-space: normal; overflow-wrap: anywhere; word-break: break-word; min-width: 0;">Wallet Credits</div>
        </div>
        <div class="stat-value text-info fw-bold fs-5 mb-0"><?= format_currency($metrics['total_wallet_credits']) ?></div>
      </div>
    </div>
    <div class="col-6 col-sm-4 col-md-4 col-xl-2">
      <div class="stat-card p-2.5 p-md-3 h-100 shadow-sm">
        <div class="d-flex align-items-center gap-1.5 gap-md-2 mb-2" style="min-width: 0;">
          <div class="stat-icon-wrapper flex-shrink-0 bg-primary bg-opacity-10 text-primary p-2 rounded-3" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
          </div>
          <div class="stat-title text-muted text-uppercase fw-bold flex-grow-1 mb-0" style="font-size: 0.62rem; letter-spacing: 0.01em; line-height: 1.15; white-space: normal; overflow-wrap: anywhere; word-break: break-word; min-width: 0;">Wallet Debits</div>
        </div>
        <div class="stat-value text-primary fw-bold fs-5 mb-0"><?= format_currency($metrics['total_wallet_debits']) ?></div>
      </div>
    </div>
    <div class="col-6 col-sm-4 col-md-4 col-xl-2">
      <div class="stat-card p-2.5 p-md-3 h-100 shadow-sm">
        <div class="d-flex align-items-center gap-1.5 gap-md-2 mb-2" style="min-width: 0;">
          <div class="stat-icon-wrapper flex-shrink-0 bg-secondary bg-opacity-10 text-secondary p-2 rounded-3" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
            <i class="fa-solid fa-share-nodes"></i>
          </div>
          <div class="stat-title text-muted text-uppercase fw-bold flex-grow-1 mb-0" style="font-size: 0.62rem; letter-spacing: 0.01em; line-height: 1.15; white-space: normal; overflow-wrap: anywhere; word-break: break-word; min-width: 0;">Payment Links</div>
        </div>
        <div class="stat-value text-secondary fw-bold fs-5 mb-0"><?= $metrics['total_online_links'] ?> Links</div>
      </div>
    </div>
  </div>

  <!-- Multi-Filter & Search Bar (100% Responsive Toolbar) -->
  <div class="card card-enterprise mb-4 shadow-sm">
    <div class="card-body p-3">
      <form method="GET" action="/payments" class="row g-2 align-items-end">
        <div class="col-12 col-md-6 col-xl-3">
          <label class="form-label small text-muted mb-1 fw-semibold">Search Keywords</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Receipt / Invoice / Name / Passport..." value="<?= e($_GET['search'] ?? '') ?>">
          </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
          <label class="form-label small text-muted mb-1 fw-semibold">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">-- All Statuses --</option>
            <option value="Completed" <?= ($_GET['status'] ?? '') === 'Completed' ? 'selected' : '' ?>>Completed</option>
            <option value="Pending" <?= ($_GET['status'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Failed" <?= ($_GET['status'] ?? '') === 'Failed' ? 'selected' : '' ?>>Failed</option>
            <option value="Refunded" <?= ($_GET['status'] ?? '') === 'Refunded' ? 'selected' : '' ?>>Refunded</option>
          </select>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
          <label class="form-label small text-muted mb-1 fw-semibold">Currency</label>
          <select name="currency" class="form-select form-select-sm">
            <option value="">-- All Currencies --</option>
            <?php foreach ($currenciesList as $curr): ?>
              <option value="<?= e($curr) ?>" <?= ($_GET['currency'] ?? '') === $curr ? 'selected' : '' ?>><?= e($curr) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
          <label class="form-label small text-muted mb-1 fw-semibold">Min Amount</label>
          <input type="number" step="0.01" min="0" name="min_amount" class="form-control form-control-sm" placeholder="Min" value="<?= e($_GET['min_amount'] ?? '') ?>">
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
          <label class="form-label small text-muted mb-1 fw-semibold">Max Amount</label>
          <input type="number" step="0.01" min="0" name="max_amount" class="form-control form-control-sm" placeholder="Max" value="<?= e($_GET['max_amount'] ?? '') ?>">
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
          <label class="form-label small text-muted mb-1 fw-semibold">Branch</label>
          <select name="branch_id" class="form-select form-select-sm">
            <option value="">-- All Branches --</option>
            <?php foreach ($branchesList as $b): ?>
              <option value="<?= $b['id'] ?>" <?= ((int)($_GET['branch_id'] ?? 0)) === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
          <label class="form-label small text-muted mb-1 fw-semibold">Destination Country</label>
          <select name="country_id" class="form-select form-select-sm">
            <option value="">-- All Countries --</option>
            <?php foreach ($countriesList as $cnt): ?>
              <option value="<?= $cnt['id'] ?>" <?= ((int)($_GET['country_id'] ?? 0)) === (int)$cnt['id'] ? 'selected' : '' ?>>
                <?= e($cnt['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
          <label class="form-label small text-muted mb-1 fw-semibold">Payment Method</label>
          <select name="method" class="form-select form-select-sm">
            <option value="">-- All Methods --</option>
            <option value="Stripe" <?= ($_GET['method'] ?? '') === 'Stripe' ? 'selected' : '' ?>>Stripe / Online</option>
            <option value="Customer Wallet" <?= ($_GET['method'] ?? '') === 'Customer Wallet' ? 'selected' : '' ?>>Customer Wallet</option>
            <option value="Bank Transfer" <?= ($_GET['method'] ?? '') === 'Bank Transfer' ? 'selected' : '' ?>>Bank Wire</option>
            <option value="Cash" <?= ($_GET['method'] ?? '') === 'Cash' ? 'selected' : '' ?>>Cash at Branch</option>
            <option value="Card" <?= ($_GET['method'] ?? '') === 'Card' ? 'selected' : '' ?>>POS Card</option>
          </select>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
          <label class="form-label small text-muted mb-1 fw-semibold">Date From</label>
          <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($_GET['date_from'] ?? '') ?>">
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
          <label class="form-label small text-muted mb-1 fw-semibold">Date To</label>
          <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($_GET['date_to'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-4 col-xl-4 d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm fw-semibold flex-fill">
            <i class="fa-solid fa-filter me-1"></i> Filter Records
          </button>
          <a href="/payments" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
            <i class="fa-solid fa-rotate-left"></i>
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- Payments & Invoices Table (100% Horizontal Responsive Grid) -->
  <div id="paymentViewTable" class="payment-view-container">
    <div class="card card-enterprise shadow-sm">
    <div class="table-responsive" style="-webkit-overflow-scrolling: touch;">
      <table class="table table-hover align-middle mb-0 table-custom" style="font-size: 0.86rem; min-width: 1080px;">
        <thead class="table-light">
          <tr>
            <th style="min-width: 140px;" class="text-nowrap">Receipt / Invoice #</th>
            <th style="min-width: 180px;" class="text-nowrap">Applicant &amp; Passport</th>
            <th style="min-width: 210px;" class="text-nowrap">Destination &amp; Visa Type</th>
            <th style="min-width: 140px;" class="text-nowrap">Date &amp; Method</th>
            <th style="min-width: 140px;" class="text-nowrap">Transaction Ref</th>
            <th style="min-width: 110px;" class="text-nowrap">Amount Paid</th>
            <th style="min-width: 100px;" class="text-nowrap">Status</th>
            <th style="min-width: 90px;" class="text-end text-nowrap">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($payments)): ?>
            <tr><td colspan="8" class="text-center py-5 text-muted">No payment transactions match the filter criteria.</td></tr>
          <?php else: ?>
            <?php foreach ($payments as $p): ?>
              <tr>
                <td class="text-nowrap">
                  <span class="fw-bold text-primary d-block"><?= e($p['payment_number']) ?></span>
                  <span class="badge bg-light text-dark border small mt-0.5"><?= e($p['invoice_number'] ?: 'INV-N/A') ?></span>
                </td>
                <td class="text-nowrap">
                  <div class="fw-bold text-dark text-truncate" style="max-width: 180px;" title="<?= e($p['customer_name']) ?>"><?= e($p['customer_name']) ?></div>
                  <div class="small text-muted text-nowrap">ID: <strong><?= e($p['customer_code']) ?></strong> &bull; Pass: <?= e($p['passport_number'] ?: 'N/A') ?></div>
                </td>
                <td class="text-nowrap">
                  <div class="fw-semibold text-dark text-truncate" style="max-width: 210px;"><?= $p['flag_emoji'] ?? '✈️' ?> <?= e($p['country_name'] ?? 'General') ?></div>
                  <div class="small text-muted text-truncate" style="max-width: 210px;" title="<?= e($p['service_name'] ?? 'Visa Service') ?>"><?= e($p['service_name'] ?? 'Visa Service') ?></div>
                </td>
                <td class="text-nowrap">
                  <div class="fw-semibold"><?= format_date($p['payment_date']) ?></div>
                  <span class="badge bg-primary-subtle text-primary small"><?= e($p['payment_method']) ?></span>
                </td>
                <td class="text-nowrap">
                  <span class="text-dark small font-monospace d-block"><?= e($p['transaction_reference'] ?: 'N/A') ?></span>
                  <?php if (!empty($p['wallet_transaction_id'])): ?>
                    <div class="text-warning small fw-semibold"><i class="fa-solid fa-wallet me-1"></i>Wallet Txn #<?= $p['wallet_transaction_id'] ?></div>
                  <?php endif; ?>
                </td>
                <td class="text-nowrap">
                  <span class="fw-bold text-success fs-6"><?= format_currency((float)$p['amount']) ?></span>
                </td>
                <td class="text-nowrap">
                  <span class="badge bg-success-subtle text-success fw-bold px-2 py-1">
                    <i class="fa-solid fa-circle-check me-1"></i> <?= e($p['status']) ?>
                  </span>
                </td>
                <td class="text-end text-nowrap">
                  <div class="d-inline-flex align-items-center gap-1">
                    <div class="btn-group btn-group-sm shadow-sm" role="group">
                      <a href="/payments/receipt?id=<?= $p['id'] ?>" target="_blank" class="btn btn-outline-secondary" title="Print Official Receipt">
                        <i class="fa-solid fa-print"></i>
                      </a>
                      <a href="/payments/invoice?app_id=<?= $p['application_id'] ?>" target="_blank" class="btn btn-outline-primary" title="View Full Tax Invoice">
                        <i class="fa-solid fa-file-invoice"></i>
                      </a>
                    </div>
                    <?php if ($canDeletePayment): ?>
                      <form action="/payments/delete" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete Payment <?= e($p['payment_number']) ?> (Invoice: <?= e($p['invoice_number'] ?: 'INV-N/A') ?>) for <?= format_currency((float)$p['amount']) ?>? This will reverse the payment and adjust the balance due on application #<?= (int)$p['application_id'] ?>.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm shadow-sm py-1 px-2" title="Delete Payment / Invoice Record">
                          <i class="fa-solid fa-trash-can"></i>
                        </button>
                      </form>
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
</div>

<!-- ================================================================= -->
<!-- VIEW OPTION 2: PAYMENTS GRID CARDS VIEW -->
<!-- ================================================================= -->
<div id="paymentViewGrid" class="payment-view-container d-none mb-4">
  <div class="row g-3">
    <?php if (empty($payments)): ?>
      <div class="col-12 text-center py-5 text-muted">No payment records found.</div>
    <?php else: ?>
      <?php foreach ($payments as $p): ?>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="card card-enterprise h-100 shadow-sm border">
            <div class="card-body p-3.5 d-flex flex-column justify-content-between">
              <div>
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="badge bg-light text-dark border fw-bold px-2.5 py-1">
                    <i class="fa-solid fa-receipt me-1 text-primary"></i><?= e($p['payment_number']) ?>
                  </span>
                  <span class="badge bg-success-subtle text-success fw-bold px-2 py-0.5" style="font-size: 0.72rem;">
                    <i class="fa-solid fa-circle-check me-1"></i><?= e($p['status']) ?>
                  </span>
                </div>

                <h6 class="fw-bold text-dark mb-1 text-truncate">
                  <?= e($p['customer_name'] ?? 'Direct Walk-in') ?>
                </h6>
                <div class="text-muted small mb-2 text-truncate" style="font-size: 0.75rem;">
                  ID: <?= e($p['customer_code'] ?? '—') ?> &bull; Pass: <?= e($p['passport_number'] ?: 'N/A') ?>
                </div>

                <div class="p-2.5 bg-light rounded-3 border mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small">Service:</span>
                    <strong class="text-dark small text-truncate" style="max-width: 170px;"><?= $p['flag_emoji'] ?? '✈️' ?> <?= e($p['country_name'] ?? 'Visa') ?></strong>
                  </div>
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small">Method:</span>
                    <span class="badge bg-primary-subtle text-primary small"><?= e($p['payment_method']) ?></span>
                  </div>
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Date:</span>
                    <span class="small fw-semibold text-secondary"><?= format_date($p['payment_date']) ?></span>
                  </div>
                </div>

                <div class="d-flex align-items-center justify-content-between mb-2 p-2 bg-white rounded border">
                  <span class="text-muted small text-uppercase fw-bold" style="font-size: 0.68rem;">Amount Paid:</span>
                  <span class="fw-bold text-success fs-5"><?= format_currency((float)$p['amount']) ?></span>
                </div>
              </div>

              <div class="pt-2 border-top d-flex align-items-center justify-content-between gap-1 mt-2">
                <div class="btn-group btn-group-sm shadow-sm" role="group">
                  <a href="/payments/receipt?id=<?= $p['id'] ?>" target="_blank" class="btn btn-outline-secondary" title="Print Official Receipt">
                    <i class="fa-solid fa-print me-1"></i> Receipt
                  </a>
                  <a href="/payments/invoice?app_id=<?= $p['application_id'] ?>" target="_blank" class="btn btn-outline-primary" title="View Full Tax Invoice">
                    <i class="fa-solid fa-file-invoice me-1"></i> Invoice
                  </a>
                </div>

                <?php if ($canDeletePayment): ?>
                  <form action="/payments/delete" method="POST" class="d-inline" onsubmit="return confirm('Delete payment <?= e($p['payment_number']) ?>?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2" title="Delete Payment">
                      <i class="fa-solid fa-trash-can"></i>
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- ================================================================= -->
<!-- VIEW OPTION 3: PAYMENTS COMPACT LIST VIEW -->
<!-- ================================================================= -->
<div id="paymentViewCompact" class="payment-view-container d-none mb-4">
  <div class="card card-enterprise shadow-sm border">
    <div class="list-group list-group-flush">
      <?php if (empty($payments)): ?>
        <div class="p-4 text-center text-muted">No payment records available.</div>
      <?php else: ?>
        <?php foreach ($payments as $p): ?>
          <div class="list-group-item p-3 border-start border-4 border-success d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2" style="min-width: 240px; flex: 1 1 300px;">
              <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                  <span class="badge bg-light text-dark border fw-bold"><?= e($p['payment_number']) ?></span>
                  <span class="fw-bold text-dark fs-6"><?= e($p['customer_name'] ?? 'Direct Walk-in') ?></span>
                  <span class="badge bg-info-subtle text-info border" style="font-size: 0.68rem;"><?= $p['flag_emoji'] ?? '✈️' ?> <?= e($p['country_name'] ?? 'General') ?></span>
                </div>
                <div class="text-muted small d-flex align-items-center gap-2 mt-0.5" style="font-size: 0.74rem;">
                  <span><i class="fa-regular fa-credit-card me-1 text-primary"></i><?= e($p['payment_method']) ?></span>
                  <span>&bull;</span>
                  <span><?= format_date($p['payment_date']) ?></span>
                  <?php if (!empty($p['transaction_reference'])): ?>
                    <span>&bull;</span>
                    <span class="font-monospace">Ref: <?= e($p['transaction_reference']) ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="d-flex align-items-center gap-3 ms-auto">
              <span class="fw-bold text-success fs-6"><?= format_currency((float)$p['amount']) ?></span>
              <div class="btn-group btn-group-sm shadow-sm" role="group">
                <a href="/payments/receipt?id=<?= $p['id'] ?>" target="_blank" class="btn btn-outline-secondary" title="Print Official Receipt">
                  <i class="fa-solid fa-print"></i>
                </a>
                <a href="/payments/invoice?app_id=<?= $p['application_id'] ?>" target="_blank" class="btn btn-outline-primary" title="View Full Tax Invoice">
                  <i class="fa-solid fa-file-invoice"></i>
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
</div>

<!-- Modal: Generate Payment Link -->
<div class="modal fade" id="generateLinkModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="/payments/link/create" method="POST">
        <?= csrf_field() ?>
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title fw-bold"><i class="fa-solid fa-link me-2"></i> Generate Online Payment Link</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Select Visa Application <span class="text-danger">*</span></label>
            <select name="application_id" class="form-select" required onchange="setLinkAmount(this)">
              <option value="">-- Choose Application / Applicant --</option>
              <?php foreach ($applicationsList as $ap): ?>
                <option value="<?= $ap['id'] ?>" data-balance="<?= $ap['balance_amount'] ?>" data-total="<?= $ap['total_amount'] ?>">
                  <?= e($ap['application_number']) ?> &mdash; <?= e($ap['customer_name']) ?> (Bal: $<?= number_format((float)$ap['balance_amount'], 2) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Payment Amount ($) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="amount" id="linkAmountInput" class="form-control" placeholder="0.00" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Custom Title / Purpose</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Schengen Visa Processing &amp; Biometrics Fee">
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Customer Instructions / Note</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Instructions shown on customer checkout page..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
            <i class="fa-solid fa-paper-plane me-1"></i> Generate &amp; Activate Link
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Record Payment with Interactive Applicant Selector (Sir Feedback) -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="/payments/store" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title fw-bold"><i class="fa-solid fa-credit-card me-2"></i> Record Application Payment</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <!-- Step 1: Select Applicant -->
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">1. Select Applicant / Visa Application <span class="text-danger">*</span></label>
            <select name="application_id" id="paymentAppSelect" class="form-select" required onchange="onApplicantSelected(this)">
              <option value="">-- Search &amp; Select Customer / Application --</option>
              <?php $activeSelAppId = (int)($selectedAppId ?? ($_GET['app_id'] ?? ($_GET['application_id'] ?? 0))); ?>
              <?php foreach ($applicationsList as $ap): ?>
                <option value="<?= $ap['id'] ?>" 
                  <?= ($activeSelAppId === (int)$ap['id']) ? 'selected' : '' ?>
                  data-name="<?= e($ap['customer_name']) ?>"
                  data-code="<?= e($ap['customer_code']) ?>"
                  data-passport="<?= e($ap['passport_number'] ?: 'On File') ?>"
                  data-mobile="<?= e($ap['customer_mobile'] ?: '—') ?>"
                  data-email="<?= e($ap['customer_email'] ?: '—') ?>"
                  data-country="<?= e($ap['country_name']) ?>"
                  data-emoji="<?= $ap['flag_emoji'] ?>"
                  data-service="<?= e($ap['service_name']) ?>"
                  data-appno="<?= e($ap['application_number']) ?>"
                  data-balance="<?= $ap['balance_amount'] ?>"
                  data-total="<?= $ap['total_amount'] ?>">
                  <?= e($ap['application_number']) ?> &mdash; <?= e($ap['customer_name']) ?> (ID: <?= e($ap['customer_code']) ?> | Pass: <?= e($ap['passport_number'] ?: '—') ?>) [Bal: $<?= number_format((float)$ap['balance_amount'], 2) ?>]
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Step 2: Auto-populated Customer Card (Part 18, 19, 51) -->
          <div id="applicantCard" class="card bg-light border p-3 mb-3 d-none">
            <div class="d-flex justify-content-between align-items-start border-bottom pb-2 mb-2">
              <div>
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Customer Identity:</span>
                <div class="fw-bold text-dark fs-6" id="cardCustName">—</div>
                <div class="small text-muted">ID: <strong id="cardCustCode">—</strong> &bull; Passport: <strong id="cardPassport">—</strong></div>
              </div>
              <div class="text-end">
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Outstanding Balance:</span>
                <div class="fw-bold text-danger fs-5" id="cardBalance">$0.00</div>
              </div>
            </div>
            <div class="row g-2 small">
              <div class="col-sm-6">
                <span class="text-muted">Contact:</span> <span class="text-dark" id="cardContact">—</span>
              </div>
              <div class="col-sm-6 text-sm-end">
                <span class="text-muted">Destination:</span> <span class="fw-semibold text-dark" id="cardDestination">—</span>
              </div>
            </div>
          </div>

          <!-- Step 3: Multi-Currency & Payment Amount Details -->
          <div class="p-3 bg-light rounded border mb-3">
            <div class="row g-2 mb-2">
              <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold">Received Currency</label>
                <select name="from_currency" id="payFromCur" class="form-select form-select-sm fw-bold" onchange="onPaymentCurrenciesChanged()">
                  <?php foreach (['USD', 'AED', 'LKR', 'EUR', 'GBP', 'SAR', 'QAR', 'INR', 'CAD', 'AUD'] as $c): ?>
                    <option value="<?= $c ?>" <?= $c === 'USD' ? 'selected' : '' ?>><?= $c ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold">Received Amount <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0.01" name="original_amount" id="payOriginalAmount" class="form-control form-control-sm fw-bold" placeholder="0.00" required oninput="calcPaymentConverter(false)">
              </div>
              <div class="col-6 col-md-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label small fw-semibold mb-0">Exchange Rate</label>
                  <button type="button" class="btn btn-link btn-xs p-0 text-decoration-none" id="payInvertRateBtn" onclick="invertPaymentRate()" title="Invert rate (1 / rate)" style="font-size: 0.68rem;">
                    <i class="fa-solid fa-arrows-rotate me-0.5"></i> Invert
                  </button>
                </div>
                <input type="number" step="0.000001" name="exchange_rate" id="payExchangeRate" class="form-control form-control-sm text-end fw-bold" value="1.000000" oninput="calcPaymentConverter(true)">
              </div>
              <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold">Settlement Currency</label>
                <select name="to_currency" id="payToCur" class="form-select form-select-sm fw-bold" onchange="onPaymentCurrenciesChanged()">
                  <?php foreach (['USD', 'AED', 'LKR', 'EUR', 'GBP', 'SAR', 'QAR', 'INR', 'CAD', 'AUD'] as $c): ?>
                    <option value="<?= $c ?>" <?= $c === 'USD' ? 'selected' : '' ?>><?= $c ?><?= $c === 'USD' ? ' ($)' : ($c === 'EUR' ? ' (€)' : ($c === 'GBP' ? ' (£)' : '')) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
              <div>
                <span class="small fw-semibold text-muted">Applied Settlement Amount:</span>
                <div class="small text-muted" id="payFormulaText" style="font-size: 0.73rem;">1:1 Same Currency</div>
              </div>
              <div class="text-end">
                <span class="h5 fw-bold text-success mb-0" id="payConvertedDisplay">$0.00 USD</span>
                <input type="hidden" name="amount" id="recordPaymentAmount" value="0.00">
                <input type="hidden" name="converted_amount" id="payConvertedAmountHidden" value="0.00">
              </div>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Payment Date <span class="text-danger">*</span></label>
              <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Payment Method <span class="text-danger">*</span></label>
              <select name="payment_method" class="form-select" required>
                <option value="Cash">Cash at Branch</option>
                <option value="Bank Transfer">Bank Transfer / Wire</option>
                <option value="Credit Card">POS Credit Card</option>
                <option value="Debit Card">POS Debit Card</option>
                <option value="Cheque">Cheque</option>
                <option value="Customer Wallet">Pay from Customer Wallet</option>
                <option value="Online Payment">Online Payment Link</option>
                <option value="Western Union">Western Union</option>
              </select>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Transaction / Bank Reference</label>
              <input type="text" name="transaction_reference" class="form-control" placeholder="Bank ref / cheque # / POS auth...">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Payment Slip / Cash Receipt Voucher <span class="text-danger">*</span></label>
              <input type="file" name="receipt_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.docx" required>
              <small class="text-muted" style="font-size: 0.72rem;"><i class="fa-solid fa-file-circle-check text-success me-1"></i>Mandatory for all payment methods (Bank slip, Deposit slip, Cash voucher, POS slip).</small>
            </div>
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold">Internal Notes / Purpose</label>
            <input type="text" name="notes" class="form-control" placeholder="Optional notes for accounting...">
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success btn-sm px-4 fw-semibold shadow-sm">
            <i class="fa-solid fa-check me-1"></i> Confirm Payment &amp; Issue Receipt
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function setLinkAmount(select) {
  const opt = select.options[select.selectedIndex];
  if (opt && opt.dataset.balance) {
    const bal = parseFloat(opt.dataset.balance);
    const tot = parseFloat(opt.dataset.total);
    document.getElementById('linkAmountInput').value = (bal > 0 ? bal : tot).toFixed(2);
  }
}

const PAYMENT_BENCHMARK = {
  'USD': 1.0,
  'AED': 3.6725,
  'LKR': 305.00,
  'EUR': 0.9200,
  'GBP': 0.7900,
  'SAR': 3.7500,
  'QAR': 3.6400,
  'INR': 83.5000,
  'CAD': 1.3600,
  'AUD': 1.5200
};

function onPaymentCurrenciesChanged() {
  const from = document.getElementById('payFromCur').value || 'USD';
  const to = document.getElementById('payToCur').value || 'USD';
  const rateInput = document.getElementById('payExchangeRate');

  if (from === to) {
    rateInput.value = '1.000000';
    rateInput.readOnly = true;
    rateInput.classList.add('bg-light');
  } else {
    rateInput.readOnly = false;
    rateInput.classList.remove('bg-light');
    const fromBench = PAYMENT_BENCHMARK[from] || 1.0;
    const toBench = PAYMENT_BENCHMARK[to] || 1.0;
    const crossRate = toBench / fromBench;
    rateInput.value = crossRate.toFixed(6);
  }
  calcPaymentConverter(false);
}

function invertPaymentRate() {
  const rateInput = document.getElementById('payExchangeRate');
  const currRate = parseFloat(rateInput.value) || 1;
  if (currRate > 0) {
    rateInput.value = (1 / currRate).toFixed(6);
    calcPaymentConverter(true);
  }
}

function calcPaymentConverter(isManualRate) {
  const origAmt = parseFloat(document.getElementById('payOriginalAmount').value) || 0;
  const from = document.getElementById('payFromCur').value || 'USD';
  const to = document.getElementById('payToCur').value || 'USD';
  let rate = parseFloat(document.getElementById('payExchangeRate').value);

  if (from === to) {
    rate = 1.0;
    document.getElementById('payExchangeRate').value = '1.000000';
  } else if (isNaN(rate) || rate <= 0) {
    rate = 1.0;
  }

  const converted = (origAmt * rate).toFixed(2);
  const sym = to === 'USD' ? '$' : (to === 'EUR' ? '€' : (to === 'GBP' ? '£' : ''));

  document.getElementById('payConvertedDisplay').innerText = sym + converted + ' ' + to;
  document.getElementById('recordPaymentAmount').value = converted;
  document.getElementById('payConvertedAmountHidden').value = converted;

  const formulaEl = document.getElementById('payFormulaText');
  if (formulaEl) {
    if (from === to) {
      formulaEl.innerText = `1:1 Same Currency: ${origAmt.toFixed(2)} ${from} applied to invoice`;
    } else {
      formulaEl.innerText = `Calculation: ${origAmt.toFixed(2)} ${from} × ${rate.toFixed(4)} = ${sym}${converted} ${to} applied to invoice`;
    }
  }
}

function onApplicantSelected(select) {
  const opt = select.options[select.selectedIndex];
  const card = document.getElementById('applicantCard');
  if (!opt || !opt.value) {
    card.classList.add('d-none');
    return;
  }

  card.classList.remove('d-none');
  document.getElementById('cardCustName').innerText = opt.dataset.name || '—';
  document.getElementById('cardCustCode').innerText = opt.dataset.code || '—';
  document.getElementById('cardPassport').innerText = opt.dataset.passport || '—';
  document.getElementById('cardContact').innerText = (opt.dataset.mobile || '') + ' | ' + (opt.dataset.email || '');
  document.getElementById('cardDestination').innerText = (opt.dataset.emoji || '') + ' ' + (opt.dataset.country || '') + ' (' + (opt.dataset.service || '') + ')';
  
  const bal = parseFloat(opt.dataset.balance || 0);
  const tot = parseFloat(opt.dataset.total || 0);
  const due = bal > 0 ? bal : tot;
  document.getElementById('cardBalance').innerText = '$' + due.toFixed(2);
  
  // Set original amount to due balance
  document.getElementById('payOriginalAmount').value = due.toFixed(2);
  calcPaymentConverter();
}

document.addEventListener('DOMContentLoaded', function () {
  var sel = document.getElementById('paymentAppSelect');
  if (sel && sel.value) {
    onApplicantSelected(sel);
    if (typeof openModalById === 'function') {
      openModalById('recordPaymentModal');
    }
  }

  const urlParams = new URLSearchParams(window.location.search);
  const paramView = urlParams.get('view');
  let savedView = null;
  try { savedView = localStorage.getItem('vt_payment_view'); } catch(e) {}

  if (paramView) {
    switchPaymentView(paramView);
  } else if (savedView) {
    switchPaymentView(savedView);
  } else if (window.innerWidth < 768) {
    switchPaymentView('grid'); // Default to cards on mobile
  } else {
    switchPaymentView('table');
  }
});

function switchPaymentView(viewMode) {
  document.querySelectorAll('.payment-view-container').forEach(el => el.classList.add('d-none'));
  document.querySelectorAll('.payment-view-btn').forEach(btn => {
    btn.classList.remove('btn-primary', 'shadow-sm');
    btn.classList.add('btn-light', 'text-muted');
  });

  if (viewMode === 'grid') {
    const el = document.getElementById('paymentViewGrid');
    if (el) el.classList.remove('d-none');
    const btn = document.getElementById('btnPaymentViewGrid');
    if (btn) { btn.classList.remove('btn-light', 'text-muted'); btn.classList.add('btn-primary', 'shadow-sm'); }
  } else if (viewMode === 'compact') {
    const el = document.getElementById('paymentViewCompact');
    if (el) el.classList.remove('d-none');
    const btn = document.getElementById('btnPaymentViewCompact');
    if (btn) { btn.classList.remove('btn-light', 'text-muted'); btn.classList.add('btn-primary', 'shadow-sm'); }
  } else {
    const el = document.getElementById('paymentViewTable');
    if (el) el.classList.remove('d-none');
    const btn = document.getElementById('btnPaymentViewTable');
    if (btn) { btn.classList.remove('btn-light', 'text-muted'); btn.classList.add('btn-primary', 'shadow-sm'); }
  }

  try {
    localStorage.setItem('vt_payment_view', viewMode);
  } catch(e) {}
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
