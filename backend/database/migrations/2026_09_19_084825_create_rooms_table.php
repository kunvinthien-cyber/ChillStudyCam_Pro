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
    Schema::create('rooms', function (Blueprint $table) {
        $table->id();
        $table->foreignId('creator_id')->nullable(); // 👈 អ្នកបង្កើតបន្ទប់ (Admin)
        $table->string('title');
        $table->string('subtitle_khmer');
        $table->text('description')->nullable();
        $table->string('category_tag');
        $table->string('badge_tag')->nullable();
        $table->string('ambient_title')->default('Lofi Beats');
        $table->string('thumbnail');
        $table->integer('active_students')->default(1);
        $table->json('avatars')->nullable();
        $table->integer('more_count')->default(0);
        $table->boolean('is_sanctuary')->default(false);
        $table->string('grade_level')->default('all');
        $table->string('subject_focus')->default('ទូទៅ');
        $table->boolean('is_private')->default(false);
        $table->string('passcode')->nullable(); // លេខកូដ PIN ៤ ខ្ទង់
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
