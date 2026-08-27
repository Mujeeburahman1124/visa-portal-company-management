<?php
$pageTitle = 'Applicant Portal Dashboard — VISA TRACK';
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

  <!-- Welcome Hero Banner -->
  <div class="card card-enterprise mb-4 shadow-sm border-0" style="background: linear-gradient(135deg, #090e1a 0%, #1e3a8a 60%, #3b82f6 100%); color: white;">
    <div class="card-body p-4 p-md-5">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
          <span class="badge bg-white bg-opacity-20 text-white px-3 py-1.5 rounded-pill fw-semibold mb-2" style="font-size: 0.75rem;">
            <i class="fa-solid fa-shield-halved me-1"></i> SECURE APPLICANT SESSION
          </span>
          <h2 class="fw-bold brand-font mb-1 text-white">Welcome, <?= e($customer['full_name']) ?></h2>
          <p class="text-white-50 mb-0" style="max-width: 540px; font-size: 0.95rem;">
            Track your visa application progress, upload requested documents, and check consular appointments in real-time.
          </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
          <a href="/portal/documents" class="btn btn-light btn-sm px-3 py-2 fw-semibold rounded-pill shadow-sm">
            <i class="fa-solid fa-cloud-arrow-up me-1 text-primary"></i> Upload Documents
          </a>
          <a href="/portal/support" class="btn btn-outline-light btn-sm px-3 py-2 fw-semibold rounded-pill">
            <i class="fa-solid fa-headset me-1"></i> Contact Support
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- URGENT ACTION REQUIRED BANNER -->
  <?php if (!empty($pendingDocRequests)): ?>
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); border-left: 5px solid #f43f5e !important;">
      <div class="card-body p-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="fs-2 text-danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div>
              <div class="fw-bold text-dark fs-6">Action Required: Document Submission Requested</div>
              <div class="text-secondary small">
                Our visa operations officer has requested: 
                <?php foreach ($pendingDocRequests as $dr): ?>
                  <strong><?= e($dr['doc_name']) ?></strong> (Due: <?= format_date($dr['due_date']) ?>) &bull;
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <a href="/portal/documents" class="btn btn-danger btn-sm px-4 py-2 fw-semibold rounded-pill shadow-sm">
            <i class="fa-solid fa-upload me-1"></i> Upload Requested Files &rarr;
          </a>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- My Active Visa Applications -->
  <h4 class="fw-bold brand-font text-dark mb-3">
    <i class="fa-solid fa-folder-open text-primary me-2"></i> My Visa Applications
  </h4>

  <?php if (empty($applications)): ?>
    <div class="card card-enterprise text-center py-5 shadow-sm">
      <i class="fa-solid fa-passport text-muted fs-1 mb-2"></i>
      <h5 class="fw-bold text-dark">No active visa applications found.</h5>
      <p class="text-muted small">Please contact your visa consultant if your application was recently registered.</p>
    </div>
  <?php else: ?>
    <?php foreach ($applications as $app): ?>
      <?php
        $isApproved = ($app['status'] === 'Approved' || $app['status'] === 'Completed');
        $isActionRequired = ($app['status'] === 'Action Required');
      ?>
      <div class="card card-enterprise mb-4 shadow-sm border">
        <!-- Application Card Header -->
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div class="d-flex align-items-center gap-2.5">
            <span class="fs-3"><?= $app['flag_emoji'] ?></span>
            <div>
              <div class="d-flex align-items-center gap-2">
                <span class="fw-bold fs-6 text-primary"><?= e($app['application_number']) ?></span>
                <span class="badge bg-light text-dark border small"><?= e($app['service_name']) ?></span>
              </div>
              <div class="text-muted small" style="font-size: 0.74rem;">
                <?= e($app['country_name']) ?> &bull; <?= e($app['entry_type']) ?> &bull; Duration: <?= e($app['duration']) ?>
              </div>
            </div>
          </div>

          <div>
            <span class="badge <?= $isApproved ? 'bg-success' : ($isActionRequired ? 'bg-danger' : 'bg-primary') ?> fs-6 px-3 py-1.5 fw-bold">
              <i class="fa-solid <?= $isApproved ? 'fa-circle-check' : ($isActionRequired ? 'fa-triangle-exclamation' : 'fa-spinner fa-spin-pulse') ?> me-1"></i>
              <?= e($app['status']) ?>
            </span>
          </div>
        </div>

        <!-- Application Card Body -->
        <div class="card-body p-4">
          <!-- Summary Metrics -->
          <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
              <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Current Stage:</div>
              <div class="fw-bold text-dark fs-6"><?= e($app['current_stage']) ?></div>
            </div>
            <div class="col-6 col-md-3">
              <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Application Date:</div>
              <div class="fw-semibold text-dark"><?= format_date($app['application_date'] ?? $app['created_at']) ?></div>
            </div>
            <div class="col-6 col-md-3">
              <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Next Milestone:</div>
              <div class="fw-semibold text-primary"><?= e($app['next_action'] ?: 'Document Verification in progress') ?></div>
            </div>
            <div class="col-6 col-md-3">
              <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Target Completion:</div>
              <div class="fw-bold text-dark"><i class="fa-regular fa-calendar-check text-success me-1"></i><?= format_date($app['expected_completion_date']) ?></div>
            </div>
          </div>

          <!-- Customer Friendly Visa Journey Visual Progression -->
          <div class="p-3 bg-light rounded border mb-3">
            <div class="fw-bold small text-uppercase mb-3 text-dark d-flex align-items-center gap-1.5">
              <i class="fa-solid fa-route text-primary"></i>
              <span>Visa Tracking Journey</span>
            </div>

            <div class="row g-2 text-center">
              <?php
                $stagesList = [
                  ['name' => 'Registration', 'icon' => 'fa-id-card'],
                  ['name' => 'Documents', 'icon' => 'fa-file-circle-check'],
                  ['name' => 'Form Prep', 'icon' => 'fa-pen-to-square'],
                  ['name' => 'Biometrics', 'icon' => 'fa-fingerprint'],
                  ['name' => 'Embassy Review', 'icon' => 'fa-building-columns'],
                  ['name' => 'Visa Issued', 'icon' => 'fa-passport'],
                ];
                
                $curr = strtolower($app['current_stage']);
                $activeIndex = 1;
                if (str_contains($curr, 'doc')) $activeIndex = 2;
                elseif (str_contains($curr, 'prep') || str_contains($curr, 'form')) $activeIndex = 3;
                elseif (str_contains($curr, 'bio') || str_contains($curr, 'appoint')) $activeIndex = 4;
                elseif (str_contains($curr, 'embassy') || str_contains($curr, 'submi') || str_contains($curr, 'process')) $activeIndex = 5;
                elseif (str_contains($curr, 'approv') || str_contains($curr, 'issued') || str_contains($curr, 'complet')) $activeIndex = 6;
              ?>
              <?php foreach ($stagesList as $sIdx => $stg): ?>
                <?php
                  $stepNum = $sIdx + 1;
                  $isDone = ($stepNum < $activeIndex) || $isApproved;
                  $isCurrent = ($stepNum === $activeIndex) && !$isApproved;
                ?>
                <div class="col-4 col-md-2 mb-2">
                  <div class="p-2 rounded border <?= $isDone ? 'bg-success-subtle border-success text-success' : ($isCurrent ? 'bg-primary-subtle border-primary text-primary shadow-sm' : 'bg-white text-muted') ?>">
                    <div class="fs-5 mb-1"><i class="fa-solid <?= $stg['icon'] ?>"></i></div>
                    <div class="fw-bold" style="font-size: 0.72rem;"><?= $stg['name'] ?></div>
                    <div class="small" style="font-size: 0.65rem;">
                      <?= $isDone ? 'Completed' : ($isCurrent ? 'In Progress' : 'Upcoming') ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Decision Specific Banners (Section 10) -->
          <?php if ($isApproved && !empty($app['visa_number'])): ?>
            <div class="p-3 mb-3 rounded-3 border border-success bg-success bg-opacity-10">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-success fw-bold px-2.5 py-1 fs-6"><i class="fa-solid fa-circle-check me-1"></i> VISA APPROVED</span>
                    <span class="fw-bold text-dark">Visa #: <?= e($app['visa_number']) ?></span>
                  </div>
                  <div class="text-secondary small">
                    Issue Date: <strong><?= format_date($app['visa_issue_date']) ?></strong> &bull; 
                    Expiry: <strong><?= format_date($app['visa_expiry_date']) ?></strong> 
                    <?php if (!empty($app['max_stay'])): ?> &bull; Stay: <strong><?= e($app['max_stay']) ?></strong><?php endif; ?>
                    <?php if (!empty($app['validity'])): ?> &bull; Validity: <strong><?= e($app['validity']) ?></strong><?php endif; ?>
                  </div>
                </div>
                <div class="d-flex gap-2">
                  <?php if (!empty($app['visa_file'])): ?>
                    <a href="<?= e($app['visa_file']) ?>" target="_blank" class="btn btn-success btn-sm px-3 fw-semibold shadow-sm">
                      <i class="fa-solid fa-eye me-1"></i> View Visa
                    </a>
                    <a href="<?= e($app['visa_file']) ?>" download class="btn btn-outline-success btn-sm px-3 fw-semibold bg-white">
                      <i class="fa-solid fa-download me-1"></i> Download Visa
                    </a>
                    <button type="button" onclick="window.open('<?= e($app['visa_file']) ?>', '_blank').print();" class="btn btn-outline-dark btn-sm px-3 fw-semibold bg-white">
                      <i class="fa-solid fa-print me-1"></i> Print Visa
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php elseif ($app['status'] === 'Rejected'): ?>
            <div class="p-3 mb-3 rounded-3 border border-danger bg-danger bg-opacity-10">
              <div class="d-flex align-items-start gap-3">
                <i class="fa-solid fa-circle-xmark text-danger fs-3 mt-1"></i>
                <div class="flex-grow-1">
                  <div class="fw-bold text-danger fs-6 mb-1">Visa Application Notice: Refused / Rejected</div>
                  <div class="text-dark small mb-2"><?= e($app['rejection_reason_customer'] ?: 'Application refused by consular authority.') ?></div>
                  <?php if (!empty($app['visa_file'])): ?>
                    <a href="<?= e($app['visa_file']) ?>" target="_blank" class="btn btn-outline-danger btn-sm py-1 px-3 fw-semibold bg-white">
                      <i class="fa-solid fa-file-pdf me-1"></i> Download Official Rejection Notice
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php elseif ($app['status'] === 'Returned' || $app['status'] === 'Modification Required'): ?>
            <div class="p-3 mb-3 rounded-3 border border-warning bg-warning bg-opacity-10">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                  <div class="fw-bold text-dark fs-6 mb-1"><i class="fa-solid fa-rotate-left text-warning me-1"></i> Action Required: Application Returned for Modification</div>
                  <div class="text-dark small">Reason: <?= e($app['return_reason'] ?: 'Please upload updated document copies as required.') ?></div>
                  <?php if (!empty($app['return_deadline'])): ?>
                    <div class="text-danger small fw-semibold mt-1">Deadline for Resubmission: <?= format_date($app['return_deadline']) ?></div>
                  <?php endif; ?>
                </div>
                <a href="/portal/documents?app_id=<?= $app['id'] ?>" class="btn btn-warning btn-sm px-3 fw-semibold shadow-sm text-dark">
                  <i class="fa-solid fa-upload me-1"></i> Upload Requested Files &rarr;
                </a>
              </div>
            </div>
          <?php endif; ?>

          <!-- Quick Action Buttons -->
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2">
            <div class="small text-muted">
              <i class="fa-solid fa-lock text-success me-1"></i> Data encrypted &amp; verified by MS TRAVEL HUB Consular Desk.
            </div>
            <div class="d-flex gap-2">
              <a href="/portal/documents?app_id=<?= $app['id'] ?>" class="btn btn-outline-primary btn-sm px-3 fw-semibold">
                <i class="fa-solid fa-folder-open me-1"></i> View Checklist &amp; Uploads
              </a>
              <a href="/portal/invoices" class="btn btn-outline-secondary btn-sm px-3 fw-semibold">
                <i class="fa-solid fa-receipt me-1"></i> Payment Status
              </a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- Upcoming Appointments Section -->
  <?php if (!empty($appointments)): ?>
    <div class="card card-enterprise shadow-sm mb-4">
      <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-calendar-check text-primary me-2"></i> Upcoming Consular &amp; Biometrics Appointments</h6>
      </div>
      <div class="card-body p-3">
        <div class="row g-3">
          <?php foreach ($appointments as $apt): ?>
            <div class="col-md-6">
              <div class="p-3 bg-light rounded border h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <span class="badge bg-primary fw-bold"><?= e($apt['appointment_type']) ?></span>
                  <span class="badge bg-success"><?= e($apt['status']) ?></span>
                </div>
                <h6 class="fw-bold text-dark mb-1"><?= e($apt['center_name']) ?></h6>
                <div class="text-muted small mb-2"><i class="fa-solid fa-location-dot me-1 text-danger"></i><?= e($apt['location_address']) ?></div>
                <div class="small fw-semibold text-dark">
                  <i class="fa-regular fa-clock text-primary me-1"></i> <?= format_date($apt['appointment_date']) ?> at <?= date('h:i A', strtotime($apt['appointment_time'])) ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

