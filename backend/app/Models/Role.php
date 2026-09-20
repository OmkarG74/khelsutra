<?php

namespace App\Models;

class Role
{
    protected string $table = 'roles';

    public ?int $id = null;
    public string $name;
    public ?string $description = null;
    public bool $is_system_role = true;

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
            'name' => $this->name,
            'description' => $this->description,
            'is_system_role' => $this->is_system_role,
        ];
    }
}
