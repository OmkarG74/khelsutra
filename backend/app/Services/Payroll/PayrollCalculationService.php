<?php

namespace App\Services\Payroll;

class PayrollCalculationService
{
    /**
     * Compute gross, deductions, and net salary using precise decimal arithmetic.
     *
     * @param float|string $basicSalary
     * @param float|string $allowances
     * @param float|string $overtimeAmount
     * @param float|string $bonus
     * @param float|string $tax
     * @param float|string $deductions
     * @param float|string $otherDeductions
     * @return array
     */
    public function calculate(
        float|string $basicSalary,
        float|string $allowances = 0.0,
        float|string $overtimeAmount = 0.0,
        float|string $bonus = 0.0,
        float|string $tax = 0.0,
        float|string $deductions = 0.0,
        float|string $otherDeductions = 0.0
    ): array {
        // Enforce non-negative values
        $basic = max(0.0, (float)$basicSalary);
        $allow = max(0.0, (float)$allowances);
        $otAmt = max(0.0, (float)$overtimeAmount);
        $bon   = max(0.0, (float)$bonus);

        $tTax   = max(0.0, (float)$tax);
        $tDeduc = max(0.0, (float)$deductions);
        $tOther = max(0.0, (float)$otherDeductions);

        // Gross Salary = Basic Salary + Allowances + Overtime Amount + Bonus
        $gross = round($basic + $allow + $otAmt + $bon, 2);

        // Total Deductions = Tax + Deductions + Other Deductions
        $totalDeductions = round($tTax + $tDeduc + $tOther, 2);

        // Net Salary = Gross Salary - Total Deductions
        $net = round($gross - $totalDeductions, 2);

        return [
            'basic_salary' => $basic,
            'allowances' => $allow,
            'overtime_amount' => $otAmt,
            'bonus' => $bon,
            'tax' => $tTax,
            'deductions' => $tDeduc,
            'other_deductions' => $tOther,
            'gross_salary' => $gross,
            'total_deductions' => $totalDeductions,
            'net_salary' => $net,
        ];
    }

    public function calculateNetSalary(
        float|string $basic,
        float|string $allowances = 0.0,
        float|string $overtimeAmount = 0.0,
        float|string $bonus = 0.0,
        float|string $tax = 0.0,
        float|string $deductions = 0.0,
        float|string $otherDeductions = 0.0
    ): array {
        return $this->calculate($basic, $allowances, $overtimeAmount, $bonus, $tax, $deductions, $otherDeductions);
    }

    public function calculateOvertimeAmount(float|string $hours, float|string $rate): string
    {
        $h = max(0.0, (float)$hours);
        $r = max(0.0, (float)$rate);
        return number_format($h * $r, 2, '.', '');
    }
}
