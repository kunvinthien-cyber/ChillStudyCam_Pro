<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiConversation extends Model
{
    protected $guarded = [];

    public function messages()
    {
        return $this->hasMany(AiMessage::class, 'conversation_id')->oldest();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
