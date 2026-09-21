<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\SoftDeletes;

class HousekeepingTask extends Model
{
    use BelongsToOrganization, SoftDeletes;

    protected $table = 'housekeeping_tasks';
    protected $guarded = ['id'];
}
