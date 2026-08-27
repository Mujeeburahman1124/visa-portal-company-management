<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="mb-4">
  <a href="/agent/applications" class="btn btn-outline-secondary btn-sm mb-2"><i class="fa-solid fa-arrow-left me-1"></i>Back to Applications</a>
  <h4 class="fw-bold mb-1 text-dark"><i class="fa-solid fa-file-circle-plus text-success me-2"></i>Submit New Visa Application</h4>
  <p class="text-muted small mb-0">Register client information and submit a new visa application directly from your agent portal.</p>
</div>

<form action="/agent/create-application" method="POST" class="agent-card">
  <?= csrf_field() ?>

  <!-- 1. Applicant Personal Details -->
  <h6 class="fw-bold text-success border-bottom pb-2 mb-3"><i class="fa-solid fa-user me-2"></i>1. Client / Applicant Information</h6>
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <label class="form-label small fw-semibold">First Name <span class="text-danger">*</span></label>
      <input type="text" name="first_name" class="form-control" required placeholder="e.g. John">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Last Name <span class="text-danger">*</span></label>
      <input type="text" name="last_name" class="form-control" required placeholder="e.g. Doe">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Mobile Number <span class="text-danger">*</span></label>
      <input type="text" name="mobile" class="form-control" required placeholder="+971 50 123 4567">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Email Address</label>
      <input type="email" name="email" class="form-control" placeholder="client@example.com">
    </div>

    <div class="col-md-3">
      <label class="form-label small fw-semibold">Nationality</label>
      <select name="nationality" class="form-select">
        <option value="">-- Choose Country --</option>
        <?php foreach ($countries as $c): ?>
          <option value="<?= e($c['name']) ?>"><?= $c['flag_emoji'] ?? '🏳️' ?> <?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Date of Birth</label>
      <input type="date" name="dob" class="form-control">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Gender</label>
      <select name="gender" class="form-select">
        <option value="Male">Male</option>
        <option value="Female">Female</option>
        <option value="Other">Other</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Passport Number</label>
      <input type="text" name="passport_number" class="form-control" placeholder="e.g. N1234567">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Passport Expiry Date</label>
      <input type="date" name="passport_expiry" class="form-control">
    </div>
  </div>

  <!-- 2. Visa Service & Travel Information -->
  <h6 class="fw-bold text-success border-bottom pb-2 mb-3"><i class="fa-solid fa-passport me-2"></i>2. Visa Service &amp; Travel Specifics</h6>
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Select Destination &amp; Visa Service <span class="text-danger">*</span></label>
      <select name="visa_service_id" class="form-select" required>
        <option value="">-- Choose Visa Service --</option>
        <?php foreach ($services as $s): ?>
          <option value="<?= $s['id'] ?>">
            <?= $s['flag_emoji'] ?? '🏳️' ?> <?= e($s['country_name']) ?> — <?= e($s['name']) ?> (<?= format_currency((float)$s['selling_price']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Expected Travel Date</label>
      <input type="date" name="travel_date" class="form-control">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Expected Return Date</label>
      <input type="date" name="return_date" class="form-control">
    </div>

    <div class="col-md-4">
      <label class="form-label small fw-semibold">Your Internal Reference Number</label>
      <input type="text" name="agent_reference" class="form-control" placeholder="e.g. AG-BOOK-998">
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Client Quoted Price (Optional)</label>
      <div class="input-group">
        <span class="input-group-text">$</span>
        <input type="number" step="0.01" name="agent_price" class="form-control" placeholder="0.00">
      </div>
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Internal Notes / Instructions</label>
      <input type="text" name="notes" class="form-control" placeholder="Special requirements...">
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2 border-top pt-3">
    <a href="/agent/applications" class="btn btn-light border px-4">Cancel</a>
    <button type="submit" class="btn btn-success px-5 fw-semibold"><i class="fa-solid fa-paper-plane me-2"></i>Submit Application</button>
  </div>
</form>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
