<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $guarded = [];

    protected $casts = [
        'avatars' => 'array',
        'is_sanctuary' => 'boolean',
    ];
}
