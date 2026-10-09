<?php
$pageTitle = 'Immutable Audit Trail & Compliance — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';

$currentView = $_GET['view'] ?? 'table';
if (!in_array($currentView, ['table', 'timeline', 'grid'], true)) {
    $currentView = 'table';
}

$hasSystemErrors = false;
foreach ($logs as $l) {
    if (in_array($l['action'], ['SYSTEM_ERROR', 'ERROR', 'EXCEPTION'], true)) {
        $hasSystemErrors = true;
        break;
    }
}
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

  <?php if ($hasSystemErrors): ?>
    <div class="alert alert-danger border-0 shadow-sm mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3 p-3">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-triangle-exclamation fs-5"></i>
        <div>
          <strong class="d-block">System Errors Logged in Operations History</strong>
          <span class="small">Super Admin diagnostic alerts recorded during application runtime. Once fixed, click Mark Resolved to reset the alert counter.</span>
        </div>
      </div>
      <a href="/audit-logs/clear-errors" class="btn btn-dark btn-sm px-3 shadow-sm fw-semibold" onclick="return confirm('Mark all recorded system error alerts as resolved and reset the error badge to zero?');">
        <i class="fa-solid fa-check-double me-1"></i> Mark Resolved &amp; Reset to Zero
      </a>
    </div>
  <?php endif; ?>

  <!-- Page Header & View Toggle -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <h3 class="fw-bold brand-font text-dark mb-0"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> System Audit Trail &amp; History</h3>
        <span class="badge bg-primary-subtle text-primary fw-bold border"><i class="fa-solid fa-shield-halved me-1"></i>Immutable Log</span>
      </div>
      <p class="text-muted small mb-0">Complete historical record of user actions, stage shifts, payment events, document approvals, and system diagnostics.</p>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <!-- 3 View Options Switcher -->
      <div class="btn-group btn-group-sm bg-white shadow-sm border rounded-pill p-1" role="group" aria-label="View Mode">
        <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold audit-view-btn <?= $currentView === 'table' ? 'btn-primary shadow-sm' : 'btn-light text-muted' ?>" onclick="switchAuditView('table')" id="btnAuditViewTable">
          <i class="fa-solid fa-table-list me-1"></i> <span class="d-none d-sm-inline">Table</span>
        </button>
        <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold audit-view-btn <?= $currentView === 'timeline' ? 'btn-primary shadow-sm' : 'btn-light text-muted' ?>" onclick="switchAuditView('timeline')" id="btnAuditViewTimeline">
          <i class="fa-solid fa-timeline me-1"></i> <span class="d-none d-sm-inline">Timeline</span>
        </button>
        <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold audit-view-btn <?= $currentView === 'grid' ? 'btn-primary shadow-sm' : 'btn-light text-muted' ?>" onclick="switchAuditView('grid')" id="btnAuditViewGrid">
          <i class="fa-solid fa-grip me-1"></i> <span class="d-none d-sm-inline">Event Cards</span>
        </button>
      </div>

      <a href="/audit-logs/clear-errors" class="btn btn-outline-danger btn-sm px-3 shadow-sm bg-white" onclick="return confirm('Clear and resolve all system error alerts to reset the topbar badge to zero?');">
        <i class="fa-solid fa-check-double me-1"></i> Clear Error Badge
      </a>

      <a href="/audit-logs/export" class="btn btn-outline-success btn-sm px-3 bg-white shadow-sm">
        <i class="fa-solid fa-file-excel me-1"></i> Export CSV
      </a>
    </div>
  </div>

  <!-- Multi-Filter Toolbar -->
  <div class="card card-enterprise mb-4 shadow-sm border bg-white">
    <div class="card-body p-3">
      <form action="/audit-logs" method="GET" class="row g-2 align-items-center" id="auditFilterForm">
        <input type="hidden" name="view" id="activeAuditViewParam" value="<?= e($currentView) ?>">
        <div class="col-12 col-md-3">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search description, context, IP..." value="<?= e($_GET['search'] ?? '') ?>">
          </div>
        </div>

        <div class="col-6 col-md-2">
          <select name="module" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Modules</option>
            <?php foreach (($modules ?? []) as $m): ?>
              <option value="<?= e($m) ?>" <?= ($_GET['module'] ?? '') === $m ? 'selected' : '' ?>><?= e($m) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-md-2">
          <select name="action" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Actions</option>
            <?php foreach (($actions ?? []) as $a): ?>
              <option value="<?= e($a) ?>" <?= ($_GET['action'] ?? '') === $a ? 'selected' : '' ?>><?= e($a) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-md-2">
          <select name="user_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Actors</option>
            <?php foreach (($users ?? []) as $u): ?>
              <option value="<?= $u['id'] ?>" <?= ((int)($_GET['user_id'] ?? 0)) === (int)$u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-md-2 d-flex gap-1">
          <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($_GET['date_from'] ?? '') ?>" title="From Date">
          <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($_GET['date_to'] ?? '') ?>" title="To Date">
        </div>

        <div class="col-12 col-md-1 d-flex gap-1">
          <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold"><i class="fa-solid fa-filter me-1"></i> Filter</button>
          <a href="/audit-logs" class="btn btn-light btn-sm border" title="Reset Filters"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
      </form>
    </div>
  </div>

  <?php if (empty($logs)): ?>
    <div class="card card-enterprise shadow-sm border text-center py-5 p-4 bg-white mb-4">
      <div class="mb-3">
        <i class="fa-regular fa-folder-open text-muted fs-1 opacity-50"></i>
      </div>
      <h5 class="fw-bold text-dark mb-1">No Audit Records Found</h5>
      <p class="text-muted small mb-0">No system activities match your current search and filter parameters.</p>
    </div>
  <?php else: ?>

    <!-- ================================================================= -->
    <!-- VIEW OPTION 1: TABLE VIEW -->
    <!-- ================================================================= -->
    <div id="auditViewTable" class="audit-view-container <?= $currentView === 'table' ? '' : 'd-none' ?>">
      <div class="card card-enterprise shadow-sm border">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="min-width: 960px;">
            <thead class="table-light">
              <tr>
                <th class="ps-3" style="width: 170px;">Timestamp</th>
                <th style="width: 180px;">Actor / User</th>
                <th style="width: 120px;">Module</th>
                <th style="width: 140px;">Action</th>
                <th>Description &amp; Delta Context</th>
                <th class="pe-3" style="width: 120px;">IP Address</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $log): ?>
                <?php
                  $isError = in_array($log['action'], ['SYSTEM_ERROR', 'ERROR', 'EXCEPTION'], true);
                  $rowClass = $isError ? 'table-danger' : '';
                ?>
                <tr class="<?= $rowClass ?>">
                  <td class="ps-3">
                    <span class="small font-monospace text-dark d-block fw-semibold"><?= format_datetime($log['created_at']) ?></span>
                  </td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="avatar-sm rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center p-2 fw-bold" style="width: 28px; height: 28px; font-size: 0.7rem;">
                        <?= strtoupper(substr($log['user_name'] ?? 'System', 0, 1)) ?>
                      </div>
                      <div>
                        <div class="fw-semibold text-dark small"><?= e($log['user_name'] ?? 'System') ?></div>
                        <span class="badge bg-light text-muted border" style="font-size: 0.65rem;"><?= e($log['actor_type']) ?></span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border px-2 py-0.5 small"><?= e($log['module'] ?: 'System') ?></span>
                  </td>
                  <td>
                    <?php if ($isError): ?>
                      <span class="badge bg-danger px-2.5 py-1 font-monospace" style="font-size: 0.72rem;"><?= e($log['action']) ?></span>
                    <?php elseif ($log['action'] === 'RESOLVED_ERROR'): ?>
                      <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 font-monospace" style="font-size: 0.72rem;"><i class="fa-solid fa-check me-1"></i>RESOLVED</span>
                    <?php else: ?>
                      <span class="badge bg-primary-subtle text-primary font-monospace px-2.5 py-1" style="font-size: 0.72rem;"><?= e($log['action']) ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="small fw-semibold text-dark mb-0.5"><?= e($log['description']) ?></div>
                    <?php if (!empty($log['details_json'])): ?>
                      <pre class="bg-light p-2 rounded border small mb-0 text-muted font-monospace" style="font-size: 0.7rem; max-height: 90px; overflow-y: auto; white-space: pre-wrap; word-break: break-all;"><?= e($log['details_json']) ?></pre>
                    <?php endif; ?>
                  </td>
                  <td class="pe-3">
                    <span class="small font-monospace text-muted"><?= e($log['ip_address'] ?: '127.0.0.1') ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ================================================================= -->
    <!-- VIEW OPTION 2: TIMELINE STREAM VIEW -->
    <!-- ================================================================= -->
    <div id="auditViewTimeline" class="audit-view-container <?= $currentView === 'timeline' ? '' : 'd-none' ?>">
      <div class="card card-enterprise shadow-sm border p-3 p-md-4 bg-white">
        <div class="timeline-container position-relative ps-4" style="border-left: 2px solid #e2e8f0; margin-left: 12px;">
          <?php foreach ($logs as $log): ?>
            <?php
              $isError = in_array($log['action'], ['SYSTEM_ERROR', 'ERROR', 'EXCEPTION'], true);
              $dotColor = $isError ? '#ef4444' : '#2563eb';
              $badgeClass = $isError ? 'bg-danger' : 'bg-primary-subtle text-primary';
            ?>
            <div class="timeline-item position-relative mb-3.5">
              <!-- Timeline Marker Dot -->
              <span class="position-absolute rounded-circle shadow-sm" style="left: -29px; top: 5px; width: 14px; height: 14px; background-color: <?= $dotColor ?>; border: 3px solid #ffffff;"></span>

              <div class="card shadow-sm border p-3 <?= $isError ? 'border-danger-subtle bg-danger-subtle bg-opacity-10' : 'bg-white' ?>">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1.5">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-dark text-white font-monospace small"><i class="fa-regular fa-clock me-1"></i><?= format_datetime($log['created_at']) ?></span>
                    <span class="badge <?= $badgeClass ?> font-monospace small px-2 py-0.5"><?= e($log['action']) ?></span>
                    <span class="badge bg-light text-dark border small"><?= e($log['module'] ?: 'System') ?></span>
                  </div>
                  <div class="small text-muted font-monospace">
                    <i class="fa-solid fa-network-wired me-1"></i><?= e($log['ip_address'] ?: '127.0.0.1') ?>
                  </div>
                </div>

                <div class="d-flex align-items-center gap-2 mb-2">
                  <div class="avatar-xs rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 22px; height: 22px; font-size: 0.65rem;">
                    <?= strtoupper(substr($log['user_name'] ?? 'S', 0, 1)) ?>
                  </div>
                  <strong class="text-dark small"><?= e($log['user_name'] ?? 'System') ?></strong>
                  <span class="text-muted small">&bull; <?= e($log['actor_type']) ?></span>
                </div>

                <div class="small text-dark fw-medium mb-1">
                  <?= e($log['description']) ?>
                </div>

                <?php if (!empty($log['details_json'])): ?>
                  <pre class="bg-light p-2 rounded border small mb-0 font-monospace text-muted" style="font-size: 0.68rem; max-height: 90px; overflow-y: auto;"><?= e($log['details_json']) ?></pre>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- ================================================================= -->
    <!-- VIEW OPTION 3: EVENT CARDS VIEW -->
    <!-- ================================================================= -->
    <div id="auditViewGrid" class="audit-view-container <?= $currentView === 'grid' ? '' : 'd-none' ?>">
      <div class="row g-3">
        <?php foreach ($logs as $log): ?>
          <?php
            $isError = in_array($log['action'], ['SYSTEM_ERROR', 'ERROR', 'EXCEPTION'], true);
            $cardBorder = $isError ? 'border-danger' : 'border-light';
            $badgeBg = $isError ? 'bg-danger' : 'bg-primary';
          ?>
          <div class="col-12 col-md-6 col-lg-4">
            <div class="card card-enterprise h-100 shadow-sm border <?= $cardBorder ?>">
              <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                <div>
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge <?= $badgeBg ?> font-monospace small px-2 py-0.5">
                      <?= e($log['action']) ?>
                    </span>
                    <span class="badge bg-light text-dark border small">
                      <?= e($log['module'] ?: 'System') ?>
                    </span>
                  </div>

                  <p class="text-dark small fw-semibold mb-2" style="min-height: 40px;">
                    <?= e(mb_strimwidth($log['description'], 0, 120, '...')) ?>
                  </p>

                  <?php if (!empty($log['details_json'])): ?>
                    <pre class="bg-light p-2 rounded border small mb-3 text-muted font-monospace" style="font-size: 0.65rem; max-height: 70px; overflow-y: auto;"><?= e($log['details_json']) ?></pre>
                  <?php endif; ?>
                </div>

                <div class="pt-2 border-top d-flex align-items-center justify-content-between small text-muted">
                  <div class="d-flex align-items-center gap-1.5">
                    <i class="fa-solid fa-user small text-primary"></i>
                    <span class="fw-semibold text-dark"><?= e($log['user_name'] ?? 'System') ?></span>
                  </div>
                  <div class="font-monospace" style="font-size: 0.72rem;">
                    <?= date('d M, H:i', strtotime($log['created_at'])) ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  <?php endif; ?>
</div>

<script>
function switchAuditView(view) {
  // Hide all view containers
  document.querySelectorAll('.audit-view-container').forEach(el => el.classList.add('d-none'));

  // Show active view container
  const target = document.getElementById('auditView' + view.charAt(0).toUpperCase() + view.slice(1));
  if (target) target.classList.remove('d-none');

  // Update button active states
  document.querySelectorAll('.audit-view-btn').forEach(btn => {
    btn.classList.remove('btn-primary', 'shadow-sm');
    btn.classList.add('btn-light', 'text-muted');
  });

  const activeBtn = document.getElementById('btnAuditView' + view.charAt(0).toUpperCase() + view.slice(1));
  if (activeBtn) {
    activeBtn.classList.remove('btn-light', 'text-muted');
    activeBtn.classList.add('btn-primary', 'shadow-sm');
  }

  // Update hidden input in filter form
  const viewInput = document.getElementById('activeAuditViewParam');
  if (viewInput) viewInput.value = view;

  // Persist preference in localStorage
  try { localStorage.setItem('audit_active_view', view); } catch (e) {}
}

// Restore saved view preference if URL does not specify one
document.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  if (!urlParams.has('view')) {
    const saved = localStorage.getItem('audit_active_view');
    if (saved && ['table', 'timeline', 'grid'].includes(saved)) {
      switchAuditView(saved);
    }
  }
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
