<?php
$pageTitle = '403 — Access Denied';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';
require_once __DIR__ . '/topbar.php';
?>
<div class="content-body d-flex align-items-center justify-content-center" style="min-height: 70vh;">
  <div class="text-center" style="max-width: 480px;">
    <div class="display-1 text-danger fw-bold"><i class="fa-solid fa-shield-halved"></i></div>
    <h2 class="mt-3 fw-bold">Access Forbidden</h2>
    <p class="text-muted">Your active role does not have administrative authorization to perform or view this restricted module.</p>
    <div class="mt-4">
      <a href="/dashboard" class="btn btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Return to Dashboard</a>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
