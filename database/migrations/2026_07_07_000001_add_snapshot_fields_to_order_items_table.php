<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('product_name')->nullable()->after('product_variant_id');
            $table->string('product_slug')->nullable()->after('product_name');
            $table->string('product_image_src', 2048)->nullable()->after('product_slug');
            $table->string('variant_size')->nullable()->after('product_image_src');
            $table->string('variant_color')->nullable()->after('variant_size');
            $table->string('variant_sku')->nullable()->after('variant_color');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'product_name',
                'product_slug',
                'product_image_src',
                'variant_size',
                'variant_color',
                'variant_sku',
            ]);
        });
    }
};
