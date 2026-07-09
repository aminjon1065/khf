<?php

use App\Http\Controllers\Admin\AlertController;
use App\Http\Controllers\Admin\Concerns\ListsTranslatableContent;
use App\Http\Controllers\Admin\Concerns\ManagesSoftDeletableContent;
use App\Http\Controllers\Admin\Concerns\SavesContentRevisions;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\GuideController;
use App\Http\Controllers\Admin\IncidentController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\TenderController;
use App\Http\Controllers\Admin\VacancyController;

it('uses shared CMS listing traits on translatable admin controllers', function (string $controller) {
    $traits = class_uses_recursive($controller);

    expect($traits)->toContain(
        ListsTranslatableContent::class,
        ManagesSoftDeletableContent::class,
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

it('exposes paginateTrashed on the listing trait', function () {
    expect(method_exists(
        ListsTranslatableContent::class,
        'paginateTrashed',
    ))->toBeTrue();
});
