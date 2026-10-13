<!DOCTYPE html>
<html lang="en" data-theme="ms-ruby-prestige">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
  <meta name="theme-color" content="#e11d48">
  <meta name="csrf-token" content="<?= csrf_token() ?>">

  <!-- Enterprise SEO, Open Graph & Structured Data Engine -->
  <?= \App\Services\SeoService::renderTags(['title' => $pageTitle ?? 'MS TRAVEL HUB — Global Visa Management Portal']) ?>
  <?= \App\Services\SeoService::renderStructuredData() ?>
  
  <!-- Official Favicon -->
  <link rel="icon" type="image/png" href="/assets/images/favicon.png?v=2">
  <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico?v=2">
  <link rel="apple-touch-icon" href="/assets/images/logo.png">
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- FontAwesome 6 Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <!-- Google Fonts: Plus Jakarta Sans & Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Central Theme Engine — must load BEFORE main.css -->
  <link rel="stylesheet" href="/assets/css/theme.css?v=3.0">
  <!-- Application CSS -->
  <link rel="stylesheet" href="/assets/css/main.css?v=7.3.0">
  <!-- Bankio FinTech Design System -->
  <link rel="stylesheet" href="/assets/css/bankio-theme.css?v=2.1">
  <!-- Flatpickr Datepicker CSS -->
  <link rel="stylesheet" href="/assets/css/flatpickr.min.css">

  <!-- Anti-FOUC: Strictly enforce official brand red theme matching logo -->
  <script>
    (function(){
      try {
        localStorage.setItem('vt_theme', 'ms-ruby-prestige');
        document.documentElement.setAttribute('data-theme', 'ms-ruby-prestige');
      } catch(e) {}
    })();
  </script>

  <?php
    // Official MS Travel Hub Logo Harmonized Brand Theme (#E11D48 Ruby)
    $thPrimary = '#E11D48';
    $thAccent = '#0F172A';
    $thRadius = '14px';
    $thFont = "'Outfit', 'Inter', sans-serif";
    $thMode = 'light';
  ?>
  <style id="dynamicAppThemeVars">
    :root,
    :root:not([data-theme]),
    :root[data-theme=""],
    :root[data-theme="ms-ruby-prestige"] {
      --primary-color: <?= e($thPrimary) ?>;
      --bs-primary: <?= e($thPrimary) ?>;
      --vt-primary: <?= e($thPrimary) ?>;
      --color-primary: <?= e($thPrimary) ?>;
      --primary-hover: #be123c;
      --accent-color: <?= e($thAccent) ?>;
      --border-radius-base: <?= e($thRadius) ?>;
    }
    :root {
      --border-radius-base: <?= e($thRadius) ?>;
      --font-heading: 'Outfit', 'Inter', sans-serif;
      --font-body: 'Inter', -apple-system, sans-serif;
    }
    body, body.app-body {
      font-family: var(--font-body) !important;
    }
    .brand-font, h1, h2, h3, h4, h5, h6, .brand-title, .stat-value {
      font-family: var(--font-heading) !important;
    }
    :root:not([data-theme]) .btn-primary,
    :root[data-theme=""] .btn-primary {
      background-color: var(--primary-color) !important;
      border-color: var(--primary-color) !important;
    }
    :root:not([data-theme]) .btn-primary:hover,
    :root:not([data-theme]) .btn-primary:focus,
    :root[data-theme=""] .btn-primary:hover,
    :root[data-theme=""] .btn-primary:focus {
      background-color: var(--primary-hover) !important;
      border-color: var(--primary-hover) !important;
    }
    :root:not([data-theme]) .btn-outline-primary,
    :root[data-theme=""] .btn-outline-primary {
      color: var(--primary-color) !important;
      border-color: var(--primary-color) !important;
    }
    :root:not([data-theme]) .text-primary,
    :root[data-theme=""] .text-primary {
      color: var(--primary-color) !important;
    }
    :root:not([data-theme]) .bg-primary,
    :root[data-theme=""] .bg-primary {
      background-color: var(--primary-color) !important;
    }
    :root:not([data-theme]) .border-primary,
    :root[data-theme=""] .border-primary {
      border-color: var(--primary-color) !important;
    }
    :root:not([data-theme]) .nav-tabs .nav-link.active,
    :root[data-theme=""] .nav-tabs .nav-link.active {
      border-bottom-color: var(--primary-color) !important;
      color: var(--primary-color) !important;
    }
  </style>

  <script>
    window.openModalById = function (modalId, evt) {
      var e = evt || window.event;
      if (e) {
        if (typeof e.preventDefault === 'function') e.preventDefault();
        if (typeof e.stopPropagation === 'function') e.stopPropagation();
        if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
      }
      var el = document.getElementById(modalId);
      if (!el) {
        console.error('Modal element not found: ' + modalId);
        return;
      }
      // Promote modal directly to document.body so it breaks out of container stacking contexts/overflow
      if (el.parentNode !== document.body) {
        document.body.appendChild(el);
      }
      try {
        if (window.bootstrap && bootstrap.Modal) {
          var modalInstance = bootstrap.Modal.getOrCreateInstance(el);
          modalInstance.show();
          return;
        }
      } catch (err) {
        console.warn('Bootstrap modal instance failed, using fallback:', err);
      }
      el.classList.add('show');
      el.style.display = 'block';
      el.removeAttribute('aria-hidden');
      el.setAttribute('aria-modal', 'true');
      el.style.zIndex = '1065';
      document.body.classList.add('modal-open');
      var backdrop = document.getElementById('vt-modal-backdrop');
      if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.id = 'vt-modal-backdrop';
        backdrop.className = 'modal-backdrop fade show';
        backdrop.style.zIndex = '1050';
        document.body.appendChild(backdrop);
      }
    };
    window.closeModalById = function (modalId) {
      var el = document.getElementById(modalId);
      if (!el) return;
      try {
        if (window.bootstrap && bootstrap.Modal) {
          var instance = bootstrap.Modal.getInstance(el);
          if (instance) {
            instance.hide();
            return;
          }
        }
      } catch (e) {}
      el.classList.remove('show');
      el.style.display = 'none';
      el.setAttribute('aria-hidden', 'true');
      el.removeAttribute('aria-modal');
      var openModals = document.querySelectorAll('.modal.show');
      if (openModals.length === 0) {
        document.body.classList.remove('modal-open');
        var backdrop = document.getElementById('vt-modal-backdrop');
        if (backdrop) backdrop.remove();
        document.querySelectorAll('.modal-backdrop').forEach(function(b) { b.remove(); });
      }
    };
  </script>
</head>
<body class="app-body <?= $thMode === 'dark' ? 'theme-dark' : 'theme-light' ?>">
<div class="app-wrapper" id="appWrapper">
  <!-- Mobile Sidebar Overlay Backdrop -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
