-- ============================================================================
-- VISA TRACK & MS TRAVEL HUB — Comprehensive 65-Table Complete MySQL Schema
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- Includes all 57 Base Tables + 8 System Compatibility Views
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';

-- Table: `companies`
CREATE TABLE IF NOT EXISTS `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `code` varchar(50) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `branches`
CREATE TABLE IF NOT EXISTS `branches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT 1,
  `name` varchar(150) NOT NULL,
  `code` varchar(50) NOT NULL,
  `country` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `branches_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `roles`
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `permissions`
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;;

-- Table: `role_permissions`
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `users`
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_email` (`email`),
  KEY `idx_users_role` (`role_id`),
  KEY `idx_users_branch` (`branch_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `user_permissions`
CREATE TABLE IF NOT EXISTS `user_permissions` (
  `user_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `password_resets`
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_pwd_resets_token` (`token`),
  KEY `idx_pwd_resets_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `customers`
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `nationality` varchar(100) NOT NULL,
  `place_of_birth` varchar(150) DEFAULT NULL,
  `marital_status` varchar(50) DEFAULT NULL,
  `occupation` varchar(150) DEFAULT NULL,
  `mobile` varchar(50) NOT NULL,
  `whatsapp` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `current_country` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `religion` varchar(100) DEFAULT NULL,
  `national_id_number` varchar(100) DEFAULT NULL,
  `national_id_expiry` date DEFAULT NULL,
  `national_id_front` varchar(255) DEFAULT NULL,
  `national_id_back` varchar(255) DEFAULT NULL,
  `passport_file` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_code` (`customer_code`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_customers_code` (`customer_code`),
  KEY `idx_customers_email` (`email`),
  KEY `idx_customers_mobile` (`mobile`),
  KEY `idx_customers_nationality` (`nationality`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `customer_passports`
CREATE TABLE IF NOT EXISTS `customer_passports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `passport_number` varchar(100) NOT NULL,
  `issuing_country` varchar(100) NOT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `place_of_issue` varchar(150) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_passports_number` (`passport_number`),
  KEY `idx_passports_expiry` (`expiry_date`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `customer_passports_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `customer_national_ids`
CREATE TABLE IF NOT EXISTS `customer_national_ids` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `id_number` varchar(100) NOT NULL,
  `id_type` varchar(100) DEFAULT 'National ID',
  `issuing_country` varchar(100) NOT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `front_file` varchar(255) DEFAULT NULL,
  `back_file` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_primary` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `customer_national_ids_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `customer_residences`
CREATE TABLE IF NOT EXISTS `customer_residences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `residence_country` varchar(100) NOT NULL,
  `permit_number` varchar(100) DEFAULT NULL,
  `permit_type` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `employer` varchar(150) DEFAULT NULL,
  `job_title` varchar(150) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_res_cust` (`customer_id`),
  CONSTRAINT `customer_residences_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `customer_family`
CREATE TABLE IF NOT EXISTS `customer_family` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `father_name` varchar(150) DEFAULT NULL,
  `father_dob` date DEFAULT NULL,
  `father_country_of_birth` varchar(100) DEFAULT NULL,
  `father_nationality` varchar(100) DEFAULT NULL,
  `father_religion` varchar(100) DEFAULT NULL,
  `mother_name` varchar(150) DEFAULT NULL,
  `mother_dob` date DEFAULT NULL,
  `mother_country_of_birth` varchar(100) DEFAULT NULL,
  `mother_nationality` varchar(100) DEFAULT NULL,
  `mother_religion` varchar(100) DEFAULT NULL,
  `mother_mobile` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_id` (`customer_id`),
  CONSTRAINT `customer_family_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `customer_wallets`
CREATE TABLE IF NOT EXISTS `customer_wallets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `current_balance` decimal(12,2) DEFAULT 0.00,
  `total_credited` decimal(12,2) DEFAULT 0.00,
  `total_debited` decimal(12,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_id` (`customer_id`),
  CONSTRAINT `customer_wallets_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `wallet_transactions`
CREATE TABLE IF NOT EXISTS `wallet_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` varchar(100) NOT NULL,
  `wallet_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `transaction_type` varchar(20) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `balance_after` decimal(12,2) NOT NULL,
  `description` text NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `application_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_id` (`transaction_id`),
  KEY `idx_wtx_cust` (`customer_id`),
  KEY `idx_wtx_wallet` (`wallet_id`),
  CONSTRAINT `wallet_transactions_ibfk_1` FOREIGN KEY (`wallet_id`) REFERENCES `customer_wallets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wallet_transactions_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `countries`
CREATE TABLE IF NOT EXISTS `countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `iso_code` varchar(10) NOT NULL,
  `flag_emoji` varchar(10) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `region` varchar(100) DEFAULT NULL,
  `embassy_info` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `iso3_code` char(3) DEFAULT NULL,
  `phone_code` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `iso_code` (`iso_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `visa_categories`
CREATE TABLE IF NOT EXISTS `visa_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `visa_services`
CREATE TABLE IF NOT EXISTS `visa_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `duration` varchar(100) NOT NULL,
  `max_stay` varchar(100) DEFAULT NULL,
  `validity` varchar(100) DEFAULT NULL,
  `entry_type` varchar(50) DEFAULT 'Single Entry',
  `processing_type` varchar(50) DEFAULT 'Normal',
  `estimated_days` int(11) DEFAULT 7,
  `passport_validity_rule_months` int(11) DEFAULT 6,
  `min_age` int(11) DEFAULT 0,
  `max_age` int(11) DEFAULT 100,
  `supplier_cost` decimal(10,2) DEFAULT 0.00,
  `service_fee` decimal(10,2) DEFAULT 0.00,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `selling_price` decimal(10,2) NOT NULL,
  `cancellation_policy` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_services_country` (`country_id`),
  KEY `idx_services_category` (`category_id`),
  CONSTRAINT `visa_services_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `visa_services_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `visa_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `visa_service_prices`
CREATE TABLE IF NOT EXISTS `visa_service_prices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visa_service_id` int(11) NOT NULL,
  `tier_name` varchar(100) DEFAULT 'Standard',
  `adult_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `child_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `infant_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `supplier_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'USD',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vsp_srv` (`visa_service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `visa_eligibility_rules`
CREATE TABLE IF NOT EXISTS `visa_eligibility_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `destination_country_id` int(11) NOT NULL,
  `visa_service_id` int(11) DEFAULT NULL,
  `service_id` int(11) NOT NULL,
  `applicant_nationality` varchar(100) NOT NULL,
  `residence_country` varchar(100) DEFAULT NULL,
  `price_override` decimal(12,2) DEFAULT NULL,
  `supplier_cost_override` decimal(12,2) DEFAULT NULL,
  `processing_days_override` int(11) DEFAULT NULL,
  `is_eligible` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `override_selling_price` decimal(10,2) DEFAULT NULL,
  `override_supplier_cost` decimal(10,2) DEFAULT NULL,
  `override_processing_days` int(11) DEFAULT NULL,
  `preferred_supplier_id` int(11) DEFAULT NULL,
  `special_conditions` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_elig_service` (`service_id`),
  CONSTRAINT `visa_eligibility_rules_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `visa_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `document_types`
CREATE TABLE IF NOT EXISTS `document_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT 'Personal',
  `requires_expiry` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `visa_requirements`
CREATE TABLE IF NOT EXISTS `visa_requirements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `document_type_id` int(11) NOT NULL,
  `is_mandatory` tinyint(1) DEFAULT 1,
  `condition_notes` text DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_critical` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_requirements_service` (`service_id`),
  KEY `document_type_id` (`document_type_id`),
  CONSTRAINT `visa_requirements_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `visa_services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `visa_requirements_ibfk_2` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `suppliers`
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_code` varchar(50) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `mobile` varchar(50) DEFAULT NULL,
  `whatsapp` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `payment_terms` text DEFAULT NULL,
  `bank_details` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `password_hash` varchar(255) DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `portal_enabled` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_code` (`supplier_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `supplier_services`
CREATE TABLE IF NOT EXISTS `supplier_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `visa_service_id` int(11) NOT NULL,
  `supplier_cost` decimal(10,2) NOT NULL,
  `processing_days` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ss_sup` (`supplier_id`),
  KEY `idx_ss_srv` (`visa_service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `supplier_prices`
CREATE TABLE IF NOT EXISTS `supplier_prices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `visa_service_id` int(11) NOT NULL,
  `cost_price` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sp_sup` (`supplier_id`),
  KEY `idx_sp_srv` (`visa_service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `supplier_payments`
CREATE TABLE IF NOT EXISTS `supplier_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_reference` varchar(100) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `payable_amount` decimal(12,2) NOT NULL,
  `paid_amount` decimal(12,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) DEFAULT 'Bank Transfer',
  `transaction_reference` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_reference` (`payment_reference`),
  KEY `supplier_id` (`supplier_id`),
  KEY `application_id` (`application_id`),
  CONSTRAINT `supplier_payments_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `supplier_payments_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `agents`
CREATE TABLE IF NOT EXISTS `agents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `mobile` varchar(50) DEFAULT NULL,
  `credit_limit` decimal(12,2) DEFAULT 0.00,
  `current_balance` decimal(12,2) DEFAULT 0.00,
  `commission_rate` decimal(5,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `whatsapp` varchar(50) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT 'Net 30',
  `bank_details` text DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `address` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `agent_code` (`agent_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `agent_applications`
CREATE TABLE IF NOT EXISTS `agent_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `commission_amount` decimal(12,2) DEFAULT 0.00,
  `is_commission_paid` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `agent_price` decimal(12,2) DEFAULT 0.00,
  `agent_commission` decimal(12,2) DEFAULT 0.00,
  `agent_reference` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agent_id` (`agent_id`),
  KEY `application_id` (`application_id`),
  CONSTRAINT `agent_applications_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `agent_applications_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `agent_payments`
CREATE TABLE IF NOT EXISTS `agent_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_reference` varchar(100) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `application_id` int(11) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_type` varchar(50) DEFAULT 'Payment',
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) DEFAULT 'Bank Transfer',
  `transaction_reference` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_reference` (`payment_reference`),
  KEY `agent_id` (`agent_id`),
  CONSTRAINT `agent_payments_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `application_statuses`
CREATE TABLE IF NOT EXISTS `application_statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `category` varchar(50) DEFAULT 'Processing',
  `badge_color` varchar(50) DEFAULT 'primary',
  `is_customer_visible` tinyint(1) DEFAULT 1,
  `is_system` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `applications`
CREATE TABLE IF NOT EXISTS `applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `visa_service_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `assigned_staff_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `current_stage` varchar(100) NOT NULL DEFAULT 'Application Registered',
  `status` varchar(50) NOT NULL DEFAULT 'Draft',
  `priority` varchar(20) NOT NULL DEFAULT 'Normal',
  `calculated_health` int(11) DEFAULT 100,
  `health_reason` text DEFAULT NULL,
  `nationality` varchar(100) NOT NULL,
  `residence_country` varchar(100) NOT NULL,
  `passport_number` varchar(100) NOT NULL,
  `travel_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `application_date` date NOT NULL,
  `expected_completion_date` date DEFAULT NULL,
  `actual_completion_date` date DEFAULT NULL,
  `selling_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `balance_amount` decimal(10,2) DEFAULT 0.00,
  `supplier_cost` decimal(10,2) DEFAULT 0.00,
  `other_expenses` decimal(10,2) DEFAULT 0.00,
  `gross_profit` decimal(10,2) DEFAULT 0.00,
  `supplier_reference` varchar(100) DEFAULT NULL,
  `embassy_reference` varchar(100) DEFAULT NULL,
  `visa_number` varchar(100) DEFAULT NULL,
  `visa_issue_date` date DEFAULT NULL,
  `visa_expiry_date` date DEFAULT NULL,
  `visa_file` varchar(255) DEFAULT NULL,
  `rejection_reason_customer` text DEFAULT NULL,
  `rejection_reason_internal` text DEFAULT NULL,
  `return_reason` text DEFAULT NULL,
  `return_deadline` date DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `customer_notes` text DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `next_action` text DEFAULT NULL,
  `next_action_due_date` date DEFAULT NULL,
  `agent_price` decimal(12,2) DEFAULT 0.00,
  `approved_visa_file` varchar(255) DEFAULT NULL,
  `approval_date` date DEFAULT NULL,
  `rejection_date` date DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `destination_country` varchar(150) DEFAULT NULL,
  `visa_category` varchar(150) DEFAULT NULL,
  `visa_type` varchar(150) DEFAULT NULL,
  `visa_duration` varchar(100) DEFAULT NULL,
  `entry_type` varchar(100) DEFAULT NULL,
  `processing_type` varchar(100) DEFAULT NULL,
  `payment_type` varchar(50) DEFAULT 'Pay Later',
  `payment_status` varchar(50) DEFAULT 'Unpaid',
  `submission_date` date DEFAULT NULL,
  `embassy_fee` decimal(10,2) DEFAULT 0.00,
  `service_fee` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `application_number` (`application_number`),
  KEY `idx_applications_num` (`application_number`),
  KEY `idx_applications_customer` (`customer_id`),
  KEY `idx_applications_service` (`visa_service_id`),
  KEY `idx_applications_staff` (`assigned_staff_id`),
  KEY `idx_applications_stage` (`current_stage`),
  KEY `idx_applications_status` (`status`),
  KEY `idx_applications_priority` (`priority`),
  KEY `idx_applications_expected` (`expected_completion_date`),
  KEY `branch_id` (`branch_id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`visa_service_id`) REFERENCES `visa_services` (`id`),
  CONSTRAINT `applications_ibfk_3` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `applications_ibfk_4` FOREIGN KEY (`assigned_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `applications_ibfk_5` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `applications_ibfk_6` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `application_assignments`
CREATE TABLE IF NOT EXISTS `application_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_at` datetime DEFAULT current_timestamp(),
  `unassigned_at` datetime DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_assign_application` (`application_id`),
  KEY `idx_assign_staff` (`assigned_to`),
  KEY `assigned_by` (`assigned_by`),
  CONSTRAINT `application_assignments_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `application_assignments_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  CONSTRAINT `application_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `application_status_history`
CREATE TABLE IF NOT EXISTS `application_status_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `from_status` varchar(50) DEFAULT NULL,
  `to_status` varchar(50) NOT NULL,
  `from_stage` varchar(100) DEFAULT NULL,
  `to_stage` varchar(100) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_hist_application` (`application_id`),
  KEY `changed_by` (`changed_by`),
  CONSTRAINT `application_status_history_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `application_status_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `application_notes`
CREATE TABLE IF NOT EXISTS `application_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `note_type` varchar(30) DEFAULT 'Internal',
  `note` text NOT NULL,
  `is_pinned` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `application_notes_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `application_notes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `application_returns`
CREATE TABLE IF NOT EXISTS `application_returns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `return_date` date NOT NULL,
  `return_reason` text NOT NULL,
  `required_modifications` text DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `returned_by` int(11) DEFAULT NULL,
  `return_file` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Awaiting Customer',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  CONSTRAINT `application_returns_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `visa_approvals`
CREATE TABLE IF NOT EXISTS `visa_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `visa_number` varchar(100) NOT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `entry_before_date` date DEFAULT NULL,
  `max_stay` varchar(100) DEFAULT NULL,
  `validity` varchar(100) DEFAULT NULL,
  `visa_file` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `maximum_stay` varchar(100) DEFAULT '30 Days',
  `approved_visa_file` varchar(255) DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `application_id` (`application_id`),
  CONSTRAINT `visa_approvals_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `visa_rejections`
CREATE TABLE IF NOT EXISTS `visa_rejections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `rejection_date` date NOT NULL,
  `customer_reason` text NOT NULL,
  `internal_reason` text DEFAULT NULL,
  `is_reapply_eligible` tinyint(1) DEFAULT 1,
  `rejection_file` varchar(255) DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `application_id` (`application_id`),
  CONSTRAINT `visa_rejections_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `documents`
CREATE TABLE IF NOT EXISTS `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `applicant_id` int(11) DEFAULT NULL,
  `document_type_id` int(11) NOT NULL,
  `document_title` varchar(200) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT 0,
  `mime_type` varchar(100) DEFAULT NULL,
  `version` int(11) DEFAULT 1,
  `expiry_date` date DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Missing',
  `uploaded_by_type` varchar(50) DEFAULT 'Staff',
  `uploaded_by_id` int(11) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `replacement_requested` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_docs_application` (`application_id`),
  KEY `idx_docs_customer` (`customer_id`),
  KEY `idx_docs_type` (`document_type_id`),
  KEY `idx_docs_status` (`status`),
  KEY `verified_by` (`verified_by`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documents_ibfk_3` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`),
  CONSTRAINT `documents_ibfk_4` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `document_versions`
CREATE TABLE IF NOT EXISTS `document_versions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT 0,
  `version_number` int(11) NOT NULL,
  `uploaded_by_type` varchar(50) DEFAULT 'Staff',
  `uploaded_by_id` int(11) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_doc_ver_doc` (`document_id`),
  CONSTRAINT `document_versions_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `document_requests`
CREATE TABLE IF NOT EXISTS `document_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `document_type_id` int(11) NOT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'PENDING',
  `notes` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_doc_req_cust` (`customer_id`),
  KEY `idx_doc_req_app` (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `tasks`
CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) DEFAULT NULL,
  `task_title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` int(11) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `priority` varchar(20) DEFAULT 'Normal',
  `due_date` date NOT NULL,
  `status` varchar(30) DEFAULT 'Pending',
  `completed_at` datetime DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `customer_id` int(11) DEFAULT NULL,
  `task_type` varchar(50) DEFAULT 'General',
  `start_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tasks_app` (`application_id`),
  KEY `idx_tasks_assigned` (`assigned_to`),
  KEY `idx_tasks_due` (`due_date`),
  KEY `idx_tasks_status` (`status`),
  KEY `created_by` (`created_by`),
  KEY `completed_by` (`completed_by`),
  CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_ibfk_4` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `task_comments`
CREATE TABLE IF NOT EXISTS `task_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `task_comments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `appointments`
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `appointment_type` varchar(100) NOT NULL,
  `center_name` varchar(150) NOT NULL,
  `location_address` text DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` varchar(20) NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'Scheduled',
  `notes` text DEFAULT NULL,
  `document_file` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `customer_id` int(11) DEFAULT NULL,
  `assigned_staff_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_appts_app` (`application_id`),
  KEY `idx_appts_date` (`appointment_date`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `invoices`
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(100) NOT NULL,
  `application_id` int(11) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `discount` decimal(12,2) DEFAULT 0.00,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `paid_amount` decimal(12,2) DEFAULT 0.00,
  `balance_amount` decimal(12,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'Unpaid',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `idx_inv_app` (`application_id`),
  KEY `idx_inv_cust` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `payments`
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_number` varchar(100) NOT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `application_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) DEFAULT 'Cash',
  `transaction_reference` varchar(150) DEFAULT NULL,
  `wallet_transaction_id` int(11) DEFAULT NULL,
  `payment_link_id` int(11) DEFAULT NULL,
  `payment_type` varchar(50) DEFAULT 'Customer Payment',
  `status` varchar(30) DEFAULT 'Completed',
  `received_by` int(11) DEFAULT NULL,
  `receipt_file` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_number` (`payment_number`),
  KEY `idx_pay_app` (`application_id`),
  KEY `idx_pay_cust` (`customer_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `payment_transactions`
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `transaction_type` varchar(50) DEFAULT 'DEBIT',
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `payment_gateway` varchar(50) DEFAULT 'MANUAL',
  `gateway_reference` varchar(150) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'COMPLETED',
  `gateway_response` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pt_pay` (`payment_id`),
  KEY `idx_pt_app` (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `payment_links`
CREATE TABLE IF NOT EXISTS `payment_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `link_token` varchar(64) NOT NULL,
  `application_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `title` varchar(255) DEFAULT 'Visa Processing Fee',
  `description` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active',
  `expires_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_reference` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `link_token` (`link_token`),
  KEY `idx_plink_token` (`link_token`),
  KEY `idx_plink_app` (`application_id`),
  KEY `idx_plink_customer` (`customer_id`),
  KEY `idx_plink_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `refunds`
CREATE TABLE IF NOT EXISTS `refunds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `refund_number` varchar(100) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `application_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reason` text NOT NULL,
  `payment_method` varchar(50) DEFAULT 'Bank Transfer',
  `transaction_reference` varchar(150) DEFAULT NULL,
  `processed_by` int(11) NOT NULL,
  `status` varchar(30) DEFAULT 'Processed',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `refund_number` (`refund_number`),
  KEY `application_id` (`application_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `refunds_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `refunds_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `communications`
CREATE TABLE IF NOT EXISTS `communications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `channel` varchar(50) NOT NULL,
  `direction` varchar(20) DEFAULT 'Outbound',
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `recorded_at` datetime DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_comm_cust` (`customer_id`),
  KEY `idx_comm_app` (`application_id`),
  CONSTRAINT `communications_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `communications_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `activity_logs`
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `actor_type` varchar(20) DEFAULT 'Staff',
  `action` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `details_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details_json`)),
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_logs_user` (`user_id`),
  KEY `idx_logs_module` (`module`),
  KEY `idx_logs_record` (`record_id`),
  KEY `idx_logs_created` (`created_at`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `activity_logs_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `notifications`
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `recipient_type` varchar(20) DEFAULT 'Staff',
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `notification_type` varchar(50) DEFAULT 'System',
  `severity` varchar(20) DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`),
  KEY `idx_notif_read` (`is_read`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `notification_logs`
CREATE TABLE IF NOT EXISTS `notification_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(100) NOT NULL,
  `recipient_type` varchar(50) DEFAULT 'Applicant',
  `recipient_id` int(11) DEFAULT NULL,
  `recipient_name` varchar(150) DEFAULT NULL,
  `recipient_email` varchar(150) DEFAULT NULL,
  `recipient_phone` varchar(50) DEFAULT NULL,
  `channel` varchar(30) NOT NULL,
  `template_name` varchar(100) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `content_preview` text DEFAULT NULL,
  `idempotency_key` varchar(150) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'Pending',
  `provider_message_id` varchar(255) DEFAULT NULL,
  `request_payload` longtext DEFAULT NULL,
  `response_payload` longtext DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `retry_count` int(11) DEFAULT 0,
  `max_retries` int(11) DEFAULT 3,
  `next_retry_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notif_logs_event` (`event_type`),
  KEY `idx_notif_logs_status` (`status`),
  KEY `idx_notif_logs_channel` (`channel`),
  KEY `idx_notif_logs_recipient` (`recipient_type`,`recipient_id`),
  KEY `idx_notif_logs_idemp` (`idempotency_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `notification_queue`
CREATE TABLE IF NOT EXISTS `notification_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(100) NOT NULL,
  `recipient_type` varchar(50) DEFAULT 'Applicant',
  `recipient_id` int(11) DEFAULT NULL,
  `recipient_name` varchar(150) DEFAULT NULL,
  `recipient_email` varchar(150) DEFAULT NULL,
  `recipient_phone` varchar(50) DEFAULT NULL,
  `channel` varchar(30) NOT NULL,
  `template_name` varchar(100) DEFAULT NULL,
  `idempotency_key` varchar(150) DEFAULT NULL,
  `payload_json` longtext NOT NULL,
  `status` varchar(30) DEFAULT 'Pending',
  `error_message` text DEFAULT NULL,
  `retry_count` int(11) DEFAULT 0,
  `max_retries` int(11) DEFAULT 3,
  `next_retry_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idempotency_key` (`idempotency_key`),
  KEY `idx_notif_queue_status_retry` (`status`,`next_retry_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `notification_settings`
CREATE TABLE IF NOT EXISTS `notification_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(100) NOT NULL,
  `event_category` varchar(50) DEFAULT 'General',
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `email_enabled` tinyint(1) DEFAULT 1,
  `whatsapp_enabled` tinyint(1) DEFAULT 1,
  `in_app_enabled` tinyint(1) DEFAULT 1,
  `applicant_enabled` tinyint(1) DEFAULT 1,
  `staff_enabled` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_type` (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `notification_templates`
CREATE TABLE IF NOT EXISTS `notification_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(100) NOT NULL,
  `channel` varchar(30) NOT NULL,
  `template_name` varchar(100) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `content` longtext NOT NULL,
  `provider_template_id` varchar(150) DEFAULT NULL,
  `language_code` varchar(10) DEFAULT 'en_US',
  `variables` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_channel` (`event_type`,`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `email_templates`
CREATE TABLE IF NOT EXISTS `email_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_key` varchar(100) NOT NULL,
  `title` varchar(150) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body_html` text NOT NULL,
  `placeholders` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_key` (`template_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `system_settings`
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'General',
  `description` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8 COMPATIBILITY VIEWS
-- ============================================================================

-- View: `applicants`
CREATE OR REPLACE VIEW `applicants` AS SELECT 
        `customers`.`id`, `customers`.`customer_code`, `customers`.`first_name`, `customers`.`middle_name`, 
        `customers`.`last_name`, `customers`.`full_name`, `customers`.`gender`, `customers`.`dob`, 
        `customers`.`nationality`, `customers`.`place_of_birth`, `customers`.`marital_status`, 
        `customers`.`occupation`, `customers`.`mobile`, `customers`.`whatsapp`, `customers`.`email`, 
        `customers`.`password_hash`, `customers`.`current_country`, `customers`.`address`, 
        `customers`.`created_by`, `customers`.`notes`, `customers`.`is_active`, `customers`.`created_at`, 
        `customers`.`updated_at` 
    FROM `customers`;

-- View: `application_appointments`
CREATE OR REPLACE VIEW `application_appointments` AS SELECT 
        `appointments`.`id`, `appointments`.`application_id`, `appointments`.`appointment_type`, 
        `appointments`.`center_name`, `appointments`.`location_address`, `appointments`.`appointment_date`, 
        `appointments`.`appointment_time`, `appointments`.`reference_number`, `appointments`.`status`, 
        `appointments`.`notes`, `appointments`.`created_by`, `appointments`.`created_at`, `appointments`.`updated_at` 
    FROM `appointments`;

-- View: `application_documents`
CREATE OR REPLACE VIEW `application_documents` AS SELECT 
        `documents`.`id`, `documents`.`application_id`, `documents`.`customer_id`, `documents`.`applicant_id`, 
        `documents`.`document_type_id`, `documents`.`document_title`, `documents`.`file_path`, 
        `documents`.`file_name`, `documents`.`file_size`, `documents`.`mime_type`, `documents`.`version`, 
        `documents`.`expiry_date`, `documents`.`status`, `documents`.`uploaded_by_type`, 
        `documents`.`uploaded_by_id`, `documents`.`verified_by`, `documents`.`verified_at`, 
        `documents`.`rejection_reason`, `documents`.`replacement_requested`, `documents`.`notes`, 
        `documents`.`created_at`, `documents`.`updated_at` 
    FROM `documents`;

-- View: `application_tasks`
CREATE OR REPLACE VIEW `application_tasks` AS SELECT 
        `tasks`.`id`, `tasks`.`application_id`, `tasks`.`task_title`, `tasks`.`description`, 
        `tasks`.`assigned_to`, `tasks`.`created_by`, `tasks`.`priority`, `tasks`.`due_date`, 
        `tasks`.`status`, `tasks`.`completed_at`, `tasks`.`completed_by`, `tasks`.`created_at`, 
        `tasks`.`updated_at` 
    FROM `tasks`;

-- View: `audit_logs`
CREATE OR REPLACE VIEW `audit_logs` AS SELECT 
        `activity_logs`.`id`, `activity_logs`.`user_id`, `activity_logs`.`customer_id`, 
        `activity_logs`.`actor_type`, `activity_logs`.`action`, `activity_logs`.`module`, 
        `activity_logs`.`record_id`, `activity_logs`.`description`, `activity_logs`.`details_json`, 
        `activity_logs`.`ip_address`, `activity_logs`.`user_agent`, `activity_logs`.`created_at` 
    FROM `activity_logs`;

-- View: `required_documents`
CREATE OR REPLACE VIEW `required_documents` AS SELECT 
        `visa_requirements`.`id`, `visa_requirements`.`service_id`, `visa_requirements`.`document_type_id`, 
        `visa_requirements`.`is_mandatory`, `visa_requirements`.`condition_notes`, 
        `visa_requirements`.`instructions`, `visa_requirements`.`created_at` 
    FROM `visa_requirements`;

-- View: `visa_applications`
CREATE OR REPLACE VIEW `visa_applications` AS SELECT 
        `applications`.`id`, `applications`.`application_number`, `applications`.`customer_id`, 
        `applications`.`visa_service_id`, `applications`.`branch_id`, `applications`.`assigned_staff_id`, 
        `applications`.`supplier_id`, `applications`.`agent_id`, `applications`.`current_stage`, 
        `applications`.`status`, `applications`.`priority`, `applications`.`calculated_health`, 
        `applications`.`health_reason`, `applications`.`nationality`, `applications`.`residence_country`, 
        `applications`.`passport_number`, `applications`.`travel_date`, `applications`.`return_date`, 
        `applications`.`application_date`, `applications`.`expected_completion_date`, 
        `applications`.`actual_completion_date`, `applications`.`selling_price`, `applications`.`discount`, 
        `applications`.`tax_amount`, `applications`.`total_amount`, `applications`.`paid_amount`, 
        `applications`.`balance_amount`, `applications`.`supplier_cost`, `applications`.`other_expenses`, 
        `applications`.`gross_profit`, `applications`.`supplier_reference`, `applications`.`embassy_reference`, 
        `applications`.`visa_number`, `applications`.`visa_issue_date`, `applications`.`visa_expiry_date`, 
        `applications`.`visa_file`, `applications`.`rejection_reason_customer`, 
        `applications`.`rejection_reason_internal`, `applications`.`return_reason`, 
        `applications`.`return_deadline`, `applications`.`internal_notes`, `applications`.`customer_notes`, 
        `applications`.`is_archived`, `applications`.`created_by`, `applications`.`created_at`, 
        `applications`.`updated_at` 
    FROM `applications`;

-- View: `visa_types`
CREATE OR REPLACE VIEW `visa_types` AS SELECT 
        `visa_categories`.`id`, `visa_categories`.`name`, `visa_categories`.`slug`, 
        `visa_categories`.`description`, `visa_categories`.`icon`, `visa_categories`.`is_active`, 
        `visa_categories`.`created_at` 
    FROM `visa_categories`;

-- Table: `staff_leave_requests`
CREATE TABLE IF NOT EXISTS `staff_leave_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `leave_type` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days_count` decimal(4,1) NOT NULL DEFAULT 1.0,
  `total_days` int(11) NOT NULL DEFAULT 1,
  `reason` text NOT NULL,
  `status` varchar(30) DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `approver_id` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approver_notes` text DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_slr_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `staff_requests`
CREATE TABLE IF NOT EXISTS `staff_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `request_type` varchar(100) NOT NULL,
  `application_id` int(11) DEFAULT NULL,
  `priority` varchar(30) DEFAULT 'Medium',
  `description` text NOT NULL,
  `status` varchar(30) DEFAULT 'Open',
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sr_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `supplier_wallets`
CREATE TABLE IF NOT EXISTS `supplier_wallets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `currency` varchar(10) DEFAULT 'AED',
  `current_balance` decimal(12,2) DEFAULT 0.00,
  `total_credited` decimal(12,2) DEFAULT 0.00,
  `total_debited` decimal(12,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_supp_wallet` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `supplier_wallet_transactions`
CREATE TABLE IF NOT EXISTS `supplier_wallet_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wallet_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `transaction_type` varchar(20) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `balance_after` decimal(12,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'AED',
  `description` text NOT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_swtx_wallet` (`wallet_id`),
  KEY `idx_swtx_supp` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `agent_wallets`
CREATE TABLE IF NOT EXISTS `agent_wallets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL,
  `currency` varchar(10) DEFAULT 'AED',
  `current_balance` decimal(12,2) DEFAULT 0.00,
  `total_credited` decimal(12,2) DEFAULT 0.00,
  `total_debited` decimal(12,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_agent_wallet` (`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `agent_wallet_transactions`
CREATE TABLE IF NOT EXISTS `agent_wallet_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wallet_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `transaction_type` varchar(20) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `balance_after` decimal(12,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'AED',
  `description` text NOT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_awtx_wallet` (`wallet_id`),
  KEY `idx_awtx_agent` (`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `inventory_categories`
CREATE TABLE IF NOT EXISTS `inventory_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `inventory_items`
CREATE TABLE IF NOT EXISTS `inventory_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `unit` varchar(30) DEFAULT 'Pcs',
  `opening_stock` int(11) NOT NULL DEFAULT 0,
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `minimum_stock` int(11) NOT NULL DEFAULT 10,
  `purchase_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'AED',
  `location` varchar(100) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'In Stock',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_code` (`item_code`),
  KEY `idx_inv_cat` (`category_id`),
  KEY `idx_inv_supp` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `inventory_transactions`
CREATE TABLE IF NOT EXISTS `inventory_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_code` varchar(50) NOT NULL,
  `item_id` int(11) NOT NULL,
  `transaction_type` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `prev_stock` int(11) NOT NULL,
  `new_stock` int(11) NOT NULL,
  `unit_price` decimal(12,2) DEFAULT 0.00,
  `total_price` decimal(12,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'AED',
  `supplier_id` int(11) DEFAULT NULL,
  `supplier_invoice_ref` varchar(100) DEFAULT NULL,
  `source_branch_id` int(11) DEFAULT NULL,
  `destination_branch_id` int(11) DEFAULT NULL,
  `reason_notes` text NOT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_code` (`transaction_code`),
  KEY `idx_it_item` (`item_id`),
  KEY `idx_it_supp` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `visa_package_price_history`
CREATE TABLE IF NOT EXISTS `visa_package_price_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visa_service_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `supplier_cost` decimal(12,2) DEFAULT 0.00,
  `service_fee` decimal(12,2) DEFAULT 0.00,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `selling_price` decimal(12,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'USD',
  `effective_from` datetime NOT NULL,
  `effective_to` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vph_serv` (`visa_service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: `visa_package_inventory_transactions`
CREATE TABLE IF NOT EXISTS `visa_package_inventory_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visa_service_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `prev_cost` decimal(12,2) DEFAULT NULL,
  `new_cost` decimal(12,2) DEFAULT NULL,
  `prev_price` decimal(12,2) DEFAULT NULL,
  `new_price` decimal(12,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `supplier_id` int(11) DEFAULT NULL,
  `application_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vpit_serv` (`visa_service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SEED DATA
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 1;
