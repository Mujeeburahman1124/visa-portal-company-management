<?php
$pageTitle = 'Secure Checkout — ' . e($link['title']);
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220%22%20%22100%22><text y=%22.9em%22 font-size=%2290%22>💳</text></svg>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css?v=7.0.0">
</head>
<body class="app-body bg-light">

<div class="container py-5" style="max-width: 860px;">
  <!-- Header Branding -->
  <div class="text-center mb-4">
    <div class="d-inline-flex align-items-center gap-2 mb-2">
      <div class="rounded-3 p-1.5 px-3 fw-bold text-white shadow-sm" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); font-size: 1.25rem;">
        <i class="fa-solid fa-plane-departure"></i>
      </div>
      <span class="fw-bold brand-font text-dark fs-3" style="letter-spacing: -0.02em;">MS TRAVEL HUB</span>
    </div>
    <p class="text-muted small mb-0">Global Visa Processing &bull; Secure Encrypted Payment Gateway</p>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if ($link['status'] === 'Paid'): ?>
    <div class="card card-enterprise shadow-sm text-center py-5 border-0">
      <div class="card-body">
        <div class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex p-3 fs-1 mb-3">
          <i class="fa-solid fa-circle-check"></i>
        </div>
        <h3 class="fw-bold text-dark mb-1">Invoice Already Settled</h3>
        <p class="text-muted mb-4">This payment link (Invoice <strong><?= e($link['invoice_number']) ?></strong>) was paid on <?= format_date($link['paid_at']) ?>.</p>
        <a href="/portal/invoices" class="btn btn-primary px-4 fw-semibold rounded-pill">
          <i class="fa-solid fa-receipt me-1"></i> View Invoices &amp; Receipts
        </a>
      </div>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <!-- Left Column: Invoice Details -->
      <div class="col-md-6">
        <div class="card card-enterprise shadow-sm h-100 border">
          <div class="card-header bg-white py-3 border-bottom">
            <span class="fw-bold small text-uppercase text-secondary">
              <i class="fa-solid fa-file-invoice text-primary me-2"></i> Invoice &amp; Travel Summary
            </span>
          </div>
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
              <div>
                <span class="text-muted small">Invoice Number:</span>
                <div class="fw-bold text-dark fs-6"><?= e($link['invoice_number']) ?></div>
              </div>
              <div class="text-end">
                <span class="text-muted small">Application Ref:</span>
                <div class="fw-bold text-primary"><?= e($link['application_number']) ?></div>
              </div>
            </div>

            <div class="mb-3">
              <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Customer / Applicant:</div>
              <div class="fw-bold text-dark"><?= e($link['customer_name']) ?> <small class="text-muted">(ID: <?= e($link['customer_code']) ?>)</small></div>
              <div class="small text-secondary"><i class="fa-solid fa-passport me-1"></i> Passport: <strong><?= e($link['passport_number'] ?: 'On File') ?></strong></div>
            </div>

            <div class="mb-3 p-3 bg-light rounded border small">
              <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Destination:</span>
                <span class="fw-bold text-dark"><?= $link['flag_emoji'] ?> <?= e($link['country_name']) ?></span>
              </div>
              <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Visa Service:</span>
                <span class="fw-bold text-dark"><?= e($link['service_name']) ?></span>
              </div>
              <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Entry &amp; Duration:</span>
                <span class="text-secondary"><?= e($link['entry_type']) ?> &bull; <?= e($link['duration']) ?></span>
              </div>
              <?php if (!empty($link['supplier_name'])): ?>
                <div class="d-flex justify-content-between">
                  <span class="text-muted">Supplier Channel:</span>
                  <span class="text-secondary"><?= e($link['supplier_name']) ?></span>
                </div>
              <?php endif; ?>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-2">
              <span class="fw-bold text-dark fs-5">Total Payable:</span>
              <span class="fw-bold text-primary fs-3">$<?= number_format((float)$link['amount'], 2) ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Payment Options -->
      <div class="col-md-6">
        <div class="card card-enterprise shadow-sm h-100 border">
          <div class="card-header bg-white py-3 border-bottom">
            <span class="fw-bold small text-uppercase text-secondary">
              <i class="fa-solid fa-shield-halved text-success me-2"></i> Select Payment Option
            </span>
          </div>
          <div class="card-body p-4">
            <form action="/pay/process" method="POST" id="checkoutForm">
              <?= csrf_field() ?>
              <input type="hidden" name="token" value="<?= e($link['link_token']) ?>">

              <!-- Option 1: Stripe Online Payment -->
              <div class="form-check p-3 rounded border mb-3 payment-option-box" style="cursor: pointer;">
                <input class="form-check-input ms-0 me-3" type="radio" name="payment_method" id="payMethodStripe" value="Stripe" checked>
                <label class="form-check-label w-100" for="payMethodStripe">
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark"><i class="fa-brands fa-stripe text-primary fs-5 me-1"></i> Credit / Debit Card (Stripe)</span>
                    <span class="badge bg-primary-subtle text-primary small">Instant</span>
                  </div>
                  <div class="text-muted small mt-1">Pay securely via Visa, Mastercard, AMEX or Apple Pay.</div>
                </label>
              </div>

              <!-- Option 2: Customer Pre-funded Wallet -->
              <div class="form-check p-3 rounded border mb-3 payment-option-box" style="cursor: pointer;">
                <input class="form-check-input ms-0 me-3" type="radio" name="payment_method" id="payMethodWallet" value="Customer Wallet">
                <label class="form-check-label w-100" for="payMethodWallet">
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark"><i class="fa-solid fa-wallet text-warning fs-5 me-1"></i> Customer Wallet</span>
                    <span class="badge bg-warning-subtle text-dark fw-bold small">Balance: $<?= number_format($walletBalance, 2) ?></span>
                  </div>
                  <div class="text-muted small mt-1">Deduct directly from your available deposit balance.</div>
                </label>
              </div>

              <!-- Option 3: Direct Bank Wire -->
              <div class="form-check p-3 rounded border mb-4 payment-option-box" style="cursor: pointer;">
                <input class="form-check-input ms-0 me-3" type="radio" name="payment_method" id="payMethodBank" value="Bank Transfer">
                <label class="form-check-label w-100" for="payMethodBank">
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark"><i class="fa-solid fa-building-columns text-secondary fs-5 me-1"></i> Direct Bank Transfer</span>
                    <span class="badge bg-secondary-subtle text-secondary small">Manual Verification</span>
                  </div>
                  <div class="text-muted small mt-1">Transfer directly to company corporate account.</div>
                </label>
              </div>

              <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold fs-6 rounded-pill shadow-sm" id="payNowBtn">
                <i class="fa-solid fa-lock me-1.5"></i> Authorize &amp; Pay $<?= number_format((float)$link['amount'], 2) ?>
              </button>
            </form>

            <div class="text-center mt-3">
              <span class="text-muted" style="font-size: 0.75rem;">
                <i class="fa-solid fa-shield text-success me-1"></i> 256-Bit SSL Encrypted &bull; PCI-DSS Compliant
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
