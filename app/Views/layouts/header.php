<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
  <meta name="theme-color" content="#e11d48">
  <title><?= e($pageTitle ?? 'MS TRAVEL HUB — Global Visa Management Portal') ?></title>
  
  <!-- Official Favicon -->
  <link rel="icon" type="image/png" href="/assets/images/logo.png">
  <link rel="apple-touch-icon" href="/assets/images/logo.png">
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- FontAwesome 6 Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <!-- Google Fonts: Plus Jakarta Sans & Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Application CSS -->
  <link rel="stylesheet" href="/assets/css/main.css?v=7.0.0">

  <?php
    // Dynamic Website Themes from system_settings
    $thPrimary = '#0284c7';
    $thAccent = '#059669';
    $thRadius = '8px';
    $thFont = "'Times New Roman', Times, serif";
    $thMode = 'light';
    try {
        $thPdo = \App\Config\Database::getConnection();
        $thRows = $thPdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'theme_%'")->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];
        if (!empty($thRows['theme_primary_color'])) $thPrimary = $thRows['theme_primary_color'];
        if (!empty($thRows['theme_accent_color'])) $thAccent = $thRows['theme_accent_color'];
        if (!empty($thRows['theme_border_radius'])) $thRadius = $thRows['theme_border_radius'];
        if (!empty($thRows['theme_font_family'])) $thFont = $thRows['theme_font_family'];
        if (!empty($thRows['theme_mode'])) $thMode = $thRows['theme_mode'];
    } catch (\Throwable $e) {}
  ?>
  <style id="dynamicAppThemeVars">
    :root {
      --primary-color: <?= e($thPrimary) ?>;
      --bs-primary: <?= e($thPrimary) ?>;
      --primary-hover: <?= e($thPrimary) ?>dd;
      --accent-color: <?= e($thAccent) ?>;
      --border-radius-base: <?= e($thRadius) ?>;
      --font-heading: <?= $thFont ?>;
    }
    .brand-font, h1.brand-font, h2.brand-font, h3.brand-font, h4.brand-font, h5.brand-font, h6.brand-font {
      font-family: var(--font-heading) !important;
    }
    .btn-primary {
      background-color: var(--primary-color) !important;
      border-color: var(--primary-color) !important;
    }
    .btn-primary:hover, .btn-primary:focus {
      background-color: var(--primary-hover) !important;
      border-color: var(--primary-hover) !important;
    }
    .btn-outline-primary {
      color: var(--primary-color) !important;
      border-color: var(--primary-color) !important;
    }
    .btn-outline-primary:hover {
      background-color: var(--primary-color) !important;
      color: #ffffff !important;
    }
    .text-primary {
      color: var(--primary-color) !important;
    }
    .bg-primary {
      background-color: var(--primary-color) !important;
    }
    .border-primary {
      border-color: var(--primary-color) !important;
    }
    .nav-tabs .nav-link.active {
      border-bottom-color: var(--primary-color) !important;
      color: var(--primary-color) !important;
    }
    <?php if ($thMode === 'dark'): ?>
    body.app-body {
      background-color: #0f172a !important;
      color: #cbd5e1 !important;
    }
    .content-body, .card-custom, .card-enterprise, .bg-white {
      background-color: #1e293b !important;
      color: #f1f5f9 !important;
    }
    .text-dark {
      color: #f8fafc !important;
    }
    .table-light, thead.table-light th {
      background-color: #334155 !important;
      color: #f8fafc !important;
    }
    <?php endif; ?>
  </style>

  <!-- Core Bootstrap 5 Bundle JS (Loaded early so modal/dropdown APIs exist everywhere) -->
  <script src="/assets/js/bootstrap.bundle.min.js"></script>
</head>
<body class="app-body <?= $thMode === 'dark' ? 'theme-dark' : 'theme-light' ?>">
<div class="app-wrapper" id="appWrapper">
  <!-- Mobile Sidebar Overlay Backdrop -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
