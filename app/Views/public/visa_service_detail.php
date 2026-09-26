<?php
$pageTitle = e($service['name']) . " — Visa Services";
$metaDescription = "Detailed requirements, processing timeline, documents needed and pricing for " . e($service['name']);
$currentRoute = '/visa-services';

ob_start();
?>

<div class="py-4 bg-dark text-white">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small text-secondary">
                <li class="breadcrumb-item"><a href="/" class="text-info text-decoration-none">Home</a></li>
                <li class="breadcrumb-item"><a href="/visa-services" class="text-info text-decoration-none">Visa Services</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page"><?= e($service['name']) ?></li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-3">
            <span class="fs-1"><?= e($service['flag_emoji'] ?? '🌐') ?></span>
            <div>
                <h1 class="fw-bold fs-2 mb-1"><?= e($service['name']) ?></h1>
                <span class="badge bg-info text-dark fw-bold"><?= e($service['country_name']) ?></span>
                <span class="badge bg-secondary text-white ms-1"><?= e($service['category_name'] ?? 'General') ?></span>
            </div>
        </div>
    </div>
</div>

<div class="py-5">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-8">
                <!-- Package Overview -->
                <div class="card card-custom p-4 mb-4">
                    <h4 class="fw-bold text-dark mb-3"><i class="fa-solid fa-circle-info text-info me-2"></i> Package Specifications</h4>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block">Visa Duration</small>
                                <span class="fw-bold text-dark fs-6"><?= e($service['duration'] ?? 'N/A') ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block">Maximum Allowed Stay</small>
                                <span class="fw-bold text-dark fs-6"><?= e($service['max_stay'] ?? 'N/A') ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block">Entry Type</small>
                                <span class="fw-bold text-dark fs-6"><?= e($service['entry_type'] ?? 'Single Entry') ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block">Estimated Processing Time</small>
                                <span class="fw-bold text-info fs-6"><?= e($service['estimated_days']) ?> Working Days</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mandatory Documents Needed -->
                <div class="card card-custom p-4 mb-4">
                    <h4 class="fw-bold text-dark mb-3"><i class="fa-solid fa-folder-closed text-primary me-2"></i> Required Checklist &amp; Documents</h4>
                    <p class="text-secondary small">Below is the standard documentation required for submission. Our visa team will review and verify every document prior to embassy filing.</p>

                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($docTypes, 0, 7) as $doc): ?>
                            <div class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="fw-semibold mb-0 text-dark"><?= e($doc['name']) ?></h6>
                                    <small class="text-muted"><?= e($doc['description'] ?? 'Standard requirement') ?></small>
                                </div>
                                <?php if ($doc['is_mandatory']): ?>
                                    <span class="badge bg-danger-subtle text-danger fw-semibold">Mandatory</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary fw-semibold">Optional</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Cancellation Policy & Information -->
                <div class="card card-custom p-4">
                    <h5 class="fw-bold text-dark mb-2"><i class="fa-solid fa-shield-halved text-success me-2"></i> Terms &amp; Cancellation Policy</h5>
                    <p class="text-secondary small mb-0"><?= e($service['cancellation_policy'] ?: 'Embassy government fees and biometric processing charges are non-refundable once filed. Service fees subject to company policy.') ?></p>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Pricing & Booking Card -->
                <div class="card card-custom p-4 sticky-top" style="top: 100px;">
                    <h5 class="fw-bold text-dark mb-1">Public Package Fee</h5>
                    <h2 class="fw-bold text-dark mb-3"><?= format_currency((float)$service['selling_price'], $service['currency'] ?? 'USD') ?></h2>

                    <ul class="list-unstyled small text-secondary mb-4">
                        <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Complete Document Verification</li>
                        <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Government Portal Filing</li>
                        <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Real-time 2-Factor SMS/Email Tracking</li>
                        <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Dedicated Visa Specialist Support</li>
                    </ul>

                    <a href="/visa-enquiry?visa_service_id=<?= $service['id'] ?>&destination=<?= urlencode($service['country_name']) ?>" class="btn btn-brand btn-lg w-100 mb-2">
                        <i class="fa-solid fa-paper-plane me-2"></i> Apply / Enquire Now
                    </a>
                    <a href="/track" class="btn btn-outline-secondary w-100 btn-sm">
                        Existing Applicant? Track Here
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/public/layout.php';
