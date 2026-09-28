<?php
$pageTitle = "Frequently Asked Questions â€” MS Travel Hub";
$metaDescription = "Answers to common questions about visa processing, document verification, application tracking and recruitment services by MS Travel Hub.";
$currentRoute = '/faq';

ob_start();
?>

<!-- Page Header -->
<div class="pub-page-header">
    <div class="container">
        <h1>Frequently Asked Questions</h1>
        <p>Clear answers about visa processing, document security, tracking and recruitment.</p>
        <nav class="pub-breadcrumb" aria-label="Breadcrumb">
            <a href="/"><i class="fa-solid fa-house" aria-hidden="true"></i> Home</a>
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            <span>FAQ</span>
        </nav>
    </div>
</div>

<div class="py-5" style="background:var(--color-background)">
    <div class="container py-2">
        <div class="row g-5">

            <!-- FAQ Accordion (left) -->
            <div class="col-lg-8">
                <span class="pub-section-eyebrow">Common Questions</span>
                <h2 class="pub-section-title mb-4">Everything You Need to Know</h2>

                <?php
                $faqs = [
                    [
                        'q' => 'How do I track my visa application status?',
                        'a' => 'You can track your application anytime using our <a href="/track" style="color:var(--color-primary)">Public Tracking Portal</a> by entering your Application Reference Number (e.g. MSV-2026-8841) and Passport Number. Registered customers can also log into the <a href="/portal/login" style="color:var(--color-primary)">Applicant Portal</a> for full document downloads and stage history.',
                    ],
                    [
                        'q' => 'What documents are required for UAE & Schengen visas?',
                        'a' => 'Standard requirements include a high-resolution colored passport scan (minimum 6 months validity), passport photo with white background, national ID, and current residency visa. For Schengen visas, bank statements, employment NOCs, and flight/hotel itineraries are also required. Our team will provide a personalized checklist after your enquiry.',
                    ],
                    [
                        'q' => 'How long does visa processing take?',
                        'a' => 'Processing times vary by destination. UAE e-visas usually take 24â€“72 hours. UK, USA, and Schengen visa applications typically take between 5 to 15 working days depending on embassy appointment availability and biometric processing. Express processing is available for urgent cases.',
                    ],
                    [
                        'q' => 'Are my documents secure with MS Travel Hub?',
                        'a' => 'Yes. All customer uploads are stored in an encrypted non-public storage directory outside the web root. Access is strictly controlled through authenticated role-based permissions and 2-factor verification. We are ISO 27001 certified and comply with UAE PDPL data protection standards.',
                    ],
                    [
                        'q' => 'How do I apply for overseas job vacancies?',
                        'a' => 'Visit our <a href="/jobs" style="color:var(--color-primary)">Job Opportunities</a> section, choose your desired vacancy, and fill out the online application form with your updated CV. Our recruitment team will review your qualifications and contact shortlisted candidates within 3â€“5 working days.',
                    ],
                    [
                        'q' => 'Can I cancel or modify my visa application?',
                        'a' => 'Applications can be cancelled before embassy submission with a partial refund of service fees. Once submitted to the embassy, cancellation is subject to embassy policy. Contact our helpline immediately if you need to modify an application â€” our team can advise on the best course of action.',
                    ],
                    [
                        'q' => 'Do you handle group or corporate visa applications?',
                        'a' => 'Yes. We offer dedicated corporate visa packages for businesses sending employees for business meetings, exhibitions, or work assignments. Volume discounts and priority processing lanes are available. Contact our B2B team via the enquiry form for a custom quote.',
                    ],
                    [
                        'q' => 'What happens if my visa is rejected?',
                        'a' => 'In the event of rejection, our consultants will review the reason provided by the embassy and advise on reapplication options. We offer a free re-assessment service and will prepare a stronger application addressing any concerns raised. Rejection fees depend on the specific embassy policy.',
                    ],
                ];
                foreach ($faqs as $i => $faq):
                ?>
                <div class="pub-faq-item" id="faq-<?= $i ?>">
                    <button class="pub-faq-trigger" aria-expanded="false" aria-controls="faq-body-<?= $i ?>">
                        <span><?= $faq['q'] ?></span>
                        <span class="pub-faq-icon" aria-hidden="true">
                            <i class="fa-solid fa-plus"></i>
                        </span>
                    </button>
                    <div class="pub-faq-body" id="faq-body-<?= $i ?>" role="region">
                        <div class="pub-faq-body-inner">
                            <?= $faq['a'] ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Sidebar CTA (right) -->
            <div class="col-lg-4">
                <div class="pub-form-card mb-4">
                    <div class="feature-icon mb-3">
                        <i class="fa-solid fa-headset" aria-hidden="true"></i>
                    </div>
                    <h3 style="font-weight:700;color:var(--color-heading);font-size:1.1rem;margin-bottom:8px">Still have questions?</h3>
                    <p style="font-size:0.87rem;color:var(--color-text-muted);margin-bottom:20px">Our visa consultants are available 7 days a week to help you choose the right service.</p>
                    <a href="/contact" class="btn-brand w-100 justify-content-center mb-3">
                        <i class="fa-solid fa-envelope" aria-hidden="true"></i> Send a Message
                    </a>
                    <a href="/visa-enquiry" class="btn-outline-brand w-100 justify-content-center">
                        <i class="fa-solid fa-rocket" aria-hidden="true"></i> Start Application
                    </a>
                </div>

                <div class="pub-form-card">
                    <h3 style="font-weight:700;color:var(--color-heading);font-size:1rem;margin-bottom:14px">
                        <i class="fa-solid fa-phone me-2" style="color:var(--color-primary)" aria-hidden="true"></i>
                        Direct Helpline
                    </h3>
                    <a href="tel:<?= e(\App\Config\Env::get('COMPANY_PHONE', '0585909349')) ?>"
                       style="font-size:1.2rem;font-weight:700;color:var(--color-primary);text-decoration:none;display:block;margin-bottom:8px">
                        <?= e(\App\Config\Env::get('COMPANY_PHONE', '0585909349')) ?>
                    </a>
                    <p style="font-size:0.82rem;color:var(--color-text-muted);margin:0">
                        Available: Satâ€“Thu, 9amâ€“7pm (GST)
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/public/layout.php';
