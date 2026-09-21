<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToOrganization;

class EventParticipant extends Model
{
    use BelongsToOrganization;

    protected $table = 'event_participants';
    protected $guarded = ['id'];

    public const UPDATED_AT = null;
}
