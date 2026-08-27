<?php
$pageTitle = 'Enterprise Notification Center & Delivery Logs — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
$activeTab = $_GET['tab'] ?? 'logs';
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

  <!-- Top Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <h3 class="fw-bold brand-font text-dark mb-0">Notification Operations Center</h3>
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 small fw-semibold">Real-Time Multi-Channel</span>
      </div>
      <p class="text-muted small mb-0">Centralized delivery monitoring, Meta WhatsApp Business Cloud API &amp; SMTP logs, event toggles, and template management.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a href="/notifications" class="btn btn-outline-secondary btn-sm px-3 bg-white shadow-sm">
        <i class="fa-solid fa-inbox me-1"></i> My Staff Inbox
      </a>
      <button type="button" class="btn btn-outline-primary btn-sm px-3 bg-white shadow-sm" data-bs-toggle="modal" data-bs-target="#testNotificationModal">
        <i class="fa-solid fa-paper-plane me-1"></i> Send Test Message
      </button>
      <a href="/api/notifications/process-queue" target="_blank" class="btn btn-light btn-sm px-3 border shadow-sm" title="Trigger background retry worker">
        <i class="fa-solid fa-rotate me-1 text-muted"></i> Run Queue Worker
      </a>
    </div>
  </div>

  <!-- KPI Metrics Cards -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card card-enterprise shadow-sm border-0 h-100 p-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-muted small fw-semibold">Total Delivered</span>
          <div class="rounded-circle bg-primary bg-opacity-10 p-2 text-primary" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
            <i class="fa-solid fa-paper-plane fs-6"></i>
          </div>
        </div>
        <div class="fs-3 fw-bold text-dark mb-1"><?= number_format($totalSent) ?></div>
        <div class="small text-muted d-flex align-items-center gap-1">
          <i class="fa-solid fa-shield-halved text-success"></i> Across Email, WhatsApp &amp; In-App
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card card-enterprise shadow-sm border-0 h-100 p-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-muted small fw-semibold">Email Success Rate</span>
          <div class="rounded-circle bg-info bg-opacity-10 p-2 text-info" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
            <i class="fa-solid fa-envelope fs-6"></i>
          </div>
        </div>
        <div class="fs-3 fw-bold text-dark mb-1"><?= $emailRate ?>%</div>
        <div class="small text-muted d-flex align-items-center gap-1">
          <span class="text-info fw-semibold"><?= number_format($emailSuccess) ?></span> sent of <?= number_format($totalEmail) ?> total attempts
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card card-enterprise shadow-sm border-0 h-100 p-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-muted small fw-semibold">WhatsApp Delivery Rate</span>
          <div class="rounded-circle bg-success bg-opacity-10 p-2 text-success" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
            <i class="fa-brands fa-whatsapp fs-5"></i>
          </div>
        </div>
        <div class="fs-3 fw-bold text-dark mb-1"><?= $whatsappRate ?>%</div>
        <div class="small text-muted d-flex align-items-center gap-1">
          <span class="text-success fw-semibold"><?= number_format($whatsappSuccess) ?></span> sent of <?= number_format($totalWhatsApp) ?> attempts
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card card-enterprise shadow-sm border-0 h-100 p-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-muted small fw-semibold">Failed &amp; Pending Queue</span>
          <div class="rounded-circle bg-danger bg-opacity-10 p-2 text-danger" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
            <i class="fa-solid fa-triangle-exclamation fs-6"></i>
          </div>
        </div>
        <div class="fs-3 fw-bold text-danger mb-1"><?= number_format($totalFailed) ?></div>
        <div class="small text-muted d-flex align-items-center gap-1">
          <span class="badge bg-warning bg-opacity-20 text-dark border"><?= $activeQueue ?> in retry queue</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabbed Interface Card -->
  <div class="card card-enterprise shadow-sm">
    <div class="card-header bg-white border-bottom p-0">
      <ul class="nav nav-tabs card-header-tabs m-0 px-3 flex-nowrap overflow-x-auto" role="tablist">
        <li class="nav-item">
          <a class="nav-link <?= $activeTab === 'logs' ? 'active' : '' ?> py-3 fw-semibold small text-nowrap" href="/notifications/admin?tab=logs">
            <i class="fa-solid fa-list-ul text-primary me-1"></i> Delivery Logs &amp; Audit Trail (<?= number_format($totalLogs) ?>)
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $activeTab === 'settings' ? 'active' : '' ?> py-3 fw-semibold small text-nowrap" href="/notifications/admin?tab=settings">
            <i class="fa-solid fa-sliders text-success me-1"></i> Event Channel Matrix
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $activeTab === 'templates' ? 'active' : '' ?> py-3 fw-semibold small text-nowrap" href="/notifications/admin?tab=templates">
            <i class="fa-solid fa-envelope-open-text text-info me-1"></i> Message Templates (<?= count($templates) ?>)
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $activeTab === 'test' ? 'active' : '' ?> py-3 fw-semibold small text-nowrap" href="/notifications/admin?tab=test">
            <i class="fa-solid fa-flask text-warning me-1"></i> Test Messenger &amp; Diagnostic
          </a>
        </li>
      </ul>
    </div>

    <div class="card-body p-4">

      <!-- ========================================================================= -->
      <!-- TAB 1: DELIVERY LOGS & AUDIT TRAIL -->
      <!-- ========================================================================= -->
      <?php if ($activeTab === 'logs'): ?>
        <!-- Filter Form -->
        <form method="GET" action="/notifications/admin" class="row g-2 align-items-end mb-4 p-3 bg-light rounded-3 border">
          <input type="hidden" name="tab" value="logs">
          
          <div class="col-md-3 col-sm-6">
            <label class="form-label small fw-semibold text-muted mb-1">Search Recipient / ID</label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
              <input type="text" name="search" class="form-control" placeholder="Name, email, phone, wamid..." value="<?= e($search) ?>">
            </div>
          </div>

          <div class="col-md-2 col-sm-6">
            <label class="form-label small fw-semibold text-muted mb-1">Channel</label>
            <select name="channel" class="form-select form-select-sm">
              <option value="">All Channels</option>
              <option value="Email" <?= $channel === 'Email' ? 'selected' : '' ?>>Email</option>
              <option value="WhatsApp" <?= $channel === 'WhatsApp' ? 'selected' : '' ?>>WhatsApp</option>
              <option value="In-App" <?= $channel === 'In-App' ? 'selected' : '' ?>>In-App</option>
            </select>
          </div>

          <div class="col-md-2 col-sm-6">
            <label class="form-label small fw-semibold text-muted mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
              <option value="">All Statuses</option>
              <option value="Sent" <?= $status === 'Sent' ? 'selected' : '' ?>>Sent</option>
              <option value="Simulated" <?= $status === 'Simulated' ? 'selected' : '' ?>>Simulated (Dev)</option>
              <option value="Failed" <?= $status === 'Failed' ? 'selected' : '' ?>>Failed</option>
              <option value="Retrying" <?= $status === 'Retrying' ? 'selected' : '' ?>>Retrying</option>
              <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
            </select>
          </div>

          <div class="col-md-2 col-sm-6">
            <label class="form-label small fw-semibold text-muted mb-1">From Date</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>">
          </div>

          <div class="col-md-2 col-sm-6">
            <label class="form-label small fw-semibold text-muted mb-1">To Date</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo) ?>">
          </div>

          <div class="col-md-1 col-sm-12 d-flex gap-1">
            <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold" title="Apply Filter">
              <i class="fa-solid fa-filter"></i>
            </button>
            <a href="/notifications/admin?tab=logs" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
              <i class="fa-solid fa-rotate-left"></i>
            </a>
          </div>
        </form>

        <!-- Logs Table -->
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
            <thead class="table-light">
              <tr>
                <th style="width: 140px;">Timestamp</th>
                <th style="width: 110px;">Channel</th>
                <th>Event &amp; Subject</th>
                <th>Recipient</th>
                <th style="width: 110px;">Status</th>
                <th>Provider ID / Error</th>
                <th class="text-end" style="width: 100px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($logs)): ?>
                <tr>
                  <td colspan="7" class="text-center py-5 text-muted">
                    <i class="fa-regular fa-bell-slash fs-2 mb-2 opacity-50"></i>
                    <div class="fw-semibold">No notification delivery logs found matching the filter criteria.</div>
                    <div class="small">Notifications triggered across the Visa Portal will appear here in real time.</div>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($logs as $log): ?>
                  <tr>
                    <td class="text-nowrap text-muted" style="font-size: 0.78rem;">
                      <div><i class="fa-regular fa-clock me-1"></i><?= format_datetime($log['created_at']) ?></div>
                    </td>
                    <td>
                      <?php if ($log['channel'] === 'WhatsApp'): ?>
                        <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 px-2 py-1">
                          <i class="fa-brands fa-whatsapp me-1"></i> WhatsApp
                        </span>
                      <?php elseif ($log['channel'] === 'Email'): ?>
                        <span class="badge bg-primary bg-opacity-15 text-primary border border-primary border-opacity-25 px-2 py-1">
                          <i class="fa-solid fa-envelope me-1"></i> Email
                        </span>
                      <?php else: ?>
                        <span class="badge bg-secondary bg-opacity-15 text-dark border px-2 py-1">
                          <i class="fa-solid fa-bell me-1"></i> In-App
                        </span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="fw-semibold text-dark"><?= e($log['subject'] ?: $log['event_type']) ?></div>
                      <div class="small text-muted text-truncate" style="max-width: 280px;"><?= e($log['content_preview'] ?: 'Template: ' . $log['template_name']) ?></div>
                    </td>
                    <td>
                      <div class="fw-medium text-dark"><?= e($log['recipient_name'] ?: 'Customer') ?></div>
                      <div class="small text-muted font-monospace" style="font-size: 0.75rem;">
                        <?= e($log['recipient_phone'] ?: ($log['recipient_email'] ?: 'Portal User #' . $log['recipient_id'])) ?>
                      </div>
                    </td>
                    <td>
                      <?php if ($log['status'] === 'Sent'): ?>
                        <span class="badge bg-success px-2 py-1"><i class="fa-solid fa-check me-1"></i> Sent</span>
                      <?php elseif ($log['status'] === 'Simulated'): ?>
                        <span class="badge bg-info text-white px-2 py-1" title="Dispatched in local simulation mode"><i class="fa-solid fa-vial me-1"></i> Simulated</span>
                      <?php elseif ($log['status'] === 'Failed'): ?>
                        <span class="badge bg-danger px-2 py-1"><i class="fa-solid fa-xmark me-1"></i> Failed</span>
                      <?php elseif ($log['status'] === 'Retrying'): ?>
                        <span class="badge bg-warning text-dark px-2 py-1"><i class="fa-solid fa-rotate me-1"></i> Retry #<?= (int)$log['retry_count'] ?></span>
                      <?php else: ?>
                        <span class="badge bg-secondary px-2 py-1"><?= e($log['status']) ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (!empty($log['provider_message_id'])): ?>
                        <div class="font-monospace small text-primary text-truncate" style="max-width: 180px;" title="<?= e($log['provider_message_id']) ?>">
                          <i class="fa-solid fa-hashtag small me-1"></i><?= e($log['provider_message_id']) ?>
                        </div>
                      <?php endif; ?>
                      <?php if (!empty($log['error_message'])): ?>
                        <div class="small text-danger text-truncate" style="max-width: 220px;" title="<?= e($log['error_message']) ?>">
                          <i class="fa-solid fa-circle-exclamation me-1"></i><?= e($log['error_message']) ?>
                        </div>
                      <?php endif; ?>
                      <?php if (empty($log['provider_message_id']) && empty($log['error_message'])): ?>
                        <span class="text-muted small">—</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end">
                      <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                onclick='viewLogPayload(<?= json_encode($log) ?>)' title="Inspect Details">
                          <i class="fa-solid fa-eye"></i>
                        </button>
                        <?php if ($log['status'] === 'Failed'): ?>
                          <form action="/notifications/retry" method="POST" class="d-inline" onsubmit="return confirm('Retry sending this notification?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="log_id" value="<?= $log['id'] ?>">
                            <button type="submit" class="btn btn-outline-primary btn-sm" title="Retry Message">
                              <i class="fa-solid fa-rotate-right"></i>
                            </button>
                          </form>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
          <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
            <div class="small text-muted">
              Showing page <strong><?= $page ?></strong> of <strong><?= $totalPages ?></strong> (<?= number_format($totalLogs) ?> total logs)
            </div>
            <nav>
              <ul class="pagination pagination-sm mb-0">
                <?php if ($page > 1): ?>
                  <li class="page-item"><a class="page-link" href="/notifications/admin?tab=logs&page=<?= $page - 1 ?>&channel=<?= urlencode($channel) ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>">&laquo; Previous</a></li>
                <?php endif; ?>
                <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                  <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="/notifications/admin?tab=logs&page=<?= $p ?>&channel=<?= urlencode($channel) ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>"><?= $p ?></a>
                  </li>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                  <li class="page-item"><a class="page-link" href="/notifications/admin?tab=logs&page=<?= $page + 1 ?>&channel=<?= urlencode($channel) ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>">Next &raquo;</a></li>
                <?php endif; ?>
              </ul>
            </nav>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <!-- ========================================================================= -->
      <!-- TAB 2: EVENT CHANNEL MATRIX & SETTINGS -->
      <!-- ========================================================================= -->
      <?php if ($activeTab === 'settings'): ?>
        <form action="/notifications/settings" method="POST">
          <?= csrf_field() ?>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h5 class="fw-bold text-dark mb-0">Automated Notification Channel Rules</h5>
              <p class="text-muted small mb-0">Activate or deactivate specific channels (Email, Meta WhatsApp, In-App) for each application lifecycle event.</p>
            </div>
            <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
              <i class="fa-solid fa-floppy-disk me-1"></i> Save Channel Matrix
            </button>
          </div>

          <?php foreach ($groupedSettings as $category => $categorySettings): ?>
            <div class="card mb-4 border shadow-sm">
              <div class="card-header bg-light py-2 px-3 fw-bold text-dark d-flex align-items-center justify-content-between">
                <span><i class="fa-solid fa-folder-tree text-primary me-2"></i><?= e($category) ?> Events</span>
                <span class="badge bg-secondary small"><?= count($categorySettings) ?> events</span>
              </div>
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                  <thead class="table-light">
                    <tr>
                      <th>Event Trigger</th>
                      <th class="text-center" style="width: 110px;"><i class="fa-solid fa-envelope text-primary me-1"></i> Email</th>
                      <th class="text-center" style="width: 120px;"><i class="fa-brands fa-whatsapp text-success me-1"></i> WhatsApp</th>
                      <th class="text-center" style="width: 110px;"><i class="fa-solid fa-bell text-secondary me-1"></i> In-App</th>
                      <th class="text-center" style="width: 120px;"><i class="fa-solid fa-user me-1"></i> Applicant</th>
                      <th class="text-center" style="width: 110px;"><i class="fa-solid fa-user-tie me-1"></i> Staff</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($categorySettings as $s): ?>
                      <tr>
                        <td>
                          <div class="fw-semibold text-dark"><?= e($s['title']) ?></div>
                          <div class="text-muted font-monospace small" style="font-size: 0.72rem;"><?= e($s['event_type']) ?></div>
                          <div class="text-muted small" style="font-size: 0.75rem;"><?= e($s['description']) ?></div>
                        </td>
                        <td class="text-center">
                          <div class="form-check form-switch d-inline-block">
                            <input class="form-check-input" type="checkbox" name="settings[<?= e($s['event_type']) ?>][email]" value="1" <?= !empty($s['email_enabled']) ? 'checked' : '' ?>>
                          </div>
                        </td>
                        <td class="text-center">
                          <div class="form-check form-switch d-inline-block">
                            <input class="form-check-input" type="checkbox" name="settings[<?= e($s['event_type']) ?>][whatsapp]" value="1" <?= !empty($s['whatsapp_enabled']) ? 'checked' : '' ?>>
                          </div>
                        </td>
                        <td class="text-center">
                          <div class="form-check form-switch d-inline-block">
                            <input class="form-check-input" type="checkbox" name="settings[<?= e($s['event_type']) ?>][in_app]" value="1" <?= !empty($s['in_app_enabled']) ? 'checked' : '' ?>>
                          </div>
                        </td>
                        <td class="text-center">
                          <div class="form-check form-switch d-inline-block">
                            <input class="form-check-input" type="checkbox" name="settings[<?= e($s['event_type']) ?>][applicant]" value="1" <?= !empty($s['applicant_enabled']) ? 'checked' : '' ?>>
                          </div>
                        </td>
                        <td class="text-center">
                          <div class="form-check form-switch d-inline-block">
                            <input class="form-check-input" type="checkbox" name="settings[<?= e($s['event_type']) ?>][staff]" value="1" <?= !empty($s['staff_enabled']) ? 'checked' : '' ?>>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          <?php endforeach; ?>

          <div class="text-end mt-3">
            <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
              <i class="fa-solid fa-floppy-disk me-1"></i> Save All Notification Settings
            </button>
          </div>
        </form>
      <?php endif; ?>

      <!-- ========================================================================= -->
      <!-- TAB 3: MESSAGE TEMPLATES -->
      <!-- ========================================================================= -->
      <?php if ($activeTab === 'templates'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="fw-bold text-dark mb-0">Email &amp; WhatsApp Message Templates</h5>
            <p class="text-muted small mb-0">Customize subject lines, responsive email layouts, and Meta approved WhatsApp template parameters.</p>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
            <thead class="table-light">
              <tr>
                <th style="width: 110px;">Channel</th>
                <th>Event &amp; Template Name</th>
                <th>Subject / Meta Template ID</th>
                <th>Placeholders / Variables</th>
                <th class="text-end" style="width: 100px;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($templates as $t): ?>
                <tr>
                  <td>
                    <?php if ($t['channel'] === 'WhatsApp'): ?>
                      <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 px-2 py-1">
                        <i class="fa-brands fa-whatsapp me-1"></i> WhatsApp
                      </span>
                    <?php else: ?>
                      <span class="badge bg-primary bg-opacity-15 text-primary border border-primary border-opacity-25 px-2 py-1">
                        <i class="fa-solid fa-envelope me-1"></i> Email
                      </span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark"><?= e($t['template_name']) ?></div>
                    <div class="small text-muted font-monospace" style="font-size: 0.72rem;"><?= e($t['event_type']) ?></div>
                  </td>
                  <td>
                    <div class="fw-medium text-dark"><?= e($t['subject'] ?: ($t['provider_template_id'] ? 'Meta Template: ' . $t['provider_template_id'] : '—')) ?></div>
                  </td>
                  <td>
                    <div class="small text-muted font-monospace" style="font-size: 0.72rem;"><?= e($t['variables'] ?: '—') ?></div>
                  </td>
                  <td class="text-end">
                    <button type="button" class="btn btn-outline-primary btn-sm" 
                            onclick='openEditTemplateModal(<?= json_encode($t) ?>)'>
                      <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <!-- ========================================================================= -->
      <!-- TAB 4: TEST MESSENGER & DIAGNOSTICS -->
      <!-- ========================================================================= -->
      <?php if ($activeTab === 'test'): ?>
        <div class="row g-4">
          <div class="col-lg-7">
            <div class="card border shadow-sm">
              <div class="card-header bg-light py-3">
                <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-vial text-primary me-2"></i> Safe Admin Test Message Sender</h6>
              </div>
              <div class="card-body p-4">
                <form action="/notifications/test" method="POST">
                  <?= csrf_field() ?>

                  <div class="mb-3">
                    <label class="form-label small fw-semibold">Delivery Channel</label>
                    <select name="channel" id="testChannelSelect" class="form-select" onchange="toggleTestFields()">
                      <option value="Email">Email (SMTP / Mail Transport)</option>
                      <option value="WhatsApp">WhatsApp (Meta WhatsApp Business API)</option>
                    </select>
                  </div>

                  <div class="mb-3">
                    <label class="form-label small fw-semibold" id="testRecipientLabel">Recipient Email Address</label>
                    <input type="text" name="recipient" class="form-control" id="testRecipientInput" placeholder="e.g. customer@example.com or 0771234567" required>
                    <div class="form-text small" id="testRecipientHint">Enter destination email. Sri Lankan and international numbers are normalized automatically.</div>
                  </div>

                  <div class="mb-3" id="testSubjectGroup">
                    <label class="form-label small fw-semibold">Email Subject</label>
                    <input type="text" name="subject" class="form-control" value="[TEST] Verification Notification from VISA TRACK">
                  </div>

                  <div class="mb-3">
                    <label class="form-label small fw-semibold">Test Message Content</label>
                    <textarea name="message" class="form-control" rows="4">This is a live test notification dispatched from the VISA TRACK Operations Portal to verify multi-channel real-time delivery.</textarea>
                  </div>

                  <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                      <i class="fa-solid fa-paper-plane me-1"></i> Send Test Notification
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <div class="col-lg-5">
            <div class="card border shadow-sm h-100">
              <div class="card-header bg-light py-3">
                <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-circle-nodes text-success me-2"></i> Environment Configuration Status</h6>
              </div>
              <div class="card-body p-4">
                <div class="mb-3">
                  <div class="text-muted small fw-semibold">Active Mode</div>
                  <span class="badge bg-<?= \App\Config\Env::get('NOTIFICATION_ENV') === 'production' ? 'success' : 'warning text-dark' ?> px-2 py-1">
                    <?= strtoupper((string)\App\Config\Env::get('NOTIFICATION_ENV', 'development')) ?>
                  </span>
                </div>

                <div class="mb-3 pb-3 border-bottom">
                  <div class="text-muted small fw-semibold">Email Provider</div>
                  <div class="fw-medium text-dark"><?= strtoupper((string)\App\Config\Env::get('EMAIL_PROVIDER', 'smtp')) ?> (Host: <?= e(\App\Config\Env::get('SMTP_HOST', 'smtp.gmail.com')) ?>:<?= e(\App\Config\Env::get('SMTP_PORT', 587)) ?>)</div>
                  <div class="small text-muted">From: <?= e(\App\Config\Env::get('EMAIL_FROM', 'notifications@mstravelhub.com')) ?></div>
                </div>

                <div class="mb-3 pb-3 border-bottom">
                  <div class="text-muted small fw-semibold">Meta WhatsApp Cloud API</div>
                  <div class="fw-medium text-dark">API Version: <?= e(\App\Config\Env::get('WHATSAPP_API_VERSION', 'v20.0')) ?></div>
                  <div class="small text-muted">Phone Number ID: <?= !empty(\App\Config\Env::get('WHATSAPP_PHONE_NUMBER_ID')) ? 'Configured (***)' : 'Not Set (Simulation Mode)' ?></div>
                </div>

                <div>
                  <div class="text-muted small fw-semibold">Queue &amp; Retry Policies</div>
                  <div class="small text-muted">Process Mode: <strong><?= strtoupper((string)\App\Config\Env::get('NOTIFICATION_PROCESS_MODE', 'sync')) ?></strong></div>
                  <div class="small text-muted">Max Retries: <strong><?= (int)\App\Config\Env::get('NOTIFICATION_MAX_RETRIES', 3) ?> Attempts</strong></div>
                  <div class="small text-muted">Backoff Intervals: <strong>2m &rarr; 5m &rarr; 15m</strong></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: QUICK TEST NOTIFICATION -->
<!-- ========================================================================= -->
<div class="modal fade" id="testNotificationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <form action="/notifications/test" method="POST">
        <?= csrf_field() ?>
        <div class="modal-header bg-light">
          <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-paper-plane text-primary me-2"></i> Send Quick Test Notification</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Channel</label>
            <select name="channel" class="form-select" id="quickTestChannel" onchange="toggleQuickTestFields()">
              <option value="Email">Email (SMTP)</option>
              <option value="WhatsApp">WhatsApp (Meta Cloud API)</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold" id="quickRecipientLabel">Recipient Email</label>
            <input type="text" name="recipient" class="form-control" id="quickRecipientInput" placeholder="recipient@example.com" required>
          </div>
          <div class="mb-3" id="quickSubjectGroup">
            <label class="form-label small fw-semibold">Subject</label>
            <input type="text" name="subject" class="form-control" value="[TEST] Real-Time Visa Portal Alert">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Message</label>
            <textarea name="message" class="form-control" rows="3">This is a test notification from VISA TRACK.</textarea>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold">Send Test Now</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: EDIT TEMPLATE -->
<!-- ========================================================================= -->
<div class="modal fade" id="editTemplateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <form action="/notifications/templates/update" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="editTmplId">
        <div class="modal-header bg-light">
          <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-pen-to-square text-primary me-2"></i> Edit Notification Template</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Template Name</label>
              <input type="text" class="form-control bg-light" id="editTmplName" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Event Trigger</label>
              <input type="text" class="form-control bg-light" id="editTmplEvent" readonly>
            </div>
          </div>

          <div class="mb-3" id="editSubjectContainer">
            <label class="form-label small fw-semibold">Email Subject</label>
            <input type="text" name="subject" id="editTmplSubject" class="form-control">
          </div>

          <div class="mb-3" id="editMetaContainer">
            <label class="form-label small fw-semibold">Meta WhatsApp Template ID / Name</label>
            <input type="text" name="provider_template_id" id="editTmplMetaId" class="form-control" placeholder="e.g. visa_status_update">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Content / Body</label>
            <textarea name="content" id="editTmplContent" class="form-control font-monospace" rows="6"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Available Variable Tokens (Comma Separated)</label>
            <input type="text" name="variables" id="editTmplVariables" class="form-control small">
            <div class="form-text small">Example tokens: <code>{{applicantName}}</code>, <code>{{applicationNumber}}</code>, <code>{{countryName}}</code>, <code>{{status}}</code>, <code>{{interviewDate}}</code>, <code>{{companyName}}</code>, <code>{{actionUrl}}</code></div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary fw-semibold"><i class="fa-solid fa-floppy-disk me-1"></i> Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: LOG INSPECTION -->
<!-- ========================================================================= -->
<div class="modal fade" id="logInspectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-light">
        <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-magnifying-glass-chart text-primary me-2"></i> Delivery Log Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <table class="table table-bordered table-sm mb-3">
          <tbody>
            <tr><th class="bg-light" style="width: 25%;">Log ID</th><td id="inspectLogId"></td></tr>
            <tr><th class="bg-light">Event Type</th><td id="inspectEventType"></td></tr>
            <tr><th class="bg-light">Channel &amp; Status</th><td id="inspectChannelStatus"></td></tr>
            <tr><th class="bg-light">Recipient</th><td id="inspectRecipient"></td></tr>
            <tr><th class="bg-light">Provider Message ID</th><td id="inspectProviderId" class="font-monospace text-primary"></td></tr>
            <tr><th class="bg-light">Idempotency Key</th><td id="inspectIdemp" class="font-monospace small"></td></tr>
            <tr><th class="bg-light">Timestamp</th><td id="inspectTimestamp"></td></tr>
          </tbody>
        </table>

        <div class="mb-3">
          <label class="fw-semibold small text-muted">Request Payload</label>
          <pre class="bg-dark text-light p-3 rounded small" id="inspectRequestPayload" style="max-height: 200px; overflow-y: auto;"></pre>
        </div>

        <div class="mb-0">
          <label class="fw-semibold small text-muted">Provider Response / Error Details</label>
          <pre class="bg-light p-3 rounded border small" id="inspectResponsePayload" style="max-height: 200px; overflow-y: auto;"></pre>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
function toggleTestFields() {
  const channel = document.getElementById('testChannelSelect').value;
  const label = document.getElementById('testRecipientLabel');
  const input = document.getElementById('testRecipientInput');
  const hint = document.getElementById('testRecipientHint');
  const subjGroup = document.getElementById('testSubjectGroup');

  if (channel === 'WhatsApp') {
    label.innerText = 'Recipient Mobile / WhatsApp Number';
    input.placeholder = 'e.g. 0771234567 or +94771234567 or +971501234567';
    hint.innerText = 'Sri Lankan and international numbers are automatically normalized to E.164 international format.';
    subjGroup.style.display = 'none';
  } else {
    label.innerText = 'Recipient Email Address';
    input.placeholder = 'e.g. applicant@example.com';
    hint.innerText = 'Enter the target email to receive the formatted responsive HTML template.';
    subjGroup.style.display = 'block';
  }
}

function toggleQuickTestFields() {
  const channel = document.getElementById('quickTestChannel').value;
  const label = document.getElementById('quickRecipientLabel');
  const input = document.getElementById('quickRecipientInput');
  const subjGroup = document.getElementById('quickSubjectGroup');

  if (channel === 'WhatsApp') {
    label.innerText = 'Recipient WhatsApp Number';
    input.placeholder = 'e.g. 0771234567 or +94771234567';
    subjGroup.style.display = 'none';
  } else {
    label.innerText = 'Recipient Email';
    input.placeholder = 'recipient@example.com';
    subjGroup.style.display = 'block';
  }
}

function openEditTemplateModal(tmpl) {
  document.getElementById('editTmplId').value = tmpl.id;
  document.getElementById('editTmplName').value = tmpl.template_name;
  document.getElementById('editTmplEvent').value = tmpl.event_type;
  document.getElementById('editTmplSubject').value = tmpl.subject || '';
  document.getElementById('editTmplMetaId').value = tmpl.provider_template_id || '';
  document.getElementById('editTmplContent').value = tmpl.content || '';
  document.getElementById('editTmplVariables').value = tmpl.variables || '';

  const subjContainer = document.getElementById('editSubjectContainer');
  const metaContainer = document.getElementById('editMetaContainer');

  if (tmpl.channel === 'WhatsApp') {
    subjContainer.style.display = 'none';
    metaContainer.style.display = 'block';
  } else {
    subjContainer.style.display = 'block';
    metaContainer.style.display = 'none';
  }

  const modal = new bootstrap.Modal(document.getElementById('editTemplateModal'));
  modal.show();
}

function viewLogPayload(log) {
  document.getElementById('inspectLogId').innerText = '#' + log.id;
  document.getElementById('inspectEventType').innerText = log.event_type;
  document.getElementById('inspectChannelStatus').innerHTML = `<strong>${log.channel}</strong> — <span class="badge bg-${log.status === 'Sent' ? 'success' : (log.status === 'Failed' ? 'danger' : 'secondary')}">${log.status}</span>`;
  document.getElementById('inspectRecipient').innerText = (log.recipient_name || 'Customer') + ' (' + (log.recipient_phone || log.recipient_email || 'ID #' + log.recipient_id) + ')';
  document.getElementById('inspectProviderId').innerText = log.provider_message_id || '—';
  document.getElementById('inspectIdemp').innerText = log.idempotency_key || '—';
  document.getElementById('inspectTimestamp').innerText = log.created_at;

  try {
    const reqObj = JSON.parse(log.request_payload || '{}');
    document.getElementById('inspectRequestPayload').innerText = JSON.stringify(reqObj, null, 2);
  } catch (e) {
    document.getElementById('inspectRequestPayload').innerText = log.request_payload || 'None';
  }

  try {
    const resObj = JSON.parse(log.response_payload || '{}');
    let resText = JSON.stringify(resObj, null, 2);
    if (log.error_message) {
      resText = 'ERROR: ' + log.error_message + '\n\n' + resText;
    }
    document.getElementById('inspectResponsePayload').innerText = resText || (log.error_message || 'None');
  } catch (e) {
    document.getElementById('inspectResponsePayload').innerText = log.error_message || log.response_payload || 'None';
  }

  const modal = new bootstrap.Modal(document.getElementById('logInspectModal'));
  modal.show();
}
</script>

<?php
require_once dirname(__DIR__) . '/layouts/footer.php';
