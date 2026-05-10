<?php

namespace App\Services\MLM;

use App\Models\Club;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;

class ClubActivationService
{
    /**
     * Consume 100 points to activate a club.
     * Uses Redis lock for atomic global club_number sequence.
     */
    public function activate(User $user): ?Club
    {
        $wallet = $user->wallet()->lockForUpdate()->first();
        $activationPoints = config('mlm.club_activation_points'); // 100

        if (bccomp($wallet->points_balance, $activationPoints, 4) === -1) {
            return null; // Not enough points
        }

        return DB::transaction(function () use ($user, $wallet, $activationPoints) {
            // Must hold Redis lock to prevent race conditions on club_number assignment
            $lock = Cache::lock('global_club_activation_lock', 10);
            
            if (!$lock->block(5)) {
                throw new Exception("System busy processing clubs. Please retry.");
            }

            try {
                // Re-fetch to be safe
                $lockedWallet = $user->wallet()->lockForUpdate()->first();
                $pointsBefore = $lockedWallet->points_balance;
                
                if (bccomp($pointsBefore, $activationPoints, 4) === -1) {
                    return null;
                }

                $pointsAfter = bcsub($pointsBefore, $activationPoints, 4);

                // Get next club number
                $lastClub = Club::orderBy('club_number', 'desc')->first();
                $nextClubNumber = $lastClub ? $lastClub->club_number + 1 : 1;

                // Mark income_eligible true if user is below 1000 limit
                $incomeEligible = bccomp($user->total_lifetime_earned, '1000.0000', 4) === -1;

                $club = Club::create([
                    'user_id' => $user->id,
                    'club_number' => $nextClubNumber,
                    'points_consumed' => $activationPoints,
                    'points_before' => $pointsBefore,
                    'points_after' => $pointsAfter,
                    'status' => 'active',
                    'income_eligible' => $incomeEligible,
                    'activated_at' => now(),
                ]);

                // Update Wallet Points
                $lockedWallet->update(['points_balance' => $pointsAfter]);

                // Set user club_income_eligible if first time
                if ($incomeEligible && !$user->club_income_eligible) {
                    $user->update(['club_income_eligible' => true]);
                }

                return $club;
            } finally {
                $lock->release();
            }
        });
    }
}
