<?php

use App\Enums\Role;
use App\Models\MediaFile;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

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
            ->where('filters.search', '')
            ->where('filters.type', ''));
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
