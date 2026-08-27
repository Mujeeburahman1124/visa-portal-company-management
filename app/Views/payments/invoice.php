<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tax Invoice — <?= e($application['application_number']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    .invoice-card { max-width: 780px; margin: 2rem auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 3rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    @media print {
      body { background: #fff; }
      .invoice-card { border: none; box-shadow: none; margin: 0; padding: 0; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>

<div class="container">
  <div class="text-end mb-3 no-print mt-3" style="max-width: 780px; margin: 0 auto;">
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fa-solid fa-print me-1"></i> Print / Download Invoice</button>
  </div>

  <div class="invoice-card">
    <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
      <div class="d-flex align-items-center gap-3">
        <img src="/assets/images/logo.png" alt="MS Travel Hub" style="height: 58px; width: auto;">
        <div>
          <div class="fw-bold fs-3 text-dark">MS TRAVEL HUB GLOBAL</div>
          <div class="text-muted small">Global Visa Processing &amp; Embassy Document Operations</div>
          <div class="text-muted small">TRN / Tax Registration: 100-8899-2233-0001</div>
          <div class="text-muted small">Dubai, UAE &bull; London, UK &bull; New York, USA</div>
        </div>
      </div>
      <div class="text-end">
        <span class="badge bg-danger fs-6 px-3 py-2">TAX INVOICE</span>
        <div class="fw-bold fs-5 mt-2 text-dark">INV-<?= date('Y', strtotime($application['application_date'])) ?>-<?= sprintf('%06d', $application['id']) ?></div>
        <div class="text-muted small">Invoice Date: <?= format_date($application['application_date']) ?></div>
        <div class="text-muted small">Status: <strong><?= e($application['status']) ?></strong></div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-6">
        <div class="text-muted small text-uppercase">BILLED TO:</div>
        <div class="fw-bold fs-6"><?= e($application['customer_name']) ?></div>
        <div class="text-muted small">Customer ID: <?= e($application['customer_code']) ?></div>
        <div class="text-muted small">Passport: <?= e($application['passport_number']) ?></div>
        <div class="text-muted small"><?= e($application['customer_address']) ?></div>
        <div class="text-muted small"><?= e($application['customer_mobile']) ?></div>
      </div>
      <div class="col-6 text-end">
        <div class="text-muted small text-uppercase">SERVICE SPECIFICATIONS:</div>
        <div class="fw-bold fs-6 text-primary"><?= e($application['application_number']) ?></div>
        <div class="text-muted small"><?= e($application['service_name']) ?> (<?= e($application['country_name']) ?>)</div>
        <div class="text-muted small"><?= e($application['entry_type']) ?> &bull; <?= e($application['processing_type']) ?></div>
        <div class="text-muted small">Account Officer: <?= e($application['staff_name']) ?></div>
      </div>
    </div>

    <table class="table table-bordered mb-4">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Service Item / Description</th>
          <th>Type</th>
          <th class="text-end">Unit Price</th>
          <th class="text-end">Total</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>1</td>
          <td>
            <div class="fw-semibold"><?= e($application['service_name']) ?></div>
            <div class="text-muted small">Complete application review, consular liaison, and smart checklist verification.</div>
          </td>
          <td><?= e($application['entry_type']) ?></td>
          <td class="text-end"><?= format_currency((float)$application['selling_price']) ?></td>
          <td class="text-end fw-bold"><?= format_currency((float)$application['selling_price']) ?></td>
        </tr>
      </tbody>
    </table>

    <div class="row justify-content-end mb-4">
      <div class="col-md-5">
        <div class="d-flex justify-content-between py-1 border-bottom small">
          <span class="text-muted">Subtotal:</span>
          <span><?= format_currency((float)$application['selling_price']) ?></span>
        </div>
        <?php if ((float)$application['discount'] > 0): ?>
          <div class="d-flex justify-content-between py-1 border-bottom small">
            <span class="text-muted">Discount Applied:</span>
            <span class="text-danger">-<?= format_currency((float)$application['discount']) ?></span>
          </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between py-1 border-bottom small">
          <span class="text-muted">VAT / Tax:</span>
          <span>+<?= format_currency((float)$application['tax_amount']) ?></span>
        </div>
        <div class="d-flex justify-content-between py-2 border-bottom fw-bold fs-6">
          <span>Final Total Due:</span>
          <span class="text-primary"><?= format_currency((float)$application['total_amount']) ?></span>
        </div>
        <div class="d-flex justify-content-between py-1 border-bottom small text-success">
          <span>Amount Paid to Date:</span>
          <span class="fw-bold"><?= format_currency((float)$application['paid_amount']) ?></span>
        </div>
        <div class="d-flex justify-content-between py-1 border-bottom small text-danger">
          <span>Outstanding Balance:</span>
          <span class="fw-bold"><?= format_currency((float)$application['balance_amount']) ?></span>
        </div>
      </div>
    </div>

    <!-- Settlement History -->
    <?php if (!empty($payments)): ?>
      <h6 class="fw-bold small text-uppercase text-muted mb-2">Recorded Payment Settlements:</h6>
      <table class="table table-sm table-bordered small mb-4">
        <thead class="table-light"><tr><th>Receipt #</th><th>Date</th><th>Method</th><th>Reference</th><th class="text-end">Amount</th></tr></thead>
        <tbody>
          <?php foreach ($payments as $pay): ?>
            <tr>
              <td><?= e($pay['payment_number']) ?></td>
              <td><?= format_date($pay['payment_date']) ?></td>
              <td><?= e($pay['payment_method']) ?></td>
              <td><?= e($pay['transaction_reference'] ?: 'CASH') ?></td>
              <td class="text-end fw-bold text-success"><?= format_currency((float)$pay['amount']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <div class="border-top pt-4 text-muted small d-flex justify-content-between align-items-center">
      <div>
        <div>Payment Methods: Bank Wire Transfer, Corporate Card, Cash Settlement.</div>
        <div style="font-size: 0.72rem;">MS Travel Hub Global Visa Services &bull; Computer Generated Tax Invoice</div>
      </div>
      <div class="text-end">
        <span class="badge bg-light text-dark border p-2">Official Tax Document</span>
      </div>
    </div>
  </div>
</div>

</body>
</html>
