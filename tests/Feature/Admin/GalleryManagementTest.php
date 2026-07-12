<?php

use App\Enums\Role;
use App\Models\Gallery;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);
    Storage::fake('public');

    $this->editor = User::factory()->withTwoFactor()->create();
    $this->editor->assignRole(Role::Moderator->value);
});

function galleryPayload(array $overrides = []): array
{
    return array_merge([
        'status' => 'published',
        'sort_order' => 0,
        'translations' => [
            'tj' => ['title' => 'Чорабинӣ', 'slug' => 'chorabini', 'description' => 'Тавсиф'],
            'ru' => ['title' => 'Мероприятие', 'slug' => 'meropriyatie', 'description' => 'Описание'],
            'en' => ['title' => '', 'slug' => ''],
        ],
    ], $overrides);
}

it('redirects guests to login', function () {
    $this->get(route('admin.gallery.index'))->assertRedirect(route('login'));
});

it('forbids users without a CMS role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.gallery.index'))
        ->assertForbidden();
});

it('renders the gallery list and form', function () {
    $gallery = Gallery::factory()->create();
    $gallery->upsertTranslations(['tj' => ['title' => 'Тест', 'slug' => 'test-gal']]);

    $this->actingAs($this->editor)->get(route('admin.gallery.index'))
        ->assertRedirect(route('admin.content.index', 'gallery'));

    $this->actingAs($this->editor)->get(route('admin.content.index', 'gallery'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/content/index')
            ->where('contentType.handle', 'gallery')
            ->has('entries.data', 1));

    $this->actingAs($this->editor)->get(route('admin.gallery.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/content/form')
            ->where('contentType.handle', 'gallery')
            ->has('locales', 3)
            ->has('statuses', 4)
            ->has('blueprint')
            ->has('existingPhotos'));
});

it('creates a gallery with translations and uploaded photos', function () {
    $this->actingAs($this->editor)
        ->post(route('admin.gallery.store'), galleryPayload([
            'photos' => [
                UploadedFile::fake()->image('one.jpg'),
                UploadedFile::fake()->image('two.jpg'),
            ],
        ]))
        ->assertRedirect(route('admin.content.index', 'gallery'));

    $gallery = Gallery::with('translations')->first();

    expect($gallery->translations)->toHaveCount(2)
        ->and($gallery->getMedia(Gallery::PHOTOS_COLLECTION))->toHaveCount(2);
});

it('requires the default-locale title', function () {
    $payload = galleryPayload();
    $payload['translations']['tj']['title'] = '';

    $this->actingAs($this->editor)
        ->from(route('admin.gallery.create'))
        ->post(route('admin.gallery.store'), $payload)
        ->assertSessionHasErrors('translations.tj.title');
});

it('enforces slug uniqueness within a locale', function () {
    $this->actingAs($this->editor)->post(route('admin.gallery.store'), galleryPayload());

    $second = galleryPayload();
    $second['translations']['tj']['slug'] = 'another-gallery';

    $this->actingAs($this->editor)
        ->from(route('admin.gallery.create'))
        ->post(route('admin.gallery.store'), $second)
        ->assertSessionHasErrors('translations.ru.slug');
});

it('removes a photo on update and deletes the gallery', function () {
    $gallery = Gallery::factory()->create();
    $gallery->upsertTranslations(['tj' => ['title' => 'Г', 'slug' => 'g-photo']]);
    $media = $gallery->addMedia(UploadedFile::fake()->image('p.jpg'))
        ->toMediaCollection(Gallery::PHOTOS_COLLECTION);

    $this->actingAs($this->editor)
        ->put(route('admin.gallery.update', $gallery), galleryPayload(['remove_photos' => [$media->id]]))
        ->assertRedirect(route('admin.content.index', 'gallery'));

    expect($gallery->fresh()->getMedia(Gallery::PHOTOS_COLLECTION))->toHaveCount(0);

    $this->actingAs($this->editor)
        ->delete(route('admin.gallery.destroy', $gallery))
        ->assertRedirect(route('admin.content.index', 'gallery'));

    expect(Gallery::find($gallery->id))->toBeNull();
});
