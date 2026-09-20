<?php
require_once __DIR__ . '/../app/Services/BaseService.php';

// Mock env if not defined
if (!function_exists('env')) {
    function env($key, $default = null) {
        $env = @parse_ini_file(__DIR__ . '/../.env');
        return $env[$key] ?? $default;
    }
}

$pdo = \App\Services\BaseService::getDatabaseConnection();
echo "--- TEAM_COACHES ---\n";
$stmt = $pdo->query("DESCRIBE team_coaches");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['Field']} ({$row['Type']})\n";
}
