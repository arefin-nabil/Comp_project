<?php

namespace App\Services\MLM;

use App\Models\Purchase;
use App\Models\Referral;
use App\Models\TeamIncomeRecord;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Exception;
use Illuminate\Support\Facades\DB;

class TeamIncomeService
{
    public function __construct(protected WalletService $walletService) {}

    /**
     * Distribute 10-level team income for a purchase.
     * Guaranteed exactly-once processing.
     */
    public function distribute(Purchase $purchase): void
    {
        if ($purchase->team_income_distributed) {
            return;
        }

        DB::transaction(function () use ($purchase) {
            $lockedPurchase = Purchase::where('id', $purchase->id)->lockForUpdate()->first();
            if ($lockedPurchase->team_income_distributed) {
                return; // Idempotency check
            }

            $customer = User::find($lockedPurchase->customer_id);
            $referralChain = Referral::where('user_id', $customer->id)->first();
            
            $pointsValue = $lockedPurchase->points_value;
            $rates = config('mlm.team_income_rates'); // [1=>10, 2=>6, 3=>5, 4=>4, 5=>3, 6=>2, 7=>2, 8=>1, 9=>1, 10=>1]
            $companyFundTotal = '0.0000';

            for ($level = 1; $level <= 10; $level++) {
                $rate = $rates[$level];
                $amount = bcmul($pointsValue, bcdiv($rate, 100, 8), 4);
                
                $uplinkField = $level === 1 ? 'referrer_id' : "level{$level}_id";
                $uplineId = $referralChain ? $referralChain->$uplinkField : null;

                if ($uplineId) {
                    $upline = User::find($uplineId);
                    // Check if upline is active/suspended/blocked (all can receive passive income per spec)
                    // But if deleted, skip to company.
                    if ($upline && !$upline->trashed() && in_array($upline->status, ['active', 'blocked', 'suspended'])) {
                        
                        $idempotencyKey = "team_income:{$lockedPurchase->id}:L{$level}:{$upline->id}";
                        
                        $txn = $this->walletService->credit(
                            wallet: $upline->wallet,
                            amount: $amount,
                            category: 'team_income',
                            referenceType: Purchase::class,
                            referenceId: $lockedPurchase->id,
                            idempotencyKey: $idempotencyKey,
                            description: "Level {$level} team income from Purchase {$lockedPurchase->ulid}"
                        );

                        TeamIncomeRecord::create([
                            'purchase_id' => $lockedPurchase->id,
                            'recipient_id' => $upline->id,
                            'source_user_id' => $customer->id,
                            'level' => $level,
                            'rate' => $rate,
                            'points_value' => $pointsValue,
                            'amount' => $amount,
                            'wallet_transaction_id' => $txn->id,
                            'is_company_fund' => false,
                        ]);
                        
                        continue;
                    }
                }

                // If chain broken or user deleted -> Company Fund
                $companyFundTotal = bcadd($companyFundTotal, $amount, 4);
                
                TeamIncomeRecord::create([
                    'purchase_id' => $lockedPurchase->id,
                    'recipient_id' => null, // Company fund marker
                    'source_user_id' => $customer->id,
                    'level' => $level,
                    'rate' => $rate,
                    'points_value' => $pointsValue,
                    'amount' => $amount,
                    'is_company_fund' => true,
                ]);
            }

            // Route $companyFundTotal to system company wallet (implemented in Phase 7 API via config)

            $lockedPurchase->update([
                'team_income_distributed' => true,
                'team_income_distributed_at' => now(),
            ]);
        });
    }
}
