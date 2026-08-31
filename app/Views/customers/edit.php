<?php
$pageTitle = 'Edit Applicant Profile — ' . e($customer['full_name']);
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

  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <a href="/customers/show?id=<?= $customer['id'] ?>" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i></a>
        <h3 class="fw-bold brand-font mb-0">Edit Applicant / Customer Profile</h3>
      </div>
      <p class="text-muted small mb-0">Updating records for <strong><?= e($customer['full_name']) ?></strong> (<?= e($customer['customer_code']) ?>)</p>
    </div>
    <div class="d-flex gap-2">
      <a href="/customers/show?id=<?= $customer['id'] ?>" class="btn btn-outline-secondary btn-sm px-3">Cancel</a>
      <button type="submit" form="editCustomerForm" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
        <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
      </button>
    </div>
  </div>

  <form id="editCustomerForm" action="/customers/update" method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $customer['id'] ?>">

    <div class="row g-4">
      <!-- Left Column: Personal & Passport Info -->
      <div class="col-lg-8">
        <!-- 1. Personal Information -->
        <div class="card card-enterprise mb-4 bg-white shadow-sm">
          <div class="card-header bg-light py-3">
            <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-user me-2"></i> Personal &amp; Identity Details</h6>
          </div>
          <div class="card-body p-4">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">First / Given Name <span class="text-danger">*</span></label>
                <input type="text" name="first_name" class="form-control" value="<?= e($customer['first_name'] ?? '') ?>" required>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Middle Name</label>
                <input type="text" name="middle_name" class="form-control" value="<?= e($customer['middle_name'] ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Last / Family Name <span class="text-danger">*</span></label>
                <input type="text" name="last_name" class="form-control" value="<?= e($customer['last_name'] ?? '') ?>" required>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Gender</label>
                <select name="gender" class="form-select">
                  <option value="Male" <?= ($customer['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                  <option value="Female" <?= ($customer['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                  <option value="Other" <?= ($customer['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Date of Birth</label>
                <input type="date" name="dob" class="form-control" value="<?= e($customer['dob'] ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Nationality <span class="text-danger">*</span></label>
                <select name="nationality" class="form-select" required>
                  <option value="">-- Choose Nationality --</option>
                  <?php foreach ($countries as $c): ?>
                    <option value="<?= e($c['name']) ?>" <?= ($customer['nationality'] ?? '') === $c['name'] ? 'selected' : '' ?>>
                      <?= $c['flag_emoji'] ?> <?= e($c['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Place of Birth</label>
                <input type="text" name="place_of_birth" class="form-control" value="<?= e($customer['place_of_birth'] ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Marital Status</label>
                <select name="marital_status" class="form-select">
                  <option value="Single" <?= ($customer['marital_status'] ?? '') === 'Single' ? 'selected' : '' ?>>Single</option>
                  <option value="Married" <?= ($customer['marital_status'] ?? '') === 'Married' ? 'selected' : '' ?>>Married</option>
                  <option value="Divorced" <?= ($customer['marital_status'] ?? '') === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                  <option value="Widowed" <?= ($customer['marital_status'] ?? '') === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Occupation / Profession</label>
                <input type="text" name="occupation" class="form-control" value="<?= e($customer['occupation'] ?? '') ?>">
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Religion</label>
                <input type="text" name="religion" class="form-control" value="<?= e($customer['religion'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Current Residence Country</label>
                <input type="text" name="current_country" class="form-control" value="<?= e($customer['current_country'] ?? '') ?>">
              </div>
            </div>
          </div>
        </div>

        <!-- 2. Primary Passport Details -->
        <div class="card card-enterprise mb-4 bg-white shadow-sm">
          <div class="card-header bg-light py-3">
            <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-passport me-2"></i> Primary Passport Details</h6>
          </div>
          <div class="card-body p-4">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Passport Number</label>
                <input type="text" name="passport_number" class="form-control font-monospace fw-bold" value="<?= e($primaryPassport['passport_number'] ?? '') ?>" placeholder="e.g. Z1234567">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Country of Issue</label>
                <select name="passport_country" class="form-select">
                  <option value="">-- Same as Nationality --</option>
                  <?php foreach ($countries as $c): ?>
                    <option value="<?= e($c['name']) ?>" <?= ($primaryPassport['country_of_issue'] ?? '') === $c['name'] ? 'selected' : '' ?>>
                      <?= $c['flag_emoji'] ?> <?= e($c['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Issue Date</label>
                <input type="date" name="passport_issue_date" class="form-control" value="<?= e($primaryPassport['issue_date'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Expiry Date</label>
                <input type="date" name="passport_expiry" class="form-control" value="<?= e($primaryPassport['expiry_date'] ?? '') ?>">
              </div>
            </div>
          </div>
        </div>

        <!-- 3. Family Details -->
        <div class="card card-enterprise mb-4 bg-white shadow-sm">
          <div class="card-header bg-light py-3">
            <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-people-roof me-2"></i> Family &amp; Parents Information</h6>
          </div>
          <div class="card-body p-4">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Father's Full Name</label>
                <input type="text" name="father_name" class="form-control" value="<?= e($family['father_name'] ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Father's DOB</label>
                <input type="date" name="father_dob" class="form-control" value="<?= e($family['father_dob'] ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Father's Nationality</label>
                <input type="text" name="father_nationality" class="form-control" value="<?= e($family['father_nationality'] ?? '') ?>">
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Mother's Full Name</label>
                <input type="text" name="mother_name" class="form-control" value="<?= e($family['mother_name'] ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Mother's DOB</label>
                <input type="date" name="mother_dob" class="form-control" value="<?= e($family['mother_dob'] ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Mother's Nationality</label>
                <input type="text" name="mother_nationality" class="form-control" value="<?= e($family['mother_nationality'] ?? '') ?>">
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Contact, Address & Internal Notes -->
      <div class="col-lg-4">
        <!-- Contact Information -->
        <div class="card card-enterprise mb-4 bg-white shadow-sm">
          <div class="card-header bg-light py-3">
            <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-address-book me-2"></i> Contact Details</h6>
          </div>
          <div class="card-body p-4">
            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">Mobile Number <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                <input type="text" name="mobile" class="form-control" value="<?= e($customer['mobile'] ?? '') ?>" required>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">WhatsApp Number</label>
              <div class="input-group">
                <span class="input-group-text text-success"><i class="fa-brands fa-whatsapp"></i></span>
                <input type="text" name="whatsapp" class="form-control" value="<?= e($customer['whatsapp'] ?? '') ?>">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">Email Address</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                <input type="email" name="email" class="form-control" value="<?= e($customer['email'] ?? '') ?>">
              </div>
            </div>

            <div class="mb-0">
              <label class="form-label small fw-semibold text-secondary">Residential Address</label>
              <textarea name="address" class="form-control" rows="3"><?= e($customer['address'] ?? '') ?></textarea>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div class="card card-enterprise mb-4 bg-white shadow-sm">
          <div class="card-header bg-light py-3">
            <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-note-sticky me-2"></i> Internal Notes</h6>
          </div>
          <div class="card-body p-4">
            <textarea name="notes" class="form-control" rows="4" placeholder="Special requirements, VIP notes, etc..."><?= e($customer['notes'] ?? '') ?></textarea>
          </div>
        </div>

        <!-- Submit Buttons -->
        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-primary fw-semibold shadow-sm py-2">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
          </button>
          <a href="/customers/show?id=<?= $customer['id'] ?>" class="btn btn-outline-secondary">
            Cancel
          </a>
        </div>
      </div>
    </div>
  </form>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
