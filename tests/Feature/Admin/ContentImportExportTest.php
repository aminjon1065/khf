<?php

use App\Cms\ContentTypeRegistry;
use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Faq;
use App\Models\Post;
use App\Models\User;
use App\Services\Admin\ContentExportService;
use App\Services\Admin\ContentImportService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->withTwoFactor()->create();
    $this->admin->assignRole(Role::SuperAdmin->value);
});

it('exports a collection as json', function () {
    $post = Post::factory()->create(['status' => ContentStatus::Published, 'published_at' => now()]);
    $post->translations()->create([
        'locale' => 'tj',
        'title' => 'Экспорт',
        'slug' => 'export-me',
        'excerpt' => 'Анонс',
        'body' => 'Текст',
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.content.export', ['type' => 'post', 'format' => 'json']));

    $response->assertOk();
    $response->assertHeader('content-disposition');

    $payload = json_decode($response->streamedContent(), true, flags: JSON_THROW_ON_ERROR);

    expect($payload['schema'])->toBe('khf-cms-export')
        ->and($payload['collection'])->toBe('post')
        ->and($payload['entries'])->toHaveCount(1)
        ->and($payload['entries'][0]['translations']['tj']['title'])->toBe('Экспорт');
});

it('exports only selected entries when ids are provided', function () {
    $first = Post::factory()->create();
    $first->translations()->create(['locale' => 'tj', 'title' => 'First', 'slug' => 'first', 'body' => 'a', 'excerpt' => 'a']);

    $second = Post::factory()->create();
    $second->translations()->create(['locale' => 'tj', 'title' => 'Second', 'slug' => 'second', 'body' => 'b', 'excerpt' => 'b']);

    $payload = app(ContentExportService::class)->toJsonPayload(
        app(ContentTypeRegistry::class)->get('post'),
        [
            'search' => '',
            'sort' => 'published_at',
            'direction' => 'desc',
            'status' => null,
            'trashed' => false,
        ],
        [$first->id],
    );

    expect($payload['entries'])->toHaveCount(1)
        ->and($payload['entries'][0]['id'])->toBe($first->id);
});

it('imports json entries into a collection', function () {
    $payload = [
        'schema' => 'khf-cms-export',
        'version' => 1,
        'collection' => 'faq',
        'entries' => [
            [
                'attributes' => ['status' => ContentStatus::Draft->value, 'sort_order' => 1],
                'translations' => [
                    'tj' => [
                        'question' => 'Вопрос 1',
                        'answer' => 'Ответ 1',
                    ],
                ],
            ],
        ],
    ];

    $file = UploadedFile::fake()->createWithContent('faqs.json', json_encode($payload, JSON_THROW_ON_ERROR));

    $this->actingAs($this->admin)
        ->post(route('admin.content.import', 'faq'), [
            'file' => $file,
        ])
        ->assertRedirect(route('admin.content.index', 'faq'));

    expect(Faq::count())->toBe(1);
});

it('updates existing entries when requested', function () {
    $faq = Faq::factory()->create(['status' => ContentStatus::Draft]);
    $faq->translations()->create([
        'locale' => 'tj',
        'question' => 'Старый вопрос',
        'answer' => 'Старый ответ',
    ]);

    $payload = [
        'schema' => 'khf-cms-export',
        'version' => 1,
        'collection' => 'faq',
        'entries' => [
            [
                'id' => $faq->id,
                'attributes' => ['status' => ContentStatus::Published->value, 'sort_order' => 2],
                'translations' => [
                    'tj' => [
                        'question' => 'Новый вопрос',
                        'answer' => 'Новый ответ',
                    ],
                ],
            ],
        ],
    ];

    $stats = app(ContentImportService::class)->importJson(
        app(ContentTypeRegistry::class)->get('faq'),
        $payload,
        updateExisting: true,
    );

    $faq->refresh();

    expect($stats)->toBe(['created' => 0, 'updated' => 1, 'skipped' => 0])
        ->and($faq->status)->toBe(ContentStatus::Published)
        ->and($faq->translation('tj')?->question)->toBe('Новый вопрос');
});

it('imports rows from csv', function () {
    $csv = "entry_id;status;locale;question;answer\n;draft;tj;CSV вопрос;CSV ответ\n";

    $stats = app(ContentImportService::class)->importCsv(
        app(ContentTypeRegistry::class)->get('faq'),
        $csv,
    );

    expect($stats['created'])->toBe(1)
        ->and(Faq::first()?->translation('tj')?->question)->toBe('CSV вопрос');
});

it('rejects import for guests', function () {
    $file = UploadedFile::fake()->create('faqs.json', 100, 'application/json');

    $this->post(route('admin.content.import', 'faq'), ['file' => $file])
        ->assertRedirect(route('login'));
});

it('rejects json files for another collection', function () {
    $payload = [
        'schema' => 'khf-cms-export',
        'version' => 1,
        'collection' => 'post',
        'entries' => [],
    ];

    $file = UploadedFile::fake()->createWithContent('posts.json', json_encode($payload, JSON_THROW_ON_ERROR));

    $this->actingAs($this->admin)
        ->from(route('admin.content.index', 'faq'))
        ->post(route('admin.content.import', 'faq'), ['file' => $file])
        ->assertSessionHasErrors('file');
});
