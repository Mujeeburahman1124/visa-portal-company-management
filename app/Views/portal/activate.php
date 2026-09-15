<?php
$pageTitle = 'Activate Portal Account — MS TRAVEL HUB';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" type="image/png" href="/assets/images/logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      font-family: 'Plus Jakarta Sans', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .brand-font {
      font-family: 'Times New Roman', Times, serif;
    }
    .activation-card {
      max-width: 480px;
      width: 100%;
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
      overflow: hidden;
    }
    .activation-header {
      background: #0f172a;
      color: #ffffff;
      padding: 32px 28px;
      text-align: center;
      border-bottom: 3px solid #0284c7;
    }
    .btn-activate {
      background-color: #0284c7;
      color: #ffffff;
      font-weight: 600;
      padding: 12px;
      border-radius: 8px;
      border: none;
      width: 100%;
      transition: all 0.2s;
    }
    .btn-activate:hover {
      background-color: #0369a1;
      color: #ffffff;
    }
  </style>
</head>
<body>

<div class="activation-card">
  <div class="activation-header">
    <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-25 rounded-circle p-3 mb-2">
      <i class="fa-solid fa-shield-halved text-info fs-3"></i>
    </div>
    <h4 class="brand-font fw-bold mb-1" style="letter-spacing: 0.5px;">MS TRAVEL HUB</h4>
    <p class="text-white-50 small mb-0"><?= e($portalTitle ?? 'Client Portal') ?> &bull; Account Activation</p>
  </div>

  <div class="p-4 p-md-5">
    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : 'success') ?> alert-dismissible fade show small mb-4" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="mb-4">
      <h5 class="fw-bold text-dark mb-1">Set Your Password</h5>
      <p class="text-muted small mb-0">Hello <strong><?= e($activation['full_name'] ?? 'User') ?></strong>, please create a secure password to activate your account.</p>
    </div>

    <form action="<?= e($actionUrl ?? '/portal/activate') ?>" method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">

      <div class="mb-3">
        <label class="form-label small fw-semibold">Registered Email</label>
        <input type="text" class="form-control bg-light" value="<?= e($activation['email'] ?? '') ?>" readonly>
      </div>

      <div class="mb-3">
        <label class="form-label small fw-semibold">New Password <span class="text-danger">*</span></label>
        <div class="input-group">
          <input type="password" name="password" id="password" class="form-control" placeholder="Minimum 6 characters" required minlength="6">
          <button class="btn btn-outline-secondary" type="button" onclick="togglePass('password')"><i class="fa-solid fa-eye"></i></button>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label small fw-semibold">Confirm Password <span class="text-danger">*</span></label>
        <div class="input-group">
          <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm your password" required minlength="6">
          <button class="btn btn-outline-secondary" type="button" onclick="togglePass('confirm_password')"><i class="fa-solid fa-eye"></i></button>
        </div>
      </div>

      <button type="submit" class="btn btn-activate mb-3">
        <i class="fa-solid fa-circle-check me-2"></i> Activate &amp; Log In
      </button>

      <div class="text-center">
        <a href="<?= e($loginUrl ?? '/portal/login') ?>" class="text-decoration-none small text-muted">
          <i class="fa-solid fa-arrow-left me-1"></i> Back to Login
        </a>
      </div>
    </form>
  </div>
</div>

<script>
function togglePass(id) {
  const el = document.getElementById(id);
  if (el.type === 'password') {
    el.type = 'text';
  } else {
    el.type = 'password';
  }
}
</script>

</body>
</html>
