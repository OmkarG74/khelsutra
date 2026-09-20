<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\VenueRepositoryInterface;
use PDO;

class VenueRepository implements VenueRepositoryInterface
{
    protected ?PDO $pdo = null;

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            $host = env('DB_HOST', '127.0.0.1');
            $port = env('DB_PORT', '3306');
            $db   = env('DB_DATABASE', 'khelsutra');
            $user = env('DB_USERNAME', 'root');
            $pass = env('DB_PASSWORD', '');
            try {
                $this->pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (\Exception $e) {
                $this->pdo = null;
            }
        }
    }

    public function getPaginated(int $organizationId, int $page = 1, int $limit = 15): array
    {
        if (!$this->pdo) return [];
        $offset = ($page - 1) * $limit;
        $stmt = $this->pdo->prepare("SELECT * FROM venues WHERE organization_id = :org_id AND deleted_at IS NULL ORDER BY id DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':org_id', $organizationId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById(int $organizationId, int $id): ?array
    {
        if (!$this->pdo) return null;
        $stmt = $this->pdo->prepare("SELECT * FROM venues WHERE organization_id = :org_id AND id = :id AND deleted_at IS NULL");
        $stmt->bindValue(':org_id', $organizationId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function create(array $data): array
    {
        if (!$this->pdo) return $data;
        $stmt = $this->pdo->prepare("INSERT INTO venues (organization_id, name, code, city, state, latitude, longitude, status, created_at, updated_at) 
                                     VALUES (:org_id, :name, :code, :city, :state, :lat, :lng, 'active', NOW(), NOW())");
        $stmt->execute([
            ':org_id' => $data['organization_id'],
            ':name' => $data['name'],
            ':code' => $data['code'] ?? 'VEN-' . time(),
            ':city' => $data['city'] ?? null,
            ':state' => $data['state'] ?? null,
            ':lat' => $data['latitude'] ?? null,
            ':lng' => $data['longitude'] ?? null,
        ]);
        $data['id'] = (int)$this->pdo->lastInsertId();
        return $data;
    }
}
