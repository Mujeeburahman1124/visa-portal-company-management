<?php
$pageTitle = 'Invoice ' . $invoice['invoice_number'] . ' — VISA TRACK';
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
  <style>
    @media print {
      .no-print { display: none !important; }
      body { background: white !important; }
      .invoice-box { box-shadow: none !important; border: none !important; }
    }
  </style>
</head>
<body class="bg-light py-4">

<div class="container" style="max-width: 800px;">
  <div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <a href="/portal/invoices" class="btn btn-outline-secondary btn-sm px-3 bg-white">
      <i class="fa-solid fa-arrow-left me-1"></i> Back to Invoices
    </a>
    <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm" onclick="window.print()">
      <i class="fa-solid fa-print me-1"></i> Print / Save as PDF
    </button>
  </div>

  <div class="card shadow-sm border-0 rounded-3 p-4 p-md-5 bg-white invoice-box">
    <!-- Invoice Header -->
    <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
      <div class="d-flex align-items-center gap-2">
        <div class="rounded-3 p-2 bg-primary text-white fs-4 fw-bold">
          <i class="fa-solid fa-plane-departure"></i>
        </div>
        <div>
          <h4 class="fw-bold brand-font text-dark mb-0">VISA TRACK</h4>
          <div class="text-muted small">MS TRAVEL HUB &amp; VISA OPERATIONS</div>
        </div>
      </div>

      <div class="text-end">
        <span class="badge bg-success fs-6 px-3 py-1.5 fw-bold mb-1">OFFICIAL RECEIPT</span>
        <div class="fw-bold text-dark font-monospace"><?= e($invoice['invoice_number']) ?></div>
        <div class="text-muted small">Date: <?= format_date($invoice['payment_date']) ?></div>
      </div>
    </div>

    <!-- Billed To / Billed By -->
    <div class="row g-4 mb-4">
      <div class="col-6">
        <div class="text-muted small fw-semibold text-uppercase">Billed To:</div>
        <h5 class="fw-bold text-dark mb-1"><?= e($customer['full_name']) ?></h5>
        <div class="text-muted small">Customer ID: <?= e($customer['customer_code']) ?></div>
        <div class="text-muted small">Email: <?= e($customer['email']) ?></div>
      </div>
      <div class="col-6 text-end">
        <div class="text-muted small fw-semibold text-uppercase">Visa Application:</div>
        <div class="fw-bold text-primary fs-6 font-monospace mb-1"><?= e($invoice['application_number']) ?></div>
        <div class="text-muted small">Destination: <?= $invoice['flag_emoji'] ?> <?= e($invoice['country_name']) ?></div>
        <div class="text-muted small">Method: <?= e($invoice['payment_method']) ?></div>
      </div>
    </div>

    <!-- Line Items Table -->
    <div class="table-responsive mb-4">
      <table class="table align-middle border">
        <thead class="table-light">
          <tr>
            <th>Description</th>
            <th class="text-center" style="width: 100px;">Qty</th>
            <th class="text-end" style="width: 150px;">Amount</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <div class="fw-bold text-dark"><?= e($invoice['service_name']) ?></div>
              <div class="text-muted small">Comprehensive visa filing, document verification &amp; consular handling</div>
            </td>
            <td class="text-center">1</td>
            <td class="text-end fw-bold text-dark"><?= format_currency((float)$invoice['amount']) ?></td>
          </tr>
        </tbody>
        <tfoot class="table-light">
          <tr>
            <th colspan="2" class="text-end">Total Amount Paid:</th>
            <th class="text-end text-success fs-5 fw-bold"><?= format_currency((float)$invoice['amount']) ?></th>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Transaction Reference Note -->
    <?php if (!empty($invoice['transaction_reference'])): ?>
      <div class="p-3 bg-light rounded border mb-4 small">
        <strong>Bank / Card Reference:</strong> <span class="font-monospace"><?= e($invoice['transaction_reference']) ?></span>
      </div>
    <?php endif; ?>

    <div class="text-center text-muted small pt-3 border-top">
      Thank you for choosing VISA TRACK &amp; MS TRAVEL HUB for your visa processing needs.
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
