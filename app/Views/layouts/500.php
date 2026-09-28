<?php
// 500 — Internal Server Error page
$pageTitle = '500 — Internal Server Error';
$currentUser = function_exists('auth_user') ? auth_user() : ($_SESSION['user'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      background: #f1f5f9;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      color: #0f172a;
      padding: 24px 16px;
    }
    .error-container {
      max-width: <?= !empty($diagnosticError) ? '880px' : '520px' ?>;
      width: 100%;
      background: #ffffff;
      border-radius: 16px;
      box-shadow: 0 10px 40px -10px rgba(0,0,0,0.12), 0 0 1px 1px rgba(0,0,0,0.05);
      overflow: hidden;
      transition: all 0.3s ease;
    }
    .error-header {
      background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
      padding: 32px 24px;
      text-align: center;
      color: #ffffff;
    }
    .error-header .code {
      font-size: 64px;
      font-weight: 800;
      letter-spacing: -2px;
      line-height: 1;
      margin-bottom: 8px;
      text-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }
    .error-header h1 {
      font-size: 20px;
      font-weight: 700;
      letter-spacing: -0.5px;
    }
    .error-body {
      padding: 32px 28px;
    }
    .error-body p {
      color: #475569;
      font-size: 15px;
      line-height: 1.6;
      text-align: center;
    }
    .actions {
      margin-top: 24px;
      display: flex;
      gap: 12px;
      justify-content: center;
      flex-wrap: wrap;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 20px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      text-decoration: none;
      transition: all .15s ease;
      cursor: pointer;
      border: 1px solid transparent;
    }
    .btn-primary { background: #2563eb; color: #ffffff; }
    .btn-primary:hover { background: #1d4ed8; }
    .btn-outline { background: #ffffff; border-color: #cbd5e1; color: #334155; }
    .btn-outline:hover { background: #f8fafc; border-color: #94a3b8; }
    .btn-danger { background: #dc2626; color: #ffffff; }
    .btn-danger:hover { background: #b91c1c; }

    /* Super Admin Diagnostics Panel */
    .admin-diagnostics {
      margin-top: 24px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      overflow: hidden;
      text-align: left;
    }
    .admin-diagnostics-header {
      background: #fef2f2;
      border-bottom: 1px solid #fee2e2;
      padding: 12px 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      color: #991b1b;
      font-weight: 700;
      font-size: 13px;
    }
    .admin-diagnostics-body {
      padding: 16px;
      font-size: 13px;
    }
    .diag-item {
      margin-bottom: 12px;
    }
    .diag-label {
      font-weight: 600;
      color: #64748b;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 4px;
    }
    .diag-value {
      background: #ffffff;
      padding: 8px 12px;
      border-radius: 6px;
      border: 1px solid #e2e8f0;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      color: #0f172a;
      word-break: break-all;
      font-size: 12px;
    }
    .diag-trace {
      max-height: 220px;
      overflow-y: auto;
      white-space: pre-wrap;
      font-size: 11px;
      background: #0f172a;
      color: #f1f5f9;
      padding: 12px;
      border-radius: 6px;
      line-height: 1.5;
    }
    .admin-tag {
      background: #fee2e2;
      color: #dc2626;
      padding: 2px 8px;
      border-radius: 4px;
      font-size: 11px;
      font-weight: 700;
    }
  </style>
</head>
<body>
  <div class="error-container">
    <div class="error-header">
      <div class="code">500</div>
      <h1>Internal Server Error</h1>
    </div>

    <div class="error-body">
      <p>
        Something went wrong while processing your request. The issue has been automatically logged for engineering review.
      </p>

      <?php if (!empty($diagnosticError)): ?>
        <!-- SUPER ADMIN EXCLUSIVE DIAGNOSTIC CONSOLE -->
        <div class="admin-diagnostics">
          <div class="admin-diagnostics-header">
            <span><i class="fa-solid fa-shield-halved me-1"></i> SUPER ADMIN DIAGNOSTIC CONSOLE</span>
            <span class="admin-tag">Visible only to Super Admin / Admin</span>
          </div>
          <div class="admin-diagnostics-body">
            <div class="diag-item">
              <div class="diag-label">Exception &amp; Message</div>
              <div class="diag-value" style="color: #dc2626; font-weight: 600;">
                <?= htmlspecialchars(get_class($diagnosticError) . ': ' . $diagnosticError->getMessage()) ?>
              </div>
            </div>

            <div class="diag-item">
              <div class="diag-label">File &amp; Line</div>
              <div class="diag-value">
                <?= htmlspecialchars($diagnosticError->getFile()) ?> : line <strong><?= (int)$diagnosticError->getLine() ?></strong>
              </div>
            </div>

            <div class="diag-item">
              <div class="diag-label">Request Context</div>
              <div class="diag-value">
                <strong>Method:</strong> <?= htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'GET') ?> &bull; 
                <strong>URI:</strong> <?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/') ?> &bull; 
                <strong>Active User:</strong> <?= htmlspecialchars(($currentUser['name'] ?? 'Guest') . ' (' . ($currentUser['role_name'] ?? $currentUser['role'] ?? 'Unauthenticated') . ')') ?>
              </div>
            </div>

            <div class="diag-item">
              <div class="diag-label">Stack Trace</div>
              <pre class="diag-trace"><?= htmlspecialchars($diagnosticError->getTraceAsString()) ?></pre>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="actions">
        <a href="/dashboard" class="btn btn-primary"><i class="fa-solid fa-house"></i> Back to Dashboard</a>
        <?php if (!empty($diagnosticError)): ?>
          <a href="/audit-logs" class="btn btn-danger"><i class="fa-solid fa-shield-halved"></i> View System Audit Logs</a>
        <?php endif; ?>
        <a href="javascript:location.reload()" class="btn btn-outline"><i class="fa-solid fa-rotate-right"></i> Try Again</a>
        <a href="javascript:history.back()" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Go Back</a>
      </div>
    </div>
  </div>
</body>
</html>
