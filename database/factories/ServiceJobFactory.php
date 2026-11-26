<?php

namespace Database\Factories;

use App\Enums\JobStatus;
use App\Models\Customer;
use App\Models\ServiceJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Test data only (uses Faker, which is a dev dependency).
 *
 * @extends Factory<ServiceJob>
 */
class ServiceJobFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'title' => fake()->randomElement(['Fix leaking faucet', 'AC tune-up', 'Replace water heater', 'Unclog drain', 'Furnace repair']).' #'.fake()->unique()->numberBetween(1, 99999),
            'status' => JobStatus::Quote,
            'price' => fake()->randomFloat(2, 50, 2000),
            'scheduled_at' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function scheduled(?\DateTimeInterface $at = null): static
    {
        return $this->state(fn () => [
            'status' => JobStatus::Scheduled,
            'scheduled_at' => $at ?? now()->addDays(2)->setTime(10, 0),
        ]);
    }

    public function status(JobStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
