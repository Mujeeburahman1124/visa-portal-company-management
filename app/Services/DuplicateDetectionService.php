<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use PDO;

class DuplicateDetectionService
{
    /**
     * Checks for potential duplicate customer entries
     */
    public static function checkCustomer(string $email, string $mobile, ?string $fullName = null, ?string $dob = null, ?int $excludeId = null): array
    {
        $pdo = Database::getConnection();
        $duplicates = [];

        // Check by Email
        if (!empty($email)) {
            $sql = "SELECT id, customer_code, full_name, mobile, email FROM customers WHERE LOWER(email) = LOWER(?)";
            $params = [$email];
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $match = $stmt->fetch();
            if ($match) {
                $duplicates[] = [
                    'type' => 'email',
                    'message' => "A customer with email '{$email}' already exists ({$match['customer_code']} - {$match['full_name']}).",
                    'customer' => $match,
                ];
            }
        }

        // Check by Mobile Phone
        if (!empty($mobile)) {
            $cleanMobile = preg_replace('/[^0-9]/', '', $mobile);
            $sql = "SELECT id, customer_code, full_name, mobile, email FROM customers WHERE REPLACE(REPLACE(REPLACE(mobile, ' ', ''), '-', ''), '+', '') = ?";
            $params = [$cleanMobile];
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $match = $stmt->fetch();
            if ($match) {
                $duplicates[] = [
                    'type' => 'mobile',
                    'message' => "A customer with mobile '{$mobile}' already exists ({$match['customer_code']} - {$match['full_name']}).",
                    'customer' => $match,
                ];
            }
        }

        // Check by Name & Date of Birth
        if (!empty($fullName) && !empty($dob)) {
            $sql = "SELECT id, customer_code, full_name, mobile, email FROM customers WHERE LOWER(full_name) = LOWER(?) AND dob = ?";
            $params = [$fullName, $dob];
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $match = $stmt->fetch();
            if ($match) {
                $duplicates[] = [
                    'type' => 'identity',
                    'message' => "A customer with matching full name '{$fullName}' and DOB ({$dob}) already exists ({$match['customer_code']}).",
                    'customer' => $match,
                ];
            }
        }

        return $duplicates;
    }

    /**
     * Checks for potential duplicate passport numbers
     */
    public static function checkPassport(string $passportNumber, ?string $issuingCountry = null, ?int $excludeCustomerId = null): array
    {
        $pdo = Database::getConnection();
        $duplicates = [];

        $cleanPassport = strtoupper(trim($passportNumber));
        if (empty($cleanPassport)) {
            return [];
        }

        $sql = "SELECT cp.*, c.customer_code, c.full_name 
            FROM customer_passports cp 
            JOIN customers c ON cp.customer_id = c.id 
            WHERE UPPER(TRIM(cp.passport_number)) = ?";
        $params = [$cleanPassport];

        if ($excludeCustomerId) {
            $sql .= " AND cp.customer_id != ?";
            $params[] = $excludeCustomerId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $matches = $stmt->fetchAll();

        foreach ($matches as $match) {
            $duplicates[] = [
                'type' => 'passport',
                'message' => "Passport number '{$cleanPassport}' is already registered under customer {$match['customer_code']} ({$match['full_name']}).",
                'match' => $match,
            ];
        }

        return $duplicates;
    }

    /**
     * Unified duplicate checker combining email, mobile, and passport checks
     */
    public static function checkDuplicate(string $mobile, string $email, string $passport, ?int $excludeId = null): array
    {
        $custDups = self::checkCustomer($email, $mobile, null, null, $excludeId);
        $passDups = self::checkPassport($passport, null, $excludeId);

        $all = array_merge($custDups, $passDups);
        if (!empty($all)) {
            return [
                'is_duplicate' => true,
                'message' => $all[0]['message'],
                'duplicates' => $all
            ];
        }

        return [
            'is_duplicate' => false,
            'message' => 'No duplicate customer or passport records found.'
        ];
    }

    /**
     * Checks for existing active visa applications for the same applicant / passport
     */
    public static function checkApplicationDuplicate(int $customerId, int $visaServiceId, ?string $passportNumber = null, ?int $excludeAppId = null): array
    {
        $pdo = Database::getConnection();
        $duplicates = [];

        // Check for active applications for this customer and visa service
        $sql = "SELECT a.id, a.application_number, a.status, a.current_stage, a.created_at, 
                       vs.name as service_name, c.full_name as customer_name
                FROM applications a
                JOIN customers c ON a.customer_id = c.id
                JOIN visa_services vs ON a.visa_service_id = vs.id
                WHERE a.customer_id = ? 
                  AND a.visa_service_id = ? 
                  AND a.status NOT IN ('Approved', 'Completed', 'Rejected', 'Cancelled')
                  AND a.is_archived = 0";
        $params = [$customerId, $visaServiceId];

        if ($excludeAppId) {
            $sql .= " AND a.id != ?";
            $params[] = $excludeAppId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $activeApp = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($activeApp) {
            $duplicates[] = [
                'type' => 'active_application',
                'message' => "An active application ({$activeApp['application_number']} - {$activeApp['current_stage']}) already exists for this applicant.",
                'application' => $activeApp
            ];
        }

        if (!empty($duplicates)) {
            return [
                'is_duplicate' => true,
                'message' => $duplicates[0]['message'],
                'duplicates' => $duplicates
            ];
        }

        return [
            'is_duplicate' => false,
            'message' => 'No duplicate active application found.'
        ];
    }
}
