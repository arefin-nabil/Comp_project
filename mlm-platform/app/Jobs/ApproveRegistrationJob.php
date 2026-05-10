<?php

namespace App\Jobs;

use App\Models\RegistrationPayment;
use App\Services\Auth\RegistrationService;
use App\Events\UserRegistered;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ApproveRegistrationJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 60, 300];

    public function __construct(
        public array $userData,
        public RegistrationPayment $payment
    ) {}

    public function uniqueId(): string
    {
        return "registration:{$this->userData['phone']}";
    }

    public function handle(RegistrationService $registrationService): void
    {
        if ($this->payment->status !== 'verified') {
            Log::warning("Skipping ApproveRegistrationJob: payment {$this->payment->id} not verified.");
            return;
        }

        try {
            $user = $registrationService->completeRegistration($this->userData, $this->payment);
            
            // Dispatch domain event
            event(new UserRegistered($user, $this->payment));
        } catch (\Exception $e) {
            Log::error("ApproveRegistrationJob failed for {$this->userData['phone']}: " . $e->getMessage());
            throw $e; // Trigger retry
        }
    }
}
