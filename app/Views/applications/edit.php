<?php
$pageTitle = 'Edit Application — ' . e($app['application_number']);
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';
?>

<div class="content-body">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show mb-4" role="alert">
      <?= e($flash['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <a href="/applications/show?id=<?= $app['id'] ?>" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i></a>
        <h3 class="fw-bold brand-font mb-0">Edit Visa Application</h3>
        <span class="badge bg-primary fs-6"><?= e($app['application_number']) ?></span>
      </div>
      <p class="text-muted small mb-0">Applicant: <strong><?= e($app['customer_name']) ?></strong> (<?= e($app['customer_code']) ?>)</p>
    </div>
    <div class="d-flex gap-2">
      <a href="/applications/show?id=<?= $app['id'] ?>" class="btn btn-outline-secondary btn-sm px-3">Cancel</a>
      <button type="submit" form="editAppForm" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
        <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
      </button>
    </div>
  </div>

  <form id="editAppForm" action="/applications/update" method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $app['id'] ?>">

    <div class="row g-4">
      <div class="col-lg-8">
        <!-- 1. Visa Service & Destination Details -->
        <div class="card card-enterprise mb-4 bg-white shadow-sm">
          <div class="card-header bg-light py-3">
            <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-plane-departure me-2"></i> Visa Service &amp; Destination</h6>
          </div>
          <div class="card-body p-4">
            <div class="row g-3">
              <div class="col-md-12">
                <label class="form-label small fw-semibold text-secondary">Visa Service Package <span class="text-danger">*</span></label>
                <select name="visa_service_id" class="form-select" required>
                  <?php foreach ($services as $svc): ?>
                    <option value="<?= $svc['id'] ?>" <?= (int)$app['visa_service_id'] === (int)$svc['id'] ? 'selected' : '' ?>>
                      <?= $svc['flag_emoji'] ?> <?= e($svc['country_name']) ?> &mdash; <?= e($svc['name']) ?> ($<?= number_format((float)$svc['selling_price'], 2) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Passport Number</label>
                <input type="text" name="passport_number" class="form-control font-monospace fw-bold" value="<?= e($app['passport_number'] ?? '') ?>" placeholder="e.g. Z9876543">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Processing Priority</label>
                <select name="priority" class="form-select">
                  <option value="Standard" <?= ($app['priority'] ?? '') === 'Standard' ? 'selected' : '' ?>>Standard</option>
                  <option value="High" <?= ($app['priority'] ?? '') === 'High' ? 'selected' : '' ?>>High</option>
                  <option value="Urgent" <?= ($app['priority'] ?? '') === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                  <option value="Critical" <?= ($app['priority'] ?? '') === 'Critical' ? 'selected' : '' ?>>Critical</option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Expected Travel Date</label>
                <input type="date" name="travel_date" class="form-control" value="<?= e($app['travel_date'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Return Date</label>
                <input type="date" name="return_date" class="form-control" value="<?= e($app['return_date'] ?? '') ?>">
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Embassy Submission Date</label>
                <input type="date" name="submission_date" class="form-control" value="<?= e($app['submission_date'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Target Completion Date</label>
                <input type="date" name="expected_completion_date" class="form-control" value="<?= e($app['expected_completion_date'] ?? '') ?>">
              </div>
            </div>
          </div>
        </div>

        <!-- 2. Financial Breakdown -->
        <div class="card card-enterprise mb-4 bg-white shadow-sm">
          <div class="card-header bg-light py-3">
            <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-coins me-2"></i> Financial &amp; Pricing Breakdown ($ USD)</h6>
          </div>
          <div class="card-body p-4">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Selling Price to Client ($)</label>
                <input type="number" step="0.01" name="selling_price" class="form-control fw-bold" value="<?= e($app['selling_price'] ?? '0.00') ?>" required>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Supplier / Vendor Cost ($)</label>
                <input type="number" step="0.01" name="supplier_cost" class="form-control" value="<?= e($app['supplier_cost'] ?? '0.00') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Embassy Fee ($)</label>
                <input type="number" step="0.01" name="embassy_fee" class="form-control" value="<?= e($app['embassy_fee'] ?? '0.00') ?>">
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Service Fee ($)</label>
                <input type="number" step="0.01" name="service_fee" class="form-control" value="<?= e($app['service_fee'] ?? '0.00') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Discount Amount ($)</label>
                <input type="number" step="0.01" name="discount_amount" class="form-control" value="<?= e($app['discount_amount'] ?? '0.00') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Tax / VAT ($)</label>
                <input type="number" step="0.01" name="tax_amount" class="form-control" value="<?= e($app['tax_amount'] ?? '0.00') ?>">
              </div>
            </div>
          </div>
        </div>

        <!-- 3. Notes -->
        <div class="card card-enterprise mb-4 bg-white shadow-sm">
          <div class="card-header bg-light py-3">
            <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-note-sticky me-2"></i> Application Case Notes</h6>
          </div>
          <div class="card-body p-4">
            <textarea name="notes" class="form-control" rows="3" placeholder="Case notes, embassy appointment reference, etc..."><?= e($app['notes'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <!-- Branch & Operations Assignment -->
        <div class="card card-enterprise mb-4 bg-white shadow-sm">
          <div class="card-header bg-light py-3">
            <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-users-gear me-2"></i> Operational Routing</h6>
          </div>
          <div class="card-body p-4">
            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">Assigned Processing Staff</label>
              <select name="assigned_staff_id" class="form-select">
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
              <select name="branch_id" class="form-select">
                <?php foreach ($branches as $br): ?>
                  <option value="<?= $br['id'] ?>" <?= (int)($app['branch_id'] ?? 1) === (int)$br['id'] ? 'selected' : '' ?>>
                    <?= e($br['name']) ?> (<?= e($br['city']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-0">
              <label class="form-label small fw-semibold text-secondary">Outsource Supplier / Partner</label>
              <select name="supplier_id" class="form-select">
                <option value="">-- In-House Direct Processing --</option>
                <?php foreach ($suppliers as $sup): ?>
                  <option value="<?= $sup['id'] ?>" <?= (int)($app['supplier_id'] ?? 0) === (int)$sup['id'] ? 'selected' : '' ?>>
                    <?= e($sup['company_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-primary fw-semibold shadow-sm py-2">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
          </button>
          <a href="/applications/show?id=<?= $app['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </div>
    </div>
  </form>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
