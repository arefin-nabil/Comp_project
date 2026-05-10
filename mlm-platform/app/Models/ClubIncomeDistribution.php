<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubIncomeDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'user_id',
        'club_id',
        'amount',
        'wallet_transaction_id',
        'status',
    ];

    protected $casts = [
        'amount' => 'string',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ClubIncomeBatch::class, 'batch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }
}
