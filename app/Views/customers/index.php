<?php
$pageTitle = 'Customers Directory — VISA TRACK';
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
      <h3 class="fw-bold brand-font mb-0">Customer & Applicant Profiles</h3>
      <p class="text-muted small mb-0">Directory of registered visa applicants, corporate sponsors, and travel histories.</p>
    </div>
    <a href="/customers/create" class="btn btn-primary btn-sm px-3 shadow-sm">
      <i class="fa-solid fa-user-plus me-1"></i> Register Customer
    </a>
  </div>

  <div class="card-custom mb-4 bg-white">
    <div class="card-custom-body p-3">
      <form action="/customers" method="GET" class="row g-2 align-items-center">
        <div class="col-md-5">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Search name, code, passport, mobile, email..." value="<?= e($_GET['search'] ?? '') ?>">
          </div>
        </div>
        <div class="col-md-3">
          <select name="nationality" class="form-select form-select-sm">
            <option value="">All Nationalities</option>
            <?php foreach ($nationalities as $nat): ?>
              <option value="<?= e($nat) ?>" <?= ($_GET['nationality'] ?? '') === $nat ? 'selected' : '' ?>><?= e($nat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
          <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
          <a href="/customers" class="btn btn-outline-secondary btn-sm" title="Clear"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
      </form>
    </div>
  </div>

  <div class="card-custom">
    <div class="table-responsive">
      <table class="table table-custom align-middle mb-0">
        <thead>
          <tr>
            <th>Customer ID</th>
            <th>Applicant Name</th>
            <th>Primary Passport</th>
            <th>Nationality</th>
            <th>Current Country</th>
            <th>Mobile / WhatsApp</th>
            <th>Applications</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($customers)): ?>
            <tr><td colspan="8" class="text-center py-5 text-muted">No customers found.</td></tr>
          <?php else: ?>
            <?php foreach ($customers as $c): ?>
              <tr>
                <td><span class="fw-bold text-primary"><?= e($c['customer_code']) ?></span></td>
                <td>
                  <a href="/customers/show?id=<?= $c['id'] ?>" class="fw-semibold text-dark text-decoration-none">
                    <?= e($c['full_name']) ?>
                  </a>
                  <div class="text-muted small"><?= e($c['email']) ?></div>
                </td>
                <td>
                  <span class="badge bg-light text-dark border"><?= e($c['passport_number'] ?? 'PENDING') ?></span>
                </td>
                <td><?= e($c['nationality']) ?></td>
                <td><?= e($c['current_country']) ?></td>
                <td>
                  <div><?= e($c['mobile']) ?></div>
                </td>
                <td>
                  <span class="badge bg-primary rounded-pill"><?= $c['total_applications'] ?> Total</span>
                  <?php if ($c['active_applications'] > 0): ?>
                    <span class="badge bg-warning text-dark rounded-pill ms-1"><?= $c['active_applications'] ?> Active</span>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <a href="/customers/show?id=<?= $c['id'] ?>" class="btn btn-outline-primary btn-sm py-1 px-2">
                    <i class="fa-solid fa-user me-1"></i> View Profile
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
