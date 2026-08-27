<?php
declare(strict_types=1);

namespace App\Validators;

use App\Config\Database;
use PDO;

class ApplicantValidator extends Validator
{
    public function validate(array $data, ?int $applicantId = null): bool
    {
        $this->validateRequired($data, [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'nationality' => 'Nationality',
            'mobile' => 'Mobile Phone Number'
        ]);

        if (!empty($data['email'])) {
            $this->validateEmail($data['email']);
            // Unique check
            $pdo = Database::getConnection();
            $sql = "SELECT id FROM customers WHERE LOWER(email) = LOWER(?)";
            $params = [trim($data['email'])];
            if ($applicantId) {
                $sql .= " AND id != ?";
                $params[] = $applicantId;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            if ($stmt->fetch()) {
                $this->addError('email', 'An applicant with this email address already exists.');
            }
        }

        if (!empty($data['dob'])) {
            $this->validateDate($data['dob'], 'dob', 'Date of Birth');
        }

        if (!empty($data['passport_number'])) {
            $this->validateRequired($data, [
                'passport_expiry_date' => 'Passport Expiry Date'
            ]);
            $this->validateDate($data['passport_expiry_date'], 'passport_expiry_date', 'Passport Expiry Date');
            
            if (!empty($data['passport_issue_date'])) {
                $this->validateDate($data['passport_issue_date'], 'passport_issue_date', 'Passport Issue Date');
            }
        }

        return $this->isValid();
    }
}
