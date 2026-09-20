<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;

class MatchAttendance
{
    use BelongsToOrganization;

    protected string $table = 'match_attendance';

    public ?int $id = null;
    public int $organization_id;
    public int $match_id;
    public ?int $athlete_id = null;
    public ?int $coach_id = null;
    public ?int $employee_id = null;
    public string $attendance_status; // 'present','absent','late','excused'
    public ?string $remarks = null;
    public ?int $recorded_by = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $val) {
            if (property_exists($this, $key)) {
                $this->{$key} = $val;
            }
        }
    }

    public function validateSingleSubject(): bool
    {
        $count = ($this->athlete_id !== null ? 1 : 0)
               + ($this->coach_id !== null ? 1 : 0)
               + ($this->employee_id !== null ? 1 : 0);
        return $count === 1;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'match_id' => $this->match_id,
            'athlete_id' => $this->athlete_id,
            'coach_id' => $this->coach_id,
            'employee_id' => $this->employee_id,
            'attendance_status' => $this->attendance_status,
            'remarks' => $this->remarks,
            'recorded_by' => $this->recorded_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
