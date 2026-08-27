<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="stat-sup">
      <div class="stat-sup-icon bg-primary bg-opacity-10 text-primary">
        <i class="fa-solid fa-file-invoice"></i>
      </div>
      <div>
        <div class="text-muted small fw-semibold">Total Assigned</div>
        <h4 class="fw-bold mb-0 text-dark"><?= (int)$totalApps ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-sup">
      <div class="stat-sup-icon bg-warning bg-opacity-10 text-warning">
        <i class="fa-solid fa-hourglass-half"></i>
      </div>
      <div>
        <div class="text-muted small fw-semibold">In Processing</div>
        <h4 class="fw-bold mb-0 text-warning"><?= (int)$inProcess ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-sup">
      <div class="stat-sup-icon bg-success bg-opacity-10 text-success">
        <i class="fa-solid fa-circle-check"></i>
      </div>
      <div>
        <div class="text-muted small fw-semibold">Approved Visas</div>
        <h4 class="fw-bold mb-0 text-success"><?= (int)$approved ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-sup">
      <div class="stat-sup-icon bg-danger bg-opacity-10 text-danger">
        <i class="fa-solid fa-hand-holding-dollar"></i>
      </div>
      <div>
        <div class="text-muted small fw-semibold">Outstanding Payables</div>
        <h4 class="fw-bold mb-0 text-danger"><?= format_currency((float)$pending_pay) ?></h4>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="supplier-card">
      <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
        <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>Active Processing Workload</h5>
        <a href="/supplier/applications" class="btn btn-sm btn-outline-dark">View All Applications</a>
      </div>
      <?php if (empty($recentApps)): ?>
        <div class="text-center py-4 text-muted">
          <i class="fa-solid fa-folder-open fa-2x mb-2 opacity-25"></i>
          <p class="mb-0">No visa applications currently assigned to your supplier queue.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
              <tr>
                <th>App #</th>
                <th>Applicant</th>
                <th>Destination / Visa</th>
                <th>Stage</th>
                <th>Status</th>
                <th>Supplier Ref</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentApps as $app): ?>
                <tr>
                  <td class="fw-bold text-primary"><?= e($app['application_number']) ?></td>
                  <td>
                    <div class="fw-semibold"><?= e($app['customer_name']) ?></div>
                    <div class="text-muted" style="font-size:0.75rem;"><?= e($app['customer_code']) ?></div>
                  </td>
                  <td><?= $app['flag_emoji'] ?? '🏳️' ?> <?= e($app['country_name'] ?? '') ?> - <?= e($app['service_name'] ?? '') ?></td>
                  <td><span class="badge bg-light text-dark border"><?= e($app['current_stage'] ?? 'Pending') ?></span></td>
                  <td>
                    <?php
                      $st = $app['status'];
                      $badge = 'bg-secondary';
                      if ($st === 'Approved') $badge = 'bg-success';
                      elseif ($st === 'In Process') $badge = 'bg-info text-dark';
                      elseif ($st === 'Pending') $badge = 'bg-warning text-dark';
                      elseif ($st === 'Rejected') $badge = 'bg-danger';
                    ?>
                    <span class="badge <?= $badge ?>"><?= e($st) ?></span>
                  </td>
                  <td><span class="badge bg-light text-muted border"><?= e($app['supplier_reference'] ?: 'None') ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="supplier-card mb-4">
      <h6 class="fw-bold mb-3 border-bottom pb-2"><i class="fa-solid fa-bolt text-warning me-2"></i>Quick Access</h6>
      <div class="d-grid gap-2">
        <a href="/supplier/applications" class="btn btn-dark fw-semibold"><i class="fa-solid fa-tasks me-2"></i>Update Processing Status</a>
        <a href="/supplier/payments" class="btn btn-outline-secondary"><i class="fa-solid fa-file-invoice-dollar me-2"></i>View Payment Statements</a>
        <a href="/supplier/profile" class="btn btn-outline-secondary"><i class="fa-solid fa-user-gear me-2"></i>Update Account Details</a>
      </div>
    </div>

    <div class="supplier-card">
      <h6 class="fw-bold mb-3 border-bottom pb-2"><i class="fa-solid fa-receipt text-success me-2"></i>Recent Settlement Disbursals</h6>
      <?php if (empty($recentPayments)): ?>
        <p class="text-muted small mb-0">No settlement disbursements recorded yet.</p>
      <?php else: ?>
        <ul class="list-group list-group-flush small">
          <?php foreach ($recentPayments as $p): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
              <div>
                <div class="fw-semibold text-dark"><?= e($p['payment_reference']) ?></div>
                <div class="text-muted" style="font-size:0.75rem;"><?= e($p['payment_date']) ?> &bull; <?= e($p['application_number'] ?: 'Consolidated') ?></div>
              </div>
              <span class="fw-bold text-success">+<?= format_currency((float)$p['paid_amount']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
