<?php

namespace App\Services\Wallet;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Wallet\IdempotencyService;
use Exception;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function __construct(
        protected IdempotencyService $idempotencyService
    ) {}

    /**
     * Credit a wallet and record ledger transaction.
     * MUST be called within a DB::transaction() at the caller level,
     * or it will create its own.
     *
     * @param Wallet $wallet
     * @param string $amount
     * @param string $category
     * @param string $referenceType
     * @param int $referenceId
     * @param string $idempotencyKey
     * @param string $description
     * @param array $metadata
     * @return WalletTransaction
     * @throws Exception
     */
    public function credit(
        Wallet $wallet,
        string $amount,
        string $category,
        string $referenceType,
        int $referenceId,
        string $idempotencyKey,
        string $description = '',
        array $metadata = []
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new Exception("Credit amount must be greater than zero.");
        }

        return DB::transaction(function () use ($wallet, $amount, $category, $referenceType, $referenceId, $idempotencyKey, $description, $metadata) {
            // Check Idempotency First
            $existing = WalletTransaction::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }

            // Lock wallet row for update
            $lockedWallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
            
            $balanceBefore = $lockedWallet->balance;
            $balanceAfter = bcadd($balanceBefore, $amount, 4);

            // Insert Ledger
            $transaction = WalletTransaction::create([
                'wallet_id' => $lockedWallet->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'category' => $category,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'idempotency_key' => $idempotencyKey,
                'metadata' => $metadata,
            ]);

            // Update Projected Balance
            $lockedWallet->balance = $balanceAfter;
            $lockedWallet->save();

            return $transaction;
        });
    }

    /**
     * Debit a wallet and record ledger transaction.
     * MUST be called within a DB::transaction() at the caller level.
     *
     * @param Wallet $wallet
     * @param string $amount
     * @param string $category
     * @param string $referenceType
     * @param int $referenceId
     * @param string $idempotencyKey
     * @param string $description
     * @param array $metadata
     * @return WalletTransaction
     * @throws Exception
     */
    public function debit(
        Wallet $wallet,
        string $amount,
        string $category,
        string $referenceType,
        int $referenceId,
        string $idempotencyKey,
        string $description = '',
        array $metadata = []
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new Exception("Debit amount must be greater than zero.");
        }

        return DB::transaction(function () use ($wallet, $amount, $category, $referenceType, $referenceId, $idempotencyKey, $description, $metadata) {
            // Check Idempotency First
            $existing = WalletTransaction::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }

            // Lock wallet row for update
            $lockedWallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
            
            $balanceBefore = $lockedWallet->balance;
            
            if (bccomp($balanceBefore, $amount, 4) === -1) {
                throw new Exception("Insufficient balance.");
            }

            $balanceAfter = bcsub($balanceBefore, $amount, 4);

            // Insert Ledger
            $transaction = WalletTransaction::create([
                'wallet_id' => $lockedWallet->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'category' => $category,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'idempotency_key' => $idempotencyKey,
                'metadata' => $metadata,
            ]);

            // Update Projected Balance
            $lockedWallet->balance = $balanceAfter;
            $lockedWallet->save();

            return $transaction;
        });
    }
}
