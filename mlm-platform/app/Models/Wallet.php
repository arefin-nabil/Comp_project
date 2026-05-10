<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'ulid',
        'owner_type',
        'owner_id',
        'balance',
        'points_balance',
        'frozen_balance',
        'is_active',
    ];

    protected $casts = [
        'balance' => 'string',
        'points_balance' => 'string',
        'frozen_balance' => 'string',
        'is_active' => 'boolean',
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

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(WalletSnapshot::class);
    }


}
