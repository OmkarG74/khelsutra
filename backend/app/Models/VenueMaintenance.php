<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\SoftDeletes;

class VenueMaintenance extends Model
{
    use BelongsToOrganization, SoftDeletes;

    protected $table = 'venue_maintenance';
    protected $guarded = ['id'];
}
