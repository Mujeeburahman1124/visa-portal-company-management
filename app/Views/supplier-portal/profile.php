<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="supplier-card">
      <h5 class="fw-bold mb-3 text-dark border-bottom pb-2"><i class="fa-solid fa-building text-primary me-2"></i>Supplier Profile &amp; Settings</h5>
      <form action="/supplier/profile" method="POST">
        <?= csrf_field() ?>
        
        <div class="mb-3">
          <label class="form-label small fw-semibold">Company Name</label>
          <input type="text" class="form-control" value="<?= e($supplierData['company_name']) ?>" disabled>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Supplier Code</label>
          <input type="text" class="form-control" value="<?= e($supplierData['supplier_code']) ?>" disabled>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Contact Person</label>
          <input type="text" name="contact_person" class="form-control" value="<?= e($supplierData['contact_person']) ?>" required>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label small fw-semibold">Mobile</label>
            <input type="text" name="mobile" class="form-control" value="<?= e($supplierData['mobile']) ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label small fw-semibold">WhatsApp</label>
            <input type="text" name="whatsapp" class="form-control" value="<?= e($supplierData['whatsapp']) ?>">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Address</label>
          <textarea name="address" class="form-control" rows="2"><?= e($supplierData['address']) ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">New Password (leave blank to keep current)</label>
          <input type="password" name="password" class="form-control" placeholder="••••••••">
        </div>

        <button type="submit" class="btn btn-dark w-100 fw-semibold"><i class="fa-solid fa-save me-1"></i>Update Profile</button>
      </form>
    </div>
  </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
