<?php
$pageTitle = 'Appointments Scheduler — VISA TRACK';
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
      <h3 class="fw-bold brand-font mb-0"><i class="fa-solid fa-calendar-check text-primary me-2"></i> Visa & Embassy Appointments</h3>
      <p class="text-muted small mb-0">Manage and schedule Biometrics, VFS appointments, Embassy interviews, and Medical tests.</p>
    </div>
    <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#newAptModal">
      <i class="fa-solid fa-calendar-plus me-1"></i> Schedule Appointment
    </button>
  </div>

  <div class="card-custom">
    <div class="table-responsive">
      <table class="table table-custom align-middle mb-0">
        <thead>
          <tr>
            <th>Appointment Type</th>
            <th>Center / Location</th>
            <th>Applicant & App #</th>
            <th>Date & Time</th>
            <th>Reference #</th>
            <th>Assigned Officer</th>
            <th>Status</th>
            <th class="text-end">Update</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($appointments)): ?>
            <tr><td colspan="8" class="text-center py-5 text-muted">No appointments scheduled.</td></tr>
          <?php else: ?>
            <?php foreach ($appointments as $apt): ?>
              <tr>
                <td>
                  <span class="fw-bold text-dark d-block"><?= e($apt['appointment_type']) ?></span>
                </td>
                <td>
                  <div class="fw-semibold text-primary"><?= e($apt['center_name']) ?></div>
                  <div class="text-muted small"><?= e($apt['location_address'] ?? '') ?></div>
                </td>
                <td>
                  <a href="/applications/show?id=<?= $apt['app_id'] ?>" class="fw-bold"><?= e($apt['application_number']) ?></a>
                  <div class="text-muted small"><?= e($apt['customer_name']) ?></div>
                </td>
                <td>
                  <div class="fw-bold text-dark"><?= format_date($apt['appointment_date']) ?></div>
                  <div class="text-muted small"><?= date('h:i A', strtotime($apt['appointment_time'])) ?></div>
                </td>
                <td><span class="badge bg-light text-dark border"><?= e($apt['reference_number'] ?: '—') ?></span></td>
                <td><span class="small"><?= e($apt['staff_name'] ?? 'Unassigned') ?></span></td>
                <td>
                  <?php
                    $badge = 'bg-primary';
                    if ($apt['status'] === 'Completed') $badge = 'bg-success';
                    elseif ($apt['status'] === 'Cancelled' || $apt['status'] === 'Missed') $badge = 'bg-danger';
                  ?>
                  <span class="badge <?= $badge ?>"><?= e($apt['status']) ?></span>
                </td>
                <td class="text-end">
                  <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle py-1 px-2" data-bs-toggle="dropdown">Status</button>
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
                          <button type="submit" class="dropdown-item py-2 text-danger"><i class="fa-solid fa-circle-xmark text-danger me-2"></i> Mark Missed</button>
                        </form>
                      </li>
                    </ul>
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

<!-- MODAL: SCHEDULE APPOINTMENT -->
<div class="modal fade" id="newAptModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-calendar-plus me-2"></i> Schedule Appointment</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/appointments/store" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Visa Application <span class="text-danger">*</span></label>
            <select name="application_id" class="form-select" required>
              <option value="">-- Select Active Application --</option>
              <?php foreach ($activeApplications as $a): ?>
                <option value="<?= $a['id'] ?>"><?= e($a['application_number']) ?> &bull; <?= e($a['customer_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Appointment Type <span class="text-danger">*</span></label>
            <select name="appointment_type" class="form-select" required>
              <option value="Biometrics Capture">Biometrics Capture (VFS / TLS / ICP)</option>
              <option value="Medical Fitness Test">Medical Fitness Test (DHA / MOHAP / Diagnostic)</option>
              <option value="Embassy Consular Interview">Embassy Consular Interview</option>
              <option value="Document Signing / Handover">Document Signing / Handover</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Center / Venue Name <span class="text-danger">*</span></label>
            <input type="text" name="center_name" class="form-control" placeholder="e.g. Smart Salem Al Quoz, VFS Wafi Mall" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Location Address</label>
            <input type="text" name="location_address" class="form-control" placeholder="Street, Mall, City">
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Date <span class="text-danger">*</span></label>
              <input type="date" name="appointment_date" class="form-control" required value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Time <span class="text-danger">*</span></label>
              <input type="time" name="appointment_time" class="form-control" required value="09:30">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Appointment Booking Reference #</label>
            <input type="text" name="reference_number" class="form-control" placeholder="e.g. VFS-DXB-99881">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Upload Appointment Letter / Slip (PDF/JPG)</label>
            <input type="file" name="document_file" class="form-control">
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-3">Confirm Schedule</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
