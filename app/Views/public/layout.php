<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    <title><?= e($pageTitle ?? 'MS Travel Hub â€” Global Visa & Recruitment Services') ?></title>
    <meta name="description" content="<?= e($metaDescription ?? 'Global Visa Processing, Recruitment, and Immigration Management Portal by MS Travel Hub.') ?>">
    <link rel="canonical" href="<?= htmlspecialchars(\App\Config\Env::get('APP_URL', 'https://visatrack.mstravelhub.com') . ($_SERVER['REQUEST_URI'] ?? '/')) ?>">

    <!-- Favicon & Icons -->
    <link rel="icon" type="image/png" href="/assets/images/favicon.png?v=2">
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico?v=2">
    <link rel="apple-touch-icon" href="/assets/images/logo.png">

    <!-- Open Graph -->
    <meta property="og:title" content="<?= e($pageTitle ?? 'MS Travel Hub â€” Global Visa & Recruitment Services') ?>">
    <meta property="og:description" content="<?= e($metaDescription ?? 'Global Visa Processing, Recruitment, and Immigration Management Portal.') ?>">
    <meta property="og:type" content="website">

    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/flatpickr.min.css">

    <!-- Central Theme Engine (loads first â€” no flash) -->
    <link rel="stylesheet" href="/assets/css/theme.css?v=2.0">

    <!-- Anti-FOUC: apply theme before any render -->
    <script>
      (function(){try{var t=localStorage.getItem('vt_theme')||'ocean-royal';document.documentElement.setAttribute('data-theme',t);}catch(e){}})();
    </script>

    <?php if (!empty($extraCss)): ?>
        <?= $extraCss ?>
    <?php endif; ?>
</head>
<body>

<!-- Mobile Overlay (behind drawer) -->
<div class="pub-overlay" id="pubOverlay" aria-hidden="true"></div>

<!-- Mobile Nav Drawer -->
<nav class="pub-mobile-nav" id="pubMobileNav" aria-label="Mobile navigation" role="dialog" aria-modal="true">
    <div class="pub-mobile-nav-head">
        <a href="/" class="pub-navbar-brand">
            <span class="brand-icon"><i class="fa-solid fa-plane-departure"></i></span>
            MS TRAVEL <span class="brand-accent">HUB</span>
        </a>
        <button class="pub-mobile-close" id="pubMobileClose" aria-label="Close navigation">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <div class="pub-mobile-nav-inner">
        <a href="/" class="pub-mobile-nav-link <?= ($currentRoute ?? '') === '/' ? 'active' : '' ?>">
            <i class="fa-solid fa-house"></i> Home
        </a>
        <a href="/visa-services" class="pub-mobile-nav-link <?= str_starts_with($currentRoute ?? '', '/visa-services') || str_starts_with($currentRoute ?? '', '/visa-service') ? 'active' : '' ?>">
            <i class="fa-solid fa-passport"></i> Visa Services
        </a>
        <a href="/jobs" class="pub-mobile-nav-link <?= str_starts_with($currentRoute ?? '', '/job') ? 'active' : '' ?>">
            <i class="fa-solid fa-briefcase"></i> Job Opportunities
        </a>
        <a href="/track" class="pub-mobile-nav-link <?= ($currentRoute ?? '') === '/track' ? 'active' : '' ?>">
            <i class="fa-solid fa-magnifying-glass-location"></i> Track Application
        </a>
        <a href="/about" class="pub-mobile-nav-link <?= ($currentRoute ?? '') === '/about' ? 'active' : '' ?>">
            <i class="fa-solid fa-building"></i> About Us
        </a>
        <a href="/contact" class="pub-mobile-nav-link <?= ($currentRoute ?? '') === '/contact' ? 'active' : '' ?>">
            <i class="fa-solid fa-envelope"></i> Contact
        </a>
        <a href="/faq" class="pub-mobile-nav-link <?= ($currentRoute ?? '') === '/faq' ? 'active' : '' ?>">
            <i class="fa-solid fa-circle-question"></i> FAQ
        </a>
    </div>
    <div class="pub-mobile-nav-footer">
        <a href="/visa-enquiry" class="btn-brand w-100 justify-content-center">
            <i class="fa-solid fa-rocket"></i> Get Started Free
        </a>
        <div class="d-flex gap-2 align-items-center small" style="color:var(--color-text-muted)">
            <a href="/portal/login" class="text-decoration-none" style="color:var(--color-text-muted)">Applicant Portal</a>
            <span>Â·</span>
            <a href="/auth/login" class="text-decoration-none" style="color:var(--color-text-muted)">Staff Login</a>
        </div>
    </div>
</nav>

<!-- Top Bar -->
<div class="pub-topbar">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <a href="tel:<?= e(\App\Config\Env::get('COMPANY_PHONE', '0585909349')) ?>">
                <i class="fa-solid fa-phone me-1"></i><?= e(\App\Config\Env::get('COMPANY_PHONE', '0585909349')) ?>
            </a>
            <span class="d-none d-sm-inline" style="opacity:.3">|</span>
            <a href="mailto:<?= e(\App\Config\Env::get('COMPANY_EMAIL', 'mstravelu@gmail.com')) ?>" class="d-none d-sm-inline">
                <i class="fa-solid fa-envelope me-1"></i><?= e(\App\Config\Env::get('COMPANY_EMAIL', 'mstravelu@gmail.com')) ?>
            </a>
        </div>
        <div class="d-none d-md-flex align-items-center gap-3">
            <span style="opacity:.6"><i class="fa-solid fa-location-dot me-1"></i>Dubai Â· London Â· New York Â· Riyadh</span>
            <span style="opacity:.3">|</span>
            <a href="/portal/login"><i class="fa-solid fa-user-check me-1"></i>Applicant Portal</a>
            <a href="/auth/login"><i class="fa-solid fa-lock me-1"></i>Staff Sign In</a>
        </div>
    </div>
</div>

<!-- Main Navigation -->
<nav class="pub-navbar" aria-label="Main navigation">
    <div class="container">
        <div class="pub-navbar-inner">

            <!-- Brand -->
            <a href="/" class="pub-navbar-brand">
                <span class="brand-icon" aria-hidden="true"><i class="fa-solid fa-plane-departure"></i></span>
                MS TRAVEL <span class="brand-accent">HUB</span>
            </a>

            <!-- Desktop Nav Links -->
            <ul class="pub-nav-menu" id="pubDesktopNav" role="menubar">
                <li role="none"><a href="/" class="pub-nav-link <?= ($currentRoute ?? '') === '/' ? 'active' : '' ?>" role="menuitem">Home</a></li>
                <li role="none"><a href="/visa-services" class="pub-nav-link <?= str_starts_with($currentRoute ?? '', '/visa-service') ? 'active' : '' ?>" role="menuitem">Visa Services</a></li>
                <li role="none"><a href="/jobs" class="pub-nav-link <?= str_starts_with($currentRoute ?? '', '/job') ? 'active' : '' ?>" role="menuitem">Job Opportunities</a></li>
                <li role="none"><a href="/track" class="pub-nav-link <?= ($currentRoute ?? '') === '/track' ? 'active' : '' ?>" role="menuitem">Track Application</a></li>
                <li role="none"><a href="/about" class="pub-nav-link <?= ($currentRoute ?? '') === '/about' ? 'active' : '' ?>" role="menuitem">About</a></li>
                <li role="none"><a href="/contact" class="pub-nav-link <?= ($currentRoute ?? '') === '/contact' ? 'active' : '' ?>" role="menuitem">Contact</a></li>
                <li role="none"><a href="/faq" class="pub-nav-link <?= ($currentRoute ?? '') === '/faq' ? 'active' : '' ?>" role="menuitem">FAQ</a></li>
            </ul>

            <!-- Right side: CTA + Theme Selector + Hamburger -->
            <div class="pub-nav-right">
                <!-- Theme Selector -->
                <div class="theme-selector-wrap" aria-label="Theme selector">
                    <button class="theme-selector-btn" id="themeSelectorBtn" aria-haspopup="listbox" aria-expanded="false" title="Switch theme">
                        <span class="theme-swatch-current" aria-hidden="true"></span>
                        <span class="d-none d-lg-inline">Theme</span>
                        <i class="fa-solid fa-chevron-down" style="font-size:0.65rem;opacity:.6" aria-hidden="true"></i>
                    </button>
                    <!-- Dropdown built by theme.js -->
                </div>

                <!-- CTA Button (desktop) -->
                <a href="/visa-enquiry" class="btn-brand d-none d-lg-inline-flex">
                    <i class="fa-solid fa-rocket" aria-hidden="true"></i> Get Started
                </a>

                <!-- Hamburger (mobile) -->
                <button class="pub-hamburger" id="pubHamburger" aria-controls="pubMobileNav" aria-expanded="false" aria-label="Open navigation menu">
                    <span></span><span></span><span></span>
                </button>
            </div>

        </div>
    </div>
</nav>

<!-- Flash Messages -->
<?php $flash = get_flash(); ?>
<?php if ($flash): ?>
    <div class="pub-flash-wrap">
        <div class="container">
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : e($flash['type']) ?> alert-dismissible fade show d-flex align-items-start gap-2 shadow-sm" role="alert">
                <i class="fa-solid fa-circle-info mt-1 flex-shrink-0" aria-hidden="true"></i>
                <span><?= e($flash['message']) ?></span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Dismiss"></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Main Content -->
<main id="main-content">
    <?= $content ?? '' ?>
</main>

<!-- Footer -->
<footer class="pub-footer" role="contentinfo">
    <div class="container">
        <div class="row g-5">

            <!-- Brand Column -->
            <div class="col-lg-4 col-md-6">
                <a href="/" class="pub-navbar-brand mb-4 d-inline-flex" style="font-size:1.2rem">
                    <span class="brand-icon me-2" style="width:32px;height:32px;font-size:0.9rem"><i class="fa-solid fa-plane-departure"></i></span>
                    MS TRAVEL <span class="brand-accent ms-1">HUB</span>
                </a>
                <p class="mb-4" style="font-size:0.88rem;line-height:1.75;max-width:320px;">
                    Licensed global visa processing, manpower recruitment, and documentation consultancy. Serving corporate clients and individual applicants worldwide.
                </p>
                <div class="pub-footer-social">
                    <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in" aria-hidden="true"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram" aria-hidden="true"></i></a>
                    <a href="#" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></a>
                    <a href="#" aria-label="Twitter/X"><i class="fa-brands fa-x-twitter" aria-hidden="true"></i></a>
                </div>
            </div>

            <!-- Visa Services -->
            <div class="col-lg-2 col-md-3 col-6">
                <h3 class="pub-footer-heading">Visa Services</h3>
                <a href="/visa-services">Tourist Visas</a>
                <a href="/visa-services">Business Visas</a>
                <a href="/visa-services">Work Permits</a>
                <a href="/visa-services">Golden Residence</a>
                <a href="/visa-services">Family Sponsorship</a>
                <a href="/visa-services">Student Visas</a>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-3 col-6">
                <h3 class="pub-footer-heading">Quick Links</h3>
                <a href="/jobs">Browse Jobs</a>
                <a href="/track">Track Application</a>
                <a href="/visa-enquiry">Online Enquiry</a>
                <a href="/about">About Us</a>
                <a href="/faq">FAQ</a>
                <a href="/contact">Contact Us</a>
            </div>

            <!-- Portals -->
            <div class="col-lg-2 col-md-3 col-6">
                <h3 class="pub-footer-heading">Portals</h3>
                <a href="/portal/login"><i class="fa-solid fa-user me-1" aria-hidden="true"></i>Customer Portal</a>
                <a href="/agent/login"><i class="fa-solid fa-handshake me-1" aria-hidden="true"></i>Agent Portal</a>
                <a href="/supplier/login"><i class="fa-solid fa-truck me-1" aria-hidden="true"></i>Supplier Portal</a>
                <a href="/auth/login"><i class="fa-solid fa-lock me-1" aria-hidden="true"></i>Staff Login</a>
            </div>

            <!-- Contact -->
            <div class="col-lg-2 col-md-3 col-6">
                <h3 class="pub-footer-heading">Contact</h3>
                <span style="font-size:0.87rem;display:block;margin-bottom:10px">
                    <i class="fa-solid fa-location-dot me-2" aria-hidden="true"></i>Business Bay, Dubai, UAE
                </span>
                <a href="tel:<?= e(\App\Config\Env::get('COMPANY_PHONE', '0585909349')) ?>" style="display:block">
                    <i class="fa-solid fa-phone me-2" aria-hidden="true"></i><?= e(\App\Config\Env::get('COMPANY_PHONE', '0585909349')) ?>
                </a>
                <a href="mailto:<?= e(\App\Config\Env::get('COMPANY_EMAIL', 'mstravelu@gmail.com')) ?>" style="display:block">
                    <i class="fa-solid fa-envelope me-2" aria-hidden="true"></i>Email Us
                </a>
                <div class="mt-3">
                    <span class="pub-service-badge" style="font-size:0.72rem;background:rgba(255,255,255,0.08);color:inherit;border:1px solid rgba(255,255,255,0.15);border-radius:6px;padding:4px 10px">
                        <i class="fa-solid fa-shield-halved me-1" aria-hidden="true"></i> ISO 27001 Certified
                    </span>
                </div>
            </div>

        </div>

        <div class="pub-footer-bottom">
            <div>&copy; <?= date('Y') ?> MS Travel Hub Global Visa Services. All rights reserved.</div>
            <div class="d-flex gap-3 flex-wrap">
                <a href="/faq">Privacy Policy</a>
                <a href="/faq">Terms of Service</a>
                <a href="/contact">Support</a>
            </div>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/flatpickr.min.js"></script>
<script src="/assets/js/app-datepicker.js?v=1.0.0"></script>
<!-- Theme engine â€” loads last so DOM is ready, but theme applied inline above to prevent flash -->
<script src="/assets/js/theme.js?v=2.0"></script>

<?php if (!empty($extraJs)): ?>
    <?= $extraJs ?>
<?php endif; ?>

</body>
</html>

