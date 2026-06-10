<?php

use App\Enums\Permission;
use App\Http\Controllers\Admin\AlertController;
use App\Http\Controllers\Admin\AppealController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\IncidentController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\SubscriberController;
use App\Http\Controllers\Admin\TouristGroupController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
 * CMS (administrative part). No locale prefix — the CMS UI is Russian (ТЗ §7.1). Access is limited
 * to authenticated staff with a CMS role, who must have verified email and confirmed 2FA (ТЗ §12.3).
 * Per-action authorization is enforced with `can:` as modules land.
 */
Route::middleware(['auth', 'verified', 'twofactor.enforce', 'role:super-admin|moderator'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Content — pages (content roles via pages.manage).
        Route::middleware('can:'.Permission::ManagePages->value)->group(function () {
            Route::get('pages', [PageController::class, 'index'])->name('pages.index');
            Route::get('pages/trash', [PageController::class, 'trash'])->name('pages.trash');
            Route::get('pages/create', [PageController::class, 'create'])->name('pages.create');
            Route::post('pages', [PageController::class, 'store'])->name('pages.store');
            Route::get('pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
            Route::put('pages/{page}', [PageController::class, 'update'])->name('pages.update');
            Route::delete('pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');
            Route::patch('pages/{page}/restore', [PageController::class, 'restore'])->name('pages.restore')->withTrashed();
            Route::delete('pages/{page}/force', [PageController::class, 'forceDelete'])->name('pages.force-delete')->withTrashed();
        });

        // Content — posts (news / releases / announcements / summaries).
        Route::middleware('can:'.Permission::ManagePosts->value)->group(function () {
            Route::get('posts', [PostController::class, 'index'])->name('posts.index');
            Route::get('posts/trash', [PostController::class, 'trash'])->name('posts.trash');
            Route::get('posts/create', [PostController::class, 'create'])->name('posts.create');
            Route::post('posts', [PostController::class, 'store'])->name('posts.store');
            Route::get('posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
            Route::put('posts/{post}', [PostController::class, 'update'])->name('posts.update');
            Route::delete('posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
            Route::patch('posts/{post}/restore', [PostController::class, 'restore'])->name('posts.restore')->withTrashed();
            Route::delete('posts/{post}/force', [PostController::class, 'forceDelete'])->name('posts.force-delete')->withTrashed();
        });

        // Content — categories / rubrics.
        Route::middleware('can:'.Permission::ManageCategories->value)->group(function () {
            Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
            Route::get('categories/create', [CategoryController::class, 'create'])->name('categories.create');
            Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
            Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
            Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
            Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        });

        // Emergencies — incidents (incidents.manage).
        Route::middleware('can:'.Permission::ManageIncidents->value)->group(function () {
            Route::get('incidents', [IncidentController::class, 'index'])->name('incidents.index');
            Route::get('incidents/trash', [IncidentController::class, 'trash'])->name('incidents.trash');
            Route::get('incidents/create', [IncidentController::class, 'create'])->name('incidents.create');
            Route::post('incidents', [IncidentController::class, 'store'])->name('incidents.store');
            Route::get('incidents/{incident}/edit', [IncidentController::class, 'edit'])->name('incidents.edit');
            Route::put('incidents/{incident}', [IncidentController::class, 'update'])->name('incidents.update');
            Route::delete('incidents/{incident}', [IncidentController::class, 'destroy'])->name('incidents.destroy');
            Route::patch('incidents/{incident}/restore', [IncidentController::class, 'restore'])->name('incidents.restore')->withTrashed();
            Route::delete('incidents/{incident}/force', [IncidentController::class, 'forceDelete'])->name('incidents.force-delete')->withTrashed();
        });

        // Emergencies — alerts (alerts.manage).
        Route::middleware('can:'.Permission::ManageAlerts->value)->group(function () {
            Route::get('alerts', [AlertController::class, 'index'])->name('alerts.index');
            Route::get('alerts/trash', [AlertController::class, 'trash'])->name('alerts.trash');
            Route::get('alerts/create', [AlertController::class, 'create'])->name('alerts.create');
            Route::post('alerts', [AlertController::class, 'store'])->name('alerts.store');
            Route::get('alerts/{alert}/edit', [AlertController::class, 'edit'])->name('alerts.edit');
            Route::put('alerts/{alert}', [AlertController::class, 'update'])->name('alerts.update');
            Route::delete('alerts/{alert}', [AlertController::class, 'destroy'])->name('alerts.destroy');
            Route::patch('alerts/{alert}/restore', [AlertController::class, 'restore'])->name('alerts.restore')->withTrashed();
            Route::delete('alerts/{alert}/force', [AlertController::class, 'forceDelete'])->name('alerts.force-delete')->withTrashed();
        });

        // Content — documents registry (documents.manage).
        Route::middleware('can:'.Permission::ManageDocuments->value)->group(function () {
            Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
            Route::get('documents/trash', [DocumentController::class, 'trash'])->name('documents.trash');
            Route::get('documents/create', [DocumentController::class, 'create'])->name('documents.create');
            Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
            Route::get('documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
            Route::put('documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
            Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
            Route::patch('documents/{document}/restore', [DocumentController::class, 'restore'])->name('documents.restore')->withTrashed();
            Route::delete('documents/{document}/force', [DocumentController::class, 'forceDelete'])->name('documents.force-delete')->withTrashed();
        });

        // Services — citizen appeals moderation queue (appeals.manage).
        Route::middleware('can:'.Permission::ManageAppeals->value)->group(function () {
            Route::get('appeals', [AppealController::class, 'index'])->name('appeals.index');
            Route::get('appeals/{appeal}', [AppealController::class, 'show'])->name('appeals.show');
            Route::put('appeals/{appeal}', [AppealController::class, 'update'])->name('appeals.update');
            Route::delete('appeals/{appeal}', [AppealController::class, 'destroy'])->name('appeals.destroy');
        });

        // Services — tourist-group applications moderation (tourist-groups.manage).
        Route::middleware('can:'.Permission::ManageTouristGroups->value)->group(function () {
            Route::get('tourist-groups', [TouristGroupController::class, 'index'])->name('tourist-groups.index');
            Route::get('tourist-groups/{touristGroup}', [TouristGroupController::class, 'show'])->name('tourist-groups.show');
            Route::put('tourist-groups/{touristGroup}', [TouristGroupController::class, 'update'])->name('tourist-groups.update');
            Route::delete('tourist-groups/{touristGroup}', [TouristGroupController::class, 'destroy'])->name('tourist-groups.destroy');
        });

        // Notifications — subscriber registry (subscribers.manage).
        Route::middleware('can:'.Permission::ManageSubscribers->value)->group(function () {
            Route::get('subscribers', [SubscriberController::class, 'index'])->name('subscribers.index');
            Route::delete('subscribers/{subscriber}', [SubscriberController::class, 'destroy'])->name('subscribers.destroy');
        });

        // System settings — languages (super-admin only via settings.manage).
        Route::middleware('can:'.Permission::ManageSettings->value)->group(function () {
            Route::get('languages', [LanguageController::class, 'index'])->name('languages.index');
            Route::post('languages', [LanguageController::class, 'store'])->name('languages.store');
            Route::put('languages/{language}', [LanguageController::class, 'update'])->name('languages.update');
            Route::delete('languages/{language}', [LanguageController::class, 'destroy'])->name('languages.destroy');
        });

        // Staff accounts (super-admin only via users.manage).
        Route::middleware('can:'.Permission::ManageUsers->value)->group(function () {
            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::post('users', [UserController::class, 'store'])->name('users.store');
            Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::patch('users/{user}/block', [UserController::class, 'toggleBlock'])->name('users.block');
            Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });
    });
