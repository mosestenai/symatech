<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        //auto create super admin account
        $password = Hash::make(env('SUPERADMINPASS'));
        return [
            'email' => env('SUPERADMINEMAIL'),
            'username' => env('SUPERADMINUSERNAME'),
            'phone' => env('SUPERADMINPHONE'),
            'type' => 'Superadmin',
            'datejoined' => now(),
            'email_verified_at' => now(),
            'password' => $password, // password
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
