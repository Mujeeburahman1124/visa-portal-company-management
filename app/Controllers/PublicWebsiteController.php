<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\App;
use App\Config\Database;
use App\Config\Env;
use App\Services\AuditService;
use App\Services\EmailService;
use App\Services\NotificationService;
use PDO;
use Exception;

class PublicWebsiteController
{
    /**
     * Public Landing Page / Homepage
     */
    public function home(): void
    {
        $pdo = Database::getConnection();

        // Active Countries
        $countries = $pdo->query("SELECT * FROM countries WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Active Visa Services (Public View — no cost/supplier exposure)
        $services = $pdo->query("SELECT vs.id, vs.name, vs.slug, vs.duration, vs.max_stay, vs.validity, vs.entry_type, 
                                        vs.processing_type, vs.estimated_days, vs.selling_price, vs.currency,
                                        c.name as country_name, c.flag_emoji, vc.name as category_name
                                 FROM visa_services vs
                                 JOIN countries c ON vs.country_id = c.id
                                 LEFT JOIN visa_categories vc ON vs.category_id = vc.id
                                 WHERE vs.is_active = 1
                                 ORDER BY vs.id DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Open Recruitment Jobs
        $jobs = [];
        try {
            $jobs = $pdo->query("SELECT j.*, c.name as country_name, c.flag_emoji 
                                 FROM jobs j 
                                 LEFT JOIN countries c ON j.country_id = c.id 
                                 WHERE j.status = 'PUBLISHED' 
                                 ORDER BY j.created_at DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {}

        // Global Statistics
        $stats = [
            'visas_processed' => '48,500+',
            'success_rate' => '99.4%',
            'countries_covered' => count($countries) ?: 50,
            'global_branches' => (int)$pdo->query("SELECT COUNT(*) FROM branches WHERE is_active = 1")->fetchColumn() ?: 4,
        ];

        require_once dirname(__DIR__) . '/Views/public/home.php';
    }

    /**
     * About Us Page
     */
    public function about(): void
    {
        $pdo = Database::getConnection();
        $branches = $pdo->query("SELECT * FROM branches WHERE is_active = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        require_once dirname(__DIR__) . '/Views/public/about.php';
    }

    /**
     * Visa Services Catalog
     */
    public function visaServices(): void
    {
        $pdo = Database::getConnection();

        $search = trim($_GET['search'] ?? '');
        $countryId = (int)($_GET['country_id'] ?? 0);
        $categoryId = (int)($_GET['category_id'] ?? 0);
        $entryType = trim($_GET['entry_type'] ?? '');

        $sql = "SELECT vs.id, vs.name, vs.slug, vs.duration, vs.max_stay, vs.validity, vs.entry_type, 
                       vs.processing_type, vs.estimated_days, vs.selling_price, vs.currency, vs.cancellation_policy,
                       c.name as country_name, c.flag_emoji, vc.name as category_name, vc.icon as category_icon
                FROM visa_services vs
                JOIN countries c ON vs.country_id = c.id
                LEFT JOIN visa_categories vc ON vs.category_id = vc.id
                WHERE vs.is_active = 1";

        $params = [];

        if ($search !== '') {
            $sql .= " AND (vs.name LIKE ? OR c.name LIKE ? OR vc.name LIKE ?)";
            $term = "%{$search}%";
            $params = [$term, $term, $term];
        }

        if ($countryId > 0) {
            $sql .= " AND vs.country_id = ?";
            $params[] = $countryId;
        }

        if ($categoryId > 0) {
            $sql .= " AND vs.category_id = ?";
            $params[] = $categoryId;
        }

        if ($entryType !== '') {
            $sql .= " AND vs.entry_type = ?";
            $params[] = $entryType;
        }

        $sql .= " ORDER BY c.name ASC, vs.selling_price ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $countries = $pdo->query("SELECT id, name, flag_emoji FROM countries WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $categories = $pdo->query("SELECT id, name, slug FROM visa_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        require_once dirname(__DIR__) . '/Views/public/visa_services.php';
    }

    /**
     * Visa Service Detail Page
     */
    public function visaServiceDetail(): void
    {
        $pdo = Database::getConnection();
        $id = (int)($_GET['id'] ?? 0);
        $slug = trim($_GET['slug'] ?? '');

        if ($id <= 0 && $slug === '') {
            redirect('/visa-services', 'Service not found.', 'warning');
        }

        $sql = "SELECT vs.*, c.name as country_name, c.flag_emoji, c.iso_code as country_code, c.embassy_info,
                       vc.name as category_name, vc.description as category_description
                FROM visa_services vs
                JOIN countries c ON vs.country_id = c.id
                LEFT JOIN visa_categories vc ON vs.category_id = vc.id
                WHERE " . ($id > 0 ? "vs.id = ?" : "vs.slug = ?") . " AND vs.is_active = 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id > 0 ? $id : $slug]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$service) {
            redirect('/visa-services', 'Visa service package not found or currently unavailable.', 'danger');
        }

        // Load document requirements for this visa service.
        // visa_requirements uses column 'service_id' (FK to visa_services.id).
        // is_mandatory lives on visa_requirements, NOT on document_types.
        // visa_requirements has no is_active column — filter by dt.is_active only.
        $docTypes = [];
        try {
            $docReqStmt = $pdo->prepare(
                "SELECT dt.name, dt.description, dt.category,
                        vr.is_mandatory,
                        COALESCE(vr.condition_notes, '') AS condition_notes,
                        COALESCE(vr.instructions, '')    AS instructions
                 FROM visa_requirements vr
                 JOIN document_types dt ON vr.document_type_id = dt.id
                 WHERE vr.service_id = ?
                   AND dt.is_active = 1
                 ORDER BY vr.is_mandatory DESC, dt.category ASC, dt.name ASC"
            );
            $docReqStmt->execute([$service['id']]);
            $docTypes = $docReqStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            // If visa_requirements is empty or has a schema issue, degrade gracefully
            $docTypes = [];
        }

        // Fallback: show all active document types (non-service-specific) if none configured
        if (empty($docTypes)) {
            try {
                $docTypes = $pdo->query(
                    "SELECT name, description, category,
                            0 AS is_mandatory,
                            '' AS condition_notes,
                            '' AS instructions
                     FROM document_types
                     WHERE is_active = 1
                     ORDER BY category ASC, name ASC"
                )->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable $e) {
                $docTypes = [];
            }
        }

        require_once dirname(__DIR__) . '/Views/public/visa_service_detail.php';
    }

    /**
     * Open Jobs & Recruitment Catalog
     */
    public function jobs(): void
    {
        $pdo = Database::getConnection();

        $search = trim($_GET['search'] ?? '');
        $category = trim($_GET['category'] ?? '');
        $countryId = (int)($_GET['country_id'] ?? 0);

        $sql = "SELECT j.*, c.name as country_name, c.flag_emoji 
                FROM jobs j 
                LEFT JOIN countries c ON j.country_id = c.id 
                WHERE j.status = 'PUBLISHED'";

        $params = [];

        if ($search !== '') {
            $sql .= " AND (j.job_title LIKE ? OR j.location LIKE ? OR j.description LIKE ?)";
            $term = "%{$search}%";
            $params = [$term, $term, $term];
        }

        if ($category !== '') {
            $sql .= " AND j.category = ?";
            $params[] = $category;
        }

        if ($countryId > 0) {
            $sql .= " AND j.country_id = ?";
            $params[] = $countryId;
        }

        $sql .= " ORDER BY j.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $countries = $pdo->query("SELECT id, name, flag_emoji FROM countries WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        require_once dirname(__DIR__) . '/Views/public/jobs.php';
    }

    /**
     * Job Opportunity Detail Page
     */
    public function jobDetail(): void
    {
        $pdo = Database::getConnection();
        $id = (int)($_GET['id'] ?? 0);
        $slug = trim($_GET['slug'] ?? '');

        if ($id <= 0 && $slug === '') {
            redirect('/jobs', 'Job posting not found.', 'warning');
        }

        $stmt = $pdo->prepare("SELECT j.*, c.name as country_name, c.flag_emoji 
                               FROM jobs j 
                               LEFT JOIN countries c ON j.country_id = c.id 
                               WHERE " . ($id > 0 ? "j.id = ?" : "j.slug = ?") . " AND j.status = 'PUBLISHED'");
        $stmt->execute([$id > 0 ? $id : $slug]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            redirect('/jobs', 'Job posting not found or application deadline has passed.', 'danger');
        }

        require_once dirname(__DIR__) . '/Views/public/job_detail.php';
    }

    /**
     * Job Application Submission (GET & POST)
     */
    public function applyJob(): void
    {
        $pdo = Database::getConnection();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF
            \App\Middleware\CsrfMiddleware::validate();

            // Honeypot Anti-Spam Check
            if (!empty($_POST['website_hp'])) {
                redirect('/jobs', 'Application received successfully.', 'success');
            }

            $jobId = (int)($_POST['job_id'] ?? 0);
            $fullName = trim($_POST['full_name'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $phone = trim($_POST['phone'] ?? '');
            $nationality = trim($_POST['nationality'] ?? 'General');
            $currentLocation = trim($_POST['current_location'] ?? '');
            $experienceYears = trim($_POST['experience_years'] ?? '');
            $coverLetter = trim($_POST['cover_letter'] ?? '');

            if (empty($fullName) || empty($email) || empty($phone) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                set_flash('Please fill in all mandatory fields with a valid email address.', 'danger');
                redirect($_SERVER['HTTP_REFERER'] ?? '/jobs');
            }

            // CV Upload Handling
            $cvPath = null;
            if (!empty($_FILES['cv_file']['name'])) {
                $file = $_FILES['cv_file'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['pdf', 'doc', 'docx'];

                if (!in_array($ext, $allowed, true)) {
                    set_flash('Invalid file format. Please upload your CV in PDF or Word document format.', 'danger');
                    redirect($_SERVER['HTTP_REFERER'] ?? '/jobs');
                }

                if ($file['size'] > 10 * 1024 * 1024) {
                    set_flash('File exceeds 10MB limit.', 'danger');
                    redirect($_SERVER['HTTP_REFERER'] ?? '/jobs');
                }

                $uploadDir = App::uploadPath();
                $cvFilename = 'cv_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $targetFile = $uploadDir . DIRECTORY_SEPARATOR . $cvFilename;

                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    $cvPath = $cvFilename;
                }
            }

            // Fetch job info
            $jobTitle = 'General Recruitment Candidate';
            if ($jobId > 0) {
                $jStmt = $pdo->prepare("SELECT job_title FROM jobs WHERE id = ?");
                $jStmt->execute([$jobId]);
                $jobTitle = $jStmt->fetchColumn() ?: $jobTitle;
            }

            $pdo->beginTransaction();
            try {
                // Find or Create Applicant/Customer Record
                $custStmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? LIMIT 1");
                $custStmt->execute([$email]);
                $existingCustId = $custStmt->fetchColumn();

                $nameParts = explode(' ', $fullName, 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? 'Candidate';

                if ($existingCustId) {
                    $customerId = (int)$existingCustId;
                } else {
                    $customerCode = 'APP-WEB-' . strtoupper(substr(md5(uniqid()), 0, 6));
                    $insCust = $pdo->prepare("INSERT INTO customers (
                        customer_code, first_name, last_name, full_name, email, mobile, nationality, 
                        current_country, address, notes, is_active
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
                    $insCust->execute([
                        $customerCode, $firstName, $lastName, $fullName, $email, $phone, $nationality,
                        $currentLocation, "Website Application for: {$jobTitle}",
                        "Experience: {$experienceYears}\nLetter: {$coverLetter}"
                    ]);
                    $customerId = (int)$pdo->lastInsertId();
                }

                // Log Communication Record
                $commStmt = $pdo->prepare("INSERT INTO communications (
                    customer_id, channel, direction, subject, message, recorded_at
                ) VALUES (?, 'Website Candidate Application', 'Inbound', ?, ?, CURRENT_TIMESTAMP)");
                $commStmt->execute([
                    $customerId,
                    "Job Application: {$jobTitle}",
                    "Candidate {$fullName} applied online for {$jobTitle}.\nPhone: {$phone}\nLocation: {$currentLocation}\nExperience: {$experienceYears}\nNotes: {$coverLetter}"
                ]);

                // Record CV Document if uploaded
                if (!empty($cvPath)) {
                    $docTypeStmt = $pdo->prepare("SELECT id FROM document_types WHERE code = 'EMPLOYMENT_LETTER' OR category = 'Employment' LIMIT 1");
                    $docTypeStmt->execute();
                    $docTypeId = (int)$docTypeStmt->fetchColumn() ?: 1;

                    $insDoc = $pdo->prepare("INSERT INTO documents (
                        customer_id, document_type_id, document_title, file_path, file_name, file_size, mime_type, status, uploaded_by_type, notes
                    ) VALUES (?, ?, ?, ?, ?, ?, 'application/pdf', 'UNDER_REVIEW', 'Customer', ?)");
                    $insDoc->execute([
                        $customerId, $docTypeId, "CV / Resume - {$fullName}", $cvPath, $_FILES['cv_file']['name'], $_FILES['cv_file']['size'], "Applied online for {$jobTitle}"
                    ]);
                }

                // Audit Log & Email Notification
                AuditService::log('WEBSITE_JOB_APPLICATION', 'Recruitment', $customerId, "Job application submitted online for '{$jobTitle}' by {$fullName}", [
                    'email' => $email,
                    'phone' => $phone,
                    'job' => $jobTitle
                ]);

                // Send email receipt to candidate
                EmailService::send([
                    'to' => $email,
                    'name' => $fullName,
                    'subject' => "Job Application Received — {$jobTitle}",
                    'bodyHtml' => "<h3>Application Received Successfully</h3><p>Dear {$fullName},</p><p>Thank you for submitting your application for <strong>{$jobTitle}</strong> at " . App::COMPANY_NAME . ". Our recruitment team will review your qualifications and reach out if your profile matches our requirements.</p><p>Warm regards,<br>Recruitment Operations Team</p>"
                ]);

                $pdo->commit();
                set_flash("Thank you {$fullName}! Your application for '{$jobTitle}' has been submitted successfully. Our recruitment team will contact you shortly.", 'success');
                redirect('/jobs');
            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash('Application submission failed: ' . $e->getMessage(), 'danger');
                redirect($_SERVER['HTTP_REFERER'] ?? '/jobs');
            }
        }

        $id = (int)($_GET['job_id'] ?? 0);
        $job = null;
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
            $stmt->execute([$id]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        require_once dirname(__DIR__) . '/Views/public/apply_job.php';
    }

    /**
     * Public Visa Enquiry Form (GET & POST)
     */
    public function visaEnquiry(): void
    {
        $pdo = Database::getConnection();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            \App\Middleware\CsrfMiddleware::validate();

            if (!empty($_POST['website_hp'])) {
                redirect('/visa-enquiry', 'Enquiry sent successfully.', 'success');
            }

            $fullName = trim($_POST['full_name'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $phone = trim($_POST['phone'] ?? '');
            $nationality = trim($_POST['nationality'] ?? '');
            $destination = trim($_POST['destination'] ?? '');
            $visaServiceId = (int)($_POST['visa_service_id'] ?? 0);
            $message = trim($_POST['message'] ?? '');

            if (empty($fullName) || empty($email) || empty($phone) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                set_flash('Please fill in your full name, phone number and a valid email address.', 'danger');
                redirect('/visa-enquiry');
            }

            $pdo->beginTransaction();
            try {
                // Find or Create Customer
                $custStmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? LIMIT 1");
                $custStmt->execute([$email]);
                $customerId = $custStmt->fetchColumn();

                if (!$customerId) {
                    $nameParts = explode(' ', $fullName, 2);
                    $customerCode = 'MSC-WEB-' . strtoupper(substr(md5(uniqid()), 0, 6));
                    $insCust = $pdo->prepare("INSERT INTO customers (
                        customer_code, first_name, last_name, full_name, email, mobile, nationality, current_country, notes, is_active
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Web Visa Enquiry', 1)");
                    $insCust->execute([
                        $customerCode, $nameParts[0], $nameParts[1] ?? '', $fullName, $email, $phone, $nationality ?: 'General', $destination ?: 'UAE'
                    ]);
                    $customerId = (int)$pdo->lastInsertId();
                }

                // Log Communication Lead Record
                $commStmt = $pdo->prepare("INSERT INTO communications (
                    customer_id, channel, direction, subject, message, recorded_at
                ) VALUES (?, 'Website Visa Enquiry', 'Inbound', ?, ?, CURRENT_TIMESTAMP)");
                $commStmt->execute([
                    $customerId,
                    "Website Visa Enquiry — Destination: {$destination}",
                    "Enquiry Details:\nName: {$fullName}\nEmail: {$email}\nPhone: {$phone}\nNationality: {$nationality}\nTarget Destination: {$destination}\nMessage: {$message}"
                ]);

                AuditService::log('WEBSITE_ENQUIRY', 'Leads', (int)$customerId, "Public visa enquiry received for {$destination} from {$fullName}");

                // Email Receipt
                EmailService::send([
                    'to' => $email,
                    'name' => $fullName,
                    'subject' => "Visa Enquiry Received — " . App::COMPANY_NAME,
                    'bodyHtml' => "<h3>Thank you for contacting " . App::COMPANY_NAME . "</h3><p>Dear {$fullName},</p><p>We have received your enquiry regarding visa services for <strong>{$destination}</strong>. A dedicated visa specialist will review your requirements and reach out to you within 24 hours.</p><p>Helpline: " . Env::get('COMPANY_PHONE', '0585909349') . "</p>"
                ]);

                $pdo->commit();
                set_flash("Thank you {$fullName}! Your enquiry has been received. Our visa consultants will get in touch with you shortly.", 'success');
                redirect('/visa-enquiry');
            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash('Enquiry submission error: ' . $e->getMessage(), 'danger');
                redirect('/visa-enquiry');
            }
        }

        $countries = $pdo->query("SELECT id, name, flag_emoji FROM countries WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $services = $pdo->query("SELECT id, name, selling_price, currency FROM visa_services WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        require_once dirname(__DIR__) . '/Views/public/visa_enquiry.php';
    }

    /**
     * Public 2-Factor Visa Tracking Lookup
     */
    public function tracking(): void
    {
        $pdo = Database::getConnection();
        $trackingResult = null;
        $searched = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' || (!empty($_GET['app_num']) && !empty($_GET['pass_num']))) {
            $appNum = trim($_POST['application_number'] ?? $_GET['app_num'] ?? '');
            $passNum = trim($_POST['passport_number'] ?? $_GET['pass_num'] ?? '');
            $searched = true;

            if (empty($appNum) || empty($passNum)) {
                set_flash('Both Application Reference Number AND Passport Number are required for tracking lookup.', 'danger');
            } else {
                // Strict 2-Factor Verification: Matches BOTH application_number AND passport_number
                $stmt = $pdo->prepare("SELECT a.application_number, a.status, a.current_stage, a.application_date, 
                                              a.expected_completion_date, vs.name as service_name, vs.entry_type, vs.duration,
                                              ct.name as country_name, ct.flag_emoji
                                       FROM applications a
                                       JOIN visa_services vs ON a.visa_service_id = vs.id
                                       JOIN countries ct ON vs.country_id = ct.id
                                       WHERE UPPER(TRIM(a.application_number)) = UPPER(TRIM(?))
                                         AND UPPER(TRIM(a.passport_number)) = UPPER(TRIM(?))
                                       LIMIT 1");
                $stmt->execute([$appNum, $passNum]);
                $trackingResult = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($trackingResult) {
                    // Safe public stage mapping
                    $trackingResult['public_stage'] = $this->mapSafePublicStage($trackingResult['current_stage'], $trackingResult['status']);
                } else {
                    set_flash('No matching visa application found. Please verify your reference number and passport number.', 'warning');
                }
            }
        }

        require_once dirname(__DIR__) . '/Views/public/track.php';
    }

    /**
     * Contact Us Page
     */
    public function contact(): void
    {
        $pdo = Database::getConnection();
        $branches = $pdo->query("SELECT * FROM branches WHERE is_active = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        require_once dirname(__DIR__) . '/Views/public/contact.php';
    }

    /**
     * Frequently Asked Questions (FAQ)
     */
    public function faq(): void
    {
        require_once dirname(__DIR__) . '/Views/public/faq.php';
    }

    /**
     * Dynamic SEO XML Sitemap Generator
     */
    public function sitemap(): void
    {
        $baseUrl = rtrim((string)Env::get('APP_URL', 'https://visatrack.mstravelhub.com'), '/');
        $pdo = Database::getConnection();

        $services = $pdo->query("SELECT id, slug, updated_at FROM visa_services WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $jobs = [];
        try {
            $jobs = $pdo->query("SELECT id, slug, updated_at FROM jobs WHERE status = 'PUBLISHED'")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {}

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $staticPages = ['/', '/about', '/visa-services', '/jobs', '/visa-enquiry', '/track', '/contact', '/faq'];
        foreach ($staticPages as $page) {
            echo '<url>';
            echo '<loc>' . htmlspecialchars($baseUrl . $page) . '</loc>';
            echo '<lastmod>' . date('Y-m-d') . '</lastmod>';
            echo '<changefreq>daily</changefreq>';
            echo '<priority>' . ($page === '/' ? '1.0' : '0.8') . '</priority>';
            echo '</url>';
        }

        foreach ($services as $srv) {
            $slug = !empty($srv['slug']) ? $srv['slug'] : $srv['id'];
            echo '<url>';
            echo '<loc>' . htmlspecialchars($baseUrl . '/visa-service?slug=' . $slug) . '</loc>';
            echo '<lastmod>' . date('Y-m-d', strtotime($srv['updated_at'] ?? 'now')) . '</lastmod>';
            echo '<changefreq>weekly</changefreq>';
            echo '<priority>0.7</priority>';
            echo '</url>';
        }

        foreach ($jobs as $j) {
            $slug = !empty($j['slug']) ? $j['slug'] : $j['id'];
            echo '<url>';
            echo '<loc>' . htmlspecialchars($baseUrl . '/job?slug=' . $slug) . '</loc>';
            echo '<lastmod>' . date('Y-m-d', strtotime($j['updated_at'] ?? 'now')) . '</lastmod>';
            echo '<changefreq>weekly</changefreq>';
            echo '<priority>0.7</priority>';
            echo '</url>';
        }

        echo '</urlset>';
        exit;
    }

    /**
     * Dynamic SEO robots.txt
     */
    public function robotsTxt(): void
    {
        $baseUrl = rtrim((string)Env::get('APP_URL', 'https://visatrack.mstravelhub.com'), '/');

        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Disallow: /dashboard\n";
        echo "Disallow: /applications\n";
        echo "Disallow: /customers\n";
        echo "Disallow: /portal/\n";
        echo "Disallow: /agent/\n";
        echo "Disallow: /supplier/\n";
        echo "Disallow: /auth/\n";
        echo "Disallow: /api/\n";
        echo "Disallow: /uploads/\n";
        echo "Disallow: /documents/\n";
        echo "Sitemap: {$baseUrl}/sitemap.xml\n";
        exit;
    }

    /**
     * Map internal stage to safe public tracking status
     */
    private function mapSafePublicStage(string $currentStage, string $status): string
    {
        if ($status === 'Approved' || $status === 'Completed') {
            return 'Visa Approved & Issued';
        }
        if ($status === 'Rejected') {
            return 'Application Outcome Finalized';
        }

        $lower = strtolower($currentStage);
        if (str_contains($lower, 'registered') || str_contains($lower, 'initiation')) {
            return 'Application Received';
        }
        if (str_contains($lower, 'review') || str_contains($lower, 'document')) {
            return 'Documents Verification & Compliance Review';
        }
        if (str_contains($lower, 'submitted') || str_contains($lower, 'posted') || str_contains($lower, 'process')) {
            return 'Under Embassy / Immigration Processing';
        }
        if (str_contains($lower, 'ready')) {
            return 'Prepared for Embassy Submission';
        }

        return 'Processing In Progress';
    }
}
