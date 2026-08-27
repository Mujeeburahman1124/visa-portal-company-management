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
    </div>

    <div class="col-6 col-md-4 col-xl-2">
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
    </div>

    <div class="col-6 col-md-4 col-xl-2">
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
    </div>

    <div class="col-6 col-md-4 col-xl-2">
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
    </div>

    <div class="col-6 col-md-4 col-xl-2">
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
    </div>

    <div class="col-6 col-md-4 col-xl-2">
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
    </div>
  </div>

  <!-- 3. Advanced Multi-Filter Toolbar (100% Responsive Grid) -->
  <div class="card card-enterprise mb-4 bg-white">
    <div class="card-body p-3">
      <form action="/documents" method="GET" class="row g-2 align-items-center">
        <!-- Search Input -->
        <div class="col-12 col-sm-6 col-md-4 col-xl-3">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search title, applicant, passport, file..." value="<?= e($_GET['search'] ?? '') ?>">
          </div>
        </div>

        <!-- Status Filter -->
        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <select name="status" class="form-select form-select-sm">
            <option value="">All Statuses</option>
            <option value="UNDER_REVIEW" <?= ($_GET['status'] ?? '') === 'UNDER_REVIEW' ? 'selected' : '' ?>>Under Review</option>
            <option value="VERIFIED" <?= ($_GET['status'] ?? '') === 'VERIFIED' ? 'selected' : '' ?>>Verified</option>
            <option value="REJECTED" <?= ($_GET['status'] ?? '') === 'REJECTED' ? 'selected' : '' ?>>Rejected</option>
            <option value="EXPIRING_SOON" <?= ($_GET['status'] ?? '') === 'EXPIRING_SOON' ? 'selected' : '' ?>>Expiring Soon (&le;30d)</option>
            <option value="EXPIRED" <?= ($_GET['status'] ?? '') === 'EXPIRED' ? 'selected' : '' ?>>Expired</option>
          </select>
        </div>

        <!-- Document Type Filter -->
        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <select name="type_id" class="form-select form-select-sm">
            <option value="">All Document Types</option>
            <?php foreach ($docTypes as $dt): ?>
              <option value="<?= $dt['id'] ?>" <?= ((int)($_GET['type_id'] ?? 0)) === (int)$dt['id'] ? 'selected' : '' ?>>
                <?= e($dt['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Visa Package Filter -->
        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <select name="service_id" class="form-select form-select-sm">
            <option value="">All Visa Packages</option>
            <?php foreach ($services as $srv): ?>
              <option value="<?= $srv['id'] ?>" <?= ((int)($_GET['service_id'] ?? 0)) === (int)$srv['id'] ? 'selected' : '' ?>>
                <?= e($srv['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Expiry Filter -->
        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <select name="expiry" class="form-select form-select-sm">
            <option value="">Any Expiry</option>
            <option value="7days" <?= ($_GET['expiry'] ?? '') === '7days' ? 'selected' : '' ?>>Expires in &le;7 days</option>
            <option value="30days" <?= ($_GET['expiry'] ?? '') === '30days' ? 'selected' : '' ?>>Expires in &le;30 days</option>
            <option value="expired" <?= ($_GET['expiry'] ?? '') === 'expired' ? 'selected' : '' ?>>Already Expired</option>
          </select>
        </div>

        <!-- Action Buttons (Joined Responsive Button Group) -->
        <div class="col-12 col-sm-6 col-md-4 col-xl-auto ms-auto d-flex justify-content-end">
          <div class="btn-group btn-group-sm w-100 w-md-auto shadow-sm" role="group" aria-label="Filter Controls">
            <button type="submit" class="btn btn-primary px-3 fw-semibold">
              <i class="fa-solid fa-filter me-1.5"></i> Filter
            </button>
            <a href="/documents" class="btn btn-primary border-start border-white border-opacity-25 px-2.5" title="Clear / Reset Filters">
              <i class="fa-solid fa-rotate-left"></i>
            </a>
          </div>
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
                  <span class="badge bg-light text-secondary border"><?= e($doc['doc_type_name']) ?></span>
                </td>
                <td>
                  <span class="<?= $statusBadgeClass ?> px-2 py-1"><?= e($doc['status']) ?></span>
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
                    <button type="button" class="btn btn-outline-primary" onclick="openDocPreview(<?= $doc['id'] ?>, '<?= e(addslashes($doc['document_title'])) ?>', '<?= e(addslashes($doc['customer_name'])) ?>', '<?= e($doc['file_name']) ?>', '<?= e($doc['status']) ?>', '<?= e(addslashes($doc['rejection_reason'] ?? '')) ?>', '<?= e($doc['expiry_date'] ?? '') ?>')" title="Preview Document">
                      <i class="fa-solid fa-eye"></i>
                    </button>
                    <a href="/documents/download?id=<?= $doc['id'] ?>" class="btn btn-outline-secondary" title="Download File">
                      <i class="fa-solid fa-download"></i>
                    </a>
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                      <span class="visually-hidden">Actions</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="font-size: 0.85rem;">
                      <?php if ($doc['status'] !== 'VERIFIED'): ?>
                        <li><button class="dropdown-item py-2 text-success" onclick="openVerifyModal(<?= $doc['id'] ?>, '<?= e(addslashes($doc['document_title'])) ?>')"><i class="fa-solid fa-check-circle me-2"></i> Verify Document</button></li>
                      <?php endif; ?>
                      <?php if ($doc['status'] !== 'REJECTED'): ?>
                        <li><button class="dropdown-item py-2 text-danger" onclick="openRejectModal(<?= $doc['id'] ?>, '<?= e(addslashes($doc['document_title'])) ?>')"><i class="fa-solid fa-times-circle me-2"></i> Reject Document</button></li>
                      <?php endif; ?>
                      <li><button class="dropdown-item py-2" onclick="openReplaceModal(<?= $doc['id'] ?>, '<?= e(addslashes($doc['document_title'])) ?>')"><i class="fa-solid fa-cloud-arrow-up text-primary me-2"></i> Upload Replacement</button></li>
                      <li><hr class="dropdown-divider my-1"></li>
                      <li><button class="dropdown-item py-2" onclick="openVersionHistoryModal(<?= $doc['id'] ?>, '<?= e(addslashes($doc['document_title'])) ?>')"><i class="fa-solid fa-clock-rotate-left text-secondary me-2"></i> Version History (v<?= (int)$doc['version'] ?>)</button></li>
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
              <button type="button" class="btn btn-outline-primary btn-sm py-1 px-3" onclick="openDocPreview(<?= $doc['id'] ?>, '<?= e(addslashes($doc['document_title'])) ?>', '<?= e(addslashes($doc['customer_name'])) ?>', '<?= e($doc['file_name']) ?>', '<?= e($doc['status']) ?>', '<?= e(addslashes($doc['rejection_reason'] ?? '')) ?>', '<?= e($doc['expiry_date'] ?? '') ?>')">
                <i class="fa-solid fa-eye me-1"></i> Preview
              </button>
              <div class="d-flex gap-1">
                <?php if ($doc['status'] !== 'VERIFIED'): ?>
                  <button type="button" class="btn btn-success btn-sm py-1 px-2" onclick="openVerifyModal(<?= $doc['id'] ?>, '<?= e(addslashes($doc['document_title'])) ?>')" title="Verify">
                    <i class="fa-solid fa-check"></i>
                  </button>
                <?php endif; ?>
                <?php if ($doc['status'] !== 'REJECTED'): ?>
                  <button type="button" class="btn btn-danger btn-sm py-1 px-2" onclick="openRejectModal(<?= $doc['id'] ?>, '<?= e(addslashes($doc['document_title'])) ?>')" title="Reject">
                    <i class="fa-solid fa-xmark"></i>
                  </button>
                <?php endif; ?>
                <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2" onclick="openReplaceModal(<?= $doc['id'] ?>, '<?= e(addslashes($doc['document_title'])) ?>')" title="Replace">
                  <i class="fa-solid fa-cloud-arrow-up"></i>
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
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
      <div class="modal-footer bg-white border-top d-flex justify-content-between">
        <div id="previewStatusBadge"></div>
        <div class="d-flex gap-2">
          <a href="#" id="previewDownloadBtn" class="btn btn-outline-secondary btn-sm" target="_blank">
            <i class="fa-solid fa-download me-1"></i> Download File
          </a>
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
          <div class="alert alert-warning py-2 small mb-3">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> Rejecting this document will mark the application as <strong>Action Required</strong> and notify the case officer.
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
function openDocPreview(id, title, applicant, filename, status, reason, expiry) {
  document.getElementById('previewModalDocTitle').innerText = title;
  document.getElementById('previewModalSubtitle').innerText = 'Applicant: ' + applicant + ' • File: ' + filename;
  document.getElementById('previewDownloadBtn').href = '/documents/download?id=' + id;

  let badge = '<span class="badge bg-secondary">' + status + '</span>';
  if (status === 'VERIFIED') badge = '<span class="badge bg-success">Verified Document</span>';
  else if (status === 'REJECTED') badge = '<span class="badge bg-danger">Rejected: ' + (reason || 'Action Required') + '</span>';
  else if (status === 'UNDER_REVIEW') badge = '<span class="badge bg-warning text-dark">Under Review</span>';

  if (expiry) {
    badge += ' <span class="badge bg-light text-dark border ms-1">Expiry: ' + expiry + '</span>';
  }
  document.getElementById('previewStatusBadge').innerHTML = badge;

  const ext = filename.split('.').pop().toLowerCase();
  const container = document.getElementById('previewContainer');

  if (ext === 'pdf') {
    container.innerHTML = '<iframe src="/documents/preview?id=' + id + '" style="width: 100%; height: 600px; border: none; border-radius: 6px;"></iframe>';
  } else if (['jpg', 'jpeg', 'png'].includes(ext)) {
    container.innerHTML = '<img src="/documents/preview?id=' + id + '" class="img-fluid rounded shadow-sm" style="max-height: 550px; object-fit: contain;">';
  } else {
    container.innerHTML = '<div class="text-center p-5"><i class="fa-solid fa-file-lines fs-1 text-secondary mb-3"></i><h5>Preview not supported for ' + ext.toUpperCase() + '</h5><p class="text-muted small">Please download the file to inspect its contents.</p><a href="/documents/download?id=' + id + '" class="btn btn-primary btn-sm"><i class="fa-solid fa-download me-1"></i> Download ' + filename + '</a></div>';
  }

  const modal = new bootstrap.Modal(document.getElementById('previewModal'));
  modal.show();
}

function openVerifyModal(id, title) {
  document.getElementById('verifyDocId').value = id;
  document.getElementById('verifyDocTitle').innerText = title;
  const modal = new bootstrap.Modal(document.getElementById('verifyModal'));
  modal.show();
}

function openRejectModal(id, title) {
  document.getElementById('rejectDocId').value = id;
  const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
  modal.show();
}

function openReplaceModal(id, title) {
  document.getElementById('replaceDocId').value = id;
  const modal = new bootstrap.Modal(document.getElementById('replaceModal'));
  modal.show();
}

function openVersionHistoryModal(id, title) {
  const body = document.getElementById('versionHistoryBody');
  body.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary mb-2"></div><div>Loading version archive...</div></div>';

  fetch('/documents/history?id=' + id)
    .then(r => r.json())
    .then(res => {
      if (res.data && res.data.length > 0) {
        let html = '<div class="table-responsive"><table class="table-custom"><thead><tr><th>Version</th><th>File Name</th><th>Size</th><th>Uploaded By</th><th>Date</th><th>Archived Reason</th></tr></thead><tbody>';
        res.data.forEach(v => {
          html += '<tr><td><span class="badge bg-secondary">v' + v.version_number + '</span></td><td>' + v.file_name + '</td><td>' + (parseFloat(v.file_size) / 1024).toFixed(1) + ' KB</td><td>' + (v.uploader_name || v.uploaded_by_type) + '</td><td class="small text-muted">' + v.created_at + '</td><td class="small text-danger">' + (v.rejection_reason || '—') + '</td></tr>';
        });
        html += '</tbody></table></div>';
        body.innerHTML = html;
      } else {
        body.innerHTML = '<div class="p-4 text-center text-muted">No previous version history archived for this document (Current is Version 1).</div>';
      }
    })
    .catch(() => {
      body.innerHTML = '<div class="p-4 text-center text-danger">Failed to load version history.</div>';
    });

  const modal = new bootstrap.Modal(document.getElementById('versionHistoryModal'));
  modal.show();
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
