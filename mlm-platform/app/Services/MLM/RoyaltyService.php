<?php

namespace App\Services\MLM;

use App\Models\RoyaltyCounter;
use App\Models\User;
use App\Models\Club;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;

class RoyaltyService
{
    public function __construct(protected WalletService $walletService) {}

    /**
     * Check if a user's direct referrals have activated enough clubs to trigger royalty.
     * Rule: Every 50 club activations from direct referrals = 3000 tk royalty.
     */
    public function process(User $referrer): void
    {
        DB::transaction(function () use ($referrer) {
            $counter = RoyaltyCounter::firstOrCreate(
                ['user_id' => $referrer->id],
                ['total_direct_clubs' => 0, 'last_paid_at_count' => 0, 'total_royalty_earned' => '0.0000']
            );

            // Lock counter for update
            $counter = RoyaltyCounter::where('id', $counter->id)->lockForUpdate()->first();

            // Count total active clubs from all direct referrals (Level 1)
            $directReferrals = User::where('referred_by', $referrer->id)->pluck('id');
            $currentDirectClubCount = Club::whereIn('user_id', $directReferrals)->count();

            $triggerCount = config('mlm.royalty_trigger_count', 50); // 50
            $royaltyAmount = config('mlm.royalty_amount', 3000.0000); // 3000

            $unpaidClubs = $currentDirectClubCount - $counter->last_paid_at_count;

            if ($unpaidClubs >= $triggerCount) {
                // Determine how many 50-club blocks we can pay out
                $multiplier = floor($unpaidClubs / $triggerCount);
                $payoutAmount = bcmul((string)$royaltyAmount, (string)$multiplier, 4);
                $clubsPaidFor = $multiplier * $triggerCount;

                $newPaidAtCount = $counter->last_paid_at_count + $clubsPaidFor;

                $idempotencyKey = "royalty:{$referrer->id}:C{$newPaidAtCount}";

                $this->walletService->credit(
                    wallet: $referrer->wallet,
                    amount: $payoutAmount,
                    category: 'royalty',
                    referenceType: RoyaltyCounter::class,
                    referenceId: $counter->id,
                    idempotencyKey: $idempotencyKey,
                    description: "Royalty Bonus for {$clubsPaidFor} direct referral clubs"
                );

                $counter->update([
                    'total_direct_clubs' => $currentDirectClubCount,
                    'last_paid_at_count' => $newPaidAtCount,
                    'total_royalty_earned' => bcadd($counter->total_royalty_earned, $payoutAmount, 4),
                ]);

                // Track lifetime earnings
                $newLifetime = bcadd($referrer->total_lifetime_earned, $payoutAmount, 4);
                $updateData = ['total_lifetime_earned' => $newLifetime];
                
                if (bccomp($newLifetime, '1000.0000', 4) >= 0) {
                    $updateData['club_income_eligible'] = false;
                    Club::where('user_id', $referrer->id)->update(['income_eligible' => false, 'status' => 'income_stopped']);
                }
                
                $referrer->update($updateData);
            } else {
                // Just update the total without paying
                $counter->update([
                    'total_direct_clubs' => $currentDirectClubCount,
                ]);
            }
        });
    }
}
