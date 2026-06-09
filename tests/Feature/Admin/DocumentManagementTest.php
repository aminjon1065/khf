<?php

use App\Enums\DocumentType;
use App\Enums\Role;
use App\Models\Document;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);

    $this->editor = User::factory()->withTwoFactor()->create();
    $this->editor->assignRole(Role::Moderator->value);
});

function documentPayload(array $overrides = []): array
{
    return array_merge([
        'type' => 'law',
        'source' => 'КЧС',
        'document_date' => '2026-01-15',
        'status' => 'published',
        'sort_order' => 0,
        'translations' => [
            'tj' => ['name' => 'Қонун', 'description' => 'Тавсиф'],
            'ru' => ['name' => 'Закон', 'description' => 'Описание'],
            'en' => ['name' => '', 'description' => ''],
        ],
    ], $overrides);
}

it('forbids users without a CMS role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.documents.index'))
        ->assertForbidden();
});

it('creates a document with files on the private disk', function () {
    Storage::fake('local');

    $this->actingAs($this->editor)
        ->post(route('admin.documents.store'), documentPayload([
            'files' => [UploadedFile::fake()->create('law.pdf', 100, 'application/pdf')],
        ]))
        ->assertRedirect(route('admin.documents.index'));

    $document = Document::with('translations')->first();

    expect($document->type)->toBe(DocumentType::Law)
        ->and($document->translations)->toHaveCount(2)
        ->and($document->getMedia(Document::FILES_COLLECTION))->toHaveCount(1);
});

it('rejects executable file uploads', function () {
    Storage::fake('local');

    $this->actingAs($this->editor)
        ->from(route('admin.documents.create'))
        ->post(route('admin.documents.store'), documentPayload([
            'files' => [UploadedFile::fake()->create('virus.exe', 10)],
        ]))
        ->assertSessionHasErrors('files.0');
});

it('validates the default-locale name', function () {
    $payload = documentPayload();
    $payload['translations']['tj']['name'] = '';

    $this->actingAs($this->editor)
        ->from(route('admin.documents.create'))
        ->post(route('admin.documents.store'), $payload)
        ->assertSessionHasErrors('translations.tj.name');
});

it('renders the public registry and downloads a file via the controlled route', function () {
    Storage::fake('local');

    $document = Document::factory()->create();
    $document->upsertTranslations(['tj' => ['name' => 'Қонун', 'description' => 'd']]);
    $document->addMedia(UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf'))
        ->toMediaCollection(Document::FILES_COLLECTION);

    $this->get(route('documents.index', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/documents/index')
            ->has('documents.data', 1)
            ->has('documents.data.0.files', 1)
        );

    $media = $document->getFirstMedia(Document::FILES_COLLECTION);

    $this->get(route('documents.download', ['locale' => 'tj', 'document' => $document->id, 'media' => $media->id]))
        ->assertOk()
        ->assertDownload('doc.pdf');
});

it('returns 404 when downloading from a draft document', function () {
    Storage::fake('local');

    $document = Document::factory()->draft()->create();
    $document->upsertTranslations(['tj' => ['name' => 'Черновик']]);
    $document->addMedia(UploadedFile::fake()->create('secret.pdf', 50, 'application/pdf'))
        ->toMediaCollection(Document::FILES_COLLECTION);

    $media = $document->getFirstMedia(Document::FILES_COLLECTION);

    $this->get(route('documents.download', ['locale' => 'tj', 'document' => $document->id, 'media' => $media->id]))
        ->assertNotFound();
});

it('soft deletes, restores and force deletes a document', function () {
    $document = Document::factory()->create();
    $document->upsertTranslations(['tj' => ['name' => 'Т']]);

    $this->actingAs($this->editor)->delete(route('admin.documents.destroy', $document));
    expect(Document::count())->toBe(0)->and(Document::onlyTrashed()->count())->toBe(1);

    $this->actingAs($this->editor)->patch(route('admin.documents.restore', $document));
    expect(Document::count())->toBe(1);

    $this->actingAs($this->editor)->delete(route('admin.documents.destroy', $document));
    $this->actingAs($this->editor)->delete(route('admin.documents.force-delete', $document));
    expect(Document::withTrashed()->count())->toBe(0);
});
