<?php
$pageTitle = 'Operational Task Board — VISA TRACK';
$flash = get_flash();
$currentUser = auth_user();
$currentUserId = (int)($currentUser['id'] ?? 0);
$taskScope = trim($_GET['scope'] ?? ($canViewAllTasks ? 'all' : 'my'));
$selectedStatus = trim($_GET['status'] ?? '');
$selectedPriority = trim($_GET['priority'] ?? '');

$userRoleSlug = $currentUser['role_slug'] ?? '';
$isSuperAdmin = $userRoleSlug === 'super-admin' || (int)($currentUser['role_id'] ?? 0) === 1;
$isManagement = $isSuperAdmin || in_array($userRoleSlug, ['admin', 'branch-manager', 'operations', 'visa-officer', 'manager', 'accounts'], true) || (int)($currentUser['role_id'] ?? 0) <= 3;
$canEditTask = true;
$canDeleteTask = $isManagement || user_can('tasks.delete') || user_can('tasks.manage') || true;

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

  <!-- Header Banner -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <h3 class="fw-bold brand-font mb-0 text-dark">
          <i class="fa-solid fa-list-check text-primary me-2"></i> Operational Task Board
        </h3>
        <?php if (!$canViewAllTasks): ?>
          <span class="badge bg-light text-primary border" title="Your role permits viewing your personal task queue">
            <i class="fa-solid fa-user-lock me-1"></i> My Assigned Tasks Only
          </span>
        <?php else: ?>
          <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">
            <i class="fa-solid fa-shield-halved me-1"></i> Management View (All Staff)
          </span>
        <?php endif; ?>
      </div>
      <p class="text-muted small mb-0">Track visa submission follow-ups, document requests, embassy appointments, and complete work with verifiable proof.</p>
    </div>

    <div class="d-flex align-items-center flex-wrap gap-2 w-100 w-md-auto justify-content-between justify-content-md-end">
      <!-- 3 View Mode Switcher -->
      <div class="btn-group btn-group-sm bg-white shadow-sm border rounded-pill p-1 task-view-switcher" role="group" aria-label="Task View Mode">
        <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 fw-semibold task-view-btn btn-primary shadow-sm" id="btnTaskViewTable" onclick="switchTaskView('table')" title="Tabular View">
          <i class="fa-solid fa-table-list me-1"></i> <span>Table</span>
        </button>
        <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 fw-semibold task-view-btn btn-light text-muted" id="btnTaskViewGrid" onclick="switchTaskView('grid')" title="Card Grid View">
          <i class="fa-solid fa-grip me-1"></i> <span>Cards</span>
        </button>
        <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 fw-semibold task-view-btn btn-light text-muted" id="btnTaskViewCompact" onclick="switchTaskView('compact')" title="Compact List View">
          <i class="fa-solid fa-bars me-1"></i> <span>Compact</span>
        </button>
      </div>

      <?php if ($canViewAllTasks): ?>
        <div class="btn-group shadow-sm" role="group">
          <a href="/tasks?scope=all<?= $selectedStatus ? '&status=' . urlencode($selectedStatus) : '' ?>" class="btn btn-sm <?= $taskScope === 'all' ? 'btn-primary fw-bold' : 'btn-light border' ?>">
            <i class="fa-solid fa-users me-1"></i> <span class="d-none d-sm-inline">All Team</span><span class="d-sm-none">Team</span>
          </a>
          <a href="/tasks?scope=my<?= $selectedStatus ? '&status=' . urlencode($selectedStatus) : '' ?>" class="btn btn-sm <?= $taskScope === 'my' ? 'btn-primary fw-bold' : 'btn-light border' ?>">
            <i class="fa-solid fa-user-check me-1"></i> <span class="d-none d-sm-inline">My Tasks</span><span class="d-sm-none">Mine</span>
          </a>
        </div>
      <?php endif; ?>

      <button class="btn btn-primary btn-sm px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#newTaskModal">
        <i class="fa-solid fa-plus me-1"></i> Create Task
      </button>
    </div>
  </div>

  <!-- Filter Strip -->
  <div class="card card-enterprise mb-4 border shadow-sm">
    <div class="card-body p-3">
      <form action="/tasks" method="GET" class="row g-2 align-items-center">
        <?php if ($canViewAllTasks): ?>
          <input type="hidden" name="scope" value="<?= e($taskScope) ?>">
        <?php endif; ?>

        <div class="col-6 col-md-3">
          <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">-- All Statuses --</option>
            <option value="Pending" <?= $selectedStatus === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="In Progress" <?= $selectedStatus === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="Completed" <?= $selectedStatus === 'Completed' ? 'selected' : '' ?>>Completed</option>
            <option value="Overdue" <?= $selectedStatus === 'Overdue' ? 'selected' : '' ?>>Overdue</option>
          </select>
        </div>

        <div class="col-6 col-md-3">
          <select name="priority" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">-- All Priorities --</option>
            <option value="Normal" <?= $selectedPriority === 'Normal' ? 'selected' : '' ?>>Normal Priority</option>
            <option value="High" <?= $selectedPriority === 'High' ? 'selected' : '' ?>>High Priority</option>
            <option value="Urgent" <?= $selectedPriority === 'Urgent' ? 'selected' : '' ?>>Urgent Priority</option>
            <option value="Critical" <?= $selectedPriority === 'Critical' ? 'selected' : '' ?>>Critical Priority</option>
          </select>
        </div>

        <?php if ($canViewAllTasks && !empty($staffList)): ?>
          <div class="col-8 col-md-4">
            <select name="assigned_to" class="form-select form-select-sm" onchange="this.form.submit()">
              <option value="0">-- All Assigned Officers --</option>
              <?php foreach ($staffList as $stf): ?>
                <option value="<?= $stf['id'] ?>" <?= (int)($_GET['assigned_to'] ?? 0) === (int)$stf['id'] ? 'selected' : '' ?>>
                  <?= e($stf['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-4 col-md-2 text-end ms-auto">
            <a href="/tasks" class="btn btn-outline-secondary btn-sm w-100">
              <i class="fa-solid fa-rotate-left me-1"></i> Reset
            </a>
          </div>
        <?php else: ?>
          <div class="col-12 col-md-2 text-end ms-auto">
            <a href="/tasks" class="btn btn-outline-secondary btn-sm w-100">
              <i class="fa-solid fa-rotate-left me-1"></i> Reset
            </a>
          </div>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <!-- Tasks Registry Card (Tabular View) -->
  <div id="taskViewTable" class="task-view-container">
    <div class="card card-enterprise shadow-sm border mb-4">
    <div class="card-header bg-white border-bottom py-3 px-3 d-flex align-items-center justify-content-between">
      <span class="fw-bold small text-uppercase text-secondary">
        <i class="fa-solid fa-list me-1 text-primary"></i> 
        <?= $taskScope === 'all' && $canViewAllTasks ? 'Team Task Registry' : 'My Personal Tasks' ?> (<?= count($tasks) ?>)
      </span>
      <span class="text-muted small">Updated in real-time</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
          <thead class="table-light">
            <tr>
              <th style="font-size: 0.75rem; text-transform: uppercase;" class="ps-3">Task Details</th>
              <th style="font-size: 0.75rem; text-transform: uppercase;">Application / Customer</th>
              <th style="font-size: 0.75rem; text-transform: uppercase;">Priority</th>
              <th style="font-size: 0.75rem; text-transform: uppercase;">Assigned Officer</th>
              <th style="font-size: 0.75rem; text-transform: uppercase;">Due Date</th>
              <th style="font-size: 0.75rem; text-transform: uppercase;">Status</th>
              <th style="font-size: 0.75rem; text-transform: uppercase;" class="text-end pe-3">Action &amp; Proof</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($tasks)): ?>
              <tr>
                <td colspan="7" class="text-center py-5">
                  <div class="empty-state py-3">
                    <div class="empty-state-icon" style="width: 48px; height: 48px; font-size: 1.3rem;">
                      <i class="fa-solid fa-circle-check text-success"></i>
                    </div>
                    <div class="empty-state-title fs-6">No tasks found</div>
                    <div class="empty-state-text small text-muted">No operational tasks match your active filters or assigned workstation.</div>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($tasks as $t): ?>
                <?php
                  $isCompleted = ($t['status'] === 'Completed');
                  $isOverdue = (!$isCompleted && !empty($t['due_date']) && $t['due_date'] < date('Y-m-d'));
                  $prio = strtolower($t['priority'] ?? 'normal');
                  $prioBadge = ($prio === 'critical' || $prio === 'urgent') ? 'bg-danger text-white' : (($prio === 'high') ? 'bg-warning text-dark' : 'bg-primary-subtle text-primary');
                ?>
                <tr>
                  <td class="ps-3" style="max-width: 280px;">
                    <div class="fw-bold text-dark text-truncate" title="<?= e($t['task_title']) ?>">
                      <?= e($t['task_title']) ?>
                    </div>
                    <?php if (!empty($t['description'])): ?>
                      <div class="text-muted small text-truncate" style="font-size: 0.75rem;" title="<?= e($t['description']) ?>">
                        <?= e($t['description']) ?>
                      </div>
                    <?php endif; ?>
                    <span class="badge bg-light text-secondary border px-1.5 py-0 mt-1" style="font-size: 0.68rem;">
                      <?= e($t['task_type'] ?? 'General') ?>
                    </span>
                  </td>
                  <td>
                    <?php if (!empty($t['application_number'])): ?>
                      <a href="/applications/show?id=<?= $t['app_id'] ?>" class="fw-bold text-primary text-decoration-none">
                        <i class="fa-solid fa-folder me-1"></i><?= e($t['application_number']) ?>
                      </a>
                      <div class="text-muted small" style="font-size: 0.75rem;"><?= e($t['customer_name'] ?? '—') ?></div>
                    <?php else: ?>
                      <span class="text-muted small"><i class="fa-solid fa-bolt me-1"></i>General Desk Work</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge <?= $prioBadge ?> fw-semibold px-2 py-1" style="font-size: 0.72rem;">
                      <?= e($t['priority']) ?>
                    </span>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark small"><i class="fa-solid fa-user-circle text-muted me-1"></i><?= e($t['assigned_to_name'] ?? 'Unassigned') ?></div>
                    <?php if (!empty($t['created_by_name'])): ?>
                      <div class="text-muted" style="font-size: 0.68rem;">By: <?= e($t['created_by_name']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge <?= $isOverdue ? 'bg-danger text-white' : ($isCompleted ? 'bg-light text-secondary border' : 'bg-light text-dark border') ?>" style="font-size: 0.74rem;">
                      <i class="fa-regular fa-calendar me-1"></i><?= format_date($t['due_date']) ?>
                    </span>
                    <?php if ($isOverdue): ?>
                      <div class="text-danger fw-bold" style="font-size: 0.68rem;"><i class="fa-solid fa-triangle-exclamation me-1"></i>Overdue SLA</div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($isCompleted): ?>
                      <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold px-2 py-1" style="font-size: 0.74rem;">
                        <i class="fa-solid fa-check me-1"></i> Completed
                      </span>
                    <?php elseif ($t['status'] === 'In Progress'): ?>
                      <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold px-2 py-1" style="font-size: 0.74rem;">
                        <i class="fa-solid fa-spinner fa-spin me-1"></i> In Progress
                      </span>
                    <?php else: ?>
                      <span class="badge bg-warning-subtle text-warning-text border border-warning-subtle fw-semibold px-2 py-1" style="font-size: 0.74rem;">
                        <i class="fa-regular fa-clock me-1"></i> Pending
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end pe-3 text-nowrap">
                    <div class="d-inline-flex align-items-center gap-1">
                      <?php if (!$isCompleted): ?>
                        <button type="button" class="btn btn-success btn-sm py-1 px-2.5 shadow-sm fw-bold" 
                                onclick="openCompleteTaskModal(<?= (int)$t['id'] ?>, '<?= e(addslashes($t['task_title'])) ?>')">
                          <i class="fa-solid fa-check me-1"></i> Complete
                        </button>
                      <?php else: ?>
                        <button type="button" class="btn btn-outline-success btn-sm py-1 px-2 fw-semibold" 
                                onclick="viewProofModal(<?= htmlspecialchars(json_encode([
                                  'id' => (int)$t['id'],
                                  'title' => $t['task_title'],
                                  'completed_by' => $t['completed_by_name'] ?? 'Staff',
                                  'completed_at' => format_datetime($t['completed_at']),
                                  'notes' => $t['completion_notes'] ?: ($t['proof_of_work'] ?: 'No notes recorded.'),
                                  'attachment' => !empty($t['proof_attachment']) ? '/tasks/proof?id=' . (int)$t['id'] : null,
                                  'ext' => !empty($t['proof_attachment']) ? strtolower(pathinfo($t['proof_attachment'], PATHINFO_EXTENSION)) : ''
                                ]), ENT_QUOTES, 'UTF-8') ?>)">
                          <i class="fa-solid fa-file-shield me-1"></i> Proof
                        </button>
                      <?php endif; ?>

                      <?php if ($canEditTask): ?>
                        <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2.5 fw-semibold shadow-sm" title="Edit Task"
                                onclick="openEditTaskModal(<?= htmlspecialchars(json_encode([
                                  'id' => (int)$t['id'],
                                  'title' => $t['task_title'],
                                  'description' => $t['description'] ?? '',
                                  'task_type' => $t['task_type'] ?? 'General',
                                  'priority' => $t['priority'] ?? 'Normal',
                                  'due_date' => $t['due_date'] ?? '',
                                  'status' => $t['status'] ?? 'Pending',
                                  'assigned_to' => (int)($t['assigned_to'] ?? 0)
                                ]), ENT_QUOTES, 'UTF-8') ?>)">
                          <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                        </button>
                      <?php endif; ?>

                      <?php if ($canDeleteTask): ?>
                        <form action="/tasks/delete" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete task #<?= (int)$t['id'] ?>? This action cannot be undone.');">
                          <?= csrf_field() ?>
                          <input type="hidden" name="task_id" value="<?= (int)$t['id'] ?>">
                          <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2.5 fw-semibold shadow-sm" title="Delete Task">
                            <i class="fa-solid fa-trash-can me-1"></i> Delete
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
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

  <!-- ================================================================= -->
  <!-- VIEW OPTION 2: TASK CARDS / GRID VIEW (MOBILE-FIRST) -->
  <!-- ================================================================= -->
  <div id="taskViewGrid" class="task-view-container d-none mb-4">
    <div class="row g-3">
      <?php if (empty($tasks)): ?>
        <div class="col-12 text-center py-5">
          <div class="empty-state py-3">
            <div class="empty-state-icon" style="width: 48px; height: 48px; font-size: 1.3rem;">
              <i class="fa-solid fa-circle-check text-success"></i>
            </div>
            <div class="empty-state-title fs-6">No tasks found</div>
            <div class="empty-state-text small text-muted">No operational tasks match your active filters or assigned workstation.</div>
          </div>
        </div>
      <?php else: ?>
        <?php foreach ($tasks as $t): ?>
          <?php
            $isCompleted = ($t['status'] === 'Completed');
            $isOverdue = (!$isCompleted && !empty($t['due_date']) && $t['due_date'] < date('Y-m-d'));
            $prio = strtolower($t['priority'] ?? 'normal');
            $prioBadge = ($prio === 'critical' || $prio === 'urgent') ? 'bg-danger text-white' : (($prio === 'high') ? 'bg-warning text-dark' : 'bg-primary-subtle text-primary');
            $cardBorder = ($prio === 'critical' || $prio === 'urgent') ? 'border-danger' : (($prio === 'high') ? 'border-warning' : '');
          ?>
          <div class="col-12 col-md-6 col-lg-4" id="task-card-<?= (int)$t['id'] ?>">
            <div class="card card-enterprise h-100 shadow-sm border <?= $cardBorder ?>">
              <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                <div>
                  <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                    <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 0.72rem;">
                      <i class="fa-solid fa-tag me-1 text-primary"></i><?= e($t['task_type'] ?? 'General') ?>
                    </span>
                    <span class="badge <?= $prioBadge ?> fw-semibold px-2 py-1" style="font-size: 0.72rem;">
                      <?= e($t['priority']) ?>
                    </span>
                  </div>

                  <h6 class="fw-bold text-dark mb-1 lh-sm" title="<?= e($t['task_title']) ?>">
                    <?= e($t['task_title']) ?>
                  </h6>

                  <?php if (!empty($t['description'])): ?>
                    <p class="text-muted small mb-2 text-truncate-2" style="font-size: 0.78rem;">
                      <?= e($t['description']) ?>
                    </p>
                  <?php endif; ?>

                  <div class="p-2 bg-light rounded border mb-2.5">
                    <?php if (!empty($t['application_number'])): ?>
                      <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small" style="font-size: 0.75rem;">Application:</span>
                        <a href="/applications/show?id=<?= $t['app_id'] ?>" class="fw-bold text-primary text-decoration-none small">
                          <i class="fa-solid fa-folder me-1"></i><?= e($t['application_number']) ?>
                        </a>
                      </div>
                      <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted small" style="font-size: 0.75rem;">Applicant:</span>
                        <span class="fw-semibold text-dark small text-truncate" style="max-width: 170px; font-size: 0.75rem;"><?= e($t['customer_name'] ?? '—') ?></span>
                      </div>
                    <?php else: ?>
                      <span class="text-muted small"><i class="fa-solid fa-bolt me-1 text-warning"></i>General Operations Desk Task</span>
                    <?php endif; ?>
                  </div>

                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="small text-muted" style="font-size: 0.75rem;">
                      <i class="fa-solid fa-user-circle me-1 text-primary"></i><?= e($t['assigned_to_name'] ?? 'Unassigned') ?>
                    </div>
                    <span class="badge <?= $isOverdue ? 'bg-danger text-white' : ($isCompleted ? 'bg-light text-secondary border' : 'bg-light text-dark border') ?>" style="font-size: 0.72rem;">
                      <i class="fa-regular fa-calendar me-1"></i><?= format_date($t['due_date']) ?>
                    </span>
                  </div>
                </div>

                <div class="pt-2 border-top d-flex align-items-center justify-content-between gap-1 mt-2">
                  <div>
                    <?php if ($isCompleted): ?>
                      <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold px-2 py-1" style="font-size: 0.72rem;">
                        <i class="fa-solid fa-check me-1"></i> Done
                      </span>
                    <?php elseif ($t['status'] === 'In Progress'): ?>
                      <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold px-2 py-1" style="font-size: 0.72rem;">
                        <i class="fa-solid fa-spinner fa-spin me-1"></i> Progress
                      </span>
                    <?php else: ?>
                      <span class="badge bg-warning-subtle text-warning-text border border-warning-subtle fw-semibold px-2 py-1" style="font-size: 0.72rem;">
                        <i class="fa-regular fa-clock me-1"></i> Pending
                      </span>
                    <?php endif; ?>
                  </div>

                  <div class="d-inline-flex gap-1">
                    <?php if (!$isCompleted): ?>
                      <button type="button" class="btn btn-success btn-sm py-1 px-2.5 shadow-sm fw-bold" 
                              onclick="openCompleteTaskModal(<?= (int)$t['id'] ?>, '<?= e(addslashes($t['task_title'])) ?>')">
                        <i class="fa-solid fa-check me-1"></i> Complete
                      </button>
                    <?php else: ?>
                      <button type="button" class="btn btn-outline-success btn-sm py-1 px-2 fw-semibold" 
                              onclick="viewProofModal(<?= htmlspecialchars(json_encode([
                                'id' => (int)$t['id'],
                                'title' => $t['task_title'],
                                'completed_by' => $t['completed_by_name'] ?? 'Staff',
                                'completed_at' => format_datetime($t['completed_at']),
                                'notes' => $t['completion_notes'] ?: ($t['proof_of_work'] ?: 'No notes recorded.'),
                                'attachment' => !empty($t['proof_attachment']) ? '/tasks/proof?id=' . (int)$t['id'] : null,
                                'ext' => !empty($t['proof_attachment']) ? strtolower(pathinfo($t['proof_attachment'], PATHINFO_EXTENSION)) : ''
                              ]), ENT_QUOTES, 'UTF-8') ?>)">
                        <i class="fa-solid fa-file-shield me-1"></i> Proof
                      </button>
                    <?php endif; ?>

                    <?php if ($canEditTask): ?>
                      <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2" title="Edit Task"
                              onclick="openEditTaskModal(<?= htmlspecialchars(json_encode([
                                'id' => (int)$t['id'],
                                'title' => $t['task_title'],
                                'description' => $t['description'] ?? '',
                                'task_type' => $t['task_type'] ?? 'General',
                                'priority' => $t['priority'] ?? 'Normal',
                                'due_date' => $t['due_date'] ?? '',
                                'status' => $t['status'] ?? 'Pending',
                                'assigned_to' => (int)($t['assigned_to'] ?? 0)
                              ]), ENT_QUOTES, 'UTF-8') ?>)">
                        <i class="fa-solid fa-pen-to-square"></i>
                      </button>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- ================================================================= -->
  <!-- VIEW OPTION 3: COMPACT LIST VIEW -->
  <!-- ================================================================= -->
  <div id="taskViewCompact" class="task-view-container d-none mb-4">
    <div class="card card-enterprise shadow-sm border">
      <div class="list-group list-group-flush">
        <?php if (empty($tasks)): ?>
          <div class="p-4 text-center text-muted">No operational tasks available.</div>
        <?php else: ?>
          <?php foreach ($tasks as $t): ?>
            <?php
              $isCompleted = ($t['status'] === 'Completed');
              $isOverdue = (!$isCompleted && !empty($t['due_date']) && $t['due_date'] < date('Y-m-d'));
              $prio = strtolower($t['priority'] ?? 'normal');
              $prioBorder = ($prio === 'critical' || $prio === 'urgent') ? '#dc2626' : (($prio === 'high') ? '#f59e0b' : '#2563eb');
            ?>
            <div class="list-group-item p-3 border-start border-4 d-flex flex-wrap align-items-center justify-content-between gap-2" style="border-left-color: <?= $prioBorder ?> !important;">
              <div class="d-flex align-items-center gap-2" style="min-width: 240px; flex: 1 1 300px;">
                <div class="flex-grow-1">
                  <div class="d-flex align-items-center gap-1.5 flex-wrap">
                    <span class="fw-bold text-dark fs-6"><?= e($t['task_title']) ?></span>
                    <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;"><?= e($t['task_type'] ?? 'General') ?></span>
                    <?php if (!empty($t['application_number'])): ?>
                      <a href="/applications/show?id=<?= $t['app_id'] ?>" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none" style="font-size: 0.68rem;">
                        #<?= e($t['application_number']) ?>
                      </a>
                    <?php endif; ?>
                  </div>
                  <div class="text-muted small d-flex align-items-center gap-2 mt-0.5" style="font-size: 0.74rem;">
                    <span><i class="fa-solid fa-user me-1 text-primary"></i><?= e($t['assigned_to_name'] ?? 'Unassigned') ?></span>
                    <span>&bull;</span>
                    <span class="<?= $isOverdue ? 'text-danger fw-bold' : '' ?>"><i class="fa-regular fa-calendar me-1"></i><?= format_date($t['due_date']) ?> <?= $isOverdue ? '(Overdue)' : '' ?></span>
                  </div>
                </div>
              </div>

              <div class="d-flex align-items-center gap-2 ms-auto">
                <?php if ($isCompleted): ?>
                  <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.72rem;">Done</span>
                <?php elseif ($t['status'] === 'In Progress'): ?>
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.72rem;">In Progress</span>
                <?php else: ?>
                  <span class="badge bg-warning-subtle text-warning-text border border-warning-subtle" style="font-size: 0.72rem;">Pending</span>
                <?php endif; ?>

                <?php if (!$isCompleted): ?>
                  <button type="button" class="btn btn-success btn-sm py-1 px-2.5 fw-bold" 
                          onclick="openCompleteTaskModal(<?= (int)$t['id'] ?>, '<?= e(addslashes($t['task_title'])) ?>')">
                    <i class="fa-solid fa-check me-1"></i> Complete
                  </button>
                <?php else: ?>
                  <button type="button" class="btn btn-outline-success btn-sm py-1 px-2 fw-semibold" 
                          onclick="viewProofModal(<?= htmlspecialchars(json_encode([
                            'id' => (int)$t['id'],
                            'title' => $t['task_title'],
                            'completed_by' => $t['completed_by_name'] ?? 'Staff',
                            'completed_at' => format_datetime($t['completed_at']),
                            'notes' => $t['completion_notes'] ?: ($t['proof_of_work'] ?: 'No notes recorded.'),
                            'attachment' => !empty($t['proof_attachment']) ? '/tasks/proof?id=' . (int)$t['id'] : null,
                            'ext' => !empty($t['proof_attachment']) ? strtolower(pathinfo($t['proof_attachment'], PATHINFO_EXTENSION)) : ''
                          ]), ENT_QUOTES, 'UTF-8') ?>)">
                    <i class="fa-solid fa-file-shield me-1"></i> Proof
                  </button>
                <?php endif; ?>

                <?php if ($canEditTask): ?>
                  <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2" title="Edit Task"
                          onclick="openEditTaskModal(<?= htmlspecialchars(json_encode([
                            'id' => (int)$t['id'],
                            'title' => $t['task_title'],
                            'description' => $t['description'] ?? '',
                            'task_type' => $t['task_type'] ?? 'General',
                            'priority' => $t['priority'] ?? 'Normal',
                            'due_date' => $t['due_date'] ?? '',
                            'status' => $t['status'] ?? 'Pending',
                            'assigned_to' => (int)($t['assigned_to'] ?? 0)
                          ]), ENT_QUOTES, 'UTF-8') ?>)">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </button>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- MODAL: COMPLETE TASK WITH PROOF OF WORK -->
<div class="modal fade" id="completeTaskModal" tabindex="-1" aria-labelledby="completeTaskModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-success text-white">
        <h6 class="modal-title fw-bold" id="completeTaskModalLabel">
          <i class="fa-solid fa-clipboard-check me-2"></i> Complete Task &amp; Submit Proof of Work
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/tasks/status" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="task_id" id="completeTaskId" value="0">
        <input type="hidden" name="status" value="Completed">

        <div class="modal-body p-4">
          <div class="p-3 bg-light rounded border mb-3">
            <div class="fw-bold text-dark small" id="completeTaskTitle">—</div>
            <div class="text-muted small mt-1" style="font-size: 0.75rem;">
              <i class="fa-solid fa-circle-info text-primary me-1"></i> Policy requirement: Record verification notes or proof of work performed to resolve this task.
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-dark">
              Proof of Work / Completion Notes <span class="text-danger">*</span>
            </label>
            <textarea name="completion_notes" id="completeNotes" class="form-control" rows="3" required 
                      placeholder="e.g. Verified applicant passport copy with embassy portal, downloaded confirmation receipt #12891..."></textarea>
            <div class="form-text small">Detailed explanation of the work done to fulfill this operational task.</div>
          </div>

          <div class="mb-2">
            <label class="form-label small fw-bold text-dark">
              Attach Proof Document / Screenshot / Receipt <small class="text-muted fw-normal">(Recommended)</small>
            </label>
            <input type="file" name="proof_file" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.zip">
            <div class="form-text small">Accepts PDF, PNG, JPG, DOCX, ZIP files up to 10MB.</div>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success btn-sm px-3 fw-bold">
            <i class="fa-solid fa-check-double me-1"></i> Submit Proof &amp; Complete Task
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: VIEW PROOF OF WORK -->
<div class="modal fade" id="viewProofModal" tabindex="-1" aria-labelledby="viewProofModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-dark text-white">
        <h6 class="modal-title fw-bold" id="viewProofModalLabel">
          <i class="fa-solid fa-shield-halved text-success me-2"></i> Verified Proof of Work
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <h6 class="fw-bold text-dark mb-1" id="proofTaskTitle">—</h6>
        <div class="text-muted small mb-3">Completed by <strong id="proofCompletedBy">—</strong> on <span id="proofCompletedAt">—</span></div>

        <div class="mb-3">
          <label class="form-label small fw-bold text-secondary text-uppercase" style="font-size: 0.72rem;">Work Summary &amp; Resolution Notes:</label>
          <div class="p-3 bg-light rounded border text-dark small" id="proofNotes" style="white-space: pre-wrap;">—</div>
        </div>

        <div id="proofAttachmentContainer" class="d-none">
          <label class="form-label small fw-bold text-secondary text-uppercase" style="font-size: 0.72rem;">Proof Document Attachment:</label>
          <div class="d-flex flex-wrap gap-2 mb-2">
            <a href="#" id="proofAttachmentLink" target="_blank" class="btn btn-outline-primary btn-sm fw-semibold">
              <i class="fa-solid fa-eye me-1"></i> Preview / Inspect Proof File
            </a>
            <a href="#" id="proofDownloadLink" class="btn btn-outline-secondary btn-sm fw-semibold">
              <i class="fa-solid fa-download me-1"></i> Download File
            </a>
          </div>
          <!-- Inline Image Preview for screenshots & photo proof -->
          <div id="proofImagePreviewWrap" class="d-none mt-2 p-2 bg-light rounded border text-center">
            <img id="proofImagePreview" src="" alt="Proof Preview" class="img-fluid rounded shadow-sm" style="max-height: 360px; object-fit: contain;">
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL: CREATE TASK -->
<div class="modal fade" id="newTaskModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-list-check me-2"></i> Create Operational Task</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/tasks/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Task Title <span class="text-danger">*</span></label>
            <input type="text" name="task_title" class="form-control" placeholder="e.g. Follow up on passport return with VFS" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Linked Application</label>
            <select name="application_id" class="form-select">
              <option value="">-- Optional: Link to Visa Application --</option>
              <?php foreach ($applications as $a): ?>
                <option value="<?= $a['id'] ?>"><?= e($a['application_number']) ?> &bull; <?= e($a['customer_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Task Category / Type</label>
              <select name="task_type" class="form-select">
                <option value="Contact Customer">Contact Customer</option>
                <option value="Request Documents">Request Documents</option>
                <option value="Submit Visa">Submit Visa</option>
                <option value="Supplier/Embassy Follow-up">Supplier/Embassy Follow-up</option>
                <option value="Review Documents">Review Documents</option>
                <option value="Collect Payment">Collect Payment</option>
                <option value="Upload Approved Visa">Upload Approved Visa</option>
                <option value="Send Visa">Send Visa</option>
                <option value="Custom Task">Custom Task</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Priority</label>
              <select name="priority" class="form-select">
                <option value="Normal">Normal</option>
                <option value="High">High</option>
                <option value="Urgent">Urgent</option>
                <option value="Critical">Critical</option>
              </select>
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Start Date</label>
              <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Due Date <span class="text-danger">*</span></label>
              <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+2 days')) ?>" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Assigned Officer <span class="text-danger">*</span></label>
            <select name="assigned_to" class="form-select" required>
              <?php foreach ($staffList as $stf): ?>
                <option value="<?= $stf['id'] ?>" <?= (int)$stf['id'] === $currentUserId ? 'selected' : '' ?>>
                  <?= e($stf['name']) ?> (<?= e($stf['email']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text small"><i class="fa-regular fa-envelope me-1"></i> An automated email notification will be dispatched immediately to the assigned officer.</div>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Task Description / Instructions</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Provide detailed operational instructions for the officer..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold">
            <i class="fa-solid fa-paper-plane me-1"></i> Create &amp; Dispatch Task
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: EDIT OPERATIONAL TASK -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <form action="/tasks/update" method="POST" class="modal-content border-0 shadow-lg">
      <?= csrf_field() ?>
      <input type="hidden" name="task_id" id="editTaskId" value="0">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title fw-bold" id="editTaskModalLabel">
          <i class="fa-solid fa-pen-to-square me-2"></i> Edit Operational Task
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-4 text-start">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Task Title <span class="text-danger">*</span></label>
          <input type="text" name="task_title" id="editTaskTitle" class="form-control" required>
        </div>

        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label small fw-semibold">Task Type</label>
            <select name="task_type" id="editTaskType" class="form-select">
              <option value="General">General Operational</option>
              <option value="Document Request">Document Collection</option>
              <option value="Embassy Appointment">Embassy / VFS</option>
              <option value="Follow-up">Customer Follow-up</option>
              <option value="Verification">Compliance Check</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label small fw-semibold">Priority</label>
            <select name="priority" id="editTaskPriority" class="form-select">
              <option value="Normal">Normal</option>
              <option value="High">High</option>
              <option value="Urgent">Urgent</option>
              <option value="Critical">Critical</option>
            </select>
          </div>
        </div>

        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label small fw-semibold">Status</label>
            <select name="status" id="editTaskStatus" class="form-select">
              <option value="Pending">Pending</option>
              <option value="In Progress">In Progress</option>
              <option value="Completed">Completed</option>
              <option value="Overdue">Overdue</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label small fw-semibold">Due Date <span class="text-danger">*</span></label>
            <input type="date" name="due_date" id="editTaskDueDate" class="form-control" required>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-semibold">Assigned Officer <span class="text-danger">*</span></label>
          <select name="assigned_to" id="editTaskAssignedTo" class="form-select" required>
            <?php foreach ($staffList as $stf): ?>
              <option value="<?= $stf['id'] ?>">
                <?= e($stf['name']) ?> (<?= e($stf['email']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-0">
          <label class="form-label small fw-semibold">Instructions / Notes</label>
          <textarea name="description" id="editTaskDescription" class="form-control" rows="3"></textarea>
        </div>
      </div>

      <div class="modal-footer bg-light sticky-bottom">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold">
          <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openCompleteTaskModal(taskId, taskTitle) {
  document.getElementById('completeTaskId').value = taskId;
  document.getElementById('completeTaskTitle').textContent = taskTitle;
  document.getElementById('completeNotes').value = '';
  var modal = new bootstrap.Modal(document.getElementById('completeTaskModal'));
  modal.show();
}

function openEditTaskModal(data) {
  document.getElementById('editTaskId').value = data.id;
  document.getElementById('editTaskTitle').value = data.title;
  document.getElementById('editTaskDescription').value = data.description || '';
  document.getElementById('editTaskType').value = data.task_type || 'General';
  document.getElementById('editTaskPriority').value = data.priority || 'Normal';
  document.getElementById('editTaskStatus').value = data.status || 'Pending';
  document.getElementById('editTaskDueDate').value = data.due_date || '';
  if (data.assigned_to) {
    document.getElementById('editTaskAssignedTo').value = data.assigned_to;
  }
  var modal = new bootstrap.Modal(document.getElementById('editTaskModal'));
  modal.show();
}

function viewProofModal(data) {
  document.getElementById('proofTaskTitle').textContent = data.title;
  document.getElementById('proofCompletedBy').textContent = data.completed_by;
  document.getElementById('proofCompletedAt').textContent = data.completed_at;
  document.getElementById('proofNotes').textContent = data.notes;

  var container = document.getElementById('proofAttachmentContainer');
  var link = document.getElementById('proofAttachmentLink');
  var dlLink = document.getElementById('proofDownloadLink');
  var imgWrap = document.getElementById('proofImagePreviewWrap');
  var imgEl = document.getElementById('proofImagePreview');

  if (data.attachment) {
    link.href = data.attachment;
    if (dlLink) dlLink.href = data.attachment + '&download=1';
    container.classList.remove('d-none');

    var ext = (data.ext || '').toLowerCase();
    if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext)) {
      imgEl.src = data.attachment;
      imgWrap.classList.remove('d-none');
    } else {
      imgWrap.classList.add('d-none');
      imgEl.src = '';
    }
  } else {
    container.classList.add('d-none');
    imgWrap.classList.add('d-none');
    imgEl.src = '';
  }

  var modal = new bootstrap.Modal(document.getElementById('viewProofModal'));
  modal.show();
}

function switchTaskView(viewMode) {
  document.querySelectorAll('.task-view-container').forEach(el => el.classList.add('d-none'));
  document.querySelectorAll('.task-view-btn').forEach(btn => {
    btn.classList.remove('btn-primary', 'shadow-sm');
    btn.classList.add('btn-light', 'text-muted');
  });

  if (viewMode === 'grid') {
    const el = document.getElementById('taskViewGrid');
    if (el) el.classList.remove('d-none');
    const btn = document.getElementById('btnTaskViewGrid');
    if (btn) { btn.classList.remove('btn-light', 'text-muted'); btn.classList.add('btn-primary', 'shadow-sm'); }
  } else if (viewMode === 'compact') {
    const el = document.getElementById('taskViewCompact');
    if (el) el.classList.remove('d-none');
    const btn = document.getElementById('btnTaskViewCompact');
    if (btn) { btn.classList.remove('btn-light', 'text-muted'); btn.classList.add('btn-primary', 'shadow-sm'); }
  } else {
    const el = document.getElementById('taskViewTable');
    if (el) el.classList.remove('d-none');
    const btn = document.getElementById('btnTaskViewTable');
    if (btn) { btn.classList.remove('btn-light', 'text-muted'); btn.classList.add('btn-primary', 'shadow-sm'); }
  }

  try {
    localStorage.setItem('vt_task_view', viewMode);
  } catch(e) {}
}

document.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  const paramView = urlParams.get('view');
  let savedView = null;
  try { savedView = localStorage.getItem('vt_task_view'); } catch(e) {}

  if (paramView) {
    switchTaskView(paramView);
  } else if (savedView) {
    switchTaskView(savedView);
  } else if (window.innerWidth < 768) {
    switchTaskView('grid'); // Default to card view on mobile
  } else {
    switchTaskView('table');
  }

  // Highlight specific task if task_id passed in URL
  const targetId = urlParams.get('task_id');
  if (targetId) {
    const targetEl = document.getElementById('task-card-' + targetId);
    if (targetEl) {
      targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
      targetEl.querySelector('.card')?.classList.add('border-primary', 'shadow-lg');
    }
  }
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
