<?php

namespace App\Listeners;

use App\Events\ClubActivated;
use App\Jobs\ProcessClubBonusJob;

class CheckClubBonus
{
    public function handle(ClubActivated $event): void
    {
        // Whenever a new club is activated, we check for bonuses.
        ProcessClubBonusJob::dispatch($event->club);
    }
}
