<?php

namespace App\Providers;

use App\Enums\Role;
use App\Listeners\LogAuthenticationActivity;
use App\Listeners\RecordNotificationDelivery;
use App\Models\User;
use App\Services\Public\SharedPublicProps;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment(['production', 'staging'])) {
            URL::forceScheme('https');
        }

        // Relative build URLs so response-cached HTML stays scheme-agnostic (no Mixed Content
        // when the same page is later served over HTTPS after an HTTP-primed cache entry).
        Vite::createAssetPathsUsing(fn (string $path, ?bool $secure = null): string => '/'.ltrim($path, '/'));

        if (! $this->app->isLocal()) {
            Vite::prefetch(concurrency: 3);
        }

        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureRateLimiting();

        // Security audit trail: log sign-in / sign-out / failed-login / 2FA events (ТЗ §12.7).
        Event::subscribe(LogAuthenticationActivity::class);

        // Delivery journal for emergency-alert e-mail + web-push (ТЗ §6.4.4).
        Event::subscribe(RecordNotificationDelivery::class);

        $this->app->make(SharedPublicProps::class)->registerInvalidation();
    }

    /**
     * Named rate limiters. The internal API allows 60 requests/minute, keyed by bearer token (so
     * each integrator gets its own budget) or client IP for the unauthenticated discovery endpoint
     * (ТЗ §10.9, §12.4).
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(60)->by($request->bearerToken() ?: $request->ip());
        });
    }

    /**
     * Grant the super administrator every ability, including gates/policies that are not backed by
     * a named permission (ТЗ §8 — «Полный доступ»).
     */
    protected function configureAuthorization(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->hasRole(Role::SuperAdmin->value) ? true : null;
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Model::preventLazyLoading(! app()->isProduction());

        Str::macro('tajikSlug', function (string $title, string $separator = '-', ?string $language = 'en') {
            // First pass: replace specific Tajik/Cyrillic characters
            $replacements = [
                'ғ' => 'gh', 'Ғ' => 'Gh',
                'ӣ' => 'i',  'Ӣ' => 'I',
                'қ' => 'q',  'Қ' => 'Q',
                'ӯ' => 'u',  'Ӯ' => 'U',
                'ҳ' => 'h',  'Ҳ' => 'H',
                'ҷ' => 'j',  'Ҷ' => 'J',
                'ё' => 'yo', 'Ё' => 'Yo',
                'ж' => 'zh', 'Ж' => 'Zh',
                'х' => 'kh', 'Х' => 'Kh',
                'ч' => 'ch', 'Ч' => 'Ch',
                'ш' => 'sh', 'Ш' => 'Sh',
                'щ' => 'shch', 'Щ' => 'Shch',
                'э' => 'e',  'Э' => 'E',
                'ю' => 'yu', 'Ю' => 'Yu',
                'я' => 'ya', 'Я' => 'Ya',
                'ъ' => '',   'Ъ' => '',
                'ь' => '',   'Ь' => '',
            ];

            $transliterated = strtr($title, $replacements);

            // Second pass: fallback to Laravel's default slugger for remaining chars (ru, etc.)
            return Str::slug($transliterated, $separator, $language);
        });

        Password::defaults(function () {
            $rule = Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols();

            return app()->isProduction() ? $rule->uncompromised() : $rule;
        });
    }
}
