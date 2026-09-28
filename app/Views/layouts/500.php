<?php
// 500 — Internal Server Error page
// This page is intentionally minimal: no calls to Database, session, or services
// that might themselves throw and create an infinite error loop.
$pageTitle = '500 — Internal Server Error';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: #f0f4f8;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      color: #1a202c;
    }
    .card {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 24px rgba(0,0,0,.10);
      padding: 48px 40px;
      max-width: 520px;
      width: 100%;
      text-align: center;
    }
    .code {
      font-size: 72px;
      font-weight: 800;
      color: #e53e3e;
      line-height: 1;
    }
    h1 { font-size: 22px; font-weight: 700; margin: 12px 0 8px; }
    p  { color: #718096; font-size: 15px; line-height: 1.6; }
    .actions { margin-top: 28px; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
    a.btn {
      display: inline-block;
      padding: 10px 22px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      text-decoration: none;
      transition: opacity .15s;
    }
    a.btn:hover { opacity: .85; }
    a.btn-primary { background: #4c6ef5; color: #fff; }
    a.btn-outline { background: transparent; border: 1.5px solid #cbd5e0; color: #4a5568; }
  </style>
</head>
<body>
  <div class="card">
    <div class="code">500</div>
    <h1>Internal Server Error</h1>
    <p>
      Something went wrong on our end. Our team has been notified and is looking into it.
      Please try again in a few moments.
    </p>
    <div class="actions">
      <a href="/dashboard" class="btn btn-primary">&#8592; Back to Dashboard</a>
      <a href="javascript:history.back()" class="btn btn-outline">Go Back</a>
    </div>
  </div>
</body>
</html>
