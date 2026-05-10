<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\MLM\ClubActivationService;
use App\Events\ClubActivated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ActivateClubJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [5, 15, 30]; // Short backoff for lock contention

    public function __construct(public User $user) {}

    public function uniqueId(): string
    {
        return (string) $this->user->id;
    }

    public function handle(ClubActivationService $clubActivationService): void
    {
        try {
            // Keep activating clubs until user doesn't have 100 points
            while (true) {
                $club = $clubActivationService->activate($this->user);
                
                if (!$club) {
                    break; // Not enough points
                }

                event(new ClubActivated($club, $this->user));
            }
        } catch (\Exception $e) {
            Log::warning("ActivateClubJob lock contention for User {$this->user->id}: " . $e->getMessage());
            $this->release(5); // Release back to queue to try again
        }
    }
}
