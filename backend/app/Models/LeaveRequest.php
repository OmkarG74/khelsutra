<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;

class LeaveRequest
{
    use BelongsToOrganization;

    protected string $table = 'leave_requests';

    public ?int $id = null;
    public int $organization_id;
    public string $applicant_type; // 'employee','athlete'
    public ?int $employee_id = null;
    public ?int $athlete_id = null;
    public int $leave_type_id;
    public string $start_date;
    public string $end_date;
    public float $total_days;
    public ?string $reason = null;
    public ?string $attachment_path = null;
    public string $status = 'pending'; // 'pending','approved','rejected','cancelled'
    public ?int $approved_by = null;
    public ?string $approved_at = null;
    public ?string $rejection_reason = null;
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

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'applicant_type' => $this->applicant_type,
            'employee_id' => $this->employee_id,
            'athlete_id' => $this->athlete_id,
            'leave_type_id' => $this->leave_type_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'total_days' => $this->total_days,
            'reason' => $this->reason,
            'attachment_path' => $this->attachment_path,
            'status' => $this->status,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
