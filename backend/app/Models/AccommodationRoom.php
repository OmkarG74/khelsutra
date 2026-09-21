<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToOrganization;

class AccommodationRoom extends Model
{
    use BelongsToOrganization;

    protected $table = 'accommodation_rooms';
    protected $guarded = ['id'];
}
