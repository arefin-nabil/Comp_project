<?php

namespace App\Listeners;

use App\Events\ClubActivated;
use App\Jobs\PayRoyaltyJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class CheckRoyalty implements ShouldQueue
{
    public function handle(ClubActivated $event): void
    {
        $user = $event->user;
        $referrer = $user->referrer;

        if ($referrer) {
            // Re-evaluating royalty carry logic for the referrer
            PayRoyaltyJob::dispatch($referrer);
        }
    }
}
