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

it('keeps separate cache entries for http and https', function () {
    $http = Request::create('http://khf.test/tj', 'GET');
    $https = Request::create('https://khf.test/tj', 'GET');

    $this->get('http://khf.test/tj')->assertOk();

    expect(ResponseCache::hasBeenCached($http))->toBeTrue();
    expect(ResponseCache::hasBeenCached($https))->toBeFalse();

    $this->get('https://khf.test/tj')->assertOk();

    expect(ResponseCache::hasBeenCached($https))->toBeTrue();
});

it('emits relative vite asset urls that work on https pages', function () {
    $this->get('https://khf.test/tj')
        ->assertOk()
        ->assertSee('src="/build/assets/', false)
        ->assertDontSee('src="http://khf.test/build/', false);
});
