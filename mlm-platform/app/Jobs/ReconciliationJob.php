<?php

namespace App\Jobs;

use App\Services\Wallet\ReconciliationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReconciliationJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1; // Run once per day, don't retry wildly
    public $timeout = 3600; // 1 hour max

    public function __construct(public string $dateString) {}

    public function uniqueId(): string
    {
        return $this->dateString;
    }

    public function handle(ReconciliationService $reconciliationService): void
    {
        $reconciliationService->reconcileAllWallets();
    }
}
