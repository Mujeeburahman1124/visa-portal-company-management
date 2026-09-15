<?php
$pageTitle = 'Supplier Wallet & Advanced Accounting — MS TRAVEL HUB';
$flash = get_flash();

require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
?>

<div class="content-body">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Top Navigation & Supplier Selector -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div class="d-flex align-items-center gap-3">
      <div>
        <h3 class="fw-bold brand-font text-dark mb-0">
          <i class="fa-solid fa-wallet text-info me-2"></i> Supplier Wallet Ledger
        </h3>
        <p class="text-muted small mb-0">Live advance deposit balances, currency conversions, and automated settlement records.</p>
      </div>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
      <!-- Quick Supplier Switcher -->
      <form method="GET" action="/suppliers/wallet" class="d-inline-flex align-items-center gap-2 me-2">
        <label for="supplierSelect" class="small fw-semibold text-muted text-nowrap">Switch Supplier:</label>
        <select name="id" id="supplierSelect" class="form-select form-select-sm fw-semibold shadow-sm" style="min-width: 220px;" onchange="this.form.submit()">
          <?php foreach ($suppliers as $s): ?>
            <option value="<?= $s['id'] ?>" <?= (int)$s['id'] === (int)$supplierId ? 'selected' : '' ?>>
              <?= e($s['company_name']) ?> (<?= e($s['supplier_code']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </form>

      <a href="/suppliers" class="btn btn-outline-secondary btn-sm shadow-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Partners List
      </a>
      <a href="/suppliers/payments" class="btn btn-outline-primary btn-sm shadow-sm">
        <i class="fa-solid fa-receipt me-1"></i> Payment Ledger
      </a>
      <button type="button" class="btn btn-success btn-sm px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#topupSupplierModal">
        <i class="fa-solid fa-plus-circle me-1"></i> Top-up / Credit
      </button>
      <button type="button" class="btn btn-outline-danger btn-sm px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#debitSupplierModal">
        <i class="fa-solid fa-minus-circle me-1"></i> Debit / Payout
      </button>
    </div>
  </div>

  <?php if ($currentSupplier): ?>
    <!-- Partner Info Card -->
    <div class="card card-enterprise border-0 shadow-sm mb-4 bg-light">
      <div class="card-body p-3">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-circle bg-white shadow-sm p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="fa-solid fa-building text-primary fs-4"></i>
              </div>
              <div>
                <h5 class="fw-bold text-dark mb-0"><?= e($currentSupplier['company_name']) ?></h5>
                <div class="text-muted small">
                  <span class="badge bg-dark me-2"><?= e($currentSupplier['supplier_code']) ?></span>
                  <span><i class="fa-solid fa-globe me-1"></i><?= e($currentSupplier['country'] ?: 'International') ?></span>
                  <span class="mx-2">•</span>
                  <span><i class="fa-solid fa-envelope me-1"></i><?= e($currentSupplier['email'] ?: 'No email') ?></span>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6 text-md-end mt-2 mt-md-0">
            <span class="badge bg-success-subtle text-success border border-success px-3 py-2 fs-7 fw-semibold">
              <i class="fa-solid fa-shield-halved me-1"></i> Verified Active Consular Partner
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Financial KPI Summary Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card card-enterprise border-0 shadow-sm border-start border-info border-4 p-3">
          <div class="text-xs fw-bold text-info text-uppercase mb-1">Available Wallet Balance</div>
          <div class="h3 mb-0 fw-bold text-dark">
            <?= e($wallet['currency'] ?? 'AED') ?> <?= number_format((float)$wallet['current_balance'], 2) ?>
          </div>
          <div class="text-muted small mt-1">Prepaid advance credit balance</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card card-enterprise border-0 shadow-sm border-start border-success border-4 p-3">
          <div class="text-xs fw-bold text-success text-uppercase mb-1">Total Credited</div>
          <div class="h3 mb-0 fw-bold text-dark">
            <?= e($wallet['currency'] ?? 'AED') ?> <?= number_format((float)$wallet['total_credited'], 2) ?>
          </div>
          <div class="text-muted small mt-1">Cumulative deposits &amp; credits</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card card-enterprise border-0 shadow-sm border-start border-danger border-4 p-3">
          <div class="text-xs fw-bold text-danger text-uppercase mb-1">Total Debited</div>
          <div class="h3 mb-0 fw-bold text-dark">
            <?= e($wallet['currency'] ?? 'AED') ?> <?= number_format((float)$wallet['total_debited'], 2) ?>
          </div>
          <div class="text-muted small mt-1">Application settlements &amp; payouts</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card card-enterprise border-0 shadow-sm border-start border-warning border-4 p-3">
          <div class="text-xs fw-bold text-warning text-uppercase mb-1">Direct Invoiced Outstanding</div>
          <div class="h3 mb-0 fw-bold text-dark">
            AED <?= number_format($outstanding, 2) ?>
          </div>
          <div class="text-muted small mt-1">Pending payments across applications</div>
        </div>
      </div>
    </div>

    <!-- Wallet Transaction Ledger -->
    <div class="card card-enterprise shadow-sm border">
      <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-dark mb-0">
          <i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i> Transaction History &amp; Audit Trail
        </h6>
        <span class="badge bg-light text-dark border"><?= count($transactions) ?> Entries</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 font-sm">
          <thead class="table-light">
            <tr>
              <th>Txn Reference</th>
              <th>Date &amp; Time</th>
              <th>Type</th>
              <th>Amount</th>
              <th>Exchange Rate / Orig</th>
              <th>Method &amp; Ref</th>
              <th>Balance After</th>
              <th>Description / Notes</th>
              <th>Processed By</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($transactions)): ?>
              <tr>
                <td colspan="9" class="text-center py-5 text-muted">
                  <i class="fa-solid fa-receipt fs-2 mb-2 d-block text-muted"></i>
                  No transactions recorded for this supplier wallet yet.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($transactions as $t): ?>
                <tr>
                  <td>
                    <span class="badge bg-light text-dark border font-monospace"><?= e($t['transaction_id']) ?></span>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark"><?= date('d M Y', strtotime($t['created_at'])) ?></div>
                    <div class="text-muted small"><?= date('H:i A', strtotime($t['created_at'])) ?></div>
                  </td>
                  <td>
                    <?php if ($t['transaction_type'] === 'Credit'): ?>
                      <span class="badge bg-success-subtle text-success border border-success"><i class="fa-solid fa-arrow-down me-1"></i>Credit</span>
                    <?php else: ?>
                      <span class="badge bg-danger-subtle text-danger border border-danger"><i class="fa-solid fa-arrow-up me-1"></i>Debit</span>
                    <?php endif; ?>
                  </td>
                  <td class="fw-bold <?= $t['transaction_type'] === 'Credit' ? 'text-success' : 'text-danger' ?>">
                    <?= $t['transaction_type'] === 'Credit' ? '+' : '-' ?><?= e($t['currency'] ?: 'AED') ?> <?= number_format((float)$t['amount'], 2) ?>
                  </td>
                  <td>
                    <?php if (!empty($t['original_amount']) && (float)$t['exchange_rate'] > 0 && (float)$t['exchange_rate'] != 1.0): ?>
                      <div class="small fw-semibold text-dark"><?= number_format((float)$t['original_amount'], 2) ?></div>
                      <div class="text-muted" style="font-size: 0.72rem;">@ <?= number_format((float)$t['exchange_rate'], 4) ?></div>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="small fw-semibold text-dark"><?= e($t['payment_method'] ?: 'Direct Ledger') ?></div>
                    <?php if (!empty($t['reference'])): ?>
                      <div class="text-muted small font-monospace"><?= e($t['reference']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="fw-semibold text-dark">
                    <?= e($t['currency'] ?: 'AED') ?> <?= number_format((float)$t['balance_after'], 2) ?>
                  </td>
                  <td class="text-muted small" style="max-width: 250px;">
                    <?= e($t['description']) ?>
                  </td>
                  <td>
                    <span class="small text-muted"><?= e($t['created_by_name'] ?? 'System') ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL: Top-Up / Credit Supplier Wallet -->
    <div class="modal fade" id="topupSupplierModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle me-2"></i> Credit Supplier Wallet</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form action="/suppliers/wallet/topup" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="supplier_id" value="<?= $currentSupplier['id'] ?>">
            <div class="modal-body p-4">
              <div class="alert alert-info border-0 small py-2 mb-3">
                Crediting wallet for: <strong><?= e($currentSupplier['company_name']) ?></strong> (<?= e($currentSupplier['supplier_code']) ?>)
              </div>

              <!-- Multi-Currency & Conversion Section -->
              <div class="row g-2 mb-3">
                <div class="col-6">
                  <label class="form-label small fw-semibold">Input Currency</label>
                  <select name="currency" id="supTopCur" class="form-select form-select-sm fw-bold" onchange="calcSupTopup()">
                    <?php foreach (['AED', 'USD', 'EUR', 'GBP', 'SAR', 'QAR', 'INR', 'LKR', 'CAD', 'AUD'] as $c): ?>
                      <option value="<?= $c ?>" <?= $c === ($wallet['currency'] ?? 'AED') ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-6">
                  <label class="form-label small fw-semibold">Amount <span class="text-danger">*</span></label>
                  <input type="number" step="0.01" name="amount" id="supTopAmount" class="form-control form-control-sm fw-bold" placeholder="0.00" required oninput="calcSupTopup()">
                </div>
              </div>

              <div class="p-3 bg-light rounded border mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="small fw-semibold text-muted">Exchange Rate to Wallet (<?= e($wallet['currency'] ?? 'AED') ?>):</span>
                  <input type="number" step="0.000001" name="exchange_rate" id="supTopRate" class="form-control form-control-sm text-end fw-bold" style="width: 120px;" value="1.000000" oninput="calcSupTopup()">
                </div>
                <div class="d-flex justify-content-between align-items-center">
                  <span class="small fw-bold text-dark">Total Credited to Wallet:</span>
                  <span class="h6 fw-bold text-success mb-0" id="supTopConverted">0.00 <?= e($wallet['currency'] ?? 'AED') ?></span>
                </div>
                <input type="hidden" name="converted_amount" id="supTopConvertedHidden" value="0.00">
                <input type="hidden" name="original_amount" id="supTopOriginalHidden" value="0.00">
              </div>

              <div class="mb-3">
                <label class="form-label small fw-semibold">Payment Method</label>
                <select name="payment_method" class="form-select form-select-sm">
                  <option value="Bank Transfer">Bank Wire / Transfer</option>
                  <option value="Corporate Card">Corporate Card</option>
                  <option value="Cash Disbursement">Cash Disbursement</option>
                  <option value="Cheque / Draft">Cheque / Banker's Draft</option>
                  <option value="Credit Note">Credit Note / Offset</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-semibold">Transaction Reference</label>
                <input type="text" name="reference" class="form-control form-control-sm" placeholder="e.g. TR-<?= date('Ymd') ?>-001">
              </div>

              <div class="mb-0">
                <label class="form-label small fw-semibold">Notes / Purpose</label>
                <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="e.g. Advance deposit for Q4 bulk visa allocations"></textarea>
              </div>
            </div>
            <div class="modal-footer bg-light">
              <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-success btn-sm px-4 fw-semibold">
                <i class="fa-solid fa-check me-1"></i> Confirm Credit
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- MODAL: Debit / Payout Supplier Wallet -->
    <div class="modal fade" id="debitSupplierModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title fw-bold"><i class="fa-solid fa-minus-circle me-2"></i> Debit Supplier Wallet</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form action="/suppliers/wallet/deduct" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="supplier_id" value="<?= $currentSupplier['id'] ?>">
            <div class="modal-body p-4">
              <div class="alert alert-warning border-0 small py-2 mb-3">
                Debiting from: <strong><?= e($currentSupplier['company_name']) ?></strong><br>
                Available Balance: <strong><?= e($wallet['currency'] ?? 'AED') ?> <?= number_format((float)$wallet['current_balance'], 2) ?></strong>
              </div>

              <div class="row g-2 mb-3">
                <div class="col-6">
                  <label class="form-label small fw-semibold">Currency</label>
                  <select name="currency" class="form-select form-select-sm fw-bold">
                    <option value="<?= e($wallet['currency'] ?? 'AED') ?>"><?= e($wallet['currency'] ?? 'AED') ?></option>
                  </select>
                </div>
                <div class="col-6">
                  <label class="form-label small fw-semibold">Debit Amount <span class="text-danger">*</span></label>
                  <input type="number" step="0.01" max="<?= (float)$wallet['current_balance'] ?>" name="amount" class="form-control form-control-sm fw-bold" placeholder="0.00" required>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-semibold">Debit Reason / Settlement</label>
                <select name="payment_method" class="form-select form-select-sm">
                  <option value="Application Settlement">Application Fee Settlement</option>
                  <option value="Supplier Refund">Supplier Balance Refund</option>
                  <option value="Correction Offset">Correction / Balance Adjustment</option>
                  <option value="Bank Withdrawal">Bank Withdrawal</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-semibold">Reference / Application #</label>
                <input type="text" name="reference" class="form-control form-control-sm" placeholder="e.g. SETTLE-<?= date('Ymd') ?>">
              </div>

              <div class="mb-0">
                <label class="form-label small fw-semibold">Notes</label>
                <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="e.g. Settlement for visa issuance batch #42" required></textarea>
              </div>
            </div>
            <div class="modal-footer bg-light">
              <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-danger btn-sm px-4 fw-semibold">
                <i class="fa-solid fa-check me-1"></i> Confirm Debit
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script>
    function calcSupTopup() {
      const amt = parseFloat(document.getElementById('supTopAmount').value) || 0;
      const rate = parseFloat(document.getElementById('supTopRate').value) || 1;
      const targetCur = '<?= e($wallet['currency'] ?? 'AED') ?>';
      const converted = (amt * rate).toFixed(2);

      document.getElementById('supTopConverted').innerText = converted + ' ' + targetCur;
      document.getElementById('supTopConvertedHidden').value = converted;
      document.getElementById('supTopOriginalHidden').value = amt;
    }
    </script>
  <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
