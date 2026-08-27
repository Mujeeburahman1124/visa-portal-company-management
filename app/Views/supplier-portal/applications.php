<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h4 class="fw-bold mb-1 text-dark"><i class="fa-solid fa-file-invoice text-primary me-2"></i>Assigned Processing Applications</h4>
    <p class="text-muted small mb-0">Manage and update embassy/consular references and tracking notes for applications assigned to your agency.</p>
  </div>
</div>

<!-- Filters -->
<div class="supplier-card mb-4 p-3">
  <form method="GET" action="/supplier/applications" class="row g-2 align-items-center">
    <div class="col-md-5">
      <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by app #, applicant name, supplier ref..." value="<?= e($_GET['search'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <select name="status" class="form-select form-select-sm">
        <option value="">All Statuses</option>
        <option value="Pending" <?= ($_GET['status'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
        <option value="In Process" <?= ($_GET['status'] ?? '') === 'In Process' ? 'selected' : '' ?>>In Process</option>
        <option value="Approved" <?= ($_GET['status'] ?? '') === 'Approved' ? 'selected' : '' ?>>Approved</option>
        <option value="Rejected" <?= ($_GET['status'] ?? '') === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
      </select>
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-sm btn-dark w-100"><i class="fa-solid fa-filter me-1"></i>Filter</button>
    </div>
    <div class="col-md-2">
      <a href="/supplier/applications" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
    </div>
  </form>
</div>

<!-- Applications Table -->
<div class="supplier-card">
  <?php if (empty($applications)): ?>
    <div class="text-center py-5 text-muted">
      <i class="fa-solid fa-folder-open fa-3x mb-3 opacity-25"></i>
      <h5>No Assigned Applications</h5>
      <p class="small">No visa applications currently assigned to your account match the filter.</p>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Application #</th>
            <th>Applicant Name</th>
            <th>Contact</th>
            <th>Destination / Visa</th>
            <th>Current Stage</th>
            <th>Status</th>
            <th>Your Supplier Ref</th>
            <th class="text-end">Update</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($applications as $app): ?>
            <tr>
              <td><span class="fw-bold text-primary"><?= e($app['application_number']) ?></span></td>
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
                  elseif ($st === 'Rejected') $badge = 'bg-danger';
                ?>
                <span class="badge <?= $badge ?>"><?= e($st) ?></span>
              </td>
              <td>
                <span class="badge bg-light text-dark border"><?= e($app['supplier_reference'] ?: 'Not Provided') ?></span>
              </td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#updateModal<?= $app['id'] ?>">
                  <i class="fa-solid fa-pen-to-square me-1"></i>Update
                </button>
              </td>
            </tr>

            <!-- Modal for Updating Supplier Reference & Notes -->
            <div class="modal fade" id="updateModal<?= $app['id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                  <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold fs-6">Update <?= e($app['application_number']) ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <form action="/supplier/update-status" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                    <div class="modal-body p-4">
                      <div class="mb-3">
                        <label class="form-label small fw-semibold">Embassy / Supplier Reference Number</label>
                        <input type="text" name="supplier_reference" class="form-control" value="<?= e($app['supplier_reference'] ?? '') ?>" placeholder="e.g. EMB-2026-9908">
                        <div class="form-text">Your internal visa portal/clearing house tracking ID</div>
                      </div>
                      <div class="mb-0">
                        <label class="form-label small fw-semibold">Operational Status Note</label>
                        <textarea name="status_note" class="form-control" rows="3" placeholder="Add tracking update or remarks..."></textarea>
                      </div>
                    </div>
                    <div class="modal-footer bg-light border-top">
                      <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-dark fw-semibold">Save Status Update</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

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
