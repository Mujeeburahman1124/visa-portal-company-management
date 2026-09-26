<?php
$pageTitle = "Frequently Asked Questions — MS Travel Hub";
$metaDescription = "Answers to common questions about visa processing, document verification, application tracking and recruitment.";
$currentRoute = '/faq';

ob_start();
?>

<div class="py-4 bg-dark text-white text-center">
    <div class="container">
        <h1 class="fw-bold fs-2">Frequently Asked Questions (FAQ)</h1>
        <p class="text-info mb-0">Clear answers regarding visa applications, document verification, tracking and policies.</p>
    </div>
</div>

<div class="py-5">
    <div class="container" style="max-width: 900px;">
        <div class="accordion accordion-flush" id="faqAccordion">

            <div class="accordion-item card card-custom mb-3 overflow-hidden">
                <h2 class="accordion-header">
                    <button class="accordion-button fw-bold text-dark fs-6" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                        How do I track my visa application status?
                    </button>
                </h2>
                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary">
                        You can track your application anytime using our <a href="/track">Public Tracking Portal</a> by entering your Application Reference Number (e.g. MSV-2026-8841) and Passport Number. Customers can also log into the <a href="/portal/login">Applicant Portal</a> for full document downloads.
                    </div>
                </div>
            </div>

            <div class="accordion-item card card-custom mb-3 overflow-hidden">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-bold text-dark fs-6" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                        What documents are required for UAE &amp; Schengen visas?
                    </button>
                </h2>
                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary">
                        Standard requirements include a high-resolution colored passport scan (minimum 6 months validity), passport photo with white background, national ID, and current residency visa. For Schengen visas, bank statements, employment NOCs, and flight/hotel itineraries are also required.
                    </div>
                </div>
            </div>

            <div class="accordion-item card card-custom mb-3 overflow-hidden">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-bold text-dark fs-6" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                        How long does visa processing take?
                    </button>
                </h2>
                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary">
                        Processing times vary by destination. UAE e-visas usually take 24–72 hours. UK, USA, and Schengen visa applications typically take between 5 to 15 working days depending on embassy appointment availability and biometric processing.
                    </div>
                </div>
            </div>

            <div class="accordion-item card card-custom mb-3 overflow-hidden">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-bold text-dark fs-6" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                        Are my documents secure with MS Travel Hub?
                    </button>
                </h2>
                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary">
                        Yes. All customer uploads are stored in an encrypted non-public storage directory outside the web root. Access is strictly controlled through authenticated role-based permissions and 2-factor verification.
                    </div>
                </div>
            </div>

            <div class="accordion-item card card-custom mb-3 overflow-hidden">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-bold text-dark fs-6" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                        How do I apply for overseas job vacancies?
                    </button>
                </h2>
                <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary">
                        Visit our <a href="/jobs">Job Opportunities</a> section, choose your desired vacancy, and fill out the online application form with your updated CV. Our recruitment team will review your qualifications and contact shortlisted candidates.
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/public/layout.php';
