<?php
$pageTitle = 'My Wallet & Advance Payments — VISA TRACK';
$flash = get_flash();
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/navbar.php';
?>

<div class="container py-4">
  <!-- Flash Alert -->
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Wallet Overview Banner -->
  <div class="row g-4 mb-4">
    <div class="col-lg-8">
      <div class="card card-enterprise shadow-sm p-4 border-0 text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #06b6d4 100%);">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div>
            <div class="text-white-50 small fw-semibold text-uppercase letter-spacing-1">Available Pre-funded Balance</div>
            <h1 class="display-5 fw-bold text-white mb-1"><?= format_currency((float)$wallet['current_balance']) ?></h1>
            <div class="text-white-50 small">Customer Account: <span class="text-white fw-bold"><?= e($customer['customer_code']) ?></span> &bull; Currency: <span class="badge bg-white text-primary fw-bold">USD</span></div>
          </div>
          <div class="d-flex flex-column gap-2">
            <button class="btn btn-light fw-bold text-primary px-4 py-2.5 shadow-sm" data-bs-toggle="modal" data-bs-target="#depositModal">
              <i class="fa-solid fa-plus-circle me-1.5"></i> Add Funds / Deposit
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="row g-3 h-100">
        <div class="col-6 col-lg-12">
          <div class="card card-enterprise shadow-sm p-3 border">
            <div class="d-flex align-items-center gap-3">
              <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 fs-4"><i class="fa-solid fa-arrow-down-left"></i></div>
              <div>
                <div class="text-muted small">Total Lifetime Credited</div>
                <h5 class="fw-bold text-success mb-0"><?= format_currency((float)$wallet['total_credited']) ?></h5>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-12">
          <div class="card card-enterprise shadow-sm p-3 border">
            <div class="d-flex align-items-center gap-3">
              <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-3 fs-4"><i class="fa-solid fa-arrow-up-right"></i></div>
              <div>
                <div class="text-muted small">Total Visa Debits</div>
                <h5 class="fw-bold text-danger mb-0"><?= format_currency((float)$wallet['total_debited']) ?></h5>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Wallet Ledger Statement -->
  <div class="card card-enterprise shadow-sm border-0">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
      <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-receipt text-primary me-2"></i> Transaction History &amp; Ledger Statement</h5>
      <span class="badge bg-light text-secondary border"><?= count($transactions) ?> Entries</span>
    </div>
    <div class="card-body p-0">
      <?php if (empty($transactions)): ?>
        <div class="text-center py-5 text-muted">
          <i class="fa-solid fa-wallet fa-3x mb-3 opacity-25"></i>
          <h5>No Wallet Transactions</h5>
          <p class="small">Your wallet activity, deposits, and visa processing fee debits will appear here.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
              <tr>
                <th>Transaction ID</th>
                <th>Date &amp; Time</th>
                <th>Type</th>
                <th>Description</th>
                <th>Application</th>
                <th class="text-end">Amount</th>
                <th class="text-end">Balance After</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($transactions as $t): ?>
                <?php $isCredit = $t['transaction_type'] === 'Credit'; ?>
                <tr>
                  <td><span class="fw-bold text-dark font-monospace"><?= e($t['transaction_id']) ?></span></td>
                  <td class="text-muted"><?= format_datetime($t['created_at']) ?></td>
                  <td>
                    <span class="badge <?= $isCredit ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> fw-bold border">
                      <i class="fa-solid <?= $isCredit ? 'fa-arrow-down me-1' : 'fa-arrow-up me-1' ?>"></i><?= e($t['transaction_type']) ?>
                    </span>
                  </td>
                  <td><?= e($t['description']) ?></td>
                  <td>
                    <?php if (!empty($t['application_number'])): ?>
                      <span class="badge bg-light text-primary border"><?= e($t['application_number']) ?></span>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end fw-bold <?= $isCredit ? 'text-success' : 'text-danger' ?>">
                    <?= $isCredit ? '+' : '-' ?><?= format_currency((float)$t['amount']) ?>
                  </td>
                  <td class="text-end fw-bold text-dark"><?= format_currency((float)$t['balance_after']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal: Deposit Funds -->
<div class="modal fade" id="depositModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold fs-6"><i class="fa-solid fa-wallet me-2"></i>Add Funds to Your Wallet</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/portal/wallet/deposit" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Deposit Amount ($ USD) <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input type="number" step="0.01" min="1" name="amount" class="form-control form-control-lg fw-bold text-primary" placeholder="100.00" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Payment Method</label>
            <select name="payment_method" class="form-select">
              <option value="Credit Card">Credit Card / Debit Card (Online)</option>
              <option value="Bank Transfer">Bank Wire Transfer</option>
              <option value="Cash Deposit">Cash Deposit at Branch</option>
            </select>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Transaction Reference / Slip # (Optional)</label>
            <input type="text" name="transaction_reference" class="form-control" placeholder="e.g. WIRE-998822">
          </div>
        </div>
        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold px-4"><i class="fa-solid fa-check me-1"></i>Confirm Deposit</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
