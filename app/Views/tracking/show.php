<?php
$pageTitle = 'Visa Tracking: ' . ($app['application_number'] ?? 'Journey') . ' — VISA TRACK';
$flash = get_flash();
$isStaff = is_authenticated();
$isCustomer = is_customer_authenticated();

if ($isStaff) {
    require_once dirname(__DIR__) . '/layouts/header.php';
    require_once dirname(__DIR__) . '/layouts/sidebar.php';
    require_once dirname(__DIR__) . '/layouts/topbar.php';
} else {
    require_once dirname(__DIR__) . '/portal/header.php';
    require_once dirname(__DIR__) . '/portal/navbar.php';
}

$statusColor = 'primary';
if ($isApproved) $statusColor = 'success';
elseif ($isRejected) $statusColor = 'danger';
elseif ($isReturned) $statusColor = 'warning';
elseif ($isOnHold) $statusColor = 'secondary';

$healthColor = 'success';
if ((int)($app['calculated_health'] ?? 100) < 50) $healthColor = 'danger';
elseif ((int)($app['calculated_health'] ?? 100) < 80) $healthColor = 'warning';
?>

<div class="<?= $isStaff ? 'content-body' : 'container py-4' ?>" style="font-family: 'Times New Roman', Times, serif;">

  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Top Navigation & Action Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <a href="<?= $isStaff ? '/tracking' : '/portal/dashboard' ?>" class="btn btn-outline-secondary btn-sm px-2.5 py-1">
          <i class="fa-solid fa-arrow-left me-1"></i> Back to Tracking
        </a>
        <span class="badge bg-light text-secondary border font-monospace"><?= e($app['application_number']) ?></span>
        <?php if (!empty($app['visa_number'])): ?>
          <span class="badge bg-success-subtle text-success border border-success font-monospace">
            <i class="fa-solid fa-stamp me-1"></i> Visa # <?= e($app['visa_number']) ?>
          </span>
        <?php endif; ?>
      </div>
      <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
        <span>Visa Journey &amp; Lifecycle Tracking</span>
      </h3>
    </div>

    <div class="d-flex align-items-center gap-2">
      <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-print me-1"></i> Print Tracking
      </button>
      <?php if ($isStaff): ?>
        <a href="/applications/show?id=<?= (int)$app['id'] ?>" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-folder-open me-1"></i> Open Application Workspace
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- 1. PROMINENT CURRENT STATUS CARD -->
  <div class="card card-enterprise border-0 shadow-sm mb-4 border-start border-4 border-<?= $statusColor ?>">
    <div class="card-body p-4">
      <div class="row g-3 align-items-center justify-content-between">
        <div class="col-12 col-lg-7">
          <div class="small text-muted text-uppercase fw-semibold mb-1 letter-spacing-1">Current Lifecycle Status</div>
          <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
            <span class="badge bg-<?= $statusColor ?> bg-opacity-10 text-<?= $statusColor ?> border border-<?= $statusColor ?> fs-5 px-3 py-1.5 fw-bold text-nowrap">
              <?php if ($isApproved): ?><i class="fa-solid fa-circle-check me-1.5"></i>
              <?php elseif ($isRejected): ?><i class="fa-solid fa-circle-xmark me-1.5"></i>
              <?php elseif ($isReturned): ?><i class="fa-solid fa-rotate-left me-1.5"></i>
              <?php elseif ($isOnHold): ?><i class="fa-solid fa-pause me-1.5"></i>
              <?php else: ?><i class="fa-solid fa-hourglass-half me-1.5"></i><?php endif; ?>
              <?= e($app['current_stage']) ?>
            </span>
            <span class="badge bg-light text-dark border px-2.5 py-1.5 fw-semibold">
              Status: <?= e($app['status']) ?>
            </span>
          </div>
          <div class="small text-muted d-flex flex-wrap align-items-center gap-3">
            <span>
              <i class="fa-solid fa-clock text-secondary me-1"></i>
              <strong>Last Updated:</strong> <?= date('d M Y, h:i A', strtotime($lastUpdatedTime)) ?>
            </span>
            <span>
              <i class="fa-solid fa-user-check text-secondary me-1"></i>
              <strong>Updated By:</strong> <?= e($lastUpdatedBy) ?>
            </span>
          </div>
        </div>

        <div class="col-12 col-lg-5">
          <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
            <div>
              <div class="small text-muted mb-0.5">SLA Deadline &amp; Target</div>
              <div class="fw-bold <?= $deadlineClass ?> fs-6">
                <?= e($deadlineStatus) ?>
              </div>
              <div class="small text-muted">Target: <?= !empty($app['expected_completion_date']) ? date('d M Y', strtotime($app['expected_completion_date'])) : 'Standard SLA' ?></div>
            </div>
            <div class="text-end">
              <div class="small text-muted mb-0.5">Case Health</div>
              <span class="badge bg-<?= $healthColor ?> text-white fs-6 px-2.5 py-1">
                <?= (int)($app['calculated_health'] ?? 100) ?>% Health
              </span>
            </div>
          </div>
        </div>
      </div>

      <?php if (!empty($app['next_action'])): ?>
        <div class="mt-3 pt-3 border-top d-flex align-items-start gap-2 small text-dark">
          <i class="fa-solid fa-forward text-primary mt-1"></i>
          <div>
            <strong>Next Action:</strong> <?= e($app['next_action']) ?>
            <?php if (!empty($app['next_action_due_date'])): ?>
              <span class="text-muted ms-2">(Due by <?= date('d M Y', strtotime($app['next_action_due_date'])) ?>)</span>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- 2. EXCEPTIONAL STATUS ALERTS (Returned, Rejected, Cancelled, On Hold) -->
  <?php if ($isReturned): ?>
    <div class="card border-warning bg-warning bg-opacity-10 shadow-sm mb-4">
      <div class="card-header bg-warning bg-opacity-25 text-dark fw-bold py-2.5 d-flex justify-content-between align-items-center">
        <span><i class="fa-solid fa-triangle-exclamation text-warning me-2 fs-5"></i> ACTION REQUIRED: APPLICATION RETURNED / MODIFICATION REQUIRED</span>
        <span class="badge bg-warning text-dark">Returned</span>
      </div>
      <div class="card-body p-3">
        <div class="row g-3 small">
          <div class="col-md-4">
            <div class="text-muted">Return Reason:</div>
            <div class="fw-bold text-dark"><?= e($app['return_reason'] ?? ($applicationReturns[0]['return_reason'] ?? 'Document modification required by visa processing authority.')) ?></div>
          </div>
          <div class="col-md-4">
            <div class="text-muted">Required Modifications:</div>
            <div class="fw-semibold text-dark"><?= e($applicationReturns[0]['required_changes'] ?? 'Please review the requested documents and provide updated copies.') ?></div>
          </div>
          <div class="col-md-4">
            <div class="text-muted">Return Deadline:</div>
            <div class="fw-bold text-danger"><?= !empty($app['return_deadline']) ? date('d M Y', strtotime($app['return_deadline'])) : (!empty($applicationReturns[0]['deadline']) ? date('d M Y', strtotime($applicationReturns[0]['deadline'])) : 'Immediate submission required') ?></div>
          </div>
        </div>
      </div>
    </div>
  <?php elseif ($isRejected): ?>
    <div class="card border-danger bg-danger bg-opacity-10 shadow-sm mb-4">
      <div class="card-header bg-danger bg-opacity-25 text-danger fw-bold py-2.5 d-flex justify-content-between align-items-center">
        <span><i class="fa-solid fa-circle-xmark me-2 fs-5"></i> VISA APPLICATION REJECTED</span>
        <span class="badge bg-danger text-white">Refused / Closed</span>
      </div>
      <div class="card-body p-3">
        <div class="row g-3 small">
          <div class="col-md-6">
            <div class="text-muted">Rejection Notice / Customer Reason:</div>
            <div class="fw-bold text-danger"><?= e($app['rejection_reason_customer'] ?? ($app['rejection_reason'] ?? ($visaRejection['customer_reason'] ?? 'Visa refused by embassy/immigration authority.'))) ?></div>
          </div>
          <div class="col-md-6">
            <div class="text-muted">Reapplication Eligibility:</div>
            <div class="fw-semibold text-dark"><?= e($visaRejection['reapplication_eligibility'] ?? 'Subject to immigration authority cool-off guidelines.') ?></div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- 3. OFFICIAL APPROVED VISA GRANT NOTICE (If Approved) -->
  <?php if ($isApproved || !empty($app['visa_number']) || !empty($visaApproval)): ?>
    <div class="card border-success shadow-sm mb-4">
      <div class="card-header bg-success text-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid fa-award fs-4"></i>
          <div>
            <h5 class="fw-bold mb-0">OFFICIAL VISA APPROVED &amp; ISSUED</h5>
            <div class="small text-white-50">Visa granted and verified successfully</div>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-white text-success fs-6 font-monospace py-1.5 px-3">
            <i class="fa-solid fa-stamp me-1"></i> Visa # <?= e($app['visa_number'] ?: ($visaApproval['visa_number'] ?? 'ISSUED')) ?>
          </span>
        </div>
      </div>
      <div class="card-body p-4">
        <div class="row g-3 small mb-3">
          <div class="col-6 col-md-3">
            <div class="text-muted">Visa Number</div>
            <div class="fw-bold fs-6 font-monospace text-dark"><?= e($app['visa_number'] ?: ($visaApproval['visa_number'] ?? '—')) ?></div>
          </div>
          <div class="col-6 col-md-3">
            <div class="text-muted">Issue Date</div>
            <div class="fw-semibold text-dark"><?= !empty($visaApproval['issue_date']) ? date('d M Y', strtotime($visaApproval['issue_date'])) : (!empty($app['approval_date']) ? date('d M Y', strtotime($app['approval_date'])) : date('d M Y')) ?></div>
          </div>
          <div class="col-6 col-md-3">
            <div class="text-muted">Expiry Date</div>
            <div class="fw-bold text-danger"><?= !empty($visaApproval['expiry_date']) ? date('d M Y', strtotime($visaApproval['expiry_date'])) : (!empty($app['visa_expiry_date']) ? date('d M Y', strtotime($app['visa_expiry_date'])) : '—') ?></div>
          </div>
          <div class="col-6 col-md-3">
            <div class="text-muted">Entry Before Date</div>
            <div class="fw-semibold text-dark"><?= !empty($visaApproval['entry_before_date']) ? date('d M Y', strtotime($visaApproval['entry_before_date'])) : '—' ?></div>
          </div>
          <div class="col-6 col-md-3">
            <div class="text-muted">Maximum Stay</div>
            <div class="fw-semibold text-dark"><?= e($visaApproval['maximum_stay'] ?? ($visaApproval['max_stay'] ?? ($app['visa_duration'] ?? '30 Days'))) ?></div>
          </div>
          <div class="col-6 col-md-3">
            <div class="text-muted">Visa Validity</div>
            <div class="fw-semibold text-dark"><?= e($visaApproval['validity'] ?? ($app['service_validity'] ?? '60 Days')) ?></div>
          </div>
          <div class="col-6 col-md-3">
            <div class="text-muted">Entry Type</div>
            <div class="fw-semibold text-dark"><?= e($app['entry_type'] ?? ($app['service_entry_type'] ?? 'Single Entry')) ?></div>
          </div>
          <div class="col-6 col-md-3">
            <div class="text-muted">Approved By</div>
            <div class="fw-semibold text-dark"><?= e($visaApproval['approved_by_name'] ?? ($app['staff_name'] ?? 'Visa Officer')) ?></div>
          </div>
        </div>

        <!-- Visa Document Actions -->
        <?php 
          $visaFilePath = $visaApproval['approved_visa_file'] ?? ($visaApproval['visa_file'] ?? ($app['approved_visa_file'] ?? ($app['visa_file'] ?? null)));
        ?>
        <div class="p-3 bg-light rounded border d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-file-pdf text-danger fs-3"></i>
            <div>
              <div class="fw-bold text-dark">Official Visa Grant Notice Document</div>
              <div class="small text-muted">Ready for download, printing, and international border travel.</div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <?php if (!empty($visaFilePath)): ?>
              <a href="/storage/uploads/<?= e($visaFilePath) ?>" target="_blank" class="btn btn-outline-primary btn-sm fw-semibold">
                <i class="fa-solid fa-eye me-1"></i> View Visa
              </a>
              <a href="/storage/uploads/<?= e($visaFilePath) ?>" download class="btn btn-success btn-sm fw-semibold">
                <i class="fa-solid fa-download me-1"></i> Download Visa
              </a>
              <button type="button" onclick="window.open('/storage/uploads/<?= e($visaFilePath) ?>', '_blank').print();" class="btn btn-secondary btn-sm fw-semibold">
                <i class="fa-solid fa-print me-1"></i> Print Visa
              </button>
            <?php else: ?>
              <span class="badge bg-secondary">Digital Visa Verified</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- 4. VISUAL LIFECYCLE JOURNEY TIMELINE -->
  <div class="card card-enterprise border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
      <div>
        <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-route text-primary me-2"></i> Visa Journey &amp; Lifecycle Progression</h5>
        <div class="small text-muted">End-to-end lifecycle milestones from registration to visa grant</div>
      </div>
      <div class="small text-muted fw-bold">
        Overall Progress: <span class="text-primary"><?= $progressPercentage ?>%</span>
      </div>
    </div>
    <div class="card-body p-4">
      <!-- Progress Bar -->
      <div class="progress mb-4" style="height: 8px;">
        <div class="progress-bar bg-<?= $statusColor ?>" role="progressbar" style="width: <?= $progressPercentage ?>%;" aria-valuenow="<?= $progressPercentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
      </div>

      <!-- Timeline Steps (Horizontal on Desktop, Stacked on Mobile) -->
      <div class="row g-3">
        <?php foreach ($journeyStages as $stg): 
          $isCompleted = ($stg['state'] === 'COMPLETED');
          $isCurrent = ($stg['is_current'] && !$isApproved);
          $iconClass = 'fa-circle-notch fa-spin text-primary';
          $cardBg = 'bg-white';
          $borderClass = 'border';

          if ($isCompleted) {
            $iconClass = 'fa-circle-check text-success';
            $borderClass = 'border-success border-opacity-50';
          } elseif ($isCurrent) {
            if ($isReturned) {
              $iconClass = 'fa-triangle-exclamation text-warning';
              $borderClass = 'border-warning';
              $cardBg = 'bg-warning bg-opacity-10';
            } elseif ($isRejected) {
              $iconClass = 'fa-circle-xmark text-danger';
              $borderClass = 'border-danger';
              $cardBg = 'bg-danger bg-opacity-10';
            } else {
              $iconClass = 'fa-spinner fa-spin text-primary';
              $borderClass = 'border-primary shadow-sm';
              $cardBg = 'bg-primary bg-opacity-10';
            }
          } else {
            $iconClass = 'fa-circle text-muted text-opacity-25';
          }
        ?>
          <div class="col-12 col-md-6 col-lg-4 col-xl-3">
            <div class="p-3 rounded-3 <?= $cardBg ?> <?= $borderClass ?> h-100 position-relative transition-hover">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge bg-light text-secondary border font-monospace small">#<?= $stg['index'] ?></span>
                <i class="fa-solid <?= $iconClass ?> fs-6"></i>
              </div>
              <div class="fw-bold small text-dark mb-1 <?= $isCurrent ? 'text-primary' : '' ?>">
                <?= e($stg['name']) ?>
              </div>
              <?php if (!empty($stg['history'])): ?>
                <div class="small text-muted" style="font-size: 0.75rem;">
                  <div><i class="fa-solid fa-calendar-check me-1"></i><?= date('d M Y, h:i A', strtotime($stg['history']['created_at'])) ?></div>
                  <?php if (!empty($stg['history']['changed_by_name'])): ?>
                    <div><i class="fa-solid fa-user me-1"></i><?= e($stg['history']['changed_by_name']) ?></div>
                  <?php endif; ?>
                </div>
              <?php elseif ($isCurrent): ?>
                <div class="small text-primary fw-semibold" style="font-size: 0.75rem;">
                  <i class="fa-solid fa-location-dot me-1"></i> Active Milestone
                </div>
              <?php else: ?>
                <div class="small text-muted" style="font-size: 0.75rem;">Pending Next Step</div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <!-- 5. COMPLETE APPLICATION INFORMATION -->
    <div class="col-12 col-lg-8">
      <div class="card card-enterprise border-0 shadow-sm h-100">
        <div class="card-header bg-white py-3 border-bottom">
          <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-id-card text-primary me-2"></i> Application &amp; Applicant Master Information</h5>
        </div>
        <div class="card-body p-4">
          <div class="row g-3 small">
            <!-- Row 1: Core Identifiers -->
            <div class="col-sm-6 col-md-4">
              <div class="text-muted">Application Reference</div>
              <div class="fw-bold font-monospace text-primary fs-6"><?= e($app['application_number']) ?></div>
            </div>
            <div class="col-sm-6 col-md-4">
              <div class="text-muted">Application Date</div>
              <div class="fw-semibold text-dark"><?= !empty($app['application_date']) ? date('d M Y', strtotime($app['application_date'])) : date('d M Y', strtotime($app['created_at'])) ?></div>
            </div>
            <div class="col-sm-6 col-md-4">
              <div class="text-muted">Customer Code / ID</div>
              <div class="fw-bold font-monospace text-dark"><?= e($app['customer_code']) ?></div>
            </div>

            <!-- Row 2: Customer Identity & Passport -->
            <div class="col-sm-6 col-md-4">
              <div class="text-muted">Customer / Applicant Name</div>
              <div class="fw-bold text-dark"><?= e($app['customer_name']) ?></div>
            </div>
            <div class="col-sm-6 col-md-4">
              <div class="text-muted">Passport Number</div>
              <div class="badge bg-light text-dark border font-monospace fs-6 px-2 py-1"><?= e($app['passport_number'] ?: ($app['current_passport'] ?? '—')) ?></div>
            </div>
            <div class="col-sm-6 col-md-4">
              <div class="text-muted">Nationality</div>
              <div class="fw-semibold text-dark"><?= e($app['nationality'] ?? ($app['customer_nationality'] ?? '—')) ?></div>
            </div>

            <!-- Row 3: Contacts -->
            <div class="col-sm-6 col-md-4">
              <div class="text-muted">Mobile Number</div>
              <div class="fw-semibold text-dark"><i class="fa-solid fa-phone text-muted me-1"></i><?= e($app['customer_mobile'] ?: '—') ?></div>
            </div>
            <div class="col-sm-6 col-md-4">
              <div class="text-muted">WhatsApp Number</div>
              <div class="fw-semibold text-dark"><i class="fa-brands fa-whatsapp text-success me-1"></i><?= e($app['customer_whatsapp'] ?: ($app['customer_mobile'] ?: '—')) ?></div>
            </div>
            <div class="col-sm-6 col-md-4">
              <div class="text-muted">Email Address</div>
              <div class="fw-semibold text-dark text-truncate" title="<?= e($app['customer_email']) ?>"><i class="fa-solid fa-envelope text-muted me-1"></i><?= e($app['customer_email'] ?: '—') ?></div>
            </div>

            <!-- Row 4: Visa Specifics -->
            <div class="col-sm-6 col-md-4">
              <div class="text-muted">Destination Country</div>
              <div class="fw-bold text-dark"><?= $app['flag_emoji'] ?> <?= e($app['country_name']) ?></div>
            </div>
            <div class="col-sm-6 col-md-4">
              <div class="text-muted">Visa Category</div>
              <div class="fw-semibold text-dark"><?= e($app['visa_category'] ?? ($app['category_name'] ?? 'Standard')) ?></div>
            </div>
            <div class="col-sm-6 col-md-4">
              <div class="text-muted">Visa Type / Service</div>
              <div class="fw-semibold text-dark"><?= e($app['visa_type'] ?? ($app['service_name'] ?? '—')) ?></div>
            </div>

            <!-- Row 5: Durations & Processing -->
            <div class="col-sm-6 col-md-4">
              <div class="text-muted">Duration</div>
              <div class="fw-semibold text-dark"><?= e($app['visa_duration'] ?? ($app['service_duration'] ?? '30 Days')) ?></div>
            </div>
            <div class="col-sm-6 col-md-4">
              <div class="text-muted">Entry Type</div>
              <div class="fw-semibold text-dark"><?= e($app['entry_type'] ?? ($app['service_entry_type'] ?? 'Single Entry')) ?></div>
            </div>
            <div class="col-sm-6 col-md-4">
              <div class="text-muted">Processing Type</div>
              <div class="fw-semibold text-dark"><?= e($app['processing_type'] ?? ($app['service_processing_type'] ?? 'Normal')) ?></div>
            </div>

            <!-- Row 6: Team & Operations -->
            <div class="col-sm-6 col-md-4">
              <div class="text-muted">Assigned Staff</div>
              <div class="fw-semibold text-dark"><i class="fa-solid fa-user-tie text-secondary me-1"></i><?= e($app['staff_name'] ?? 'Unassigned') ?></div>
            </div>
            <div class="col-sm-6 col-md-4">
              <div class="text-muted">Branch</div>
              <div class="fw-semibold text-dark"><i class="fa-solid fa-building text-secondary me-1"></i><?= e($app['branch_name'] ?? 'Main Branch') ?></div>
            </div>
            <div class="col-sm-6 col-md-4">
              <div class="text-muted">Supplier / Embassy Ref</div>
              <div class="fw-semibold text-dark">
                <?= e($app['supplier_name'] ?? 'In-House') ?>
                <?php if (!empty($app['supplier_reference'])): ?>
                  <span class="text-muted font-monospace">(<?= e($app['supplier_reference']) ?>)</span>
                <?php endif; ?>
              </div>
            </div>

            <!-- Row 7: Travel Dates if available -->
            <?php if (!empty($app['travel_date']) || !empty($app['return_date'])): ?>
              <div class="col-sm-6 col-md-6">
                <div class="text-muted">Intended Travel Date</div>
                <div class="fw-semibold text-dark"><?= !empty($app['travel_date']) ? date('d M Y', strtotime($app['travel_date'])) : '—' ?></div>
              </div>
              <div class="col-sm-6 col-md-6">
                <div class="text-muted">Intended Return Date</div>
                <div class="fw-semibold text-dark"><?= !empty($app['return_date']) ? date('d M Y', strtotime($app['return_date'])) : '—' ?></div>
              </div>
            <?php endif; ?>

            <!-- Confidential Financial Data (Gated by RBAC) -->
            <?php if ($canViewFinancials): ?>
              <div class="col-12 mt-2 pt-2 border-top">
                <div class="d-flex flex-wrap gap-4 small text-muted">
                  <span><strong>Supplier Cost:</strong> <?= format_currency((float)($app['supplier_cost'] ?? 0)) ?></span>
                  <span><strong>Selling Price:</strong> <?= format_currency((float)($app['selling_price'] ?? 0)) ?></span>
                  <span><strong>Gross Profit:</strong> <?= format_currency((float)($app['gross_profit'] ?? 0)) ?></span>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- 6. PAYMENT STATUS & FINANCIAL SUMMARY -->
    <div class="col-12 col-lg-4">
      <div class="card card-enterprise border-0 shadow-sm h-100">
        <div class="card-header bg-white py-3 border-bottom">
          <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-receipt text-primary me-2"></i> Payment &amp; Invoicing</h5>
        </div>
        <div class="card-body p-4">
          <?php
            $totalAmt = (float)($app['total_amount'] ?? 0.0);
            $paidAmt = (float)($app['paid_amount'] ?? 0.0);
            $balanceAmt = (float)($app['balance_amount'] ?? max(0.0, $totalAmt - $paidAmt));
            $isPaid = ($balanceAmt <= 0.001 && $totalAmt > 0);
            $isPartial = ($paidAmt > 0 && $balanceAmt > 0.001);
            $isUnpaid = ($paidAmt <= 0.001 && $totalAmt > 0);

            $payBadge = 'bg-success';
            $payLabel = 'PAID IN FULL';
            if ($isPartial) {
              $payBadge = 'bg-warning text-dark';
              $payLabel = 'PARTIALLY PAID';
            } elseif ($isUnpaid) {
              $payBadge = 'bg-danger text-white';
              $payLabel = 'UNPAID';
            }
          ?>

          <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="small text-muted text-uppercase fw-semibold">Payment Status</span>
            <span class="badge <?= $payBadge ?> px-2.5 py-1.5 fw-bold"><?= $payLabel ?></span>
          </div>

          <div class="p-3 bg-light rounded-3 border mb-3">
            <div class="d-flex justify-content-between mb-1.5 small">
              <span class="text-muted">Total Fee:</span>
              <strong class="text-dark"><?= format_currency($totalAmt) ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-1.5 small">
              <span class="text-muted">Amount Paid:</span>
              <strong class="text-success"><?= format_currency($paidAmt) ?></strong>
            </div>
            <div class="d-flex justify-content-between pt-1.5 border-top small">
              <span class="fw-bold text-dark">Outstanding Balance:</span>
              <strong class="<?= $balanceAmt > 0 ? 'text-danger' : 'text-success' ?> fs-6"><?= format_currency($balanceAmt) ?></strong>
            </div>
          </div>

          <?php if ($balanceAmt > 0): ?>
            <div class="alert alert-warning border-0 p-2.5 small mb-3">
              <i class="fa-solid fa-circle-exclamation me-1"></i>
              <strong>Outstanding:</strong> <?= format_currency($balanceAmt) ?> remaining.
            </div>
          <?php endif; ?>

          <!-- Payment Records Table -->
          <?php if (!empty($appPayments)): ?>
            <div class="small fw-semibold text-muted mb-2">Payment Transactions</div>
            <div class="list-group list-group-flush border-top border-bottom small">
              <?php foreach ($appPayments as $p): ?>
                <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                  <div>
                    <div class="fw-semibold text-dark"><?= e($p['payment_method']) ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;"><?= date('d M Y', strtotime($p['payment_date'])) ?></div>
                  </div>
                  <strong class="text-success"><?= format_currency((float)$p['amount']) ?></strong>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- 7. REQUIRED DOCUMENTS TRACKING PROGRESS -->
  <div class="card card-enterprise border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
      <div>
        <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-folder-open text-primary me-2"></i> Required Documents &amp; Verification Progress</h5>
        <div class="small text-muted">Complete document verification status for this visa file</div>
      </div>
      <?php if ($isStaff): ?>
        <a href="/documents" class="btn btn-outline-primary btn-sm">
          <i class="fa-solid fa-file-arrow-up me-1"></i> Document Center
        </a>
      <?php endif; ?>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-custom table-hover align-middle mb-0">
          <thead class="table-light">
            <tr class="small text-muted text-uppercase">
              <th>Document Name / Type</th>
              <th>Requirement</th>
              <th>Status</th>
              <th>File Name &amp; Version</th>
              <th>Uploaded / Verified</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($documentChecklist) && empty($uploadedDocuments)): ?>
              <tr>
                <td colspan="6" class="text-center py-4 text-muted small">No specific document requirements recorded for this application.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($documentChecklist as $doc): 
                $docStatus = $doc['status'] ?? 'PENDING';
                $badgeClass = 'bg-secondary';
                $icon = 'fa-circle-question';

                if ($docStatus === 'APPROVED' || $docStatus === 'VERIFIED') {
                  $badgeClass = 'bg-success';
                  $icon = 'fa-circle-check';
                } elseif ($docStatus === 'UNDER_REVIEW' || $docStatus === 'UPLOADED') {
                  $badgeClass = 'bg-primary';
                  $icon = 'fa-clock';
                } elseif ($docStatus === 'REJECTED') {
                  $badgeClass = 'bg-danger';
                  $icon = 'fa-circle-xmark';
                } elseif ($docStatus === 'REPLACEMENT_REQUESTED') {
                  $badgeClass = 'bg-warning text-dark';
                  $icon = 'fa-rotate';
                } else {
                  $badgeClass = 'bg-secondary text-white';
                  $icon = 'fa-circle';
                }
              ?>
                <tr>
                  <td>
                    <div class="fw-semibold text-dark"><?= e($doc['document_name']) ?></div>
                    <?php if (!empty($doc['instructions'])): ?>
                      <div class="small text-muted" style="font-size: 0.75rem;"><?= e($doc['instructions']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($doc['is_mandatory'])): ?>
                      <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 small">Mandatory</span>
                    <?php else: ?>
                      <span class="badge bg-light text-secondary border small">Optional</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge <?= $badgeClass ?> px-2 py-1 text-nowrap">
                      <i class="fa-solid <?= $icon ?> me-1"></i> <?= e($docStatus) ?>
                    </span>
                  </td>
                  <td>
                    <?php if (!empty($doc['file_name'])): ?>
                      <div class="small font-monospace text-dark text-truncate" style="max-width: 180px;" title="<?= e($doc['file_name']) ?>"><?= e($doc['file_name']) ?></div>
                      <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">v<?= (int)($doc['version'] ?? 1) ?></span>
                    <?php else: ?>
                      <span class="text-muted small">Awaiting Upload</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($doc['uploaded_at'])): ?>
                      <div class="small text-muted"><?= date('d M Y, h:i A', strtotime($doc['uploaded_at'])) ?></div>
                    <?php else: ?>
                      <span class="text-muted small">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <?php if (!empty($doc['document_id']) && !empty($doc['file_name'])): ?>
                      <a href="/documents/preview?id=<?= (int)$doc['document_id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Preview Document">
                        <i class="fa-solid fa-eye"></i>
                      </a>
                      <a href="/documents/download?id=<?= (int)$doc['document_id'] ?>" class="btn btn-sm btn-outline-secondary" title="Download Document">
                        <i class="fa-solid fa-download"></i>
                      </a>
                    <?php else: ?>
                      <span class="text-muted small">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- 8. IMMUTABLE STATUS & AUDIT LOG HISTORY -->
  <div class="card card-enterprise border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
      <div>
        <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-timeline text-primary me-2"></i> Official Status History Log</h5>
        <div class="small text-muted">Complete audit trail of all lifecycle changes and decision timestamps</div>
      </div>
      <span class="badge bg-light text-dark border"><?= count($stageHistory) ?> Status Updates</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-custom table-hover align-middle mb-0">
          <thead class="table-light">
            <tr class="small text-muted text-uppercase">
              <th style="width: 170px;">Date &amp; Time</th>
              <th style="width: 200px;">Stage Progression</th>
              <th style="width: 140px;">Status</th>
              <th style="width: 180px;">Changed By</th>
              <th>Comments / Reason</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($stageHistory)): ?>
              <tr>
                <td colspan="5" class="text-center py-4 text-muted small">No prior status transitions recorded yet.</td>
              </tr>
            <?php else: ?>
              <?php foreach (array_reverse($stageHistory) as $hist): ?>
                <tr>
                  <td class="text-nowrap small">
                    <div class="fw-bold text-dark"><?= date('d M Y', strtotime($hist['created_at'])) ?></div>
                    <div class="text-muted"><?= date('h:i A', strtotime($hist['created_at'])) ?></div>
                  </td>
                  <td>
                    <?php if (!empty($hist['from_stage'])): ?>
                      <span class="text-muted small"><?= e($hist['from_stage']) ?></span>
                      <i class="fa-solid fa-arrow-right text-secondary mx-1 small"></i>
                    <?php endif; ?>
                    <strong class="text-dark small"><?= e($hist['to_stage']) ?></strong>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border small">
                      <?= e($hist['to_status']) ?>
                    </span>
                  </td>
                  <td class="small">
                    <div class="fw-semibold text-dark"><i class="fa-solid fa-user-tie text-muted me-1"></i><?= e($hist['changed_by_name'] ?? 'System') ?></div>
                    <?php if (!empty($hist['changed_by_role'])): ?>
                      <div class="text-muted" style="font-size: 0.75rem;"><?= e($hist['changed_by_role']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="small text-muted">
                    <?= e($hist['comments'] ?: 'Status updated successfully.') ?>
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

<?php 
if ($isStaff) {
    require_once dirname(__DIR__) . '/layouts/footer.php';
} else {
    require_once dirname(__DIR__) . '/portal/footer.php';
}
?>
