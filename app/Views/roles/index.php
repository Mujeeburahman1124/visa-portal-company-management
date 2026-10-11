<?php
$pageTitle = 'Security Roles & Permissions — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';

$totalRolesCount = count($roles);
$totalStaffCount = array_sum(array_column($roles, 'user_count'));
$totalModulesCount = count($modulesList ?? []);
?>

<!-- Link Dedicated Bento CSS -->
<link rel="stylesheet" href="/assets/css/dashboard-bento.css">
<link rel="stylesheet" href="/assets/css/pages/roles-index.css">

<div class="content-body roles-dashboard">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Bento Hero Header -->
  <div class="roles-hero">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
          <h1 class="roles-hero-title mb-0">
            <i class="fa-solid fa-shield-halved text-danger"></i> Security Roles &amp; Granular Permissions
          </h1>
          <span class="roles-hero-badge">
            <i class="fa-solid fa-lock me-1"></i> RBAC Security Matrix
          </span>
        </div>
        <p class="roles-hero-desc">
          Enforce role-based operational boundaries, define modular capabilities (View, Create, Edit, Delete, Approve, Export), and isolate branch scopes.
        </p>
      </div>

      <div class="d-flex align-items-center gap-2 flex-wrap">
        <button type="button" class="btn btn-outline-light btn-sm px-3 shadow-sm rounded-pill fw-semibold" data-bs-toggle="modal" data-bs-target="#createModuleModal">
          <i class="fa-solid fa-folder-plus me-1 text-danger"></i> Add Module
        </button>
        <button type="button" class="btn btn-outline-light btn-sm px-3 shadow-sm rounded-pill fw-semibold" data-bs-toggle="modal" data-bs-target="#createPermissionModal">
          <i class="fa-solid fa-key me-1 text-warning"></i> Add Permission
        </button>
        <button type="button" class="btn btn-primary btn-sm px-3.5 shadow-sm rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#createRoleModal">
          <i class="fa-solid fa-shield-plus me-1"></i> Create Custom Role
        </button>
      </div>
    </div>
  </div>

  <!-- Bento KPI Metrics Strip -->
  <div class="bento-role-kpi-grid">
    <!-- Card 1: Total Roles -->
    <div class="bento-role-kpi-card" style="--kpi-accent: #E11D48; --kpi-icon-bg: #FFF1F2; --kpi-icon-color: #E11D48;">
      <div class="bento-role-kpi-header">
        <span class="bento-role-kpi-title">Security Roles</span>
        <div class="bento-role-kpi-icon">
          <i class="fa-solid fa-shield-halved"></i>
        </div>
      </div>
      <div class="bento-role-kpi-value"><?= $totalRolesCount ?></div>
      <div class="bento-role-kpi-footer">
        <i class="fa-solid fa-user-lock text-danger"></i>
        <span>Defined operational personas</span>
      </div>
    </div>

    <!-- Card 2: Permissions Count -->
    <div class="bento-role-kpi-card" style="--kpi-accent: #10B981; --kpi-icon-bg: #ECFDF5; --kpi-icon-color: #059669;">
      <div class="bento-role-kpi-header">
        <span class="bento-role-kpi-title">Permissions Rules</span>
        <div class="bento-role-kpi-icon">
          <i class="fa-solid fa-key"></i>
        </div>
      </div>
      <div class="bento-role-kpi-value text-success"><?= $totalPermissions ?></div>
      <div class="bento-role-kpi-footer">
        <i class="fa-solid fa-sliders text-success"></i>
        <span>Fine-grained security gates</span>
      </div>
    </div>

    <!-- Card 3: Modules Count -->
    <div class="bento-role-kpi-card" style="--kpi-accent: #0284C7; --kpi-icon-bg: #F0F9FF; --kpi-icon-color: #0284C7;">
      <div class="bento-role-kpi-header">
        <span class="bento-role-kpi-title">System Modules</span>
        <div class="bento-role-kpi-icon">
          <i class="fa-solid fa-boxes-stacked"></i>
        </div>
      </div>
      <div class="bento-role-kpi-value text-primary"><?= $totalModulesCount ?></div>
      <div class="bento-role-kpi-footer">
        <i class="fa-solid fa-layer-group text-primary"></i>
        <span>Registered enterprise packages</span>
      </div>
    </div>

    <!-- Card 4: Assigned Officers -->
    <div class="bento-role-kpi-card" style="--kpi-accent: #8B5CF6; --kpi-icon-bg: #F5F3FF; --kpi-icon-color: #7C3AED;">
      <div class="bento-role-kpi-header">
        <span class="bento-role-kpi-title">Assigned Personnel</span>
        <div class="bento-role-kpi-icon">
          <i class="fa-solid fa-users"></i>
        </div>
      </div>
      <div class="bento-role-kpi-value" style="color: #7C3AED;"><?= $totalStaffCount ?></div>
      <div class="bento-role-kpi-footer">
        <i class="fa-solid fa-user-check text-purple"></i>
        <span>Active personnel mapped to RBAC</span>
      </div>
    </div>
  </div>

  <!-- Role Cards Grid -->
  <div class="row g-3 mb-4">
    <?php foreach ($roles as $r): ?>
      <?php
        $permPct = $totalPermissions > 0 ? (int)round(((int)$r['permission_count'] / $totalPermissions) * 100) : 0;
        $isSuper = ($r['slug'] === 'super-admin');
        $isCustom = ((int)$r['id'] > 11);
      ?>
      <div class="col-12 col-md-6 col-xl-4">
        <div class="bento-role-card">
          <div>
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="role-slug-chip <?= $isSuper ? 'super-admin' : 'standard' ?>">
                <i class="fa-solid <?= $isSuper ? 'fa-crown' : 'fa-shield-halved' ?> me-1"></i><?= e($r['slug']) ?>
              </span>
              <div class="d-flex align-items-center gap-1">
                <span class="badge bg-light text-dark border font-monospace small px-2 py-1 rounded-pill">
                  <i class="fa-solid fa-users me-1 text-muted"></i><?= (int)$r['user_count'] ?> Officers
                </span>
                <?php if (!$isSuper && $isCustom): ?>
                  <form action="/roles/delete" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this custom role?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm p-1 py-0 rounded-circle" title="Delete Custom Role" style="width: 24px; height: 24px; line-height: 1;">
                      <i class="fa-solid fa-trash-can" style="font-size: 0.68rem;"></i>
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </div>

            <h3 class="fw-bold text-dark mb-1 fs-5">
              <?= e($r['name']) ?>
            </h3>
            <p class="text-muted small mb-3" style="font-size: 0.8rem; min-height: 38px;">
              <?= e($r['description'] ?: 'Operational role within the visa processing pipeline.') ?>
            </p>

            <div class="bento-role-progress-card">
              <div class="d-flex justify-content-between align-items-center mb-1.5">
                <span class="text-muted small fw-semibold">Coverage:</span>
                <span class="small fw-bold <?= $isSuper ? 'text-danger' : 'text-primary' ?> font-monospace"><?= (int)$r['permission_count'] ?> / <?= $totalPermissions ?> (<?= $permPct ?>%)</span>
              </div>
              <div class="progress" style="height: 6px; border-radius: 9999px;">
                <div class="progress-bar <?= $isSuper ? 'bg-danger' : 'bg-primary' ?>" role="progressbar" style="width: <?= $permPct ?>%;"></div>
              </div>
            </div>
          </div>

          <div class="d-flex align-items-center justify-content-between pt-2 border-top mt-2">
            <span class="text-muted small" style="font-size: 0.74rem;">
              <i class="fa-solid fa-layer-group me-1 text-primary"></i><?= $totalModulesCount ?> Modules
            </span>
            <a href="/roles/edit?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary px-3 fw-semibold rounded-pill shadow-sm">
              <i class="fa-solid fa-sliders me-1"></i> Configure Matrix &rarr;
            </a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Modules & Permission Catalog -->
  <div class="bento-module-card">
    <div class="card-header bg-white py-3 px-3.5 d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom">
      <div>
        <h2 class="fw-bold text-dark mb-0 fs-6"><i class="fa-solid fa-boxes-stacked text-danger me-2"></i> System Modules Catalog</h2>
        <span class="text-muted small">Manage registered operational modules and granular capability gates.</span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-primary px-3 rounded-pill fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#createModuleModal">
          <i class="fa-solid fa-plus me-1"></i> Add Module
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary px-3 rounded-pill fw-semibold" data-bs-toggle="modal" data-bs-target="#createPermissionModal">
          <i class="fa-solid fa-key me-1"></i> Add Permission
        </button>
      </div>
    </div>
    <div class="table-responsive mb-0">
      <table class="bento-module-table align-middle">
        <thead>
          <tr>
            <th style="min-width: 180px;">Module Name</th>
            <th style="min-width: 130px;">Permissions</th>
            <th style="min-width: 300px;">Key Capability Gates</th>
            <th style="min-width: 120px;">Category</th>
            <th class="text-end" style="min-width: 100px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
            $coreModules = ['Applications', 'Applicants', 'Documents', 'Tasks', 'Payments', 'Staff', 'Settings'];
            foreach (($modulesList ?? []) as $mod):
              $isCore = in_array($mod['module'], $coreModules, true);
              $samplePerms = array_slice(array_filter($allPermissions, fn($p) => $p['module'] === $mod['module']), 0, 4);
          ?>
            <tr>
              <td data-label="Module Name">
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center p-2 flex-shrink-0" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-cubes-stacked" style="font-size: 0.8rem;"></i>
                  </div>
                  <strong class="text-dark"><?= e($mod['module']) ?></strong>
                </div>
              </td>
              <td data-label="Permissions">
                <span class="badge bg-light text-dark border font-monospace rounded-pill px-2.5 py-1"><?= (int)$mod['perm_count'] ?> rules</span>
              </td>
              <td data-label="Key Gates">
                <div class="d-flex flex-wrap gap-1">
                  <?php foreach ($samplePerms as $sp): ?>
                    <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.68rem;"><?= e($sp['slug']) ?></span>
                  <?php endforeach; ?>
                  <?php if ((int)$mod['perm_count'] > 4): ?>
                    <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;">+<?= (int)$mod['perm_count'] - 4 ?> more</span>
                  <?php endif; ?>
                </div>
              </td>
              <td data-label="Category">
                <span class="badge <?= $isCore ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-info-subtle text-info border border-info-subtle' ?> fw-semibold px-2 py-0.5 rounded-pill" style="font-size: 0.72rem;">
                  <?= $isCore ? 'Core Module' : 'Add-on Module' ?>
                </span>
              </td>
              <td data-label="Actions" class="text-end">
                <?php if (!$isCore): ?>
                  <form action="/roles/delete-module" method="POST" class="d-inline" onsubmit="return confirm('Delete module \'<?= e(addslashes($mod['module'])) ?>\' and its permissions?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="module" value="<?= e($mod['module']) ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2.5 rounded-pill" title="Delete Module">
                      <i class="fa-solid fa-trash-can"></i>
                    </button>
                  </form>
                <?php else: ?>
                  <span class="text-muted small"><i class="fa-solid fa-lock text-secondary me-1"></i>Protected</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ================================================================= -->
<!-- MODALS -->
<!-- ================================================================= -->

<!-- Modal: Add Module -->
<div class="modal fade" id="createModuleModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow" style="border-radius: var(--bento-radius);">
      <div class="modal-header bg-dark text-white" style="border-top-left-radius: var(--bento-radius); border-top-right-radius: var(--bento-radius);">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-folder-plus text-danger me-2"></i> Register New System Module</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/roles/add-module" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Module Display Name <span class="text-danger">*</span></label>
            <input type="text" name="module_name" class="form-control" placeholder="e.g. Accounting, Invoicing, Inventory" required>
          </div>
          <div class="mb-0">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="generate_crud" value="1" id="genCrudCheck" checked>
              <label class="form-check-label small text-dark fw-semibold" for="genCrudCheck">
                Auto-generate Standard CRUD Permissions
              </label>
            </div>
            <div class="form-text small text-muted">
              Generates <code>view</code>, <code>create</code>, <code>edit</code>, and <code>delete</code> permission gates for this module.
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light" style="border-bottom-left-radius: var(--bento-radius); border-bottom-right-radius: var(--bento-radius);">
          <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold rounded-pill px-4"><i class="fa-solid fa-save me-1"></i> Save Module</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Add Permission -->
<div class="modal fade" id="createPermissionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow" style="border-radius: var(--bento-radius);">
      <div class="modal-header bg-dark text-white" style="border-top-left-radius: var(--bento-radius); border-top-right-radius: var(--bento-radius);">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-key text-warning me-2"></i> Add Granular Permission Rule</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/roles/add-permission" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Target Module <span class="text-danger">*</span></label>
            <select name="module" class="form-select" required>
              <?php foreach (($modulesList ?? []) as $mod): ?>
                <option value="<?= e($mod['module']) ?>"><?= e($mod['module']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Permission Title <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Export Financial Audit Reports" required>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Permission Slug <span class="text-danger">*</span></label>
            <input type="text" name="slug" class="form-control font-monospace" placeholder="e.g. finance.export" required>
          </div>
        </div>
        <div class="modal-footer bg-light" style="border-bottom-left-radius: var(--bento-radius); border-bottom-right-radius: var(--bento-radius);">
          <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold rounded-pill px-4"><i class="fa-solid fa-save me-1"></i> Save Permission</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Create Role -->
<div class="modal fade" id="createRoleModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow" style="border-radius: var(--bento-radius);">
      <div class="modal-header bg-dark text-white" style="border-top-left-radius: var(--bento-radius); border-top-right-radius: var(--bento-radius);">
        <h6 class="modal-title fw-bold"><i class="fa-solid fa-shield-plus text-danger me-2"></i> Create Custom Security Role</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="/roles/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Role Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Senior Regional Auditor" required>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Role Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Describe the operational responsibilities and scope of this role..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light" style="border-bottom-left-radius: var(--bento-radius); border-bottom-right-radius: var(--bento-radius);">
          <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold rounded-pill px-4"><i class="fa-solid fa-save me-1"></i> Create Role</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
