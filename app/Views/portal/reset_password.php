<?php
$pageTitle = 'Reset Password — Applicant Portal';
$flash = $flash ?? get_flash();
$token = $_GET['token'] ?? '';
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
        <i class="fa-solid fa-lock-open"></i>
      </div>
      <h3 class="fw-bold brand-font text-dark mb-0" style="font-size:1.45rem;">VISA TRACK</h3>
      <div class="text-muted small" style="font-size:0.78rem;">Applicant Portal — Set New Password</div>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : 'success') ?> alert-dismissible fade show py-2 px-3 small mb-3 border-0 shadow-sm">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : 'fa-circle-check' ?> me-1"></i>
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form action="/portal/reset-password" method="POST" id="portalResetForm">
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">

      <div class="mb-3">
        <label class="form-label small fw-semibold text-secondary mb-1" style="font-size:0.85rem;">New Password</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
          <input type="password" name="password" id="newPassword" class="form-control border-start-0 ps-0" placeholder="Minimum 6 characters" required minlength="6" style="font-size:0.9rem;height:40px;">
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label small fw-semibold text-secondary mb-1" style="font-size:0.85rem;">Confirm New Password</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
          <input type="password" name="password_confirm" id="confirmPassword" class="form-control border-start-0 ps-0" placeholder="Repeat password" required style="font-size:0.9rem;height:40px;">
        </div>
      </div>
      <button type="submit" class="btn btn-primary w-100 fw-semibold shadow-sm rounded-2 mb-2" style="height:42px;font-size:0.92rem;">
        <i class="fa-solid fa-shield-check me-1"></i> Update Password
      </button>
    </form>

    <div class="text-center small text-muted mt-2" style="font-size:0.78rem;">
      <a href="/portal/login" class="text-primary fw-semibold text-decoration-none">&larr; Back to Login</a>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
