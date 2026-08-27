<?php
$pageTitle = 'Set New Password — VISA TRACK';
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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css?v=1.3.0">
</head>
<body class="auth-page-body">

<div class="auth-card-container">
  <!-- Brand Header -->
  <div class="text-center mb-4">
    <div class="auth-logo-badge mb-3">
      <i class="fa-solid fa-passport"></i>
    </div>
    <h3 class="auth-brand-title">VISA TRACK</h3>
    <div class="auth-brand-subtitle">STAFF VISA TRACKING &amp; MANAGEMENT SYSTEM</div>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show auth-alert" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="card auth-card shadow-sm border-0">
    <div class="card-body p-4 p-md-5">
      <div class="mb-4">
        <h4 class="fw-bold mb-1" style="color: #0f172a;">Set New Password</h4>
        <p class="text-muted small mb-0">Choose a secure password with at least 8 characters.</p>
      </div>

      <form action="/auth/reset-password" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($_GET['token'] ?? '') ?>">

        <div class="mb-3">
          <label for="newPassword" class="form-label small fw-semibold text-secondary">New Password</label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
            <input type="password" name="password" id="newPassword" class="form-control border-start-0 ps-0" placeholder="Minimum 8 characters" required minlength="8">
          </div>
        </div>

        <div class="mb-4">
          <label for="confirmPassword" class="form-label small fw-semibold text-secondary">Confirm New Password</label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
            <input type="password" name="password_confirm" id="confirmPassword" class="form-control border-start-0 ps-0" placeholder="Re-enter new password" required minlength="8">
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm mb-3">
          <i class="fa-solid fa-check-double me-2"></i> Update Password &amp; Sign In
        </button>

        <div class="text-center">
          <a href="/auth/login" class="text-muted small text-decoration-none">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Sign In
          </a>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
