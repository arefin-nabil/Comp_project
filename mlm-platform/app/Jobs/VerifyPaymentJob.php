<?php

namespace App\Jobs;

use App\Models\RegistrationPayment;
use App\Services\Payment\PaymentGatewayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerifyPaymentJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [10, 30, 60, 300, 900];

    public function __construct(
        public RegistrationPayment $payment,
        public array $userData // The registration data to process once verified
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->payment->id;
    }

    public function handle(PaymentGatewayService $gatewayService): void
    {
        if ($this->payment->status !== 'pending') {
            return;
        }

        try {
            $result = $gatewayService->verifyPayment($this->payment->method, $this->payment->gateway_transaction_id);

            if ($result['status'] === 'success') {
                $this->payment->update([
                    'gateway_response' => $result['gateway_response'],
                ]);
                
                // Dispatch approval job to the queue
                ApproveRegistrationJob::dispatch($this->userData, $this->payment);
            } else {
                $this->payment->update([
                    'status' => 'rejected',
                    'notes' => 'Gateway verification failed.',
                    'gateway_response' => $result['gateway_response'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Payment verification failed for {$this->payment->id}: " . $e->getMessage());
            $this->release($this->backoff[$this->attempts() - 1] ?? 900);
        }
    }
}
