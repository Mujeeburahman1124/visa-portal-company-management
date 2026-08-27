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
            ['Dubai Head Office', 'DXB-01', 'United Arab Emirates', 'Dubai', 'Business Bay, Tower B, Level 14', '+971 4 388 9900', 'dubai@mstravelhub.com'],
            ['London Branch', 'LON-01', 'United Kingdom', 'London', '125 Kingsway, Holborn', '+44 20 7946 0991', 'london@mstravelhub.com'],
            ['New York Branch', 'NYC-01', 'United States', 'New York', '450 Lexington Ave, Suite 2200', '+1 212 555 0199', 'ny@mstravelhub.com'],
            ['Riyadh Branch', 'RUH-01', 'Saudi Arabia', 'Riyadh', 'King Fahd Road, Al Olaya', '+966 11 445 6789', 'riyadh@mstravelhub.com'],
        ];

        $stmt = $pdo->prepare("{$ins} branches (name, code, country, city, address, phone, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($branches as $branch) {
            $stmt->execute($branch);
        }

        // 3. Users (Staff)
        $defaultPasswordHash = password_hash('password123', PASSWORD_DEFAULT);
        $adminPasswordHash = password_hash('admin123', PASSWORD_DEFAULT);

        $users = [
            [1, 1, 'Tariq Al-Mansoor', 'admin@visatrack.com', $adminPasswordHash, '+971 50 111 2233', 'Director of Visa Operations', 'Management'],
            [2, 1, 'Sarah Jenkins', 'manager@visatrack.com', $defaultPasswordHash, '+971 50 222 3344', 'Senior Visa Operations Manager', 'Visa Department'],
            [3, 1, 'Alexander Chen', 'branch.manager@visatrack.com', $defaultPasswordHash, '+44 7700 900123', 'London Branch Manager', 'Branch Management'],
            [4, 1, 'Fatima Al-Zaabi', 'officer@visatrack.com', $defaultPasswordHash, '+971 50 333 4455', 'Senior Visa Officer', 'Visa Department'],
            [5, 1, 'Marcus Vance', 'staff@visatrack.com', $defaultPasswordHash, '+971 50 444 5566', 'Processing Specialist', 'Operations'],
            [6, 1, 'Priya Sharma', 'accounts@visatrack.com', $defaultPasswordHash, '+971 50 555 6677', 'Senior Accounts Officer', 'Finance'],
            [7, 1, 'Elena Rostova', 'support@visatrack.com', $defaultPasswordHash, '+971 50 666 7788', 'Customer Success Executive', 'Customer Support'],
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

        // 10. Customers (Realistic Sample Data)
        $customers = [
            ['MSC-000001', 'Rahul', 'Dev', 'Sharma', 'Rahul Dev Sharma', 'Male', '1990-05-14', 'India', 'New Delhi', 'Married', 'Senior Software Architect', '+971 55 123 4567', '+971 55 123 4567', 'rahul.sharma@cloudtech.ae', $defaultPasswordHash, 'United Arab Emirates', 'Apartment 1402, Marina Crown Tower, Dubai Marina, Dubai', 1, 'VIP corporate client sponsored by CloudTech Innovations LLC'],
            ['MSC-000002', 'Aisha', 'Bint', 'Al-Kindi', 'Aisha Al-Kindi', 'Female', '1994-08-22', 'Jordan', 'Amman', 'Single', 'Investment Banker', '+971 50 987 6543', '+971 50 987 6543', 'aisha.alkindi@gulfcapital.com', $defaultPasswordHash, 'United Arab Emirates', 'Villa 45, Meadows 2, Emirates Living, Dubai', 2, 'Frequent traveler applying for UK and Schengen visas'],
            ['MSC-000003', 'David', 'James', 'Miller', 'David James Miller', 'Male', '1985-11-03', 'United Kingdom', 'Manchester', 'Married', 'Managing Director', '+971 52 444 8899', '+971 52 444 8899', 'david.miller@apexglobal.co.uk', $defaultPasswordHash, 'United Arab Emirates', 'Downtown Dubai, Boulevard Point, Unit 2804', 1, 'UAE 10-Year Golden Visa application for family and self'],
            ['MSC-000004', 'Mariam', 'Zahid', 'Khan', 'Mariam Zahid Khan', 'Female', '1998-03-19', 'Pakistan', 'Lahore', 'Single', 'Biomedical Researcher', '+971 58 667 8901', '+971 58 667 8901', 'mariam.khan@biotechlab.org', $defaultPasswordHash, 'United Arab Emirates', 'Al Nahda 2, Dubai', 4, 'France Schengen Short-stay research conference application'],
            ['MSC-000005', 'Carlos', 'Eduardo', 'Santos', 'Carlos Eduardo Santos', 'Male', '1992-07-30', 'Brazil', 'Sao Paulo', 'Married', 'Aviation Engineer', '+971 54 332 1100', '+971 54 332 1100', 'carlos.santos@emirateseng.ae', $defaultPasswordHash, 'United Arab Emirates', 'Al Barsha 1, Dubai', 2, 'US B1/B2 Visa for training program in Seattle'],
            ['MSC-000006', 'Fatima', 'Noor', 'Hassan', 'Fatima Noor Hassan', 'Female', '1996-12-05', 'Egypt', 'Cairo', 'Single', 'Marketing Manager', '+971 56 778 9911', '+971 56 778 9911', 'fatima.hassan@brandpulse.me', $defaultPasswordHash, 'United Arab Emirates', 'Silicon Oasis, Dubai', 4, 'Saudi Arabia Multiple Entry Tourist Visa'],
            ['MSC-000007', 'Zhang', 'Wei', 'Li', 'Zhang Wei Li', 'Male', '1988-09-12', 'China', 'Shanghai', 'Married', 'Logistics Director', '+971 50 889 0022', '+971 50 889 0022', 'zhang.wei@sinotrans.cn', $defaultPasswordHash, 'United Arab Emirates', 'Jumeirah Lake Towers, Cluster X', 1, 'Canada TRV Business Visitor application'],
        ];

        $stmt = $pdo->prepare("{$ins} customers (
            customer_code, first_name, middle_name, last_name, full_name, gender, dob, 
            nationality, place_of_birth, marital_status, occupation, mobile, whatsapp, 
            email, password_hash, current_country, address, created_by, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($customers as $c) {
            $stmt->execute($c);
        }

        // 11. Customer Passports
        $passports = [
            [1, 'Z6543219', 'India', '2022-01-10', '2032-01-09', 'New Delhi', 1],
            [2, 'J9876543', 'Jordan', '2021-04-15', '2028-04-14', 'Amman', 1],
            [3, 'GB554433221', 'United Kingdom', '2020-08-20', '2030-08-19', 'London', 1],
            [4, 'PK8877665', 'Pakistan', '2023-02-12', '2028-02-11', 'Lahore', 1],
            [5, 'BR3322114', 'Brazil', '2022-09-05', '2032-09-04', 'Sao Paulo', 1],
            [6, 'EG7766554', 'Egypt', '2021-11-18', '2026-11-17', 'Cairo', 1],
            [7, 'E99887766', 'China', '2020-05-30', '2030-05-29', 'Shanghai', 1],
        ];

        $stmt = $pdo->prepare("{$ins} customer_passports (customer_id, passport_number, issuing_country, issue_date, expiry_date, place_of_issue, is_primary) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($passports as $p) {
            $stmt->execute($p);
        }

        // 12. Customer National IDs & Residences
        $nationalIds = [
            [1, '784-1990-1234567-1', 'Emirates ID', 'United Arab Emirates', '2022-02-01', '2026-12-31'],
            [2, '784-1994-9876543-2', 'Emirates ID', 'United Arab Emirates', '2021-05-01', '2027-05-01'],
            [3, '784-1985-5544332-3', 'Emirates ID', 'United Arab Emirates', '2020-09-01', '2030-09-01'],
            [4, '784-1998-8877665-4', 'Emirates ID', 'United Arab Emirates', '2023-03-01', '2025-03-01'],
            [5, '784-1992-3322114-5', 'Emirates ID', 'United Arab Emirates', '2022-10-01', '2026-10-01'],
            [6, '784-1996-7766554-6', 'Emirates ID', 'United Arab Emirates', '2021-12-01', '2026-12-01'],
            [7, '784-1988-9988776-7', 'Emirates ID', 'United Arab Emirates', '2020-06-01', '2026-06-01'],
        ];

        $stmt = $pdo->prepare("{$ins} customer_national_ids (customer_id, id_number, id_type, issuing_country, issue_date, expiry_date) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($nationalIds as $nid) {
            $stmt->execute($nid);
        }

        // 13. Realistic Applications with Complete Tracking Lifecycles
        $applications = [
            // MSV-2026-000001: UAE Employment Visa (In Process - Medical/Biometrics)
            [
                'MSV-2026-000001', 1, 3, 1, 4, 1, null,
                'Medical / Biometrics Processing', 'In Process', 'High',
                85, 'Awaiting DHA Medical fitness results and Emirates ID biometrics slot confirmation',
                'India', 'United Arab Emirates', 'Z6543219',
                '2026-09-01', null, '2026-08-01', '2026-08-25', null,
                1450.00, 50.00, 70.00, 1470.00, 1470.00, 0.00,
                850.00, 50.00, 570.00,
                'EC-MOHRE-98442', 'ICP-2026-88190', null, null, null, null,
                null, null, null, null,
                'Sponsor contract fully approved. Entry permit issued on 10 Aug. Medical appointment scheduled for 18 Aug at Salem Smart Center Al Quoz.',
                'Your entry permit is approved. Please attend your medical test appointment on 18 August at 10:00 AM.',
                0, 1
            ],
            // MSV-2026-000002: UK Standard Visitor (Documents Under Review)
            [
                'MSV-2026-000002', 2, 5, 1, 4, 2, null,
                'Documents Under Review', 'Documents Under Verification', 'Normal',
                92, 'All mandatory documents uploaded; final financial audit check in progress',
                'Jordan', 'United Arab Emirates', 'J9876543',
                '2026-09-15', '2026-09-28', '2026-08-10', '2026-08-30', null,
                320.00, 0.00, 0.00, 320.00, 320.00, 0.00,
                140.00, 20.00, 160.00,
                'VFS-LON-5512', null, null, null, null, null,
                null, null, null, null,
                '6 months bank statement verified with closing balance AED 85,000. Employer NOC signed.',
                'Your application documents are under final review before appointment booking.',
                0, 2
            ],
            // MSV-2026-000003: France Schengen (Submitted to Embassy / VFS)
            [
                'MSV-2026-000003', 4, 8, 1, 5, 2, null,
                'Submitted / In Process at Embassy', 'Submitted', 'Urgent',
                95, 'Application successfully submitted at TLScontact Dubai; awaiting passport collection',
                'Pakistan', 'United Arab Emirates', 'PK8877665',
                '2026-09-05', '2026-09-18', '2026-08-05', '2026-08-22', null,
                245.00, 0.00, 0.00, 245.00, 245.00, 0.00,
                95.00, 15.00, 135.00,
                'TLS-DXB-99120', 'FR-CONS-4402', null, null, null, null,
                null, null, null, null,
                'Biometrics captured at TLScontact on 11 Aug. Tracking via French Consulate portal.',
                'Your Schengen visa file is currently under review with the French Embassy.',
                0, 4
            ],
            // MSV-2026-000004: US B1/B2 (Appointment Scheduled)
            [
                'MSV-2026-000004', 5, 7, 1, 4, null, null,
                'Embassy Interview Scheduled', 'In Process', 'Normal',
                88, 'DS-160 filed, MRV fee paid, Consular interview scheduled at US Consulate Dubai',
                'Brazil', 'United Arab Emirates', 'BR3322114',
                '2026-10-10', '2026-10-25', '2026-07-28', '2026-09-10', null,
                385.00, 0.00, 0.00, 385.00, 385.00, 0.00,
                185.00, 25.00, 175.00,
                null, 'AA00DF4812', null, null, null, null,
                null, null, null, null,
                'DS-160 confirmation AA00DF4812. Interview confirmed for 28 August 2026 at 08:30 AM.',
                'Your consular interview is scheduled at US Consulate Dubai on 28 Aug 2026 at 08:30 AM.',
                0, 2
            ],
            // MSV-2026-000005: UAE 10-Year Golden Visa (Approved & Completed)
            [
                'MSV-2026-000005', 3, 4, 1, 1, 1, null,
                'Visa Issued & Completed', 'Approved', 'Normal',
                100, 'Application 100% complete; 10-Year Residency Visa and Emirates ID issued',
                'United Kingdom', 'United Arab Emirates', 'GB554433221',
                null, null, '2026-07-15', '2026-08-10', '2026-08-08',
                2600.00, 100.00, 125.00, 2625.00, 2625.00, 0.00,
                1400.00, 100.00, 1125.00,
                'GDRFA-GV-1099', 'ICP-GV-2026-9901', 'GV-201-2026-998811', '2026-08-08', '2036-08-07', 'uae_golden_visa_approved_sample.pdf',
                null, null, null, null,
                '10-Year Executive Golden Visa issued by GDRFA Dubai. Official electronic residency downloaded and shared with customer.',
                'Congratulations! Your 10-Year UAE Golden Visa has been approved and issued.',
                0, 1
            ],
            // MSV-2026-000006: Saudi Arabia 1-Year Multi-Entry (Returned / Documents Required)
            [
                'MSV-2026-000006', 6, 9, 1, 5, 3, null,
                'Returned / Modification Required', 'Action Required', 'Critical',
                45, 'Critical action required: Passport copy has glare on MRZ line; new scan requested from customer',
                'Egypt', 'United Arab Emirates', 'EG7766554',
                '2026-08-30', '2026-09-07', '2026-08-12', '2026-08-20', null,
                195.00, 0.00, 25.00, 220.00, 220.00, 0.00,
                120.00, 10.00, 90.00,
                'ENJAZ-SA-7788', null, null, null, null, null,
                'Uploaded passport page is blurred and cuts off bottom machine-readable zone (MRZ). Please re-upload high quality clear copy.',
                'Supplier Enjaz portal rejected initial passport scan due to low DPI.',
                'Passport bio page requires replacement with 300+ DPI color scan.', '2026-08-20',
                'Customer notified via system email. Awaiting updated passport scan.',
                'Please re-upload a clear color scan of your passport bio page without light glare.',
                0, 4
            ],
            // MSV-2026-000007: Canada TRV Visitor (Application Registered / Documents Pending)
            [
                'MSV-2026-000007', 7, 10, 1, 4, null, null,
                'Application Registered', 'Documents Pending', 'Normal',
                60, 'New application registered; awaiting customer upload of employment NOC and bank statement',
                'China', 'United Arab Emirates', 'E99887766',
                '2026-11-01', '2026-11-20', '2026-08-15', '2026-09-25', null,
                290.00, 0.00, 0.00, 290.00, 150.00, 140.00,
                110.00, 15.00, 165.00,
                null, null, null, null, null, null,
                null, null, null, null,
                'Initial deposit of USD 150 received. Checklist generated and sent to customer.',
                'Please upload your 6-month bank statements and employer NOC to proceed.',
                0, 1
            ],
        ];

        $stmt = $pdo->prepare("{$ins} applications (
            application_number, customer_id, visa_service_id, branch_id, assigned_staff_id, supplier_id, agent_id,
            current_stage, status, priority, calculated_health, health_reason, nationality, residence_country,
            passport_number, travel_date, return_date, application_date, expected_completion_date, actual_completion_date,
            selling_price, discount, tax_amount, total_amount, paid_amount, balance_amount,
            supplier_cost, other_expenses, gross_profit, supplier_reference, embassy_reference,
            visa_number, visa_issue_date, visa_expiry_date, visa_file, rejection_reason_customer, rejection_reason_internal,
            return_reason, return_deadline, internal_notes, customer_notes, is_archived, created_by
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?
        )");

        foreach ($applications as $app) {
            $stmt->execute($app);
        }

        // 14. Application Status & Stage History (Audit Trail)
        $history = [
            // App 1 History
            [1, 'Draft', 'Registered', 'Draft', 'Application Registered', 1, 'Application registered and verified with CloudTech Innovations sponsor.'],
            [1, 'Registered', 'Documents Submitted', 'Application Registered', 'Documents Collected', 4, 'Passport, photo, and employment contract received.'],
            [1, 'Documents Submitted', 'Documents Under Verification', 'Documents Collected', 'Documents Verified', 5, 'Documents verified against MOHRE requirements.'],
            [1, 'Documents Under Verification', 'Submitted', 'Documents Verified', 'Application Submitted', 4, 'Entry permit application submitted to ICP/GDRFA.'],
            [1, 'Submitted', 'In Process', 'Application Submitted', 'Medical / Biometrics Processing', 4, 'Entry permit issued; medical and Emirates ID biometrics scheduled.'],

            // App 5 History (Golden Visa - Completed)
            [5, 'Draft', 'Registered', 'Draft', 'Application Registered', 1, 'Executive Golden Visa nominated and registered.'],
            [5, 'Registered', 'Documents Submitted', 'Application Registered', 'Documents Collected', 1, 'High-salary bank statements, degree, and salary certificate uploaded.'],
            [5, 'Documents Submitted', 'Documents Under Verification', 'Documents Collected', 'Documents Verified', 1, 'Equivalency and financial thresholds verified.'],
            [5, 'Documents Under Verification', 'Submitted', 'Documents Verified', 'Application Submitted', 1, 'Nomination submitted to GDRFA Golden Visa Committee.'],
            [5, 'Submitted', 'In Process', 'Application Submitted', 'Medical / Biometrics Processing', 1, 'Golden Visa nomination approved. VIP Medical completed.'],
            [5, 'In Process', 'Approved', 'Medical / Biometrics Processing', 'Visa Issued & Completed', 1, '10-Year Golden Visa issued and verified on GDRFA portal.'],

            // App 6 History (Returned)
            [6, 'Draft', 'Registered', 'Draft', 'Application Registered', 4, 'Saudi tourist application created.'],
            [6, 'Registered', 'Documents Submitted', 'Application Registered', 'Documents Collected', 6, 'Customer uploaded documents.'],
            [6, 'Documents Submitted', 'Action Required', 'Documents Collected', 'Returned / Modification Required', 5, 'Passport scan rejected due to blurred MRZ code.'],
        ];

        $stmt = $pdo->prepare("{$ins} application_status_history (application_id, from_status, to_status, from_stage, to_stage, changed_by, comments) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($history as $h) {
            $stmt->execute($h);
        }

        // 15. Create Sample Physical Upload Documents
        $uploadDir = App::uploadPath();
        $sampleFiles = [
            'passport_rahul_sharma.pdf' => "Sample Passport Bio Page - Rahul Dev Sharma (Z6543219) - Verified",
            'employment_contract_rahul.pdf' => "Standard UAE Employment Contract - CloudTech Innovations LLC - Verified",
            'medical_appointment_slip_rahul.pdf' => "DHA Smart Salem Medical Appointment Slip - Date: 18-Aug-2026",
            'passport_aisha_alkindi.pdf' => "Passport Bio Page - Aisha Al-Kindi (J9876543) - Verified",
            'bank_statement_aisha.pdf' => "ADCB Bank Stamped 6-Month Statement - Closing Balance AED 85,000",
            'passport_david_miller.pdf' => "Passport Bio Page - David James Miller (GB554433221) - Verified",
            'uae_golden_visa_approved_sample.pdf' => "GOVERNMENT OF DUBAI - GDRFA\n10-YEAR GOLDEN RESIDENCY VISA\nVisa No: GV-201-2026-998811\nName: David James Miller\nValid: 2026-08-08 to 2036-08-07\nStatus: APPROVED",
            'passport_fatima_unclear.pdf' => "Passport Scan - Fatima Noor Hassan - Status: Blurred scan returned for re-upload",
        ];

        foreach ($sampleFiles as $filename => $content) {
            $path = $uploadDir . DIRECTORY_SEPARATOR . $filename;
            if (!file_exists($path)) {
                file_put_contents($path, "%PDF-1.4\n% Sample Document\n" . $content . "\n%%EOF");
            }
        }

        // 16. Documents Records
        $documents = [
            // App 1 Documents
            [1, 1, 1, 'Passport Bio Page', 'passport_rahul_sharma.pdf', 'passport_rahul_sharma.pdf', 102400, 'application/pdf', 1, '2032-01-09', 'VERIFIED', 'Staff', 4, 4, '2026-08-02 10:00:00', null, 0, 'Original scan verified with 6+ years validity'],
            [1, 1, 3, 'Passport Size Photograph', 'photo_rahul.jpg', 'photo_rahul.jpg', 45000, 'image/jpeg', 1, null, 'VERIFIED', 'Staff', 4, 4, '2026-08-02 10:05:00', null, 0, 'ICAO compliant photo approved'],
            [1, 1, 9, 'Signed Employment Contract', 'employment_contract_rahul.pdf', 'employment_contract_rahul.pdf', 150000, 'application/pdf', 1, null, 'VERIFIED', 'Staff', 4, 5, '2026-08-03 14:20:00', null, 0, 'MOHRE job offer with company stamp'],
            [1, 1, 13, 'Medical Test Appointment Request', 'medical_appointment_slip_rahul.pdf', 'medical_appointment_slip_rahul.pdf', 85000, 'application/pdf', 1, '2026-08-18', 'UNDER_REVIEW', 'Staff', 5, null, null, null, 0, 'Appointment booked for 18-Aug-2026'],

            // App 2 Documents
            [2, 2, 1, 'Passport Bio Page', 'passport_aisha_alkindi.pdf', 'passport_aisha_alkindi.pdf', 98000, 'application/pdf', 1, '2028-04-14', 'VERIFIED', 'Customer', null, 4, '2026-08-11 11:30:00', null, 0, 'Passport valid through April 2028'],
            [2, 2, 6, 'Bank Statement (6 Months)', 'bank_statement_aisha.pdf', 'bank_statement_aisha.pdf', 210000, 'application/pdf', 1, null, 'VERIFIED', 'Customer', null, 4, '2026-08-11 11:35:00', null, 0, 'Closing balance AED 85,000 confirmed'],
            [2, 2, 7, 'Employer Salary Certificate', null, null, 0, null, 1, null, 'MISSING', 'Staff', null, null, null, null, 0, 'Requested from customer'],

            // App 5 Documents (Golden Visa)
            [5, 3, 1, 'Passport Bio Page', 'passport_david_miller.pdf', 'passport_david_miller.pdf', 110000, 'application/pdf', 1, '2030-08-19', 'VERIFIED', 'Staff', 1, 1, '2026-07-16 09:00:00', null, 0, 'Verified'],
            [5, 3, 15, 'Company Trade License', 'trade_license_apex.pdf', 'trade_license_apex.pdf', 120000, 'application/pdf', 1, '2027-05-15', 'VERIFIED', 'Staff', 1, 1, '2026-07-16 09:15:00', null, 0, 'Verified'],

            // App 6 Documents (Returned)
            [6, 6, 1, 'Passport Bio Page (Unclear)', 'passport_fatima_unclear.pdf', 'passport_fatima_unclear.pdf', 64000, 'application/pdf', 1, '2026-11-17', 'REJECTED', 'Customer', null, 5, '2026-08-13 16:00:00', 'Passport scan is blurred and cuts off bottom MRZ machine-readable lines. Please re-upload clean 300 DPI scan.', 1, 'Replaced document required before resubmission.'],
        ];

        $stmt = $pdo->prepare("{$ins} documents (
            application_id, customer_id, document_type_id, document_title, file_path, file_name, file_size, 
            mime_type, version, expiry_date, status, uploaded_by_type, uploaded_by_id, 
            verified_by, verified_at, rejection_reason, replacement_requested, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($documents as $doc) {
            $stmt->execute($doc);
        }

        // 17. Tasks
        $tasks = [
            [1, 1, 'Track DHA Medical Fitness Result', 'Follow up on Salem smart center medical blood test and X-ray report for Rahul Sharma', 'Medical', 'High', 4, 1, '2026-08-17', '2026-08-19', 'In Progress', 'Result expected in 24 hours from test'],
            [1, 1, 'Book Emirates ID Biometrics Slot', 'Schedule biometrics appointment at ICP Al Barsha center once medical is clear', 'Biometrics', 'Normal', 5, 4, '2026-08-18', '2026-08-21', 'Pending', 'Awaiting medical pass status'],
            [2, 2, 'Request Salary Certificate from Aisha', 'Call client to request company signed and stamped salary certificate on official letterhead', 'Document Collection', 'Urgent', 4, 2, '2026-08-16', '2026-08-18', 'Pending', 'Needed before UKVI submission'],
            [3, 4, 'Monitor French Consulate Decision', 'Check TLScontact passport return status daily for Schengen visa issuance', 'Embassy Follow-up', 'High', 5, 4, '2026-08-16', '2026-08-22', 'In Progress', 'Consulate ref FR-CONS-4402'],
            [6, 6, 'Follow up with Fatima for Clear Passport Scan', 'Contact Fatima via WhatsApp & phone to assist with scanning passport without glare', 'Customer Follow-up', 'Critical', 5, 4, '2026-08-16', '2026-08-18', 'In Progress', 'Urgent travel date on 30-Aug'],
        ];

        $stmt = $pdo->prepare("{$ins} tasks (
            application_id, customer_id, task_title, description, task_type, priority, 
            assigned_to, created_by, start_date, due_date, status, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($tasks as $task) {
            $stmt->execute($task);
        }

        // 18. Appointments
        $appointments = [
            [1, 1, 'Medical Fitness Test', 'Smart Salem Center', 'Al Quoz Mall, Dubai', '2026-08-18', '10:00:00', 'MED-DHA-99881', 4, 'Confirmed', 'VIP Express Package test'],
            [3, 4, 'VFS / TLS Biometrics & Interview', 'TLScontact Visa Application Center', 'Oud Metha, Wafi Mall, Dubai', '2026-08-11', '09:30:00', 'TLS-DXB-99120', 5, 'Completed', 'Biometrics & passport submitted to French Consulate'],
            [4, 5, 'US Consular Visa Interview', 'US Consulate General Dubai', 'Al Seef Rd, Bur Dubai', '2026-08-28', '08:30:00', 'US-CONS-8841', 4, 'Scheduled', 'Carry DS-160, appointment letter, and original bank statements'],
        ];

        $stmt = $pdo->prepare("{$ins} appointments (
            application_id, customer_id, appointment_type, center_name, location_address, 
            appointment_date, appointment_time, reference_number, assigned_staff_id, status, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($appointments as $apt) {
            $stmt->execute($apt);
        }

        // 19. Payments & Receipts
        $payments = [
            ['RCP-2026-000001', 'INV-2026-000001', 1, 1, 1470.00, 'USD', '2026-08-01', 'Bank Transfer', 'TXN-ENBD-889192', 'Customer Payment', 'Completed', 6, 'Full settlement paid by CloudTech Innovations LLC'],
            ['RCP-2026-000002', 'INV-2026-000002', 2, 2, 320.00, 'USD', '2026-08-10', 'Card', 'AUTH-VISA-99120', 'Customer Payment', 'Completed', 6, 'Full visa package fee paid online'],
            ['RCP-2026-000003', 'INV-2026-000003', 3, 4, 245.00, 'USD', '2026-08-05', 'Card', 'AUTH-MC-55198', 'Customer Payment', 'Completed', 6, 'Schengen visa and processing fee paid'],
            ['RCP-2026-000004', 'INV-2026-000005', 5, 3, 2625.00, 'USD', '2026-07-15', 'Bank Transfer', 'TXN-HSBC-110099', 'Customer Payment', 'Completed', 6, '10-Year Golden Visa fee full settlement'],
            ['RCP-2026-000005', 'INV-2026-000006', 6, 6, 220.00, 'USD', '2026-08-12', 'Card', 'AUTH-VISA-33991', 'Customer Payment', 'Completed', 6, 'Saudi tourist fee settled'],
            ['RCP-2026-000006', 'INV-2026-000007', 7, 7, 150.00, 'USD', '2026-08-15', 'Cash', 'CASH-REC-0019', 'Customer Payment', 'Completed', 6, '50% advance deposit received'],
        ];

        $stmt = $pdo->prepare("{$ins} payments (
            payment_number, invoice_number, application_id, customer_id, amount, currency, 
            payment_date, payment_method, transaction_reference, payment_type, status, received_by, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($payments as $pay) {
            $stmt->execute($pay);
        }

        // 20. Communications Log
        $communications = [
            [1, 1, 'WhatsApp', 'Outbound', 'Medical Fitness Appointment Reminder', 'Hello Rahul, your medical appointment is confirmed for tomorrow, 18 August at 10:00 AM at Smart Salem Al Quoz. Please bring your original passport and entry permit copy.', 'Rahul Dev Sharma', 4, '2026-08-17 09:30:00'],
            [2, 2, 'Phone Call', 'Outbound', 'Salary Certificate Follow-up', 'Called client regarding missing salary certificate. Client confirmed her HR department will issue the stamped letter by tomorrow afternoon.', 'Aisha Al-Kindi', 4, '2026-08-16 14:00:00'],
            [6, 6, 'WhatsApp', 'Outbound', 'Urgent Passport Scan Request', 'Dear Fatima, your passport copy was rejected by the visa system due to glare on the bottom machine-readable lines. Please provide a clear flat scan so we can resubmit immediately.', 'Fatima Noor Hassan', 5, '2026-08-13 16:30:00'],
            [6, 6, 'Phone Call', 'Inbound', 'Client called regarding resubmission', 'Client acknowledged the notification and stated she is visiting an internet cafe now to get a high resolution flatbed scan.', 'Fatima Noor Hassan', 5, '2026-08-17 11:15:00'],
        ];

        $stmt = $pdo->prepare("{$ins} communications (
            application_id, customer_id, channel, direction, subject, message, 
            contact_person, staff_id, recorded_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($communications as $comm) {
            $stmt->execute($comm);
        }

        // 21. System Notifications
        $notifications = [
            [4, null, 'Staff', 'Action Required: Passport Scan Rejected', 'Application MSV-2026-000006 (Fatima Hassan) requires updated passport scan.', '/applications/show?id=6', 'Document Alert', 'danger'],
            [4, null, 'Staff', 'Upcoming Medical Appointment Tomorrow', 'Rahul Sharma (MSV-2026-000001) has a scheduled DHA Medical test on 18-Aug-2026.', '/applications/show?id=1', 'Appointment', 'info'],
            [1, null, 'Staff', '10-Year Golden Visa Issued Successfully', 'Application MSV-2026-000005 (David Miller) is completed and approved.', '/applications/show?id=5', 'Visa Approved', 'success'],
            [null, 6, 'Customer', 'Document Re-upload Required', 'Your passport scan was returned for re-upload. Please provide a clear 300 DPI copy.', '/portal/documents', 'Document Request', 'warning'],
            [null, 1, 'Customer', 'Appointment Scheduled', 'Your UAE Medical Fitness Test has been booked for 18 August 2026 at 10:00 AM.', '/portal/appointments', 'Appointment', 'info'],
        ];

        $stmt = $pdo->prepare("{$ins} notifications (
            user_id, customer_id, recipient_type, title, message, link, notification_type, severity
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($notifications as $n) {
            $stmt->execute($n);
        }

        // 22. Email Templates
        $templates = [
            ['APP_REGISTERED', 'Application Registered Confirmation', 'Visa Application Registered: {{application_number}} - {{destination_country}}', '<p>Dear {{customer_name}},</p><p>Your visa application <strong>{{application_number}}</strong> for <strong>{{visa_service}}</strong> has been successfully registered. You can track your progress online in real-time.</p><p>Next Step: {{next_action}}</p>', '{{customer_name}}, {{application_number}}, {{visa_service}}, {{destination_country}}, {{next_action}}'],
            ['DOC_REQUIRED', 'Document Request Notice', 'Action Required: Additional Document Needed for {{application_number}}', '<p>Dear {{customer_name}},</p><p>To proceed with your application <strong>{{application_number}}</strong>, we require the following document: <strong>{{document_name}}</strong>.</p><p>Instructions: {{instructions}}</p><p>Please upload it via your customer portal before {{due_date}}.</p>', '{{customer_name}}, {{application_number}}, {{document_name}}, {{instructions}}, {{due_date}}'],
            ['DOC_REJECTED', 'Document Replacement Requested', 'Urgent: Document Replacement Required for {{application_number}}', '<p>Dear {{customer_name}},</p><p>Your uploaded document <strong>{{document_name}}</strong> for application <strong>{{application_number}}</strong> could not be accepted.</p><p><strong>Reason:</strong> {{rejection_reason}}</p><p>Please upload a clear replacement via your portal immediately.</p>', '{{customer_name}}, {{application_number}}, {{document_name}}, {{rejection_reason}}'],
            ['STAGE_UPDATED', 'Application Stage Progress Update', 'Status Update: Application {{application_number}} is now {{current_stage}}', '<p>Dear {{customer_name}},</p><p>Your visa application has progressed to stage: <strong>{{current_stage}}</strong>.</p><p>Next Action: {{next_action}}</p><p>Estimated Completion: {{expected_date}}</p>', '{{customer_name}}, {{application_number}}, {{current_stage}}, {{next_action}}, {{expected_date}}'],
            ['VISA_APPROVED', 'Visa Approval & Final Download', 'Congratulations! Your Visa {{visa_number}} is APPROVED', '<p>Dear {{customer_name}},</p><p>We are delighted to inform you that your visa for <strong>{{destination_country}}</strong> has been approved!</p><p>Visa Number: <strong>{{visa_number}}</strong><br>Validity: {{validity}}</p><p>You can now download and print your official visa document from your customer portal.</p>', '{{customer_name}}, {{destination_country}}, {{visa_number}}, {{validity}}'],
        ];

        $stmt = $pdo->prepare("{$ins} email_templates (template_key, title, subject, body_html, placeholders) VALUES (?, ?, ?, ?, ?)");
        foreach ($templates as $tmpl) {
            $stmt->execute($tmpl);
        }

        // 23. Immutable Audit Logs
        $auditLogs = [
            [1, null, 'Staff', 'CREATE', 'Applications', 1, 'Created new application MSV-2026-000001 for customer Rahul Sharma', '{"customer":"Rahul Sharma","service":"UAE 2-Year Employment","total":1470}', '127.0.0.1', 'Mozilla/5.0'],
            [4, null, 'Staff', 'STAGE_CHANGE', 'Applications', 1, 'Transitioned stage from Application Submitted to Medical / Biometrics Processing', '{"from_stage":"Application Submitted","to_stage":"Medical / Biometrics Processing"}', '127.0.0.1', 'Mozilla/5.0'],
            [4, null, 'Staff', 'VERIFY_DOC', 'Documents', 1, 'Verified Passport Bio Page for Rahul Sharma', '{"doc_id":1,"status":"VERIFIED"}', '127.0.0.1', 'Mozilla/5.0'],
            [6, null, 'Staff', 'PAYMENT_RECEIVED', 'Payments', 1, 'Recorded customer payment of $1,470.00 (Receipt RCP-2026-000001)', '{"amount":1470,"method":"Bank Transfer"}', '127.0.0.1', 'Mozilla/5.0'],
            [5, null, 'Staff', 'REJECT_DOC', 'Documents', 8, 'Rejected passport scan for Fatima Hassan with reason: Blurred scan', '{"doc_id":8,"reason":"Blurred scan"}', '127.0.0.1', 'Mozilla/5.0'],
            [1, null, 'Staff', 'APPROVE_VISA', 'Applications', 5, 'Recorded approval and issued 10-Year Golden Visa GV-201-2026-998811 for David Miller', '{"visa_number":"GV-201-2026-998811","validity":"10 Years"}', '127.0.0.1', 'Mozilla/5.0'],
        ];

        $stmt = $pdo->prepare("{$ins} activity_logs (
            user_id, customer_id, actor_type, action, module, record_id, description, details_json, ip_address, user_agent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($auditLogs as $log) {
            $stmt->execute($log);
        }

        // 24. System Settings
        $settings = [
            ['company_name', 'MS Travel Hub Global Visa Services', 'Company', 'Registered global business name'],
            ['company_tagline', 'Staff Visa Tracking & Global Management Portal', 'Company', 'Portal branding subtitle'],
            ['company_email', 'operations@mstravelhub.com', 'Company', 'Primary operational email'],
            ['company_phone', '+971 4 388 9900', 'Company', 'Main contact telephone'],
            ['company_address', 'Level 14, Business Bay Tower B, Dubai, UAE', 'Company', 'Headquarters address'],
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
    }
}
