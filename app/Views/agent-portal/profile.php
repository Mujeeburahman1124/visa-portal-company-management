<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="agent-card">
      <h5 class="fw-bold mb-3 text-dark border-bottom pb-2"><i class="fa-solid fa-building text-success me-2"></i>Agency Profile</h5>
      <form action="/agent/profile" method="POST">
        <?= csrf_field() ?>
        
        <div class="mb-3">
          <label class="form-label small fw-semibold">Company Name</label>
          <input type="text" class="form-control" value="<?= e($agentData['company_name']) ?>" disabled>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Agent Code</label>
          <input type="text" class="form-control" value="<?= e($agentData['agent_code']) ?>" disabled>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Contact Person</label>
          <input type="text" name="contact_person" class="form-control" value="<?= e($agentData['contact_person']) ?>" required>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label small fw-semibold">Mobile</label>
            <input type="text" name="mobile" class="form-control" value="<?= e($agentData['mobile']) ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label small fw-semibold">WhatsApp</label>
            <input type="text" name="whatsapp" class="form-control" value="<?= e($agentData['whatsapp']) ?>">
          </div>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label small fw-semibold">City</label>
            <input type="text" name="city" class="form-control" value="<?= e($agentData['city']) ?>">
          </div>
          <div class="col-6">
            <label class="form-label small fw-semibold">Country</label>
            <input type="text" class="form-control" value="<?= e($agentData['country']) ?>" disabled>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Address</label>
          <textarea name="address" class="form-control" rows="2"><?= e($agentData['address']) ?></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-semibold">New Password (leave blank to keep current)</label>
          <input type="password" name="password" class="form-control" placeholder="••••••••">
        </div>

        <button type="submit" class="btn btn-success w-100 fw-semibold"><i class="fa-solid fa-save me-1"></i>Save Changes</button>
      </form>
    </div>
  </div>

  <div class="col-lg-7">
    <!-- Credit & Financial Account Details -->
    <div class="agent-card mb-4">
      <h5 class="fw-bold mb-3 text-dark border-bottom pb-2"><i class="fa-solid fa-wallet text-success me-2"></i>Account &amp; Credit Standing</h5>
      <div class="row g-3 text-center">
        <div class="col-md-4">
          <div class="p-3 border rounded bg-light">
            <div class="text-muted small">Credit Limit</div>
            <h5 class="fw-bold text-dark mb-0"><?= format_currency((float)$agentData['credit_limit']) ?></h5>
          </div>
        </div>
        <div class="col-md-4">
          <div class="p-3 border rounded bg-light">
            <div class="text-muted small">Current Balance</div>
            <h5 class="fw-bold text-danger mb-0"><?= format_currency((float)$agentData['current_balance']) ?></h5>
          </div>
        </div>
        <div class="col-md-4">
          <div class="p-3 border rounded bg-light">
            <div class="text-muted small">Commission Rate</div>
            <h5 class="fw-bold text-success mb-0"><?= (float)$agentData['commission_rate'] ?>%</h5>
          </div>
        </div>
      </div>
    </div>

    <!-- Payments and Account Statement -->
    <div class="agent-card">
      <h5 class="fw-bold mb-3 text-dark border-bottom pb-2"><i class="fa-solid fa-receipt text-success me-2"></i>Payment &amp; Credit History</h5>
      <?php if (empty($payments)): ?>
        <p class="text-muted small mb-0">No financial transactions recorded.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
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
                  <td class="fw-bold text-success"><?= e($p['payment_reference']) ?></td>
                  <td><?= e($p['payment_date']) ?></td>
                  <td><span class="badge bg-light text-dark border"><?= e($p['payment_type']) ?></span></td>
                  <td><?= e($p['payment_method']) ?></td>
                  <td class="text-muted"><?= e($p['transaction_reference'] ?: '—') ?></td>
                  <td class="text-end fw-bold text-dark"><?= format_currency((float)$p['amount']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
