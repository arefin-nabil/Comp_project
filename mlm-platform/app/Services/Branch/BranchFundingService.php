<?php

namespace App\Services\Branch;

use App\Models\Branch;
use App\Models\BranchFunding;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallet\WalletService;
use Exception;
use Illuminate\Support\Facades\DB;

class BranchFundingService
{
    public function __construct(protected WalletService $walletService) {}

    public function fundBranch(Branch $branch, User $financeAdmin, string $amount, string $idempotencyKey, ?string $notes = null): BranchFunding
    {
        if ($amount <= 0) {
            throw new Exception("Funding amount must be positive.");
        }

        return DB::transaction(function () use ($branch, $financeAdmin, $amount, $idempotencyKey, $notes) {
            $existing = BranchFunding::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }

            $incentiveRate = config('mlm.branch_funding_incentive', 3.00);
            $incentiveAmount = bcmul($amount, bcdiv((string)$incentiveRate, '100', 8), 4);
            $totalCredited = bcadd($amount, $incentiveAmount, 4);

            // Note: Company wallet is typically a virtual wallet or specific system user ID (e.g. user ID 1).
            // For now, we assume the finance admin's wallet holds the company fund, or we just credit the branch directly
            // from the ether (since fiat enters the system). Let's credit the branch wallet directly.
            
            $branchWallet = $branch->wallet;
            if (!$branchWallet) {
                $branchWallet = Wallet::create([
                    'owner_type' => Branch::class,
                    'owner_id' => $branch->id,
                ]);
            }

            $txn = $this->walletService->credit(
                wallet: $branchWallet,
                amount: $totalCredited,
                category: 'branch_fund',
                referenceType: Branch::class,
                referenceId: $branch->id,
                idempotencyKey: $idempotencyKey,
                description: "Company funding with {$incentiveRate}% incentive"
            );

            return BranchFunding::create([
                'branch_id' => $branch->id,
                'funded_by' => $financeAdmin->id,
                'base_amount' => $amount,
                'incentive_rate' => $incentiveRate,
                'incentive_amount' => $incentiveAmount,
                'total_credited' => $totalCredited,
                'idempotency_key' => $idempotencyKey,
                'credit_transaction_id' => $txn->id,
                'notes' => $notes,
            ]);
        });
    }
}
