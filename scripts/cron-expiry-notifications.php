<?php
declare(strict_types=1);

/**
 * Automated Expiry & Follow-up Notification Runner
 *
 * Scans visa expiry (20, 15, 10, 5, 4, 3, 2, 1, 0 days countdown and expired),
 * passport expiry, national ID expiry, and residence permit expiry.
 * Dispatches emails to applicants and in-app alerts to staff and applicants.
 *
 * Run via CLI / cron:
 *   php scripts/cron-expiry-notifications.php
 */

require_once dirname(__DIR__) . '/app/autoload.php';
\App\Database\DatabaseBootstrapper::init(true);

echo "====================================================\n";
echo "Starting Automated Expiry & Follow-up Scan...\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "====================================================\n";

$result = \App\Services\ExpiryNotificationService::run();

echo "Visas Scanned:         " . $result['scanned_visas'] . "\n";
echo "Passports Scanned:     " . $result['scanned_passports'] . "\n";
echo "National IDs Scanned:  " . $result['scanned_national_ids'] . "\n";
echo "Residences Scanned:    " . $result['scanned_residences'] . "\n";
echo "Emails Dispatched:     " . $result['emails_sent'] . "\n";
echo "In-App Alerts Created: " . $result['in_app_sent'] . "\n";
echo "Skipped (Already Sent Today): " . $result['skipped_duplicates'] . "\n";
echo "====================================================\n";
echo "Expiry Scan Complete.\n";
