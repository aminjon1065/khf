<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies HTTP security headers to every response (ТЗ §12.1, §12.2): CSP, X-Frame-Options,
 * X-Content-Type-Options, Referrer-Policy, Permissions-Policy and HSTS (HTTPS only). The policy
 * is driven by config/security.php; in local dev the CSP is widened so the Vite dev server / HMR
 * keep working.
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', config('security.frame_options'));
        $headers->set('Referrer-Policy', config('security.referrer_policy'));
        $headers->set('Permissions-Policy', config('security.permissions_policy'));
        $headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        if ($csp = $this->contentSecurityPolicy()) {
            $headers->set('Content-Security-Policy', $csp);
        }

        if ($this->shouldSendHsts($request)) {
            $headers->set('Strict-Transport-Security', $this->hstsValue());
        }

        return $response;
    }

    private function contentSecurityPolicy(): ?string
    {
        if (! config('security.csp.enabled')) {
            return null;
        }

        /** @var array<string, list<string>> $directives */
        $directives = config('security.csp.directives');

        if (app()->isLocal()) {
            $directives = $this->withDevSources($directives);
        }

        return collect($directives)
            ->map(fn (array $sources, string $directive): string => $directive.' '.implode(' ', array_unique($sources)))
            ->implode('; ');
    }

    /**
     * Widen the CSP so the Vite dev server and its HMR websocket are allowed in local dev.
     *
     * @param  array<string, list<string>>  $directives
     * @return array<string, list<string>>
     */
    private function withDevSources(array $directives): array
    {
        $http = [
            'http://localhost:*',
            'http://127.0.0.1:*',
            'http://*.test:*',
            'https://*.test:*',
        ];
        $ws = [
            'ws://localhost:*',
            'ws://127.0.0.1:*',
            'ws://*.test:*',
            'wss://*.test:*',
        ];

        $directives['script-src'] = [...$directives['script-src'], "'unsafe-eval'", ...$http];
        $directives['connect-src'] = [...$directives['connect-src'], ...$http, ...$ws];
        $directives['style-src'] = [...$directives['style-src'], ...$http];
        $directives['font-src'] = [...$directives['font-src'], ...$http];

        return $directives;
    }

    private function shouldSendHsts(Request $request): bool
    {
        return config('security.hsts.enabled')
            && $request->secure()
            && ! app()->isLocal();
    }

    private function hstsValue(): string
    {
        $value = 'max-age='.config('security.hsts.max_age');

        if (config('security.hsts.include_subdomains')) {
            $value .= '; includeSubDomains';
        }

        if (config('security.hsts.preload')) {
            $value .= '; preload';
        }

        return $value;
    }
}
