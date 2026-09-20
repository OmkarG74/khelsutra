<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;

class Team
{
    use BelongsToOrganization;

    protected string $table = 'teams';

    public ?int $id = null;
    public int $organization_id;
    public int $sport_id;
    public ?int $sport_category_id = null;
    public string $name;
    public ?string $short_name = null;
    public string $gender = 'open';
    public ?int $max_players = null;
    public string $status = 'active';

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
            'sport_id' => $this->sport_id,
            'sport_category_id' => $this->sport_category_id,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'gender' => $this->gender,
            'max_players' => $this->max_players,
            'status' => $this->status,
        ];
    }
}
