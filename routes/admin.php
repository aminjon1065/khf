<?php

use App\Enums\Permission;
use App\Http\Controllers\Admin\AlertController;
use App\Http\Controllers\Admin\AppealController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\GuideController;
use App\Http\Controllers\Admin\IncidentController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\LeaderController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\RevisionController;
use App\Http\Controllers\Admin\StatisticController;
use App\Http\Controllers\Admin\SubdivisionController;
use App\Http\Controllers\Admin\SubscriberController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\TenderBidController;
use App\Http\Controllers\Admin\TenderController;
use App\Http\Controllers\Admin\TouristGroupController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VacancyApplicationController;
use App\Http\Controllers\Admin\VacancyController;
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

        // Media Library (accessible to all CMS users)
        Route::get('media', [MediaController::class, 'index'])->name('media.index');
        Route::get('api/media', [MediaController::class, 'apiIndex'])->name('api.media.index');
        Route::post('media', [MediaController::class, 'store'])->name('media.store');
        Route::put('media/{mediaFile}', [MediaController::class, 'update'])->name('media.update');
        Route::delete('media/{mediaFile}', [MediaController::class, 'destroy'])->name('media.destroy');

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

        // Content — tags.
        Route::middleware('can:'.Permission::ManageTags->value)->group(function () {
            Route::get('tags', [TagController::class, 'index'])->name('tags.index');
            Route::get('tags/create', [TagController::class, 'create'])->name('tags.create');
            Route::post('tags', [TagController::class, 'store'])->name('tags.store');
            Route::get('tags/{tag}/edit', [TagController::class, 'edit'])->name('tags.edit');
            Route::put('tags/{tag}', [TagController::class, 'update'])->name('tags.update');
            Route::delete('tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
        });

        // Menus (menus.manage).
        Route::middleware('can:'.Permission::ManageMenus->value)->group(function () {
            Route::get('menus', [MenuController::class, 'index'])->name('menus.index');
            Route::get('menus/{menu}', [MenuController::class, 'show'])->name('menus.show');
            Route::post('menus/{menu}/items', [MenuItemController::class, 'store'])->name('menus.items.store');
            Route::put('menus/{menu}/items/{item}', [MenuItemController::class, 'update'])->name('menus.items.update');
            Route::delete('menus/{menu}/items/{item}', [MenuItemController::class, 'destroy'])->name('menus.items.destroy');
            Route::post('menus/{menu}/reorder', [MenuItemController::class, 'reorder'])->name('menus.reorder');
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
            Route::get('alerts/estimate', [AlertController::class, 'estimateRecipients'])->name('alerts.estimate');
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

        // Content — safety guides / memos (guides.manage).
        Route::middleware('can:'.Permission::ManageGuides->value)->group(function () {
            Route::get('guides', [GuideController::class, 'index'])->name('guides.index');
            Route::get('guides/trash', [GuideController::class, 'trash'])->name('guides.trash');
            Route::get('guides/create', [GuideController::class, 'create'])->name('guides.create');
            Route::post('guides', [GuideController::class, 'store'])->name('guides.store');
            Route::get('guides/{guide}/edit', [GuideController::class, 'edit'])->name('guides.edit');
            Route::put('guides/{guide}', [GuideController::class, 'update'])->name('guides.update');
            Route::delete('guides/{guide}', [GuideController::class, 'destroy'])->name('guides.destroy');
            Route::patch('guides/{guide}/restore', [GuideController::class, 'restore'])->name('guides.restore')->withTrashed();
            Route::delete('guides/{guide}/force', [GuideController::class, 'forceDelete'])->name('guides.force-delete')->withTrashed();
        });

        // Content — leadership (leadership.manage).
        Route::middleware('can:'.Permission::ManageLeadership->value)->group(function () {
            Route::get('leadership', [LeaderController::class, 'index'])->name('leadership.index');
            Route::get('leadership/create', [LeaderController::class, 'create'])->name('leadership.create');
            Route::post('leadership', [LeaderController::class, 'store'])->name('leadership.store');
            Route::get('leadership/{leader}/edit', [LeaderController::class, 'edit'])->name('leadership.edit');
            Route::put('leadership/{leader}', [LeaderController::class, 'update'])->name('leadership.update');
            Route::delete('leadership/{leader}', [LeaderController::class, 'destroy'])->name('leadership.destroy');
        });

        // Content — organisational structure (structure.manage).
        Route::middleware('can:'.Permission::ManageStructure->value)->group(function () {
            Route::get('structure', [SubdivisionController::class, 'index'])->name('structure.index');
            Route::get('structure/create', [SubdivisionController::class, 'create'])->name('structure.create');
            Route::post('structure', [SubdivisionController::class, 'store'])->name('structure.store');
            Route::get('structure/{subdivision}/edit', [SubdivisionController::class, 'edit'])->name('structure.edit');
            Route::put('structure/{subdivision}', [SubdivisionController::class, 'update'])->name('structure.update');
            Route::delete('structure/{subdivision}', [SubdivisionController::class, 'destroy'])->name('structure.destroy');
        });

        // Content — photo galleries (gallery.manage).
        Route::middleware('can:'.Permission::ManageGallery->value)->group(function () {
            Route::get('gallery', [GalleryController::class, 'index'])->name('gallery.index');
            Route::get('gallery/create', [GalleryController::class, 'create'])->name('gallery.create');
            Route::post('gallery', [GalleryController::class, 'store'])->name('gallery.store');
            Route::get('gallery/{gallery}/edit', [GalleryController::class, 'edit'])->name('gallery.edit');
            Route::put('gallery/{gallery}', [GalleryController::class, 'update'])->name('gallery.update');
            Route::delete('gallery/{gallery}', [GalleryController::class, 'destroy'])->name('gallery.destroy');
        });

        // Content — FAQ / questions & answers (faqs.manage).
        Route::middleware('can:'.Permission::ManageFaqs->value)->group(function () {
            Route::get('faqs', [FaqController::class, 'index'])->name('faqs.index');
            Route::get('faqs/create', [FaqController::class, 'create'])->name('faqs.create');
            Route::post('faqs', [FaqController::class, 'store'])->name('faqs.store');
            Route::get('faqs/{faq}/edit', [FaqController::class, 'edit'])->name('faqs.edit');
            Route::put('faqs/{faq}', [FaqController::class, 'update'])->name('faqs.update');
            Route::delete('faqs/{faq}', [FaqController::class, 'destroy'])->name('faqs.destroy');
        });

        // Content — official statistics indicators (statistics.manage).
        Route::middleware('can:'.Permission::ManageStatistics->value)->group(function () {
            Route::get('statistics', [StatisticController::class, 'index'])->name('statistics.index');
            Route::get('statistics/create', [StatisticController::class, 'create'])->name('statistics.create');
            Route::post('statistics', [StatisticController::class, 'store'])->name('statistics.store');
            Route::get('statistics/{statistic}/edit', [StatisticController::class, 'edit'])->name('statistics.edit');
            Route::put('statistics/{statistic}', [StatisticController::class, 'update'])->name('statistics.update');
            Route::delete('statistics/{statistic}', [StatisticController::class, 'destroy'])->name('statistics.destroy');
        });

        // Content — civil-service vacancies (vacancies.manage).
        Route::middleware('can:'.Permission::ManageVacancies->value)->group(function () {
            Route::get('vacancies', [VacancyController::class, 'index'])->name('vacancies.index');
            Route::get('vacancies/trash', [VacancyController::class, 'trash'])->name('vacancies.trash');
            Route::get('vacancies/create', [VacancyController::class, 'create'])->name('vacancies.create');
            Route::post('vacancies', [VacancyController::class, 'store'])->name('vacancies.store');
            Route::get('vacancies/{vacancy}/edit', [VacancyController::class, 'edit'])->name('vacancies.edit');
            Route::put('vacancies/{vacancy}', [VacancyController::class, 'update'])->name('vacancies.update');
            Route::delete('vacancies/{vacancy}', [VacancyController::class, 'destroy'])->name('vacancies.destroy');
            Route::patch('vacancies/{vacancy}/restore', [VacancyController::class, 'restore'])->name('vacancies.restore')->withTrashed();
            Route::delete('vacancies/{vacancy}/force', [VacancyController::class, 'forceDelete'])->name('vacancies.force-delete')->withTrashed();
        });

        // Content — public procurement tenders (tenders.manage).
        Route::middleware('can:'.Permission::ManageTenders->value)->group(function () {
            Route::get('tenders', [TenderController::class, 'index'])->name('tenders.index');
            Route::get('tenders/trash', [TenderController::class, 'trash'])->name('tenders.trash');
            Route::get('tenders/create', [TenderController::class, 'create'])->name('tenders.create');
            Route::post('tenders', [TenderController::class, 'store'])->name('tenders.store');
            Route::get('tenders/{tender}/edit', [TenderController::class, 'edit'])->name('tenders.edit');
            Route::put('tenders/{tender}', [TenderController::class, 'update'])->name('tenders.update');
            Route::delete('tenders/{tender}', [TenderController::class, 'destroy'])->name('tenders.destroy');
            Route::patch('tenders/{tender}/restore', [TenderController::class, 'restore'])->name('tenders.restore')->withTrashed();
            Route::delete('tenders/{tender}/force', [TenderController::class, 'forceDelete'])->name('tenders.force-delete')->withTrashed();
        });

        // Services — vacancy applications (questionnaires) moderation (vacancy-applications.manage).
        Route::middleware('can:'.Permission::ManageVacancyApplications->value)->group(function () {
            Route::get('vacancy-applications', [VacancyApplicationController::class, 'index'])->name('vacancy-applications.index');
            Route::get('vacancy-applications/{application}', [VacancyApplicationController::class, 'show'])->name('vacancy-applications.show');
            Route::get('vacancy-applications/{application}/resume', [VacancyApplicationController::class, 'downloadResume'])->name('vacancy-applications.resume');
            Route::put('vacancy-applications/{application}', [VacancyApplicationController::class, 'update'])->name('vacancy-applications.update');
            Route::delete('vacancy-applications/{application}', [VacancyApplicationController::class, 'destroy'])->name('vacancy-applications.destroy');
        });

        // Services — tender bids moderation (tender-bids.manage).
        Route::middleware('can:'.Permission::ManageTenderBids->value)->group(function () {
            Route::get('tender-bids', [TenderBidController::class, 'index'])->name('tender-bids.index');
            Route::get('tender-bids/{bid}', [TenderBidController::class, 'show'])->name('tender-bids.show');
            Route::get('tender-bids/{bid}/document', [TenderBidController::class, 'downloadDocument'])->name('tender-bids.document');
            Route::put('tender-bids/{bid}', [TenderBidController::class, 'update'])->name('tender-bids.update');
            Route::delete('tender-bids/{bid}', [TenderBidController::class, 'destroy'])->name('tender-bids.destroy');
        });

        // Services — citizen appeals moderation queue (appeals.manage).
        Route::middleware('can:'.Permission::ManageAppeals->value)->group(function () {
            Route::get('appeals', [AppealController::class, 'index'])->name('appeals.index');
            Route::get('appeals/export', [AppealController::class, 'export'])->name('appeals.export');
            Route::get('appeals/{appeal}/download/{media}', [AppealController::class, 'downloadAttachment'])->name('appeals.download-attachment');
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

        // Audit log — read-only security/activity trail (ТЗ §7.10, §12.7), gated by audit.view.
        Route::middleware('can:'.Permission::ViewAudit->value)->group(function () {
            Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        });

        // Revisions (rollback for key materials)
        Route::get('revisions/{type}/{id}', [RevisionController::class, 'index'])->name('revisions.index');
        Route::post('revisions/{revision}/restore', [RevisionController::class, 'restore'])->name('revisions.restore');
    });
