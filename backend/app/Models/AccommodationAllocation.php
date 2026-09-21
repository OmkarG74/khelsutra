<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToOrganization;

class AccommodationAllocation extends Model
{
    use BelongsToOrganization;

    protected $table = 'accommodation_allocations';
    protected $guarded = ['id'];
}
