<?php

use App\Support\StagingSmokeChecker;
use Database\Seeders\LanguageSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => $this->seed(LanguageSeeder::class));

it('passes in-process smoke checks for critical public routes', function () {
    $this->artisan('deploy:smoke', ['--in-process' => true])
        ->expectsOutputToContain('Smoke checks passed')
        ->assertSuccessful();
});

it('fails when a smoke path returns an unexpected status', function () {
    config([
        'deployment.smoke.paths' => [
            ['path' => '/this-route-does-not-exist-smoke', 'expect' => [200]],
        ],
        'deployment.smoke.locale_paths' => [],
        'deployment.health_check_token' => '',
    ]);

    $this->artisan('deploy:smoke', ['--in-process' => true])
        ->expectsOutputToContain('Smoke checks failed')
        ->assertFailed();
});

it('runs live HTTP smoke checks against a base url', function () {
    Http::fake([
        'https://staging.example.test/up' => Http::response('OK', 200),
        'https://staging.example.test/health' => Http::response([
            'status' => 'ok',
            'checks' => [],
        ], 200),
        'https://staging.example.test/sitemap.xml' => Http::response('<urlset></urlset>', 200),
        'https://staging.example.test/tj' => Http::response(
            '<html><head><meta name="csrf-token" content="x"></head></html>',
            200,
            ['Content-Security-Policy' => "default-src 'self'"],
        ),
    ]);

    config([
        'app.url' => 'https://staging.example.test',
        'app.locales' => ['tj'],
        'deployment.smoke.paths' => [
            ['path' => '/up', 'expect' => [200]],
            ['path' => '/health', 'expect' => [200], 'json_status' => 'ok'],
            ['path' => '/sitemap.xml', 'expect' => [200]],
        ],
        'deployment.smoke.locale_paths' => ['/'],
        'deployment.health_check_token' => 'ops-token',
    ]);

    $this->artisan('deploy:smoke', [
        '--http' => true,
        '--base-url' => 'https://staging.example.test',
    ])
        ->expectsOutputToContain('Smoke checks passed')
        ->assertSuccessful();

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://staging.example.test/health'
        && $request->hasHeader('Authorization', 'Bearer ops-token'));
});

it('detects missing csrf meta on the homepage', function () {
    $checker = app(StagingSmokeChecker::class);

    config([
        'deployment.smoke.paths' => [],
        'deployment.smoke.locale_paths' => ['/'],
        'deployment.health_check_token' => '',
        'app.locales' => ['tj'],
    ]);

    Http::fake([
        'https://staging.example.test/tj' => Http::response('<html></html>', 200, [
            'Content-Security-Policy' => "default-src 'self'",
        ]),
    ]);

    $results = $checker->run(inProcess: false, baseUrl: 'https://staging.example.test', locales: ['tj']);

    expect($checker->allPassed($results))->toBeFalse()
        ->and(collect($results)->firstWhere('path', '/tj')['message'] ?? null)
        ->toBe('csrf-token meta missing');
});
