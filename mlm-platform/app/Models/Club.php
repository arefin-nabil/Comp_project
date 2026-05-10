<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Club extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'club_number',
        'points_consumed',
        'points_before',
        'points_after',
        'status',
        'income_eligible',
        'bonus_paid_count',
        'activated_at',
    ];

    protected $casts = [
        'points_consumed' => 'string',
        'points_before' => 'string',
        'points_after' => 'string',
        'income_eligible' => 'boolean',
        'activated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function incomeDistributions(): HasMany
    {
        return $this->hasMany(ClubIncomeDistribution::class);
    }

    public function bonusPayouts(): HasMany
    {
        return $this->hasMany(ClubBonusPayout::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeIncomeEligible($query)
    {
        return $query->where('income_eligible', true);
    }
}
