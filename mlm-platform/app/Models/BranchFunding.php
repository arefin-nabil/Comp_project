<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BranchFunding extends Model
{
    use HasFactory;

    protected $fillable = [
        'ulid',
        'branch_id',
        'funded_by',
        'base_amount',
        'incentive_rate',
        'incentive_amount',
        'total_credited',
        'idempotency_key',
        'debit_transaction_id',
        'credit_transaction_id',
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function funder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'funded_by');
    }
}
