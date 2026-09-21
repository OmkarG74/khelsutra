<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\SoftDeletes;

class Facility extends Model
{
    use BelongsToOrganization, SoftDeletes;

    protected $table = 'venue_facilities';
    protected $guarded = ['id'];
}
