<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $guarded = [];

    protected $hidden = ['passcode'];

    protected $casts = [
        'avatars' => 'array',
        'is_sanctuary' => 'boolean',
        'is_private' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function participants()
    {
        return $this->hasMany(RoomParticipant::class);
    }

    public function invites()
    {
        return $this->hasMany(RoomInvite::class);
    }
}
