<?php
$currentUri = $_SERVER['REQUEST_URI'] ?? '/portal/dashboard';
$customer = auth_customer();
$pdo = \App\Config\Database::getConnection();
$customerId = (int)($customer['id'] ?? 0);
$unreadNotifsCount = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE customer_id = {$customerId} AND is_read = 0")->fetchColumn();
?>

<!-- Flatpickr Datepicker Assets -->
<link rel="stylesheet" href="/assets/css/flatpickr.min.css">
<script src="/assets/js/flatpickr.min.js"></script>
<script src="/assets/js/app-datepicker.js?v=1.0.0"></script>

<!-- Applicant Portal Bento Design System (Official Brand Red #E11D48 Theme) -->
<link rel="stylesheet" href="/assets/css/pages/portal-bento.css?v=<?= time() ?>">

<!-- Customer Portal Header Navbar -->
<nav class="navbar navbar-expand-xl portal-navbar sticky-top">
  <div class="container-fluid container-xl">
    <!-- Brand Logo -->
    <a class="navbar-brand" href="/portal/dashboard">
      <img src="/assets/images/logo.png" alt="MS Travel Hub" class="portal-brand-logo">
      <div class="d-flex flex-column">
        <span class="portal-brand-title">MS <span class="brand-gem">TRAVEL HUB</span></span>
      </div>
      <span class="portal-tag ms-1">
        <i class="fa-solid fa-passport"></i> Applicant Portal
      </span>
    </a>

    <!-- Mobile Toggler -->
    <button class="navbar-toggler border-0 p-2 text-white shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#portalNavbar" aria-controls="portalNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <i class="fa-solid fa-bars fs-5"></i>
    </button>

    <div class="collapse navbar-collapse" id="portalNavbar">
      <!-- Portal Navigation Links -->
      <ul class="navbar-nav mx-auto my-2 my-xl-0 gap-1 gap-xl-2 align-items-stretch align-items-xl-center">
        <li class="nav-item">
          <a class="nav-link <?= str_starts_with($currentUri, '/portal/dashboard') ? 'active' : '' ?>" href="/portal/dashboard">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= str_starts_with($currentUri, '/portal/documents') ? 'active' : '' ?>" href="/portal/documents">
            <i class="fa-solid fa-file-circle-check"></i>
            <span>Documents</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= str_starts_with($currentUri, '/portal/appointments') ? 'active' : '' ?>" href="/portal/appointments">
            <i class="fa-solid fa-calendar-check"></i>
            <span>Appointments</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= str_starts_with($currentUri, '/portal/invoices') ? 'active' : '' ?>" href="/portal/invoices">
            <i class="fa-solid fa-receipt"></i>
            <span>Invoices</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= str_starts_with($currentUri, '/portal/wallet') ? 'active' : '' ?>" href="/portal/wallet">
            <i class="fa-solid fa-wallet"></i>
            <span>Wallet</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link position-relative <?= str_starts_with($currentUri, '/portal/notifications') ? 'active' : '' ?>" href="/portal/notifications">
            <i class="fa-solid fa-bell" id="portalNotifBellIcon"></i>
            <span>Alerts</span>
            <span id="portalNotifBadge" class="badge bg-danger rounded-pill ms-1 <?= $unreadNotifsCount > 0 ? '' : 'd-none' ?>" style="font-size: 0.65rem;"><?= $unreadNotifsCount ?></span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= str_starts_with($currentUri, '/portal/support') ? 'active' : '' ?>" href="/portal/support">
            <i class="fa-solid fa-headset"></i>
            <span>Help &amp; Support</span>
          </a>
        </li>
      </ul>

      <!-- User Profile & Account Actions -->
      <div class="d-flex align-items-center justify-content-between justify-content-xl-end gap-2 pt-2 pt-xl-0">
        <div class="dropdown">
          <button class="portal-user-badge border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Account Security &amp; Profile">
            <div class="portal-avatar-circle">
              <?= strtoupper(substr($customer['full_name'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="text-start d-none d-sm-block" style="line-height: 1.15;">
              <div class="fw-bold text-white text-truncate" style="max-width: 140px; font-size: 0.82rem;"><?= e($customer['full_name'] ?? 'Applicant') ?></div>
              <div class="text-white-50 font-monospace" style="font-size: 0.68rem;"><?= e($customer['customer_code'] ?? 'MSV-CUST') ?></div>
            </div>
            <i class="fa-solid fa-chevron-down text-white-50 ms-1" style="font-size: 0.65rem;"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2" style="font-size: 0.85rem; min-width: 220px; z-index: 1060;">
            <li class="px-3 py-2 border-bottom bg-light rounded-top">
              <div class="fw-bold text-dark"><?= e($customer['full_name'] ?? 'Applicant') ?></div>
              <div class="text-muted small text-truncate"><?= e($customer['email'] ?? '') ?></div>
              <div class="badge mt-1" style="background:var(--portal-primary);"><?= e($customer['customer_code'] ?? '') ?></div>
            </li>
            <li>
              <button type="button" class="dropdown-item py-2 text-dark" data-bs-toggle="modal" data-bs-target="#portalChangePasswordModal">
                <i class="fa-solid fa-key me-2 text-warning"></i> Reset / Change Password
              </button>
            </li>
            <li><hr class="dropdown-divider my-1"></li>
            <li>
              <a href="/portal/logout" class="dropdown-item py-2 text-danger">
                <i class="fa-solid fa-right-from-bracket me-2"></i> Sign Out
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</nav>

<!-- Modal: Reset / Change Password -->
<div class="modal fade" id="portalChangePasswordModal" tabindex="-1" aria-labelledby="portalChangePasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <div class="modal-header text-white" style="background: linear-gradient(135deg, var(--portal-primary) 0%, var(--portal-primary-hover) 100%);">
        <h6 class="modal-title fw-bold mb-0" id="portalChangePasswordModalLabel">
          <i class="fa-solid fa-shield-halved me-2"></i> Reset / Change Password
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/portal/change-password" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Current Password</label>
            <div class="input-group">
              <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-lock"></i></span>
              <input type="password" name="current_password" class="form-control portal-form-control" placeholder="••••••••" required>
            </div>
            <div class="form-text text-muted small" style="font-size: 0.72rem;">Enter your current password to authorize change.</div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">New Password</label>
            <div class="input-group">
              <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-key"></i></span>
              <input type="password" name="new_password" class="form-control portal-form-control" placeholder="Minimum 6 characters" minlength="6" required>
            </div>
          </div>
          <div class="mb-2">
            <label class="form-label small fw-semibold text-secondary">Confirm New Password</label>
            <div class="input-group">
              <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-check-double"></i></span>
              <input type="password" name="confirm_password" class="form-control portal-form-control" placeholder="Re-type new password" minlength="6" required>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light border-top py-2 px-4 d-flex justify-content-between">
          <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="portal-btn-primary py-1.5 px-3" style="font-size:0.85rem;">
            <i class="fa-solid fa-floppy-disk me-1"></i> Update Password
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Real-time Applicant Notification Synchronizer
(function initCustomerRealtimeNotifications() {
  async function checkCustomerNotifications() {
    try {
      const res = await fetch('/api/notifications/stream', { credentials: 'same-origin' });
      if (!res.ok) return;
      const text = await res.text();
      const match = text.match(/data:\s*({.*})/);
      if (!match) return;
      const data = JSON.parse(match[1]);

      const badge = document.getElementById('portalNotifBadge');
      if (badge && data.unread_count !== undefined) {
        if (data.unread_count > 0) {
          badge.classList.remove('d-none');
          badge.innerText = data.unread_count > 99 ? '99+' : data.unread_count;
        } else {
          badge.classList.add('d-none');
        }
      }
    } catch (e) {}
  }
  setInterval(checkCustomerNotifications, 15000);
})();
</script>
