<?php

namespace App\Models;

class OrganizationUser
{
    protected string $table = 'organization_users';

    public ?int $id = null;
    public int $organization_id;
    public int $user_id;
    public int $role_id;
    public ?int $employee_id = null;
    public ?int $athlete_id = null;
    public string $access_status = 'active';

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
            'user_id' => $this->user_id,
            'role_id' => $this->role_id,
            'employee_id' => $this->employee_id,
            'athlete_id' => $this->athlete_id,
            'access_status' => $this->access_status,
        ];
    }
}
