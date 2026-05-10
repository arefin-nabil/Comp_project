<?php

namespace App\Services\MLM;

use App\Models\Club;
use App\Models\ClubIncomeBatch;
use App\Models\ClubIncomeDistribution;
use App\Models\Purchase;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class ClubIncomeService
{
    public function __construct(protected WalletService $walletService) {}

    /**
     * Settle club income for a specific date (usually run at midnight for the previous day).
     */
    public function settleNightlyBatch(Carbon $date): ClubIncomeBatch
    {
        return DB::transaction(function () use ($date) {
            $batchDate = $date->toDateString();

            // Prevent duplicate settlement
            $existing = ClubIncomeBatch::where('batch_date', $batchDate)->first();
            if ($existing) {
                return $existing;
            }

            // Calculate total points generated on that date
            $totalPoints = Purchase::whereDate('created_at', $batchDate)->sum('points_value');
            $rate = config('mlm.club_income_rate'); // 0.4 BDT per point
            
            $clubPool = bcmul((string)$totalPoints, (string)$rate, 4);

            // Get eligible members (users who have at least one active club and haven't hit 1000 limit)
            // One user gets ONLY ONE share, regardless of how many clubs they have.
            $eligibleUsers = User::where('club_income_eligible', true)
                                 ->whereIn('status', ['active', 'suspended', 'blocked'])
                                 ->get();
            
            $memberCount = $eligibleUsers->count();
            
            $perMemberAmount = $memberCount > 0 ? bcdiv($clubPool, (string)$memberCount, 4) : '0.0000';

            $batch = ClubIncomeBatch::create([
                'batch_date' => $batchDate,
                'total_points_today' => $totalPoints,
                'club_income_rate' => $rate,
                'club_pool' => $clubPool,
                'eligible_member_count' => $memberCount,
                'per_member_amount' => $perMemberAmount,
                'total_distributed' => '0.0000',
                'status' => 'processing',
                'idempotency_key' => "club_income_batch:{$batchDate}",
            ]);

            if ($memberCount === 0 || bccomp($perMemberAmount, '0.0000', 4) <= 0) {
                $batch->update(['status' => 'completed', 'completed_at' => now()]);
                return $batch;
            }

            $totalDistributed = '0.0000';

            foreach ($eligibleUsers as $user) {
                // Find their first active club to link
                $qualifyingClub = Club::where('user_id', $user->id)->where('status', 'active')->first();
                if (!$qualifyingClub) continue;

                $idempotencyKey = "club_income:{$batch->id}:U{$user->id}";

                $txn = $this->walletService->credit(
                    wallet: $user->wallet,
                    amount: $perMemberAmount,
                    category: 'club_income',
                    referenceType: ClubIncomeBatch::class,
                    referenceId: $batch->id,
                    idempotencyKey: $idempotencyKey,
                    description: "Club Income for {$batchDate}"
                );

                ClubIncomeDistribution::create([
                    'batch_id' => $batch->id,
                    'user_id' => $user->id,
                    'club_id' => $qualifyingClub->id,
                    'amount' => $perMemberAmount,
                    'wallet_transaction_id' => $txn->id,
                    'status' => 'credited',
                ]);

                // Track lifetime earnings
                $newLifetime = bcadd($user->total_lifetime_earned, $perMemberAmount, 4);
                $updateData = ['total_lifetime_earned' => $newLifetime];
                
                if (bccomp($newLifetime, '1000.0000', 4) >= 0) {
                    $updateData['club_income_eligible'] = false;
                    // Also mark all their clubs as income_stopped
                    Club::where('user_id', $user->id)->update(['income_eligible' => false, 'status' => 'income_stopped']);
                }
                
                $user->update($updateData);

                $totalDistributed = bcadd($totalDistributed, $perMemberAmount, 4);
            }

            $batch->update([
                'total_distributed' => $totalDistributed,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return $batch;
        });
    }
}
