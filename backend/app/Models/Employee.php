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
    public ?string $middle_name = null;
    public ?string $last_name = null;
    public ?string $photo_path = null;
    public ?string $date_of_birth = null;
    public string $gender = 'not_specified';
    public ?string $blood_group = null;
    public ?string $phone = null;
    public ?string $email = null;
    public ?string $address_line1 = null;
    public ?string $address_line2 = null;
    public ?string $city = null;
    public ?string $state = null;
    public string $country = 'India';
    public ?string $postal_code = null;
    public ?float $latitude = null;
    public ?float $longitude = null;
    public ?int $department_id = null;
    public ?int $employee_category_id = null;
    public ?string $designation = null;
    public ?string $joining_date = null;
    public string $employment_type = 'full_time';
    public string $employment_status = 'active';
    public ?string $emergency_contact_name = null;
    public ?string $emergency_contact_phone = null;
    public ?string $emergency_contact_relationship = null;
    public ?string $bank_name = null;
    public ?string $bank_account_number = null;
    public ?string $bank_ifsc = null;
    public ?string $notes = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public ?string $deleted_at = null;

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
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'user_id' => $this->user_id,
            'employee_code' => $this->employee_code,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'full_name' => $this->getFullName(),
            'photo_path' => $this->photo_path,
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
            'blood_group' => $this->blood_group,
            'phone' => $this->phone,
            'email' => $this->email,
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'department_id' => $this->department_id,
            'employee_category_id' => $this->employee_category_id,
            'designation' => $this->designation,
            'joining_date' => $this->joining_date,
            'employment_type' => $this->employment_type,
            'employment_status' => $this->employment_status,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'emergency_contact_relationship' => $this->emergency_contact_relationship,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_ifsc' => $this->bank_ifsc,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
