<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'full_name' => fake()->name(),
            'phone' => '01' . fake()->numberBetween(3, 9) . fake()->numerify('########'),
            'password' => static::$password ??= Hash::make('password'),
            'referral_id' => strtoupper(Str::random(8)),
            'status' => 'active',
            'total_lifetime_earned' => '0.0000',
            'club_income_eligible' => false,
        ];
    }
}
