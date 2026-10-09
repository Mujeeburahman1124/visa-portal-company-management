<?php
$pageTitle = 'APPLICANT DOCUMENT PROFILE — ' . e($app['customer_name'] ?? 'Applicant') . ' (' . e($app['application_number'] ?? '') . ')';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';

// Calculate age if DOB available
$age = null;
if (!empty($app['dob'])) {
    $dobDate = new DateTime($app['dob']);
    $todayDate = new DateTime();
    $age = $todayDate->diff($dobDate)->y;
}

// Permissions
$canManageDocs = user_can('documents.manage') || user_has_role(['super-admin', 'admin', 'branch-manager']);
$canVerifyDocs = user_can('documents.verify') || $canManageDocs;
$canDeleteDocs = user_can('documents.delete') || user_has_role(['super-admin', 'admin']);
?>

<link rel="stylesheet" href="/assets/css/pages/documents.css?v=1.0">

<div class="content-body">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'warning')) ?> alert-dismissible fade show mb-4 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- 1. Header with Breadcrumb, Navigation, and Application Details -->
  <div class="workspace-header">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="d-flex align-items-center gap-3">
        <a href="/documents" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm bg-white" title="Return to Document Directory">
          <i class="fa-solid fa-arrow-left me-1"></i> Documents
        </a>
        <div>
          <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
            <h3 class="workspace-title mb-0"><?= e($app['customer_name']) ?></h3>
            <span class="workspace-app-badge"><?= e($app['application_number']) ?></span>
            
            <?php
              $statusBadge = 'bg-secondary';
              if ($app['status'] === 'Approved') $statusBadge = 'bg-success';
              elseif ($app['status'] === 'Pending') $statusBadge = 'bg-warning text-dark';
              elseif ($app['status'] === 'Rejected') $statusBadge = 'bg-danger';
              elseif ($app['status'] === 'In Process') $statusBadge = 'bg-primary';
              elseif ($app['status'] === 'Draft') $statusBadge = 'bg-info text-dark';
            ?>
            <span class="badge <?= $statusBadge ?> px-2.5 py-1.5 fw-bold"><?= strtoupper(e($app['status'])) ?></span>
            
            <span class="badge bg-light text-dark border px-2.5 py-1.5 fw-semibold">
              <i class="fa-solid fa-timeline text-primary me-1"></i><?= e($app['current_stage']) ?>
            </span>
          </div>
          <div class="text-muted small d-flex flex-wrap align-items-center gap-3">
            <span><i class="fa-solid fa-passport text-secondary me-1"></i><strong>Visa Service:</strong> <?= e($app['service_name'] ?: 'Standard Visa') ?></span>
            <span><i class="fa-solid fa-globe text-secondary me-1"></i><strong>Destination:</strong> <?= e($app['flag_emoji'] ?? '🌐') ?> <?= e($app['destination_country_name'] ?? $app['destination_country'] ?? 'United Arab Emirates') ?></span>
            <span><i class="fa-solid fa-code-branch text-secondary me-1"></i><strong>Branch:</strong> <?= e($app['branch_name'] ?? 'Head Office') ?></span>
          </div>
        </div>
      </div>

      <!-- Top Quick Header Actions -->
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="/documents/download-all?application_id=<?= (int)$app['id'] ?>" class="btn btn-outline-primary btn-sm px-3 shadow-sm" title="Download all documents in organized ZIP bundle">
          <i class="fa-solid fa-file-zipper me-1"></i> Download All (ZIP)
        </a>
        <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
          <i class="fa-solid fa-cloud-arrow-up me-1"></i> + Upload Document
        </button>
      </div>
    </div>

    <!-- Multi-Application Switcher Tabs (Requirement 42) -->
    <?php if (count($customerApplications) > 1): ?>
      <div class="app-switcher-bar">
        <span class="text-muted small fw-bold me-1"><i class="fa-solid fa-folder-tree me-1"></i>Customer Applications:</span>
        <?php foreach ($customerApplications as $otherApp): ?>
          <a href="/documents/profile?application_id=<?= (int)$otherApp['id'] ?>" 
             class="app-switch-chip <?= ((int)$otherApp['id'] === (int)$app['id']) ? 'active' : '' ?>">
            <i class="fa-solid fa-file-invoice"></i>
            <span><?= e($otherApp['application_number']) ?></span>
            <span class="opacity-75">(<?= e($otherApp['status']) ?>)</span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- 2. Responsive 3-Column Layout: Left (Profile), Center (Details & Docs), Right (Quick Access & Actions) -->
  <div class="row g-3 g-xl-4">

    <!-- ================================================================
         LEFT COLUMN: HERO APPLICANT PROFILE CARD (Matching Screenshot)
         ================================================================ -->
    <div class="col-12 col-lg-4 col-xl-3">
      
      <!-- Luxury Hero Profile Card Inspired by Mobile Reference -->
      <div class="hero-profile-card">
        
        <!-- Header: Title, Customer Code, and Floating Edit/Camera Circle Button -->
        <div class="hero-profile-header">
          <div class="min-w-0 flex-grow-1 pe-2">
            <h4 class="hero-profile-title mb-1 text-truncate" title="<?= e($app['customer_name']) ?>"><?= e($app['customer_name']) ?></h4>
            <div class="d-flex align-items-center gap-1.5 flex-wrap">
              <span class="hero-code-badge font-monospace"><?= e($app['customer_code'] ?? 'MSC-000000') ?></span>
              <span class="badge <?= $statusBadge ?> px-2 py-0.5" style="font-size:0.68rem;"><?= e($app['status']) ?></span>
            </div>
          </div>
          <button type="button" class="hero-action-circle shadow-sm" data-bs-toggle="modal" data-bs-target="#changePhotoModal" title="Upload or Change Photograph">
            <i class="fa-solid fa-pen"></i>
          </button>
        </div>

        <!-- Center: Arched / Domed Avatar Cutout with Radial Background Aura -->
        <div class="hero-avatar-arch-container">
          <div class="hero-avatar-arch">
            <?php
              $nameParts = explode(' ', trim((string)($app['customer_name'] ?? '')));
              $firstInitial = !empty($nameParts[0]) ? mb_substr($nameParts[0], 0, 1) : 'A';
              $lastInitial = count($nameParts) > 1 ? mb_substr(end($nameParts), 0, 1) : '';
              $initials = strtoupper($firstInitial . $lastInitial);
            ?>
            <?php if (!empty($quickDocs['photo']['id'])): ?>
              <img src="/documents/preview?id=<?= (int)$quickDocs['photo']['id'] ?>" 
                   alt="<?= e($app['customer_name']) ?>" 
                   id="applicantProfilePhotoImg"
                   loading="lazy"
                   onerror="this.style.display='none'; document.getElementById('applicantPhotoFallbackAvatar').style.display='flex';">
              <div id="applicantPhotoFallbackAvatar" class="hero-avatar-arch-initials" style="display:none;">
                <div class="initials-letters"><?= e($initials) ?></div>
                <span class="initials-caption">Photo not uploaded</span>
              </div>
            <?php else: ?>
              <div class="hero-avatar-arch-initials">
                <div class="initials-letters"><?= e($initials) ?></div>
                <span class="initials-caption">Photo not uploaded</span>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Lower Frosted Details Panel (Matching Screenshot) -->
        <div class="hero-frosted-details">
          <div class="hero-info-list">
            <div class="hero-info-row">
              <span class="hero-info-label">Age :</span>
              <span class="hero-info-value">
                <?= $age !== null ? $age . ' years' : '—' ?>
                <?php if (!empty($app['dob'])): ?>
                  <span class="text-muted fw-normal" style="font-size:0.75rem;">(<?= date('d M Y', strtotime($app['dob'])) ?>)</span>
                <?php endif; ?>
              </span>
            </div>
            <div class="hero-info-row">
              <span class="hero-info-label">Email :</span>
              <span class="hero-info-value">
                <?php if (!empty($app['email'])): ?>
                  <a href="mailto:<?= e($app['email']) ?>" class="text-dark text-decoration-none"><?= e($app['email']) ?></a>
                <?php else: ?>
                  —
                <?php endif; ?>
              </span>
            </div>
            <div class="hero-info-row">
              <span class="hero-info-label">Phone number :</span>
              <span class="hero-info-value">
                <?php if (!empty($app['mobile'])): ?>
                  <a href="tel:<?= e($app['mobile']) ?>" class="text-dark text-decoration-none font-monospace"><?= e($app['mobile']) ?></a>
                <?php else: ?>
                  —
                <?php endif; ?>
              </span>
            </div>
          </div>

          <!-- 3 Pill Mini-Cards (ID, Passport, Residence) -->
          <div class="hero-pills-row">
            <div class="hero-pill-stat">
              <span class="hero-pill-lbl">ID</span>
              <span class="hero-pill-val font-monospace" title="<?= e($app['national_id_number'] ?: 'N/A') ?>">
                <?= e($app['national_id_number'] ?: '—') ?>
              </span>
            </div>
            <div class="hero-pill-stat">
              <span class="hero-pill-lbl">Passport</span>
              <span class="hero-pill-val font-monospace text-primary" title="<?= e($app['passport_number'] ?: 'N/A') ?>">
                <?= e($app['passport_number'] ?: '—') ?>
              </span>
            </div>
            <div class="hero-pill-stat">
              <span class="hero-pill-lbl">Residence</span>
              <span class="hero-pill-val text-truncate" title="<?= e($app['current_country'] ?: ($app['nationality'] ?: 'N/A')) ?>">
                <?= e($app['current_country'] ?: ($app['nationality'] ?: '—')) ?>
              </span>
            </div>
          </div>
        </div>

        <!-- Direct Contact Actions Bar -->
        <div class="d-flex align-items-center justify-content-center gap-2 mt-3 pt-1">
          <?php if (!empty($app['whatsapp'])): ?>
            <?php $waClean = preg_replace('/[^0-9]/', '', (string)$app['whatsapp']); ?>
            <a href="https://wa.me/<?= e($waClean) ?>" target="_blank" class="btn btn-sm btn-outline-success py-1 px-2.5 rounded-pill shadow-2xs font-monospace" title="WhatsApp Chat" style="font-size:0.75rem;">
              <i class="fa-brands fa-whatsapp me-1"></i> WhatsApp
            </a>
          <?php endif; ?>
          <?php if (!empty($app['mobile'])): ?>
            <a href="tel:<?= e($app['mobile']) ?>" class="btn btn-sm btn-outline-primary py-1 px-2.5 rounded-pill shadow-2xs" title="Call Applicant" style="font-size:0.75rem;">
              <i class="fa-solid fa-phone me-1"></i> Call
            </a>
          <?php endif; ?>
          <a href="/customers/show?id=<?= (int)$app['customer_id'] ?>" class="btn btn-sm btn-light border py-1 px-2.5 rounded-pill shadow-2xs text-secondary" title="Full Customer Profile" style="font-size:0.75rem;">
            <i class="fa-solid fa-user-gear me-1"></i> CRM
          </a>
        </div>

        <!-- Operations Assignment Strip -->
        <div class="w-100 p-2.5 mt-3 bg-white border rounded-3 text-start small shadow-2xs">
          <div class="text-muted" style="font-size: 0.68rem; text-transform: uppercase; font-weight: 700;">Operations Assignment</div>
          <div class="fw-bold text-dark mt-0.5"><i class="fa-solid fa-user-tie text-secondary me-1"></i><?= e($app['assigned_staff_name'] ?: 'Unassigned Staff') ?></div>
          <div class="text-muted small mt-0.5"><i class="fa-solid fa-building text-secondary me-1"></i><?= e($app['branch_name'] ?: 'Main Branch') ?></div>
        </div>

      </div>

    </div>

    <!-- ================================================================
         CENTER COLUMN: BENTO GRID & COMPLETE WORKSPACE
         ================================================================ -->
    <div class="col-12 col-lg-8 col-xl-6">

      <!-- Luxury Bento Grid (Matching 4 Bento Tiles in Screenshot) -->
      <div class="bento-grid">
        <!-- Bento Card 1: Documents Vault -->
        <div class="bento-card bento-card-info">
          <div class="bento-header">
            <span class="bento-title"><i class="fa-solid fa-folder-open text-primary me-1"></i> Documents</span>
            <a href="#allDocsSection" class="bento-arrow-btn" title="View All Uploaded Documents"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
          </div>
          <div class="bento-metric">
            <span class="bento-metric-num"><?= count($applicationDocuments) ?></span>
            <span class="bento-metric-lbl">Total Files</span>
          </div>
        </div>

        <!-- Bento Card 2: Requirements Action Required -->
        <div class="bento-card <?= (int)$checklist['total_missing'] > 0 ? 'bento-card-warning' : 'bento-card-primary' ?>">
          <div class="bento-header">
            <span class="bento-title"><i class="fa-solid fa-triangle-exclamation <?= (int)$checklist['total_missing'] > 0 ? 'text-warning' : 'text-success' ?> me-1"></i> Action Req</span>
            <a href="#actionRequiredSection" class="bento-arrow-btn" title="View Pending Actions"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
          </div>
          <div class="bento-metric">
            <span class="bento-metric-num"><?= (int)$checklist['total_missing'] + (int)$checklist['total_rejected'] ?></span>
            <span class="bento-metric-lbl"><?= (int)$checklist['total_missing'] > 0 ? 'Pending' : 'Compliant' ?></span>
          </div>
        </div>

        <!-- Bento Card 3: Completion Readiness -->
        <div class="bento-card bento-card-primary">
          <div class="bento-header">
            <span class="bento-title"><i class="fa-solid fa-circle-check text-success me-1"></i> Readiness</span>
            <a href="#readinessSection" class="bento-arrow-btn" title="View Verification Readiness"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
          </div>
          <div class="bento-metric">
            <span class="bento-metric-num"><?= (int)$checklist['percentage'] ?><span style="font-size:1.3rem;">%</span></span>
            <span class="bento-metric-lbl"><?= (int)$checklist['total_verified'] ?>/<?= (int)$checklist['total_required'] ?> Verified</span>
          </div>
        </div>

        <!-- Bento Card 4: Passport Validity -->
        <div class="bento-card bento-card-accent">
          <div class="bento-header">
            <span class="bento-title"><i class="fa-solid fa-passport text-secondary me-1"></i> Passport</span>
            <a href="#passportSection" class="bento-arrow-btn" title="View Passport Credentials"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
          </div>
          <div class="bento-metric">
            <span class="bento-metric-num" style="font-size:1.45rem;"><?= e($passportValidity['label']) ?></span>
            <span class="bento-metric-lbl"><?= !empty($passportValidity['days_remaining']) && $passportValidity['days_remaining'] > 0 ? round($passportValidity['days_remaining'] / 365, 1) . ' Yrs' : 'Status' ?></span>
          </div>
        </div>
      </div>

      <!-- 1. Document Completion & Checklist Progress (Requirement 24) -->
      <div class="workspace-card" id="readinessSection">
        <div class="workspace-card-header">
          <h6 class="workspace-card-title">
            <i class="fa-solid fa-list-check text-primary"></i> Document Completion &amp; Requirements
          </h6>
          <span class="badge <?= (int)$checklist['percentage'] === 100 ? 'bg-success' : 'bg-primary' ?> px-2.5 py-1 fw-bold"><?= (int)$checklist['percentage'] ?>% Complete</span>
        </div>
        <div class="workspace-card-body">
          <div class="completion-banner">
            <div class="d-flex justify-content-between align-items-center">
              <span class="small fw-bold text-dark">Overall File Readiness</span>
              <span class="small fw-bold text-primary font-monospace"><?= (int)$checklist['total_verified'] ?> of <?= (int)$checklist['total_required'] ?> Verified</span>
            </div>
            <div class="completion-progress-bar">
              <div class="completion-progress-fill" style="width: <?= (int)$checklist['percentage'] ?>%;"></div>
            </div>
            <div class="d-flex flex-wrap gap-2 pt-1 small">
              <span class="badge bg-white text-secondary border"><i class="fa-solid fa-asterisk text-primary me-1"></i>Required: <?= (int)$checklist['total_required'] ?></span>
              <span class="badge bg-white text-success border"><i class="fa-solid fa-check-circle text-success me-1"></i>Verified: <?= (int)$checklist['total_verified'] ?></span>
              <span class="badge bg-white text-warning text-dark border"><i class="fa-solid fa-clock text-warning me-1"></i>Pending: <?= (int)$checklist['total_pending'] ?></span>
              <?php if ((int)$checklist['total_missing'] > 0): ?>
                <span class="badge bg-danger text-white"><i class="fa-solid fa-circle-exclamation me-1"></i>Missing: <?= (int)$checklist['total_missing'] ?></span>
              <?php endif; ?>
              <?php if ((int)$checklist['total_rejected'] > 0): ?>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fa-solid fa-ban me-1"></i>Rejected: <?= (int)$checklist['total_rejected'] ?></span>
              <?php endif; ?>
              <?php if ((int)$checklist['total_expired'] > 0): ?>
                <span class="badge bg-dark text-white"><i class="fa-solid fa-calendar-xmark me-1"></i>Expired: <?= (int)$checklist['total_expired'] ?></span>
              <?php endif; ?>
              <?php if ((int)($checklist['total_optional'] ?? 0) > 0): ?>
                <span class="badge bg-light text-muted border"><i class="fa-solid fa-plus me-1"></i>Optional: <?= (int)$checklist['total_optional'] ?></span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Missing Documents Alert Box (Part 19) -->
          <div id="actionRequiredSection"></div>
          <?php
            $missingMandatory = array_filter($checklist['mandatory_items'] ?? [], function($item) {
                return $item['status'] === 'MISSING';
            });
            $rejectedMandatory = array_filter($checklist['mandatory_items'] ?? [], function($item) {
                return $item['status'] === 'REJECTED';
            });
            $expiredMandatory = array_filter($checklist['mandatory_items'] ?? [], function($item) {
                return $item['status'] === 'EXPIRED';
            });
          ?>
          <?php if (!empty($missingMandatory)): ?>
            <div class="mt-3 p-3 bg-warning-subtle border border-warning rounded-3">
              <div class="fw-bold text-dark small mb-2 d-flex align-items-center gap-1.5">
                <i class="fa-solid fa-triangle-exclamation text-warning"></i>
                <span>Action Required: <?= count($missingMandatory) ?> Mandatory Document(s) Missing</span>
              </div>
              <div class="d-flex flex-column gap-2">
                <?php foreach ($missingMandatory as $mItem): ?>
                  <div class="d-flex flex-wrap align-items-center justify-content-between p-2 bg-white rounded border border-warning-subtle gap-2">
                    <div class="d-flex align-items-center gap-2">
                      <i class="fa-solid fa-file-circle-exclamation text-danger fs-6"></i>
                      <div>
                        <div class="fw-bold text-dark small"><?= e($mItem['document_name']) ?></div>
                        <div class="text-muted" style="font-size: 0.72rem;"><?= e($mItem['condition_notes'] ?: ($mItem['instructions'] ?: 'Standard requirement for visa filing.')) ?></div>
                      </div>
                    </div>
                    <div class="d-flex align-items-center gap-1.5">
                      <button type="button" class="btn btn-outline-warning text-dark btn-sm py-0.5 px-2 font-monospace" style="font-size: 0.72rem;" onclick="openRequestDocModal(<?= (int)$mItem['document_type_id'] ?>, '<?= e(addslashes($mItem['document_name'])) ?>')">
                        <i class="fa-solid fa-paper-plane me-1"></i>Request
                      </button>
                      <button type="button" class="btn btn-primary btn-sm py-0.5 px-2 font-monospace" style="font-size: 0.72rem;" onclick="openUploadModalWithType(<?= (int)$mItem['document_type_id'] ?>)">
                        <i class="fa-solid fa-upload me-1"></i>Upload
                      </button>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($rejectedMandatory)): ?>
            <div class="mt-3 p-3 bg-danger-subtle border border-danger-subtle rounded-3">
              <div class="fw-bold text-danger small mb-2 d-flex align-items-center gap-1.5">
                <i class="fa-solid fa-ban text-danger"></i>
                <span>Action Required: <?= count($rejectedMandatory) ?> Mandatory Document(s) Rejected</span>
              </div>
              <div class="d-flex flex-column gap-2">
                <?php foreach ($rejectedMandatory as $rItem): ?>
                  <div class="d-flex flex-wrap align-items-center justify-content-between p-2 bg-white rounded border border-danger-subtle gap-2">
                    <div>
                      <div class="fw-bold text-dark small"><?= e($rItem['document_name']) ?></div>
                      <div class="text-danger small" style="font-size: 0.72rem;"><strong>Rejection Reason:</strong> <?= e($rItem['rejection_reason'] ?: 'Document does not meet compliance standards.') ?></div>
                    </div>
                    <button type="button" class="btn btn-danger btn-sm py-0.5 px-2 font-monospace" style="font-size: 0.72rem;" onclick="openUploadModalWithType(<?= (int)$rItem['document_type_id'] ?>)">
                      <i class="fa-solid fa-upload me-1"></i>Re-upload
                    </button>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($expiredMandatory)): ?>
            <div class="mt-3 p-3 bg-dark-subtle border border-dark-subtle rounded-3">
              <div class="fw-bold text-dark small mb-2 d-flex align-items-center gap-1.5">
                <i class="fa-solid fa-calendar-xmark text-danger"></i>
                <span>Action Required: <?= count($expiredMandatory) ?> Mandatory Document(s) Expired</span>
              </div>
              <div class="d-flex flex-column gap-2">
                <?php foreach ($expiredMandatory as $exItem): ?>
                  <div class="d-flex flex-wrap align-items-center justify-content-between p-2 bg-white rounded border border-dark-subtle gap-2">
                    <div>
                      <div class="fw-bold text-dark small"><?= e($exItem['document_name']) ?></div>
                      <div class="text-danger small" style="font-size: 0.72rem;">Expired on <?= e($exItem['expiry_date']) ?>. A valid renewed document is required.</div>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm py-0.5 px-2 font-monospace" style="font-size: 0.72rem;" onclick="openUploadModalWithType(<?= (int)$exItem['document_type_id'] ?>)">
                      <i class="fa-solid fa-upload me-1"></i>Upload Valid
                    </button>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Unique Document Requirements Checklist Matrix (Part 7, 8, 9, 10) -->
          <?php if (!empty($checklist['items'])): ?>
            <div class="mt-4" id="requirementsSection">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold text-dark small"><i class="fa-solid fa-clipboard-check text-primary me-1"></i> Visa Service Document Requirements</span>
                <span class="text-muted" style="font-size: 0.72rem;"><?= count($checklist['items']) ?> Unique Requirements</span>
              </div>
              <div class="d-flex flex-column gap-2">
                <?php foreach ($checklist['items'] as $cItem): ?>
                  <?php
                    $cStatus = $cItem['status'];
                    $cBadge = 'bg-secondary';
                    $cIcon = 'fa-circle-minus';
                    if ($cStatus === 'VERIFIED') {
                        $cBadge = 'bg-success';
                        $cIcon = 'fa-check';
                    } elseif ($cStatus === 'UNDER_REVIEW') {
                        $cBadge = 'bg-warning text-dark';
                        $cIcon = 'fa-clock';
                    } elseif ($cStatus === 'REJECTED') {
                        $cBadge = 'bg-danger';
                        $cIcon = 'fa-xmark';
                    } elseif ($cStatus === 'EXPIRED') {
                        $cBadge = 'bg-dark text-white';
                        $cIcon = 'fa-calendar-xmark';
                    } elseif ($cStatus === 'MISSING') {
                        $cBadge = 'bg-secondary-subtle text-secondary border';
                        $cIcon = 'fa-circle-exclamation';
                    }
                  ?>
                  <div class="p-2.5 bg-white rounded border d-flex flex-wrap align-items-center justify-content-between gap-2 shadow-2xs">
                    <div class="d-flex align-items-start gap-2 flex-grow-1 min-w-0">
                      <div class="mt-0.5">
                        <span class="badge <?= $cBadge ?> rounded-circle p-1.5 d-inline-flex align-items-center justify-content-center" style="width: 22px; height: 22px;">
                          <i class="fa-solid <?= $cIcon ?>" style="font-size: 0.7rem;"></i>
                        </span>
                      </div>
                      <div class="min-w-0">
                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                          <span class="fw-bold text-dark small"><?= e($cItem['document_name']) ?></span>
                          <?php if ($cItem['is_critical']): ?>
                            <span class="badge bg-danger text-white py-0.5 px-1.5" style="font-size: 0.65rem;">Critical</span>
                          <?php elseif ($cItem['is_mandatory']): ?>
                            <span class="badge bg-danger-subtle text-danger py-0.5 px-1.5" style="font-size: 0.65rem;">Mandatory</span>
                          <?php else: ?>
                            <span class="badge bg-light text-muted border py-0.5 px-1.5" style="font-size: 0.65rem;">Optional</span>
                          <?php endif; ?>
                          <span class="badge <?= $cBadge ?> py-0.5 px-1.5 font-monospace" style="font-size: 0.68rem;"><?= e($cStatus) ?></span>
                          <?php if (!empty($cItem['version'])): ?>
                            <span class="badge bg-light text-secondary border py-0.5 px-1.5 font-monospace" style="font-size: 0.65rem;">v<?= (int)$cItem['version'] ?></span>
                          <?php endif; ?>
                        </div>
                        <?php if (!empty($cItem['condition_notes']) || !empty($cItem['instructions'])): ?>
                          <div class="text-muted small mt-0.5" style="font-size: 0.72rem;">
                            <?= e($cItem['condition_notes'] ?: $cItem['instructions']) ?>
                          </div>
                        <?php endif; ?>
                        <?php if (!empty($cItem['rejection_reason'])): ?>
                          <div class="text-danger small mt-0.5" style="font-size: 0.72rem;">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i>Rejected: <?= e($cItem['rejection_reason']) ?>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                      <?php if (!empty($cItem['document_id'])): ?>
                        <?php $ext = strtolower(pathinfo($cItem['file_name'] ?? '', PATHINFO_EXTENSION)); ?>
                        <button type="button" class="btn btn-outline-primary btn-sm py-0.5 px-2 font-monospace" style="font-size: 0.72rem;" onclick="openDocumentPreview(<?= (int)$cItem['document_id'] ?>, '<?= e(addslashes($cItem['document_name'])) ?>', '<?= e($ext) ?>', '<?= e($cItem['status']) ?>', <?= (int)$cItem['version'] ?>)">
                          <i class="fa-solid fa-eye me-1"></i>View
                        </button>
                      <?php else: ?>
                        <button type="button" class="btn btn-outline-warning text-dark btn-sm py-0.5 px-2 font-monospace" style="font-size: 0.72rem;" onclick="openRequestDocModal(<?= (int)$cItem['document_type_id'] ?>, '<?= e(addslashes($cItem['document_name'])) ?>')">
                          <i class="fa-solid fa-paper-plane me-1"></i>Request
                        </button>
                        <button type="button" class="btn btn-primary btn-sm py-0.5 px-2 font-monospace" style="font-size: 0.72rem;" onclick="openUploadModalWithType(<?= (int)$cItem['document_type_id'] ?>)">
                          <i class="fa-solid fa-upload me-1"></i>Upload
                        </button>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Expiring Documents Alert Box (Requirement 26) -->
          <?php if (!empty($expiringDocs)): ?>
            <div class="mt-3 p-3 bg-danger-subtle border border-danger-subtle rounded-3">
              <div class="fw-bold text-danger small mb-2 d-flex align-items-center gap-1.5">
                <i class="fa-solid fa-calendar-xmark text-danger"></i>
                <span>Notice: <?= count($expiringDocs) ?> Uploaded Document(s) Expiring Within 30 Days</span>
              </div>
              <div class="d-flex flex-column gap-2">
                <?php foreach ($expiringDocs as $expDoc): ?>
                  <?php $expDays = (int)round((strtotime($expDoc['expiry_date']) - time()) / 86400); ?>
                  <div class="d-flex align-items-center justify-content-between p-2 bg-white rounded border gap-2 small">
                    <span class="fw-bold text-dark"><?= e($expDoc['document_title'] ?: $expDoc['doc_type_name']) ?></span>
                    <span class="badge bg-warning text-dark font-monospace">Expires in <?= max(0, $expDays) ?> days (<?= e($expDoc['expiry_date']) ?>)</span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- 2. Basic Information (All Database Customer Fields - Requirement 11) -->
      <div class="workspace-card">
        <div class="workspace-card-header">
          <h6 class="workspace-card-title">
            <i class="fa-solid fa-address-card text-primary"></i> Basic Personal Information
          </h6>
          <a href="/customers/edit?id=<?= (int)$app['customer_id'] ?>" class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold text-primary" style="font-size: 0.78rem;">
            <i class="fa-solid fa-user-pen me-1"></i>Edit
          </a>
        </div>
        <div class="workspace-card-body">
          <div class="detail-grid">
            <div class="detail-item">
              <span class="detail-label">Full Name</span>
              <span class="detail-value"><?= e($app['customer_name'] ?: '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">First / Middle / Last Name</span>
              <span class="detail-value"><?= e(trim(($app['first_name'] ?? '') . ' ' . ($app['middle_name'] ?? '') . ' ' . ($app['last_name'] ?? '')) ?: '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Customer Code</span>
              <span class="detail-value font-monospace"><?= e($app['customer_code'] ?: '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Gender</span>
              <span class="detail-value"><?= e($app['gender'] ?: '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Date of Birth &amp; Age</span>
              <span class="detail-value">
                <?= e($app['dob'] ? date('d M Y', strtotime($app['dob'])) : '—') ?>
                <?php if ($age !== null): ?>
                  <span class="text-muted fw-normal">(<?= $age ?> yrs)</span>
                <?php endif; ?>
              </span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Nationality</span>
              <span class="detail-value"><?= e($app['nationality'] ?: '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Place of Birth</span>
              <span class="detail-value"><?= e($app['place_of_birth'] ?: '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Marital Status</span>
              <span class="detail-value"><?= e($app['marital_status'] ?: '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Religion</span>
              <span class="detail-value"><?= e($app['religion'] ?: '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Occupation</span>
              <span class="detail-value"><?= e($app['occupation'] ?: '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Mobile Number</span>
              <span class="detail-value"><?= e($app['mobile'] ?: '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">WhatsApp Number</span>
              <span class="detail-value"><?= e($app['whatsapp'] ?: '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Email Address</span>
              <span class="detail-value"><?= e($app['email'] ?: '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Country of Residence</span>
              <span class="detail-value"><?= e($app['current_country'] ?: '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Address</span>
              <span class="detail-value"><?= e($app['address'] ?: '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">National / Emirates ID</span>
              <span class="detail-value font-monospace">
                <?= e($app['national_id_number'] ?: '—') ?>
                <?php if (!empty($app['national_id_expiry'])): ?>
                  <small class="text-muted">(Exp: <?= e($app['national_id_expiry']) ?>)</small>
                <?php endif; ?>
              </span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Profile Created</span>
              <span class="detail-value text-muted" style="font-size: 0.8rem;"><?= e($app['customer_created_at'] ? date('d M Y, h:i A', strtotime($app['customer_created_at'])) : '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Last Profile Update</span>
              <span class="detail-value text-muted" style="font-size: 0.8rem;"><?= e($app['customer_updated_at'] ? date('d M Y, h:i A', strtotime($app['customer_updated_at'])) : '—') ?></span>
            </div>
          </div>
          <?php if (!empty($app['customer_notes'])): ?>
            <div class="mt-3 p-2.5 bg-light rounded border small">
              <span class="text-muted fw-bold d-block mb-1" style="font-size: 0.72rem; text-transform: uppercase;">Customer Profile Notes:</span>
              <span class="text-secondary"><?= nl2br(e($app['customer_notes'])) ?></span>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- 3. Application Information (Requirement 12) -->
      <div class="workspace-card">
        <div class="workspace-card-header">
          <h6 class="workspace-card-title">
            <i class="fa-solid fa-file-invoice text-primary"></i> Application Details &amp; Processing Workflow
          </h6>
          <a href="/applications/show?id=<?= (int)$app['id'] ?>" class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold text-primary" style="font-size: 0.78rem;">
            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>View Application
          </a>
        </div>
        <div class="workspace-card-body">
          <div class="detail-grid">
            <div class="detail-item">
              <span class="detail-label">Application Number</span>
              <span class="detail-value font-monospace text-primary"><?= e($app['application_number']) ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Visa Service Package</span>
              <span class="detail-value"><?= e($app['service_name'] ?: 'Standard Visa') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Visa Category</span>
              <span class="detail-value"><?= e($app['visa_category'] ?? $app['category_name'] ?? 'General') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Destination Country</span>
              <span class="detail-value"><?= e($app['flag_emoji'] ?? '🌐') ?> <?= e($app['destination_country_name'] ?? $app['destination_country'] ?? 'United Arab Emirates') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Visa Duration</span>
              <span class="detail-value"><?= e($app['visa_duration'] ?: ($app['service_duration'] ?? '30 Days')) ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Entry Type</span>
              <span class="detail-value"><?= e($app['entry_type'] ?: ($app['service_entry_type'] ?? 'Single Entry')) ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Processing Type</span>
              <span class="detail-value"><?= e($app['processing_type'] ?: ($app['service_processing_type'] ?? 'Normal')) ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Application Filing Date</span>
              <span class="detail-value"><?= e($app['application_date'] ? date('d M Y', strtotime($app['application_date'])) : '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Expected Completion Date</span>
              <span class="detail-value"><?= e($app['expected_completion_date'] ? date('d M Y', strtotime($app['expected_completion_date'])) : '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Current Stage</span>
              <span class="detail-value"><span class="badge bg-light text-primary border"><?= e($app['current_stage']) ?></span></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Application Status</span>
              <span class="detail-value"><span class="badge <?= $statusBadge ?>"><?= e($app['status']) ?></span></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Priority Level</span>
              <span class="detail-value">
                <?php
                  $pClass = 'bg-secondary';
                  if ($app['priority'] === 'Urgent') $pClass = 'bg-warning text-dark';
                  elseif ($app['priority'] === 'Critical') $pClass = 'bg-danger text-white';
                  elseif ($app['priority'] === 'High') $pClass = 'bg-primary text-white';
                ?>
                <span class="badge <?= $pClass ?>"><?= e($app['priority'] ?? 'Normal') ?></span>
              </span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Processing Branch</span>
              <span class="detail-value"><?= e($app['branch_name'] ?: 'Main Branch') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Assigned Operations Staff</span>
              <span class="detail-value"><?= e($app['assigned_staff_name'] ?: 'Unassigned') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Submission Date</span>
              <span class="detail-value"><?= !empty($app['submission_date']) ? date('d M Y', strtotime($app['submission_date'])) : '—' ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Planned Travel Dates</span>
              <span class="detail-value">
                <?= !empty($app['travel_date']) ? date('d M Y', strtotime($app['travel_date'])) : '—' ?>
                <?php if (!empty($app['return_date'])): ?>
                  <span class="text-muted small">to</span> <?= date('d M Y', strtotime($app['return_date'])) ?>
                <?php endif; ?>
              </span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Package &amp; Financials</span>
              <span class="detail-value">
                <span class="fw-bold font-monospace">AED <?= number_format((float)($app['total_amount'] ?? $app['selling_price'] ?? 0), 2) ?></span>
                <span class="badge <?= ($app['payment_status'] ?? '') === 'Paid' ? 'bg-success' : 'bg-warning text-dark' ?> ms-1" style="font-size:0.68rem;"><?= e($app['payment_status'] ?? 'Pending') ?></span>
                <div class="text-muted" style="font-size:0.72rem;">Paid: AED <?= number_format((float)($app['paid_amount'] ?? 0), 2) ?> • Bal: AED <?= number_format((float)($app['balance_amount'] ?? 0), 2) ?></div>
              </span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Next Action / Follow-up</span>
              <span class="detail-value">
                <?= e($app['next_action'] ?: 'Standard file review') ?>
                <?php if (!empty($app['next_action_due_date'])): ?>
                  <small class="text-muted">(Due: <?= date('d M Y', strtotime($app['next_action_due_date'])) ?>)</small>
                <?php endif; ?>
              </span>
            </div>
            <?php if (!empty($app['visa_number'])): ?>
              <div class="detail-item">
                <span class="detail-label">Issued Visa Number</span>
                <span class="detail-value font-monospace text-success fw-bold">
                  <?= e($app['visa_number']) ?>
                  <?php if (!empty($app['visa_expiry_date'])): ?>
                    <span class="text-muted fw-normal small">(Valid till <?= e($app['visa_expiry_date']) ?>)</span>
                  <?php endif; ?>
                </span>
              </div>
            <?php endif; ?>
          </div>
          <?php if (!empty($app['internal_notes']) || !empty($app['customer_notes'])): ?>
            <div class="mt-3 p-2.5 bg-light rounded border small">
              <?php if (!empty($app['internal_notes'])): ?>
                <div class="mb-1">
                  <span class="text-muted fw-bold" style="font-size: 0.72rem; text-transform: uppercase;">Internal Operations Notes:</span>
                  <div class="text-secondary"><?= nl2br(e($app['internal_notes'])) ?></div>
                </div>
              <?php endif; ?>
              <?php if (!empty($app['customer_notes'])): ?>
                <div>
                  <span class="text-muted fw-bold" style="font-size: 0.72rem; text-transform: uppercase;">Application Notes for Applicant:</span>
                  <div class="text-secondary"><?= nl2br(e($app['customer_notes'])) ?></div>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- 4. Passport Information & Document Actions (Requirement 18 & 19) -->
      <div class="workspace-card" id="passportSection">
        <div class="workspace-card-header">
          <h6 class="workspace-card-title">
            <i class="fa-solid fa-passport text-primary"></i> PASSPORT INFORMATION
          </h6>
          <span class="badge <?= e($passportValidity['badge_class']) ?> px-2.5 py-1 fw-bold">
            <?= strtoupper(e($passportValidity['label'])) ?>
          </span>
        </div>
        <div class="workspace-card-body">
          <div class="detail-grid mb-3">
            <div class="detail-item">
              <span class="detail-label">Passport Number</span>
              <span class="detail-value font-monospace text-primary fs-6 fw-bold"><?= e($app['passport_number'] ?: '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Nationality</span>
              <span class="detail-value"><?= e($app['nationality'] ?: '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Issuing Country</span>
              <span class="detail-value"><?= e($app['passport_issuing_country'] ?: ($app['nationality'] ?: '—')) ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Date of Birth</span>
              <span class="detail-value"><?= e($app['dob'] ? date('d/m/Y', strtotime($app['dob'])) : '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Gender</span>
              <span class="detail-value"><?= e($app['gender'] ?: '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Date of Issue</span>
              <span class="detail-value"><?= e($app['passport_issue_date'] ? date('d/m/Y', strtotime($app['passport_issue_date'])) : '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Date of Expiry</span>
              <span class="detail-value font-monospace fw-semibold"><?= e($app['passport_expiry_date'] ? date('d/m/Y', strtotime($app['passport_expiry_date'])) : '—') ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Status</span>
              <span class="detail-value fw-bold <?= strpos($passportValidity['badge_class'], 'danger') !== false ? 'text-danger' : 'text-success' ?>">
                <?= !empty($passportValidity['label']) && $passportValidity['label'] === 'Valid' ? 'VALID' : strtoupper(e($passportValidity['label'] ?? 'VALID')) ?>
              </span>
            </div>
          </div>

          <!-- Direct Passport Document Actions -->
          <div class="p-3 bg-light rounded border">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-file-contract text-primary fs-5"></i>
                <div>
                  <div class="fw-bold small text-dark">PASSPORT DOCUMENT</div>
                  <div class="text-muted" style="font-size:0.75rem;">
                    <?php if (!empty($quickDocs['passport'])): ?>
                      <?= e($quickDocs['passport']['file_name'] ?: 'Passport Bio Page') ?> &bull; 
                      <span class="text-success fw-semibold"><i class="fa-solid fa-check"></i> Verified</span>
                    <?php else: ?>
                      No passport bio document attached
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="d-flex gap-2 flex-wrap">
                <?php if (!empty($quickDocs['passport'])): ?>
                  <?php $pExt = strtolower(pathinfo($quickDocs['passport']['file_name'] ?? 'doc.pdf', PATHINFO_EXTENSION)) ?: 'pdf'; ?>
                  <button type="button" class="btn btn-primary btn-sm px-3" onclick="openDocumentPreview(<?= (int)$quickDocs['passport']['id'] ?>, 'Passport Bio Page', '<?= e($pExt) ?>', '<?= e($quickDocs['passport']['status']) ?>', <?= (int)$quickDocs['passport']['version'] ?>)">
                    <i class="fa-solid fa-eye me-1"></i> View Passport
                  </button>
                  <a href="/documents/download?id=<?= (int)$quickDocs['passport']['id'] ?>" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="fa-solid fa-download me-1"></i> Download
                  </a>
                  <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#replaceDocModal" onclick="prepareReplaceModal(<?= (int)$quickDocs['passport']['id'] ?>, 'Passport Bio Page')">
                    <i class="fa-solid fa-arrows-rotate me-1"></i> Replace
                  </button>
                  <a href="/documents/history?id=<?= (int)$quickDocs['passport']['id'] ?>" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i> History
                  </a>
                <?php else: ?>
                  <button type="button" class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#uploadDocModal" onclick="openUploadModalWithType(1)">
                    <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload Passport
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 5. All Uploaded Documents Grouped by Category (Requirement 16 & 17) -->
      <div class="workspace-card" id="allDocsSection">
        <div class="workspace-card-header">
          <h6 class="workspace-card-title">
            <i class="fa-solid fa-folder-open text-primary"></i> All Application Documents (<?= count($applicationDocuments) ?>)
          </h6>
          <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2.5 fw-semibold" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
            <i class="fa-solid fa-plus me-1"></i> Add Document
          </button>
        </div>
        <div class="workspace-card-body">
          <?php if (empty($applicationDocuments)): ?>
            <div class="text-center py-4 text-muted">
              <i class="fa-solid fa-folder-open fs-2 mb-2 text-secondary opacity-50"></i>
              <div class="fw-semibold">No documents uploaded for this application yet.</div>
              <p class="small mb-3">Upload required passport, photo, or supporting visa documentation.</p>
              <button type="button" class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload First Document
              </button>
            </div>
          <?php else: ?>
            <?php foreach ($categorizedDocs as $catName => $catDocs): ?>
              <?php if (!empty($catDocs)): ?>
                <div class="doc-category-section">
                  <div class="doc-category-header">
                    <span><i class="fa-solid fa-folder me-1 text-primary"></i> <?= e($catName) ?></span>
                    <span class="badge bg-light text-muted border"><?= count($catDocs) ?> files</span>
                  </div>
                  <div class="doc-card-grid">
                    <?php foreach ($catDocs as $docItem): ?>
                      <?php
                        $ext = strtolower(pathinfo($docItem['file_name'] ?? '', PATHINFO_EXTENSION));
                        $dIcon = 'fa-file text-secondary';
                        if ($ext === 'pdf') $dIcon = 'fa-file-pdf text-danger';
                        elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) $dIcon = 'fa-file-image text-primary';
                        elseif (in_array($ext, ['doc', 'docx'], true)) $dIcon = 'fa-file-word text-info';

                        $stBadge = 'bg-secondary';
                        if ($docItem['status'] === 'VERIFIED') $stBadge = 'bg-success';
                        elseif ($docItem['status'] === 'REJECTED') $stBadge = 'bg-danger';
                        elseif ($docItem['status'] === 'UNDER_REVIEW') $stBadge = 'bg-warning text-dark';
                      ?>
                      <div class="doc-item-card">
                        <div class="doc-item-top">
                          <i class="fa-solid <?= $dIcon ?> doc-type-icon"></i>
                          <div class="flex-grow-1 min-w-0">
                            <div class="doc-title-text" title="<?= e($docItem['document_title'] ?: $docItem['doc_type_name']) ?>">
                              <?= e($docItem['document_title'] ?: $docItem['doc_type_name']) ?>
                            </div>
                            <div class="text-muted" style="font-size: 0.72rem;">
                              <span><?= e($docItem['doc_type_name']) ?></span>
                            </div>
                            <div class="d-flex align-items-center gap-1.5 mt-1">
                              <span class="badge <?= $stBadge ?> py-0.5 px-1.5" style="font-size: 0.68rem;">
                                <?= e($docItem['status']) ?>
                              </span>
                              <span class="badge bg-light text-muted border py-0.5 px-1.5 font-monospace" style="font-size: 0.68rem;">
                                v<?= (int)$docItem['version'] ?>
                              </span>
                              <?php if (!empty($docItem['expiry_date'])): ?>
                                <?php
                                  $expD = (int)round((strtotime($docItem['expiry_date']) - time()) / 86400);
                                  $expClass = ($expD < 0) ? 'text-danger' : (($expD <= 30) ? 'text-warning' : 'text-muted');
                                ?>
                                <span class="<?= $expClass ?>" style="font-size: 0.68rem;" title="Expiry Date: <?= e($docItem['expiry_date']) ?>">
                                  <i class="fa-regular fa-clock me-0.5"></i><?= e($docItem['expiry_date']) ?>
                                </span>
                              <?php endif; ?>
                            </div>
                          </div>
                        </div>

                        <!-- Action Bar (Requirements 18, 43, 44) -->
                        <div class="doc-item-actions">
                          <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 fw-semibold" style="font-size: 0.72rem;" onclick="openDocumentPreview(<?= (int)$docItem['id'] ?>, '<?= e(addslashes($docItem['document_title'] ?: $docItem['doc_type_name'])) ?>', '<?= e($ext) ?>', '<?= e($docItem['status']) ?>', <?= (int)$docItem['version'] ?>)">
                            <i class="fa-solid fa-eye me-1"></i> Preview
                          </button>
                          <a href="/documents/download?id=<?= (int)$docItem['id'] ?>" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.72rem;" title="Download File">
                            <i class="fa-solid fa-download"></i>
                          </a>
                          
                          <?php if ($canVerifyDocs && $docItem['status'] !== 'VERIFIED'): ?>
                            <form action="/documents/verify" method="POST" class="d-inline" onsubmit="return confirm('Verify this document as compliant?');">
                              <?= csrf_field() ?>
                              <input type="hidden" name="document_id" value="<?= (int)$docItem['id'] ?>">
                              <button type="submit" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size: 0.72rem;" title="Verify Document">
                                <i class="fa-solid fa-check"></i>
                              </button>
                            </form>
                          <?php endif; ?>

                          <?php if ($canVerifyDocs && $docItem['status'] !== 'REJECTED'): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.72rem;" title="Reject Document" onclick="openRejectModal(<?= (int)$docItem['id'] ?>)">
                              <i class="fa-solid fa-xmark"></i>
                            </button>
                          <?php endif; ?>

                          <button type="button" class="btn btn-sm btn-light border py-0 px-2" style="font-size: 0.72rem;" title="Version History" onclick="openHistoryModal(<?= (int)$docItem['id'] ?>)">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                          </button>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- 6. Recent Activity Log (Requirement 48) -->
      <?php if (!empty($recentActivity)): ?>
        <div class="workspace-card">
          <div class="workspace-card-header">
            <h6 class="workspace-card-title">
              <i class="fa-solid fa-clock-rotate-left text-primary"></i> Recent Document Activity
            </h6>
          </div>
          <div class="workspace-card-body p-0">
            <div class="list-group list-group-flush small">
              <?php foreach ($recentActivity as $act): ?>
                <div class="list-group-item d-flex align-items-center justify-content-between p-3">
                  <div>
                    <span class="badge bg-light text-dark border me-1 font-monospace"><?= e($act['action']) ?></span>
                    <span class="fw-semibold text-dark"><?= e($act['description'] ?: $act['action']) ?></span>
                    <div class="text-muted" style="font-size: 0.72rem;">By <?= e($act['user_name'] ?: 'System') ?></div>
                  </div>
                  <span class="text-muted" style="font-size: 0.75rem; white-space: nowrap;"><?= date('d M, h:i A', strtotime($act['created_at'])) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

    </div>

    <!-- ================================================================
         RIGHT COLUMN: DOCUMENT QUICK-ACCESS & ACTIONS PANEL
         ================================================================ -->
    <div class="col-12 col-xl-3">

      <!-- 1. Document Quick Access Panel (Requirement 14 & 15) -->
      <div class="workspace-card mb-3">
        <div class="workspace-card-header">
          <h6 class="workspace-card-title">
            <i class="fa-solid fa-bolt text-warning"></i> Quick Access Documents
          </h6>
        </div>
        <div class="workspace-card-body p-3">
          <?php
            $renderQuickStatus = function($doc, $defaultLabel) {
                if (empty($doc)) {
                    return '<div class="quick-doc-sub text-muted">○ ' . e($defaultLabel) . '</div>';
                }
                $st = $doc['status'] ?? 'UPLOADED';
                $badgeClass = 'bg-info-subtle text-dark';
                $badgeText = '⏳ ' . e($st);
                if ($st === 'VERIFIED') {
                    $badgeClass = 'bg-success-subtle text-success';
                    $badgeText = '✓ Verified';
                } elseif ($st === 'UNDER_REVIEW') {
                    $badgeClass = 'bg-warning-subtle text-dark';
                    $badgeText = '⏳ Under Review';
                } elseif ($st === 'REJECTED') {
                    $badgeClass = 'bg-danger-subtle text-danger';
                    $badgeText = '⚠ Rejected';
                } elseif ($st === 'EXPIRED') {
                    $badgeClass = 'bg-dark-subtle text-dark';
                    $badgeText = '⚠ Expired';
                }
                return '<div class="quick-doc-sub"><span class="badge ' . $badgeClass . ' py-0.5">' . $badgeText . '</span> &bull; <span class="font-monospace">v' . (int)($doc['version'] ?? 1) . '</span></div>';
            };
          ?>
          
          <!-- Quick Card: Photo -->
          <div class="quick-doc-card">
            <div class="quick-doc-icon-box text-primary">
              <i class="fa-solid fa-camera"></i>
            </div>
            <div class="quick-doc-details">
              <div class="quick-doc-name">Applicant Photograph</div>
              <?= $renderQuickStatus($quickDocs['photo'], 'Photo Not Uploaded') ?>
            </div>
            <div>
              <?php if (!empty($quickDocs['photo'])): ?>
                <?php $pExt = strtolower(pathinfo($quickDocs['photo']['file_name'] ?? 'photo.jpg', PATHINFO_EXTENSION)) ?: 'png'; ?>
                <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" onclick="openDocumentPreview(<?= (int)$quickDocs['photo']['id'] ?>, 'Applicant Photograph', '<?= e($pExt) ?>', '<?= e($quickDocs['photo']['status']) ?>', <?= (int)$quickDocs['photo']['version'] ?>)" title="Preview Photo">
                  <i class="fa-solid fa-eye"></i>
                </button>
              <?php else: ?>
                <button type="button" class="btn btn-sm btn-primary py-1 px-2" data-bs-toggle="modal" data-bs-target="#changePhotoModal" title="Upload Photo">
                  <i class="fa-solid fa-plus"></i>
                </button>
              <?php endif; ?>
            </div>
          </div>

          <!-- Quick Card: Passport -->
          <div class="quick-doc-card">
            <div class="quick-doc-icon-box text-danger">
              <i class="fa-solid fa-passport"></i>
            </div>
            <div class="quick-doc-details">
              <div class="quick-doc-name">Passport Bio Page</div>
              <?= $renderQuickStatus($quickDocs['passport'], 'Passport Not Uploaded') ?>
            </div>
            <div>
              <?php if (!empty($quickDocs['passport'])): ?>
                <?php $passExt = strtolower(pathinfo($quickDocs['passport']['file_name'] ?? 'doc.pdf', PATHINFO_EXTENSION)) ?: 'pdf'; ?>
                <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" onclick="openDocumentPreview(<?= (int)$quickDocs['passport']['id'] ?>, 'Passport Bio Page', '<?= e($passExt) ?>', '<?= e($quickDocs['passport']['status']) ?>', <?= (int)$quickDocs['passport']['version'] ?>)" title="Preview Passport">
                  <i class="fa-solid fa-eye"></i>
                </button>
              <?php else: ?>
                <button type="button" class="btn btn-sm btn-primary py-1 px-2" onclick="openUploadModalWithType(1)" title="Upload Passport">
                  <i class="fa-solid fa-plus"></i>
                </button>
              <?php endif; ?>
            </div>
          </div>

          <!-- Quick Card: CV -->
          <div class="quick-doc-card">
            <div class="quick-doc-icon-box text-info">
              <i class="fa-solid fa-file-lines"></i>
            </div>
            <div class="quick-doc-details">
              <div class="quick-doc-name">Curriculum Vitae (CV)</div>
              <?= $renderQuickStatus($quickDocs['cv'], 'CV Not Uploaded') ?>
            </div>
            <div>
              <?php if (!empty($quickDocs['cv'])): ?>
                <?php $cvExt = strtolower(pathinfo($quickDocs['cv']['file_name'] ?? 'cv.pdf', PATHINFO_EXTENSION)) ?: 'pdf'; ?>
                <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" onclick="openDocumentPreview(<?= (int)$quickDocs['cv']['id'] ?>, 'Curriculum Vitae', '<?= e($cvExt) ?>', '<?= e($quickDocs['cv']['status']) ?>', <?= (int)$quickDocs['cv']['version'] ?>)" title="Preview CV">
                  <i class="fa-solid fa-eye"></i>
                </button>
              <?php else: ?>
                <button type="button" class="btn btn-sm btn-light border py-1 px-2" onclick="openUploadModalWithKeyword('cv')" title="Upload CV">
                  <i class="fa-solid fa-plus"></i>
                </button>
              <?php endif; ?>
            </div>
          </div>

          <!-- Quick Card: Visa Copy -->
          <div class="quick-doc-card">
            <div class="quick-doc-icon-box text-success">
              <i class="fa-solid fa-stamp"></i>
            </div>
            <div class="quick-doc-details">
              <div class="quick-doc-name">Visa / Entry Permit Copy</div>
              <?= $renderQuickStatus($quickDocs['visa'], 'Visa Document Pending') ?>
            </div>
            <div>
              <?php if (!empty($quickDocs['visa'])): ?>
                <?php $vExt = strtolower(pathinfo($quickDocs['visa']['file_name'] ?? 'visa.pdf', PATHINFO_EXTENSION)) ?: 'pdf'; ?>
                <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" onclick="openDocumentPreview(<?= (int)$quickDocs['visa']['id'] ?>, 'Visa Copy', '<?= e($vExt) ?>', '<?= e($quickDocs['visa']['status']) ?>', <?= (int)$quickDocs['visa']['version'] ?>)" title="Preview Visa">
                  <i class="fa-solid fa-eye"></i>
                </button>
              <?php else: ?>
                <button type="button" class="btn btn-sm btn-light border py-1 px-2" onclick="openUploadModalWithKeyword('visa')" title="Upload Visa">
                  <i class="fa-solid fa-plus"></i>
                </button>
              <?php endif; ?>
            </div>
          </div>

          <!-- Quick Card: National ID -->
          <div class="quick-doc-card">
            <div class="quick-doc-icon-box text-secondary">
              <i class="fa-solid fa-id-card"></i>
            </div>
            <div class="quick-doc-details">
              <div class="quick-doc-name">National ID / Emirates ID</div>
              <?= $renderQuickStatus($quickDocs['national_id'], 'ID Document Pending') ?>
            </div>
            <div>
              <?php if (!empty($quickDocs['national_id'])): ?>
                <?php $nidExt = strtolower(pathinfo($quickDocs['national_id']['file_name'] ?? 'id.pdf', PATHINFO_EXTENSION)) ?: 'pdf'; ?>
                <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" onclick="openDocumentPreview(<?= (int)$quickDocs['national_id']['id'] ?>, 'National ID', '<?= e($nidExt) ?>', '<?= e($quickDocs['national_id']['status']) ?>', <?= (int)$quickDocs['national_id']['version'] ?>)" title="Preview ID">
                  <i class="fa-solid fa-eye"></i>
                </button>
              <?php else: ?>
                <button type="button" class="btn btn-sm btn-light border py-1 px-2" onclick="openUploadModalWithType(4)" title="Upload National ID">
                  <i class="fa-solid fa-plus"></i>
                </button>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </div>

      <!-- 2. Quick Actions Panel (Requirement 27) -->
      <div class="workspace-card mb-3">
        <div class="workspace-card-header">
          <h6 class="workspace-card-title">
            <i class="fa-solid fa-gear text-primary"></i> Workspace Actions
          </h6>
        </div>
        <div class="workspace-card-body p-3">
          <button type="button" class="action-panel-btn btn-primary-action shadow-sm" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <span>+ Upload Document</span>
          </button>

          <button type="button" class="action-panel-btn" data-bs-toggle="modal" data-bs-target="#requestDocModal">
            <i class="fa-solid fa-paper-plane text-warning"></i>
            <span>Request Document</span>
          </button>

          <a href="/documents/download-all?application_id=<?= (int)$app['id'] ?>" class="action-panel-btn">
            <i class="fa-solid fa-file-zipper text-primary"></i>
            <span>Download All Documents (ZIP)</span>
          </a>

          <a href="/customers/show?id=<?= (int)$app['customer_id'] ?>" class="action-panel-btn">
            <i class="fa-solid fa-user-circle text-secondary"></i>
            <span>Full Customer Profile</span>
          </a>

          <a href="/applications/show?id=<?= (int)$app['id'] ?>" class="action-panel-btn">
            <i class="fa-solid fa-file-lines text-secondary"></i>
            <span>View Application</span>
          </a>

          <a href="/applications/edit?id=<?= (int)$app['id'] ?>" class="action-panel-btn">
            <i class="fa-solid fa-pen-to-square text-secondary"></i>
            <span>Edit Application Details</span>
          </a>

          <?php if (!empty($app['whatsapp'])): ?>
            <?php
              $waClean = preg_replace('/[^0-9]/', '', (string)$app['whatsapp']);
              $waMsg = urlencode("Hello " . $app['customer_name'] . ", regarding your visa application " . $app['application_number'] . " at " . \App\Config\App::COMPANY_NAME . ":");
            ?>
            <a href="https://wa.me/<?= e($waClean) ?>?text=<?= $waMsg ?>" target="_blank" class="action-panel-btn btn-whatsapp-action shadow-sm">
              <i class="fa-brands fa-whatsapp"></i>
              <span>WhatsApp Message</span>
            </a>
          <?php endif; ?>

          <?php if (!empty($app['email'])): ?>
            <a href="mailto:<?= e($app['email']) ?>?subject=<?= urlencode('Visa Application ' . $app['application_number'] . ' — ' . \App\Config\App::COMPANY_NAME) ?>" class="action-panel-btn">
              <i class="fa-solid fa-envelope text-info"></i>
              <span>Send Email</span>
            </a>
          <?php endif; ?>
        </div>
      </div>

    </div>

  </div>
</div>

<!-- ========================================================================
     MODALS SECTION
     ======================================================================== -->

<!-- 1. Interactive Document Preview Modal (Requirements 18 & 19) -->
<div class="modal fade" id="docPreviewModal" tabindex="-1" aria-labelledby="docPreviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-dark text-white py-2.5 px-3">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid fa-file text-warning fs-5" id="previewIcon"></i>
          <div>
            <h6 class="modal-title fw-bold mb-0 text-white" id="previewTitle">Document Preview</h6>
            <div class="text-white-50" style="font-size: 0.72rem;">
              <span id="previewMeta">Loading document stream...</span>
            </div>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="#" id="previewDownloadBtn" class="btn btn-outline-light btn-sm py-1 px-2.5" title="Download Document">
            <i class="fa-solid fa-download me-1"></i> Download
          </a>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>
      <div class="modal-body p-0 bg-dark position-relative">
        <div class="modal-doc-viewer-container" id="previewContainer">
          <div class="spinner-border text-light" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
        
        <!-- Viewer Toolbar for Zoom & Rotation on Images -->
        <div class="viewer-toolbar" id="imageToolbar" style="display: none;">
          <button type="button" class="viewer-toolbar-btn" onclick="zoomImage(1.2)" title="Zoom In"><i class="fa-solid fa-magnifying-glass-plus"></i></button>
          <button type="button" class="viewer-toolbar-btn" onclick="zoomImage(0.8)" title="Zoom Out"><i class="fa-solid fa-magnifying-glass-minus"></i></button>
          <button type="button" class="viewer-toolbar-btn" onclick="rotateImage(90)" title="Rotate Right"><i class="fa-solid fa-rotate-right"></i></button>
          <button type="button" class="viewer-toolbar-btn" onclick="resetImageView()" title="Reset View"><i class="fa-solid fa-compress"></i></button>
        </div>
      </div>
      <div class="modal-footer bg-light py-2 px-3 justify-content-between">
        <div class="small text-muted" id="previewSecurityNote">
          <i class="fa-solid fa-shield-halved text-success me-1"></i> Secure authenticated stream
        </div>
        <div class="d-flex align-items-center gap-2" id="previewActionButtons">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- 2. Document Upload Modal (Requirement 21) -->
<div class="modal fade" id="uploadDocModal" tabindex="-1" aria-labelledby="uploadDocModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-primary text-white py-3">
        <h6 class="modal-title fw-bold text-white mb-0">
          <i class="fa-solid fa-cloud-arrow-up me-2"></i> Upload Document for <?= e($app['customer_name']) ?>
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/documents/upload" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <!-- Automatically locked to this application and customer (Requirement 21) -->
        <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
        
        <div class="modal-body p-4">
          <div class="alert alert-info py-2 px-3 small mb-3 border-0">
            <i class="fa-solid fa-lock text-primary me-1"></i> Document will be bound to <strong><?= e($app['customer_name']) ?></strong> (<?= e($app['application_number']) ?>).
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Document Type <span class="text-danger">*</span></label>
            <select name="document_type_id" id="uploadDocTypeSelect" class="form-select" required>
              <option value="">-- Select Document Type --</option>
              <?php foreach ($docTypes as $dt): ?>
                <option value="<?= $dt['id'] ?>" data-expiry="<?= $dt['requires_expiry'] ?>" data-code="<?= e($dt['code']) ?>">
                  <?= e($dt['category']) ?> &mdash; <?= e($dt['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Document Title / Description</label>
            <input type="text" name="document_title" id="uploadDocTitleInput" class="form-control" placeholder="e.g. Passport Bio Page Scan">
            <div class="form-text small" style="font-size: 0.72rem;">Leave empty to use default document type name.</div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Select File <span class="text-danger">*</span></label>
            <input type="file" name="document_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp,.docx,.doc" required>
            <div class="form-text small" style="font-size: 0.72rem;">Supported: PDF, JPG, PNG, WEBP, DOCX (Max 10MB).</div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Document Expiry Date</label>
            <input type="date" name="expiry_date" class="form-control">
          </div>

          <div class="mb-2">
            <label class="form-label small fw-semibold">Internal Notes</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes for operations review"></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light py-2 px-3">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
            <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload File
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 3. Change Profile Photo Modal (Requirement 22) -->
<div class="modal fade" id="changePhotoModal" tabindex="-1" aria-labelledby="changePhotoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-dark text-white py-3">
        <h6 class="modal-title fw-bold text-white mb-0">
          <i class="fa-solid fa-camera me-2 text-warning"></i> Update Applicant Profile Photo
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/documents/upload" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
        
        <?php
          // Look up document_type_id for PHOTO_WHITE_BG
          $photoTypeId = 3;
          foreach ($docTypes as $dt) {
              if ($dt['code'] === 'PHOTO_WHITE_BG') {
                  $photoTypeId = (int)$dt['id'];
                  break;
              }
          }
        ?>
        <input type="hidden" name="document_type_id" value="<?= $photoTypeId ?>">
        <input type="hidden" name="document_title" value="Passport Size Photograph (White Background)">
        <input type="hidden" name="notes" value="Profile photo updated via Workspace">

        <div class="modal-body p-4 text-center">
          <p class="text-muted small mb-3">
            Upload recent passport-sized photograph (35x45mm or 2x2in) with clear white background and neutral expression.
          </p>

          <div class="profile-photo-container mx-auto mb-3" style="width: 150px; height: 185px;">
            <img id="photoPreviewImg" src="<?= !empty($quickDocs['photo']) ? '/documents/preview?id=' . (int)$quickDocs['photo']['id'] : '' ?>" 
                 alt="Preview" 
                 class="profile-photo-img" 
                 style="<?= empty($quickDocs['photo']) ? 'display:none;' : '' ?>">
            <div id="photoPreviewPlaceholder" class="profile-avatar-placeholder" style="<?= !empty($quickDocs['photo']) ? 'display:none;' : '' ?>">
              <i class="fa-solid fa-user text-white opacity-75 fs-1"></i>
              <span class="small mt-2">Selected Photo</span>
            </div>
          </div>

          <div class="mb-3 text-start">
            <label class="form-label small fw-semibold">Choose Image File (JPG, PNG, WEBP) <span class="text-danger">*</span></label>
            <input type="file" name="document_file" id="photoFileInput" class="form-control" accept="image/jpeg,image/png,image/webp" required onchange="handlePhotoPreview(this)">
            <div class="form-text small" style="font-size: 0.72rem;">Maximum allowed size: 5MB. Must be valid image.</div>
          </div>
        </div>
        <div class="modal-footer bg-light py-2 px-3">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
            <i class="fa-solid fa-cloud-arrow-up me-1"></i> Save &amp; Set as Photo
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 4. Version History Modal (Requirement 20) -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-dark text-white py-2.5 px-3">
        <h6 class="modal-title fw-bold text-white mb-0">
          <i class="fa-solid fa-clock-rotate-left text-info me-2"></i> Document Version History
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3">
        <div id="historyLoading" class="text-center py-4">
          <div class="spinner-border text-primary" role="status"></div>
          <div class="text-muted small mt-2">Loading version archive...</div>
        </div>
        <div id="historyContent" style="display: none;">
          <table class="table table-sm table-bordered align-middle">
            <thead class="table-light small">
              <tr>
                <th>Version</th>
                <th>File Name</th>
                <th>Uploaded By</th>
                <th>Date &amp; Time</th>
                <th>Size</th>
              </tr>
            </thead>
            <tbody id="historyTableBody" class="small"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer bg-light py-2 px-3">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- 5. Reject Document Modal -->
<div class="modal fade" id="rejectDocModal" tabindex="-1" aria-labelledby="rejectDocModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-danger text-white py-3">
        <h6 class="modal-title fw-bold text-white mb-0">
          <i class="fa-solid fa-triangle-exclamation me-2"></i> Reject Document
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/documents/reject" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="document_id" id="rejectDocIdInput" value="">
        <div class="modal-body p-4">
          <p class="text-muted small mb-3">
            Please provide an explicit rejection reason. The applicant and staff will be notified to upload a compliant replacement.
          </p>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Rejection Reason <span class="text-danger">*</span></label>
            <select name="rejection_reason" class="form-select" required>
              <option value="">-- Choose Reason --</option>
              <option value="Blurry or Unreadable Scan">Blurry or Unreadable Scan</option>
              <option value="Cutoff Borders / Incomplete Document">Cutoff Borders / Incomplete Document</option>
              <option value="Expired Document">Expired Document</option>
              <option value="Incorrect Document Type">Incorrect Document Type</option>
              <option value="Photograph Background Not White">Photograph Background Not White</option>
              <option value="Missing Required Stamp / Signature">Missing Required Stamp / Signature</option>
              <option value="Name Mismatch with Passport">Name Mismatch with Passport</option>
              <option value="Other Non-Compliance">Other Non-Compliance</option>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label small fw-semibold">Detailed Instructions for Applicant</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Explain what the applicant must do to correct this file"></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light py-2 px-3">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger btn-sm px-4 fw-semibold shadow-sm">
            <i class="fa-solid fa-ban me-1"></i> Confirm Rejection
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 6. Request Document Modal (Requirement 25) -->
<div class="modal fade" id="requestDocModal" tabindex="-1" aria-labelledby="requestDocModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-warning text-dark py-3">
        <h6 class="modal-title fw-bold mb-0">
          <i class="fa-solid fa-paper-plane me-2"></i> Request Document from Applicant
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/documents/request" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
        
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Requested Document Type <span class="text-danger">*</span></label>
            <select name="document_type_id" id="requestDocTypeSelect" class="form-select" required>
              <option value="">-- Choose Document to Request --</option>
              <?php foreach ($docTypes as $dt): ?>
                <option value="<?= $dt['id'] ?>">
                  <?= e($dt['name']) ?> (<?= e($dt['category']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label small fw-semibold">Instructions / Notes for Applicant</label>
            <textarea name="notes" id="requestDocNotes" class="form-control" rows="3" placeholder="Please upload a high-resolution colored scan..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light py-2 px-3">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning text-dark btn-sm px-4 fw-semibold shadow-sm">
            <i class="fa-solid fa-paper-plane me-1"></i> Send Request
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========================================================================
     CLIENT-SIDE INTERACTIVE VIEWER & MODAL SCRIPT
     ======================================================================== -->
<script>
let currentZoom = 1;
let currentRotation = 0;

function openDocumentPreview(docId, docTitle, ext, status, version) {
  const modal = new bootstrap.Modal(document.getElementById('docPreviewModal'));
  document.getElementById('previewTitle').textContent = docTitle;
  document.getElementById('previewMeta').textContent = `v${version} • Status: ${status} • Format: .${ext.toUpperCase()}`;
  document.getElementById('previewDownloadBtn').href = `/documents/download?id=${docId}`;

  const container = document.getElementById('previewContainer');
  const toolbar = document.getElementById('imageToolbar');
  currentZoom = 1;
  currentRotation = 0;

  if (['jpg', 'jpeg', 'png', 'webp'].includes(ext.toLowerCase())) {
    toolbar.style.display = 'flex';
    container.innerHTML = `<img id="activePreviewImg" src="/documents/preview?id=${docId}" alt="${docTitle}" style="transform: scale(1) rotate(0deg);">`;
  } else if (ext.toLowerCase() === 'pdf') {
    toolbar.style.display = 'none';
    container.innerHTML = `<iframe src="/documents/preview?id=${docId}" style="width: 100%; height: 75vh; border: none;"></iframe>`;
  } else {
    toolbar.style.display = 'none';
    container.innerHTML = `
      <div class="text-center p-5 text-white">
        <i class="fa-solid fa-file-word fs-1 text-info mb-3"></i>
        <h6 class="fw-bold">${docTitle}</h6>
        <p class="text-white-50 small mb-3">This file format (.${ext.toUpperCase()}) cannot be rendered directly inline.</p>
        <a href="/documents/download?id=${docId}" class="btn btn-primary btn-sm px-4">
          <i class="fa-solid fa-download me-1"></i> Download File (${ext.toUpperCase()})
        </a>
      </div>
    `;
  }

  modal.show();
}

function zoomImage(factor) {
  const img = document.getElementById('activePreviewImg');
  if (!img) return;
  currentZoom *= factor;
  if (currentZoom < 0.2) currentZoom = 0.2;
  if (currentZoom > 4) currentZoom = 4;
  applyImageTransform(img);
}

function rotateImage(deg) {
  const img = document.getElementById('activePreviewImg');
  if (!img) return;
  currentRotation = (currentRotation + deg) % 360;
  applyImageTransform(img);
}

function resetImageView() {
  const img = document.getElementById('activePreviewImg');
  if (!img) return;
  currentZoom = 1;
  currentRotation = 0;
  applyImageTransform(img);
}

function applyImageTransform(img) {
  img.style.transform = `scale(${currentZoom}) rotate(${currentRotation}deg)`;
}

function handlePhotoPreview(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const img = document.getElementById('photoPreviewImg');
      const placeholder = document.getElementById('photoPreviewPlaceholder');
      img.src = e.target.result;
      img.style.display = 'block';
      placeholder.style.display = 'none';
    }
    reader.readAsDataURL(input.files[0]);
  }
}

function openUploadModalWithType(typeId) {
  const select = document.getElementById('uploadDocTypeSelect');
  if (select) {
    select.value = typeId;
  }
  const modal = new bootstrap.Modal(document.getElementById('uploadDocModal'));
  modal.show();
}

function openUploadModalWithKeyword(keyword) {
  const select = document.getElementById('uploadDocTypeSelect');
  if (select) {
    for (let opt of select.options) {
      if (opt.text.toLowerCase().includes(keyword.toLowerCase())) {
        select.value = opt.value;
        break;
      }
    }
  }
  const modal = new bootstrap.Modal(document.getElementById('uploadDocModal'));
  modal.show();
}

function openRejectModal(docId) {
  document.getElementById('rejectDocIdInput').value = docId;
  const modal = new bootstrap.Modal(document.getElementById('rejectDocModal'));
  modal.show();
}

function openRequestDocModal(typeId, typeName) {
  const select = document.getElementById('requestDocTypeSelect');
  if (select) select.value = typeId;
  const notes = document.getElementById('requestDocNotes');
  if (notes) notes.value = `Please upload your ${typeName} as soon as possible for visa processing.`;
  const modal = new bootstrap.Modal(document.getElementById('requestDocModal'));
  modal.show();
}

function openHistoryModal(docId) {
  const loading = document.getElementById('historyLoading');
  const content = document.getElementById('historyContent');
  const tbody = document.getElementById('historyTableBody');
  loading.style.display = 'block';
  content.style.display = 'none';
  tbody.innerHTML = '';

  const modal = new bootstrap.Modal(document.getElementById('historyModal'));
  modal.show();

  fetch(`/documents/history?id=${docId}`, {
    headers: { 'Accept': 'application/json' }
  })
  .then(res => res.json())
  .then(data => {
    loading.style.display = 'none';
    content.style.display = 'block';

    if (data.success && data.data && data.data.length > 0) {
      data.data.forEach(v => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td><span class="badge bg-secondary font-monospace">v${v.version_number}</span></td>
          <td class="fw-semibold">${v.file_name}</td>
          <td>${v.uploader_name || 'Staff'}</td>
          <td class="text-muted">${v.created_at || '—'}</td>
          <td>${(v.file_size ? (v.file_size / 1024).toFixed(1) + ' KB' : '—')}</td>
        `;
        tbody.appendChild(tr);
      });
    } else {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No previous archived versions found. This is currently Version 1.</td></tr>';
    }
  })
  .catch(err => {
    loading.style.display = 'none';
    content.style.display = 'block';
    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-3">Failed to load history archive.</td></tr>';
  });
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
