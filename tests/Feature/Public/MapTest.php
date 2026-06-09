<?php

use App\Models\Incident;
use Database\Seeders\LanguageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(LanguageSeeder::class));

it('renders the map with active incidents that have coordinates', function () {
    $withCoords = Incident::factory()->create(['latitude' => 38.5, 'longitude' => 68.7]);
    $withCoords->upsertTranslations(['tj' => ['title' => 'Заминларза']]);

    // Excluded: no coordinates.
    Incident::factory()->create(['latitude' => null, 'longitude' => null]);

    // Excluded: resolved (not active).
    Incident::factory()->resolved()->create(['latitude' => 39.0, 'longitude' => 69.0]);

    $this->get(route('map.index', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/map')
            ->has('incidents', 1)
            ->where('incidents.0.title', 'Заминларза')
            ->where('incidents.0.lat', 38.5)
        );
});
