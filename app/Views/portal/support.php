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
    <div class="col-12 col-lg-6">
      <!-- Quick Contact Cards (Fluid 2-Column Responsive Grid) -->
      <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6">
          <div class="p-3.5 p-md-4 bg-white rounded-3 border shadow-sm h-100 d-flex flex-column justify-content-between transition-all">
            <div>
              <div class="d-flex align-items-center gap-2 mb-2">
                <div class="fs-3 text-success p-2 rounded-3 bg-success-subtle d-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                  <i class="fa-brands fa-whatsapp"></i>
                </div>
                <div class="fw-bold text-dark fs-6">Official WhatsApp Desk</div>
              </div>
              <div class="text-muted small mb-3">Direct real-time assistance with document prep and visa queries.</div>
            </div>
            <a href="https://wa.me/971501112233" target="_blank" class="btn btn-outline-success btn-sm w-100 fw-semibold shadow-sm">
              <i class="fa-brands fa-whatsapp me-1.5"></i> Chat on WhatsApp &rarr;
            </a>
          </div>
        </div>

        <div class="col-12 col-sm-6">
          <div class="p-3.5 p-md-4 bg-white rounded-3 border shadow-sm h-100 d-flex flex-column justify-content-between transition-all">
            <div>
              <div class="d-flex align-items-center gap-2 mb-2">
                <div class="fs-4 text-primary p-2 rounded-3 bg-primary-subtle d-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                  <i class="fa-solid fa-headset"></i>
                </div>
                <div class="fw-bold text-dark fs-6">Support Hotline</div>
              </div>
              <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $settings['company_phone'] ?? '+97143889900')) ?>" class="text-decoration-none text-dark fw-bold fs-6 d-block mb-1">
                <i class="fa-solid fa-phone me-1 text-primary"></i><?= e($settings['company_phone'] ?? '+971 4 388 9900') ?>
              </a>
              <div class="text-secondary small mb-3"><i class="fa-regular fa-clock me-1 text-muted"></i>Mon - Sat: 9:00 AM - 6:00 PM</div>
            </div>
            <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $settings['company_phone'] ?? '+97143889900')) ?>" class="btn btn-outline-primary btn-sm w-100 fw-semibold shadow-sm">
              <i class="fa-solid fa-phone me-1.5"></i> Call Hotline Now
            </a>
          </div>
        </div>
      </div>

      <!-- Submit Message to Case Officer Form -->
      <div class="card card-enterprise shadow-sm border">
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
          <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-paper-plane text-primary fs-5"></i>
            <span>Send Message to Visa Processing Desk</span>
          </h6>
        </div>
        <div class="card-body p-3 p-md-4">
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

            <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm w-100 py-2.5">
              <i class="fa-solid fa-paper-plane me-1.5"></i> Send Inquiry to Support
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- FAQ Accordion Column -->
    <div class="col-12 col-lg-6">
      <div class="card card-enterprise shadow-sm border h-100">
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
          <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-circle-question text-primary fs-5"></i>
            <span>Frequently Asked Questions</span>
          </h6>
        </div>
        <div class="card-body p-3 p-md-4">
          <div class="accordion accordion-flush" id="faqAccordion">
            <div class="accordion-item border rounded-3 mb-2 overflow-hidden shadow-xs">
              <h2 class="accordion-header">
                <button class="accordion-button fw-semibold small text-dark py-3 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                  How do I know when my visa is approved?
                </button>
              </h2>
              <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                <div class="accordion-body small text-secondary bg-light p-3">
                  As soon as consular authorities issue your visa decision, the status on your dashboard updates immediately to <strong class="text-success">Approved</strong>, and an electronic copy will be available for instant download.
                </div>
              </div>
            </div>

            <div class="accordion-item border rounded-3 mb-2 overflow-hidden shadow-xs">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed fw-semibold small text-dark py-3 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                  What if a document is rejected or needs replacement?
                </button>
              </h2>
              <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body small text-secondary bg-light p-3">
                  If an officer flags an issue (e.g. blurry scan or missing page), you will see an <strong class="text-danger">ACTION REQUIRED</strong> alert on your dashboard with the exact reason. Simply click the Upload button to provide a replacement copy.
                </div>
              </div>
            </div>

            <div class="accordion-item border rounded-3 mb-2 overflow-hidden shadow-xs">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed fw-semibold small text-dark py-3 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                  What should I bring to my biometrics appointment?
                </button>
              </h2>
              <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body small text-secondary bg-light p-3">
                  Always bring your original valid passport, the official appointment confirmation letter, and physical copies of your application dossier as indicated on your appointment card.
                </div>
              </div>
            </div>

            <div class="accordion-item border rounded-3 mb-2 overflow-hidden shadow-xs">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed fw-semibold small text-dark py-3 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                  How are processing timelines estimated?
                </button>
              </h2>
              <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body small text-secondary bg-light p-3">
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

