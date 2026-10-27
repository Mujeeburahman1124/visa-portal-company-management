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

<!-- Load Bento Design System CSS & Page Styles -->
<link rel="stylesheet" href="/assets/css/dashboard-bento.css?v=<?= time() ?>">
<link rel="stylesheet" href="/assets/css/pages/payments-index.css?v=<?= time() ?>">

<div class="content-body p-0 payments-page">
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

    <!-- ─── 1. BENTO HERO HEADER ─────────────────────────────────────────── -->
    <header class="bento-header mb-4">
      <div class="bento-header-left">
        <div class="bento-tag">
          <i class="fa-solid fa-receipt"></i>
          <span>Finance &amp; Collections</span>
        </div>
        <h1 class="bento-header-title">Payments &amp; Invoices</h1>
        <p class="bento-header-sub">
          End-to-end payment registry &bull; Multi-currency checkout &bull; Invoices &bull; Receipts &bull; Wallets
        </p>
      </div>

      <div class="pay-header-actions">
        <!-- 3-Mode View Switcher -->
        <div class="pay-segmented me-1" role="group" aria-label="View Mode">
          <button type="button" class="active" id="btnPaymentViewTable" onclick="switchPaymentView('table')" title="Tabular View">
            <i class="fa-solid fa-table-list"></i> Table
          </button>
          <button type="button" id="btnPaymentViewGrid" onclick="switchPaymentView('grid')" title="Card Grid View">
            <i class="fa-solid fa-grip"></i> Cards
          </button>
          <button type="button" id="btnPaymentViewCompact" onclick="switchPaymentView('compact')" title="Compact List View">
            <i class="fa-solid fa-bars"></i> Compact
          </button>
        </div>

        <a href="/payments/history" class="bento-btn-outline" title="Payment Audit History">
          <i class="fa-solid fa-clock-rotate-left"></i> History
        </a>
        <a href="/payments/links" class="bento-btn-outline" title="Online Payment Links">
          <i class="fa-solid fa-list-check"></i> Links (<?= (int)$metrics['total_online_links'] ?>)
        </a>
        <button type="button" class="bento-btn-outline" data-bs-toggle="modal" data-bs-target="#generateLinkModal" title="Create Customer Payment Link">
          <i class="fa-solid fa-link"></i> Payment Link
        </button>
        <button type="button" class="bento-btn-primary" data-bs-toggle="modal" data-bs-target="#recordPaymentModal" title="Record Manual Payment">
          <i class="fa-solid fa-plus"></i> Record Payment
        </button>
      </div>
    </header>

    <!-- ─── 2. EXECUTIVE FINANCIAL KPI STRIP ──────────────────────────────── -->
    <div class="pay-kpi-grid">
      <!-- Total Collected -->
      <div class="pay-kpi-card tone-success">
        <div class="pay-kpi-top">
          <span class="pay-kpi-label">Total Collected</span>
          <div class="pay-kpi-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
        </div>
        <div class="pay-kpi-val"><?= format_currency($metrics['total_received']) ?></div>
        <span class="pay-kpi-sub">Cleared revenue</span>
      </div>

      <!-- Outstanding Balance -->
      <div class="pay-kpi-card tone-danger">
        <div class="pay-kpi-top">
          <span class="pay-kpi-label">Outstanding</span>
          <div class="pay-kpi-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        </div>
        <div class="pay-kpi-val"><?= format_currency($metrics['outstanding']) ?></div>
        <span class="pay-kpi-sub">Receivables due</span>
      </div>

      <!-- Refunded -->
      <div class="pay-kpi-card tone-warning">
        <div class="pay-kpi-top">
          <span class="pay-kpi-label">Refunded</span>
          <div class="pay-kpi-icon"><i class="fa-solid fa-rotate-left"></i></div>
        </div>
        <div class="pay-kpi-val"><?= format_currency($metrics['total_refunded']) ?></div>
        <span class="pay-kpi-sub">Reversals &amp; returns</span>
      </div>

      <!-- Wallet Credits -->
      <div class="pay-kpi-card tone-info">
        <div class="pay-kpi-top">
          <span class="pay-kpi-label">Wallet Credits</span>
          <div class="pay-kpi-icon"><i class="fa-solid fa-wallet"></i></div>
        </div>
        <div class="pay-kpi-val"><?= format_currency($metrics['total_wallet_credits']) ?></div>
        <span class="pay-kpi-sub">Prepayments held</span>
      </div>

      <!-- Wallet Debits -->
      <div class="pay-kpi-card tone-primary">
        <div class="pay-kpi-top">
          <span class="pay-kpi-label">Wallet Debits</span>
          <div class="pay-kpi-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></div>
        </div>
        <div class="pay-kpi-val"><?= format_currency($metrics['total_wallet_debits']) ?></div>
        <span class="pay-kpi-sub">Disbursed balance</span>
      </div>

      <!-- Payment Links -->
      <div class="pay-kpi-card tone-secondary">
        <div class="pay-kpi-top">
          <span class="pay-kpi-label">Payment Links</span>
          <div class="pay-kpi-icon"><i class="fa-solid fa-share-nodes"></i></div>
        </div>
        <div class="pay-kpi-val"><?= (int)$metrics['total_online_links'] ?></div>
        <span class="pay-kpi-sub">Active online links</span>
      </div>
    </div>

    <!-- ─── 3. ADVANCED SEARCH & FILTER TOOLBAR ───────────────────────────── -->
    <div class="pay-filter-card">
      <form method="GET" action="/payments" id="payFilterForm">
        <div class="row g-2.5 align-items-center">
          
          <!-- Search Bar -->
          <div class="col-12 col-xl-3">
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
              <input type="text" name="search" class="form-control" placeholder="Receipt / Invoice / Name / Passport..." value="<?= e($_GET['search'] ?? '') ?>">
            </div>
          </div>

          <!-- Status Dropdown -->
          <div class="col-6 col-sm-4 col-md-2 col-xl-1.5">
            <select name="status" class="form-select form-select-sm" title="Filter by Status">
              <option value="">All Statuses</option>
              <option value="Completed" <?= ($_GET['status'] ?? '') === 'Completed' ? 'selected' : '' ?>>Completed</option>
              <option value="Pending" <?= ($_GET['status'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
              <option value="Failed" <?= ($_GET['status'] ?? '') === 'Failed' ? 'selected' : '' ?>>Failed</option>
              <option value="Refunded" <?= ($_GET['status'] ?? '') === 'Refunded' ? 'selected' : '' ?>>Refunded</option>
            </select>
          </div>

          <!-- Payment Method -->
          <div class="col-6 col-sm-4 col-md-2 col-xl-1.5">
            <select name="method" class="form-select form-select-sm" title="Payment Method">
              <option value="">All Methods</option>
              <option value="Stripe" <?= ($_GET['method'] ?? '') === 'Stripe' ? 'selected' : '' ?>>Stripe / Online</option>
              <option value="Customer Wallet" <?= ($_GET['method'] ?? '') === 'Customer Wallet' ? 'selected' : '' ?>>Customer Wallet</option>
              <option value="Bank Transfer" <?= ($_GET['method'] ?? '') === 'Bank Transfer' ? 'selected' : '' ?>>Bank Wire</option>
              <option value="Cash" <?= ($_GET['method'] ?? '') === 'Cash' ? 'selected' : '' ?>>Cash at Branch</option>
              <option value="Card" <?= ($_GET['method'] ?? '') === 'Card' ? 'selected' : '' ?>>POS Card</option>
            </select>
          </div>

          <!-- Destination Country -->
          <div class="col-6 col-sm-4 col-md-2 col-xl-1.5">
            <select name="country_id" class="form-select form-select-sm" title="Destination Country">
              <option value="">All Countries</option>
              <?php foreach ($countriesList as $cnt): ?>
                <option value="<?= $cnt['id'] ?>" <?= ((int)($_GET['country_id'] ?? 0)) === (int)$cnt['id'] ? 'selected' : '' ?>>
                  <?= e($cnt['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Currency -->
          <div class="col-6 col-sm-4 col-md-2 col-xl-1">
            <select name="currency" class="form-select form-select-sm" title="Currency">
              <option value="">Currency</option>
              <?php foreach ($currenciesList as $curr): ?>
                <option value="<?= e($curr) ?>" <?= ($_GET['currency'] ?? '') === $curr ? 'selected' : '' ?>><?= e($curr) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Date Range (From & To) -->
          <div class="col-6 col-sm-4 col-md-2 col-xl-1.5">
            <input type="date" name="date_from" class="form-control form-control-sm" title="Date From" value="<?= e($_GET['date_from'] ?? '') ?>" placeholder="From">
          </div>
          <div class="col-6 col-sm-4 col-md-2 col-xl-1.5">
            <input type="date" name="date_to" class="form-control form-control-sm" title="Date To" value="<?= e($_GET['date_to'] ?? '') ?>" placeholder="To">
          </div>

          <!-- Action Buttons -->
          <div class="col-12 col-md-auto ms-auto d-flex align-items-center gap-1.5">
            <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.15rem; font-size: 0.82rem;">
              <i class="fa-solid fa-filter me-1"></i> Filter
            </button>
            <a href="/payments" class="btn btn-sm btn-light border rounded-pill px-2.5 py-1.5 text-muted hover-dark" title="Reset Filters">
              <i class="fa-solid fa-rotate-left"></i>
            </a>
          </div>

        </div>
      </form>
    </div>

    <!-- ─── 4. RESULTS PRESENTATION ──────────────────────────────────────── -->
    <?php if (empty($payments)): ?>
      <div class="bento-card text-center py-5">
        <i class="fa-solid fa-file-invoice-dollar fs-1 text-muted opacity-50 mb-3 d-block"></i>
        <h5 class="fw-bold text-dark mb-1">No payment transactions found</h5>
        <p class="text-muted small mb-3">No payments match your current filter or date range criteria.</p>
        <div class="d-flex justify-content-center gap-2">
          <a href="/payments" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Clear Filters</a>
          <button type="button" class="bento-btn-primary" style="padding: 0.45rem 1.15rem; font-size: 0.82rem;" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
            <i class="fa-solid fa-plus me-1"></i> Record Payment
          </button>
        </div>
      </div>
    <?php else: ?>

      <!-- ─── 4A. TABLE VIEW (BENTO ADAPTIVE DATA TABLE) ─────────────────── -->
      <div id="paymentViewTable" class="payment-view-container">
        <div class="bento-card p-0 overflow-hidden mb-4">
          <div class="pay-table-wrap">
            <table class="pay-table">
              <thead>
                <tr>
                  <th>Receipt / Invoice</th>
                  <th>Applicant &amp; Passport</th>
                  <th>Visa Service</th>
                  <th>Date &amp; Method</th>
                  <th>Reference</th>
                  <th>Amount Paid</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($payments as $p): ?>
                  <tr>
                    <!-- Receipt / Invoice -->
                    <td data-label="Receipt / Inv">
                      <div>
                        <span class="pay-receipt-badge"><?= e($p['payment_number']) ?></span>
                        <div class="mt-1">
                          <span class="pay-inv-badge"><?= e($p['invoice_number'] ?: 'INV-N/A') ?></span>
                        </div>
                      </div>
                    </td>

                    <!-- Applicant -->
                    <td data-label="Applicant">
                      <div class="d-flex align-items-center gap-2">
                        <div class="overflow-hidden">
                          <div class="fw-bold text-dark text-truncate" style="max-width: 190px;" title="<?= e($p['customer_name'] ?? 'Direct Walk-in') ?>">
                            <?= e($p['customer_name'] ?? 'Direct Walk-in') ?>
                          </div>
                          <div class="text-muted small text-truncate" style="font-size: 0.74rem; max-width: 190px;">
                            ID: <strong class="text-dark"><?= e($p['customer_code'] ?? '—') ?></strong> &bull; Pass: <?= e($p['passport_number'] ?: 'N/A') ?>
                          </div>
                        </div>
                      </div>
                    </td>

                    <!-- Visa Service -->
                    <td data-label="Destination">
                      <div>
                        <span class="small fw-semibold text-dark">
                          <?= $p['flag_emoji'] ?? '✈️' ?> <?= e($p['country_name'] ?? 'General') ?>
                        </span>
                        <div class="text-muted small text-truncate" style="font-size: 0.73rem; max-width: 200px;" title="<?= e($p['service_name'] ?? 'Visa Service') ?>">
                          <?= e($p['service_name'] ?? 'Visa Service') ?>
                        </div>
                      </div>
                    </td>

                    <!-- Date & Method -->
                    <td data-label="Date & Method">
                      <div>
                        <div class="small fw-semibold text-dark"><?= format_date($p['payment_date']) ?></div>
                        <span class="pay-chip neutral mt-0.5"><?= e($p['payment_method']) ?></span>
                      </div>
                    </td>

                    <!-- Reference -->
                    <td data-label="Reference">
                      <div>
                        <span class="text-dark small font-monospace d-block"><?= e($p['transaction_reference'] ?: '—') ?></span>
                        <?php if (!empty($p['wallet_transaction_id'])): ?>
                          <span class="pay-chip warning mt-0.5"><i class="fa-solid fa-wallet"></i> Wallet #<?= $p['wallet_transaction_id'] ?></span>
                        <?php endif; ?>
                      </div>
                    </td>

                    <!-- Amount Paid -->
                    <td data-label="Amount Paid">
                      <span class="fw-bold text-success fs-6"><?= format_currency((float)$p['amount']) ?></span>
                    </td>

                    <!-- Status -->
                    <td data-label="Status">
                      <?php 
                        $st = strtolower($p['status'] ?? '');
                        $stClass = ($st === 'completed') ? 'success' : (($st === 'refunded') ? 'warning' : (($st === 'failed') ? 'danger' : 'neutral'));
                      ?>
                      <span class="pay-chip <?= $stClass ?>">
                        <i class="fa-solid fa-circle-check"></i> <?= e($p['status']) ?>
                      </span>
                    </td>

                    <!-- Actions -->
                    <td class="text-end td-actions" data-label="Actions">
                      <div class="d-flex align-items-center justify-content-end gap-1">
                        <a href="/payments/receipt?id=<?= $p['id'] ?>" target="_blank" class="pay-icon-btn" title="Print Official Receipt">
                          <i class="fa-solid fa-print"></i>
                        </a>
                        <a href="/payments/invoice?app_id=<?= $p['application_id'] ?>" target="_blank" class="pay-icon-btn primary" title="View Full Tax Invoice">
                          <i class="fa-solid fa-file-invoice"></i>
                        </a>
                        <?php if ($canDeletePayment): ?>
                          <form action="/payments/delete" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete Payment <?= e($p['payment_number']) ?> (Invoice: <?= e($p['invoice_number'] ?: 'INV-N/A') ?>) for <?= format_currency((float)$p['amount']) ?>? This will reverse the payment and adjust the balance due on application #<?= (int)$p['application_id'] ?>.');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="pay-icon-btn danger" title="Delete Payment Record">
                              <i class="fa-solid fa-trash-can"></i>
                            </button>
                          </form>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ─── 4B. GRID CARDS VIEW ────────────────────────────────────────── -->
      <div id="paymentViewGrid" class="payment-view-container d-none mb-4">
        <div class="pay-grid">
          <?php foreach ($payments as $p): ?>
            <?php 
              $st = strtolower($p['status'] ?? '');
              $stClass = ($st === 'completed') ? 'success' : (($st === 'refunded') ? 'warning' : (($st === 'failed') ? 'danger' : 'neutral'));
            ?>
            <div class="pay-card">
              <div>
                <div class="pay-card-top">
                  <span class="pay-receipt-badge"><i class="fa-solid fa-receipt me-1"></i><?= e($p['payment_number']) ?></span>
                  <span class="pay-chip <?= $stClass ?>"><?= e($p['status']) ?></span>
                </div>

                <h6 class="fw-bold text-dark mb-1 text-truncate">
                  <?= e($p['customer_name'] ?? 'Direct Walk-in') ?>
                </h6>
                <div class="text-muted small mb-2 text-truncate" style="font-size: 0.74rem;">
                  ID: <strong class="text-dark"><?= e($p['customer_code'] ?? '—') ?></strong> &bull; Pass: <?= e($p['passport_number'] ?: 'N/A') ?>
                </div>

                <div class="pay-card-details">
                  <div class="pay-card-row">
                    <span class="text-muted">Destination:</span>
                    <strong class="text-dark"><?= $p['flag_emoji'] ?? '✈️' ?> <?= e($p['country_name'] ?? 'Visa') ?></strong>
                  </div>
                  <div class="pay-card-row">
                    <span class="text-muted">Method:</span>
                    <span class="pay-chip neutral"><?= e($p['payment_method']) ?></span>
                  </div>
                  <div class="pay-card-row">
                    <span class="text-muted">Date:</span>
                    <span class="fw-semibold text-secondary"><?= format_date($p['payment_date']) ?></span>
                  </div>
                  <?php if (!empty($p['transaction_reference'])): ?>
                    <div class="pay-card-row">
                      <span class="text-muted">Ref:</span>
                      <span class="font-monospace text-dark"><?= e($p['transaction_reference']) ?></span>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="d-flex align-items-center justify-content-between p-2.5 bg-white rounded-3 border">
                  <span class="text-muted small text-uppercase fw-bold" style="font-size: 0.68rem;">Amount Paid:</span>
                  <span class="fw-bold text-success fs-5"><?= format_currency((float)$p['amount']) ?></span>
                </div>
              </div>

              <div class="pay-card-foot">
                <div class="d-flex gap-1">
                  <a href="/payments/receipt?id=<?= $p['id'] ?>" target="_blank" class="bento-btn-outline" style="padding: 0.35rem 0.75rem; font-size: 0.75rem;" title="Receipt">
                    <i class="fa-solid fa-print me-1"></i> Receipt
                  </a>
                  <a href="/payments/invoice?app_id=<?= $p['application_id'] ?>" target="_blank" class="bento-btn-primary" style="padding: 0.35rem 0.75rem; font-size: 0.75rem;" title="Invoice">
                    <i class="fa-solid fa-file-invoice me-1"></i> Invoice
                  </a>
                </div>

                <?php if ($canDeletePayment): ?>
                  <form action="/payments/delete" method="POST" class="d-inline" onsubmit="return confirm('Delete payment <?= e($p['payment_number']) ?>?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="pay-icon-btn danger" title="Delete Payment">
                      <i class="fa-solid fa-trash-can"></i>
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- ─── 4C. COMPACT LIST VIEW ──────────────────────────────────────── -->
      <div id="paymentViewCompact" class="payment-view-container d-none mb-4">
        <div class="pay-compact-list">
          <?php foreach ($payments as $p): ?>
            <?php 
              $st = strtolower($p['status'] ?? '');
              $stClass = ($st === 'completed') ? 'success' : (($st === 'refunded') ? 'warning' : (($st === 'failed') ? 'danger' : 'neutral'));
            ?>
            <div class="pay-compact-row">
              <div>
                <span class="pay-receipt-badge"><?= e($p['payment_number']) ?></span>
                <span class="pay-inv-badge ms-1"><?= e($p['invoice_number'] ?: 'INV-N/A') ?></span>
              </div>

              <div>
                <span class="fw-bold text-dark fs-6 d-block"><?= e($p['customer_name'] ?? 'Direct Walk-in') ?></span>
                <span class="text-muted small" style="font-size: 0.72rem;">ID: <?= e($p['customer_code'] ?? '—') ?> &bull; Pass: <?= e($p['passport_number'] ?: 'N/A') ?></span>
              </div>

              <div class="pc-meta">
                <span class="small fw-semibold text-dark"><?= $p['flag_emoji'] ?? '✈️' ?> <?= e($p['country_name'] ?? 'General') ?></span>
                <div class="text-muted small" style="font-size: 0.72rem;"><?= format_date($p['payment_date']) ?> &bull; <?= e($p['payment_method']) ?></div>
              </div>

              <div class="pc-meta">
                <span class="pay-chip <?= $stClass ?>"><?= e($p['status']) ?></span>
              </div>

              <div class="text-end">
                <span class="fw-bold text-success fs-6"><?= format_currency((float)$p['amount']) ?></span>
              </div>

              <div class="d-flex align-items-center justify-content-end gap-1">
                <a href="/payments/receipt?id=<?= $p['id'] ?>" target="_blank" class="pay-icon-btn" title="Print Receipt">
                  <i class="fa-solid fa-print"></i>
                </a>
                <a href="/payments/invoice?app_id=<?= $p['application_id'] ?>" target="_blank" class="pay-icon-btn primary" title="View Invoice">
                  <i class="fa-solid fa-file-invoice"></i>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    <?php endif; ?>

  </div>
</div>

<!-- ======================================================================= -->
<!-- MODAL: GENERATE PAYMENT LINK                                            -->
<!-- ======================================================================= -->
<div class="modal fade" id="generateLinkModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
      <form action="/payments/link/create" method="POST">
        <?= csrf_field() ?>
        <div class="modal-header bg-dark text-white p-3.5">
          <h5 class="modal-title fw-bold fs-6"><i class="fa-solid fa-link me-2 text-primary"></i> Generate Online Payment Link</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Select Visa Application <span class="text-danger">*</span></label>
            <select name="application_id" class="form-select rounded-3" required onchange="setLinkAmount(this)">
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
            <input type="number" step="0.01" name="amount" id="linkAmountInput" class="form-control rounded-3" placeholder="0.00" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Custom Title / Purpose</label>
            <input type="text" name="title" class="form-control rounded-3" placeholder="e.g. Schengen Visa Processing &amp; Biometrics Fee">
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Customer Instructions / Note</label>
            <textarea name="description" class="form-control rounded-3" rows="2" placeholder="Instructions shown on customer checkout page..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light p-3 border-top">
          <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.15rem; font-size: 0.82rem;">
            <i class="fa-solid fa-paper-plane me-1"></i> Generate &amp; Activate Link
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ======================================================================= -->
<!-- MODAL: RECORD PAYMENT                                                   -->
<!-- ======================================================================= -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
      <form action="/payments/store" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="modal-header bg-dark text-white p-3.5">
          <h5 class="modal-title fw-bold fs-6"><i class="fa-solid fa-credit-card me-2 text-success"></i> Record Application Payment</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <!-- Step 1: Select Applicant -->
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">1. Select Applicant / Visa Application <span class="text-danger">*</span></label>
            <select name="application_id" id="paymentAppSelect" class="form-select rounded-3" required onchange="onApplicantSelected(this)">
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

          <!-- Step 2: Auto-populated Customer Card -->
          <div id="applicantCard" class="card bg-light border rounded-3 p-3 mb-3 d-none">
            <div class="d-flex justify-content-between align-items-start border-bottom pb-2 mb-2">
              <div>
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Customer Identity:</span>
                <div class="fw-bold text-dark fs-6" id="cardCustName">—</div>
                <div class="small text-muted">ID: <strong id="cardCustCode" class="text-dark">—</strong> &bull; Passport: <strong id="cardPassport" class="text-dark">—</strong></div>
              </div>
              <div class="text-end">
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Outstanding Balance:</span>
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
          <div class="p-3 bg-light rounded-3 border mb-3">
            <div class="row g-2 mb-2">
              <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold">Received Currency</label>
                <select name="from_currency" id="payFromCur" class="form-select form-select-sm fw-bold rounded-2" onchange="onPaymentCurrenciesChanged()">
                  <?php foreach (['USD', 'AED', 'LKR', 'EUR', 'GBP', 'SAR', 'QAR', 'INR', 'CAD', 'AUD'] as $c): ?>
                    <option value="<?= $c ?>" <?= $c === 'USD' ? 'selected' : '' ?>><?= $c ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold">Received Amount <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0.01" name="original_amount" id="payOriginalAmount" class="form-control form-control-sm fw-bold rounded-2" placeholder="0.00" required oninput="calcPaymentConverter(false)">
              </div>
              <div class="col-6 col-md-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label small fw-semibold mb-0">Exchange Rate</label>
                  <button type="button" class="btn btn-link btn-xs p-0 text-decoration-none" id="payInvertRateBtn" onclick="invertPaymentRate()" title="Invert rate (1 / rate)" style="font-size: 0.68rem;">
                    <i class="fa-solid fa-arrows-rotate me-0.5"></i> Invert
                  </button>
                </div>
                <input type="number" step="0.000001" name="exchange_rate" id="payExchangeRate" class="form-control form-control-sm text-end fw-bold rounded-2" value="1.000000" oninput="calcPaymentConverter(true)">
              </div>
              <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold">Settlement Currency</label>
                <select name="to_currency" id="payToCur" class="form-select form-select-sm fw-bold rounded-2" onchange="onPaymentCurrenciesChanged()">
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
              <input type="date" name="payment_date" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Payment Method <span class="text-danger">*</span></label>
              <select name="payment_method" class="form-select rounded-3" required>
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
              <input type="text" name="transaction_reference" class="form-control rounded-3" placeholder="Bank ref / cheque # / POS auth...">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Payment Slip / Cash Receipt Voucher <span class="text-danger">*</span></label>
              <input type="file" name="receipt_file" class="form-control rounded-3" accept=".pdf,.jpg,.jpeg,.png,.docx" required>
              <small class="text-muted" style="font-size: 0.72rem;"><i class="fa-solid fa-file-circle-check text-success me-1"></i>Mandatory for all payment methods (Bank slip, Deposit slip, Cash voucher, POS slip).</small>
            </div>
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold">Internal Notes / Purpose</label>
            <input type="text" name="notes" class="form-control rounded-3" placeholder="Optional notes for accounting...">
          </div>
        </div>
        <div class="modal-footer bg-light p-3 border-top">
          <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.25rem; font-size: 0.82rem;">
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
    setTimeout(function () {
      if (typeof openModalById === 'function') {
        openModalById('recordPaymentModal');
      } else if (window.bootstrap && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('recordPaymentModal')).show();
      } else {
        var m = document.getElementById('recordPaymentModal');
        if (m) {
          m.classList.add('show');
          m.style.display = 'block';
        }
      }
    }, 60);
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
  document.querySelectorAll('.pay-segmented button').forEach(btn => btn.classList.remove('active'));

  if (viewMode === 'grid') {
    const el = document.getElementById('paymentViewGrid');
    if (el) el.classList.remove('d-none');
    const btn = document.getElementById('btnPaymentViewGrid');
    if (btn) btn.classList.add('active');
  } else if (viewMode === 'compact') {
    const el = document.getElementById('paymentViewCompact');
    if (el) el.classList.remove('d-none');
    const btn = document.getElementById('btnPaymentViewCompact');
    if (btn) btn.classList.add('active');
  } else {
    const el = document.getElementById('paymentViewTable');
    if (el) el.classList.remove('d-none');
    const btn = document.getElementById('btnPaymentViewTable');
    if (btn) btn.classList.add('active');
  }

  try {
    localStorage.setItem('vt_payment_view', viewMode);
  } catch(e) {}
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
