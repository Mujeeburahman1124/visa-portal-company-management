<?php
$pageTitle = 'Master Settings & Operations Configuration — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
$activeTab = $_GET['tab'] ?? 'company';
?>

<div class="content-body">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Page Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <h3 class="fw-bold brand-font text-dark mb-1">Master Operations &amp; System Configuration</h3>
      <p class="text-muted small mb-0">Organized administrative controls for company branding, visa workflow rules, document criteria, email templates, and security policies.</p>
    </div>
  </div>

  <!-- Multi-Category Organized Settings Engine -->
  <div class="card card-enterprise shadow-sm">
    <div class="card-header bg-white border-bottom p-0">
      <ul class="nav nav-tabs card-header-tabs m-0 px-3 flex-nowrap overflow-x-auto" role="tablist">
        <li class="nav-item">
          <a class="nav-link <?= $activeTab === 'company' ? 'active' : '' ?> py-3 fw-semibold small text-nowrap" href="/settings?tab=company">
            <i class="fa-solid fa-building text-primary me-1"></i> Company &amp; Branding
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $activeTab === 'workflow' ? 'active' : '' ?> py-3 fw-semibold small text-nowrap" href="/settings?tab=workflow">
            <i class="fa-solid fa-route text-success me-1"></i> Visa Workflow &amp; Stages
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $activeTab === 'documents' ? 'active' : '' ?> py-3 fw-semibold small text-nowrap" href="/settings?tab=documents">
            <i class="fa-solid fa-file-shield text-warning me-1"></i> Document Settings
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $activeTab === 'tasks' ? 'active' : '' ?> py-3 fw-semibold small text-nowrap" href="/settings?tab=tasks">
            <i class="fa-solid fa-list-check text-info me-1"></i> Task SLAs
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $activeTab === 'templates' ? 'active' : '' ?> py-3 fw-semibold small text-nowrap" href="/settings?tab=templates">
            <i class="fa-solid fa-envelope-open-text text-secondary me-1"></i> Email Templates
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $activeTab === 'countries' ? 'active' : '' ?> py-3 fw-semibold small text-nowrap" href="/settings?tab=countries">
            <i class="fa-solid fa-globe text-primary me-1"></i> Countries (<?= count($countries) ?>)
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $activeTab === 'services' ? 'active' : '' ?> py-3 fw-semibold small text-nowrap" href="/settings?tab=services">
            <i class="fa-solid fa-passport text-info me-1"></i> Visa Packages (<?= count($services) ?>)
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $activeTab === 'statuses' ? 'active' : '' ?> py-3 fw-semibold small text-nowrap" href="/settings?tab=statuses">
            <i class="fa-solid fa-tags text-success me-1"></i> Application Statuses (<?= count($applicationStatuses ?? []) ?>)
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $activeTab === 'security' ? 'active' : '' ?> py-3 fw-semibold small text-nowrap" href="/settings?tab=security">
            <i class="fa-solid fa-shield-halved text-danger me-1"></i> Security &amp; Sessions
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $activeTab === 'appearance' ? 'active' : '' ?> py-3 fw-semibold small text-nowrap" href="/settings?tab=appearance">
            <i class="fa-solid fa-palette text-warning me-1"></i> Admin Portal Themes &amp; Appearance
          </a>
        </li>
      </ul>
    </div>

    <div class="card-body p-4">

      <!-- ============================================== -->
      <!-- TAB 1: COMPANY & BRANDING -->
      <!-- ============================================== -->
      <?php if ($activeTab === 'company'): ?>
        <form action="/settings/preferences" method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="target_tab" value="company">
          
          <div class="row g-4">
            <div class="col-lg-6">
              <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-id-card text-primary me-2"></i> Company Profile &amp; Contact Info</h6>
              <div class="mb-3">
                <label class="form-label small fw-semibold">Company / Agency Name</label>
                <input type="text" name="settings[company_name]" class="form-control" value="<?= e($settings['company_name'] ?? 'MS TRAVEL HUB & VISA SERVICES') ?>">
              </div>
              <div class="row g-2 mb-3">
                <div class="col-6">
                  <label class="form-label small fw-semibold">Trade License / Reg #</label>
                  <input type="text" name="settings[trade_license]" class="form-control" value="<?= e($settings['trade_license'] ?? 'DXB-TR-88910') ?>">
                </div>
                <div class="col-6">
                  <label class="form-label small fw-semibold">Primary Currency</label>
                  <input type="text" name="settings[primary_currency]" class="form-control" value="<?= e($settings['primary_currency'] ?? 'USD') ?>">
                </div>
              </div>
              <div class="row g-2 mb-3">
                <div class="col-6">
                  <label class="form-label small fw-semibold">Official Contact Email</label>
                  <input type="email" name="settings[company_email]" class="form-control" value="<?= e($settings['company_email'] ?? 'support@mstravelhub.com') ?>">
                </div>
                <div class="col-6">
                  <label class="form-label small fw-semibold">Hotline / WhatsApp</label>
                  <input type="text" name="settings[company_phone]" class="form-control" value="<?= e($settings['company_phone'] ?? '+971 4 388 9900') ?>">
                </div>
              </div>
            </div>

            <div class="col-lg-6">
              <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-palette text-primary me-2"></i> Portal Localization &amp; Branding</h6>
              <div class="mb-3">
                <label class="form-label small fw-semibold">Portal Header Brand Text</label>
                <input type="text" name="settings[portal_brand_text]" class="form-control" value="<?= e($settings['portal_brand_text'] ?? 'VISA TRACK') ?>">
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">Tagline / Subtitle</label>
                <input type="text" name="settings[portal_tagline]" class="form-control" value="<?= e($settings['portal_tagline'] ?? 'Global Visa Operations & Embassy Logistics') ?>">
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">Official Address</label>
                <textarea name="settings[company_address]" class="form-control" rows="2"><?= e($settings['company_address'] ?? 'Tower B, Level 14, Business Bay, Dubai, UAE') ?></textarea>
              </div>
            </div>
          </div>

          <div class="pt-3 mt-4 border-top text-end">
            <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
              <i class="fa-solid fa-floppy-disk me-1"></i> Save Company Settings
            </button>
          </div>
        </form>

      <!-- ============================================== -->
      <!-- TAB 2: VISA WORKFLOW & STAGES -->
      <!-- ============================================== -->
      <?php elseif ($activeTab === 'workflow'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h6 class="fw-bold text-dark mb-0">Visa Lifecycle Stages &amp; SLA Milestones</h6>
            <p class="text-muted small mb-0">Configure sequence progression order, mandatory checklist gates, and SLA targets.</p>
          </div>
        </div>

        <div class="table-responsive mb-4">
          <table class="table-modern mb-0">
            <thead>
              <tr>
                <th style="width: 80px;">Order</th>
                <th style="min-width: 220px;">Stage Title</th>
                <th style="min-width: 140px;">Stage Code</th>
                <th style="min-width: 130px;">Internal Target SLA</th>
                <th style="min-width: 200px;">Mandatory Gate Rule</th>
                <th class="text-end">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($stages)): ?>
                <tr>
                  <td>1</td>
                  <td class="fw-bold text-dark">Draft &amp; Registration</td>
                  <td><span class="badge bg-light text-dark border">REGISTRATION</span></td>
                  <td>1 Day</td>
                  <td>Customer Profile Created</td>
                  <td class="text-end"><span class="badge bg-success">Active</span></td>
                </tr>
                <tr>
                  <td>2</td>
                  <td class="fw-bold text-dark">Document Collection &amp; Review</td>
                  <td><span class="badge bg-light text-dark border">DOC_COLLECTION</span></td>
                  <td>2 Days</td>
                  <td>All Required Docs Verified</td>
                  <td class="text-end"><span class="badge bg-success">Active</span></td>
                </tr>
                <tr>
                  <td>3</td>
                  <td class="fw-bold text-dark">Application Form &amp; Prep</td>
                  <td><span class="badge bg-light text-dark border">FORM_PREP</span></td>
                  <td>1 Day</td>
                  <td>Consular Form Drafted</td>
                  <td class="text-end"><span class="badge bg-success">Active</span></td>
                </tr>
                <tr>
                  <td>4</td>
                  <td class="fw-bold text-dark">Appointment &amp; Biometrics</td>
                  <td><span class="badge bg-light text-dark border">BIOMETRICS</span></td>
                  <td>3 Days</td>
                  <td>VFS/Consulate Booked</td>
                  <td class="text-end"><span class="badge bg-success">Active</span></td>
                </tr>
                <tr>
                  <td>5</td>
                  <td class="fw-bold text-dark">Submitted / Under Embassy Review</td>
                  <td><span class="badge bg-light text-dark border">EMBASSY_PROCESS</span></td>
                  <td>5-15 Days</td>
                  <td>Embassy Reference Attached</td>
                  <td class="text-end"><span class="badge bg-success">Active</span></td>
                </tr>
                <tr>
                  <td>6</td>
                  <td class="fw-bold text-dark">Decision Received &amp; Visa Stamping</td>
                  <td><span class="badge bg-light text-dark border">DECISION</span></td>
                  <td>1 Day</td>
                  <td>Visa e-Document Attached</td>
                  <td class="text-end"><span class="badge bg-success">Active</span></td>
                </tr>
              <?php else: ?>
                <?php foreach ($stages as $stg): ?>
                  <tr>
                    <td><span class="badge bg-light text-dark border fw-bold"><?= $stg['sequence_order'] ?></span></td>
                    <td class="fw-bold text-dark"><?= e($stg['name']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= e($stg['code']) ?></span></td>
                    <td><i class="fa-regular fa-clock me-1 text-primary"></i><?= $stg['default_sla_days'] ?? 2 ?> Days</td>
                    <td class="small text-muted"><?= e($stg['description'] ?: 'Stage progression gating') ?></td>
                    <td class="text-end"><span class="badge bg-success">Active</span></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="alert alert-info small mb-0">
          <i class="fa-solid fa-circle-info me-1"></i> <strong>Note:</strong> Internal target SLAs are internal company processing goals and do NOT represent official government consular processing durations.
        </div>

      <!-- ============================================== -->
      <!-- TAB 3: DOCUMENT SETTINGS -->
      <!-- ============================================== -->
      <?php elseif ($activeTab === 'documents'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h6 class="fw-bold text-dark mb-0">Configured Document Requirements</h6>
            <p class="text-muted small mb-0">Define mandatory document types, category classification, and expiry alert horizons.</p>
          </div>
          <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addDocTypeModal">
            <i class="fa-solid fa-plus me-1"></i> Add Document Type
          </button>
        </div>

        <div class="table-responsive mb-4">
          <table class="table-modern mb-0">
            <thead>
              <tr>
                <th>Document Type</th>
                <th>Requirement Code</th>
                <th>Category</th>
                <th>Requires Expiry Date?</th>
                <th>Expiry Horizon Warning</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($docTypes as $dt): ?>
                <tr>
                  <td class="fw-bold text-dark"><?= e($dt['name']) ?></td>
                  <td><span class="badge bg-light text-dark border"><?= e($dt['code']) ?></span></td>
                  <td><span class="badge bg-primary-subtle text-primary"><?= e($dt['category']) ?></span></td>
                  <td>
                    <?php if ((int)$dt['requires_expiry'] === 1): ?>
                      <span class="badge bg-warning-subtle text-dark"><i class="fa-solid fa-check me-1"></i>Yes</span>
                    <?php else: ?>
                      <span class="text-muted small">No</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="small text-muted"><i class="fa-solid fa-clock-rotate-left me-1"></i>30 / 60 / 90 Days</span>
                  </td>
                  <td><span class="badge bg-success">Active</span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      <!-- ============================================== -->
      <!-- TAB 4: TASK SLAS -->
      <!-- ============================================== -->
      <?php elseif ($activeTab === 'tasks'): ?>
        <form action="/settings/preferences" method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="target_tab" value="tasks">
          
          <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-list-check text-info me-2"></i> Task Categories &amp; Internal SLA Configuration</h6>

          <div class="row g-3 mb-4">
            <div class="col-md-3">
              <div class="p-3 bg-light rounded border">
                <div class="fw-bold text-danger mb-1"><i class="fa-solid fa-fire me-1"></i>Critical Priority SLA</div>
                <div class="small text-muted mb-2">Target resolution turnaround</div>
                <div class="input-group input-group-sm">
                  <input type="number" name="settings[sla_critical_hours]" class="form-control" value="<?= e($settings['sla_critical_hours'] ?? '4') ?>">
                  <span class="input-group-text">Hours</span>
                </div>
              </div>
            </div>

            <div class="col-md-3">
              <div class="p-3 bg-light rounded border">
                <div class="fw-bold text-warning mb-1"><i class="fa-solid fa-bolt me-1"></i>Urgent Priority SLA</div>
                <div class="small text-muted mb-2">Target resolution turnaround</div>
                <div class="input-group input-group-sm">
                  <input type="number" name="settings[sla_urgent_hours]" class="form-control" value="<?= e($settings['sla_urgent_hours'] ?? '12') ?>">
                  <span class="input-group-text">Hours</span>
                </div>
              </div>
            </div>

            <div class="col-md-3">
              <div class="p-3 bg-light rounded border">
                <div class="fw-bold text-primary mb-1"><i class="fa-solid fa-arrow-trend-up me-1"></i>High Priority SLA</div>
                <div class="small text-muted mb-2">Target resolution turnaround</div>
                <div class="input-group input-group-sm">
                  <input type="number" name="settings[sla_high_hours]" class="form-control" value="<?= e($settings['sla_high_hours'] ?? '24') ?>">
                  <span class="input-group-text">Hours</span>
                </div>
              </div>
            </div>

            <div class="col-md-3">
              <div class="p-3 bg-light rounded border">
                <div class="fw-bold text-secondary mb-1"><i class="fa-solid fa-circle-check me-1"></i>Normal Priority SLA</div>
                <div class="small text-muted mb-2">Target resolution turnaround</div>
                <div class="input-group input-group-sm">
                  <input type="number" name="settings[sla_normal_hours]" class="form-control" value="<?= e($settings['sla_normal_hours'] ?? '48') ?>">
                  <span class="input-group-text">Hours</span>
                </div>
              </div>
            </div>
          </div>

          <div class="pt-3 border-top text-end">
            <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
              <i class="fa-solid fa-floppy-disk me-1"></i> Save Task SLAs
            </button>
          </div>
        </form>

      <!-- ============================================== -->
      <!-- ============================================== -->
      <!-- TAB 5: EMAIL NOTIFICATION TEMPLATES -->
      <!-- ============================================== -->
      <?php elseif ($activeTab === 'templates'): ?>
        <!-- Global Email Branding & Theme Customizer -->
        <div class="card border rounded-3 shadow-sm mb-4">
          <div class="card-header bg-light d-flex flex-wrap align-items-center justify-content-between py-3 gap-2">
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-primary p-2 rounded-2"><i class="fa-solid fa-paintbrush text-white"></i></span>
              <div>
                <h6 class="fw-bold text-dark mb-0">Global Email Branding &amp; Visual Styling</h6>
                <div class="text-muted small" style="font-size: 0.8rem;">Configure the official logo, header color, brand accents, and footer branding applied to all automated emails.</div>
              </div>
            </div>
            <button type="button" class="btn btn-outline-info btn-sm px-3 fw-semibold shadow-sm" onclick="previewBrandedEmail()">
              <i class="fa-solid fa-eye me-1"></i> Preview Branded Layout
            </button>
          </div>
          <div class="card-body p-3.5">
            <form action="/settings/preferences" method="POST" id="emailBrandingForm">
              <?= csrf_field() ?>
              <input type="hidden" name="target_tab" value="templates">

              <div class="row g-3 align-items-start">
                <!-- Logo URL -->
                <div class="col-lg-5 col-md-12">
                  <label class="form-label small fw-semibold text-dark mb-1">
                    <i class="fa-solid fa-image text-primary me-1"></i> Email Header Logo
                  </label>
                  <div class="input-group">
                    <input type="text" name="settings[email_theme_logo]" id="emailThemeLogoInput" class="form-control form-control-sm" 
                           value="<?= e(!empty($settings['email_theme_logo']) ? $settings['email_theme_logo'] : '/assets/images/logo.png') ?>" 
                           placeholder="/assets/images/logo.png or https://..." oninput="updateEmailHeaderPreview()">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetEmailLogo()">Default</button>
                  </div>
                  <div class="form-text small" style="font-size: 0.72rem;">Default: <code>/assets/images/logo.png</code>. Supports absolute URLs or local asset paths.</div>
                </div>

                <!-- Header Background Color -->
                <div class="col-lg-3 col-md-6 col-sm-6">
                  <label class="form-label small fw-semibold text-dark mb-1">
                    <i class="fa-solid fa-fill-drip text-dark me-1"></i> Header Background
                  </label>
                  <div class="d-flex align-items-center gap-2">
                    <input type="color" id="emailHeaderBgColor" class="form-control form-control-color border-0 p-0" style="width: 36px; height: 34px; border-radius: 6px; cursor: pointer;"
                           value="<?= e(!empty($settings['email_theme_header_bg']) ? $settings['email_theme_header_bg'] : '#0f172a') ?>" oninput="syncEmailHeaderColor(this.value)">
                    <input type="text" name="settings[email_theme_header_bg]" id="emailHeaderBgHex" class="form-control form-control-sm font-monospace text-uppercase" 
                           value="<?= e(!empty($settings['email_theme_header_bg']) ? $settings['email_theme_header_bg'] : '#0f172a') ?>" maxlength="7" oninput="syncEmailHeaderHex(this.value)">
                  </div>
                  <div class="form-text small" style="font-size: 0.72rem;">Email top banner color (Dark, Navy, Black, etc.)</div>
                </div>

                <!-- Primary Brand / Button Color -->
                <div class="col-lg-4 col-md-6 col-sm-6">
                  <label class="form-label small fw-semibold text-dark mb-1">
                    <i class="fa-solid fa-palette text-primary me-1"></i> Brand Color (Buttons &amp; Links)
                  </label>
                  <div class="d-flex align-items-center gap-2">
                    <input type="color" id="emailThemeColor" class="form-control form-control-color border-0 p-0" style="width: 36px; height: 34px; border-radius: 6px; cursor: pointer;"
                           value="<?= e(!empty($settings['email_theme_color']) ? $settings['email_theme_color'] : (!empty($settings['email_theme_primary']) ? $settings['email_theme_primary'] : '#2563eb')) ?>" oninput="syncEmailThemeColor(this.value)">
                    <input type="text" name="settings[email_theme_color]" id="emailThemeColorHex" class="form-control form-control-sm font-monospace text-uppercase" 
                           value="<?= e(!empty($settings['email_theme_color']) ? $settings['email_theme_color'] : (!empty($settings['email_theme_primary']) ? $settings['email_theme_primary'] : '#2563eb')) ?>" maxlength="7" oninput="syncEmailThemeHex(this.value)">
                  </div>
                  <div class="form-text small" style="font-size: 0.72rem;">Applied to Call-To-Action buttons and accent borders</div>
                </div>

                <!-- Footer Text -->
                <div class="col-lg-9 col-md-8">
                  <label class="form-label small fw-semibold text-dark mb-1">
                    <i class="fa-solid fa-align-left text-secondary me-1"></i> Email Footer Branding Text
                  </label>
                  <input type="text" name="settings[email_theme_footer]" id="emailThemeFooterInput" class="form-control form-control-sm"
                         value="<?= e($settings['email_theme_footer'] ?? 'MS Travel Hub Global Visa Services • Enterprise Visa Operations') ?>" oninput="updateEmailHeaderPreview()">
                </div>

                <!-- Save Button -->
                <div class="col-lg-3 col-md-4 d-flex align-items-end">
                  <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold shadow-sm py-2">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Save Email Branding
                  </button>
                </div>
              </div>

              <!-- Interactive Live Header Preview Strip -->
              <div class="mt-3 p-3 rounded-2 border" style="background-color: #f8fafc;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="small fw-bold text-secondary text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Live Email Header &amp; Button Preview:</span>
                  <span class="badge bg-success-subtle text-success small" style="font-size: 0.68rem;">Realtime CSS Preview</span>
                </div>
                <div id="liveEmailHeaderPreview" style="background-color: <?= e(!empty($settings['email_theme_header_bg']) ? $settings['email_theme_header_bg'] : '#0f172a') ?>; border-bottom: 4px solid <?= e(!empty($settings['email_theme_color']) ? $settings['email_theme_color'] : '#2563eb') ?>; padding: 16px 24px; border-radius: 8px 8px 0 0;" class="d-flex align-items-center justify-content-between shadow-sm">
                  <div class="d-flex align-items-center gap-3">
                    <img id="previewEmailLogoImg" src="<?= e(!empty($settings['email_theme_logo']) ? $settings['email_theme_logo'] : '/assets/images/logo.png') ?>" alt="Logo" style="max-height: 42px; width: auto; max-width: 150px; border-radius: 4px; display: block;" onerror="this.src='/assets/images/logo.png'">
                    <div>
                      <div class="text-white fw-bold" style="font-size: 0.98rem; line-height: 1.2;">MS TRAVEL HUB</div>
                      <div style="color: #94a3b8; font-size: 0.74rem;">Global Visa &amp; Operations Management</div>
                    </div>
                  </div>
                  <span class="badge" style="background: rgba(255,255,255,0.15); color: #fff; font-size: 0.7rem; border: 1px solid rgba(255,255,255,0.25);">OFFICIAL NOTICE</span>
                </div>
                <div class="bg-white p-3 border-start border-end border-bottom rounded-bottom-2 d-flex flex-wrap align-items-center justify-content-between gap-2 shadow-sm">
                  <div class="small text-secondary"><i class="fa-solid fa-wand-magic-sparkles text-primary me-1"></i> Sample Branded Email CTA Button:</div>
                  <button type="button" id="previewEmailBtn" class="btn btn-sm text-white fw-semibold px-4 shadow-sm" style="background-color: <?= e(!empty($settings['email_theme_color']) ? $settings['email_theme_color'] : '#2563eb') ?>; border-radius: 6px; font-size: 0.82rem; pointer-events: none;">
                    Review Visa Application &rarr;
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <div class="row g-4">
          <!-- Templates Catalog List -->
          <div class="col-lg-5">
            <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-envelope-open-text text-primary me-2"></i> System Email Templates</h6>
            <div class="list-group list-group-flush border rounded">
              <?php foreach ($templates as $idx => $tmpl): ?>
                <a href="#tmpl_<?= $tmpl['id'] ?>" class="list-group-item list-group-item-action p-3 <?= $idx === 0 ? 'active' : '' ?>" data-bs-toggle="list">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold"><?= e($tmpl['title']) ?></span>
                    <span class="badge bg-light text-dark border small font-monospace"><?= e($tmpl['template_key']) ?></span>
                  </div>
                  <div class="small text-truncate" style="opacity: 0.85;"><?= e($tmpl['subject']) ?></div>
                </a>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Template Editor & Live Variable Preview -->
          <div class="col-lg-7">
            <div class="tab-content">
              <?php foreach ($templates as $idx => $tmpl): ?>
                <div class="tab-pane fade <?= $idx === 0 ? 'show active' : '' ?>" id="tmpl_<?= $tmpl['id'] ?>">
                  <form action="/settings/update-template" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $tmpl['id'] ?>">

                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                      <h6 class="fw-bold text-dark mb-0">Edit Template: <?= e($tmpl['title']) ?></h6>
                      <span class="badge bg-primary-subtle text-primary"><?= e($tmpl['template_key']) ?></span>
                    </div>

                    <div class="mb-3">
                      <label class="form-label small fw-semibold">Email Subject Line <span class="text-danger">*</span></label>
                      <input type="text" name="subject" class="form-control" value="<?= e($tmpl['subject']) ?>" required>
                    </div>

                    <div class="mb-3">
                      <label class="form-label small fw-semibold">Email HTML Body <span class="text-danger">*</span></label>
                      <textarea name="body_html" class="form-control font-monospace" rows="8" required><?= e($tmpl['body_html']) ?></textarea>
                    </div>

                    <div class="p-3 bg-light rounded border mb-3">
                      <div class="fw-semibold small text-dark mb-1"><i class="fa-solid fa-code text-primary me-1"></i> Supported Dynamic Placeholders:</div>
                      <div class="text-muted small font-monospace">
                        <?= e($tmpl['placeholders'] ?: '{{user_name}}, {{applicant_name}}, {{application_number}}, {{task_title}}, {{due_date}}, {{action_url}}') ?>
                      </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                      <button type="button" class="btn btn-outline-info btn-sm px-3 fw-semibold" onclick="previewEmailTemplate(<?= $tmpl['id'] ?>)">
                        <i class="fa-solid fa-eye me-1"></i> Live Preview
                      </button>
                      <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-semibold" onclick="openTestEmailModal(<?= $tmpl['id'] ?>, '<?= e(addslashes($tmpl['title'])) ?>')">
                        <i class="fa-solid fa-paper-plane me-1"></i> Send Test Email
                      </button>
                      <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Update Template
                      </button>
                    </div>
                  </form>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

      <!-- ============================================== -->
      <!-- TAB 6: DESTINATION COUNTRIES -->
      <!-- ============================================== -->
      <?php elseif ($activeTab === 'countries'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h6 class="fw-bold text-dark mb-0">Destination Countries</h6>
            <p class="text-muted small mb-0">Global jurisdictions, embassies, and visa clearing authorities.</p>
          </div>
          <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCountryModal">
            <i class="fa-solid fa-plus me-1"></i> Add Country
          </button>
        </div>

        <div class="table-responsive">
          <table class="table-modern mb-0">
            <thead><tr><th>Flag</th><th>Country Name</th><th>ISO Code</th><th>Region</th><th>Currency</th><th>Embassy / Clearing Unit</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($countries as $c): ?>
                <tr>
                  <td class="fs-4"><?= $c['flag_emoji'] ?></td>
                  <td class="fw-bold text-dark"><?= e($c['name']) ?></td>
                  <td><span class="badge bg-light text-dark border"><?= e($c['iso_code']) ?></span></td>
                  <td><?= e($c['region']) ?></td>
                  <td><span class="fw-semibold"><?= e($c['currency']) ?></span></td>
                  <td><span class="small text-muted"><?= e($c['embassy_info'] ?: '—') ?></span></td>
                  <td><span class="badge bg-success">Active</span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      <!-- ============================================== -->
      <!-- TAB 7: VISA PACKAGES & SERVICES -->
      <!-- ============================================== -->
      <?php elseif ($activeTab === 'services'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h6 class="fw-bold text-dark mb-0">Configured Visa Packages &amp; Pricing</h6>
            <p class="text-muted small mb-0">Visa types, entry categories, SLA turnaround times, selling prices, and supplier costs.</p>
          </div>
          <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addServiceModal">
            <i class="fa-solid fa-plus me-1"></i> Add Visa Package
          </button>
        </div>

        <div class="table-responsive">
          <table class="table-modern mb-0">
            <thead><tr><th>Country</th><th>Service Package</th><th>Category</th><th>Duration / Stay</th><th>Entry</th><th>SLA Days</th><th>Selling Price</th><th>Supplier Cost</th></tr></thead>
            <tbody>
              <?php foreach ($services as $srv): ?>
                <tr>
                  <td><span class="fs-5 me-1"><?= $srv['flag_emoji'] ?></span> <?= e($srv['country_name']) ?></td>
                  <td class="fw-bold text-dark"><?= e($srv['name']) ?></td>
                  <td><span class="badge bg-light text-primary border"><?= e($srv['category_name']) ?></span></td>
                  <td><?= e($srv['duration']) ?> (Stay: <?= e($srv['max_stay']) ?>)</td>
                  <td><span class="badge bg-secondary-subtle text-dark"><?= e($srv['entry_type']) ?></span></td>
                  <td><i class="fa-regular fa-clock me-1 text-muted"></i><?= $srv['estimated_days'] ?>d</td>
                  <td class="fw-bold text-success"><?= format_currency((float)$srv['selling_price']) ?></td>
                  <td class="fw-semibold text-danger"><?= format_currency((float)$srv['supplier_cost']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      <!-- ============================================== -->
      <!-- TAB 8: SECURITY & SESSION POLICY -->
      <!-- ============================================== -->
      <?php elseif ($activeTab === 'security'): ?>
        <form action="/settings/preferences" method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="target_tab" value="security">

          <div class="row g-4">
            <div class="col-lg-6">
              <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-shield-halved text-danger me-2"></i> Session &amp; Login Security Policy</h6>
              
              <div class="mb-3">
                <label class="form-label small fw-semibold">Inactivity Session Timeout (Minutes)</label>
                <input type="number" name="settings[session_timeout_minutes]" class="form-control" value="<?= e($settings['session_timeout_minutes'] ?? '120') ?>">
                <div class="form-text small">Staff will be prompted to log back in after inactivity.</div>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-semibold">Max Failed Login Attempts Before Lockout</label>
                <input type="number" name="settings[max_login_attempts]" class="form-control" value="<?= e($settings['max_login_attempts'] ?? '5') ?>">
                <div class="form-text small">Temporary security lockout for brute-force protection.</div>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-semibold">Brute-Force Lockout Duration (Minutes)</label>
                <input type="number" name="settings[lockout_duration_minutes]" class="form-control" value="<?= e($settings['lockout_duration_minutes'] ?? '15') ?>">
              </div>
            </div>

            <div class="col-lg-6">
              <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-key text-primary me-2"></i> Password Complexity Rules</h6>
              
              <div class="mb-3">
                <label class="form-label small fw-semibold">Minimum Password Length</label>
                <input type="number" name="settings[min_password_length]" class="form-control" value="<?= e($settings['min_password_length'] ?? '8') ?>">
              </div>

              <div class="p-3 bg-light rounded border mb-3">
                <div class="form-check form-switch mb-2">
                  <input class="form-check-input" type="checkbox" name="settings[require_special_char]" value="1" <?= ($settings['require_special_char'] ?? '1') === '1' ? 'checked' : '' ?>>
                  <label class="form-check-label small fw-semibold">Require Special Character &amp; Numbers</label>
                </div>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" name="settings[log_all_logins]" value="1" <?= ($settings['log_all_logins'] ?? '1') === '1' ? 'checked' : '' ?>>
                  <label class="form-check-label small fw-semibold">Log All Successful &amp; Failed Sign-ins to Audit Trail</label>
                </div>
              </div>
            </div>
          </div>

          <div class="pt-3 mt-4 border-top text-end">
            <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
              <i class="fa-solid fa-floppy-disk me-1"></i> Save Security Policies
            </button>
          </div>
        </form>
      <?php endif; ?>

      <!-- ============================================== -->
      <!-- TAB: APPLICATION STATUSES (Sir Feedback) -->
      <!-- ============================================== -->
      <?php if ($activeTab === 'statuses'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-tags text-primary me-2"></i> Application Statuses &amp; Lifecycle Stages</h6>
            <p class="text-muted small mb-0">Manage system and custom workflow statuses with configurable categories and colors.</p>
          </div>
          <button type="button" class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#addStatusModal">
            <i class="fa-solid fa-plus me-1"></i> Add Custom Status
          </button>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr class="small text-muted text-uppercase">
                <th>Order</th>
                <th>Status Name</th>
                <th>Category</th>
                <th>Badge Color</th>
                <th>Customer Visible</th>
                <th>Type</th>
                <th>State</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($applicationStatuses as $st): ?>
                <tr>
                  <td class="fw-bold text-muted"><?= (int)$st['display_order'] ?></td>
                  <td>
                    <span class="badge bg-<?= e($st['badge_color'] ?: 'primary') ?> text-white px-2 py-1">
                      <?= e($st['name']) ?>
                    </span>
                    <?php if (!empty($st['description'])): ?>
                      <div class="small text-muted mt-1"><?= e($st['description']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td><span class="badge bg-light text-dark border"><?= e($st['category']) ?></span></td>
                  <td><code><?= e($st['badge_color']) ?></code></td>
                  <td>
                    <?php if ($st['is_customer_visible']): ?>
                      <span class="text-success small fw-semibold"><i class="fa-solid fa-eye me-1"></i> Visible</span>
                    <?php else: ?>
                      <span class="text-muted small"><i class="fa-solid fa-eye-slash me-1"></i> Hidden</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($st['is_system']): ?>
                      <span class="badge bg-secondary-subtle text-secondary border">System Protected</span>
                    <?php else: ?>
                      <span class="badge bg-info-subtle text-info border">Custom</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($st['is_active']): ?>
                      <span class="badge bg-success-subtle text-success">Active</span>
                    <?php else: ?>
                      <span class="badge bg-danger-subtle text-danger">Inactive</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <?php if (!$st['is_system']): ?>
                      <form action="/settings/statuses/toggle" method="POST" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $st['id'] ?>">
                        <button type="submit" class="btn btn-outline-secondary btn-sm" title="Toggle Active / Inactive">
                          <i class="fa-solid fa-power-off"></i>
                        </button>
                      </form>
                    <?php else: ?>
                      <span class="text-muted small">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <!-- ============================================== -->
      <!-- TAB: WEBSITE THEMES & APPEARANCE (PHASE 9 & 10) -->
      <!-- ============================================== -->
      <?php if ($activeTab === 'appearance'): ?>
        <form action="/settings/preferences" method="POST" id="themeSettingsForm">
          <?= csrf_field() ?>
          <input type="hidden" name="target_tab" value="appearance">

          <div class="row g-4">
            <!-- Left Column: Theme Controls -->
            <div class="col-lg-7">
              <div class="p-4 bg-light rounded border mb-4">
                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-palette text-primary me-2"></i> Admin Portal Visual Branding &amp; Themes</h6>
                
                <div class="mb-3">
                  <label class="form-label small fw-semibold">Dashboard Luxury Theme Presets</label>
                  <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 shadow-sm" onclick="applyThemePreset('ocean-royal', '#1e40af', '#0891b2')">
                      <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#1e40af;"></span> Ocean Royal
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 shadow-sm" onclick="applyThemePreset('sunset-fusion', '#be185d', '#ea580c')">
                      <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#be185d;"></span> Sunset Fusion
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success d-flex align-items-center gap-1 shadow-sm" onclick="applyThemePreset('emerald-royal', '#065f46', '#0d9488')">
                      <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#065f46;"></span> Emerald Royal
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-dark d-flex align-items-center gap-1 shadow-sm" style="color:#7c3aed;border-color:#7c3aed;" onclick="applyThemePreset('violet-aurora', '#5b21b6', '#2563eb')">
                      <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#5b21b6;"></span> Violet Aurora
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 shadow-sm" onclick="applyThemePreset('crimson-midnight', '#991b1b', '#92400e')">
                      <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#991b1b;"></span> Crimson Midnight
                    </button>
                  </div>
                </div>
                
                <div class="row g-3 mb-3">
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Primary Brand Color</label>
                    <div class="input-group">
                      <input type="color" name="settings[theme_primary_color]" id="themePrimaryColor" class="form-control form-control-color" value="<?= e($settings['theme_primary_color'] ?? '#0284c7') ?>" oninput="syncColorInput('primary', this.value)">
                      <input type="text" id="themePrimaryColorHex" class="form-control" value="<?= e($settings['theme_primary_color'] ?? '#0284c7') ?>" oninput="syncColorHex('primary', this.value)">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Accent / Action Color</label>
                    <div class="input-group">
                      <input type="color" name="settings[theme_accent_color]" id="themeAccentColor" class="form-control form-control-color" value="<?= e($settings['theme_accent_color'] ?? '#059669') ?>" oninput="syncColorInput('accent', this.value)">
                      <input type="text" id="themeAccentColorHex" class="form-control" value="<?= e($settings['theme_accent_color'] ?? '#059669') ?>" oninput="syncColorHex('accent', this.value)">
                    </div>
                  </div>
                </div>

                <div class="row g-3 mb-3">
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Heading Typography Font</label>
                    <select name="settings[theme_font_family]" id="themeFontFamily" class="form-select" onchange="updateLiveThemePreview()">
                      <option value="'Times New Roman', Times, serif" <?= ($settings['theme_font_family'] ?? '') === "'Times New Roman', Times, serif" ? 'selected' : '' ?>>Times New Roman (Brand Heritage)</option>
                      <option value="'Plus Jakarta Sans', sans-serif" <?= ($settings['theme_font_family'] ?? '') === "'Plus Jakarta Sans', sans-serif" ? 'selected' : '' ?>>Plus Jakarta Sans (Modern)</option>
                      <option value="'Outfit', sans-serif" <?= ($settings['theme_font_family'] ?? '') === "'Outfit', sans-serif" ? 'selected' : '' ?>>Outfit (Geometric)</option>
                      <option value="'Inter', sans-serif" <?= ($settings['theme_font_family'] ?? '') === "'Inter', sans-serif" ? 'selected' : '' ?>>Inter (Clean)</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Corner Border Radius</label>
                    <select name="settings[theme_border_radius]" id="themeBorderRadius" class="form-select" onchange="updateLiveThemePreview()">
                      <option value="4px" <?= ($settings['theme_border_radius'] ?? '') === '4px' ? 'selected' : '' ?>>Sharp (4px)</option>
                      <option value="8px" <?= ($settings['theme_border_radius'] ?? '8px') === '8px' ? 'selected' : '' ?>>Standard (8px)</option>
                      <option value="12px" <?= ($settings['theme_border_radius'] ?? '') === '12px' ? 'selected' : '' ?>>Rounded (12px)</option>
                      <option value="16px" <?= ($settings['theme_border_radius'] ?? '') === '16px' ? 'selected' : '' ?>>Pill (16px)</option>
                    </select>
                  </div>
                </div>

                <div class="row g-3 mb-2">
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Portal Theme Mode</label>
                    <select name="settings[theme_mode]" id="themeModeSelect" class="form-select" onchange="updateLiveThemePreview()">
                      <option value="light" <?= ($settings['theme_mode'] ?? 'light') === 'light' ? 'selected' : '' ?>>Clean Light Mode</option>
                      <option value="dark" <?= ($settings['theme_mode'] ?? '') === 'dark' ? 'selected' : '' ?>>Executive Dark Mode</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Sidebar Theme Style</label>
                    <select name="settings[theme_sidebar_style]" class="form-select">
                      <option value="dark" <?= ($settings['theme_sidebar_style'] ?? 'dark') === 'dark' ? 'selected' : '' ?>>Deep Slate Dark</option>
                      <option value="light" <?= ($settings['theme_sidebar_style'] ?? '') === 'light' ? 'selected' : '' ?>>Modern Crisp Light</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Email Template Theme Styling -->
              <div class="p-4 bg-light rounded border mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                  <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-envelope-open-text text-info me-2"></i> Email Notification Branding</h6>
                  <button type="button" class="btn btn-outline-info btn-sm" onclick="previewEmailTemplate(1)"><i class="fa-solid fa-eye me-1"></i> Preview Live Email</button>
                </div>

                <div class="row g-3 mb-2">
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Email Header Background</label>
                    <div class="input-group">
                      <input type="color" name="settings[email_theme_header_bg]" id="emailHeaderBg" class="form-control form-control-color" value="<?= e($settings['email_theme_header_bg'] ?? '#0f172a') ?>">
                      <input type="text" class="form-control" value="<?= e($settings['email_theme_header_bg'] ?? '#0f172a') ?>" oninput="document.getElementById('emailHeaderBg').value=this.value">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Email Accent &amp; Button Color</label>
                    <div class="input-group">
                      <input type="color" name="settings[email_theme_color]" id="emailAccentColor" class="form-control form-control-color" value="<?= e($settings['email_theme_color'] ?? '#0284c7') ?>">
                      <input type="text" class="form-control" value="<?= e($settings['email_theme_color'] ?? '#0284c7') ?>" oninput="document.getElementById('emailAccentColor').value=this.value">
                    </div>
                  </div>
                </div>
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4 fw-semibold shadow"><i class="fa-solid fa-floppy-disk me-1"></i> Save Theme Settings</button>
                <button type="button" class="btn btn-light border px-3" onclick="resetThemeDefaults()"><i class="fa-solid fa-rotate-left me-1"></i> Reset Defaults</button>
              </div>
            </div>

            <!-- Right Column: Live Real-Time Interactive Preview -->
            <div class="col-lg-5">
              <div class="sticky-top" style="top: 20px;">
                <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-wand-magic-sparkles text-warning me-1"></i> Live Real-Time Preview</h6>
                <p class="text-muted small mb-3">Changes appear immediately in the mockup card below before saving.</p>

                <div id="previewCardContainer" class="p-4 bg-white rounded border shadow-sm" style="transition: all 0.3s ease;">
                  <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                    <span id="previewBrandName" class="brand-font fw-bold fs-5" style="color: <?= e($settings['theme_primary_color'] ?? '#0284c7') ?>;">MS TRAVEL HUB</span>
                    <span id="previewBadge" class="badge" style="background-color: <?= e($settings['theme_accent_color'] ?? '#059669') ?>;">Verified</span>
                  </div>

                  <h6 id="previewHeading" class="brand-font fw-bold mb-2">Visa Application Status #APP-2026-0042</h6>
                  <p class="text-muted small mb-3">Your visa document verification is progressing as expected with all consular requirements fulfilled.</p>

                  <div class="d-flex gap-2 mb-3">
                    <button type="button" id="previewPrimaryBtn" class="btn btn-sm text-white px-3" style="background-color: <?= e($settings['theme_primary_color'] ?? '#0284c7') ?>; border-radius: <?= e($settings['theme_border_radius'] ?? '8px') ?>;">
                      Primary Action
                    </button>
                    <button type="button" id="previewOutlineBtn" class="btn btn-sm btn-outline-secondary px-3" style="border-radius: <?= e($settings['theme_border_radius'] ?? '8px') ?>;">
                      Secondary
                    </button>
                  </div>

                  <div class="p-2 bg-light rounded small mb-0">
                    <i class="fa-solid fa-circle-info text-primary me-1"></i>
                    Applied Heading Font: <strong id="previewFontLabel"><?= e($settings['theme_font_family'] ?? 'Times New Roman') ?></strong>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      <?php endif; ?>

    </div>
  </div>
</div>

<!-- MODAL: ADD COUNTRY -->
<div class="modal fade" id="addCountryModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-earth-americas me-2"></i> Add Destination Country</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/settings/country" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="row g-2 mb-3">
            <div class="col-8">
              <label class="form-label small fw-semibold">Country Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Germany" required>
            </div>
            <div class="col-4">
              <label class="form-label small fw-semibold">ISO Code <span class="text-danger">*</span></label>
              <input type="text" name="iso_code" class="form-control" placeholder="DE" maxlength="3" required>
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-4">
              <label class="form-label small fw-semibold">Flag Emoji</label>
              <input type="text" name="flag_emoji" class="form-control text-center fs-4" value="🇩🇪">
            </div>
            <div class="col-4">
              <label class="form-label small fw-semibold">Currency</label>
              <input type="text" name="currency" class="form-control" value="EUR">
            </div>
            <div class="col-4">
              <label class="form-label small fw-semibold">Region</label>
              <input type="text" name="region" class="form-control" value="Europe">
            </div>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Embassy / Consular Clearing Info</label>
            <input type="text" name="embassy_info" class="form-control" placeholder="e.g. German Embassy & VFS Global">
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">Save Country</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: ADD VISA SERVICE -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-passport me-2"></i> Add Visa Package</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/settings/service" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Destination Country <span class="text-danger">*</span></label>
              <select name="country_id" class="form-select" required>
                <?php foreach ($countries as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= $c['flag_emoji'] ?> <?= e($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Visa Category <span class="text-danger">*</span></label>
              <select name="category_id" class="form-select" required>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Package Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Germany Tourist Visa (90 Days)" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Selling Price ($) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="selling_price" class="form-control" placeholder="250.00" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Supplier Cost ($)</label>
              <input type="number" step="0.01" name="supplier_cost" class="form-control" placeholder="120.00">
            </div>
          </div>
          <div class="row g-2 mb-0">
            <div class="col-6">
              <label class="form-label small fw-semibold">Entry Type</label>
              <select name="entry_type" class="form-select">
                <option value="Single Entry">Single Entry</option>
                <option value="Multiple Entry">Multiple Entry</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Target Turnaround (Days)</label>
              <input type="number" name="estimated_days" class="form-control" value="7">
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">Create Package</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: ADD DOCUMENT TYPE -->
<div class="modal fade" id="addDocTypeModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-file-shield me-2"></i> Add Document Type</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/settings/doc-type" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Document Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Schengen Travel Insurance" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Category</label>
              <select name="category" class="form-select">
                <option value="Personal">Personal</option>
                <option value="Financial">Financial</option>
                <option value="Employment">Employment</option>
                <option value="Travel">Travel &amp; Stay</option>
                <option value="Legal">Legal &amp; Consular</option>
              </select>
            </div>
            <div class="col-6 d-flex align-items-center pt-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="requires_expiry" value="1" id="reqExp">
                <label class="form-check-label small fw-semibold" for="reqExp">Requires Expiry Date</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">Save Document Type</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: ADD CUSTOM STATUS (Sir Feedback) -->
<div class="modal fade" id="addStatusModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-tags me-2"></i> Add Custom Application Status</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/settings/statuses/add" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Status Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Awaiting Ministry Attestation" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Category</label>
              <select name="category" class="form-select">
                <option value="Initial">Initial Registration</option>
                <option value="Documentation">Documentation</option>
                <option value="Processing" selected>Processing &amp; Cleared</option>
                <option value="Decision">Decision &amp; Outcome</option>
                <option value="Post-Decision">Post-Decision</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Badge Color</label>
              <select name="badge_color" class="form-select">
                <option value="primary">Primary (Blue)</option>
                <option value="success">Success (Green)</option>
                <option value="warning">Warning (Yellow/Amber)</option>
                <option value="danger">Danger (Red)</option>
                <option value="info">Info (Cyan)</option>
                <option value="secondary">Secondary (Gray)</option>
                <option value="dark">Dark (Black/Dark Gray)</option>
              </select>
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Display Order</label>
              <input type="number" name="display_order" class="form-control" value="50">
            </div>
            <div class="col-6 d-flex align-items-center pt-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_customer_visible" value="1" id="custVis" checked>
                <label class="form-check-label small fw-semibold" for="custVis">Visible to Customer</label>
              </div>
            </div>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Description / Notes</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Optional notes for staff..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">Create Status</button>
        </div>
      </form>
    </div>
  </div>
<!-- MODAL: EMAIL TEMPLATE LIVE PREVIEW -->
<div class="modal fade" id="emailPreviewModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-eye text-primary me-2"></i> Email Live Preview: <span id="previewTmplName" class="text-primary"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="mb-3">
          <div class="text-muted small fw-semibold">Subject Preview:</div>
          <div class="fw-bold fs-6 text-dark p-2 bg-light rounded border" id="previewSubjectText"></div>
        </div>
        <div>
          <div class="text-muted small fw-semibold mb-1">Rendered Email Layout:</div>
          <iframe id="previewEmailFrame" style="width: 100%; height: 420px; border: 1px solid #e2e8f0; border-radius: 8px;"></iframe>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary px-4 fw-semibold" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL: SEND TEST EMAIL -->
<div class="modal fade" id="sendTestEmailModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-paper-plane text-info me-2"></i> Send Test Email</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <input type="hidden" id="testEmailTmplId">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Template:</label>
          <div class="fw-bold text-dark" id="testEmailTmplName"></div>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Recipient Email Address <span class="text-danger">*</span></label>
          <input type="email" id="testEmailAddress" class="form-control" placeholder="your.name@example.com" value="<?= e($currentUser['email'] ?? '') ?>" required>
        </div>
        <div id="testEmailResult" style="display:none;" class="alert py-2 px-3 small"></div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-info text-white px-4 fw-semibold" id="sendTestEmailBtn" onclick="submitTestEmail()">
          <i class="fa-solid fa-paper-plane me-1"></i> Send Now
        </button>
      </div>
    </div>
  </div>
</div>

<script>
function previewEmailTemplate(tmplId) {
  fetch('/settings/template/preview?id=' + encodeURIComponent(tmplId))
    .then(r => r.json())
    .then(data => {
      if (!data.success) {
        alert(data.error || 'Failed to load preview');
        return;
      }
      document.getElementById('previewTmplName').innerText = data.template_name;
      document.getElementById('previewSubjectText').innerText = data.subject;
      const iframe = document.getElementById('previewEmailFrame');
      iframe.srcdoc = data.html;
      new bootstrap.Modal(document.getElementById('emailPreviewModal')).show();
    })
    .catch(err => {
      alert('Error fetching email template preview.');
    });
}

function openTestEmailModal(tmplId, tmplName) {
  document.getElementById('testEmailTmplId').value = tmplId;
  document.getElementById('testEmailTmplName').innerText = tmplName;
  document.getElementById('testEmailResult').style.display = 'none';
  new bootstrap.Modal(document.getElementById('sendTestEmailModal')).show();
}

function submitTestEmail() {
  const tmplId = document.getElementById('testEmailTmplId').value;
  const email = document.getElementById('testEmailAddress').value;
  const btn = document.getElementById('sendTestEmailBtn');
  const resultDiv = document.getElementById('testEmailResult');

  if (!email) {
    alert('Please enter a recipient email.');
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Sending...';

  const formData = new FormData();
  formData.append('csrf_token', '<?= csrf_token() ?>');
  formData.append('template_id', tmplId);
  formData.append('test_email', email);

  fetch('/settings/template/test', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Send Now';
    resultDiv.style.display = 'block';
    if (data.success) {
      resultDiv.className = 'alert alert-success py-2 px-3 small';
      resultDiv.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> ' + (data.message || 'Test email dispatched successfully.');
    } else {
      resultDiv.className = 'alert alert-danger py-2 px-3 small';
      resultDiv.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i> ' + (data.error || 'Failed to send test email.');
    }
  })
  .catch(err => {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Send Now';
    resultDiv.style.display = 'block';
    resultDiv.className = 'alert alert-danger py-2 px-3 small';
    resultDiv.innerText = 'Network error while dispatching test email.';
  });
}

// Live Theme & Color Sync Functions
function applyThemePreset(themeId, primaryHex, accentHex) {
  document.documentElement.setAttribute('data-theme', themeId);
  try { localStorage.setItem('vt_theme', themeId); } catch(e){}
  if (document.getElementById('themePrimaryColor')) document.getElementById('themePrimaryColor').value = primaryHex;
  if (document.getElementById('themePrimaryColorHex')) document.getElementById('themePrimaryColorHex').value = primaryHex;
  if (document.getElementById('themeAccentColor')) document.getElementById('themeAccentColor').value = accentHex;
  if (document.getElementById('themeAccentColorHex')) document.getElementById('themeAccentColorHex').value = accentHex;
  updateLiveThemePreview();
}

function syncColorInput(type, hex) {
  if (type === 'primary') {
    document.getElementById('themePrimaryColorHex').value = hex;
  } else if (type === 'accent') {
    document.getElementById('themeAccentColorHex').value = hex;
  }
  updateLiveThemePreview();
}

function syncColorHex(type, hex) {
  if (/^#[0-9A-F]{6}$/i.test(hex)) {
    if (type === 'primary') {
      document.getElementById('themePrimaryColor').value = hex;
    } else if (type === 'accent') {
      document.getElementById('themeAccentColor').value = hex;
    }
    updateLiveThemePreview();
  }
}

function updateLiveThemePreview() {
  const primaryColor = document.getElementById('themePrimaryColor') ? document.getElementById('themePrimaryColor').value : '#0284c7';
  const accentColor = document.getElementById('themeAccentColor') ? document.getElementById('themeAccentColor').value : '#059669';
  const fontFamily = document.getElementById('themeFontFamily') ? document.getElementById('themeFontFamily').value : "'Times New Roman', Times, serif";
  const radius = document.getElementById('themeBorderRadius') ? document.getElementById('themeBorderRadius').value : '8px';
  const mode = document.getElementById('themeModeSelect') ? document.getElementById('themeModeSelect').value : 'light';

  // Update Preview Card elements
  const brandName = document.getElementById('previewBrandName');
  const badge = document.getElementById('previewBadge');
  const primaryBtn = document.getElementById('previewPrimaryBtn');
  const outlineBtn = document.getElementById('previewOutlineBtn');
  const fontLabel = document.getElementById('previewFontLabel');
  const heading = document.getElementById('previewHeading');
  const cardContainer = document.getElementById('previewCardContainer');

  if (brandName) brandName.style.color = primaryColor;
  if (brandName) brandName.style.fontFamily = fontFamily;
  if (heading) heading.style.fontFamily = fontFamily;
  if (badge) badge.style.backgroundColor = accentColor;
  if (primaryBtn) {
    primaryBtn.style.backgroundColor = primaryColor;
    primaryBtn.style.borderRadius = radius;
  }
  if (outlineBtn) outlineBtn.style.borderRadius = radius;
  if (fontLabel) fontLabel.textContent = fontFamily.replace(/['",]/g, '').trim();

  if (cardContainer) {
    if (mode === 'dark') {
      cardContainer.style.backgroundColor = '#0f172a';
      cardContainer.style.color = '#f8fafc';
    } else {
      cardContainer.style.backgroundColor = '#ffffff';
      cardContainer.style.color = '#1e293b';
    }
  }

  // Also update live CSS variables on document
  document.documentElement.style.setProperty('--primary-color', primaryColor);
  document.documentElement.style.setProperty('--bs-primary', primaryColor);
  document.documentElement.style.setProperty('--accent-color', accentColor);
  document.documentElement.style.setProperty('--border-radius-base', radius);
  document.documentElement.style.setProperty('--font-heading', fontFamily);

  // Store in localStorage for client persistence
  try {
    localStorage.setItem('user_theme_primary', primaryColor);
    localStorage.setItem('user_theme_accent', accentColor);
    localStorage.setItem('user_theme_radius', radius);
    localStorage.setItem('user_theme_font', fontFamily);
    localStorage.setItem('user_theme_mode', mode);
  } catch (e) {}
}

function resetThemeDefaults() {
  if (document.getElementById('themePrimaryColor')) document.getElementById('themePrimaryColor').value = '#0284c7';
  if (document.getElementById('themePrimaryColorHex')) document.getElementById('themePrimaryColorHex').value = '#0284c7';
  if (document.getElementById('themeAccentColor')) document.getElementById('themeAccentColor').value = '#059669';
  if (document.getElementById('themeAccentColorHex')) document.getElementById('themeAccentColorHex').value = '#059669';
  if (document.getElementById('themeFontFamily')) document.getElementById('themeFontFamily').value = "'Times New Roman', Times, serif";
  if (document.getElementById('themeBorderRadius')) document.getElementById('themeBorderRadius').value = '8px';
  if (document.getElementById('themeModeSelect')) document.getElementById('themeModeSelect').value = 'light';
  updateLiveThemePreview();
}
</script>

<!-- ============================================== -->
<!-- EMAIL TEMPLATE PREVIEW MODAL -->
<!-- ============================================== -->
<div class="modal fade" id="emailPreviewModal" tabindex="-1" aria-labelledby="emailPreviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content shadow-lg border-0 rounded-3">
      <div class="modal-header bg-dark text-white py-3">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid fa-envelope-open text-info fs-5"></i>
          <div>
            <h6 class="modal-title fw-bold mb-0 text-white" id="emailPreviewModalLabel">Email Template Preview</h6>
            <div class="small text-white-50" id="emailPreviewSubject">Loading subject...</div>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0" style="background-color: #f1f5f9; min-height: 480px;">
        <div class="p-3 bg-white border-bottom small d-flex justify-content-between align-items-center text-muted">
          <span><i class="fa-solid fa-desktop me-1 text-primary"></i> Responsive Desktop &amp; Mobile Email Rendering</span>
          <span class="badge bg-primary-subtle text-primary fw-semibold"><i class="fa-solid fa-palette me-1"></i> Branded Output</span>
        </div>
        <div id="emailPreviewContainer" class="p-3 d-flex justify-content-center">
          <div id="emailPreviewLoading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="text-muted small mt-2">Generating live template preview...</div>
          </div>
          <iframe id="emailPreviewIframe" style="width: 100%; min-height: 540px; border: none; display: none; background: #f1f5f9;" title="Email Preview"></iframe>
        </div>
      </div>
      <div class="modal-footer bg-light py-2">
        <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- ============================================== -->
<!-- SEND TEST EMAIL MODAL -->
<!-- ============================================== -->
<div class="modal fade" id="sendTestEmailModal" tabindex="-1" aria-labelledby="sendTestEmailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow border-0 rounded-3">
      <div class="modal-header bg-light py-3">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid fa-paper-plane text-primary"></i>
          <h6 class="modal-title fw-bold mb-0" id="sendTestEmailModalLabel">Send Test Notification</h6>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <input type="hidden" id="testEmailTemplateId" value="0">
        <div class="mb-3">
          <label class="form-label small fw-semibold text-dark">Template to Dispatch</label>
          <input type="text" id="testEmailTemplateTitle" class="form-control form-control-sm bg-light" readonly value="">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold text-dark">Recipient Email Address <span class="text-danger">*</span></label>
          <input type="email" id="testEmailRecipientInput" class="form-control" placeholder="your-email@example.com" value="<?= e(auth_user()['email'] ?? '') ?>" required>
          <div class="form-text small" style="font-size: 0.75rem;">Will dispatch the live formatted email with sample data.</div>
        </div>
        <div id="testEmailAlert" class="alert d-none small mb-0" role="alert"></div>
      </div>
      <div class="modal-footer bg-light py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm px-3 fw-semibold" id="confirmSendTestBtn" onclick="executeSendTestEmail()">
          <span class="spinner-border spinner-border-sm me-1 d-none" id="sendTestSpinner"></span>
          <i class="fa-solid fa-paper-plane me-1" id="sendTestIcon"></i> Send Test Now
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Email Branding Live Synchronization Functions
function syncEmailHeaderColor(hex) {
  const hexInput = document.getElementById('emailHeaderBgHex');
  if (hexInput) hexInput.value = hex;
  updateEmailHeaderPreview();
}

function syncEmailHeaderHex(hex) {
  if (/^#[0-9A-F]{6}$/i.test(hex)) {
    const colorInput = document.getElementById('emailHeaderBgColor');
    if (colorInput) colorInput.value = hex;
    updateEmailHeaderPreview();
  }
}

function syncEmailThemeColor(hex) {
  const hexInput = document.getElementById('emailThemeColorHex');
  if (hexInput) hexInput.value = hex;
  updateEmailHeaderPreview();
}

function syncEmailThemeHex(hex) {
  if (/^#[0-9A-F]{6}$/i.test(hex)) {
    const colorInput = document.getElementById('emailThemeColor');
    if (colorInput) colorInput.value = hex;
    updateEmailHeaderPreview();
  }
}

function updateEmailHeaderPreview() {
  const headerBg = document.getElementById('emailHeaderBgHex') ? document.getElementById('emailHeaderBgHex').value : '#0f172a';
  const themeColor = document.getElementById('emailThemeColorHex') ? document.getElementById('emailThemeColorHex').value : '#2563eb';
  const logoUrl = document.getElementById('emailThemeLogoInput') ? document.getElementById('emailThemeLogoInput').value.trim() : '/assets/images/logo.png';
  
  const headerStrip = document.getElementById('liveEmailHeaderPreview');
  const btn = document.getElementById('previewEmailBtn');
  const logoImg = document.getElementById('previewEmailLogoImg');

  if (headerStrip) {
    headerStrip.style.backgroundColor = headerBg;
    headerStrip.style.borderBottomColor = themeColor;
  }
  if (btn) {
    btn.style.backgroundColor = themeColor;
  }
  if (logoImg && logoUrl) {
    logoImg.src = logoUrl;
  }
}

function resetEmailLogo() {
  const input = document.getElementById('emailThemeLogoInput');
  if (input) {
    input.value = '/assets/images/logo.png';
    updateEmailHeaderPreview();
  }
}

// Live Preview of any template
function previewEmailTemplate(templateId) {
  const modal = new bootstrap.Modal(document.getElementById('emailPreviewModal'));
  const titleEl = document.getElementById('emailPreviewModalLabel');
  const subjEl = document.getElementById('emailPreviewSubject');
  const iframe = document.getElementById('emailPreviewIframe');
  const loader = document.getElementById('emailPreviewLoading');

  if (titleEl) titleEl.textContent = 'Loading Template...';
  if (subjEl) subjEl.textContent = '';
  if (loader) loader.style.display = 'block';
  if (iframe) iframe.style.display = 'none';

  modal.show();

  fetch('/settings/preview-template?id=' + templateId)
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        if (titleEl) titleEl.textContent = 'Preview: ' + (data.template_name || 'Email Template');
        if (subjEl) subjEl.textContent = 'Subject: ' + (data.subject || '');
        if (loader) loader.style.display = 'none';
        if (iframe) {
          iframe.style.display = 'block';
          const doc = iframe.contentDocument || iframe.contentWindow.document;
          doc.open();
          doc.write(data.html);
          doc.close();
        }
      } else {
        alert(data.error || 'Failed to load preview');
      }
    })
    .catch(err => {
      if (loader) loader.innerHTML = '<div class="text-danger py-4">Error loading email preview: ' + err.message + '</div>';
    });
}

function previewBrandedEmail() {
  // Previews template #1 (or first available template) to view branding
  const firstTmpl = <?= !empty($templates[0]['id']) ? (int)$templates[0]['id'] : 1 ?>;
  previewEmailTemplate(firstTmpl);
}

// Send Test Email Modal
function openTestEmailModal(templateId, templateTitle) {
  document.getElementById('testEmailTemplateId').value = templateId;
  document.getElementById('testEmailTemplateTitle').value = templateTitle;
  const alertEl = document.getElementById('testEmailAlert');
  if (alertEl) {
    alertEl.className = 'alert d-none small mb-0';
    alertEl.textContent = '';
  }
  const modal = new bootstrap.Modal(document.getElementById('sendTestEmailModal'));
  modal.show();
}

function executeSendTestEmail() {
  const id = document.getElementById('testEmailTemplateId').value;
  const email = document.getElementById('testEmailRecipientInput').value.trim();
  const alertEl = document.getElementById('testEmailAlert');
  const btn = document.getElementById('confirmSendTestBtn');
  const spinner = document.getElementById('sendTestSpinner');
  const icon = document.getElementById('sendTestIcon');

  if (!email) {
    alertEl.className = 'alert alert-danger small mb-0';
    alertEl.textContent = 'Please enter a recipient email address.';
    alertEl.classList.remove('d-none');
    return;
  }

  btn.disabled = true;
  if (spinner) spinner.classList.remove('d-none');
  if (icon) icon.classList.add('d-none');

  const formData = new FormData();
  formData.append('template_id', id);
  formData.append('test_email', email);
  formData.append('csrf_token', '<?= csrf_token() ?>');

  fetch('/settings/send-test-email', {
    method: 'POST',
    body: formData
  })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      if (spinner) spinner.classList.add('d-none');
      if (icon) icon.classList.remove('d-none');

      if (data.success) {
        alertEl.className = 'alert alert-success small mb-0';
        alertEl.textContent = data.message || 'Test email dispatched successfully!';
        alertEl.classList.remove('d-none');
      } else {
        alertEl.className = 'alert alert-danger small mb-0';
        alertEl.textContent = data.error || data.message || 'Failed to dispatch test email.';
        alertEl.classList.remove('d-none');
      }
    })
    .catch(err => {
      btn.disabled = false;
      if (spinner) spinner.classList.add('d-none');
      if (icon) icon.classList.remove('d-none');
      alertEl.className = 'alert alert-danger small mb-0';
      alertEl.textContent = 'Network error: ' + err.message;
      alertEl.classList.remove('d-none');
    });
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
