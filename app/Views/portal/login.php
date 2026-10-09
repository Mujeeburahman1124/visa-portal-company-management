<?php
$pageTitle = 'Applicant Portal Sign In — VISA TRACK';
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
  <link rel="stylesheet" href="/assets/css/theme.css?v=2.3">
  <link rel="stylesheet" href="/assets/css/main.css?v=7.0.0">
  <script>
    (function(){try{var m={'ocean-royal':'aviation-sapphire','sunset-fusion':'sunset-fusion','emerald-royal':'consular-emerald','violet-aurora':'aviation-sapphire','crimson-midnight':'ms-ruby-prestige','imperial-gold':'sunset-fusion','dark-mode':'obsidian-dark'};var t=localStorage.getItem('vt_theme')||'ms-ruby-prestige';if(m[t])t=m[t];document.documentElement.setAttribute('data-theme',t);}catch(e){}})();
  </script>
  <style>
    @media (max-width: 576px) {
      .auth-card-compact { padding: 1.25rem 1rem !important; border-radius: 14px !important; }
      .container { padding: 0.75rem 0.5rem !important; }
      .brand-font { font-size: 1.2rem !important; }
      .form-control, .input-group-text, .btn { height: 38px !important; font-size: 0.88rem !important; }
      .mb-3 { margin-bottom: 0.65rem !important; }
      .mb-2.5 { margin-bottom: 0.55rem !important; }
    }
  </style>
</head>
<body class="auth-page-body">

<div class="container d-flex flex-column align-items-center justify-content-center" style="min-height: 100vh; padding: 1.5rem 1rem;">
  
  <div class="auth-card-compact">
    <!-- Brand Header -->
    <div class="text-center mb-3">
      <div class="d-inline-flex align-items-center justify-content-center text-white rounded-3 shadow-sm mb-2" style="width: 44px; height: 44px; font-size: 1.25rem; background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);">
        <i class="fa-solid fa-plane-departure"></i>
      </div>
      <h3 class="fw-bold brand-font text-dark mb-0" style="font-size: 1.45rem; letter-spacing: -0.01em;">MS TRAVEL HUB</h3>
      <div class="text-muted small" style="font-size: 0.78rem;">Global Visa Management &bull; Applicant Portal</div>
    </div>

    <div class="text-center mb-3">
      <h5 class="fw-bold text-dark mb-0" style="font-size: 1.15rem;">Applicant Portal</h5>
      <p class="text-muted small mb-0" style="font-size: 0.82rem;">Sign in to view progress &amp; upload documents</p>
    </div>

    <!-- Flash Alert -->
    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show py-2 px-3 small mb-3 border-0 shadow-sm" role="alert">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
          <span><?= e($flash['message']) ?></span>
        </div>
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form action="/portal/login" method="POST" id="portalLoginForm">
      <?= csrf_field() ?>
      
      <div class="mb-2.5">
        <label class="form-label small fw-semibold text-secondary mb-1" style="font-size: 0.85rem;">Email or Customer Code</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0 text-muted" style="font-size: 0.85rem;"><i class="fa-solid fa-envelope"></i></span>
          <input type="text" name="email" id="custLoginEmail" class="form-control border-start-0 ps-0" placeholder="your.email@domain.com" value="" required style="font-size: 0.9rem; height: 40px;">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label small fw-semibold text-secondary mb-1" style="font-size: 0.85rem;">Password</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0 text-muted" style="font-size: 0.85rem;"><i class="fa-solid fa-lock"></i></span>
          <input type="password" name="password" id="custLoginPass" class="form-control border-start-0 px-0" placeholder="••••••••" value="" required style="font-size: 0.9rem; height: 40px;">
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100 fw-semibold shadow-sm rounded-2 mb-2.5" style="height: 42px; font-size: 0.92rem;">
        <i class="fa-solid fa-right-to-bracket me-1.5"></i> Sign In to Applicant Portal
      </button>
    </form>

    <!-- Forgot Password Link -->
    <div class="text-center mb-2" style="font-size:0.8rem;">
      <a href="/portal/forgot-password" class="text-primary text-decoration-none"><i class="fa-solid fa-key me-1"></i>Forgot your password?</a>
    </div>

    <!-- Quick Tracking Option -->
    <div class="p-2.5 bg-light rounded border text-center mb-2.5">
      <div class="small fw-semibold text-dark mb-0.5" style="font-size: 0.78rem;">Quick Application Lookup</div>
      <div class="text-muted small mb-1.5" style="font-size: 0.72rem;">Track instantly with Ref # &amp; Passport #</div>
      <a href="/portal/track" class="btn btn-outline-primary btn-sm w-100 py-1 fw-semibold" style="font-size: 0.75rem;">
        <i class="fa-solid fa-magnifying-glass me-1"></i> Public Tracking Tool
      </a>
    </div>

  </div>

  <div class="text-center text-muted small mt-3" style="font-size: 0.75rem;">
    &copy; <?= date('Y') ?> MS TRAVEL HUB &bull; Applicant Self-Service
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
