<?php
$pageTitle = 'Consular & Biometrics Appointments — VISA TRACK';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220%22%20%22100%22><text y=%22.9em%22 font-size=%2290%22>✈️</text></svg>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css?v=7.0.0">
  <link rel="stylesheet" href="/assets/css/timeline.css?v=4.0.0">
</head>
<body class="app-body">

<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="container py-4">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <h3 class="fw-bold brand-font text-dark mb-1">Consular &amp; Biometrics Appointments</h3>
      <p class="text-muted small mb-0">Scheduled appointments for embassy interviews, TLS/VFS biometric capture, and medical tests.</p>
    </div>
  </div>

  <?php if (empty($appointments)): ?>
    <div class="card card-enterprise text-center py-5 shadow-sm">
      <i class="fa-solid fa-calendar-xmark text-muted fs-1 mb-2"></i>
      <h5 class="fw-bold text-dark">No appointments currently scheduled.</h5>
      <p class="text-muted small">Your assigned visa specialist will notify you as soon as an appointment slot is secured.</p>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($appointments as $apt): ?>
        <?php
          $isPast = strtotime($apt['appointment_date']) < strtotime(date('Y-m-d'));
        ?>
        <div class="col-md-6">
          <div class="card card-enterprise h-100 shadow-sm border">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2">
                <span class="fs-4"><?= $apt['flag_emoji'] ?? '🌐' ?></span>
                <div>
                  <div class="fw-bold text-dark fs-6"><?= e($apt['appointment_type']) ?></div>
                  <div class="text-muted small font-monospace"><?= e($apt['application_number']) ?></div>
                </div>
              </div>
              <span class="badge <?= $isPast ? 'bg-secondary' : ($apt['status'] === 'Confirmed' ? 'bg-success' : 'bg-primary') ?> px-2.5 py-1">
                <?= $isPast ? 'Completed' : e($apt['status']) ?>
              </span>
            </div>

            <div class="card-body p-4">
              <div class="p-3 bg-light rounded border mb-3">
                <div class="row g-2">
                  <div class="col-6">
                    <div class="text-muted small fw-semibold">DATE:</div>
                    <div class="fw-bold text-primary fs-6"><i class="fa-regular fa-calendar me-1"></i><?= format_date($apt['appointment_date']) ?></div>
                  </div>
                  <div class="col-6">
                    <div class="text-muted small fw-semibold">TIME:</div>
                    <div class="fw-bold text-dark fs-6"><i class="fa-regular fa-clock me-1"></i><?= date('h:i A', strtotime($apt['appointment_time'])) ?></div>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <div class="fw-semibold text-dark mb-1"><i class="fa-solid fa-building text-primary me-1"></i> Center / Venue:</div>
                <div class="text-dark small fw-bold"><?= e($apt['center_name']) ?></div>
                <div class="text-muted small"><i class="fa-solid fa-location-dot text-danger me-1"></i><?= e($apt['location_address'] ?: 'Official consular premises') ?></div>
              </div>

              <?php if (!empty($apt['reference_number'])): ?>
                <div class="mb-3">
                  <span class="text-muted small">Appointment Booking Reference:</span>
                  <span class="badge bg-light text-dark border font-monospace"><?= e($apt['reference_number']) ?></span>
                </div>
              <?php endif; ?>

              <?php if (!empty($apt['notes'])): ?>
                <div class="p-2.5 bg-warning bg-opacity-10 border border-warning rounded small text-dark">
                  <strong><i class="fa-solid fa-circle-info text-warning me-1"></i> Preparation Instructions:</strong>
                  <?= e($apt['notes']) ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

