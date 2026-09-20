<?php

namespace Tests\Unit;

use App\Services\Payroll\PayrollCalculationService;

class PayrollCalculationTest
{
    /**
     * Section 62 Requirement:
     * Basic: 30000, Allowances: 5000, Overtime: 2000, Bonus: 3000 -> Gross: 40000
     * Tax: 2000, Deductions: 1000, Other: 500 -> Deductions: 3500 -> Net: 36500
     */
    public function testStandardPayrollCalculation(): bool
    {
        $calc = new PayrollCalculationService();
        $result = $calc->calculateNetSalary(
            basic: '30000.00',
            allowances: '5000.00',
            overtimeAmount: '2000.00',
            bonus: '3000.00',
            tax: '2000.00',
            deductions: '1000.00',
            otherDeductions: '500.00'
        );

        $grossOk = bccomp($result['gross_salary'], '40000.00', 2) === 0;
        $deductionsOk = bccomp($result['total_deductions'], '3500.00', 2) === 0;
        $netOk = bccomp($result['net_salary'], '36500.00', 2) === 0;

        return ($grossOk && $deductionsOk && $netOk);
    }

    /**
     * Test Zero Allowances, Zero OT, Zero Bonus
     */
    public function testZeroAllowancesAndBonusCalculation(): bool
    {
        $calc = new PayrollCalculationService();
        $result = $calc->calculateNetSalary(
            basic: '25000.00',
            allowances: '0.00',
            overtimeAmount: '0.00',
            bonus: '0.00',
            tax: '1000.00',
            deductions: '500.00',
            otherDeductions: '0.00'
        );

        return (
            bccomp($result['gross_salary'], '25000.00', 2) === 0 &&
            bccomp($result['total_deductions'], '1500.00', 2) === 0 &&
            bccomp($result['net_salary'], '23500.00', 2) === 0
        );
    }

    /**
     * Test Overtime Amount Calculation: Hours * Rate
     */
    public function testOvertimeAmountCalculation(): bool
    {
        $calc = new PayrollCalculationService();
        $otAmount = $calc->calculateOvertimeAmount('10.5', '200.00');
        // 10.5 * 200 = 2100.00
        return (bccomp($otAmount, '2100.00', 2) === 0);
    }
}
