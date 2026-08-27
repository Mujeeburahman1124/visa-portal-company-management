<?php
$pageTitle = 'Agent & Partner Network — VISA TRACK';
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
      <h3 class="fw-bold brand-font text-dark mb-1">Agent &amp; B2B Partner Network</h3>
      <p class="text-muted small mb-0">Manage partner travel agents, credit limits, commission rates, balances, and portal access.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <button class="btn btn-success btn-sm px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#newAgentModal">
        <i class="fa-solid fa-plus me-1"></i> Register New Agent
      </button>
      <button class="btn btn-outline-secondary btn-sm px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#recordAgentPayModal">
        <i class="fa-solid fa-receipt me-1"></i> Record Agent Payment
      </button>
    </div>
  </div>

  <!-- Agent Summary Metrics (100% Responsive Grid) -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-md-3">
      <div class="stat-card p-3 h-100 shadow-sm border rounded-3">
        <div class="d-flex align-items-center gap-3">
          <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 fs-4"><i class="fa-solid fa-handshake"></i></div>
          <div>
            <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.03em;">Total Registered</div>
            <h4 class="fw-bold mb-0 text-dark"><?= count($agents) ?></h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
      <div class="stat-card p-3 h-100 shadow-sm border rounded-3">
        <div class="d-flex align-items-center gap-3">
          <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 fs-4"><i class="fa-solid fa-folder-tree"></i></div>
          <div>
            <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.03em;">Agent Applications</div>
            <h4 class="fw-bold mb-0 text-primary"><?= array_sum(array_column($agents, 'total_applications')) ?></h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
      <div class="stat-card p-3 h-100 shadow-sm border rounded-3">
        <div class="d-flex align-items-center gap-3">
          <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-3 fs-4"><i class="fa-solid fa-wallet"></i></div>
          <div>
            <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.03em;">Outstanding Balance</div>
            <h4 class="fw-bold mb-0 text-danger"><?= format_currency(array_sum(array_column($agents, 'current_balance'))) ?></h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
      <div class="stat-card p-3 h-100 shadow-sm border rounded-3">
        <div class="d-flex align-items-center gap-3">
          <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3 fs-4"><i class="fa-solid fa-money-bill-transfer"></i></div>
          <div>
            <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.03em;">Payments Collected</div>
            <h4 class="fw-bold mb-0 text-warning"><?= format_currency(array_sum(array_column($agents, 'total_paid'))) ?></h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Agent Table (100% Touch Scroll Responsive Grid) -->
  <div class="card card-enterprise shadow-sm border">
    <div class="card-body p-0">
      <div class="table-responsive" style="-webkit-overflow-scrolling: touch;">
        <table class="table table-hover align-middle mb-0 table-custom" style="font-size: 0.86rem; min-width: 1100px;">
          <thead class="table-light">
            <tr>
              <th style="width: 100px;" class="text-nowrap">Agent Code</th>
              <th style="width: 200px;">Company Name</th>
              <th style="width: 140px;">Contact Person</th>
              <th style="width: 200px;">Contact Details</th>
              <th style="width: 140px;">Location</th>
              <th style="width: 120px;" class="text-nowrap">Credit Limit</th>
              <th style="width: 120px;" class="text-nowrap">Balance</th>
              <th style="width: 90px;" class="text-nowrap">Commission</th>
              <th style="width: 90px;" class="text-nowrap">Apps</th>
              <th style="width: 90px;" class="text-nowrap">Status</th>
              <th style="width: 90px;" class="text-end text-nowrap">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($agents)): ?>
              <tr><td colspan="11" class="text-center py-5 text-muted">No agents registered yet.</td></tr>
            <?php else: ?>
              <?php foreach ($agents as $a): ?>
                <?php $active = (int)$a['is_active'] === 1; ?>
                <tr>
                  <td class="text-nowrap"><span class="badge bg-success-subtle text-success fw-bold border"><?= e($a['agent_code']) ?></span></td>
                  <td>
                    <div class="fw-bold text-dark text-truncate" style="max-width: 190px;" title="<?= e($a['company_name']) ?>"><?= e($a['company_name']) ?></div>
                    <div class="text-muted small"><?= e($a['payment_terms']) ?></div>
                  </td>
                  <td><span class="text-truncate d-inline-block" style="max-width: 130px;" title="<?= e($a['contact_person']) ?>"><?= e($a['contact_person']) ?></span></td>
                  <td class="small">
                    <div class="text-nowrap"><i class="fa-solid fa-phone me-1 text-muted"></i><?= e($a['mobile']) ?></div>
                    <div class="text-truncate" style="max-width: 190px;" title="<?= e($a['email']) ?>"><i class="fa-solid fa-envelope me-1 text-muted"></i><?= e($a['email']) ?></div>
                  </td>
                  <td class="small">
                    <span class="text-truncate d-inline-block" style="max-width: 130px;" title="<?= e($a['city'] ?: '') ?><?= !empty($a['city']) && !empty($a['country']) ? ', ' : '' ?><?= e($a['country'] ?: '—') ?>">
                      <?= e($a['city'] ?: '') ?><?= !empty($a['city']) && !empty($a['country']) ? ', ' : '' ?><?= e($a['country'] ?: '—') ?>
                    </span>
                  </td>
                  <td class="text-nowrap"><?= format_currency((float)$a['credit_limit']) ?></td>
                  <td class="text-nowrap">
                    <span class="fw-bold <?= (float)$a['current_balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                      <?= format_currency((float)$a['current_balance']) ?>
                    </span>
                  </td>
                  <td class="text-nowrap"><span class="badge bg-light text-dark border"><?= (float)$a['commission_rate'] ?>%</span></td>
                  <td class="text-nowrap"><span class="badge bg-primary rounded-pill px-2.5 py-1"><?= (int)$a['total_applications'] ?></span></td>
                  <td class="text-nowrap">
                    <span class="badge <?= $active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> fw-bold px-2 py-1">
                      <?= $active ? 'Active' : 'Suspended' ?>
                    </span>
                  </td>
                  <td class="text-end text-nowrap">
                    <div class="dropdown">
                      <button class="btn btn-light btn-sm dropdown-toggle border shadow-sm" data-bs-toggle="dropdown">Actions</button>
                      <ul class="dropdown-menu dropdown-menu-end shadow border-0 small">
                        <li>
                          <form action="/agents/toggle-status" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="agent_id" value="<?= $a['id'] ?>">
                            <button type="submit" class="dropdown-item"><?= $active ? '<i class="fa-solid fa-ban text-danger me-2"></i>Suspend' : '<i class="fa-solid fa-check text-success me-2"></i>Activate' ?></button>
                          </form>
                        </li>
                      </ul>
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
</div>

<!-- Modal: Register Agent -->
<div class="modal fade" id="newAgentModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title fw-bold fs-6"><i class="fa-solid fa-plus-circle me-2"></i>Register New B2B Agent</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/agents/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Agent Code <span class="text-danger">*</span></label>
              <input type="text" name="agent_code" class="form-control" placeholder="e.g. AGT-001" required>
            </div>
            <div class="col-md-8">
              <label class="form-label small fw-semibold">Company / Agency Name <span class="text-danger">*</span></label>
              <input type="text" name="company_name" class="form-control" required placeholder="e.g. Skyline Travel Agency">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Contact Person <span class="text-danger">*</span></label>
              <input type="text" name="contact_person" class="form-control" required placeholder="e.g. Sarah Khan">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Email (Portal Login) <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" required placeholder="sarah@skylinetravel.com">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Mobile Number <span class="text-danger">*</span></label>
              <input type="text" name="mobile" class="form-control" required placeholder="+971 50 111 2233">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">WhatsApp</label>
              <input type="text" name="whatsapp" class="form-control" placeholder="+971 50 111 2233">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">City</label>
              <input type="text" name="city" class="form-control" placeholder="Dubai">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Country</label>
              <input type="text" name="country" class="form-control" placeholder="United Arab Emirates">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Credit Limit ($)</label>
              <input type="number" step="0.01" name="credit_limit" class="form-control" value="0.00">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Commission Rate (%)</label>
              <input type="number" step="0.1" name="commission_rate" class="form-control" value="10.0">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Payment Terms</label>
              <select name="payment_terms" class="form-select">
                <option value="Prepaid">Prepaid</option>
                <option value="Net 7">Net 7 Days</option>
                <option value="Net 15">Net 15 Days</option>
                <option value="Net 30" selected>Net 30 Days</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Portal Password (Optional)</label>
              <input type="password" name="password" class="form-control" placeholder="Initial password for agent login">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Bank / Wire Details</label>
              <input type="text" name="bank_details" class="form-control" placeholder="Bank Name, IBAN...">
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold">Internal Notes</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Agreements, contract terms..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success fw-semibold"><i class="fa-solid fa-save me-1"></i>Create Agent</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Record Agent Payment -->
<div class="modal fade" id="recordAgentPayModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold fs-6"><i class="fa-solid fa-receipt text-success me-2"></i>Record Agent Settlement / Payment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/agents/pay" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Select Agent <span class="text-danger">*</span></label>
            <select name="agent_id" class="form-select" required>
              <option value="">-- Choose Agent --</option>
              <?php foreach ($agents as $a): ?>
                <option value="<?= $a['id'] ?>"><?= e($a['company_name']) ?> (<?= e($a['agent_code']) ?>) — Balance: <?= format_currency((float)$a['current_balance']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Amount ($) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="0.00" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Payment Date <span class="text-danger">*</span></label>
              <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Payment Method</label>
            <select name="payment_method" class="form-select">
              <option value="Bank Transfer">Bank Transfer</option>
              <option value="Cash">Cash</option>
              <option value="Credit Card">Credit Card</option>
              <option value="Cheque">Cheque</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Transaction Reference</label>
            <input type="text" name="transaction_reference" class="form-control" placeholder="Bank ref / deposit slip #">
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Notes</label>
            <input type="text" name="notes" class="form-control" placeholder="Settlement remarks...">
          </div>
        </div>
        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success fw-semibold"><i class="fa-solid fa-check me-1"></i>Record Payment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
