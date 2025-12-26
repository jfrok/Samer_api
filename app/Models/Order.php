<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'reference_number', 'order_number', 'status', 'total_amount', 'discount_amount', 'shipping_address_id', 'phone',
        'payment_method', 'tracking_number', 'notes', 'ordered_at', 'payment_status',
        'customer_first_name', 'customer_last_name', 'customer_email'
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'shipping_address_id' => 'integer',
        'total_amount' => 'float',
        'discount_amount' => 'float',
        'ordered_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($order) {
            $order->ordered_at = now();

            // Generate reference number: REF-YYYYMMDD-XXXX (e.g., REF-20251117-1234)
            $date = now()->format('Ymd');
            $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $order->reference_number = "REF-{$date}-{$random}";

            // Generate order number: ORD-XXXXXXXXX (e.g., ORD-674A2B1C3)
            $order->order_number = 'ORD-' . strtoupper(uniqid());
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
