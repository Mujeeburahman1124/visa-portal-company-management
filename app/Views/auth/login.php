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
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Frameworks & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

  <!-- Bento Auth Styling -->
  <link rel="stylesheet" href="/assets/css/pages/auth-bento.css?v=<?= time() ?>">
</head>
<body class="auth-bento-body">

  <!-- Atmospheric Glow & Ambient Grid -->
  <div class="auth-ambient-glow-1"></div>
  <div class="auth-ambient-glow-2"></div>
  <div class="auth-ambient-grid"></div>

  <div class="auth-container-wrap">
    
    <!-- Bento Login Card -->
    <div class="auth-bento-card">
      
      <!-- Brand & Logo -->
      <div class="auth-logo-wrap">
        <a href="/" class="auth-logo-badge text-decoration-none" title="MS Travel Hub">
          <img src="/assets/images/logo.png" alt="MS Travel Hub Logo" class="auth-logo-img">
        </a>
        <div class="auth-brand-name">MS <span class="brand-gem">TRAVEL HUB</span></div>
        <div class="auth-brand-subtitle">Enterprise Visa Operations Platform</div>
        <div>
          <span class="auth-portal-badge">
            <i class="fa-solid fa-user-shield"></i> Operations &amp; Staff Console
          </span>
        </div>
      </div>

      <!-- Heading -->
      <div class="auth-heading-wrap">
        <h5 class="auth-heading-title">Staff Sign In</h5>
        <p class="auth-heading-desc">Authenticate with your corporate credentials</p>
      </div>

      <!-- Flash Message -->
      <?php if ($flash): ?>
        <?php 
          $alertType = $flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info');
          $alertIcon = $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info');
        ?>
        <div class="alert alert-<?= e($alertType) ?> alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 small mb-3 border-0 shadow-sm rounded-3" role="alert">
          <i class="fa-solid <?= $alertIcon ?>"></i>
          <div class="flex-grow-1"><?= e($flash['message']) ?></div>
          <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Authentication Form -->
      <form action="/auth/login" method="POST" id="proLoginForm" novalidate autocomplete="on">
        <?= csrf_field() ?>

        <!-- Email Field -->
        <div class="auth-form-group">
          <label for="userEmail" class="auth-form-label">Corporate Email</label>
          <div class="auth-input-wrap">
            <span class="auth-input-icon">
              <i class="fa-solid fa-envelope"></i>
            </span>
            <input 
              type="email" 
              name="email" 
              id="userEmail" 
              class="auth-input-field" 
              placeholder="staff@company.com" 
              required 
              autocomplete="username" 
              autofocus
            >
          </div>
        </div>

        <!-- Password Field -->
        <div class="auth-form-group">
          <label for="userPassword" class="auth-form-label">Password</label>
          <div class="auth-input-wrap">
            <span class="auth-input-icon">
              <i class="fa-solid fa-lock"></i>
            </span>
            <input 
              type="password" 
              name="password" 
              id="userPassword" 
              class="auth-input-field" 
              placeholder="••••••••" 
              required 
              autocomplete="current-password"
            >
            <button type="button" class="auth-pwd-toggle" id="togglePasswordBtn" aria-label="Toggle password visibility">
              <i class="fa-solid fa-eye" id="togglePasswordIcon"></i>
            </button>
          </div>
        </div>

        <!-- Options Row -->
        <div class="auth-options-row">
          <label class="auth-checkbox-wrap" for="rememberWorkstation">
            <input type="checkbox" name="remember" id="rememberWorkstation">
            <span>Remember workstation</span>
          </label>
          <a href="/auth/forgot-password" class="auth-link">Forgot password?</a>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="auth-btn-primary" id="btnProSubmit">
          <span class="spinner-border spinner-border-sm d-none" id="btnSubmitSpinner" role="status" aria-hidden="true"></span>
          <i class="fa-solid fa-arrow-right-to-bracket" id="btnSubmitIcon"></i>
          <span id="btnSubmitText">Sign In to Operations</span>
        </button>
      </form>

      <!-- Security Box -->
      <div class="auth-security-box">
        <div class="auth-security-line">
          <i class="fa-solid fa-shield-check text-success"></i>
          <span>Enterprise TLS 1.3 &amp; Rate-Limiting Active</span>
        </div>
        <div>Sessions are monitored, encrypted, and audit-logged.</div>
      </div>

      <!-- Quick Switch to Portals -->
      <div class="auth-switch-links">
        <span>Other Portals:</span>
        <a href="/agent/login" class="auth-switch-pill"><i class="fa-solid fa-handshake"></i> Agent Portal</a>
        <a href="/portal/login" class="auth-switch-pill"><i class="fa-solid fa-plane-departure"></i> Applicant Portal</a>
        <a href="/supplier/login" class="auth-switch-pill"><i class="fa-solid fa-building"></i> Supplier Portal</a>
      </div>

    </div>

    <!-- Security Compliance Footer -->
    <div class="auth-footer-note">
      &copy; <?= date('Y') ?> <span>MS TRAVEL HUB</span> &bull; Global Visa Management<br>
      ISO 27001 &bull; SOC 2 Type II Certified Architecture
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
