<?php
declare(strict_types=1);

$baseUrl = 'http://127.0.0.1:8000';
$cookieFile = sys_get_temp_dir() . '/vt_api_test_cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

function apiReq(string $url, string $method = 'GET', array $data = [], ?string $cookieFile = null): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($res, $headerSize);
    curl_close($ch);
    $json = json_decode($body, true);
    return ['code' => $code, 'json' => $json, 'body' => $body];
}

echo "=== PHASE 2 RESTFUL API ROUTING VERIFICATION ===\n\n";

// 1. Staff Login
$loginPage = apiReq("$baseUrl/auth/login", 'GET', [], $cookieFile);
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $loginPage['body'], $m);
$token = $m[1] ?? '';

$loginRes = apiReq("$baseUrl/auth/login", 'POST', [
    'csrf_token' => $token,
    'email' => 'admin@visatrack.com',
    'password' => 'admin123'
], $cookieFile);

echo "1. POST /auth/login -> HTTP {$loginRes['code']}\n";

// 2. Test API Endpoints
$apiEndpoints = [
    '/api/applications' => 'GET Applications List',
    '/api/applications/show?id=1' => 'GET Application Detail & Checklist',
    '/api/tracking?id=1' => 'GET Visa Journey Tracking & Health',
    '/api/documents?application_id=1' => 'GET Application Documents',
    '/api/tasks' => 'GET Operational Tasks',
    '/api/appointments' => 'GET Scheduled Appointments',
    '/api/applicants' => 'GET Applicants Directory',
    '/api/applicants/check-duplicate?mobile=+971501234567&email=rahul.sharma@cloudtech.ae&passport=Z6543219' => 'GET Check Duplicates'
];

$allOk = true;
foreach ($apiEndpoints as $path => $label) {
    $res = apiReq("$baseUrl$path", 'GET', [], $cookieFile);
    $status = ($res['code'] === 200 && ($res['json']['success'] ?? false) === true) ? "[OK]" : "[FAIL]";
    if ($status === "[FAIL]") $allOk = false;
    echo "• {$label} ({$path}) -> HTTP {$res['code']} {$status}\n";
}

if (file_exists($cookieFile)) unlink($cookieFile);

echo "\n===================================================\n";
if ($allOk) {
    echo " ALL RESTFUL API ENDPOINTS VERIFIED & WORKING (HTTP 200 OK)!\n";
} else {
    echo " SOME API ENDPOINTS FAILED!\n";
}
echo "===================================================\n";
