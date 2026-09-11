<?php
$pageTitle = 'Operational Action Center — MS TRAVEL HUB';
$flash = get_flash();
$activeTab = $activeTab ?? ($_GET['tab'] ?? 'missing');
$userRole = strtolower($user['role'] ?? 'staff');
$canApproveLeave = in_array($userRole, ['super-admin', 'admin', 'branch-manager'], true);

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
        <h3 class="fw-bold brand-font text-dark mb-0">Operational Action Center</h3>
        <span class="badge bg-danger px-2 py-1 fw-bold"><i class="fa-solid fa-bolt me-1"></i>SLA Priority</span>
      </div>
      <p class="text-muted small mb-0">Comprehensive operational triage for tasks, leave approvals, verification requests, document alerts, and staff requests.</p>
    </div>

    <div class="d-flex gap-2 align-items-center">
      <!-- Leave & Request Trigger Buttons -->
      <button type="button" class="btn btn-outline-primary btn-sm px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#applyLeaveModal">
        <i class="fa-solid fa-calendar-plus me-1"></i> Apply Leave
      </button>
      <button type="button" class="btn btn-outline-info btn-sm px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#createRequestModal">
        <i class="fa-solid fa-hand-holding-hand me-1"></i> Staff Request
      </button>
      <button type="button" class="btn btn-primary btn-sm px-3 shadow fw-semibold" data-bs-toggle="modal" data-bs-target="#createTaskModal">
        <i class="fa-solid fa-plus me-1"></i> Create Task
      </button>

      <!-- My Actions vs Team Actions Switcher -->
      <div class="btn-group shadow-sm bg-white p-1 rounded border ms-2">
        <a href="/action-center?scope=my&tab=<?= e($activeTab) ?>" class="btn btn-sm <?= $scope === 'my' ? 'btn-primary' : 'btn-light text-dark' ?> px-3 fw-semibold">
          <i class="fa-solid fa-user me-1"></i> My Scope
        </a>
        <a href="/action-center?scope=team&tab=<?= e($activeTab) ?>" class="btn btn-sm <?= $scope === 'team' ? 'btn-primary' : 'btn-light text-dark' ?> px-3 fw-semibold">
          <i class="fa-solid fa-users me-1"></i> Team Scope
        </a>
      </div>
    </div>
  </div>

  <!-- Action Category Tabs -->
  <div class="card card-enterprise mb-4">
    <div class="card-header p-0 bg-white">
      <ul class="nav nav-tabs card-header-tabs m-0 px-3" role="tablist">
        <li class="nav-item">
          <button class="nav-link <?= $activeTab === 'missing' ? 'active fw-bold' : '' ?> py-3 small" data-bs-toggle="tab" data-bs-target="#tab-missing">
            <i class="fa-solid fa-file-circle-exclamation text-danger me-1"></i> Missing Docs 
            <span class="badge bg-danger rounded-pill ms-1"><?= count($missingDocuments) ?></span>
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link <?= $activeTab === 'rejected' ? 'active fw-bold' : '' ?> py-3 small" data-bs-toggle="tab" data-bs-target="#tab-rejected">
            <i class="fa-solid fa-ban text-danger me-1"></i> Rejected Docs 
            <span class="badge bg-danger rounded-pill ms-1"><?= count($rejectedDocuments) ?></span>
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link <?= $activeTab === 'pending-verif' ? 'active fw-bold' : '' ?> py-3 small" data-bs-toggle="tab" data-bs-target="#tab-pending-verif">
            <i class="fa-solid fa-check-double text-info me-1"></i> Pending Verification 
            <span class="badge bg-info rounded-pill ms-1"><?= count($pendingVerifications) ?></span>
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link <?= $activeTab === 'tasks' ? 'active fw-bold' : '' ?> py-3 small" data-bs-toggle="tab" data-bs-target="#tab-tasks">
            <i class="fa-solid fa-list-check text-primary me-1"></i> Tasks Workspace 
            <span class="badge bg-primary rounded-pill ms-1"><?= count($allTasks) ?></span>
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link <?= $activeTab === 'leave' ? 'active fw-bold' : '' ?> py-3 small" data-bs-toggle="tab" data-bs-target="#tab-leave">
            <i class="fa-solid fa-umbrella-beach text-warning me-1"></i> Staff Leaves 
            <span class="badge bg-warning text-dark rounded-pill ms-1"><?= count($leaveRequests) ?></span>
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link <?= $activeTab === 'requests' ? 'active fw-bold' : '' ?> py-3 small" data-bs-toggle="tab" data-bs-target="#tab-requests">
            <i class="fa-solid fa-hands-helping text-secondary me-1"></i> Staff Requests 
            <span class="badge bg-secondary rounded-pill ms-1"><?= count($staffRequests) ?></span>
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link <?= $activeTab === 'deadlines' ? 'active fw-bold' : '' ?> py-3 small" data-bs-toggle="tab" data-bs-target="#tab-deadlines">
            <i class="fa-solid fa-clock text-danger me-1"></i> Deadlines 
            <span class="badge bg-danger rounded-pill ms-1"><?= count($approachingDeadlines) ?></span>
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link <?= $activeTab === 'expiring-passports' ? 'active fw-bold' : '' ?> py-3 small" data-bs-toggle="tab" data-bs-target="#tab-expiring-passports">
            <i class="fa-solid fa-id-card-clip text-secondary me-1"></i> Passports 
            <span class="badge bg-secondary rounded-pill ms-1"><?= count($expiringPassports) ?></span>
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link <?= $activeTab === 'stuck' ? 'active fw-bold' : '' ?> py-3 small" data-bs-toggle="tab" data-bs-target="#tab-stuck">
            <i class="fa-solid fa-hourglass-half text-warning me-1"></i> Stuck Stages 
            <span class="badge bg-warning text-dark rounded-pill ms-1"><?= count($stuckApplications) ?></span>
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link <?= $activeTab === 'history' ? 'active fw-bold' : '' ?> py-3 small" data-bs-toggle="tab" data-bs-target="#tab-history">
            <i class="fa-solid fa-clock-rotate-left text-muted me-1"></i> Action History
          </button>
        </li>
      </ul>
    </div>

    <div class="card-body p-0">
      <div class="tab-content">
        
        <!-- QUEUE 1: MISSING MANDATORY DOCUMENTS -->
        <div class="tab-pane fade <?= $activeTab === 'missing' ? 'show active' : '' ?>" id="tab-missing" role="tabpanel">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-sm">
              <thead class="table-light">
                <tr><th>Document Requirement</th><th>Application #</th><th>Applicant</th><th>Priority</th><th>Case Officer</th><th class="text-end">Action</th></tr>
              </thead>
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
                        <div class="text-muted small"><?= e($mDoc['mobile']) ?></div>
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
        <div class="tab-pane fade <?= $activeTab === 'rejected' ? 'show active' : '' ?>" id="tab-rejected" role="tabpanel">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-sm">
              <thead class="table-light">
                <tr><th>Document Name</th><th>Rejection Reason</th><th>Application #</th><th>Customer</th><th class="text-end">Action</th></tr>
              </thead>
              <tbody>
                <?php if (empty($rejectedDocuments)): ?>
                  <tr><td colspan="5" class="text-center py-4 text-muted">No rejected document re-uploads pending.</td></tr>
                <?php else: ?>
                  <?php foreach ($rejectedDocuments as $rDoc): ?>
                    <tr>
                      <td class="fw-bold text-danger"><?= e($rDoc['doc_name']) ?></td>
                      <td><span class="text-muted small"><?= e($rDoc['rejection_reason'] ?: 'Document rejected by reviewer') ?></span></td>
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
        <div class="tab-pane fade <?= $activeTab === 'pending-verif' ? 'show active' : '' ?>" id="tab-pending-verif" role="tabpanel">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-sm">
              <thead class="table-light">
                <tr><th>Uploaded Document</th><th>Application #</th><th>Customer</th><th>Upload Type</th><th class="text-end">Action</th></tr>
              </thead>
              <tbody>
                <?php if (empty($pendingVerifications)): ?>
                  <tr><td colspan="5" class="text-center py-4 text-muted">No documents awaiting verification.</td></tr>
                <?php else: ?>
                  <?php foreach ($pendingVerifications as $pDoc): ?>
                    <tr>
                      <td>
                        <div class="fw-semibold text-dark"><i class="fa-solid fa-file text-info me-1"></i> <?= e($pDoc['doc_name']) ?></div>
                        <div class="text-muted small"><?= e($pDoc['file_name']) ?></div>
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

        <!-- TAB 4: TASKS WORKSPACE -->
        <div class="tab-pane fade <?= $activeTab === 'tasks' ? 'show active' : '' ?>" id="tab-tasks" role="tabpanel">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-sm">
              <thead class="table-light">
                <tr><th>Task Title</th><th>Application #</th><th>Customer</th><th>Assigned To</th><th>Priority</th><th>Due Date</th><th>Status</th><th class="text-end">Update</th></tr>
              </thead>
              <tbody>
                <?php if (empty($allTasks)): ?>
                  <tr><td colspan="8" class="text-center py-4 text-muted">No tasks recorded in this scope.</td></tr>
                <?php else: ?>
                  <?php foreach ($allTasks as $task): ?>
                    <?php 
                      $isOverdue = $task['status'] !== 'Completed' && $task['due_date'] < date('Y-m-d');
                    ?>
                    <tr>
                      <td>
                        <div class="fw-bold text-dark"><?= e($task['task_title']) ?></div>
                        <?php if (!empty($task['description'])): ?>
                          <div class="text-muted small text-truncate" style="max-width: 250px;"><?= e($task['description']) ?></div>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if (!empty($task['application_number'])): ?>
                          <a href="/applications/show?id=<?= $task['app_id'] ?>" class="fw-bold text-primary text-decoration-none"><?= e($task['application_number']) ?></a>
                        <?php else: ?>
                          <span class="text-muted">—</span>
                        <?php endif; ?>
                      </td>
                      <td><?= e($task['customer_name'] ?? '—') ?></td>
                      <td><span class="badge bg-light text-dark border"><?= e($task['staff_name'] ?? 'Unassigned') ?></span></td>
                      <td>
                        <span class="badge bg-<?= strtolower($task['priority']) === 'high' || strtolower($task['priority']) === 'urgent' ? 'danger' : 'secondary' ?>">
                          <?= e($task['priority']) ?>
                        </span>
                      </td>
                      <td>
                        <span class="<?= $isOverdue ? 'text-danger fw-bold' : 'text-muted' ?> small">
                          <?= format_date($task['due_date']) ?>
                          <?= $isOverdue ? '<span class="badge bg-danger ms-1">Overdue</span>' : '' ?>
                        </span>
                      </td>
                      <td>
                        <?php if ($task['status'] === 'Completed'): ?>
                          <span class="badge bg-success-subtle text-success border">Completed</span>
                        <?php elseif ($task['status'] === 'In Progress'): ?>
                          <span class="badge bg-primary-subtle text-primary border">In Progress</span>
                        <?php elseif ($task['status'] === 'Cancelled'): ?>
                          <span class="badge bg-secondary-subtle text-secondary border">Cancelled</span>
                        <?php else: ?>
                          <span class="badge bg-warning-subtle text-warning border"><?= e($task['status'] ?: 'Pending') ?></span>
                        <?php endif; ?>
                      </td>
                      <td class="text-end">
                        <form action="/tasks/status" method="POST" class="d-inline">
                          <?= csrf_field() ?>
                          <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                          <select name="status" class="form-select form-select-sm d-inline-block w-auto py-0" style="font-size: 0.75rem;" onchange="this.form.submit()">
                            <option value="Pending" <?= $task['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="In Progress" <?= $task['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="Completed" <?= $task['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="Cancelled" <?= $task['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                          </select>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 5: STAFF LEAVE REQUESTS -->
        <div class="tab-pane fade <?= $activeTab === 'leave' ? 'show active' : '' ?>" id="tab-leave" role="tabpanel">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-sm">
              <thead class="table-light">
                <tr><th>Staff Member</th><th>Leave Type</th><th>Start Date</th><th>End Date</th><th>Duration</th><th>Reason</th><th>Status</th><th>Approver</th><th class="text-end">Action</th></tr>
              </thead>
              <tbody>
                <?php if (empty($leaveRequests)): ?>
                  <tr><td colspan="9" class="text-center py-4 text-muted">No staff leave requests submitted.</td></tr>
                <?php else: ?>
                  <?php foreach ($leaveRequests as $leave): ?>
                    <tr>
                      <td>
                        <div class="fw-bold text-dark"><?= e($leave['staff_name']) ?></div>
                        <div class="text-muted small"><?= e($leave['staff_email']) ?></div>
                      </td>
                      <td><span class="badge bg-light text-dark border"><?= e($leave['leave_type']) ?></span></td>
                      <td><?= format_date($leave['start_date']) ?></td>
                      <td><?= format_date($leave['end_date']) ?></td>
                      <td><span class="fw-bold text-primary"><?= (int)$leave['total_days'] ?> Days</span></td>
                      <td style="max-width: 200px;"><span class="text-muted small text-truncate d-block" title="<?= e($leave['reason']) ?>"><?= e($leave['reason']) ?></span></td>
                      <td>
                        <?php if ($leave['status'] === 'Approved'): ?>
                          <span class="badge bg-success-subtle text-success border">Approved</span>
                        <?php elseif ($leave['status'] === 'Rejected'): ?>
                          <span class="badge bg-danger-subtle text-danger border">Rejected</span>
                        <?php else: ?>
                          <span class="badge bg-warning-subtle text-warning border">Pending</span>
                        <?php endif; ?>
                      </td>
                      <td><span class="small text-muted"><?= e($leave['approver_name'] ?? '—') ?></span></td>
                      <td class="text-end">
                        <?php if ($leave['status'] === 'Pending' && $canApproveLeave): ?>
                          <div class="btn-group btn-group-sm">
                            <form action="/action-center/leave/approve" method="POST" class="d-inline">
                              <?= csrf_field() ?>
                              <input type="hidden" name="id" value="<?= $leave['id'] ?>">
                              <input type="hidden" name="approver_notes" value="Approved by manager">
                              <button type="submit" class="btn btn-success btn-sm py-0 px-2" title="Approve Leave"><i class="fa-solid fa-check"></i></button>
                            </form>
                            <form action="/action-center/leave/reject" method="POST" class="d-inline" onsubmit="return confirm('Reject this leave request?')">
                              <?= csrf_field() ?>
                              <input type="hidden" name="id" value="<?= $leave['id'] ?>">
                              <input type="hidden" name="approver_notes" value="Declined due to operational staffing requirements">
                              <button type="submit" class="btn btn-danger btn-sm py-0 px-2" title="Reject Leave"><i class="fa-solid fa-xmark"></i></button>
                            </form>
                          </div>
                        <?php else: ?>
                          <span class="small text-muted">—</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 6: STAFF OPERATIONAL REQUESTS -->
        <div class="tab-pane fade <?= $activeTab === 'requests' ? 'show active' : '' ?>" id="tab-requests" role="tabpanel">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-sm">
              <thead class="table-light">
                <tr><th>Staff Member</th><th>Request Type</th><th>Title &amp; Description</th><th>Priority</th><th>Date</th><th>Status</th><th>Resolved By</th><th class="text-end">Action</th></tr>
              </thead>
              <tbody>
                <?php if (empty($staffRequests)): ?>
                  <tr><td colspan="8" class="text-center py-4 text-muted">No staff operational requests recorded.</td></tr>
                <?php else: ?>
                  <?php foreach ($staffRequests as $sReq): ?>
                    <tr>
                      <td><div class="fw-bold text-dark"><?= e($sReq['staff_name']) ?></div></td>
                      <td><span class="badge bg-light text-dark border"><?= e($sReq['request_type']) ?></span></td>
                      <td>
                        <div class="fw-semibold text-dark"><?= e($sReq['title']) ?></div>
                        <div class="text-muted small"><?= e($sReq['description']) ?></div>
                      </td>
                      <td><span class="badge bg-<?= strtolower($sReq['priority']) === 'high' ? 'danger' : 'secondary' ?>"><?= e($sReq['priority']) ?></span></td>
                      <td><?= date('d M Y', strtotime($sReq['created_at'])) ?></td>
                      <td>
                        <?php if ($sReq['status'] === 'Completed'): ?>
                          <span class="badge bg-success-subtle text-success border">Completed</span>
                        <?php elseif ($sReq['status'] === 'In Progress'): ?>
                          <span class="badge bg-primary-subtle text-primary border">In Progress</span>
                        <?php else: ?>
                          <span class="badge bg-warning-subtle text-warning border">Pending</span>
                        <?php endif; ?>
                      </td>
                      <td><span class="small text-muted"><?= e($sReq['resolver_name'] ?? '—') ?></span></td>
                      <td class="text-end">
                        <form action="/action-center/staff-request/update" method="POST" class="d-inline">
                          <?= csrf_field() ?>
                          <input type="hidden" name="id" value="<?= $sReq['id'] ?>">
                          <select name="status" class="form-select form-select-sm d-inline-block w-auto py-0" style="font-size: 0.75rem;" onchange="this.form.submit()">
                            <option value="Pending" <?= $sReq['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="In Progress" <?= $sReq['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="Completed" <?= $sReq['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="Rejected" <?= $sReq['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                          </select>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- QUEUE 7: APPROACHING DEADLINES -->
        <div class="tab-pane fade <?= $activeTab === 'deadlines' ? 'show active' : '' ?>" id="tab-deadlines" role="tabpanel">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-sm">
              <thead class="table-light">
                <tr><th>Application #</th><th>Applicant</th><th>Visa Service</th><th>Current Stage</th><th>Target Date</th><th class="text-end">Action</th></tr>
              </thead>
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

        <!-- QUEUE 8: EXPIRING PASSPORTS -->
        <div class="tab-pane fade <?= $activeTab === 'expiring-passports' ? 'show active' : '' ?>" id="tab-expiring-passports" role="tabpanel">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-sm">
              <thead class="table-light">
                <tr><th>Customer Name</th><th>Passport #</th><th>Expiry Date</th><th>Contact</th><th class="text-end">Action</th></tr>
              </thead>
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

        <!-- QUEUE 9: STUCK STAGES -->
        <div class="tab-pane fade <?= $activeTab === 'stuck' ? 'show active' : '' ?>" id="tab-stuck" role="tabpanel">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-sm">
              <thead class="table-light">
                <tr><th>Application #</th><th>Applicant</th><th>Current Stage</th><th>Case Officer</th><th class="text-end">Action</th></tr>
              </thead>
              <tbody>
                <?php if (empty($stuckApplications)): ?>
                  <tr><td colspan="5" class="text-center py-4 text-muted">No stagnated visa applications detected.</td></tr>
                <?php else: ?>
                  <?php foreach ($stuckApplications as $sApp): ?>
                    <tr>
                      <td><a href="/applications/show?id=<?= $sApp['id'] ?>" class="fw-bold text-primary text-decoration-none"><?= e($sApp['application_number']) ?></a></td>
                      <td><?= e($sApp['customer_name']) ?></td>
                      <td><span class="badge bg-warning-subtle text-warning fw-semibold"><?= e($sApp['current_stage']) ?></span></td>
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

        <!-- TAB 10: ACTION CENTER HISTORY -->
        <div class="tab-pane fade <?= $activeTab === 'history' ? 'show active' : '' ?>" id="tab-history" role="tabpanel">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-sm">
              <thead class="table-light">
                <tr><th>Timestamp</th><th>User</th><th>Entity</th><th>Action</th><th>Details</th></tr>
              </thead>
              <tbody>
                <?php if (empty($actionHistory)): ?>
                  <tr><td colspan="5" class="text-center py-4 text-muted">No action history logs found.</td></tr>
                <?php else: ?>
                  <?php foreach ($actionHistory as $h): ?>
                    <tr>
                      <td><?= date('d M Y, H:i A', strtotime($h['created_at'])) ?></td>
                      <td><span class="fw-semibold text-dark"><?= e($h['user_name'] ?? 'System') ?></span></td>
                      <td><span class="badge bg-light text-dark border"><?= e($h['entity_type']) ?> #<?= $h['entity_id'] ?></span></td>
                      <td><span class="badge bg-primary-subtle text-primary"><?= e($h['action']) ?></span></td>
                      <td class="small text-muted"><?= e($h['details'] ?? '—') ?></td>
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

<!-- Modal: Apply Staff Leave -->
<div class="modal fade" id="applyLeaveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-umbrella-beach text-warning me-2"></i> Apply Staff Leave</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/action-center/leave/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Leave Type <span class="text-danger">*</span></label>
            <select name="leave_type" class="form-select" required>
              <option value="Annual Leave">Annual Leave</option>
              <option value="Casual Leave">Casual Leave</option>
              <option value="Sick Leave">Sick Leave</option>
              <option value="Emergency Leave">Emergency Leave</option>
              <option value="Unpaid Leave">Unpaid Leave</option>
            </select>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Start Date <span class="text-danger">*</span></label>
              <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">End Date <span class="text-danger">*</span></label>
              <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
            </div>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Reason for Leave <span class="text-danger">*</span></label>
            <textarea name="reason" class="form-control" rows="3" placeholder="Please specify the reason for your leave request..." required></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning text-dark px-4 fw-semibold"><i class="fa-solid fa-paper-plane me-1"></i> Submit Leave Request</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Staff Request -->
<div class="modal fade" id="createRequestModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-hands-helping text-info me-2"></i> Submit Staff Request</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/action-center/staff-request/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Request Type <span class="text-danger">*</span></label>
              <select name="request_type" class="form-select" required>
                <option value="Document Assistance">Document Assistance</option>
                <option value="Payment Verification">Payment Verification</option>
                <option value="Embassy Escalation">Embassy Escalation</option>
                <option value="IT &amp; System Support">IT &amp; System Support</option>
                <option value="Administrative Support">Administrative Support</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Priority <span class="text-danger">*</span></label>
              <select name="priority" class="form-select" required>
                <option value="Normal">Normal</option>
                <option value="High">High</option>
                <option value="Urgent">Urgent</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Subject / Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" placeholder="Brief summary of request..." required>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Description &amp; Context</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Provide full details, application numbers, or client names..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-info text-white px-4 fw-semibold"><i class="fa-solid fa-paper-plane me-1"></i> Submit Request</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Create Task -->
<div class="modal fade" id="createTaskModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-list-check text-primary me-2"></i> Create Operational Task</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/tasks/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Task Title <span class="text-danger">*</span></label>
            <input type="text" name="task_title" class="form-control" placeholder="e.g. Follow up on attested birth certificate" required>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Link Application (Optional)</label>
              <select name="application_id" class="form-select">
                <option value="">-- No Application Linked --</option>
                <?php foreach ($applications as $app): ?>
                  <option value="<?= $app['id'] ?>"><?= e($app['application_number']) ?> - <?= e($app['customer_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Assign To</label>
              <select name="assigned_to" class="form-select">
                <?php foreach ($staffList as $st): ?>
                  <option value="<?= $st['id'] ?>" <?= $st['id'] == $user['id'] ? 'selected' : '' ?>><?= e($st['name']) ?> (<?= e($st['role']) ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Priority</label>
              <select name="priority" class="form-select">
                <option value="Normal">Normal</option>
                <option value="High">High</option>
                <option value="Urgent">Urgent</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Due Date</label>
              <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+2 days')) ?>">
            </div>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Task Details</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Specific instructions for assigned staff..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="fa-solid fa-save me-1"></i> Create Task</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
