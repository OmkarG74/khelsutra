<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiclePosition extends Model
{
    protected $table = 'vehicle_positions';
    public $timestamps = false;
    
    protected $fillable = [
        'organization_id',
        'vehicle_id',
        'latitude',
        'longitude',
        'speed',
        'heading',
        'recorded_at',
        'created_at'
    ];
}
