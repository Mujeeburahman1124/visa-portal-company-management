<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\AuditService;
use PDO;

class SettingsController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        \App\Database\DatabaseBootstrapper::init();
        $pdo = Database::getConnection();

        $tab = trim($_GET['tab'] ?? 'company');

        $countries = $pdo->query("SELECT * FROM countries ORDER BY name ASC")->fetchAll();
        $categories = $pdo->query("SELECT * FROM visa_categories ORDER BY name ASC")->fetchAll();
        $services = $pdo->query("SELECT vs.*, c.name as country_name, c.flag_emoji, vc.name as category_name 
            FROM visa_services vs 
            JOIN countries c ON vs.country_id = c.id 
            JOIN visa_categories vc ON vs.category_id = vc.id 
            ORDER BY c.name ASC, vs.name ASC")->fetchAll();
        $docTypes = $pdo->query("SELECT * FROM document_types ORDER BY name ASC")->fetchAll();
        $templates = $pdo->query("SELECT * FROM email_templates ORDER BY title ASC")->fetchAll();

        try {
            $stages = $pdo->query("SELECT * FROM visa_stages ORDER BY sequence_order ASC")->fetchAll() ?: [];
        } catch (\Throwable $e) {
            $stages = [];
        }

        try {
            $applicationStatuses = $pdo->query("SELECT * FROM application_statuses ORDER BY display_order ASC, name ASC")->fetchAll() ?: [];
        } catch (\Throwable $e) {
            $applicationStatuses = [];
        }

        $settingsRaw = $pdo->query("SELECT * FROM system_settings")->fetchAll();
        
        $settings = [];
        foreach ($settingsRaw as $s) {
            $settings[$s['setting_key']] = $s['setting_value'];
        }

        require_once dirname(__DIR__) . '/Views/settings/index.php';
    }

    public function updateTemplate(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $id = (int)($_POST['id'] ?? 0);
        $subject = trim($_POST['subject'] ?? '');
        $bodyHtml = trim($_POST['body_html'] ?? '');

        if ($id <= 0 || empty($subject) || empty($bodyHtml)) {
            redirect('/settings?tab=templates', 'Template subject and content are required.', 'danger');
        }

        $stmt = $pdo->prepare("UPDATE email_templates SET subject = ?, body_html = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$subject, $bodyHtml, $id]);

        AuditService::log('UPDATE_EMAIL_TEMPLATE', 'Settings', $id, "Updated email notification template #{$id}");

        redirect('/settings?tab=templates', 'Email template updated successfully.', 'success');
    }

    public function addCountry(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $name = trim($_POST['name'] ?? '');
        $isoCode = strtoupper(trim($_POST['iso_code'] ?? ''));
        $flag = trim($_POST['flag_emoji'] ?? '🌐');
        $currency = strtoupper(trim($_POST['currency'] ?? 'USD'));
        $region = trim($_POST['region'] ?? 'Global');
        $embassyInfo = trim($_POST['embassy_info'] ?? '');

        if (!empty($name) && !empty($isoCode)) {
            $stmt = $pdo->prepare("INSERT INTO countries (name, iso_code, flag_emoji, currency, region, embassy_info) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $isoCode, $flag, $currency, $region, $embassyInfo]);
            AuditService::log('ADD_COUNTRY', 'Settings', (int)$pdo->lastInsertId(), "Added destination country {$name}");
            redirect('/settings?tab=countries', "Country {$name} added successfully.", 'success');
        }

        redirect('/settings?tab=countries', 'Country name and ISO code are required.', 'danger');
    }

    public function addService(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $countryId = (int)($_POST['country_id'] ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $duration = trim($_POST['duration'] ?? '30 Days');
        $maxStay = trim($_POST['max_stay'] ?? '30 Days');
        $validity = trim($_POST['validity'] ?? '60 Days');
        $entryType = trim($_POST['entry_type'] ?? 'Single Entry');
        $processingType = trim($_POST['processing_type'] ?? 'Normal');
        $estimatedDays = (int)($_POST['estimated_days'] ?? 7);
        $supplierCost = (float)($_POST['supplier_cost'] ?? 0.00);
        $sellingPrice = (float)($_POST['selling_price'] ?? 0.00);
        $taxRate = (float)($_POST['tax_rate'] ?? 0.00);

        if ($countryId > 0 && $categoryId > 0 && !empty($name) && $sellingPrice > 0) {
            $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)) . '-' . time();
            $stmt = $pdo->prepare("INSERT INTO visa_services (
                country_id, category_id, name, slug, duration, max_stay, validity,
                entry_type, processing_type, estimated_days, supplier_cost, tax_rate, selling_price
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$countryId, $categoryId, $name, $slug, $duration, $maxStay, $validity, $entryType, $processingType, $estimatedDays, $supplierCost, $taxRate, $sellingPrice]);
            $serviceId = (int)$pdo->lastInsertId();

            AuditService::log('ADD_SERVICE', 'Settings', $serviceId, "Added visa service {$name}");
            redirect('/settings?tab=services', "Visa package '{$name}' created successfully.", 'success');
        }

        redirect('/settings?tab=services', 'Please complete all required visa service fields.', 'danger');
    }

    public function addDocType(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim(preg_replace('/[^A-Za-z0-9_]+/', '_', $name)));
        $category = trim($_POST['category'] ?? 'Personal');
        $requiresExpiry = isset($_POST['requires_expiry']) ? 1 : 0;
        $desc = trim($_POST['description'] ?? '');

        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO document_types (name, code, description, category, requires_expiry) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $code, $desc, $category, $requiresExpiry]);
            AuditService::log('ADD_DOC_TYPE', 'Settings', (int)$pdo->lastInsertId(), "Added document type {$name}");
            redirect('/settings?tab=documents', "Document requirement '{$name}' created.", 'success');
        }

        redirect('/settings?tab=documents', 'Document type name is required.', 'danger');
    }

    public function updatePreferences(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $targetTab = $_POST['target_tab'] ?? 'company';

        foreach ($_POST['settings'] ?? [] as $key => $val) {
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP");
            $stmt->execute([$key, $val]);
        }

        AuditService::log('UPDATE_SETTINGS', 'Settings', null, "Updated system configuration for category: {$targetTab}");

        redirect("/settings?tab={$targetTab}", 'Settings updated successfully.', 'success');
    }

    public function addStatus(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? 'Processing');
        $badgeColor = trim($_POST['badge_color'] ?? 'primary');
        $isCustomerVisible = isset($_POST['is_customer_visible']) ? 1 : 0;
        $displayOrder = (int)($_POST['display_order'] ?? 50);
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            redirect('/settings?tab=statuses', 'Status name is required.', 'danger');
        }

        $code = strtoupper(preg_replace('/[^A-Za-z0-9_]+/', '_', $name));

        try {
            $stmt = $pdo->prepare("INSERT INTO application_statuses (name, code, category, badge_color, is_customer_visible, is_system, is_active, display_order, description) 
                VALUES (?, ?, ?, ?, ?, 0, 1, ?, ?)");
            $stmt->execute([$name, $code, $category, $badgeColor, $isCustomerVisible, $displayOrder, $description]);

            AuditService::log('ADD_CUSTOM_STATUS', 'Settings', (int)$pdo->lastInsertId(), "Created custom application status '{$name}'");
            redirect('/settings?tab=statuses', "Status '{$name}' created successfully.", 'success');
        } catch (\Throwable $e) {
            redirect('/settings?tab=statuses', 'Error creating status: ' . $e->getMessage(), 'danger');
        }
    }

    public function toggleStatus(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE application_statuses SET is_active = IF(is_active=1, 0, 1) WHERE id = ? AND is_system = 0");
            $stmt->execute([$id]);
            AuditService::log('TOGGLE_STATUS', 'Settings', $id, "Toggled status #{$id}");
            redirect('/settings?tab=statuses', "Status state updated.", 'success');
        }

        redirect('/settings?tab=statuses', 'Invalid status ID.', 'danger');
    }
}
