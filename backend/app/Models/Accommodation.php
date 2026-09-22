<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\SoftDeletes;

class Accommodation extends Model
{
    use BelongsToOrganization, SoftDeletes;

    protected $table = 'accommodations';
    protected $guarded = ['id'];
}
