<?php
$pageTitle = 'Visa Tracking & Journey Center — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';

$activeStage = $_GET['stage'] ?? '';
$viewMode = $_GET['view'] ?? 'table'; // 'table' or 'timeline'
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

  <!-- Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <h3 class="fw-bold brand-font text-dark mb-0">Visa Tracking &amp; Lifecycle Center</h3>
      <p class="text-muted small mb-0">Live tracking by date, applicant name, passport, phone, email, visa number, and destination.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <div class="btn-group btn-group-sm" role="group">
        <a href="?<?= http_build_query(array_merge($_GET, ['view' => 'table'])) ?>" class="btn <?= $viewMode === 'table' ? 'btn-primary' : 'btn-outline-secondary' ?>">
          <i class="fa-solid fa-table-list me-1"></i> Table View
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['view' => 'timeline'])) ?>" class="btn <?= $viewMode === 'timeline' ? 'btn-primary' : 'btn-outline-secondary' ?>">
          <i class="fa-solid fa-timeline me-1"></i> Visual Timeline
        </a>
      </div>
      <a href="/applications/create" class="btn btn-success btn-sm">
        <i class="fa-solid fa-plus me-1"></i> New Application
      </a>
    </div>
  </div>

  <!-- Multi-Criteria Advanced Filter Panel (100% Responsive) -->
  <div class="card card-enterprise mb-4 border-0 shadow-sm">
    <div class="card-body p-3">
      <form action="/tracking" method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode) ?>">

        <!-- Row 1: Key Identifiers -->
        <div class="col-12 col-sm-6 col-md-4 col-xl-3">
          <label class="form-label small fw-semibold text-secondary mb-1">Customer / Applicant Name</label>
          <input type="text" name="name" class="form-control form-control-sm bg-light" placeholder="Search name..." value="<?= htmlspecialchars($_GET['name'] ?? '') ?>">
        </div>

        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <label class="form-label small fw-semibold text-secondary mb-1">Passport Number</label>
          <input type="text" name="passport" class="form-control form-control-sm bg-light font-monospace" placeholder="Passport #..." value="<?= htmlspecialchars($_GET['passport'] ?? '') ?>">
        </div>

        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <label class="form-label small fw-semibold text-secondary mb-1">Mobile / WhatsApp</label>
          <input type="text" name="phone" class="form-control form-control-sm bg-light" placeholder="Phone #..." value="<?= htmlspecialchars($_GET['phone'] ?? '') ?>">
        </div>

        <div class="col-12 col-sm-6 col-md-4 col-xl-3">
          <label class="form-label small fw-semibold text-secondary mb-1">Email Address</label>
          <input type="email" name="email" class="form-control form-control-sm bg-light" placeholder="Email..." value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">
        </div>

        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <label class="form-label small fw-semibold text-secondary mb-1">Visa Number</label>
          <input type="text" name="visa_number" class="form-control form-control-sm bg-light font-monospace" placeholder="Visa #..." value="<?= htmlspecialchars($_GET['visa_number'] ?? '') ?>">
        </div>

        <!-- Row 2: Dates, Destination, Stage, Supplier -->
        <div class="col-6 col-sm-6 col-md-3 col-xl-2">
          <label class="form-label small fw-semibold text-secondary mb-1">Date From</label>
          <input type="date" name="date_from" class="form-control form-control-sm bg-light" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
        </div>

        <div class="col-6 col-sm-6 col-md-3 col-xl-2">
          <label class="form-label small fw-semibold text-secondary mb-1">Date To</label>
          <input type="date" name="date_to" class="form-control form-control-sm bg-light" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
        </div>

        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <label class="form-label small fw-semibold text-secondary mb-1">Destination Country</label>
          <select name="country_id" class="form-select form-select-sm bg-light">
            <option value="">All Countries</option>
            <?php foreach ($countriesList as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ((int)($_GET['country_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                <?= $c['flag_emoji'] ?> <?= e($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <label class="form-label small fw-semibold text-secondary mb-1">Lifecycle Stage</label>
          <select name="stage" class="form-select form-select-sm bg-light">
            <option value="">All Stages</option>
            <?php foreach ($stages as $stg): ?>
              <?php if ($stg !== 'All'): ?>
                <option value="<?= e($stg) ?>" <?= (($_GET['stage'] ?? '') === $stg) ? 'selected' : '' ?>>
                  <?= e($stg) ?>
                </option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
          <label class="form-label small fw-semibold text-secondary mb-1">Assigned Staff</label>
          <select name="staff_id" class="form-select form-select-sm bg-light">
            <option value="">All Staff</option>
            <?php foreach ($staffList as $u): ?>
              <option value="<?= $u['id'] ?>" <?= ((int)($_GET['staff_id'] ?? 0) === (int)$u['id']) ? 'selected' : '' ?>>
                <?= e($u['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Joined Action Buttons (Filter + Clear) -->
        <div class="col-12 col-md-4 col-xl-auto ms-auto d-flex justify-content-end">
          <div class="btn-group btn-group-sm w-100 w-md-auto shadow-sm" role="group" aria-label="Filter Controls">
            <button type="submit" class="btn btn-primary px-3 fw-semibold">
              <i class="fa-solid fa-filter me-1.5"></i> Filter
            </button>
            <a href="/tracking" class="btn btn-primary border-start border-white border-opacity-25 px-2.5" title="Reset Filters">
              <i class="fa-solid fa-rotate-left"></i>
            </a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Results Count Badge -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="small text-muted">
      Showing <strong><?= count($applications) ?></strong> of <strong><?= $totalRecords ?></strong> tracking cases
    </div>
  </div>

  <?php if (empty($applications)): ?>
    <div class="card card-enterprise border-0 shadow-sm text-center py-5">
      <div class="card-body">
        <i class="fa-solid fa-route fs-1 text-muted opacity-50 mb-3"></i>
        <h5 class="fw-bold text-dark">No tracking records found</h5>
        <p class="text-muted small">No applications match your specific search and filter criteria.</p>
        <a href="/tracking" class="btn btn-outline-secondary btn-sm">Clear All Filters</a>
      </div>
    </div>
  <?php else: ?>

    <?php if ($viewMode === 'table'): ?>
      <!-- TABULAR TRACKING VIEW (100% Responsive) -->
      <div class="card card-enterprise border-0 shadow-sm mb-4">
        <div class="table-responsive" style="-webkit-overflow-scrolling: touch;">
          <table class="table table-custom table-hover align-middle mb-0" style="min-width: 1050px;">
            <thead class="table-light">
              <tr class="small text-muted text-uppercase">
                <th style="min-width: 130px;">App Ref &amp; Date</th>
                <th style="min-width: 160px;">Customer / Applicant</th>
                <th style="min-width: 110px;">Passport #</th>
                <th style="min-width: 170px;">Phone &amp; Email</th>
                <th style="min-width: 180px;">Destination &amp; Visa Type</th>
                <th style="min-width: 120px;">Visa Number</th>
                <th style="min-width: 160px;">Current Status / Stage</th>
                <th style="min-width: 160px;">Staff &amp; Supplier</th>
                <th style="min-width: 80px;">Health</th>
                <th class="text-end" style="min-width: 90px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($applications as $app): 
                $healthClass = 'bg-success';
                if ((int)$app['calculated_health'] < 50) $healthClass = 'bg-danger';
                elseif ((int)$app['calculated_health'] < 80) $healthClass = 'bg-warning';
              ?>
                <tr>
                  <td>
                    <a href="/applications/show?id=<?= $app['id'] ?>" class="fw-bold text-primary text-decoration-none text-nowrap">
                      <?= e($app['application_number']) ?>
                    </a>
                    <div class="small text-muted text-nowrap"><?= date('M d, Y', strtotime($app['application_date'] ?? $app['created_at'])) ?></div>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark"><?= e($app['customer_name']) ?></div>
                    <div class="small text-muted"><?= e($app['customer_code']) ?></div>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark font-monospace border"><?= e($app['passport_number'] ?: '—') ?></span>
                  </td>
                  <td>
                    <div class="small text-nowrap"><i class="fa-solid fa-phone text-muted me-1"></i><?= e($app['customer_mobile'] ?: '—') ?></div>
                    <div class="small text-muted text-truncate" style="max-width: 160px;" title="<?= e($app['customer_email']) ?>"><i class="fa-solid fa-envelope me-1"></i><?= e($app['customer_email'] ?: '—') ?></div>
                  </td>
                  <td>
                    <div><?= $app['flag_emoji'] ?> <strong><?= e($app['country_name']) ?></strong></div>
                    <div class="small text-muted"><?= e($app['service_name']) ?> (<?= e($app['duration'] ?? 'Standard') ?>)</div>
                  </td>
                  <td>
                    <?php if (!empty($app['visa_number'])): ?>
                      <span class="badge bg-success-subtle text-success border border-success font-monospace text-nowrap">
                        <i class="fa-solid fa-stamp me-1"></i><?= e($app['visa_number']) ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted small">Pending</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 text-nowrap">
                      <?= e($app['current_stage']) ?>
                    </span>
                    <div class="small text-muted mt-1 text-nowrap">Status: <?= e($app['status']) ?></div>
                  </td>
                  <td>
                    <div class="small text-dark fw-medium text-nowrap"><i class="fa-solid fa-user-tie text-secondary me-1"></i><?= e($app['staff_name'] ?? 'Unassigned') ?></div>
                    <div class="small text-muted text-nowrap"><i class="fa-solid fa-building me-1"></i><?= e($app['supplier_name'] ?? 'In-House') ?></div>
                  </td>
                  <td>
                    <span class="badge <?= $healthClass ?> text-white" title="<?= e($app['health_reason'] ?? 'Good health') ?>">
                      <?= (int)$app['calculated_health'] ?>%
                    </span>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="/applications/show?id=<?= $app['id'] ?>" class="btn btn-outline-primary" title="View Application Details">
                        <i class="fa-solid fa-folder-open"></i>
                      </a>
                      <a href="/applications/show?id=<?= $app['id'] ?>#history-pane" class="btn btn-outline-secondary" title="View Status Timeline">
                        <i class="fa-solid fa-timeline"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php else: ?>

      <!-- VISUAL TIMELINE CARDS VIEW -->
      <?php foreach ($applications as $app): ?>
        <?php
          $isAppApproved = ($app['status'] === 'Approved' || $app['status'] === 'Completed');
          $isAppReturned = ($app['status'] === 'Action Required' || str_contains(strtolower($app['current_stage']), 'returned'));
          $healthBadge = ((int)$app['calculated_health'] < 50) ? 'health-critical' : (((int)$app['calculated_health'] < 80) ? 'health-at-risk' : 'health-healthy');
        ?>
        <div class="card card-enterprise mb-4 border-0 shadow-sm">
          <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between gap-3 py-3 border-bottom">
            <div class="d-flex align-items-center gap-3">
              <a href="/applications/show?id=<?= $app['id'] ?>" class="fw-bold fs-6 text-primary text-decoration-none">
                <?= e($app['application_number']) ?>
              </a>
              <span class="fs-5"><?= $app['flag_emoji'] ?></span>
              <span class="fw-semibold text-dark"><?= e($app['customer_name']) ?></span>
              <span class="badge bg-light text-secondary border font-monospace"><?= e($app['passport_number']) ?></span>
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-primary bg-opacity-10 text-primary border"><?= e($app['current_stage']) ?></span>
              <a href="/applications/show?id=<?= $app['id'] ?>" class="btn btn-sm btn-outline-primary">
                <i class="fa-solid fa-arrow-right me-1"></i> Open Workspace
              </a>
            </div>
          </div>
          <div class="card-body p-4">
            <div class="row g-3">
              <div class="col-md-3">
                <div class="small text-muted">Destination &amp; Visa Type</div>
                <div class="fw-bold text-dark"><?= e($app['country_name']) ?> &bull; <?= e($app['service_name']) ?></div>
              </div>
              <div class="col-md-3">
                <div class="small text-muted">Contact Info</div>
                <div class="small text-dark"><?= e($app['customer_mobile']) ?> &bull; <?= e($app['customer_email']) ?></div>
              </div>
              <div class="col-md-3">
                <div class="small text-muted">Assigned Team</div>
                <div class="small text-dark">Staff: <strong><?= e($app['staff_name'] ?? 'Unassigned') ?></strong> &bull; Supplier: <?= e($app['supplier_name'] ?? 'In-House') ?></div>
              </div>
              <div class="col-md-3">
                <div class="small text-muted">Health &amp; SLA Target</div>
                <div class="small">Score: <strong><?= (int)$app['calculated_health'] ?>%</strong> &bull; Target: <?= e($app['expected_completion_date'] ?? 'N/A') ?></div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

    <?php endif; ?>

    <!-- Pagination Controls -->
    <?php if ($totalPages > 1): ?>
      <nav class="d-flex justify-content-center my-4">
        <ul class="pagination pagination-sm">
          <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
          </li>
          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
              <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>

  <?php endif; ?>

</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
