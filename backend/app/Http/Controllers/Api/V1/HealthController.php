<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use PDO;
use Exception;

class HealthController extends Controller
{
    /**
     * Check API health and test live database connection.
     *
     * @return array
     */
    public function check(): array
    {
        $dbStatus = 'disconnected';
        $host = env('DB_HOST', '127.0.0.1');
        $port = env('DB_PORT', '3306');
        $db   = env('DB_DATABASE', 'khelsutra');
        $user = env('DB_USERNAME', 'root');
        $pass = env('DB_PASSWORD', '');

        try {
            $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 2,
            ]);
            $stmt = $pdo->query("SELECT 1");
            if ($stmt && $stmt->fetchColumn() == 1) {
                $dbStatus = 'connected';
            }
        } catch (Exception $e) {
            $dbStatus = 'error: ' . $e->getMessage();
        }

        $isHealthy = ($dbStatus === 'connected');
        $statusCode = $isHealthy ? 200 : 503;

        return ApiResponse::success([
            'application' => 'KhelSutra',
            'status' => $isHealthy ? 'ok' : 'degraded',
            'database' => $dbStatus,
            'timestamp' => date('c'),
            'version' => '1.0.0'
        ], 'KhelSutra API is healthy', $statusCode);
    }
}
