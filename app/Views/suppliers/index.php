<?php
$pageTitle = 'Processing Suppliers & Consular Partners — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';

// Calculate summary totals across all suppliers
$currentView = $_GET['view'] ?? 'table';
$totalPayablesSum = 0;
$totalSettledSum = 0;
$totalOutstandingSum = 0;
foreach ($suppliers as $s) {
    $pay = (float)($s['total_payables'] ?? 0);
    $paid = (float)($s['total_paid'] ?? 0);
    $totalPayablesSum += $pay;
    $totalSettledSum += $paid;
    $totalOutstandingSum += max(0, $pay - $paid);
}
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
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <!-- 3 View Options Switcher (Responsive) -->
      <div class="btn-group btn-group-sm bg-white shadow-sm border rounded-pill p-1" role="group" aria-label="View Mode">
        <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 fw-semibold supplier-view-btn <?= $currentView === 'table' ? 'btn-primary shadow-sm' : 'btn-light text-muted' ?>" onclick="switchSupplierView('table')" id="btnSupplierViewTable" title="Table View">
          <i class="fa-solid fa-table-list me-1"></i> <span class="d-none d-sm-inline">Table</span>
        </button>
        <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 fw-semibold supplier-view-btn <?= $currentView === 'grid' ? 'btn-primary shadow-sm' : 'btn-light text-muted' ?>" onclick="switchSupplierView('grid')" id="btnSupplierViewGrid" title="Grid Cards View">
          <i class="fa-solid fa-grip me-1"></i> <span class="d-none d-sm-inline">Grid Cards</span>
        </button>
        <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 fw-semibold supplier-view-btn <?= $currentView === 'compact' ? 'btn-primary shadow-sm' : 'btn-light text-muted' ?>" onclick="switchSupplierView('compact')" id="btnSupplierViewCompact" title="Compact List View">
          <i class="fa-solid fa-list-ul me-1"></i> <span class="d-none d-sm-inline">Compact List</span>
        </button>
      </div>

      <a href="/suppliers/wallet" class="btn btn-outline-info btn-sm px-3 shadow-sm">
        <i class="fa-solid fa-wallet me-1"></i> Supplier Wallets
      </a>
      <a href="/suppliers/payments" class="btn btn-outline-primary btn-sm px-3 shadow-sm">
        <i class="fa-solid fa-receipt me-1"></i> Payment Ledger &amp; History
      </a>
      <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#newSupplierModal">
        <i class="fa-solid fa-handshake-angle me-1"></i> Add Partner Supplier
      </button>
    </div>
  </div>

  <!-- Summary KPI Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card card-enterprise border-0 shadow-sm border-start border-primary border-4 p-3">
        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Active Suppliers</div>
        <div class="h4 mb-0 fw-bold text-dark"><?= count($suppliers) ?></div>
        <div class="text-muted small mt-1">Consular &amp; logistics partners</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card card-enterprise border-0 shadow-sm border-start border-danger border-4 p-3">
        <div class="text-xs fw-bold text-danger text-uppercase mb-1">Total Supplier Payables</div>
        <div class="h4 mb-0 fw-bold text-dark"><?= format_currency($totalPayablesSum) ?></div>
        <div class="text-muted small mt-1">Total supplier cost invoiced</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card card-enterprise border-0 shadow-sm border-start border-success border-4 p-3">
        <div class="text-xs fw-bold text-success text-uppercase mb-1">Total Settled / Paid</div>
        <div class="h4 mb-0 fw-bold text-dark"><?= format_currency($totalSettledSum) ?></div>
        <div class="text-muted small mt-1">Disbursements executed</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card card-enterprise border-0 shadow-sm border-start border-warning border-4 p-3">
        <div class="text-xs fw-bold text-warning text-uppercase mb-1">Outstanding Balance</div>
        <div class="h4 mb-0 fw-bold text-dark"><?= format_currency($totalOutstandingSum) ?></div>
        <div class="text-muted small mt-1">Payables pending settlement</div>
      </div>
    </div>
  </div>

  <!-- ================================================================= -->
  <!-- VIEW OPTION 1: SUPPLIERS DATA TABLE VIEW -->
  <!-- ================================================================= -->
  <div id="supplierViewTable" class="supplier-view-container <?= $currentView === 'table' ? '' : 'd-none' ?>">
    <div class="card card-enterprise shadow-sm">
      <div class="table-responsive">
        <table class="table-modern mb-0">
          <thead>
            <tr>
              <th style="min-width: 120px;">Partner Code</th>
              <th style="min-width: 200px;">Company &amp; Service</th>
              <th style="min-width: 170px;">Contact Person</th>
              <th style="min-width: 140px;">Country</th>
              <th style="min-width: 100px;">Apps</th>
              <th style="min-width: 120px;">Total Payables</th>
              <th style="min-width: 120px;">Total Settled</th>
              <th style="min-width: 120px;">Balance Due</th>
              <th class="text-end" style="min-width: 170px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($suppliers)): ?>
              <tr><td colspan="9" class="text-center py-5 text-muted">No external suppliers registered.</td></tr>
            <?php else: ?>
              <?php foreach ($suppliers as $sup): ?>
                <?php
                $due = max(0, (float)$sup['total_payables'] - (float)$sup['total_paid']);
                ?>
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
                  <td>
                    <span class="fw-bold <?= $due > 0 ? 'text-warning' : 'text-muted' ?>">
                      <?= format_currency($due) ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <div class="d-inline-flex align-items-center gap-1">
                      <a href="/suppliers/wallet?id=<?= $sup['id'] ?>" class="btn btn-sm btn-outline-info py-1 px-2" title="Supplier Wallet & Advance Ledger">
                        <i class="fa-solid fa-wallet"></i>
                      </a>
                      <form action="/suppliers/send-activation" method="POST" class="d-inline" onsubmit="return confirm('Send portal activation link to <?= e($sup['company_name']) ?>?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="supplier_id" value="<?= $sup['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-info py-1 px-2" title="Send Password Setup & Activation Link">
                          <i class="fa-solid fa-paper-plane"></i>
                        </button>
                      </form>
                      <button type="button" class="btn btn-sm btn-outline-warning py-1 px-2" data-bs-toggle="modal" data-bs-target="#resetSupplierPasswordModal<?= $sup['id'] ?>" title="Reset Portal Password">
                        <i class="fa-solid fa-key"></i>
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-success py-1 px-2.5 fw-semibold" data-bs-toggle="modal" data-bs-target="#paySupplierModal<?= $sup['id'] ?>">
                        <i class="fa-solid fa-money-bill-transfer me-1"></i> Pay
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" data-bs-toggle="modal" data-bs-target="#editSupplierModal<?= $sup['id'] ?>" title="Edit Supplier">
                        <i class="fa-solid fa-pen-to-square"></i>
                      </button>
                      <form action="/suppliers/delete" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete supplier <?= e($sup['company_name']) ?> (<?= e($sup['supplier_code']) ?>)?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="supplier_id" value="<?= $sup['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Delete Supplier">
                          <i class="fa-solid fa-trash-can"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>

              <!-- MODAL: RESET SUPPLIER PASSWORD -->
              <div class="modal fade" id="resetSupplierPasswordModal<?= $sup['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-warning text-dark">
                      <h6 class="modal-title fw-bold"><i class="fa-solid fa-key me-2"></i> Reset Supplier Portal Password</h6>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="/suppliers/reset-password" method="POST">
                      <?= csrf_field() ?>
                      <input type="hidden" name="supplier_id" value="<?= $sup['id'] ?>">
                      <div class="modal-body p-4 text-start">
                        <div class="p-3 bg-light rounded border mb-3">
                          <div class="small text-muted">Supplier Company:</div>
                          <div class="fw-bold fs-6 text-dark"><?= e($sup['company_name']) ?> (<?= e($sup['supplier_code']) ?>)</div>
                          <div class="small text-primary"><i class="fa-solid fa-envelope me-1"></i><?= e($sup['email'] ?: 'No email registered') ?></div>
                        </div>
                        <div class="mb-3">
                          <label class="form-label small fw-semibold">Custom Password (leave blank to auto-generate temporary password)</label>
                          <input type="text" name="new_password" class="form-control font-monospace" placeholder="e.g. Leave blank for auto-generated password">
                          <div class="form-text small text-muted">If left blank, the system will generate a secure temporary password (e.g. <code>SUP@...</code>) and email it to the partner.</div>
                        </div>
                      </div>
                      <div class="modal-footer bg-light p-3">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning btn-sm fw-bold">
                          <i class="fa-solid fa-arrows-rotate me-1"></i> Reset Password Now
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>

              <!-- MODAL: PAY SUPPLIER -->
              <div class="modal fade" id="paySupplierModal<?= $sup['id'] ?>" tabindex="-1" aria-labelledby="paySupplierModalLabel<?= $sup['id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                  <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-success text-white py-3 px-4">
                      <h6 class="modal-title fw-bold" id="paySupplierModalLabel<?= $sup['id'] ?>">
                        <i class="fa-solid fa-money-bill-transfer me-2"></i> Record Supplier Disbursement
                      </h6>
                      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="/suppliers/pay" method="POST" enctype="multipart/form-data" class="m-0 p-0">
                      <?= csrf_field() ?>
                      <input type="hidden" name="supplier_id" value="<?= $sup['id'] ?>">
                      <div class="modal-body p-4 text-start">
                        <div class="p-3 bg-light rounded border mb-3">
                          <div class="small text-muted">Supplier / Payee:</div>
                          <div class="fw-bold fs-6 text-dark"><?= e($sup['company_name']) ?> (<?= e($sup['supplier_code']) ?>)</div>
                          <div class="small text-muted">Current Outstanding: <span class="fw-bold text-danger"><?= format_currency($due) ?></span></div>
                        </div>

                        <div class="row g-3 mb-3">
                          <div class="col-md-6">
                            <label class="form-label small fw-semibold">Disbursement Amount ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" class="form-control" placeholder="150.00" required>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label small fw-semibold">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                          </div>
                        </div>

                        <div class="row g-3 mb-3">
                          <div class="col-md-6">
                            <label class="form-label small fw-semibold">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select" required>
                              <option value="Bank Transfer">Bank Transfer</option>
                              <option value="Supplier Wallet">Supplier Wallet (Deduct Balance)</option>
                              <option value="Corporate Card">Corporate Card</option>
                              <option value="Cheque">Cheque</option>
                              <option value="Cash">Cash</option>
                              <option value="Online / Card">Online / Card</option>
                            </select>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label small fw-semibold">Linked Application (Optional)</label>
                            <select name="application_id" class="form-select">
                              <option value="0">General Account Settlement</option>
                              <?php if (!empty($applications)): ?>
                                <?php foreach ($applications as $app): ?>
                                  <option value="<?= $app['id'] ?>">
                                    <?= e($app['application_number']) ?> - <?= e($app['applicant_name'] ?? '') ?>
                                  </option>
                                <?php endforeach; ?>
                              <?php endif; ?>
                            </select>
                          </div>
                        </div>

                        <div class="row g-3 mb-3">
                          <div class="col-md-6">
                            <label class="form-label small fw-semibold">Supplier Invoice / Bill Ref</label>
                            <input type="text" name="supplier_invoice_ref" class="form-control" placeholder="INV-2026-9081">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label small fw-semibold">Transaction / Wire Ref</label>
                            <input type="text" name="transaction_reference" class="form-control" placeholder="TXN-WIRE-99210">
                          </div>
                        </div>

                        <div class="mb-3">
                          <label class="form-label small fw-semibold">Payment Slip / Wire Voucher <span class="text-danger">*</span></label>
                          <input type="file" name="receipt_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.docx" required>
                          <div class="form-text small text-muted"><i class="fa-solid fa-file-circle-check text-success me-1"></i>Mandatory payment proof slip / bank transfer voucher (PDF, JPG, PNG, DOCX up to 15MB).</div>
                        </div>

                        <div class="mb-0">
                          <label class="form-label small fw-semibold">Internal Notes</label>
                          <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Cleared 5 Schengen biometrics voucher fees..."></textarea>
                        </div>
                      </div>
                      <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm px-4 fw-semibold shadow-sm">
                          <i class="fa-solid fa-check me-1"></i> Record Payment
                        </button>
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

  <!-- ================================================================= -->
  <!-- VIEW OPTION 2: SUPPLIERS GRID CARDS VIEW -->
  <!-- ================================================================= -->
  <div id="supplierViewGrid" class="supplier-view-container <?= $currentView === 'grid' ? '' : 'd-none' ?>">
    <div class="row g-3">
      <?php if (empty($suppliers)): ?>
        <div class="col-12 text-center py-5 text-muted">No external suppliers registered.</div>
      <?php else: ?>
        <?php foreach ($suppliers as $sup): ?>
          <?php
          $due = max(0, (float)$sup['total_payables'] - (float)$sup['total_paid']);
          ?>
          <div class="col-12 col-md-6 col-lg-4">
            <div class="card card-enterprise h-100 shadow-sm border">
              <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                <div>
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-light text-dark border fw-bold px-2.5 py-1">
                      <i class="fa-solid fa-handshake me-1 text-primary"></i><?= e($sup['supplier_code']) ?>
                    </span>
                    <span class="badge bg-info-subtle text-info fw-semibold px-2 py-0.5" style="font-size: 0.72rem;">
                      <i class="fa-solid fa-location-dot me-1"></i><?= e($sup['country'] ?: 'Global') ?>
                    </span>
                  </div>

                  <h6 class="fw-bold text-dark mb-1 text-truncate">
                    <?= e($sup['company_name']) ?>
                  </h6>
                  <div class="text-muted small mb-3 text-truncate" style="font-size: 0.76rem;">
                    <?= e($sup['services_provided'] ?? 'Visa Clearing & Embassy Liaison') ?>
                  </div>

                  <div class="p-2.5 bg-light rounded-3 border mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="text-muted small">Contact Person:</span>
                      <strong class="text-dark small text-truncate" style="max-width: 160px;"><?= e($sup['contact_person'] ?: 'Operations Desk') ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="text-muted small">Mobile / Phone:</span>
                      <span class="small fw-semibold text-secondary text-truncate" style="max-width: 160px;"><?= e($sup['mobile'] ?: '—') ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="text-muted small">Email:</span>
                      <span class="small text-primary text-truncate" style="max-width: 160px;"><?= e($sup['email'] ?: '—') ?></span>
                    </div>
                  </div>

                  <div class="row g-2 mb-3 text-center">
                    <div class="col-4">
                      <div class="p-2 border rounded bg-white">
                        <div class="text-muted text-uppercase" style="font-size: 0.65rem;">Payables</div>
                        <div class="fw-bold small text-danger"><?= format_currency((float)$sup['total_payables']) ?></div>
                      </div>
                    </div>
                    <div class="col-4">
                      <div class="p-2 border rounded bg-white">
                        <div class="text-muted text-uppercase" style="font-size: 0.65rem;">Settled</div>
                        <div class="fw-bold small text-success"><?= format_currency((float)$sup['total_paid']) ?></div>
                      </div>
                    </div>
                    <div class="col-4">
                      <div class="p-2 border rounded bg-white">
                        <div class="text-muted text-uppercase" style="font-size: 0.65rem;">Due</div>
                        <div class="fw-bold small <?= $due > 0 ? 'text-warning' : 'text-muted' ?>"><?= format_currency($due) ?></div>
                      </div>
                    </div>
                  </div>

                  <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary rounded-pill px-2.5 py-1 small">
                      <i class="fa-solid fa-passport me-1"></i><?= (int)$sup['total_applications'] ?> Visas
                    </span>
                  </div>
                </div>

                <div class="pt-2 border-top d-flex align-items-center justify-content-between gap-1 flex-wrap">
                  <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-sm btn-outline-success fw-semibold px-2.5" data-bs-toggle="modal" data-bs-target="#paySupplierModal<?= $sup['id'] ?>">
                      <i class="fa-solid fa-money-bill-transfer me-1"></i> Pay
                    </button>
                    <a href="/suppliers/wallet?id=<?= $sup['id'] ?>" class="btn btn-sm btn-outline-info py-1 px-2" title="Supplier Wallet">
                      <i class="fa-solid fa-wallet"></i>
                    </a>
                  </div>

                  <div class="btn-group btn-group-sm">
                    <form action="/suppliers/send-activation" method="POST" class="d-inline" onsubmit="return confirm('Send portal activation link to <?= e($sup['company_name']) ?>?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="supplier_id" value="<?= $sup['id'] ?>">
                      <button type="submit" class="btn btn-outline-info py-1 px-2" title="Send Password Setup & Activation Link">
                        <i class="fa-solid fa-paper-plane"></i>
                      </button>
                    </form>
                    <button type="button" class="btn btn-outline-warning py-1 px-2" data-bs-toggle="modal" data-bs-target="#resetSupplierPasswordModal<?= $sup['id'] ?>" title="Reset Password">
                      <i class="fa-solid fa-key"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary py-1 px-2" data-bs-toggle="modal" data-bs-target="#editSupplierModal<?= $sup['id'] ?>" title="Edit">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <form action="/suppliers/delete" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete supplier <?= e($sup['company_name']) ?>?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="supplier_id" value="<?= $sup['id'] ?>">
                      <button type="submit" class="btn btn-outline-danger py-1 px-2" title="Delete">
                        <i class="fa-solid fa-trash-can"></i>
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- ================================================================= -->
  <!-- VIEW OPTION 3: SUPPLIERS COMPACT LIST VIEW -->
  <!-- ================================================================= -->
  <div id="supplierViewCompact" class="supplier-view-container <?= $currentView === 'compact' ? '' : 'd-none' ?>">
    <div class="card card-enterprise shadow-sm border">
      <ul class="list-group list-group-flush mb-0">
        <?php if (empty($suppliers)): ?>
          <li class="list-group-item text-center py-5 text-muted">No external suppliers registered.</li>
        <?php else: ?>
          <?php foreach ($suppliers as $sup): ?>
            <?php
            $due = max(0, (float)$sup['total_payables'] - (float)$sup['total_paid']);
            ?>
            <li class="list-group-item px-3 py-2.5 hover-bg-light transition">
              <div class="row align-items-center g-2">
                <div class="col-12 col-md-4">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border fw-bold px-2 py-1"><?= e($sup['supplier_code']) ?></span>
                    <div class="min-w-0">
                      <div class="fw-bold text-dark text-truncate small"><?= e($sup['company_name']) ?></div>
                      <div class="text-muted text-truncate" style="font-size: 0.72rem;"><?= e($sup['contact_person'] ?: 'Operations Desk') ?> &bull; <?= e($sup['country'] ?: 'Global') ?></div>
                    </div>
                  </div>
                </div>

                <div class="col-6 col-md-3">
                  <div class="small text-muted" style="font-size: 0.72rem;">Payables / Settled:</div>
                  <div class="small fw-semibold text-dark">
                    <span class="text-danger"><?= format_currency((float)$sup['total_payables']) ?></span> / 
                    <span class="text-success"><?= format_currency((float)$sup['total_paid']) ?></span>
                  </div>
                </div>

                <div class="col-6 col-md-2 text-center text-md-start">
                  <div class="small text-muted" style="font-size: 0.72rem;">Balance Due:</div>
                  <span class="badge <?= $due > 0 ? 'bg-warning text-dark' : 'bg-light text-muted border' ?> fw-bold">
                    <?= format_currency($due) ?>
                  </span>
                </div>

                <div class="col-12 col-md-3 text-end">
                  <div class="d-inline-flex align-items-center gap-1">
                    <button type="button" class="btn btn-sm btn-outline-success py-1 px-2" data-bs-toggle="modal" data-bs-target="#paySupplierModal<?= $sup['id'] ?>" title="Record Payment">
                      <i class="fa-solid fa-money-bill-transfer"></i>
                    </button>
                    <a href="/suppliers/wallet?id=<?= $sup['id'] ?>" class="btn btn-sm btn-outline-info py-1 px-2" title="Wallet">
                      <i class="fa-solid fa-wallet"></i>
                    </a>
                    <form action="/suppliers/send-activation" method="POST" class="d-inline" onsubmit="return confirm('Send portal activation link to <?= e($sup['company_name']) ?>?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="supplier_id" value="<?= $sup['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-info py-1 px-2" title="Send Activation Link">
                        <i class="fa-solid fa-paper-plane"></i>
                      </button>
                    </form>
                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" data-bs-toggle="modal" data-bs-target="#editSupplierModal<?= $sup['id'] ?>" title="Edit">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <form action="/suppliers/delete" method="POST" class="d-inline" onsubmit="return confirm('Delete supplier <?= e($sup['company_name']) ?>?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="supplier_id" value="<?= $sup['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Delete">
                        <i class="fa-solid fa-trash-can"></i>
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            </li>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>
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
          <!-- Password-free invitation notice -->
          <div class="alert alert-info py-2 px-3 small mb-3 border-0 rounded-3 shadow-none">
            <i class="fa-solid fa-envelope-circle-check me-1.5 text-info"></i>
            <strong>Password-Free Onboarding:</strong> An email with a secure, single-use activation link will be automatically sent to the partner's work email to set their own password upon registration.
          </div>

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

<script>
function switchSupplierView(viewMode) {
  // Hide all view containers
  document.querySelectorAll('.supplier-view-container').forEach(el => el.classList.add('d-none'));

  // Reset button states
  document.querySelectorAll('.supplier-view-btn').forEach(btn => {
    btn.classList.remove('btn-primary', 'shadow-sm');
    btn.classList.add('btn-light', 'text-muted');
  });

  if (viewMode === 'grid') {
    const el = document.getElementById('supplierViewGrid');
    if (el) el.classList.remove('d-none');
    const btn = document.getElementById('btnSupplierViewGrid');
    if (btn) { btn.classList.remove('btn-light', 'text-muted'); btn.classList.add('btn-primary', 'shadow-sm'); }
  } else if (viewMode === 'compact') {
    const el = document.getElementById('supplierViewCompact');
    if (el) el.classList.remove('d-none');
    const btn = document.getElementById('btnSupplierViewCompact');
    if (btn) { btn.classList.remove('btn-light', 'text-muted'); btn.classList.add('btn-primary', 'shadow-sm'); }
  } else {
    const el = document.getElementById('supplierViewTable');
    if (el) el.classList.remove('d-none');
    const btn = document.getElementById('btnSupplierViewTable');
    if (btn) { btn.classList.remove('btn-light', 'text-muted'); btn.classList.add('btn-primary', 'shadow-sm'); }
  }

  try {
    localStorage.setItem('vt_supplier_view', viewMode);
  } catch(e) {}
}

document.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  const paramView = urlParams.get('view');
  if (paramView) {
    switchSupplierView(paramView);
  } else {
    try {
      const saved = localStorage.getItem('vt_supplier_view');
      if (saved) {
        switchSupplierView(saved);
      } else if (window.innerWidth < 768) {
        // Mobile view default to Grid Cards for optimal UX
        switchSupplierView('grid');
      }
    } catch(e) {}
  }
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
