<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'ulid',
        'full_name',
        'phone',
        'password',
        'referral_id',
        'referred_by',
        'status',
        'nid',
        'address',
        'profile_photo',
        'total_lifetime_earned',
        'club_income_eligible',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'nid' => 'encrypted',
        'total_lifetime_earned' => 'string', // Decimal to string
        'club_income_eligible' => 'boolean',
        'two_factor_confirmed' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->ulid)) {
                $model->ulid = (string) Str::ulid();
            }
            if (empty($model->referral_id)) {
                $model->referral_id = strtoupper(Str::random(8));
            }
        });
    }

    // Relationships
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function wallet(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Wallet::class, 'owner');
    }

    public function clubs(): HasMany
    {
        return $this->hasMany(Club::class);
    }

    public function teamIncomeRecords(): HasMany
    {
        return $this->hasMany(TeamIncomeRecord::class, 'recipient_id');
    }

    public function royaltyCounter(): HasOne
    {
        return $this->hasOne(RoyaltyCounter::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCanReceiveIncome($query)
    {
        return $query->whereIn('status', ['active', 'suspended', 'blocked']); // Even blocked users can receive passive income per spec
    }

    public function scopeClubEligible($query)
    {
        return $query->where('club_income_eligible', true);
    }

    // Helpers
    public function isShopper(): bool
    {
        return $this->hasRole('shopper');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('admin');
    }
}
