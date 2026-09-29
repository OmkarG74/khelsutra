<?php

namespace App\Services\Sport;

use App\Services\BaseService;
use PDO;

class SportService extends BaseService
{
    protected ?PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? BaseService::getDatabaseConnection();
    }

    /**
     * Resolves a sport identifier (slug, code, name, or numeric ID) to a database sport ID.
     */
    public function resolveSportId(string|int $key): int
    {
        if (is_numeric($key) && (int)$key > 0) {
            $id = (int)$key;
            $stmt = $this->pdo->prepare("SELECT id FROM sports WHERE id = :id AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([':id' => $id]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                return $id;
            }
        }

        $keyStr = trim((string)$key);
        if ($keyStr === '') {
            return 1;
        }

        $keyLower = strtolower($keyStr);

        // 1. Try slug, lowercase name, or code in DB
        $stmt = $this->pdo->prepare("
            SELECT id FROM sports 
            WHERE (slug = :key_str OR LOWER(name) = :key_lower OR LOWER(code) = :key_lower) 
              AND deleted_at IS NULL 
            LIMIT 1
        ");
        $stmt->execute([
            ':key_str' => $keyStr,
            ':key_lower' => $keyLower
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return (int)$row['id'];
        }

        // 2. Check catalog mapping
        $catalog = config('sports.catalog') ?? [];
        $sportName = null;
        $slug = null;

        if (isset($catalog[$keyLower])) {
            $sportName = $catalog[$keyLower];
            $slug = $keyLower;
        } else {
            // Check reverse (if key is 'Football')
            foreach ($catalog as $s => $n) {
                if (strtolower($n) === $keyLower) {
                    $sportName = $n;
                    $slug = $s;
                    break;
                }
            }
        }

        if ($sportName) {
            // Check if existing by name
            $stmt = $this->pdo->prepare("SELECT id FROM sports WHERE LOWER(name) = :name AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([':name' => strtolower($sportName)]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                // Update slug/code if empty
                $this->pdo->prepare("UPDATE sports SET slug = :slug WHERE id = :id AND (slug IS NULL OR slug = '')")
                    ->execute([':slug' => $slug, ':id' => (int)$existing['id']]);
                return (int)$existing['id'];
            }

            // Generate short code
            $code = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $slug), 0, 3));
            $insertStmt = $this->pdo->prepare("
                INSERT INTO sports (name, slug, code, is_global, status, created_at, updated_at) 
                VALUES (:name, :slug, :code, 1, 'active', NOW(), NOW())
            ");
            $insertStmt->execute([
                ':name' => $sportName,
                ':slug' => $slug,
                ':code' => $code
            ]);
            return (int)$this->pdo->lastInsertId();
        }

        // Default fallback to first active sport or 1
        $fallbackStmt = $this->pdo->query("SELECT id FROM sports WHERE status = 'active' AND deleted_at IS NULL ORDER BY id ASC LIMIT 1");
        $fallback = $fallbackStmt->fetch(PDO::FETCH_ASSOC);
        return $fallback ? (int)$fallback['id'] : 1;
    }

    /**
     * Get sports catalog list with database IDs.
     */
    public function getSportsCatalog(): array
    {
        $catalog = config('sports.catalog') ?? [];
        $result = [];
        foreach ($catalog as $slug => $name) {
            $id = $this->resolveSportId($slug);
            $result[] = [
                'id' => $id,
                'slug' => $slug,
                'name' => $name
            ];
        }
        usort($result, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $result;
    }
}
