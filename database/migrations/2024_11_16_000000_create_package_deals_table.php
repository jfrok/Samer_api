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
        // Create package_deals table
        Schema::create('package_deals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('original_price', 10, 2); // Sum of individual product prices
            $table->decimal('package_price', 10, 2); // Discounted package price
            $table->integer('discount_percentage')->default(0);
            $table->json('images')->nullable(); // Array of image URLs
            $table->boolean('is_active')->default(true);
            $table->integer('stock')->default(0); // How many packages available
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index('slug');
            $table->index('is_active');
        });

        // Create pivot table for package_deals and products
        Schema::create('package_deal_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_deal_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1); // How many of this product in the package
            $table->timestamps();

            $table->unique(['package_deal_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_deal_product');
        Schema::dropIfExists('package_deals');
    }
};
