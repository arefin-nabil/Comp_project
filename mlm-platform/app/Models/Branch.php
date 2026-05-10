<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ulid',
        'branch_name',
        'division',
        'district',
        'upazila',
        'address',
        'contact_phone',
        'status',
        'managed_by',
        'wallet_balance',
    ];

    protected $casts = [
        'wallet_balance' => 'string',
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

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'managed_by');
    }

    public function wallet(): MorphOne
    {
        return $this->morphOne(Wallet::class, 'owner');
    }

    public function shopperUpgrades(): HasMany
    {
        return $this->hasMany(ShopperUpgrade::class);
    }

    public function branchFundings(): HasMany
    {
        return $this->hasMany(BranchFunding::class);
    }

    public function shopperFundings(): HasMany
    {
        return $this->hasMany(ShopperFunding::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
