<?php

namespace Database\Factories;

use App\Models\Redirect;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Redirect>
 */
class RedirectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_path' => fake()->unique()->slug(3),
            'to_url' => '/tj/news/'.fake()->slug(),
            'status_code' => 301,
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function temporary(): static
    {
        return $this->state(fn (): array => ['status_code' => 302]);
    }
}
