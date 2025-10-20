<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->unsignedBigInteger('category_id');
            $table->string('brand')->nullable();
            $table->decimal('base_price', 8, 2);
            $table->json('images');
            $table->string('slug')->unique()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->index('category_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
};
