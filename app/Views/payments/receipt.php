<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Receipt — <?= e($payment['payment_number']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    .receipt-card { max-width: 680px; margin: 2rem auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 2.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    @media print {
      body { background: #fff; }
      .receipt-card { border: none; box-shadow: none; margin: 0; padding: 0; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>

<div class="container">
  <div class="text-end mb-3 no-print mt-3" style="max-width: 680px; margin: 0 auto;">
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fa-solid fa-print me-1"></i> Print / Save PDF</button>
  </div>

  <div class="receipt-card">
    <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
      <div class="d-flex align-items-center gap-3">
        <img src="/assets/images/logo.png" alt="MS Travel Hub" style="height: 54px; width: auto;">
        <div>
          <div class="fw-bold fs-4 text-dark">MS TRAVEL HUB GLOBAL</div>
          <div class="text-muted small">Global Visa Processing &amp; Document Clearing Services</div>
          <div class="text-muted small">Business Bay, Dubai, United Arab Emirates</div>
        </div>
      </div>
      <div class="text-end">
        <span class="badge bg-success fs-6 px-3 py-2">OFFICIAL RECEIPT</span>
        <div class="fw-bold fs-6 mt-2 text-dark"><?= e($payment['payment_number']) ?></div>
        <div class="text-muted small">Date: <?= format_date($payment['payment_date']) ?></div>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-6">
        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.7rem;">RECEIVED FROM / APPLICANT:</div>
        <div class="fw-bold fs-6 text-dark"><?= e($payment['customer_name']) ?></div>
        <div class="text-muted small">Applicant ID: <strong><?= e($payment['customer_code']) ?></strong></div>
        <div class="text-muted small">Passport Number: <strong><?= e($payment['passport_number'] ?? 'On File') ?></strong></div>
        <div class="text-muted small"><?= e($payment['customer_mobile']) ?> &bull; <?= e($payment['customer_email']) ?></div>
      </div>
      <div class="col-6 text-end">
        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.7rem;">APPLICATION &amp; DESTINATION:</div>
        <div class="fw-bold fs-6 text-primary"><?= e($payment['application_number']) ?></div>
        <div class="fw-semibold text-dark"><?= e($payment['country_name']) ?> &mdash; <?= e($payment['service_name']) ?></div>
        <div class="text-muted small">Invoice Number: <strong><?= e($payment['invoice_number']) ?></strong></div>
      </div>
    </div>

    <table class="table table-bordered mb-4">
      <thead class="table-light">
        <tr>
          <th>Description</th>
          <th>Payment Method</th>
          <th>Reference #</th>
          <th class="text-end">Amount Paid</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <div class="fw-semibold">Visa Processing Fee Settlement</div>
            <div class="text-muted small"><?= e($payment['service_name']) ?> (<?= e($payment['country_name']) ?>)</div>
          </td>
          <td><?= e($payment['payment_method']) ?></td>
          <td><?= e($payment['transaction_reference'] ?: 'CASH_REC') ?></td>
          <td class="text-end fw-bold fs-6 text-success"><?= format_currency((float)$payment['amount']) ?></td>
        </tr>
      </tbody>
    </table>

    <div class="row justify-content-end mb-4">
      <div class="col-6">
        <div class="d-flex justify-content-between py-1 border-bottom small">
          <span class="text-muted">Total Application Fee:</span>
          <span class="fw-semibold"><?= format_currency((float)$payment['total_amount']) ?></span>
        </div>
        <div class="d-flex justify-content-between py-1 border-bottom small">
          <span class="text-muted">Total Paid to Date:</span>
          <span class="text-success fw-bold"><?= format_currency((float)$payment['paid_amount']) ?></span>
        </div>
        <div class="d-flex justify-content-between py-1 border-bottom small">
          <span class="text-muted">Remaining Balance:</span>
          <span class="text-danger fw-bold"><?= format_currency((float)$payment['balance_amount']) ?></span>
        </div>
      </div>
    </div>

    <div class="border-top pt-4 mt-4 d-flex justify-content-between align-items-center text-muted small">
      <div>
        <div>Authorized Signatory: <strong><?= e($payment['received_by_name'] ?? 'Accounts Department') ?></strong></div>
        <div style="font-size: 0.72rem;">Thank you for trusting MS Travel Hub Global Services.</div>
      </div>
      <div class="text-end">
        <i class="fa-solid fa-stamp text-primary opacity-50 fs-2"></i>
      </div>
    </div>
  </div>
</div>

</body>
</html>
