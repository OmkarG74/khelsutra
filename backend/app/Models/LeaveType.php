<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;

class LeaveType
{
    use BelongsToOrganization;

    protected string $table = 'leave_types';

    public ?int $id = null;
    public int $organization_id;
    public string $name;
    public ?string $description = null;
    public ?float $max_days_per_year = null;
    public string $status = 'active';
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
            'name' => $this->name,
            'description' => $this->description,
            'max_days_per_year' => $this->max_days_per_year,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
