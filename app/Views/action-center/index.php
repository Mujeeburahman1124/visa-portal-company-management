<?php
$pageTitle = 'Operational Action Center — MS TRAVEL HUB';
$flash = get_flash();
$activeTab = $activeTab ?? ($_GET['tab'] ?? 'missing');
$userRole = strtolower($user['role_slug'] ?? $user['role_name'] ?? 'staff');
$canApproveLeave = user_can('leave.approve') || user_has_role(['super-admin', 'admin', 'branch-manager', 'visa-manager']);
$canRejectLeave = user_can('leave.reject') || user_has_role(['super-admin', 'admin', 'branch-manager', 'visa-manager']);
$canApproveStaffRequest = user_can('staff_requests.approve') || user_has_role(['super-admin', 'admin', 'branch-manager', 'visa-manager']);
$canRejectStaffRequest = user_can('staff_requests.reject') || user_has_role(['super-admin', 'admin', 'branch-manager', 'visa-manager']);

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

    <div class="d-flex flex-wrap gap-2 align-items-center w-100 w-lg-auto justify-content-between justify-content-lg-end">
      <!-- My Actions vs Team Actions Switcher -->
      <div class="btn-group shadow-sm bg-white p-1 rounded border w-100 w-sm-auto mb-1 mb-sm-0">
        <a href="/action-center?scope=my&tab=<?= e($activeTab) ?>" id="scopeMyBtn" class="btn btn-sm <?= $scope === 'my' ? 'btn-primary' : 'btn-light text-dark' ?> px-3 fw-semibold flex-fill flex-sm-grow-0">
          <i class="fa-solid fa-user me-1"></i> My Scope
        </a>
        <a href="/action-center?scope=team&tab=<?= e($activeTab) ?>" id="scopeTeamBtn" class="btn btn-sm <?= $scope === 'team' ? 'btn-primary' : 'btn-light text-dark' ?> px-3 fw-semibold flex-fill flex-sm-grow-0">
          <i class="fa-solid fa-users me-1"></i> Team Scope
        </a>
      </div>

      <!-- Action Trigger Buttons -->
      <div class="d-flex gap-1.5 w-100 w-sm-auto">
        <button type="button" class="btn btn-outline-primary btn-sm flex-fill px-2.5 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#applyLeaveModal">
          <i class="fa-solid fa-calendar-plus me-1"></i> <span class="d-none d-sm-inline">Apply </span>Leave
        </button>
        <button type="button" class="btn btn-outline-info btn-sm flex-fill px-2.5 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#createRequestModal">
          <i class="fa-solid fa-hand-holding-hand me-1"></i> <span class="d-none d-sm-inline">Staff </span>Request
        </button>
        <button type="button" class="btn btn-primary btn-sm flex-fill px-2.5 shadow fw-semibold" data-bs-toggle="modal" data-bs-target="#createTaskModal">
          <i class="fa-solid fa-plus me-1"></i> <span class="d-none d-sm-inline">Create </span>Task
        </button>
      </div>
    </div>
  </div>

  <!-- Action Category Tabs -->
  <div class="card card-enterprise mb-4">
    <div class="card-header p-0 bg-white">
      <ul class="nav nav-tabs card-header-tabs m-0 px-2 px-md-3 flex-nowrap overflow-x-auto text-nowrap" id="actionCenterTabs" role="tablist" style="scrollbar-width: none; -ms-overflow-style: none; -webkit-overflow-scrolling: touch;">
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
          <!-- Filter and Status Pills Bar -->
          <div class="p-3 bg-light border-bottom">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
              <!-- Status Pills -->
              <div class="btn-group btn-group-sm" role="group" id="taskStatusPills">
                <button type="button" class="btn btn-outline-secondary active fw-semibold" data-status-filter="all">
                  All <span class="badge bg-secondary ms-1"><?= count($allTasks) ?></span>
                </button>
                <button type="button" class="btn btn-outline-warning text-dark fw-semibold" data-status-filter="Pending">
                  Pending <span class="badge bg-warning text-dark ms-1"><?= count(array_filter($allTasks, fn($t) => $t['status'] === 'Pending')) ?></span>
                </button>
                <button type="button" class="btn btn-outline-primary fw-semibold" data-status-filter="In Progress">
                  In Progress <span class="badge bg-primary ms-1"><?= count(array_filter($allTasks, fn($t) => $t['status'] === 'In Progress')) ?></span>
                </button>
                <button type="button" class="btn btn-outline-danger fw-semibold" data-status-filter="Overdue">
                  Overdue <span class="badge bg-danger ms-1"><?= count($overdueTasks) ?></span>
                </button>
                <button type="button" class="btn btn-outline-success fw-semibold" data-status-filter="Completed">
                  Completed <span class="badge bg-success ms-1"><?= count(array_filter($allTasks, fn($t) => $t['status'] === 'Completed')) ?></span>
                </button>
              </div>

              <!-- Action button -->
              <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                <i class="fa-solid fa-plus me-1"></i> New Task
              </button>
            </div>

            <!-- Instant Search & Dropdown Filters -->
            <div class="row g-2">
              <div class="col-md-5">
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                  <input type="text" id="taskSearchInput" class="form-control border-start-0" placeholder="Search tasks by title, application #, customer...">
                </div>
              </div>
              <div class="col-md-3">
                <select id="taskPriorityFilter" class="form-select form-select-sm">
                  <option value="">All Priorities</option>
                  <option value="Normal">Normal</option>
                  <option value="High">High</option>
                  <option value="Urgent">Urgent</option>
                </select>
              </div>
              <div class="col-md-4">
                <select id="taskStaffFilter" class="form-select form-select-sm">
                  <option value="">All Staff Members</option>
                  <?php foreach ($staffList as $st): ?>
                    <option value="<?= e($st['name']) ?>"><?= e($st['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-sm" id="tasksTable">
              <thead class="table-light">
                <tr>
                  <th>Task Title</th>
                  <th>Application #</th>
                  <th>Customer</th>
                  <th>Assigned To</th>
                  <th>Priority</th>
                  <th>Due Date</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($allTasks)): ?>
                  <tr><td colspan="8" class="text-center py-4 text-muted">No tasks recorded in this scope.</td></tr>
                <?php else: ?>
                  <?php foreach ($allTasks as $task): ?>
                    <?php 
                      $isOverdue = $task['status'] !== 'Completed' && $task['due_date'] < date('Y-m-d');
                    ?>
                    <tr class="task-row" 
                        data-status="<?= e($task['status']) ?>" 
                        data-is-overdue="<?= $isOverdue ? '1' : '0' ?>"
                        data-priority="<?= e($task['priority']) ?>"
                        data-staff="<?= e($task['staff_name'] ?? '') ?>"
                        data-search="<?= strtolower(e($task['task_title'] . ' ' . ($task['application_number'] ?? '') . ' ' . ($task['customer_name'] ?? '') . ' ' . ($task['staff_name'] ?? ''))) ?>">
                      <td>
                        <div class="fw-bold text-dark"><?= e($task['task_title']) ?></div>
                        <?php if (!empty($task['description'])): ?>
                          <div class="text-muted small text-truncate" style="max-width: 250px;"><?= e($task['description']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($task['completion_notes'])): ?>
                          <div class="text-success small fst-italic"><i class="fa-solid fa-check-double me-1"></i><?= e($task['completion_notes']) ?></div>
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
                      <td>
                        <span class="badge bg-light text-dark border"><?= e($task['staff_name'] ?? 'Unassigned') ?></span>
                        <?php if (!empty($task['created_by']) && (int)$task['created_by'] === (int)$user['id'] && (int)($task['assigned_to'] ?? 0) !== (int)$user['id']): ?>
                          <span class="badge bg-info-subtle text-info border ms-1" style="font-size: 0.65rem;" title="Created and assigned by you">Assigned by you</span>
                        <?php endif; ?>
                      </td>
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
                        <div class="d-flex align-items-center justify-content-end gap-1">
                          <!-- View Details & Comments -->
                          <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2" style="font-size: 0.75rem;" 
                                  onclick="openTaskDetailModal(<?= $task['id'] ?>, event)" title="View Details, History & Comments">
                             <i class="fa-solid fa-eye me-1"></i> Details
                          </button>
                          <!-- Reassign -->
                          <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2" style="font-size: 0.75rem;" 
                                  onclick="openReassignModal(<?= $task['id'] ?>, '<?= addslashes(e($task['task_title'])) ?>', <?= (int)$task['assigned_to'] ?>, event)" title="Reassign Task">
                            <i class="fa-solid fa-user-pen"></i>
                          </button>
                          <!-- Update Status with Notes -->
                          <button type="button" class="btn btn-outline-success btn-sm py-1 px-2" style="font-size: 0.75rem;" 
                                  onclick="openUpdateStatusModal(<?= $task['id'] ?>, '<?= addslashes(e($task['task_title'])) ?>', '<?= e($task['status']) ?>', event)" title="Update Status">
                            <i class="fa-solid fa-pen-to-square"></i>
                          </button>
                        </div>
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
                <tr><th>Staff Member</th><th>Leave Type</th><th>Start Date</th><th>End Date</th><th>Duration</th><th>Reason</th><th>Status</th><th>Approver / Notes</th><th class="text-end">Action</th></tr>
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
                      <td>
                        <div class="small fw-semibold"><?= e($leave['approver_name'] ?? '—') ?></div>
                        <?php if (!empty($leave['approver_notes'])): ?>
                          <div class="small text-muted fst-italic"><?= e($leave['approver_notes']) ?></div>
                        <?php endif; ?>
                      </td>
                      <td class="text-end">
                        <?php if ($leave['status'] === 'Pending' && ($canApproveLeave || $canRejectLeave)): ?>
                          <div class="btn-group btn-group-sm">
                            <?php if ($canApproveLeave): ?>
                              <button type="button" class="btn btn-success btn-sm py-1 px-2" 
                                      onclick="openApproveLeaveModal(<?= $leave['id'] ?>, '<?= addslashes(e($leave['staff_name'])) ?>', <?= (int)$leave['total_days'] ?>)" 
                                      title="Approve Leave">
                                <i class="fa-solid fa-check me-1"></i> Approve
                              </button>
                            <?php endif; ?>
                            <?php if ($canRejectLeave): ?>
                              <button type="button" class="btn btn-danger btn-sm py-1 px-2" 
                                      onclick="openRejectLeaveModal(<?= $leave['id'] ?>, '<?= addslashes(e($leave['staff_name'])) ?>')" 
                                      title="Reject Leave">
                                <i class="fa-solid fa-xmark me-1"></i> Reject
                              </button>
                            <?php endif; ?>
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
                <tr><th>Staff Member</th><th>Request Type</th><th>Title &amp; Description</th><th>Priority</th><th>Date</th><th>Status</th><th>Resolved By &amp; Notes</th><th class="text-end">Action</th></tr>
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
                        <?php elseif ($sReq['status'] === 'Rejected'): ?>
                          <span class="badge bg-danger-subtle text-danger border">Rejected</span>
                        <?php else: ?>
                          <span class="badge bg-warning-subtle text-warning border">Pending</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <div class="small fw-semibold"><?= e($sReq['resolver_name'] ?? '—') ?></div>
                        <?php if (!empty($sReq['resolution_notes'])): ?>
                          <div class="small text-muted fst-italic"><?= e($sReq['resolution_notes']) ?></div>
                        <?php endif; ?>
                      </td>
                      <td class="text-end">
                        <div class="btn-group btn-group-sm">
                          <?php if ($sReq['status'] === 'Pending' && $canApproveStaffRequest): ?>
                            <button type="button" class="btn btn-success btn-sm py-1 px-2" style="font-size: 0.75rem;"
                                    onclick="openUpdateStaffRequestModal(<?= $sReq['id'] ?>, '<?= addslashes(e($sReq['title'])) ?>', 'Completed', 'Approved & Completed')"
                                    title="Quick Approve">
                              <i class="fa-solid fa-check me-1"></i> Approve
                            </button>
                          <?php endif; ?>
                          <?php if ($sReq['status'] === 'Pending' && $canRejectStaffRequest): ?>
                            <button type="button" class="btn btn-danger btn-sm py-1 px-2" style="font-size: 0.75rem;"
                                    onclick="openUpdateStaffRequestModal(<?= $sReq['id'] ?>, '<?= addslashes(e($sReq['title'])) ?>', 'Rejected', 'Rejected')"
                                    title="Quick Reject">
                              <i class="fa-solid fa-xmark me-1"></i> Reject
                            </button>
                          <?php endif; ?>
                          <button type="button" class="btn btn-outline-info btn-sm py-1 px-2" style="font-size: 0.75rem;"
                                  onclick="openUpdateStaffRequestModal(<?= $sReq['id'] ?>, '<?= addslashes(e($sReq['title'])) ?>', '<?= e($sReq['status']) ?>', '<?= addslashes(e($sReq['resolution_notes'] ?? '')) ?>')"
                                  title="Update Status & Notes">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Update
                          </button>
                        </div>
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

<!-- Modal: Task Detail, Comments & Timeline -->
<div class="modal fade" id="taskDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-list-check me-2"></i> Task Details &amp; History</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4" id="taskDetailBody">
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="text-muted small mt-2">Loading task details...</p>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Reassign Task -->
<div class="modal fade" id="reassignTaskModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-pen text-primary me-2"></i> Reassign Task</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/tasks/reassign" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="task_id" id="reassignTaskId">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Task</label>
            <input type="text" id="reassignTaskTitle" class="form-control bg-light" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Assign To <span class="text-danger">*</span></label>
            <select name="assigned_to" id="reassignStaffSelect" class="form-select" required>
              <option value="">-- Select Staff Member --</option>
              <?php foreach ($staffList as $st): ?>
                <option value="<?= $st['id'] ?>"><?= e($st['name']) ?> (<?= e($st['role']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Reassignment Reason / Note</label>
            <textarea name="reason" class="form-control" rows="2" placeholder="e.g. Workload rebalancing, specialist assignment..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="fa-solid fa-check me-1"></i> Reassign Task</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Update Task Status with Completion Notes -->
<div class="modal fade" id="updateTaskStatusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square text-success me-2"></i> Update Task Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/tasks/status" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="task_id" id="statusTaskId">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Task</label>
            <input type="text" id="statusTaskTitle" class="form-control bg-light" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">New Status <span class="text-danger">*</span></label>
            <select name="status" id="statusSelect" class="form-select" required>
              <option value="Pending">Pending</option>
              <option value="In Progress">In Progress</option>
              <option value="Completed">Completed</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Completion Notes / Remarks</label>
            <textarea name="completion_notes" class="form-control" rows="3" placeholder="Add resolution notes, outcome, or follow-up details..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success px-4 fw-semibold"><i class="fa-solid fa-save me-1"></i> Save Status</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Approve Leave with Notes -->
<div class="modal fade" id="approveLeaveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-check-circle me-2"></i> Approve Staff Leave</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/action-center/leave/approve" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="approveLeaveId">
        <div class="modal-body p-4">
          <p class="mb-3 text-dark">You are approving <strong id="approveLeaveStaff"></strong>'s leave request (<span id="approveLeaveDays"></span> days).</p>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Approver Remarks (Optional)</label>
            <textarea name="approver_notes" class="form-control" rows="2" placeholder="e.g. Approved. Handover completed.">Approved by manager</textarea>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success px-4 fw-semibold"><i class="fa-solid fa-check me-1"></i> Confirm Approval</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Reject Leave with Notes -->
<div class="modal fade" id="rejectLeaveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-ban me-2"></i> Reject Staff Leave</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/action-center/leave/reject" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="rejectLeaveId">
        <div class="modal-body p-4">
          <p class="mb-3 text-dark">You are rejecting <strong id="rejectLeaveStaff"></strong>'s leave request.</p>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Rejection Reason <span class="text-danger">*</span></label>
            <textarea name="approver_notes" class="form-control" rows="2" required placeholder="State the operational reason for rejecting this leave..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger px-4 fw-semibold"><i class="fa-solid fa-ban me-1"></i> Confirm Rejection</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Update Staff Request Status & Notes -->
<div class="modal fade" id="updateStaffRequestModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i> Update Staff Request</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/action-center/staff-request/update" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="staffReqId">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Request Title</label>
            <input type="text" id="staffReqTitle" class="form-control bg-light" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
            <select name="status" id="staffReqStatus" class="form-select" required>
              <option value="Pending">Pending</option>
              <option value="In Progress">In Progress</option>
              <option value="Completed">Completed</option>
              <option value="Rejected">Rejected</option>
            </select>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Resolution Notes</label>
            <textarea name="resolution_notes" id="staffReqNotes" class="form-control" rows="3" placeholder="Provide action taken or reason..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-info text-white px-4 fw-semibold"><i class="fa-solid fa-save me-1"></i> Update Request</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Filter Pills & Live Search for Tasks Table
document.addEventListener('DOMContentLoaded', function () {
  const statusPills = document.querySelectorAll('#taskStatusPills button');
  const searchInput = document.getElementById('taskSearchInput');
  const priorityFilter = document.getElementById('taskPriorityFilter');
  const staffFilter = document.getElementById('taskStaffFilter');
  const taskRows = document.querySelectorAll('#tasksTable .task-row');

  let activeStatus = 'all';

  function applyTaskFilters() {
    const q = (searchInput ? searchInput.value.toLowerCase().trim() : '');
    const pr = (priorityFilter ? priorityFilter.value : '');
    const st = (staffFilter ? staffFilter.value : '');

    taskRows.forEach(row => {
      const rowStatus = row.getAttribute('data-status');
      const isOverdue = row.getAttribute('data-is-overdue') === '1';
      const rowPriority = row.getAttribute('data-priority');
      const rowStaff = row.getAttribute('data-staff');
      const rowSearch = row.getAttribute('data-search');

      let matchStatus = true;
      if (activeStatus === 'Overdue') {
        matchStatus = isOverdue;
      } else if (activeStatus !== 'all') {
        matchStatus = (rowStatus === activeStatus);
      }

      const matchPriority = !pr || (rowPriority === pr);
      const matchStaff = !st || (rowStaff === st);
      const matchQuery = !q || (rowSearch.includes(q));

      if (matchStatus && matchPriority && matchStaff && matchQuery) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });
  }

  if (statusPills) {
    statusPills.forEach(pill => {
      pill.addEventListener('click', function () {
        statusPills.forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        activeStatus = this.getAttribute('data-status-filter');
        applyTaskFilters();
      });
    });
  }

  if (searchInput) searchInput.addEventListener('input', applyTaskFilters);
  if (priorityFilter) priorityFilter.addEventListener('change', applyTaskFilters);
  if (staffFilter) staffFilter.addEventListener('change', applyTaskFilters);

  // Dynamic Tab and Scope synchronization
  const tabsList = document.querySelectorAll('#actionCenterTabs button[data-bs-toggle="tab"]');
  const scopeMyBtn = document.getElementById('scopeMyBtn');
  const scopeTeamBtn = document.getElementById('scopeTeamBtn');
  const currentScope = '<?= e($scope) ?>';

  tabsList.forEach(btn => {
    btn.addEventListener('shown.bs.tab', function(e) {
      const tabTarget = e.target.getAttribute('data-bs-target').replace('#tab-', '');
      if (scopeMyBtn) scopeMyBtn.href = '/action-center?scope=my&tab=' + tabTarget;
      if (scopeTeamBtn) scopeTeamBtn.href = '/action-center?scope=team&tab=' + tabTarget;
      const newUrl = window.location.pathname + '?scope=' + currentScope + '&tab=' + tabTarget;
      history.replaceState(null, '', newUrl);
    });
  });

  // Automatically activate tab from URL query param if present
  const urlParams = new URLSearchParams(window.location.search);
  const activeTabParam = urlParams.get('tab') || '<?= e($activeTab) ?>';
  if (activeTabParam && activeTabParam !== 'missing') {
    const targetTabBtn = document.querySelector('#actionCenterTabs button[data-bs-target="#tab-' + activeTabParam + '"]');
    if (targetTabBtn && !targetTabBtn.classList.contains('active')) {
      const tabTrigger = bootstrap.Tab.getOrCreateInstance(targetTabBtn);
      tabTrigger.show();
    }
  }
});

// Modal Triggers with Event Propagation Guards
function openReassignModal(id, title, staffId, evt) {
  if (evt) {
    evt.stopPropagation();
    evt.stopImmediatePropagation();
  }
  document.getElementById('reassignTaskId').value = id;
  document.getElementById('reassignTaskTitle').value = title;
  const sel = document.getElementById('reassignStaffSelect');
  if (sel) sel.value = staffId || '';
  const modalEl = document.getElementById('reassignTaskModal');
  if (modalEl) {
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  }
}

function openUpdateStatusModal(id, title, status, evt) {
  if (evt) {
    evt.stopPropagation();
    evt.stopImmediatePropagation();
  }
  document.getElementById('statusTaskId').value = id;
  document.getElementById('statusTaskTitle').value = title;
  const sel = document.getElementById('statusSelect');
  if (sel) sel.value = status || 'Completed';
  const modalEl = document.getElementById('updateTaskStatusModal');
  if (modalEl) {
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  }
}

function openApproveLeaveModal(id, staffName, days, evt) {
  if (evt) {
    evt.stopPropagation();
    evt.stopImmediatePropagation();
  }
  document.getElementById('approveLeaveId').value = id;
  document.getElementById('approveLeaveStaff').textContent = staffName;
  document.getElementById('approveLeaveDays').textContent = days;
  const modalEl = document.getElementById('approveLeaveModal');
  if (modalEl) {
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  }
}

function openRejectLeaveModal(id, staffName, evt) {
  if (evt) {
    evt.stopPropagation();
    evt.stopImmediatePropagation();
  }
  document.getElementById('rejectLeaveId').value = id;
  document.getElementById('rejectLeaveStaff').textContent = staffName;
  const modalEl = document.getElementById('rejectLeaveModal');
  if (modalEl) {
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  }
}

function openUpdateStaffRequestModal(id, title, status, notes, evt) {
  if (evt) {
    evt.stopPropagation();
    evt.stopImmediatePropagation();
  }
  document.getElementById('staffReqId').value = id;
  document.getElementById('staffReqTitle').value = title;
  document.getElementById('staffReqStatus').value = status || 'Completed';
  document.getElementById('staffReqNotes').value = notes || '';
  const modalEl = document.getElementById('updateStaffRequestModal');
  if (modalEl) {
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  }
}

// Fetch Task Details, Comments & History dynamically
function openTaskDetailModal(taskId, evt) {
  if (evt) {
    evt.stopPropagation();
    evt.stopImmediatePropagation();
  }
  const modalEl = document.getElementById('taskDetailModal');
  const bodyEl = document.getElementById('taskDetailBody');
  const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
  bsModal.show();

  bodyEl.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="text-muted small mt-2">Loading task details...</p></div>';

  fetch('/tasks/details?id=' + encodeURIComponent(taskId))
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        bodyEl.innerHTML = '<div class="alert alert-danger mb-0">' + (data.message || 'Error loading details') + '</div>';
        return;
      }
      const t = data.task;
      const comments = data.comments || [];
      const history = data.history || [];

      let html = `
        <div class="row g-3 mb-4">
          <div class="col-md-7">
            <h5 class="fw-bold text-dark mb-1">${t.task_title || ''}</h5>
            <p class="text-muted small mb-2">${t.description || '<em>No description provided</em>'}</p>
            ${t.completion_notes ? `<div class="p-2 bg-success bg-opacity-10 rounded border border-success border-opacity-25 small text-success mb-2"><strong>Completion Note:</strong> ${t.completion_notes}</div>` : ''}
          </div>
          <div class="col-md-5">
            <div class="bg-light p-3 rounded border small">
              <div><strong>Status:</strong> <span class="badge bg-${t.status === 'Completed' ? 'success' : (t.status === 'In Progress' ? 'primary' : 'warning text-dark')}">${t.status}</span></div>
              <div class="mt-1"><strong>Priority:</strong> <span class="badge bg-${t.priority === 'High' || t.priority === 'Urgent' ? 'danger' : 'secondary'}">${t.priority}</span></div>
              <div class="mt-1"><strong>Assigned To:</strong> ${t.assigned_to_name || 'Unassigned'}</div>
              <div class="mt-1"><strong>Due Date:</strong> ${t.due_date || '—'}</div>
              ${t.application_number ? `<div class="mt-1"><strong>Application:</strong> <a href="/applications/show?id=${t.app_id}" class="fw-bold text-primary">${t.application_number}</a></div>` : ''}
              ${t.customer_name ? `<div class="mt-1"><strong>Customer:</strong> ${t.customer_name}</div>` : ''}
            </div>
          </div>
        </div>

        <ul class="nav nav-tabs mb-3" id="taskDetailTabs" role="tablist">
          <li class="nav-item">
            <button class="nav-link active fw-bold small" data-bs-toggle="tab" data-bs-target="#tabModalComments">
              <i class="fa-solid fa-comments me-1"></i> Comments (${comments.length})
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link fw-bold small" data-bs-toggle="tab" data-bs-target="#tabModalHistory">
              <i class="fa-solid fa-clock-rotate-left me-1"></i> Audit History (${history.length})
            </button>
          </li>
        </ul>

        <div class="tab-content">
          <!-- Comments Pane -->
          <div class="tab-pane fade show active" id="tabModalComments">
            <form action="/tasks/comment" method="POST" class="mb-3">
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
              <input type="hidden" name="task_id" value="${t.id}">
              <div class="input-group">
                <input type="text" name="comment" class="form-control" placeholder="Write an operational comment..." required>
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-paper-plane me-1"></i> Send</button>
              </div>
            </form>

            <div class="comments-list" style="max-height: 250px; overflow-y: auto;">
              ${comments.length === 0 ? '<p class="text-muted small text-center py-3">No comments posted yet.</p>' : ''}
              ${comments.map(c => `
                <div class="p-2 mb-2 bg-light rounded border small">
                  <div class="d-flex justify-content-between text-muted" style="font-size:0.75rem;">
                    <strong>${c.user_name || 'Staff'}</strong>
                    <span>${c.created_at || ''}</span>
                  </div>
                  <div class="mt-1 text-dark">${c.comment || ''}</div>
                </div>
              `).join('')}
            </div>
          </div>

          <!-- History Pane -->
          <div class="tab-pane fade" id="tabModalHistory">
            <div class="history-list" style="max-height: 280px; overflow-y: auto;">
              ${history.length === 0 ? '<p class="text-muted small text-center py-3">No history entries recorded.</p>' : ''}
              ${history.map(h => `
                <div class="d-flex align-items-start gap-2 py-2 border-bottom small">
                  <i class="fa-solid fa-circle-dot text-primary mt-1" style="font-size:0.65rem;"></i>
                  <div class="flex-grow-1">
                    <div>
                      <span class="badge bg-secondary-subtle text-secondary me-1">${h.action}</span>
                      <strong>${h.performed_by_name || 'User'}</strong>
                      ${h.action === 'STATUS_CHANGE' ? ` changed status to <span class="fw-bold">${h.to_status}</span>` : ''}
                      ${h.action === 'REASSIGN' ? ` reassigned from <strong>${h.assigned_from_name || 'Unassigned'}</strong> to <strong>${h.assigned_to_name || 'Staff'}</strong>` : ''}
                    </div>
                    ${h.notes ? `<div class="text-muted fst-italic mt-1">${h.notes}</div>` : ''}
                    <div class="text-muted" style="font-size:0.7rem;">${h.created_at}</div>
                  </div>
                </div>
              `).join('')}
            </div>
          </div>
        </div>
      `;

      bodyEl.innerHTML = html;
    })
    .catch(err => {
      bodyEl.innerHTML = '<div class="alert alert-danger mb-0">Failed to load task details.</div>';
    });
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
