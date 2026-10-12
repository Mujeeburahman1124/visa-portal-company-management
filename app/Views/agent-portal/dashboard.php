<?php require_once __DIR__ . '/navbar.php'; ?>

<!-- ─── BENTO HEADER STRIP ────────────────────────────────────────────────── -->
<div class="agent-header-strip">
  <div>
    <h1 class="agent-header-title">Agent Partner Dashboard</h1>
    <p class="agent-header-subtitle">
      Welcome back, <strong><?= e($agentAuth['company_name'] ?? 'Agent Partner') ?></strong> &bull; Monitor client visa files, processing stages, and balances.
    </p>
  </div>
  <div class="d-flex align-items-center gap-2 flex-wrap">
    <a href="/agent/create-application" class="agent-btn-primary">
      <i class="fa-solid fa-circle-plus"></i>
      <span>New Application</span>
    </a>
    <a href="/agent/applications" class="agent-btn-outline">
      <i class="fa-solid fa-list-check"></i>
      <span>All Applications</span>
    </a>
  </div>
</div>

<!-- ─── TOP 4 BENTO METRIC STAT CARDS ────────────────────────────────────── -->
<div class="row g-3 g-md-4 mb-4">
  <!-- 1. Total Applications (Featured Brand Red) -->
  <div class="col-12 col-sm-6 col-xl-3">
    <a href="/agent/applications" class="agent-stat-card featured-card">
      <div class="agent-stat-top">
        <span class="agent-stat-label">Total Applications</span>
        <div class="agent-stat-icon-wrap">
          <i class="fa-solid fa-arrow-up-right"></i>
        </div>
      </div>
      <div class="agent-stat-value"><?= number_format((int)$totalApps) ?></div>
      <div class="agent-stat-bottom">
        <span class="agent-stat-badge-pill">
          <i class="fa-solid fa-folder-open"></i> Portfolio
        </span>
        <span>All submitted files</span>
      </div>
    </a>
  </div>

  <!-- 2. In Process -->
  <div class="col-12 col-sm-6 col-xl-3">
    <a href="/agent/applications?status=In+Process" class="agent-stat-card">
      <div class="agent-stat-top">
        <span class="agent-stat-label">Under Processing</span>
        <div class="agent-stat-icon-wrap" style="background:#FFFBEB;color:#D97706;">
          <i class="fa-solid fa-hourglass-half"></i>
        </div>
      </div>
      <div class="agent-stat-value" style="color:#D97706;"><?= number_format((int)$activeApps) ?></div>
      <div class="agent-stat-bottom">
        <span class="agent-stat-badge-pill" style="background:#FFFBEB;color:#D97706;">
          <i class="fa-solid fa-clock-rotate-left"></i> Live Ops
        </span>
        <span>At Embassy / Desk</span>
      </div>
    </a>
  </div>

  <!-- 3. Approved Visas -->
  <div class="col-12 col-sm-6 col-xl-3">
    <a href="/agent/applications?status=Approved" class="agent-stat-card">
      <div class="agent-stat-top">
        <span class="agent-stat-label">Approved Visas</span>
        <div class="agent-stat-icon-wrap" style="background:#ECFDF5;color:#059669;">
          <i class="fa-solid fa-circle-check"></i>
        </div>
      </div>
      <div class="agent-stat-value" style="color:#059669;"><?= number_format((int)$approvedApps) ?></div>
      <div class="agent-stat-bottom">
        <span class="agent-stat-badge-pill" style="background:#ECFDF5;color:#059669;">
          <i class="fa-solid fa-shield-check"></i> Success
        </span>
        <span>Ready for traveler</span>
      </div>
    </a>
  </div>

  <!-- 4. Account Balance -->
  <div class="col-12 col-sm-6 col-xl-3">
    <a href="/agent/profile" class="agent-stat-card">
      <div class="agent-stat-top">
        <span class="agent-stat-label">Account Balance</span>
        <div class="agent-stat-icon-wrap" style="background:#FFF1F3;color:var(--agent-primary);">
          <i class="fa-solid fa-wallet"></i>
        </div>
      </div>
      <div class="agent-stat-value" style="color:var(--agent-primary);"><?= format_currency((float)($agentRow['current_balance'] ?? 0)) ?></div>
      <div class="agent-stat-bottom">
        <span class="agent-stat-badge-pill" style="background:#F1F5F9;color:#475569;">
          <i class="fa-solid fa-credit-card"></i> Limit
        </span>
        <span><?= format_currency((float)($agentRow['credit_limit'] ?? 0)) ?> available</span>
      </div>
    </a>
  </div>
</div>

<!-- ─── MAIN BENTO GRID ───────────────────────────────────────────────────── -->
<div class="row g-4">
  <!-- Left Column: Recent Submissions -->
  <div class="col-lg-8">
    <div class="agent-bento-card">
      <div class="agent-bento-card-header">
        <h5 class="agent-bento-card-title">
          <span class="card-title-icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
          <span>Recent Submissions</span>
        </h5>
        <a href="/agent/applications" class="agent-btn-outline py-1.5 px-3" style="font-size:0.82rem;">
          <span>View All</span>
          <i class="fa-solid fa-arrow-right fs-xs"></i>
        </a>
      </div>

      <?php if (empty($recentApps)): ?>
        <div class="agent-empty-box">
          <div class="agent-empty-icon-circle">
            <i class="fa-solid fa-folder-open"></i>
          </div>
          <h4 class="agent-empty-title">No Applications Submitted Yet</h4>
          <p class="agent-empty-desc">
            Get started by registering client information and submitting your first visa application.
          </p>
          <a href="/agent/create-application" class="agent-btn-primary">
            <i class="fa-solid fa-circle-plus"></i>
            <span>Submit First Application</span>
          </a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="agent-table">
            <thead>
              <tr>
                <th>Application #</th>
                <th>Client Name</th>
                <th>Destination &amp; Visa</th>
                <th>Current Stage</th>
                <th>Status</th>
                <th>Submitted</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentApps as $app): ?>
                <?php
                  $st = $app['status'] ?? 'Pending';
                  $badgeClass = 'status-pending';
                  if ($st === 'Approved') $badgeClass = 'status-approved';
                  elseif ($st === 'In Process' || $st === 'Under Processing') $badgeClass = 'status-processing';
                  elseif ($st === 'Action Required') $badgeClass = 'status-action';
                  elseif ($st === 'Rejected') $badgeClass = 'status-rejected';
                ?>
                <tr>
                  <td>
                    <span class="fw-bold" style="color:var(--agent-primary); font-family:'SFMono-Regular', Menlo, Monaco, Consolas, monospace;">
                      <?= e($app['application_number']) ?>
                    </span>
                  </td>
                  <td>
                    <div class="fw-bold text-dark"><?= e($app['customer_name']) ?></div>
                    <div class="text-muted small" style="font-size:0.75rem;"><?= e($app['customer_code'] ?? '') ?></div>
                  </td>
                  <td>
                    <div class="d-flex align-items-center gap-1.5">
                      <span class="fs-6"><?= $app['flag_emoji'] ?? '🏳️' ?></span>
                      <span class="fw-semibold text-dark"><?= e($app['country_name'] ?? '') ?></span>
                    </div>
                    <div class="text-muted small" style="font-size:0.75rem;"><?= e($app['service_name'] ?? '') ?></div>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border px-2 py-1 font-monospace" style="font-size:0.75rem;">
                      <?= e($app['current_stage'] ?? 'Pending') ?>
                    </span>
                  </td>
                  <td>
                    <span class="agent-status-badge <?= $badgeClass ?>">
                      <i class="fa-solid fa-circle fs-xs" style="font-size: 0.45rem;"></i>
                      <?= e($st) ?>
                    </span>
                  </td>
                  <td class="text-muted small">
                    <?= date('M d, Y', strtotime($app['created_at'])) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right Column: Quick Actions & Financial Transactions -->
  <div class="col-lg-4">
    <!-- Quick Actions Card -->
    <div class="agent-bento-card mb-4">
      <div class="agent-bento-card-header">
        <h5 class="agent-bento-card-title">
          <span class="card-title-icon"><i class="fa-solid fa-bolt"></i></span>
          <span>Quick Actions</span>
        </h5>
      </div>

      <!-- Action 1: Featured Primary Submit -->
      <a href="/agent/create-application" class="agent-action-tile tile-featured">
        <div class="tile-icon-box">
          <i class="fa-solid fa-file-circle-plus"></i>
        </div>
        <div class="tile-content">
          <h6 class="tile-title">Submit Application</h6>
          <div class="tile-desc">Register client &amp; submit visa file</div>
        </div>
        <i class="fa-solid fa-chevron-right tile-arrow"></i>
      </a>

      <!-- Action 2: Track Applications -->
      <a href="/agent/applications" class="agent-action-tile">
        <div class="tile-icon-box">
          <i class="fa-solid fa-list-check"></i>
        </div>
        <div class="tile-content">
          <h6 class="tile-title">Track Applications</h6>
          <div class="tile-desc">Monitor stages &amp; client status</div>
        </div>
        <i class="fa-solid fa-chevron-right tile-arrow"></i>
      </a>

      <!-- Action 3: Statement & Payments -->
      <a href="/agent/profile" class="agent-action-tile">
        <div class="tile-icon-box">
          <i class="fa-solid fa-receipt"></i>
        </div>
        <div class="tile-content">
          <h6 class="tile-title">Statements &amp; Payments</h6>
          <div class="tile-desc">Review ledger, credits &amp; profile</div>
        </div>
        <i class="fa-solid fa-chevron-right tile-arrow"></i>
      </a>
    </div>

    <!-- Recent Payments & Credits -->
    <div class="agent-bento-card">
      <div class="agent-bento-card-header">
        <h5 class="agent-bento-card-title">
          <span class="card-title-icon"><i class="fa-solid fa-money-bill-transfer"></i></span>
          <span>Recent Payments &amp; Credits</span>
        </h5>
      </div>

      <?php if (empty($recentPayments)): ?>
        <div class="text-center py-4 text-muted">
          <i class="fa-solid fa-receipt fa-2x mb-2 opacity-25"></i>
          <p class="small mb-0">No payment transactions recorded.</p>
        </div>
      <?php else: ?>
        <div>
          <?php foreach ($recentPayments as $p): ?>
            <div class="agent-pay-item">
              <div>
                <div class="agent-pay-ref"><?= e($p['payment_reference']) ?></div>
                <div class="agent-pay-meta">
                  <?= date('M d, Y', strtotime($p['payment_date'] ?? $p['created_at'])) ?> &bull; <?= e($p['payment_method'] ?? 'Bank') ?>
                </div>
              </div>
              <div class="agent-pay-amount">
                +<?= format_currency((float)$p['amount']) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

</div><!-- /.container-fluid -->

<footer class="text-center py-4 text-muted small" style="border-top: 1px solid var(--agent-border-subtle);">
  &copy; <?= date('Y') ?> <strong style="color:var(--agent-text-main);">MS TRAVEL HUB</strong> &bull; Agent Partner Portal &bull; Enterprise Visa Solutions
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
