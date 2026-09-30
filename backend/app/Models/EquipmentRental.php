<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\BelongsToOrganization;

class EquipmentRental extends BaseModel
{
    use BelongsToOrganization;

    protected $table = 'equipment_rentals';

    protected $fillable = [
        'organization_id',
        'inventory_item_id',
        'borrower_type',
        'athlete_id',
        'coach_id',
        'employee_id',
        'team_id',
        'venue_id',
        'borrower_name',
        'borrowed_quantity',
        'returned_quantity',
        'damaged_quantity',
        'start_time',
        'expected_return_time',
        'actual_return_time',
        'condition_on_return',
        'status',
        'issued_by',
        'received_by',
        'notes'
    ];
}
