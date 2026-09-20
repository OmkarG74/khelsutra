<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;

class Department
{
    use BelongsToOrganization;

    protected string $table = 'departments';

    public ?int $id = null;
    public int $organization_id;
    public string $name;
    public ?string $description = null;
    public string $status = 'active';
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

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
