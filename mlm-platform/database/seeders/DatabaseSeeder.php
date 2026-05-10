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
        $roles = [
            'superadmin',
            'admin_general',
            'admin_finance',
            'admin_ecommerce',
            'branch_admin',
            'shopper',
            'customer'
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // 2. Create Root Superadmin
        $admin = User::create([
            'full_name' => 'System Super Admin',
            'phone' => '01000000000',
            'password' => Hash::make('admin1234'),
            'referral_id' => 'ROOT1234',
            'status' => 'active',
        ]);
        $admin->assignRole('superadmin');
        
        Wallet::create([
            'owner_type' => User::class,
            'owner_id' => $admin->id,
            'balance' => '0.0000',
        ]);
        
        Referral::create([
            'user_id' => $admin->id,
            'referrer_id' => null,
        ]);

        // 3. Create Company System Account (for Company Funds)
        $company = User::create([
            'full_name' => 'Company System Wallet',
            'phone' => '01000000001',
            'password' => Hash::make('company_system_wallet_secure_password_do_not_login'),
            'referral_id' => 'COMPANYFUND',
            'status' => 'active',
        ]);
        
        // Don't give it any roles, it's just a placeholder for the company fund.
        
        Wallet::create([
            'owner_type' => User::class,
            'owner_id' => $company->id,
            'balance' => '0.0000',
        ]);

        Referral::create([
            'user_id' => $company->id,
            'referrer_id' => null,
        ]);

        $this->command->info('Super Admin User Created: 01000000000 / admin1234');
        $this->command->info('Admin Referral ID: ROOT1234');
        $this->command->info('Company Fund Account Created with ID: ' . $company->id);
    }
}
