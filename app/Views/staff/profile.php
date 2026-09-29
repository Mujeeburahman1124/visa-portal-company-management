<?php
$pageTitle = 'My Profile & Security — MS TRAVEL HUB';
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';

$flash = get_flash();
$user = $staffUser ?? auth_user();
$avatarUrl = !empty($user['profile_photo']) ? '/uploads/avatars/' . e($user['profile_photo']) : '';
$initials = strtoupper(substr($user['name'] ?? 'U', 0, 2));
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

          <!-- Avatar with Photo Upload -->
          <form action="/profile/upload-photo" method="POST" enctype="multipart/form-data" id="photoUploadForm" class="mb-3">
            <?= csrf_field() ?>
            <div class="mx-auto mb-2 position-relative" style="width: 96px; height: 96px;">
              <?php if ($avatarUrl): ?>
                <img id="profileAvatarImg" src="<?= $avatarUrl ?>?v=<?= time() ?>" alt="Profile Photo"
                     class="rounded-circle border border-3 border-primary shadow"
                     style="width: 96px; height: 96px; object-fit: cover;">
              <?php else: ?>
                <div id="profileAvatarInitials" class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow" 
                     style="width: 96px; height: 96px; font-size: 2rem;">
                  <?= $initials ?>
                </div>
              <?php endif; ?>
              <label for="profilePhotoInput" class="position-absolute bottom-0 end-0 bg-white border border-2 border-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" 
                     style="width: 30px; height: 30px; cursor: pointer;" title="Change Profile Photo">
                <i class="fa-solid fa-camera text-primary" style="font-size: 0.7rem;"></i>
              </label>
              <input type="file" name="profile_photo" id="profilePhotoInput" accept="image/jpeg,image/png,image/gif,image/webp" class="d-none">
            </div>
            <div id="photoUploadActions" class="d-none mb-2">
              <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3">
                <i class="fa-solid fa-upload me-1"></i> Upload Photo
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2 ms-1" id="cancelPhotoBtn">Cancel</button>
            </div>
          </form>

          <h5 class="fw-bold text-dark mb-1"><?= e($user['name'] ?? 'Staff Member') ?></h5>
          <div class="badge bg-primary px-3 rounded-pill mb-2"><?= e($user['role_name'] ?? ($user['role'] ?? 'Staff')) ?></div>
          <p class="text-muted small mb-3"><?= e($user['email'] ?? '') ?></p>

          <hr class="my-3 opacity-10">

          <div class="text-start small">
            <div class="d-flex justify-content-between py-2 border-bottom border-light">
              <span class="text-muted"><i class="fa-solid fa-building me-2 text-secondary"></i> Branch</span>
              <span class="fw-semibold text-dark"><?= e($user['branch_name'] ?? 'Main HQ') ?></span>
            </div>
            <div class="d-flex justify-content-between py-2 border-bottom border-light">
              <span class="text-muted"><i class="fa-solid fa-briefcase me-2 text-secondary"></i> Designation</span>
              <span class="fw-semibold text-dark"><?= e($user['designation'] ?? '—') ?></span>
            </div>
            <div class="d-flex justify-content-between py-2 border-bottom border-light">
              <span class="text-muted"><i class="fa-solid fa-phone me-2 text-secondary"></i> Phone</span>
              <span class="fw-semibold text-dark"><?= e($user['phone'] ?? 'Not set') ?></span>
            </div>
            <div class="d-flex justify-content-between py-2">
              <span class="text-muted"><i class="fa-regular fa-clock me-2 text-secondary"></i> Last Sign In</span>
              <span class="fw-semibold text-dark"><?= !empty($user['last_login_at']) ? format_datetime($user['last_login_at']) : 'Current Session' ?></span>
            </div>
          </div>
        </div>

        <!-- Security Notice -->
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-warning bg-opacity-10">
          <div class="d-flex align-items-start gap-2">
            <i class="fa-solid fa-lock text-warning mt-1"></i>
            <div>
              <div class="fw-semibold small text-dark">Security Notice</div>
              <div class="text-muted" style="font-size:0.72rem;">Role, branch, and company cannot be changed here. Contact your Super Admin for account changes.</div>
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
              <i class="fa-solid fa-user-pen me-1"></i> Edit Profile Details
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="password-tab" data-bs-toggle="tab" data-bs-target="#password-pane" type="button" role="tab">
              <i class="fa-solid fa-key me-1"></i> Change Password
            </button>
          </li>
        </ul>

        <div class="tab-content">
          
          <!-- Edit Profile Details Tab -->
          <div class="tab-pane fade show active" id="personal-pane" role="tabpanel">
            <div class="card card-custom border-0 shadow-sm rounded-3 p-3 p-md-4">
              <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-circle-info text-primary me-2"></i> Update Personal Contact Details</h6>
              
              <form action="/profile/update" method="POST" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                
                <div class="row g-3">
                  <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-dark">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= e($user['name'] ?? '') ?>" required>
                    <div class="invalid-feedback">Full name is required.</div>
                  </div>

                  <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-dark">Work Email</label>
                    <input type="email" class="form-control bg-light" value="<?= e($user['email'] ?? '') ?>" readonly title="Email cannot be changed directly for security reasons">
                    <div class="form-text" style="font-size: 0.72rem;">To modify your login email, contact your Super Admin.</div>
                  </div>

                  <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-dark">Mobile Number</label>
                    <input type="text" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>" placeholder="+971 50 123 4567">
                  </div>

                  <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-dark">WhatsApp Number <small class="text-muted">(if different)</small></label>
                    <input type="text" name="whatsapp_number" class="form-control" value="<?= e($user['whatsapp_number'] ?? $user['phone'] ?? '') ?>" placeholder="+971 50 123 4567">
                  </div>

                  <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-dark">Job Designation</label>
                    <input type="text" name="designation" class="form-control" value="<?= e($user['designation'] ?? '') ?>" placeholder="e.g. Senior Visa Consultant">
                  </div>

                  <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-dark">Department</label>
                    <input type="text" name="department" class="form-control" value="<?= e($user['department'] ?? 'Operations') ?>" placeholder="e.g. Visa Processing">
                  </div>

                  <div class="col-12">
                    <label class="form-label small fw-semibold text-dark">Address / Location <small class="text-muted">(optional)</small></label>
                    <textarea name="address" class="form-control" rows="2" placeholder="e.g. Dubai, UAE"><?= e($user['address'] ?? '') ?></textarea>
                  </div>

                  <div class="col-12 text-end pt-2">
                    <button type="submit" class="btn btn-primary px-4 rounded-pill shadow-sm">
                      <i class="fa-solid fa-floppy-disk me-1"></i> Save Profile Details
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

              <div class="alert alert-info py-2 px-3 small mb-3 rounded-3 border-0">
                <i class="fa-solid fa-circle-info me-2"></i>
                Password must be at least <strong>8 characters</strong>. After updating, your new password will be required next time you sign in.
              </div>

              <form action="/auth/change-password" method="POST" class="needs-validation" novalidate id="changePasswordForm">
                <?= csrf_field() ?>
                
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label small fw-semibold text-dark">Current Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <input type="password" name="current_password" id="currentPasswordInput" class="form-control" required placeholder="Enter your existing password" autocomplete="current-password">
                      <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVis('currentPasswordInput',this)"><i class="fa-solid fa-eye"></i></button>
                    </div>
                  </div>

                  <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-dark">New Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <input type="password" name="new_password" id="newPasswordInput" class="form-control" required minlength="8" placeholder="Min 8 characters" autocomplete="new-password">
                      <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVis('newPasswordInput',this)"><i class="fa-solid fa-eye"></i></button>
                    </div>
                  </div>

                  <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-dark">Confirm New Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <input type="password" name="confirm_password" id="confirmPasswordInput" class="form-control" required minlength="8" placeholder="Re-type new password" autocomplete="new-password">
                      <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVis('confirmPasswordInput',this)"><i class="fa-solid fa-eye"></i></button>
                    </div>
                  </div>

                  <!-- Password Strength Bars -->
                  <div class="col-12">
                    <div class="d-flex gap-1 mb-1" id="strengthBars">
                      <div class="flex-grow-1 rounded" style="height:4px;background:#e2e8f0;" id="sbar1"></div>
                      <div class="flex-grow-1 rounded" style="height:4px;background:#e2e8f0;" id="sbar2"></div>
                      <div class="flex-grow-1 rounded" style="height:4px;background:#e2e8f0;" id="sbar3"></div>
                      <div class="flex-grow-1 rounded" style="height:4px;background:#e2e8f0;" id="sbar4"></div>
                    </div>
                    <div class="text-muted" id="strengthLabel" style="font-size:0.72rem;"></div>
                  </div>

                  <div class="col-12 text-end pt-2">
                    <button type="submit" class="btn btn-danger px-4 rounded-pill shadow-sm">
                      <i class="fa-solid fa-lock me-1"></i> Update Password
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

<script>
function togglePasswordVis(inputId, btn) {
  var el = document.getElementById(inputId);
  if (!el) return;
  el.type = el.type === 'password' ? 'text' : 'password';
  btn.innerHTML = el.type === 'password' ? '<i class="fa-solid fa-eye"></i>' : '<i class="fa-solid fa-eye-slash"></i>';
}

// Strength meter
document.getElementById('newPasswordInput').addEventListener('input', function() {
  var v = this.value, s = 0;
  if (v.length >= 8) s++;
  if (/[A-Z]/.test(v)) s++;
  if (/[0-9]/.test(v)) s++;
  if (/[^A-Za-z0-9]/.test(v)) s++;
  var colors = ['#ef4444','#f97316','#eab308','#22c55e'];
  var labels = ['','Weak','Fair','Good','Strong'];
  for (var i = 1; i <= 4; i++) {
    document.getElementById('sbar'+i).style.background = (i <= s && s > 0) ? colors[s-1] : '#e2e8f0';
  }
  document.getElementById('strengthLabel').textContent = v.length > 0 ? 'Strength: ' + (labels[s] || 'Weak') : '';
});

// Confirm password match
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
  var np = document.getElementById('newPasswordInput').value;
  var cp = document.getElementById('confirmPasswordInput').value;
  var cf = document.getElementById('confirmPasswordInput');
  if (np !== cp) {
    e.preventDefault();
    cf.setCustomValidity('Passwords do not match.');
    this.classList.add('was-validated');
  } else {
    cf.setCustomValidity('');
  }
});

// Photo preview
document.getElementById('profilePhotoInput').addEventListener('change', function() {
  if (!this.files.length) return;
  if (this.files[0].size > 2097152) {
    alert('Photo must be under 2MB.');
    this.value = '';
    return;
  }
  var reader = new FileReader();
  reader.onload = function(e) {
    var initDiv = document.getElementById('profileAvatarInitials');
    var img = document.getElementById('profileAvatarImg');
    if (!img) {
      img = document.createElement('img');
      img.id = 'profileAvatarImg';
      img.className = 'rounded-circle border border-3 border-primary shadow';
      img.style.cssText = 'width:96px;height:96px;object-fit:cover;';
      if (initDiv) initDiv.replaceWith(img);
    }
    img.src = e.target.result;
  };
  reader.readAsDataURL(this.files[0]);
  document.getElementById('photoUploadActions').classList.remove('d-none');
});

document.getElementById('cancelPhotoBtn').addEventListener('click', function() {
  document.getElementById('profilePhotoInput').value = '';
  document.getElementById('photoUploadActions').classList.add('d-none');
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>

