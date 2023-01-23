<?php

namespace App\Models;

class UserAddress extends Model
{
    protected $fillable = [
        'user_id',
        'address',
        'latitude',
        'longitude',
        'is_primary',
        'sequence',
    ];

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}
