<?php
$currentUri = $_SERVER['REQUEST_URI'] ?? '/portal/dashboard';
$customer = auth_customer();
$pdo = \App\Config\Database::getConnection();
$customerId = (int)($customer['id'] ?? 0);
$unreadNotifsCount = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE (customer_id = {$customerId} OR (recipient_type = 'Customer' AND customer_id IS NULL)) AND is_read = 0")->fetchColumn();
?>

<!-- Customer Portal Header Navbar (MS Luxury Obsidian, Emerald & Sapphire Gradient) -->
<nav class="navbar navbar-expand-xl navbar-dark sticky-top shadow-sm py-2" style="background: linear-gradient(135deg, #070a12 0%, #064e3b 35%, #0369a1 70%, #1d4ed8 100%); border-bottom: 1px solid rgba(255,255,255,0.15); z-index: 1040;">
  <div class="container-fluid container-xl">
    <!-- Brand Logo -->
    <a class="navbar-brand d-flex align-items-center gap-2 me-3" href="/portal/dashboard">
      <img src="/assets/images/logo.png" alt="MS Travel Hub" style="height: 36px; width: auto; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));" class="rounded-2">
      <div class="lh-sm">
        <span class="fw-bold brand-font text-white fs-5" style="letter-spacing: -0.02em;">MS TRAVEL HUB</span>
        <span class="text-white-50 small d-none d-sm-inline ms-1" style="font-size: 0.72rem; letter-spacing: 0.05em;">| APPLICANT PORTAL</span>
      </div>
    </a>

    <!-- Mobile Toggler -->
    <button class="navbar-toggler border-0 p-2 text-white shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#portalNavbar" aria-controls="portalNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="portalNavbar">
      <!-- Portal Navigation Links (Stacked Icon + Label Layout) -->
      <ul class="navbar-nav mx-auto mb-2 mb-xl-0 gap-1 gap-xl-3 py-2 py-xl-0 align-items-stretch align-items-xl-center">
        <li class="nav-item">
          <a class="nav-link portal-nav-link text-center px-2.5 py-1.5 rounded-3 d-flex flex-row flex-xl-column align-items-center justify-content-start justify-content-xl-center gap-2 gap-xl-0 <?= str_starts_with($currentUri, '/portal/dashboard') ? 'active' : '' ?>" href="/portal/dashboard">
            <i class="fa-solid fa-gauge-high fs-5 mb-xl-1"></i>
            <span style="font-size: 0.75rem; font-weight: 600;">Dashboard</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link portal-nav-link text-center px-2.5 py-1.5 rounded-3 d-flex flex-row flex-xl-column align-items-center justify-content-start justify-content-xl-center gap-2 gap-xl-0 <?= str_starts_with($currentUri, '/portal/documents') ? 'active' : '' ?>" href="/portal/documents">
            <i class="fa-solid fa-file-circle-check fs-5 mb-xl-1"></i>
            <span style="font-size: 0.75rem; font-weight: 600;">Documents</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link portal-nav-link text-center px-2.5 py-1.5 rounded-3 d-flex flex-row flex-xl-column align-items-center justify-content-start justify-content-xl-center gap-2 gap-xl-0 <?= str_starts_with($currentUri, '/portal/appointments') ? 'active' : '' ?>" href="/portal/appointments">
            <i class="fa-solid fa-calendar-check fs-5 mb-xl-1"></i>
            <span style="font-size: 0.75rem; font-weight: 600;">Appointments</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link portal-nav-link text-center px-2.5 py-1.5 rounded-3 d-flex flex-row flex-xl-column align-items-center justify-content-start justify-content-xl-center gap-2 gap-xl-0 <?= str_starts_with($currentUri, '/portal/invoices') ? 'active' : '' ?>" href="/portal/invoices">
            <i class="fa-solid fa-receipt fs-5 mb-xl-1"></i>
            <span style="font-size: 0.75rem; font-weight: 600;">Invoices</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link portal-nav-link text-center px-2.5 py-1.5 rounded-3 d-flex flex-row flex-xl-column align-items-center justify-content-start justify-content-xl-center gap-2 gap-xl-0 <?= str_starts_with($currentUri, '/portal/wallet') ? 'active' : '' ?>" href="/portal/wallet">
            <i class="fa-solid fa-wallet fs-5 mb-xl-1 text-warning"></i>
            <span style="font-size: 0.75rem; font-weight: 600;">Wallet</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link portal-nav-link text-center px-2.5 py-1.5 rounded-3 position-relative d-flex flex-row flex-xl-column align-items-center justify-content-start justify-content-xl-center gap-2 gap-xl-0 <?= str_starts_with($currentUri, '/portal/notifications') ? 'active' : '' ?>" href="/portal/notifications">
            <div class="position-relative">
              <i class="fa-solid fa-bell fs-5 mb-xl-1" id="portalNotifBellIcon"></i>
              <span id="portalNotifBadge" class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle <?= $unreadNotifsCount > 0 ? '' : 'd-none' ?>" style="font-size: 0.62rem; padding: 0.25em 0.45em;"><?= $unreadNotifsCount ?></span>
            </div>
            <span style="font-size: 0.75rem; font-weight: 600;">Alerts</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link portal-nav-link text-center px-2.5 py-1.5 rounded-3 d-flex flex-row flex-xl-column align-items-center justify-content-start justify-content-xl-center gap-2 gap-xl-0 <?= str_starts_with($currentUri, '/portal/support') ? 'active' : '' ?>" href="/portal/support">
            <i class="fa-solid fa-headset fs-5 mb-xl-1"></i>
            <span style="font-size: 0.75rem; font-weight: 600;">Help &amp; Support</span>
          </a>
        </li>
      </ul>

      <!-- User Profile & Sign Out -->
      <div class="d-flex align-items-center justify-content-between justify-content-xl-end gap-3 pt-3 pt-xl-0 border-top border-xl-0 border-white border-opacity-10 mt-2 mt-xl-0">
        <div class="text-white text-start text-xl-end" style="line-height: 1.15;">
          <div class="fw-bold small text-white"><?= e($customer['full_name'] ?? 'Applicant') ?></div>
          <div class="text-white-50" style="font-size: 0.72rem;"><i class="fa-solid fa-id-badge me-1"></i><?= e($customer['customer_code'] ?? 'MSV-CUST') ?></div>
        </div>
        <a href="/portal/logout" class="btn btn-outline-light btn-sm px-3 rounded-pill fw-semibold shadow-sm">
          <i class="fa-solid fa-right-from-bracket me-1"></i> Sign Out
        </a>
      </div>
    </div>
  </div>
</nav>

<style>
.portal-nav-link {
  color: rgba(255, 255, 255, 0.85) !important;
  transition: all 0.2s ease;
  border: 1px solid transparent !important;
}
.portal-nav-link:hover {
  color: #ffffff !important;
  background: rgba(255, 255, 255, 0.12) !important;
}
.portal-nav-link.active {
  color: #ffffff !important;
  background: rgba(255, 255, 255, 0.22) !important;
  border-color: rgba(255, 255, 255, 0.35) !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25) !important;
}

@media (max-width: 1199.98px) {
  #portalNavbar {
    background: rgba(7, 10, 18, 0.96);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    padding: 1.25rem;
    border-radius: 12px;
    margin-top: 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.15);
    box-shadow: 0 12px 36px rgba(0, 0, 0, 0.5);
  }
}
</style>

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
