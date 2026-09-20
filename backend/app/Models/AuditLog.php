<?php

namespace App\Models;

class AuditLog
{
    protected string $table = 'audit_logs';

    public ?int $id = null;
    public ?int $organization_id = null;
    public ?int $user_id = null;
    public string $action;
    public string $module;
    public ?string $table_name = null;
    public ?int $record_id = null;
    public ?array $old_values = null;
    public ?array $new_values = null;
    public ?string $description = null;
    public ?string $ip_address = null;
    public ?string $user_agent = null;
    public ?string $created_at = null;

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $val) {
            if (property_exists($this, $key)) {
                if (($key === 'old_values' || $key === 'new_values') && is_string($val)) {
                    $this->{$key} = json_decode($val, true);
                } else {
                    $this->{$key} = $val;
                }
            }
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'user_id' => $this->user_id,
            'action' => $this->action,
            'module' => $this->module,
            'table_name' => $this->table_name,
            'record_id' => $this->record_id,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'description' => $this->description,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at,
        ];
    }
}
