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
        $supplierId = !empty($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
        $search = trim($_GET['search'] ?? '');
        $activeTab = trim($_GET['tab'] ?? 'packages');

        // Fetch Packages with Supplier Info
        $sql = "SELECT vs.*, c.name as country_name, c.flag_emoji, vc.name as category_name,
                s.name as supplier_company_name,
                (SELECT COUNT(*) FROM applications a WHERE a.visa_service_id = vs.id) as total_applications,
                (SELECT COUNT(*) FROM visa_package_price_history vph WHERE vph.visa_service_id = vs.id) as price_changes_count
                FROM visa_services vs
                JOIN countries c ON c.id = vs.country_id
                JOIN visa_categories vc ON vc.id = vs.category_id
                LEFT JOIN suppliers s ON vs.supplier_id = s.id
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

        if ($supplierId > 0) {
            $sql .= " AND vs.supplier_id = ?";
            $params[] = $supplierId;
        }

        if ($search !== '') {
            $sql .= " AND (vs.name LIKE ? OR vs.duration LIKE ? OR c.name LIKE ? OR vs.supplier_name LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $sql .= " ORDER BY c.name ASC, vs.name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Countries, Categories, Visa Types, and Suppliers
        $countries = $pdo->query("SELECT id, name, flag_emoji FROM countries ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $categories = $pdo->query("SELECT vc.*, (SELECT COUNT(*) FROM visa_services vs WHERE vs.category_id = vc.id) as packages_count FROM visa_categories vc ORDER BY vc.name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $visaTypes = $pdo->query("SELECT * FROM visa_types ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $suppliers = $pdo->query("SELECT id, name, contact_person, country FROM suppliers WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Inventory / Transaction History with Combinable Filters
        $invFilterDate = trim($_GET['inv_date_preset'] ?? '');
        $invDateFrom = trim($_GET['inv_date_from'] ?? '');
        $invDateTo = trim($_GET['inv_date_to'] ?? '');
        $invSupplier = (int)($_GET['inv_supplier'] ?? 0);
        $invCountry = (int)($_GET['inv_country'] ?? 0);
        $invPackage = (int)($_GET['inv_package'] ?? 0);
        $invCurrency = trim($_GET['inv_currency'] ?? '');
        $invUser = (int)($_GET['inv_user'] ?? 0);
        $invAppRef = trim($_GET['inv_app_ref'] ?? '');

        // Date Presets resolution
        if ($invFilterDate === 'today') {
            $invDateFrom = date('Y-m-d');
            $invDateTo = date('Y-m-d');
        } elseif ($invFilterDate === 'yesterday') {
            $invDateFrom = date('Y-m-d', strtotime('-1 day'));
            $invDateTo = date('Y-m-d', strtotime('-1 day'));
        } elseif ($invFilterDate === 'this_week') {
            $invDateFrom = date('Y-m-d', strtotime('monday this week'));
            $invDateTo = date('Y-m-d');
        } elseif ($invFilterDate === 'this_month') {
            $invDateFrom = date('Y-m-01');
            $invDateTo = date('Y-m-d');
        }

        $invSql = "SELECT it.*, vs.name as package_name, c.name as country_name, s.name as supplier_name_ref,
                   u.name as user_name, a.application_number
                   FROM visa_package_inventory_transactions it
                   JOIN visa_services vs ON it.visa_service_id = vs.id
                   LEFT JOIN countries c ON vs.country_id = c.id
                   LEFT JOIN suppliers s ON it.supplier_id = s.id
                   LEFT JOIN users u ON it.user_id = u.id
                   LEFT JOIN applications a ON it.application_id = a.id
                   WHERE 1=1";
        $invParams = [];

        if (!empty($invDateFrom)) {
            $invSql .= " AND DATE(it.created_at) >= ?";
            $invParams[] = $invDateFrom;
        }
        if (!empty($invDateTo)) {
            $invSql .= " AND DATE(it.created_at) <= ?";
            $invParams[] = $invDateTo;
        }
        if ($invSupplier > 0) {
            $invSql .= " AND (it.supplier_id = ? OR vs.supplier_id = ?)";
            $invParams[] = $invSupplier;
            $invParams[] = $invSupplier;
        }
        if ($invCountry > 0) {
            $invSql .= " AND vs.country_id = ?";
            $invParams[] = $invCountry;
        }
        if ($invPackage > 0) {
            $invSql .= " AND it.visa_service_id = ?";
            $invParams[] = $invPackage;
        }
        if (!empty($invCurrency)) {
            $invSql .= " AND it.currency = ?";
            $invParams[] = $invCurrency;
        }
        if ($invUser > 0) {
            $invSql .= " AND it.user_id = ?";
            $invParams[] = $invUser;
        }
        if (!empty($invAppRef)) {
            $invSql .= " AND a.application_number LIKE ?";
            $invParams[] = "%{$invAppRef}%";
        }

        $invSql .= " ORDER BY it.created_at DESC LIMIT 200";
        $invStmt = $pdo->prepare($invSql);
        $invStmt->execute($invParams);
        $inventoryTransactions = $invStmt->fetchAll(PDO::FETCH_ASSOC);

        $staffUsers = $pdo->query("SELECT id, name FROM users WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

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
        $currentUser = auth_user();

        $countryId = (int)($_POST['country_id'] ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $supplierId = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
        $name = trim($_POST['name'] ?? '');
        $duration = trim($_POST['duration'] ?? '30 Days');
        $maxStay = trim($_POST['max_stay'] ?? $duration);
        $validity = trim($_POST['validity'] ?? '60 Days');
        $entryType = trim($_POST['entry_type'] ?? 'Single Entry');
        $processingType = trim($_POST['processing_type'] ?? 'Normal');
        $estimatedDays = (int)($_POST['estimated_days'] ?? 3);
        $currency = strtoupper(trim($_POST['currency'] ?? 'USD'));
        $effectiveDate = !empty($_POST['effective_date']) ? $_POST['effective_date'] : date('Y-m-d');
        $supplierCost = (float)($_POST['supplier_cost'] ?? 0.00);
        $serviceFee = (float)($_POST['service_fee'] ?? 0.00);
        $taxRate = (float)($_POST['tax_rate'] ?? 5.00);
        $taxAmount = ($supplierCost + $serviceFee) * ($taxRate / 100);
        $sellingPrice = (float)($_POST['selling_price'] ?? ($supplierCost + $serviceFee + $taxAmount));
        $notes = trim($_POST['notes'] ?? '');
        $cancellationPolicy = trim($_POST['cancellation_policy'] ?? 'Non-refundable once submitted to immigration authorities.');

        // Fetch supplier name if supplier ID provided
        $supplierName = trim($_POST['supplier_name'] ?? '');
        if ($supplierId && empty($supplierName)) {
            $supplierName = (string)($pdo->query("SELECT name FROM suppliers WHERE id = {$supplierId}")->fetchColumn() ?: '');
        }

        if ($countryId <= 0 || $categoryId <= 0 || empty($name) || $sellingPrice <= 0) {
            redirect('/visa-packages', 'Please provide valid Country, Category, Package Name and Selling Price.', 'danger');
        }

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name . '-' . $duration . '-' . time()), '-'));

        $stmt = $pdo->prepare("INSERT INTO visa_services (
            country_id, category_id, supplier_id, supplier_name, name, slug, duration, max_stay, validity,
            entry_type, processing_type, estimated_days, supplier_cost, service_fee,
            tax_rate, selling_price, currency, effective_date, notes, cancellation_policy, is_active, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");

        $stmt->execute([
            $countryId, $categoryId, $supplierId, $supplierName, $name, $slug, $duration, $maxStay, $validity,
            $entryType, $processingType, $estimatedDays, $supplierCost, $serviceFee,
            $taxRate, $sellingPrice, $currency, $effectiveDate, $notes, $cancellationPolicy
        ]);

        $newId = (int)$pdo->lastInsertId();

        // 1. Record Initial Immutable Price History Snapshot
        $histStmt = $pdo->prepare("INSERT INTO visa_package_price_history (
            visa_service_id, supplier_id, supplier_cost, service_fee, tax_rate, selling_price, currency,
            effective_from, effective_to, notes, created_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, CURRENT_TIMESTAMP)");
        $histStmt->execute([
            $newId, $supplierId, $supplierCost, $serviceFee, $taxRate, $sellingPrice, $currency,
            $effectiveDate . ' 00:00:00', "Initial package pricing setup: {$notes}", $currentUser['id'] ?? 1
        ]);

        // 2. Record Initial Inventory Audit Log
        $invStmt = $pdo->prepare("INSERT INTO visa_package_inventory_transactions (
            visa_service_id, action_type, prev_cost, new_cost, prev_price, new_price, currency,
            supplier_id, user_id, notes, effective_date, created_at
        ) VALUES (?, 'Created', 0.00, ?, 0.00, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
        $invStmt->execute([
            $newId, $supplierCost, $sellingPrice, $currency,
            $supplierId, $currentUser['id'] ?? 1, "Package created with selling price {$currency} " . number_format($sellingPrice, 2), $effectiveDate
        ]);

        AuditService::log('CREATE', 'VisaServices', $newId, "Created new Visa Package: {$name} ({$currency} " . number_format($sellingPrice, 2) . ")");

        redirect('/visa-packages', "Visa Package '{$name}' created successfully with price history recorded!", 'success');
    }

    public function update(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $id = (int)($_POST['id'] ?? 0);
        $countryId = (int)($_POST['country_id'] ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $supplierId = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
        $name = trim($_POST['name'] ?? '');
        $duration = trim($_POST['duration'] ?? '30 Days');
        $maxStay = trim($_POST['max_stay'] ?? $duration);
        $validity = trim($_POST['validity'] ?? '60 Days');
        $entryType = trim($_POST['entry_type'] ?? 'Single Entry');
        $processingType = trim($_POST['processing_type'] ?? 'Normal');
        $estimatedDays = (int)($_POST['estimated_days'] ?? 3);
        $currency = strtoupper(trim($_POST['currency'] ?? 'USD'));
        $effectiveDate = !empty($_POST['effective_date']) ? $_POST['effective_date'] : date('Y-m-d');
        $supplierCost = (float)($_POST['supplier_cost'] ?? 0.00);
        $serviceFee = (float)($_POST['service_fee'] ?? 0.00);
        $taxRate = (float)($_POST['tax_rate'] ?? 5.00);
        $sellingPrice = (float)($_POST['selling_price'] ?? ($supplierCost + $serviceFee));
        $notes = trim($_POST['notes'] ?? '');
        $cancellationPolicy = trim($_POST['cancellation_policy'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($id <= 0 || $countryId <= 0 || $categoryId <= 0 || empty($name) || $sellingPrice <= 0) {
            redirect('/visa-packages', 'Please provide valid Country, Category, Package Name and Selling Price.', 'danger');
        }

        // Fetch previous version to check for price/supplier change
        $prev = $pdo->query("SELECT * FROM visa_services WHERE id = {$id}")->fetch(PDO::FETCH_ASSOC);
        if (!$prev) {
            redirect('/visa-packages', 'Package not found.', 'danger');
        }

        $supplierName = trim($_POST['supplier_name'] ?? '');
        if ($supplierId && empty($supplierName)) {
            $supplierName = (string)($pdo->query("SELECT name FROM suppliers WHERE id = {$supplierId}")->fetchColumn() ?: '');
        }

        $priceChanged = (
            abs((float)$prev['selling_price'] - $sellingPrice) > 0.001 ||
            abs((float)$prev['supplier_cost'] - $supplierCost) > 0.001 ||
            abs((float)$prev['service_fee'] - $serviceFee) > 0.001 ||
            abs((float)$prev['tax_rate'] - $taxRate) > 0.001 ||
            ($prev['currency'] ?? 'USD') !== $currency ||
            (int)($prev['supplier_id'] ?? 0) !== (int)$supplierId
        );

        $stmt = $pdo->prepare("UPDATE visa_services SET
            country_id = ?, category_id = ?, supplier_id = ?, supplier_name = ?, name = ?, duration = ?, max_stay = ?,
            validity = ?, entry_type = ?, processing_type = ?, estimated_days = ?,
            supplier_cost = ?, service_fee = ?, tax_rate = ?, selling_price = ?, currency = ?, effective_date = ?, notes = ?,
            cancellation_policy = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?");

        $stmt->execute([
            $countryId, $categoryId, $supplierId, $supplierName, $name, $duration, $maxStay,
            $validity, $entryType, $processingType, $estimatedDays,
            $supplierCost, $serviceFee, $taxRate, $sellingPrice, $currency, $effectiveDate, $notes,
            $cancellationPolicy, $isActive, $id
        ]);

        // If price or supplier changed, maintain COMPLETE immutable price history
        if ($priceChanged) {
            $now = date('Y-m-d H:i:s');
            // Close previous active price history record
            $pdo->prepare("UPDATE visa_package_price_history SET effective_to = ? WHERE visa_service_id = ? AND effective_to IS NULL")
                ->execute([$now, $id]);

            // Insert new price snapshot
            $insHist = $pdo->prepare("INSERT INTO visa_package_price_history (
                visa_service_id, supplier_id, supplier_cost, service_fee, tax_rate, selling_price, currency,
                effective_from, effective_to, notes, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, CURRENT_TIMESTAMP)");
            $insHist->execute([
                $id, $supplierId, $supplierCost, $serviceFee, $taxRate, $sellingPrice, $currency,
                $effectiveDate . ' 00:00:00', "Updated price: {$notes}", $currentUser['id'] ?? 1
            ]);

            // Record in inventory audit transactions
            $pdo->prepare("INSERT INTO visa_package_inventory_transactions (
                visa_service_id, action_type, prev_cost, new_cost, prev_price, new_price, currency,
                supplier_id, user_id, notes, effective_date, created_at
            ) VALUES (?, 'Price Updated', ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)")
            ->execute([
                $id, (float)$prev['supplier_cost'], $supplierCost, (float)$prev['selling_price'], $sellingPrice, $currency,
                $supplierId, $currentUser['id'] ?? 1, "Price revised from " . number_format((float)$prev['selling_price'], 2) . " to " . number_format($sellingPrice, 2) . " {$currency}. Note: {$notes}", $effectiveDate
            ]);
        } else {
            // General Update Audit
            $pdo->prepare("INSERT INTO visa_package_inventory_transactions (
                visa_service_id, action_type, prev_cost, new_cost, prev_price, new_price, currency,
                supplier_id, user_id, notes, effective_date, created_at
            ) VALUES (?, 'Details Updated', ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)")
            ->execute([
                $id, $supplierCost, $supplierCost, $sellingPrice, $sellingPrice, $currency,
                $supplierId, $currentUser['id'] ?? 1, "Package details updated ({$name})", $effectiveDate
            ]);
        }

        AuditService::log('UPDATE', 'VisaServices', $id, "Updated Visa Package: {$name} ({$currency} " . number_format($sellingPrice, 2) . ")");
        redirect('/visa-packages', "Visa Package '{$name}' updated successfully! Price history recorded.", 'success');
    }

    public function priceHistory(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $serviceId = (int)($_GET['id'] ?? 0);

        $stmt = $pdo->prepare("SELECT vph.*, vs.name as service_name, s.name as supplier_name, u.name as created_by_name
                               FROM visa_package_price_history vph
                               JOIN visa_services vs ON vph.visa_service_id = vs.id
                               LEFT JOIN suppliers s ON vph.supplier_id = s.id
                               LEFT JOIN users u ON vph.created_by = u.id
                               WHERE vph.visa_service_id = ?
                               ORDER BY vph.effective_from DESC, vph.id DESC");
        $stmt->execute([$serviceId]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'history' => $history]);
        exit;
    }

    public function delete(): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getConnection();
        $currentUser = auth_user();
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            redirect('/visa-packages', 'Invalid package ID.', 'danger');
        }

        // Check if applications exist
        $appCount = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE visa_service_id = {$id}")->fetchColumn();
        if ($appCount > 0) {
            // Safe archive
            $pdo->prepare("UPDATE visa_services SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$id]);
            
            $pdo->prepare("INSERT INTO visa_package_inventory_transactions (
                visa_service_id, action_type, prev_cost, new_cost, prev_price, new_price, currency,
                user_id, notes, created_at
            ) VALUES (?, 'Archived', NULL, NULL, NULL, NULL, 'USD', ?, 'Deactivated package due to active applications', CURRENT_TIMESTAMP)")
            ->execute([$id, $currentUser['id'] ?? 1]);

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
            $stmt = $pdo->prepare("UPDATE visa_services SET is_active = 1 - is_active, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
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

        $stmt = $pdo->prepare("INSERT INTO visa_categories (name, slug, description, icon, is_active, created_at) VALUES (?, ?, ?, ?, 1, CURRENT_TIMESTAMP)");
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

        $stmt = $pdo->prepare("INSERT INTO visa_types (name, slug, description, icon, is_active, created_at) VALUES (?, ?, ?, ?, 1, CURRENT_TIMESTAMP)");
        $stmt->execute([$name, $slug, $description, $icon]);

        $typeId = (int)$pdo->lastInsertId();
        AuditService::log('CREATE', 'VisaTypes', $typeId, "Created new Visa Type: {$name}");

        redirect('/visa-packages?tab=types', "Visa Type '{$name}' created successfully!", 'success');
    }
}
