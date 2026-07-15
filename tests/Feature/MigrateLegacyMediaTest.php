<?php

use App\Models\Leader;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
});

it('skips records that already have media and makes no network call', function () {
    $this->seed(ProductionSeeder::class);

    // Give every leader a photo up front so the command has nothing to download.
    Leader::query()->get()->each(
        fn (Leader $leader) => $leader->addMedia(UploadedFile::fake()->image('p.jpg'))
            ->toMediaCollection(Leader::PHOTO_COLLECTION),
    );

    $before = Media::query()->where('collection_name', 'photo')->count();

    $this->artisan('legacy:migrate-media --only=leaders')->assertSuccessful();

    // No new media rows — every leader was skipped, so no download was attempted.
    expect(Media::query()->where('collection_name', 'photo')->count())->toBe($before);
});

it('rejects an invalid --only option', function () {
    $this->artisan('legacy:migrate-media --only=bogus')->assertFailed();
});
