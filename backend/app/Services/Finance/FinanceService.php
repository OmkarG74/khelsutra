<?php

namespace App\Services\Finance;

class FinanceService
{
    public function calculateNetSalary(float $basic, float $allowances, float $overtime, float $bonus, float $deductions, float $tax, float $otherDeductions): float
    {
        return ($basic + $allowances + $overtime + $bonus) - ($deductions + $tax + $otherDeductions);
    }
}
