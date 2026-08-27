<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Services\AuditService;
use PDO;

class VisaPackageController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $countryId = !empty($_GET['country_id']) ? (int)$_GET['country_id'] : 0;
        $categoryId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
        $search = trim($_GET['search'] ?? '');
        $activeTab = trim($_GET['tab'] ?? 'packages');

        // Fetch Packages
        $sql = "SELECT vs.*, c.name as country_name, c.flag_emoji, vc.name as category_name,
                (SELECT COUNT(*) FROM applications a WHERE a.visa_service_id = vs.id) as total_applications
                FROM visa_services vs
                JOIN countries c ON c.id = vs.country_id
                JOIN visa_categories vc ON vc.id = vs.category_id
                WHERE 1=1";
        $params = [];

        if ($countryId > 0) {
            $sql .= " AND vs.country_id = ?";
            $params[] = $countryId;
        }

        if ($categoryId > 0) {
            $sql .= " AND vs.category_id = ?";
            $params[] = $categoryId;
        }

        if ($search !== '') {
            $sql .= " AND (vs.name LIKE ? OR vs.duration LIKE ? OR c.name LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $sql .= " ORDER BY c.name ASC, vs.name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Countries, Categories, and Types
        $countries = $pdo->query("SELECT id, name, flag_emoji FROM countries ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $categories = $pdo->query("SELECT vc.*, (SELECT COUNT(*) FROM visa_services vs WHERE vs.category_id = vc.id) as packages_count FROM visa_categories vc ORDER BY vc.name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $visaTypes = $pdo->query("SELECT * FROM visa_types ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Financial KPIs
        $totalPackages = count($packages);
        $activePackages = count(array_filter($packages, fn($p) => !empty($p['is_active'])));
        $totalCountries = (int)$pdo->query("SELECT COUNT(DISTINCT country_id) FROM visa_services")->fetchColumn();
        $totalCategories = count($categories);

        require_once dirname(__DIR__) . '/Views/visa_packages/index.php';
    }

    public function store(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $countryId = (int)($_POST['country_id'] ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $duration = trim($_POST['duration'] ?? '30 Days');
        $maxStay = trim($_POST['max_stay'] ?? $duration);
        $validity = trim($_POST['validity'] ?? '60 Days');
        $entryType = trim($_POST['entry_type'] ?? 'Single Entry');
        $processingType = trim($_POST['processing_type'] ?? 'Normal');
        $estimatedDays = (int)($_POST['estimated_days'] ?? 3);
        $supplierCost = (float)($_POST['supplier_cost'] ?? 0.00);
        $serviceFee = (float)($_POST['service_fee'] ?? 0.00);
        $taxRate = (float)($_POST['tax_rate'] ?? 5.00);
        $sellingPrice = (float)($_POST['selling_price'] ?? ($supplierCost + $serviceFee));
        $cancellationPolicy = trim($_POST['cancellation_policy'] ?? 'Non-refundable once submitted to immigration authorities.');

        if ($countryId <= 0 || $categoryId <= 0 || empty($name) || $sellingPrice <= 0) {
            redirect('/visa-packages', 'Please provide valid Country, Category, Package Name and Selling Price.', 'danger');
        }

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name . '-' . $duration), '-'));

        $stmt = $pdo->prepare("INSERT INTO visa_services (
            country_id, category_id, name, slug, duration, max_stay, validity,
            entry_type, processing_type, estimated_days, supplier_cost, service_fee,
            tax_rate, selling_price, cancellation_policy, is_active, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");

        $stmt->execute([
            $countryId, $categoryId, $name, $slug, $duration, $maxStay, $validity,
            $entryType, $processingType, $estimatedDays, $supplierCost, $serviceFee,
            $taxRate, $sellingPrice, $cancellationPolicy
        ]);

        $newId = (int)$pdo->lastInsertId();
        AuditService::log('CREATE', 'VisaServices', $newId, "Created new Visa Package: {$name} ($" . number_format($sellingPrice, 2) . ")");

        redirect('/visa-packages', "Visa Package '{$name}' created successfully!", 'success');
    }

    public function update(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $id = (int)($_POST['id'] ?? 0);
        $countryId = (int)($_POST['country_id'] ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $duration = trim($_POST['duration'] ?? '30 Days');
        $maxStay = trim($_POST['max_stay'] ?? $duration);
        $validity = trim($_POST['validity'] ?? '60 Days');
        $entryType = trim($_POST['entry_type'] ?? 'Single Entry');
        $processingType = trim($_POST['processing_type'] ?? 'Normal');
        $estimatedDays = (int)($_POST['estimated_days'] ?? 3);
        $supplierCost = (float)($_POST['supplier_cost'] ?? 0.00);
        $serviceFee = (float)($_POST['service_fee'] ?? 0.00);
        $taxRate = (float)($_POST['tax_rate'] ?? 5.00);
        $sellingPrice = (float)($_POST['selling_price'] ?? ($supplierCost + $serviceFee));
        $cancellationPolicy = trim($_POST['cancellation_policy'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($id <= 0 || $countryId <= 0 || $categoryId <= 0 || empty($name) || $sellingPrice <= 0) {
            redirect('/visa-packages', 'Please provide valid Country, Category, Package Name and Selling Price.', 'danger');
        }

        $stmt = $pdo->prepare("UPDATE visa_services SET
            country_id = ?, category_id = ?, name = ?, duration = ?, max_stay = ?,
            validity = ?, entry_type = ?, processing_type = ?, estimated_days = ?,
            supplier_cost = ?, service_fee = ?, tax_rate = ?, selling_price = ?,
            cancellation_policy = ?, is_active = ?, updated_at = NOW()
            WHERE id = ?");

        $stmt->execute([
            $countryId, $categoryId, $name, $duration, $maxStay,
            $validity, $entryType, $processingType, $estimatedDays,
            $supplierCost, $serviceFee, $taxRate, $sellingPrice,
            $cancellationPolicy, $isActive, $id
        ]);

        AuditService::log('UPDATE', 'VisaServices', $id, "Updated Visa Package: {$name} ($" . number_format($sellingPrice, 2) . ")");
        redirect('/visa-packages', "Visa Package '{$name}' updated successfully!", 'success');
    }

    public function delete(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            redirect('/visa-packages', 'Invalid package ID.', 'danger');
        }

        // Check if applications exist
        $appCount = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE visa_service_id = {$id}")->fetchColumn();
        if ($appCount > 0) {
            // Safe archive
            $pdo->prepare("UPDATE visa_services SET is_active = 0, updated_at = NOW() WHERE id = ?")->execute([$id]);
            AuditService::log('DEACTIVATE', 'VisaServices', $id, "Deactivated Visa Package #{$id} because it has {$appCount} linked applications");
            redirect('/visa-packages', "Visa Package has linked applications. It has been deactivated instead of deleted.", 'warning');
        } else {
            $pdo->prepare("DELETE FROM visa_services WHERE id = ?")->execute([$id]);
            AuditService::log('DELETE', 'VisaServices', $id, "Deleted Visa Package #{$id}");
            redirect('/visa-packages', "Visa Package deleted successfully.", 'success');
        }
    }

    public function toggleStatus(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE visa_services SET is_active = IF(is_active=1, 0, 1), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            AuditService::log('UPDATE', 'VisaServices', $id, "Toggled status for visa package #{$id}");
        }

        redirect('/visa-packages', 'Visa package status updated successfully.', 'success');
    }

    public function storeCategory(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icon = trim($_POST['icon'] ?? 'fa-solid fa-passport');

        if (empty($name)) {
            redirect('/visa-packages?tab=categories', 'Category name is required.', 'danger');
        }

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

        $stmt = $pdo->prepare("INSERT INTO visa_categories (name, slug, description, icon, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())");
        $stmt->execute([$name, $slug, $description, $icon]);

        $catId = (int)$pdo->lastInsertId();
        AuditService::log('CREATE', 'VisaCategories', $catId, "Created new Visa Category: {$name}");

        redirect('/visa-packages?tab=categories', "Visa Category '{$name}' created successfully!", 'success');
    }

    public function storeType(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icon = trim($_POST['icon'] ?? 'fa-solid fa-file-lines');

        if (empty($name)) {
            redirect('/visa-packages?tab=types', 'Type name is required.', 'danger');
        }

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

        $stmt = $pdo->prepare("INSERT INTO visa_types (name, slug, description, icon, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())");
        $stmt->execute([$name, $slug, $description, $icon]);

        $typeId = (int)$pdo->lastInsertId();
        AuditService::log('CREATE', 'VisaTypes', $typeId, "Created new Visa Type: {$name}");

        redirect('/visa-packages?tab=types', "Visa Type '{$name}' created successfully!", 'success');
    }
}
