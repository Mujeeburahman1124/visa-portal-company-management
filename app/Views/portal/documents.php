<?php
$pageTitle = 'My Visa Documents & Checklist — VISA TRACK';
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
  <link rel="stylesheet" href="/assets/css/main.css?v=7.0.0">
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

  <!-- Page Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <h3 class="fw-bold brand-font text-dark mb-1">Required Documents &amp; Upload Vault</h3>
      <p class="text-muted small mb-0">Upload mandatory embassy documents, verify status, and respond to consular replacement requests.</p>
    </div>

    <!-- Application Selector if multiple applications exist -->
    <?php if (count($customerApps) > 1): ?>
      <form action="/portal/documents" method="GET" class="d-flex align-items-center gap-2">
        <label class="small fw-semibold text-muted">Application:</label>
        <select name="app_id" class="form-select form-select-sm" onchange="this.form.submit()">
          <?php foreach ($customerApps as $cApp): ?>
            <option value="<?= $cApp['id'] ?>" <?= $appId === (int)$cApp['id'] ? 'selected' : '' ?>>
              <?= e($cApp['application_number']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </form>
    <?php endif; ?>
  </div>

  <!-- Document Checklist Matrix Card -->
  <div class="card card-enterprise shadow-sm">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
      <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-list-check text-primary me-2"></i> Application Document Checklist</h6>
      <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#custUploadModal">
        <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload Document
      </button>
    </div>

    <div class="card-body p-0">
      <?php if (empty($checklist)): ?>
        <div class="text-center py-5 text-muted">
          <i class="fa-solid fa-file-circle-question fs-2 mb-2"></i>
          <div class="fw-semibold">No document requirements found for this file.</div>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table-modern mb-0">
            <thead>
              <tr>
                <th style="min-width: 220px;">Document Name</th>
                <th style="min-width: 120px;">Category</th>
                <th style="min-width: 120px;">Requirement</th>
                <th style="min-width: 150px;">Verification Status</th>
                <th style="min-width: 200px;">Officer Remarks</th>
                <th class="text-end" style="min-width: 130px;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($checklist as $item): ?>
                <?php
                  if (!is_array($item)) continue;
                  $status = $item['status'] ?? 'PENDING';
                  $statusClass = 'bg-secondary-subtle text-dark';
                  $statusIcon = 'fa-clock';
                  if ($status === 'VERIFIED') {
                      $statusClass = 'bg-success-subtle text-success';
                      $statusIcon = 'fa-circle-check';
                  } elseif ($status === 'UNDER_REVIEW') {
                      $statusClass = 'bg-info-subtle text-info';
                      $statusIcon = 'fa-spinner fa-spin-pulse';
                  } elseif ($status === 'REJECTED' || $status === 'ACTION_REQUIRED') {
                      $statusClass = 'bg-danger-subtle text-danger';
                      $statusIcon = 'fa-triangle-exclamation';
                  }
                  $docName = $item['document_name'] ?? ($item['name'] ?? 'Required Document');
                  $docTypeId = (int)($item['document_type_id'] ?? ($item['id'] ?? 0));
                ?>
                <tr>
                  <td>
                    <div class="fw-bold text-dark"><?= e($docName) ?></div>
                    <?php if (!empty($item['file_name'])): ?>
                      <div class="text-muted font-monospace" style="font-size: 0.72rem;">
                        <i class="fa-solid fa-paperclip text-primary me-1"></i><?= e($item['file_name']) ?>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge bg-light text-secondary border"><?= e($item['category'] ?? 'General') ?></span>
                  </td>
                  <td>
                    <span class="badge <?= !empty($item['is_mandatory']) ? 'bg-danger-subtle text-danger' : 'bg-light text-muted border' ?>">
                      <?= !empty($item['is_mandatory']) ? 'Mandatory' : 'Optional' ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge <?= $statusClass ?> fw-bold px-2.5 py-1.5" style="font-size: 0.74rem;">
                      <i class="fa-solid <?= $statusIcon ?> me-1"></i><?= e($status) ?>
                    </span>
                  </td>
                  <td>
                    <?php if (!empty($item['rejection_reason'])): ?>
                      <div class="p-1.5 bg-danger bg-opacity-10 border border-danger rounded text-danger small">
                        <strong>Reason:</strong> <?= e($item['rejection_reason']) ?>
                      </div>
                    <?php else: ?>
                      <span class="text-muted small">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <?php if ($status === 'REJECTED' || $status === 'ACTION_REQUIRED' || empty($item['file_path'])): ?>
                      <button type="button" class="btn btn-sm btn-primary py-1 px-3 fw-semibold shadow-sm" 
                              onclick="openUploadForDoc(<?= $docTypeId ?>, '<?= e(addslashes($docName)) ?>')">
                        <i class="fa-solid fa-upload me-1"></i> Upload
                      </button>
                    <?php else: ?>
                      <span class="badge bg-success-subtle text-success border small">
                        <i class="fa-solid fa-check me-1"></i>Submitted
                      </span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- MODAL: UPLOAD DOCUMENT -->
<div class="modal fade" id="custUploadModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-cloud-arrow-up me-2"></i> Upload Document File</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/portal/upload" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="application_id" value="<?= $appId ?>">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Select Document Type <span class="text-danger">*</span></label>
            <select name="document_type_id" id="modalDocTypeSelect" class="form-select" required>
              <?php foreach ($docTypes as $dt): ?>
                <option value="<?= $dt['id'] ?>"><?= e($dt['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Choose File (PDF, JPG, PNG) <span class="text-danger">*</span></label>
            <input type="file" name="document_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.docx" required>
            <div class="form-text small">Max file size 10MB. Ensure document edges and machine-readable zones are clearly visible.</div>
          </div>

          <div class="alert alert-info small mb-0">
            <i class="fa-solid fa-shield-halved me-1"></i> Uploaded documents are securely transmitted directly to your dedicated visa operations officer.
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">Submit for Verification</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openUploadForDoc(docTypeId, docName) {
  const select = document.getElementById('modalDocTypeSelect');
  if (select) select.value = docTypeId;
  const modal = new bootstrap.Modal(document.getElementById('custUploadModal'));
  modal.show();
}
</script>
</body>
</html>

