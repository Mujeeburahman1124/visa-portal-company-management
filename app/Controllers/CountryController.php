<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\AuditService;
use PDO;

class CountryController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'visa-manager']);
        $pdo = Database::getConnection();

        $search = trim($_GET['search'] ?? '');
        $regionFilter = trim($_GET['region'] ?? '');
        $currentView = trim($_GET['view'] ?? 'cards');

        $sql = "SELECT c.*,
            COUNT(DISTINCT vs.id) as services_count,
            COUNT(DISTINCT a.id) as applications_count
            FROM countries c
            LEFT JOIN visa_services vs ON vs.country_id = c.id
            LEFT JOIN applications a ON (a.visa_service_id = vs.id OR a.destination_country = c.name)
            WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (c.name LIKE ? OR c.iso_code LIKE ? OR c.currency LIKE ? OR c.region LIKE ?)";
            $t = "%{$search}%";
            $params = array_merge($params, [$t, $t, $t, $t]);
        }

        if ($regionFilter !== '') {
            $sql .= " AND c.region = ?";
            $params[] = $regionFilter;
        }

        $sql .= " GROUP BY c.id ORDER BY c.is_active DESC, c.name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch distinct regions for filter dropdown
        $regions = $pdo->query("SELECT DISTINCT region FROM countries WHERE region IS NOT NULL AND region != '' ORDER BY region ASC")->fetchAll(PDO::FETCH_COLUMN) ?: [];

        // Global metrics
        $totalCountries = count($countries);
        $activeCountries = count(array_filter($countries, fn($x) => (int)($x['is_active'] ?? 0) === 1));
        $totalServices = array_sum(array_column($countries, 'services_count'));
        $totalApplications = array_sum(array_column($countries, 'applications_count'));

        require_once dirname(__DIR__) . '/Views/countries/index.php';
    }

    public function store(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        $pdo = Database::getConnection();

        $name = trim($_POST['name'] ?? '');
        $isoCode = strtoupper(trim($_POST['iso_code'] ?? ''));
        $iso3Code = strtoupper(trim($_POST['iso3_code'] ?? ''));
        $flag = trim($_POST['flag_emoji'] ?? '🌐');
        $currency = strtoupper(trim($_POST['currency'] ?? 'USD'));
        $region = trim($_POST['region'] ?? 'Global');
        $phoneCode = trim($_POST['phone_code'] ?? '');
        $embassyInfo = trim($_POST['embassy_info'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (empty($name) || empty($isoCode)) {
            redirect('/countries', 'Country name and 2-letter ISO code are required.', 'danger');
        }

        // Check duplicate ISO code
        $dup = $pdo->prepare("SELECT COUNT(*) FROM countries WHERE iso_code = ? OR name = ?");
        $dup->execute([$isoCode, $name]);
        if ((int)$dup->fetchColumn() > 0) {
            redirect('/countries', "Country '{$name}' or ISO code '{$isoCode}' already exists.", 'danger');
        }

        $stmt = $pdo->prepare("INSERT INTO countries (name, iso_code, iso3_code, flag_emoji, currency, region, phone_code, embassy_info, notes, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$name, $isoCode, $iso3Code, $flag, $currency, $region, $phoneCode, $embassyInfo, $notes]);
        $newId = (int)$pdo->lastInsertId();

        AuditService::log('CREATE_COUNTRY', 'Countries', $newId, "Added destination country {$name} ({$isoCode})");

        redirect('/countries', "Country '{$name}' successfully added to destination registry.", 'success');
    }

    public function update(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        $pdo = Database::getConnection();

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $isoCode = strtoupper(trim($_POST['iso_code'] ?? ''));
        $iso3Code = strtoupper(trim($_POST['iso3_code'] ?? ''));
        $flag = trim($_POST['flag_emoji'] ?? '🌐');
        $currency = strtoupper(trim($_POST['currency'] ?? 'USD'));
        $region = trim($_POST['region'] ?? 'Global');
        $phoneCode = trim($_POST['phone_code'] ?? '');
        $embassyInfo = trim($_POST['embassy_info'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if ($id <= 0 || empty($name) || empty($isoCode)) {
            redirect('/countries', 'Valid country ID, name, and ISO code are required.', 'danger');
        }

        $stmt = $pdo->prepare("UPDATE countries SET name = ?, iso_code = ?, iso3_code = ?, flag_emoji = ?, currency = ?, region = ?, phone_code = ?, embassy_info = ?, notes = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$name, $isoCode, $iso3Code, $flag, $currency, $region, $phoneCode, $embassyInfo, $notes, $isActive, $id]);

        AuditService::log('UPDATE_COUNTRY', 'Countries', $id, "Updated country details for {$name} ({$isoCode})");

        redirect('/countries', "Country '{$name}' updated successfully.", 'success');
    }

    public function toggleStatus(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            redirect('/countries', 'Invalid country ID.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT id, name, is_active FROM countries WHERE id = ?");
        $stmt->execute([$id]);
        $country = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$country) {
            redirect('/countries', 'Country record not found.', 'danger');
        }

        $newStatus = (int)$country['is_active'] === 1 ? 0 : 1;
        $pdo->prepare("UPDATE countries SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$newStatus, $id]);

        AuditService::log('TOGGLE_COUNTRY_STATUS', 'Countries', $id, "Toggled status to " . ($newStatus ? 'active' : 'suspended') . " for {$country['name']}");

        redirect('/countries', "Country '{$country['name']}' status updated to " . ($newStatus ? 'Active' : 'Suspended') . ".", 'success');
    }

    public function delete(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            redirect('/countries', 'Invalid country identifier.', 'danger');
        }

        // Safety check: ensure no active visa packages depend on this country
        $chk = $pdo->prepare("SELECT COUNT(*) FROM visa_services WHERE country_id = ?");
        $chk->execute([$id]);
        if ((int)$chk->fetchColumn() > 0) {
            redirect('/countries', 'Cannot delete this country because configured visa services are linked to it.', 'danger');
        }

        $stmt = $pdo->prepare("DELETE FROM countries WHERE id = ?");
        $stmt->execute([$id]);

        AuditService::log('DELETE_COUNTRY', 'Countries', $id, "Deleted country record #{$id}");

        redirect('/countries', 'Country deleted successfully.', 'success');
    }
}
