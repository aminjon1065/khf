<?php

use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Services\Public\SharedPublicProps;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RegionSeeder;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
    $this->seed(RegionSeeder::class);
    config()->set('cms.content_cache.enabled', true);
});

it('invalidates the alert banner when a scheduled window opens', function () {
    $shared = app(SharedPublicProps::class);

    // A published alert scheduled to start in half an hour is not yet active.
    Alert::factory()->create([
        'status' => AlertStatus::Published,
        'starts_at' => now()->addMinutes(30),
        'ends_at' => now()->addHours(3),
    ]);

    // Prime the signature while the alert is still dormant.
    $this->artisan('alerts:refresh-cache')->assertSuccessful();
    $baseline = $shared->version(SharedPublicProps::GROUP_ALERTS);

    // The window opens — a boundary that fires no model event.
    $this->travelTo(now()->addMinutes(31));
    $this->artisan('alerts:refresh-cache')->assertSuccessful();

    expect($shared->version(SharedPublicProps::GROUP_ALERTS))->toBeGreaterThan($baseline);
});

it('does not bump the version when the active set is unchanged', function () {
    $shared = app(SharedPublicProps::class);

    Alert::factory()->create(['status' => AlertStatus::Published]);

    $this->artisan('alerts:refresh-cache')->assertSuccessful();
    $stable = $shared->version(SharedPublicProps::GROUP_ALERTS);

    $this->artisan('alerts:refresh-cache')->assertSuccessful();

    expect($shared->version(SharedPublicProps::GROUP_ALERTS))->toBe($stable);
});

it('invalidates the banner when a scheduled window closes', function () {
    $shared = app(SharedPublicProps::class);

    Alert::factory()->create([
        'status' => AlertStatus::Published,
        'starts_at' => now()->subMinutes(10),
        'ends_at' => now()->addMinutes(5),
    ]);

    $this->artisan('alerts:refresh-cache')->assertSuccessful();
    $baseline = $shared->version(SharedPublicProps::GROUP_ALERTS);

    $this->travelTo(now()->addMinutes(6));
    $this->artisan('alerts:refresh-cache')->assertSuccessful();

    expect($shared->version(SharedPublicProps::GROUP_ALERTS))->toBeGreaterThan($baseline);
});
