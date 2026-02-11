<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'code',
        'country',
        'shipping_price',
        'is_active',
    ];

    protected $casts = [
        'shipping_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Scope a query to only include active cities.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
