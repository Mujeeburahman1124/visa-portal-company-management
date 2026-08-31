<?php
$pageTitle = e($customer['full_name']) . ' — Customer Profile';
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

  <div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center gap-3">
      <a href="/customers" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i></a>
      <div>
        <div class="d-flex align-items-center gap-2">
          <h4 class="fw-bold brand-font mb-0"><?= e($customer['full_name']) ?></h4>
          <span class="badge bg-primary"><?= e($customer['customer_code']) ?></span>
        </div>
        <div class="text-muted small"><?= e($customer['nationality']) ?> &bull; Resident of <?= e($customer['current_country']) ?></div>
      </div>
    </div>

    <div class="d-flex gap-2">
      <a href="/customers/edit?id=<?= $customer['id'] ?>" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
        <i class="fa-solid fa-pen-to-square me-1"></i> Edit Profile
      </a>
      <form action="/customers/delete" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this customer (<?= e($customer['customer_code']) ?>) and all linked applications and documents?');">
        <?= csrf_field() ?>
        <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">
        <button type="submit" class="btn btn-outline-danger btn-sm px-3 shadow-sm">
          <i class="fa-solid fa-trash-can me-1"></i> Delete
        </button>
      </form>
      <a href="/applications/create?customer_id=<?= $customer['id'] ?>" class="btn btn-primary btn-sm px-3 shadow-sm">
        <i class="fa-solid fa-plus me-1"></i> New Visa Application
      </a>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column: Customer Profile & Identity Credentials -->
    <div class="col-lg-4">
      <!-- 1. Bio & Contact Card -->
      <div class="card-custom mb-3">
        <div class="card-custom-header">
          <span class="fw-bold small text-uppercase"><i class="fa-solid fa-user text-primary me-2"></i> Personal &amp; Contact</span>
        </div>
        <div class="card-custom-body">
          <div class="mb-2">
            <span class="text-muted small">FULL NAME:</span>
            <div class="fw-bold text-dark"><?= e($customer['full_name']) ?></div>
          </div>
          <div class="row g-2 mb-2">
            <div class="col-6">
              <span class="text-muted small">DATE OF BIRTH:</span>
              <div class="fw-semibold small"><?= format_date($customer['dob']) ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">GENDER:</span>
              <div class="fw-semibold small"><?= e($customer['gender']) ?></div>
            </div>
          </div>
          <div class="row g-2 mb-2">
            <div class="col-6">
              <span class="text-muted small">PLACE OF BIRTH:</span>
              <div class="fw-semibold small"><?= e($customer['place_of_birth'] ?: '—') ?></div>
            </div>
            <div class="col-6">
              <span class="text-muted small">MARITAL STATUS:</span>
              <div class="fw-semibold small"><?= e($customer['marital_status'] ?: 'Single') ?></div>
            </div>
          </div>
          <div class="mb-2">
            <span class="text-muted small">NATIONALITY / RESIDENCE:</span>
            <div class="fw-semibold small"><?= e($customer['nationality']) ?> &bull; <span class="text-primary">Resident of <?= e($customer['current_country']) ?></span></div>
          </div>
          <div class="mb-2">
            <span class="text-muted small">OCCUPATION / TITLE:</span>
            <div class="fw-semibold small"><?= e($customer['occupation'] ?: '—') ?></div>
          </div>
          <div class="mb-2">
            <span class="text-muted small">MOBILE PHONE:</span>
            <div class="fw-semibold small"><a href="tel:<?= e($customer['mobile']) ?>" class="text-decoration-none"><i class="fa-solid fa-phone me-1 text-primary"></i><?= e($customer['mobile']) ?></a></div>
          </div>
          <div class="mb-2">
            <span class="text-muted small">WHATSAPP:</span>
            <div class="fw-semibold small"><a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $customer['whatsapp'] ?: $customer['mobile']) ?>" target="_blank" class="text-decoration-none text-success"><i class="fa-brands fa-whatsapp me-1"></i><?= e($customer['whatsapp'] ?: $customer['mobile']) ?></a></div>
          </div>
          <div class="mb-2">
            <span class="text-muted small">EMAIL:</span>
            <div class="fw-semibold small"><a href="mailto:<?= e($customer['email']) ?>" class="text-decoration-none"><i class="fa-solid fa-envelope me-1 text-info"></i><?= e($customer['email'] ?: '—') ?></a></div>
          </div>
          <div class="mb-0">
            <span class="text-muted small">ADDRESS:</span>
            <div class="small text-muted"><?= e($customer['address'] ?: '—') ?></div>
          </div>
        </div>
      </div>

      <!-- 2. Passports List -->
      <div class="card-custom mb-3">
        <div class="card-custom-header">
          <span class="fw-bold small text-uppercase"><i class="fa-solid fa-passport text-primary me-2"></i> Passports (<?= count($passports) ?>)</span>
        </div>
        <div class="card-custom-body p-0">
          <ul class="list-group list-group-flush small">
            <?php if (empty($passports)): ?>
              <li class="list-group-item text-muted py-3 text-center">No passports on record</li>
            <?php else: ?>
              <?php foreach ($passports as $p): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <div class="fw-bold text-dark font-monospace"><?= e($p['passport_number']) ?> <?= $p['is_primary'] ? '<span class="badge bg-success" style="font-size: 0.65rem;">Primary</span>' : '' ?></div>
                    <div class="text-muted small"><?= e($p['issuing_country']) ?> &bull; Issued: <?= format_date($p['issue_date']) ?> &bull; Exp: <?= format_date($p['expiry_date']) ?></div>
                    <?php if (!empty($p['place_of_issue'])): ?>
                      <div class="text-muted small">Place: <?= e($p['place_of_issue']) ?></div>
                    <?php endif; ?>
                  </div>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </div>
      </div>

      <!-- 3. National IDs & Emirates ID (with downloads) -->
      <div class="card-custom mb-3">
        <div class="card-custom-header">
          <span class="fw-bold small text-uppercase"><i class="fa-solid fa-id-card text-success me-2"></i> National / Emirates ID</span>
        </div>
        <div class="card-custom-body p-0">
          <ul class="list-group list-group-flush small">
            <?php if (empty($nationalIds)): ?>
              <li class="list-group-item text-muted py-3 text-center">No National IDs registered</li>
            <?php else: ?>
              <?php foreach ($nationalIds as $nid): ?>
                <li class="list-group-item">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold text-dark"><?= e($nid['id_type'] ?? 'National ID') ?></span>
                    <span class="badge bg-light text-dark border font-monospace"><?= e($nid['id_number']) ?></span>
                  </div>
                  <div class="text-muted small mb-2">
                    Country: <?= e($nid['issuing_country']) ?> 
                    <?php if (!empty($nid['expiry_date'])): ?> &bull; Exp: <?= format_date($nid['expiry_date']) ?><?php endif; ?>
                  </div>
                  <?php if (!empty($nid['front_file']) || !empty($nid['back_file'])): ?>
                    <div class="d-flex gap-2">
                      <?php if (!empty($nid['front_file'])): ?>
                        <a href="<?= e($nid['front_file']) ?>" target="_blank" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 0.72rem;">
                          <i class="fa-solid fa-file-image me-1"></i> Front Doc
                        </a>
                      <?php endif; ?>
                      <?php if (!empty($nid['back_file'])): ?>
                        <a href="<?= e($nid['back_file']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 0.72rem;">
                          <i class="fa-solid fa-file-image me-1"></i> Back Doc
                        </a>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </div>
      </div>

      <!-- 4. Residence Details -->
      <div class="card-custom mb-3">
        <div class="card-custom-header">
          <span class="fw-bold small text-uppercase"><i class="fa-solid fa-house-user text-warning me-2"></i> Residence Permits</span>
        </div>
        <div class="card-custom-body p-0">
          <ul class="list-group list-group-flush small">
            <?php if (empty($residences)): ?>
              <li class="list-group-item text-muted py-3 text-center">No residence permits recorded</li>
            <?php else: ?>
              <?php foreach ($residences as $res): ?>
                <li class="list-group-item">
                  <div class="fw-bold text-dark font-monospace"><?= e($res['permit_number'] ?: 'Permit on file') ?></div>
                  <div class="text-muted small"><?= e($res['residence_country']) ?> &bull; Exp: <?= format_date($res['expiry_date']) ?></div>
                  <?php if (!empty($res['employer'])): ?>
                    <div class="text-muted small">Employer: <strong><?= e($res['employer']) ?></strong> (<?= e($res['job_title'] ?: 'Staff') ?>)</div>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </div>
      </div>

      <!-- 5. Family Details Card -->
      <div class="card-custom mb-3">
        <div class="card-custom-header">
          <span class="fw-bold small text-uppercase"><i class="fa-solid fa-people-roof text-primary me-2"></i> Family Information</span>
        </div>
        <div class="card-custom-body">
          <?php if (empty($family) || (empty($family['father_name']) && empty($family['mother_name']))): ?>
            <div class="text-muted small py-2 text-center">No family details recorded</div>
          <?php else: ?>
            <div class="mb-2 border-bottom pb-2">
              <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Father Details:</span>
              <div class="fw-bold text-dark"><?= e($family['father_name'] ?: '—') ?></div>
              <div class="small text-muted">
                DOB: <?= format_date($family['father_dob'] ?? null) ?> &bull; Country: <?= e($family['father_country_of_birth'] ?? '—') ?>
              </div>
              <?php if (!empty($family['father_religion'])): ?>
                <div class="small text-muted">Religion: <?= e($family['father_religion']) ?></div>
              <?php endif; ?>
            </div>
            <div>
              <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Mother Details:</span>
              <div class="fw-bold text-dark"><?= e($family['mother_name'] ?: '—') ?></div>
              <div class="small text-muted">
                DOB: <?= format_date($family['mother_dob'] ?? null) ?> &bull; Phone: <?= e($family['mother_mobile'] ?? '—') ?>
              </div>
              <?php if (!empty($family['mother_religion'])): ?>
                <div class="small text-muted">Religion: <?= e($family['mother_religion']) ?></div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- 6. Customer Digital Wallet Card -->
      <div class="card-custom">
        <div class="card-custom-header d-flex align-items-center justify-content-between">
          <span class="fw-bold small text-uppercase"><i class="fa-solid fa-wallet text-success me-2"></i> Digital Wallet</span>
          <span class="badge bg-success-subtle text-success border">Active</span>
        </div>
        <div class="card-custom-body">
          <div class="d-flex justify-content-between align-items-baseline mb-2">
            <span class="text-muted small">Current Balance:</span>
            <span class="fw-bold text-success fs-5"><?= format_currency((float)($wallet['current_balance'] ?? 0)) ?></span>
          </div>
          <div class="row g-2 small text-muted border-top pt-2">
            <div class="col-6">Total Credited: <strong><?= format_currency((float)($wallet['total_credited'] ?? 0)) ?></strong></div>
            <div class="col-6 text-end">Total Debited: <strong><?= format_currency((float)($wallet['total_debited'] ?? 0)) ?></strong></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: 5 Full History Tabs -->
    <div class="col-lg-8">
      <div class="card card-enterprise shadow-sm">
        <div class="card-header bg-white border-bottom p-0">
          <ul class="nav nav-tabs card-header-tabs m-0 px-3" id="custTabs" role="tablist">
            <li class="nav-item">
              <button class="nav-link active py-3 fw-semibold small" data-bs-toggle="tab" data-bs-target="#tab-apps">
                <i class="fa-solid fa-folder-open text-primary me-1"></i> Applications (<?= count($applications) ?>)
              </button>
            </li>
            <li class="nav-item">
              <button class="nav-link py-3 fw-semibold small" data-bs-toggle="tab" data-bs-target="#tab-docs">
                <i class="fa-solid fa-file-shield text-warning me-1"></i> Documents (<?= count($documents) ?>)
              </button>
            </li>
            <li class="nav-item">
              <button class="nav-link py-3 fw-semibold small" data-bs-toggle="tab" data-bs-target="#tab-pays">
                <i class="fa-solid fa-receipt text-success me-1"></i> Payments (<?= count($payments) ?>)
              </button>
            </li>
            <li class="nav-item">
              <button class="nav-link py-3 fw-semibold small" data-bs-toggle="tab" data-bs-target="#tab-comms">
                <i class="fa-solid fa-comments text-info me-1"></i> Communications (<?= count($communications) ?>)
              </button>
            </li>
            <li class="nav-item">
              <button class="nav-link py-3 fw-semibold small" data-bs-toggle="tab" data-bs-target="#tab-tasks">
                <i class="fa-solid fa-list-check text-secondary me-1"></i> Tasks (<?= count($tasks) ?>)
              </button>
            </li>
          </ul>
        </div>

        <div class="card-body p-3">
          <div class="tab-content">
            <!-- 1. Applications Tab -->
            <div class="tab-pane fade show active" id="tab-apps">
              <div class="table-responsive">
                <table class="table-modern mb-0">
                  <thead><tr><th>Application #</th><th>Country / Service</th><th>Stage</th><th>Status</th><th>Total / Balance</th><th class="text-end">Action</th></tr></thead>
                  <tbody>
                    <?php if (empty($applications)): ?>
                      <tr><td colspan="6" class="text-center py-4 text-muted">No visa applications registered yet.</td></tr>
                    <?php else: ?>
                      <?php foreach ($applications as $app): ?>
                        <tr>
                          <td><a href="/applications/show?id=<?= $app['id'] ?>" class="badge bg-primary-subtle text-primary fw-bold text-decoration-none px-2 py-1"><i class="fa-solid fa-folder me-1"></i><?= e($app['application_number']) ?></a></td>
                          <td>
                            <div class="fw-semibold text-dark"><?= $app['flag_emoji'] ?> <?= e($app['service_name']) ?></div>
                            <div class="text-muted small" style="font-size: 0.72rem;"><?= format_date($app['application_date']) ?></div>
                          </td>
                          <td><span class="badge bg-light text-dark border"><?= e($app['current_stage']) ?></span></td>
                          <td><span class="badge bg-primary"><?= e($app['status']) ?></span></td>
                          <td>
                            <div class="fw-bold"><?= format_currency((float)$app['total_amount']) ?></div>
                            <?php if ((float)$app['balance_amount'] > 0): ?>
                              <div class="text-danger small fw-semibold" style="font-size: 0.72rem;">Bal: <?= format_currency((float)$app['balance_amount']) ?></div>
                            <?php endif; ?>
                          </td>
                          <td class="text-end">
                            <a href="/applications/show?id=<?= $app['id'] ?>" class="btn btn-outline-primary btn-sm py-1 px-2">
                              <i class="fa-solid fa-arrow-right"></i>
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- 2. Documents Tab -->
            <div class="tab-pane fade" id="tab-docs">
              <div class="table-responsive">
                <table class="table-modern mb-0">
                  <thead><tr><th>Document</th><th>File Name</th><th>Status</th><th>Application</th><th>Uploaded</th><th class="text-end">Action</th></tr></thead>
                  <tbody>
                    <?php if (empty($documents)): ?>
                      <tr><td colspan="6" class="text-center py-4 text-muted">No documents uploaded yet.</td></tr>
                    <?php else: ?>
                      <?php foreach ($documents as $doc): ?>
                        <tr>
                          <td class="fw-bold text-dark"><?= e($doc['doc_type_name'] ?? 'Document') ?></td>
                          <td class="small font-monospace"><?= e($doc['file_name']) ?></td>
                          <td><span class="badge <?= $doc['status'] === 'APPROVED' ? 'bg-success' : 'bg-warning' ?>"><?= e($doc['status']) ?></span></td>
                          <td><span class="badge bg-light text-dark border"><?= e($doc['application_number'] ?: 'Customer File') ?></span></td>
                          <td class="small text-muted"><?= format_date($doc['created_at']) ?></td>
                          <td class="text-end">
                            <?php if (!empty($doc['file_path'])): ?>
                              <a href="<?= e($doc['file_path']) ?>" target="_blank" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 0.75rem;">
                                <i class="fa-solid fa-download me-1"></i> View
                              </a>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- 3. Payments Tab -->
            <div class="tab-pane fade" id="tab-pays">
              <div class="table-responsive">
                <table class="table-modern mb-0">
                  <thead><tr><th>Receipt #</th><th>Date</th><th>Amount</th><th>Method</th><th>App #</th><th class="text-end">Receipt</th></tr></thead>
                  <tbody>
                    <?php if (empty($payments)): ?>
                      <tr><td colspan="6" class="text-center py-4 text-muted">No payment transactions recorded.</td></tr>
                    <?php else: ?>
                      <?php foreach ($payments as $pay): ?>
                        <tr>
                          <td class="fw-bold text-primary"><?= e($pay['payment_number']) ?></td>
                          <td><?= format_date($pay['payment_date']) ?></td>
                          <td class="fw-bold text-success"><?= format_currency((float)$pay['amount']) ?></td>
                          <td><span class="badge bg-light text-dark border"><?= e($pay['payment_method']) ?></span></td>
                          <td><span class="badge bg-primary-subtle text-primary"><?= e($pay['application_number'] ?: 'General') ?></span></td>
                          <td class="text-end">
                            <a href="/payments/receipt?id=<?= $pay['id'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 0.75rem;">
                              <i class="fa-solid fa-print me-1"></i> Print
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- 4. Communications Tab -->
            <div class="tab-pane fade" id="tab-comms">
              <div class="table-responsive">
                <table class="table-modern mb-0">
                  <thead><tr><th>Channel</th><th>Subject / Summary</th><th>Staff</th><th>Date</th></tr></thead>
                  <tbody>
                    <?php if (empty($communications)): ?>
                      <tr><td colspan="4" class="text-center py-4 text-muted">No communication logs recorded.</td></tr>
                    <?php else: ?>
                      <?php foreach ($communications as $comm): ?>
                        <tr>
                          <td><span class="badge bg-info-subtle text-info fw-bold"><?= e($comm['channel']) ?></span></td>
                          <td>
                            <div class="fw-semibold text-dark"><?= e($comm['subject']) ?></div>
                            <div class="text-muted small"><?= e($comm['message']) ?></div>
                          </td>
                          <td><?= e($comm['staff_name'] ?: 'System') ?></td>
                          <td class="small text-muted"><?= format_datetime($comm['recorded_at']) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- 5. Tasks Tab -->
            <div class="tab-pane fade" id="tab-tasks">
              <div class="table-responsive">
                <table class="table-modern mb-0">
                  <thead><tr><th>Task</th><th>Priority</th><th>Due Date</th><th>Assigned To</th><th>Status</th></tr></thead>
                  <tbody>
                    <?php if (empty($tasks)): ?>
                      <tr><td colspan="5" class="text-center py-4 text-muted">No tasks assigned for this customer.</td></tr>
                    <?php else: ?>
                      <?php foreach ($tasks as $tsk): ?>
                        <tr>
                          <td class="fw-semibold text-dark"><?= e($tsk['task_title'] ?? $tsk['title'] ?? 'Task') ?></td>
                          <td><span class="badge bg-light text-dark border"><?= e($tsk['priority']) ?></span></td>
                          <td class="small"><?= format_date($tsk['due_date']) ?></td>
                          <td><?= e($tsk['assigned_to_name'] ?: 'Unassigned') ?></td>
                          <td><span class="badge bg-secondary"><?= e($tsk['status']) ?></span></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
