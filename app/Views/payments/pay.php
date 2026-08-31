<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment — <?= htmlspecialchars($link['title'] ?? 'MS Travel Hub') ?></title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0f172a;
            --accent-color: #3b82f6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --bg-color: #f8fafc;
        }
        body {
            background-color: var(--bg-color);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 2rem 1rem;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .pay-card {
            background: #ffffff;
            border-radius: 1.25rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            max-width: 680px;
            width: 100%;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .pay-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.12);
            padding: 0.35rem 1rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 0.75rem;
        }
        .pay-body {
            padding: 2rem;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 640px) {
            .grid-2 { grid-template-columns: 1fr; }
            .pay-body { padding: 1.25rem; }
        }
        .info-box {
            background: #f1f5f9;
            border-radius: 0.75rem;
            padding: 1.25rem;
            border-left: 4px solid var(--accent-color);
        }
        .info-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 0.25rem;
        }
        .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
        }
        .amount-hero {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .amount-val {
            font-size: 2.25rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0.25rem 0;
        }
        .btn-pay {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            width: 100%;
            padding: 1rem;
            background: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 0.75rem;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            margin-bottom: 0.75rem;
        }
        .btn-pay:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }
        .btn-wallet {
            background: #0d9488;
        }
        .btn-wallet:hover {
            background: #0f766e;
        }
        .badge-status {
            display: inline-block;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 700;
        }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-paid { background: #dbeafe; color: #1e40af; }
        .badge-expired { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

<div class="pay-card">
    <!-- Header -->
    <div class="pay-header" style="background: linear-gradient(135deg, #090d16 0%, #1e293b 50%, #881337 100%);">
        <div class="brand-badge" style="background: rgba(255, 255, 255, 0.15);">
            <img src="/assets/images/logo.png" alt="MS Travel Hub" style="height: 28px; width: auto;" class="bg-white p-0.5 rounded-1 me-1">
            MS TRAVEL HUB &bull; SECURE PAYMENT GATEWAY
        </div>
        <h2 style="margin: 0; font-size: 1.5rem;"><?= htmlspecialchars($link['title'] ?? 'Visa Fee Payment') ?></h2>
        <div style="font-size: 0.9rem; opacity: 0.85; margin-top: 0.5rem;">
            Invoice: <strong><?= htmlspecialchars($link['invoice_number'] ?? 'INV-PAY') ?></strong> &bull;
            Date: <?= date('M d, Y', strtotime($link['created_at'])) ?>
        </div>
    </div>

    <!-- Body -->
    <div class="pay-body">
        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div style="padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.25rem; font-weight: 600; background: <?= $_SESSION['flash_type'] === 'danger' ? '#fee2e2; color: #991b1b;' : '#dcfce7; color: #166534;' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- Customer & Application Grid -->
        <div class="grid-2">
            <div class="info-box">
                <div class="info-label"><i class="fa-solid fa-user"></i> Customer & Applicant</div>
                <div class="info-value"><?= htmlspecialchars($link['customer_name'] ?? 'N/A') ?></div>
                <div style="font-size: 0.85rem; color: #64748b; margin-top: 0.25rem;">
                    ID: <?= htmlspecialchars($link['customer_code'] ?? 'MSC-000') ?> &bull; 
                    Passport: <?= htmlspecialchars($link['passport_number'] ?? 'N/A') ?>
                </div>
            </div>

            <div class="info-box" style="border-left-color: #10b981;">
                <div class="info-label"><i class="fa-solid fa-plane"></i> Visa Application</div>
                <div class="info-value"><?= htmlspecialchars($link['service_name'] ?? 'Visa Service') ?></div>
                <div style="font-size: 0.85rem; color: #64748b; margin-top: 0.25rem;">
                    Ref: <strong><?= htmlspecialchars($link['application_number'] ?? 'MSV-APP') ?></strong> &bull; 
                    Dest: <?= htmlspecialchars($link['country_name'] ?? 'Global') ?>
                </div>
            </div>
        </div>

        <!-- Amount Hero -->
        <div class="amount-hero">
            <div class="info-label">Payment Amount Requested</div>
            <div class="amount-val">$<?= number_format((float)$link['amount'], 2) ?> <span style="font-size: 1.25rem; color: #64748b; font-weight: 600;"><?= htmlspecialchars($link['currency'] ?? 'USD') ?></span></div>
            <div style="font-size: 0.85rem; color: #64748b;">
                Outstanding Case Balance: <strong>$<?= number_format((float)$link['balance_amount'], 2) ?> USD</strong> &bull;
                Valid Until: <strong><?= date('M d, Y h:i A', strtotime($link['expires_at'])) ?></strong>
            </div>
        </div>

        <!-- Status & Actions -->
        <?php if ($link['status'] === 'Paid'): ?>
            <div style="text-align: center; padding: 1.5rem; background: #ecfdf5; border-radius: 0.75rem; border: 1px solid #a7f3d0;">
                <i class="fa-solid fa-circle-check" style="font-size: 3rem; color: #10b981; margin-bottom: 0.75rem;"></i>
                <h3 style="margin: 0; color: #065f46;">Payment Successfully Completed</h3>
                <p style="color: #047857; margin: 0.5rem 0 1.25rem 0; font-size: 0.95rem;">
                    This invoice was settled on <?= date('M d, Y h:i A', strtotime($link['paid_at'] ?? 'now')) ?> via <?= htmlspecialchars($link['payment_method'] ?? 'Online') ?>.
                </p>
                <?php if (!empty($paymentId)): ?>
                    <a href="/payments/receipt?id=<?= $paymentId ?>" class="btn-pay" style="display: inline-flex; width: auto; padding: 0.75rem 1.5rem;">
                        <i class="fa-solid fa-receipt"></i> View Official Receipt
                    </a>
                <?php endif; ?>
            </div>
        <?php elseif ($link['status'] === 'Expired'): ?>
            <div style="text-align: center; padding: 1.5rem; background: #fef2f2; border-radius: 0.75rem; border: 1px solid #fecaca;">
                <i class="fa-solid fa-clock" style="font-size: 3rem; color: #ef4444; margin-bottom: 0.75rem;"></i>
                <h3 style="margin: 0; color: #991b1b;">Payment Link Expired</h3>
                <p style="color: #b91c1c; margin: 0.5rem 0 0 0; font-size: 0.95rem;">
                    This link expired on <?= date('M d, Y', strtotime($link['expires_at'])) ?>. Please contact MS Travel Hub to generate a renewed link.
                </p>
            </div>
        <?php else: ?>
            <!-- Payment Action Form (Stripe Gateway Checkout) -->
            <form action="/pay/checkout" method="POST" id="stripePayForm">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= htmlspecialchars($link['link_token']) ?>">
                
                <button type="submit" class="btn-pay" id="payNowBtn">
                    <i class="fa-brands fa-stripe" style="font-size: 1.5rem;"></i> Pay Now ($<?= number_format((float)$link['amount'], 2) ?> USD)
                </button>
            </form>

            <?php if (!empty($customerWallet) && (float)$customerWallet['current_balance'] >= (float)$link['amount']): ?>
                <!-- Wallet Payment Option -->
                <form action="/pay/wallet" method="POST" onsubmit="return confirm('Confirm debit of $<?= number_format((float)$link['amount'], 2) ?> from your digital wallet balance ($<?= number_format((float)$customerWallet['current_balance'], 2) ?>)?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($link['link_token']) ?>">
                    <button type="submit" class="btn-pay btn-wallet">
                        <i class="fa-solid fa-wallet"></i> Pay from Digital Wallet (Balance: $<?= number_format((float)$customerWallet['current_balance'], 2) ?>)
                    </button>
                </form>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 1rem; font-size: 0.8rem; color: #64748b;">
                <i class="fa-solid fa-shield-halved"></i> 256-bit Encrypted SSL Connection &bull; Powered by Stripe Gateway
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
