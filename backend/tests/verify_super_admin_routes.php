<?php

echo "=== Verifying Super Admin Web Routes With Authenticated Session ===" . PHP_EOL;

// 1. Login via /login
$loginUrl = 'http://127.0.0.1:8000/login';
$postData = http_build_query([
    'email' => 'superadmin@khelsutra.local',
    'password' => 'KhelSutra@123'
]);

$ctx = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => $postData,
        'follow_location' => 0,
        'ignore_errors' => true
    ]
]);

$fp = @file_get_contents($loginUrl, false, $ctx);
$cookie = '';
foreach ($http_response_header as $hdr) {
    if (stripos($hdr, 'Set-Cookie:') === 0) {
        $parts = explode(';', substr($hdr, 11));
        $cookie = trim($parts[0]);
        break;
    }
}

echo "Extracted Session Cookie: " . ($cookie ?: 'None') . PHP_EOL;

$routes = [
    '/super-admin/organizations',
    '/super-admin/organizations/create',
    '/super-admin/organizations/1',
    '/super-admin/organizations/1/edit',
    '/users',
    '/users/create',
    '/users/1',
    '/users/1/edit',
    '/roles',
    '/roles/1',
    '/permissions',
    '/audit-logs'
];

$allOk = true;

foreach ($routes as $r) {
    $url = 'http://127.0.0.1:8000' . $r;
    $ctx = stream_context_create([
        'http' => [
            'header' => "Cookie: " . $cookie . "\r\n",
            'ignore_errors' => true,
            'timeout' => 5
        ]
    ]);
    $content = @file_get_contents($url, false, $ctx);
    $statusLine = $http_response_header[0] ?? 'UNKNOWN';
    if (strpos($statusLine, '200') !== false) {
        echo "  [200 OK] " . $r . PHP_EOL;
    } else {
        echo "  [FAIL: {$statusLine}] " . $r . PHP_EOL;
        $allOk = false;
    }
}

if ($allOk) {
    echo "SUCCESS: ALL 12 SUPER ADMIN WEB ROUTES RETURN 200 OK WITH SUPER ADMIN AUTH!" . PHP_EOL;
    exit(0);
} else {
    echo "ERROR: Some routes failed." . PHP_EOL;
    exit(1);
}
