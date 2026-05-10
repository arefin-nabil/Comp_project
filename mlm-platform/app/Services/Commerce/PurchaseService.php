<?php

namespace App\Services\Commerce;

use App\Models\Purchase;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseService
{
    public function __construct(protected WalletService $walletService) {}

    /**
     * Complete a purchase transfer from Shopper to Customer.
     * Calculates cashback and points, and distributes them safely.
     */
    public function completePurchase(User $shopper, User $customer, string $transferAmount, string $idempotencyKey): Purchase
    {
        if (!$shopper->isShopper()) {
            throw new Exception("Only shoppers can initiate purchases.");
        }

        if ($transferAmount <= 0) {
            throw new Exception("Transfer amount must be positive.");
        }

        return DB::transaction(function () use ($shopper, $customer, $transferAmount, $idempotencyKey) {
            // Check idempotency first
            $existing = Purchase::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }

            $cashbackRate = '40.0000';
            $pointsRate = '60.0000';
            $pointToBdt = config('mlm.point_to_bdt_rate', '6.0000');

            $cashbackAmount = bcmul($transferAmount, bcdiv($cashbackRate, '100', 8), 4);
            $pointsValue = bcmul($transferAmount, bcdiv($pointsRate, '100', 8), 4);
            $pointsAwarded = bcdiv($pointsValue, $pointToBdt, 4);
            
            $teamIncomePool = bcmul($pointsValue, '0.35', 4);


            $purchase = Purchase::create([
                'shopper_id' => $shopper->id,
                'customer_id' => $customer->id,
                'transfer_amount' => $transferAmount,
                'cashback_rate' => $cashbackRate,
                'cashback_amount' => $cashbackAmount,
                'points_rate' => $pointsRate,
                'points_value' => $pointsValue,
                'points_awarded' => $pointsAwarded,
                'team_income_pool' => $teamIncomePool,
                'team_income_distributed' => false,
                'idempotency_key' => $idempotencyKey,
            ]);


            // Debit Shopper Wallet (Base Amount)
            $this->walletService->debit(
                wallet: $shopper->wallet,
                amount: $transferAmount,
                category: 'purchase',
                referenceType: Purchase::class,
                referenceId: $purchase->id,
                idempotencyKey: "{$idempotencyKey}:debit",
                description: "Purchase transfer to Customer {$customer->phone}"
            );


            // Credit Customer Wallet (Cashback)
            $this->walletService->credit(
                wallet: $customer->wallet,
                amount: $cashbackAmount,
                category: 'cashback',
                referenceType: Purchase::class,
                referenceId: $purchase->id,
                idempotencyKey: "{$idempotencyKey}:cashback",
                description: "Cashback from Purchase {$purchase->ulid}"
            );

            // Credit Customer Points Wallet
            $customerWallet = $customer->wallet()->lockForUpdate()->first();
            $customerWallet->points_balance = bcadd($customerWallet->points_balance, $pointsAwarded, 4);
            $customerWallet->save();

            // Note: Team income distribution is handled via Domain Events & Jobs asynchronously

            return $purchase;
        });
    }
}
