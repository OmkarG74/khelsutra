<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Geofence extends Model
{
    protected $table = 'geofences';
    
    protected $fillable = [
        'organization_id',
        'name',
        'venue_id',
        'latitude',
        'longitude',
        'radius_meters'
    ];
}
