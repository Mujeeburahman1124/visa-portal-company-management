<?php
$pageTitle = 'Activation Link Expired — MS TRAVEL HUB';
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
    
    <!-- Bento Card -->
    <div class="auth-bento-card text-center">
      
      <!-- Brand & Logo -->
      <div class="auth-logo-wrap">
        <a href="/" class="auth-logo-badge text-decoration-none" title="MS Travel Hub">
          <img src="/assets/images/logo.png" alt="MS Travel Hub Logo" class="auth-logo-img">
        </a>
        <div class="auth-brand-name">MS <span class="brand-gem">TRAVEL HUB</span></div>
        <div class="auth-brand-subtitle">Portal Account Activation</div>
      </div>

      <!-- Warning Icon & Title -->
      <div class="mb-3">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle p-3" style="width: 58px; height: 58px; background: #FFF1F3; color: var(--auth-primary);">
          <i class="fa-solid fa-triangle-exclamation fs-3"></i>
        </div>
      </div>

      <h5 class="auth-heading-title mb-2">Activation Link Expired</h5>
      <p class="auth-heading-desc mb-4" style="line-height: 1.5;">
        This account activation link is invalid, already used, or has exceeded its 48-hour validity period. Please contact administration or request a new invitation.
      </p>

      <div class="d-flex flex-column gap-2">
        <a href="/portal/login" class="auth-btn-primary">
          <i class="fa-solid fa-right-to-bracket"></i> Go to Portal Login
        </a>
        <a href="/agent/login" class="btn btn-light rounded-3 py-2 fw-semibold text-secondary" style="border: 1px solid #E2E8F0; font-size: 0.9rem;">
          <i class="fa-solid fa-handshake me-1"></i> Agent Partner Login
        </a>
      </div>

    </div>

    <!-- Security Compliance Footer -->
    <div class="auth-footer-note">
      &copy; <?= date('Y') ?> <span>MS TRAVEL HUB</span> &bull; Global Visa Management Platform
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
