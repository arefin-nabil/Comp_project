<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoyaltyCounter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_direct_clubs',
        'last_paid_at_count',
        'total_royalty_earned',
    ];

    protected $casts = [
        'total_royalty_earned' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
