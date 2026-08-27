<?php
$pageTitle = 'Forgot Password — VISA TRACK';
$flash = get_flash();
$demoLink = $_SESSION['demo_reset_link'] ?? null;
unset($_SESSION['demo_reset_link']);
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

  <?php if ($demoLink): ?>
    <div class="alert alert-info border-0 shadow-sm mb-3">
      <div class="fw-bold small mb-1"><i class="fa-solid fa-envelope-open-text me-1"></i> Simulated Email Delivery (Local Environment):</div>
      <div class="small mb-2 text-muted">A password reset token was generated. Click the link below to test the reset flow:</div>
      <a href="<?= e($demoLink) ?>" class="btn btn-sm btn-primary">Proceed to Reset Password &rarr;</a>
    </div>
  <?php endif; ?>

  <div class="card auth-card shadow-sm border-0">
    <div class="card-body p-4 p-md-5">
      <div class="mb-4">
        <h4 class="fw-bold mb-1" style="color: #0f172a;">Password Recovery</h4>
        <p class="text-muted small mb-0">Enter your registered work email to receive password reset instructions.</p>
      </div>

      <form action="/auth/forgot-password" method="POST">
        <?= csrf_field() ?>

        <div class="mb-4">
          <label for="recoveryEmail" class="form-label small fw-semibold text-secondary">Work Email Address</label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-envelope"></i></span>
            <input type="email" name="email" id="recoveryEmail" class="form-control border-start-0 ps-0" placeholder="user@visatrack.com" required value="admin@visatrack.com">
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm mb-3">
          <i class="fa-solid fa-paper-plane me-2"></i> Send Reset Link
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
