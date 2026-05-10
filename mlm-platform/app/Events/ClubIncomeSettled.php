<?php

namespace App\Events;

use App\Models\ClubIncomeBatch;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClubIncomeSettled
{
    use Dispatchable, SerializesModels;

    public function __construct(public ClubIncomeBatch $batch) {}
}
