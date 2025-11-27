<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PackageDeal extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'original_price',
        'package_price',
        'discount_percentage',
        'images',
        'is_active',
        'stock',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'images' => 'array',
        'original_price' => 'decimal:2',
        'package_price' => 'decimal:2',
        'discount_percentage' => 'integer',
        'is_active' => 'boolean',
        'stock' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($packageDeal) {
            if (empty($packageDeal->slug)) {
                $packageDeal->slug = Str::slug($packageDeal->name);
            }
        });

        static::updating(function ($packageDeal) {
            if ($packageDeal->isDirty('name') && empty($packageDeal->slug)) {
                $packageDeal->slug = Str::slug($packageDeal->name);
            }
        });
    }

    /**
     * Get the products included in this package deal
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'package_deal_product')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Check if package is currently active and available
     */
    public function isAvailable(): bool
    {
        if (!$this->is_active || $this->stock <= 0) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * Get savings amount
     */
    public function getSavingsAttribute(): float
    {
        return (float) ($this->original_price - $this->package_price);
    }

    /**
     * Scope: Only active packages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Only available packages (active + in date range + in stock)
     */
    public function scopeAvailable($query)
    {
        $now = now();

        return $query->where('is_active', true)
            ->where('stock', '>', 0)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            });
    }

    /**
     * Calculate total discount percentage
     */
    public function calculateDiscount(): void
    {
        if ($this->original_price > 0) {
            $this->discount_percentage = (int) round(
                (($this->original_price - $this->package_price) / $this->original_price) * 100
            );
        }
    }
}
