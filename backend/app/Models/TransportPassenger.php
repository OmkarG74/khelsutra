<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToOrganization;

class TransportPassenger extends Model
{
    use BelongsToOrganization;

    protected $table = 'transport_passengers';
    protected $guarded = ['id'];

    public const UPDATED_AT = null;
}
