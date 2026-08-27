<?php
$pageTitle = e($app['application_number']) . ' — Visa Tracking & Management';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';

$isReturned = ($app['status'] === 'Action Required' || str_contains(strtolower($app['current_stage']), 'returned'));
$isApproved = ($app['status'] === 'Approved');
$isRejected = ($app['status'] === 'Rejected');

$healthClass = 'health-healthy';
if ((int)$app['calculated_health'] < 50) $healthClass = 'health-critical';
elseif ((int)$app['calculated_health'] < 80) $healthClass = 'health-at-risk';
?>

<div class="content-body">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- 1. Top Master Breadcrumb & Action Header -->
  <div class="card card-enterprise mb-4 bg-white">
    <div class="card-body p-3 p-md-4">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
          <a href="/applications" class="btn btn-outline-secondary btn-sm" title="Back to Applications Directory"><i class="fa-solid fa-arrow-left"></i></a>
          <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
              <h4 class="fw-bold brand-font mb-0 text-primary" style="letter-spacing: -0.02em;"><?= e($app['application_number']) ?></h4>
              <button class="btn btn-light btn-sm p-1 px-2 border text-muted" onclick="navigator.clipboard.writeText('<?= e($app['application_number']) ?>'); showToast('Application ID copied!', 'info');" title="Copy ID">
                <i class="fa-regular fa-copy"></i>
              </button>
              <span class="fs-5"><?= $app['flag_emoji'] ?></span>
              <span class="badge-status badge-status-<?= strtolower(str_replace(' ', '-', $app['status'])) ?>">
                <?= e($app['status']) ?>
              </span>
              <span class="badge badge-priority-<?= strtolower($app['priority']) ?>">
                <?= e($app['priority']) ?> PRIORITY
              </span>
              <button type="button" class="btn btn-light btn-sm health-meter <?= $healthClass ?> py-1 px-2" data-bs-toggle="modal" data-bs-target="#healthModal" title="Click to view health diagnosis">
                <i class="fa-solid fa-heart-pulse me-1"></i> Health: <?= (int)$app['calculated_health'] ?>%
              </button>
            </div>
            <div class="text-muted small">
              <a href="/customers/show?id=<?= $app['customer_id'] ?>" class="fw-bold text-dark text-decoration-none hover-underline">
                <i class="fa-solid fa-user me-1 text-secondary"></i><?= e($app['customer_name']) ?>
              </a>
              (<?= e($app['customer_code']) ?>) &bull; 
              <span class="fw-medium text-dark"><?= e($app['service_name']) ?></span> &bull; 
              <span>Passport: <strong><?= e($app['passport_number'] ?: $app['current_passport'] ?: '—') ?></strong></span>
            </div>
          </div>
        </div>

        <!-- Master Operational Action Buttons -->
        <div class="d-flex flex-wrap align-items-center gap-2">
          <!-- Update Stage Button -->
          <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#stageTransitionModal">
            <i class="fa-solid fa-forward-step me-1"></i> Update Stage
          </button>

          <!-- Approve Visa Button -->
          <button type="button" class="btn btn-success btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#approveVisaModal">
            <i class="fa-solid fa-circle-check me-1"></i> Approve Visa
          </button>

          <!-- Reject / Return Decision Dropdown -->
          <div class="dropdown">
            <button class="btn btn-outline-dark btn-sm px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fa-solid fa-gavel me-1"></i> Decisions
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="font-size: 0.85rem;">
              <li><button type="button" class="dropdown-item py-2 text-success fw-semibold" data-bs-toggle="modal" data-bs-target="#approveVisaModal"><i class="fa-solid fa-circle-check me-2"></i> Approve (Grant Visa)</button></li>
              <li><button type="button" class="dropdown-item py-2 text-warning fw-semibold" data-bs-toggle="modal" data-bs-target="#returnVisaModal"><i class="fa-solid fa-rotate-left me-2"></i> Return for Modifications</button></li>
              <li><button type="button" class="dropdown-item py-2 text-danger fw-semibold" data-bs-toggle="modal" data-bs-target="#rejectVisaModal"><i class="fa-solid fa-circle-xmark me-2"></i> Reject Application</button></li>
              <li><hr class="dropdown-divider my-1"></li>
              <li><button type="button" class="dropdown-item py-2" data-bs-toggle="modal" data-bs-target="#requestDocModal"><i class="fa-solid fa-file-circle-question text-primary me-2"></i> Request Additional Document</button></li>
              <li><button type="button" class="dropdown-item py-2" data-bs-toggle="modal" data-bs-target="#addCommModal"><i class="fa-solid fa-phone text-info me-2"></i> Log Client Communication</button></li>
            </ul>
          </div>

          <!-- Quick Actions Dropdown -->
          <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm px-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fa-solid fa-ellipsis-vertical"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="font-size: 0.85rem;">
              <li><button type="button" class="dropdown-item py-2" data-bs-toggle="modal" data-bs-target="#reassignStaffModal"><i class="fa-solid fa-user-gear text-primary me-2"></i> Reassign Staff</button></li>
              <li><button type="button" class="dropdown-item py-2" data-bs-toggle="modal" data-bs-target="#priorityModal"><i class="fa-solid fa-flag text-warning me-2"></i> Change Priority</button></li>
              <li><button type="button" class="dropdown-item py-2" data-bs-toggle="modal" data-bs-target="#addNoteModal"><i class="fa-solid fa-note-sticky text-info me-2"></i> Add Internal Note</button></li>
              <li><hr class="dropdown-divider my-1"></li>
              <li><a class="dropdown-item py-2" href="/payments/invoice?app_id=<?= $app['id'] ?>" target="_blank"><i class="fa-solid fa-file-invoice text-success me-2"></i> Print Invoice</a></li>
              <li>
                <form action="/applications/archive" method="POST" onsubmit="return confirm('Archive this visa application record?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                  <button type="submit" class="dropdown-item py-2 text-danger"><i class="fa-solid fa-box-archive text-danger me-2"></i> Archive Application</button>
                </form>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. PRIMARY OPERATIONAL BANNERS: Current Stage & Next Action -->
  <div class="row g-3 mb-4">
    <!-- Current Stage Banner -->
    <div class="col-lg-6">
      <div class="card card-enterprise h-100 border-start border-4 border-primary p-3 bg-white">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <span class="small fw-bold text-uppercase text-primary" style="font-size: 0.72rem; letter-spacing: 0.05em;">
            <i class="fa-solid fa-circle-dot text-primary me-1"></i> Current Lifecycle Stage
          </span>
          <span class="badge bg-primary-subtle text-primary fw-semibold" style="font-size: 0.72rem;">
            <?= e($app['status']) ?>
          </span>
        </div>
        <h5 class="fw-bold mb-1" style="color: #0f172a;"><?= e($app['current_stage']) ?></h5>
        <div class="text-muted small mb-2">
          <span>Assigned Officer: <strong><?= e($app['staff_name'] ?? 'Unassigned') ?></strong></span> &bull; 
          <span>Updated: <?= format_datetime($app['updated_at'] ?? $app['created_at']) ?></span>
        </div>
        <div class="small text-secondary bg-light p-2 rounded">
          <i class="fa-solid fa-clock me-1 text-muted"></i> 
          <strong>Operational Deadline:</strong> <span class="<?= e($deadlineClass) ?>"><?= e($deadlineStatus) ?></span> 
          (<?= format_date($app['expected_completion_date']) ?>)
        </div>
      </div>
    </div>

    <!-- Next Action Banner -->
    <div class="col-lg-6">
      <div class="card card-enterprise h-100 border-start border-4 border-warning p-3 bg-white">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <span class="small fw-bold text-uppercase text-warning" style="font-size: 0.72rem; letter-spacing: 0.05em;">
            <i class="fa-solid fa-bolt text-warning me-1"></i> Immediate Next Action
          </span>
          <span class="badge bg-warning-subtle text-dark fw-semibold" style="font-size: 0.72rem;">
            Due: <?= format_date($app['next_action_due_date'] ?? date('Y-m-d', strtotime('+3 days'))) ?>
          </span>
        </div>
        <h5 class="fw-bold mb-1 text-dark"><?= e($app['next_action'] ?? 'Review checklist documents and prepare for stage progression') ?></h5>
        <div class="text-muted small mb-2">
          <span>Responsible: <strong><?= e($app['staff_name'] ?? 'Operations Team') ?></strong></span> &bull; 
          <span>Priority: <span class="fw-semibold text-danger"><?= e($app['priority']) ?></span></span>
        </div>
        <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded">
          <span class="small text-muted"><i class="fa-solid fa-info-circle me-1"></i> Fulfill pending checklist items to advance</span>
          <button type="button" class="btn btn-sm btn-warning text-dark py-1 px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#stageTransitionModal">
            <i class="fa-solid fa-circle-check me-1"></i> Advance Stage &rarr;
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- 3. ⭐ VISA JOURNEY TIMELINE (CENTERPIECE) -->
  <div class="card card-enterprise mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div>
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-route text-primary me-2"></i> Visa Tracking Journey Timeline</h6>
        <div class="text-muted small" style="font-size: 0.75rem;">Click any stage node to inspect operational history, assigned case worker, and specific notes.</div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="small fw-semibold text-muted"><?= $completedCount ?> of <?= $totalStages ?> Stages (<?= $progressPercentage ?>%)</span>
        <div class="progress" style="width: 120px; height: 8px;">
          <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $progressPercentage ?>%;" aria-valuenow="<?= $progressPercentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
      </div>
    </div>

    <div class="card-body p-4">
      <!-- Horizontal Timeline for Desktop / Vertical for Mobile -->
      <div class="visa-journey-timeline">
        <?php foreach ($journeyStages as $js): ?>
          <?php
            $nodeClass = 'stage-pending';
            $nodeIcon = $js['index'];
            if ($js['state'] === 'COMPLETED') {
                $nodeClass = 'stage-completed';
                $nodeIcon = '<i class="fa-solid fa-check"></i>';
            } elseif ($js['state'] === 'CURRENT') {
                $nodeClass = 'stage-current';
                $nodeIcon = '<i class="fa-solid fa-circle-dot"></i>';
            } elseif ($js['state'] === 'BLOCKED') {
                $nodeClass = 'stage-blocked';
                $nodeIcon = '<i class="fa-solid fa-triangle-exclamation"></i>';
            }
          ?>
          <div class="timeline-step <?= $nodeClass ?>" onclick="showStageDetails('<?= e(addslashes($js['name'])) ?>', '<?= e($js['state']) ?>', '<?= e($js['history']['changed_by_name'] ?? ($js['is_current'] ? ($app['staff_name'] ?? 'Assigned Staff') : '—')) ?>', '<?= format_datetime($js['history']['created_at'] ?? null) ?>', '<?= e(addslashes($js['history']['comments'] ?? 'Stage pending progression')) ?>')">
            <div class="timeline-node">
              <?= $nodeIcon ?>
            </div>
            <div class="timeline-content">
              <div class="timeline-stage-name"><?= e($js['name']) ?></div>
              <div class="timeline-stage-state"><?= e($js['state']) ?></div>
              <?php if (!empty($js['history']['created_at'])): ?>
                <div class="timeline-stage-date"><?= format_date($js['history']['created_at']) ?></div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- 4. TABBED OPERATIONAL SECTIONS (Section 22: 12 Tabs) -->
  <div class="card card-enterprise">
    <div class="card-header bg-white border-bottom p-0">
      <ul class="nav nav-tabs card-header-tabs m-0 px-3 overflow-x-auto flex-nowrap" id="appDetailsTabs" role="tablist">
        <li class="nav-item">
          <button class="nav-link active py-3 fw-semibold text-nowrap" id="overview-tab" data-bs-toggle="tab" data-bs-target="#info-pane" type="button" role="tab">
            <i class="fa-solid fa-gauge-high me-1 text-primary"></i> Overview
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link py-3 fw-semibold text-nowrap" id="customer-tab" data-bs-toggle="tab" data-bs-target="#customer-pane" type="button" role="tab">
            <i class="fa-solid fa-user me-1 text-primary"></i> Customer
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link py-3 fw-semibold text-nowrap" id="visa-tab" data-bs-toggle="tab" data-bs-target="#visa-pane" type="button" role="tab">
            <i class="fa-solid fa-passport me-1 text-info"></i> Visa Details
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link py-3 fw-semibold text-nowrap" id="docs-tab" data-bs-toggle="tab" data-bs-target="#docs-pane" type="button" role="tab">
            <i class="fa-solid fa-file-circle-check me-1 text-success"></i> Documents (<?= count($documentChecklist) ?>)
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link py-3 fw-semibold text-nowrap" id="doc-req-tab" data-bs-toggle="tab" data-bs-target="#doc-req-pane" type="button" role="tab">
            <i class="fa-solid fa-file-circle-question me-1 text-warning"></i> Document Requests (<?= count($documentRequests) ?>)
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link py-3 fw-semibold text-nowrap" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments-pane" type="button" role="tab">
            <i class="fa-solid fa-credit-card me-1 text-success"></i> Payments (<?= count($appPayments) ?>)
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link py-3 fw-semibold text-nowrap" id="supplier-tab" data-bs-toggle="tab" data-bs-target="#supplier-pane" type="button" role="tab">
            <i class="fa-solid fa-building-flag me-1 text-secondary"></i> Supplier
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link py-3 fw-semibold text-nowrap" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks-pane" type="button" role="tab">
            <i class="fa-solid fa-list-check me-1 text-warning"></i> Tasks (<?= count($tasks) ?>)
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link py-3 fw-semibold text-nowrap" id="comm-tab" data-bs-toggle="tab" data-bs-target="#comm-pane" type="button" role="tab">
            <i class="fa-solid fa-comments me-1 text-info"></i> Communication (<?= count($communications) ?>)
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link py-3 fw-semibold text-nowrap" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-pane" type="button" role="tab">
            <i class="fa-solid fa-clock-rotate-left me-1 text-info"></i> Status History
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link py-3 fw-semibold text-nowrap" id="downloads-tab" data-bs-toggle="tab" data-bs-target="#decision-pane" type="button" role="tab">
            <i class="fa-solid fa-download me-1 text-dark"></i> Downloads
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link py-3 fw-semibold text-nowrap" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity-pane" type="button" role="tab">
            <i class="fa-solid fa-shield-halved me-1 text-danger"></i> Activity Log
          </button>
        </li>
      </ul>
    </div>

    <div class="card-body p-4">
      <div class="tab-content" id="appDetailsTabContent">
        <!-- TAB 1: Application & Applicant Info -->
        <div class="tab-pane fade show active" id="info-pane" role="tabpanel">
          <div class="row g-4">
            <!-- Application Information -->
            <div class="col-lg-6">
              <h6 class="fw-bold mb-3 text-dark border-bottom pb-2"><i class="fa-solid fa-folder-open text-primary me-2"></i> Application Specifics</h6>
              <div class="row g-2 small">
                <div class="col-sm-5 text-muted">Application Number:</div>
                <div class="col-sm-7 fw-bold text-primary"><?= e($app['application_number']) ?></div>

                <div class="col-sm-5 text-muted">Visa Service:</div>
                <div class="col-sm-7 fw-semibold"><?= e($app['service_name']) ?></div>

                <div class="col-sm-5 text-muted">Destination Country:</div>
                <div class="col-sm-7"><?= $app['flag_emoji'] ?> <?= e($app['country_name']) ?> (<?= e($app['country_code']) ?>)</div>

                <div class="col-sm-5 text-muted">Entry &amp; Processing Type:</div>
                <div class="col-sm-7"><?= e($app['entry_type']) ?> &bull; <?= e($app['processing_type']) ?></div>

                <div class="col-sm-5 text-muted">Application Date:</div>
                <div class="col-sm-7"><?= format_date($app['application_date'] ?? $app['created_at']) ?></div>

                <div class="col-sm-5 text-muted">Expected Completion:</div>
                <div class="col-sm-7 <?= e($deadlineClass) ?> fw-semibold"><?= format_date($app['expected_completion_date']) ?> (<?= e($deadlineStatus) ?>)</div>

                <div class="col-sm-5 text-muted">Travel &amp; Return Dates:</div>
                <div class="col-sm-7"><?= format_date($app['travel_date']) ?> &rarr; <?= format_date($app['return_date']) ?></div>

                <div class="col-sm-5 text-muted">Processing Branch:</div>
                <div class="col-sm-7"><?= e($app['branch_name'] ?? 'Dubai Head Office') ?></div>

                <div class="col-sm-5 text-muted">Created By:</div>
                <div class="col-sm-7"><?= e($app['created_by_name'] ?? 'System') ?> on <?= format_datetime($app['created_at']) ?></div>
              </div>
            </div>

            <!-- Applicant Identity & Passport Summary -->
            <div class="col-lg-6">
              <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-id-card text-success me-2"></i> Applicant Identity Summary</h6>
                <a href="/customers/show?id=<?= $app['customer_id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.75rem;">View Full Profile &rarr;</a>
              </div>
              <div class="row g-2 small">
                <div class="col-sm-5 text-muted">Full Legal Name:</div>
                <div class="col-sm-7 fw-bold text-dark"><?= e($app['customer_name']) ?></div>

                <div class="col-sm-5 text-muted">Customer Code:</div>
                <div class="col-sm-7"><span class="badge bg-light text-dark border"><?= e($app['customer_code']) ?></span></div>

                <div class="col-sm-5 text-muted">Primary Passport:</div>
                <div class="col-sm-7 fw-semibold"><i class="fa-solid fa-passport text-secondary me-1"></i><?= e($app['passport_number'] ?: $app['current_passport'] ?: '—') ?></div>

                <div class="col-sm-5 text-muted">Passport Expiry:</div>
                <div class="col-sm-7"><?= format_date($app['passport_expiry'] ?? $app['passport_expiry_date'] ?? null) ?></div>

                <div class="col-sm-5 text-muted">Nationality &amp; Gender:</div>
                <div class="col-sm-7"><?= e($app['customer_nationality']) ?> &bull; <?= e($app['customer_gender'] ?? '—') ?></div>

                <div class="col-sm-5 text-muted">Date of Birth:</div>
                <div class="col-sm-7"><?= format_date($app['customer_dob']) ?></div>

                <div class="col-sm-5 text-muted">Mobile Phone:</div>
                <div class="col-sm-7"><a href="tel:<?= e($app['customer_mobile']) ?>" class="text-decoration-none"><?= e($app['customer_mobile']) ?></a></div>

                <div class="col-sm-5 text-muted">Email Address:</div>
                <div class="col-sm-7"><a href="mailto:<?= e($app['customer_email']) ?>" class="text-decoration-none"><?= e($app['customer_email']) ?></a></div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 1B: Customer & Family Background -->
        <div class="tab-pane fade" id="customer-pane" role="tabpanel">
          <div class="row g-4">
            <!-- Family Details -->
            <div class="col-lg-6">
              <h6 class="fw-bold mb-3 text-dark border-bottom pb-2"><i class="fa-solid fa-people-roof text-primary me-2"></i> Family &amp; Parental Background</h6>
              <div class="row g-2 small">
                <div class="col-sm-5 text-muted">Father's Full Name:</div>
                <div class="col-sm-7 fw-semibold"><?= e($customerFamily['father_name'] ?? 'Not Recorded') ?></div>

                <div class="col-sm-5 text-muted">Father's DOB:</div>
                <div class="col-sm-7"><?= format_date($customerFamily['father_dob'] ?? null) ?></div>

                <div class="col-sm-5 text-muted">Father's Birth Country:</div>
                <div class="col-sm-7"><?= e($customerFamily['father_country_of_birth'] ?? '—') ?></div>

                <div class="col-sm-5 text-muted">Father's Nationality:</div>
                <div class="col-sm-7"><?= e($customerFamily['father_nationality'] ?? '—') ?></div>

                <div class="col-sm-5 text-muted">Father's Religion:</div>
                <div class="col-sm-7"><?= e($customerFamily['father_religion'] ?? '—') ?></div>

                <div class="col-12 my-2"><hr class="my-1"></div>

                <div class="col-sm-5 text-muted">Mother's Full Name:</div>
                <div class="col-sm-7 fw-semibold"><?= e($customerFamily['mother_name'] ?? 'Not Recorded') ?></div>

                <div class="col-sm-5 text-muted">Mother's DOB:</div>
                <div class="col-sm-7"><?= format_date($customerFamily['mother_dob'] ?? null) ?></div>

                <div class="col-sm-5 text-muted">Mother's Birth Country:</div>
                <div class="col-sm-7"><?= e($customerFamily['mother_country_of_birth'] ?? '—') ?></div>

                <div class="col-sm-5 text-muted">Mother's Nationality:</div>
                <div class="col-sm-7"><?= e($customerFamily['mother_nationality'] ?? '—') ?></div>

                <div class="col-sm-5 text-muted">Mother's Mobile:</div>
                <div class="col-sm-7"><?= e($customerFamily['mother_mobile'] ?? '—') ?></div>
              </div>
            </div>

            <!-- Residence & Employment Details -->
            <div class="col-lg-6">
              <h6 class="fw-bold mb-3 text-dark border-bottom pb-2"><i class="fa-solid fa-house-user text-success me-2"></i> Current Country of Residence</h6>
              <div class="row g-2 small">
                <div class="col-sm-5 text-muted">Residence Country:</div>
                <div class="col-sm-7 fw-bold text-dark"><?= e($customerResidence['residence_country'] ?? $app['customer_nationality']) ?></div>

                <div class="col-sm-5 text-muted">Residency / Visa Permit #:</div>
                <div class="col-sm-7 font-monospace fw-semibold text-primary"><?= e($customerResidence['permit_number'] ?? 'Not Applicable') ?></div>

                <div class="col-sm-5 text-muted">Permit Expiry Date:</div>
                <div class="col-sm-7"><?= format_date($customerResidence['expiry_date'] ?? null) ?></div>

                <div class="col-sm-5 text-muted">Current Employer / Sponsor:</div>
                <div class="col-sm-7"><?= e($customerResidence['employer'] ?? '—') ?></div>

                <div class="col-sm-5 text-muted">Job Title / Designation:</div>
                <div class="col-sm-7"><?= e($customerResidence['job_title'] ?? '—') ?></div>

                <div class="col-12 my-2"><hr class="my-1"></div>

                <div class="col-sm-5 text-muted">Customer Address:</div>
                <div class="col-sm-7"><?= e($app['customer_address'] ?? '—') ?></div>

                <div class="col-sm-5 text-muted">Applicant Account:</div>
                <div class="col-sm-7">
                  <a href="/customers/show?id=<?= $app['customer_id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2 mt-1">
                    <i class="fa-solid fa-user-pen me-1"></i> Edit Full Profile
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 2: Documents Checklist -->
        <div class="tab-pane fade" id="docs-pane" role="tabpanel">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pb-3 border-bottom">
            <div>
              <div class="d-flex align-items-center gap-2">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-file-circle-check text-success me-1"></i> Visa Document Checklist</h6>
                <span class="badge bg-primary-subtle text-primary fw-semibold"><?= $checklistData['total_verified'] ?> / <?= $checklistData['total_required'] ?> Verified</span>
              </div>
              <div class="text-muted small mt-1">Checklist automatically generated from visa service category requirements.</div>
            </div>

            <div class="d-flex align-items-center gap-2" style="min-width: 240px;">
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between small mb-1">
                  <span class="text-muted">Verification Progress:</span>
                  <span class="fw-bold text-dark"><?= $checklistData['percentage'] ?>%</span>
                </div>
                <div class="progress" style="height: 8px;">
                  <div class="progress-bar <?= $checklistData['percentage'] === 100 ? 'bg-success' : 'bg-primary' ?>" role="progressbar" style="width: <?= $checklistData['percentage'] ?>%;" aria-valuenow="<?= $checklistData['percentage'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
              </div>
              <a href="/documents" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-folder-open me-1"></i> Document Hub</a>
            </div>
          </div>

          <?php if (empty($documentChecklist)): ?>
            <div class="p-4 text-center text-muted bg-light rounded">No specific document requirements configured for this visa service.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table-custom">
                <thead>
                  <tr>
                    <th>Required Document</th>
                    <th>Category</th>
                    <th>Requirement Type</th>
                    <th>Status</th>
                    <th>Uploaded File &amp; Expiry</th>
                    <th>Verified By</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($documentChecklist as $doc): ?>
                    <?php
                      $docStatus = $doc['status'];
                      $statusBadge = 'bg-secondary';
                      if ($docStatus === 'VERIFIED') $statusBadge = 'bg-success';
                      elseif ($docStatus === 'UNDER_REVIEW' || $docStatus === 'UPLOADED') $statusBadge = 'bg-warning text-dark';
                      elseif ($docStatus === 'REJECTED') $statusBadge = 'bg-danger';
                      elseif ($docStatus === 'EXPIRED') $statusBadge = 'bg-danger text-white fw-bold';
                      elseif ($docStatus === 'MISSING') $statusBadge = 'bg-secondary-subtle text-secondary border';
                    ?>
                    <tr>
                      <td>
                        <div class="fw-semibold text-dark"><?= e($doc['document_name']) ?></div>
                        <?php if (!empty($doc['condition_notes'])): ?>
                          <div class="text-muted small" style="font-size: 0.72rem;"><?= e($doc['condition_notes']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($doc['rejection_reason'])): ?>
                          <div class="text-danger small mt-1" style="font-size: 0.72rem;">
                            <i class="fa-solid fa-circle-exclamation me-1"></i><strong>Rejected:</strong> <?= e($doc['rejection_reason']) ?>
                          </div>
                        <?php endif; ?>
                      </td>
                      <td><span class="badge bg-light text-secondary border"><?= e($doc['category']) ?></span></td>
                      <td>
                        <?php if ($doc['is_critical']): ?>
                          <span class="badge bg-danger text-white fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i>Critical</span>
                        <?php elseif ($doc['is_mandatory']): ?>
                          <span class="badge bg-danger-subtle text-danger">Mandatory</span>
                        <?php else: ?>
                          <span class="badge bg-light text-muted border">Optional</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <span class="badge <?= $statusBadge ?> px-2 py-1"><?= e($docStatus) ?></span>
                      </td>
                      <td>
                        <?php if (!empty($doc['file_path'])): ?>
                          <div>
                            <a href="/documents/preview?id=<?= $doc['document_id'] ?>" target="_blank" class="text-primary text-decoration-none small fw-semibold">
                              <i class="fa-solid fa-paperclip me-1"></i><?= e($doc['file_name']) ?>
                            </a>
                            <span class="text-muted small"> (v<?= (int)$doc['version'] ?>)</span>
                          </div>
                          <?php if (!empty($doc['expiry_date'])): ?>
                            <div class="mt-1">
                              <span class="badge <?= $doc['expiry_info']['badge_class'] ?>" style="font-size: 0.7rem;">
                                <i class="fa-solid fa-clock me-1"></i><?= e($doc['expiry_info']['label']) ?>
                              </span>
                            </div>
                          <?php endif; ?>
                        <?php else: ?>
                          <span class="text-muted small">Not Uploaded</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <span class="small text-muted"><?= e($doc['verified_by_name'] ?? '—') ?></span>
                      </td>
                      <td class="text-end">
                        <?php if (!empty($doc['file_path'])): ?>
                          <div class="btn-group btn-group-sm">
                            <a href="/documents/preview?id=<?= $doc['document_id'] ?>" target="_blank" class="btn btn-outline-primary" title="Preview">
                              <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="/documents/download?id=<?= $doc['document_id'] ?>" class="btn btn-outline-secondary" title="Download">
                              <i class="fa-solid fa-download"></i>
                            </a>
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false"></button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="font-size: 0.85rem;">
                              <?php if ($doc['status'] !== 'VERIFIED'): ?>
                                <li>
                                  <form action="/documents/verify" method="POST" class="d-inline" onsubmit="return confirm('Confirm verification of this document?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="document_id" value="<?= $doc['document_id'] ?>">
                                    <button type="submit" class="dropdown-item py-2 text-success"><i class="fa-solid fa-check-circle me-2"></i> Verify Document</button>
                                  </form>
                                </li>
                              <?php endif; ?>
                              <?php if ($doc['status'] !== 'REJECTED'): ?>
                                <li>
                                  <button type="button" class="dropdown-item py-2 text-danger" onclick="openAppDocRejectModal(<?= $doc['document_id'] ?>, '<?= e(addslashes($doc['document_name'])) ?>')">
                                    <i class="fa-solid fa-times-circle me-2"></i> Reject Document
                                  </button>
                                </li>
                              <?php endif; ?>
                              <li>
                                <button type="button" class="dropdown-item py-2" onclick="openAppDocReplaceModal(<?= $doc['document_id'] ?>, '<?= e(addslashes($doc['document_name'])) ?>')">
                                  <i class="fa-solid fa-cloud-arrow-up text-primary me-2"></i> Upload Replacement
                                </button>
                              </li>
                            </ul>
                          </div>
                        <?php else: ?>
                          <button type="button" class="btn btn-primary btn-sm py-1 px-2" onclick="openAppDocUploadModal(<?= $app['id'] ?>, <?= $doc['document_type_id'] ?>, '<?= e(addslashes($doc['document_name'])) ?>')">
                            <i class="fa-solid fa-upload me-1"></i> Upload
                          </button>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <!-- TAB 2B: Document Requests -->
        <div class="tab-pane fade" id="doc-req-pane" role="tabpanel">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-file-circle-question text-warning me-2"></i> Additional Documents Requested from Applicant</h6>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#requestDocModal">
              <i class="fa-solid fa-plus me-1"></i> Request Document
            </button>
          </div>

          <?php if (empty($documentRequests)): ?>
            <div class="p-4 text-center text-muted bg-light rounded border">
              <i class="fa-solid fa-file-circle-check fa-2x mb-2 d-block opacity-25"></i>
              No additional documents requested for this applicant.
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 small border rounded">
                <thead class="table-light">
                  <tr>
                    <th>Document Type</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Instructions / Notes</th>
                    <th>Requested By</th>
                    <th>Date Requested</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($documentRequests as $dr): ?>
                    <tr>
                      <td class="fw-semibold text-primary"><?= e($dr['document_name'] ?? 'Custom Document') ?></td>
                      <td class="fw-semibold <?= strtotime($dr['due_date']) < time() && $dr['status'] === 'PENDING' ? 'text-danger' : 'text-dark' ?>">
                        <?= format_date($dr['due_date']) ?>
                      </td>
                      <td>
                        <span class="badge <?= $dr['status'] === 'SUBMITTED' ? 'bg-success' : ($dr['status'] === 'PENDING' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                          <?= e($dr['status']) ?>
                        </span>
                      </td>
                      <td><?= e($dr['notes'] ?: '—') ?></td>
                      <td><?= e($dr['requested_by_name'] ?? 'Staff') ?></td>
                      <td class="text-muted"><?= format_datetime($dr['created_at']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <!-- TAB 3: Tasks -->
        <div class="tab-pane fade" id="tasks-pane" role="tabpanel">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-list-check text-warning me-2"></i> Operational Tasks &amp; Milestones</h6>
            <a href="/tasks" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-plus me-1"></i> Create Task</a>
          </div>

          <?php if (empty($tasks)): ?>
            <div class="p-4 text-center text-muted bg-light rounded">No linked tasks for this visa application.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table-custom">
                <thead>
                  <tr>
                    <th>Task Title</th>
                    <th>Assigned To</th>
                    <th>Due Date</th>
                    <th>Priority</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($tasks as $tsk): ?>
                    <tr>
                      <td class="fw-semibold text-dark"><?= e($tsk['task_title'] ?? $tsk['title'] ?? 'Task') ?></td>
                      <td><?= e($tsk['assigned_to_name'] ?? 'Unassigned') ?></td>
                      <td><?= format_date($tsk['due_date']) ?></td>
                      <td><span class="badge badge-priority-<?= strtolower($tsk['priority']) ?>"><?= e($tsk['priority']) ?></span></td>
                      <td><span class="badge bg-<?= $tsk['status'] === 'Completed' ? 'success' : 'warning text-dark' ?>"><?= e($tsk['status']) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <!-- TAB 4: Appointments -->
        <div class="tab-pane fade" id="appointments-pane" role="tabpanel">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-calendar-check text-danger me-2"></i> Scheduled Appointments</h6>
            <a href="/appointments" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-calendar-plus me-1"></i> Schedule Appointment</a>
          </div>

          <?php if (empty($appointments)): ?>
            <div class="p-4 text-center text-muted bg-light rounded">No appointments scheduled for this application.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table-custom">
                <thead>
                  <tr>
                    <th>Type</th>
                    <th>Center / Location</th>
                    <th>Date &amp; Time</th>
                    <th>Reference</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($appointments as $apt): ?>
                    <tr>
                      <td class="fw-semibold text-dark"><?= e($apt['appointment_type']) ?></td>
                      <td><?= e($apt['center_name']) ?></td>
                      <td><?= format_date($apt['appointment_date']) ?> at <?= e($apt['appointment_time']) ?></td>
                      <td><code><?= e($apt['reference_number'] ?? '—') ?></code></td>
                      <td><span class="badge bg-info text-dark"><?= e($apt['status']) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <!-- TAB 5: Stage History & Audit Trail -->
        <div class="tab-pane fade" id="history-pane" role="tabpanel">
          <h6 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-timeline text-primary me-2"></i> Immutable Lifecycle Stage Transition Log</h6>
          <?php if (empty($stageHistory)): ?>
            <div class="p-4 text-center text-muted bg-light rounded">No stage transition history recorded yet.</div>
          <?php else: ?>
            <div class="table-responsive mb-4">
              <table class="table-custom">
                <thead>
                  <tr>
                    <th>Timestamp</th>
                    <th>From Stage</th>
                    <th>To Stage</th>
                    <th>Status Shift</th>
                    <th>Officer</th>
                    <th>Comments / Reason</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($stageHistory as $sh): ?>
                    <tr>
                      <td class="small text-muted"><?= format_datetime($sh['created_at']) ?></td>
                      <td><span class="badge bg-light text-muted border"><?= e($sh['from_stage']) ?></span></td>
                      <td><span class="badge bg-primary-subtle text-primary fw-semibold">&rarr; <?= e($sh['to_stage']) ?></span></td>
                      <td><span class="small fw-medium"><?= e($sh['from_status']) ?> &rarr; <?= e($sh['to_status']) ?></span></td>
                      <td><span class="fw-semibold small"><?= e($sh['changed_by_name'] ?? 'System') ?></span></td>
                      <td class="small text-dark"><?= e($sh['comments']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

          <h6 class="fw-bold mb-3 text-dark border-top pt-3"><i class="fa-solid fa-shield-halved text-secondary me-2"></i> Application Audit Trail Events</h6>
          <?php if (empty($activityLogs)): ?>
            <div class="p-4 text-center text-muted bg-light rounded">No detailed audit log entries recorded.</div>
          <?php else: ?>
            <div class="activity-timeline">
              <?php foreach ($activityLogs as $log): ?>
                <div class="activity-item p-2 border-bottom d-flex align-items-start gap-3">
                  <div class="rounded-circle p-1 bg-light border text-primary mt-1" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                    <i class="fa-solid fa-clock"></i>
                  </div>
                  <div>
                    <div class="fw-semibold text-dark small"><?= e($log['action']) ?> &bull; <span class="text-muted"><?= e($log['user_name'] ?? 'Staff') ?></span></div>
                    <div class="text-muted small"><?= e($log['description']) ?></div>
                    <div class="text-muted" style="font-size: 0.7rem;"><?= format_datetime($log['created_at']) ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- TAB 6: Internal Notes -->
        <div class="tab-pane fade" id="notes-pane" role="tabpanel">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-note-sticky text-secondary me-2"></i> Confidential Internal Staff Notes</h6>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addNoteModal">
              <i class="fa-solid fa-plus me-1"></i> Add Note
            </button>
          </div>

          <div class="bg-light p-3 rounded border" style="white-space: pre-wrap; font-family: inherit; font-size: 0.85rem; max-height: 400px; overflow-y: auto;">
            <?= e($app['internal_notes'] ?: 'No internal notes recorded for this application yet.') ?>
          </div>
        </div>

        <!-- TAB 7: Payments & Finance -->
        <div class="tab-pane fade" id="payments-pane" role="tabpanel">
          
          <!-- Financial Summary Cards -->
          <div class="row g-3 mb-4">
            <div class="col-md-3">
              <div class="border rounded p-3 text-center bg-light">
                <div class="small text-muted mb-1">Total Invoice</div>
                <div class="fs-5 fw-bold text-dark"><?= format_currency($app['total_amount'] ?? $app['selling_price'] ?? 0) ?></div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="border rounded p-3 text-center bg-success bg-opacity-10">
                <div class="small text-muted mb-1">Total Paid</div>
                <div class="fs-5 fw-bold text-success"><?= format_currency($app['paid_amount'] ?? 0) ?></div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="border rounded p-3 text-center <?= ((float)($app['balance_amount'] ?? 0) > 0) ? 'bg-danger bg-opacity-10' : 'bg-light' ?>">
                <div class="small text-muted mb-1">Balance Due</div>
                <div class="fs-5 fw-bold <?= ((float)($app['balance_amount'] ?? 0) > 0) ? 'text-danger' : 'text-muted' ?>"><?= format_currency($app['balance_amount'] ?? 0) ?></div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="border rounded p-3 text-center bg-light">
                <div class="small text-muted mb-1">Payment Status</div>
                <div class="fw-bold">
                  <?php
                    $bal = (float)($app['balance_amount'] ?? 0);
                    $paid = (float)($app['paid_amount'] ?? 0);
                    $total = (float)($app['total_amount'] ?? $app['selling_price'] ?? 0);
                    if ($bal <= 0 && $paid > 0) echo '<span class="badge bg-success fs-6">PAID</span>';
                    elseif ($paid > 0 && $bal > 0) echo '<span class="badge bg-warning text-dark fs-6">PARTIAL</span>';
                    else echo '<span class="badge bg-danger fs-6">UNPAID</span>';
                  ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Record Payment Form -->
          <div class="card border mb-4">
            <div class="card-header bg-success bg-opacity-10 border-bottom py-3">
              <h6 class="mb-0 fw-bold text-success"><i class="fa-solid fa-circle-plus me-2"></i>Record New Payment</h6>
            </div>
            <div class="card-body p-4">
              <form action="/payments/store" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                <div class="row g-3">
                  <div class="col-md-3">
                    <label class="form-label small fw-semibold">Amount (USD) <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <span class="input-group-text">$</span>
                      <input type="number" name="amount" class="form-control" step="0.01" min="0.01" 
                             value="<?= number_format((float)($app['balance_amount'] ?? 0), 2, '.', '') ?>"
                             placeholder="0.00" required>
                    </div>
                    <?php if ((float)($app['balance_amount'] ?? 0) > 0): ?>
                      <div class="form-text text-danger fw-semibold">Balance due: <?= format_currency($app['balance_amount']) ?></div>
                    <?php endif; ?>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label small fw-semibold">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label small fw-semibold">Payment Method <span class="text-danger">*</span></label>
                    <select name="payment_method" class="form-select" required>
                      <option value="Cash">Cash</option>
                      <option value="Bank Transfer">Bank Transfer</option>
                      <option value="Credit Card">Credit Card</option>
                      <option value="Debit Card">Debit Card</option>
                      <option value="Cheque">Cheque</option>
                      <option value="Online Payment">Online Payment</option>
                      <option value="Western Union">Western Union</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold">Transaction Reference</label>
                    <input type="text" name="transaction_reference" class="form-control" placeholder="Bank ref / cheque number...">
                  </div>
                  <div class="col-12">
                    <label class="form-label small fw-semibold">Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="Optional payment notes...">
                  </div>
                  <div class="col-12">
                    <button type="submit" class="btn btn-success px-4 fw-semibold">
                      <i class="fa-solid fa-check me-2"></i>Record Payment & Generate Receipt
                    </button>
                    <button type="button" class="btn btn-outline-primary ms-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#generateLinkModal">
                      <i class="fa-solid fa-link me-1"></i>Generate Payment Link
                    </button>
                    <a href="/payments/invoice?app_id=<?= $app['id'] ?>" target="_blank" class="btn btn-outline-secondary ms-2">
                      <i class="fa-solid fa-file-invoice me-1"></i>Print Invoice
                    </a>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <!-- Payment History Table -->
          <div class="mb-4">
            <h6 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="fa-solid fa-receipt text-success me-2"></i>Payment History</h6>
            <?php if (empty($appPayments)): ?>
              <div class="text-center py-4 text-muted bg-light rounded border">
                <i class="fa-solid fa-money-bill-wave fa-2x mb-2 d-block opacity-25"></i>
                No payments recorded yet for this application.
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Receipt #</th>
                      <th>Date</th>
                      <th>Amount</th>
                      <th>Method</th>
                      <th>Reference</th>
                      <th>Received By</th>
                      <th>Status</th>
                      <th class="text-end">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($appPayments as $pay): ?>
                      <tr>
                        <td class="fw-semibold text-primary"><?= e($pay['payment_number']) ?></td>
                        <td><?= e($pay['payment_date']) ?></td>
                        <td class="fw-bold text-success"><?= format_currency($pay['amount']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= e($pay['payment_method']) ?></span></td>
                        <td class="text-muted small"><?= e($pay['transaction_reference'] ?: '—') ?></td>
                        <td><?= e($pay['received_by_name'] ?? '—') ?></td>
                        <td><span class="badge bg-success-subtle text-success border border-success"><?= e($pay['status']) ?></span></td>
                        <td class="text-end">
                          <a href="/payments/receipt?id=<?= $pay['id'] ?>" target="_blank" class="btn btn-outline-primary btn-sm" title="View Receipt">
                            <i class="fa-solid fa-receipt me-1"></i>Receipt
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                  <tfoot class="table-light fw-bold">
                    <tr>
                      <td colspan="2" class="text-end text-muted">Total Collected:</td>
                      <td class="text-success"><?= format_currency(array_sum(array_column($appPayments, 'amount'))) ?></td>
                      <td colspan="5"></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            <?php endif; ?>
          </div>

          <!-- Refund Section -->
          <?php if (!empty($appPayments)): ?>
          <div>
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
              <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-rotate-left text-warning me-2"></i>Refunds</h6>
              <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#refundModal">
                <i class="fa-solid fa-plus me-1"></i>Process Refund
              </button>
            </div>
            <?php if (empty($appRefunds)): ?>
              <p class="text-muted small">No refunds processed for this application.</p>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead class="table-light">
                    <tr><th>Refund #</th><th>Date</th><th>Amount</th><th>Method</th><th>Reason</th><th>Processed By</th></tr>
                  </thead>
                  <tbody>
                    <?php foreach ($appRefunds as $ref): ?>
                      <tr>
                        <td class="fw-semibold text-warning"><?= e($ref['refund_number']) ?></td>
                        <td><?= e(date('Y-m-d', strtotime($ref['created_at']))) ?></td>
                        <td class="fw-bold text-warning"><?= format_currency($ref['amount']) ?></td>
                        <td><?= e($ref['payment_method']) ?></td>
                        <td class="text-muted small"><?= e($ref['reason']) ?></td>
                        <td><?= e($ref['processed_by_name'] ?? '—') ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>

        </div>

        <!-- TAB 8: Supplier Details & Finance -->
        <div class="tab-pane fade" id="supplier-pane" role="tabpanel">
          <div class="row g-4 mb-4">
            <div class="col-lg-6">
              <h6 class="fw-bold mb-3 text-dark border-bottom pb-2"><i class="fa-solid fa-building-flag text-primary me-2"></i> Processing Supplier &amp; Clearing Agent</h6>
              <?php if (!empty($supplierDetails)): ?>
                <div class="row g-2 small">
                  <div class="col-sm-5 text-muted">Supplier / Vendor:</div>
                  <div class="col-sm-7 fw-bold text-dark"><?= e($supplierDetails['name']) ?></div>

                  <div class="col-sm-5 text-muted">Supplier Code:</div>
                  <div class="col-sm-7"><span class="badge bg-light text-dark border"><?= e($supplierDetails['code']) ?></span></div>

                  <div class="col-sm-5 text-muted">Country &amp; City:</div>
                  <div class="col-sm-7"><?= e($supplierDetails['country'] ?? '—') ?>, <?= e($supplierDetails['city'] ?? '—') ?></div>

                  <div class="col-sm-5 text-muted">Contact Person:</div>
                  <div class="col-sm-7"><?= e($supplierDetails['contact_person'] ?? '—') ?></div>

                  <div class="col-sm-5 text-muted">Supplier Reference #:</div>
                  <div class="col-sm-7 font-monospace fw-bold text-primary"><?= e($app['supplier_reference'] ?? 'Not Assigned') ?></div>

                  <div class="col-sm-5 text-muted">Embassy / Gov Ref #:</div>
                  <div class="col-sm-7 font-monospace fw-bold text-secondary"><?= e($app['embassy_reference'] ?? '—') ?></div>
                </div>
              <?php else: ?>
                <div class="p-3 bg-light rounded text-muted small">No external supplier assigned. Processed via in-house consular team.</div>
              <?php endif; ?>
            </div>

            <div class="col-lg-6">
              <h6 class="fw-bold mb-3 text-dark border-bottom pb-2"><i class="fa-solid fa-calculator text-success me-2"></i> Commercial Margins &amp; Costs</h6>
              <div class="row g-2 small">
                <div class="col-sm-5 text-muted">Selling Price (Client):</div>
                <div class="col-sm-7 fw-bold text-dark"><?= format_currency($app['selling_price'] ?? $app['total_amount'] ?? 0) ?></div>

                <div class="col-sm-5 text-muted">Supplier Net Cost:</div>
                <div class="col-sm-7 fw-bold text-danger"><?= format_currency($app['cost_price'] ?? $app['supplier_cost'] ?? 0) ?></div>

                <div class="col-sm-5 text-muted">Estimated Profit:</div>
                <div class="col-sm-7 fw-bold text-success">
                  <?= format_currency(max(0, (float)($app['selling_price'] ?? $app['total_amount'] ?? 0) - (float)($app['cost_price'] ?? $app['supplier_cost'] ?? 0))) ?>
                </div>

                <div class="col-sm-5 text-muted">Profit Margin %:</div>
                <div class="col-sm-7 fw-bold text-primary">
                  <?php
                    $sp = (float)($app['selling_price'] ?? $app['total_amount'] ?? 0);
                    $cp = (float)($app['cost_price'] ?? $app['supplier_cost'] ?? 0);
                    echo $sp > 0 ? number_format((($sp - $cp) / $sp) * 100, 1) . '%' : '0.0%';
                  ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Supplier Disbursements -->
          <?php if (!empty($supplierPayments)): ?>
            <h6 class="fw-bold mb-2 text-dark border-bottom pb-2"><i class="fa-solid fa-money-bill-transfer text-primary me-2"></i> Supplier Disbursements &amp; Settlements</h6>
            <div class="table-responsive">
              <table class="table table-sm align-middle small mb-0">
                <thead class="table-light">
                  <tr><th>Payment Ref</th><th>Date</th><th>Amount</th><th>Method</th><th>Notes</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($supplierPayments as $sp): ?>
                    <tr>
                      <td class="fw-bold font-monospace"><?= e($sp['payment_reference']) ?></td>
                      <td><?= e($sp['payment_date']) ?></td>
                      <td class="fw-bold text-danger"><?= format_currency($sp['amount']) ?></td>
                      <td><?= e($sp['payment_method']) ?></td>
                      <td><?= e($sp['notes'] ?? '—') ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <!-- TAB 9: Client Communications Log -->
        <div class="tab-pane fade" id="comm-pane" role="tabpanel">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-comments text-info me-2"></i> Client Touchpoints &amp; Communications</h6>
            <button type="button" class="btn btn-info btn-sm text-dark fw-semibold" data-bs-toggle="modal" data-bs-target="#addCommModal">
              <i class="fa-solid fa-plus me-1"></i> Log Communication
            </button>
          </div>

          <?php if (empty($communications)): ?>
            <div class="p-4 text-center text-muted bg-light rounded border">
              <i class="fa-solid fa-phone-volume fa-2x mb-2 d-block opacity-25"></i>
              No communications logged for this application yet.
            </div>
          <?php else: ?>
            <div class="activity-timeline">
              <?php foreach ($communications as $comm): ?>
                <div class="p-3 border rounded bg-light mb-3">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                      <span class="badge bg-primary me-1"><i class="fa-solid fa-phone me-1"></i><?= e($comm['channel']) ?></span>
                      <span class="badge <?= $comm['direction'] === 'Inbound' ? 'bg-success' : 'bg-secondary' ?>"><?= e($comm['direction']) ?></span>
                      <strong class="text-dark ms-2"><?= e($comm['subject'] ?? 'Client Touchpoint') ?></strong>
                    </div>
                    <span class="text-muted small"><?= format_datetime($comm['created_at']) ?></span>
                  </div>
                  <p class="mb-1 text-dark small" style="white-space: pre-wrap;"><?= e($comm['message']) ?></p>
                  <div class="text-muted" style="font-size: 0.72rem;">
                    Contact Person: <strong><?= e($comm['contact_person'] ?? 'Applicant') ?></strong> &bull; Logged by: <strong><?= e($comm['staff_name'] ?? 'Staff Officer') ?></strong>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- TAB 10: Decision, Visa Grant & Downloads -->
        <div class="tab-pane fade" id="decision-pane" role="tabpanel">
          <h6 class="fw-bold mb-3 text-dark border-bottom pb-2"><i class="fa-solid fa-gavel text-dark me-2"></i> Official Visa Decisions &amp; Documents</h6>

          <?php if ($visaApproval): ?>
            <div class="card border-success shadow-sm mb-4">
              <div class="card-header bg-success text-white py-2.5 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="fa-solid fa-circle-check me-2"></i> OFFICIAL VISA APPROVED &amp; ISSUED</span>
                <span class="badge bg-white text-success fw-bold">Visa # <?= e($visaApproval['visa_number']) ?></span>
              </div>
              <div class="card-body p-3">
                <div class="row g-3 small">
                  <div class="col-md-3"><strong>Issue Date:</strong> <?= format_date($visaApproval['issue_date']) ?></div>
                  <div class="col-md-3"><strong>Expiry Date:</strong> <span class="text-danger fw-bold"><?= format_date($visaApproval['expiry_date']) ?></span></div>
                  <div class="col-md-3"><strong>Max Stay:</strong> <?= e($visaApproval['maximum_stay'] ?? '30 Days') ?></div>
                  <div class="col-md-3"><strong>Validity:</strong> <?= e($visaApproval['validity'] ?? '60 Days') ?></div>
                  <div class="col-12 text-muted"><strong>Guidelines:</strong> <?= e($visaApproval['approval_notes'] ?? 'Carry copy while traveling.') ?></div>
                  <?php if (!empty($visaApproval['approved_visa_file'])): ?>
                    <div class="col-12 mt-2">
                      <a href="/storage/uploads/<?= e($visaApproval['approved_visa_file']) ?>" target="_blank" class="btn btn-success btn-sm fw-bold">
                        <i class="fa-solid fa-download me-1"></i> Download Official Approved Visa PDF
                      </a>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($visaRejection): ?>
            <div class="card border-danger shadow-sm mb-4">
              <div class="card-header bg-danger text-white py-2.5">
                <span class="fw-bold"><i class="fa-solid fa-circle-xmark me-2"></i> VISA APPLICATION REJECTED</span>
              </div>
              <div class="card-body p-3 small">
                <div class="text-danger fw-bold mb-1">Customer Reason:</div>
                <p class="mb-2"><?= e($visaRejection['customer_reason']) ?></p>
                <div class="text-muted">Reapplication Eligibility: <strong><?= e($visaRejection['reapplication_eligibility']) ?></strong></div>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($applicationReturns)): ?>
            <h6 class="fw-bold mb-2 text-dark"><i class="fa-solid fa-rotate-left text-warning me-2"></i> Modification Return History</h6>
            <?php foreach ($applicationReturns as $ret): ?>
              <div class="p-3 border rounded bg-warning bg-opacity-10 mb-2 small">
                <div class="d-flex justify-content-between mb-1">
                  <strong class="text-dark"><?= e($ret['return_reason']) ?></strong>
                  <span class="text-muted"><?= format_datetime($ret['created_at']) ?></span>
                </div>
                <div>Required Changes: <?= e($ret['required_changes'] ?? '—') ?></div>
                <div class="text-danger fw-bold mt-1">Deadline: <?= format_date($ret['deadline'] ?? null) ?></div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>

          <!-- Downloadable Financial Documents -->
          <div class="mt-4 pt-3 border-top">
            <h6 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-download text-primary me-2"></i> Export &amp; Downloadable Receipts</h6>
            <div class="d-flex flex-wrap gap-2">
              <a href="/payments/invoice?app_id=<?= $app['id'] ?>" target="_blank" class="btn btn-outline-dark btn-sm">
                <i class="fa-solid fa-file-invoice text-success me-1"></i> Official Tax Invoice
              </a>
              <?php if (!empty($appPayments)): ?>
                <a href="/payments/receipt?id=<?= $appPayments[0]['id'] ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                  <i class="fa-solid fa-receipt text-primary me-1"></i> Latest Payment Receipt
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- TAB 3: Visa Details -->
        <div class="tab-pane fade" id="visa-pane" role="tabpanel">
          <div class="row g-4">
            <div class="col-lg-6">
              <h6 class="fw-bold mb-3 text-dark border-bottom pb-2"><i class="fa-solid fa-passport text-primary me-2"></i> Visa Specification &amp; Policy</h6>
              <div class="row g-2 small">
                <div class="col-sm-5 text-muted">Destination:</div>
                <div class="col-sm-7 fw-bold text-dark"><?= $app['flag_emoji'] ?> <?= e($app['country_name']) ?> (<?= e($app['country_code']) ?>)</div>
                <div class="col-sm-5 text-muted">Service Tier:</div>
                <div class="col-sm-7 fw-semibold text-primary"><?= e($app['service_name']) ?></div>
                <div class="col-sm-5 text-muted">Entry Type:</div>
                <div class="col-sm-7"><?= e($app['entry_type']) ?></div>
                <div class="col-sm-5 text-muted">Processing Type:</div>
                <div class="col-sm-7"><?= e($app['processing_type']) ?></div>
                <div class="col-sm-5 text-muted">Duration / Validity:</div>
                <div class="col-sm-7"><?= e($app['duration'] ?? '30 Days') ?> (Max Stay: <?= e($app['max_stay'] ?? '30 Days') ?>)</div>
                <div class="col-sm-5 text-muted">Estimated SLA:</div>
                <div class="col-sm-7"><?= (int)($app['estimated_days'] ?? 7) ?> Working Days</div>
              </div>
            </div>
            <div class="col-lg-6">
              <h6 class="fw-bold mb-3 text-dark border-bottom pb-2"><i class="fa-solid fa-calendar-days text-success me-2"></i> Travel &amp; Consular Timeline</h6>
              <div class="row g-2 small">
                <div class="col-sm-5 text-muted">Intended Travel:</div>
                <div class="col-sm-7 fw-semibold"><?= format_date($app['travel_date']) ?></div>
                <div class="col-sm-5 text-muted">Expected Return:</div>
                <div class="col-sm-7 fw-semibold"><?= format_date($app['return_date']) ?></div>
                <div class="col-sm-5 text-muted">Submitted Date:</div>
                <div class="col-sm-7"><?= format_date($app['application_date'] ?? $app['created_at']) ?></div>
                <div class="col-sm-5 text-muted">Consular Reference:</div>
                <div class="col-sm-7 font-monospace fw-bold"><?= e($app['embassy_reference'] ?? 'Pending Submission') ?></div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 10: Status History -->
        <div class="tab-pane fade" id="history-pane" role="tabpanel">
          <h6 class="fw-bold mb-3 text-dark border-bottom pb-2"><i class="fa-solid fa-clock-rotate-left text-info me-2"></i> Immutable Lifecycle Stage Transition Audit</h6>
          <?php if (empty($stageHistory)): ?>
            <div class="p-4 text-center text-muted bg-light rounded border">No stage transitions logged yet.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle small mb-0">
                <thead class="table-light">
                  <tr>
                    <th>From Stage</th>
                    <th>To Stage</th>
                    <th>Status</th>
                    <th>Comments / Notes</th>
                    <th>Changed By</th>
                    <th>Timestamp</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($stageHistory as $sh): ?>
                    <tr>
                      <td class="text-muted"><?= e($sh['from_stage'] ?: 'Application Registered') ?></td>
                      <td class="fw-bold text-primary"><?= e($sh['to_stage']) ?></td>
                      <td><span class="badge bg-light text-dark border"><?= e($sh['status'] ?? '—') ?></span></td>
                      <td class="text-muted"><?= e($sh['comments'] ?? '—') ?></td>
                      <td><?= e($sh['changed_by_name'] ?? 'System') ?></td>
                      <td class="text-muted"><?= format_datetime($sh['created_at']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

          <?php if (!empty($assignmentHistory)): ?>
            <h6 class="fw-bold mt-4 mb-3 text-dark border-bottom pb-2"><i class="fa-solid fa-users-gear text-primary me-2"></i> Case Officer Assignment History</h6>
            <div class="table-responsive">
              <table class="table table-sm align-middle small mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Officer</th>
                    <th>Assigned By</th>
                    <th>Notes</th>
                    <th>Assigned At</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($assignmentHistory as $ah): ?>
                    <tr>
                      <td class="fw-bold text-dark"><?= e($ah['staff_name']) ?> <span class="badge bg-light text-secondary"><?= e($ah['staff_role'] ?? 'Officer') ?></span></td>
                      <td><?= e($ah['assigned_by_name'] ?? 'System') ?></td>
                      <td><?= e($ah['notes'] ?? '—') ?></td>
                      <td><?= format_datetime($ah['assigned_at']) ?></td>
                      <td>
                        <span class="badge <?= $ah['is_current'] ? 'bg-success' : 'bg-secondary' ?>">
                          <?= $ah['is_current'] ? 'Active Assignee' : 'Past Assignee' ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <!-- TAB 12: Activity Log -->
        <div class="tab-pane fade" id="activity-pane" role="tabpanel">
          <h6 class="fw-bold mb-3 text-dark border-bottom pb-2"><i class="fa-solid fa-shield-halved text-danger me-2"></i> System Activity &amp; Audit Trail</h6>
          <?php if (empty($activityLogs)): ?>
            <div class="p-4 text-center text-muted bg-light rounded border">No audit logs recorded for this record.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle small mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Description</th>
                    <th>User</th>
                    <th>Timestamp</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($activityLogs as $al): ?>
                    <tr>
                      <td><span class="badge bg-dark"><?= e($al['action']) ?></span></td>
                      <td class="fw-semibold text-primary"><?= e($al['module']) ?></td>
                      <td><?= e($al['description']) ?></td>
                      <td><?= e($al['user_name'] ?? 'System') ?></td>
                      <td class="text-muted"><?= format_datetime($al['created_at']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ==========================================================================
     ACTION MODALS
     ========================================================================== -->

<!-- 1. Stage Transition Modal -->
<div class="modal fade" id="stageTransitionModal" tabindex="-1" aria-labelledby="stageTransitionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold fs-6" id="stageTransitionModalLabel"><i class="fa-solid fa-forward-step me-2"></i> Advance Visa Lifecycle Stage</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/applications/update-stage" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

        <div class="modal-body p-4">
          <div class="alert alert-info py-2 small mb-3">
            Current Stage: <strong><?= e($app['current_stage']) ?></strong> (<?= e($app['status']) ?>)
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Select Target Stage <span class="text-danger">*</span></label>
            <select name="new_stage" class="form-select" required>
              <?php foreach ($lifecycleStages as $ls): ?>
                <option value="<?= e($ls) ?>" <?= $ls === $app['current_stage'] ? 'selected' : '' ?>>
                  <?= e($ls) ?>
                </option>
              <?php endforeach; ?>
              <option value="Returned / Modification Required">Returned / Modification Required</option>
              <option value="Medical / Biometrics Processing">Medical / Biometrics Processing</option>
              <option value="Visa Issued & Completed">Visa Issued & Completed</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Application Status</label>
            <select name="new_status" class="form-select">
              <option value="In Process" <?= $app['status'] === 'In Process' ? 'selected' : '' ?>>In Process</option>
              <option value="Documents Under Verification" <?= $app['status'] === 'Documents Under Verification' ? 'selected' : '' ?>>Documents Under Verification</option>
              <option value="Submitted" <?= $app['status'] === 'Submitted' ? 'selected' : '' ?>>Submitted</option>
              <option value="Action Required" <?= $app['status'] === 'Action Required' ? 'selected' : '' ?>>Action Required</option>
              <option value="Approved" <?= $app['status'] === 'Approved' ? 'selected' : '' ?>>Approved</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Next Action to Schedule</label>
            <input type="text" name="next_action" class="form-control form-control-sm" placeholder="e.g. Schedule biometrics appointment at VFS">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Next Action Due Date</label>
            <input type="date" name="next_action_due_date" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Stage Transition Notes</label>
            <textarea name="comments" class="form-control" rows="2" placeholder="Optional notes for stage history log..."></textarea>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold"><i class="fa-solid fa-check me-1"></i> Confirm Stage Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 2. Approve Visa Modal -->
<div class="modal fade" id="approveVisaModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title fw-bold fs-6"><i class="fa-solid fa-circle-check me-2"></i>Official Visa Grant &amp; Approval</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/applications/decision/approve" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

        <div class="modal-body p-4">
          <div class="alert alert-success py-2 small mb-3">
            <i class="fa-solid fa-info-circle me-1"></i> Granting this visa will update status to <strong>Approved</strong> and enable visa download on the customer portal.
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Visa / Sticker / eVisa Number <span class="text-danger">*</span></label>
              <input type="text" name="visa_number" class="form-control fw-bold text-success" placeholder="e.g. 2026/V/098762" required>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Issue Date <span class="text-danger">*</span></label>
              <input type="date" name="issue_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Expiry Date <span class="text-danger">*</span></label>
              <input type="date" name="expiry_date" class="form-control" value="<?= date('Y-m-d', strtotime('+60 days')) ?>" required>
            </div>

            <div class="col-md-4">
              <label class="form-label small fw-semibold">Entry Before Date (Optional)</label>
              <input type="date" name="entry_before_date" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Maximum Stay</label>
              <input type="text" name="maximum_stay" class="form-control" value="30 Days" placeholder="e.g. 30 Days">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Visa Validity</label>
              <input type="text" name="validity" class="form-control" value="60 Days" placeholder="e.g. 60 Days from issue">
            </div>

            <div class="col-12">
              <label class="form-label small fw-semibold">Upload Approved Visa Document (PDF, JPG, PNG)</label>
              <input type="file" name="visa_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
            </div>

            <div class="col-12">
              <label class="form-label small fw-semibold">Approval Notes &amp; Traveler Guidelines</label>
              <textarea name="approval_notes" class="form-control" rows="2" placeholder="e.g. Valid for single entry tourism. Must carry return ticket."></textarea>
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success fw-semibold"><i class="fa-solid fa-circle-check me-1"></i>Issue Official Approval</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 2B. Reject Application Modal -->
<div class="modal fade" id="rejectVisaModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title fw-bold fs-6"><i class="fa-solid fa-circle-xmark me-2"></i>Record Visa Rejection</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/applications/decision/reject" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Rejection Date <span class="text-danger">*</span></label>
            <input type="date" name="rejection_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Customer-Facing Reason <span class="text-danger">*</span></label>
            <textarea name="customer_reason" class="form-control" rows="3" required placeholder="Visible to client: Official refusal reason provided by consular authority..."></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Confidential Internal Notes</label>
            <textarea name="internal_reason" class="form-control" rows="2" placeholder="Private internal notes (NEVER visible to client)..."></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Reapplication Eligibility</label>
            <select name="reapplication_eligibility" class="form-select">
              <option value="Eligible to Reapply Immediately">Eligible to Reapply Immediately</option>
              <option value="Eligible after 30 Days">Eligible after 30 Days</option>
              <option value="Eligible after 6 Months">Eligible after 6 Months</option>
              <option value="Not Eligible / Permanent Inadmissibility">Not Eligible / Permanent Inadmissibility</option>
            </select>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Attach Consular Rejection Letter (Optional)</label>
            <input type="file" name="rejection_file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger fw-semibold"><i class="fa-solid fa-ban me-1"></i>Confirm Rejection</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 2C. Return for Modification Modal -->
<div class="modal fade" id="returnVisaModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-warning bg-opacity-10 border-bottom">
        <h5 class="modal-title fw-bold fs-6 text-dark"><i class="fa-solid fa-rotate-left text-warning me-2"></i>Return for Applicant Modification</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/applications/decision/return" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Reason for Return <span class="text-danger">*</span></label>
            <input type="text" name="return_reason" class="form-control" placeholder="e.g. Passport scan blurry or missing employment NOC" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Detailed Required Corrections</label>
            <textarea name="required_changes" class="form-control" rows="3" placeholder="Instruct the client on exactly what needs to be changed or resubmitted..."></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Resubmission Deadline</label>
            <input type="date" name="deadline" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Staff Comment</label>
            <textarea name="staff_comment" class="form-control" rows="2" placeholder="Internal workflow comment..."></textarea>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning fw-semibold"><i class="fa-solid fa-rotate-left me-1"></i>Issue Return Notice</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 2D. Request Additional Document Modal -->
<div class="modal fade" id="requestDocModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold fs-6"><i class="fa-solid fa-file-circle-question me-2"></i>Request Document from Applicant</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/applications/document-request" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Select Document Requirement <span class="text-danger">*</span></label>
            <select name="document_type_id" class="form-select" required>
              <?php foreach ($documentChecklist as $dc): ?>
                <option value="<?= $dc['document_type_id'] ?>"><?= e($dc['type_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Due Date <span class="text-danger">*</span></label>
            <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+5 days')) ?>" required>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Special Instructions for Client</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Specify format, resolution or notary requirements..."></textarea>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold"><i class="fa-solid fa-paper-plane me-1"></i>Dispatch Request</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 2E. Log Client Communication Modal -->
<div class="modal fade" id="addCommModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-info text-dark">
        <h5 class="modal-title fw-bold fs-6"><i class="fa-solid fa-phone me-2"></i>Log Communication with Client</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/applications/communication" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

        <div class="modal-body p-4">
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Channel</label>
              <select name="channel" class="form-select">
                <option value="Phone Call">Phone Call</option>
                <option value="WhatsApp">WhatsApp</option>
                <option value="Email">Email</option>
                <option value="SMS">SMS</option>
                <option value="Office Visit">Office Visit</option>
                <option value="Consular Follow-up">Consular Follow-up</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Direction</label>
              <select name="direction" class="form-select">
                <option value="Outbound">Outbound (We called/messaged)</option>
                <option value="Inbound">Inbound (Client contacted us)</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Subject / Topic</label>
            <input type="text" name="subject" class="form-control" placeholder="e.g. Document clarification / Payment reminder">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Contact Person</label>
            <input type="text" name="contact_person" class="form-control" value="<?= e($app['customer_name']) ?>" placeholder="Name of person spoken to">
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Summary of Discussion <span class="text-danger">*</span></label>
            <textarea name="message" class="form-control" rows="3" required placeholder="Details of conversation and agreed next steps..."></textarea>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-info fw-semibold"><i class="fa-solid fa-save me-1"></i>Save Communication</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 3. Staff Reassignment Modal -->
<div class="modal fade" id="reassignStaffModal" tabindex="-1" aria-labelledby="reassignStaffModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold fs-6" id="reassignStaffModalLabel"><i class="fa-solid fa-user-gear text-primary me-2"></i> Reassign Case Worker</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/applications/assign" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Select Staff Member <span class="text-danger">*</span></label>
            <select name="staff_id" class="form-select" required>
              <?php foreach ($allStaff as $stf): ?>
                <option value="<?= $stf['id'] ?>" <?= ((int)($app['assigned_staff_id'] ?? 0)) === (int)$stf['id'] ? 'selected' : '' ?>>
                  <?= e($stf['name']) ?> (<?= e($stf['designation'] ?? 'Staff') ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Assignment Notes</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Reason for case reassignment..."></textarea>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold"><i class="fa-solid fa-save me-1"></i> Confirm Reassignment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 4. Priority Modal -->
<div class="modal fade" id="priorityModal" tabindex="-1" aria-labelledby="priorityModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold fs-6" id="priorityModalLabel"><i class="fa-solid fa-flag text-warning me-2"></i> Change Case Priority</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/applications/priority" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

        <div class="modal-body p-4">
          <label class="form-label small fw-semibold text-secondary">Priority Level</label>
          <select name="priority" class="form-select" required>
            <option value="Normal" <?= $app['priority'] === 'Normal' ? 'selected' : '' ?>>Normal</option>
            <option value="High" <?= $app['priority'] === 'High' ? 'selected' : '' ?>>High</option>
            <option value="Urgent" <?= $app['priority'] === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
            <option value="Critical" <?= $app['priority'] === 'Critical' ? 'selected' : '' ?>>Critical</option>
          </select>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold">Update Priority</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 5. Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold fs-6" id="addNoteModalLabel"><i class="fa-solid fa-note-sticky text-info me-2"></i> Append Internal Note</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/applications/note" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

        <div class="modal-body p-4">
          <label class="form-label small fw-semibold text-secondary">Confidential Note</label>
          <textarea name="note" class="form-control" rows="4" required placeholder="Type confidential operational note..."></textarea>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold"><i class="fa-solid fa-save me-1"></i> Save Note</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 6. Health Diagnosis Modal -->
<div class="modal fade" id="healthModal" tabindex="-1" aria-labelledby="healthModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold fs-6" id="healthModalLabel"><i class="fa-solid fa-heart-pulse text-danger me-2"></i> Workflow Health Diagnosis</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="d-flex align-items-center justify-content-between p-3 rounded bg-light mb-3">
          <div>
            <div class="fw-bold fs-5 <?= e($healthClass) ?>">Health Score: <?= (int)$healthDiagnosis['score'] ?>%</div>
            <div class="text-muted small">Status: <strong><?= e($healthDiagnosis['status']) ?></strong></div>
          </div>
          <div class="rounded-circle p-2 bg-white border shadow-sm">
            <i class="fa-solid fa-heart-pulse fs-3 <?= e($healthClass) ?>"></i>
          </div>
        </div>

        <h6 class="fw-bold small text-uppercase text-secondary mb-2">Diagnostic Factors Evaluated:</h6>
        <ul class="list-group list-group-flush small border rounded">
          <?php foreach ($healthDiagnosis['reasons'] as $rsn): ?>
            <li class="list-group-item d-flex align-items-start gap-2">
              <i class="fa-solid <?= $healthDiagnosis['score'] >= 80 ? 'fa-check text-success' : 'fa-circle-exclamation text-warning' ?> mt-1"></i>
              <span><?= e($rsn) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>

        <div class="text-muted small mt-3" style="font-size: 0.72rem;">
          <i class="fa-solid fa-circle-info me-1"></i> Note: Health score is an internal workflow efficiency diagnostic and does not reflect official government decision probability.
        </div>
      </div>
      <div class="modal-footer bg-light border-top">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- 7. Refund Modal -->
<div class="modal fade" id="refundModal" tabindex="-1" aria-labelledby="refundModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-warning bg-opacity-10 border-bottom">
        <h5 class="modal-title fw-bold fs-6" id="refundModalLabel"><i class="fa-solid fa-rotate-left text-warning me-2"></i> Process Customer Refund</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/payments/refund" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
        <div class="modal-body p-4">
          <div class="alert alert-warning py-2 small mb-3">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>
            Total Paid: <strong><?= format_currency($app['paid_amount'] ?? 0) ?></strong> — Refund amount cannot exceed this.
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Refund Amount (USD) <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input type="number" name="amount" class="form-control" step="0.01" min="0.01" 
                     max="<?= number_format((float)($app['paid_amount'] ?? 0), 2, '.', '') ?>"
                     placeholder="0.00" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Refund Method <span class="text-danger">*</span></label>
            <select name="payment_method" class="form-select" required>
              <option value="Bank Transfer">Bank Transfer</option>
              <option value="Cash">Cash</option>
              <option value="Credit Card">Credit Card</option>
              <option value="Cheque">Cheque</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Transaction Reference</label>
            <input type="text" name="transaction_reference" class="form-control" placeholder="Bank ref / transfer ID...">
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Reason for Refund <span class="text-danger">*</span></label>
            <textarea name="reason" class="form-control" rows="3" required placeholder="Mandatory: explain reason for this refund..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning fw-semibold"><i class="fa-solid fa-rotate-left me-1"></i>Process Refund</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 8. Interactive Stage Detail Modal -->
<div class="modal fade" id="stageDetailModal" tabindex="-1" aria-labelledby="stageDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold fs-6" id="stageDetailModalLabel"><i class="fa-solid fa-route text-primary me-2"></i> Stage Inspection</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="mb-3">
          <div class="text-muted small">Lifecycle Stage Name:</div>
          <h5 class="fw-bold text-dark mb-0" id="modalStageName">—</h5>
        </div>

        <div class="row g-2 small mb-3">
          <div class="col-5 text-muted">Status:</div>
          <div class="col-7 fw-bold" id="modalStageState">—</div>

          <div class="col-5 text-muted">Responsible Officer:</div>
          <div class="col-7 fw-semibold" id="modalStageOfficer">—</div>

          <div class="col-5 text-muted">Execution Date:</div>
          <div class="col-7" id="modalStageDate">—</div>
        </div>

        <div class="mb-0">
          <div class="text-muted small mb-1">Operational Comments:</div>
          <div class="p-2 rounded bg-light border small" id="modalStageComments">—</div>
        </div>
      </div>
      <div class="modal-footer bg-light border-top">
        <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#stageTransitionModal">
          <i class="fa-solid fa-forward-step me-1"></i> Change / Advance Stage
        </button>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- 8. Application Document Upload Modal -->
<div class="modal fade" id="appDocUploadModal" tabindex="-1" aria-labelledby="appDocUploadModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold fs-6" id="appDocUploadModalLabel"><i class="fa-solid fa-cloud-arrow-up me-2"></i> Upload Document</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/documents/upload" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="application_id" id="appDocAppId" value="<?= $app['id'] ?>">
        <input type="hidden" name="document_type_id" id="appDocTypeId" value="">

        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Document Requirement</label>
            <input type="text" id="appDocTypeNameDisplay" class="form-control bg-light" readonly>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Select File (PDF, JPG, PNG, DOCX) <span class="text-danger">*</span></label>
            <input type="file" name="document_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.docx" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Document Expiry Date <small class="text-muted">(Optional)</small></label>
            <input type="date" name="expiry_date" class="form-control form-control-sm">
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Operational Remarks</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold"><i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload File</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 9. Application Document Reject Modal -->
<div class="modal fade" id="appDocRejectModal" tabindex="-1" aria-labelledby="appDocRejectModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title fw-bold fs-6" id="appDocRejectModalLabel"><i class="fa-solid fa-circle-xmark me-2"></i> Reject Document &amp; Request Replacement</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/documents/reject" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="document_id" id="appDocRejectDocId" value="">

        <div class="modal-body p-4">
          <div class="alert alert-warning py-2 small mb-3">
            Rejecting <strong id="appDocRejectDocName">this document</strong> will set this application to <strong>Action Required</strong>.
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Rejection Reason <span class="text-danger">*</span></label>
            <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Mandatory rejection reason (e.g. Scanned image is truncated or blurry)..."></textarea>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger fw-semibold"><i class="fa-solid fa-ban me-1"></i> Confirm Rejection</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 10. Application Document Replace Modal -->
<div class="modal fade" id="appDocReplaceModal" tabindex="-1" aria-labelledby="appDocReplaceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold fs-6" id="appDocReplaceModalLabel"><i class="fa-solid fa-cloud-arrow-up me-2"></i> Upload Replacement</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/documents/replace" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="document_id" id="appDocReplaceDocId" value="">

        <div class="modal-body p-4">
          <p class="small text-muted mb-3">Uploading a new version for <strong id="appDocReplaceDocName">this document</strong> will preserve the rejected file in version history.</p>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Select New File (PDF, JPG, PNG) <span class="text-danger">*</span></label>
            <input type="file" name="document_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.docx" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Expiry Date <small class="text-muted">(If applicable)</small></label>
            <input type="date" name="expiry_date" class="form-control form-control-sm">
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold"><i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload Replacement</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function toggleDecisionFields() {
  const dec = document.getElementById('decisionSelect').value;
  const appFields = document.getElementById('approvedFields');
  const rejFields = document.getElementById('rejectedFields');
  const rejInput = document.getElementById('rejectionReasonInput');

  if (dec === 'Approved') {
    appFields.classList.remove('d-none');
    rejFields.classList.add('d-none');
    rejInput.removeAttribute('required');
  } else {
    appFields.classList.add('d-none');
    rejFields.classList.remove('d-none');
    rejInput.setAttribute('required', 'required');
  }
}

function showStageDetails(name, state, officer, date, comments) {
  document.getElementById('modalStageName').innerText = name;
  document.getElementById('modalStageState').innerText = state;
  document.getElementById('modalStageOfficer').innerText = officer || 'Unassigned';
  document.getElementById('modalStageDate').innerText = date || 'Pending';
  document.getElementById('modalStageComments').innerText = comments || 'No specific notes recorded for this stage.';

  const modal = new bootstrap.Modal(document.getElementById('stageDetailModal'));
  modal.show();
}

function openAppDocUploadModal(appId, typeId, typeName) {
  document.getElementById('appDocAppId').value = appId;
  document.getElementById('appDocTypeId').value = typeId;
  document.getElementById('appDocTypeNameDisplay').value = typeName;
  const modal = new bootstrap.Modal(document.getElementById('appDocUploadModal'));
  modal.show();
}

function openAppDocRejectModal(docId, docName) {
  document.getElementById('appDocRejectDocId').value = docId;
  document.getElementById('appDocRejectDocName').innerText = docName;
  const modal = new bootstrap.Modal(document.getElementById('appDocRejectModal'));
  modal.show();
}

function openAppDocReplaceModal(docId, docName) {
  document.getElementById('appDocReplaceDocId').value = docId;
  document.getElementById('appDocReplaceDocName').innerText = docName;
  const modal = new bootstrap.Modal(document.getElementById('appDocReplaceModal'));
  modal.show();
}
</script>

<!-- Generate Payment Link Modal -->
<div class="modal fade" id="generateLinkModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow">
      <form action="/payments/generate-link" method="POST">
        <?= \App\Middleware\CsrfMiddleware::field() ?>
        <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title fw-bold"><i class="fa-solid fa-link me-2"></i>Generate Online Payment Link</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Customer / Applicant</label>
            <input type="text" class="form-control bg-light" value="<?= e($app['customer_name']) ?> (<?= e($app['customer_code']) ?>)" readonly>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-8">
              <label class="form-label small fw-semibold">Payment Amount <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" step="0.01" min="1" name="amount" class="form-control fw-bold" value="<?= (float)($app['balance_amount'] > 0 ? $app['balance_amount'] : $app['total_amount']) ?>" required>
              </div>
            </div>
            <div class="col-4">
              <label class="form-label small fw-semibold">Currency</label>
              <input type="text" name="currency" class="form-control bg-light" value="USD" readonly>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Payment Description / Title</label>
            <input type="text" name="title" class="form-control" value="Visa Fee for <?= e($app['customer_name']) ?> — <?= e($app['service_name']) ?>" placeholder="Title...">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Due Date / Expiry</label>
            <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Optional Notes / Instructions</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Special instructions for customer..."></textarea>
          </div>
          <div class="p-3 bg-light rounded border mb-3">
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" name="send_email" value="1" id="sendEmailCheck" checked>
              <label class="form-check-label small fw-semibold" for="sendEmailCheck">
                <i class="fa-solid fa-envelope text-primary me-1"></i> Send payment link immediately via Email
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="send_whatsapp" value="1" id="sendWhatsappCheck" checked>
              <label class="form-check-label small fw-semibold" for="sendWhatsappCheck">
                <i class="fa-brands fa-whatsapp text-success me-1"></i> Send payment request via WhatsApp
              </label>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold">
            <i class="fa-solid fa-bolt me-1"></i> Generate &amp; Dispatch Link
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>

