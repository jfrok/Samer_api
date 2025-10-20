<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'type', 'value', 'min_order_amount', 'max_uses', 'uses_count',
        'start_date', 'end_date', 'is_active'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'max_uses' => 'integer',
        'uses_count' => 'integer',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', Carbon::now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', Carbon::now());
            });
    }

    // Method to check if applicable (use in controller)
    public function isApplicable($orderAmount)
    {
        return $this->active && $this->uses_count < $this->max_uses && $orderAmount >= $this->min_order_amount;
    }

    // Calculate discount amount
    public function calculateDiscount($orderAmount)
    {
        if ($this->type === 'percentage') {
            return $orderAmount * ($this->value / 100);
        }
        return min($this->value, $orderAmount);
    }
}
