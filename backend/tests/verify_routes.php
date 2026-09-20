<?php

$routes = [
    '/dashboard',
    '/athletes',
    '/athletes/create',
    '/athletes/1',
    '/athletes/1/edit',
    '/coaches',
    '/coaches/create',
    '/coaches/1',
    '/coaches/1/edit',
    '/teams',
    '/teams/create',
    '/teams/1',
    '/teams/1/edit',
    '/tournaments',
    '/tournaments/create',
    '/tournaments/1',
    '/tournaments/1/edit',
    '/training',
    '/training/create',
    '/training/1',
    '/training/1/edit',
    '/venues',
    '/venues/create',
    '/venues/bookings/create',
    '/venues/1',
    '/venues/1/edit',
    '/inventory',
    '/inventory/create',
    '/inventory/1',
    '/inventory/1/edit',
    '/hr-finance',
    '/hr/employees',
    '/leave',
    '/leave/create',
    '/payroll',
    '/reports',
    '/search?q=Apex'
];

echo "=== Verifying All Sports Admin Web Routes ===" . PHP_EOL;
$allOk = true;

foreach ($routes as $r) {
    $url = 'http://127.0.0.1:8000' . $r;
    $ctx = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 5]]);
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
    echo "SUCCESS: ALL 36 SPORTS ADMIN ROUTES ARE 100% HEALTHY AND RETURNING 200 OK!" . PHP_EOL;
    exit(0);
} else {
    echo "ERROR: Some routes failed." . PHP_EOL;
    exit(1);
}
