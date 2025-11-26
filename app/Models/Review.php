<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'rating',
        'comment',
        'is_verified_purchase',
        'is_approved',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'user_id', // Don't expose user IDs directly
    ];

    /**
     * Get the user who wrote the review
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product being reviewed
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to get only approved reviews
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope to get verified purchase reviews
     */
    public function scopeVerifiedPurchase($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * Scope to order by most recent
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope to order by rating
     */
    public function scopeByRating($query, $direction = 'desc')
    {
        return $query->orderBy('rating', $direction);
    }
}
