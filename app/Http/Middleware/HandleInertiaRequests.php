<?php

namespace App\Http\Middleware;

use App\Models\Alert;
use App\Models\Language;
use App\Models\Page;
use App\Models\User;
use App\Support\LocaleUrls;
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
            'translations' => $this->translations(),
            'navPages' => $this->navPages(),
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
     * Interface dictionary for the active locale, consumed by the client `useTranslations` hook
     * (ТЗ §14). Mirrors lang/{locale}/ui.php; an empty map keeps the front end resilient if the
     * file is missing (the client falls back to the translation key).
     *
     * @return array<string, mixed>
     */
    private function translations(): array
    {
        $messages = trans('ui');

        return is_array($messages) ? $messages : [];
    }

    /**
     * Published top-level static pages that have a translation in the active locale, for the footer
     * section navigation (ТЗ §5). Only current-locale-translated pages are listed, so every link
     * resolves (the public page renderer looks up by current-locale slug).
     *
     * @return list<array{title: string, slug: string}>
     */
    private function navPages(): array
    {
        try {
            $locale = app()->getLocale();

            return Page::published()
                ->whereNull('parent_id')
                ->with('translations')
                ->orderBy('sort_order')
                ->get()
                ->map(function (Page $page) use ($locale): ?array {
                    $translation = $page->translations->firstWhere('locale', $locale);

                    return $translation === null
                        ? null
                        : ['title' => $translation->title, 'slug' => $translation->slug];
                })
                ->filter()
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Map of locale code → URL for the language switcher (shared logic with the SEO tags).
     *
     * @return array<string, string>
     */
    private function localeSwitch(Request $request): array
    {
        return app(LocaleUrls::class)->switchMap($request);
    }
}
