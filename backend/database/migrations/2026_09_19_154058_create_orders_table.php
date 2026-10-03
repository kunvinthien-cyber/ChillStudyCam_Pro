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
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->nullable();
        $table->foreignId('product_id');
        $table->string('fulfillment_type'); // 'pickup' ឬ 'delivery'
        $table->text('delivery_address')->nullable();
        $table->string('phone_number')->nullable();
        $table->integer('pts_used')->default(0);
        $table->decimal('final_cash_amount', 8, 2);
        $table->string('status')->default('completed'); // pending, completed
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
