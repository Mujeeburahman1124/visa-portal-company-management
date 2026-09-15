<?php
$pageTitle = 'Activation Link Expired — MS TRAVEL HUB';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" type="image/png" href="/assets/images/logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      font-family: 'Plus Jakarta Sans', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .brand-font {
      font-family: 'Times New Roman', Times, serif;
    }
    .activation-card {
      max-width: 480px;
      width: 100%;
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
      overflow: hidden;
      text-align: center;
      padding: 40px 30px;
    }
  </style>
</head>
<body>

<div class="activation-card">
  <div class="mb-3 text-danger">
    <i class="fa-solid fa-triangle-exclamation fs-1"></i>
  </div>
  <h4 class="brand-font fw-bold text-dark mb-2">Activation Link Expired</h4>
  <p class="text-muted small mb-4">
    This account activation link is invalid, already used, or has exceeded its 48-hour validity period.
  </p>
  <div class="d-grid gap-2">
    <a href="/portal/login" class="btn btn-primary py-2 fw-semibold">Go to Portal Login</a>
  </div>
</div>

</body>
</html>
