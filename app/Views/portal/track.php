<?php
$pageTitle = 'Public Visa Application Tracking — MS TRAVEL HUB';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Enterprise SEO, Open Graph & Structured Data Engine -->
  <?= \App\Services\SeoService::renderTags([
      'title' => 'Track Visa Application Status Online | Real-Time Verification — MS TRAVEL HUB',
      'description' => 'Track your UAE, Schengen, UK, and global visa application status online in real time. Secure 2-factor verification for consular processing milestones and document checklist.',
      'keywords' => 'track visa status, UAE visa check, track Dubai visa, check visa status online, visa tracking portal, MS Travel Hub tracking, passport visa enquiry, Schengen visa progress',
      'canonical' => \App\Services\SeoService::getBaseUrl() . '/track',
      'robots' => 'index, follow, max-image-preview:large, max-snippet:-1'
  ]) ?>
  <?= \App\Services\SeoService::renderStructuredData([
      '@type' => 'WebApplication',
      'name' => 'MS Travel Hub Visa Application Tracker',
      'url' => \App\Services\SeoService::getBaseUrl() . '/track',
      'applicationCategory' => 'TravelApplication',
      'operatingSystem' => 'All',
      'offers' => [
          '@type' => 'Offer',
          'price' => '0',
          'priceCurrency' => 'USD'
      ],
      'featureList' => [
          '2-Factor Secure Consular Lookup',
          'Milestone Journey Timeline',
          'Automated Document Checklist Verification',
          'Consular SLA Target Tracking'
      ]
  ]) ?>
  
  <!-- Favicons -->
  <link rel="icon" type="image/png" href="/assets/images/favicon.png?v=2">
  <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico?v=2">
  <link rel="apple-touch-icon" href="/assets/images/logo.png">

  <!-- Typography -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Frameworks & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/main.css?v=7.0.0">

  <!-- Bento Styling -->
  <link rel="stylesheet" href="/assets/css/pages/portal-bento.css?v=<?= time() ?>">
</head>
<body class="portal-body">

<nav class="navbar portal-navbar sticky-top">
  <div class="container d-flex align-items-center justify-content-between">
    <a class="navbar-brand" href="/portal/track">
      <img src="/assets/images/logo.png" alt="MS Travel Hub" class="portal-brand-logo">
      <div class="d-flex flex-column">
        <span class="portal-brand-title">MS <span class="brand-gem">TRAVEL HUB</span></span>
      </div>
      <span class="portal-tag ms-1">
        <i class="fa-solid fa-route"></i> Public Tracking
      </span>
    </a>
    <a href="/portal/login" class="portal-btn-primary py-1.5 px-3" style="font-size:0.82rem;">
      <i class="fa-solid fa-user me-1"></i> Applicant Sign In
    </a>
  </div>
</nav>

<div class="container py-5" style="max-width: 860px;">
  <div class="text-center mb-4">
    <h2 class="fw-bold mb-1 text-dark" style="font-family:'Outfit', sans-serif;">Track Visa Application Status</h2>
    <p class="text-muted small">Enter your official application reference number and passport number to verify real-time consular progress.</p>
  </div>

  <!-- Search Card -->
  <div class="portal-bento-card mb-4 p-4">
    <form action="/portal/track" method="GET" class="row g-3 align-items-end">
      <div class="col-md-5">
        <label class="form-label small fw-semibold">Application Reference # <span class="text-danger">*</span></label>
        <input type="text" name="ref" class="form-control portal-form-control font-monospace" placeholder="e.g. MSV-2026-000001" value="<?= e($_GET['ref'] ?? 'MSV-2026-000001') ?>" required>
      </div>
      <div class="col-md-5">
        <label class="form-label small fw-semibold">Passport Number / Customer Code <span class="text-danger">*</span></label>
        <input type="text" name="passport" class="form-control portal-form-control font-monospace" placeholder="e.g. Z6543219" value="<?= e($_GET['passport'] ?? 'Z6543219') ?>" required>
      </div>
      <div class="col-md-2">
        <button type="submit" class="portal-btn-primary w-100 py-2 justify-content-center">
          <i class="fa-solid fa-magnifying-glass"></i>
          <span>Track</span>
        </button>
      </div>
    </form>
  </div>

  <!-- Result Timeline Card -->
  <?php if (!empty($application)): ?>
    <div class="portal-bento-card p-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-3 mb-3">
        <div class="d-flex align-items-center gap-3">
          <span class="fs-2 p-2 bg-light rounded-3 border"><?= $application['flag_emoji'] ?></span>
          <div>
            <div class="fw-bold fs-5 portal-app-num"><?= e($application['application_number']) ?></div>
            <div class="text-muted small"><?= e($application['service_name']) ?> (<?= e($application['country_name']) ?>)</div>
          </div>
        </div>
        <div>
          <span class="badge fs-6 px-3 py-1.5 fw-bold rounded-pill" style="background:var(--portal-primary);">
            <?= e($application['status']) ?>
          </span>
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
          <div class="fw-bold" style="color:var(--portal-primary);"><?= e($application['current_stage']) ?></div>
        </div>
      </div>

      <!-- Simplified Friendly Milestones Progress Bar -->
      <div class="p-3 bg-light rounded-3 border mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-bold small text-uppercase text-dark"><i class="fa-solid fa-route me-1" style="color:var(--portal-primary);"></i> Current Processing Status</span>
          <span class="badge bg-success-subtle text-success fw-bold"><?= e($application['status']) ?></span>
        </div>

        <div class="progress" style="height: 10px; border-radius: 999px;">
          <?php
            $health = 30;
            $curr = strtolower($application['current_stage']);
            if (str_contains($curr, 'doc')) $health = 45;
            elseif (str_contains($curr, 'prep')) $health = 60;
            elseif (str_contains($curr, 'bio') || str_contains($curr, 'appoint')) $health = 75;
            elseif (str_contains($curr, 'embassy') || str_contains($curr, 'submi')) $health = 85;
            elseif (str_contains($curr, 'approv') || str_contains($curr, 'issued') || str_contains($curr, 'complet')) $health = 100;
          ?>
          <div class="progress-bar" role="progressbar" style="width: <?= $health ?>%; background: linear-gradient(135deg, var(--portal-primary) 0%, var(--portal-primary-hover) 100%);"></div>
        </div>
      </div>

      <div class="d-flex flex-wrap justify-content-between align-items-center text-muted small pt-2 border-top">
        <span><i class="fa-solid fa-lock text-success me-1"></i> Public view masked for applicant privacy.</span>
        <a href="/portal/login" class="fw-semibold text-decoration-none" style="color:var(--portal-primary);">
          Sign In for Full Documents &amp; Invoices &rarr;
        </a>
      </div>
    </div>
  <?php elseif (isset($_GET['ref'])): ?>
    <div class="alert alert-warning text-center py-4 border-0 shadow-sm rounded-4">
      <i class="fa-solid fa-triangle-exclamation fs-3 d-block mb-2 text-warning"></i>
      <strong>No Matching Record Found</strong>
      <div class="small text-muted mt-1">Please verify your Application Reference Number and Passport Number.</div>
    </div>
  <?php endif; ?>
</div>

<footer class="text-center py-4 text-muted small" style="border-top: 1px solid var(--portal-border-subtle);">
  &copy; <?= date('Y') ?> <strong style="color:var(--portal-text-main);">MS TRAVEL HUB</strong> &bull; Public Visa Verification Portal &bull; Enterprise Visa Solutions
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
