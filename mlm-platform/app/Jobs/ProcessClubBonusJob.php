<?php

namespace App\Jobs;

use App\Models\Club;
use App\Services\MLM\ClubBonusService;
use App\Events\ClubBonusPaid;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessClubBonusJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [10, 60, 300, 900, 3600];

    public function __construct(public Club $newClub) {}

    public function uniqueId(): string
    {
        return (string) $this->newClub->id;
    }

    public function handle(ClubBonusService $clubBonusService): void
    {
        $clubBonusService->process($this->newClub);
        
        event(new ClubBonusPaid($this->newClub));
    }
}
