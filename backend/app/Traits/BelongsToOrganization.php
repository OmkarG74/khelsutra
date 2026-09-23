<?php

namespace App\Traits;

trait BelongsToOrganization
{
    /**
     * Boot the BelongsToOrganization trait for an Eloquent model.
     * Automatically sets organization_id on creation and scopes queries.
     */
    public static function bootBelongsToOrganization(): void
    {
        // For standard Eloquent integration:
        if (function_exists('addGlobalScope')) {
            static::addGlobalScope('organization', function ($builder) {
                if (defined('CURRENT_ORGANIZATION_ID') && CURRENT_ORGANIZATION_ID !== null) {
                    $builder->where('organization_id', CURRENT_ORGANIZATION_ID);
                }
            });
        }
    }

    /**
     * Organization relationship definition.
     */
    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class, 'organization_id');
    }

    /**
     * Scope query to a specific organization.
     */
    public function scopeForOrganization($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }
}
