<?php

namespace App\Events;

use App\Models\User;
use App\Models\ShopperUpgrade;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShopperUpgraded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public ShopperUpgrade $upgrade
    ) {}
}
