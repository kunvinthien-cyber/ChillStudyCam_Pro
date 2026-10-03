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
    Schema::create('room_participants', function (Blueprint $table) {
        $table->id();
        $table->foreignId('room_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id')->nullable();
        $table->string('user_name');
        $table->string('peer_id')->nullable(); // ID សម្រាប់ Call សំឡេង
        $table->boolean('is_in_voice')->default(false);
        $table->boolean('is_muted')->default(false);
        $table->timestamp('last_seen_at')->useCurrent();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_participants');
    }
};
