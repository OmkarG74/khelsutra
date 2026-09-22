<?php

namespace Tests\Feature;

use App\Services\BaseService;
use App\Services\Athlete\AthleteService;
use App\Services\Coach\CoachService;
use App\Services\Team\TeamService;
use App\Services\Tournament\TournamentService;
use App\Services\Venue\VenueService;
use PDO;

class StatusAndSoftDeleteTest
{
    private int $orgId = 1; // Apex Sports Academy
    private int $otherOrgId = 999; // Separate tenant
    private int $userId = 5; // Sports Admin user
    private PDO $pdo;

    private AthleteService $athleteService;
    private CoachService $coachService;
    private TeamService $teamService;
    private TournamentService $tournamentService;
    private VenueService $venueService;

    // Track created test fixture IDs for safe teardown
    private array $createdAthleteIds = [];
    private array $createdCoachProfileIds = [];
    private array $createdEmployeeIds = [];
    private array $createdTeamIds = [];
    private array $createdTournamentIds = [];
    private array $createdVenueIds = [];
    private array $createdBookingIds = [];

    public function __construct()
    {
        $this->pdo = BaseService::getDatabaseConnection();
        $this->athleteService = new AthleteService();
        $this->coachService = new CoachService();
        $this->teamService = new TeamService();
        $this->tournamentService = new TournamentService();
        $this->venueService = new VenueService();
    }

    public function __destruct()
    {
        $this->cleanup();
    }

    private function cleanup(): void
    {
        if (!empty($this->createdBookingIds)) {
            $in = implode(',', array_map('intval', $this->createdBookingIds));
            $this->pdo->exec("DELETE FROM venue_bookings WHERE id IN ({$in})");
        }
        if (!empty($this->createdVenueIds)) {
            $in = implode(',', array_map('intval', $this->createdVenueIds));
            $this->pdo->exec("DELETE FROM venues WHERE id IN ({$in})");
        }
        if (!empty($this->createdTournamentIds)) {
            $in = implode(',', array_map('intval', $this->createdTournamentIds));
            $this->pdo->exec("DELETE FROM tournament_teams WHERE tournament_id IN ({$in})");
            $this->pdo->exec("DELETE FROM fixtures WHERE tournament_id IN ({$in})");
            $this->pdo->exec("DELETE FROM tournaments WHERE id IN ({$in})");
        }
        if (!empty($this->createdTeamIds)) {
            $in = implode(',', array_map('intval', $this->createdTeamIds));
            $this->pdo->exec("DELETE FROM team_members WHERE team_id IN ({$in})");
            $this->pdo->exec("DELETE FROM team_coaches WHERE team_id IN ({$in})");
            $this->pdo->exec("DELETE FROM teams WHERE id IN ({$in})");
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
        if (!empty($this->createdAthleteIds)) {
            $in = implode(',', array_map('intval', $this->createdAthleteIds));
            $this->pdo->exec("DELETE FROM team_members WHERE athlete_id IN ({$in})");
            $this->pdo->exec("DELETE FROM athlete_guardians WHERE athlete_id IN ({$in})");
            $this->pdo->exec("DELETE FROM athlete_documents WHERE athlete_id IN ({$in})");
            $this->pdo->exec("DELETE FROM athletes WHERE id IN ({$in})");
        }
    }

    private function executeWebAction(string $uri, array $post, array $session = []): int
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ks_maint_');
        $bootstrapPath = addslashes(str_replace('\\', '/', dirname(__DIR__, 2) . '/bootstrap/app.php'));
        $actionsPath = addslashes(str_replace('\\', '/', dirname(__DIR__, 2) . '/routes/web_actions.php'));

        $defaultSession = [
            'auth' => [
                'authenticated' => true,
                'user' => [
                    'id' => $this->userId,
                    'email' => 'sportsadmin@khelsutra.local',
                    'first_name' => 'Sports',
                    'last_name' => 'Admin',
                    'role_id' => 2,
                    'role' => ['id' => 2, 'name' => 'Sports Administrator', 'slug' => 'sports_admin'],
                    'permissions' => [
                        'athlete.update', 'athlete.edit', 'athlete.delete', 'athlete.view',
                        'coach.update', 'coach.manage', 'coach.view',
                        'team.update', 'team.manage', 'team.view', 'team.members.manage', 'team.coaches.manage',
                        'tournament.manage', 'tournament.update', 'tournament.view',
                        'venue.update', 'venue.manage', 'venue.view', 'venue.booking.manage'
                    ],
                    'status' => 'active'
                ],
                'organization' => [
                    'id' => $this->orgId,
                    'name' => 'Apex Sports Academy',
                    'code' => 'APEX'
                ]
            ]
        ];

        $effectiveSession = !empty($session) ? $session : $defaultSession;
        $sessionExport = var_export($effectiveSession, true);
        $postExport = var_export($post, true);

        $code = "<?php\n"
            . "require_once '{$bootstrapPath}';\n"
            . "\$_SERVER['REQUEST_METHOD'] = 'POST';\n"
            . "\$_SERVER['REQUEST_URI'] = '{$uri}';\n"
            . "\$method = 'POST';\n"
            . "\$uri = '{$uri}';\n"
            . "\$_POST = {$postExport};\n"
            . "\$_SESSION = {$sessionExport};\n"
            . "require '{$actionsPath}';\n";

        file_put_contents($tempFile, $code);

        $cmd = 'php ' . escapeshellarg($tempFile) . ' 2>&1';
        exec($cmd, $output, $exitCode);

        @unlink($tempFile);

        return $exitCode;
    }

    private function createTestAthlete(int $orgId, string $status = 'active'): int
    {
        $unique = bin2hex(random_bytes(3));
        $stmt = $this->pdo->prepare("
            INSERT INTO athletes (organization_id, athlete_code, first_name, last_name, date_of_birth, gender, current_sport_id, status, created_at, updated_at)
            VALUES (:org_id, :code, :first, :last, '2005-01-01', 'male', 1, :status, NOW(), NOW())
        ");
        $code = 'ATH-T-' . $unique;
        $stmt->execute([
            ':org_id' => $orgId,
            ':code' => $code,
            ':first' => 'TestAth',
            ':last' => $unique,
            ':status' => $status
        ]);
        $id = (int)$this->pdo->lastInsertId();
        $this->createdAthleteIds[] = $id;
        return $id;
    }

    private function createTestCoach(int $orgId, string $status = 'active'): int
    {
        $unique = bin2hex(random_bytes(3));
        $empCode = 'EMP-T-' . $unique;
        $coachCode = 'CCH-T-' . $unique;

        $eStmt = $this->pdo->prepare("
            INSERT INTO employees (organization_id, employee_code, first_name, last_name, email, employment_status, created_at, updated_at)
            VALUES (:org_id, :code, :first, :last, :email, 'active', NOW(), NOW())
        ");
        $eStmt->execute([
            ':org_id' => $orgId,
            ':code' => $empCode,
            ':first' => 'TestCoach',
            ':last' => $unique,
            ':email' => "coach_{$unique}@khelsutra.local"
        ]);
        $empId = (int)$this->pdo->lastInsertId();
        $this->createdEmployeeIds[] = $empId;

        $cStmt = $this->pdo->prepare("
            INSERT INTO coach_profiles (organization_id, employee_id, coach_code, specialization, experience_years, status, created_at, updated_at)
            VALUES (:org_id, :emp_id, :code, 'Football Tactical', 5.0, :status, NOW(), NOW())
        ");
        $cStmt->execute([
            ':org_id' => $orgId,
            ':emp_id' => $empId,
            ':code' => $coachCode,
            ':status' => $status
        ]);
        $cId = (int)$this->pdo->lastInsertId();
        $this->createdCoachProfileIds[] = $cId;
        return $cId;
    }

    private function createTestTeam(int $orgId, string $status = 'active'): int
    {
        $unique = bin2hex(random_bytes(3));
        $code = 'TEAM-T-' . $unique;
        $stmt = $this->pdo->prepare("
            INSERT INTO teams (organization_id, team_code, name, sport_id, gender, age_group, status, created_at, updated_at)
            VALUES (:org_id, :code, :name, 1, 'open', 'U-19', :status, NOW(), NOW())
        ");
        $stmt->execute([
            ':org_id' => $orgId,
            ':code' => $code,
            ':name' => 'Team ' . $unique,
            ':status' => $status
        ]);
        $id = (int)$this->pdo->lastInsertId();
        $this->createdTeamIds[] = $id;
        return $id;
    }

    private function createTestTournament(int $orgId, string $status = 'draft'): int
    {
        $unique = bin2hex(random_bytes(3));
        $ref = 'TOURN-T-' . $unique;
        $stmt = $this->pdo->prepare("
            INSERT INTO tournaments (organization_id, tournament_reference, name, sport_id, start_date, end_date, status, created_at, updated_at)
            VALUES (:org_id, :ref, :name, 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 5 DAY), :status, NOW(), NOW())
        ");
        $stmt->execute([
            ':org_id' => $orgId,
            ':ref' => $ref,
            ':name' => 'Tourney ' . $unique,
            ':status' => $status
        ]);
        $id = (int)$this->pdo->lastInsertId();
        $this->createdTournamentIds[] = $id;
        return $id;
    }

    private function createTestVenue(int $orgId, string $status = 'active'): int
    {
        $unique = bin2hex(random_bytes(3));
        $code = 'VEN-T-' . $unique;
        $stmt = $this->pdo->prepare("
            INSERT INTO venues (organization_id, venue_code, name, venue_type, status, created_at, updated_at)
            VALUES (:org_id, :code, :name, 'Stadium', :status, NOW(), NOW())
        ");
        $stmt->execute([
            ':org_id' => $orgId,
            ':code' => $code,
            ':name' => 'Venue ' . $unique,
            ':status' => $status
        ]);
        $id = (int)$this->pdo->lastInsertId();
        $this->createdVenueIds[] = $id;
        return $id;
    }

    // ==========================================
    // ATHLETE TESTS
    // ==========================================

    public function testAthleteActiveToInactive(): bool
    {
        $athId = $this->createTestAthlete($this->orgId, 'active');

        $this->executeWebAction("/athletes/{$athId}/status", ['status' => 'inactive']);

        $stmt = $this->pdo->prepare("SELECT status, deleted_at FROM athletes WHERE id = :id");
        $stmt->execute([':id' => $athId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || $row['status'] !== 'inactive' || $row['deleted_at'] !== null) {
            return false;
        }

        // Verify audit log
        $aStmt = $this->pdo->prepare("SELECT id FROM audit_logs WHERE table_name = 'athletes' AND record_id = :id AND action = 'ATHLETE_UPDATE' LIMIT 1");
        $aStmt->execute([':id' => $athId]);
        return (bool)$aStmt->fetchColumn();
    }

    public function testAthleteInactiveToActive(): bool
    {
        $athId = $this->createTestAthlete($this->orgId, 'inactive');

        $this->executeWebAction("/athletes/{$athId}/status", ['status' => 'active']);

        $stmt = $this->pdo->prepare("SELECT status, deleted_at FROM athletes WHERE id = :id");
        $stmt->execute([':id' => $athId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row && $row['status'] === 'active' && $row['deleted_at'] === null;
    }

    public function testAthleteInvalidStatusRejected(): bool
    {
        $athId = $this->createTestAthlete($this->orgId, 'active');

        $this->executeWebAction("/athletes/{$athId}/status", ['status' => 'unknown_bad_status']);

        $stmt = $this->pdo->prepare("SELECT status FROM athletes WHERE id = :id");
        $stmt->execute([':id' => $athId]);
        return $stmt->fetchColumn() === 'active';
    }

    public function testAthleteUnauthorizedStatusRejected(): bool
    {
        $athId = $this->createTestAthlete($this->orgId, 'active');

        $unauthorizedSession = [
            'auth' => [
                'authenticated' => true,
                'user' => [
                    'id' => 99,
                    'role_id' => 5,
                    'role' => ['id' => 5, 'name' => 'Coach', 'slug' => 'coach'],
                    'permissions' => ['coach.view'],
                    'status' => 'active'
                ],
                'organization' => ['id' => $this->orgId]
            ]
        ];

        $this->executeWebAction("/athletes/{$athId}/status", ['status' => 'inactive'], $unauthorizedSession);

        $stmt = $this->pdo->prepare("SELECT status FROM athletes WHERE id = :id");
        $stmt->execute([':id' => $athId]);
        return $stmt->fetchColumn() === 'active';
    }

    public function testAthleteCrossTenantStatusRejected(): bool
    {
        $foreignAthId = $this->createTestAthlete($this->otherOrgId, 'active');

        $this->executeWebAction("/athletes/{$foreignAthId}/status", ['status' => 'inactive']);

        $stmt = $this->pdo->prepare("SELECT status FROM athletes WHERE id = :id");
        $stmt->execute([':id' => $foreignAthId]);
        return $stmt->fetchColumn() === 'active';
    }

    public function testAthleteSoftDelete(): bool
    {
        $athId = $this->createTestAthlete($this->orgId, 'active');

        $this->executeWebAction("/athletes/{$athId}/delete", []);

        $stmt = $this->pdo->prepare("SELECT status, deleted_at FROM athletes WHERE id = :id");
        $stmt->execute([':id' => $athId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Record must NOT be hard-deleted, but deleted_at MUST be set
        if (!$row || empty($row['deleted_at'])) {
            return false;
        }

        // Verify audit log
        $aStmt = $this->pdo->prepare("SELECT id FROM audit_logs WHERE table_name = 'athletes' AND record_id = :id AND action = 'ATHLETE_DELETE' LIMIT 1");
        $aStmt->execute([':id' => $athId]);
        return (bool)$aStmt->fetchColumn();
    }

    public function testAthleteUnauthorizedDeleteRejected(): bool
    {
        $athId = $this->createTestAthlete($this->orgId, 'active');

        $unauthorizedSession = [
            'auth' => [
                'authenticated' => true,
                'user' => [
                    'id' => 99,
                    'role_id' => 5,
                    'role' => ['id' => 5, 'name' => 'Coach', 'slug' => 'coach'],
                    'permissions' => ['coach.view'],
                    'status' => 'active'
                ],
                'organization' => ['id' => $this->orgId]
            ]
        ];

        $this->executeWebAction("/athletes/{$athId}/delete", [], $unauthorizedSession);

        $stmt = $this->pdo->prepare("SELECT deleted_at FROM athletes WHERE id = :id");
        $stmt->execute([':id' => $athId]);
        return $stmt->fetchColumn() === null;
    }

    public function testAthleteCrossTenantDeleteRejected(): bool
    {
        $foreignAthId = $this->createTestAthlete($this->otherOrgId, 'active');

        $this->executeWebAction("/athletes/{$foreignAthId}/delete", []);

        $stmt = $this->pdo->prepare("SELECT deleted_at FROM athletes WHERE id = :id");
        $stmt->execute([':id' => $foreignAthId]);
        return $stmt->fetchColumn() === null;
    }

    // ==========================================
    // COACH TESTS
    // ==========================================

    public function testCoachActiveToInactive(): bool
    {
        $coachId = $this->createTestCoach($this->orgId, 'active');

        $this->executeWebAction("/coaches/{$coachId}/status", ['status' => 'inactive']);

        $stmt = $this->pdo->prepare("SELECT status, deleted_at FROM coach_profiles WHERE id = :id");
        $stmt->execute([':id' => $coachId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || $row['status'] !== 'inactive' || $row['deleted_at'] !== null) {
            return false;
        }

        $aStmt = $this->pdo->prepare("SELECT id FROM audit_logs WHERE table_name = 'coach_profiles' AND record_id = :id AND action = 'COACH_STATUS_UPDATE' LIMIT 1");
        $aStmt->execute([':id' => $coachId]);
        return (bool)$aStmt->fetchColumn();
    }

    public function testCoachInactiveToActive(): bool
    {
        $coachId = $this->createTestCoach($this->orgId, 'inactive');

        $this->executeWebAction("/coaches/{$coachId}/status", ['status' => 'active']);

        $stmt = $this->pdo->prepare("SELECT status, deleted_at FROM coach_profiles WHERE id = :id");
        $stmt->execute([':id' => $coachId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row && $row['status'] === 'active' && $row['deleted_at'] === null;
    }

    public function testCoachSoftDelete(): bool
    {
        $coachId = $this->createTestCoach($this->orgId, 'active');

        $this->executeWebAction("/coaches/{$coachId}/delete", []);

        $stmt = $this->pdo->prepare("SELECT deleted_at FROM coach_profiles WHERE id = :id");
        $stmt->execute([':id' => $coachId]);
        $del = $stmt->fetchColumn();

        if (empty($del)) {
            return false;
        }

        $aStmt = $this->pdo->prepare("SELECT id FROM audit_logs WHERE table_name = 'coach_profiles' AND record_id = :id AND action = 'COACH_DELETE' LIMIT 1");
        $aStmt->execute([':id' => $coachId]);
        return (bool)$aStmt->fetchColumn();
    }

    public function testCoachSoftDeletePreservesTeamCoachHistory(): bool
    {
        $coachId = $this->createTestCoach($this->orgId, 'active');
        $teamId = $this->createTestTeam($this->orgId, 'active');

        // Assign coach to team
        $stmt = $this->pdo->prepare("
            INSERT INTO team_coaches (organization_id, team_id, coach_id, coach_role, start_date, is_primary, created_at, updated_at)
            VALUES (:org_id, :team_id, :coach_id, 'assistant_coach', CURDATE(), 0, NOW(), NOW())
        ");
        $stmt->execute([':org_id' => $this->orgId, ':team_id' => $teamId, ':coach_id' => $coachId]);

        // Soft-delete coach
        $this->executeWebAction("/coaches/{$coachId}/delete", []);

        // team_coaches row must remain intact
        $chk = $this->pdo->prepare("SELECT id, coach_role FROM team_coaches WHERE team_id = :team_id AND coach_id = :coach_id");
        $chk->execute([':team_id' => $teamId, ':coach_id' => $coachId]);
        $tcRow = $chk->fetch(PDO::FETCH_ASSOC);

        return !empty($tcRow) && $tcRow['coach_role'] === 'assistant_coach';
    }

    public function testCoachUnauthorizedAccessBlocked(): bool
    {
        $coachId = $this->createTestCoach($this->orgId, 'active');

        $unauthorizedSession = [
            'auth' => [
                'authenticated' => true,
                'user' => [
                    'id' => 99,
                    'role_id' => 6,
                    'role' => ['id' => 6, 'name' => 'Athlete', 'slug' => 'athlete'],
                    'permissions' => ['athlete.view'],
                    'status' => 'active'
                ],
                'organization' => ['id' => $this->orgId]
            ]
        ];

        // Status update rejected
        $this->executeWebAction("/coaches/{$coachId}/status", ['status' => 'inactive'], $unauthorizedSession);
        $s1 = $this->pdo->query("SELECT status FROM coach_profiles WHERE id = {$coachId}")->fetchColumn();

        // Delete rejected
        $this->executeWebAction("/coaches/{$coachId}/delete", [], $unauthorizedSession);
        $d1 = $this->pdo->query("SELECT deleted_at FROM coach_profiles WHERE id = {$coachId}")->fetchColumn();

        return $s1 === 'active' && $d1 === null;
    }

    public function testCoachCrossTenantRejected(): bool
    {
        $foreignCoachId = $this->createTestCoach($this->otherOrgId, 'active');

        $this->executeWebAction("/coaches/{$foreignCoachId}/status", ['status' => 'inactive']);
        $s = $this->pdo->query("SELECT status FROM coach_profiles WHERE id = {$foreignCoachId}")->fetchColumn();

        $this->executeWebAction("/coaches/{$foreignCoachId}/delete", []);
        $d = $this->pdo->query("SELECT deleted_at FROM coach_profiles WHERE id = {$foreignCoachId}")->fetchColumn();

        return $s === 'active' && $d === null;
    }

    // ==========================================
    // TEAM TESTS
    // ==========================================

    public function testTeamActiveToInactive(): bool
    {
        $teamId = $this->createTestTeam($this->orgId, 'active');

        $this->executeWebAction("/teams/{$teamId}/status", ['status' => 'inactive']);

        $stmt = $this->pdo->prepare("SELECT status, deleted_at FROM teams WHERE id = :id");
        $stmt->execute([':id' => $teamId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || $row['status'] !== 'inactive' || $row['deleted_at'] !== null) {
            return false;
        }

        $aStmt = $this->pdo->prepare("SELECT id FROM audit_logs WHERE table_name = 'teams' AND record_id = :id AND action = 'TEAM_UPDATE' LIMIT 1");
        $aStmt->execute([':id' => $teamId]);
        return (bool)$aStmt->fetchColumn();
    }

    public function testTeamInactiveToActive(): bool
    {
        $teamId = $this->createTestTeam($this->orgId, 'inactive');

        $this->executeWebAction("/teams/{$teamId}/status", ['status' => 'active']);

        $stmt = $this->pdo->prepare("SELECT status, deleted_at FROM teams WHERE id = :id");
        $stmt->execute([':id' => $teamId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row && $row['status'] === 'active' && $row['deleted_at'] === null;
    }

    public function testTeamStatusChangePreservesRosterAndCoachAndTournament(): bool
    {
        $teamId = $this->createTestTeam($this->orgId, 'active');
        $athId = $this->createTestAthlete($this->orgId, 'active');
        $cId = $this->createTestCoach($this->orgId, 'active');
        $tournId = $this->createTestTournament($this->orgId, 'draft');

        // Add roster
        $this->pdo->prepare("INSERT INTO team_members (organization_id, team_id, athlete_id, start_date, is_current, member_role, created_at, updated_at) VALUES (:org_id, :team_id, :ath_id, CURDATE(), 1, 'player', NOW(), NOW())")->execute([':org_id' => $this->orgId, ':team_id' => $teamId, ':ath_id' => $athId]);

        // Add coach
        $this->pdo->prepare("INSERT INTO team_coaches (organization_id, team_id, coach_id, coach_role, start_date, is_primary, created_at, updated_at) VALUES (:org_id, :team_id, :coach_id, 'head_coach', CURDATE(), 1, NOW(), NOW())")->execute([':org_id' => $this->orgId, ':team_id' => $teamId, ':coach_id' => $cId]);

        // Add tournament registration
        $this->pdo->prepare("INSERT INTO tournament_teams (tournament_id, team_id, status, registered_at) VALUES (:tourn_id, :team_id, 'enrolled', NOW())")->execute([':tourn_id' => $tournId, ':team_id' => $teamId]);

        // Change team to inactive
        $this->executeWebAction("/teams/{$teamId}/status", ['status' => 'inactive']);

        // Check roster is intact
        $rCount = (int)$this->pdo->query("SELECT count(*) FROM team_members WHERE team_id = {$teamId} AND is_current = 1")->fetchColumn();

        // Check coach is intact
        $cCount = (int)$this->pdo->query("SELECT count(*) FROM team_coaches WHERE team_id = {$teamId}")->fetchColumn();

        // Check tournament team is intact
        $tCount = (int)$this->pdo->query("SELECT count(*) FROM tournament_teams WHERE team_id = {$teamId}")->fetchColumn();

        return $rCount === 1 && $cCount === 1 && $tCount === 1;
    }

    public function testTeamInvalidStatusRejected(): bool
    {
        $teamId = $this->createTestTeam($this->orgId, 'active');

        $this->executeWebAction("/teams/{$teamId}/status", ['status' => 'nonexistent_status']);

        $stmt = $this->pdo->prepare("SELECT status FROM teams WHERE id = :id");
        $stmt->execute([':id' => $teamId]);
        return $stmt->fetchColumn() === 'active';
    }

    public function testTeamUnauthorizedStatusRejected(): bool
    {
        $teamId = $this->createTestTeam($this->orgId, 'active');

        $unauthSession = [
            'auth' => [
                'authenticated' => true,
                'user' => [
                    'id' => 99,
                    'role_id' => 6,
                    'role' => ['id' => 6, 'name' => 'Athlete', 'slug' => 'athlete'],
                    'permissions' => ['team.view'],
                    'status' => 'active'
                ],
                'organization' => ['id' => $this->orgId]
            ]
        ];

        $this->executeWebAction("/teams/{$teamId}/status", ['status' => 'inactive'], $unauthSession);

        $stmt = $this->pdo->prepare("SELECT status FROM teams WHERE id = :id");
        $stmt->execute([':id' => $teamId]);
        return $stmt->fetchColumn() === 'active';
    }

    public function testTeamCrossTenantStatusRejected(): bool
    {
        $foreignTeamId = $this->createTestTeam($this->otherOrgId, 'active');

        $this->executeWebAction("/teams/{$foreignTeamId}/status", ['status' => 'inactive']);

        $stmt = $this->pdo->prepare("SELECT status FROM teams WHERE id = :id");
        $stmt->execute([':id' => $foreignTeamId]);
        return $stmt->fetchColumn() === 'active';
    }

    public function testTeamSoftDelete(): bool
    {
        $teamId = $this->createTestTeam($this->orgId, 'active');

        $this->executeWebAction("/teams/{$teamId}/delete", []);

        $stmt = $this->pdo->prepare("SELECT status, deleted_at FROM teams WHERE id = :id");
        $stmt->execute([':id' => $teamId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || empty($row['deleted_at'])) {
            return false;
        }

        $aStmt = $this->pdo->prepare("SELECT id FROM audit_logs WHERE table_name = 'teams' AND record_id = :id AND action = 'TEAM_DELETE' LIMIT 1");
        $aStmt->execute([':id' => $teamId]);
        return (bool)$aStmt->fetchColumn();
    }

    public function testTeamUnauthorizedDeleteRejected(): bool
    {
        $teamId = $this->createTestTeam($this->orgId, 'active');

        $unauthSession = [
            'auth' => [
                'authenticated' => true,
                'user' => [
                    'id' => 99,
                    'role_id' => 6,
                    'role' => ['id' => 6, 'name' => 'Athlete', 'slug' => 'athlete'],
                    'permissions' => ['team.view'],
                    'status' => 'active'
                ],
                'organization' => ['id' => $this->orgId]
            ]
        ];

        $this->executeWebAction("/teams/{$teamId}/delete", [], $unauthSession);

        $stmt = $this->pdo->prepare("SELECT deleted_at FROM teams WHERE id = :id");
        $stmt->execute([':id' => $teamId]);
        return $stmt->fetchColumn() === null;
    }

    public function testTeamCrossTenantDeleteRejected(): bool
    {
        $foreignTeamId = $this->createTestTeam($this->otherOrgId, 'active');

        $this->executeWebAction("/teams/{$foreignTeamId}/delete", []);

        $stmt = $this->pdo->prepare("SELECT deleted_at FROM teams WHERE id = :id");
        $stmt->execute([':id' => $foreignTeamId]);
        return $stmt->fetchColumn() === null;
    }

    // ==========================================
    // TOURNAMENT TESTS
    // ==========================================

    public function testTournamentLifecyclePreserved(): bool
    {
        // 6 valid lifecycle states must be supported in DB
        $validStatuses = ['draft', 'registration_open', 'registration_closed', 'ongoing', 'completed', 'cancelled'];
        foreach ($validStatuses as $st) {
            $tId = $this->createTestTournament($this->orgId, $st);
            $check = $this->pdo->query("SELECT status FROM tournaments WHERE id = {$tId}")->fetchColumn();
            if ($check !== $st) {
                return false;
            }
        }
        return true;
    }

    public function testTournamentSoftDelete(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'draft');

        $this->executeWebAction("/tournaments/{$tournId}/delete", []);

        $stmt = $this->pdo->prepare("SELECT deleted_at FROM tournaments WHERE id = :id");
        $stmt->execute([':id' => $tournId]);
        $del = $stmt->fetchColumn();

        if (empty($del)) {
            return false;
        }

        $aStmt = $this->pdo->prepare("SELECT id FROM audit_logs WHERE table_name = 'tournaments' AND record_id = :id AND action = 'TOURNAMENT_DELETE' LIMIT 1");
        $aStmt->execute([':id' => $tournId]);
        return (bool)$aStmt->fetchColumn();
    }

    public function testTournamentUnauthorizedDeleteRejected(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'draft');

        $unauthSession = [
            'auth' => [
                'authenticated' => true,
                'user' => [
                    'id' => 99,
                    'role_id' => 6,
                    'role' => ['id' => 6, 'name' => 'Athlete', 'slug' => 'athlete'],
                    'permissions' => ['tournament.view'],
                    'status' => 'active'
                ],
                'organization' => ['id' => $this->orgId]
            ]
        ];

        $this->executeWebAction("/tournaments/{$tournId}/delete", [], $unauthSession);

        $stmt = $this->pdo->prepare("SELECT deleted_at FROM tournaments WHERE id = :id");
        $stmt->execute([':id' => $tournId]);
        return $stmt->fetchColumn() === null;
    }

    public function testTournamentCrossTenantDeleteRejected(): bool
    {
        $foreignTournId = $this->createTestTournament($this->otherOrgId, 'draft');

        $this->executeWebAction("/tournaments/{$foreignTournId}/delete", []);

        $stmt = $this->pdo->prepare("SELECT deleted_at FROM tournaments WHERE id = :id");
        $stmt->execute([':id' => $foreignTournId]);
        return $stmt->fetchColumn() === null;
    }

    public function testTournamentRelationshipsProtectedOnSoftDelete(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'ongoing');
        $teamId = $this->createTestTeam($this->orgId, 'active');

        // Add team to tournament
        $this->pdo->prepare("INSERT INTO tournament_teams (tournament_id, team_id, status, registered_at) VALUES (:t_id, :tm_id, 'enrolled', NOW())")->execute([':t_id' => $tournId, ':tm_id' => $teamId]);

        // Add fixture
        $fixRef = 'FIX-T-' . bin2hex(random_bytes(3));
        $this->pdo->prepare("INSERT INTO fixtures (organization_id, tournament_id, fixture_reference, home_team_id, away_team_id, scheduled_date, scheduled_start_time, status, created_at, updated_at) VALUES (:org_id, :t_id, :fix_ref, :home_id, :away_id, CURDATE(), '10:00:00', 'scheduled', NOW(), NOW())")->execute([
            ':org_id' => $this->orgId,
            ':t_id' => $tournId,
            ':fix_ref' => $fixRef,
            ':home_id' => $teamId,
            ':away_id' => $teamId,
        ]);

        // Soft delete tournament
        $this->executeWebAction("/tournaments/{$tournId}/delete", []);

        // Verify tournament has deleted_at set but rows in tournament_teams and fixtures remain in DB
        $tDel = $this->pdo->query("SELECT deleted_at FROM tournaments WHERE id = {$tournId}")->fetchColumn();
        $ttCount = (int)$this->pdo->query("SELECT count(*) FROM tournament_teams WHERE tournament_id = {$tournId}")->fetchColumn();
        $fCount = (int)$this->pdo->query("SELECT count(*) FROM fixtures WHERE tournament_id = {$tournId}")->fetchColumn();

        return !empty($tDel) && $ttCount === 1 && $fCount === 1;
    }

    // ==========================================
    // VENUE TESTS
    // ==========================================

    public function testVenueActiveToInactive(): bool
    {
        $venueId = $this->createTestVenue($this->orgId, 'active');

        $this->executeWebAction("/venues/{$venueId}/status", ['status' => 'inactive']);

        $stmt = $this->pdo->prepare("SELECT status, deleted_at FROM venues WHERE id = :id");
        $stmt->execute([':id' => $venueId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || $row['status'] !== 'inactive' || $row['deleted_at'] !== null) {
            return false;
        }

        $aStmt = $this->pdo->prepare("SELECT id FROM audit_logs WHERE table_name = 'venues' AND record_id = :id AND action = 'VENUE_UPDATE' LIMIT 1");
        $aStmt->execute([':id' => $venueId]);
        return (bool)$aStmt->fetchColumn();
    }

    public function testVenueInactiveToActive(): bool
    {
        $venueId = $this->createTestVenue($this->orgId, 'inactive');

        $this->executeWebAction("/venues/{$venueId}/status", ['status' => 'active']);

        $stmt = $this->pdo->prepare("SELECT status, deleted_at FROM venues WHERE id = :id");
        $stmt->execute([':id' => $venueId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row && $row['status'] === 'active' && $row['deleted_at'] === null;
    }

    public function testVenueInvalidStatusRejected(): bool
    {
        $venueId = $this->createTestVenue($this->orgId, 'active');

        $this->executeWebAction("/venues/{$venueId}/status", ['status' => 'bogus_status']);

        $stmt = $this->pdo->prepare("SELECT status FROM venues WHERE id = :id");
        $stmt->execute([':id' => $venueId]);
        return $stmt->fetchColumn() === 'active';
    }

    public function testVenueUnauthorizedStatusRejected(): bool
    {
        $venueId = $this->createTestVenue($this->orgId, 'active');

        $unauthSession = [
            'auth' => [
                'authenticated' => true,
                'user' => [
                    'id' => 99,
                    'role_id' => 6,
                    'role' => ['id' => 6, 'name' => 'Athlete', 'slug' => 'athlete'],
                    'permissions' => ['venue.view'],
                    'status' => 'active'
                ],
                'organization' => ['id' => $this->orgId]
            ]
        ];

        $this->executeWebAction("/venues/{$venueId}/status", ['status' => 'inactive'], $unauthSession);

        $stmt = $this->pdo->prepare("SELECT status FROM venues WHERE id = :id");
        $stmt->execute([':id' => $venueId]);
        return $stmt->fetchColumn() === 'active';
    }

    public function testVenueCrossTenantStatusRejected(): bool
    {
        $foreignVenueId = $this->createTestVenue($this->otherOrgId, 'active');

        $this->executeWebAction("/venues/{$foreignVenueId}/status", ['status' => 'inactive']);

        $stmt = $this->pdo->prepare("SELECT status FROM venues WHERE id = :id");
        $stmt->execute([':id' => $foreignVenueId]);
        return $stmt->fetchColumn() === 'active';
    }

    public function testVenueSoftDelete(): bool
    {
        $venueId = $this->createTestVenue($this->orgId, 'active');

        $this->executeWebAction("/venues/{$venueId}/delete", []);

        $stmt = $this->pdo->prepare("SELECT deleted_at FROM venues WHERE id = :id");
        $stmt->execute([':id' => $venueId]);
        $del = $stmt->fetchColumn();

        if (empty($del)) {
            return false;
        }

        $aStmt = $this->pdo->prepare("SELECT id FROM audit_logs WHERE table_name = 'venues' AND record_id = :id AND action = 'VENUE_DELETE' LIMIT 1");
        $aStmt->execute([':id' => $venueId]);
        return (bool)$aStmt->fetchColumn();
    }

    public function testVenueSoftDeletePreservesBookings(): bool
    {
        $venueId = $this->createTestVenue($this->orgId, 'active');

        // Create booking referencing venue
        $stmt = $this->pdo->prepare("
            INSERT INTO venue_bookings (organization_id, venue_id, booking_reference, booked_by_user_id, purpose, booking_date, start_time, end_time, status, created_at, updated_at)
            VALUES (:org_id, :v_id, :ref, :u_id, 'Training Match', CURDATE(), '09:00:00', '11:00:00', 'approved', NOW(), NOW())
        ");
        $ref = 'BK-T-' . bin2hex(random_bytes(3));
        $stmt->execute([
            ':org_id' => $this->orgId,
            ':v_id' => $venueId,
            ':ref' => $ref,
            ':u_id' => $this->userId
        ]);
        $bkId = (int)$this->pdo->lastInsertId();
        $this->createdBookingIds[] = $bkId;

        // Soft delete venue
        $this->executeWebAction("/venues/{$venueId}/delete", []);

        // Booking remains in DB
        $bCount = (int)$this->pdo->query("SELECT count(*) FROM venue_bookings WHERE id = {$bkId}")->fetchColumn();
        return $bCount === 1;
    }

    public function testVenueUnauthorizedDeleteRejected(): bool
    {
        $venueId = $this->createTestVenue($this->orgId, 'active');

        $unauthSession = [
            'auth' => [
                'authenticated' => true,
                'user' => [
                    'id' => 99,
                    'role_id' => 6,
                    'role' => ['id' => 6, 'name' => 'Athlete', 'slug' => 'athlete'],
                    'permissions' => ['venue.view'],
                    'status' => 'active'
                ],
                'organization' => ['id' => $this->orgId]
            ]
        ];

        $this->executeWebAction("/venues/{$venueId}/delete", [], $unauthSession);

        $stmt = $this->pdo->prepare("SELECT deleted_at FROM venues WHERE id = :id");
        $stmt->execute([':id' => $venueId]);
        return $stmt->fetchColumn() === null;
    }

    public function testVenueCrossTenantDeleteRejected(): bool
    {
        $foreignVenueId = $this->createTestVenue($this->otherOrgId, 'active');

        $this->executeWebAction("/venues/{$foreignVenueId}/delete", []);

        $stmt = $this->pdo->prepare("SELECT deleted_at FROM venues WHERE id = :id");
        $stmt->execute([':id' => $foreignVenueId]);
        return $stmt->fetchColumn() === null;
    }

    // ==========================================
    // PRESENTATION HELPER TESTS
    // ==========================================

    public function testCoachRolePresentationFormatting(): bool
    {
        $r1 = format_coach_role('head_coach');
        $r2 = format_coach_role('assistant_coach');
        $r3 = format_coach_role('fitness_coach');
        $r4 = format_coach_role('other');

        return $r1 === 'Head Coach'
            && $r2 === 'Assistant Coach'
            && $r3 === 'Fitness Coach'
            && $r4 === 'Other';
    }

    public function runAll(): bool
    {
        $tests = [
            // Athletes
            'testAthleteActiveToInactive' => $this->testAthleteActiveToInactive(),
            'testAthleteInactiveToActive' => $this->testAthleteInactiveToActive(),
            'testAthleteInvalidStatusRejected' => $this->testAthleteInvalidStatusRejected(),
            'testAthleteUnauthorizedStatusRejected' => $this->testAthleteUnauthorizedStatusRejected(),
            'testAthleteCrossTenantStatusRejected' => $this->testAthleteCrossTenantStatusRejected(),
            'testAthleteSoftDelete' => $this->testAthleteSoftDelete(),
            'testAthleteUnauthorizedDeleteRejected' => $this->testAthleteUnauthorizedDeleteRejected(),
            'testAthleteCrossTenantDeleteRejected' => $this->testAthleteCrossTenantDeleteRejected(),

            // Coaches
            'testCoachActiveToInactive' => $this->testCoachActiveToInactive(),
            'testCoachInactiveToActive' => $this->testCoachInactiveToActive(),
            'testCoachSoftDelete' => $this->testCoachSoftDelete(),
            'testCoachSoftDeletePreservesTeamCoachHistory' => $this->testCoachSoftDeletePreservesTeamCoachHistory(),
            'testCoachUnauthorizedAccessBlocked' => $this->testCoachUnauthorizedAccessBlocked(),
            'testCoachCrossTenantRejected' => $this->testCoachCrossTenantRejected(),

            // Teams
            'testTeamActiveToInactive' => $this->testTeamActiveToInactive(),
            'testTeamInactiveToActive' => $this->testTeamInactiveToActive(),
            'testTeamStatusChangePreservesRosterAndCoachAndTournament' => $this->testTeamStatusChangePreservesRosterAndCoachAndTournament(),
            'testTeamInvalidStatusRejected' => $this->testTeamInvalidStatusRejected(),
            'testTeamUnauthorizedStatusRejected' => $this->testTeamUnauthorizedStatusRejected(),
            'testTeamCrossTenantStatusRejected' => $this->testTeamCrossTenantStatusRejected(),
            'testTeamSoftDelete' => $this->testTeamSoftDelete(),
            'testTeamUnauthorizedDeleteRejected' => $this->testTeamUnauthorizedDeleteRejected(),
            'testTeamCrossTenantDeleteRejected' => $this->testTeamCrossTenantDeleteRejected(),

            // Tournaments
            'testTournamentLifecyclePreserved' => $this->testTournamentLifecyclePreserved(),
            'testTournamentSoftDelete' => $this->testTournamentSoftDelete(),
            'testTournamentUnauthorizedDeleteRejected' => $this->testTournamentUnauthorizedDeleteRejected(),
            'testTournamentCrossTenantDeleteRejected' => $this->testTournamentCrossTenantDeleteRejected(),
            'testTournamentRelationshipsProtectedOnSoftDelete' => $this->testTournamentRelationshipsProtectedOnSoftDelete(),

            // Venues
            'testVenueActiveToInactive' => $this->testVenueActiveToInactive(),
            'testVenueInactiveToActive' => $this->testVenueInactiveToActive(),
            'testVenueInvalidStatusRejected' => $this->testVenueInvalidStatusRejected(),
            'testVenueUnauthorizedStatusRejected' => $this->testVenueUnauthorizedStatusRejected(),
            'testVenueCrossTenantStatusRejected' => $this->testVenueCrossTenantStatusRejected(),
            'testVenueSoftDelete' => $this->testVenueSoftDelete(),
            'testVenueSoftDeletePreservesBookings' => $this->testVenueSoftDeletePreservesBookings(),
            'testVenueUnauthorizedDeleteRejected' => $this->testVenueUnauthorizedDeleteRejected(),
            'testVenueCrossTenantDeleteRejected' => $this->testVenueCrossTenantDeleteRejected(),

            // Presentation
            'testCoachRolePresentationFormatting' => $this->testCoachRolePresentationFormatting(),
        ];

        $allPassed = true;
        foreach ($tests as $name => $passed) {
            echo ($passed ? " [PASS] " : " [FAIL] ") . $name . PHP_EOL;
            if (!$passed) $allPassed = false;
        }

        return $allPassed;
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    if (file_exists(dirname(__DIR__, 2) . '/bootstrap/app.php')) {
        require_once dirname(__DIR__, 2) . '/bootstrap/app.php';
    }
    echo "Running StatusAndSoftDeleteTest suite..." . PHP_EOL;
    $test = new StatusAndSoftDeleteTest();
    $passed = $test->runAll();
    exit($passed ? 0 : 1);
}
