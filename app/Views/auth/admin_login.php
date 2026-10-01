<?php
$pageTitle = 'Super Administrator Console — VISA TRACK';
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/main.css?v=7.1.0">
  <style>
    .admin-login-body {
      min-height: 100vh;
      background: radial-gradient(circle at 50% 20%, #0f172a 0%, #020617 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      font-family: system-ui, -apple-system, sans-serif;
    }
    .admin-card {
      width: 100%;
      max-width: 420px;
      background: rgba(15, 23, 42, 0.95);
      border: 1px solid rgba(239, 68, 68, 0.3);
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.7), 0 0 35px rgba(239, 68, 68, 0.15);
      border-radius: 16px;
      padding: 2rem 1.75rem;
      color: #f8fafc;
    }
    .admin-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 12px;
      background: rgba(239, 68, 68, 0.15);
      border: 1px solid rgba(239, 68, 68, 0.35);
      color: #fca5a5;
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      border-radius: 20px;
    }
    .admin-input-group {
      background: #090d16;
      border: 1.5px solid #1e293b;
      border-radius: 10px;
      display: flex;
      align-items: center;
      transition: all 0.2s;
    }
    .admin-input-group:focus-within {
      border-color: #ef4444;
      box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
    }
    .admin-input-group .icon {
      padding: 0 12px;
      color: #64748b;
      font-size: 0.85rem;
    }
    .admin-input-group input {
      flex: 1;
      background: transparent;
      border: none;
      color: #f8fafc;
      padding: 9px 8px 9px 0;
      font-size: 0.9rem;
      outline: none;
    }
    .admin-input-group input::placeholder {
      color: #475569;
    }
    .btn-admin-submit {
      width: 100%;
      height: 42px;
      background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
      color: #ffffff;
      border: none;
      border-radius: 10px;
      font-size: 0.92rem;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.2s;
      box-shadow: 0 4px 14px rgba(239, 68, 68, 0.3);
    }
    .btn-admin-submit:hover {
      opacity: 0.94;
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(239, 68, 68, 0.4);
    }
    .btn-admin-submit:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
    @media (max-width: 480px) {
      .admin-card {
        padding: 1.25rem 1rem;
        border-radius: 14px;
      }
      .admin-badge {
        font-size: 0.68rem;
      }
    }
  </style>
</head>
<body class="admin-login-body">

<div class="admin-card">
  <!-- Brand & Security Header -->
  <div class="text-center mb-3">
    <div class="admin-badge mb-2">
      <i class="fa-solid fa-shield-halved"></i> Super Admin Clearance Only
    </div>
    <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
      <img src="/assets/images/logo.png" alt="Logo" style="height: 38px; width: auto;">
      <h4 class="fw-bold mb-0 text-white" style="letter-spacing: -0.01em;">MS TRAVEL HUB</h4>
    </div>
    <p class="text-secondary small mb-0" style="font-size: 0.8rem;">Executive Management &amp; System Configuration</p>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show py-2 px-3 small mb-3 border-0" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-triangle-exclamation' : 'fa-circle-info' ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <form action="/admin/login" method="POST" id="adminLoginForm">
    <?= csrf_field() ?>

    <div class="mb-2.5">
      <label class="form-label small text-secondary fw-semibold mb-1" style="font-size: 0.82rem;">Super Admin Email</label>
      <div class="admin-input-group">
        <span class="icon"><i class="fa-solid fa-envelope"></i></span>
        <input type="email" name="email" id="adminEmail" placeholder="admin@system.com" required autocomplete="username" value="">
      </div>
    </div>

    <div class="mb-3">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <label class="form-label small text-secondary fw-semibold mb-0" style="font-size: 0.82rem;">Admin Password</label>
        <a href="/auth/forgot-password" class="text-danger small text-decoration-none" style="font-size: 0.75rem;">Recovery</a>
      </div>
      <div class="admin-input-group">
        <span class="icon"><i class="fa-solid fa-lock"></i></span>
        <input type="password" name="password" id="adminPass" placeholder="••••••••" required autocomplete="current-password">
        <button type="button" class="btn btn-link text-secondary text-decoration-none px-2" id="toggleAdminPass" aria-label="Toggle password visibility">
          <i class="fa-solid fa-eye" id="toggleAdminIcon" style="font-size: 0.8rem;"></i>
        </button>
      </div>
    </div>

    <!-- 1-Click Credential Helper -->
    <div class="p-2 mb-3 rounded" style="background: rgba(255,255,255,0.04); border: 1px dashed rgba(255,255,255,0.12); font-size: 0.75rem;">
      <div class="d-flex justify-content-between align-items-center">
        <span class="text-secondary"><i class="fa-solid fa-key me-1 text-danger"></i> Super Admin Access:</span>
        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-1.5" style="font-size: 0.7rem;" onclick="document.getElementById('adminEmail').value='admin@system.com'; document.getElementById('adminPass').value='admin123';">
          Use Default Admin
        </button>
      </div>
    </div>

    <button type="submit" class="btn-admin-submit" id="btnAdminSubmit">
      <i class="fa-solid fa-lock-open" id="adminSubmitIcon"></i>
      <span class="spinner-border spinner-border-sm d-none" id="adminSubmitSpinner"></span>
      Authenticate as Super Admin
    </button>
  </form>

  <!-- Navigation Links -->
  <div class="text-center mt-3 pt-2.5 border-top border-secondary border-opacity-25 small">
    <span class="text-secondary" style="font-size: 0.78rem;">Regular Staff Member?</span>
    <a href="/auth/login" class="text-white fw-bold text-decoration-none ms-1" style="font-size: 0.78rem;">Staff Login &rarr;</a>
  </div>
</div>

<script>
document.getElementById('toggleAdminPass')?.addEventListener('click', function() {
  const p = document.getElementById('adminPass');
  const ic = document.getElementById('toggleAdminIcon');
  if (p.type === 'password') {
    p.type = 'text';
    ic.classList.replace('fa-eye', 'fa-eye-slash');
  } else {
    p.type = 'password';
    ic.classList.replace('fa-eye-slash', 'fa-eye');
  }
});

document.getElementById('adminLoginForm')?.addEventListener('submit', function() {
  const btn = document.getElementById('btnAdminSubmit');
  const sp = document.getElementById('adminSubmitSpinner');
  const ic = document.getElementById('adminSubmitIcon');
  btn.disabled = true;
  sp.classList.remove('d-none');
  ic.classList.add('d-none');
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
