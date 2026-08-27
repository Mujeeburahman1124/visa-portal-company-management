<?php
$pageTitle = 'Applicant Help & Support Desk — VISA TRACK';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220%22%20%22100%22><text y=%22.9em%22 font-size=%2290%22>✈️</text></svg>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/main.css?v=7.0.0">
</head>
<body class="app-body">

<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="container py-4">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
      <h3 class="fw-bold brand-font text-dark mb-1">Applicant Help &amp; Support Desk</h3>
      <p class="text-muted small mb-0">Get in touch with our visa operations support team or consult answers to frequently asked questions.</p>
    </div>
  </div>

  <div class="row g-4">
    <!-- Contact Channels & Inquiry Form Column -->
    <div class="col-lg-6">
      <!-- Quick Contact Cards -->
      <div class="row g-3 mb-4">
        <div class="col-sm-6">
          <div class="p-3.5 bg-white rounded-3 border shadow-sm h-100">
            <div class="fs-3 text-success mb-2"><i class="fa-brands fa-whatsapp"></i></div>
            <div class="fw-bold text-dark fs-6">Official WhatsApp Desk</div>
            <div class="text-muted small mb-2">Direct assistance with document prep</div>
            <a href="https://wa.me/971501112233" target="_blank" class="btn btn-outline-success btn-sm w-100 fw-semibold">
              Chat on WhatsApp &rarr;
            </a>
          </div>
        </div>

        <div class="col-sm-6">
          <div class="p-3.5 bg-white rounded-3 border shadow-sm h-100">
            <div class="fs-3 text-primary mb-2"><i class="fa-solid fa-phone-volume"></i></div>
            <div class="fw-bold text-dark fs-6">Support Hotline</div>
            <div class="text-muted small mb-2"><?= e($settings['company_phone'] ?? '+971 4 388 9900') ?></div>
            <div class="text-secondary small">Mon - Sat: 9:00 AM - 6:00 PM</div>
          </div>
        </div>
      </div>

      <!-- Submit Message to Case Officer Form -->
      <div class="card card-enterprise shadow-sm border">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-paper-plane text-primary me-2"></i> Send Message to Visa Processing Desk</h6>
        </div>
        <div class="card-body p-4">
          <form action="/portal/support/inquiry" method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Inquiry Subject <span class="text-danger">*</span></label>
              <input type="text" name="subject" class="form-control" placeholder="e.g. Question regarding my Schengen flight itinerary" required>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold">Detailed Message <span class="text-danger">*</span></label>
              <textarea name="message" class="form-control" rows="4" placeholder="Type your question or message for your case officer..." required></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm w-100">
              <i class="fa-solid fa-paper-plane me-1"></i> Send Inquiry
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- FAQ Accordion Column -->
    <div class="col-lg-6">
      <div class="card card-enterprise shadow-sm border">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-circle-question text-primary me-2"></i> Frequently Asked Questions</h6>
        </div>
        <div class="card-body p-3">
          <div class="accordion accordion-flush" id="faqAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button fw-semibold small text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                  How do I know when my visa is approved?
                </button>
              </h2>
              <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                <div class="accordion-body small text-secondary">
                  As soon as consular authorities issue your visa decision, the status on your dashboard updates immediately to <strong>Approved</strong>, and an electronic copy will be available for instant download.
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed fw-semibold small text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                  What if a document is rejected or needs replacement?
                </button>
              </h2>
              <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body small text-secondary">
                  If an officer flags an issue (e.g. blurry scan or missing page), you will see an <strong>ACTION REQUIRED</strong> alert on your dashboard with the exact reason. Simply click the Upload button to provide a replacement copy.
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed fw-semibold small text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                  What should I bring to my biometrics appointment?
                </button>
              </h2>
              <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body small text-secondary">
                  Always bring your original valid passport, the official appointment confirmation letter, and physical copies of your application dossier as indicated on your appointment card.
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed fw-semibold small text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                  How are processing timelines estimated?
                </button>
              </h2>
              <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body small text-secondary">
                  Timelines shown in the portal are standard target milestones. Official processing times are ultimately determined by respective government embassies and consular clearing authorities.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

