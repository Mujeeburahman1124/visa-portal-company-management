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
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($applications as $app): ?>
            <tr>
              <td><span class="fw-bold text-success"><?= e($app['application_number']) ?></span></td>
              <td>
                <span class="badge bg-light text-secondary border font-monospace"><?= e($app['agent_reference'] ?: 'None') ?></span>
              </td>
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
                  elseif ($st === 'In Process' || $st === 'Under Processing') $badge = 'bg-info text-dark';
                  elseif ($st === 'Pending' || $st === 'Application Registered') $badge = 'bg-warning text-dark';
                  elseif ($st === 'Action Required') $badge = 'bg-danger';
                  elseif ($st === 'Rejected') $badge = 'bg-dark';
                ?>
                <span class="badge <?= $badge ?>"><?= e($st) ?></span>
              </td>
              <td class="fw-semibold text-dark"><?= format_currency((float)($app['balance_amount'] ?? 0)) ?></td>
              <td class="text-muted small"><?= date('M d, Y', strtotime($app['created_at'])) ?></td>
              <td class="text-end">
                <div class="d-inline-flex gap-1">
                  <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#viewModal<?= $app['id'] ?>" title="View Application Details">
                    <i class="fa-solid fa-eye me-1"></i>View
                  </button>
                  <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal<?= $app['id'] ?>" title="Update Agency Reference / Notes">
                    <i class="fa-solid fa-pen me-1"></i>Update
                  </button>
                </div>
              </td>
            </tr>

            <!-- View Modal -->
            <div class="modal fade" id="viewModal<?= $app['id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow">
                  <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold fs-6">
                      <i class="fa-solid fa-passport me-2"></i>Application Details — <?= e($app['application_number']) ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                      <div class="col-md-6">
                        <label class="form-label small text-muted text-uppercase fw-bold mb-1">Applicant Details</label>
                        <div class="p-3 bg-light rounded border">
                          <h6 class="fw-bold mb-1"><?= e($app['customer_name']) ?></h6>
                          <div class="small text-muted mb-1"><i class="fa-solid fa-id-badge me-1"></i>Code: <?= e($app['customer_code']) ?></div>
                          <div class="small text-muted mb-1"><i class="fa-solid fa-phone me-1"></i><?= e($app['customer_mobile']) ?></div>
                          <div class="small text-muted"><i class="fa-solid fa-passport me-1"></i>Passport: <?= e($app['passport_number'] ?? 'On File') ?></div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label small text-muted text-uppercase fw-bold mb-1">Visa Service &amp; Stage</label>
                        <div class="p-3 bg-light rounded border">
                          <h6 class="fw-bold text-success mb-1"><?= $app['flag_emoji'] ?? '🏳️' ?> <?= e($app['country_name'] ?? '') ?></h6>
                          <div class="small fw-semibold mb-1"><?= e($app['service_name'] ?? '') ?></div>
                          <div class="small mb-1"><strong>Current Stage:</strong> <span class="badge bg-light text-dark border"><?= e($app['current_stage'] ?? 'Pending') ?></span></div>
                          <div class="small"><strong>Status:</strong> <span class="badge <?= $badge ?>"><?= e($st) ?></span></div>
                        </div>
                      </div>
                    </div>

                    <div class="row g-3 mb-3">
                      <div class="col-md-4">
                        <div class="p-2.5 bg-light rounded border text-center">
                          <div class="text-muted small">Package Price</div>
                          <div class="fw-bold text-dark fs-6"><?= format_currency((float)($app['agent_price'] > 0 ? $app['agent_price'] : $app['selling_price'])) ?></div>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="p-2.5 bg-light rounded border text-center">
                          <div class="text-muted small">Paid Amount</div>
                          <div class="fw-bold text-success fs-6"><?= format_currency((float)($app['paid_amount'] ?? 0)) ?></div>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="p-2.5 bg-light rounded border text-center">
                          <div class="text-muted small">Balance Due</div>
                          <div class="fw-bold text-danger fs-6"><?= format_currency((float)($app['balance_amount'] ?? 0)) ?></div>
                        </div>
                      </div>
                    </div>

                    <?php if (!empty($app['internal_notes'])): ?>
                      <div class="mb-0">
                        <label class="form-label small text-muted text-uppercase fw-bold mb-1">Processing Remarks &amp; History</label>
                        <div class="p-3 bg-light rounded border small font-monospace text-secondary" style="white-space: pre-wrap;"><?= e($app['internal_notes']) ?></div>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $app['id'] ?>">
                      <i class="fa-solid fa-pen me-1"></i>Update Agency Ref / Notes
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Update Modal -->
            <div class="modal fade" id="editModal<?= $app['id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                  <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold fs-6">
                      <i class="fa-solid fa-pen-to-square me-2"></i>Update Application Reference — <?= e($app['application_number']) ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <form action="/agent/applications/update" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                    <div class="modal-body p-4">
                      <div class="mb-3">
                        <label class="form-label small fw-semibold">Your Agency Reference Code</label>
                        <input type="text" name="agent_reference" class="form-control" value="<?= e($app['agent_reference'] ?? '') ?>" placeholder="e.g. SKY-DXB-9901">
                        <div class="form-text">Your agency's internal booking or invoice reference</div>
                      </div>
                      <div class="mb-0">
                        <label class="form-label small fw-semibold">Add Processing Note / Instruction</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Add any special instructions or status inquiries for the processing desk..."></textarea>
                      </div>
                    </div>
                    <div class="modal-footer bg-light border-top">
                      <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-success fw-semibold">Save Changes</button>
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
