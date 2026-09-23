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
        if (!$pdo) return false;

        // Ensure leave type fixture exists
        $stmt = $pdo->query("SELECT id FROM leave_types WHERE id = 1 AND organization_id = 1");
        if (!$stmt->fetchColumn()) {
            $pdo->exec("INSERT INTO leave_types (id, organization_id, name, description, max_days_per_year, status, created_at, updated_at) VALUES (1, 1, 'Casual Leave', 'Standard Casual Leave', 12.00, 'active', NOW(), NOW())");
        }

        // Establish temporary employee/user relationship for the test and record original state
        $stmtUser = $pdo->query("SELECT user_id FROM employees WHERE id = 1");
        $originalUserId = $stmtUser->fetchColumn();
        $pdo->exec("UPDATE employees SET user_id = 2 WHERE id = 1");

        $reqId = null;
        try {
            // Remove any leftover test rows to prevent overlap
            $pdo->exec("DELETE FROM leave_requests WHERE organization_id = 1 AND employee_id = 1 AND reason = 'Separation of duties test application'");

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
            $reqId = (int)$req['id'];

            try {
                // Attempting to approve as user 2 (who is employee 1) must trigger separation of duties
                $service->reviewLeave(1, $reqId, 'approved', null, 2);
                return false;
            } catch (\Throwable $e) {
                return str_contains($e->getMessage(), 'Separation of duties');
            }
        } finally {
            // Clean up test leave request
            if ($reqId) {
                $pdo->exec("DELETE FROM leave_requests WHERE id = " . $reqId);
            }
            // Restore original employee 1 user_id
            if ($originalUserId !== false && $originalUserId !== null) {
                $pdo->exec("UPDATE employees SET user_id = " . (int)$originalUserId . " WHERE id = 1");
            } else {
                $pdo->exec("UPDATE employees SET user_id = NULL WHERE id = 1");
            }
        }
    }

    /**
     * Test successful approval by an authorized third party
     */
    public function testAuthorizedReviewerCanApprove(): bool
    {
        $service = new LeaveService();
        $pdo = BaseService::getDatabaseConnection();
        if (!$pdo) return false;

        // Ensure leave type fixture exists
        $stmt = $pdo->query("SELECT id FROM leave_types WHERE id = 1 AND organization_id = 1");
        if (!$stmt->fetchColumn()) {
            $pdo->exec("INSERT INTO leave_types (id, organization_id, name, description, max_days_per_year, status, created_at, updated_at) VALUES (1, 1, 'Casual Leave', 'Standard Casual Leave', 12.00, 'active', NOW(), NOW())");
        }

        $reqId = null;
        try {
            // Remove any leftover test rows to prevent overlap
            $pdo->exec("DELETE FROM leave_requests WHERE organization_id = 1 AND employee_id = 1 AND reason = 'Third-party approval test'");

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
            $reqId = (int)$req['id'];

            // User 1 (Super Admin) is NOT employee 1, so approval succeeds!
            return $service->reviewLeave(1, $reqId, 'approved', null, 1);
        } finally {
            if ($reqId) {
                $pdo->exec("DELETE FROM leave_requests WHERE id = " . $reqId);
            }
        }
    }
}
