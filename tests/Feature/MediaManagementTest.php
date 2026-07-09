<?php

use App\Enums\Role;
use App\Models\MediaFile;
use App\Models\MediaFolder;
use App\Models\MediaTag;
use App\Models\MediaUsage;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, LanguageSeeder::class]);

    $this->editor = User::factory()->withTwoFactor()->create();
    $this->editor->assignRole(Role::Moderator->value);
});

function createLibraryImage(User $user, string $name = 'photo.jpg', ?string $altText = null): MediaFile
{
    $mediaFile = MediaFile::create([
        'user_id' => $user->id,
        'name' => $name,
        'alt_text' => $altText,
    ]);

    $mediaFile->addMedia(UploadedFile::fake()->image($name, 640, 480))
        ->toMediaCollection('default');

    return $mediaFile;
}

function createLibraryDocument(User $user, string $name = 'report.pdf'): MediaFile
{
    $mediaFile = MediaFile::create([
        'user_id' => $user->id,
        'name' => $name,
    ]);

    $mediaFile->addMedia(UploadedFile::fake()->create($name, 50, 'application/pdf'))
        ->toMediaCollection('default');

    return $mediaFile;
}

it('renders the media library index for CMS users', function () {
    createLibraryImage($this->editor);

    $this->actingAs($this->editor)
        ->get(route('admin.media.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/media/index')
            ->has('mediaFiles.data', 1)
            ->has('folders')
            ->has('locales')
            ->where('filters.search', '')
            ->where('filters.type', '')
            ->where('filters.folder_id', 'all')
            ->where('filters.tag', ''));
});

it('uploads a file to the media library', function () {
    $this->actingAs($this->editor)
        ->post(route('admin.media.store'), [
            'file' => UploadedFile::fake()->image('upload.jpg'),
        ])
        ->assertRedirect();

    expect(MediaFile::count())->toBe(1)
        ->and(MediaFile::first()->getFirstMedia('default'))->not->toBeNull();
});

it('searches media files by name and alt text', function () {
    createLibraryImage($this->editor, 'mountain.jpg', 'Snow peak');
    createLibraryImage($this->editor, 'river.jpg', 'Water flow');

    $this->actingAs($this->editor)
        ->get(route('admin.media.index', ['search' => 'snow']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('mediaFiles.data', 1)
            ->where('mediaFiles.data.0.name', 'mountain.jpg'));

    $this->actingAs($this->editor)
        ->getJson(route('admin.api.media.index', ['search' => 'river']))
        ->assertOk()
        ->assertJsonPath('data.0.name', 'river.jpg');
});

it('filters media files by type via the API', function () {
    createLibraryImage($this->editor);
    createLibraryDocument($this->editor);

    $this->actingAs($this->editor)
        ->getJson(route('admin.api.media.index', ['type' => 'image']))
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs($this->editor)
        ->getJson(route('admin.api.media.index', ['type' => 'document']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'report.pdf');
});

it('updates media metadata', function () {
    $mediaFile = createLibraryImage($this->editor, 'old-name.jpg');

    $this->actingAs($this->editor)
        ->put(route('admin.media.update', $mediaFile), [
            'name' => 'new-name.jpg',
            'alt_text' => 'Updated alt text',
        ])
        ->assertRedirect();

    expect($mediaFile->fresh())
        ->name->toBe('new-name.jpg')
        ->alt_text->toBe('Updated alt text');
});

it('deletes a media file', function () {
    $mediaFile = createLibraryImage($this->editor);

    $this->actingAs($this->editor)
        ->delete(route('admin.media.destroy', $mediaFile))
        ->assertRedirect();

    expect(MediaFile::count())->toBe(0);
});

it('creates media folders and filters files by folder', function () {
    $this->actingAs($this->editor)
        ->postJson(route('admin.media.folders.store'), [
            'name' => 'Баннеры',
            'container' => 'public',
        ])
        ->assertCreated()
        ->assertJsonPath('folder.name', 'Баннеры');

    $folderId = MediaFolder::first()->id;

    createLibraryImage($this->editor, 'in-folder.jpg');
    MediaFile::latest('id')->first()->update(['media_folder_id' => $folderId]);
    createLibraryImage($this->editor, 'outside.jpg');

    $this->actingAs($this->editor)
        ->get(route('admin.media.index', ['folder_id' => $folderId]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('mediaFiles.data', 1)
            ->where('mediaFiles.data.0.name', 'in-folder.jpg'));
});

it('updates focal point metadata for an image', function () {
    $mediaFile = createLibraryImage($this->editor);

    $this->actingAs($this->editor)
        ->put(route('admin.media.update', $mediaFile), [
            'name' => $mediaFile->name,
            'alt_text' => $mediaFile->alt_text,
            'focal_x' => 25.5,
            'focal_y' => 75,
        ])
        ->assertRedirect();

    expect($mediaFile->fresh())
        ->focal_x->toEqual(25.5)
        ->focal_y->toEqual(75);
});

it('uploads a file into the selected folder', function () {
    $folder = MediaFolder::create([
        'name' => 'Документы',
        'container' => 'public',
    ]);

    $this->actingAs($this->editor)
        ->post(route('admin.media.store'), [
            'file' => UploadedFile::fake()->image('folder-upload.jpg'),
            'folder_id' => $folder->id,
        ])
        ->assertRedirect();

    expect(MediaFile::first()?->media_folder_id)->toBe($folder->id);
});

it('stores localized alt text and tags on media files', function () {
    $mediaFile = createLibraryImage($this->editor);

    $this->actingAs($this->editor)
        ->put(route('admin.media.update', $mediaFile), [
            'name' => $mediaFile->name,
            'translations' => [
                'ru' => ['alt_text' => 'Горный пейзаж'],
                'en' => ['alt_text' => 'Mountain landscape'],
            ],
            'tags' => ['Hero', 'banner'],
        ])
        ->assertRedirect();

    $mediaFile->refresh()->load(['translations', 'tags']);

    expect($mediaFile->translation('ru')?->alt_text)->toBe('Горный пейзаж')
        ->and($mediaFile->translation('en')?->alt_text)->toBe('Mountain landscape')
        ->and($mediaFile->tags->pluck('name')->sort()->values()->all())->toBe(['banner', 'hero']);
});

it('filters media files by tag', function () {
    $mediaFile = createLibraryImage($this->editor, 'tagged.jpg');
    $mediaFile->syncTags(['press']);

    createLibraryImage($this->editor, 'plain.jpg');

    $this->actingAs($this->editor)
        ->get(route('admin.media.index', ['tag' => 'press']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('mediaFiles.data', 1)
            ->where('mediaFiles.data.0.name', 'tagged.jpg'));
});

it('bulk deletes and moves selected media files', function () {
    $folder = MediaFolder::create([
        'name' => 'Архив',
        'container' => 'public',
    ]);

    $first = createLibraryImage($this->editor, 'one.jpg');
    $second = createLibraryImage($this->editor, 'two.jpg');
    $third = createLibraryImage($this->editor, 'three.jpg');

    $this->actingAs($this->editor)
        ->post(route('admin.media.bulk-move'), [
            'ids' => [$first->id, $second->id],
            'folder_id' => $folder->id,
        ])
        ->assertRedirect();

    expect($first->fresh()->media_folder_id)->toBe($folder->id)
        ->and($second->fresh()->media_folder_id)->toBe($folder->id)
        ->and($third->fresh()->media_folder_id)->toBeNull();

    $this->actingAs($this->editor)
        ->post(route('admin.media.bulk-destroy'), [
            'ids' => [$first->id, $second->id],
        ])
        ->assertRedirect();

    expect(MediaFile::count())->toBe(1)
        ->and(MediaTag::count())->toBeGreaterThanOrEqual(0)
        ->and(MediaUsage::count())->toBe(0);
});
