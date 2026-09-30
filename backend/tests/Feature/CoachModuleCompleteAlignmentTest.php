<?php

namespace Tests\Feature;

use App\Services\BaseService;
use App\Services\Coach\CoachService;
use App\Services\Export\CoachXlsxExporter;
use App\Services\Rbac\PermissionService;
use PDO;
use ZipArchive;

class CoachModuleCompleteAlignmentTest
{
    private int $orgId = 1;
    private int $otherOrgId = 2;
    private int $sportsAdminUserId = 102;
    private PDO $pdo;
    private CoachService $coachService;
    private PermissionService $permissionService;

    private array $createdCoachProfileIds = [];
    private array $createdEmployeeIds = [];
    private array $createdTeamIds = [];
    private array $createdTrainingSessionIds = [];
    private array $createdDocIds = [];

    public function __construct()
    {
        $this->pdo = BaseService::getDatabaseConnection();
        $this->coachService = new CoachService($this->pdo);
        $this->permissionService = new PermissionService($this->pdo);
    }

    public function __destruct()
    {
        $this->cleanup();
    }

    private function cleanup(): void
    {
        if (!empty($this->createdTrainingSessionIds)) {
            $in = implode(',', array_map('intval', $this->createdTrainingSessionIds));
            $this->pdo->exec("DELETE FROM training_sessions WHERE id IN ({$in})");
        }
        if (!empty($this->createdDocIds)) {
            $in = implode(',', array_map('intval', $this->createdDocIds));
            $this->pdo->exec("DELETE FROM employee_documents WHERE id IN ({$in})");
        }
        if (!empty($this->createdCoachProfileIds)) {
            $in = implode(',', array_map('intval', $this->createdCoachProfileIds));
            $this->pdo->exec("DELETE FROM team_coaches WHERE coach_id IN ({$in})");
            $this->pdo->exec("DELETE FROM audit_logs WHERE table_name = 'coach_profiles' AND record_id IN ({$in})");
            $this->pdo->exec("DELETE FROM coach_profiles WHERE id IN ({$in})");
        }
        if (!empty($this->createdEmployeeIds)) {
            $in = implode(',', array_map('intval', $this->createdEmployeeIds));
            $this->pdo->exec("DELETE FROM employees WHERE id IN ({$in})");
        }
        if (!empty($this->createdTeamIds)) {
            $in = implode(',', array_map('intval', $this->createdTeamIds));
            $this->pdo->exec("DELETE FROM team_coaches WHERE team_id IN ({$in})");
            $this->pdo->exec("DELETE FROM teams WHERE id IN ({$in})");
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

    private function createTestCoach(
        int $orgId,
        string $firstName,
        string $lastName,
        string $specialization = 'Football Tactical',
        string $status = 'active',
        float $experienceYears = 8.0,
        ?string $email = null,
        ?string $phone = null
    ): int {
        $this->ensureOrgExists($orgId);
        $unique = strtoupper(bin2hex(random_bytes(3)));
        $empCode = 'EMP-CA-' . $unique;
        $coachCode = 'CCH-CA-' . $unique;

        $empStmt = $this->pdo->prepare("
            INSERT INTO employees (
                organization_id, employee_code, first_name, last_name,
                date_of_birth, gender, phone, email, designation,
                joining_date, employment_type, employment_status, created_at, updated_at
            ) VALUES (
                :org_id, :emp_code, :first, :last,
                '1986-04-12', 'male', :phone, :email, 'Senior Coach',
                CURDATE(), 'full_time', 'active', NOW(), NOW()
            )
        ");
        $empStmt->execute([
            ':org_id'   => $orgId,
            ':emp_code' => $empCode,
            ':first'    => $firstName,
            ':last'     => $lastName,
            ':phone'    => $phone ?? ('98' . rand(10000000, 99999999)),
            ':email'    => $email ?? (strtolower($firstName . '.' . $unique) . '@khelsutra.local'),
        ]);
        $empId = (int)$this->pdo->lastInsertId();
        $this->createdEmployeeIds[] = $empId;

        $cpStmt = $this->pdo->prepare("
            INSERT INTO coach_profiles (
                organization_id, employee_id, coach_code, specialization,
                qualification, certifications, experience_years, joining_date,
                license_number, status, notes, created_at, updated_at
            ) VALUES (
                :org_id, :emp_id, :code, :spec,
                'M.P.Ed', 'AFC Pro Diploma', :exp, CURDATE(),
                :lic, :status, 'Verified coaching record', NOW(), NOW()
            )
        ");
        $cpStmt->execute([
            ':org_id' => $orgId,
            ':emp_id' => $empId,
            ':code'   => $coachCode,
            ':spec'   => $specialization,
            ':exp'    => $experienceYears,
            ':lic'    => 'LIC-' . $unique,
            ':status' => $status,
        ]);
        $cpId = (int)$this->pdo->lastInsertId();
        $this->createdCoachProfileIds[] = $cpId;

        return $cpId;
    }

    private function createTestTeam(int $orgId, string $name, int $sportId = 1): int
    {
        $this->ensureOrgExists($orgId);
        $unique = strtoupper(bin2hex(random_bytes(3)));
        $stmt = $this->pdo->prepare("
            INSERT INTO teams (organization_id, team_code, name, sport_id, gender, age_group, status, created_at, updated_at)
            VALUES (:org_id, :code, :name, :sport_id, 'open', 'Senior', 'active', NOW(), NOW())
        ");
        $stmt->execute([
            ':org_id'   => $orgId,
            ':code'     => 'TM-CA-' . $unique,
            ':name'     => $name,
            ':sport_id' => $sportId,
        ]);
        $id = (int)$this->pdo->lastInsertId();
        $this->createdTeamIds[] = $id;
        return $id;
    }

    private function assignCoachToTeam(int $orgId, int $coachProfileId, int $teamId, string $role = 'head_coach', int $isPrimary = 0): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO team_coaches (organization_id, team_id, coach_id, coach_role, start_date, is_primary, created_at, updated_at)
            VALUES (:org_id, :team_id, :coach_id, :role, CURDATE(), :primary, NOW(), NOW())
        ");
        $stmt->execute([
            ':org_id'   => $orgId,
            ':team_id'  => $teamId,
            ':coach_id' => $coachProfileId,
            ':role'     => $role,
            ':primary'  => $isPrimary,
        ]);
    }

    private function executeWebAction(string $uri, array $post, array $session): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ks_coach_act_');
        $bootstrapPath = addslashes(str_replace('\\', '/', dirname(__DIR__, 2) . '/bootstrap/app.php'));
        $authHelperPath = addslashes(str_replace('\\', '/', dirname(__DIR__, 2) . '/app/Helpers/AuthContext.php'));
        $actionsPath = addslashes(str_replace('\\', '/', dirname(__DIR__, 2) . '/routes/web_actions.php'));

        $sessionExport = var_export($session, true);
        $postExport = var_export($post, true);

        $code = "<?php\n"
            . "require_once '{$bootstrapPath}';\n"
            . "require_once '{$authHelperPath}';\n"
            . "\$_SERVER['REQUEST_METHOD'] = 'POST';\n"
            . "\$_SERVER['REQUEST_URI'] = '{$uri}';\n"
            . "\$method = 'POST';\n"
            . "\$uri = '{$uri}';\n"
            . "\$_POST = {$postExport};\n"
            . "\$_SESSION = {$sessionExport};\n"
            . "require '{$actionsPath}';\n";

        file_put_contents($tempFile, $code);
        $cmd = 'php -d display_errors=0 ' . escapeshellarg($tempFile) . ' 2>&1';
        exec($cmd, $output, $exitCode);
        @unlink($tempFile);

        return ['exit_code' => $exitCode, 'output' => implode("\n", $output)];
    }

    private function renderBladeView(string $relativeViewPath, array $getParams, array $session, array $vars = []): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ks_coach_view_');
        $bootstrapPath = addslashes(str_replace('\\', '/', dirname(__DIR__, 2) . '/bootstrap/app.php'));
        $authHelperPath = addslashes(str_replace('\\', '/', dirname(__DIR__, 2) . '/app/Helpers/AuthContext.php'));
        $viewFullPath = addslashes(str_replace('\\', '/', dirname(__DIR__, 2) . '/resources/views/' . $relativeViewPath));

        $getExport = var_export($getParams, true);
        $sessionExport = var_export($session, true);
        $varsExport = var_export($vars, true);

        $code = "<?php\n"
            . "require_once '{$bootstrapPath}';\n"
            . "spl_autoload_register(function (\$class) {\n"
            . "    \$prefix = 'App\\\\';\n"
            . "    \$base = '" . addslashes(str_replace('\\', '/', dirname(__DIR__, 2) . '/app/')) . "';\n"
            . "    if (strncmp(\$prefix, \$class, strlen(\$prefix)) === 0) {\n"
            . "        \$f = \$base . str_replace('\\\\', '/', substr(\$class, strlen(\$prefix))) . '.php';\n"
            . "        if (file_exists(\$f)) require_once \$f;\n"
            . "    }\n"
            . "});\n"
            . "require_once '{$authHelperPath}';\n"
            . "\$_GET = {$getExport};\n"
            . "\$_SESSION = {$sessionExport};\n"
            . "extract({$varsExport});\n"
            . "include '{$viewFullPath}';\n";

        file_put_contents($tempFile, $code);
        $cmd = 'php -d display_errors=0 ' . escapeshellarg($tempFile) . ' 2>&1';
        exec($cmd, $output);
        @unlink($tempFile);

        return implode("\n", $output);
    }

    // 1. Coach unique-row query
    public function test1CoachUniqueRowQuery(): bool
    {
        $spec = 'RowUnique ' . bin2hex(random_bytes(3));
        $coachId = $this->createTestCoach($this->orgId, 'MultiSquad', 'Coach', $spec, 'active');

        $t1 = $this->createTestTeam($this->orgId, 'Squad One ' . substr($spec, -4));
        $t2 = $this->createTestTeam($this->orgId, 'Squad Two ' . substr($spec, -4));
        $t3 = $this->createTestTeam($this->orgId, 'Squad Three ' . substr($spec, -4));

        $this->assignCoachToTeam($this->orgId, $coachId, $t1, 'head_coach', 1);
        $this->assignCoachToTeam($this->orgId, $coachId, $t2, 'assistant_coach', 0);
        $this->assignCoachToTeam($this->orgId, $coachId, $t3, 'fitness_trainer', 0);

        $res = $this->coachService->listCoaches($this->orgId, 1, 20, null, $spec, null);
        return count($res['data']) === 1
            && (int)$res['data'][0]['coach_profile_id'] === $coachId
            && count($res['data'][0]['teams']) === 3
            && (int)$res['data'][0]['extra_teams_count'] === 2;
    }

    // 2. Coach count
    public function test2CoachCountAccuracy(): bool
    {
        $spec = 'CountCheck ' . bin2hex(random_bytes(3));
        $c1 = $this->createTestCoach($this->orgId, 'CountOne', 'Coach', $spec, 'active');
        $c2 = $this->createTestCoach($this->orgId, 'CountTwo', 'Coach', $spec, 'inactive');

        $t1 = $this->createTestTeam($this->orgId, 'Team A ' . substr($spec, -4));
        $t2 = $this->createTestTeam($this->orgId, 'Team B ' . substr($spec, -4));
        $this->assignCoachToTeam($this->orgId, $c1, $t1);
        $this->assignCoachToTeam($this->orgId, $c1, $t2);

        $all = $this->coachService->listCoaches($this->orgId, 1, 20, null, $spec, null);
        $activeOnly = $this->coachService->listCoaches($this->orgId, 1, 20, null, $spec, 'active');

        return (int)$all['total'] === 2 && (int)$activeOnly['total'] === 1;
    }

    // 3. Coach pagination
    public function test3CoachPagination(): bool
    {
        $spec = 'PagSpec ' . bin2hex(random_bytes(3));
        for ($i = 1; $i <= 24; $i++) {
            $this->createTestCoach($this->orgId, 'CoachPag' . $i, 'Staff', $spec, 'active');
        }

        $p1 = $this->coachService->listCoaches($this->orgId, 1, 20, null, $spec, null);
        $p2 = $this->coachService->listCoaches($this->orgId, 2, 20, null, $spec, null);

        return (int)$p1['total'] === 24
            && (int)$p1['total_pages'] === 2
            && count($p1['data']) === 20
            && (int)$p1['from'] === 1
            && (int)$p1['to'] === 20
            && count($p2['data']) === 4
            && (int)$p2['from'] === 21
            && (int)$p2['to'] === 24;
    }

    // 4. Search
    public function test4CoachSearch(): bool
    {
        $uniqueName = 'RathoreSearch' . strtoupper(bin2hex(random_bytes(2)));
        $coachId = $this->createTestCoach($this->orgId, $uniqueName, 'Test', 'Batting Masterclass', 'active');
        $coach = $this->coachService->getCoach($this->orgId, $coachId);

        // Search by name
        $byName = $this->coachService->listCoaches($this->orgId, 1, 20, $uniqueName, null, null);
        // Search by coach_code
        $byCode = $this->coachService->listCoaches($this->orgId, 1, 20, $coach['coach_code'], null, null);

        return (int)$byName['total'] === 1
            && (int)$byCode['total'] === 1
            && (int)$byCode['data'][0]['coach_profile_id'] === $coachId;
    }

    // 5. Specialization filter
    public function test5SpecializationFilter(): bool
    {
        $spec = 'Javelin Throw ' . bin2hex(random_bytes(3));
        $this->createTestCoach($this->orgId, 'Neeraj', 'CoachA', $spec, 'active');
        $this->createTestCoach($this->orgId, 'Other', 'CoachB', 'Shot Put General', 'active');

        $res = $this->coachService->listCoaches($this->orgId, 1, 20, null, $spec, null);
        return (int)$res['total'] === 1 && $res['data'][0]['specialization'] === $spec;
    }

    // 6. Status filter
    public function test6StatusFilter(): bool
    {
        $spec = 'Rowing Status ' . bin2hex(random_bytes(3));
        $this->createTestCoach($this->orgId, 'ActiveRow', 'Coach', $spec, 'active');
        $this->createTestCoach($this->orgId, 'InactiveRow', 'Coach', $spec, 'inactive');

        $act = $this->coachService->listCoaches($this->orgId, 1, 20, null, $spec, 'active');
        $inact = $this->coachService->listCoaches($this->orgId, 1, 20, null, $spec, 'inactive');

        return (int)$act['total'] === 1
            && $act['data'][0]['coach_status'] === 'active'
            && (int)$inact['total'] === 1
            && $inact['data'][0]['coach_status'] === 'inactive';
    }

    // 7. Combined filters
    public function test7CombinedFilters(): bool
    {
        $token = 'CMB' . strtoupper(bin2hex(random_bytes(2)));
        $spec = 'Boxing Elite ' . $token;
        $cMatch = $this->createTestCoach($this->orgId, 'Mary' . $token, 'Kom', $spec, 'active');
        $this->createTestCoach($this->orgId, 'Mary' . $token, 'Other', $spec, 'inactive');
        $this->createTestCoach($this->orgId, 'Vijender' . $token, 'Singh', $spec, 'active');

        $res = $this->coachService->listCoaches($this->orgId, 1, 20, 'Mary' . $token, $spec, 'active');
        return (int)$res['total'] === 1 && (int)$res['data'][0]['coach_profile_id'] === $cMatch;
    }

    // 8. Filter persistence across pagination in Blade view
    public function test8FilterPersistenceAcrossPagination(): bool
    {
        $spec = 'Kabaddi Pro ' . bin2hex(random_bytes(2));
        for ($i = 1; $i <= 22; $i++) {
            $this->createTestCoach($this->orgId, 'KabaddiCoach' . $i, 'Player', $spec, 'active');
        }

        $session = [
            'auth' => [
                'authenticated' => true,
                'user' => ['id' => $this->sportsAdminUserId, 'role_id' => 2],
                'role' => ['id' => 2, 'name' => 'Sports Administrator', 'slug' => 'sports_admin'],
                'organization' => ['id' => $this->orgId],
            ],
        ];

        $html = $this->renderBladeView('sports/coaches.blade.php', [
            'search' => 'KabaddiCoach',
            'specialization' => $spec,
            'status' => 'active',
            'page' => 1,
        ], $session);

        $expectedQueryPart = 'search=KabaddiCoach&amp;specialization=' . urlencode($spec) . '&amp;status=active&amp;page=2';
        return str_contains($html, $expectedQueryPart)
            && str_contains($html, 'Coaching Staff (22)')
            && str_contains($html, 'Showing <strong>1–20</strong> of <strong>22</strong> coaches');
    }

    // 9. Export without filters
    public function test9ExportWithoutFilters(): bool
    {
        $all = $this->coachService->listAllFilteredCoaches($this->orgId);
        $export = $this->coachService->exportCoachesXlsx($this->orgId);

        return $export['filename'] === 'khelsutra-coaches.xlsx'
            && (int)$export['count'] === count($all)
            && strlen($export['binary']) > 500;
    }

    // 10. Export with specialization
    public function test10ExportWithSpecialization(): bool
    {
        $spec = 'Table Tennis ' . bin2hex(random_bytes(2));
        $this->createTestCoach($this->orgId, 'Sharath', 'Kamal', $spec, 'active');
        $this->createTestCoach($this->orgId, 'Manika', 'Batra', $spec, 'inactive');

        $export = $this->coachService->exportCoachesXlsx($this->orgId, null, $spec, null);
        return (int)$export['count'] === 2 && count($export['rows']) === 2;
    }

    // 11. Export with status
    public function test11ExportWithStatus(): bool
    {
        $activeList = $this->coachService->listAllFilteredCoaches($this->orgId, null, null, 'active');
        $export = $this->coachService->exportCoachesXlsx($this->orgId, null, null, 'active');

        foreach ($export['rows'] as $row) {
            if ($row['Status'] !== 'Active') {
                return false;
            }
        }
        return (int)$export['count'] === count($activeList);
    }

    // 12. Export with combined filters
    public function test12ExportWithCombinedFilters(): bool
    {
        $token = 'EXCMB' . strtoupper(bin2hex(random_bytes(2)));
        $spec = 'Wrestling ' . $token;
        $this->createTestCoach($this->orgId, 'Sushil' . $token, 'Kumar', $spec, 'active');
        $this->createTestCoach($this->orgId, 'Sushil' . $token, 'Inactive', $spec, 'inactive');

        $export = $this->coachService->exportCoachesXlsx($this->orgId, 'Sushil' . $token, $spec, 'active');
        return (int)$export['count'] === 1
            && $export['rows'][0]['Coach Name'] === 'Sushil' . $token . ' Kumar'
            && $export['rows'][0]['Status'] === 'Active';
    }

    // 13. Export ignores pagination (80 matching coaches, 20 per page, current page 3 -> 80 rows in Excel)
    public function test13ExportIgnoresPagination80Coaches(): bool
    {
        $spec = 'Marathon80 ' . bin2hex(random_bytes(2));
        for ($i = 1; $i <= 80; $i++) {
            $this->createTestCoach($this->orgId, 'Runner' . $i, 'Coach', $spec, 'active');
        }

        $page3 = $this->coachService->listCoaches($this->orgId, 3, 20, null, $spec, 'active');
        $export = $this->coachService->exportCoachesXlsx($this->orgId, null, $spec, 'active');

        if (count($page3['data']) !== 20 || (int)$export['count'] !== 80) {
            return false;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'ks_xlsx_80_');
        file_put_contents($tmp, $export['binary']);
        $zip = new ZipArchive();
        $ok = ($zip->open($tmp) === true);
        $sheetXml = $ok ? $zip->getFromName('xl/worksheets/sheet1.xml') : '';
        if ($ok) $zip->close();
        @unlink($tmp);

        return str_contains((string)$sheetXml, 'ref="A1:N81"');
    }

    // 14. Sports Admin delete permission
    public function test14SportsAdminHasDeletePermission(): bool
    {
        $sportsAdminPayload = [
            'id' => $this->sportsAdminUserId,
            'role_id' => 2,
            'role' => ['id' => 2, 'name' => 'Sports Administrator', 'slug' => 'sports_admin'],
        ];
        return $this->permissionService->hasPermission($sportsAdminPayload, 'coach.manage', $this->orgId);
    }

    // 15. Unauthorized role cannot delete
    public function test15UnauthorizedRoleCannotDelete(): bool
    {
        $coachId = $this->createTestCoach($this->orgId, 'Protected', 'Coach', 'Gymnastics', 'active');

        $unauthRoles = [
            ['id' => 104, 'role_id' => 4, 'role' => ['id' => 4, 'name' => 'Coach', 'slug' => 'coach'], 'permissions' => ['coach.view']],
            ['id' => 105, 'role_id' => 5, 'role' => ['id' => 5, 'name' => 'Athlete', 'slug' => 'athlete'], 'permissions' => ['team.view']],
            ['id' => 107, 'role_id' => 7, 'role' => ['id' => 7, 'name' => 'Inventory Manager', 'slug' => 'inventory_manager'], 'permissions' => ['inventory.manage']],
        ];

        foreach ($unauthRoles as $user) {
            $session = [
                'auth' => [
                    'authenticated' => true,
                    'user' => $user,
                    'role' => $user['role'],
                    'organization' => ['id' => $this->orgId],
                ],
            ];
            $this->executeWebAction("/coaches/{$coachId}/delete", [], $session);
            $del = $this->pdo->query("SELECT deleted_at FROM coach_profiles WHERE id = {$coachId}")->fetchColumn();
            if ($del !== null) {
                return false;
            }
        }

        return true;
    }

    // 16. Soft delete
    public function test16SoftDelete(): bool
    {
        $spec = 'SoftDelSpec ' . bin2hex(random_bytes(2));
        $coachId = $this->createTestCoach($this->orgId, 'SoftDel', 'Target', $spec, 'active');

        $sportsAdminSession = [
            'auth' => [
                'authenticated' => true,
                'user' => ['id' => $this->sportsAdminUserId, 'role_id' => 2],
                'role' => ['id' => 2, 'name' => 'Sports Administrator', 'slug' => 'sports_admin'],
                'organization' => ['id' => $this->orgId],
            ],
        ];

        $this->executeWebAction("/coaches/{$coachId}/delete", [], $sportsAdminSession);

        $row = $this->pdo->query("SELECT id, deleted_at FROM coach_profiles WHERE id = {$coachId}")->fetch(PDO::FETCH_ASSOC);
        return !empty($row) && !empty($row['deleted_at']);
    }

    // 17. Soft-deleted Coach excluded from normal list
    public function test17SoftDeletedCoachExcludedFromNormalList(): bool
    {
        $spec = 'SoftExcludeSpec ' . bin2hex(random_bytes(2));
        $coachId = $this->createTestCoach($this->orgId, 'ExcludeMe', 'Target', $spec, 'active');
        $this->coachService->deleteCoach($this->orgId, $coachId, $this->sportsAdminUserId);

        $list = $this->coachService->listCoaches($this->orgId, 1, 20, null, $spec, null);
        $export = $this->coachService->exportCoachesXlsx($this->orgId, null, $spec, null);
        return (int)$list['total'] === 0 && empty($list['data']) && (int)$export['count'] === 0;
    }

    // 18. Coach history preserved after soft delete
    public function test18CoachHistoryPreserved(): bool
    {
        $coachId = $this->createTestCoach($this->orgId, 'History', 'Keeper', 'Volleyball', 'active');
        $teamId = $this->createTestTeam($this->orgId, 'Volley Squad');
        $this->assignCoachToTeam($this->orgId, $coachId, $teamId, 'head_coach', 1);

        $tsRef = 'TRN-HIST-' . strtoupper(bin2hex(random_bytes(3)));
        $this->pdo->prepare("
            INSERT INTO training_sessions (organization_id, training_reference, team_id, coach_id, title, training_date, start_time, end_time, status, created_at, updated_at)
            VALUES (:org_id, :ref, :team_id, :coach_id, 'Serve Practice', CURDATE(), '09:00:00', '11:00:00', 'completed', NOW(), NOW())
        ")->execute([':org_id' => $this->orgId, ':ref' => $tsRef, ':team_id' => $teamId, ':coach_id' => $coachId]);
        $tsId = (int)$this->pdo->lastInsertId();
        $this->createdTrainingSessionIds[] = $tsId;

        $this->coachService->deleteCoach($this->orgId, $coachId, $this->sportsAdminUserId);

        $tcExists = (int)$this->pdo->query("SELECT COUNT(*) FROM team_coaches WHERE coach_id = {$coachId} AND team_id = {$teamId}")->fetchColumn();
        $tsExists = (int)$this->pdo->query("SELECT COUNT(*) FROM training_sessions WHERE id = {$tsId} AND coach_id = {$coachId}")->fetchColumn();
        $auditExists = (int)$this->pdo->query("SELECT COUNT(*) FROM audit_logs WHERE table_name = 'coach_profiles' AND record_id = {$coachId} AND action = 'COACH_DELETE'")->fetchColumn();

        return $tcExists === 1 && $tsExists === 1 && $auditExists >= 1;
    }

    // 19. Tenant isolation
    public function test19TenantIsolation(): bool
    {
        $otherSpec = 'ForeignSpec ' . bin2hex(random_bytes(2));
        $foreignCoachId = $this->createTestCoach($this->otherOrgId, 'OtherOrg', 'Coach', $otherSpec, 'active');

        $list = $this->coachService->listCoaches($this->orgId, 1, 20, null, $otherSpec, null);
        $detail = $this->coachService->getCoach($this->orgId, $foreignCoachId);
        $export = $this->coachService->exportCoachesXlsx($this->orgId, null, $otherSpec, null);
        $deleted = $this->coachService->deleteCoach($this->orgId, $foreignCoachId, $this->sportsAdminUserId);

        return (int)$list['total'] === 0
            && $detail === null
            && (int)$export['count'] === 0
            && $deleted === false;
    }

    // 20. Coach detail authorization & clean UI rendering
    public function test20CoachDetailAuthorizationAndUi(): bool
    {
        $coachId = $this->createTestCoach($this->orgId, 'DetailView', 'Coach', 'Tactical Analysis', 'active', 11.0);

        $sportsAdminSession = [
            'auth' => [
                'authenticated' => true,
                'user' => ['id' => $this->sportsAdminUserId, 'role_id' => 2],
                'role' => ['id' => 2, 'name' => 'Sports Administrator', 'slug' => 'sports_admin'],
                'organization' => ['id' => $this->orgId],
            ],
        ];
        $htmlAdmin = $this->renderBladeView('sports/coaches-show.blade.php', [], $sportsAdminSession, ['id' => $coachId]);

        // Athlete role must be denied (403)
        $athleteSession = [
            'auth' => [
                'authenticated' => true,
                'user' => ['id' => 105, 'role_id' => 5, 'athlete_id' => 1],
                'role' => ['id' => 5, 'name' => 'Athlete', 'slug' => 'athlete'],
                'organization' => ['id' => $this->orgId],
            ],
        ];
        $htmlAthlete = $this->renderBladeView('sports/coaches-show.blade.php', [], $athleteSession, ['id' => $coachId]);

        return str_contains($htmlAdmin, 'Coaching Credentials &amp; Experience')
            && str_contains($htmlAdmin, 'Personal &amp; Employment Details')
            && str_contains($htmlAdmin, 'Assigned Teams &amp; Squads')
            && str_contains($htmlAdmin, 'Recent Training Sessions')
            && str_contains($htmlAdmin, 'Emergency Contact')
            && str_contains($htmlAdmin, '11 Years')
            && !str_contains($htmlAdmin, 'Organisation ID')
            && str_contains($htmlAthlete, '403 — Access Forbidden');
    }

    // 21. Add Coach validation
    public function test21AddCoachValidation(): bool
    {
        $missingFields = [
            ['first_name' => '', 'last_name' => 'Rathore', 'specialization' => 'Batting'],
            ['first_name' => 'Vikram', 'last_name' => '', 'specialization' => 'Batting'],
            ['first_name' => 'Vikram', 'last_name' => 'Rathore', 'specialization' => ''],
        ];

        foreach ($missingFields as $payload) {
            $caught = false;
            try {
                $this->coachService->createCoach($this->orgId, $payload);
            } catch (\InvalidArgumentException $e) {
                $caught = true;
            }
            if (!$caught) {
                return false;
            }
        }
        return true;
    }

    // 22. Edit Coach validation
    public function test22EditCoachValidation(): bool
    {
        $coachId = $this->createTestCoach($this->orgId, 'ValidEdit', 'Coach', 'Sprint', 'active');
        $invalidPayloads = [
            ['first_name' => ''],
            ['last_name' => ''],
            ['specialization' => ''],
            ['status' => 'invalid_status_val'],
        ];

        foreach ($invalidPayloads as $payload) {
            $caught = false;
            try {
                $this->coachService->updateCoach($this->orgId, $coachId, $payload);
            } catch (\InvalidArgumentException $e) {
                $caught = true;
            }
            if (!$caught) {
                return false;
            }
        }
        return true;
    }

    // 23. Team assignment authorization
    public function test23TeamAssignmentAuthorization(): bool
    {
        $foreignTeamId = $this->createTestTeam($this->otherOrgId, 'Foreign Org Squad');
        $myCoachId = $this->createTestCoach($this->orgId, 'Local', 'Coach', 'Batting', 'active');

        $blockedOnCreate = false;
        try {
            $this->coachService->createCoach($this->orgId, [
                'first_name'     => 'New',
                'last_name'      => 'Coach',
                'date_of_birth'  => '1985-05-10',
                'gender'         => 'male',
                'designation'    => 'Head Coach',
                'specialization' => 'Batting',
                'status'         => 'active',
                'team_id'        => $foreignTeamId,
            ]);
        } catch (\InvalidArgumentException $e) {
            $blockedOnCreate = true;
        }

        $blockedOnUpdate = false;
        try {
            $this->coachService->updateCoach($this->orgId, $myCoachId, [
                'team_id' => $foreignTeamId,
            ]);
        } catch (\InvalidArgumentException $e) {
            $blockedOnUpdate = true;
        }

        return $blockedOnCreate && $blockedOnUpdate;
    }

    public function runAll(): bool
    {
        $tests = [
            '1. Coach unique-row query (multi-team deduplication)' => 'test1CoachUniqueRowQuery',
            '2. Coach count accuracy'                              => 'test2CoachCountAccuracy',
            '3. Coach server-side pagination (20 per page)'        => 'test3CoachPagination',
            '4. Coach search (name, code, specialization)'         => 'test4CoachSearch',
            '5. Specialization filter'                             => 'test5SpecializationFilter',
            '6. Status filter'                                     => 'test6StatusFilter',
            '7. Combined filters (search + specialization + status)' => 'test7CombinedFilters',
            '8. Filter persistence across pagination links'        => 'test8FilterPersistenceAcrossPagination',
            '9. Export without filters (.xlsx)'                    => 'test9ExportWithoutFilters',
            '10. Export with specialization filter'                => 'test10ExportWithSpecialization',
            '11. Export with status filter'                        => 'test11ExportWithStatus',
            '12. Export with combined filters'                     => 'test12ExportWithCombinedFilters',
            '13. Export ignores pagination (80 rows across 4 pages)' => 'test13ExportIgnoresPagination80Coaches',
            '14. Sports Admin delete permission'                   => 'test14SportsAdminHasDeletePermission',
            '15. Unauthorized role cannot delete'                  => 'test15UnauthorizedRoleCannotDelete',
            '16. Soft delete sets deleted_at without hard-delete'  => 'test16SoftDelete',
            '17. Soft-deleted Coach excluded from normal list'     => 'test17SoftDeletedCoachExcludedFromNormalList',
            '18. Coach history preserved on archive'               => 'test18CoachHistoryPreserved',
            '19. Tenant isolation (list/view/edit/delete/export)'  => 'test19TenantIsolation',
            '20. Coach detail authorization & UI redesign'         => 'test20CoachDetailAuthorizationAndUi',
            '21. Add Coach validation'                             => 'test21AddCoachValidation',
            '22. Edit Coach validation'                            => 'test22EditCoachValidation',
            '23. Team assignment authorization (cross-org blocked)'=> 'test23TeamAssignmentAuthorization',
        ];

        $passed = 0;
        $failed = 0;

        echo "==========================================================\n";
        echo "  KHELSUTRA — COACH MODULE COMPLETE ALIGNMENT TEST SUITE\n";
        echo "==========================================================\n";

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

        echo "----------------------------------------------------------\n";
        echo "Total: " . ($passed + $failed) . " | Passed: {$passed} | Failed: {$failed}\n";
        echo "==========================================================\n";

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

    $suite = new CoachModuleCompleteAlignmentTest();
    exit($suite->runAll() ? 0 : 1);
}
