<?php
$pageTitle = e($job['job_title']) . " — Overseas Vacancy";
$metaDescription = "Job vacancy details for " . e($job['job_title']) . " in " . e($job['location']) . ". Apply online.";
$currentRoute = '/jobs';

ob_start();
?>

<div class="py-4 bg-dark text-white">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small text-secondary">
                <li class="breadcrumb-item"><a href="/" class="text-info text-decoration-none">Home</a></li>
                <li class="breadcrumb-item"><a href="/jobs" class="text-info text-decoration-none">Jobs</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page"><?= e($job['job_title']) ?></li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="fw-bold fs-2 mb-1"><?= e($job['job_title']) ?></h1>
                <span class="badge bg-info text-dark fw-semibold"><i class="fa-solid fa-location-dot me-1"></i> <?= e($job['location']) ?></span>
                <span class="badge bg-secondary text-white ms-1"><?= e($job['category']) ?></span>
            </div>
            <a href="/jobs/apply?job_id=<?= $job['id'] ?>" class="btn btn-brand btn-lg">Apply Now &rarr;</a>
        </div>
    </div>
</div>

<div class="py-5">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-8">
                <!-- Overview -->
                <div class="card card-custom p-4 mb-4">
                    <h4 class="fw-bold text-dark mb-3"><i class="fa-solid fa-circle-info text-info me-2"></i> Role Description</h4>
                    <p class="text-secondary" style="white-space: pre-line;"><?= e($job['description']) ?></p>

                    <h5 class="fw-bold text-dark mt-4 mb-2"><i class="fa-solid fa-list-check text-primary me-2"></i> Candidate Requirements</h5>
                    <p class="text-secondary" style="white-space: pre-line;"><?= e($job['requirements'] ?? 'Standard relevant experience required.') ?></p>
                </div>

                <!-- Benefits Offered -->
                <div class="card card-custom p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-gift text-success me-2"></i> Overseas Employment Benefits</h5>
                    <p class="text-secondary mb-0"><?= e($job['benefits'] ?? 'Competitive salary, visa sponsorship, medical insurance and flight tickets.') ?></p>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-custom p-4 sticky-top" style="top: 100px;">
                    <h5 class="fw-bold text-dark mb-3">Job Summary</h5>

                    <ul class="list-unstyled small mb-4">
                        <li class="py-2 border-bottom d-flex justify-content-between">
                            <span class="text-muted">Salary Package:</span>
                            <span class="fw-bold text-success"><?= number_format((float)$job['salary_min']) ?> - <?= number_format((float)$job['salary_max']) ?> <?= e($job['currency']) ?></span>
                        </li>
                        <li class="py-2 border-bottom d-flex justify-content-between">
                            <span class="text-muted">Location:</span>
                            <span class="fw-semibold text-dark"><?= e($job['location']) ?></span>
                        </li>
                        <li class="py-2 border-bottom d-flex justify-content-between">
                            <span class="text-muted">Duty Hours:</span>
                            <span class="fw-semibold text-dark"><?= e($job['duty_hours']) ?></span>
                        </li>
                        <li class="py-2 border-bottom d-flex justify-content-between">
                            <span class="text-muted">Open Positions:</span>
                            <span class="fw-semibold text-dark"><?= e($job['vacancies']) ?> Vacancies</span>
                        </li>
                        <li class="py-2 d-flex justify-content-between">
                            <span class="text-muted">Experience Needed:</span>
                            <span class="fw-semibold text-dark"><?= e($job['experience_required']) ?></span>
                        </li>
                    </ul>

                    <a href="/jobs/apply?job_id=<?= $job['id'] ?>" class="btn btn-brand btn-lg w-100">
                        Submit Online Application
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/public/layout.php';
