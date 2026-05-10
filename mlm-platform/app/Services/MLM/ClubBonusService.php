<?php

namespace App\Services\MLM;

use App\Models\Club;
use App\Models\ClubBonusPayout;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;

class ClubBonusService
{
    public function __construct(protected WalletService $walletService) {}

    /**
     * Called whenever a new club is created.
     * Checks all pending bonuses and pays out eligible ones.
     */
    public function process(Club $newlyActivatedClub): void
    {
        $currentMaxClubNumber = $newlyActivatedClub->club_number;

        // We need to find clubs that haven't received all 8 bonuses,
        // and check if their next trigger threshold has been crossed.
        
        DB::transaction(function () use ($currentMaxClubNumber, $newlyActivatedClub) {
            
            // To prevent massive queries, we only check clubs that could mathematically trigger right now.
            // But since this is a background job, we can query clubs where bonus_paid_count < 8
            // Optimization: The trigger threshold grows very fast. We can calculate the minimum N that could be triggered.
            
            $clubsToCheck = Club::where('bonus_paid_count', '<', 8)
                                ->lockForUpdate()
                                ->get();

            $bonusAmounts = config('mlm.club_bonus_amounts'); // [1=>200, 2=>400, ... 8=>25600]

            foreach ($clubsToCheck as $club) {
                $n = $club->club_number;
                $k = $club->bonus_paid_count + 1; // The next bonus to pay

                $triggerClubNumber = $this->calculateTrigger($n, $k);

                if ($currentMaxClubNumber >= $triggerClubNumber) {
                    // Trigger condition met!
                    $amount = $bonusAmounts[$k] ?? 0;
                    
                    if ($amount <= 0) continue;

                    $idempotencyKey = "club_bonus:{$club->id}:B{$k}";
                    $user = $club->user;

                    // Pay the bonus
                    $txn = $this->walletService->credit(
                        wallet: $user->wallet,
                        amount: (string) $amount,
                        category: 'club_bonus',
                        referenceType: Club::class,
                        referenceId: $club->id,
                        idempotencyKey: $idempotencyKey,
                        description: "Club {$n} Bonus {$k}"
                    );

                    ClubBonusPayout::create([
                        'club_id' => $club->id,
                        'bonus_number' => $k,
                        'amount' => $amount,
                        'trigger_club_id' => $newlyActivatedClub->id,
                        'wallet_transaction_id' => $txn->id,
                        'status' => 'credited',
                    ]);

                    $club->increment('bonus_paid_count');

                    // Track lifetime earnings for income cap
                    $newLifetime = bcadd($user->total_lifetime_earned, (string)$amount, 4);
                    $updateData = ['total_lifetime_earned' => $newLifetime];
                    
                    if (bccomp($newLifetime, '1000.0000', 4) >= 0) {
                        $updateData['club_income_eligible'] = false;
                        Club::where('user_id', $user->id)->update(['income_eligible' => false, 'status' => 'income_stopped']);
                    }
                    
                    $user->update($updateData);
                }
            }
        });
    }

    /**
     * Calculate the global club number that triggers the k-th bonus for club N.
     * T(N,1) = 3N + 1
     * T(N,k) = T(N,k-1) + 3^k
     */
    public function calculateTrigger(int $n, int $k): int
    {
        if ($k === 1) {
            return 3 * $n + 1;
        }

        $trigger = 3 * $n + 1;
        for ($i = 2; $i <= $k; $i++) {
            $trigger += pow(3, $i);
        }
        
        return (int) $trigger;
    }
}
