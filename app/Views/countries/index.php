<?php
$pageTitle = 'Destination Countries & Consular Jurisdictions — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
?>

<!-- Link Dedicated Bento CSS -->
<link rel="stylesheet" href="/assets/css/dashboard-bento.css">
<link rel="stylesheet" href="/assets/css/pages/countries-index.css">

<div class="content-body countries-dashboard">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Bento Hero Header -->
  <div class="countries-hero">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
          <h1 class="countries-hero-title mb-0">
            <i class="fa-solid fa-earth-americas text-danger"></i> Destination Countries &amp; Jurisdictions
          </h1>
          <span class="countries-hero-badge">
            <i class="fa-solid fa-passport me-1"></i> Global Registry
          </span>
        </div>
        <p class="countries-hero-desc">
          Worldwide consular clearing jurisdictions, embassies, ISO standards, authorized local currencies, and linked visa service categories.
        </p>
      </div>

      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="/visa-packages" class="btn btn-outline-light btn-sm px-3 shadow-sm rounded-pill fw-semibold">
          <i class="fa-solid fa-box-archive me-1"></i> Visa Packages
        </a>
        <button type="button" class="btn btn-primary btn-sm px-3.5 shadow-sm rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#newCountryModal">
          <i class="fa-solid fa-plus-circle me-1"></i> Add Country
        </button>
      </div>
    </div>
  </div>

  <!-- Bento KPI Metrics Strip -->
  <div class="bento-country-kpi-grid">
    <!-- Card 1: Total Countries -->
    <div class="bento-country-kpi-card" style="--kpi-accent: #E11D48; --kpi-icon-bg: #FFF1F2; --kpi-icon-color: #E11D48;">
      <div class="bento-country-kpi-header">
        <span class="bento-country-kpi-title">Destination Countries</span>
        <div class="bento-country-kpi-icon">
          <i class="fa-solid fa-globe"></i>
        </div>
      </div>
      <div class="bento-country-kpi-value"><?= $totalCountries ?></div>
      <div class="bento-country-kpi-footer">
        <i class="fa-solid fa-map-location-dot text-danger"></i>
        <span>Configured consular destinations</span>
      </div>
    </div>

    <!-- Card 2: Active Jurisdictions -->
    <div class="bento-country-kpi-card" style="--kpi-accent: #10B981; --kpi-icon-bg: #ECFDF5; --kpi-icon-color: #059669;">
      <div class="bento-country-kpi-header">
        <span class="bento-country-kpi-title">Active Jurisdictions</span>
        <div class="bento-country-kpi-icon">
          <i class="fa-solid fa-circle-check"></i>
        </div>
      </div>
      <div class="bento-country-kpi-value text-success"><?= $activeCountries ?></div>
      <div class="bento-country-kpi-footer">
        <i class="fa-solid fa-shield-halved text-success"></i>
        <span>Operational embassy clearing units</span>
      </div>
    </div>

    <!-- Card 3: Configured Packages -->
    <div class="bento-country-kpi-card" style="--kpi-accent: #0284C7; --kpi-icon-bg: #F0F9FF; --kpi-icon-color: #0284C7;">
      <div class="bento-country-kpi-header">
        <span class="bento-country-kpi-title">Visa Packages</span>
        <div class="bento-country-kpi-icon">
          <i class="fa-solid fa-passport"></i>
        </div>
      </div>
      <div class="bento-country-kpi-value text-primary"><?= $totalServices ?></div>
      <div class="bento-country-kpi-footer">
        <i class="fa-solid fa-ticket text-primary"></i>
        <span>Linked catalog offers &amp; tariffs</span>
      </div>
    </div>

    <!-- Card 4: Linked Applications -->
    <div class="bento-country-kpi-card" style="--kpi-accent: #8B5CF6; --kpi-icon-bg: #F5F3FF; --kpi-icon-color: #7C3AED;">
      <div class="bento-country-kpi-header">
        <span class="bento-country-kpi-title">Processed Cases</span>
        <div class="bento-country-kpi-icon">
          <i class="fa-solid fa-folder-tree"></i>
        </div>
      </div>
      <div class="bento-country-kpi-value" style="color: #7C3AED;"><?= $totalApplications ?></div>
      <div class="bento-country-kpi-footer">
        <i class="fa-solid fa-check-double text-purple"></i>
        <span>Total lifetime applications routed</span>
      </div>
    </div>
  </div>

  <!-- Bento Toolbar: Search, Region Filter & View Switcher -->
  <div class="bento-country-toolbar">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <form method="GET" action="/countries" class="d-flex align-items-center gap-2 flex-grow-1 flex-wrap" style="max-width: 650px;">
        <div class="input-group" style="max-width: 380px;">
          <span class="input-group-text bg-white border-end-0 text-muted rounded-start-pill ps-3">
            <i class="fa-solid fa-magnifying-glass"></i>
          </span>
          <input type="text" name="search" class="form-control form-control-sm border-start-0 py-2" placeholder="Search country name, ISO code, currency..." value="<?= e($_GET['search'] ?? '') ?>">
          <button type="submit" class="btn btn-primary btn-sm px-3 rounded-end-pill fw-semibold">Search</button>
        </div>

        <select name="region" class="form-select form-select-sm rounded-pill" style="width: auto; min-width: 150px;" onchange="this.form.submit()">
          <option value="">All Regions</option>
          <?php foreach ($regions as $r): ?>
            <option value="<?= e($r) ?>" <?= ($_GET['region'] ?? '') === $r ? 'selected' : '' ?>><?= e($r) ?></option>
          <?php endforeach; ?>
        </select>

        <?php if (!empty($_GET['search']) || !empty($_GET['region'])): ?>
          <a href="/countries" class="btn btn-light btn-sm border rounded-pill px-2.5" title="Clear Filters">
            <i class="fa-solid fa-rotate-left"></i>
          </a>
        <?php endif; ?>
      </form>

      <!-- 3-View Segmented Switcher -->
      <div class="view-switcher-pill-group" role="group" aria-label="Country View Mode">
        <button type="button" class="country-view-btn <?= $currentView === 'cards' ? 'active' : '' ?>" onclick="switchCountryView('cards')" id="btnCountryViewCards" title="Grid Cards View">
          <i class="fa-solid fa-grip"></i> <span>Cards</span>
        </button>
        <button type="button" class="country-view-btn <?= $currentView === 'table' ? 'active' : '' ?>" onclick="switchCountryView('table')" id="btnCountryViewTable" title="Data Table View">
          <i class="fa-solid fa-table-list"></i> <span>Table</span>
        </button>
        <button type="button" class="country-view-btn <?= $currentView === 'compact' ? 'active' : '' ?>" onclick="switchCountryView('compact')" id="btnCountryViewCompact" title="Compact List View">
          <i class="fa-solid fa-list-ul"></i> <span>List</span>
        </button>
      </div>
    </div>
  </div>

  <!-- ================================================================= -->
  <!-- VIEW OPTION 1: COUNTRY CARDS GRID -->
  <!-- ================================================================= -->
  <div id="countryViewCards" class="country-view-container <?= $currentView === 'cards' ? '' : 'd-none' ?>">
    <div class="row g-3">
      <?php if (empty($countries)): ?>
        <div class="col-12 text-center py-5 text-muted">
          <i class="fa-solid fa-earth-americas fa-2x mb-2 d-block opacity-25"></i>
          No destination countries found matching your criteria.
        </div>
      <?php else: ?>
        <?php foreach ($countries as $c): 
          $isActive = (int)($c['is_active'] ?? 0) === 1;
          $cJson = htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8');
        ?>
          <div class="col-12 col-md-6 col-xl-4">
            <div class="bento-country-card">
              <div>
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <div class="d-flex align-items-center gap-2">
                    <span class="country-flag-display"><?= $c['flag_emoji'] ?: '🌐' ?></span>
                    <span class="iso-code-badge"><?= e($c['iso_code']) ?></span>
                  </div>
                  <span class="badge <?= $isActive ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> fw-bold px-2.5 py-1 rounded-pill" style="font-size: 0.72rem;">
                    <?= $isActive ? 'Active' : 'Suspended' ?>
                  </span>
                </div>

                <h3 class="fw-bold text-dark mb-1 fs-5 text-truncate">
                  <?= e($c['name']) ?>
                </h3>
                <div class="text-muted small mb-3">
                  <i class="fa-solid fa-compass me-1 text-primary"></i> <?= e($c['region'] ?: 'Global') ?>
                  <?php if (!empty($c['currency'])): ?>
                    &bull; <span class="fw-semibold text-dark"><?= e($c['currency']) ?></span>
                  <?php endif; ?>
                </div>

                <div class="p-2.5 bg-light rounded-3 border mb-3 small">
                  <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Clearing Authority:</span>
                    <strong class="text-dark text-truncate" style="max-width: 170px;" title="<?= e($c['embassy_info'] ?: 'Standard Consular') ?>">
                      <?= e($c['embassy_info'] ?: 'Standard Consular') ?>
                    </strong>
                  </div>
                  <?php if (!empty($c['phone_code'])): ?>
                    <div class="d-flex justify-content-between">
                      <span class="text-muted">Phone Code:</span>
                      <span class="fw-semibold text-dark"><?= e($c['phone_code']) ?></span>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="country-metrics-bar">
                  <div>
                    <div class="country-metric-lbl">Visa Packages</div>
                    <div class="country-metric-val text-primary"><?= (int)$c['services_count'] ?></div>
                  </div>
                  <div>
                    <div class="country-metric-lbl">Total Visas</div>
                    <div class="country-metric-val text-success"><?= (int)$c['applications_count'] ?></div>
                  </div>
                </div>
              </div>

              <div class="pt-2 border-top d-flex align-items-center justify-content-between gap-1 flex-wrap mt-2">
                <div class="d-flex align-items-center gap-1">
                  <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2.5 rounded-pill fw-semibold" onclick="openEditCountryModal(<?= $cJson ?>)">
                    <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                  </button>
                  <a href="/visa-packages?country=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary py-1 px-2.5 rounded-pill">
                    <i class="fa-solid fa-box-archive me-1"></i> Offers
                  </a>
                </div>

                <div class="d-flex align-items-center gap-1">
                  <form action="/countries/toggle" method="POST" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                    <button type="submit" class="btn btn-sm <?= $isActive ? 'btn-outline-warning' : 'btn-outline-success' ?> py-1 px-2 rounded-pill" title="<?= $isActive ? 'Suspend' : 'Activate' ?>">
                      <i class="fa-solid <?= $isActive ? 'fa-ban' : 'fa-check' ?>"></i>
                    </button>
                  </form>
                  <?php if ((int)$c['services_count'] === 0): ?>
                    <form action="/countries/delete" method="POST" class="d-inline" onsubmit="return confirm('Delete destination country \'<?= e(addslashes($c['name'])) ?>\'?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= $c['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2 rounded-pill" title="Delete">
                        <i class="fa-solid fa-trash-can"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- ================================================================= -->
  <!-- VIEW OPTION 2: DATA TABLE VIEW -->
  <!-- ================================================================= -->
  <div id="countryViewTable" class="country-view-container <?= $currentView === 'table' ? '' : 'd-none' ?>">
    <div class="bento-country-table-card">
      <div class="table-responsive mb-0">
        <table class="bento-country-table align-middle">
          <thead>
            <tr>
              <th style="min-width: 60px;">Flag</th>
              <th style="min-width: 180px;">Country Name</th>
              <th style="min-width: 90px;">ISO Code</th>
              <th style="min-width: 130px;">Region</th>
              <th style="min-width: 90px;">Currency</th>
              <th style="min-width: 200px;">Embassy / Authority</th>
              <th style="min-width: 90px;">Packages</th>
              <th style="min-width: 90px;">Cases</th>
              <th style="min-width: 90px;">Status</th>
              <th class="text-end" style="min-width: 120px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($countries)): ?>
              <tr>
                <td colspan="10" class="text-center py-5 text-muted">No destination countries registered.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($countries as $c): 
                $isActive = (int)($c['is_active'] ?? 0) === 1;
                $cJson = htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8');
              ?>
                <tr>
                  <td data-label="Flag" class="fs-4"><?= $c['flag_emoji'] ?: '🌐' ?></td>
                  <td data-label="Country Name">
                    <strong class="text-dark"><?= e($c['name']) ?></strong>
                  </td>
                  <td data-label="ISO Code">
                    <span class="iso-code-badge"><?= e($c['iso_code']) ?></span>
                  </td>
                  <td data-label="Region" class="text-muted small"><?= e($c['region'] ?: 'Global') ?></td>
                  <td data-label="Currency" class="fw-semibold text-dark font-monospace"><?= e($c['currency'] ?: 'USD') ?></td>
                  <td data-label="Embassy / Authority" class="small text-muted text-truncate" style="max-width: 200px;">
                    <?= e($c['embassy_info'] ?: '—') ?>
                  </td>
                  <td data-label="Packages">
                    <span class="badge bg-primary rounded-pill px-2.5 py-1 font-monospace"><?= (int)$c['services_count'] ?></span>
                  </td>
                  <td data-label="Cases">
                    <span class="badge bg-success rounded-pill px-2.5 py-1 font-monospace"><?= (int)$c['applications_count'] ?></span>
                  </td>
                  <td data-label="Status">
                    <span class="badge <?= $isActive ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> fw-bold px-2 py-0.5 rounded-pill">
                      <?= $isActive ? 'Active' : 'Suspended' ?>
                    </span>
                  </td>
                  <td data-label="Actions" class="text-end">
                    <div class="d-inline-flex align-items-center gap-1">
                      <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 rounded-pill" onclick="openEditCountryModal(<?= $cJson ?>)" title="Edit">
                        <i class="fa-solid fa-pen-to-square"></i>
                      </button>
                      <form action="/countries/toggle" method="POST" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn btn-sm <?= $isActive ? 'btn-outline-warning' : 'btn-outline-success' ?> py-1 px-2 rounded-pill" title="<?= $isActive ? 'Suspend' : 'Activate' ?>">
                          <i class="fa-solid <?= $isActive ? 'fa-ban' : 'fa-check' ?>"></i>
                        </button>
                      </form>
                      <?php if ((int)$c['services_count'] === 0): ?>
                        <form action="/countries/delete" method="POST" class="d-inline" onsubmit="return confirm('Delete country?');">
                          <?= csrf_field() ?>
                          <input type="hidden" name="id" value="<?= $c['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2 rounded-pill" title="Delete">
                            <i class="fa-solid fa-trash-can"></i>
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ================================================================= -->
  <!-- VIEW OPTION 3: COMPACT LIST VIEW -->
  <!-- ================================================================= -->
  <div id="countryViewCompact" class="country-view-container <?= $currentView === 'compact' ? '' : 'd-none' ?>">
    <div class="bento-country-table-card">
      <ul class="list-group list-group-flush mb-0">
        <?php foreach ($countries as $c): 
          $isActive = (int)($c['is_active'] ?? 0) === 1;
          $cJson = htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8');
        ?>
          <li class="list-group-item p-3 d-flex flex-wrap align-items-center justify-content-between gap-2.5">
            <div class="d-flex align-items-center gap-3">
              <span class="fs-4"><?= $c['flag_emoji'] ?: '🌐' ?></span>
              <div>
                <div class="d-flex align-items-center gap-2">
                  <span class="iso-code-badge" style="font-size: 0.72rem;"><?= e($c['iso_code']) ?></span>
                  <strong class="text-dark"><?= e($c['name']) ?></strong>
                  <span class="badge <?= $isActive ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> rounded-pill" style="font-size: 0.68rem;">
                    <?= $isActive ? 'Active' : 'Suspended' ?>
                  </span>
                </div>
                <div class="text-muted small" style="font-size: 0.75rem;">
                  <?= e($c['region'] ?: 'Global') ?> &bull; Currency: <?= e($c['currency'] ?: 'USD') ?> &bull; <?= e($c['embassy_info'] ?: 'Standard Consular') ?>
                </div>
              </div>
            </div>

            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-primary rounded-pill px-2.5 py-1 small"><?= (int)$c['services_count'] ?> Packages</span>
              <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2.5 rounded-pill" onclick="openEditCountryModal(<?= $cJson ?>)">
                <i class="fa-solid fa-pen-to-square me-1"></i> Edit
              </button>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<!-- ================================================================= -->
<!-- MODALS: ADD & EDIT COUNTRY -->
<!-- ================================================================= -->

<!-- Modal: Add New Country -->
<div class="modal fade" id="newCountryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow" style="border-radius: var(--bento-radius);">
      <div class="modal-header bg-dark text-white" style="border-top-left-radius: var(--bento-radius); border-top-right-radius: var(--bento-radius);">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-earth-americas text-danger me-2"></i> Register Destination Country</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/countries/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="row g-2 mb-3">
            <div class="col-8">
              <label class="form-label small fw-semibold">Country Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Germany" required>
            </div>
            <div class="col-4">
              <label class="form-label small fw-semibold">ISO Code <span class="text-danger">*</span></label>
              <input type="text" name="iso_code" class="form-control font-monospace" placeholder="DE" maxlength="2" required>
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-4">
              <label class="form-label small fw-semibold">Flag Emoji</label>
              <input type="text" name="flag_emoji" class="form-control text-center fs-5" placeholder="🇩🇪" value="🌐">
            </div>
            <div class="col-4">
              <label class="form-label small fw-semibold">Currency</label>
              <input type="text" name="currency" class="form-control font-monospace" placeholder="EUR" value="USD">
            </div>
            <div class="col-4">
              <label class="form-label small fw-semibold">Region</label>
              <input type="text" name="region" class="form-control" placeholder="Europe" value="Global">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Embassy / Consular Authority</label>
            <input type="text" name="embassy_info" class="form-control" placeholder="e.g. German Embassy Consular Section / VFS Global">
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold">Internal Notes</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Schengen rules, document notes..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light" style="border-bottom-left-radius: var(--bento-radius); border-bottom-right-radius: var(--bento-radius);">
          <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold rounded-pill px-4"><i class="fa-solid fa-save me-1"></i> Save Country</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Edit Country -->
<div class="modal fade" id="editCountryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow" style="border-radius: var(--bento-radius);">
      <div class="modal-header bg-dark text-white" style="border-top-left-radius: var(--bento-radius); border-top-right-radius: var(--bento-radius);">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square text-danger me-2"></i> Edit Destination Country</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/countries/update" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="edit_country_id" value="">
        <div class="modal-body p-4">
          <div class="row g-2 mb-3">
            <div class="col-8">
              <label class="form-label small fw-semibold">Country Name <span class="text-danger">*</span></label>
              <input type="text" name="name" id="edit_country_name" class="form-control" required>
            </div>
            <div class="col-4">
              <label class="form-label small fw-semibold">ISO Code <span class="text-danger">*</span></label>
              <input type="text" name="iso_code" id="edit_country_iso" class="form-control font-monospace" maxlength="2" required>
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-4">
              <label class="form-label small fw-semibold">Flag Emoji</label>
              <input type="text" name="flag_emoji" id="edit_country_flag" class="form-control text-center fs-5">
            </div>
            <div class="col-4">
              <label class="form-label small fw-semibold">Currency</label>
              <input type="text" name="currency" id="edit_country_currency" class="form-control font-monospace">
            </div>
            <div class="col-4">
              <label class="form-label small fw-semibold">Region</label>
              <input type="text" name="region" id="edit_country_region" class="form-control">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Embassy / Consular Authority</label>
            <input type="text" name="embassy_info" id="edit_country_embassy" class="form-control">
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Phone Code</label>
              <input type="text" name="phone_code" id="edit_country_phone_code" class="form-control" placeholder="+49">
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Status</label>
              <select name="is_active" id="edit_country_is_active" class="form-select">
                <option value="1">Active</option>
                <option value="0">Suspended</option>
              </select>
            </div>
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold">Internal Notes</label>
            <textarea name="notes" id="edit_country_notes" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light" style="border-bottom-left-radius: var(--bento-radius); border-bottom-right-radius: var(--bento-radius);">
          <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold rounded-pill px-4"><i class="fa-solid fa-save me-1"></i> Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function switchCountryView(mode) {
  document.querySelectorAll('.country-view-container').forEach(el => el.classList.add('d-none'));
  document.querySelectorAll('.country-view-btn').forEach(btn => btn.classList.remove('active'));

  if (mode === 'table') {
    const t = document.getElementById('countryViewTable');
    if (t) t.classList.remove('d-none');
    const b = document.getElementById('btnCountryViewTable');
    if (b) b.classList.add('active');
    try { localStorage.setItem('vt_country_view', 'table'); } catch(e) {}
  } else if (mode === 'compact') {
    const c = document.getElementById('countryViewCompact');
    if (c) c.classList.remove('d-none');
    const b = document.getElementById('btnCountryViewCompact');
    if (b) b.classList.add('active');
    try { localStorage.setItem('vt_country_view', 'compact'); } catch(e) {}
  } else {
    const g = document.getElementById('countryViewCards');
    if (g) g.classList.remove('d-none');
    const b = document.getElementById('btnCountryViewCards');
    if (b) b.classList.add('active');
    try { localStorage.setItem('vt_country_view', 'cards'); } catch(e) {}
  }
}

function openEditCountryModal(data) {
  if (!data) return;
  document.getElementById('edit_country_id').value = data.id || '';
  document.getElementById('edit_country_name').value = data.name || '';
  document.getElementById('edit_country_iso').value = data.iso_code || '';
  document.getElementById('edit_country_flag').value = data.flag_emoji || '🌐';
  document.getElementById('edit_country_currency').value = data.currency || 'USD';
  document.getElementById('edit_country_region').value = data.region || 'Global';
  document.getElementById('edit_country_embassy').value = data.embassy_info || '';
  document.getElementById('edit_country_phone_code').value = data.phone_code || '';
  document.getElementById('edit_country_notes').value = data.notes || '';
  document.getElementById('edit_country_is_active').value = (data.is_active !== undefined) ? data.is_active : 1;

  window.openModalById('editCountryModal');
}

document.addEventListener('DOMContentLoaded', function() {
  const saved = (function() {
    try { return localStorage.getItem('vt_country_view') || 'cards'; } catch(e) { return 'cards'; }
  })();
  if (saved === 'table' || saved === 'compact') {
    switchCountryView(saved);
  }
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
