<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use BelongsToOrganization, SoftDeletes;

    protected $table = 'events';
    protected $guarded = ['id'];
}
