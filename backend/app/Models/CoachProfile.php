<?php

namespace App\Models;

class CoachProfile
{
    protected string $table = 'coach_profiles';

    public ?int $id = null;
    public int $employee_id;
    public ?int $primary_sport_id = null;
    public ?string $license_level = null;
    public ?string $specialization = null;
    public ?int $experience_years = null;
    public ?string $bio = null;

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
            'employee_id' => $this->employee_id,
            'primary_sport_id' => $this->primary_sport_id,
            'license_level' => $this->license_level,
            'specialization' => $this->specialization,
            'experience_years' => $this->experience_years,
            'bio' => $this->bio,
        ];
    }
}
