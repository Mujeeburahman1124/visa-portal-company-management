<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\AuditService;
use PDO;

class AgentController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'accounts', 'branch-manager']);
        $pdo = Database::getConnection();

        $search = trim($_GET['search'] ?? '');
        $sql = "SELECT a.*,
            COUNT(DISTINCT aa.id) as total_applications,
            COALESCE(SUM(CASE WHEN ap.payment_type='Payment' THEN ap.amount ELSE 0 END),0) as total_paid
            FROM agents a
            LEFT JOIN agent_applications aa ON aa.agent_id = a.id
            LEFT JOIN agent_payments ap ON ap.agent_id = a.id
            WHERE 1=1";
        $params = [];
        if ($search !== '') {
            $sql .= " AND (a.company_name LIKE ? OR a.agent_code LIKE ? OR a.email LIKE ? OR a.contact_person LIKE ?)";
            $t = "%{$search}%";
            $params = [$t, $t, $t, $t];
        }
        $sql .= " GROUP BY a.id ORDER BY a.is_active DESC, a.company_name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once dirname(__DIR__) . '/Views/agents/index.php';
    }

    public function store(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'accounts']);
        $pdo = Database::getConnection();

        $code        = strtoupper(trim($_POST['agent_code'] ?? ''));
        $company     = trim($_POST['company_name'] ?? '');
        $contact     = trim($_POST['contact_person'] ?? '');
        $mobile      = trim($_POST['mobile'] ?? '');
        $whatsapp    = trim($_POST['whatsapp'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $country     = trim($_POST['country'] ?? '');
        $city        = trim($_POST['city'] ?? '');
        $address     = trim($_POST['address'] ?? '');
        $creditLimit = (float)($_POST['credit_limit'] ?? 0);
        $commission  = (float)($_POST['commission_rate'] ?? 0);
        $terms       = trim($_POST['payment_terms'] ?? 'Net 30');
        $bank        = trim($_POST['bank_details'] ?? '');
        $notes       = trim($_POST['notes'] ?? '');
        $password    = trim($_POST['password'] ?? '');

        if (empty($code) || empty($company) || empty($email) || empty($mobile)) {
            redirect('/agents', 'Agent code, company name, email and mobile are required.', 'danger');
        }

        // Check duplicate
        $dup = $pdo->prepare("SELECT COUNT(*) FROM agents WHERE agent_code = ? OR email = ?");
        $dup->execute([$code, $email]);
        if ((int)$dup->fetchColumn() > 0) {
            redirect('/agents', "Agent code '{$code}' or email already exists.", 'danger');
        }

        $hash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

        $stmt = $pdo->prepare("INSERT INTO agents
            (agent_code, company_name, contact_person, mobile, whatsapp, email, password_hash,
             country, city, address, credit_limit, commission_rate, payment_terms, bank_details, notes)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$code, $company, $contact, $mobile, $whatsapp, $email, $hash,
                        $country, $city, $address, $creditLimit, $commission, $terms, $bank, $notes]);
        $agentId = (int)$pdo->lastInsertId();

        AuditService::log('CREATE_AGENT', 'Agents', $agentId, "New agent created: {$company} ({$code})");
        redirect('/agents', "Agent {$code} — {$company} created successfully.", 'success');
    }

    public function update(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'accounts']);
        $pdo = Database::getConnection();

        $id          = (int)($_POST['agent_id'] ?? 0);
        $company     = trim($_POST['company_name'] ?? '');
        $contact     = trim($_POST['contact_person'] ?? '');
        $mobile      = trim($_POST['mobile'] ?? '');
        $whatsapp    = trim($_POST['whatsapp'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $country     = trim($_POST['country'] ?? '');
        $city        = trim($_POST['city'] ?? '');
        $address     = trim($_POST['address'] ?? '');
        $creditLimit = (float)($_POST['credit_limit'] ?? 0);
        $commission  = (float)($_POST['commission_rate'] ?? 0);
        $terms       = trim($_POST['payment_terms'] ?? 'Net 30');
        $bank        = trim($_POST['bank_details'] ?? '');
        $notes       = trim($_POST['notes'] ?? '');
        $password    = trim($_POST['password'] ?? '');
        $isActive    = (int)($_POST['is_active'] ?? 1);

        if ($id <= 0) {
            redirect('/agents', 'Invalid agent.', 'danger');
        }

        $sql = "UPDATE agents SET company_name=?, contact_person=?, mobile=?, whatsapp=?, email=?,
                country=?, city=?, address=?, credit_limit=?, commission_rate=?, payment_terms=?,
                bank_details=?, notes=?, is_active=?";
        $params = [$company, $contact, $mobile, $whatsapp, $email, $country, $city, $address,
                   $creditLimit, $commission, $terms, $bank, $notes, $isActive];

        if (!empty($password)) {
            $sql .= ", password_hash=?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        $sql .= " WHERE id=?";
        $params[] = $id;

        $pdo->prepare($sql)->execute($params);
        AuditService::log('UPDATE_AGENT', 'Agents', $id, "Agent updated: {$company}");
        redirect('/agents', "Agent updated successfully.", 'success');
    }

    public function toggleStatus(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();
        $id = (int)($_POST['agent_id'] ?? 0);
        $pdo->prepare("UPDATE agents SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
        redirect('/agents', 'Agent status updated.', 'success');
    }

    public function recordPayment(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'accounts']);
        $pdo = Database::getConnection();
        $user = auth_user();

        $agentId  = (int)($_POST['agent_id'] ?? 0);
        $amount   = (float)($_POST['amount'] ?? 0);
        $method   = trim($_POST['payment_method'] ?? 'Bank Transfer');
        $txnRef   = trim($_POST['transaction_reference'] ?? '');
        $date     = !empty($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d');
        $type     = trim($_POST['payment_type'] ?? 'Payment');
        $notes    = trim($_POST['notes'] ?? '');
        $appId    = (int)($_POST['application_id'] ?? 0) ?: null;

        if ($agentId <= 0 || $amount <= 0) {
            redirect('/agents', 'Agent and amount are required.', 'danger');
        }

        $ref = 'AGP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
        $stmt = $pdo->prepare("INSERT INTO agent_payments
            (payment_reference, agent_id, application_id, amount, payment_type, payment_method,
             transaction_reference, payment_date, notes, created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$ref, $agentId, $appId, $amount, $type, $method, $txnRef, $date, $notes, $user['id']]);

        // Update agent balance
        $delta = ($type === 'Payment') ? -$amount : $amount;
        $pdo->prepare("UPDATE agents SET current_balance = current_balance + ? WHERE id = ?")->execute([$delta, $agentId]);

        AuditService::log('AGENT_PAYMENT', 'Agents', $agentId, "Recorded agent payment {$ref}: \${$amount}");
        redirect('/agents', "Agent payment {$ref} recorded.", 'success');
    }
}
