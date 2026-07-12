<?php

use App\Http\Controllers\Admin\AlertController;
use App\Http\Controllers\Admin\Concerns\ManagesSoftDeletableContent;
use App\Http\Controllers\Admin\Concerns\RedirectsToContentBrowser;
use App\Http\Controllers\Admin\Concerns\SavesContentRevisions;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\GuideController;
use App\Http\Controllers\Admin\IncidentController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\TenderController;
use App\Http\Controllers\Admin\VacancyController;

it('uses shared soft-delete and browser redirect traits on CMS controllers', function (string $controller) {
    $traits = class_uses_recursive($controller);

    expect($traits)->toContain(
        ManagesSoftDeletableContent::class,
        RedirectsToContentBrowser::class,
        SavesContentRevisions::class,
    );
})->with([
    PageController::class,
    PostController::class,
    DocumentController::class,
    GuideController::class,
    VacancyController::class,
    TenderController::class,
    IncidentController::class,
    AlertController::class,
]);
