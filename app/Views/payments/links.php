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
        <div class="col-md-5">
          <div class="input-group">
            <span class="input-group-text bg-light border-0"><i class="fa-solid fa-search text-muted"></i></span>
            <input type="text" name="search" class="form-control bg-light border-0" placeholder="Search by customer, application, invoice, or token..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
          </div>
        </div>
        <div class="col-md-3">
          <select name="status" class="form-select bg-light border-0">
            <option value="">All Statuses</option>
            <option value="Pending" <?= ($_GET['status'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Paid" <?= ($_GET['status'] ?? '') === 'Paid' ? 'selected' : '' ?>>Paid</option>
            <option value="Partially Paid" <?= ($_GET['status'] ?? '') === 'Partially Paid' ? 'selected' : '' ?>>Partially Paid</option>
            <option value="Expired" <?= ($_GET['status'] ?? '') === 'Expired' ? 'selected' : '' ?>>Expired</option>
            <option value="Cancelled" <?= ($_GET['status'] ?? '') === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-dark w-100 fw-semibold">Filter</button>
        </div>
        <div class="col-md-2">
          <a href="/payments/links" class="btn btn-light w-100">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Links Table -->
  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr class="small text-muted text-uppercase">
            <th>Link &amp; Token</th>
            <th>Customer / Applicant</th>
            <th>Application</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Created</th>
            <th>Expiry / Due</th>
            <th class="text-end">Actions</th>
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
                <td>
                  <div class="fw-bold font-monospace text-primary" style="font-size: 0.85rem;"><?= htmlspecialchars(substr($l['link_token'], 0, 12)) ?>...</div>
                  <div class="small text-muted"><?= htmlspecialchars($l['invoice_number']) ?></div>
                </td>
                <td>
                  <div class="fw-bold"><?= htmlspecialchars($l['customer_name']) ?></div>
                  <div class="small text-muted"><?= htmlspecialchars($l['customer_code']) ?> &bull; <?= htmlspecialchars($l['customer_mobile'] ?? '') ?></div>
                </td>
                <td>
                  <a href="/applications/show?id=<?= $l['application_id'] ?>" class="badge bg-light text-dark text-decoration-none border">
                    <?= htmlspecialchars($l['application_number']) ?>
                  </a>
                  <div class="small text-muted"><?= htmlspecialchars($l['service_name']) ?> (<?= htmlspecialchars($l['country_name']) ?>)</div>
                </td>
                <td>
                  <div class="fw-bold text-dark fs-6">$<?= number_format((float)$l['amount'], 2) ?></div>
                  <div class="small text-muted"><?= htmlspecialchars($l['currency']) ?></div>
                </td>
                <td>
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
                <td class="small text-muted">
                  <?= date('M d, Y', strtotime($l['created_at'])) ?>
                </td>
                <td class="small text-muted">
                  <?= date('M d, Y', strtotime($l['expires_at'])) ?>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText('<?= $linkUrl ?>'); alert('Payment link copied to clipboard:\n<?= $linkUrl ?>');" title="Copy Link">
                      <i class="fa-solid fa-copy"></i>
                    </button>
                    <a href="/pay?token=<?= htmlspecialchars($l['link_token']) ?>" target="_blank" class="btn btn-outline-primary" title="Open Payment Page">
                      <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                    <?php if ($isPending): ?>
                      <form action="/payments/links/cancel" method="POST" class="d-inline" onsubmit="return confirm('Cancel this payment link?');">
                        <?= \App\Middleware\CsrfMiddleware::field() ?>
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

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
