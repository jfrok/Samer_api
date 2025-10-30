<?php

// Simple test script to debug the products API endpoint
require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Product;
use App\Http\Resources\ProductResource;

// Set up Laravel app context
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "Testing Product model and resource...\n\n";

    // Test fetching a single product
    $product = Product::active()
        ->with([
            'category',
            'variants' => function ($q) {
                $q->where('stock', '>', 0);
            }
        ])
        ->first();

    if (!$product) {
        echo "No products found!\n";
        exit(1);
    }

    echo "Found product: {$product->name}\n";
    echo "Category: " . ($product->category ? $product->category->name : 'None') . "\n";
    echo "Variants count: " . $product->variants->count() . "\n";

    // Test the resource transformation
    $resource = new ProductResource($product);
    $array = $resource->toArray(new \Illuminate\Http\Request());

    echo "\nResource transformation successful!\n";
    echo "Product ID: {$array['id']}\n";
    echo "Product Name: {$array['name']}\n";
    echo "Variants in resource: " . count($array['variants']) . "\n";

    // Test collection
    echo "\nTesting collection...\n";
    $products = Product::active()
        ->with([
            'category',
            'variants' => function ($q) {
                $q->where('stock', '>', 0);
            }
        ])
        ->limit(3)
        ->get();

    $collection = ProductResource::collection($products);
    $collectionArray = $collection->toArray(new \Illuminate\Http\Request());

    echo "Collection transformation successful!\n";
    echo "Products in collection: " . count($collectionArray) . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
