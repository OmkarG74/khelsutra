<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToOrganization;

class AccommodationRoom extends Model
{
    use BelongsToOrganization;

    protected $table = 'accommodation_rooms';
    protected $guarded = ['id'];

    protected $casts = [
        'organization_id' => 'integer',
        'accommodation_id' => 'integer',
        'capacity' => 'integer',
    ];

    public function accommodation()
    {
        return $this->belongsTo(Accommodation::class);
    }
}
