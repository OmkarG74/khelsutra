<?php

namespace App\Services\Tournament;

use App\Services\BaseService;
use App\Services\Audit\AuditLogService;
use PDO;

class TournamentService extends BaseService
{
    protected AuditLogService $auditLog;

    public function __construct(?PDO $pdo = null, ?AuditLogService $auditLog = null)
    {
        parent::__construct($pdo);
        $this->auditLog = $auditLog ?? new AuditLogService($this->pdo);
    }

    public function listTournaments(int $organizationId, int $page = 1, int $limit = 15, ?string $search = null, ?string $status = null, ?int $sportId = null): array
    {
        if (!$this->pdo) {
            return ['data' => [], 'total' => 0, 'page' => 1, 'limit' => $limit, 'total_pages' => 0];
        }

        $conditions = ["t.organization_id = :org_id", "t.deleted_at IS NULL"];
        $params = [':org_id' => $organizationId];

        if (!empty($search)) {
            $conditions[] = "(t.name LIKE :search OR t.tournament_reference LIKE :search OR t.organizer_name LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if (!empty($status)) {
            $conditions[] = "t.status = :status";
            $params[':status'] = $status;
        }

        if (!empty($sportId)) {
            $conditions[] = "t.sport_id = :sport_id";
            $params[':sport_id'] = $sportId;
        }

        $whereClause = implode(' AND ', $conditions);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM tournaments t WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT 
                t.*,
                s.name as sport_name,
                tl.name as level_name,
                tf.name as format_name,
                COUNT(DISTINCT tt.team_id) as enrolled_teams_count,
                COUNT(DISTINCT f.id) as fixtures_count
            FROM tournaments t
            LEFT JOIN sports s ON t.sport_id = s.id
            LEFT JOIN tournament_levels tl ON t.tournament_level_id = tl.id
            LEFT JOIN tournament_formats tf ON t.tournament_format_id = tf.id
            LEFT JOIN tournament_teams tt ON t.id = tt.tournament_id
            LEFT JOIN fixtures f ON t.id = f.tournament_id AND f.deleted_at IS NULL
            WHERE {$whereClause}
            GROUP BY t.id
            ORDER BY t.start_date DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'data' => $tournaments,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / max(1, $limit)),
        ];
    }

    public function getTournament(int $organizationId, int $id): ?array
    {
        if (!$this->pdo) return null;

        $stmt = $this->pdo->prepare("
            SELECT 
                t.*,
                s.name as sport_name,
                tl.name as level_name,
                tf.name as format_name
            FROM tournaments t
            LEFT JOIN sports s ON t.sport_id = s.id
            LEFT JOIN tournament_levels tl ON t.tournament_level_id = tl.id
            LEFT JOIN tournament_formats tf ON t.tournament_format_id = tf.id
            WHERE t.id = :id AND t.organization_id = :org_id AND t.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':org_id' => $organizationId]);
        $tournament = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tournament) return null;

        // Participating Teams
        $ttStmt = $this->pdo->prepare("
            SELECT 
                tt.*,
                tm.name as team_name,
                tm.team_code,
                tm.age_group
            FROM tournament_teams tt
            JOIN teams tm ON tt.team_id = tm.id
            WHERE tt.tournament_id = :tour_id
            ORDER BY tt.seed_number ASC, tm.name ASC
        ");
        $ttStmt->execute([':tour_id' => $id]);
        $tournament['participating_teams'] = $ttStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Tournament Venues
        $tvStmt = $this->pdo->prepare("
            SELECT 
                tv.*,
                v.name as venue_name,
                v.city,
                v.venue_code
            FROM tournament_venues tv
            JOIN venues v ON tv.venue_id = v.id
            WHERE tv.tournament_id = :tour_id
            ORDER BY tv.is_primary DESC
        ");
        $tvStmt->execute([':tour_id' => $id]);
        $tournament['venues'] = $tvStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Fixtures and Matches
        $fixStmt = $this->pdo->prepare("
            SELECT 
                f.*,
                t1.name as home_team_name,
                t2.name as away_team_name,
                v.name as venue_name,
                vf.name as facility_name,
                m.id as match_id,
                m.match_reference,
                m.home_score,
                m.away_score,
                m.winner_team_id,
                m.result_type,
                m.status as match_status
            FROM fixtures f
            LEFT JOIN teams t1 ON f.home_team_id = t1.id
            LEFT JOIN teams t2 ON f.away_team_id = t2.id
            LEFT JOIN venues v ON f.venue_id = v.id
            LEFT JOIN venue_facilities vf ON f.facility_id = vf.id
            LEFT JOIN matches m ON f.id = m.fixture_id AND m.deleted_at IS NULL
            WHERE f.tournament_id = :tour_id AND f.organization_id = :org_id AND f.deleted_at IS NULL
            ORDER BY f.scheduled_date ASC, f.scheduled_start_time ASC
        ");
        $fixStmt->execute([':tour_id' => $id, ':org_id' => $organizationId]);
        $tournament['fixtures'] = $fixStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Standings
        $stdStmt = $this->pdo->prepare("
            SELECT 
                ts.*,
                tm.name as team_name,
                tm.team_code
            FROM tournament_standings ts
            JOIN teams tm ON ts.team_id = tm.id
            WHERE ts.tournament_id = :tour_id
            ORDER BY ts.points DESC, ts.difference DESC, ts.scored DESC
        ");
        $stdStmt->execute([':tour_id' => $id]);
        $tournament['standings'] = $stdStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return $tournament;
    }

    public function createTournament(int $organizationId, array $data, ?int $performedBy = null): array
    {
        if (!$this->pdo) return [];

        $this->pdo->beginTransaction();
        try {
            $ref = $data['tournament_reference'] ?? ('TOURN-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4)));

            $sql = "
                INSERT INTO tournaments (
                    organization_id, tournament_reference, name, sport_id,
                    tournament_level_id, tournament_format_id, start_date, end_date,
                    location_name, city, state, organizer_name, description, rules,
                    status, created_by, created_at, updated_at
                ) VALUES (
                    :org_id, :ref, :name, :sport_id,
                    :level_id, :format_id, :sdate, :edate,
                    :loc, :city, :state, :organizer, :desc, :rules,
                    :status, :created_by, NOW(), NOW()
                )
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':org_id' => $organizationId,
                ':ref' => $ref,
                ':name' => trim($data['name'] ?? ''),
                ':sport_id' => (int)($data['sport_id'] ?? 1),
                ':level_id' => !empty($data['tournament_level_id']) ? (int)$data['tournament_level_id'] : 1,
                ':format_id' => !empty($data['tournament_format_id']) ? (int)$data['tournament_format_id'] : 1,
                ':sdate' => $data['start_date'] ?? date('Y-m-d'),
                ':edate' => $data['end_date'] ?? date('Y-m-d', strtotime('+3 days')),
                ':loc' => $data['location_name'] ?? 'Main Stadium Complex',
                ':city' => $data['city'] ?? 'Mumbai',
                ':state' => $data['state'] ?? 'Maharashtra',
                ':organizer' => $data['organizer_name'] ?? 'Apex Sports Academy',
                ':desc' => $data['description'] ?? null,
                ':rules' => $data['rules'] ?? null,
                ':status' => $data['status'] ?? 'draft',
                ':created_by' => $performedBy,
            ]);

            $tournamentId = (int)$this->pdo->lastInsertId();

            // 1. Assign Primary Venue
            if (!empty($data['venue_id'])) {
                $vSql = "INSERT INTO tournament_venues (tournament_id, venue_id, is_primary, created_at) VALUES (:t_id, :v_id, 1, NOW())";
                $vStmt = $this->pdo->prepare($vSql);
                $vStmt->execute([':t_id' => $tournamentId, ':v_id' => (int)$data['venue_id']]);
            }

            // 2. Assign Participating Teams & Initialize Standings
            if (!empty($data['team_ids']) && is_array($data['team_ids'])) {
                $ttSql = "INSERT INTO tournament_teams (tournament_id, team_id, status, registered_at) VALUES (:t_id, :tm_id, 'approved', NOW())";
                $ttStmt = $this->pdo->prepare($ttSql);

                $stdSql = "INSERT INTO tournament_standings (tournament_id, team_id, played, won, drawn, lost, points, scored, conceded, difference, rank_position, updated_at) VALUES (:t_id, :tm_id, 0, 0, 0, 0, 0, 0, 0, 0, 1, NOW())";
                $stdStmt = $this->pdo->prepare($stdSql);

                foreach ($data['team_ids'] as $teamId) {
                    if (!empty($teamId)) {
                        $ttStmt->execute([':t_id' => $tournamentId, ':tm_id' => (int)$teamId]);
                        $stdStmt->execute([':t_id' => $tournamentId, ':tm_id' => (int)$teamId]);
                    }
                }
            }

            $this->pdo->commit();

            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'TOURNAMENT_CREATE',
                'Tournaments',
                'tournaments',
                $tournamentId,
                null,
                ['reference' => $ref, 'name' => $data['name'] ?? ''],
                "Created tournament {$data['name']} ({$ref})"
            );

            return [
                'id' => $tournamentId,
                'tournament_reference' => $ref,
                'name' => $data['name'] ?? ''
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateTournament(int $organizationId, int $id, array $data, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;

        $existing = $this->getTournament($organizationId, $id);
        if (!$existing) return false;

        $sql = "
            UPDATE tournaments SET
                name = :name,
                sport_id = :sport_id,
                tournament_level_id = :level_id,
                tournament_format_id = :format_id,
                start_date = :sdate,
                end_date = :edate,
                location_name = :loc,
                city = :city,
                state = :state,
                organizer_name = :organizer,
                description = :desc,
                rules = :rules,
                status = :status,
                updated_at = NOW()
            WHERE id = :id AND organization_id = :org_id
        ";

        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute([
            ':name' => trim($data['name'] ?? $existing['name']),
            ':sport_id' => (int)($data['sport_id'] ?? $existing['sport_id']),
            ':level_id' => !empty($data['tournament_level_id']) ? (int)$data['tournament_level_id'] : $existing['tournament_level_id'],
            ':format_id' => !empty($data['tournament_format_id']) ? (int)$data['tournament_format_id'] : $existing['tournament_format_id'],
            ':sdate' => $data['start_date'] ?? $existing['start_date'],
            ':edate' => $data['end_date'] ?? $existing['end_date'],
            ':loc' => $data['location_name'] ?? $existing['location_name'],
            ':city' => $data['city'] ?? $existing['city'],
            ':state' => $data['state'] ?? $existing['state'],
            ':organizer' => $data['organizer_name'] ?? $existing['organizer_name'],
            ':desc' => $data['description'] ?? $existing['description'],
            ':rules' => $data['rules'] ?? $existing['rules'],
            ':status' => $data['status'] ?? $existing['status'],
            ':id' => $id,
            ':org_id' => $organizationId,
        ]);

        if ($ok) {
            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'TOURNAMENT_UPDATE',
                'Tournaments',
                'tournaments',
                $id,
                $existing,
                $data,
                "Updated tournament #{$id} ({$existing['name']})"
            );
        }

        return $ok;
    }

    public function createFixture(int $organizationId, int $tournamentId, array $data, ?int $performedBy = null): array
    {
        if (!$this->pdo) return [];

        $this->pdo->beginTransaction();
        try {
            $fixRef = $data['fixture_reference'] ?? ('FIX-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4)));
            $matchRef = 'MCH-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4));

            $fSql = "
                INSERT INTO fixtures (
                    organization_id, tournament_id, fixture_reference, round_name,
                    group_name, home_team_id, away_team_id, venue_id, facility_id,
                    scheduled_date, scheduled_start_time, scheduled_end_time, status,
                    notes, created_at, updated_at
                ) VALUES (
                    :org_id, :tour_id, :fix_ref, :round,
                    :grp, :home_id, :away_id, :venue_id, :fac_id,
                    :sdate, :stime, :etime, 'scheduled',
                    :notes, NOW(), NOW()
                )
            ";
            $fStmt = $this->pdo->prepare($fSql);
            $fStmt->execute([
                ':org_id' => $organizationId,
                ':tour_id' => $tournamentId,
                ':fix_ref' => $fixRef,
                ':round' => $data['round_name'] ?? 'Round 1',
                ':grp' => $data['group_name'] ?? null,
                ':home_id' => (int)$data['home_team_id'],
                ':away_id' => (int)$data['away_team_id'],
                ':venue_id' => !empty($data['venue_id']) ? (int)$data['venue_id'] : null,
                ':fac_id' => !empty($data['facility_id']) ? (int)$data['facility_id'] : null,
                ':sdate' => $data['scheduled_date'] ?? date('Y-m-d'),
                ':stime' => $data['scheduled_start_time'] ?? '15:00:00',
                ':etime' => $data['scheduled_end_time'] ?? '17:00:00',
                ':notes' => $data['notes'] ?? null,
            ]);

            $fixtureId = (int)$this->pdo->lastInsertId();

            // Create corresponding match record
            $mSql = "
                INSERT INTO matches (
                    organization_id, fixture_id, match_reference, status, created_at, updated_at
                ) VALUES (
                    :org_id, :fix_id, :m_ref, 'scheduled', NOW(), NOW()
                )
            ";
            $mStmt = $this->pdo->prepare($mSql);
            $mStmt->execute([
                ':org_id' => $organizationId,
                ':fix_id' => $fixtureId,
                ':m_ref' => $matchRef
            ]);

            $this->pdo->commit();

            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'FIXTURE_CREATE',
                'Fixtures',
                'fixtures',
                $fixtureId,
                null,
                ['fixture_reference' => $fixRef, 'tournament_id' => $tournamentId],
                "Created fixture {$fixRef} in tournament #{$tournamentId}"
            );

            return [
                'id' => $fixtureId,
                'fixture_reference' => $fixRef
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateMatchResult(int $organizationId, int $fixtureId, array $data, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;

        $fStmt = $this->pdo->prepare("SELECT * FROM fixtures WHERE id = :fix_id AND organization_id = :org_id LIMIT 1");
        $fStmt->execute([':fix_id' => $fixtureId, ':org_id' => $organizationId]);
        $fixture = $fStmt->fetch(PDO::FETCH_ASSOC);
        if (!$fixture) return false;

        $this->pdo->beginTransaction();
        try {
            $homeScore = (float)($data['home_score'] ?? 0);
            $awayScore = (float)($data['away_score'] ?? 0);

            $winnerTeamId = null;
            if ($homeScore > $awayScore) {
                $winnerTeamId = (int)$fixture['home_team_id'];
                $resultType = 'home_win';
            } elseif ($awayScore > $homeScore) {
                $winnerTeamId = (int)$fixture['away_team_id'];
                $resultType = 'away_win';
            } else {
                $resultType = 'draw';
            }

            // Update match record
            $mSql = "
                UPDATE matches SET
                    home_score = :h_score,
                    away_score = :a_score,
                    winner_team_id = :winner,
                    result_type = :res_type,
                    status = 'completed',
                    updated_at = NOW()
                WHERE fixture_id = :fix_id AND organization_id = :org_id
            ";
            $mStmt = $this->pdo->prepare($mSql);
            $mStmt->execute([
                ':h_score' => $homeScore,
                ':a_score' => $awayScore,
                ':winner' => $winnerTeamId,
                ':res_type' => $resultType,
                ':fix_id' => $fixtureId,
                ':org_id' => $organizationId
            ]);

            // Mark fixture completed
            $upFix = $this->pdo->prepare("UPDATE fixtures SET status = 'completed', updated_at = NOW() WHERE id = :fix_id");
            $upFix->execute([':fix_id' => $fixtureId]);

            // Update standings if tournament has standings
            $tournamentId = (int)$fixture['tournament_id'];
            $this->recalculateStandings($tournamentId);

            $this->pdo->commit();

            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'MATCH_RESULT',
                'Matches',
                'matches',
                $fixtureId,
                null,
                ['fixture_id' => $fixtureId, 'home_score' => $homeScore, 'away_score' => $awayScore],
                "Recorded match result for fixture #{$fixtureId}: {$homeScore} - {$awayScore}"
            );

            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    protected function recalculateStandings(int $tournamentId): void
    {
        // Simple standings update from completed matches
        $q = "
            SELECT 
                f.home_team_id, f.away_team_id,
                m.home_score, m.away_score, m.winner_team_id, m.result_type
            FROM fixtures f
            JOIN matches m ON f.id = m.fixture_id
            WHERE f.tournament_id = :t_id AND m.status = 'completed' AND f.deleted_at IS NULL
        ";
        $stmt = $this->pdo->prepare($q);
        $stmt->execute([':t_id' => $tournamentId]);
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $table = [];
        foreach ($matches as $m) {
            $h = (int)$m['home_team_id'];
            $a = (int)$m['away_team_id'];

            if (!isset($table[$h])) $table[$h] = ['p' => 0, 'w' => 0, 'd' => 0, 'l' => 0, 'gf' => 0, 'ga' => 0, 'pts' => 0];
            if (!isset($table[$a])) $table[$a] = ['p' => 0, 'w' => 0, 'd' => 0, 'l' => 0, 'gf' => 0, 'ga' => 0, 'pts' => 0];

            $table[$h]['p']++;
            $table[$a]['p']++;
            $table[$h]['gf'] += (float)$m['home_score'];
            $table[$h]['ga'] += (float)$m['away_score'];
            $table[$a]['gf'] += (float)$m['away_score'];
            $table[$a]['ga'] += (float)$m['home_score'];

            if ($m['result_type'] === 'home_win') {
                $table[$h]['w']++;
                $table[$h]['pts'] += 3;
                $table[$a]['l']++;
            } elseif ($m['result_type'] === 'away_win') {
                $table[$a]['w']++;
                $table[$a]['pts'] += 3;
                $table[$h]['l']++;
            } else {
                $table[$h]['d']++;
                $table[$h]['pts'] += 1;
                $table[$a]['d']++;
                $table[$a]['pts'] += 1;
            }
        }

        $upStmt = $this->pdo->prepare("
            INSERT INTO tournament_standings (tournament_id, team_id, played, won, drawn, lost, points, scored, conceded, difference, updated_at)
            VALUES (:t_id, :tm_id, :p, :w, :d, :l, :pts, :gf, :ga, :diff, NOW())
            ON DUPLICATE KEY UPDATE
                played = VALUES(played),
                won = VALUES(won),
                drawn = VALUES(drawn),
                lost = VALUES(lost),
                points = VALUES(points),
                scored = VALUES(scored),
                conceded = VALUES(conceded),
                difference = VALUES(difference),
                updated_at = NOW()
        ");

        foreach ($table as $teamId => $stats) {
            $diff = $stats['gf'] - $stats['ga'];
            $upStmt->execute([
                ':t_id' => $tournamentId,
                ':tm_id' => $teamId,
                ':p' => $stats['p'],
                ':w' => $stats['w'],
                ':d' => $stats['d'],
                ':l' => $stats['l'],
                ':pts' => $stats['pts'],
                ':gf' => $stats['gf'],
                ':ga' => $stats['ga'],
                ':diff' => $diff
            ]);
        }
    }

    public function deleteTournament(int $organizationId, int $id, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;

        $existing = $this->getTournament($organizationId, $id);
        if (!$existing) return false;

        $stmt = $this->pdo->prepare("UPDATE tournaments SET deleted_at = NOW() WHERE id = :id AND organization_id = :org_id");
        $stmt->execute([':id' => $id, ':org_id' => $organizationId]);

        $this->auditLog->log(
            $organizationId,
            $performedBy,
            'TOURNAMENT_DELETE',
            'Tournaments',
            'tournaments',
            $id,
            $existing,
            null,
            "Soft deleted tournament #{$id}"
        );

        return true;
    }

    public function addTeam(int $organizationId, int $tournamentId, int $teamId, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;
        $t = $this->getTournament($organizationId, $tournamentId);
        if (!$t) return false;

        $ttStmt = $this->pdo->prepare("
            INSERT INTO tournament_teams (tournament_id, team_id, status, registered_at) 
            VALUES (:t_id, :tm_id, 'approved', NOW())
            ON DUPLICATE KEY UPDATE status = 'approved'
        ");
        $ttStmt->execute([':t_id' => $tournamentId, ':tm_id' => $teamId]);

        $stdStmt = $this->pdo->prepare("
            INSERT INTO tournament_standings (tournament_id, team_id, played, won, drawn, lost, points, scored, conceded, difference, rank_position, updated_at)
            VALUES (:t_id, :tm_id, 0, 0, 0, 0, 0, 0, 0, 0, 1, NOW())
            ON DUPLICATE KEY UPDATE updated_at = NOW()
        ");
        $stdStmt->execute([':t_id' => $tournamentId, ':tm_id' => $teamId]);

        return true;
    }
}
