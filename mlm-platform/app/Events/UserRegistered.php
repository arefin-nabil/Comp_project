<?php

namespace App\Events;

use App\Models\User;
use App\Models\RegistrationPayment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public RegistrationPayment $payment
    ) {}
}
