<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Config\Database;
use App\Services\AuditService;
use App\Services\DuplicateDetectionService;
use App\Validators\ApplicantValidator;
use PDO;

class ApplicantApiController extends ApiController
{
    /**
     * GET /api/applicants
     */
    public function index(): void
    {
        $pdo = Database::getConnection();
        $search = trim($_GET['search'] ?? '');
        
        $sql = "SELECT c.*, 
                    cp.passport_number, cp.expiry_date as passport_expiry_date,
                    (SELECT COUNT(*) FROM applications WHERE customer_id = c.id) as total_applications
                FROM customers c 
                LEFT JOIN customer_passports cp ON c.id = cp.customer_id AND cp.is_primary = 1
                WHERE 1=1";

        $params = [];
        if ($search !== '') {
            $sql .= " AND (c.full_name LIKE ? OR c.customer_code LIKE ? OR c.email LIKE ? OR c.mobile LIKE ? OR cp.passport_number LIKE ?)";
            $term = "%{$search}%";
            $params = [$term, $term, $term, $term, $term];
        }

        $sql .= " ORDER BY c.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->jsonSuccess($applicants, 'Applicants retrieved');
    }

    /**
     * GET /api/applicants/check-duplicate
     */
    public function checkDuplicate(): void
    {
        $mobile = trim($_GET['mobile'] ?? '');
        $email = trim($_GET['email'] ?? '');
        $passport = trim($_GET['passport'] ?? '');
        $excludeId = !empty($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : null;

        $res = DuplicateDetectionService::checkDuplicate($mobile, $email, $passport, $excludeId);
        $this->jsonSuccess($res, 'Duplicate check completed');
    }

    /**
     * POST /api/applicants
     */
    public function store(): void
    {
        $input = $this->getJsonInput();
        $user = auth_user();
        $userId = $user ? (int)$user['id'] : null;

        $validator = new ApplicantValidator();
        if (!$validator->validate($input)) {
            $this->jsonError($validator->getFirstError() ?? 'Validation failed', $validator->getErrors(), 422);
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $customerCode = 'MSC-' . date('y') . sprintf('%05d', rand(1000, 99999));
            $fullName = trim($input['first_name'] . ' ' . ($input['middle_name'] ?? '') . ' ' . $input['last_name']);

            $stmt = $pdo->prepare("INSERT INTO customers 
                (customer_code, first_name, middle_name, last_name, full_name, gender, dob, nationality, mobile, whatsapp, email, current_country, address, occupation, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $customerCode,
                trim($input['first_name']),
                trim($input['middle_name'] ?? ''),
                trim($input['last_name']),
                $fullName,
                $input['gender'] ?? 'Male',
                $input['dob'] ?: null,
                $input['nationality'],
                trim($input['mobile']),
                trim($input['whatsapp'] ?? ''),
                trim($input['email'] ?? '') ?: null,
                $input['current_country'] ?? 'United Arab Emirates',
                trim($input['address'] ?? ''),
                trim($input['occupation'] ?? ''),
                $userId
            ]);

            $applicantId = (int)$pdo->lastInsertId();

            // Insert Passport
            if (!empty($input['passport_number'])) {
                $stmtPass = $pdo->prepare("INSERT INTO customer_passports 
                    (customer_id, passport_number, issuing_country, issue_date, expiry_date, is_primary) 
                    VALUES (?, ?, ?, ?, ?, 1)");
                $stmtPass->execute([
                    $applicantId,
                    trim($input['passport_number']),
                    $input['nationality'],
                    $input['passport_issue_date'] ?: null,
                    $input['passport_expiry_date']
                ]);
            }

            AuditService::log('CREATE_APPLICANT', 'Customers', $applicantId, "Registered applicant {$fullName} ({$customerCode})", $input, $userId);

            $pdo->commit();

            $this->jsonSuccess([
                'id' => $applicantId,
                'customer_code' => $customerCode,
                'full_name' => $fullName
            ], 'Applicant registered successfully', 201);
        } catch (\Exception $e) {
            $pdo->rollBack();
            $this->jsonError('Failed to register applicant: ' . $e->getMessage(), [], 500);
        }
    }
}
