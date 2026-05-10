<?php

namespace App\Jobs;

use App\Services\Auth\OtpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOtpJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60]; // Exponential backoff in seconds

    public function __construct(
        public string $phone,
        public string $action
    ) {}

    public function uniqueId(): string
    {
        return "{$this->action}:{$this->phone}";
    }

    public function handle(OtpService $otpService): void
    {
        $otpService->sendOtp($this->phone, $this->action);
    }
}
