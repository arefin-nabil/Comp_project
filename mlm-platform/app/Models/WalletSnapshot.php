<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletSnapshot extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'wallet_id',
        'computed_balance',
        'snapshot_balance',
        'is_match',
        'discrepancy_notes',
        'last_transaction_id',
        'snapshotted_at',
    ];

    protected $casts = [
        'computed_balance' => 'string',
        'snapshot_balance' => 'string',
        'is_match' => 'boolean',
        'snapshotted_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
