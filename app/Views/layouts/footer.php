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

<!-- Core JS Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js?v=1.3.0"></script>
</body>
</html>
