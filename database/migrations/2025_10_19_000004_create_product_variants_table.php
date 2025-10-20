<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('size'); // e.g., "S", "M", "42"
            $table->string('color');
            $table->decimal('price', 8, 2);
            $table->unsignedInteger('stock');
            $table->string('sku')->unique()->index();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->index('product_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_variants');
    }
};
