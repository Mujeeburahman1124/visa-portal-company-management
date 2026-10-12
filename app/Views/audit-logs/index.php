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
$errorCount = 0;
foreach ($logs as $l) {
    if (in_array($l['action'], ['SYSTEM_ERROR', 'ERROR', 'EXCEPTION'], true)) {
        $hasSystemErrors = true;
        $errorCount++;
    }
}
$totalLogsCount = count($logs);
$totalModulesCount = count($modules ?? []);
$totalUsersCount = count($users ?? []);
?>

<!-- Link Dedicated Bento CSS -->
<link rel="stylesheet" href="/assets/css/dashboard-bento.css">
<link rel="stylesheet" href="/assets/css/pages/audit-logs-index.css">

<div class="content-body audit-dashboard">
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
    <div class="alert alert-danger border-0 shadow-sm mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3 p-3.5 rounded-3">
      <div class="d-flex align-items-center gap-2.5">
        <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center p-2" style="width: 38px; height: 38px;">
          <i class="fa-solid fa-triangle-exclamation fs-5"></i>
        </div>
        <div>
          <strong class="d-block text-danger">System Errors Recorded in Operations Ledger</strong>
          <span class="small text-muted">Super Admin diagnostic alerts recorded during application runtime. Click Mark Resolved to reset the alert counter.</span>
        </div>
      </div>
      <a href="/audit-logs/clear-errors" class="btn btn-dark btn-sm px-3 shadow-sm fw-semibold" onclick="return confirm('Mark all recorded system error alerts as resolved and reset the error badge to zero?');">
        <i class="fa-solid fa-check-double me-1.5 text-success"></i> Mark Resolved &amp; Reset to Zero
      </a>
    </div>
  <?php endif; ?>

  <!-- Bento Hero Header -->
  <div class="audit-hero">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <div class="audit-hero-title">
          <i class="fa-solid fa-clock-rotate-left text-danger"></i>
          <span>System Audit Trail &amp; Compliance</span>
          <span class="audit-hero-badge">Immutable Ledger</span>
        </div>
        <p class="audit-hero-desc">
          Complete chronological record of user actions, stage shifts, payment events, document approvals, and system diagnostics.
        </p>
      </div>

      <div class="d-flex align-items-center gap-2 flex-wrap">
        <!-- 3 View Options Switcher -->
        <div class="audit-view-switcher" role="group" aria-label="View Mode">
          <button type="button" class="audit-view-btn <?= $currentView === 'table' ? 'active' : '' ?>" onclick="switchAuditView('table')" id="btnAuditViewTable">
            <i class="fa-solid fa-table-list"></i> <span>Table</span>
          </button>
          <button type="button" class="audit-view-btn <?= $currentView === 'timeline' ? 'active' : '' ?>" onclick="switchAuditView('timeline')" id="btnAuditViewTimeline">
            <i class="fa-solid fa-timeline"></i> <span>Timeline</span>
          </button>
          <button type="button" class="audit-view-btn <?= $currentView === 'grid' ? 'active' : '' ?>" onclick="switchAuditView('grid')" id="btnAuditViewGrid">
            <i class="fa-solid fa-grip"></i> <span>Cards</span>
          </button>
        </div>

        <a href="/audit-logs/clear-errors" class="btn btn-outline-light btn-sm px-3 shadow-sm fw-semibold" onclick="return confirm('Clear and resolve all system error alerts to reset the topbar badge to zero?');">
          <i class="fa-solid fa-check-double me-1.5"></i> Clear Errors
        </a>

        <a href="/audit-logs/export" class="btn btn-light btn-sm px-3 shadow-sm fw-semibold text-dark">
          <i class="fa-solid fa-file-excel me-1.5 text-success"></i> Export CSV
        </a>
      </div>
    </div>
  </div>

  <!-- Bento KPI Grid -->
  <div class="bento-audit-kpi-grid">
    <!-- Total Events -->
    <div class="bento-audit-kpi-card">
      <div class="bento-audit-kpi-header">
        <span class="bento-audit-kpi-label">Audit Events Logged</span>
        <div class="bento-audit-kpi-icon audit-icon-events">
          <i class="fa-solid fa-fingerprint"></i>
        </div>
      </div>
      <div class="bento-audit-kpi-value"><?= number_format($totalLogsCount) ?></div>
      <div class="bento-audit-kpi-sub">
        <span>Recorded activities in view</span>
      </div>
    </div>

    <!-- Security & System Alerts -->
    <div class="bento-audit-kpi-card">
      <div class="bento-audit-kpi-header">
        <span class="bento-audit-kpi-label">Diagnostic Errors</span>
        <div class="bento-audit-kpi-icon audit-icon-errors">
          <i class="fa-solid fa-shield-halved"></i>
        </div>
      </div>
      <div class="bento-audit-kpi-value <?= $errorCount > 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($errorCount) ?></div>
      <div class="bento-audit-kpi-sub">
        <?php if ($errorCount > 0): ?>
          <span class="text-danger fw-semibold"><i class="fa-solid fa-triangle-exclamation me-1"></i>System alerts pending</span>
        <?php else: ?>
          <span class="text-success fw-semibold"><i class="fa-solid fa-check me-1"></i>All system health nominal</span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Monitored Modules -->
    <div class="bento-audit-kpi-card">
      <div class="bento-audit-kpi-header">
        <span class="bento-audit-kpi-label">Monitored Modules</span>
        <div class="bento-audit-kpi-icon audit-icon-modules">
          <i class="fa-solid fa-cubes-stacked"></i>
        </div>
      </div>
      <div class="bento-audit-kpi-value"><?= number_format($totalModulesCount) ?></div>
      <div class="bento-audit-kpi-sub">
        <span>Active business sub-systems</span>
      </div>
    </div>

    <!-- Active User Actors -->
    <div class="bento-audit-kpi-card">
      <div class="bento-audit-kpi-header">
        <span class="bento-audit-kpi-label">Registered Actors</span>
        <div class="bento-audit-kpi-icon audit-icon-actors">
          <i class="fa-solid fa-users-gear"></i>
        </div>
      </div>
      <div class="bento-audit-kpi-value"><?= number_format($totalUsersCount) ?></div>
      <div class="bento-audit-kpi-sub">
        <span>Staff &amp; system users tracked</span>
      </div>
    </div>
  </div>

  <!-- Multi-Filter Toolbar -->
  <div class="audit-filter-card">
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
        <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold" title="Apply Filter">
          <i class="fa-solid fa-filter"></i>
        </button>
        <a href="/audit-logs" class="btn btn-light btn-sm border" title="Reset Filters">
          <i class="fa-solid fa-rotate-left"></i>
        </a>
      </div>
    </form>
  </div>

  <?php if (empty($logs)): ?>
    <div class="card card-enterprise shadow-sm border text-center py-5 p-4 bg-white mb-4 rounded-3">
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
      <div class="audit-table-card">
        <div class="table-responsive">
          <table class="audit-table">
            <thead>
              <tr>
                <th style="width: 170px;">Timestamp</th>
                <th style="width: 180px;">Actor / User</th>
                <th style="width: 120px;">Module</th>
                <th style="width: 140px;">Action</th>
                <th>Description &amp; Delta Context</th>
                <th class="text-end" style="width: 140px;">IP Address</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $log): ?>
                <?php
                  $isError = in_array($log['action'], ['SYSTEM_ERROR', 'ERROR', 'EXCEPTION'], true);
                  $rowClass = $isError ? 'is-system-error' : '';
                ?>
                <tr class="<?= $rowClass ?>">
                  <td data-label="Timestamp">
                    <span class="small font-monospace text-dark d-block fw-semibold"><?= format_datetime($log['created_at']) ?></span>
                  </td>
                  <td data-label="Actor">
                    <div class="d-flex align-items-center gap-2">
                      <div class="audit-actor-avatar">
                        <?= strtoupper(substr($log['user_name'] ?? 'S', 0, 1)) ?>
                      </div>
                      <div>
                        <div class="fw-semibold text-dark small"><?= e($log['user_name'] ?? 'System') ?></div>
                        <span class="badge bg-light text-muted border" style="font-size: 0.65rem;"><?= e($log['actor_type']) ?></span>
                      </div>
                    </div>
                  </td>
                  <td data-label="Module">
                    <span class="badge-module-pill"><?= e($log['module'] ?: 'System') ?></span>
                  </td>
                  <td data-label="Action">
                    <?php if ($isError): ?>
                      <span class="badge-audit-action badge-audit-error"><?= e($log['action']) ?></span>
                    <?php elseif ($log['action'] === 'RESOLVED_ERROR'): ?>
                      <span class="badge-audit-action badge-audit-resolved"><i class="fa-solid fa-check me-1"></i>RESOLVED</span>
                    <?php else: ?>
                      <span class="badge-audit-action badge-audit-default"><?= e($log['action']) ?></span>
                    <?php endif; ?>
                  </td>
                  <td data-label="Description & Delta">
                    <div class="small fw-semibold text-dark mb-1"><?= e($log['description']) ?></div>
                    <?php if (!empty($log['details_json'])): ?>
                      <button type="button" class="btn btn-light btn-xs border px-2 py-0.5 text-muted small" style="font-size: 0.72rem;" onclick='openAuditJsonModal(<?= json_encode($log['details_json']) ?>)'>
                        <i class="fa-solid fa-code me-1 text-primary"></i> View Delta JSON
                      </button>
                    <?php endif; ?>
                  </td>
                  <td data-label="IP Address" class="text-end">
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
      <div class="audit-timeline-card">
        <div class="audit-timeline-track">
          <?php foreach ($logs as $log): ?>
            <?php
              $isError = in_array($log['action'], ['SYSTEM_ERROR', 'ERROR', 'EXCEPTION'], true);
            ?>
            <div class="audit-timeline-node">
              <!-- Marker Dot -->
              <span class="audit-timeline-dot <?= $isError ? 'danger' : '' ?>"></span>

              <div class="audit-timeline-box <?= $isError ? 'bg-danger-subtle bg-opacity-25' : '' ?>">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-dark text-white font-monospace small"><i class="fa-regular fa-clock me-1"></i><?= format_datetime($log['created_at']) ?></span>
                    <?php if ($isError): ?>
                      <span class="badge-audit-action badge-audit-error"><?= e($log['action']) ?></span>
                    <?php else: ?>
                      <span class="badge-audit-action badge-audit-default"><?= e($log['action']) ?></span>
                    <?php endif; ?>
                    <span class="badge-module-pill"><?= e($log['module'] ?: 'System') ?></span>
                  </div>
                  <div class="small text-muted font-monospace">
                    <i class="fa-solid fa-network-wired me-1"></i><?= e($log['ip_address'] ?: '127.0.0.1') ?>
                  </div>
                </div>

                <div class="d-flex align-items-center gap-2 mb-2">
                  <div class="audit-actor-avatar" style="width: 24px; height: 24px; font-size: 0.68rem;">
                    <?= strtoupper(substr($log['user_name'] ?? 'S', 0, 1)) ?>
                  </div>
                  <strong class="text-dark small"><?= e($log['user_name'] ?? 'System') ?></strong>
                  <span class="text-muted small">&bull; <?= e($log['actor_type']) ?></span>
                </div>

                <div class="small text-dark fw-medium mb-1">
                  <?= e($log['description']) ?>
                </div>

                <?php if (!empty($log['details_json'])): ?>
                  <button type="button" class="btn btn-light btn-xs border px-2 py-0.5 text-muted small mt-1" style="font-size: 0.72rem;" onclick='openAuditJsonModal(<?= json_encode($log['details_json']) ?>)'>
                    <i class="fa-solid fa-code me-1 text-primary"></i> Inspect Details Payload
                  </button>
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
      <div class="audit-grid-container">
        <?php foreach ($logs as $log): ?>
          <?php
            $isError = in_array($log['action'], ['SYSTEM_ERROR', 'ERROR', 'EXCEPTION'], true);
          ?>
          <div class="audit-event-card <?= $isError ? 'is-error' : '' ?>">
            <div>
              <div class="d-flex align-items-center justify-content-between mb-2">
                <?php if ($isError): ?>
                  <span class="badge-audit-action badge-audit-error"><?= e($log['action']) ?></span>
                <?php else: ?>
                  <span class="badge-audit-action badge-audit-default"><?= e($log['action']) ?></span>
                <?php endif; ?>
                <span class="badge-module-pill"><?= e($log['module'] ?: 'System') ?></span>
              </div>

              <p class="text-dark small fw-semibold mb-2" style="min-height: 40px;">
                <?= e(mb_strimwidth($log['description'], 0, 120, '...')) ?>
              </p>

              <?php if (!empty($log['details_json'])): ?>
                <button type="button" class="btn btn-light btn-xs border px-2 py-0.5 text-muted small mb-3" style="font-size: 0.72rem;" onclick='openAuditJsonModal(<?= json_encode($log['details_json']) ?>)'>
                  <i class="fa-solid fa-code me-1 text-primary"></i> Inspect Payload
                </button>
              <?php endif; ?>
            </div>

            <div class="pt-2 border-top d-flex align-items-center justify-content-between small text-muted">
              <div class="d-flex align-items-center gap-1.5">
                <div class="audit-actor-avatar" style="width: 22px; height: 22px; font-size: 0.65rem;">
                  <?= strtoupper(substr($log['user_name'] ?? 'S', 0, 1)) ?>
                </div>
                <span class="fw-semibold text-dark"><?= e($log['user_name'] ?? 'System') ?></span>
              </div>
              <div class="font-monospace" style="font-size: 0.72rem;">
                <?= date('d M, H:i', strtotime($log['created_at'])) ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  <?php endif; ?>
</div>

<!-- ========================================================================= -->
<!-- MODAL: AUDIT JSON PAYLOAD INSPECTOR -->
<!-- ========================================================================= -->
<div class="modal fade" id="auditJsonModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <div class="modal-header bg-light">
        <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-code text-primary me-2"></i> Audit Event Delta Payload</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <pre class="bg-dark text-light p-3 rounded font-monospace small mb-0" id="auditJsonPayloadViewer" style="max-height: 400px; overflow-y: auto; white-space: pre-wrap; word-break: break-all;"></pre>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
function openAuditJsonModal(rawJson) {
  const viewer = document.getElementById('auditJsonPayloadViewer');
  try {
    const parsed = typeof rawJson === 'string' ? JSON.parse(rawJson) : rawJson;
    viewer.textContent = JSON.stringify(parsed, null, 2);
  } catch(e) {
    viewer.textContent = rawJson || 'No additional JSON data.';
  }
  new bootstrap.Modal(document.getElementById('auditJsonModal')).show();
}

function switchAuditView(view) {
  // Hide all view containers
  document.querySelectorAll('.audit-view-container').forEach(el => el.classList.add('d-none'));

  // Show active view container
  const target = document.getElementById('auditView' + view.charAt(0).toUpperCase() + view.slice(1));
  if (target) target.classList.remove('d-none');

  // Update button active states
  document.querySelectorAll('.audit-view-btn').forEach(btn => {
    btn.classList.remove('active');
  });

  const activeBtn = document.getElementById('btnAuditView' + view.charAt(0).toUpperCase() + view.slice(1));
  if (activeBtn) {
    activeBtn.classList.add('active');
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
