<?php
$pageTitle = "About Us — MS Travel Hub Global Visa Services";
$metaDescription = "Learn about MS Travel Hub Global Visa Services. Licensed international visa processing, recruitment, and document management agency.";
$currentRoute = '/about';

ob_start();
?>

<div class="py-5 bg-dark text-white text-center">
    <div class="container">
        <h1 class="fw-bold display-5">About MS Travel Hub</h1>
        <p class="lead text-info">Licensed Global Visa Consultancy &amp; International Recruitment Agency</p>
    </div>
</div>

<div class="py-5">
    <div class="container">
        <div class="row align-items-center g-5 mb-5">
            <div class="col-lg-6">
                <span class="text-info fw-bold text-uppercase small">Our Organization</span>
                <h2 class="fw-bold text-dark mb-4">Empowering Seamless Global Mobility</h2>
                <p class="text-secondary">
                    Founded with a mission to streamline international visa processing and corporate recruitment, MS Travel Hub operates across key financial hubs including Dubai, London, New York, and Riyadh.
                </p>
                <p class="text-secondary">
                    Our team of certified visa consultants, processing specialists, and legal experts handle over 40,000 applications annually with a verified 99.4% approval accuracy. We work directly with government portals, embassies, and consulate submission centers worldwide.
                </p>
            </div>
            <div class="col-lg-6">
                <div class="card card-custom p-4 bg-light">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-award me-2 text-warning"></i> Key Operational Highlights</h5>
                    <ul class="list-unstyled mb-0">
                        <li class="py-2 border-bottom d-flex justify-content-between">
                            <span class="text-secondary">Official Registrations:</span>
                            <span class="fw-semibold">GDRFA, ICP, UKVI, MOFA</span>
                        </li>
                        <li class="py-2 border-bottom d-flex justify-content-between">
                            <span class="text-secondary">Security Certification:</span>
                            <span class="fw-semibold">ISO 27001 Data Privacy</span>
                        </li>
                        <li class="py-2 border-bottom d-flex justify-content-between">
                            <span class="text-secondary">Active Countries Covered:</span>
                            <span class="fw-semibold">50+ Worldwide</span>
                        </li>
                        <li class="py-2 d-flex justify-content-between">
                            <span class="text-secondary">Corporate Clients:</span>
                            <span class="fw-semibold">500+ Enterprise Partners</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <h3 class="fw-bold text-dark mb-4">Our Global Branch Network</h3>
        <div class="row g-4">
            <?php foreach ($branches as $b): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card card-custom p-4 h-100">
                        <span class="badge bg-secondary-subtle text-secondary w-fit mb-2 fw-bold"><?= e($b['code']) ?></span>
                        <h5 class="fw-bold text-dark mb-1"><?= e($b['name']) ?></h5>
                        <p class="text-secondary small mb-3"><?= e($b['city']) ?>, <?= e($b['country']) ?></p>
                        <p class="small text-muted mb-2"><i class="fa-solid fa-location-dot me-1 text-danger"></i> <?= e($b['address']) ?></p>
                        <p class="small text-muted mb-0"><i class="fa-solid fa-phone me-1 text-info"></i> <?= e($b['phone']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/public/layout.php';
