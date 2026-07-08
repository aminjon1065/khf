<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\Public\AppealController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\DocumentController;
use App\Http\Controllers\Public\FaqController;
use App\Http\Controllers\Public\FeedController;
use App\Http\Controllers\Public\GalleryController;
use App\Http\Controllers\Public\GovServiceController;
use App\Http\Controllers\Public\GuideController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\IncidentController;
use App\Http\Controllers\Public\LeadershipController;
use App\Http\Controllers\Public\MapController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PollController;
use App\Http\Controllers\Public\PostController;
use App\Http\Controllers\Public\PushSubscriptionController;
use App\Http\Controllers\Public\SearchController;
use App\Http\Controllers\Public\SitemapController;
use App\Http\Controllers\Public\StatisticController;
use App\Http\Controllers\Public\StructureController;
use App\Http\Controllers\Public\SubscriptionController;
use App\Http\Controllers\Public\TenderController;
use App\Http\Controllers\Public\TouristGroupController;
use App\Http\Controllers\Public\VacancyController;
use Illuminate\Support\Facades\Route;
use Spatie\ResponseCache\Middlewares\CacheResponse;

/*
 * Root → resolved localized homepage. The locale is set by the SetLocale middleware
 * (session → browser → default), so first-time visitors land on their language (ТЗ §14).
 */
Route::get('/', fn () => redirect()->route('welcome', ['locale' => app()->getLocale()]))
    ->name('home');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.xml');

Route::get('/health', HealthController::class)
    ->middleware('cache.headers:no_cache;private')
    ->name('health');

/*
 * Public, locale-prefixed content (ТЗ §14, decision D-15). Every locale carries a prefix
 * (/tj, /ru, /en) for clean hreflang/canonical handling.
 */
Route::prefix('{locale}')
    ->whereIn('locale', config('app.locales'))
    ->group(function () {
        Route::get('/', [HomeController::class, 'index'])->middleware(CacheResponse::class)->name('welcome');
        Route::get('search', [SearchController::class, 'index'])->name('search.index');
        Route::get('search/api', [SearchController::class, 'api'])->name('search.api');
        Route::get('news', [PostController::class, 'index'])->middleware(CacheResponse::class)->name('news.index');
        Route::get('news/rss', [FeedController::class, 'news'])->middleware(CacheResponse::class)->name('news.rss');
        Route::get('news/{slug}', [PostController::class, 'show'])->middleware(CacheResponse::class)->name('news.show');
        Route::get('incidents', [IncidentController::class, 'index'])->middleware(CacheResponse::class)->name('incidents.index');
        Route::get('map', [MapController::class, 'index'])->middleware(CacheResponse::class)->name('map.index');
        Route::get('documents', [DocumentController::class, 'index'])->middleware(CacheResponse::class)->name('documents.index');
        Route::get('documents/{document}/files/{media}', [DocumentController::class, 'download'])->name('documents.download');

        // Safety guides catalogue + guide page (ТЗ §6.5). Download is controlled (private disk).
        Route::get('guides', [GuideController::class, 'index'])->middleware([CacheResponse::class, 'cache.headers:public;max_age=3600;etag'])->name('guides.index');
        Route::get('guides/{guide}/files/{media}', [GuideController::class, 'download'])->name('guides.download');
        Route::get('guides/{slug}', [GuideController::class, 'show'])->middleware([CacheResponse::class, 'cache.headers:public;max_age=3600;etag'])->name('guides.show');

        // About the organisation — leadership and structure (ТЗ §20 «г», «б»).
        Route::get('leadership', [LeadershipController::class, 'index'])->middleware([CacheResponse::class, 'cache.headers:public;max_age=3600;etag'])->name('leadership.index');
        Route::get('structure', [StructureController::class, 'index'])->middleware([CacheResponse::class, 'cache.headers:public;max_age=3600;etag'])->name('structure.index');

        // Photo galleries (ТЗ §20 «ш»). `gallery` is registered before `{slug}`.
        Route::get('gallery', [GalleryController::class, 'index'])->middleware([CacheResponse::class, 'cache.headers:public;max_age=3600;etag'])->name('gallery.index');
        Route::get('gallery/{slug}', [GalleryController::class, 'show'])->middleware([CacheResponse::class, 'cache.headers:public;max_age=3600;etag'])->name('gallery.show');

        // FAQ — questions & answers (ТЗ §20 «й»).
        Route::get('faq', [FaqController::class, 'index'])->middleware([CacheResponse::class, 'cache.headers:public;max_age=3600;etag'])->name('faq.index');

        // Public opinion polls (ТЗ §8, §20 «к»).
        Route::get('polls', [PollController::class, 'index'])->name('polls.index');
        Route::get('polls/{slug}', [PollController::class, 'show'])->name('polls.show');
        Route::post('polls/{slug}/vote', [PollController::class, 'vote'])->middleware('throttle:6,1')->name('polls.vote');

        // Government services catalogue (ТЗ §20 «ф»).
        Route::get('services', [GovServiceController::class, 'index'])->middleware([CacheResponse::class, 'cache.headers:public;max_age=3600;etag'])->name('services.index');
        Route::get('services/{slug}', [GovServiceController::class, 'show'])->middleware([CacheResponse::class, 'cache.headers:public;max_age=3600;etag'])->name('services.show');

        // Official statistics — key activity indicators (ТЗ §20 «у»).
        Route::get('statistics', [StatisticController::class, 'index'])->middleware([CacheResponse::class, 'cache.headers:public;max_age=3600;etag'])->name('statistics.index');

        // Civil-service vacancies + online application with receipt confirmation (ТЗ §20 «н», §21).
        // `track` is registered before `{slug}` so it is not captured as a vacancy slug.
        Route::get('vacancies', [VacancyController::class, 'index'])->middleware(CacheResponse::class)->name('vacancies.index');
        Route::get('vacancies/track', [VacancyController::class, 'track'])->middleware('throttle:20,1')->name('vacancies.track');
        Route::get('vacancies/{slug}', [VacancyController::class, 'show'])->name('vacancies.show');
        Route::post('vacancies/{vacancy}/apply', [VacancyController::class, 'apply'])->middleware('throttle:6,1')->name('vacancies.apply');

        // Public procurement tenders + online bid submission with receipt confirmation (ТЗ §9, §20 «э»).
        // `track` is registered before `{slug}` so it is not captured as a tender slug.
        Route::get('tenders', [TenderController::class, 'index'])->middleware(CacheResponse::class)->name('tenders.index');
        Route::get('tenders/track', [TenderController::class, 'track'])->middleware('throttle:20,1')->name('tenders.track');
        Route::get('tenders/{slug}', [TenderController::class, 'show'])->name('tenders.show');
        Route::post('tenders/{tender}/bid', [TenderController::class, 'submitBid'])->middleware('throttle:6,1')->name('tenders.bid');

        // Contacts: emergency numbers, regional offices, map + feedback (ТЗ §6.9).
        Route::get('contacts', [ContactController::class, 'index'])->middleware([CacheResponse::class, 'cache.headers:public;max_age=3600;etag'])->name('contacts.index');

        // Citizen appeals (electronic reception) — public form is rate-limited (ТЗ §12.4).
        Route::get('appeals', [AppealController::class, 'create'])->name('appeals.create');
        Route::post('appeals', [AppealController::class, 'store'])->middleware('throttle:6,1')->name('appeals.store');
        // Tracking lookups are throttled to prevent brute-force enumeration of reference numbers.
        Route::get('appeals/track', [AppealController::class, 'track'])->middleware('throttle:20,1')->name('appeals.track');

        // Tourist-group registration — public form is rate-limited (ТЗ §12.4).
        Route::get('tourist-groups', [TouristGroupController::class, 'create'])->name('tourist-groups.create');
        Route::post('tourist-groups', [TouristGroupController::class, 'store'])->middleware('throttle:6,1')->name('tourist-groups.store');
        // Tracking lookups are throttled to prevent brute-force enumeration of reference numbers.
        Route::get('tourist-groups/track', [TouristGroupController::class, 'track'])->middleware('throttle:20,1')->name('tourist-groups.track');

        // Notification subscriptions (double opt-in) — public form is rate-limited (ТЗ §6.4.3).
        Route::get('subscribe', [SubscriptionController::class, 'create'])->name('subscriptions.create');
        Route::post('subscribe', [SubscriptionController::class, 'store'])->middleware('throttle:6,1')->name('subscriptions.store');
        Route::get('subscribe/confirm/{token}', [SubscriptionController::class, 'confirm'])->name('subscriptions.confirm');
        Route::get('subscribe/unsubscribe/{token}', [SubscriptionController::class, 'unsubscribe'])->name('subscriptions.unsubscribe');

        // Web Push subscriptions — rate-limited like the other public endpoints (ТЗ §12.4).
        Route::post('push/subscribe', [PushSubscriptionController::class, 'store'])->middleware('throttle:10,1')->name('push.subscribe');
        Route::post('push/unsubscribe', [PushSubscriptionController::class, 'destroy'])->middleware('throttle:10,1')->name('push.unsubscribe');

        // CMS-managed static content pages (About / Activities / Contacts …) by current-locale slug.
        Route::get('pages/{slug}', [PageController::class, 'show'])->middleware([CacheResponse::class, 'cache.headers:public;max_age=3600;etag'])->name('pages.show');
    });

/*
 * Authenticated application (dashboard / CMS / settings) — no locale prefix; the CMS UI is
 * Russian (ТЗ §7.1) and these routes resolve locale from the session.
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';

Route::fallback(function () {
    $path = request()->path();
    $redirects = config('redirects', []);

    if (array_key_exists($path, $redirects)) {
        return redirect($redirects[$path], 301);
    }

    $pathWithSlash = '/'.ltrim($path, '/');
    if (array_key_exists($pathWithSlash, $redirects)) {
        return redirect($redirects[$pathWithSlash], 301);
    }

    abort(404);
});
