<?php

namespace App\Models;

class Permission
{
    protected string $table = 'permissions';

    public ?int $id = null;
    public string $name;
    public string $module;
    public string $action;
    public ?string $description = null;

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
            'module' => $this->module,
            'action' => $this->action,
            'description' => $this->description,
        ];
    }
}
