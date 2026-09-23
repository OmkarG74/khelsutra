<?php

namespace Tests\Feature;

use App\Services\BaseService;
use App\Services\Tournament\TournamentService;
use PDO;

class FixtureMatchWebActionTest
{
    private int $orgId = 1; // Apex Sports Academy
    private int $otherOrgId = 2; // Separate tenant
    private int $userId = 5; // Sports Admin user
    private PDO $pdo;
    private TournamentService $service;

    public function __construct()
    {
        $this->pdo = BaseService::getDatabaseConnection();
        $this->service = new TournamentService($this->pdo);
    }

    /**
     * Helper to execute web_actions.php in an isolated PHP subprocess.
     */
    private function executeWebAction(string $uri, array $post, array $session = []): int
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ks_fix_');
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
                    'permissions' => ['tournament.create', 'tournament.update', 'tournament.manage', 'tournament.view'],
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
     * Helper to create a dedicated test tournament with enrolled teams.
     */
    private function createTournamentWithTeams(): array
    {
        // Find 2 active teams for org 1
        $tStmt = $this->pdo->prepare("SELECT id FROM teams WHERE organization_id = :org AND deleted_at IS NULL LIMIT 2");
        $tStmt->execute([':org' => $this->orgId]);
        $teams = $tStmt->fetchAll(PDO::FETCH_COLUMN);

        // Find a valid venue for org 1
        $vStmt = $this->pdo->prepare("SELECT id FROM venues WHERE organization_id = :org AND deleted_at IS NULL LIMIT 1");
        $vStmt->execute([':org' => $this->orgId]);
        $venueId = (int)$vStmt->fetchColumn();

        $tData = [
            'name' => 'Test Championship ' . uniqid(),
            'sport_id' => 1,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+10 days')),
            'status' => 'ongoing',
            'team_ids' => [(int)$teams[0], (int)$teams[1]],
            'venue_id' => $venueId,
        ];

        $created = $this->service->createTournament($this->orgId, $tData, $this->userId);
        $tournId = (int)$created['id'];

        $this->service->addTeam($this->orgId, $tournId, (int)$teams[0], $this->userId);
        $this->service->addTeam($this->orgId, $tournId, (int)$teams[1], $this->userId);

        return [
            'tournament_id' => $tournId,
            'home_team_id' => (int)$teams[0],
            'away_team_id' => (int)$teams[1],
            'venue_id' => $venueId,
        ];
    }

    /**
     * TEST 1: Fixture Creation Success
     */
    public function testCreateFixtureSuccess(): bool
    {
        $context = $this->createTournamentWithTeams();
        $tournId = $context['tournament_id'];

        $post = [
            'round_name' => 'Quarter Final 1',
            'home_team_id' => $context['home_team_id'],
            'away_team_id' => $context['away_team_id'],
            'venue_id' => $context['venue_id'],
            'scheduled_date' => date('Y-m-d', strtotime('+2 days')),
            'scheduled_start_time' => '16:30',
        ];

        $this->executeWebAction("/tournaments/{$tournId}/fixtures/create", $post);

        // Verify fixture exists in database
        $stmt = $this->pdo->prepare("
            SELECT f.*, m.id as match_id, m.status as match_status
            FROM fixtures f
            JOIN matches m ON f.id = m.fixture_id
            WHERE f.tournament_id = :t_id AND f.round_name = 'Quarter Final 1'
            LIMIT 1
        ");
        $stmt->execute([':t_id' => $tournId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return false;
        if ((int)$row['home_team_id'] !== $context['home_team_id']) return false;
        if ((int)$row['away_team_id'] !== $context['away_team_id']) return false;
        if ($row['status'] !== 'scheduled') return false;
        if ($row['match_status'] !== 'scheduled') return false;

        // Clean up
        $this->service->deleteTournament($this->orgId, $tournId, $this->userId);

        return true;
    }

    /**
     * TEST 2: Invalid Fixture Validation
     * Rejects identical teams, non-enrolled teams, and missing dates.
     */
    public function testCreateFixtureValidation(): bool
    {
        $context = $this->createTournamentWithTeams();
        $tournId = $context['tournament_id'];
        $countBefore = (int)$this->pdo->query("SELECT COUNT(*) FROM fixtures WHERE tournament_id = {$tournId}")->fetchColumn();

        // 1. Identical home & away teams
        $this->executeWebAction("/tournaments/{$tournId}/fixtures/create", [
            'round_name' => 'Invalid Identical Match',
            'home_team_id' => $context['home_team_id'],
            'away_team_id' => $context['home_team_id'], // SAME TEAM
            'venue_id' => $context['venue_id'],
            'scheduled_date' => date('Y-m-d'),
            'scheduled_start_time' => '15:00',
        ]);

        // 2. Missing date
        $this->executeWebAction("/tournaments/{$tournId}/fixtures/create", [
            'round_name' => 'Invalid No Date',
            'home_team_id' => $context['home_team_id'],
            'away_team_id' => $context['away_team_id'],
            'venue_id' => $context['venue_id'],
            'scheduled_date' => '',
            'scheduled_start_time' => '15:00',
        ]);

        // 3. Non-enrolled team (team 999999 or a team not in tournament_teams)
        $this->executeWebAction("/tournaments/{$tournId}/fixtures/create", [
            'round_name' => 'Invalid Team Match',
            'home_team_id' => $context['home_team_id'],
            'away_team_id' => 999999,
            'venue_id' => $context['venue_id'],
            'scheduled_date' => date('Y-m-d'),
            'scheduled_start_time' => '15:00',
        ]);

        $countAfter = (int)$this->pdo->query("SELECT COUNT(*) FROM fixtures WHERE tournament_id = {$tournId}")->fetchColumn();

        $this->service->deleteTournament($this->orgId, $tournId, $this->userId);

        return ($countBefore === $countAfter);
    }

    /**
     * TEST 3: Tenant Isolation in Fixture Creation
     * Rejects venue from another tenant and blocks POSTed organization_id spoofing.
     */
    public function testCreateFixtureTenantIsolation(): bool
    {
        $context = $this->createTournamentWithTeams();
        $tournId = $context['tournament_id'];

        // Get venue belonging to org 2
        $vStmt = $this->pdo->prepare("SELECT id FROM venues WHERE organization_id = :org AND deleted_at IS NULL LIMIT 1");
        $vStmt->execute([':org' => $this->otherOrgId]);
        $otherOrgVenueId = (int)$vStmt->fetchColumn();

        if ($otherOrgVenueId > 0) {
            $countBefore = (int)$this->pdo->query("SELECT COUNT(*) FROM fixtures WHERE tournament_id = {$tournId}")->fetchColumn();

            $this->executeWebAction("/tournaments/{$tournId}/fixtures/create", [
                'round_name' => 'Cross Tenant Venue Match',
                'home_team_id' => $context['home_team_id'],
                'away_team_id' => $context['away_team_id'],
                'venue_id' => $otherOrgVenueId,
                'scheduled_date' => date('Y-m-d'),
                'scheduled_start_time' => '15:00',
                'organization_id' => $this->otherOrgId, // Spoofed org
            ]);

            $countAfter = (int)$this->pdo->query("SELECT COUNT(*) FROM fixtures WHERE tournament_id = {$tournId}")->fetchColumn();
            if ($countBefore !== $countAfter) {
                $this->service->deleteTournament($this->orgId, $tournId, $this->userId);
                return false;
            }
        }

        $this->service->deleteTournament($this->orgId, $tournId, $this->userId);
        return true;
    }

    /**
     * TEST 4: Match Result Submission (Home Win)
     */
    public function testMatchResultSubmissionHomeWin(): bool
    {
        $context = $this->createTournamentWithTeams();
        $tournId = $context['tournament_id'];

        // Create fixture
        $fixture = $this->service->createFixture($this->orgId, $tournId, [
            'round_name' => 'Match 1',
            'home_team_id' => $context['home_team_id'],
            'away_team_id' => $context['away_team_id'],
            'venue_id' => $context['venue_id'],
            'scheduled_date' => date('Y-m-d'),
            'scheduled_start_time' => '14:00',
        ], $this->userId);
        $fixtureId = (int)$fixture['id'];

        // Submit Result: Home 3 - Away 1
        $this->executeWebAction("/tournaments/{$tournId}/matches/{$fixtureId}/result", [
            'home_score' => '3',
            'away_score' => '1',
        ]);

        // Verify match record
        $stmt = $this->pdo->prepare("SELECT * FROM matches WHERE fixture_id = :fix_id LIMIT 1");
        $stmt->execute([':fix_id' => $fixtureId]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$match) return false;
        if ($match['status'] !== 'completed') return false;
        if ((int)$match['home_score'] !== 3) return false;
        if ((int)$match['away_score'] !== 1) return false;
        if ((int)$match['winner_team_id'] !== $context['home_team_id']) return false;
        if ($match['result_type'] !== 'home_win') return false;

        // Verify fixture status
        $fStatus = $this->pdo->query("SELECT status FROM fixtures WHERE id = {$fixtureId}")->fetchColumn();
        if ($fStatus !== 'completed') return false;

        $this->service->deleteTournament($this->orgId, $tournId, $this->userId);
        return true;
    }

    /**
     * TEST 5: Match Result Submission (Draw)
     */
    public function testMatchResultSubmissionDraw(): bool
    {
        $context = $this->createTournamentWithTeams();
        $tournId = $context['tournament_id'];

        // Create fixture
        $fixture = $this->service->createFixture($this->orgId, $tournId, [
            'round_name' => 'Match 2',
            'home_team_id' => $context['home_team_id'],
            'away_team_id' => $context['away_team_id'],
            'venue_id' => $context['venue_id'],
            'scheduled_date' => date('Y-m-d'),
            'scheduled_start_time' => '17:00',
        ], $this->userId);
        $fixtureId = (int)$fixture['id'];

        // Submit Result: Home 2 - Away 2 (Draw)
        $this->executeWebAction("/tournaments/{$tournId}/matches/{$fixtureId}/result", [
            'home_score' => '2',
            'away_score' => '2',
        ]);

        $stmt = $this->pdo->prepare("SELECT * FROM matches WHERE fixture_id = :fix_id LIMIT 1");
        $stmt->execute([':fix_id' => $fixtureId]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$match) return false;
        if ($match['status'] !== 'completed') return false;
        if ((int)$match['home_score'] !== 2 || (int)$match['away_score'] !== 2) return false;
        if ($match['result_type'] !== 'draw') return false;
        if (!empty($match['winner_team_id'])) return false;

        $this->service->deleteTournament($this->orgId, $tournId, $this->userId);
        return true;
    }

    /**
     * TEST 6: Validation Rejection of Negative and Non-Integer Scores
     */
    public function testMatchResultScoreValidation(): bool
    {
        $context = $this->createTournamentWithTeams();
        $tournId = $context['tournament_id'];

        $fixture = $this->service->createFixture($this->orgId, $tournId, [
            'round_name' => 'Match 3',
            'home_team_id' => $context['home_team_id'],
            'away_team_id' => $context['away_team_id'],
            'venue_id' => $context['venue_id'],
            'scheduled_date' => date('Y-m-d'),
            'scheduled_start_time' => '18:00',
        ], $this->userId);
        $fixtureId = (int)$fixture['id'];

        // Attempt 1: Negative score
        $this->executeWebAction("/tournaments/{$tournId}/matches/{$fixtureId}/result", [
            'home_score' => '-1',
            'away_score' => '3',
        ]);

        // Attempt 2: Non-integer string
        $this->executeWebAction("/tournaments/{$tournId}/matches/{$fixtureId}/result", [
            'home_score' => 'abc',
            'away_score' => '2',
        ]);

        // Verify match remains 'scheduled'
        $mStatus = $this->pdo->query("SELECT status FROM matches WHERE fixture_id = {$fixtureId}")->fetchColumn();

        $this->service->deleteTournament($this->orgId, $tournId, $this->userId);

        return ($mStatus === 'scheduled');
    }

    /**
     * TEST 7: Standings Update Trigger
     * Recording match result must automatically recalculate tournament standings.
     */
    public function testStandingsUpdateTrigger(): bool
    {
        $context = $this->createTournamentWithTeams();
        $tournId = $context['tournament_id'];
        $homeId = $context['home_team_id'];
        $awayId = $context['away_team_id'];

        $fixture = $this->service->createFixture($this->orgId, $tournId, [
            'round_name' => 'Group Match Standings Test',
            'home_team_id' => $homeId,
            'away_team_id' => $awayId,
            'venue_id' => $context['venue_id'],
            'scheduled_date' => date('Y-m-d'),
            'scheduled_start_time' => '15:00',
        ], $this->userId);
        $fixtureId = (int)$fixture['id'];

        // Submit Home 4 - Away 1
        $this->executeWebAction("/tournaments/{$tournId}/matches/{$fixtureId}/result", [
            'home_score' => '4',
            'away_score' => '1',
        ]);

        // Verify tournament_standings table
        $stmt = $this->pdo->prepare("SELECT * FROM tournament_standings WHERE tournament_id = :t_id ORDER BY points DESC");
        $stmt->execute([':t_id' => $tournId]);
        $standings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($standings) < 2) return false;

        $top = $standings[0];
        $bottom = $standings[1];

        // Winner checks
        if ((int)$top['team_id'] !== $homeId) return false;
        if ((int)$top['played'] !== 1) return false;
        if ((int)$top['won'] !== 1) return false;
        if ((int)$top['points'] !== 3) return false;
        if ((int)$top['scored'] !== 4) return false;
        if ((int)$top['conceded'] !== 1) return false;
        if ((int)$top['difference'] !== 3) return false;

        // Loser checks
        if ((int)$bottom['team_id'] !== $awayId) return false;
        if ((int)$bottom['played'] !== 1) return false;
        if ((int)$bottom['lost'] !== 1) return false;
        if ((int)$bottom['points'] !== 0) return false;
        if ((int)$bottom['scored'] !== 1) return false;
        if ((int)$bottom['conceded'] !== 4) return false;
        if ((int)$bottom['difference'] !== -3) return false;

        $this->service->deleteTournament($this->orgId, $tournId, $this->userId);
        return true;
    }

    /**
     * TEST 8: RBAC Unauthorized Role Blocked
     */
    public function testRBACUnauthorizedBlocked(): bool
    {
        $context = $this->createTournamentWithTeams();
        $tournId = $context['tournament_id'];

        $fixture = $this->service->createFixture($this->orgId, $tournId, [
            'round_name' => 'RBAC Match',
            'home_team_id' => $context['home_team_id'],
            'away_team_id' => $context['away_team_id'],
            'venue_id' => $context['venue_id'],
            'scheduled_date' => date('Y-m-d'),
            'scheduled_start_time' => '15:00',
        ], $this->userId);
        $fixtureId = (int)$fixture['id'];

        // Athlete session without tournament.manage or tournament.update
        $unauthorizedSession = [
            'auth' => [
                'authenticated' => true,
                'user' => [
                    'id' => 999,
                    'role_id' => 5, // Athlete role
                    'role' => ['name' => 'Athlete', 'slug' => 'athlete'],
                    'permissions' => ['tournament.view'], // view only
                    'status' => 'active'
                ],
                'organization' => [
                    'id' => $this->orgId,
                    'name' => 'Apex Sports Academy',
                    'code' => 'APEX'
                ]
            ]
        ];

        // Attempt result update with unauthorized session
        $this->executeWebAction("/tournaments/{$tournId}/matches/{$fixtureId}/result", [
            'home_score' => '2',
            'away_score' => '0',
        ], $unauthorizedSession);

        $mStatus = $this->pdo->query("SELECT status FROM matches WHERE fixture_id = {$fixtureId}")->fetchColumn();

        $this->service->deleteTournament($this->orgId, $tournId, $this->userId);

        return ($mStatus === 'scheduled');
    }

    /**
     * Run all tests in this suite.
     */
    public function runAll(): bool
    {
        $tests = [
            'testCreateFixtureSuccess' => $this->testCreateFixtureSuccess(),
            'testCreateFixtureValidation' => $this->testCreateFixtureValidation(),
            'testCreateFixtureTenantIsolation' => $this->testCreateFixtureTenantIsolation(),
            'testMatchResultSubmissionHomeWin' => $this->testMatchResultSubmissionHomeWin(),
            'testMatchResultSubmissionDraw' => $this->testMatchResultSubmissionDraw(),
            'testMatchResultScoreValidation' => $this->testMatchResultScoreValidation(),
            'testStandingsUpdateTrigger' => $this->testStandingsUpdateTrigger(),
            'testRBACUnauthorizedBlocked' => $this->testRBACUnauthorizedBlocked(),
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
    echo "Running FixtureMatchWebActionTest suite..." . PHP_EOL;
    $test = new FixtureMatchWebActionTest();
    $passed = $test->runAll();
    exit($passed ? 0 : 1);
}
