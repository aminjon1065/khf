<?php

use App\Http\Controllers\Api\V1\AlertController;
use App\Http\Controllers\Api\V1\IncidentController;
use App\Http\Controllers\Api\V1\MetaController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\SetApiLocale;
use Illuminate\Support\Facades\Route;

/*
 * Internal read API (ТЗ §10.9, §18.3). Versioned under /api/v1, locale-aware via ?locale=, and rate
 * limited. The discovery endpoint is open; all data endpoints require an `Authorization: Bearer`
 * token (see the `api:token` command). Errors render as JSON (configured in bootstrap/app.php).
 */
Route::prefix('v1')
    ->name('api.v1.')
    ->middleware([SetApiLocale::class, 'throttle:api'])
    ->group(function () {
        Route::get('/', [MetaController::class, 'index'])->name('meta');

        Route::middleware(AuthenticateApiToken::class)->group(function () {
            Route::get('alerts', [AlertController::class, 'index'])->name('alerts.index');
            Route::get('incidents', [IncidentController::class, 'index'])->name('incidents.index');
            Route::get('news', [PostController::class, 'index'])->name('news.index');
            Route::get('news/{post}', [PostController::class, 'show'])->name('news.show');
        });
    });
