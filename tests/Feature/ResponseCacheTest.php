<?php

use Database\Seeders\LanguageSeeder;
use Illuminate\Http\Request;
use Spatie\ResponseCache\Facades\ResponseCache;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
    ResponseCache::clear();
    config(['responsecache.enabled' => true]);
});

/**
 * @return array<string, string>
 */
function inertiaRequestHeaders(): array
{
    $manifest = public_path('build/manifest.json');

    return [
        'X-Inertia' => 'true',
        'X-Requested-With' => 'XMLHttpRequest',
        'X-Inertia-Version' => file_exists($manifest) ? hash_file('xxh128', $manifest) : '',
    ];
}

it('caches full page visits to the homepage', function () {
    $url = route('welcome', ['locale' => 'tj']);

    $this->get($url)->assertOk();

    expect(ResponseCache::hasBeenCached(Request::create($url)))->toBeTrue();
});

it('does not serve cached html to inertia visits', function () {
    $url = route('welcome', ['locale' => 'tj']);

    $this->get($url)->assertOk();
    expect(ResponseCache::hasBeenCached(Request::create($url)))->toBeTrue();

    $this->withHeaders(inertiaRequestHeaders())
        ->get($url)
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonPath('component', 'public/home');
});

it('does not cache inertia visits', function () {
    $url = route('welcome', ['locale' => 'tj']);

    $this->withHeaders(inertiaRequestHeaders())
        ->get($url)
        ->assertOk();

    expect(ResponseCache::hasBeenCached(Request::create($url)))->toBeFalse();
});
