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

<link rel="stylesheet" href="/assets/css/dashboard-bento.css?v=2.5">
<link rel="stylesheet" href="/assets/css/pages/documents.css?v=2.5">

<?php
$statusBadgeClass = 'bg-secondary';
if ($app['status'] === 'Approved') $statusBadgeClass = 'bg-success';
elseif ($app['status'] === 'Pending') $statusBadgeClass = 'bg-warning text-dark';
elseif ($app['status'] === 'Rejected') $statusBadgeClass = 'bg-danger';
elseif ($app['status'] === 'In Process') $statusBadgeClass = 'bg-primary';
elseif ($app['status'] === 'Action Required') $statusBadgeClass = 'bg-danger text-white';
elseif ($app['status'] === 'Draft') $statusBadgeClass = 'bg-info text-dark';

$priorityBadgeClass = 'bg-secondary text-white';
if ($app['priority'] === 'Urgent') $priorityBadgeClass = 'bg-warning text-dark fw-bold';
elseif ($app['priority'] === 'Critical') $priorityBadgeClass = 'bg-danger text-white fw-bold';
elseif ($app['priority'] === 'High') $priorityBadgeClass = 'bg-primary text-white';
?>

<div class="content-body">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 rounded-4 shadow-xs" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation text-danger' : ($flash['type'] === 'success' ? 'fa-circle-check text-success' : 'fa-circle-info text-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- 1. Top Master Breadcrumb & Action Header (Executive Bento Header) -->
  <div class="master-workspace-header">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="d-flex align-items-center gap-3">
        <a href="/applications" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-xs bg-white" title="Back to Applications Directory">
          <i class="fa-solid fa-arrow-left me-1"></i> Applications
        </a>
        <div>
          <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
            <h4 class="fw-bold mb-0 text-primary font-monospace" style="letter-spacing: -0.02em; font-size: 1.35rem;"><?= e($app['application_number']) ?></h4>
            <button class="btn btn-light btn-sm p-1 px-2 border rounded-pill text-muted shadow-2xs" onclick="navigator.clipboard.writeText('<?= e($app['application_number']) ?>'); showToast('Application ID copied!', 'info');" title="Copy ID">
              <i class="fa-regular fa-copy"></i>
            </button>
            <span class="fs-5"><?= $app['flag_emoji'] ?></span>
            <span class="badge rounded-pill px-2.5 py-1 text-white fw-bold <?= $statusBadgeClass ?>" style="font-size: 0.72rem;">
              <?= strtoupper(e($app['status'])) ?>
            </span>
            <span class="badge rounded-pill px-2.5 py-1 <?= $priorityBadgeClass ?>" style="font-size: 0.72rem;">
              <?= strtoupper(e($app['priority'])) ?> PRIORITY
            </span>
            <button type="button" class="btn btn-light btn-sm health-meter rounded-pill <?= $healthClass ?> py-1 px-2.5 shadow-2xs fw-bold" data-bs-toggle="modal" data-bs-target="#healthModal" onclick="openModalById('healthModal')" title="Click to view health diagnosis" style="font-size: 0.72rem;">
              <i class="fa-solid fa-heart-pulse me-1"></i> Health: <?= (int)$app['calculated_health'] ?>%
            </button>
          </div>
          <div class="text-muted small d-flex flex-wrap align-items-center gap-2">
            <a href="/customers/show?id=<?= $app['customer_id'] ?>" class="fw-bold text-dark text-decoration-none">
              <i class="fa-solid fa-user me-1 text-secondary"></i><?= e($app['customer_name']) ?>
            </a>
            <span class="badge bg-light text-dark border font-monospace px-2 py-0.5">@<?= e($app['customer_code']) ?></span>
            <span>&bull;</span>
            <span class="fw-medium text-dark"><i class="fa-solid fa-passport me-1 text-secondary"></i><?= e($app['service_name']) ?></span>
            <span>&bull;</span>
            <span>Passport: <strong class="font-monospace text-primary"><?= e($app['passport_number'] ?: $app['current_passport'] ?: '—') ?></strong></span>
          </div>
        </div>
      </div>

      <!-- Master Operational Action Buttons -->
      <div class="master-action-btn-group">
        <!-- Edit Application Button -->
        <a href="/applications/edit?id=<?= $app['id'] ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-xs bg-white">
          <i class="fa-solid fa-pen-to-square me-1"></i> Edit Application
        </a>

        <!-- Update Stage Button -->
        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-xs text-white fw-semibold" style="background: linear-gradient(135deg, #E11D48, #BE123C); border: none;" onclick="openModalById('stageTransitionModal', event)">
          <i class="fa-solid fa-forward-step me-1"></i> Update Stage
        </button>

        <!-- Approve Visa Button -->
        <button type="button" class="btn btn-success btn-sm rounded-pill px-3 shadow-xs text-white fw-semibold" onclick="openModalById('approveVisaModal', event)">
          <i class="fa-solid fa-circle-check me-1"></i> Approve Visa
        </button>

        <!-- Decisions Dropdown -->
        <div class="dropdown">
          <button class="btn btn-outline-dark btn-sm rounded-pill px-3 shadow-xs dropdown-toggle bg-white" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-solid fa-gavel me-1"></i> Decisions
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3" style="font-size: 0.85rem; z-index: 1070;">
            <li><button type="button" class="dropdown-item py-2 text-success fw-semibold" onclick="openModalById('approveVisaModal', event)"><i class="fa-solid fa-circle-check me-2"></i> Approve (Grant Visa)</button></li>
            <li><button type="button" class="dropdown-item py-2 text-warning fw-semibold" onclick="openModalById('returnVisaModal', event)"><i class="fa-solid fa-rotate-left me-2"></i> Return for Modifications</button></li>
            <li><button type="button" class="dropdown-item py-2 text-danger fw-semibold" onclick="openModalById('rejectVisaModal', event)"><i class="fa-solid fa-circle-xmark me-2"></i> Reject Application</button></li>
            <li><hr class="dropdown-divider my-1"></li>
            <li><button type="button" class="dropdown-item py-2" onclick="openModalById('requestDocModal', event)"><i class="fa-solid fa-file-circle-question text-primary me-2"></i> Request Additional Document</button></li>
            <li><button type="button" class="dropdown-item py-2" onclick="openModalById('addCommModal', event)"><i class="fa-solid fa-phone text-info me-2"></i> Log Client Communication</button></li>
          </ul>
        </div>

        <!-- Quick Actions Dropdown -->
        <div class="dropdown">
          <button class="btn btn-outline-secondary btn-sm rounded-pill px-2.5 shadow-xs dropdown-toggle bg-white" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-solid fa-ellipsis-vertical"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3" style="font-size: 0.85rem; z-index: 1070;">
            <li><a class="dropdown-item py-2" href="/applications/edit?id=<?= $app['id'] ?>"><i class="fa-solid fa-pen-to-square text-primary me-2"></i> Edit Details</a></li>
            <li><button type="button" class="dropdown-item py-2" onclick="openModalById('reassignStaffModal', event)"><i class="fa-solid fa-user-gear text-info me-2"></i> Reassign Staff</button></li>
            <li><button type="button" class="dropdown-item py-2" onclick="openModalById('priorityModal', event)"><i class="fa-solid fa-flag text-warning me-2"></i> Change Priority</button></li>
            <li><button type="button" class="dropdown-item py-2" onclick="openModalById('addNoteModal', event)"><i class="fa-solid fa-note-sticky text-secondary me-2"></i> Add Internal Note</button></li>
            <li><hr class="dropdown-divider my-1"></li>
            <li><a class="dropdown-item py-2" href="/payments/invoice?app_id=<?= $app['id'] ?>" target="_blank"><i class="fa-solid fa-file-invoice text-success me-2"></i> Print Invoice</a></li>
            <li>
              <form action="/applications/archive" method="POST" onsubmit="return confirm('Archive this visa application record?');">
                <?= csrf_field() ?>
                <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                <button type="submit" class="dropdown-item py-2 text-warning"><i class="fa-solid fa-box-archive text-warning me-2"></i> Archive Application</button>
              </form>
            </li>
            <li>
              <form action="/applications/delete" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this application (<?= e($app['application_number']) ?>) and all related records?');">
                <?= csrf_field() ?>
                <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                <button type="submit" class="dropdown-item py-2 text-danger"><i class="fa-solid fa-trash-can text-danger me-2"></i> Delete Application</button>
              </form>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. PRIMARY OPERATIONAL BANNERS: Current Stage & Next Action (Bento Cards) -->
  <div class="row g-3 mb-4">
    <!-- Current Stage Bento Banner -->
    <div class="col-12 col-lg-6">
      <div class="master-banner-card banner-stage">
        <div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small fw-bold text-uppercase text-danger d-flex align-items-center gap-1.5" style="font-size: 0.72rem; letter-spacing: 0.05em;">
              <i class="fa-solid fa-circle-dot"></i> Current Lifecycle Stage
            </span>
            <span class="badge <?= $statusBadgeClass ?> px-2.5 py-1 rounded-pill" style="font-size: 0.7rem;">
              <?= e($app['status']) ?>
            </span>
          </div>
          <h4 class="fw-bold mb-2 text-dark" style="letter-spacing: -0.01em;"><?= e($app['current_stage']) ?></h4>
          <div class="text-muted small mb-3 d-flex align-items-center flex-wrap gap-2">
            <span>Officer: <strong><?= e($app['staff_name'] ?? 'Unassigned') ?></strong></span>
            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill py-0 px-2 fw-semibold" style="font-size: 0.72rem;" onclick="openModalById('reassignStaffModal', event)" title="Reassign to another staff member">
              <i class="fa-solid fa-user-pen me-1"></i> Reassign
            </button>
            <span>&bull;</span>
            <span>Updated: <?= format_datetime($app['updated_at'] ?? $app['created_at']) ?></span>
          </div>
        </div>
        <div class="p-2.5 bg-light rounded-3 border d-flex align-items-center justify-content-between flex-wrap gap-2 small">
          <span class="text-muted"><i class="fa-solid fa-clock me-1 text-primary"></i> <strong>Operational Deadline:</strong></span>
          <span class="badge rounded-pill px-2.5 py-1 fw-bold <?= e($deadlineClass) ?>"><?= e($deadlineStatus) ?> (<?= format_date($app['expected_completion_date']) ?>)</span>
        </div>
      </div>
    </div>

    <!-- Next Action Bento Banner -->
    <div class="col-12 col-lg-6">
      <div class="master-banner-card banner-action">
        <div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small fw-bold text-uppercase d-flex align-items-center gap-1.5" style="color: #D97706; font-size: 0.72rem; letter-spacing: 0.05em;">
              <i class="fa-solid fa-bolt"></i> Immediate Next Action
            </span>
            <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.7rem;">
              Due: <?= format_date($app['next_action_due_date'] ?? date('Y-m-d', strtotime('+3 days'))) ?>
            </span>
          </div>
          <h4 class="fw-bold mb-2 text-dark" style="letter-spacing: -0.01em;"><?= e($app['next_action'] ?? 'Review checklist documents and prepare for stage progression') ?></h4>
          <div class="text-muted small mb-3 d-flex align-items-center flex-wrap gap-2">
            <span>Responsible: <strong><?= e($app['staff_name'] ?? 'Operations Team') ?></strong></span>
            <span>&bull;</span>
            <span>Priority: <span class="badge bg-danger-subtle text-danger font-monospace px-2 py-0.5"><?= e($app['priority']) ?></span></span>
          </div>
        </div>
        <div class="p-2.5 bg-light rounded-3 border d-flex align-items-center justify-content-between flex-wrap gap-2">
          <span class="small text-muted"><i class="fa-solid fa-circle-info me-1 text-warning"></i> Fulfill pending checklist items to advance</span>
          <button type="button" class="btn btn-sm text-white rounded-pill px-3 py-1 fw-semibold shadow-xs" style="background: linear-gradient(135deg, #F59E0B, #D97706); border: none;" onclick="openModalById('stageTransitionModal', event)">
            <i class="fa-solid fa-circle-check me-1"></i> Advance Stage &rarr;
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- 3. VISA JOURNEY TIMELINE (BENTO TIMELINE CARD) -->
  <div class="master-timeline-card">
    <div class="master-timeline-header">
      <div>
        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
          <i class="fa-solid fa-route text-primary fs-5"></i> Visa Tracking Journey Timeline
        </h6>
        <div class="text-muted small mt-0.5" style="font-size: 0.75rem;">Click any stage node to inspect operational history, assigned case worker, and specific notes.</div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="small fw-bold text-dark font-monospace"><?= $completedCount ?> of <?= $totalStages ?> Stages (<?= $progressPercentage ?>%)</span>
        <div class="progress rounded-pill" style="width: 140px; height: 8px;">
          <div class="progress-bar rounded-pill" role="progressbar" style="background: var(--bento-primary, #E11D48); width: <?= $progressPercentage ?>%;" aria-valuenow="<?= $progressPercentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
      </div>
    </div>

    <div class="master-timeline-body">
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
            <div class="timeline-node shadow-xs">
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

  <!-- 4. TABBED OPERATIONAL SECTIONS -->
  <div class="master-tabs-nav-wrap mb-4">
    <div class="master-nav-tabs" role="tablist">
      <button class="master-nav-tab-btn active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#info-pane" type="button" role="tab">
        <i class="fa-solid fa-gauge-high text-danger"></i> Overview
      </button>
      <button class="master-nav-tab-btn" id="customer-tab" data-bs-toggle="tab" data-bs-target="#customer-pane" type="button" role="tab">
        <i class="fa-solid fa-user text-danger"></i> Customer
      </button>
      <button class="master-nav-tab-btn" id="visa-tab" data-bs-toggle="tab" data-bs-target="#visa-pane" type="button" role="tab">
        <i class="fa-solid fa-passport text-info"></i> Visa Details
      </button>
      <button class="master-nav-tab-btn" id="docs-tab" data-bs-toggle="tab" data-bs-target="#docs-pane" type="button" role="tab">
        <i class="fa-solid fa-file-circle-check text-success"></i> Documents (<?= count($documentChecklist) ?>)
      </button>
      <button class="master-nav-tab-btn" id="doc-req-tab" data-bs-toggle="tab" data-bs-target="#doc-req-pane" type="button" role="tab">
        <i class="fa-solid fa-file-circle-question text-warning"></i> Requests (<?= count($documentRequests) ?>)
      </button>
      <button class="master-nav-tab-btn" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments-pane" type="button" role="tab">
        <i class="fa-solid fa-credit-card text-success"></i> Payments (<?= count($appPayments) ?>)
      </button>
      <?php if (!empty($supplierDetails) || !empty($app['selling_price']) || user_can('finance.manage') || user_can('suppliers.view')): ?>
      <button class="master-nav-tab-btn" id="supplier-tab" data-bs-toggle="tab" data-bs-target="#supplier-pane" type="button" role="tab">
        <i class="fa-solid fa-building-flag text-primary"></i> Supplier &amp; Costs
      </button>
      <?php endif; ?>
      <button class="master-nav-tab-btn" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks-pane" type="button" role="tab">
        <i class="fa-solid fa-list-check text-warning"></i> Tasks (<?= count($tasks) ?>)
      </button>
      <button class="master-nav-tab-btn" id="appointments-tab" data-bs-toggle="tab" data-bs-target="#appointments-pane" type="button" role="tab">
        <i class="fa-solid fa-calendar-check text-primary"></i> Appointments (<?= count($appointments ?? []) ?>)
      </button>
      <button class="master-nav-tab-btn" id="notes-tab" data-bs-toggle="tab" data-bs-target="#notes-pane" type="button" role="tab">
        <i class="fa-solid fa-note-sticky text-secondary"></i> Internal Notes
      </button>
      <button class="master-nav-tab-btn" id="comm-tab" data-bs-toggle="tab" data-bs-target="#comm-pane" type="button" role="tab">
        <i class="fa-solid fa-comments text-info"></i> Communication (<?= count($communications) ?>)
      </button>
      <button class="master-nav-tab-btn" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-pane" type="button" role="tab">
        <i class="fa-solid fa-clock-rotate-left text-info"></i> Status History
      </button>
      <button class="master-nav-tab-btn" id="downloads-tab" data-bs-toggle="tab" data-bs-target="#decision-pane" type="button" role="tab">
        <i class="fa-solid fa-download text-dark"></i> Downloads
      </button>
      <button class="master-nav-tab-btn" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity-pane" type="button" role="tab">
        <i class="fa-solid fa-shield-halved text-danger"></i> Activity Log
      </button>
    </div>

    <div class="card-body p-3 p-md-4">
      <div class="tab-content" id="appDetailsTabContent">
        <!-- TAB 1: Overview (Executive Bento Cards) -->
        <div class="tab-pane fade show active" id="info-pane" role="tabpanel">
          <div class="row g-4">
            <!-- Bento Card 1: Application Specifics -->
            <div class="col-12 col-lg-6">
              <div class="master-data-card">
                <div class="master-data-card-header">
                  <div class="d-flex align-items-center gap-2">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(225, 29, 72, 0.1); color: #E11D48; display: flex; align-items: center; justify-content: center; font-size: 1.05rem;">
                      <i class="fa-solid fa-folder-open"></i>
                    </div>
                    <h6 class="fw-bold mb-0 text-dark">Application Specifics</h6>
                  </div>
                  <span class="badge bg-light text-primary border font-monospace px-2.5 py-1">
                    <?= e($app['application_number']) ?>
                  </span>
                </div>

                <div class="row g-2.5">
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Application Number</span>
                      <span class="master-data-val font-monospace text-primary fw-bold"><?= e($app['application_number']) ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Visa Service</span>
                      <span class="master-data-val text-dark"><?= e($app['service_name']) ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Destination Country</span>
                      <span class="master-data-val"><?= $app['flag_emoji'] ?> <?= e($app['country_name']) ?> (<?= e($app['country_code']) ?>)</span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Entry &amp; Processing</span>
                      <span class="master-data-val"><?= e($app['entry_type']) ?> &bull; <?= e($app['processing_type']) ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Application Date</span>
                      <span class="master-data-val"><?= format_date($app['application_date'] ?? $app['created_at']) ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Expected Completion</span>
                      <span class="master-data-val <?= e($deadlineClass) ?>"><?= format_date($app['expected_completion_date']) ?> (<?= e($deadlineStatus) ?>)</span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Travel &amp; Return Dates</span>
                      <span class="master-data-val"><?= format_date($app['travel_date']) ?> &rarr; <?= format_date($app['return_date']) ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Processing Branch</span>
                      <span class="master-data-val"><?= e($app['branch_name'] ?? 'Dubai Head Office') ?></span>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="master-data-item">
                      <span class="master-data-label">Created By</span>
                      <span class="master-data-val text-muted small"><?= e($app['created_by_name'] ?? 'Super Admin') ?> on <?= format_datetime($app['created_at']) ?></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Bento Card 2: Applicant Identity Summary -->
            <div class="col-12 col-lg-6">
              <div class="master-data-card">
                <div class="master-data-card-header">
                  <div class="d-flex align-items-center gap-2">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(16, 185, 129, 0.1); color: #10B981; display: flex; align-items: center; justify-content: center; font-size: 1.05rem;">
                      <i class="fa-solid fa-id-card"></i>
                    </div>
                    <h6 class="fw-bold mb-0 text-dark">Applicant Identity Summary</h6>
                  </div>
                  <a href="/customers/show?id=<?= $app['customer_id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill py-1 px-3 fw-semibold shadow-2xs" style="font-size: 0.75rem;">
                    View Full Profile &rarr;
                  </a>
                </div>

                <div class="row g-2.5">
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Full Legal Name</span>
                      <span class="master-data-val text-dark fw-bold"><?= e($app['customer_name']) ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Customer Code</span>
                      <span class="master-data-val font-monospace"><span class="badge bg-light text-dark border">@<?= e($app['customer_code']) ?></span></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Primary Passport</span>
                      <span class="master-data-val font-monospace text-primary fw-bold"><i class="fa-solid fa-passport me-1 text-muted"></i><?= e($app['passport_number'] ?: $app['current_passport'] ?: '—') ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Passport Expiry</span>
                      <span class="master-data-val font-monospace"><?= format_date($app['passport_expiry'] ?? $app['passport_expiry_date'] ?? null) ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Nationality &amp; Gender</span>
                      <span class="master-data-val"><?= e($app['customer_nationality']) ?> &bull; <?= e($app['customer_gender'] ?? '—') ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Date of Birth</span>
                      <span class="master-data-val"><?= format_date($app['customer_dob']) ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Mobile &amp; WhatsApp</span>
                      <div class="d-flex align-items-center gap-1.5 flex-wrap mt-0.5">
                        <a href="tel:<?= e($app['customer_mobile']) ?>" class="fw-semibold text-dark text-decoration-none font-monospace small"><?= e($app['customer_mobile']) ?></a>
                        <?php 
                          $cleanCustPhone = preg_replace('/[^0-9]/', '', $app['customer_whatsapp'] ?: $app['customer_mobile'] ?: '');
                          if ($cleanCustPhone && user_can('whatsapp.send')): 
                            $waApplicantMsg = "Hello " . ($app['customer_name'] ?? 'Applicant') . ", regarding your visa application " . ($app['application_number'] ?? '') . " with MS Travel Hub:";
                        ?>
                          <a href="https://api.whatsapp.com/send?phone=<?= $cleanCustPhone ?>&text=<?= urlencode($waApplicantMsg) ?>" 
                             target="_blank" class="badge rounded-pill bg-success-subtle text-success border border-success-subtle py-1 px-2.5 text-decoration-none fw-semibold" title="Chat directly on WhatsApp">
                            <i class="fa-brands fa-whatsapp me-1"></i> WhatsApp
                          </a>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Email Address</span>
                      <span class="master-data-val"><a href="mailto:<?= e($app['customer_email']) ?>" class="text-danger text-decoration-none small"><?= e($app['customer_email']) ?></a></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 2: Customer & Family Background -->
        <div class="tab-pane fade" id="customer-pane" role="tabpanel">
          <div class="row g-4">
            <!-- Family Details Bento Card -->
            <div class="col-12 col-lg-6">
              <div class="master-data-card">
                <div class="master-data-card-header">
                  <div class="d-flex align-items-center gap-2">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(225, 29, 72, 0.1); color: #E11D48; display: flex; align-items: center; justify-content: center; font-size: 1.05rem;">
                      <i class="fa-solid fa-people-roof"></i>
                    </div>
                    <h6 class="fw-bold mb-0 text-dark">Family &amp; Parental Background</h6>
                  </div>
                </div>

                <div class="row g-2.5">
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Father's Full Name</span>
                      <span class="master-data-val text-dark fw-semibold"><?= e($customerFamily['father_name'] ?? 'Not Recorded') ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Father's Date of Birth</span>
                      <span class="master-data-val"><?= format_date($customerFamily['father_dob'] ?? null) ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Father's Birth Country</span>
                      <span class="master-data-val"><?= e($customerFamily['father_country_of_birth'] ?? '—') ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Father's Nationality</span>
                      <span class="master-data-val"><?= e($customerFamily['father_nationality'] ?? '—') ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Father's Religion</span>
                      <span class="master-data-val"><?= e($customerFamily['father_religion'] ?? '—') ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Mother's Full Name</span>
                      <span class="master-data-val text-dark fw-semibold"><?= e($customerFamily['mother_name'] ?? 'Not Recorded') ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Mother's Date of Birth</span>
                      <span class="master-data-val"><?= format_date($customerFamily['mother_dob'] ?? null) ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Mother's Birth Country</span>
                      <span class="master-data-val"><?= e($customerFamily['mother_country_of_birth'] ?? '—') ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Mother's Nationality</span>
                      <span class="master-data-val"><?= e($customerFamily['mother_nationality'] ?? '—') ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Mother's Mobile</span>
                      <span class="master-data-val font-monospace"><?= e($customerFamily['mother_mobile'] ?? '—') ?></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Residence & Employment Bento Card -->
            <div class="col-12 col-lg-6">
              <div class="master-data-card">
                <div class="master-data-card-header">
                  <div class="d-flex align-items-center gap-2">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(16, 185, 129, 0.1); color: #10B981; display: flex; align-items: center; justify-content: center; font-size: 1.05rem;">
                      <i class="fa-solid fa-house-user"></i>
                    </div>
                    <h6 class="fw-bold mb-0 text-dark">Current Country of Residence &amp; Employment</h6>
                  </div>
                  <a href="/customers/show?id=<?= $app['customer_id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill py-1 px-3 fw-semibold shadow-2xs" style="font-size: 0.75rem;">
                    <i class="fa-solid fa-user-pen me-1"></i> Edit Profile
                  </a>
                </div>

                <div class="row g-2.5">
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Residence Country</span>
                      <span class="master-data-val text-dark fw-bold"><?= e($customerResidence['residence_country'] ?? $app['customer_nationality']) ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Permit / Residency #</span>
                      <span class="master-data-val font-monospace fw-semibold text-primary"><?= e($customerResidence['permit_number'] ?? 'Not Applicable') ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Permit Expiry Date</span>
                      <span class="master-data-val font-monospace"><?= format_date($customerResidence['expiry_date'] ?? null) ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Employer / Sponsor</span>
                      <span class="master-data-val text-dark"><?= e($customerResidence['employer'] ?? '—') ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Job Title / Designation</span>
                      <span class="master-data-val text-dark"><?= e($customerResidence['job_title'] ?? '—') ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Customer Address</span>
                      <span class="master-data-val text-muted small"><?= e($app['customer_address'] ?? '—') ?></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 3: Visa Details -->
        <div class="tab-pane fade" id="visa-pane" role="tabpanel">
          <div class="row g-4">
            <!-- Visa Specification Bento Card -->
            <div class="col-12 col-lg-6">
              <div class="master-data-card">
                <div class="master-data-card-header">
                  <div class="d-flex align-items-center gap-2">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(59, 130, 246, 0.1); color: #2563EB; display: flex; align-items: center; justify-content: center; font-size: 1.05rem;">
                      <i class="fa-solid fa-passport"></i>
                    </div>
                    <h6 class="fw-bold mb-0 text-dark">Visa Specification &amp; Policy</h6>
                  </div>
                </div>

                <div class="row g-2.5">
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Destination Country</span>
                      <span class="master-data-val fw-bold text-dark"><?= $app['flag_emoji'] ?> <?= e($app['country_name']) ?> (<?= e($app['country_code']) ?>)</span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Service Tier</span>
                      <span class="master-data-val text-primary fw-bold"><?= e($app['service_name']) ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Entry Type</span>
                      <span class="master-data-val"><?= e($app['entry_type']) ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Processing Type</span>
                      <span class="master-data-val"><?= e($app['processing_type']) ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Duration / Validity</span>
                      <span class="master-data-val"><?= e($app['duration'] ?? '30 Days') ?> (Max Stay: <?= e($app['max_stay'] ?? '30 Days') ?>)</span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Estimated SLA</span>
                      <span class="master-data-val font-monospace fw-bold"><?= (int)($app['estimated_days'] ?? 7) ?> Working Days</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Travel & Consular Timeline Bento Card -->
            <div class="col-12 col-lg-6">
              <div class="master-data-card">
                <div class="master-data-card-header">
                  <div class="d-flex align-items-center gap-2">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(16, 185, 129, 0.1); color: #10B981; display: flex; align-items: center; justify-content: center; font-size: 1.05rem;">
                      <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <h6 class="fw-bold mb-0 text-dark">Travel &amp; Consular Timeline</h6>
                  </div>
                </div>

                <div class="row g-2.5">
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Intended Travel Date</span>
                      <span class="master-data-val fw-semibold text-dark"><?= format_date($app['travel_date']) ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Expected Return Date</span>
                      <span class="master-data-val fw-semibold text-dark"><?= format_date($app['return_date']) ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Submission Date</span>
                      <span class="master-data-val"><?= format_date($app['application_date'] ?? $app['created_at']) ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Consular Reference</span>
                      <span class="master-data-val font-monospace fw-bold text-primary"><?= e($app['embassy_reference'] ?? 'Pending Submission') ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Processing Branch</span>
                      <span class="master-data-val"><?= e($app['branch_name'] ?? 'Dubai Head Office') ?></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 4: Documents Checklist -->
        <div class="tab-pane fade" id="docs-pane" role="tabpanel">
          <div class="master-data-card">
            <!-- Documents Header Strip -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pb-3 border-bottom">
              <div>
                <div class="d-flex align-items-center gap-2">
                  <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(16, 185, 129, 0.1); color: #10B981; display: flex; align-items: center; justify-content: center; font-size: 1.05rem;">
                    <i class="fa-solid fa-file-circle-check"></i>
                  </div>
                  <div>
                    <div class="d-flex align-items-center gap-2">
                      <h6 class="fw-bold mb-0 text-dark">Visa Document Checklist</h6>
                      <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-0.5 fw-bold" style="font-size: 0.72rem;">
                        <?= $checklistData['total_verified'] ?> / <?= $checklistData['total_required'] ?> Verified
                      </span>
                    </div>
                    <div class="text-muted small mt-0.5" style="font-size: 0.76rem;">Checklist automatically generated from visa service category requirements.</div>
                  </div>
                </div>
              </div>

              <div class="d-flex align-items-center gap-3 flex-wrap">
                <div style="min-width: 180px;">
                  <div class="d-flex justify-content-between small mb-1">
                    <span class="text-muted" style="font-size: 0.75rem;">Verification Progress:</span>
                    <span class="fw-bold text-dark font-monospace" style="font-size: 0.78rem;"><?= $checklistData['percentage'] ?>%</span>
                  </div>
                  <div class="progress rounded-pill" style="height: 7px;">
                    <div class="progress-bar rounded-pill <?= $checklistData['percentage'] === 100 ? 'bg-success' : 'bg-primary' ?>" role="progressbar" style="width: <?= $checklistData['percentage'] ?>%;" aria-valuenow="<?= $checklistData['percentage'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                  </div>
                </div>
                <a href="/documents" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-2xs">
                  <i class="fa-solid fa-folder-open me-1 text-warning"></i> Document Hub
                </a>
              </div>
            </div>

            <?php if (empty($documentChecklist)): ?>
              <div class="p-5 text-center text-muted bg-light rounded-4 border">
                <i class="fa-solid fa-folder-open fa-2x mb-2 d-block opacity-25"></i>
                No specific document requirements configured for this visa service.
              </div>
            <?php else: ?>
              <!-- DESKTOP / TABLET VIEW: Sleek Bento Table -->
              <div class="d-none d-md-block table-responsive" style="-webkit-overflow-scrolling: touch;">
                <table class="table-documents-bento">
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
                          <div class="fw-bold text-dark"><?= e($doc['document_name']) ?></div>
                          <?php if (!empty($doc['condition_notes'])): ?>
                            <div class="text-muted small" style="font-size: 0.72rem;"><?= e($doc['condition_notes']) ?></div>
                          <?php endif; ?>
                          <?php if (!empty($doc['rejection_reason'])): ?>
                            <div class="text-danger small mt-1" style="font-size: 0.72rem;">
                              <i class="fa-solid fa-circle-exclamation me-1"></i><strong>Rejected:</strong> <?= e($doc['rejection_reason']) ?>
                            </div>
                          <?php endif; ?>
                        </td>
                        <td>
                          <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                            <?= e($doc['category']) ?>
                          </span>
                        </td>
                        <td>
                          <?php if ($doc['is_critical']): ?>
                            <span class="badge bg-danger text-white fw-bold rounded-pill px-2.5 py-1" style="font-size: 0.72rem;"><i class="fa-solid fa-triangle-exclamation me-1"></i>Critical</span>
                          <?php elseif ($doc['is_mandatory']): ?>
                            <span class="badge bg-danger-subtle text-danger rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.72rem;">Mandatory</span>
                          <?php else: ?>
                            <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">Optional</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <span class="badge <?= $statusBadge ?> px-2.5 py-1 rounded-pill fw-bold" style="font-size: 0.72rem; letter-spacing: 0.02em;">
                            <?= e($docStatus) ?>
                          </span>
                        </td>
                        <td>
                          <?php if (!empty($doc['file_path'])): ?>
                            <div>
                              <a href="/documents/preview?id=<?= $doc['document_id'] ?>" target="_blank" class="text-danger text-decoration-none small fw-semibold">
                                <i class="fa-solid fa-paperclip me-1 text-danger"></i><?= e($doc['file_name']) ?>
                              </a>
                              <span class="text-muted small" style="font-size: 0.7rem;"> (v<?= (int)$doc['version'] ?>)</span>
                            </div>
                            <?php if (!empty($doc['expiry_date'])): ?>
                              <div class="mt-1">
                                <span class="badge <?= $doc['expiry_info']['badge_class'] ?> rounded-pill px-2 py-0.5" style="font-size: 0.68rem;">
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
                              <a href="/documents/preview?id=<?= $doc['document_id'] ?>" target="_blank" class="btn btn-outline-danger btn-sm rounded-start-pill px-2.5" title="Preview">
                                <i class="fa-solid fa-eye"></i>
                              </a>
                              <a href="/documents/download?id=<?= $doc['document_id'] ?>" class="btn btn-outline-secondary btn-sm px-2.5" title="Download">
                                <i class="fa-solid fa-download"></i>
                              </a>
                              <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle dropdown-toggle-split rounded-end-pill px-2" data-bs-toggle="dropdown" aria-expanded="false"></button>
                              <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3" style="font-size: 0.85rem; z-index: 1070;">
                                <?php if ($doc['status'] !== 'VERIFIED'): ?>
                                  <li>
                                    <form action="/documents/verify" method="POST" class="d-inline" onsubmit="return confirm('Confirm verification of this document?');">
                                      <?= csrf_field() ?>
                                      <input type="hidden" name="document_id" value="<?= $doc['document_id'] ?>">
                                      <button type="submit" class="dropdown-item py-2 text-success fw-semibold"><i class="fa-solid fa-check-circle me-2"></i> Verify Document</button>
                                    </form>
                                  </li>
                                <?php endif; ?>
                                <?php if ($doc['status'] !== 'UNDER_REVIEW' && $doc['status'] !== 'PENDING'): ?>
                                  <li>
                                    <form action="/documents/under-review" method="POST" class="d-inline" onsubmit="return confirm('Reset document back to Under Review?');">
                                      <?= csrf_field() ?>
                                      <input type="hidden" name="document_id" value="<?= $doc['document_id'] ?>">
                                      <button type="submit" class="dropdown-item py-2 text-warning fw-semibold"><i class="fa-solid fa-clock-rotate-left me-2"></i> Set Under Review</button>
                                    </form>
                                  </li>
                                <?php endif; ?>
                                <?php if ($doc['status'] !== 'REJECTED'): ?>
                                  <li>
                                    <button type="button" class="dropdown-item py-2 text-danger fw-semibold" onclick="openAppDocRejectModal(<?= $doc['document_id'] ?>, '<?= e(addslashes($doc['document_name'])) ?>')">
                                      <i class="fa-solid fa-times-circle me-2"></i> Reject Document
                                    </button>
                                  </li>
                                <?php endif; ?>
                                <li>
                                  <button type="button" class="dropdown-item py-2" onclick="openAppDocReplaceModal(<?= $doc['document_id'] ?>, '<?= e(addslashes($doc['document_name'])) ?>')">
                                    <i class="fa-solid fa-cloud-arrow-up text-primary me-2"></i> Upload Replacement
                                  </button>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                  <button type="button" class="dropdown-item py-2 text-danger" onclick="openAppDocDeleteModal(<?= $doc['document_id'] ?>, '<?= e(addslashes($doc['document_name'])) ?>')">
                                    <i class="fa-solid fa-trash-can me-2"></i> Delete Document
                                  </button>
                                </li>
                              </ul>
                            </div>
                          <?php else: ?>
                            <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 py-1 shadow-xs" style="background: linear-gradient(135deg, #E11D48, #BE123C); border: none;" onclick="openAppDocUploadModal(<?= $app['id'] ?>, <?= $doc['document_type_id'] ?>, '<?= e(addslashes($doc['document_name'])) ?>')">
                              <i class="fa-solid fa-upload me-1"></i> Upload
                            </button>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <!-- MOBILE VIEW: Individual Document Bento Cards (d-md-none) -->
              <div class="d-md-none">
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
                  <div class="doc-mobile-card doc-status-<?= strtolower($docStatus) ?>">
                    <div class="doc-mobile-top">
                      <div>
                        <div class="doc-mobile-title"><?= e($doc['document_name']) ?></div>
                        <div class="doc-mobile-meta">
                          <span class="badge bg-light text-secondary border rounded-pill px-2 py-0.5" style="font-size: 0.68rem;"><?= e($doc['category']) ?></span>
                          <?php if ($doc['is_critical']): ?>
                            <span class="badge bg-danger text-white rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.68rem;"><i class="fa-solid fa-triangle-exclamation me-0.5"></i>Critical</span>
                          <?php elseif ($doc['is_mandatory']): ?>
                            <span class="badge bg-danger-subtle text-danger rounded-pill px-2 py-0.5 fw-semibold" style="font-size: 0.68rem;">Mandatory</span>
                          <?php else: ?>
                            <span class="badge bg-light text-muted border rounded-pill px-2 py-0.5" style="font-size: 0.68rem;">Optional</span>
                          <?php endif; ?>
                        </div>
                      </div>
                      <span class="badge <?= $statusBadge ?> rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.7rem;">
                        <?= e($docStatus) ?>
                      </span>
                    </div>

                    <?php if (!empty($doc['condition_notes'])): ?>
                      <div class="text-muted small mb-2" style="font-size: 0.74rem;"><?= e($doc['condition_notes']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($doc['rejection_reason'])): ?>
                      <div class="alert alert-danger p-2 mb-2 rounded-3 small" style="font-size: 0.74rem;">
                        <i class="fa-solid fa-circle-exclamation me-1"></i><strong>Rejected:</strong> <?= e($doc['rejection_reason']) ?>
                      </div>
                    <?php endif; ?>

                    <!-- Mobile File Box -->
                    <div class="doc-mobile-file-box">
                      <?php if (!empty($doc['file_path'])): ?>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-1">
                          <a href="/documents/preview?id=<?= $doc['document_id'] ?>" target="_blank" class="text-danger fw-semibold text-truncate small" style="max-width: 200px;">
                            <i class="fa-solid fa-paperclip me-1"></i><?= e($doc['file_name']) ?>
                          </a>
                          <span class="badge bg-white text-muted border font-monospace" style="font-size: 0.66rem;">v<?= (int)$doc['version'] ?></span>
                        </div>
                        <?php if (!empty($doc['expiry_date'])): ?>
                          <div class="mt-1">
                            <span class="badge <?= $doc['expiry_info']['badge_class'] ?> rounded-pill px-2 py-0.5" style="font-size: 0.66rem;">
                              <i class="fa-solid fa-clock me-1"></i><?= e($doc['expiry_info']['label']) ?>
                            </span>
                          </div>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="text-muted small"><i class="fa-solid fa-file-circle-xmark me-1 text-secondary"></i>File not uploaded yet</span>
                      <?php endif; ?>
                    </div>

                    <!-- Mobile Actions -->
                    <div class="doc-mobile-actions">
                      <?php if (!empty($doc['file_path'])): ?>
                        <a href="/documents/preview?id=<?= $doc['document_id'] ?>" target="_blank" class="btn btn-outline-danger btn-sm rounded-pill flex-grow-1 text-center py-1">
                          <i class="fa-solid fa-eye me-1"></i> Preview
                        </a>
                        <a href="/documents/download?id=<?= $doc['document_id'] ?>" class="btn btn-outline-secondary btn-sm rounded-pill flex-grow-1 text-center py-1">
                          <i class="fa-solid fa-download me-1"></i> Download
                        </a>
                        <div class="dropdown">
                          <button class="btn btn-light border btn-sm rounded-pill px-2.5 py-1 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                          </button>
                          <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3" style="font-size: 0.85rem; z-index: 1070;">
                            <?php if ($doc['status'] !== 'VERIFIED'): ?>
                              <li>
                                <form action="/documents/verify" method="POST" class="d-inline" onsubmit="return confirm('Confirm verification of this document?');">
                                  <?= csrf_field() ?>
                                  <input type="hidden" name="document_id" value="<?= $doc['document_id'] ?>">
                                  <button type="submit" class="dropdown-item py-2 text-success fw-semibold"><i class="fa-solid fa-check-circle me-2"></i> Verify</button>
                                </form>
                              </li>
                            <?php endif; ?>
                            <li>
                              <button type="button" class="dropdown-item py-2" onclick="openAppDocReplaceModal(<?= $doc['document_id'] ?>, '<?= e(addslashes($doc['document_name'])) ?>')">
                                <i class="fa-solid fa-cloud-arrow-up text-primary me-2"></i> Replace File
                              </button>
                            </li>
                            <?php if ($doc['status'] !== 'REJECTED'): ?>
                              <li>
                                <button type="button" class="dropdown-item py-2 text-danger" onclick="openAppDocRejectModal(<?= $doc['document_id'] ?>, '<?= e(addslashes($doc['document_name'])) ?>')">
                                  <i class="fa-solid fa-times-circle me-2"></i> Reject
                                </button>
                              </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                              <button type="button" class="dropdown-item py-2 text-danger" onclick="openAppDocDeleteModal(<?= $doc['document_id'] ?>, '<?= e(addslashes($doc['document_name'])) ?>')">
                                <i class="fa-solid fa-trash-can me-2"></i> Delete
                              </button>
                            </li>
                          </ul>
                        </div>
                      <?php else: ?>
                        <button type="button" class="btn btn-danger btn-sm rounded-pill w-100 py-1.5 shadow-xs fw-semibold" style="background: linear-gradient(135deg, #E11D48, #BE123C); border: none;" onclick="openAppDocUploadModal(<?= $app['id'] ?>, <?= $doc['document_type_id'] ?>, '<?= e(addslashes($doc['document_name'])) ?>')">
                          <i class="fa-solid fa-upload me-1"></i> Upload Document
                        </button>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- TAB 5: Document Requests -->
        <div class="tab-pane fade" id="doc-req-pane" role="tabpanel">
          <div class="master-data-card">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(245, 158, 11, 0.1); color: #F59E0B; display: flex; align-items: center; justify-content: center; font-size: 1.05rem;">
                  <i class="fa-solid fa-file-circle-question"></i>
                </div>
                <div>
                  <h6 class="fw-bold mb-0 text-dark">Additional Documents Requested</h6>
                  <div class="text-muted small" style="font-size: 0.76rem;">Specialized requirements issued to applicant</div>
                </div>
              </div>
              <button type="button" class="btn btn-warning btn-sm rounded-pill text-dark fw-bold px-3 shadow-2xs" data-bs-toggle="modal" data-bs-target="#requestDocModal">
                <i class="fa-solid fa-plus me-1"></i> Request Document
              </button>
            </div>

            <?php if (empty($documentRequests)): ?>
              <div class="p-5 text-center text-muted bg-light rounded-4 border">
                <i class="fa-solid fa-file-circle-check fa-2x mb-2 d-block opacity-25"></i>
                No additional documents requested for this applicant.
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table-documents-bento">
                  <thead>
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
                        <td class="fw-bold text-dark"><?= e($dr['document_name'] ?? 'Custom Document') ?></td>
                        <td class="fw-semibold <?= strtotime($dr['due_date']) < time() && $dr['status'] === 'PENDING' ? 'text-danger' : 'text-dark' ?>">
                          <?= format_date($dr['due_date']) ?>
                        </td>
                        <td>
                          <span class="badge rounded-pill px-2.5 py-1 fw-bold <?= $dr['status'] === 'SUBMITTED' ? 'bg-success' : ($dr['status'] === 'PENDING' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                            <?= e($dr['status']) ?>
                          </span>
                        </td>
                        <td class="text-muted small"><?= e($dr['notes'] ?: '—') ?></td>
                        <td><span class="small fw-semibold"><?= e($dr['requested_by_name'] ?? 'Staff') ?></span></td>
                        <td class="text-muted small"><?= format_datetime($dr['created_at']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- TAB 6: Tasks -->
        <div class="tab-pane fade" id="tasks-pane" role="tabpanel">
          <div class="master-data-card">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(245, 158, 11, 0.1); color: #F59E0B; display: flex; align-items: center; justify-content: center; font-size: 1.05rem;">
                  <i class="fa-solid fa-list-check"></i>
                </div>
                <div>
                  <h6 class="fw-bold mb-0 text-dark">Operational Tasks &amp; Milestones</h6>
                  <div class="text-muted small" style="font-size: 0.76rem;">Internal case worker task checklist</div>
                </div>
              </div>
              <a href="/tasks" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-2xs">
                <i class="fa-solid fa-plus me-1"></i> Create Task
              </a>
            </div>

            <?php if (empty($tasks)): ?>
              <div class="p-5 text-center text-muted bg-light rounded-4 border">
                <i class="fa-solid fa-list-check fa-2x mb-2 d-block opacity-25"></i>
                No linked tasks for this visa application.
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table-documents-bento">
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
                        <td class="fw-bold text-dark"><?= e($tsk['task_title'] ?? $tsk['title'] ?? 'Task') ?></td>
                        <td><span class="small fw-semibold"><?= e($tsk['assigned_to_name'] ?? 'Unassigned') ?></span></td>
                        <td><?= format_date($tsk['due_date']) ?></td>
                        <td><span class="badge rounded-pill px-2.5 py-1 fw-bold badge-priority-<?= strtolower($tsk['priority']) ?>"><?= e($tsk['priority']) ?></span></td>
                        <td><span class="badge rounded-pill px-2.5 py-1 fw-bold bg-<?= $tsk['status'] === 'Completed' ? 'success' : 'warning text-dark' ?>"><?= e($tsk['status']) ?></span></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- TAB 7: Appointments -->
        <div class="tab-pane fade" id="appointments-pane" role="tabpanel">
          <div class="master-data-card">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(37, 99, 235, 0.1); color: #2563EB; display: flex; align-items: center; justify-content: center; font-size: 1.05rem;">
                  <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div>
                  <h6 class="fw-bold mb-0 text-dark">Scheduled Appointments</h6>
                  <div class="text-muted small" style="font-size: 0.76rem;">Embassy, biometric, and medical interview bookings</div>
                </div>
              </div>
              <a href="/appointments" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-2xs">
                <i class="fa-solid fa-calendar-plus me-1"></i> Schedule Appointment
              </a>
            </div>

            <?php if (empty($appointments)): ?>
              <div class="p-5 text-center text-muted bg-light rounded-4 border">
                <i class="fa-solid fa-calendar-check fa-2x mb-2 d-block opacity-25"></i>
                No appointments scheduled for this application.
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table-documents-bento">
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
                        <td class="fw-bold text-dark"><?= e($apt['appointment_type']) ?></td>
                        <td><?= e($apt['center_name']) ?></td>
                        <td><?= format_date($apt['appointment_date']) ?> at <?= e($apt['appointment_time']) ?></td>
                        <td><code class="text-primary font-monospace"><?= e($apt['reference_number'] ?? '—') ?></code></td>
                        <td><span class="badge rounded-pill px-2.5 py-1 bg-info text-dark fw-bold"><?= e($apt['status']) ?></span></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- TAB 8: Internal Notes -->
        <div class="tab-pane fade" id="notes-pane" role="tabpanel">
          <div class="master-data-card">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(100, 116, 139, 0.1); color: #64748B; display: flex; align-items: center; justify-content: center; font-size: 1.05rem;">
                  <i class="fa-solid fa-note-sticky"></i>
                </div>
                <div>
                  <h6 class="fw-bold mb-0 text-dark">Confidential Internal Staff Notes</h6>
                  <div class="text-muted small" style="font-size: 0.76rem;">Private case worker commentary (not visible to applicant)</div>
                </div>
              </div>
              <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-2xs" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                <i class="fa-solid fa-plus me-1"></i> Add Note
              </button>
            </div>

            <div class="bg-light p-3.5 rounded-4 border font-monospace text-dark" style="white-space: pre-wrap; font-size: 0.88rem; max-height: 400px; overflow-y: auto;">
              <?= e($app['internal_notes'] ?: 'No internal notes recorded for this application yet.') ?>
            </div>
          </div>
        </div>

        <!-- TAB 7: Payments & Finance -->
        <div class="tab-pane fade" id="payments-pane" role="tabpanel">
          
          <!-- Financial Summary Cards (Bento Style) -->
          <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
              <div class="master-data-card p-3 d-flex flex-column justify-content-between" style="border-left: 4px solid #2563EB;">
                <div class="d-flex align-items-center justify-content-between text-muted small mb-1">
                  <span class="fw-semibold">Total Invoice</span>
                  <i class="fa-solid fa-file-invoice-dollar text-primary"></i>
                </div>
                <div class="fs-4 fw-bold text-dark font-monospace"><?= format_currency($app['total_amount'] ?? $app['selling_price'] ?? 0) ?></div>
                <div class="text-muted" style="font-size: 0.7rem;">Contracted Amount</div>
              </div>
            </div>
            <div class="col-6 col-lg-3">
              <div class="master-data-card p-3 d-flex flex-column justify-content-between" style="border-left: 4px solid #10B981;">
                <div class="d-flex align-items-center justify-content-between text-muted small mb-1">
                  <span class="fw-semibold">Total Paid</span>
                  <i class="fa-solid fa-circle-check text-success"></i>
                </div>
                <div class="fs-4 fw-bold text-success font-monospace"><?= format_currency($app['paid_amount'] ?? 0) ?></div>
                <div class="text-muted" style="font-size: 0.7rem;">Verified Collections</div>
              </div>
            </div>
            <div class="col-6 col-lg-3">
              <div class="master-data-card p-3 d-flex flex-column justify-content-between" style="border-left: 4px solid <?= ((float)($app['balance_amount'] ?? 0) > 0) ? '#E11D48' : '#64748B' ?>;">
                <div class="d-flex align-items-center justify-content-between text-muted small mb-1">
                  <span class="fw-semibold">Balance Due</span>
                  <i class="fa-solid fa-receipt <?= ((float)($app['balance_amount'] ?? 0) > 0) ? 'text-danger' : 'text-secondary' ?>"></i>
                </div>
                <div class="fs-4 fw-bold <?= ((float)($app['balance_amount'] ?? 0) > 0) ? 'text-danger' : 'text-muted' ?> font-monospace"><?= format_currency($app['balance_amount'] ?? 0) ?></div>
                <div class="text-muted" style="font-size: 0.7rem;">Outstanding Amount</div>
              </div>
            </div>
            <div class="col-6 col-lg-3">
              <div class="master-data-card p-3 d-flex flex-column justify-content-between" style="border-left: 4px solid #F59E0B;">
                <div class="d-flex align-items-center justify-content-between text-muted small mb-1">
                  <span class="fw-semibold">Payment Status</span>
                  <i class="fa-solid fa-wallet text-warning"></i>
                </div>
                <div class="mt-1">
                  <?php
                    $bal = (float)($app['balance_amount'] ?? 0);
                    $paid = (float)($app['paid_amount'] ?? 0);
                    $total = (float)($app['total_amount'] ?? $app['selling_price'] ?? 0);
                    if ($bal <= 0 && $paid > 0) echo '<span class="badge bg-success rounded-pill px-3 py-1 fs-6 fw-bold">PAID IN FULL</span>';
                    elseif ($paid > 0 && $bal > 0) echo '<span class="badge bg-warning text-dark rounded-pill px-3 py-1 fs-6 fw-bold">PARTIAL PAID</span>';
                    else echo '<span class="badge bg-danger rounded-pill px-3 py-1 fs-6 fw-bold">UNPAID DUE</span>';
                  ?>
                </div>
                <div class="text-muted" style="font-size: 0.7rem;">Settlement Stage</div>
              </div>
            </div>
          </div>

          <!-- Record Payment Form Bento Card -->
          <div class="master-data-card mb-4 p-0 overflow-hidden">
            <div class="p-3 bg-light border-bottom d-flex align-items-center justify-content-between">
              <h6 class="mb-0 fw-bold text-success"><i class="fa-solid fa-circle-plus me-2"></i>Record New Payment</h6>
              <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1">Direct Settlement</span>
            </div>
            <div class="p-3 p-md-4">
              <form action="/payments/store" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                <!-- Multi-Currency & Payment Details Strip -->
                <div class="p-3 bg-light rounded border mb-3">
                  <div class="row g-2 mb-2">
                    <div class="col-6 col-lg-3">
                      <label class="form-label small fw-semibold">Received Currency</label>
                      <select name="from_currency" id="appPayFromCur" class="form-select form-select-sm fw-bold" onchange="onAppPayCurrenciesChanged()">
                        <?php foreach (['USD', 'AED', 'LKR', 'EUR', 'GBP', 'SAR', 'QAR', 'INR', 'CAD', 'AUD'] as $c): ?>
                          <option value="<?= $c ?>" <?= $c === ($app['currency'] ?? 'USD') ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-6 col-lg-3">
                      <label class="form-label small fw-semibold">Received Amount <span class="text-danger">*</span></label>
                      <input type="number" step="0.01" min="0.01" name="original_amount" id="appPayOrigAmount" class="form-control form-control-sm fw-bold" 
                             value="<?= number_format((float)($app['balance_amount'] ?? 0), 2, '.', '') ?>" placeholder="0.00" required oninput="calcAppPaymentConverter()">
                    </div>
                    <div class="col-6 col-lg-3">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-semibold mb-0">Exchange Rate</label>
                        <button type="button" class="btn btn-link btn-xs p-0 text-decoration-none" id="appPayInvertRateBtn" onclick="invertAppPayRate()" title="Invert rate (1 / rate)" style="font-size: 0.68rem;">
                          <i class="fa-solid fa-arrows-rotate me-0.5"></i> Invert
                        </button>
                      </div>
                      <input type="number" step="0.000001" name="exchange_rate" id="appPayRate" class="form-control form-control-sm text-end fw-bold" value="1.000000" oninput="calcAppPaymentConverter(true)">
                    </div>
                    <div class="col-6 col-lg-3">
                      <label class="form-label small fw-semibold">Settlement Currency (Invoice)</label>
                      <select name="to_currency" id="appPayToCur" class="form-select form-select-sm fw-bold" onchange="onAppPayCurrenciesChanged()">
                        <?php foreach (['USD', 'AED', 'LKR', 'EUR', 'GBP', 'SAR', 'QAR', 'INR', 'CAD', 'AUD'] as $c): ?>
                          <option value="<?= $c ?>" <?= $c === ($app['currency'] ?? 'USD') ? 'selected' : '' ?>><?= $c ?><?= $c === 'USD' ? ' ($)' : ($c === 'EUR' ? ' (€)' : ($c === 'GBP' ? ' (£)' : '')) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                    <div>
                      <span class="small fw-semibold text-muted">Settlement Applied to Invoice:</span>
                      <div class="small text-muted" id="appPayFormulaText" style="font-size: 0.73rem;">1:1 Same Currency</div>
                    </div>
                    <div class="text-end">
                      <span class="h6 fw-bold text-success mb-0 fs-5" id="appPayDisplay">$<?= number_format((float)($app['balance_amount'] ?? 0), 2) ?> <?= e($app['currency'] ?? 'USD') ?></span>
                      <input type="hidden" name="amount" id="appPayAmountHidden" value="<?= number_format((float)($app['balance_amount'] ?? 0), 2, '.', '') ?>">
                      <input type="hidden" name="converted_amount" id="appPayConvertedHidden" value="<?= number_format((float)($app['balance_amount'] ?? 0), 2, '.', '') ?>">
                    </div>
                  </div>
                </div>

                <div class="row g-3">
                  <div class="col-md-3">
                    <label class="form-label small fw-semibold">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold">Payment Method <span class="text-danger">*</span></label>
                    <select name="payment_method" class="form-select" required>
                      <option value="Cash">Cash</option>
                      <option value="Bank Transfer">Bank Transfer</option>
                      <option value="Credit Card">Credit Card</option>
                      <option value="Debit Card">Debit Card</option>
                      <option value="Cheque">Cheque</option>
                      <option value="Customer Wallet">Customer Wallet</option>
                      <option value="Online Payment">Online Payment</option>
                      <option value="Western Union">Western Union</option>
                    </select>
                  </div>
                  <div class="col-md-5">
                    <label class="form-label small fw-semibold">Transaction Reference</label>
                    <input type="text" name="transaction_reference" class="form-control" placeholder="Bank ref / cheque number...">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Payment Slip / Cash Receipt Voucher <span class="text-danger">*</span></label>
                    <input type="file" name="receipt_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.docx" required>
                    <small class="text-muted" style="font-size: 0.72rem;"><i class="fa-solid fa-file-circle-check text-success me-1"></i>Mandatory for all payment methods (Bank slip, Deposit slip, Cash voucher, POS slip).</small>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Internal Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="Optional payment notes...">
                  </div>
                  <div class="col-12">
                    <button type="submit" class="btn btn-success px-4 fw-semibold">
                      <i class="fa-solid fa-check me-2"></i>Record Payment &amp; Generate Receipt
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
              <script>
              const APP_CURRENCY_BENCHMARK = {
                'USD': 1.0,
                'AED': 3.6725,
                'LKR': 305.00,
                'EUR': 0.9200,
                'GBP': 0.7900,
                'SAR': 3.7500,
                'QAR': 3.6400,
                'INR': 83.5000,
                'CAD': 1.3600,
                'AUD': 1.5200
              };

              function onAppPayCurrenciesChanged() {
                const from = document.getElementById('appPayFromCur').value || 'USD';
                const to = document.getElementById('appPayToCur').value || 'USD';
                const rateInput = document.getElementById('appPayRate');

                if (from === to) {
                  rateInput.value = '1.000000';
                  rateInput.readOnly = true;
                  rateInput.classList.add('bg-light');
                } else {
                  rateInput.readOnly = false;
                  rateInput.classList.remove('bg-light');
                  const fromBench = APP_CURRENCY_BENCHMARK[from] || 1.0;
                  const toBench = APP_CURRENCY_BENCHMARK[to] || 1.0;
                  const crossRate = toBench / fromBench;
                  rateInput.value = crossRate.toFixed(6);
                }
                calcAppPaymentConverter(false);
              }

              function invertAppPayRate() {
                const rateInput = document.getElementById('appPayRate');
                const currRate = parseFloat(rateInput.value) || 1;
                if (currRate > 0) {
                  rateInput.value = (1 / currRate).toFixed(6);
                  calcAppPaymentConverter(true);
                }
              }

              function calcAppPaymentConverter(isManualRate) {
                const orig = parseFloat(document.getElementById('appPayOrigAmount').value) || 0;
                const from = document.getElementById('appPayFromCur').value || 'USD';
                const to = document.getElementById('appPayToCur').value || 'USD';
                let rate = parseFloat(document.getElementById('appPayRate').value);

                if (from === to) {
                  rate = 1.0;
                  document.getElementById('appPayRate').value = '1.000000';
                } else if (isNaN(rate) || rate <= 0) {
                  rate = 1.0;
                }

                const conv = (orig * rate).toFixed(2);
                const sym = to === 'USD' ? '$' : (to === 'EUR' ? '€' : (to === 'GBP' ? '£' : ''));
                document.getElementById('appPayDisplay').innerText = sym + conv + ' ' + to;
                document.getElementById('appPayAmountHidden').value = conv;
                document.getElementById('appPayConvertedHidden').value = conv;

                const formulaEl = document.getElementById('appPayFormulaText');
                if (formulaEl) {
                  if (from === to) {
                    formulaEl.innerHTML = `<span class="badge bg-secondary-subtle text-secondary">Same Currency</span> ${orig.toFixed(2)} ${from} = <strong>${conv} ${to}</strong> (1:1)`;
                  } else {
                    formulaEl.innerHTML = `Calculation: <strong>${orig.toFixed(2)} ${from}</strong> × ${rate.toFixed(4)} = <strong class="text-success">${sym}${conv} ${to}</strong> applied to invoice`;
                  }
                }
              }

              document.addEventListener('DOMContentLoaded', function () {
                onAppPayCurrenciesChanged();
              });
              </script>
            </div>
          </div>

          <!-- Payment History Table (Bento Style) -->
          <div class="master-data-card mb-4">
            <div class="master-data-card-header">
              <div class="d-flex align-items-center gap-2">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(16, 185, 129, 0.1); color: #10B981; display: flex; align-items: center; justify-content: center; font-size: 1.05rem;">
                  <i class="fa-solid fa-receipt"></i>
                </div>
                <div>
                  <h6 class="fw-bold mb-0 text-dark">Payment History &amp; Receipts</h6>
                  <div class="text-muted small" style="font-size: 0.76rem;">Verified transaction settlements for this visa application</div>
                </div>
              </div>
            </div>

            <?php if (empty($appPayments)): ?>
              <div class="p-5 text-center text-muted bg-light rounded-4 border">
                <i class="fa-solid fa-money-bill-wave fa-2x mb-2 d-block opacity-25"></i>
                No payments recorded yet for this application.
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table-documents-bento">
                  <thead>
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
                        <td class="fw-bold font-monospace text-primary"><?= e($pay['payment_number']) ?></td>
                        <td><?= format_date($pay['payment_date']) ?></td>
                        <td class="fw-bold text-success font-monospace"><?= format_currency($pay['amount']) ?></td>
                        <td><span class="badge bg-light text-dark border rounded-pill px-2.5 py-1"><?= e($pay['payment_method']) ?></span></td>
                        <td class="text-muted small font-monospace"><?= e($pay['transaction_reference'] ?: '—') ?></td>
                        <td><span class="small fw-semibold"><?= e($pay['received_by_name'] ?? '—') ?></span></td>
                        <td><span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fw-bold"><?= e($pay['status']) ?></span></td>
                        <td class="text-end text-nowrap">
                          <a href="/payments/receipt?id=<?= $pay['id'] ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-2.5 shadow-2xs" title="View Receipt">
                            <i class="fa-solid fa-receipt me-1"></i> Receipt
                          </a>
                          <?php if (($currentUser['role_slug'] ?? '') === 'super-admin' || (int)($currentUser['role_id'] ?? 0) === 1 || user_can('payments.delete') || user_can('payments.manage') || user_can('finance.manage')): ?>
                            <form action="/payments/delete" method="POST" class="d-inline ms-1" onsubmit="return confirm('Are you sure you want to permanently delete payment <?= e($pay['payment_number']) ?> of <?= format_currency($pay['amount']) ?>? This action cannot be undone and will restore the balance on this application.');">
                              <?= csrf_field() ?>
                              <input type="hidden" name="payment_id" value="<?= (int)$pay['id'] ?>">
                              <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2.5 shadow-2xs" title="Delete Payment Record">
                                <i class="fa-solid fa-trash-can"></i>
                              </button>
                            </form>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="2" class="text-end text-muted fw-bold border-top" style="border-left: none; border-bottom: none;">Total Collected:</td>
                      <td class="text-success fw-bold font-monospace fs-6 border-top" style="border-bottom: none;"><?= format_currency(array_sum(array_column($appPayments, 'amount'))) ?></td>
                      <td colspan="5" class="border-top" style="border-right: none; border-bottom: none;"></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            <?php endif; ?>
          </div>

          <!-- Refund Section (Bento Style) -->
          <?php if (!empty($appPayments)): ?>
          <div class="master-data-card mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(245, 158, 11, 0.1); color: #F59E0B; display: flex; align-items: center; justify-content: center; font-size: 1.05rem;">
                  <i class="fa-solid fa-rotate-left"></i>
                </div>
                <div>
                  <h6 class="fw-bold mb-0 text-dark">Refund Transactions</h6>
                  <div class="text-muted small" style="font-size: 0.76rem;">Reversals and disbursed client refunds</div>
                </div>
              </div>
              <button type="button" class="btn btn-outline-warning btn-sm rounded-pill px-3 shadow-2xs fw-bold" data-bs-toggle="modal" data-bs-target="#refundModal">
                <i class="fa-solid fa-plus me-1"></i> Process Refund
              </button>
            </div>

            <?php if (empty($appRefunds)): ?>
              <div class="p-4 text-center text-muted bg-light rounded-4 border">
                No refunds processed for this application.
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table-documents-bento">
                  <thead>
                    <tr><th>Refund #</th><th>Date</th><th>Amount</th><th>Method</th><th>Reason</th><th>Processed By</th></tr>
                  </thead>
                  <tbody>
                    <?php foreach ($appRefunds as $ref): ?>
                      <tr>
                        <td class="fw-bold font-monospace text-warning"><?= e($ref['refund_number']) ?></td>
                        <td><?= format_date($ref['created_at']) ?></td>
                        <td class="fw-bold text-warning font-monospace"><?= format_currency($ref['amount']) ?></td>
                        <td><span class="badge bg-light text-dark border rounded-pill px-2.5 py-1"><?= e($ref['payment_method']) ?></span></td>
                        <td class="text-muted small"><?= e($ref['reason']) ?></td>
                        <td><span class="small fw-semibold"><?= e($ref['processed_by_name'] ?? '—') ?></span></td>
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
            <div class="col-12 col-lg-6">
              <div class="master-data-card">
                <div class="master-data-card-header">
                  <div class="d-flex align-items-center gap-2">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(37, 99, 235, 0.1); color: #2563EB; display: flex; align-items: center; justify-content: center; font-size: 1.05rem;">
                      <i class="fa-solid fa-building-flag"></i>
                    </div>
                    <h6 class="fw-bold mb-0 text-dark">Processing Supplier &amp; Clearing Agent</h6>
                  </div>
                </div>

                <?php if (!empty($supplierDetails)): ?>
                  <div class="row g-2.5">
                    <div class="col-12 col-sm-6">
                      <div class="master-data-item">
                        <span class="master-data-label">Supplier / Vendor</span>
                        <span class="master-data-val text-dark fw-bold"><?= e($supplierDetails['company_name'] ?? ($supplierDetails['name'] ?? '—')) ?></span>
                      </div>
                    </div>
                    <div class="col-12 col-sm-6">
                      <div class="master-data-item">
                        <span class="master-data-label">Supplier Code</span>
                        <span class="master-data-val font-monospace"><span class="badge bg-light text-dark border">@<?= e($supplierDetails['supplier_code'] ?? ($supplierDetails['code'] ?? '—')) ?></span></span>
                      </div>
                    </div>
                    <div class="col-12 col-sm-6">
                      <div class="master-data-item">
                        <span class="master-data-label">Country &amp; City</span>
                        <span class="master-data-val"><?= e($supplierDetails['country'] ?? '—') ?>, <?= e($supplierDetails['city'] ?? '—') ?></span>
                      </div>
                    </div>
                    <div class="col-12 col-sm-6">
                      <div class="master-data-item">
                        <span class="master-data-label">Contact Person</span>
                        <span class="master-data-val"><?= e($supplierDetails['contact_person'] ?? '—') ?></span>
                      </div>
                    </div>
                    <div class="col-12 col-sm-6">
                      <div class="master-data-item">
                        <span class="master-data-label">Supplier Reference #</span>
                        <span class="master-data-val font-monospace fw-bold text-primary"><?= e($app['supplier_reference'] ?? 'Not Assigned') ?></span>
                      </div>
                    </div>
                    <div class="col-12 col-sm-6">
                      <div class="master-data-item">
                        <span class="master-data-label">Embassy / Gov Ref #</span>
                        <span class="master-data-val font-monospace fw-bold text-secondary"><?= e($app['embassy_reference'] ?? '—') ?></span>
                      </div>
                    </div>
                  </div>
                <?php else: ?>
                  <div class="p-4 bg-light rounded-4 text-center text-muted small">
                    <i class="fa-solid fa-handshake-slash fa-2x mb-2 d-block opacity-25"></i>
                    No external supplier assigned. Processed via in-house consular team.
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <div class="col-12 col-lg-6">
              <div class="master-data-card">
                <div class="master-data-card-header">
                  <div class="d-flex align-items-center gap-2">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(16, 185, 129, 0.1); color: #10B981; display: flex; align-items: center; justify-content: center; font-size: 1.05rem;">
                      <i class="fa-solid fa-calculator"></i>
                    </div>
                    <h6 class="fw-bold mb-0 text-dark">Commercial Margins &amp; Costs</h6>
                  </div>
                </div>

                <div class="row g-2.5">
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Selling Price (Client)</span>
                      <span class="master-data-val text-dark fw-bold"><?= format_currency($app['selling_price'] ?? $app['total_amount'] ?? 0) ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Supplier Net Cost</span>
                      <span class="master-data-val text-danger fw-bold"><?= format_currency($app['cost_price'] ?? $app['supplier_cost'] ?? 0) ?></span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Estimated Profit</span>
                      <span class="master-data-val text-success fw-bold">
                        <?= format_currency(max(0, (float)($app['selling_price'] ?? $app['total_amount'] ?? 0) - (float)($app['cost_price'] ?? $app['supplier_cost'] ?? 0))) ?>
                      </span>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="master-data-item">
                      <span class="master-data-label">Profit Margin %</span>
                      <span class="master-data-val text-primary fw-bold">
                        <?php
                          $sp = (float)($app['selling_price'] ?? $app['total_amount'] ?? 0);
                          $cp = (float)($app['cost_price'] ?? $app['supplier_cost'] ?? 0);
                          echo $sp > 0 ? number_format((($sp - $cp) / $sp) * 100, 1) . '%' : '0.0%';
                        ?>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Supplier Disbursements -->
          <?php if (!empty($supplierPayments)): ?>
            <div class="master-data-card mt-3">
              <div class="master-data-card-header">
                <div class="d-flex align-items-center gap-2">
                  <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(37, 99, 235, 0.1); color: #2563EB; display: flex; align-items: center; justify-content: center; font-size: 1.05rem;">
                    <i class="fa-solid fa-money-bill-transfer"></i>
                  </div>
                  <h6 class="fw-bold mb-0 text-dark">Supplier Disbursements &amp; Settlements</h6>
                </div>
              </div>
              <div class="table-responsive">
                <table class="table-documents-bento">
                  <thead>
                    <tr><th>Payment Ref</th><th>Date</th><th>Amount</th><th>Method</th><th>Notes</th></tr>
                  </thead>
                  <tbody>
                    <?php foreach ($supplierPayments as $sp): ?>
                      <tr>
                        <td class="fw-bold font-monospace text-primary"><?= e($sp['payment_reference']) ?></td>
                        <td><?= format_date($sp['payment_date']) ?></td>
                        <td class="fw-bold text-danger font-monospace"><?= format_currency($sp['amount']) ?></td>
                        <td><span class="badge bg-light text-dark border rounded-pill px-2.5 py-1"><?= e($sp['payment_method']) ?></span></td>
                        <td class="text-muted small"><?= e($sp['notes'] ?? '—') ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- TAB 9: Client Communications Log -->
        <div class="tab-pane fade" id="comm-pane" role="tabpanel">
          <div class="master-data-card">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(6, 182, 212, 0.1); color: #0891B2; display: flex; align-items: center; justify-content: center; font-size: 1.05rem;">
                  <i class="fa-solid fa-comments"></i>
                </div>
                <div>
                  <h6 class="fw-bold mb-0 text-dark">Client Touchpoints &amp; Communications</h6>
                  <div class="text-muted small" style="font-size: 0.76rem;">Logs of all phone, email, and WhatsApp interactions</div>
                </div>
              </div>
              <button type="button" class="btn btn-info btn-sm rounded-pill text-dark fw-bold px-3 shadow-2xs" data-bs-toggle="modal" data-bs-target="#addCommModal">
                <i class="fa-solid fa-plus me-1"></i> Log Communication
              </button>
            </div>

            <?php if (empty($communications)): ?>
              <div class="p-5 text-center text-muted bg-light rounded-4 border">
                <i class="fa-solid fa-phone-volume fa-2x mb-2 d-block opacity-25"></i>
                No communications logged for this application yet.
              </div>
            <?php else: ?>
              <div class="activity-timeline">
                <?php foreach ($communications as $comm): ?>
                  <div class="p-3.5 border rounded-4 bg-light mb-3 shadow-2xs">
                    <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-1">
                      <div>
                        <span class="badge bg-primary rounded-pill px-2.5 py-1 me-1"><i class="fa-solid fa-phone me-1"></i><?= e($comm['channel']) ?></span>
                        <span class="badge rounded-pill px-2.5 py-1 <?= $comm['direction'] === 'Inbound' ? 'bg-success' : 'bg-secondary' ?>"><?= e($comm['direction']) ?></span>
                        <strong class="text-dark ms-2"><?= e($comm['subject'] ?? 'Client Touchpoint') ?></strong>
                      </div>
                      <span class="text-muted small"><?= format_datetime($comm['created_at']) ?></span>
                    </div>
                    <p class="mb-2 text-dark small" style="white-space: pre-wrap; font-size: 0.86rem;"><?= e($comm['message']) ?></p>
                    <div class="text-muted" style="font-size: 0.75rem;">
                      Contact Person: <strong><?= e($comm['contact_person'] ?? 'Applicant') ?></strong> &bull; Logged by: <strong><?= e($comm['staff_name'] ?? 'Staff Officer') ?></strong>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- TAB 10: Decision, Visa Grant & Downloads -->
        <div class="tab-pane fade" id="decision-pane" role="tabpanel">
          <div class="master-data-card">
            <div class="master-data-card-header">
              <div class="d-flex align-items-center gap-2">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(15, 23, 42, 0.1); color: #0F172A; display: flex; align-items: center; justify-content: center; font-size: 1.05rem;">
                  <i class="fa-solid fa-gavel"></i>
                </div>
                <div>
                  <h6 class="fw-bold mb-0 text-dark">Official Visa Decisions &amp; Documents</h6>
                  <div class="text-muted small" style="font-size: 0.76rem;">Consular decision records, approval certificates and receipts</div>
                </div>
              </div>
            </div>

            <?php if ($visaApproval): ?>
              <div class="card border-success shadow-2xs rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-success text-white py-3 px-3 px-md-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                  <span class="fw-bold"><i class="fa-solid fa-circle-check me-2"></i> OFFICIAL VISA APPROVED &amp; ISSUED</span>
                  <span class="badge bg-white text-success fw-bold font-monospace px-3 py-1.5 rounded-pill">Visa # <?= e($visaApproval['visa_number']) ?></span>
                </div>
                <div class="card-body p-3 p-md-4">
                  <div class="row g-2.5">
                    <div class="col-6 col-md-3">
                      <div class="master-data-item">
                        <span class="master-data-label">Issue Date</span>
                        <span class="master-data-val"><?= format_date($visaApproval['issue_date']) ?></span>
                      </div>
                    </div>
                    <div class="col-6 col-md-3">
                      <div class="master-data-item">
                        <span class="master-data-label">Expiry Date</span>
                        <span class="master-data-val text-danger fw-bold"><?= format_date($visaApproval['expiry_date']) ?></span>
                      </div>
                    </div>
                    <div class="col-6 col-md-3">
                      <div class="master-data-item">
                        <span class="master-data-label">Max Stay</span>
                        <span class="master-data-val"><?= e($visaApproval['maximum_stay'] ?? '30 Days') ?></span>
                      </div>
                    </div>
                    <div class="col-6 col-md-3">
                      <div class="master-data-item">
                        <span class="master-data-label">Validity</span>
                        <span class="master-data-val"><?= e($visaApproval['validity'] ?? '60 Days') ?></span>
                      </div>
                    </div>
                    <div class="col-12">
                      <div class="master-data-item">
                        <span class="master-data-label">Consular Guidelines</span>
                        <span class="master-data-val text-muted small"><?= e($visaApproval['approval_notes'] ?? 'Carry copy while traveling.') ?></span>
                      </div>
                    </div>
                    <?php if (!empty($visaApproval['approved_visa_file'])): ?>
                      <div class="col-12 mt-2">
                        <a href="/storage/uploads/<?= e($visaApproval['approved_visa_file']) ?>" target="_blank" class="btn btn-success rounded-pill px-4 py-2 fw-bold shadow-xs">
                          <i class="fa-solid fa-download me-2"></i> Download Official Approved Visa PDF
                        </a>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <?php if ($visaRejection): ?>
              <div class="card border-danger shadow-2xs rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-danger text-white py-3 px-3 px-md-4">
                  <span class="fw-bold"><i class="fa-solid fa-circle-xmark me-2"></i> VISA APPLICATION REJECTED</span>
                </div>
                <div class="card-body p-3 p-md-4">
                  <div class="master-data-item mb-2">
                    <span class="master-data-label text-danger">Rejection Reason</span>
                    <span class="master-data-val text-dark"><?= e($visaRejection['customer_reason']) ?></span>
                  </div>
                  <div class="text-muted small mt-2">Reapplication Eligibility: <strong><?= e($visaRejection['reapplication_eligibility']) ?></strong></div>
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($applicationReturns)): ?>
              <h6 class="fw-bold mb-2.5 text-dark"><i class="fa-solid fa-rotate-left text-warning me-2"></i> Modification Return History</h6>
              <?php foreach ($applicationReturns as $ret): ?>
                <div class="p-3 border rounded-4 bg-warning bg-opacity-10 mb-2.5 small">
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
                <a href="/payments/invoice?app_id=<?= $app['id'] ?>" target="_blank" class="btn btn-outline-dark btn-sm rounded-pill px-3 shadow-2xs">
                  <i class="fa-solid fa-file-invoice text-success me-1"></i> Official Tax Invoice
                </a>
                <?php if (!empty($appPayments)): ?>
                  <a href="/payments/receipt?id=<?= $appPayments[0]['id'] ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-2xs">
                    <i class="fa-solid fa-receipt text-primary me-1"></i> Latest Payment Receipt
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 11: Status History -->
        <div class="tab-pane fade" id="history-pane" role="tabpanel">
          <div class="master-data-card">
            <div class="master-data-card-header">
              <div class="d-flex align-items-center gap-2">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(6, 182, 212, 0.1); color: #0891B2; display: flex; align-items: center; justify-content: center; font-size: 1.05rem;">
                  <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                  <h6 class="fw-bold mb-0 text-dark">Immutable Lifecycle Stage Transition Audit</h6>
                  <div class="text-muted small" style="font-size: 0.76rem;">Historical log of every workflow state change</div>
                </div>
              </div>
            </div>

            <?php if (empty($stageHistory)): ?>
              <div class="p-5 text-center text-muted bg-light rounded-4 border">
                <i class="fa-solid fa-clock-rotate-left fa-2x mb-2 d-block opacity-25"></i>
                No stage transitions logged yet.
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table-documents-bento">
                  <thead>
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
                        <td><span class="badge bg-light text-dark border rounded-pill px-2.5 py-1"><?= e($sh['status'] ?? '—') ?></span></td>
                        <td class="text-muted small"><?= e($sh['comments'] ?? '—') ?></td>
                        <td><span class="small fw-semibold"><?= e($sh['changed_by_name'] ?? 'System') ?></span></td>
                        <td class="text-muted small"><?= format_datetime($sh['created_at']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>

            <?php if (!empty($assignmentHistory)): ?>
              <div class="mt-4 pt-3 border-top">
                <div class="d-flex align-items-center gap-2 mb-3">
                  <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(37, 99, 235, 0.1); color: #2563EB; display: flex; align-items: center; justify-content: center; font-size: 0.95rem;">
                    <i class="fa-solid fa-users-gear"></i>
                  </div>
                  <h6 class="fw-bold mb-0 text-dark">Case Officer Assignment History</h6>
                </div>
                <div class="table-responsive">
                  <table class="table-documents-bento">
                    <thead>
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
                          <td class="fw-bold text-dark"><?= e($ah['staff_name']) ?> <span class="badge bg-light text-secondary rounded-pill px-2 py-0.5 border ms-1"><?= e($ah['staff_role'] ?? 'Officer') ?></span></td>
                          <td><?= e($ah['assigned_by_name'] ?? 'System') ?></td>
                          <td class="text-muted small"><?= e($ah['notes'] ?? '—') ?></td>
                          <td class="text-muted small"><?= format_datetime($ah['assigned_at']) ?></td>
                          <td>
                            <span class="badge rounded-pill px-2.5 py-1 fw-bold <?= $ah['is_current'] ? 'bg-success' : 'bg-secondary' ?>">
                              <?= $ah['is_current'] ? 'Active Assignee' : 'Past Assignee' ?>
                            </span>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- TAB 12: Activity Log -->
        <div class="tab-pane fade" id="activity-pane" role="tabpanel">
          <div class="master-data-card">
            <div class="master-data-card-header">
              <div class="d-flex align-items-center gap-2">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(225, 29, 72, 0.1); color: #E11D48; display: flex; align-items: center; justify-content: center; font-size: 1.05rem;">
                  <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                  <h6 class="fw-bold mb-0 text-dark">System Activity &amp; Audit Trail</h6>
                  <div class="text-muted small" style="font-size: 0.76rem;">Cryptographically verified system and user event log</div>
                </div>
              </div>
            </div>

            <?php if (empty($activityLogs)): ?>
              <div class="p-5 text-center text-muted bg-light rounded-4 border">
                <i class="fa-solid fa-shield-halved fa-2x mb-2 d-block opacity-25"></i>
                No audit logs recorded for this record.
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table-documents-bento">
                  <thead>
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
                        <td><span class="badge bg-dark rounded-pill px-2.5 py-1 font-monospace"><?= e($al['action']) ?></span></td>
                        <td class="fw-semibold text-primary"><?= e($al['module']) ?></td>
                        <td class="text-dark small"><?= e($al['description']) ?></td>
                        <td><span class="small fw-semibold"><?= e($al['user_name'] ?? 'System') ?></span></td>
                        <td class="text-muted small"><?= format_datetime($al['created_at']) ?></td>
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
</div>

<!-- ==========================================================================
     ACTION MODALS
     ========================================================================== -->

<!-- 1. Stage Transition Modal -->
<div class="modal fade" id="stageTransitionModal" tabindex="-1" aria-labelledby="stageTransitionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <form action="/applications/update-stage" method="POST" id="stageTransitionForm" class="modal-content border-0 shadow" style="max-height: 90vh;">
      <?= csrf_field() ?>
      <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

      <div class="modal-header bg-primary text-white flex-shrink-0">
        <h5 class="modal-title fw-bold fs-6" id="stageTransitionModalLabel"><i class="fa-solid fa-forward-step me-2"></i> Advance Visa Lifecycle Stage</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-4" style="overflow-y: auto;">
        <div class="alert alert-info py-2 small mb-3">
          Current Stage: <strong><?= e($app['current_stage']) ?></strong> (<?= e($app['status']) ?>)
        </div>

        <div class="mb-3">
          <label class="form-label small fw-semibold text-secondary">Select Target Stage <span class="text-danger">*</span></label>
          <select name="new_stage" id="newStageSelect" class="form-select" required>
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
          <select name="new_status" id="newStatusSelect" class="form-select">
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

      <div class="modal-footer bg-light border-top flex-shrink-0">
        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" id="confirmStageUpdateBtn" class="btn btn-primary fw-semibold"><i class="fa-solid fa-check me-1"></i> Confirm Stage Update</button>
      </div>
    </form>
  </div>
</div>

<!-- 2. Approve Visa Modal -->
<div class="modal fade" id="approveVisaModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <form action="/applications/decision/approve" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow" style="max-height: 90vh;">
      <?= csrf_field() ?>
      <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

      <div class="modal-header bg-success text-white flex-shrink-0">
        <h5 class="modal-title fw-bold fs-6"><i class="fa-solid fa-circle-check me-2"></i>Official Visa Grant &amp; Approval</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-4" style="overflow-y: auto;">
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

      <div class="modal-footer bg-light border-top flex-shrink-0">
        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success fw-semibold"><i class="fa-solid fa-circle-check me-1"></i>Issue Official Approval</button>
      </div>
    </form>
  </div>
</div>

<!-- 2B. Reject Application Modal -->
<div class="modal fade" id="rejectVisaModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <form action="/applications/decision/reject" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow" style="max-height: 90vh;">
      <?= csrf_field() ?>
      <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

      <div class="modal-header bg-danger text-white flex-shrink-0">
        <h5 class="modal-title fw-bold fs-6"><i class="fa-solid fa-circle-xmark me-2"></i>Record Visa Rejection</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-4" style="overflow-y: auto;">
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

      <div class="modal-footer bg-light border-top flex-shrink-0">
        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger fw-semibold"><i class="fa-solid fa-ban me-1"></i>Confirm Rejection</button>
      </div>
    </form>
  </div>
</div>

<!-- 2C. Return for Modification Modal -->
<div class="modal fade" id="returnVisaModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <form action="/applications/decision/return" method="POST" class="modal-content border-0 shadow" style="max-height: 90vh;">
      <?= csrf_field() ?>
      <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

      <div class="modal-header bg-warning bg-opacity-10 border-bottom flex-shrink-0">
        <h5 class="modal-title fw-bold fs-6 text-dark"><i class="fa-solid fa-rotate-left text-warning me-2"></i>Return for Applicant Modification</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-4" style="overflow-y: auto;">
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

      <div class="modal-footer bg-light border-top flex-shrink-0">
        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning fw-semibold"><i class="fa-solid fa-rotate-left me-1"></i>Issue Return Notice</button>
      </div>
    </form>
  </div>
</div>

<!-- 2D. Request Additional Document Modal -->
<div class="modal fade" id="requestDocModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <form action="/applications/document-request" method="POST" class="modal-content border-0 shadow" style="max-height: 90vh;">
      <?= csrf_field() ?>
      <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

      <div class="modal-header bg-primary text-white flex-shrink-0">
        <h5 class="modal-title fw-bold fs-6"><i class="fa-solid fa-file-circle-question me-2"></i>Request Document from Applicant</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-4" style="overflow-y: auto;">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Select Document Requirement <span class="text-danger">*</span></label>
          <select name="document_type_id" class="form-select" required>
            <?php foreach ($documentChecklist as $dc): ?>
              <option value="<?= $dc['document_type_id'] ?? ($dc['id'] ?? 0) ?>"><?= e($dc['document_name'] ?? ($dc['type_name'] ?? ($dc['name'] ?? 'Document Requirement'))) ?></option>
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

      <div class="modal-footer bg-light border-top flex-shrink-0">
        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary fw-semibold"><i class="fa-solid fa-paper-plane me-1"></i>Dispatch Request</button>
      </div>
    </form>
  </div>
</div>

<!-- 2E. Log Client Communication Modal -->
<div class="modal fade" id="addCommModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <form action="/applications/communication" method="POST" class="modal-content border-0 shadow" style="max-height: 90vh;">
      <?= csrf_field() ?>
      <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

      <div class="modal-header bg-info text-dark flex-shrink-0">
        <h5 class="modal-title fw-bold fs-6"><i class="fa-solid fa-phone me-2"></i>Log Communication with Client</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-4" style="overflow-y: auto;">
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

      <div class="modal-footer bg-light border-top flex-shrink-0">
        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-info fw-semibold"><i class="fa-solid fa-save me-1"></i>Save Communication</button>
      </div>
    </form>
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
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <form action="/documents/upload" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow" style="max-height: 90vh;">
      <?= csrf_field() ?>
      <input type="hidden" name="application_id" id="appDocAppId" value="<?= $app['id'] ?>">
      <input type="hidden" name="document_type_id" id="appDocTypeId" value="">

      <div class="modal-header bg-primary text-white flex-shrink-0">
        <h5 class="modal-title fw-bold fs-6" id="appDocUploadModalLabel"><i class="fa-solid fa-cloud-arrow-up me-2"></i> Upload Document</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-4" style="overflow-y: auto;">
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

      <div class="modal-footer bg-light border-top flex-shrink-0">
        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary fw-semibold"><i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload File</button>
      </div>
    </form>
  </div>
</div>

<!-- 9. Application Document Reject Modal -->
<div class="modal fade" id="appDocRejectModal" tabindex="-1" aria-labelledby="appDocRejectModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <form action="/documents/reject" method="POST" class="modal-content border-0 shadow" style="max-height: 90vh;">
      <?= csrf_field() ?>
      <input type="hidden" name="document_id" id="appDocRejectDocId" value="">

      <div class="modal-header bg-danger text-white flex-shrink-0">
        <h5 class="modal-title fw-bold fs-6" id="appDocRejectModalLabel"><i class="fa-solid fa-circle-xmark me-2"></i> Reject Document &amp; Request Replacement</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-4" style="overflow-y: auto;">
        <div class="alert alert-warning py-2 small mb-3">
          Rejecting <strong id="appDocRejectDocName">this document</strong> will set this application to <strong>Action Required</strong>.
        </div>

        <div class="mb-3">
          <label class="form-label small fw-semibold text-secondary">Rejection Reason <span class="text-danger">*</span></label>
          <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Mandatory rejection reason (e.g. Scanned image is truncated or blurry)..."></textarea>
        </div>
      </div>

      <div class="modal-footer bg-light border-top flex-shrink-0">
        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger fw-semibold"><i class="fa-solid fa-ban me-1"></i> Confirm Rejection</button>
      </div>
    </form>
  </div>
</div>

<!-- 10. Application Document Replace Modal -->
<div class="modal fade" id="appDocReplaceModal" tabindex="-1" aria-labelledby="appDocReplaceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <form action="/documents/replace" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow" style="max-height: 90vh;">
      <?= csrf_field() ?>
      <input type="hidden" name="document_id" id="appDocReplaceDocId" value="">

      <div class="modal-header bg-primary text-white flex-shrink-0">
        <h5 class="modal-title fw-bold fs-6" id="appDocReplaceModalLabel"><i class="fa-solid fa-cloud-arrow-up me-2"></i> Upload Replacement</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-4" style="overflow-y: auto;">
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

      <div class="modal-footer bg-light border-top flex-shrink-0">
        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary fw-semibold"><i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload Replacement</button>
      </div>
    </form>
  </div>
</div>

<!-- 11. Application Document Delete Modal -->
<div class="modal fade" id="appDocDeleteModal" tabindex="-1" aria-labelledby="appDocDeleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <form action="/documents/delete" method="POST" class="modal-content border-0 shadow" style="max-height: 90vh;">
      <?= csrf_field() ?>
      <input type="hidden" name="document_id" id="appDocDeleteDocId" value="">

      <div class="modal-header bg-danger text-white flex-shrink-0">
        <h5 class="modal-title fw-bold fs-6" id="appDocDeleteModalLabel"><i class="fa-solid fa-trash-can me-2"></i> Delete Document Permanently</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-4" style="overflow-y: auto;">
        <div class="alert alert-danger py-2 small mb-3">
          <i class="fa-solid fa-triangle-exclamation me-1"></i> <strong>Warning:</strong> This action cannot be undone. The uploaded file and all version records will be permanently removed.
        </div>
        <p class="mb-2">Are you sure you want to permanently delete:</p>
        <div class="p-3 bg-light rounded border mb-2">
          <div class="fw-bold text-dark" id="appDocDeleteDocName">Document</div>
        </div>
      </div>

      <div class="modal-footer bg-light border-top flex-shrink-0">
        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger fw-semibold"><i class="fa-solid fa-trash-can me-1"></i> Delete Document</button>
      </div>
    </form>
  </div>
</div>

<script>
window.openModalById = function (modalId) {
  var el = document.getElementById(modalId);
  if (!el) {
    console.error('Modal element not found: ' + modalId);
    return;
  }
  // Move modal to body if not already
  if (el.parentNode !== document.body) {
    document.body.appendChild(el);
  }
  if (window.bootstrap && bootstrap.Modal) {
    try {
      bootstrap.Modal.getOrCreateInstance(el).show();
      return;
    } catch (err) {
      console.warn('Bootstrap modal instance failed:', err);
    }
  }
  el.classList.add('show');
  el.style.display = 'block';
  el.removeAttribute('aria-hidden');
  el.setAttribute('aria-modal', 'true');
  document.body.classList.add('modal-open');
};

// Stage transition form submission protection & instant user feedback
document.addEventListener('DOMContentLoaded', function () {
  var stageForm = document.getElementById('stageTransitionForm');
  var stageBtn = document.getElementById('confirmStageUpdateBtn');
  if (stageForm && stageBtn) {
    stageForm.addEventListener('submit', function (e) {
      if (!stageForm.checkValidity()) {
        e.preventDefault();
        stageForm.reportValidity();
        return;
      }
      stageBtn.disabled = true;
      stageBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Confirming...';
    });
  }
});

function toggleDecisionFields() {
  const dec = document.getElementById('decisionSelect')?.value;
  const appFields = document.getElementById('approvedFields');
  const rejFields = document.getElementById('rejectedFields');
  const rejInput = document.getElementById('rejectionReasonInput');

  if (dec === 'Approved') {
    appFields?.classList.remove('d-none');
    rejFields?.classList.add('d-none');
    rejInput?.removeAttribute('required');
  } else {
    appFields?.classList.add('d-none');
    rejFields?.classList.remove('d-none');
    rejInput?.setAttribute('required', 'required');
  }
}

function showStageDetails(name, state, officer, date, comments) {
  const n = document.getElementById('modalStageName'); if (n) n.innerText = name;
  const s = document.getElementById('modalStageState'); if (s) s.innerText = state;
  const o = document.getElementById('modalStageOfficer'); if (o) o.innerText = officer || 'Unassigned';
  const d = document.getElementById('modalStageDate'); if (d) d.innerText = date || 'Pending';
  const c = document.getElementById('modalStageComments'); if (c) c.innerText = comments || 'No specific notes recorded for this stage.';

  openModalById('stageDetailModal');
}

function openAppDocUploadModal(appId, typeId, typeName) {
  const a = document.getElementById('appDocAppId'); if (a) a.value = appId;
  const t = document.getElementById('appDocTypeId'); if (t) t.value = typeId;
  const d = document.getElementById('appDocTypeNameDisplay'); if (d) d.value = typeName;
  openModalById('appDocUploadModal');
}

function openAppDocRejectModal(docId, docName) {
  const d = document.getElementById('appDocRejectDocId'); if (d) d.value = docId;
  const n = document.getElementById('appDocRejectDocName'); if (n) n.innerText = docName;
  openModalById('appDocRejectModal');
}

function openAppDocReplaceModal(docId, docName) {
  const d = document.getElementById('appDocReplaceDocId'); if (d) d.value = docId;
  const n = document.getElementById('appDocReplaceDocName'); if (n) n.innerText = docName;
  openModalById('appDocReplaceModal');
}

function openAppDocDeleteModal(docId, docName) {
  const d = document.getElementById('appDocDeleteDocId'); if (d) d.value = docId;
  const n = document.getElementById('appDocDeleteDocName'); if (n) n.innerText = docName;
  openModalById('appDocDeleteModal');
}
</script>

<!-- Generate Payment Link Modal -->
<div class="modal fade" id="generateLinkModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow">
      <form action="/payments/generate-link" method="POST">
        <?= csrf_field() ?>
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

