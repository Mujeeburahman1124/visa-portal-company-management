<?php require_once __DIR__ . '/navbar.php'; ?>

<!-- ─── HEADER STRIP ──────────────────────────────────────────────────────── -->
<div class="agent-header-strip">
  <div>
    <h1 class="agent-header-title">Agency Profile &amp; Statements</h1>
    <p class="agent-header-subtitle">
      Manage agency contact credentials, monitor credit limits, and view financial transaction history.
    </p>
  </div>
</div>

<div class="row g-4">
  <!-- Left Column: Agency Profile & Password Settings -->
  <div class="col-lg-5">
    <div class="agent-bento-card">
      <div class="agent-bento-card-header">
        <h5 class="agent-bento-card-title">
          <span class="card-title-icon"><i class="fa-solid fa-building"></i></span>
          <span>Agency Profile</span>
        </h5>
      </div>

      <form action="/agent/profile" method="POST">
        <?= csrf_field() ?>
        
        <div class="mb-3">
          <label class="form-label small fw-semibold">Company Name</label>
          <input type="text" class="form-control agent-form-control bg-light" value="<?= e($agentData['company_name']) ?>" disabled>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Agent Code</label>
          <input type="text" class="form-control agent-form-control bg-light font-monospace" value="<?= e($agentData['agent_code']) ?>" disabled>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Contact Person <span class="text-danger">*</span></label>
          <input type="text" name="contact_person" class="form-control agent-form-control" value="<?= e($agentData['contact_person']) ?>" required>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label small fw-semibold">Mobile <span class="text-danger">*</span></label>
            <input type="text" name="mobile" class="form-control agent-form-control" value="<?= e($agentData['mobile']) ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label small fw-semibold">WhatsApp</label>
            <input type="text" name="whatsapp" class="form-control agent-form-control" value="<?= e($agentData['whatsapp']) ?>">
          </div>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label small fw-semibold">City</label>
            <input type="text" name="city" class="form-control agent-form-control" value="<?= e($agentData['city']) ?>">
          </div>
          <div class="col-6">
            <label class="form-label small fw-semibold">Country</label>
            <input type="text" class="form-control agent-form-control bg-light" value="<?= e($agentData['country']) ?>" disabled>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Address</label>
          <textarea name="address" class="form-control agent-form-control" rows="2"><?= e($agentData['address']) ?></textarea>
        </div>

        <div class="mb-4">
          <label class="form-label small fw-semibold">New Password (leave blank to keep current)</label>
          <input type="password" name="password" class="form-control agent-form-control" placeholder="••••••••" autocomplete="new-password">
        </div>

        <button type="submit" class="agent-btn-primary w-100 justify-content-center py-2.5">
          <i class="fa-solid fa-floppy-disk"></i>
          <span>Save Profile Changes</span>
        </button>
      </form>
    </div>
  </div>

  <!-- Right Column: Credit Standing & Ledger Statements -->
  <div class="col-lg-7">
    <!-- Credit & Financial Account Standing -->
    <div class="agent-bento-card mb-4">
      <div class="agent-bento-card-header">
        <h5 class="agent-bento-card-title">
          <span class="card-title-icon"><i class="fa-solid fa-wallet"></i></span>
          <span>Account &amp; Credit Standing</span>
        </h5>
      </div>

      <div class="row g-3 text-center">
        <div class="col-md-4">
          <div class="p-3 border rounded-3 bg-light">
            <div class="text-muted small fw-semibold mb-1">Credit Limit</div>
            <h4 class="fw-bold text-dark mb-0 font-monospace"><?= format_currency((float)$agentData['credit_limit']) ?></h4>
          </div>
        </div>
        <div class="col-md-4">
          <div class="p-3 border rounded-3" style="background:#FFF1F3; border-color:var(--agent-primary-border) !important;">
            <div class="small fw-semibold mb-1" style="color:var(--agent-primary);">Current Balance</div>
            <h4 class="fw-bold mb-0 font-monospace" style="color:var(--agent-primary);"><?= format_currency((float)$agentData['current_balance']) ?></h4>
          </div>
        </div>
        <div class="col-md-4">
          <div class="p-3 border rounded-3 bg-light">
            <div class="text-muted small fw-semibold mb-1">Commission Rate</div>
            <h4 class="fw-bold text-success mb-0 font-monospace"><?= (float)$agentData['commission_rate'] ?>%</h4>
          </div>
        </div>
      </div>
    </div>

    <!-- Payments and Account Statement -->
    <div class="agent-bento-card">
      <div class="agent-bento-card-header">
        <h5 class="agent-bento-card-title">
          <span class="card-title-icon"><i class="fa-solid fa-receipt"></i></span>
          <span>Payment &amp; Credit History</span>
        </h5>
      </div>

      <?php if (empty($payments)): ?>
        <div class="text-center py-4 text-muted">
          <i class="fa-solid fa-receipt fa-2x mb-2 opacity-25"></i>
          <p class="small mb-0">No financial transactions recorded.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="agent-table">
            <thead>
              <tr>
                <th>Receipt #</th>
                <th>Date</th>
                <th>Type</th>
                <th>Method</th>
                <th>Ref</th>
                <th class="text-end">Amount</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($payments as $p): ?>
                <tr>
                  <td>
                    <span class="fw-bold" style="color:var(--agent-primary); font-family:'SFMono-Regular', Menlo, Monaco, Consolas, monospace;">
                      <?= e($p['payment_reference']) ?>
                    </span>
                  </td>
                  <td class="text-muted small"><?= date('M d, Y', strtotime($p['payment_date'])) ?></td>
                  <td><span class="badge bg-light text-dark border font-monospace"><?= e($p['payment_type']) ?></span></td>
                  <td class="small"><?= e($p['payment_method']) ?></td>
                  <td class="text-muted small"><?= e($p['transaction_reference'] ?: '—') ?></td>
                  <td class="text-end fw-bold text-success fs-6 font-monospace">+<?= format_currency((float)$p['amount']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
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
