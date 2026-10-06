<?php
declare(strict_types=1);

namespace App\Database;

use PDO;
use App\Config\App;

class SeedData
{
    public static function seed(PDO $pdo): void
    {
        $ins = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') ? 'INSERT IGNORE INTO' : 'INSERT OR IGNORE INTO';

        // Disable foreign key checks during seeding to allow safe multi-table provisioning
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            try { $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;"); } catch (\Throwable $e) {}
        }

        // 0. Companies (Required as root parent for branches in MySQL)
        try {
            $pdo->exec("{$ins} companies (id, name, code, email, phone, address, status) VALUES 
                (1, 'MS Travel Hub Global Visa Services', 'MSTH-01', 'mstravelu@gmail.com', '0585909349', 'Dubai, United Arab Emirates', 'active')");
        } catch (\Throwable $e) {}

        // 1. Roles
        $roles = [
            ['Super Admin', 'super-admin', 'Full system control and unrestricted access'],
            ['Admin', 'admin', 'Administrative access to all operations'],
            ['Branch Manager', 'branch-manager', 'Branch management and team oversight'],
            ['Visa Manager', 'visa-manager', 'Visa operations management and stage approvals'],
            ['Visa Consultant', 'visa-consultant', 'Application management and customer handling'],
            ['Processing Staff', 'processing-staff', 'Document verification and embassy submission processing'],
            ['Accounts', 'accounts', 'Payment tracking, invoicing, supplier costs, and refunds'],
            ['Customer Service', 'customer-service', 'Customer communications and general tracking'],
            ['Data Entry', 'data-entry', 'Application and customer data entry'],
            ['Read Only', 'read-only', 'Auditor view without editing permissions'],
            ['Customer', 'customer', 'Customer portal self-service tracking and document upload'],
        ];

        $stmt = $pdo->prepare("{$ins} roles (name, slug, description) VALUES (?, ?, ?)");
        foreach ($roles as $role) {
            $stmt->execute($role);
        }

        // 2. Branches
        $branches = [
            ['Dubai Head Office', 'DXB-01', 'United Arab Emirates', 'Dubai', 'Dubai, United Arab Emirates', '0585909349', 'mstravelu@gmail.com'],
            ['London Branch', 'LON-01', 'United Kingdom', 'London', '125 Kingsway, Holborn', '+44 20 7946 0991', 'london@mstravelhub.com'],
            ['New York Branch', 'NYC-01', 'United States', 'New York', '450 Lexington Ave, Suite 2200', '+1 212 555 0199', 'ny@mstravelhub.com'],
            ['Riyadh Branch', 'RUH-01', 'Saudi Arabia', 'Riyadh', 'King Fahd Road, Al Olaya', '+966 11 445 6789', 'riyadh@mstravelhub.com'],
        ];

        $stmt = $pdo->prepare("{$ins} branches (name, code, country, city, address, phone, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($branches as $branch) {
            $stmt->execute($branch);
        }

        // 3. Users (Super Admin)
        $admin123Hash = password_hash('admin123', PASSWORD_DEFAULT);

        $users = [
            [1, 1, 'Super Admin', 'admin@system.com', $admin123Hash, '+971 50 111 2234', 'System Administrator', 'Management'],
        ];

        $stmt = $pdo->prepare("{$ins} users (role_id, branch_id, name, email, password_hash, phone, designation, department) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($users as $user) {
            $stmt->execute($user);
        }

        // 4. Countries
        $countries = [
            ['United Arab Emirates', 'AE', '🇦🇪', 'AED', 'Middle East', 'GDRFA / ICP Visa Processing Unit', 'Comprehensive visa services available'],
            ['United Kingdom', 'GB', '🇬🇧', 'GBP', 'Europe', 'UK Visas and Immigration (UKVI) & VFS', 'Priority & Standard processing available'],
            ['United States', 'US', '🇺🇸', 'USD', 'North America', 'US Embassy Consular Section & DS-160', 'Interview scheduling & document preparation'],
            ['France (Schengen)', 'FR', '🇫🇷', 'EUR', 'Europe', 'Consulate General of France & TLScontact', 'Biometrics required for Schengen area'],
            ['Saudi Arabia', 'SA', '🇸🇦', 'SAR', 'Middle East', 'Ministry of Foreign Affairs (MOFA) & Enjaz', 'Tourist, Umrah and Business e-visas'],
            ['Canada', 'CA', '🇨🇦', 'CAD', 'North America', 'IRCC Immigration, Refugees and Citizenship', 'Visitor, Study & Work permit processing'],
            ['Singapore', 'SG', '🇸🇬', 'SGD', 'Asia', 'Immigration & Checkpoints Authority (ICA)', 'eVisa authorized submission channel'],
            ['Turkey', 'TR', '🇹🇷', 'USD', 'Europe/Asia', 'Republic of Turkey e-Visa & Gateway VFS', 'Sticker & e-Visa options'],
            ['Australia', 'AU', '🇦🇺', 'AUD', 'Oceania', 'Department of Home Affairs (ImmiAccount)', 'Subclass 600 Tourist & Business'],
            ['Qatar', 'QA', '🇶🇦', 'QAR', 'Middle East', 'Ministry of Interior (MOI) & Hayya Portal', 'Tourist, Transit & GCC resident visas'],
        ];

        $stmt = $pdo->prepare("{$ins} countries (name, iso_code, flag_emoji, currency, region, embassy_info, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($countries as $c) {
            $stmt->execute($c);
        }

        // 5. Visa Categories
        $categories = [
            ['Tourist Visa', 'tourist', 'Leisure, tourism, sightseeing and short visits', 'fa-umbrella-beach'],
            ['Business Visa', 'business', 'Commercial meetings, conferences, exhibitions and negotiations', 'fa-briefcase'],
            ['Employment Visa', 'employment', 'Work permits, residency and corporate sponsorships', 'fa-id-card'],
            ['Golden Visa / Investor', 'golden-investor', 'Long-term residency, property owners, investors and high-achievers', 'fa-gem'],
            ['Family Residence Visa', 'family', 'Spousal, child and dependent residency sponsorship', 'fa-users'],
            ['Student Visa', 'student', 'Higher education, university and vocational study permits', 'fa-graduation-cap'],
            ['Umrah & Religious Visa', 'religious', 'Pilgrimage, Umrah and spiritual travel permits', 'fa-mosque'],
            ['Transit Visa', 'transit', 'Short airport transit and stopover entry permits', 'fa-plane-departure'],
        ];

        $stmt = $pdo->prepare("{$ins} visa_categories (name, slug, description, icon) VALUES (?, ?, ?, ?)");
        foreach ($categories as $cat) {
            $stmt->execute($cat);
        }

        // 6. Visa Services
        $services = [
            // UAE Services
            [1, 1, 'UAE 60-Day Tourist Visa (Single Entry)', 'uae-60d-tourist-single', '60 Days', '60 Days', '60 Days from issue', 'Single Entry', 'Normal', 3, 6, 0, 100, 110.00, 40.00, 5.00, 185.00, 'Non-refundable once submitted to ICP/GDRFA'],
            [1, 1, 'UAE 60-Day Tourist Visa (Multiple Entry)', 'uae-60d-tourist-multi', '60 Days', '60 Days', '60 Days from issue', 'Multiple Entry', 'Express', 2, 6, 0, 100, 180.00, 55.00, 5.00, 290.00, 'Non-refundable once processed'],
            [1, 3, 'UAE 2-Year Employment Visa (Mainland)', 'uae-2y-employment-mainland', '2 Years', '2 Years', '2 Years Renewable', 'Multiple Entry', 'Normal', 14, 6, 18, 65, 850.00, 300.00, 5.00, 1450.00, 'Standard MOHRE and GDRFA cancellation terms'],
            [1, 4, 'UAE 10-Year Golden Visa (Executive / Investor)', 'uae-10y-golden-visa', '10 Years', '10 Years', '10 Years Renewable', 'Multiple Entry', 'Express', 10, 6, 21, 80, 1400.00, 650.00, 5.00, 2600.00, 'Official government screening fee non-refundable'],
            
            // UK Services
            [2, 1, 'UK Standard Visitor Visa (6 Months)', 'uk-standard-visitor-6m', '6 Months', '180 Days', '6 Months', 'Multiple Entry', 'Normal', 15, 6, 0, 100, 140.00, 120.00, 0.00, 320.00, 'UKVI fee non-refundable'],
            [2, 2, 'UK Business Visitor Visa (6 Months)', 'uk-business-visitor-6m', '6 Months', '180 Days', '6 Months', 'Multiple Entry', 'Express', 7, 6, 18, 100, 140.00, 180.00, 0.00, 390.00, 'UKVI fee non-refundable'],
            
            // US Services
            [3, 1, 'US B1/B2 Visitor & Business Visa (10 Years)', 'us-b1-b2-10y', '10 Years', '180 Days/visit', '10 Years', 'Multiple Entry', 'Normal', 30, 6, 0, 100, 185.00, 150.00, 0.00, 385.00, 'MRV fee non-refundable'],
            
            // France Services
            [4, 1, 'France / Schengen Short Stay Tourist Visa (90 Days)', 'france-schengen-tourist-90d', '90 Days', '90 Days', 'Up to 1 Year', 'Multiple Entry', 'Normal', 15, 6, 0, 100, 95.00, 110.00, 0.00, 245.00, 'Embassy & TLS fees non-refundable'],
            
            // Saudi Arabia Services
            [5, 1, 'Saudi Arabia 1-Year Multiple Entry Tourist / Umrah eVisa', 'ksa-1y-tourist-multi-evisa', '1 Year', '90 Days/visit', '1 Year', 'Multiple Entry', 'Express', 1, 6, 18, 100, 120.00, 45.00, 15.00, 195.00, 'Includes mandatory medical insurance'],
            
            // Canada Services
            [6, 1, 'Canada Temporary Resident Visa (Visitor Visa)', 'canada-trv-visitor', 'Up to 10 Years', '180 Days/visit', 'Passport Validity', 'Multiple Entry', 'Normal', 25, 6, 0, 100, 110.00, 140.00, 0.00, 290.00, 'Biometrics & IRCC fee non-refundable'],
        ];

        $stmt = $pdo->prepare("{$ins} visa_services (
            country_id, category_id, name, slug, duration, max_stay, validity, 
            entry_type, processing_type, estimated_days, passport_validity_rule_months, 
            min_age, max_age, supplier_cost, service_fee, tax_rate, selling_price, cancellation_policy
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($services as $srv) {
            $stmt->execute($srv);
        }

        // 7. Document Types
        $docTypes = [
            ['Passport Bio Page (Coloured Copy)', 'PASSPORT_BIO', 'High resolution colored scan of passport first and last page with 6+ months validity', 'Identity', 1],
            ['Additional Passport Pages / Old Visas', 'PASSPORT_PAGES', 'Previous travel stamps, visas, and relevant travel history pages', 'Identity', 0],
            ['Passport Size Photograph (White Background)', 'PHOTO_WHITE_BG', 'Recent passport-sized photograph (35x45mm or 2x2in) with neutral expression', 'Identity', 0],
            ['National ID / Emirates ID (Front & Back)', 'NATIONAL_ID', 'Government issued national identity card copy or UAE Emirates ID', 'Identity', 1],
            ['Current Residence Permit / Visa Copy', 'RESIDENCE_PERMIT', 'Valid residency visa stamp or digital residence card of current country', 'Identity', 1],
            ['Bank Statement (Last 3-6 Months)', 'BANK_STATEMENT', 'Original stamped bank account statement showing adequate proof of funds', 'Financial', 0],
            ['Employment Letter / Salary Certificate', 'EMPLOYMENT_LETTER', 'Official letterhead statement from employer detailing position, salary, and date of joining', 'Employment', 0],
            ['No Objection Certificate (NOC)', 'NOC_LETTER', 'Company NOC granting leave of absence and confirming job continuity', 'Employment', 0],
            ['Job Offer Letter / Labor Contract', 'JOB_OFFER_CONTRACT', 'Formal employment contract signed by sponsor/employer and applicant', 'Employment', 0],
            ['Flight Itinerary / Roundtrip Booking', 'FLIGHT_TICKET', 'Confirmed or reserved return flight reservation matching travel dates', 'Travel', 0],
            ['Hotel Accommodation Booking', 'HOTEL_BOOKING', 'Confirmed hotel reservation or voucher covering the entire duration of stay', 'Travel', 0],
            ['Travel & Medical Insurance Certificate', 'TRAVEL_INSURANCE', 'Valid overseas travel medical insurance policy with minimum EUR 30,000 / USD 50,000 coverage', 'Insurance', 1],
            ['Medical Fitness Certificate', 'MEDICAL_FITNESS', 'Authorized diagnostic center medical fitness test report (Blood & X-Ray)', 'Medical', 1],
            ['Police Clearance Certificate (PCC)', 'POLICE_CLEARANCE', 'Official criminal record check / PCC from issuing country or home nation', 'Legal', 0],
            ['Company Trade License Copy', 'TRADE_LICENSE', 'Sponsoring entity or business owner trade license copy', 'Corporate', 1],
        ];

        $stmt = $pdo->prepare("{$ins} document_types (name, code, description, category, requires_expiry) VALUES (?, ?, ?, ?, ?)");
        foreach ($docTypes as $dt) {
            $stmt->execute($dt);
        }

        // 8. Visa Requirements mapping
        $requirements = [
            // UAE 60-Day Tourist Visa (service_id: 1)
            [1, 1, 1, 'Minimum 6 months validity', 'Clear color scan of passport bio page'],
            [1, 3, 1, 'White background, matte finish', 'Recent photo taken within 3 months'],
            [1, 4, 0, 'For UAE / GCC residents', 'Front and back clear copy'],
            [1, 10, 0, 'Return flight ticket', 'Recommended for fast approval'],
            [1, 11, 0, 'Hotel or host address', 'Hotel confirmation voucher'],

            // UAE 2-Year Employment (service_id: 3)
            [3, 1, 1, 'Valid passport with at least 2 blank pages', 'Must be high resolution'],
            [3, 3, 1, 'Passport photo white background', 'High resolution image'],
            [3, 9, 1, 'Official MOHRE offer letter', 'Signed by employee and employer'],
            [3, 13, 1, 'UAE MOHAP/DHA medical test', 'Required after entry permit issued'],
            [3, 4, 1, 'Emirates ID application form', 'Biometrics capture document'],
            [3, 15, 1, 'Sponsor company trade license', 'Must be currently active'],

            // UK Standard Visitor (service_id: 5)
            [5, 1, 1, 'Valid passport', 'Clear copy of all pages with stamps'],
            [5, 3, 1, 'UK format photo', '35mm x 45mm'],
            [5, 6, 1, '6 months bank statements', 'Must be stamped by bank with closing balance > GBP 3,000'],
            [5, 7, 1, 'Employment letter / Pay slips (3 months)', 'On company letterhead with HR contact'],
            [5, 8, 1, 'No Objection Certificate', 'Confirming approved annual leave'],
            [5, 10, 1, 'Flight travel itinerary', 'Proposed travel schedule'],
            [5, 11, 1, 'Accommodation proof', 'Hotel or invitation letter with host passport'],

            // France Schengen (service_id: 8)
            [8, 1, 1, 'Passport issued within last 10 years', 'Valid at least 3 months after departure'],
            [8, 3, 1, 'ICAO standard photo', 'White background'],
            [8, 6, 1, 'Last 3-6 months bank statement', 'Demonstrating minimum daily allowance'],
            [8, 7, 1, 'Employment certificate', 'Position and salary breakdown'],
            [8, 10, 1, 'Round-trip flight booking', 'Confirmed booking'],
            [8, 11, 1, 'Hotel voucher covering all Schengen nights', 'Confirmed booking'],
            [8, 12, 1, 'Schengen travel insurance certificate', 'EUR 30,000 coverage including repatriation'],

            // Saudi Arabia 1-Year (service_id: 9)
            [9, 1, 1, 'Passport bio page', 'Clear color copy'],
            [9, 3, 1, 'White background photo', 'Clear facial photograph'],
            [9, 5, 1, 'Valid GCC residency or US/UK/Schengen visa copy', 'For instant eligibility check'],
        ];

        $stmt = $pdo->prepare("{$ins} visa_requirements (service_id, document_type_id, is_mandatory, condition_notes, instructions) VALUES (?, ?, ?, ?, ?)");
        foreach ($requirements as $req) {
            $stmt->execute($req);
        }

        // 9. Suppliers
        $suppliers = [
            ['SUP-001', 'Emirates Visa Clearing LLC', 'Kareem Mansoor', '+971 4 221 4455', '+971 52 998 1122', 'processing@emiratesclearing.ae', 'United Arab Emirates', 'Deira, Dubai', 'Net 15 Days', 'Emirates NBD: AE32 0260 0012 3456 7890', 'Authorized government visa aggregator'],
            ['SUP-002', 'VFS Global Express Partner', 'Jonathan Reynolds', '+44 20 8900 1200', '+44 7911 123456', 'partner@vfs-express.co.uk', 'United Kingdom', 'Canary Wharf, London', 'Weekly Settlement', 'Barclays Bank: 20-00-00 12345678', 'Official VFS appointment & consular agent'],
            ['SUP-003', 'Gulf MOFA & Attestation Services', 'Sultan Al-Otaibi', '+966 11 200 3344', '+966 50 111 9988', 'contact@gulfattestation.com', 'Saudi Arabia', 'King Fahd Rd, Riyadh', 'Prepaid Balance', 'Al Rajhi Bank: SA12 8000 0123 4567 8901', 'Enjaz and Umrah visa processing partner'],
        ];

        $stmt = $pdo->prepare("{$ins} suppliers (supplier_code, company_name, contact_person, mobile, whatsapp, email, country, address, payment_terms, bank_details, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($suppliers as $sup) {
            $stmt->execute($sup);
        }

        // 10. System Settings
        $settings = [
            ['company_name', 'MS Travel Hub Global Visa Services', 'Company', 'Registered global business name'],
            ['company_tagline', 'Staff Visa Tracking & Global Management Portal', 'Company', 'Portal branding subtitle'],
            ['company_email', 'mstravelu@gmail.com', 'Company', 'Primary operational email'],
            ['company_phone', '0585909349', 'Company', 'Main contact telephone'],
            ['company_address', 'Dubai, United Arab Emirates', 'Company', 'Headquarters address'],
            ['company_website', 'https://mshorizonuae.com', 'Company', 'Official public portal domain'],
            ['base_currency', 'USD', 'Finance', 'Default currency code'],
            ['currency_symbol', '$', 'Finance', 'Default currency display symbol'],
            ['expiry_alert_days', '90,60,30,15,7', 'Alerts', 'Comma separated days for document expiry warnings'],
            ['stuck_stage_threshold_days', '5', 'Operations', 'Days after which application in same stage triggers bottleneck alert'],
            ['customer_number_prefix', 'MSC-', 'Numbering', 'Prefix for customer identifiers'],
            ['application_number_prefix', 'MSV-', 'Numbering', 'Prefix for visa application reference numbers'],
            ['receipt_number_prefix', 'RCP-', 'Numbering', 'Prefix for payment receipts'],
        ];

        $stmt = $pdo->prepare("{$ins} system_settings (setting_key, setting_value, setting_group, description) VALUES (?, ?, ?, ?)");
        foreach ($settings as $st) {
            $stmt->execute($st);
        }

        // 25. Sample Recruitment Jobs
        $jobs = [
            ['Senior Hospitality Manager', 'senior-hospitality-manager-dubai', 'Hospitality', 'Dubai, UAE', 1, 8000.00, 12000.00, 'AED', '8 Hours/Day', 5, '3+ Years', 'Free Accommodation, Medical Insurance, Annual Flight Ticket, Duty Meals', 'Leading 5-Star Hotel Group in Dubai is seeking an experienced Senior Hospitality Manager to oversee guest relations and front office operations.', 'Must have minimum 3 years experience in luxury hotel management. Fluent English required.'],
            ['Civil Construction Supervisor', 'civil-construction-supervisor-riyadh', 'Engineering', 'Riyadh, Saudi Arabia', 5, 9000.00, 14000.00, 'SAR', '8 Hours/Day', 10, '4+ Years', 'Furnished Housing, Transport Allowance, Comprehensive Medical, Paid Leave', 'Top tier construction firm in Riyadh looking for qualified Civil Supervisors for commercial tower projects.', 'Bachelor degree in Civil Engineering or Diploma with 4+ years site supervision experience.'],
            ['Executive Chef & Culinary Lead', 'executive-chef-dubai', 'Hospitality', 'Dubai, UAE', 1, 7500.00, 11000.00, 'AED', '9 Hours/Day', 3, '5+ Years', 'Free Accommodation, Food Allowance, Flight Ticket, Visa Sponsorship', 'Premier fine dining restaurant chain hiring Executive Chefs specialized in International and Middle Eastern cuisine.', 'Strong background in kitchen management, HACCP standards, and menu engineering.'],
            ['Customer Success Executive', 'customer-success-executive-london', 'Customer Service', 'London, UK', 2, 2200.00, 3000.00, 'GBP', '8 Hours/Day', 4, '2+ Years', 'UK Visa Sponsorship, Health Coverage, Paid Annual Leave, Bonus Scheme', 'International Travel and Visa Services firm recruiting Customer Success Executives for our London Holborn office.', 'Excellent English communication skills, customer service passion, and proficiency in CRM tools.'],
            ['Heavy Vehicle Driver', 'heavy-vehicle-driver-abudhabi', 'Logistics', 'Abu Dhabi, UAE', 1, 3500.00, 5000.00, 'AED', '8 Hours/Day', 12, '2+ Years', 'Free Accommodation, Overtime Pay, Insurance, Driving License Transfer', 'Government contractor recruiting experienced Heavy Vehicle Drivers holding valid UAE License #6/#8.', 'Clean driving record and minimum 2 years GCC heavy driving experience.']
        ];

        $stmtJob = $pdo->prepare("{$ins} jobs (
            job_title, slug, category, location, country_id, salary_min, salary_max, currency, 
            duty_hours, vacancies, experience_required, benefits, description, requirements, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PUBLISHED')");

        foreach ($jobs as $j) {
            $stmtJob->execute($j);
        }


        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            try { $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;"); } catch (\Throwable $e) {}
        }
    }
}
