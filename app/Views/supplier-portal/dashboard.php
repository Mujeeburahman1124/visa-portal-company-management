<?php require_once __DIR__ . '/navbar.php'; ?>

<!-- Scoped Data Notice: Supplier Portal is restricted — no customer PII or internal margins shown -->
<div class="scoped-notice">
  <i class="fa-solid fa-shield-check me-2"></i>
  <strong>Scoped View:</strong> This dashboard shows only your assigned applications, processing status, and payable settlements. Customer private data and internal cost margins are not visible.
</div>

<!-- Supplier Hero Banner -->
<div class="supplier-hero mb-4" style="background:linear-gradient(135deg,#0a1628 0%,#0d2048 60%,#063c5a 100%);border-radius:16px;padding:1.75rem 2rem;color:#fff;position:relative;overflow:hidden;border:1px solid rgba(6,182,212,0.2);">
  <div style="position:absolute;top:-40px;right:-40px;width:220px;height:220px;background:radial-gradient(circle,rgba(6,182,212,.18) 0%,transparent 70%);pointer-events:none;"></div>
  <div class="row align-items-center" style="position:relative;z-index:2;">
    <div class="col-md-8">
      <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:#7bafd4;margin-bottom:.35rem;">
        <i class="fa-solid fa-building me-1"></i>Supplier Portal
      </div>
      <h4 class="fw-bold mb-1" style="color:#fff;"><?= e($supplierAuth['company_name'] ?? 'Your Company') ?></h4>
      <div style="font-size:.85rem;color:#94a3b8;">
        Supplier Code: <strong style="color:#38bdf8;"><?= e($supplierAuth['supplier_code'] ?? 'N/A') ?></strong>
        &bull; Type: <?= e($supplierAuth['company_type'] ?? 'Partner Agency') ?>
        &bull; Session: <?= date('d M Y') ?>
      </div>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
      <div class="d-inline-flex gap-2 flex-wrap">
        <a href="/supplier/applications" class="btn btn-sm" style="background:linear-gradient(135deg,#22c55e,#0891b2);color:#fff;font-weight:700;border:none;border-radius:8px;padding:.45rem 1rem;">
          <i class="fa-solid fa-tasks me-1"></i>My Applications
        </a>
        <a href="/supplier/payments" class="btn btn-sm btn-outline-light" style="border-radius:8px;">
          <i class="fa-solid fa-receipt me-1"></i>Settlements
        </a>
      </div>
    </div>
  </div>
</div>

<!-- KPI Stats: Only scoped metrics -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-sup">
      <div class="stat-sup-icon" style="background:#eff6ff;color:#2563eb;">
        <i class="fa-solid fa-file-invoice"></i>
      </div>
      <div>
        <div class="text-muted small fw-semibold" style="font-size:.72rem;">Total Assigned</div>
        <h4 class="fw-bold mb-0 text-dark"><?= (int)$totalApps ?></h4>
        <div style="font-size:.7rem;color:#64748b;">Applications</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-sup">
      <div class="stat-sup-icon" style="background:#fffbeb;color:#d97706;">
        <i class="fa-solid fa-hourglass-half"></i>
      </div>
      <div>
        <div class="text-muted small fw-semibold" style="font-size:.72rem;">In Processing</div>
        <h4 class="fw-bold mb-0 text-warning"><?= (int)$inProcess ?></h4>
        <div style="font-size:.7rem;color:#64748b;">Active cases</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-sup">
      <div class="stat-sup-icon" style="background:#f0fdf4;color:#16a34a;">
        <i class="fa-solid fa-circle-check"></i>
      </div>
      <div>
        <div class="text-muted small fw-semibold" style="font-size:.72rem;">Approved Visas</div>
        <h4 class="fw-bold mb-0 text-success"><?= (int)$approved ?></h4>
        <div style="font-size:.7rem;color:#64748b;">This period</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-sup">
      <div class="stat-sup-icon" style="background:#fef2f2;color:#ef4444;">
        <i class="fa-solid fa-hand-holding-dollar"></i>
      </div>
      <div>
        <div class="text-muted small fw-semibold" style="font-size:.72rem;">Pending Payables</div>
        <h4 class="fw-bold mb-0 text-danger"><?= format_currency((float)$pending_pay) ?></h4>
        <div style="font-size:.7rem;color:#64748b;">Awaiting settlement</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Applications Workload Table -->
  <div class="col-lg-8">
    <div class="supplier-card">
      <div class="d-flex align-items-center justify-content-between mb-3 pb-2" style="border-bottom:2px solid #dce8f5;">
        <h5 class="fw-bold mb-0 text-dark">
          <i class="fa-solid fa-clock-rotate-left me-2" style="color:#2563eb;"></i>Active Processing Workload
        </h5>
        <a href="/supplier/applications" class="btn btn-sm btn-outline-primary fw-semibold">View All</a>
      </div>
      <?php if (empty($recentApps)): ?>
        <div class="text-center py-5 text-muted">
          <i class="fa-solid fa-folder-open fa-2x mb-3 opacity-25"></i>
          <p class="mb-0">No visa applications currently assigned to your supplier queue.</p>
          <div class="mt-2 small">Contact the admin to get assigned applications.</div>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size:.87rem;">
            <thead>
              <tr style="background:linear-gradient(135deg,#f0f6ff,#e8f0fe);">
                <th style="color:#1e40af;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:.85rem 1rem;border-bottom:2px solid #bfdbfe;">App #</th>
                <th style="color:#1e40af;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:.85rem 1rem;border-bottom:2px solid #bfdbfe;">Applicant</th>
                <th style="color:#1e40af;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:.85rem 1rem;border-bottom:2px solid #bfdbfe;">Destination / Visa</th>
                <th style="color:#1e40af;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:.85rem 1rem;border-bottom:2px solid #bfdbfe;">Stage</th>
                <th style="color:#1e40af;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:.85rem 1rem;border-bottom:2px solid #bfdbfe;">Status</th>
                <th style="color:#1e40af;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:.85rem 1rem;border-bottom:2px solid #bfdbfe;">Supplier Ref</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentApps as $app): ?>
                <tr>
                  <td style="font-weight:700;color:#2563eb;"><?= e($app['application_number']) ?></td>
                  <td>
                    <div class="fw-semibold"><?= e($app['customer_name']) ?></div>
                    <div class="text-muted" style="font-size:.75rem;"><?= e($app['customer_code']) ?></div>
                  </td>
                  <td><?= $app['flag_emoji'] ?? '🏳️' ?> <?= e($app['country_name'] ?? '') ?> — <?= e($app['service_name'] ?? '') ?></td>
                  <td><span class="badge" style="background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe;"><?= e($app['current_stage'] ?? 'Pending') ?></span></td>
                  <td>
                    <?php
                      $st = $app['status'];
                      $badgeStyle = match($st) {
                        'Approved'   => 'background:#dcfce7;color:#15803d;',
                        'In Process' => 'background:#ecfeff;color:#0e7490;',
                        'Pending'    => 'background:#fef9c3;color:#854d0e;',
                        'Rejected'   => 'background:#fee2e2;color:#b91c1c;',
                        default      => 'background:#f1f5f9;color:#475569;'
                      };
                    ?>
                    <span class="badge fw-semibold" style="<?= $badgeStyle ?>"><?= e($st) ?></span>
                  </td>
                  <td><span class="badge" style="background:#f0f6ff;color:#334155;border:1px solid #dce8f5;"><?= e($app['supplier_reference'] ?: '—') ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Sidebar: Quick Access + Recent Settlements -->
  <div class="col-lg-4">
    <div class="supplier-card mb-4">
      <h6 class="fw-bold mb-3 pb-2" style="border-bottom:2px solid #dce8f5;color:#0f172a;">
        <i class="fa-solid fa-bolt me-2" style="color:#f59e0b;"></i>Quick Actions
      </h6>
      <div class="d-grid gap-2">
        <a href="/supplier/applications" class="btn fw-semibold" style="background:linear-gradient(135deg,#0a1628,#0f2040);color:#fff;border:none;border-radius:8px;">
          <i class="fa-solid fa-tasks me-2"></i>Update Processing Status
        </a>
        <a href="/supplier/payments" class="btn btn-outline-primary fw-semibold" style="border-radius:8px;">
          <i class="fa-solid fa-file-invoice-dollar me-2"></i>View Payment Statements
        </a>
        <a href="/supplier/profile" class="btn btn-outline-secondary fw-semibold" style="border-radius:8px;">
          <i class="fa-solid fa-user-gear me-2"></i>Update Account Details
        </a>
      </div>
    </div>

    <div class="supplier-card">
      <h6 class="fw-bold mb-3 pb-2" style="border-bottom:2px solid #dce8f5;color:#0f172a;">
        <i class="fa-solid fa-receipt me-2" style="color:#22c55e;"></i>Recent Settlement Disbursals
      </h6>
      <?php if (empty($recentPayments)): ?>
        <p class="text-muted small mb-0">No settlement disbursements recorded yet.</p>
      <?php else: ?>
        <ul class="list-unstyled mb-0">
          <?php foreach ($recentPayments as $p): ?>
            <li style="display:flex;align-items:center;justify-content:space-between;padding:.6rem 0;border-bottom:1px solid #f0f6ff;">
              <div>
                <div class="fw-semibold text-dark" style="font-size:.87rem;"><?= e($p['payment_reference']) ?></div>
                <div class="text-muted" style="font-size:.75rem;"><?= e($p['payment_date']) ?> &bull; <?= e($p['application_number'] ?: 'Consolidated') ?></div>
              </div>
              <span class="fw-bold" style="color:#16a34a;font-size:.95rem;">+<?= format_currency((float)$p['paid_amount']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <!-- Info: What suppliers CANNOT see -->
    <div class="supplier-card mt-2" style="border:1px solid #bfdbfe;background:#f0f6ff;">
      <h6 class="fw-bold mb-2" style="color:#1e40af;font-size:.82rem;">
        <i class="fa-solid fa-eye-slash me-2"></i>Privacy & Data Scope
      </h6>
      <ul class="mb-0" style="font-size:.78rem;color:#334155;padding-left:1.25rem;">
        <li>Customer personal data is anonymised</li>
        <li>Internal pricing & profit margins hidden</li>
        <li>Only your assigned cases are visible</li>
        <li>Financial data shows payables only</li>
      </ul>
    </div>
  </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
