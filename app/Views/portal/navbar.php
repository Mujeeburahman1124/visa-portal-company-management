<?php
$currentUri = $_SERVER['REQUEST_URI'] ?? '/portal/dashboard';
$customer = auth_customer();
$pdo = \App\Config\Database::getConnection();
$customerId = (int)($customer['id'] ?? 0);
$unreadNotifsCount = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE (customer_id = {$customerId} OR (recipient_type = 'Customer' AND customer_id IS NULL)) AND is_read = 0")->fetchColumn();
?>

<!-- Customer Portal Header Navbar (MS Luxury Obsidian, Emerald & Sapphire Gradient) -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow py-2" style="background: linear-gradient(135deg, #070a12 0%, #064e3b 35%, #0369a1 70%, #1d4ed8 100%); border-bottom: 1px solid rgba(255,255,255,0.15); z-index: 1040;">
  <div class="container">
    <!-- Brand Logo -->
    <a class="navbar-brand d-flex align-items-center gap-2" href="/portal/dashboard">
      <img src="/assets/images/logo.png" alt="MS Travel Hub" style="height: 38px; width: auto;" class="bg-white p-1 rounded-2 shadow-sm">
      <div>
        <span class="fw-bold brand-font text-white fs-5" style="letter-spacing: -0.02em;">MS TRAVEL HUB</span>
        <span class="text-white-50 small d-none d-sm-inline ms-1" style="font-size: 0.72rem; letter-spacing: 0.05em;">| APPLICANT PORTAL</span>
      </div>
    </a>

    <!-- Mobile Toggler -->
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#portalNavbar" aria-controls="portalNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="portalNavbar">
      <!-- Portal Navigation Links -->
      <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-1">
        <li class="nav-item">
          <a class="nav-link text-white fw-semibold px-3 rounded-pill <?= str_starts_with($currentUri, '/portal/dashboard') ? 'active bg-white bg-opacity-20' : 'opacity-75 hover-opacity-100' ?>" href="/portal/dashboard">
            <i class="fa-solid fa-gauge-high me-1.5"></i> Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white fw-semibold px-3 rounded-pill <?= str_starts_with($currentUri, '/portal/documents') ? 'active bg-white bg-opacity-20' : 'opacity-75 hover-opacity-100' ?>" href="/portal/documents">
            <i class="fa-solid fa-file-circle-check me-1.5"></i> Documents
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white fw-semibold px-3 rounded-pill <?= str_starts_with($currentUri, '/portal/appointments') ? 'active bg-white bg-opacity-20' : 'opacity-75 hover-opacity-100' ?>" href="/portal/appointments">
            <i class="fa-solid fa-calendar-check me-1.5"></i> Appointments
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white fw-semibold px-3 rounded-pill <?= str_starts_with($currentUri, '/portal/invoices') ? 'active bg-white bg-opacity-20' : 'opacity-75 hover-opacity-100' ?>" href="/portal/invoices">
            <i class="fa-solid fa-receipt me-1.5"></i> Invoices
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white fw-semibold px-3 rounded-pill <?= str_starts_with($currentUri, '/portal/wallet') ? 'active bg-white bg-opacity-20' : 'opacity-75 hover-opacity-100' ?>" href="/portal/wallet">
            <i class="fa-solid fa-wallet me-1.5 text-warning"></i> Wallet
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white fw-semibold px-3 rounded-pill position-relative <?= str_starts_with($currentUri, '/portal/notifications') ? 'active bg-white bg-opacity-20' : 'opacity-75 hover-opacity-100' ?>" href="/portal/notifications">
            <i class="fa-solid fa-bell me-1.5" id="portalNotifBellIcon"></i> Alerts
            <span id="portalNotifBadge" class="badge bg-danger rounded-pill ms-1 <?= $unreadNotifsCount > 0 ? '' : 'd-none' ?>" style="font-size: 0.65rem;"><?= $unreadNotifsCount ?></span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white fw-semibold px-3 rounded-pill <?= str_starts_with($currentUri, '/portal/support') ? 'active bg-white bg-opacity-20' : 'opacity-75 hover-opacity-100' ?>" href="/portal/support">
            <i class="fa-solid fa-headset me-1.5"></i> Help &amp; Support
          </a>
        </li>
      </ul>

      <!-- User Profile & Sign Out -->
      <div class="d-flex align-items-center gap-3 pt-2 pt-lg-0 border-top border-lg-0 border-white border-opacity-10">
        <div class="text-white text-end d-none d-md-block" style="line-height: 1.15;">
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
