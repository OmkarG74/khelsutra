<?php

namespace Tests\Feature;

use App\Services\BaseService;
use App\Services\Team\TeamService;
use PDO;

class TeamCoachWebActionTest
{
    private int $orgId = 1; // Apex Sports Academy
    private int $otherOrgId = 999; // Separate tenant
    private int $userId = 5; // Sports Admin user
    private PDO $pdo;
    private TeamService $service;

    // Track test fixture IDs for automatic cleanup
    private array $createdTeamIds = [];
    private array $createdCoachIds = [];
    private array $createdEmployeeIds = [];

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
            $this->pdo->exec("DELETE FROM team_coaches WHERE team_id IN ({$inTeams})");
            $this->pdo->exec("DELETE FROM teams WHERE id IN ({$inTeams})");
        }
        if (!empty($this->createdCoachIds)) {
            $inCoaches = implode(',', array_map('intval', $this->createdCoachIds));
            $this->pdo->exec("DELETE FROM team_coaches WHERE coach_id IN ({$inCoaches})");
            $this->pdo->exec("DELETE FROM coach_profiles WHERE id IN ({$inCoaches})");
        }
        if (!empty($this->createdEmployeeIds)) {
            $inEmployees = implode(',', array_map('intval', $this->createdEmployeeIds));
            $this->pdo->exec("DELETE FROM employees WHERE id IN ({$inEmployees})");
        }
    }

    /**
     * Helper to execute web_actions.php in an isolated PHP subprocess.
     */
    private function executeWebAction(string $uri, array $post, array $session = []): int
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ks_cch_');
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
                    'permissions' => ['team.coaches.manage', 'team.manage', 'team.view'],
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

    private function createTestTeam(int $orgId, string $name = 'Coach Test Team'): int
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

    private function createTestCoach(int $orgId, string $status = 'active', bool $isDeleted = false, bool $empDeleted = false): int
    {
        $code = 'EMP-' . substr(uniqid(), -6);
        $empStmt = $this->pdo->prepare("
            INSERT INTO employees (organization_id, employee_code, first_name, last_name, country, employment_type, employment_status, created_at, updated_at, deleted_at)
            VALUES (:org_id, :code, :fn, :ln, 'India', 'full_time', 'active', NOW(), NOW(), :deleted_at)
        ");
        $empStmt->execute([
            ':org_id' => $orgId,
            ':code' => $code,
            ':fn' => 'Coach',
            ':ln' => uniqid(),
            ':deleted_at' => $empDeleted ? date('Y-m-d H:i:s') : null
        ]);
        $empId = (int)$this->pdo->lastInsertId();
        $this->createdEmployeeIds[] = $empId;

        $cCode = 'CCH-' . substr(uniqid(), -6);
        $cStmt = $this->pdo->prepare("
            INSERT INTO coach_profiles (organization_id, employee_id, coach_code, specialization, status, created_at, updated_at, deleted_at)
            VALUES (:org_id, :emp_id, :code, 'Tactical Training', :status, NOW(), NOW(), :deleted_at)
        ");
        $cStmt->execute([
            ':org_id' => $orgId,
            ':emp_id' => $empId,
            ':code' => $cCode,
            ':status' => $status,
            ':deleted_at' => $isDeleted ? date('Y-m-d H:i:s') : null
        ]);
        $coachId = (int)$this->pdo->lastInsertId();
        $this->createdCoachIds[] = $coachId;

        return $coachId;
    }

    /**
     * TEST 1: Successful coach assignment
     */
    public function testSuccessfulCoachAssignment(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $coachId = $this->createTestCoach($this->orgId);

        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $coachId,
            'coach_role' => 'assistant_coach'
        ]);

        $stmt = $this->pdo->prepare("
            SELECT * FROM team_coaches 
            WHERE team_id = :team_id AND coach_id = :coach_id AND (end_date IS NULL OR end_date > CURDATE())
        ");
        $stmt->execute([':team_id' => $teamId, ':coach_id' => $coachId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false && (int)$row['coach_id'] === $coachId;
    }

    /**
     * TEST 2: Correct coach role is stored
     */
    public function testCorrectRoleStored(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $coachId = $this->createTestCoach($this->orgId);

        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $coachId,
            'coach_role' => 'fitness_coach'
        ]);

        $stmt = $this->pdo->prepare("
            SELECT coach_role FROM team_coaches 
            WHERE team_id = :team_id AND coach_id = :coach_id AND (end_date IS NULL OR end_date > CURDATE())
        ");
        $stmt->execute([':team_id' => $teamId, ':coach_id' => $coachId]);
        $role = $stmt->fetchColumn();

        return $role === 'fitness_coach';
    }

    /**
     * TEST 3: Head coach default primary behavior
     */
    public function testHeadCoachDefaultPrimaryBehavior(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $coachId = $this->createTestCoach($this->orgId);

        // Assigning head_coach without specifying is_primary should default to primary = 1
        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $coachId,
            'coach_role' => 'head_coach'
        ]);

        $stmt = $this->pdo->prepare("
            SELECT is_primary FROM team_coaches 
            WHERE team_id = :team_id AND coach_id = :coach_id AND (end_date IS NULL OR end_date > CURDATE())
        ");
        $stmt->execute([':team_id' => $teamId, ':coach_id' => $coachId]);
        $isPrimary = $stmt->fetchColumn();

        return (int)$isPrimary === 1;
    }

    /**
     * TEST 4: Explicit primary assignment
     */
    public function testExplicitPrimaryAssignment(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $coachId = $this->createTestCoach($this->orgId);

        // Explicitly setting is_primary = 1 with assistant_coach
        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $coachId,
            'coach_role' => 'assistant_coach',
            'is_primary' => '1'
        ]);

        $stmt = $this->pdo->prepare("
            SELECT is_primary, coach_role FROM team_coaches 
            WHERE team_id = :team_id AND coach_id = :coach_id AND (end_date IS NULL OR end_date > CURDATE())
        ");
        $stmt->execute([':team_id' => $teamId, ':coach_id' => $coachId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false && (int)$row['is_primary'] === 1 && $row['coach_role'] === 'assistant_coach';
    }

    /**
     * TEST 5: Existing primary coach conflict is handled safely
     */
    public function testPrimaryCoachConflictHandledSafely(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $coach1 = $this->createTestCoach($this->orgId);
        $coach2 = $this->createTestCoach($this->orgId);

        // Assign coach 1 as primary
        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $coach1,
            'coach_role' => 'head_coach',
            'is_primary' => '1'
        ]);

        // Assign coach 2 as primary
        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $coach2,
            'coach_role' => 'head_coach',
            'is_primary' => '1'
        ]);

        // Coach 1 should now have is_primary = 0
        $stmt1 = $this->pdo->prepare("
            SELECT is_primary FROM team_coaches 
            WHERE team_id = :team_id AND coach_id = :coach_id AND (end_date IS NULL OR end_date > CURDATE())
        ");
        $stmt1->execute([':team_id' => $teamId, ':coach_id' => $coach1]);
        $p1 = (int)$stmt1->fetchColumn();

        // Coach 2 should have is_primary = 1
        $stmt2 = $this->pdo->prepare("
            SELECT is_primary FROM team_coaches 
            WHERE team_id = :team_id AND coach_id = :coach_id AND (end_date IS NULL OR end_date > CURDATE())
        ");
        $stmt2->execute([':team_id' => $teamId, ':coach_id' => $coach2]);
        $p2 = (int)$stmt2->fetchColumn();

        // Only one active primary coach should exist for the team
        $countStmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM team_coaches 
            WHERE team_id = :team_id AND is_primary = 1 AND (end_date IS NULL OR end_date > CURDATE())
        ");
        $countStmt->execute([':team_id' => $teamId]);
        $primaryCount = (int)$countStmt->fetchColumn();

        return $p1 === 0 && $p2 === 1 && $primaryCount === 1;
    }

    /**
     * TEST 6: Duplicate active assignment is idempotent
     */
    public function testDuplicateActiveAssignmentIsIdempotent(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $coachId = $this->createTestCoach($this->orgId);

        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $coachId,
            'coach_role' => 'fitness_coach',
            'is_primary' => '0'
        ]);

        // Re-assign identical details
        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $coachId,
            'coach_role' => 'fitness_coach',
            'is_primary' => '0'
        ]);

        $countStmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM team_coaches 
            WHERE team_id = :team_id AND coach_id = :coach_id AND (end_date IS NULL OR end_date > CURDATE())
        ");
        $countStmt->execute([':team_id' => $teamId, ':coach_id' => $coachId]);
        $activeCount = (int)$countStmt->fetchColumn();

        return $activeCount === 1;
    }

    /**
     * TEST 7: Multiple supported coach roles work
     */
    public function testMultipleSupportedCoachRolesWork(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $roles = ['assistant_coach', 'fitness_coach', 'other'];

        foreach ($roles as $role) {
            $coachId = $this->createTestCoach($this->orgId);
            $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
                'coach_id' => $coachId,
                'coach_role' => $role,
                'is_primary' => '0'
            ]);

            $stmt = $this->pdo->prepare("
                SELECT coach_role FROM team_coaches 
                WHERE team_id = :team_id AND coach_id = :coach_id AND (end_date IS NULL OR end_date > CURDATE())
            ");
            $stmt->execute([':team_id' => $teamId, ':coach_id' => $coachId]);
            $storedRole = $stmt->fetchColumn();
            if ($storedRole !== $role) {
                return false;
            }
        }

        return true;
    }

    /**
     * TEST 8: Invalid coach role rejected
     */
    public function testInvalidRoleRejected(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $coachId = $this->createTestCoach($this->orgId);

        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $coachId,
            'coach_role' => 'invalid_super_coach'
        ]);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM team_coaches 
            WHERE team_id = :team_id AND coach_id = :coach_id
        ");
        $stmt->execute([':team_id' => $teamId, ':coach_id' => $coachId]);
        $count = (int)$stmt->fetchColumn();

        return $count === 0;
    }

    /**
     * TEST 9: Cross-tenant team rejected
     */
    public function testCrossTenantTeamRejected(): bool
    {
        // Team in organization 999
        $otherTeamId = $this->createTestTeam($this->otherOrgId);
        $coachId = $this->createTestCoach($this->orgId);

        // User in organization 1 attempts to assign to organization 999 team
        $this->executeWebAction("/teams/{$otherTeamId}/coaches/assign", [
            'coach_id' => $coachId,
            'coach_role' => 'head_coach'
        ]);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM team_coaches WHERE team_id = :team_id
        ");
        $stmt->execute([':team_id' => $otherTeamId]);
        $count = (int)$stmt->fetchColumn();

        return $count === 0;
    }

    /**
     * TEST 10: Cross-tenant coach rejected
     */
    public function testCrossTenantCoachRejected(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        // Coach in organization 999
        $otherCoachId = $this->createTestCoach($this->otherOrgId);

        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $otherCoachId,
            'coach_role' => 'head_coach'
        ]);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM team_coaches WHERE team_id = :team_id AND coach_id = :coach_id
        ");
        $stmt->execute([':team_id' => $teamId, ':coach_id' => $otherCoachId]);
        $count = (int)$stmt->fetchColumn();

        return $count === 0;
    }

    /**
     * TEST 11: Inactive coach rejected
     */
    public function testInactiveCoachRejected(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $inactiveCoachId = $this->createTestCoach($this->orgId, 'inactive');

        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $inactiveCoachId,
            'coach_role' => 'assistant_coach'
        ]);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM team_coaches WHERE team_id = :team_id AND coach_id = :coach_id
        ");
        $stmt->execute([':team_id' => $teamId, ':coach_id' => $inactiveCoachId]);
        $count = (int)$stmt->fetchColumn();

        return $count === 0;
    }

    /**
     * TEST 12: Deleted coach rejected
     */
    public function testDeletedCoachRejected(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $deletedCoachId = $this->createTestCoach($this->orgId, 'active', true);

        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $deletedCoachId,
            'coach_role' => 'assistant_coach'
        ]);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM team_coaches WHERE team_id = :team_id AND coach_id = :coach_id
        ");
        $stmt->execute([':team_id' => $teamId, ':coach_id' => $deletedCoachId]);
        $count = (int)$stmt->fetchColumn();

        return $count === 0;
    }

    /**
     * TEST 13: Unauthorized user cannot assign coach
     */
    public function testUnauthorizedUserCannotAssign(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $coachId = $this->createTestCoach($this->orgId);

        // Role 6: Athlete (has only athlete permissions, no team.coaches.manage)
        $athleteSession = [
            'auth' => [
                'authenticated' => true,
                'user' => [
                    'id' => 99,
                    'email' => 'athlete@khelsutra.local',
                    'role_id' => 6,
                    'role' => ['name' => 'Athlete', 'slug' => 'athlete'],
                    'permissions' => ['team.view'],
                    'status' => 'active'
                ],
                'organization' => [
                    'id' => $this->orgId,
                    'name' => 'Apex Sports Academy',
                    'code' => 'APEX'
                ]
            ]
        ];

        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $coachId,
            'coach_role' => 'head_coach'
        ], $athleteSession);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM team_coaches WHERE team_id = :team_id AND coach_id = :coach_id
        ");
        $stmt->execute([':team_id' => $teamId, ':coach_id' => $coachId]);
        $count = (int)$stmt->fetchColumn();

        return $count === 0;
    }

    /**
     * TEST 14: Successful coach removal
     */
    public function testSuccessfulRemoval(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $coachId = $this->createTestCoach($this->orgId);

        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $coachId,
            'coach_role' => 'assistant_coach'
        ]);

        $this->executeWebAction("/teams/{$teamId}/coaches/{$coachId}/remove", []);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM team_coaches 
            WHERE team_id = :team_id AND coach_id = :coach_id AND (end_date IS NULL OR end_date > CURDATE())
        ");
        $stmt->execute([':team_id' => $teamId, ':coach_id' => $coachId]);
        $activeCount = (int)$stmt->fetchColumn();

        return $activeCount === 0;
    }

    /**
     * TEST 15: Removal preserves team_coaches historical row
     */
    public function testRemovalPreservesTeamCoachesHistoricalRow(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $coachId = $this->createTestCoach($this->orgId);

        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $coachId,
            'coach_role' => 'assistant_coach'
        ]);

        $this->executeWebAction("/teams/{$teamId}/coaches/{$coachId}/remove", []);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM team_coaches 
            WHERE team_id = :team_id AND coach_id = :coach_id
        ");
        $stmt->execute([':team_id' => $teamId, ':coach_id' => $coachId]);
        $totalCount = (int)$stmt->fetchColumn();

        return $totalCount === 1;
    }

    /**
     * TEST 16: Removal sets end_date correctly
     */
    public function testRemovalSetsEndDateCorrectly(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $coachId = $this->createTestCoach($this->orgId);

        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $coachId,
            'coach_role' => 'fitness_coach'
        ]);

        $this->executeWebAction("/teams/{$teamId}/coaches/{$coachId}/remove", []);

        $stmt = $this->pdo->prepare("
            SELECT end_date FROM team_coaches 
            WHERE team_id = :team_id AND coach_id = :coach_id
        ");
        $stmt->execute([':team_id' => $teamId, ':coach_id' => $coachId]);
        $endDate = $stmt->fetchColumn();

        return $endDate === date('Y-m-d');
    }

    /**
     * TEST 17: Removal clears is_primary
     */
    public function testRemovalClearsIsPrimary(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $coachId = $this->createTestCoach($this->orgId);

        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $coachId,
            'coach_role' => 'head_coach',
            'is_primary' => '1'
        ]);

        $this->executeWebAction("/teams/{$teamId}/coaches/{$coachId}/remove", []);

        $stmt = $this->pdo->prepare("
            SELECT is_primary FROM team_coaches 
            WHERE team_id = :team_id AND coach_id = :coach_id
        ");
        $stmt->execute([':team_id' => $teamId, ':coach_id' => $coachId]);
        $isPrimary = (int)$stmt->fetchColumn();

        return $isPrimary === 0;
    }

    /**
     * TEST 18: Unauthorized user cannot remove coach
     */
    public function testUnauthorizedUserCannotRemove(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $coachId = $this->createTestCoach($this->orgId);

        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $coachId,
            'coach_role' => 'assistant_coach'
        ]);

        $athleteSession = [
            'auth' => [
                'authenticated' => true,
                'user' => [
                    'id' => 99,
                    'email' => 'athlete@khelsutra.local',
                    'role_id' => 6,
                    'role' => ['name' => 'Athlete', 'slug' => 'athlete'],
                    'permissions' => ['team.view'],
                    'status' => 'active'
                ],
                'organization' => [
                    'id' => $this->orgId,
                    'name' => 'Apex Sports Academy',
                    'code' => 'APEX'
                ]
            ]
        ];

        $this->executeWebAction("/teams/{$teamId}/coaches/{$coachId}/remove", [], $athleteSession);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM team_coaches 
            WHERE team_id = :team_id AND coach_id = :coach_id AND (end_date IS NULL OR end_date > CURDATE())
        ");
        $stmt->execute([':team_id' => $teamId, ':coach_id' => $coachId]);
        $activeCount = (int)$stmt->fetchColumn();

        return $activeCount === 1;
    }

    /**
     * TEST 19: Assignment audit created
     */
    public function testAssignmentAuditCreated(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $coachId = $this->createTestCoach($this->orgId);

        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $coachId,
            'coach_role' => 'fitness_coach'
        ]);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM audit_logs 
            WHERE organization_id = :org_id 
              AND action = 'TEAM_COACH_ASSIGN' 
              AND description LIKE :desc
        ");
        $stmt->execute([
            ':org_id' => $this->orgId,
            ':desc' => "%Assigned coach #{$coachId}%to team #{$teamId}%"
        ]);
        $count = (int)$stmt->fetchColumn();

        return $count >= 1;
    }

    /**
     * TEST 20: Removal audit created
     */
    public function testRemovalAuditCreated(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $coachId = $this->createTestCoach($this->orgId);

        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $coachId,
            'coach_role' => 'other'
        ]);

        $this->executeWebAction("/teams/{$teamId}/coaches/{$coachId}/remove", []);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM audit_logs 
            WHERE organization_id = :org_id 
              AND action = 'TEAM_COACH_REMOVE' 
              AND description LIKE :desc
        ");
        $stmt->execute([
            ':org_id' => $this->orgId,
            ':desc' => "%Removed coach #{$coachId}%from team #{$teamId}%"
        ]);
        $count = (int)$stmt->fetchColumn();

        return $count >= 1;
    }

    /**
     * TEST 21: Invalid IDs handled safely
     */
    public function testInvalidIdsHandledSafely(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);

        // Invalid coach ID <= 0
        $code1 = $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => '0',
            'coach_role' => 'head_coach'
        ]);

        // Non-existent coach ID
        $code2 = $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => '999999',
            'coach_role' => 'head_coach'
        ]);

        // Removal with invalid coach ID
        $code3 = $this->executeWebAction("/teams/{$teamId}/coaches/999999/remove", []);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM team_coaches WHERE team_id = :team_id");
        $stmt->execute([':team_id' => $teamId]);
        $count = (int)$stmt->fetchColumn();

        return $count === 0;
    }

    /**
     * TEST 22: Fake POST organization_id cannot bypass tenant isolation
     */
    public function testFakePostOrganizationIdCannotBypassTenantIsolation(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $otherCoachId = $this->createTestCoach($this->otherOrgId);

        // Attacker attempts to provide organization_id in POST payload to access coach from org 999
        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'organization_id' => $this->otherOrgId,
            'coach_id' => $otherCoachId,
            'coach_role' => 'head_coach'
        ]);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM team_coaches WHERE team_id = :team_id AND coach_id = :coach_id
        ");
        $stmt->execute([':team_id' => $teamId, ':coach_id' => $otherCoachId]);
        $count = (int)$stmt->fetchColumn();

        return $count === 0;
    }

    /**
     * TEST 23: Unchecked primary checkbox and default semantics behave correctly
     */
    public function testUncheckedPrimaryCheckboxDefaultSemanticsBehaveCorrectly(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $coach1 = $this->createTestCoach($this->orgId);
        $coach2 = $this->createTestCoach($this->orgId);

        // 1. Explicitly unchecked checkbox sends is_primary = '0'
        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $coach1,
            'coach_role' => 'head_coach',
            'is_primary' => '0'
        ]);

        $stmt1 = $this->pdo->prepare("
            SELECT is_primary FROM team_coaches 
            WHERE team_id = :team_id AND coach_id = :coach_id AND (end_date IS NULL OR end_date > CURDATE())
        ");
        $stmt1->execute([':team_id' => $teamId, ':coach_id' => $coach1]);
        $p1 = (int)$stmt1->fetchColumn();

        // 2. Omitted is_primary field (default convention) => head_coach becomes primary
        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $coach2,
            'coach_role' => 'head_coach'
        ]);

        $stmt2 = $this->pdo->prepare("
            SELECT is_primary FROM team_coaches 
            WHERE team_id = :team_id AND coach_id = :coach_id AND (end_date IS NULL OR end_date > CURDATE())
        ");
        $stmt2->execute([':team_id' => $teamId, ':coach_id' => $coach2]);
        $p2 = (int)$stmt2->fetchColumn();

        return $p1 === 0 && $p2 === 1;
    }

    /**
     * TEST 24: Removed coach is no longer returned as active staff by TeamService
     */
    public function testRemovedCoachIsNoLongerReturnedAsActiveStaff(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $coachId = $this->createTestCoach($this->orgId);

        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $coachId,
            'coach_role' => 'assistant_coach'
        ]);

        $teamBefore = $this->service->getTeam($this->orgId, $teamId);
        $activeBefore = array_filter($teamBefore['coaches'] ?? [], fn($c) => (int)$c['coach_id'] === $coachId);

        $this->executeWebAction("/teams/{$teamId}/coaches/{$coachId}/remove", []);

        $teamAfter = $this->service->getTeam($this->orgId, $teamId);
        $activeAfter = array_filter($teamAfter['coaches'] ?? [], fn($c) => (int)$c['coach_id'] === $coachId);

        return count($activeBefore) === 1 && count($activeAfter) === 0;
    }

    /**
     * TEST 25: Reassignment after historical removal does not corrupt history
     */
    public function testReassignmentAfterHistoricalRemovalDoesNotCorruptHistory(): bool
    {
        $teamId = $this->createTestTeam($this->orgId);
        $coachId = $this->createTestCoach($this->orgId);

        // 1. Initial assignment as fitness coach
        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $coachId,
            'coach_role' => 'fitness_coach',
            'is_primary' => '0'
        ]);

        // 2. Remove coach
        $this->executeWebAction("/teams/{$teamId}/coaches/{$coachId}/remove", []);

        // 3. Re-assign coach as assistant coach
        $this->executeWebAction("/teams/{$teamId}/coaches/assign", [
            'coach_id' => $coachId,
            'coach_role' => 'assistant_coach',
            'is_primary' => '0'
        ]);

        // Total rows in team_coaches should be 2: one historical (with end_date), one current (end_date NULL)
        $allRowsStmt = $this->pdo->prepare("
            SELECT id, coach_role, end_date FROM team_coaches 
            WHERE team_id = :team_id AND coach_id = :coach_id 
            ORDER BY id ASC
        ");
        $allRowsStmt->execute([':team_id' => $teamId, ':coach_id' => $coachId]);
        $rows = $allRowsStmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) !== 2) {
            return false;
        }

        $historical = $rows[0];
        $current = $rows[1];

        $histOk = $historical['coach_role'] === 'fitness_coach' && !empty($historical['end_date']);
        $currOk = $current['coach_role'] === 'assistant_coach' && empty($current['end_date']);

        return $histOk && $currOk;
    }
}

// Direct CLI execution support
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    if (file_exists(dirname(__DIR__, 2) . '/bootstrap/app.php')) {
        require_once dirname(__DIR__, 2) . '/bootstrap/app.php';
    }
    echo "Running TeamCoachWebActionTest suite...\n";
    $test = new TeamCoachWebActionTest();

    $methods = [
        'testSuccessfulCoachAssignment',
        'testCorrectRoleStored',
        'testHeadCoachDefaultPrimaryBehavior',
        'testExplicitPrimaryAssignment',
        'testPrimaryCoachConflictHandledSafely',
        'testDuplicateActiveAssignmentIsIdempotent',
        'testMultipleSupportedCoachRolesWork',
        'testInvalidRoleRejected',
        'testCrossTenantTeamRejected',
        'testCrossTenantCoachRejected',
        'testInactiveCoachRejected',
        'testDeletedCoachRejected',
        'testUnauthorizedUserCannotAssign',
        'testSuccessfulRemoval',
        'testRemovalPreservesTeamCoachesHistoricalRow',
        'testRemovalSetsEndDateCorrectly',
        'testRemovalClearsIsPrimary',
        'testUnauthorizedUserCannotRemove',
        'testAssignmentAuditCreated',
        'testRemovalAuditCreated',
        'testInvalidIdsHandledSafely',
        'testFakePostOrganizationIdCannotBypassTenantIsolation',
        'testUncheckedPrimaryCheckboxDefaultSemanticsBehaveCorrectly',
        'testRemovedCoachIsNoLongerReturnedAsActiveStaff',
        'testReassignmentAfterHistoricalRemovalDoesNotCorruptHistory',
    ];

    $passed = 0;
    $failed = 0;

    foreach ($methods as $method) {
        try {
            $result = $test->$method();
            if ($result) {
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
