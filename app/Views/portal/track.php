<?php
$pageTitle = 'Public Visa Application Tracking — VISA TRACK';
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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/main.css?v=7.0.0">
</head>
<body class="app-body">

<nav class="navbar navbar-dark sticky-top shadow py-3" style="background: linear-gradient(135deg, #090e1a 0%, #1e3a8a 50%, #2563eb 100%);">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="/portal/track">
      <div class="rounded-3 p-1 px-2.5 fw-bold text-white shadow-sm" style="background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);"><i class="fa-solid fa-route"></i></div>
      <span class="fw-bold brand-font text-white fs-5">VISA TRACK <small class="text-white-50 fw-normal" style="font-size: 0.75rem;">| SECURE PUBLIC TRACKING</small></span>
    </a>
    <a href="/portal/login" class="btn btn-outline-light btn-sm px-3 rounded-pill fw-semibold"><i class="fa-solid fa-user me-1"></i> Applicant Login</a>
  </div>
</nav>

<div class="container py-5" style="max-width: 820px;">
  <div class="text-center mb-4">
    <h3 class="fw-bold brand-font text-dark mb-1">Track Visa Application Status</h3>
    <p class="text-muted small">Enter your official application reference number and passport number to verify real-time consular progress.</p>
  </div>

  <!-- Search Card -->
  <div class="card card-enterprise shadow-sm border-0 mb-4 bg-white">
    <div class="card-body p-4">
      <form action="/portal/track" method="GET" class="row g-3 align-items-end">
        <div class="col-md-5">
          <label class="form-label small fw-semibold">Application Reference # <span class="text-danger">*</span></label>
          <input type="text" name="ref" class="form-control" placeholder="e.g. MSV-2026-000001" value="<?= e($_GET['ref'] ?? 'MSV-2026-000001') ?>" required>
        </div>
        <div class="col-md-5">
          <label class="form-label small fw-semibold">Passport Number / Customer Code <span class="text-danger">*</span></label>
          <input type="text" name="passport" class="form-control" placeholder="e.g. Z6543219" value="<?= e($_GET['passport'] ?? 'Z6543219') ?>" required>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm"><i class="fa-solid fa-magnifying-glass me-1"></i> Track</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Result Timeline Card -->
  <?php if (!empty($application)): ?>
    <div class="card card-enterprise shadow-sm border p-4 bg-white">
      <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-3 mb-3">
        <div class="d-flex align-items-center gap-2">
          <span class="fs-3"><?= $application['flag_emoji'] ?></span>
          <div>
            <div class="fw-bold fs-5 text-primary"><?= e($application['application_number']) ?></div>
            <div class="text-muted small"><?= e($application['service_name']) ?> (<?= e($application['country_name']) ?>)</div>
          </div>
        </div>
        <div>
          <span class="badge bg-primary fs-6 px-3 py-1.5 fw-bold"><?= e($application['status']) ?></span>
        </div>
      </div>

      <!-- Masked Security Summary -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Applicant:</div>
          <div class="fw-bold text-dark"><?= e($application['masked_name']) ?></div>
        </div>
        <div class="col-6 col-md-3">
          <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Passport Number:</div>
          <div class="fw-semibold font-monospace text-dark"><?= e($application['masked_passport']) ?></div>
        </div>
        <div class="col-6 col-md-3">
          <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Application Date:</div>
          <div class="fw-semibold text-dark"><?= format_date($application['application_date']) ?></div>
        </div>
        <div class="col-6 col-md-3">
          <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Current Stage:</div>
          <div class="fw-bold text-primary"><?= e($application['current_stage']) ?></div>
        </div>
      </div>

      <!-- Simplified Friendly Milestones Progress Bar -->
      <div class="p-3 bg-light rounded border mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-bold small text-uppercase text-dark"><i class="fa-solid fa-route text-primary me-1"></i> Current Processing Status</span>
          <span class="badge bg-success-subtle text-success fw-bold"><?= e($application['status']) ?></span>
        </div>

        <div class="progress" style="height: 10px;">
          <?php
            $health = 30;
            $curr = strtolower($application['current_stage']);
            if (str_contains($curr, 'doc')) $health = 45;
            elseif (str_contains($curr, 'prep')) $health = 60;
            elseif (str_contains($curr, 'bio') || str_contains($curr, 'appoint')) $health = 75;
            elseif (str_contains($curr, 'embassy') || str_contains($curr, 'submi')) $health = 85;
            elseif (str_contains($curr, 'approv') || str_contains($curr, 'issued') || str_contains($curr, 'complet')) $health = 100;
          ?>
          <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $health ?>%;"></div>
        </div>
      </div>

      <div class="d-flex flex-wrap justify-content-between align-items-center text-muted small pt-2">
        <span><i class="fa-solid fa-lock text-success me-1"></i> Public view masked for applicant privacy.</span>
        <a href="/portal/login" class="fw-semibold text-primary text-decoration-none">
          Sign In for Full Documents &amp; Invoices &rarr;
        </a>
      </div>
    </div>
  <?php elseif (isset($_GET['ref'])): ?>
    <div class="alert alert-warning text-center py-4 border-0 shadow-sm">
      <i class="fa-solid fa-triangle-exclamation fs-3 d-block mb-2 text-warning"></i>
      <strong>No Matching Record Found</strong>
      <div class="small text-muted mt-1">Please verify your Application Reference Number and Passport Number.</div>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

