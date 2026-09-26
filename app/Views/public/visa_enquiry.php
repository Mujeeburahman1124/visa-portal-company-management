<?php
$pageTitle = "Public Visa Enquiry — MS Travel Hub";
$metaDescription = "Submit an online visa enquiry for UAE, UK, USA, Schengen, Canada and Saudi Arabia. Instant response from visa consultants.";
$currentRoute = '/visa-enquiry';

ob_start();
?>

<div class="py-4 bg-dark text-white text-center">
    <div class="container">
        <h1 class="fw-bold fs-2">Online Visa Enquiry &amp; Eligibility Check</h1>
        <p class="text-info mb-0">Get tailored advice and pricing from our certified visa consultants in under 24 hours.</p>
    </div>
</div>

<div class="py-5">
    <div class="container" style="max-width: 800px;">
        <div class="card card-custom p-4 p-md-5">
            <h4 class="fw-bold text-dark mb-4"><i class="fa-solid fa-passport text-info me-2"></i> Visa Application Request Form</h4>

            <form action="/visa-enquiry" method="POST">
                <?= csrf_field() ?>
                <!-- Honeypot Anti-Spam -->
                <input type="text" name="website_hp" style="display:none !important;" tabindex="-1" autocomplete="off">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" placeholder="e.g. Mujeeb Rahman" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="name@domain.com" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Mobile / WhatsApp Number <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" class="form-control" placeholder="+971 50 123 4567" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Your Passport Nationality <span class="text-danger">*</span></label>
                        <input type="text" name="nationality" class="form-control" placeholder="e.g. Indian, Pakistani, British" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Destination Country <span class="text-danger">*</span></label>
                        <select name="destination" class="form-select" required>
                            <option value="">Select Destination</option>
                            <?php foreach ($countries as $c): ?>
                                <option value="<?= e($c['name']) ?>" <?= ($_GET['destination'] ?? '') === $c['name'] ? 'selected' : '' ?>>
                                    <?= e($c['flag_emoji']) ?> <?= e($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Specific Visa Service (Optional)</label>
                        <select name="visa_service_id" class="form-select">
                            <option value="0">Select Specific Service Package</option>
                            <?php foreach ($services as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= (int)($_GET['visa_service_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>>
                                    <?= e($s['name']) ?> (<?= format_currency((float)$s['selling_price'], $s['currency'] ?? 'USD') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold text-secondary">Enquiry Details / Special Circumstances</label>
                        <textarea name="message" class="form-control" rows="4" placeholder="Mention planned travel dates, current location, previous visa history or any specific requirements..."></textarea>
                    </div>
                </div>

                <hr class="my-4">

                <button type="submit" class="btn btn-brand btn-lg w-100 fw-semibold">
                    <i class="fa-solid fa-paper-plane me-2"></i> Submit Visa Enquiry
                </button>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/public/layout.php';
