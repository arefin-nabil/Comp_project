<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'ulid',
        'shopper_id',
        'customer_id',
        'transfer_amount',
        'cashback_rate',
        'cashback_amount',
        'points_rate',
        'points_value',
        'points_awarded',
        'team_income_pool',
        'team_income_distributed',
        'team_income_distributed_at',
        'idempotency_key',
        'status',
    ];

    protected $casts = [
        'transfer_amount' => 'string',
        'cashback_rate' => 'string',
        'cashback_amount' => 'string',
        'points_rate' => 'string',
        'points_value' => 'string',
        'points_awarded' => 'string',
        'team_income_pool' => 'string',
        'team_income_distributed' => 'boolean',
        'team_income_distributed_at' => 'datetime',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
