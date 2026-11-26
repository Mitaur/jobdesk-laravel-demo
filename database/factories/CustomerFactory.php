<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Test data only (uses Faker, which is a dev dependency).
 *
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'phone' => '(555) 010-'.fake()->numerify('####'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->streetAddress().', '.fake()->city(),
        ];
    }
}
