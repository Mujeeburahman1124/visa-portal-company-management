<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use PDO;

class VisaRuleEngineService
{
    /**
     * Resolves dynamic pricing, supplier routing, processing SLA, and eligibility
     * based on rule key: Destination + Visa Service + Applicant Nationality + Residence Country
     */
    public static function resolve(int $countryId, int $serviceId, string $nationality, ?string $residence = null): array
    {
        $pdo = Database::getConnection();

        // 1. Fetch baseline service details
        $stmt = $pdo->prepare("SELECT vs.*, c.name as country_name 
            FROM visa_services vs 
            JOIN countries c ON vs.country_id = c.id 
            WHERE vs.id = ?");
        $stmt->execute([$serviceId]);
        $baseService = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$baseService) {
            return [
                'is_eligible' => false,
                'reason' => 'Visa service not found.',
                'selling_price' => 0.00,
                'supplier_cost' => 0.00,
                'processing_days' => 0,
                'preferred_supplier_id' => null,
                'special_conditions' => null,
                'rule_applied' => null,
            ];
        }

        $residence = $residence ?: 'United Arab Emirates';

        // 2. Query dynamic override matching most specific rule key
        // Precedence:
        // Match 1: service_id + nationality + residence
        // Match 2: service_id + nationality + ANY residence
        // Match 3: country_id + nationality + residence
        // Match 4: country_id + nationality + ANY residence
        $ruleStmt = $pdo->prepare("SELECT * FROM visa_eligibility_rules 
            WHERE destination_country_id = ?
            AND (visa_service_id = ? OR visa_service_id IS NULL)
            AND (applicant_nationality = ? OR applicant_nationality = 'ANY')
            AND (residence_country = ? OR residence_country = 'ANY' OR residence_country IS NULL)
            ORDER BY 
                (CASE WHEN visa_service_id IS NOT NULL THEN 2 ELSE 0 END) +
                (CASE WHEN applicant_nationality != 'ANY' THEN 2 ELSE 0 END) +
                (CASE WHEN residence_country IS NOT NULL AND residence_country != 'ANY' THEN 1 ELSE 0 END) DESC
            LIMIT 1");

        $ruleStmt->execute([$countryId, $serviceId, $nationality, $residence]);
        $matchedRule = $ruleStmt->fetch(PDO::FETCH_ASSOC);

        $sellingPrice = (float)($matchedRule['override_selling_price'] ?? $baseService['selling_price']);
        $supplierCost = (float)($matchedRule['override_supplier_cost'] ?? $baseService['supplier_cost']);
        $processingDays = (int)($matchedRule['override_processing_days'] ?? $baseService['estimated_days']);
        $preferredSupplierId = $matchedRule['preferred_supplier_id'] ?? null;
        $isEligible = isset($matchedRule['is_eligible']) ? (bool)$matchedRule['is_eligible'] : true;
        $specialConditions = $matchedRule['special_conditions'] ?? null;

        return [
            'is_eligible' => $isEligible,
            'reason' => $isEligible ? 'Eligible' : ($specialConditions ?: 'Restricted nationality/residence for this visa category.'),
            'selling_price' => $sellingPrice,
            'supplier_cost' => $supplierCost,
            'processing_days' => $processingDays,
            'preferred_supplier_id' => $preferredSupplierId,
            'special_conditions' => $specialConditions,
            'rule_applied' => $matchedRule ? $matchedRule['id'] : null,
            'base_service' => $baseService
        ];
    }
}
