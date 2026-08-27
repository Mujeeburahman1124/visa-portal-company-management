-- ============================================================================
-- VISA TRACK & MS TRAVEL HUB — Comprehensive MySQL 8+ Production Schema
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. COMPANIES & BRANCHES
CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(150),
    phone VARCHAR(50),
    address TEXT,
    status VARCHAR(20) DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT DEFAULT 1,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    country VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    address TEXT,
    phone VARCHAR(50),
    email VARCHAR(150),
    manager_id INT,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. RBAC: ROLES & PERMISSIONS
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    module VARCHAR(50) NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. USERS (STAFF & OFFICERS)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    branch_id INT,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    avatar VARCHAR(255),
    designation VARCHAR(100),
    department VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    last_login_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_role (role_id),
    INDEX idx_users_branch (branch_id),
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. APPLICANTS / CUSTOMERS & IDENTITIES
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_code VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    gender VARCHAR(20),
    dob DATE,
    nationality VARCHAR(100) NOT NULL,
    place_of_birth VARCHAR(150),
    marital_status VARCHAR(50),
    occupation VARCHAR(150),
    mobile VARCHAR(50) NOT NULL,
    whatsapp VARCHAR(50),
    email VARCHAR(150) UNIQUE,
    password_hash VARCHAR(255),
    current_country VARCHAR(100) NOT NULL,
    address TEXT,
    created_by INT,
    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customers_code (customer_code),
    INDEX idx_customers_email (email),
    INDEX idx_customers_mobile (mobile),
    INDEX idx_customers_nationality (nationality),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_passports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    passport_number VARCHAR(100) NOT NULL,
    issuing_country VARCHAR(100) NOT NULL,
    issue_date DATE,
    expiry_date DATE NOT NULL,
    place_of_issue VARCHAR(150),
    is_primary TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_passports_number (passport_number),
    INDEX idx_passports_expiry (expiry_date),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_national_ids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    id_number VARCHAR(100) NOT NULL,
    id_type VARCHAR(100) DEFAULT 'National ID',
    issuing_country VARCHAR(100) NOT NULL,
    issue_date DATE,
    expiry_date DATE,
    front_file VARCHAR(255),
    back_file VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. COUNTRIES & VISA CATALOG
CREATE TABLE IF NOT EXISTS countries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    iso_code VARCHAR(10) NOT NULL UNIQUE,
    flag_emoji VARCHAR(10),
    currency VARCHAR(10) DEFAULT 'USD',
    region VARCHAR(100),
    embassy_info TEXT,
    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visa_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visa_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_id INT NOT NULL,
    category_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    duration VARCHAR(100) NOT NULL,
    max_stay VARCHAR(100),
    validity VARCHAR(100),
    entry_type VARCHAR(50) DEFAULT 'Single Entry',
    processing_type VARCHAR(50) DEFAULT 'Normal',
    estimated_days INT DEFAULT 7,
    passport_validity_rule_months INT DEFAULT 6,
    min_age INT DEFAULT 0,
    max_age INT DEFAULT 100,
    supplier_cost DECIMAL(10,2) DEFAULT 0.00,
    service_fee DECIMAL(10,2) DEFAULT 0.00,
    tax_rate DECIMAL(5,2) DEFAULT 0.00,
    selling_price DECIMAL(10,2) NOT NULL,
    cancellation_policy TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_services_country (country_id),
    INDEX idx_services_category (category_id),
    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES visa_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. DOCUMENT RULES & MATRIX
CREATE TABLE IF NOT EXISTS document_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    code VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    category VARCHAR(50) DEFAULT 'Personal',
    requires_expiry TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visa_requirements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    document_type_id INT NOT NULL,
    is_mandatory TINYINT(1) DEFAULT 1,
    condition_notes TEXT,
    instructions TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_requirements_service (service_id),
    FOREIGN KEY (service_id) REFERENCES visa_services(id) ON DELETE CASCADE,
    FOREIGN KEY (document_type_id) REFERENCES document_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. SUPPLIERS
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_code VARCHAR(50) NOT NULL UNIQUE,
    company_name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(150),
    mobile VARCHAR(50),
    whatsapp VARCHAR(50),
    email VARCHAR(150),
    country VARCHAR(100),
    address TEXT,
    payment_terms TEXT,
    bank_details TEXT,
    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. VISA APPLICATIONS (CORE MASTER ENTITY)
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_number VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    visa_service_id INT NOT NULL,
    branch_id INT,
    assigned_staff_id INT,
    supplier_id INT,
    agent_id INT,
    current_stage VARCHAR(100) NOT NULL DEFAULT 'Application Registered',
    status VARCHAR(50) NOT NULL DEFAULT 'Draft',
    priority VARCHAR(20) NOT NULL DEFAULT 'Normal',
    calculated_health INT DEFAULT 100,
    health_reason TEXT,
    nationality VARCHAR(100) NOT NULL,
    residence_country VARCHAR(100) NOT NULL,
    passport_number VARCHAR(100) NOT NULL,
    travel_date DATE,
    return_date DATE,
    application_date DATE NOT NULL,
    expected_completion_date DATE,
    actual_completion_date DATE,
    selling_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(10,2) DEFAULT 0.00,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    balance_amount DECIMAL(10,2) DEFAULT 0.00,
    supplier_cost DECIMAL(10,2) DEFAULT 0.00,
    other_expenses DECIMAL(10,2) DEFAULT 0.00,
    gross_profit DECIMAL(10,2) DEFAULT 0.00,
    supplier_reference VARCHAR(100),
    embassy_reference VARCHAR(100),
    visa_number VARCHAR(100),
    visa_issue_date DATE,
    visa_expiry_date DATE,
    visa_file VARCHAR(255),
    rejection_reason_customer TEXT,
    rejection_reason_internal TEXT,
    return_reason TEXT,
    return_deadline DATE,
    internal_notes TEXT,
    customer_notes TEXT,
    is_archived TINYINT(1) DEFAULT 0,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_applications_num (application_number),
    INDEX idx_applications_customer (customer_id),
    INDEX idx_applications_service (visa_service_id),
    INDEX idx_applications_staff (assigned_staff_id),
    INDEX idx_applications_stage (current_stage),
    INDEX idx_applications_status (status),
    INDEX idx_applications_priority (priority),
    INDEX idx_applications_expected (expected_completion_date),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (visa_service_id) REFERENCES visa_services(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_staff_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. STAGE HISTORY & ASSIGNMENTS (IMMUTABLE LIFECYCLE AUDITING)
CREATE TABLE IF NOT EXISTS application_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    from_status VARCHAR(50),
    to_status VARCHAR(50) NOT NULL,
    from_stage VARCHAR(100),
    to_stage VARCHAR(100) NOT NULL,
    changed_by INT,
    comments TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_hist_application (application_id),
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS application_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    staff_id INT,
    assigned_to INT NOT NULL,
    assigned_by INT NOT NULL,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    unassigned_at DATETIME,
    is_current TINYINT(1) DEFAULT 1,
    notes TEXT,
    INDEX idx_assign_application (application_id),
    INDEX idx_assign_staff (assigned_to),
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (assigned_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. DOCUMENTS & VERIFICATIONS
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT,
    customer_id INT NOT NULL,
    applicant_id INT,
    document_type_id INT NOT NULL,
    document_title VARCHAR(200) NOT NULL,
    file_path VARCHAR(255),
    file_name VARCHAR(255),
    file_size INT DEFAULT 0,
    mime_type VARCHAR(100),
    version INT DEFAULT 1,
    expiry_date DATE,
    status VARCHAR(50) NOT NULL DEFAULT 'Missing',
    uploaded_by_type VARCHAR(50) DEFAULT 'Staff',
    uploaded_by_id INT,
    verified_by INT,
    verified_at DATETIME,
    rejection_reason TEXT,
    replacement_requested TINYINT(1) DEFAULT 0,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_docs_application (application_id),
    INDEX idx_docs_customer (customer_id),
    INDEX idx_docs_type (document_type_id),
    INDEX idx_docs_status (status),
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (document_type_id) REFERENCES document_types(id),
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. TASKS & APPOINTMENTS
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT,
    task_title VARCHAR(200) NOT NULL,
    description TEXT,
    assigned_to INT NOT NULL,
    created_by INT,
    priority VARCHAR(20) DEFAULT 'Normal',
    due_date DATE NOT NULL,
    status VARCHAR(30) DEFAULT 'Pending',
    completed_at DATETIME,
    completed_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tasks_app (application_id),
    INDEX idx_tasks_assigned (assigned_to),
    INDEX idx_tasks_due (due_date),
    INDEX idx_tasks_status (status),
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    appointment_type VARCHAR(100) NOT NULL,
    center_name VARCHAR(150) NOT NULL,
    location_address TEXT,
    appointment_date DATE NOT NULL,
    appointment_time VARCHAR(20) NOT NULL,
    reference_number VARCHAR(100),
    status VARCHAR(30) DEFAULT 'Scheduled',
    notes TEXT,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_appts_app (application_id),
    INDEX idx_appts_date (appointment_date),
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. NOTIFICATIONS, EMAIL TEMPLATES & AUDIT TRAIL
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    customer_id INT,
    recipient_type VARCHAR(20) DEFAULT 'Staff',
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    notification_type VARCHAR(50) DEFAULT 'System',
    severity VARCHAR(20) DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notif_user (user_id),
    INDEX idx_notif_read (is_read),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(150) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body_html TEXT NOT NULL,
    placeholders TEXT,
    is_active TINYINT(1) DEFAULT 1,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    customer_id INT,
    actor_type VARCHAR(20) DEFAULT 'Staff',
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL,
    record_id INT,
    description TEXT NOT NULL,
    details_json JSON,
    ip_address VARCHAR(50),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_logs_user (user_id),
    INDEX idx_logs_module (module),
    INDEX idx_logs_record (record_id),
    INDEX idx_logs_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_settings (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_logs (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_queue (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_templates (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. COMPATIBILITY VIEWS
CREATE OR REPLACE VIEW applicants AS SELECT * FROM customers;
CREATE OR REPLACE VIEW visa_applications AS SELECT * FROM applications;
CREATE OR REPLACE VIEW application_documents AS SELECT * FROM documents;
CREATE OR REPLACE VIEW application_tasks AS SELECT * FROM tasks;
CREATE OR REPLACE VIEW application_appointments AS SELECT * FROM appointments;
CREATE OR REPLACE VIEW audit_logs AS SELECT * FROM activity_logs;
CREATE OR REPLACE VIEW required_documents AS SELECT * FROM visa_requirements;
CREATE OR REPLACE VIEW visa_types AS SELECT * FROM visa_categories;

SET FOREIGN_KEY_CHECKS = 1;
