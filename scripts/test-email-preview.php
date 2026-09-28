<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';

use App\Config\Database;
use App\Services\EmailService;

$pdo = Database::getConnection();
$t = $pdo->query("SELECT * FROM email_templates WHERE template_key = 'PASSWORD_RESET'")->fetch(PDO::FETCH_ASSOC);

$sampleData = [
    'user_name' => 'Tariq Al-Mansoor',
    'action_url' => 'http://localhost:8000/auth/reset-password?token=sample123',
    'companyName' => 'MS TRAVEL HUB GLOBAL',
];

$subject = EmailService::interpolate($t['subject'], $sampleData);
$body = EmailService::interpolate($t['body_html'], $sampleData);
$html = EmailService::wrapEmailTemplate($subject, $body, $sampleData);

echo "Subject: {$subject}\n";
echo "HTML Length: " . strlen($html) . " bytes\n";
echo "Has Logo: " . (strpos($html, 'logo.png') !== false ? 'YES' : 'NO') . "\n";
echo "Has Header Background: " . (strpos($html, 'background-color: #0f172a') !== false ? 'YES' : 'NO') . "\n";
echo "Has CTA Button: " . (strpos($html, 'btn-primary') !== false ? 'YES' : 'NO') . "\n";
echo "Has Footer Text: " . (strpos($html, 'Enterprise Visa Operations') !== false ? 'YES' : 'NO') . "\n";
