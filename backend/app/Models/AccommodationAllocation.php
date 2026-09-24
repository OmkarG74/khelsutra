<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use App\Traits\BelongsToOrganization;
use App\Traits\Auditable;

class AccommodationAllocation extends Model
{
    use BelongsToOrganization, Auditable;

    protected $table = 'accommodation_allocations';
    protected $guarded = ['id'];
}
