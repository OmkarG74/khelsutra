<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;

class OrganizationAccessLog
{
    use BelongsToOrganization;

    protected string $table = 'organization_access_logs';

    public ?int $id = null;
    public int $organization_id;
    public string $action; // 'created','activated','suspended','expired','renewed','deactivated'
    public ?string $previous_status = null;
    public ?string $new_status = null;
    public ?string $access_start_date = null;
    public ?string $access_end_date = null;
    public ?int $performed_by = null;
    public ?string $remarks = null;
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
            'action' => $this->action,
            'previous_status' => $this->previous_status,
            'new_status' => $this->new_status,
            'access_start_date' => $this->access_start_date,
            'access_end_date' => $this->access_end_date,
            'performed_by' => $this->performed_by,
            'remarks' => $this->remarks,
            'created_at' => $this->created_at,
        ];
    }
}
