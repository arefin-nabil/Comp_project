<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Referral;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Roles
        $adminRole = Role::create(['name' => 'admin']);
        $branchRole = Role::create(['name' => 'branch_manager']);
        $shopperRole = Role::create(['name' => 'shopper']);
        $customerRole = Role::create(['name' => 'customer']);

        // 2. Create Root Admin
        $admin = User::create([
            'full_name' => 'System Admin',
            'phone' => '01000000000',
            'password' => Hash::make('admin1234'),
            'referral_id' => 'ROOT1234',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');
        
        Wallet::create([
            'owner_type' => User::class,
            'owner_id' => $admin->id,
            'balance' => '0.0000',
        ]);
        
        Referral::create([
            'user_id' => $admin->id,
            'referrer_id' => null,
        ]);

        $this->command->info('Admin User Created: 01000000000 / admin1234');
        $this->command->info('Admin Referral ID: ROOT1234');
    }
}
