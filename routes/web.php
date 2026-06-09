<?php

use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\IncidentController;
use App\Http\Controllers\Public\MapController;
use App\Http\Controllers\Public\PostController;
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
        Route::get('news/{slug}', [PostController::class, 'show'])->name('news.show');
        Route::get('incidents', [IncidentController::class, 'index'])->name('incidents.index');
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
