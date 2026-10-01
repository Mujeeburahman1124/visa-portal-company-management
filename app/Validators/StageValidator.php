<?php
declare(strict_types=1);

namespace App\Validators;

class StageValidator extends Validator
{
    public const VALID_STAGES = [
        // Primary Flow
        'New Application',
        'Application Registered',
        'Pending Review',
        'Documents Required',
        'Documents Submitted',
        'Documents Collected',
        'Documents Under Review',
        'Documents Under Verification',
        'Documents Verified',
        'Documents Approved',
        'Ready for Submission',
        'Security / Blacklist Check',
        'Submitted / Posted',
        'Application Submitted',
        'In Process',
        'Medical / Biometrics Processing',
        'Approved',
        'Visa Issued & Completed',

        // Return & Modification Flow
        'Returned',
        'Returned / Modification Required',
        'Modification Required',
        'Customer Documents Required',
        'Documents Resubmitted',
        'Resubmitted',

        // Additional Specialized States
        'Application Rejected',
        'Rejected',
        'Cancelled',
        'On Hold',
        'Waiting for Customer',
        'Waiting for Supplier',
        'Waiting for Embassy'
    ];

    public const VALID_STATUSES = [
        'Draft',
        'Registered',
        'New Application',
        'Pending Review',
        'Documents Pending',
        'Documents Required',
        'Documents Submitted',
        'Documents Under Verification',
        'Documents Under Review',
        'In Review',
        'Documents Approved',
        'Ready',
        'Ready for Submission',
        'Submitted',
        'Submitted / Posted',
        'In Process',
        'Processing',
        'Returned',
        'Modification Required',
        'Customer Documents Required',
        'Documents Resubmitted',
        'Action Required',
        'Approved',
        'Completed',
        'Rejected',
        'Refused',
        'Cancelled',
        'On Hold',
        'Waiting for Customer',
        'Waiting for Supplier',
        'Waiting for Embassy'
    ];

    public const ALLOWED_TRANSITIONS = [
        'New Application' => ['Application Registered', 'Pending Review', 'Documents Required', 'Documents Collected', 'Cancelled', 'On Hold'],
        'Application Registered' => ['Pending Review', 'Documents Required', 'Documents Collected', 'Documents Under Review', 'Cancelled', 'On Hold'],
        'Pending Review' => ['Documents Required', 'Documents Collected', 'Documents Under Review', 'Returned / Modification Required', 'Cancelled', 'On Hold'],
        'Documents Required' => ['Documents Submitted', 'Documents Collected', 'Documents Under Review', 'Cancelled', 'On Hold'],
        'Documents Submitted' => ['Documents Collected', 'Documents Under Review', 'Documents Under Verification', 'Cancelled', 'On Hold'],
        'Documents Collected' => ['Documents Under Review', 'Documents Under Verification', 'Cancelled', 'On Hold'],
        'Documents Under Review' => ['Documents Under Verification', 'Documents Verified', 'Documents Approved', 'Returned / Modification Required', 'Customer Documents Required', 'Cancelled', 'On Hold'],
        'Documents Under Verification' => ['Documents Verified', 'Documents Approved', 'Returned / Modification Required', 'Customer Documents Required', 'Cancelled', 'On Hold'],
        'Documents Verified' => ['Documents Approved', 'Ready for Submission', 'Security / Blacklist Check', 'Submitted / Posted', 'Application Submitted', 'Returned / Modification Required', 'Cancelled', 'On Hold'],
        'Documents Approved' => ['Ready for Submission', 'Security / Blacklist Check', 'Submitted / Posted', 'Application Submitted', 'Returned / Modification Required', 'Cancelled', 'On Hold'],
        'Ready for Submission' => ['Submitted / Posted', 'Application Submitted', 'Security / Blacklist Check', 'Returned / Modification Required', 'Cancelled', 'On Hold'],
        'Security / Blacklist Check' => ['Ready for Submission', 'Submitted / Posted', 'Application Submitted', 'Rejected', 'Cancelled', 'On Hold'],
        'Submitted / Posted' => ['In Process', 'Medical / Biometrics Processing', 'Approved', 'Visa Issued & Completed', 'Rejected', 'Cancelled', 'On Hold'],
        'Application Submitted' => ['In Process', 'Medical / Biometrics Processing', 'Approved', 'Visa Issued & Completed', 'Rejected', 'Cancelled', 'On Hold'],
        'In Process' => ['Medical / Biometrics Processing', 'Approved', 'Visa Issued & Completed', 'Rejected', 'Returned / Modification Required', 'Cancelled', 'On Hold'],
        'Medical / Biometrics Processing' => ['In Process', 'Approved', 'Visa Issued & Completed', 'Rejected', 'Cancelled', 'On Hold'],
        'Approved' => ['Visa Issued & Completed', 'Cancelled'],
        'Returned' => ['Documents Resubmitted', 'Documents Under Review', 'Cancelled'],
        'Returned / Modification Required' => ['Documents Resubmitted', 'Documents Under Review', 'Cancelled'],
        'Modification Required' => ['Documents Resubmitted', 'Documents Under Review', 'Cancelled'],
        'Customer Documents Required' => ['Documents Submitted', 'Documents Resubmitted', 'Documents Under Review', 'Cancelled'],
        'Documents Resubmitted' => ['Documents Under Review', 'Documents Under Verification', 'Cancelled'],
        'Resubmitted' => ['Documents Under Review', 'Documents Under Verification', 'Cancelled'],
        'On Hold' => ['Application Registered', 'Documents Required', 'Documents Under Review', 'Documents Verified', 'Ready for Submission', 'In Process', 'Cancelled'],
        'Waiting for Customer' => ['Documents Submitted', 'Documents Resubmitted', 'Documents Under Review', 'In Process', 'Cancelled'],
        'Waiting for Supplier' => ['In Process', 'Approved', 'Rejected', 'Cancelled'],
        'Waiting for Embassy' => ['In Process', 'Approved', 'Rejected', 'Cancelled'],
        'Visa Issued & Completed' => [],
        'Application Rejected' => [],
        'Rejected' => [],
        'Cancelled' => [],
    ];

    public const CANONICAL_STATUS_MAP = [
        'New Application' => 'New Application',
        'Application Registered' => 'Registered',
        'Pending Review' => 'Pending Review',
        'Documents Required' => 'Documents Required',
        'Documents Submitted' => 'Documents Pending',
        'Documents Collected' => 'Documents Pending',
        'Documents Under Review' => 'Documents Under Review',
        'Documents Under Verification' => 'Documents Under Review',
        'Documents Verified' => 'Documents Approved',
        'Documents Approved' => 'Documents Approved',
        'Ready for Submission' => 'Ready for Submission',
        'Security / Blacklist Check' => 'In Process',
        'Submitted / Posted' => 'Submitted',
        'Application Submitted' => 'Submitted',
        'In Process' => 'In Process',
        'Medical / Biometrics Processing' => 'In Process',
        'Approved' => 'Approved',
        'Visa Issued & Completed' => 'Completed',
        'Returned' => 'Modification Required',
        'Returned / Modification Required' => 'Modification Required',
        'Modification Required' => 'Modification Required',
        'Customer Documents Required' => 'Customer Documents Required',
        'Documents Resubmitted' => 'Documents Resubmitted',
        'Resubmitted' => 'Documents Resubmitted',
        'Application Rejected' => 'Rejected',
        'Rejected' => 'Rejected',
        'Cancelled' => 'Cancelled',
        'On Hold' => 'On Hold',
        'Waiting for Customer' => 'Waiting for Customer',
        'Waiting for Supplier' => 'Waiting for Supplier',
        'Waiting for Embassy' => 'Waiting for Embassy'
    ];

    public const STAGES_REQUIRING_DOCUMENTS_VERIFIED = [
        'Documents Verified',
        'Documents Approved',
        'Ready for Submission',
        'Security / Blacklist Check',
        'Submitted / Posted',
        'Application Submitted',
        'Approved',
        'Visa Issued & Completed'
    ];

    public static function getCanonicalStatus(string $stage, ?string $fallback = null): string
    {
        return self::CANONICAL_STATUS_MAP[$stage] ?? ($fallback ?: 'Processing');
    }

    public static function isTransitionAllowed(string $fromStage, string $toStage, bool $isAdmin = false): bool
    {
        if ($fromStage === $toStage) {
            return true;
        }

        // Admin override allows recovery transitions except from final states
        if ($isAdmin && !in_array($fromStage, ['Visa Issued & Completed', 'Cancelled'], true)) {
            return true;
        }

        $allowed = self::ALLOWED_TRANSITIONS[$fromStage] ?? [];
        return in_array($toStage, $allowed, true);
    }

    public function validate(array $data): bool
    {
        $this->validateRequired($data, [
            'application_id' => 'Application ID',
            'new_stage' => 'Lifecycle Stage',
            'new_status' => 'Status'
        ]);

        if (!empty($data['new_stage']) && !in_array($data['new_stage'], self::VALID_STAGES, true)) {
            $this->addError('new_stage', 'The selected lifecycle stage is invalid.');
        }

        if (!empty($data['new_status']) && !in_array($data['new_status'], self::VALID_STATUSES, true)) {
            $this->addError('new_status', 'The selected application status is invalid.');
        }

        if (!empty($data['current_stage']) && !empty($data['new_stage'])) {
            $isAdmin = !empty($data['is_admin']);
            if (!self::isTransitionAllowed((string)$data['current_stage'], (string)$data['new_stage'], $isAdmin)) {
                $this->addError('new_stage', "Invalid transition from '{$data['current_stage']}' to '{$data['new_stage']}'.");
            }
        }

        return $this->isValid();
    }
}
