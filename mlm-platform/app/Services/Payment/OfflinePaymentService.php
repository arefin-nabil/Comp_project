<?php

namespace App\Services\Payment;

use App\Models\RegistrationPayment;
use App\Models\ShopperUpgrade;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class OfflinePaymentService
{
    /**
     * Manually verify an offline payment for registration.
     */
    public function verifyRegistration(RegistrationPayment $payment, User $admin, string $receiptNumber): RegistrationPayment
    {
        if ($payment->status !== 'pending' || $payment->method !== 'offline') {
            throw new Exception("Only pending offline payments can be manually verified.");
        }

        $payment->update([
            'status' => 'verified',
            'receipt_number' => $receiptNumber,
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);

        return $payment;
    }

    /**
     * Manually verify an offline payment for shopper upgrade (typically done by branch admin).
     */
    public function verifyShopperUpgrade(ShopperUpgrade $upgrade, User $branchAdmin, string $receiptNumber): ShopperUpgrade
    {
        if ($upgrade->status !== 'pending' || $upgrade->payment_method !== 'offline') {
            throw new Exception("Only pending offline upgrades can be manually verified.");
        }

        $upgrade->update([
            'status' => 'approved',
            'receipt_number' => $receiptNumber,
            'approved_by' => $branchAdmin->id,
            'approved_at' => now(),
        ]);

        return $upgrade;
    }
}
