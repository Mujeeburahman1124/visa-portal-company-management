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
      <p class="text-muted small mb-0">Centralized accounting ledger for customer prepayments, supplier advance balances, agent credit limits &amp; multi-currency conversions.</p>
    </div>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-outline-primary px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#customerWalletModal">
        <i class="fa-solid fa-wallet me-1"></i> Top-up Customer
      </button>
      <button type="button" class="btn btn-outline-success px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#supplierWalletModal">
        <i class="fa-solid fa-building me-1"></i> Top-up Supplier
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
      <a class="nav-link <?= $activeTab === 'suppliers' ? 'active fw-bold' : '' ?>" href="/payments/wallets?tab=suppliers">
        <i class="fa-solid fa-building me-1 text-success"></i> Supplier Wallets (<?= count($supplierWallets) ?>)
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
                    <button type="button" class="btn btn-sm btn-outline-success py-1 px-2" onclick="quickTopUpSupplier(<?= $sw['supplier_id'] ?>, '<?= e(addslashes($sw['supplier_name'])) ?>', '<?= e($sw['currency'] ?: 'USD') ?>')">
                      <i class="fa-solid fa-plus me-1"></i> Top-up
                    </button>
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
              <th>Email Address</th>
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
              <th>Balance After</th>
              <th>Description</th>
              <th>Processed By</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentTransactions)): ?>
              <tr><td colspan="8" class="text-center py-4 text-muted">No wallet transactions found.</td></tr>
            <?php else: ?>
              <?php foreach ($recentTransactions as $tx): ?>
                <tr>
                  <td><span class="badge bg-light text-dark border font-monospace"><?= e($tx['transaction_id']) ?></span></td>
                  <td>
                    <div class="fw-semibold text-dark"><?= date('d M Y', strtotime($tx['created_at'])) ?></div>
                    <div class="text-muted small"><?= date('H:i A', strtotime($tx['created_at'])) ?></div>
                  </td>
                  <td><?= e($tx['customer_name'] ?? 'Account User') ?></td>
                  <td>
                    <?php if ($tx['transaction_type'] === 'Credit'): ?>
                      <span class="badge bg-success-subtle text-success border">Credit</span>
                    <?php else: ?>
                      <span class="badge bg-danger-subtle text-danger border">Debit</span>
                    <?php endif; ?>
                  </td>
                  <td class="fw-bold <?= $tx['transaction_type'] === 'Credit' ? 'text-success' : 'text-danger' ?>">
                    <?= $tx['transaction_type'] === 'Credit' ? '+' : '-' ?><?= e($tx['currency'] ?? 'USD') ?> <?= number_format((float)$tx['amount'], 2) ?>
                  </td>
                  <td class="fw-semibold text-dark"><?= e($tx['currency'] ?? 'USD') ?> <?= number_format((float)$tx['balance_after'], 2) ?></td>
                  <td class="small text-muted" style="max-width: 250px;"><?= e($tx['description']) ?></td>
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

<!-- Modal: Top-up Customer Wallet -->
<div class="modal fade" id="customerWalletModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-wallet text-primary me-2"></i> Top-up Customer Wallet</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/payments/wallet-deposit" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Customer / Applicant <span class="text-danger">*</span></label>
            <select name="customer_id" id="topupCustId" class="form-select" required>
              <option value="">-- Select Customer --</option>
              <?php foreach ($customersList as $c): ?>
                <option value="<?= $c['id'] ?>"><?= e($c['full_name']) ?> (<?= e($c['customer_code']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-8">
              <label class="form-label small fw-semibold">Deposit Amount <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="amount" class="form-control fw-bold" placeholder="0.00" required>
            </div>
            <div class="col-4">
              <label class="form-label small fw-semibold">Currency</label>
              <select name="currency" id="topupCustCur" class="form-select fw-bold">
                <?php foreach (['USD', 'AED', 'LKR', 'EUR', 'GBP', 'SAR', 'QAR', 'INR', 'CAD', 'AUD'] as $cur): ?>
                  <option value="<?= $cur ?>"><?= $cur ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Payment Method</label>
            <select name="payment_method" class="form-select">
              <option value="Cash at Office">Cash at Office</option>
              <option value="Bank Transfer">Bank Transfer</option>
              <option value="Credit Card">Credit Card</option>
              <option value="Online Gateway">Online Gateway</option>
            </select>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Notes / Reference</label>
            <input type="text" name="notes" class="form-control" placeholder="e.g. Advance deposit for upcoming tourist visa application">
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="fa-solid fa-check me-1"></i> Process Top-up</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Top-up Supplier Wallet -->
<div class="modal fade" id="supplierWalletModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-building text-success me-2"></i> Top-up Supplier Wallet</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/payments/supplier-wallet-deposit" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Supplier Partner <span class="text-danger">*</span></label>
            <select name="supplier_id" id="topupSupId" class="form-select" required>
              <option value="">-- Select Supplier --</option>
              <?php foreach ($suppliersList as $s): ?>
                <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-8">
              <label class="form-label small fw-semibold">Credit Amount <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="amount" class="form-control fw-bold" placeholder="0.00" required>
            </div>
            <div class="col-4">
              <label class="form-label small fw-semibold">Currency</label>
              <select name="currency" id="topupSupCur" class="form-select fw-bold">
                <?php foreach (['USD', 'AED', 'LKR', 'EUR', 'GBP', 'SAR', 'QAR', 'INR', 'CAD', 'AUD'] as $cur): ?>
                  <option value="<?= $cur ?>"><?= $cur ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Notes / Purpose</label>
            <input type="text" name="notes" class="form-control" placeholder="e.g. Advance deposit for Q4 bulk visa allocations">
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success px-4 fw-semibold"><i class="fa-solid fa-check me-1"></i> Credit Supplier</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Top-up Agent Wallet -->
<div class="modal fade" id="agentWalletModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-tie text-info me-2"></i> Top-up Agent Wallet</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/payments/agent-wallet-deposit" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Agent / Consultant <span class="text-danger">*</span></label>
            <select name="agent_id" id="topupAgId" class="form-select" required>
              <option value="">-- Select Agent --</option>
              <?php foreach ($agentsList as $a): ?>
                <option value="<?= $a['id'] ?>"><?= e($a['name']) ?> (<?= e($a['role']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-8">
              <label class="form-label small fw-semibold">Credit Amount <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="amount" class="form-control fw-bold" placeholder="0.00" required>
            </div>
            <div class="col-4">
              <label class="form-label small fw-semibold">Currency</label>
              <select name="currency" id="topupAgCur" class="form-select fw-bold">
                <?php foreach (['USD', 'AED', 'LKR', 'EUR', 'GBP', 'SAR', 'QAR', 'INR', 'CAD', 'AUD'] as $cur): ?>
                  <option value="<?= $cur ?>"><?= $cur ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Notes / Purpose</label>
            <input type="text" name="notes" class="form-control" placeholder="e.g. Monthly commission credit or pre-approved limit">
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-info text-white px-4 fw-semibold"><i class="fa-solid fa-check me-1"></i> Credit Agent</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function quickTopUpCustomer(id, name, cur) {
  document.getElementById('topupCustId').value = id;
  if (document.getElementById('topupCustCur')) {
    document.getElementById('topupCustCur').value = cur;
  }
  new bootstrap.Modal(document.getElementById('customerWalletModal')).show();
}

function quickTopUpSupplier(id, name, cur) {
  document.getElementById('topupSupId').value = id;
  if (document.getElementById('topupSupCur')) {
    document.getElementById('topupSupCur').value = cur;
  }
  new bootstrap.Modal(document.getElementById('supplierWalletModal')).show();
}

function quickTopUpAgent(id, name, cur) {
  document.getElementById('topupAgId').value = id;
  if (document.getElementById('topupAgCur')) {
    document.getElementById('topupAgCur').value = cur;
  }
  new bootstrap.Modal(document.getElementById('agentWalletModal')).show();
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
