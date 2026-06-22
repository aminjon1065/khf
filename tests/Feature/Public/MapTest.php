<?php

use App\Enums\HazardLevel;
use App\Enums\IncidentType;
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

it('filters map incidents by type and level', function () {
    $incident1 = Incident::factory()->create(['latitude' => 38.5, 'longitude' => 68.7, 'type' => IncidentType::Earthquake, 'hazard_level' => HazardLevel::Elevated]);
    $incident1->upsertTranslations(['tj' => ['title' => 'EQ']]);

    $incident2 = Incident::factory()->create(['latitude' => 38.5, 'longitude' => 68.7, 'type' => IncidentType::Flood, 'hazard_level' => HazardLevel::Critical]);
    $incident2->upsertTranslations(['tj' => ['title' => 'FL']]);

    // Filter by type
    $this->get(route('map.index', ['locale' => 'tj', 'type' => 'earthquake']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('incidents', 1)
            ->where('incidents.0.title', 'EQ')
        );

    // Filter by hazard level
    $this->get(route('map.index', ['locale' => 'tj', 'level' => 'critical']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('incidents', 1)
            ->where('incidents.0.title', 'FL')
        );
});
