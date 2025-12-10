<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'product_variant_id', 'quantity', 'price', 'subtotal'
    ];

    protected $casts = [
        'id' => 'integer',
        'order_id' => 'integer',
        'product_variant_id' => 'integer',
        'price' => 'float',
        'subtotal' => 'float',
        'quantity' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($item) {
            $item->subtotal = $item->quantity * $item->price;
        });
    }

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
