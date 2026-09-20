<?php

namespace Tests\Feature;

use App\Services\Leave\LeaveService;
use App\Services\BaseService;

class LeaveTest
{
    /**
     * Test leave date validation: end_date before start_date is rejected
     */
    public function testEndDateBeforeStartDateFails(): bool
    {
        $service = new LeaveService();
        try {
            $service->applyLeave(1, [
                'applicant_type' => 'employee',
                'employee_id' => 1,
                'leave_type_id' => 1,
                'start_date' => '2026-10-15',
                'end_date' => '2026-10-10', // Invalid!
                'total_days' => 5,
                'reason' => 'Invalid dates test'
            ]);
            return false;
        } catch (\Throwable $e) {
            return str_contains($e->getMessage(), 'cannot be earlier than start date');
        }
    }

    /**
     * Test separation of duties: applicant cannot approve own leave request
     */
    public function testApplicantCannotApproveOwnRequest(): bool
    {
        $service = new LeaveService();
        $pdo = BaseService::getDatabaseConnection();

        // Create a unique pending leave request for employee 1 (whose user_id is 2)
        $futureYear = rand(2030, 2040);
        $req = $service->applyLeave(1, [
            'applicant_type' => 'employee',
            'employee_id' => 1,
            'leave_type_id' => 1,
            'start_date' => "{$futureYear}-11-10",
            'end_date' => "{$futureYear}-11-15",
            'total_days' => 5,
            'reason' => 'Separation of duties test application'
        ]);

        if (!$req || empty($req['id'])) return false;

        try {
            // Attempting to approve as user 2 (who is employee 1) must trigger separation of duties
            $service->reviewLeave(1, (int)$req['id'], 'approved', null, 2);
            return false;
        } catch (\Throwable $e) {
            $ok = str_contains($e->getMessage(), 'Separation of duties');
            // Clean up test row
            if ($pdo) {
                $pdo->exec("DELETE FROM leave_requests WHERE id = " . (int)$req['id']);
            }
            return $ok;
        }
    }

    /**
     * Test successful approval by an authorized third party
     */
    public function testAuthorizedReviewerCanApprove(): bool
    {
        $service = new LeaveService();
        $pdo = BaseService::getDatabaseConnection();

        // Create a fresh pending request in a distant future window
        $futureYear = rand(2041, 2050);
        $req = $service->applyLeave(1, [
            'applicant_type' => 'employee',
            'employee_id' => 1,
            'leave_type_id' => 1,
            'start_date' => "{$futureYear}-12-10",
            'end_date' => "{$futureYear}-12-15",
            'total_days' => 5,
            'reason' => 'Third-party approval test'
        ]);

        if (!$req || empty($req['id'])) return false;

        // User 1 (Super Admin) is NOT employee 1, so approval succeeds!
        $res = $service->reviewLeave(1, (int)$req['id'], 'approved', null, 1);

        // Clean up test row
        if ($pdo) {
            $pdo->exec("DELETE FROM leave_requests WHERE id = " . (int)$req['id']);
        }

        return $res;
    }
}
