<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RegistrationPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ulid',
        'user_id',
        'method',
        'amount',
        'status',
        'receipt_number',
        'verified_by',
        'verified_at',
        'gateway_transaction_id',
        'gateway_response',
        'referrer_amount',
        'company_amount',
        'split_processed',
        'idempotency_key',
        'notes',
    ];

    protected $casts = [
        'amount' => 'string',
        'verified_at' => 'datetime',
        'gateway_response' => 'array',
        'referrer_amount' => 'string',
        'company_amount' => 'string',
        'split_processed' => 'boolean',
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

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
