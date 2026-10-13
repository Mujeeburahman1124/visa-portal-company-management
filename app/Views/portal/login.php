<?php
$pageTitle = 'Applicant Portal Sign In — MS TRAVEL HUB';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Enterprise SEO & Open Graph Engine -->
  <?= \App\Services\SeoService::renderTags([
      'title' => 'Applicant Portal Sign In — MS TRAVEL HUB',
      'description' => 'Sign in to your MS Travel Hub applicant portal. Upload documents, monitor real-time consular milestones, make payments, and manage visa appointments.',
      'canonical' => \App\Services\SeoService::getBaseUrl() . '/portal/login',
      'robots' => 'index, follow'
  ]) ?>

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
        <div class="auth-brand-subtitle">Global Visa Management &bull; Applicant Services</div>
        <div>
          <span class="auth-portal-badge">
            <i class="fa-solid fa-plane-departure"></i> Applicant Self-Service Portal
          </span>
        </div>
      </div>

      <!-- Heading -->
      <div class="auth-heading-wrap">
        <h5 class="auth-heading-title">Applicant Sign In</h5>
        <p class="auth-heading-desc">Access real-time application status, upload documents &amp; pay fees</p>
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
      <form action="/portal/login" method="POST" id="portalLoginForm" novalidate autocomplete="on">
        <?= csrf_field() ?>

        <!-- Email or Customer Code Field -->
        <div class="auth-form-group">
          <label for="custLoginEmail" class="auth-form-label">Email or Customer ID</label>
          <div class="auth-input-wrap">
            <span class="auth-input-icon">
              <i class="fa-solid fa-envelope"></i>
            </span>
            <input 
              type="text" 
              name="email" 
              id="custLoginEmail" 
              class="auth-input-field" 
              placeholder="your.email@domain.com" 
              required 
              autocomplete="username" 
              autofocus
            >
          </div>
        </div>

        <!-- Password Field -->
        <div class="auth-form-group">
          <label for="custLoginPass" class="auth-form-label">Password</label>
          <div class="auth-input-wrap">
            <span class="auth-input-icon">
              <i class="fa-solid fa-lock"></i>
            </span>
            <input 
              type="password" 
              name="password" 
              id="custLoginPass" 
              class="auth-input-field" 
              placeholder="••••••••" 
              required 
              autocomplete="current-password"
            >
            <button type="button" class="auth-pwd-toggle" id="toggleCustPass" aria-label="Toggle password visibility">
              <i class="fa-solid fa-eye" id="toggleCustIcon"></i>
            </button>
          </div>
        </div>

        <!-- Options Row -->
        <div class="auth-options-row">
          <label class="auth-checkbox-wrap" for="rememberCust">
            <input type="checkbox" name="remember" id="rememberCust">
            <span>Remember login</span>
          </label>
          <a href="/portal/forgot-password" class="auth-link">Forgot password?</a>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="auth-btn-primary" id="btnCustSubmit">
          <span class="spinner-border spinner-border-sm d-none" id="custSubmitSpinner" role="status" aria-hidden="true"></span>
          <i class="fa-solid fa-right-to-bracket" id="custSubmitIcon"></i>
          <span>Sign In to Applicant Portal</span>
        </button>
      </form>

    </div>

    <!-- Security Compliance Footer -->
    <div class="auth-footer-note">
      &copy; <?= date('Y') ?> <span>MS TRAVEL HUB</span> &bull; Applicant Self-Service Portal<br>
      Confidential Personal Data Protection &bull; GDPR &amp; SSL Compliant
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Password Reveal Toggle
    document.getElementById('toggleCustPass')?.addEventListener('click', function() {
      const pwd = document.getElementById('custLoginPass');
      const icon = document.getElementById('toggleCustIcon');
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
    document.getElementById('portalLoginForm')?.addEventListener('submit', function() {
      const btn = document.getElementById('btnCustSubmit');
      const spinner = document.getElementById('custSubmitSpinner');
      const icon = document.getElementById('custSubmitIcon');

      if (btn && spinner && icon) {
        btn.disabled = true;
        spinner.classList.remove('d-none');
        icon.classList.add('d-none');
      }
    });
  </script>
</body>
</html>
