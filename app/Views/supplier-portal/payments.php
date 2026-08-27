<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h4 class="fw-bold mb-1 text-dark"><i class="fa-solid fa-money-bill-wave text-success me-2"></i>Payables &amp; Settlement Statements</h4>
    <p class="text-muted small mb-0">Financial records, invoice payables, and settlement disbursements from MS Travel Hub.</p>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="supplier-card text-center p-3">
      <div class="text-muted small">Total Cost of Services (Payable)</div>
      <h4 class="fw-bold text-dark mb-0"><?= format_currency((float)$totalPayable) ?></h4>
    </div>
  </div>
  <div class="col-md-4">
    <div class="supplier-card text-center p-3">
      <div class="text-muted small">Total Disbursed (Paid)</div>
      <h4 class="fw-bold text-success mb-0"><?= format_currency((float)$totalPaid) ?></h4>
    </div>
  </div>
  <div class="col-md-4">
    <div class="supplier-card text-center p-3">
      <div class="text-muted small">Outstanding Balance</div>
      <h4 class="fw-bold text-danger mb-0"><?= format_currency((float)($totalPayable - $totalPaid)) ?></h4>
    </div>
  </div>
</div>

<div class="supplier-card">
  <h5 class="fw-bold mb-3 text-dark border-bottom pb-2">Disbursement Transaction History</h5>
  <?php if (empty($payments)): ?>
    <div class="text-center py-5 text-muted">
      <i class="fa-solid fa-receipt fa-3x mb-3 opacity-25"></i>
      <p class="mb-0">No settlement payment records found.</p>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 small">
        <thead class="table-light">
          <tr>
            <th>Payment Reference</th>
            <th>Date</th>
            <th>Related Application</th>
            <th>Applicant</th>
            <th>Method</th>
            <th>Transaction Ref</th>
            <th>Payable</th>
            <th>Disbursed</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($payments as $p): ?>
            <tr>
              <td><span class="fw-bold text-dark"><?= e($p['payment_reference']) ?></span></td>
              <td><?= e($p['payment_date']) ?></td>
              <td><span class="badge bg-light text-primary border"><?= e($p['application_number'] ?: 'Bulk Settlement') ?></span></td>
              <td><?= e($p['customer_name'] ?? '—') ?></td>
              <td><span class="badge bg-light text-dark border"><?= e($p['payment_method']) ?></span></td>
              <td class="text-muted"><?= e($p['transaction_reference'] ?: '—') ?></td>
              <td><?= format_currency((float)$p['payable_amount']) ?></td>
              <td class="fw-bold text-success"><?= format_currency((float)$p['paid_amount']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
