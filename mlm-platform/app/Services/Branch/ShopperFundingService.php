<?php

namespace App\Services\Branch;

use App\Models\Branch;
use App\Models\ShopperFunding;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Exception;
use Illuminate\Support\Facades\DB;

class ShopperFundingService
{
    public function __construct(protected WalletService $walletService) {}

    public function fundShopper(User $shopper, Branch $branch, User $branchAdmin, string $amount, string $idempotencyKey, ?string $notes = null): ShopperFunding
    {
        if (!$shopper->isShopper()) {
            throw new Exception("Recipient must be a shopper.");
        }

        if ($amount <= 0) {
            throw new Exception("Funding amount must be positive.");
        }

        return DB::transaction(function () use ($shopper, $branch, $branchAdmin, $amount, $idempotencyKey, $notes) {
            $existing = ShopperFunding::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }

            $incentiveRate = config('mlm.shopper_funding_incentive', 2.00);
            $incentiveAmount = bcmul($amount, bcdiv((string)$incentiveRate, '100', 8), 4);
            $totalCredited = bcadd($amount, $incentiveAmount, 4);

            // Debit branch wallet (for the full base amount)
            $debitTxn = $this->walletService->debit(
                wallet: $branch->wallet,
                amount: $amount,
                category: 'shopper_fund',
                referenceType: User::class,
                referenceId: $shopper->id,
                idempotencyKey: "{$idempotencyKey}:debit",
                description: "Funded shopper {$shopper->phone}"
            );

            // Credit shopper wallet (base + 2%)
            $creditTxn = $this->walletService->credit(
                wallet: $shopper->wallet,
                amount: $totalCredited,
                category: 'shopper_fund',
                referenceType: Branch::class,
                referenceId: $branch->id,
                idempotencyKey: "{$idempotencyKey}:credit",
                description: "Funded by branch {$branch->branch_name} with {$incentiveRate}% incentive"
            );

            return ShopperFunding::create([
                'shopper_id' => $shopper->id,
                'branch_id' => $branch->id,
                'funded_by' => $branchAdmin->id,
                'base_amount' => $amount,
                'incentive_rate' => $incentiveRate,
                'incentive_amount' => $incentiveAmount,
                'total_credited' => $totalCredited,
                'idempotency_key' => $idempotencyKey,
                'branch_debit_transaction_id' => $debitTxn->id,
                'shopper_credit_transaction_id' => $creditTxn->id,
                'notes' => $notes,
            ]);
        });
    }
}
