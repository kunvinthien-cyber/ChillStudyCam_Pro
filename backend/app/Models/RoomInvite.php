<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomInvite extends Model
{
    protected $guarded = [];

    // 🔗 ភ្ជាប់ទៅកាន់បន្ទប់រៀន
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // 🔗 ភ្ជាប់ទៅកាន់អ្នកដែលបានផ្ញើការអញ្ជើញ
    public function inviter()
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }
}
