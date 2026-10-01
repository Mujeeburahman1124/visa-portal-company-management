<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Config\Database;
use App\Services\AuditService;
use App\Services\DocumentChecklistService;
use App\Services\DocumentVerificationService;
use App\Services\DocumentExpiryService;
use PDO;

class DocumentApiController extends ApiController
{
    /**
     * GET /api/documents?application_id=...
     */
    public function index(int $applicationId = 0): void
    {
        $user = $this->requireAuth();
        $scopedBranchId = $this->getScopedBranchId($user);

        if ($applicationId <= 0) {
            $applicationId = (int)($_GET['application_id'] ?? 0);
        }

        $pdo = Database::getConnection();

        if ($applicationId > 0) {
            // Verify application access and branch scoping
            if ($scopedBranchId !== null) {
                $checkStmt = $pdo->prepare("SELECT id FROM applications WHERE id = ? AND branch_id = ?");
                $checkStmt->execute([$applicationId, $scopedBranchId]);
                if (!$checkStmt->fetch()) {
                    $this->jsonError('Application not found or access denied for your branch.', [], 403);
                    return;
                }
            }

            $checklist = DocumentChecklistService::getChecklist($applicationId);
            $this->jsonSuccess($checklist, 'Application document checklist retrieved');
            return;
        }

        $status = $_GET['status'] ?? '';
        $sql = "SELECT d.*, dt.name as document_type_name, dt.category, c.full_name as customer_name, a.application_number 
            FROM documents d 
            JOIN document_types dt ON d.document_type_id = dt.id 
            JOIN customers c ON d.customer_id = c.id
            LEFT JOIN applications a ON d.application_id = a.id
            WHERE 1=1";
        $params = [];

        if ($scopedBranchId !== null) {
            $sql .= " AND (a.branch_id = ? OR (a.branch_id IS NULL AND c.branch_id = ?))";
            $params[] = $scopedBranchId;
            $params[] = $scopedBranchId;
        }

        if ($status !== '') {
            $sql .= " AND d.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY d.created_at DESC LIMIT 100";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->jsonSuccess($docs, 'Documents retrieved successfully');
    }

    /**
     * POST /api/documents/verify
     */
    public function verify(int $id = 0): void
    {
        $user = auth_user();
        if (!$user) {
            $this->jsonError('Authentication required.', [], 401);
            return;
        }

        $input = $this->getJsonInput();
        if ($id <= 0) {
            $id = (int)($input['document_id'] ?? $_POST['document_id'] ?? $_GET['id'] ?? 0);
        }

        $userId = (int)$user['id'];
        $notes = trim($input['notes'] ?? '');

        $res = DocumentVerificationService::verify($id, $userId, $notes);
        if (!$res['success']) {
            $this->jsonError($res['message'], [], 422);
        }

        $this->jsonSuccess($res['data'], $res['message']);
    }

    /**
     * POST /api/documents/reject
     */
    public function reject(int $id = 0): void
    {
        $user = auth_user();
        if (!$user) {
            $this->jsonError('Authentication required.', [], 401);
            return;
        }

        $input = $this->getJsonInput();
        if ($id <= 0) {
            $id = (int)($input['document_id'] ?? $_POST['document_id'] ?? $_GET['id'] ?? 0);
        }

        $reason = trim($input['reason'] ?? $input['rejection_reason'] ?? '');
        if ($reason === '') {
            $this->jsonError('Rejection reason is required.', ['reason' => 'Required'], 422);
        }

        $userId = (int)$user['id'];
        $notes = trim($input['notes'] ?? '');

        $res = DocumentVerificationService::reject($id, $userId, $reason, $notes);
        if (!$res['success']) {
            $this->jsonError($res['message'], [], 422);
        }

        $this->jsonSuccess($res['data'], $res['message']);
    }

    /**
     * GET /api/documents/expiry-summary
     */
    public function expirySummary(): void
    {
        $this->requireAuth();
        $summary = DocumentExpiryService::getExpirySummary();
        $this->jsonSuccess($summary, 'Document expiry summary');
    }
}
