<?php

namespace App\Services;

use PDO;
use Exception;

abstract class BaseService
{
    protected ?PDO $pdo = null;

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            $this->pdo = self::getDatabaseConnection();
        }
    }

    public static function getDatabaseConnection(): ?PDO
    {
        static $instance = null;
        if ($instance === null) {
            $host = env('DB_HOST', '127.0.0.1');
            $port = env('DB_PORT', '3306');
            $db   = env('DB_DATABASE', 'khelsutra');
            $user = env('DB_USERNAME', 'root');
            $pass = env('DB_PASSWORD', '');
            try {
                $instance = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (Exception $e) {
                $instance = null;
            }
        }
        return $instance;
    }

    public function getPdo(): ?PDO
    {
        return $this->pdo;
    }

    public function beginTransaction(): bool
    {
        return $this->pdo && $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo && $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo && $this->pdo->rollBack();
    }
}
