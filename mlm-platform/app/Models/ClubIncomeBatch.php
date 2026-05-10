<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClubIncomeBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_date',
        'total_points_today',
        'club_income_rate',
        'club_pool',
        'eligible_member_count',
        'per_member_amount',
        'total_distributed',
        'status',
        'idempotency_key',
        'completed_at',
    ];

    protected $casts = [
        'batch_date' => 'date',
        'total_points_today' => 'string',
        'club_income_rate' => 'string',
        'club_pool' => 'string',
        'per_member_amount' => 'string',
        'total_distributed' => 'string',
        'completed_at' => 'datetime',
    ];

    public function distributions(): HasMany
    {
        return $this->hasMany(ClubIncomeDistribution::class, 'batch_id');
    }
}
