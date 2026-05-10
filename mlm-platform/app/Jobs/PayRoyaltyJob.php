<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\MLM\RoyaltyService;
use App\Events\RoyaltyPaid;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PayRoyaltyJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [10, 60, 300, 900, 3600];

    public function __construct(public User $referrer) {}

    public function uniqueId(): string
    {
        return (string) $this->referrer->id;
    }

    public function handle(RoyaltyService $royaltyService): void
    {
        $royaltyService->process($this->referrer);
        
        event(new RoyaltyPaid($this->referrer));
    }
}
