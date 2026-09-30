<?php
$pageTitle = 'DOCUMENT MANAGEMENT — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
?>

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
            <i class="fa-solid fa-file-lines"></i>
          </div>
          <div class="stat-card-content">
            <div class="stat-title">Total Docs</div>
            <div class="stat-value"><?= $stats['total'] ?></div>
            <div class="stat-trend"><i class="fa-solid fa-vault me-1"></i>All records</div>
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
    <?php if (empty($documents)): ?>
      <div class="empty-state-box p-5 text-center">
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
      <div class="table-responsive d-none d-md-block">
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
                  <!-- Interactive Status Button Dropdown -->
                  <div class="dropdown d-inline-block">
                    <button class="btn btn-sm dropdown-toggle p-0 border-0 bg-transparent text-decoration-none shadow-none" 
                            type="button" 
                            data-bs-toggle="dropdown" 
                            data-bs-popper-config='{"strategy":"fixed"}'
                            data-bs-display="static"
                            aria-expanded="false" 
                            title="Click to change document compliance status">
                      <span class="<?= $statusBadgeClass ?> px-2.5 py-1 d-inline-flex align-items-center gap-1 shadow-xs" style="cursor: pointer;">
                        <?= e($doc['status']) ?> <i class="fa-solid fa-chevron-down" style="font-size: 0.6rem; opacity: 0.8;"></i>
                      </span>
                    </button>
                    <ul class="dropdown-menu shadow border-0" style="font-size: 0.85rem; z-index: 1065;">
                      <li class="dropdown-header text-uppercase small py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">Update Status</li>
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
                <td class="text-end">
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
                            onclick="openDocPreview(this)" 
                            title="Preview Document">
                      <i class="fa-solid fa-eye"></i>
                    </button>
                    <a href="/documents/download?id=<?= $doc['id'] ?>" class="btn btn-outline-secondary" title="Download File">
                      <i class="fa-solid fa-download"></i>
                    </a>
                    <button type="button" 
                            class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" 
                            data-bs-toggle="dropdown" 
                            data-bs-popper-config='{"strategy":"fixed"}'
                            data-bs-display="static"
                            aria-expanded="false">
                      <span class="visually-hidden">Actions</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="font-size: 0.85rem; z-index: 1065;">
                      <li class="dropdown-header text-uppercase small py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">Status &amp; Verification</li>
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
                      <li class="dropdown-header text-uppercase small py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">File Management</li>
                      <li>
                        <button type="button" 
                                class="dropdown-item py-2" 
                                data-doc-id="<?= (int)$doc['id'] ?>"
                                data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                                onclick="openReplaceModal(this)">
                          <i class="fa-solid fa-cloud-arrow-up text-primary me-2"></i> Upload Replacement
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

      <!-- Mobile Document Cards (< 768px) -->
      <div class="d-md-none p-3">
        <?php foreach ($documents as $doc): ?>
          <div class="card border rounded-3 p-3 mb-3 shadow-sm bg-white">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <div class="fw-bold text-dark"><?= e($doc['document_title']) ?></div>
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

            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
              <button type="button" 
                      class="btn btn-outline-primary btn-sm py-1 px-2.5" 
                      data-doc-id="<?= (int)$doc['id'] ?>"
                      data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                      data-doc-applicant="<?= htmlspecialchars($doc['customer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                      data-doc-filename="<?= htmlspecialchars($doc['file_name'], ENT_QUOTES, 'UTF-8') ?>"
                      data-doc-status="<?= htmlspecialchars($doc['status'], ENT_QUOTES, 'UTF-8') ?>"
                      data-doc-reason="<?= htmlspecialchars($doc['rejection_reason'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                      data-doc-expiry="<?= htmlspecialchars($doc['expiry_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                      onclick="openDocPreview(this)">
                <i class="fa-solid fa-eye me-1"></i> Preview
              </button>
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
                        class="btn btn-outline-secondary btn-sm py-1 px-2" 
                        data-doc-id="<?= (int)$doc['id'] ?>"
                        data-doc-title="<?= htmlspecialchars($doc['document_title'] ?: $doc['doc_type_name'], ENT_QUOTES, 'UTF-8') ?>"
                        onclick="openReplaceModal(this)" 
                        title="Upload Replacement">
                  <i class="fa-solid fa-cloud-arrow-up"></i>
                </button>
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

<!-- 2. Verify Confirmation Modal -->
<div class="modal fade" id="verifyModal" tabindex="-1" aria-labelledby="verifyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title fw-bold fs-6" id="verifyModalLabel"><i class="fa-solid fa-circle-check me-2"></i> Verify Compliance Document</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/documents/verify" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="document_id" id="verifyDocId" value="">

        <div class="modal-body p-4">
          <p class="mb-3">Confirm that you have reviewed <strong id="verifyDocTitle">this document</strong> and verified that it meets all official consular and embassy compliance standards?</p>
          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Verification Audit Notes <small class="text-muted">(Optional)</small></label>
            <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Verified clear passport scan with 6+ months validity."></textarea>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success fw-semibold"><i class="fa-solid fa-check me-1"></i> Confirm Verification</button>
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
      <form action="/documents/reject" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="document_id" id="rejectDocId" value="">

        <div class="modal-body p-4">
          <p class="mb-3">Reject <strong id="rejectDocTitle">this document</strong>? This will mark the application as <strong>Action Required</strong> and notify the case officer.</p>
          <div class="alert alert-warning py-2 small mb-3">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> A mandatory rejection reason must be provided. The applicant will be notified to upload a replacement.
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Rejection Reason <span class="text-danger">*</span></label>
            <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Mandatory explanation (e.g. Passport copy is blurred, Expiry date is less than 6 months, Name does not match application)..."></textarea>
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Internal Operational Notes</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes for internal case team..."></textarea>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger fw-semibold"><i class="fa-solid fa-ban me-1"></i> Confirm Rejection</button>
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
      <form action="/documents/under-review" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="document_id" id="underReviewDocId" value="">

        <div class="modal-body p-4">
          <p class="mb-3">Reset <strong id="underReviewDocTitle">this document</strong> status back to <span class="badge bg-warning text-dark">Under Review</span>? Any previous rejection or approval state will be cleared for re-examination.</p>
          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Notes <small class="text-muted">(Optional)</small></label>
            <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Document returned to review queue for verification."></textarea>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning text-dark fw-semibold"><i class="fa-solid fa-rotate-left me-1"></i> Confirm Under Review</button>
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
      expiry: target.dataset.docExpiry || ''
    };
  }
  return {
    id: target || '',
    title: defaultTitle || 'Document',
    applicant: defaultApplicant || '',
    filename: '',
    status: '',
    reason: '',
    expiry: ''
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

function openDocPreview(idOrEl, maybeTitle, maybeApplicant, maybeFilename, maybeStatus, maybeReason, maybeExpiry) {
  let id, title, applicant, filename, status, reason, expiry;

  if (idOrEl && typeof idOrEl === 'object' && idOrEl.dataset) {
    const d = getDocData(idOrEl);
    id = d.id;
    title = d.title;
    applicant = d.applicant;
    filename = d.filename;
    status = d.status;
    reason = d.reason;
    expiry = d.expiry;
  } else {
    id = idOrEl;
    title = maybeTitle || 'Document';
    applicant = maybeApplicant || '';
    filename = maybeFilename || '';
    status = maybeStatus || '';
    reason = maybeReason || '';
    expiry = maybeExpiry || '';
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

// Automatically configure Popper fixed positioning for all table dropdowns
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
