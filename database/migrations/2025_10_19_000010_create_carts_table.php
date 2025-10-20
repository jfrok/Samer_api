<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();  // Nullable for guest carts
            $table->unsignedBigInteger('product_variant_id');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('cascade');
            $table->unique(['user_id', 'product_variant_id']);  // Prevent duplicates
            $table->index(['user_id', 'product_variant_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('carts');
    }
};
