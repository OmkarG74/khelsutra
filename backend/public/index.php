<?php

define('LARAVEL_START', microtime(true));

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

require_once __DIR__ . '/../app/Helpers/ApiResponse.php';

// Helper env function
if (!function_exists('env')) {
    function env($key, $default = null) {
        static $envCache = null;
        if ($envCache === null) {
            $envCache = [];
            $envFile = __DIR__ . '/../.env';
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
                    list($name, $value) = explode('=', $line, 2);
                    $envCache[trim($name)] = trim($value);
                }
            }
        }
        return $envCache[$key] ?? getenv($key) ?: $default;
    }
}

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Handle CORS Pre-flight
if ($method === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Organization-ID');
    http_response_code(200);
    exit;
}

// 1. API Route Handler
if (str_starts_with($uri, '/api/')) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Organization-ID');
    
    // Parse JSON or Form payload
    $rawInput = file_get_contents('php://input');
    $body = json_decode($rawInput, true) ?? [];
    $requestData = array_merge($_GET, $_POST, $body);
    $requestData['headers'] = array_change_key_case(getallheaders() ?: [], CASE_LOWER);

    try {
        $apiRouter = require __DIR__ . '/../routes/api.php';
        $response = $apiRouter($uri, $method, $requestData);
        \App\Helpers\ApiResponse::send($response);
    } catch (\Throwable $e) {
        $error = \App\Exceptions\Handler::render($e);
        \App\Helpers\ApiResponse::send($error);
    }
    exit;
}

// 2. Web UI Route Handler
$webRoutes = require __DIR__ . '/../routes/web.php';
$viewTarget = $webRoutes[$uri] ?? $webRoutes['/'] ?? null;

if ($viewTarget && is_callable($viewTarget)) {
    $result = $viewTarget();
    $viewPath = __DIR__ . '/../resources/views/' . $result['view'] . '.blade.php';
    if (file_exists($viewPath)) {
        // Render simple blade view
        include $viewPath;
        exit;
    }
}

// Fallback to Dashboard
$dashboardView = __DIR__ . '/../resources/views/dashboard/index.blade.php';
if (file_exists($dashboardView)) {
    include $dashboardView;
    exit;
}

echo "<h1>KhelSutra Sports Management Platform</h1><p>API is active at /api/v1/health</p>";
