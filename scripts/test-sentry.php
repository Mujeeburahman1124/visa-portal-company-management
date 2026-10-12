<?php
declare(strict_types=1);

/**
 * Test Sentry Connection Script
 * Run: php scripts/test-sentry.php
 */

require_once dirname(__DIR__) . '/app/autoload.php';

echo "=====================================================\n";
echo "MS TRAVEL HUB — Sentry Cloud Monitoring Test\n";
echo "=====================================================\n\n";

$dsn = \App\Services\SentryService::getDsn();

if (empty($dsn)) {
    echo "❌ ERROR: SENTRY_DSN is not configured in your .env file!\n\n";
    echo "To configure Sentry:\n";
    echo "1. Go to https://sentry.io and create a free account.\n";
    echo "2. Create a project with platform 'PHP' or 'Browser'.\n";
    echo "3. Go to Settings -> Client Keys (DSN) and copy your DSN.\n";
    echo "4. Add it to your .env file:\n";
    echo "   SENTRY_DSN=https://your-key@o450xxxxxx.ingest.us.sentry.io/450xxxxxx\n";
    echo "5. Re-run this script: php scripts/test-sentry.php\n\n";
    exit(1);
}

// Mask DSN for privacy
$maskedDsn = preg_replace('/:[^@]+@/', ':****@', $dsn);
echo "✓ SENTRY_DSN detected: {$maskedDsn}\n";
echo "Sending a test exception to Sentry...\n";

try {
    throw new \Exception("MS Travel Hub — Sentry Test Event sent successfully at " . date('Y-m-d H:i:s'));
} catch (\Throwable $e) {
    $eventId = \App\Services\SentryService::captureException($e, [
        'test_runner' => 'CLI test-sentry.php',
        'server_time' => date('r'),
    ]);

    if ($eventId) {
        echo "✅ SUCCESS! Test event dispatched to Sentry!\n";
        echo "   Event ID: {$eventId}\n";
        echo "   Check your Sentry.io dashboard 'Issues' tab to verify it appeared.\n\n";
    } else {
        echo "❌ Could not dispatch to Sentry. Please check your DSN format in .env.\n\n";
    }
}
