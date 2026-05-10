<?php

namespace App\Services\Payment;

use App\Models\RegistrationPayment;
use App\Models\ShopperUpgrade;
use Exception;

class PaymentGatewayService
{
    /**
     * Stub for online payment gateway verification (bKash/Nagad/Rocket/Upay).
     */
    public function verifyPayment(string $method, string $transactionId): array
    {
        // In a real implementation, this would call the respective driver.
        // For Phase 1 scope, we'll simulate a successful verification.
        
        $supported = ['bkash', 'nagad', 'rocket', 'upay'];
        if (!in_array($method, $supported)) {
            throw new Exception("Unsupported payment method: {$method}");
        }

        return [
            'status' => 'success', // or 'failed'
            'amount' => '100.00', // Mock amount
            'gateway_response' => [
                'trxID' => $transactionId,
                'paymentID' => 'PAY' . uniqid(),
                'statusMessage' => 'Successful',
            ]
        ];
    }
}
