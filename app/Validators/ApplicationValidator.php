<?php
declare(strict_types=1);

namespace App\Validators;

use App\Config\Database;
use PDO;

class ApplicationValidator extends Validator
{
    public function validate(array $data, ?int $applicationId = null): bool
    {
        $this->validateRequired($data, [
            'customer_id' => 'Applicant / Customer',
            'visa_service_id' => 'Visa Service / Type',
            'assigned_staff_id' => 'Assigned Visa Officer'
        ]);

        $pdo = Database::getConnection();

        // Validate Applicant Exists
        if (!empty($data['customer_id'])) {
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE id = ?");
            $stmt->execute([(int)$data['customer_id']]);
            if (!$stmt->fetch()) {
                $this->addError('customer_id', 'Selected applicant does not exist in the database.');
            }
        }

        // Validate Visa Service Exists
        if (!empty($data['visa_service_id'])) {
            $stmt = $pdo->prepare("SELECT id FROM visa_services WHERE id = ?");
            $stmt->execute([(int)$data['visa_service_id']]);
            if (!$stmt->fetch()) {
                $this->addError('visa_service_id', 'Selected visa service does not exist.');
            }
        }

        // Validate Staff Exists
        if (!empty($data['assigned_staff_id'])) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND is_active = 1");
            $stmt->execute([(int)$data['assigned_staff_id']]);
            if (!$stmt->fetch()) {
                $this->addError('assigned_staff_id', 'Assigned staff officer is not a valid active user.');
            }
        }

        // Validate Financials
        $this->validateNumeric($data['selling_price'] ?? null, 'selling_price', 'Selling Price', 0);
        $this->validateNumeric($data['discount'] ?? null, 'discount', 'Discount Amount', 0);
        $this->validateNumeric($data['tax_amount'] ?? null, 'tax_amount', 'Tax Amount', 0);

        // Validate Dates
        if (!empty($data['travel_date'])) {
            $this->validateDate($data['travel_date'], 'travel_date', 'Travel Date');
        }
        if (!empty($data['return_date'])) {
            $this->validateDate($data['return_date'], 'return_date', 'Return Date');
        }

        return $this->isValid();
    }
}
