<?php
$pageTitle = 'Notification Delivery Preferences — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
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

  <!-- Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <h3 class="fw-bold brand-font text-dark mb-1">Notification Delivery Preferences</h3>
      <p class="text-muted small mb-0">Customize how and when you receive in-app alerts and external email notifications.</p>
    </div>
    <a href="/notifications" class="btn btn-outline-secondary btn-sm px-3 bg-white">
      <i class="fa-solid fa-arrow-left me-1"></i> Back to Notifications
    </a>
  </div>

  <form action="/notifications/preferences/update" method="POST">
    <?= csrf_field() ?>
    <div class="card card-enterprise shadow-sm">
      <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-sliders text-primary me-2"></i> Operational Event Triggers</h6>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table-modern mb-0">
            <thead>
              <tr>
                <th style="min-width: 250px;">Operational Event Trigger</th>
                <th class="text-center" style="width: 160px;">In-App Notification</th>
                <th class="text-center" style="width: 160px;">Email Notification</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($eventTypes as $key => $title): ?>
                <?php
                  $hasInApp = !isset($preferences[$key]) || (int)($preferences[$key]['in_app'] ?? 1) === 1;
                  $hasEmail = !isset($preferences[$key]) || (int)($preferences[$key]['email'] ?? 1) === 1;
                ?>
                <tr>
                  <td>
                    <div class="fw-bold text-dark"><?= e($title) ?></div>
                    <div class="text-muted font-monospace" style="font-size: 0.7rem;"><?= e($key) ?></div>
                  </td>
                  <td class="text-center">
                    <div class="form-check form-switch d-inline-block">
                      <input class="form-check-input" type="checkbox" name="in_app[<?= e($key) ?>]" value="1" <?= $hasInApp ? 'checked' : '' ?>>
                    </div>
                  </td>
                  <td class="text-center">
                    <div class="form-check form-switch d-inline-block">
                      <input class="form-check-input" type="checkbox" name="email[<?= e($key) ?>]" value="1" <?= $hasEmail ? 'checked' : '' ?>>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer bg-light p-3 d-flex align-items-center justify-content-between">
        <span class="text-muted small"><i class="fa-solid fa-circle-info text-primary me-1"></i> Preferences apply to your personal staff officer account.</span>
        <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
          <i class="fa-solid fa-floppy-disk me-1"></i> Save Preferences
        </button>
      </div>
    </div>
  </form>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
