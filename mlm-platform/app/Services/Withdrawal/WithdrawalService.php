<?php

namespace App\Services\Withdrawal;

use App\Models\User;
use App\Models\Withdrawal;
use App\Services\Wallet\WalletService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WithdrawalService
{
    public function __construct(protected WalletService $walletService) {}

    public function requestWithdrawal(User $user, array $data): Withdrawal
    {
        $requestedAmount = $data['amount'];
        $min = config('mlm.withdrawal_min', 55.00);

        if ($requestedAmount < $min) {
            throw new Exception("Minimum withdrawal amount is {$min} BDT.");
        }

        $wallet = $user->wallet()->lockForUpdate()->first();
        if (bccomp($wallet->balance, $requestedAmount, 4) === -1) {
            throw new Exception("Insufficient balance.");
        }

        return DB::transaction(function () use ($user, $data, $requestedAmount) {
            $type = $data['type']; // 'online' or 'branch'
            
            $vatPercent = $type === 'online' ? config('mlm.withdrawal_vat_online', 5.00) : config('mlm.withdrawal_vat_branch', 7.00);
            $vatAmount = bcmul($requestedAmount, bcdiv((string)$vatPercent, '100', 8), 4);
            $payableAmount = bcsub($requestedAmount, $vatAmount, 4);

            $branchSplit = '0.0000';
            $companySplit = $vatAmount;

            if ($type === 'branch') {
                $branchSplitPercent = config('mlm.branch_withdrawal_split', 3.00);
                $branchSplit = bcmul($requestedAmount, bcdiv((string)$branchSplitPercent, '100', 8), 4);
                $companySplit = bcsub($vatAmount, $branchSplit, 4);
            }

            return Withdrawal::create([
                'user_id' => $user->id,
                'type' => $type,
                'branch_id' => $data['branch_id'] ?? null,
                'requested_amount' => $requestedAmount,
                'vat_amount' => $vatAmount,
                'payable_amount' => $payableAmount,
                'branch_split_amount' => $branchSplit,
                'company_split_amount' => $companySplit,
                'gateway' => $data['gateway'] ?? null,
                'account_number' => $data['account_number'] ?? null,
                'status' => 'pending',
                'otp_verified' => false,
                'idempotency_key' => $data['idempotency_key'] ?? Str::uuid(),
            ]);
        });
    }

    public function approveAndPay(Withdrawal $withdrawal, User $approver, ?string $gatewayTxnId = null, ?string $notes = null): void
    {
        if ($withdrawal->status !== 'pending' || !$withdrawal->otp_verified) {
            throw new Exception("Withdrawal must be pending and OTP verified to approve.");
        }

        DB::transaction(function () use ($withdrawal, $approver, $gatewayTxnId, $notes) {
            $lockedWithdrawal = Withdrawal::where('id', $withdrawal->id)->lockForUpdate()->first();
            if ($lockedWithdrawal->status !== 'pending') return;

            $user = $lockedWithdrawal->user;

            // Debit user wallet
            $txn = $this->walletService->debit(
                wallet: $user->wallet,
                amount: $lockedWithdrawal->requested_amount,
                category: 'withdrawal',
                referenceType: Withdrawal::class,
                referenceId: $lockedWithdrawal->id,
                idempotencyKey: "withdrawal_approve_{$lockedWithdrawal->id}",
                description: "Withdrawal ({$lockedWithdrawal->type})"
            );

            // If branch type, credit the branch its 3% split
            if ($lockedWithdrawal->type === 'branch' && $lockedWithdrawal->branch_id) {
                $branch = $lockedWithdrawal->branch;
                if ($branch && $branch->wallet) {
                    $this->walletService->credit(
                        wallet: $branch->wallet,
                        amount: $lockedWithdrawal->branch_split_amount,
                        category: 'withdrawal', // or withdrawal_fee_share
                        referenceType: Withdrawal::class,
                        referenceId: $lockedWithdrawal->id,
                        idempotencyKey: "withdrawal_branch_split_{$lockedWithdrawal->id}",
                        description: "3% share from withdrawal {$lockedWithdrawal->ulid}"
                    );
                }
            }

            // Company split implicitly retained (since user is debited 100%, branch credited 3%, remaining 97% leaves system or is distributed).
            // Actually, in accounting:
            // User Wallet -100
            // Branch Wallet +3
            // Company Wallet +4
            // Bank/Gateway Outflow -93

            $lockedWithdrawal->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'wallet_transaction_id' => $txn->id,
                'gateway_transaction_id' => $gatewayTxnId,
                'notes' => $notes,
            ]);
        });
    }
}
