<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubBonusPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'bonus_number',
        'amount',
        'trigger_club_id',
        'wallet_transaction_id',
        'status',
    ];

    protected $casts = [
        'amount' => 'string',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function triggerClub(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'trigger_club_id');
    }
}
