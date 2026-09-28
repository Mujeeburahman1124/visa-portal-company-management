<?php
$pageTitle = "MS Travel Hub â€” Global Visa & Recruitment Services";
$metaDescription = "Premier visa processing, recruitment solutions, and travel documentation management for UAE, UK, USA, Schengen, Canada and Saudi Arabia. Fast, secure and transparent.";
$currentRoute = '/';

ob_start();
?>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• HERO â•â• -->
<section class="pub-hero">
    <div class="container">
        <div class="row align-items-center g-5">

            <!-- Left: copy -->
            <div class="col-lg-7">
                <div class="pub-hero-eyebrow">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                    Trusted Visa &amp; Recruitment Management
                </div>

                <h1 class="pub-hero-title">
                    Simplified Visa Processing &amp; <span class="highlight">Global Manpower</span> Solutions.
                </h1>

                <p class="pub-hero-lead">
                    Fast-track your visa applications for UAE, UK, USA, Schengen, Canada and Saudi Arabia. Track progress in real-time with enterprise 2-factor security.
                </p>

                <div class="pub-hero-btns">
                    <a href="/visa-services" class="btn-brand">
                        <i class="fa-solid fa-compass" aria-hidden="true"></i> Explore Visa Packages
                    </a>
                    <a href="/jobs" class="btn-light-outline">
                        <i class="fa-solid fa-briefcase" aria-hidden="true"></i> Browse Jobs
                    </a>
                </div>

                <!-- Trust stats strip -->
                <div class="pub-hero-stats">
                    <div>
                        <div class="pub-hero-stat-value"><?= e($stats['visas_processed'] ?? '48,000+') ?></div>
                        <div class="pub-hero-stat-label">Visas Approved</div>
                    </div>
                    <div class="pub-hero-divider" aria-hidden="true"></div>
                    <div>
                        <div class="pub-hero-stat-value accent"><?= e($stats['success_rate'] ?? '99.4%') ?></div>
                        <div class="pub-hero-stat-label">Approval Accuracy</div>
                    </div>
                    <div class="pub-hero-divider" aria-hidden="true"></div>
                    <div>
                        <div class="pub-hero-stat-value"><?= e($stats['global_branches'] ?? '4') ?> Branches</div>
                        <div class="pub-hero-stat-label">Global Offices</div>
                    </div>
                </div>
            </div>

            <!-- Right: 2-Factor Track Card -->
            <div class="col-lg-5">
                <div class="pub-track-card">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="pub-track-card-icon">
                            <i class="fa-solid fa-magnifying-glass-location" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h2 class="fw-bold mb-0" style="font-size:1.1rem;color:var(--color-heading)">Track Visa Status</h2>
                            <small style="color:var(--color-text-muted)">Instant 2-Factor Verification</small>
                        </div>
                    </div>

                    <form action="/track" method="POST" novalidate>
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="application_number" class="form-label">Application Reference Number</label>
                            <input type="text" id="application_number" name="application_number"
                                   class="form-control" placeholder="e.g. MSV-2026-8841" required
                                   autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label for="passport_number" class="form-label">Passport Number</label>
                            <input type="text" id="passport_number" name="passport_number"
                                   class="form-control" placeholder="e.g. A9821034" required
                                   autocomplete="off">
                        </div>
                        <button type="submit" class="btn-brand w-100 justify-content-center" style="padding:13px">
                            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Track Progress Now
                        </button>
                    </form>

                    <p class="text-center mt-3 mb-0" style="font-size:0.82rem;color:var(--color-text-muted)">
                        Need assistance? <a href="/contact" style="color:var(--color-primary)">Contact Helpline</a>
                    </p>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• COUNTRY DESTINATIONS â•â• -->
<section class="py-4" style="background:var(--color-surface);border-bottom:1px solid var(--color-border)">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <?php
            $destinations = [
                ['flag'=>'ðŸ‡¦ðŸ‡ª','name'=>'UAE'],['flag'=>'ðŸ‡¬ðŸ‡§','name'=>'UK'],
                ['flag'=>'ðŸ‡ºðŸ‡¸','name'=>'USA'],['flag'=>'ðŸ‡©ðŸ‡ª','name'=>'Schengen'],
                ['flag'=>'ðŸ‡¨ðŸ‡¦','name'=>'Canada'],['flag'=>'ðŸ‡¸ðŸ‡¦','name'=>'Saudi Arabia'],
                ['flag'=>'ðŸ‡¦ðŸ‡º','name'=>'Australia'],['flag'=>'ðŸ‡³ðŸ‡¿','name'=>'New Zealand'],
            ];
            foreach($destinations as $d):
            ?>
            <a href="/visa-services?search=<?= urlencode($d['name']) ?>" class="pub-country-pill">
                <?= $d['flag'] ?> <?= e($d['name']) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• FEATURED VISA SERVICES â•â• -->
<section class="py-5" style="background:var(--color-surface-alt)">
    <div class="container py-3">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-5">
            <div>
                <span class="pub-section-eyebrow">Popular Destinations</span>
                <h2 class="pub-section-title mb-0">Featured Visa Processing Services</h2>
            </div>
            <a href="/visa-services" class="btn-outline-brand">View All Packages &rarr;</a>
        </div>

        <div class="row g-4">
            <?php foreach ($services as $srv): ?>
            <div class="col-lg-4 col-md-6">
                <div class="pub-service-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="pub-service-flag" aria-label="<?= e($srv['country_name'] ?? '') ?> flag"><?= e($srv['flag_emoji'] ?? 'ðŸŒ') ?></span>
                        <span class="pub-service-badge"><?= e($srv['entry_type'] ?? 'Single Entry') ?></span>
                    </div>

                    <h3 style="font-size:1rem;font-weight:700;color:var(--color-heading);margin-bottom:4px"><?= e($srv['name']) ?></h3>
                    <p style="font-size:0.83rem;color:var(--color-text-muted);margin-bottom:0"><?= e($srv['country_name']) ?> &bull; <?= e($srv['category_name'] ?? 'Visa') ?></p>

                    <div class="pub-service-meta">
                        <div class="pub-service-meta-row">
                            <span>Duration:</span>
                            <strong><?= e($srv['duration'] ?? 'N/A') ?></strong>
                        </div>
                        <div class="pub-service-meta-row">
                            <span>Est. Processing:</span>
                            <strong class="pub-service-meta-value-accent"><?= e($srv['estimated_days']) ?> Working Days</strong>
                        </div>
                        <div class="pub-service-meta-row">
                            <span>Validity:</span>
                            <strong><?= e($srv['validity'] ?? 'Standard') ?></strong>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-auto pt-2" style="border-top:1px solid var(--color-border)">
                        <div>
                            <small style="color:var(--color-text-muted);display:block">Public Price</small>
                            <span class="pub-service-price"><?= format_currency((float)$srv['selling_price'], $srv['currency'] ?? 'USD') ?></span>
                        </div>
                        <a href="/visa-service?id=<?= (int)$srv['id'] ?>" class="btn-outline-brand" style="padding:7px 16px;font-size:0.84rem">
                            Details &amp; Apply
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• TRUST STATS STRIP â•â• -->
<section class="pub-stats-strip">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-6 col-md-3">
                <div class="pub-stat-value"><?= e($stats['visas_processed'] ?? '48,000+') ?></div>
                <div class="pub-stat-label">Visas Approved</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="pub-stat-value"><?= e($stats['success_rate'] ?? '99.4%') ?></div>
                <div class="pub-stat-label">Approval Rate</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="pub-stat-value"><?= e($stats['global_branches'] ?? '4') ?>+</div>
                <div class="pub-stat-label">Global Branches</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="pub-stat-value">120+</div>
                <div class="pub-stat-label">Destinations Served</div>
            </div>
        </div>
    </div>
</section>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• HOW IT WORKS â•â• -->
<section class="py-5" style="background:var(--color-surface)">
    <div class="container py-3">
        <div class="text-center mb-5">
            <span class="pub-section-eyebrow">Simple Process</span>
            <h2 class="pub-section-title mx-auto">How It Works in 4 Easy Steps</h2>
        </div>
        <div class="row g-4 text-center">
            <?php
            $steps = [
                ['n'=>'1','icon'=>'fa-file-signature','title'=>'Submit Enquiry','desc'=>'Fill our simple online form or call us directly. No obligation, free initial consultation.'],
                ['n'=>'2','icon'=>'fa-clipboard-check','title'=>'Document Review','desc'=>'Our experts review your documents and advise on the best visa category for your profile.'],
                ['n'=>'3','icon'=>'fa-building-columns','title'=>'Embassy Submission','desc'=>'We prepare and submit your application to the relevant embassy or immigration authority.'],
                ['n'=>'4','icon'=>'fa-stamp','title'=>'Visa Approved','desc'=>'Receive your visa notification. Track every stage in real-time via your applicant portal.'],
            ];
            $count = count($steps);
            foreach($steps as $i => $step):
            ?>
            <div class="col-md-3 col-sm-6">
                <div class="pub-process-step position-relative">
                    <div class="pub-process-number"><?= $step['n'] ?></div>
                    <?php if ($i < $count - 1): ?>
                    <div class="pub-process-connector d-none d-md-block"></div>
                    <?php endif; ?>
                    <div class="feature-icon mx-auto mb-3">
                        <i class="fa-solid <?= $step['icon'] ?>" aria-hidden="true"></i>
                    </div>
                    <h3 style="font-size:1rem;font-weight:700;color:var(--color-heading);margin-bottom:8px"><?= $step['title'] ?></h3>
                    <p style="font-size:0.85rem;color:var(--color-text-muted);max-width:220px;margin:0 auto"><?= $step['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• JOB VACANCIES PREVIEW â•â• -->
<?php if (!empty($jobs)): ?>
<section class="py-5" style="background:var(--color-surface-alt)">
    <div class="container py-3">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-5">
            <div>
                <span class="pub-section-eyebrow">Global Recruitment</span>
                <h2 class="pub-section-title mb-0">Latest Open Job Vacancies</h2>
            </div>
            <a href="/jobs" class="btn-outline-brand">Browse All Jobs &rarr;</a>
        </div>

        <div class="row g-4">
            <?php foreach ($jobs as $job): ?>
            <div class="col-lg-6">
                <div class="pub-job-card">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <h3 style="font-size:1.02rem;font-weight:700;color:var(--color-heading);margin-bottom:5px"><?= e($job['job_title']) ?></h3>
                            <span style="font-size:0.82rem;color:var(--color-text-muted)">
                                <i class="fa-solid fa-location-dot me-1" style="color:var(--color-danger)" aria-hidden="true"></i>
                                <?= e($job['location']) ?> &bull; <?= e($job['category']) ?>
                            </span>
                        </div>
                        <span class="pub-job-tag flex-shrink-0">
                            <?= e($job['vacancies']) ?> Vacancies
                        </span>
                    </div>

                    <p style="font-size:0.87rem;color:var(--color-text-muted);margin-bottom:16px;-webkit-line-clamp:2;display:-webkit-box;-webkit-box-orient:vertical;overflow:hidden">
                        <?= e(substr($job['description'], 0, 150)) ?>...
                    </p>

                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-auto pt-3" style="border-top:1px solid var(--color-border)">
                        <div>
                            <small style="color:var(--color-text-muted);display:block">Salary Offer</small>
                            <span class="pub-job-salary">
                                <?= number_format((float)$job['salary_min']) ?> â€“ <?= number_format((float)$job['salary_max']) ?> <?= e($job['currency']) ?>/mo
                            </span>
                        </div>
                        <a href="/jobs/apply?job_id=<?= (int)$job['id'] ?>" class="btn-brand" style="padding:8px 18px;font-size:0.87rem">
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

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• WHY CHOOSE US â•â• -->
<section class="py-5" style="background:var(--color-surface)">
    <div class="container py-3">
        <div class="text-center mb-5">
            <span class="pub-section-eyebrow">Enterprise Excellence</span>
            <h2 class="pub-section-title mx-auto">Why Process With MS Travel Hub?</h2>
            <p class="pub-section-lead mx-auto text-center">Direct embassy coordination, strict privacy standards, and end-to-end digital tracking.</p>
        </div>
        <div class="row g-4">
            <?php
            $features = [
                ['icon'=>'fa-shield-check','title'=>'Secure Document Vault','desc'=>'Your passport scans, national IDs and bank statements are stored in encrypted non-public storage with strict access controls.'],
                ['icon'=>'fa-clock-rotate-left','title'=>'Real-Time Stage Updates','desc'=>'Instant SMS and Email notifications every time your application progresses from review to embassy submission and visa issuance.'],
                ['icon'=>'fa-building-columns','title'=>'Direct Channel Authority','desc'=>'Authorized submissions for UAE GDRFA/ICP, UKVI, US DS-160, Schengen TLS/VFS, and Saudi MOFA portals.'],
                ['icon'=>'fa-user-tie','title'=>'Expert Visa Consultants','desc'=>'Our licensed consultants average 8+ years in international immigration, providing guidance tailored to your specific situation.'],
                ['icon'=>'fa-globe','title'=>'120+ Destinations','desc'=>'We handle visa processing for over 120 countries including tourist, work, student, investor, and residency categories.'],
                ['icon'=>'fa-bolt','title'=>'Express Processing','desc'=>'Urgent applications handled within 24â€“48 hours with dedicated processing lanes for premium clients.'],
            ];
            foreach($features as $feat):
            ?>
            <div class="col-md-4 col-sm-6">
                <div class="pub-feature-box">
                    <div class="feature-icon mb-3">
                        <i class="fa-solid <?= $feat['icon'] ?>" aria-hidden="true"></i>
                    </div>
                    <h3 style="font-size:1rem;font-weight:700;color:var(--color-heading);margin-bottom:10px"><?= $feat['title'] ?></h3>
                    <p style="font-size:0.87rem;color:var(--color-text-muted);margin:0;line-height:1.7"><?= $feat['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• CALL TO ACTION â•â• -->
<section class="pub-cta-section">
    <div class="container text-center position-relative" style="z-index:1">
        <span class="pub-hero-eyebrow" style="margin-bottom:16px">Start Today</span>
        <h2 class="mb-3">Ready to Begin Your Visa or Job Application?</h2>
        <p class="mb-5" style="color:rgba(255,255,255,0.75);font-size:1.05rem;max-width:520px;margin-left:auto;margin-right:auto">
            Get expert guidance from our visa consultants or submit an online enquiry in under 2 minutes.
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="/visa-enquiry" class="btn-brand">
                <i class="fa-solid fa-rocket" aria-hidden="true"></i> Submit Visa Enquiry
            </a>
            <a href="/contact" class="btn-light-outline">
                <i class="fa-solid fa-envelope" aria-hidden="true"></i> Contact Us
            </a>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/public/layout.php';
