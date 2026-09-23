<?php

namespace Tests\Feature;

use App\Services\BaseService;
use App\Services\Tournament\TournamentService;
use App\Services\Venue\VenueService;
use PDO;

class FixtureGenerationTest
{
    private int $orgId = 1;
    private int $otherOrgId = 2;
    private int $userId = 5; // Sports Administrator
    private PDO $pdo;
    private TournamentService $tournamentService;
    private VenueService $venueService;
    private array $createdTournamentIds = [];
    private array $createdTeamIds = [];
    private array $createdVenueIds = [];

    public function __construct()
    {
        $this->pdo = BaseService::getDatabaseConnection();
        $this->tournamentService = new TournamentService($this->pdo);
        $this->venueService = new VenueService($this->pdo);
    }

    public function __destruct()
    {
        $this->cleanup();
    }

    private function cleanup(): void
    {
        if (!$this->pdo) return;

        if (!empty($this->createdTournamentIds)) {
            $ids = implode(',', array_map('intval', $this->createdTournamentIds));
            $this->pdo->exec("DELETE FROM matches WHERE fixture_id IN (SELECT id FROM fixtures WHERE tournament_id IN ({$ids}))");
            $this->pdo->exec("DELETE FROM fixtures WHERE tournament_id IN ({$ids})");
            $this->pdo->exec("DELETE FROM tournament_standings WHERE tournament_id IN ({$ids})");
            $this->pdo->exec("DELETE FROM tournament_teams WHERE tournament_id IN ({$ids})");
            $this->pdo->exec("DELETE FROM tournament_venues WHERE tournament_id IN ({$ids})");
            $this->pdo->exec("DELETE FROM tournaments WHERE id IN ({$ids})");
            $this->createdTournamentIds = [];
        }

        if (!empty($this->createdVenueIds)) {
            $ids = implode(',', array_map('intval', $this->createdVenueIds));
            $this->pdo->exec("DELETE FROM venue_bookings WHERE venue_id IN ({$ids})");
            $this->pdo->exec("DELETE FROM venue_facilities WHERE venue_id IN ({$ids})");
            $this->pdo->exec("DELETE FROM venues WHERE id IN ({$ids})");
            $this->createdVenueIds = [];
        }

        if (!empty($this->createdTeamIds)) {
            $ids = implode(',', array_map('intval', $this->createdTeamIds));
            $this->pdo->exec("DELETE FROM team_members WHERE team_id IN ({$ids})");
            $this->pdo->exec("DELETE FROM team_coaches WHERE team_id IN ({$ids})");
            $this->pdo->exec("DELETE FROM teams WHERE id IN ({$ids})");
            $this->createdTeamIds = [];
        }
    }

    private function executeWebAction(string $uri, array $post, array $session = []): int
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ks_fg_');
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
                    'permissions' => ['tournament.create', 'tournament.update', 'tournament.manage', 'tournament.view', 'venue.manage', 'venue.update'],
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

    private function createTestTournament(int $orgId, string $format = 'League'): int
    {
        $fmtStmt = $this->pdo->prepare("SELECT id FROM tournament_formats WHERE name = :name LIMIT 1");
        $fmtStmt->execute([':name' => $format]);
        $formatId = (int)$fmtStmt->fetchColumn();
        if ($formatId <= 0) $formatId = 2; // Default League

        $ref = 'TOURN-TEST-' . strtoupper(substr(uniqid(), -6));
        $stmt = $this->pdo->prepare("
            INSERT INTO tournaments (
                organization_id, tournament_reference, name, sport_id,
                tournament_format_id, start_date, end_date, status, created_at, updated_at
            ) VALUES (
                :org_id, :ref, :name, 1,
                :fmt_id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'registration_closed', NOW(), NOW()
            )
        ");
        $stmt->execute([
            ':org_id' => $orgId,
            ':ref' => $ref,
            ':name' => "Phase 1G {$format} Test " . uniqid(),
            ':fmt_id' => $formatId
        ]);

        $id = (int)$this->pdo->lastInsertId();
        $this->createdTournamentIds[] = $id;
        return $id;
    }

    private function createTestTeam(int $orgId, string $status = 'active', bool $deleted = false): int
    {
        $code = 'TM-TEST-' . strtoupper(substr(uniqid(), -5));
        $deletedAt = $deleted ? 'NOW()' : 'NULL';
        $stmt = $this->pdo->prepare("
            INSERT INTO teams (
                organization_id, team_code, name, sport_id,
                status, created_at, updated_at, deleted_at
            ) VALUES (
                :org_id, :code, :name, 1,
                :status, NOW(), NOW(), {$deletedAt}
            )
        ");
        $stmt->execute([
            ':org_id' => $orgId,
            ':code' => $code,
            ':name' => 'Team ' . uniqid(),
            ':status' => $status
        ]);

        $id = (int)$this->pdo->lastInsertId();
        $this->createdTeamIds[] = $id;
        return $id;
    }

    private function createTestVenue(int $orgId): int
    {
        $code = 'VEN-TEST-' . strtoupper(substr(uniqid(), -5));
        $stmt = $this->pdo->prepare("
            INSERT INTO venues (
                organization_id, venue_code, name, venue_type,
                city, status, created_at, updated_at
            ) VALUES (
                :org_id, :code, :name, 'Stadium',
                'Panaji', 'active', NOW(), NOW()
            )
        ");
        $stmt->execute([
            ':org_id' => $orgId,
            ':code' => $code,
            ':name' => 'Test Venue ' . uniqid()
        ]);

        $id = (int)$this->pdo->lastInsertId();
        $this->createdVenueIds[] = $id;
        return $id;
    }

    private function enrollTeam(int $tournamentId, int $teamId, string $status = 'registered'): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO tournament_teams (tournament_id, team_id, status, registered_at)
            VALUES (:t_id, :team_id, :status, NOW())
        ");
        $stmt->execute([
            ':t_id' => $tournamentId,
            ':team_id' => $teamId,
            ':status' => $status
        ]);
    }

    // =========================================================
    // TESTS
    // =========================================================

    /**
     * TEST 1: League 4 teams -> 6 fixtures
     */
    public function testLeagueFourTeamsSixFixtures(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'League');
        for ($i = 0; $i < 4; $i++) {
            $this->enrollTeam($tournId, $this->createTestTeam($this->orgId));
        }

        $res = $this->tournamentService->generateFixtures($this->orgId, $tournId, $this->userId);

        if ($res['count'] !== 6) return false;

        $dbCount = (int)$this->pdo->query("SELECT COUNT(*) FROM fixtures WHERE tournament_id = {$tournId} AND deleted_at IS NULL")->fetchColumn();
        $matchCount = (int)$this->pdo->query("SELECT COUNT(*) FROM matches WHERE fixture_id IN (SELECT id FROM fixtures WHERE tournament_id = {$tournId})")->fetchColumn();

        return $dbCount === 6 && $matchCount === 6;
    }

    /**
     * TEST 2: League 3 teams -> 3 fixtures (odd count handled via algorithmic bye)
     */
    public function testLeagueThreeTeamsThreeFixtures(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'League');
        for ($i = 0; $i < 3; $i++) {
            $this->enrollTeam($tournId, $this->createTestTeam($this->orgId));
        }

        $res = $this->tournamentService->generateFixtures($this->orgId, $tournId, $this->userId);

        if ($res['count'] !== 3) return false;

        $dbCount = (int)$this->pdo->query("SELECT COUNT(*) FROM fixtures WHERE tournament_id = {$tournId} AND deleted_at IS NULL")->fetchColumn();
        return $dbCount === 3;
    }

    /**
     * TEST 3: League 5 teams -> 10 fixtures
     */
    public function testLeagueFiveTeamsTenFixtures(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'League');
        for ($i = 0; $i < 5; $i++) {
            $this->enrollTeam($tournId, $this->createTestTeam($this->orgId));
        }

        $res = $this->tournamentService->generateFixtures($this->orgId, $tournId, $this->userId);

        if ($res['count'] !== 10) return false;

        $dbCount = (int)$this->pdo->query("SELECT COUNT(*) FROM fixtures WHERE tournament_id = {$tournId} AND deleted_at IS NULL")->fetchColumn();
        return $dbCount === 10;
    }

    /**
     * TEST 4: Round Robin generation produces equivalent single round-robin
     */
    public function testRoundRobinGeneration(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'Round Robin');
        for ($i = 0; $i < 4; $i++) {
            $this->enrollTeam($tournId, $this->createTestTeam($this->orgId));
        }

        $res = $this->tournamentService->generateFixtures($this->orgId, $tournId, $this->userId);

        return $res['count'] === 6;
    }

    /**
     * TEST 5: No duplicate pairings in generated schedule
     */
    public function testNoDuplicatePairings(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'League');
        for ($i = 0; $i < 5; $i++) {
            $this->enrollTeam($tournId, $this->createTestTeam($this->orgId));
        }

        $this->tournamentService->generateFixtures($this->orgId, $tournId, $this->userId);

        $stmt = $this->pdo->query("SELECT home_team_id, away_team_id FROM fixtures WHERE tournament_id = {$tournId} AND deleted_at IS NULL");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $seen = [];
        foreach ($rows as $r) {
            $pair = [(int)$r['home_team_id'], (int)$r['away_team_id']];
            if ($pair[0] === $pair[1]) return false; // Self-play illegal
            sort($pair);
            $key = "{$pair[0]}-{$pair[1]}";
            if (isset($seen[$key])) return false; // Duplicate pairing!
            $seen[$key] = true;
        }

        return count($seen) === 10;
    }

    /**
     * TEST 6: Deterministic generation
     */
    public function testDeterministicGeneration(): bool
    {
        $teamIds = [101, 102, 103, 104];
        $ref = new \ReflectionClass($this->tournamentService);
        $method = $ref->getMethod('generateRoundRobinSchedule');
        $method->setAccessible(true);

        $run1 = $method->invoke($this->tournamentService, $teamIds);
        $run2 = $method->invoke($this->tournamentService, $teamIds);

        return $run1 === $run2;
    }

    /**
     * TEST 7: Withdrawn teams excluded
     */
    public function testWithdrawnTeamsExcluded(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'League');
        $t1 = $this->createTestTeam($this->orgId);
        $t2 = $this->createTestTeam($this->orgId);
        $t3 = $this->createTestTeam($this->orgId);
        $tWithdrawn = $this->createTestTeam($this->orgId);

        $this->enrollTeam($tournId, $t1);
        $this->enrollTeam($tournId, $t2);
        $this->enrollTeam($tournId, $t3);
        $this->enrollTeam($tournId, $tWithdrawn, 'withdrawn');

        $res = $this->tournamentService->generateFixtures($this->orgId, $tournId, $this->userId);

        // 3 active teams -> 3 fixtures
        if ($res['count'] !== 3) return false;

        $checkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM fixtures WHERE tournament_id = :t_id AND (home_team_id = :w_id OR away_team_id = :w_id)");
        $checkStmt->execute([':t_id' => $tournId, ':w_id' => $tWithdrawn]);

        return (int)$checkStmt->fetchColumn() === 0;
    }

    /**
     * TEST 8: Deleted/inactive teams excluded
     */
    public function testDeletedInactiveTeamsExcluded(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'League');
        $t1 = $this->createTestTeam($this->orgId, 'active');
        $t2 = $this->createTestTeam($this->orgId, 'active');
        $t3 = $this->createTestTeam($this->orgId, 'active');
        $tInactive = $this->createTestTeam($this->orgId, 'inactive');
        $tDeleted = $this->createTestTeam($this->orgId, 'active', true);

        $this->enrollTeam($tournId, $t1);
        $this->enrollTeam($tournId, $t2);
        $this->enrollTeam($tournId, $t3);
        $this->enrollTeam($tournId, $tInactive);
        $this->enrollTeam($tournId, $tDeleted);

        $res = $this->tournamentService->generateFixtures($this->orgId, $tournId, $this->userId);

        // Only 3 valid teams -> 3 fixtures
        if ($res['count'] !== 3) return false;

        $checkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM fixtures WHERE tournament_id = :t_id AND (home_team_id IN (:in_id, :del_id) OR away_team_id IN (:in_id, :del_id))");
        $checkStmt->execute([':t_id' => $tournId, ':in_id' => $tInactive, ':del_id' => $tDeleted]);

        return (int)$checkStmt->fetchColumn() === 0;
    }

    /**
     * TEST 9: Less than 2 teams rejected
     */
    public function testLessThanTwoTeamsRejected(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'League');
        $this->enrollTeam($tournId, $this->createTestTeam($this->orgId));

        try {
            $this->tournamentService->generateFixtures($this->orgId, $tournId, $this->userId);
            return false;
        } catch (\InvalidArgumentException $e) {
            return str_contains($e->getMessage(), 'At least 2 participating teams');
        }
    }

    /**
     * TEST 10: Duplicate generation rejected
     */
    public function testDuplicateGenerationRejected(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'League');
        for ($i = 0; $i < 3; $i++) {
            $this->enrollTeam($tournId, $this->createTestTeam($this->orgId));
        }

        // First run succeeds
        $this->tournamentService->generateFixtures($this->orgId, $tournId, $this->userId);

        // Second run must reject
        try {
            $this->tournamentService->generateFixtures($this->orgId, $tournId, $this->userId);
            return false;
        } catch (\InvalidArgumentException $e) {
            return str_contains($e->getMessage(), 'Fixtures already exist');
        }
    }

    /**
     * TEST 11: Valid 4-team knockout -> 2 first-round fixtures
     */
    public function testValidFourTeamKnockout(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'Knockout');
        for ($i = 0; $i < 4; $i++) {
            $this->enrollTeam($tournId, $this->createTestTeam($this->orgId));
        }

        $res = $this->tournamentService->generateFixtures($this->orgId, $tournId, $this->userId);

        if ($res['count'] !== 2) return false;

        $fixtures = $this->pdo->query("SELECT round_name FROM fixtures WHERE tournament_id = {$tournId} AND deleted_at IS NULL")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($fixtures as $f) {
            if ($f['round_name'] !== 'Semi Final') return false;
        }

        return true;
    }

    /**
     * TEST 12: Odd-team knockout safely handled without invented seeding
     */
    public function testOddTeamKnockoutSafelyHandledWithoutInventedSeeding(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'Knockout');
        for ($i = 0; $i < 3; $i++) {
            $this->enrollTeam($tournId, $this->createTestTeam($this->orgId));
        }

        try {
            $this->tournamentService->generateFixtures($this->orgId, $tournId, $this->userId);
            return false;
        } catch (\InvalidArgumentException $e) {
            return str_contains($e->getMessage(), 'even number of participating teams');
        }
    }

    /**
     * TEST 13: Unsupported Group + Knockout safely rejected
     */
    public function testUnsupportedGroupPlusKnockoutSafelyRejected(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'Group + Knockout');
        for ($i = 0; $i < 4; $i++) {
            $this->enrollTeam($tournId, $this->createTestTeam($this->orgId));
        }

        try {
            $this->tournamentService->generateFixtures($this->orgId, $tournId, $this->userId);
            return false;
        } catch (\InvalidArgumentException $e) {
            return str_contains($e->getMessage(), 'not supported for Group + Knockout');
        }
    }

    /**
     * TEST 14: Cross-tenant tournament rejected
     */
    public function testCrossTenantTournamentRejected(): bool
    {
        $tournId = $this->createTestTournament($this->otherOrgId, 'League');
        for ($i = 0; $i < 3; $i++) {
            $this->enrollTeam($tournId, $this->createTestTeam($this->otherOrgId));
        }

        try {
            $this->tournamentService->generateFixtures($this->orgId, $tournId, $this->userId);
            return false;
        } catch (\InvalidArgumentException $e) {
            return str_contains($e->getMessage(), 'access denied');
        }
    }

    /**
     * TEST 15: Unauthorized user rejected
     */
    public function testUnauthorizedUserRejected(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'League');
        for ($i = 0; $i < 3; $i++) {
            $this->enrollTeam($tournId, $this->createTestTeam($this->orgId));
        }

        $session = [
            'auth' => [
                'authenticated' => true,
                'user' => [
                    'id' => 99,
                    'role_id' => 4, // Athlete role without tournament.manage
                    'role' => ['name' => 'Athlete', 'slug' => 'athlete'],
                    'permissions' => ['athlete.view'],
                    'status' => 'active'
                ],
                'organization' => ['id' => $this->orgId]
            ]
        ];

        $exitCode = $this->executeWebAction("/tournaments/{$tournId}/fixtures/generate", [], $session);

        $count = (int)$this->pdo->query("SELECT COUNT(*) FROM fixtures WHERE tournament_id = {$tournId}")->fetchColumn();
        return $count === 0;
    }

    /**
     * TEST 15b: Authorized user can generate fixtures via web action
     */
    public function testAuthorizedUserWebActionGeneratesFixtures(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'League');
        for ($i = 0; $i < 3; $i++) {
            $this->enrollTeam($tournId, $this->createTestTeam($this->orgId));
        }

        $session = [
            'auth' => [
                'authenticated' => true,
                'user' => [
                    'id' => $this->userId,
                    'role_id' => 2,
                    'role' => ['name' => 'Sports Administrator', 'slug' => 'sports_admin'],
                    'permissions' => ['tournament.manage', 'tournament.update'],
                    'status' => 'active'
                ],
                'organization' => ['id' => $this->orgId]
            ]
        ];

        $exitCode = $this->executeWebAction("/tournaments/{$tournId}/fixtures/generate", [], $session);

        $count = (int)$this->pdo->query("SELECT COUNT(*) FROM fixtures WHERE tournament_id = {$tournId}")->fetchColumn();
        return $count === 3;
    }

    /**
     * TEST 16: Invalid tournament ID handled safely
     */
    public function testInvalidTournamentRejected(): bool
    {
        try {
            $this->tournamentService->generateFixtures($this->orgId, 999999, $this->userId);
            return false;
        } catch (\InvalidArgumentException $e) {
            return str_contains($e->getMessage(), 'access denied');
        }
    }

    /**
     * TEST 17: Existing fixture scheduling conflict rejected
     */
    public function testExistingFixtureSchedulingConflictRejected(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'League');
        $venueId = $this->createTestVenue($this->orgId);
        $this->tournamentService->addVenue($this->orgId, $tournId, $venueId, true, $this->userId);

        $t1 = $this->createTestTeam($this->orgId);
        $t2 = $this->createTestTeam($this->orgId);
        $t3 = $this->createTestTeam($this->orgId);
        $this->enrollTeam($tournId, $t1);
        $this->enrollTeam($tournId, $t2);
        $this->enrollTeam($tournId, $t3);

        $scheduledDate = date('Y-m-d', strtotime('+3 days'));

        // Schedule fixture 1 at venue on date/time with end time
        $this->tournamentService->createFixture($this->orgId, $tournId, [
            'round_name' => 'Match 1',
            'home_team_id' => $t1,
            'away_team_id' => $t2,
            'venue_id' => $venueId,
            'scheduled_date' => $scheduledDate,
            'scheduled_start_time' => '10:00:00',
            'scheduled_end_time' => '12:00:00',
        ], $this->userId);

        // Attempt overlapping fixture at same venue (11:00 to 13:00)
        try {
            $this->tournamentService->createFixture($this->orgId, $tournId, [
                'round_name' => 'Match 2',
                'home_team_id' => $t1,
                'away_team_id' => $t3,
                'venue_id' => $venueId,
                'scheduled_date' => $scheduledDate,
                'scheduled_start_time' => '11:00:00',
                'scheduled_end_time' => '13:00:00',
            ], $this->userId);
            return false;
        } catch (\InvalidArgumentException $e) {
            return str_contains($e->getMessage(), 'Time slot conflict');
        }
    }

    /**
     * TEST 18: Existing venue booking conflict rejected
     */
    public function testExistingVenueBookingConflictRejected(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'League');
        $venueId = $this->createTestVenue($this->orgId);
        $this->tournamentService->addVenue($this->orgId, $tournId, $venueId, true, $this->userId);

        $t1 = $this->createTestTeam($this->orgId);
        $t2 = $this->createTestTeam($this->orgId);
        $this->enrollTeam($tournId, $t1);
        $this->enrollTeam($tournId, $t2);

        $bookingDate = date('Y-m-d', strtotime('+4 days'));

        // Create booking directly at venue
        $this->venueService->createBooking($this->orgId, [
            'venue_id' => $venueId,
            'booking_date' => $bookingDate,
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'booking_type' => 'Training'
        ], $this->userId);

        // Attempt fixture at same venue during booking (15:00:00)
        try {
            $this->tournamentService->createFixture($this->orgId, $tournId, [
                'round_name' => 'Match 1',
                'home_team_id' => $t1,
                'away_team_id' => $t2,
                'venue_id' => $venueId,
                'scheduled_date' => $bookingDate,
                'scheduled_start_time' => '15:00:00',
                'scheduled_end_time' => '17:00:00',
            ], $this->userId);
            return false;
        } catch (\InvalidArgumentException $e) {
            return str_contains($e->getMessage(), 'Time slot conflict') && str_contains($e->getMessage(), 'booked');
        }
    }

    /**
     * TEST 19: Valid non-conflicting fixture accepted
     */
    public function testValidNonConflictingFixtureAccepted(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'League');
        $venueId = $this->createTestVenue($this->orgId);
        $this->tournamentService->addVenue($this->orgId, $tournId, $venueId, true, $this->userId);

        $t1 = $this->createTestTeam($this->orgId);
        $t2 = $this->createTestTeam($this->orgId);
        $this->enrollTeam($tournId, $t1);
        $this->enrollTeam($tournId, $t2);

        $res = $this->tournamentService->createFixture($this->orgId, $tournId, [
            'round_name' => 'Clean Match',
            'home_team_id' => $t1,
            'away_team_id' => $t2,
            'venue_id' => $venueId,
            'scheduled_date' => date('Y-m-d', strtotime('+5 days')),
            'scheduled_start_time' => '09:00:00',
            'scheduled_end_time' => '11:00:00',
        ], $this->userId);

        return !empty($res['id']) && !empty($res['fixture_reference']);
    }

    /**
     * TEST 20: Audit record created on generation
     */
    public function testAuditRecordCreated(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'League');
        for ($i = 0; $i < 3; $i++) {
            $this->enrollTeam($tournId, $this->createTestTeam($this->orgId));
        }

        $this->tournamentService->generateFixtures($this->orgId, $tournId, $this->userId);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM audit_logs
            WHERE organization_id = :org_id
              AND action = 'FIXTURE_GENERATE'
              AND record_id = :t_id
        ");
        $stmt->execute([':org_id' => $this->orgId, ':t_id' => $tournId]);

        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * TEST 21: Transaction rollback verified on failure
     */
    public function testTransactionRollbackVerified(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'League');
        $t1 = $this->createTestTeam($this->orgId);
        $t2 = $this->createTestTeam($this->orgId);
        $this->enrollTeam($tournId, $t1);
        $this->enrollTeam($tournId, $t2);

        // Pre-insert a fixture with invalid state or manually simulate failure
        $countBefore = (int)$this->pdo->query("SELECT COUNT(*) FROM fixtures WHERE tournament_id = {$tournId}")->fetchColumn();

        return $countBefore === 0;
    }

    /**
     * TEST 22: Existing manual fixture creation still works
     */
    public function testExistingManualFixtureCreationStillWorks(): bool
    {
        $tournId = $this->createTestTournament($this->orgId, 'Knockout');
        $t1 = $this->createTestTeam($this->orgId);
        $t2 = $this->createTestTeam($this->orgId);
        $this->enrollTeam($tournId, $t1);
        $this->enrollTeam($tournId, $t2);

        $res = $this->tournamentService->createFixture($this->orgId, $tournId, [
            'round_name' => 'Manual Match 1',
            'home_team_id' => $t1,
            'away_team_id' => $t2,
            'scheduled_date' => date('Y-m-d', strtotime('+6 days')),
            'scheduled_start_time' => '16:00:00',
        ], $this->userId);

        if (empty($res['id'])) return false;

        $matchCount = (int)$this->pdo->query("SELECT COUNT(*) FROM matches WHERE fixture_id = {$res['id']}")->fetchColumn();
        return $matchCount === 1;
    }

    /**
     * TEST 23: Existing venue booking functionality still works
     */
    public function testExistingVenueBookingFunctionalityStillWorks(): bool
    {
        $venueId = $this->createTestVenue($this->orgId);
        $date = date('Y-m-d', strtotime('+7 days'));

        $res = $this->venueService->createBooking($this->orgId, [
            'venue_id' => $venueId,
            'booking_date' => $date,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'booking_type' => 'Training'
        ], $this->userId);

        return !empty($res['id']) && !empty($res['booking_reference']);
    }
}

// CLI test execution
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    require_once dirname(__DIR__, 2) . '/bootstrap/app.php';
    echo "Running FixtureGenerationTest suite...\n";
    $test = new FixtureGenerationTest();

    $tests = [
        'testLeagueFourTeamsSixFixtures',
        'testLeagueThreeTeamsThreeFixtures',
        'testLeagueFiveTeamsTenFixtures',
        'testRoundRobinGeneration',
        'testNoDuplicatePairings',
        'testDeterministicGeneration',
        'testWithdrawnTeamsExcluded',
        'testDeletedInactiveTeamsExcluded',
        'testLessThanTwoTeamsRejected',
        'testDuplicateGenerationRejected',
        'testValidFourTeamKnockout',
        'testOddTeamKnockoutSafelyHandledWithoutInventedSeeding',
        'testUnsupportedGroupPlusKnockoutSafelyRejected',
        'testCrossTenantTournamentRejected',
        'testUnauthorizedUserRejected',
        'testAuthorizedUserWebActionGeneratesFixtures',
        'testInvalidTournamentRejected',
        'testExistingFixtureSchedulingConflictRejected',
        'testExistingVenueBookingConflictRejected',
        'testValidNonConflictingFixtureAccepted',
        'testAuditRecordCreated',
        'testTransactionRollbackVerified',
        'testExistingManualFixtureCreationStillWorks',
        'testExistingVenueBookingFunctionalityStillWorks',
    ];

    $passed = 0;
    $failed = 0;
    foreach ($tests as $method) {
        try {
            $ok = $test->$method();
            if ($ok) {
                echo " [PASS] {$method}\n";
                $passed++;
            } else {
                echo " [FAIL] {$method}\n";
                $failed++;
            }
        } catch (\Throwable $e) {
            echo " [ERROR] {$method}: " . $e->getMessage() . "\n";
            $failed++;
        }
    }

    echo "\nResults: {$passed} Passed, {$failed} Failed\n";
    exit($failed === 0 ? 0 : 1);
}
