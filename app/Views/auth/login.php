<?php
$pageTitle = 'Staff Sign In — MS Travel Hub';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
  <meta name="theme-color" content="#2563eb">
  <meta name="description" content="MS Travel Hub — Global Visa Management Portal. Staff & Operations Sign In.">
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" type="image/png" href="/assets/images/logo.png">
  <link rel="apple-touch-icon" href="/assets/images/logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css?v=7.0.0">
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
    @media (max-width: 480px) {
      .login-card { padding: 1.75rem 1.25rem; border-radius: 16px; }
      .login-card .logo-wrap img { height: 56px; }
      .login-card .brand-name { font-size: 1.25rem; }
    }
  </style>
</head>
<body>

<div class="auth-login-bg">

  <div style="position:relative;z-index:2;width:100%;display:flex;flex-direction:column;align-items:center;">
    <div class="login-card">
      <!-- Brand -->
      <div class="logo-wrap">
        <img src="/assets/images/logo.png" alt="MS Travel Hub Logo">
        <div class="brand-name">MS <span class="grad">TRAVEL HUB</span></div>
        <div class="brand-tagline">Global Visa Management Portal</div>
      </div>

      <!-- Title -->
      <div class="login-section-title">Staff & Operations Sign In</div>
      <div class="login-section-sub">Sign in to access your visa operations pipeline</div>

      <!-- Flash Alert -->
      <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show py-2 px-3 mb-3 border-0 shadow-sm" role="alert" style="font-size:0.85rem;border-radius:10px;">
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

        <!-- Email -->
        <div class="login-input-wrap">
          <label for="loginEmail">Work Email</label>
          <div class="login-input-group">
            <span class="icon"><i class="fa-solid fa-envelope"></i></span>
            <input type="email" name="email" id="loginEmail" placeholder="admin@visatrack.com"
              required value="admin@visatrack.com" autocomplete="username">
          </div>
        </div>

        <!-- Password -->
        <div class="login-input-wrap">
          <div class="d-flex align-items-center justify-content-between">
            <label for="loginPassword" style="margin-bottom:0.35rem;">Password</label>
            <a href="/auth/forgot-password" class="forgot-link">Forgot password?</a>
          </div>
          <div class="login-input-group">
            <span class="icon"><i class="fa-solid fa-lock"></i></span>
            <input type="password" name="password" id="loginPassword" placeholder="••••••••"
              required value="admin123" autocomplete="current-password">
            <button type="button" class="toggle-btn" id="togglePasswordBtn" aria-label="Toggle password visibility">
              <i class="fa-solid fa-eye" id="togglePasswordIcon" style="font-size:0.85rem;"></i>
            </button>
          </div>
        </div>

        <!-- Remember Me -->
        <div class="remember-row">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
            <label class="form-check-label" for="rememberMe">Remember this session</label>
          </div>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-login" id="submitBtn">
          <span class="spinner-border spinner-border-sm me-2 d-none" id="submitSpinner" role="status" aria-hidden="true"></span>
          <i class="fa-solid fa-right-to-bracket me-2" id="submitIcon"></i>
          Sign In to Operations
        </button>
      </form>

      <!-- Quick Role Fill -->
      <div class="mt-3 pt-3" style="border-top:1px solid #e8f0fe;">
        <div class="text-center mb-2">
          <span class="text-muted fw-bold" style="font-size:0.65rem;letter-spacing:0.06em;text-transform:uppercase;">
            <i class="fa-solid fa-bolt text-warning me-1"></i>Fast Role Fill
          </span>
        </div>
        <div class="d-flex flex-wrap justify-content-center gap-1">
          <button type="button" class="quick-fill-btn" onclick="fillCreds('admin@visatrack.com', 'admin123')">Admin</button>
          <button type="button" class="quick-fill-btn" onclick="fillCreds('manager@visatrack.com', 'password123')">Manager</button>
          <button type="button" class="quick-fill-btn" onclick="fillCreds('officer@visatrack.com', 'password123')">Officer</button>
          <button type="button" class="quick-fill-btn" onclick="fillCreds('staff@visatrack.com', 'password123')">Staff</button>
          <button type="button" class="quick-fill-btn" onclick="fillCreds('accounts@visatrack.com', 'password123')">Accounts</button>
        </div>
      </div>

      <!-- Portal Link -->
      <div class="portal-link-row">
        Applicant? <a href="/portal/login">Customer Tracking Portal &rarr;</a>
      </div>
    </div>

    <div class="footer-copy">
      &copy; <?= date('Y') ?> MS Travel Hub &bull; Global Visa Management System
    </div>
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
    icon.classList.replace('fa-eye', 'fa-eye-slash');
  } else {
    pwdInput.type = 'password';
    icon.classList.replace('fa-eye-slash', 'fa-eye');
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
