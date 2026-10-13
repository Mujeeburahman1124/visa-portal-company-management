<?php
$pageTitle = 'Super Administrator Console — MS TRAVEL HUB';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Enterprise SEO & Security Directives -->
  <?= \App\Services\SeoService::renderTags([
      'title' => 'Master Control Console — MS TRAVEL HUB',
      'description' => 'Restricted administrative access for MS Travel Hub system administrators.',
      'canonical' => \App\Services\SeoService::getBaseUrl() . '/admin/login',
      'robots' => 'noindex, nofollow'
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
    
    <!-- Dark Bento Card (Executive Clearance) -->
    <div class="auth-bento-card auth-bento-card-dark">
      
      <!-- Brand & Logo -->
      <div class="auth-logo-wrap">
        <a href="/" class="auth-logo-badge text-decoration-none" title="MS Travel Hub">
          <img src="/assets/images/logo.png" alt="MS Travel Hub Logo" class="auth-logo-img">
        </a>
        <div class="auth-brand-name">MS <span class="brand-gem">TRAVEL HUB</span></div>
        <div class="auth-brand-subtitle">Executive Root Administration</div>
        <div>
          <span class="auth-portal-badge">
            <i class="fa-solid fa-shield-halved"></i> Super Admin Clearance Only
          </span>
        </div>
      </div>

      <!-- Heading -->
      <div class="auth-heading-wrap">
        <h5 class="auth-heading-title">Executive Sign In</h5>
        <p class="auth-heading-desc">Hardware/IP verification &amp; 2FA enabled</p>
      </div>

      <!-- Flash Message -->
      <?php if ($flash): ?>
        <?php 
          $alertType = $flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info');
          $alertIcon = $flash['type'] === 'danger' ? 'fa-triangle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info');
        ?>
        <div class="alert alert-<?= e($alertType) ?> alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 small mb-3 border-0 shadow-sm rounded-3 bg-opacity-25" role="alert">
          <i class="fa-solid <?= $alertIcon ?>"></i>
          <div class="flex-grow-1 text-white"><?= e($flash['message']) ?></div>
          <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Authentication Form -->
      <form action="/admin/login" method="POST" id="adminLoginForm" novalidate autocomplete="on">
        <?= csrf_field() ?>

        <!-- Email Field -->
        <div class="auth-form-group">
          <label for="adminEmail" class="auth-form-label">Super Admin Email</label>
          <div class="auth-input-wrap">
            <span class="auth-input-icon">
              <i class="fa-solid fa-envelope"></i>
            </span>
            <input 
              type="email" 
              name="email" 
              id="adminEmail" 
              class="auth-input-field" 
              placeholder="admin@system.com" 
              required 
              autocomplete="username" 
              autofocus
            >
          </div>
        </div>

        <!-- Password Field -->
        <div class="auth-form-group">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label for="adminPass" class="auth-form-label mb-0">Security Password</label>
            <a href="/auth/forgot-password" class="auth-link small" style="font-size: 0.75rem;">Recovery</a>
          </div>
          <div class="auth-input-wrap">
            <span class="auth-input-icon">
              <i class="fa-solid fa-lock"></i>
            </span>
            <input 
              type="password" 
              name="password" 
              id="adminPass" 
              class="auth-input-field" 
              placeholder="••••••••" 
              required 
              autocomplete="current-password"
            >
            <button type="button" class="auth-pwd-toggle" id="toggleAdminPass" aria-label="Toggle password visibility">
              <i class="fa-solid fa-eye" id="toggleAdminIcon"></i>
            </button>
          </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="auth-btn-primary mt-3" id="btnAdminSubmit">
          <span class="spinner-border spinner-border-sm d-none" id="adminSubmitSpinner" role="status" aria-hidden="true"></span>
          <i class="fa-solid fa-lock-open" id="adminSubmitIcon"></i>
          <span>Authenticate as Super Admin</span>
        </button>
      </form>

      <!-- Security Box -->
      <div class="auth-security-box">
        <div class="auth-security-line">
          <i class="fa-solid fa-lock text-danger"></i>
          <span>Restricted Executive Portal</span>
        </div>
        <div>Hardware token &bull; IP geofence verification active.</div>
      </div>

      <!-- Quick Switch to Portals -->
      <div class="auth-switch-links">
        <a href="/auth/login" class="auth-switch-pill"><i class="fa-solid fa-arrow-left"></i> Staff Portal</a>
        <a href="/portal/login" class="auth-switch-pill"><i class="fa-solid fa-plane-departure"></i> Applicant Portal</a>
      </div>

    </div>

    <!-- Security Compliance Footer -->
    <div class="auth-footer-note">
      &copy; <?= date('Y') ?> <span>MS TRAVEL HUB</span> &bull; Executive System Console<br>
      High-Security Zone &bull; All Access Attempts Logged
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Password Reveal Toggle
    document.getElementById('toggleAdminPass')?.addEventListener('click', function() {
      const pwd = document.getElementById('adminPass');
      const icon = document.getElementById('toggleAdminIcon');
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
    document.getElementById('adminLoginForm')?.addEventListener('submit', function() {
      const btn = document.getElementById('btnAdminSubmit');
      const spinner = document.getElementById('adminSubmitSpinner');
      const icon = document.getElementById('adminSubmitIcon');

      if (btn && spinner && icon) {
        btn.disabled = true;
        spinner.classList.remove('d-none');
        icon.classList.add('d-none');
      }
    });
  </script>
</body>
</html>
