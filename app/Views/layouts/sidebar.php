<?php
$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$currentUser = auth_user();
$roleSlug = $currentUser['role_slug'] ?? '';
$roleName = $currentUser['role_name'] ?? 'Staff';

$isSuperAdmin = ($roleSlug === 'super-admin' || (int)($currentUser['role_id'] ?? 0) === 1 || $roleName === 'Super Admin');
$isAdmin = $isSuperAdmin || ($roleSlug === 'admin');

// Helper to check module permission with dynamic user_can() support
$canViewStaff = $isAdmin || user_can('staff.view');
$canViewAudit = $isAdmin || user_can('audit.view');
$canViewSettings = $isAdmin || user_can('settings.view') || ($roleSlug === 'branch-manager');
$canViewSuppliers = $isAdmin || user_can('suppliers.view');
$canViewBranches = $isAdmin || user_can('branches.view');
$canViewReports = $isAdmin || user_can('reports.view');
$canViewPayments = $isAdmin || user_can('payments.view') || user_can('finance.view');
$canViewPayroll = $isAdmin || user_can('staff.view') || ($roleSlug === 'accounts');
$canViewInventory = $isAdmin || user_can('settings.view') || ($roleSlug === 'branch-manager');
?>
<aside class="app-sidebar" id="appSidebar">
  <!-- Sidebar Brand Header -->
  <div class="sidebar-header">
    <a href="/dashboard" class="sidebar-brand text-decoration-none">
      <img src="/assets/images/logo.png" alt="MS Travel Hub Logo" class="brand-logo-sidebar me-2">
      <div class="sidebar-brand-text">
        <div class="fw-bold brand-title">MS <span class="ruby-gem">TRAVEL HUB</span></div>
        <div class="brand-subtitle">GLOBAL VISA MANAGEMENT</div>
      </div>
    </a>
    <button type="button" class="btn btn-sm text-white d-lg-none p-1" id="sidebarCloseBtn" aria-label="Close navigation">
      <i class="fa-solid fa-xmark fs-5"></i>
    </button>
  </div>

  <!-- Sidebar Navigation Menu -->
  <div class="sidebar-menu" id="sidebarMenu">
    <!-- Section: Core Operations -->
    <div class="sidebar-heading"><span>Core Operations</span></div>
    
    <a href="/dashboard" class="nav-link-custom <?= $currentUri === '/dashboard' ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Operations Dashboard">
      <i class="fa-solid fa-gauge-high nav-icon"></i>
      <span class="nav-label">Dashboard</span>
    </a>

    <a href="/tracking" class="nav-link-custom nav-link-tracking <?= $currentUri === '/tracking' ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Visual Visa Journey & Tracking">
      <i class="fa-solid fa-route nav-icon"></i>
      <span class="nav-label fw-semibold">Visa Tracking</span>
      <span class="badge bg-primary sidebar-badge">CORE</span>
    </a>

    <a href="/applications" class="nav-link-custom <?= (str_starts_with($currentUri, '/applications') && !str_starts_with($currentUri, '/applications/track')) ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Visa Applications Registry">
      <i class="fa-solid fa-folder-open nav-icon"></i>
      <span class="nav-label">Applications</span>
    </a>

    <a href="/action-center" class="nav-link-custom <?= $currentUri === '/action-center' ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Action Center & Priority Queue">
      <i class="fa-solid fa-bolt text-warning nav-icon"></i>
      <span class="nav-label">Action Center</span>
      <span class="badge bg-danger sidebar-badge">Queue</span>
    </a>

    <!-- Section: Management & Workflow -->
    <div class="sidebar-heading mt-2"><span>Management &amp; Workflow</span></div>

    <a href="/customers" class="nav-link-custom <?= str_starts_with($currentUri, '/customers') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Applicants & Customer Profiles">
      <i class="fa-solid fa-users nav-icon"></i>
      <span class="nav-label">Applicants / Customers</span>
    </a>

    <a href="/documents" class="nav-link-custom <?= str_starts_with($currentUri, '/documents') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Document Verification & Vault">
      <i class="fa-solid fa-file-circle-check nav-icon"></i>
      <span class="nav-label">Documents</span>
    </a>

    <?php if ($canViewPayments): ?>
    <a href="/payments" class="nav-link-custom <?= ($currentUri === '/payments' || str_starts_with($currentUri, '/payments/history') || str_starts_with($currentUri, '/payments/links')) ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Payments, Invoicing & Cost Tracking">
      <i class="fa-solid fa-receipt nav-icon"></i>
      <span class="nav-label">Payments &amp; Invoices</span>
    </a>
    <a href="/payments/wallets" class="nav-link-custom <?= str_starts_with($currentUri, '/payments/wallets') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Customer, Supplier & Agent Digital Wallets">
      <i class="fa-solid fa-wallet nav-icon text-success"></i>
      <span class="nav-label">Wallets &amp; Ledgers</span>
    </a>
    <?php endif; ?>


    <?php if ($canViewInventory): ?>
    <a href="/inventory" class="nav-link-custom <?= str_starts_with($currentUri, '/inventory') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Office Inventory, Supplies & Stock Control">
      <i class="fa-solid fa-boxes-stacked nav-icon text-warning"></i>
      <span class="nav-label">Inventory &amp; Stock</span>
    </a>
    <?php endif; ?>

    <a href="/appointments" class="nav-link-custom <?= str_starts_with($currentUri, '/appointments') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Embassy & Biometrics Appointments">
      <i class="fa-solid fa-calendar-check nav-icon"></i>
      <span class="nav-label">Appointments</span>
    </a>

    <a href="/tasks" class="nav-link-custom <?= str_starts_with($currentUri, '/tasks') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Staff Tasks & Deadlines">
      <i class="fa-solid fa-list-check nav-icon"></i>
      <span class="nav-label">Tasks</span>
    </a>

    <?php if ($canViewReports): ?>
    <a href="/reports" class="nav-link-custom <?= str_starts_with($currentUri, '/reports') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Operational Reports & Performance Analytics">
      <i class="fa-solid fa-chart-line nav-icon"></i>
      <span class="nav-label">Reports &amp; Analytics</span>
    </a>
    <?php endif; ?>

    <!-- Section: Administration -->
    <?php if ($canViewSuppliers || $canViewBranches || $canViewStaff || $canViewAudit || $canViewSettings): ?>
    <div class="sidebar-heading mt-2"><span>Administration</span></div>

    <?php if ($canViewSuppliers): ?>
    <a href="/suppliers" class="nav-link-custom <?= str_starts_with($currentUri, '/suppliers') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Visa Vendors & Suppliers">
      <i class="fa-solid fa-building-flag nav-icon"></i>
      <span class="nav-label">Suppliers</span>
    </a>
    <a href="/agents" class="nav-link-custom <?= str_starts_with($currentUri, '/agents') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="B2B Travel Agents & Partner Network">
      <i class="fa-solid fa-handshake nav-icon text-success"></i>
      <span class="nav-label">Agents &amp; Partners</span>
    </a>
    <a href="/countries" class="nav-link-custom <?= str_starts_with($currentUri, '/countries') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Manage Destination Countries">
      <i class="fa-solid fa-earth-americas nav-icon text-primary"></i>
      <span class="nav-label">Countries</span>
    </a>
    <a href="/visa-services" class="nav-link-custom <?= str_starts_with($currentUri, '/visa-services') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Manage Visa Categories & Services">
      <i class="fa-solid fa-list-check nav-icon text-warning"></i>
      <span class="nav-label">Visa Services</span>
    </a>
    <?php endif; ?>

    <?php if ($canViewBranches): ?>
    <a href="/branches" class="nav-link-custom <?= str_starts_with($currentUri, '/branches') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Company Global Branches">
      <i class="fa-solid fa-building nav-icon"></i>
      <span class="nav-label">Branches</span>
    </a>
    <?php endif; ?>

    <?php if ($canViewStaff): ?>
    <a href="/staff" class="nav-link-custom <?= str_starts_with($currentUri, '/staff') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Staff Management & Profiles">
      <i class="fa-solid fa-users-gear nav-icon"></i>
      <span class="nav-label">Staff</span>
    </a>

    <a href="/roles" class="nav-link-custom <?= str_starts_with($currentUri, '/roles') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Security Roles & Permissions Matrix">
      <i class="fa-solid fa-shield-halved nav-icon"></i>
      <span class="nav-label">Roles &amp; Permissions</span>
    </a>
    <?php endif; ?>

    <a href="/notifications" class="nav-link-custom <?= ($currentUri === '/notifications' || $currentUri === '/notifications/preferences') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Internal Alerts & System Notifications">
      <i class="fa-solid fa-bell nav-icon"></i>
      <span class="nav-label">Notifications</span>
    </a>

    <?php if (user_has_role(['super-admin', 'admin', 'branch-manager', 'visa-manager'])): ?>
    <a href="/notifications/admin" class="nav-link-custom <?= str_starts_with($currentUri, '/notifications/admin') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Real-Time Notification Ops, WhatsApp Logs & Settings">
      <i class="fa-solid fa-tower-broadcast text-info nav-icon"></i>
      <span class="nav-label">Notification Ops</span>
      <span class="badge bg-success sidebar-badge">LIVE</span>
    </a>
    <?php endif; ?>

    <?php if ($canViewAudit): ?>
    <a href="/audit-logs" class="nav-link-custom <?= str_starts_with($currentUri, '/audit-logs') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Security & Operations Audit Trail">
      <i class="fa-solid fa-clock-rotate-left nav-icon"></i>
      <span class="nav-label">Audit Trail</span>
    </a>
    <?php endif; ?>

    <?php if ($canViewSettings): ?>
    <a href="/settings" class="nav-link-custom <?= str_starts_with($currentUri, '/settings') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="System Settings & Master Data">
      <i class="fa-solid fa-sliders nav-icon"></i>
      <span class="nav-label">Settings</span>
    </a>
    <?php endif; ?>
    <?php endif; ?>

    <div class="sidebar-heading mt-2"><span>External Portals</span></div>
    <a href="/portal/dashboard" target="_blank" class="nav-link-custom" style="color: #a5b4fc;" data-bs-toggle="tooltip" data-bs-placement="right" title="Open Customer Self-Service Portal">
      <i class="fa-solid fa-arrow-up-right-from-square text-info nav-icon"></i>
      <span class="nav-label">Customer Portal</span>
    </a>
    <a href="/agent/dashboard" target="_blank" class="nav-link-custom" style="color: #6ee7b7;" data-bs-toggle="tooltip" data-bs-placement="right" title="Open Agent Partner Portal">
      <i class="fa-solid fa-arrow-up-right-from-square text-success nav-icon"></i>
      <span class="nav-label">Agent Portal</span>
    </a>
    <a href="/supplier/dashboard" target="_blank" class="nav-link-custom" style="color: #cbd5e1;" data-bs-toggle="tooltip" data-bs-placement="right" title="Open Embassy & Supplier Portal">
      <i class="fa-solid fa-arrow-up-right-from-square text-secondary nav-icon"></i>
      <span class="nav-label">Supplier Portal</span>
    </a>
  </div>

  <!-- Sidebar Footer: Active User & Instant Role Switcher -->
  <div class="sidebar-footer">
    <div class="d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2 overflow-hidden">
        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 34px; height: 34px; font-size: 0.85rem;">
          <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
        </div>
        <div class="sidebar-user-details text-truncate">
          <div class="text-white small fw-semibold text-truncate"><?= e($currentUser['name'] ?? 'Staff User') ?></div>
          <div class="text-muted text-truncate" style="font-size: 0.72rem;"><?= e($roleName) ?></div>
        </div>
      </div>
      <a href="/auth/logout" class="btn btn-sm btn-outline-secondary text-light p-1 px-2 flex-shrink-0" title="Sign Out">
        <i class="fa-solid fa-right-from-bracket"></i>
      </a>
    </div>

    <!-- Dynamic Switch Account Dropdown (Database-Driven) -->
    <?php if (can_switch_accounts()): 
        $switchUsers = get_switchable_users();
    ?>
    <div class="dropdown mt-2 sidebar-role-switcher">
      <button class="btn btn-dark btn-sm w-100 py-1 text-start d-flex align-items-center justify-content-between border-secondary" style="font-size: 0.72rem;" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Switch between eligible user accounts">
        <span>
          <i class="fa-solid fa-users-viewfinder text-info me-1"></i> Switch Account
          <?= is_impersonating() ? '<span class="badge bg-warning text-dark ms-1" style="font-size: 0.58rem; padding: 2px 4px;">Active</span>' : '' ?>
        </span>
        <i class="fa-solid fa-chevron-down" style="font-size: 0.6rem;"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-dark shadow" style="font-size: 0.8rem; z-index: 1060; max-height: 280px; overflow-y: auto;">
        <li class="dropdown-header small text-uppercase text-muted" style="font-size: 0.65rem;">Active Accounts in Database</li>
        <?php if (is_impersonating()): ?>
          <li>
            <a class="dropdown-item text-warning fw-bold py-1.5 border-bottom border-secondary bg-warning-subtle text-dark" href="/auth/switch-back">
              <i class="fa-solid fa-rotate-left me-2"></i> Return to Super Admin
            </a>
          </li>
        <?php endif; ?>
        <?php foreach ($switchUsers as $su): 
            $isCur = ((int)($currentUser['id'] ?? 0) === (int)$su['id']);
            $rSlug = $su['role_slug'] ?? '';
            $icon = 'fa-user text-light';
            if ($rSlug === 'super-admin') $icon = 'fa-crown text-warning';
            elseif ($rSlug === 'admin') $icon = 'fa-user-shield text-danger';
            elseif ($rSlug === 'branch-manager') $icon = 'fa-building-user text-primary';
            elseif ($rSlug === 'visa-manager') $icon = 'fa-user-tie text-info';
            elseif ($rSlug === 'processing-staff') $icon = 'fa-user-gear text-secondary';
            elseif ($rSlug === 'accounts') $icon = 'fa-calculator text-success';
        ?>
          <li>
            <a class="dropdown-item <?= $isCur ? 'active' : '' ?> py-1.5 d-flex align-items-center justify-content-between" href="/auth/switch?user_id=<?= $su['id'] ?>" title="<?= e($su['email']) ?> (<?= e($su['branch_name'] ?? 'Main') ?>)">
              <span class="text-truncate" style="max-width: 135px;">
                <i class="fa-solid <?= $icon ?> me-1.5"></i> <?= e($su['name']) ?>
              </span>
              <span class="badge bg-secondary-subtle text-light border-0 ms-1" style="font-size: 0.62rem;"><?= e($su['role_name']) ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
  </div>
</aside>
