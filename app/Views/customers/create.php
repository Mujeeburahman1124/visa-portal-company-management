<?php
$pageTitle = 'Register New Applicant — MS TRAVEL HUB';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
?>

<div class="content-body">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show mb-4" role="alert">
      <?= e($flash['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
    <div class="d-flex align-items-center gap-3">
      <a href="/customers" class="btn btn-outline-secondary btn-sm bg-white"><i class="fa-solid fa-arrow-left"></i></a>
      <div>
        <h3 class="fw-bold brand-font text-dark mb-0">Register New Applicant</h3>
        <p class="text-muted small mb-0">Create applicant identity profile with personal, family, passport, ID, residence &amp; supporting documents.</p>
      </div>
    </div>
  </div>

  <form action="/customers/store" method="POST" enctype="multipart/form-data" id="registerApplicantForm">
    <?= csrf_field() ?>

    <!-- Section 1: Personal Information -->
    <div class="card card-enterprise mb-3 shadow-sm border">
      <div class="card-header bg-white py-3 border-bottom">
        <span class="fw-bold small text-uppercase text-secondary">
          <i class="fa-solid fa-user text-primary me-2"></i> 1. Personal Information
        </span>
      </div>
      <div class="card-body p-4">
        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <label class="form-label small fw-semibold">First Name <span class="text-danger">*</span></label>
            <input type="text" name="first_name" class="form-control" placeholder="e.g. Tariq" required>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Middle Name</label>
            <input type="text" name="middle_name" class="form-control" placeholder="e.g. Mohammed">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Last Name <span class="text-danger">*</span></label>
            <input type="text" name="last_name" class="form-control" placeholder="e.g. Al-Mansoor" required>
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Gender</label>
            <select name="gender" class="form-select">
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Date of Birth</label>
            <input type="date" name="dob" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Nationality <span class="text-danger">*</span></label>
            <select name="nationality" class="form-select" required>
              <option value="">-- Choose Country --</option>
              <?php foreach ($countries as $ct): ?>
                <option value="<?= e($ct['name']) ?>"><?= $ct['flag_emoji'] ?> <?= e($ct['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Place of Birth</label>
            <input type="text" name="place_of_birth" class="form-control" placeholder="City, State / Country">
          </div>
        </div>

        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Marital Status</label>
            <select name="marital_status" class="form-select">
              <option value="Single">Single</option>
              <option value="Married">Married</option>
              <option value="Divorced">Divorced</option>
              <option value="Widowed">Widowed</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Occupation / Profession</label>
            <input type="text" name="occupation" class="form-control" placeholder="e.g. Senior Software Architect">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Religion</label>
            <input type="text" name="religion" class="form-control" placeholder="e.g. Islam / Christianity / Hinduism">
          </div>
        </div>
      </div>
    </div>

    <!-- Section 2: Family Information -->
    <div class="card card-enterprise mb-3 shadow-sm border">
      <div class="card-header bg-white py-3 border-bottom">
        <span class="fw-bold small text-uppercase text-secondary">
          <i class="fa-solid fa-people-roof text-primary me-2"></i> 2. Family Information
        </span>
      </div>
      <div class="card-body p-4">
        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3" style="font-size: 0.88rem;">Father Details</h6>
        <div class="row g-3 mb-4">
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Father Full Name</label>
            <input type="text" name="father_name" class="form-control" placeholder="Father's full name">
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Father Date of Birth</label>
            <input type="date" name="father_dob" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Father Country of Birth</label>
            <input type="text" name="father_country_of_birth" class="form-control" placeholder="Country of birth">
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Father Religion</label>
            <input type="text" name="father_religion" class="form-control" placeholder="Religion">
          </div>
        </div>

        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3" style="font-size: 0.88rem;">Mother Details</h6>
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Mother Full Name</label>
            <input type="text" name="mother_name" class="form-control" placeholder="Mother's full name">
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Mother Date of Birth</label>
            <input type="date" name="mother_dob" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Mother Mobile Phone</label>
            <input type="text" name="mother_mobile" class="form-control" placeholder="+971 50 000 0000">
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Mother Religion</label>
            <input type="text" name="mother_religion" class="form-control" placeholder="Religion">
          </div>
        </div>
      </div>
    </div>

    <!-- Section 3: Contact Details -->
    <div class="card card-enterprise mb-3 shadow-sm border">
      <div class="card-header bg-white py-3 border-bottom">
        <span class="fw-bold small text-uppercase text-secondary">
          <i class="fa-solid fa-address-book text-primary me-2"></i> 3. Contact &amp; Address
        </span>
      </div>
      <div class="card-body p-4">
        <div class="row g-3 mb-3">
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Mobile Phone <span class="text-danger">*</span></label>
            <input type="text" name="mobile" class="form-control" placeholder="+971 50 000 0000" required>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">WhatsApp Number</label>
            <input type="text" name="whatsapp" class="form-control" placeholder="+971 50 000 0000">
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="applicant@email.com">
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Current Country of Residence <span class="text-danger">*</span></label>
            <select name="current_country" class="form-select" required>
              <?php foreach ($countries as $ct): ?>
                <option value="<?= e($ct['name']) ?>" <?= $ct['name'] === 'United Arab Emirates' ? 'selected' : '' ?>>
                  <?= $ct['flag_emoji'] ?> <?= e($ct['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div>
          <label class="form-label small fw-semibold">Full Residential Address</label>
          <input type="text" name="address" class="form-control" placeholder="Building, Street, Area, City, Country">
        </div>
      </div>
    </div>

    <!-- Section 4: Passport Information -->
    <div class="card card-enterprise mb-3 shadow-sm border">
      <div class="card-header bg-white py-3 border-bottom">
        <span class="fw-bold small text-uppercase text-secondary">
          <i class="fa-solid fa-passport text-primary me-2"></i> 4. Passport Details
        </span>
      </div>
      <div class="card-body p-4">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Primary Passport Number <span class="text-danger">*</span></label>
            <input type="text" name="passport_number" class="form-control fw-bold" placeholder="e.g. Z1234567" required>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Issuing Country</label>
            <select name="passport_issuing_country" class="form-select">
              <?php foreach ($countries as $ct): ?>
                <option value="<?= e($ct['name']) ?>"><?= $ct['flag_emoji'] ?> <?= e($ct['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-semibold">Date of Issue</label>
            <input type="date" name="passport_issue_date" class="form-control">
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-semibold">Date of Expiry <span class="text-danger">*</span></label>
            <input type="date" name="passport_expiry_date" class="form-control" required>
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-semibold">Place of Issue</label>
            <input type="text" name="passport_place_of_issue" class="form-control" placeholder="City / Embassy">
          </div>
        </div>
      </div>
    </div>

    <!-- Section 5: National ID & Residence Details -->
    <div class="card card-enterprise mb-3 shadow-sm border">
      <div class="card-header bg-white py-3 border-bottom">
        <span class="fw-bold small text-uppercase text-secondary">
          <i class="fa-solid fa-id-card text-primary me-2"></i> 5. National ID &amp; Residence Permit
        </span>
      </div>
      <div class="card-body p-4">
        <div class="row g-3 mb-3">
          <div class="col-md-3">
            <label class="form-label small fw-semibold">National ID Number</label>
            <input type="text" name="national_id_number" class="form-control" placeholder="784-XXXX-XXXXXXX-X">
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">National ID Type</label>
            <input type="text" name="national_id_type" class="form-control" value="Emirates ID / National Identity">
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">ID Issue Date</label>
            <input type="date" name="national_id_issue_date" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">ID Expiry Date</label>
            <input type="date" name="national_id_expiry_date" class="form-control">
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label small fw-semibold">National ID Front Scan</label>
            <input type="file" name="national_id_front" class="form-control" accept="image/*,application/pdf">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">National ID Back Scan</label>
            <input type="file" name="national_id_back" class="form-control" accept="image/*,application/pdf">
          </div>
        </div>

        <hr class="my-3">

        <h6 class="fw-bold text-dark mb-3" style="font-size: 0.88rem;">Residence / Work Permit Details</h6>
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Residence Country</label>
            <input type="text" name="residence_country" class="form-control" placeholder="e.g. United Arab Emirates" value="United Arab Emirates">
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Residence Permit Number</label>
            <input type="text" name="residence_permit_number" class="form-control" placeholder="Permit / Visa UID #">
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-semibold">Permit Expiry</label>
            <input type="date" name="residence_expiry_date" class="form-control">
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-semibold">Employer / Sponsor</label>
            <input type="text" name="residence_employer" class="form-control" placeholder="Company / Sponsor Name">
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-semibold">Job Title</label>
            <input type="text" name="residence_job_title" class="form-control" placeholder="Designation">
          </div>
        </div>
      </div>
    </div>

    <!-- Section 6: Visa Application Details (Part 1, 2, 3, 4, 5, 6, 7, 8) -->
    <div class="card card-enterprise mb-4 shadow-sm border">
      <div class="card-header bg-white d-flex align-items-center justify-content-between py-3 border-bottom">
        <span class="fw-bold small text-uppercase text-secondary">
          <i class="fa-solid fa-passport text-primary me-2"></i> 6. Visa Application Details
        </span>
        <span class="badge bg-primary-subtle text-primary fw-semibold">
          <i class="fa-solid fa-circle-check me-1"></i> Configurable &bull; Manual Overrides Supported
        </span>
      </div>
      <div class="card-body p-4">
        <!-- 1. Destination Country (Catalog Select + Manual Override) -->
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label small fw-semibold text-secondary mb-0">Destination Country</label>
              <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold text-primary" style="font-size: 0.78rem;" onclick="toggleManualInput('countryManualBox', 'countrySelect')">
                <i class="fa-solid fa-pen-to-square me-1"></i>+ Enter Manually
              </button>
            </div>
            <select name="country_id" id="countrySelect" class="form-select" onchange="filterVisaPackages()">
              <option value="">-- Select Destination Country --</option>
              <?php foreach ($countries as $ct): ?>
                <option value="<?= $ct['id'] ?>"><?= $ct['flag_emoji'] ?> <?= e($ct['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <div id="countryManualBox" class="mt-2 d-none">
              <input type="text" name="custom_destination_country" class="form-control form-control-sm" placeholder="Custom Destination Country (e.g. United Kingdom, Singapore)">
              <div class="form-text text-muted small" style="font-size: 0.72rem;">Manual entry saved directly with this visa file.</div>
            </div>
          </div>

          <!-- 2. Visa Category (Catalog Select + Manual Override) -->
          <div class="col-md-6">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label small fw-semibold text-secondary mb-0">Visa Category</label>
              <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold text-primary" style="font-size: 0.78rem;" onclick="toggleManualInput('categoryManualBox', 'categorySelect')">
                <i class="fa-solid fa-pen-to-square me-1"></i>+ Enter Manually
              </button>
            </div>
            <select name="category_id" id="categorySelect" class="form-select" onchange="filterVisaPackages()">
              <option value="">-- Select Visa Category --</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <div id="categoryManualBox" class="mt-2 d-none">
              <input type="text" name="custom_visa_category" class="form-control form-control-sm" placeholder="Custom Visa Category (e.g. Golden Visa, Digital Nomad, Pilgrimage)">
              <div class="form-text text-muted small" style="font-size: 0.72rem;">Custom category saved with this application.</div>
            </div>
          </div>
        </div>

        <!-- 3. Visa Type & Service Package (Catalog Select + Manual Override) -->
        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="form-label small fw-semibold text-secondary mb-0">Visa Type / Service Package</label>
            <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold text-primary" style="font-size: 0.78rem;" onclick="toggleManualInput('typeManualBox', 'serviceSelect')">
              <i class="fa-solid fa-pen-to-square me-1"></i>+ Enter Manually
            </button>
          </div>
          <select name="visa_service_id" id="serviceSelect" class="form-select" onchange="updateServiceInfo()">
            <option value="">-- Choose Visa Type / Duration / Entry --</option>
            <?php foreach ($services as $srv): ?>
              <option value="<?= $srv['id'] ?>" 
                      data-country-id="<?= $srv['country_id'] ?>"
                      data-category-id="<?= $srv['category_id'] ?? '' ?>"
                      data-price="<?= $srv['selling_price'] ?>"
                      data-cost="<?= $srv['supplier_cost'] ?>"
                      data-tax="<?= $srv['tax_rate'] ?>"
                      data-days="<?= $srv['estimated_days'] ?>"
                      data-duration="<?= e($srv['duration'] ?? '30 Days') ?>"
                      data-entry="<?= e($srv['entry_type']) ?>"
                      data-processing="<?= e($srv['processing_type'] ?? 'Normal') ?>">
                <?= $srv['flag_emoji'] ?> <?= e($srv['country_name']) ?> &mdash; <?= e($srv['name']) ?> (<?= e($srv['duration'] ?? 'Standard') ?> &bull; <?= e($srv['entry_type']) ?> &bull; $<?= number_format((float)$srv['selling_price'], 2) ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <div id="typeManualBox" class="mt-2 d-none">
            <input type="text" name="custom_visa_type" class="form-control form-control-sm" placeholder="Custom Visa Type (e.g. 60 Days Multiple Entry Express Visit Visa)">
            <div class="form-text text-muted small" style="font-size: 0.72rem;">Manual custom visa type saved permanently with this application.</div>
          </div>
        </div>

        <!-- 4. Visa Duration, 5. Entry Type, 6. Processing Type -->
        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label small fw-semibold text-secondary mb-0">Visa Duration</label>
              <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold text-primary" style="font-size: 0.75rem;" onclick="toggleManualInput('durationManualBox', 'durationSelect')">
                <i class="fa-solid fa-pen-to-square me-1"></i>Custom
              </button>
            </div>
            <select name="visa_duration" id="durationSelect" class="form-select form-select-sm">
              <option value="14 Days">14 Days</option>
              <option value="30 Days" selected>30 Days</option>
              <option value="60 Days">60 Days</option>
              <option value="90 Days">90 Days</option>
              <option value="180 Days">180 Days</option>
              <option value="1 Year">1 Year</option>
              <option value="2 Years">2 Years</option>
              <option value="5 Years">5 Years</option>
              <option value="10 Years">10 Years</option>
            </select>
            <div id="durationManualBox" class="mt-1 d-none">
              <input type="text" name="custom_visa_duration" class="form-control form-control-sm" placeholder="Custom Duration (e.g. 45 Days, 6 Months)">
            </div>
          </div>

          <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label small fw-semibold text-secondary mb-0">Entry Type</label>
              <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold text-primary" style="font-size: 0.75rem;" onclick="toggleManualInput('entryManualBox', 'entrySetSelect')">
                <i class="fa-solid fa-pen-to-square me-1"></i>Custom
              </button>
            </div>
            <select name="entry_type" id="entrySetSelect" class="form-select form-select-sm">
              <option value="Single Entry" selected>Single Entry</option>
              <option value="Double Entry">Double Entry</option>
              <option value="Multiple Entry">Multiple Entry</option>
            </select>
            <div id="entryManualBox" class="mt-1 d-none">
              <input type="text" name="custom_entry_type" class="form-control form-control-sm" placeholder="Custom Entry Type (e.g. Triple Entry, Multi-Pass)">
            </div>
          </div>

          <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label small fw-semibold text-secondary mb-0">Processing Type</label>
              <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold text-primary" style="font-size: 0.75rem;" onclick="toggleManualInput('processingManualBox', 'processingSelect')">
                <i class="fa-solid fa-pen-to-square me-1"></i>Custom
              </button>
            </div>
            <select name="processing_type" id="processingSelect" class="form-select form-select-sm">
              <option value="Normal" selected>Normal Processing</option>
              <option value="Express">Express Processing</option>
              <option value="Urgent">Urgent Processing</option>
              <option value="Super Express">Super Express</option>
            </select>
            <div id="processingManualBox" class="mt-1 d-none">
              <input type="text" name="custom_processing_type" class="form-control form-control-sm" placeholder="Custom Processing Type (e.g. VIP Concierge, Same Day)">
            </div>
          </div>
        </div>

        <!-- Travel & Processing Dates -->
        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <label class="form-label small fw-semibold text-secondary">Estimated Travel Date</label>
            <input type="date" name="travel_date" class="form-control form-control-sm">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold text-secondary">Proposed Return Date</label>
            <input type="date" name="return_date" class="form-control form-control-sm">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold text-secondary">Priority Level</label>
            <select name="priority" class="form-select form-select-sm">
              <option value="Normal">Normal Priority</option>
              <option value="High">High Priority</option>
              <option value="Urgent">Urgent Priority</option>
              <option value="Critical">Critical Priority</option>
            </select>
          </div>
        </div>

        <!-- Operational Assignment & References -->
        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <label class="form-label small fw-semibold text-secondary">Processing Branch</label>
            <select name="branch_id" class="form-select form-select-sm">
              <?php foreach ($branches as $br): ?>
                <option value="<?= $br['id'] ?>"><?= e($br['name']) ?> (<?= e($br['city']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold text-secondary">Assigned Case Officer</label>
            <select name="assigned_staff_id" class="form-select form-select-sm">
              <option value="">-- Assign Staff Member --</option>
              <?php foreach ($staffMembers as $stf): ?>
                <option value="<?= $stf['id'] ?>"><?= e($stf['name']) ?> (<?= e($stf['designation'] ?? 'Staff') ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold text-secondary">Visa Supplier / Vendor</label>
            <select name="supplier_id" class="form-select form-select-sm">
              <option value="">-- Direct Consulate / In-House --</option>
              <?php foreach ($suppliers as $sup): ?>
                <option value="<?= $sup['id'] ?>"><?= e($sup['company_name'] ?? $sup['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Financial Breakdown Card -->
        <div class="p-3 bg-light rounded border mb-0">
          <h6 class="fw-bold text-dark mb-2" style="font-size: 0.85rem;"><i class="fa-solid fa-receipt text-primary me-1.5"></i> Financial Breakdown &amp; Package Pricing</h6>
          <div class="row g-2 align-items-center">
            <div class="col-md-3 col-6">
              <label class="form-label small text-muted mb-1" style="font-size: 0.72rem;">Selling Price ($)</label>
              <input type="number" step="0.01" name="selling_price" id="custSellingPrice" class="form-control form-control-sm fw-bold" value="0.00" oninput="calcTotalCustomerPrice()">
            </div>
            <div class="col-md-2 col-6">
              <label class="form-label small text-muted mb-1" style="font-size: 0.72rem;">Discount ($)</label>
              <input type="number" step="0.01" name="discount" id="custDiscount" class="form-control form-control-sm" value="0.00" oninput="calcTotalCustomerPrice()">
            </div>
            <div class="col-md-2 col-6">
              <label class="form-label small text-muted mb-1" style="font-size: 0.72rem;">Supplier Cost ($)</label>
              <input type="number" step="0.01" name="supplier_cost" id="custSupplierCost" class="form-control form-control-sm" value="0.00">
            </div>
            <div class="col-md-2 col-6">
              <label class="form-label small text-muted mb-1" style="font-size: 0.72rem;">Tax / VAT ($)</label>
              <input type="number" step="0.01" name="tax_amount" id="custTaxAmount" class="form-control form-control-sm" value="0.00" oninput="calcTotalCustomerPrice()">
            </div>
            <div class="col-md-3 col-12">
              <div class="p-2 bg-white rounded border text-end">
                <span class="text-muted small d-block" style="font-size: 0.7rem;">Total Invoice Amount:</span>
                <span class="fw-bold text-primary fs-6" id="custTotalAmountDisplay">$0.00</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 7: Unlimited Supporting Documents Multi-Upload -->
    <div class="card card-enterprise mb-4 shadow-sm border">
      <div class="card-header bg-white d-flex align-items-center justify-content-between py-3 border-bottom">
        <span class="fw-bold small text-uppercase text-secondary">
          <i class="fa-solid fa-file-arrow-up text-primary me-2"></i> 7. Supporting Documents (Unlimited Multi-Upload)
        </span>
        <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2.5" onclick="addCustomerDocRow()">
          <i class="fa-solid fa-plus me-1"></i> Add Another Document
        </button>
      </div>
      <div class="card-body p-4">
        <div id="customerDocContainer">
          <div class="row g-2 align-items-center mb-2.5 customer-doc-row p-2 bg-light rounded border">
            <div class="col-md-3">
              <label class="form-label small fw-semibold mb-1">Document Type</label>
              <select name="document_types[]" class="form-select form-select-sm">
                <?php foreach ($docTypes as $dt): ?>
                  <option value="<?= $dt['id'] ?>"><?= e($dt['name']) ?> (<?= e($dt['category'] ?? 'General') ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold mb-1">Document Title / Label</label>
              <input type="text" name="document_titles[]" class="form-control form-control-sm" placeholder="e.g. Passport Bio Page, Salary Slip, Bank Statement">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold mb-1">Select File (PDF, PNG, JPG)</label>
              <input type="file" name="applicant_documents[]" class="form-control form-control-sm" accept="image/*,application/pdf">
            </div>
            <div class="col-md-1 text-center pt-3">
              <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2" onclick="removeCustomerDocRow(this)" title="Remove">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 8: Payment (Optional — Default Pay Later) -->
    <div class="card card-enterprise mb-4 shadow-sm border">
      <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <span class="fw-bold small text-uppercase text-secondary">
          <i class="fa-solid fa-credit-card text-success me-2"></i> 8. Registration Payment (Optional)
        </span>
        <span class="badge bg-success-subtle text-success fw-semibold">Payment is Optional</span>
      </div>
      <div class="card-body p-4">
        <div class="form-check mb-2">
          <input class="form-check-input" type="radio" name="pay_now" value="0" id="custPayLaterOpt" checked onchange="toggleCustPaymentBox()">
          <label class="form-check-label fw-semibold small" for="custPayLaterOpt">
            <i class="fa-solid fa-clock text-warning me-1"></i> Skip Payment &amp; Pay Later (Default &mdash; Invoice remains Unpaid, does not block registration)
          </label>
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="radio" name="pay_now" value="1" id="custPayNowOpt" onchange="toggleCustPaymentBox()">
          <label class="form-check-label fw-semibold small" for="custPayNowOpt">
            <i class="fa-solid fa-circle-dollar-to-slot text-success me-1"></i> Pay Now / Settle Fee Immediately at Registration
          </label>
        </div>

        <div id="custPayDetailsBox" class="p-3 bg-light rounded border small d-none">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Payment Method</label>
              <select name="pay_method" class="form-select form-select-sm">
                <option value="Cash">Cash at Branch</option>
                <option value="Bank Transfer">Bank Transfer / Wire</option>
                <option value="Customer Wallet">Pay from Customer Digital Wallet</option>
                <option value="Credit Card">POS Credit Card</option>
                <option value="Stripe">Online Payment Link (Stripe)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Transaction / Bank Reference</label>
              <input type="text" name="pay_reference" class="form-control form-control-sm" placeholder="Optional bank wire ref # or POS slip...">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Form Submit Actions -->
    <div class="d-flex align-items-center justify-content-end gap-2 mb-5">
      <a href="/customers" class="btn btn-light border px-4">Cancel</a>
      <button type="submit" class="btn btn-primary px-4 fw-semibold shadow">
        <i class="fa-solid fa-user-plus me-1"></i> Register Applicant &amp; Save File &rarr;
      </button>
    </div>
  </form>
</div>

<script>
function toggleManualInput(manualBoxId, selectId) {
  const box = document.getElementById(manualBoxId);
  const sel = document.getElementById(selectId);
  if (box.classList.contains('d-none')) {
    box.classList.remove('d-none');
    const input = box.querySelector('input');
    if (input) input.focus();
  } else {
    box.classList.add('d-none');
    const input = box.querySelector('input');
    if (input) input.value = '';
  }
}

function filterVisaPackages() {
  const countryId = document.getElementById('countrySelect').value;
  const categoryId = document.getElementById('categorySelect').value;
  const srvSelect = document.getElementById('serviceSelect');
  
  for (let i = 1; i < srvSelect.options.length; i++) {
    const opt = srvSelect.options[i];
    const matchCountry = !countryId || opt.dataset.countryId == countryId;
    const matchCat = !categoryId || opt.dataset.categoryId == categoryId;
    opt.style.display = (matchCountry && matchCat) ? '' : 'none';
  }
}

function updateServiceInfo() {
  const sel = document.getElementById('serviceSelect');
  const opt = sel.options[sel.selectedIndex];

  if (!opt || !opt.value) {
    document.getElementById('custSellingPrice').value = '0.00';
    document.getElementById('custSupplierCost').value = '0.00';
    document.getElementById('custTaxAmount').value = '0.00';
    document.getElementById('custTotalAmountDisplay').innerText = '$0.00';
    return;
  }

  const price = parseFloat(opt.dataset.price || 0);
  const cost = parseFloat(opt.dataset.cost || 0);
  const taxRate = parseFloat(opt.dataset.tax || 0);
  const duration = opt.dataset.duration || '30 Days';
  const entry = opt.dataset.entry || 'Single Entry';
  const processing = opt.dataset.processing || 'Normal';

  document.getElementById('custSellingPrice').value = price.toFixed(2);
  document.getElementById('custSupplierCost').value = cost.toFixed(2);

  // Auto-sync duration, entry, and processing dropdowns
  const durSel = document.getElementById('durationSelect');
  if (durSel) {
    for (let i = 0; i < durSel.options.length; i++) {
      if (durSel.options[i].value === duration) {
        durSel.selectedIndex = i;
        break;
      }
    }
  }

  const entrySel = document.getElementById('entrySetSelect');
  if (entrySel) {
    for (let i = 0; i < entrySel.options.length; i++) {
      if (entrySel.options[i].value === entry) {
        entrySel.selectedIndex = i;
        break;
      }
    }
  }

  const procSel = document.getElementById('processingSelect');
  if (procSel) {
    for (let i = 0; i < procSel.options.length; i++) {
      if (procSel.options[i].value === processing) {
        procSel.selectedIndex = i;
        break;
      }
    }
  }

  calcTotalCustomerPrice();
}

function calcTotalCustomerPrice() {
  const price = parseFloat(document.getElementById('custSellingPrice').value || 0);
  const discount = parseFloat(document.getElementById('custDiscount').value || 0);
  const net = Math.max(0, price - discount);
  const tax = parseFloat(document.getElementById('custTaxAmount').value || 0);
  const total = net + tax;
  document.getElementById('custTotalAmountDisplay').innerText = '$' + total.toFixed(2);
}

function toggleCustPaymentBox() {
  const isPayNow = document.getElementById('custPayNowOpt').checked;
  const box = document.getElementById('custPayDetailsBox');
  if (isPayNow) {
    box.classList.remove('d-none');
  } else {
    box.classList.add('d-none');
  }
}

function addCustomerDocRow() {
  const container = document.getElementById('customerDocContainer');
  const firstRow = container.querySelector('.customer-doc-row');
  const newRow = firstRow.cloneNode(true);
  newRow.querySelectorAll('input').forEach(inp => {
    if (inp.type === 'file') inp.value = '';
    else if (inp.type === 'text') inp.value = '';
  });
  container.appendChild(newRow);
}

function removeCustomerDocRow(btn) {
  const rows = document.querySelectorAll('.customer-doc-row');
  if (rows.length > 1) {
    btn.closest('.customer-doc-row').remove();
  }
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
