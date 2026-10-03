<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomParticipant extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_in_voice' => 'boolean',
        'is_muted' => 'boolean',
        'last_seen_at' => 'datetime',
    ];
}
