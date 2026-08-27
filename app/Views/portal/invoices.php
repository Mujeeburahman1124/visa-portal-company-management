<?php
$pageTitle = 'My Invoices & Payment Status — VISA TRACK';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220%22%20%22100%22><text y=%22.9em%22 font-size=%2290%22>✈️</text></svg>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/main.css?v=7.0.0">
</head>
<body class="app-body">

<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="container py-4">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
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
      <h3 class="fw-bold brand-font text-dark mb-1">Invoices &amp; Payment Ledger</h3>
      <p class="text-muted small mb-0">Official receipts, invoice breakdowns, and company payment settlement instructions.</p>
    </div>
  </div>

  <div class="row g-4">
    <!-- Invoices Table Column -->
    <div class="col-lg-8">
      <div class="card card-enterprise shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-receipt text-primary me-2"></i> Application Billing Statements</h6>
        </div>

        <div class="card-body p-0">
          <?php if (empty($invoices)): ?>
            <div class="text-center py-5 text-muted">
              <i class="fa-solid fa-file-invoice-dollar fs-2 mb-2"></i>
              <div class="fw-semibold">No invoices generated for your account.</div>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table-modern mb-0">
                <thead>
                  <tr>
                    <th>Invoice / Receipt #</th>
                    <th>Visa Service</th>
                    <th>Billing Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th class="text-end">View Statement</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($invoices as $inv): ?>
                    <?php
                      $status = $inv['status'] ?? 'Completed';
                      $isPaid = ($status === 'Completed' || $status === 'Paid');
                    ?>
                    <tr>
                      <td>
                        <div class="fw-bold text-dark"><?= e($inv['payment_number']) ?></div>
                        <div class="text-muted font-monospace" style="font-size: 0.72rem;"><?= e($inv['invoice_number']) ?></div>
                      </td>
                      <td>
                        <div class="fw-semibold text-dark"><?= e($inv['service_name']) ?></div>
                        <div class="text-muted small"><?= e($inv['country_name']) ?></div>
                      </td>
                      <td class="text-muted small"><?= format_date($inv['payment_date']) ?></td>
                      <td>
                        <span class="fw-bold text-dark"><?= format_currency((float)$inv['amount']) ?></span>
                      </td>
                      <td>
                        <span class="badge <?= $isPaid ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-dark' ?> fw-bold px-2.5 py-1">
                          <i class="fa-solid <?= $isPaid ? 'fa-circle-check' : 'fa-clock' ?> me-1"></i><?= $isPaid ? 'Paid in Full' : 'Pending' ?>
                        </span>
                      </td>
                      <td class="text-end">
                        <a href="/portal/invoice/view?id=<?= $inv['id'] ?>" class="btn btn-sm btn-outline-primary py-1 px-3 fw-semibold">
                          <i class="fa-solid fa-eye me-1"></i> View
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Payment Instructions Column -->
    <div class="col-lg-4">
      <div class="card card-enterprise shadow-sm border mb-4">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-building-columns text-primary me-2"></i> Payment Instructions</h6>
        </div>
        <div class="card-body p-4">
          <div class="p-3 bg-light rounded border mb-3">
            <div class="small fw-semibold text-muted mb-1">OFFICIAL BENEFICIARY:</div>
            <div class="fw-bold text-dark"><?= e($settings['company_name'] ?? 'MS TRAVEL HUB & VISA SERVICES LLC') ?></div>
          </div>

          <div class="mb-3 small">
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted">Primary Bank:</span>
              <span class="fw-bold text-dark">Emirates NBD / HSBC Global</span>
            </div>
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted">Currency:</span>
              <span class="fw-bold text-dark"><?= e($settings['primary_currency'] ?? 'USD') ?></span>
            </div>
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted">IBAN / Wire Code:</span>
              <span class="font-monospace fw-bold text-primary">AE29 0331 0000 8891 0019</span>
            </div>
          </div>

          <div class="alert alert-info small mb-3">
            <i class="fa-solid fa-circle-info me-1"></i> <strong>Wire Note:</strong> Please include your <strong>Application Number</strong> as the payment transfer reference so our accounts team can immediately credit your file.
          </div>

          <div class="text-center p-3 bg-light rounded border text-muted small">
            <i class="fa-solid fa-credit-card fs-4 mb-2 text-secondary d-block"></i>
            Online credit/debit card gateway integration is in progress. For immediate clearance, please use direct wire or deposit.
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

