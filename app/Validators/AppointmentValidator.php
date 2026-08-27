<?php
declare(strict_types=1);

namespace App\Validators;

class AppointmentValidator extends Validator
{
    public const VALID_TYPES = [
        'Biometrics Capture',
        'Medical Fitness Test',
        'Embassy Consular Interview',
        'Document Signing / Handover'
    ];

    public const VALID_STATUSES = [
        'Scheduled',
        'Confirmed',
        'Completed',
        'Cancelled',
        'Missed'
    ];

    public function validate(array $data): bool
    {
        $this->validateRequired($data, [
            'application_id' => 'Application Reference',
            'appointment_type' => 'Appointment Type',
            'center_name' => 'Center / Venue Name',
            'appointment_date' => 'Appointment Date',
            'appointment_time' => 'Appointment Time'
        ]);

        if (!empty($data['appointment_date'])) {
            $this->validateDate($data['appointment_date'], 'appointment_date', 'Appointment Date');
        }

        if (!empty($data['appointment_type']) && !in_array($data['appointment_type'], self::VALID_TYPES, true)) {
            $this->addError('appointment_type', 'Invalid appointment type selected.');
        }

        if (!empty($data['status']) && !in_array($data['status'], self::VALID_STATUSES, true)) {
            $this->addError('status', 'Invalid appointment status selected.');
        }

        return $this->isValid();
    }
}
