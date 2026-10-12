<?php require_once __DIR__ . '/navbar.php'; ?>

<!-- ─── HEADER STRIP ──────────────────────────────────────────────────────── -->
<div class="agent-header-strip">
  <div>
    <h1 class="agent-header-title">My Client Applications</h1>
    <p class="agent-header-subtitle">
      View tracking status and progress for all visa applications submitted under your agency.
    </p>
  </div>
  <a href="/agent/create-application" class="agent-btn-primary">
    <i class="fa-solid fa-circle-plus"></i>
    <span>New Application</span>
  </a>
</div>

<!-- ─── BENTO FILTERS CARD ────────────────────────────────────────────────── -->
<div class="agent-bento-card mb-4 p-3">
  <form method="GET" action="/agent/applications" class="row g-2 align-items-center">
    <div class="col-md-5">
      <div class="input-group">
        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="text" name="search" class="form-control border-start-0 ps-0 agent-form-control" placeholder="Search by app #, client name, passport..." value="<?= e($_GET['search'] ?? '') ?>">
      </div>
    </div>
    <div class="col-md-3">
      <select name="status" class="form-select agent-form-control">
        <option value="">All Statuses</option>
        <option value="Pending" <?= ($_GET['status'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
        <option value="In Process" <?= ($_GET['status'] ?? '') === 'In Process' ? 'selected' : '' ?>>In Process</option>
        <option value="Action Required" <?= ($_GET['status'] ?? '') === 'Action Required' ? 'selected' : '' ?>>Action Required / Returned</option>
        <option value="Approved" <?= ($_GET['status'] ?? '') === 'Approved' ? 'selected' : '' ?>>Approved</option>
        <option value="Rejected" <?= ($_GET['status'] ?? '') === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
      </select>
    </div>
    <div class="col-md-2">
      <button type="submit" class="agent-btn-primary w-100 justify-content-center py-2" style="font-size:0.85rem;">
        <i class="fa-solid fa-filter"></i>
        <span>Filter</span>
      </button>
    </div>
    <div class="col-md-2">
      <a href="/agent/applications" class="agent-btn-outline w-100 justify-content-center py-2" style="font-size:0.85rem;">
        <i class="fa-solid fa-rotate-left"></i>
        <span>Reset</span>
      </a>
    </div>
  </form>
</div>

<!-- ─── APPLICATIONS TABLE CARD ───────────────────────────────────────────── -->
<div class="agent-bento-card">
  <?php if (empty($applications)): ?>
    <div class="agent-empty-box">
      <div class="agent-empty-icon-circle">
        <i class="fa-solid fa-folder-open"></i>
      </div>
      <h4 class="agent-empty-title">No Applications Found</h4>
      <p class="agent-empty-desc">
        No visa applications match your current search or filter criteria.
      </p>
      <a href="/agent/create-application" class="agent-btn-primary">
        <i class="fa-solid fa-circle-plus"></i>
        <span>Submit New Application</span>
      </a>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="agent-table">
        <thead>
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
            <?php
              $st = $app['status'];
              $badgeClass = 'status-pending';
              if ($st === 'Approved') $badgeClass = 'status-approved';
              elseif ($st === 'In Process' || $st === 'Under Processing') $badgeClass = 'status-processing';
              elseif ($st === 'Action Required') $badgeClass = 'status-action';
              elseif ($st === 'Rejected') $badgeClass = 'status-rejected';
            ?>
            <tr>
              <td>
                <span class="fw-bold" style="color:var(--agent-primary); font-family:'SFMono-Regular', Menlo, Monaco, Consolas, monospace;">
                  <?= e($app['application_number']) ?>
                </span>
              </td>
              <td>
                <span class="badge bg-light text-secondary border font-monospace px-2 py-1"><?= e($app['agent_reference'] ?: 'None') ?></span>
              </td>
              <td>
                <div class="fw-bold text-dark"><?= e($app['customer_name']) ?></div>
                <div class="text-muted small" style="font-size:0.75rem;"><?= e($app['customer_code'] ?? '') ?></div>
              </td>
              <td class="small text-muted"><?= e($app['customer_mobile'] ?? '—') ?></td>
              <td>
                <div class="d-flex align-items-center gap-1.5">
                  <span class="fs-6"><?= $app['flag_emoji'] ?? '🏳️' ?></span>
                  <span class="fw-semibold text-dark"><?= e($app['country_name'] ?? '') ?></span>
                </div>
                <div class="text-muted small" style="font-size:0.75rem;"><?= e($app['service_name'] ?? '') ?></div>
              </td>
              <td>
                <span class="badge bg-light text-dark border px-2 py-1 font-monospace" style="font-size:0.75rem;">
                  <?= e($app['current_stage'] ?? 'Pending') ?>
                </span>
              </td>
              <td>
                <span class="agent-status-badge <?= $badgeClass ?>">
                  <i class="fa-solid fa-circle fs-xs" style="font-size: 0.45rem;"></i>
                  <?= e($st) ?>
                </span>
              </td>
              <td class="fw-semibold text-dark"><?= format_currency((float)($app['balance_amount'] ?? 0)) ?></td>
              <td class="text-muted small"><?= date('M d, Y', strtotime($app['created_at'])) ?></td>
              <td class="text-end">
                <div class="d-inline-flex gap-1.5">
                  <button class="agent-btn-outline py-1 px-2.5" style="font-size:0.78rem;" data-bs-toggle="modal" data-bs-target="#viewModal<?= $app['id'] ?>" title="View Application Details">
                    <i class="fa-solid fa-eye text-primary"></i> <span>View</span>
                  </button>
                  <button class="agent-btn-outline py-1 px-2.5" style="font-size:0.78rem;" data-bs-toggle="modal" data-bs-target="#editModal<?= $app['id'] ?>" title="Update Agency Reference / Notes">
                    <i class="fa-solid fa-pen" style="color:var(--agent-primary);"></i> <span>Update</span>
                  </button>
                </div>
              </td>
            </tr>

            <!-- View Modal -->
            <div class="modal fade" id="viewModal<?= $app['id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                  <div class="modal-header text-white" style="background: linear-gradient(135deg, var(--agent-primary) 0%, var(--agent-primary-hover) 100%);">
                    <h5 class="modal-title fw-bold fs-6">
                      <i class="fa-solid fa-passport me-2"></i>Application Details &bull; <?= e($app['application_number']) ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                      <div class="col-md-6">
                        <label class="form-label small text-muted text-uppercase fw-bold mb-1">Applicant Details</label>
                        <div class="p-3 bg-light rounded-3 border">
                          <h6 class="fw-bold mb-1 text-dark"><?= e($app['customer_name']) ?></h6>
                          <div class="small text-muted mb-1"><i class="fa-solid fa-id-badge me-1"></i>Code: <?= e($app['customer_code'] ?? '') ?></div>
                          <div class="small text-muted mb-1"><i class="fa-solid fa-phone me-1"></i><?= e($app['customer_mobile'] ?? '') ?></div>
                          <div class="small text-muted"><i class="fa-solid fa-passport me-1"></i>Passport: <?= e($app['passport_number'] ?? 'On File') ?></div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label small text-muted text-uppercase fw-bold mb-1">Visa Service &amp; Stage</label>
                        <div class="p-3 bg-light rounded-3 border">
                          <h6 class="fw-bold mb-1" style="color:var(--agent-primary);"><?= $app['flag_emoji'] ?? '🏳️' ?> <?= e($app['country_name'] ?? '') ?></h6>
                          <div class="small fw-semibold mb-1 text-dark"><?= e($app['service_name'] ?? '') ?></div>
                          <div class="small mb-1"><strong>Current Stage:</strong> <span class="badge bg-white text-dark border"><?= e($app['current_stage'] ?? 'Pending') ?></span></div>
                          <div class="small"><strong>Status:</strong> <span class="agent-status-badge <?= $badgeClass ?> ms-1"><?= e($st) ?></span></div>
                        </div>
                      </div>
                    </div>

                    <div class="row g-3 mb-3">
                      <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3 border text-center">
                          <div class="text-muted small">Package Price</div>
                          <div class="fw-bold text-dark fs-6"><?= format_currency((float)($app['agent_price'] > 0 ? $app['agent_price'] : $app['selling_price'])) ?></div>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3 border text-center">
                          <div class="text-muted small">Paid Amount</div>
                          <div class="fw-bold text-success fs-6"><?= format_currency((float)($app['paid_amount'] ?? 0)) ?></div>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3 border text-center">
                          <div class="text-muted small">Balance Due</div>
                          <div class="fw-bold text-danger fs-6"><?= format_currency((float)($app['balance_amount'] ?? 0)) ?></div>
                        </div>
                      </div>
                    </div>

                    <?php if (!empty($app['internal_notes'])): ?>
                      <div class="mb-0">
                        <label class="form-label small text-muted text-uppercase fw-bold mb-1">Processing Remarks &amp; History</label>
                        <div class="p-3 bg-light rounded-3 border small font-monospace text-secondary" style="white-space: pre-wrap;"><?= e($app['internal_notes']) ?></div>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="agent-btn-primary py-1.5 px-3" style="font-size:0.85rem;" data-bs-toggle="modal" data-bs-target="#editModal<?= $app['id'] ?>">
                      <i class="fa-solid fa-pen me-1"></i>Update Agency Ref / Notes
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Update Modal -->
            <div class="modal fade" id="editModal<?= $app['id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                  <div class="modal-header text-white" style="background: var(--agent-dark-surface);">
                    <h5 class="modal-title fw-bold fs-6">
                      <i class="fa-solid fa-pen-to-square me-2" style="color:var(--agent-primary);"></i>Update Reference &bull; <?= e($app['application_number']) ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <form action="/agent/applications/update" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                    <div class="modal-body p-4">
                      <div class="mb-3">
                        <label class="form-label small fw-semibold">Your Agency Reference Code</label>
                        <input type="text" name="agent_reference" class="form-control agent-form-control" value="<?= e($app['agent_reference'] ?? '') ?>" placeholder="e.g. SKY-DXB-9901">
                        <div class="form-text small">Your agency's internal booking or client file reference</div>
                      </div>
                      <div class="mb-0">
                        <label class="form-label small fw-semibold">Add Processing Note / Instruction</label>
                        <textarea name="notes" class="form-control agent-form-control" rows="3" placeholder="Add instructions or inquiries for the processing desk..."></textarea>
                      </div>
                    </div>
                    <div class="modal-footer bg-light border-top">
                      <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="agent-btn-primary py-1.5 px-3" style="font-size:0.85rem;">Save Changes</button>
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

</div><!-- /.container-fluid -->

<footer class="text-center py-4 text-muted small" style="border-top: 1px solid var(--agent-border-subtle);">
  &copy; <?= date('Y') ?> <strong style="color:var(--agent-text-main);">MS TRAVEL HUB</strong> &bull; Agent Partner Portal &bull; Enterprise Visa Solutions
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
