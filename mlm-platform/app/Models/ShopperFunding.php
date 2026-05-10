<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ShopperFunding extends Model
{
    use HasFactory;

    protected $fillable = [
        'ulid',
        'shopper_id',
        'branch_id',
        'funded_by',
        'base_amount',
        'incentive_rate',
        'incentive_amount',
        'total_credited',
        'idempotency_key',
        'branch_debit_transaction_id',
        'shopper_credit_transaction_id',
        'notes',
    ];

    protected $casts = [
        'base_amount' => 'string',
        'incentive_rate' => 'string',
        'incentive_amount' => 'string',
        'total_credited' => 'string',
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

    public function shopper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shopper_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function funder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'funded_by');
    }
}
