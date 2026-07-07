<?php

use App\Enums\HazardLevel;
use App\Enums\IncidentType;
use App\Models\Incident;
use App\Models\Region;
use App\Services\Public\MapDataService;
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
            ->where('incidents.0.type_key', $withCoords->type->value)
            // The map popup card renders these (localised server-side); guard the payload
            // contract the client popup depends on so a title-only regression is caught.
            ->has('incidents.0.type')
            ->has('incidents.0.level')
            ->has('incidents.0.status')
            ->has('incidents.0.occurred_at')
            ->has('units')
            ->has('riskZones.features')
            ->has('incidentTypes', count(IncidentType::cases()))
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

it('includes regional kchs units from oblast centres', function () {
    $region = Region::factory()->create([
        'latitude' => 38.56,
        'longitude' => 68.77,
        'parent_id' => null,
    ]);
    $region->upsertTranslations(['ru' => ['name' => 'Душанбе']]);

    $units = app(MapDataService::class)->regionalUnits('ru');

    expect($units)->toHaveCount(1)
        ->and($units[0]['lat'])->toBe(38.56)
        ->and($units[0]['lng'])->toBe(68.77)
        ->and($units[0]['title'])->toContain('Душанбе');
});

it('builds localised risk zone geojson from config', function () {
    $geojson = app(MapDataService::class)->riskZonesGeoJson('en');

    expect($geojson['type'])->toBe('FeatureCollection')
        ->and($geojson['features'])->not->toBeEmpty()
        ->and($geojson['features'][0]['properties']['name'])->toBeString()
        ->and($geojson['features'][0]['properties']['color'])->toStartWith('#')
        ->and($geojson['features'][0]['geometry']['type'])->toBe('Polygon');
});
