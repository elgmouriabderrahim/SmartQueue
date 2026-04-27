<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<\App\Models\User>
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
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'identity_number' => fake()->optional()->numerify('########'),
            'role' => 'citizen',
            'institution_id' => null,
            'department_id' => null,
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'remember_token' => fake()->regexify('[A-Za-z0-9]{10}'),
        ];
    }
}
