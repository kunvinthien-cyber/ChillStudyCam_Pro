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
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('category');       // ភេសជ្ជៈ, អាហារសម្រន់
        $table->decimal('price', 8, 2);   // ឧ. 1.50
        $table->integer('pts_price');     // ឧ. 150
        $table->decimal('pts_discount', 8, 2); // ឧ. 0.50
        $table->string('image');
        $table->string('partner_shop');   // ឧ. Tube Cafe (RUPP)
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
