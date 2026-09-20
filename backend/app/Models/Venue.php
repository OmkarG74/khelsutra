<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;

class Venue
{
    use BelongsToOrganization;

    protected string $table = 'venues';

    public ?int $id = null;
    public int $organization_id;
    public string $name;
    public ?string $code = null;
    public ?string $address_line1 = null;
    public ?string $city = null;
    public ?string $state = null;
    public ?float $latitude = null;
    public ?float $longitude = null;
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
            'name' => $this->name,
            'code' => $this->code,
            'city' => $this->city,
            'state' => $this->state,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
        ];
    }
}
