<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h4 class="fw-bold mb-1 text-dark"><i class="fa-solid fa-folder-open text-success me-2"></i>My Client Applications</h4>
    <p class="text-muted small mb-0">View tracking status and progress for all visa applications submitted under your agency.</p>
  </div>
  <a href="/agent/create-application" class="btn btn-success fw-semibold">
    <i class="fa-solid fa-plus-circle me-1"></i>New Application
  </a>
</div>

<!-- Filters -->
<div class="agent-card mb-4 p-3">
  <form method="GET" action="/agent/applications" class="row g-2 align-items-center">
    <div class="col-md-4">
      <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by app #, client name..." value="<?= e($_GET['search'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <select name="status" class="form-select form-select-sm">
        <option value="">All Statuses</option>
        <option value="Pending" <?= ($_GET['status'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
        <option value="In Process" <?= ($_GET['status'] ?? '') === 'In Process' ? 'selected' : '' ?>>In Process</option>
        <option value="Action Required" <?= ($_GET['status'] ?? '') === 'Action Required' ? 'selected' : '' ?>>Action Required / Returned</option>
        <option value="Approved" <?= ($_GET['status'] ?? '') === 'Approved' ? 'selected' : '' ?>>Approved</option>
        <option value="Rejected" <?= ($_GET['status'] ?? '') === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
      </select>
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-sm btn-success w-100"><i class="fa-solid fa-filter me-1"></i>Filter</button>
    </div>
    <div class="col-md-2">
      <a href="/agent/applications" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
    </div>
  </form>
</div>

<!-- Applications Table -->
<div class="agent-card">
  <?php if (empty($applications)): ?>
    <div class="text-center py-5 text-muted">
      <i class="fa-solid fa-folder-open fa-3x mb-3 opacity-25"></i>
      <h5>No Applications Found</h5>
      <p class="small">No visa applications match your criteria.</p>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Application #</th>
            <th>Your Ref</th>
            <th>Client Name</th>
            <th>Contact</th>
            <th>Destination / Service</th>
            <th>Current Stage</th>
            <th>Status</th>
            <th>Balance</th>
            <th>Applied Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($applications as $app): ?>
            <tr>
              <td><span class="fw-bold text-success"><?= e($app['application_number']) ?></span></td>
              <td class="text-muted small"><?= e($app['agent_reference'] ?: '—') ?></td>
              <td>
                <div class="fw-semibold text-dark"><?= e($app['customer_name']) ?></div>
                <div class="text-muted small" style="font-size:0.75rem;"><?= e($app['customer_code']) ?></div>
              </td>
              <td class="small"><?= e($app['customer_mobile']) ?></td>
              <td>
                <span class="me-1"><?= $app['flag_emoji'] ?? '🏳️' ?></span>
                <span class="fw-medium"><?= e($app['country_name'] ?? '') ?></span>
                <div class="text-muted small"><?= e($app['service_name'] ?? '') ?></div>
              </td>
              <td><span class="badge bg-light text-dark border"><?= e($app['current_stage'] ?? 'Pending') ?></span></td>
              <td>
                <?php
                  $st = $app['status'];
                  $badge = 'bg-secondary';
                  if ($st === 'Approved') $badge = 'bg-success';
                  elseif ($st === 'In Process') $badge = 'bg-info text-dark';
                  elseif ($st === 'Pending') $badge = 'bg-warning text-dark';
                  elseif ($st === 'Action Required') $badge = 'bg-danger';
                  elseif ($st === 'Rejected') $badge = 'bg-dark';
                ?>
                <span class="badge <?= $badge ?>"><?= e($st) ?></span>
              </td>
              <td class="fw-semibold text-dark"><?= format_currency((float)($app['balance_amount'] ?? 0)) ?></td>
              <td class="text-muted small"><?= date('M d, Y', strtotime($app['created_at'])) ?></td>
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
