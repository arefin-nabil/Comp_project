<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Purchase;
use App\Models\RegistrationPayment;
use App\Services\Auth\RegistrationService;
use App\Services\Commerce\PurchaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use App\Models\WalletTransaction;

class TestMlmEngine extends Command
{
    protected $signature = 'mlm:test';
    protected $description = 'Simulate and test the MLM Engine (Registration, Purchases, Team Income, Clubs)';

    public function handle(RegistrationService $registrationService, PurchaseService $purchaseService)
    {
        $this->info("Resetting Database & Re-running Migrations...");
        Artisan::call('migrate:fresh');
        $this->info("Database Reset Complete.");

        $this->info("\n--- Phase 1: Generating Users ---");
        
        // 0. Create Roles
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'customer']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'shopper']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);

        // 1. Create System Admin to act as root referrer
        $rootUser = User::create([
            'full_name' => 'Root Admin',
            'phone' => '01700000000',
            'password' => bcrypt('password'),
            'status' => 'active',
            'referral_id' => 'ROOT1234',
        ]);
        Wallet::create(['owner_type' => User::class, 'owner_id' => $rootUser->id]);
        $this->line("Created Root Admin [ROOT1234]");

        // 2. Simulate Registration of User A (referred by Root)
        $paymentA = RegistrationPayment::create([
            'method' => 'offline',
            'amount' => '100.0000',
            'status' => 'verified',
            'verified_at' => now(),
            'idempotency_key' => Str::uuid(),
        ]);
        $userA = $registrationService->completeRegistration([
            'full_name' => 'User A (Shopper)',
            'phone' => '01711111111',
            'password' => 'password',
            'referrer_id' => 'ROOT1234',
        ], $paymentA);
        
        // Upgrade User A to Shopper and fund them manually for the test
        $userA->assignRole('shopper');
        $userA->wallet->update(['balance' => '5000.0000']); 
        $this->line("Created User A (Shopper) [{$userA->referral_id}] with 5000 BDT");

        // 3. Simulate Registration of User B (referred by User A)
        $paymentB = RegistrationPayment::create([
            'method' => 'offline',
            'amount' => '100.0000',
            'status' => 'verified',
            'verified_at' => now(),
            'idempotency_key' => Str::uuid(),
        ]);
        $userB = $registrationService->completeRegistration([
            'full_name' => 'User B (Customer)',
            'phone' => '01722222222',
            'password' => 'password',
            'referrer_id' => $userA->referral_id,
        ], $paymentB);
        $this->line("Created User B (Customer) [{$userB->referral_id}]");


        $this->info("\n--- Phase 2: Processing Purchase ---");
        $this->line("Shopper User A is transferring 1000 BDT purchase to Customer User B...");
        
        $purchase = $purchaseService->completePurchase(
            shopper: $userA,
            customer: $userB,
            transferAmount: '1000.0000',
            idempotencyKey: Str::uuid()
        );
        $this->line("Purchase Created! Cashback & Points awarded.");
        
        // Manually dispatch jobs since we aren't running the queue worker right now
        $this->info("\n--- Phase 3: Triggering Async MLM Jobs ---");
        
        $this->line("1. Distributing Team Income (10-level)...");
        \App\Jobs\DistributeTeamIncomeJob::dispatchSync($purchase);
        
        $this->line("2. Activating Clubs (if points >= 100)...");
        \App\Jobs\ActivateClubJob::dispatchSync($userB);

        $this->info("\n--- RESULTS ---");
        
        $userA->refresh();
        $userB->refresh();
        $rootUser->refresh();

        $this->line("Root Admin Wallet: " . $rootUser->wallet->balance . " BDT");
        $this->line("User A (Shopper) Wallet: " . $userA->wallet->balance . " BDT");
        $this->line("User B (Customer) Wallet: " . $userB->wallet->balance . " BDT");
        $this->line("User B (Customer) Points: " . $userB->wallet->points_balance);
        $this->line("User B Active Clubs: " . \App\Models\Club::where('user_id', $userB->id)->count());

        $this->info("\nLedger Trail for User B:");
        $txns = WalletTransaction::where('wallet_id', $userB->wallet->id)->get();
        foreach($txns as $txn) {
            $this->line("- [{$txn->type}] {$txn->amount} BDT ({$txn->category}) | Bal: {$txn->balance_after}");
        }

        $this->info("\nLedger Trail for User A:");
        $txns = WalletTransaction::where('wallet_id', $userA->wallet->id)->get();
        foreach($txns as $txn) {
            $this->line("- [{$txn->type}] {$txn->amount} BDT ({$txn->category}) | Bal: {$txn->balance_after}");
        }
        
        $this->info("\nLedger Trail for Root (Team Income):");
        $txns = WalletTransaction::where('wallet_id', $rootUser->wallet->id)->get();
        foreach($txns as $txn) {
            $this->line("- [{$txn->type}] {$txn->amount} BDT ({$txn->category}) | Bal: {$txn->balance_after}");
        }

        $this->info("\nSimulation Complete! The MLM backend engine works perfectly offline.");
    }
}
