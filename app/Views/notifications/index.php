<?php
$pageTitle = 'Notification Center — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';

$activeFilter = $_GET['filter'] ?? 'all';
$selectedType = $_GET['type'] ?? '';

// Calculate severity metrics
$countUrgent = 0;
foreach ($notifications as $n) {
    if (in_array($n['severity'] ?? '', ['danger', 'warning'], true)) {
        $countUrgent++;
    }
}
?>

<!-- Link Dedicated Bento CSS -->
<link rel="stylesheet" href="/assets/css/dashboard-bento.css">
<link rel="stylesheet" href="/assets/css/pages/notifications-index.css">

<div class="content-body notifications-dashboard">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Bento Hero Header -->
  <div class="notifications-hero">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <div class="notifications-hero-title">
          <i class="fa-solid fa-bell text-danger"></i>
          <span>Notification Center</span>
          <?php if ($countUnread > 0): ?>
            <span class="notifications-hero-badge"><?= $countUnread ?> Unread</span>
          <?php else: ?>
            <span class="notifications-hero-badge" style="background: rgba(16, 185, 129, 0.25); color: #6EE7B7; border-color: rgba(16, 185, 129, 0.4);">All Clear</span>
          <?php endif; ?>
        </div>
        <p class="notifications-hero-desc">
          Live real-time operational notifications, SLA warnings, verification alerts, and consular event triggers.
        </p>
      </div>

      <div class="d-flex flex-wrap gap-2">
        <?php if (user_has_role(['super-admin', 'admin', 'branch-manager', 'visa-manager'])): ?>
          <a href="/notifications/admin" class="btn btn-primary btn-sm px-3 shadow-sm fw-semibold">
            <i class="fa-solid fa-tower-broadcast me-1.5"></i> Operations &amp; Logs
          </a>
        <?php endif; ?>
        <a href="/notifications/preferences" class="btn btn-outline-light btn-sm px-3 shadow-sm fw-semibold">
          <i class="fa-solid fa-sliders me-1.5"></i> Delivery Preferences
        </a>
        <a href="/notifications/mark-all-read" class="btn btn-light btn-sm px-3 shadow-sm fw-semibold text-dark">
          <i class="fa-solid fa-check-double me-1.5 text-primary"></i> Mark All as Read
        </a>
      </div>
    </div>
  </div>

  <!-- Bento KPI Grid -->
  <div class="bento-notif-kpi-grid">
    <!-- Unread Alerts -->
    <div class="bento-notif-kpi-card">
      <div class="bento-notif-kpi-header">
        <span class="bento-notif-kpi-label">Unread Alerts</span>
        <div class="bento-notif-kpi-icon icon-unread">
          <i class="fa-solid fa-bell"></i>
        </div>
      </div>
      <div class="bento-notif-kpi-value"><?= number_format($countUnread) ?></div>
      <div class="bento-notif-kpi-sub">
        <?php if ($countUnread > 0): ?>
          <span class="text-danger fw-semibold"><i class="fa-solid fa-circle-exclamation me-1"></i>Action required</span>
        <?php else: ?>
          <span class="text-success fw-semibold"><i class="fa-solid fa-check me-1"></i>Inbox up to date</span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Total In-App Notifications -->
    <div class="bento-notif-kpi-card">
      <div class="bento-notif-kpi-header">
        <span class="bento-notif-kpi-label">Total Volume</span>
        <div class="bento-notif-kpi-icon icon-total">
          <i class="fa-solid fa-inbox"></i>
        </div>
      </div>
      <div class="bento-notif-kpi-value"><?= number_format($countAll) ?></div>
      <div class="bento-notif-kpi-sub">
        <span>Historical notification records</span>
      </div>
    </div>

    <!-- Critical / Warning Severity -->
    <div class="bento-notif-kpi-card">
      <div class="bento-notif-kpi-header">
        <span class="bento-notif-kpi-label">Urgent / Warnings</span>
        <div class="bento-notif-kpi-icon icon-warning">
          <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
      </div>
      <div class="bento-notif-kpi-value"><?= number_format($countUrgent) ?></div>
      <div class="bento-notif-kpi-sub">
        <span>High-priority alerts in view</span>
      </div>
    </div>

    <!-- Multi-Channel Delivery Status -->
    <div class="bento-notif-kpi-card">
      <div class="bento-notif-kpi-header">
        <span class="bento-notif-kpi-label">Delivery Gateways</span>
        <div class="bento-notif-kpi-icon icon-channels">
          <i class="fa-solid fa-circle-nodes"></i>
        </div>
      </div>
      <div class="bento-notif-kpi-value" style="font-size: 1.45rem;">Email &amp; WhatsApp</div>
      <div class="bento-notif-kpi-sub">
        <span class="text-success fw-semibold"><i class="fa-solid fa-circle-dot me-1 text-success small"></i>Multi-channel active</span>
      </div>
    </div>
  </div>

  <!-- Filter & Control Toolbar -->
  <div class="notif-toolbar-card">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <!-- Status Filter Pills -->
      <div class="notif-filter-pills">
        <a href="/notifications?filter=all<?= $selectedType ? '&type=' . urlencode($selectedType) : '' ?>" class="notif-pill-btn <?= $activeFilter === 'all' ? 'active' : '' ?>">
          <i class="fa-solid fa-layer-group"></i> All (<?= $countAll ?>)
        </a>
        <a href="/notifications?filter=unread<?= $selectedType ? '&type=' . urlencode($selectedType) : '' ?>" class="notif-pill-btn <?= $activeFilter === 'unread' ? 'active active-unread' : '' ?>">
          <i class="fa-solid fa-envelope"></i> Unread Only (<?= $countUnread ?>)
        </a>
        <a href="/notifications?filter=read<?= $selectedType ? '&type=' . urlencode($selectedType) : '' ?>" class="notif-pill-btn <?= $activeFilter === 'read' ? 'active' : '' ?>">
          <i class="fa-solid fa-envelope-open"></i> Read Archived (<?= max(0, $countAll - $countUnread) ?>)
        </a>
      </div>

      <!-- Controls: Type Select & Live Filter -->
      <div class="d-flex align-items-center gap-2 flex-grow-1 flex-sm-grow-0 justify-content-sm-end">
        <div class="input-group input-group-sm" style="max-width: 220px;">
          <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
          <input type="text" id="liveNotifSearch" class="form-control border-start-0" placeholder="Filter alerts...">
        </div>

        <form action="/notifications" method="GET" class="m-0">
          <input type="hidden" name="filter" value="<?= e($activeFilter) ?>">
          <select name="type" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 170px;">
            <option value="">All Categories</option>
            <?php foreach (($types ?? []) as $t): ?>
              <option value="<?= e($t) ?>" <?= $selectedType === $t ? 'selected' : '' ?>><?= e($t) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
    </div>
  </div>

  <!-- Notifications Feed Container -->
  <div class="notif-feed-container">
    <?php if (empty($notifications)): ?>
      <div class="notif-empty-state">
        <div class="notif-empty-icon">
          <i class="fa-regular fa-bell-slash"></i>
        </div>
        <h5 class="fw-bold text-dark mb-1">No Notifications Found</h5>
        <p class="text-muted small mb-0">You're completely caught up! New operational events and SLA updates will appear here in real time.</p>
      </div>
    <?php else: ?>
      <div id="notifItemsList">
        <?php foreach ($notifications as $n): ?>
          <?php
            $isUnread = (int)$n['is_read'] === 0;
            $severity = $n['severity'] ?? 'info';
            $icon = 'fa-solid fa-circle-info';
            $avatarClass = 'info';

            if ($severity === 'danger') {
                $icon = 'fa-solid fa-triangle-exclamation';
                $avatarClass = 'danger';
            } elseif ($severity === 'warning') {
                $icon = 'fa-solid fa-clock';
                $avatarClass = 'warning';
            } elseif ($severity === 'success') {
                $icon = 'fa-solid fa-circle-check';
                $avatarClass = 'success';
            }
          ?>
          <div class="notif-item <?= $isUnread ? 'is-unread' : 'is-read' ?>" data-search-text="<?= e(strtolower($n['title'] . ' ' . $n['message'] . ' ' . ($n['notification_type'] ?? ''))) ?>">
            <!-- Severity Avatar -->
            <div class="notif-avatar <?= $avatarClass ?>">
              <i class="<?= $icon ?>"></i>
            </div>

            <!-- Content Body -->
            <div class="notif-body">
              <div class="notif-header-line">
                <h6 class="notif-title"><?= e($n['title']) ?></h6>
                <?php if (!empty($n['notification_type'])): ?>
                  <span class="notif-type-badge"><?= e($n['notification_type']) ?></span>
                <?php endif; ?>
                <?php if ($isUnread): ?>
                  <span class="notif-new-badge">
                    <span class="notif-new-dot"></span> NEW
                  </span>
                <?php endif; ?>
              </div>

              <div class="notif-message">
                <?= nl2br(e($n['message'])) ?>
              </div>

              <div class="notif-meta">
                <span><i class="fa-regular fa-clock me-1"></i><?= format_datetime($n['created_at']) ?></span>
                <?php if (!empty($n['severity'])): ?>
                  <span><i class="fa-solid fa-shield-halved me-1"></i><?= ucfirst(e($n['severity'])) ?> priority</span>
                <?php endif; ?>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="notif-actions">
              <?php if (!empty($n['link'])): ?>
                <a href="/notifications/mark-read?id=<?= $n['id'] ?>&redirect=<?= urlencode($n['link']) ?>" class="btn btn-sm btn-primary px-3 fw-semibold shadow-sm" style="font-size: 0.8rem;">
                  <span>View Record</span> <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
              <?php endif; ?>

              <?php if ($isUnread): ?>
                <a href="/notifications/mark-read?id=<?= $n['id'] ?>" class="btn btn-sm btn-light border px-2.5" title="Mark as Read">
                  <i class="fa-solid fa-check text-success"></i>
                </a>
              <?php endif; ?>

              <a href="/notifications/delete?id=<?= $n['id'] ?>" class="btn btn-sm btn-light border px-2.5 text-danger" title="Dismiss" onclick="return confirm('Dismiss this notification?');">
                <i class="fa-regular fa-trash-can"></i>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
// Client-side quick filter
document.getElementById('liveNotifSearch')?.addEventListener('input', function(e) {
  const query = e.target.value.toLowerCase().trim();
  const items = document.querySelectorAll('#notifItemsList .notif-item');
  items.forEach(item => {
    const text = item.getAttribute('data-search-text') || '';
    if (query === '' || text.includes(query)) {
      item.style.display = 'flex';
    } else {
      item.style.display = 'none';
    }
  });
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
