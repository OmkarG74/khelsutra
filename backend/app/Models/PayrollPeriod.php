<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;

class PayrollPeriod
{
    use BelongsToOrganization;

    protected string $table = 'payroll_periods';

    public ?int $id = null;
    public int $organization_id;
    public string $period_name;
    public string $start_date;
    public string $end_date;
    public string $status = 'draft'; // 'draft','processing','processed','locked'
    public ?int $processed_by = null;
    public ?string $processed_at = null;
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

    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'period_name' => $this->period_name,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'processed_by' => $this->processed_by,
            'processed_at' => $this->processed_at,
            'is_locked' => $this->isLocked(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
