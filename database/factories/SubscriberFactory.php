<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Enums\SubscriptionTopic;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscriber>
 */
class SubscriberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'token' => Subscriber::generateToken(),
            'locale' => 'tj',
            'status' => SubscriptionStatus::Pending,
            'topics' => [SubscriptionTopic::Alerts->value],
            'region_id' => null,
            'confirmed_at' => null,
            'consented_at' => now(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (): array => [
            'status' => SubscriptionStatus::Confirmed,
            'confirmed_at' => now(),
        ]);
    }
}
