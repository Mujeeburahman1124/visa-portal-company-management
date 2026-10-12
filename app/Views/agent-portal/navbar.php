<?php
// Agent Portal shared navbar partial
$agentAuth = $_SESSION['agent_auth'] ?? [];
$currentPath = (string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
function agentNavActive(string $path): string {
    global $currentPath;
    return str_starts_with((string)$currentPath, $path) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Agent Portal — MS TRAVEL HUB') ?></title>
  
  <!-- Favicons -->
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
  
  <!-- Base Application Styles & Datepicker -->
  <link rel="stylesheet" href="/assets/css/main.css?v=7.0.0">
  <link rel="stylesheet" href="/assets/css/flatpickr.min.css">
  <script src="/assets/js/flatpickr.min.js"></script>
  <script src="/assets/js/app-datepicker.js?v=1.0.0"></script>

  <!-- Agent Portal Bento Design System (Official Brand Red #E11D48 Theme) -->
  <link rel="stylesheet" href="/assets/css/pages/agent-bento.css?v=<?= time() ?>">
</head>
<body class="agent-portal-body">

<nav class="navbar navbar-expand-lg agent-navbar">
  <div class="container-fluid px-lg-4">
    <!-- Brand & Identity -->
    <a class="navbar-brand" href="/agent/dashboard">
      <img src="/assets/images/logo.png" alt="MS Travel Hub" class="agent-brand-logo">
      <div class="d-flex flex-column">
        <span class="agent-brand-title">MS <span class="brand-gem">TRAVEL HUB</span></span>
      </div>
      <span class="agent-portal-tag ms-1">
        <i class="fa-solid fa-briefcase"></i> Agent Portal
      </span>
      <?php if (!empty($agentAuth['agent_code'])): ?>
        <span class="agent-code-tag d-none d-sm-inline-flex">
          <?= e($agentAuth['agent_code']) ?>
        </span>
      <?php endif; ?>
    </a>

    <!-- Mobile Menu Toggle -->
    <button class="navbar-toggler border-0 p-2 text-white shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#agentNav" aria-controls="agentNav" aria-expanded="false" aria-label="Toggle navigation">
      <i class="fa-solid fa-bars fs-5"></i>
    </button>

    <!-- Nav Links & Agency Profile -->
    <div class="collapse navbar-collapse" id="agentNav">
      <ul class="navbar-nav mx-auto my-2 my-lg-0 gap-1">
        <li class="nav-item">
          <a class="nav-link <?= agentNavActive('/agent/dashboard') ?>" href="/agent/dashboard">
            <i class="fa-solid fa-gauge"></i> <span>Dashboard</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= agentNavActive('/agent/applications') ?>" href="/agent/applications">
            <i class="fa-solid fa-folder-open"></i> <span>My Applications</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= agentNavActive('/agent/create') ?>" href="/agent/create-application">
            <i class="fa-solid fa-circle-plus"></i> <span>New Application</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= agentNavActive('/agent/profile') ?>" href="/agent/profile">
            <i class="fa-solid fa-user-gear"></i> <span>Profile &amp; Payments</span>
          </a>
        </li>
      </ul>

      <!-- Desktop User & Session Controls (Screens >= 992px) -->
      <div class="d-none d-lg-flex align-items-center gap-2">
        <div class="agent-agency-pill">
          <i class="fa-solid fa-building"></i>
          <span><?= e($agentAuth['company_name'] ?? 'Agent Partner') ?></span>
        </div>
        <a href="/agent/logout" class="agent-btn-logout" title="Sign out of Agent Portal">
          <i class="fa-solid fa-right-from-bracket"></i>
          <span>Logout</span>
        </a>
      </div>

      <!-- Mobile Agency Card (Screens < 992px) -->
      <div class="agent-mobile-user-card d-lg-none">
        <div class="agent-mobile-user-top">
          <div class="agent-mobile-avatar">
            <i class="fa-solid fa-building"></i>
          </div>
          <div class="flex-grow-1 overflow-hidden" style="line-height: 1.25;">
            <div class="fw-bold text-white text-truncate"><?= e($agentAuth['company_name'] ?? 'Agent Partner') ?></div>
            <?php if (!empty($agentAuth['agent_code'])): ?>
              <span class="badge font-monospace mt-1" style="background:var(--agent-primary); font-size: 0.68rem;"><?= e($agentAuth['agent_code']) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <div class="agent-mobile-actions">
          <a href="/agent/profile" class="btn btn-sm btn-outline-light">
            <i class="fa-solid fa-user-gear"></i> Profile
          </a>
          <a href="/agent/logout" class="btn btn-sm text-white" style="background:var(--agent-primary); border-color:var(--agent-primary);">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
          </a>
        </div>
      </div>
    </div>
  </div>
</nav>

<div class="container-fluid px-3 px-md-4 py-4" style="max-width: 1440px;">
<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : 'success') ?> alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 small mb-4 border-0 shadow-sm rounded-3">
    <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : 'fa-circle-check' ?> fs-6"></i>
    <div class="flex-grow-1 fw-semibold"><?= e($flash['message']) ?></div>
    <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
