<?php
$pageTitle = 'APPLICANT DOCUMENT REPOSITORY — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';

// Safe URL builder preserving query params
$buildDocUrl = function(array $params = []): string {
    $merged = array_merge($_GET, $params);
    return '/documents?' . http_build_query($merged);
};
?>

<link rel="stylesheet" href="/assets/css/pages/documents.css?v=2.0">

<div class="content-body">
  <div class="bento-dashboard-wrap">

    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'warning')) ?> alert-dismissible fade show mb-4 rounded-4 border shadow-xs" role="alert">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation text-danger' : ($flash['type'] === 'success' ? 'fa-circle-check text-success' : 'fa-triangle-exclamation text-warning') ?>"></i>
          <span class="fw-medium"><?= e($flash['message']) ?></span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- ─── 1. BENTO REPOSITORY HEADER ──────────────────────────────────────── -->
    <div class="bento-card mb-4" style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); color: #FFFFFF; border: none;">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
          <div style="width: 52px; height: 52px; border-radius: 16px; background: rgba(225, 29, 72, 0.2); border: 1px solid rgba(225, 29, 72, 0.4); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #FDA4AF;">
            <i class="fa-solid fa-folder-tree"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <h3 class="fw-bold mb-0 text-white" style="font-size: 1.4rem; letter-spacing: -0.02em;">APPLICANT DOCUMENT REPOSITORY</h3>
              <span class="badge rounded-pill px-2.5 py-1 text-white" style="background: var(--bento-primary); font-size: 0.72rem; letter-spacing: 0.04em;">
                FOLDER ARCHITECTURE
              </span>
            </div>
            <p class="text-white-50 small mb-0 mt-0.5">
              Secure applicant-based document vaults. Click any folder to inspect person details and manage files.
            </p>
          </div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
          <a href="/documents/export-csv?<?= http_build_query($_GET) ?>" class="btn btn-sm btn-outline-light rounded-pill px-3 shadow-xs">
            <i class="fa-solid fa-file-csv me-1.5 text-success"></i> Export CSV
          </a>
          <button type="button" class="btn btn-sm text-white rounded-pill px-3 shadow-xs" style="background: var(--bento-primary); border: none;" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
            <i class="fa-solid fa-cloud-arrow-up me-1.5"></i> Upload Document
          </button>
        </div>
      </div>
    </div>

    <!-- ─── 2. EXECUTIVE METRIC COUNTERS (4 STRIP) ─────────────────────────── -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <a href="/documents" class="text-decoration-none">
          <div class="bento-metric-card h-100">
            <div class="bento-metric-header">
              <span class="bento-metric-title">Applicant Folders</span>
              <div class="bento-metric-icon" style="background: #FEF3C7; color: #D97706;">
                <i class="fa-solid fa-folder-closed"></i>
              </div>
            </div>
            <div class="bento-metric-val"><?= $stats['total_folders'] ?? count($allFolders) ?></div>
            <div class="bento-metric-sub text-muted">
              <i class="fa-solid fa-users me-1 text-primary"></i> <?= $totalFolders ?> applicant directories
            </div>
          </div>
        </a>
      </div>

      <div class="col-6 col-md-3">
        <a href="/documents?status=VERIFIED" class="text-decoration-none">
          <div class="bento-metric-card h-100">
            <div class="bento-metric-header">
              <span class="bento-metric-title">Verified Documents</span>
              <div class="bento-metric-icon" style="background: #ECFDF5; color: #059669;">
                <i class="fa-solid fa-shield-check"></i>
              </div>
            </div>
            <div class="bento-metric-val" style="color: #059669;"><?= $stats['verified'] ?></div>
            <div class="bento-metric-sub text-muted">
              <i class="fa-solid fa-circle-check me-1 text-success"></i> 100% compliant &amp; stamped
            </div>
          </div>
        </a>
      </div>

      <div class="col-6 col-md-3">
        <a href="/documents?status=UNDER_REVIEW" class="text-decoration-none">
          <div class="bento-metric-card h-100">
            <div class="bento-metric-header">
              <span class="bento-metric-title">Under Review</span>
              <div class="bento-metric-icon" style="background: #FFFBEB; color: #D97706;">
                <i class="fa-solid fa-clock-rotate-left"></i>
              </div>
            </div>
            <div class="bento-metric-val" style="color: #D97706;"><?= $stats['pending_review'] ?></div>
            <div class="bento-metric-sub text-muted">
              <i class="fa-solid fa-hourglass-half me-1 text-warning"></i> Awaiting officer check
            </div>
          </div>
        </a>
      </div>

      <div class="col-6 col-md-3">
        <a href="/documents?status=REJECTED" class="text-decoration-none">
          <div class="bento-metric-card h-100">
            <div class="bento-metric-header">
              <span class="bento-metric-title">Rejected / Missing</span>
              <div class="bento-metric-icon" style="background: #FFF1F2; color: #E11D48;">
                <i class="fa-solid fa-triangle-exclamation"></i>
              </div>
            </div>
            <div class="bento-metric-val" style="color: #E11D48;"><?= $stats['rejected'] ?></div>
            <div class="bento-metric-sub text-muted">
              <i class="fa-solid fa-ban me-1 text-danger"></i> Needs applicant action
            </div>
          </div>
        </a>
      </div>
    </div>

    <!-- ─── 3. SEARCH & ADVANCED FILTER TOOLBAR ────────────────────────────── -->
    <div class="bento-card mb-4 p-3 bg-white">
      <form action="/documents" method="GET" class="row g-2 align-items-center">
        <!-- Keyword Search -->
        <div class="col-12 col-md-4">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light border-end-0 text-muted">
              <i class="fa-solid fa-magnifying-glass"></i>
            </span>
            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search applicant, passport, app #..." value="<?= e($_GET['search'] ?? '') ?>">
          </div>
        </div>

        <!-- Compliance Status -->
        <div class="col-6 col-sm-4 col-md-2">
          <select name="status" class="form-select form-select-sm">
            <option value="">All Statuses</option>
            <option value="VERIFIED" <?= ($_GET['status'] ?? '') === 'VERIFIED' ? 'selected' : '' ?>>Verified</option>
            <option value="UNDER_REVIEW" <?= ($_GET['status'] ?? '') === 'UNDER_REVIEW' ? 'selected' : '' ?>>Under Review</option>
            <option value="REJECTED" <?= ($_GET['status'] ?? '') === 'REJECTED' ? 'selected' : '' ?>>Rejected</option>
            <option value="EXPIRING_SOON" <?= ($_GET['status'] ?? '') === 'EXPIRING_SOON' ? 'selected' : '' ?>>Expiring (&le;30d)</option>
            <option value="EXPIRED" <?= ($_GET['status'] ?? '') === 'EXPIRED' ? 'selected' : '' ?>>Expired</option>
          </select>
        </div>

        <!-- Destination Country -->
        <div class="col-6 col-sm-4 col-md-2">
          <select name="country_id" class="form-select form-select-sm">
            <option value="">All Countries</option>
            <?php foreach ($countries as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ((int)($_GET['country_id'] ?? 0)) === (int)$c['id'] ? 'selected' : '' ?>>
                <?= $c['flag_emoji'] ?> <?= e($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Visa Service -->
        <div class="col-6 col-sm-4 col-md-2">
          <select name="service_id" class="form-select form-select-sm">
            <option value="">All Services</option>
            <?php foreach ($services as $srv): ?>
              <option value="<?= $srv['id'] ?>" <?= ((int)($_GET['service_id'] ?? 0)) === (int)$srv['id'] ? 'selected' : '' ?>>
                <?= e($srv['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Actions -->
        <div class="col-6 col-md-2 d-flex gap-1 justify-content-end">
          <button type="submit" class="btn btn-sm btn-dark flex-grow-1 rounded-pill">
            <i class="fa-solid fa-filter me-1 small"></i> Filter
          </button>
          <a href="/documents" class="btn btn-sm btn-light border rounded-pill" title="Reset Filters">
            <i class="fa-solid fa-rotate-left"></i>
          </a>
        </div>
      </form>
    </div>

    <!-- ─── 4. APPLICANT FOLDERS DIRECTORY HEADER & VIEW TOGGLE ────────────── -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
      <div class="d-flex align-items-center gap-2">
        <h5 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.01em;">
          <i class="fa-solid fa-folder-tree text-warning me-2"></i>Applicant Folders
        </h5>
        <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1">
          <?= count($folders) ?> of <?= $totalFolders ?> folders
        </span>
      </div>

      <!-- View Switcher: Tree / Explorer View vs Bento Cards -->
      <div class="bento-view-switcher" role="group" aria-label="Folder View Switcher">
        <button type="button" class="bento-view-pill active" id="btnViewTree" onclick="toggleFolderView('tree')">
          <i class="fa-solid fa-folder me-1.5" style="color: #F59E0B;"></i> Explorer View
        </button>
        <button type="button" class="bento-view-pill" id="btnViewCards" onclick="toggleFolderView('cards')">
          <i class="fa-solid fa-grip me-1.5"></i> Cards View
        </button>
      </div>
    </div>

    <!-- ─── 5. APPLICANT FOLDER LIST / DIRECTORY ───────────────────────────── -->
    <?php if (empty($folders)): ?>
      <div class="bento-card p-5 text-center bg-white">
        <div style="font-size: 3rem; color: #CBD5E1;" class="mb-3">
          <i class="fa-solid fa-folder-open"></i>
        </div>
        <h5 class="fw-bold text-dark mb-1">No Applicant Folders Found</h5>
        <p class="text-muted small mb-3">No folders match your active search or filter criteria.</p>
        <a href="/documents" class="btn btn-sm btn-outline-secondary rounded-pill px-3 me-2">Clear Filters</a>
        <a href="/applications/create" class="btn btn-sm btn-primary rounded-pill px-3">
          <i class="fa-solid fa-plus me-1"></i> New Application
        </a>
      </div>
    <?php else: ?>

      <!-- ─── A. EXPLORER TREE VIEW (MATCHES USER REFERENCE SCREENSHOT) ───── -->
      <div id="folderTreeView" class="bento-folder-tree mb-4">
        <?php foreach ($folders as $f): ?>
          <?php
            $fStatusBadge = 'bg-secondary';
            if ($f['application_status'] === 'Approved') $fStatusBadge = 'bg-success';
            elseif ($f['application_status'] === 'Pending') $fStatusBadge = 'bg-warning text-dark';
            elseif ($f['application_status'] === 'Rejected') $fStatusBadge = 'bg-danger';
            elseif ($f['application_status'] === 'In Process') $fStatusBadge = 'bg-primary';
            elseif ($f['application_status'] === 'Draft') $fStatusBadge = 'bg-info text-dark';

            $encodedFolder = htmlspecialchars(json_encode($f), ENT_QUOTES, 'UTF-8');
            $docCount = count($f['uploaded_documents'] ?? []);
          ?>
          <div class="bento-folder-tree-item" onclick="openApplicantFolderModal(this)" data-folder="<?= $encodedFolder ?>">
            <div class="bento-folder-tree-main">
              <!-- Golden Folder Icon matching native file systems -->
              <div class="bento-folder-icon-box">
                <i class="fa-solid fa-folder"></i>
              </div>

              <!-- Folder Name & Core Details -->
              <div class="min-w-0">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <div class="bento-folder-name" title="<?= e($f['customer_name']) ?>">
                    <?= e($f['customer_name']) ?>
                  </div>
                  <span class="badge <?= $fStatusBadge ?> px-2 py-0.5 rounded-pill" style="font-size: 0.68rem;">
                    <?= e($f['application_status']) ?>
                  </span>
                  <span class="badge bg-light text-secondary border rounded-pill px-2 py-0.5" style="font-size: 0.68rem;">
                    <?= e($f['current_stage']) ?>
                  </span>
                </div>

                <div class="bento-folder-submeta mt-1">
                  <span class="font-monospace text-muted fw-semibold">
                    <i class="fa-regular fa-id-badge me-1"></i><?= e($f['customer_code'] ?? 'MSC-000000') ?>
                  </span>
                  <?php if (!empty($f['passport_number'])): ?>
                    <span>&bull;</span>
                    <span class="font-monospace text-dark fw-bold">
                      <i class="fa-solid fa-passport text-muted me-1"></i><?= e($f['passport_number']) ?>
                    </span>
                  <?php endif; ?>
                  <span>&bull;</span>
                  <span>
                    <?= e($f['flag_emoji'] ?? '🌐') ?> <?= e($f['country_name'] ?? 'Destination') ?> (<?= e($f['service_name'] ?? 'Visa') ?>)
                  </span>
                </div>
              </div>
            </div>

            <!-- Right Meta Chips & Action -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="bento-folder-meta-pill">
                <i class="fa-solid fa-file-lines text-primary"></i> <?= $docCount ?> files
              </span>
              <?php if ((int)$f['verified_docs'] > 0): ?>
                <span class="bento-folder-meta-pill" style="background: #ECFDF5; color: #059669;">
                  <i class="fa-solid fa-check"></i> <?= (int)$f['verified_docs'] ?> verified
                </span>
              <?php endif; ?>
              <?php if ((int)$f['pending_docs'] > 0): ?>
                <span class="bento-folder-meta-pill" style="background: #FFFBEB; color: #D97706;">
                  <i class="fa-solid fa-clock"></i> <?= (int)$f['pending_docs'] ?> pending
                </span>
              <?php endif; ?>

              <!-- Direct Actions -->
              <div class="d-flex align-items-center gap-1 ms-2" onclick="event.stopPropagation();">
                <button type="button" class="btn btn-sm btn-light border rounded-pill px-2.5 py-1 text-dark" 
                        onclick="triggerApplicantFolderFromBtn(this)" 
                        data-folder="<?= $encodedFolder ?>" 
                        title="Inspect Folder Details">
                  <i class="fa-solid fa-eye text-muted me-1 small"></i> View
                </button>
                <a href="/documents/profile?application_id=<?= (int)$f['application_id'] ?>" 
                   class="btn btn-sm btn-light border rounded-pill px-2.5 py-1" 
                   title="Open Dedicated Document Workspace">
                  <i class="fa-solid fa-folder-open text-primary small"></i>
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- ─── B. BENTO FOLDER GRID CARDS VIEW ────────────────────────────── -->
      <div id="folderCardsView" class="bento-folder-grid mb-4 d-none">
        <?php foreach ($folders as $f): ?>
          <?php
            $fStatusBadge = 'bg-secondary';
            if ($f['application_status'] === 'Approved') $fStatusBadge = 'bg-success';
            elseif ($f['application_status'] === 'Pending') $fStatusBadge = 'bg-warning text-dark';
            elseif ($f['application_status'] === 'Rejected') $fStatusBadge = 'bg-danger';
            elseif ($f['application_status'] === 'In Process') $fStatusBadge = 'bg-primary';
            elseif ($f['application_status'] === 'Draft') $fStatusBadge = 'bg-info text-dark';

            $encodedFolder = htmlspecialchars(json_encode($f), ENT_QUOTES, 'UTF-8');
            $docCount = count($f['uploaded_documents'] ?? []);

            $nameParts = explode(' ', trim((string)($f['customer_name'] ?? '')));
            $firstInitial = !empty($nameParts[0]) ? mb_substr($nameParts[0], 0, 1) : 'A';
            $lastInitial = count($nameParts) > 1 ? mb_substr(end($nameParts), 0, 1) : '';
            $initials = strtoupper($firstInitial . $lastInitial) ?: 'AP';
          ?>
          <div class="bento-folder-card" onclick="openApplicantFolderModal(this)" data-folder="<?= $encodedFolder ?>">
            <!-- Card Top: Folder Tab Badge + Application Status -->
            <div class="bento-folder-card-top">
              <div class="bento-folder-tab-badge">
                <i class="fa-solid fa-folder" style="color: #F59E0B; font-size: 1.1rem;"></i>
                <span class="font-monospace text-dark"><?= e($f['application_number']) ?></span>
              </div>
              <span class="badge <?= $fStatusBadge ?> px-2 py-0.5 rounded-pill" style="font-size: 0.68rem;">
                <?= e($f['application_status']) ?>
              </span>
            </div>

            <!-- Card Body: Avatar + Applicant Info -->
            <div class="bento-folder-card-body">
              <div class="bento-folder-avatar-circle">
                <?php if (!empty($f['photo_doc_id'])): ?>
                  <img src="/documents/preview?id=<?= (int)$f['photo_doc_id'] ?>" 
                       alt="<?= e($f['customer_name']) ?>" 
                       loading="lazy" 
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                  <span style="display:none;"><?= e($initials) ?></span>
                <?php else: ?>
                  <span><?= e($initials) ?></span>
                <?php endif; ?>
              </div>

              <div class="min-w-0 flex-grow-1">
                <div class="fw-bold text-dark text-truncate" style="font-size: 0.95rem;">
                  <?= e($f['customer_name']) ?>
                </div>
                <div class="text-muted small" style="font-size: 0.75rem;">
                  <span class="font-monospace text-secondary fw-semibold"><?= e($f['customer_code'] ?? 'MSC-000000') ?></span>
                  <?php if (!empty($f['passport_number'])): ?>
                    &bull; <i class="fa-solid fa-passport text-muted"></i> <?= e($f['passport_number']) ?>
                  <?php endif; ?>
                </div>
                <div class="text-muted small mt-0.5" style="font-size: 0.72rem;">
                  <?= e($f['flag_emoji'] ?? '🌐') ?> <?= e($f['country_name'] ?? 'UAE') ?> &bull; <?= e($f['service_name'] ?? 'Visa') ?>
                </div>
              </div>
            </div>

            <!-- Card Stats Strip: Files, Verified, Pending -->
            <div class="bento-folder-stats-strip">
              <div class="bento-folder-stat-box">
                <span class="bento-folder-stat-num"><?= $docCount ?></span>
                <span class="bento-folder-stat-lbl">Files</span>
              </div>
              <div class="bento-folder-stat-box">
                <span class="bento-folder-stat-num text-success"><?= (int)$f['verified_docs'] ?></span>
                <span class="bento-folder-stat-lbl">Verified</span>
              </div>
              <div class="bento-folder-stat-box">
                <span class="bento-folder-stat-num text-warning"><?= (int)$f['pending_docs'] ?></span>
                <span class="bento-folder-stat-lbl">Pending</span>
              </div>
            </div>

            <!-- Footer Action Buttons -->
            <div class="d-flex align-items-center gap-2 pt-2 border-top" onclick="event.stopPropagation();">
              <button type="button" class="btn btn-sm btn-dark flex-grow-1 rounded-pill" style="font-size: 0.8rem;" 
                      onclick="triggerApplicantFolderFromBtn(this)" 
                      data-folder="<?= $encodedFolder ?>">
                <i class="fa-solid fa-folder-open me-1 text-warning"></i> Open Folder
              </button>
              <a href="/documents/profile?application_id=<?= (int)$f['application_id'] ?>" 
                 class="btn btn-sm btn-light border rounded-pill px-2.5 py-1 text-muted" 
                 title="Dedicated Workspace">
                <i class="fa-solid fa-arrow-up-right-from-square small"></i>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- ─── PAGINATION CONTROLS ────────────────────────────────────────── -->
      <?php if ($folderTotalPages >= 1 && $totalFolders > 0): ?>
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mt-3 mb-4 p-3 bg-white rounded-4 border shadow-xs">
          <div class="text-muted small">
            Showing <span class="fw-bold text-dark"><?= (($folderPage - 1) * $folderPerPage) + 1 ?></span> &ndash; <span class="fw-bold text-dark"><?= min($totalFolders, $folderPage * $folderPerPage) ?></span> of <span class="fw-bold text-dark"><?= $totalFolders ?></span> applicant folders
          </div>

          <nav aria-label="Applicant Folder Pagination">
            <ul class="pagination pagination-sm mb-0 gap-1 align-items-center">
              <li class="page-item <?= ($folderPage <= 1) ? 'disabled' : '' ?>">
                <a class="page-link rounded-pill px-3 py-1.5 fw-semibold <?= ($folderPage <= 1) ? 'text-muted bg-light border-0' : 'text-dark border shadow-xs' ?>" 
                   href="<?= ($folderPage > 1) ? $buildDocUrl(['f_page' => $folderPage - 1]) : 'javascript:void(0)' ?>">
                  <i class="fa-solid fa-chevron-left me-1 small"></i> Prev
                </a>
              </li>

              <?php for ($p = 1; $p <= max(1, $folderTotalPages); $p++): ?>
                <li class="page-item <?= ($p === $folderPage) ? 'active' : '' ?>">
                  <a class="page-link rounded-pill text-center fw-bold <?= ($p === $folderPage) ? 'text-white shadow-xs' : 'text-dark border' ?>" 
                     style="<?= ($p === $folderPage) ? 'background: var(--bento-primary); border-color: var(--bento-primary);' : '' ?> min-width: 34px; padding: 0.35rem 0.65rem;" 
                     href="<?= $buildDocUrl(['f_page' => $p]) ?>">
                    <?= $p ?>
                  </a>
                </li>
              <?php endfor; ?>

              <li class="page-item <?= ($folderPage >= $folderTotalPages) ? 'disabled' : '' ?>">
                <a class="page-link rounded-pill px-3 py-1.5 fw-semibold <?= ($folderPage >= $folderTotalPages) ? 'text-muted bg-light border-0' : 'text-dark border shadow-xs' ?>" 
                   href="<?= ($folderPage < $folderTotalPages) ? $buildDocUrl(['f_page' => $folderPage + 1]) : 'javascript:void(0)' ?>">
                  Next <i class="fa-solid fa-chevron-right ms-1 small"></i>
                </a>
              </li>
            </ul>
          </nav>
        </div>
      <?php endif; ?>

    <?php endif; ?>

  </div><!-- /bento-dashboard-wrap -->
</div><!-- /content-body -->


<!-- ══════════════════════════════════════════════════════════════════════════
     INTERACTIVE APPLICANT FOLDER DETAILS MODAL (ALL PERSON DETAILS & DOCUMENTS)
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="applicantFolderModal" tabindex="-1" aria-labelledby="applicantFolderModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
      <!-- Modal Header -->
      <div class="bento-drawer-header">
        <div class="d-flex align-items-center gap-3">
          <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(245, 158, 11, 0.2); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: #F59E0B;">
            <i class="fa-solid fa-folder-open"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <h5 class="fw-bold mb-0 text-white" id="folderModalApplicantName">Applicant Name</h5>
              <span class="badge rounded-pill bg-light text-dark font-monospace" id="folderModalCustCode">MSC-000000</span>
              <span class="badge rounded-pill bg-success" id="folderModalAppStatus">Status</span>
            </div>
            <div class="small text-white-50 mt-0.5">
              <span id="folderModalAppNumber" class="font-monospace text-warning fw-semibold">MSV-2026-000000</span> &bull; 
              <span id="folderModalServiceCountry">UAE 30 Days Visa</span>
            </div>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-3 p-md-4 bg-light">
        <!-- 1. PERSON ALL DETAILS SECTION -->
        <div class="card rounded-4 border shadow-xs bg-white mb-4 overflow-hidden">
          <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
            <span class="fw-bold small text-dark">
              <i class="fa-solid fa-user-check me-1.5" style="color: var(--bento-primary);"></i> Applicant Personal &amp; Passport Details
            </span>
            <span class="badge bg-light text-muted border" id="folderModalStageBadge">Application Registered</span>
          </div>
          <div class="card-body p-3">
            <div class="row g-3">
              <div class="col-6 col-md-3">
                <div class="text-muted small" style="font-size: 0.72rem;">Passport Number</div>
                <div class="fw-bold text-dark font-monospace" id="folderModalPassport">—</div>
              </div>
              <div class="col-6 col-md-3">
                <div class="text-muted small" style="font-size: 0.72rem;">Passport Expiry</div>
                <div class="fw-bold text-dark" id="folderModalPassportExpiry">—</div>
              </div>
              <div class="col-6 col-md-3">
                <div class="text-muted small" style="font-size: 0.72rem;">Nationality / Origin</div>
                <div class="fw-bold text-dark" id="folderModalNationality">—</div>
              </div>
              <div class="col-6 col-md-3">
                <div class="text-muted small" style="font-size: 0.72rem;">Date of Birth / Gender</div>
                <div class="fw-bold text-dark" id="folderModalDobGender">—</div>
              </div>

              <div class="col-6 col-md-3">
                <div class="text-muted small" style="font-size: 0.72rem;">Mobile Phone</div>
                <div class="fw-bold text-dark" id="folderModalMobile">—</div>
              </div>
              <div class="col-6 col-md-3">
                <div class="text-muted small" style="font-size: 0.72rem;">WhatsApp</div>
                <div id="folderModalWhatsAppBox">
                  <span class="fw-bold text-dark">—</span>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="text-muted small" style="font-size: 0.72rem;">Email Address</div>
                <div class="fw-bold text-dark text-truncate" id="folderModalEmail">—</div>
              </div>
              <div class="col-6 col-md-3">
                <div class="text-muted small" style="font-size: 0.72rem;">Assigned Officer</div>
                <div class="fw-bold text-dark" id="folderModalStaff">—</div>
              </div>

              <div class="col-12">
                <div class="text-muted small" style="font-size: 0.72rem;">Current Address / City</div>
                <div class="fw-medium text-dark" id="folderModalAddress">—</div>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. FOLDER UPLOADED DOCUMENTS TABLE -->
        <div class="card rounded-4 border shadow-xs bg-white overflow-hidden">
          <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
              <span class="fw-bold small text-dark">
                <i class="fa-solid fa-folder-closed text-warning me-1.5"></i> Uploaded Documents in this Folder
              </span>
              <span class="badge bg-light text-muted border" id="folderModalDocCountBadge">0 files</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 shadow-xs" onclick="openUploadForCurrentApplicant()">
              <i class="fa-solid fa-cloud-arrow-up me-1"></i> + Upload to this Folder
            </button>
          </div>

          <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr class="small text-muted text-uppercase">
                  <th class="ps-3 py-2.5">Document File</th>
                  <th class="py-2.5">Category</th>
                  <th class="py-2.5">Status</th>
                  <th class="py-2.5">Expiry</th>
                  <th class="py-2.5">Uploaded</th>
                  <th class="pe-3 py-2.5 text-end">Actions</th>
                </tr>
              </thead>
              <tbody id="folderModalDocTableBody">
                <!-- Dynamically populated via JavaScript -->
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer bg-white border-top p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
          <a href="#" id="folderModalZipBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="fa-solid fa-file-zipper me-1 text-warning"></i> Download All (ZIP)
          </a>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="#" id="folderModalMasterWorkspaceBtn" class="btn btn-sm btn-outline-dark rounded-pill px-3">
            <i class="fa-solid fa-briefcase me-1"></i> Master Case Workspace
          </a>
          <a href="#" id="folderModalFullProfileBtn" class="btn btn-sm text-white rounded-pill px-3" style="background: var(--bento-primary);">
            <i class="fa-solid fa-folder-open me-1"></i> Full Document Workspace &rarr;
          </a>
        </div>
      </div>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════════════════════════════════════
     STANDARD ACTION MODALS (UPLOAD, PREVIEW, VERIFY, REJECT, ETC.)
     ══════════════════════════════════════════════════════════════════════════ -->

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocModal" tabindex="-1" aria-labelledby="uploadDocModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <form action="/documents/upload" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="application_id" id="uploadAppId" value="">
        <div class="modal-header bg-light border-bottom py-3">
          <h6 class="modal-title fw-bold text-dark" id="uploadDocModalTitle">
            <i class="fa-solid fa-cloud-arrow-up text-primary me-1.5"></i> Upload Document to Folder
          </h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <!-- Application Selector (if not pre-filled) -->
          <div class="mb-3" id="uploadAppSelectorGroup">
            <label class="form-label small fw-bold text-dark">Target Visa Application / Applicant *</label>
            <select name="application_id" id="uploadAppIdSelect" class="form-select form-select-sm" required>
              <option value="">Select applicant folder...</option>
              <?php foreach ($allFolders as $af): ?>
                <option value="<?= (int)$af['application_id'] ?>">
                  <?= e($af['customer_name']) ?> &mdash; <?= e($af['application_number']) ?> (<?= e($af['passport_number'] ?: 'No Passport') ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Document Type -->
          <div class="mb-3">
            <label class="form-label small fw-bold text-dark">Document Type *</label>
            <select name="document_type_id" class="form-select form-select-sm" required>
              <option value="">Select document type...</option>
              <?php foreach ($docTypes as $dt): ?>
                <option value="<?= (int)$dt['id'] ?>"><?= e($dt['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Title -->
          <div class="mb-3">
            <label class="form-label small fw-bold text-dark">Document Title (Optional)</label>
            <input type="text" name="document_title" class="form-control form-control-sm" placeholder="e.g. Passport Bio Page or Bank Statement">
          </div>

          <!-- File Upload -->
          <div class="mb-3">
            <label class="form-label small fw-bold text-dark">Choose File (PDF, JPG, PNG, DOCX &le; 10MB) *</label>
            <input type="file" name="document_file" class="form-control form-control-sm" required accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
          </div>

          <!-- Expiry Date -->
          <div class="mb-3">
            <label class="form-label small fw-bold text-dark">Expiry Date (if applicable)</label>
            <input type="date" name="expiry_date" class="form-control form-control-sm">
          </div>

          <!-- Notes -->
          <div class="mb-2">
            <label class="form-label small fw-bold text-dark">Notes</label>
            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Verification notes or notes for embassy..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light border-top py-2.5 px-3">
          <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 shadow-xs">
            <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload File
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header bg-light border-bottom py-2.5 px-3">
        <h6 class="modal-title fw-bold text-dark" id="previewModalTitle">Document Preview</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3 text-center" id="previewContainer">
        <div class="spinner-border text-primary" role="status"></div>
      </div>
    </div>
  </div>
</div>

<!-- Verify Modal -->
<div class="modal fade" id="verifyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <form action="/documents/verify" method="POST" id="verifyDocForm">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="verifyDocId" value="">
        <div class="modal-header bg-light border-bottom py-3">
          <h6 class="modal-title fw-bold text-success"><i class="fa-solid fa-circle-check me-1.5"></i> Verify Document</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <p class="mb-3">Confirm compliance and mark <strong id="verifyDocTitle">this document</strong> as verified?</p>
          <div class="mb-2">
            <label class="form-label small fw-bold text-dark">Verification Notes (Optional)</label>
            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="e.g. Scanned copy verified against embassy criteria"></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light border-top py-2.5 px-3">
          <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="btnConfirmVerify" class="btn btn-sm btn-success rounded-pill px-4 shadow-xs">Verify Document</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <form action="/documents/reject" method="POST" id="rejectDocForm">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="rejectDocId" value="">
        <div class="modal-header bg-light border-bottom py-3">
          <h6 class="modal-title fw-bold text-danger"><i class="fa-solid fa-circle-xmark me-1.5"></i> Reject Document</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <p class="mb-3">Specify reason for rejecting <strong id="rejectDocTitle">this document</strong>:</p>
          <div class="mb-2">
            <label class="form-label small fw-bold text-dark">Rejection Reason *</label>
            <textarea name="rejection_reason" class="form-control form-control-sm" rows="3" required placeholder="e.g. Passport image is blurred, needs high resolution colored scan"></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light border-top py-2.5 px-3">
          <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="btnConfirmReject" class="btn btn-sm btn-danger rounded-pill px-4 shadow-xs">Reject Document</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteDocModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <form action="/documents/delete" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="deleteDocId" value="">
        <div class="modal-header bg-light border-bottom py-3">
          <h6 class="modal-title fw-bold text-danger"><i class="fa-solid fa-trash-can me-1.5"></i> Delete Document</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <p class="mb-0">Are you sure you want to permanently delete <strong id="deleteDocTitle">this document</strong>?</p>
        </div>
        <div class="modal-footer bg-light border-top py-2.5 px-3">
          <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 shadow-xs">Delete Permanently</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════════════════════════════════════
     INTERACTIVE JAVASCRIPT LOGIC
     ══════════════════════════════════════════════════════════════════════════ -->
<script>
let activeFolderData = null;

function toggleFolderView(view) {
  const treeView = document.getElementById('folderTreeView');
  const cardsView = document.getElementById('folderCardsView');
  const btnTree = document.getElementById('btnViewTree');
  const btnCards = document.getElementById('btnViewCards');

  if (view === 'cards') {
    if (treeView) treeView.classList.add('d-none');
    if (cardsView) cardsView.classList.remove('d-none');
    if (btnTree) btnTree.classList.remove('active');
    if (btnCards) btnCards.classList.add('active');
    try { localStorage.setItem('vt_doc_pref_view', 'cards'); } catch(e) {}
  } else {
    if (cardsView) cardsView.classList.add('d-none');
    if (treeView) treeView.classList.remove('d-none');
    if (btnCards) btnCards.classList.remove('active');
    if (btnTree) btnTree.classList.add('active');
    try { localStorage.setItem('vt_doc_pref_view', 'tree'); } catch(e) {}
  }
}

// Open Applicant Folder Details Modal
function openApplicantFolderModal(el) {
  try {
    const raw = el.getAttribute('data-folder');
    if (!raw) return;
    const f = JSON.parse(raw);
    activeFolderData = f;
    renderFolderModal(f);
  } catch(e) {
    console.error('Failed to parse folder data:', e);
  }
}

function triggerApplicantFolderFromBtn(btn) {
  try {
    const raw = btn.getAttribute('data-folder');
    if (!raw) return;
    const f = JSON.parse(raw);
    activeFolderData = f;
    renderFolderModal(f);
  } catch(e) {
    console.error('Failed to parse folder data:', e);
  }
}

function renderFolderModal(f) {
  // Populate Header
  document.getElementById('folderModalApplicantName').textContent = f.customer_name || 'Applicant';
  document.getElementById('folderModalCustCode').textContent = f.customer_code || 'MSC-000000';
  document.getElementById('folderModalAppStatus').textContent = f.application_status || 'Draft';
  document.getElementById('folderModalAppNumber').textContent = f.application_number || '';
  document.getElementById('folderModalServiceCountry').textContent = (f.flag_emoji || '🌐') + ' ' + (f.country_name || 'Destination') + ' &bull; ' + (f.service_name || 'Visa');
  document.getElementById('folderModalStageBadge').textContent = f.current_stage || 'Application Registered';

  // Populate Personal Details
  document.getElementById('folderModalPassport').textContent = f.passport_number || '—';
  document.getElementById('folderModalPassportExpiry').textContent = f.passport_expiry_date ? f.passport_expiry_date : '—';
  document.getElementById('folderModalNationality').textContent = (f.nationality || '—') + (f.current_country ? ' (' + f.current_country + ')' : '');
  
  let dobText = f.dob ? f.dob : '—';
  if (f.gender) dobText += ' / ' + f.gender;
  document.getElementById('folderModalDobGender').textContent = dobText;

  document.getElementById('folderModalMobile').textContent = f.mobile || '—';
  document.getElementById('folderModalEmail').textContent = f.email || '—';
  document.getElementById('folderModalStaff').textContent = f.assigned_staff_name || 'Operations Desk';

  let addrText = f.address || '';
  if (f.city) addrText = (addrText ? addrText + ', ' : '') + f.city;
  document.getElementById('folderModalAddress').textContent = addrText || '—';

  // WhatsApp 1-Click Action
  const waBox = document.getElementById('folderModalWhatsAppBox');
  const waNum = (f.whatsapp || f.mobile || '').replace(/[^0-9]/g, '');
  if (waNum) {
    waBox.innerHTML = '<a href="https://wa.me/' + waNum + '?text=' + encodeURIComponent('Hello ' + (f.customer_name || 'Applicant') + ', regarding your visa file ' + (f.application_number || '') + ':') + '" target="_blank" class="btn btn-sm btn-outline-success rounded-pill px-2.5 py-0.5 fw-semibold" style="font-size:0.75rem;"><i class="fa-brands fa-whatsapp me-1"></i> ' + (f.whatsapp || f.mobile) + '</a>';
  } else {
    waBox.innerHTML = '<span class="fw-bold text-dark">—</span>';
  }

  // Populate Action Footer URLs
  document.getElementById('folderModalZipBtn').href = '/documents/download-all?application_id=' + f.application_id;
  document.getElementById('folderModalMasterWorkspaceBtn').href = '/applications/show?id=' + f.application_id;
  document.getElementById('folderModalFullProfileBtn').href = '/documents/profile?application_id=' + f.application_id;

  // Populate Uploaded Documents Table
  const docs = f.uploaded_documents || [];
  document.getElementById('folderModalDocCountBadge').textContent = docs.length + ' files';

  const tbody = document.getElementById('folderModalDocTableBody');
  tbody.innerHTML = '';

  if (docs.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted"><i class="fa-solid fa-folder-open text-secondary mb-2 fs-4 d-block"></i>No documents uploaded yet in this folder.<br><button type="button" class="btn btn-sm btn-outline-primary rounded-pill mt-2" onclick="openUploadForCurrentApplicant()"><i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload First Document</button></td></tr>';
  } else {
    docs.forEach(d => {
      const ext = (d.file_name || '').split('.').pop().toLowerCase();
      let iconClass = 'fa-file text-secondary';
      if (ext === 'pdf') iconClass = 'fa-file-pdf text-danger';
      else if (['jpg', 'jpeg', 'png', 'webp'].includes(ext)) iconClass = 'fa-file-image text-primary';
      else if (['doc', 'docx'].includes(ext)) iconClass = 'fa-file-word text-info';

      let statusBadge = 'badge bg-secondary';
      if (d.status === 'VERIFIED') statusBadge = 'badge bg-success';
      else if (d.status === 'REJECTED') statusBadge = 'badge bg-danger';
      else if (d.status === 'UNDER_REVIEW') statusBadge = 'badge bg-warning text-dark';

      const fileSizeKb = d.file_size ? (parseFloat(d.file_size) / 1024).toFixed(1) + ' KB' : '—';

      const tr = document.createElement('tr');
      tr.className = 'bento-file-table-row';
      tr.innerHTML = `
        <td class="ps-3 py-2.5">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid ${iconClass} fs-5"></i>
            <div>
              <div class="fw-bold text-dark small">${escapeHtml(d.document_title || d.doc_type_name || 'Document')}</div>
              <div class="text-muted" style="font-size: 0.7rem;">${escapeHtml(d.file_name)} &bull; ${fileSizeKb}</div>
            </div>
          </div>
        </td>
        <td class="py-2.5">
          <span class="badge bg-light text-secondary border" style="font-size:0.7rem;">${escapeHtml(d.category || 'General')}</span>
        </td>
        <td class="py-2.5">
          <span class="${statusBadge}" style="font-size:0.72rem;">${escapeHtml(d.status || 'UPLOADED')}</span>
        </td>
        <td class="py-2.5 small text-muted" style="font-size:0.75rem;">
          ${d.expiry_date ? d.expiry_date : '—'}
        </td>
        <td class="py-2.5 small text-muted" style="font-size:0.72rem;">
          ${d.created_at ? d.created_at.substring(0, 10) : '—'}
        </td>
        <td class="pe-3 py-2.5 text-end">
          <div class="d-flex align-items-center justify-content-end gap-1">
            <button type="button" class="btn btn-sm btn-light border rounded-pill px-2 py-1 text-primary" 
                    onclick="openDocPreviewModal(${d.id}, '${escapeJs(d.file_name)}', '${escapeJs(d.document_title || d.doc_type_name)}')" 
                    title="Preview Document">
              <i class="fa-solid fa-eye small"></i>
            </button>
            <a href="/documents/download?id=${d.id}" class="btn btn-sm btn-light border rounded-pill px-2 py-1 text-secondary" title="Download">
              <i class="fa-solid fa-download small"></i>
            </a>
            ${d.status !== 'VERIFIED' ? `
              <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-2 py-1" onclick="triggerVerifyDoc(${d.id}, '${escapeJs(d.document_title || d.doc_type_name)}')" title="Mark as Verified">
                <i class="fa-solid fa-check small"></i>
              </button>
            ` : ''}
            ${d.status !== 'REJECTED' ? `
              <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1" onclick="triggerRejectDoc(${d.id}, '${escapeJs(d.document_title || d.doc_type_name)}')" title="Reject Document">
                <i class="fa-solid fa-xmark small"></i>
              </button>
            ` : ''}
            <button type="button" class="btn btn-sm btn-light border rounded-pill px-2 py-1 text-danger" onclick="triggerDeleteDoc(${d.id}, '${escapeJs(d.document_title || d.doc_type_name)}')" title="Delete">
              <i class="fa-solid fa-trash-can small"></i>
            </button>
          </div>
        </td>
      `;
      tbody.appendChild(tr);
    });
  }

  // Show Modal
  const modalEl = document.getElementById('applicantFolderModal');
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
}

function openUploadForCurrentApplicant() {
  if (!activeFolderData) return;
  const appId = activeFolderData.application_id;
  const select = document.getElementById('uploadAppIdSelect');
  if (select) {
    select.value = appId;
  }
  const hiddenInput = document.getElementById('uploadAppId');
  if (hiddenInput) {
    hiddenInput.value = appId;
  }
  const uploadModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('uploadDocModal'));
  uploadModal.show();
}

function openDocPreviewModal(id, filename, title) {
  const container = document.getElementById('previewContainer');
  document.getElementById('previewModalTitle').textContent = title || filename;
  const ext = (filename || '').split('.').pop().toLowerCase();

  if (container) {
    if (ext === 'pdf') {
      container.innerHTML = '<iframe src="/documents/preview?id=' + id + '" style="width: 100%; height: 600px; border: none; border-radius: 6px;"></iframe>';
    } else if (['jpg', 'jpeg', 'png', 'webp'].includes(ext)) {
      container.innerHTML = '<img src="/documents/preview?id=' + id + '" class="img-fluid rounded shadow-sm" style="max-height: 550px; object-fit: contain;">';
    } else {
      container.innerHTML = '<div class="p-5 text-center"><i class="fa-solid fa-file-lines fs-1 text-secondary mb-3"></i><h5>Preview not supported for ' + ext.toUpperCase() + '</h5><a href="/documents/download?id=' + id + '" class="btn btn-primary btn-sm rounded-pill mt-2"><i class="fa-solid fa-download me-1"></i> Download File</a></div>';
    }
  }
  bootstrap.Modal.getOrCreateInstance(document.getElementById('previewModal')).show();
}

function triggerVerifyDoc(id, title) {
  document.getElementById('verifyDocId').value = id;
  document.getElementById('verifyDocTitle').textContent = title;
  bootstrap.Modal.getOrCreateInstance(document.getElementById('verifyModal')).show();
}

function triggerRejectDoc(id, title) {
  document.getElementById('rejectDocId').value = id;
  document.getElementById('rejectDocTitle').textContent = title;
  bootstrap.Modal.getOrCreateInstance(document.getElementById('rejectModal')).show();
}

function triggerDeleteDoc(id, title) {
  document.getElementById('deleteDocId').value = id;
  document.getElementById('deleteDocTitle').textContent = title;
  bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteDocModal')).show();
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/[&<>"']/g, function(m) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
  });
}

function escapeJs(str) {
  if (!str) return '';
  return String(str).replace(/'/g, "\\'").replace(/"/g, '\\"');
}

// Restore user view preference
document.addEventListener('DOMContentLoaded', function() {
  const pref = (function() {
    try { return localStorage.getItem('vt_doc_pref_view') || 'tree'; } catch(e) { return 'tree'; }
  })();
  toggleFolderView(pref);
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
