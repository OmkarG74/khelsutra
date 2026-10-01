<?php

namespace Tests\Feature;

use App\Services\BaseService;
use App\Services\Rbac\PermissionService;
use App\Services\Team\TeamService;
use App\Services\Tournament\TournamentService;
use App\Services\Training\TrainingService;
use PDO;

class TeamsTournamentsTrainingAlignmentTest
{
    private int $orgId = 1;
    private int $otherOrgId = 2;
    private int $sportsAdminUserId = 102;
    private PDO $pdo;
    private TeamService $teamService;
    private TournamentService $tournamentService;
    private TrainingService $trainingService;
    private PermissionService $permissionService;

    private array $createdTeamIds = [];
    private array $createdTournamentIds = [];
    private array $createdFixtureIds = [];
    private array $createdMatchIds = [];
    private array $createdTrainingSessionIds = [];
    private array $createdAthleteIds = [];
    private array $createdCoachProfileIds = [];
    private array $createdEmployeeIds = [];
    private array $createdVenueIds = [];

    public function __construct()
    {
        $this->pdo = BaseService::getDatabaseConnection();
        $this->teamService = new TeamService($this->pdo);
        $this->tournamentService = new TournamentService($this->pdo);
        $this->trainingService = new TrainingService($this->pdo);
        $this->permissionService = new PermissionService($this->pdo);
        $this->ensureOrgExists($this->orgId);
        $this->ensureOrgExists($this->otherOrgId);
    }

    public function __destruct()
    {
        $this->cleanup();
    }

    private function cleanup(): void
    {
        if (!empty($this->createdTrainingSessionIds)) {
            $in = implode(',', array_map('intval', $this->createdTrainingSessionIds));
            $this->pdo->exec("DELETE FROM training_attendance WHERE training_session_id IN ({$in})");
            $this->pdo->exec("DELETE FROM audit_logs WHERE table_name IN ('training_sessions', 'training_attendance') AND record_id IN ({$in})");
            $this->pdo->exec("DELETE FROM training_sessions WHERE id IN ({$in})");
        }
        if (!empty($this->createdMatchIds)) {
            $in = implode(',', array_map('intval', $this->createdMatchIds));
            $this->pdo->exec("DELETE FROM matches WHERE id IN ({$in})");
        }
        if (!empty($this->createdFixtureIds)) {
            $in = implode(',', array_map('intval', $this->createdFixtureIds));
            $this->pdo->exec("DELETE FROM matches WHERE fixture_id IN ({$in})");
            $this->pdo->exec("DELETE FROM fixtures WHERE id IN ({$in})");
        }
        if (!empty($this->createdTournamentIds)) {
            $in = implode(',', array_map('intval', $this->createdTournamentIds));
            $this->pdo->exec("DELETE FROM matches WHERE fixture_id IN (SELECT id FROM fixtures WHERE tournament_id IN ({$in}))");
            $this->pdo->exec("DELETE FROM fixtures WHERE tournament_id IN ({$in})");
            $this->pdo->exec("DELETE FROM tournament_standings WHERE tournament_id IN ({$in})");
            $this->pdo->exec("DELETE FROM tournament_teams WHERE tournament_id IN ({$in})");
            $this->pdo->exec("DELETE FROM tournament_venues WHERE tournament_id IN ({$in})");
            $this->pdo->exec("DELETE FROM audit_logs WHERE table_name = 'tournaments' AND record_id IN ({$in})");
            $this->pdo->exec("DELETE FROM tournaments WHERE id IN ({$in})");
        }
        if (!empty($this->createdTeamIds)) {
            $in = implode(',', array_map('intval', $this->createdTeamIds));
            $this->pdo->exec("DELETE FROM team_members WHERE team_id IN ({$in})");
            $this->pdo->exec("DELETE FROM team_coaches WHERE team_id IN ({$in})");
            $this->pdo->exec("DELETE FROM audit_logs WHERE table_name = 'teams' AND record_id IN ({$in})");
            $this->pdo->exec("DELETE FROM teams WHERE id IN ({$in})");
        }
        if (!empty($this->createdAthleteIds)) {
            $in = implode(',', array_map('intval', $this->createdAthleteIds));
            $this->pdo->exec("DELETE FROM team_members WHERE athlete_id IN ({$in})");
            $this->pdo->exec("DELETE FROM training_attendance WHERE athlete_id IN ({$in})");
            $this->pdo->exec("DELETE FROM athletes WHERE id IN ({$in})");
        }
        if (!empty($this->createdCoachProfileIds)) {
            $in = implode(',', array_map('intval', $this->createdCoachProfileIds));
            $this->pdo->exec("DELETE FROM team_coaches WHERE coach_id IN ({$in})");
            $this->pdo->exec("DELETE FROM coach_profiles WHERE id IN ({$in})");
        }
        if (!empty($this->createdEmployeeIds)) {
            $in = implode(',', array_map('intval', $this->createdEmployeeIds));
            $this->pdo->exec("DELETE FROM employees WHERE id IN ({$in})");
        }
        if (!empty($this->createdVenueIds)) {
            $in = implode(',', array_map('intval', $this->createdVenueIds));
            $this->pdo->exec("DELETE FROM tournament_venues WHERE venue_id IN ({$in})");
            $this->pdo->exec("DELETE FROM venues WHERE id IN ({$in})");
        }
    }

    private function ensureOrgExists(int $orgId): void
    {
        $stmt = $this->pdo->prepare("SELECT id FROM organizations WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $orgId]);
        if (!$stmt->fetchColumn()) {
            $ins = $this->pdo->prepare("
                INSERT INTO organizations (id, code, name, status, created_at, updated_at)
                VALUES (:id, :code, :name, 'active', NOW(), NOW())
            ");
            $ins->execute([
                ':id'   => $orgId,
                ':code' => 'ORG-TEST-' . $orgId,
                ':name' => 'Test Organization ' . $orgId,
            ]);
        }
    }

    private function createTestCoach(int $orgId, string $firstName, string $lastName): int
    {
        $empCode = 'EMP-TTT-' . strtoupper(bin2hex(random_bytes(4)));
        $coachCode = 'COA-TTT-' . strtoupper(bin2hex(random_bytes(4)));
        $stmt = $this->pdo->prepare("
            INSERT INTO employees (organization_id, employee_code, first_name, last_name, employment_status, created_at, updated_at)
            VALUES (:org_id, :code, :fn, :ln, 'active', NOW(), NOW())
        ");
        $stmt->execute([
            ':org_id' => $orgId,
            ':code'   => $empCode,
            ':fn'     => $firstName,
            ':ln'     => $lastName,
        ]);
        $empId = (int)$this->pdo->lastInsertId();
        $this->createdEmployeeIds[] = $empId;

        $cpStmt = $this->pdo->prepare("
            INSERT INTO coach_profiles (organization_id, employee_id, coach_code, specialization, status, created_at, updated_at)
            VALUES (:org_id, :emp_id, :code, 'Tactical Coaching', 'active', NOW(), NOW())
        ");
        $cpStmt->execute([
            ':org_id' => $orgId,
            ':emp_id' => $empId,
            ':code'   => $coachCode,
        ]);
        $cpId = (int)$this->pdo->lastInsertId();
        $this->createdCoachProfileIds[] = $cpId;
        return $cpId;
    }

    private function createTestAthlete(int $orgId, string $firstName, string $lastName, int $sportId = 1): int
    {
        $code = 'ATH-TTT-' . strtoupper(bin2hex(random_bytes(4)));
        $stmt = $this->pdo->prepare("
            INSERT INTO athletes (organization_id, athlete_code, first_name, last_name, gender, current_sport_id, status, created_at, updated_at)
            VALUES (:org_id, :code, :fn, :ln, 'male', :sport_id, 'active', NOW(), NOW())
        ");
        $stmt->execute([
            ':org_id'   => $orgId,
            ':code'     => $code,
            ':fn'       => $firstName,
            ':ln'       => $lastName,
            ':sport_id' => $sportId,
        ]);
        $athId = (int)$this->pdo->lastInsertId();
        $this->createdAthleteIds[] = $athId;
        return $athId;
    }

    private function createTestVenue(int $orgId, string $name): int
    {
        $code = 'VEN-TTT-' . strtoupper(bin2hex(random_bytes(4)));
        $stmt = $this->pdo->prepare("
            INSERT INTO venues (organization_id, venue_code, name, city, state, country, status, created_at, updated_at)
            VALUES (:org_id, :code, :name, 'Pune', 'Maharashtra', 'India', 'active', NOW(), NOW())
        ");
        $stmt->execute([
            ':org_id' => $orgId,
            ':code'   => $code,
            ':name'   => $name,
        ]);
        $vId = (int)$this->pdo->lastInsertId();
        $this->createdVenueIds[] = $vId;
        return $vId;
    }

    // =========================================================================
    // 1. TEAMS TESTS
    // =========================================================================
    public function testTeamsListAndOneRowDeduplication(): bool
    {
        $coach1 = $this->createTestCoach($this->orgId, 'Rahul', 'Dravid');
        $coach2 = $this->createTestCoach($this->orgId, 'Vikram', 'Rathour');
        $ath1 = $this->createTestAthlete($this->orgId, 'Shubman', 'Gill', 1);
        $ath2 = $this->createTestAthlete($this->orgId, 'Yashasvi', 'Jaiswal', 1);

        $uniqueTag = 'DedupSquad_' . bin2hex(random_bytes(3));
        $created = $this->teamService->createTeam($this->orgId, [
            'name' => $uniqueTag,
            'sport_id' => 1,
            'gender' => 'male',
            'age_group' => 'U-19',
            'coach_id' => $coach1,
            'athlete_ids' => [$ath1, $ath2],
        ], $this->sportsAdminUserId);

        $teamId = (int)($created['id'] ?? 0);
        if ($teamId <= 0) return false;
        $this->createdTeamIds[] = $teamId;

        // Assign a second coach to verify multi-coach + multi-athlete does NOT duplicate team rows
        $this->teamService->assignCoach($this->orgId, $teamId, $coach2, 'assistant_coach', false, $this->sportsAdminUserId);

        $list = $this->teamService->listTeams($this->orgId, 1, 20, $uniqueTag);
        if (($list['total'] ?? 0) !== 1) return false;
        if (count($list['data'] ?? []) !== 1) return false;

        $row = $list['data'][0];
        if ((int)$row['id'] !== $teamId) return false;
        if ((int)$row['athlete_count'] !== 2) return false;
        if ((int)$row['extra_coaches_count'] !== 1) return false;
        if (empty($row['head_coach_name'])) return false;

        return true;
    }

    public function testTeamsSearchFiltersAndPagination(): bool
    {
        $prefix = 'PagTeam_' . bin2hex(random_bytes(3));
        for ($i = 1; $i <= 22; $i++) {
            $c = $this->teamService->createTeam($this->orgId, [
                'name' => "{$prefix}_{$i}",
                'sport_id' => 1,
                'gender' => 'male',
                'age_group' => 'Senior',
                'status' => ($i <= 20) ? 'active' : 'inactive',
            ], $this->sportsAdminUserId);
            if (!empty($c['id'])) {
                $this->createdTeamIds[] = (int)$c['id'];
            }
        }

        // Page 1 (20 per page)
        $p1 = $this->teamService->listTeams($this->orgId, 1, 20, $prefix);
        if ($p1['total'] !== 22 || count($p1['data']) !== 20 || $p1['from'] !== 1 || $p1['to'] !== 20 || $p1['total_pages'] !== 2) {
            return false;
        }

        // Page 2
        $p2 = $this->teamService->listTeams($this->orgId, 2, 20, $prefix);
        if ($p2['total'] !== 22 || count($p2['data']) !== 2 || $p2['from'] !== 21 || $p2['to'] !== 22) {
            return false;
        }

        // Combined search + sport + status filter
        $inactiveOnly = $this->teamService->listTeams($this->orgId, 1, 20, $prefix, 1, 'inactive');
        if ($inactiveOnly['total'] !== 2 || count($inactiveOnly['data']) !== 2) {
            return false;
        }

        return true;
    }

    public function testTeamsViewEditRosterCoachesAndSoftDelete(): bool
    {
        $coachId = $this->createTestCoach($this->orgId, 'Gautam', 'Gambhir');
        $athId = $this->createTestAthlete($this->orgId, 'Rishabh', 'Pant', 1);

        $created = $this->teamService->createTeam($this->orgId, [
            'name' => 'Lifecycle Squad ' . bin2hex(random_bytes(3)),
            'sport_id' => 1,
            'gender' => 'male',
            'age_group' => 'U-23',
        ], $this->sportsAdminUserId);
        $teamId = (int)($created['id'] ?? 0);
        if ($teamId <= 0) return false;
        $this->createdTeamIds[] = $teamId;

        // Edit Team
        $ok = $this->teamService->updateTeam($this->orgId, $teamId, [
            'name' => 'Lifecycle Squad Updated',
            'sport_id' => 1,
            'gender' => 'male',
            'age_group' => 'Senior',
            'formation_or_level' => 'Elite Division',
            'status' => 'active',
        ], $this->sportsAdminUserId);
        if (!$ok) return false;

        // Assign Coach & Add Roster Member
        if (!$this->teamService->assignCoach($this->orgId, $teamId, $coachId, 'head_coach', true, $this->sportsAdminUserId)) return false;
        if (!$this->teamService->assignAthlete($this->orgId, $teamId, $athId, 'captain', 17, $this->sportsAdminUserId)) return false;

        // Re-assigning the same active coach or member is idempotent and does not create duplicates
        $this->teamService->assignCoach($this->orgId, $teamId, $coachId, 'assistant_coach', false, $this->sportsAdminUserId);
        $this->teamService->assignAthlete($this->orgId, $teamId, $athId, 'player', 17, $this->sportsAdminUserId);

        $detail = $this->teamService->getTeam($this->orgId, $teamId);
        if (!$detail || $detail['name'] !== 'Lifecycle Squad Updated') return false;
        if (count($detail['coaches'] ?? []) !== 1 || count($detail['current_athletes'] ?? []) !== 1) return false;

        // Soft Delete
        if (!$this->teamService->deleteTeam($this->orgId, $teamId, $this->sportsAdminUserId)) return false;
        if ($this->teamService->getTeam($this->orgId, $teamId) !== null) return false;

        return true;
    }

    // =========================================================================
    // 2. TOURNAMENTS TESTS
    // =========================================================================
    public function testTournamentsListDeduplicationSearchFilterAndPagination(): bool
    {
        $v1 = $this->createTestVenue($this->orgId, 'Main Stadium A');
        $v2 = $this->createTestVenue($this->orgId, 'Annex Ground B');

        $t1 = $this->teamService->createTeam($this->orgId, ['name' => 'TournTeamA_' . bin2hex(random_bytes(2)), 'sport_id' => 1, 'gender' => 'male'], $this->sportsAdminUserId);
        $t2 = $this->teamService->createTeam($this->orgId, ['name' => 'TournTeamB_' . bin2hex(random_bytes(2)), 'sport_id' => 1, 'gender' => 'male'], $this->sportsAdminUserId);
        $this->createdTeamIds[] = (int)$t1['id'];
        $this->createdTeamIds[] = (int)$t2['id'];

        $uniqueName = 'Championship_' . bin2hex(random_bytes(3));
        $created = $this->tournamentService->createTournament($this->orgId, [
            'name' => $uniqueName,
            'sport_id' => 1,
            'tournament_level_id' => 1,
            'tournament_format_id' => 1,
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-20',
            'status' => 'ongoing',
            'venue_id' => $v1,
            'team_ids' => [(int)$t1['id'], (int)$t2['id']],
        ], $this->sportsAdminUserId);

        $tournId = (int)($created['id'] ?? 0);
        if ($tournId <= 0) return false;
        $this->createdTournamentIds[] = $tournId;

        // Assign second venue to verify multi-venue + multi-team does NOT duplicate tournament rows
        $this->tournamentService->addVenue($this->orgId, $tournId, $v2, false, $this->sportsAdminUserId);

        $list = $this->tournamentService->listTournaments($this->orgId, 1, 20, $uniqueName, 'ongoing', 1);
        if (($list['total'] ?? 0) !== 1 || count($list['data'] ?? []) !== 1) return false;

        $row = $list['data'][0];
        if ((int)$row['id'] !== $tournId) return false;
        if ((int)$row['enrolled_teams_count'] !== 2) return false;
        if ((int)$row['extra_venues_count'] !== 1) return false;
        if ($row['primary_venue_name'] !== 'Main Stadium A') return false;

        return true;
    }

    public function testTournamentsViewEditTeamsFixturesAndResults(): bool
    {
        $v1 = $this->createTestVenue($this->orgId, 'Arena Central');
        $t1 = $this->teamService->createTeam($this->orgId, ['name' => 'Alpha FC ' . bin2hex(random_bytes(2)), 'sport_id' => 1, 'gender' => 'male'], $this->sportsAdminUserId);
        $t2 = $this->teamService->createTeam($this->orgId, ['name' => 'Beta FC ' . bin2hex(random_bytes(2)), 'sport_id' => 1, 'gender' => 'male'], $this->sportsAdminUserId);
        $this->createdTeamIds[] = (int)$t1['id'];
        $this->createdTeamIds[] = (int)$t2['id'];

        $created = $this->tournamentService->createTournament($this->orgId, [
            'name' => 'Cup_' . bin2hex(random_bytes(3)),
            'sport_id' => 1,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-10',
            'status' => 'registration_open',
            'venue_id' => $v1,
            'team_ids' => [(int)$t1['id'], (int)$t2['id']],
        ], $this->sportsAdminUserId);
        $tournId = (int)($created['id'] ?? 0);
        if ($tournId <= 0) return false;
        $this->createdTournamentIds[] = $tournId;

        // Edit Tournament
        $updated = $this->tournamentService->updateTournament($this->orgId, $tournId, [
            'name' => 'Cup Updated',
            'status' => 'ongoing',
        ], $this->sportsAdminUserId);
        if (!$updated) return false;

        // Create Fixture between the two teams
        $fix = $this->tournamentService->createFixture($this->orgId, $tournId, [
            'round_name' => 'Final',
            'match_number' => 1,
            'home_team_id' => (int)$t1['id'],
            'away_team_id' => (int)$t2['id'],
            'venue_id' => $v1,
            'scheduled_date' => '2026-11-05',
            'scheduled_start_time' => '16:00',
            'status' => 'scheduled',
        ], $this->sportsAdminUserId);
        $fixtureId = (int)($fix['id'] ?? 0);
        if ($fixtureId <= 0) return false;
        $this->createdFixtureIds[] = $fixtureId;

        // Record Match Result
        $this->tournamentService->updateMatchResult($this->orgId, $fixtureId, [
            'home_score' => '3',
            'away_score' => '1',
        ], $this->sportsAdminUserId);

        $detail = $this->tournamentService->getTournament($this->orgId, $tournId);
        if (!$detail || $detail['name'] !== 'Cup Updated') return false;
        if (count($detail['participating_teams'] ?? []) !== 2) return false;
        if (count($detail['fixtures'] ?? []) !== 1) return false;
        if ((int)($detail['fixtures'][0]['home_score'] ?? 0) !== 3 || (int)($detail['fixtures'][0]['away_score'] ?? 0) !== 1) return false;

        // Soft Delete Tournament
        if (!$this->tournamentService->deleteTournament($this->orgId, $tournId, $this->sportsAdminUserId)) return false;
        if ($this->tournamentService->getTournament($this->orgId, $tournId) !== null) return false;

        return true;
    }

    // =========================================================================
    // 3. TRAINING TESTS
    // =========================================================================
    public function testTrainingListDeduplicationFiltersAttendanceAndLifecycle(): bool
    {
        $coachId = $this->createTestCoach($this->orgId, 'Igor', 'Stimac');
        $venueId = $this->createTestVenue($this->orgId, 'Training Pitch 1');
        $ath1 = $this->createTestAthlete($this->orgId, 'Sunil', 'Chhetri', 1);
        $ath2 = $this->createTestAthlete($this->orgId, 'Gurpreet', 'Sandhu', 1);

        $team = $this->teamService->createTeam($this->orgId, [
            'name' => 'TrainSquad_' . bin2hex(random_bytes(3)),
            'sport_id' => 1,
            'gender' => 'male',
            'coach_id' => $coachId,
            'athlete_ids' => [$ath1, $ath2],
        ], $this->sportsAdminUserId);
        $teamId = (int)$team['id'];
        $this->createdTeamIds[] = $teamId;

        $sessTitle = 'HighPressDrill_' . bin2hex(random_bytes(3));
        $created = $this->trainingService->createSession($this->orgId, [
            'title' => $sessTitle,
            'training_type' => 'Tactical Drills',
            'team_id' => $teamId,
            'coach_id' => $coachId,
            'venue_id' => $venueId,
            'training_date' => '2026-10-15',
            'start_time' => '07:30:00',
            'end_time' => '09:30:00',
            'status' => 'scheduled',
            'objectives' => 'Improve transition speed',
            'notes' => 'Bring bibs',
        ], $this->sportsAdminUserId);

        $sessionId = (int)($created['id'] ?? 0);
        if ($sessionId <= 0) return false;
        $this->createdTrainingSessionIds[] = $sessionId;

        // Record Attendance (1 present, 1 late with remarks)
        $attOk = $this->trainingService->recordAttendance($this->orgId, $sessionId, [
            $ath1 => ['status' => 'present', 'remarks' => 'Sharp session'],
            $ath2 => ['status' => 'late', 'remarks' => 'Arrived 10m late'],
        ], $this->sportsAdminUserId);
        if (!$attOk) return false;

        // List with filters: verify 1 row (no duplicates from multiple athletes/attendance rows)
        $list = $this->trainingService->listSessions($this->orgId, 1, 20, $sessTitle, '2026-10-15', 'scheduled', $teamId, $coachId);
        if (($list['total'] ?? 0) !== 1 || count($list['data'] ?? []) !== 1) return false;

        $row = $list['data'][0];
        if ((int)$row['id'] !== $sessionId) return false;
        if ((int)$row['squad_size'] !== 2) return false;
        if ((int)$row['marked_count'] !== 2) return false;
        if ((int)$row['present_count'] !== 1) return false;
        if ((int)$row['late_count'] !== 1) return false;

        // View Session Detail & Attendance Summary
        $detail = $this->trainingService->getSession($this->orgId, $sessionId);
        if (!$detail || $detail['title'] !== $sessTitle) return false;
        if (($detail['attendance_summary']['present'] ?? 0) !== 1) return false;
        if (($detail['attendance_summary']['late'] ?? 0) !== 1) return false;

        // Edit Session
        $editOk = $this->trainingService->updateSession($this->orgId, $sessionId, [
            'title' => $sessTitle . '_Updated',
            'status' => 'completed',
        ], $this->sportsAdminUserId);
        if (!$editOk) return false;

        $afterEdit = $this->trainingService->getSession($this->orgId, $sessionId);
        if (($afterEdit['status'] ?? '') !== 'completed') return false;

        // Soft Delete Session
        if (!$this->trainingService->deleteSession($this->orgId, $sessionId, $this->sportsAdminUserId)) return false;
        if ($this->trainingService->getSession($this->orgId, $sessionId) !== null) return false;

        return true;
    }

    // =========================================================================
    // 4. SECURITY, RBAC & TENANT ISOLATION TESTS
    // =========================================================================
    public function testTenantIsolationAcrossTeamsTournamentsAndTraining(): bool
    {
        $otherVenue = $this->createTestVenue($this->otherOrgId, 'Other Org Venue');
        $otherCoach = $this->createTestCoach($this->otherOrgId, 'Other', 'Coach');
        $otherAthlete = $this->createTestAthlete($this->otherOrgId, 'Other', 'Athlete', 1);

        $otherTeam = $this->teamService->createTeam($this->otherOrgId, [
            'name' => 'Org2 Secret Team',
            'sport_id' => 1,
            'gender' => 'male',
        ], $this->sportsAdminUserId);
        $otherTeamId = (int)$otherTeam['id'];
        $this->createdTeamIds[] = $otherTeamId;

        $otherTourn = $this->tournamentService->createTournament($this->otherOrgId, [
            'name' => 'Org2 Secret Tournament',
            'sport_id' => 1,
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-05',
        ], $this->sportsAdminUserId);
        $otherTournId = (int)$otherTourn['id'];
        $this->createdTournamentIds[] = $otherTournId;

        $otherSession = $this->trainingService->createSession($this->otherOrgId, [
            'title' => 'Org2 Secret Session',
            'team_id' => $otherTeamId,
            'venue_id' => $otherVenue,
            'training_date' => '2026-12-02',
        ], $this->sportsAdminUserId);
        $otherSessionId = (int)$otherSession['id'];
        $this->createdTrainingSessionIds[] = $otherSessionId;

        // Org 1 must NOT be able to view, update, or delete Org 2 entities
        if ($this->teamService->getTeam($this->orgId, $otherTeamId) !== null) return false;
        if ($this->teamService->updateTeam($this->orgId, $otherTeamId, ['name' => 'Hacked'], $this->sportsAdminUserId) !== false) return false;
        if ($this->teamService->deleteTeam($this->orgId, $otherTeamId, $this->sportsAdminUserId) !== false) return false;

        if ($this->tournamentService->getTournament($this->orgId, $otherTournId) !== null) return false;
        if ($this->tournamentService->updateTournament($this->orgId, $otherTournId, ['name' => 'Hacked'], $this->sportsAdminUserId) !== false) return false;
        if ($this->tournamentService->deleteTournament($this->orgId, $otherTournId, $this->sportsAdminUserId) !== false) return false;

        if ($this->trainingService->getSession($this->orgId, $otherSessionId) !== null) return false;
        if ($this->trainingService->updateSession($this->orgId, $otherSessionId, ['title' => 'Hacked'], $this->sportsAdminUserId) !== false) return false;
        if ($this->trainingService->deleteSession($this->orgId, $otherSessionId, $this->sportsAdminUserId) !== false) return false;

        // Cross-org foreign key injection into Org 1 must be rejected
        $crossOrgRejected = false;
        try {
            $this->trainingService->createSession($this->orgId, [
                'title' => 'Cross Org Injection Attempt',
                'team_id' => $otherTeamId,
                'coach_id' => $otherCoach,
                'venue_id' => $otherVenue,
                'training_date' => '2026-12-03',
            ], $this->sportsAdminUserId);
        } catch (\InvalidArgumentException $e) {
            $crossOrgRejected = true;
        }
        if (!$crossOrgRejected) return false;

        return true;
    }

    public function testRbacPermissionsForTeamsTournamentsAndTraining(): bool
    {
        $adminPerms = $this->permissionService->getUserPermissions($this->sportsAdminUserId, $this->orgId);

        $requiredAdminPerms = [
            'team.view', 'team.create', 'team.update', 'team.manage',
            'tournament.view', 'tournament.create', 'tournament.update', 'tournament.manage',
            'training.view', 'training.create', 'training.update', 'training.manage',
        ];
        foreach ($requiredAdminPerms as $perm) {
            if (!in_array($perm, $adminPerms, true)) {
                return false;
            }
        }

        // Athlete role payload should not have manage/delete permissions
        $athletePayload = [
            'id' => 999999,
            'role_id' => 5,
            'role' => ['id' => 5, 'slug' => 'athlete', 'name' => 'Athlete'],
            'permissions' => ['team.view', 'tournament.view', 'training.view'],
        ];
        if ($this->permissionService->hasPermission($athletePayload, 'team.manage', $this->orgId)) return false;
        if ($this->permissionService->hasPermission($athletePayload, 'tournament.manage', $this->orgId)) return false;
        if ($this->permissionService->hasPermission($athletePayload, 'training.manage', $this->orgId)) return false;

        return true;
    }

    public function test9BladeViewsRenderAndDirectUrlAuthorization(): bool
    {
        $viewsDir = dirname(__DIR__, 2) . '/resources/views';
        $filesToSyntaxCheck = [
            $viewsDir . '/sports/teams.blade.php',
            $viewsDir . '/sports/teams-show.blade.php',
            $viewsDir . '/sports/teams-create.blade.php',
            $viewsDir . '/sports/teams-edit.blade.php',
            $viewsDir . '/competitions/tournaments.blade.php',
            $viewsDir . '/competitions/tournaments-show.blade.php',
            $viewsDir . '/competitions/tournaments-create.blade.php',
            $viewsDir . '/competitions/tournaments-edit.blade.php',
            $viewsDir . '/sports/training.blade.php',
            $viewsDir . '/sports/training-show.blade.php',
            $viewsDir . '/sports/training-create.blade.php',
            $viewsDir . '/sports/training-edit.blade.php',
        ];

        foreach ($filesToSyntaxCheck as $file) {
            if (!file_exists($file)) return false;
            $output = [];
            $exitCode = 0;
            exec('php -l ' . escapeshellarg($file) . ' 2>&1', $output, $exitCode);
            if ($exitCode !== 0) {
                return false;
            }
        }

        // Verify direct URL guard on create views for unauthorized Athlete role
        $_SESSION['auth'] = [
            'authenticated' => true,
            'role' => ['id' => 5, 'slug' => 'athlete', 'name' => 'Athlete'],
            'user' => ['id' => 999999, 'role_id' => 5, 'athlete_id' => 1, 'permissions' => ['team.view', 'tournament.view', 'training.view']],
            'permissions' => ['team.view', 'tournament.view', 'training.view'],
            'organization' => ['id' => $this->orgId],
        ];

        foreach ([
            $viewsDir . '/sports/teams-create.blade.php',
            $viewsDir . '/competitions/tournaments-create.blade.php',
            $viewsDir . '/sports/training-create.blade.php',
        ] as $createView) {
            ob_start();
            include $createView;
            $html = (string)ob_get_clean();
            if (stripos($html, '403') === false && stripos($html, 'Forbidden') === false && stripos($html, 'Restricted') === false) {
                return false;
            }
        }

        return true;
    }

    public function runAll(): bool
    {
        $tests = [
            '1. Teams: list & ONE TEAM = ONE ROW deduplication (multi-coach + multi-athlete)' => 'testTeamsListAndOneRowDeduplication',
            '2. Teams: search, sport/status filters & 20-per-page server-side pagination'     => 'testTeamsSearchFiltersAndPagination',
            '3. Teams: view, edit, roster/coach management, duplicate guard & soft delete'    => 'testTeamsViewEditRosterCoachesAndSoftDelete',
            '4. Tournaments: list, ONE TOURNAMENT = ONE ROW deduplication, search & filters'  => 'testTournamentsListDeduplicationSearchFilterAndPagination',
            '5. Tournaments: view, edit, teams, fixtures, match results & soft delete'        => 'testTournamentsViewEditTeamsFixturesAndResults',
            '6. Training: list deduplication, filters, attendance & full lifecycle'           => 'testTrainingListDeduplicationFiltersAttendanceAndLifecycle',
            '7. Security: strict tenant isolation & cross-org reference blocking'             => 'testTenantIsolationAcrossTeamsTournamentsAndTraining',
            '8. Security: RBAC permission enforcement for Sports Admin vs Athlete'            => 'testRbacPermissionsForTeamsTournamentsAndTraining',
            '9. Views & Direct URL Auth: all 12 Blade views syntax-clean & 403 guarded'       => 'test9BladeViewsRenderAndDirectUrlAuthorization',
        ];

        $passed = 0;
        $failed = 0;

        echo "====================================================================\n";
        echo "  KHELSUTRA — TEAMS + TOURNAMENTS + TRAINING ALIGNMENT TEST SUITE\n";
        echo "====================================================================\n";

        foreach ($tests as $label => $method) {
            try {
                $ok = $this->$method();
                if ($ok) {
                    echo " [PASS] {$label}\n";
                    $passed++;
                } else {
                    echo " [FAIL] {$label}\n";
                    $failed++;
                }
            } catch (\Throwable $e) {
                echo " [ERROR] {$label}: " . $e->getMessage() . "\n";
                $failed++;
            }
        }

        echo "--------------------------------------------------------------------\n";
        echo "Total: " . ($passed + $failed) . " | Passed: {$passed} | Failed: {$failed}\n";
        echo "====================================================================\n";

        return $failed === 0;
    }
}

if (php_sapi_name() === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    require_once __DIR__ . '/../../bootstrap/app.php';
    spl_autoload_register(function ($class) {
        $prefix = 'App\\';
        $base_dir = __DIR__ . '/../../app/';
        if (strncmp($prefix, $class, strlen($prefix)) === 0) {
            $file = $base_dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (file_exists($file)) require_once $file;
        }
    });
    require_once __DIR__ . '/../../app/Helpers/AuthContext.php';

    $suite = new TeamsTournamentsTrainingAlignmentTest();
    exit($suite->runAll() ? 0 : 1);
}
