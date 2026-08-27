<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use PDO;

class AuditLogController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        $pdo = Database::getConnection();

        $module = trim($_GET['module'] ?? '');
        $action = trim($_GET['action'] ?? '');
        $userId = (int)($_GET['user_id'] ?? 0);
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');
        $search = trim($_GET['search'] ?? '');

        $sql = "SELECT l.*, u.name as user_name, u.email as user_email, u.designation 
            FROM activity_logs l 
            LEFT JOIN users u ON l.user_id = u.id 
            WHERE 1=1";

        $params = [];
        if ($module !== '') {
            $sql .= " AND l.module = ?";
            $params[] = $module;
        }
        if ($action !== '') {
            $sql .= " AND l.action = ?";
            $params[] = $action;
        }
        if ($userId > 0) {
            $sql .= " AND l.user_id = ?";
            $params[] = $userId;
        }
        if ($dateFrom !== '') {
            $sql .= " AND DATE(l.created_at) >= ?";
            $params[] = $dateFrom;
        }
        if ($dateTo !== '') {
            $sql .= " AND DATE(l.created_at) <= ?";
            $params[] = $dateTo;
        }
        if ($search !== '') {
            $sql .= " AND (l.description LIKE ? OR u.name LIKE ? OR l.ip_address LIKE ? OR l.details_json LIKE ?)";
            $term = "%{$search}%";
            $params = array_merge($params, [$term, $term, $term, $term]);
        }

        $sql .= " ORDER BY l.created_at DESC LIMIT 150";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        $modules = $pdo->query("SELECT DISTINCT module FROM activity_logs ORDER BY module ASC")->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $actions = $pdo->query("SELECT DISTINCT action FROM activity_logs ORDER BY action ASC")->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $users = $pdo->query("SELECT id, name FROM users ORDER BY name ASC")->fetchAll() ?: [];

        require_once dirname(__DIR__) . '/Views/audit-logs/index.php';
    }

    public function exportCsv(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $stmt = $pdo->query("SELECT l.created_at, u.name as actor, l.actor_type, l.action, l.module, l.description, l.details_json, l.ip_address 
            FROM activity_logs l 
            LEFT JOIN users u ON l.user_id = u.id 
            ORDER BY l.created_at DESC LIMIT 1000");
        $logs = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=visatrack_audit_logs_' . date('Ymd_His') . '.csv');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Timestamp', 'Actor Name', 'Actor Type', 'Action', 'Module', 'Description', 'Context Data', 'IP Address']);

        foreach ($logs as $row) {
            fputcsv($out, [
                $row['created_at'],
                $row['actor'] ?? 'System / Guest',
                $row['actor_type'],
                $row['action'],
                $row['module'],
                $row['description'],
                $row['details_json'] ?? '',
                $row['ip_address']
            ]);
        }

        fclose($out);
        exit;
    }
}
