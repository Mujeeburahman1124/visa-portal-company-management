<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use PDO;

class AgentPortalController
{
    private function guard(): array
    {
        session_start_safe();
        if (empty($_SESSION['agent_auth'])) {
            redirect('/agent/login');
        }
        return $_SESSION['agent_auth'];
    }

    public function showLogin(): void
    {
        session_start_safe();
        if (!empty($_SESSION['agent_auth'])) {
            redirect('/agent/dashboard');
        }
        $pageTitle = 'Agent Portal Login — MS Travel Hub';
        $flash = get_flash();
        require_once dirname(__DIR__) . '/Views/agent-portal/login.php';
    }

    public function login(): void
    {
        session_start_safe();
        $pdo   = Database::getConnection();
        $email = trim($_POST['email'] ?? '');
        $pass  = trim($_POST['password'] ?? '');

        if (empty($email) || empty($pass)) {
            set_flash('Email and password are required.', 'danger');
            redirect('/agent/login');
        }

        $stmt = $pdo->prepare("SELECT * FROM agents WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$email]);
        $agent = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$agent || empty($agent['password_hash']) || !password_verify($pass, $agent['password_hash'])) {
            set_flash('Invalid credentials or account not activated.', 'danger');
            redirect('/agent/login');
        }

        $_SESSION['agent_auth'] = [
            'id'           => $agent['id'],
            'agent_code'   => $agent['agent_code'],
            'company_name' => $agent['company_name'],
            'contact_person' => $agent['contact_person'],
            'email'        => $agent['email'],
        ];

        $pdo->prepare("UPDATE agents SET last_login_at = NOW() WHERE id = ?")->execute([$agent['id']]);
        redirect('/agent/dashboard');
    }

    public function logout(): void
    {
        session_start_safe();
        unset($_SESSION['agent_auth']);
        redirect('/agent/login');
    }

    public function dashboard(): void
    {
        $agent = $this->guard();
        $pdo   = Database::getConnection();
        $agentId = (int)$agent['id'];

        // KPIs
        $totalApps   = (int)$pdo->query("SELECT COUNT(*) FROM agent_applications WHERE agent_id = {$agentId}")->fetchColumn();
        $activeApps  = (int)$pdo->query("SELECT COUNT(*) FROM agent_applications aa JOIN applications a ON a.id=aa.application_id WHERE aa.agent_id={$agentId} AND a.status NOT IN ('Approved','Rejected','Cancelled')")->fetchColumn();
        $approvedApps= (int)$pdo->query("SELECT COUNT(*) FROM agent_applications aa JOIN applications a ON a.id=aa.application_id WHERE aa.agent_id={$agentId} AND a.status='Approved'")->fetchColumn();

        $agentRow = $pdo->query("SELECT current_balance, credit_limit, commission_rate FROM agents WHERE id={$agentId}")->fetch(PDO::FETCH_ASSOC);

        // Recent applications
        $recentStmt = $pdo->prepare("SELECT a.*, vs.name as service_name, ct.name as country_name, ct.flag_emoji,
            c.full_name as customer_name, c.customer_code
            FROM agent_applications aa
            JOIN applications a ON a.id = aa.application_id
            JOIN visa_services vs ON vs.id = a.visa_service_id
            JOIN countries ct ON ct.id = vs.country_id
            JOIN customers c ON c.id = a.customer_id
            WHERE aa.agent_id = ?
            ORDER BY a.created_at DESC LIMIT 10");
        $recentStmt->execute([$agentId]);
        $recentApps = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

        // Recent payments
        $payStmt = $pdo->prepare("SELECT * FROM agent_payments WHERE agent_id = ? ORDER BY created_at DESC LIMIT 5");
        $payStmt->execute([$agentId]);
        $recentPayments = $payStmt->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'Agent Dashboard — MS Travel Hub';
        $flash = get_flash();
        require_once dirname(__DIR__) . '/Views/agent-portal/dashboard.php';
    }

    public function applications(): void
    {
        $agent = $this->guard();
        $pdo   = Database::getConnection();
        $agentId = (int)$agent['id'];

        $status = trim($_GET['status'] ?? '');
        $search = trim($_GET['search'] ?? '');

        $sql = "SELECT a.*, aa.agent_reference, aa.agent_price,
            vs.name as service_name, ct.name as country_name, ct.flag_emoji,
            c.full_name as customer_name, c.customer_code, c.mobile as customer_mobile
            FROM agent_applications aa
            JOIN applications a ON a.id = aa.application_id
            JOIN visa_services vs ON vs.id = a.visa_service_id
            JOIN countries ct ON ct.id = vs.country_id
            JOIN customers c ON c.id = a.customer_id
            WHERE aa.agent_id = ?";
        $params = [$agentId];

        if ($status !== '') {
            $sql .= " AND a.status = ?";
            $params[] = $status;
        }
        if ($search !== '') {
            $sql .= " AND (a.application_number LIKE ? OR c.full_name LIKE ? OR c.customer_code LIKE ?)";
            $t = "%{$search}%";
            $params[] = $t; $params[] = $t; $params[] = $t;
        }
        $sql .= " ORDER BY a.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'My Applications — Agent Portal';
        $flash = get_flash();
        require_once dirname(__DIR__) . '/Views/agent-portal/applications.php';
    }

    public function createApplication(): void
    {
        $agent = $this->guard();
        $pdo   = Database::getConnection();

        $countries  = $pdo->query("SELECT id, name, flag_emoji, iso_code FROM countries WHERE is_active=1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $services   = $pdo->query("SELECT vs.*, ct.name as country_name, ct.flag_emoji FROM visa_services vs JOIN countries ct ON ct.id=vs.country_id WHERE vs.is_active=1 ORDER BY ct.name, vs.name")->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'Submit New Application — Agent Portal';
        $flash = get_flash();
        require_once dirname(__DIR__) . '/Views/agent-portal/create-application.php';
    }

    public function storeApplication(): void
    {
        $agent   = $this->guard();
        $pdo     = Database::getConnection();
        $agentId = (int)$agent['id'];

        // Customer fields
        $firstName    = trim($_POST['first_name'] ?? '');
        $lastName     = trim($_POST['last_name'] ?? '');
        $fullName     = trim(($_POST['first_name'] ?? '') . ' ' . ($_POST['last_name'] ?? ''));
        $mobile       = trim($_POST['mobile'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $nationality  = trim($_POST['nationality'] ?? '');
        $dob          = trim($_POST['dob'] ?? '');
        $gender       = trim($_POST['gender'] ?? '');
        $passportNum  = trim($_POST['passport_number'] ?? '');
        $passExpiry   = trim($_POST['passport_expiry'] ?? '');

        // Application fields
        $serviceId    = (int)($_POST['visa_service_id'] ?? 0);
        $travelDate   = trim($_POST['travel_date'] ?? '');
        $returnDate   = trim($_POST['return_date'] ?? '');
        $agentRef     = trim($_POST['agent_reference'] ?? '');
        $agentPrice   = (float)($_POST['agent_price'] ?? 0);
        $notes        = trim($_POST['notes'] ?? '');

        if (empty($firstName) || empty($lastName) || empty($mobile) || $serviceId <= 0) {
            set_flash('Please fill in all required fields.', 'danger');
            redirect('/agent/create-application');
        }

        // Get service details
        $svc = $pdo->prepare("SELECT vs.*, ct.name as country_name FROM visa_services vs JOIN countries ct ON ct.id=vs.country_id WHERE vs.id=?");
        $svc->execute([$serviceId]);
        $service = $svc->fetch(PDO::FETCH_ASSOC);
        if (!$service) {
            set_flash('Invalid visa service selected.', 'danger');
            redirect('/agent/create-application');
        }

        // Create or find customer
        $custStmt = $pdo->prepare("SELECT id FROM customers WHERE mobile = ? LIMIT 1");
        $custStmt->execute([$mobile]);
        $existingCust = $custStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingCust) {
            $customerId = (int)$existingCust['id'];
        } else {
            // Auto-generate customer code
            $lastCode = $pdo->query("SELECT customer_code FROM customers ORDER BY id DESC LIMIT 1")->fetchColumn();
            $nextNum  = $lastCode ? (int)substr($lastCode, 4) + 1 : 1;
            $custCode = 'MSC-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);

            $cStmt = $pdo->prepare("INSERT INTO customers (customer_code, first_name, last_name, full_name, mobile, email, nationality, dob, gender, current_country) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $cStmt->execute([$custCode, $firstName, $lastName, $fullName, $mobile, $email, $nationality, $dob ?: null, $gender, $nationality]);
            $customerId = (int)$pdo->lastInsertId();

            // Save passport if provided
            if (!empty($passportNum) && !empty($passExpiry)) {
                $pdo->prepare("INSERT INTO customer_passports (customer_id, passport_number, issuing_country, expiry_date, is_primary) VALUES (?,?,?,?,1)")->execute([$customerId, $passportNum, $nationality, $passExpiry]);
            }
        }

        // Generate application number
        $lastApp = $pdo->query("SELECT application_number FROM applications ORDER BY id DESC LIMIT 1")->fetchColumn();
        $year    = date('Y');
        if ($lastApp && str_contains($lastApp, $year)) {
            $nextAppNum = (int)substr($lastApp, -6) + 1;
        } else {
            $nextAppNum = 1;
        }
        $appNumber = 'MSV-' . $year . '-' . str_pad($nextAppNum, 6, '0', STR_PAD_LEFT);

        // Insert application
        $aStmt = $pdo->prepare("INSERT INTO applications
            (application_number, customer_id, visa_service_id, selling_price, total_amount, balance_amount,
             agent_id, agent_price, current_stage, status, travel_date, return_date, application_date, internal_notes)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW(),?)");
        $sellingPrice = $agentPrice > 0 ? $agentPrice : (float)$service['selling_price'];
        $aStmt->execute([$appNumber, $customerId, $serviceId, $sellingPrice, $sellingPrice, $sellingPrice,
                         $agentId, $agentPrice, 'Application Registered', 'Pending', $travelDate ?: null, $returnDate ?: null,
                         "Submitted by agent: {$agent['company_name']}. " . $notes]);
        $appId = (int)$pdo->lastInsertId();

        // Link to agent
        $pdo->prepare("INSERT INTO agent_applications (agent_id, application_id, agent_price, agent_reference) VALUES (?,?,?,?)")->execute([$agentId, $appId, $agentPrice, $agentRef]);

        set_flash("Application {$appNumber} submitted successfully!", 'success');
        redirect('/agent/applications');
    }

    public function updateApplication(): void
    {
        $agent   = $this->guard();
        $pdo     = Database::getConnection();
        $agentId = (int)$agent['id'];

        $appId    = (int)($_POST['application_id'] ?? 0);
        $agentRef = trim($_POST['agent_reference'] ?? '');
        $notes    = trim($_POST['notes'] ?? '');

        // Verify application belongs to this agent
        $check = $pdo->prepare("SELECT a.id, a.application_number, a.internal_notes FROM applications a JOIN agent_applications aa ON aa.application_id = a.id WHERE a.id = ? AND aa.agent_id = ?");
        $check->execute([$appId, $agentId]);
        $app = $check->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            set_flash('Application not found or unauthorized access.', 'danger');
            redirect('/agent/applications');
        }

        // Update agent reference in agent_applications
        $pdo->prepare("UPDATE agent_applications SET agent_reference = ? WHERE application_id = ? AND agent_id = ?")->execute([$agentRef, $appId, $agentId]);

        // Append note to application if provided
        if (!empty($notes)) {
            $existingNotes = $app['internal_notes'] ?? '';
            $timestamp = date('d M Y H:i');
            $newNote = "[Agent Note - {$timestamp}]: {$notes}";
            $combinedNotes = !empty($existingNotes) ? "{$existingNotes}\n{$newNote}" : $newNote;
            $pdo->prepare("UPDATE applications SET internal_notes = ? WHERE id = ?")->execute([$combinedNotes, $appId]);
        }

        set_flash("Application {$app['application_number']} updated successfully.", 'success');
        redirect('/agent/applications');
    }

    public function profile(): void
    {
        $agent   = $this->guard();
        $pdo     = Database::getConnection();
        $agentId = (int)$agent['id'];

        $agentData = $pdo->query("SELECT * FROM agents WHERE id = {$agentId}")->fetch(PDO::FETCH_ASSOC);
        $payments  = $pdo->query("SELECT * FROM agent_payments WHERE agent_id = {$agentId} ORDER BY created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'My Profile — Agent Portal';
        $flash = get_flash();
        require_once dirname(__DIR__) . '/Views/agent-portal/profile.php';
    }

    public function updateProfile(): void
    {
        $agent   = $this->guard();
        $pdo     = Database::getConnection();
        $agentId = (int)$agent['id'];

        $contact  = trim($_POST['contact_person'] ?? '');
        $mobile   = trim($_POST['mobile'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $city     = trim($_POST['city'] ?? '');
        $password = trim($_POST['password'] ?? '');

        $sql = "UPDATE agents SET contact_person=?, mobile=?, whatsapp=?, address=?, city=?";
        $params = [$contact, $mobile, $whatsapp, $address, $city];
        if (!empty($password)) {
            $sql .= ", password_hash=?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        $sql .= " WHERE id=?";
        $params[] = $agentId;
        $pdo->prepare($sql)->execute($params);

        // Update session
        $_SESSION['agent_auth']['contact_person'] = $contact;

        set_flash('Profile updated successfully.', 'success');
        redirect('/agent/profile');
    }
}
