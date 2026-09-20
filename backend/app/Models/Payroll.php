<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;

class Payroll
{
    use BelongsToOrganization;

    protected string $table = 'payroll';

    public ?int $id = null;
    public int $organization_id;
    public int $payroll_period_id;
    public int $employee_id;
    public float $basic_salary = 0.00;
    public float $allowances = 0.00;
    public float $overtime_hours = 0.00;
    public float $overtime_amount = 0.00;
    public float $bonus = 0.00;
    public float $tax = 0.00;
    public float $deductions = 0.00;
    public float $other_deductions = 0.00;
    public float $gross_salary = 0.00;
    public float $net_salary = 0.00;
    public string $payment_status = 'pending'; // 'pending','processed','paid','cancelled'
    public ?string $payment_date = null;
    public ?string $payment_reference = null;
    public ?string $remarks = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $val) {
            if (property_exists($this, $key)) {
                $this->{$key} = $val;
            }
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'payroll_period_id' => $this->payroll_period_id,
            'employee_id' => $this->employee_id,
            'basic_salary' => $this->basic_salary,
            'allowances' => $this->allowances,
            'overtime_hours' => $this->overtime_hours,
            'overtime_amount' => $this->overtime_amount,
            'bonus' => $this->bonus,
            'tax' => $this->tax,
            'deductions' => $this->deductions,
            'other_deductions' => $this->other_deductions,
            'gross_salary' => $this->gross_salary,
            'net_salary' => $this->net_salary,
            'payment_status' => $this->payment_status,
            'payment_date' => $this->payment_date,
            'payment_reference' => $this->payment_reference,
            'remarks' => $this->remarks,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
