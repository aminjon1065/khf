<?php

use App\Http\Controllers\Public\AppealController;
use App\Http\Controllers\Public\DocumentController;
use App\Http\Controllers\Public\FeedController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\IncidentController;
use App\Http\Controllers\Public\MapController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PostController;
use App\Http\Controllers\Public\SubscriptionController;
use App\Http\Controllers\Public\TouristGroupController;
use Illuminate\Support\Facades\Route;

/*
 * Root → resolved localized homepage. The locale is set by the SetLocale middleware
 * (session → browser → default), so first-time visitors land on their language (ТЗ §14).
 */
Route::get('/', fn () => redirect()->route('welcome', ['locale' => app()->getLocale()]))
    ->name('home');

/*
 * Public, locale-prefixed content (ТЗ §14, decision D-15). Every locale carries a prefix
 * (/tj, /ru, /en) for clean hreflang/canonical handling.
 */
Route::prefix('{locale}')
    ->whereIn('locale', config('app.locales'))
    ->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('welcome');
        Route::get('news', [PostController::class, 'index'])->name('news.index');
        Route::get('news/rss', [FeedController::class, 'news'])->name('news.rss');
        Route::get('news/{slug}', [PostController::class, 'show'])->name('news.show');
        Route::get('incidents', [IncidentController::class, 'index'])->name('incidents.index');
        Route::get('map', [MapController::class, 'index'])->name('map.index');
        Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('documents/{document}/files/{media}', [DocumentController::class, 'download'])->name('documents.download');

        // Citizen appeals (electronic reception) — public form is rate-limited (ТЗ §12.4).
        Route::get('appeals', [AppealController::class, 'create'])->name('appeals.create');
        Route::post('appeals', [AppealController::class, 'store'])->middleware('throttle:6,1')->name('appeals.store');
        Route::get('appeals/track', [AppealController::class, 'track'])->name('appeals.track');

        // Tourist-group registration — public form is rate-limited (ТЗ §12.4).
        Route::get('tourist-groups', [TouristGroupController::class, 'create'])->name('tourist-groups.create');
        Route::post('tourist-groups', [TouristGroupController::class, 'store'])->middleware('throttle:6,1')->name('tourist-groups.store');
        Route::get('tourist-groups/track', [TouristGroupController::class, 'track'])->name('tourist-groups.track');

        // Notification subscriptions (double opt-in) — public form is rate-limited (ТЗ §6.4.3).
        Route::get('subscribe', [SubscriptionController::class, 'create'])->name('subscriptions.create');
        Route::post('subscribe', [SubscriptionController::class, 'store'])->middleware('throttle:6,1')->name('subscriptions.store');
        Route::get('subscribe/confirm/{token}', [SubscriptionController::class, 'confirm'])->name('subscriptions.confirm');
        Route::get('subscribe/unsubscribe/{token}', [SubscriptionController::class, 'unsubscribe'])->name('subscriptions.unsubscribe');

        // CMS-managed static content pages (About / Activities / Contacts …) by current-locale slug.
        Route::get('pages/{slug}', [PageController::class, 'show'])->name('pages.show');
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
