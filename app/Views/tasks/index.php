<?php
$pageTitle = 'Tasks Management — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
?>

<div class="content-body">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show mb-4" role="alert">
      <?= e($flash['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
      <h3 class="fw-bold brand-font mb-0"><i class="fa-solid fa-list-check text-primary me-2"></i> Operational Task Board</h3>
      <p class="text-muted small mb-0">Track visa submission follow-ups, embassy liaison, and customer document requests.</p>
    </div>
    <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#newTaskModal">
      <i class="fa-solid fa-plus me-1"></i> Create Task
    </button>
  </div>

  <div class="card-custom">
    <div class="table-responsive">
      <table class="table table-custom align-middle mb-0">
        <thead>
          <tr>
            <th>Task Title</th>
            <th>Application #</th>
            <th>Priority</th>
            <th>Assigned To</th>
            <th>Due Date</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($tasks)): ?>
            <tr><td colspan="7" class="text-center py-5 text-muted">No operational tasks found.</td></tr>
          <?php else: ?>
            <?php foreach ($tasks as $t): ?>
              <tr>
                <td>
                  <div class="fw-semibold text-dark"><?= e($t['task_title']) ?></div>
                  <div class="text-muted small"><?= e($t['description']) ?></div>
                </td>
                <td>
                  <?php if ($t['application_number']): ?>
                    <a href="/applications/show?id=<?= $t['app_id'] ?>" class="fw-bold"><?= e($t['application_number']) ?></a>
                  <?php else: ?>
                    <span class="text-muted small">General Task</span>
                  <?php endif; ?>
                </td>
                <td><span class="badge badge-priority-<?= strtolower($t['priority']) ?>"><?= e($t['priority']) ?></span></td>
                <td><span class="small fw-semibold"><?= e($t['assigned_to_name']) ?></span></td>
                <td>
                  <span class="small <?= strtotime($t['due_date']) < time() && $t['status'] !== 'Completed' ? 'text-danger fw-bold' : 'text-muted' ?>">
                    <?= format_date($t['due_date']) ?>
                  </span>
                </td>
                <td>
                  <span class="badge <?= $t['status'] === 'Completed' ? 'bg-success' : ($t['status'] === 'In Progress' ? 'bg-primary' : 'bg-warning text-dark') ?>">
                    <?= e($t['status']) ?>
                  </span>
                </td>
                <td class="text-end">
                  <?php if ($t['status'] !== 'Completed'): ?>
                    <form action="/tasks/status" method="POST" class="d-inline">
                      <?= csrf_field() ?>
                      <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
                      <input type="hidden" name="status" value="Completed">
                      <button type="submit" class="btn btn-outline-success btn-sm py-1 px-2">
                        <i class="fa-solid fa-check me-1"></i> Complete
                      </button>
                    </form>
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
                <option value="<?= $stf['id'] ?>"><?= e($stf['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Task Description / Instructions</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Provide details..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-3">Create Task</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
