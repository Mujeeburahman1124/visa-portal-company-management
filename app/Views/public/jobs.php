<?php
$pageTitle = "Global Job Opportunities — MS Travel Hub Recruitment";
$metaDescription = "Explore overseas job opportunities in Dubai, Riyadh, London and worldwide. Apply online directly.";
$currentRoute = '/jobs';

ob_start();
?>

<div class="py-4 bg-dark text-white">
    <div class="container">
        <h1 class="fw-bold fs-2">Global Recruitment &amp; Career Opportunities</h1>
        <p class="text-info mb-0">Verified job vacancies with visa sponsorship, flight tickets, and corporate benefits.</p>
    </div>
</div>

<div class="py-5">
    <div class="container">
        <!-- Search & Category Filters -->
        <div class="card p-3 mb-4 border-0 shadow-sm bg-light">
            <form action="/jobs" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Search by job title, location or keywords..." value="<?= e($_GET['search'] ?? '') ?>">
                </div>

                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="">All Job Categories</option>
                        <option value="Hospitality" <?= ($_GET['category'] ?? '') === 'Hospitality' ? 'selected' : '' ?>>Hospitality &amp; Hotel</option>
                        <option value="Engineering" <?= ($_GET['category'] ?? '') === 'Engineering' ? 'selected' : '' ?>>Engineering &amp; Construction</option>
                        <option value="Customer Service" <?= ($_GET['category'] ?? '') === 'Customer Service' ? 'selected' : '' ?>>Customer Service &amp; Corporate</option>
                        <option value="Logistics" <?= ($_GET['category'] ?? '') === 'Logistics' ? 'selected' : '' ?>>Logistics &amp; Transport</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="country_id" class="form-select">
                        <option value="">All Countries</option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (int)($_GET['country_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-brand w-100">
                        <i class="fa-solid fa-filter me-1"></i> Search Jobs
                    </button>
                </div>
            </form>
        </div>

        <!-- Jobs List -->
        <?php if (empty($jobs)): ?>
            <div class="text-center py-5">
                <i class="fa-solid fa-briefcase text-muted fs-1 mb-3"></i>
                <h5>No published job openings found matching your query</h5>
                <p class="text-secondary">Submit your CV for general candidate consideration.</p>
                <a href="/jobs/apply" class="btn btn-brand btn-sm">Submit General Application</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($jobs as $j): ?>
                    <div class="col-lg-6">
                        <div class="card card-custom p-4 h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h5 class="fw-bold mb-1 text-dark"><?= e($j['job_title']) ?></h5>
                                    <span class="text-secondary small">
                                        <i class="fa-solid fa-location-dot me-1 text-danger"></i> <?= e($j['location']) ?> &bull; <?= e($j['category']) ?>
                                    </span>
                                </div>
                                <span class="badge bg-success-subtle text-success fw-semibold px-2.5 py-1">
                                    <?= e($j['vacancies']) ?> Vacancies
                                </span>
                            </div>

                            <p class="text-muted small my-3">
                                <?= e(substr($j['description'], 0, 160)) ?>...
                            </p>

                            <div class="p-3 bg-light rounded-3 mb-3 small">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Salary Offer:</span>
                                    <span class="fw-bold text-success"><?= number_format((float)$j['salary_min']) ?> - <?= number_format((float)$j['salary_max']) ?> <?= e($j['currency']) ?> / mo</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Duty Hours:</span>
                                    <span class="fw-semibold"><?= e($j['duty_hours'] ?? 'Standard') ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Experience:</span>
                                    <span class="fw-semibold"><?= e($j['experience_required'] ?? '1+ Year') ?></span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                                <a href="/job?id=<?= $j['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                    View Full Details
                                </a>
                                <a href="/jobs/apply?job_id=<?= $j['id'] ?>" class="btn btn-sm btn-brand">
                                    Apply Online &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/public/layout.php';
