<?php

namespace App\Http\Controllers\Api\V1\Coaches;

use App\Http\Controllers\Controller;
use App\Services\Coach\CoachProfileService;
use App\Helpers\ApiResponse;

class CoachProfileController extends Controller
{
    protected CoachProfileService $coachService;

    public function __construct(?CoachProfileService $coachService = null)
    {
        $this->coachService = $coachService ?? new CoachProfileService();
    }

    public function show(int $orgId, int $employeeId): array
    {
        $profile = $this->coachService->getByEmployeeId($orgId, $employeeId);
        if (!$profile) {
            return ApiResponse::error('Coach profile not found for this employee.', null, 404);
        }
        return ApiResponse::success($profile, 'Coach profile retrieved', 200);
    }

    public function storeOrUpdate(int $orgId, int $employeeId, array $requestData, ?int $performedBy = null): array
    {
        $profile = $this->coachService->createOrUpdateProfile($orgId, $employeeId, $requestData, $performedBy);
        if (!$profile) {
            return ApiResponse::error('Failed to sync coach profile.', null, 500);
        }
        return ApiResponse::success($profile, 'Coach profile saved successfully', 200);
    }
}
