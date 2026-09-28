<?php
$pageTitle = "Visa Services & Processing Packages â€” MS Travel Hub";
$metaDescription = "Browse international visa processing packages for UAE, UK, USA, Schengen, Canada and Saudi Arabia with public pricing and clear timeline.";
$currentRoute = '/visa-services';

ob_start();
?>

<!-- Page Header -->
<div class="pub-page-header">
    <div class="container">
        <h1>Visa Processing Services Catalog</h1>
        <p>Select your destination country or visa category to explore pricing and processing requirements.</p>
        <nav class="pub-breadcrumb" aria-label="Breadcrumb">
            <a href="/"><i class="fa-solid fa-house" aria-hidden="true"></i> Home</a>
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            <span>Visa Services</span>
        </nav>
    </div>
</div>

<div class="py-5" style="background:var(--color-background)">
    <div class="container py-2">

        <!-- Filter Bar -->
        <div class="pub-form-card mb-5" style="border-radius:var(--border-radius-lg);padding:20px 24px">
            <form action="/visa-services" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="vs_search" class="form-label">Search</label>
                    <input type="text" id="vs_search" name="search" class="form-control"
                           placeholder="Service name or country..." value="<?= e($_GET['search'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label for="vs_country" class="form-label">Destination Country</label>
                    <select id="vs_country" name="country_id" class="form-select">
                        <option value="">All Countries</option>
                        <?php foreach ($countries as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (int)($_GET['country_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
                            <?= e($c['flag_emoji']) ?> <?= e($c['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="vs_cat" class="form-label">Category</label>
                    <select id="vs_cat" name="category_id" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (int)($_GET['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn-brand w-100 justify-content-center">
                        <i class="fa-solid fa-filter" aria-hidden="true"></i> Filter
                    </button>
                    <?php if (!empty($_GET['search']) || !empty($_GET['country_id']) || !empty($_GET['category_id'])): ?>
                    <a href="/visa-services" class="btn-outline-brand flex-shrink-0" title="Reset filters">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Services Grid -->
        <?php if (empty($services)): ?>
        <div class="text-center py-5">
            <i class="fa-solid fa-folder-open fs-1 mb-3" style="color:var(--color-text-muted)" aria-hidden="true"></i>
            <h2 style="font-size:1.2rem;color:var(--color-heading)">No visa service packages found</h2>
            <p style="color:var(--color-text-muted)">Try adjusting your filters or search terms.</p>
            <a href="/visa-services" class="btn-outline-brand">Reset Filters</a>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($services as $srv): ?>
            <div class="col-lg-4 col-md-6">
                <div class="pub-service-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="pub-service-flag" aria-label="<?= e($srv['country_name'] ?? '') ?> flag"><?= e($srv['flag_emoji'] ?? 'ðŸŒ') ?></span>
                        <span class="pub-service-badge"><?= e($srv['entry_type'] ?? 'Single Entry') ?></span>
                    </div>

                    <h2 style="font-size:1rem;font-weight:700;color:var(--color-heading);margin-bottom:4px"><?= e($srv['name']) ?></h2>
                    <p style="font-size:0.83rem;color:var(--color-text-muted);margin-bottom:0"><?= e($srv['country_name']) ?> &bull; <?= e($srv['category_name'] ?? 'Visa') ?></p>

                    <div class="pub-service-meta">
                        <div class="pub-service-meta-row">
                            <span>Stay Duration:</span>
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
