<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves and applies the active locale for every web request (ТЗ §14).
 *
 * Resolution order: URL `{locale}` prefix (public localized routes) → session (last choice) →
 * browser Accept-Language → configured default. The chosen locale is persisted to the session so
 * unprefixed areas (CMS, auth) keep the visitor's language. The supported set comes from the
 * `languages` table (cached) with `config('app.locales')` as a boot/empty-DB fallback.
 */
class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);

        App::setLocale($locale);
        $request->session()->put('locale', $locale);

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        $supported = $this->supported();

        $param = $request->route('locale');
        if (is_string($param) && in_array($param, $supported, true)) {
            return $param;
        }

        $session = $request->session()->get('locale');
        if (is_string($session) && in_array($session, $supported, true)) {
            return $session;
        }

        $browser = $this->browserLocale($request, $supported);
        if ($browser !== null) {
            return $browser;
        }

        return $this->default();
    }

    /**
     * Best Accept-Language match among supported locales, or null if none match.
     * The Tajik BCP-47 tag `tg` maps to the internal `tj` code (decision D-14).
     *
     * @param  list<string>  $supported
     */
    private function browserLocale(Request $request, array $supported): ?string
    {
        foreach ($request->getLanguages() as $language) {
            $primary = strtolower(substr((string) $language, 0, 2));
            $primary = $primary === 'tg' ? 'tj' : $primary;

            if (in_array($primary, $supported, true)) {
                return $primary;
            }
        }

        return null;
    }

    /**
     * Supported locale codes — DB-backed (cached), falling back to config if the
     * table is empty or unavailable (e.g. before migrations).
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

    private function default(): string
    {
        try {
            $codes = Language::codes();

            if ($codes !== []) {
                return Language::defaultCode();
            }
        } catch (\Throwable) {
            // Fall through to config.
        }

        return config('app.locales')[0] ?? config('app.fallback_locale');
    }
}
