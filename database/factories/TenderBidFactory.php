<?php

namespace Database\Factories;

use App\Enums\AppealStatus;
use App\Models\Tender;
use App\Models\TenderBid;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenderBid>
 */
class TenderBidFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => TenderBid::generateReference(),
            'tender_id' => Tender::factory(),
            'company_name' => fake()->company(),
            'contact_name' => fake()->name(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'proposal' => fake()->paragraph(),
            'status' => AppealStatus::New,
            'assigned_to' => null,
            'internal_note' => null,
        ];
    }
}
