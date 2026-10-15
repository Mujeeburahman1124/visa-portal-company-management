<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\App;
use App\Config\Database;
use App\Config\Env;
use PDO;

class ExpiryNotificationService
{
    /**
     * Target milestone follow-up days requested by user:
     * 20, 15, 10, 5, 4, 3, 2, 1, 0, and expired (< 0).
     */
    public const VISA_MILESTONE_DAYS = [20, 15, 10, 5, 4, 3, 2, 1, 0];
    public const DOCUMENT_MILESTONE_DAYS = [30, 20, 15, 10, 7, 5, 4, 3, 2, 1, 0];

    /**
     * Run full automated expiry scan and dispatch daily follow-up notifications.
     */
    public static function run(): array
    {
        $pdo = Database::getConnection();
        $today = strtotime(date('Y-m-d'));
        $todayYmd = date('Y-m-d');

        $stats = [
            'scanned_visas' => 0,
            'scanned_passports' => 0,
            'scanned_national_ids' => 0,
            'scanned_residences' => 0,
            'emails_sent' => 0,
            'in_app_sent' => 0,
            'skipped_duplicates' => 0,
            'details' => [],
        ];

        // 1. SCAN VISAS (applications and visa_approvals)
        $visaStmt = $pdo->query("
            SELECT a.id as app_id, a.application_number, a.customer_id, a.visa_number as app_visa_number,
                   a.visa_issue_date, a.visa_expiry_date, a.visa_status, a.destination_country, a.assigned_staff_id,
                   c.full_name as applicant_name, c.email as applicant_email, c.mobile as applicant_mobile, c.whatsapp as applicant_whatsapp,
                   vs.name as service_name, ct.name as country_name,
                   va.visa_number as approval_visa_number, va.expiry_date as approval_expiry_date,
                   va.entry_before_date as approval_entry_before_date, va.visa_status as approval_visa_status
            FROM applications a
            JOIN customers c ON a.customer_id = c.id
            LEFT JOIN visa_services vs ON a.visa_service_id = vs.id
            LEFT JOIN countries ct ON vs.country_id = ct.id
            LEFT JOIN visa_approvals va ON a.id = va.application_id
            WHERE (a.visa_expiry_date IS NOT NULL OR va.expiry_date IS NOT NULL)
              AND a.status NOT IN ('Draft', 'Rejected', 'Cancelled')
        ");
        $visas = $visaStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($visas as $v) {
            $stats['scanned_visas']++;
            $expiryDate = !empty($v['approval_expiry_date']) ? $v['approval_expiry_date'] : $v['visa_expiry_date'];
            if (empty($expiryDate)) continue;

            $expTime = strtotime($expiryDate);
            if (!$expTime) continue;
            $diffDays = (int)round(($expTime - $today) / 86400);

            // Check if diffDays qualifies: in milestone list or any day <= 20
            $isMilestone = in_array($diffDays, self::VISA_MILESTONE_DAYS, true) || ($diffDays < 0 && $diffDays >= -30);
            if (!$isMilestone) continue;

            $visaNo = !empty($v['approval_visa_number']) ? $v['approval_visa_number'] : (!empty($v['app_visa_number']) ? $v['app_visa_number'] : 'Visa');
            $status = !empty($v['approval_visa_status']) ? $v['approval_visa_status'] : (!empty($v['visa_status']) ? $v['visa_status'] : 'active');
            $country = !empty($v['country_name']) ? $v['country_name'] : (!empty($v['destination_country']) ? $v['destination_country'] : 'Destination Country');
            $applicantEmail = trim((string)$v['applicant_email']);

            $urgencyLabel = ($diffDays < 0) 
                ? "EXPIRED (" . abs($diffDays) . " days ago)" 
                : ($diffDays === 0 ? "EXPIRES TODAY" : "Expires in {$diffDays} days");

            // Idempotency token: 1 notification per day per milestone
            $idempotencyKey = "exp_visa_{$v['app_id']}_{$diffDays}_{$todayYmd}";

            // Check if already sent today
            $dupStmt = $pdo->prepare("SELECT id FROM notification_logs WHERE idempotency_key = ? LIMIT 1");
            $dupStmt->execute([$idempotencyKey]);
            if ($dupStmt->fetch()) {
                $stats['skipped_duplicates']++;
                continue;
            }

            // A. Send Email to Applicant
            $emailSent = false;
            if (!empty($applicantEmail)) {
                $subject = "Visa Expiry Follow-up: {$country} Visa ({$urgencyLabel}) — {$v['application_number']}";
                $portalUrl = App::url('portal/dashboard');
                $bodyHtml = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #1e293b;'>
                        <div style='background: #dc2626; color: #ffffff; padding: 18px 24px; border-radius: 8px 8px 0 0;'>
                            <h2 style='margin: 0; font-size: 1.3rem;'><i class='fa-solid fa-triangle-exclamation'></i> Visa Expiry Follow-up Notice</h2>
                            <p style='margin: 6px 0 0 0; opacity: 0.9; font-size: 0.9rem;'>Important reminder for your travel & residency validity</p>
                        </div>
                        <div style='background: #ffffff; border: 1px solid #e2e8f0; border-top: none; padding: 24px; border-radius: 0 0 8px 8px;'>
                            <p>Dear <strong>" . htmlspecialchars($v['applicant_name']) . "</strong>,</p>
                            <p>This is an automated operational alert regarding your visa document for <strong>" . htmlspecialchars($country) . "</strong>.</p>
                            
                            <div style='background: #fef2f2; border-left: 4px solid #dc2626; padding: 16px; margin: 20px 0; border-radius: 4px;'>
                                <table style='width: 100%; border-collapse: collapse; font-size: 0.92rem;'>
                                    <tr><td style='padding: 4px 0; color: #64748b;'>Application Number:</td><td style='padding: 4px 0; font-weight: bold;'>" . htmlspecialchars($v['application_number']) . "</td></tr>
                                    <tr><td style='padding: 4px 0; color: #64748b;'>Visa Number:</td><td style='padding: 4px 0; font-weight: bold; font-family: monospace;'>" . htmlspecialchars($visaNo) . "</td></tr>
                                    <tr><td style='padding: 4px 0; color: #64748b;'>Expiry Date:</td><td style='padding: 4px 0; font-weight: bold; color: #dc2626;'>" . date('d M Y', $expTime) . "</td></tr>
                                    <tr><td style='padding: 4px 0; color: #64748b;'>Countdown Status:</td><td style='padding: 4px 0; font-weight: bold; color: #dc2626; font-size: 1.05rem;'>" . htmlspecialchars($urgencyLabel) . "</td></tr>
                                    <tr><td style='padding: 4px 0; color: #64748b;'>Visa Validity Status:</td><td style='padding: 4px 0; font-weight: bold; text-transform: uppercase;'>" . htmlspecialchars($status) . "</td></tr>
                                </table>
                            </div>

                            <p style='font-size: 0.9rem; line-height: 1.5; color: #475569;'>
                                Please ensure timely renewal, visa extension, or planned departure prior to the deadline to avoid immigration overstay fines or legal restrictions.
                            </p>

                            <div style='text-align: center; margin: 28px 0;'>
                                <a href='{$portalUrl}' style='background: #dc2626; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>
                                    View Visa in Applicant Portal &rarr;
                                </a>
                            </div>

                            <hr style='border: none; border-top: 1px solid #f1f5f9; margin: 24px 0;'>
                            <p style='font-size: 0.8rem; color: #94a3b8; text-align: center; margin: 0;'>
                                " . App::COMPANY_NAME . " &bull; Automated Consular Expiry Follow-up System
                            </p>
                        </div>
                    </div>
                ";

                $res = EmailService::send([
                    'to' => $applicantEmail,
                    'name' => $v['applicant_name'],
                    'subject' => $subject,
                    'bodyHtml' => $bodyHtml,
                    'data' => [
                        'applicantName' => $v['applicant_name'],
                        'applicationNumber' => $v['application_number'],
                        'visaNumber' => $visaNo,
                        'expiryDate' => date('d M Y', $expTime),
                        'daysRemaining' => (string)$diffDays,
                        'visaStatus' => $status,
                        'actionUrl' => $portalUrl,
                    ],
                ]);

                if (!empty($res['success'])) {
                    $emailSent = true;
                    $stats['emails_sent']++;
                }
            }

            // B. In-App Notification for Applicant & Assigned Staff
            try {
                $notifMsg = "Visa {$visaNo} for {$country} expires on " . date('d M Y', $expTime) . " ({$urgencyLabel}).";
                
                // For customer
                $pdo->prepare("INSERT INTO notifications (user_id, recipient_type, title, message, type, is_read, created_at) VALUES (?, 'Customer', ?, ?, 'warning', 0, CURRENT_TIMESTAMP)")
                    ->execute([(int)$v['customer_id'], "Visa Expiry Notice ({$urgencyLabel})", $notifMsg]);
                
                // For staff
                if (!empty($v['assigned_staff_id'])) {
                    $pdo->prepare("INSERT INTO notifications (user_id, recipient_type, title, message, type, is_read, created_at) VALUES (?, 'Staff', ?, ?, 'warning', 0, CURRENT_TIMESTAMP)")
                        ->execute([(int)$v['assigned_staff_id'], "Applicant Visa Expiry Alert: {$v['applicant_name']}", $notifMsg]);
                }
                $stats['in_app_sent']++;
            } catch (\Throwable $eNotif) {}

            // C. Log to notification_logs
            try {
                $pdo->prepare("INSERT INTO notification_logs (event_type, recipient_type, recipient_id, recipient_name, recipient_email, channel, template_name, subject, content_preview, idempotency_key, status, sent_at) VALUES ('visa.expiry_alert', 'Applicant', ?, ?, ?, 'Email', 'visa_expiry_alert_email', ?, ?, ?, ?, CURRENT_TIMESTAMP)")
                    ->execute([
                        (int)$v['customer_id'],
                        $v['applicant_name'],
                        $applicantEmail ?: 'no-email',
                        "Visa Expiry Follow-up: {$country} ({$urgencyLabel})",
                        "Visa {$visaNo} expiry follow-up {$urgencyLabel}",
                        $idempotencyKey,
                        $emailSent ? 'Sent' : 'InAppOnly'
                    ]);
            } catch (\Throwable $eLog) {}

            $stats['details'][] = [
                'type' => 'Visa',
                'ref' => $v['application_number'],
                'applicant' => $v['applicant_name'],
                'days' => $diffDays,
                'urgency' => $urgencyLabel,
                'email' => $applicantEmail,
            ];
        }

        // 2. SCAN PASSPORTS (customer_passports)
        $passStmt = $pdo->query("
            SELECT cp.*, c.full_name as applicant_name, c.email as applicant_email, c.customer_code
            FROM customer_passports cp
            JOIN customers c ON cp.customer_id = c.id
            WHERE cp.expiry_date IS NOT NULL
        ");
        $passports = $passStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($passports as $p) {
            $stats['scanned_passports']++;
            $expTime = strtotime($p['expiry_date']);
            if (!$expTime) continue;
            $diffDays = (int)round(($expTime - $today) / 86400);

            if (!in_array($diffDays, self::DOCUMENT_MILESTONE_DAYS, true) && !($diffDays < 0 && $diffDays >= -30)) {
                continue;
            }

            $idempotencyKey = "exp_pass_{$p['id']}_{$diffDays}_{$todayYmd}";
            $dupStmt = $pdo->prepare("SELECT id FROM notification_logs WHERE idempotency_key = ? LIMIT 1");
            $dupStmt->execute([$idempotencyKey]);
            if ($dupStmt->fetch()) continue;

            $applicantEmail = trim((string)$p['applicant_email']);
            $urgencyLabel = ($diffDays < 0) ? "EXPIRED (" . abs($diffDays) . " days ago)" : ($diffDays === 0 ? "EXPIRES TODAY" : "Expires in {$diffDays} days");

            if (!empty($applicantEmail)) {
                $res = EmailService::send([
                    'to' => $applicantEmail,
                    'name' => $p['applicant_name'],
                    'subject' => "Passport Expiry Alert: Passport #{$p['passport_number']} ({$urgencyLabel})",
                    'bodyHtml' => "<p>Dear <strong>" . htmlspecialchars($p['applicant_name']) . "</strong>,</p><p>Your passport (<strong>" . htmlspecialchars($p['passport_number']) . "</strong>) recorded in our visa management system has an expiry status of: <strong style='color:#dc2626;'>{$urgencyLabel}</strong> (Expiry Date: " . date('d M Y', $expTime) . ").</p><p>Most consulates and airlines require at least 6 months remaining passport validity. Please arrange passport renewal promptly.</p>",
                ]);
                if (!empty($res['success'])) $stats['emails_sent']++;
            }

            // In-App Notification
            try {
                $pdo->prepare("INSERT INTO notifications (user_id, recipient_type, title, message, type, is_read, created_at) VALUES (?, 'Customer', ?, ?, 'danger', 0, CURRENT_TIMESTAMP)")
                    ->execute([(int)$p['customer_id'], "Passport Expiry Alert ({$urgencyLabel})", "Your passport {$p['passport_number']} expires on " . date('d M Y', $expTime)]);
                $stats['in_app_sent']++;

                $pdo->prepare("INSERT INTO notification_logs (event_type, recipient_type, recipient_id, recipient_name, recipient_email, channel, template_name, subject, content_preview, idempotency_key, status, sent_at) VALUES ('document.expiry_reminder', 'Applicant', ?, ?, ?, 'Email', 'passport_expiry_email', ?, ?, ?, 'Sent', CURRENT_TIMESTAMP)")
                    ->execute([(int)$p['customer_id'], $p['applicant_name'], $applicantEmail, "Passport Expiry Alert", "Passport {$p['passport_number']} {$urgencyLabel}", $idempotencyKey]);
            } catch (\Throwable $e) {}
        }

        // 3. SCAN NATIONAL IDs
        try {
            $nidStmt = $pdo->query("SELECT nid.*, c.full_name as applicant_name, c.email as applicant_email FROM customer_national_ids nid JOIN customers c ON nid.customer_id = c.id WHERE nid.expiry_date IS NOT NULL");
            $nids = $nidStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($nids as $nid) {
                $stats['scanned_national_ids']++;
                $expTime = strtotime($nid['expiry_date']);
                if (!$expTime) continue;
                $diffDays = (int)round(($expTime - $today) / 86400);
                if (!in_array($diffDays, self::DOCUMENT_MILESTONE_DAYS, true)) continue;
                $idempotencyKey = "exp_nid_{$nid['id']}_{$diffDays}_{$todayYmd}";
                $dup = $pdo->prepare("SELECT id FROM notification_logs WHERE idempotency_key = ? LIMIT 1");
                $dup->execute([$idempotencyKey]);
                if ($dup->fetch()) continue;

                $urgency = ($diffDays < 0) ? "EXPIRED" : ($diffDays === 0 ? "EXPIRES TODAY" : "Expires in {$diffDays}d");
                if (!empty($nid['applicant_email'])) {
                    EmailService::send([
                        'to' => $nid['applicant_email'],
                        'name' => $nid['applicant_name'],
                        'subject' => "National ID Expiry Warning: {$nid['id_number']} ({$urgency})",
                        'bodyHtml' => "<p>Dear <strong>" . htmlspecialchars($nid['applicant_name']) . "</strong>,</p><p>Your National ID document (<strong>" . htmlspecialchars($nid['id_number']) . "</strong>) expires on " . date('d M Y', $expTime) . " ({$urgency}). Please keep your records updated.</p>",
                    ]);
                    $stats['emails_sent']++;
                }
                $pdo->prepare("INSERT INTO notification_logs (event_type, recipient_type, recipient_id, recipient_name, recipient_email, channel, template_name, subject, content_preview, idempotency_key, status, sent_at) VALUES ('document.expiry_reminder', 'Applicant', ?, ?, ?, 'Email', 'nid_expiry_email', 'National ID Expiry', 'National ID {$urgency}', ?, 'Sent', CURRENT_TIMESTAMP)")
                    ->execute([(int)$nid['customer_id'], $nid['applicant_name'], $nid['applicant_email'], $idempotencyKey]);
            }
        } catch (\Throwable $e) {}

        // 4. SCAN RESIDENCE PERMITS
        try {
            $resStmt = $pdo->query("SELECT cr.*, c.full_name as applicant_name, c.email as applicant_email FROM customer_residences cr JOIN customers c ON cr.customer_id = c.id WHERE cr.expiry_date IS NOT NULL");
            $resList = $resStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($resList as $r) {
                $stats['scanned_residences']++;
                $expTime = strtotime($r['expiry_date']);
                if (!$expTime) continue;
                $diffDays = (int)round(($expTime - $today) / 86400);
                if (!in_array($diffDays, self::DOCUMENT_MILESTONE_DAYS, true)) continue;
                $idempotencyKey = "exp_res_{$r['id']}_{$diffDays}_{$todayYmd}";
                $dup = $pdo->prepare("SELECT id FROM notification_logs WHERE idempotency_key = ? LIMIT 1");
                $dup->execute([$idempotencyKey]);
                if ($dup->fetch()) continue;

                $urgency = ($diffDays < 0) ? "EXPIRED" : ($diffDays === 0 ? "EXPIRES TODAY" : "Expires in {$diffDays}d");
                if (!empty($r['applicant_email'])) {
                    EmailService::send([
                        'to' => $r['applicant_email'],
                        'name' => $r['applicant_name'],
                        'subject' => "Residence Permit Expiry Alert: {$r['residence_country']} ({$urgency})",
                        'bodyHtml' => "<p>Dear <strong>" . htmlspecialchars($r['applicant_name']) . "</strong>,</p><p>Your Residence Permit for <strong>" . htmlspecialchars($r['residence_country']) . "</strong> expires on " . date('d M Y', $expTime) . " ({$urgency}). Please renew your permit timely.</p>",
                    ]);
                    $stats['emails_sent']++;
                }
                $pdo->prepare("INSERT INTO notification_logs (event_type, recipient_type, recipient_id, recipient_name, recipient_email, channel, template_name, subject, content_preview, idempotency_key, status, sent_at) VALUES ('document.expiry_reminder', 'Applicant', ?, ?, ?, 'Email', 'res_expiry_email', 'Residence Permit Expiry', 'Residence Permit {$urgency}', ?, 'Sent', CURRENT_TIMESTAMP)")
                    ->execute([(int)$r['customer_id'], $r['applicant_name'], $r['applicant_email'], $idempotencyKey]);
            }
        } catch (\Throwable $e) {}

        AuditService::log('EXPIRY_CHECKS_RUN', 'System', null, "Automated expiry scan processed: {$stats['emails_sent']} emails sent, {$stats['in_app_sent']} in-app alerts created");

        return $stats;
    }
}
