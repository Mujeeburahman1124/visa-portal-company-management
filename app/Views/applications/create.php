<?php
$pageTitle = 'New Visa Application — VISA TRACK';
$flash = get_flash();
$currentUser = auth_user();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
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

  <!-- Guided Header & Breadcrumb -->
  <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
    <div class="d-flex align-items-center gap-3">
      <a href="/applications" class="btn btn-outline-secondary btn-sm bg-white" title="Back to Applications">
        <i class="fa-solid fa-arrow-left"></i>
      </a>
      <div>
        <h3 class="fw-bold brand-font text-dark mb-0">New Visa Application</h3>
        <p class="text-muted small mb-0">Initiate a tracked visa case, assign case officers, upload supporting documents, and process optional payments.</p>
      </div>
    </div>
    <div class="d-none d-md-block">
      <span class="badge bg-primary-subtle text-primary px-3 py-2 fw-semibold">
        <i class="fa-solid fa-shield-halved me-1"></i> Auto Checklist &amp; Health Engine
      </span>
    </div>
  </div>

  <!-- Visual Multi-Step Form Stepper -->
  <div class="form-stepper-header d-none d-md-flex mb-4">
    <div class="stepper-node active">
      <div class="stepper-circle"><i class="fa-solid fa-user"></i></div>
      <span class="stepper-label">1. Applicant</span>
    </div>
    <div class="stepper-node active">
      <div class="stepper-circle"><i class="fa-solid fa-passport"></i></div>
      <span class="stepper-label">2. Visa Service</span>
    </div>
    <div class="stepper-node active">
      <div class="stepper-circle"><i class="fa-solid fa-folder-open"></i></div>
      <span class="stepper-label">3. Documents</span>
    </div>
    <div class="stepper-node active">
      <div class="stepper-circle"><i class="fa-solid fa-credit-card"></i></div>
      <span class="stepper-label">4. Payment</span>
    </div>
    <div class="stepper-node active">
      <div class="stepper-circle"><i class="fa-solid fa-check"></i></div>
      <span class="stepper-label">5. Complete</span>
    </div>
  </div>

  <!-- Duplicate Application Alert Container (AJAX populated) -->
  <div id="duplicateWarningBox" class="alert alert-warning border-0 shadow-sm d-none mb-4" role="alert">
    <div class="d-flex align-items-start gap-3">
      <div class="rounded-circle bg-warning bg-opacity-25 text-warning p-2 mt-1">
        <i class="fa-solid fa-triangle-exclamation fs-5"></i>
      </div>
      <div>
        <div class="fw-bold fs-6 text-dark" id="duplicateWarningTitle">Potential Duplicate Application Detected</div>
        <div class="small text-dark" id="duplicateWarningMsg"></div>
      </div>
    </div>
  </div>

  <form action="/applications/store" method="POST" id="createAppForm" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="row g-4">
      <!-- Left Column: Core Application Data -->
      <div class="col-lg-8">
        <!-- Section 1: Customer / Applicant Selection -->
        <div class="card card-enterprise mb-4 shadow-sm border">
          <div class="card-header bg-white d-flex align-items-center justify-content-between py-3 border-bottom">
            <span class="fw-bold small text-uppercase text-secondary">
              <i class="fa-solid fa-user-check text-primary me-2"></i> 1. Applicant Selection &amp; Identity
            </span>
            <a href="/customers/create" target="_blank" class="btn btn-outline-primary btn-sm py-1 px-2" style="font-size: 0.78rem;">
              <i class="fa-solid fa-user-plus me-1"></i> Register New Applicant
            </a>
          </div>
          <div class="card-body p-4">
            <div class="mb-3">
              <label for="customerSelect" class="form-label small fw-semibold text-secondary">Select Registered Applicant <span class="text-danger">*</span></label>
              <select name="customer_id" id="customerSelect" class="form-select" required onchange="checkDuplicateApplication()">
                <option value="">-- Choose Applicant (Name / Code / Passport) --</option>
                <?php foreach ($customers as $c): ?>
                  <option value="<?= $c['id'] ?>">
                    <?= e($c['customer_code']) ?> &bull; <?= e($c['full_name']) ?> (<?= e($c['nationality']) ?>) &mdash; Passport: <?= e($c['passport_number'] ?: 'N/A') ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text small text-muted">Selecting an applicant auto-links their primary passport and identity record to this visa file.</div>
            </div>
          </div>
        </div>

        <!-- Section 2: Visa Service Package (Part 5, 6, 7) -->
        <div class="card card-enterprise mb-4 shadow-sm border">
          <div class="card-header bg-white py-3 border-bottom">
            <span class="fw-bold small text-uppercase text-secondary">
              <i class="fa-solid fa-passport text-primary me-2"></i> 2. Destination Country &amp; Visa Package
            </span>
          </div>
          <div class="card-body p-4">
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Destination Country <span class="text-danger">*</span></label>
                <select id="countryFilterSelect" class="form-select" onchange="filterVisaPackages()">
                  <option value="">-- All Destination Countries --</option>
                  <?php foreach ($countries as $ct): ?>
                    <option value="<?= $ct['id'] ?>"><?= $ct['flag_emoji'] ?> <?= e($ct['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Visa Category</label>
                <select id="categoryFilterSelect" class="form-select" onchange="filterVisaPackages()">
                  <option value="">-- All Categories (Visit / Tourist / Work / etc.) --</option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="mb-3">
              <label for="serviceSelect" class="form-label small fw-semibold text-secondary">Visa Service Package / Type <span class="text-danger">*</span></label>
              <select name="visa_service_id" id="serviceSelect" class="form-select" required onchange="onServiceChanged();">
                <option value="">-- Choose Visa Type / Duration / Entry --</option>
                <?php foreach ($services as $srv): ?>
                  <option value="<?= $srv['id'] ?>" 
                          data-country-id="<?= $srv['country_id'] ?>"
                          data-category-id="<?= $srv['category_id'] ?? '' ?>"
                          data-price="<?= $srv['selling_price'] ?>"
                          data-cost="<?= $srv['supplier_cost'] ?>"
                          data-tax="<?= $srv['tax_rate'] ?>"
                          data-days="<?= $srv['estimated_days'] ?>"
                          data-entry="<?= e($srv['entry_type']) ?>">
                    <?= $srv['flag_emoji'] ?> <?= e($srv['country_name']) ?> &mdash; <?= e($srv['name']) ?> (<?= e($srv['duration'] ?? 'Standard') ?> &bull; <?= e($srv['entry_type']) ?> &bull; $<?= number_format((float)$srv['selling_price'], 2) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Estimated Travel Date</label>
                <input type="date" name="travel_date" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Proposed Return Date</label>
                <input type="date" name="return_date" class="form-control">
              </div>
            </div>
          </div>
        </div>

        <!-- Section 3: Supporting Documents Upload Vault (Part 2, 3, 4) -->
        <div class="card card-enterprise mb-4 shadow-sm border">
          <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-bottom">
            <span class="fw-bold small text-uppercase text-secondary">
              <i class="fa-solid fa-folder-open text-primary me-2"></i> 3. Supporting Documents (Unlimited Multiple Uploads)
            </span>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addAppDocRow()">
              <i class="fa-solid fa-plus me-1"></i> Add Another File
            </button>
          </div>
          <div class="card-body p-4">
            <p class="text-muted small mb-3">Attach passport copies, bank statements, photos, employment letters, travel insurance, or consular forms.</p>
            <div id="appDocUploadContainer">
              <div class="row g-2 mb-2 app-doc-row align-items-center bg-light p-2 rounded border">
                <div class="col-md-4">
                  <label class="form-label small fw-semibold mb-1">Document Type</label>
                  <select name="document_types[]" class="form-select form-select-sm">
                    <option value="">-- Select Type --</option>
                    <?php foreach ($docTypes as $dt): ?>
                      <option value="<?= $dt['id'] ?>"><?= e($dt['name']) ?> (<?= e($dt['category']) ?>)</option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-semibold mb-1">Custom Title / Month</label>
                  <input type="text" name="document_titles[]" class="form-control form-control-sm" placeholder="e.g. 6 Months Bank Statement">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold mb-1">File (PDF/JPG/PNG)</label>
                  <input type="file" name="application_documents[]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="col-md-1 text-end pt-3">
                  <button type="button" class="btn btn-outline-danger btn-sm p-1 py-0" onclick="this.closest('.app-doc-row').remove()" title="Remove row">
                    <i class="fa-solid fa-xmark"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Section 4: Notes & Instructions -->
        <div class="card card-enterprise mb-4 shadow-sm border">
          <div class="card-header bg-white py-3 border-bottom">
            <span class="fw-bold small text-uppercase text-secondary">
              <i class="fa-solid fa-comment-dots text-primary me-2"></i> 4. Internal &amp; Applicant Notes
            </span>
          </div>
          <div class="card-body p-4">
            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">Internal Operational Notes <small class="text-muted">(Confidential to staff)</small></label>
              <textarea name="internal_notes" class="form-control" rows="3" placeholder="Add confidential operational notes, consulate submission specifics, or processing instructions..."></textarea>
            </div>
            <div class="mb-0">
              <label class="form-label small fw-semibold text-secondary">Customer Instructions / Remarks</label>
              <textarea name="customer_notes" class="form-control" rows="2" placeholder="Notes that can be seen by the applicant in their tracking portal..."></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Operational Setup & Financials -->
      <div class="col-lg-4">
        <!-- Application Setup -->
        <div class="card card-enterprise mb-4 shadow-sm border">
          <div class="card-header bg-white py-3 border-bottom">
            <span class="fw-bold small text-uppercase text-secondary">
              <i class="fa-solid fa-sliders text-primary me-2"></i> Processing Setup
            </span>
          </div>
          <div class="card-body p-4">
            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">Priority Level <span class="text-danger">*</span></label>
              <select name="priority" class="form-select" required>
                <option value="Normal">Normal Priority</option>
                <option value="High">High Priority</option>
                <option value="Urgent">Urgent Priority</option>
                <option value="Critical">Critical Priority</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">Assigned Case Officer</label>
              <select name="assigned_staff_id" class="form-select">
                <option value="">-- Assign Staff Member --</option>
                <?php foreach ($staffMembers as $stf): ?>
                  <option value="<?= $stf['id'] ?>" <?= ((int)($currentUser['id'] ?? 0)) === (int)$stf['id'] ? 'selected' : '' ?>>
                    <?= e($stf['name']) ?> (<?= e($stf['designation'] ?? 'Staff') ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">Processing Branch <span class="text-danger">*</span></label>
              <select name="branch_id" class="form-select" required>
                <?php foreach ($branches as $br): ?>
                  <option value="<?= $br['id'] ?>"><?= e($br['name']) ?> (<?= e($br['city']) ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label small fw-semibold text-secondary">Supplier Reference #</label>
                <input type="text" name="supplier_reference" class="form-control form-control-sm" placeholder="e.g. SUP-99812">
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold text-secondary">Embassy / Govt Ref #</label>
                <input type="text" name="embassy_reference" class="form-control form-control-sm" placeholder="e.g. EMB-2026-44">
              </div>
            </div>

            <div class="mb-0">
              <label class="form-label small fw-semibold text-secondary">Visa Vendor / Supplier</label>
              <select name="supplier_id" class="form-select">
                <option value="">-- Direct Consulate / In-House --</option>
                <?php foreach ($suppliers as $sup): ?>
                  <option value="<?= $sup['id'] ?>"><?= e($sup['company_name'] ?? $sup['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <!-- Financial Summary Card -->
        <div class="card card-enterprise mb-4 shadow-sm border">
          <div class="card-header bg-white py-3 border-bottom">
            <span class="fw-bold small text-uppercase text-secondary">
              <i class="fa-solid fa-receipt text-primary me-2"></i> Financial &amp; SLA Breakdown
            </span>
          </div>
          <div class="card-body p-4">
            <div class="d-flex justify-content-between small mb-2">
              <span class="text-muted">Package Selling Price:</span>
              <span class="fw-bold text-dark" id="dispSellingPrice">$0.00</span>
            </div>
            <div class="row g-2 mb-2">
              <div class="col-6">
                <label class="form-label small text-muted mb-1" style="font-size: 0.75rem;">Discount ($)</label>
                <input type="number" step="0.01" name="discount" id="inputDiscount" class="form-control form-control-sm" value="0.00" oninput="updateServiceInfo()">
              </div>
              <div class="col-6">
                <label class="form-label small text-muted mb-1" style="font-size: 0.75rem;">Other Expenses ($)</label>
                <input type="number" step="0.01" name="other_expenses" id="inputOtherExpenses" class="form-control form-control-sm" value="0.00">
              </div>
            </div>
            <div class="d-flex justify-content-between small mb-2">
              <span class="text-muted">Supplier / Embassy Cost:</span>
              <span class="text-secondary" id="dispSupplierCost">$0.00</span>
            </div>
            <div class="d-flex justify-content-between small mb-2">
              <span class="text-muted">Estimated Tax Amount:</span>
              <span class="text-secondary" id="dispTaxAmount">$0.00</span>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="fw-bold text-dark">Total Invoice Amount:</span>
              <span class="fw-bold text-primary fs-5" id="dispTotalAmount">$0.00</span>
            </div>

            <div class="p-3 bg-light rounded border small">
              <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Estimated Processing:</span>
                <span class="fw-bold text-dark" id="dispEstimatedDays">-- Days</span>
              </div>
              <div class="d-flex justify-content-between">
                <span class="text-muted">Expected Completion:</span>
                <span class="fw-bold text-primary" id="dispExpectedDate">--</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Section 5: Optional Payment (Part 9, 10) -->
        <div class="card card-enterprise shadow-sm border mb-4">
          <div class="card-header bg-white py-3 border-bottom">
            <span class="fw-bold small text-uppercase text-secondary">
              <i class="fa-solid fa-money-bill-wave text-success me-2"></i> Registration Payment (Optional)
            </span>
          </div>
          <div class="card-body p-4">
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="pay_now" value="0" id="payLaterOpt" checked onchange="togglePaymentBox()">
              <label class="form-check-label fw-semibold small" for="payLaterOpt">
                <i class="fa-solid fa-clock text-warning me-1"></i> Skip Payment &amp; Pay Later (Invoice remains Unpaid)
              </label>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="radio" name="pay_now" value="1" id="payNowOpt" onchange="togglePaymentBox()">
              <label class="form-check-label fw-semibold small" for="payNowOpt">
                <i class="fa-solid fa-circle-dollar-to-slot text-success me-1"></i> Pay Now / Settle Fee Immediately
              </label>
            </div>

            <div id="payDetailsBox" class="p-3 bg-light rounded border small d-none">
              <div class="mb-2">
                <label class="form-label small fw-semibold text-secondary">Payment Method</label>
                <select name="pay_method" class="form-select form-select-sm">
                  <option value="Cash">Cash at Branch</option>
                  <option value="Bank Transfer">Bank Transfer / Wire</option>
                  <option value="Customer Wallet">Pay from Customer Digital Wallet</option>
                  <option value="Credit Card">POS Credit Card</option>
                  <option value="Stripe">Online Payment Link (Stripe)</option>
                </select>
              </div>
              <div class="mb-0">
                <label class="form-label small fw-semibold text-secondary">Transaction / Bank Reference</label>
                <input type="text" name="pay_reference" class="form-control form-control-sm" placeholder="Optional ref #...">
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Sticky Bottom Form Action Bar -->
    <div class="form-sticky-actions mt-4 rounded-3 shadow-sm bg-white p-3 d-flex justify-content-between align-items-center border">
      <a href="/applications" class="btn btn-outline-secondary px-4 fw-semibold">
        <i class="fa-solid fa-times me-1"></i> Cancel
      </a>
      <div class="d-flex align-items-center gap-2">
        <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm" id="submitAppBtn">
          <i class="fa-solid fa-check-circle me-1"></i> Register &amp; Open Tracking File &rarr;
        </button>
      </div>
    </div>
  </form>
</div>

<script>
const allServices = <?= json_encode($services, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const allCategories = <?= json_encode($categories, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const allCountries = <?= json_encode($countries, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

function filterVisaPackages() {
  const countryId = document.getElementById('countryFilterSelect').value;
  const categoryId = document.getElementById('categoryFilterSelect').value;
  const srvSelect = document.getElementById('serviceSelect');
  const previousVal = srvSelect.value;
  
  const selectedCat = allCategories.find(c => String(c.id) === String(categoryId));
  const catName = selectedCat ? (selectedCat.name || '').toLowerCase() : '';

  let filtered = allServices.filter(s => {
    let matchCountry = !countryId || String(s.country_id) === String(countryId);
    let matchCat = true;
    if (categoryId) {
      const srvCatId = String(s.category_id || '');
      const srvName = (s.name || '').toLowerCase();
      const srvCatName = (s.category_name || '').toLowerCase();
      matchCat = (srvCatId === String(categoryId)) || 
                 (catName && (
                   srvName.includes(catName.replace(' visa', '').trim()) || 
                   srvCatName.includes(catName.replace(' visa', '').trim()) || 
                   (catName.includes('work') && (srvName.includes('employment') || srvName.includes('work'))) || 
                   (catName.includes('employment') && (srvName.includes('work') || srvName.includes('employment'))) ||
                   (catName.includes('tourist') && (srvName.includes('tourist') || srvName.includes('visit'))) ||
                   (catName.includes('visit') && (srvName.includes('visit') || srvName.includes('tourist'))) ||
                   (catName.includes('golden') && (srvName.includes('golden') || srvName.includes('residency') || srvName.includes('investor')))
                 ));
    }
    return matchCountry && matchCat;
  });

  // If no services matched category but country is selected, fallback gracefully so dropdown is never empty
  let isFallback = false;
  if (filtered.length === 0 && countryId) {
    filtered = allServices.filter(s => String(s.country_id) === String(countryId));
    isFallback = true;
  }

  // Rebuild select options
  srvSelect.innerHTML = '';
  const defaultOpt = document.createElement('option');
  defaultOpt.value = '';
  if (filtered.length === 0) {
    defaultOpt.textContent = '-- No visa packages available --';
  } else if (isFallback) {
    defaultOpt.textContent = `-- Showing all ${filtered.length} packages for this destination --`;
  } else {
    defaultOpt.textContent = `-- Choose Visa Type / Duration / Entry (${filtered.length} Available) --`;
  }
  srvSelect.appendChild(defaultOpt);

  filtered.forEach(s => {
    const opt = document.createElement('option');
    opt.value = s.id;
    opt.dataset.countryId = s.country_id;
    opt.dataset.categoryId = s.category_id || '';
    opt.dataset.price = s.selling_price;
    opt.dataset.cost = s.supplier_cost;
    opt.dataset.tax = s.tax_rate || 0;
    opt.dataset.days = s.estimated_days || 10;
    opt.dataset.entry = s.entry_type || 'Single Entry';
    opt.textContent = `${s.flag_emoji || '✈️'} ${s.country_name} — ${s.name} (${s.duration || 'Standard'} • ${s.entry_type || 'Single'} • $${parseFloat(s.selling_price).toFixed(2)})`;
    if (String(s.id) === String(previousVal)) {
      opt.selected = true;
    }
    srvSelect.appendChild(opt);
  });

  if (filtered.length === 1) {
    srvSelect.selectedIndex = 1;
  }

  updateServiceInfo();
  checkDuplicateApplication();
}

function onServiceChanged() {
  const srvSelect = document.getElementById('serviceSelect');
  const selectedId = srvSelect.value;
  if (selectedId) {
    const srv = allServices.find(s => String(s.id) === String(selectedId));
    if (srv) {
      if (srv.country_id) {
        document.getElementById('countryFilterSelect').value = srv.country_id;
      }
      if (srv.category_id) {
        const catSelect = document.getElementById('categoryFilterSelect');
        if (catSelect.querySelector(`option[value="${srv.category_id}"]`)) {
          catSelect.value = srv.category_id;
        }
      }
    }
  }
  updateServiceInfo();
  checkDuplicateApplication();
}

function addAppDocRow() {
  const container = document.getElementById('appDocUploadContainer');
  const firstRow = container.querySelector('.app-doc-row');
  const clone = firstRow.cloneNode(true);
  clone.querySelectorAll('input').forEach(input => input.value = '');
  container.appendChild(clone);
}

function togglePaymentBox() {
  const isPayNow = document.getElementById('payNowOpt').checked;
  const box = document.getElementById('payDetailsBox');
  if (isPayNow) {
    box.classList.remove('d-none');
  } else {
    box.classList.add('d-none');
  }
}

function updateServiceInfo() {
  const sel = document.getElementById('serviceSelect');
  const opt = sel.options[sel.selectedIndex];

  if (!opt || !opt.value) {
    document.getElementById('dispSellingPrice').innerText = '$0.00';
    document.getElementById('dispSupplierCost').innerText = '$0.00';
    document.getElementById('dispTaxAmount').innerText = '$0.00';
    document.getElementById('dispTotalAmount').innerText = '$0.00';
    document.getElementById('dispEstimatedDays').innerText = '-- Days';
    document.getElementById('dispExpectedDate').innerText = '--';
    return;
  }

  const price = parseFloat(opt.dataset.price || 0);
  const cost = parseFloat(opt.dataset.cost || 0);
  const taxRate = parseFloat(opt.dataset.tax || 0);
  const days = parseInt(opt.dataset.days || 10, 10);
  const discountInput = document.getElementById('inputDiscount');
  const discount = discountInput ? parseFloat(discountInput.value || 0) : 0;

  const netPrice = Math.max(0, price - discount);
  const tax = netPrice * (taxRate / 100);
  const total = netPrice + tax;

  document.getElementById('dispSellingPrice').innerText = '$' + price.toFixed(2);
  document.getElementById('dispSupplierCost').innerText = '$' + cost.toFixed(2);
  document.getElementById('dispTaxAmount').innerText = '$' + tax.toFixed(2);
  document.getElementById('dispTotalAmount').innerText = '$' + total.toFixed(2);
  document.getElementById('dispEstimatedDays').innerText = days + ' Days';

  const expDate = new Date();
  expDate.setDate(expDate.getDate() + days);
  document.getElementById('dispExpectedDate').innerText = expDate.toISOString().split('T')[0];
}

function checkDuplicateApplication() {
  const custId = document.getElementById('customerSelect').value;
  const srvId = document.getElementById('serviceSelect').value;
  const warnBox = document.getElementById('duplicateWarningBox');

  if (!custId || !srvId) {
    warnBox.classList.add('d-none');
    return;
  }

  fetch(`/applications/check-duplicate?customer_id=${custId}&service_id=${srvId}`)
    .then(r => r.json())
    .then(data => {
      if (data.duplicate) {
        document.getElementById('duplicateWarningTitle').innerText = 'Active Application In Progress';
        document.getElementById('duplicateWarningMsg').innerHTML = `This applicant already has an active application (<strong>${data.application_number}</strong>) in stage <strong>${data.current_stage}</strong>. Registering another case is permitted but will be flagged.`;
        warnBox.classList.remove('d-none');
      } else {
        warnBox.classList.add('d-none');
      }
    })
    .catch(() => {
      warnBox.classList.add('d-none');
    });
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
