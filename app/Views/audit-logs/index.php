<?php
$pageTitle = 'Immutable Audit Trail & Compliance — VISA TRACK';
$flash = get_flash();
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

  <!-- Page Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <h3 class="fw-bold brand-font text-dark mb-0">System-Wide Immutable Audit Trail</h3>
        <span class="badge bg-primary-subtle text-primary fw-bold border"><i class="fa-solid fa-lock me-1"></i>Cryptographic Log</span>
      </div>
      <p class="text-muted small mb-0">Audit-grade record of administrative actions, stage shifts, payment receipts, document approvals, and login events.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="/audit-logs/export" class="btn btn-outline-success btn-sm px-3 bg-white shadow-sm">
        <i class="fa-solid fa-file-excel me-1"></i> Export to CSV
      </a>
    </div>
  </div>

  <!-- Multi-Filter Toolbar -->
  <div class="card card-enterprise mb-4 shadow-sm">
    <div class="card-body p-3">
      <form action="/audit-logs" method="GET" class="row g-2 align-items-center">
        <div class="col-12 col-md-3">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search description, context, IP..." value="<?= e($_GET['search'] ?? '') ?>">
          </div>
        </div>

        <div class="col-6 col-md-2">
          <select name="module" class="form-select form-select-sm">
            <option value="">All Modules</option>
            <?php foreach ($modules as $m): ?>
              <option value="<?= e($m) ?>" <?= ($_GET['module'] ?? '') === $m ? 'selected' : '' ?>><?= e($m) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-md-2">
          <select name="action" class="form-select form-select-sm">
            <option value="">All Actions</option>
            <?php foreach ($actions as $a): ?>
              <option value="<?= e($a) ?>" <?= ($_GET['action'] ?? '') === $a ? 'selected' : '' ?>><?= e($a) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-md-2">
          <select name="user_id" class="form-select form-select-sm">
            <option value="">All Actors</option>
            <?php foreach ($users as $u): ?>
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

  <!-- Audit Log Data Table -->
  <div class="card card-enterprise shadow-sm">
    <div class="table-responsive">
      <table class="table-modern mb-0">
        <thead>
          <tr>
            <th style="min-width: 160px;">Timestamp</th>
            <th style="min-width: 180px;">Actor / User</th>
            <th style="min-width: 120px;">Module</th>
            <th style="min-width: 140px;">Action</th>
            <th style="min-width: 320px;">Description &amp; Delta Context</th>
            <th class="text-end" style="min-width: 120px;">IP Address</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($logs)): ?>
            <tr><td colspan="6" class="text-center py-5 text-muted">No audit trail records match the active criteria.</td></tr>
          <?php else: ?>
            <?php foreach ($logs as $log): ?>
              <tr>
                <!-- Timestamp -->
                <td class="text-muted small" style="white-space: nowrap;">
                  <i class="fa-regular fa-clock me-1 text-primary"></i><?= format_datetime($log['created_at']) ?>
                </td>

                <!-- Actor -->
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 28px; height: 28px; font-size: 0.72rem;">
                      <?= strtoupper(substr($log['user_name'] ?? 'S', 0, 1)) ?>
                    </div>
                    <div>
                      <div class="fw-semibold small text-dark"><?= e($log['user_name'] ?? 'System / Automation') ?></div>
                      <div class="text-muted" style="font-size: 0.7rem;"><?= e($log['actor_type']) ?></div>
                    </div>
                  </div>
                </td>

                <!-- Module -->
                <td>
                  <span class="badge bg-light text-dark border px-2 py-1 fw-semibold"><?= e($log['module']) ?></span>
                </td>

                <!-- Action Badge -->
                <td>
                  <span class="badge bg-secondary-subtle text-dark fw-bold px-2 py-1"><?= e($log['action']) ?></span>
                </td>

                <!-- Description & Delta -->
                <td>
                  <div class="small fw-semibold text-dark mb-0.5"><?= e($log['description']) ?></div>
                  <?php if (!empty($log['details_json'])): ?>
                    <div class="p-1.5 bg-light rounded border text-muted font-monospace" style="font-size: 0.7rem;">
                      <?= e($log['details_json']) ?>
                    </div>
                  <?php endif; ?>
                </td>

                <!-- IP Address -->
                <td class="text-end">
                  <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.72rem;">
                    <?= e($log['ip_address']) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
