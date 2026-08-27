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

    <!-- Section 6: Unlimited Supporting Documents -->
    <div class="card card-enterprise mb-4 shadow-sm border">
      <div class="card-header bg-white d-flex align-items-center justify-content-between py-3 border-bottom">
        <span class="fw-bold small text-uppercase text-secondary">
          <i class="fa-solid fa-file-arrow-up text-primary me-2"></i> 6. Supporting Documents (Unlimited Multi-Upload)
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
              <input type="text" name="document_titles[]" class="form-control form-control-sm" placeholder="e.g. Passport Bio Page, Salary Slip">
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

    <!-- Form Submit Actions -->
    <div class="d-flex align-items-center justify-content-end gap-2 mb-5">
      <a href="/customers" class="btn btn-light border px-4">Cancel</a>
      <button type="submit" class="btn btn-primary px-4 fw-semibold shadow">
        <i class="fa-solid fa-user-plus me-1"></i> Register Applicant Profile
      </button>
    </div>
  </form>
</div>

<script>
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
