<?php

use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Services\SystemLoadService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::clear();
    SystemLoadService::disable();
});

it('disables search during high load', function () {
    SystemLoadService::enable();

    $response = $this->get('/tj/search?q=test');
    $response->assertStatus(503);

    $apiResponse = $this->get('/tj/search/api?q=test');
    $apiResponse->assertStatus(503);
    $apiResponse->assertJson(['data' => []]);
});

it('hides resolved incidents during high load', function () {
    $active = Incident::factory()->create(['status' => IncidentStatus::Active]);
    $resolved = Incident::factory()->create(['status' => IncidentStatus::Resolved]);

    // Normal load
    $response = $this->get('/tj/incidents');
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('public/incidents/index')
        ->has('incidents.data', 2)
    );

    // High load
    Cache::clear(); // clear cache so it hits the controller logic again
    SystemLoadService::enable();

    $response = $this->get('/tj/incidents');
    $response->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->component('public/incidents/index')
        ->has('incidents.data', 1)
        ->where('incidents.data.0.status', 'active')
    );
});

afterEach(function () {
    SystemLoadService::disable();
});
