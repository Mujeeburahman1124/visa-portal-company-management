<?php
declare(strict_types=1);

namespace App\Database;

use App\Config\App;
use App\Config\Database;
use PDO;

class DatabaseBootstrapper
{
    public static function init(): void
    {
        $pdo = Database::getConnection();
        
        // Check if schema is initialized
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
            $exists = $stmt->fetch();
            
            if (!$exists) {
                $schemaFile = __DIR__ . '/schema_sqlite.sql';
                if (file_exists($schemaFile)) {
                    $sql = file_get_contents($schemaFile);
                    $pdo->exec($sql);
                }
                
                // Seed initial data
                SeedData::seed($pdo);
            }

            // Ensure password_resets table exists for SQLite
            $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL,
                token TEXT NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL
            );");

            // Ensure document_requests table exists for SQLite
            $pdo->exec("CREATE TABLE IF NOT EXISTS document_requests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_id INTEGER NOT NULL,
                application_id INTEGER NOT NULL,
                document_type_id INTEGER NOT NULL,
                requested_by INTEGER,
                status TEXT DEFAULT 'PENDING',
                notes TEXT,
                due_date DATE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
                FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
            );");
        } else {
            // Ensure password_resets table exists for MySQL
            $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(150) NOT NULL,
                token VARCHAR(255) NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                INDEX idx_pwd_resets_token (token),
                INDEX idx_pwd_resets_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // Ensure document_requests table exists for MySQL
            $pdo->exec("CREATE TABLE IF NOT EXISTS document_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
                application_id INT NOT NULL,
                document_type_id INT NOT NULL,
                requested_by INT NULL,
                status VARCHAR(50) DEFAULT 'PENDING',
                notes TEXT NULL,
                due_date DATE NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_doc_req_cust (customer_id),
                INDEX idx_doc_req_app (application_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // Ensure payments table exists for MySQL
            $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                payment_number VARCHAR(100) NOT NULL UNIQUE,
                invoice_number VARCHAR(100) NOT NULL,
                application_id INT NOT NULL,
                customer_id INT NOT NULL,
                amount DECIMAL(12,2) NOT NULL,
                currency VARCHAR(10) DEFAULT 'USD',
                payment_date DATE NOT NULL,
                payment_method VARCHAR(50) DEFAULT 'Cash',
                transaction_reference VARCHAR(150),
                payment_type VARCHAR(50) DEFAULT 'Customer Payment',
                status VARCHAR(30) DEFAULT 'Completed',
                received_by INT,
                receipt_file VARCHAR(255),
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_pay_app (application_id),
                INDEX idx_pay_cust (customer_id),
                FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
                FOREIGN KEY (customer_id) REFERENCES customers(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // Ensure refunds table exists for MySQL
            $pdo->exec("CREATE TABLE IF NOT EXISTS refunds (
                id INT AUTO_INCREMENT PRIMARY KEY,
                refund_number VARCHAR(100) NOT NULL UNIQUE,
                payment_id INT,
                application_id INT NOT NULL,
                customer_id INT NOT NULL,
                amount DECIMAL(12,2) NOT NULL,
                reason TEXT NOT NULL,
                payment_method VARCHAR(50) DEFAULT 'Bank Transfer',
                transaction_reference VARCHAR(150),
                processed_by INT NOT NULL,
                status VARCHAR(30) DEFAULT 'Processed',
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
                FOREIGN KEY (customer_id) REFERENCES customers(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // Ensure supplier_payments table exists for MySQL
            $pdo->exec("CREATE TABLE IF NOT EXISTS supplier_payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                payment_reference VARCHAR(100) NOT NULL UNIQUE,
                supplier_id INT NOT NULL,
                application_id INT NOT NULL,
                payable_amount DECIMAL(12,2) NOT NULL,
                paid_amount DECIMAL(12,2) NOT NULL,
                payment_date DATE NOT NULL,
                payment_method VARCHAR(50) DEFAULT 'Bank Transfer',
                transaction_reference VARCHAR(150),
                notes TEXT,
                created_by INT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
                FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // Ensure tasks table exists for MySQL
            $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                application_id INT,
                customer_id INT,
                task_title VARCHAR(200) NOT NULL,
                description TEXT,
                task_type VARCHAR(50) DEFAULT 'General',
                priority VARCHAR(30) DEFAULT 'Normal',
                assigned_to INT,
                created_by INT,
                start_date DATE,
                due_date DATE,
                completed_at DATETIME,
                status VARCHAR(30) DEFAULT 'Pending',
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_task_app (application_id),
                INDEX idx_task_assigned (assigned_to),
                INDEX idx_task_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // Ensure communications table exists for MySQL
            $pdo->exec("CREATE TABLE IF NOT EXISTS communications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                application_id INT NULL,
                customer_id INT NOT NULL,
                channel VARCHAR(50) NOT NULL,
                direction VARCHAR(20) DEFAULT 'Outbound',
                subject VARCHAR(255) NULL,
                message TEXT NOT NULL,
                contact_person VARCHAR(150) NULL,
                staff_id INT NULL,
                recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_comm_cust (customer_id),
                INDEX idx_comm_app (application_id),
                FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
                FOREIGN KEY (customer_id) REFERENCES customers(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // Ensure customer_family table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS customer_family (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL UNIQUE,
                father_name VARCHAR(150),
                father_dob DATE,
                father_country_of_birth VARCHAR(100),
                father_nationality VARCHAR(100),
                father_religion VARCHAR(100),
                mother_name VARCHAR(150),
                mother_dob DATE,
                mother_country_of_birth VARCHAR(100),
                mother_nationality VARCHAR(100),
                mother_religion VARCHAR(100),
                mother_mobile VARCHAR(50),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // Ensure customer_residences table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS customer_residences (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
                residence_country VARCHAR(100) NOT NULL,
                permit_number VARCHAR(100),
                expiry_date DATE,
                employer VARCHAR(200),
                job_title VARCHAR(150),
                is_primary TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_cust_res (customer_id),
                FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // Ensure visa_approvals table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS visa_approvals (
                id INT AUTO_INCREMENT PRIMARY KEY,
                application_id INT NOT NULL UNIQUE,
                visa_number VARCHAR(100) NOT NULL,
                issue_date DATE NOT NULL,
                expiry_date DATE NOT NULL,
                entry_before_date DATE,
                maximum_stay VARCHAR(100) DEFAULT '30 Days',
                validity VARCHAR(100) DEFAULT '60 Days',
                approved_visa_file VARCHAR(255),
                approval_notes TEXT,
                approved_by INT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
                FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // Ensure visa_rejections table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS visa_rejections (
                id INT AUTO_INCREMENT PRIMARY KEY,
                application_id INT NOT NULL UNIQUE,
                rejection_date DATE NOT NULL,
                customer_reason TEXT NOT NULL,
                internal_reason TEXT,
                reapplication_eligibility VARCHAR(100) DEFAULT 'Eligible to Reapply',
                rejection_document VARCHAR(255),
                rejected_by INT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
                FOREIGN KEY (rejected_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // Ensure visa_eligibility_rules table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS visa_eligibility_rules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                destination_country_id INT NOT NULL,
                visa_service_id INT NULL,
                applicant_nationality VARCHAR(100) NOT NULL DEFAULT 'ANY',
                residence_country VARCHAR(100) DEFAULT 'ANY',
                is_eligible TINYINT(1) DEFAULT 1,
                override_selling_price DECIMAL(10,2) NULL,
                override_supplier_cost DECIMAL(10,2) NULL,
                override_processing_days INT NULL,
                preferred_supplier_id INT NULL,
                special_conditions TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_rule_dest (destination_country_id),
                INDEX idx_rule_svc (visa_service_id),
                INDEX idx_rule_nat (applicant_nationality)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // Ensure application_returns table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS application_returns (
                id INT AUTO_INCREMENT PRIMARY KEY,
                application_id INT NOT NULL,
                return_reason TEXT NOT NULL,
                required_changes TEXT,
                deadline DATE,
                staff_comment TEXT,
                returned_by INT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ret_app (application_id),
                FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
                FOREIGN KEY (returned_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // Ensure customer_wallets table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS customer_wallets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL UNIQUE,
                currency VARCHAR(10) DEFAULT 'USD',
                current_balance DECIMAL(12,2) DEFAULT 0.00,
                total_credited DECIMAL(12,2) DEFAULT 0.00,
                total_debited DECIMAL(12,2) DEFAULT 0.00,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // Ensure wallet_transactions table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS wallet_transactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                transaction_id VARCHAR(100) NOT NULL UNIQUE,
                wallet_id INT NOT NULL,
                customer_id INT NOT NULL,
                transaction_type VARCHAR(20) NOT NULL, -- 'Credit' or 'Debit'
                amount DECIMAL(12,2) NOT NULL,
                balance_after DECIMAL(12,2) NOT NULL,
                description TEXT NOT NULL,
                payment_id INT,
                application_id INT,
                created_by INT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_wtx_cust (customer_id),
                INDEX idx_wtx_wallet (wallet_id),
                FOREIGN KEY (wallet_id) REFERENCES customer_wallets(id) ON DELETE CASCADE,
                FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        }

        // ── PHASE 2: AGENT & SUPPLIER PORTAL TABLES ──────────────────────────
        if ($driver === 'mysql') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS agents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                agent_code VARCHAR(30) NOT NULL UNIQUE,
                company_name VARCHAR(200) NOT NULL,
                contact_person VARCHAR(150) NOT NULL,
                mobile VARCHAR(50) NOT NULL,
                whatsapp VARCHAR(50),
                email VARCHAR(150) NOT NULL UNIQUE,
                password_hash VARCHAR(255),
                country VARCHAR(100),
                city VARCHAR(100),
                address TEXT,
                credit_limit DECIMAL(12,2) DEFAULT 0.00,
                current_balance DECIMAL(12,2) DEFAULT 0.00,
                commission_rate DECIMAL(5,2) DEFAULT 0.00,
                payment_terms VARCHAR(100) DEFAULT 'Net 30',
                bank_details TEXT,
                notes TEXT,
                is_active TINYINT(1) DEFAULT 1,
                last_login_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_agents_code (agent_code),
                INDEX idx_agents_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS agent_applications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                agent_id INT NOT NULL,
                application_id INT NOT NULL,
                agent_price DECIMAL(12,2) DEFAULT 0.00,
                agent_commission DECIMAL(12,2) DEFAULT 0.00,
                agent_reference VARCHAR(100),
                status VARCHAR(50) DEFAULT 'Active',
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_agent_app (agent_id, application_id),
                FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE,
                FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS agent_payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                payment_reference VARCHAR(100) NOT NULL UNIQUE,
                agent_id INT NOT NULL,
                application_id INT,
                amount DECIMAL(12,2) NOT NULL,
                payment_type VARCHAR(50) DEFAULT 'Payment',
                payment_method VARCHAR(50) DEFAULT 'Bank Transfer',
                transaction_reference VARCHAR(150),
                payment_date DATE NOT NULL,
                notes TEXT,
                created_by INT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (agent_id) REFERENCES agents(id),
                FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            try { $pdo->exec("ALTER TABLE applications ADD COLUMN agent_id INT NULL"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE applications ADD COLUMN agent_price DECIMAL(12,2) DEFAULT 0.00"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE suppliers ADD COLUMN password_hash VARCHAR(255) NULL"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE suppliers ADD COLUMN last_login_at DATETIME NULL"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE suppliers ADD COLUMN portal_enabled TINYINT(1) DEFAULT 0"); } catch (\Throwable $e) {}

            // Safe ALTER TABLE migrations for agents table
            try { $pdo->exec("ALTER TABLE agents ADD COLUMN whatsapp VARCHAR(50) NULL"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE agents ADD COLUMN city VARCHAR(100) NULL"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE agents ADD COLUMN country VARCHAR(100) NULL"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE agents ADD COLUMN password_hash VARCHAR(255) NULL"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE agents ADD COLUMN credit_limit DECIMAL(12,2) DEFAULT 0.00"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE agents ADD COLUMN current_balance DECIMAL(12,2) DEFAULT 0.00"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE agents ADD COLUMN commission_rate DECIMAL(5,2) DEFAULT 0.00"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE agents ADD COLUMN payment_terms VARCHAR(100) DEFAULT 'Net 30'"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE agents ADD COLUMN bank_details TEXT NULL"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE agents ADD COLUMN last_login_at DATETIME NULL"); } catch (\Throwable $e) {}
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS agents (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                agent_code TEXT NOT NULL UNIQUE,
                company_name TEXT NOT NULL,
                contact_person TEXT NOT NULL,
                mobile TEXT NOT NULL,
                whatsapp TEXT,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT,
                country TEXT, city TEXT, address TEXT,
                credit_limit REAL DEFAULT 0.00,
                current_balance REAL DEFAULT 0.00,
                commission_rate REAL DEFAULT 0.00,
                payment_terms TEXT DEFAULT 'Net 30',
                bank_details TEXT, notes TEXT,
                is_active INTEGER DEFAULT 1,
                last_login_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );");
            $pdo->exec("CREATE TABLE IF NOT EXISTS agent_applications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                agent_id INTEGER NOT NULL, application_id INTEGER NOT NULL,
                agent_price REAL DEFAULT 0.00, agent_commission REAL DEFAULT 0.00,
                agent_reference TEXT, status TEXT DEFAULT 'Active', notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(agent_id, application_id),
                FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE,
                FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
            );");
            $pdo->exec("CREATE TABLE IF NOT EXISTS agent_payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                payment_reference TEXT NOT NULL UNIQUE,
                agent_id INTEGER NOT NULL, application_id INTEGER,
                amount REAL NOT NULL, payment_type TEXT DEFAULT 'Payment',
                payment_method TEXT DEFAULT 'Bank Transfer',
                transaction_reference TEXT, payment_date DATE NOT NULL,
                notes TEXT, created_by INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (agent_id) REFERENCES agents(id),
                FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE SET NULL
            );");
            try { $pdo->exec("ALTER TABLE applications ADD COLUMN agent_id INTEGER NULL"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE applications ADD COLUMN agent_price REAL DEFAULT 0.00"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE suppliers ADD COLUMN password_hash TEXT NULL"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE suppliers ADD COLUMN last_login_at DATETIME NULL"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE suppliers ADD COLUMN portal_enabled INTEGER DEFAULT 0"); } catch (\Throwable $e) {}
        }

        // Ensure communications table exists for SQLite
        if ($driver === 'sqlite') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS communications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                application_id INTEGER,
                customer_id INTEGER NOT NULL,
                channel TEXT NOT NULL,
                direction TEXT DEFAULT 'Outbound',
                subject TEXT,
                message TEXT NOT NULL,
                contact_person TEXT,
                staff_id INTEGER,
                recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
                FOREIGN KEY (customer_id) REFERENCES customers(id)
            );");
        }

        // Ensure system_settings table exists for SQLite
        if ($driver === 'sqlite') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key TEXT NOT NULL UNIQUE,
                setting_value TEXT,
                setting_group TEXT DEFAULT 'General',
                description TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );");
        }

        // Ensure countries table exists (both drivers)
        if ($driver === 'mysql') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS countries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                iso_code CHAR(2) NOT NULL UNIQUE,
                iso3_code CHAR(3),
                flag_emoji VARCHAR(10),
                phone_code VARCHAR(20),
                currency VARCHAR(10) DEFAULT 'USD',
                region VARCHAR(100),
                embassy_info TEXT,
                is_active TINYINT(1) DEFAULT 1,
                INDEX idx_countries_iso (iso_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS countries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                iso_code TEXT NOT NULL UNIQUE,
                iso3_code TEXT,
                flag_emoji TEXT,
                phone_code TEXT,
                currency TEXT DEFAULT 'USD',
                region TEXT,
                embassy_info TEXT,
                is_active INTEGER DEFAULT 1
            );");
        }

        // Safe column migrations for existing countries tables (add missing columns)
        if ($driver === 'mysql') {
            try { $pdo->exec("ALTER TABLE countries ADD COLUMN iso3_code CHAR(3) NULL"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE countries ADD COLUMN phone_code VARCHAR(20) NULL"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE countries ADD COLUMN currency VARCHAR(10) DEFAULT 'USD'"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE countries ADD COLUMN region VARCHAR(100) NULL"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE countries ADD COLUMN embassy_info TEXT NULL"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE countries ADD COLUMN is_active TINYINT(1) DEFAULT 1"); } catch (\Throwable $e) {}
        } else {
            // SQLite doesn't support multi-column ALTER, each separately
            try { $pdo->exec("ALTER TABLE countries ADD COLUMN iso3_code TEXT"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE countries ADD COLUMN phone_code TEXT"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE countries ADD COLUMN currency TEXT DEFAULT 'USD'"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE countries ADD COLUMN region TEXT"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE countries ADD COLUMN embassy_info TEXT"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE countries ADD COLUMN is_active INTEGER DEFAULT 1"); } catch (\Throwable $e) {}
        }

        // Seed countries — only insert the 3 guaranteed columns so old tables aren't broken
        $insIgnore = $driver === 'mysql' ? 'INSERT IGNORE INTO' : 'INSERT OR IGNORE INTO';
        $worldCountries = [
            ['Afghanistan','AF','AFG','🇦🇫','+93'],['Albania','AL','ALB','🇦🇱','+355'],
            ['Algeria','DZ','DZA','🇩🇿','+213'],['Andorra','AD','AND','🇦🇩','+376'],
            ['Angola','AO','AGO','🇦🇴','+244'],['Antigua and Barbuda','AG','ATG','🇦🇬','+1-268'],
            ['Argentina','AR','ARG','🇦🇷','+54'],['Armenia','AM','ARM','🇦🇲','+374'],
            ['Australia','AU','AUS','🇦🇺','+61'],['Austria','AT','AUT','🇦🇹','+43'],
            ['Azerbaijan','AZ','AZE','🇦🇿','+994'],['Bahamas','BS','BHS','🇧🇸','+1-242'],
            ['Bahrain','BH','BHR','🇧🇭','+973'],['Bangladesh','BD','BGD','🇧🇩','+880'],
            ['Barbados','BB','BRB','🇧🇧','+1-246'],['Belarus','BY','BLR','🇧🇾','+375'],
            ['Belgium','BE','BEL','🇧🇪','+32'],['Belize','BZ','BLZ','🇧🇿','+501'],
            ['Benin','BJ','BEN','🇧🇯','+229'],['Bhutan','BT','BTN','🇧🇹','+975'],
            ['Bolivia','BO','BOL','🇧🇴','+591'],['Bosnia and Herzegovina','BA','BIH','🇧🇦','+387'],
            ['Botswana','BW','BWA','🇧🇼','+267'],['Brazil','BR','BRA','🇧🇷','+55'],
            ['Brunei','BN','BRN','🇧🇳','+673'],['Bulgaria','BG','BGR','🇧🇬','+359'],
            ['Burkina Faso','BF','BFA','🇧🇫','+226'],['Burundi','BI','BDI','🇧🇮','+257'],
            ['Cabo Verde','CV','CPV','🇨🇻','+238'],['Cambodia','KH','KHM','🇰🇭','+855'],
            ['Cameroon','CM','CMR','🇨🇲','+237'],['Canada','CA','CAN','🇨🇦','+1'],
            ['Central African Republic','CF','CAF','🇨🇫','+236'],['Chad','TD','TCD','🇹🇩','+235'],
            ['Chile','CL','CHL','🇨🇱','+56'],['China','CN','CHN','🇨🇳','+86'],
            ['Colombia','CO','COL','🇨🇴','+57'],['Comoros','KM','COM','🇰🇲','+269'],
            ['Congo','CG','COG','🇨🇬','+242'],['Costa Rica','CR','CRI','🇨🇷','+506'],
            ['Croatia','HR','HRV','🇭🇷','+385'],['Cuba','CU','CUB','🇨🇺','+53'],
            ['Cyprus','CY','CYP','🇨🇾','+357'],['Czech Republic','CZ','CZE','🇨🇿','+420'],
            ['Denmark','DK','DNK','🇩🇰','+45'],['Djibouti','DJ','DJI','🇩🇯','+253'],
            ['Dominica','DM','DMA','🇩🇲','+1-767'],['Dominican Republic','DO','DOM','🇩🇴','+1-809'],
            ['DR Congo','CD','COD','🇨🇩','+243'],['Ecuador','EC','ECU','🇪🇨','+593'],
            ['Egypt','EG','EGY','🇪🇬','+20'],['El Salvador','SV','SLV','🇸🇻','+503'],
            ['Equatorial Guinea','GQ','GNQ','🇬🇶','+240'],['Eritrea','ER','ERI','🇪🇷','+291'],
            ['Estonia','EE','EST','🇪🇪','+372'],['Eswatini','SZ','SWZ','🇸🇿','+268'],
            ['Ethiopia','ET','ETH','🇪🇹','+251'],['Fiji','FJ','FJI','🇫🇯','+679'],
            ['Finland','FI','FIN','🇫🇮','+358'],['France','FR','FRA','🇫🇷','+33'],
            ['Gabon','GA','GAB','🇬🇦','+241'],['Gambia','GM','GMB','🇬🇲','+220'],
            ['Georgia','GE','GEO','🇬🇪','+995'],['Germany','DE','DEU','🇩🇪','+49'],
            ['Ghana','GH','GHA','🇬🇭','+233'],['Greece','GR','GRC','🇬🇷','+30'],
            ['Grenada','GD','GRD','🇬🇩','+1-473'],['Guatemala','GT','GTM','🇬🇹','+502'],
            ['Guinea','GN','GIN','🇬🇳','+224'],['Guinea-Bissau','GW','GNB','🇬🇼','+245'],
            ['Guyana','GY','GUY','🇬🇾','+592'],['Haiti','HT','HTI','🇭🇹','+509'],
            ['Honduras','HN','HND','🇭🇳','+504'],['Hungary','HU','HUN','🇭🇺','+36'],
            ['Iceland','IS','ISL','🇮🇸','+354'],['India','IN','IND','🇮🇳','+91'],
            ['Indonesia','ID','IDN','🇮🇩','+62'],['Iran','IR','IRN','🇮🇷','+98'],
            ['Iraq','IQ','IRQ','🇮🇶','+964'],['Ireland','IE','IRL','🇮🇪','+353'],
            ['Israel','IL','ISR','🇮🇱','+972'],['Italy','IT','ITA','🇮🇹','+39'],
            ['Jamaica','JM','JAM','🇯🇲','+1-876'],['Japan','JP','JPN','🇯🇵','+81'],
            ['Jordan','JO','JOR','🇯🇴','+962'],['Kazakhstan','KZ','KAZ','🇰🇿','+7'],
            ['Kenya','KE','KEN','🇰🇪','+254'],['Kiribati','KI','KIR','🇰🇮','+686'],
            ['Kuwait','KW','KWT','🇰🇼','+965'],['Kyrgyzstan','KG','KGZ','🇰🇬','+996'],
            ['Laos','LA','LAO','🇱🇦','+856'],['Latvia','LV','LVA','🇱🇻','+371'],
            ['Lebanon','LB','LBN','🇱🇧','+961'],['Lesotho','LS','LSO','🇱🇸','+266'],
            ['Liberia','LR','LBR','🇱🇷','+231'],['Libya','LY','LBY','🇱🇾','+218'],
            ['Liechtenstein','LI','LIE','🇱🇮','+423'],['Lithuania','LT','LTU','🇱🇹','+370'],
            ['Luxembourg','LU','LUX','🇱🇺','+352'],['Madagascar','MG','MDG','🇲🇬','+261'],
            ['Malawi','MW','MWI','🇲🇼','+265'],['Malaysia','MY','MYS','🇲🇾','+60'],
            ['Maldives','MV','MDV','🇲🇻','+960'],['Mali','ML','MLI','🇲🇱','+223'],
            ['Malta','MT','MLT','🇲🇹','+356'],['Marshall Islands','MH','MHL','🇲🇭','+692'],
            ['Mauritania','MR','MRT','🇲🇷','+222'],['Mauritius','MU','MUS','🇲🇺','+230'],
            ['Mexico','MX','MEX','🇲🇽','+52'],['Micronesia','FM','FSM','🇫🇲','+691'],
            ['Moldova','MD','MDA','🇲🇩','+373'],['Monaco','MC','MCO','🇲🇨','+377'],
            ['Mongolia','MN','MNG','🇲🇳','+976'],['Montenegro','ME','MNE','🇲🇪','+382'],
            ['Morocco','MA','MAR','🇲🇦','+212'],['Mozambique','MZ','MOZ','🇲🇿','+258'],
            ['Myanmar','MM','MMR','🇲🇲','+95'],['Namibia','NA','NAM','🇳🇦','+264'],
            ['Nauru','NR','NRU','🇳🇷','+674'],['Nepal','NP','NPL','🇳🇵','+977'],
            ['Netherlands','NL','NLD','🇳🇱','+31'],['New Zealand','NZ','NZL','🇳🇿','+64'],
            ['Nicaragua','NI','NIC','🇳🇮','+505'],['Niger','NE','NER','🇳🇪','+227'],
            ['Nigeria','NG','NGA','🇳🇬','+234'],['North Korea','KP','PRK','🇰🇵','+850'],
            ['North Macedonia','MK','MKD','🇲🇰','+389'],['Norway','NO','NOR','🇳🇴','+47'],
            ['Oman','OM','OMN','🇴🇲','+968'],['Pakistan','PK','PAK','🇵🇰','+92'],
            ['Palau','PW','PLW','🇵🇼','+680'],['Palestine','PS','PSE','🇵🇸','+970'],
            ['Panama','PA','PAN','🇵🇦','+507'],['Papua New Guinea','PG','PNG','🇵🇬','+675'],
            ['Paraguay','PY','PRY','🇵🇾','+595'],['Peru','PE','PER','🇵🇪','+51'],
            ['Philippines','PH','PHL','🇵🇭','+63'],['Poland','PL','POL','🇵🇱','+48'],
            ['Portugal','PT','PRT','🇵🇹','+351'],['Qatar','QA','QAT','🇶🇦','+974'],
            ['Romania','RO','ROU','🇷🇴','+40'],['Russia','RU','RUS','🇷🇺','+7'],
            ['Rwanda','RW','RWA','🇷🇼','+250'],['Saint Kitts and Nevis','KN','KNA','🇰🇳','+1-869'],
            ['Saint Lucia','LC','LCA','🇱🇨','+1-758'],['Saint Vincent and the Grenadines','VC','VCT','🇻🇨','+1-784'],
            ['Samoa','WS','WSM','🇼🇸','+685'],['San Marino','SM','SMR','🇸🇲','+378'],
            ['Sao Tome and Principe','ST','STP','🇸🇹','+239'],['Saudi Arabia','SA','SAU','🇸🇦','+966'],
            ['Senegal','SN','SEN','🇸🇳','+221'],['Serbia','RS','SRB','🇷🇸','+381'],
            ['Seychelles','SC','SYC','🇸🇨','+248'],['Sierra Leone','SL','SLE','🇸🇱','+232'],
            ['Singapore','SG','SGP','🇸🇬','+65'],['Slovakia','SK','SVK','🇸🇰','+421'],
            ['Slovenia','SI','SVN','🇸🇮','+386'],['Solomon Islands','SB','SLB','🇸🇧','+677'],
            ['Somalia','SO','SOM','🇸🇴','+252'],['South Africa','ZA','ZAF','🇿🇦','+27'],
            ['South Korea','KR','KOR','🇰🇷','+82'],['South Sudan','SS','SSD','🇸🇸','+211'],
            ['Spain','ES','ESP','🇪🇸','+34'],['Sri Lanka','LK','LKA','🇱🇰','+94'],
            ['Sudan','SD','SDN','🇸🇩','+249'],['Suriname','SR','SUR','🇸🇷','+597'],
            ['Sweden','SE','SWE','🇸🇪','+46'],['Switzerland','CH','CHE','🇨🇭','+41'],
            ['Syria','SY','SYR','🇸🇾','+963'],['Taiwan','TW','TWN','🇹🇼','+886'],
            ['Tajikistan','TJ','TJK','🇹🇯','+992'],['Tanzania','TZ','TZA','🇹🇿','+255'],
            ['Thailand','TH','THA','🇹🇭','+66'],['Timor-Leste','TL','TLS','🇹🇱','+670'],
            ['Togo','TG','TGO','🇹🇬','+228'],['Tonga','TO','TON','🇹🇴','+676'],
            ['Trinidad and Tobago','TT','TTO','🇹🇹','+1-868'],['Tunisia','TN','TUN','🇹🇳','+216'],
            ['Turkey','TR','TUR','🇹🇷','+90'],['Turkmenistan','TM','TKM','🇹🇲','+993'],
            ['Tuvalu','TV','TUV','🇹🇻','+688'],['Uganda','UG','UGA','🇺🇬','+256'],
            ['Ukraine','UA','UKR','🇺🇦','+380'],['United Arab Emirates','AE','ARE','🇦🇪','+971'],
            ['United Kingdom','GB','GBR','🇬🇧','+44'],['United States','US','USA','🇺🇸','+1'],
            ['Uruguay','UY','URY','🇺🇾','+598'],['Uzbekistan','UZ','UZB','🇺🇿','+998'],
            ['Vanuatu','VU','VUT','🇻🇺','+678'],['Vatican City','VA','VAT','🇻🇦','+379'],
            ['Venezuela','VE','VEN','🇻🇪','+58'],['Vietnam','VN','VNM','🇻🇳','+84'],
            ['Yemen','YE','YEM','🇾🇪','+967'],['Zambia','ZM','ZMB','🇿🇲','+260'],
            ['Zimbabwe','ZW','ZWE','🇿🇼','+263'],
        ];
        // Insert only name/iso_code/flag_emoji — other columns are optional and added by ALTER above
        $stCountry = $pdo->prepare("{$insIgnore} countries (name, iso_code, flag_emoji) VALUES (?, ?, ?)");
        foreach ($worldCountries as $c) {
            $stCountry->execute([$c[0], $c[1], $c[3]]);
        }
        // Back-fill iso3_code and phone_code where still empty
        $stUpdate = $pdo->prepare("UPDATE countries SET iso3_code = ?, phone_code = ? WHERE iso_code = ? AND (iso3_code IS NULL OR iso3_code = '')");
        foreach ($worldCountries as $c) {
            $stUpdate->execute([$c[2], $c[4], $c[1]]);
        }

        // Seed system_settings default keys
        $insertIgnore = $driver === 'mysql' ? 'INSERT IGNORE INTO' : 'INSERT OR IGNORE INTO';
        $defaultSettings = [
            ['company_name', 'Visa Track & MS Travel Hub', 'General', 'Primary business name'],
            ['company_email', 'info@visatrack.com', 'General', 'Official business support email'],
            ['company_phone', '+94 11 234 5678', 'General', 'Customer service hotline'],
            ['company_website', 'https://visatrack.mstravelhub.com', 'General', 'Portal public domain'],
            ['currency', 'USD', 'Finance', 'System default currency'],
            ['currency_symbol', '$', 'Finance', 'Default currency display symbol'],
            ['tax_rate', '5', 'Finance', 'Standard VAT rate percentage'],
            ['invoice_prefix', 'INV-', 'Finance', 'Standard invoice numbering prefix'],
            ['receipt_prefix', 'REC-', 'Finance', 'Standard receipt numbering prefix'],
            ['sla_warning_threshold_days', '2', 'Workflow', 'Days before SLA deadline to trigger warning'],
        ];
        $stSetting = $pdo->prepare("{$insertIgnore} system_settings (setting_key, setting_value, setting_group, description) VALUES (?, ?, ?, ?)");
        foreach ($defaultSettings as $ds) {
            $stSetting->execute($ds);
        }

        // Safe column migrations for application operational tracking
        try {
            $pdo->exec("ALTER TABLE applications ADD COLUMN next_action TEXT NULL");
        } catch (\Throwable $e) {}

        try {
            $pdo->exec("ALTER TABLE applications ADD COLUMN next_action_due_date DATE NULL");
        } catch (\Throwable $e) {}

        try {
            $pdo->exec("ALTER TABLE visa_requirements ADD COLUMN is_critical INTEGER DEFAULT 0");
        } catch (\Throwable $e) {}

        try {
            $pdo->exec("ALTER TABLE customer_passports ADD COLUMN is_primary TINYINT(1) DEFAULT 1");
        } catch (\Throwable $e) {}

        try {
            $pdo->exec("ALTER TABLE customer_national_ids ADD COLUMN is_primary TINYINT(1) DEFAULT 1");
        } catch (\Throwable $e) {}

        try {
            $pdo->exec("ALTER TABLE tasks ADD COLUMN customer_id INT NULL");
        } catch (\Throwable $e) {}

        try {
            $pdo->exec("ALTER TABLE document_versions ADD COLUMN rejection_reason TEXT NULL");
        } catch (\Throwable $e) {}

        try {
            $pdo->exec("ALTER TABLE document_versions ADD COLUMN mime_type TEXT NULL");
        } catch (\Throwable $e) {}

        // Ensure document_versions table exists
        if ($driver === 'sqlite') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS document_versions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                document_id INTEGER NOT NULL,
                file_path TEXT NOT NULL,
                file_name TEXT NOT NULL,
                file_size INTEGER DEFAULT 0,
                mime_type TEXT,
                version_number INTEGER DEFAULT 1,
                rejection_reason TEXT,
                uploaded_by_type TEXT DEFAULT 'Staff',
                uploaded_by_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
            );");

            $pdo->exec("CREATE TABLE IF NOT EXISTS notification_preferences (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                event_type TEXT NOT NULL,
                in_app INTEGER DEFAULT 1,
                email INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(user_id, event_type),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );");

            $pdo->exec("CREATE TABLE IF NOT EXISTS visa_stages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                code TEXT NOT NULL UNIQUE,
                sequence_order INTEGER NOT NULL,
                default_sla_days INTEGER DEFAULT 2,
                description TEXT,
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );");

            $stageStmt = $pdo->prepare("INSERT OR IGNORE INTO visa_stages (name, code, sequence_order, default_sla_days, description) VALUES (?, ?, ?, ?, ?)");
            $defaultStages = [
                ['Draft & Registration', 'REGISTRATION', 1, 1, 'Initial registration and customer file setup'],
                ['Document Collection & Review', 'DOC_COLLECTION', 2, 2, 'All mandatory checklist documents uploaded & verified'],
                ['Application Form & Prep', 'FORM_PREP', 3, 1, 'Consular form drafted, reviewed and validated'],
                ['Appointment & Biometrics', 'BIOMETRICS', 4, 3, 'VFS / TLS / Consulate appointment booked and attended'],
                ['Submitted / Under Embassy Review', 'EMBASSY_PROCESS', 5, 10, 'Passport and file submitted to consular clearing unit'],
                ['Decision Received & Visa Stamping', 'DECISION', 6, 1, 'Visa grant decision issued and approved document stamped'],
            ];
            foreach ($defaultStages as $stg) {
                $stageStmt->execute($stg);
            }

            // Production Performance Indexes
            $indexes = [
                "CREATE INDEX IF NOT EXISTS idx_apps_num_pass ON applications(application_number, passport_number)",
                "CREATE INDEX IF NOT EXISTS idx_apps_status_stage ON applications(status, current_stage)",
                "CREATE INDEX IF NOT EXISTS idx_apps_staff ON applications(assigned_staff_id)",
                "CREATE INDEX IF NOT EXISTS idx_apps_customer ON applications(customer_id)",
                "CREATE INDEX IF NOT EXISTS idx_tasks_staff_due ON tasks(assigned_to, due_date, status)",
                "CREATE INDEX IF NOT EXISTS idx_docs_app_type ON documents(application_id, document_type_id)",
                "CREATE INDEX IF NOT EXISTS idx_activity_logs_search ON activity_logs(module, action, created_at)",
                "CREATE INDEX IF NOT EXISTS idx_notifications_user_read ON notifications(user_id, is_read)",
                "CREATE INDEX IF NOT EXISTS idx_customers_code_email ON customers(customer_code, email)",
            ];

            foreach ($indexes as $sql) {
                try {
                    $pdo->exec($sql);
                } catch (\Throwable $e) {}
            }
        }

        // Auto-seed all 13 Phase 8 Permission Categories & Actions if not already populated
        self::ensurePhase8Data($pdo);

        // Auto-seed Central Real-Time Notification & WhatsApp/Email infrastructure
        self::ensureNotificationSystem($pdo);
    }

    private static function ensureNotificationSystem(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS notification_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_type TEXT NOT NULL UNIQUE,
                event_category TEXT DEFAULT 'General',
                title TEXT NOT NULL,
                description TEXT,
                email_enabled INTEGER DEFAULT 1,
                whatsapp_enabled INTEGER DEFAULT 1,
                in_app_enabled INTEGER DEFAULT 1,
                applicant_enabled INTEGER DEFAULT 1,
                staff_enabled INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );");

            $pdo->exec("CREATE TABLE IF NOT EXISTS notification_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_type TEXT NOT NULL,
                recipient_type TEXT DEFAULT 'Applicant',
                recipient_id INTEGER,
                recipient_name TEXT,
                recipient_email TEXT,
                recipient_phone TEXT,
                channel TEXT NOT NULL,
                template_name TEXT,
                subject TEXT,
                content_preview TEXT,
                idempotency_key TEXT,
                status TEXT DEFAULT 'Pending',
                provider_message_id TEXT,
                request_payload TEXT,
                response_payload TEXT,
                error_message TEXT,
                retry_count INTEGER DEFAULT 0,
                max_retries INTEGER DEFAULT 3,
                next_retry_at DATETIME,
                sent_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );");

            $pdo->exec("CREATE TABLE IF NOT EXISTS notification_queue (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_type TEXT NOT NULL,
                recipient_type TEXT DEFAULT 'Applicant',
                recipient_id INTEGER,
                recipient_name TEXT,
                recipient_email TEXT,
                recipient_phone TEXT,
                channel TEXT NOT NULL,
                template_name TEXT,
                idempotency_key TEXT UNIQUE,
                payload_json TEXT NOT NULL,
                status TEXT DEFAULT 'Pending',
                error_message TEXT,
                retry_count INTEGER DEFAULT 0,
                max_retries INTEGER DEFAULT 3,
                next_retry_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                processed_at DATETIME
            );");

            $pdo->exec("CREATE TABLE IF NOT EXISTS notification_templates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_type TEXT NOT NULL,
                channel TEXT NOT NULL,
                template_name TEXT NOT NULL,
                subject TEXT,
                content TEXT NOT NULL,
                provider_template_id TEXT,
                language_code TEXT DEFAULT 'en_US',
                variables TEXT,
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(event_type, channel)
            );");

            // Performance indexes
            $indexes = [
                "CREATE INDEX IF NOT EXISTS idx_notif_logs_event ON notification_logs(event_type)",
                "CREATE INDEX IF NOT EXISTS idx_notif_logs_status ON notification_logs(status)",
                "CREATE INDEX IF NOT EXISTS idx_notif_logs_channel ON notification_logs(channel)",
                "CREATE INDEX IF NOT EXISTS idx_notif_logs_recipient ON notification_logs(recipient_type, recipient_id)",
                "CREATE INDEX IF NOT EXISTS idx_notif_logs_idemp ON notification_logs(idempotency_key)",
                "CREATE INDEX IF NOT EXISTS idx_notif_queue_status_retry ON notification_queue(status, next_retry_at)",
            ];
            foreach ($indexes as $sql) {
                try {
                    $pdo->exec($sql);
                } catch (\Throwable $e) {}
            }
        } else {
            // MySQL Schema
            $pdo->exec("CREATE TABLE IF NOT EXISTS notification_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                event_type VARCHAR(100) NOT NULL UNIQUE,
                event_category VARCHAR(50) DEFAULT 'General',
                title VARCHAR(200) NOT NULL,
                description TEXT,
                email_enabled TINYINT(1) DEFAULT 1,
                whatsapp_enabled TINYINT(1) DEFAULT 1,
                in_app_enabled TINYINT(1) DEFAULT 1,
                applicant_enabled TINYINT(1) DEFAULT 1,
                staff_enabled TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS notification_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                event_type VARCHAR(100) NOT NULL,
                recipient_type VARCHAR(50) DEFAULT 'Applicant',
                recipient_id INT NULL,
                recipient_name VARCHAR(150) NULL,
                recipient_email VARCHAR(150) NULL,
                recipient_phone VARCHAR(50) NULL,
                channel VARCHAR(30) NOT NULL,
                template_name VARCHAR(100) NULL,
                subject VARCHAR(255) NULL,
                content_preview TEXT NULL,
                idempotency_key VARCHAR(150) NULL,
                status VARCHAR(30) DEFAULT 'Pending',
                provider_message_id VARCHAR(255) NULL,
                request_payload LONGTEXT NULL,
                response_payload LONGTEXT NULL,
                error_message TEXT NULL,
                retry_count INT DEFAULT 0,
                max_retries INT DEFAULT 3,
                next_retry_at DATETIME NULL,
                sent_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_notif_logs_event (event_type),
                INDEX idx_notif_logs_status (status),
                INDEX idx_notif_logs_channel (channel),
                INDEX idx_notif_logs_recipient (recipient_type, recipient_id),
                INDEX idx_notif_logs_idemp (idempotency_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS notification_queue (
                id INT AUTO_INCREMENT PRIMARY KEY,
                event_type VARCHAR(100) NOT NULL,
                recipient_type VARCHAR(50) DEFAULT 'Applicant',
                recipient_id INT NULL,
                recipient_name VARCHAR(150) NULL,
                recipient_email VARCHAR(150) NULL,
                recipient_phone VARCHAR(50) NULL,
                channel VARCHAR(30) NOT NULL,
                template_name VARCHAR(100) NULL,
                idempotency_key VARCHAR(150) UNIQUE,
                payload_json LONGTEXT NOT NULL,
                status VARCHAR(30) DEFAULT 'Pending',
                error_message TEXT NULL,
                retry_count INT DEFAULT 0,
                max_retries INT DEFAULT 3,
                next_retry_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                processed_at DATETIME NULL,
                INDEX idx_notif_queue_status_retry (status, next_retry_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS notification_templates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                event_type VARCHAR(100) NOT NULL,
                channel VARCHAR(30) NOT NULL,
                template_name VARCHAR(100) NOT NULL,
                subject VARCHAR(255) NULL,
                content LONGTEXT NOT NULL,
                provider_template_id VARCHAR(150) NULL,
                language_code VARCHAR(10) DEFAULT 'en_US',
                variables TEXT NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_event_channel (event_type, channel)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        }

        // Seed 25+ standard lifecycle event settings
        $defaultSettings = [
            // Applicant events
            ['applicant.registered', 'Applicant', 'New Applicant Registered', 'Triggered when a new customer/applicant profile is registered in the system.', 1, 1, 1, 1, 1],
            ['applicant.profile_updated', 'Applicant', 'Applicant Profile Updated', 'Triggered when customer passport, bio or contact details are modified.', 1, 0, 1, 1, 1],
            ['applicant.approved', 'Applicant', 'Applicant Profile Verified & Approved', 'Triggered when an applicant account verification is approved.', 1, 1, 1, 1, 1],
            ['applicant.rejected', 'Applicant', 'Applicant File Rejected / Blacklisted', 'Triggered when an applicant file is rejected due to policy restrictions.', 1, 1, 1, 1, 1],
            ['applicant.status_changed', 'Applicant', 'Applicant Status Changed', 'Triggered when customer operational status changes.', 1, 1, 1, 1, 1],

            // Interview / Appointment events
            ['interview.scheduled', 'Interview', 'Interview / Embassy Appointment Scheduled', 'Triggered when an embassy, VFS, or consular interview is scheduled.', 1, 1, 1, 1, 1],
            ['interview.rescheduled', 'Interview', 'Interview / Embassy Appointment Rescheduled', 'Triggered when an appointment slot or center location changes.', 1, 1, 1, 1, 1],
            ['interview.cancelled', 'Interview', 'Interview / Appointment Cancelled', 'Triggered when an appointment is cancelled.', 1, 1, 1, 1, 1],
            ['interview.reminder', 'Interview', 'Interview / Appointment Upcoming Reminder', 'Sent 24-48 hours before the scheduled appointment time.', 1, 1, 1, 1, 1],
            ['interview.result_updated', 'Interview', 'Interview Outcome / Biometrics Updated', 'Triggered when interview attendance and biometrics outcome is recorded.', 1, 1, 1, 1, 1],

            // Visa Lifecycle events
            ['visa.created', 'Visa', 'Visa Application Created', 'Triggered when a new visa case file is opened.', 1, 1, 1, 1, 1],
            ['visa.processing_started', 'Visa', 'Visa Application Processing Started', 'Triggered when file moves to active preparation and consular processing.', 1, 1, 1, 1, 1],
            ['visa.stage_changed', 'Visa', 'Visa Application Stage Shift', 'Triggered whenever the visa advances along the 6-stage lifecycle.', 1, 1, 1, 1, 1],
            ['visa.approved', 'Visa', 'Visa Approved & Stamped', 'Triggered when consular authorities grant and issue the visa.', 1, 1, 1, 1, 1],
            ['visa.rejected', 'Visa', 'Visa Application Refused / Rejected', 'Triggered when a visa is refused by consular authorities.', 1, 1, 1, 1, 1],
            ['visa.expired', 'Visa', 'Visa Expiration Warning', 'Triggered when an issued visa is approaching its expiration date.', 1, 1, 1, 1, 1],
            ['visa.document_required', 'Visa', 'Visa Additional Document Required', 'Triggered when consular or processing staff request extra documentation.', 1, 1, 1, 1, 1],
            ['visa.document_approved', 'Visa', 'Visa Document Verified & Approved', 'Triggered when an uploaded file is verified successfully.', 1, 0, 1, 1, 1],
            ['visa.document_rejected', 'Visa', 'Visa Document Verification Returned', 'Triggered when an uploaded file fails verification and needs re-upload.', 1, 1, 1, 1, 1],

            // Placement / Employer events
            ['placement.created', 'Placement', 'Placement Match Created', 'Triggered when applicant is matched with overseas sponsor/employer.', 1, 1, 1, 1, 1],
            ['placement.confirmed', 'Placement', 'Placement Offer Confirmed', 'Triggered when contract and job placement terms are confirmed.', 1, 1, 1, 1, 1],
            ['placement.agreement_generated', 'Placement', 'Placement Agreement Issued', 'Triggered when official employment/placement agreement is generated.', 1, 1, 1, 1, 1],
            ['placement.status_changed', 'Placement', 'Placement Status Changed', 'Triggered when placement status transitions.', 1, 1, 1, 1, 1],

            // Payment events
            ['payment.created', 'Payment', 'Payment Invoice Created', 'Triggered when a fee invoice is issued for an application.', 1, 1, 1, 1, 1],
            ['payment.received', 'Payment', 'Payment Received & Receipt Generated', 'Triggered when payment is collected and verified in accounts.', 1, 1, 1, 1, 1],
            ['payment.pending', 'Payment', 'Payment Pending Confirmation', 'Triggered when an offline transfer is submitted for verification.', 1, 0, 1, 1, 1],
            ['payment.overdue', 'Payment', 'Payment Overdue Notice', 'Triggered when an invoice has passed its due date.', 1, 1, 1, 1, 1],
            ['payment.completed', 'Payment', 'Payment Completed & Zero Balance', 'Triggered when all invoice milestones are 100% cleared.', 1, 1, 1, 1, 1],
            ['payment.refunded', 'Payment', 'Payment Refund Processed', 'Triggered when a refund is approved and issued to customer.', 1, 1, 1, 1, 1],

            // Document events
            ['document.uploaded', 'Documents', 'Document Uploaded by Customer', 'Triggered when an applicant uploads a new file on self-service portal.', 1, 0, 1, 0, 1],
            ['document.approved', 'Documents', 'Document Verification Approved', 'Triggered when verification officer accepts uploaded file.', 1, 1, 1, 1, 1],
            ['document.rejected', 'Documents', 'Document Rejected — Replacement Required', 'Triggered when verification officer rejects uploaded file.', 1, 1, 1, 1, 1],
            ['document.reminder_missing', 'Documents', 'Missing Mandatory Document Reminder', 'Triggered to remind applicant about missing mandatory files.', 1, 1, 1, 1, 1],
            ['document.expiry_reminder', 'Documents', 'Document Expiry Warning', 'Triggered when passport/ID in system is approaching expiry.', 1, 1, 1, 1, 1],

            // System / Security events
            ['system.announcement', 'System', 'Important System Announcement', 'Broadcast operational notices to staff or customers.', 1, 0, 1, 1, 1],
            ['account.created', 'System', 'Portal Login Credentials Created', 'Triggered when customer or staff login credentials are provisioned.', 1, 1, 1, 1, 1],
            ['password.reset_requested', 'System', 'Password Reset Link Requested', 'Triggered when password reset workflow is requested.', 1, 0, 1, 1, 0],
            ['password.security_alert', 'System', 'Account Security / Password Changed', 'Triggered when password or 2FA credentials change.', 1, 1, 1, 1, 1],
        ];

        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $insertIgnore = $driver === 'mysql' ? 'INSERT IGNORE INTO' : 'INSERT OR IGNORE INTO';

        $insSettingStmt = $pdo->prepare("{$insertIgnore} notification_settings 
            (event_type, event_category, title, description, email_enabled, whatsapp_enabled, in_app_enabled, applicant_enabled, staff_enabled) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($defaultSettings as $s) {
            $insSettingStmt->execute($s);
        }

        // Seed comprehensive Email and WhatsApp templates
        $defaultTemplates = [
            // EMAIL TEMPLATES
            [
                'applicant.registered',
                'Email',
                'applicant_registered_email',
                'Welcome to {{companyName}} — Registration Confirmed',
                '<h3>Welcome to {{companyName}}</h3><p>Dear {{applicantName}},</p><p>Your applicant profile has been successfully created in our visa management system.</p><p><strong>Customer ID:</strong> {{customerCode}}<br><strong>Registered Mobile:</strong> {{applicantPhone}}<br><strong>Email:</strong> {{applicantEmail}}</p><p>You can now access your customer self-service portal at any time to track your visa applications and upload required documents.</p><p style="text-align: center; margin: 25px 0;"><a href="{{loginUrl}}" style="background: #0284c7; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">Access Applicant Portal</a></p><p>If you have any questions, our visa consulting team is here to assist you.</p>',
                null,
                'en_US',
                'applicantName, customerCode, applicantEmail, applicantPhone, companyName, loginUrl, supportEmail'
            ],
            [
                'interview.scheduled',
                'Email',
                'interview_scheduled_email',
                'Interview / Embassy Appointment Scheduled — {{applicationNumber}}',
                '<h3>Embassy / Visa Appointment Confirmation</h3><p>Dear {{applicantName}},</p><p>Your appointment for <strong>{{countryName}} ({{visaType}})</strong> has been officially scheduled.</p><div style="background: #f8fafc; border-left: 4px solid #0284c7; padding: 15px; margin: 15px 0;"><p style="margin: 0 0 8px 0;"><strong>Appointment Type:</strong> {{appointmentType}}</p><p style="margin: 0 0 8px 0;"><strong>Date:</strong> {{interviewDate}}</p><p style="margin: 0 0 8px 0;"><strong>Time:</strong> {{interviewTime}}</p><p style="margin: 0 0 8px 0;"><strong>Center / Embassy:</strong> {{centerName}}</p><p style="margin: 0;"><strong>Location Address:</strong> {{locationAddress}}</p></div><p><strong>Important Instructions:</strong></p><ul><li>Please arrive at the center at least 15 minutes prior to your scheduled time.</li><li>Bring your original passport, appointment confirmation letter, and all original supporting documents.</li><li>Electronic devices and luggage may not be permitted inside the consular premises.</li></ul><p style="text-align: center; margin: 25px 0;"><a href="{{actionUrl}}" style="background: #0284c7; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">View Appointment Slip</a></p>',
                null,
                'en_US',
                'applicantName, applicationNumber, countryName, visaType, appointmentType, interviewDate, interviewTime, centerName, locationAddress, actionUrl, companyName'
            ],
            [
                'interview.rescheduled',
                'Email',
                'interview_rescheduled_email',
                'Appointment Rescheduled: {{applicationNumber}} (New Date: {{interviewDate}})',
                '<h3>Visa Appointment Rescheduled</h3><p>Dear {{applicantName}},</p><p>Please note that your appointment for application <strong>{{applicationNumber}}</strong> has been rescheduled.</p><div style="background: #fffbeb; border-left: 4px solid #f59e0b; padding: 15px; margin: 15px 0;"><p style="margin: 0 0 8px 0;"><strong>New Date:</strong> {{interviewDate}}</p><p style="margin: 0 0 8px 0;"><strong>New Time:</strong> {{interviewTime}}</p><p style="margin: 0 0 8px 0;"><strong>Center / Embassy:</strong> {{centerName}}</p><p style="margin: 0;"><strong>Location:</strong> {{locationAddress}}</p></div><p><a href="{{actionUrl}}">Download updated appointment confirmation</a></p>',
                null,
                'en_US',
                'applicantName, applicationNumber, interviewDate, interviewTime, centerName, locationAddress, actionUrl, companyName'
            ],
            [
                'visa.created',
                'Email',
                'visa_created_email',
                'Visa Application Created: {{applicationNumber}} — {{countryName}}',
                '<h3>Visa Application Successfully Opened</h3><p>Dear {{applicantName}},</p><p>A new visa application has been registered for you at {{companyName}}.</p><p><strong>Application Number:</strong> {{applicationNumber}}<br><strong>Destination:</strong> {{countryName}}<br><strong>Visa Category:</strong> {{visaType}}<br><strong>Current Stage:</strong> {{currentStage}}</p><p>Please complete any pending checklist documents to expedite consular submission.</p><p style="text-align: center; margin: 25px 0;"><a href="{{actionUrl}}" style="background: #0284c7; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">Track Application Status</a></p>',
                null,
                'en_US',
                'applicantName, applicationNumber, countryName, visaType, currentStage, actionUrl, companyName'
            ],
            [
                'visa.stage_changed',
                'Email',
                'visa_stage_changed_email',
                'Visa Status Update: {{applicationNumber}} is now in {{currentStage}}',
                '<h3>Visa Lifecycle Progress Update</h3><p>Dear {{applicantName}},</p><p>Your visa application <strong>{{applicationNumber}}</strong> for <strong>{{countryName}}</strong> has advanced to a new stage.</p><div style="background: #f0fdf4; border-left: 4px solid #16a34a; padding: 15px; margin: 15px 0;"><p style="margin: 0 0 8px 0;"><strong>Current Stage:</strong> {{currentStage}}</p><p style="margin: 0 0 8px 0;"><strong>Application Status:</strong> {{status}}</p><p style="margin: 0;"><strong>Next Planned Action:</strong> {{nextAction}}</p></div><p style="text-align: center; margin: 25px 0;"><a href="{{actionUrl}}" style="background: #16a34a; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">View Real-Time Progress</a></p>',
                null,
                'en_US',
                'applicantName, applicationNumber, countryName, currentStage, status, nextAction, actionUrl, companyName'
            ],
            [
                'visa.approved',
                'Email',
                'visa_approved_email',
                'Congratulations! Your Visa Has Been Approved — {{applicationNumber}}',
                '<h2 style="color: #16a34a;">Congratulations! Visa Granted</h2><p>Dear {{applicantName}},</p><p>We are delighted to inform you that your visa application for <strong>{{countryName}} ({{visaType}})</strong> has been <strong>APPROVED &amp; ISSUED</strong> by consular authorities!</p><div style="background: #f0fdf4; border-left: 4px solid #16a34a; padding: 15px; margin: 15px 0;"><p style="margin: 0 0 8px 0;"><strong>Application No:</strong> {{applicationNumber}}</p><p style="margin: 0 0 8px 0;"><strong>Destination Country:</strong> {{countryName}}</p><p style="margin: 0;"><strong>Passport Number:</strong> {{passportNumber}}</p></div><p>Your official visa document and stamped confirmation are now available for secure download in your portal.</p><p style="text-align: center; margin: 25px 0;"><a href="{{actionUrl}}" style="background: #16a34a; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">Download Official Visa Document</a></p><p>We wish you a safe and successful journey!</p>',
                null,
                'en_US',
                'applicantName, applicationNumber, countryName, visaType, passportNumber, actionUrl, companyName'
            ],
            [
                'visa.rejected',
                'Email',
                'visa_rejected_email',
                'Important Notice Regarding Your Visa Application — {{applicationNumber}}',
                '<h3 style="color: #dc2626;">Visa Application Decision Notice</h3><p>Dear {{applicantName}},</p><p>We regret to inform you that consular authorities have returned a decision of refusal for visa application <strong>{{applicationNumber}}</strong> ({{countryName}}).</p><div style="background: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin: 15px 0;"><p style="margin: 0;"><strong>Reason / Notes:</strong> {{decisionNotes}}</p></div><p>Our senior visa consulting team is currently reviewing your refusal grounds to advise on appeals, administrative review, or re-application options.</p><p><a href="{{actionUrl}}">Review consular refusal details</a></p>',
                null,
                'en_US',
                'applicantName, applicationNumber, countryName, decisionNotes, actionUrl, companyName'
            ],
            [
                'document.rejected',
                'Email',
                'document_rejected_email',
                'Document Verification Returned — Replacement Required ({{applicationNumber}})',
                '<h3>Document Replacement Required</h3><p>Dear {{applicantName}},</p><p>Our visa compliance officers have reviewed your uploaded document for application <strong>{{applicationNumber}}</strong> and found that a replacement copy is required.</p><div style="background: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin: 15px 0;"><p style="margin: 0 0 8px 0;"><strong>Document:</strong> {{documentName}}</p><p style="margin: 0;"><strong>Issue / Reason:</strong> {{rejectionReason}}</p></div><p>Please upload a high-resolution, full-page replacement copy immediately to avoid delays in embassy filing.</p><p style="text-align: center; margin: 25px 0;"><a href="{{actionUrl}}" style="background: #dc2626; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">Upload Replacement Document</a></p>',
                null,
                'en_US',
                'applicantName, applicationNumber, documentName, rejectionReason, actionUrl, companyName'
            ],
            [
                'payment.received',
                'Email',
                'payment_received_email',
                'Payment Received & Receipt Generated — {{paymentNumber}}',
                '<h3>Payment Receipt Confirmation</h3><p>Dear {{applicantName}},</p><p>We have successfully received and processed your payment for application <strong>{{applicationNumber}}</strong>.</p><div style="background: #f8fafc; border-left: 4px solid #0284c7; padding: 15px; margin: 15px 0;"><p style="margin: 0 0 8px 0;"><strong>Receipt / Payment No:</strong> {{paymentNumber}}</p><p style="margin: 0 0 8px 0;"><strong>Amount Paid:</strong> {{amount}} {{currency}}</p><p style="margin: 0 0 8px 0;"><strong>Payment Method:</strong> {{paymentMethod}}</p><p style="margin: 0;"><strong>Date:</strong> {{paymentDate}}</p></div><p style="text-align: center; margin: 25px 0;"><a href="{{receiptUrl}}" style="background: #0284c7; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">Download Official Receipt</a></p>',
                null,
                'en_US',
                'applicantName, applicationNumber, paymentNumber, amount, currency, paymentMethod, paymentDate, receiptUrl, companyName'
            ],

            // WHATSAPP TEMPLATES (Meta Cloud API Approved Formats)
            [
                'applicant.registered',
                'WhatsApp',
                'applicant_welcome_wa',
                null,
                "Hello {{applicantName}},\n\nWelcome to {{companyName}}! Your applicant profile has been created.\n\nCustomer Code: {{customerCode}}\n\nYou can track your visa application status on our customer portal: {{loginUrl}}\n\nThank you for choosing {{companyName}}.",
                'applicant_welcome',
                'en_US',
                'applicantName, companyName, customerCode, loginUrl'
            ],
            [
                'interview.scheduled',
                'WhatsApp',
                'interview_scheduled_wa',
                null,
                "Hello {{applicantName}},\n\nYour visa appointment has been scheduled.\n\nApplication No: {{applicationNumber}}\nDate: {{interviewDate}}\nTime: {{interviewTime}}\nCenter: {{centerName}}\nLocation: {{locationAddress}}\n\nPlease arrive 15 minutes early with all original documents.\n\n{{companyName}}",
                'interview_scheduled',
                'en_US',
                'applicantName, applicationNumber, interviewDate, interviewTime, centerName, locationAddress, companyName'
            ],
            [
                'interview.rescheduled',
                'WhatsApp',
                'interview_rescheduled_wa',
                null,
                "Hello {{applicantName}},\n\nYour visa appointment for application {{applicationNumber}} has been rescheduled.\n\nNew Date: {{interviewDate}}\nNew Time: {{interviewTime}}\nCenter: {{centerName}}\n\nPlease check your portal for the updated appointment slip.\n\n{{companyName}}",
                'interview_rescheduled',
                'en_US',
                'applicantName, applicationNumber, interviewDate, interviewTime, centerName, companyName'
            ],
            [
                'visa.stage_changed',
                'WhatsApp',
                'visa_stage_changed_wa',
                null,
                "Hello {{applicantName}},\n\nYour visa application status has been updated.\n\nApplication No: {{applicationNumber}}\nCurrent Stage: {{currentStage}}\nStatus: {{status}}\n\nPlease log in to your portal for full details: {{actionUrl}}\n\n{{companyName}}",
                'visa_status_update',
                'en_US',
                'applicantName, applicationNumber, currentStage, status, actionUrl, companyName'
            ],
            [
                'visa.approved',
                'WhatsApp',
                'visa_approved_wa',
                null,
                "🎉 Congratulations {{applicantName}}!\n\nYour visa for {{countryName}} has been APPROVED and issued!\n\nApplication No: {{applicationNumber}}\nPassport: {{passportNumber}}\n\nPlease log in to download your official e-visa document: {{actionUrl}}\n\n{{companyName}}",
                'visa_approved',
                'en_US',
                'applicantName, countryName, applicationNumber, passportNumber, actionUrl, companyName'
            ],
            [
                'visa.rejected',
                'WhatsApp',
                'visa_rejected_wa',
                null,
                "Hello {{applicantName}},\n\nAn update is available regarding your visa application {{applicationNumber}} for {{countryName}}.\n\nPlease log in to your portal to review the decision details and next steps: {{actionUrl}}\n\n{{companyName}}",
                'visa_decision_notice',
                'en_US',
                'applicantName, applicationNumber, countryName, actionUrl, companyName'
            ],
            [
                'document.rejected',
                'WhatsApp',
                'document_rejected_wa',
                null,
                "Hello {{applicantName}},\n\nA replacement document is required for your visa application {{applicationNumber}}.\n\nDocument: {{documentName}}\nReason: {{rejectionReason}}\n\nPlease upload a replacement copy promptly: {{actionUrl}}\n\n{{companyName}}",
                'document_required',
                'en_US',
                'applicantName, applicationNumber, documentName, rejectionReason, actionUrl, companyName'
            ],
            [
                'payment.received',
                'WhatsApp',
                'payment_received_wa',
                null,
                "Hello {{applicantName}},\n\nWe have received your payment of {{amount}} {{currency}} for application {{applicationNumber}}.\n\nReceipt No: {{paymentNumber}}\nDate: {{paymentDate}}\n\nDownload receipt: {{receiptUrl}}\n\n{{companyName}}",
                'payment_received',
                'en_US',
                'applicantName, amount, currency, applicationNumber, paymentNumber, paymentDate, receiptUrl, companyName'
            ],
            [
                'placement.confirmed',
                'WhatsApp',
                'placement_confirmed_wa',
                null,
                "Hello {{applicantName}},\n\nYour overseas placement offer for application {{applicationNumber}} has been confirmed!\n\nPosition: {{position}}\nLocation: {{location}}\n\nPlease log in to your portal to review your placement agreement: {{actionUrl}}\n\n{{companyName}}",
                'placement_confirmed',
                'en_US',
                'applicantName, applicationNumber, position, location, actionUrl, companyName'
            ]
        ];

        $insTmplStmt = $pdo->prepare("{$insertIgnore} notification_templates 
            (event_type, channel, template_name, subject, content, provider_template_id, language_code, variables) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($defaultTemplates as $t) {
            $insTmplStmt->execute($t);
        }
    }

    private static function ensurePhase8Data(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $insertIgnore = $driver === 'mysql' ? 'INSERT IGNORE INTO' : 'INSERT OR IGNORE INTO';

        $categories = [
            'Applications', 'Applicants', 'Documents', 'Tasks', 'Appointments',
            'Payments', 'Reports', 'Staff', 'Branches', 'Suppliers',
            'Notifications', 'Audit', 'Settings'
        ];

        $actions = ['view', 'create', 'edit', 'delete', 'approve', 'assign', 'export'];

        $stmt = $pdo->prepare("{$insertIgnore} permissions (name, slug, module, description) VALUES (?, ?, ?, ?)");

        foreach ($categories as $cat) {
            foreach ($actions as $act) {
                $slug = strtolower($cat) . '.' . $act;
                $name = ucfirst($act) . ' ' . $cat;
                $desc = "Permission to {$act} {$cat} records";
                $stmt->execute([$name, $slug, $cat, $desc]);
            }
        }

        // Grant all permissions to Super Admin (role_id = 1) and Admin (role_id = 2)
        $permIds = $pdo->query("SELECT id FROM permissions")->fetchAll(PDO::FETCH_COLUMN);
        $rolePermStmt = $pdo->prepare("{$insertIgnore} role_permissions (role_id, permission_id) VALUES (?, ?)");
        
        foreach ([1, 2] as $rId) {
            foreach ($permIds as $pId) {
                $rolePermStmt->execute([$rId, $pId]);
            }
        }

        // Ensure all 7 standard email templates exist with placeholders
        $emailTemplates = [
            ['TASK_ASSIGNED', 'Task Assigned Notification', 'New Task Assigned: {{task_title}} (Due: {{due_date}})', '<p>Dear {{user_name}},</p><p>You have been assigned a new operational task: <strong>{{task_title}}</strong>.</p><p>Related Application: <strong>{{application_number}}</strong><br>Applicant: <strong>{{applicant_name}}</strong><br>Deadline: <strong>{{due_date}}</strong></p><p><a href="{{action_url}}">Click here to view task details</a></p>', '{{user_name}}, {{applicant_name}}, {{application_number}}, {{task_title}}, {{due_date}}, {{action_url}}'],
            ['TASK_DUE_SOON', 'Task Approaching SLA Deadline', 'Urgent: Task {{task_title}} Due Soon', '<p>Dear {{user_name}},</p><p>The task <strong>{{task_title}}</strong> for applicant <strong>{{applicant_name}}</strong> is approaching its SLA target.</p><p>Due Date: <strong>{{due_date}}</strong></p><p><a href="{{action_url}}">Resolve and complete task</a></p>', '{{user_name}}, {{applicant_name}}, {{application_number}}, {{task_title}}, {{due_date}}, {{action_url}}'],
            ['TASK_OVERDUE', 'Task SLA Overdue Alert', 'CRITICAL SLA ALERT: Task {{task_title}} is Overdue', '<p>Attention {{user_name}},</p><p>The task <strong>{{task_title}}</strong> on application <strong>{{application_number}}</strong> is now <strong>OVERDUE</strong>.</p><p>Immediate operational intervention is required.</p><p><a href="{{action_url}}">Take Immediate Action</a></p>', '{{user_name}}, {{applicant_name}}, {{application_number}}, {{task_title}}, {{due_date}}, {{action_url}}'],
            ['DOC_REJECTED', 'Document Verification Returned', 'Document Verification Failed: {{application_number}}', '<p>Dear {{applicant_name}},</p><p>Your uploaded document for application <strong>{{application_number}}</strong> could not be verified by our visa team.</p><p>Please upload a clear replacement copy to prevent embassy filing delays.</p><p><a href="{{action_url}}">Upload Replacement Document</a></p>', '{{user_name}}, {{applicant_name}}, {{application_number}}, {{task_title}}, {{due_date}}, {{action_url}}'],
            ['APP_UPDATED', 'Application Lifecycle Stage Updated', 'Visa Progress Update: {{application_number}} is now in {{current_stage}}', '<p>Dear {{applicant_name}},</p><p>Your visa application <strong>{{application_number}}</strong> has successfully moved to the next lifecycle stage.</p><p>Next Milestone: <strong>{{next_action}}</strong></p><p><a href="{{action_url}}">Track Real-Time Status</a></p>', '{{user_name}}, {{applicant_name}}, {{application_number}}, {{task_title}}, {{due_date}}, {{action_url}}'],
            ['APP_APPROVED', 'Visa Approved & Ready for Download', 'Congratulations! Visa Approved for {{applicant_name}} ({{application_number}})', '<p>Dear {{applicant_name}},</p><p>We are thrilled to confirm that your visa for <strong>{{destination_country}}</strong> has been granted and issued by consular authorities.</p><p><a href="{{action_url}}">Download Official Visa e-Document</a></p>', '{{user_name}}, {{applicant_name}}, {{application_number}}, {{task_title}}, {{due_date}}, {{action_url}}'],
            ['PASSWORD_RESET', 'Administrative Password Reset', 'Your VISA TRACK Account Password Reset', '<p>Hello {{user_name}},</p><p>Your security credentials for the VISA TRACK Operations Portal have been reset by system administration.</p><p>Temporary Access Link: <a href="{{action_url}}">Reset and choose your new password</a></p>', '{{user_name}}, {{applicant_name}}, {{application_number}}, {{task_title}}, {{due_date}}, {{action_url}}'],
        ];

        $tmplStmt = $pdo->prepare("{$insertIgnore} email_templates (template_key, title, subject, body_html, placeholders) VALUES (?, ?, ?, ?, ?)");
        foreach ($emailTemplates as $t) {
            $tmplStmt->execute($t);
        }
    }
}

