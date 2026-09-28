<?php

namespace App\Http\Controllers\Api\V1\Coaches;

use App\Http\Controllers\Controller;
use App\Services\Coach\CoachService;
use App\Helpers\ApiResponse;
use PDO;

class CoachController extends Controller
{
    protected CoachService $coachService;

    public function __construct(?CoachService $coachService = null)
    {
        $this->coachService = $coachService ?? new CoachService();
    }

    public function index(int $organizationId, array $requestData): array
    {
        $page = (int)($requestData['page'] ?? 1);
        $limit = (int)($requestData['limit'] ?? 15);
        $search = $requestData['search'] ?? null;
        $specialization = $requestData['specialization'] ?? null;
        $status = $requestData['status'] ?? null;

        $coaches = $this->coachService->listCoaches($organizationId, $page, $limit, $search, $specialization, $status);
        return ApiResponse::success($coaches, 'Coaches retrieved successfully', 200);
    }

    public function show(int $organizationId, int $id): array
    {
        $coach = $this->coachService->getCoach($organizationId, $id);
        if (!$coach) {
            return ApiResponse::error('Coach not found', null, 404);
        }
        return ApiResponse::success($coach, 'Coach profile retrieved successfully', 200);
    }

    /**
     * Get athletes assigned to this coach's teams
     */
    public function athletes(int $organizationId, int $coachId, array $requestData): array
    {
        $pdo = $this->coachService->getPdo();
        if (!$pdo) {
            return ApiResponse::success(['data' => [], 'total' => 0], 'No athletes found', 200);
        }

        $search = $requestData['search'] ?? null;
        $params = [':cid' => $coachId, ':oid' => $organizationId];

        $sql = "
            SELECT DISTINCT 
                a.id, a.athlete_code, a.first_name, a.middle_name, a.last_name,
                a.date_of_birth, a.gender, a.phone, a.email, a.photo_path, a.status,
                s.name as sport_name,
                t.id as team_id, t.name as team_name,
                tm.jersey_number, tm.member_role
            FROM team_coaches tc
            JOIN teams t ON tc.team_id = t.id AND t.deleted_at IS NULL
            JOIN team_members tm ON t.id = tm.team_id AND tm.is_current = 1
            JOIN athletes a ON tm.athlete_id = a.id AND a.deleted_at IS NULL
            LEFT JOIN sports s ON a.current_sport_id = s.id
            WHERE tc.coach_id = :cid AND tc.organization_id = :oid
        ";

        if (!empty($search)) {
            $sql .= " AND (a.first_name LIKE :search OR a.last_name LIKE :search OR a.athlete_code LIKE :search OR t.name LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $sql .= " ORDER BY a.first_name ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $athletes = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return ApiResponse::success([
            'data' => $athletes,
            'total' => count($athletes),
        ], 'Assigned athletes retrieved successfully', 200);
    }

    /**
     * Get coach operational dashboard summary
     */
    public function dashboard(int $organizationId, int $coachId): array
    {
        $pdo = $this->coachService->getPdo();
        if (!$pdo) {
            return ApiResponse::error('Database unavailable', null, 500);
        }

        // 1. Teams count
        $tStmt = $pdo->prepare("SELECT COUNT(DISTINCT team_id) FROM team_coaches WHERE coach_id = :cid AND organization_id = :oid");
        $tStmt->execute([':cid' => $coachId, ':oid' => $organizationId]);
        $teamsCount = (int)$tStmt->fetchColumn();

        // 2. Athletes count
        $aStmt = $pdo->prepare("
            SELECT COUNT(DISTINCT tm.athlete_id) 
            FROM team_coaches tc
            JOIN team_members tm ON tc.team_id = tm.team_id AND tm.is_current = 1
            JOIN athletes a ON tm.athlete_id = a.id AND a.deleted_at IS NULL
            WHERE tc.coach_id = :cid AND tc.organization_id = :oid
        ");
        $aStmt->execute([':cid' => $coachId, ':oid' => $organizationId]);
        $athletesCount = (int)$aStmt->fetchColumn();

        // 3. Today's sessions
        $today = date('Y-m-d');
        $sStmt = $pdo->prepare("
            SELECT ts.*, t.name as team_name, v.name as venue_name
            FROM training_sessions ts
            LEFT JOIN teams t ON ts.team_id = t.id
            LEFT JOIN venues v ON ts.venue_id = v.id
            LEFT JOIN team_coaches tc ON ts.team_id = tc.team_id
            WHERE (ts.coach_id = :cid OR tc.coach_id = :cid2)
              AND ts.organization_id = :oid
              AND ts.training_date = :tdate
              AND ts.deleted_at IS NULL
            ORDER BY ts.start_time ASC
        ");
        $sStmt->execute([':cid' => $coachId, ':cid2' => $coachId, ':oid' => $organizationId, ':tdate' => $today]);
        $todaySessions = $sStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 4. Overall Attendance rate for coach's sessions
        $attStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_records,
                SUM(CASE WHEN ta.attendance_status = 'present' THEN 1 ELSE 0 END) as present_count
            FROM training_attendance ta
            JOIN training_sessions ts ON ta.training_session_id = ts.id
            LEFT JOIN team_coaches tc ON ts.team_id = tc.team_id
            WHERE (ts.coach_id = :cid OR tc.coach_id = :cid2)
              AND ta.organization_id = :oid
        ");
        $attStmt->execute([':cid' => $coachId, ':cid2' => $coachId, ':oid' => $organizationId]);
        $attStats = $attStmt->fetch(PDO::FETCH_ASSOC) ?: ['total_records' => 0, 'present_count' => 0];
        $totalRecs = (int)($attStats['total_records'] ?? 0);
        $presentRecs = (int)($attStats['present_count'] ?? 0);
        $attendanceRate = $totalRecs > 0 ? round(($presentRecs / $totalRecs) * 100, 1) : 0;

        return ApiResponse::success([
            'teams_count' => $teamsCount,
            'athletes_count' => $athletesCount,
            'today_sessions_count' => count($todaySessions),
            'today_sessions' => $todaySessions,
            'attendance_rate' => $attendanceRate,
            'total_attendance_records' => $totalRecs,
            'present_count' => $presentRecs,
        ], 'Coach dashboard summary retrieved', 200);
    }
}
