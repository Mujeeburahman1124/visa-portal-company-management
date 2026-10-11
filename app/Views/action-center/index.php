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

<!-- Load Bento Design System CSS (Referencing Dashboard & Visa Tracking UI) -->
<link rel="stylesheet" href="/assets/css/dashboard-bento.css?v=<?= time() ?>">
<link rel="stylesheet" href="/assets/css/pages/action-center.css?v=<?= time() ?>">

<div class="content-body p-0 action-center-page">
  <div class="bento-dashboard-wrap">

    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm rounded-4" role="alert">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
          <span><?= e($flash['message']) ?></span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- ─── 1. BENTO HEADER & ACTIONS ────────────────────────────────────── -->
    <header class="bento-header mb-4">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
          <h1 class="bento-header-title mb-0">Operational Action Center</h1>
          <span class="badge rounded-pill text-white fw-bold px-2.5 py-1" style="background: var(--bento-primary); font-size: 0.72rem;">
            <i class="fa-solid fa-bolt me-1"></i> SLA Priority Hub
          </span>
          <span class="badge bg-white text-secondary border rounded-pill px-2.5 py-1 small">
            <?= (int)$totalActionCount ?> items requiring triage
          </span>
        </div>
        <p class="bento-header-subtitle">
          Comprehensive operational triage for tasks, leave approvals, document alerts, verification bottlenecks, and staff requests.
        </p>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center w-100 w-xl-auto justify-content-between justify-content-xl-end">
        <!-- My Actions vs Team Actions Switcher (Segmented Pill) -->
        <div class="ac-scope-segmented" role="group" aria-label="Action Scope">
          <a href="/action-center?scope=my&tab=<?= e($activeTab) ?>" id="scopeMyBtn" class="<?= $scope === 'my' ? 'active' : '' ?>" title="Filter by assigned to me">
            <i class="fa-solid fa-user me-1"></i> My Desk
          </a>
          <a href="/action-center?scope=team&tab=<?= e($activeTab) ?>" id="scopeTeamBtn" class="<?= $scope === 'team' ? 'active' : '' ?>" title="View all team operational files">
            <i class="fa-solid fa-users me-1"></i> Team Scope
          </a>
        </div>

        <!-- Action Trigger Buttons -->
        <div class="ac-header-actions">
          <button type="button" class="bento-btn-outline" data-bs-toggle="modal" data-bs-target="#applyLeaveModal">
            <i class="fa-solid fa-calendar-plus"></i> Apply Leave
          </button>
          <button type="button" class="bento-btn-outline" data-bs-toggle="modal" data-bs-target="#createRequestModal">
            <i class="fa-solid fa-hands-helping"></i> Staff Request
          </button>
          <button type="button" class="bento-btn-primary" data-bs-toggle="modal" data-bs-target="#createTaskModal">
            <i class="fa-solid fa-plus"></i> Create Task
          </button>
        </div>
      </div>
    </header>

    <!-- ─── 2. EXECUTIVE KPI TRIAGE STRIP (DASHBOARD-INSPIRED) ─────────────── -->
    <div class="ac-kpi-grid">
      <!-- Total Triage Queue -->
      <a href="javascript:void(0)" onclick="selectActionTab('missing')" class="ac-kpi-card tone-dark">
        <div class="ac-kpi-top">
          <span class="ac-kpi-title">Total Triage</span>
          <div class="ac-kpi-icon"><i class="fa-solid fa-fire"></i></div>
        </div>
        <div class="ac-kpi-val"><?= (int)$totalActionCount ?></div>
        <span class="ac-kpi-sub">Critical operational files</span>
      </a>

      <!-- Missing Documents -->
      <a href="javascript:void(0)" onclick="selectActionTab('missing')" class="ac-kpi-card tone-danger">
        <div class="ac-kpi-top">
          <span class="ac-kpi-title">Missing Docs</span>
          <div class="ac-kpi-icon"><i class="fa-solid fa-file-circle-exclamation"></i></div>
        </div>
        <div class="ac-kpi-val"><?= count($missingDocuments) ?></div>
        <span class="ac-kpi-sub">Mandatory files pending</span>
      </a>

      <!-- Rejected Documents -->
      <a href="javascript:void(0)" onclick="selectActionTab('rejected')" class="ac-kpi-card tone-danger">
        <div class="ac-kpi-top">
          <span class="ac-kpi-title">Rejected Docs</span>
          <div class="ac-kpi-icon"><i class="fa-solid fa-ban"></i></div>
        </div>
        <div class="ac-kpi-val"><?= count($rejectedDocuments) ?></div>
        <span class="ac-kpi-sub">Re-upload required</span>
      </a>

      <!-- Pending Verification -->
      <a href="javascript:void(0)" onclick="selectActionTab('pending-verif')" class="ac-kpi-card tone-info">
        <div class="ac-kpi-top">
          <span class="ac-kpi-title">To Verify</span>
          <div class="ac-kpi-icon"><i class="fa-solid fa-check-double"></i></div>
        </div>
        <div class="ac-kpi-val"><?= count($pendingVerifications) ?></div>
        <span class="ac-kpi-sub">Awaiting officer check</span>
      </a>

      <!-- Overdue Tasks -->
      <a href="javascript:void(0)" onclick="selectActionTab('tasks')" class="ac-kpi-card tone-warning">
        <div class="ac-kpi-top">
          <span class="ac-kpi-title">Overdue Tasks</span>
          <div class="ac-kpi-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
        </div>
        <div class="ac-kpi-val"><?= count($overdueTasks) ?></div>
        <span class="ac-kpi-sub"><?= count($allTasks) ?> total active tasks</span>
      </a>

      <!-- Approaching Deadlines -->
      <a href="javascript:void(0)" onclick="selectActionTab('deadlines')" class="ac-kpi-card tone-primary">
        <div class="ac-kpi-top">
          <span class="ac-kpi-title">Deadlines &lt;48h</span>
          <div class="ac-kpi-icon"><i class="fa-solid fa-hourglass-half"></i></div>
        </div>
        <div class="ac-kpi-val"><?= count($approachingDeadlines) ?></div>
        <span class="ac-kpi-sub">SLA risk window</span>
      </a>
    </div>

    <!-- ─── 3. MODERN BENTO HORIZONTAL TAB NAV ────────────────────────────── -->
    <div class="ac-tabs-wrap">
      <ul class="ac-tabs-nav" id="actionCenterTabs" role="tablist">
        <li>
          <button class="ac-tab-btn <?= $activeTab === 'missing' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-missing">
            <i class="fa-solid fa-file-circle-exclamation text-danger"></i> Missing Docs
            <span class="badge bg-danger ms-1"><?= count($missingDocuments) ?></span>
          </button>
        </li>
        <li>
          <button class="ac-tab-btn <?= $activeTab === 'rejected' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-rejected">
            <i class="fa-solid fa-ban text-danger"></i> Rejected Docs
            <span class="badge bg-danger ms-1"><?= count($rejectedDocuments) ?></span>
          </button>
        </li>
        <li>
          <button class="ac-tab-btn <?= $activeTab === 'pending-verif' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-pending-verif">
            <i class="fa-solid fa-check-double text-info"></i> Verification Queue
            <span class="badge bg-info text-white ms-1"><?= count($pendingVerifications) ?></span>
          </button>
        </li>
        <li>
          <button class="ac-tab-btn <?= $activeTab === 'tasks' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-tasks">
            <i class="fa-solid fa-list-check text-primary"></i> Tasks Workspace
            <span class="badge bg-primary ms-1"><?= count($allTasks) ?></span>
          </button>
        </li>
        <li>
          <button class="ac-tab-btn <?= $activeTab === 'leave' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-leave">
            <i class="fa-solid fa-umbrella-beach text-warning"></i> Staff Leaves
            <span class="badge bg-warning text-dark ms-1"><?= count($leaveRequests) ?></span>
          </button>
        </li>
        <li>
          <button class="ac-tab-btn <?= $activeTab === 'requests' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-requests">
            <i class="fa-solid fa-hands-helping text-secondary"></i> Staff Requests
            <span class="badge bg-secondary ms-1"><?= count($staffRequests) ?></span>
          </button>
        </li>
        <li>
          <button class="ac-tab-btn <?= $activeTab === 'deadlines' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-deadlines">
            <i class="fa-solid fa-clock text-danger"></i> Deadlines
            <span class="badge bg-danger ms-1"><?= count($approachingDeadlines) ?></span>
          </button>
        </li>
        <li>
          <button class="ac-tab-btn <?= $activeTab === 'expiring-passports' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-expiring-passports">
            <i class="fa-solid fa-id-card text-secondary"></i> Passports
            <span class="badge bg-secondary ms-1"><?= count($expiringPassports) ?></span>
          </button>
        </li>
        <li>
          <button class="ac-tab-btn <?= $activeTab === 'stuck' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-stuck">
            <i class="fa-solid fa-hourglass-half text-warning"></i> Stuck Stages
            <span class="badge bg-warning text-dark ms-1"><?= count($stuckApplications) ?></span>
          </button>
        </li>
        <li>
          <button class="ac-tab-btn <?= $activeTab === 'history' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-history">
            <i class="fa-solid fa-clock-rotate-left text-muted"></i> History
          </button>
        </li>
      </ul>
    </div>

    <!-- ─── 4. QUEUE PANES (BENTO CARDS & ADAPTIVE DATA TABLES) ───────────── -->
    <div class="ac-content-card">
      <div class="tab-content">

        <!-- ─── QUEUE 1: MISSING MANDATORY DOCUMENTS ─────────────────────── -->
        <div class="tab-pane fade <?= $activeTab === 'missing' ? 'show active' : '' ?>" id="tab-missing" role="tabpanel">
          <div class="ac-table-wrap">
            <table class="ac-table">
              <thead>
                <tr>
                  <th style="min-width: 220px;">Document Requirement</th>
                  <th style="min-width: 150px;">Application #</th>
                  <th style="min-width: 200px;">Applicant</th>
                  <th style="min-width: 110px;">Priority</th>
                  <th style="min-width: 160px;">Case Officer</th>
                  <th class="text-end" style="min-width: 130px;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($missingDocuments)): ?>
                  <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                      <i class="fa-solid fa-circle-check fs-2 text-success opacity-50 mb-2 d-block"></i>
                      <div class="fw-bold text-dark">No missing document alerts</div>
                      <span class="small">All applications in this scope have required files attached.</span>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($missingDocuments as $mDoc): 
                    $priority = strtolower($mDoc['priority'] ?? 'normal');
                    $prioClass = ($priority === 'critical' || $priority === 'urgent') ? 'danger' : 'neutral';
                  ?>
                    <tr>
                      <td data-label="Document">
                        <span class="fw-bold text-danger d-inline-flex align-items-center gap-1.5">
                          <i class="fa-solid fa-circle-exclamation"></i> <?= e($mDoc['doc_name']) ?>
                        </span>
                      </td>
                      <td data-label="Application #">
                        <a href="/applications/show?id=<?= $mDoc['app_id'] ?>" class="ac-appno">
                          <i class="fa-solid fa-folder me-1"></i><?= e($mDoc['application_number']) ?>
                        </a>
                      </td>
                      <td data-label="Applicant">
                        <div class="fw-semibold text-dark"><?= e($mDoc['customer_name']) ?></div>
                        <div class="text-muted small"><?= e($mDoc['mobile'] ?: '—') ?></div>
                      </td>
                      <td data-label="Priority">
                        <span class="ac-chip <?= $prioClass ?>">
                          <i class="fa-solid <?= $prioClass === 'danger' ? 'fa-fire' : 'fa-circle-check' ?> me-1"></i>
                          <?= e($mDoc['priority']) ?>
                        </span>
                      </td>
                      <td data-label="Officer">
                        <span class="small text-muted fw-semibold">
                          <i class="fa-regular fa-user me-1 text-primary"></i><?= e($mDoc['staff_name'] ?? 'Unassigned') ?>
                        </span>
                      </td>
                      <td class="text-end td-actions" data-label="Action">
                        <a href="/applications/show?id=<?= $mDoc['app_id'] ?>#docs-pane" class="bento-btn-primary" style="padding: 0.35rem 0.95rem; font-size: 0.76rem;">
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

        <!-- ─── QUEUE 2: REJECTED DOCUMENTS ──────────────────────────────── -->
        <div class="tab-pane fade <?= $activeTab === 'rejected' ? 'show active' : '' ?>" id="tab-rejected" role="tabpanel">
          <div class="ac-table-wrap">
            <table class="ac-table">
              <thead>
                <tr>
                  <th style="min-width: 200px;">Document Name</th>
                  <th style="min-width: 240px;">Rejection Reason</th>
                  <th style="min-width: 150px;">Application #</th>
                  <th style="min-width: 180px;">Customer</th>
                  <th class="text-end" style="min-width: 120px;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($rejectedDocuments)): ?>
                  <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                      <i class="fa-solid fa-circle-check fs-2 text-success opacity-50 mb-2 d-block"></i>
                      <div class="fw-bold text-dark">No rejected documents pending</div>
                      <span class="small">No documents have reviewer rejection flags in this scope.</span>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($rejectedDocuments as $rDoc): ?>
                    <tr>
                      <td data-label="Document" class="fw-bold text-danger">
                        <i class="fa-solid fa-ban me-1.5"></i><?= e($rDoc['doc_name']) ?>
                      </td>
                      <td data-label="Reason">
                        <span class="text-muted small fst-italic">
                          <?= e($rDoc['rejection_reason'] ?: 'Document rejected by reviewer') ?>
                        </span>
                      </td>
                      <td data-label="Application #">
                        <a href="/applications/show?id=<?= $rDoc['app_id'] ?>" class="ac-appno">
                          <i class="fa-solid fa-folder me-1"></i><?= e($rDoc['application_number']) ?>
                        </a>
                      </td>
                      <td data-label="Customer" class="fw-semibold text-dark">
                        <?= e($rDoc['customer_name']) ?>
                      </td>
                      <td class="text-end td-actions" data-label="Action">
                        <a href="/applications/show?id=<?= $rDoc['app_id'] ?>#docs-pane" class="bento-btn-primary" style="padding: 0.35rem 0.95rem; font-size: 0.76rem; background: var(--bento-danger); border-color: var(--bento-danger);">
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

        <!-- ─── QUEUE 3: PENDING VERIFICATION ────────────────────────────── -->
        <div class="tab-pane fade <?= $activeTab === 'pending-verif' ? 'show active' : '' ?>" id="tab-pending-verif" role="tabpanel">
          <div class="ac-table-wrap">
            <table class="ac-table">
              <thead>
                <tr>
                  <th style="min-width: 220px;">Uploaded Document</th>
                  <th style="min-width: 150px;">Application #</th>
                  <th style="min-width: 180px;">Customer</th>
                  <th style="min-width: 140px;">Upload Channel</th>
                  <th class="text-end" style="min-width: 130px;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($pendingVerifications)): ?>
                  <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                      <i class="fa-solid fa-circle-check fs-2 text-success opacity-50 mb-2 d-block"></i>
                      <div class="fw-bold text-dark">Verification queue is clear</div>
                      <span class="small">All uploaded documents have been inspected and verified.</span>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($pendingVerifications as $pDoc): ?>
                    <tr>
                      <td data-label="Document">
                        <div class="fw-semibold text-dark d-flex align-items-center gap-1.5">
                          <i class="fa-solid fa-file text-info"></i> <?= e($pDoc['doc_name']) ?>
                        </div>
                        <div class="text-muted small"><?= e($pDoc['file_name']) ?></div>
                      </td>
                      <td data-label="Application #">
                        <a href="/applications/show?id=<?= $pDoc['app_id'] ?>" class="ac-appno">
                          <i class="fa-solid fa-folder me-1"></i><?= e($pDoc['application_number']) ?>
                        </a>
                      </td>
                      <td data-label="Customer" class="fw-semibold text-dark">
                        <?= e($pDoc['customer_name']) ?>
                      </td>
                      <td data-label="Channel">
                        <span class="ac-chip neutral"><?= e($pDoc['uploaded_by_type'] ?: 'Portal Upload') ?></span>
                      </td>
                      <td class="text-end td-actions" data-label="Action">
                        <a href="/documents" class="bento-btn-primary" style="padding: 0.35rem 0.95rem; font-size: 0.76rem; background: var(--bento-info); border-color: var(--bento-info);">
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

        <!-- ─── TAB 4: TASKS WORKSPACE (ENHANCED TOOLBAR & TABLE) ────────── -->
        <div class="tab-pane fade <?= $activeTab === 'tasks' ? 'show active' : '' ?>" id="tab-tasks" role="tabpanel">
          <!-- Tasks Filter Toolbar -->
          <div class="ac-tasks-toolbar">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
              <!-- Status Pills -->
              <div class="ac-task-pills" id="taskStatusPills">
                <button type="button" class="ac-task-pill active" data-status-filter="all">
                  All <span class="badge"><?= count($allTasks) ?></span>
                </button>
                <button type="button" class="ac-task-pill" data-status-filter="Pending">
                  Pending <span class="badge"><?= count(array_filter($allTasks, fn($t) => $t['status'] === 'Pending')) ?></span>
                </button>
                <button type="button" class="ac-task-pill" data-status-filter="In Progress">
                  In Progress <span class="badge"><?= count(array_filter($allTasks, fn($t) => $t['status'] === 'In Progress')) ?></span>
                </button>
                <button type="button" class="ac-task-pill text-danger" data-status-filter="Overdue">
                  Overdue <span class="badge text-white bg-danger"><?= count($overdueTasks) ?></span>
                </button>
                <button type="button" class="ac-task-pill" data-status-filter="Completed">
                  Completed <span class="badge"><?= count(array_filter($allTasks, fn($t) => $t['status'] === 'Completed')) ?></span>
                </button>
              </div>

              <!-- Action button -->
              <button type="button" class="bento-btn-primary" style="padding: 0.45rem 1.15rem; font-size: 0.8rem;" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                <i class="fa-solid fa-plus me-1"></i> New Task
              </button>
            </div>

            <!-- Instant Search & Dropdown Filters -->
            <div class="row g-2 align-items-center">
              <div class="col-12 col-md-5">
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                  <input type="text" id="taskSearchInput" class="form-control form-control-sm border-start-0 bg-light" placeholder="Search tasks by title, application #, customer...">
                </div>
              </div>
              <div class="col-6 col-md-3">
                <select id="taskPriorityFilter" class="form-select form-select-sm bg-light">
                  <option value="">All Priorities</option>
                  <option value="Normal">Normal</option>
                  <option value="High">High</option>
                  <option value="Urgent">Urgent</option>
                </select>
              </div>
              <div class="col-6 col-md-4">
                <select id="taskStaffFilter" class="form-select form-select-sm bg-light">
                  <option value="">All Staff Members</option>
                  <?php foreach ($staffList as $st): ?>
                    <option value="<?= e($st['name']) ?>"><?= e($st['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>

          <!-- Tasks Data Table -->
          <div class="ac-table-wrap">
            <table class="ac-table" id="tasksTable">
              <thead>
                <tr>
                  <th style="min-width: 220px;">Task Title</th>
                  <th style="min-width: 140px;">Application #</th>
                  <th style="min-width: 160px;">Customer</th>
                  <th style="min-width: 160px;">Assigned To</th>
                  <th style="min-width: 100px;">Priority</th>
                  <th style="min-width: 130px;">Due Date</th>
                  <th style="min-width: 110px;">Status</th>
                  <th class="text-end" style="min-width: 120px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($allTasks)): ?>
                  <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                      <i class="fa-solid fa-list-check fs-2 text-muted opacity-50 mb-2 d-block"></i>
                      <div class="fw-bold text-dark">No tasks recorded in this scope</div>
                      <span class="small">Click "+ New Task" to create your first operational assignment.</span>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($allTasks as $task): 
                    $isOverdue = $task['status'] !== 'Completed' && $task['due_date'] < date('Y-m-d');
                    $priority = strtolower($task['priority'] ?? 'normal');
                    $prioClass = ($priority === 'urgent' || $priority === 'high') ? 'danger' : 'neutral';

                    $status = $task['status'] ?? 'Pending';
                    $statusChip = 'neutral';
                    if ($status === 'Completed') $statusChip = 'success';
                    elseif ($status === 'In Progress') $statusChip = 'info';
                    elseif ($status === 'Cancelled') $statusChip = 'neutral';
                    elseif ($isOverdue) $statusChip = 'danger';
                    else $statusChip = 'warning';
                  ?>
                    <tr class="task-row" 
                        data-status="<?= e($task['status']) ?>" 
                        data-is-overdue="<?= $isOverdue ? '1' : '0' ?>"
                        data-priority="<?= e($task['priority']) ?>"
                        data-staff="<?= e($task['staff_name'] ?? '') ?>"
                        data-search="<?= strtolower(e($task['task_title'] . ' ' . ($task['application_number'] ?? '') . ' ' . ($task['customer_name'] ?? '') . ' ' . ($task['staff_name'] ?? ''))) ?>">
                      <td data-label="Task Title">
                        <div class="fw-bold text-dark"><?= e($task['task_title']) ?></div>
                        <?php if (!empty($task['description'])): ?>
                          <div class="text-muted small text-truncate" style="max-width: 250px;"><?= e($task['description']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($task['completion_notes'])): ?>
                          <div class="text-success small fst-italic mt-0.5"><i class="fa-solid fa-check-double me-1"></i><?= e($task['completion_notes']) ?></div>
                        <?php endif; ?>
                      </td>
                      <td data-label="Application #">
                        <?php if (!empty($task['application_number'])): ?>
                          <a href="/applications/show?id=<?= $task['app_id'] ?>" class="ac-appno">
                            <i class="fa-solid fa-folder me-1"></i><?= e($task['application_number']) ?>
                          </a>
                        <?php else: ?>
                          <span class="text-muted small">—</span>
                        <?php endif; ?>
                      </td>
                      <td data-label="Customer" class="fw-semibold text-dark">
                        <?= e($task['customer_name'] ?? '—') ?>
                      </td>
                      <td data-label="Assigned To">
                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                          <span class="small fw-semibold text-dark"><i class="fa-regular fa-user me-1 text-primary"></i><?= e($task['staff_name'] ?? 'Unassigned') ?></span>
                          <?php if (!empty($task['created_by']) && (int)$task['created_by'] === (int)$user['id'] && (int)($task['assigned_to'] ?? 0) !== (int)$user['id']): ?>
                            <span class="badge bg-info-subtle text-info border rounded-pill" style="font-size: 0.65rem;">Assigned by you</span>
                          <?php endif; ?>
                        </div>
                      </td>
                      <td data-label="Priority">
                        <span class="ac-chip <?= $prioClass ?>">
                          <?= e($task['priority']) ?>
                        </span>
                      </td>
                      <td data-label="Due Date">
                        <span class="<?= $isOverdue ? 'text-danger fw-bold' : 'text-muted' ?> small">
                          <?= format_date($task['due_date']) ?>
                          <?= $isOverdue ? '<span class="badge bg-danger rounded-pill ms-1">Overdue</span>' : '' ?>
                        </span>
                      </td>
                      <td data-label="Status">
                        <span class="ac-chip <?= $statusChip ?>">
                          <?= e($task['status'] ?: 'Pending') ?>
                        </span>
                      </td>
                      <td class="text-end td-actions" data-label="Actions">
                        <div class="d-flex align-items-center justify-content-end gap-1">
                          <button type="button" class="ac-icon-btn primary" onclick="openTaskDetailModal(<?= $task['id'] ?>, event)" title="View Details, History & Comments">
                            <i class="fa-solid fa-eye"></i>
                          </button>
                          <button type="button" class="ac-icon-btn" onclick="openReassignModal(<?= $task['id'] ?>, '<?= addslashes(e($task['task_title'])) ?>', <?= (int)$task['assigned_to'] ?>, event)" title="Reassign Task">
                            <i class="fa-solid fa-user-pen"></i>
                          </button>
                          <button type="button" class="ac-icon-btn success" onclick="openUpdateStatusModal(<?= $task['id'] ?>, '<?= addslashes(e($task['task_title'])) ?>', '<?= e($task['status']) ?>', event)" title="Update Status">
                            <i class="fa-solid fa-check"></i>
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

        <!-- ─── TAB 5: STAFF LEAVE REQUESTS ──────────────────────────────── -->
        <div class="tab-pane fade <?= $activeTab === 'leave' ? 'show active' : '' ?>" id="tab-leave" role="tabpanel">
          <div class="ac-table-wrap">
            <table class="ac-table">
              <thead>
                <tr>
                  <th style="min-width: 180px;">Staff Member</th>
                  <th style="min-width: 130px;">Leave Type</th>
                  <th style="min-width: 120px;">Start Date</th>
                  <th style="min-width: 120px;">End Date</th>
                  <th style="min-width: 100px;">Duration</th>
                  <th style="min-width: 200px;">Reason</th>
                  <th style="min-width: 110px;">Status</th>
                  <th style="min-width: 160px;">Approver / Notes</th>
                  <th class="text-end" style="min-width: 120px;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($leaveRequests)): ?>
                  <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                      <i class="fa-solid fa-umbrella-beach fs-2 text-muted opacity-50 mb-2 d-block"></i>
                      <div class="fw-bold text-dark">No leave requests submitted</div>
                      <span class="small">Click "Apply Leave" to submit a new leave booking.</span>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($leaveRequests as $leave): 
                    $leaveStatus = $leave['status'] ?? 'Pending';
                    $lChip = 'warning';
                    if ($leaveStatus === 'Approved') $lChip = 'success';
                    elseif ($leaveStatus === 'Rejected') $lChip = 'danger';
                  ?>
                    <tr>
                      <td data-label="Staff">
                        <div class="fw-bold text-dark"><?= e($leave['staff_name']) ?></div>
                        <div class="text-muted small"><?= e($leave['staff_email']) ?></div>
                      </td>
                      <td data-label="Type">
                        <span class="ac-chip neutral"><?= e($leave['leave_type']) ?></span>
                      </td>
                      <td data-label="Start"><?= format_date($leave['start_date']) ?></td>
                      <td data-label="End"><?= format_date($leave['end_date']) ?></td>
                      <td data-label="Duration">
                        <span class="fw-bold text-primary"><?= (int)$leave['total_days'] ?> Days</span>
                      </td>
                      <td data-label="Reason">
                        <span class="text-muted small text-truncate d-block" style="max-width: 200px;" title="<?= e($leave['reason']) ?>">
                          <?= e($leave['reason']) ?>
                        </span>
                      </td>
                      <td data-label="Status">
                        <span class="ac-chip <?= $lChip ?>"><?= e($leave['status']) ?></span>
                      </td>
                      <td data-label="Approver">
                        <div class="small fw-semibold text-dark"><?= e($leave['approver_name'] ?? '—') ?></div>
                        <?php if (!empty($leave['approver_notes'])): ?>
                          <div class="small text-muted fst-italic"><?= e($leave['approver_notes']) ?></div>
                        <?php endif; ?>
                      </td>
                      <td class="text-end td-actions" data-label="Action">
                        <?php if ($leave['status'] === 'Pending' && ($canApproveLeave || $canRejectLeave)): ?>
                          <div class="d-flex align-items-center justify-content-end gap-1">
                            <?php if ($canApproveLeave): ?>
                              <button type="button" class="ac-icon-btn success" onclick="openApproveLeaveModal(<?= $leave['id'] ?>, '<?= addslashes(e($leave['staff_name'])) ?>', <?= (int)$leave['total_days'] ?>)" title="Approve Leave">
                                <i class="fa-solid fa-check"></i>
                              </button>
                            <?php endif; ?>
                            <?php if ($canRejectLeave): ?>
                              <button type="button" class="ac-icon-btn danger" onclick="openRejectLeaveModal(<?= $leave['id'] ?>, '<?= addslashes(e($leave['staff_name'])) ?>')" title="Reject Leave">
                                <i class="fa-solid fa-xmark"></i>
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

        <!-- ─── TAB 6: STAFF OPERATIONAL REQUESTS ────────────────────────── -->
        <div class="tab-pane fade <?= $activeTab === 'requests' ? 'show active' : '' ?>" id="tab-requests" role="tabpanel">
          <div class="ac-table-wrap">
            <table class="ac-table">
              <thead>
                <tr>
                  <th style="min-width: 170px;">Staff Member</th>
                  <th style="min-width: 140px;">Request Type</th>
                  <th style="min-width: 220px;">Title &amp; Description</th>
                  <th style="min-width: 100px;">Priority</th>
                  <th style="min-width: 120px;">Date</th>
                  <th style="min-width: 110px;">Status</th>
                  <th style="min-width: 160px;">Resolved By</th>
                  <th class="text-end" style="min-width: 130px;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($staffRequests)): ?>
                  <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                      <i class="fa-solid fa-hands-helping fs-2 text-muted opacity-50 mb-2 d-block"></i>
                      <div class="fw-bold text-dark">No operational requests recorded</div>
                      <span class="small">Click "Staff Request" to submit an escalation or support query.</span>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($staffRequests as $sReq): 
                    $reqStatus = $sReq['status'] ?? 'Pending';
                    $rChip = 'warning';
                    if ($reqStatus === 'Completed') $rChip = 'success';
                    elseif ($reqStatus === 'In Progress') $rChip = 'info';
                    elseif ($reqStatus === 'Rejected') $rChip = 'danger';
                  ?>
                    <tr>
                      <td data-label="Staff" class="fw-bold text-dark"><?= e($sReq['staff_name']) ?></td>
                      <td data-label="Type"><span class="ac-chip neutral"><?= e($sReq['request_type']) ?></span></td>
                      <td data-label="Subject">
                        <div class="fw-semibold text-dark"><?= e($sReq['title']) ?></div>
                        <div class="text-muted small text-truncate" style="max-width: 250px;"><?= e($sReq['description'] ?: '—') ?></div>
                      </td>
                      <td data-label="Priority">
                        <span class="ac-chip <?= strtolower($sReq['priority'] ?? '') === 'high' || strtolower($sReq['priority'] ?? '') === 'urgent' ? 'danger' : 'neutral' ?>">
                          <?= e($sReq['priority']) ?>
                        </span>
                      </td>
                      <td data-label="Date" class="text-muted small"><?= date('d M Y', strtotime($sReq['created_at'])) ?></td>
                      <td data-label="Status">
                        <span class="ac-chip <?= $rChip ?>"><?= e($sReq['status']) ?></span>
                      </td>
                      <td data-label="Resolved By">
                        <div class="small fw-semibold text-dark"><?= e($sReq['resolver_name'] ?? '—') ?></div>
                        <?php if (!empty($sReq['resolution_notes'])): ?>
                          <div class="small text-muted fst-italic"><?= e($sReq['resolution_notes']) ?></div>
                        <?php endif; ?>
                      </td>
                      <td class="text-end td-actions" data-label="Action">
                        <div class="d-flex align-items-center justify-content-end gap-1">
                          <?php if ($sReq['status'] === 'Pending' && $canApproveStaffRequest): ?>
                            <button type="button" class="ac-icon-btn success" onclick="openUpdateStaffRequestModal(<?= $sReq['id'] ?>, '<?= addslashes(e($sReq['title'])) ?>', 'Completed', 'Approved & Completed')" title="Quick Approve">
                              <i class="fa-solid fa-check"></i>
                            </button>
                          <?php endif; ?>
                          <?php if ($sReq['status'] === 'Pending' && $canRejectStaffRequest): ?>
                            <button type="button" class="ac-icon-btn danger" onclick="openUpdateStaffRequestModal(<?= $sReq['id'] ?>, '<?= addslashes(e($sReq['title'])) ?>', 'Rejected', 'Rejected')" title="Quick Reject">
                              <i class="fa-solid fa-xmark"></i>
                            </button>
                          <?php endif; ?>
                          <button type="button" class="ac-icon-btn" onclick="openUpdateStaffRequestModal(<?= $sReq['id'] ?>, '<?= addslashes(e($sReq['title'])) ?>', '<?= e($sReq['status']) ?>', '<?= addslashes(e($sReq['resolution_notes'] ?? '')) ?>')" title="Update Status & Notes">
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

        <!-- ─── QUEUE 7: APPROACHING DEADLINES ───────────────────────────── -->
        <div class="tab-pane fade <?= $activeTab === 'deadlines' ? 'show active' : '' ?>" id="tab-deadlines" role="tabpanel">
          <div class="ac-table-wrap">
            <table class="ac-table">
              <thead>
                <tr>
                  <th style="min-width: 150px;">Application #</th>
                  <th style="min-width: 180px;">Applicant</th>
                  <th style="min-width: 200px;">Visa Service</th>
                  <th style="min-width: 160px;">Current Stage</th>
                  <th style="min-width: 130px;">Target Date</th>
                  <th class="text-end" style="min-width: 120px;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($approachingDeadlines)): ?>
                  <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                      <i class="fa-solid fa-clock fs-2 text-success opacity-50 mb-2 d-block"></i>
                      <div class="fw-bold text-dark">No SLA deadlines imminent</div>
                      <span class="small">All applications in this scope are running within acceptable turnaround windows.</span>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($approachingDeadlines as $aDl): ?>
                    <tr>
                      <td data-label="Application #">
                        <a href="/applications/show?id=<?= $aDl['id'] ?>" class="ac-appno">
                          <i class="fa-solid fa-folder me-1"></i><?= e($aDl['application_number']) ?>
                        </a>
                      </td>
                      <td data-label="Applicant" class="fw-semibold text-dark">
                        <?= e($aDl['customer_name']) ?>
                      </td>
                      <td data-label="Service" class="text-muted small">
                        <?= e($aDl['service_name']) ?>
                      </td>
                      <td data-label="Stage">
                        <span class="ac-chip neutral">
                          <i class="fa-solid fa-circle-dot text-primary me-1" style="font-size: 0.55rem;"></i>
                          <?= e($aDl['current_stage']) ?>
                        </span>
                      </td>
                      <td data-label="Target Date">
                        <span class="text-danger fw-bold small">
                          <i class="fa-regular fa-clock me-1"></i><?= format_date($aDl['expected_completion_date']) ?>
                        </span>
                      </td>
                      <td class="text-end td-actions" data-label="Action">
                        <a href="/applications/show?id=<?= $aDl['id'] ?>" class="bento-btn-primary" style="padding: 0.35rem 0.95rem; font-size: 0.76rem;">
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

        <!-- ─── QUEUE 8: EXPIRING PASSPORTS ──────────────────────────────── -->
        <div class="tab-pane fade <?= $activeTab === 'expiring-passports' ? 'show active' : '' ?>" id="tab-expiring-passports" role="tabpanel">
          <div class="ac-table-wrap">
            <table class="ac-table">
              <thead>
                <tr>
                  <th style="min-width: 180px;">Customer Name</th>
                  <th style="min-width: 160px;">Passport #</th>
                  <th style="min-width: 130px;">Expiry Date</th>
                  <th style="min-width: 160px;">Contact Phone</th>
                  <th class="text-end" style="min-width: 130px;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($expiringPassports)): ?>
                  <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                      <i class="fa-solid fa-id-card fs-2 text-success opacity-50 mb-2 d-block"></i>
                      <div class="fw-bold text-dark">No passports expiring soon</div>
                      <span class="small">No client passports expire within the next 90 days.</span>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($expiringPassports as $ePass): ?>
                    <tr>
                      <td data-label="Customer" class="fw-bold text-dark">
                        <?= e($ePass['customer_name'] ?? $ePass['full_name'] ?? 'Customer') ?>
                      </td>
                      <td data-label="Passport #">
                        <span class="badge bg-light text-secondary border font-monospace px-2 py-1" style="font-size: 0.76rem;">
                          <i class="fa-solid fa-passport text-primary me-1"></i><?= e($ePass['passport_number']) ?>
                        </span>
                      </td>
                      <td data-label="Expiry Date" class="text-danger fw-bold small">
                        <?= format_date($ePass['expiry_date']) ?>
                      </td>
                      <td data-label="Contact" class="small text-muted">
                        <?= e($ePass['customer_mobile'] ?? $ePass['mobile'] ?? '—') ?>
                      </td>
                      <td class="text-end td-actions" data-label="Action">
                        <a href="/customers/show?id=<?= $ePass['customer_id'] ?? $ePass['cust_id'] ?>" class="bento-btn-outline" style="padding: 0.35rem 0.95rem; font-size: 0.76rem;">
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

        <!-- ─── QUEUE 9: STUCK STAGES ────────────────────────────────────── -->
        <div class="tab-pane fade <?= $activeTab === 'stuck' ? 'show active' : '' ?>" id="tab-stuck" role="tabpanel">
          <div class="ac-table-wrap">
            <table class="ac-table">
              <thead>
                <tr>
                  <th style="min-width: 150px;">Application #</th>
                  <th style="min-width: 180px;">Applicant</th>
                  <th style="min-width: 160px;">Stagnated Stage</th>
                  <th style="min-width: 160px;">Case Officer</th>
                  <th class="text-end" style="min-width: 120px;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($stuckApplications)): ?>
                  <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                      <i class="fa-solid fa-hourglass-half fs-2 text-success opacity-50 mb-2 d-block"></i>
                      <div class="fw-bold text-dark">No stagnated visa applications</div>
                      <span class="small">All applications have transitioned through stages within normal velocity.</span>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($stuckApplications as $sApp): ?>
                    <tr>
                      <td data-label="Application #">
                        <a href="/applications/show?id=<?= $sApp['id'] ?>" class="ac-appno">
                          <i class="fa-solid fa-folder me-1"></i><?= e($sApp['application_number']) ?>
                        </a>
                      </td>
                      <td data-label="Applicant" class="fw-semibold text-dark">
                        <?= e($sApp['customer_name']) ?>
                      </td>
                      <td data-label="Stage">
                        <span class="ac-chip warning">
                          <i class="fa-solid fa-triangle-exclamation me-1"></i><?= e($sApp['current_stage']) ?>
                        </span>
                      </td>
                      <td data-label="Officer" class="small text-muted fw-semibold">
                        <i class="fa-regular fa-user me-1 text-primary"></i><?= e($sApp['staff_name'] ?? 'Unassigned') ?>
                      </td>
                      <td class="text-end td-actions" data-label="Action">
                        <a href="/applications/show?id=<?= $sApp['id'] ?>" class="bento-btn-primary" style="padding: 0.35rem 0.95rem; font-size: 0.76rem;">
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

        <!-- ─── TAB 10: ACTION CENTER HISTORY ────────────────────────────── -->
        <div class="tab-pane fade <?= $activeTab === 'history' ? 'show active' : '' ?>" id="tab-history" role="tabpanel">
          <div class="ac-table-wrap">
            <table class="ac-table">
              <thead>
                <tr>
                  <th style="min-width: 160px;">Timestamp</th>
                  <th style="min-width: 150px;">User</th>
                  <th style="min-width: 160px;">Entity</th>
                  <th style="min-width: 140px;">Action</th>
                  <th style="min-width: 250px;">Details</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($actionHistory)): ?>
                  <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                      <i class="fa-solid fa-clock-rotate-left fs-2 text-muted opacity-50 mb-2 d-block"></i>
                      <div class="fw-bold text-dark">No operational history logged</div>
                      <span class="small">Activity logs for actions taken will appear here automatically.</span>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($actionHistory as $h): ?>
                    <tr>
                      <td data-label="Time" class="text-muted small"><?= date('d M Y, H:i A', strtotime($h['created_at'])) ?></td>
                      <td data-label="User"><span class="fw-semibold text-dark"><?= e($h['user_name'] ?? 'System') ?></span></td>
                      <td data-label="Entity"><span class="ac-chip neutral"><?= e($h['entity_type']) ?> #<?= $h['entity_id'] ?></span></td>
                      <td data-label="Action"><span class="ac-chip primary"><?= e($h['action']) ?></span></td>
                      <td data-label="Details" class="small text-muted"><?= e($h['details'] ?? '—') ?></td>
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

<!-- ─── MODAL 1: APPLY STAFF LEAVE ──────────────────────────────────────── -->
<div class="modal fade" id="applyLeaveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom py-3 px-4">
        <h5 class="modal-title fw-bold fs-6">
          <i class="fa-solid fa-umbrella-beach text-warning me-2"></i> Apply Staff Leave
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/action-center/leave/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Leave Type <span class="text-danger">*</span></label>
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
              <label class="form-label small fw-semibold text-secondary">Start Date <span class="text-danger">*</span></label>
              <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold text-secondary">End Date <span class="text-danger">*</span></label>
              <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
            </div>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Reason for Leave <span class="text-danger">*</span></label>
            <textarea name="reason" class="form-control" rows="3" placeholder="Please specify the reason for your leave request..." required></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light border-top py-2.5 px-4">
          <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.25rem; font-size: 0.82rem; background: var(--bento-warning); border-color: var(--bento-warning); color: #0F172A !important;">
            <i class="fa-solid fa-paper-plane me-1"></i> Submit Leave Request
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ─── MODAL 2: STAFF OPERATIONAL REQUEST ──────────────────────────────── -->
<div class="modal fade" id="createRequestModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom py-3 px-4">
        <h5 class="modal-title fw-bold fs-6">
          <i class="fa-solid fa-hands-helping text-info me-2"></i> Submit Staff Request
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/action-center/staff-request/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Request Type <span class="text-danger">*</span></label>
              <select name="request_type" class="form-select" required>
                <option value="Document Assistance">Document Assistance</option>
                <option value="Payment Verification">Payment Verification</option>
                <option value="Embassy Escalation">Embassy Escalation</option>
                <option value="IT &amp; System Support">IT &amp; System Support</option>
                <option value="Administrative Support">Administrative Support</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Priority <span class="text-danger">*</span></label>
              <select name="priority" class="form-select" required>
                <option value="Normal">Normal</option>
                <option value="High">High</option>
                <option value="Urgent">Urgent</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Subject / Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" placeholder="Brief summary of request..." required>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Description &amp; Context</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Provide full details, application numbers, or client names..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light border-top py-2.5 px-4">
          <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.25rem; font-size: 0.82rem; background: var(--bento-info); border-color: var(--bento-info);">
            <i class="fa-solid fa-paper-plane me-1"></i> Submit Request
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ─── MODAL 3: CREATE TASK ────────────────────────────────────────────── -->
<div class="modal fade" id="createTaskModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom py-3 px-4">
        <h5 class="modal-title fw-bold fs-6">
          <i class="fa-solid fa-list-check text-primary me-2"></i> Create Operational Task
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/tasks/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Task Title <span class="text-danger">*</span></label>
            <input type="text" name="task_title" class="form-control" placeholder="e.g. Follow up on attested birth certificate" required>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Link Application (Optional)</label>
              <select name="application_id" class="form-select">
                <option value="">-- No Application Linked --</option>
                <?php foreach ($applications as $app): ?>
                  <option value="<?= $app['id'] ?>"><?= e($app['application_number']) ?> - <?= e($app['customer_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Assign To</label>
              <select name="assigned_to" class="form-select">
                <?php foreach ($staffList as $st): ?>
                  <option value="<?= $st['id'] ?>" <?= $st['id'] == $user['id'] ? 'selected' : '' ?>><?= e($st['name']) ?> (<?= e($st['role']) ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Priority</label>
              <select name="priority" class="form-select">
                <option value="Normal">Normal</option>
                <option value="High">High</option>
                <option value="Urgent">Urgent</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Due Date</label>
              <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+2 days')) ?>">
            </div>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Task Details</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Specific instructions for assigned staff..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light border-top py-2.5 px-4">
          <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.25rem; font-size: 0.82rem;">
            <i class="fa-solid fa-save me-1"></i> Create Task
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ─── MODAL 4: TASK DETAIL, COMMENTS & TIMELINE ───────────────────────── -->
<div class="modal fade" id="taskDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-dark text-white py-3 px-4">
        <h5 class="modal-title fw-bold fs-6">
          <i class="fa-solid fa-list-check me-2"></i> Task Details &amp; History
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4" id="taskDetailBody">
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="text-muted small mt-2">Loading task details...</p>
        </div>
      </div>
      <div class="modal-footer bg-light border-top py-2.5 px-4">
        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- ─── MODAL 5: REASSIGN TASK ─────────────────────────────────────────── -->
<div class="modal fade" id="reassignTaskModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom py-3 px-4">
        <h5 class="modal-title fw-bold fs-6">
          <i class="fa-solid fa-user-pen text-primary me-2"></i> Reassign Task
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/tasks/reassign" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="task_id" id="reassignTaskId">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Task</label>
            <input type="text" id="reassignTaskTitle" class="form-control bg-light" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Assign To <span class="text-danger">*</span></label>
            <select name="assigned_to" id="reassignStaffSelect" class="form-select" required>
              <option value="">-- Select Staff Member --</option>
              <?php foreach ($staffList as $st): ?>
                <option value="<?= $st['id'] ?>"><?= e($st['name']) ?> (<?= e($st['role']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Reassignment Reason / Note</label>
            <textarea name="reason" class="form-control" rows="2" placeholder="e.g. Workload rebalancing, specialist assignment..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light border-top py-2.5 px-4">
          <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.25rem; font-size: 0.82rem;">
            <i class="fa-solid fa-check me-1"></i> Confirm Reassign
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ─── MODAL 6: UPDATE TASK STATUS ────────────────────────────────────── -->
<div class="modal fade" id="updateTaskStatusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom py-3 px-4">
        <h5 class="modal-title fw-bold fs-6">
          <i class="fa-solid fa-pen-to-square text-success me-2"></i> Update Task Status
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="/tasks/status" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="task_id" id="statusTaskId">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Task</label>
            <input type="text" id="statusTaskTitle" class="form-control bg-light" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">New Status <span class="text-danger">*</span></label>
            <select name="status" id="statusSelect" class="form-select" required>
              <option value="Pending">Pending</option>
              <option value="In Progress">In Progress</option>
              <option value="Completed">Completed</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Completion Notes / Remarks</label>
            <textarea name="completion_notes" class="form-control" rows="3" placeholder="Add resolution notes, outcome, or follow-up details..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light border-top py-2.5 px-4">
          <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.25rem; font-size: 0.82rem; background: var(--bento-success); border-color: var(--bento-success);">
            <i class="fa-solid fa-save me-1"></i> Save Status
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ─── MODAL 7: APPROVE LEAVE ─────────────────────────────────────────── -->
<div class="modal fade" id="approveLeaveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white py-3 px-4">
        <h5 class="modal-title fw-bold fs-6">
          <i class="fa-solid fa-circle-check me-2"></i> Approve Staff Leave
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/action-center/leave/approve" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="approveLeaveId">
        <div class="modal-body p-4">
          <p class="mb-3 text-dark">You are approving <strong id="approveLeaveStaff"></strong>'s leave request (<span id="approveLeaveDays" class="fw-bold text-success"></span> days).</p>
          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Approver Remarks (Optional)</label>
            <textarea name="approver_notes" class="form-control" rows="2" placeholder="e.g. Approved. Handover completed.">Approved by manager</textarea>
          </div>
        </div>
        <div class="modal-footer bg-light border-top py-2.5 px-4">
          <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.25rem; font-size: 0.82rem; background: var(--bento-success); border-color: var(--bento-success);">
            <i class="fa-solid fa-check me-1"></i> Confirm Approval
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ─── MODAL 8: REJECT LEAVE ──────────────────────────────────────────── -->
<div class="modal fade" id="rejectLeaveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white py-3 px-4">
        <h5 class="modal-title fw-bold fs-6">
          <i class="fa-solid fa-ban me-2"></i> Reject Staff Leave
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/action-center/leave/reject" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="rejectLeaveId">
        <div class="modal-body p-4">
          <p class="mb-3 text-dark">You are rejecting <strong id="rejectLeaveStaff"></strong>'s leave request.</p>
          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Rejection Reason <span class="text-danger">*</span></label>
            <textarea name="approver_notes" class="form-control" rows="2" required placeholder="State the operational reason for rejecting this leave..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light border-top py-2.5 px-4">
          <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.25rem; font-size: 0.82rem; background: var(--bento-danger); border-color: var(--bento-danger);">
            <i class="fa-solid fa-ban me-1"></i> Confirm Rejection
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ─── MODAL 9: UPDATE STAFF REQUEST ──────────────────────────────────── -->
<div class="modal fade" id="updateStaffRequestModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-info text-white py-3 px-4">
        <h5 class="modal-title fw-bold fs-6">
          <i class="fa-solid fa-pen-to-square me-2"></i> Update Staff Request
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/action-center/staff-request/update" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="staffReqId">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Request Title</label>
            <input type="text" id="staffReqTitle" class="form-control bg-light" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Status <span class="text-danger">*</span></label>
            <select name="status" id="staffReqStatus" class="form-select" required>
              <option value="Pending">Pending</option>
              <option value="In Progress">In Progress</option>
              <option value="Completed">Completed</option>
              <option value="Rejected">Rejected</option>
            </select>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Resolution Notes</label>
            <textarea name="resolution_notes" id="staffReqNotes" class="form-control" rows="3" placeholder="Provide action taken or reason..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light border-top py-2.5 px-4">
          <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.25rem; font-size: 0.82rem; background: var(--bento-info); border-color: var(--bento-info);">
            <i class="fa-solid fa-save me-1"></i> Update Request
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Filter Pills & Live Search for Tasks Table
document.addEventListener('DOMContentLoaded', function () {
  const statusPills = document.querySelectorAll('#taskStatusPills .ac-task-pill');
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
      tabsList.forEach(b => b.classList.remove('active'));
      e.target.classList.add('active');
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
    selectActionTab(activeTabParam);
  }
});

function selectActionTab(tabKey) {
  const targetTabBtn = document.querySelector('#actionCenterTabs button[data-bs-target="#tab-' + tabKey + '"]');
  if (targetTabBtn) {
    const tabTrigger = bootstrap.Tab.getOrCreateInstance(targetTabBtn);
    tabTrigger.show();
    targetTabBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
  }
}

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
            ${t.completion_notes ? `<div class="p-2.5 bg-success bg-opacity-10 rounded-3 border border-success border-opacity-25 small text-success mb-2"><strong>Completion Note:</strong> ${t.completion_notes}</div>` : ''}
          </div>
          <div class="col-md-5">
            <div class="bg-light p-3 rounded-4 border small">
              <div><strong>Status:</strong> <span class="badge bg-${t.status === 'Completed' ? 'success' : (t.status === 'In Progress' ? 'primary' : 'warning text-dark')} rounded-pill">${t.status}</span></div>
              <div class="mt-1"><strong>Priority:</strong> <span class="badge bg-${t.priority === 'High' || t.priority === 'Urgent' ? 'danger' : 'secondary'} rounded-pill">${t.priority}</span></div>
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
                <button class="bento-btn-primary" style="padding: 0.45rem 1.15rem; font-size: 0.82rem;" type="submit"><i class="fa-solid fa-paper-plane me-1"></i> Send</button>
              </div>
            </form>

            <div class="comments-list" style="max-height: 250px; overflow-y: auto;">
              ${comments.length === 0 ? '<p class="text-muted small text-center py-3">No comments posted yet.</p>' : ''}
              ${comments.map(c => `
                <div class="p-2.5 mb-2 bg-light rounded-3 border small">
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
                      <span class="badge bg-secondary-subtle text-secondary me-1 rounded-pill">${h.action}</span>
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
