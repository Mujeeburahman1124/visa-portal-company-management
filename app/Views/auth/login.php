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
  <link rel="icon" type="image/png" href="/assets/images/favicon.png?v=2">
  <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico?v=2">
  <link rel="apple-touch-icon" href="/assets/images/logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/main.css?v=7.1.0">
  <style>
    .auth-login-bg {
      min-height: 100vh;
      background: linear-gradient(135deg, #060e1c 0%, #0a1e3d 40%, #0d3462 70%, #0f4c75 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
      position: relative;
      overflow: hidden;
    }
    .auth-login-bg::before {
      content: '';
      position: absolute;
      top: -100px; left: -100px;
      width: 400px; height: 400px;
      background: radial-gradient(circle, rgba(34,197,94,0.12) 0%, transparent 70%);
      pointer-events: none;
    }
    .auth-login-bg::after {
      content: '';
      position: absolute;
      bottom: -100px; right: -80px;
      width: 350px; height: 350px;
      background: radial-gradient(circle, rgba(6,182,212,0.12) 0%, transparent 70%);
      pointer-events: none;
    }
    .login-card {
      background: rgba(255,255,255,0.98);
      border-radius: 20px;
      padding: 2.5rem 2.25rem;
      width: 100%;
      max-width: 440px;
      box-shadow: 0 25px 60px rgba(6,14,28,0.5), 0 8px 24px rgba(37,99,235,0.15);
      border: 1px solid rgba(37,99,235,0.15);
      position: relative;
      z-index: 2;
    }
    .login-card .logo-wrap {
      text-align: center;
      margin-bottom: 1.5rem;
      padding-bottom: 1.25rem;
      border-bottom: 1px solid #e8f0fe;
    }
    .login-card .logo-wrap img {
      height: 72px; width: auto;
      filter: drop-shadow(0 4px 12px rgba(37,99,235,0.20));
      margin-bottom: 0.75rem;
    }
    .login-card .brand-name {
      font-family: 'Times New Roman', Times, serif;
      font-size: 1.5rem;
      font-weight: 800;
      color: #0f172a;
      letter-spacing: -0.02em;
      margin-bottom: 0.1rem;
    }
    .login-card .brand-name .grad {
      background: linear-gradient(135deg, #22c55e 0%, #06b6d4 50%, #2563eb 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .login-card .brand-tagline {
      font-size: 0.76rem;
      color: #64748b;
      font-weight: 600;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }
    .login-section-title {
      font-size: 1.1rem;
      font-weight: 700;
      color: #0f172a;
      margin-bottom: 0.35rem;
    }
    .login-section-sub {
      font-size: 0.82rem;
      color: #64748b;
      margin-bottom: 1.25rem;
    }
    .login-input-wrap {
      margin-bottom: 1rem;
    }
    .login-input-wrap label {
      display: block;
      font-size: 0.82rem;
      font-weight: 600;
      color: #374151;
      margin-bottom: 0.35rem;
    }
    .login-input-group {
      display: flex;
      align-items: center;
      border: 1.5px solid #dce8f5;
      border-radius: 10px;
      background: #f8fbff;
      overflow: hidden;
      transition: all 0.2s ease;
    }
    .login-input-group:focus-within {
      border-color: #2563eb;
      background: #ffffff;
      box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
    }
    .login-input-group .icon {
      padding: 0 0.75rem;
      color: #94a3b8;
      font-size: 0.9rem;
      flex-shrink: 0;
    }
    .login-input-group input {
      flex: 1;
      border: none;
      background: transparent;
      padding: 0.65rem 0.5rem;
      font-size: 0.9rem;
      color: #0f172a;
      outline: none;
      font-family: 'Times New Roman', Times, serif;
      min-height: 44px;
    }
    .login-input-group .toggle-btn {
      background: none;
      border: none;
      padding: 0 0.75rem;
      color: #94a3b8;
      cursor: pointer;
      transition: color 0.15s;
    }
    .login-input-group .toggle-btn:hover { color: #2563eb; }
    .btn-login {
      display: block;
      width: 100%;
      padding: 0.75rem 1.5rem;
      background: linear-gradient(135deg, #22c55e 0%, #06b6d4 50%, #2563eb 100%);
      color: #ffffff;
      border: none;
      border-radius: 10px;
      font-size: 0.95rem;
      font-weight: 700;
      letter-spacing: 0.02em;
      cursor: pointer;
      transition: all 0.2s ease;
      box-shadow: 0 4px 14px rgba(37,99,235,0.25);
      font-family: 'Times New Roman', Times, serif;
      margin-top: 0.5rem;
    }
    .btn-login:hover {
      opacity: 0.92;
      transform: translateY(-2px);
      box-shadow: 0 8px 22px rgba(37,99,235,0.35);
    }
    .btn-login:disabled { opacity: 0.7; cursor: not-allowed; }
    .quick-fill-btn {
      background: #f0f6ff;
      border: 1px solid #bfdbfe;
      color: #1e40af;
      border-radius: 6px;
      padding: 0.25rem 0.65rem;
      font-size: 0.7rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.15s;
      font-family: 'Times New Roman', Times, serif;
    }
    .quick-fill-btn:hover {
      background: #2563eb;
      border-color: #2563eb;
      color: #ffffff;
    }
    .remember-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 0.75rem;
    }
    .remember-row .form-check-label {
      font-size: 0.82rem;
      color: #64748b;
    }
    .forgot-link {
      font-size: 0.8rem;
      color: #2563eb;
      text-decoration: none;
      font-weight: 600;
    }
    .forgot-link:hover { text-decoration: underline; }
    .portal-link-row {
      text-align: center;
      margin-top: 1.25rem;
      font-size: 0.8rem;
      color: #64748b;
    }
    .portal-link-row a {
      color: #2563eb;
      font-weight: 700;
      text-decoration: none;
    }
    .portal-link-row a:hover { text-decoration: underline; }
    .footer-copy {
      text-align: center;
      margin-top: 1.5rem;
      font-size: 0.72rem;
      color: rgba(255,255,255,0.45);
      position: relative;
      z-index: 2;
    }
    @media (max-width: 576px) {
      .login-card { padding: 1.25rem 1rem !important; border-radius: 14px; }
      .login-card .logo-wrap img { height: 42px !important; }
      .login-card .brand-name { font-size: 1.15rem !important; }
      .auth-container-wrap { padding: 0.75rem 0.5rem !important; min-height: 100vh !important; }
      .auth-card-compact { padding: 1.25rem 1rem !important; border-radius: 14px !important; }
      .brand-header-img { max-height: 42px !important; margin-bottom: 0.25rem !important; }
      .brand-title-text { font-size: 1.2rem !important; }
      .brand-subtitle-text { display: none !important; }
      .login-sub-text { display: none !important; }
      .login-title-text { font-size: 1.05rem !important; margin-bottom: 0.25rem !important; }
      .header-wrap-mb { margin-bottom: 0.65rem !important; }
      .form-field-mb { margin-bottom: 0.55rem !important; }
      .input-group input, .input-group span, .input-group button { height: 38px !important; font-size: 0.86rem !important; }
      .btn-login-submit { height: 38px !important; font-size: 0.88rem !important; }
      .portal-switcher-wrap { margin-top: 0.65rem !important; padding-top: 0.5rem !important; }
      .footer-copy-text { margin-top: 0.5rem !important; font-size: 0.7rem !important; }
    }
  </style>
</head>
<body class="auth-page-body">

<div class="container d-flex flex-column align-items-center justify-content-center auth-container-wrap" style="min-height: 100vh; padding: 1.5rem 1rem;">
  
  <!-- Compact Centered Card -->
  <div class="auth-card-compact shadow-sm">
    <!-- Brand Header -->
    <div class="text-center header-wrap-mb mb-3">
      <img src="/assets/images/logo.png" alt="MS Travel Hub Logo" style="max-height: 60px; width: auto;" class="mb-1 brand-header-img">
      <h3 class="fw-bold brand-font text-dark mb-0 brand-title-text" style="font-size: 1.35rem; letter-spacing: -0.01em;">MS TRAVEL HUB</h3>
      <div class="text-muted small fw-semibold brand-subtitle-text" style="font-size: 0.75rem;">Global Visa Management Portal</div>
    </div>

    <div class="text-center header-wrap-mb mb-3">
      <h5 class="fw-bold text-dark mb-0 login-title-text" style="font-size: 1.1rem;">Staff &amp; Operations Sign In</h5>
      <p class="text-muted small mb-0 login-sub-text" style="font-size: 0.8rem;">Access your visa operations dashboard</p>
    </div>

    <!-- Flash Alert -->
    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show py-2 px-3 small mb-2.5 border-0 shadow-sm" role="alert">
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

      <div class="form-field-mb mb-2.5">
        <label for="loginEmail" class="form-label small fw-semibold text-secondary mb-1" style="font-size: 0.82rem;">Work Email</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0 text-muted" style="font-size: 0.85rem;"><i class="fa-solid fa-envelope"></i></span>
          <input type="email" name="email" id="loginEmail" class="form-control border-start-0 ps-0" placeholder="staff@visatrack.com" required value="" autocomplete="username" style="font-size: 0.88rem; height: 38px;">
        </div>
      </div>

      <div class="form-field-mb mb-2.5">
        <div class="d-flex align-items-center justify-content-between mb-1">
          <label for="loginPassword" class="form-label small fw-semibold text-secondary mb-0" style="font-size: 0.82rem;">Password</label>
          <a href="/auth/forgot-password" class="small text-primary text-decoration-none fw-semibold" style="font-size: 0.74rem;">Forgot?</a>
        </div>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0 text-muted" style="font-size: 0.85rem;"><i class="fa-solid fa-lock"></i></span>
          <input type="password" name="password" id="loginPassword" class="form-control border-start-0 border-end-0 px-0" placeholder="••••••••" required value="" autocomplete="current-password" style="font-size: 0.88rem; height: 38px;">
          <button type="button" class="input-group-text bg-light border-start-0 text-muted" id="togglePasswordBtn" aria-label="Toggle password visibility">
            <i class="fa-solid fa-eye" id="togglePasswordIcon" style="font-size: 0.8rem;"></i>
          </button>
        </div>
      </div>

      <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
          <label class="form-check-label small text-muted user-select-none" for="rememberMe" style="font-size: 0.78rem;">
            Remember session
          </label>
        </div>
      </div>

      <!-- Quick Demo Login Credentials (Collapsible to keep mobile screen short) -->
      <details class="p-2 mb-2.5 bg-light rounded-3 border" style="font-size: 0.76rem;">
        <summary class="fw-bold text-secondary user-select-none" style="cursor: pointer; font-size: 0.74rem;">
          <i class="fa-solid fa-key me-1 text-primary"></i> 1-Click Demo Accounts (Tap to view)
        </summary>
        <div class="mt-2 pt-1 border-top d-flex flex-wrap gap-1">
          <button type="button" class="btn btn-outline-secondary btn-sm py-0.5 px-2 fw-semibold" style="font-size: 0.72rem;" onclick="fillCreds('manager@visatrack.com', 'password')">
            <i class="fa-solid fa-user-tie me-1"></i> Manager
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm py-0.5 px-2 fw-semibold" style="font-size: 0.72rem;" onclick="fillCreds('staff@visatrack.com', 'password')">
            <i class="fa-solid fa-user me-1"></i> Staff
          </button>
          <button type="button" class="btn btn-outline-info btn-sm py-0.5 px-2 fw-semibold" style="font-size: 0.72rem;" onclick="fillCreds('agent@skylinetravel.com', 'password')">
            <i class="fa-solid fa-handshake me-1"></i> Agent
          </button>
        </div>
      </details>

      <button type="submit" class="btn btn-primary w-100 fw-semibold shadow-sm rounded-2 btn-login-submit" id="submitBtn" style="height: 40px; font-size: 0.9rem;">
        <span class="spinner-border spinner-border-sm me-2 d-none" id="submitSpinner" role="status" aria-hidden="true"></span>
        <i class="fa-solid fa-right-to-bracket me-1.5" id="submitIcon"></i> Sign In to Operations
      </button>
    </form>

    <!-- Portal Switcher Links -->
    <div class="d-flex justify-content-center align-items-center flex-wrap gap-2 mt-2.5 pt-2 border-top small text-muted portal-switcher-wrap" style="font-size: 0.76rem;">
      <a href="/admin/login" class="text-decoration-none fw-semibold text-danger"><i class="fa-solid fa-shield-halved me-1"></i>Super Admin</a>
      <span>&bull;</span>
      <a href="/portal/login" class="text-decoration-none fw-semibold text-info"><i class="fa-solid fa-plane-departure me-1"></i>Applicant Portal</a>
      <span>&bull;</span>
      <a href="/agent/login" class="text-decoration-none fw-semibold text-primary"><i class="fa-solid fa-handshake me-1"></i>Agent</a>
      <span>&bull;</span>
      <a href="/supplier/login" class="text-decoration-none fw-semibold text-secondary"><i class="fa-solid fa-truck-fast me-1"></i>Supplier</a>
    </div>
  </div>
  
  <div class="text-center text-muted small mt-2 footer-copy-text" style="font-size: 0.72rem;">
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
