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

$aptCounts = [
    'total' => count($appointments),
    'scheduled' => 0,
    'confirmed' => 0,
    'completed' => 0,
    'attention' => 0,
];
foreach ($appointments as $a) {
    $st = $a['status'] ?? '';
    if ($st === 'Scheduled') $aptCounts['scheduled']++;
    elseif ($st === 'Confirmed') $aptCounts['confirmed']++;
    elseif ($st === 'Completed') $aptCounts['completed']++;
    elseif (in_array($st, ['Missed', 'Cancelled', 'Rescheduled'])) $aptCounts['attention']++;
}
?>

<!-- Load Bento Design System CSS & Page Styles -->
<link rel="stylesheet" href="/assets/css/dashboard-bento.css?v=<?= time() ?>">
<link rel="stylesheet" href="/assets/css/pages/appointments-index.css?v=<?= time() ?>">

<div class="content-body p-0 appointments-page">
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
          <i class="fa-solid fa-calendar-check"></i>
          <span>Operations &amp; Biometrics</span>
        </div>
        <h1 class="bento-header-title">Embassy &amp; Consular Appointments</h1>
        <p class="bento-header-sub">
          Schedule and monitor Biometrics capture, VFS bookings, Consular interviews &amp; Medical fitness tests.
        </p>
      </div>

      <div class="apt-header-actions">
        <!-- 3-Mode View Switcher -->
        <div class="apt-segmented me-1" role="group" aria-label="View Mode">
          <button type="button" class="<?= $currentView === 'table' ? 'active' : '' ?>" id="btnViewTable" onclick="switchAptView('table')" title="Tabular View">
            <i class="fa-solid fa-table-list"></i> Table
          </button>
          <button type="button" class="<?= $currentView === 'grid' ? 'active' : '' ?>" id="btnViewGrid" onclick="switchAptView('grid')" title="Card Grid View">
            <i class="fa-solid fa-grip"></i> Cards
          </button>
          <button type="button" class="<?= $currentView === 'timeline' ? 'active' : '' ?>" id="btnViewTimeline" onclick="switchAptView('timeline')" title="Timeline View">
            <i class="fa-solid fa-timeline"></i> Timeline
          </button>
        </div>

        <button type="button" class="bento-btn-outline" data-bs-toggle="modal" data-bs-target="#newAptTypeModal" title="Manage Appointment Categories">
          <i class="fa-solid fa-plus-circle"></i> Add Type
        </button>
        <button type="button" class="bento-btn-primary" data-bs-toggle="modal" data-bs-target="#newAptModal" title="Schedule New Appointment">
          <i class="fa-solid fa-calendar-plus"></i> Schedule Appointment
        </button>
      </div>
    </header>

    <!-- ─── 2. EXECUTIVE KPI STRIP ───────────────────────────────────────── -->
    <div class="apt-kpi-grid">
      <!-- Total Scheduled -->
      <a href="/appointments" class="apt-kpi-card tone-primary">
        <div class="apt-kpi-top">
          <span class="apt-kpi-label">Total Bookings</span>
          <div class="apt-kpi-icon"><i class="fa-solid fa-calendar-days"></i></div>
        </div>
        <div class="apt-kpi-val"><?= (int)$aptCounts['total'] ?></div>
        <span class="apt-kpi-sub">Total booked roster</span>
      </a>

      <!-- Confirmed -->
      <a href="/appointments?status=Confirmed" class="apt-kpi-card tone-info">
        <div class="apt-kpi-top">
          <span class="apt-kpi-label">Confirmed</span>
          <div class="apt-kpi-icon"><i class="fa-solid fa-circle-check"></i></div>
        </div>
        <div class="apt-kpi-val"><?= (int)$aptCounts['confirmed'] ?></div>
        <span class="apt-kpi-sub">Ready for consular date</span>
      </a>

      <!-- Completed -->
      <a href="/appointments?status=Completed" class="apt-kpi-card tone-success">
        <div class="apt-kpi-top">
          <span class="apt-kpi-label">Attended / Completed</span>
          <div class="apt-kpi-icon"><i class="fa-solid fa-user-check"></i></div>
        </div>
        <div class="apt-kpi-val"><?= (int)$aptCounts['completed'] ?></div>
        <span class="apt-kpi-sub">Biometrics captured</span>
      </a>

      <!-- Attention / Missed / Rescheduled -->
      <a href="/appointments?status=Rescheduled" class="apt-kpi-card tone-warning">
        <div class="apt-kpi-top">
          <span class="apt-kpi-label">Action / Rescheduled</span>
          <div class="apt-kpi-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
        </div>
        <div class="apt-kpi-val"><?= (int)$aptCounts['attention'] ?></div>
        <span class="apt-kpi-sub">Follow-up needed</span>
      </a>
    </div>

    <!-- ─── 3. ADVANCED SEARCH & FILTER TOOLBAR ───────────────────────────── -->
    <div class="apt-filter-card">
      <form action="/appointments" method="GET" class="row g-2.5 align-items-center" id="aptFilterForm">
        <input type="hidden" name="view" id="activeViewParam" value="<?= e($currentView) ?>">

        <div class="col-12 col-md-5 col-xl-4">
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Search applicant, app #, venue, ref #..." value="<?= e($_GET['search'] ?? '') ?>">
          </div>
        </div>

        <div class="col-6 col-md-3 col-xl-3">
          <select name="type" class="form-select form-select-sm" title="Appointment Type" onchange="this.form.submit()">
            <option value="">All Appointment Types</option>
            <?php foreach (($appointmentTypes ?? []) as $at): ?>
              <option value="<?= e($at['name']) ?>" <?= ($_GET['type'] ?? '') === $at['name'] ? 'selected' : '' ?>><?= e($at['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-md-2 col-xl-2.5">
          <select name="status" class="form-select form-select-sm" title="Status" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="Scheduled" <?= ($_GET['status'] ?? '') === 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
            <option value="Confirmed" <?= ($_GET['status'] ?? '') === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
            <option value="Completed" <?= ($_GET['status'] ?? '') === 'Completed' ? 'selected' : '' ?>>Completed</option>
            <option value="Missed" <?= ($_GET['status'] ?? '') === 'Missed' ? 'selected' : '' ?>>Missed</option>
            <option value="Cancelled" <?= ($_GET['status'] ?? '') === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            <option value="Rescheduled" <?= ($_GET['status'] ?? '') === 'Rescheduled' ? 'selected' : '' ?>>Rescheduled</option>
          </select>
        </div>

        <div class="col-12 col-md-auto ms-auto d-flex align-items-center gap-1.5">
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.15rem; font-size: 0.82rem;">
            <i class="fa-solid fa-filter me-1"></i> Filter
          </button>
          <a href="/appointments" class="btn btn-sm btn-light border rounded-pill px-2.5 py-1.5 text-muted hover-dark" title="Reset Filters">
            <i class="fa-solid fa-rotate-left"></i>
          </a>
        </div>
      </form>
    </div>

    <!-- ─── 4. RESULTS PRESENTATION ──────────────────────────────────────── -->
    <?php if (empty($appointments)): ?>
      <div class="bento-card text-center py-5">
        <i class="fa-regular fa-calendar-xmark fs-1 text-muted opacity-50 mb-3 d-block"></i>
        <h5 class="fw-bold text-dark mb-1">No appointments found</h5>
        <p class="text-muted small mb-3">No biometric, consular, or medical appointments match your active filter criteria.</p>
        <div class="d-flex justify-content-center gap-2">
          <a href="/appointments" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Clear Filters</a>
          <button type="button" class="bento-btn-primary" style="padding: 0.45rem 1.15rem; font-size: 0.82rem;" data-bs-toggle="modal" data-bs-target="#newAptModal">
            <i class="fa-solid fa-calendar-plus me-1"></i> Schedule First Appointment
          </button>
        </div>
      </div>
    <?php else: ?>

      <!-- ─── 4A. TABLE VIEW (BENTO ADAPTIVE DATA TABLE) ─────────────────── -->
      <div id="aptViewTable" class="apt-view-container <?= $currentView === 'table' ? '' : 'd-none' ?>">
        <div class="bento-card p-0 overflow-hidden mb-4">
          <div class="apt-table-wrap">
            <table class="apt-table">
              <thead>
                <tr>
                  <th>Appointment Type</th>
                  <th>Center / Location</th>
                  <th>Applicant &amp; App #</th>
                  <th>Date &amp; Time</th>
                  <th>Reference #</th>
                  <th>Assigned Officer</th>
                  <th>Status</th>
                  <th class="text-end">Update</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($appointments as $apt): ?>
                  <?php
                    $st = $apt['status'] ?? '';
                    $chipClass = 'neutral';
                    if ($st === 'Completed') $chipClass = 'success';
                    elseif ($st === 'Confirmed') $chipClass = 'info';
                    elseif ($st === 'Scheduled') $chipClass = 'primary';
                    elseif ($st === 'Cancelled' || $st === 'Missed') $chipClass = 'danger';
                    elseif ($st === 'Rescheduled') $chipClass = 'warning';
                  ?>
                  <tr>
                    <!-- Type -->
                    <td data-label="Type">
                      <div class="d-flex align-items-center gap-2">
                        <div class="apt-kpi-icon flex-shrink-0" style="background: var(--bento-primary-soft); color: var(--bento-primary);">
                          <i class="fa-solid fa-calendar-day"></i>
                        </div>
                        <span class="fw-bold text-dark"><?= e($apt['appointment_type']) ?></span>
                      </div>
                    </td>

                    <!-- Center / Location -->
                    <td data-label="Venue">
                      <div>
                        <div class="fw-semibold text-primary"><?= e($apt['center_name']) ?></div>
                        <div class="text-muted small" style="font-size: 0.74rem;">
                          <i class="fa-solid fa-location-dot me-1 text-danger"></i><?= e($apt['location_address'] ?: 'Consular / Center Location') ?>
                        </div>
                      </div>
                    </td>

                    <!-- Applicant & App # -->
                    <td data-label="Applicant">
                      <div>
                        <a href="/applications/show?id=<?= $apt['app_id'] ?>" class="fw-bold text-dark text-decoration-none d-block">
                          <?= e($apt['application_number']) ?>
                        </a>
                        <div class="text-muted small"><?= e($apt['customer_name']) ?></div>
                      </div>
                    </td>

                    <!-- Date & Time -->
                    <td data-label="Schedule">
                      <div>
                        <div class="fw-bold text-dark"><?= format_date($apt['appointment_date']) ?></div>
                        <div class="text-muted small" style="font-size: 0.74rem;">
                          <i class="fa-regular fa-clock me-1"></i><?= date('h:i A', strtotime($apt['appointment_time'])) ?>
                        </div>
                      </div>
                    </td>

                    <!-- Reference -->
                    <td data-label="Ref #">
                      <span class="badge bg-light text-dark border font-monospace"><?= e($apt['reference_number'] ?: '—') ?></span>
                    </td>

                    <!-- Officer -->
                    <td data-label="Officer">
                      <span class="small fw-semibold text-dark"><?= e($apt['staff_name'] ?? 'Unassigned') ?></span>
                    </td>

                    <!-- Status -->
                    <td data-label="Status">
                      <span class="badge rounded-pill px-2.5 py-1 fw-bold text-uppercase" style="font-size: 0.7rem; <?php
                        if ($st === 'Completed') echo 'background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0;';
                        elseif ($st === 'Confirmed') echo 'background: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE;';
                        elseif ($st === 'Scheduled') echo 'background: #FFF1F3; color: #E11D48; border: 1px solid #FCE7F3;';
                        elseif ($st === 'Rescheduled') echo 'background: #FFFBEB; color: #D97706; border: 1px solid #FDE68A;';
                        else echo 'background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA;';
                      ?>">
                        <?= e($apt['status']) ?>
                      </span>
                    </td>

                    <!-- Actions -->
                    <td class="text-end td-actions" data-label="Actions">
                      <div class="d-flex align-items-center justify-content-end gap-1">
                        <div class="btn-group">
                          <button type="button" class="btn btn-outline-primary btn-sm rounded-start-pill py-1 px-2.5 fw-semibold" 
                            onclick="openAptStatusModal(<?= (int)$apt['id'] ?>, '<?= e($apt['status']) ?>', '<?= e($apt['application_number']) ?>', '<?= e($apt['customer_name']) ?>')">
                            Status <i class="fa-solid fa-pen-to-square ms-1"></i>
                          </button>
                          <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle dropdown-toggle-split rounded-end-pill py-1 px-2" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="visually-hidden">Toggle</span>
                          </button>
                          <ul class="dropdown-menu dropdown-menu-end shadow border-0 small rounded-3">
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
      <div id="aptViewGrid" class="apt-view-container <?= $currentView === 'grid' ? '' : 'd-none' ?> mb-4">
        <div class="apt-grid">
          <?php foreach ($appointments as $apt): ?>
            <?php
              $st = $apt['status'] ?? '';
            ?>
            <div class="apt-card">
              <div>
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="badge bg-light text-dark border small fw-semibold">
                    <i class="fa-solid fa-tag me-1 text-primary"></i><?= e($apt['appointment_type']) ?>
                  </span>
                  <span class="badge rounded-pill px-2 py-0.5 fw-bold" style="<?php
                    if ($st === 'Completed') echo 'background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0;';
                    elseif ($st === 'Confirmed') echo 'background: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE;';
                    elseif ($st === 'Scheduled') echo 'background: #FFF1F3; color: #E11D48; border: 1px solid #FCE7F3;';
                    elseif ($st === 'Rescheduled') echo 'background: #FFFBEB; color: #D97706; border: 1px solid #FDE68A;';
                    else echo 'background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA;';
                  ?>">
                    <?= e($apt['status']) ?>
                  </span>
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
                    <span class="font-monospace small fw-semibold text-secondary"><?= e($apt['reference_number'] ?: '—') ?></span>
                  </div>
                </div>
              </div>

              <div class="pt-2 border-top d-flex align-items-center justify-content-between">
                <div>
                  <div class="small fw-bold text-dark"><i class="fa-regular fa-calendar me-1 text-primary"></i><?= format_date($apt['appointment_date']) ?></div>
                  <div class="text-muted" style="font-size: 0.72rem;"><i class="fa-regular fa-clock me-1"></i><?= date('h:i A', strtotime($apt['appointment_time'])) ?> &bull; <?= e($apt['staff_name'] ?? 'Unassigned') ?></div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-semibold shadow-sm"
                  onclick="openAptStatusModal(<?= (int)$apt['id'] ?>, '<?= e($apt['status']) ?>', '<?= e($apt['application_number']) ?>', '<?= e($apt['customer_name']) ?>')">
                  Status <i class="fa-solid fa-pen-to-square ms-1"></i>
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- ─── 4C. TIMELINE VIEW ─────────────────────────────────────────── -->
      <div id="aptViewTimeline" class="apt-view-container <?= $currentView === 'timeline' ? '' : 'd-none' ?> mb-4">
        <div class="apt-timeline">
          <?php foreach ($appointments as $apt): ?>
            <?php
              $st = $apt['status'] ?? '';
            ?>
            <div class="apt-timeline-item">
              <div class="apt-timeline-date">
                <div class="fw-bold text-dark" style="font-size: 0.85rem;"><?= format_date($apt['appointment_date']) ?></div>
                <div class="text-muted small"><?= date('h:i A', strtotime($apt['appointment_time'])) ?></div>
              </div>

              <div class="apt-timeline-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border small fw-semibold"><?= e($apt['appointment_type']) ?></span>
                    <span class="badge rounded-pill px-2.5 py-0.5 fw-bold" style="<?php
                      if ($st === 'Completed') echo 'background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0;';
                      elseif ($st === 'Confirmed') echo 'background: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE;';
                      elseif ($st === 'Scheduled') echo 'background: #FFF1F3; color: #E11D48; border: 1px solid #FCE7F3;';
                      elseif ($st === 'Rescheduled') echo 'background: #FFFBEB; color: #D97706; border: 1px solid #FDE68A;';
                      else echo 'background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA;';
                    ?>">
                      <?= e($apt['status']) ?>
                    </span>
                  </div>
                  <div>
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 fw-semibold"
                      onclick="openAptStatusModal(<?= (int)$apt['id'] ?>, '<?= e($apt['status']) ?>', '<?= e($apt['application_number']) ?>', '<?= e($apt['customer_name']) ?>')">
                      Update <i class="fa-solid fa-pen-to-square ms-1"></i>
                    </button>
                  </div>
                </div>

                <div class="row g-2 align-items-center">
                  <div class="col-md-6">
                    <div class="fw-bold text-dark fs-6"><?= e($apt['center_name']) ?></div>
                    <div class="text-muted small"><i class="fa-solid fa-location-dot me-1 text-danger"></i><?= e($apt['location_address'] ?: 'Consular / Center Venue') ?></div>
                  </div>
                  <div class="col-md-6">
                    <div class="small text-muted">Applicant: <strong class="text-dark"><?= e($apt['customer_name']) ?></strong></div>
                    <div class="small">File: <a href="/applications/show?id=<?= $apt['app_id'] ?>" class="fw-semibold text-decoration-none"><?= e($apt['application_number']) ?></a> (Ref: <span class="font-monospace text-muted"><?= e($apt['reference_number'] ?: '—') ?></span>)</div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    <?php endif; ?>

  </div>
</div>

<!-- ======================================================================= -->
<!-- MODAL: ADD APPOINTMENT TYPE                                             -->
<!-- ======================================================================= -->
<div class="modal fade" id="newAptTypeModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
      <form action="/appointments/types/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-header bg-dark text-white p-3.5">
          <h6 class="modal-title fw-bold fs-6"><i class="fa-solid fa-plus-circle me-2 text-primary"></i> Add New Appointment Type</h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Appointment Type Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control rounded-3" placeholder="e.g. Passport Drop-off, Embassy VIP Lounge, DNA Verification" required>
            <div class="form-text small">Descriptive label shown across appointment bookings and filters.</div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Description (Optional)</label>
            <textarea name="description" class="form-control rounded-3" rows="2" placeholder="Brief note on requirements or instructions for this appointment category..."></textarea>
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
        <div class="modal-footer bg-light p-3 border-top">
          <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.15rem; font-size: 0.82rem;">
            <i class="fa-solid fa-check me-1"></i> Save Appointment Type
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ======================================================================= -->
<!-- MODAL: UPDATE APPOINTMENT STATUS                                        -->
<!-- ======================================================================= -->
<div class="modal fade" id="updateAptStatusModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
      <div class="modal-header bg-dark text-white p-3.5">
        <h6 class="modal-title fw-bold fs-6"><i class="fa-solid fa-calendar-check me-2 text-primary"></i> Update Appointment Status</h6>
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
            <select name="status" id="modalAptStatusSelect" class="form-select rounded-3" required>
              <option value="Confirmed">Confirmed (Biometrics / Consular Confirmed)</option>
              <option value="Completed">Completed (Applicant Attended Successfully)</option>
              <option value="Scheduled">Scheduled (Upcoming / Pending)</option>
              <option value="Rescheduled">Rescheduled (Shifted to New Date)</option>
              <option value="Missed">Missed (Applicant Did Not Attend)</option>
              <option value="Cancelled">Cancelled (Appointment Withdrawn)</option>
            </select>
          </div>
        </div>
        <div class="modal-footer bg-light p-3 border-top">
          <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.15rem; font-size: 0.82rem;">Save Status</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ======================================================================= -->
<!-- MODAL: SCHEDULE APPOINTMENT                                             -->
<!-- ======================================================================= -->
<div class="modal fade" id="newAptModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
      <div class="modal-header bg-dark text-white p-3.5">
        <h6 class="modal-title fw-bold fs-6"><i class="fa-solid fa-calendar-plus me-2 text-primary"></i> Schedule Appointment</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/appointments/store" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Visa Application <span class="text-danger">*</span></label>
              <select name="application_id" class="form-select rounded-3" required>
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
              <select name="appointment_type" id="schedAptTypeSelect" class="form-select rounded-3" required onchange="handleTypeSelectChange(this)">
                <?php foreach (($appointmentTypes ?? []) as $at): ?>
                  <option value="<?= e($at['name']) ?>"><?= e($at['name']) ?></option>
                <?php endforeach; ?>
                <option value="custom">+ Other / Custom Type...</option>
              </select>
            </div>
          </div>

          <div id="customTypeContainer" class="mb-3 p-3 bg-light rounded-3 border d-none">
            <label class="form-label small fw-semibold text-primary"><i class="fa-solid fa-pen-nib me-1"></i> Specify Custom Appointment Type</label>
            <input type="text" name="custom_appointment_type" id="customTypeInput" class="form-control rounded-3" placeholder="Type new appointment category name...">
            <div class="form-text small">This new category will automatically be saved to your system appointment types.</div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Center / Venue Name <span class="text-danger">*</span></label>
              <input type="text" name="center_name" class="form-control rounded-3" placeholder="e.g. VFS Global Wafi Mall, Smart Salem Al Quoz" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Location Address</label>
              <input type="text" name="location_address" class="form-control rounded-3" placeholder="e.g. Level 3, Wafi City Mall, Oud Metha, Dubai">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Date <span class="text-danger">*</span></label>
              <input type="date" name="appointment_date" class="form-control rounded-3" required value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Time <span class="text-danger">*</span></label>
              <input type="time" name="appointment_time" class="form-control rounded-3" required value="09:30">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Booking Ref # (Slip / Barcode)</label>
              <input type="text" name="reference_number" class="form-control rounded-3" placeholder="e.g. VFS-DXB-99881">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Assigned Accompanying Officer</label>
              <select name="assigned_staff_id" class="form-select rounded-3">
                <option value="">-- Auto-assign active staff --</option>
                <?php foreach (($staffMembers ?? []) as $sm): ?>
                  <option value="<?= $sm['id'] ?>"><?= e($sm['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Upload Appointment Letter / Slip (PDF/JPG)</label>
              <input type="file" name="document_file" class="form-control rounded-3" accept=".pdf,.jpg,.jpeg,.png">
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label small fw-semibold">Special Instructions / Applicant Notes</label>
            <textarea name="notes" class="form-control rounded-3" rows="2" placeholder="e.g. Original passport, 2 biometric photos, and bank statements required on day of visit."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light p-3 border-top">
          <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="bento-btn-primary" style="padding: 0.45rem 1.25rem; font-size: 0.82rem;">
            <i class="fa-solid fa-check me-1"></i> Confirm &amp; Notify Applicant
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function switchAptView(view) {
  document.querySelectorAll('.apt-view-container').forEach(el => el.classList.add('d-none'));
  document.querySelectorAll('.apt-segmented button').forEach(btn => btn.classList.remove('active'));

  const target = document.getElementById('aptView' + view.charAt(0).toUpperCase() + view.slice(1));
  if (target) target.classList.remove('d-none');

  const activeBtn = document.getElementById('btnView' + view.charAt(0).toUpperCase() + view.slice(1));
  if (activeBtn) activeBtn.classList.add('active');

  const viewInput = document.getElementById('activeViewParam');
  if (viewInput) viewInput.value = view;

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
