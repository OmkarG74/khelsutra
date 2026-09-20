<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;

class Tournament
{
    use BelongsToOrganization;

    protected string $table = 'tournaments';

    public ?int $id = null;
    public int $organization_id;
    public int $sport_id;
    public ?int $tournament_level_id = null;
    public ?int $tournament_format_id = null;
    public string $name;
    public ?string $edition = null;
    public string $start_date;
    public string $end_date;
    public string $status = 'draft';

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
            'tournament_level_id' => $this->tournament_level_id,
            'tournament_format_id' => $this->tournament_format_id,
            'name' => $this->name,
            'edition' => $this->edition,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
        ];
    }
}
