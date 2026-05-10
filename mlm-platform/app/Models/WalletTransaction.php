<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class WalletTransaction extends Model
{
    use HasFactory;

    const UPDATED_AT = null; // Immutable ledger

    protected $fillable = [
        'ulid',
        'wallet_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'category',
        'reference_type',
        'reference_id',
        'description',
        'idempotency_key',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'string',
        'balance_before' => 'string',
        'balance_after' => 'string',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->ulid)) {
                $model->ulid = (string) Str::ulid();
            }
        });

        // Prevent updating existing ledger rows
        static::updating(function ($model) {
            return false;
        });
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeCredits($query)
    {
        return $query->where('type', 'credit');
    }

    public function scopeDebits($query)
    {
        return $query->where('type', 'debit');
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('created_at', $date);
    }
}
