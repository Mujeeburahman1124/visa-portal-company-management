<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="stat-agent">
      <div class="stat-agent-icon bg-success bg-opacity-10 text-success">
        <i class="fa-solid fa-folder-open"></i>
      </div>
      <div>
        <div class="text-muted small fw-semibold">Total Applications</div>
        <h4 class="fw-bold mb-0 text-dark"><?= (int)$totalApps ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-agent">
      <div class="stat-agent-icon bg-warning bg-opacity-10 text-warning">
        <i class="fa-solid fa-spinner"></i>
      </div>
      <div>
        <div class="text-muted small fw-semibold">In Process</div>
        <h4 class="fw-bold mb-0 text-warning"><?= (int)$activeApps ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-agent">
      <div class="stat-agent-icon bg-primary bg-opacity-10 text-primary">
        <i class="fa-solid fa-circle-check"></i>
      </div>
      <div>
        <div class="text-muted small fw-semibold">Approved Visas</div>
        <h4 class="fw-bold mb-0 text-primary"><?= (int)$approvedApps ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-agent">
      <div class="stat-agent-icon bg-danger bg-opacity-10 text-danger">
        <i class="fa-solid fa-wallet"></i>
      </div>
      <div>
        <div class="text-muted small fw-semibold">Account Balance</div>
        <h4 class="fw-bold mb-0 text-danger"><?= format_currency((float)($agentRow['current_balance'] ?? 0)) ?></h4>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="agent-card">
      <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
        <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-clock-rotate-left text-success me-2"></i>Recent Submissions</h5>
        <a href="/agent/applications" class="btn btn-sm btn-outline-success">View All</a>
      </div>
      <?php if (empty($recentApps)): ?>
        <div class="text-center py-4 text-muted">
          <i class="fa-solid fa-folder-open fa-2x mb-2 opacity-25"></i>
          <p>No applications submitted yet.</p>
          <a href="/agent/create-application" class="btn btn-sm btn-success"><i class="fa-solid fa-plus me-1"></i>Submit First Application</a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
              <tr>
                <th>App #</th>
                <th>Client</th>
                <th>Destination / Visa</th>
                <th>Stage</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentApps as $app): ?>
                <tr>
                  <td class="fw-bold text-success"><?= e($app['application_number']) ?></td>
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
                  <td class="text-muted"><?= date('M d, Y', strtotime($app['created_at'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="agent-card mb-4">
      <h6 class="fw-bold mb-3 border-bottom pb-2"><i class="fa-solid fa-bolt text-warning me-2"></i>Quick Actions</h6>
      <div class="d-grid gap-2">
        <a href="/agent/create-application" class="btn btn-success fw-semibold"><i class="fa-solid fa-plus-circle me-2"></i>Submit Application</a>
        <a href="/agent/applications" class="btn btn-outline-secondary"><i class="fa-solid fa-list-check me-2"></i>Track Applications</a>
        <a href="/agent/profile" class="btn btn-outline-secondary"><i class="fa-solid fa-receipt me-2"></i>Statement &amp; Payments</a>
      </div>
    </div>

    <div class="agent-card">
      <h6 class="fw-bold mb-3 border-bottom pb-2"><i class="fa-solid fa-receipt text-success me-2"></i>Recent Payments &amp; Credits</h6>
      <?php if (empty($recentPayments)): ?>
        <p class="text-muted small mb-0">No payment transactions recorded.</p>
      <?php else: ?>
        <ul class="list-group list-group-flush small">
          <?php foreach ($recentPayments as $p): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
              <div>
                <div class="fw-semibold text-dark"><?= e($p['payment_reference']) ?></div>
                <div class="text-muted" style="font-size:0.75rem;"><?= e($p['payment_date']) ?> &bull; <?= e($p['payment_method']) ?></div>
              </div>
              <span class="fw-bold text-success">+<?= format_currency((float)$p['amount']) ?></span>
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
