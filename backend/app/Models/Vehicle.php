<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use BelongsToOrganization, SoftDeletes;

    protected $table = 'vehicles';
    protected $guarded = ['id'];
}
