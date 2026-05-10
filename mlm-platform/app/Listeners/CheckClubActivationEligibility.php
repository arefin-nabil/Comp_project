<?php

namespace App\Listeners;

use App\Jobs\ActivateClubJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class CheckClubActivationEligibility implements ShouldQueue
{
    /**
     * Handle events that might result in points being added.
     * $event can be UserRegistered, TeamIncomeDistributed, etc.
     */
    public function handle($event): void
    {
        $user = null;
        
        if (property_exists($event, 'user')) {
            $user = $event->user;
        } elseif (property_exists($event, 'purchase')) {
            $user = $event->purchase->customer;
        }

        if ($user) {
            $activationPoints = config('mlm.club_activation_points', 100.00);
            $pointsBalance = $user->wallet->points_balance;
            
            if (bccomp($pointsBalance, $activationPoints, 4) >= 0) {
                ActivateClubJob::dispatch($user);
            }
        }
    }
}
