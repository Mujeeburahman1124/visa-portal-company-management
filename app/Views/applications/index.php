<?php
$pageTitle = 'Visa Applications Registry — VISA TRACK';
$flash = get_flash();
$activePreset = $_GET['preset'] ?? 'all';
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
?>

<div class="content-body">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 shadow-sm border-0" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Page Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <h3 class="fw-bold brand-font mb-1 text-dark" style="letter-spacing: -0.02em;">Visa Applications Registry</h3>
      <p class="text-muted small mb-0">Unified operations directory for tracking, filtering and processing global visa files.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="/tracking" class="btn btn-outline-primary btn-sm px-3 shadow-sm bg-white">
        <i class="fa-solid fa-route me-1"></i> Visual Tracking Hub
      </a>
      <a href="/applications/create" class="btn btn-primary btn-sm px-3 shadow-sm">
        <i class="fa-solid fa-plus me-1"></i> New Application
      </a>
    </div>
  </div>

  <!-- Quick Preset Status Filters Bar -->
  <div class="card card-enterprise mb-3 shadow-sm">
    <div class="card-body p-2">
      <div class="d-flex flex-wrap gap-2 align-items-center">
        <a href="/applications" class="btn btn-sm <?= ($activePreset === 'all' && empty($_GET['status']) && empty($_GET['priority'])) ? 'btn-primary shadow-sm' : 'btn-light border' ?> px-3 py-1.5 fw-semibold rounded-pill">
          All Files <span class="badge bg-white bg-opacity-25 text-dark ms-1"><?= $counts['all'] ?></span>
        </a>
        <a href="/applications?preset=my_assigned" class="btn btn-sm <?= $activePreset === 'my_assigned' ? 'btn-primary shadow-sm' : 'btn-light border' ?> px-3 py-1.5 fw-semibold rounded-pill">
          <i class="fa-solid fa-user-check me-1 text-primary"></i> My Assigned <span class="badge bg-primary-subtle text-primary ms-1"><?= $counts['my_assigned'] ?></span>
        </a>
        <a href="/applications?preset=critical" class="btn btn-sm <?= $activePreset === 'critical' ? 'btn-danger shadow-sm' : 'btn-light border' ?> px-3 py-1.5 fw-semibold rounded-pill">
          <i class="fa-solid fa-fire text-danger me-1"></i> Critical / Urgent <span class="badge bg-danger-subtle text-danger ms-1"><?= $counts['critical'] ?></span>
        </a>
        <a href="/applications?preset=pending_docs" class="btn btn-sm <?= $activePreset === 'pending_docs' ? 'btn-warning text-dark shadow-sm' : 'btn-light border' ?> px-3 py-1.5 fw-semibold rounded-pill">
          <i class="fa-solid fa-file-circle-exclamation text-warning me-1"></i> Pending Docs <span class="badge bg-warning-subtle text-dark ms-1"><?= $counts['pending_docs'] ?></span>
        </a>
        <a href="/applications?preset=action_required" class="btn btn-sm <?= $activePreset === 'action_required' ? 'btn-danger shadow-sm' : 'btn-light border' ?> px-3 py-1.5 fw-semibold rounded-pill">
          <i class="fa-solid fa-triangle-exclamation text-danger me-1"></i> Action Required <span class="badge bg-danger-subtle text-danger ms-1"><?= $counts['action_required'] ?></span>
        </a>
        <a href="/applications?preset=overdue" class="btn btn-sm <?= $activePreset === 'overdue' ? 'btn-dark shadow-sm' : 'btn-light border' ?> px-3 py-1.5 fw-semibold rounded-pill">
          <i class="fa-solid fa-clock-rotate-left text-danger me-1"></i> Overdue SLAs <span class="badge bg-danger text-white ms-1"><?= $counts['overdue'] ?></span>
        </a>
      </div>
    </div>
  </div>

  <!-- Multi-Field Search & Filter Toolbar -->
  <div class="card card-enterprise mb-4 shadow-sm">
    <div class="card-body p-3">
      <form action="/applications" method="GET" class="row g-2 align-items-center">
        <div class="col-12 col-lg-3">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search applicant, passport, ID..." value="<?= e($_GET['search'] ?? '') ?>">
          </div>
        </div>

        <div class="col-6 col-md-3 col-lg-2">
          <select name="status" class="form-select form-select-sm">
            <option value="">All Statuses</option>
            <option value="Draft" <?= ($_GET['status'] ?? '') === 'Draft' ? 'selected' : '' ?>>Draft</option>
            <option value="Registered" <?= ($_GET['status'] ?? '') === 'Registered' ? 'selected' : '' ?>>Registered</option>
            <option value="Documents Pending" <?= ($_GET['status'] ?? '') === 'Documents Pending' ? 'selected' : '' ?>>Documents Pending</option>
            <option value="Documents Under Verification" <?= ($_GET['status'] ?? '') === 'Documents Under Verification' ? 'selected' : '' ?>>Under Verification</option>
            <option value="Submitted" <?= ($_GET['status'] ?? '') === 'Submitted' ? 'selected' : '' ?>>Submitted to Embassy</option>
            <option value="In Process" <?= ($_GET['status'] ?? '') === 'In Process' ? 'selected' : '' ?>>In Process</option>
            <option value="Action Required" <?= ($_GET['status'] ?? '') === 'Action Required' ? 'selected' : '' ?>>Action Required</option>
            <option value="Approved" <?= ($_GET['status'] ?? '') === 'Approved' ? 'selected' : '' ?>>Approved</option>
            <option value="Rejected" <?= ($_GET['status'] ?? '') === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
          </select>
        </div>

        <div class="col-6 col-md-3 col-lg-2">
          <select name="priority" class="form-select form-select-sm">
            <option value="">All Priorities</option>
            <option value="Critical" <?= ($_GET['priority'] ?? '') === 'Critical' ? 'selected' : '' ?>>Critical</option>
            <option value="Urgent" <?= ($_GET['priority'] ?? '') === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
            <option value="High" <?= ($_GET['priority'] ?? '') === 'High' ? 'selected' : '' ?>>High</option>
            <option value="Normal" <?= ($_GET['priority'] ?? '') === 'Normal' ? 'selected' : '' ?>>Normal</option>
          </select>
        </div>

        <div class="col-6 col-md-3 col-lg-2">
          <select name="country_id" class="form-select form-select-sm">
            <option value="">All Destinations</option>
            <?php foreach ($countries as $ct): ?>
              <option value="<?= $ct['id'] ?>" <?= ((int)($_GET['country_id'] ?? 0)) === (int)$ct['id'] ? 'selected' : '' ?>>
                <?= $ct['flag_emoji'] ?> <?= e($ct['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-md-3 col-lg-2">
          <select name="staff_id" class="form-select form-select-sm">
            <option value="">All Officers</option>
            <?php foreach ($staffMembers as $stf): ?>
              <option value="<?= $stf['id'] ?>" <?= ((int)($_GET['staff_id'] ?? 0)) === (int)$stf['id'] ? 'selected' : '' ?>>
                <?= e($stf['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 col-lg-auto ms-auto d-flex justify-content-end">
          <div class="btn-group btn-group-sm w-100 w-lg-auto shadow-sm" role="group" aria-label="Filter Controls">
            <button type="submit" class="btn btn-primary px-3 fw-semibold"><i class="fa-solid fa-filter me-1"></i> Filter</button>
            <a href="/applications" class="btn btn-primary border-start border-white border-opacity-25 px-2.5" title="Reset Filters"><i class="fa-solid fa-rotate-left"></i></a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Applications Master Data Table -->
  <div class="card card-enterprise shadow-sm">
    <?php if (empty($applications)): ?>
      <div class="empty-state py-5 text-center">
        <div class="empty-state-icon mb-3" style="width: 56px; height: 56px; font-size: 1.5rem;">
          <i class="fa-solid fa-folder-open text-primary"></i>
        </div>
        <h5 class="fw-bold mb-1 text-dark">No visa applications found</h5>
        <p class="text-muted small mb-3">No applications match your active search or filter criteria.</p>
        <a href="/applications" class="btn btn-outline-secondary btn-sm me-2">Clear Filters</a>
        <a href="/applications/create" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i> New Application</a>
      </div>
    <?php else: ?>
      <!-- Desktop & Tablet Responsive Table -->
      <div class="table-responsive">
        <table class="table-modern mb-0">
          <thead>
            <tr>
              <th style="min-width: 150px;">Application #</th>
              <th style="min-width: 200px;">Applicant &amp; Passport</th>
              <th style="min-width: 220px;">Visa Service</th>
              <th style="min-width: 180px;">Current Stage</th>
              <th style="min-width: 130px;">Status</th>
              <th style="min-width: 110px;">Priority</th>
              <th style="min-width: 150px;">Officer</th>
              <th style="min-width: 130px;">Deadline</th>
              <th class="text-end" style="min-width: 100px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($applications as $app): ?>
              <?php
                $status = $app['status'] ?? 'Draft';
                $priority = strtolower($app['priority'] ?? 'normal');
                $prioBadgeClass = ($priority === 'critical') ? 'badge-priority-critical' : (($priority === 'urgent' || $priority === 'high') ? 'badge-priority-urgent' : 'badge-priority-normal');
                
                // Deadline computation
                $dlLabel = '—';
                $dlBadge = 'bg-light text-secondary border';
                if (!empty($app['expected_completion_date'])) {
                    $diff = (int)round((strtotime($app['expected_completion_date']) - strtotime(date('Y-m-d'))) / 86400);
                    if ($diff < 0) {
                        $dlLabel = abs($diff) . 'd overdue';
                        $dlBadge = 'bg-danger text-white fw-bold';
                    } elseif ($diff === 0) {
                        $dlLabel = 'Due today';
                        $dlBadge = 'bg-warning text-dark fw-bold';
                    } elseif ($diff <= 3) {
                        $dlLabel = $diff . 'd left';
                        $dlBadge = 'bg-warning-subtle text-dark fw-semibold';
                    } else {
                        $dlLabel = format_date($app['expected_completion_date']);
                        $dlBadge = 'bg-light text-dark border';
                    }
                }
              ?>
              <tr>
                <!-- Application Number -->
                <td>
                  <a href="/applications/show?id=<?= $app['id'] ?>" class="badge bg-primary-subtle text-primary fw-bold text-decoration-none px-2.5 py-1.5 d-inline-flex align-items-center gap-1" style="font-size: 0.82rem; border: 1px solid var(--vt-primary-border);">
                    <i class="fa-solid fa-folder me-1"></i><?= e($app['application_number']) ?>
                  </a>
                  <div class="text-muted mt-1" style="font-size: 0.72rem;">
                    <i class="fa-regular fa-calendar me-1"></i><?= format_date($app['application_date'] ?? $app['created_at']) ?>
                  </div>
                </td>

                <!-- Applicant & Passport -->
                <td>
                  <div class="fw-bold text-dark fs-6"><?= e($app['customer_name']) ?></div>
                  <div class="mt-1">
                    <span class="badge bg-light text-secondary border px-2 py-0.5" style="font-size: 0.73rem;">
                      <i class="fa-solid fa-passport text-primary me-1"></i><?= e($app['passport_number'] ?: $app['current_passport'] ?: '—') ?>
                    </span>
                  </div>
                </td>

                <!-- Visa Service & Destination -->
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <span class="fs-5"><?= $app['flag_emoji'] ?></span>
                    <div>
                      <div class="fw-bold text-dark"><?= e($app['country_name']) ?></div>
                      <div class="text-muted small text-truncate" style="max-width: 190px; font-size: 0.74rem;">
                        <?= e($app['service_name']) ?>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Current Stage -->
                <td>
                  <span class="badge bg-light text-dark border px-2.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;">
                    <i class="fa-solid fa-circle-dot text-primary me-1" style="font-size: 0.55rem;"></i>
                    <?= e($app['current_stage']) ?>
                  </span>
                </td>

                <!-- Status Badge -->
                <td>
                  <?php if ($status === 'Approved' || $status === 'Completed'): ?>
                    <span class="badge bg-success-subtle text-success fw-bold px-2.5 py-1.5" style="font-size: 0.74rem; border: 1px solid var(--vt-success-border);">
                      <i class="fa-solid fa-circle-check me-1"></i><?= e($status) ?>
                    </span>
                  <?php elseif ($status === 'Action Required' || str_contains(strtolower($status), 'action')): ?>
                    <span class="badge bg-danger-subtle text-danger fw-bold px-2.5 py-1.5" style="font-size: 0.74rem; border: 1px solid var(--vt-danger-border);">
                      <i class="fa-solid fa-triangle-exclamation me-1"></i><?= e($status) ?>
                    </span>
                  <?php elseif ($status === 'In Process'): ?>
                    <span class="badge bg-info-subtle text-info fw-bold px-2.5 py-1.5" style="font-size: 0.74rem; border: 1px solid var(--vt-info-border);">
                      <i class="fa-solid fa-spinner fa-spin-pulse me-1"></i>In Process
                    </span>
                  <?php elseif ($status === 'Submitted'): ?>
                    <span class="badge bg-primary-subtle text-primary fw-bold px-2.5 py-1.5" style="font-size: 0.74rem; border: 1px solid var(--vt-primary-border);">
                      <i class="fa-solid fa-paper-plane me-1"></i>Submitted
                    </span>
                  <?php else: ?>
                    <span class="badge bg-secondary-subtle text-dark fw-bold px-2.5 py-1.5" style="font-size: 0.74rem; border: 1px solid #cbd5e1;">
                      <?= e($status) ?>
                    </span>
                  <?php endif; ?>
                </td>

                <!-- Priority -->
                <td>
                  <span class="badge <?= $prioBadgeClass ?>" style="font-size: 0.72rem;">
                    <i class="fa-solid <?= $priority === 'critical' ? 'fa-fire' : ($priority === 'urgent' ? 'fa-bolt' : 'fa-circle-check') ?> me-1"></i>
                    <?= e($app['priority']) ?>
                  </span>
                </td>

                <!-- Assigned Staff -->
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 26px; height: 26px; font-size: 0.7rem;">
                      <?= strtoupper(substr($app['staff_name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <span class="small fw-semibold text-dark text-truncate" style="max-width: 110px;"><?= e($app['staff_name'] ?? 'Unassigned') ?></span>
                  </div>
                </td>

                <!-- Deadline -->
                <td>
                  <span class="badge <?= $dlBadge ?> px-2 py-1" style="font-size: 0.74rem;">
                    <i class="fa-regular fa-clock me-1"></i><?= $dlLabel ?>
                  </span>
                </td>

                <!-- Action Button -->
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a href="/applications/show?id=<?= $app['id'] ?>" class="btn btn-primary py-1 px-2.5 fw-semibold shadow-sm" title="View Application Details">
                      <i class="fa-solid fa-eye"></i>
                    </a>
                    <a href="/applications/edit?id=<?= $app['id'] ?>" class="btn btn-outline-secondary py-1 px-2" title="Edit Application">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                    <form action="/applications/delete" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete application <?= e($app['application_number']) ?>?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                      <button type="submit" class="btn btn-outline-danger py-1 px-2" title="Delete Application">
                        <i class="fa-solid fa-trash-can"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
