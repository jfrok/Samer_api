<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class CacheProducts extends Command
{
    protected $signature = 'cache:products {--clear : Clear the cache instead of building it}';
    protected $description = 'Cache active products with variants for faster API responses';

    public function handle()
    {
        $clear = $this->option('clear');

        if ($clear) {
            Cache::forget('products_index');
            $this->info('Product cache cleared!');
            return 0;
        }

        $this->info('Caching products...');

        // Fixed eager loading: Use closure to apply scope on the relation query
        $products = Product::active()
            ->with([
                'category',
                'variants' => function ($query) {
                    $query->inStock();  // This applies the scope correctly
                }
            ])
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'brand' => $product->brand,
                    'base_price' => $product->base_price,
                    'images' => $product->images,
                    'slug' => $product->slug,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                        'slug' => $product->category->slug,
                    ] : null,
                    'variants' => $product->variants->map(function ($variant) {
                        return [
                            'id' => $variant->id,
                            'size' => $variant->size,
                            'color' => $variant->color,
                            'price' => $variant->price,
                            'stock' => $variant->stock,
                            'sku' => $variant->sku,
                        ];
                    }),
                ];
            });

        Cache::put('products_index', $products, 3600);

        $this->info("Cached {$products->count()} products successfully!");
        return 0;
    }
}
