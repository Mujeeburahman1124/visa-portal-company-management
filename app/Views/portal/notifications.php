<?php
$pageTitle = 'Applicant Notification Inbox — VISA TRACK';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220%22%20%22100%22><text y=%22.9em%22 font-size=%2290%22>✈️</text></svg>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/main.css?v=7.0.0">
</head>
<body class="app-body">

<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="container py-4">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <h3 class="fw-bold brand-font text-dark mb-1">Applicant Notifications &amp; Alerts</h3>
      <p class="text-muted small mb-0">Direct notifications from your dedicated visa officer and embassy status updates.</p>
    </div>
    <a href="/portal/notifications?mark_all_read=1" class="btn btn-outline-primary btn-sm px-3 rounded-pill bg-white shadow-sm">
      <i class="fa-solid fa-check-double me-1"></i> Mark All as Read
    </a>
  </div>

  <div class="card card-enterprise shadow-sm">
    <div class="card-body p-0">
      <?php if (empty($notifications)): ?>
        <div class="text-center py-5 text-muted">
          <i class="fa-regular fa-bell-slash fs-2 mb-2"></i>
          <div class="fw-semibold">No notifications in your applicant inbox.</div>
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

              <?php if (!empty($n['link'])): ?>
                <a href="<?= e($n['link']) ?>" class="btn btn-sm btn-primary py-1 px-3 fw-semibold rounded-pill shadow-sm">
                  View &rarr;
                </a>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

