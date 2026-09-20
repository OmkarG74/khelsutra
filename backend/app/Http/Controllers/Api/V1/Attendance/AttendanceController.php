<?php

namespace App\Http\Controllers\Api\V1\Attendance;

use App\Http\Controllers\Controller;
use App\Services\Attendance\AttendanceService;
use App\Helpers\ApiResponse;

class AttendanceController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(?AttendanceService $attendanceService = null)
    {
        $this->attendanceService = $attendanceService ?? new AttendanceService();
    }

    public function recordTraining(int $orgId, int $sessionId, array $requestData, ?int $performedBy = null): array
    {
        $res = $this->attendanceService->recordTrainingAttendance($orgId, $sessionId, $requestData, $performedBy);
        if (!$res) {
            return ApiResponse::error('Validation failed. Exactly one participant (athlete, coach, or employee) must be specified with valid status.', null, 422);
        }
        return ApiResponse::success($res, 'Training attendance recorded', 200);
    }

    public function recordMatch(int $orgId, int $matchId, array $requestData, ?int $performedBy = null): array
    {
        $res = $this->attendanceService->recordMatchAttendance($orgId, $matchId, $requestData, $performedBy);
        if (!$res) {
            return ApiResponse::error('Validation failed. Exactly one participant (athlete, coach, or employee) must be specified with valid status.', null, 422);
        }
        return ApiResponse::success($res, 'Match attendance recorded', 200);
    }

    public function trainingHistory(int $orgId, array $requestData): array
    {
        $sessionId = !empty($requestData['training_session_id']) ? (int)$requestData['training_session_id'] : null;
        $limit = (int)($requestData['limit'] ?? 50);
        $history = $this->attendanceService->getTrainingAttendanceHistory($orgId, $sessionId, $limit);
        return ApiResponse::success($history, 'Training attendance history retrieved', 200);
    }
}
