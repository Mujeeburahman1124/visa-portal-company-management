</div><!-- End .app-main-content -->
</div><!-- End .app-wrapper -->

<!-- Global Toast Notification Container -->
<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;">
  <?php $flash = get_flash(); if ($flash): ?>
    <div class="toast align-items-center text-bg-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> border-0 show shadow" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body d-flex align-items-center gap-2">
          <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
          <span><?= e($flash['message']) ?></span>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold" id="changePasswordModalLabel"><i class="fa-solid fa-key text-primary me-2"></i> Change Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/auth/change-password" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Current Password</label>
            <input type="password" name="current_password" class="form-control" required placeholder="Enter current password">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">New Password</label>
            <input type="password" name="new_password" class="form-control" required minlength="8" placeholder="Minimum 8 characters">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required minlength="8" placeholder="Re-enter new password">
          </div>
        </div>
        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold"><i class="fa-solid fa-save me-1"></i> Update Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$curFootUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
?>
<!-- Native Mobile App Bottom Navigation Bar (Bankio Mobile View) -->
<nav class="mobile-app-bottom-nav d-md-none" id="mobileAppBottomNav">
  <a href="/dashboard" class="mobile-nav-item <?= $curFootUri === '/dashboard' ? 'active' : '' ?>">
    <i class="fa-solid fa-shapes"></i>
    <span>Dashboard</span>
  </a>
  <a href="/applications" class="mobile-nav-item <?= (str_starts_with($curFootUri, '/applications') && !str_starts_with($curFootUri, '/applications/create')) ? 'active' : '' ?>">
    <i class="fa-solid fa-folder-open"></i>
    <span>Applications</span>
  </a>
  <!-- Center Fast-Scan Action Button (FAB) -->
  <a href="/applications/create" class="mobile-nav-scan-fab" title="Fast-Path Passport Scan">
    <i class="fa-solid fa-camera"></i>
  </a>
  <a href="/documents" class="mobile-nav-item <?= str_starts_with($curFootUri, '/documents') ? 'active' : '' ?>">
    <i class="fa-solid fa-file-circle-check"></i>
    <span>Documents</span>
  </a>
  <a href="javascript:void(0)" class="mobile-nav-item" id="mobileMenuOpenBtn" onclick="document.getElementById('sidebarToggleBtn')?.click();">
    <i class="fa-solid fa-bars-staggered"></i>
    <span>Menu</span>
  </a>
</nav>

<!-- Core JS Dependencies -->
<script src="/assets/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/flatpickr.min.js"></script>
<script src="/assets/js/app-datepicker.js?v=1.0.0"></script>
<script src="/assets/js/app.js?v=2.1.0"></script>
<!-- Central Theme Engine -->
<script src="/assets/js/theme.js?v=2.0"></script>

<?php if (!empty($_SESSION['auto_open_whatsapp'])): 
  $waAutoUrl = $_SESSION['auto_open_whatsapp'];
  unset($_SESSION['auto_open_whatsapp']);
?>
<script>
  (function() {
    var url = <?= json_encode($waAutoUrl) ?>;
    if (url) {
      setTimeout(function() {
        var w = window.open(url, '_blank');
        if (!w || w.closed || typeof w.closed === 'undefined') {
          // If browser popup blocker intercepts, redirect gracefully
          window.location.href = url;
        }
      }, 350);
    }
  })();
</script>
<?php endif; ?>
</body>


</html>
