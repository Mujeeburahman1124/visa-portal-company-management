<?php
$pageTitle = 'Forgot Password — Applicant Portal';
$flash = $flash ?? get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>✈️</text></svg>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/main.css?v=7.0.0">
</head>
<body class="auth-page-body">
<div class="container d-flex flex-column align-items-center justify-content-center" style="min-height:100vh;padding:1.5rem 1rem;">
  <div class="auth-card-compact">
    <div class="text-center mb-3">
      <div class="d-inline-flex align-items-center justify-content-center text-white rounded-3 shadow-sm mb-2" style="width:44px;height:44px;font-size:1.25rem;background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%);">
        <i class="fa-solid fa-key"></i>
      </div>
      <h3 class="fw-bold brand-font text-dark mb-0" style="font-size:1.45rem;letter-spacing:-0.01em;">VISA TRACK</h3>
      <div class="text-muted small" style="font-size:0.78rem;">Applicant Portal — Password Recovery</div>
    </div>

    <div class="text-center mb-3">
      <h5 class="fw-bold text-dark mb-1" style="font-size:1.1rem;">Forgot Your Password?</h5>
      <p class="text-muted small mb-0" style="font-size:0.82rem;">Enter your registered email. We'll send a secure reset link.</p>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show py-2 px-3 small mb-3 border-0 shadow-sm">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : 'fa-circle-check' ?> me-1"></i>
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form action="/portal/forgot-password" method="POST" id="portalForgotForm">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label small fw-semibold text-secondary mb-1" style="font-size:0.85rem;">Email Address</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0 text-muted" style="font-size:0.85rem;"><i class="fa-solid fa-envelope"></i></span>
          <input type="email" name="email" id="forgotEmail" class="form-control border-start-0 ps-0" placeholder="your.email@domain.com" required autofocus style="font-size:0.9rem;height:40px;">
        </div>
      </div>
      <button type="submit" class="btn btn-primary w-100 fw-semibold shadow-sm rounded-2 mb-2" style="height:42px;font-size:0.92rem;">
        <i class="fa-solid fa-paper-plane me-1"></i> Send Reset Link
      </button>
    </form>

    <div class="text-center small text-muted mt-2" style="font-size:0.78rem;">
      Remembered it? <a href="/portal/login" class="text-primary fw-semibold text-decoration-none">Back to Login &rarr;</a>
    </div>
  </div>

  <div class="text-center text-muted small mt-3" style="font-size:0.75rem;">
    &copy; <?= date('Y') ?> VISA TRACK &bull; Applicant Self-Service
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
