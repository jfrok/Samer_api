<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::active();
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->with('variants')->paginate(20);  // Paginate for speed

        return ProductResource::collection($products);
    }

    public function show(Product $product)
    {
        $product->load('variants.inStock');

        return new ProductResource($product);
    }
}
