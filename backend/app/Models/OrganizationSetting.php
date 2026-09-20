<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;

class OrganizationSetting
{
    use BelongsToOrganization;

    protected string $table = 'organization_settings';

    public ?int $id = null;
    public int $organization_id;
    public string $setting_key;
    public ?string $setting_value = null;
    public string $setting_type = 'string'; // 'string','integer','decimal','boolean','json'
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

    public function getCastedValue(): mixed
    {
        if ($this->setting_value === null) {
            return null;
        }
        return match ($this->setting_type) {
            'integer' => (int)$this->setting_value,
            'decimal' => (float)$this->setting_value,
            'boolean' => in_array(strtolower($this->setting_value), ['1', 'true', 'yes', 'on'], true),
            'json' => json_decode($this->setting_value, true),
            default => $this->setting_value,
        };
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'setting_key' => $this->setting_key,
            'setting_value' => $this->setting_value,
            'setting_type' => $this->setting_type,
            'value' => $this->getCastedValue(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
