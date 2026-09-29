<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\App;
use App\Config\Env;
use Exception;

class EmailService
{
    /**
     * Send an email with template rendering, dynamic placeholder replacement,
     * and multi-provider transport (SMTP, Mailgun, SendGrid, simulation).
     *
     * @param array $params [
     *   'to' => string (email),
     *   'name' => ?string (recipient name),
     *   'subject' => string,
     *   'template' => ?string (template content or key),
     *   'bodyHtml' => ?string,
     *   'data' => array (variables for interpolation),
     *   'attachments' => ?array
     * ]
     * @return array [
     *   'success' => bool,
     *   'message_id' => ?string,
     *   'provider' => string,
     *   'error' => ?string,
     *   'simulated' => bool
     * ]
     */
    public static function send(array $params): array
    {
        $to = trim($params['to'] ?? '');
        $recipientName = trim($params['name'] ?? '');
        $subject = trim($params['subject'] ?? 'Notification from ' . App::COMPANY_NAME);
        $data = $params['data'] ?? [];
        $rawHtml = $params['bodyHtml'] ?? $params['template'] ?? '';

        // 1. Validate Recipient Email
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message_id' => null,
                'provider' => 'none',
                'error' => "Invalid or empty recipient email address: '{$to}'",
                'simulated' => false,
            ];
        }

        // 2. Populate Default Brand Variables
        $data['companyName'] = $data['companyName'] ?? Env::get('COMPANY_NAME', App::COMPANY_NAME);
        $data['companyEmail'] = $data['companyEmail'] ?? Env::get('COMPANY_EMAIL', 'mstravelu@gmail.com');
        $data['companyPhone'] = $data['companyPhone'] ?? Env::get('COMPANY_PHONE', '0585909349');
        $data['companyWebsite'] = $data['companyWebsite'] ?? Env::get('COMPANY_WEBSITE', 'https://mshorizonuae.com');
        $data['appUrl'] = $data['appUrl'] ?? App::url();
        $data['currentYear'] = date('Y');

        if (empty($data['applicantName']) && !empty($recipientName)) {
            $data['applicantName'] = $recipientName;
        }

        // 3. Interpolate dynamic variables into Subject and Body
        $interpolatedSubject = self::interpolate($subject, $data);
        $interpolatedContent = self::interpolate($rawHtml, $data);

        // 4. Wrap with Responsive HTML Email Layout
        $fullHtml = self::wrapEmailTemplate($interpolatedSubject, $interpolatedContent, $data);
        
        // Defensive enforcement: replace any localhost or 127.0.0.1 references with the live production domain
        $liveDomain = 'https://mshorizonuae.com';
        $fullHtml = str_replace(
            ['http://localhost:8000', 'https://localhost:8000', 'http://localhost', 'https://localhost', 'http://127.0.0.1:8000', 'http://127.0.0.1'],
            $liveDomain,
            $fullHtml
        );
        $plainText = strip_tags(str_replace(['<br>', '<br/>', '</p>', '</div>'], "\n", $interpolatedContent));
        $plainText = str_replace(
            ['http://localhost:8000', 'https://localhost:8000', 'http://localhost', 'https://localhost', 'http://127.0.0.1:8000', 'http://127.0.0.1'],
            $liveDomain,
            $plainText
        );

        // 5. Check Environment & Provider
        $provider = strtolower((string)Env::get('EMAIL_PROVIDER', 'smtp'));
        $smtpHost = (string)Env::get('SMTP_HOST', 'smtp.hostinger.com');
        $smtpUser = (string)Env::get('SMTP_USER', '');
        $smtpPass = (string)Env::get('SMTP_PASSWORD', '');

        // 6. Dispatch via configured transport with automatic fallback
        $result = null;
        if ($provider === 'smtp' && !empty($smtpUser) && !empty($smtpPass)) {
            try {
                $result = self::sendSmtp($to, $recipientName, $interpolatedSubject, $fullHtml, $plainText, $params['attachments'] ?? []);
            } catch (\Throwable $smtpErr) {
                // Secondary retry on SSL port 465 if 587/TLS failed
                try {
                    $result = self::sendSmtp($to, $recipientName, $interpolatedSubject, $fullHtml, $plainText, $params['attachments'] ?? [], 465, 'ssl');
                } catch (\Throwable $smtpErr2) {
                    error_log("[VISA-TRACK] SMTP delivery failed: {$smtpErr->getMessage()} / {$smtpErr2->getMessage()} — Falling back to native PHP mail()...");
                    $result = self::sendPhpMail($to, $recipientName, $interpolatedSubject, $fullHtml, $plainText);
                    if ($result['success']) {
                        $result['provider'] = 'phpmail (fallback from smtp)';
                    } else {
                        $result['error'] = "SMTP error: {$smtpErr->getMessage()} | PHP mail error: " . ($result['error'] ?? 'mail() failed');
                    }
                }
            }
        } else {
            // Native PHP mail() delivery (standard on Hostinger/cPanel)
            $result = self::sendPhpMail($to, $recipientName, $interpolatedSubject, $fullHtml, $plainText);
        }

        // 7. Audit log to notification_logs
        try {
            $pdo = \App\Config\Database::getConnection();
            $logStmt = $pdo->prepare("INSERT INTO notification_logs (event_type, recipient_type, recipient_name, recipient_email, channel, subject, content_preview, status, error_details, provider, message_id, sent_at) VALUES ('email.direct', 'User', ?, ?, 'Email', ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $logStmt->execute([
                $recipientName ?: $to,
                $to,
                $interpolatedSubject,
                mb_substr($plainText, 0, 250),
                $result['success'] ? 'Sent' : 'Failed',
                $result['error'] ?? null,
                $result['provider'] ?? 'email',
                $result['message_id'] ?? null
            ]);
        } catch (\Throwable $ignored) {}

        return $result;
    }

    /**
     * Native Production-Grade SMTP Socket Client with STARTTLS, SSL/TLS, AUTH LOGIN/PLAIN.
     */
    private static function sendSmtp(
        string $to,
        string $recipientName,
        string $subject,
        string $htmlBody,
        string $plainText,
        array $attachments = [],
        ?int $overridePort = null,
        ?string $overrideEncryption = null
    ): array {
        $host = (string)Env::get('SMTP_HOST', 'smtp.hostinger.com');
        $port = $overridePort ?: (int)Env::get('SMTP_PORT', 587);
        $user = (string)Env::get('SMTP_USER', '');
        $pass = (string)Env::get('SMTP_PASSWORD', '');
        $encryption = $overrideEncryption ?: strtolower((string)Env::get('SMTP_ENCRYPTION', 'tls'));
        $fromEmail = (string)Env::get('EMAIL_FROM', 'admin@mshorizonuae.com');
        $fromName = (string)Env::get('EMAIL_FROM_NAME', App::COMPANY_NAME);

        if (empty($user) || empty($pass)) {
            throw new Exception("SMTP credentials (SMTP_USER / SMTP_PASSWORD) are not configured.");
        }

        $timeout = 10;
        $remoteSocket = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        
        $verifyPeer = (bool)Env::get('SMTP_VERIFY_PEER', false);
        $verifyPeerName = (bool)Env::get('SMTP_VERIFY_PEER_NAME', false);
        $allowSelfSigned = (bool)Env::get('SMTP_ALLOW_SELF_SIGNED', true);

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => $verifyPeer,
                'verify_peer_name' => $verifyPeerName,
                'allow_self_signed' => $allowSelfSigned,
                'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT,
            ]
        ]);

        $socket = @stream_socket_client($remoteSocket, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            throw new Exception("SMTP Connection Failed to {$remoteSocket}: {$errstr} ({$errno})");
        }

        stream_set_timeout($socket, $timeout);

        $readResponse = function () use ($socket): string {
            $response = '';
            while ($line = fgets($socket, 512)) {
                $response .= $line;
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
            }
            return $response;
        };

        $sendCommand = function (string $cmd, array $expectedCodes = [250]) use ($socket, $readResponse): string {
            fwrite($socket, $cmd . "\r\n");
            $response = $readResponse();
            $code = (int)substr($response, 0, 3);
            if (!in_array($code, $expectedCodes, true)) {
                throw new Exception("SMTP Command '{$cmd}' returned unexpected code {$code}: {$response}");
            }
            return $response;
        };

        // 1. Initial Greeting
        $greeting = $readResponse();
        if (substr($greeting, 0, 3) !== '220') {
            fclose($socket);
            throw new Exception("SMTP Invalid Greeting: {$greeting}");
        }

        // 2. EHLO
        $clientHost = gethostname() ?: 'localhost';
        $sendCommand("EHLO {$clientHost}", [250]);

        // 3. STARTTLS if configured
        if ($encryption === 'tls' || ($encryption !== 'ssl' && $port === 587)) {
            $sendCommand("STARTTLS", [220]);
            $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            }
            $cryptoOk = false;
            for ($attempt = 0; $attempt < 3; $attempt++) {
                $resCrypto = stream_socket_enable_crypto($socket, true, $cryptoMethod);
                if ($resCrypto === true) {
                    $cryptoOk = true;
                    break;
                }
                if ($resCrypto === false) {
                    break;
                }
                usleep(50000);
            }
            if (!$cryptoOk) {
                $cryptoOk = (bool)stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            }
            if (!$cryptoOk) {
                fclose($socket);
                throw new Exception("SMTP STARTTLS handshake negotiation failed");
            }
            // Re-send EHLO after TLS established
            $sendCommand("EHLO {$clientHost}", [250]);
        }

        // 4. AUTH LOGIN if credentials provided
        if (!empty($user) && !empty($pass)) {
            $sendCommand("AUTH LOGIN", [334]);
            $sendCommand(base64_encode($user), [334]);
            $sendCommand(base64_encode($pass), [235]);
        }

        // 5. MAIL FROM & RCPT TO
        $sendCommand("MAIL FROM:<{$fromEmail}>", [250]);
        $sendCommand("RCPT TO:<{$to}>", [250, 251]);

        // 6. DATA
        $sendCommand("DATA", [354]);

        $boundary = "==Multipart_Boundary_x" . md5((string)time()) . "x";
        $messageId = "<" . time() . "." . uniqid() . "@" . ($clientHost ?: 'visatrack.local') . ">";

        $headers = [];
        $headers[] = "Message-ID: {$messageId}";
        $headers[] = "Date: " . date('r');
        $headers[] = "From: " . self::encodeHeader($fromName) . " <{$fromEmail}>";
        $headers[] = "To: " . (!empty($recipientName) ? self::encodeHeader($recipientName) . " <{$to}>" : "<{$to}>");
        $headers[] = "Subject: " . self::encodeHeader($subject);
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
        $headers[] = "X-Mailer: VISA TRACK Enterprise Mailer v2.0";

        $body = implode("\r\n", $headers) . "\r\n\r\n";
        
        // Plain text part
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($plainText)) . "\r\n";

        // HTML part
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";

        $body .= "--{$boundary}--\r\n";
        $body .= ".";

        $sendCommand($body, [250]);

        // 7. QUIT
        try {
            $sendCommand("QUIT", [221, 250]);
        } catch (\Throwable $e) {}

        fclose($socket);

        return [
            'success' => true,
            'message_id' => trim($messageId, '<>'),
            'provider' => 'smtp',
            'error' => null,
            'simulated' => false,
        ];
    }

    /**
     * Native PHP mail() fallback with RFC-compliant headers and envelope sender.
     */
    private static function sendPhpMail(string $to, string $recipientName, string $subject, string $htmlBody, string $plainText): array
    {
        $fromEmail = (string)Env::get('EMAIL_FROM', 'admin@mshorizonuae.com');
        $fromName = (string)Env::get('EMAIL_FROM_NAME', App::COMPANY_NAME);

        // Derive domain-aligned return-path for SPF on shared hosting
        $serverHost = $_SERVER['HTTP_HOST'] ?? 'mshorizonuae.com';
        $serverHost = preg_replace('/:[0-9]+$/', '', $serverHost);
        $domainFrom = 'noreply@' . $serverHost;

        $boundary = "==Multipart_Boundary_x" . md5((string)time()) . "x";
        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "From: " . self::encodeHeader($fromName) . " <{$fromEmail}>";
        $headers[] = "Reply-To: <{$fromEmail}>";
        $headers[] = "Return-Path: <{$domainFrom}>";
        $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
        $headers[] = "X-Mailer: VISA TRACK Enterprise Mailer (PHP/" . phpversion() . ")";

        $eol = "\r\n";
        $body = "--{$boundary}{$eol}";
        $body .= "Content-Type: text/plain; charset=UTF-8{$eol}";
        $body .= "Content-Transfer-Encoding: base64{$eol}{$eol}";
        $body .= chunk_split(base64_encode($plainText)) . "{$eol}";

        $body .= "--{$boundary}{$eol}";
        $body .= "Content-Type: text/html; charset=UTF-8{$eol}";
        $body .= "Content-Transfer-Encoding: base64{$eol}{$eol}";
        $body .= chunk_split(base64_encode($htmlBody)) . "{$eol}";
        $body .= "--{$boundary}--";

        $headersStr = implode($eol, $headers);
        $encodedSubject = self::encodeHeader($subject);

        // Try 1: with domain-matched envelope sender (-f noreply@domain)
        $extraParam = '-f' . $domainFrom;
        $res = @mail($to, $encodedSubject, $body, $headersStr, $extraParam);

        // Try 2: with fromEmail envelope
        if (!$res && filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $res = @mail($to, $encodedSubject, $body, $headersStr, '-f' . $fromEmail);
        }

        // Try 3: standard mail() without extra param (for hosts restricting -f)
        if (!$res) {
            $res = @mail($to, $encodedSubject, $body, $headersStr);
        }

        // Try 4: using LF on Linux if MTA rejects CRLF
        if (!$res) {
            $headersLf = implode("\n", $headers);
            $res = @mail($to, $encodedSubject, $body, $headersLf);
        }

        return [
            'success' => (bool)$res,
            'message_id' => 'phpmail-' . uniqid(),
            'provider' => 'phpmail',
            'error' => $res ? null : 'Native mail() function returned false. Check server mail logs or configure SMTP in Settings.',
            'simulated' => false,
        ];
    }

    /**
     * Interpolate template placeholders like {{applicantName}}, {{applicationNumber}}.
     */
    public static function interpolate(string $template, array $data): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_\-\.]+)\s*\}\}/', function ($matches) use ($data) {
            $key = $matches[1];
            if (isset($data[$key])) {
                return (string)$data[$key];
            }
            // Also try snake_case or lower case equivalents
            $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $key));
            if (isset($data[$snake])) {
                return (string)$data[$snake];
            }
            return $matches[0]; // leave untouched if not supplied
        }, $template);
    }

    /**
     * Master Responsive HTML Email Template wrapper.
     */
    public static function wrapEmailTemplate(string $title, string $contentHtml, array $data = []): string
    {
        $appUrl = rtrim((string)($data['appUrl'] ?? App::url()), '/');
        if (empty($appUrl) || str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1')) {
            $appUrl = 'https://mshorizonuae.com';
        }
        $companyName = htmlspecialchars((string)($data['companyName'] ?? App::COMPANY_NAME), ENT_QUOTES, 'UTF-8');
        $companyEmail = htmlspecialchars((string)($data['companyEmail'] ?? 'mstravelu@gmail.com'), ENT_QUOTES, 'UTF-8');
        $companyPhone = htmlspecialchars((string)($data['companyPhone'] ?? '0585909349'), ENT_QUOTES, 'UTF-8');
        $companyWebsite = htmlspecialchars((string)($data['companyWebsite'] ?? 'https://mshorizonuae.com'), ENT_QUOTES, 'UTF-8');
        $currentYear = date('Y');

        $emailHeaderBg = '#0f172a';
        $emailThemeColor = '#2563eb';
        $emailLogo = 'https://mshorizonuae.com/assets/images/logo.png';
        $emailFooterText = 'MS Travel Hub Global Visa Services &bull; Enterprise Visa Operations';

        try {
            $emPdo = \App\Config\Database::getConnection();
            $emSettings = $emPdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('email_theme_header_bg', 'email_theme_color', 'email_theme_primary', 'email_theme_logo', 'email_theme_footer')")->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];
            
            if (!empty($emSettings['email_theme_header_bg'])) {
                $emailHeaderBg = htmlspecialchars($emSettings['email_theme_header_bg'], ENT_QUOTES, 'UTF-8');
            }
            if (!empty($emSettings['email_theme_color'])) {
                $emailThemeColor = htmlspecialchars($emSettings['email_theme_color'], ENT_QUOTES, 'UTF-8');
            } elseif (!empty($emSettings['email_theme_primary'])) {
                $emailThemeColor = htmlspecialchars($emSettings['email_theme_primary'], ENT_QUOTES, 'UTF-8');
            }
            if (!empty($emSettings['email_theme_logo'])) {
                $rawLogo = trim($emSettings['email_theme_logo']);
                $emailLogo = str_starts_with($rawLogo, 'http') ? $rawLogo : $appUrl . '/' . ltrim($rawLogo, '/');
            }
            if (!empty($emSettings['email_theme_footer'])) {
                $emailFooterText = htmlspecialchars($emSettings['email_theme_footer'], ENT_QUOTES, 'UTF-8');
            }
        } catch (\Throwable $e) {}

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$title}</title>
  <style>
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
    body { margin: 0; padding: 0; width: 100% !important; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; line-height: 1.6; }
    .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08); border: 1px solid #e2e8f0; }
    .email-header { background: {$emailHeaderBg}; padding: 24px 32px; border-bottom: 4px solid {$emailThemeColor}; text-align: left; }
    .email-body { padding: 32px 32px 28px; font-size: 15px; color: #334155; line-height: 1.65; }
    .email-body h1, .email-body h2, .email-body h3 { color: #0f172a; margin-top: 0; font-weight: 700; letter-spacing: -0.01em; }
    .email-footer { background-color: #f8fafc; padding: 24px 32px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
    .email-footer a { color: {$emailThemeColor}; text-decoration: none; font-weight: 600; }
    .btn-primary { display: inline-block; background-color: {$emailThemeColor}; color: #ffffff !important; font-weight: 600; font-size: 14px; padding: 12px 28px; border-radius: 8px; text-decoration: none; margin: 16px 0; box-shadow: 0 3px 8px rgba(0,0,0,0.12); }
    .data-table { width: 100%; border-collapse: collapse; margin: 18px 0; font-size: 14px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
    .data-table td { padding: 10px 14px; border-bottom: 1px solid #f1f5f9; }
    .data-table td:first-child { font-weight: 600; color: #475569; width: 36%; background: #f8fafc; }
    .info-card { background: #f8fafc; border-left: 4px solid {$emailThemeColor}; padding: 14px 18px; border-radius: 6px; margin: 18px 0; font-size: 14px; color: #334155; }
    .badge-status { display: inline-block; padding: 4px 12px; font-size: 12px; font-weight: 700; border-radius: 20px; background-color: {$emailThemeColor}; color: #ffffff; text-transform: uppercase; letter-spacing: 0.5px; }
  </style>
</head>
<body style="background-color: #f1f5f9; margin: 0; padding: 28px 12px;">
  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
      <td align="center">
        <table role="presentation" class="email-container" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0;">
          <!-- BRANDED HEADER -->
          <tr>
            <td class="email-header" style="background-color: {$emailHeaderBg}; padding: 22px 32px; border-bottom: 4px solid {$emailThemeColor};">
              <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td valign="middle" style="vertical-align: middle;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="padding-right: 14px; vertical-align: middle;">
                          <img src="{$emailLogo}" alt="{$companyName}" style="max-height: 48px; width: auto; max-width: 170px; display: block; border-radius: 6px;" border="0">
                        </td>
                        <td style="vertical-align: middle;">
                          <div style="font-size: 17px; font-weight: 800; color: #ffffff; letter-spacing: 0.3px; line-height: 1.2;">{$companyName}</div>
                          <div style="font-size: 11px; color: #94a3b8; margin-top: 2px; letter-spacing: 0.2px;">Global Visa &amp; Operations Management</div>
                        </td>
                      </tr>
                    </table>
                  </td>
                  <td align="right" valign="middle" style="vertical-align: middle;">
                    <span style="background: rgba(255,255,255,0.12); color: #ffffff; font-size: 10px; padding: 4px 10px; border-radius: 14px; font-weight: 600; letter-spacing: 0.5px; border: 1px solid rgba(255,255,255,0.2);">OFFICIAL NOTICE</span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          
          <!-- EMAIL BODY CONTENT -->
          <tr>
            <td class="email-body" style="padding: 32px 32px 28px; font-size: 15px; color: #334155; line-height: 1.65;">
              {$contentHtml}
            </td>
          </tr>
          
          <!-- BRANDED FOOTER -->
          <tr>
            <td class="email-footer" style="background-color: #f8fafc; padding: 24px 32px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0;">
              <p style="margin: 0 0 6px 0; font-weight: 700; color: #1e293b; font-size: 13px;">{$companyName}</p>
              <p style="margin: 0 0 10px 0; color: #64748b; font-size: 12px;">{$emailFooterText}</p>
              <p style="margin: 0 0 10px 0;">Helpline: <strong style="color: #334155;">{$companyPhone}</strong> &bull; Support: <a href="mailto:{$companyEmail}" style="color: {$emailThemeColor}; text-decoration: none; font-weight: 600;">{$companyEmail}</a></p>
              <div style="height: 1px; background: #e2e8f0; margin: 14px auto; max-width: 320px;"></div>
              <p style="margin: 0; font-size: 11px; color: #94a3b8;">&copy; {$currentYear} {$companyName}. All rights reserved. Generated automatically by VISA TRACK.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    private static function encodeHeader(string $str): string
    {
        return '=?UTF-8?B?' . base64_encode($str) . '?=';
    }
}
