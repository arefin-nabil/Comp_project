<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'ulid',
        'user_id',
        'type',
        'branch_id',
        'requested_amount',
        'vat_amount',
        'payable_amount',
        'branch_split_amount',
        'company_split_amount',
        'gateway',
        'account_number',
        'status',
        'otp_verified',
        'approved_by',
        'approved_at',
        'wallet_transaction_id',
        'gateway_transaction_id',
        'notes',
        'idempotency_key',
    ];

    protected $casts = [
        'requested_amount' => 'string',
        'vat_amount' => 'string',
        'payable_amount' => 'string',
        'branch_split_amount' => 'string',
        'company_split_amount' => 'string',
        'otp_verified' => 'boolean',
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

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
