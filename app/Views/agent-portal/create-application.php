<?php require_once __DIR__ . '/navbar.php'; ?>

<!-- ─── HEADER STRIP ──────────────────────────────────────────────────────── -->
<div class="agent-header-strip">
  <div>
    <div class="mb-2">
      <a href="/agent/applications" class="agent-btn-outline py-1 px-3" style="font-size:0.82rem;">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Back to Applications</span>
      </a>
    </div>
    <h1 class="agent-header-title">Submit New Visa Application</h1>
    <p class="agent-header-subtitle">
      Register client information and submit a new visa application directly from your agent portal.
    </p>
  </div>
</div>

<!-- ─── FORM CONTAINER ────────────────────────────────────────────────────── -->
<form action="/agent/create-application" method="POST" class="agent-bento-card">
  <?= csrf_field() ?>

  <!-- 1. Applicant Personal Details -->
  <div class="agent-bento-card-header">
    <h5 class="agent-bento-card-title">
      <span class="card-title-icon"><i class="fa-solid fa-user"></i></span>
      <span>1. Client / Applicant Information</span>
    </h5>
  </div>
  
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <label class="form-label small fw-semibold">First Name <span class="text-danger">*</span></label>
      <input type="text" name="first_name" class="form-control agent-form-control" required placeholder="e.g. John">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Last Name <span class="text-danger">*</span></label>
      <input type="text" name="last_name" class="form-control agent-form-control" required placeholder="e.g. Doe">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Mobile Number <span class="text-danger">*</span></label>
      <input type="text" name="mobile" class="form-control agent-form-control" required placeholder="+971 50 123 4567">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Email Address</label>
      <input type="email" name="email" class="form-control agent-form-control" placeholder="client@example.com">
    </div>

    <div class="col-md-3">
      <label class="form-label small fw-semibold">Nationality</label>
      <select name="nationality" class="form-select agent-form-control">
        <option value="">-- Choose Country --</option>
        <?php foreach ($countries as $c): ?>
          <option value="<?= e($c['name']) ?>"><?= $c['flag_emoji'] ?? '🏳️' ?> <?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Date of Birth</label>
      <input type="date" name="dob" class="form-control agent-form-control">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Gender</label>
      <select name="gender" class="form-select agent-form-control">
        <option value="Male">Male</option>
        <option value="Female">Female</option>
        <option value="Other">Other</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Passport Number</label>
      <input type="text" name="passport_number" class="form-control agent-form-control font-monospace" placeholder="e.g. N1234567">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Passport Expiry Date</label>
      <input type="date" name="passport_expiry" class="form-control agent-form-control">
    </div>
  </div>

  <!-- 2. Visa Service & Travel Information -->
  <div class="agent-bento-card-header mt-4">
    <h5 class="agent-bento-card-title">
      <span class="card-title-icon"><i class="fa-solid fa-passport"></i></span>
      <span>2. Visa Service &amp; Travel Specifics</span>
    </h5>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Select Destination &amp; Visa Service <span class="text-danger">*</span></label>
      <select name="visa_service_id" class="form-select agent-form-control" required>
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
      <input type="date" name="travel_date" class="form-control agent-form-control">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Expected Return Date</label>
      <input type="date" name="return_date" class="form-control agent-form-control">
    </div>

    <div class="col-md-4">
      <label class="form-label small fw-semibold">Your Internal Reference Number</label>
      <input type="text" name="agent_reference" class="form-control agent-form-control font-monospace" placeholder="e.g. AG-BOOK-998">
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Client Quoted Price (Optional)</label>
      <div class="input-group">
        <span class="input-group-text bg-white border-end-0 text-muted">$</span>
        <input type="number" step="0.01" name="agent_price" class="form-control border-start-0 ps-0 agent-form-control" placeholder="0.00">
      </div>
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Internal Notes / Instructions</label>
      <input type="text" name="notes" class="form-control agent-form-control" placeholder="Special requirements or urgent handling...">
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-4">
    <a href="/agent/applications" class="agent-btn-outline px-4">Cancel</a>
    <button type="submit" class="agent-btn-primary px-5">
      <i class="fa-solid fa-paper-plane"></i>
      <span>Submit Application</span>
    </button>
  </div>
</form>

</div><!-- /.container-fluid -->

<footer class="text-center py-4 text-muted small" style="border-top: 1px solid var(--agent-border-subtle);">
  &copy; <?= date('Y') ?> <strong style="color:var(--agent-text-main);">MS TRAVEL HUB</strong> &bull; Agent Partner Portal &bull; Enterprise Visa Solutions
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
