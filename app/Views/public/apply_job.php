<?php
$pageTitle = "Apply for Job — MS Travel Hub Recruitment";
$metaDescription = "Submit your online job application and CV directly to MS Travel Hub Recruitment Operations.";
$currentRoute = '/jobs';

ob_start();
?>

<div class="py-4 bg-dark text-white">
    <div class="container">
        <h1 class="fw-bold fs-2">Online Candidate Application Form</h1>
        <p class="text-info mb-0"><?= !empty($job) ? "Applying for: " . e($job['job_title']) . " (" . e($job['location']) . ")" : "General Overseas Recruitment Candidate Registration" ?></p>
    </div>
</div>

<div class="py-5">
    <div class="container" style="max-width: 800px;">
        <div class="card card-custom p-4 p-md-5">
            <h4 class="fw-bold text-dark mb-4"><i class="fa-solid fa-file-signature text-primary me-2"></i> Candidate Contact &amp; Resume Details</h4>

            <form action="/jobs/apply" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <!-- Honeypot for Anti-Spam Bot Blocking -->
                <input type="text" name="website_hp" style="display:none !important;" tabindex="-1" autocomplete="off">

                <?php if (!empty($job)): ?>
                    <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                    <div class="p-3 bg-light rounded-3 mb-4 d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block">Target Position</small>
                            <span class="fw-bold text-dark fs-6"><?= e($job['job_title']) ?></span>
                        </div>
                        <span class="badge bg-primary text-white"><?= e($job['location']) ?></span>
                    </div>
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" placeholder="e.g. Mujeeb Rahman" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Mobile / WhatsApp Number <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" class="form-control" placeholder="+971 50 123 4567" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Nationality <span class="text-danger">*</span></label>
                        <input type="text" name="nationality" class="form-control" placeholder="e.g. Indian, Pakistani, Egyptian" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Current Country of Residence</label>
                        <input type="text" name="current_location" class="form-control" placeholder="e.g. UAE, Saudi Arabia, India">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Total Years of Experience</label>
                        <select name="experience_years" class="form-select">
                            <option value="1-2 Years">1-2 Years</option>
                            <option value="3-5 Years">3-5 Years</option>
                            <option value="5-8 Years">5-8 Years</option>
                            <option value="8+ Years">8+ Years</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold text-secondary">Upload CV / Resume (PDF or DOC, Max 10MB) <span class="text-danger">*</span></label>
                        <input type="file" name="cv_file" class="form-control" accept=".pdf,.doc,.docx" required>
                        <div class="form-text">Your resume will be stored securely in our private recruitment vault.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold text-secondary">Cover Note / Additional Comments</label>
                        <textarea name="cover_letter" class="form-control" rows="4" placeholder="Briefly describe your relevant qualifications, key skills, and current notice period..."></textarea>
                    </div>
                </div>

                <hr class="my-4">

                <button type="submit" class="btn btn-brand btn-lg w-100 fw-semibold">
                    <i class="fa-solid fa-paper-plane me-2"></i> Submit Job Application
                </button>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/public/layout.php';
