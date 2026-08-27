<?php
$currentUser = auth_user();
$pdo = App\Config\Database::getConnection();

// Fetch unread notifications for current staff user
$unreadNotifs = 0;
$recentNotifs = [];
try {
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM notifications WHERE (recipient_type = 'Staff' OR user_id = " . (int)($currentUser['id'] ?? 0) . ") AND is_read = 0");
    $unreadNotifs = (int)$stmtCount->fetchColumn();

    $stmtRecent = $pdo->query("SELECT * FROM notifications WHERE (recipient_type = 'Staff' OR user_id = " . (int)($currentUser['id'] ?? 0) . ") ORDER BY created_at DESC LIMIT 5");
    $recentNotifs = $stmtRecent->fetchAll() ?: [];
} catch (\Throwable $e) {
    // Graceful fallback
}

// Generate dynamic breadcrumb from URI
$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$uriSegments = array_filter(explode('/', trim($currentUri, '/')));
?>
<div class="app-main-content" id="appMainContent">
<header class="app-topbar" id="appTopbar">
  <div class="d-flex align-items-center gap-2 gap-md-3">
    <!-- Desktop Sidebar Collapse Toggle -->
    <button class="btn btn-light d-none d-lg-inline-flex p-2 border topbar-toggle-btn" id="desktopSidebarToggleBtn" type="button" aria-label="Toggle Desktop Sidebar" title="Collapse / Expand Navigation">
      <i class="fa-solid fa-bars-staggered text-dark"></i>
    </button>

    <!-- Mobile Hamburger Toggle -->
    <button class="btn btn-light d-lg-none p-2 border topbar-toggle-btn" id="sidebarToggleBtn" type="button" aria-label="Toggle Mobile Navigation Drawer">
      <i class="fa-solid fa-bars fs-6 text-dark"></i>
    </button>

    <!-- Mobile Brand Logo -->
    <a href="/dashboard" class="d-lg-none d-flex align-items-center text-decoration-none">
      <img src="/assets/images/logo.png" alt="MS Travel Hub" style="height: 32px; width: auto;" class="me-1">
      <span class="fw-bold fs-6 text-dark font-monospace">MS <span class="text-danger">TRAVEL</span></span>
    </a>
    
    <!-- Breadcrumb Trail & Page Context (Responsive) -->
    <div class="d-none d-md-block topbar-breadcrumb-container" style="min-width: 180px; max-width: 340px;">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0" style="font-size: 0.75rem;">
          <li class="breadcrumb-item"><a href="/dashboard" class="text-decoration-none text-muted"><i class="fa-solid fa-house-chimney small me-1"></i>Home</a></li>
          <?php if (empty($uriSegments) || $currentUri === '/dashboard'): ?>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Dashboard</li>
          <?php else: ?>
            <?php 
            $accumulated = '';
            $totalSeg = count($uriSegments);
            $idx = 0;
            foreach ($uriSegments as $seg): 
              $idx++;
              $accumulated .= '/' . $seg;
              $segLabel = ucwords(str_replace(['-', '_'], ' ', $seg));
              if ($idx === $totalSeg): ?>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page"><?= e($segLabel) ?></li>
              <?php else: ?>
                <li class="breadcrumb-item"><a href="<?= e($accumulated) ?>" class="text-decoration-none text-muted"><?= e($segLabel) ?></a></li>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </ol>
      </nav>
      <?php
        $cleanTitle = trim(explode('—', (string)($pageTitle ?? 'Staff Operations'))[0]);
      ?>
      <div class="fw-bold fs-6 page-title-header text-truncate" title="<?= e($cleanTitle) ?>"><?= e($cleanTitle) ?></div>
    </div>
  </div>

  <!-- Global Live Search Bar -->
  <div class="topbar-search position-relative">
    <i class="fa-solid fa-magnifying-glass topbar-search-icon"></i>
    <input type="text" id="globalSearchInput" class="form-control form-control-sm" placeholder="Search applicant, passport, application #..." autocomplete="off">
    <span class="search-shortcut-badge d-none d-md-inline-block">Ctrl K</span>

    <!-- Search Live Dropdown Results Container -->
    <div id="globalSearchResults" class="global-search-dropdown shadow-lg rounded-3 border d-none">
      <div class="p-2 border-bottom text-muted small d-flex justify-content-between align-items-center bg-light">
        <span class="fw-semibold"><i class="fa-solid fa-database text-primary me-1"></i> Live Database Results</span>
        <button type="button" class="btn-close btn-sm" id="closeSearchDropdown" aria-label="Close"></button>
      </div>
      <div id="searchResultsContent" class="search-results-list p-2">
        <div class="text-center py-3 text-muted small">Type 2 or more characters to search...</div>
      </div>
    </div>
  </div>

  <!-- Topbar Action Items -->
  <div class="d-flex align-items-center gap-2 gap-md-3">
    <!-- Quick Actions Button -->
    <div class="dropdown">
      <button class="btn btn-primary btn-sm px-2 px-md-3 rounded-pill d-flex align-items-center gap-1 shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fa-solid fa-plus"></i>
        <span class="d-none d-md-inline">Quick Action</span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow border-0 topbar-quick-action-menu" style="font-size: 0.875rem;">
        <li class="dropdown-header small text-uppercase text-muted" style="font-size: 0.7rem;">Visa Operations</li>
        <li><a class="dropdown-item py-2" href="/applications/create"><i class="fa-solid fa-folder-plus text-primary me-2"></i> New Visa Application</a></li>
        <li><a class="dropdown-item py-2" href="/customers/create"><i class="fa-solid fa-user-plus text-success me-2"></i> Register Applicant</a></li>
        <li><a class="dropdown-item py-2" href="/documents"><i class="fa-solid fa-file-arrow-up text-info me-2"></i> Upload Document</a></li>
        <li><hr class="dropdown-divider my-1"></li>
        <li class="dropdown-header small text-uppercase text-muted" style="font-size: 0.7rem;">Workflow</li>
        <li><a class="dropdown-item py-2" href="/tasks"><i class="fa-solid fa-list-check text-warning me-2"></i> Create Task</a></li>
        <li><a class="dropdown-item py-2" href="/appointments"><i class="fa-solid fa-calendar-plus text-danger me-2"></i> Schedule Appointment</a></li>
      </ul>
    </div>

    <!-- Notification Bell with Dropdown Panel -->
    <div class="dropdown">
      <button class="btn btn-light position-relative p-2 rounded-circle border topbar-icon-btn" id="topbarNotifBtn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
        <i class="fa-solid fa-bell text-secondary"></i>
        <span id="topbarNotifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger <?= $unreadNotifs > 0 ? '' : 'd-none' ?>" style="font-size: 0.65rem;">
          <?= $unreadNotifs > 99 ? '99+' : $unreadNotifs ?>
        </span>
      </button>
      <div class="dropdown-menu dropdown-menu-end shadow border-0 p-0 notification-dropdown" style="width: 340px; font-size: 0.85rem;">
        <div class="p-3 border-bottom d-flex align-items-center justify-content-between bg-light rounded-top">
          <div class="fw-bold">
            <i class="fa-solid fa-bell text-primary me-1"></i> Notifications
            <span id="topbarNotifHeaderBadge" class="badge bg-danger ms-1 <?= $unreadNotifs > 0 ? '' : 'd-none' ?>">
              <span id="topbarNotifCountText"><?= $unreadNotifs ?></span> new
            </span>
          </div>
          <a href="/notifications/mark-all-read" id="topbarMarkAllReadBtn" class="text-primary text-decoration-none small fw-semibold <?= $unreadNotifs > 0 ? '' : 'd-none' ?>">Mark all read</a>
        </div>

        <div class="notification-list" id="topbarNotifList" style="max-height: 280px; overflow-y: auto;">
          <?php if (empty($recentNotifs)): ?>
            <div class="p-4 text-center text-muted" id="topbarNotifEmptyState">
              <i class="fa-regular fa-bell-slash fs-3 mb-2 opacity-50"></i>
              <div class="small fw-semibold">No notifications right now</div>
              <div style="font-size: 0.72rem;">You are completely caught up!</div>
            </div>
          <?php else: ?>
            <?php foreach ($recentNotifs as $notif): ?>
              <a href="<?= e($notif['link'] ?: '/notifications') ?>" class="dropdown-item p-3 border-bottom text-wrap <?= empty($notif['is_read']) ? 'bg-light fw-medium' : '' ?>">
                <div class="d-flex align-items-start gap-2">
                  <div class="rounded-circle p-1 bg-<?= $notif['severity'] === 'danger' ? 'danger' : ($notif['severity'] === 'warning' ? 'warning' : 'primary') ?> text-white mt-1 flex-shrink-0" style="width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 0.65rem;">
                    <i class="fa-solid <?= $notif['severity'] === 'danger' ? 'fa-triangle-exclamation' : ($notif['severity'] === 'warning' ? 'fa-clock' : 'fa-info') ?>"></i>
                  </div>
                  <div class="flex-grow-1">
                    <div class="fw-semibold text-dark small mb-0"><?= e($notif['title']) ?></div>
                    <div class="text-muted small" style="font-size: 0.75rem; line-height: 1.3;"><?= e(mb_strimwidth($notif['message'], 0, 80, '...')) ?></div>
                    <div class="text-muted mt-1" style="font-size: 0.68rem;"><i class="fa-regular fa-clock me-1"></i><?= format_datetime($notif['created_at']) ?></div>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div class="p-2 text-center border-top bg-light rounded-bottom d-flex justify-content-between px-3">
          <a href="/notifications" class="text-primary text-decoration-none small fw-semibold">View All &rarr;</a>
          <?php if (user_has_role(['super-admin', 'admin', 'branch-manager', 'visa-manager'])): ?>
            <a href="/notifications/admin" class="text-secondary text-decoration-none small"><i class="fa-solid fa-tower-broadcast me-1"></i> Ops Center</a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- User Profile Dropdown -->
    <div class="dropdown">
      <a href="#" class="d-flex align-items-center gap-2 text-decoration-none text-dark topbar-user-btn" data-bs-toggle="dropdown" aria-expanded="false">
        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow-sm topbar-user-avatar" style="width: 36px; height: 36px; font-size: 0.9rem;">
          <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
        </div>
        <div class="d-none d-md-block text-start" style="line-height: 1.1;">
          <div class="fw-semibold small user-name-label"><?= e($currentUser['name'] ?? 'Staff') ?></div>
          <div class="text-muted user-role-label" style="font-size: 0.72rem;"><?= e($currentUser['role_name'] ?? 'Staff') ?></div>
        </div>
        <i class="fa-solid fa-chevron-down text-muted small ms-1 d-none d-sm-inline-block"></i>
      </a>
      <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="font-size: 0.875rem; min-width: 220px;">
        <li class="px-3 py-2 border-bottom bg-light rounded-top">
          <div class="fw-bold text-dark"><?= e($currentUser['name'] ?? '') ?></div>
          <div class="text-muted small text-truncate"><?= e($currentUser['email'] ?? '') ?></div>
          <div class="badge bg-primary mt-1"><?= e($currentUser['role_name'] ?? 'Staff') ?></div>
        </li>
        <li><a class="dropdown-item py-2" href="/dashboard"><i class="fa-solid fa-gauge me-2 text-muted"></i> Operations Dashboard</a></li>
        <li><a class="dropdown-item py-2" href="/audit-logs"><i class="fa-solid fa-clock-rotate-left me-2 text-muted"></i> My Activity Log</a></li>
        <?php if (user_has_role(['super-admin', 'admin', 'branch-manager', 'visa-manager'])): ?>
          <li><a class="dropdown-item py-2" href="/notifications/admin"><i class="fa-solid fa-tower-broadcast me-2 text-primary"></i> Notification Center</a></li>
        <?php endif; ?>
        <li><button type="button" class="dropdown-item py-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal"><i class="fa-solid fa-key me-2 text-muted"></i> Change Password</button></li>
        <?php if (user_has_role(['super-admin', 'admin', 'branch-manager'])): ?>
          <li><a class="dropdown-item py-2" href="/settings"><i class="fa-solid fa-sliders me-2 text-muted"></i> System Settings</a></li>
        <?php endif; ?>
        <li><hr class="dropdown-divider my-1"></li>
        <li><a class="dropdown-item py-2 text-danger fw-semibold" href="/auth/logout"><i class="fa-solid fa-right-from-bracket me-2"></i> Sign Out</a></li>
      </ul>
    </div>
  </div>
</header>

<script>
// Real-time Notification Synchronizer (Polling & SSE stream)
(function initRealtimeNotifications() {
  let lastUnreadCount = <?= $unreadNotifs ?>;

  async function checkNotifications() {
    try {
      const res = await fetch('/api/notifications/stream', { credentials: 'same-origin' });
      if (!res.ok) return;
      const text = await res.text();
      const match = text.match(/data:\s*({.*})/);
      if (!match) return;
      const data = JSON.parse(match[1]);
      
      const badge = document.getElementById('topbarNotifBadge');
      const headerBadge = document.getElementById('topbarNotifHeaderBadge');
      const countText = document.getElementById('topbarNotifCountText');
      const markAllBtn = document.getElementById('topbarMarkAllReadBtn');
      const listContainer = document.getElementById('topbarNotifList');

      if (badge && data.unread_count !== undefined) {
        if (data.unread_count > 0) {
          badge.classList.remove('d-none');
          badge.innerText = data.unread_count > 99 ? '99+' : data.unread_count;
          if (headerBadge) {
            headerBadge.classList.remove('d-none');
            countText.innerText = data.unread_count;
          }
          if (markAllBtn) markAllBtn.classList.remove('d-none');
        } else {
          badge.classList.add('d-none');
          if (headerBadge) headerBadge.classList.add('d-none');
          if (markAllBtn) markAllBtn.classList.add('d-none');
        }

        // Check if new alert arrived to animate bell icon
        if (data.unread_count > lastUnreadCount) {
          const btn = document.getElementById('topbarNotifBtn');
          if (btn) {
            btn.classList.add('animate__animated', 'animate__swing');
            setTimeout(() => btn.classList.remove('animate__animated', 'animate__swing'), 1500);
          }
        }
        lastUnreadCount = data.unread_count;
      }
    } catch (err) {
      // Graceful fallback
    }
  }

  // Periodic non-blocking background heartbeat every 15s
  setInterval(checkNotifications, 15000);
})();
</script>
