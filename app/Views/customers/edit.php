<?php
$pageTitle = 'Edit Applicant Profile — ' . e($customer['full_name']);
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
?>

<!-- Load Bento Design System CSS (Referencing Dashboard & Visa Tracking UI) -->
<link rel="stylesheet" href="/assets/css/dashboard-bento.css?v=<?= time() ?>">
<link rel="stylesheet" href="/assets/css/pages/customers-index.css?v=<?= time() ?>">

<div class="content-body p-0 customers-page">
  <div class="bento-dashboard-wrap">

    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm rounded-4" role="alert">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
          <span><?= e($flash['message']) ?></span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- ─── HEADER & ACTIONS ─────────────────────────────────────────────── -->
    <header class="bento-header mb-4">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
          <a href="/customers/show?id=<?= $customer['id'] ?>" class="bento-btn-outline" style="padding: 0.4rem 0.9rem; font-size: 0.8rem;" title="Back to Customer Profile">
            <i class="fa-solid fa-arrow-left"></i>
          </a>
          <h1 class="bento-header-title mb-0">Edit Applicant Profile</h1>
          <span class="cust-code">
            <i class="fa-solid fa-id-badge me-1"></i><?= e($customer['customer_code']) ?>
          </span>
        </div>
        <p class="bento-header-subtitle">
          Updating records for <strong class="text-dark"><?= e($customer['full_name']) ?></strong>
        </p>
      </div>

      <div class="cust-header-actions">
        <a href="/customers/show?id=<?= $customer['id'] ?>" class="bento-btn-outline">
          Cancel
        </a>
        <button type="submit" form="editCustomerForm" class="bento-btn-primary">
          <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
        </button>
      </div>
    </header>

    <form id="editCustomerForm" action="/customers/update" method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $customer['id'] ?>">

      <div class="row g-4">
        <!-- Left Column: Personal, Passport & Family Info -->
        <div class="col-12 col-lg-8">
          <!-- 1. Personal & Identity Details -->
          <div class="bento-card mb-4 p-4">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
              <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 1.05rem;">
                <i class="fa-solid fa-user text-primary"></i> Personal &amp; Identity Details
              </h5>
            </div>

            <div class="row g-3">
              <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary">First / Given Name <span class="text-danger">*</span></label>
                <input type="text" name="first_name" class="form-control bg-light" value="<?= e($customer['first_name'] ?? '') ?>" required>
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary">Middle Name</label>
                <input type="text" name="middle_name" class="form-control bg-light" value="<?= e($customer['middle_name'] ?? '') ?>">
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary">Last / Family Name <span class="text-danger">*</span></label>
                <input type="text" name="last_name" class="form-control bg-light" value="<?= e($customer['last_name'] ?? '') ?>" required>
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary">Gender</label>
                <select name="gender" class="form-select bg-light">
                  <option value="Male" <?= ($customer['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                  <option value="Female" <?= ($customer['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                  <option value="Other" <?= ($customer['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary">Date of Birth</label>
                <input type="date" name="dob" class="form-control bg-light" value="<?= e($customer['dob'] ?? '') ?>">
              </div>
              <div class="col-12 col-md-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label small fw-semibold text-secondary mb-0">Nationality <span class="text-danger">*</span></label>
                  <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold text-primary" style="font-size: 0.76rem;" onclick="toggleManualInput('nationalityManualBox', 'nationalitySelect')">
                    <i class="fa-solid fa-pen-to-square me-1"></i>+ Custom
                  </button>
                </div>
                <select name="nationality" id="nationalitySelect" class="form-select bg-light" required onchange="checkManualSelect(this, 'nationalityManualBox')">
                  <option value="">-- Choose Nationality --</option>
                  <option value="__custom__" class="fw-bold text-primary">+ Enter Manually / Other...</option>
                  <?php foreach ($countries as $c): ?>
                    <option value="<?= e($c['name']) ?>" <?= ($customer['nationality'] ?? '') === $c['name'] ? 'selected' : '' ?>>
                      <?= $c['flag_emoji'] ?> <?= e($c['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div id="nationalityManualBox" class="mt-2 d-none">
                  <input type="text" name="custom_nationality" class="form-control form-control-sm bg-light" placeholder="Type custom nationality / country...">
                </div>
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary">Place of Birth</label>
                <input type="text" name="place_of_birth" class="form-control bg-light" value="<?= e($customer['place_of_birth'] ?? '') ?>">
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary">Marital Status</label>
                <select name="marital_status" class="form-select bg-light">
                  <option value="Single" <?= ($customer['marital_status'] ?? '') === 'Single' ? 'selected' : '' ?>>Single</option>
                  <option value="Married" <?= ($customer['marital_status'] ?? '') === 'Married' ? 'selected' : '' ?>>Married</option>
                  <option value="Divorced" <?= ($customer['marital_status'] ?? '') === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                  <option value="Widowed" <?= ($customer['marital_status'] ?? '') === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                </select>
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary">Occupation / Profession</label>
                <input type="text" name="occupation" class="form-control bg-light" value="<?= e($customer['occupation'] ?? '') ?>">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold text-secondary">Religion</label>
                <input type="text" name="religion" class="form-control bg-light" value="<?= e($customer['religion'] ?? '') ?>">
              </div>
              <div class="col-12 col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label small fw-semibold text-secondary mb-0">Current Residence Country</label>
                  <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold text-primary" style="font-size: 0.76rem;" onclick="toggleManualInput('currentCountryManualBox', 'currentCountrySelect')">
                    <i class="fa-solid fa-pen-to-square me-1"></i>+ Custom
                  </button>
                </div>
                <select name="current_country" id="currentCountrySelect" class="form-select bg-light" onchange="checkManualSelect(this, 'currentCountryManualBox')">
                  <option value="__custom__" class="fw-bold text-primary">+ Enter Manually / Other Country...</option>
                  <?php foreach ($countries as $c): ?>
                    <option value="<?= e($c['name']) ?>" <?= ($customer['current_country'] ?? '') === $c['name'] ? 'selected' : '' ?>>
                      <?= $c['flag_emoji'] ?> <?= e($c['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div id="currentCountryManualBox" class="mt-2 d-none">
                  <input type="text" name="custom_current_country" class="form-control form-control-sm bg-light" placeholder="Type custom residence country...">
                </div>
              </div>
            </div>
          </div>

          <!-- 2. Primary Passport Details -->
          <div class="bento-card mb-4 p-4">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
              <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 1.05rem;">
                <i class="fa-solid fa-passport text-primary"></i> Primary Passport Details
              </h5>
            </div>

            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold text-secondary">Passport Number</label>
                <input type="text" name="passport_number" class="form-control bg-light font-monospace fw-bold" value="<?= e($primaryPassport['passport_number'] ?? '') ?>" placeholder="e.g. Z1234567">
              </div>

              <div class="col-12 col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label small fw-semibold text-secondary mb-0">Country of Issue</label>
                  <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold text-primary" style="font-size: 0.76rem;" onclick="toggleManualInput('passportCountryManualBox', 'passportCountrySelect')">
                    <i class="fa-solid fa-pen-to-square me-1"></i>+ Custom
                  </button>
                </div>
                <select name="passport_country" id="passportCountrySelect" class="form-select bg-light" onchange="checkManualSelect(this, 'passportCountryManualBox')">
                  <option value="">-- Same as Nationality --</option>
                  <option value="__custom__" class="fw-bold text-primary">+ Enter Manually / Other Country...</option>
                  <?php foreach ($countries as $c): ?>
                    <option value="<?= e($c['name']) ?>" <?= ($primaryPassport['country_of_issue'] ?? '') === $c['name'] ? 'selected' : '' ?>>
                      <?= $c['flag_emoji'] ?> <?= e($c['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div id="passportCountryManualBox" class="mt-2 d-none">
                  <input type="text" name="custom_passport_country" class="form-control form-control-sm bg-light" placeholder="Type custom issuing country...">
                </div>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold text-secondary">Issue Date</label>
                <input type="date" name="passport_issue_date" class="form-control bg-light" value="<?= e($primaryPassport['issue_date'] ?? '') ?>">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold text-secondary">Expiry Date</label>
                <input type="date" name="passport_expiry" class="form-control bg-light" value="<?= e($primaryPassport['expiry_date'] ?? '') ?>">
              </div>
            </div>
          </div>

          <!-- 3. Family Details -->
          <div class="bento-card mb-4 p-4">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
              <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 1.05rem;">
                <i class="fa-solid fa-people-roof text-primary"></i> Family &amp; Parents Information
              </h5>
            </div>

            <div class="row g-3">
              <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary">Father's Full Name</label>
                <input type="text" name="father_name" class="form-control bg-light" value="<?= e($family['father_name'] ?? '') ?>">
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary">Father's DOB</label>
                <input type="date" name="father_dob" class="form-control bg-light" value="<?= e($family['father_dob'] ?? '') ?>">
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary">Father's Nationality</label>
                <input type="text" name="father_nationality" class="form-control bg-light" value="<?= e($family['father_nationality'] ?? '') ?>">
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary">Mother's Full Name</label>
                <input type="text" name="mother_name" class="form-control bg-light" value="<?= e($family['mother_name'] ?? '') ?>">
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary">Mother's DOB</label>
                <input type="date" name="mother_dob" class="form-control bg-light" value="<?= e($family['mother_dob'] ?? '') ?>">
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary">Mother's Nationality</label>
                <input type="text" name="mother_nationality" class="form-control bg-light" value="<?= e($family['mother_nationality'] ?? '') ?>">
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Contact, Address & Internal Notes -->
        <div class="col-12 col-lg-4">
          <!-- Contact Details -->
          <div class="bento-card mb-4 p-4">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
              <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 1.05rem;">
                <i class="fa-solid fa-address-book text-primary"></i> Contact Details
              </h5>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">Mobile Number <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-phone"></i></span>
                <input type="text" name="mobile" class="form-control bg-light border-start-0" value="<?= e($customer['mobile'] ?? '') ?>" required>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">WhatsApp Number</label>
              <div class="input-group">
                <span class="input-group-text bg-light text-success border-end-0"><i class="fa-brands fa-whatsapp"></i></span>
                <input type="text" name="whatsapp" class="form-control bg-light border-start-0" value="<?= e($customer['whatsapp'] ?? '') ?>">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">Email Address</label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-envelope"></i></span>
                <input type="email" name="email" class="form-control bg-light border-start-0" value="<?= e($customer['email'] ?? '') ?>">
              </div>
            </div>

            <div class="mb-0">
              <label class="form-label small fw-semibold text-secondary">Residential Address</label>
              <textarea name="address" class="form-control bg-light" rows="3"><?= e($customer['address'] ?? '') ?></textarea>
            </div>
          </div>

          <!-- Internal Notes -->
          <div class="bento-card mb-4 p-4">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
              <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 1.05rem;">
                <i class="fa-solid fa-note-sticky text-info"></i> Internal Notes
              </h5>
            </div>
            <textarea name="notes" class="form-control bg-light" rows="4" placeholder="Special requirements, VIP notes, etc..."><?= e($customer['notes'] ?? '') ?></textarea>
          </div>

          <!-- Submit Buttons -->
          <div class="d-grid gap-2">
            <button type="submit" class="bento-btn-primary w-100 py-2.5">
              <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
            </button>
            <a href="/customers/show?id=<?= $customer['id'] ?>" class="btn btn-light border rounded-pill py-2 text-center text-muted fw-semibold">
              Cancel &amp; Return
            </a>
          </div>
        </div>
      </div>
    </form>

  </div>
</div>

<script>
function toggleManualInput(manualBoxId, selectId) {
  const box = document.getElementById(manualBoxId);
  const sel = document.getElementById(selectId);
  if (!box) return;
  if (box.classList.contains('d-none')) {
    box.classList.remove('d-none');
    if (sel) {
      if (sel.querySelector('option[value="__custom__"]')) {
        sel.value = '__custom__';
      }
      if (sel.hasAttribute('required')) {
        sel.removeAttribute('required');
        sel.dataset.hadRequired = 'true';
      }
    }
    const input = box.querySelector('input, select, textarea');
    if (input) {
      if (sel && sel.dataset.hadRequired === 'true') {
        input.setAttribute('required', 'required');
      }
      input.focus();
    }
  } else {
    box.classList.add('d-none');
    const input = box.querySelector('input, select, textarea');
    if (input) {
      input.value = '';
      input.removeAttribute('required');
    }
    if (sel) {
      if (sel.value === '__custom__') {
        sel.value = '';
      }
      if (sel.dataset.hadRequired === 'true') {
        sel.setAttribute('required', 'required');
      }
    }
  }
}

function checkManualSelect(sel, manualBoxId) {
  const box = document.getElementById(manualBoxId);
  if (!box) return;
  const input = box.querySelector('input, select, textarea');
  if (sel.value === '__custom__') {
    box.classList.remove('d-none');
    if (sel.hasAttribute('required')) {
      sel.removeAttribute('required');
      sel.dataset.hadRequired = 'true';
    }
    if (input) {
      if (sel.dataset.hadRequired === 'true') {
        input.setAttribute('required', 'required');
      }
      input.focus();
    }
  } else {
    box.classList.add('d-none');
    if (input) {
      input.value = '';
      input.removeAttribute('required');
    }
    if (sel.dataset.hadRequired === 'true') {
      sel.setAttribute('required', 'required');
    }
  }
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
