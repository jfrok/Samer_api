<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['product_id', 'size', 'color', 'price', 'stock', 'sku'];

    protected $casts = [
        'id' => 'integer',
        'product_id' => 'integer',
        'price' => 'float',
        'stock' => 'integer',
    ];
    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItems()
    {
        return $this->hasMany(Cart::class, 'product_variant_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_variant_id');
    }
}
