<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\TournamentRepositoryInterface;
use PDO;

class TournamentRepository implements TournamentRepositoryInterface
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
        $stmt = $this->pdo->prepare("SELECT t.*, s.name as sport_name, tl.name as level_name, tf.name as format_name 
                                     FROM tournaments t 
                                     LEFT JOIN sports s ON t.sport_id = s.id 
                                     LEFT JOIN tournament_levels tl ON t.tournament_level_id = tl.id
                                     LEFT JOIN tournament_formats tf ON t.tournament_format_id = tf.id
                                     WHERE t.organization_id = :org_id 
                                     ORDER BY t.id DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':org_id', $organizationId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById(int $organizationId, int $id): ?array
    {
        if (!$this->pdo) return null;
        $stmt = $this->pdo->prepare("SELECT t.*, s.name as sport_name FROM tournaments t LEFT JOIN sports s ON t.sport_id = s.id WHERE t.organization_id = :org_id AND t.id = :id");
        $stmt->bindValue(':org_id', $organizationId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function create(array $data): array
    {
        if (!$this->pdo) return $data;
        $stmt = $this->pdo->prepare("INSERT INTO tournaments (organization_id, sport_id, tournament_level_id, tournament_format_id, name, edition, start_date, end_date, status, created_at, updated_at) 
                                     VALUES (:org_id, :sport_id, :level_id, :format_id, :name, :edition, :start_date, :end_date, 'draft', NOW(), NOW())");
        $stmt->execute([
            ':org_id' => $data['organization_id'],
            ':sport_id' => $data['sport_id'],
            ':level_id' => $data['tournament_level_id'] ?? null,
            ':format_id' => $data['tournament_format_id'] ?? null,
            ':name' => $data['name'],
            ':edition' => $data['edition'] ?? null,
            ':start_date' => $data['start_date'] ?? date('Y-m-d'),
            ':end_date' => $data['end_date'] ?? date('Y-m-d', strtotime('+7 days')),
        ]);
        $data['id'] = (int)$this->pdo->lastInsertId();
        return $data;
    }
}
