<?php
$pageTitle = 'DOCUMENT MANAGEMENT — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
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

  <!-- 1. Page Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
      <h3 class="fw-bold brand-font mb-0" style="color: #0f172a;">DOCUMENT MANAGEMENT</h3>
      <p class="text-muted small mb-0">Manage, verify and monitor visa application documents.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="/documents/export-csv?<?= http_build_query($_GET) ?>" class="btn btn-outline-success btn-sm px-3 shadow-sm" title="Export filtered documents to CSV">
        <i class="fa-solid fa-file-csv me-1"></i> Export CSV
      </a>
      <a href="/applications" class="btn btn-outline-secondary btn-sm px-3">
        <i class="fa-solid fa-folder-open me-1"></i> Visa Applications
      </a>
      <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
        <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload Document
      </button>
    </div>
  </div>

  <!-- 2. Real Database Statistics Cards (Vibrant 6 Grid) -->
  <div class="row g-2 g-md-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
      <a href="/documents" class="text-decoration-none d-block h-100">
        <div class="stat-card stat-card-blue h-100">
          <div class="stat-icon-wrapper">
            <i class="fa-solid fa-folder-tree"></i>
          </div>
          <div class="stat-card-content">
            <div class="stat-title">Folders</div>
            <div class="stat-value"><?= $stats['total_folders'] ?? count($folders) ?></div>
            <div class="stat-trend"><i class="fa-solid fa-users me-1"></i>Applicant files</div>
          </div>
        </div>
      </a>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
      <a href="/documents?status=UNDER_REVIEW" class="text-decoration-none d-block h-100">
        <div class="stat-card stat-card-warning h-100">
          <div class="stat-icon-wrapper">
            <i class="fa-solid fa-clock"></i>
          </div>
          <div class="stat-card-content">
            <div class="stat-title">Pending Review</div>
            <div class="stat-value"><?= $stats['pending_review'] ?></div>
            <div class="stat-trend"><i class="fa-solid fa-hourglass-start me-1"></i>Needs check</div>
          </div>
        </div>
      </a>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
      <a href="/documents?status=VERIFIED" class="text-decoration-none d-block h-100">
        <div class="stat-card stat-card-success h-100">
          <div class="stat-icon-wrapper">
            <i class="fa-solid fa-circle-check"></i>
          </div>
          <div class="stat-card-content">
            <div class="stat-title">Verified</div>
            <div class="stat-value"><?= $stats['verified'] ?></div>
            <div class="stat-trend"><i class="fa-solid fa-shield-check me-1"></i>Compliant</div>
          </div>
        </div>
      </a>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
      <a href="/documents?status=REJECTED" class="text-decoration-none d-block h-100">
        <div class="stat-card stat-card-danger h-100">
          <div class="stat-icon-wrapper">
            <i class="fa-solid fa-circle-xmark"></i>
          </div>
          <div class="stat-card-content">
            <div class="stat-title">Rejected</div>
            <div class="stat-value"><?= $stats['rejected'] ?></div>
            <div class="stat-trend"><i class="fa-solid fa-ban me-1"></i>Resubmit</div>
          </div>
        </div>
      </a>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
      <a href="/documents?status=EXPIRING_SOON" class="text-decoration-none d-block h-100">
        <div class="stat-card stat-card-warning h-100">
          <div class="stat-icon-wrapper">
            <i class="fa-solid fa-triangle-exclamation"></i>
          </div>
          <div class="stat-card-content">
            <div class="stat-title">Expiring Soon</div>
            <div class="stat-value"><?= $stats['expiring_soon'] ?></div>
            <div class="stat-trend"><i class="fa-solid fa-clock me-1"></i>&le; 30 days</div>
          </div>
        </div>
      </a>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
      <a href="/documents?status=EXPIRED" class="text-decoration-none d-block h-100">
        <div class="stat-card stat-card-purple h-100">
          <div class="stat-icon-wrapper">
            <i class="fa-solid fa-calendar-xmark"></i>
          </div>
          <div class="stat-card-content">
            <div class="stat-title">Expired Files</div>
            <div class="stat-value"><?= $stats['expired'] ?></div>
            <div class="stat-trend"><i class="fa-solid fa-fire me-1"></i>Action required</div>
          </div>
        </div>
      </a>
    </div>
  </div>

  <!-- 3. Advanced Multi-Filter Toolbar (14 Comprehensive Filters) -->
  <div class="card card-enterprise mb-4 bg-white shadow-sm border">
    <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
      <span class="fw-bold small text-dark"><i class="fa-solid fa-filter me-1 text-primary"></i> Advanced Filter Bar (14 Filters)</span>
      <span class="badge bg-secondary-subtle text-secondary small">Filtered: <?= $totalRecords ?> total records</span>
    </div>
    <div class="card-body p-3">
      <form action="/documents" method="GET" class="row g-2 align-items-center">
        <!-- 1. Search Keyword -->
        <div class="col-12 col-sm-6 col-md-4 col-xl-3">
          <label class="form-label text-muted" style="font-size:0.75rem; margin-bottom:2px;">Search Keyword</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Title, applicant, passport, file..." value="<?= e($_GET['search'] ?? '') ?>">
          </div>
        </div>

        <!-- 2. Status Filter -->
        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <label class="form-label text-muted" style="font-size:0.75rem; margin-bottom:2px;">Compliance Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">All Statuses</option>
            <option value="UNDER_REVIEW" <?= ($_GET['status'] ?? '') === 'UNDER_REVIEW' ? 'selected' : '' ?>>Under Review</option>
            <option value="VERIFIED" <?= ($_GET['status'] ?? '') === 'VERIFIED' ? 'selected' : '' ?>>Verified</option>
            <option value="REJECTED" <?= ($_GET['status'] ?? '') === 'REJECTED' ? 'selected' : '' ?>>Rejected</option>
            <option value="EXPIRING_SOON" <?= ($_GET['status'] ?? '') === 'EXPIRING_SOON' ? 'selected' : '' ?>>Expiring Soon (&le;30d)</option>
            <option value="EXPIRED" <?= ($_GET['status'] ?? '') === 'EXPIRED' ? 'selected' : '' ?>>Expired</option>
          </select>
        </div>

        <!-- 3. Document Type Filter -->
        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <label class="form-label text-muted" style="font-size:0.75rem; margin-bottom:2px;">Document Type</label>
          <select name="type_id" class="form-select form-select-sm">
            <option value="">All Types</option>
            <?php foreach ($docTypes as $dt): ?>
              <option value="<?= $dt['id'] ?>" <?= ((int)($_GET['type_id'] ?? 0)) === (int)$dt['id'] ? 'selected' : '' ?>>
                <?= e($dt['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- 4. Category Filter -->
        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <label class="form-label text-muted" style="font-size:0.75rem; margin-bottom:2px;">Doc Category</label>
          <select name="category" class="form-select form-select-sm">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat ?>" <?= ($_GET['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- 5. Visa Service Package Filter -->
        <div class="col-6 col-sm-6 col-md-4 col-xl-3">
          <label class="form-label text-muted" style="font-size:0.75rem; margin-bottom:2px;">Visa Package</label>
          <select name="service_id" class="form-select form-select-sm">
            <option value="">All Visa Packages</option>
            <?php foreach ($services as $srv): ?>
              <option value="<?= $srv['id'] ?>" <?= ((int)($_GET['service_id'] ?? 0)) === (int)$srv['id'] ? 'selected' : '' ?>>
                <?= e($srv['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- 6. Expiry Filter -->
        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <label class="form-label text-muted" style="font-size:0.75rem; margin-bottom:2px;">Expiry Alert</label>
          <select name="expiry" class="form-select form-select-sm">
            <option value="">Any Expiry</option>
            <option value="7days" <?= ($_GET['expiry'] ?? '') === '7days' ? 'selected' : '' ?>>Expires in &le;7 days</option>
            <option value="30days" <?= ($_GET['expiry'] ?? '') === '30days' ? 'selected' : '' ?>>Expires in &le;30 days</option>
            <option value="expired" <?= ($_GET['expiry'] ?? '') === 'expired' ? 'selected' : '' ?>>Already Expired</option>
          </select>
        </div>

        <!-- 7. Destination Country Filter -->
        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <label class="form-label text-muted" style="font-size:0.75rem; margin-bottom:2px;">Destination Country</label>
          <select name="country_id" class="form-select form-select-sm">
            <option value="">All Countries</option>
            <?php foreach ($countries as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ((int)($_GET['country_id'] ?? 0)) === (int)$c['id'] ? 'selected' : '' ?>>
                <?= $c['flag_emoji'] ?> <?= e($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- 8. Assigned Staff Member -->
        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <label class="form-label text-muted" style="font-size:0.75rem; margin-bottom:2px;">Assigned Staff</label>
          <select name="staff_id" class="form-select form-select-sm">
            <option value="">All Staff</option>
            <?php foreach ($staffMembers as $sm): ?>
              <option value="<?= $sm['id'] ?>" <?= ((int)($_GET['staff_id'] ?? 0)) === (int)$sm['id'] ? 'selected' : '' ?>>
                <?= e($sm['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- 9. Specific Customer Filter -->
        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <label class="form-label text-muted" style="font-size:0.75rem; margin-bottom:2px;">Customer / Applicant</label>
          <select name="customer_id" class="form-select form-select-sm">
            <option value="">All Customers</option>
            <?php foreach ($customers as $cust): ?>
              <option value="<?= $cust['id'] ?>" <?= ((int)($_GET['customer_id'] ?? 0)) === (int)$cust['id'] ? 'selected' : '' ?>>
                <?= e($cust['full_name']) ?> (<?= e($cust['customer_code']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- 10. Uploaded By Filter -->
        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <label class="form-label text-muted" style="font-size:0.75rem; margin-bottom:2px;">Uploaded By</label>
          <select name="uploaded_by_type" class="form-select form-select-sm">
            <option value="">All Sources</option>
            <option value="Staff" <?= ($_GET['uploaded_by_type'] ?? '') === 'Staff' ? 'selected' : '' ?>>Staff</option>
            <option value="Customer" <?= ($_GET['uploaded_by_type'] ?? '') === 'Customer' ? 'selected' : '' ?>>Customer</option>
            <option value="System" <?= ($_GET['uploaded_by_type'] ?? '') === 'System' ? 'selected' : '' ?>>System</option>
          </select>
        </div>

        <!-- 11. File Format Filter -->
        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <label class="form-label text-muted" style="font-size:0.75rem; margin-bottom:2px;">File Extension</label>
          <select name="file_format" class="form-select form-select-sm">
            <option value="">All Formats</option>
            <option value="pdf" <?= ($_GET['file_format'] ?? '') === 'pdf' ? 'selected' : '' ?>>PDF (.pdf)</option>
            <option value="jpg" <?= ($_GET['file_format'] ?? '') === 'jpg' ? 'selected' : '' ?>>JPEG (.jpg/.jpeg)</option>
            <option value="png" <?= ($_GET['file_format'] ?? '') === 'png' ? 'selected' : '' ?>>PNG (.png)</option>
            <option value="docx" <?= ($_GET['file_format'] ?? '') === 'docx' ? 'selected' : '' ?>>DOCX (.docx)</option>
          </select>
        </div>

        <!-- 12. Date Range: From -->
        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <label class="form-label text-muted" style="font-size:0.75rem; margin-bottom:2px;">Upload Date From</label>
          <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($_GET['date_from'] ?? '') ?>" title="Uploaded From">
        </div>

        <!-- 13. Date Range: To -->
        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <label class="form-label text-muted" style="font-size:0.75rem; margin-bottom:2px;">Upload Date To</label>
          <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($_GET['date_to'] ?? '') ?>" title="Uploaded To">
        </div>

        <!-- 14. Per Page -->
        <div class="col-6 col-sm-6 col-md-4 col-xl-1">
          <label class="form-label text-muted" style="font-size:0.75rem; margin-bottom:2px;">Per Page</label>
          <select name="per_page" class="form-select form-select-sm">
            <option value="15" <?= ((int)($_GET['per_page'] ?? 15)) === 15 ? 'selected' : '' ?>>15</option>
            <option value="25" <?= ((int)($_GET['per_page'] ?? 15)) === 25 ? 'selected' : '' ?>>25</option>
            <option value="50" <?= ((int)($_GET['per_page'] ?? 15)) === 50 ? 'selected' : '' ?>>50</option>
            <option value="100" <?= ((int)($_GET['per_page'] ?? 15)) === 100 ? 'selected' : '' ?>>100</option>
          </select>
        </div>

        <!-- Action Buttons -->
        <div class="col-12 col-xl-3 ms-auto d-flex align-items-end justify-content-end gap-2 pt-2">
          <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm">
            <i class="fa-solid fa-filter me-1"></i> Apply Filters
          </button>
          <a href="/documents" class="btn btn-light border btn-sm px-3" title="Clear / Reset Filters">
            <i class="fa-solid fa-rotate-left me-1"></i> Reset
          </a>
          <a href="/documents/export-csv?<?= http_build_query($_GET) ?>" class="btn btn-outline-success btn-sm px-3" title="Export current search to CSV">
            <i class="fa-solid fa-download me-1"></i> CSV
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- 4. Document Directory Table / Responsive Cards -->
  <div class="card card-enterprise bg-white">
    <div class="card-header bg-white border-bottom p-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div class="fw-bold text-dark fs-6 d-flex align-items-center gap-2">
        <i class="fa-solid fa-folder-closed text-primary"></i>
        <span id="docRegistryTitle">Applicant Folders Directory</span>
        <span class="badge bg-light text-muted border" id="docRegistryCountBadge"><?= count($folders) ?> folders</span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <!-- 4 View Options Switcher (Responsive) -->
        <div class="btn-group btn-group-sm bg-white shadow-sm border rounded-pill p-1 view-switcher-pill-group" role="group" aria-label="View Mode">
          <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 fw-semibold doc-view-btn btn-primary shadow-sm" id="docViewFoldersBtn" onclick="setDocumentView('folders')" title="Applicant Folder View">
            <i class="fa-solid fa-folder me-1"></i> <span class="d-none d-sm-inline">Folders</span>
          </button>
          <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 fw-semibold doc-view-btn btn-light text-muted" id="docViewListBtn" onclick="setDocumentView('table')" title="Table View">
            <i class="fa-solid fa-table-list me-1"></i> <span class="d-none d-sm-inline">Table</span>
          </button>
          <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 fw-semibold doc-view-btn btn-light text-muted" id="docViewGridBtn" onclick="setDocumentView('grid')" title="Card Grid View">
            <i class="fa-solid fa-grip me-1"></i> <span class="d-none d-sm-inline">Cards</span>
          </button>
          <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 fw-semibold doc-view-btn btn-light text-muted" id="docViewCompactBtn" onclick="setDocumentView('compact')" title="Compact List View">
            <i class="fa-solid fa-list-ul me-1"></i> <span class="d-none d-sm-inline">Compact</span>
          </button>
        </div>
      </div>
    </div>

    <!-- 1. PRIMARY PRESENTATION: APPLICANT FOLDER DIRECTORY -->
    <div id="documentFolderView" class="p-3">
      <?php if (empty($folders)): ?>
        <div class="empty-state-box p-5 text-center">
          <div class="empty-state-icon mb-3" style="font-size: 2.5rem; color: #94a3b8;">
            <i class="fa-solid fa-folder-open"></i>
          </div>
          <h5 class="fw-bold mb-1">No applicant folders found</h5>
          <p class="text-muted small mb-3">No applicant files match your current search criteria.</p>
          <a href="/documents" class="btn btn-outline-secondary btn-sm me-2">Clear Filters</a>
          <a href="/applications/create" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus me-1"></i> New Application
          </a>
        </div>
      <?php else: ?>
        <div class="applicant-folder-grid">
          <?php foreach ($folders as $f): ?>
            <?php
              $fStatusBadge = 'bg-secondary';
              if ($f['application_status'] === 'Approved') $fStatusBadge = 'bg-success';
              elseif ($f['application_status'] === 'Pending') $fStatusBadge = 'bg-warning text-dark';
              elseif ($f['application_status'] === 'Rejected') $fStatusBadge = 'bg-danger';
              elseif ($f['application_status'] === 'In Process') $fStatusBadge = 'bg-primary';
              elseif ($f['application_status'] === 'Draft') $fStatusBadge = 'bg-info text-dark';
            ?>
            <div class="applicant-folder-card">
              <div class="folder-card-header">
                <div class="folder-tab-badge">
                  <i class="fa-solid fa-folder-open text-primary"></i>
                  <span class="font-monospace"><?= e($f['application_number']) ?></span>
                </div>
                <span class="badge <?= $fStatusBadge ?> px-2 py-1" style="font-size: 0.72rem;"><?= e($f['application_status']) ?></span>
              </div>
              <div class="folder-card-body">
                <div class="folder-applicant-meta">
                  <div class="folder-avatar-wrapper">
                    <?php
                      $nameParts = explode(' ', trim((string)($f['customer_name'] ?? '')));
                      $firstInitial = !empty($nameParts[0]) ? mb_substr($nameParts[0], 0, 1) : 'A';
                      $lastInitial = count($nameParts) > 1 ? mb_substr(end($nameParts), 0, 1) : '';
                      $initials = strtoupper($firstInitial . $lastInitial) ?: 'AP';
                    ?>
                    <?php if (!empty($f['photo_doc_id'])): ?>
                      <img src="/documents/preview?id=<?= (int)$f['photo_doc_id'] ?>" 
                           alt="<?= e($f['customer_name']) ?>" 
                           class="folder-avatar-img" 
                           loading="lazy"
                           onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                      <div class="folder-avatar-initials" style="display:none;"><?= e($initials) ?></div>
                    <?php else: ?>
                      <div class="folder-avatar-initials"><?= e($initials) ?></div>
                    <?php endif; ?>
                  </div>
                  <div class="min-w-0 flex-grow-1">
                    <h6 class="folder-name-title mb-0">
                      <a href="/documents/profile?application_id=<?= (int)$f['application_id'] ?>" class="text-dark text-decoration-none">
                        <?= e($f['customer_name']) ?>
                      </a>
                    </h6>
                    <div class="text-muted small">
                      <span class="font-monospace text-secondary fw-semibold"><?= e($f['customer_code'] ?? 'MSC-000000') ?></span>
                      <?php if (!empty($f['passport_number'])): ?>
                        &bull; <i class="fa-solid fa-passport text-muted"></i> <?= e($f['passport_number']) ?>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 small">
                  <div class="folder-route-pill">
                    <span><?= e($f['nationality'] ?: 'Applicant') ?></span>
                    <i class="fa-solid fa-arrow-right-long text-secondary" style="font-size: 0.65rem;"></i>
                    <span><?= e($f['flag_emoji'] ?? '🌐') ?> <?= e($f['country_name'] ?? 'UAE') ?></span>
                  </div>
                  <span class="badge bg-light text-secondary border"><?= e($f['current_stage']) ?></span>
                </div>

                <!-- 4 Stats Strip: Total Docs, Verified, Pending, Missing -->
                <div class="folder-doc-stats-strip">
                  <div class="folder-stat-item">
                    <span class="folder-stat-num text-dark"><?= (int)$f['total_docs'] ?></span>
                    <span class="folder-stat-lbl">Documents</span>
                  </div>
                  <div class="folder-stat-item">
                    <span class="folder-stat-num text-success"><?= (int)$f['verified_docs'] ?></span>
                    <span class="folder-stat-lbl">Verified</span>
                  </div>
                  <div class="folder-stat-item">
                    <span class="folder-stat-num text-warning"><?= (int)$f['pending_docs'] ?></span>
                    <span class="folder-stat-lbl">Pending</span>
                  </div>
                  <div class="folder-stat-item">
                    <span class="folder-stat-num <?= ((int)($f['missing_docs'] ?? 0) > 0) ? 'text-danger' : 'text-muted' ?>">
                      <?= (int)($f['missing_docs'] ?? 0) ?>
                    </span>
                    <span class="folder-stat-lbl">Missing</span>
                  </div>
                </div>
              </div>

              <div class="folder-card-footer">
                <a href="/documents/profile?application_id=<?= (int)$f['application_id'] ?>" class="btn btn-primary btn-sm btn-view-profile shadow-sm">
                  <i class="fa-solid fa-folder-open me-1"></i> VIEW PROFILE
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Folder Pagination -->
        <?php if ($folderTotalPages > 1): ?>
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 mt-3 border-top">
            <div class="small text-muted">
              Showing <?= (($folderPage - 1) * $folderPerPage) + 1 ?> to <?= min($totalFolders, $folderPage * $folderPerPage) ?> of <?= $totalFolders ?> applicant folders
            </div>
            <ul class="pagination pagination-sm mb-0">
              <?php if ($folderPage > 1): ?>
                <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['f_page' => $folderPage - 1])) ?>">&laquo; Prev</a></li>
              <?php endif; ?>
              <?php for ($p = 1; $p <= $folderTotalPages; $p++): ?>
                <li class="page-item <?= ($p === $folderPage) ? 'active' : '' ?>">
                  <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['f_page' => $p])) ?>"><?= $p ?></a>
                </li>
              <?php endfor; ?>
              <?php if ($folderPage < $folderTotalPages): ?>
                <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['f_page' => $folderPage + 1])) ?>">Next &raquo;</a></li>
              <?php endif; ?>
            </ul>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <!-- 2. Flat Document Registry Views (Table, Cards, Compact) -->
    <?php if (empty($documents)): ?>
      <div id="documentEmptyState" class="empty-state-box p-5 text-center d-none">
        <div class="empty-state-icon mb-3" style="font-size: 2.5rem; color: #94a3b8;">
          <i class="fa-solid fa-folder-open"></i>
        </div>
        <h5 class="fw-bold mb-1">No documents found</h5>
        <p class="text-muted small mb-3">No uploaded documents match your active filter criteria.</p>
        <a href="/documents" class="btn btn-outline-secondary btn-sm me-2">Clear Filters</a>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
          <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload Document
        </button>
      </div>
    <?php else: ?>
      <!-- Desktop & Tablet Table -->
      <div class="table-responsive d-none d-md-block" id="documentTableView">
        <table class="table-custom">
          <thead>
            <tr>
              <th>Document</th>
              <th>Applicant &amp; Passport</th>
              <th>Application ID</th>
              <th>Document Type</th>
              <th>Status</th>
              <th>Expiry</th>
              <th>Uploaded By</th>
              <th>Verified By</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($documents as $doc): ?>
              <?php
                $statusBadgeClass = 'badge bg-secondary';
                if ($doc['status'] === 'VERIFIED') $statusBadgeClass = 'badge bg-success';
                elseif ($doc['status'] === 'REJECTED') $statusBadgeClass = 'badge bg-danger';
                elseif ($doc['status'] === 'UNDER_REVIEW') $statusBadgeClass = 'badge bg-warning text-dark';
                
                $ext = strtolower(pathinfo($doc['file_name'] ?? '', PATHINFO_EXTENSION));
                $fileIcon = 'fa-file';
                if ($ext === 'pdf') $fileIcon = 'fa-file-pdf text-danger';
                elseif (in_array($ext, ['jpg', 'jpeg', 'png'], true)) $fileIcon = 'fa-file-image text-primary';
                elseif (in_array($ext, ['doc', 'docx'], true)) $fileIcon = 'fa-file-word text-info';
              ?>
              <tr>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid <?= $fileIcon ?> fs-5"></i>
                    <div>
                      <div class="fw-bold text-dark"><?= e($doc['document_title']) ?></div>
                      <div class="text-muted small" style="font-size: 0.72rem;">
                        <span><?= e($doc['file_name']) ?></span> &bull; 
                        <span>v<?= (int)$doc['version'] ?></span> &bull; 
                        <span><?= number_format(((float)$doc['file_size']) / 1024, 1) ?> KB</span>
                      </div>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="fw-semibold text-dark"><?= e($doc['customer_name']) ?></div>
                  <div class="text-muted small" style="font-size: 0.72rem;">
                    <i class="fa-solid fa-passport text-secondary me-1"></i><?= e($doc['passport_number'] ?: '—') ?>
                  </div>
                </td>
                <td>
                  <?php if (!empty($doc['application_number'])): ?>
                    <a href="/applications/show?id=<?= $doc['app_id'] ?>" class="fw-bold text-primary text-decoration-none">
                      <?= e($doc['application_number']) ?>
                    </a>
                  <?php else: ?>
                    <span class="badge bg-light text-muted border">General</span>
                  <?php endif; ?>
                </td>
                <td>
                  <!-- Interactive Status Dropdown -->
                  <div class="dropdown d-inline-block">
                    <button class="btn btn-sm dropdown-toggle p-0 border-0 bg-transparent text-decoration-none shadow-none" 
                            type="button" 
                            data-bs-toggle="dropdown" 
                            data-bs-boundary="viewport"
                            aria-expanded="false" 
                            title="Click to update document status">
                      <span class="<?= $statusBadgeClass ?> px-2.5 py-1 d-inline-flex align-items-center gap-1 shadow-xs" style="cursor: pointer; font-size: 0.78rem;">
                        <?= e($doc['status']) ?> <i class="fa-solid fa-chevron-down ms-1" style="font-size: 0.6rem; opacity: 0.85;"></i>
                      </span>
                    </button>
                    <ul class="dropdown-menu shadow border-0" style="font-size: 0.85rem; z-index: 1065;">
                      <li class="dropdown-header text-uppercase small py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">Change Status</li>
                      <li>
                        <button type="button" 
                                class="dropdown-item py-2 <?= $doc['status'] === 'VERIFIED' ? 'active fw-bold' : 'text-success' ?>" 
                                data-doc-id="<?= (int)$doc['id'] ?>"
                                data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                                onclick="openVerifyModal(this)">
                          <i class="fa-solid fa-circle-check me-2 text-success"></i> Mark as Verified
                        </button>
                      </li>
                      <li>
                        <button type="button" 
                                class="dropdown-item py-2 <?= $doc['status'] === 'UNDER_REVIEW' ? 'active fw-bold' : 'text-warning text-dark' ?>" 
                                data-doc-id="<?= (int)$doc['id'] ?>"
                                data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                                onclick="openUnderReviewModal(this)">
                          <i class="fa-solid fa-clock me-2 text-warning"></i> Set Under Review
                        </button>
                      </li>
                      <li>
                        <button type="button" 
                                class="dropdown-item py-2 <?= $doc['status'] === 'REJECTED' ? 'active fw-bold' : 'text-danger' ?>" 
                                data-doc-id="<?= (int)$doc['id'] ?>"
                                data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                                onclick="openRejectModal(this)">
                          <i class="fa-solid fa-circle-xmark me-2 text-danger"></i> Mark as Rejected
                        </button>
                      </li>
                      <li><hr class="dropdown-divider my-1"></li>
                      <li>
                        <button type="button" 
                                class="dropdown-item py-2 text-primary" 
                                data-doc-id="<?= (int)$doc['id'] ?>"
                                data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                                data-doc-applicant="<?= htmlspecialchars($doc['customer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-doc-filename="<?= htmlspecialchars($doc['file_name'], ENT_QUOTES, 'UTF-8') ?>"
                                data-doc-status="<?= htmlspecialchars($doc['status'], ENT_QUOTES, 'UTF-8') ?>"
                                data-doc-reason="<?= htmlspecialchars($doc['rejection_reason'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-doc-expiry="<?= htmlspecialchars($doc['expiry_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-doc-type-id="<?= (int)$doc['document_type_id'] ?>"
                                data-doc-notes="<?= htmlspecialchars($doc['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                onclick="openEditDocModal(this)">
                          <i class="fa-solid fa-pen-to-square me-2"></i> Edit Details &amp; File
                        </button>
                      </li>
                    </ul>
                  </div>
                  <?php if (!empty($doc['rejection_reason'])): ?>
                    <div class="text-danger small mt-1" style="font-size: 0.7rem;" title="<?= e($doc['rejection_reason']) ?>">
                      <i class="fa-solid fa-circle-exclamation me-1"></i><?= e(substr($doc['rejection_reason'], 0, 30)) ?>...
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge <?= $doc['expiry_info']['badge_class'] ?>"><?= e($doc['expiry_info']['label']) ?></span>
                </td>
                <td>
                  <div class="small fw-medium text-dark"><?= e($doc['uploaded_by_name'] ?? $doc['uploaded_by_type']) ?></div>
                  <div class="text-muted small" style="font-size: 0.7rem;"><?= format_date($doc['created_at']) ?></div>
                </td>
                <td>
                  <?php if (!empty($doc['verified_by_name'])): ?>
                    <div class="small fw-semibold text-success"><?= e($doc['verified_by_name']) ?></div>
                    <div class="text-muted small" style="font-size: 0.7rem;"><?= format_date($doc['verified_at']) ?></div>
                  <?php else: ?>
                    <span class="text-muted small">&mdash;</span>
                  <?php endif; ?>
                </td>
                <td class="text-end text-nowrap">
                  <div class="btn-group btn-group-sm" role="group" aria-label="Document Actions">
                    <!-- 1. View / Preview -->
                    <button type="button" 
                            class="btn btn-outline-primary" 
                            data-doc-id="<?= (int)$doc['id'] ?>"
                            data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                            data-doc-applicant="<?= htmlspecialchars($doc['customer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-doc-filename="<?= htmlspecialchars($doc['file_name'], ENT_QUOTES, 'UTF-8') ?>"
                            data-doc-status="<?= htmlspecialchars($doc['status'], ENT_QUOTES, 'UTF-8') ?>"
                            data-doc-reason="<?= htmlspecialchars($doc['rejection_reason'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-doc-expiry="<?= htmlspecialchars($doc['expiry_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-doc-type-id="<?= (int)$doc['document_type_id'] ?>"
                            data-doc-notes="<?= htmlspecialchars($doc['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            onclick="openDocPreview(this)" 
                            title="Preview Document">
                      <i class="fa-solid fa-eye"></i>
                    </button>

                    <!-- 2. Download -->
                    <a href="/documents/download?id=<?= $doc['id'] ?>" class="btn btn-outline-secondary" title="Download File">
                      <i class="fa-solid fa-download"></i>
                    </a>

                    <!-- 3. Edit Details & Upload Replacement -->
                    <button type="button" 
                            class="btn btn-outline-info text-dark" 
                            data-doc-id="<?= (int)$doc['id'] ?>"
                            data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                            data-doc-applicant="<?= htmlspecialchars($doc['customer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-doc-filename="<?= htmlspecialchars($doc['file_name'], ENT_QUOTES, 'UTF-8') ?>"
                            data-doc-status="<?= htmlspecialchars($doc['status'], ENT_QUOTES, 'UTF-8') ?>"
                            data-doc-reason="<?= htmlspecialchars($doc['rejection_reason'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-doc-expiry="<?= htmlspecialchars($doc['expiry_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-doc-type-id="<?= (int)$doc['document_type_id'] ?>"
                            data-doc-notes="<?= htmlspecialchars($doc['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            onclick="openEditDocModal(this)" 
                            title="Edit Details / Upload Replacement">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </button>

                    <!-- 4. Delete Document -->
                    <button type="button" 
                            class="btn btn-outline-danger" 
                            data-doc-id="<?= (int)$doc['id'] ?>"
                            data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                            data-doc-applicant="<?= htmlspecialchars($doc['customer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            onclick="openDeleteDocModal(this)" 
                            title="Delete Document Permanently">
                      <i class="fa-solid fa-trash-can"></i>
                    </button>

                    <!-- 5. More Actions Split Dropdown -->
                    <button type="button" 
                            class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" 
                            data-bs-toggle="dropdown" 
                            data-bs-boundary="viewport"
                            aria-expanded="false"
                            title="More Options">
                      <span class="visually-hidden">Actions</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="font-size: 0.85rem; z-index: 1065;">
                      <li class="dropdown-header text-uppercase small py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">Status Actions</li>
                      <li>
                        <button type="button" 
                                class="dropdown-item py-2 <?= $doc['status'] === 'VERIFIED' ? 'disabled text-muted' : 'text-success' ?>" 
                                data-doc-id="<?= (int)$doc['id'] ?>"
                                data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                                onclick="openVerifyModal(this)">
                          <i class="fa-solid fa-circle-check me-2 text-success"></i> Verify Document
                        </button>
                      </li>
                      <li>
                        <button type="button" 
                                class="dropdown-item py-2 <?= $doc['status'] === 'UNDER_REVIEW' ? 'disabled text-muted' : 'text-warning text-dark' ?>" 
                                data-doc-id="<?= (int)$doc['id'] ?>"
                                data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                                onclick="openUnderReviewModal(this)">
                          <i class="fa-solid fa-clock me-2 text-warning"></i> Set Under Review
                        </button>
                      </li>
                      <li>
                        <button type="button" 
                                class="dropdown-item py-2 <?= $doc['status'] === 'REJECTED' ? 'disabled text-muted' : 'text-danger' ?>" 
                                data-doc-id="<?= (int)$doc['id'] ?>"
                                data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                                onclick="openRejectModal(this)">
                          <i class="fa-solid fa-circle-xmark me-2 text-danger"></i> Reject Document
                        </button>
                      </li>
                      <li><hr class="dropdown-divider my-1"></li>
                      <li class="dropdown-header text-uppercase small py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">File Operations</li>
                      <li>
                        <button type="button" 
                                class="dropdown-item py-2" 
                                data-doc-id="<?= (int)$doc['id'] ?>"
                                data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                                onclick="openReplaceModal(this)">
                          <i class="fa-solid fa-cloud-arrow-up text-primary me-2"></i> Upload Replacement Only
                        </button>
                      </li>
                      <li>
                        <button type="button" 
                                class="dropdown-item py-2" 
                                data-doc-id="<?= (int)$doc['id'] ?>"
                                data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                                onclick="openVersionHistoryModal(<?= (int)$doc['id'] ?>, '<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>')">
                          <i class="fa-solid fa-clock-rotate-left text-secondary me-2"></i> Version History (v<?= (int)$doc['version'] ?>)
                        </button>
                      </li>
                      <li><hr class="dropdown-divider my-1"></li>
                      <li>
                        <button type="button" 
                                class="dropdown-item py-2 text-danger fw-semibold" 
                                data-doc-id="<?= (int)$doc['id'] ?>"
                                data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                                data-doc-applicant="<?= htmlspecialchars($doc['customer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                onclick="openDeleteDocModal(this)">
                          <i class="fa-solid fa-trash-can me-2"></i> Delete Document
                        </button>
                      </li>
                    </ul>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Grid / Card View Mode -->
      <div id="documentGridView" class="p-3 d-none">
        <div class="row g-3">
          <?php foreach ($documents as $doc): ?>
            <?php
              $statusBadgeClass = 'badge bg-secondary';
              if ($doc['status'] === 'VERIFIED') $statusBadgeClass = 'badge bg-success';
              elseif ($doc['status'] === 'REJECTED') $statusBadgeClass = 'badge bg-danger';
              elseif ($doc['status'] === 'UNDER_REVIEW') $statusBadgeClass = 'badge bg-warning text-dark';
              
              $ext = strtolower(pathinfo($doc['file_name'] ?? '', PATHINFO_EXTENSION));
              $fileIcon = 'fa-file';
              $iconBg = 'bg-secondary-subtle text-secondary';
              if ($ext === 'pdf') { $fileIcon = 'fa-file-pdf'; $iconBg = 'bg-danger-subtle text-danger'; }
              elseif (in_array($ext, ['jpg', 'jpeg', 'png'], true)) { $fileIcon = 'fa-file-image'; $iconBg = 'bg-primary-subtle text-primary'; }
              elseif (in_array($ext, ['doc', 'docx'], true)) { $fileIcon = 'fa-file-word'; $iconBg = 'bg-info-subtle text-info'; }
            ?>
            <div class="col-12 col-md-6 col-xl-4 col-xxl-3">
              <div class="card h-100 border rounded-3 shadow-xs hover-shadow-sm transition bg-white position-relative">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                  <div>
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 p-2 <?= $iconBg ?> d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                          <i class="fa-solid <?= $fileIcon ?> fs-5"></i>
                        </div>
                        <div style="min-width: 0;">
                          <span class="badge bg-light text-dark border small" style="font-size: 0.68rem;"><?= e($doc['doc_type_name'] ?? 'General') ?></span>
                          <div class="small text-muted" style="font-size: 0.72rem;">v<?= (int)$doc['version'] ?> &bull; <?= number_format(((float)$doc['file_size']) / 1024, 1) ?> KB</div>
                        </div>
                      </div>
                      <span class="<?= $statusBadgeClass ?> px-2 py-1 small" style="font-size: 0.72rem;"><?= e($doc['status']) ?></span>
                    </div>

                    <h6 class="fw-bold text-dark mb-1 text-truncate" title="<?= e($doc['document_title'] ?: $doc['doc_type_name']) ?>">
                      <?= e($doc['document_title'] ?: $doc['doc_type_name']) ?>
                    </h6>
                    <div class="text-muted small text-truncate mb-2" style="font-size: 0.74rem;" title="<?= e($doc['file_name']) ?>">
                      <i class="fa-solid fa-paperclip me-1 opacity-75"></i><?= e($doc['file_name']) ?>
                    </div>

                    <div class="bg-light p-2 rounded small mb-2" style="font-size: 0.76rem;">
                      <div class="d-flex justify-content-between text-truncate mb-1">
                        <span class="text-muted">Applicant:</span>
                        <strong class="text-dark text-truncate" style="max-width: 140px;"><?= e($doc['customer_name'] ?: '—') ?></strong>
                      </div>
                      <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Passport:</span>
                        <span><?= e($doc['passport_number'] ?: '—') ?></span>
                      </div>
                      <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Application:</span>
                        <?php if (!empty($doc['application_number'])): ?>
                          <a href="/applications/show?id=<?= $doc['app_id'] ?>" class="fw-bold text-primary text-decoration-none">
                            <?= e($doc['application_number']) ?>
                          </a>
                        <?php else: ?>
                          <span class="text-muted">General</span>
                        <?php endif; ?>
                      </div>
                      <div class="d-flex justify-content-between">
                        <span class="text-muted">Expiry:</span>
                        <span class="badge <?= $doc['expiry_info']['badge_class'] ?> py-0.5" style="font-size: 0.68rem;"><?= e($doc['expiry_info']['label']) ?></span>
                      </div>
                    </div>

                    <?php if (!empty($doc['rejection_reason'])): ?>
                      <div class="alert alert-danger py-1 px-2 small mb-2" style="font-size: 0.72rem;">
                        <strong>Rejection:</strong> <?= e($doc['rejection_reason']) ?>
                      </div>
                    <?php endif; ?>
                  </div>

                  <div class="pt-2 border-top d-flex align-items-center justify-content-between gap-1">
                    <div class="btn-group btn-group-sm w-100">
                      <button type="button" 
                              class="btn btn-outline-primary btn-sm py-1" 
                              data-doc-id="<?= (int)$doc['id'] ?>"
                              data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                              data-doc-applicant="<?= htmlspecialchars($doc['customer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-doc-filename="<?= htmlspecialchars($doc['file_name'], ENT_QUOTES, 'UTF-8') ?>"
                              data-doc-status="<?= htmlspecialchars($doc['status'], ENT_QUOTES, 'UTF-8') ?>"
                              data-doc-reason="<?= htmlspecialchars($doc['rejection_reason'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-doc-expiry="<?= htmlspecialchars($doc['expiry_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-doc-type-id="<?= (int)$doc['document_type_id'] ?>"
                              data-doc-notes="<?= htmlspecialchars($doc['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              onclick="openDocPreview(this)" 
                              title="Preview Document">
                        <i class="fa-solid fa-eye"></i>
                      </button>
                      <a href="/documents/download?id=<?= $doc['id'] ?>" class="btn btn-outline-secondary btn-sm py-1" title="Download File">
                        <i class="fa-solid fa-download"></i>
                      </a>
                      <button type="button" 
                              class="btn btn-outline-info text-dark btn-sm py-1" 
                              data-doc-id="<?= (int)$doc['id'] ?>"
                              data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                              data-doc-applicant="<?= htmlspecialchars($doc['customer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-doc-filename="<?= htmlspecialchars($doc['file_name'], ENT_QUOTES, 'UTF-8') ?>"
                              data-doc-status="<?= htmlspecialchars($doc['status'], ENT_QUOTES, 'UTF-8') ?>"
                              data-doc-reason="<?= htmlspecialchars($doc['rejection_reason'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-doc-expiry="<?= htmlspecialchars($doc['expiry_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-doc-type-id="<?= (int)$doc['document_type_id'] ?>"
                              data-doc-notes="<?= htmlspecialchars($doc['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              onclick="openEditDocModal(this)" 
                              title="Edit Details / Upload Replacement">
                        <i class="fa-solid fa-pen-to-square"></i>
                      </button>
                      <button type="button" 
                              class="btn btn-outline-danger btn-sm py-1" 
                              data-doc-id="<?= (int)$doc['id'] ?>"
                              data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                              data-doc-applicant="<?= htmlspecialchars($doc['customer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              onclick="openDeleteDocModal(this)" 
                              title="Delete Document">
                        <i class="fa-solid fa-trash-can"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Mobile Document Cards (< 768px) -->
      <div class="d-md-none p-3" id="documentMobileCards">
        <?php foreach ($documents as $doc): ?>
          <div class="card border rounded-3 p-3 mb-3 shadow-sm bg-white">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <div class="fw-bold text-dark"><?= e($doc['document_title'] ?: $doc['doc_type_name']) ?></div>
                <div class="small text-muted"><?= e($doc['customer_name']) ?> &bull; App: <?= e($doc['application_number'] ?: 'N/A') ?></div>
              </div>
              <span class="badge <?= $doc['status'] === 'VERIFIED' ? 'bg-success' : ($doc['status'] === 'REJECTED' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                <?= e($doc['status']) ?>
              </span>
            </div>

            <div class="bg-light p-2 rounded small mb-2 d-flex justify-content-between">
              <span>Expiry: <strong><?= e($doc['expiry_info']['label']) ?></strong></span>
              <span>Version: <strong>v<?= (int)$doc['version'] ?></strong></span>
            </div>

            <?php if (!empty($doc['rejection_reason'])): ?>
              <div class="alert alert-danger py-1 px-2 small mb-2">
                <strong>Reason:</strong> <?= e($doc['rejection_reason']) ?>
              </div>
            <?php endif; ?>

            <div class="d-flex flex-wrap justify-content-between align-items-center pt-2 border-top gap-2">
              <div class="btn-group btn-group-sm">
                <button type="button" 
                        class="btn btn-outline-primary" 
                        data-doc-id="<?= (int)$doc['id'] ?>"
                        data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-applicant="<?= htmlspecialchars($doc['customer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-filename="<?= htmlspecialchars($doc['file_name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-status="<?= htmlspecialchars($doc['status'], ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-reason="<?= htmlspecialchars($doc['rejection_reason'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-expiry="<?= htmlspecialchars($doc['expiry_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-type-id="<?= (int)$doc['document_type_id'] ?>"
                        data-doc-notes="<?= htmlspecialchars($doc['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        onclick="openDocPreview(this)" 
                        title="Preview">
                  <i class="fa-solid fa-eye me-1"></i> Preview
                </button>
                <a href="/documents/download?id=<?= $doc['id'] ?>" class="btn btn-outline-secondary" title="Download">
                  <i class="fa-solid fa-download"></i>
                </a>
                <button type="button" 
                        class="btn btn-outline-info text-dark" 
                        data-doc-id="<?= (int)$doc['id'] ?>"
                        data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-applicant="<?= htmlspecialchars($doc['customer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-filename="<?= htmlspecialchars($doc['file_name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-status="<?= htmlspecialchars($doc['status'], ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-reason="<?= htmlspecialchars($doc['rejection_reason'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-expiry="<?= htmlspecialchars($doc['expiry_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-type-id="<?= (int)$doc['document_type_id'] ?>"
                        data-doc-notes="<?= htmlspecialchars($doc['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        onclick="openEditDocModal(this)" 
                        title="Edit">
                  <i class="fa-solid fa-pen-to-square"></i>
                </button>
              </div>
              <div class="d-flex gap-1">
                <?php if ($doc['status'] !== 'VERIFIED'): ?>
                  <button type="button" 
                          class="btn btn-success btn-sm py-1 px-2" 
                          data-doc-id="<?= (int)$doc['id'] ?>"
                          data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                          onclick="openVerifyModal(this)" 
                          title="Verify Document">
                    <i class="fa-solid fa-check"></i>
                  </button>
                <?php endif; ?>
                <?php if ($doc['status'] !== 'UNDER_REVIEW'): ?>
                  <button type="button" 
                          class="btn btn-warning btn-sm py-1 px-2 text-dark" 
                          data-doc-id="<?= (int)$doc['id'] ?>"
                          data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                          onclick="openUnderReviewModal(this)" 
                          title="Set Under Review">
                    <i class="fa-solid fa-clock"></i>
                  </button>
                <?php endif; ?>
                <?php if ($doc['status'] !== 'REJECTED'): ?>
                  <button type="button" 
                          class="btn btn-danger btn-sm py-1 px-2" 
                          data-doc-id="<?= (int)$doc['id'] ?>"
                          data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                          onclick="openRejectModal(this)" 
                          title="Reject Document">
                    <i class="fa-solid fa-xmark"></i>
                  </button>
                <?php endif; ?>
                <button type="button" 
                        class="btn btn-outline-danger btn-sm py-1 px-2" 
                        data-doc-id="<?= (int)$doc['id'] ?>"
                        data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-applicant="<?= htmlspecialchars($doc['customer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        onclick="openDeleteDocModal(this)" 
                        title="Delete Document">
                  <i class="fa-solid fa-trash-can"></i>
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Compact List View Mode -->
      <div id="documentCompactView" class="p-3 d-none">
        <div class="list-group shadow-xs rounded-3">
          <?php foreach ($documents as $doc): ?>
            <?php
              $statusBadgeClass = 'badge bg-secondary';
              if ($doc['status'] === 'VERIFIED') $statusBadgeClass = 'badge bg-success';
              elseif ($doc['status'] === 'REJECTED') $statusBadgeClass = 'badge bg-danger';
              elseif ($doc['status'] === 'UNDER_REVIEW') $statusBadgeClass = 'badge bg-warning text-dark';

              $ext = strtolower(pathinfo($doc['file_name'] ?? '', PATHINFO_EXTENSION));
              $fileIcon = 'fa-file text-secondary';
              if ($ext === 'pdf') { $fileIcon = 'fa-file-pdf text-danger'; }
              elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) { $fileIcon = 'fa-file-image text-primary'; }
              elseif (in_array($ext, ['doc', 'docx'], true)) { $fileIcon = 'fa-file-word text-info'; }
            ?>
            <div class="list-group-item list-group-item-action d-flex flex-wrap align-items-center justify-content-between p-2.5 gap-2 border-start-0 border-end-0">
              <div class="d-flex align-items-center gap-2.5 flex-grow-1" style="min-width: 240px;">
                <div class="rounded p-2 bg-light d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                  <i class="fa-solid <?= $fileIcon ?> fs-5"></i>
                </div>
                <div class="text-truncate">
                  <div class="fw-bold text-dark text-truncate" style="max-width: 260px;" title="<?= e($doc['document_title'] ?: $doc['doc_type_name']) ?>">
                    <?= e($doc['document_title'] ?: $doc['doc_type_name']) ?>
                    <span class="badge bg-light text-muted border ms-1" style="font-size: 0.68rem;">v<?= (int)$doc['version'] ?></span>
                  </div>
                  <div class="small text-muted text-truncate" style="font-size: 0.74rem;">
                    <span class="text-dark fw-medium"><?= e($doc['customer_name'] ?: 'General Applicant') ?></span> &bull; 
                    <?= e($doc['file_name']) ?> &bull; 
                    <?= number_format(((float)$doc['file_size']) / 1024, 1) ?> KB
                  </div>
                </div>
              </div>

              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="<?= $statusBadgeClass ?> px-2 py-1" style="font-size: 0.72rem;"><?= e($doc['status']) ?></span>
                <span class="badge <?= $doc['expiry_info']['badge_class'] ?>" style="font-size: 0.72rem;"><?= e($doc['expiry_info']['label']) ?></span>
                <?php if (!empty($doc['application_number'])): ?>
                  <a href="/applications/show?id=<?= $doc['app_id'] ?>" class="badge bg-primary-subtle text-primary border text-decoration-none" style="font-size: 0.72rem;">
                    <?= e($doc['application_number']) ?>
                  </a>
                <?php endif; ?>
              </div>

              <div class="d-flex align-items-center gap-1">
                <button type="button" 
                        class="btn btn-sm btn-outline-primary py-1 px-2" 
                        data-doc-id="<?= (int)$doc['id'] ?>"
                        data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-applicant="<?= htmlspecialchars($doc['customer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-filename="<?= htmlspecialchars($doc['file_name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-status="<?= htmlspecialchars($doc['status'], ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-reason="<?= htmlspecialchars($doc['rejection_reason'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-expiry="<?= htmlspecialchars($doc['expiry_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-type-id="<?= (int)$doc['document_type_id'] ?>"
                        data-doc-notes="<?= htmlspecialchars($doc['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        onclick="openDocPreview(this)" 
                        title="Preview">
                  <i class="fa-solid fa-eye"></i>
                </button>
                <a href="/documents/download?id=<?= $doc['id'] ?>" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Download">
                  <i class="fa-solid fa-download"></i>
                </a>
                <button type="button" 
                        class="btn btn-sm btn-outline-info text-dark py-1 px-2" 
                        data-doc-id="<?= (int)$doc['id'] ?>"
                        data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-applicant="<?= htmlspecialchars($doc['customer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-filename="<?= htmlspecialchars($doc['file_name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-status="<?= htmlspecialchars($doc['status'], ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-reason="<?= htmlspecialchars($doc['rejection_reason'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-expiry="<?= htmlspecialchars($doc['expiry_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-type-id="<?= (int)$doc['document_type_id'] ?>"
                        data-doc-notes="<?= htmlspecialchars($doc['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        onclick="openEditDocModal(this)" 
                        title="Edit">
                  <i class="fa-solid fa-pen-to-square"></i>
                </button>
                <button type="button" 
                        class="btn btn-sm btn-outline-danger py-1 px-2" 
                        data-doc-id="<?= (int)$doc['id'] ?>"
                        data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-doc-applicant="<?= htmlspecialchars($doc['customer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        onclick="openDeleteDocModal(this)" 
                        title="Delete">
                  <i class="fa-solid fa-trash-can"></i>
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Pagination Component -->
      <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white d-flex flex-wrap align-items-center justify-content-between p-3 border-top gap-2">
          <div class="small text-muted">
            Showing <strong><?= ($page - 1) * $perPage + 1 ?></strong> to <strong><?= min($totalRecords, $page * $perPage) ?></strong> of <strong><?= $totalRecords ?></strong> documents (Page <?= $page ?> of <?= $totalPages ?>)
          </div>
          <nav aria-label="Document pagination">
            <ul class="pagination pagination-sm mb-0">
              <?php
                $queryParams = $_GET;
                $queryParams['page'] = max(1, $page - 1);
                $prevUrl = '/documents?' . http_build_query($queryParams);
                $queryParams['page'] = min($totalPages, $page + 1);
                $nextUrl = '/documents?' . http_build_query($queryParams);
              ?>
              <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $prevUrl ?>"><i class="fa-solid fa-chevron-left me-1"></i> Prev</a>
              </li>
              <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                <?php 
                  $queryParams['page'] = $p;
                  $pageUrl = '/documents?' . http_build_query($queryParams);
                ?>
                <li class="page-item <?= $p === $page ? 'active fw-bold' : '' ?>">
                  <a class="page-link" href="<?= $pageUrl ?>"><?= $p ?></a>
                </li>
              <?php endfor; ?>
              <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $nextUrl ?>">Next <i class="fa-solid fa-chevron-right ms-1"></i></a>
              </li>
            </ul>
          </nav>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<!-- ==========================================================================
     DOCUMENT MODALS
     ========================================================================== -->

<!-- 1. Document Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-dark text-white">
        <div>
          <h5 class="modal-title fw-bold fs-6" id="previewModalDocTitle">Document Preview</h5>
          <div class="text-white-50 small" id="previewModalSubtitle">—</div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0 bg-light" style="min-height: 500px; max-height: 75vh; overflow-y: auto;">
        <div id="previewContainer" class="w-100 h-100 d-flex align-items-center justify-content-center p-3">
          <!-- Populated dynamically via JS -->
        </div>
      </div>
      <div class="modal-footer bg-white border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div id="previewStatusBadge"></div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <button type="button" id="previewVerifyBtn" class="btn btn-success btn-sm px-2.5">
            <i class="fa-solid fa-circle-check me-1"></i> Verify
          </button>
          <button type="button" id="previewUnderReviewBtn" class="btn btn-warning text-dark btn-sm px-2.5">
            <i class="fa-solid fa-clock me-1"></i> Under Review
          </button>
          <button type="button" id="previewRejectBtn" class="btn btn-outline-danger btn-sm px-2.5">
            <i class="fa-solid fa-circle-xmark me-1"></i> Reject
          </button>
          <button type="button" id="previewEditBtn" class="btn btn-outline-info text-dark btn-sm px-2.5">
            <i class="fa-solid fa-pen-to-square me-1"></i> Edit Details
          </button>
          <a href="#" id="previewDownloadBtn" class="btn btn-outline-secondary btn-sm" target="_blank">
            <i class="fa-solid fa-download me-1"></i> Download
          </a>
          <button type="button" id="previewDeleteBtn" class="btn btn-danger btn-sm px-2.5">
            <i class="fa-solid fa-trash-can me-1"></i> Delete
          </button>
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- 1b. Edit Document Modal -->
<div class="modal fade" id="editDocModal" tabindex="-1" aria-labelledby="editDocModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold fs-6" id="editDocModalLabel"><i class="fa-solid fa-pen-to-square me-2"></i> Edit Document &amp; Compliance Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/documents/update" method="POST" enctype="multipart/form-data" id="editDocForm">
        <?= csrf_field() ?>
        <input type="hidden" name="document_id" id="editDocId" value="">

        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-7">
              <label class="form-label small fw-semibold text-secondary">Document Title <span class="text-danger">*</span></label>
              <input type="text" name="document_title" id="editDocTitleInput" class="form-control" required placeholder="e.g. Passport Copy, Bank Statement">
            </div>
            <div class="col-md-5">
              <label class="form-label small fw-semibold text-secondary">Document Type</label>
              <select name="document_type_id" id="editDocTypeId" class="form-select">
                <?php foreach ($docTypes as $dt): ?>
                  <option value="<?= $dt['id'] ?>"><?= e($dt['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Compliance Status <span class="text-danger">*</span></label>
              <select name="status" id="editDocStatus" class="form-select fw-semibold" onchange="toggleEditRejectionReason(this.value)">
                <option value="UNDER_REVIEW">Under Review</option>
                <option value="VERIFIED">Verified</option>
                <option value="REJECTED">Rejected</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Expiry Date <small class="text-muted">(Optional)</small></label>
              <input type="date" name="expiry_date" id="editDocExpiry" class="form-control">
            </div>

            <div class="col-12" id="editRejectionReasonContainer" style="display: none;">
              <label class="form-label small fw-semibold text-danger">Rejection Reason <span class="text-danger">*</span></label>
              <textarea name="rejection_reason" id="editDocRejectionReason" class="form-control border-danger" rows="2" placeholder="Mandatory reason for rejection (e.g. Blurry photo, expired validity)..."></textarea>
            </div>

            <div class="col-12">
              <div class="p-3 bg-light rounded border">
                <label class="form-label small fw-semibold text-secondary mb-1">
                  <i class="fa-solid fa-cloud-arrow-up me-1 text-primary"></i> Replace File <small class="text-muted">(Leave empty to keep existing file: <span id="editDocCurrentFile" class="fw-bold text-dark"></span>)</small>
                </label>
                <input type="file" name="document_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.docx">
                <div class="form-text small" style="font-size: 0.74rem;">Allowed formats: PDF, JPG, PNG, DOCX (Max 10MB). Uploading a file archives the current version.</div>
              </div>
            </div>

            <div class="col-12">
              <label class="form-label small fw-semibold text-secondary">Internal Operational Notes</label>
              <textarea name="notes" id="editDocNotes" class="form-control" rows="2" placeholder="Optional notes for internal case team..."></textarea>
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold"><i class="fa-solid fa-floppy-disk me-1"></i> Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 2. Verify Confirmation Modal -->
<div class="modal fade" id="verifyModal" tabindex="-1" aria-labelledby="verifyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title fw-bold fs-6" id="verifyModalLabel"><i class="fa-solid fa-circle-check me-2"></i> Verify Compliance Document</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/documents/verify" method="POST" id="verifyDocForm">
        <?= csrf_field() ?>
        <input type="hidden" name="document_id" id="verifyDocId" value="">

        <div class="modal-body p-4">
          <p class="mb-3">Confirm that you have reviewed <strong id="verifyDocTitle">this document</strong> and verified that it meets all official consular and embassy compliance standards?</p>
          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Verification Audit Notes <small class="text-muted">(Optional)</small></label>
            <textarea name="notes" id="verifyDocNotes" class="form-control" rows="2" placeholder="e.g. Verified clear passport scan with 6+ months validity."></textarea>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="btnConfirmVerify" class="btn btn-success fw-semibold"><i class="fa-solid fa-check me-1"></i> Confirm Verification</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 3. Reject Modal with Mandatory Reason -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title fw-bold fs-6" id="rejectModalLabel"><i class="fa-solid fa-circle-xmark me-2"></i> Reject Document &amp; Request Replacement</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/documents/reject" method="POST" id="rejectDocForm">
        <?= csrf_field() ?>
        <input type="hidden" name="document_id" id="rejectDocId" value="">

        <div class="modal-body p-4">
          <p class="mb-3">Reject <strong id="rejectDocTitle">this document</strong>? This will mark the application as <strong>Action Required</strong> and notify the case officer.</p>
          <div class="alert alert-warning py-2 small mb-3">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> A mandatory rejection reason must be provided. The applicant will be notified to upload a replacement.
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Rejection Reason <span class="text-danger">*</span></label>
            <textarea name="rejection_reason" id="rejectDocReason" class="form-control" rows="3" required placeholder="Mandatory explanation (e.g. Passport copy is blurred, Expiry date is less than 6 months, Name does not match application)..."></textarea>
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Internal Operational Notes</label>
            <textarea name="notes" id="rejectDocNotes" class="form-control" rows="2" placeholder="Optional notes for internal case team..."></textarea>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="btnConfirmReject" class="btn btn-danger fw-semibold"><i class="fa-solid fa-ban me-1"></i> Confirm Rejection</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 3b. Under Review Confirmation Modal -->
<div class="modal fade" id="underReviewModal" tabindex="-1" aria-labelledby="underReviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title fw-bold fs-6" id="underReviewModalLabel"><i class="fa-solid fa-clock me-2"></i> Reset Document to Under Review</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/documents/under-review" method="POST" id="underReviewDocForm">
        <?= csrf_field() ?>
        <input type="hidden" name="document_id" id="underReviewDocId" value="">

        <div class="modal-body p-4">
          <p class="mb-3">Reset <strong id="underReviewDocTitle">this document</strong> status back to <span class="badge bg-warning text-dark">Under Review</span>? Any previous rejection or approval state will be cleared for re-examination.</p>
          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Notes <small class="text-muted">(Optional)</small></label>
            <textarea name="notes" id="underReviewDocNotes" class="form-control" rows="2" placeholder="e.g. Document returned to review queue for verification."></textarea>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="btnConfirmUnderReview" class="btn btn-warning text-dark fw-semibold"><i class="fa-solid fa-rotate-left me-1"></i> Confirm Under Review</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 3c. Delete Document Confirmation Modal -->
<div class="modal fade" id="deleteDocModal" tabindex="-1" aria-labelledby="deleteDocModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title fw-bold fs-6" id="deleteDocModalLabel"><i class="fa-solid fa-trash-can me-2"></i> Delete Document Permanently</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/documents/delete" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="document_id" id="deleteDocId" value="">

        <div class="modal-body p-4">
          <div class="alert alert-danger py-2 small mb-3">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> <strong>Warning:</strong> This action cannot be undone. The uploaded file and all version archives will be permanently removed.
          </div>
          <p class="mb-2">Are you sure you want to permanently delete:</p>
          <div class="p-3 bg-light rounded border mb-2">
            <div class="fw-bold text-dark" id="deleteDocTitle">Document</div>
            <div class="small text-muted" id="deleteDocApplicant">Applicant</div>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger fw-semibold"><i class="fa-solid fa-trash-can me-1"></i> Delete Document Permanently</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 4. Upload Replacement Modal -->
<div class="modal fade" id="replaceModal" tabindex="-1" aria-labelledby="replaceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold fs-6" id="replaceModalLabel"><i class="fa-solid fa-cloud-arrow-up me-2"></i> Upload Replacement Document</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/documents/replace" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="document_id" id="replaceDocId" value="">

        <div class="modal-body p-4">
          <p class="small text-muted mb-3">Uploading a replacement will preserve the previous rejected version in history and mark the new file as <strong>Under Review</strong>.</p>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Select New File (PDF, JPG, PNG) <span class="text-danger">*</span></label>
            <input type="file" name="document_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.docx" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Document Expiry Date <small class="text-muted">(If applicable)</small></label>
            <input type="date" name="expiry_date" class="form-control form-control-sm">
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Replacement Remarks</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Uploaded high-resolution color scan per embassy request."></textarea>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold"><i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload Version</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 5. Version History Modal -->
<div class="modal fade" id="versionHistoryModal" tabindex="-1" aria-labelledby="versionHistoryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold fs-6" id="versionHistoryModalLabel"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Version History &amp; Traceability</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" id="versionHistoryBody">
        <div class="text-center py-4 text-muted">
          <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
          <div>Loading version archive...</div>
        </div>
      </div>
      <div class="modal-footer bg-light border-top">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- 6. General Upload Document Modal -->
<div class="modal fade" id="uploadDocModal" tabindex="-1" aria-labelledby="uploadDocModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold fs-6" id="uploadDocModalLabel"><i class="fa-solid fa-cloud-arrow-up me-2"></i> Upload Application Document</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/documents/upload" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Target Visa Application <span class="text-danger">*</span></label>
            <select name="application_id" class="form-select" required>
              <option value="">-- Choose Visa Application --</option>
              <?php
                $apps = $pdo->query("SELECT a.id, a.application_number, c.full_name FROM applications a JOIN customers c ON a.customer_id = c.id WHERE a.is_archived = 0 ORDER BY a.created_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($apps as $ap):
              ?>
                <option value="<?= $ap['id'] ?>"><?= e($ap['application_number']) ?> &mdash; <?= e($ap['full_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Document Type <span class="text-danger">*</span></label>
            <select name="document_type_id" class="form-select" required>
              <option value="">-- Choose Document Type --</option>
              <?php foreach ($docTypes as $dt): ?>
                <option value="<?= $dt['id'] ?>"><?= e($dt['name']) ?> (<?= e($dt['category'] ?? 'General') ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Select File (PDF, JPG, PNG, DOCX) <span class="text-danger">*</span></label>
            <input type="file" name="document_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.docx" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Document Expiry Date <small class="text-muted">(Optional)</small></label>
            <input type="date" name="expiry_date" class="form-control form-control-sm">
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Operational Notes</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold"><i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload File</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function getDocData(target, defaultTitle, defaultApplicant) {
  if (target && typeof target === 'object' && target.dataset) {
    return {
      id: target.dataset.docId || target.dataset.id || '',
      title: target.dataset.docTitle || target.dataset.title || defaultTitle || 'Document',
      applicant: target.dataset.docApplicant || target.dataset.applicant || defaultApplicant || '',
      filename: target.dataset.docFilename || '',
      status: target.dataset.docStatus || '',
      reason: target.dataset.docReason || '',
      expiry: target.dataset.docExpiry || '',
      typeId: target.dataset.docTypeId || '',
      notes: target.dataset.docNotes || ''
    };
  }
  return {
    id: target || '',
    title: defaultTitle || 'Document',
    applicant: defaultApplicant || '',
    filename: '',
    status: '',
    reason: '',
    expiry: '',
    typeId: '',
    notes: ''
  };
}

function showModalSafely(modalId) {
  // Close any currently active Bootstrap dropdowns to prevent backdrop/focus interference
  try {
    document.querySelectorAll('.dropdown-toggle.show, .dropdown-menu.show').forEach(function(el) {
      el.classList.remove('show');
      el.setAttribute('aria-expanded', 'false');
    });
  } catch (e) {}

  const el = document.getElementById(modalId);
  if (!el) {
    console.warn('Modal not found:', modalId);
    return;
  }

  // Ensure modal lives directly under body to avoid container stacking contexts
  if (el.parentNode !== document.body) {
    document.body.appendChild(el);
  }

  try {
    if (window.bootstrap && bootstrap.Modal) {
      const inst = bootstrap.Modal.getOrCreateInstance(el);
      inst.show();
      return;
    }
  } catch (err) {
    console.warn('Bootstrap modal show warning:', err);
  }

  el.classList.add('show');
  el.style.display = 'block';
  el.removeAttribute('aria-hidden');
  el.setAttribute('aria-modal', 'true');
  document.body.classList.add('modal-open');
}

function hideModalSafely(modalId) {
  const el = document.getElementById(modalId);
  if (!el) return;
  try {
    if (window.bootstrap && bootstrap.Modal) {
      const inst = bootstrap.Modal.getInstance(el);
      if (inst) {
        inst.hide();
        return;
      }
    }
  } catch (e) {}

  el.classList.remove('show');
  el.style.display = 'none';
  el.setAttribute('aria-hidden', 'true');
  el.removeAttribute('aria-modal');
  if (document.querySelectorAll('.modal.show').length === 0) {
    document.body.classList.remove('modal-open');
  }
}

function switchModal(fromModalId, toModalId, callback) {
  const fromEl = document.getElementById(fromModalId);
  if (fromEl && (fromEl.classList.contains('show') || fromEl.style.display === 'block')) {
    hideModalSafely(fromModalId);
    let called = false;
    const runOnce = function() {
      if (!called) {
        called = true;
        if (typeof callback === 'function') callback();
      }
    };
    fromEl.addEventListener('hidden.bs.modal', runOnce, { once: true });
    setTimeout(runOnce, 280);
  } else {
    if (typeof callback === 'function') callback();
  }
}

function openDocPreview(idOrEl, maybeTitle, maybeApplicant, maybeFilename, maybeStatus, maybeReason, maybeExpiry, maybeTypeId, maybeNotes) {
  let id, title, applicant, filename, status, reason, expiry, typeId, notes;

  if (idOrEl && typeof idOrEl === 'object' && idOrEl.dataset) {
    const d = getDocData(idOrEl);
    id = d.id;
    title = d.title;
    applicant = d.applicant;
    filename = d.filename;
    status = d.status;
    reason = d.reason;
    expiry = d.expiry;
    typeId = d.typeId;
    notes = d.notes;
  } else {
    id = idOrEl;
    title = maybeTitle || 'Document';
    applicant = maybeApplicant || '';
    filename = maybeFilename || '';
    status = maybeStatus || '';
    reason = maybeReason || '';
    expiry = maybeExpiry || '';
    typeId = maybeTypeId || '';
    notes = maybeNotes || '';
  }

  const titleEl = document.getElementById('previewModalDocTitle');
  if (titleEl) titleEl.innerText = title;

  const subEl = document.getElementById('previewModalSubtitle');
  if (subEl) subEl.innerText = (applicant ? 'Applicant: ' + applicant + ' • ' : '') + 'File: ' + (filename || 'Document');

  const dlBtn = document.getElementById('previewDownloadBtn');
  if (dlBtn) dlBtn.href = '/documents/download?id=' + id;

  let badge = '<span class="badge bg-secondary">' + (status || 'UNDER_REVIEW') + '</span>';
  if (status === 'VERIFIED') badge = '<span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i> Verified</span>';
  else if (status === 'REJECTED') badge = '<span class="badge bg-danger"><i class="fa-solid fa-circle-xmark me-1"></i> Rejected' + (reason ? ': ' + reason : '') + '</span>';
  else if (status === 'UNDER_REVIEW') badge = '<span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i> Under Review</span>';

  if (expiry) {
    badge += ' <span class="badge bg-light text-dark border ms-1">Expiry: ' + expiry + '</span>';
  }
  const statusEl = document.getElementById('previewStatusBadge');
  if (statusEl) statusEl.innerHTML = badge;

  // Configure action buttons in preview modal using smooth transition helper
  const vBtn = document.getElementById('previewVerifyBtn');
  const uBtn = document.getElementById('previewUnderReviewBtn');
  const rBtn = document.getElementById('previewRejectBtn');
  const eBtn = document.getElementById('previewEditBtn');
  const dBtn = document.getElementById('previewDeleteBtn');

  if (vBtn) {
    vBtn.disabled = (status === 'VERIFIED');
    vBtn.onclick = function() {
      switchModal('previewModal', 'verifyModal', function() {
        openVerifyModal(id, title);
      });
    };
  }

  if (uBtn) {
    uBtn.disabled = (status === 'UNDER_REVIEW');
    uBtn.onclick = function() {
      switchModal('previewModal', 'underReviewModal', function() {
        openUnderReviewModal(id, title);
      });
    };
  }

  if (rBtn) {
    rBtn.disabled = (status === 'REJECTED');
    rBtn.onclick = function() {
      switchModal('previewModal', 'rejectModal', function() {
        openRejectModal(id, title);
      });
    };
  }

  if (eBtn) {
    eBtn.onclick = function() {
      switchModal('previewModal', 'editDocModal', function() {
        openEditDocModal({
          dataset: {
            docId: id,
            docTitle: title,
            docApplicant: applicant,
            docFilename: filename,
            docStatus: status,
            docReason: reason,
            docExpiry: expiry,
            docTypeId: typeId,
            docNotes: notes
          }
        });
      });
    };
  }

  if (dBtn) {
    dBtn.onclick = function() {
      switchModal('previewModal', 'deleteDocModal', function() {
        openDeleteDocModal(id, title, applicant);
      });
    };
  }

  const ext = (filename || '').split('.').pop().toLowerCase();
  const container = document.getElementById('previewContainer');

  if (container) {
    if (ext === 'pdf') {
      container.innerHTML = '<iframe src="/documents/preview?id=' + id + '" style="width: 100%; height: 600px; border: none; border-radius: 6px;"></iframe>';
    } else if (['jpg', 'jpeg', 'png', 'webp'].includes(ext)) {
      container.innerHTML = '<img src="/documents/preview?id=' + id + '" class="img-fluid rounded shadow-sm" style="max-height: 550px; object-fit: contain;">';
    } else {
      container.innerHTML = '<div class="text-center p-5"><i class="fa-solid fa-file-lines fs-1 text-secondary mb-3"></i><h5>Preview not supported for ' + (ext ? ext.toUpperCase() : 'file') + '</h5><p class="text-muted small">Please download the file to inspect its contents.</p><a href="/documents/download?id=' + id + '" class="btn btn-primary btn-sm"><i class="fa-solid fa-download me-1"></i> Download ' + (filename || 'File') + '</a></div>';
    }
  }

  showModalSafely('previewModal');
}

function openEditDocModal(idOrEl, maybeTitle) {
  const data = getDocData(idOrEl, maybeTitle);
  const idInput = document.getElementById('editDocId');
  if (idInput) idInput.value = data.id;

  const titleInput = document.getElementById('editDocTitleInput');
  if (titleInput) titleInput.value = data.title;

  const typeSelect = document.getElementById('editDocTypeId');
  if (typeSelect && data.typeId) typeSelect.value = data.typeId;

  const statusSelect = document.getElementById('editDocStatus');
  if (statusSelect && data.status) {
    statusSelect.value = data.status;
    toggleEditRejectionReason(data.status);
  }

  const expiryInput = document.getElementById('editDocExpiry');
  if (expiryInput) expiryInput.value = data.expiry || '';

  const notesInput = document.getElementById('editDocNotes');
  if (notesInput) notesInput.value = data.notes || '';

  const reasonInput = document.getElementById('editDocRejectionReason');
  if (reasonInput) reasonInput.value = data.reason || '';

  const fileLabel = document.getElementById('editDocCurrentFile');
  if (fileLabel) fileLabel.innerText = data.filename || 'Current File';

  showModalSafely('editDocModal');
}

function toggleEditRejectionReason(status) {
  const container = document.getElementById('editRejectionReasonContainer');
  const input = document.getElementById('editDocRejectionReason');
  if (container) {
    if (status === 'REJECTED') {
      container.style.display = 'block';
      if (input) input.setAttribute('required', 'required');
    } else {
      container.style.display = 'none';
      if (input) input.removeAttribute('required');
    }
  }
}

function openVerifyModal(idOrEl, maybeTitle) {
  const data = getDocData(idOrEl, maybeTitle);
  const idInput = document.getElementById('verifyDocId');
  if (idInput) idInput.value = data.id;
  const titleEl = document.getElementById('verifyDocTitle');
  if (titleEl) titleEl.innerText = data.title;
  showModalSafely('verifyModal');
}

function openUnderReviewModal(idOrEl, maybeTitle) {
  const data = getDocData(idOrEl, maybeTitle);
  const idInput = document.getElementById('underReviewDocId');
  if (idInput) idInput.value = data.id;
  const titleEl = document.getElementById('underReviewDocTitle');
  if (titleEl) titleEl.innerText = data.title;
  showModalSafely('underReviewModal');
}

function openRejectModal(idOrEl, maybeTitle) {
  const data = getDocData(idOrEl, maybeTitle);
  const idInput = document.getElementById('rejectDocId');
  if (idInput) idInput.value = data.id;
  const titleEl = document.getElementById('rejectDocTitle');
  if (titleEl) titleEl.innerText = data.title;
  showModalSafely('rejectModal');
}

function openReplaceModal(idOrEl, maybeTitle) {
  const data = getDocData(idOrEl, maybeTitle);
  const idInput = document.getElementById('replaceDocId');
  if (idInput) idInput.value = data.id;
  showModalSafely('replaceModal');
}

function openDeleteDocModal(idOrEl, maybeTitle, maybeApplicant) {
  const data = getDocData(idOrEl, maybeTitle, maybeApplicant);
  const idInput = document.getElementById('deleteDocId');
  if (idInput) idInput.value = data.id;
  const titleEl = document.getElementById('deleteDocTitle');
  if (titleEl) titleEl.innerText = data.title;
  const appEl = document.getElementById('deleteDocApplicant');
  if (appEl) appEl.innerText = data.applicant ? 'Applicant: ' + data.applicant : '';
  showModalSafely('deleteDocModal');
}

function openVersionHistoryModal(id, title) {
  const body = document.getElementById('versionHistoryBody');
  if (body) {
    body.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary mb-2"></div><div>Loading version archive...</div></div>';
  }

  fetch('/documents/history?id=' + id)
    .then(r => r.json())
    .then(res => {
      if (res.data && res.data.length > 0) {
        let html = '<div class="table-responsive"><table class="table-custom"><thead><tr><th>Version</th><th>File Name</th><th>Size</th><th>Uploaded By</th><th>Date</th><th>Archived Reason</th></tr></thead><tbody>';
        res.data.forEach(v => {
          html += '<tr><td><span class="badge bg-secondary">v' + v.version_number + '</span></td><td>' + v.file_name + '</td><td>' + (parseFloat(v.file_size) / 1024).toFixed(1) + ' KB</td><td>' + (v.uploader_name || v.uploaded_by_type) + '</td><td class="small text-muted">' + v.created_at + '</td><td class="small text-danger">' + (v.rejection_reason || '—') + '</td></tr>';
        });
        html += '</tbody></table></div>';
        if (body) body.innerHTML = html;
      } else {
        if (body) body.innerHTML = '<div class="p-4 text-center text-muted">No previous version history archived for this document (Current is Version 1).</div>';
      }
    })
    .catch(() => {
      if (body) body.innerHTML = '<div class="p-4 text-center text-danger">Failed to load version history.</div>';
    });

  showModalSafely('versionHistoryModal');
}

function setDocumentView(mode) {
  const folderView = document.getElementById('documentFolderView');
  const tableView = document.getElementById('documentTableView');
  const gridView = document.getElementById('documentGridView');
  const compactView = document.getElementById('documentCompactView');
  const mobileCards = document.getElementById('documentMobileCards');
  const emptyState = document.getElementById('documentEmptyState');
  const foldersBtn = document.getElementById('docViewFoldersBtn');
  const listBtn = document.getElementById('docViewListBtn');
  const gridBtn = document.getElementById('docViewGridBtn');
  const compactBtn = document.getElementById('docViewCompactBtn');
  const titleSpan = document.getElementById('docRegistryTitle');
  const badgeSpan = document.getElementById('docRegistryCountBadge');

  // Hide all containers
  if (folderView) folderView.classList.add('d-none');
  if (tableView) tableView.classList.add('d-none');
  if (gridView) gridView.classList.add('d-none');
  if (compactView) compactView.classList.add('d-none');
  if (mobileCards) mobileCards.classList.add('d-none');
  if (emptyState) emptyState.classList.add('d-none');

  // Reset all buttons to inactive pill style
  [foldersBtn, listBtn, gridBtn, compactBtn].forEach(btn => {
    if (btn) {
      btn.classList.remove('btn-primary', 'shadow-sm', 'text-white', 'active');
      btn.classList.add('btn-light', 'text-muted');
    }
  });

  if (mode === 'grid') {
    if (gridView) {
      gridView.classList.remove('d-none');
    } else if (emptyState) {
      emptyState.classList.remove('d-none');
    }
    if (gridBtn) {
      gridBtn.classList.remove('btn-light', 'text-muted');
      gridBtn.classList.add('btn-primary', 'shadow-sm', 'text-white', 'active');
    }
    if (titleSpan) titleSpan.textContent = 'Document Registry Cards';
    if (badgeSpan) badgeSpan.textContent = '<?= count($documents) ?> documents';
    try { localStorage.setItem('vt_doc_view', 'grid'); } catch(e) {}
  } else if (mode === 'compact') {
    if (compactView) {
      compactView.classList.remove('d-none');
    } else if (emptyState) {
      emptyState.classList.remove('d-none');
    }
    if (compactBtn) {
      compactBtn.classList.remove('btn-light', 'text-muted');
      compactBtn.classList.add('btn-primary', 'shadow-sm', 'text-white', 'active');
    }
    if (titleSpan) titleSpan.textContent = 'Compact Document Registry';
    if (badgeSpan) badgeSpan.textContent = '<?= count($documents) ?> documents';
    try { localStorage.setItem('vt_doc_view', 'compact'); } catch(e) {}
  } else if (mode === 'table') {
    if (tableView) {
      tableView.classList.remove('d-none');
      if (window.innerWidth < 768 && mobileCards) {
        mobileCards.classList.remove('d-none');
      }
    } else if (emptyState) {
      emptyState.classList.remove('d-none');
    }
    if (listBtn) {
      listBtn.classList.remove('btn-light', 'text-muted');
      listBtn.classList.add('btn-primary', 'shadow-sm', 'text-white', 'active');
    }
    if (titleSpan) titleSpan.textContent = 'Document Flat Registry';
    if (badgeSpan) badgeSpan.textContent = '<?= count($documents) ?> documents';
    try { localStorage.setItem('vt_doc_view', 'table'); } catch(e) {}
  } else {
    // Default is 'folders'
    if (folderView) folderView.classList.remove('d-none');
    if (foldersBtn) {
      foldersBtn.classList.remove('btn-light', 'text-muted');
      foldersBtn.classList.add('btn-primary', 'shadow-sm', 'text-white', 'active');
    }
    if (titleSpan) titleSpan.textContent = 'Applicant Folders Directory';
    if (badgeSpan) badgeSpan.textContent = '<?= count($folders) ?> folders';
    try { localStorage.setItem('vt_doc_view', 'folders'); } catch(e) {}
  }
}

// Automatically configure Popper fixed positioning for all table dropdowns & restore view
document.addEventListener('DOMContentLoaded', function() {
  const savedView = (function() {
    try { return localStorage.getItem('vt_doc_view') || 'folders'; } catch(e) { return 'folders'; }
  })();
  setDocumentView(savedView);

  function initFixedDropdowns() {
    if (window.bootstrap && bootstrap.Dropdown) {
      document.querySelectorAll('.table-responsive [data-bs-toggle="dropdown"]').forEach(function(el) {
        new bootstrap.Dropdown(el, {
          popperConfig: function(defaultConfig) {
            return Object.assign({}, defaultConfig, { strategy: 'fixed' });
          }
        });
      });
    }
  }
  initFixedDropdowns();
  // Run again slightly later in case scripts loaded in footer initialized afterwards
  setTimeout(initFixedDropdowns, 200);

  // Wire explicit submit handlers for document compliance action modals
  function setupDocModalSubmit(formId, btnId, loadingText) {
    const form = document.getElementById(formId);
    const btn = document.getElementById(btnId);
    if (!form || !btn) return;

    btn.addEventListener('click', function(e) {
      // Trigger HTML5 validation check
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      e.preventDefault();
      const originalHtml = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> ' + loadingText;

      const formData = new FormData(form);
      fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
      .then(res => {
        // If server returns JSON
        const contentType = res.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
          return res.json().then(data => ({ ok: res.ok, data }));
        }
        // If server redirected or returned HTML
        return { ok: res.ok, data: { success: res.ok } };
      })
      .then(result => {
        if (result.ok && (result.data.success !== false)) {
          window.location.reload();
        } else {
          alert((result.data && result.data.message) ? result.data.message : 'Operation failed. Please try again.');
          btn.disabled = false;
          btn.innerHTML = originalHtml;
        }
      })
      .catch(err => {
        console.warn('Form fetch fallback to native submit:', err);
        form.submit();
      });
    });
  }

  setupDocModalSubmit('verifyDocForm', 'btnConfirmVerify', 'Verifying...');
  setupDocModalSubmit('rejectDocForm', 'btnConfirmReject', 'Rejecting...');
  setupDocModalSubmit('underReviewDocForm', 'btnConfirmUnderReview', 'Updating...');
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
