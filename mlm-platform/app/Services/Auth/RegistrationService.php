<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\Referral;
use App\Models\Wallet;
use App\Models\RegistrationPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Exception;

class RegistrationService
{
    /**
     * Reserve phone to prevent race conditions during registration form filling.
     */
    public function reservePhone(string $phone): void
    {
        $lockKey = "phone_reservation:{$phone}";
        if (!Cache::add($lockKey, true, now()->addMinutes(15))) {
            throw new Exception("This phone number is currently being registered by someone else.");
        }
        if (User::where('phone', $phone)->exists()) {
            Cache::forget($lockKey);
            throw new Exception("This phone number is already registered.");
        }
    }

    /**
     * Complete the registration process (called after payment verification or manual approval).
     */
    public function completeRegistration(array $data, RegistrationPayment $payment): User
    {
        return DB::transaction(function () use ($data, $payment) {
            $referrer = User::where('referral_id', $data['referrer_id'])->firstOrFail();

            $user = User::create([
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'referred_by' => $referrer->id,
                'status' => 'active',
            ]);

            // Assign Customer Role (Using Spatie Permission)
            $user->assignRole('customer');

            // Create Wallet
            Wallet::create([
                'owner_type' => User::class,
                'owner_id' => $user->id,
            ]);

            // Build Referral Chain Immutable Snapshot
            $chain = $this->buildReferralChain($referrer);
            
            Referral::create(array_merge([
                'user_id' => $user->id,
                'referrer_id' => $referrer->id,
            ], $chain));

            // Link Payment
            $payment->update([
                'user_id' => $user->id,
                'status' => 'verified',
                'verified_at' => now(),
            ]);

            // Release lock
            Cache::forget("phone_reservation:{$data['phone']}");

            return $user;
        });
    }

    private function buildReferralChain(User $referrer): array
    {
        $chain = [];
        $current = $referrer;
        
        for ($i = 2; $i <= 10; $i++) {
            if (!$current->referred_by) {
                break;
            }
            $current = User::find($current->referred_by);
            if ($current) {
                $chain["level{$i}_id"] = $current->id;
            } else {
                break;
            }
        }

        return $chain;
    }
}
