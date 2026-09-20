<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;

class SalaryStructure
{
    use BelongsToOrganization;

    protected string $table = 'salary_structures';

    public ?int $id = null;
    public int $organization_id;
    public int $employee_id;
    public string $effective_from;
    public float $basic_salary = 0.00;
    public float $allowances = 0.00;
    public float $deduction = 0.00;
    public float $overtime_rate = 0.00;
    public float $bonus_default = 0.00;
    public float $tax_default = 0.00;
    public float $other_deductions_default = 0.00;
    public string $status = 'active'; // 'active','inactive'
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
            'employee_id' => $this->employee_id,
            'effective_from' => $this->effective_from,
            'basic_salary' => $this->basic_salary,
            'allowances' => $this->allowances,
            'deduction' => $this->deduction,
            'overtime_rate' => $this->overtime_rate,
            'bonus_default' => $this->bonus_default,
            'tax_default' => $this->tax_default,
            'other_deductions_default' => $this->other_deductions_default,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
