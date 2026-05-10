<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'action',
        'code',
        'is_used',
        'attempts',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function scopeValid($query)
    {
        return $query->where('is_used', false)
                     ->where('expires_at', '>', now())
                     ->where('attempts', '<', 3);
    }
}
