<?php

namespace App\Models;

class Sport
{
    protected string $table = 'sports';

    public ?int $id = null;
    public ?int $organization_id = null;
    public string $name;
    public ?string $code = null;
    public ?string $description = null;
    public string $status = 'active';
    public bool $is_global = false;

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
            'code' => $this->code,
            'description' => $this->description,
            'status' => $this->status,
            'is_global' => $this->is_global,
        ];
    }
}
