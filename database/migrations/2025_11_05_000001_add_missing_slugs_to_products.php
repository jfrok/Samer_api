<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;
use App\Models\Product;

return new class extends Migration
{
    public function up(): void
    {
        // Find all products without slugs and generate them
        $products = Product::whereNull('slug')->orWhere('slug', '')->get();

        foreach ($products as $product) {
            $slug = Str::slug($product->name);

            // Ensure unique slug
            $originalSlug = $slug;
            $counter = 1;
            while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $product->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        // No need to reverse this
    }
};
