<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the locale for internal-API responses from the `?locale=` query parameter (ТЗ §10.9,
 * §14), validated against the active languages, falling back to the default. Unlike the web
 * {@see SetLocale}, the API is stateless — there is no session or URL prefix.
 */
class SetApiLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supported = $this->supported();
        $requested = (string) $request->query('locale', '');

        $locale = in_array($requested, $supported, true)
            ? $requested
            : $this->default($supported);

        App::setLocale($locale);

        return $next($request);
    }

    /**
     * Supported locale codes — DB-backed (cached), config allow-list as a fallback.
     *
     * @return list<string>
     */
    private function supported(): array
    {
        try {
            $codes = Language::codes();

            if ($codes !== []) {
                return $codes;
            }
        } catch (\Throwable) {
            // Database not ready — fall back to the static config allow-list.
        }

        return config('app.locales');
    }

    /**
     * @param  list<string>  $supported
     */
    private function default(array $supported): string
    {
        return $supported[0] ?? config('app.fallback_locale');
    }
}
