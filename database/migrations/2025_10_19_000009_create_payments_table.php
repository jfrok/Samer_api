<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->unique();
            $table->decimal('amount', 8, 2);
            $table->enum('status', ['pending', 'completed', 'failed'])->index();
            $table->string('transaction_id');
            $table->string('payment_gateway');
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->index('order_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};
