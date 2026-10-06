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
    Schema::table('room_participants', function (Blueprint $table) {
        $table->string('study_goal')->default('រៀនផ្ដោតអារម្មណ៍ ២៥ នាទី');
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_participants', function (Blueprint $table) {
            $table->dropColumn('study_goal');
        });
    }
};
