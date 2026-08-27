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
      <!-- TAB 5: EMAIL NOTIFICATION TEMPLATES -->
      <!-- ============================================== -->
      <?php elseif ($activeTab === 'templates'): ?>
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

                    <div class="text-end">
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
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
