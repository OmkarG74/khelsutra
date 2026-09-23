<?php

namespace Tests\Feature;

use App\Services\BaseService;
use App\Services\Tournament\TournamentService;
use PDO;

class TournamentTeamRegistrationTest
{
    private int $orgId = 1; // Apex Sports Academy
    private int $otherOrgId = 999; // Separate tenant
    private int $userId = 5; // Sports Admin user
    private PDO $pdo;
    private TournamentService $service;

    // Track test IDs for automatic cleanup
    private array $createdTournamentIds = [];
    private array $createdTeamIds = [];

    public function __construct()
    {
        $this->pdo = BaseService::getDatabaseConnection();
        $this->service = new TournamentService($this->pdo);
    }

    public function __destruct()
    {
        $this->cleanup();
    }

    private function cleanup(): void
    {
        if (!empty($this->createdTournamentIds)) {
            $inTourns = implode(',', array_map('intval', $this->createdTournamentIds));
            $this->pdo->exec("DELETE FROM fixtures WHERE tournament_id IN ({$inTourns})");
            $this->pdo->exec("DELETE FROM tournament_standings WHERE tournament_id IN ({$inTourns})");
            $this->pdo->exec("DELETE FROM tournament_teams WHERE tournament_id IN ({$inTourns})");
            $this->pdo->exec("DELETE FROM tournaments WHERE id IN ({$inTourns})");
        }
        if (!empty($this->createdTeamIds)) {
            $inTeams = implode(',', array_map('intval', $this->createdTeamIds));
            $this->pdo->exec("DELETE FROM fixtures WHERE home_team_id IN ({$inTeams}) OR away_team_id IN ({$inTeams})");
            $this->pdo->exec("DELETE FROM tournament_standings WHERE team_id IN ({$inTeams})");
            $this->pdo->exec("DELETE FROM tournament_teams WHERE team_id IN ({$inTeams})");
            $this->pdo->exec("DELETE FROM teams WHERE id IN ({$inTeams})");
        }
    }

    /**
     * Helper to execute web_actions.php in an isolated PHP subprocess.
     */
    private function executeWebAction(string $uri, array $post, array $session = []): int
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ks_treg_');
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
                    'role' => ['name' => 'Sports Administrator', 'slug' => 'sports_admin'],
                    'permissions' => ['tournament.manage', 'tournament.update', 'tournament.view'],
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

    /**
     * Helper to create an isolated test tournament.
     */
    private function createTestTournament(int $orgId, string $status = 'ongoing'): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO tournaments (
                organization_id, tournament_reference, name, sport_id, 
                start_date, end_date, status, created_by, created_at, updated_at
            ) VALUES (
                :org_id, :ref, :name, 1, 
                CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), :status, :user_id, NOW(), NOW()
            )
        ");
        $ref = 'TRN-' . substr(uniqid(), -6);
        $stmt->execute([
            ':org_id' => $orgId,
            ':ref' => $ref,
            ':name' => 'Reg Test Championship ' . uniqid(),
            ':status' => $status,
            ':user_id' => $this->userId
        ]);
        $tournId = (int)$this->pdo->lastInsertId();
        $this->createdTournamentIds[] = $tournId;
        return $tournId;
    }

    /**
     * Helper to create an isolated test team.
     */
    private function createTestTeam(int $orgId, string $status = 'active', ?string $deletedAt = null): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO teams (organization_id, sport_id, name, team_code, status, deleted_at, created_at, updated_at)
            VALUES (:org_id, 1, :name, :code, :status, :deleted_at, NOW(), NOW())
        ");
        $code = 'TM-' . substr(uniqid(), -6);
        $stmt->execute([
            ':org_id' => $orgId,
            ':name' => 'Test Squad ' . uniqid(),
            ':code' => $code,
            ':status' => $status,
            ':deleted_at' => $deletedAt
        ]);
        $teamId = (int)$this->pdo->lastInsertId();
        $this->createdTeamIds[] = $teamId;
        return $teamId;
    }

    /**
     * TEST 1: Successful team registration via web action
     */
    public function testSuccessfulTeamRegistration(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $teamId = $this->createTestTeam($this->orgId);

        $this->executeWebAction("/tournaments/{$tournId}/teams/add", [
            'team_id' => $teamId
        ]);

        $stmt = $this->pdo->prepare("
            SELECT status FROM tournament_teams 
            WHERE tournament_id = :t_id AND team_id = :tm_id
        ");
        $stmt->execute([':t_id' => $tournId, ':tm_id' => $teamId]);
        $status = $stmt->fetchColumn();

        return ($status === 'approved');
    }

    /**
     * TEST 2: Participant appears correctly in tournament participants query
     */
    public function testParticipantAppearsInTournament(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $teamId = $this->createTestTeam($this->orgId);

        $this->executeWebAction("/tournaments/{$tournId}/teams/add", [
            'team_id' => $teamId
        ]);

        $tournament = $this->service->getTournament($this->orgId, $tournId);
        $participating = $tournament['participating_teams'] ?? [];

        foreach ($participating as $pt) {
            if ((int)$pt['team_id'] === $teamId && $pt['status'] === 'approved') {
                return true;
            }
        }

        return false;
    }

    /**
     * TEST 3: Duplicate registration is safely idempotent (no extra rows created)
     */
    public function testDuplicateRegistrationIsIdempotent(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $teamId = $this->createTestTeam($this->orgId);

        // Register first time
        $this->executeWebAction("/tournaments/{$tournId}/teams/add", [
            'team_id' => $teamId
        ]);

        // Register second time (duplicate attempt)
        $this->executeWebAction("/tournaments/{$tournId}/teams/add", [
            'team_id' => $teamId
        ]);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM tournament_teams 
            WHERE tournament_id = :t_id AND team_id = :tm_id
        ");
        $stmt->execute([':t_id' => $tournId, ':tm_id' => $teamId]);
        $count = (int)$stmt->fetchColumn();

        return ($count === 1);
    }

    /**
     * TEST 4: Withdrawn team re-registration reactivates to approved
     */
    public function testWithdrawnTeamReRegistration(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $teamId = $this->createTestTeam($this->orgId);

        // Register team
        $this->executeWebAction("/tournaments/{$tournId}/teams/add", ['team_id' => $teamId]);

        // Withdraw team
        $this->executeWebAction("/tournaments/{$tournId}/teams/{$teamId}/remove", []);

        $checkWithdrawn = $this->pdo->query("
            SELECT status FROM tournament_teams 
            WHERE tournament_id = {$tournId} AND team_id = {$teamId}
        ")->fetchColumn();
        if ($checkWithdrawn !== 'withdrawn') return false;

        // Re-register withdrawn team
        $this->executeWebAction("/tournaments/{$tournId}/teams/add", ['team_id' => $teamId]);

        $checkReactivated = $this->pdo->query("
            SELECT status FROM tournament_teams 
            WHERE tournament_id = {$tournId} AND team_id = {$teamId}
        ")->fetchColumn();

        return ($checkReactivated === 'approved');
    }

    /**
     * TEST 5: Terminal statuses (eliminated, winner, runner_up, qualified) are protected and not overwritten
     */
    public function testTerminalStatusesProtected(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $teamId = $this->createTestTeam($this->orgId);

        // Directly set a terminal status in tournament_teams
        $this->pdo->exec("
            INSERT INTO tournament_teams (tournament_id, team_id, status, registered_at)
            VALUES ({$tournId}, {$teamId}, 'eliminated', NOW())
        ");

        // Attempt re-registration via web action
        $this->executeWebAction("/tournaments/{$tournId}/teams/add", ['team_id' => $teamId]);

        // Status must remain 'eliminated'
        $statusAfter = $this->pdo->query("
            SELECT status FROM tournament_teams 
            WHERE tournament_id = {$tournId} AND team_id = {$teamId}
        ")->fetchColumn();

        return ($statusAfter === 'eliminated');
    }

    /**
     * TEST 6: Cross-tenant tournament registration rejected
     */
    public function testCrossTenantTournamentRejected(): bool
    {
        // Tournament belongs to other tenant (999)
        $otherTournId = $this->createTestTournament($this->otherOrgId);
        $teamId = $this->createTestTeam($this->orgId);

        // Session from org 1 attempts to add team to org 999 tournament
        $this->executeWebAction("/tournaments/{$otherTournId}/teams/add", [
            'team_id' => $teamId
        ]);

        $count = (int)$this->pdo->query("
            SELECT COUNT(*) FROM tournament_teams WHERE tournament_id = {$otherTournId}
        ")->fetchColumn();

        return ($count === 0);
    }

    /**
     * TEST 7: Cross-tenant team registration rejected
     */
    public function testCrossTenantTeamRejected(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        // Team belongs to other tenant (999)
        $otherTeamId = $this->createTestTeam($this->otherOrgId);

        // Session from org 1 attempts to add org 999 team
        $this->executeWebAction("/tournaments/{$tournId}/teams/add", [
            'team_id' => $otherTeamId,
            'organization_id' => $this->otherOrgId // Post tampering attempt
        ]);

        $count = (int)$this->pdo->query("
            SELECT COUNT(*) FROM tournament_teams 
            WHERE tournament_id = {$tournId} AND team_id = {$otherTeamId}
        ")->fetchColumn();

        return ($count === 0);
    }

    /**
     * TEST 8: Inactive / deleted team registration rejected
     */
    public function testInactiveOrDeletedTeamRejected(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $inactiveTeamId = $this->createTestTeam($this->orgId, 'inactive');
        $deletedTeamId = $this->createTestTeam($this->orgId, 'active', date('Y-m-d H:i:s'));

        // Attempt registering inactive team
        $this->executeWebAction("/tournaments/{$tournId}/teams/add", ['team_id' => $inactiveTeamId]);

        // Attempt registering soft-deleted team
        $this->executeWebAction("/tournaments/{$tournId}/teams/add", ['team_id' => $deletedTeamId]);

        $count = (int)$this->pdo->query("
            SELECT COUNT(*) FROM tournament_teams 
            WHERE tournament_id = {$tournId} AND team_id IN ({$inactiveTeamId}, {$deletedTeamId})
        ")->fetchColumn();

        return ($count === 0);
    }

    /**
     * TEST 9: Unauthorized role cannot register a team
     */
    public function testUnauthorizedRoleCannotRegisterTeam(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $teamId = $this->createTestTeam($this->orgId);

        $unauthorizedSession = [
            'auth' => [
                'authenticated' => true,
                'user' => [
                    'id' => 888,
                    'email' => 'viewer@khelsutra.local',
                    'first_name' => 'Viewer',
                    'last_name' => 'User',
                    'role_id' => 99,
                    'role' => ['name' => 'Viewer', 'slug' => 'viewer'],
                    'permissions' => ['tournament.view'], // Lacks tournament.update/manage
                    'status' => 'active'
                ],
                'organization' => [
                    'id' => $this->orgId,
                    'name' => 'Apex Sports Academy',
                    'code' => 'APEX'
                ]
            ]
        ];

        $this->executeWebAction("/tournaments/{$tournId}/teams/add", [
            'team_id' => $teamId
        ], $unauthorizedSession);

        $count = (int)$this->pdo->query("
            SELECT COUNT(*) FROM tournament_teams 
            WHERE tournament_id = {$tournId} AND team_id = {$teamId}
        ")->fetchColumn();

        return ($count === 0);
    }

    /**
     * TEST 10: Unauthorized role cannot withdraw a team
     */
    public function testUnauthorizedRoleCannotWithdrawTeam(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $teamId = $this->createTestTeam($this->orgId);

        // Register team with authorized session
        $this->executeWebAction("/tournaments/{$tournId}/teams/add", ['team_id' => $teamId]);

        $unauthorizedSession = [
            'auth' => [
                'authenticated' => true,
                'user' => [
                    'id' => 888,
                    'email' => 'viewer@khelsutra.local',
                    'first_name' => 'Viewer',
                    'last_name' => 'User',
                    'role_id' => 99,
                    'role' => ['name' => 'Viewer', 'slug' => 'viewer'],
                    'permissions' => ['tournament.view'], // Lacks tournament.update/manage
                    'status' => 'active'
                ],
                'organization' => [
                    'id' => $this->orgId,
                    'name' => 'Apex Sports Academy',
                    'code' => 'APEX'
                ]
            ]
        ];

        // Attempt withdrawal with unauthorized session
        $this->executeWebAction("/tournaments/{$tournId}/teams/{$teamId}/remove", [], $unauthorizedSession);

        // Team must still have status 'approved'
        $status = $this->pdo->query("
            SELECT status FROM tournament_teams 
            WHERE tournament_id = {$tournId} AND team_id = {$teamId}
        ")->fetchColumn();

        return ($status === 'approved');
    }

    /**
     * TEST 11: Audit log is created for registration and withdrawal
     */
    public function testAuditLogCreated(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $teamId = $this->createTestTeam($this->orgId);

        $this->executeWebAction("/tournaments/{$tournId}/teams/add", ['team_id' => $teamId]);

        $addLog = (int)$this->pdo->query("
            SELECT COUNT(*) FROM audit_logs 
            WHERE action = 'TOURNAMENT_TEAM_ADD' AND record_id = {$tournId}
        ")->fetchColumn();

        $this->executeWebAction("/tournaments/{$tournId}/teams/{$teamId}/remove", []);

        $removeLog = (int)$this->pdo->query("
            SELECT COUNT(*) FROM audit_logs 
            WHERE action = 'TOURNAMENT_TEAM_REMOVE' AND record_id = {$tournId}
        ")->fetchColumn();

        return ($addLog > 0 && $removeLog > 0);
    }

    /**
     * TEST 12: Invalid tournament/team IDs handled safely
     */
    public function testInvalidIdsHandledSafely(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);

        $exit1 = $this->executeWebAction("/tournaments/{$tournId}/teams/add", ['team_id' => 999999]);
        $exit2 = $this->executeWebAction("/tournaments/999999/teams/add", ['team_id' => 1]);
        $exit3 = $this->executeWebAction("/tournaments/{$tournId}/teams/999999/remove", []);

        return ($exit1 === 0 && $exit2 === 0 && $exit3 === 0);
    }

    /**
     * TEST 13: Participant count and data integrity maintained
     */
    public function testParticipantCountAndDataIntegrity(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $team1 = $this->createTestTeam($this->orgId);
        $team2 = $this->createTestTeam($this->orgId);

        $this->executeWebAction("/tournaments/{$tournId}/teams/add", ['team_id' => $team1]);
        $this->executeWebAction("/tournaments/{$tournId}/teams/add", ['team_id' => $team2]);

        $tournament = $this->service->getTournament($this->orgId, $tournId);
        $participants = $tournament['participating_teams'] ?? [];

        return (count($participants) === 2);
    }

    /**
     * TEST 14: Successful withdrawal sets status to withdrawn and preserves row
     */
    public function testSuccessfulWithdrawalPreservesRow(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $teamId = $this->createTestTeam($this->orgId);

        $this->executeWebAction("/tournaments/{$tournId}/teams/add", ['team_id' => $teamId]);
        $this->executeWebAction("/tournaments/{$tournId}/teams/{$teamId}/remove", []);

        $stmt = $this->pdo->prepare("
            SELECT id, status, registered_at FROM tournament_teams 
            WHERE tournament_id = :t_id AND team_id = :tm_id
        ");
        $stmt->execute([':t_id' => $tournId, ':tm_id' => $teamId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Row must be preserved with status 'withdrawn'
        return (!empty($row) && $row['status'] === 'withdrawn' && !empty($row['registered_at']));
    }

    /**
     * TEST 15: Scheduled fixture protection for THIS tournament blocks withdrawal
     */
    public function testScheduledFixtureInThisTournamentBlocksWithdrawal(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $homeTeam = $this->createTestTeam($this->orgId);
        $awayTeam = $this->createTestTeam($this->orgId);

        // Register both teams
        $this->executeWebAction("/tournaments/{$tournId}/teams/add", ['team_id' => $homeTeam]);
        $this->executeWebAction("/tournaments/{$tournId}/teams/add", ['team_id' => $awayTeam]);

        // Schedule fixture for this tournament
        $this->pdo->exec("
            INSERT INTO fixtures (
                organization_id, tournament_id, fixture_reference, round_name, 
                home_team_id, away_team_id, scheduled_date, scheduled_start_time, 
                status, created_at, updated_at
            ) VALUES (
                {$this->orgId}, {$tournId}, 'FIX-TEST-" . uniqid() . "', 'Round 1', 
                {$homeTeam}, {$awayTeam}, CURDATE(), '15:00:00', 
                'scheduled', NOW(), NOW()
            )
        ");

        // Attempt to withdraw homeTeam from THIS tournament
        $this->executeWebAction("/tournaments/{$tournId}/teams/{$homeTeam}/remove", []);

        // Withdrawal must be blocked; status must still be 'approved'
        $status = $this->pdo->query("
            SELECT status FROM tournament_teams 
            WHERE tournament_id = {$tournId} AND team_id = {$homeTeam}
        ")->fetchColumn();

        return ($status === 'approved');
    }

    /**
     * TEST 16: Scheduled fixture in ANOTHER tournament does NOT block withdrawal
     */
    public function testScheduledFixtureInAnotherTournamentDoesNotBlockWithdrawal(): bool
    {
        $tourn1 = $this->createTestTournament($this->orgId);
        $tourn2 = $this->createTestTournament($this->orgId);
        $team = $this->createTestTeam($this->orgId);
        $otherTeam = $this->createTestTeam($this->orgId);

        // Register team in both tournaments
        $this->executeWebAction("/tournaments/{$tourn1}/teams/add", ['team_id' => $team]);
        $this->executeWebAction("/tournaments/{$tourn2}/teams/add", ['team_id' => $team]);
        $this->executeWebAction("/tournaments/{$tourn2}/teams/add", ['team_id' => $otherTeam]);

        // Schedule fixture only in tourn2
        $this->pdo->exec("
            INSERT INTO fixtures (
                organization_id, tournament_id, fixture_reference, round_name, 
                home_team_id, away_team_id, scheduled_date, scheduled_start_time, 
                status, created_at, updated_at
            ) VALUES (
                {$this->orgId}, {$tourn2}, 'FIX-OTHER-" . uniqid() . "', 'Round 1', 
                {$team}, {$otherTeam}, CURDATE(), '15:00:00', 
                'scheduled', NOW(), NOW()
            )
        ");

        // Attempt to withdraw team from tourn1 (where NO fixtures are scheduled for it)
        $this->executeWebAction("/tournaments/{$tourn1}/teams/{$team}/remove", []);

        // Withdrawal in tourn1 must SUCCEED (status='withdrawn') because fixture was in tourn2
        $statusTourn1 = $this->pdo->query("
            SELECT status FROM tournament_teams 
            WHERE tournament_id = {$tourn1} AND team_id = {$team}
        ")->fetchColumn();

        return ($statusTourn1 === 'withdrawn');
    }

    /**
     * Run all tests in this suite.
     */
    public function runAll(): bool
    {
        $tests = [
            'testSuccessfulTeamRegistration' => $this->testSuccessfulTeamRegistration(),
            'testParticipantAppearsInTournament' => $this->testParticipantAppearsInTournament(),
            'testDuplicateRegistrationIsIdempotent' => $this->testDuplicateRegistrationIsIdempotent(),
            'testWithdrawnTeamReRegistration' => $this->testWithdrawnTeamReRegistration(),
            'testTerminalStatusesProtected' => $this->testTerminalStatusesProtected(),
            'testCrossTenantTournamentRejected' => $this->testCrossTenantTournamentRejected(),
            'testCrossTenantTeamRejected' => $this->testCrossTenantTeamRejected(),
            'testInactiveOrDeletedTeamRejected' => $this->testInactiveOrDeletedTeamRejected(),
            'testUnauthorizedRoleCannotRegisterTeam' => $this->testUnauthorizedRoleCannotRegisterTeam(),
            'testUnauthorizedRoleCannotWithdrawTeam' => $this->testUnauthorizedRoleCannotWithdrawTeam(),
            'testAuditLogCreated' => $this->testAuditLogCreated(),
            'testInvalidIdsHandledSafely' => $this->testInvalidIdsHandledSafely(),
            'testParticipantCountAndDataIntegrity' => $this->testParticipantCountAndDataIntegrity(),
            'testSuccessfulWithdrawalPreservesRow' => $this->testSuccessfulWithdrawalPreservesRow(),
            'testScheduledFixtureInThisTournamentBlocksWithdrawal' => $this->testScheduledFixtureInThisTournamentBlocksWithdrawal(),
            'testScheduledFixtureInAnotherTournamentDoesNotBlockWithdrawal' => $this->testScheduledFixtureInAnotherTournamentDoesNotBlockWithdrawal(),
        ];

        $allPassed = true;
        foreach ($tests as $name => $passed) {
            echo ($passed ? " [PASS] " : " [FAIL] ") . $name . PHP_EOL;
            if (!$passed) $allPassed = false;
        }

        $this->cleanup();

        return $allPassed;
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    if (file_exists(dirname(__DIR__, 2) . '/bootstrap/app.php')) {
        require_once dirname(__DIR__, 2) . '/bootstrap/app.php';
    }
    echo "Running TournamentTeamRegistrationTest suite..." . PHP_EOL;
    $test = new TournamentTeamRegistrationTest();
    $passed = $test->runAll();
    exit($passed ? 0 : 1);
}
