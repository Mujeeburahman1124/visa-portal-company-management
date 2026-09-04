<?php $pageTitle = 'Agent Portal Login — MS Travel Hub'; $flash = get_flash(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🤝</text></svg>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/main.css?v=7.0.0">
</head>
<body class="auth-page-body">
<div class="container d-flex flex-column align-items-center justify-content-center" style="min-height:100vh;padding:1.5rem 1rem;">
  <div class="auth-card-compact">
    <div class="text-center mb-3">
      <div class="d-inline-flex align-items-center justify-content-center text-white rounded-3 shadow-sm mb-2" style="width:48px;height:48px;font-size:1.4rem;background:linear-gradient(135deg,#065f46 0%,#10b981 100%);">
        <i class="fa-solid fa-handshake"></i>
      </div>
      <h3 class="fw-bold brand-font text-dark mb-0" style="font-size:1.45rem;">MS Travel Hub</h3>
      <div class="text-muted small">Agent Partner Portal</div>
    </div>
    <div class="text-center mb-3">
      <h5 class="fw-bold text-dark mb-0">Agent Sign In</h5>
      <p class="text-muted small mb-0">Submit visa applications &amp; track your clients</p>
    </div>
    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show py-2 px-3 small mb-3 border-0 shadow-sm">
        <i class="fa-solid <?= $flash['type']==='danger'?'fa-circle-exclamation':'fa-circle-check' ?> me-1"></i>
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <form action="/agent/login" method="POST">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label small fw-semibold">Email Address</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fa-solid fa-envelope text-muted"></i></span>
          <input type="email" name="email" class="form-control" placeholder="agent@skylinetravel.com" value="agent@skylinetravel.com" required autofocus>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label small fw-semibold">Password</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fa-solid fa-lock text-muted"></i></span>
          <input type="password" name="password" class="form-control" placeholder="••••••••" value="password123" required>
        </div>
        <div class="text-muted small mt-1" style="font-size: 0.72rem;">Demo password: <code>password123</code></div>
      </div>
      <button type="submit" class="btn btn-success w-100 fw-semibold py-2">
        <i class="fa-solid fa-right-to-bracket me-2"></i>Sign In to Agent Portal
      </button>
    </form>
    <div class="text-center mt-3 small text-muted">
      <a href="/auth/login" class="text-decoration-none text-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Staff Login</a>
      &nbsp;|&nbsp;
      <a href="/supplier/login" class="text-decoration-none text-secondary">Supplier Portal</a>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
