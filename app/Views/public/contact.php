<?php
$pageTitle = "Contact Us — MS Travel Hub Global Visa Services";
$metaDescription = "Get in touch with MS Travel Hub. Contact our Dubai, London, New York and Riyadh branches for visa support.";
$currentRoute = '/contact';

ob_start();
?>

<div class="py-4 bg-dark text-white text-center">
    <div class="container">
        <h1 class="fw-bold fs-2">Contact Global Operations</h1>
        <p class="text-info mb-0">Our visa and recruitment consultants are available 24/7 to assist your application.</p>
    </div>
</div>

<div class="py-5">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-5">
                <h4 class="fw-bold text-dark mb-4">Headquarters Contact Info</h4>

                <div class="card card-custom p-4 mb-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon mb-0" style="width:44px; height:44px;"><i class="fa-solid fa-building"></i></div>
                        <div>
                            <h6 class="fw-bold mb-0">Dubai Head Office</h6>
                            <small class="text-muted">Business Bay, Tower B, Level 14</small>
                        </div>
                    </div>

                    <p class="small text-secondary mb-2"><i class="fa-solid fa-phone text-info me-2"></i> <?= e(\App\Config\Env::get('COMPANY_PHONE', '0585909349')) ?></p>
                    <p class="small text-secondary mb-2"><i class="fa-solid fa-envelope text-info me-2"></i> <?= e(\App\Config\Env::get('COMPANY_EMAIL', 'mstravelu@gmail.com')) ?></p>
                    <p class="small text-secondary mb-0"><i class="fa-solid fa-clock text-info me-2"></i> Monday – Saturday: 9:00 AM – 6:00 PM GST</p>
                </div>

                <h5 class="fw-bold text-dark mb-3">Global Offices</h5>
                <div class="row g-3">
                    <?php foreach ($branches as $b): ?>
                        <div class="col-12">
                            <div class="p-3 bg-light rounded-3">
                                <h6 class="fw-bold mb-1 text-dark"><?= e($b['name']) ?> (<?= e($b['code']) ?>)</h6>
                                <p class="small text-secondary mb-1"><i class="fa-solid fa-location-dot text-danger me-1"></i> <?= e($b['address']) ?>, <?= e($b['city']) ?></p>
                                <p class="small text-secondary mb-0"><i class="fa-solid fa-phone text-info me-1"></i> <?= e($b['phone']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card card-custom p-4 p-md-5">
                    <h4 class="fw-bold text-dark mb-4"><i class="fa-solid fa-envelope-open-text text-primary me-2"></i> Send Us a Message</h4>

                    <form action="/visa-enquiry" method="POST">
                        <?= csrf_field() ?>
                        <input type="text" name="website_hp" style="display:none !important;" tabindex="-1" autocomplete="off">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Your Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" placeholder="e.g. Tariq Mansoor" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" placeholder="name@domain.com" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Phone / Mobile <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" class="form-control" placeholder="+971 50 123 4567" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Subject / Interest</label>
                                <input type="text" name="destination" class="form-control" placeholder="e.g. Dubai Visa, UK Visitor, Job Enquiry">
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold text-secondary">Your Message <span class="text-danger">*</span></label>
                                <textarea name="message" class="form-control" rows="5" placeholder="How can our visa consultants assist you today?" required></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-brand btn-lg w-100 mt-4 fw-semibold">
                            <i class="fa-solid fa-paper-plane me-2"></i> Send Message Now
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/public/layout.php';
