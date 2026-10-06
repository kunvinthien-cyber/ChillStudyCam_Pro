<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiMessage extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_voice' => 'boolean',
        'is_starred' => 'boolean',
    ];

    public function conversation()
    {
        return $this->belongsTo(AiConversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
