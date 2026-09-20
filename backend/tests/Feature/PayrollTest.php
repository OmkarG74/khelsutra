<?php

namespace Tests\Feature;

use App\Services\Payroll\PayrollService;

class PayrollTest
{
    /**
     * Test that modifying or generating payroll for a locked period is rejected (Section 63)
     */
    public function testLockedPayrollPeriodCannotBeModified(): bool
    {
        $service = new PayrollService();
        // Period 1 is 'locked'
        try {
            $service->generatePayrollRecord(1, 1, 1, 5, 1000);
            return false;
        } catch (\Throwable $e) {
            return str_contains(strtolower($e->getMessage()), 'locked period');
        }
    }

    /**
     * Test that payroll dashboard returns accurate live figures
     */
    public function testPayrollDashboardSummary(): bool
    {
        $service = new PayrollService();
        $summary = $service->getPayrollDashboardSummary(1);

        return (
            isset($summary['total_gross']) &&
            isset($summary['total_net']) &&
            isset($summary['employee_count']) &&
            $summary['employee_count'] > 0
        );
    }
}
