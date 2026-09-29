<?php
$pageTitle = 'Appointments Scheduler — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';

$currentView = $_GET['view'] ?? 'table';
if (!in_array($currentView, ['table', 'grid', 'timeline'], true)) {
    $currentView = 'table';
}
?>

<div class="content-body">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Page Header & Action Controls -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <div class="d-flex align-items-center gap-2">
        <h3 class="fw-bold brand-font mb-0 text-dark"><i class="fa-solid fa-calendar-check text-primary me-2"></i> Visa &amp; Embassy Appointments</h3>
        <span class="badge bg-primary-subtle text-primary border rounded-pill"><?= count($appointments) ?> Scheduled</span>
      </div>
      <p class="text-muted small mb-0 mt-1">Manage and schedule Biometrics, VFS appointments, Embassy consular interviews, and Medical fitness tests.</p>
    </div>
    
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <!-- 3 View Options Switcher -->
      <div class="btn-group btn-group-sm bg-white shadow-sm border rounded-pill p-1" role="group" aria-label="View Mode">
        <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold view-toggle-btn <?= $currentView === 'table' ? 'btn-primary shadow-sm' : 'btn-light text-muted' ?>" onclick="switchAptView('table')" id="btnViewTable">
          <i class="fa-solid fa-table-list me-1"></i> <span class="d-none d-sm-inline">Table</span>
        </button>
        <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold view-toggle-btn <?= $currentView === 'grid' ? 'btn-primary shadow-sm' : 'btn-light text-muted' ?>" onclick="switchAptView('grid')" id="btnViewGrid">
          <i class="fa-solid fa-grip me-1"></i> <span class="d-none d-sm-inline">Grid Cards</span>
        </button>
        <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold view-toggle-btn <?= $currentView === 'timeline' ? 'btn-primary shadow-sm' : 'btn-light text-muted' ?>" onclick="switchAptView('timeline')" id="btnViewTimeline">
          <i class="fa-solid fa-timeline me-1"></i> <span class="d-none d-sm-inline">Timeline</span>
        </button>
      </div>

      <!-- Add Appointment Type Button -->
      <button type="button" class="btn btn-outline-primary btn-sm px-3 shadow-sm bg-white" data-bs-toggle="modal" data-bs-target="#newAptTypeModal">
        <i class="fa-solid fa-plus-circle me-1"></i> Add Type
      </button>

      <!-- Schedule Appointment Button -->
      <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#newAptModal">
        <i class="fa-solid fa-calendar-plus me-1"></i> Schedule Appointment
      </button>
    </div>
  </div>

  <!-- Search & Filter Toolbar -->
  <div class="card card-enterprise shadow-sm border mb-4 bg-white">
    <div class="card-body p-3">
      <form action="/appointments" method="GET" class="row g-2 align-items-center" id="aptFilterForm">
        <input type="hidden" name="view" id="activeViewParam" value="<?= e($currentView) ?>">
        <div class="col-12 col-md-5">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search applicant, app #, venue, ref #..." value="<?= e($_GET['search'] ?? '') ?>">
          </div>
        </div>
        <div class="col-6 col-md-3">
          <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Appointment Types</option>
            <?php foreach (($appointmentTypes ?? []) as $at): ?>
              <option value="<?= e($at['name']) ?>" <?= ($_GET['type'] ?? '') === $at['name'] ? 'selected' : '' ?>><?= e($at['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="Scheduled" <?= ($_GET['status'] ?? '') === 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
            <option value="Confirmed" <?= ($_GET['status'] ?? '') === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
            <option value="Completed" <?= ($_GET['status'] ?? '') === 'Completed' ? 'selected' : '' ?>>Completed</option>
            <option value="Missed" <?= ($_GET['status'] ?? '') === 'Missed' ? 'selected' : '' ?>>Missed</option>
            <option value="Cancelled" <?= ($_GET['status'] ?? '') === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            <option value="Rescheduled" <?= ($_GET['status'] ?? '') === 'Rescheduled' ? 'selected' : '' ?>>Rescheduled</option>
          </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-1">
          <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold"><i class="fa-solid fa-filter me-1"></i> Filter</button>
          <a href="/appointments" class="btn btn-light btn-sm border" title="Reset Filters"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
      </form>
    </div>
  </div>

  <?php if (empty($appointments)): ?>
    <div class="card card-enterprise shadow-sm border text-center py-5 p-4 bg-white mb-4">
      <div class="mb-3">
        <i class="fa-regular fa-calendar-xmark text-muted fs-1 opacity-50"></i>
      </div>
      <h5 class="fw-bold text-dark mb-1">No Appointments Found</h5>
      <p class="text-muted small mb-3">No biometric, consular, or medical appointments match your active filter criteria.</p>
      <div>
        <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#newAptModal">
          <i class="fa-solid fa-calendar-plus me-1"></i> Schedule First Appointment
        </button>
      </div>
    </div>
  <?php else: ?>

    <!-- ================================================================= -->
    <!-- VIEW OPTION 1: TABLE VIEW -->
    <!-- ================================================================= -->
    <div id="aptViewTable" class="apt-view-container <?= $currentView === 'table' ? '' : 'd-none' ?>">
      <div class="card card-enterprise shadow-sm border">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-3">Appointment Type</th>
                <th>Center / Location</th>
                <th>Applicant &amp; App #</th>
                <th>Date &amp; Time</th>
                <th>Reference #</th>
                <th>Assigned Officer</th>
                <th>Status</th>
                <th class="text-end pe-3">Update</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($appointments as $apt): ?>
                <?php
                  $badge = 'bg-primary';
                  if ($apt['status'] === 'Completed') $badge = 'bg-success';
                  elseif ($apt['status'] === 'Confirmed') $badge = 'bg-info text-white';
                  elseif ($apt['status'] === 'Cancelled' || $apt['status'] === 'Missed') $badge = 'bg-danger';
                  elseif ($apt['status'] === 'Rescheduled') $badge = 'bg-warning text-dark';
                ?>
                <tr>
                  <td class="ps-3">
                    <div class="d-flex align-items-center gap-2">
                      <div class="avatar-sm rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center p-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                        <i class="fa-solid fa-calendar-day"></i>
                      </div>
                      <span class="fw-bold text-dark"><?= e($apt['appointment_type']) ?></span>
                    </div>
                  </td>
                  <td>
                    <div class="fw-semibold text-primary"><?= e($apt['center_name']) ?></div>
                    <div class="text-muted small" style="font-size: 0.75rem;"><i class="fa-solid fa-location-dot me-1 text-muted"></i><?= e($apt['location_address'] ?: 'Consular / Center Location') ?></div>
                  </td>
                  <td>
                    <a href="/applications/show?id=<?= $apt['app_id'] ?>" class="fw-bold text-decoration-none"><?= e($apt['application_number']) ?></a>
                    <div class="text-muted small fw-medium"><?= e($apt['customer_name']) ?></div>
                  </td>
                  <td>
                    <div class="fw-bold text-dark"><?= format_date($apt['appointment_date']) ?></div>
                    <div class="text-muted small" style="font-size: 0.75rem;"><i class="fa-regular fa-clock me-1"></i><?= date('h:i A', strtotime($apt['appointment_time'])) ?></div>
                  </td>
                  <td><span class="badge bg-light text-dark border font-monospace"><?= e($apt['reference_number'] ?: '—') ?></span></td>
                  <td><span class="small fw-semibold"><?= e($apt['staff_name'] ?? 'Unassigned') ?></span></td>
                  <td>
                    <span class="badge <?= $badge ?> px-2.5 py-1" style="font-size: 0.72rem;"><?= e($apt['status']) ?></span>
                  </td>
                  <td class="text-end pe-3">
                    <div class="btn-group">
                      <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2.5 fw-semibold shadow-sm" 
                        onclick="openAptStatusModal(<?= (int)$apt['id'] ?>, '<?= e($apt['status']) ?>', '<?= e($apt['application_number']) ?>', '<?= e($apt['customer_name']) ?>')">
                        Status <i class="fa-solid fa-pen-to-square ms-1"></i>
                      </button>
                      <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle dropdown-toggle-split py-1 px-2" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                        <span class="visually-hidden">Toggle</span>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end shadow border-0 small">
                        <li>
                          <form action="/appointments/status" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                            <input type="hidden" name="status" value="Confirmed">
                            <button type="submit" class="dropdown-item py-2"><i class="fa-solid fa-check text-info me-2"></i> Mark Confirmed</button>
                          </form>
                        </li>
                        <li>
                          <form action="/appointments/status" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                            <input type="hidden" name="status" value="Completed">
                            <button type="submit" class="dropdown-item py-2"><i class="fa-solid fa-circle-check text-success me-2"></i> Mark Completed</button>
                          </form>
                        </li>
                        <li>
                          <form action="/appointments/status" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                            <input type="hidden" name="status" value="Missed">
                            <button type="submit" class="dropdown-item py-2"><i class="fa-solid fa-circle-xmark text-danger me-2"></i> Mark Missed</button>
                          </form>
                        </li>
                        <li>
                          <form action="/appointments/status" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                            <input type="hidden" name="status" value="Cancelled">
                            <button type="submit" class="dropdown-item py-2"><i class="fa-solid fa-ban text-secondary me-2"></i> Mark Cancelled</button>
                          </form>
                        </li>
                      </ul>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ================================================================= -->
    <!-- VIEW OPTION 2: GRID CARDS VIEW -->
    <!-- ================================================================= -->
    <div id="aptViewGrid" class="apt-view-container <?= $currentView === 'grid' ? '' : 'd-none' ?>">
      <div class="row g-3">
        <?php foreach ($appointments as $apt): ?>
          <?php
            $badge = 'bg-primary';
            $borderClass = 'border-primary';
            if ($apt['status'] === 'Completed') { $badge = 'bg-success'; $borderClass = 'border-success'; }
            elseif ($apt['status'] === 'Confirmed') { $badge = 'bg-info text-white'; $borderClass = 'border-info'; }
            elseif ($apt['status'] === 'Cancelled' || $apt['status'] === 'Missed') { $badge = 'bg-danger'; $borderClass = 'border-danger'; }
            elseif ($apt['status'] === 'Rescheduled') { $badge = 'bg-warning text-dark'; $borderClass = 'border-warning'; }
          ?>
          <div class="col-12 col-md-6 col-lg-4">
            <div class="card card-enterprise h-100 shadow-sm border border-start-4 <?= $borderClass ?>">
              <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                <div>
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-light text-dark border small fw-semibold">
                      <i class="fa-solid fa-tag me-1 text-primary"></i><?= e($apt['appointment_type']) ?>
                    </span>
                    <span class="badge <?= $badge ?> px-2 py-0.5" style="font-size: 0.72rem;"><?= e($apt['status']) ?></span>
                  </div>

                  <h6 class="fw-bold text-dark mb-1 d-flex align-items-center gap-1.5">
                    <i class="fa-solid fa-building text-primary small"></i>
                    <span><?= e($apt['center_name']) ?></span>
                  </h6>
                  <p class="text-muted small mb-3" style="font-size: 0.75rem;">
                    <i class="fa-solid fa-location-dot me-1 text-danger"></i><?= e($apt['location_address'] ?: 'Application Support Center') ?>
                  </p>

                  <div class="p-2.5 bg-light rounded-3 border mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="text-muted small">Applicant:</span>
                      <strong class="text-dark small"><?= e($apt['customer_name']) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="text-muted small">Application #:</span>
                      <a href="/applications/show?id=<?= $apt['app_id'] ?>" class="small fw-bold text-decoration-none"><?= e($apt['application_number']) ?></a>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="text-muted small">Booking Ref:</span>
                      <span class="font-monospace small fw-semibold text-secondary"><?= e($apt['reference_number'] ?: 'N/A') ?></span>
                    </div>
                  </div>
                </div>

                <div class="pt-2 border-top d-flex align-items-center justify-content-between">
                  <div>
                    <div class="small fw-bold text-dark"><i class="fa-regular fa-calendar me-1 text-primary"></i><?= format_date($apt['appointment_date']) ?></div>
                    <div class="text-muted" style="font-size: 0.72rem;"><i class="fa-regular fa-clock me-1"></i><?= date('h:i A', strtotime($apt['appointment_time'])) ?> &bull; <?= e($apt['staff_name'] ?? 'Unassigned') ?></div>
                  </div>
                  <button type="button" class="btn btn-sm btn-outline-primary px-2.5 py-1 fw-semibold shadow-sm"
                    onclick="openAptStatusModal(<?= (int)$apt['id'] ?>, '<?= e($apt['status']) ?>', '<?= e($apt['application_number']) ?>', '<?= e($apt['customer_name']) ?>')">
                    Status <i class="fa-solid fa-pen-to-square ms-1"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ================================================================= -->
    <!-- VIEW OPTION 3: TIMELINE / CHRONOLOGICAL VIEW -->
    <!-- ================================================================= -->
    <div id="aptViewTimeline" class="apt-view-container <?= $currentView === 'timeline' ? '' : 'd-none' ?>">
      <div class="card card-enterprise shadow-sm border p-3 p-md-4 bg-white">
        <div class="timeline-container position-relative ps-4" style="border-left: 2px solid #e2e8f0; margin-left: 12px;">
          <?php foreach ($appointments as $apt): ?>
            <?php
              $badge = 'bg-primary';
              $dotColor = '#2563eb';
              if ($apt['status'] === 'Completed') { $badge = 'bg-success'; $dotColor = '#10b981'; }
              elseif ($apt['status'] === 'Confirmed') { $badge = 'bg-info text-white'; $dotColor = '#06b6d4'; }
              elseif ($apt['status'] === 'Cancelled' || $apt['status'] === 'Missed') { $badge = 'bg-danger'; $dotColor = '#ef4444'; }
              elseif ($apt['status'] === 'Rescheduled') { $badge = 'bg-warning text-dark'; $dotColor = '#f59e0b'; }
            ?>
            <div class="timeline-item position-relative mb-4">
              <!-- Timeline Marker Dot -->
              <span class="position-absolute rounded-circle shadow-sm" style="left: -29px; top: 4px; width: 14px; height: 14px; background-color: <?= $dotColor ?>; border: 3px solid #ffffff;"></span>

              <div class="card shadow-sm border p-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-dark text-white font-monospace small"><i class="fa-regular fa-calendar me-1"></i><?= format_date($apt['appointment_date']) ?> @ <?= date('h:i A', strtotime($apt['appointment_time'])) ?></span>
                    <span class="badge bg-light text-dark border small fw-semibold"><?= e($apt['appointment_type']) ?></span>
                  </div>
                  <span class="badge <?= $badge ?> px-2.5 py-1" style="font-size: 0.72rem;"><?= e($apt['status']) ?></span>
                </div>

                <div class="row g-2 align-items-center">
                  <div class="col-md-5">
                    <div class="fw-bold text-dark fs-6"><?= e($apt['center_name']) ?></div>
                    <div class="text-muted small"><i class="fa-solid fa-location-dot me-1 text-danger"></i><?= e($apt['location_address'] ?: 'Consular / Center Venue') ?></div>
                  </div>
                  <div class="col-md-4">
                    <div class="small text-muted">Applicant: <strong class="text-dark"><?= e($apt['customer_name']) ?></strong></div>
                    <div class="small">File: <a href="/applications/show?id=<?= $apt['app_id'] ?>" class="fw-semibold text-decoration-none"><?= e($apt['application_number']) ?></a> (Ref: <span class="font-monospace text-muted"><?= e($apt['reference_number'] ?: '—') ?></span>)</div>
                  </div>
                  <div class="col-md-3 text-md-end">
                    <button type="button" class="btn btn-outline-primary btn-sm px-3 fw-semibold shadow-sm"
                      onclick="openAptStatusModal(<?= (int)$apt['id'] ?>, '<?= e($apt['status']) ?>', '<?= e($apt['application_number']) ?>', '<?= e($apt['customer_name']) ?>')">
                      Update Status <i class="fa-solid fa-pen-to-square ms-1"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  <?php endif; ?>
</div>

<!-- ================================================================= -->
<!-- MODAL: ADD APPOINTMENT TYPE -->
<!-- ================================================================= -->
<div class="modal fade" id="newAptTypeModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="/appointments/types/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-header bg-primary text-white">
          <h6 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle me-2"></i> Add New Appointment Type</h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Appointment Type Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Passport Drop-off, Embassy VIP Lounge, DNA Verification" required>
            <div class="form-text small">Descriptive label shown across appointment bookings and filters.</div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Description (Optional)</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Brief note on requirements or instructions for this appointment category..."></textarea>
          </div>

          <div class="p-3 bg-light rounded-3 border">
            <div class="fw-semibold small text-dark mb-1"><i class="fa-solid fa-list-check text-primary me-1"></i> Current Appointment Types:</div>
            <div class="d-flex flex-wrap gap-1">
              <?php foreach (($appointmentTypes ?? []) as $at): ?>
                <span class="badge bg-white text-dark border small fw-normal py-1 px-2"><?= e($at['name']) ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
            <i class="fa-solid fa-check me-1"></i> Save Appointment Type
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ================================================================= -->
<!-- MODAL: UPDATE APPOINTMENT STATUS -->
<!-- ================================================================= -->
<div class="modal fade" id="updateAptStatusModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-calendar-check me-2"></i> Update Appointment Status</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/appointments/status" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="appointment_id" id="modalAptId" value="0">
        <div class="modal-body p-4">
          <div class="mb-3 p-3 bg-light rounded-3 border">
            <div class="small text-muted">Application &amp; Applicant:</div>
            <div class="fw-bold text-dark fs-6" id="modalAptAppInfo">—</div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Select New Status <span class="text-danger">*</span></label>
            <select name="status" id="modalAptStatusSelect" class="form-select" required>
              <option value="Confirmed">Confirmed (Biometrics / Consular Confirmed)</option>
              <option value="Completed">Completed (Applicant Attended Successfully)</option>
              <option value="Scheduled">Scheduled (Upcoming / Pending)</option>
              <option value="Rescheduled">Rescheduled (Shifted to New Date)</option>
              <option value="Missed">Missed (Applicant Did Not Attend)</option>
              <option value="Cancelled">Cancelled (Appointment Withdrawn)</option>
            </select>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">Save Status</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ================================================================= -->
<!-- MODAL: SCHEDULE APPOINTMENT -->
<!-- ================================================================= -->
<div class="modal fade" id="newAptModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-calendar-plus me-2"></i> Schedule Appointment</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/appointments/store" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Visa Application <span class="text-danger">*</span></label>
              <select name="application_id" class="form-select" required>
                <option value="">-- Select Active Application --</option>
                <?php foreach ($activeApplications as $a): ?>
                  <option value="<?= $a['id'] ?>"><?= e($a['application_number']) ?> &bull; <?= e($a['customer_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label small fw-semibold mb-0">Appointment Type <span class="text-danger">*</span></label>
                <a href="javascript:void(0)" class="small text-decoration-none text-primary fw-semibold" onclick="document.getElementById('customTypeContainer').classList.toggle('d-none');">+ Add New</a>
              </div>
              <select name="appointment_type" id="schedAptTypeSelect" class="form-select" required onchange="handleTypeSelectChange(this)">
                <?php foreach (($appointmentTypes ?? []) as $at): ?>
                  <option value="<?= e($at['name']) ?>"><?= e($at['name']) ?></option>
                <?php endforeach; ?>
                <option value="custom">+ Other / Custom Type...</option>
              </select>
            </div>
          </div>

          <div id="customTypeContainer" class="mb-3 p-3 bg-light rounded-3 border d-none">
            <label class="form-label small fw-semibold text-primary"><i class="fa-solid fa-pen-nib me-1"></i> Specify Custom Appointment Type</label>
            <input type="text" name="custom_appointment_type" id="customTypeInput" class="form-control" placeholder="Type new appointment category name...">
            <div class="form-text small">This new category will automatically be saved to your system appointment types.</div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Center / Venue Name <span class="text-danger">*</span></label>
              <input type="text" name="center_name" class="form-control" placeholder="e.g. VFS Global Wafi Mall, Smart Salem Al Quoz" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Location Address</label>
              <input type="text" name="location_address" class="form-control" placeholder="e.g. Level 3, Wafi City Mall, Oud Metha, Dubai">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Date <span class="text-danger">*</span></label>
              <input type="date" name="appointment_date" class="form-control" required value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Time <span class="text-danger">*</span></label>
              <input type="time" name="appointment_time" class="form-control" required value="09:30">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Booking Ref # (Slip / Barcode)</label>
              <input type="text" name="reference_number" class="form-control" placeholder="e.g. VFS-DXB-99881">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Assigned Accompanying Officer</label>
              <select name="assigned_staff_id" class="form-select">
                <option value="">-- Auto-assign active staff --</option>
                <?php foreach (($staffMembers ?? []) as $sm): ?>
                  <option value="<?= $sm['id'] ?>"><?= e($sm['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Upload Appointment Letter / Slip (PDF/JPG)</label>
              <input type="file" name="document_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label small fw-semibold">Special Instructions / Applicant Notes</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Original passport, 2 biometric photos, and bank statements required on day of visit."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
            <i class="fa-solid fa-check me-1"></i> Confirm &amp; Notify Applicant
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function switchAptView(view) {
  // Hide all view containers
  document.querySelectorAll('.apt-view-container').forEach(el => el.classList.add('d-none'));

  // Show active view container
  const target = document.getElementById('aptView' + view.charAt(0).toUpperCase() + view.slice(1));
  if (target) target.classList.remove('d-none');

  // Update button active states
  document.querySelectorAll('.view-toggle-btn').forEach(btn => {
    btn.classList.remove('btn-primary', 'shadow-sm');
    btn.classList.add('btn-light', 'text-muted');
  });

  const activeBtn = document.getElementById('btnView' + view.charAt(0).toUpperCase() + view.slice(1));
  if (activeBtn) {
    activeBtn.classList.remove('btn-light', 'text-muted');
    activeBtn.classList.add('btn-primary', 'shadow-sm');
  }

  // Update hidden input in filter form
  const viewInput = document.getElementById('activeViewParam');
  if (viewInput) viewInput.value = view;

  // Persist preference in localStorage
  try { localStorage.setItem('apt_active_view', view); } catch (e) {}
}

function handleTypeSelectChange(sel) {
  const customBox = document.getElementById('customTypeContainer');
  if (sel.value === 'custom') {
    customBox.classList.remove('d-none');
    document.getElementById('customTypeInput').focus();
  } else {
    customBox.classList.add('d-none');
  }
}

function openAptStatusModal(aptId, currentStatus, appNo, custName) {
  document.getElementById('modalAptId').value = aptId;
  document.getElementById('modalAptAppInfo').innerText = appNo + ' (' + custName + ')';
  const sel = document.getElementById('modalAptStatusSelect');
  for (let i = 0; i < sel.options.length; i++) {
    if (sel.options[i].value === currentStatus) {
      sel.selectedIndex = i;
      break;
    }
  }
  const modalEl = document.getElementById('updateAptStatusModal');
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
}

// Restore saved view preference if URL does not specify one
document.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  if (!urlParams.has('view')) {
    const saved = localStorage.getItem('apt_active_view');
    if (saved && ['table', 'grid', 'timeline'].includes(saved)) {
      switchAptView(saved);
    }
  }
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
