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
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');

        // 👇 ត្រូវប្រាកដថាមាន ៥ បន្ទាត់នេះនៅខាងក្នុង Schema::create
        $table->integer('coins')->default(250);
        $table->integer('streak_days')->default(7);
        $table->string('rank_title')->default('Hanuman IV');
        $table->float('studied_hours')->default(2.5);
        $table->float('target_hours')->default(4.0);

        $table->rememberToken();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
