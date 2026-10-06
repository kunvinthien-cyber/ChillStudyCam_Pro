<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // 👈 ១. បន្ថែមបន្ទាត់ Import នេះ

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable; // 👈 ២. បន្ថែម HasApiTokens ត្រង់នេះ

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = []; // អនុញ្ញាតឱ្យបញ្ចូលគ្រប់ column (coins, streak, etc.)

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function aiConversations()
    {
        return $this->hasMany(AiConversation::class);
    }

    public function aiMessages()
    {
        return $this->hasMany(AiMessage::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function roomsCreated()
    {
        return $this->hasMany(Room::class, 'creator_id');
    }

    public function roomParticipants()
    {
        return $this->hasMany(RoomParticipant::class);
    }

    public function sentRoomInvites()
    {
        return $this->hasMany(RoomInvite::class, 'inviter_id');
    }

    public function receivedRoomInvites()
    {
        return $this->hasMany(RoomInvite::class, 'invitee_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function studySessions()
    {
        return $this->hasMany(StudySession::class);
    }
}
