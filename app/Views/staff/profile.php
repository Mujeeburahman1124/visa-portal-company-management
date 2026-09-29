<?php
$pageTitle = 'My Profile & Security — MS TRAVEL HUB';
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';

$flash = get_flash();
$user = $staffUser ?? auth_user();
?>

<div class="content-body">
  <div class="container-fluid px-2 px-md-3 py-3">
    
    <!-- Page Header -->
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3 mb-4">
      <div>
        <h4 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
          <i class="fa-solid fa-id-badge text-primary"></i> My Profile &amp; Account Settings
        </h4>
        <p class="text-muted small mb-0">Manage your personal credentials, contact details, and password security.</p>
      </div>
      <a href="/dashboard" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
      </a>
    </div>

    <!-- Flash Alerts -->
    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm rounded-3 mb-4" role="alert">
        <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check text-success' : 'fa-triangle-exclamation text-danger' ?> me-2"></i>
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="row g-3 g-md-4">
      
      <!-- Profile Overview Card -->
      <div class="col-12 col-lg-4">
        <div class="card card-custom border-0 shadow-sm rounded-3 text-center p-3 p-md-4 mb-3">
          <div class="mx-auto mb-3 position-relative" style="width: 84px; height: 84px;">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow" style="width: 84px; height: 84px; font-size: 2rem;">
              <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
            </div>
            <span class="position-absolute bottom-0 end-0 p-2 bg-success border border-white rounded-circle" title="Account Active"></span>
          </div>

          <h5 class="fw-bold text-dark mb-1"><?= e($user['name'] ?? 'Staff Member') ?></h5>
          <div class="badge bg-primary px-3 py-1.5 rounded-pill mb-2"><?= e($user['role_name'] ?? ($user['role'] ?? 'Staff')) ?></div>
          <p class="text-muted small mb-3"><?= e($user['email'] ?? '') ?></p>

          <hr class="my-3 opacity-10">

          <div class="text-start small">
            <div class="d-flex justify-content-between py-1.5 border-bottom border-light">
              <span class="text-muted"><i class="fa-solid fa-building me-1.5 text-secondary"></i> Branch</span>
              <span class="fw-semibold text-dark"><?= e($user['branch_name'] ?? 'Main Headquarters') ?></span>
            </div>
            <div class="d-flex justify-content-between py-1.5 border-bottom border-light">
              <span class="text-muted"><i class="fa-solid fa-briefcase me-1.5 text-secondary"></i> Designation</span>
              <span class="fw-semibold text-dark"><?= e($user['designation'] ?? 'Operations') ?></span>
            </div>
            <div class="d-flex justify-content-between py-1.5 border-bottom border-light">
              <span class="text-muted"><i class="fa-solid fa-phone me-1.5 text-secondary"></i> Phone</span>
              <span class="fw-semibold text-dark"><?= e($user['phone'] ?? 'Not set') ?></span>
            </div>
            <div class="d-flex justify-content-between py-1.5">
              <span class="text-muted"><i class="fa-solid fa-clock me-1.5 text-secondary"></i> Last Sign In</span>
              <span class="fw-semibold text-dark"><?= !empty($user['last_login_at']) ? format_datetime($user['last_login_at']) : 'Current Session' ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Profile Edit & Password Change Form -->
      <div class="col-12 col-lg-8">
        
        <!-- Tab Navigation -->
        <ul class="nav nav-tabs nav-fill mb-3 border-bottom" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active fw-semibold" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal-pane" type="button" role="tab">
              <i class="fa-solid fa-user-pen me-1.5"></i> Edit Profile Details
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="password-tab" data-bs-toggle="tab" data-bs-target="#password-pane" type="button" role="tab">
              <i class="fa-solid fa-key me-1.5"></i> Change Password
            </button>
          </li>
        </ul>

        <div class="tab-content">
          
          <!-- Edit Profile Details Tab -->
          <div class="tab-pane fade show active" id="personal-pane" role="tabpanel">
            <div class="card card-custom border-0 shadow-sm rounded-3 p-3 p-md-4">
              <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-circle-info text-primary me-2"></i> Update Personal Contact Details</h6>
              
              <form action="/profile/update" method="POST" class="needs-validation">
                <?= csrf_field() ?>
                
                <div class="row g-3">
                  <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-dark">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= e($user['name'] ?? '') ?>" required>
                  </div>

                  <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-dark">Work Email</label>
                    <input type="email" class="form-control bg-light" value="<?= e($user['email'] ?? '') ?>" readonly title="Email cannot be changed directly for security reasons">
                    <div class="form-text" style="font-size: 0.72rem;">To modify your login email, contact your Super Admin.</div>
                  </div>

                  <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-dark">Mobile / WhatsApp Number</label>
                    <input type="text" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>" placeholder="+971 50 123 4567">
                  </div>

                  <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-dark">Job Designation</label>
                    <input type="text" name="designation" class="form-control" value="<?= e($user['designation'] ?? '') ?>" placeholder="e.g. Senior Visa Consultant">
                  </div>

                  <div class="col-12">
                    <label class="form-label small fw-semibold text-dark">Department</label>
                    <input type="text" name="department" class="form-control" value="<?= e($user['department'] ?? 'Operations') ?>" placeholder="e.g. Visa Processing & Documentation">
                  </div>

                  <div class="col-12 text-end pt-2">
                    <button type="submit" class="btn btn-primary px-4 rounded-pill shadow-sm">
                      <i class="fa-solid fa-floppy-disk me-1.5"></i> Save Profile Details
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <!-- Change Password Tab -->
          <div class="tab-pane fade" id="password-pane" role="tabpanel">
            <div class="card card-custom border-0 shadow-sm rounded-3 p-3 p-md-4">
              <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-shield-halved text-danger me-2"></i> Update Security Credentials</h6>
              
              <form action="/auth/change-password" method="POST" class="needs-validation">
                <?= csrf_field() ?>
                
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label small fw-semibold text-dark">Current Password <span class="text-danger">*</span></label>
                    <input type="password" name="current_password" class="form-control" required placeholder="Enter your existing account password" autocomplete="current-password">
                  </div>

                  <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-dark">New Password <span class="text-danger">*</span></label>
                    <input type="password" name="new_password" class="form-control" required minlength="8" placeholder="Minimum 8 characters" autocomplete="new-password">
                    <div class="form-text" style="font-size: 0.72rem;">Must be at least 8 characters long.</div>
                  </div>

                  <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-dark">Confirm New Password <span class="text-danger">*</span></label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="8" placeholder="Re-type new password" autocomplete="new-password">
                  </div>

                  <div class="col-12 text-end pt-2">
                    <button type="submit" class="btn btn-danger px-4 rounded-pill shadow-sm">
                      <i class="fa-solid fa-lock me-1.5"></i> Update Password
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
