<?php

namespace Tests\Feature;

use App\Services\Training\TrainingService;
use App\Services\Attendance\AttendanceService;
use App\Services\Team\TeamService;
use App\Services\Coach\CoachService;
use App\Services\Athlete\AthleteService;
use App\Services\Rbac\PermissionService;
use App\Services\BaseService;
use PDO;

class TrainingSessionModuleComprehensiveTest
{
    private int $orgId = 1;
    private int $otherOrgId = 2;
    private int $adminUserId = 102;
    private ?PDO $pdo;

    public function __construct()
    {
        $this->pdo = BaseService::getDatabaseConnection();
    }

    public function runAllTests(): bool
    {
        $trainService = new TrainingService();
        $teamService = new TeamService();
        $coachService = new CoachService();
        $athService = new AthleteService();
        $permService = new PermissionService();

        // 1. Training session list
        $list = $trainService->listSessions($this->orgId, 1, 10);
        if (!is_array($list) || !isset($list['data']) || !isset($list['total'])) {
            echo "Failed: 1. Training session list\n";
            return false;
        }

        // 2. Unique session results (no duplicates)
        $sessionIds = array_column($list['data'], 'id');
        if (count($sessionIds) !== count(array_unique($sessionIds))) {
            echo "Failed: 2. Unique session results (duplicates found)\n";
            return false;
        }

        // Setup test data for tests 3-18
        $coachId = null;
        $teamId = null;
        $venueId = 1;
        $athleteId = null;
        $createdSessionIds = [];

        try {
            // Find or use an active coach
            $cStmt = $this->pdo->prepare("SELECT id FROM coach_profiles WHERE organization_id = :org_id AND deleted_at IS NULL LIMIT 1");
            $cStmt->execute([':org_id' => $this->orgId]);
            $coachId = (int)($cStmt->fetchColumn() ?: 1);

            // Find or use an active team
            $tStmt = $this->pdo->prepare("SELECT id FROM teams WHERE organization_id = :org_id AND deleted_at IS NULL LIMIT 1");
            $tStmt->execute([':org_id' => $this->orgId]);
            $teamId = (int)($tStmt->fetchColumn() ?: 1);

            // Find or use an active athlete
            $aStmt = $this->pdo->prepare("SELECT a.id FROM athletes a JOIN team_members tm ON tm.athlete_id = a.id WHERE a.organization_id = :org_id AND tm.team_id = :t_id AND tm.is_current = 1 AND a.deleted_at IS NULL LIMIT 1");
            $aStmt->execute([':org_id' => $this->orgId, ':t_id' => $teamId]);
            $athleteId = (int)$aStmt->fetchColumn();

            if (!$athleteId) {
                // Pick any athlete and assign to team
                $aStmt2 = $this->pdo->prepare("SELECT id FROM athletes WHERE organization_id = :org_id AND deleted_at IS NULL LIMIT 1");
                $aStmt2->execute([':org_id' => $this->orgId]);
                $athleteId = (int)$aStmt2->fetchColumn();
                if ($athleteId) {
                    $insTm = $this->pdo->prepare("INSERT IGNORE INTO team_members (organization_id, team_id, athlete_id, is_current, member_role, created_at, updated_at) VALUES (:oid, :tid, :aid, 1, 'player', NOW(), NOW())");
                    $insTm->execute([':oid' => $this->orgId, ':tid' => $teamId, ':aid' => $athleteId]);
                }
            }

            // Create Session 1 (Date: 2026-09-30, Coach: $coachId, Team: $teamId)
            $sess1 = $trainService->createSession($this->orgId, [
                'title' => 'Comprehensive Batting Technique Drill',
                'training_type' => 'Batting Practice',
                'coach_id' => $coachId,
                'team_id' => $teamId,
                'venue_id' => $venueId,
                'training_date' => '2026-09-30',
                'start_time' => '08:00:00',
                'end_time' => '10:00:00',
                'status' => 'completed',
                'objectives' => 'Master forward defensive technique',
                'notes' => 'Full batting kit required'
            ], $this->adminUserId);
            $sessId1 = (int)$sess1['id'];
            $createdSessionIds[] = $sessId1;

            // Create Session 2 for the same coach on a different date (Historical: 2026-09-28)
            $sess2 = $trainService->createSession($this->orgId, [
                'title' => 'Comprehensive Fielding & Catching Drill',
                'training_type' => 'Fielding Drills',
                'coach_id' => $coachId,
                'team_id' => $teamId,
                'venue_id' => $venueId,
                'training_date' => '2026-09-28',
                'start_time' => '08:00:00',
                'end_time' => '10:00:00',
                'status' => 'completed',
            ], $this->adminUserId);
            $sessId2 = (int)$sess2['id'];
            $createdSessionIds[] = $sessId2;

            // 3. Coach filtering
            $coachFiltered = $trainService->listSessions($this->orgId, 1, 20, null, null, null, null, $coachId);
            foreach ($coachFiltered['data'] as $cs) {
                if ((int)$cs['coach_id'] !== $coachId) {
                    echo "Failed: 3. Coach filtering\n";
                    return false;
                }
            }

            // 4. Team filtering
            $teamFiltered = $trainService->listSessions($this->orgId, 1, 20, null, null, null, $teamId);
            foreach ($teamFiltered['data'] as $ts) {
                if ((int)$ts['team_id'] !== $teamId) {
                    echo "Failed: 4. Team filtering\n";
                    return false;
                }
            }

            // 5. Date filtering
            $dateFiltered = $trainService->listSessions($this->orgId, 1, 20, null, '2026-09-30');
            foreach ($dateFiltered['data'] as $ds) {
                if ($ds['training_date'] !== '2026-09-30') {
                    echo "Failed: 5. Date filtering\n";
                    return false;
                }
            }

            // 6. Status filtering
            $statusFiltered = $trainService->listSessions($this->orgId, 1, 20, null, null, 'completed');
            foreach ($statusFiltered['data'] as $ss) {
                if ($ss['status'] !== 'completed') {
                    echo "Failed: 6. Status filtering\n";
                    return false;
                }
            }

            // 7. Search
            $searchFiltered = $trainService->listSessions($this->orgId, 1, 20, 'Batting Technique Drill');
            $foundSearch = false;
            foreach ($searchFiltered['data'] as $sr) {
                if ((int)$sr['id'] === $sessId1) {
                    $foundSearch = true;
                    break;
                }
            }
            if (!$foundSearch) {
                echo "Failed: 7. Search\n";
                return false;
            }

            // 8. Pagination
            $page1 = $trainService->listSessions($this->orgId, 1, 1);
            $page2 = $trainService->listSessions($this->orgId, 2, 1);
            if (count($page1['data']) !== 1 || $page1['page'] !== 1 || $page2['page'] !== 2) {
                echo "Failed: 8. Pagination\n";
                return false;
            }

            // 9. Session detail
            $detail1 = $trainService->getSession($this->orgId, $sessId1);
            if (!$detail1 || (int)$detail1['id'] !== $sessId1) {
                echo "Failed: 9. Session detail\n";
                return false;
            }

            // 10. Correct coach
            if ((int)$detail1['coach_id'] !== $coachId) {
                echo "Failed: 10. Correct coach\n";
                return false;
            }

            // 11. Correct team
            if ((int)$detail1['team_id'] !== $teamId) {
                echo "Failed: 11. Correct team\n";
                return false;
            }

            // 12. Correct date
            if ($detail1['training_date'] !== '2026-09-30') {
                echo "Failed: 12. Correct date\n";
                return false;
            }

            // Record attendance for session 1
            if ($athleteId) {
                $attOk = $trainService->recordAttendance($this->orgId, $sessId1, [
                    $athleteId => [
                        'status' => 'present',
                        'check_in_time' => '07:55:00',
                        'check_out_time' => '10:03:00',
                        'remarks' => 'Excellent footwork and timing'
                    ]
                ], $this->adminUserId);

                if (!$attOk) {
                    echo "Failed: recording attendance\n";
                    return false;
                }
            }

            // Reload session 1 with roster attendance
            $reloaded1 = $trainService->getSession($this->orgId, $sessId1);

            // 13. Correct roster
            $roster = $reloaded1['roster_attendance'] ?? [];
            if (empty($roster)) {
                echo "Failed: 13. Correct roster\n";
                return false;
            }

            // 14. Attendance status
            $athRec = null;
            foreach ($roster as $r) {
                if ((int)$r['athlete_id'] === $athleteId) {
                    $athRec = $r;
                    break;
                }
            }
            if (!$athRec || $athRec['attendance_status'] !== 'present') {
                echo "Failed: 14. Attendance status\n";
                return false;
            }

            // 15. Attendance date
            // The attendance belongs to session date 2026-09-30
            if ($reloaded1['training_date'] !== '2026-09-30') {
                echo "Failed: 15. Attendance date\n";
                return false;
            }

            // 16. Attendance remarks
            if ($athRec['remarks'] !== 'Excellent footwork and timing') {
                echo "Failed: 16. Attendance remarks\n";
                return false;
            }

            // 17. Multiple sessions for same coach
            $coachSessions = $trainService->listSessions($this->orgId, 1, 20, null, null, null, null, $coachId);
            $foundSess1 = false;
            $foundSess2 = false;
            foreach ($coachSessions['data'] as $cs) {
                if ((int)$cs['id'] === $sessId1) $foundSess1 = true;
                if ((int)$cs['id'] === $sessId2) $foundSess2 = true;
            }
            if (!$foundSess1 || !$foundSess2) {
                echo "Failed: 17. Multiple sessions for same coach\n";
                return false;
            }

            // 18. Historical sessions (independent attendance)
            $detail2 = $trainService->getSession($this->orgId, $sessId2);
            if (!$detail2 || (int)$detail2['id'] !== $sessId2) {
                echo "Failed: 18. Historical sessions\n";
                return false;
            }
            // Session 2 should NOT inherit Session 1's attendance
            $summary2 = $detail2['attendance_summary'] ?? [];
            if (($summary2['present'] ?? 0) > 0) {
                // Should be unrecorded (0 present)
                echo "Failed: 18. Historical sessions attendance was incorrectly shared\n";
                return false;
            }

            // 19. Tenant isolation
            $crossOrgAccess = $trainService->getSession($this->otherOrgId, $sessId1);
            if ($crossOrgAccess !== null) {
                echo "Failed: 19. Tenant isolation (cross-tenant session access allowed)\n";
                return false;
            }

            // 20. RBAC: Permissions check
            $sportsAdminPayload = [
                'id' => 102,
                'role_id' => 2,
                'role' => ['id' => 2, 'slug' => 'sports_admin'],
                'permissions' => ['training.view', 'training.create', 'training.update', 'training.manage', 'attendance.manage'],
            ];
            $athletePayload = [
                'id' => 501,
                'role_id' => 5,
                'role' => ['id' => 5, 'slug' => 'athlete'],
                'athlete_id' => 1,
                'permissions' => ['training.view'],
            ];
            if (!$permService->hasPermission($sportsAdminPayload, 'training.manage', $this->orgId)) {
                echo "Failed: 20. RBAC sports_admin should have training.manage\n";
                return false;
            }
            if ($permService->hasPermission($athletePayload, 'training.create', $this->orgId)) {
                echo "Failed: 20. RBAC athlete should not have training.create\n";
                return false;
            }

            // 21. Direct URL authorization
            // Athletes are prohibited from updating training attendance
            $attController = new \App\Http\Controllers\Api\V1\Attendance\AttendanceController();
            $athAttempt = $attController->recordTraining($this->orgId, $sessId1, [
                'user' => $athletePayload,
                'attendance' => [$athleteId => 'present']
            ], 501);
            $statusCode = (int)($athAttempt['_status_code'] ?? ($athAttempt['status_code'] ?? 200));
            if ($statusCode !== 403) {
                echo "Failed: 21. Direct URL authorization: athlete was not rejected with 403 (got {$statusCode})\n";
                return false;
            }

            return true;
        } finally {
            // Cleanup created test sessions
            foreach ($createdSessionIds as $sid) {
                $this->pdo->exec("DELETE FROM training_attendance WHERE training_session_id = {$sid}");
                $this->pdo->exec("DELETE FROM training_sessions WHERE id = {$sid}");
            }
        }
    }
}
