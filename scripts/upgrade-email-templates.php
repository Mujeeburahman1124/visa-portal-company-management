<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';

use App\Config\Database;

$pdo = Database::getConnection();

$upgradedTemplates = [
    'PASSWORD_RESET' => [
        'subject' => 'Password Reset Request — {{companyName}}',
        'body_html' => '
<div style="text-align: center; margin-bottom: 24px;">
  <span class="badge-status" style="background-color: #2563eb;">SECURITY NOTICE</span>
  <h2 style="margin: 14px 0 6px 0; color: #0f172a; font-size: 22px;">Reset Your Account Password</h2>
  <p style="color: #64748b; font-size: 14px; margin: 0;">We received a request to reset the login credentials for your operational account.</p>
</div>

<p>Hello <strong>{{user_name}}</strong>,</p>

<p>Your security credentials for the <strong>MS TRAVEL HUB Global Operations Portal</strong> have been requested for reset. Click the secure link below to establish your new password:</p>

<div style="text-align: center; margin: 28px 0;">
  <a href="{{action_url}}" class="btn-primary" style="font-size: 15px; padding: 13px 32px;">
    Reset My Password &rarr;
  </a>
</div>

<div class="info-card">
  <strong>Security Advisory:</strong> This password reset link is valid for <strong>60 minutes</strong> and can only be used once. If you did not initiate this request, you can safely ignore this email; your existing password remains secure.
</div>

<p style="font-size: 13px; color: #94a3b8; margin-top: 24px;">
  Button not working? Copy and paste this URL into your browser:<br>
  <span style="color: #2563eb; word-break: break-all;">{{action_url}}</span>
</p>
'
    ],
    'APP_REGISTERED' => [
        'subject' => 'Visa Application Registered: {{application_number}} — {{destination_country}}',
        'body_html' => '
<div style="text-align: center; margin-bottom: 24px;">
  <span class="badge-status" style="background-color: #10b981;">APPLICATION REGISTERED</span>
  <h2 style="margin: 14px 0 6px 0; color: #0f172a; font-size: 22px;">Visa Application Confirmed</h2>
  <p style="color: #64748b; font-size: 14px; margin: 0;">Your visa filing has been successfully submitted and logged into our processing pipeline.</p>
</div>

<p>Dear <strong>{{customer_name}}</strong>,</p>

<p>Thank you for choosing <strong>MS TRAVEL HUB</strong>. Your visa application has been officially registered under tracking reference <strong>{{application_number}}</strong>.</p>

<table class="data-table">
  <tr>
    <td>Application Ref</td>
    <td><strong style="color: #0f172a;">{{application_number}}</strong></td>
  </tr>
  <tr>
    <td>Applicant Name</td>
    <td>{{customer_name}}</td>
  </tr>
  <tr>
    <td>Visa Service</td>
    <td>{{visa_service}}</td>
  </tr>
  <tr>
    <td>Destination</td>
    <td>{{destination_country}}</td>
  </tr>
  <tr>
    <td>Current Status</td>
    <td><span style="color: #2563eb; font-weight: 700;">Document Screening &amp; Verification</span></td>
  </tr>
</table>

<div style="text-align: center; margin: 26px 0;">
  <a href="{{action_url}}" class="btn-primary">
    Track Live Application Status &rarr;
  </a>
</div>

<div class="info-card">
  <strong>Next Milestone:</strong> Our dedicated processing officers are currently reviewing your uploaded documents. We will notify you immediately if any additional consular attestations or documents are required.
</div>
'
    ],
    'APP_UPDATED' => [
        'subject' => 'Visa Progress Update: {{application_number}} is now in {{current_stage}}',
        'body_html' => '
<div style="text-align: center; margin-bottom: 24px;">
  <span class="badge-status" style="background-color: #0284c7;">LIFECYCLE PROGRESS</span>
  <h2 style="margin: 14px 0 6px 0; color: #0f172a; font-size: 22px;">Application Stage Milestone</h2>
  <p style="color: #64748b; font-size: 14px; margin: 0;">Your application has reached a new operational milestone.</p>
</div>

<p>Dear <strong>{{applicant_name}}</strong>,</p>

<p>We are pleased to inform you that your visa file has successfully advanced to the next processing stage: <strong style="color: #0284c7;">{{current_stage}}</strong>.</p>

<table class="data-table">
  <tr>
    <td>Tracking Number</td>
    <td><strong style="color: #0f172a;">{{application_number}}</strong></td>
  </tr>
  <tr>
    <td>Current Stage</td>
    <td><strong style="color: #0284c7;">{{current_stage}}</strong></td>
  </tr>
  <tr>
    <td>Next Operational Action</td>
    <td>{{next_action}}</td>
  </tr>
  <tr>
    <td>Destination Country</td>
    <td>{{destination_country}}</td>
  </tr>
</table>

<div style="text-align: center; margin: 26px 0;">
  <a href="{{action_url}}" class="btn-primary">
    View Realtime Tracking Timeline &rarr;
  </a>
</div>

<p style="font-size: 13px; color: #64748b;">If you have any questions regarding this update, please reply to this email or contact your assigned case officer.</p>
'
    ],
    'VISA_APPROVED' => [
        'subject' => 'Congratulations! Your Visa is Approved — {{application_number}}',
        'body_html' => '
<div style="text-align: center; margin-bottom: 24px;">
  <span class="badge-status" style="background-color: #16a34a;">OFFICIALLY APPROVED</span>
  <h2 style="margin: 14px 0 6px 0; color: #0f172a; font-size: 24px;">🎉 Visa Granted Successfully!</h2>
  <p style="color: #64748b; font-size: 14px; margin: 0;">Your visa has been officially issued by the immigration authorities.</p>
</div>

<p>Dear <strong>{{customer_name}}</strong>,</p>

<p>We are delighted to confirm that your visa application for <strong style="color: #0f172a;">{{destination_country}}</strong> has been granted and your official electronic visa document is ready for download.</p>

<table class="data-table">
  <tr>
    <td>Application Ref</td>
    <td><strong>{{application_number}}</strong></td>
  </tr>
  <tr>
    <td>Visa Number</td>
    <td><strong style="color: #16a34a; font-size: 15px;">{{visa_number}}</strong></td>
  </tr>
  <tr>
    <td>Destination Country</td>
    <td>{{destination_country}}</td>
  </tr>
  <tr>
    <td>Validity / Stay</td>
    <td>Valid for entry as per consular endorsement</td>
  </tr>
</table>

<div style="text-align: center; margin: 28px 0;">
  <a href="{{action_url}}" class="btn-primary" style="background-color: #16a34a; font-size: 15px;">
    Download Official Visa Document &rarr;
  </a>
</div>

<div class="info-card" style="border-left-color: #16a34a;">
  <strong>Travel Advisory:</strong> Please print a physical copy of your e-Visa to carry alongside your valid passport when traveling. Ensure your passport remains valid for at least 6 months from your travel date.
</div>
'
    ],
    'DOC_REQUIRED' => [
        'subject' => 'Action Required: Additional Document Needed for {{application_number}}',
        'body_html' => '
<div style="text-align: center; margin-bottom: 24px;">
  <span class="badge-status" style="background-color: #f59e0b;">ACTION REQUIRED</span>
  <h2 style="margin: 14px 0 6px 0; color: #0f172a; font-size: 22px;">Document Upload Needed</h2>
  <p style="color: #64748b; font-size: 14px; margin: 0;">Additional documentation is required to avoid consular delays.</p>
</div>

<p>Dear <strong>{{customer_name}}</strong>,</p>

<p>To proceed with consular filing for application <strong>{{application_number}}</strong>, we require you to upload the following document:</p>

<div class="info-card" style="border-left-color: #f59e0b; background-color: #fffbeb;">
  <div style="font-weight: 700; color: #92400e; font-size: 15px;">{{document_name}}</div>
  <div style="color: #78350f; font-size: 13px; margin-top: 4px;">{{document_instructions}}</div>
</div>

<div style="text-align: center; margin: 26px 0;">
  <a href="{{action_url}}" class="btn-primary" style="background-color: #f59e0b;">
    Upload Document Now &rarr;
  </a>
</div>

<p style="font-size: 13px; color: #64748b;">Please upload high-resolution colored scans (PDF, JPG, or PNG, max 10MB) to ensure prompt verification.</p>
'
    ],
];

foreach ($upgradedTemplates as $key => $data) {
    $stmt = $pdo->prepare("UPDATE email_templates SET subject = ?, body_html = ?, updated_at = CURRENT_TIMESTAMP WHERE template_key = ?");
    $stmt->execute([$data['subject'], trim($data['body_html']), $key]);
    echo "Upgraded template: {$key}\n";
}

echo "All templates upgraded successfully!\n";
