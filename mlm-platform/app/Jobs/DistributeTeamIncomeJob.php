<?php

namespace App\Jobs;

use App\Models\Purchase;
use App\Services\MLM\TeamIncomeService;
use App\Events\TeamIncomeDistributed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DistributeTeamIncomeJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [10, 60, 300, 900, 3600];

    public function __construct(public Purchase $purchase) {}

    public function uniqueId(): string
    {
        return (string) $this->purchase->id;
    }

    public function handle(TeamIncomeService $teamIncomeService): void
    {
        $teamIncomeService->distribute($this->purchase);
        
        event(new TeamIncomeDistributed($this->purchase));
    }
}
