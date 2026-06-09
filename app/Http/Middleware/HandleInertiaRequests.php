<?php

namespace App\Http\Middleware;

use App\Models\Alert;
use App\Models\Language;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'roles' => $this->authRoles($request),
                'permissions' => $this->authPermissions($request),
            ],
            'locale' => app()->getLocale(),
            'locales' => $this->locales(),
            'localeSwitch' => $this->localeSwitch($request),
            'activeAlerts' => $this->activeAlerts(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Role names of the authenticated user (for permission-aware UI).
     *
     * @return list<string>
     */
    private function authRoles(Request $request): array
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return [];
        }

        try {
            return $user->getRoleNames()->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Effective permission names of the authenticated user (via roles + direct grants).
     *
     * @return list<string>
     */
    private function authPermissions(Request $request): array
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return [];
        }

        try {
            return $user->getAllPermissions()->pluck('name')->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Active emergency alerts for the current locale, ordered by severity — drives the site banner
     * on every public page (ТЗ §6.4.1). Refreshed via Inertia polling (D-11).
     *
     * @return list<array<string, mixed>>
     */
    private function activeAlerts(): array
    {
        $severity = ['critical' => 0, 'danger' => 1, 'elevated' => 2, 'normal' => 3];
        $locale = app()->getLocale();

        try {
            return Alert::active()
                ->with('translations')
                ->get()
                ->sortBy(fn (Alert $alert): int => $severity[$alert->hazard_level->value] ?? 9)
                ->values()
                ->map(function (Alert $alert) use ($locale): array {
                    $translation = $alert->translation($locale);

                    return [
                        'id' => $alert->id,
                        'level' => $alert->hazard_level->value,
                        'level_label' => $alert->hazard_level->label(),
                        'color' => $alert->hazard_level->color(),
                        'title' => $translation?->title,
                        'body' => $translation?->body,
                        'dismissible' => $alert->is_dismissible,
                    ];
                })
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Active portal languages exposed to the front end (ТЗ §14).
     *
     * @return list<array{code: string, native_name: string, hreflang: string, is_default: bool}>
     */
    private function locales(): array
    {
        try {
            return Language::active()
                ->map(fn (Language $language): array => [
                    'code' => $language->code,
                    'native_name' => $language->native_name,
                    'hreflang' => $language->hreflang,
                    'is_default' => $language->is_default,
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Map of locale code → URL for the language switcher. On a localized public route the
     * locale segment is swapped while preserving the rest of the path and query; elsewhere it
     * points to that locale's homepage.
     *
     * @return array<string, string>
     */
    private function localeSwitch(Request $request): array
    {
        $codes = $this->supportedCodes();

        $segments = explode('/', trim($request->path(), '/'));
        $hasLocalePrefix = in_array($segments[0] ?? '', $codes, true);

        $queryString = $request->getQueryString();
        $query = $queryString !== null ? '?'.$queryString : '';

        $map = [];

        foreach ($codes as $code) {
            if ($hasLocalePrefix) {
                $segments[0] = $code;
                $path = implode('/', $segments);
            } else {
                $path = $code;
            }

            $map[$code] = url($path).$query;
        }

        return $map;
    }

    /**
     * @return list<string>
     */
    private function supportedCodes(): array
    {
        try {
            $codes = Language::codes();

            if ($codes !== []) {
                return $codes;
            }
        } catch (\Throwable) {
            // Fall back to the static config allow-list below.
        }

        return config('app.locales');
    }
}
