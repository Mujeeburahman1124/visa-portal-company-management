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

// Super Admin System Error Detection
$isSuperAdminUser = (($currentUser['role_slug'] ?? '') === 'super-admin' || ($currentUser['role'] ?? '') === 'Super Admin' || is_impersonating());
$systemErrorCount = 0;
if ($isSuperAdminUser) {
    try {
        $stmtErr = $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE action IN ('SYSTEM_ERROR', 'ERROR', 'EXCEPTION') AND action != 'RESOLVED_ERROR'");
        $systemErrorCount = (int)$stmtErr->fetchColumn();
    } catch (\Throwable $e) {}
}

// Generate dynamic breadcrumb from URI
$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$uriSegments = array_filter(explode('/', trim($currentUri, '/')));
?>
<div class="app-main-content" id="appMainContent">
<?php if (is_impersonating()): 
    $origAdmin = original_admin_user();
?>
  <div class="impersonation-banner bg-warning text-dark px-3 py-2 d-flex flex-wrap align-items-center justify-content-between shadow-sm border-bottom border-warning-subtle" style="font-size: 0.84rem; z-index: 1040;">
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-dark text-warning p-1 px-2"><i class="fa-solid fa-user-secret me-1"></i> Impersonating</span>
      <span>Operating as: <strong><?= e($currentUser['name'] ?? 'Staff') ?></strong> (Role: <span class="fw-bold"><?= e($currentUser['role_name'] ?? 'Staff') ?></span>, Branch: <?= e($currentUser['branch_name'] ?? 'Main') ?>) &mdash; Real Admin: <em><?= e($origAdmin['name'] ?? 'Super Admin') ?></em></span>
    </div>
    <div class="d-flex align-items-center gap-2 mt-1 mt-md-0">
      <a href="/auth/switch-back" class="btn btn-dark btn-sm py-1 px-3 fw-bold shadow-sm">
        <i class="fa-solid fa-rotate-left me-1"></i> Return to Super Admin
      </a>
    </div>
  </div>
<?php endif; ?>
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
    <div class="d-none d-lg-block topbar-breadcrumb-container" style="min-width: 180px; max-width: 340px;">
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
  <div class="d-flex align-items-center gap-2 ms-auto topbar-actions-group">
    <!-- UAE / Dubai Local Time Display -->
    <div class="d-none d-md-flex align-items-center gap-2 px-2.5 py-1 rounded-pill bg-light border text-muted small flex-shrink-0" style="font-size: 0.78rem;" title="Official UAE Standard Time (GST, UTC+4)">
      <i class="fa-regular fa-clock text-primary"></i>
      <span class="fw-semibold text-dark" id="uaeLiveClock"><?= date('h:i A') ?></span>
      <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">GST (UAE)</span>
    </div>

    <!-- Quick Actions Button (Responsive: hidden on xs phones, visible on sm+) -->
    <div class="dropdown flex-shrink-0 d-none d-sm-block">
      <button class="btn btn-primary btn-sm px-2.5 px-md-3 rounded-pill d-flex align-items-center gap-1 shadow-sm topbar-quick-action-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Quick Action">
        <i class="fa-solid fa-plus"></i>
        <span class="d-none d-md-inline ms-1 fw-semibold">Quick Action</span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow border-0 topbar-quick-action-menu" style="font-size: 0.875rem;">
        <li class="dropdown-header small text-uppercase text-muted" style="font-size: 0.7rem;">Visa Operations</li>
        <li><a class="dropdown-item py-2" href="/applications/create"><i class="fa-solid fa-folder-plus text-primary me-2"></i> New Visa Application</a></li>
        <li><a class="dropdown-item py-2" href="/customers/create"><i class="fa-solid fa-user-plus text-success me-2"></i> Register Applicant</a></li>
        <li><a class="dropdown-item py-2" href="/tracking"><i class="fa-solid fa-magnifying-glass-location text-info me-2"></i> Track Visa Status</a></li>
        <li><a class="dropdown-item py-2" href="/documents"><i class="fa-solid fa-file-arrow-up text-secondary me-2"></i> Upload Document</a></li>
        <li><hr class="dropdown-divider my-1"></li>
        <li class="dropdown-header small text-uppercase text-muted" style="font-size: 0.7rem;">Workflow &amp; Finance</li>
        <li><a class="dropdown-item py-2" href="/payments"><i class="fa-solid fa-receipt text-success me-2"></i> Record Payment / Invoices</a></li>
        <li><a class="dropdown-item py-2" href="/tasks"><i class="fa-solid fa-list-check text-warning me-2"></i> Create Task</a></li>
        <li><a class="dropdown-item py-2" href="/appointments"><i class="fa-solid fa-calendar-plus text-danger me-2"></i> Schedule Appointment</a></li>
      </ul>
    </div>

    <!-- Super Admin System Errors Warning Indicator -->
    <?php if ($isSuperAdminUser && $systemErrorCount > 0): ?>
      <a href="/audit-logs?action=SYSTEM_ERROR" class="btn btn-outline-danger btn-sm rounded-pill d-flex align-items-center gap-1 px-2 px-md-2.5 shadow-sm flex-shrink-0" title="System Errors Logged — Click to inspect">
        <i class="fa-solid fa-triangle-exclamation text-danger"></i>
        <span class="d-none d-md-inline small fw-bold">Errors</span>
        <span class="badge bg-danger rounded-pill"><?= $systemErrorCount ?></span>
      </a>
    <?php endif; ?>

    <!-- Theme Palette Selector (Responsive: hidden on xs phones, visible on sm+) -->
    <div class="theme-selector-wrap flex-shrink-0 d-none d-sm-block">
      <button class="theme-selector-btn" type="button" aria-label="Choose Theme" title="Choose Theme">
        <span class="theme-swatch-current"></span>
        <span class="d-none d-xl-inline">Theme</span>
        <i class="fa-solid fa-chevron-down" style="font-size: 0.65rem;"></i>
      </button>
    </div>

    <!-- Notification Bell with Dropdown Panel -->
    <div class="dropdown flex-shrink-0">
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
    <div class="dropdown flex-shrink-0">
      <a href="#" class="d-flex align-items-center gap-2 text-decoration-none text-dark topbar-user-btn" data-bs-toggle="dropdown" aria-expanded="false" title="<?= e($currentUser['name'] ?? 'User Profile') ?>">
        <?php $topbarAvatar = user_avatar_url($currentUser); if (!empty($topbarAvatar)): ?>
          <img src="<?= e($topbarAvatar) ?>" alt="<?= e($currentUser['name'] ?? 'User Profile') ?>" class="rounded-circle shadow-sm topbar-user-avatar border" style="width: 36px; height: 36px; object-fit: cover;">
        <?php else: ?>
          <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow-sm topbar-user-avatar" style="width: 36px; height: 36px; font-size: 0.9rem;">
            <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
          </div>
        <?php endif; ?>
        <div class="d-none d-lg-block text-start" style="line-height: 1.1;">
          <div class="fw-semibold small user-name-label"><?= e($currentUser['name'] ?? 'Staff') ?></div>
          <div class="text-muted user-role-label" style="font-size: 0.72rem;"><?= e($currentUser['role_name'] ?? 'Staff') ?></div>
        </div>
        <i class="fa-solid fa-chevron-down text-muted small ms-1 d-none d-sm-inline-block"></i>
      </a>
      <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="font-size: 0.875rem; min-width: 230px;">
        <li class="px-3 py-2 border-bottom bg-light rounded-top">
          <div class="fw-bold text-dark"><?= e($currentUser['name'] ?? '') ?></div>
          <div class="text-muted small text-truncate"><?= e($currentUser['email'] ?? '') ?></div>
          <div class="badge bg-primary mt-1"><?= e($currentUser['role_name'] ?? 'Staff') ?></div>
        </li>
        <li><a class="dropdown-item py-2" href="/profile"><i class="fa-solid fa-id-badge me-2 text-primary"></i> My Profile &amp; Password</a></li>
        <li><a class="dropdown-item py-2" href="/dashboard"><i class="fa-solid fa-gauge me-2 text-muted"></i> Operations Dashboard</a></li>
        <li><a class="dropdown-item py-2" href="/audit-logs"><i class="fa-solid fa-clock-rotate-left me-2 text-muted"></i> My Activity Log</a></li>
        <?php if (user_has_role(['super-admin', 'admin', 'branch-manager', 'visa-manager'])): ?>
          <li><a class="dropdown-item py-2" href="/notifications/admin"><i class="fa-solid fa-tower-broadcast me-2 text-primary"></i> Notification Center</a></li>
        <?php endif; ?>
        <?php if (user_has_role(['super-admin', 'admin', 'branch-manager'])): ?>
          <li><a class="dropdown-item py-2" href="/settings"><i class="fa-solid fa-sliders me-2 text-muted"></i> System Settings</a></li>
        <?php endif; ?>
        <?php if (can_switch_accounts()): 
            $topbarSwitchUsers = get_switchable_users();
        ?>
          <li><hr class="dropdown-divider my-1"></li>
          <li class="dropdown-header small text-uppercase text-muted" style="font-size: 0.7rem;"><i class="fa-solid fa-users-viewfinder text-primary me-1"></i> Switch Account</li>
          <?php if (is_impersonating()): ?>
            <li>
              <a class="dropdown-item py-1.5 text-warning fw-bold bg-warning-subtle text-dark" href="/auth/switch-back">
                <i class="fa-solid fa-rotate-left me-2 text-warning"></i> Return to Super Admin
              </a>
            </li>
          <?php endif; ?>
          <?php foreach ($topbarSwitchUsers as $tsu): ?>
            <?php if ((int)$tsu['id'] !== (int)($currentUser['id'] ?? 0)): ?>
              <li>
                <a class="dropdown-item py-1.5 d-flex align-items-center justify-content-between" href="/auth/switch?user_id=<?= $tsu['id'] ?>">
                  <span class="text-truncate" style="max-width: 145px;">
                    <i class="fa-solid <?= $tsu['role_slug'] === 'super-admin' ? 'fa-crown text-warning' : ($tsu['role_slug'] === 'admin' ? 'fa-user-shield text-danger' : 'fa-user text-primary') ?> me-1.5"></i>
                    <?= e($tsu['name']) ?>
                  </span>
                  <span class="badge bg-light text-dark border ms-1" style="font-size: 0.65rem;"><?= e($tsu['role_name']) ?></span>
                </a>
              </li>
            <?php endif; ?>
          <?php endforeach; ?>
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

  const markBtn = document.getElementById('topbarMarkAllReadBtn');
  if (markBtn) {
    markBtn.addEventListener('click', async function(e) {
      e.preventDefault();
      const badge = document.getElementById('topbarNotifBadge');
      const headerBadge = document.getElementById('topbarNotifHeaderBadge');
      if (badge) badge.classList.add('d-none');
      if (headerBadge) headerBadge.classList.add('d-none');
      markBtn.classList.add('d-none');
      try {
        await fetch('/notifications/mark-all-read', { method: 'GET', credentials: 'same-origin' });
      } catch (err) {}
    });
  }

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

  // Live UAE Clock ticker
  function updateUaeClock() {
    var el = document.getElementById('uaeLiveClock');
    if (!el) return;
    try {
      var d = new Date();
      var timeStr = d.toLocaleTimeString('en-US', {
        timeZone: 'Asia/Dubai',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
      });
      el.textContent = timeStr;
    } catch(e) {}
  }
  setInterval(updateUaeClock, 1000);
})();
</script>
