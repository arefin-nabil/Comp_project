<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ShopperUpgrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'ulid',
        'user_id',
        'branch_id',
        'payment_method',
        'amount',
        'status',
        'receipt_number',
        'gateway_transaction_id',
        'gateway_response',
        'referrer_amount',
        'own_wallet_amount',
        'company_amount',
        'onboarding_allocation',
        'split_processed',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'idempotency_key',
    ];

    protected $casts = [
        'amount' => 'string',
        'gateway_response' => 'array',
        'referrer_amount' => 'string',
        'own_wallet_amount' => 'string',
        'company_amount' => 'string',
        'onboarding_allocation' => 'string',
        'split_processed' => 'boolean',
        'approved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->ulid)) {
                $model->ulid = (string) Str::ulid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
