<?php
$pageTitle = 'Applicant Portal Dashboard — MS TRAVEL HUB';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  
  <!-- Favicons -->
  <link rel="icon" type="image/png" href="/assets/images/favicon.png?v=2">
  <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico?v=2">
  <link rel="apple-touch-icon" href="/assets/images/logo.png">

  <!-- Typography -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Frameworks & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/main.css?v=7.0.0">

  <!-- Bento Styling -->
  <link rel="stylesheet" href="/assets/css/pages/portal-bento.css?v=<?= time() ?>">
</head>
<body class="portal-body">

<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="container-fluid container-xl py-4">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm rounded-3 d-flex align-items-center gap-2" role="alert">
      <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?> fs-6"></i>
      <div class="flex-grow-1 fw-semibold small"><?= e($flash['message']) ?></div>
      <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- ─── HERO BANNER (BENTO STYLE) ────────────────────────────────────────── -->
  <div class="portal-hero-card">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <span class="portal-hero-badge">
          <i class="fa-solid fa-shield-halved"></i> Secure Applicant Session
        </span>
        <h1 class="portal-hero-title">Welcome, <?= e($customer['full_name']) ?></h1>
        <p class="portal-hero-desc">
          Track your visa application progress, upload requested documents, and verify consular appointments in real-time.
        </p>
      </div>

      <div class="d-flex flex-wrap gap-2">
        <a href="/portal/documents" class="portal-btn-primary">
          <i class="fa-solid fa-cloud-arrow-up"></i>
          <span>Upload Documents</span>
        </a>
        <a href="/portal/support" class="portal-btn-outline">
          <i class="fa-solid fa-headset"></i>
          <span>Contact Support</span>
        </a>
      </div>
    </div>
  </div>

  <!-- ─── URGENT ACTION REQUIRED BANNER ────────────────────────────────────── -->
  <?php if (!empty($pendingDocRequests)): ?>
    <div class="portal-banner-action mb-4">
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
        <a href="/portal/documents" class="portal-btn-primary py-2 px-3.5" style="font-size:0.85rem;">
          <i class="fa-solid fa-upload"></i>
          <span>Upload Requested Files</span>
        </a>
      </div>
    </div>
  <?php endif; ?>

  <!-- ─── MY VISA APPLICATIONS SECTION ─────────────────────────────────────── -->
  <div class="d-flex align-items-center justify-content-between gap-2 mb-3" id="applications">
    <h4 class="fw-bold mb-0 text-dark" style="font-family:'Outfit', sans-serif;">
      <i class="fa-solid fa-folder-open me-2" style="color:var(--portal-primary);"></i> My Visa Applications
    </h4>
  </div>

  <?php if (empty($applications)): ?>
    <div class="portal-bento-card text-center py-5">
      <div class="mb-3">
        <i class="fa-solid fa-passport fs-1 opacity-25" style="color:var(--portal-primary);"></i>
      </div>
      <h5 class="fw-bold text-dark">No Active Visa Applications Found</h5>
      <p class="text-muted small max-w-sm mx-auto">
        Please contact your visa consultant if your application was recently registered or under file creation.
      </p>
    </div>
  <?php else: ?>
    <?php foreach ($applications as $app): ?>
      <?php
        $isApproved = ($app['status'] === 'Approved' || $app['status'] === 'Completed');
        $isActionRequired = ($app['status'] === 'Action Required');
      ?>
      <div class="portal-bento-card">
        <!-- Application Card Header -->
        <div class="portal-card-header">
          <div class="d-flex align-items-center gap-3">
            <div class="fs-2 lh-1 p-2 rounded-3 bg-light border d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
              <?= $app['flag_emoji'] ?>
            </div>
            <div>
              <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <span class="portal-app-num"><?= e($app['application_number']) ?></span>
                <span class="portal-service-badge"><?= e($app['service_name']) ?></span>
              </div>
              <div class="text-muted small d-flex flex-wrap align-items-center gap-2" style="font-size: 0.78rem;">
                <span><i class="fa-solid fa-earth-americas me-1 text-secondary"></i><?= e($app['country_name']) ?></span>
                <span>&bull;</span>
                <span><i class="fa-solid fa-passport me-1 text-secondary"></i><?= e($app['entry_type']) ?></span>
                <span>&bull;</span>
                <span><i class="fa-regular fa-clock me-1 text-secondary"></i>Duration: <?= e($app['duration']) ?></span>
              </div>
            </div>
          </div>

          <div>
            <?php
              $badgeClass = 'bg-primary';
              $badgeIcon = 'fa-spinner fa-spin-pulse';
              if ($isApproved) {
                $badgeClass = 'bg-success';
                $badgeIcon = 'fa-circle-check';
              } elseif ($isActionRequired) {
                $badgeClass = 'bg-danger';
                $badgeIcon = 'fa-triangle-exclamation';
              }
            ?>
            <span class="badge <?= $badgeClass ?> fs-6 px-3 py-2 fw-bold rounded-pill shadow-sm">
              <i class="fa-solid <?= $badgeIcon ?> me-1.5"></i>
              <?= e($app['status']) ?>
            </span>
          </div>
        </div>

        <!-- Application Card Body -->
        <div class="p-3 p-md-4">
          <!-- Summary Metrics Grid -->
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
              <div class="fw-semibold" style="color:var(--portal-primary);"><?= e($app['next_action'] ?: 'Document Verification in progress') ?></div>
            </div>
            <div class="col-6 col-md-3">
              <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Target Completion:</div>
              <div class="fw-bold text-dark"><i class="fa-regular fa-calendar-check text-success me-1"></i><?= format_date($app['expected_completion_date']) ?></div>
            </div>
          </div>

          <!-- Customer Friendly Visa Journey Visual Progression -->
          <div class="portal-stepper-wrap">
            <div class="portal-stepper-title">
              <i class="fa-solid fa-route"></i>
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
                  $cardStateClass = $isDone ? 'step-done' : ($isCurrent ? 'step-active' : 'step-upcoming');
                ?>
                <div class="col-6 col-sm-4 col-md-2 mb-2">
                  <div class="portal-step-card <?= $cardStateClass ?>">
                    <div class="step-icon"><i class="fa-solid <?= $stg['icon'] ?>"></i></div>
                    <div class="step-label text-truncate w-100" title="<?= $stg['name'] ?>"><?= $stg['name'] ?></div>
                    <div class="step-state-tag">
                      <?= $isDone ? 'Completed' : ($isCurrent ? 'In Progress' : 'Upcoming') ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Decision Specific Banners -->
          <?php if ($isApproved && !empty($app['visa_number'])): ?>
            <div class="portal-banner-approved">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-success fw-bold px-2.5 py-1 fs-6"><i class="fa-solid fa-circle-check me-1"></i> VISA APPROVED</span>
                    <span class="fw-bold text-dark font-monospace">Visa #: <?= e($app['visa_number']) ?></span>
                  </div>
                  <div class="text-secondary small">
                    Issue Date: <strong><?= format_date($app['visa_issue_date']) ?></strong> &bull; 
                    Expiry: <strong><?= format_date($app['visa_expiry_date']) ?></strong> 
                    <?php if (!empty($app['max_stay'])): ?> &bull; Stay: <strong><?= e($app['max_stay']) ?></strong><?php endif; ?>
                    <?php if (!empty($app['validity'])): ?> &bull; Validity: <strong><?= e($app['validity']) ?></strong><?php endif; ?>
                  </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <?php if (!empty($app['visa_file'])): ?>
                    <a href="<?= e($app['visa_file']) ?>" target="_blank" class="btn btn-success btn-sm px-3 fw-semibold rounded-pill shadow-sm">
                      <i class="fa-solid fa-eye me-1"></i> View Visa
                    </a>
                    <a href="<?= e($app['visa_file']) ?>" download class="btn btn-outline-success btn-sm px-3 fw-semibold bg-white rounded-pill">
                      <i class="fa-solid fa-download me-1"></i> Download
                    </a>
                    <button type="button" onclick="window.open('<?= e($app['visa_file']) ?>', '_blank').print();" class="btn btn-outline-dark btn-sm px-3 fw-semibold bg-white rounded-pill">
                      <i class="fa-solid fa-print me-1"></i> Print
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php elseif ($app['status'] === 'Rejected'): ?>
            <div class="portal-banner-rejected">
              <div class="d-flex align-items-start gap-3">
                <i class="fa-solid fa-circle-xmark text-danger fs-3 mt-1"></i>
                <div class="flex-grow-1">
                  <div class="fw-bold text-danger fs-6 mb-1">Visa Application Notice: Refused / Rejected</div>
                  <div class="text-dark small mb-2"><?= e($app['rejection_reason_customer'] ?: 'Application refused by consular authority.') ?></div>
                  <?php if (!empty($app['visa_file'])): ?>
                    <a href="<?= e($app['visa_file']) ?>" target="_blank" class="btn btn-outline-danger btn-sm py-1 px-3 fw-semibold bg-white rounded-pill">
                      <i class="fa-solid fa-file-pdf me-1"></i> Download Rejection Notice
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php elseif ($app['status'] === 'Returned' || $app['status'] === 'Modification Required'): ?>
            <div class="portal-banner-action">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                  <div class="fw-bold text-dark fs-6 mb-1"><i class="fa-solid fa-rotate-left text-warning me-1"></i> Action Required: Application Returned for Modification</div>
                  <div class="text-dark small">Reason: <?= e($app['return_reason'] ?: 'Please upload updated document copies as required.') ?></div>
                  <?php if (!empty($app['return_deadline'])): ?>
                    <div class="text-danger small fw-semibold mt-1">Deadline for Resubmission: <?= format_date($app['return_deadline']) ?></div>
                  <?php endif; ?>
                </div>
                <a href="/portal/documents?app_id=<?= $app['id'] ?>" class="portal-btn-primary py-2 px-3.5" style="font-size:0.85rem;">
                  <i class="fa-solid fa-upload"></i>
                  <span>Upload Requested Files</span>
                </a>
              </div>
            </div>
          <?php endif; ?>

          <!-- Quick Action Buttons Footer -->
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 border-top">
            <div class="small text-muted">
              <i class="fa-solid fa-lock text-success me-1"></i> Data encrypted &amp; verified by MS TRAVEL HUB Consular Desk.
            </div>
            <div class="d-flex gap-2">
              <a href="/portal/documents?app_id=<?= $app['id'] ?>" class="portal-btn-primary py-1.5 px-3" style="font-size:0.82rem;">
                <i class="fa-solid fa-folder-open"></i>
                <span>Checklist &amp; Uploads</span>
              </a>
              <a href="/portal/invoices" class="portal-btn-outline py-1.5 px-3" style="font-size:0.82rem;">
                <i class="fa-solid fa-receipt"></i>
                <span>Payment Status</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- ─── UPCOMING APPOINTMENTS SECTION ───────────────────────────────────── -->
  <?php if (!empty($appointments)): ?>
    <div class="portal-bento-card mb-4">
      <div class="portal-card-header">
        <h6 class="fw-bold text-dark mb-0">
          <i class="fa-solid fa-calendar-check me-2" style="color:var(--portal-primary);"></i> Upcoming Consular &amp; Biometrics Appointments
        </h6>
      </div>
      <div class="p-3 p-md-4">
        <div class="row g-3">
          <?php foreach ($appointments as $apt): ?>
            <div class="col-md-6">
              <div class="p-3 bg-light rounded-3 border h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <span class="badge fw-bold" style="background:var(--portal-primary);"><?= e($apt['appointment_type']) ?></span>
                  <span class="badge bg-success"><?= e($apt['status']) ?></span>
                </div>
                <h6 class="fw-bold text-dark mb-1"><?= e($apt['center_name']) ?></h6>
                <div class="text-muted small mb-2"><i class="fa-solid fa-location-dot me-1 text-danger"></i><?= e($apt['location_address']) ?></div>
                <div class="small fw-semibold text-dark">
                  <i class="fa-regular fa-clock me-1" style="color:var(--portal-primary);"></i> <?= format_date($apt['appointment_date']) ?> at <?= date('h:i A', strtotime($apt['appointment_time'])) ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- ─── APPLICANT PROFILE & IDENTITY SECTION ────────────────────────── -->
  <div class="portal-bento-card mb-4" id="profile">
    <div class="portal-card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h6 class="fw-bold text-dark mb-0">
        <i class="fa-solid fa-id-card me-2" style="color:var(--portal-primary);"></i> Applicant Profile &amp; Verification Details
      </h6>
      <span class="badge font-monospace px-3 py-1.5" style="background:var(--portal-primary); font-size:0.75rem;">
        <?= e($customer['customer_code'] ?? 'MSV-CUST') ?>
      </span>
    </div>
    <div class="p-3 p-md-4">
      <div class="row g-3">
        <div class="col-sm-6 col-md-3">
          <div class="p-3 bg-light rounded-3 border">
            <div class="text-muted small mb-1">Full Legal Name</div>
            <div class="fw-bold text-dark"><?= e($customer['full_name'] ?? '—') ?></div>
          </div>
        </div>
        <div class="col-sm-6 col-md-3">
          <div class="p-3 bg-light rounded-3 border">
            <div class="text-muted small mb-1">Registered Email</div>
            <div class="fw-bold text-dark text-truncate" title="<?= e($customer['email'] ?? '') ?>"><?= e($customer['email'] ?? '—') ?></div>
          </div>
        </div>
        <div class="col-sm-6 col-md-3">
          <div class="p-3 bg-light rounded-3 border">
            <div class="text-muted small mb-1">Contact Phone</div>
            <div class="fw-bold text-dark"><?= e($customer['phone'] ?? '—') ?></div>
          </div>
        </div>
        <div class="col-sm-6 col-md-3">
          <div class="p-3 bg-light rounded-3 border">
            <div class="text-muted small mb-1">Passport / Travel Doc</div>
            <div class="fw-bold text-dark font-monospace"><?= e($customer['passport_number'] ?? 'On File') ?></div>
          </div>
        </div>
      </div>
      <div class="mt-3 pt-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2 text-muted small">
        <div>
          <i class="fa-solid fa-circle-check text-success me-1"></i> Customer account active and linked to MS TRAVEL HUB consular management network.
        </div>
        <div>
          <a href="/portal/documents" class="text-decoration-none fw-semibold" style="color:var(--portal-primary);">
            <i class="fa-solid fa-file-circle-check me-1"></i> Manage Document Vault &rarr;
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<footer class="text-center py-4 text-muted small" style="border-top: 1px solid var(--portal-border-subtle);">
  &copy; <?= date('Y') ?> <strong style="color:var(--portal-text-main);">MS TRAVEL HUB</strong> &bull; Applicant Portal &bull; Enterprise Visa Solutions
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
