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

    public function listTeams(
        int $organizationId,
        int $page = 1,
        int $limit = 20,
        ?string $search = null,
        ?int $sportId = null,
        ?string $status = null,
        ?int $coachId = null,
        ?int $athleteId = null
    ): array {
        if (!$this->pdo) {
            return [
                'data' => [],
                'total' => 0,
                'page' => 1,
                'limit' => $limit,
                'total_pages' => 0,
                'from' => 0,
                'to' => 0,
            ];
        }

        $page = max(1, $page);
        $limit = max(1, $limit);

        $conditions = ["t.organization_id = :org_id", "t.deleted_at IS NULL"];
        $params = [':org_id' => $organizationId];

        if ($search !== null && trim($search) !== '') {
            $q = '%' . trim($search) . '%';
            $conditions[] = "(
                t.name LIKE :search1
                OR t.team_code LIKE :search2
                OR t.age_group LIKE :search3
                OR t.formation_or_level LIKE :search4
                OR s.name LIKE :search5
            )";
            $params[':search1'] = $q;
            $params[':search2'] = $q;
            $params[':search3'] = $q;
            $params[':search4'] = $q;
            $params[':search5'] = $q;
        }

        if (!empty($sportId)) {
            $conditions[] = "t.sport_id = :sport_id";
            $params[':sport_id'] = $sportId;
        }

        if (!empty($status)) {
            $conditions[] = "t.status = :status";
            $params[':status'] = $status;
        }

        if (!empty($coachId)) {
            $conditions[] = "EXISTS (
                SELECT 1 FROM team_coaches tc_f
                WHERE tc_f.team_id = t.id
                  AND tc_f.organization_id = t.organization_id
                  AND tc_f.coach_id = :filter_coach_id
                  AND (tc_f.end_date IS NULL OR tc_f.end_date > CURDATE())
            )";
            $params[':filter_coach_id'] = $coachId;
        }

        if (!empty($athleteId)) {
            $conditions[] = "EXISTS (
                SELECT 1 FROM team_members tm_f
                WHERE tm_f.team_id = t.id
                  AND tm_f.organization_id = t.organization_id
                  AND tm_f.athlete_id = :filter_athlete_id
                  AND tm_f.is_current = 1
            )";
            $params[':filter_athlete_id'] = $athleteId;
        }

        $whereClause = implode(' AND ', $conditions);

        // Count distinct teams matching filters
        $countSql = "
            SELECT COUNT(DISTINCT t.id)
            FROM teams t
            LEFT JOIN sports s ON t.sport_id = s.id
            WHERE {$whereClause}
        ";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $totalPages = $total > 0 ? (int)ceil($total / $limit) : 1;
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $limit;

        // ONE TEAM = ONE ROW (strictly grouped by t.id)
        $sql = "
            SELECT 
                t.*,
                s.name as sport_name,
                COUNT(DISTINCT a.id) as athlete_count
            FROM teams t
            LEFT JOIN sports s ON t.sport_id = s.id
            LEFT JOIN team_members tm ON t.id = tm.team_id AND tm.organization_id = t.organization_id AND tm.is_current = 1
            LEFT JOIN athletes a ON tm.athlete_id = a.id AND a.deleted_at IS NULL
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

        $teams = $this->attachTeamCoaches($organizationId, $teams);

        $rowCount = count($teams);
        $from = ($total > 0 && $rowCount > 0) ? ($offset + 1) : 0;
        $to = ($total > 0 && $rowCount > 0) ? min($total, $offset + $rowCount) : 0;

        return [
            'data' => $teams,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $total > 0 ? (int)ceil($total / $limit) : 0,
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * Attach active coaches to each team row without causing SQL row duplication.
     */
    protected function attachTeamCoaches(int $organizationId, array $teams): array
    {
        if (!$this->pdo || empty($teams)) {
            return $teams;
        }

        $teamIds = [];
        foreach ($teams as $t) {
            $tid = (int)($t['id'] ?? 0);
            if ($tid > 0) {
                $teamIds[] = $tid;
            }
        }
        $teamIds = array_values(array_unique($teamIds));
        if (empty($teamIds)) {
            return $teams;
        }

        $placeholders = implode(',', array_fill(0, count($teamIds), '?'));
        $sql = "
            SELECT
                tc.team_id,
                tc.coach_id,
                tc.coach_role,
                tc.is_primary,
                cp.coach_code,
                cp.specialization,
                e.first_name,
                e.last_name
            FROM team_coaches tc
            JOIN coach_profiles cp ON tc.coach_id = cp.id AND cp.deleted_at IS NULL
            JOIN employees e ON cp.employee_id = e.id AND e.deleted_at IS NULL
            WHERE tc.organization_id = ?
              AND tc.team_id IN ({$placeholders})
              AND (tc.end_date IS NULL OR tc.end_date > CURDATE())
            ORDER BY tc.is_primary DESC, tc.id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge([$organizationId], $teamIds));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $coachesByTeam = [];
        foreach ($rows as $r) {
            $tid = (int)$r['team_id'];
            $cid = (int)$r['coach_id'];
            if (!isset($coachesByTeam[$tid][$cid])) {
                $fullName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                $r['full_name'] = $fullName;
                $coachesByTeam[$tid][$cid] = $r;
            }
        }

        foreach ($teams as &$team) {
            $tid = (int)($team['id'] ?? 0);
            $activeCoaches = isset($coachesByTeam[$tid]) ? array_values($coachesByTeam[$tid]) : [];
            $primaryCoach = $activeCoaches[0] ?? null;

            $team['coaches'] = $activeCoaches;
            $team['coach_count'] = count($activeCoaches);
            $team['head_coach_name'] = $primaryCoach ? $primaryCoach['full_name'] : null;
            $team['head_coach_id'] = $primaryCoach ? (int)$primaryCoach['coach_id'] : null;
            $team['head_coach_role'] = $primaryCoach ? ($primaryCoach['coach_role'] ?? 'head_coach') : null;
            $team['extra_coaches_count'] = max(0, count($activeCoaches) - 1);
            $team['all_coaches_label'] = !empty($activeCoaches)
                ? implode(', ', array_map(fn($c) => $c['full_name'], $activeCoaches))
                : '';
        }
        unset($team);

        return $teams;
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
            JOIN coach_profiles cp ON tc.coach_id = cp.id AND cp.deleted_at IS NULL
            JOIN employees e ON cp.employee_id = e.id AND e.deleted_at IS NULL
            WHERE tc.team_id = :team_id AND tc.organization_id = :org_id AND (tc.end_date IS NULL OR tc.end_date > CURDATE())
            ORDER BY tc.is_primary DESC, tc.id ASC
        ");
        $coachStmt->execute([':team_id' => $id, ':org_id' => $organizationId]);
        $team['coaches'] = $coachStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Historical Coaches
        $histCoachStmt = $this->pdo->prepare("
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
            WHERE tc.team_id = :team_id AND tc.organization_id = :org_id AND tc.end_date IS NOT NULL AND tc.end_date <= CURDATE()
            ORDER BY tc.end_date DESC
            LIMIT 10
        ");
        $histCoachStmt->execute([':team_id' => $id, ':org_id' => $organizationId]);
        $team['historical_coaches'] = $histCoachStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

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
                a.current_sport_id,
                s.name as sport_name,
                a.status as athlete_status
            FROM team_members tm
            JOIN athletes a ON tm.athlete_id = a.id AND a.deleted_at IS NULL
            LEFT JOIN sports s ON a.current_sport_id = s.id
            WHERE tm.team_id = :team_id AND tm.organization_id = :org_id AND tm.is_current = 1
            ORDER BY 
                CASE tm.member_role
                    WHEN 'captain' THEN 1
                    WHEN 'vice_captain' THEN 2
                    WHEN 'player' THEN 3
                    WHEN 'other' THEN 4
                    ELSE 5
                END ASC,
                tm.jersey_number ASC, 
                a.first_name ASC
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

        $teamName = trim($data['name'] ?? '');
        if ($teamName === '') {
            throw new \InvalidArgumentException("Team Name is required.");
        }
        $sportId = (int)($data['sport_id'] ?? 0);
        if ($sportId <= 0) {
            throw new \InvalidArgumentException("Sport is required.");
        }

        $teamGender = strtolower(trim($data['gender'] ?? 'open'));
        if (!in_array($teamGender, ['male', 'female', 'mixed', 'open', 'not_specified'], true)) {
            $teamGender = 'open';
        }
        $teamStatus = strtolower(trim($data['status'] ?? 'active'));
        if (!in_array($teamStatus, ['active', 'inactive'], true)) {
            $teamStatus = 'active';
        }

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
                ':name' => $teamName,
                ':sport_id' => $sportId,
                ':cat_id' => !empty($data['category_id']) ? (int)$data['category_id'] : null,
                ':gender' => $teamGender,
                ':age_group' => trim($data['age_group'] ?? 'Open') ?: 'Open',
                ':formation' => !empty($data['formation_or_level']) ? trim($data['formation_or_level']) : null,
                ':desc' => !empty($data['description']) ? trim($data['description']) : null,
                ':status' => $teamStatus,
            ]);

            $teamId = (int)$this->pdo->lastInsertId();

            // 1. Normalize and Assign Coaches (supports multiple coaches via coach_ids[] or coaches[] + legacy coach_id)
            $coachAssignments = $this->normalizeCoachAssignmentsFromInput($data);
            $chkCoach = $this->pdo->prepare("
                SELECT cp.id, cp.status, cp.deleted_at, e.deleted_at as emp_deleted_at, e.first_name, e.last_name
                FROM coach_profiles cp
                JOIN employees e ON cp.employee_id = e.id
                WHERE cp.id = :cid AND cp.organization_id = :org_id
                LIMIT 1
            ");
            $tcStmt = $this->pdo->prepare("
                INSERT INTO team_coaches (organization_id, team_id, coach_id, coach_role, start_date, is_primary, created_at, updated_at)
                VALUES (:org_id, :team_id, :coach_id, :role, CURDATE(), :is_primary, NOW(), NOW())
            ");

            foreach ($coachAssignments as $ca) {
                $cid = (int)$ca['coach_id'];
                $chkCoach->execute([':cid' => $cid, ':org_id' => $organizationId]);
                $coachRow = $chkCoach->fetch(PDO::FETCH_ASSOC);
                if (!$coachRow || $coachRow['deleted_at'] !== null || $coachRow['emp_deleted_at'] !== null) {
                    throw new \InvalidArgumentException("Coach #{$cid} not found or does not belong to your organization.");
                }
                if ($coachRow['status'] !== 'active') {
                    throw new \InvalidArgumentException("Coach {$coachRow['first_name']} {$coachRow['last_name']} is not active.");
                }

                $tcStmt->execute([
                    ':org_id' => $organizationId,
                    ':team_id' => $teamId,
                    ':coach_id' => $cid,
                    ':role' => $ca['coach_role'],
                    ':is_primary' => $ca['is_primary'] ? 1 : 0,
                ]);
                $tcId = (int)$this->pdo->lastInsertId();

                $this->auditLog->log(
                    $organizationId,
                    $performedBy,
                    'TEAM_COACH_ASSIGN',
                    'Teams',
                    'team_coaches',
                    $tcId,
                    null,
                    ['team_id' => $teamId, 'coach_id' => $cid, 'coach_role' => $ca['coach_role'], 'is_primary' => $ca['is_primary'] ? 1 : 0],
                    "Assigned coach #{$cid} ({$coachRow['first_name']} {$coachRow['last_name']}) to team #{$teamId} ({$teamName}) as {$ca['coach_role']}"
                );
            }

            // 2. Assign Registered Athletes (strictly validate organization, active status, and sport compatibility)
            if (!empty($data['athlete_ids']) && is_array($data['athlete_ids'])) {
                $chkAth = $this->pdo->prepare("
                    SELECT id, athlete_code, first_name, last_name, status, deleted_at, current_sport_id, gender
                    FROM athletes
                    WHERE id = :ath_id AND organization_id = :org_id AND deleted_at IS NULL
                    LIMIT 1
                ");
                $tmStmt = $this->pdo->prepare("
                    INSERT INTO team_members (organization_id, team_id, athlete_id, start_date, is_current, member_role, created_at, updated_at)
                    VALUES (:org_id, :team_id, :ath_id, CURDATE(), 1, 'player', NOW(), NOW())
                ");
                $seenAth = [];
                foreach ($data['athlete_ids'] as $athId) {
                    $aid = (int)$athId;
                    if ($aid <= 0 || isset($seenAth[$aid])) {
                        continue;
                    }
                    $seenAth[$aid] = true;

                    $chkAth->execute([':ath_id' => $aid, ':org_id' => $organizationId]);
                    $athlete = $chkAth->fetch(PDO::FETCH_ASSOC);
                    if (!$athlete) {
                        throw new \InvalidArgumentException("Athlete #{$aid} not found or does not belong to your organization.");
                    }
                    if ($athlete['status'] !== 'active') {
                        throw new \InvalidArgumentException("Only active registered athletes can be added to a team roster.");
                    }
                    if (!empty($sportId) && (int)($athlete['current_sport_id'] ?? 0) !== $sportId) {
                        throw new \InvalidArgumentException("Athlete {$athlete['first_name']} {$athlete['last_name']}'s primary sport does not match the team's sport.");
                    }
                    if (in_array($teamGender, ['male', 'female'], true)) {
                        $athGender = strtolower(trim($athlete['gender'] ?? ''));
                        if ($athGender !== '' && $athGender !== 'not_specified' && $athGender !== $teamGender) {
                            throw new \InvalidArgumentException("Athlete {$athlete['first_name']} {$athlete['last_name']}'s gender does not match the team's gender division.");
                        }
                    }

                    $tmStmt->execute([
                        ':org_id' => $organizationId,
                        ':team_id' => $teamId,
                        ':ath_id' => $aid,
                    ]);
                    $tmId = (int)$this->pdo->lastInsertId();

                    $this->auditLog->log(
                        $organizationId,
                        $performedBy,
                        'TEAM_MEMBER_ADD',
                        'Teams',
                        'team_members',
                        $tmId,
                        null,
                        ['team_id' => $teamId, 'athlete_id' => $aid, 'member_role' => 'player'],
                        "Added athlete #{$aid} ({$athlete['first_name']} {$athlete['last_name']}) to team #{$teamId} ({$teamName})"
                    );
                }
            }

            $this->pdo->commit();

            // 3. Audit Log for Team Creation
            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'TEAM_CREATE',
                'Teams',
                'teams',
                $teamId,
                null,
                ['team_code' => $teamCode, 'name' => $teamName, 'sport_id' => $sportId],
                "Created team {$teamName} ({$teamCode})"
            );

            return [
                'id' => $teamId,
                'team_code' => $teamCode,
                'name' => $teamName,
                'sport_id' => $sportId,
                'status' => $teamStatus,
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Normalize multi-coach or single-coach input into a deduplicated list with at most one primary coach.
     */
    protected function normalizeCoachAssignmentsFromInput(array $data): array
    {
        $validRoles = ['head_coach', 'assistant_coach', 'fitness_coach', 'other'];
        $rawList = [];

        if (!empty($data['coaches']) && is_array($data['coaches'])) {
            foreach ($data['coaches'] as $item) {
                if (is_array($item) && !empty($item['coach_id'])) {
                    $rawList[] = [
                        'coach_id' => (int)$item['coach_id'],
                        'coach_role' => in_array($item['coach_role'] ?? '', $validRoles, true) ? $item['coach_role'] : 'head_coach',
                        'is_primary' => !empty($item['is_primary']),
                    ];
                }
            }
        } elseif (!empty($data['coach_ids']) && is_array($data['coach_ids'])) {
            $roles = isset($data['coach_roles']) && is_array($data['coach_roles']) ? $data['coach_roles'] : [];
            $primaryId = !empty($data['primary_coach_id']) ? (int)$data['primary_coach_id'] : 0;
            foreach ($data['coach_ids'] as $idx => $cidVal) {
                $cid = (int)$cidVal;
                if ($cid <= 0) continue;
                $role = $roles[$cid] ?? ($roles[$idx] ?? 'assistant_coach');
                if (!in_array($role, $validRoles, true)) {
                    $role = 'assistant_coach';
                }
                $isPrim = ($primaryId > 0) ? ($cid === $primaryId) : ($role === 'head_coach');
                $rawList[] = [
                    'coach_id' => $cid,
                    'coach_role' => $role,
                    'is_primary' => $isPrim,
                ];
            }
        } elseif (!empty($data['coach_id'])) {
            $cid = (int)$data['coach_id'];
            if ($cid > 0) {
                $role = in_array($data['coach_role'] ?? '', $validRoles, true) ? $data['coach_role'] : 'head_coach';
                $rawList[] = [
                    'coach_id' => $cid,
                    'coach_role' => $role,
                    'is_primary' => true,
                ];
            }
        }

        // Deduplicate by coach_id and enforce at most ONE primary coach
        $deduped = [];
        $hasPrimary = false;
        foreach ($rawList as $item) {
            $cid = (int)$item['coach_id'];
            if ($cid <= 0 || isset($deduped[$cid])) {
                continue;
            }
            $isPrimary = (bool)$item['is_primary'];
            if ($isPrimary) {
                if ($hasPrimary) {
                    $isPrimary = false;
                    if ($item['coach_role'] === 'head_coach') {
                        $item['coach_role'] = 'assistant_coach';
                    }
                } else {
                    $hasPrimary = true;
                }
            }
            $item['is_primary'] = $isPrimary;
            $deduped[$cid] = $item;
        }

        // If coaches exist and none was marked primary, promote the first head_coach (or first coach) as primary
        if (!empty($deduped) && !$hasPrimary) {
            $promoted = false;
            foreach ($deduped as &$cItem) {
                if ($cItem['coach_role'] === 'head_coach') {
                    $cItem['is_primary'] = true;
                    $promoted = true;
                    break;
                }
            }
            unset($cItem);
            if (!$promoted) {
                $firstKey = array_key_first($deduped);
                $deduped[$firstKey]['is_primary'] = true;
            }
        }

        return array_values($deduped);
    }

    public function updateTeam(int $organizationId, int $id, array $data, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;

        $existing = $this->getTeam($organizationId, $id);
        if (!$existing) return false;

        $newName = trim($data['name'] ?? $existing['name']);
        $newSportId = (int)($data['sport_id'] ?? $existing['sport_id']);
        $newGender = strtolower(trim($data['gender'] ?? $existing['gender']));
        if (!in_array($newGender, ['male', 'female', 'mixed', 'open', 'not_specified'], true)) {
            $newGender = $existing['gender'] ?? 'open';
        }
        $newStatus = strtolower(trim($data['status'] ?? $existing['status']));
        if (!in_array($newStatus, ['active', 'inactive'], true)) {
            $newStatus = $existing['status'] ?? 'active';
        }

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
                ':name' => $newName,
                ':sport_id' => $newSportId,
                ':gender' => $newGender,
                ':age_group' => $data['age_group'] ?? $existing['age_group'],
                ':formation' => $data['formation_or_level'] ?? $existing['formation_or_level'],
                ':desc' => $data['description'] ?? $existing['description'],
                ':status' => $newStatus,
                ':id' => $id,
                ':org_id' => $organizationId,
            ]);

            // Sync multi-coach staff if sync_coaches or coach_ids / coaches array is passed
            if (!empty($data['sync_coaches']) || isset($data['coach_ids']) || isset($data['coaches'])) {
                $desiredCoaches = $this->normalizeCoachAssignmentsFromInput($data);
                $desiredById = [];
                foreach ($desiredCoaches as $dc) {
                    $desiredById[(int)$dc['coach_id']] = $dc;
                }

                // Validate all desired coaches first
                $chkCoach = $this->pdo->prepare("
                    SELECT cp.id, cp.status, cp.deleted_at, e.deleted_at as emp_deleted_at, e.first_name, e.last_name
                    FROM coach_profiles cp
                    JOIN employees e ON cp.employee_id = e.id
                    WHERE cp.id = :cid AND cp.organization_id = :org_id
                    LIMIT 1
                ");
                foreach ($desiredById as $cid => $dc) {
                    $chkCoach->execute([':cid' => $cid, ':org_id' => $organizationId]);
                    $cRow = $chkCoach->fetch(PDO::FETCH_ASSOC);
                    if (!$cRow || $cRow['deleted_at'] !== null || $cRow['emp_deleted_at'] !== null) {
                        throw new \InvalidArgumentException("Coach #{$cid} not found or does not belong to your organization.");
                    }
                    if ($cRow['status'] !== 'active') {
                        throw new \InvalidArgumentException("Coach {$cRow['first_name']} {$cRow['last_name']} is not active.");
                    }
                }

                // Fetch current active coaches for this team
                $currStmt = $this->pdo->prepare("
                    SELECT id, coach_id, coach_role, is_primary
                    FROM team_coaches
                    WHERE team_id = :team_id AND organization_id = :org_id
                      AND (end_date IS NULL OR end_date > CURDATE())
                ");
                $currStmt->execute([':team_id' => $id, ':org_id' => $organizationId]);
                $currRows = $currStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                $currByCoachId = [];
                foreach ($currRows as $cr) {
                    $currByCoachId[(int)$cr['coach_id']] = $cr;
                }

                // Soft-close coaches removed from selection
                $closeCoachStmt = $this->pdo->prepare("
                    UPDATE team_coaches
                    SET is_primary = 0, end_date = CURDATE(), updated_at = NOW()
                    WHERE id = :id AND organization_id = :org_id
                ");
                foreach ($currByCoachId as $cid => $cr) {
                    if (!isset($desiredById[$cid])) {
                        $closeCoachStmt->execute([':id' => (int)$cr['id'], ':org_id' => $organizationId]);
                    }
                }

                // Reset primary flag before applying desired primary state
                $unsetStmt = $this->pdo->prepare("
                    UPDATE team_coaches
                    SET is_primary = 0, updated_at = NOW()
                    WHERE team_id = :team_id AND organization_id = :org_id
                      AND (end_date IS NULL OR end_date > CURDATE())
                ");
                $unsetStmt->execute([':team_id' => $id, ':org_id' => $organizationId]);

                $upCoachStmt = $this->pdo->prepare("
                    UPDATE team_coaches
                    SET coach_role = :role, is_primary = :is_primary, updated_at = NOW()
                    WHERE id = :id AND organization_id = :org_id
                ");
                $insCoachStmt = $this->pdo->prepare("
                    INSERT INTO team_coaches (organization_id, team_id, coach_id, coach_role, start_date, is_primary, created_at, updated_at)
                    VALUES (:org_id, :team_id, :coach_id, :role, CURDATE(), :is_primary, NOW(), NOW())
                ");

                foreach ($desiredById as $cid => $dc) {
                    if (isset($currByCoachId[$cid])) {
                        $upCoachStmt->execute([
                            ':role' => $dc['coach_role'],
                            ':is_primary' => $dc['is_primary'] ? 1 : 0,
                            ':id' => (int)$currByCoachId[$cid]['id'],
                            ':org_id' => $organizationId,
                        ]);
                    } else {
                        $insCoachStmt->execute([
                            ':org_id' => $organizationId,
                            ':team_id' => $id,
                            ':coach_id' => $cid,
                            ':role' => $dc['coach_role'],
                            ':is_primary' => $dc['is_primary'] ? 1 : 0,
                        ]);
                    }
                }
            } elseif (isset($data['coach_id'])) {
                // Legacy single primary coach update
                $coachId = (int)$data['coach_id'];
                if ($coachId > 0) {
                    $chkCoach = $this->pdo->prepare("
                        SELECT cp.id FROM coach_profiles cp
                        JOIN employees e ON cp.employee_id = e.id
                        WHERE cp.id = :cid AND cp.organization_id = :org_id AND cp.deleted_at IS NULL AND e.deleted_at IS NULL
                        LIMIT 1
                    ");
                    $chkCoach->execute([':cid' => $coachId, ':org_id' => $organizationId]);
                    if (!$chkCoach->fetchColumn()) {
                        throw new \InvalidArgumentException("Coach not found or does not belong to your organization.");
                    }

                    $unsetStmt = $this->pdo->prepare("UPDATE team_coaches SET is_primary = 0 WHERE team_id = :team_id AND organization_id = :org_id");
                    $unsetStmt->execute([':team_id' => $id, ':org_id' => $organizationId]);

                    $checkStmt = $this->pdo->prepare("
                        SELECT id FROM team_coaches
                        WHERE team_id = :team_id AND coach_id = :coach_id AND organization_id = :org_id
                          AND (end_date IS NULL OR end_date > CURDATE())
                        ORDER BY id DESC LIMIT 1
                    ");
                    $checkStmt->execute([':team_id' => $id, ':coach_id' => $coachId, ':org_id' => $organizationId]);
                    $activeRow = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    if ($activeRow) {
                        $upCoach = $this->pdo->prepare("UPDATE team_coaches SET is_primary = 1, updated_at = NOW() WHERE id = :id AND organization_id = :org_id");
                        $upCoach->execute([':id' => (int)$activeRow['id'], ':org_id' => $organizationId]);
                    } else {
                        $insCoach = $this->pdo->prepare("INSERT INTO team_coaches (organization_id, team_id, coach_id, coach_role, start_date, is_primary, created_at, updated_at) VALUES (:org_id, :team_id, :coach_id, 'head_coach', CURDATE(), 1, NOW(), NOW())");
                        $insCoach->execute([':org_id' => $organizationId, ':team_id' => $id, ':coach_id' => $coachId]);
                    }
                }
            }

            // Sync registered athlete roster if sync_roster or athlete_ids is passed
            if (!empty($data['sync_roster']) || isset($data['athlete_ids'])) {
                $rawAthIds = isset($data['athlete_ids']) && is_array($data['athlete_ids']) ? $data['athlete_ids'] : [];
                $desiredAthIds = [];
                foreach ($rawAthIds as $aidVal) {
                    $aid = (int)$aidVal;
                    if ($aid > 0) {
                        $desiredAthIds[$aid] = $aid;
                    }
                }

                // Validate all desired athletes belong to org, are active, and match sport
                $chkAth = $this->pdo->prepare("
                    SELECT id, athlete_code, first_name, last_name, status, deleted_at, current_sport_id, gender
                    FROM athletes
                    WHERE id = :ath_id AND organization_id = :org_id AND deleted_at IS NULL
                    LIMIT 1
                ");
                foreach ($desiredAthIds as $aid) {
                    $chkAth->execute([':ath_id' => $aid, ':org_id' => $organizationId]);
                    $aRow = $chkAth->fetch(PDO::FETCH_ASSOC);
                    if (!$aRow) {
                        throw new \InvalidArgumentException("Athlete #{$aid} not found or does not belong to your organization.");
                    }
                    if ($aRow['status'] !== 'active') {
                        throw new \InvalidArgumentException("Only active registered athletes can be assigned to a team roster.");
                    }
                    if (!empty($newSportId) && (int)($aRow['current_sport_id'] ?? 0) !== $newSportId) {
                        throw new \InvalidArgumentException("Athlete {$aRow['first_name']} {$aRow['last_name']}'s primary sport does not match the team's sport.");
                    }
                    if (in_array($newGender, ['male', 'female'], true)) {
                        $athGender = strtolower(trim($aRow['gender'] ?? ''));
                        if ($athGender !== '' && $athGender !== 'not_specified' && $athGender !== $newGender) {
                            throw new \InvalidArgumentException("Athlete {$aRow['first_name']} {$aRow['last_name']}'s gender does not match the team's gender division.");
                        }
                    }
                }

                // Fetch current active members
                $currMemStmt = $this->pdo->prepare("
                    SELECT id, athlete_id
                    FROM team_members
                    WHERE team_id = :team_id AND organization_id = :org_id AND is_current = 1
                ");
                $currMemStmt->execute([':team_id' => $id, ':org_id' => $organizationId]);
                $currMemRows = $currMemStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                $currByAthId = [];
                foreach ($currMemRows as $mr) {
                    $currByAthId[(int)$mr['athlete_id']] = (int)$mr['id'];
                }

                // Soft-close removed members (preserving historical membership)
                $closeMemStmt = $this->pdo->prepare("
                    UPDATE team_members
                    SET is_current = 0, end_date = CURDATE(), updated_at = NOW()
                    WHERE id = :id AND organization_id = :org_id
                ");
                foreach ($currByAthId as $aid => $memRowId) {
                    if (!isset($desiredAthIds[$aid])) {
                        $closeMemStmt->execute([':id' => $memRowId, ':org_id' => $organizationId]);
                    }
                }

                // Insert newly added members (skip already active members to prevent duplicates)
                $insMemStmt = $this->pdo->prepare("
                    INSERT INTO team_members (organization_id, team_id, athlete_id, start_date, is_current, member_role, created_at, updated_at)
                    VALUES (:org_id, :team_id, :ath_id, CURDATE(), 1, 'player', NOW(), NOW())
                ");
                foreach ($desiredAthIds as $aid) {
                    if (!isset($currByAthId[$aid])) {
                        $insMemStmt->execute([
                            ':org_id' => $organizationId,
                            ':team_id' => $id,
                            ':ath_id' => $aid,
                        ]);
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
                "Updated team #{$id} ({$newName})"
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

    public function assignCoach(
        int $organizationId,
        int $teamId,
        int $coachId,
        string $role = 'head_coach',
        ?bool $isPrimary = null,
        ?int $performedBy = null
    ): bool {
        if (!$this->pdo) return false;

        // 1. Verify team exists, belongs to organization, and is not soft-deleted
        $team = $this->getTeam($organizationId, $teamId);
        if (!$team) {
            throw new \InvalidArgumentException("Team not found or access denied.");
        }

        // 2. Verify coach exists, belongs to organization, is not deleted, and is active
        $cStmt = $this->pdo->prepare("
            SELECT cp.id, cp.status, cp.deleted_at, e.deleted_at as emp_deleted_at, e.first_name, e.last_name
            FROM coach_profiles cp
            JOIN employees e ON cp.employee_id = e.id
            WHERE cp.id = :coach_id AND cp.organization_id = :org_id
            LIMIT 1
        ");
        $cStmt->execute([':coach_id' => $coachId, ':org_id' => $organizationId]);
        $coach = $cStmt->fetch(PDO::FETCH_ASSOC);

        if (!$coach || $coach['deleted_at'] !== null || $coach['emp_deleted_at'] !== null) {
            throw new \InvalidArgumentException("Coach not found or does not belong to your organization.");
        }
        if ($coach['status'] !== 'active') {
            throw new \InvalidArgumentException("Only active coaches can be assigned to a team.");
        }

        // 3. Validate role enum
        $validRoles = ['head_coach', 'assistant_coach', 'fitness_coach', 'other'];
        if (!in_array($role, $validRoles, true)) {
            throw new \InvalidArgumentException("Invalid coach role specified. Must be one of: " . implode(', ', $validRoles) . ".");
        }

        // 4. Determine primary behavior:
        // If $isPrimary is null, use the application convention: head_coach => primary by default, otherwise false.
        $effectivePrimary = $isPrimary !== null ? (bool)$isPrimary : ($role === 'head_coach');

        // 5. Transaction for safe primary assignment and idempotency
        $this->pdo->beginTransaction();
        try {
            // Check for existing active assignment
            $checkStmt = $this->pdo->prepare("
                SELECT id, coach_role, is_primary, start_date, end_date
                FROM team_coaches
                WHERE team_id = :team_id AND coach_id = :coach_id AND organization_id = :org_id
                  AND (end_date IS NULL OR end_date > CURDATE())
                ORDER BY id DESC
                LIMIT 1
            ");
            $checkStmt->execute([
                ':team_id' => $teamId,
                ':coach_id' => $coachId,
                ':org_id' => $organizationId
            ]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $this->pdo->rollBack();
                throw new \InvalidArgumentException("Coach is already assigned to this team.");
            }

            // Not actively assigned: if setting primary, unset other coaches' primary state first
                if ($effectivePrimary) {
                    $unsetStmt = $this->pdo->prepare("
                        UPDATE team_coaches
                        SET is_primary = 0, updated_at = NOW()
                        WHERE team_id = :team_id AND organization_id = :org_id
                          AND (end_date IS NULL OR end_date > CURDATE())
                    ");
                    $unsetStmt->execute([
                        ':team_id' => $teamId,
                        ':org_id' => $organizationId
                    ]);
                }

                $insStmt = $this->pdo->prepare("
                    INSERT INTO team_coaches (
                        organization_id, team_id, coach_id, coach_role, start_date, end_date, is_primary, created_at, updated_at
                    ) VALUES (
                        :org_id, :team_id, :coach_id, :role, CURDATE(), NULL, :is_primary, NOW(), NOW()
                    )
                ");
                $insStmt->execute([
                    ':org_id' => $organizationId,
                    ':team_id' => $teamId,
                    ':coach_id' => $coachId,
                    ':role' => $role,
                    ':is_primary' => $effectivePrimary ? 1 : 0
                ]);

                $assignmentId = (int)$this->pdo->lastInsertId();

            $this->pdo->commit();

            // 6. Audit Log
            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'TEAM_COACH_ASSIGN',
                'Teams',
                'team_coaches',
                $assignmentId,
                $existing ?: null,
                [
                    'team_id' => $teamId,
                    'coach_id' => $coachId,
                    'coach_role' => $role,
                    'is_primary' => $effectivePrimary ? 1 : 0
                ],
                "Assigned coach #{$coachId} ({$coach['first_name']} {$coach['last_name']}) to team #{$teamId} ({$team['name']}) as {$role}"
            );

            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function assignAthlete(int $organizationId, int $teamId, int $athleteId, string $role = 'player', ?int $jersey = null, ?int $performedBy = null): bool
    {
        return $this->addAthlete($organizationId, $teamId, $athleteId, [
            'member_role' => $role,
            'jersey_number' => $jersey
        ], $performedBy);
    }

    public function addAthlete(int $organizationId, int $teamId, int $athleteId, array $data = [], ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;

        // 1. Verify team belongs to organization and is not deleted
        $team = $this->getTeam($organizationId, $teamId);
        if (!$team) {
            throw new \InvalidArgumentException("Team not found or access denied.");
        }

        // 2. Verify athlete belongs to organization, is active, and is not deleted
        $aStmt = $this->pdo->prepare("
            SELECT id, athlete_code, first_name, last_name, status, deleted_at, current_sport_id, gender
            FROM athletes
            WHERE id = :ath_id AND organization_id = :org_id AND deleted_at IS NULL
            LIMIT 1
        ");
        $aStmt->execute([':ath_id' => $athleteId, ':org_id' => $organizationId]);
        $athlete = $aStmt->fetch(PDO::FETCH_ASSOC);

        if (!$athlete) {
            throw new \InvalidArgumentException("Athlete not found or does not belong to your organization.");
        }
        if ($athlete['status'] !== 'active') {
            throw new \InvalidArgumentException("Only active athletes can be added to a team roster.");
        }

        if (!empty($team['sport_id']) && !empty($athlete['current_sport_id']) && $team['sport_id'] != $athlete['current_sport_id']) {
            throw new \InvalidArgumentException("Athlete's primary sport does not match the team's sport.");
        }

        $teamGender = strtolower(trim($team['gender'] ?? 'open'));
        if (in_array($teamGender, ['male', 'female'], true)) {
            $athleteGender = strtolower(trim($athlete['gender'] ?? ''));
            if ($athleteGender !== $teamGender) {
                throw new \InvalidArgumentException("Athlete's gender does not match the team's gender division.");
            }
        }

        // 3. Prevent duplicate CURRENT membership (safely idempotent)
        $checkStmt = $this->pdo->prepare("
            SELECT id, is_current
            FROM team_members
            WHERE team_id = :team_id AND athlete_id = :ath_id AND organization_id = :org_id AND is_current = 1
            LIMIT 1
        ");
        $checkStmt->execute([
            ':team_id' => $teamId,
            ':ath_id' => $athleteId,
            ':org_id' => $organizationId
        ]);
        if ($checkStmt->fetch()) {
            throw new \InvalidArgumentException("Athlete is already an active member of this team.");
        }

        $jersey = !empty($data['jersey_number']) ? trim((string)$data['jersey_number']) : null;
        $role = !empty($data['member_role']) ? trim((string)$data['member_role']) : 'player';
        if (!in_array($role, ['player', 'captain', 'vice_captain', 'other'], true)) {
            $role = 'player';
        }
        
        $position = null;
        if ($role === 'other') {
            $position = !empty($data['position']) ? trim((string)$data['position']) : null;
            if (empty($position)) {
                throw new \InvalidArgumentException("Custom position is required when role is 'Other'.");
            }
        }
        
        $startDate = !empty($data['start_date']) && strtotime($data['start_date']) ? $data['start_date'] : date('Y-m-d');

        $this->pdo->beginTransaction();
        try {
            $insertStmt = $this->pdo->prepare("
                INSERT INTO team_members (
                    organization_id, team_id, athlete_id, jersey_number,
                    position, member_role, start_date, is_current,
                    created_at, updated_at
                ) VALUES (
                    :org_id, :team_id, :ath_id, :jersey,
                    :pos, :role, :start_date, 1,
                    NOW(), NOW()
                )
            ");
            $insertStmt->execute([
                ':org_id' => $organizationId,
                ':team_id' => $teamId,
                ':ath_id' => $athleteId,
                ':jersey' => $jersey,
                ':pos' => $position,
                ':role' => $role,
                ':start_date' => $startDate
            ]);
            $memberId = (int)$this->pdo->lastInsertId();

            $this->pdo->commit();

            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'TEAM_MEMBER_ADD',
                'Teams',
                'team_members',
                $memberId,
                null,
                [
                    'team_id' => $teamId,
                    'athlete_id' => $athleteId,
                    'jersey_number' => $jersey,
                    'position' => $position,
                    'member_role' => $role
                ],
                "Added athlete #{$athleteId} ({$athlete['first_name']} {$athlete['last_name']}) to team #{$teamId} ({$team['name']})"
            );

            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateAthleteRoster(int $organizationId, int $teamId, int $athleteId, array $data, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;

        $team = $this->getTeam($organizationId, $teamId);
        if (!$team) {
            throw new \InvalidArgumentException("Team not found or access denied.");
        }

        $checkStmt = $this->pdo->prepare("
            SELECT id, jersey_number, member_role
            FROM team_members
            WHERE team_id = :team_id AND athlete_id = :ath_id AND organization_id = :org_id AND is_current = 1
            LIMIT 1
        ");
        $checkStmt->execute([
            ':team_id' => $teamId,
            ':ath_id' => $athleteId,
            ':org_id' => $organizationId
        ]);
        $member = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            throw new \InvalidArgumentException("Athlete is not currently an active member of this team.");
        }

        $jersey = isset($data['jersey_number']) && $data['jersey_number'] !== '' ? trim((string)$data['jersey_number']) : null;
        $role = !empty($data['member_role']) ? trim((string)$data['member_role']) : 'player';
        if (!in_array($role, ['player', 'captain', 'vice_captain', 'other'], true)) {
            $role = 'player';
        }

        $position = null;
        if ($role === 'other') {
            $position = !empty($data['position']) ? trim((string)$data['position']) : null;
            if (empty($position)) {
                throw new \InvalidArgumentException("Custom position is required when role is 'Other'.");
            }
        }

        $this->pdo->beginTransaction();
        try {
            $updateStmt = $this->pdo->prepare("
                UPDATE team_members
                SET jersey_number = :jersey, member_role = :role, position = :pos, updated_at = NOW()
                WHERE id = :id AND organization_id = :org_id
            ");
            $updateStmt->execute([
                ':jersey' => $jersey,
                ':role' => $role,
                ':pos' => $position,
                ':id' => $member['id'],
                ':org_id' => $organizationId
            ]);

            $this->pdo->commit();

            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'TEAM_ROSTER_UPDATE',
                'Teams',
                'team_members',
                $member['id'],
                $member,
                ['jersey_number' => $jersey, 'member_role' => $role, 'position' => $position],
                "Updated roster details for athlete #{$athleteId} in team #{$teamId}"
            );

            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function removeAthlete(int $organizationId, int $teamId, int $athleteId, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;

        // 1. Verify team belongs to organization and is not deleted
        $team = $this->getTeam($organizationId, $teamId);
        if (!$team) {
            throw new \InvalidArgumentException("Team not found or access denied.");
        }

        // 2. Verify athlete exists and belongs to organization
        $aStmt = $this->pdo->prepare("
            SELECT id, athlete_code, first_name, last_name
            FROM athletes
            WHERE id = :ath_id AND organization_id = :org_id AND deleted_at IS NULL
            LIMIT 1
        ");
        $aStmt->execute([':ath_id' => $athleteId, ':org_id' => $organizationId]);
        $athlete = $aStmt->fetch(PDO::FETCH_ASSOC);
        if (!$athlete) {
            throw new \InvalidArgumentException("Athlete not found or does not belong to your organization.");
        }

        // 3. Find current membership for this athlete in this team
        $mStmt = $this->pdo->prepare("
            SELECT id, is_current, member_role, start_date
            FROM team_members
            WHERE team_id = :team_id AND athlete_id = :ath_id AND organization_id = :org_id AND is_current = 1
            ORDER BY id DESC
            LIMIT 1
        ");
        $mStmt->execute([
            ':team_id' => $teamId,
            ':ath_id' => $athleteId,
            ':org_id' => $organizationId
        ]);
        $member = $mStmt->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            throw new \InvalidArgumentException("Athlete is not currently an active member of this team.");
        }

        $this->pdo->beginTransaction();
        try {
            // Set is_current = 0 and end_date = CURDATE() (preserving historical membership)
            $upStmt = $this->pdo->prepare("
                UPDATE team_members SET
                    is_current = 0,
                    end_date = CURDATE(),
                    updated_at = NOW()
                WHERE id = :member_id AND organization_id = :org_id
            ");
            $upStmt->execute([
                ':member_id' => (int)$member['id'],
                ':org_id' => $organizationId
            ]);

            $this->pdo->commit();

            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'TEAM_MEMBER_REMOVE',
                'Teams',
                'team_members',
                (int)$member['id'],
                $member,
                ['is_current' => 0, 'end_date' => date('Y-m-d')],
                "Removed athlete #{$athleteId} ({$athlete['first_name']} {$athlete['last_name']}) from team #{$teamId} ({$team['name']})"
            );

            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function removeCoach(int $organizationId, int $teamId, int $coachId, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;

        // 1. Verify team belongs to organization and is not deleted
        $team = $this->getTeam($organizationId, $teamId);
        if (!$team) {
            throw new \InvalidArgumentException("Team not found or access denied.");
        }

        // 2. Verify coach exists and belongs to organization
        $cStmt = $this->pdo->prepare("
            SELECT cp.id, cp.coach_code, e.first_name, e.last_name
            FROM coach_profiles cp
            JOIN employees e ON cp.employee_id = e.id
            WHERE cp.id = :coach_id AND cp.organization_id = :org_id AND cp.deleted_at IS NULL AND e.deleted_at IS NULL
            LIMIT 1
        ");
        $cStmt->execute([':coach_id' => $coachId, ':org_id' => $organizationId]);
        $coach = $cStmt->fetch(PDO::FETCH_ASSOC);
        if (!$coach) {
            throw new \InvalidArgumentException("Coach not found or does not belong to your organization.");
        }

        // 3. Find active assignment
        $checkStmt = $this->pdo->prepare("
            SELECT id, coach_role, is_primary, start_date, end_date
            FROM team_coaches
            WHERE team_id = :team_id AND coach_id = :coach_id AND organization_id = :org_id
              AND (end_date IS NULL OR end_date > CURDATE())
            ORDER BY id DESC
            LIMIT 1
        ");
        $checkStmt->execute([
            ':team_id' => $teamId,
            ':coach_id' => $coachId,
            ':org_id' => $organizationId
        ]);
        $active = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if (!$active) {
            throw new \InvalidArgumentException("Coach is not currently actively assigned to this team.");
        }

        // 4. Soft-close active assignment (no hard delete, preserve historical record)
        $this->pdo->beginTransaction();
        try {
            $upStmt = $this->pdo->prepare("
                UPDATE team_coaches
                SET is_primary = 0,
                    end_date = CURDATE(),
                    updated_at = NOW()
                WHERE id = :id AND organization_id = :org_id
            ");
            $upStmt->execute([
                ':id' => (int)$active['id'],
                ':org_id' => $organizationId
            ]);

            $this->pdo->commit();

            // 5. Audit Log
            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'TEAM_COACH_REMOVE',
                'Teams',
                'team_coaches',
                (int)$active['id'],
                $active,
                ['is_primary' => 0, 'end_date' => date('Y-m-d')],
                "Removed coach #{$coachId} ({$coach['first_name']} {$coach['last_name']}) from team #{$teamId} ({$team['name']})"
            );

            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateCoachRole(
        int $organizationId,
        int $teamId,
        int $coachId,
        string $newRole,
        ?int $performedBy = null,
        ?bool $isPrimary = null
    ): bool {
        if (!$this->pdo) return false;

        // 1. Verify team belongs to organization and is not deleted
        $team = $this->getTeam($organizationId, $teamId);
        if (!$team) {
            throw new \InvalidArgumentException("Team not found or access denied.");
        }

        // 2. Validate role enum
        $validRoles = ['head_coach', 'assistant_coach', 'fitness_coach', 'other'];
        if (!in_array($newRole, $validRoles, true)) {
            throw new \InvalidArgumentException("Invalid coach role specified. Must be one of: " . implode(', ', $validRoles) . ".");
        }

        // 3. Find active assignment
        $checkStmt = $this->pdo->prepare("
            SELECT id, coach_role, is_primary, start_date, end_date
            FROM team_coaches
            WHERE team_id = :team_id AND coach_id = :coach_id AND organization_id = :org_id
              AND (end_date IS NULL OR end_date > CURDATE())
            ORDER BY id DESC
            LIMIT 1
        ");
        $checkStmt->execute([
            ':team_id' => $teamId,
            ':coach_id' => $coachId,
            ':org_id' => $organizationId
        ]);
        $active = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if (!$active) {
            throw new \InvalidArgumentException("Coach is not currently actively assigned to this team.");
        }

        $targetPrimary = $isPrimary !== null ? ($isPrimary ? 1 : 0) : (int)$active['is_primary'];

        if ($active['coach_role'] === $newRole && (int)$active['is_primary'] === $targetPrimary) {
            return true; // No change needed
        }

        // 4. Update the role and primary designation safely
        $this->pdo->beginTransaction();
        try {
            if ($targetPrimary === 1) {
                $unsetStmt = $this->pdo->prepare("
                    UPDATE team_coaches
                    SET is_primary = 0, updated_at = NOW()
                    WHERE team_id = :team_id AND organization_id = :org_id AND id != :current_id
                      AND (end_date IS NULL OR end_date > CURDATE())
                ");
                $unsetStmt->execute([
                    ':team_id' => $teamId,
                    ':org_id' => $organizationId,
                    ':current_id' => (int)$active['id']
                ]);
            }

            $upStmt = $this->pdo->prepare("
                UPDATE team_coaches
                SET coach_role = :role,
                    is_primary = :is_primary,
                    updated_at = NOW()
                WHERE id = :id AND organization_id = :org_id
            ");
            $upStmt->execute([
                ':role' => $newRole,
                ':is_primary' => $targetPrimary,
                ':id' => (int)$active['id'],
                ':org_id' => $organizationId
            ]);

            $this->pdo->commit();

            // 5. Audit Log
            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'TEAM_COACH_UPDATE_ROLE',
                'Teams',
                'team_coaches',
                (int)$active['id'],
                $active,
                ['coach_role' => $newRole, 'is_primary' => $targetPrimary],
                "Updated coach #{$coachId} role from '{$active['coach_role']}' to '{$newRole}' on team #{$teamId} ({$team['name']})"
            );

            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Server-side organization-scoped and sport-scoped registered athlete search for team roster selection.
     */
    public function searchEligibleAthletes(
        int $organizationId,
        int $sportId,
        ?string $search = null,
        ?string $gender = null,
        ?int $excludeTeamId = null,
        int $limit = 30
    ): array {
        if (!$this->pdo || $sportId <= 0) {
            return [];
        }

        $limit = max(1, min(100, $limit));
        $conditions = [
            "a.organization_id = :org_id",
            "a.deleted_at IS NULL",
            "a.status = 'active'",
            "a.current_sport_id = :sport_id"
        ];
        $params = [
            ':org_id' => $organizationId,
            ':sport_id' => $sportId,
        ];

        $genderClean = strtolower(trim((string)($gender ?? '')));
        if (in_array($genderClean, ['male', 'female'], true)) {
            $conditions[] = "(a.gender = :gender OR a.gender = 'not_specified')";
            $params[':gender'] = $genderClean;
        }

        $q = trim((string)($search ?? ''));
        if ($q !== '') {
            $conditions[] = "(
                a.first_name LIKE :s1
                OR a.last_name LIKE :s2
                OR CONCAT(COALESCE(a.first_name, ''), ' ', COALESCE(a.last_name, '')) LIKE :s3
                OR a.athlete_code LIKE :s4
            )";
            $like = "%{$q}%";
            $params[':s1'] = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
            $params[':s4'] = $like;
        }

        $whereClause = implode(' AND ', $conditions);
        $sql = "
            SELECT
                a.id,
                a.athlete_code,
                a.first_name,
                a.last_name,
                a.gender,
                a.date_of_birth,
                a.status,
                a.current_sport_id,
                s.name AS sport_name
            FROM athletes a
            LEFT JOIN sports s ON a.current_sport_id = s.id
            WHERE {$whereClause}
            ORDER BY a.first_name ASC, a.last_name ASC
            LIMIT :limit
        ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $athletes = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (empty($athletes)) {
            return [];
        }

        // Hydrate active teams for these athletes so the UI displays current team memberships and prevents duplicate enrollment
        $athIds = array_values(array_unique(array_map(fn($r) => (int)$r['id'], $athletes)));
        $placeholders = implode(',', array_fill(0, count($athIds), '?'));
        $tmStmt = $this->pdo->prepare("
            SELECT tm.athlete_id, t.id AS team_id, t.name AS team_name
            FROM team_members tm
            JOIN teams t ON tm.team_id = t.id AND t.deleted_at IS NULL AND t.status = 'active'
            WHERE tm.organization_id = ?
              AND tm.is_current = 1
              AND tm.athlete_id IN ({$placeholders})
            ORDER BY tm.id DESC
        ");
        $tmStmt->execute(array_merge([$organizationId], $athIds));
        $tmRows = $tmStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $teamsByAth = [];
        foreach ($tmRows as $tr) {
            $aid = (int)$tr['athlete_id'];
            $tid = (int)$tr['team_id'];
            $teamsByAth[$aid][$tid] = $tr['team_name'];
        }

        foreach ($athletes as &$a) {
            $aid = (int)$a['id'];
            $currTeams = $teamsByAth[$aid] ?? [];
            $a['full_name'] = trim(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? ''));
            $a['current_team_ids'] = array_keys($currTeams);
            $a['current_teams'] = array_values($currTeams);
            $a['current_teams_label'] = !empty($currTeams) ? implode(', ', array_values($currTeams)) : '—';
            $a['already_in_team'] = ($excludeTeamId && isset($currTeams[$excludeTeamId])) ? true : false;
        }
        unset($a);

        return $athletes;
    }

    /**
     * Server-side organization-scoped coach search for team coaching staff selection.
     */
    public function searchAvailableCoaches(
        int $organizationId,
        ?string $search = null,
        ?string $sportName = null,
        ?int $excludeTeamId = null,
        int $limit = 30
    ): array {
        if (!$this->pdo) {
            return [];
        }

        $limit = max(1, min(100, $limit));
        $conditions = [
            "cp.organization_id = :org_id",
            "e.organization_id = :emp_org_id",
            "cp.status = 'active'",
            "cp.deleted_at IS NULL",
            "e.deleted_at IS NULL"
        ];
        $params = [
            ':org_id' => $organizationId,
            ':emp_org_id' => $organizationId,
        ];

        $q = trim((string)($search ?? ''));
        if ($q !== '') {
            $conditions[] = "(
                e.first_name LIKE :s1
                OR e.last_name LIKE :s2
                OR CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) LIKE :s3
                OR cp.coach_code LIKE :s4
                OR cp.specialization LIKE :s5
            )";
            $like = "%{$q}%";
            $params[':s1'] = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
            $params[':s4'] = $like;
            $params[':s5'] = $like;
        }

        $whereClause = implode(' AND ', $conditions);
        $sql = "
            SELECT
                cp.id AS coach_id,
                cp.coach_code,
                cp.specialization,
                e.first_name,
                e.last_name,
                e.designation
            FROM coach_profiles cp
            JOIN employees e ON cp.employee_id = e.id
            WHERE {$whereClause}
            ORDER BY e.first_name ASC, e.last_name ASC
            LIMIT :limit
        ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $coaches = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (empty($coaches)) {
            return [];
        }

        $coachIds = array_values(array_unique(array_map(fn($c) => (int)$c['coach_id'], $coaches)));
        $placeholders = implode(',', array_fill(0, count($coachIds), '?'));
        $tcStmt = $this->pdo->prepare("
            SELECT tc.coach_id, t.id AS team_id, t.name AS team_name
            FROM team_coaches tc
            JOIN teams t ON tc.team_id = t.id AND t.deleted_at IS NULL AND t.status = 'active'
            WHERE tc.organization_id = ?
              AND (tc.end_date IS NULL OR tc.end_date > CURDATE())
              AND tc.coach_id IN ({$placeholders})
            ORDER BY tc.is_primary DESC, tc.id DESC
        ");
        $tcStmt->execute(array_merge([$organizationId], $coachIds));
        $tcRows = $tcStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $teamsByCoach = [];
        foreach ($tcRows as $tr) {
            $cid = (int)$tr['coach_id'];
            $tid = (int)$tr['team_id'];
            $teamsByCoach[$cid][$tid] = $tr['team_name'];
        }

        $sportLower = strtolower(trim((string)($sportName ?? '')));
        foreach ($coaches as &$c) {
            $cid = (int)$c['coach_id'];
            $currTeams = $teamsByCoach[$cid] ?? [];
            $spec = trim((string)($c['specialization'] ?? ''));
            $c['full_name'] = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
            $c['current_team_ids'] = array_keys($currTeams);
            $c['current_teams'] = array_values($currTeams);
            $c['current_teams_label'] = !empty($currTeams) ? implode(', ', array_values($currTeams)) : '—';
            $c['already_in_team'] = ($excludeTeamId && isset($currTeams[$excludeTeamId])) ? true : false;
            $c['sport_match'] = ($sportLower !== '' && $spec !== '' && str_contains(strtolower($spec), $sportLower));
        }
        unset($c);

        // Sort coaches whose specialization matches the selected sport first
        if ($sportLower !== '') {
            usort($coaches, function ($a, $b) {
                if ($a['sport_match'] !== $b['sport_match']) {
                    return $a['sport_match'] ? -1 : 1;
                }
                return strcmp($a['full_name'], $b['full_name']);
            });
        }

        return $coaches;
    }

    public function getEligibleCoaches(int $organizationId, int $teamId): array
    {
        if (!$this->pdo) return [];

        $stmt = $this->pdo->prepare("
            SELECT 
                cp.id as coach_id,
                cp.coach_code,
                cp.specialization,
                e.first_name,
                e.last_name
            FROM coach_profiles cp
            JOIN employees e ON cp.employee_id = e.id
            WHERE cp.organization_id = :org_id
              AND cp.status = 'active'
              AND cp.deleted_at IS NULL
              AND e.deleted_at IS NULL
              AND cp.id NOT IN (
                  SELECT tc.coach_id
                  FROM team_coaches tc
                  WHERE tc.team_id = :team_id
                    AND tc.organization_id = :org_id2
                    AND (tc.end_date IS NULL OR tc.end_date > CURDATE())
              )
            ORDER BY e.first_name ASC, e.last_name ASC
        ");
        $stmt->execute([
            ':org_id' => $organizationId,
            ':team_id' => $teamId,
            ':org_id2' => $organizationId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Update team status between active and inactive non-destructively
     */
    public function updateStatus(int $organizationId, int $id, string $status, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new \InvalidArgumentException("Invalid team status: {$status}");
        }

        $existing = $this->getTeam($organizationId, $id);
        if (!$existing || !empty($existing['deleted_at'])) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            UPDATE teams 
            SET status = :status, updated_at = NOW() 
            WHERE id = :id AND organization_id = :org_id AND deleted_at IS NULL
        ");
        $ok = $stmt->execute([
            ':status' => $status,
            ':id' => $id,
            ':org_id' => $organizationId,
        ]);

        if ($ok) {
            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'TEAM_UPDATE',
                'Teams',
                'teams',
                $id,
                ['status' => $existing['status']],
                ['status' => $status],
                "Changed team #{$id} ({$existing['name']}) status to {$status}"
            );
        }

        return $ok;
    }

    public function getActiveTeams(int $organizationId): array
    {
        if (!$this->pdo) return [];

        $stmt = $this->pdo->prepare("SELECT id, name, team_code, sport_id FROM teams WHERE organization_id = :org_id AND status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
        $stmt->execute([':org_id' => $organizationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
