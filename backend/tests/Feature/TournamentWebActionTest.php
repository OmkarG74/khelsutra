<?php

namespace Tests\Feature;

use App\Services\BaseService;
use App\Services\Tournament\TournamentService;
use App\Services\Rbac\PermissionService;
use PDO;

class TournamentWebActionTest
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
        $tempFile = tempnam(sys_get_temp_dir(), 'ks_act_');
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
            . "\$method = 'POST';\n"
            . "\$uri = '{$uri}';\n"
            . "\$_POST = {$postExport};\n"
            . "\$_SESSION = {$sessionExport};\n"
            . "require '{$actionsPath}';\n";

        file_put_contents($tempFile, $code);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open("php " . escapeshellarg($tempFile), $descriptors, $pipes, dirname(__DIR__, 2));
        fclose($pipes[0]);
        stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);
        @unlink($tempFile);

        return $exitCode;
    }

    /**
     * Helper to query HTTP server on 127.0.0.1:8000 if available.
     */
    private function requestHttp(string $uri, array $post, ?string $cookie = null): array
    {
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n" . ($cookie ? "Cookie: PHPSESSID={$cookie}\r\n" : ""),
                'content' => http_build_query($post),
                'follow_location' => 0,
                'ignore_errors' => true,
                'timeout' => 3
            ]
        ];
        $ctx = stream_context_create($opts);
        $body = @file_get_contents("http://127.0.0.1:8000" . $uri, false, $ctx);
        $headers = $http_response_header ?? [];

        $location = null;
        foreach ($headers as $h) {
            if (stripos($h, 'Location:') === 0) {
                $location = trim(substr($h, 9));
                break;
            }
        }

        return [
            'headers' => $headers,
            'location' => $location,
            'body' => $body,
        ];
    }

    /**
     * TEST 1: POST /tournaments/create creates a tournament with all valid fields
     */
    public function testCreateTournamentSuccess(): bool
    {
        $uniqueName = 'Championship-' . uniqid();
        $startDate = date('Y-m-d', strtotime('+3 days'));
        $endDate = date('Y-m-d', strtotime('+10 days'));

        $this->executeWebAction('/tournaments/create', [
            'name' => $uniqueName,
            'sport_id' => 1,
            'tournament_level_id' => 2,
            'tournament_format_id' => 1,
            'status' => 'registration_open',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'organizer_name' => 'Apex Tournaments',
            'city' => 'Pune',
            'state' => 'Maharashtra',
        ]);

        $stmt = $this->pdo->prepare("SELECT * FROM tournaments WHERE name = :name AND organization_id = :org AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([':name' => $uniqueName, ':org' => $this->orgId]);
        $tournament = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tournament) {
            return false;
        }

        // Verify fields
        if ((int)$tournament['sport_id'] !== 1 || $tournament['status'] !== 'registration_open') {
            return false;
        }

        // Verify audit log
        $auditStmt = $this->pdo->prepare("SELECT id FROM audit_logs WHERE table_name = 'tournaments' AND record_id = :id AND action = 'TOURNAMENT_CREATE' LIMIT 1");
        $auditStmt->execute([':id' => $tournament['id']]);
        return (bool)$auditStmt->fetchColumn();
    }

    /**
     * TEST 2: POST /tournaments/create rejects invalid inputs (missing name, invalid dates, invalid status)
     */
    public function testCreateTournamentValidation(): bool
    {
        $countBefore = (int)$this->pdo->query("SELECT COUNT(*) FROM tournaments")->fetchColumn();

        // Missing name
        $this->executeWebAction('/tournaments/create', [
            'name' => '',
            'sport_id' => 1,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+5 days')),
        ]);

        // End date before start date
        $this->executeWebAction('/tournaments/create', [
            'name' => 'Invalid Date Cup ' . uniqid(),
            'sport_id' => 1,
            'start_date' => date('Y-m-d', strtotime('+10 days')),
            'end_date' => date('Y-m-d', strtotime('+2 days')),
        ]);

        // Invalid status
        $this->executeWebAction('/tournaments/create', [
            'name' => 'Invalid Status Cup ' . uniqid(),
            'sport_id' => 1,
            'status' => 'bogus_status',
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+5 days')),
        ]);

        $countAfter = (int)$this->pdo->query("SELECT COUNT(*) FROM tournaments")->fetchColumn();

        return ($countBefore === $countAfter);
    }

    /**
     * TEST 3: Tenant isolation — untrusted organization_id from POST is ignored, foreign venue rejected
     */
    public function testCreateTournamentTenantIsolation(): bool
    {
        $uniqueName = 'TenantIsoCup-' . uniqid();

        // Attempt to spoof organization_id = 999
        $this->executeWebAction('/tournaments/create', [
            'name' => $uniqueName,
            'sport_id' => 1,
            'organization_id' => 999, // spoof attempt
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+5 days')),
            'status' => 'draft',
        ]);

        // Check it was NOT created for org 999
        $stmt999 = $this->pdo->prepare("SELECT id FROM tournaments WHERE name = :name AND organization_id = 999");
        $stmt999->execute([':name' => $uniqueName]);
        if ($stmt999->fetchColumn()) {
            return false;
        }

        // Check it was safely isolated to current tenant ($this->orgId = 1)
        $stmtOrg = $this->pdo->prepare("SELECT id FROM tournaments WHERE name = :name AND organization_id = :org");
        $stmtOrg->execute([':name' => $uniqueName, ':org' => $this->orgId]);
        $tournId = $stmtOrg->fetchColumn();

        if (!$tournId) {
            return false;
        }

        // Now test foreign venue rejection
        $countBefore = (int)$this->pdo->query("SELECT COUNT(*) FROM tournaments")->fetchColumn();
        $this->executeWebAction('/tournaments/create', [
            'name' => 'ForeignVenueCup-' . uniqid(),
            'sport_id' => 1,
            'venue_id' => 999999, // Non-existent / foreign venue
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+5 days')),
        ]);
        $countAfter = (int)$this->pdo->query("SELECT COUNT(*) FROM tournaments")->fetchColumn();

        return ($countBefore === $countAfter);
    }

    /**
     * TEST 4: POST /tournaments/{id}/edit updates tournament fields and logs audit
     */
    public function testEditTournamentSuccess(): bool
    {
        // 1. Create a tournament
        $created = $this->service->createTournament($this->orgId, [
            'name' => 'Pre-Edit Tourney ' . uniqid(),
            'sport_id' => 1,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+5 days')),
            'status' => 'draft',
        ], $this->userId);

        $tournId = (int)$created['id'];
        $updatedName = 'Post-Edit Tourney ' . uniqid();
        $newEndDate = date('Y-m-d', strtotime('+12 days'));

        // 2. Execute edit action
        $this->executeWebAction("/tournaments/{$tournId}/edit", [
            'name' => $updatedName,
            'sport_id' => 1,
            'start_date' => date('Y-m-d'),
            'end_date' => $newEndDate,
            'status' => 'ongoing',
            'city' => 'Thane',
        ]);

        // 3. Verify changes in DB
        $fresh = $this->service->getTournament($this->orgId, $tournId);
        if (!$fresh || $fresh['name'] !== $updatedName || $fresh['status'] !== 'ongoing' || $fresh['end_date'] !== $newEndDate) {
            return false;
        }

        // 4. Verify audit log
        $auditStmt = $this->pdo->prepare("SELECT id FROM audit_logs WHERE table_name = 'tournaments' AND record_id = :id AND action = 'TOURNAMENT_UPDATE' LIMIT 1");
        $auditStmt->execute([':id' => $tournId]);
        return (bool)$auditStmt->fetchColumn();
    }

    /**
     * TEST 5: POST /tournaments/{id}/edit enforces tenant isolation (cannot edit foreign tournament)
     */
    public function testEditTournamentTenantIsolation(): bool
    {
        // 1. Create tournament for Org 2
        $foreign = $this->service->createTournament($this->otherOrgId, [
            'name' => 'Org2 Tourney ' . uniqid(),
            'sport_id' => 1,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+5 days')),
            'status' => 'draft',
        ], $this->userId);

        $foreignId = (int)$foreign['id'];

        // 2. Org 1 user attempts to edit Org 2's tournament
        $this->executeWebAction("/tournaments/{$foreignId}/edit", [
            'name' => 'Maliciously Changed Tourney Name',
            'sport_id' => 1,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+5 days')),
            'status' => 'ongoing',
        ]);

        // 3. Verify Org 2 tournament was NOT modified
        $check = $this->service->getTournament($this->otherOrgId, $foreignId);
        return ($check !== null && str_starts_with($check['name'], 'Org2 Tourney'));
    }

    /**
     * TEST 6: POST /tournaments/{id}/delete soft-deletes the tournament (not hard delete)
     */
    public function testDeleteTournamentSoftDelete(): bool
    {
        // 1. Create tournament
        $created = $this->service->createTournament($this->orgId, [
            'name' => 'To-Delete Tourney ' . uniqid(),
            'sport_id' => 1,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+5 days')),
            'status' => 'draft',
        ], $this->userId);

        $tournId = (int)$created['id'];

        // 2. Execute delete action
        $this->executeWebAction("/tournaments/{$tournId}/delete", []);

        // 3. Verify record STILL EXISTS in DB (not hard-deleted)
        $rawStmt = $this->pdo->prepare("SELECT id, deleted_at FROM tournaments WHERE id = :id");
        $rawStmt->execute([':id' => $tournId]);
        $row = $rawStmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || empty($row['deleted_at'])) {
            return false; // must exist with deleted_at populated
        }

        // 4. Verify getTournament() filters it out
        $activeCheck = $this->service->getTournament($this->orgId, $tournId);
        if ($activeCheck !== null) {
            return false;
        }

        // 5. Verify audit log
        $auditStmt = $this->pdo->prepare("SELECT id FROM audit_logs WHERE table_name = 'tournaments' AND record_id = :id AND action = 'TOURNAMENT_DELETE' LIMIT 1");
        $auditStmt->execute([':id' => $tournId]);
        return (bool)$auditStmt->fetchColumn();
    }

    /**
     * TEST 7: POST /tournaments/{id}/delete cannot soft-delete another tenant's tournament
     */
    public function testDeleteTournamentCrossTenantBlocked(): bool
    {
        // 1. Create tournament for Org 2
        $foreign = $this->service->createTournament($this->otherOrgId, [
            'name' => 'Org2 Delete Defense Tourney ' . uniqid(),
            'sport_id' => 1,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+5 days')),
            'status' => 'draft',
        ], $this->userId);

        $foreignId = (int)$foreign['id'];

        // 2. Org 1 user attempts to delete Org 2's tournament
        $this->executeWebAction("/tournaments/{$foreignId}/delete", []);

        // 3. Verify Org 2 tournament was NOT soft deleted
        $foreignCheck = $this->service->getTournament($this->otherOrgId, $foreignId);
        return ($foreignCheck !== null);
    }

    /**
     * TEST 8: RBAC authorization check rejects unauthorized roles
     */
    public function testRBACUnauthorizedBlocked(): bool
    {
        $countBefore = (int)$this->pdo->query("SELECT COUNT(*) FROM tournaments")->fetchColumn();

        // Athlete session without tournament.create or tournament.manage permission
        $unauthorizedSession = [
            'auth' => [
                'authenticated' => true,
                'user' => [
                    'id' => 999,
                    'role_id' => 5, // Athlete role
                    'role' => ['name' => 'Athlete', 'slug' => 'athlete'],
                    'permissions' => ['tournament.view'], // view only!
                    'status' => 'active'
                ],
                'organization' => [
                    'id' => $this->orgId,
                    'name' => 'Apex Sports Academy',
                    'code' => 'APEX'
                ]
            ]
        ];

        $this->executeWebAction('/tournaments/create', [
            'name' => 'Unauthorized Athlete Cup ' . uniqid(),
            'sport_id' => 1,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+5 days')),
            'status' => 'draft',
        ], $unauthorizedSession);

        $countAfter = (int)$this->pdo->query("SELECT COUNT(*) FROM tournaments")->fetchColumn();

        return ($countBefore === $countAfter);
    }

    /**
     * Run all test methods and return summary boolean.
     */
    public function runAll(): bool
    {
        $tests = [
            'testCreateTournamentSuccess' => $this->testCreateTournamentSuccess(),
            'testCreateTournamentValidation' => $this->testCreateTournamentValidation(),
            'testCreateTournamentTenantIsolation' => $this->testCreateTournamentTenantIsolation(),
            'testEditTournamentSuccess' => $this->testEditTournamentSuccess(),
            'testEditTournamentTenantIsolation' => $this->testEditTournamentTenantIsolation(),
            'testDeleteTournamentSoftDelete' => $this->testDeleteTournamentSoftDelete(),
            'testDeleteTournamentCrossTenantBlocked' => $this->testDeleteTournamentCrossTenantBlocked(),
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
    echo "Running TournamentWebActionTest suite..." . PHP_EOL;
    $test = new TournamentWebActionTest();
    $passed = $test->runAll();
    exit($passed ? 0 : 1);
}
