<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;

class Employee
{
    use BelongsToOrganization;

    protected string $table = 'employees';

    public ?int $id = null;
    public int $organization_id;
    public ?int $user_id = null;
    public string $employee_code;
    public string $first_name;
    public ?string $last_name = null;
    public ?int $department_id = null;
    public ?string $designation = null;
    public ?string $phone = null;
    public ?string $email = null;
    public string $employment_status = 'active';
    public ?string $joining_date = null;

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $val) {
            if (property_exists($this, $key)) {
                $this->{$key} = $val;
            }
        }
    }

    public function getFullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'employee_code' => $this->employee_code,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->getFullName(),
            'designation' => $this->designation,
            'phone' => $this->phone,
            'email' => $this->email,
            'employment_status' => $this->employment_status,
            'joining_date' => $this->joining_date,
        ];
    }
}
