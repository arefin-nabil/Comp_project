<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamIncomeRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'recipient_id',
        'source_user_id',
        'level',
        'rate',
        'points_value',
        'amount',
        'wallet_transaction_id',
        'is_company_fund',
    ];

    protected $casts = [
        'rate' => 'string',
        'points_value' => 'string',
        'amount' => 'string',
        'is_company_fund' => 'boolean',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function sourceUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'source_user_id');
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }
}
