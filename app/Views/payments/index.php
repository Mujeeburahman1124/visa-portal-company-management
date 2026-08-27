<?php
$pageTitle = 'Payments, Invoices & Online Links — VISA TRACK';
$flash = get_flash();
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
    <div class="d-flex flex-wrap gap-2">
      <a href="/payments/history" class="btn btn-outline-dark btn-sm px-3 fw-semibold shadow-sm">
        <i class="fa-solid fa-clock-rotate-left me-1"></i> Payment History
      </a>
      <a href="/payments/links" class="btn btn-outline-info btn-sm px-3 fw-semibold shadow-sm">
        <i class="fa-solid fa-list-check me-1"></i> Payment Links (<?= $metrics['total_online_links'] ?>)
      </a>
      <button type="button" class="btn btn-outline-primary btn-sm px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#generateLinkModal">
        <i class="fa-solid fa-link me-1"></i> Generate Link
      </button>
      <button type="button" class="btn btn-success btn-sm px-3 fw-semibold shadow-sm">
        <i class="fa-solid fa-credit-card me-1"></i> Record Payment
      </button>
    </div>
  </div>

  <!-- Financial KPI Metrics (100% Responsive Grid) -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-md-4 col-xl-2">
      <div class="stat-card p-3 h-100 shadow-sm">
        <div class="d-flex align-items-center gap-2 mb-2">
          <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success fs-5 p-2 rounded-3">
            <i class="fa-solid fa-money-bill-wave"></i>
          </div>
          <div class="stat-title text-muted text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.03em;">Total Collected</div>
        </div>
        <div class="stat-value text-success fw-bold fs-5 mb-0"><?= format_currency($metrics['total_received']) ?></div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-xl-2">
      <div class="stat-card p-3 h-100 shadow-sm">
        <div class="d-flex align-items-center gap-2 mb-2">
          <div class="stat-icon-wrapper bg-danger bg-opacity-10 text-danger fs-5 p-2 rounded-3">
            <i class="fa-solid fa-file-invoice-dollar"></i>
          </div>
          <div class="stat-title text-muted text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.03em;">Outstanding</div>
        </div>
        <div class="stat-value text-danger fw-bold fs-5 mb-0"><?= format_currency($metrics['outstanding']) ?></div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-xl-2">
      <div class="stat-card p-3 h-100 shadow-sm">
        <div class="d-flex align-items-center gap-2 mb-2">
          <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning fs-5 p-2 rounded-3">
            <i class="fa-solid fa-rotate-left"></i>
          </div>
          <div class="stat-title text-muted text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.03em;">Refunded</div>
        </div>
        <div class="stat-value text-warning fw-bold fs-5 mb-0"><?= format_currency($metrics['total_refunded']) ?></div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-xl-2">
      <div class="stat-card p-3 h-100 shadow-sm">
        <div class="d-flex align-items-center gap-2 mb-2">
          <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info fs-5 p-2 rounded-3">
            <i class="fa-solid fa-wallet"></i>
          </div>
          <div class="stat-title text-muted text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.03em;">Wallet Credits</div>
        </div>
        <div class="stat-value text-info fw-bold fs-5 mb-0"><?= format_currency($metrics['total_wallet_credits']) ?></div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-xl-2">
      <div class="stat-card p-3 h-100 shadow-sm">
        <div class="d-flex align-items-center gap-2 mb-2">
          <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary fs-5 p-2 rounded-3">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
          </div>
          <div class="stat-title text-muted text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.03em;">Wallet Debits</div>
        </div>
        <div class="stat-value text-primary fw-bold fs-5 mb-0"><?= format_currency($metrics['total_wallet_debits']) ?></div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-xl-2">
      <div class="stat-card p-3 h-100 shadow-sm">
        <div class="d-flex align-items-center gap-2 mb-2">
          <div class="stat-icon-wrapper bg-secondary bg-opacity-10 text-secondary fs-5 p-2 rounded-3">
            <i class="fa-solid fa-share-nodes"></i>
          </div>
          <div class="stat-title text-muted text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.03em;">Payment Links</div>
        </div>
        <div class="stat-value text-secondary fw-bold fs-5 mb-0"><?= $metrics['total_online_links'] ?> Links</div>
      </div>
    </div>
  </div>

  <!-- Multi-Filter & Search Bar (100% Responsive Toolbar) -->
  <div class="card card-enterprise mb-4 shadow-sm">
    <div class="card-body p-3">
      <form method="GET" action="/payments" class="row g-2 align-items-end">
        <div class="col-12 col-sm-6 col-md-4 col-xl-3">
          <label class="form-label small text-muted mb-1 fw-semibold">Search Keywords</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Receipt / Invoice / Name / Passport..." value="<?= e($_GET['search'] ?? '') ?>">
          </div>
        </div>
        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <label class="form-label small text-muted mb-1 fw-semibold">Supplier Channel</label>
          <select name="supplier_id" class="form-select form-select-sm">
            <option value="">-- All Suppliers --</option>
            <?php foreach ($suppliersList as $sup): ?>
              <option value="<?= $sup['id'] ?>" <?= ((int)($_GET['supplier_id'] ?? 0)) === (int)$sup['id'] ? 'selected' : '' ?>>
                <?= e($sup['company_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
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
        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
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
        <div class="col-12 col-sm-6 col-md-4 col-xl-3">
          <div class="btn-group btn-group-sm w-100 shadow-sm" role="group">
            <button type="submit" class="btn btn-primary fw-semibold">
              <i class="fa-solid fa-filter me-1"></i> Filter Records
            </button>
            <a href="/payments" class="btn btn-outline-secondary" title="Reset Filters">
              <i class="fa-solid fa-rotate-left"></i>
            </a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Payments & Invoices Table (100% Horizontal Responsive Grid) -->
  <div class="card card-enterprise shadow-sm">
    <div class="table-responsive" style="-webkit-overflow-scrolling: touch;">
      <table class="table table-hover align-middle mb-0 table-custom" style="font-size: 0.86rem; min-width: 1100px;">
        <thead class="table-light">
          <tr>
            <th style="width: 150px;" class="text-nowrap">Receipt / Invoice #</th>
            <th style="width: 190px;" class="text-nowrap">Applicant &amp; Passport</th>
            <th style="width: 220px;">Destination &amp; Visa Type</th>
            <th style="width: 150px;">Supplier</th>
            <th style="width: 140px;" class="text-nowrap">Date &amp; Method</th>
            <th style="width: 140px;" class="text-nowrap">Transaction Ref</th>
            <th style="width: 110px;" class="text-nowrap">Amount Paid</th>
            <th style="width: 110px;" class="text-nowrap">Status</th>
            <th style="width: 90px;" class="text-end text-nowrap">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($payments)): ?>
            <tr><td colspan="9" class="text-center py-5 text-muted">No payment transactions match the filter criteria.</td></tr>
          <?php else: ?>
            <?php foreach ($payments as $p): ?>
              <tr>
                <td class="text-nowrap">
                  <span class="fw-bold text-primary d-block"><?= e($p['payment_number']) ?></span>
                  <span class="badge bg-light text-dark border small mt-0.5"><?= e($p['invoice_number'] ?: 'INV-N/A') ?></span>
                </td>
                <td>
                  <div class="fw-bold text-dark text-truncate" style="max-width: 180px;" title="<?= e($p['customer_name']) ?>"><?= e($p['customer_name']) ?></div>
                  <div class="small text-muted text-nowrap">ID: <strong><?= e($p['customer_code']) ?></strong> &bull; Pass: <?= e($p['passport_number'] ?: 'N/A') ?></div>
                </td>
                <td>
                  <div class="fw-semibold text-dark text-truncate" style="max-width: 210px;"><?= $p['flag_emoji'] ?? '✈️' ?> <?= e($p['country_name'] ?? 'General') ?></div>
                  <div class="small text-muted text-truncate" style="max-width: 210px;" title="<?= e($p['service_name'] ?? 'Visa Service') ?>"><?= e($p['service_name'] ?? 'Visa Service') ?></div>
                </td>
                <td>
                  <span class="badge bg-light text-secondary border text-truncate" style="max-width: 140px;" title="<?= e($p['supplier_name'] ?: 'In-House / Direct') ?>"><?= e($p['supplier_name'] ?: 'In-House / Direct') ?></span>
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
                  <div class="btn-group btn-group-sm shadow-sm" role="group">
                    <a href="/payments/receipt?id=<?= $p['id'] ?>" target="_blank" class="btn btn-outline-secondary" title="Print Official Receipt">
                      <i class="fa-solid fa-print"></i>
                    </a>
                    <a href="/payments/invoice?app_id=<?= $p['application_id'] ?>" target="_blank" class="btn btn-outline-primary" title="View Full Tax Invoice">
                      <i class="fa-solid fa-file-invoice"></i>
                    </a>
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
              <?php foreach ($applicationsList as $ap): ?>
                <option value="<?= $ap['id'] ?>" 
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

          <!-- Step 3: Payment Details -->
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Payment Amount ($) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="1" name="amount" id="recordPaymentAmount" class="form-control fw-bold" placeholder="0.00" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Payment Date <span class="text-danger">*</span></label>
              <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-4">
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
              <label class="form-label small fw-semibold">Upload Payment Receipt</label>
              <input type="file" name="receipt_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
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
  document.getElementById('recordPaymentAmount').value = due.toFixed(2);
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
