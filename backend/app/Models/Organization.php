<?php

namespace App\Models;

class Organization
{
    protected string $table = 'organizations';

    public ?int $id = null;
    public string $organization_code;
    public string $name;
    public ?string $legal_name = null;
    public ?string $email = null;
    public ?string $phone = null;
    public ?string $website = null;
    public ?string $city = null;
    public ?string $state = null;
    public string $country = 'India';
    public ?float $latitude = null;
    public ?float $longitude = null;
    public string $status = 'pending';
    public ?string $plan_name = null;

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
            'organization_code' => $this->organization_code,
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'plan_name' => $this->plan_name,
        ];
    }
}
