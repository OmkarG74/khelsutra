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
        // Employee 1 is seeded as coach Vikram Singh
        $emp = $service->getEmployeeDetails(1, 1);

        return (!empty($emp['coach_profile']) && !empty($emp['coach_profile']['coach_code']));
    }
}
