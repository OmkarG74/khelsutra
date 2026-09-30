<?php

namespace Tests\Feature;

use App\Services\BaseService;
use App\Services\Athlete\AthleteService;
use App\Services\Export\AthleteXlsxExporter;
use App\Services\Rbac\PermissionService;
use PDO;
use ZipArchive;

class AthleteModuleCompleteFixTest
{
    private int $orgId = 1;
    private int $otherOrgId = 2;
    private int $sportsAdminUserId = 102;
    private PDO $pdo;
    private AthleteService $athleteService;
    private PermissionService $permissionService;

    private array $createdAthleteIds = [];
    private array $createdTeamIds = [];
    private array $createdDocIds = [];
    private array $createdGuardianIds = [];

    public function __construct()
    {
        $this->pdo = BaseService::getDatabaseConnection();
        $this->athleteService = new AthleteService();
        $this->permissionService = new PermissionService($this->pdo);
    }

    public function __destruct()
    {
        $this->cleanup();
    }

    private function cleanup(): void
    {
        if (!empty($this->createdDocIds)) {
            $in = implode(',', array_map('intval', $this->createdDocIds));
            $this->pdo->exec("DELETE FROM athlete_documents WHERE id IN ({$in})");
        }
        if (!empty($this->createdGuardianIds)) {
            $in = implode(',', array_map('intval', $this->createdGuardianIds));
            $this->pdo->exec("DELETE FROM athlete_guardians WHERE id IN ({$in})");
        }
        if (!empty($this->createdAthleteIds)) {
            $in = implode(',', array_map('intval', $this->createdAthleteIds));
            $this->pdo->exec("DELETE FROM team_members WHERE athlete_id IN ({$in})");
            $this->pdo->exec("DELETE FROM athlete_guardians WHERE athlete_id IN ({$in})");
            $this->pdo->exec("DELETE FROM athlete_documents WHERE athlete_id IN ({$in})");
            $this->pdo->exec("DELETE FROM audit_logs WHERE table_name = 'athletes' AND record_id IN ({$in})");
            $this->pdo->exec("DELETE FROM athletes WHERE id IN ({$in})");
        }
        if (!empty($this->createdTeamIds)) {
            $in = implode(',', array_map('intval', $this->createdTeamIds));
            $this->pdo->exec("DELETE FROM team_members WHERE team_id IN ({$in})");
            $this->pdo->exec("DELETE FROM teams WHERE id IN ({$in})");
        }
    }

    private function createTestAthlete(
        int $orgId,
        string $firstName,
        string $lastName,
        int $sportId = 1,
        string $status = 'active',
        ?string $email = null,
        ?string $phone = null
    ): int {
        $unique = strtoupper(bin2hex(random_bytes(3)));
        $code = 'ATH-FX-' . $unique;
        $stmt = $this->pdo->prepare("
            INSERT INTO athletes (
                organization_id, athlete_code, first_name, last_name,
                date_of_birth, gender, current_sport_id, phone, email,
                registration_date, status, created_at, updated_at
            ) VALUES (
                :org_id, :code, :first, :last,
                '2004-05-15', 'male', :sport_id, :phone, :email,
                CURDATE(), :status, NOW(), NOW()
            )
        ");
        $stmt->execute([
            ':org_id'   => $orgId,
            ':code'     => $code,
            ':first'    => $firstName,
            ':last'     => $lastName,
            ':sport_id' => $sportId,
            ':phone'    => $phone ?? ('98' . rand(10000000, 99999999)),
            ':email'    => $email ?? (strtolower($firstName . '.' . $unique) . '@khelsutra.local'),
            ':status'   => $status,
        ]);
        $id = (int)$this->pdo->lastInsertId();
        $this->createdAthleteIds[] = $id;
        return $id;
    }

    private function createTestTeam(int $orgId, string $name, int $sportId = 1): int
    {
        $unique = strtoupper(bin2hex(random_bytes(3)));
        $stmt = $this->pdo->prepare("
            INSERT INTO teams (organization_id, team_code, name, sport_id, gender, age_group, status, created_at, updated_at)
            VALUES (:org_id, :code, :name, :sport_id, 'open', 'Senior', 'active', NOW(), NOW())
        ");
        $stmt->execute([
            ':org_id'   => $orgId,
            ':code'     => 'TM-FX-' . $unique,
            ':name'     => $name,
            ':sport_id' => $sportId,
        ]);
        $id = (int)$this->pdo->lastInsertId();
        $this->createdTeamIds[] = $id;
        return $id;
    }

    private function assignAthleteToTeam(int $orgId, int $athleteId, int $teamId, int $isCurrent = 1): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO team_members (organization_id, team_id, athlete_id, start_date, is_current, member_role, created_at, updated_at)
            VALUES (:org_id, :team_id, :ath_id, CURDATE(), :is_current, 'player', NOW(), NOW())
        ");
        $stmt->execute([
            ':org_id'     => $orgId,
            ':team_id'    => $teamId,
            ':ath_id'     => $athleteId,
            ':is_current' => $isCurrent,
        ]);
    }

    private function executeWebPostAction(string $uri, array $post, array $session = []): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ks_ath_post_');
        $bootstrapPath = addslashes(str_replace('\\', '/', dirname(__DIR__, 2) . '/bootstrap/app.php'));
        $authContextPath = addslashes(str_replace('\\', '/', dirname(__DIR__, 2) . '/app/Helpers/AuthContext.php'));
        $actionsPath = addslashes(str_replace('\\', '/', dirname(__DIR__, 2) . '/routes/web_actions.php'));

        $defaultSession = [
            'auth' => [
                'authenticated' => true,
                'user' => [
                    'id' => $this->sportsAdminUserId,
                    'email' => 'sportsadmin@khelsutra.local',
                    'first_name' => 'Rajesh',
                    'last_name' => 'Sharma',
                    'role_id' => 2,
                ],
                'role' => ['id' => 2, 'name' => 'Sports Administrator', 'slug' => 'sports_admin'],
                'organization' => ['id' => $this->orgId, 'name' => 'Apex Sports Academy'],
                'permissions' => ['athlete.view', 'athlete.create', 'athlete.update', 'athlete.delete'],
            ],
        ];

        $effectiveSession = !empty($session) ? $session : $defaultSession;
        $sessionExport = var_export($effectiveSession, true);
        $postExport = var_export($post, true);

        $code = "<?php\n"
            . "require_once '{$bootstrapPath}';\n"
            . "require_once '{$authContextPath}';\n"
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

        return ['exitCode' => $exitCode, 'output' => implode("\n", $output)];
    }

    private function renderShowView(int $athleteId, array $session): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ks_ath_show_');
        $bootstrapPath = addslashes(str_replace('\\', '/', dirname(__DIR__, 2) . '/bootstrap/app.php'));
        $authContextPath = addslashes(str_replace('\\', '/', dirname(__DIR__, 2) . '/app/Helpers/AuthContext.php'));
        $viewPath = addslashes(str_replace('\\', '/', dirname(__DIR__, 2) . '/resources/views/sports/athletes-show.blade.php'));

        $sessionExport = var_export($session, true);
        $code = "<?php\n"
            . "require_once '{$bootstrapPath}';\n"
            . "require_once '{$authContextPath}';\n"
            . "\$_SESSION = {$sessionExport};\n"
            . "\$id = {$athleteId};\n"
            . "ob_start();\n"
            . "include '{$viewPath}';\n"
            . "\$html = ob_get_clean();\n"
            . "echo 'HTTP_CODE=' . http_response_code() . \"\\n\";\n"
            . "echo \$html;\n";

        file_put_contents($tempFile, $code);
        $cmd = 'php ' . escapeshellarg($tempFile) . ' 2>&1';
        exec($cmd, $output, $exitCode);
        @unlink($tempFile);

        $fullOut = implode("\n", $output);
        $httpCode = 200;
        if (preg_match('/HTTP_CODE=(\d+)/', $fullOut, $m)) {
            $httpCode = (int)$m[1];
        }
        return ['http_code' => $httpCode, 'body' => $fullOut];
    }

    // 1. Athlete pagination
    public function testAthletePagination(): bool
    {
        $prefix = 'PagBatch' . bin2hex(random_bytes(2));
        for ($i = 1; $i <= 25; $i++) {
            $this->createTestAthlete($this->orgId, $prefix, "Player{$i}", 1, 'active');
        }

        $page1 = $this->athleteService->listAthletes($this->orgId, 1, 20, $prefix);
        $page2 = $this->athleteService->listAthletes($this->orgId, 2, 20, $prefix);

        return $page1['total'] === 25
            && count($page1['data']) === 20
            && $page1['page'] === 1
            && $page1['total_pages'] === 2
            && $page1['from'] === 1
            && $page1['to'] === 20
            && count($page2['data']) === 5
            && $page2['page'] === 2
            && $page2['from'] === 21
            && $page2['to'] === 25;
    }

    // 2. Athlete unique-row query (multiple team memberships = 1 row)
    public function testAthleteUniqueRowWithMultipleTeams(): bool
    {
        $uniqueName = 'MultiTeam' . bin2hex(random_bytes(3));
        $athId = $this->createTestAthlete($this->orgId, $uniqueName, 'Star', 1, 'active');
        $team1 = $this->createTestTeam($this->orgId, 'Alpha Squad ' . $uniqueName, 1);
        $team2 = $this->createTestTeam($this->orgId, 'Beta Squad ' . $uniqueName, 1);
        $team3 = $this->createTestTeam($this->orgId, 'Gamma Squad ' . $uniqueName, 1);

        $this->assignAthleteToTeam($this->orgId, $athId, $team1, 1);
        $this->assignAthleteToTeam($this->orgId, $athId, $team2, 1);
        $this->assignAthleteToTeam($this->orgId, $athId, $team3, 1);
        // Also insert a duplicate row for team1 to verify deduplication
        $this->assignAthleteToTeam($this->orgId, $athId, $team1, 1);

        $result = $this->athleteService->listAthletes($this->orgId, 1, 20, $uniqueName);
        if ($result['total'] !== 1 || count($result['data']) !== 1) {
            return false;
        }

        $row = $result['data'][0];
        return (int)$row['id'] === $athId
            && count($row['teams']) === 3
            && $row['extra_teams_count'] === 2
            && str_contains($row['all_teams_label'], 'Alpha Squad')
            && str_contains($row['all_teams_label'], 'Beta Squad')
            && str_contains($row['all_teams_label'], 'Gamma Squad');
    }

    // 3. Athlete count accuracy
    public function testAthleteCountAccuracy(): bool
    {
        $res = $this->athleteService->listAthletes($this->orgId, 1, 100);
        $ids = array_column($res['data'], 'id');
        $uniqueIds = array_unique($ids);

        $stmt = $this->pdo->prepare("SELECT COUNT(DISTINCT id) FROM athletes WHERE organization_id = :org AND deleted_at IS NULL");
        $stmt->execute([':org' => $this->orgId]);
        $dbCount = (int)$stmt->fetchColumn();

        return $res['total'] === $dbCount && count($ids) === count($uniqueIds);
    }

    // 4. Search + pagination
    public function testSearchAndPagination(): bool
    {
        $token = 'SrchTok' . bin2hex(random_bytes(2));
        for ($i = 1; $i <= 22; $i++) {
            $this->createTestAthlete($this->orgId, "First{$i}", "Last{$token}", 1, 'active', "user{$i}.{$token}@test.local", "99001122{$i}");
        }

        $p1 = $this->athleteService->listAthletes($this->orgId, 1, 20, $token);
        $p2 = $this->athleteService->listAthletes($this->orgId, 2, 20, $token);

        // Also test searching by phone and full name
        $byFullName = $this->athleteService->listAthletes($this->orgId, 1, 20, "First1 Last{$token}");
        $byPhone = $this->athleteService->listAthletes($this->orgId, 1, 20, "9900112215");

        return $p1['total'] === 22
            && count($p1['data']) === 20
            && count($p2['data']) === 2
            && $byFullName['total'] === 1
            && $byPhone['total'] === 1;
    }

    // 5. Sport filter
    public function testSportFilter(): bool
    {
        $tag = 'SpFlt' . bin2hex(random_bytes(2));
        $this->createTestAthlete($this->orgId, $tag, 'Footballer', 1, 'active');
        $this->createTestAthlete($this->orgId, $tag, 'Badminton1', 5, 'active');
        $this->createTestAthlete($this->orgId, $tag, 'Badminton2', 5, 'active');

        $badmintonRes = $this->athleteService->listAthletes($this->orgId, 1, 20, $tag, 5);
        $footballRes = $this->athleteService->listAthletes($this->orgId, 1, 20, $tag, 1);

        return $badmintonRes['total'] === 2
            && count($badmintonRes['data']) === 2
            && $footballRes['total'] === 1;
    }

    // 6. Status filter
    public function testStatusFilter(): bool
    {
        $tag = 'StFlt' . bin2hex(random_bytes(2));
        $this->createTestAthlete($this->orgId, $tag, 'Act1', 1, 'active');
        $this->createTestAthlete($this->orgId, $tag, 'Inact1', 1, 'inactive');
        $this->createTestAthlete($this->orgId, $tag, 'Inact2', 1, 'inactive');

        $activeRes = $this->athleteService->listAthletes($this->orgId, 1, 20, $tag, null, 'active');
        $inactiveRes = $this->athleteService->listAthletes($this->orgId, 1, 20, $tag, null, 'inactive');

        return $activeRes['total'] === 1 && $inactiveRes['total'] === 2;
    }

    // 7. Combined filters (search + sport + status)
    public function testCombinedFilters(): bool
    {
        $tag = 'CmbFlt' . bin2hex(random_bytes(2));
        $this->createTestAthlete($this->orgId, $tag, 'MatchOne', 5, 'active');
        $this->createTestAthlete($this->orgId, $tag, 'WrongStatus', 5, 'inactive');
        $this->createTestAthlete($this->orgId, $tag, 'WrongSport', 1, 'active');

        $res = $this->athleteService->listAthletes($this->orgId, 1, 20, $tag, 5, 'active');
        return $res['total'] === 1 && $res['data'][0]['last_name'] === 'MatchOne';
    }

    // 8. Export without filters (valid .xlsx OpenXML zip archive)
    public function testExportWithoutFilters(): bool
    {
        $export = $this->athleteService->exportAthletesXlsx($this->orgId);
        $list = $this->athleteService->listAthletes($this->orgId, 1, 1000);

        if ($export['filename'] !== 'khelsutra-athletes.xlsx' || $export['count'] !== $list['total']) {
            return false;
        }

        // Verify binary is a genuine OpenXML .xlsx zip archive
        $tmp = tempnam(sys_get_temp_dir(), 'ks_verify_xlsx_');
        file_put_contents($tmp, $export['binary']);
        $zip = new ZipArchive();
        $opened = $zip->open($tmp);
        $hasContentTypes = $opened === true && $zip->locateName('[Content_Types].xml') !== false;
        $sheetXml = $opened === true ? $zip->getFromName('xl/worksheets/sheet1.xml') : false;
        if ($opened === true) $zip->close();
        @unlink($tmp);

        return $hasContentTypes
            && is_string($sheetXml)
            && str_contains($sheetXml, 'Registration ID')
            && str_contains($sheetXml, 'Assigned Team');
    }

    // 9. Export with sport filter
    public function testExportWithSportFilter(): bool
    {
        $tag = 'ExpSp' . bin2hex(random_bytes(2));
        $this->createTestAthlete($this->orgId, $tag, 'ShuttleOne', 5, 'active');
        $this->createTestAthlete($this->orgId, $tag, 'RunnerTwo', 1, 'active');

        $export = $this->athleteService->exportAthletesXlsx($this->orgId, null, 5);
        foreach ($export['athletes'] as $a) {
            if ((int)$a['current_sport_id'] !== 5) {
                return false;
            }
        }
        return str_starts_with($export['filename'], 'khelsutra-athletes-')
            && str_ends_with($export['filename'], '.xlsx')
            && $export['count'] >= 1;
    }

    // 10. Export with status filter
    public function testExportWithStatusFilter(): bool
    {
        $tag = 'ExpSt' . bin2hex(random_bytes(2));
        $this->createTestAthlete($this->orgId, $tag, 'OnlyInactive', 1, 'inactive');

        $export = $this->athleteService->exportAthletesXlsx($this->orgId, null, null, 'inactive');
        foreach ($export['athletes'] as $a) {
            if ($a['status'] !== 'inactive') {
                return false;
            }
        }
        return $export['filename'] === 'khelsutra-athletes-inactive.xlsx' && $export['count'] >= 1;
    }

    // 11. Export with combined filters
    public function testExportWithCombinedFilters(): bool
    {
        $tag = 'ExpCmb' . bin2hex(random_bytes(2));
        $this->createTestAthlete($this->orgId, $tag, 'TargetShuttle', 5, 'active');
        $this->createTestAthlete($this->orgId, $tag, 'OtherShuttle', 5, 'inactive');
        $this->createTestAthlete($this->orgId, $tag, 'OtherFootball', 1, 'active');

        $export = $this->athleteService->exportAthletesXlsx($this->orgId, $tag, 5, 'active');
        return $export['count'] === 1
            && $export['rows'][0]['Athlete Name'] === "{$tag} TargetShuttle";
    }

    // 12. Export ignores pagination (returns full filtered dataset > 20 rows)
    public function testExportIgnoresPagination(): bool
    {
        $tag = 'ExpFull' . bin2hex(random_bytes(2));
        for ($i = 1; $i <= 26; $i++) {
            $this->createTestAthlete($this->orgId, $tag, "Bulk{$i}", 5, 'active');
        }

        // Page 2 with page size 20 has only 6 rows
        $page2List = $this->athleteService->listAthletes($this->orgId, 2, 20, $tag, 5, 'active');
        // Export with same filters must return all 26 rows
        $export = $this->athleteService->exportAthletesXlsx($this->orgId, $tag, 5, 'active');

        return count($page2List['data']) === 6
            && $page2List['total'] === 26
            && $export['count'] === 26
            && count($export['rows']) === 26;
    }

    // 13. Sports Admin delete permission
    public function testSportsAdminHasDeletePermissionAndCanArchive(): bool
    {
        $userPayload = [
            'id' => $this->sportsAdminUserId,
            'role_id' => 2,
            'role' => ['id' => 2, 'name' => 'Sports Administrator', 'slug' => 'sports_admin'],
        ];
        if (!$this->permissionService->hasPermission($userPayload, 'athlete.delete', $this->orgId)) {
            return false;
        }

        $athId = $this->createTestAthlete($this->orgId, 'AdminDel', 'Test', 1, 'active');
        $this->executeWebPostAction("/athletes/{$athId}/delete", []);

        $stmt = $this->pdo->prepare("SELECT deleted_at FROM athletes WHERE id = :id");
        $stmt->execute([':id' => $athId]);
        $deletedAt = $stmt->fetchColumn();

        return !empty($deletedAt);
    }

    // 14. Unauthorized roles cannot delete
    public function testUnauthorizedRolesCannotDelete(): bool
    {
        $athId = $this->createTestAthlete($this->orgId, 'UnauthDel', 'Protected', 1, 'active');

        $unauthorizedRoles = [
            ['id' => 103, 'role_id' => 3, 'slug' => 'hr_finance', 'name' => 'HR & Finance Manager', 'perms' => ['employee.view', 'finance.view']],
            ['id' => 104, 'role_id' => 4, 'slug' => 'coach', 'name' => 'Coach', 'perms' => ['athlete.view', 'team.view']],
            ['id' => 105, 'role_id' => 5, 'slug' => 'athlete', 'name' => 'Athlete', 'perms' => ['athlete.view']],
            ['id' => 106, 'role_id' => 6, 'slug' => 'venue_manager', 'name' => 'Venue & Tournament Manager', 'perms' => ['venue.view', 'tournament.view']],
            ['id' => 107, 'role_id' => 7, 'slug' => 'inventory_manager', 'name' => 'Inventory Manager', 'perms' => ['inventory.view']],
        ];

        foreach ($unauthorizedRoles as $r) {
            $session = [
                'auth' => [
                    'authenticated' => true,
                    'user' => ['id' => $r['id'], 'role_id' => $r['role_id']],
                    'role' => ['id' => $r['role_id'], 'name' => $r['name'], 'slug' => $r['slug']],
                    'organization' => ['id' => $this->orgId],
                    'permissions' => $r['perms'],
                ],
            ];
            $this->executeWebPostAction("/athletes/{$athId}/delete", [], $session);

            $stmt = $this->pdo->prepare("SELECT deleted_at FROM athletes WHERE id = :id");
            $stmt->execute([':id' => $athId]);
            if ($stmt->fetchColumn() !== null) {
                return false;
            }
        }

        return true;
    }

    // 15 & 16. Soft delete & exclusion from normal list + export
    public function testSoftDeleteAndExclusionFromNormalList(): bool
    {
        $tag = 'SoftDelExcl' . bin2hex(random_bytes(2));
        $athId = $this->createTestAthlete($this->orgId, $tag, 'ToArchive', 1, 'active');

        $before = $this->athleteService->listAthletes($this->orgId, 1, 20, $tag);
        if ($before['total'] !== 1) return false;

        $deleted = $this->athleteService->deleteAthlete($this->orgId, $athId, $this->sportsAdminUserId);
        if (!$deleted) return false;

        $afterList = $this->athleteService->listAthletes($this->orgId, 1, 20, $tag);
        $afterExport = $this->athleteService->exportAthletesXlsx($this->orgId, $tag);
        $afterGet = $this->athleteService->getAthlete($this->orgId, $athId);

        // Verify record still physically exists in database with deleted_at set
        $stmt = $this->pdo->prepare("SELECT id, deleted_at FROM athletes WHERE id = :id");
        $stmt->execute([':id' => $athId]);
        $dbRow = $stmt->fetch(PDO::FETCH_ASSOC);

        return $afterList['total'] === 0
            && empty($afterList['data'])
            && $afterExport['count'] === 0
            && $afterGet === null
            && !empty($dbRow)
            && !empty($dbRow['deleted_at']);
    }

    // 17. Historical records remain intact after soft delete
    public function testHistoricalRecordsRemainAfterSoftDelete(): bool
    {
        $athId = $this->createTestAthlete($this->orgId, 'HistKeep', 'Record', 1, 'active');
        $teamId = $this->createTestTeam($this->orgId, 'Hist Team ' . $athId, 1);
        $this->assignAthleteToTeam($this->orgId, $athId, $teamId, 1);

        // Insert guardian
        $gStmt = $this->pdo->prepare("
            INSERT INTO athlete_guardians (organization_id, athlete_id, full_name, relationship, phone, is_primary, is_emergency_contact, created_at, updated_at)
            VALUES (:org, :ath, 'Parent Guardian', 'Father', '9876543210', 1, 1, NOW(), NOW())
        ");
        $gStmt->execute([':org' => $this->orgId, ':ath' => $athId]);
        $gId = (int)$this->pdo->lastInsertId();
        $this->createdGuardianIds[] = $gId;

        // Insert document
        $dStmt = $this->pdo->prepare("
            INSERT INTO athlete_documents (organization_id, athlete_id, document_type, document_name, file_path, created_at, updated_at)
            VALUES (:org, :ath, 'id_proof', 'Aadhaar Card', 'athletes/test_doc.pdf', NOW(), NOW())
        ");
        $dStmt->execute([':org' => $this->orgId, ':ath' => $athId]);
        $dId = (int)$this->pdo->lastInsertId();
        $this->createdDocIds[] = $dId;

        // Soft delete athlete
        $this->athleteService->deleteAthlete($this->orgId, $athId, $this->sportsAdminUserId);

        // Verify team_members, athlete_guardians, athlete_documents still exist in DB and are not deleted
        $tmCount = (int)$this->pdo->query("SELECT COUNT(*) FROM team_members WHERE athlete_id = {$athId} AND team_id = {$teamId}")->fetchColumn();
        $gRow = $this->pdo->query("SELECT id, deleted_at FROM athlete_guardians WHERE id = {$gId}")->fetch(PDO::FETCH_ASSOC);
        $dRow = $this->pdo->query("SELECT id, deleted_at FROM athlete_documents WHERE id = {$dId}")->fetch(PDO::FETCH_ASSOC);

        return $tmCount === 1
            && !empty($gRow) && $gRow['deleted_at'] === null
            && !empty($dRow) && $dRow['deleted_at'] === null;
    }

    // 18. Tenant isolation for delete
    public function testTenantIsolationForDelete(): bool
    {
        $otherAthId = $this->createTestAthlete($this->otherOrgId, 'OrgTwo', 'Protected', 1, 'active');

        // Attempt delete via service and web_actions from Org 1 context
        $resService = $this->athleteService->deleteAthlete($this->orgId, $otherAthId, $this->sportsAdminUserId);
        $this->executeWebPostAction("/athletes/{$otherAthId}/delete", []);

        $stmt = $this->pdo->prepare("SELECT deleted_at FROM athletes WHERE id = :id");
        $stmt->execute([':id' => $otherAthId]);
        $deletedAt = $stmt->fetchColumn();

        return $resService === false && $deletedAt === null;
    }

    // 19. Tenant isolation for export
    public function testTenantIsolationForExport(): bool
    {
        $tag = 'IsoExp' . bin2hex(random_bytes(2));
        $org1AthId = $this->createTestAthlete($this->orgId, $tag, 'OrgOneAthlete', 1, 'active');
        $org2AthId = $this->createTestAthlete($this->otherOrgId, $tag, 'OrgTwoAthlete', 1, 'active');

        $exportOrg1 = $this->athleteService->exportAthletesXlsx($this->orgId, $tag);
        $exportedIds = array_map('intval', array_column($exportOrg1['athletes'], 'id'));

        return in_array($org1AthId, $exportedIds, true)
            && !in_array($org2AthId, $exportedIds, true)
            && $exportOrg1['count'] === 1;
    }

    // 20. Athlete detail authorization (Sports Admin, Coach squad vs non-squad, Athlete own vs other, Cross-tenant)
    public function testAthleteDetailAuthorization(): bool
    {
        $unassignedAthId = $this->createTestAthlete($this->orgId, 'DetailUnassigned', 'Player', 1, 'active');
        $otherOrgAthId = $this->createTestAthlete($this->otherOrgId, 'DetailForeign', 'Player', 1, 'active');

        // A. Sports Admin can view Org 1 athlete
        $adminSession = [
            'auth' => [
                'user' => ['id' => 102, 'role_id' => 2],
                'role' => ['id' => 2, 'name' => 'Sports Administrator', 'slug' => 'sports_admin'],
                'organization' => ['id' => 1],
                'permissions' => ['athlete.view', 'athlete.update', 'athlete.delete'],
            ],
        ];
        $adminView = $this->renderShowView($unassignedAthId, $adminSession);
        $adminCrossTenantView = $this->renderShowView($otherOrgAthId, $adminSession);

        // B. Coach can view squad athlete (#1 Aarav Patel in Coach #1's squad) but NOT unassigned athlete
        $coachSession = [
            'auth' => [
                'user' => ['id' => 104, 'role_id' => 4, 'coach_id' => 1],
                'role' => ['id' => 4, 'name' => 'Coach', 'slug' => 'coach'],
                'organization' => ['id' => 1],
                'permissions' => ['athlete.view'],
            ],
        ];
        $coachSquadView = $this->renderShowView(1, $coachSession);
        $coachNonSquadView = $this->renderShowView($unassignedAthId, $coachSession);

        // C. Athlete (#1) can view own profile (#1) but NOT another athlete ($unassignedAthId)
        $athleteSession = [
            'auth' => [
                'user' => ['id' => 105, 'role_id' => 5, 'athlete_id' => 1],
                'role' => ['id' => 5, 'name' => 'Athlete', 'slug' => 'athlete'],
                'organization' => ['id' => 1],
                'permissions' => ['athlete.view'],
            ],
        ];
        $athleteOwnView = $this->renderShowView(1, $athleteSession);
        $athleteOtherView = $this->renderShowView($unassignedAthId, $athleteSession);

        return $adminView['http_code'] === 200
            && str_contains($adminView['body'], 'DetailUnassigned Player')
            && $adminCrossTenantView['http_code'] === 404
            && $coachSquadView['http_code'] === 200
            && $coachNonSquadView['http_code'] === 403
            && $athleteOwnView['http_code'] === 200
            && $athleteOtherView['http_code'] === 403;
    }

    public function runAll(): bool
    {
        $tests = [
            '1. testAthletePagination'                     => $this->testAthletePagination(),
            '2. testAthleteUniqueRowWithMultipleTeams'     => $this->testAthleteUniqueRowWithMultipleTeams(),
            '3. testAthleteCountAccuracy'                  => $this->testAthleteCountAccuracy(),
            '4. testSearchAndPagination'                   => $this->testSearchAndPagination(),
            '5. testSportFilter'                           => $this->testSportFilter(),
            '6. testStatusFilter'                          => $this->testStatusFilter(),
            '7. testCombinedFilters'                       => $this->testCombinedFilters(),
            '8. testExportWithoutFilters'                  => $this->testExportWithoutFilters(),
            '9. testExportWithSportFilter'                 => $this->testExportWithSportFilter(),
            '10. testExportWithStatusFilter'               => $this->testExportWithStatusFilter(),
            '11. testExportWithCombinedFilters'            => $this->testExportWithCombinedFilters(),
            '12. testExportIgnoresPagination'              => $this->testExportIgnoresPagination(),
            '13. testSportsAdminHasDeletePermissionAndCanArchive' => $this->testSportsAdminHasDeletePermissionAndCanArchive(),
            '14. testUnauthorizedRolesCannotDelete'        => $this->testUnauthorizedRolesCannotDelete(),
            '15 & 16. testSoftDeleteAndExclusionFromNormalList' => $this->testSoftDeleteAndExclusionFromNormalList(),
            '17. testHistoricalRecordsRemainAfterSoftDelete' => $this->testHistoricalRecordsRemainAfterSoftDelete(),
            '18. testTenantIsolationForDelete'             => $this->testTenantIsolationForDelete(),
            '19. testTenantIsolationForExport'             => $this->testTenantIsolationForExport(),
            '20. testAthleteDetailAuthorization'           => $this->testAthleteDetailAuthorization(),
        ];

        $allPassed = true;
        foreach ($tests as $name => $passed) {
            echo ($passed ? " [PASS] " : " [FAIL] ") . $name . PHP_EOL;
            if (!$passed) {
                $allPassed = false;
            }
        }
        return $allPassed;
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    require_once dirname(__DIR__, 2) . '/bootstrap/app.php';
    require_once dirname(__DIR__, 2) . '/app/Helpers/AuthContext.php';
    echo "Running AthleteModuleCompleteFixTest suite..." . PHP_EOL;
    $suite = new AthleteModuleCompleteFixTest();
    $ok = $suite->runAll();
    exit($ok ? 0 : 1);
}
