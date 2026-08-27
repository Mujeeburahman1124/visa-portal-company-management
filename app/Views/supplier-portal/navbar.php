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
  <link rel="stylesheet" href="/assets/css/main.css?v=7.0.0">
  <style>
    .supplier-navbar { background: linear-gradient(135deg, #070a12 0%, #064e3b 35%, #0369a1 70%, #1d4ed8 100%); }
    .supplier-navbar .nav-link { color: rgba(255,255,255,.85) !important; font-size:.9rem; padding:.5rem .9rem; border-radius:6px; transition:background .2s; }
    .supplier-navbar .nav-link:hover, .supplier-navbar .nav-link.active { background:rgba(255,255,255,.15); color:#fff !important; }
    .supplier-navbar .navbar-brand { color:#fff !important; font-weight:700; }
    .supplier-portal-body { background:#f8fafc; min-height:100vh; font-family:'Times New Roman', Times, serif; }
    .supplier-card { background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06); padding:1.5rem; margin-bottom:1.25rem; border:1px solid #e2e8f0; }
    .stat-sup { background:#fff; border-radius:10px; padding:1.25rem; border:1px solid #e2e8f0; display:flex; align-items:center; gap:1rem; }
    .stat-sup-icon { width:42px;height:42px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.1rem; }
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
