<?php

namespace App\Events;

use App\Models\Club;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClubActivated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Club $club,
        public User $user
    ) {}
}
