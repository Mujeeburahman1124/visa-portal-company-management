<?php
declare(strict_types=1);

// If built-in PHP server, serve static files directly if they exist
if (php_sapi_name() === 'cli-server') {
    $filePath = __DIR__ . parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (is_file($filePath)) {
        return false;
    }
}

// VISA TRACK — Staff Visa Tracking & Management System Front Controller
require_once dirname(__DIR__) . '/app/autoload.php';

use App\Database\DatabaseBootstrapper;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RoleMiddleware;

// Automatically initialize database schema and seed data on bootstrap
DatabaseBootstrapper::init();

// Validate CSRF on all POST requests except API / public tracking webhooks
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (!str_starts_with($uri, '/api/')) {
        CsrfMiddleware::validate();
    }
}

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = rtrim($uri, '/');
if (empty($uri)) {
    $uri = '/';
}

// Router Dispatch
switch ($uri) {
    case '':
    case '/':
        if (is_authenticated()) {
            redirect('/dashboard');
        } elseif (is_customer_authenticated()) {
            redirect('/portal/dashboard');
        } else {
            redirect('/auth/login');
        }
        break;

    // Authentication Routes
    case '/login':
    case '/auth/login':
        $ctrl = new App\Controllers\AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->login();
        } else {
            $ctrl->showLogin();
        }
        break;

    case '/logout':
    case '/auth/logout':
        (new App\Controllers\AuthController())->logout();
        break;

    case '/forgot-password':
    case '/auth/forgot-password':
        $ctrl = new App\Controllers\AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->forgotPassword();
        } else {
            $ctrl->showForgotPassword();
        }
        break;

    case '/reset-password':
    case '/auth/reset-password':
        $ctrl = new App\Controllers\AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->resetPassword();
        } else {
            $ctrl->showResetPassword();
        }
        break;

    case '/auth/change-password':
        (new App\Controllers\AuthController())->changePassword();
        break;

    case '/auth/switch':
        (new App\Controllers\AuthController())->quickSwitch();
        break;

    // Core Operations: Dashboard
    case '/dashboard':
        (new App\Controllers\DashboardController())->index();
        break;

    // Core Operations: Visual Tracking Center
    case '/tracking':
        (new App\Controllers\TrackingController())->index();
        break;

    // Core Operations: Visa Applications
    case '/applications':
        (new App\Controllers\ApplicationController())->index();
        break;

    case '/applications/create':
        (new App\Controllers\ApplicationController())->create();
        break;

    case '/applications/store':
        (new App\Controllers\ApplicationController())->store();
        break;

    case '/applications/show':
        (new App\Controllers\ApplicationController())->show();
        break;

    case '/applications/edit':
        (new App\Controllers\ApplicationController())->edit();
        break;

    case '/applications/update':
        (new App\Controllers\ApplicationController())->update();
        break;

    case '/applications/delete':
        (new App\Controllers\ApplicationController())->delete();
        break;

    case '/applications/update-stage':
        (new App\Controllers\ApplicationController())->updateStage();
        break;

    case '/applications/assign':
        (new App\Controllers\ApplicationController())->assignStaff();
        break;

    case '/applications/decision':
        (new App\Controllers\ApplicationController())->recordDecision();
        break;

    case '/applications/priority':
        (new App\Controllers\ApplicationController())->updatePriority();
        break;

    case '/applications/note':
        (new App\Controllers\ApplicationController())->addNote();
        break;

    case '/applications/archive':
        (new App\Controllers\ApplicationController())->archive();
        break;

    case '/applications/decision/approve':
        (new App\Controllers\ApplicationController())->decisionApprove();
        break;

    case '/applications/decision/reject':
        (new App\Controllers\ApplicationController())->decisionReject();
        break;

    case '/applications/decision/return':
        (new App\Controllers\ApplicationController())->decisionReturn();
        break;

    case '/applications/document-request':
        (new App\Controllers\ApplicationController())->requestDocument();
        break;

    case '/applications/communication':
        (new App\Controllers\ApplicationController())->addCommunication();
        break;

    // Core Operations: Action Center
    case '/action-center':
        (new App\Controllers\ActionCenterController())->index();
        break;

    // Management & Workflow: Customers / Applicants
    case '/customers':
    case '/applicants':
        (new App\Controllers\CustomerController())->index();
        break;

    case '/customers/create':
    case '/applicants/create':
        (new App\Controllers\CustomerController())->create();
        break;

    case '/customers/store':
    case '/applicants/store':
        (new App\Controllers\CustomerController())->store();
        break;

    case '/customers/show':
    case '/applicants/show':
        (new App\Controllers\CustomerController())->show();
        break;

    case '/customers/edit':
    case '/applicants/edit':
        (new App\Controllers\CustomerController())->edit();
        break;

    case '/customers/update':
    case '/applicants/update':
        (new App\Controllers\CustomerController())->update();
        break;

    case '/customers/delete':
    case '/applicants/delete':
        (new App\Controllers\CustomerController())->delete();
        break;

    case '/customers/check-duplicate':
    case '/applicants/check-duplicate':
        (new App\Controllers\CustomerController())->checkDuplicate();
        break;

    // Management & Workflow: Documents
    case '/documents':
        (new App\Controllers\DocumentController())->index();
        break;

    case '/documents/upload':
        (new App\Controllers\DocumentController())->upload();
        break;

    case '/documents/verify':
        (new App\Controllers\DocumentController())->verify();
        break;

    case '/documents/reject':
        (new App\Controllers\DocumentController())->reject();
        break;

    case '/documents/replace':
        (new App\Controllers\DocumentController())->replace();
        break;

    case '/documents/preview':
        (new App\Controllers\DocumentController())->preview();
        break;

    case '/documents/history':
        (new App\Controllers\DocumentController())->history();
        break;

    case '/documents/request':
        (new App\Controllers\DocumentController())->requestDocument();
        break;

    case '/documents/download':
        (new App\Controllers\DocumentController())->download();
        break;

    // Management & Workflow: Payments & Invoices (Protected: Finance/Management)
    case '/payments':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'accounts', 'visa-manager']);
        (new App\Controllers\PaymentController())->index();
        break;

    case '/payments/history':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'accounts', 'visa-manager']);
        (new App\Controllers\PaymentController())->history();
        break;

    case '/payments/export-csv':
    case '/payments/export':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'accounts', 'visa-manager']);
        (new App\Controllers\PaymentController())->exportCsv();
        break;

    case '/payments/store':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'accounts']);
        (new App\Controllers\PaymentController())->store();
        break;

    case '/payments/refund':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'accounts']);
        (new App\Controllers\PaymentController())->refund();
        break;

    case '/payments/receipt':
        (new App\Controllers\PaymentController())->receipt();
        break;

    case '/payments/invoice':
        (new App\Controllers\PaymentController())->invoice();
        break;

    case '/payments/links':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'accounts', 'visa-manager', 'visa-consultant']);
        (new App\Controllers\PaymentController())->links();
        break;

    case '/payments/generate-link':
    case '/payments/link/create':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'accounts', 'visa-manager', 'visa-consultant']);
        (new App\Controllers\PaymentController())->generateLink();
        break;

    case '/payments/links/cancel':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'accounts']);
        (new App\Controllers\PaymentController())->cancelLink();
        break;

    case '/pay':
        (new App\Controllers\PaymentController())->pay();
        break;

    case '/pay/checkout':
    case '/pay/process':
        (new App\Controllers\PaymentController())->checkout();
        break;

    case '/pay/wallet':
        (new App\Controllers\PaymentController())->payWithWallet();
        break;

    // Management & Workflow: Appointments
    case '/appointments':
        (new App\Controllers\AppointmentController())->index();
        break;

    case '/appointments/store':
        (new App\Controllers\AppointmentController())->store();
        break;

    case '/appointments/status':
        (new App\Controllers\AppointmentController())->updateStatus();
        break;

    // Management & Workflow: Tasks
    case '/tasks':
        (new App\Controllers\TaskController())->index();
        break;

    case '/tasks/store':
        (new App\Controllers\TaskController())->store();
        break;

    case '/tasks/status':
        (new App\Controllers\TaskController())->updateStatus();
        break;

    // Management & Workflow: Reports & Analytics (Protected: Management/Finance)
    case '/reports':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'visa-manager', 'accounts']);
        (new App\Controllers\ReportController())->index();
        break;

    // Administration: Suppliers & Partners
    case '/suppliers':
        RoleMiddleware::authorize(['super-admin', 'admin', 'accounts']);
        (new App\Controllers\SupplierController())->index();
        break;

    case '/suppliers/store':
        RoleMiddleware::authorize(['super-admin', 'admin', 'accounts']);
        (new App\Controllers\SupplierController())->store();
        break;

    case '/suppliers/update':
        RoleMiddleware::authorize(['super-admin', 'admin', 'accounts']);
        (new App\Controllers\SupplierController())->update();
        break;

    case '/suppliers/pay':
        RoleMiddleware::authorize(['super-admin', 'admin', 'accounts']);
        (new App\Controllers\SupplierController())->pay();
        break;

    // Administration: Agents & Partners (Protected)
    case '/agents':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'accounts']);
        (new App\Controllers\AgentController())->index();
        break;

    case '/agents/store':
        RoleMiddleware::authorize(['super-admin', 'admin', 'accounts']);
        (new App\Controllers\AgentController())->store();
        break;

    case '/agents/update':
        RoleMiddleware::authorize(['super-admin', 'admin', 'accounts']);
        (new App\Controllers\AgentController())->update();
        break;

    case '/agents/toggle-status':
        RoleMiddleware::authorize(['super-admin', 'admin']);
        (new App\Controllers\AgentController())->toggleStatus();
        break;

    case '/agents/pay':
        RoleMiddleware::authorize(['super-admin', 'admin', 'accounts']);
        (new App\Controllers\AgentController())->recordPayment();
        break;

    // Administration: Branches (Protected)
    case '/branches':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        (new App\Controllers\BranchController())->index();
        break;

    case '/branches/store':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        (new App\Controllers\BranchController())->store();
        break;

    case '/branches/update':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        (new App\Controllers\BranchController())->update();
        break;

    case '/branches/toggle-status':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        (new App\Controllers\BranchController())->toggleStatus();
        break;

    // Administration: Staff & Roles (Protected: Admin Only)
    case '/staff':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        (new App\Controllers\StaffController())->index();
        break;

    case '/staff/show':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        (new App\Controllers\StaffController())->show();
        break;

    case '/staff/store':
        RoleMiddleware::authorize(['super-admin', 'admin']);
        (new App\Controllers\StaffController())->store();
        break;

    case '/staff/update':
        RoleMiddleware::authorize(['super-admin', 'admin']);
        (new App\Controllers\StaffController())->update();
        break;

    case '/staff/toggle-active':
        RoleMiddleware::authorize(['super-admin', 'admin']);
        (new App\Controllers\StaffController())->toggleActive();
        break;

    case '/staff/reset-password':
        RoleMiddleware::authorize(['super-admin', 'admin']);
        (new App\Controllers\StaffController())->resetPassword();
        break;

    // Administration: Notifications & Real-Time Notification Center
    case '/notifications':
        (new App\Controllers\NotificationController())->index();
        break;

    case '/notifications/admin':
        (new App\Controllers\NotificationController())->admin();
        break;

    case '/notifications/settings':
        (new App\Controllers\NotificationController())->updateSettings();
        break;

    case '/notifications/test':
        (new App\Controllers\NotificationController())->sendTest();
        break;

    case '/notifications/retry':
        (new App\Controllers\NotificationController())->retryLog();
        break;

    case '/notifications/templates/update':
        (new App\Controllers\NotificationController())->updateTemplate();
        break;

    case '/notifications/mark-read':
        (new App\Controllers\NotificationController())->markRead();
        break;

    case '/notifications/mark-all-read':
        (new App\Controllers\NotificationController())->markAllRead();
        break;

    case '/notifications/delete':
        (new App\Controllers\NotificationController())->delete();
        break;

    case '/notifications/preferences':
        (new App\Controllers\NotificationController())->preferences();
        break;

    case '/notifications/preferences/update':
        (new App\Controllers\NotificationController())->updatePreferences();
        break;

    // Administration: Audit Trail (Protected: Admin Only)
    case '/audit':
    case '/audit-logs':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        (new App\Controllers\AuditLogController())->index();
        break;

    case '/audit-logs/export':
        RoleMiddleware::authorize(['super-admin', 'admin']);
        (new App\Controllers\AuditLogController())->exportCsv();
        break;

    // Administration: Roles & Permissions (Protected: Super Admin / Admin)
    case '/roles':
    case '/roles/index':
        RoleMiddleware::authorize(['super-admin', 'admin']);
        (new App\Controllers\RoleController())->index();
        break;

    case '/roles/store':
        RoleMiddleware::authorize(['super-admin', 'admin']);
        (new App\Controllers\RoleController())->store();
        break;

    case '/roles/edit':
        RoleMiddleware::authorize(['super-admin', 'admin']);
        (new App\Controllers\RoleController())->edit();
        break;

    case '/roles/update':
        RoleMiddleware::authorize(['super-admin', 'admin']);
        (new App\Controllers\RoleController())->update();
        break;

    case '/roles/toggle':
        RoleMiddleware::authorize(['super-admin', 'admin']);
        (new App\Controllers\RoleController())->toggleStatus();
        break;

    case '/roles/delete':
        RoleMiddleware::authorize(['super-admin']);
        (new App\Controllers\RoleController())->delete();
        break;

    // Administration: System Settings, Countries & Visa Services / Packages (Protected)
    case '/visa-packages':
    case '/visa-services':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'visa-manager']);
        (new App\Controllers\VisaPackageController())->index();
        break;

    case '/visa-packages/store':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        (new App\Controllers\VisaPackageController())->store();
        break;

    case '/visa-packages/update':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        (new App\Controllers\VisaPackageController())->update();
        break;

    case '/visa-packages/delete':
        RoleMiddleware::authorize(['super-admin', 'admin']);
        (new App\Controllers\VisaPackageController())->delete();
        break;

    case '/visa-packages/toggle':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        (new App\Controllers\VisaPackageController())->toggleStatus();
        break;

    case '/visa-packages/categories/store':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        (new App\Controllers\VisaPackageController())->storeCategory();
        break;

    case '/visa-packages/types/store':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        (new App\Controllers\VisaPackageController())->storeType();
        break;

    case '/countries':
        redirect('/settings?tab=countries');
        break;

    case '/settings':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        (new App\Controllers\SettingsController())->index();
        break;

    case '/settings/country':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        (new App\Controllers\SettingsController())->addCountry();
        break;

    case '/settings/service':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        (new App\Controllers\SettingsController())->addService();
        break;

    case '/settings/doc-type':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        (new App\Controllers\SettingsController())->addDocType();
        break;

    case '/settings/status':
    case '/settings/statuses/add':
        RoleMiddleware::authorize(['super-admin', 'admin']);
        (new App\Controllers\SettingsController())->addStatus();
        break;

    case '/settings/statuses/toggle':
        RoleMiddleware::authorize(['super-admin', 'admin']);
        (new App\Controllers\SettingsController())->toggleStatus();
        break;

    case '/settings/update-template':
        RoleMiddleware::authorize(['super-admin', 'admin']);
        (new App\Controllers\SettingsController())->updateTemplate();
        break;

    case '/settings/preferences':
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        (new App\Controllers\SettingsController())->updatePreferences();
        break;

    // Customer Self-Service Portal (Phase 9)
    case '/portal':
        if (is_customer_authenticated()) {
            redirect('/portal/dashboard');
        } else {
            redirect('/portal/login');
        }
        break;

    case '/portal/login':
        $ctrl = new App\Controllers\PortalController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->login();
        } else {
            $ctrl->showLogin();
        }
        break;

    case '/portal/logout':
        (new App\Controllers\PortalController())->logout();
        break;

    case '/track':
    case '/portal/track':
        (new App\Controllers\PortalController())->trackPublic();
        break;

    case '/portal/dashboard':
        (new App\Controllers\PortalController())->dashboard();
        break;

    case '/portal/documents':
        (new App\Controllers\PortalController())->documents();
        break;

    case '/portal/upload':
        (new App\Controllers\PortalController())->uploadDocument();
        break;

    case '/portal/appointments':
        (new App\Controllers\PortalController())->appointments();
        break;

    case '/portal/notifications':
        (new App\Controllers\PortalController())->notifications();
        break;

    case '/portal/invoices':
        (new App\Controllers\PortalController())->invoices();
        break;

    case '/portal/invoice/view':
        (new App\Controllers\PortalController())->viewInvoice();
        break;

    case '/portal/support':
        (new App\Controllers\PortalController())->support();
        break;

    case '/portal/support/inquiry':
        (new App\Controllers\PortalController())->submitInquiry();
        break;

    case '/portal/wallet':
        (new App\Controllers\PortalController())->wallet();
        break;

    case '/portal/wallet/deposit':
        (new App\Controllers\PortalController())->walletDeposit();
        break;

    // Agent Self-Service Portal (Phase 2)
    case '/agent':
        session_start_safe();
        if (!empty($_SESSION['agent_auth'])) {
            redirect('/agent/dashboard');
        } else {
            redirect('/agent/login');
        }
        break;

    case '/agent/login':
        $agentCtrl = new App\Controllers\AgentPortalController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $agentCtrl->login();
        } else {
            $agentCtrl->showLogin();
        }
        break;

    case '/agent/logout':
        (new App\Controllers\AgentPortalController())->logout();
        break;

    case '/agent/dashboard':
        (new App\Controllers\AgentPortalController())->dashboard();
        break;

    case '/agent/applications':
        (new App\Controllers\AgentPortalController())->applications();
        break;

    case '/agent/create-application':
        $agentCtrl = new App\Controllers\AgentPortalController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $agentCtrl->storeApplication();
        } else {
            $agentCtrl->createApplication();
        }
        break;

    case '/agent/profile':
        $agentCtrl = new App\Controllers\AgentPortalController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $agentCtrl->updateProfile();
        } else {
            $agentCtrl->profile();
        }
        break;

    // Supplier Self-Service Portal (Phase 2)
    case '/supplier':
        session_start_safe();
        if (!empty($_SESSION['supplier_auth'])) {
            redirect('/supplier/dashboard');
        } else {
            redirect('/supplier/login');
        }
        break;

    case '/supplier/login':
        $supCtrl = new App\Controllers\SupplierPortalController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $supCtrl->login();
        } else {
            $supCtrl->showLogin();
        }
        break;

    case '/supplier/logout':
        (new App\Controllers\SupplierPortalController())->logout();
        break;

    case '/supplier/dashboard':
        (new App\Controllers\SupplierPortalController())->dashboard();
        break;

    case '/supplier/applications':
        (new App\Controllers\SupplierPortalController())->applications();
        break;

    case '/supplier/update-status':
        (new App\Controllers\SupplierPortalController())->updateStatus();
        break;

    case '/supplier/payments':
        (new App\Controllers\SupplierPortalController())->payments();
        break;

    case '/supplier/profile':
        $supCtrl = new App\Controllers\SupplierPortalController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $supCtrl->updateProfile();
        } else {
            $supCtrl->profile();
        }
        break;

    // RESTful API Endpoints & Global Live Search
    case '/api/search':
        (new App\Controllers\Api\SearchApiController())->search();
        break;

    case '/api/applications':
        (new App\Controllers\Api\ApplicationApiController())->index();
        break;

    case '/api/applications/show':
        (new App\Controllers\Api\ApplicationApiController())->show((int)($_GET['id'] ?? 0));
        break;

    case '/api/applications/stages':
        (new App\Controllers\Api\ApplicationApiController())->updateStage((int)($_POST['application_id'] ?? $_GET['id'] ?? 0));
        break;

    case '/api/applications/assign':
        (new App\Controllers\Api\ApplicationApiController())->assignStaff((int)($_POST['application_id'] ?? $_GET['id'] ?? 0));
        break;

    case '/api/applications/duplicate-check':
        (new App\Controllers\Api\ApplicationApiController())->checkDuplicate();
        break;

    case '/api/tracking':
        (new App\Controllers\Api\TrackingApiController())->showTracking((int)($_GET['id'] ?? 0));
        break;

    case '/api/documents':
        (new App\Controllers\Api\DocumentApiController())->index((int)($_GET['application_id'] ?? 0));
        break;

    case '/api/documents/verify':
        (new App\Controllers\Api\DocumentApiController())->verify((int)($_POST['document_id'] ?? $_GET['id'] ?? 0));
        break;

    case '/api/documents/reject':
        (new App\Controllers\Api\DocumentApiController())->reject((int)($_POST['document_id'] ?? $_GET['id'] ?? 0));
        break;

    case '/api/tasks':
        $taskCtrl = new App\Controllers\Api\TaskApiController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $taskCtrl->store();
        } else {
            $taskCtrl->index();
        }
        break;

    case '/api/tasks/status':
        (new App\Controllers\Api\TaskApiController())->updateStatus((int)($_POST['task_id'] ?? $_GET['id'] ?? 0));
        break;

    case '/api/appointments':
        $aptCtrl = new App\Controllers\Api\AppointmentApiController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $aptCtrl->store();
        } else {
            $aptCtrl->index();
        }
        break;

    case '/api/applicants':
        $appliCtrl = new App\Controllers\Api\ApplicantApiController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $appliCtrl->store();
        } else {
            $appliCtrl->index();
        }
        break;

    case '/api/applicants/check-duplicate':
        (new App\Controllers\Api\ApplicantApiController())->checkDuplicate();
        break;

    // Real-Time Notification SSE Stream & Queue Processor
    case '/api/notifications/stream':
        (new App\Controllers\NotificationController())->stream();
        break;

    case '/api/notifications/process-queue':
        (new App\Controllers\NotificationController())->processQueueApi();
        break;

    default:
        http_response_code(404);
        require_once dirname(__DIR__) . '/app/Views/layouts/404.php';
        break;
}
