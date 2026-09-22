<?php

namespace Tests\Feature;

use App\Services\BaseService;
use App\Services\Tournament\TournamentService;
use App\Services\Venue\VenueService;
use PDO;

class TournamentVenueWebActionTest
{
    private int $orgId = 1; // Apex Sports Academy
    private int $otherOrgId = 999; // Separate tenant
    private int $userId = 5; // Sports Admin user
    private PDO $pdo;
    private TournamentService $tournamentService;
    private VenueService $venueService;

    // Track test IDs for clean teardown
    private array $createdTournamentIds = [];
    private array $createdVenueIds = [];
    private array $createdTeamIds = [];
    private array $createdBookingIds = [];

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
        if (!empty($this->createdBookingIds)) {
            $inB = implode(',', array_map('intval', $this->createdBookingIds));
            $this->pdo->exec("DELETE FROM venue_bookings WHERE id IN ({$inB})");
        }
        if (!empty($this->createdTournamentIds)) {
            $inTourns = implode(',', array_map('intval', $this->createdTournamentIds));
            $this->pdo->exec("DELETE FROM matches WHERE fixture_id IN (SELECT id FROM fixtures WHERE tournament_id IN ({$inTourns}))");
            $this->pdo->exec("DELETE FROM fixtures WHERE tournament_id IN ({$inTourns})");
            $this->pdo->exec("DELETE FROM tournament_standings WHERE tournament_id IN ({$inTourns})");
            $this->pdo->exec("DELETE FROM tournament_teams WHERE tournament_id IN ({$inTourns})");
            $this->pdo->exec("DELETE FROM tournament_venues WHERE tournament_id IN ({$inTourns})");
            $this->pdo->exec("DELETE FROM tournaments WHERE id IN ({$inTourns})");
        }
        if (!empty($this->createdVenueIds)) {
            $inVenues = implode(',', array_map('intval', $this->createdVenueIds));
            $this->pdo->exec("DELETE FROM tournament_venues WHERE venue_id IN ({$inVenues})");
            $this->pdo->exec("DELETE FROM venue_bookings WHERE venue_id IN ({$inVenues})");
            $this->pdo->exec("DELETE FROM venue_facilities WHERE venue_id IN ({$inVenues})");
            $this->pdo->exec("DELETE FROM venues WHERE id IN ({$inVenues})");
        }
        if (!empty($this->createdTeamIds)) {
            $inTeams = implode(',', array_map('intval', $this->createdTeamIds));
            $this->pdo->exec("DELETE FROM tournament_teams WHERE team_id IN ({$inTeams})");
            $this->pdo->exec("DELETE FROM teams WHERE id IN ({$inTeams})");
        }
    }

    /**
     * Helper to execute web_actions.php in an isolated PHP subprocess.
     */
    private function executeWebAction(string $uri, array $post, array $session = []): int
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ks_tven_');
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
                        'tournament.create', 'tournament.update', 'tournament.manage', 'tournament.view',
                        'venue.create', 'venue.update', 'venue.manage', 'venue.view',
                        'fixture.manage'
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

    private function createTestTournament(int $orgId, string $status = 'ongoing'): int
    {
        $unique = bin2hex(random_bytes(4));
        $ref = 'TOURN-TEST-' . strtoupper($unique);
        $stmt = $this->pdo->prepare("
            INSERT INTO tournaments (
                organization_id, tournament_reference, name, sport_id,
                tournament_level_id, tournament_format_id, start_date, end_date,
                status, created_at, updated_at
            ) VALUES (
                :org_id, :ref, :name, 1,
                1, 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY),
                :status, NOW(), NOW()
            )
        ");
        $stmt->execute([
            ':org_id' => $orgId,
            ':ref' => $ref,
            ':name' => "Tournament {$unique}",
            ':status' => $status
        ]);
        $id = (int)$this->pdo->lastInsertId();
        $this->createdTournamentIds[] = $id;
        return $id;
    }

    private function createTestVenue(int $orgId, string $status = 'active', bool $isDeleted = false): int
    {
        $unique = bin2hex(random_bytes(4));
        $code = 'VEN-TEST-' . strtoupper($unique);
        $deletedAt = $isDeleted ? date('Y-m-d H:i:s') : null;
        $stmt = $this->pdo->prepare("
            INSERT INTO venues (
                organization_id, venue_code, name, venue_type,
                city, state, country, status, deleted_at, created_at, updated_at
            ) VALUES (
                :org_id, :code, :name, 'Arena',
                'Mumbai', 'Maharashtra', 'India', :status, :del_at, NOW(), NOW()
            )
        ");
        $stmt->execute([
            ':org_id' => $orgId,
            ':code' => $code,
            ':name' => "Venue {$unique}",
            ':status' => $status,
            ':del_at' => $deletedAt
        ]);
        $id = (int)$this->pdo->lastInsertId();
        $this->createdVenueIds[] = $id;
        return $id;
    }

    private function createTestTeam(int $orgId): int
    {
        $unique = bin2hex(random_bytes(4));
        $stmt = $this->pdo->prepare("
            INSERT INTO teams (organization_id, team_code, name, sport_id, gender, age_group, status, created_at, updated_at)
            VALUES (:org_id, :code, :name, 1, 'men', 'senior', 'active', NOW(), NOW())
        ");
        $stmt->execute([
            ':org_id' => $orgId,
            ':code' => 'TM-' . strtoupper($unique),
            ':name' => "Team {$unique}"
        ]);
        $id = (int)$this->pdo->lastInsertId();
        $this->createdTeamIds[] = $id;
        return $id;
    }

    // ==========================================
    // 17 DEDICATED TESTS
    // ==========================================

    /**
     * TEST 1: Successful venue assignment via POST /tournaments/{id}/venues/add
     */
    public function testSuccessfulVenueAssignment(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $venueId = $this->createTestVenue($this->orgId);

        $exitCode = $this->executeWebAction("/tournaments/{$tournId}/venues/add", [
            'venue_id' => $venueId
        ]);

        if ($exitCode !== 0) return false;

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tournament_venues WHERE tournament_id = :t_id AND venue_id = :v_id");
        $stmt->execute([':t_id' => $tournId, ':v_id' => $venueId]);
        return (int)$stmt->fetchColumn() === 1;
    }

    /**
     * TEST 2: Assigned venue appears in getTournament()
     */
    public function testVenueAppearsAsAssigned(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $venueId = $this->createTestVenue($this->orgId);

        $this->tournamentService->addVenue($this->orgId, $tournId, $venueId, true, $this->userId);

        $tournament = $this->tournamentService->getTournament($this->orgId, $tournId);
        if (empty($tournament['venues'])) return false;

        $assignedVenueIds = array_column($tournament['venues'], 'venue_id');
        return in_array($venueId, $assignedVenueIds);
    }

    /**
     * TEST 3: Duplicate venue assignment prevented (idempotent)
     */
    public function testDuplicateAssignmentPrevented(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $venueId = $this->createTestVenue($this->orgId);

        // Assign once
        $this->tournamentService->addVenue($this->orgId, $tournId, $venueId, false, $this->userId);

        // Assign again via Web Action
        $exitCode = $this->executeWebAction("/tournaments/{$tournId}/venues/add", [
            'venue_id' => $venueId
        ]);

        if ($exitCode !== 0) return false;

        // Verify still exactly 1 row
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tournament_venues WHERE tournament_id = :t_id AND venue_id = :v_id");
        $stmt->execute([':t_id' => $tournId, ':v_id' => $venueId]);
        return (int)$stmt->fetchColumn() === 1;
    }

    /**
     * TEST 4: Cross-tenant tournament rejected
     */
    public function testCrossTenantTournamentRejected(): bool
    {
        $otherTournId = $this->createTestTournament($this->otherOrgId);
        $venueId = $this->createTestVenue($this->orgId);

        // Org 1 operator attempts to assign to Org 999 tournament
        $this->executeWebAction("/tournaments/{$otherTournId}/venues/add", [
            'venue_id' => $venueId
        ]);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tournament_venues WHERE tournament_id = :t_id AND venue_id = :v_id");
        $stmt->execute([':t_id' => $otherTournId, ':v_id' => $venueId]);
        return (int)$stmt->fetchColumn() === 0;
    }

    /**
     * TEST 5: Cross-tenant venue rejected
     */
    public function testCrossTenantVenueRejected(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $otherVenueId = $this->createTestVenue($this->otherOrgId);

        // Org 1 operator attempts to assign Org 999 venue
        $this->executeWebAction("/tournaments/{$tournId}/venues/add", [
            'venue_id' => $otherVenueId
        ]);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tournament_venues WHERE tournament_id = :t_id AND venue_id = :v_id");
        $stmt->execute([':t_id' => $tournId, ':v_id' => $otherVenueId]);
        return (int)$stmt->fetchColumn() === 0;
    }

    /**
     * TEST 6: Deleted venue rejected
     */
    public function testDeletedVenueRejected(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $deletedVenueId = $this->createTestVenue($this->orgId, 'active', true);

        $this->executeWebAction("/tournaments/{$tournId}/venues/add", [
            'venue_id' => $deletedVenueId
        ]);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tournament_venues WHERE tournament_id = :t_id AND venue_id = :v_id");
        $stmt->execute([':t_id' => $tournId, ':v_id' => $deletedVenueId]);
        return (int)$stmt->fetchColumn() === 0;
    }

    /**
     * TEST 7: Inactive venue follows existing status rules (rejected)
     */
    public function testInactiveVenueFollowsExistingStatusRules(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $inactiveVenueId = $this->createTestVenue($this->orgId, 'inactive', false);

        $this->executeWebAction("/tournaments/{$tournId}/venues/add", [
            'venue_id' => $inactiveVenueId
        ]);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tournament_venues WHERE tournament_id = :t_id AND venue_id = :v_id");
        $stmt->execute([':t_id' => $tournId, ':v_id' => $inactiveVenueId]);
        return (int)$stmt->fetchColumn() === 0;
    }

    /**
     * TEST 8: Unauthorized user rejected (RBAC)
     */
    public function testUnauthorizedUserRejected(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $venueId = $this->createTestVenue($this->orgId);

        // Athlete session without tournament permissions
        $unauthSession = [
            'auth' => [
                'authenticated' => true,
                'user' => [
                    'id' => 99,
                    'email' => 'athlete@khelsutra.local',
                    'role_id' => 5,
                    'role' => ['id' => 5, 'name' => 'Athlete', 'slug' => 'athlete'],
                    'permissions' => ['athlete.view'],
                    'status' => 'active'
                ],
                'organization' => [
                    'id' => $this->orgId,
                    'name' => 'Apex Sports Academy',
                    'code' => 'APEX'
                ]
            ]
        ];

        $this->executeWebAction("/tournaments/{$tournId}/venues/add", [
            'venue_id' => $venueId
        ], $unauthSession);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tournament_venues WHERE tournament_id = :t_id AND venue_id = :v_id");
        $stmt->execute([':t_id' => $tournId, ':v_id' => $venueId]);
        return (int)$stmt->fetchColumn() === 0;
    }

    /**
     * TEST 9: Audit record created (TOURNAMENT_VENUE_ADD)
     */
    public function testAuditRecordCreated(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $venueId = $this->createTestVenue($this->orgId);

        $this->executeWebAction("/tournaments/{$tournId}/venues/add", [
            'venue_id' => $venueId
        ]);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM audit_logs 
            WHERE organization_id = :org_id 
              AND action = 'TOURNAMENT_VENUE_ADD' 
              AND record_id = :t_id
        ");
        $stmt->execute([':org_id' => $this->orgId, ':t_id' => $tournId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * TEST 10: Invalid tournament ID handled safely
     */
    public function testInvalidTournamentId(): bool
    {
        $venueId = $this->createTestVenue($this->orgId);

        $exitCode = $this->executeWebAction("/tournaments/999999/venues/add", [
            'venue_id' => $venueId
        ]);

        return $exitCode === 0; // Script redirects without fatal crash
    }

    /**
     * TEST 11: Invalid venue ID handled safely
     */
    public function testInvalidVenueId(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);

        $exitCode = $this->executeWebAction("/tournaments/{$tournId}/venues/add", [
            'venue_id' => 999999
        ]);

        return $exitCode === 0;
    }

    /**
     * TEST 12: Successful venue removal (does NOT delete actual venue)
     */
    public function testSuccessfulVenueRemoval(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $venueId = $this->createTestVenue($this->orgId);

        $this->tournamentService->addVenue($this->orgId, $tournId, $venueId, false, $this->userId);

        $exitCode = $this->executeWebAction("/tournaments/{$tournId}/venues/{$venueId}/remove", []);
        if ($exitCode !== 0) return false;

        // Verify tournament_venues row removed
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tournament_venues WHERE tournament_id = :t_id AND venue_id = :v_id");
        $stmt->execute([':t_id' => $tournId, ':v_id' => $venueId]);
        if ((int)$stmt->fetchColumn() !== 0) return false;

        // Verify actual venue was NOT deleted
        $vStmt = $this->pdo->prepare("SELECT id, deleted_at FROM venues WHERE id = :id");
        $vStmt->execute([':id' => $venueId]);
        $venue = $vStmt->fetch(PDO::FETCH_ASSOC);

        return $venue && empty($venue['deleted_at']);
    }

    /**
     * TEST 13: Venue removal blocked when existing fixtures depend on it
     */
    public function testVenueRemovalBlockedWhenExistingFixturesDependOnIt(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $venueId = $this->createTestVenue($this->orgId);
        $homeId = $this->createTestTeam($this->orgId);
        $awayId = $this->createTestTeam($this->orgId);

        $this->tournamentService->addVenue($this->orgId, $tournId, $venueId, true, $this->userId);
        $this->tournamentService->addTeam($this->orgId, $tournId, $homeId, $this->userId);
        $this->tournamentService->addTeam($this->orgId, $tournId, $awayId, $this->userId);

        // Schedule fixture at this venue
        $this->tournamentService->createFixture($this->orgId, $tournId, [
            'round_name' => 'Group Match 1',
            'home_team_id' => $homeId,
            'away_team_id' => $awayId,
            'venue_id' => $venueId,
            'scheduled_date' => date('Y-m-d', strtotime('+3 days')),
            'scheduled_start_time' => '10:00:00',
        ], $this->userId);

        // Attempt removal
        $this->executeWebAction("/tournaments/{$tournId}/venues/{$venueId}/remove", []);

        // Verify relationship was NOT removed
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tournament_venues WHERE tournament_id = :t_id AND venue_id = :v_id");
        $stmt->execute([':t_id' => $tournId, ':v_id' => $venueId]);
        return (int)$stmt->fetchColumn() === 1;
    }

    /**
     * TEST 14: Historical fixture/booking data remains intact on removal of another venue
     */
    public function testHistoricalFixtureAndBookingDataRemainsIntact(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $venueA = $this->createTestVenue($this->orgId);
        $venueB = $this->createTestVenue($this->orgId);
        $homeId = $this->createTestTeam($this->orgId);
        $awayId = $this->createTestTeam($this->orgId);

        $this->tournamentService->addVenue($this->orgId, $tournId, $venueA, true, $this->userId);
        $this->tournamentService->addVenue($this->orgId, $tournId, $venueB, false, $this->userId);
        $this->tournamentService->addTeam($this->orgId, $tournId, $homeId, $this->userId);
        $this->tournamentService->addTeam($this->orgId, $tournId, $awayId, $this->userId);

        // Create fixture at Venue A
        $fix = $this->tournamentService->createFixture($this->orgId, $tournId, [
            'round_name' => 'Historical Semi',
            'home_team_id' => $homeId,
            'away_team_id' => $awayId,
            'venue_id' => $venueA,
            'scheduled_date' => date('Y-m-d'),
            'scheduled_start_time' => '11:00:00',
        ], $this->userId);

        // Create booking at Venue B
        $bkg = $this->venueService->createBooking($this->orgId, [
            'venue_id' => $venueB,
            'purpose' => 'Practice',
            'booking_date' => date('Y-m-d'),
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
        ], $this->userId);
        if (!empty($bkg['id'])) {
            $this->createdBookingIds[] = (int)$bkg['id'];
        }

        // Now remove Venue B (which has no fixtures in this tournament)
        $this->tournamentService->removeVenue($this->orgId, $tournId, $venueB, $this->userId);

        // Verify Venue A fixture is untouched
        $fStmt = $this->pdo->prepare("SELECT venue_id FROM fixtures WHERE id = :id");
        $fStmt->execute([':id' => $fix['id']]);
        $fVenue = (int)$fStmt->fetchColumn();

        // Verify Venue B booking is untouched
        $bStmt = $this->pdo->prepare("SELECT venue_id FROM venue_bookings WHERE id = :id");
        $bStmt->execute([':id' => $bkg['id']]);
        $bVenue = (int)$bStmt->fetchColumn();

        return $fVenue === $venueA && $bVenue === $venueB;
    }

    /**
     * TEST 15: Fixture cannot use an unassigned tournament venue
     */
    public function testFixtureCannotUseUnassignedTournamentVenue(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $assignedVenue = $this->createTestVenue($this->orgId);
        $unassignedVenue = $this->createTestVenue($this->orgId);
        $homeId = $this->createTestTeam($this->orgId);
        $awayId = $this->createTestTeam($this->orgId);

        $this->tournamentService->addVenue($this->orgId, $tournId, $assignedVenue, true, $this->userId);
        $this->tournamentService->addTeam($this->orgId, $tournId, $homeId, $this->userId);
        $this->tournamentService->addTeam($this->orgId, $tournId, $awayId, $this->userId);

        // Attempt fixture creation with unassigned venue
        $this->executeWebAction("/tournaments/{$tournId}/fixtures/create", [
            'round_name' => 'Unassigned Venue Test',
            'home_team_id' => $homeId,
            'away_team_id' => $awayId,
            'venue_id' => $unassignedVenue,
            'scheduled_date' => date('Y-m-d', strtotime('+2 days')),
            'scheduled_start_time' => '14:00:00',
        ]);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM fixtures WHERE tournament_id = :t_id AND round_name = 'Unassigned Venue Test'");
        $stmt->execute([':t_id' => $tournId]);
        return (int)$stmt->fetchColumn() === 0;
    }

    /**
     * TEST 16: Fixture can use a valid assigned venue
     */
    public function testFixtureCanUseValidAssignedVenue(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $assignedVenue = $this->createTestVenue($this->orgId);
        $homeId = $this->createTestTeam($this->orgId);
        $awayId = $this->createTestTeam($this->orgId);

        $this->tournamentService->addVenue($this->orgId, $tournId, $assignedVenue, true, $this->userId);
        $this->tournamentService->addTeam($this->orgId, $tournId, $homeId, $this->userId);
        $this->tournamentService->addTeam($this->orgId, $tournId, $awayId, $this->userId);

        // Attempt fixture creation with assigned venue
        $this->executeWebAction("/tournaments/{$tournId}/fixtures/create", [
            'round_name' => 'Assigned Venue Test',
            'home_team_id' => $homeId,
            'away_team_id' => $awayId,
            'venue_id' => $assignedVenue,
            'scheduled_date' => date('Y-m-d', strtotime('+2 days')),
            'scheduled_start_time' => '14:00:00',
        ]);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM fixtures WHERE tournament_id = :t_id AND round_name = 'Assigned Venue Test'");
        $stmt->execute([':t_id' => $tournId]);
        return (int)$stmt->fetchColumn() === 1;
    }

    /**
     * TEST 17: Malicious cross-tenant fixture venue submission rejected
     */
    public function testMaliciousCrossTenantFixtureVenueSubmissionRejected(): bool
    {
        $tournId = $this->createTestTournament($this->orgId);
        $otherVenue = $this->createTestVenue($this->otherOrgId);
        $homeId = $this->createTestTeam($this->orgId);
        $awayId = $this->createTestTeam($this->orgId);

        $this->tournamentService->addTeam($this->orgId, $tournId, $homeId, $this->userId);
        $this->tournamentService->addTeam($this->orgId, $tournId, $awayId, $this->userId);

        $this->executeWebAction("/tournaments/{$tournId}/fixtures/create", [
            'round_name' => 'Cross Tenant Fixture Venue Test',
            'home_team_id' => $homeId,
            'away_team_id' => $awayId,
            'venue_id' => $otherVenue,
            'scheduled_date' => date('Y-m-d', strtotime('+2 days')),
            'scheduled_start_time' => '14:00:00',
        ]);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM fixtures WHERE tournament_id = :t_id AND round_name = 'Cross Tenant Fixture Venue Test'");
        $stmt->execute([':t_id' => $tournId]);
        return (int)$stmt->fetchColumn() === 0;
    }

    public function runAll(): bool
    {
        $tests = [
            'testSuccessfulVenueAssignment' => $this->testSuccessfulVenueAssignment(),
            'testVenueAppearsAsAssigned' => $this->testVenueAppearsAsAssigned(),
            'testDuplicateAssignmentPrevented' => $this->testDuplicateAssignmentPrevented(),
            'testCrossTenantTournamentRejected' => $this->testCrossTenantTournamentRejected(),
            'testCrossTenantVenueRejected' => $this->testCrossTenantVenueRejected(),
            'testDeletedVenueRejected' => $this->testDeletedVenueRejected(),
            'testInactiveVenueFollowsExistingStatusRules' => $this->testInactiveVenueFollowsExistingStatusRules(),
            'testUnauthorizedUserRejected' => $this->testUnauthorizedUserRejected(),
            'testAuditRecordCreated' => $this->testAuditRecordCreated(),
            'testInvalidTournamentId' => $this->testInvalidTournamentId(),
            'testInvalidVenueId' => $this->testInvalidVenueId(),
            'testSuccessfulVenueRemoval' => $this->testSuccessfulVenueRemoval(),
            'testVenueRemovalBlockedWhenExistingFixturesDependOnIt' => $this->testVenueRemovalBlockedWhenExistingFixturesDependOnIt(),
            'testHistoricalFixtureAndBookingDataRemainsIntact' => $this->testHistoricalFixtureAndBookingDataRemainsIntact(),
            'testFixtureCannotUseUnassignedTournamentVenue' => $this->testFixtureCannotUseUnassignedTournamentVenue(),
            'testFixtureCanUseValidAssignedVenue' => $this->testFixtureCanUseValidAssignedVenue(),
            'testMaliciousCrossTenantFixtureVenueSubmissionRejected' => $this->testMaliciousCrossTenantFixtureVenueSubmissionRejected(),
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
    echo "Running TournamentVenueWebActionTest suite..." . PHP_EOL;
    $test = new TournamentVenueWebActionTest();
    $passed = $test->runAll();
    exit($passed ? 0 : 1);
}
