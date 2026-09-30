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

    /**
     * Build shared SQL WHERE conditions and bound parameters for athlete list, count, and export.
     */
    protected function buildFilterConditions(
        int $organizationId,
        ?string $search = null,
        ?int $sportId = null,
        ?string $status = null,
        ?int $coachId = null
    ): array {
        $where = "a.organization_id = :org_id AND a.deleted_at IS NULL";
        $params = [':org_id' => $organizationId];

        $search = $search !== null ? trim($search) : '';
        if ($search !== '') {
            $where .= " AND (
                a.first_name LIKE :search_first
                OR a.last_name LIKE :search_last
                OR CONCAT(COALESCE(a.first_name, ''), ' ', COALESCE(a.last_name, '')) LIKE :search_full
                OR a.athlete_code LIKE :search_code
                OR a.email LIKE :search_email
                OR a.phone LIKE :search_phone
            )";
            $likeVal = "%{$search}%";
            $params[':search_first'] = $likeVal;
            $params[':search_last']  = $likeVal;
            $params[':search_full']  = $likeVal;
            $params[':search_code']  = $likeVal;
            $params[':search_email'] = $likeVal;
            $params[':search_phone'] = $likeVal;
        }

        if (!empty($sportId) && $sportId > 0) {
            $where .= " AND a.current_sport_id = :sport_id";
            $params[':sport_id'] = (int)$sportId;
        }

        $status = $status !== null ? trim($status) : '';
        if ($status !== '') {
            $where .= " AND a.status = :status";
            $params[':status'] = $status;
        }

        if (!empty($coachId) && $coachId > 0) {
            $where .= " AND EXISTS (
                SELECT 1
                FROM team_members cm
                INNER JOIN teams ct ON cm.team_id = ct.id AND ct.deleted_at IS NULL
                INNER JOIN team_coaches tc ON ct.id = tc.team_id
                WHERE cm.athlete_id = a.id
                  AND cm.is_current = 1
                  AND tc.coach_id = :coach_id
                  AND tc.organization_id = :coach_org_id
            )";
            $params[':coach_id'] = (int)$coachId;
            $params[':coach_org_id'] = $organizationId;
        }

        return [$where, $params];
    }

    /**
     * Batch-hydrate active current team memberships for a list of athlete rows without multiplying athlete rows.
     */
    protected function attachActiveTeams(int $organizationId, array $rows): array
    {
        if (empty($rows) || !$this->pdo) {
            return $rows;
        }

        $athleteIds = array_values(array_unique(array_filter(array_map(fn($r) => (int)($r['id'] ?? 0), $rows))));
        if (empty($athleteIds)) {
            return $rows;
        }

        $placeholders = implode(',', array_fill(0, count($athleteIds), '?'));
        $sql = "
            SELECT mem.athlete_id, tm.id AS team_id, tm.name AS team_name, mem.member_role
            FROM team_members mem
            INNER JOIN teams tm ON mem.team_id = tm.id
            WHERE mem.athlete_id IN ({$placeholders})
              AND mem.is_current = 1
              AND tm.deleted_at IS NULL
              AND tm.status = 'active'
              AND tm.organization_id = ?
            ORDER BY mem.id DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $bindValues = array_merge($athleteIds, [$organizationId]);
        $stmt->execute($bindValues);
        $teamRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $teamsByAthlete = [];
        foreach ($teamRows as $tr) {
            $aid = (int)$tr['athlete_id'];
            $tid = (int)$tr['team_id'];
            $normName = strtolower(trim((string)($tr['team_name'] ?? '')));
            if ($normName === '') {
                continue;
            }
            if (!isset($teamsByAthlete[$aid])) {
                $teamsByAthlete[$aid] = [];
            }
            // Deduplicate by normalized team name (and team_id) while preserving most recent assignment order
            if (!isset($teamsByAthlete[$aid][$normName])) {
                $teamsByAthlete[$aid][$normName] = [
                    'id' => $tid,
                    'name' => trim((string)$tr['team_name']),
                    'member_role' => $tr['member_role'] ?? 'player',
                ];
            }
        }

        foreach ($rows as &$row) {
            $aid = (int)($row['id'] ?? 0);
            $activeTeams = isset($teamsByAthlete[$aid]) ? array_values($teamsByAthlete[$aid]) : [];
            $row['teams'] = $activeTeams;
            $row['team_id'] = $activeTeams[0]['id'] ?? null;
            $row['team_name'] = $activeTeams[0]['name'] ?? null;
            $row['extra_teams_count'] = max(0, count($activeTeams) - 1);
            $row['all_teams_label'] = !empty($activeTeams)
                ? implode(', ', array_column($activeTeams, 'name'))
                : 'Unassigned';
        }
        unset($row);

        return $rows;
    }

    public function getPaginated(
        int $organizationId,
        int $page = 1,
        int $limit = 20,
        ?string $search = null,
        ?int $sportId = null,
        ?string $status = null,
        ?int $coachId = null
    ): array {
        if (!$this->pdo) {
            return [
                'data' => [],
                'total' => 0,
                'page' => 1,
                'limit' => $limit,
                'total_pages' => 1,
                'from' => 0,
                'to' => 0,
            ];
        }

        $page = max(1, $page);
        $limit = max(1, $limit);
        [$where, $params] = $this->buildFilterConditions($organizationId, $search, $sportId, $status, $coachId);

        // Count unique non-deleted athletes matching filters
        $countStmt = $this->pdo->prepare("SELECT COUNT(DISTINCT a.id) FROM athletes a WHERE {$where}");
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        $totalPages = max(1, (int)ceil($total / $limit));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = max(0, ($page - 1) * $limit);

        // Fetch unique athlete records (1 row per athlete.id)
        $sql = "
            SELECT a.*, s.name AS sport_name
            FROM athletes a
            LEFT JOIN sports s ON a.current_sport_id = s.id
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

        $rows = $this->attachActiveTeams($organizationId, $rows);

        $from = $total > 0 ? ($offset + 1) : 0;
        $to = $total > 0 ? min($total, $offset + count($rows)) : 0;

        return [
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'current_page' => $page,
            'limit' => $limit,
            'per_page' => $limit,
            'total_pages' => $totalPages,
            'last_page' => $totalPages,
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * Fetch all filtered unique athletes without pagination (for Excel export).
     */
    public function getAllFiltered(
        int $organizationId,
        ?string $search = null,
        ?int $sportId = null,
        ?string $status = null,
        ?int $coachId = null
    ): array {
        if (!$this->pdo) return [];

        [$where, $params] = $this->buildFilterConditions($organizationId, $search, $sportId, $status, $coachId);

        $sql = "
            SELECT a.*, s.name AS sport_name
            FROM athletes a
            LEFT JOIN sports s ON a.current_sport_id = s.id
            WHERE {$where}
            ORDER BY a.id DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return $this->attachActiveTeams($organizationId, $rows);
    }

    public function findById(int $organizationId, int $id): ?array
    {
        if (!$this->pdo) return null;

        $stmt = $this->pdo->prepare("
            SELECT a.*, s.name AS sport_name
            FROM athletes a
            LEFT JOIN sports s ON a.current_sport_id = s.id
            WHERE a.organization_id = :org_id AND a.id = :id AND a.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->bindValue(':org_id', $organizationId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $athlete = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$athlete) return null;

        $hydrated = $this->attachActiveTeams($organizationId, [$athlete]);
        $athlete = $hydrated[0];

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

        // Soft-delete athlete record only; preserve historical records (user account, team history, documents, guardians, medical, attendance)
        $stmt = $this->pdo->prepare("UPDATE athletes SET deleted_at = NOW(), updated_at = NOW() WHERE organization_id = :org_id AND id = :id AND deleted_at IS NULL");
        $deleted = $stmt->execute([':org_id' => $organizationId, ':id' => $id]);

        return $deleted && $stmt->rowCount() > 0;
    }
}
