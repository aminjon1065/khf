<?php

use App\Http\Middleware\ResolveLocalApplicationUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    URL::forceRootUrl(null);
    URL::forceScheme(null);
});

/**
 * @return array<string, mixed>
 */
function forwardedHttpsRequest(string $host = 'khf.test'): array
{
    return [
        'HTTP_HOST' => $host,
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_FORWARDED_HOST' => $host,
        'HTTPS' => 'on',
        'SERVER_PORT' => '443',
    ];
}

it('forces root url from forwarded headers in local', function () {
    app()['env'] = 'local';

    $request = Request::create('/tj', 'GET', [], [], [], forwardedHttpsRequest());

    (new ResolveLocalApplicationUrl)->handle($request, fn () => response('ok'));

    expect(url('/build/assets/app.js'))->toBe('https://khf.test/build/assets/app.js');
});

it('does not override the root url outside local', function () {
    app()['env'] = 'testing';
    URL::forceRootUrl('https://khf.test:8443');
    URL::forceScheme('https');

    $request = Request::create('/tj', 'GET', [], [], [], forwardedHttpsRequest());

    (new ResolveLocalApplicationUrl)->handle($request, fn () => response('ok'));

    expect(url('/build/assets/app.js'))->toBe('https://khf.test:8443/build/assets/app.js');
});
