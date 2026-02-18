<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'name', 'description', 'category_id', 'brand', 'base_price', 'images', 'slug', 'is_active'
    ];

    protected $casts = [
        'id' => 'integer',
        'category_id' => 'integer',
        'base_price' => 'float',
        'images' => 'array',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'images' => '[]',  // Default empty JSON array for backward compatibility
        'is_active' => true,
    ];

    // Accessors
    public function getImageUrlAttribute()
    {
        // Return the first image from the images array, or a placeholder
        if ($this->images && is_array($this->images) && count($this->images) > 0) {
            return $this->images[0];
        }
        return '/api/placeholder/300/300';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        // Main product image
        $this->addMediaCollection('main_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
            ->registerMediaConversions(function () {
                $this->addMediaConversion('thumb')
                    ->width(150)
                    ->height(150)
                    ->sharpen(10);

                $this->addMediaConversion('medium')
                    ->width(500)
                    ->height(500)
                    ->optimize();

                $this->addMediaConversion('large')
                    ->width(1200)
                    ->height(1200)
                    ->quality(90);
            });

        // Product gallery (multiple images)
        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
            ->registerMediaConversions(function () {
                $this->addMediaConversion('thumb')
                    ->width(200)
                    ->height(200)
                    ->sharpen(10);

                $this->addMediaConversion('medium')
                    ->width(600)
                    ->height(600)
                    ->optimize();

                $this->addMediaConversion('large')
                    ->width(1200)
                    ->height(1200)
                    ->quality(85);
            });
    }

    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'user_liked_products')->withTimestamps();
    }
}
