<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;

class UserPermissionOverride
{
    use BelongsToOrganization;

    protected string $table = 'user_permission_overrides';

    public ?int $id = null;
    public int $organization_id;
    public int $user_id;
    public int $permission_id;
    public string $override_type; // 'grant','deny'
    public ?int $created_by = null;
    public ?string $created_at = null;

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
            'permission_id' => $this->permission_id,
            'override_type' => $this->override_type,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
