<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'MS Travel Hub — Global Visa & Recruitment Services') ?></title>
    <meta name="description" content="<?= e($metaDescription ?? 'Global Visa Processing, Recruitment, and Immigration Management Portal by MS Travel Hub.') ?>">
    <link rel="canonical" href="<?= htmlspecialchars(\App\Config\Env::get('APP_URL', 'https://visatrack.mstravelhub.com') . ($_SERVER['REQUEST_URI'] ?? '/')) ?>">

    <!-- Open Graph SEO -->
    <meta property="og:title" content="<?= e($pageTitle ?? 'MS Travel Hub — Global Visa & Recruitment Services') ?>">
    <meta property="og:description" content="<?= e($metaDescription ?? 'Global Visa Processing, Recruitment, and Immigration Management Portal.') ?>">
    <meta property="og:type" content="website">

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/flatpickr.min.css">

    <style>
        :root {
            --primary-color: #0f172a;
            --accent-color: #0284c7;
            --accent-hover: #0369a1;
            --brand-gold: #f59e0b;
            --bg-light: #f8fafc;
            --text-main: #334155;
            --text-heading: #0f172a;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-main);
            background-color: #ffffff;
            line-height: 1.6;
            overflow-x: hidden;
        }

        .top-bar {
            background-color: #0f172a;
            color: #94a3b8;
            font-size: 0.82rem;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .top-bar a {
            color: #e2e8f0;
            text-decoration: none;
        }

        .top-bar a:hover {
            color: #38bdf8;
        }

        .navbar-main {
            background: #ffffff;
            box-shadow: 0 4px 20px -5px rgba(15, 23, 42, 0.08);
            padding: 14px 0;
        }

        .navbar-brand-logo {
            font-size: 1.4rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
            text-decoration: none;
        }

        .navbar-brand-logo span {
            color: var(--accent-color);
        }

        .nav-link {
            font-weight: 500;
            color: #334155 !important;
            font-size: 0.94rem;
            padding: 6px 14px !important;
            transition: color 0.2s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--accent-color) !important;
        }

        .btn-brand {
            background-color: var(--accent-color);
            color: #ffffff;
            font-weight: 600;
            border-radius: 6px;
            padding: 9px 20px;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-brand:hover {
            background-color: var(--accent-hover);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .btn-outline-brand {
            border: 2px solid var(--accent-color);
            color: var(--accent-color);
            font-weight: 600;
            border-radius: 6px;
            padding: 8px 18px;
            background: transparent;
            transition: all 0.2s ease;
        }

        .btn-outline-brand:hover {
            background-color: var(--accent-color);
            color: #ffffff;
        }

        .hero-section {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            padding: 80px 0 90px;
            position: relative;
        }

        .hero-title {
            font-size: 2.8rem;
            font-weight: 800;
            letter-spacing: -1px;
            line-height: 1.15;
            color: #ffffff;
        }

        .hero-title span {
            color: #38bdf8;
        }

        .card-custom {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card-custom:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        }

        .feature-icon {
            width: 52px;
            height: 52px;
            border-radius: 10px;
            background: rgba(2, 132, 199, 0.1);
            color: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 16px;
        }

        .footer-main {
            background-color: #0f172a;
            color: #94a3b8;
            padding: 60px 0 30px;
            font-size: 0.9rem;
        }

        .footer-main h5 {
            color: #ffffff;
            font-weight: 700;
            font-size: 1.05rem;
            margin-bottom: 20px;
        }

        .footer-main a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .footer-main a:hover {
            color: #38bdf8;
        }

        .badge-country {
            background: #f1f5f9;
            color: #1e293b;
            font-weight: 600;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 0.8rem;
        }

        @media (max-width: 768px) {
            .hero-title { font-size: 2rem; }
            .hero-section { padding: 50px 0; }
        }
    </style>
</head>
<body>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <i class="fa-solid fa-phone me-1.5 text-info"></i> <?= e(\App\Config\Env::get('COMPANY_PHONE', '0585909349')) ?>
                <span class="mx-2 text-muted">|</span>
                <i class="fa-solid fa-envelope me-1.5 text-info"></i> <?= e(\App\Config\Env::get('COMPANY_EMAIL', 'mstravelu@gmail.com')) ?>
            </div>
            <div class="d-none d-md-flex align-items-center gap-3">
                <span><i class="fa-solid fa-location-dot me-1 text-info"></i> Dubai • London • New York • Riyadh</span>
                <span class="text-muted">|</span>
                <a href="/portal/login"><i class="fa-solid fa-user-check me-1"></i> Applicant Portal</a>
                <span class="text-muted">|</span>
                <a href="/auth/login"><i class="fa-solid fa-lock me-1"></i> Staff Sign In</a>
            </div>
        </div>
    </div>

    <!-- Header Navigation -->
    <nav class="navbar navbar-expand-lg navbar-main sticky-top">
        <div class="container">
            <a class="navbar-brand navbar-brand-logo" href="/">
                <i class="fa-solid fa-plane-departure text-info me-2"></i>MS TRAVEL <span>HUB</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link <?= ($currentRoute ?? '') === '/' ? 'active' : '' ?>" href="/">Home</a></li>
                    <li class="nav-item"><a class="nav-link <?= str_starts_with($currentRoute ?? '', '/visa-services') ? 'active' : '' ?>" href="/visa-services">Visa Services</a></li>
                    <li class="nav-item"><a class="nav-link <?= str_starts_with($currentRoute ?? '', '/jobs') ? 'active' : '' ?>" href="/jobs">Job Opportunities</a></li>
                    <li class="nav-item"><a class="nav-link <?= ($currentRoute ?? '') === '/track' ? 'active' : '' ?>" href="/track">Track Application</a></li>
                    <li class="nav-item"><a class="nav-link <?= ($currentRoute ?? '') === '/about' ? 'active' : '' ?>" href="/about">About Us</a></li>
                    <li class="nav-item"><a class="nav-link <?= ($currentRoute ?? '') === '/contact' ? 'active' : '' ?>" href="/contact">Contact</a></li>
                    <li class="nav-item"><a class="nav-link <?= ($currentRoute ?? '') === '/faq' ? 'active' : '' ?>" href="/faq">FAQ</a></li>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    <a href="/visa-enquiry" class="btn btn-brand">Get Started</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php $flash = get_flash(); ?>
    <?php if ($flash): ?>
        <div class="container mt-3">
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : e($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
                <i class="fa-solid fa-circle-info me-2"></i> <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Page Content -->
    <main>
        <?= $content ?? '' ?>
    </main>

    <!-- Footer -->
    <footer class="footer-main">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="navbar-brand-logo text-white mb-3">
                        <i class="fa-solid fa-plane-departure text-info me-2"></i>MS TRAVEL <span class="text-info">HUB</span>
                    </div>
                    <p class="small text-secondary mb-3">
                        Licensed global visa processing, manpower recruitment, and documentation consultancy. Serving corporate clients and individual applicants worldwide.
                    </p>
                    <div class="d-flex gap-2">
                        <a href="#" class="btn btn-outline-secondary btn-sm"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" class="btn btn-outline-secondary btn-sm"><i class="fa-brands fa-linkedin-in"></i></a>
                        <a href="#" class="btn btn-outline-secondary btn-sm"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" class="btn btn-outline-secondary btn-sm"><i class="fa-brands fa-whatsapp"></i></a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-4">
                    <h5>Visa Services</h5>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><a href="/visa-services">Tourist Visas</a></li>
                        <li class="mb-2"><a href="/visa-services">Business Visas</a></li>
                        <li class="mb-2"><a href="/visa-services">Work Permits</a></li>
                        <li class="mb-2"><a href="/visa-services">Golden Residence</a></li>
                        <li class="mb-2"><a href="/visa-services">Family Sponsorship</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-4">
                    <h5>Quick Navigation</h5>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><a href="/jobs">Recruitment Jobs</a></li>
                        <li class="mb-2"><a href="/track">Public Application Tracking</a></li>
                        <li class="mb-2"><a href="/visa-enquiry">Online Visa Enquiry</a></li>
                        <li class="mb-2"><a href="/portal/login">Customer Portal Login</a></li>
                        <li class="mb-2"><a href="/agent/login">Agent Portal</a></li>
                        <li class="mb-2"><a href="/supplier/login">Supplier Portal</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-4">
                    <h5>Global Headquarters</h5>
                    <p class="small mb-2"><i class="fa-solid fa-location-dot me-2 text-info"></i> Level 14, Business Bay Tower B, Dubai, UAE</p>
                    <p class="small mb-2"><i class="fa-solid fa-phone me-2 text-info"></i> <?= e(\App\Config\Env::get('COMPANY_PHONE', '0585909349')) ?></p>
                    <p class="small mb-3"><i class="fa-solid fa-envelope me-2 text-info"></i> <?= e(\App\Config\Env::get('COMPANY_EMAIL', 'mstravelu@gmail.com')) ?></p>
                    <span class="badge bg-secondary text-white small"><i class="fa-solid fa-shield-halved me-1"></i> ISO 27001 Certified Security</span>
                </div>
            </div>

            <hr class="my-4 border-secondary opacity-25">

            <div class="d-flex flex-wrap justify-content-between align-items-center small text-secondary">
                <div>
                    &copy; <?= date('Y') ?> MS Travel Hub Global Visa Services. All rights reserved.
                </div>
                <div class="d-flex gap-3 mt-2 mt-md-0">
                    <a href="/faq">Privacy Policy</a>
                    <a href="/faq">Terms of Service</a>
                    <a href="/sitemap.xml">XML Sitemap</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/flatpickr.min.js"></script>
    <script src="/assets/js/app-datepicker.js?v=1.0.0"></script>
</body>

</html>
