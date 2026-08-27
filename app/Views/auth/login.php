<?php
$pageTitle = 'Staff Sign In — VISA TRACK';
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
  <link rel="stylesheet" href="/assets/css/style.css?v=5.0.0">
</head>
<body class="auth-page-body">

<div class="container d-flex flex-column align-items-center justify-content-center" style="min-height: 100vh; padding: 1.5rem 1rem;">
  
  <!-- Compact Centered Card -->
  <div class="auth-card-compact">
    <!-- Brand Header -->
    <div class="text-center mb-3">
      <img src="/assets/images/logo.png" alt="MS Travel Hub Logo" style="max-height: 68px; width: auto;" class="mb-2">
      <h3 class="fw-bold brand-font text-dark mb-0" style="font-size: 1.45rem; letter-spacing: -0.01em;">MS TRAVEL HUB</h3>
      <div class="text-muted small fw-semibold" style="font-size: 0.78rem;">Global Visa Management Portal</div>
    </div>

    <div class="text-center mb-3">
      <h5 class="fw-bold text-dark mb-0" style="font-size: 1.15rem;">Staff &amp; Operations Sign In</h5>
      <p class="text-muted small mb-0" style="font-size: 0.82rem;">Sign in to access your visa operations pipeline</p>
    </div>

    <!-- Flash Alert -->
    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show py-2 px-3 small mb-3 border-0 shadow-sm" role="alert">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
          <span><?= e($flash['message']) ?></span>
        </div>
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form action="/auth/login" method="POST" id="loginForm" novalidate>
      <?= csrf_field() ?>

      <div class="mb-2.5">
        <label for="loginEmail" class="form-label small fw-semibold text-secondary mb-1" style="font-size: 0.85rem;">Work Email</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0 text-muted" style="font-size: 0.85rem;"><i class="fa-solid fa-envelope"></i></span>
          <input type="email" name="email" id="loginEmail" class="form-control border-start-0 ps-0" placeholder="admin@visatrack.com" required value="admin@visatrack.com" autocomplete="username" style="font-size: 0.9rem; height: 40px;">
        </div>
      </div>

      <div class="mb-2.5">
        <div class="d-flex align-items-center justify-content-between mb-1">
          <label for="loginPassword" class="form-label small fw-semibold text-secondary mb-0" style="font-size: 0.85rem;">Password</label>
          <a href="/auth/forgot-password" class="small text-primary text-decoration-none fw-semibold" style="font-size: 0.76rem;">Forgot password?</a>
        </div>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0 text-muted" style="font-size: 0.85rem;"><i class="fa-solid fa-lock"></i></span>
          <input type="password" name="password" id="loginPassword" class="form-control border-start-0 border-end-0 px-0" placeholder="••••••••" required value="admin123" autocomplete="current-password" style="font-size: 0.9rem; height: 40px;">
          <button type="button" class="input-group-text bg-light border-start-0 text-muted" id="togglePasswordBtn" aria-label="Toggle password visibility">
            <i class="fa-solid fa-eye" id="togglePasswordIcon" style="font-size: 0.82rem;"></i>
          </button>
        </div>
      </div>

      <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
          <label class="form-check-label small text-muted user-select-none" for="rememberMe" style="font-size: 0.8rem;">
            Remember this session
          </label>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100 fw-semibold shadow-sm rounded-2" id="submitBtn" style="height: 42px; font-size: 0.92rem;">
        <span class="spinner-border spinner-border-sm me-2 d-none" id="submitSpinner" role="status" aria-hidden="true"></span>
        <i class="fa-solid fa-right-to-bracket me-1.5" id="submitIcon"></i> Sign In to Operations
      </button>
    </form>

    <!-- Quick Role Testing (Compact) -->
    <div class="mt-3 pt-2.5 border-top">
      <div class="text-center mb-1.5">
        <span class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.04em;">
          <i class="fa-solid fa-bolt text-warning me-1"></i> Fast Role Fill
        </span>
      </div>
      <div class="d-flex flex-wrap justify-content-center gap-1">
        <button type="button" class="btn btn-outline-secondary btn-sm py-0.5 px-2 text-dark" style="font-size: 0.72rem;" onclick="fillCreds('admin@visatrack.com', 'admin123')">Admin</button>
        <button type="button" class="btn btn-outline-secondary btn-sm py-0.5 px-2 text-dark" style="font-size: 0.72rem;" onclick="fillCreds('manager@visatrack.com', 'password123')">Manager</button>
        <button type="button" class="btn btn-outline-secondary btn-sm py-0.5 px-2 text-dark" style="font-size: 0.72rem;" onclick="fillCreds('officer@visatrack.com', 'password123')">Officer</button>
        <button type="button" class="btn btn-outline-secondary btn-sm py-0.5 px-2 text-dark" style="font-size: 0.72rem;" onclick="fillCreds('staff@visatrack.com', 'password123')">Staff</button>
        <button type="button" class="btn btn-outline-secondary btn-sm py-0.5 px-2 text-dark" style="font-size: 0.72rem;" onclick="fillCreds('accounts@visatrack.com', 'password123')">Accounts</button>
      </div>
    </div>

    <!-- Portal Link -->
    <div class="text-center mt-2.5 small text-muted" style="font-size: 0.78rem;">
      Applicant? <a href="/portal/login" class="text-primary fw-semibold text-decoration-none">Customer Tracking Portal &rarr;</a>
    </div>
  </div>
  
  <div class="text-center text-muted small mt-3" style="font-size: 0.75rem;">
    &copy; <?= date('Y') ?> VISA TRACK &bull; Enterprise Visa Operations
  </div>
</div>

<script>
function fillCreds(email, pass) {
  document.getElementById('loginEmail').value = email;
  document.getElementById('loginPassword').value = pass;
}

document.getElementById('togglePasswordBtn')?.addEventListener('click', function() {
  const pwdInput = document.getElementById('loginPassword');
  const icon = document.getElementById('togglePasswordIcon');
  if (pwdInput.type === 'password') {
    pwdInput.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    pwdInput.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
});

document.getElementById('loginForm')?.addEventListener('submit', function() {
  const btn = document.getElementById('submitBtn');
  const spinner = document.getElementById('submitSpinner');
  const icon = document.getElementById('submitIcon');
  btn.disabled = true;
  spinner.classList.remove('d-none');
  icon.classList.add('d-none');
  this.submit();
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
