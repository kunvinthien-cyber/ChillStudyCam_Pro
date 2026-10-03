<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::create('room_invites', function (Blueprint $table) {
        $table->id();
        $table->foreignId('room_id')->constrained()->onDelete('cascade');
        $table->foreignId('inviter_id')->constrained('users')->onDelete('cascade'); // អ្នក Invite
        $table->foreignId('invitee_id')->constrained('users')->onDelete('cascade'); // អ្នកត្រូវគេ Invite
        $table->string('status')->default('pending'); // 'pending', 'accepted', 'rejected'
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_invites');
    }
};
