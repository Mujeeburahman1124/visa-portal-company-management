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

<link rel="stylesheet" href="/assets/css/dashboard-bento.css?v=2.4">
<link rel="stylesheet" href="/assets/css/pages/documents.css?v=2.4">

<div class="content-body">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'warning')) ?> alert-dismissible fade show mb-4 rounded-4 shadow-xs" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation text-danger' : ($flash['type'] === 'success' ? 'fa-circle-check text-success' : 'fa-triangle-exclamation text-warning') ?>"></i>
        <span class="fw-medium"><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- ─── 1. TOP BREADCRUMB & MULTI-APP SWITCHER BAR ────────────────────── -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <div class="d-flex align-items-center gap-2">
      <a href="/documents" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-xs bg-white" title="Return to Document Directory">
        <i class="fa-solid fa-arrow-left me-1.5"></i> Back to Documents
      </a>
      <span class="text-muted small">/</span>
      <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 font-monospace">
        <?= e($app['application_number']) ?>
      </span>
    </div>

    <!-- Multi-Application Switcher Chips (Requirement 42) -->
    <?php if (count($customerApplications) > 1): ?>
      <div class="d-flex align-items-center gap-1.5 flex-wrap">
        <span class="text-muted small fw-bold me-1"><i class="fa-solid fa-folder-tree me-1 text-primary"></i>Customer Applications:</span>
        <?php foreach ($customerApplications as $otherApp): ?>
          <a href="/documents/profile?application_id=<?= (int)$otherApp['id'] ?>" 
             class="btn btn-xs rounded-pill px-2.5 py-1 text-decoration-none <?= ((int)$otherApp['id'] === (int)$app['id']) ? 'btn-primary text-white' : 'btn-light border text-muted' ?>">
            <i class="fa-solid fa-file-invoice me-1"></i>
            <span><?= e($otherApp['application_number']) ?></span>
            <span class="opacity-75">(<?= e($otherApp['status']) ?>)</span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php
    $statusBadge = 'bg-secondary';
    if ($app['status'] === 'Approved') $statusBadge = 'bg-success';
    elseif ($app['status'] === 'Pending') $statusBadge = 'bg-warning text-dark';
    elseif ($app['status'] === 'Rejected') $statusBadge = 'bg-danger';
    elseif ($app['status'] === 'In Process') $statusBadge = 'bg-primary';
    elseif ($app['status'] === 'Draft') $statusBadge = 'bg-info text-dark';

    $nameParts = explode(' ', trim((string)($app['customer_name'] ?? '')));
    $firstInitial = !empty($nameParts[0]) ? mb_substr($nameParts[0], 0, 1) : 'A';
    $lastInitial = count($nameParts) > 1 ? mb_substr(end($nameParts), 0, 1) : '';
    $initials = strtoupper($firstInitial . $lastInitial) ?: 'AP';
  ?>

  <!-- ─── 2. INSTAGRAM / TIKTOK EXECUTIVE SOCIAL PROFILE CARD ─────────────── -->
  <div class="social-profile-card">
    <!-- Profile Cover Canvas with MS Travel Hub Brand Gradient -->
    <div class="social-profile-cover">
      <div class="d-flex align-items-center gap-2">
        <span class="badge rounded-pill px-3 py-1.5 text-white fw-bold shadow-xs" style="background: rgba(255, 255, 255, 0.18); backdrop-filter: blur(8px); border: 1px solid rgba(255, 255, 255, 0.25);">
          <i class="fa-solid fa-passport me-1.5 text-warning"></i> <?= e($app['service_name'] ?: 'Visa Service') ?>
        </span>
        <span class="badge rounded-pill px-2.5 py-1.5 text-white" style="background: rgba(0, 0, 0, 0.25); backdrop-filter: blur(6px);">
          <?= e($app['flag_emoji'] ?? '🌐') ?> <?= e($app['destination_country_name'] ?? $app['destination_country'] ?? 'UAE') ?>
        </span>
      </div>

      <div class="d-flex align-items-center gap-2">
        <span class="badge <?= $statusBadge ?> px-3 py-1.5 rounded-pill fw-bold shadow-xs">
          <?= strtoupper(e($app['status'])) ?>
        </span>
        <a href="/documents/download-all?application_id=<?= (int)$app['id'] ?>" class="btn btn-sm btn-light rounded-pill px-3 shadow-xs" title="Download all documents in organized ZIP bundle">
          <i class="fa-solid fa-file-zipper me-1 text-primary"></i> ZIP
        </a>
      </div>
    </div>

    <!-- Profile Inner Header & Social Identity -->
    <div class="social-profile-header">
      <!-- Avatar Row with Instagram/TikTok Story Gradient Ring & Actions -->
      <div class="social-avatar-row">
        <!-- Avatar Wrapper -->
        <div class="social-avatar-wrapper">
          <div class="social-avatar-ring">
            <div class="social-avatar-inner">
              <?php if (!empty($quickDocs['photo']['id'])): ?>
                <img src="/documents/preview?id=<?= (int)$quickDocs['photo']['id'] ?>" 
                     alt="<?= e($app['customer_name']) ?>" 
                     id="applicantProfilePhotoImg"
                     loading="lazy"
                     onerror="this.style.display='none'; document.getElementById('applicantPhotoFallbackAvatar').style.display='flex';">
                <div id="applicantPhotoFallbackAvatar" style="display:none; width: 100%; height: 100%; align-items: center; justify-content: center; background: #0F172A; color: #FFFFFF;">
                  <?= e($initials) ?>
                </div>
              <?php else: ?>
                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #0F172A; color: #FFFFFF;">
                  <?= e($initials) ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
          <!-- Verified Checkmark Badge -->
          <div class="social-verified-badge" title="Verified Applicant Profile">
            <i class="fa-solid fa-check"></i>
          </div>
          <!-- Camera / Edit Profile Photo Circle Button -->
          <button type="button" class="btn btn-sm btn-dark position-absolute bottom-0 end-0 rounded-circle shadow-sm" 
                  style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; border: 2.5px solid #FFFFFF; z-index: 5;" 
                  data-bs-toggle="modal" data-bs-target="#changePhotoModal" title="Upload or Change Photograph">
            <i class="fa-solid fa-camera" style="font-size: 0.75rem;"></i>
          </button>
        </div>

        <!-- Social Action Buttons Bar (Instagram-style Contact / Action Strip) -->
        <div class="social-actions-bar">
          <?php if (!empty($app['whatsapp'])): ?>
            <?php 
              $waClean = preg_replace('/[^0-9]/', '', (string)$app['whatsapp']); 
              $waMsg = urlencode("Hello " . $app['customer_name'] . ", regarding your visa application " . $app['application_number'] . " at MS Travel Hub Global Visa Management:");
            ?>
            <a href="https://wa.me/<?= e($waClean) ?>?text=<?= $waMsg ?>" target="_blank" 
               class="btn btn-sm btn-outline-success rounded-pill px-3 shadow-xs fw-semibold" title="Direct WhatsApp Chat">
              <i class="fa-brands fa-whatsapp me-1.5 fs-6"></i> WhatsApp
            </a>
          <?php endif; ?>

          <?php if (!empty($app['mobile'])): ?>
            <a href="tel:<?= e($app['mobile']) ?>" 
               class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-xs fw-semibold" title="Direct Phone Call">
              <i class="fa-solid fa-phone me-1.5"></i> Call
            </a>
          <?php endif; ?>

          <button type="button" class="btn btn-sm text-white rounded-pill px-3.5 shadow-xs fw-semibold" 
                  style="background: var(--bento-primary, #E11D48); border: none;" 
                  data-bs-toggle="modal" data-bs-target="#uploadDocModal">
            <i class="fa-solid fa-cloud-arrow-up me-1.5"></i> + Upload Document
          </button>

          <a href="/customers/show?id=<?= (int)$app['customer_id'] ?>" 
             class="btn btn-sm btn-light border rounded-pill px-3 shadow-xs text-secondary fw-semibold" title="Customer CRM Master Case">
            <i class="fa-solid fa-user-gear me-1.5"></i> CRM Profile
          </a>
        </div>
      </div>

      <!-- Identity Typography & Social Bio -->
      <div>
        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
          <h2 class="social-name mb-0"><?= e($app['customer_name']) ?></h2>
          <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1" style="font-size: 0.72rem;">
            <i class="fa-solid fa-certificate me-1"></i> Verified Applicant
          </span>
        </div>

        <div class="social-handle mb-2">
          <span class="badge bg-light text-dark border font-monospace px-2 py-0.5">@<?= e($app['customer_code'] ?? 'MSC-000000') ?></span>
          <span class="badge bg-light text-muted border font-monospace px-2 py-0.5"><i class="fa-solid fa-hashtag me-0.5"></i><?= e($app['application_number']) ?></span>
          <span class="badge bg-light text-primary border px-2 py-0.5"><i class="fa-solid fa-timeline me-1"></i><?= e($app['current_stage']) ?></span>
          <span class="badge bg-light text-secondary border px-2 py-0.5"><i class="fa-solid fa-building me-1"></i><?= e($app['branch_name'] ?? 'Main Branch') ?></span>
          <?php if (!empty($app['assigned_staff_name'])): ?>
            <span class="badge bg-light text-dark border px-2 py-0.5"><i class="fa-solid fa-user-tie text-secondary me-1"></i><?= e($app['assigned_staff_name']) ?></span>
          <?php endif; ?>
        </div>

        <!-- Social Bio Description Box -->
        <div class="social-bio-text">
          <span>💼 <strong><?= e($app['occupation'] ?: 'Applicant') ?></strong></span>
          <span class="text-muted mx-1.5">&bull;</span>
          <span>✈️ Service: <strong><?= e($app['service_name'] ?: 'Standard Visa') ?></strong> (<?= e($app['flag_emoji'] ?? '🌐') ?> <?= e($app['destination_country_name'] ?? 'UAE') ?>)</span>
          <span class="text-muted mx-1.5">&bull;</span>
          <span>🛂 Passport: <strong class="font-monospace text-primary"><?= e($app['passport_number'] ?: '—') ?></strong> (<?= e($passportValidity['label']) ?>)</span>
          <span class="text-muted mx-1.5">&bull;</span>
          <span>🎂 Age: <strong><?= $age !== null ? $age . ' yrs' : '—' ?></strong> (<?= e($app['nationality'] ?: 'National') ?>)</span>
          <?php if (!empty($app['current_country'])): ?>
            <span class="text-muted mx-1.5">&bull;</span>
            <span>📍 Residing in: <strong><?= e($app['current_country']) ?></strong></span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Social Media Stats Strip (Posts / Followers / Following Style adapted to Visa Portal) -->
      <div class="social-stats-strip">
        <div class="social-stat-item">
          <span class="social-stat-num text-primary"><?= count($applicationDocuments) ?></span>
          <span class="social-stat-label">Documents Vault</span>
        </div>
        <div class="social-stat-item">
          <span class="social-stat-num text-success"><?= (int)$checklist['percentage'] ?>%</span>
          <span class="social-stat-label">KYC Readiness</span>
        </div>
        <div class="social-stat-item">
          <span class="social-stat-num text-success"><?= (int)$checklist['total_verified'] ?></span>
          <span class="social-stat-label">Verified &amp; Stamped</span>
        </div>
        <div class="social-stat-item">
          <span class="social-stat-num <?= (int)$checklist['total_missing'] + (int)$checklist['total_rejected'] > 0 ? 'text-danger' : 'text-muted' ?>">
            <?= (int)$checklist['total_missing'] + (int)$checklist['total_rejected'] ?>
          </span>
          <span class="social-stat-label">Action Required</span>
        </div>
        <div class="social-stat-item">
          <span class="social-stat-num" style="color: #D97706;">
            <?= !empty($passportValidity['days_remaining']) && $passportValidity['days_remaining'] > 0 ? round($passportValidity['days_remaining'] / 365, 1) . ' Yrs' : e($passportValidity['label']) ?>
          </span>
          <span class="social-stat-label">Passport Validity</span>
        </div>
      </div>

      <!-- Instagram / TikTok Social Tabs Navigation Bar -->
      <div class="social-tabs-nav" role="tablist">
        <button type="button" class="social-tab-btn active" data-tab="tab-docs">
          <i class="fa-solid fa-folder-open"></i> Documents Vault 
          <span class="badge bg-light text-dark border rounded-pill ms-1"><?= count($applicationDocuments) ?></span>
        </button>
        <button type="button" class="social-tab-btn" data-tab="tab-personal">
          <i class="fa-solid fa-address-card"></i> Personal &amp; Passport
        </button>
        <button type="button" class="social-tab-btn" data-tab="tab-visa">
          <i class="fa-solid fa-plane-departure"></i> Visa Application
        </button>
        <button type="button" class="social-tab-btn" data-tab="tab-checklist">
          <i class="fa-solid fa-clipboard-check"></i> Requirements Checklist
          <?php if ((int)$checklist['total_missing'] + (int)$checklist['total_rejected'] > 0): ?>
            <span class="badge bg-danger text-white rounded-pill ms-1"><?= (int)$checklist['total_missing'] + (int)$checklist['total_rejected'] ?></span>
          <?php endif; ?>
        </button>
        <button type="button" class="social-tab-btn" data-tab="tab-activity">
          <i class="fa-solid fa-clock-rotate-left"></i> Activity Timeline
        </button>
      </div>
    </div>
  </div>

  <!-- ─── 3. TABBED DASHBOARD BENTO PANES ─────────────────────────────────── -->

  <!-- ======================================================================
       TAB PANE 1: DOCUMENTS VAULT (MAIN VAULT & QUICK ACCESS CARDS)
       ====================================================================== -->
  <div id="tab-docs" class="profile-tab-pane">
    <!-- Pinned Quick Access Bento Cards (5 Columns) -->
    <div class="row g-3 mb-4">
      <?php
        $renderQuickBentoCard = function($title, $iconClass, $doc, $typeId, $keyword, $colorHex) use ($app) {
          $isUploaded = !empty($doc);
          $status = $doc['status'] ?? 'PENDING';
          $stBadge = 'bg-secondary';
          if ($status === 'VERIFIED') $stBadge = 'bg-success';
          elseif ($status === 'UNDER_REVIEW') $stBadge = 'bg-warning text-dark';
          elseif ($status === 'REJECTED') $stBadge = 'bg-danger';

          $ext = $isUploaded ? strtolower(pathinfo($doc['file_name'] ?? '', PATHINFO_EXTENSION)) : '';
          ?>
          <div class="col-6 col-md-4 col-xl">
            <div class="bento-card h-100 p-3 bg-white" style="border-top: 3px solid <?= $colorHex ?>;">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                  <div style="width: 34px; height: 34px; border-radius: 10px; background: <?= $colorHex ?>15; color: <?= $colorHex ?>; display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                    <i class="fa-solid <?= $iconClass ?>"></i>
                  </div>
                  <span class="fw-bold small text-dark"><?= e($title) ?></span>
                </div>
              </div>

              <div class="mb-3">
                <?php if ($isUploaded): ?>
                  <div class="d-flex align-items-center gap-1.5 flex-wrap">
                    <span class="badge <?= $stBadge ?> px-2 py-0.5" style="font-size: 0.68rem;"><?= e($status) ?></span>
                    <span class="badge bg-light text-muted border px-1.5 py-0.5 font-monospace" style="font-size: 0.65rem;">v<?= (int)($doc['version'] ?? 1) ?></span>
                  </div>
                  <div class="text-truncate text-muted small mt-1 font-monospace" style="font-size: 0.72rem;" title="<?= e($doc['file_name']) ?>">
                    <?= e($doc['file_name']) ?>
                  </div>
                <?php else: ?>
                  <span class="badge bg-light text-muted border px-2 py-0.5" style="font-size: 0.68rem;">Not Uploaded</span>
                  <div class="text-muted small mt-1" style="font-size: 0.72rem;">File required for visa filing</div>
                <?php endif; ?>
              </div>

              <div class="mt-auto pt-2 border-top d-flex gap-1">
                <?php if ($isUploaded): ?>
                  <button type="button" class="btn btn-sm btn-outline-primary w-100 py-1" style="font-size: 0.75rem;" 
                          onclick="openDocumentPreview(<?= (int)$doc['id'] ?>, '<?= e(addslashes($title)) ?>', '<?= e($ext) ?>', '<?= e($status) ?>', <?= (int)$doc['version'] ?>)">
                    <i class="fa-solid fa-eye me-1"></i> View
                  </button>
                  <a href="/documents/download?id=<?= (int)$doc['id'] ?>" class="btn btn-sm btn-light border py-1 px-2" title="Download">
                    <i class="fa-solid fa-download"></i>
                  </a>
                <?php else: ?>
                  <?php if ($typeId > 0): ?>
                    <button type="button" class="btn btn-sm btn-primary w-100 py-1" style="font-size: 0.75rem;" onclick="openUploadModalWithType(<?= (int)$typeId ?>)">
                      <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload
                    </button>
                  <?php else: ?>
                    <button type="button" class="btn btn-sm btn-primary w-100 py-1" style="font-size: 0.75rem;" onclick="openUploadModalWithKeyword('<?= e($keyword) ?>')">
                      <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload
                    </button>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php
        };

        $renderQuickBentoCard('Passport Bio', 'fa-passport', $quickDocs['passport'], 1, 'passport', '#E11D48');
        $renderQuickBentoCard('Photograph', 'fa-camera', $quickDocs['photo'], 2, 'photo', '#D97706');
        $renderQuickBentoCard('CV / Resume', 'fa-file-lines', $quickDocs['cv'], 0, 'cv', '#0284C7');
        $renderQuickBentoCard('Visa / Entry', 'fa-stamp', $quickDocs['visa'], 0, 'visa', '#059669');
        $renderQuickBentoCard('National ID', 'fa-id-card', $quickDocs['national_id'], 4, 'id', '#7C3AED');
      ?>
    </div>

    <!-- All Application Documents by Category -->
    <div class="bento-card p-4 bg-white mb-4">
      <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
          <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-folder-tree text-primary"></i> Application Documents Vault
          </h5>
          <span class="text-muted small">Total of <?= count($applicationDocuments) ?> documents organized by category</span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="/documents/download-all?application_id=<?= (int)$app['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-xs">
            <i class="fa-solid fa-file-zipper me-1 text-primary"></i> Download All ZIP
          </a>
          <button type="button" class="btn btn-sm text-white rounded-pill px-3 shadow-xs" style="background: var(--bento-primary, #E11D48);" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
            <i class="fa-solid fa-plus me-1"></i> Upload New Document
          </button>
        </div>
      </div>

      <?php if (empty($applicationDocuments)): ?>
        <div class="text-center py-5">
          <div style="font-size: 3rem; color: #CBD5E1;" class="mb-3">
            <i class="fa-solid fa-folder-open"></i>
          </div>
          <h6 class="fw-bold text-dark mb-1">No Documents Uploaded Yet</h6>
          <p class="text-muted small mb-3">Begin by uploading the applicant's passport, photo, or visa forms.</p>
          <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
            <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload First Document
          </button>
        </div>
      <?php else: ?>
        <?php foreach ($categorizedDocs as $catName => $catDocs): ?>
          <?php if (!empty($catDocs)): ?>
            <div class="mb-4">
              <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom">
                <span class="fw-bold text-dark small text-uppercase">
                  <i class="fa-solid fa-folder me-1.5" style="color: #F59E0B;"></i> <?= e($catName) ?>
                </span>
                <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1">
                  <?= count($catDocs) ?> <?= count($catDocs) === 1 ? 'file' : 'files' ?>
                </span>
              </div>

              <!-- Grid of Instagram-style document cards -->
              <div class="row g-3">
                <?php foreach ($catDocs as $docItem): ?>
                  <?php
                    $ext = strtolower(pathinfo($docItem['file_name'] ?? '', PATHINFO_EXTENSION));
                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);
                    $isPdf = ($ext === 'pdf');

                    $stBadge = 'bg-secondary';
                    if ($docItem['status'] === 'VERIFIED') $stBadge = 'bg-success';
                    elseif ($docItem['status'] === 'REJECTED') $stBadge = 'bg-danger';
                    elseif ($docItem['status'] === 'UNDER_REVIEW') $stBadge = 'bg-warning text-dark';
                  ?>
                  <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                    <div class="social-doc-card h-100">
                      <!-- Document Preview Box -->
                      <div class="social-doc-preview-thumb">
                        <?php if ($isImage): ?>
                          <img src="/documents/preview?id=<?= (int)$docItem['id'] ?>" alt="<?= e($docItem['document_title'] ?: $docItem['doc_type_name']) ?>" loading="lazy">
                        <?php elseif ($isPdf): ?>
                          <div class="d-flex flex-column align-items-center justify-content-center text-danger">
                            <i class="fa-solid fa-file-pdf fs-1 mb-1"></i>
                            <span class="badge bg-danger-subtle text-danger font-monospace px-2 py-0.5">PDF DOCUMENT</span>
                          </div>
                        <?php else: ?>
                          <div class="d-flex flex-column align-items-center justify-content-center text-primary">
                            <i class="fa-solid fa-file-lines fs-1 mb-1"></i>
                            <span class="badge bg-primary-subtle text-primary font-monospace px-2 py-0.5"><?= strtoupper(e($ext ?: 'DOC')) ?></span>
                          </div>
                        <?php endif; ?>

                        <!-- Floating Status Pill on Thumbnail -->
                        <span class="position-absolute top-0 start-0 m-2 badge <?= $stBadge ?> shadow-xs" style="font-size: 0.65rem;">
                          <?= e($docItem['status']) ?>
                        </span>
                        <span class="position-absolute top-0 end-0 m-2 badge bg-dark text-white font-monospace shadow-xs" style="font-size: 0.65rem;">
                          v<?= (int)$docItem['version'] ?>
                        </span>
                      </div>

                      <!-- Document Card Body -->
                      <div class="p-3 d-flex flex-column flex-grow-1">
                        <div class="fw-bold text-dark small text-truncate" title="<?= e($docItem['document_title'] ?: $docItem['doc_type_name']) ?>">
                          <?= e($docItem['document_title'] ?: $docItem['doc_type_name']) ?>
                        </div>
                        <div class="text-muted mt-0.5" style="font-size: 0.72rem;">
                          <span><?= e($docItem['doc_type_name']) ?></span>
                        </div>

                        <?php if (!empty($docItem['expiry_date'])): ?>
                          <?php
                            $expD = (int)round((strtotime($docItem['expiry_date']) - time()) / 86400);
                            $expClass = ($expD < 0) ? 'text-danger' : (($expD <= 30) ? 'text-warning' : 'text-muted');
                          ?>
                          <div class="mt-1 <?= $expClass ?>" style="font-size: 0.7rem;">
                            <i class="fa-regular fa-clock me-1"></i>Exp: <?= e($docItem['expiry_date']) ?>
                          </div>
                        <?php endif; ?>

                        <!-- Actions Bar -->
                        <div class="mt-auto pt-2.5 border-top d-flex align-items-center justify-content-between gap-1">
                          <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2.5 flex-grow-1" style="font-size: 0.75rem;"
                                  onclick="openDocumentPreview(<?= (int)$docItem['id'] ?>, '<?= e(addslashes($docItem['document_title'] ?: $docItem['doc_type_name'])) ?>', '<?= e($ext) ?>', '<?= e($docItem['status']) ?>', <?= (int)$docItem['version'] ?>)">
                            <i class="fa-solid fa-eye me-1"></i> Preview
                          </button>
                          
                          <a href="/documents/download?id=<?= (int)$docItem['id'] ?>" class="btn btn-sm btn-light border py-1 px-2" title="Download File">
                            <i class="fa-solid fa-download"></i>
                          </a>

                          <?php if ($canVerifyDocs && $docItem['status'] !== 'VERIFIED'): ?>
                            <form action="/documents/verify" method="POST" class="d-inline" onsubmit="return confirm('Verify this document as compliant?');">
                              <?= csrf_field() ?>
                              <input type="hidden" name="document_id" value="<?= (int)$docItem['id'] ?>">
                              <button type="submit" class="btn btn-sm btn-outline-success py-1 px-2" title="Verify Document">
                                <i class="fa-solid fa-check"></i>
                              </button>
                            </form>
                          <?php endif; ?>

                          <?php if ($canVerifyDocs && $docItem['status'] !== 'REJECTED'): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2" title="Reject Document" onclick="openRejectModal(<?= (int)$docItem['id'] ?>)">
                              <i class="fa-solid fa-xmark"></i>
                            </button>
                          <?php endif; ?>

                          <button type="button" class="btn btn-sm btn-light border py-1 px-2" title="Version History" onclick="openHistoryModal(<?= (int)$docItem['id'] ?>)">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                          </button>
                        </div>
                      </div>
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

  <!-- ======================================================================
       TAB PANE 2: PERSONAL & PASSPORT (DASHBOARD BENTO CARDS)
       ====================================================================== -->
  <div id="tab-personal" class="profile-tab-pane d-none">
    <div class="row g-4 mb-4">
      <!-- Bento Card: Passport Credentials -->
      <div class="col-12 col-lg-6">
        <div class="bento-card p-4 bg-white h-100">
          <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
            <span class="fw-bold text-dark d-flex align-items-center gap-2">
              <i class="fa-solid fa-passport text-primary fs-5"></i> PASSPORT CREDENTIALS
            </span>
            <span class="badge <?= e($passportValidity['badge_class']) ?> px-2.5 py-1 fw-bold rounded-pill">
              <?= strtoupper(e($passportValidity['label'])) ?>
            </span>
          </div>

          <div class="row g-3">
            <div class="col-6">
              <span class="text-muted small">PASSPORT NUMBER</span>
              <div class="font-monospace text-primary fw-bold fs-6 mt-0.5"><?= e($app['passport_number'] ?: '—') ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">NATIONALITY</span>
              <div class="fw-bold text-dark mt-0.5"><?= e($app['nationality'] ?: '—') ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">ISSUING COUNTRY</span>
              <div class="fw-semibold text-dark mt-0.5"><?= e($app['passport_issuing_country'] ?: ($app['nationality'] ?: '—')) ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">DATE OF BIRTH</span>
              <div class="fw-semibold text-dark mt-0.5"><?= e($app['dob'] ? date('d M Y', strtotime($app['dob'])) : '—') ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">ISSUE DATE</span>
              <div class="fw-semibold text-dark mt-0.5"><?= e($app['passport_issue_date'] ? date('d M Y', strtotime($app['passport_issue_date'])) : '—') ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">EXPIRY DATE</span>
              <div class="fw-bold font-monospace text-dark mt-0.5"><?= e($app['passport_expiry_date'] ? date('d M Y', strtotime($app['passport_expiry_date'])) : '—') ?></div>
            </div>
          </div>

          <!-- Direct Passport Document Strip -->
          <div class="mt-4 p-3 bg-light rounded-4 border d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
              <div class="fw-bold small text-dark"><i class="fa-solid fa-file-contract text-primary me-1"></i> Passport Bio Document</div>
              <div class="text-muted" style="font-size: 0.72rem;">
                <?= !empty($quickDocs['passport']) ? e($quickDocs['passport']['file_name']) : 'No digital bio page attached' ?>
              </div>
            </div>
            <div class="d-flex gap-1.5">
              <?php if (!empty($quickDocs['passport'])): ?>
                <?php $pExt = strtolower(pathinfo($quickDocs['passport']['file_name'] ?? 'doc.pdf', PATHINFO_EXTENSION)) ?: 'pdf'; ?>
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" onclick="openDocumentPreview(<?= (int)$quickDocs['passport']['id'] ?>, 'Passport Bio Page', '<?= e($pExt) ?>', '<?= e($quickDocs['passport']['status']) ?>', <?= (int)$quickDocs['passport']['version'] ?>)">
                  <i class="fa-solid fa-eye me-1"></i> View Scan
                </button>
              <?php else: ?>
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" onclick="openUploadModalWithType(1)">
                  <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload Scan
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Bento Card: Demographics & Bio -->
      <div class="col-12 col-lg-6">
        <div class="bento-card p-4 bg-white h-100">
          <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
            <span class="fw-bold text-dark d-flex align-items-center gap-2">
              <i class="fa-solid fa-user text-primary fs-5"></i> PERSONAL DEMOGRAPHICS
            </span>
            <a href="/customers/edit?id=<?= (int)$app['customer_id'] ?>" class="btn btn-sm btn-link text-primary p-0 fw-semibold">
              <i class="fa-solid fa-pen-to-square me-1"></i> Edit CRM
            </a>
          </div>

          <div class="row g-3">
            <div class="col-12">
              <span class="text-muted small">FULL NAME</span>
              <div class="fw-bold text-dark fs-6 mt-0.5"><?= e($app['customer_name'] ?: '—') ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">GENDER</span>
              <div class="fw-semibold text-dark mt-0.5"><?= e($app['gender'] ?: '—') ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">AGE</span>
              <div class="fw-semibold text-dark mt-0.5"><?= $age !== null ? $age . ' years' : '—' ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">MARITAL STATUS</span>
              <div class="fw-semibold text-dark mt-0.5"><?= e($app['marital_status'] ?: '—') ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">RELIGION</span>
              <div class="fw-semibold text-dark mt-0.5"><?= e($app['religion'] ?: '—') ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">PLACE OF BIRTH</span>
              <div class="fw-semibold text-dark mt-0.5"><?= e($app['place_of_birth'] ?: '—') ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">OCCUPATION / DESIGNATION</span>
              <div class="fw-semibold text-dark mt-0.5"><?= e($app['occupation'] ?: '—') ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bento Card: Contact Channels & Residence -->
      <div class="col-12 col-lg-6">
        <div class="bento-card p-4 bg-white h-100">
          <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
            <span class="fw-bold text-dark d-flex align-items-center gap-2">
              <i class="fa-solid fa-address-book text-primary fs-5"></i> CONTACT &amp; RESIDENCE
            </span>
          </div>

          <div class="row g-3">
            <div class="col-6">
              <span class="text-muted small">MOBILE PHONE</span>
              <div class="fw-bold font-monospace text-dark mt-0.5">
                <?php if (!empty($app['mobile'])): ?>
                  <a href="tel:<?= e($app['mobile']) ?>" class="text-decoration-none text-dark"><?= e($app['mobile']) ?></a>
                <?php else: ?>—<?php endif; ?>
              </div>
            </div>
            <div class="col-6">
              <span class="text-muted small">WHATSAPP</span>
              <div class="fw-bold font-monospace text-success mt-0.5">
                <?php if (!empty($app['whatsapp'])): ?>
                  <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', (string)$app['whatsapp']) ?>" target="_blank" class="text-decoration-none text-success"><?= e($app['whatsapp']) ?></a>
                <?php else: ?>—<?php endif; ?>
              </div>
            </div>
            <div class="col-12">
              <span class="text-muted small">EMAIL ADDRESS</span>
              <div class="fw-semibold text-dark mt-0.5">
                <?php if (!empty($app['email'])): ?>
                  <a href="mailto:<?= e($app['email']) ?>" class="text-decoration-none text-dark"><?= e($app['email']) ?></a>
                <?php else: ?>—<?php endif; ?>
              </div>
            </div>
            <div class="col-6">
              <span class="text-muted small">COUNTRY OF RESIDENCE</span>
              <div class="fw-semibold text-dark mt-0.5"><?= e($app['current_country'] ?: '—') ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">PHYSICAL ADDRESS</span>
              <div class="fw-semibold text-dark mt-0.5"><?= e($app['address'] ?: '—') ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bento Card: Identifications & System Records -->
      <div class="col-12 col-lg-6">
        <div class="bento-card p-4 bg-white h-100">
          <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
            <span class="fw-bold text-dark d-flex align-items-center gap-2">
              <i class="fa-solid fa-id-card text-primary fs-5"></i> IDENTIFICATION &amp; METADATA
            </span>
          </div>

          <div class="row g-3">
            <div class="col-6">
              <span class="text-muted small">CUSTOMER CODE</span>
              <div class="fw-bold font-monospace text-primary mt-0.5"><?= e($app['customer_code'] ?: '—') ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">NATIONAL / EMIRATES ID</span>
              <div class="fw-bold font-monospace text-dark mt-0.5"><?= e($app['national_id_number'] ?: '—') ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">PROFILE CREATED</span>
              <div class="text-muted small mt-0.5"><?= !empty($app['customer_created_at']) ? date('d M Y, h:i A', strtotime($app['customer_created_at'])) : '—' ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">LAST UPDATED</span>
              <div class="text-muted small mt-0.5"><?= !empty($app['customer_updated_at']) ? date('d M Y, h:i A', strtotime($app['customer_updated_at'])) : '—' ?></div>
            </div>
            <?php if (!empty($app['customer_notes'])): ?>
              <div class="col-12">
                <span class="text-muted small">PROFILE NOTES</span>
                <div class="p-2 bg-light rounded small text-secondary mt-1"><?= nl2br(e($app['customer_notes'])) ?></div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ======================================================================
       TAB PANE 3: VISA APPLICATION (DASHBOARD BENTO CARDS)
       ====================================================================== -->
  <div id="tab-visa" class="profile-tab-pane d-none">
    <div class="row g-4 mb-4">
      <!-- Bento Card: Visa Service Package -->
      <div class="col-12 col-lg-6">
        <div class="bento-card p-4 bg-white h-100">
          <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
            <span class="fw-bold text-dark d-flex align-items-center gap-2">
              <i class="fa-solid fa-plane-departure text-primary fs-5"></i> VISA SERVICE PACKAGE
            </span>
            <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1">
              <?= e($app['visa_category'] ?? $app['category_name'] ?? 'General') ?>
            </span>
          </div>

          <div class="row g-3">
            <div class="col-12">
              <span class="text-muted small">PACKAGE NAME</span>
              <div class="fw-bold text-dark fs-6 mt-0.5"><?= e($app['service_name'] ?: 'Standard Visa') ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">DESTINATION</span>
              <div class="fw-semibold text-dark mt-0.5"><?= e($app['flag_emoji'] ?? '🌐') ?> <?= e($app['destination_country_name'] ?? $app['destination_country'] ?? 'United Arab Emirates') ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">VISA DURATION</span>
              <div class="fw-semibold text-dark mt-0.5"><?= e($app['visa_duration'] ?: ($app['service_duration'] ?? '30 Days')) ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">ENTRY TYPE</span>
              <div class="fw-semibold text-dark mt-0.5"><?= e($app['entry_type'] ?: ($app['service_entry_type'] ?? 'Single Entry')) ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">PROCESSING SPEED</span>
              <div class="fw-semibold text-dark mt-0.5"><?= e($app['processing_type'] ?: ($app['service_processing_type'] ?? 'Normal')) ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bento Card: Workflow & Dates -->
      <div class="col-12 col-lg-6">
        <div class="bento-card p-4 bg-white h-100">
          <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
            <span class="fw-bold text-dark d-flex align-items-center gap-2">
              <i class="fa-solid fa-timeline text-primary fs-5"></i> WORKFLOW &amp; TIMELINE
            </span>
            <a href="/applications/show?id=<?= (int)$app['id'] ?>" class="btn btn-sm btn-link text-primary p-0 fw-semibold">
              <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Full Application
            </a>
          </div>

          <div class="row g-3">
            <div class="col-6">
              <span class="text-muted small">APPLICATION NUMBER</span>
              <div class="fw-bold font-monospace text-primary mt-0.5"><?= e($app['application_number']) ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">CURRENT STAGE</span>
              <div class="mt-0.5"><span class="badge bg-light text-primary border"><?= e($app['current_stage']) ?></span></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">APPLICATION FILING DATE</span>
              <div class="fw-semibold text-dark mt-0.5"><?= !empty($app['application_date']) ? date('d M Y', strtotime($app['application_date'])) : '—' ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">EXPECTED COMPLETION</span>
              <div class="fw-semibold text-dark mt-0.5"><?= !empty($app['expected_completion_date']) ? date('d M Y', strtotime($app['expected_completion_date'])) : '—' ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">PLANNED TRAVEL</span>
              <div class="fw-semibold text-dark mt-0.5"><?= !empty($app['travel_date']) ? date('d M Y', strtotime($app['travel_date'])) : '—' ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">PRIORITY LEVEL</span>
              <div class="mt-0.5">
                <?php
                  $pClass = 'bg-secondary';
                  if ($app['priority'] === 'Urgent') $pClass = 'bg-warning text-dark';
                  elseif ($app['priority'] === 'Critical') $pClass = 'bg-danger text-white';
                  elseif ($app['priority'] === 'High') $pClass = 'bg-primary text-white';
                ?>
                <span class="badge <?= $pClass ?>"><?= e($app['priority'] ?? 'Normal') ?></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bento Card: Financials & Ledger -->
      <div class="col-12 col-lg-6">
        <div class="bento-card p-4 bg-white h-100">
          <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
            <span class="fw-bold text-dark d-flex align-items-center gap-2">
              <i class="fa-solid fa-receipt text-success fs-5"></i> FINANCIAL BREAKDOWN
            </span>
            <span class="badge <?= ($app['payment_status'] ?? '') === 'Paid' ? 'bg-success' : 'bg-warning text-dark' ?> px-2.5 py-1 rounded-pill fw-bold">
              <?= e($app['payment_status'] ?? 'Pending') ?>
            </span>
          </div>

          <div class="row g-3">
            <div class="col-4">
              <span class="text-muted small">TOTAL COST</span>
              <div class="fw-bold font-monospace fs-5 text-dark mt-0.5">AED <?= number_format((float)($app['total_amount'] ?? $app['selling_price'] ?? 0), 2) ?></div>
            </div>
            <div class="col-4">
              <span class="text-muted small">AMOUNT PAID</span>
              <div class="fw-bold font-monospace fs-5 text-success mt-0.5">AED <?= number_format((float)($app['paid_amount'] ?? 0), 2) ?></div>
            </div>
            <div class="col-4">
              <span class="text-muted small">REMAINING BALANCE</span>
              <div class="fw-bold font-monospace fs-5 <?= (float)($app['balance_amount'] ?? 0) > 0 ? 'text-danger' : 'text-muted' ?> mt-0.5">AED <?= number_format((float)($app['balance_amount'] ?? 0), 2) ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bento Card: Operations Staff Assignment -->
      <div class="col-12 col-lg-6">
        <div class="bento-card p-4 bg-white h-100">
          <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
            <span class="fw-bold text-dark d-flex align-items-center gap-2">
              <i class="fa-solid fa-user-tie text-primary fs-5"></i> OPERATIONS DESK
            </span>
          </div>

          <div class="row g-3">
            <div class="col-6">
              <span class="text-muted small">ASSIGNED OFFICER</span>
              <div class="fw-bold text-dark mt-0.5"><i class="fa-solid fa-user-check text-secondary me-1"></i><?= e($app['assigned_staff_name'] ?: 'Unassigned') ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">OPERATING BRANCH</span>
              <div class="fw-semibold text-dark mt-0.5"><i class="fa-solid fa-building text-secondary me-1"></i><?= e($app['branch_name'] ?: 'Main Branch') ?></div>
            </div>
            <div class="col-12">
              <span class="text-muted small">NEXT STEP / ACTION</span>
              <div class="fw-semibold text-dark mt-0.5">
                <?= e($app['next_action'] ?: 'Standard compliance review') ?>
                <?php if (!empty($app['next_action_due_date'])): ?>
                  <span class="badge bg-light text-danger border ms-1 font-monospace">Due: <?= date('d M Y', strtotime($app['next_action_due_date'])) ?></span>
                <?php endif; ?>
              </div>
            </div>
            <?php if (!empty($app['internal_notes'])): ?>
              <div class="col-12">
                <span class="text-muted small">INTERNAL NOTES</span>
                <div class="p-2 bg-light rounded small text-secondary mt-1"><?= nl2br(e($app['internal_notes'])) ?></div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ======================================================================
       TAB PANE 4: REQUIREMENTS CHECKLIST & COMPLIANCE
       ====================================================================== -->
  <div id="tab-checklist" class="profile-tab-pane d-none">
    <!-- Readiness Banner -->
    <div class="bento-card p-4 bg-white mb-4">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
          <i class="fa-solid fa-list-check text-primary"></i> Overall File Readiness
        </h6>
        <span class="badge <?= (int)$checklist['percentage'] === 100 ? 'bg-success' : 'bg-primary' ?> px-3 py-1.5 rounded-pill fw-bold fs-6">
          <?= (int)$checklist['percentage'] ?>% Complete
        </span>
      </div>

      <div class="progress my-3" style="height: 10px; border-radius: 9999px;">
        <div class="progress-bar progress-bar-striped progress-bar-animated <?= (int)$checklist['percentage'] === 100 ? 'bg-success' : 'bg-primary' ?>" 
             role="progressbar" style="width: <?= (int)$checklist['percentage'] ?>%;"></div>
      </div>

      <div class="d-flex flex-wrap gap-2 pt-1 small">
        <span class="badge bg-white text-secondary border rounded-pill px-2.5 py-1"><i class="fa-solid fa-asterisk text-primary me-1"></i>Required: <?= (int)$checklist['total_required'] ?></span>
        <span class="badge bg-white text-success border rounded-pill px-2.5 py-1"><i class="fa-solid fa-circle-check text-success me-1"></i>Verified: <?= (int)$checklist['total_verified'] ?></span>
        <span class="badge bg-white text-warning text-dark border rounded-pill px-2.5 py-1"><i class="fa-solid fa-clock text-warning me-1"></i>Pending: <?= (int)$checklist['total_pending'] ?></span>
        <?php if ((int)$checklist['total_missing'] > 0): ?>
          <span class="badge bg-danger text-white rounded-pill px-2.5 py-1"><i class="fa-solid fa-circle-exclamation me-1"></i>Missing: <?= (int)$checklist['total_missing'] ?></span>
        <?php endif; ?>
        <?php if ((int)$checklist['total_rejected'] > 0): ?>
          <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1"><i class="fa-solid fa-ban me-1"></i>Rejected: <?= (int)$checklist['total_rejected'] ?></span>
        <?php endif; ?>
        <?php if ((int)$checklist['total_expired'] > 0): ?>
          <span class="badge bg-dark text-white rounded-pill px-2.5 py-1"><i class="fa-solid fa-calendar-xmark me-1"></i>Expired: <?= (int)$checklist['total_expired'] ?></span>
        <?php endif; ?>
      </div>

      <!-- Action Required Missing Items -->
      <?php
        $missingMandatory = array_filter($checklist['mandatory_items'] ?? [], function($item) {
            return $item['status'] === 'MISSING';
        });
        $rejectedMandatory = array_filter($checklist['mandatory_items'] ?? [], function($item) {
            return $item['status'] === 'REJECTED';
        });
      ?>

      <?php if (!empty($missingMandatory)): ?>
        <div class="mt-4 p-3 bg-warning-subtle border border-warning rounded-4">
          <div class="fw-bold text-dark small mb-2 d-flex align-items-center gap-1.5">
            <i class="fa-solid fa-triangle-exclamation text-warning"></i>
            <span>Action Required: <?= count($missingMandatory) ?> Mandatory Document(s) Missing</span>
          </div>
          <div class="d-flex flex-column gap-2">
            <?php foreach ($missingMandatory as $mItem): ?>
              <div class="d-flex flex-wrap align-items-center justify-content-between p-2.5 bg-white rounded-3 border border-warning-subtle gap-2">
                <div class="d-flex align-items-center gap-2">
                  <i class="fa-solid fa-file-circle-exclamation text-danger fs-5"></i>
                  <div>
                    <div class="fw-bold text-dark small"><?= e($mItem['document_name']) ?></div>
                    <div class="text-muted" style="font-size: 0.72rem;"><?= e($mItem['condition_notes'] ?: ($mItem['instructions'] ?: 'Standard requirement for visa filing.')) ?></div>
                  </div>
                </div>
                <div class="d-flex align-items-center gap-1.5">
                  <button type="button" class="btn btn-outline-warning text-dark btn-sm rounded-pill px-2.5 py-1" onclick="openRequestDocModal(<?= (int)$mItem['document_type_id'] ?>, '<?= e(addslashes($mItem['document_name'])) ?>')">
                    <i class="fa-solid fa-paper-plane me-1"></i> Request
                  </button>
                  <button type="button" class="btn btn-primary btn-sm rounded-pill px-2.5 py-1" onclick="openUploadModalWithType(<?= (int)$mItem['document_type_id'] ?>)">
                    <i class="fa-solid fa-upload me-1"></i> Upload
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($rejectedMandatory)): ?>
        <div class="mt-3 p-3 bg-danger-subtle border border-danger-subtle rounded-4">
          <div class="fw-bold text-danger small mb-2 d-flex align-items-center gap-1.5">
            <i class="fa-solid fa-ban text-danger"></i>
            <span>Action Required: <?= count($rejectedMandatory) ?> Mandatory Document(s) Rejected</span>
          </div>
          <div class="d-flex flex-column gap-2">
            <?php foreach ($rejectedMandatory as $rItem): ?>
              <div class="d-flex flex-wrap align-items-center justify-content-between p-2.5 bg-white rounded-3 border border-danger-subtle gap-2">
                <div>
                  <div class="fw-bold text-dark small"><?= e($rItem['document_name']) ?></div>
                  <div class="text-danger small" style="font-size: 0.72rem;"><strong>Rejection Reason:</strong> <?= e($rItem['rejection_reason'] ?: 'Document does not meet embassy standards.') ?></div>
                </div>
                <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 py-1" onclick="openUploadModalWithType(<?= (int)$rItem['document_type_id'] ?>)">
                  <i class="fa-solid fa-upload me-1"></i> Re-upload
                </button>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Full Requirements Matrix -->
    <div class="bento-card p-4 bg-white mb-4">
      <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
          <i class="fa-solid fa-clipboard-check text-primary"></i> Service Requirements Matrix
        </h6>
        <span class="text-muted small"><?= count($checklist['items']) ?> Requirements</span>
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
          <div class="p-3 bg-white rounded-3 border d-flex flex-wrap align-items-center justify-content-between gap-2 shadow-2xs">
            <div class="d-flex align-items-start gap-2.5 flex-grow-1 min-w-0">
              <div class="mt-0.5">
                <span class="badge <?= $cBadge ?> rounded-circle p-1.5 d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                  <i class="fa-solid <?= $cIcon ?>" style="font-size: 0.72rem;"></i>
                </span>
              </div>
              <div class="min-w-0">
                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                  <span class="fw-bold text-dark small"><?= e($cItem['document_name']) ?></span>
                  <?php if ($cItem['is_critical']): ?>
                    <span class="badge bg-danger text-white rounded-pill py-0.5 px-2" style="font-size: 0.65rem;">Critical</span>
                  <?php elseif ($cItem['is_mandatory']): ?>
                    <span class="badge bg-danger-subtle text-danger rounded-pill py-0.5 px-2" style="font-size: 0.65rem;">Mandatory</span>
                  <?php else: ?>
                    <span class="badge bg-light text-muted border rounded-pill py-0.5 px-2" style="font-size: 0.65rem;">Optional</span>
                  <?php endif; ?>
                  <span class="badge <?= $cBadge ?> rounded-pill py-0.5 px-2 font-monospace" style="font-size: 0.68rem;"><?= e($cStatus) ?></span>
                </div>
                <?php if (!empty($cItem['condition_notes']) || !empty($cItem['instructions'])): ?>
                  <div class="text-muted small mt-0.5" style="font-size: 0.72rem;">
                    <?= e($cItem['condition_notes'] ?: $cItem['instructions']) ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <div class="d-flex align-items-center gap-1.5">
              <?php if (!empty($cItem['document_id'])): ?>
                <?php $ext = strtolower(pathinfo($cItem['file_name'] ?? '', PATHINFO_EXTENSION)); ?>
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1" style="font-size: 0.75rem;" onclick="openDocumentPreview(<?= (int)$cItem['document_id'] ?>, '<?= e(addslashes($cItem['document_name'])) ?>', '<?= e($ext) ?>', '<?= e($cItem['status']) ?>', <?= (int)$cItem['version'] ?>)">
                  <i class="fa-solid fa-eye me-1"></i> View Scan
                </button>
              <?php else: ?>
                <button type="button" class="btn btn-outline-warning text-dark btn-sm rounded-pill px-2.5 py-1" style="font-size: 0.75rem;" onclick="openRequestDocModal(<?= (int)$cItem['document_type_id'] ?>, '<?= e(addslashes($cItem['document_name'])) ?>')">
                  <i class="fa-solid fa-paper-plane me-1"></i> Request
                </button>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-2.5 py-1" style="font-size: 0.75rem;" onclick="openUploadModalWithType(<?= (int)$cItem['document_type_id'] ?>)">
                  <i class="fa-solid fa-upload me-1"></i> Upload
                </button>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- ======================================================================
       TAB PANE 5: ACTIVITY TIMELINE
       ====================================================================== -->
  <div id="tab-activity" class="profile-tab-pane d-none">
    <div class="bento-card p-4 bg-white mb-4">
      <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
          <i class="fa-solid fa-clock-rotate-left text-primary"></i> Document &amp; Case Audit Trail
        </h6>
        <span class="text-muted small">Live Activity Records</span>
      </div>

      <?php if (empty($recentActivity)): ?>
        <div class="text-center py-4 text-muted small">
          No audit entries recorded for this application yet.
        </div>
      <?php else: ?>
        <div class="list-group list-group-flush small">
          <?php foreach ($recentActivity as $act): ?>
            <div class="list-group-item d-flex align-items-center justify-content-between p-3 border-bottom">
              <div>
                <span class="badge bg-light text-dark border me-1.5 font-monospace"><?= e($act['action']) ?></span>
                <span class="fw-semibold text-dark"><?= e($act['description'] ?: $act['action']) ?></span>
                <div class="text-muted mt-0.5" style="font-size: 0.72rem;">By <?= e($act['user_name'] ?: 'System') ?></div>
              </div>
              <span class="text-muted small" style="white-space: nowrap;"><?= date('d M Y, h:i A', strtotime($act['created_at'])) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
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

// Instagram & TikTok Social Profile Tab Switcher
document.addEventListener('DOMContentLoaded', function() {
  const tabBtns = document.querySelectorAll('.social-tab-btn');
  const panes = document.querySelectorAll('.profile-tab-pane');

  tabBtns.forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const tabTarget = this.getAttribute('data-tab');
      tabBtns.forEach(b => b.classList.remove('active'));
      panes.forEach(p => p.classList.add('d-none'));
      this.classList.add('active');
      const targetPane = document.getElementById(tabTarget);
      if (targetPane) {
        targetPane.classList.remove('d-none');
      }
      if (history.replaceState) {
        history.replaceState(null, null, '#' + tabTarget);
      }
    });
  });

  if (window.location.hash) {
    const hash = window.location.hash.substring(1);
    const activeBtn = document.querySelector(`.social-tab-btn[data-tab="${hash}"]`);
    if (activeBtn) {
      activeBtn.click();
    }
  }
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
