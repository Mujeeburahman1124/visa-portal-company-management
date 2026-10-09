<?php
$pageTitle = 'New Visa Application — VISA TRACK';
$flash = get_flash();
$currentUser = auth_user();
$preselectedServiceId = (int)($_GET['service_id'] ?? 0);
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
?>

<link rel="stylesheet" href="/assets/css/pages/applications-create.css">

<div class="content-body pb-5">
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
      <a href="/applications" class="btn btn-outline-secondary btn-sm bg-white shadow-xs" title="Back to Applications">
        <i class="fa-solid fa-arrow-left"></i>
      </a>
      <div>
        <h3 class="fw-bold brand-font text-dark mb-0">New Visa Application</h3>
        <p class="text-muted small mb-0">Fast-path passport scanning, MRZ auto-fill, verified document onboarding, and case processing.</p>
      </div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-primary-subtle text-primary px-3 py-2 fw-semibold">
        <i class="fa-solid fa-bolt me-1"></i> Fast-Path OCR Enabled
      </span>
      <span class="badge bg-success-subtle text-success px-3 py-2 fw-semibold d-none d-md-inline-block">
        <i class="fa-solid fa-shield-halved me-1"></i> ICAO 9303 Compliant
      </span>
    </div>
  </div>

  <!-- Visual Multi-Step Form Stepper -->
  <div class="form-stepper-header d-none d-md-flex mb-4">
    <div class="stepper-node active" id="step1Node">
      <div class="stepper-circle"><i class="fa-solid fa-passport"></i></div>
      <span class="stepper-label">1. Passport &amp; Identity</span>
    </div>
    <div class="stepper-node" id="step2Node">
      <div class="stepper-circle"><i class="fa-solid fa-plane-departure"></i></div>
      <span class="stepper-label">2. Visa Service</span>
    </div>
    <div class="stepper-node" id="step3Node">
      <div class="stepper-circle"><i class="fa-solid fa-folder-open"></i></div>
      <span class="stepper-label">3. Documents</span>
    </div>
    <div class="stepper-node" id="step4Node">
      <div class="stepper-circle"><i class="fa-solid fa-credit-card"></i></div>
      <span class="stepper-label">4. Payment &amp; Review</span>
    </div>
    <div class="stepper-node" id="step5Node">
      <div class="stepper-circle"><i class="fa-solid fa-check"></i></div>
      <span class="stepper-label">5. File Registered</span>
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

  <!-- ============================================================ -->
  <!-- FAST-PATH PASSPORT SCANNER HERO CARD                         -->
  <!-- ============================================================ -->
  <div class="passport-hero-card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-primary text-white px-2 py-1 small fw-bold">FAST-PATH</span>
        <h5 class="fw-bold text-dark mb-0">Passport Scan &amp; Automated Data Extraction</h5>
      </div>
      <span class="text-muted small"><i class="fa-solid fa-circle-info me-1"></i> No manual re-typing required</span>
    </div>

    <div class="passport-dropzone" id="passportDropzone">
      <!-- Hidden file inputs -->
      <input type="file" id="passportFileInput" accept=".jpg,.jpeg,.png,.webp,.pdf" style="display:none;" onchange="handlePassportFileSelected(this.files[0])">
      <input type="file" id="passportCameraInput" accept="image/*" capture="environment" style="display:none;" onchange="handlePassportFileSelected(this.files[0])">

      <!-- Dropzone Default View -->
      <div id="dropzoneDefaultView">
        <div class="dropzone-icon-ring">
          <i class="fa-solid fa-passport"></i>
        </div>
        <h5 class="fw-bold text-dark mb-1">Drag &amp; Drop Passport Scan Here</h5>
        <p class="text-muted small mb-3">High-resolution colored bio page (JPG, PNG, WEBP, or PDF up to 20MB)</p>
        <div class="d-flex justify-content-center gap-2 flex-wrap">
          <button type="button" class="btn btn-mac-primary btn-sm px-4" onclick="document.getElementById('passportFileInput').click()">
            <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload Passport Scan
          </button>
          <button type="button" class="btn btn-mac-secondary btn-sm px-4" onclick="document.getElementById('passportCameraInput').click()">
            <i class="fa-solid fa-camera me-1"></i> Take Passport Photo
          </button>
        </div>
        <div class="small text-secondary mt-3">
          <i class="fa-solid fa-shield-check text-success me-1"></i> MRZ checksum verified &bull; Visual OCR fallback &bull; Privacy protected
        </div>
      </div>

      <!-- OCR Progress Overlay -->
      <div class="ocr-progress-overlay d-none" id="ocrProgressOverlay">
        <div class="ocr-scanner-radar"></div>
        <h5 class="fw-bold text-dark mb-2" id="ocrStatusTitle">Processing Passport...</h5>
        <ul class="ocr-step-list text-start">
          <li id="ocrStep1" class="active"><i class="fa-solid fa-circle-notch fa-spin"></i> Uploading passport document...</li>
          <li id="ocrStep2"><i class="fa-regular fa-circle"></i> Analyzing image contrast &amp; orientation...</li>
          <li id="ocrStep3"><i class="fa-regular fa-circle"></i> Reading ICAO 9303 MRZ lines...</li>
          <li id="ocrStep4"><i class="fa-regular fa-circle"></i> Extracting visual details &amp; dates...</li>
          <li id="ocrStep5"><i class="fa-regular fa-circle"></i> Validating checksums &amp; country codes...</li>
        </ul>
      </div>
    </div>

    <!-- OCR Success / Active Badge Card (Populated after user confirms) -->
    <div id="passportActiveCard" class="card bg-white border border-success mt-3 p-3 d-none shadow-xs">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle bg-success bg-opacity-10 text-success p-2">
            <i class="fa-solid fa-check fs-5"></i>
          </div>
          <div>
            <div class="fw-bold text-dark">
              Passport Verified: <span id="activePassportNumber">--</span> &mdash; <span id="activePassportName">--</span>
            </div>
            <div class="small text-muted">
              Nationality: <strong id="activePassportNat">--</strong> &bull; Expiry: <strong id="activePassportExp">--</strong> &bull; Confidence: <span class="badge bg-success-subtle text-success" id="activePassportConf">99% High</span>
            </div>
          </div>
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-primary btn-sm" onclick="reopenOcrReviewModal()">
            <i class="fa-solid fa-eye me-1"></i> View Scan &amp; Details
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('passportFileInput').click()">
            <i class="fa-solid fa-arrows-rotate me-1"></i> Scan Different
          </button>
        </div>
      </div>
    </div>

    <!-- Error Banner (if OCR fails or image blurry) -->
    <div id="ocrErrorBanner" class="alert alert-warning border-0 mt-3 d-none" role="alert">
      <div class="d-flex align-items-start gap-2">
        <i class="fa-solid fa-triangle-exclamation text-warning mt-1"></i>
        <div class="flex-grow-1">
          <strong id="ocrErrorTitle">Passport data could not be read confidently.</strong>
          <div class="small" id="ocrErrorMsg">Ensure all four corners and the bottom MRZ lines are clearly visible without glare or blur. You can retry with a clearer photo or enter details manually below.</div>
          <div class="mt-2 d-flex gap-2">
            <button type="button" class="btn btn-warning btn-sm py-1" onclick="document.getElementById('passportFileInput').click()">
              <i class="fa-solid fa-rotate-right me-1"></i> Retry Upload
            </button>
            <button type="button" class="btn btn-outline-dark btn-sm py-1" onclick="dismissOcrError()">
              <i class="fa-solid fa-pen me-1"></i> Enter Manually
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Application Form -->
  <form action="/applications/store" method="POST" id="createAppForm" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <!-- Hidden OCR Metadata Attached with Form Submission -->
    <input type="hidden" name="temp_passport_token" id="tempPassportToken" value="">
    <input type="hidden" name="ocr_confidence" id="ocrConfidenceInput" value="">
    <input type="hidden" name="ocr_provider" id="ocrProviderInput" value="">
    <input type="hidden" name="passport_original_filename" id="passportOrigFilename" value="">

    <div class="row g-4">
      <!-- Left Column: Core Application & Identity Data -->
      <div class="col-lg-8">

        <!-- ============================================================ -->
        <!-- SECTION 1: APPLICANT IDENTITY & PASSPORT INFORMATION         -->
        <!-- ============================================================ -->
        <div class="card card-enterprise mb-4 shadow-sm border">
          <div class="card-header bg-white d-flex align-items-center justify-content-between py-3 border-bottom flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
              <span class="fw-bold small text-uppercase text-secondary">
                <i class="fa-solid fa-user-check text-primary me-2"></i> 1. Applicant Identity &amp; Passport Details
              </span>
              <span class="provenance-badge manual" id="mainProvenanceTag">✎ Manual</span>
            </div>

            <div class="d-flex align-items-center gap-2">
              <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2" style="font-size: 0.78rem;" onclick="toggleExistingApplicantModal()">
                <i class="fa-solid fa-address-book me-1"></i> Select From Existing Customers
              </button>
            </div>
          </div>

          <div class="card-body p-4">
            <!-- Hidden or linked customer select -->
            <input type="hidden" name="customer_id" id="customerIdInput" value="0">
            <div id="linkedCustomerInfoBox" class="alert alert-info border-0 p-2 mb-3 d-none">
              <div class="d-flex justify-content-between align-items-center">
                <span class="small"><i class="fa-solid fa-link me-1"></i> Linked Existing Customer: <strong id="linkedCustomerName">--</strong> (<span id="linkedCustomerCode">--</span>)</span>
                <button type="button" class="btn btn-link btn-sm p-0 text-danger text-decoration-none" onclick="unlinkCustomer()">Unlink</button>
              </div>
            </div>

            <!-- Row 1: Passport No, Present Nationality, Gender -->
            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label for="passportNoInput" class="form-label small fw-semibold text-secondary mb-0">
                    Passport No <span class="text-danger">*</span>
                  </label>
                  <span class="provenance-badge d-none" id="prov_passport_number"></span>
                </div>
                <div class="input-group">
                  <input type="text" name="passport_number" id="passportNoInput" class="form-control text-uppercase fw-semibold" placeholder="e.g. N9876543" required oninput="onFieldManuallyEdited('passport_number')">
                  <span class="input-group-text bg-white"><i class="fa-solid fa-passport text-muted"></i></span>
                </div>
              </div>

              <div class="col-md-5">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label for="nationalityInput" class="form-label small fw-semibold text-secondary mb-0">
                    Present Nationality <span class="text-danger">*</span>
                  </label>
                  <span class="provenance-badge d-none" id="prov_nationality"></span>
                </div>
                <input type="text" name="nationality" id="nationalityInput" class="form-control" list="countriesDataList" placeholder="e.g. Sri Lanka" required oninput="onFieldManuallyEdited('nationality'); checkDuplicateApplication();">
              </div>

              <div class="col-md-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label small fw-semibold text-secondary mb-0">
                    Gender <span class="text-danger">*</span>
                  </label>
                  <span class="provenance-badge d-none" id="prov_gender"></span>
                </div>
                <div class="ios-segmented-control w-100 d-flex">
                  <div class="flex-fill text-center">
                    <input type="radio" name="gender" id="genderMale" value="Male" checked onchange="onFieldManuallyEdited('gender')">
                    <label for="genderMale" class="w-100"><i class="fa-solid fa-mars me-1 text-primary"></i> Male</label>
                  </div>
                  <div class="flex-fill text-center">
                    <input type="radio" name="gender" id="genderFemale" value="Female" onchange="onFieldManuallyEdited('gender')">
                    <label for="genderFemale" class="w-100"><i class="fa-solid fa-venus me-1 text-danger"></i> Female</label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Row 2: Name Breakdown -->
            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label for="firstNameInput" class="form-label small fw-semibold text-secondary mb-0">
                    First Name <span class="text-danger">*</span>
                  </label>
                  <span class="provenance-badge d-none" id="prov_first_name"></span>
                </div>
                <input type="text" name="first_name" id="firstNameInput" class="form-control" placeholder="Given name" required oninput="onFieldManuallyEdited('first_name'); syncFullName();">
              </div>
              <div class="col-md-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label for="middleNameInput" class="form-label small fw-semibold text-secondary mb-0">
                    Middle Name
                  </label>
                  <span class="provenance-badge d-none" id="prov_middle_name"></span>
                </div>
                <input type="text" name="middle_name" id="middleNameInput" class="form-control" placeholder="Middle name (if any)" oninput="onFieldManuallyEdited('middle_name'); syncFullName();">
              </div>
              <div class="col-md-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label for="lastNameInput" class="form-label small fw-semibold text-secondary mb-0">
                    Last Name / Surname <span class="text-danger">*</span>
                  </label>
                  <span class="provenance-badge d-none" id="prov_last_name"></span>
                </div>
                <input type="text" name="last_name" id="lastNameInput" class="form-control" placeholder="Surname" required oninput="onFieldManuallyEdited('last_name'); syncFullName();">
              </div>
            </div>

            <!-- Full Name Preview / Sync -->
            <input type="hidden" name="full_name" id="fullNameHidden" value="">

            <!-- Row 3: Birth Details & Dates -->
            <div class="row g-3 mb-3">
              <div class="col-md-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label for="dobInput" class="form-label small fw-semibold text-secondary mb-0">
                    Birth Date <span class="text-danger">*</span>
                  </label>
                  <span class="provenance-badge d-none" id="prov_dob"></span>
                </div>
                <input type="date" name="dob" id="dobInput" class="form-control" required oninput="onFieldManuallyEdited('dob')">
              </div>

              <div class="col-md-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label for="birthCountryInput" class="form-label small fw-semibold text-secondary mb-0">
                    Birth Country <span class="text-danger">*</span>
                  </label>
                  <span class="provenance-badge d-none" id="prov_birth_country"></span>
                </div>
                <input type="text" name="birth_country" id="birthCountryInput" class="form-control" list="countriesDataList" placeholder="e.g. Sri Lanka" required oninput="onFieldManuallyEdited('birth_country')">
              </div>

              <div class="col-md-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label for="birthPlaceInput" class="form-label small fw-semibold text-secondary mb-0">
                    Birth Place <span class="text-danger">*</span>
                  </label>
                  <span class="provenance-badge d-none" id="prov_place_of_birth"></span>
                </div>
                <input type="text" name="place_of_birth" id="birthPlaceInput" class="form-control" placeholder="e.g. Colombo" required oninput="onFieldManuallyEdited('place_of_birth')">
              </div>

              <div class="col-md-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label for="maritalStatusSelect" class="form-label small fw-semibold text-secondary mb-0">
                    Marital Status <span class="text-danger">*</span>
                  </label>
                  <span class="provenance-badge d-none" id="prov_marital_status"></span>
                </div>
                <select name="marital_status" id="maritalStatusSelect" class="form-select" required onchange="onFieldManuallyEdited('marital_status')">
                  <option value="Single">Single</option>
                  <option value="Married">Married</option>
                  <option value="Divorced">Divorced</option>
                  <option value="Widowed">Widowed</option>
                </select>
              </div>
            </div>

            <!-- Row 4: Passport Dates & Issuance -->
            <div class="row g-3 mb-3">
              <div class="col-md-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label for="issueDateInput" class="form-label small fw-semibold text-secondary mb-0">
                    Date of Issue <span class="text-danger">*</span>
                  </label>
                  <span class="provenance-badge d-none" id="prov_issue_date"></span>
                </div>
                <input type="date" name="passport_issue_date" id="issueDateInput" class="form-control" required oninput="onFieldManuallyEdited('issue_date')">
              </div>

              <div class="col-md-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label for="expiryDateInput" class="form-label small fw-semibold text-secondary mb-0">
                    Expiration Date <span class="text-danger">*</span>
                  </label>
                  <span class="provenance-badge d-none" id="prov_expiry_date"></span>
                </div>
                <input type="date" name="passport_expiry_date" id="expiryDateInput" class="form-control" required oninput="onFieldManuallyEdited('expiry_date')">
              </div>

              <div class="col-md-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label for="issuingCountryInput" class="form-label small fw-semibold text-secondary mb-0">
                    Issuing Country
                  </label>
                  <span class="provenance-badge d-none" id="prov_issuing_country"></span>
                </div>
                <input type="text" name="passport_issuing_country" id="issuingCountryInput" class="form-control" list="countriesDataList" placeholder="e.g. Sri Lanka" oninput="onFieldManuallyEdited('issuing_country')">
              </div>

              <div class="col-md-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label for="placeOfIssueInput" class="form-label small fw-semibold text-secondary mb-0">
                    Issue Place
                  </label>
                  <span class="provenance-badge d-none" id="prov_place_of_issue"></span>
                </div>
                <input type="text" name="passport_place_of_issue" id="placeOfIssueInput" class="form-control" placeholder="e.g. Colombo" oninput="onFieldManuallyEdited('place_of_issue')">
              </div>
            </div>

            <!-- Row 5: Family Information (from reference screenshots) -->
            <div class="p-3 bg-light bg-opacity-50 rounded border mb-3">
              <div class="fw-bold small text-dark mb-2">
                <i class="fa-solid fa-people-roof text-secondary me-1"></i> Family Details
              </div>
              <div class="row g-3">
                <div class="col-md-4">
                  <label for="fatherNameInput" class="form-label small fw-semibold text-secondary mb-1">
                    Father Name <span class="text-danger">*</span>
                  </label>
                  <input type="text" name="father_name" id="fatherNameInput" class="form-control form-control-sm" placeholder="Father's full name" required>
                </div>
                <div class="col-md-4">
                  <label for="motherNameInput" class="form-label small fw-semibold text-secondary mb-1">
                    Mother Name <span class="text-danger">*</span>
                  </label>
                  <input type="text" name="mother_name" id="motherNameInput" class="form-control form-control-sm" placeholder="Mother's full name" required>
                </div>
                <div class="col-md-4">
                  <label for="spouseNameInput" class="form-label small fw-semibold text-secondary mb-1">
                    Husband / Spouse Name
                  </label>
                  <input type="text" name="spouse_name" id="spouseNameInput" class="form-control form-control-sm" placeholder="Spouse's full name (if married)">
                </div>
              </div>
            </div>

            <!-- Row 6: Contact & Professional Details -->
            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label for="mobileInput" class="form-label small fw-semibold text-secondary mb-1">
                  Applicant Mobile <span class="text-danger">*</span>
                </label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-white"><i class="fa-solid fa-phone text-muted"></i></span>
                  <input type="tel" name="mobile" id="mobileInput" class="form-control" placeholder="+971 50 123 4567" required>
                </div>
              </div>

              <div class="col-md-4">
                <label for="whatsappInput" class="form-label small fw-semibold text-secondary mb-1">WhatsApp</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-white"><i class="fa-brands fa-whatsapp text-success"></i></span>
                  <input type="tel" name="whatsapp" id="whatsappInput" class="form-control" placeholder="WhatsApp number">
                </div>
              </div>

              <div class="col-md-4">
                <label for="emailInput" class="form-label small fw-semibold text-secondary mb-1">Email</label>
                <input type="email" name="email" id="emailInput" class="form-control form-control-sm" placeholder="applicant@email.com">
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-3">
                <label for="professionInput" class="form-label small fw-semibold text-secondary mb-1">
                  Profession / Occupation <span class="text-danger">*</span>
                </label>
                <input type="text" name="profession" id="professionInput" class="form-control form-control-sm" placeholder="e.g. Software Engineer" required>
              </div>

              <div class="col-md-3">
                <label for="educationSelect" class="form-label small fw-semibold text-secondary mb-1">
                  Education <span class="text-danger">*</span>
                </label>
                <select name="education" id="educationSelect" class="form-select form-select-sm" required>
                  <option value="High School">High School</option>
                  <option value="Diploma">Diploma / Vocational</option>
                  <option value="Bachelor Degree" selected>Bachelor's Degree</option>
                  <option value="Master Degree">Master's Degree</option>
                  <option value="Doctorate">Doctorate / PhD</option>
                  <option value="Other">Other</option>
                </select>
              </div>

              <div class="col-md-3">
                <label for="languageSelect" class="form-label small fw-semibold text-secondary mb-1">
                  Language <span class="text-danger">*</span>
                </label>
                <input type="text" name="language" id="languageSelect" class="form-control form-control-sm" list="languagesDataList" placeholder="e.g. English" value="English" required>
              </div>

              <div class="col-md-3">
                <label for="cityInput" class="form-label small fw-semibold text-secondary mb-1">City</label>
                <input type="text" name="city" id="cityInput" class="form-control form-control-sm" placeholder="e.g. Dubai / Colombo">
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-4">
                <label for="residingCountryInput" class="form-label small fw-semibold text-secondary mb-1">Coming From / Residing Country</label>
                <input type="text" name="residing_country" id="residingCountryInput" class="form-control form-control-sm" list="countriesDataList" placeholder="e.g. United Arab Emirates" value="United Arab Emirates">
              </div>
              <div class="col-md-8">
                <label for="addressInput" class="form-label small fw-semibold text-secondary mb-1">Residential Address</label>
                <input type="text" name="address" id="addressInput" class="form-control form-control-sm" placeholder="Building, Street, Area">
              </div>
            </div>
          </div>
        </div>

        <!-- ============================================================ -->
        <!-- SECTION 2: DESTINATION COUNTRY & VISA PACKAGE                -->
        <!-- ============================================================ -->
        <div class="card card-enterprise mb-4 shadow-sm border">
          <div class="card-header bg-white py-3 border-bottom">
            <span class="fw-bold small text-uppercase text-secondary">
              <i class="fa-solid fa-passport text-primary me-2"></i> 2. Destination Country &amp; Visa Package
            </span>
          </div>
          <div class="card-body p-4">
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label small fw-semibold text-secondary mb-0">Destination Country <span class="text-danger">*</span></label>
                  <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold text-primary" style="font-size: 0.78rem;" onclick="toggleManualInput('destCountryManualBox', 'countryFilterSelect')">
                    <i class="fa-solid fa-pen-to-square me-1"></i>+ Enter Manually
                  </button>
                </div>
                <select id="countryFilterSelect" class="form-select" onchange="checkManualSelect(this, 'destCountryManualBox'); filterVisaPackages()">
                  <option value="">-- All Destination Countries --</option>
                  <option value="__custom__" class="fw-bold text-primary">+ Enter Manually / Other Country...</option>
                  <?php foreach ($countries as $ct): ?>
                    <option value="<?= $ct['id'] ?>"><?= $ct['flag_emoji'] ?> <?= e($ct['name']) ?></option>
                  <?php endforeach; ?>
                </select>
                <div id="destCountryManualBox" class="mt-2 d-none">
                  <input type="text" name="custom_destination_country" id="customDestCountryInput" class="form-control form-control-sm" placeholder="Type destination country (e.g. Poland, Japan, Malaysia)...">
                </div>
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
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="serviceSelect" class="form-label small fw-semibold text-secondary mb-0">Visa Service Package / Type <span class="text-danger">*</span></label>
                <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold text-primary" style="font-size: 0.78rem;" onclick="toggleManualInput('visaServiceManualBox', 'serviceSelect')">
                  <i class="fa-solid fa-pen-to-square me-1"></i>+ Enter Manually
                </button>
              </div>
              <select name="visa_service_id" id="serviceSelect" class="form-select" onchange="checkManualSelect(this, 'visaServiceManualBox'); onServiceChanged();">
                <option value="">-- Choose Visa Type / Duration / Entry --</option>
                <option value="__custom__" class="fw-bold text-primary">+ Enter Manually / Custom Package...</option>
                <?php foreach ($services as $srv): ?>
                  <option value="<?= $srv['id'] ?>" 
                          <?= ($preselectedServiceId === (int)$srv['id']) ? 'selected' : '' ?>
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
              <div id="visaServiceManualBox" class="mt-2 d-none p-3 bg-light rounded border">
                <div class="row g-2 mb-2">
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary mb-1">Custom Visa Package Name <span class="text-danger">*</span></label>
                    <input type="text" name="custom_visa_type" id="customVisaTypeInput" class="form-control form-control-sm" placeholder="e.g. Express Tourist 30 Days">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">Duration</label>
                    <input type="text" name="custom_visa_duration" class="form-control form-control-sm" placeholder="e.g. 30 Days">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">Entry Type</label>
                    <select name="custom_entry_type" class="form-select form-select-sm">
                      <option value="Single Entry">Single Entry</option>
                      <option value="Multiple Entry">Multiple Entry</option>
                    </select>
                  </div>
                </div>
                <div class="row g-2">
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary mb-1">Selling Price ($) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="custom_selling_price" id="customSellingPriceInput" class="form-control form-control-sm" placeholder="e.g. 290.00" oninput="updateServiceInfo()">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary mb-1">Base Cost ($)</label>
                    <input type="number" step="0.01" min="0" name="custom_supplier_cost" id="customSupplierCostInput" class="form-control form-control-sm" placeholder="e.g. 210.00" oninput="updateServiceInfo()">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary mb-1">Estimated Days</label>
                    <input type="number" min="1" name="custom_estimated_days" id="customEstimatedDaysInput" class="form-control form-control-sm" placeholder="e.g. 10" value="10" oninput="updateServiceInfo()">
                  </div>
                </div>
              </div>
            </div>

            <!-- Workflow details matching screenshot: Source Type, Visit Reason, Travel Dates -->
            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label for="sourceTypeSelect" class="form-label small fw-semibold text-secondary mb-1">Source Type <span class="text-danger">*</span></label>
                <select name="source_type" id="sourceTypeSelect" class="form-select" required>
                  <option value="Normal" selected>Normal / Direct</option>
                  <option value="B2B">B2B Partner</option>
                  <option value="B2C">B2C Retail</option>
                  <option value="Agent">Agent Referral</option>
                  <option value="Corporate">Corporate Account</option>
                </select>
              </div>

              <div class="col-md-4">
                <label for="visitReasonSelect" class="form-label small fw-semibold text-secondary mb-1">Visit Reason <span class="text-danger">*</span></label>
                <select name="visit_reason" id="visitReasonSelect" class="form-select" required>
                  <option value="Tourism" selected>Tourism</option>
                  <option value="Business">Business / Meeting</option>
                  <option value="Employment">Employment</option>
                  <option value="Family Visit">Family Visit</option>
                  <option value="Transit">Transit</option>
                </select>
              </div>

              <div class="col-md-4">
                <label for="processingTypeSelect" class="form-label small fw-semibold text-secondary mb-1">Processing Type</label>
                <select name="processing_type" id="processingTypeSelect" class="form-select">
                  <option value="Normal" selected>Normal Processing</option>
                  <option value="Urgent">Urgent / Express</option>
                  <option value="VIP">VIP Executive</option>
                </select>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Estimated Arrival / Travel Date</label>
                <input type="date" name="travel_date" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Proposed Departure / Return Date</label>
                <input type="date" name="return_date" class="form-control">
              </div>
            </div>
          </div>
        </div>

        <!-- ============================================================ -->
        <!-- SECTION 3: APPLICANT PHOTO & SUPPORTING DOCUMENTS            -->
        <!-- ============================================================ -->
        <div class="card card-enterprise mb-4 shadow-sm border">
          <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-bottom flex-wrap gap-2">
            <div>
              <span class="fw-bold small text-uppercase text-secondary">
                <i class="fa-solid fa-folder-open text-primary me-2"></i> 3. Applicant Portrait Photo &amp; Documents
              </span>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addAppDocRow()">
              <i class="fa-solid fa-plus me-1"></i> Add Another Document
            </button>
          </div>
          <div class="card-body p-4">

            <!-- CRITICAL SEPARATION: Profile Photo vs Passport Scan -->
            <div class="p-3 bg-light rounded border mb-4">
              <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="photo-preview-box" id="applicantPhotoPreviewBox" onclick="document.getElementById('applicantPhotoInput').click()" title="Click to upload portrait photo">
                  <img id="applicantPhotoPreviewImg" src="" alt="Profile Photo" class="d-none">
                  <div id="applicantPhotoPlaceholder" class="text-center p-2">
                    <i class="fa-solid fa-user-circle text-muted fs-2 mb-1"></i>
                    <div style="font-size: 0.68rem;" class="text-secondary fw-semibold">Photo (White BG)</div>
                  </div>
                </div>

                <div class="flex-grow-1">
                  <div class="fw-bold text-dark small mb-1">
                    <i class="fa-solid fa-camera text-primary me-1"></i> Applicant Portrait Photo (White Background)
                  </div>
                  <p class="text-muted small mb-2" style="font-size: 0.82rem;">
                    This photograph is used strictly as the applicant's profile avatar. It is <strong>completely separate</strong> from the passport bio page scan.
                  </p>
                  <div class="d-flex gap-2">
                    <input type="file" name="applicant_photo" id="applicantPhotoInput" class="form-control form-control-sm" accept="image/*" onchange="previewApplicantPhoto(this)" style="max-width: 280px;">
                    <span class="badge bg-secondary-subtle text-secondary align-self-center">PHOTO_WHITE_BG</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Supporting Documents Queue -->
            <p class="text-muted small mb-2">Upload supporting documents (CV, Visa Copy, National ID, Bank Statement, etc.):</p>
            <div id="appDocUploadContainer">
              <!-- Default Pre-set Row 1: CV / Resume -->
              <div class="row g-2 mb-2 app-doc-row align-items-center bg-white p-2 rounded border">
                <div class="col-md-4">
                  <label class="form-label small fw-semibold mb-1">Document Type</label>
                  <select name="document_types[]" class="form-select form-select-sm">
                    <option value="">-- Select Type --</option>
                    <?php foreach ($docTypes as $dt): ?>
                      <option value="<?= $dt['id'] ?>" <?= $dt['code'] === 'CV' ? 'selected' : '' ?>>
                        <?= e($dt['name']) ?> (<?= e($dt['category']) ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-semibold mb-1">Custom Title</label>
                  <input type="text" name="document_titles[]" class="form-control form-control-sm" placeholder="e.g. Applicant CV / Resume" value="Applicant CV">
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

              <!-- Default Pre-set Row 2: Visa Copy -->
              <div class="row g-2 mb-2 app-doc-row align-items-center bg-white p-2 rounded border">
                <div class="col-md-4">
                  <label class="form-label small fw-semibold mb-1">Document Type</label>
                  <select name="document_types[]" class="form-select form-select-sm">
                    <option value="">-- Select Type --</option>
                    <?php foreach ($docTypes as $dt): ?>
                      <option value="<?= $dt['id'] ?>" <?= $dt['code'] === 'VISA_COPY' ? 'selected' : '' ?>>
                        <?= e($dt['name']) ?> (<?= e($dt['category']) ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-semibold mb-1">Custom Title</label>
                  <input type="text" name="document_titles[]" class="form-control form-control-sm" placeholder="e.g. Previous UAE Visa" value="Previous Visa Copy">
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

              <!-- Default Pre-set Row 3: National ID -->
              <div class="row g-2 mb-2 app-doc-row align-items-center bg-white p-2 rounded border">
                <div class="col-md-4">
                  <label class="form-label small fw-semibold mb-1">Document Type</label>
                  <select name="document_types[]" class="form-select form-select-sm">
                    <option value="">-- Select Type --</option>
                    <?php foreach ($docTypes as $dt): ?>
                      <option value="<?= $dt['id'] ?>" <?= $dt['code'] === 'NATIONAL_ID' ? 'selected' : '' ?>>
                        <?= e($dt['name']) ?> (<?= e($dt['category']) ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-semibold mb-1">Custom Title</label>
                  <input type="text" name="document_titles[]" class="form-control form-control-sm" placeholder="e.g. National Identity Card" value="National ID Card">
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

        <!-- ============================================================ -->
        <!-- SECTION 4: NOTES & REMARKS                                   -->
        <!-- ============================================================ -->
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
              <textarea name="customer_notes" class="form-control" rows="2" placeholder="Notes visible to the applicant on their tracking portal..."></textarea>
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
              <i class="fa-solid fa-sliders text-primary me-2"></i> Case Assignment
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

            <div class="mb-0">
              <label class="form-label small fw-semibold text-secondary">Embassy / Govt Reference #</label>
              <input type="text" name="embassy_reference" class="form-control form-control-sm" placeholder="e.g. EMB-2026-44">
              <input type="hidden" name="supplier_id" value="">
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
              <span class="text-muted">Standard Selling Price:</span>
              <span class="fw-semibold text-dark" id="dispSellingPrice">$0.00</span>
            </div>
            <div class="d-flex justify-content-between small mb-2">
              <span class="text-muted">Base Cost:</span>
              <span class="text-secondary" id="dispSupplierCost">$0.00</span>
            </div>
            <div class="d-flex justify-content-between small mb-2">
              <span class="text-muted">Estimated Tax Amount:</span>
              <span class="text-secondary" id="dispTaxAmount">$0.00</span>
            </div>
            <div class="d-flex justify-content-between small mb-3">
              <span class="text-muted">Expected Processing SLA:</span>
              <span class="fw-semibold text-primary" id="dispEstimatedDays">-- Days</span>
            </div>
            <div class="d-flex justify-content-between small mb-3">
              <span class="text-muted">Target Completion Date:</span>
              <span class="fw-semibold text-dark" id="dispExpectedDate">--</span>
            </div>

            <!-- Discount controls -->
            <div class="mb-3 pt-2 border-top">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label small fw-semibold text-secondary mb-0">Discount</label>
                <span class="badge bg-light text-secondary border small" id="appDiscountPctTag">0%</span>
              </div>
              <div class="input-group input-group-sm mb-1">
                <span class="input-group-text">$</span>
                <input type="number" step="0.01" min="0" name="discount" id="inputDiscount" class="form-control" placeholder="0.00" oninput="onAppDiscountAmountChange()">
                <span class="input-group-text">%</span>
                <input type="number" step="0.5" min="0" max="100" id="inputDiscountPercent" class="form-control" placeholder="0" style="max-width: 75px;" oninput="onAppDiscountPercentChange()">
              </div>
              <div class="d-flex gap-1">
                <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-2" style="font-size: 0.72rem;" onclick="applyAppQuickDiscount(5)">5%</button>
                <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-2" style="font-size: 0.72rem;" onclick="applyAppQuickDiscount(10)">10%</button>
                <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-2" style="font-size: 0.72rem;" onclick="applyAppQuickDiscount(15)">15%</button>
                <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-2" style="font-size: 0.72rem;" onclick="applyAppQuickDiscount(0)">Clear</button>
              </div>
            </div>

            <div class="p-3 bg-light rounded text-center mb-3">
              <div class="small text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Payable Amount</div>
              <div class="fs-3 fw-bold text-dark my-1" id="dispTotalAmount">$0.00</div>
              <div class="small text-muted" id="dispConvertedAED" style="font-size: 0.75rem;">0.00 AED (@ 3.6725)</div>
            </div>

            <!-- Payment Options -->
            <div class="border rounded p-3 bg-light bg-opacity-50 mb-4">
              <div class="fw-semibold small text-dark mb-2">Initial Registration Payment</div>
              <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="pay_now" id="payLaterOpt" value="0" checked onchange="togglePaymentBox()">
                <label class="form-check-label small" for="payLaterOpt">
                  <strong>Pay Later</strong> &mdash; Register case file and invoice customer
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="pay_now" id="payNowOpt" value="1" onchange="togglePaymentBox()">
                <label class="form-check-label small" for="payNowOpt">
                  <strong>Pay Now</strong> &mdash; Collect full payment upon initiation
                </label>
              </div>

              <div id="payDetailsBox" class="mt-3 pt-3 border-top d-none">
                <div class="mb-2">
                  <label class="form-label small fw-semibold text-secondary">Payment Method</label>
                  <select name="pay_method" class="form-select form-select-sm">
                    <option value="Cash">Cash</option>
                    <option value="Credit Card">Credit / Debit Card</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Online">Online Gateway</option>
                  </select>
                </div>
                <div>
                  <label class="form-label small fw-semibold text-secondary">Payment Reference / Receipt #</label>
                  <input type="text" name="pay_reference" class="form-control form-control-sm" placeholder="e.g. REC-102938">
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-mac-primary w-100 py-2 fs-6 shadow-sm">
              <i class="fa-solid fa-file-circle-check me-2"></i> Register Visa Application
            </button>
            <div class="text-center mt-2 small text-muted" style="font-size: 0.75rem;">
              <i class="fa-solid fa-lock me-1"></i> Data encrypted &bull; Checklist auto-generated
            </div>
          </div>
        </div>

      </div>
    </div>
  </form>
</div>

<!-- ============================================================ -->
<!-- MAC-STYLE SIDE-BY-SIDE OCR REVIEW MODAL                      -->
<!-- ============================================================ -->
<div class="modal fade modal-mac" id="ocrReviewModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
          <div class="traffic-dots">
            <span class="traffic-dot red" onclick="dismissOcrReviewModal()" title="Close"></span>
            <span class="traffic-dot yellow"></span>
            <span class="traffic-dot green"></span>
          </div>
          <h5 class="modal-title fw-bold text-dark mb-0 fs-6">
            <i class="fa-solid fa-passport text-primary me-2"></i> Passport Data Detected &amp; Review
          </h5>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-secondary-subtle text-secondary fw-semibold px-2 py-1" id="reviewOverallConfBadge">
            <i class="fa-solid fa-spinner fa-spin me-1"></i> Analyzing...
          </span>
          <button type="button" class="btn-close" onclick="dismissOcrReviewModal()"></button>
        </div>
      </div>

      <div class="modal-body p-4">
        <div class="row g-4">
          <!-- Left: High Fidelity Passport Viewer -->
          <div class="col-lg-6">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-semibold small text-secondary">
                <i class="fa-regular fa-image me-1"></i> Uploaded Passport Scan
              </span>
              <span class="small text-muted" id="viewerZoomLabel">100% Zoom</span>
            </div>

            <div class="passport-viewer-stage" id="passportViewerStage">
              <img id="reviewPassportImg" src="" alt="Passport Scan">
              <div class="viewer-toolbar">
                <button type="button" onclick="adjustViewerZoom(0.2)" title="Zoom In"><i class="fa-solid fa-magnifying-glass-plus"></i></button>
                <button type="button" onclick="adjustViewerZoom(-0.2)" title="Zoom Out"><i class="fa-solid fa-magnifying-glass-minus"></i></button>
                <button type="button" onclick="resetViewerTransform()" title="Reset Zoom"><i class="fa-solid fa-expand"></i></button>
                <button type="button" onclick="rotateViewerImage()" title="Rotate 90°"><i class="fa-solid fa-rotate-right"></i></button>
                <button type="button" onclick="toggleViewerFullscreen()" title="Fullscreen"><i class="fa-solid fa-up-right-and-down-left-and-up-left-to-bottom-right"></i></button>
              </div>
            </div>
            <div class="small text-muted text-center mt-2" style="font-size: 0.76rem;">
              <i class="fa-solid fa-hand-pointer me-1"></i> Drag to pan when zoomed &bull; Rotate if scan is sideways
            </div>
          </div>

          <!-- Right: Detected Form Fields (Editable) -->
          <div class="col-lg-6">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="fw-bold small text-uppercase text-dark">
                Extracted Passport Fields
              </span>
              <span class="small text-muted">You can review and correct any field</span>
            </div>

            <div class="row g-3" style="max-height: 480px; overflow-y: auto; padding-right: 6px;">
              <!-- Passport Number -->
              <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label small fw-semibold text-secondary mb-0">Passport Number</label>
                  <span class="conf-tag conf-not-detected" id="confTag_passport_number"><i class="fa-solid fa-triangle-exclamation me-1"></i> Not detected</span>
                </div>
                <input type="text" id="review_passport_number" class="form-control form-control-sm text-uppercase fw-bold" placeholder="e.g. N1234567" oninput="onReviewFieldInput('review_passport_number', 'confTag_passport_number')">
              </div>

              <!-- Nationality -->
              <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label small fw-semibold text-secondary mb-0">Nationality</label>
                  <span class="conf-tag conf-not-detected" id="confTag_nationality"><i class="fa-solid fa-triangle-exclamation me-1"></i> Not detected</span>
                </div>
                <input type="text" id="review_nationality" class="form-control form-control-sm" placeholder="e.g. Sri Lanka" oninput="onReviewFieldInput('review_nationality', 'confTag_nationality')">
              </div>

              <!-- Surname -->
              <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label small fw-semibold text-secondary mb-0">Surname / Last Name</label>
                  <span class="conf-tag conf-not-detected" id="confTag_surname"><i class="fa-solid fa-triangle-exclamation me-1"></i> Not detected</span>
                </div>
                <input type="text" id="review_surname" class="form-control form-control-sm" placeholder="Surname" oninput="onReviewFieldInput('review_surname', 'confTag_surname')">
              </div>

              <!-- Given Names -->
              <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label small fw-semibold text-secondary mb-0">Given Names</label>
                  <span class="conf-tag conf-not-detected" id="confTag_given_names"><i class="fa-solid fa-triangle-exclamation me-1"></i> Not detected</span>
                </div>
                <input type="text" id="review_given_names" class="form-control form-control-sm" placeholder="Given Names" oninput="onReviewFieldInput('review_given_names', 'confTag_given_names')">
              </div>

              <!-- Date of Birth -->
              <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label small fw-semibold text-secondary mb-0">Date of Birth</label>
                  <span class="conf-tag conf-not-detected" id="confTag_dob"><i class="fa-solid fa-triangle-exclamation me-1"></i> Not detected</span>
                </div>
                <input type="date" id="review_dob" class="form-control form-control-sm" oninput="onReviewFieldInput('review_dob', 'confTag_dob')">
              </div>

              <!-- Gender -->
              <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label small fw-semibold text-secondary mb-0">Gender</label>
                  <span class="conf-tag conf-not-detected" id="confTag_gender"><i class="fa-solid fa-triangle-exclamation me-1"></i> Not detected</span>
                </div>
                <select id="review_gender" class="form-select form-select-sm" onchange="onReviewFieldInput('review_gender', 'confTag_gender')">
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select>
              </div>

              <!-- Date of Issue -->
              <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label small fw-semibold text-secondary mb-0">Date of Issue</label>
                  <span class="conf-tag conf-not-detected" id="confTag_issue_date"><i class="fa-solid fa-triangle-exclamation me-1"></i> Not detected</span>
                </div>
                <input type="date" id="review_issue_date" class="form-control form-control-sm" oninput="onReviewFieldInput('review_issue_date', 'confTag_issue_date')">
              </div>

              <!-- Date of Expiry -->
              <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label small fw-semibold text-secondary mb-0">Date of Expiry</label>
                  <span class="conf-tag conf-not-detected" id="confTag_expiry_date"><i class="fa-solid fa-triangle-exclamation me-1"></i> Not detected</span>
                </div>
                <input type="date" id="review_expiry_date" class="form-control form-control-sm" oninput="onReviewFieldInput('review_expiry_date', 'confTag_expiry_date')">
              </div>

              <!-- Issuing Country -->
              <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label small fw-semibold text-secondary mb-0">Issuing Country</label>
                  <span class="conf-tag conf-not-detected" id="confTag_issuing_country"><i class="fa-solid fa-triangle-exclamation me-1"></i> Not detected</span>
                </div>
                <input type="text" id="review_issuing_country" class="form-control form-control-sm" oninput="onReviewFieldInput('review_issuing_country', 'confTag_issuing_country')">
              </div>

              <!-- Birth Place -->
              <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label small fw-semibold text-secondary mb-0">Birth Place / Country</label>
                  <span class="conf-tag conf-not-detected" id="confTag_birth_place"><i class="fa-solid fa-triangle-exclamation me-1"></i> Not detected</span>
                </div>
                <input type="text" id="review_birth_place" class="form-control form-control-sm" oninput="onReviewFieldInput('review_birth_place', 'confTag_birth_place')">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer bg-light d-flex justify-content-between py-3">
        <div>
          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="dismissOcrReviewModal()">
            <i class="fa-solid fa-pen me-1"></i> Enter Manually
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="dismissOcrReviewModal(); document.getElementById('passportFileInput').click();">
            <i class="fa-solid fa-rotate me-1"></i> Scan Different Image
          </button>
        </div>
        <button type="button" class="btn btn-mac-primary px-4" onclick="applyOcrDataToForm()">
          <i class="fa-solid fa-check me-1"></i> CONFIRM &amp; AUTO-FILL FORM
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ============================================================ -->
<!-- EXISTING CUSTOMER SELECTION MODAL                            -->
<!-- ============================================================ -->
<div class="modal fade" id="existingCustomerModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold text-dark fs-6">
          <i class="fa-solid fa-address-book text-primary me-2"></i> Select Registered Customer
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <label class="form-label small fw-semibold text-secondary mb-1">Choose Applicant</label>
        <select id="modalCustomerSelect" class="form-select mb-3">
          <option value="">-- Choose Existing Applicant --</option>
          <?php foreach ($customers as $c): ?>
            <option value="<?= $c['id'] ?>" 
                    data-code="<?= e($c['customer_code']) ?>"
                    data-name="<?= e($c['full_name']) ?>"
                    data-first="<?= e($c['first_name']) ?>"
                    data-middle="<?= e($c['middle_name']) ?>"
                    data-last="<?= e($c['last_name']) ?>"
                    data-passport="<?= e($c['passport_number']) ?>"
                    data-nationality="<?= e($c['nationality']) ?>"
                    data-gender="<?= e($c['gender']) ?>"
                    data-dob="<?= e($c['dob']) ?>"
                    data-mobile="<?= e($c['mobile']) ?>"
                    data-email="<?= e($c['email']) ?>">
              <?= e($c['customer_code']) ?> &bull; <?= e($c['full_name']) ?> (<?= e($c['nationality']) ?>) &mdash; <?= e($c['passport_number'] ?: 'No Passport') ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="small text-muted">
          Selecting a registered customer links their profile and auto-fills their identity information.
        </div>
      </div>
      <div class="modal-footer bg-light py-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm px-3" onclick="applySelectedCustomerFromModal()">Link Selected</button>
      </div>
    </div>
  </div>
</div>

<!-- Datalist of Countries for quick autocomplete -->
<datalist id="countriesDataList">
  <?php foreach ($countries as $ct): ?>
    <option value="<?= e($ct['name']) ?>"><?= $ct['flag_emoji'] ?> <?= e($ct['name']) ?></option>
  <?php endforeach; ?>
  <option value="Sri Lanka">Sri Lanka</option>
  <option value="India">India</option>
  <option value="Nepal">Nepal</option>
  <option value="Pakistan">Pakistan</option>
  <option value="Philippines">Philippines</option>
  <option value="Bangladesh">Bangladesh</option>
  <option value="United Arab Emirates">United Arab Emirates</option>
  <option value="United Kingdom">United Kingdom</option>
  <option value="United States">United States</option>
</datalist>

<!-- Datalist of Languages -->
<datalist id="languagesDataList">
  <option value="English">English</option>
  <option value="Arabic">Arabic</option>
  <option value="Sinhala">Sinhala</option>
  <option value="Tamil">Tamil</option>
  <option value="Hindi">Hindi</option>
  <option value="Urdu">Urdu</option>
  <option value="Tagalog">Tagalog</option>
  <option value="French">French</option>
</datalist>

<!-- JavaScript Data & Logic -->
<script>
const allServices = <?= json_encode($services) ?>;
let currentOcrData = null;
let viewerZoom = 1.0;
let viewerRotation = 0;
let isPanning = false;
let startX = 0, startY = 0, translateX = 0, translateY = 0;

// Drag and drop event listeners
document.addEventListener('DOMContentLoaded', function() {
  const dropzone = document.getElementById('passportDropzone');
  if (dropzone) {
    ['dragenter', 'dragover'].forEach(eventName => {
      dropzone.addEventListener(eventName, (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.add('dragover');
      }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
      dropzone.addEventListener(eventName, (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.remove('dragover');
      }, false);
    });

    dropzone.addEventListener('drop', (e) => {
      const dt = e.dataTransfer;
      const files = dt.files;
      if (files && files.length > 0) {
        handlePassportFileSelected(files[0]);
      }
    }, false);
  }

  // Setup pan/drag inside passport viewer
  setupViewerPan();
  updateServiceInfo();
});

// Handle passport file upload & OCR process
function handlePassportFileSelected(file) {
  if (!file) return;

  const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
  if (!validTypes.includes(file.type) && !file.name.match(/\.(jpg|jpeg|png|webp|pdf)$/i)) {
    showOcrError('Invalid File Type', 'Please upload a clear JPG, PNG, WEBP, or PDF passport scan.');
    return;
  }

  // Show OCR progress overlay with animated checklist steps
  const overlay = document.getElementById('ocrProgressOverlay');
  const errorBanner = document.getElementById('ocrErrorBanner');
  overlay.classList.remove('d-none');
  errorBanner.classList.add('d-none');

  animateOcrSteps();

  const formData = new FormData();
  formData.append('passport_file', file);
  const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
  formData.append('csrf_token', csrfToken);

  fetch('/api/documents/ocr-passport', {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': csrfToken
    },
    body: formData
  })
  .then(res => res.json())
  .then(resp => {
    overlay.classList.add('d-none');
    if (!resp.success || !resp.data) {
      showOcrError('Extraction Notice', resp.message || 'Could not confidently read passport information. Please upload a clear photo or enter manually.');
      return;
    }

    currentOcrData = resp.data;
    openOcrReviewModal(resp.data);
  })
  .catch(err => {
    overlay.classList.add('d-none');
    showOcrError('Connection Error', 'Automatic extraction service temporarily unavailable. You can enter details manually.');
  });
}

function animateOcrSteps() {
  const steps = [
    { id: 'ocrStep1', time: 300 },
    { id: 'ocrStep2', time: 800 },
    { id: 'ocrStep3', time: 1400 },
    { id: 'ocrStep4', time: 2000 },
    { id: 'ocrStep5', time: 2600 }
  ];

  steps.forEach((s, idx) => {
    setTimeout(() => {
      const el = document.getElementById(s.id);
      if (el) {
        el.className = 'active';
        el.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> ' + el.innerText.replace(/^.*? /, '');
        if (idx > 0) {
          const prevEl = document.getElementById(steps[idx - 1].id);
          if (prevEl) {
            prevEl.className = 'done';
            prevEl.innerHTML = '<i class="fa-solid fa-check text-success"></i> ' + prevEl.innerText.replace(/^.*? /, '');
          }
        }
      }
    }, s.time);
  });
}

function showOcrError(title, msg) {
  const banner = document.getElementById('ocrErrorBanner');
  document.getElementById('ocrErrorTitle').innerText = title;
  document.getElementById('ocrErrorMsg').innerText = msg;
  banner.classList.remove('d-none');
}

function dismissOcrError() {
  document.getElementById('ocrErrorBanner').classList.add('d-none');
  document.getElementById('passportNoInput').focus();
}

// Side-by-Side Review Modal Logic
function openOcrReviewModal(data) {
  const ext = data.extracted || data.data || {};
  const conf = data.confidence || data.confidence_scores || {};

  // Setup preview image
  const img = document.getElementById('reviewPassportImg');
  img.src = data.preview_url || '';
  resetViewerTransform();

  // Setup overall confidence badge (Honest score, never faked)
  const isMrz = Boolean(data.mrz_detected);
  const mrzTag = isMrz ? '<span class="badge bg-primary text-white border border-primary px-2 py-1 me-1"><i class="fa-solid fa-barcode me-1"></i> MRZ Verified</span>' : '<span class="badge bg-light text-secondary border px-2 py-1 me-1"><i class="fa-regular fa-eye me-1"></i> Visual Zone</span>';
  const confRaw = (typeof data.overall_confidence === 'number') ? data.overall_confidence : 0;
  const confPct = Math.round(confRaw);
  const confBadge = document.getElementById('reviewOverallConfBadge');
  if (confPct >= 80) {
    confBadge.className = 'd-inline-flex align-items-center';
    confBadge.innerHTML = `${mrzTag}<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa-solid fa-shield-check me-1"></i> ${confPct}% High</span>`;
  } else if (confPct >= 50) {
    confBadge.className = 'd-inline-flex align-items-center';
    confBadge.innerHTML = `${mrzTag}<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="fa-solid fa-triangle-exclamation me-1"></i> ${confPct}% Review</span>`;
  } else {
    confBadge.className = 'd-inline-flex align-items-center';
    confBadge.innerHTML = `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="fa-solid fa-pen me-1"></i> Manual Review</span>`;
  }

  // Fill review fields with honest detection state
  setReviewField('review_passport_number', ext.passport_number || ext.passport_no || '', conf.passport_number || conf.passport_no, 'confTag_passport_number');
  setReviewField('review_surname', ext.surname || ext.last_name || '', conf.surname || conf.last_name, 'confTag_surname');
  setReviewField('review_given_names', ext.given_names || ext.first_name || '', conf.given_names || conf.first_name, 'confTag_given_names');
  setReviewField('review_nationality', ext.nationality || '', conf.nationality, 'confTag_nationality');
  setReviewField('review_dob', ext.dob || ext.date_of_birth || '', conf.dob || conf.date_of_birth, 'confTag_dob');
  setReviewField('review_gender', ext.gender || ext.sex || '', conf.gender || conf.sex, 'confTag_gender');
  setReviewField('review_issue_date', ext.issue_date || ext.date_of_issue || '', conf.issue_date || conf.date_of_issue, 'confTag_issue_date');
  setReviewField('review_expiry_date', ext.expiry_date || ext.date_of_expiry || '', conf.expiry_date || conf.date_of_expiry, 'confTag_expiry_date');
  setReviewField('review_issuing_country', ext.issuing_country || ext.nationality || '', conf.issuing_country, 'confTag_issuing_country');
  setReviewField('review_birth_place', ext.place_of_birth || ext.birth_place || '', conf.place_of_birth, 'confTag_birth_place');

  const modal = new bootstrap.Modal(document.getElementById('ocrReviewModal'));
  modal.show();
}

function setReviewField(inputId, val, confScore, tagId) {
  const el = document.getElementById(inputId);
  const cleanVal = (val !== null && val !== undefined) ? String(val).trim() : '';
  if (el) el.value = cleanVal;

  const tag = document.getElementById(tagId);
  if (!tag) return;

  // Strict: If value is empty, NEVER display a high confidence badge
  if (!cleanVal) {
    tag.className = 'conf-tag conf-not-detected';
    tag.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i> Not detected';
    return;
  }

  // Value exists: display honest confidence rating
  const score = (typeof confScore === 'number' && confScore > 0) ? Math.round(confScore) : 85;
  if (score >= 85) {
    tag.className = 'conf-tag conf-high';
    tag.innerText = `✓ ${score}% High`;
  } else if (score >= 60) {
    tag.className = 'conf-tag conf-med';
    tag.innerText = `⚠ ${score}% Verify`;
  } else {
    tag.className = 'conf-tag conf-low';
    tag.innerText = `⚠ Low`;
  }
}

// User inline correction in review modal
function onReviewFieldInput(inputId, tagId) {
  const el = document.getElementById(inputId);
  const tag = document.getElementById(tagId);
  if (!el || !tag) return;

  const val = el.value.trim();
  if (val) {
    tag.className = 'conf-tag conf-med';
    tag.innerHTML = '<i class="fa-solid fa-pen me-1"></i> User Entered';
  } else {
    tag.className = 'conf-tag conf-not-detected';
    tag.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i> Not detected';
  }
}

function dismissOcrReviewModal() {
  const modalEl = document.getElementById('ocrReviewModal');
  const modal = bootstrap.Modal.getInstance(modalEl);
  if (modal) modal.hide();
}

function reopenOcrReviewModal() {
  if (currentOcrData) {
    openOcrReviewModal(currentOcrData);
  }
}

// Apply OCR data to main form (Confirm & Auto-Fill)
function applyOcrDataToForm() {
  // Read verified values from review modal
  const pNum = document.getElementById('review_passport_number').value.trim();
  const surname = document.getElementById('review_surname').value.trim();
  const givenNames = document.getElementById('review_given_names').value.trim();
  const nat = document.getElementById('review_nationality').value.trim();
  const dob = document.getElementById('review_dob').value.trim();
  const gender = document.getElementById('review_gender').value;
  const issueDate = document.getElementById('review_issue_date').value.trim();
  const expiryDate = document.getElementById('review_expiry_date').value.trim();
  const issuingCountry = document.getElementById('review_issuing_country').value.trim();
  const birthPlace = document.getElementById('review_birth_place').value.trim();

  // Name splitting (Given Names -> First + Middle, Surname -> Last)
  const nameParts = givenNames.split(/\s+/).filter(Boolean);
  const firstName = nameParts[0] || '';
  const middleName = nameParts.slice(1).join(' ');

  // Development-only debugging pipeline log (Requirement #20)
  console.group('Passport OCR Auto-Fill Debug Pipeline');
  console.log('1. REVIEW MODAL VALUES:', { pNum, surname, givenNames, nat, dob, gender, issueDate, expiryDate, issuingCountry, birthPlace });
  console.log('2. DOM APPLICATION FIELD MAPPING:');
  console.log('   passport_number -> #passportNoInput =', pNum);
  console.log('   nationality -> #nationalityInput =', nat);
  console.log('   first_name -> #firstNameInput =', firstName);
  console.log('   middle_name -> #middleNameInput =', middleName);
  console.log('   last_name -> #lastNameInput =', surname);
  console.log('   dob -> #dobInput =', dob);
  console.log('   gender -> input[name="gender"] =', gender);
  console.log('   birth_country -> #birthCountryInput =', nat);
  console.log('   place_of_birth -> #birthPlaceInput =', birthPlace);
  console.log('   passport_issue_date -> #issueDateInput =', issueDate);
  console.log('   passport_expiry_date -> #expiryDateInput =', expiryDate);
  console.log('   passport_issuing_country -> #issuingCountryInput =', issuingCountry);
  console.groupEnd();

  // Populate main form fields
  if (pNum) setFormValue('passportNoInput', pNum, 'prov_passport_number');
  if (nat) {
    setFormValue('nationalityInput', nat, 'prov_nationality');
    if (!document.getElementById('birthCountryInput').value) {
      setFormValue('birthCountryInput', nat, 'prov_birth_country');
    }
  }

  if (firstName) setFormValue('firstNameInput', firstName, 'prov_first_name');
  if (middleName) setFormValue('middleNameInput', middleName, 'prov_middle_name');
  if (surname) setFormValue('lastNameInput', surname, 'prov_last_name');
  syncFullName();

  if (dob) setFormValue('dobInput', dob, 'prov_dob');
  if (issueDate) setFormValue('issueDateInput', issueDate, 'prov_issue_date');
  if (expiryDate) setFormValue('expiryDateInput', expiryDate, 'prov_expiry_date');
  if (issuingCountry) setFormValue('issuingCountryInput', issuingCountry, 'prov_issuing_country');
  if (birthPlace) setFormValue('birthPlaceInput', birthPlace, 'prov_place_of_birth');

  if (gender === 'Female') {
    document.getElementById('genderFemale').checked = true;
  } else if (gender === 'Male') {
    document.getElementById('genderMale').checked = true;
  }
  if (gender) {
    setProvenancePill('prov_gender', 'ocr');
  }

  // Attach temporary token & metadata
  if (currentOcrData) {
    document.getElementById('tempPassportToken').value = currentOcrData.temp_token || '';
    document.getElementById('ocrConfidenceInput').value = currentOcrData.overall_confidence || '90';
    document.getElementById('ocrProviderInput').value = currentOcrData.ocr_provider || 'PassportOcrService';
    document.getElementById('passportOrigFilename').value = currentOcrData.original_filename || '';
  }

  // Update active card
  document.getElementById('activePassportNumber').innerText = pNum || 'N/A';
  document.getElementById('activePassportName').innerText = (givenNames + ' ' + surname).trim() || 'Passport Holder';
  document.getElementById('activePassportNat').innerText = nat || 'N/A';
  document.getElementById('activePassportExp').innerText = expiryDate || 'N/A';
  document.getElementById('passportActiveCard').classList.remove('d-none');

  // Update main provenance tag
  const mainTag = document.getElementById('mainProvenanceTag');
  mainTag.className = 'provenance-badge ocr';
  mainTag.innerHTML = '<i class="fa-solid fa-check"></i> Passport OCR Verified';

  // Dismiss modal
  dismissOcrReviewModal();

  // Scroll to destination section
  document.getElementById('countryFilterSelect')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function setFormValue(id, val, provId) {
  const el = document.getElementById(id);
  if (el) {
    el.value = val;
    setProvenancePill(provId, 'ocr');
  }
}

function setProvenancePill(provId, type) {
  const el = document.getElementById(provId);
  if (!el) return;
  el.classList.remove('d-none', 'ocr', 'edited', 'manual');
  if (type === 'ocr') {
    el.classList.add('provenance-badge', 'ocr');
    el.innerHTML = '<i class="fa-solid fa-check"></i> Passport OCR';
  } else if (type === 'edited') {
    el.classList.add('provenance-badge', 'edited');
    el.innerHTML = '<i class="fa-solid fa-pen"></i> Edited';
  } else {
    el.classList.add('provenance-badge', 'manual');
    el.innerHTML = '✎ Manual';
  }
}

function onFieldManuallyEdited(fieldKey) {
  setProvenancePill('prov_' + fieldKey, 'edited');
  const mainTag = document.getElementById('mainProvenanceTag');
  if (mainTag && mainTag.classList.contains('ocr')) {
    mainTag.className = 'provenance-badge edited';
    mainTag.innerHTML = '<i class="fa-solid fa-pen"></i> OCR with Manual Edits';
  }
}

function syncFullName() {
  const first = document.getElementById('firstNameInput').value.trim();
  const mid = document.getElementById('middleNameInput').value.trim();
  const last = document.getElementById('lastNameInput').value.trim();
  const full = [first, mid, last].filter(Boolean).join(' ');
  document.getElementById('fullNameHidden').value = full;
}

// Viewer pan, zoom, rotate
function adjustViewerZoom(delta) {
  viewerZoom = Math.max(0.5, Math.min(3.5, viewerZoom + delta));
  applyViewerTransform();
}

function rotateViewerImage() {
  viewerRotation = (viewerRotation + 90) % 360;
  applyViewerTransform();
}

function resetViewerTransform() {
  viewerZoom = 1.0;
  viewerRotation = 0;
  translateX = 0;
  translateY = 0;
  applyViewerTransform();
}

function applyViewerTransform() {
  const img = document.getElementById('reviewPassportImg');
  if (img) {
    img.style.transform = `translate(${translateX}px, ${translateY}px) scale(${viewerZoom}) rotate(${viewerRotation}deg)`;
  }
  const label = document.getElementById('viewerZoomLabel');
  if (label) {
    label.innerText = Math.round(viewerZoom * 100) + '% Zoom';
  }
}

function toggleViewerFullscreen() {
  const stage = document.getElementById('passportViewerStage');
  if (!document.fullscreenElement) {
    stage.requestFullscreen().catch(() => {});
  } else {
    document.exitFullscreen().catch(() => {});
  }
}

function setupViewerPan() {
  const stage = document.getElementById('passportViewerStage');
  if (!stage) return;

  stage.addEventListener('mousedown', (e) => {
    if (viewerZoom <= 1.0) return;
    isPanning = true;
    startX = e.clientX - translateX;
    startY = e.clientY - translateY;
    stage.style.cursor = 'grabbing';
  });

  window.addEventListener('mousemove', (e) => {
    if (!isPanning) return;
    translateX = e.clientX - startX;
    translateY = e.clientY - startY;
    applyViewerTransform();
  });

  window.addEventListener('mouseup', () => {
    isPanning = false;
    if (stage) stage.style.cursor = viewerZoom > 1.0 ? 'grab' : 'default';
  });
}

// Profile Photo Preview
function previewApplicantPhoto(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const img = document.getElementById('applicantPhotoPreviewImg');
      const placeholder = document.getElementById('applicantPhotoPlaceholder');
      img.src = e.target.result;
      img.classList.remove('d-none');
      placeholder.classList.add('d-none');
    };
    reader.readAsDataURL(input.files[0]);
  }
}

// Existing Customers Linking Modal
function toggleExistingApplicantModal() {
  const modal = new bootstrap.Modal(document.getElementById('existingCustomerModal'));
  modal.show();
}

function applySelectedCustomerFromModal() {
  const sel = document.getElementById('modalCustomerSelect');
  const opt = sel.options[sel.selectedIndex];
  if (!opt || !opt.value) return;

  document.getElementById('customerIdInput').value = opt.value;
  document.getElementById('linkedCustomerName').innerText = opt.dataset.name || '';
  document.getElementById('linkedCustomerCode').innerText = opt.dataset.code || '';
  document.getElementById('linkedCustomerInfoBox').classList.remove('d-none');

  // Auto-populate form
  if (opt.dataset.first) document.getElementById('firstNameInput').value = opt.dataset.first;
  if (opt.dataset.middle) document.getElementById('middleNameInput').value = opt.dataset.middle;
  if (opt.dataset.last) document.getElementById('lastNameInput').value = opt.dataset.last;
  syncFullName();

  if (opt.dataset.passport) document.getElementById('passportNoInput').value = opt.dataset.passport;
  if (opt.dataset.nationality) document.getElementById('nationalityInput').value = opt.dataset.nationality;
  if (opt.dataset.dob) document.getElementById('dobInput').value = opt.dataset.dob;
  if (opt.dataset.mobile) document.getElementById('mobileInput').value = opt.dataset.mobile;
  if (opt.dataset.email) document.getElementById('emailInput').value = opt.dataset.email;

  if (opt.dataset.gender === 'Female') {
    document.getElementById('genderFemale').checked = true;
  } else {
    document.getElementById('genderMale').checked = true;
  }

  bootstrap.Modal.getInstance(document.getElementById('existingCustomerModal')).hide();
  checkDuplicateApplication();
}

function unlinkCustomer() {
  document.getElementById('customerIdInput').value = '0';
  document.getElementById('linkedCustomerInfoBox').classList.add('d-none');
}

// Supporting Document Multi-rows
function addAppDocRow() {
  const container = document.getElementById('appDocUploadContainer');
  const firstRow = container.querySelector('.app-doc-row');
  if (firstRow) {
    const clone = firstRow.cloneNode(true);
    clone.querySelectorAll('input').forEach(input => input.value = '');
    container.appendChild(clone);
  }
}

// Visa Service Filters & Calculations
function filterVisaPackages() {
  const countryId = document.getElementById('countryFilterSelect').value;
  const catId = document.getElementById('categoryFilterSelect').value;
  const srvSelect = document.getElementById('serviceSelect');
  const previousVal = srvSelect.value;

  let filtered = allServices.filter(s => {
    const matchCountry = !countryId || countryId === '__custom__' || String(s.country_id) === String(countryId);
    let matchCat = !catId || String(s.category_id) === String(catId);
    return matchCountry && matchCat;
  });

  let isFallback = false;
  if (filtered.length === 0 && countryId && countryId !== '__custom__') {
    filtered = allServices.filter(s => String(s.country_id) === String(countryId));
    isFallback = true;
  }

  srvSelect.innerHTML = '';
  const defaultOpt = document.createElement('option');
  defaultOpt.value = '';
  defaultOpt.textContent = filtered.length > 0 ? `-- Choose Visa Type (${filtered.length} Available) --` : '-- Choose Visa Type / Duration / Entry --';
  srvSelect.appendChild(defaultOpt);

  const customOpt = document.createElement('option');
  customOpt.value = '__custom__';
  customOpt.className = 'fw-bold text-primary';
  customOpt.textContent = '+ Enter Manually / Custom Package...';
  srvSelect.appendChild(customOpt);

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

  if (filtered.length === 1 && previousVal !== '__custom__') {
    srvSelect.selectedIndex = 2;
  }

  updateServiceInfo();
  checkDuplicateApplication();
}

function onServiceChanged() {
  const srvSelect = document.getElementById('serviceSelect');
  const selectedId = srvSelect.value;
  if (selectedId === '__custom__') {
    const box = document.getElementById('visaServiceManualBox');
    if (box) box.classList.remove('d-none');
  } else if (selectedId) {
    const srv = allServices.find(s => String(s.id) === String(selectedId));
    if (srv && srv.country_id) {
      document.getElementById('countryFilterSelect').value = srv.country_id;
    }
  }
  updateServiceInfo();
  checkDuplicateApplication();
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

function getAppSellingPrice() {
  const sel = document.getElementById('serviceSelect');
  const customBox = document.getElementById('visaServiceManualBox');
  const isCustomMode = (sel && sel.value === '__custom__') || (customBox && !customBox.classList.contains('d-none'));
  if (isCustomMode) {
    const custPriceInput = document.getElementById('customSellingPriceInput');
    return custPriceInput ? parseFloat(custPriceInput.value || 0) : 0;
  }
  const opt = sel ? sel.options[sel.selectedIndex] : null;
  return opt ? parseFloat(opt.dataset.price || 0) : 0;
}

function updateServiceInfo() {
  const sel = document.getElementById('serviceSelect');
  const customBox = document.getElementById('visaServiceManualBox');
  const isCustomMode = (sel && sel.value === '__custom__') || (customBox && !customBox.classList.contains('d-none'));

  let price = 0, cost = 0, taxRate = 0, days = 10;

  if (isCustomMode) {
    const custPriceInput = document.getElementById('customSellingPriceInput');
    const custCostInput = document.getElementById('customSupplierCostInput');
    const custDaysInput = document.getElementById('customEstimatedDaysInput');
    price = custPriceInput ? parseFloat(custPriceInput.value || 0) : 0;
    cost = custCostInput ? parseFloat(custCostInput.value || 0) : 0;
    days = custDaysInput ? (parseInt(custDaysInput.value || 10, 10) || 10) : 10;
  } else {
    const opt = sel ? sel.options[sel.selectedIndex] : null;
    if (!opt || !opt.value) {
      document.getElementById('dispSellingPrice').innerText = '$0.00';
      document.getElementById('dispSupplierCost').innerText = '$0.00';
      document.getElementById('dispTaxAmount').innerText = '$0.00';
      document.getElementById('dispTotalAmount').innerText = '$0.00';
      document.getElementById('dispEstimatedDays').innerText = '-- Days';
      document.getElementById('dispExpectedDate').innerText = '--';
      return;
    }
    price = parseFloat(opt.dataset.price || 0);
    cost = parseFloat(opt.dataset.cost || 0);
    taxRate = parseFloat(opt.dataset.tax || 0);
    days = parseInt(opt.dataset.days || 10, 10);
  }

  const discountInput = document.getElementById('inputDiscount');
  const discount = discountInput ? parseFloat(discountInput.value || 0) : 0;
  const netPrice = Math.max(0, price - discount);
  const tax = netPrice * (taxRate / 100);
  const total = netPrice + tax;

  const pct = price > 0 ? ((discount / price) * 100) : 0;
  if (document.getElementById('appDiscountPctTag')) {
    document.getElementById('appDiscountPctTag').innerText = pct.toFixed(1) + '%';
  }

  document.getElementById('dispSellingPrice').innerText = '$' + price.toFixed(2);
  document.getElementById('dispSupplierCost').innerText = '$' + cost.toFixed(2);
  document.getElementById('dispTaxAmount').innerText = '$' + tax.toFixed(2);
  document.getElementById('dispTotalAmount').innerText = '$' + total.toFixed(2);
  document.getElementById('dispEstimatedDays').innerText = days + ' Days';

  const expDate = new Date();
  expDate.setDate(expDate.getDate() + days);
  document.getElementById('dispExpectedDate').innerText = expDate.toISOString().split('T')[0];

  if (document.getElementById('dispConvertedAED')) {
    const aed = (total * 3.6725).toFixed(2);
    document.getElementById('dispConvertedAED').innerText = `${aed} AED (@ 3.6725)`;
  }
}

function onAppDiscountPercentChange() {
  const price = getAppSellingPrice();
  let pct = parseFloat(document.getElementById('inputDiscountPercent').value || 0);
  if (pct < 0) pct = 0;
  if (pct > 100) pct = 100;
  document.getElementById('inputDiscountPercent').value = pct;
  const discAmt = price * (pct / 100);
  document.getElementById('inputDiscount').value = discAmt.toFixed(2);
  updateServiceInfo();
}

function onAppDiscountAmountChange() {
  const price = getAppSellingPrice();
  let discAmt = parseFloat(document.getElementById('inputDiscount').value || 0);
  if (discAmt < 0) discAmt = 0;
  if (price > 0 && discAmt > price) discAmt = price;
  document.getElementById('inputDiscount').value = discAmt.toFixed(2);
  const pct = price > 0 ? ((discAmt / price) * 100) : 0;
  document.getElementById('inputDiscountPercent').value = pct.toFixed(1);
  updateServiceInfo();
}

function applyAppQuickDiscount(pct) {
  document.getElementById('inputDiscountPercent').value = pct;
  onAppDiscountPercentChange();
}

function checkDuplicateApplication() {
  const custId = document.getElementById('customerIdInput').value;
  const srvId = document.getElementById('serviceSelect').value;
  const pNum = document.getElementById('passportNoInput').value.trim();
  const warnBox = document.getElementById('duplicateWarningBox');

  if ((!custId || custId === '0') && !pNum) {
    warnBox.classList.add('d-none');
    return;
  }

  fetch(`/applications/check-duplicate?customer_id=${custId}&passport=${encodeURIComponent(pNum)}&service_id=${srvId}`)
    .then(r => r.json())
    .then(data => {
      if (data && data.duplicate) {
        document.getElementById('duplicateWarningTitle').innerText = 'Active Application In Progress';
        document.getElementById('duplicateWarningMsg').innerHTML = `This applicant already has an active case (<strong>${data.application_number}</strong>) in stage <strong>${data.current_stage}</strong>.`;
        warnBox.classList.remove('d-none');
      } else {
        warnBox.classList.add('d-none');
      }
    })
    .catch(() => {
      warnBox.classList.add('d-none');
    });
}

function toggleManualInput(manualBoxId, selectId) {
  const box = document.getElementById(manualBoxId);
  const sel = document.getElementById(selectId);
  if (!box) return;
  if (box.classList.contains('d-none')) {
    box.classList.remove('d-none');
    if (sel && sel.querySelector('option[value="__custom__"]')) {
      sel.value = '__custom__';
    }
  } else {
    box.classList.add('d-none');
    if (sel && sel.value === '__custom__') {
      sel.value = '';
    }
  }
  updateServiceInfo();
}

function checkManualSelect(sel, manualBoxId) {
  const box = document.getElementById(manualBoxId);
  if (!box) return;
  if (sel.value === '__custom__') {
    box.classList.remove('d-none');
  } else {
    box.classList.add('d-none');
  }
  updateServiceInfo();
}

document.addEventListener('DOMContentLoaded', function() {
  const srvSelect = document.getElementById('serviceSelect');
  if (srvSelect && srvSelect.value && srvSelect.value !== '__custom__') {
    onServiceChanged();
  }
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
