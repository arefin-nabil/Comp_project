<?php

namespace App\Jobs;

use App\Services\MLM\ClubIncomeService;
use App\Events\ClubIncomeSettled;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SettleClubIncomeJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [300, 900]; // Longer backoff for big batch jobs

    public function __construct(public string $dateString) {}

    public function uniqueId(): string
    {
        return $this->dateString;
    }

    public function handle(ClubIncomeService $clubIncomeService): void
    {
        $date = Carbon::parse($this->dateString);
        $batch = $clubIncomeService->settleNightlyBatch($date);
        
        event(new ClubIncomeSettled($batch));
    }
}
