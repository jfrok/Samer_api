<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending')->index();
            $table->decimal('total_amount', 8, 2);
            $table->decimal('discount_amount', 8, 2)->default(0);
            $table->unsignedBigInteger('shipping_address_id');
            $table->string('payment_method');
            $table->string('tracking_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('ordered_at');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('shipping_address_id')->references('id')->on('addresses')->onDelete('restrict');
            $table->index(['user_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
