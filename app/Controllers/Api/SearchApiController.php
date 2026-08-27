<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Config\Database;
use PDO;

class SearchApiController
{
    /**
     * Unified Global Search Engine across:
     * - Application Reference
     * - Customer ID / Name
     * - Passport Number
     * - National / Emirates ID
     * - Mobile & WhatsApp
     * - Email
     * - Visa Grant Number
     * - Supplier Reference
     * - Destination Country
     */
    public function search(): void
    {
        $query = trim($_GET['q'] ?? '');
        if (strlen($query) < 2) {
            json_response(['applications' => [], 'customers' => []]);
        }

        $pdo = Database::getConnection();
        $searchTerm = '%' . $query . '%';

        // 1. Search Applications with Destination, Visa #, and Supplier Reference
        $appStmt = $pdo->prepare("SELECT a.id, a.application_number, a.status, a.priority, a.visa_number, a.supplier_reference,
            c.full_name as customer_name, c.customer_code,
            vs.name as visa_type_name,
            ct.name as country_name, ct.flag_emoji
            FROM applications a
            LEFT JOIN customers c ON a.customer_id = c.id
            LEFT JOIN visa_services vs ON a.visa_service_id = vs.id
            LEFT JOIN countries ct ON vs.country_id = ct.id
            WHERE a.application_number LIKE ? 
               OR a.visa_number LIKE ?
               OR a.supplier_reference LIKE ?
               OR a.embassy_reference LIKE ?
               OR a.passport_number LIKE ?
               OR c.full_name LIKE ? 
               OR c.customer_code LIKE ?
               OR vs.name LIKE ?
               OR ct.name LIKE ?
            ORDER BY a.created_at DESC LIMIT 6");
        $appStmt->execute([
            $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm,
            $searchTerm, $searchTerm, $searchTerm, $searchTerm
        ]);
        $applications = $appStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 2. Search Customers with Passport, National ID, Mobile, WhatsApp, and Email
        $custStmt = $pdo->prepare("SELECT c.id, c.customer_code, c.full_name, c.nationality, c.mobile, c.email,
            cp.passport_number,
            nid.id_number as national_id
            FROM customers c
            LEFT JOIN customer_passports cp ON c.id = cp.customer_id AND cp.is_primary = 1
            LEFT JOIN customer_national_ids nid ON c.id = nid.customer_id
            WHERE c.full_name LIKE ? 
               OR c.customer_code LIKE ? 
               OR c.mobile LIKE ? 
               OR c.whatsapp LIKE ?
               OR c.email LIKE ? 
               OR cp.passport_number LIKE ?
               OR nid.id_number LIKE ?
            GROUP BY c.id
            ORDER BY c.created_at DESC LIMIT 6");
        $custStmt->execute([
            $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm,
            $searchTerm, $searchTerm
        ]);
        $customers = $custStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        json_response([
            'applications' => $applications,
            'customers' => $customers,
        ]);
    }
}
