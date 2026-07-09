<?php

use Database\Seeders\LanguageSeeder;

beforeEach(fn () => $this->seed(LanguageSeeder::class));

it('sends the baseline security headers on responses', function () {
    $response = $this->get(route('welcome', ['locale' => 'tj']));

    $response->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');

    expect($response->headers->get('Permissions-Policy'))->toContain('geolocation=(self)');
});

it('sends a content security policy with safe defaults', function () {
    $response = $this->get(route('welcome', ['locale' => 'tj']));

    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)
        ->toContain("default-src 'self'")
        ->toContain("object-src 'none'")
        ->toContain("frame-ancestors 'self'")
        ->toContain("base-uri 'self'");
});

it('does not send HSTS over plain http', function () {
    $this->get('http://khf.test/tj')
        ->assertHeaderMissing('Strict-Transport-Security');
});

it('sends HSTS over https outside local', function () {
    // The test environment is "testing" (non-local), so a secure request gets HSTS.
    $response = $this->get('https://khf.test/tj');

    expect($response->headers->get('Strict-Transport-Security'))
        ->toContain('max-age=31536000')
        ->toContain('includeSubDomains');
});

it('allows local vite fonts in the content security policy', function () {
    app()['env'] = 'local';

    $response = $this->get(route('welcome', ['locale' => 'tj']));

    expect($response->headers->get('Content-Security-Policy'))
        ->toContain('font-src')
        ->toContain('https://*.test:*');
});
