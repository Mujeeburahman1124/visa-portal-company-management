<?php
$pageTitle = 'Payment Links Management — VISA TRACK';
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
      <h3 class="fw-bold brand-font mb-0">Online Payment Links</h3>
      <p class="text-muted small mb-0">Manage, share, track and cancel secure unguessable online checkout links.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="/payments" class="btn btn-outline-secondary px-3 fw-semibold shadow-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Payments
      </a>
      <button type="button" class="btn btn-primary px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#createLinkModal">
        <i class="fa-solid fa-plus me-1"></i> Generate New Link
      </button>
    </div>
  </div>

  <!-- Filter Card -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form action="/payments/links" method="GET" class="row g-2 align-items-center">
        <div class="col-12 col-md-5">
          <div class="input-group">
            <span class="input-group-text bg-light border-0"><i class="fa-solid fa-search text-muted"></i></span>
            <input type="text" name="search" class="form-control bg-light border-0" placeholder="Search by customer, application, invoice, or token..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
          </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
          <select name="status" class="form-select bg-light border-0">
            <option value="">All Statuses</option>
            <option value="Pending" <?= ($_GET['status'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Paid" <?= ($_GET['status'] ?? '') === 'Paid' ? 'selected' : '' ?>>Paid</option>
            <option value="Partially Paid" <?= ($_GET['status'] ?? '') === 'Partially Paid' ? 'selected' : '' ?>>Partially Paid</option>
            <option value="Expired" <?= ($_GET['status'] ?? '') === 'Expired' ? 'selected' : '' ?>>Expired</option>
            <option value="Cancelled" <?= ($_GET['status'] ?? '') === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <button type="submit" class="btn btn-dark w-100 fw-semibold">Filter</button>
        </div>
        <div class="col-6 col-md-2">
          <a href="/payments/links" class="btn btn-light w-100">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Links Table -->
  <div class="card border-0 shadow-sm">
    <div class="table-responsive" style="-webkit-overflow-scrolling: touch;">
      <table class="table table-hover align-middle mb-0" style="min-width: 950px;">
        <thead class="table-light">
          <tr class="small text-muted text-uppercase">
            <th class="text-nowrap">Link &amp; Token</th>
            <th class="text-nowrap">Customer / Applicant</th>
            <th class="text-nowrap">Application</th>
            <th class="text-nowrap">Amount</th>
            <th class="text-nowrap">Status</th>
            <th class="text-nowrap">Created</th>
            <th class="text-nowrap">Expiry / Due</th>
            <th class="text-end text-nowrap">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($paymentLinks)): ?>
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">
                <i class="fa-solid fa-link-slash fs-3 d-block mb-2 text-secondary"></i>
                No payment links found.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($paymentLinks as $l): 
              $linkUrl = (string)\App\Config\Env::get('APP_URL', 'http://localhost:8000') . "/pay?token=" . $l['link_token'];
              $isPaid = ($l['status'] === 'Paid');
              $isPending = ($l['status'] === 'Pending');
            ?>
              <tr>
                <td class="text-nowrap">
                  <div class="fw-bold font-monospace text-primary" style="font-size: 0.85rem;"><?= htmlspecialchars(substr($l['link_token'], 0, 12)) ?>...</div>
                  <div class="small text-muted"><?= htmlspecialchars($l['invoice_number']) ?></div>
                </td>
                <td>
                  <div class="fw-bold text-nowrap"><?= htmlspecialchars($l['customer_name']) ?></div>
                  <div class="small text-muted text-nowrap"><?= htmlspecialchars($l['customer_code']) ?> &bull; <?= htmlspecialchars($l['customer_mobile'] ?? '') ?></div>
                </td>
                <td>
                  <a href="/applications/show?id=<?= $l['application_id'] ?>" class="badge bg-light text-dark text-decoration-none border text-nowrap">
                    <?= htmlspecialchars($l['application_number']) ?>
                  </a>
                  <div class="small text-muted text-nowrap"><?= htmlspecialchars($l['service_name']) ?> (<?= htmlspecialchars($l['country_name']) ?>)</div>
                </td>
                <td class="text-nowrap">
                  <div class="fw-bold text-dark fs-6">$<?= number_format((float)$l['amount'], 2) ?></div>
                  <div class="small text-muted"><?= htmlspecialchars($l['currency']) ?></div>
                </td>
                <td class="text-nowrap">
                  <?php if ($l['status'] === 'Paid'): ?>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                      <i class="fa-solid fa-check me-1"></i> Paid
                    </span>
                  <?php elseif ($l['status'] === 'Partially Paid'): ?>
                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1">
                      <i class="fa-solid fa-adjust me-1"></i> Partially Paid
                    </span>
                  <?php elseif ($l['status'] === 'Expired'): ?>
                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                      <i class="fa-solid fa-clock me-1"></i> Expired
                    </span>
                  <?php elseif ($l['status'] === 'Cancelled'): ?>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1">
                      Cancelled
                    </span>
                  <?php else: ?>
                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">
                      <i class="fa-solid fa-hourglass-start me-1"></i> Pending
                    </span>
                  <?php endif; ?>
                </td>
                <td class="small text-muted text-nowrap">
                  <?= date('M d, Y', strtotime($l['created_at'])) ?>
                </td>
                <td class="small text-muted text-nowrap">
                  <?= date('M d, Y', strtotime($l['expires_at'])) ?>
                </td>
                <td class="text-end text-nowrap">
                  <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText('<?= $linkUrl ?>'); alert('Payment link copied to clipboard:\n<?= $linkUrl ?>');" title="Copy Link">
                      <i class="fa-solid fa-copy"></i>
                    </button>
                    <?php if (!empty($l['customer_mobile'])): 
                      $cleanPhone = preg_replace('/[^0-9]/', '', $l['customer_mobile']);
                      $waText = "Hello " . $l['customer_name'] . ",\n\nPlease use this secure link to pay $" . number_format((float)$l['amount'], 2) . " USD for your visa application (" . $l['application_number'] . "):\n" . $linkUrl;
                    ?>
                      <a href="https://api.whatsapp.com/send?phone=<?= $cleanPhone ?>&text=<?= urlencode($waText) ?>" target="_blank" class="btn btn-outline-success" title="Share on WhatsApp">
                        <i class="fa-brands fa-whatsapp"></i>
                      </a>
                    <?php endif; ?>
                    <a href="/pay?token=<?= htmlspecialchars($l['link_token']) ?>" target="_blank" class="btn btn-outline-primary" title="Open Payment Page">
                      <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                    <?php if ($isPending): ?>
                      <form action="/payments/links/cancel" method="POST" class="d-inline" onsubmit="return confirm('Cancel this payment link?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="link_id" value="<?= $l['id'] ?>">
                        <button type="submit" class="btn btn-outline-danger" title="Cancel Link">
                          <i class="fa-solid fa-ban"></i>
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

<!-- Modal: Generate Payment Link -->
<div class="modal fade" id="createLinkModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="/payments/generate-link" method="POST">
        <?= csrf_field() ?>
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title fw-bold"><i class="fa-solid fa-link me-2"></i> Generate Online Payment Link</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Select Visa Application <span class="text-danger">*</span></label>
            <select name="application_id" class="form-select" required onchange="onPaymentLinkAppSelected(this)">
              <option value="">-- Choose Application / Applicant --</option>
              <?php if (!empty($applicationsList)): ?>
                <?php foreach ($applicationsList as $ap): ?>
                  <option value="<?= $ap['id'] ?>" 
                    data-balance="<?= $ap['balance_amount'] ?>" 
                    data-total="<?= $ap['total_amount'] ?>"
                    data-name="<?= e($ap['customer_name']) ?>"
                    data-service="<?= e($ap['service_name']) ?>"
                    data-country="<?= e($ap['country_name']) ?>">
                    <?= e($ap['application_number']) ?> &mdash; <?= e($ap['customer_name']) ?> [Bal: $<?= number_format((float)$ap['balance_amount'], 2) ?>]
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-md-7">
              <label class="form-label small fw-semibold text-secondary">Payment Amount ($ USD) <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" step="0.01" min="0.01" name="amount" id="createLinkAmountInput" class="form-control fw-bold" placeholder="0.00" required>
              </div>
            </div>
            <div class="col-md-5">
              <label class="form-label small fw-semibold text-secondary">Due Date / Expiry</label>
              <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Payment Title / Purpose</label>
            <input type="text" name="title" id="createLinkTitleInput" class="form-control" placeholder="e.g. Visa Processing Fee Payment">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Customer Instructions / Notes</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Instructions shown on customer checkout page..."></textarea>
          </div>

          <div class="card bg-light border p-3">
            <span class="small fw-bold text-secondary text-uppercase mb-2 d-block" style="font-size: 0.72rem;">Dispatch Notification Options:</span>
            <div class="form-check mb-1">
              <input class="form-check-input" type="checkbox" name="send_email" value="1" id="linkSendEmail" checked>
              <label class="form-check-label small fw-semibold" for="linkSendEmail">
                <i class="fa-solid fa-envelope text-primary me-1"></i> Send payment link automatically via Email
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="send_whatsapp" value="1" id="linkSendWhatsapp" checked>
              <label class="form-check-label small fw-semibold" for="linkSendWhatsapp">
                <i class="fa-brands fa-whatsapp text-success me-1"></i> Send payment request instantly via WhatsApp Cloud
              </label>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
            <i class="fa-solid fa-paper-plane me-1"></i> Generate &amp; Send Payment Link
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function onPaymentLinkAppSelected(select) {
  const opt = select.options[select.selectedIndex];
  if (!opt || !opt.value) return;
  const balance = parseFloat(opt.getAttribute('data-balance') || 0);
  const total = parseFloat(opt.getAttribute('data-total') || 0);
  const name = opt.getAttribute('data-name') || '';
  const service = opt.getAttribute('data-service') || '';

  const amtInput = document.getElementById('createLinkAmountInput');
  if (amtInput) {
    amtInput.value = balance > 0 ? balance.toFixed(2) : total.toFixed(2);
  }

  const titleInput = document.getElementById('createLinkTitleInput');
  if (titleInput && (!titleInput.value || titleInput.value.includes('Visa Fee for'))) {
    titleInput.value = 'Visa Fee for ' + name + (service ? ' (' + service + ')' : '');
  }
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
