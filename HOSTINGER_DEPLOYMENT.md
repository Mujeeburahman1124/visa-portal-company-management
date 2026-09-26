# Hostinger & Apache Production Deployment Guide
## VISA TRACK — Global Visa, Recruitment & Operations SaaS

This document provides step-by-step instructions for deploying the **VISA TRACK SaaS Application** to **Hostinger Web Hosting (Apache + PHP + MySQL/MariaDB + HTTPS)**.

---

## 1. System Requirements

* **PHP Version:** PHP 8.1 or PHP 8.2 (Recommended: PHP 8.2)
* **Web Server:** Apache with `mod_rewrite`, `mod_headers`, and `mod_deflate` enabled
* **Database Engine:** MySQL 5.7+ or MariaDB 10.3+ (utf8mb4_unicode_ci collation)
* **Required PHP Extensions:**
  * `pdo_mysql`
  * `mbstring`
  * `openssl`
  * `curl`
  * `fileinfo` (`finfo`)
  * `json`
  * `gd`
  * `zip`
* **SSL Certificate:** Active Let's Encrypt / Hostinger SSL certificate (HTTPS required)

---

## 2. Server Directory Structure

Deploy the repository onto Hostinger as follows:

```text
/home/u123456789/domains/yourdomain.com/
│
├── public_html/ (OR public_html -> public/)
│   ├── index.php
│   ├── .htaccess
│   └── assets/ (CSS, JS, Fonts, Images)
│
├── app/
│   ├── Config/
│   ├── Controllers/
│   ├── Database/
│   ├── Middleware/
│   ├── Services/
│   └── Views/
│
├── storage/
│   ├── documents/ (PRIVATE: Passports, CVs, Certificates)
│   └── backups/ (Database backups)
│
├── data/
├── scripts/
├── tests/
├── .env
├── .env.example
├── .htaccess (Root protection)
└── HOSTINGER_DEPLOYMENT.md
```

> [!IMPORTANT]
> **Web Root Mapping:** In Hostinger hPanel, set the **Document Root** to point to the `public/` directory. If Hostinger restricts changing the root folder, place the repository in the parent directory and point `public_html` to `public/` or keep root `.htaccess` active.

---

## 3. Environment Configuration (`.env`)

1. Copy `.env.example` to `.env` on Hostinger:
   ```bash
   cp .env.example .env
   ```

2. Configure production variables in `.env`:
   ```env
   NOTIFICATION_ENV=production
   APP_ENV=production
   APP_NAME="VISA TRACK — Global Visa Management Portal"
   COMPANY_NAME="MS Travel Hub Global Visa Services"
   COMPANY_EMAIL="mstravelu@gmail.com"
   COMPANY_PHONE="0585909349"
   COMPANY_WEBSITE="https://yourdomain.com"
   APP_URL="https://yourdomain.com"

   # Production MySQL Database Connection
   DB_DRIVER=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=u123456789_visatrack
   DB_USER=u123456789_visauser
   DB_PASS=YourStrongProductionPassword123!
   DB_CHARSET=utf8mb4
   DB_ALLOW_SQLITE_FALLBACK=false

   # Production SMTP Credentials
   EMAIL_PROVIDER=smtp
   SMTP_HOST=smtp.gmail.com
   SMTP_PORT=587
   SMTP_USER=your_email@domain.com
   SMTP_PASSWORD=your_smtp_app_password
   SMTP_ENCRYPTION=tls
   SMTP_VERIFY_PEER=true
   SMTP_VERIFY_PEER_NAME=true
   SMTP_ALLOW_SELF_SIGNED=false
   EMAIL_FROM=notifications@yourdomain.com
   EMAIL_FROM_NAME="Visa Portal"
   ```

---

## 4. Database Setup & Import

1. Log into **Hostinger hPanel** -> **Databases** -> **MySQL Databases**.
2. Create a new MySQL database (e.g., `u123456789_visatrack`) and user.
3. Open **phpMyAdmin**.
4. Import `app/Database/schema_mysql.sql`.
5. Run schema initialization script or execute initial seed via CLI:
   ```bash
   php -r "require 'app/autoload.php'; \App\Database\DatabaseBootstrapper::init(true);"
   ```

---

## 5. Filesystem Permissions & Private Document Vault

Sensitive customer documents (passports, national IDs, CVs) are saved under `storage/documents/` outside the web root.

Set safe permissions via Hostinger File Manager or SSH:
```bash
chmod 755 storage/
chmod 755 storage/documents/
chmod 755 storage/backups/
chmod 644 .env
```

---

## 6. Hostinger Production Launch Checklist

- [x] Hostinger Domain & SSL (HTTPS) configured and enforced
- [x] Document root set to `public/`
- [x] Apache `public/.htaccess` and root `.htaccess` active
- [x] `vercel.json` completely removed
- [x] Production MySQL credentials configured (`DB_DRIVER=mysql`)
- [x] Silent SQLite fallback disabled (`DB_ALLOW_SQLITE_FALLBACK=false`)
- [x] Pre-filled demo login credentials & quick-fill buttons removed from UI
- [x] Document downloads protected by IDOR ownership check
- [x] Private storage folder `storage/documents/` outside public web root
- [x] Insecure SMTP TLS verification disabled (`SMTP_VERIFY_PEER=true`)
- [x] Public Website operational (`/`, `/about`, `/visa-services`, `/jobs`, `/track`, `/contact`, `/faq`)
- [x] Public Job Applications (`/jobs/apply`) connected to applicant management system with secure CV upload
- [x] Public Visa Enquiries (`/visa-enquiry`) connected to lead management system with honeypot spam protection
- [x] Dynamic `sitemap.xml` & `robots.txt` active
- [x] Full test suite executed with 100% pass rate
