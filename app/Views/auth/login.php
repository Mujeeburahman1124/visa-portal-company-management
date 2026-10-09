<?php
$pageTitle = 'Staff Sign In — MS TRAVEL HUB';
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

  <!-- Premium Typography -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Core Frameworks -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

  <style>
    :root {
      --primary-color: #2563eb;
      --primary-hover: #1d4ed8;
      --navy-dark: #0a1324;
      --navy-deep: #060a14;
      --accent-cyan: #06b6d4;
      --accent-emerald: #10b981;
      --text-main: #0f172a;
      --text-muted: #64748b;
      --border-color: #e2e8f0;
      --bg-input: #f8fafc;
    }

    * {
      box-sizing: border-box;
    }

    body.login-page-body {
      margin: 0;
      padding: 0;
      min-height: 100vh;
      font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background: radial-gradient(circle at 50% 10%, #112240 0%, #0a1325 50%, #050a14 100%);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow-x: hidden;
      color: var(--text-main);
    }

    /* Ambient Lighting Background Accents */
    .ambient-glow-1 {
      position: absolute;
      top: -120px;
      left: 15%;
      width: 500px;
      height: 500px;
      background: radial-gradient(circle, rgba(37, 99, 235, 0.16) 0%, rgba(37, 99, 235, 0) 70%);
      pointer-events: none;
      filter: blur(40px);
      z-index: 0;
    }

    .ambient-glow-2 {
      position: absolute;
      bottom: -140px;
      right: 15%;
      width: 520px;
      height: 520px;
      background: radial-gradient(circle, rgba(16, 185, 129, 0.12) 0%, rgba(6, 182, 212, 0) 70%);
      pointer-events: none;
      filter: blur(50px);
      z-index: 0;
    }

    .ambient-grid {
      position: absolute;
      inset: 0;
      background-image: 
        linear-gradient(rgba(255, 255, 255, 0.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.025) 1px, transparent 1px);
      background-size: 40px 40px;
      pointer-events: none;
      z-index: 0;
    }

    /* Container Wrap */
    .login-container-wrap {
      width: 100%;
      max-width: 460px;
      padding: 1.5rem 1rem;
      position: relative;
      z-index: 1;
    }

    /* Executive Login Card */
    .login-card-pro {
      background: #ffffff;
      border: 1px solid rgba(226, 232, 240, 0.9);
      border-radius: 20px;
      padding: 2.25rem 2.25rem;
      box-shadow: 
        0 25px 60px -15px rgba(2, 6, 23, 0.6),
        0 0 0 1px rgba(255, 255, 255, 0.4) inset,
        0 8px 24px -6px rgba(37, 99, 235, 0.12);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    /* Brand Header */
    .brand-header-section {
      text-align: center;
      margin-bottom: 1.5rem;
    }

    .brand-logo-img {
      max-height: 64px;
      width: auto;
      filter: drop-shadow(0 4px 10px rgba(37, 99, 235, 0.25));
      margin-bottom: 0.75rem;
      transition: transform 0.2s ease;
    }

    .brand-logo-img:hover {
      transform: scale(1.03);
    }

    .brand-title-main {
      font-family: 'Outfit', 'Plus Jakarta Sans', sans-serif;
      font-size: 1.45rem;
      font-weight: 800;
      letter-spacing: -0.02em;
      color: #0f172a;
      margin-bottom: 0.2rem;
    }

    .brand-tagline-text {
      font-size: 0.75rem;
      font-weight: 600;
      color: #64748b;
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }

    /* Security Trust Badge */
    .security-status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 12px;
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
      color: #166534;
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      border-radius: 20px;
      margin-top: 0.75rem;
      margin-bottom: 0.25rem;
    }

    .security-status-badge i {
      color: #16a34a;
      font-size: 0.75rem;
    }

    /* Section Greeting */
    .login-heading-section {
      text-align: center;
      margin-bottom: 1.5rem;
    }

    .login-heading-title {
      font-size: 1.15rem;
      font-weight: 700;
      color: #0f172a;
      margin-bottom: 0.25rem;
    }

    .login-heading-sub {
      font-size: 0.82rem;
      color: #64748b;
      margin-bottom: 0;
    }

    /* Form Fields */
    .form-group-pro {
      margin-bottom: 1.15rem;
    }

    .form-label-pro {
      display: block;
      font-size: 0.82rem;
      font-weight: 600;
      color: #334155;
      margin-bottom: 0.4rem;
    }

    .input-group-pro {
      position: relative;
      display: flex;
      align-items: center;
      background: #f8fafc;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      overflow: hidden;
    }

    .input-group-pro:hover {
      border-color: #cbd5e1;
      background: #ffffff;
    }

    .input-group-pro:focus-within {
      border-color: #2563eb;
      background: #ffffff;
      box-shadow: 0 0 0 3.5px rgba(37, 99, 235, 0.12);
    }

    .input-icon-pro {
      padding: 0 0.85rem;
      color: #94a3b8;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      justify-content: center;
      pointer-events: none;
      transition: color 0.2s ease;
    }

    .input-group-pro:focus-within .input-icon-pro {
      color: #2563eb;
    }

    .form-control-pro {
      flex: 1;
      border: none;
      outline: none;
      background: transparent;
      padding: 0.65rem 0.5rem 0.65rem 0;
      font-family: inherit;
      font-size: 0.9rem;
      font-weight: 500;
      color: #0f172a;
      height: 44px;
    }

    .form-control-pro::placeholder {
      color: #94a3b8;
      font-weight: 400;
    }

    .password-toggle-btn {
      background: transparent;
      border: none;
      padding: 0 0.85rem;
      color: #94a3b8;
      cursor: pointer;
      font-size: 0.88rem;
      height: 44px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.15s ease;
    }

    .password-toggle-btn:hover {
      color: #2563eb;
    }

    /* Options Row */
    .form-options-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1.35rem;
      font-size: 0.82rem;
    }

    .custom-checkbox-wrap {
      display: flex;
      align-items: center;
      gap: 7px;
      cursor: pointer;
      user-select: none;
      color: #475569;
      font-weight: 500;
    }

    .custom-checkbox-wrap input[type="checkbox"] {
      cursor: pointer;
      width: 15px;
      height: 15px;
      border-radius: 4px;
      accent-color: #2563eb;
    }

    .forgot-pass-link {
      color: #2563eb;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.15s ease;
    }

    .forgot-pass-link:hover {
      color: #1d4ed8;
      text-decoration: underline;
    }

    /* Submit Button */
    .btn-submit-pro {
      width: 100%;
      height: 46px;
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
      color: #ffffff;
      border: none;
      border-radius: 10px;
      font-family: inherit;
      font-size: 0.95rem;
      font-weight: 700;
      letter-spacing: 0.01em;
      cursor: pointer;
      box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-submit-pro:hover {
      background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
      transform: translateY(-1.5px);
      box-shadow: 0 8px 20px rgba(37, 99, 235, 0.45);
    }

    .btn-submit-pro:active {
      transform: translateY(0);
      box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
    }

    .btn-submit-pro:disabled {
      opacity: 0.75;
      cursor: not-allowed;
      transform: none;
    }

    /* Security Notice Box at Bottom of Card */
    .security-guarantee-box {
      margin-top: 1.5rem;
      padding-top: 1.15rem;
      border-top: 1px solid #f1f5f9;
      text-align: center;
    }

    .security-guarantee-line {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      font-size: 0.76rem;
      font-weight: 600;
      color: #475569;
    }

    .security-guarantee-sub {
      font-size: 0.7rem;
      color: #94a3b8;
      margin-top: 0.25rem;
    }

    /* Page Footer */
    .auth-footer-pro {
      text-align: center;
      margin-top: 1.5rem;
      font-size: 0.73rem;
      color: rgba(255, 255, 255, 0.55);
      letter-spacing: 0.02em;
    }

    .auth-footer-pro span {
      color: rgba(255, 255, 255, 0.8);
      font-weight: 600;
    }

    /* Alert Styling */
    .alert-pro {
      border-radius: 10px;
      border: none;
      font-size: 0.84rem;
      padding: 0.7rem 0.9rem;
      margin-bottom: 1.25rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .alert-pro-danger {
      background: #fef2f2;
      color: #991b1b;
      border: 1px solid #fecaca;
    }

    .alert-pro-success {
      background: #f0fdf4;
      color: #166534;
      border: 1px solid #bbf7d0;
    }

    .alert-pro-info {
      background: #eff6ff;
      color: #1e40af;
      border: 1px solid #bfdbfe;
    }

    @media (max-width: 480px) {
      .login-card-pro {
        padding: 1.75rem 1.25rem;
        border-radius: 16px;
      }
      .brand-title-main {
        font-size: 1.3rem;
      }
      .login-container-wrap {
        padding: 1rem 0.75rem;
      }
    }
  </style>
</head>
<body class="login-page-body">

  <!-- Ambient Light Effects -->
  <div class="ambient-glow-1"></div>
  <div class="ambient-glow-2"></div>
  <div class="ambient-grid"></div>

  <div class="login-container-wrap">
    
    <!-- Executive Login Card -->
    <div class="login-card-pro">
      
      <!-- Brand & Security Header -->
      <div class="brand-header-section">
        <img src="/assets/images/logo.png" alt="MS Travel Hub Logo" class="brand-logo-img">
        <div class="brand-title-main">MS TRAVEL HUB</div>
        <div class="brand-tagline-text">Global Visa Management Portal</div>
        
        <div>
          <span class="security-status-badge">
            <i class="fa-solid fa-shield-halved"></i> 256-Bit SSL Encrypted Console
          </span>
        </div>
      </div>

      <!-- Heading -->
      <div class="login-heading-section">
        <h5 class="login-heading-title">Staff &amp; Operations Sign In</h5>
        <p class="login-heading-sub">Authenticate with your corporate credentials</p>
      </div>

      <!-- Flash Message -->
      <?php if ($flash): ?>
        <?php 
          $alertType = $flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info');
          $alertIcon = $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info');
        ?>
        <div class="alert-pro alert-pro-<?= e($alertType) ?>" role="alert">
          <i class="fa-solid <?= $alertIcon ?>"></i>
          <div class="flex-grow-1"><?= e($flash['message']) ?></div>
        </div>
      <?php endif; ?>

      <!-- Authentication Form -->
      <form action="/auth/login" method="POST" id="proLoginForm" novalidate autocomplete="on">
        <?= csrf_field() ?>

        <!-- Email Field -->
        <div class="form-group-pro">
          <label for="userEmail" class="form-label-pro">Work Email</label>
          <div class="input-group-pro">
            <span class="input-icon-pro">
              <i class="fa-solid fa-envelope"></i>
            </span>
            <input 
              type="email" 
              name="email" 
              id="userEmail" 
              class="form-control-pro" 
              placeholder="name@company.com" 
              required 
              autocomplete="username" 
              autofocus
            >
          </div>
        </div>

        <!-- Password Field -->
        <div class="form-group-pro">
          <label for="userPassword" class="form-label-pro">Password</label>
          <div class="input-group-pro">
            <span class="input-icon-pro">
              <i class="fa-solid fa-lock"></i>
            </span>
            <input 
              type="password" 
              name="password" 
              id="userPassword" 
              class="form-control-pro" 
              placeholder="••••••••" 
              required 
              autocomplete="current-password"
            >
            <button type="button" class="password-toggle-btn" id="togglePasswordBtn" aria-label="Toggle password visibility">
              <i class="fa-solid fa-eye" id="togglePasswordIcon"></i>
            </button>
          </div>
        </div>

        <!-- Options Row -->
        <div class="form-options-row">
          <label class="custom-checkbox-wrap" for="rememberWorkstation">
            <input type="checkbox" name="remember" id="rememberWorkstation">
            <span>Remember workstation</span>
          </label>
          <a href="/auth/forgot-password" class="forgot-pass-link">Forgot password?</a>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn-submit-pro" id="btnProSubmit">
          <span class="spinner-border spinner-border-sm d-none" id="btnSubmitSpinner" role="status" aria-hidden="true"></span>
          <i class="fa-solid fa-arrow-right-to-bracket" id="btnSubmitIcon"></i>
          <span id="btnSubmitText">Sign In to Operations</span>
        </button>
      </form>

      <!-- Bank-Grade Security Assurance Box (No Switcher Buttons) -->
      <div class="security-guarantee-box">
        <div class="security-guarantee-line">
          <i class="fa-solid fa-shield-check text-success"></i>
          <span>Protected by Enterprise TLS 1.3 &amp; Rate-Limiting Defense</span>
        </div>
        <div class="security-guarantee-sub">
          Authorized personnel only. Sessions are monitored and audit-logged.
        </div>
      </div>

    </div>

    <!-- Security Compliance Footer -->
    <div class="auth-footer-pro">
      &copy; <?= date('Y') ?> <span>MS TRAVEL HUB</span> &bull; Enterprise Visa Operations Platform<br>
      ISO 27001 &bull; SOC 2 Type II Compliant Architecture
    </div>

  </div>

  <script>
    // Password Reveal Toggle
    document.getElementById('togglePasswordBtn')?.addEventListener('click', function() {
      const pwd = document.getElementById('userPassword');
      const icon = document.getElementById('togglePasswordIcon');
      if (!pwd || !icon) return;

      if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        pwd.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    });

    // Submit Loading Feedback
    document.getElementById('proLoginForm')?.addEventListener('submit', function() {
      const btn = document.getElementById('btnProSubmit');
      const spinner = document.getElementById('btnSubmitSpinner');
      const icon = document.getElementById('btnSubmitIcon');
      const text = document.getElementById('btnSubmitText');

      if (btn && spinner && icon && text) {
        btn.disabled = true;
        spinner.classList.remove('d-none');
        icon.classList.add('d-none');
        text.textContent = 'Verifying Credentials...';
      }
    });
  </script>
</body>
</html>
