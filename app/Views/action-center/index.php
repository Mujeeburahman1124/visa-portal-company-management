<?php
$pageTitle = 'Operational Action Center — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
?>

<div class="content-body">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <div class="d-flex align-items-center gap-2">
        <h3 class="fw-bold brand-font text-dark mb-0">Action Center &amp; Priority Triage</h3>
        <span class="badge bg-danger px-2 py-1 fw-bold"><i class="fa-solid fa-bolt me-1"></i>SLA Priority</span>
      </div>
      <p class="text-muted small mb-0">Prioritized operational triage queue for missing documents, verification requests, and approaching deadlines.</p>
    </div>

    <!-- My Actions vs Team Actions Switcher -->
    <div class="btn-group shadow-sm bg-white p-1 rounded border">
      <a href="/action-center?scope=my" class="btn btn-sm <?= $scope === 'my' ? 'btn-primary' : 'btn-light text-dark' ?> px-3 fw-semibold">
        <i class="fa-solid fa-user me-1"></i> My Actions
      </a>
      <a href="/action-center?scope=team" class="btn btn-sm <?= $scope === 'team' ? 'btn-primary' : 'btn-light text-dark' ?> px-3 fw-semibold">
        <i class="fa-solid fa-users me-1"></i> Team Actions
      </a>
    </div>
  </div>

  <!-- Action Category Tabs -->
  <div class="card card-enterprise mb-4">
    <div class="card-header p-0">
      <ul class="nav nav-tabs card-header-tabs m-0 px-3" role="tablist">
        <li class="nav-item">
          <button class="nav-link active py-3 fw-semibold small" data-bs-toggle="tab" data-bs-target="#tab-missing">
            <i class="fa-solid fa-file-circle-exclamation text-danger me-1"></i> Missing Documents 
            <span class="badge bg-danger rounded-pill ms-1"><?= count($missingDocuments) ?></span>
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link py-3 fw-semibold small" data-bs-toggle="tab" data-bs-target="#tab-rejected">
            <i class="fa-solid fa-ban text-danger me-1"></i> Rejected Documents 
            <span class="badge bg-danger rounded-pill ms-1"><?= count($rejectedDocuments) ?></span>
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link py-3 fw-semibold small" data-bs-toggle="tab" data-bs-target="#tab-pending-verif">
            <i class="fa-solid fa-check-double text-info me-1"></i> Pending Verification 
            <span class="badge bg-info rounded-pill ms-1"><?= count($pendingVerifications) ?></span>
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link py-3 fw-semibold small" data-bs-toggle="tab" data-bs-target="#tab-overdue-tasks">
            <i class="fa-solid fa-list-check text-warning me-1"></i> Overdue Tasks 
            <span class="badge bg-warning text-dark rounded-pill ms-1"><?= count($overdueTasks) ?></span>
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link py-3 fw-semibold small" data-bs-toggle="tab" data-bs-target="#tab-deadlines">
            <i class="fa-solid fa-clock text-danger me-1"></i> Approaching Deadlines 
            <span class="badge bg-danger rounded-pill ms-1"><?= count($approachingDeadlines) ?></span>
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link py-3 fw-semibold small" data-bs-toggle="tab" data-bs-target="#tab-expiring-passports">
            <i class="fa-solid fa-id-card-clip text-secondary me-1"></i> Expiring Passports 
            <span class="badge bg-secondary rounded-pill ms-1"><?= count($expiringPassports) ?></span>
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link py-3 fw-semibold small" data-bs-toggle="tab" data-bs-target="#tab-stuck">
            <i class="fa-solid fa-hourglass-half text-warning me-1"></i> Stuck Stages 
            <span class="badge bg-warning text-dark rounded-pill ms-1"><?= count($stuckApplications) ?></span>
          </button>
        </li>
      </ul>
    </div>

    <div class="card-body p-0">
      <div class="tab-content">
        
        <!-- QUEUE 1: MISSING MANDATORY DOCUMENTS -->
        <div class="tab-pane fade show active" id="tab-missing" role="tabpanel">
          <div class="table-responsive">
            <table class="table-modern mb-0">
              <thead><tr><th>Document Requirement</th><th>Application #</th><th>Applicant</th><th>Priority</th><th>Case Officer</th><th class="text-end">Action</th></tr></thead>
              <tbody>
                <?php if (empty($missingDocuments)): ?>
                  <tr><td colspan="6" class="text-center py-4 text-muted">No missing document alerts in this scope.</td></tr>
                <?php else: ?>
                  <?php foreach ($missingDocuments as $mDoc): ?>
                    <tr>
                      <td><span class="fw-semibold text-danger"><i class="fa-solid fa-circle-exclamation me-1"></i> <?= e($mDoc['doc_name']) ?></span></td>
                      <td><a href="/applications/show?id=<?= $mDoc['app_id'] ?>" class="fw-bold text-primary text-decoration-none"><?= e($mDoc['application_number']) ?></a></td>
                      <td>
                        <div class="fw-semibold text-dark"><?= e($mDoc['customer_name']) ?></div>
                        <div class="text-muted small" style="font-size: 0.72rem;"><?= e($mDoc['mobile']) ?></div>
                      </td>
                      <td><span class="badge bg-<?= strtolower($mDoc['priority']) === 'urgent' || strtolower($mDoc['priority']) === 'critical' ? 'danger' : 'secondary' ?>"><?= e($mDoc['priority']) ?></span></td>
                      <td><span class="small text-muted"><?= e($mDoc['staff_name'] ?? 'Unassigned') ?></span></td>
                      <td class="text-end">
                        <a href="/applications/show?id=<?= $mDoc['app_id'] ?>#docs-pane" class="btn btn-sm btn-primary py-1 px-2" style="font-size: 0.75rem;">
                          Upload / Request &rarr;
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- QUEUE 2: REJECTED DOCUMENTS -->
        <div class="tab-pane fade" id="tab-rejected" role="tabpanel">
          <div class="table-responsive">
            <table class="table-modern mb-0">
              <thead><tr><th>Document Name</th><th>Rejection Reason</th><th>Application #</th><th>Customer</th><th class="text-end">Action</th></tr></thead>
              <tbody>
                <?php if (empty($rejectedDocuments)): ?>
                  <tr><td colspan="5" class="text-center py-4 text-muted">No rejected document re-uploads pending.</td></tr>
                <?php else: ?>
                  <?php foreach ($rejectedDocuments as $rDoc): ?>
                    <tr>
                      <td class="fw-bold text-danger"><?= e($rDoc['doc_name']) ?></td>
                      <td><span class="text-muted small"><?= e($rDoc['rejection_reason']) ?></span></td>
                      <td><a href="/applications/show?id=<?= $rDoc['app_id'] ?>" class="fw-bold text-primary text-decoration-none"><?= e($rDoc['application_number']) ?></a></td>
                      <td><?= e($rDoc['customer_name']) ?></td>
                      <td class="text-end">
                        <a href="/applications/show?id=<?= $rDoc['app_id'] ?>#docs-pane" class="btn btn-sm btn-danger py-1 px-2" style="font-size: 0.75rem;">
                          Follow Up &rarr;
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- QUEUE 3: PENDING VERIFICATION -->
        <div class="tab-pane fade" id="tab-pending-verif" role="tabpanel">
          <div class="table-responsive">
            <table class="table-modern mb-0">
              <thead><tr><th>Uploaded Document</th><th>Application #</th><th>Customer</th><th>Upload Type</th><th class="text-end">Action</th></tr></thead>
              <tbody>
                <?php if (empty($pendingVerifications)): ?>
                  <tr><td colspan="5" class="text-center py-4 text-muted">No documents awaiting verification.</td></tr>
                <?php else: ?>
                  <?php foreach ($pendingVerifications as $pDoc): ?>
                    <tr>
                      <td>
                        <div class="fw-semibold text-dark"><i class="fa-solid fa-file text-info me-1"></i> <?= e($pDoc['doc_name']) ?></div>
                        <div class="text-muted small" style="font-size: 0.72rem;"><?= e($pDoc['file_name']) ?></div>
                      </td>
                      <td><a href="/applications/show?id=<?= $pDoc['app_id'] ?>" class="fw-bold text-primary text-decoration-none"><?= e($pDoc['application_number']) ?></a></td>
                      <td><?= e($pDoc['customer_name']) ?></td>
                      <td><span class="badge bg-light text-secondary border"><?= e($pDoc['uploaded_by_type']) ?></span></td>
                      <td class="text-end">
                        <a href="/documents" class="btn btn-sm btn-info text-white py-1 px-2" style="font-size: 0.75rem;">
                          Inspect &amp; Verify &rarr;
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- QUEUE 4: OVERDUE TASKS -->
        <div class="tab-pane fade" id="tab-overdue-tasks" role="tabpanel">
          <div class="table-responsive">
            <table class="table-modern mb-0">
              <thead><tr><th>Task Title</th><th>Application #</th><th>Customer</th><th>Assigned To</th><th>Due Date</th><th class="text-end">Action</th></tr></thead>
              <tbody>
                <?php if (empty($overdueTasks)): ?>
                  <tr><td colspan="6" class="text-center py-4 text-muted">No overdue tasks.</td></tr>
                <?php else: ?>
                  <?php foreach ($overdueTasks as $oTask): ?>
                    <tr>
                      <td class="fw-semibold text-danger"><i class="fa-solid fa-clock-rotate-left me-1"></i> <?= e($oTask['title']) ?></td>
                      <td><a href="/applications/show?id=<?= $oTask['app_id'] ?>" class="fw-bold text-primary text-decoration-none"><?= e($oTask['application_number']) ?></a></td>
                      <td><?= e($oTask['customer_name']) ?></td>
                      <td><?= e($oTask['assigned_to_name'] ?? 'Unassigned') ?></td>
                      <td class="text-danger fw-bold small"><?= format_date($oTask['due_date']) ?></td>
                      <td class="text-end">
                        <a href="/tasks" class="btn btn-sm btn-warning text-dark py-1 px-2" style="font-size: 0.75rem;">
                          Resolve Task &rarr;
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- QUEUE 5: APPROACHING DEADLINES -->
        <div class="tab-pane fade" id="tab-deadlines" role="tabpanel">
          <div class="table-responsive">
            <table class="table-modern mb-0">
              <thead><tr><th>Application #</th><th>Applicant</th><th>Visa Service</th><th>Current Stage</th><th>Target Date</th><th class="text-end">Action</th></tr></thead>
              <tbody>
                <?php if (empty($approachingDeadlines)): ?>
                  <tr><td colspan="6" class="text-center py-4 text-muted">No applications nearing immediate deadlines.</td></tr>
                <?php else: ?>
                  <?php foreach ($approachingDeadlines as $aDl): ?>
                    <tr>
                      <td><a href="/applications/show?id=<?= $aDl['id'] ?>" class="fw-bold text-primary text-decoration-none"><?= e($aDl['application_number']) ?></a></td>
                      <td><?= e($aDl['customer_name']) ?></td>
                      <td><?= e($aDl['service_name']) ?></td>
                      <td><span class="badge bg-primary-subtle text-primary"><?= e($aDl['current_stage']) ?></span></td>
                      <td class="text-danger fw-bold small"><?= format_date($aDl['expected_completion_date']) ?></td>
                      <td class="text-end">
                        <a href="/applications/show?id=<?= $aDl['id'] ?>" class="btn btn-sm btn-primary py-1 px-2" style="font-size: 0.75rem;">
                          Advance Stage &rarr;
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- QUEUE 6: EXPIRING PASSPORTS -->
        <div class="tab-pane fade" id="tab-expiring-passports" role="tabpanel">
          <div class="table-responsive">
            <table class="table-modern mb-0">
              <thead><tr><th>Customer Name</th><th>Passport #</th><th>Expiry Date</th><th>Contact</th><th class="text-end">Action</th></tr></thead>
              <tbody>
                <?php if (empty($expiringPassports)): ?>
                  <tr><td colspan="5" class="text-center py-4 text-muted">No passports expiring within 90 days.</td></tr>
                <?php else: ?>
                  <?php foreach ($expiringPassports as $ePass): ?>
                    <tr>
                      <td class="fw-semibold text-dark"><?= e($ePass['customer_name'] ?? $ePass['full_name'] ?? 'Customer') ?></td>
                      <td><span class="badge bg-light text-secondary border font-monospace"><?= e($ePass['passport_number']) ?></span></td>
                      <td class="text-danger fw-bold small"><?= format_date($ePass['expiry_date']) ?></td>
                      <td class="small text-muted"><?= e($ePass['customer_mobile'] ?? $ePass['mobile'] ?? '—') ?></td>
                      <td class="text-end">
                        <a href="/customers/show?id=<?= $ePass['customer_id'] ?>" class="btn btn-sm btn-secondary py-1 px-2" style="font-size: 0.75rem;">
                          Update Passport &rarr;
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- QUEUE 7: STUCK STAGES -->
        <div class="tab-pane fade" id="tab-stuck" role="tabpanel">
          <div class="table-responsive">
            <table class="table-modern mb-0">
              <thead><tr><th>Application #</th><th>Applicant</th><th>Current Stage</th><th>Days in Stage</th><th>Officer</th><th class="text-end">Action</th></tr></thead>
              <tbody>
                <?php if (empty($stuckApplications)): ?>
                  <tr><td colspan="6" class="text-center py-4 text-muted">No stagnated visa applications detected.</td></tr>
                <?php else: ?>
                  <?php foreach ($stuckApplications as $sApp): ?>
                    <tr>
                      <td><a href="/applications/show?id=<?= $sApp['id'] ?>" class="fw-bold text-primary text-decoration-none"><?= e($sApp['application_number']) ?></a></td>
                      <td><?= e($sApp['customer_name']) ?></td>
                      <td><span class="badge bg-warning-subtle text-warning fw-semibold"><?= e($sApp['current_stage']) ?></span></td>
                      <td class="fw-bold text-danger"><?= (int)($sApp['days_in_stage'] ?? 0) ?> days</td>
                      <td><?= e($sApp['staff_name'] ?? 'Unassigned') ?></td>
                      <td class="text-end">
                        <a href="/applications/show?id=<?= $sApp['id'] ?>" class="btn btn-sm btn-primary py-1 px-2" style="font-size: 0.75rem;">
                          Expedite &rarr;
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
