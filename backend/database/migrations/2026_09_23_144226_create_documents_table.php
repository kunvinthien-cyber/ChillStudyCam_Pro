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
    Schema::create('documents', function (Blueprint $table) {
        $table->id();
        $table->string('title'); // ឧ. វិញ្ញាសាគណិតវិទ្យាបាក់ឌុប ២០២៣ + អត្រាកំណែ
        $table->text('description')->nullable();
        $table->string('grade_level'); // 'grade_12', 'university', 'language', 'high_school'
        $table->string('subject'); // 'គណិតវិទ្យា', 'រូបវិទ្យា', 'IELTS', 'Programming'
        $table->string('file_url'); // Link PDF ឬ ឯកសារ
        $table->string('file_type')->default('pdf'); // 'pdf', 'image'
        $table->integer('pts_cost')->default(0); // 0 = Free, ឬត្រូវប្រើ PTS ដោះសោ
        $table->integer('downloads_count')->default(0);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
