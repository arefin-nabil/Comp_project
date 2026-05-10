<?php

namespace App\Jobs;

use App\Models\ShopperUpgrade;
use App\Events\ShopperUpgraded;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Services\Wallet\WalletService;
use Exception;

class ApproveShopperUpgradeJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 60, 300];

    public function __construct(public ShopperUpgrade $upgrade) {}

    public function uniqueId(): string
    {
        return (string) $this->upgrade->id;
    }

    public function handle(WalletService $walletService): void
    {
        if ($this->upgrade->status !== 'approved' || $this->upgrade->split_processed) {
            return;
        }

        DB::transaction(function () use ($walletService) {
            $lockedUpgrade = ShopperUpgrade::where('id', $this->upgrade->id)->lockForUpdate()->first();
            if ($lockedUpgrade->split_processed) return;

            $user = $lockedUpgrade->user;
            $referrer = $user->referrer;

            // 1. Assign Shopper Role
            if (!$user->hasRole('shopper')) {
                $user->assignRole('shopper');
            }

            // 2. Process Splits
            // $30 to referrer
            if ($referrer) {
                $walletService->credit(
                    wallet: $referrer->wallet,
                    amount: $lockedUpgrade->referrer_amount,
                    category: 'shopper_upgrade',
                    referenceType: ShopperUpgrade::class,
                    referenceId: $lockedUpgrade->id,
                    idempotencyKey: "upgrade_{$lockedUpgrade->id}_referrer",
                    description: "Shopper upgrade bonus for referring {$user->phone}"
                );
            }

            // $30 to own wallet
            $walletService->credit(
                wallet: $user->wallet,
                amount: $lockedUpgrade->own_wallet_amount,
                category: 'shopper_upgrade',
                referenceType: ShopperUpgrade::class,
                referenceId: $lockedUpgrade->id,
                idempotencyKey: "upgrade_{$lockedUpgrade->id}_own",
                description: "Shopper upgrade self bonus"
            );

            // Remaining $40 and $200 (onboarding allocation) goes to company wallets (skipped here, tracked in DB)

            $lockedUpgrade->update(['split_processed' => true]);

            event(new ShopperUpgraded($user, $lockedUpgrade));
        });
    }
}
