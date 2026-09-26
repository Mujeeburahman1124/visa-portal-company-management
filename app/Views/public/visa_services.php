<?php
$pageTitle = "Visa Services & Processing Packages — MS Travel Hub";
$metaDescription = "Browse international visa processing packages for UAE, UK, USA, Schengen, Canada and Saudi Arabia with public pricing and clear timeline.";
$currentRoute = '/visa-services';

ob_start();
?>

<div class="py-4 bg-dark text-white">
    <div class="container">
        <h1 class="fw-bold fs-2">Visa Processing Services Catalog</h1>
        <p class="text-info mb-0">Select your destination country or visa category to explore pricing and processing requirements.</p>
    </div>
</div>

<div class="py-5">
    <div class="container">
        <!-- Search & Filter Bar -->
        <div class="card p-3 mb-4 border-0 shadow-sm bg-light">
            <form action="/visa-services" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by service name or country..." value="<?= e($_GET['search'] ?? '') ?>">
                </div>

                <div class="col-md-3">
                    <select name="country_id" class="form-select">
                        <option value="">All Destination Countries</option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (int)($_GET['country_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
                                <?= e($c['flag_emoji']) ?> <?= e($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <select name="category_id" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= (int)($_GET['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-brand w-100">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Services Grid -->
        <?php if (empty($services)): ?>
            <div class="text-center py-5">
                <i class="fa-solid fa-folder-open text-muted fs-1 mb-3"></i>
                <h5>No visa service packages found</h5>
                <p class="text-secondary">Try adjusting your filters or search terms.</p>
                <a href="/visa-services" class="btn btn-outline-secondary btn-sm">Reset Filters</a>
            </div>
        <?php else: ?>
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
                                    <span class="text-muted">Stay Duration:</span>
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
                                    View Requirements &rarr;
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
