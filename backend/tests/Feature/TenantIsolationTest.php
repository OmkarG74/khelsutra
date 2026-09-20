<?php

namespace Tests\Feature;

use App\Services\Staff\EmployeeService;
use App\Services\Leave\LeaveService;
use App\Services\Payroll\PayrollService;
use App\Services\Organization\OrganizationSettingsService;

class TenantIsolationTest
{
    /**
     * Test that an employee belonging to Org 1 is never accessible from Org 2 context
     */
    public function testCrossTenantEmployeeAccessIsForbidden(): bool
    {
        $service = new EmployeeService();
        // Employee 1 belongs to Org 1
        $emp1 = $service->getEmployeeDetails(1, 1);
        if (!$emp1 || (int)$emp1['organization_id'] !== 1) {
            return false;
        }

        // Attempting to fetch Employee 1 under Org 2 context must throw exception / return null
        try {
            $crossAccess = $service->getEmployeeDetails(1, 2);
            return false; // Should not reach here
        } catch (\Throwable $e) {
            return true; // Correctly blocked!
        }
    }

    /**
     * Test that Leave requests cannot be viewed across tenant boundaries
     */
    public function testCrossTenantLeaveAccessIsForbidden(): bool
    {
        $service = new LeaveService();
        try {
            // Leave request 1 belongs to Org 1
            $res = $service->getLeaveRequestDetails(1, 999);
            return false;
        } catch (\Throwable $e) {
            return true; // Correctly isolated
        }
    }

    /**
     * Test that Payroll records cannot be accessed across organisations
     */
    public function testCrossTenantPayrollAccessIsForbidden(): bool
    {
        $service = new PayrollService();
        try {
            $res = $service->getPayrollDetails(1, 999);
            return ($res === null); // Correctly returns null when outside tenant
        } catch (\Throwable $e) {
            return true; // Correctly isolated
        }
    }

    /**
     * Test that settings are scoped strictly to the current organisation
     */
    public function testOrganizationSettingsAreTenantScoped(): bool
    {
        $service = new OrganizationSettingsService();
        $settingsOrg1 = $service->getAllSettings(1);
        $settingsOrg999 = $service->getAllSettings(999);

        // Org 1 has seeded settings, Org 999 has none
        return !empty($settingsOrg1) && empty($settingsOrg999);
    }
}
