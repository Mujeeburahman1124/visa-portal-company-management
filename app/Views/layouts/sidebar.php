<?php
$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$currentUser = auth_user();
$roleSlug = $currentUser['role_slug'] ?? '';
$roleName = $currentUser['role_name'] ?? 'Staff';

$isSuperAdmin = ($roleSlug === 'super-admin' || (int)($currentUser['role_id'] ?? 0) === 1 || $roleName === 'Super Admin');
$isAdmin      = $isSuperAdmin || ($roleSlug === 'admin');

// ── Permission flags — ALL modules gated ──────────────────────────────────────
// Core Operations: Dashboard, Tracking, Applications, Action Center always visible
$canViewDashboard   = true; // Everyone can see the dashboard
$canViewTracking    = true; // Everyone can view visa tracking
$canViewApplications = $isAdmin || user_can('applications.view') || user_can('applications.manage') || user_can('applications.*');
$canViewActionCenter = $isAdmin || user_can('applications.view') || user_can('applications.manage') || user_can('tasks.view') || user_can('leave.approve') || user_can('leave.reject');

// Management & Workflow
$canViewCustomers   = $isAdmin || user_can('customers.view') || user_can('applicants.view') || user_can('customers.manage');
$canViewDocuments   = $isAdmin || user_can('documents.view') || user_can('documents.manage') || user_can('customers.view') || user_can('applications.view');
$canViewPayments    = $isAdmin || user_can('payments.view') || user_can('finance.view') || user_can('payments.manage');
$canViewWallets     = $isAdmin || user_can('wallets.view') || user_can('payments.view') || user_can('finance.view') || ($roleSlug === 'accounts');
$canViewAppointments = $isAdmin || user_can('appointments.view') || user_can('appointments.manage') || user_can('applications.view');
$canViewTasks       = $isAdmin || user_can('tasks.view') || user_can('tasks.manage') || user_can('tasks.view_all') || user_can('tasks.*');
$canViewReports     = $isAdmin || user_can('reports.view') || user_can('reports.manage');

// Administration
$canViewAgents      = $isAdmin || user_can('agents.view') || user_can('agents.manage');
$canViewCountries   = $isAdmin || user_can('visa_services.view') || user_can('visa.view') || user_can('settings.view') || ($roleSlug === 'visa-manager') || ($roleSlug === 'branch-manager');
$canViewVisaServices = $isAdmin || user_can('visa_services.view') || user_can('visa.view') || ($roleSlug === 'visa-manager');
$canViewBranches    = $isAdmin || user_can('branches.view') || user_can('branches.manage');
$canViewStaff       = $isAdmin || user_can('staff.view') || user_can('staff.manage');
$canViewRoles       = $isAdmin || user_can('roles.view') || user_can('roles.manage');
$canViewAudit       = $isAdmin || user_can('audit.view') || user_can('audit.manage');
$canViewSettings    = $isAdmin || user_can('settings.view') || user_can('settings.manage') || ($roleSlug === 'branch-manager');
$canViewNotifAdmin  = $isAdmin || user_has_role(['branch-manager', 'visa-manager']) || user_can('notifications.admin');
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

    <?php if ($canViewDashboard): ?>
    <a href="/dashboard" class="nav-link-custom <?= $currentUri === '/dashboard' ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Operations Dashboard">
      <i class="fa-solid fa-gauge-high nav-icon"></i>
      <span class="nav-label">Dashboard</span>
    </a>
    <?php endif; ?>

    <?php if ($canViewTracking): ?>
    <a href="/tracking" class="nav-link-custom nav-link-tracking <?= $currentUri === '/tracking' ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Visual Visa Journey & Tracking">
      <i class="fa-solid fa-route nav-icon"></i>
      <span class="nav-label fw-semibold">Visa Tracking</span>
      <span class="badge bg-primary sidebar-badge">CORE</span>
    </a>
    <?php endif; ?>

    <?php if ($canViewApplications): ?>
    <a href="/applications" class="nav-link-custom <?= (str_starts_with($currentUri, '/applications') && !str_starts_with($currentUri, '/applications/track')) ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Visa Applications Registry">
      <i class="fa-solid fa-folder-open nav-icon"></i>
      <span class="nav-label">Applications</span>
    </a>
    <?php endif; ?>

    <?php if ($canViewActionCenter): ?>
    <a href="/action-center" class="nav-link-custom <?= $currentUri === '/action-center' ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Action Center & Priority Queue">
      <i class="fa-solid fa-bolt text-warning nav-icon"></i>
      <span class="nav-label">Action Center</span>
      <span class="badge bg-danger sidebar-badge">Queue</span>
    </a>
    <?php endif; ?>

    <!-- Section: Management & Workflow -->
    <?php if ($canViewCustomers || $canViewDocuments || $canViewPayments || $canViewWallets || $canViewAppointments || $canViewTasks || $canViewReports): ?>
    <div class="sidebar-heading mt-2"><span>Management &amp; Workflow</span></div>

    <?php if ($canViewCustomers): ?>
    <a href="/customers" class="nav-link-custom <?= str_starts_with($currentUri, '/customers') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Applicants & Customer Profiles">
      <i class="fa-solid fa-users nav-icon"></i>
      <span class="nav-label">Applicants / Customers</span>
    </a>
    <?php endif; ?>

    <?php if ($canViewDocuments): ?>
    <a href="/documents" class="nav-link-custom <?= str_starts_with($currentUri, '/documents') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Document Verification & Vault">
      <i class="fa-solid fa-file-circle-check nav-icon"></i>
      <span class="nav-label">Documents</span>
    </a>
    <?php endif; ?>

    <?php if ($canViewPayments): ?>
    <a href="/payments" class="nav-link-custom <?= ($currentUri === '/payments' || str_starts_with($currentUri, '/payments/history') || str_starts_with($currentUri, '/payments/links')) ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Payments, Invoicing & Cost Tracking">
      <i class="fa-solid fa-receipt nav-icon"></i>
      <span class="nav-label">Payments &amp; Invoices</span>
    </a>
    <?php endif; ?>

    <?php if ($canViewWallets): ?>
    <a href="/payments/wallets" class="nav-link-custom <?= str_starts_with($currentUri, '/payments/wallets') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Customer, Supplier & Agent Digital Wallets">
      <i class="fa-solid fa-wallet nav-icon text-success"></i>
      <span class="nav-label">Wallets &amp; Ledgers</span>
    </a>
    <?php endif; ?>

    <?php if ($canViewAppointments): ?>
    <a href="/appointments" class="nav-link-custom <?= str_starts_with($currentUri, '/appointments') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Embassy & Biometrics Appointments">
      <i class="fa-solid fa-calendar-check nav-icon"></i>
      <span class="nav-label">Appointments</span>
    </a>
    <?php endif; ?>

    <?php if ($canViewTasks): ?>
    <a href="/tasks" class="nav-link-custom <?= str_starts_with($currentUri, '/tasks') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Staff Tasks & Deadlines">
      <i class="fa-solid fa-list-check nav-icon"></i>
      <span class="nav-label">Tasks</span>
    </a>
    <?php endif; ?>

    <?php if ($canViewReports): ?>
    <a href="/reports" class="nav-link-custom <?= str_starts_with($currentUri, '/reports') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Operational Reports & Performance Analytics">
      <i class="fa-solid fa-chart-line nav-icon"></i>
      <span class="nav-label">Reports &amp; Analytics</span>
    </a>
    <?php endif; ?>
    <?php endif; // end management section ?>

    <!-- Section: Administration -->
    <?php if ($canViewAgents || $canViewCountries || $canViewVisaServices || $canViewBranches || $canViewStaff || $canViewRoles || $canViewAudit || $canViewSettings): ?>
    <div class="sidebar-heading mt-2"><span>Administration</span></div>

    <?php if ($canViewAgents): ?>
    <a href="/agents" class="nav-link-custom <?= str_starts_with($currentUri, '/agents') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="B2B Travel Agents & Partner Network">
      <i class="fa-solid fa-handshake nav-icon text-success"></i>
      <span class="nav-label">Agents &amp; Partners</span>
    </a>
    <?php endif; ?>

    <?php if ($canViewCountries): ?>
    <a href="/countries" class="nav-link-custom <?= str_starts_with($currentUri, '/countries') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Manage Destination Countries">
      <i class="fa-solid fa-earth-americas nav-icon text-primary"></i>
      <span class="nav-label">Countries</span>
    </a>
    <?php endif; ?>

    <?php if ($canViewVisaServices): ?>
    <a href="/visa-services" class="nav-link-custom <?= str_starts_with($currentUri, '/visa-services') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Manage Visa Categories & Services">
      <i class="fa-solid fa-passport nav-icon text-warning"></i>
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
    <?php endif; ?>

    <?php if ($canViewRoles): ?>
    <a href="/roles" class="nav-link-custom <?= str_starts_with($currentUri, '/roles') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Security Roles & Permissions Matrix">
      <i class="fa-solid fa-shield-halved nav-icon"></i>
      <span class="nav-label">Roles &amp; Permissions</span>
    </a>
    <?php endif; ?>

    <a href="/notifications" class="nav-link-custom <?= ($currentUri === '/notifications' || $currentUri === '/notifications/preferences') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Internal Alerts & System Notifications">
      <i class="fa-solid fa-bell nav-icon"></i>
      <span class="nav-label">Notifications</span>
    </a>

    <?php if ($canViewNotifAdmin): ?>
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
    <?php endif; // end administration section ?>

    <div class="sidebar-heading mt-2"><span>External Portals</span></div>
    <a href="/portal/dashboard" target="_blank" class="nav-link-custom" style="color: #a5b4fc;" data-bs-toggle="tooltip" data-bs-placement="right" title="Open Customer Self-Service Portal">
      <i class="fa-solid fa-arrow-up-right-from-square text-info nav-icon"></i>
      <span class="nav-label">Customer Portal</span>
    </a>
    <a href="/agent/dashboard" target="_blank" class="nav-link-custom" style="color: #6ee7b7;" data-bs-toggle="tooltip" data-bs-placement="right" title="Open Agent Partner Portal">
      <i class="fa-solid fa-arrow-up-right-from-square text-success nav-icon"></i>
      <span class="nav-label">Agent Portal</span>
    </a>
  </div>

  <!-- Sidebar Footer: Active User & Instant Role Switcher -->
  <div class="sidebar-footer">
    <div class="d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2 overflow-hidden">
        <?php $sbAvatar = user_avatar_url($currentUser); if (!empty($sbAvatar)): ?>
          <img src="<?= e($sbAvatar) ?>" alt="<?= e($currentUser['name'] ?? 'User') ?>" class="rounded-circle flex-shrink-0 shadow-sm border border-secondary" style="width: 34px; height: 34px; object-fit: cover;">
        <?php else: ?>
          <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 34px; height: 34px; font-size: 0.85rem;">
            <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
          </div>
        <?php endif; ?>
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
