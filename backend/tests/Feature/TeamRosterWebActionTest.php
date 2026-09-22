<?php

namespace Tests\Feature;

use App\Services\BaseService;
use App\Services\Team\TeamService;
use PDO;

class TeamRosterWebActionTest
{
    private int $orgId = 1; // Apex Sports Academy
    private int $otherOrgId = 999; // Separate tenant
    private int $userId = 5; // Sports Admin user
    private PDO $pdo;
    private TeamService $service;

    // Track test IDs for cleanup
    private array $createdTeamIds = [];
    private array $createdAthleteIds = [];

    public function __construct()
    {
        $this->pdo = BaseService::getDatabaseConnection();
        $this->service = new TeamService();
    }

    public function __destruct()
    {
        $this->cleanup();
    }

    private function cleanup(): void
    {
        if (!empty($this->createdTeamIds)) {
            $inTeams = implode(',', array_map('intval', $this->createdTeamIds));
            $this->pdo->exec("DELETE FROM team_members WHERE team_id IN ({$inTeams})");
            $this->pdo->exec("DELETE FROM teams WHERE id IN ({$inTeams})");
        }
        if (!empty($this->createdAthleteIds)) {
            $inAthletes = implode(',', array_map('intval', $this->createdAthleteIds));
            $this->pdo->exec("DELETE FROM team_members WHERE athlete_id IN ({$inAthletes})");
            $this->pdo->exec("DELETE FROM athletes WHERE id IN ({$inAthletes})");
        }
    }

    /**
     * Helper to execute web_actions.php in an isolated PHP subprocess.
     */
    private function executeWebAction(string $uri, array $post, array $session = []): int
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ks_rost_');
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
                    'permissions' => ['team.members.manage', 'team.manage', 'team.view'],
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
     * Helper to create an isolated test team.
     */
    private function createTestTeam(int $orgId, string $name = 'Roster Test Team'): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO teams (organization_id, sport_id, name, team_code, status, created_at, updated_at)
            VALUES (:org_id, 1, :name, :code, 'active', NOW(), NOW())
        ");
        $code = 'TM-' . substr(uniqid(), -6);
        $stmt->execute([
            ':org_id' => $orgId,
            ':name' => $name . ' ' . uniqid(),
            ':code' => $code
        ]);
        $teamId = (int)$this->pdo->lastInsertId();
        $this->createdTeamIds[] = $teamId;
        return $teamId;
    }

    /**
     * Helper to create an isolated test athlete.
     */
    private function createTestAthlete(int $orgId, string $status = 'active'): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO athletes (organization_id, athlete_code, first_name, last_name, status, created_at, updated_at)
            VALUES (:org_id, :code, :fn, :ln, :status, NOW(), NOW())
        ");
        $code = 'ATH-' . substr(uniqid(), -6);
        $stmt->execute([
            ':org_id' => $orgId,
            ':code' => $code,
            ':fn' => 'TestAth',
            ':ln' => uniqid(),
            ':status' => $status
        ]);
        $athId = (int)$this->pdo->lastInsertId();
        $this->createdAthleteIds[] = $athId;
        return $athId;
    }

    /**
     * TEST 1: Add athlete successfully via web action
     */
    public function testAddAthleteSuccess(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $athId = $this->createTestAthlete($this->orgId);

        $this->executeWebAction("/teams/{$teamId}/roster/add", [
            'athlete_id' => $athId,
            'jersey_number' => '10',
            'member_role' => 'captain'
        ]);

        $stmt = $this->pdo->prepare("
            SELECT * FROM team_members 
            WHERE team_id = :team_id AND athlete_id = :ath_id AND is_current = 1
        ");
        $stmt->execute([':team_id' => $teamId, ':ath_id' => $athId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return !empty($row) && $row['jersey_number'] === '10' && $row['member_role'] === 'captain';
    }

    /**
     * TEST 2: Add athlete appears as current team member in service
     */
    public function testAddAthleteAppearsAsCurrentMember(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $athId = $this->createTestAthlete($this->orgId);

        $this->executeWebAction("/teams/{$teamId}/roster/add", [
            'athlete_id' => $athId,
            'jersey_number' => '7',
            'member_role' => 'player'
        ]);

        $team = $this->service->getTeam($this->orgId, $teamId);
        $currentAthletes = $team['current_athletes'] ?? [];

        foreach ($currentAthletes as $ath) {
            if ((int)$ath['athlete_id'] === $athId) {
                return true;
            }
        }

        return false;
    }

    /**
     * TEST 3: Duplicate current membership is rejected or safely idempotent
     */
    public function testDuplicateCurrentMembershipHandling(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $athId = $this->createTestAthlete($this->orgId);

        // Add first time
        $this->executeWebAction("/teams/{$teamId}/roster/add", [
            'athlete_id' => $athId,
            'jersey_number' => '9',
            'member_role' => 'player'
        ]);

        // Attempt duplicate add
        $this->executeWebAction("/teams/{$teamId}/roster/add", [
            'athlete_id' => $athId,
            'jersey_number' => '9',
            'member_role' => 'player'
        ]);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM team_members 
            WHERE team_id = :team_id AND athlete_id = :ath_id AND is_current = 1
        ");
        $stmt->execute([':team_id' => $teamId, ':ath_id' => $athId]);
        $activeCount = (int)$stmt->fetchColumn();

        // Must NOT create a duplicate active membership row
        return ($activeCount === 1);
    }

    /**
     * TEST 4: Remove athlete successfully (is_current = 0, end_date recorded)
     */
    public function testRemoveAthleteSuccess(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $athId = $this->createTestAthlete($this->orgId);

        // Add athlete first
        $this->executeWebAction("/teams/{$teamId}/roster/add", [
            'athlete_id' => $athId,
            'jersey_number' => '11',
            'member_role' => 'player'
        ]);

        // Remove athlete
        $this->executeWebAction("/teams/{$teamId}/roster/{$athId}/remove", []);

        $stmt = $this->pdo->prepare("
            SELECT is_current, end_date FROM team_members 
            WHERE team_id = :team_id AND athlete_id = :ath_id
        ");
        $stmt->execute([':team_id' => $teamId, ':ath_id' => $athId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return !empty($row) && (int)$row['is_current'] === 0 && !empty($row['end_date']);
    }

    /**
     * TEST 5: Removed membership is no longer current in squad roster
     */
    public function testRemovedMembershipNoLongerCurrent(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $athId = $this->createTestAthlete($this->orgId);

        $this->executeWebAction("/teams/{$teamId}/roster/add", [
            'athlete_id' => $athId
        ]);

        $this->executeWebAction("/teams/{$teamId}/roster/{$athId}/remove", []);

        $team = $this->service->getTeam($this->orgId, $teamId);
        $currentAthletes = $team['current_athletes'] ?? [];

        foreach ($currentAthletes as $ath) {
            if ((int)$ath['athlete_id'] === $athId) {
                return false; // Failed if still present in current roster
            }
        }

        return true;
    }

    /**
     * TEST 6: Historical membership remains preserved where supported by schema
     */
    public function testHistoricalMembershipPreserved(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $athId = $this->createTestAthlete($this->orgId);

        $this->executeWebAction("/teams/{$teamId}/roster/add", [
            'athlete_id' => $athId,
            'member_role' => 'player'
        ]);

        $this->executeWebAction("/teams/{$teamId}/roster/{$athId}/remove", []);

        $team = $this->service->getTeam($this->orgId, $teamId);
        $historical = $team['historical_athletes'] ?? [];

        $foundHistorical = false;
        foreach ($historical as $ha) {
            if ((int)$ha['athlete_id'] === $athId) {
                $foundHistorical = true;
                break;
            }
        }

        // Verify athlete record itself in athletes table is NOT deleted
        $athCheck = $this->pdo->query("SELECT deleted_at FROM athletes WHERE id = {$athId}")->fetch(PDO::FETCH_ASSOC);
        $athleteIntact = ($athCheck && $athCheck['deleted_at'] === null);

        return $foundHistorical && $athleteIntact;
    }

    /**
     * TEST 7: Cross-tenant team access rejected
     */
    public function testCrossTenantTeamAccessRejected(): bool
    {
        // Team belongs to tenant 999
        $otherTeamId = $this->createTestTeam($this->otherOrgId, 'Other Tenant Team');
        $athId = $this->createTestAthlete($this->orgId);

        // Session belongs to tenant 1, attempts to add athlete to other tenant's team
        $this->executeWebAction("/teams/{$otherTeamId}/roster/add", [
            'athlete_id' => $athId
        ]);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM team_members WHERE team_id = :team_id");
        $stmt->execute([':team_id' => $otherTeamId]);
        $count = (int)$stmt->fetchColumn();

        return ($count === 0);
    }

    /**
     * TEST 8: Cross-tenant athlete access rejected
     */
    public function testCrossTenantAthleteAccessRejected(): bool
    {
        // Team in tenant 1
        $teamId = $this->createTestTeam($this->orgId);
        // Athlete in tenant 999
        $otherAthleteId = $this->createTestAthlete($this->otherOrgId);

        // Attempt to add tenant 999 athlete to tenant 1 team
        $this->executeWebAction("/teams/{$teamId}/roster/add", [
            'athlete_id' => $otherAthleteId,
            'organization_id' => $this->otherOrgId // Post tampering attempt
        ]);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM team_members WHERE team_id = :team_id AND athlete_id = :ath_id");
        $stmt->execute([':team_id' => $teamId, ':ath_id' => $otherAthleteId]);
        $count = (int)$stmt->fetchColumn();

        return ($count === 0);
    }

    /**
     * TEST 9: Unauthorized role cannot add athlete
     */
    public function testUnauthorizedRoleCannotAddAthlete(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $athId = $this->createTestAthlete($this->orgId);

        $unauthorizedSession = [
            'auth' => [
                'authenticated' => true,
                'user' => [
                    'id' => 999,
                    'email' => 'viewer@khelsutra.local',
                    'first_name' => 'Viewer',
                    'last_name' => 'Only',
                    'role_id' => 99,
                    'role' => ['name' => 'Viewer', 'slug' => 'viewer'],
                    'permissions' => ['team.view'], // Lacks team.members.manage
                    'status' => 'active'
                ],
                'organization' => [
                    'id' => $this->orgId,
                    'name' => 'Apex Sports Academy',
                    'code' => 'APEX'
                ]
            ]
        ];

        $this->executeWebAction("/teams/{$teamId}/roster/add", [
            'athlete_id' => $athId
        ], $unauthorizedSession);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM team_members WHERE team_id = :team_id AND athlete_id = :ath_id");
        $stmt->execute([':team_id' => $teamId, ':ath_id' => $athId]);
        $count = (int)$stmt->fetchColumn();

        return ($count === 0);
    }

    /**
     * TEST 10: Unauthorized role cannot remove athlete
     */
    public function testUnauthorizedRoleCannotRemoveAthlete(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $athId = $this->createTestAthlete($this->orgId);

        // Add as authorized user
        $this->executeWebAction("/teams/{$teamId}/roster/add", [
            'athlete_id' => $athId
        ]);

        $unauthorizedSession = [
            'auth' => [
                'authenticated' => true,
                'user' => [
                    'id' => 999,
                    'email' => 'viewer@khelsutra.local',
                    'first_name' => 'Viewer',
                    'last_name' => 'Only',
                    'role_id' => 99,
                    'role' => ['name' => 'Viewer', 'slug' => 'viewer'],
                    'permissions' => ['team.view'], // Lacks team.members.manage
                    'status' => 'active'
                ],
                'organization' => [
                    'id' => $this->orgId,
                    'name' => 'Apex Sports Academy',
                    'code' => 'APEX'
                ]
            ]
        ];

        // Attempt remove with unauthorized session
        $this->executeWebAction("/teams/{$teamId}/roster/{$athId}/remove", [], $unauthorizedSession);

        // Membership must still be active (is_current = 1)
        $stmt = $this->pdo->prepare("SELECT is_current FROM team_members WHERE team_id = :team_id AND athlete_id = :ath_id");
        $stmt->execute([':team_id' => $teamId, ':ath_id' => $athId]);
        $isCurrent = (int)$stmt->fetchColumn();

        return ($isCurrent === 1);
    }

    /**
     * TEST 11: Audit log is created for both add and remove actions
     */
    public function testAuditLogCreated(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $athId = $this->createTestAthlete($this->orgId);

        $this->executeWebAction("/teams/{$teamId}/roster/add", [
            'athlete_id' => $athId
        ]);

        $addLog = (int)$this->pdo->query("
            SELECT COUNT(*) FROM audit_logs 
            WHERE action = 'TEAM_MEMBER_ADD' AND description LIKE '%team #{$teamId}%'
        ")->fetchColumn();

        $this->executeWebAction("/teams/{$teamId}/roster/{$athId}/remove", []);

        $removeLog = (int)$this->pdo->query("
            SELECT COUNT(*) FROM audit_logs 
            WHERE action = 'TEAM_MEMBER_REMOVE' AND description LIKE '%team #{$teamId}%'
        ")->fetchColumn();

        return ($addLog > 0 && $removeLog > 0);
    }

    /**
     * TEST 12: Invalid athlete/team IDs handled safely
     */
    public function testInvalidIdsHandledSafely(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);

        // Invalid athlete ID on existing team
        $exit1 = $this->executeWebAction("/teams/{$teamId}/roster/add", [
            'athlete_id' => 999999
        ]);

        // Invalid team ID
        $exit2 = $this->executeWebAction("/teams/999999/roster/add", [
            'athlete_id' => 1
        ]);

        // Remove non-existent athlete
        $exit3 = $this->executeWebAction("/teams/{$teamId}/roster/999999/remove", []);

        // None should crash or exit with PHP fatal error
        return ($exit1 === 0 && $exit2 === 0 && $exit3 === 0);
    }

    /**
     * Run all tests in this suite.
     */
    public function runAll(): bool
    {
        $tests = [
            'testAddAthleteSuccess' => $this->testAddAthleteSuccess(),
            'testAddAthleteAppearsAsCurrentMember' => $this->testAddAthleteAppearsAsCurrentMember(),
            'testDuplicateCurrentMembershipHandling' => $this->testDuplicateCurrentMembershipHandling(),
            'testRemoveAthleteSuccess' => $this->testRemoveAthleteSuccess(),
            'testRemovedMembershipNoLongerCurrent' => $this->testRemovedMembershipNoLongerCurrent(),
            'testHistoricalMembershipPreserved' => $this->testHistoricalMembershipPreserved(),
            'testCrossTenantTeamAccessRejected' => $this->testCrossTenantTeamAccessRejected(),
            'testCrossTenantAthleteAccessRejected' => $this->testCrossTenantAthleteAccessRejected(),
            'testUnauthorizedRoleCannotAddAthlete' => $this->testUnauthorizedRoleCannotAddAthlete(),
            'testUnauthorizedRoleCannotRemoveAthlete' => $this->testUnauthorizedRoleCannotRemoveAthlete(),
            'testAuditLogCreated' => $this->testAuditLogCreated(),
            'testInvalidIdsHandledSafely' => $this->testInvalidIdsHandledSafely(),
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
    echo "Running TeamRosterWebActionTest suite..." . PHP_EOL;
    $test = new TeamRosterWebActionTest();
    $passed = $test->runAll();
    exit($passed ? 0 : 1);
}
