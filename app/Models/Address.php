<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'type', 'street', 'city', 'state', 'country', 'zip_code', 'is_default'
    ];

    protected $casts = ['is_default' => 'boolean'];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
