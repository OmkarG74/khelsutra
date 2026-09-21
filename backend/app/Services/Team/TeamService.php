<?php

namespace App\Services\Team;

use App\Services\BaseService;
use App\Services\Audit\AuditLogService;
use PDO;

class TeamService extends BaseService
{
    protected AuditLogService $auditLog;

    public function __construct(?PDO $pdo = null, ?AuditLogService $auditLog = null)
    {
        parent::__construct($pdo);
        $this->auditLog = $auditLog ?? new AuditLogService($this->pdo);
    }

    public function listTeams(int $organizationId, int $page = 1, int $limit = 15, ?string $search = null, ?int $sportId = null, ?string $status = null): array
    {
        if (!$this->pdo) {
            return ['data' => [], 'total' => 0, 'page' => 1, 'limit' => $limit, 'total_pages' => 0];
        }

        $conditions = ["t.organization_id = :org_id", "t.deleted_at IS NULL"];
        $params = [':org_id' => $organizationId];

        if (!empty($search)) {
            $conditions[] = "(t.name LIKE :search OR t.team_code LIKE :search OR t.age_group LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if (!empty($sportId)) {
            $conditions[] = "t.sport_id = :sport_id";
            $params[':sport_id'] = $sportId;
        }

        if (!empty($status)) {
            $conditions[] = "t.status = :status";
            $params[':status'] = $status;
        }

        $whereClause = implode(' AND ', $conditions);

        // Count total
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM teams t WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT 
                t.*,
                s.name as sport_name,
                COUNT(DISTINCT tm.athlete_id) as athlete_count,
                CONCAT(e.first_name, ' ', e.last_name) as head_coach_name,
                cp.id as head_coach_id
            FROM teams t
            LEFT JOIN sports s ON t.sport_id = s.id
            LEFT JOIN team_members tm ON t.id = tm.team_id AND tm.is_current = 1
            LEFT JOIN team_coaches tc ON t.id = tc.team_id AND tc.is_primary = 1
            LEFT JOIN coach_profiles cp ON tc.coach_id = cp.id
            LEFT JOIN employees e ON cp.employee_id = e.id
            WHERE {$whereClause}
            GROUP BY t.id
            ORDER BY t.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $teams = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'data' => $teams,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / max(1, $limit)),
        ];
    }

    public function getTeam(int $organizationId, int $id): ?array
    {
        if (!$this->pdo) return null;

        $stmt = $this->pdo->prepare("
            SELECT t.*, s.name as sport_name
            FROM teams t
            LEFT JOIN sports s ON t.sport_id = s.id
            WHERE t.organization_id = :org_id AND t.id = :id AND t.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':org_id' => $organizationId, ':id' => $id]);
        $team = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$team) return null;

        // Assigned Coaches
        $coachStmt = $this->pdo->prepare("
            SELECT 
                tc.*,
                cp.id as coach_profile_id,
                cp.coach_code,
                cp.specialization,
                e.first_name,
                e.last_name,
                e.phone,
                e.email
            FROM team_coaches tc
            JOIN coach_profiles cp ON tc.coach_id = cp.id
            JOIN employees e ON cp.employee_id = e.id
            WHERE tc.team_id = :team_id AND tc.organization_id = :org_id
            ORDER BY tc.is_primary DESC, tc.id ASC
        ");
        $coachStmt->execute([':team_id' => $id, ':org_id' => $organizationId]);
        $team['coaches'] = $coachStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Current Athletes
        $athStmt = $this->pdo->prepare("
            SELECT 
                tm.*,
                a.id as athlete_id,
                a.athlete_code,
                a.first_name,
                a.last_name,
                a.gender,
                a.date_of_birth,
                a.status as athlete_status
            FROM team_members tm
            JOIN athletes a ON tm.athlete_id = a.id AND a.deleted_at IS NULL
            WHERE tm.team_id = :team_id AND tm.organization_id = :org_id AND tm.is_current = 1
            ORDER BY tm.jersey_number ASC, a.first_name ASC
        ");
        $athStmt->execute([':team_id' => $id, ':org_id' => $organizationId]);
        $team['current_athletes'] = $athStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Historical Athletes
        $histStmt = $this->pdo->prepare("
            SELECT 
                tm.*,
                a.id as athlete_id,
                a.athlete_code,
                a.first_name,
                a.last_name
            FROM team_members tm
            JOIN athletes a ON tm.athlete_id = a.id
            WHERE tm.team_id = :team_id AND tm.organization_id = :org_id AND tm.is_current = 0
            ORDER BY tm.end_date DESC
            LIMIT 10
        ");
        $histStmt->execute([':team_id' => $id, ':org_id' => $organizationId]);
        $team['historical_athletes'] = $histStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Upcoming Training Sessions
        $trainStmt = $this->pdo->prepare("
            SELECT ts.*, v.name as venue_name, CONCAT(e.first_name, ' ', e.last_name) as coach_name
            FROM training_sessions ts
            LEFT JOIN venues v ON ts.venue_id = v.id
            LEFT JOIN coach_profiles cp ON ts.coach_id = cp.id
            LEFT JOIN employees e ON cp.employee_id = e.id
            WHERE ts.team_id = :team_id AND ts.organization_id = :org_id AND ts.deleted_at IS NULL
            ORDER BY ts.training_date ASC, ts.start_time ASC
            LIMIT 5
        ");
        $trainStmt->execute([':team_id' => $id, ':org_id' => $organizationId]);
        $team['upcoming_training'] = $trainStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Upcoming Matches & Fixtures
        $matchStmt = $this->pdo->prepare("
            SELECT f.*, t1.name as home_team_name, t2.name as away_team_name, v.name as venue_name
            FROM fixtures f
            LEFT JOIN teams t1 ON f.home_team_id = t1.id
            LEFT JOIN teams t2 ON f.away_team_id = t2.id
            LEFT JOIN venues v ON f.venue_id = v.id
            WHERE (f.home_team_id = :team_id OR f.away_team_id = :team_id) AND f.organization_id = :org_id AND f.deleted_at IS NULL
            ORDER BY f.scheduled_date ASC, f.scheduled_start_time ASC
            LIMIT 5
        ");
        $matchStmt->execute([':team_id' => $id, ':org_id' => $organizationId]);
        $team['upcoming_matches'] = $matchStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return $team;
    }

    public function createTeam(int $organizationId, array $data, ?int $performedBy = null): array
    {
        if (!$this->pdo) return [];

        $this->pdo->beginTransaction();
        try {
            $teamCode = $data['team_code'] ?? ('TM-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4)));

            $sql = "
                INSERT INTO teams (
                    organization_id, team_code, name, sport_id, category_id,
                    gender, age_group, formation_or_level, description, status,
                    created_at, updated_at
                ) VALUES (
                    :org_id, :team_code, :name, :sport_id, :cat_id,
                    :gender, :age_group, :formation, :desc, :status,
                    NOW(), NOW()
                )
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':org_id' => $organizationId,
                ':team_code' => $teamCode,
                ':name' => trim($data['name'] ?? ''),
                ':sport_id' => (int)($data['sport_id'] ?? 1),
                ':cat_id' => !empty($data['category_id']) ? (int)$data['category_id'] : null,
                ':gender' => $data['gender'] ?? 'open',
                ':age_group' => $data['age_group'] ?? 'Open',
                ':formation' => $data['formation_or_level'] ?? null,
                ':desc' => $data['description'] ?? null,
                ':status' => $data['status'] ?? 'active',
            ]);

            $teamId = (int)$this->pdo->lastInsertId();

            // 1. Assign Primary Coach
            if (!empty($data['coach_id'])) {
                $tcSql = "
                    INSERT INTO team_coaches (organization_id, team_id, coach_id, coach_role, start_date, is_primary, created_at, updated_at)
                    VALUES (:org_id, :team_id, :coach_id, 'head_coach', CURDATE(), 1, NOW(), NOW())
                ";
                $tcStmt = $this->pdo->prepare($tcSql);
                $tcStmt->execute([
                    ':org_id' => $organizationId,
                    ':team_id' => $teamId,
                    ':coach_id' => (int)$data['coach_id']
                ]);
            }

            // 2. Assign Initial Athletes
            if (!empty($data['athlete_ids']) && is_array($data['athlete_ids'])) {
                $tmSql = "
                    INSERT INTO team_members (organization_id, team_id, athlete_id, start_date, is_current, member_role, created_at, updated_at)
                    VALUES (:org_id, :team_id, :ath_id, CURDATE(), 1, 'player', NOW(), NOW())
                ";
                $tmStmt = $this->pdo->prepare($tmSql);
                foreach ($data['athlete_ids'] as $athId) {
                    if (!empty($athId)) {
                        $tmStmt->execute([
                            ':org_id' => $organizationId,
                            ':team_id' => $teamId,
                            ':ath_id' => (int)$athId
                        ]);
                    }
                }
            }

            $this->pdo->commit();

            // 3. Audit Log
            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'TEAM_CREATE',
                'Teams',
                'teams',
                $teamId,
                null,
                ['team_code' => $teamCode, 'name' => $data['name'] ?? ''],
                "Created team {$data['name']} ({$teamCode})"
            );

            return [
                'id' => $teamId,
                'team_code' => $teamCode,
                'name' => $data['name'] ?? ''
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateTeam(int $organizationId, int $id, array $data, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;

        $existing = $this->getTeam($organizationId, $id);
        if (!$existing) return false;

        $this->pdo->beginTransaction();
        try {
            $sql = "
                UPDATE teams SET
                    name = :name,
                    sport_id = :sport_id,
                    gender = :gender,
                    age_group = :age_group,
                    formation_or_level = :formation,
                    description = :desc,
                    status = :status,
                    updated_at = NOW()
                WHERE id = :id AND organization_id = :org_id
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':name' => trim($data['name'] ?? $existing['name']),
                ':sport_id' => (int)($data['sport_id'] ?? $existing['sport_id']),
                ':gender' => $data['gender'] ?? $existing['gender'],
                ':age_group' => $data['age_group'] ?? $existing['age_group'],
                ':formation' => $data['formation_or_level'] ?? $existing['formation_or_level'],
                ':desc' => $data['description'] ?? $existing['description'],
                ':status' => $data['status'] ?? $existing['status'],
                ':id' => $id,
                ':org_id' => $organizationId,
            ]);

            // Update Primary Coach if changed
            if (isset($data['coach_id'])) {
                $coachId = (int)$data['coach_id'];
                if ($coachId > 0) {
                    // Set all existing coaches as not primary
                    $unsetStmt = $this->pdo->prepare("UPDATE team_coaches SET is_primary = 0 WHERE team_id = :team_id AND organization_id = :org_id");
                    $unsetStmt->execute([':team_id' => $id, ':org_id' => $organizationId]);

                    // Check if coach already linked
                    $checkStmt = $this->pdo->prepare("SELECT id FROM team_coaches WHERE team_id = :team_id AND coach_id = :coach_id AND organization_id = :org_id");
                    $checkStmt->execute([':team_id' => $id, ':coach_id' => $coachId, ':org_id' => $organizationId]);
                    if ($checkStmt->fetch()) {
                        $upCoach = $this->pdo->prepare("UPDATE team_coaches SET is_primary = 1 WHERE team_id = :team_id AND coach_id = :coach_id AND organization_id = :org_id");
                        $upCoach->execute([':team_id' => $id, ':coach_id' => $coachId, ':org_id' => $organizationId]);
                    } else {
                        $insCoach = $this->pdo->prepare("INSERT INTO team_coaches (organization_id, team_id, coach_id, coach_role, start_date, is_primary, created_at, updated_at) VALUES (:org_id, :team_id, :coach_id, 'head_coach', CURDATE(), 1, NOW(), NOW())");
                        $insCoach->execute([':org_id' => $organizationId, ':team_id' => $id, ':coach_id' => $coachId]);
                    }
                }
            }

            $this->pdo->commit();

            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'TEAM_UPDATE',
                'Teams',
                'teams',
                $id,
                $existing,
                $data,
                "Updated team #{$id} ({$existing['name']})"
            );

            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function deleteTeam(int $organizationId, int $id, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;

        $existing = $this->getTeam($organizationId, $id);
        if (!$existing) return false;

        $stmt = $this->pdo->prepare("UPDATE teams SET deleted_at = NOW() WHERE id = :id AND organization_id = :org_id");
        $stmt->execute([':id' => $id, ':org_id' => $organizationId]);

        $this->auditLog->log(
            $organizationId,
            $performedBy,
            'TEAM_DELETE',
            'Teams',
            'teams',
            $id,
            $existing,
            null,
            "Soft deleted team #{$id}"
        );

        return true;
    }

    public function assignCoach(int $organizationId, int $teamId, int $coachId, string $role = 'head_coach', ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;
        $stmt = $this->pdo->prepare("
            INSERT INTO team_coaches (organization_id, team_id, coach_id, coach_role, start_date, is_primary, created_at, updated_at)
            VALUES (:org_id, :team_id, :coach_id, :role, CURDATE(), 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE coach_role = :role2, is_primary = 1, updated_at = NOW()
        ");
        return $stmt->execute([
            ':org_id' => $organizationId,
            ':team_id' => $teamId,
            ':coach_id' => $coachId,
            ':role' => $role,
            ':role2' => $role
        ]);
    }

    public function assignAthlete(int $organizationId, int $teamId, int $athleteId, string $role = 'player', ?int $jersey = null, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;
        $stmt = $this->pdo->prepare("
            INSERT INTO team_members (organization_id, team_id, athlete_id, jersey_number, member_role, start_date, is_current, created_at, updated_at)
            VALUES (:org_id, :team_id, :ath_id, :jersey, :role, CURDATE(), 1, NOW(), NOW())
        ");
        return $stmt->execute([
            ':org_id' => $organizationId,
            ':team_id' => $teamId,
            ':ath_id' => $athleteId,
            ':jersey' => $jersey,
            ':role' => $role
        ]);
    }
}
