<?php

namespace App\Http\Controllers\Api\V1\Attendance;

use App\Http\Controllers\Controller;
use App\Services\Attendance\AttendanceService;
use App\Services\Training\TrainingService;
use App\Helpers\ApiResponse;
use PDO;

class AttendanceController extends Controller
{
    protected AttendanceService $attendanceService;
    protected TrainingService $trainingService;

    public function __construct(?AttendanceService $attendanceService = null, ?TrainingService $trainingService = null)
    {
        $this->attendanceService = $attendanceService ?? new AttendanceService();
        $this->trainingService = $trainingService ?? new TrainingService();
    }

    public function recordTraining(int $orgId, int $sessionId, array $requestData, ?int $performedBy = null): array
    {
        $pdo = $this->trainingService->getPdo();
        if (!$pdo) {
            return ApiResponse::error('Database connection unavailable', null, 500);
        }

        // 1. Authorize: Session must exist, belong to this organization, not deleted
        $sessStmt = $pdo->prepare("
            SELECT ts.id, ts.coach_id, ts.team_id, ts.organization_id 
            FROM training_sessions ts 
            WHERE ts.id = :id AND ts.organization_id = :org_id AND ts.deleted_at IS NULL 
            LIMIT 1
        ");
        $sessStmt->execute([':id' => $sessionId, ':org_id' => $orgId]);
        $session = $sessStmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            return ApiResponse::error('Training session not found or does not belong to your organization.', null, 404);
        }

        // 2. Authorize coach: if authenticated user is a coach, ensure they own or coach this session. Athletes CANNOT submit attendance.
        $currentUser = $requestData['user'] ?? null;
        $roleSlug = $currentUser['role']['slug'] ?? '';
        $isCoachUser = ($roleSlug === 'coach') || !empty($currentUser['coach_id']);
        $isAdminUser = in_array($roleSlug, ['super_admin', 'sports_admin', 'admin']);
        $isAthleteUser = ($roleSlug === 'athlete') || !empty($currentUser['athlete_id']);

        if ($isAthleteUser) {
            return ApiResponse::error('Access denied: Athletes are not permitted to submit training attendance.', null, 403);
        }

        if (!$isCoachUser && !$isAdminUser) {
            return ApiResponse::error('Access denied: Only authorized coaches and administrators can record attendance.', null, 403);
        }

        if ($isCoachUser) {
            $coachId = (int)($currentUser['coach_id'] ?? 0);
            if (!$coachId && !empty($currentUser['id'])) {
                $cStmt = $pdo->prepare("
                    SELECT cp.id 
                    FROM coach_profiles cp 
                    JOIN employees e ON cp.employee_id = e.id 
                    WHERE e.user_id = :uid AND cp.organization_id = :oid AND cp.deleted_at IS NULL 
                    LIMIT 1
                ");
                $cStmt->execute([':uid' => (int)$currentUser['id'], ':oid' => $orgId]);
                $foundCoach = $cStmt->fetchColumn();
                if ($foundCoach) $coachId = (int)$foundCoach;
            }

            if ($coachId) {
                $sessionCoachId = (int)($session['coach_id'] ?? 0);
                $isCoachOfSession = ($sessionCoachId === $coachId);
                if (!$isCoachOfSession && !empty($session['team_id'])) {
                    $tcStmt = $pdo->prepare("SELECT 1 FROM team_coaches WHERE team_id = :t_id AND coach_id = :c_id AND organization_id = :oid LIMIT 1");
                    $tcStmt->execute([':t_id' => $session['team_id'], ':c_id' => $coachId, ':oid' => $orgId]);
                    $isCoachOfSession = (bool)$tcStmt->fetchColumn();
                }

                if (!$isCoachOfSession) {
                    return ApiResponse::error('Access denied: You are not authorized to record attendance for this training session.', null, 403);
                }
            } else {
                return ApiResponse::error('Access denied: Coach profile required.', null, 403);
            }
        }

        // 3. Process Attendance Payload
        // Supported payload formats:
        // Format A: { "attendance": [ { "athlete_id": 1, "status": "present" }, ... ] }
        // Format B: { "attendance": { "1": "present", "2": "absent" } }
        // Format C: { "athlete_id": 1, "status": "present" }
        $attendanceMap = [];

        if (isset($requestData['attendance']) && is_array($requestData['attendance'])) {
            foreach ($requestData['attendance'] as $key => $val) {
                if (is_array($val) && isset($val['athlete_id'])) {
                    $athId = (int)$val['athlete_id'];
                    $st = $val['status'] ?? $val['attendance_status'] ?? 'present';
                    if ($athId > 0 && strtolower(trim((string)$st)) !== 'not_marked') {
                        $attendanceMap[$athId] = [
                            'status' => $st,
                            'remarks' => $val['remarks'] ?? null,
                        ];
                    }
                } elseif (is_numeric($key)) {
                    $athId = (int)$key;
                    if ($athId > 0 && strtolower(trim((string)$val)) !== 'not_marked') {
                        $attendanceMap[$athId] = [
                            'status' => (string)$val,
                            'remarks' => null,
                        ];
                    }
                }
            }
        } elseif (!empty($requestData['athlete_id'])) {
            $athId = (int)$requestData['athlete_id'];
            $st = $requestData['status'] ?? $requestData['attendance_status'] ?? 'present';
            if ($athId > 0 && strtolower(trim((string)$st)) !== 'not_marked') {
                $attendanceMap[$athId] = [
                    'status' => $st,
                    'remarks' => $requestData['remarks'] ?? null,
                ];
            }
        }

        if (empty($attendanceMap)) {
            return ApiResponse::error('Validation failed: No valid attendance records (present or absent) provided to record.', null, 422);
        }

        $saved = $this->trainingService->recordAttendance($orgId, $sessionId, $attendanceMap, $performedBy);
        if (!$saved) {
            return ApiResponse::error('Failed to record attendance for training session.', null, 500);
        }

        return ApiResponse::success([
            'session_id' => $sessionId,
            'records_recorded' => count($attendanceMap),
            'attendance' => $attendanceMap
        ], 'Training attendance recorded successfully', 200);
    }

    public function recordMatch(int $orgId, int $matchId, array $requestData, ?int $performedBy = null): array
    {
        $currentUser = $requestData['user'] ?? null;
        $roleSlug = $currentUser['role']['slug'] ?? '';
        $isAthleteUser = ($roleSlug === 'athlete') || !empty($currentUser['athlete_id']);
        if ($isAthleteUser) {
            return ApiResponse::error('Access denied: Athletes are not permitted to submit match attendance.', null, 403);
        }

        // Support batch attendance submission
        if (!empty($requestData['attendance']) && is_array($requestData['attendance'])) {
            $recorded = 0;
            foreach ($requestData['attendance'] as $item) {
                if (is_array($item)) {
                    try {
                        $res = $this->attendanceService->recordMatchAttendance($orgId, $matchId, $item, $performedBy);
                        if ($res) $recorded++;
                    } catch (\Throwable $e) {}
                }
            }
            return ApiResponse::success([
                'match_id' => $matchId,
                'records_recorded' => $recorded,
            ], 'Match attendance recorded successfully', 200);
        }

        $res = $this->attendanceService->recordMatchAttendance($orgId, $matchId, $requestData, $performedBy);
        if (!$res) {
            return ApiResponse::error('Validation failed. Exactly one participant (athlete, coach, or employee) must be specified with valid status.', null, 422);
        }
        return ApiResponse::success($res, 'Match attendance recorded', 200);
    }

    public function trainingHistory(int $orgId, array $requestData): array
    {
        $currentUser = $requestData['user'] ?? null;
        $roleSlug = $currentUser['role']['slug'] ?? '';
        $isAthlete = ($roleSlug === 'athlete') || !empty($currentUser['athlete_id']);

        $sessionId = !empty($requestData['training_session_id']) ? (int)$requestData['training_session_id'] : null;
        $athleteId = !empty($requestData['athlete_id']) ? (int)$requestData['athlete_id'] : null;
        $coachId = !empty($requestData['coach_id']) ? (int)$requestData['coach_id'] : null;
        $limit = (int)($requestData['limit'] ?? 50);

        if ($isAthlete) {
            $userAthleteId = (int)($currentUser['athlete_id'] ?? 0);
            if ($athleteId && $athleteId !== $userAthleteId) {
                return ApiResponse::error('Access denied: Athletes can only view their own attendance history.', null, 403);
            }
            $athleteId = $userAthleteId;
        }

        $history = $this->attendanceService->getTrainingAttendanceHistory($orgId, $sessionId, $limit, $athleteId, $coachId);
        return ApiResponse::success($history, 'Training attendance history retrieved', 200);
    }

    public function matchHistory(int $orgId, array $requestData): array
    {
        $currentUser = $requestData['user'] ?? null;
        $roleSlug = $currentUser['role']['slug'] ?? '';
        $isAthlete = ($roleSlug === 'athlete') || !empty($currentUser['athlete_id']);

        $matchId = !empty($requestData['match_id']) ? (int)$requestData['match_id'] : null;
        $athleteId = !empty($requestData['athlete_id']) ? (int)$requestData['athlete_id'] : null;
        $coachId = !empty($requestData['coach_id']) ? (int)$requestData['coach_id'] : null;
        $limit = (int)($requestData['limit'] ?? 50);

        if ($isAthlete) {
            $userAthleteId = (int)($currentUser['athlete_id'] ?? 0);
            if ($athleteId && $athleteId !== $userAthleteId) {
                return ApiResponse::error('Access denied: Athletes can only view their own match attendance history.', null, 403);
            }
            $athleteId = $userAthleteId;
        }

        $history = $this->attendanceService->getMatchAttendanceHistory($orgId, $matchId, $limit, $athleteId, $coachId);
        return ApiResponse::success($history, 'Match attendance history retrieved', 200);
    }
}
