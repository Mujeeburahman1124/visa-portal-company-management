<?php
$pageTitle = 'Edit Application — ' . e($app['application_number']);
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
?>

<!-- Load Bento Design System CSS (Referencing Dashboard & Visa Tracking UI) -->
<link rel="stylesheet" href="/assets/css/dashboard-bento.css?v=<?= time() ?>">
<link rel="stylesheet" href="/assets/css/pages/applications-index.css?v=<?= time() ?>">

<div class="content-body p-0 apps-page">
  <div class="bento-dashboard-wrap">

    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm rounded-4" role="alert">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
          <span><?= e($flash['message']) ?></span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- ─── HEADER & ACTIONS ─────────────────────────────────────────────── -->
    <header class="bento-header mb-4">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
          <a href="/applications/show?id=<?= $app['id'] ?>" class="bento-btn-outline" style="padding: 0.4rem 0.9rem; font-size: 0.8rem;" title="Back to Application Details">
            <i class="fa-solid fa-arrow-left"></i>
          </a>
          <h1 class="bento-header-title mb-0">Edit Visa Application</h1>
          <span class="apps-appno">
            <i class="fa-solid fa-folder me-1"></i><?= e($app['application_number']) ?>
          </span>
        </div>
        <p class="bento-header-subtitle">
          Applicant: <strong class="text-dark"><?= e($app['customer_name']) ?></strong> (<?= e($app['customer_code']) ?>)
        </p>
      </div>

      <div class="apps-header-actions">
        <a href="/applications/show?id=<?= $app['id'] ?>" class="bento-btn-outline">
          Cancel
        </a>
        <button type="submit" form="editAppForm" class="bento-btn-primary">
          <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
        </button>
      </div>
    </header>

    <form id="editAppForm" action="/applications/update" method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $app['id'] ?>">

      <div class="row g-4">
        <div class="col-12 col-lg-8">
          <!-- 1. Visa Service & Destination Details -->
          <div class="bento-card mb-4 p-4">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
              <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 1.05rem;">
                <i class="fa-solid fa-plane-departure text-primary"></i> Visa Service &amp; Destination Details
              </h5>
            </div>

            <div class="row g-3">
              <div class="col-12">
                <label class="form-label small fw-semibold text-secondary">Visa Service Package <span class="text-danger">*</span></label>
                <select name="visa_service_id" class="form-select bg-light" required>
                  <?php foreach ($services as $svc): ?>
                    <option value="<?= $svc['id'] ?>" <?= (int)$app['visa_service_id'] === (int)$svc['id'] ? 'selected' : '' ?>>
                      <?= $svc['flag_emoji'] ?> <?= e($svc['country_name']) ?> &mdash; <?= e($svc['name']) ?> ($<?= number_format((float)$svc['selling_price'], 2) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold text-secondary">Passport Number</label>
                <input type="text" name="passport_number" class="form-control bg-light font-monospace fw-bold" value="<?= e($app['passport_number'] ?? '') ?>" placeholder="e.g. Z9876543">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold text-secondary">Processing Priority</label>
                <select name="priority" class="form-select bg-light">
                  <option value="Normal" <?= ($app['priority'] ?? '') === 'Normal' || ($app['priority'] ?? '') === 'Standard' ? 'selected' : '' ?>>Normal</option>
                  <option value="High" <?= ($app['priority'] ?? '') === 'High' ? 'selected' : '' ?>>High</option>
                  <option value="Urgent" <?= ($app['priority'] ?? '') === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                  <option value="Critical" <?= ($app['priority'] ?? '') === 'Critical' ? 'selected' : '' ?>>Critical</option>
                </select>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold text-secondary">Expected Travel Date</label>
                <input type="date" name="travel_date" class="form-control bg-light" value="<?= e($app['travel_date'] ?? '') ?>">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold text-secondary">Return Date</label>
                <input type="date" name="return_date" class="form-control bg-light" value="<?= e($app['return_date'] ?? '') ?>">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold text-secondary">Embassy Submission Date</label>
                <input type="date" name="submission_date" class="form-control bg-light" value="<?= e($app['submission_date'] ?? $app['application_date'] ?? '') ?>">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold text-secondary">Target Completion Date (SLA)</label>
                <input type="date" name="expected_completion_date" class="form-control bg-light" value="<?= e($app['expected_completion_date'] ?? '') ?>">
              </div>
            </div>
          </div>

          <!-- 2. Financial Breakdown -->
          <div class="bento-card mb-4 p-4">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
              <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 1.05rem;">
                <i class="fa-solid fa-coins text-warning"></i> Financial &amp; Pricing Breakdown ($ USD)
              </h5>
            </div>

            <div class="row g-3">
              <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary">Selling Price to Client ($) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="selling_price" class="form-control bg-light fw-bold" value="<?= e($app['selling_price'] ?? '0.00') ?>" required>
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary">Base Operational Cost ($)</label>
                <input type="number" step="0.01" name="supplier_cost" class="form-control bg-light" value="<?= e($app['supplier_cost'] ?? '0.00') ?>">
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary">Embassy Fee ($)</label>
                <input type="number" step="0.01" name="embassy_fee" class="form-control bg-light" value="<?= e($app['embassy_fee'] ?? '0.00') ?>">
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary">Service Fee ($)</label>
                <input type="number" step="0.01" name="service_fee" class="form-control bg-light" value="<?= e($app['service_fee'] ?? '0.00') ?>">
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary">Discount Amount ($)</label>
                <input type="number" step="0.01" name="discount_amount" class="form-control bg-light" value="<?= e($app['discount_amount'] ?? $app['discount'] ?? '0.00') ?>">
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary">Tax / VAT ($)</label>
                <input type="number" step="0.01" name="tax_amount" class="form-control bg-light" value="<?= e($app['tax_amount'] ?? '0.00') ?>">
              </div>
            </div>
          </div>

          <!-- 3. Case Notes -->
          <div class="bento-card mb-4 p-4">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
              <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 1.05rem;">
                <i class="fa-solid fa-note-sticky text-info"></i> Application Case Notes
              </h5>
            </div>
            <textarea name="notes" class="form-control bg-light" rows="3" placeholder="Case notes, embassy appointment reference, consular remarks..."><?= e($app['internal_notes'] ?? $app['notes'] ?? '') ?></textarea>
          </div>
        </div>

        <!-- Right Column: Routing & Save Action -->
        <div class="col-12 col-lg-4">
          <div class="bento-card mb-4 p-4">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
              <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 1.05rem;">
                <i class="fa-solid fa-users-gear text-primary"></i> Operational Routing
              </h5>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">Assigned Processing Staff</label>
              <select name="assigned_staff_id" class="form-select bg-light">
                <option value="">-- Unassigned --</option>
                <?php foreach ($staffMembers as $st): ?>
                  <option value="<?= $st['id'] ?>" <?= (int)($app['assigned_staff_id'] ?? 0) === (int)$st['id'] ? 'selected' : '' ?>>
                    <?= e($st['name']) ?> (<?= e($st['designation']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">Processing Branch</label>
              <select name="branch_id" class="form-select bg-light">
                <?php foreach ($branches as $br): ?>
                  <option value="<?= $br['id'] ?>" <?= (int)($app['branch_id'] ?? 1) === (int)$br['id'] ? 'selected' : '' ?>>
                    <?= e($br['name']) ?> (<?= e($br['city']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <input type="hidden" name="supplier_id" value="<?= (int)($app['supplier_id'] ?? 0) ?>">
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="bento-btn-primary w-100 py-2.5">
              <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
            </button>
            <a href="/applications/show?id=<?= $app['id'] ?>" class="btn btn-light border rounded-pill py-2 text-center text-muted fw-semibold">
              Cancel &amp; Return
            </a>
          </div>
        </div>
      </div>
    </form>

  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
