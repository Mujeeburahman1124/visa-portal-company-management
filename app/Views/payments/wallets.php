<?php
$pageTitle = 'Digital Wallets & Master Ledgers — MS TRAVEL HUB';
$flash = get_flash();
$activeTab = $_GET['tab'] ?? 'customers';

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

  <!-- Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <h3 class="fw-bold brand-font text-dark mb-0">Wallets &amp; Financial Ledgers</h3>
      <p class="text-muted small mb-0">Centralized accounting ledger for customer prepayments, agent credit limits &amp; multi-currency conversions.</p>
    </div>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-outline-primary px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#customerWalletModal">
        <i class="fa-solid fa-wallet me-1"></i> Top-up Customer
      </button>
      <button type="button" class="btn btn-outline-info px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#agentWalletModal">
        <i class="fa-solid fa-user-tie me-1"></i> Top-up Agent
      </button>
    </div>
  </div>

  <!-- Tabs Navigation -->
  <ul class="nav nav-tabs mb-4" id="walletTabs" role="tablist">
    <li class="nav-item">
      <a class="nav-link <?= $activeTab === 'customers' ? 'active fw-bold' : '' ?>" href="/payments/wallets?tab=customers">
        <i class="fa-solid fa-user me-1 text-primary"></i> Customer Wallets (<?= count($customerWallets) ?>)
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $activeTab === 'agents' ? 'active fw-bold' : '' ?>" href="/payments/wallets?tab=agents">
        <i class="fa-solid fa-user-tie me-1 text-info"></i> Agent Wallets (<?= count($agentWallets) ?>)
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $activeTab === 'transactions' ? 'active fw-bold' : '' ?>" href="/payments/wallets?tab=transactions">
        <i class="fa-solid fa-list-check me-1 text-warning"></i> Recent Audit Ledger (<?= count($recentTransactions) ?>)
      </a>
    </li>
  </ul>

  <!-- Wallets & Ledger Multi-Criteria Filter Bar -->
  <div class="card card-enterprise shadow-sm border mb-4">
    <div class="card-body p-3">
      <form action="/payments/wallets" method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="tab" value="<?= e($activeTab) ?>">

        <div class="col-12 col-md-3">
          <label class="form-label small fw-semibold text-secondary mb-1">Search Keywords</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Search name, code, txn ref..." value="<?= e($_GET['search'] ?? '') ?>">
          </div>
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label small fw-semibold text-secondary mb-1">Date From</label>
          <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($_GET['date_from'] ?? '') ?>">
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label small fw-semibold text-secondary mb-1">Date To</label>
          <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($_GET['date_to'] ?? '') ?>">
        </div>

        <?php if ($activeTab === 'transactions'): ?>
          <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-secondary mb-1">Transaction Type</label>
            <select name="type" class="form-select form-select-sm">
              <option value="">All Types</option>
              <option value="credit" <?= strtolower($_GET['type'] ?? '') === 'credit' ? 'selected' : '' ?>>Credit</option>
              <option value="debit" <?= strtolower($_GET['type'] ?? '') === 'debit' ? 'selected' : '' ?>>Debit</option>
              <option value="refund" <?= strtolower($_GET['type'] ?? '') === 'refund' ? 'selected' : '' ?>>Refund</option>
              <option value="adjustment" <?= strtolower($_GET['type'] ?? '') === 'adjustment' ? 'selected' : '' ?>>Adjustment</option>
            </select>
          </div>
        <?php endif; ?>

        <div class="col-12 <?= $activeTab === 'transactions' ? 'col-md-3' : 'col-md-5' ?> d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm flex-fill fw-semibold shadow-sm">
            <i class="fa-solid fa-filter me-1"></i> Filter
          </button>
          <a href="/payments/wallets?tab=<?= e($activeTab) ?>" class="btn btn-light btn-sm border text-secondary" title="Reset Filters">
            <i class="fa-solid fa-rotate-left me-1"></i> Reset
          </a>
        </div>
      </form>
    </div>
  </div>

  <?php if ($activeTab === 'customers'): ?>
    <!-- Customer Wallets -->
    <div class="card card-enterprise shadow-sm border">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 font-sm">
          <thead class="table-light">
            <tr>
              <th>Customer / Applicant</th>
              <th>Customer Code</th>
              <th>Currency</th>
              <th>Current Balance</th>
              <th>Total Credited</th>
              <th>Total Debited</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($customerWallets)): ?>
              <tr><td colspan="7" class="text-center py-4 text-muted">No customer wallet accounts initialized.</td></tr>
            <?php else: ?>
              <?php foreach ($customerWallets as $cw): ?>
                <tr>
                  <td>
                    <div class="fw-bold text-dark"><?= e($cw['full_name']) ?></div>
                    <div class="text-muted small"><?= e($cw['email'] ?: $cw['mobile']) ?></div>
                  </td>
                  <td><span class="badge bg-light text-dark border font-monospace"><?= e($cw['customer_code']) ?></span></td>
                  <td><span class="badge bg-primary-subtle text-primary"><?= e($cw['currency'] ?: 'USD') ?></span></td>
                  <td>
                    <span class="fw-bold fs-6 <?= (float)$cw['current_balance'] > 0 ? 'text-success' : 'text-muted' ?>">
                      <?= e($cw['currency'] ?: 'USD') ?> <?= number_format((float)$cw['current_balance'], 2) ?>
                    </span>
                  </td>
                  <td class="text-success small fw-semibold"><?= e($cw['currency'] ?: 'USD') ?> <?= number_format((float)$cw['total_credited'], 2) ?></td>
                  <td class="text-danger small fw-semibold"><?= e($cw['currency'] ?: 'USD') ?> <?= number_format((float)$cw['total_debited'], 2) ?></td>
                  <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" onclick="quickTopUpCustomer(<?= $cw['customer_id'] ?>, '<?= e(addslashes($cw['full_name'])) ?>', '<?= e($cw['currency'] ?: 'USD') ?>')">
                      <i class="fa-solid fa-plus me-1"></i> Top-up
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 ms-1" onclick="quickDebitCustomer(<?= $cw['customer_id'] ?>, '<?= e(addslashes($cw['full_name'])) ?>', '<?= e($cw['currency'] ?: 'USD') ?>', <?= (float)$cw['current_balance'] ?>)">
                      <i class="fa-solid fa-minus me-1"></i> Debit
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php elseif ($activeTab === 'suppliers'): ?>
    <!-- Supplier Wallets -->
    <div class="card card-enterprise shadow-sm border">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 font-sm">
          <thead class="table-light">
            <tr>
              <th>Supplier Partner</th>
              <th>Company Name</th>
              <th>Country</th>
              <th>Currency</th>
              <th>Current Balance</th>
              <th>Total Credited</th>
              <th>Total Debited</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($supplierWallets)): ?>
              <tr><td colspan="8" class="text-center py-4 text-muted">No supplier wallet accounts initialized.</td></tr>
            <?php else: ?>
              <?php foreach ($supplierWallets as $sw): ?>
                <tr>
                  <td><div class="fw-bold text-dark"><?= e($sw['supplier_name']) ?></div></td>
                  <td><span class="text-muted small"><?= e($sw['company_name'] ?: '—') ?></span></td>
                  <td><span class="badge bg-light text-dark border"><?= e($sw['country'] ?: 'Global') ?></span></td>
                  <td><span class="badge bg-success-subtle text-success"><?= e($sw['currency'] ?: 'USD') ?></span></td>
                  <td>
                    <span class="fw-bold fs-6 <?= (float)$sw['current_balance'] > 0 ? 'text-success' : 'text-muted' ?>">
                      <?= e($sw['currency'] ?: 'USD') ?> <?= number_format((float)$sw['current_balance'], 2) ?>
                    </span>
                  </td>
                  <td class="text-success small fw-semibold"><?= e($sw['currency'] ?: 'USD') ?> <?= number_format((float)$sw['total_credited'], 2) ?></td>
                  <td class="text-danger small fw-semibold"><?= e($sw['currency'] ?: 'USD') ?> <?= number_format((float)$sw['total_debited'], 2) ?></td>
                  <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-success py-1 px-2" onclick="quickTopUpSupplier(<?= $sw['supplier_id'] ?>, '<?= e(addslashes($sw['supplier_name'])) ?>', '<?= e($sw['currency'] ?: 'AED') ?>')">
                      <i class="fa-solid fa-plus me-1"></i> Top-up
                    </button>
                    <a href="/suppliers/wallet?id=<?= $sw['supplier_id'] ?>" class="btn btn-sm btn-outline-info py-1 px-2 ms-1" title="Supplier Wallet & Ledger">
                      <i class="fa-solid fa-eye me-1"></i> Ledger
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php elseif ($activeTab === 'agents'): ?>
    <!-- Agent Wallets -->
    <div class="card card-enterprise shadow-sm border">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 font-sm">
          <thead class="table-light">
            <tr>
              <th>Agent Name</th>
              <th>Email</th>
              <th>Currency</th>
              <th>Current Balance</th>
              <th>Total Credited</th>
              <th>Total Debited</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($agentWallets)): ?>
              <tr><td colspan="7" class="text-center py-4 text-muted">No agent wallet accounts initialized.</td></tr>
            <?php else: ?>
              <?php foreach ($agentWallets as $aw): ?>
                <tr>
                  <td><div class="fw-bold text-dark"><?= e($aw['agent_name']) ?></div></td>
                  <td><span class="text-muted small"><?= e($aw['agent_email']) ?></span></td>
                  <td><span class="badge bg-info-subtle text-info"><?= e($aw['currency'] ?: 'USD') ?></span></td>
                  <td>
                    <span class="fw-bold fs-6 <?= (float)$aw['current_balance'] > 0 ? 'text-info' : 'text-muted' ?>">
                      <?= e($aw['currency'] ?: 'USD') ?> <?= number_format((float)$aw['current_balance'], 2) ?>
                    </span>
                  </td>
                  <td class="text-success small fw-semibold"><?= e($aw['currency'] ?: 'USD') ?> <?= number_format((float)$aw['total_credited'], 2) ?></td>
                  <td class="text-danger small fw-semibold"><?= e($aw['currency'] ?: 'USD') ?> <?= number_format((float)$aw['total_debited'], 2) ?></td>
                  <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-info py-1 px-2" onclick="quickTopUpAgent(<?= $aw['agent_id'] ?>, '<?= e(addslashes($aw['agent_name'])) ?>', '<?= e($aw['currency'] ?: 'USD') ?>')">
                      <i class="fa-solid fa-plus me-1"></i> Top-up
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 ms-1" onclick="quickDebitAgent(<?= $aw['agent_id'] ?>, '<?= e(addslashes($aw['agent_name'])) ?>', '<?= e($aw['currency'] ?: 'USD') ?>', <?= (float)$aw['current_balance'] ?>)">
                      <i class="fa-solid fa-minus me-1"></i> Debit
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php elseif ($activeTab === 'transactions'): ?>
    <!-- Audit Ledger Transactions -->
    <div class="card card-enterprise shadow-sm border">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 font-sm">
          <thead class="table-light">
            <tr>
              <th>Txn ID</th>
              <th>Date &amp; Time</th>
              <th>Customer / Entity</th>
              <th>Type</th>
              <th>Amount</th>
              <th>Conversion / Orig</th>
              <th>Method &amp; Ref</th>
              <th>Balance After</th>
              <th>Description</th>
              <th>Processed By</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentTransactions)): ?>
              <tr><td colspan="10" class="text-center py-4 text-muted">No wallet transactions found.</td></tr>
            <?php else: ?>
              <?php foreach ($recentTransactions as $tx): ?>
                <tr>
                  <td><span class="badge bg-light text-dark border font-monospace"><?= e($tx['transaction_id']) ?></span></td>
                  <td>
                    <div class="fw-semibold text-dark"><?= date('d M Y', strtotime($tx['created_at'])) ?></div>
                    <div class="text-muted small"><?= date('H:i A', strtotime($tx['created_at'])) ?></div>
                  </td>
                  <td>
                    <div class="fw-bold text-dark"><?= e($tx['customer_name'] ?? 'Account User') ?></div>
                  </td>
                  <td>
                    <?php if ($tx['transaction_type'] === 'Credit'): ?>
                      <span class="badge bg-success-subtle text-success border border-success"><i class="fa-solid fa-arrow-down me-1"></i>Credit</span>
                    <?php else: ?>
                      <span class="badge bg-danger-subtle text-danger border border-danger"><i class="fa-solid fa-arrow-up me-1"></i>Debit</span>
                    <?php endif; ?>
                  </td>
                  <td class="fw-bold <?= $tx['transaction_type'] === 'Credit' ? 'text-success' : 'text-danger' ?>">
                    <?= $tx['transaction_type'] === 'Credit' ? '+' : '-' ?><?= e($tx['currency'] ?? 'USD') ?> <?= number_format((float)$tx['amount'], 2) ?>
                  </td>
                  <td>
                    <?php if (!empty($tx['original_amount']) && (float)$tx['exchange_rate'] > 0 && (float)$tx['exchange_rate'] != 1.0): ?>
                      <div class="small fw-semibold text-dark"><?= number_format((float)$tx['original_amount'], 2) ?></div>
                      <div class="text-muted" style="font-size: 0.72rem;">@ <?= number_format((float)$tx['exchange_rate'], 4) ?></div>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="small fw-semibold text-dark"><?= e($tx['payment_method'] ?: 'Direct Ledger') ?></div>
                    <?php if (!empty($tx['reference'])): ?>
                      <div class="text-muted small font-monospace"><?= e($tx['reference']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="fw-semibold text-dark"><?= e($tx['currency'] ?? 'USD') ?> <?= number_format((float)$tx['balance_after'], 2) ?></td>
                  <td class="small text-muted" style="max-width: 220px;"><?= e($tx['description']) ?></td>
                  <td><span class="small text-muted"><?= e($tx['created_by_name'] ?? 'System') ?></span></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Modal: Top-up Customer Wallet (with Currency Converter) -->
<div class="modal fade" id="customerWalletModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-wallet me-2"></i> Top-up Customer Wallet</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/payments/wallet-deposit" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Customer / Applicant <span class="text-danger">*</span></label>
            <select name="customer_id" id="topupCustId" class="form-select form-select-sm" required>
              <option value="">-- Select Customer --</option>
              <?php foreach ($customersList as $c): ?>
                <option value="<?= $c['id'] ?>"><?= e($c['full_name']) ?> (<?= e($c['customer_code']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Multi-Currency Conversion Inputs -->
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Received Currency</label>
              <select name="currency" id="topupCustCur" class="form-select form-select-sm fw-bold" onchange="calcCustTopup()">
                <?php foreach (['USD', 'AED', 'LKR', 'EUR', 'GBP', 'SAR', 'QAR', 'INR', 'CAD', 'AUD'] as $cur): ?>
                  <option value="<?= $cur ?>"><?= $cur ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Received Amount <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="amount" id="topupCustAmount" class="form-control form-control-sm fw-bold" placeholder="0.00" required oninput="calcCustTopup()">
            </div>
          </div>

          <div class="p-3 bg-light rounded border mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="small fw-semibold text-muted">Exchange Rate (to Wallet Currency):</span>
              <input type="number" step="0.000001" name="exchange_rate" id="topupCustRate" class="form-control form-control-sm text-end fw-bold" style="width: 120px;" value="1.000000" oninput="calcCustTopup()">
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="small fw-bold text-dark">Credited to Customer Wallet:</span>
              <span class="h6 fw-bold text-primary mb-0" id="topupCustConverted">0.00</span>
            </div>
            <input type="hidden" name="converted_amount" id="topupCustConvertedHidden" value="0.00">
            <input type="hidden" name="original_amount" id="topupCustOriginalHidden" value="0.00">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Payment Method</label>
            <select name="payment_method" class="form-select form-select-sm">
              <option value="Cash at Office">Cash at Office</option>
              <option value="Bank Transfer">Bank Transfer</option>
              <option value="Credit Card">Credit Card</option>
              <option value="Online Gateway">Online Gateway</option>
              <option value="Cheque">Cheque Deposit</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Reference / Receipt Number</label>
            <input type="text" name="transaction_reference" class="form-control form-control-sm" placeholder="e.g. REC-<?= date('Ymd') ?>-01">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Bank Slip / Deposit Receipt Voucher <span class="text-danger">*</span></label>
            <input type="file" name="receipt_file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.docx" required>
            <small class="text-muted" style="font-size: 0.72rem;"><i class="fa-solid fa-file-circle-check text-success me-1"></i>Mandatory deposit proof voucher.</small>
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold">Notes / Purpose</label>
            <input type="text" name="notes" class="form-control form-control-sm" placeholder="e.g. Advance deposit for upcoming tourist visa application">
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold"><i class="fa-solid fa-check me-1"></i> Process Top-up</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Debit Customer Wallet -->
<div class="modal fade" id="customerDebitModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-minus-circle me-2"></i> Debit Customer Wallet</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/payments/wallet-debit" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="customer_id" id="debitCustId">
        <div class="modal-body p-4">
          <div class="alert alert-warning border-0 small py-2 mb-3">
            Customer: <strong id="debitCustName">—</strong><br>
            Current Balance: <strong id="debitCustBalance">—</strong>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Debit Amount <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="amount" id="debitCustAmount" class="form-control form-control-sm fw-bold" placeholder="0.00" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Debit Reason / Type</label>
            <select name="reason" class="form-select form-select-sm">
              <option value="Application Fee Settlement">Application Fee Settlement</option>
              <option value="Manual Balance Refund">Manual Balance Refund to Customer</option>
              <option value="Penalty / Cancellation Offset">Penalty / Cancellation Offset</option>
              <option value="Accounting Correction">Accounting Correction / Adjustment</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Reference / Application #</label>
            <input type="text" name="reference" class="form-control form-control-sm" placeholder="e.g. APP-<?= date('Ymd') ?>">
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold">Notes</label>
            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="e.g. Customer requested refund or offset for cancelled application" required></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger btn-sm px-4 fw-semibold"><i class="fa-solid fa-check me-1"></i> Confirm Debit</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Top-up Supplier Wallet -->
<div class="modal fade" id="supplierWalletModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-building me-2"></i> Top-up Supplier Wallet</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/payments/supplier-wallet-deposit" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Supplier Partner <span class="text-danger">*</span></label>
            <select name="supplier_id" id="topupSupId" class="form-select form-select-sm" required>
              <option value="">-- Select Supplier --</option>
              <?php foreach ($suppliersList as $s): ?>
                <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Disbursed Currency</label>
              <select name="currency" id="topupSupCur" class="form-select form-select-sm fw-bold" onchange="calcSupModalTopup()">
                <?php foreach (['AED', 'USD', 'EUR', 'GBP', 'SAR', 'QAR', 'INR', 'LKR', 'CAD', 'AUD'] as $cur): ?>
                  <option value="<?= $cur ?>"><?= $cur ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Amount <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="amount" id="topupSupAmount" class="form-control form-control-sm fw-bold" placeholder="0.00" required oninput="calcSupModalTopup()">
            </div>
          </div>

          <div class="p-3 bg-light rounded border mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="small fw-semibold text-muted">Exchange Rate:</span>
              <input type="number" step="0.000001" name="exchange_rate" id="topupSupRate" class="form-control form-control-sm text-end fw-bold" style="width: 120px;" value="1.000000" oninput="calcSupModalTopup()">
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="small fw-bold text-dark">Credited to Wallet:</span>
              <span class="h6 fw-bold text-success mb-0" id="topupSupConverted">0.00</span>
            </div>
            <input type="hidden" name="converted_amount" id="topupSupConvertedHidden" value="0.00">
            <input type="hidden" name="original_amount" id="topupSupOriginalHidden" value="0.00">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Payment Method</label>
            <select name="payment_method" class="form-select form-select-sm">
              <option value="Bank Transfer">Bank Transfer / Wire</option>
              <option value="Corporate Card">Corporate Card</option>
              <option value="Cash Disbursement">Cash Disbursement</option>
              <option value="Cheque">Cheque</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Reference</label>
            <input type="text" name="reference" class="form-control form-control-sm" placeholder="e.g. SW-<?= date('Ymd') ?>-01">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Disbursement Voucher / Bank Slip <span class="text-danger">*</span></label>
            <input type="file" name="receipt_file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.docx" required>
            <small class="text-muted" style="font-size: 0.72rem;"><i class="fa-solid fa-file-circle-check text-success me-1"></i>Mandatory deposit proof voucher.</small>
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold">Notes / Purpose</label>
            <input type="text" name="notes" class="form-control form-control-sm" placeholder="e.g. Advance deposit for Q4 bulk visa allocations">
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success btn-sm px-4 fw-semibold"><i class="fa-solid fa-check me-1"></i> Credit Supplier</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Top-up Agent Wallet -->
<div class="modal fade" id="agentWalletModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-tie me-2"></i> Top-up Agent Wallet</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/payments/agent-wallet-deposit" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Agent / Consultant <span class="text-danger">*</span></label>
            <select name="agent_id" id="topupAgId" class="form-select form-select-sm" required>
              <option value="">-- Select Agent --</option>
              <?php foreach ($agentsList as $a): ?>
                <option value="<?= $a['id'] ?>"><?= e($a['name']) ?> (<?= e($a['company_name'] ?? '') ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Currency</label>
              <select name="currency" id="topupAgCur" class="form-select form-select-sm fw-bold" onchange="calcAgentTopup()">
                <?php foreach (['USD', 'AED', 'LKR', 'EUR', 'GBP', 'SAR', 'QAR', 'INR', 'CAD', 'AUD'] as $cur): ?>
                  <option value="<?= $cur ?>"><?= $cur ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Credit Amount <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="amount" id="topupAgAmount" class="form-control form-control-sm fw-bold" placeholder="0.00" required oninput="calcAgentTopup()">
            </div>
          </div>

          <div class="p-3 bg-light rounded border mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="small fw-semibold text-muted">Exchange Rate:</span>
              <input type="number" step="0.000001" name="exchange_rate" id="topupAgRate" class="form-control form-control-sm text-end fw-bold" style="width: 120px;" value="1.000000" oninput="calcAgentTopup()">
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="small fw-bold text-dark">Credited to Wallet:</span>
              <span class="h6 fw-bold text-info mb-0" id="topupAgConverted">0.00</span>
            </div>
            <input type="hidden" name="converted_amount" id="topupAgConvertedHidden" value="0.00">
            <input type="hidden" name="original_amount" id="topupAgOriginalHidden" value="0.00">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Payment Method</label>
            <select name="payment_method" class="form-select form-select-sm">
              <option value="Bank Transfer">Bank Transfer</option>
              <option value="Cash at Office">Cash</option>
              <option value="Commission Credit">Commission Credit</option>
              <option value="Cheque">Cheque</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Reference</label>
            <input type="text" name="reference" class="form-control form-control-sm" placeholder="e.g. AG-<?= date('Ymd') ?>-01">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Credit Voucher / Bank Slip <span class="text-danger">*</span></label>
            <input type="file" name="receipt_file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.docx" required>
            <small class="text-muted" style="font-size: 0.72rem;"><i class="fa-solid fa-file-circle-check text-success me-1"></i>Mandatory credit voucher / deposit proof.</small>
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold">Notes / Purpose</label>
            <input type="text" name="notes" class="form-control form-control-sm" placeholder="e.g. Monthly commission credit or pre-approved limit">
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-info text-white btn-sm px-4 fw-semibold"><i class="fa-solid fa-check me-1"></i> Credit Agent</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Debit Agent Wallet -->
<div class="modal fade" id="agentDebitModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-minus-circle me-2"></i> Debit Agent Wallet</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/payments/agent-wallet-debit" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="agent_id" id="debitAgId">
        <div class="modal-body p-4">
          <div class="alert alert-warning border-0 small py-2 mb-3">
            Agent: <strong id="debitAgName">—</strong><br>
            Current Balance: <strong id="debitAgBalance">—</strong>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Debit Amount <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="amount" id="debitAgAmount" class="form-control form-control-sm fw-bold" placeholder="0.00" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Debit Reason / Type</label>
            <select name="reason" class="form-select form-select-sm">
              <option value="Application Processing Settlement">Application Fee Settlement</option>
              <option value="Commission Reversal">Commission Reversal / Clawback</option>
              <option value="Accounting Correction">Accounting Correction</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Reference</label>
            <input type="text" name="reference" class="form-control form-control-sm" placeholder="e.g. AD-<?= date('Ymd') ?>">
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold">Notes</label>
            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="e.g. Deduction for application settlement" required></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger btn-sm px-4 fw-semibold"><i class="fa-solid fa-check me-1"></i> Confirm Debit</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function quickTopUpCustomer(id, name, cur) {
  document.getElementById('topupCustId').value = id;
  if (document.getElementById('topupCustCur')) {
    document.getElementById('topupCustCur').value = cur || 'USD';
  }
  calcCustTopup();
  new bootstrap.Modal(document.getElementById('customerWalletModal')).show();
}

function quickDebitCustomer(id, name, cur, bal) {
  document.getElementById('debitCustId').value = id;
  document.getElementById('debitCustName').innerText = name;
  document.getElementById('debitCustBalance').innerText = cur + ' ' + parseFloat(bal).toFixed(2);
  document.getElementById('debitCustAmount').max = bal;
  new bootstrap.Modal(document.getElementById('customerDebitModal')).show();
}

function quickTopUpSupplier(id, name, cur) {
  document.getElementById('topupSupId').value = id;
  if (document.getElementById('topupSupCur')) {
    document.getElementById('topupSupCur').value = cur || 'AED';
  }
  calcSupModalTopup();
  new bootstrap.Modal(document.getElementById('supplierWalletModal')).show();
}

function quickTopUpAgent(id, name, cur) {
  document.getElementById('topupAgId').value = id;
  if (document.getElementById('topupAgCur')) {
    document.getElementById('topupAgCur').value = cur || 'USD';
  }
  calcAgentTopup();
  new bootstrap.Modal(document.getElementById('agentWalletModal')).show();
}

function quickDebitAgent(id, name, cur, bal) {
  document.getElementById('debitAgId').value = id;
  document.getElementById('debitAgName').innerText = name;
  document.getElementById('debitAgBalance').innerText = cur + ' ' + parseFloat(bal).toFixed(2);
  document.getElementById('debitAgAmount').max = bal;
  new bootstrap.Modal(document.getElementById('agentDebitModal')).show();
}

function calcCustTopup() {
  const amt = parseFloat(document.getElementById('topupCustAmount').value) || 0;
  const rate = parseFloat(document.getElementById('topupCustRate').value) || 1;
  const cur = document.getElementById('topupCustCur').value;
  const converted = (amt * rate).toFixed(2);
  document.getElementById('topupCustConverted').innerText = converted + ' ' + cur;
  document.getElementById('topupCustConvertedHidden').value = converted;
  document.getElementById('topupCustOriginalHidden').value = amt;
}

function calcSupModalTopup() {
  const amt = parseFloat(document.getElementById('topupSupAmount').value) || 0;
  const rate = parseFloat(document.getElementById('topupSupRate').value) || 1;
  const cur = document.getElementById('topupSupCur').value;
  const converted = (amt * rate).toFixed(2);
  document.getElementById('topupSupConverted').innerText = converted + ' ' + cur;
  document.getElementById('topupSupConvertedHidden').value = converted;
  document.getElementById('topupSupOriginalHidden').value = amt;
}

function calcAgentTopup() {
  const amt = parseFloat(document.getElementById('topupAgAmount').value) || 0;
  const rate = parseFloat(document.getElementById('topupAgRate').value) || 1;
  const cur = document.getElementById('topupAgCur').value;
  const converted = (amt * rate).toFixed(2);
  document.getElementById('topupAgConverted').innerText = converted + ' ' + cur;
  document.getElementById('topupAgConvertedHidden').value = converted;
  document.getElementById('topupAgOriginalHidden').value = amt;
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>

