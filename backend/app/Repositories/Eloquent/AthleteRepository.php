<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\AthleteRepositoryInterface;
use PDO;

class AthleteRepository implements AthleteRepositoryInterface
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

    public function getPaginated(int $organizationId, int $page = 1, int $limit = 15, ?string $search = null, ?int $sportId = null, ?string $status = null): array
    {
        if (!$this->pdo) return ['data' => [], 'total' => 0];

        $offset = max(0, ($page - 1) * $limit);
        $where = "a.organization_id = :org_id AND a.deleted_at IS NULL";
        $params = [':org_id' => $organizationId];

        if (!empty($search)) {
            $where .= " AND (a.first_name LIKE :search OR a.last_name LIKE :search OR a.athlete_code LIKE :search OR a.email LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        if (!empty($sportId)) {
            $where .= " AND a.current_sport_id = :sport_id";
            $params[':sport_id'] = $sportId;
        }
        if (!empty($status)) {
            $where .= " AND a.status = :status";
            $params[':status'] = $status;
        }

        // Count total
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM athletes a WHERE {$where}");
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        // Fetch page
        $sql = "
            SELECT a.*, s.name as sport_name, tm.name as team_name
            FROM athletes a 
            LEFT JOIN sports s ON a.current_sport_id = s.id 
            LEFT JOIN team_members mem ON a.id = mem.athlete_id AND mem.is_current = 1
            LEFT JOIN teams tm ON mem.team_id = tm.id AND tm.deleted_at IS NULL
            WHERE {$where} 
            ORDER BY a.id DESC 
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / max(1, $limit)),
        ];
    }

    public function findById(int $organizationId, int $id): ?array
    {
        if (!$this->pdo) return null;

        $stmt = $this->pdo->prepare("
            SELECT a.*, s.name as sport_name, tm.name as team_name, tm.id as team_id
            FROM athletes a 
            LEFT JOIN sports s ON a.current_sport_id = s.id 
            LEFT JOIN team_members mem ON a.id = mem.athlete_id AND mem.is_current = 1
            LEFT JOIN teams tm ON mem.team_id = tm.id AND tm.deleted_at IS NULL
            WHERE a.organization_id = :org_id AND a.id = :id AND a.deleted_at IS NULL
        ");
        $stmt->bindValue(':org_id', $organizationId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $athlete = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$athlete) return null;

        // Fetch guardian info
        $gStmt = $this->pdo->prepare("
            SELECT * FROM athlete_guardians 
            WHERE athlete_id = :ath_id AND organization_id = :org_id AND deleted_at IS NULL
            ORDER BY is_primary DESC, id ASC LIMIT 1
        ");
        $gStmt->execute([':ath_id' => $id, ':org_id' => $organizationId]);
        $athlete['guardian'] = $gStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        // Fetch documents
        $dStmt = $this->pdo->prepare("
            SELECT * FROM athlete_documents 
            WHERE athlete_id = :ath_id AND organization_id = :org_id AND deleted_at IS NULL
            ORDER BY id DESC
        ");
        $dStmt->execute([':ath_id' => $id, ':org_id' => $organizationId]);
        $athlete['documents'] = $dStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Fetch linked user account if user_id exists
        if (!empty($athlete['user_id'])) {
            $uStmt = $this->pdo->prepare("
                SELECT u.id, u.uuid, u.username, u.email, u.status as user_status, u.last_login_at,
                       ou.access_status, ou.role_id, r.name as role_name
                FROM users u
                LEFT JOIN organization_users ou ON u.id = ou.user_id AND ou.organization_id = :org_id
                LEFT JOIN roles r ON ou.role_id = r.id
                WHERE u.id = :uid AND u.deleted_at IS NULL
                LIMIT 1
            ");
            $uStmt->execute([':uid' => $athlete['user_id'], ':org_id' => $organizationId]);
            $athlete['user_account'] = $uStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } else {
            $athlete['user_account'] = null;
        }

        return $athlete;
    }

    public function create(array $data): array
    {
        if (!$this->pdo) return $data;

        $this->pdo->beginTransaction();
        try {
            $athleteCode = $data['athlete_code'] ?? ('ATH-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4)));
            $userId = !empty($data['user_id']) ? (int)$data['user_id'] : null;

            $sql = "INSERT INTO athletes (organization_id, user_id, athlete_code, first_name, middle_name, last_name, date_of_birth, gender, blood_group, current_sport_id, phone, email, address_line1, city, state, country, postal_code, registration_date, joining_date, status, notes, created_at, updated_at) 
                    VALUES (:org_id, :user_id, :athlete_code, :first_name, :middle_name, :last_name, :dob, :gender, :blood_group, :sport_id, :phone, :email, :addr, :city, :state, 'India', :zip, CURDATE(), CURDATE(), :status, :notes, NOW(), NOW())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':org_id' => $data['organization_id'],
                ':user_id' => $userId,
                ':athlete_code' => $athleteCode,
                ':first_name' => $data['first_name'],
                ':middle_name' => $data['middle_name'] ?? null,
                ':last_name' => $data['last_name'] ?? '',
                ':dob' => $data['date_of_birth'],
                ':gender' => $data['gender'] ?? 'not_specified',
                ':blood_group' => $data['blood_group'] ?? null,
                ':sport_id' => (int)($data['current_sport_id'] ?? $data['primary_sport_id'] ?? 1),
                ':phone' => $data['phone'] ?? null,
                ':email' => $data['email'] ?? null,
                ':addr' => $data['address_line1'] ?? null,
                ':city' => $data['city'] ?? 'Pune',
                ':state' => $data['state'] ?? 'Maharashtra',
                ':zip' => $data['postal_code'] ?? null,
                ':status' => $data['status'] ?? 'active',
                ':notes' => $data['notes'] ?? null,
            ]);
            $athleteId = (int)$this->pdo->lastInsertId();
            $data['id'] = $athleteId;
            $data['athlete_code'] = $athleteCode;

            // Insert Guardian if provided
            $guardianName = trim(($data['guardian_first_name'] ?? '') . ' ' . ($data['guardian_last_name'] ?? '')) ?: ($data['guardian_name'] ?? '');
            if (!empty($guardianName)) {
                $gSql = "INSERT INTO athlete_guardians (organization_id, athlete_id, full_name, relationship, phone, email, address_line1, city, state, postal_code, is_primary, is_emergency_contact, created_at, updated_at)
                         VALUES (:org_id, :ath_id, :name, :rel, :phone, :email, :addr, :city, :state, :zip, 1, :emrg, NOW(), NOW())";
                $gStmt = $this->pdo->prepare($gSql);
                $gStmt->execute([
                    ':org_id' => $data['organization_id'],
                    ':ath_id' => $athleteId,
                    ':name' => $guardianName,
                    ':rel' => $data['guardian_relationship'] ?? 'Guardian',
                    ':phone' => $data['guardian_phone'] ?? ($data['phone'] ?? ''),
                    ':email' => $data['guardian_email'] ?? null,
                    ':addr' => $data['guardian_address_line1'] ?? null,
                    ':city' => $data['guardian_city'] ?? null,
                    ':state' => $data['guardian_state'] ?? null,
                    ':zip' => $data['guardian_postal_code'] ?? null,
                    ':emrg' => !empty($data['is_emergency_contact']) ? 1 : 0,
                ]);
            }

            // Assign to team if specified
            if (!empty($data['team_id'])) {
                $tStmt = $this->pdo->prepare("
                    INSERT INTO team_members (organization_id, team_id, athlete_id, start_date, is_current, member_role, created_at, updated_at)
                    VALUES (:org_id, :team_id, :ath_id, CURDATE(), 1, 'player', NOW(), NOW())
                ");
                $tStmt->execute([
                    ':org_id' => $data['organization_id'],
                    ':team_id' => (int)$data['team_id'],
                    ':ath_id' => $athleteId,
                ]);
            }

            $this->pdo->commit();
            return $data;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function update(int $organizationId, int $id, array $data): bool
    {
        if (!$this->pdo) return false;

        $this->pdo->beginTransaction();
        try {
            $allowedFields = [
                'user_id', 'first_name', 'middle_name', 'last_name', 'date_of_birth', 'gender',
                'blood_group', 'current_sport_id', 'phone', 'email', 'status',
                'address_line1', 'city', 'state', 'postal_code', 'notes'
            ];

            $fields = [];
            $params = [':org_id' => $organizationId, ':id' => $id];
            foreach ($data as $key => $val) {
                if (in_array($key, $allowedFields, true)) {
                    $fields[] = "{$key} = :{$key}";
                    $params[":{$key}"] = $val;
                }
            }

            if (!empty($fields)) {
                $sql = "UPDATE athletes SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE organization_id = :org_id AND id = :id AND deleted_at IS NULL";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            }

            // Update guardian if provided
            $guardianName = trim(($data['guardian_first_name'] ?? '') . ' ' . ($data['guardian_last_name'] ?? '')) ?: ($data['guardian_name'] ?? '');
            if (!empty($guardianName)) {
                $checkG = $this->pdo->prepare("SELECT id FROM athlete_guardians WHERE athlete_id = :ath_id AND organization_id = :org_id AND deleted_at IS NULL LIMIT 1");
                $checkG->execute([':ath_id' => $id, ':org_id' => $organizationId]);
                $gId = $checkG->fetchColumn();

                if ($gId) {
                    $uG = $this->pdo->prepare("
                        UPDATE athlete_guardians SET full_name = :name, relationship = :rel, phone = :phone, email = :email, updated_at = NOW()
                        WHERE id = :gid
                    ");
                    $uG->execute([
                        ':name' => $guardianName,
                        ':rel' => $data['guardian_relationship'] ?? 'Guardian',
                        ':phone' => $data['guardian_phone'] ?? '',
                        ':email' => $data['guardian_email'] ?? null,
                        ':gid' => $gId,
                    ]);
                } else {
                    $iG = $this->pdo->prepare("
                        INSERT INTO athlete_guardians (organization_id, athlete_id, full_name, relationship, phone, email, is_primary, is_emergency_contact, created_at, updated_at)
                        VALUES (:org_id, :ath_id, :name, :rel, :phone, :email, 1, 1, NOW(), NOW())
                    ");
                    $iG->execute([
                        ':org_id' => $organizationId,
                        ':ath_id' => $id,
                        ':name' => $guardianName,
                        ':rel' => $data['guardian_relationship'] ?? 'Guardian',
                        ':phone' => $data['guardian_phone'] ?? '',
                        ':email' => $data['guardian_email'] ?? null,
                    ]);
                }
            }

            // Update team assignment if provided
            if (isset($data['team_id'])) {
                $this->pdo->prepare("UPDATE team_members SET is_current = 0, end_date = CURDATE() WHERE athlete_id = :ath_id AND organization_id = :org_id")->execute([':ath_id' => $id, ':org_id' => $organizationId]);

                if (!empty($data['team_id'])) {
                    $tStmt = $this->pdo->prepare("
                        INSERT INTO team_members (organization_id, team_id, athlete_id, start_date, is_current, member_role, created_at, updated_at)
                        VALUES (:org_id, :team_id, :ath_id, CURDATE(), 1, 'player', NOW(), NOW())
                    ");
                    $tStmt->execute([
                        ':org_id' => $organizationId,
                        ':team_id' => (int)$data['team_id'],
                        ':ath_id' => $id,
                    ]);
                }
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function delete(int $organizationId, int $id): bool
    {
        if (!$this->pdo) return false;
        $stmt = $this->pdo->prepare("UPDATE athletes SET deleted_at = NOW() WHERE organization_id = :org_id AND id = :id");
        return $stmt->execute([':org_id' => $organizationId, ':id' => $id]);
    }
}
