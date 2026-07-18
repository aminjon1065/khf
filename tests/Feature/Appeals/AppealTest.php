<?php

use App\Enums\AppealStatus;
use App\Enums\Role;
use App\Models\Appeal;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);
});

function appealForm(array $overrides = []): array
{
    return array_merge([
        'category' => 'general',
        'name' => 'Иван Иванов',
        'email' => 'ivan@example.com',
        'phone' => '+992900000000',
        'subject' => 'Вопрос по безопасности',
        'message' => 'Текст обращения.',
        'website' => '',
    ], $overrides);
}

it('renders the public appeal form', function () {
    $this->get(route('appeals.create', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('public/appeals/create')->has('categories', 4));
});

it('accepts a citizen appeal and assigns a reference', function () {
    $this->post(route('appeals.store', ['locale' => 'tj']), appealForm())
        ->assertRedirect(route('appeals.create', ['locale' => 'tj']))
        ->assertSessionHas('appeal_reference');

    $appeal = Appeal::first();

    expect($appeal)->not->toBeNull()
        ->and($appeal->status)->toBe(AppealStatus::New)
        ->and($appeal->reference)->toStartWith('OBR-');
});

it('retries creation when a generated reference collides', function () {
    $year = now()->year;
    Appeal::factory()->create(['reference' => "OBR-{$year}-ABC123"]);

    Str::createRandomStringsUsingSequence(['ABC123', 'DEF456']);

    try {
        $appeal = Appeal::createWithUniqueReference([
            'category' => 'general',
            'name' => 'Collision Test',
            'email' => 'collision@example.tj',
            'phone' => null,
            'subject' => 'Reference collision',
            'message' => 'The second generated reference must be used.',
        ]);
    } finally {
        Str::createRandomStringsNormally();
    }

    expect($appeal->reference)->toBe("OBR-{$year}-DEF456")
        ->and(Appeal::query()->count())->toBe(2);
});

it('rejects a submission with the honeypot filled', function () {
    $this->post(route('appeals.store', ['locale' => 'tj']), appealForm(['website' => 'http://spam']))
        ->assertSessionHasErrors('website');

    expect(Appeal::count())->toBe(0);
});

it('validates required fields', function () {
    $this->post(route('appeals.store', ['locale' => 'tj']), appealForm(['email' => '', 'message' => '']))
        ->assertSessionHasErrors(['email', 'message']);
});

it('tracks an appeal by reference', function () {
    $appeal = Appeal::factory()->create(['subject' => 'Моё обращение']);

    $this->get(route('appeals.track', ['locale' => 'tj', 'reference' => $appeal->reference]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/appeals/track')
            ->where('result.found', true)
            ->missing('result.subject')
        );

    $this->get(route('appeals.track', ['locale' => 'tj', 'reference' => 'OBR-2026-NOPE00']))
        ->assertInertia(fn (Assert $page) => $page->where('result.found', false));
});

it('restricts the CMS appeals queue to staff with permission', function () {
    $this->get(route('admin.appeals.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('admin.appeals.index'))
        ->assertForbidden();
});

it('lets a moderator view and update an appeal', function () {
    $moderator = User::factory()->withTwoFactor()->create();
    $moderator->assignRole(Role::Moderator->value);
    $appeal = Appeal::factory()->create();

    $this->actingAs($moderator)->get(route('admin.appeals.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/appeals/index')->has('appeals.data', 1));

    $this->actingAs($moderator)
        ->put(route('admin.appeals.update', $appeal), [
            'status' => 'in_progress',
            'assigned_to' => $moderator->id,
            'internal_note' => 'Взято в работу',
            'deadline_at' => '2026-07-01',
        ])
        ->assertRedirect(route('admin.appeals.show', $appeal));

    expect($appeal->fresh()->status)->toBe(AppealStatus::InProgress)
        ->and($appeal->fresh()->assigned_to)->toBe($moderator->id)
        ->and($appeal->fresh()->deadline_at->format('Y-m-d'))->toBe('2026-07-01');
});

it('accepts file attachments', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('document.pdf', 100);

    $data = appealForm(['attachments' => [$file]]);

    $this->post(route('appeals.store', ['locale' => 'tj']), $data)
        ->assertRedirect();

    $appeal = Appeal::first();
    expect($appeal->getMedia(Appeal::ATTACHMENTS_COLLECTION))->toHaveCount(1);
});

it('rejects executable appeal attachments', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('shell.php', 100, 'application/x-httpd-php');

    $this->post(route('appeals.store', ['locale' => 'tj']), appealForm([
        'attachments' => [$file],
    ]))->assertSessionHasErrors('attachments.0');

    expect(Appeal::count())->toBe(0);
});

it('exports appeals to csv', function () {
    $moderator = User::factory()->withTwoFactor()->create();
    $moderator->assignRole(Role::Moderator->value);
    Appeal::factory()->count(3)->create();

    $response = $this->actingAs($moderator)
        ->get(route('admin.appeals.export'))
        ->assertOk();

    expect($response->headers->get('Content-type'))->toStartWith('text/csv')
        ->and($response->headers->get('Content-Disposition'))->toContain('attachment; filename="appeals-export-');
});
