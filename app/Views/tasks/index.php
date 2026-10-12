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

$taskCounts = [
    'total' => count($tasks),
    'pending' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'overdue' => 0,
];
$todayDate = date('Y-m-d');
foreach ($tasks as $t) {
    $st = $t['status'] ?? '';
    if ($st === 'Completed') {
        $taskCounts['completed']++;
    } elseif ($st === 'In Progress') {
        $taskCounts['in_progress']++;
    } else {
        $taskCounts['pending']++;
    }

    if ($st !== 'Completed' && !empty($t['due_date']) && $t['due_date'] < $todayDate) {
        $taskCounts['overdue']++;
    }
}
?>

<!-- Load Bento Design System CSS & Page Styles -->
<link rel="stylesheet" href="/assets/css/dashboard-bento.css?v=<?= time() ?>">
<link rel="stylesheet" href="/assets/css/pages/tasks-index.css?v=<?= time() ?>">

<div class="content-body p-0 tasks-page">
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

    <!-- ─── 1. BENTO HERO HEADER ─────────────────────────────────────────── -->
    <header class="bento-header mb-4">
      <div class="bento-header-left">
        <div class="bento-tag">
          <i class="fa-solid fa-list-check"></i>
          <span>Operations &amp; Workstation</span>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <h1 class="bento-header-title mb-0">Operational Task Board</h1>
          <?php if (!$canViewAllTasks): ?>
            <span class="badge bg-light text-primary border rounded-pill px-2.5 py-1">
              <i class="fa-solid fa-user-lock me-1"></i> My Assigned Tasks
            </span>
          <?php else: ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fw-semibold">
              <i class="fa-solid fa-shield-halved me-1"></i> Management View (Team Scope)
            </span>
          <?php endif; ?>
        </div>
        <p class="bento-header-sub mt-1">
          Track visa submission follow-ups, document requests, embassy appointments &amp; verifiable proof of completion.
        </p>
      </div>

      <div class="task-header-actions">
        <!-- 3-Mode View Switcher -->
        <div class="task-segmented me-1" role="group" aria-label="View Mode">
          <button type="button" class="active" id="btnTaskViewTable" onclick="switchTaskView('table')" title="Tabular View">
            <i class="fa-solid fa-table-list"></i> Table
          </button>
          <button type="button" id="btnTaskViewGrid" onclick="switchTaskView('grid')" title="Card Grid View">
            <i class="fa-solid fa-grip"></i> Cards
          </button>
          <button type="button" id="btnTaskViewCompact" onclick="switchTaskView('compact')" title="Compact List View">
            <i class="fa-solid fa-bars"></i> Compact
          </button>
        </div>

        <?php if ($canViewAllTasks): ?>
          <div class="btn-group rounded-pill border p-1 bg-white shadow-xs me-1">
            <a href="/tasks?scope=all<?= $selectedStatus ? '&status=' . urlencode($selectedStatus) : '' ?>" class="btn btn-sm rounded-pill px-3 fw-semibold <?= $taskScope === 'all' ? 'btn-primary' : 'btn-light border-0 text-muted' ?>">
              <i class="fa-solid fa-users me-1"></i> Team Scope
            </a>
            <a href="/tasks?scope=my<?= $selectedStatus ? '&status=' . urlencode($selectedStatus) : '' ?>" class="btn btn-sm rounded-pill px-3 fw-semibold <?= $taskScope === 'my' ? 'btn-primary' : 'btn-light border-0 text-muted' ?>">
              <i class="fa-solid fa-user-check me-1"></i> My Desk
            </a>
          </div>
        <?php endif; ?>

        <button class="bento-btn-primary" data-bs-toggle="modal" data-bs-target="#newTaskModal" title="Create New Operational Task">
          <i class="fa-solid fa-plus"></i> Create Task
        </button>
      </div>
    </header>

    <!-- ─── 2. EXECUTIVE KPI STRIP ───────────────────────────────────────── -->
    <div class="task-kpi-grid">
      <!-- Total Tasks -->
      <a href="/tasks?scope=<?= e($taskScope) ?>" class="task-kpi-card tone-primary">
        <div class="task-kpi-top">
          <span class="task-kpi-label">Total Assigned</span>
          <div class="task-kpi-icon"><i class="fa-solid fa-list-check"></i></div>
        </div>
        <div class="task-kpi-val"><?= (int)$taskCounts['total'] ?></div>
        <span class="task-kpi-sub"><?= $taskScope === 'my' ? 'Your active queue' : 'Full team roster' ?></span>
      </a>

      <!-- Pending / To Do -->
      <a href="/tasks?scope=<?= e($taskScope) ?>&status=Pending" class="task-kpi-card tone-warning">
        <div class="task-kpi-top">
          <span class="task-kpi-label">Pending / To Do</span>
          <div class="task-kpi-icon"><i class="fa-regular fa-clock"></i></div>
        </div>
        <div class="task-kpi-val text-warning"><?= (int)$taskCounts['pending'] ?></div>
        <span class="task-kpi-sub">Awaiting action</span>
      </a>

      <!-- Overdue SLA -->
      <a href="/tasks?scope=<?= e($taskScope) ?>&status=Overdue" class="task-kpi-card tone-danger">
        <div class="task-kpi-top">
          <span class="task-kpi-label">Overdue SLAs</span>
          <div class="task-kpi-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        </div>
        <div class="task-kpi-val text-danger"><?= (int)$taskCounts['overdue'] ?></div>
        <span class="task-kpi-sub">Past due deadline</span>
      </a>

      <!-- Completed -->
      <a href="/tasks?scope=<?= e($taskScope) ?>&status=Completed" class="task-kpi-card tone-success">
        <div class="task-kpi-top">
          <span class="task-kpi-label">Completed</span>
          <div class="task-kpi-icon"><i class="fa-solid fa-circle-check"></i></div>
        </div>
        <div class="task-kpi-val text-success"><?= (int)$taskCounts['completed'] ?></div>
        <span class="task-kpi-sub">Verified completions</span>
      </a>
    </div>

    <!-- ─── 3. ADVANCED FILTER TOOLBAR ───────────────────────────────────── -->
    <div class="task-filter-card">
      <form action="/tasks" method="GET" class="row g-2.5 align-items-center">
        <?php if ($canViewAllTasks): ?>
          <input type="hidden" name="scope" value="<?= e($taskScope) ?>">
        <?php endif; ?>

        <div class="col-6 col-md-3 col-xl-3">
          <select name="status" class="form-select form-select-sm" title="Status" onchange="this.form.submit()">
            <option value="">-- All Statuses --</option>
            <option value="Pending" <?= $selectedStatus === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="In Progress" <?= $selectedStatus === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="Completed" <?= $selectedStatus === 'Completed' ? 'selected' : '' ?>>Completed</option>
            <option value="Overdue" <?= $selectedStatus === 'Overdue' ? 'selected' : '' ?>>Overdue</option>
          </select>
        </div>

        <div class="col-6 col-md-3 col-xl-3">
          <select name="priority" class="form-select form-select-sm" title="Priority" onchange="this.form.submit()">
            <option value="">-- All Priorities --</option>
            <option value="Normal" <?= $selectedPriority === 'Normal' ? 'selected' : '' ?>>Normal Priority</option>
            <option value="High" <?= $selectedPriority === 'High' ? 'selected' : '' ?>>High Priority</option>
            <option value="Urgent" <?= $selectedPriority === 'Urgent' ? 'selected' : '' ?>>Urgent Priority</option>
            <option value="Critical" <?= $selectedPriority === 'Critical' ? 'selected' : '' ?>>Critical Priority</option>
          </select>
        </div>

        <?php if ($canViewAllTasks && !empty($staffList)): ?>
          <div class="col-8 col-md-4 col-xl-3">
            <select name="assigned_to" class="form-select form-select-sm" title="Assigned Officer" onchange="this.form.submit()">
              <option value="0">-- All Assigned Officers --</option>
              <?php foreach ($staffList as $stf): ?>
                <option value="<?= $stf['id'] ?>" <?= (int)($_GET['assigned_to'] ?? 0) === (int)$stf['id'] ? 'selected' : '' ?>>
                  <?= e($stf['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <div class="col-4 col-md-auto ms-auto d-flex align-items-center gap-1.5">
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.15rem; font-size: 0.82rem;">
            <i class="fa-solid fa-filter me-1"></i> Filter
          </button>
          <a href="/tasks" class="btn btn-sm btn-light border rounded-pill px-2.5 py-1.5 text-muted hover-dark" title="Reset Filters">
            <i class="fa-solid fa-rotate-left"></i>
          </a>
        </div>
      </form>
    </div>

    <!-- ─── 4. RESULTS PRESENTATION ──────────────────────────────────────── -->
    <?php if (empty($tasks)): ?>
      <div class="bento-card text-center py-5">
        <i class="fa-solid fa-list-check fs-1 text-muted opacity-50 mb-3 d-block"></i>
        <h5 class="fw-bold text-dark mb-1">No tasks in this view</h5>
        <p class="text-muted small mb-3">No operational tasks match your active filters or assigned workstation.</p>
        <div class="d-flex justify-content-center gap-2">
          <a href="/tasks" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Clear Filters</a>
          <button class="bento-btn-primary" style="padding: 0.45rem 1.15rem; font-size: 0.82rem;" data-bs-toggle="modal" data-bs-target="#newTaskModal">
            <i class="fa-solid fa-plus me-1"></i> Create Task
          </button>
        </div>
      </div>
    <?php else: ?>

      <!-- ─── 4A. TABLE VIEW (BENTO ADAPTIVE DATA TABLE) ─────────────────── -->
      <div id="taskViewTable" class="task-view-container">
        <div class="bento-card p-0 overflow-hidden mb-4">
          <div class="task-table-wrap">
            <table class="task-table">
              <thead>
                <tr>
                  <th>Task Title &amp; Category</th>
                  <th>Linked File</th>
                  <th>Priority</th>
                  <th>Assigned Officer</th>
                  <th>Due Date</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($tasks as $t): ?>
                  <?php
                    $isCompleted = ($t['status'] === 'Completed');
                    $isOverdue = (!$isCompleted && !empty($t['due_date']) && $t['due_date'] < date('Y-m-d'));
                    $prio = strtolower($t['priority'] ?? 'normal');
                    $prioClass = ($prio === 'critical' || $prio === 'urgent') ? 'bg-danger text-white' : (($prio === 'high') ? 'bg-warning text-dark' : 'bg-primary-subtle text-primary');
                  ?>
                  <tr>
                    <!-- Task Title -->
                    <td data-label="Task">
                      <div>
                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                          <span class="fw-bold text-dark"><?= e($t['task_title']) ?></span>
                          <span class="badge bg-light text-secondary border small px-2 py-0.5"><?= e($t['task_type'] ?? 'General') ?></span>
                        </div>
                        <?php if (!empty($t['description'])): ?>
                          <div class="text-muted small text-truncate mt-0.5" style="max-width: 280px; font-size: 0.75rem;">
                            <?= e($t['description']) ?>
                          </div>
                        <?php endif; ?>
                      </div>
                    </td>

                    <!-- Linked File -->
                    <td data-label="Application">
                      <?php if (!empty($t['application_number'])): ?>
                        <div>
                          <a href="/applications/show?id=<?= $t['app_id'] ?>" class="fw-bold text-primary text-decoration-none small">
                            <i class="fa-solid fa-folder me-1"></i><?= e($t['application_number']) ?>
                          </a>
                          <div class="text-muted small" style="font-size: 0.74rem;"><?= e($t['customer_name'] ?? '—') ?></div>
                        </div>
                      <?php else: ?>
                        <span class="text-muted small"><i class="fa-solid fa-bolt me-1 text-warning"></i>General Desk Work</span>
                      <?php endif; ?>
                    </td>

                    <!-- Priority -->
                    <td data-label="Priority">
                      <span class="badge <?= $prioClass ?> fw-semibold px-2 py-1" style="font-size: 0.72rem;">
                        <?= e($t['priority']) ?>
                      </span>
                    </td>

                    <!-- Assigned Officer -->
                    <td data-label="Officer">
                      <div class="small fw-semibold text-dark"><i class="fa-solid fa-user-circle text-muted me-1"></i><?= e($t['assigned_to_name'] ?? 'Unassigned') ?></div>
                      <?php if (!empty($t['created_by_name'])): ?>
                        <div class="text-muted" style="font-size: 0.68rem;">By: <?= e($t['created_by_name']) ?></div>
                      <?php endif; ?>
                    </td>

                    <!-- Due Date -->
                    <td data-label="Due Date">
                      <span class="badge <?= $isOverdue ? 'bg-danger text-white' : ($isCompleted ? 'bg-light text-secondary border' : 'bg-light text-dark border') ?>" style="font-size: 0.74rem;">
                        <i class="fa-regular fa-calendar me-1"></i><?= format_date($t['due_date']) ?>
                      </span>
                      <?php if ($isOverdue): ?>
                        <div class="text-danger fw-bold" style="font-size: 0.68rem;"><i class="fa-solid fa-triangle-exclamation me-1"></i>Overdue SLA</div>
                      <?php endif; ?>
                    </td>

                    <!-- Status -->
                    <td data-label="Status">
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

                    <!-- Actions -->
                    <td class="text-end td-actions" data-label="Actions">
                      <div class="d-flex align-items-center justify-content-end gap-1">
                        <?php if (!$isCompleted): ?>
                          <button type="button" class="bento-btn-primary" style="padding: 0.35rem 0.85rem; font-size: 0.75rem;" 
                                  onclick="openCompleteTaskModal(<?= (int)$t['id'] ?>, '<?= e(addslashes($t['task_title'])) ?>')">
                            <i class="fa-solid fa-check me-1"></i> Complete
                          </button>
                        <?php else: ?>
                          <button type="button" class="btn btn-outline-success btn-sm rounded-pill py-1 px-2.5 fw-semibold" 
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
                          <button type="button" class="btn btn-light border btn-sm rounded-pill py-1 px-2 text-muted hover-dark" title="Edit Task"
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

                        <?php if ($canDeleteTask): ?>
                          <form action="/tasks/delete" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete task #<?= (int)$t['id'] ?>?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="task_id" value="<?= (int)$t['id'] ?>">
                            <button type="submit" class="btn btn-light border btn-sm rounded-pill py-1 px-2 text-danger hover-danger" title="Delete Task">
                              <i class="fa-solid fa-trash-can"></i>
                            </button>
                          </form>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ─── 4B. GRID CARDS VIEW ────────────────────────────────────────── -->
      <div id="taskViewGrid" class="task-view-container d-none mb-4">
        <div class="task-grid">
          <?php foreach ($tasks as $t): ?>
            <?php
              $isCompleted = ($t['status'] === 'Completed');
              $isOverdue = (!$isCompleted && !empty($t['due_date']) && $t['due_date'] < date('Y-m-d'));
              $prio = strtolower($t['priority'] ?? 'normal');
              $prioBadge = ($prio === 'critical' || $prio === 'urgent') ? 'bg-danger text-white' : (($prio === 'high') ? 'bg-warning text-dark' : 'bg-primary-subtle text-primary');
            ?>
            <div class="task-card" id="task-card-<?= (int)$t['id'] ?>">
              <div>
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                  <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 0.72rem;">
                    <i class="fa-solid fa-tag me-1 text-primary"></i><?= e($t['task_type'] ?? 'General') ?>
                  </span>
                  <span class="badge <?= $prioBadge ?> fw-semibold px-2 py-1" style="font-size: 0.72rem;">
                    <?= e($t['priority']) ?>
                  </span>
                </div>

                <h6 class="fw-bold text-dark mb-1 lh-sm"><?= e($t['task_title']) ?></h6>

                <?php if (!empty($t['description'])): ?>
                  <p class="text-muted small mb-2 text-truncate" style="font-size: 0.78rem;">
                    <?= e($t['description']) ?>
                  </p>
                <?php endif; ?>

                <div class="p-2.5 bg-light rounded-3 border mb-2.5">
                  <?php if (!empty($t['application_number'])): ?>
                    <div class="d-flex align-items-center justify-content-between mb-1">
                      <span class="text-muted small" style="font-size: 0.74rem;">Application:</span>
                      <a href="/applications/show?id=<?= $t['app_id'] ?>" class="fw-bold text-primary text-decoration-none small">
                        <i class="fa-solid fa-folder me-1"></i><?= e($t['application_number']) ?>
                      </a>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                      <span class="text-muted small" style="font-size: 0.74rem;">Applicant:</span>
                      <span class="fw-semibold text-dark small text-truncate" style="max-width: 170px;"><?= e($t['customer_name'] ?? '—') ?></span>
                    </div>
                  <?php else: ?>
                    <span class="text-muted small"><i class="fa-solid fa-bolt me-1 text-warning"></i>General Operations Desk Task</span>
                  <?php endif; ?>
                </div>

                <div class="d-flex align-items-center justify-content-between mb-2">
                  <div class="small text-muted" style="font-size: 0.74rem;">
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
                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold px-2 py-0.5" style="font-size: 0.72rem;">Done</span>
                  <?php elseif ($t['status'] === 'In Progress'): ?>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold px-2 py-0.5" style="font-size: 0.72rem;">Progress</span>
                  <?php else: ?>
                    <span class="badge bg-warning-subtle text-warning-text border border-warning-subtle fw-semibold px-2 py-0.5" style="font-size: 0.72rem;">Pending</span>
                  <?php endif; ?>
                </div>

                <div class="d-inline-flex gap-1">
                  <?php if (!$isCompleted): ?>
                    <button type="button" class="bento-btn-primary" style="padding: 0.35rem 0.85rem; font-size: 0.75rem;" 
                            onclick="openCompleteTaskModal(<?= (int)$t['id'] ?>, '<?= e(addslashes($t['task_title'])) ?>')">
                      <i class="fa-solid fa-check me-1"></i> Complete
                    </button>
                  <?php else: ?>
                    <button type="button" class="btn btn-outline-success btn-sm rounded-pill py-1 px-2.5 fw-semibold" 
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
                    <button type="button" class="btn btn-light border btn-sm rounded-pill py-1 px-2" title="Edit Task"
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
          <?php endforeach; ?>
        </div>
      </div>

      <!-- ─── 4C. COMPACT LIST VIEW ──────────────────────────────────────── -->
      <div id="taskViewCompact" class="task-view-container d-none mb-4">
        <div class="task-compact-list">
          <?php foreach ($tasks as $t): ?>
            <?php
              $isCompleted = ($t['status'] === 'Completed');
              $isOverdue = (!$isCompleted && !empty($t['due_date']) && $t['due_date'] < date('Y-m-d'));
              $prio = strtolower($t['priority'] ?? 'normal');
              $prioClass = ($prio === 'critical' || $prio === 'urgent') ? 'bg-danger text-white' : (($prio === 'high') ? 'bg-warning text-dark' : 'bg-primary-subtle text-primary');
            ?>
            <div class="task-compact-row">
              <div>
                <span class="fw-bold text-dark fs-6 d-block"><?= e($t['task_title']) ?></span>
                <span class="text-muted small" style="font-size: 0.72rem;"><?= e($t['task_type'] ?? 'General') ?> &bull; Assigned: <?= e($t['assigned_to_name'] ?? 'Unassigned') ?></span>
              </div>

              <div class="tc-meta">
                <?php if (!empty($t['application_number'])): ?>
                  <a href="/applications/show?id=<?= $t['app_id'] ?>" class="small fw-bold text-decoration-none text-primary">
                    <i class="fa-solid fa-folder me-1"></i><?= e($t['application_number']) ?>
                  </a>
                <?php else: ?>
                  <span class="small text-muted">Desk Task</span>
                <?php endif; ?>
              </div>

              <div>
                <span class="badge <?= $prioClass ?> fw-semibold"><?= e($t['priority']) ?></span>
              </div>

              <div class="tc-meta">
                <span class="badge <?= $isOverdue ? 'bg-danger text-white' : 'bg-light text-dark border' ?>">
                  <?= format_date($t['due_date']) ?>
                </span>
              </div>

              <div>
                <?php if ($isCompleted): ?>
                  <span class="badge bg-success-subtle text-success border border-success-subtle">Done</span>
                <?php else: ?>
                  <span class="badge bg-warning-subtle text-warning-text border border-warning-subtle">Active</span>
                <?php endif; ?>
              </div>

              <div class="d-flex align-items-center justify-content-end gap-1">
                <?php if (!$isCompleted): ?>
                  <button type="button" class="bento-btn-primary" style="padding: 0.3rem 0.75rem; font-size: 0.75rem;" 
                          onclick="openCompleteTaskModal(<?= (int)$t['id'] ?>, '<?= e(addslashes($t['task_title'])) ?>')">
                    <i class="fa-solid fa-check me-1"></i> Done
                  </button>
                <?php else: ?>
                  <button type="button" class="btn btn-outline-success btn-sm rounded-pill py-1 px-2.5 fw-semibold" 
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
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    <?php endif; ?>

  </div>
</div>

<!-- ======================================================================= -->
<!-- MODAL: CREATE TASK                                                      -->
<!-- ======================================================================= -->
<div class="modal fade" id="newTaskModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
      <form action="/tasks/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-header bg-dark text-white p-3.5">
          <h6 class="modal-title fw-bold fs-6"><i class="fa-solid fa-plus-circle me-2 text-primary"></i> Create Operational Task</h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Task Title <span class="text-danger">*</span></label>
            <input type="text" name="task_title" class="form-control rounded-3" placeholder="e.g. Call customer for missing bank statement" required>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Task Category</label>
              <select name="task_type" class="form-select rounded-3">
                <option value="General">General Task</option>
                <option value="Document Collection">Document Collection</option>
                <option value="Embassy Follow-up">Embassy Follow-up</option>
                <option value="Payment Collection">Payment Collection</option>
                <option value="Biometrics Appointment">Biometrics Appointment</option>
                <option value="Passport Delivery">Passport Delivery</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Priority</label>
              <select name="priority" class="form-select rounded-3">
                <option value="Normal">Normal</option>
                <option value="High">High</option>
                <option value="Urgent">Urgent</option>
                <option value="Critical">Critical</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Linked Visa Application (Optional)</label>
            <select name="application_id" class="form-select rounded-3">
              <option value="">-- No Linked File (General Desk Work) --</option>
              <?php foreach ($applications as $app): ?>
                <option value="<?= $app['id'] ?>">
                  <?= e($app['application_number']) ?> &mdash; <?= e($app['customer_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Assign To</label>
              <select name="assigned_to" class="form-select rounded-3">
                <option value="<?= $currentUserId ?>">Assign to Me</option>
                <?php foreach ($staffList as $stf): ?>
                  <?php if ((int)$stf['id'] !== $currentUserId): ?>
                    <option value="<?= $stf['id'] ?>"><?= e($stf['name']) ?></option>
                  <?php endif; ?>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Due Date <span class="text-danger">*</span></label>
              <input type="date" name="due_date" class="form-control rounded-3" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
            </div>
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold">Description / Work Notes</label>
            <textarea name="description" class="form-control rounded-3" rows="2" placeholder="Specific notes or checklist for completing this task..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light p-3 border-top">
          <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.15rem; font-size: 0.82rem;">
            <i class="fa-solid fa-plus me-1"></i> Create &amp; Assign Task
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ======================================================================= -->
<!-- MODAL: COMPLETE TASK (WITH VERIFICATION PROOF)                          -->
<!-- ======================================================================= -->
<div class="modal fade" id="completeTaskModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
      <form action="/tasks/complete" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="task_id" id="completeTaskId" value="0">
        <div class="modal-header bg-dark text-white p-3.5">
          <h6 class="modal-title fw-bold fs-6"><i class="fa-solid fa-circle-check me-2 text-success"></i> Complete Task Verification</h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="p-3 bg-light rounded-3 border mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Target Task:</span>
            <div class="fw-bold text-dark fs-6" id="completeTaskTitle">—</div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Completion Notes / Work Accomplished <span class="text-danger">*</span></label>
            <textarea name="completion_notes" id="completeNotes" class="form-control rounded-3" rows="3" placeholder="Describe actions taken, customer response, or resolution details..." required></textarea>
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold">Attach Proof of Work (Optional)</label>
            <input type="file" name="proof_attachment" class="form-control rounded-3" accept=".pdf,.jpg,.jpeg,.png,.docx">
            <div class="form-text small">Upload email confirmation, deposit slip, biometric slip, or chat screenshot.</div>
          </div>
        </div>
        <div class="modal-footer bg-light p-3 border-top">
          <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.25rem; font-size: 0.82rem;">
            <i class="fa-solid fa-check me-1"></i> Verify &amp; Mark Complete
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ======================================================================= -->
<!-- MODAL: VIEW PROOF                                                       -->
<!-- ======================================================================= -->
<div class="modal fade" id="viewProofModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
      <div class="modal-header bg-dark text-white p-3.5">
        <h6 class="modal-title fw-bold fs-6"><i class="fa-solid fa-file-shield me-2 text-info"></i> Completion Proof &amp; Verification</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <h6 class="fw-bold text-dark mb-2" id="proofTaskTitle">—</h6>
        <div class="small text-muted mb-3">
          Completed by: <strong class="text-dark" id="proofCompletedBy">—</strong> on <span id="proofCompletedAt">—</span>
        </div>

        <div class="p-3 bg-light rounded-3 border mb-3">
          <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Staff Completion Notes:</span>
          <div class="text-dark small mt-1" id="proofNotes" style="white-space: pre-wrap;">—</div>
        </div>

        <div id="proofAttachmentContainer" class="d-none">
          <span class="text-muted small fw-semibold text-uppercase d-block mb-1" style="font-size: 0.7rem;">Attached Proof Document:</span>
          <div id="proofImagePreviewWrap" class="mb-2 d-none text-center">
            <img src="" id="proofImagePreview" class="img-fluid rounded border shadow-xs" style="max-height: 240px;" alt="Proof preview">
          </div>
          <div class="d-flex gap-2">
            <a href="#" id="proofAttachmentLink" target="_blank" class="bento-btn-outline" style="padding: 0.35rem 0.85rem; font-size: 0.78rem;">
              <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> View Full File
            </a>
            <a href="#" id="proofDownloadLink" class="bento-btn-primary" style="padding: 0.35rem 0.85rem; font-size: 0.78rem;">
              <i class="fa-solid fa-download me-1"></i> Download
            </a>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light p-3 border-top">
        <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- ======================================================================= -->
<!-- MODAL: EDIT TASK                                                        -->
<!-- ======================================================================= -->
<div class="modal fade" id="editTaskModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
      <form action="/tasks/update" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="task_id" id="editTaskId" value="0">
        <div class="modal-header bg-dark text-white p-3.5">
          <h6 class="modal-title fw-bold fs-6"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i> Edit Task</h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Task Title <span class="text-danger">*</span></label>
            <input type="text" name="task_title" id="editTaskTitle" class="form-control rounded-3" required>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Task Category</label>
              <select name="task_type" id="editTaskType" class="form-select rounded-3">
                <option value="General">General Task</option>
                <option value="Document Collection">Document Collection</option>
                <option value="Embassy Follow-up">Embassy Follow-up</option>
                <option value="Payment Collection">Payment Collection</option>
                <option value="Biometrics Appointment">Biometrics Appointment</option>
                <option value="Passport Delivery">Passport Delivery</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Priority</label>
              <select name="priority" id="editTaskPriority" class="form-select rounded-3">
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
              <select name="status" id="editTaskStatus" class="form-select rounded-3">
                <option value="Pending">Pending</option>
                <option value="In Progress">In Progress</option>
                <option value="Completed">Completed</option>
                <option value="Overdue">Overdue</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Due Date <span class="text-danger">*</span></label>
              <input type="date" name="due_date" id="editTaskDueDate" class="form-control rounded-3" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Assigned Officer <span class="text-danger">*</span></label>
            <select name="assigned_to" id="editTaskAssignedTo" class="form-select rounded-3" required>
              <?php foreach ($staffList as $stf): ?>
                <option value="<?= $stf['id'] ?>">
                  <?= e($stf['name']) ?> (<?= e($stf['email']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold">Instructions / Notes</label>
            <textarea name="description" id="editTaskDescription" class="form-control rounded-3" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light p-3 border-top">
          <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.15rem; font-size: 0.82rem;">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
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
  document.querySelectorAll('.task-segmented button').forEach(btn => btn.classList.remove('active'));

  if (viewMode === 'grid') {
    const el = document.getElementById('taskViewGrid');
    if (el) el.classList.remove('d-none');
    const btn = document.getElementById('btnTaskViewGrid');
    if (btn) btn.classList.add('active');
  } else if (viewMode === 'compact') {
    const el = document.getElementById('taskViewCompact');
    if (el) el.classList.remove('d-none');
    const btn = document.getElementById('btnTaskViewCompact');
    if (btn) btn.classList.add('active');
  } else {
    const el = document.getElementById('taskViewTable');
    if (el) el.classList.remove('d-none');
    const btn = document.getElementById('btnTaskViewTable');
    if (btn) btn.classList.add('active');
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

  const targetId = urlParams.get('task_id');
  if (targetId) {
    const targetEl = document.getElementById('task-card-' + targetId);
    if (targetEl) {
      targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
      targetEl.querySelector('.task-card')?.classList.add('border-primary', 'shadow-lg');
    }
  }
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
