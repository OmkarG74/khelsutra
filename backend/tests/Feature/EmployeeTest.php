<?php

namespace Tests\Feature;

use App\Services\Staff\EmployeeService;

class EmployeeTest
{
    /**
     * Test employee creation with exact schema fields (Section 25)
     */
    public function testEmployeeCreationAndRetrieval(): bool
    {
        $service = new EmployeeService();
        $code = 'EMP-TEST-' . strtoupper(bin2hex(random_bytes(4)));

        $emp = $service->createEmployee(1, [
            'employee_code' => $code,
            'first_name' => 'Suresh',
            'last_name' => 'Raina',
            'phone' => '+91 9988776655',
            'email' => 'suresh.' . bin2hex(random_bytes(3)) . '@khelsutra.com',
            'department_id' => 1,
            'employee_category_id' => 1,
            'designation' => 'Fielding Coach',
            'joining_date' => '2026-05-01',
            'employment_type' => 'full_time',
            'employment_status' => 'active',
            'bank_name' => 'HDFC Bank',
            'bank_account_no' => '50100234567890',
            'bank_ifsc' => 'HDFC0001234'
        ]);

        if (empty($emp['id'])) return false;

        $fetched = $service->getEmployeeDetails((int)$emp['id'], 1);
        return ($fetched['employee_code'] === $code && $fetched['first_name'] === 'Suresh');
    }

    /**
     * Test Coach Profile Foundation relationship (Section 29)
     */
    public function testEmployeeCoachRelationship(): bool
    {
        $service = new EmployeeService();
        $pdo = $service->getPdo();

        // 1. Query Organization 1 for an employee that actually has a valid coach profile
        $coachEmpId = null;
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT employee_id FROM coach_profiles WHERE organization_id = 1 AND deleted_at IS NULL LIMIT 1");
            $stmt->execute();
            $coachEmpId = $stmt->fetchColumn();
        }

        // 2 & 3. If no suitable coach exists in an isolated test environment, create the required coach fixture
        if (!$coachEmpId) {
            require_once dirname(__DIR__, 2) . '/app/Services/Coach/CoachService.php';
            $coachService = new \App\Services\Coach\CoachService($pdo);
            $created = $coachService->createCoach(1, [
                'first_name' => 'Vikram',
                'last_name' => 'Singh',
                'designation' => 'Head Coach',
                'specialization' => 'Cricket Batting',
                'experience_years' => 10,
            ]);
            $coachEmpId = $created['employee_id'] ?? null;
        }

        if (!$coachEmpId) return false;

        // 4. Call EmployeeService::getEmployeeDetails($coachEmpId, 1)
        $emp = $service->getEmployeeDetails((int)$coachEmpId, 1);

        // 5. Verify that the returned employee contains the expected coach_profile data
        return (!empty($emp['coach_profile']) && !empty($emp['coach_profile']['coach_code']));
    }
}
