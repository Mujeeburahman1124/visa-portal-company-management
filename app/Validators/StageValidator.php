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

        return $this->isValid();
    }
}
