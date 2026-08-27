-- VISA TRACK & MS TRAVEL HUB — Comprehensive SQLite Database Schema

CREATE TABLE IF NOT EXISTS roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    slug TEXT NOT NULL UNIQUE,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    module TEXT NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS branches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    code TEXT NOT NULL UNIQUE,
    country TEXT NOT NULL,
    city TEXT NOT NULL,
    address TEXT,
    phone TEXT,
    email TEXT,
    manager_id INTEGER,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role_id INTEGER NOT NULL,
    branch_id INTEGER,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    phone TEXT,
    avatar TEXT,
    designation TEXT,
    department TEXT,
    is_active INTEGER DEFAULT 1,
    last_login_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

CREATE TABLE IF NOT EXISTS customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_code TEXT NOT NULL UNIQUE,
    first_name TEXT NOT NULL,
    middle_name TEXT,
    last_name TEXT NOT NULL,
    full_name TEXT NOT NULL,
    gender TEXT,
    dob DATE,
    nationality TEXT NOT NULL,
    place_of_birth TEXT,
    marital_status TEXT,
    occupation TEXT,
    mobile TEXT NOT NULL,
    whatsapp TEXT,
    email TEXT UNIQUE,
    password_hash TEXT,
    current_country TEXT NOT NULL,
    address TEXT,
    created_by INTEGER,
    notes TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS customer_passports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER NOT NULL,
    passport_number TEXT NOT NULL,
    issuing_country TEXT NOT NULL,
    issue_date DATE,
    expiry_date DATE NOT NULL,
    place_of_issue TEXT,
    is_primary INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS customer_national_ids (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER NOT NULL,
    id_number TEXT NOT NULL,
    id_type TEXT DEFAULT 'National ID',
    issuing_country TEXT NOT NULL,
    issue_date DATE,
    expiry_date DATE,
    front_file TEXT,
    back_file TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS customer_residences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER NOT NULL,
    residence_country TEXT NOT NULL,
    permit_number TEXT,
    permit_type TEXT,
    expiry_date DATE,
    employer TEXT,
    job_title TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS countries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    iso_code TEXT NOT NULL UNIQUE,
    flag_emoji TEXT,
    currency TEXT DEFAULT 'USD',
    region TEXT,
    embassy_info TEXT,
    notes TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS visa_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    slug TEXT NOT NULL UNIQUE,
    description TEXT,
    icon TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS visa_services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    country_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    slug TEXT NOT NULL,
    duration TEXT NOT NULL,
    max_stay TEXT,
    validity TEXT,
    entry_type TEXT DEFAULT 'Single Entry',
    processing_type TEXT DEFAULT 'Normal',
    estimated_days INTEGER DEFAULT 7,
    passport_validity_rule_months INTEGER DEFAULT 6,
    min_age INTEGER DEFAULT 0,
    max_age INTEGER DEFAULT 100,
    supplier_cost REAL DEFAULT 0.00,
    service_fee REAL DEFAULT 0.00,
    tax_rate REAL DEFAULT 0.00,
    selling_price REAL NOT NULL,
    cancellation_policy TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES visa_categories(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS visa_eligibility_rules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_id INTEGER NOT NULL,
    applicant_nationality TEXT NOT NULL,
    residence_country TEXT,
    price_override REAL,
    supplier_cost_override REAL,
    processing_days_override INTEGER,
    is_eligible INTEGER DEFAULT 1,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES visa_services(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS document_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    code TEXT NOT NULL UNIQUE,
    description TEXT,
    category TEXT DEFAULT 'Personal',
    requires_expiry INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS visa_requirements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_id INTEGER NOT NULL,
    document_type_id INTEGER NOT NULL,
    is_mandatory INTEGER DEFAULT 1,
    condition_notes TEXT,
    instructions TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES visa_services(id) ON DELETE CASCADE,
    FOREIGN KEY (document_type_id) REFERENCES document_types(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS suppliers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    supplier_code TEXT NOT NULL UNIQUE,
    company_name TEXT NOT NULL,
    contact_person TEXT,
    mobile TEXT,
    whatsapp TEXT,
    email TEXT,
    country TEXT,
    address TEXT,
    payment_terms TEXT,
    bank_details TEXT,
    notes TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS supplier_services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    supplier_id INTEGER NOT NULL,
    visa_service_id INTEGER NOT NULL,
    supplier_cost REAL NOT NULL,
    processing_days INTEGER,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
    FOREIGN KEY (visa_service_id) REFERENCES visa_services(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS agents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    agent_code TEXT NOT NULL UNIQUE,
    company_name TEXT NOT NULL,
    contact_person TEXT,
    mobile TEXT,
    email TEXT,
    country TEXT,
    credit_limit REAL DEFAULT 0.00,
    balance REAL DEFAULT 0.00,
    commission_rate REAL DEFAULT 0.00,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS applications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    application_number TEXT NOT NULL UNIQUE,
    customer_id INTEGER NOT NULL,
    visa_service_id INTEGER NOT NULL,
    branch_id INTEGER,
    assigned_staff_id INTEGER,
    supplier_id INTEGER,
    agent_id INTEGER,
    current_stage TEXT NOT NULL DEFAULT 'Application Registered',
    status TEXT NOT NULL DEFAULT 'Draft',
    priority TEXT NOT NULL DEFAULT 'Normal',
    calculated_health INTEGER DEFAULT 100,
    health_reason TEXT,
    nationality TEXT NOT NULL,
    residence_country TEXT NOT NULL,
    passport_number TEXT NOT NULL,
    travel_date DATE,
    return_date DATE,
    application_date DATE NOT NULL,
    expected_completion_date DATE,
    actual_completion_date DATE,
    selling_price REAL NOT NULL DEFAULT 0.00,
    discount REAL DEFAULT 0.00,
    tax_amount REAL DEFAULT 0.00,
    total_amount REAL NOT NULL DEFAULT 0.00,
    paid_amount REAL DEFAULT 0.00,
    balance_amount REAL DEFAULT 0.00,
    supplier_cost REAL DEFAULT 0.00,
    other_expenses REAL DEFAULT 0.00,
    gross_profit REAL DEFAULT 0.00,
    supplier_reference TEXT,
    embassy_reference TEXT,
    visa_number TEXT,
    visa_issue_date DATE,
    visa_expiry_date DATE,
    visa_file TEXT,
    rejection_reason_customer TEXT,
    rejection_reason_internal TEXT,
    return_reason TEXT,
    return_deadline DATE,
    internal_notes TEXT,
    customer_notes TEXT,
    is_archived INTEGER DEFAULT 0,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (visa_service_id) REFERENCES visa_services(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (assigned_staff_id) REFERENCES users(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (agent_id) REFERENCES agents(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS application_assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    application_id INTEGER NOT NULL,
    assigned_to INTEGER NOT NULL,
    assigned_by INTEGER NOT NULL,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (assigned_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS application_status_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    application_id INTEGER NOT NULL,
    from_status TEXT,
    to_status TEXT NOT NULL,
    from_stage TEXT,
    to_stage TEXT NOT NULL,
    changed_by INTEGER,
    comments TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS documents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    application_id INTEGER,
    customer_id INTEGER NOT NULL,
    document_type_id INTEGER NOT NULL,
    document_title TEXT NOT NULL,
    file_path TEXT,
    file_name TEXT,
    file_size INTEGER DEFAULT 0,
    mime_type TEXT,
    version INTEGER DEFAULT 1,
    expiry_date DATE,
    status TEXT NOT NULL DEFAULT 'Missing',
    uploaded_by_type TEXT DEFAULT 'Staff',
    uploaded_by_id INTEGER,
    verified_by INTEGER,
    verified_at DATETIME,
    rejection_reason TEXT,
    replacement_requested INTEGER DEFAULT 0,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (document_type_id) REFERENCES document_types(id),
    FOREIGN KEY (verified_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS document_versions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    document_id INTEGER NOT NULL,
    file_path TEXT NOT NULL,
    file_name TEXT NOT NULL,
    file_size INTEGER DEFAULT 0,
    version_number INTEGER NOT NULL,
    uploaded_by_type TEXT DEFAULT 'Staff',
    uploaded_by_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS document_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    application_id INTEGER NOT NULL,
    customer_id INTEGER NOT NULL,
    document_type_id INTEGER NOT NULL,
    request_reason TEXT NOT NULL,
    instructions TEXT,
    due_date DATE,
    is_mandatory INTEGER DEFAULT 1,
    status TEXT DEFAULT 'PENDING',
    requested_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    fulfilled_at DATETIME,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (document_type_id) REFERENCES document_types(id),
    FOREIGN KEY (requested_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    payment_number TEXT NOT NULL UNIQUE,
    invoice_number TEXT NOT NULL,
    application_id INTEGER NOT NULL,
    customer_id INTEGER NOT NULL,
    amount REAL NOT NULL,
    currency TEXT DEFAULT 'USD',
    payment_date DATE NOT NULL,
    payment_method TEXT DEFAULT 'Cash',
    transaction_reference TEXT,
    payment_type TEXT DEFAULT 'Customer Payment',
    status TEXT DEFAULT 'Completed',
    received_by INTEGER,
    receipt_file TEXT,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (received_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS refunds (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    refund_number TEXT NOT NULL UNIQUE,
    payment_id INTEGER,
    application_id INTEGER NOT NULL,
    customer_id INTEGER NOT NULL,
    amount REAL NOT NULL,
    reason TEXT NOT NULL,
    payment_method TEXT DEFAULT 'Bank Transfer',
    transaction_reference TEXT,
    processed_by INTEGER NOT NULL,
    status TEXT DEFAULT 'Processed',
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id),
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (processed_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS supplier_payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    payment_reference TEXT NOT NULL UNIQUE,
    supplier_id INTEGER NOT NULL,
    application_id INTEGER NOT NULL,
    payable_amount REAL NOT NULL,
    paid_amount REAL NOT NULL,
    payment_date DATE NOT NULL,
    payment_method TEXT DEFAULT 'Bank Transfer',
    transaction_reference TEXT,
    notes TEXT,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    application_id INTEGER,
    customer_id INTEGER,
    task_title TEXT NOT NULL,
    description TEXT,
    task_type TEXT DEFAULT 'General',
    priority TEXT DEFAULT 'Normal',
    assigned_to INTEGER,
    created_by INTEGER,
    start_date DATE,
    due_date DATE,
    completed_at DATETIME,
    status TEXT DEFAULT 'Pending',
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS task_comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    task_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS appointments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    application_id INTEGER NOT NULL,
    customer_id INTEGER NOT NULL,
    appointment_type TEXT NOT NULL,
    center_name TEXT NOT NULL,
    location_address TEXT,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reference_number TEXT,
    assigned_staff_id INTEGER,
    status TEXT DEFAULT 'Scheduled',
    document_file TEXT,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (assigned_staff_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS communications (
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
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (staff_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    customer_id INTEGER,
    recipient_type TEXT DEFAULT 'Staff',
    title TEXT NOT NULL,
    message TEXT NOT NULL,
    link TEXT,
    notification_type TEXT DEFAULT 'System',
    severity TEXT DEFAULT 'info',
    is_read INTEGER DEFAULT 0,
    read_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE IF NOT EXISTS email_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    template_key TEXT NOT NULL UNIQUE,
    title TEXT NOT NULL,
    subject TEXT NOT NULL,
    body_html TEXT NOT NULL,
    placeholders TEXT,
    is_active INTEGER DEFAULT 1,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    customer_id INTEGER,
    actor_type TEXT DEFAULT 'Staff',
    action TEXT NOT NULL,
    module TEXT NOT NULL,
    record_id INTEGER,
    description TEXT NOT NULL,
    details_json TEXT,
    ip_address TEXT,
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS system_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT NOT NULL UNIQUE,
    setting_value TEXT,
    setting_group TEXT DEFAULT 'General',
    description TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS notification_settings (
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
);

CREATE TABLE IF NOT EXISTS notification_logs (
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
);

CREATE TABLE IF NOT EXISTS notification_queue (
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
);

CREATE TABLE IF NOT EXISTS notification_templates (
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
);

-- Essential Performance Indexes
CREATE INDEX IF NOT EXISTS idx_apps_num ON applications(application_number);
CREATE INDEX IF NOT EXISTS idx_apps_status ON applications(status);
CREATE INDEX IF NOT EXISTS idx_apps_stage ON applications(current_stage);
CREATE INDEX IF NOT EXISTS idx_apps_customer ON applications(customer_id);
CREATE INDEX IF NOT EXISTS idx_apps_staff ON applications(assigned_staff_id);
CREATE INDEX IF NOT EXISTS idx_docs_app ON documents(application_id);
CREATE INDEX IF NOT EXISTS idx_docs_cust ON documents(customer_id);
CREATE INDEX IF NOT EXISTS idx_tasks_app ON tasks(application_id);
CREATE INDEX IF NOT EXISTS idx_tasks_staff ON tasks(assigned_to);
CREATE INDEX IF NOT EXISTS idx_notif_user ON notifications(user_id, is_read);
CREATE INDEX IF NOT EXISTS idx_act_module ON activity_logs(module, record_id);
CREATE INDEX IF NOT EXISTS idx_notif_logs_event ON notification_logs(event_type);
CREATE INDEX IF NOT EXISTS idx_notif_logs_status ON notification_logs(status);
CREATE INDEX IF NOT EXISTS idx_notif_logs_channel ON notification_logs(channel);
CREATE INDEX IF NOT EXISTS idx_notif_logs_recipient ON notification_logs(recipient_type, recipient_id);
CREATE INDEX IF NOT EXISTS idx_notif_logs_idemp ON notification_logs(idempotency_key);
CREATE INDEX IF NOT EXISTS idx_notif_queue_status_retry ON notification_queue(status, next_retry_at);

