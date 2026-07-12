<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Align generated URLs with the browser origin during local development.
 *
 * Laragon/Herd sites are often opened as https://khf.test while APP_URL still
 * points at a custom port (e.g. :8443). Absolute Vite/asset URLs would then
 * cross origins and trip CORS + CSP.
 */
class ResolveLocalApplicationUrl
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isLocal() && $request->getHost() !== '') {
            $rootUrl = $this->rootUrl($request);

            URL::forceRootUrl($rootUrl);

            if (str_starts_with($rootUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }

        return $next($request);
    }

    private function rootUrl(Request $request): string
    {
        $scheme = $request->headers->get('X-Forwarded-Proto', $request->getScheme());
        $host = $request->headers->get('X-Forwarded-Host', $request->getHttpHost());

        // Herd/nginx often terminate TLS and forward plain HTTP to PHP-FPM without
        // X-Forwarded-Proto. Prefer the configured APP_URL scheme when the host matches.
        $configured = parse_url((string) config('app.url'));
        if (
            ($configured['scheme'] ?? null) === 'https'
            && $scheme === 'http'
            && ($configured['host'] ?? null) === $request->getHost()
        ) {
            $scheme = 'https';
        }

        return $scheme.'://'.$host;
    }
}
