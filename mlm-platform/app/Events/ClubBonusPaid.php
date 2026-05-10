<?php

namespace App\Events;

use App\Models\Club;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClubBonusPaid
{
    use Dispatchable, SerializesModels;

    public function __construct(public Club $club) {}
}
