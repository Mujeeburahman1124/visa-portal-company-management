<?php
$supplierAuth = $_SESSION['supplier_auth'] ?? [];
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
function supplierNavActive(string $path): string {
    global $currentPath;
    return str_starts_with($currentPath, $path) ? 'active fw-semibold' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Supplier Portal — MS Travel Hub') ?></title>
  <!-- Official Favicon -->
  <link rel="icon" type="image/png" href="/assets/images/logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css?v=7.0.0">
  <style>
    .supplier-navbar { background: linear-gradient(135deg,#060e1c 0%,#0a1e3d 50%,#0d3462 100%); }
    .supplier-navbar .nav-link { color: rgba(255,255,255,.80) !important; font-size:.9rem; padding:.5rem .9rem; border-radius:8px; transition:all .2s; font-family:'Times New Roman',Times,serif; }
    .supplier-navbar .nav-link:hover, .supplier-navbar .nav-link.active { background:rgba(255,255,255,.13); color:#fff !important; }
    .supplier-navbar .navbar-brand { color:#fff !important; font-weight:700; font-family:'Times New Roman',Times,serif; }
    .supplier-portal-body { background:#f0f6ff; min-height:100vh; font-family:'Times New Roman', Times, serif; }
    .supplier-card { background:#fff; border-radius:14px; box-shadow:0 2px 14px rgba(10,22,40,.07); padding:1.5rem; margin-bottom:1.25rem; border:1px solid #dce8f5; }
    .supplier-card .card-header-brand { background:linear-gradient(135deg,#2563eb 0%,#1e40af 100%); color:#fff; padding:.85rem 1.25rem; margin:-1.5rem -1.5rem 1.25rem; border-radius:13px 13px 0 0; font-weight:700; }
    .stat-sup { background:#fff; border-radius:12px; padding:1.25rem; border:1px solid #dce8f5; display:flex; align-items:center; gap:1rem; box-shadow:0 2px 8px rgba(10,22,40,.05); transition:transform .2s; }
    .stat-sup:hover { transform:translateY(-3px); }
    .stat-sup-icon { width:46px;height:46px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.15rem; }
    .scoped-notice { background:linear-gradient(135deg,#f0f6ff,#e8f4f8); border:1px solid #bfdbfe; border-radius:10px; padding:.75rem 1rem; margin-bottom:1rem; font-size:.82rem; color:#1e40af; }
  </style>
</head>
<body class="supplier-portal-body">

<nav class="navbar navbar-expand-lg supplier-navbar px-3 py-2 shadow-sm mb-0">
  <a class="navbar-brand d-flex align-items-center gap-2" href="/supplier/dashboard">
    <img src="/assets/images/logo.png" alt="MS Travel Hub" style="height: 36px; width: auto;" class="bg-white p-1 rounded-2 shadow-sm">
    <span>MS TRAVEL HUB</span>
    <span class="text-white-50 small" style="font-size: 0.72rem;">| SUPPLIER PORTAL</span>
    <span class="badge bg-secondary text-white ms-1" style="font-size:.65rem;"><?= e($supplierAuth['supplier_code'] ?? '') ?></span>
  </a>
  <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#supNav">
    <span class="navbar-toggler-icon" style="filter:invert(1)"></span>
  </button>
  <div class="collapse navbar-collapse" id="supNav">
    <ul class="navbar-nav ms-3 me-auto gap-1">
      <li class="nav-item"><a class="nav-link <?= supplierNavActive('/supplier/dashboard') ?>" href="/supplier/dashboard"><i class="fa-solid fa-gauge me-1"></i>Dashboard</a></li>
      <li class="nav-item"><a class="nav-link <?= supplierNavActive('/supplier/applications') ?>" href="/supplier/applications"><i class="fa-solid fa-file-invoice me-1"></i>Assigned Applications</a></li>
      <li class="nav-item"><a class="nav-link <?= supplierNavActive('/supplier/payments') ?>" href="/supplier/payments"><i class="fa-solid fa-money-bill-wave me-1"></i>Payables &amp; Settlements</a></li>
      <li class="nav-item"><a class="nav-link <?= supplierNavActive('/supplier/profile') ?>" href="/supplier/profile"><i class="fa-solid fa-user-gear me-1"></i>Company Profile</a></li>
    </ul>
    <div class="d-flex align-items-center gap-2">
      <span class="text-white small opacity-75"><?= e($supplierAuth['company_name'] ?? '') ?></span>
      <a href="/supplier/logout" class="btn btn-sm btn-outline-light fw-semibold">
        <i class="fa-solid fa-right-from-bracket me-1"></i>Logout
      </a>
    </div>
  </div>
</nav>
<div class="container-fluid px-4 py-4">
<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show mb-3">
    <i class="fa-solid <?= $flash['type']==='danger'?'fa-circle-exclamation':'fa-circle-check' ?> me-2"></i>
    <?= e($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
