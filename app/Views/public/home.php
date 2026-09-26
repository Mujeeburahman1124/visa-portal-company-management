<?php
$pageTitle = "MS Travel Hub — Global Visa & Recruitment Services";
$metaDescription = "Premier visa processing, recruitment solutions, and travel documentation management. Fast, secure and transparent.";
$currentRoute = '/';

ob_start();
?>

<!-- Hero Banner -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="badge bg-info text-dark px-3 py-2 rounded-pill fw-semibold mb-3">
                    <i class="fa-solid fa-shield-halved me-1"></i> Trusted Visa &amp; Recruitment Management
                </span>
                <h1 class="hero-title mb-4">
                    Simplified Visa Processing &amp; <span>Global Manpower</span> Solutions.
                </h1>
                <p class="lead text-slate-300 mb-4" style="color: #cbd5e1; font-size: 1.15rem;">
                    Fast-track your visa applications for UAE, UK, USA, Schengen, Canada and Saudi Arabia. Track progress in real-time with enterprise 2-factor security.
                </p>

                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="/visa-services" class="btn btn-brand btn-lg shadow-sm">
                        <i class="fa-solid fa-compass me-2"></i> Explore Visa Packages
                    </a>
                    <a href="/jobs" class="btn btn-outline-light btn-lg">
                        <i class="fa-solid fa-briefcase me-2"></i> Browse Jobs
                    </a>
                </div>

                <div class="d-flex align-items-center gap-4 pt-3 border-top border-secondary border-opacity-25">
                    <div>
                        <h4 class="fw-bold mb-0 text-white"><?= e($stats['visas_processed'] ?? '48,000+') ?></h4>
                        <small class="text-secondary">Visas Approved</small>
                    </div>
                    <div class="vr bg-secondary opacity-25" style="height: 35px;"></div>
                    <div>
                        <h4 class="fw-bold mb-0 text-info"><?= e($stats['success_rate'] ?? '99.4%') ?></h4>
                        <small class="text-secondary">Approval Accuracy</small>
                    </div>
                    <div class="vr bg-secondary opacity-25" style="height: 35px;"></div>
                    <div>
                        <h4 class="fw-bold mb-0 text-white"><?= e($stats['global_branches'] ?? '4') ?> Branches</h4>
                        <small class="text-secondary">Global Offices</small>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <!-- 2-Factor Quick Tracking Box -->
                <div class="card p-4 shadow-lg border-0 rounded-4 text-dark" style="background: #ffffff;">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="feature-icon mb-0" style="width:40px; height:40px; font-size:1.1rem;">
                            <i class="fa-solid fa-magnifying-glass-location"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0">Track Visa Status</h5>
                            <small class="text-muted">Instant 2-Factor Verification</small>
                        </div>
                    </div>

                    <form action="/track" method="POST">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Application Reference Number</label>
                            <input type="text" name="application_number" class="form-control" placeholder="e.g. MSV-2026-8841" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Passport Number</label>
                            <input type="text" name="passport_number" class="form-control" placeholder="e.g. A9821034" required>
                        </div>

                        <button type="submit" class="btn btn-brand w-100 fw-semibold py-2.5">
                            <i class="fa-solid fa-shield-halved me-2"></i> Track Progress Now
                        </button>
                    </form>

                    <div class="text-center mt-3 small text-muted">
                        Need assistance? <a href="/contact" class="text-decoration-none">Contact Helpline</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Active Visa Packages Preview -->
<section class="py-5 bg-light">
    <div class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
            <div>
                <span class="text-info text-uppercase fw-bold small">Popular Destinations</span>
                <h2 class="fw-bold text-dark">Featured Visa Processing Services</h2>
            </div>
            <a href="/visa-services" class="btn btn-outline-brand btn-sm">View All Packages &rarr;</a>
        </div>

        <div class="row g-4">
            <?php foreach ($services as $srv): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card card-custom h-100 p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="fs-2"><?= e($srv['flag_emoji'] ?? '🌐') ?></span>
                            <span class="badge bg-primary-subtle text-primary fw-semibold px-2.5 py-1">
                                <?= e($srv['entry_type'] ?? 'Single Entry') ?>
                            </span>
                        </div>

                        <h5 class="fw-bold text-dark mb-1"><?= e($srv['name']) ?></h5>
                        <p class="text-secondary small mb-3"><?= e($srv['country_name']) ?> &bull; <?= e($srv['category_name'] ?? 'Visa') ?></p>

                        <div class="p-3 bg-light rounded-3 mb-3 small">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Duration:</span>
                                <span class="fw-semibold"><?= e($srv['duration'] ?? 'N/A') ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Est. Processing:</span>
                                <span class="fw-semibold text-info"><?= e($srv['estimated_days']) ?> Working Days</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Validity:</span>
                                <span class="fw-semibold"><?= e($srv['validity'] ?? 'Standard') ?></span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                            <div>
                                <small class="text-muted d-block">Public Price</small>
                                <span class="fw-bold text-dark fs-5"><?= format_currency((float)$srv['selling_price'], $srv['currency'] ?? 'USD') ?></span>
                            </div>
                            <a href="/visa-service?id=<?= $srv['id'] ?>" class="btn btn-sm btn-outline-brand">
                                Details &amp; Apply
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Recruitment Jobs Preview -->
<?php if (!empty($jobs)): ?>
<section class="py-5">
    <div class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
            <div>
                <span class="text-info text-uppercase fw-bold small">Global Recruitment</span>
                <h2 class="fw-bold text-dark">Latest Open Job Vacancies</h2>
            </div>
            <a href="/jobs" class="btn btn-outline-brand btn-sm">Browse All Jobs &rarr;</a>
        </div>

        <div class="row g-4">
            <?php foreach ($jobs as $job): ?>
                <div class="col-lg-6">
                    <div class="card card-custom p-4 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="fw-bold mb-1 text-dark"><?= e($job['job_title']) ?></h5>
                                <span class="text-secondary small">
                                    <i class="fa-solid fa-location-dot me-1 text-danger"></i> <?= e($job['location']) ?> &bull; <?= e($job['category']) ?>
                                </span>
                            </div>
                            <span class="badge bg-success-subtle text-success fw-semibold px-2.5 py-1">
                                <?= e($job['vacancies']) ?> Vacancies
                            </span>
                        </div>

                        <p class="text-muted small my-3 line-clamp-2">
                            <?= e(substr($job['description'], 0, 150)) ?>...
                        </p>

                        <div class="d-flex flex-wrap justify-content-between align-items-center pt-3 border-top mt-auto">
                            <div>
                                <small class="text-muted d-block">Salary Offer</small>
                                <span class="fw-bold text-success fs-6">
                                    <?= number_format((float)$job['salary_min']) ?> - <?= number_format((float)$job['salary_max']) ?> <?= e($job['currency']) ?> / mo
                                </span>
                            </div>
                            <a href="/jobs/apply?job_id=<?= $job['id'] ?>" class="btn btn-brand btn-sm">
                                Apply Now &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Why Choose Us -->
<section class="py-5 bg-light">
    <div class="container py-4">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="text-info text-uppercase fw-bold small">Enterprise Excellence</span>
            <h2 class="fw-bold text-dark">Why Process With MS Travel Hub?</h2>
            <p class="text-secondary">Direct embassy coordination, strict privacy standards, and end-to-end digital tracking.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-custom p-4 h-100 text-center">
                    <div class="feature-icon mx-auto">
                        <i class="fa-solid fa-shield-check"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Secure Document Vault</h5>
                    <p class="text-secondary small">Your passport scans, national IDs and bank statements are stored in encrypted non-public storage with strict access controls.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-custom p-4 h-100 text-center">
                    <div class="feature-icon mx-auto">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Real-Time Stage Updates</h5>
                    <p class="text-secondary small">Instant SMS and Email notifications every time your application progresses from review to embassy submission and visa issuance.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-custom p-4 h-100 text-center">
                    <div class="feature-icon mx-auto">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Direct Channel Authority</h5>
                    <p class="text-secondary small">Authorized submissions for UAE GDRFA/ICP, UKVI, US DS-160, Schengen TLS/VFS, and Saudi MOFA portals.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call To Action -->
<section class="py-5 bg-dark text-white text-center">
    <div class="container py-3">
        <h2 class="fw-bold mb-3">Ready to Begin Your Visa or Job Application?</h2>
        <p class="lead text-secondary mb-4" style="color: #cbd5e1;">Get expert guidance from our visa consultants or submit an online enquiry in under 2 minutes.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="/visa-enquiry" class="btn btn-brand btn-lg">Submit Visa Enquiry</a>
            <a href="/contact" class="btn btn-outline-light btn-lg">Contact Us</a>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/public/layout.php';
