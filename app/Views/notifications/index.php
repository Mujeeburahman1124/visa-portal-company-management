<?php
$pageTitle = 'Notification Center — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
$activeFilter = $_GET['filter'] ?? 'all';
?>

<div class="content-body">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Page Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <div class="d-flex align-items-center gap-2">
        <h3 class="fw-bold brand-font text-dark mb-0">Notification Center</h3>
        <?php if ($countUnread > 0): ?>
          <span class="badge bg-danger rounded-pill px-2.5 py-1"><?= $countUnread ?> Unread</span>
        <?php endif; ?>
      </div>
      <p class="text-muted small mb-0">Live real-time operational notifications, SLA warnings, and verification alerts.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <?php if (user_has_role(['super-admin', 'admin', 'branch-manager', 'visa-manager'])): ?>
        <a href="/notifications/admin" class="btn btn-primary btn-sm px-3 shadow-sm">
          <i class="fa-solid fa-tower-broadcast me-1"></i> Operations &amp; Logs
        </a>
      <?php endif; ?>
      <a href="/notifications/preferences" class="btn btn-outline-secondary btn-sm px-3 bg-white shadow-sm">
        <i class="fa-solid fa-bell-slash me-1"></i> Delivery Preferences
      </a>
      <a href="/notifications/mark-all-read" class="btn btn-outline-primary btn-sm px-3 bg-white shadow-sm">
        <i class="fa-solid fa-check-double me-1"></i> Mark All as Read
      </a>
    </div>
  </div>

  <!-- Filter Pills Bar -->
  <div class="card card-enterprise mb-4 shadow-sm">
    <div class="card-body p-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div class="d-flex flex-wrap gap-1.5 align-items-center">
        <a href="/notifications?filter=all" class="btn btn-sm <?= $activeFilter === 'all' ? 'btn-primary' : 'btn-light border' ?> px-3 fw-semibold rounded-pill">
          All (<?= $countAll ?>)
        </a>
        <a href="/notifications?filter=unread" class="btn btn-sm <?= $activeFilter === 'unread' ? 'btn-danger' : 'btn-light border' ?> px-3 fw-semibold rounded-pill">
          Unread Only (<?= $countUnread ?>)
        </a>
        <a href="/notifications?filter=read" class="btn btn-sm <?= $activeFilter === 'read' ? 'btn-secondary' : 'btn-light border' ?> px-3 fw-semibold rounded-pill">
          Read Archived
        </a>
      </div>

      <!-- Type Filter Dropdown -->
      <form action="/notifications" method="GET" class="d-flex align-items-center gap-1">
        <input type="hidden" name="filter" value="<?= e($activeFilter) ?>">
        <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">All Notification Types</option>
          <?php foreach ($types as $t): ?>
            <option value="<?= e($t) ?>" <?= ($_GET['type'] ?? '') === $t ? 'selected' : '' ?>><?= e($t) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
  </div>

  <!-- Notifications List Container -->
  <div class="card card-enterprise shadow-sm">
    <div class="card-body p-0">
      <?php if (empty($notifications)): ?>
        <div class="text-center py-5 text-muted">
          <i class="fa-regular fa-bell-slash fs-2 text-muted mb-2"></i>
          <div class="fw-semibold">No notifications found in this view.</div>
        </div>
      <?php else: ?>
        <div class="list-group list-group-flush">
          <?php foreach ($notifications as $n): ?>
            <?php
              $isUnread = (int)$n['is_read'] === 0;
              $icon = 'fa-solid fa-circle-info text-primary';
              if ($n['severity'] === 'danger') $icon = 'fa-solid fa-triangle-exclamation text-danger';
              elseif ($n['severity'] === 'warning') $icon = 'fa-solid fa-clock text-warning';
              elseif ($n['severity'] === 'success') $icon = 'fa-solid fa-circle-check text-success';
            ?>
            <div class="list-group-item p-3.5 d-flex flex-wrap align-items-center justify-content-between gap-3 <?= $isUnread ? 'bg-primary-subtle bg-opacity-25' : 'bg-white' ?>">
              <div class="d-flex align-items-start gap-3 flex-grow-1">
                <div class="fs-4 mt-0.5"><i class="<?= $icon ?>"></i></div>
                <div>
                  <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <span class="fw-bold text-dark fs-6"><?= e($n['title']) ?></span>
                    <span class="badge bg-light text-dark border small"><?= e($n['notification_type']) ?></span>
                    <?php if ($isUnread): ?>
                      <span class="badge bg-danger fw-bold" style="font-size: 0.65rem;">NEW</span>
                    <?php endif; ?>
                  </div>
                  <p class="text-muted small mb-1"><?= e($n['message']) ?></p>
                  <div class="text-secondary" style="font-size: 0.72rem;">
                    <i class="fa-regular fa-clock me-1"></i><?= format_datetime($n['created_at']) ?>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="d-flex align-items-center gap-2">
                <?php if (!empty($n['link'])): ?>
                  <a href="/notifications/mark-read?id=<?= $n['id'] ?>&redirect=<?= urlencode($n['link']) ?>" class="btn btn-sm btn-primary py-1 px-3 fw-semibold shadow-sm">
                    View Record &rarr;
                  </a>
                <?php endif; ?>

                <?php if ($isUnread): ?>
                  <a href="/notifications/mark-read?id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Mark as Read">
                    <i class="fa-solid fa-check"></i>
                  </a>
                <?php endif; ?>

                <a href="/notifications/delete?id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-danger py-1 px-2" title="Dismiss">
                  <i class="fa-solid fa-trash-can"></i>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
