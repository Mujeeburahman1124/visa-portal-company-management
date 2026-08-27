<?php
$pageTitle = 'Processing Suppliers & Consular Partners — VISA TRACK';
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

  <!-- Page Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <h3 class="fw-bold brand-font text-dark mb-1">Processing Suppliers &amp; Consular Partners</h3>
      <p class="text-muted small mb-0">Manage external visa clearing suppliers, VFS/TLS express partners, and accounts payable balances.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#newSupplierModal">
        <i class="fa-solid fa-handshake-angle me-1"></i> Add Partner Supplier
      </button>
    </div>
  </div>

  <!-- Suppliers Data Table -->
  <div class="card card-enterprise shadow-sm">
    <div class="table-responsive">
      <table class="table-modern mb-0">
        <thead>
          <tr>
            <th style="min-width: 130px;">Partner Code</th>
            <th style="min-width: 220px;">Company &amp; Service</th>
            <th style="min-width: 180px;">Contact Person</th>
            <th style="min-width: 160px;">Country / Location</th>
            <th style="min-width: 120px;">Applications</th>
            <th style="min-width: 130px;">Total Payables</th>
            <th style="min-width: 130px;">Total Settled</th>
            <th class="text-end" style="min-width: 150px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($suppliers)): ?>
            <tr><td colspan="8" class="text-center py-5 text-muted">No external suppliers registered.</td></tr>
          <?php else: ?>
            <?php foreach ($suppliers as $sup): ?>
              <tr>
                <td>
                  <span class="badge bg-light text-dark border fw-bold px-2 py-1"><?= e($sup['supplier_code']) ?></span>
                </td>
                <td>
                  <div class="fw-bold text-dark fs-6"><?= e($sup['company_name']) ?></div>
                  <div class="text-muted small" style="font-size: 0.73rem;"><?= e($sup['services_provided'] ?? 'Visa Clearing & Embassy Liaison') ?></div>
                </td>
                <td>
                  <div class="fw-semibold small text-dark"><?= e($sup['contact_person'] ?: 'Operations Desk') ?></div>
                  <div class="text-muted" style="font-size: 0.72rem;">
                    <i class="fa-solid fa-phone me-1 text-primary"></i><?= e($sup['mobile'] ?: $sup['email'] ?: '—') ?>
                  </div>
                </td>
                <td>
                  <div class="small fw-semibold text-dark"><?= e($sup['country'] ?: 'Global') ?></div>
                  <div class="text-muted text-truncate" style="font-size: 0.72rem; max-width: 150px;"><?= e($sup['address'] ?: '—') ?></div>
                </td>
                <td>
                  <span class="badge bg-primary rounded-pill px-2.5 py-1"><?= (int)$sup['total_applications'] ?></span>
                </td>
                <td>
                  <span class="fw-bold text-danger"><?= format_currency((float)$sup['total_payables']) ?></span>
                </td>
                <td>
                  <span class="fw-bold text-success"><?= format_currency((float)$sup['total_paid']) ?></span>
                </td>
                <td class="text-end">
                  <div class="d-inline-flex align-items-center gap-1">
                    <button type="button" class="btn btn-sm btn-outline-success py-1 px-2.5 fw-semibold" data-bs-toggle="modal" data-bs-target="#paySupplierModal<?= $sup['id'] ?>">
                      <i class="fa-solid fa-money-bill-transfer me-1"></i> Pay
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" data-bs-toggle="modal" data-bs-target="#editSupplierModal<?= $sup['id'] ?>" title="Edit Supplier">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                  </div>
                </td>
              </tr>

              <!-- MODAL: PAY SUPPLIER -->
              <div class="modal fade" id="paySupplierModal<?= $sup['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-success text-white">
                      <h6 class="modal-title fw-bold"><i class="fa-solid fa-money-bill-transfer me-2"></i> Record Supplier Disbursement</h6>
                      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="/suppliers/pay" method="POST">
                      <?= csrf_field() ?>
                      <input type="hidden" name="supplier_id" value="<?= $sup['id'] ?>">
                      <div class="modal-body p-4 text-start">
                        <div class="p-3 bg-light rounded border mb-3">
                          <div class="small text-muted">Supplier / Payee:</div>
                          <div class="fw-bold fs-6 text-dark"><?= e($sup['company_name']) ?> (<?= e($sup['supplier_code']) ?>)</div>
                        </div>

                        <div class="row g-2 mb-3">
                          <div class="col-6">
                            <label class="form-label small fw-semibold">Disbursement Amount ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" class="form-control" placeholder="150.00" required>
                          </div>
                          <div class="col-6">
                            <label class="form-label small fw-semibold">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                          </div>
                        </div>

                        <div class="row g-2 mb-3">
                          <div class="col-6">
                            <label class="form-label small fw-semibold">Payment Method</label>
                            <select name="payment_method" class="form-select">
                              <option value="Bank Transfer">Bank Transfer</option>
                              <option value="Corporate Card">Corporate Card</option>
                              <option value="Cheque">Cheque</option>
                              <option value="Cash">Cash</option>
                            </select>
                          </div>
                          <div class="col-6">
                            <label class="form-label small fw-semibold">Linked Application (Optional)</label>
                            <select name="application_id" class="form-select">
                              <option value="0">General Account Settlement</option>
                              <?php foreach ($applications as $app): ?>
                                <option value="<?= $app['id'] ?>"><?= e($app['application_number']) ?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                        </div>

                        <div class="mb-3">
                          <label class="form-label small fw-semibold">Transaction Reference / Receipt #</label>
                          <input type="text" name="transaction_reference" class="form-control" placeholder="TXN-WIRE-99210">
                        </div>

                        <div class="mb-0">
                          <label class="form-label small fw-semibold">Internal Notes</label>
                          <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Cleared 5 Schengen biometrics voucher fees..."></textarea>
                        </div>
                      </div>
                      <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm px-3 fw-semibold">Record Payment</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>

              <!-- MODAL: EDIT SUPPLIER -->
              <div class="modal fade" id="editSupplierModal<?= $sup['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                      <h6 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i> Edit Supplier Profile</h6>
                      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="/suppliers/update" method="POST">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= $sup['id'] ?>">
                      <div class="modal-body p-4 text-start">
                        <div class="row g-2 mb-3">
                          <div class="col-4">
                            <label class="form-label small fw-semibold">Supplier Code <span class="text-danger">*</span></label>
                            <input type="text" name="supplier_code" class="form-control" value="<?= e($sup['supplier_code']) ?>" required>
                          </div>
                          <div class="col-8">
                            <label class="form-label small fw-semibold">Company Name <span class="text-danger">*</span></label>
                            <input type="text" name="company_name" class="form-control" value="<?= e($sup['company_name']) ?>" required>
                          </div>
                        </div>

                        <div class="row g-2 mb-3">
                          <div class="col-6">
                            <label class="form-label small fw-semibold">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control" value="<?= e($sup['contact_person']) ?>">
                          </div>
                          <div class="col-6">
                            <label class="form-label small fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= e($sup['email']) ?>">
                          </div>
                        </div>

                        <div class="row g-2 mb-3">
                          <div class="col-6">
                            <label class="form-label small fw-semibold">Mobile / Phone</label>
                            <input type="text" name="mobile" class="form-control" value="<?= e($sup['mobile']) ?>">
                          </div>
                          <div class="col-6">
                            <label class="form-label small fw-semibold">Country</label>
                            <input type="text" name="country" class="form-control" value="<?= e($sup['country']) ?>">
                          </div>
                        </div>

                        <div class="mb-3">
                          <label class="form-label small fw-semibold">Services Provided</label>
                          <input type="text" name="services_provided" class="form-control" value="<?= e($sup['services_provided'] ?? '') ?>">
                        </div>

                        <div class="mb-0">
                          <label class="form-label small fw-semibold">Address</label>
                          <input type="text" name="address" class="form-control" value="<?= e($sup['address']) ?>">
                        </div>
                      </div>
                      <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">Save Changes</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODAL: ADD SUPPLIER -->
<div class="modal fade" id="newSupplierModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-handshake-angle me-2"></i> Register Processing Supplier</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/suppliers/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="row g-2 mb-3">
            <div class="col-4">
              <label class="form-label small fw-semibold">Supplier Code <span class="text-danger">*</span></label>
              <input type="text" name="supplier_code" class="form-control" placeholder="SUP-005" required>
            </div>
            <div class="col-8">
              <label class="form-label small fw-semibold">Company Name <span class="text-danger">*</span></label>
              <input type="text" name="company_name" class="form-control" placeholder="e.g. Gulf Visa Clearing LLC" required>
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Contact Person</label>
              <input type="text" name="contact_person" class="form-control" placeholder="e.g. Ahmed Al-Suwaidi">
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Work Email</label>
              <input type="email" name="email" class="form-control" placeholder="contact@gulfvisaclearing.ae">
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Mobile / WhatsApp</label>
              <input type="text" name="mobile" class="form-control" placeholder="+971 55 998 8776">
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Country / Jurisdiction</label>
              <input type="text" name="country" class="form-control" placeholder="United Arab Emirates">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Services Provided</label>
            <input type="text" name="services_provided" class="form-control" placeholder="e.g. Express Embassy Clearance, Medical VIP Escort">
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold">Office Address</label>
            <input type="text" name="address" class="form-control" placeholder="Suite 401, Al Razi Building, Dubai Healthcare City">
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">Register Partner</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
