<?php
$pageTitle = 'Activate Portal Account — MS TRAVEL HUB';
$flash = get_flash();
$portalLabel = $portalTitle ?? 'Client Portal';
$backLoginUrl = str_contains(strtolower($portalLabel), 'agent') ? '/agent/login' : (str_contains(strtolower($portalLabel), 'supplier') ? '/supplier/login' : '/portal/login');
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
    
    <!-- Bento Activation Card -->
    <div class="auth-bento-card">
      
      <!-- Brand & Logo -->
      <div class="auth-logo-wrap">
        <a href="/" class="auth-logo-badge text-decoration-none" title="MS Travel Hub">
          <img src="/assets/images/logo.png" alt="MS Travel Hub Logo" class="auth-logo-img">
        </a>
        <div class="auth-brand-name">MS <span class="brand-gem">TRAVEL HUB</span></div>
        <div class="auth-brand-subtitle"><?= e($portalLabel) ?> &bull; Account Activation</div>
        <div>
          <span class="auth-portal-badge">
            <i class="fa-solid fa-shield-check"></i> Verified Account Setup
          </span>
        </div>
      </div>

      <!-- Heading -->
      <div class="auth-heading-wrap">
        <h5 class="auth-heading-title">Set Your Password</h5>
        <p class="auth-heading-desc">
          Hello <strong><?= e($activation['full_name'] ?? 'Partner') ?></strong>, create a secure password to activate your portal account.
        </p>
      </div>

      <!-- Flash Message -->
      <?php if ($flash): ?>
        <?php 
          $alertType = $flash['type'] === 'danger' ? 'danger' : 'success';
          $alertIcon = $flash['type'] === 'danger' ? 'fa-circle-exclamation' : 'fa-circle-check';
        ?>
        <div class="alert alert-<?= e($alertType) ?> alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 small mb-3 border-0 shadow-sm rounded-3" role="alert">
          <i class="fa-solid <?= $alertIcon ?>"></i>
          <div class="flex-grow-1"><?= e($flash['message']) ?></div>
          <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Activation Form -->
      <form action="<?= e($actionUrl ?? '/portal/activate') ?>" method="POST" id="activationForm" novalidate autocomplete="on">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">

        <!-- Registered Email (Readonly) -->
        <div class="auth-form-group">
          <label class="auth-form-label">Registered Email</label>
          <div class="auth-input-wrap" style="background: #F1F5F9; border-color: #E2E8F0;">
            <span class="auth-input-icon">
              <i class="fa-solid fa-envelope text-muted"></i>
            </span>
            <input 
              type="text" 
              class="auth-input-field text-muted fw-semibold" 
              value="<?= e($activation['email'] ?? '') ?>" 
              readonly 
              tabindex="-1"
            >
          </div>
        </div>

        <!-- New Password Field -->
        <div class="auth-form-group">
          <label for="password" class="auth-form-label">New Password <span class="text-danger">*</span></label>
          <div class="auth-input-wrap">
            <span class="auth-input-icon">
              <i class="fa-solid fa-lock"></i>
            </span>
            <input 
              type="password" 
              name="password" 
              id="password" 
              class="auth-input-field" 
              placeholder="Minimum 6 characters" 
              required 
              minlength="6"
              autocomplete="new-password"
              autofocus
            >
            <button type="button" class="auth-pwd-toggle" onclick="togglePass('password')" aria-label="Toggle password visibility">
              <i class="fa-solid fa-eye" id="passwordIcon"></i>
            </button>
          </div>
        </div>

        <!-- Confirm Password Field -->
        <div class="auth-form-group">
          <label for="confirm_password" class="auth-form-label">Confirm Password <span class="text-danger">*</span></label>
          <div class="auth-input-wrap">
            <span class="auth-input-icon">
              <i class="fa-solid fa-lock-check"></i>
            </span>
            <input 
              type="password" 
              name="confirm_password" 
              id="confirm_password" 
              class="auth-input-field" 
              placeholder="Confirm your password" 
              required 
              minlength="6"
              autocomplete="new-password"
            >
            <button type="button" class="auth-pwd-toggle" onclick="togglePass('confirm_password')" aria-label="Toggle confirm password visibility">
              <i class="fa-solid fa-eye" id="confirm_passwordIcon"></i>
            </button>
          </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="auth-btn-primary mt-4" id="btnActivateSubmit">
          <span class="spinner-border spinner-border-sm d-none" id="activateSpinner" role="status" aria-hidden="true"></span>
          <i class="fa-solid fa-circle-check" id="activateIcon"></i>
          <span>Activate &amp; Log In</span>
        </button>
      </form>

      <!-- Back to Login -->
      <div class="text-center mt-3 pt-2">
        <a href="<?= e($backLoginUrl) ?>" class="auth-link small d-inline-flex align-items-center gap-1.5">
          <i class="fa-solid fa-arrow-left"></i> Back to Login
        </a>
      </div>

    </div>

    <!-- Security Compliance Footer -->
    <div class="auth-footer-note">
      &copy; <?= date('Y') ?> <span>MS TRAVEL HUB</span> &bull; Global Visa Management Platform<br>
      Confidential Activation Gateway &bull; Encrypted Token Protocol
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function togglePass(fieldId) {
      const field = document.getElementById(fieldId);
      const icon = document.getElementById(fieldId + 'Icon');
      if (!field || !icon) return;
      if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    }

    document.getElementById('activationForm')?.addEventListener('submit', function() {
      const btn = document.getElementById('btnActivateSubmit');
      const spinner = document.getElementById('activateSpinner');
      const icon = document.getElementById('activateIcon');
      if (btn && spinner && icon) {
        btn.disabled = true;
        spinner.classList.remove('d-none');
        icon.classList.add('d-none');
      }
    });
  </script>
</body>
</html>
