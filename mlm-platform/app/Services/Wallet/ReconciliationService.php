<?php

namespace App\Services\Wallet;

use App\Models\Wallet;
use App\Models\WalletSnapshot;
use App\Models\WalletTransaction;
use App\Services\Admin\AuditService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReconciliationService
{
    public function __construct(protected AuditService $auditService) {}

    /**
     * Run nightly to verify that SUM(credits) - SUM(debits) matches wallets.balance exactly.
     * Logs discrepancies for manual investigation.
     */
    public function reconcileAllWallets(): void
    {
        $wallets = Wallet::where('is_active', true)->get();

        foreach ($wallets as $wallet) {
            $this->reconcile($wallet);
        }
    }

    public function reconcile(Wallet $wallet): WalletSnapshot
    {
        // Don't lock the whole table, just get the current state
        $transactions = WalletTransaction::where('wallet_id', $wallet->id)->get();
        
        $credits = '0.0000';
        $debits = '0.0000';
        $lastTxnId = null;

        foreach ($transactions as $txn) {
            if ($txn->type === 'credit') {
                $credits = bcadd($credits, $txn->amount, 4);
            } else {
                $debits = bcadd($debits, $txn->amount, 4);
            }
            if ($lastTxnId === null || $txn->id > $lastTxnId) {
                $lastTxnId = $txn->id;
            }
        }

        $computedBalance = bcsub($credits, $debits, 4);
        $snapshotBalance = $wallet->balance;

        $isMatch = bccomp($computedBalance, $snapshotBalance, 4) === 0;
        $notes = null;

        if (!$isMatch) {
            $notes = "Mismatch: Computed {$computedBalance} vs Snapshot {$snapshotBalance}";
            Log::critical("LEDGER MISMATCH DETECTED Wallet ID {$wallet->id}: {$notes}");
            
            $this->auditService->log(
                action: 'ledger_mismatch_detected',
                target: $wallet,
                oldValue: ['computed' => $computedBalance],
                newValue: ['snapshot' => $snapshotBalance]
            );
        }

        return WalletSnapshot::create([
            'wallet_id' => $wallet->id,
            'computed_balance' => $computedBalance,
            'snapshot_balance' => $snapshotBalance,
            'is_match' => $isMatch,
            'discrepancy_notes' => $notes,
            'last_transaction_id' => $lastTxnId,
        ]);
    }
}
